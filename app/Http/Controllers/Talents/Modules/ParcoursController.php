<?php

namespace App\Http\Controllers\Talents\Modules;

use App\Http\Controllers\Talents\RessourceController;
use App\Models\Talents\Entraineur;
use App\Models\Talents\Parcours;
use App\Models\Talents\Referentiel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ParcoursController extends RessourceController
{
    protected static string $module = 'parcours';

    protected string $modele = Parcours::class;

    protected string $route = 'suivi.parcours';

    protected string $titre = 'Parcours, clubs & encadrement';

    protected string $singulier = 'Étape de parcours';

    protected string $icone = 'bi-signpost-split';

    protected array $with = ['entraineur'];

    protected string $ordre = 'saison';

    protected function champs(?Model $objet = null): array
    {
        return [
            'talent_id' => ['label' => 'Talent', 'type' => 'talent', 'requis' => true, 'col' => 6],
            'saison' => ['label' => 'Saison', 'requis' => true, 'col' => 3, 'aide' => 'Ex : 2025-2026'],
            'statut_contractuel' => ['label' => 'Statut contractuel', 'type' => 'select', 'options' => self::options(Referentiel::liste('statut_contractuel')), 'col' => 3],
            'club' => ['label' => 'Club / structure', 'requis' => true, 'col' => 6],
            'pays' => ['label' => 'Pays', 'requis' => true, 'col' => 6],
            'date_debut' => ['label' => 'Début', 'type' => 'date', 'col' => 3],
            'date_fin' => ['label' => 'Fin', 'type' => 'date', 'col' => 3],
            'entraineur_id' => ['label' => 'Entraîneur principal', 'type' => 'select', 'col' => 6,
                'regles' => ['nullable', Rule::in(Entraineur::visiblePar(request()->user())->pluck('id'))],
                'options' => Entraineur::visiblePar(request()->user())->orderBy('nom')->get()->pluck('nom_complet', 'id')->all()],
            'niveau_scolaire' => ['label' => 'Niveau scolaire / professionnel', 'col' => 12],
            'commentaires' => ['label' => 'Commentaires', 'type' => 'textarea', 'col' => 12],
        ];
    }

    protected function colonnes(): array
    {
        return [
            'Saison' => fn ($p) => $p->saison,
            'Club / structure' => fn ($p) => $p->club,
            'Pays' => fn ($p) => $p->pays,
            'Contrat' => fn ($p) => $p->statut_contractuel,
            'Entraîneur' => fn ($p) => $p->entraineur?->nom_complet,
            'Scolarité' => fn ($p) => $p->niveau_scolaire,
            'Commentaires' => fn ($p) => $p->commentaires,
        ];
    }

    protected function filtres(): array
    {
        return ['statut_contractuel' => ['Contrat', self::options(Referentiel::liste('statut_contractuel'))]];
    }
}
