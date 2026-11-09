<?php

namespace App\Http\Controllers\Talents\Modules;

use App\Http\Controllers\Talents\RessourceController;
use App\Models\Talents\Competition;
use App\Models\Talents\Discipline;
use App\Models\Talents\Referentiel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompetitionController extends RessourceController
{
    protected static string $module = 'competitions';

    protected string $modele = Competition::class;

    protected string $route = 'suivi.competitions';

    protected string $titre = 'Calendrier des compétitions';

    protected string $singulier = 'Compétition';

    protected string $icone = 'bi-calendar-event';

    protected bool $lieAuTalent = false;

    protected array $with = ['discipline'];

    protected string $ordre = 'date_debut';

    protected function champs(?Model $objet = null): array
    {
        return [
            'nom' => ['label' => 'Nom de la compétition', 'requis' => true, 'col' => 8],
            'discipline_id' => ['label' => 'Discipline', 'type' => 'select', 'options' => Discipline::visiblePar(request()->user())->orderBy('nom')->pluck('nom', 'id')->all(), 'col' => 4,
                'regles' => ['nullable', Rule::in(Discipline::visiblePar(request()->user())->pluck('id'))]],
            'niveau' => ['label' => 'Niveau', 'type' => 'select', 'options' => self::options(Referentiel::liste('niveau_competition')), 'requis' => true, 'col' => 4],
            'lieu' => ['label' => 'Lieu / ville', 'col' => 4],
            'pays' => ['label' => 'Pays', 'col' => 4],
            'date_debut' => ['label' => 'Date de début', 'type' => 'date', 'requis' => true, 'col' => 6],
            'date_fin' => ['label' => 'Date de fin', 'type' => 'date', 'col' => 6, 'regles' => ['nullable', 'date', 'after_or_equal:date_debut']],
        ];
    }

    protected function colonnes(): array
    {
        return [
            'Compétition' => fn ($c) => $c->nom,
            'Discipline' => fn ($c) => $c->discipline?->nom ?? 'Multisports',
            'Niveau' => fn ($c) => badge($c->niveau, 'info'),
            'Lieu' => fn ($c) => trim($c->lieu.' - '.$c->pays, ' -'),
            'Dates' => fn ($c) => $c->date_debut->format('d/m/Y').($c->date_fin ? ' → '.$c->date_fin->format('d/m/Y') : ''),
            'Résultats saisis' => fn ($c) => $c->performances_count,
        ];
    }

    protected function filtres(): array
    {
        return [
            'niveau' => ['Niveau', self::options(Referentiel::liste('niveau_competition'))],
            'discipline_id' => ['Discipline', Discipline::visiblePar(request()->user())->orderBy('nom')->pluck('nom', 'id')->all()],
        ];
    }

    protected function requete(Request $request): Builder
    {
        // Le nombre de résultats ne compte que les athlètes visibles par l'utilisateur.
        return parent::requete($request)
            ->withCount(['performances' => fn ($q) => $q->visiblePar($request->user())]);
    }

    protected function trouver(Request $request, string $id): Model
    {
        $competition = parent::trouver($request, $id);
        abort_unless($competition->estModifiablePar($request->user()), 403,
            'Compétition du calendrier national : seul le Ministère peut la modifier.');

        return $competition;
    }

    protected function avantSauvegarde(Request $request, Model $objet): void
    {
        if (! $objet->exists && ! $request->user()->aAccesNational()) {
            $objet->federation_id = $request->user()->federationCloisonnee();
        }
    }
}
