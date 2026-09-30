<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Campaign;
use App\Models\CampaignAllocation;
use App\Models\User;
use App\Support\PonderationGrille;
use App\Support\Quitus;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::withCount('allocations')->orderByDesc('annee_n1')->get();

        return view('campaign.index', compact('campaigns'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->isDshn(), 403);

        $data = $request->validate([
            'annee_n1' => ['required', 'integer', 'min:2000', 'max:2100', 'unique:campaigns,annee_n1'],
        ]);

        $campaign = Campaign::create(['annee_n1' => $data['annee_n1'], 'etape' => 3]);

        ActivityLog::record('created', "a créé la campagne {$campaign->annee_n1}", $campaign->id, (string) $campaign->annee_n1);

        return redirect()->route('campagnes.show', $campaign)->with('status', "Campagne {$campaign->annee_n1} créée.");
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['allocations' => fn ($q) => $q->with([
            'federation',
            'federation.reports' => fn ($r) => $r->where('type', 'programme_reamenage')->where('year', $campaign->annee_n1)->where('status', '!=', 'brouillon'),
        ])->orderByDesc('score_total')]);

        $grille = $campaign->grille();

        return view('campaign.show', [
            'campaign' => $campaign,
            'rubriques' => $grille->rubriques(),
            'criteres' => $grille->criteres(),
            'paliers' => $grille->paliers(),
            // Détail des paliers pour le calcul en direct de la catégorie
            // pendant la saisie de la pondération.
            'paliersDetail' => $grille->paliersDetail(),
            'pointsMax' => $grille->pointsMax(),
            // Récapitulatif par rubrique : sous-total de chaque rubrique pour
            // chaque fédération, à partir des scores saisis en pondération.
            'recapLignes' => $campaign->allocations->map(fn (CampaignAllocation $allocation) => [
                'allocation' => $allocation,
                'sous_totaux' => $grille->sousTotauxParRubrique($allocation->criteres_scores ?? []),
            ]),
        ]);
    }

    public function advanceToPonderation(Campaign $campaign)
    {
        abort_unless(Auth::user()->isDshn(), 403);
        abort_unless($campaign->etape === 3, 404);

        $grille = PonderationGrille::current();

        if ($grille->isEmpty()) {
            return back()->withErrors(['ponderation' => "Aucun critère de pondération n'est paramétré. Renseignez la grille avant de lancer la pondération."]);
        }

        $rapportYear = $campaign->annee_n1 - 1;

        $federations = User::where('role', 'federation')
            ->where('status', 'active')
            ->whereHas('reports', fn ($q) => $q->where('type', 'rapport_activite')->where('year', $rapportYear)->where('status', 'valide'))
            ->whereHas('reports', fn ($q) => $q->where('type', 'programme_budgetise')->where('year', $campaign->annee_n1)->where('status', 'valide'))
            ->get();

        foreach ($federations as $federation) {
            CampaignAllocation::firstOrCreate([
                'campaign_id' => $campaign->id,
                'user_id' => $federation->id,
            ]);
        }

        // La grille est figée ici : la campagne gardera ces critères et ces
        // paliers même si la DSHN fait évoluer le paramétrage ensuite.
        $campaign->update(['etape' => 4, 'grille_ponderation' => $grille->toSnapshot()]);

        ActivityLog::record(
            'updated',
            "a lancé la pondération de la campagne {$campaign->annee_n1} ({$federations->count()} fédération(s) retenue(s))",
            $campaign->id,
            (string) $campaign->annee_n1
        );

        return back()->with('status', $federations->count().' fédération(s) retenue(s) — pondération ouverte.');
    }

    public function updatePonderation(Request $request, Campaign $campaign)
    {
        abort_unless(Auth::user()->isDshn(), 403);
        abort_unless($campaign->etape === 4, 404);

        $grille = $campaign->grille();
        $criteres = collect($grille->criteres())->keyBy('slug');

        $data = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*' => ['array'],
        ]);

        foreach ($data['scores'] as $allocationId => $scores) {
            $allocation = $campaign->allocations()->find($allocationId);

            if (! $allocation) {
                continue;
            }

            // Un critère laissé vide reste vide (null) plutôt que de devenir 0 :
            // « non évalué » et « évalué à zéro » ne disent pas la même chose.
            // Les totaux traitent null comme 0.
            $clean = [];
            foreach ($criteres as $slug => $critere) {
                $valeur = $scores[$slug] ?? null;
                $clean[$slug] = ($valeur === null || $valeur === '')
                    ? null
                    : max(0, min((float) $critere['max'], (float) $valeur));
            }

            $allocation->criteres_scores = $clean;
            $allocation->recalculerScores($grille);
            $allocation->save();
        }

        ActivityLog::record('updated', "a mis à jour la pondération de la campagne {$campaign->annee_n1}", $campaign->id, (string) $campaign->annee_n1);

        return back()->with('status', 'Pondération enregistrée.');
    }

    /**
     * Grille de pondération au format Excel, sur le modèle de la feuille
     * officielle « PONDERATION PAR ACTI PAR FEDE » : critères en lignes,
     * une colonne par fédération, sous-totaux de rubrique en vert.
     */
    public function exportPonderation(Campaign $campaign)
    {
        abort_unless(Auth::user()->isDshn(), 403);

        $grille = $campaign->grille();
        $rubriques = $grille->rubriques();
        $allocations = $campaign->allocations()->with('federation')->orderByDesc('score_total')->get();

        $vert = 'FF92D050';
        $couleursCategorie = ['A' => 'FF92D050', 'B' => 'FFFFFF00', 'C' => 'FF00B0F0', 'D' => 'FFFFC7CE'];

        $classeur = new Spreadsheet();
        $feuille = $classeur->getActiveSheet();
        $feuille->setTitle('Pondération');

        $derniereColonne = Coordinate::stringFromColumnIndex(4 + $allocations->count());

        $feuille->setCellValue('A1', 'Pondération par activité par fédération — Campagne '.$campaign->annee_n1);
        $feuille->mergeCells('A1:'.$derniereColonne.'1');
        $feuille->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $feuille->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $ligne = 3;
        $feuille->fromArray(['N°', 'Rubriques', 'Critères', 'Pondération'], null, 'A'.$ligne);
        foreach ($allocations as $index => $allocation) {
            $feuille->setCellValue(Coordinate::stringFromColumnIndex(5 + $index).$ligne, $allocation->federation->federation_name);
        }
        $feuille->getStyle('A'.$ligne.':'.$derniereColonne.$ligne)->getFont()->setBold(true);
        $feuille->getStyle('A'.$ligne.':'.$derniereColonne.$ligne)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_BOTTOM)->setWrapText(true);
        $feuille->getRowDimension($ligne)->setRowHeight(60);

        $numero = 1;
        $lignesTotaux = [];

        foreach ($rubriques as $rubrique) {
            $premiereLigneRubrique = $ligne + 1;

            foreach ($rubrique['criteres'] as $critere) {
                $ligne++;
                $feuille->setCellValue('A'.$ligne, $numero++);
                $feuille->setCellValue('C'.$ligne, $critere['label']);
                $feuille->setCellValue('D'.$ligne, (float) $critere['max']);

                foreach ($allocations as $index => $allocation) {
                    $feuille->setCellValue(
                        Coordinate::stringFromColumnIndex(5 + $index).$ligne,
                        (float) ($allocation->criteres_scores[$critere['slug']] ?? 0)
                    );
                }
            }

            // Libellé de la rubrique fusionné en face de ses critères.
            $feuille->setCellValue('B'.$premiereLigneRubrique, $rubrique['rubrique']);
            if ($ligne > $premiereLigneRubrique) {
                $feuille->mergeCells('B'.$premiereLigneRubrique.':B'.$ligne);
            }
            $feuille->getStyle('B'.$premiereLigneRubrique)->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

            $ligne++;
            $feuille->setCellValue('A'.$ligne, 'Total '.$rubrique['rubrique']);
            $feuille->mergeCells('A'.$ligne.':C'.$ligne);
            $feuille->setCellValue('D'.$ligne, (float) $rubrique['rubrique_max']);

            foreach ($allocations as $index => $allocation) {
                $colonne = Coordinate::stringFromColumnIndex(5 + $index);
                $feuille->setCellValue(
                    $colonne.$ligne,
                    '=SUM('.$colonne.$premiereLigneRubrique.':'.$colonne.($ligne - 1).')'
                );
            }

            $feuille->getStyle('A'.$ligne.':'.$derniereColonne.$ligne)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($vert);
            $feuille->getStyle('A'.$ligne.':'.$derniereColonne.$ligne)->getFont()->setBold(true);

            $lignesTotaux[] = $ligne;
        }

        $ligne++;
        $feuille->setCellValue('A'.$ligne, 'TOTAL');
        $feuille->mergeCells('A'.$ligne.':C'.$ligne);
        $feuille->setCellValue('D'.$ligne, (float) $grille->pointsMax());
        foreach ($allocations as $index => $allocation) {
            $colonne = Coordinate::stringFromColumnIndex(5 + $index);
            $cellules = array_map(fn ($l) => $colonne.$l, $lignesTotaux);
            $feuille->setCellValue($colonne.$ligne, '=SUM('.implode(',', $cellules).')');
        }
        $feuille->getStyle('A'.$ligne.':'.$derniereColonne.$ligne)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($vert);
        $feuille->getStyle('A'.$ligne.':'.$derniereColonne.$ligne)->getFont()->setBold(true);

        $ligne++;
        $feuille->setCellValue('A'.$ligne, 'CATEGORIE');
        $feuille->mergeCells('A'.$ligne.':C'.$ligne);
        foreach ($allocations as $index => $allocation) {
            $colonne = Coordinate::stringFromColumnIndex(5 + $index);
            $feuille->setCellValue($colonne.$ligne, $allocation->categorie ?? '');

            if (isset($couleursCategorie[$allocation->categorie])) {
                $feuille->getStyle($colonne.$ligne)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($couleursCategorie[$allocation->categorie]);
            }
        }
        $feuille->getStyle('A'.$ligne.':'.$derniereColonne.$ligne)->getFont()->setBold(true);

        $feuille->getStyle('A3:'.$derniereColonne.$ligne)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $feuille->getStyle('D3:'.$derniereColonne.$ligne)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $feuille->getColumnDimension('A')->setWidth(5);
        $feuille->getColumnDimension('B')->setWidth(22);
        $feuille->getColumnDimension('C')->setWidth(45);
        $feuille->getColumnDimension('D')->setWidth(12);
        foreach ($allocations as $index => $allocation) {
            $feuille->getColumnDimension(Coordinate::stringFromColumnIndex(5 + $index))->setWidth(14);
        }
        $feuille->freezePane('E4');

        $fichier = 'ponderation-campagne-'.$campaign->annee_n1.'.xlsx';
        $chemin = tempnam(sys_get_temp_dir(), 'pond');

        (new Xlsx($classeur))->save($chemin);
        $classeur->disconnectWorksheets();

        return response()->download($chemin, $fichier)->deleteFileAfterSend(true);
    }

    /**
     * Récapitulatif par rubrique au format Excel, sur le modèle de la feuille
     * officielle « RECAP PAR RUBRIQUE PAR FEDE » : une colonne par rubrique,
     * la ligne des barèmes, le total et les catégories.
     */
    public function exportRecap(Campaign $campaign)
    {
        abort_unless(Auth::user()->isDshn(), 403);

        $grille = $campaign->grille();
        $rubriques = $grille->rubriques();
        $allocations = $campaign->allocations()->with('federation')->orderByDesc('score_total')->get();

        $orange = 'FFF4B183';
        $couleursCategorie = ['A' => 'FF92D050', 'B' => 'FFFFFF00', 'C' => 'FF00B0F0', 'D' => 'FFFFC7CE'];

        $classeur = new Spreadsheet();
        $feuille = $classeur->getActiveSheet();
        $feuille->setTitle('Récap par rubrique');

        // N° + Structures + rubriques + Total + Catégories + Catégories ajustées + Montant
        $nombreColonnes = 2 + count($rubriques) + 4;
        $derniereColonne = Coordinate::stringFromColumnIndex($nombreColonnes);

        $feuille->setCellValue('A1', 'Récapitulatif par rubrique par fédération — Campagne '.$campaign->annee_n1);
        $feuille->mergeCells('A1:'.$derniereColonne.'1');
        $feuille->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $feuille->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $ligneEntete = 3;
        $entetes = array_merge(
            ['N°', 'Structures'],
            array_column($rubriques, 'rubrique'),
            ['Total points', 'Catégories', 'Catégories ajustées', 'Montant']
        );
        $feuille->fromArray($entetes, null, 'A'.$ligneEntete);
        $feuille->getStyle('A'.$ligneEntete.':'.$derniereColonne.$ligneEntete)->getFont()->setBold(true);
        $feuille->getStyle('A'.$ligneEntete.':'.$derniereColonne.$ligneEntete)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $feuille->getRowDimension($ligneEntete)->setRowHeight(45);

        // Ligne des barèmes, sur le fond orange du document.
        $ligneBaremes = $ligneEntete + 1;
        foreach ($rubriques as $index => $rubrique) {
            $feuille->setCellValue(Coordinate::stringFromColumnIndex(3 + $index).$ligneBaremes, (float) $rubrique['rubrique_max']);
        }
        $colonneTotal = Coordinate::stringFromColumnIndex(3 + count($rubriques));
        $feuille->setCellValue($colonneTotal.$ligneBaremes, (float) $grille->pointsMax());
        $feuille->getStyle('C'.$ligneBaremes.':'.$colonneTotal.$ligneBaremes)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($orange);
        $feuille->getStyle('A'.$ligneBaremes.':'.$derniereColonne.$ligneBaremes)->getFont()->setBold(true);

        $ligne = $ligneBaremes;
        foreach ($allocations as $index => $allocation) {
            $ligne++;
            $scores = $allocation->criteres_scores ?? [];

            $feuille->setCellValue('A'.$ligne, $index + 1);
            $feuille->setCellValue('B'.$ligne, $allocation->federation->federation_name);

            foreach ($rubriques as $rubriqueIndex => $rubrique) {
                $sousTotal = collect($rubrique['criteres'])->sum(fn ($critere) => (float) ($scores[$critere['slug']] ?? 0));
                $feuille->setCellValue(Coordinate::stringFromColumnIndex(3 + $rubriqueIndex).$ligne, $sousTotal);
            }

            $feuille->setCellValue($colonneTotal.$ligne, (float) ($allocation->score_total ?? 0));

            $colonneCategorie = Coordinate::stringFromColumnIndex(4 + count($rubriques));
            $feuille->setCellValue($colonneCategorie.$ligne, $allocation->categorie ?? '');
            $feuille->setCellValue(Coordinate::stringFromColumnIndex(5 + count($rubriques)).$ligne, $allocation->categorie_ajustee ?? '');

            $montant = $allocation->montant_final ?? $allocation->montant_arbitre ?? $allocation->montant_propose;
            if ($montant !== null) {
                $feuille->setCellValue(Coordinate::stringFromColumnIndex(6 + count($rubriques)).$ligne, (float) $montant);
            }

            if (isset($couleursCategorie[$allocation->categorie])) {
                $feuille->getStyle($colonneCategorie.$ligne)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($couleursCategorie[$allocation->categorie]);
            }
            $feuille->getStyle($colonneCategorie.$ligne)->getFont()->setBold(true);
        }

        $feuille->getStyle('A'.$ligneEntete.':'.$derniereColonne.$ligne)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $feuille->getStyle('C'.$ligneEntete.':'.$derniereColonne.$ligne)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $feuille->getStyle(Coordinate::stringFromColumnIndex($nombreColonnes).($ligneBaremes + 1).':'.$derniereColonne.$ligne)
            ->getNumberFormat()->setFormatCode('# ##0');

        $feuille->getColumnDimension('A')->setWidth(5);
        $feuille->getColumnDimension('B')->setWidth(42);
        for ($colonne = 3; $colonne <= $nombreColonnes; $colonne++) {
            $feuille->getColumnDimension(Coordinate::stringFromColumnIndex($colonne))->setWidth(16);
        }
        $feuille->freezePane('C'.($ligneBaremes + 1));

        $fichier = 'recap-rubriques-campagne-'.$campaign->annee_n1.'.xlsx';
        $chemin = tempnam(sys_get_temp_dir(), 'recap');

        (new Xlsx($classeur))->save($chemin);
        $classeur->disconnectWorksheets();

        return response()->download($chemin, $fichier)->deleteFileAfterSend(true);
    }

    /**
     * Classement au format Excel, sur le modèle de la feuille officielle
     * « CLASSEMENT PAR FEDERATION » : fédérations classées par points, avec
     * leur catégorie et le montant proposé.
     */
    /**
     * Répartition définitive : le classement complété des montants arbitré et
     * final, disponible une fois la validation du Ministre acquise.
     */
    public function exportRepartitionDefinitive(Campaign $campaign)
    {
        abort_unless($campaign->ministre_decision === 'valide', 404);

        return $this->exportClassement($campaign, true);
    }

    public function exportClassement(Campaign $campaign, bool $avecMontantsFinaux = false)
    {
        abort_unless(Auth::user()->isDshn(), 403);

        $allocations = $campaign->allocations()->with('federation')->orderByDesc('score_total')->get();

        // Cette feuille a ses propres couleurs, plus sourdes que celles du
        // récapitulatif : elles sont reprises telles quelles.
        $couleursCategorie = [
            'A' => ['fond' => 'FFC6EFCE', 'texte' => 'FF006100'],
            'B' => ['fond' => 'FFFFEB9C', 'texte' => 'FF9C6500'],
            'C' => ['fond' => 'FF0070C0', 'texte' => 'FFFFFFFF'],
            'D' => ['fond' => 'FFFFC7CE', 'texte' => 'FF9C0006'],
        ];

        $classeur = new Spreadsheet();
        $feuille = $classeur->getActiveSheet();
        $feuille->setTitle('Classement');

        $derniereColonne = $avecMontantsFinaux ? 'H' : 'F';

        $feuille->setCellValue('A1', ($avecMontantsFinaux ? 'Répartition définitive par fédération' : 'Classement et montant proposé par fédérations').' — Campagne '.$campaign->annee_n1);
        $feuille->mergeCells('A1:'.$derniereColonne.'1');
        $feuille->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $feuille->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $ligneEntete = 3;
        $entetes = ['N°', 'Fédérations sportives et de loisirs', 'Nbre de points', 'Catégories', 'Catégories ajustée', 'Montant proposé'];
        if ($avecMontantsFinaux) {
            $entetes[] = 'Montant arbitré';
            $entetes[] = 'Montant final';
        }

        $feuille->fromArray($entetes, null, 'A'.$ligneEntete);
        $feuille->getStyle('A'.$ligneEntete.':'.$derniereColonne.$ligneEntete)->getFont()->setBold(true);
        $feuille->getStyle('A'.$ligneEntete.':'.$derniereColonne.$ligneEntete)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $feuille->getRowDimension($ligneEntete)->setRowHeight(32);

        $ligne = $ligneEntete;
        foreach ($allocations as $index => $allocation) {
            $ligne++;

            $feuille->setCellValue('A'.$ligne, $index + 1);
            $feuille->setCellValue('B'.$ligne, $allocation->federation->federation_name);
            $feuille->setCellValue('C'.$ligne, (float) ($allocation->score_total ?? 0));
            $feuille->setCellValue('D'.$ligne, $allocation->categorie ?? '');
            $feuille->setCellValue('E'.$ligne, $allocation->categorie_ajustee ?? '');

            if ($allocation->montant_propose !== null) {
                $feuille->setCellValue('F'.$ligne, (float) $allocation->montant_propose);
            }

            if ($avecMontantsFinaux) {
                if ($allocation->montant_arbitre !== null) {
                    $feuille->setCellValue('G'.$ligne, (float) $allocation->montant_arbitre);
                }
                if ($allocation->montant_final !== null) {
                    $feuille->setCellValue('H'.$ligne, (float) $allocation->montant_final);
                }
            }

            if (isset($couleursCategorie[$allocation->categorie])) {
                $style = $feuille->getStyle('D'.$ligne);
                $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()
                    ->setARGB($couleursCategorie[$allocation->categorie]['fond']);
                $style->getFont()->setBold(true)->getColor()
                    ->setARGB($couleursCategorie[$allocation->categorie]['texte']);
            }
        }

        $feuille->getStyle('A'.$ligneEntete.':'.$derniereColonne.$ligne)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $feuille->getStyle('A'.$ligneEntete.':A'.$ligne)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $feuille->getStyle('C'.$ligneEntete.':E'.$ligne)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $feuille->getStyle('F'.($ligneEntete + 1).':'.$derniereColonne.$ligne)->getNumberFormat()->setFormatCode('# ##0');

        if ($avecMontantsFinaux) {
            $feuille->getStyle('H'.$ligneEntete.':H'.$ligne)->getFont()->setBold(true);
        }

        $feuille->getColumnDimension('A')->setWidth(5);
        $feuille->getColumnDimension('B')->setWidth(45);
        foreach (range('C', $derniereColonne) as $colonne) {
            $feuille->getColumnDimension($colonne)->setWidth(18);
        }
        $feuille->freezePane('A'.($ligneEntete + 1));

        $fichier = ($avecMontantsFinaux ? 'repartition-definitive' : 'classement').'-campagne-'.$campaign->annee_n1.'.xlsx';
        $chemin = tempnam(sys_get_temp_dir(), 'classement');

        (new Xlsx($classeur))->save($chemin);
        $classeur->disconnectWorksheets();

        return response()->download($chemin, $fichier)->deleteFileAfterSend(true);
    }

    public function confirmPonderation(Campaign $campaign)
    {
        abort_unless(Auth::user()->isDshn(), 403);
        abort_unless($campaign->etape === 4, 404);

        $campaign->update(['etape' => 5]);

        ActivityLog::record('updated', "a validé la pondération de la campagne {$campaign->annee_n1}", $campaign->id, (string) $campaign->annee_n1);

        return back()->with('status', 'Pondération validée — catégorisation calculée.');
    }

    public function advanceToRepartition(Campaign $campaign)
    {
        abort_unless(Auth::user()->isDshn(), 403);
        abort_unless($campaign->etape === 5, 404);

        $campaign->update(['etape' => 6]);

        ActivityLog::record('updated', "a confirmé la catégorisation de la campagne {$campaign->annee_n1}", $campaign->id, (string) $campaign->annee_n1);

        return back()->with('status', 'Catégorisation confirmée — passage à la répartition.');
    }

    public function updateRepartition(Request $request, Campaign $campaign)
    {
        abort_unless(Auth::user()->isDshn(), 403);
        abort_unless($campaign->etape === 6, 404);

        $paliers = $campaign->grille()->paliers();

        $data = $request->validate([
            'bareme' => ['required', 'array'],
            'bareme.*' => ['nullable', 'numeric', 'min:0'],
            'overrides' => ['nullable', 'array'],
            'overrides.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $bareme = [];
        foreach ($paliers as $palier) {
            $bareme[$palier] = (float) ($data['bareme'][$palier] ?? 0);
        }

        $campaign->update(['bareme_repartition' => $bareme]);

        $overrides = $data['overrides'] ?? [];

        foreach ($campaign->allocations as $allocation) {
            $override = $overrides[$allocation->id] ?? null;
            $categorieAjustee = $allocation->categorie_ajustee;

            $allocation->update([
                'montant_propose' => $override !== null && $override !== ''
                    ? (float) $override
                    : ($bareme[$categorieAjustee] ?? 0),
            ]);
        }

        ActivityLog::record('updated', "a mis à jour le barème de répartition de la campagne {$campaign->annee_n1}", $campaign->id, (string) $campaign->annee_n1);

        return back()->with('status', 'Répartition mise à jour.');
    }

    public function submitToDg(Campaign $campaign)
    {
        abort_unless(Auth::user()->isDshn(), 403);
        abort_unless($campaign->etape === 6, 404);

        $campaign->update(['etape' => 7]);

        ActivityLog::record('updated', "a soumis la répartition de la campagne {$campaign->annee_n1} au DG", $campaign->id, (string) $campaign->annee_n1);

        return back()->with('status', 'Répartition soumise au Directeur Général.');
    }

    public function dgValidate(Campaign $campaign)
    {
        abort_unless(Auth::user()->isDg(), 403);
        abort_unless($campaign->etape === 7, 404);

        $campaign->update([
            'etape' => 8,
            'dg_decision' => 'valide',
            'dg_decided_at' => now(),
            'dg_rejection_reason' => null,
        ]);

        ActivityLog::record('validated', "a pré-validé la répartition de la campagne {$campaign->annee_n1}", $campaign->id, (string) $campaign->annee_n1);

        return back()->with('status', 'Répartition pré-validée.');
    }

    public function dgReject(Request $request, Campaign $campaign)
    {
        abort_unless(Auth::user()->isDg(), 403);
        abort_unless($campaign->etape === 7, 404);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $campaign->update([
            'etape' => 6,
            'dg_decision' => 'rejete',
            'dg_decided_at' => now(),
            'dg_rejection_reason' => $data['rejection_reason'],
        ]);

        ActivityLog::record('rejected', "a rejeté la répartition de la campagne {$campaign->annee_n1} (motif : {$data['rejection_reason']})", $campaign->id, (string) $campaign->annee_n1);

        return back()->with('status', 'Répartition rejetée — retour à la DSHN.');
    }

    public function updateArbitrage(Request $request, Campaign $campaign)
    {
        abort_unless(Auth::user()->isComiteArbitrage(), 403);
        abort_unless($campaign->etape === 8, 404);

        $data = $request->validate([
            'montants' => ['required', 'array'],
            'montants.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($data['montants'] as $allocationId => $montant) {
            $allocation = $campaign->allocations()->find($allocationId);

            if (! $allocation) {
                continue;
            }

            $allocation->update(['montant_arbitre' => $montant !== null && $montant !== '' ? (float) $montant : $allocation->montant_propose]);
        }

        ActivityLog::record('updated', "a ajusté l'arbitrage de la campagne {$campaign->annee_n1}", $campaign->id, (string) $campaign->annee_n1);

        return back()->with('status', 'Arbitrage enregistré.');
    }

    public function finalizeArbitrage(Campaign $campaign)
    {
        abort_unless(Auth::user()->isComiteArbitrage(), 403);
        abort_unless($campaign->etape === 8, 404);

        foreach ($campaign->allocations as $allocation) {
            if ($allocation->montant_arbitre === null) {
                $allocation->update(['montant_arbitre' => $allocation->montant_propose]);
            }
        }

        $campaign->update(['etape' => 9]);

        ActivityLog::record('updated', "a finalisé l'arbitrage de la campagne {$campaign->annee_n1}", $campaign->id, (string) $campaign->annee_n1);

        return back()->with('status', 'Arbitrage finalisé — soumis au Ministre.');
    }

    public function ministreValidate(Campaign $campaign)
    {
        abort_unless(Auth::user()->isMinistre(), 403);
        abort_unless($campaign->etape === 9, 404);

        foreach ($campaign->allocations as $allocation) {
            $allocation->update(['montant_final' => $allocation->montant_arbitre]);
        }

        $campaign->update([
            'etape' => 10,
            'ministre_decision' => 'valide',
            'ministre_decided_at' => now(),
            'ministre_rejection_reason' => null,
        ]);

        ActivityLog::record('validated', "a validé la répartition de la campagne {$campaign->annee_n1}", $campaign->id, (string) $campaign->annee_n1);

        return back()->with('status', 'Répartition validée par le Ministre.');
    }

    public function ministreReject(Request $request, Campaign $campaign)
    {
        abort_unless(Auth::user()->isMinistre(), 403);
        abort_unless($campaign->etape === 9, 404);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $campaign->update([
            'etape' => 8,
            'ministre_decision' => 'rejete',
            'ministre_decided_at' => now(),
            'ministre_rejection_reason' => $data['rejection_reason'],
        ]);

        ActivityLog::record('rejected', "a rejeté la répartition de la campagne {$campaign->annee_n1} (motif : {$data['rejection_reason']})", $campaign->id, (string) $campaign->annee_n1);

        return back()->with('status', 'Répartition rejetée — retour au Comité d\'arbitrage.');
    }

    public function organizeSession(Request $request, Campaign $campaign)
    {
        abort_unless(Auth::user()->isComiteArbitrage(), 403);
        abort_unless($campaign->etape === 10, 404);

        $data = $request->validate([
            'session_arbitrage_date' => ['required', 'date'],
        ]);

        $campaign->update([
            'etape' => 11,
            'session_arbitrage_date' => $data['session_arbitrage_date'],
            'session_arbitrage_organized_at' => now(),
        ]);

        ActivityLog::record('updated', "a organisé la session d'arbitrage de la campagne {$campaign->annee_n1}", $campaign->id, (string) $campaign->annee_n1);

        return back()->with('status', "Session d'arbitrage organisée.");
    }

    /**
     * Préparation du quitus : la DSHN fixe le délai de justification de chaque
     * activité du programme réaménagé, vérifie l'aperçu puis délivre. Après
     * délivrance, la page reste consultable en lecture seule.
     */
    public function prepareQuitus(Campaign $campaign, User $federation)
    {
        abort_unless(Auth::user()->isDshn(), 403);
        abort_unless($federation->role === 'federation' && $campaign->etape >= 11, 404);

        return view('campaign.quitus', ['quitus' => Quitus::pour($campaign, $federation)]);
    }

    /** Aperçu du PDF avec les délais saisis, sans rien enregistrer. */
    public function previewQuitus(Request $request, Campaign $campaign, User $federation)
    {
        abort_unless(Auth::user()->isDshn(), 403);
        abort_unless($federation->role === 'federation', 404);

        $quitus = Quitus::pour($campaign, $federation);
        $delais = (array) $request->input('delais', []);

        if (! $quitus->allocation->quitus_delivered_at) {
            foreach ($quitus->lignes as $ligne) {
                $saisie = $delais[$ligne->id] ?? null;
                $ligne->delai_justification = is_string($saisie) && strtotime($saisie) ? Carbon::parse($saisie) : null;
            }
        }

        return Pdf::loadView('pdf.quitus', ['quitus' => $quitus, 'apercu' => true])
            ->stream("apercu-quitus-{$campaign->annee_n1}.pdf");
    }

    public function deliverQuitus(Request $request, Campaign $campaign, User $federation)
    {
        abort_unless(Auth::user()->isDshn(), 403);
        abort_unless($federation->role === 'federation', 404);

        $quitus = Quitus::pour($campaign, $federation);

        abort_unless($quitus->programmeValide(), 422, 'Le programme réaménagé de cette fédération n\'est pas encore validé.');

        if ($quitus->allocation->quitus_delivered_at) {
            return redirect()->route('campagnes.quitus.prepare', [$campaign, $federation])
                ->with('status', 'Ce quitus a déjà été délivré.');
        }

        if ($quitus->lignes->isEmpty()) {
            throw ValidationException::withMessages(['delais' => "Le programme réaménagé ne contient aucune activité : le quitus ne peut pas être délivré."]);
        }

        $delais = (array) $request->input('delais', []);
        $erreurs = [];

        foreach ($quitus->lignes as $index => $ligne) {
            $numero = $index + 1;
            $saisie = $delais[$ligne->id] ?? null;

            if (! is_string($saisie) || ! strtotime($saisie)) {
                $erreurs["delais.{$ligne->id}"] = "Renseignez le délai de justification de l'activité n° {$numero}.";
            } elseif ($ligne->date && Carbon::parse($saisie)->lt($ligne->date)) {
                $erreurs["delais.{$ligne->id}"] = "Le délai de justification de l'activité n° {$numero} ne peut pas précéder sa date ({$ligne->date->format('d/m/Y')}).";
            }
        }

        if ($erreurs) {
            throw ValidationException::withMessages($erreurs);
        }

        DB::transaction(function () use ($quitus, $delais, $campaign, $federation) {
            foreach ($quitus->lignes as $ligne) {
                $ligne->update(['delai_justification' => Carbon::parse($delais[$ligne->id])]);
            }

            $quitus->allocation->update([
                'quitus_delivered_at' => now(),
                'quitus_reference' => 'QUITUS-'.$campaign->annee_n1.'-'.Str::padLeft((string) $federation->id, 4, '0'),
            ]);

            if ($campaign->allocations()->whereNull('quitus_delivered_at')->doesntExist()) {
                $campaign->update(['statut' => 'termine']);
            }
        });

        ActivityLog::record(
            'validated',
            "a délivré le quitus de déblocage de subvention à {$federation->federation_name} pour {$campaign->annee_n1}",
            $federation->id,
            $federation->federation_name
        );

        return redirect()->route('campagnes.quitus.prepare', [$campaign, $federation])
            ->with('status', "Quitus délivré à {$federation->federation_name}. Il est téléchargeable par la DSHN et par la fédération.");
    }

    public function downloadQuitus(Campaign $campaign, User $federation)
    {
        $user = Auth::user();
        abort_unless($user->isDshn() || ($user->isFederation() && $user->id === $federation->id), 403);

        $quitus = Quitus::pour($campaign, $federation);

        abort_unless($quitus->allocation->quitus_delivered_at, 404);

        return Pdf::loadView('pdf.quitus', ['quitus' => $quitus, 'apercu' => false])
            ->download('quitus-'.Str::slug($federation->federation_name).'-'.$campaign->annee_n1.'.pdf');
    }
}
