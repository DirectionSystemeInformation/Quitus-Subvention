<?php

namespace App\Http\Controllers\Talents\Modules;

use App\Http\Controllers\Talents\RessourceController;
use App\Models\Talents\Reconversion;
use App\Models\Talents\Referentiel;
use App\Models\Talents\Talent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReconversionController extends RessourceController
{
    protected static string $module = 'reconversions';

    protected string $modele = Reconversion::class;

    protected string $route = 'suivi.reconversions';

    protected string $titre = 'Retraite sportive & reconversion';

    protected string $singulier = 'Dossier de reconversion';

    protected string $icone = 'bi-mortarboard';

    protected string $ordre = 'annee_fin_carriere';

    protected function champs(?Model $objet = null): array
    {
        return [
            'talent_id' => ['label' => 'Ancien athlète', 'type' => 'talent', 'requis' => true, 'col' => 6,
                'regles' => ['required', Rule::unique('talent_reconversions')->ignore($objet?->id)]],
            'annee_fin_carriere' => ['label' => 'Année de fin de carrière', 'type' => 'number', 'requis' => true, 'col' => 3,
                'regles' => ['required', 'integer', 'between:1960,'.(now()->year + 1)]],
            'motif_retraite' => ['label' => 'Motif de retraite', 'type' => 'select', 'options' => self::options(Referentiel::liste('motif_retraite')), 'requis' => true, 'col' => 3],
            'niveau_etudes' => ['label' => 'Niveau d\'études / diplômes', 'col' => 6],
            'projet' => ['label' => 'Projet de reconversion', 'col' => 6],
            'domaine' => ['label' => 'Domaine', 'type' => 'select', 'options' => self::options(Referentiel::liste('domaine_reconversion')), 'col' => 4],
            'statut_accompagnement' => ['label' => 'Statut de l\'accompagnement', 'type' => 'select', 'options' => self::options(Referentiel::liste('statut_accompagnement')), 'requis' => true, 'col' => 4],
            'role_actuel' => ['label' => 'Rôle actuel dans le sport', 'col' => 4],
            'programme_appui' => ['label' => 'Programme / appui de l\'État', 'col' => 12],
            'besoins' => ['label' => 'Observations / besoins', 'type' => 'textarea', 'col' => 12],
        ];
    }

    protected function colonnes(): array
    {
        return [
            'Fin de carrière' => fn ($r) => $r->annee_fin_carriere,
            'Motif' => fn ($r) => $r->motif_retraite,
            'Diplômes' => fn ($r) => $r->niveau_etudes,
            'Projet' => fn ($r) => $r->projet,
            'Domaine' => fn ($r) => $r->domaine,
            'Accompagnement' => fn ($r) => badge($r->statut_accompagnement),
            'Rôle actuel' => fn ($r) => $r->role_actuel,
            'Appui de l\'État' => fn ($r) => $r->programme_appui,
        ];
    }

    protected function filtres(): array
    {
        return [
            'statut_accompagnement' => ['Accompagnement', self::options(Referentiel::liste('statut_accompagnement'))],
            'domaine' => ['Domaine', self::options(Referentiel::liste('domaine_reconversion'))],
            'motif_retraite' => ['Motif', self::options(Referentiel::liste('motif_retraite'))],
        ];
    }

    /** Ouvrir un dossier de reconversion fait sortir l'athlète du statut "Actif". */
    protected function avantSauvegarde(Request $request, Model $objet): void
    {
        $talent = Talent::find($objet->talent_id);
        if ($talent && in_array($talent->statut_carriere, ['Actif', 'En pause', 'Retraité'])) {
            $talent->update(['statut_carriere' => $objet->statut_accompagnement === 'Reconverti' ? 'Reconverti' : 'Retraité']);
        }
    }
}
