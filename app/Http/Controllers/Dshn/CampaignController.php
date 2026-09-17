<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Campaign;
use App\Models\CampaignAllocation;
use App\Models\User;
use App\Support\PonderationGrille;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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

            $clean = [];
            foreach ($criteres as $slug => $critere) {
                $valeur = (float) ($scores[$slug] ?? 0);
                $clean[$slug] = max(0, min((float) $critere['max'], $valeur));
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

    public function deliverQuitus(Campaign $campaign, User $federation)
    {
        abort_unless(Auth::user()->isDshn(), 403);
        abort_unless($federation->role === 'federation', 404);

        $allocation = $campaign->allocations()->where('user_id', $federation->id)->firstOrFail();

        $reamenage = $federation->reports()
            ->where('type', 'programme_reamenage')
            ->where('year', $campaign->annee_n1)
            ->first();

        abort_unless($reamenage && $reamenage->status === 'valide', 422, 'Le programme réaménagé de cette fédération n\'est pas encore validé.');

        $allocation->update([
            'quitus_delivered_at' => now(),
            'quitus_reference' => 'QUITUS-'.$campaign->annee_n1.'-'.Str::padLeft((string) $federation->id, 4, '0'),
        ]);

        if ($campaign->allocations()->whereNull('quitus_delivered_at')->doesntExist()) {
            $campaign->update(['statut' => 'termine']);
        }

        ActivityLog::record(
            'validated',
            "a délivré le quitus de déblocage de subvention à {$federation->federation_name} pour {$campaign->annee_n1}",
            $federation->id,
            $federation->federation_name
        );

        return back()->with('status', "Quitus délivré à {$federation->federation_name}.");
    }

    public function downloadQuitus(Campaign $campaign, User $federation)
    {
        $user = Auth::user();
        abort_unless($user->isDshn() || ($user->isFederation() && $user->id === $federation->id), 403);

        $allocation = $campaign->allocations()->where('user_id', $federation->id)->firstOrFail();

        abort_unless($allocation->quitus_delivered_at, 404);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.quitus', [
            'campaign' => $campaign,
            'federation' => $federation,
            'allocation' => $allocation,
        ]);

        return $pdf->download("quitus-{$federation->federation_name}-{$campaign->annee_n1}.pdf");
    }
}
