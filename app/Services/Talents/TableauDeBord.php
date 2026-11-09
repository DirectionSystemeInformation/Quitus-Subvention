<?php

namespace App\Services\Talents;

use App\Models\Talents\Bourse;
use App\Models\Talents\EvaluationPhysique;
use App\Models\Talents\JournalActivite;
use App\Models\Talents\Licence;
use App\Models\Talents\Performance;
use App\Models\Talents\Reconversion;
use App\Models\Talents\SuiviMedical;
use App\Models\Talents\Talent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Calcule les données de chaque widget du tableau de bord.
 * Une méthode par widget (clé de config/talents.php en camelCase) : seuls les widgets affichés sont calculés.
 */
class TableauDeBord
{
    public function __construct(private User $user, private array $filtres = []) {}

    /** Widgets autorisés pour l'utilisateur, dans l'ordre et selon ses préférences. */
    public function widgetsDisponibles(): array
    {
        return collect(config('talents.widgets'))
            ->filter(fn ($w) => $this->user->can($w['permission']))
            ->all();
    }

    public function widgetsAffiches(): array
    {
        $disponibles = $this->widgetsDisponibles();
        $masques = $this->user->talent_preferences['masques'] ?? [];
        $ordre = $this->user->talent_preferences['ordre'] ?? array_keys($disponibles);

        $affiches = [];
        foreach (array_unique([...$ordre, ...array_keys($disponibles)]) as $cle) {
            if (isset($disponibles[$cle]) && ! in_array($cle, $masques)) {
                $affiches[$cle] = $disponibles[$cle];
            }
        }

        return $affiches;
    }

    public function donnees(string $widget): array
    {
        return $this->{Str::camel($widget)}();
    }

    // ---- Requêtes de base (périmètre + filtres globaux) ----

    private function talents(): Builder
    {
        return Talent::visiblePar($this->user)->filtrer($this->filtres);
    }

    private function lies(string $modele): Builder
    {
        return $modele::query()->whereHas('talent', fn ($q) => $q->visiblePar($this->user)->filtrer($this->filtres));
    }

    private function annee(): ?int
    {
        return isset($this->filtres['annee']) ? (int) $this->filtres['annee'] : null;
    }

    // ---- Widgets ----

    private function kpiTalents(): array
    {
        $t = $this->talents();

        return [
            'total' => (clone $t)->count(),
            'actifs' => (clone $t)->where('statut_carriere', 'Actif')->count(),
            'feminin' => (clone $t)->where('genre', 'Féminin')->count(),
            'internationaux' => (clone $t)->whereHas('licences', fn ($q) => $q->where('portee', 'Internationale')->where('statut', 'Active'))->count(),
            'haut_potentiel' => (clone $t)->where('haut_potentiel', true)->count(),
            'retraites' => (clone $t)->whereIn('statut_carriere', ['Retraité', 'Reconverti'])->count(),
            'medailles' => $this->lies(Performance::class)->whereNotNull('distinction')->where('distinction', 'like', 'Médaille%')
                ->when($this->annee(), fn ($q, $a) => $q->whereYear('date_evenement', $a))->count(),
            'en_attente' => (clone $t)->whereIn('statut_profil', ['En attente validation', 'En attente API'])->count(),
        ];
    }

    private function validationAttente(): array
    {
        return ['talents' => $this->talents()->whereIn('statut_profil', ['En attente validation', 'En attente API'])
            ->with('federation', 'discipline')->orderBy('updated_at')->limit(6)->get()];
    }

    private function repartitionDiscipline(): array
    {
        return $this->compter($this->talents()->join('talent_disciplines', 'talent_disciplines.id', '=', 'talents.discipline_id'), 'talent_disciplines.nom');
    }

    private function repartitionGenre(): array
    {
        return $this->compter($this->talents(), 'genre');
    }

    private function statutCarriere(): array
    {
        return $this->compter($this->talents(), 'statut_carriere');
    }

    private function repartitionFederation(): array
    {
        return $this->compter($this->talents()->join('talent_federations', 'talent_federations.id', '=', 'talents.federation_id'), 'talent_federations.sigle');
    }

    private function categoriesAge(): array
    {
        $comptes = array_fill_keys(array_keys(Talent::CATEGORIES_AGE), 0);
        foreach ($this->talents()->where('statut_carriere', 'Actif')->pluck('date_naissance') as $naissance) {
            $comptes[Talent::categoriePourAge($naissance->age)]++;
        }

        return ['labels' => array_keys($comptes), 'valeurs' => array_values($comptes)];
    }

    private function diaspora(): array
    {
        $licences = $this->lies(Licence::class)->where('portee', 'Internationale')->where('statut', 'Active')
            ->whereNotNull('pays_club')->where('pays_club', '!=', 'Burkina Faso')->with('talent.discipline');

        return [
            'parPays' => $this->compter(clone $licences, 'pays_club'),
            'liste' => $licences->latest()->limit(5)->get(),
        ];
    }

    private function licences(): array
    {
        $l = $this->lies(Licence::class);

        return [
            'parType' => $this->compter((clone $l)->where('statut', 'Active'), 'type_licence'),
            'expirant' => (clone $l)->where('statut', 'Active')
                ->whereBetween('date_expiration', [now()->toDateString(), now()->addDays(60)->toDateString()])
                ->with('talent')->orderBy('date_expiration')->limit(5)->get(),
            'nonVerifiees' => (clone $l)->where('verification_api', '!=', 'Vérifiée')->count(),
        ];
    }

    private function medailles(): array
    {
        $rows = $this->lies(Performance::class)
            ->join('talent_competitions', 'talent_competitions.id', '=', 'talent_performances.competition_id')
            ->where('distinction', 'like', 'Médaille%')
            ->when($this->annee(), fn ($q, $a) => $q->whereYear('date_evenement', $a))
            ->selectRaw('talent_competitions.niveau as niveau, distinction, count(*) as total')
            ->groupBy('talent_competitions.niveau', 'distinction')->get();

        $niveaux = $rows->pluck('niveau')->unique()->values()->all();
        $series = [];
        foreach (['Médaille d\'Or' => '#d4a017', 'Médaille d\'Argent' => '#9aa5b1', 'Médaille de Bronze' => '#b0703c'] as $metal => $couleur) {
            $series[] = ['label' => $metal, 'couleur' => $couleur,
                'valeurs' => array_map(fn ($n) => (int) $rows->where('niveau', $n)->where('distinction', $metal)->sum('total'), $niveaux)];
        }

        return ['labels' => $niveaux, 'series' => $series];
    }

    private function dernieresPerformances(): array
    {
        return ['performances' => $this->lies(Performance::class)->with('talent.discipline', 'competition')
            ->when($this->annee(), fn ($q, $a) => $q->whereYear('date_evenement', $a))
            ->latest('date_evenement')->limit(8)->get()];
    }

    private function alertesMedicales(): array
    {
        $ouverts = $this->lies(SuiviMedical::class)->where('cloture', false)->where('statut_medical', '!=', 'Aptitude Totale');

        return [
            'dossiers' => (clone $ouverts)->with('talent')->orderBy('date_retour_prevue')->limit(6)->get(),
            'total' => (clone $ouverts)->count(),
            'jours' => (clone $ouverts)->sum('jours_indisponibilite'),
        ];
    }

    private function evolutionPhysique(): array
    {
        $e = $this->lies(EvaluationPhysique::class)
            ->when($this->annee(), fn ($q, $a) => $q->whereYear('date_evaluation', $a));

        return [
            'nombre' => (clone $e)->count(),
            'vma' => round((float) (clone $e)->avg('vma_kmh'), 1),
            'detente' => round((float) (clone $e)->avg('detente_cm'), 1),
            'sprint' => round((float) (clone $e)->avg('sprint_30m_s'), 2),
            'meilleurs' => (clone $e)->whereNotNull('vma_kmh')->with('talent')->orderByDesc('vma_kmh')->limit(3)->get(),
        ];
    }

    private function bourses(): array
    {
        $b = $this->lies(Bourse::class);

        return [
            'montantActif' => (clone $b)->whereIn('statut', ['Accordée', 'En cours'])->where('devise', 'XOF')->sum('montant'),
            'beneficiaires' => (clone $b)->whereIn('statut', ['Accordée', 'En cours'])->distinct('talent_id')->count('talent_id'),
            'demandes' => (clone $b)->where('statut', 'Demandée')->count(),
            'parType' => $this->compter((clone $b)->whereIn('statut', ['Accordée', 'En cours']), 'type_bourse', 'montant'),
        ];
    }

    private function reconversion(): array
    {
        return [
            'parStatut' => $this->compter($this->lies(Reconversion::class), 'statut_accompagnement'),
            'parDomaine' => $this->compter($this->lies(Reconversion::class), 'domaine'),
        ];
    }

    private function activiteRecente(): array
    {
        return ['activites' => JournalActivite::with('user')
            ->when(! $this->user->aAccesNational(), fn ($q) => $q->whereIn('user_id', User::where('talent_federation_id', $this->user->federationCloisonnee())
                ->orWhere('id', $this->user->federationTalents()?->user_id)->pluck('id')))
            ->latest('created_at')->limit(10)->get()];
    }

    /** Agrégat générique groupé par colonne → ['labels' => [...], 'valeurs' => [...]]. */
    private function compter(Builder $query, string $colonne, ?string $somme = null): array
    {
        $rows = $query->reorder()->selectRaw("$colonne as cle, ".($somme ? "sum($somme)" : 'count(*)').' as total')
            ->whereNotNull($colonne)->groupBy($colonne)->orderByDesc('total')->get();

        return ['labels' => $rows->pluck('cle')->all(), 'valeurs' => $rows->pluck('total')->map(fn ($v) => (float) $v)->all()];
    }
}
