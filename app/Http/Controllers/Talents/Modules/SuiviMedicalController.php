<?php

namespace App\Http\Controllers\Talents\Modules;

use App\Http\Controllers\Talents\RessourceController;
use App\Models\Talents\Referentiel;
use App\Models\Talents\SuiviMedical;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SuiviMedicalController extends RessourceController
{
    protected static string $module = 'medical';

    protected string $modele = SuiviMedical::class;

    protected string $route = 'suivi.medical';

    protected string $titre = 'Suivi médical & blessures';

    protected string $singulier = 'Dossier médical';

    protected string $icone = 'bi-heart-pulse';

    protected string $ordre = 'date_consultation';

    protected function champs(?Model $objet = null): array
    {
        return [
            'talent_id' => ['label' => 'Talent', 'type' => 'talent', 'requis' => true, 'col' => 6],
            'date_consultation' => ['label' => 'Date de consultation', 'type' => 'date', 'requis' => true, 'col' => 3],
            'medecin' => ['label' => 'Médecin', 'col' => 3],
            'statut_medical' => ['label' => 'Statut médical', 'type' => 'select', 'options' => self::options(Referentiel::liste('statut_medical')), 'requis' => true, 'col' => 4],
            'type_blessure' => ['label' => 'Blessure (en cours / passée)', 'col' => 4],
            'localisation' => ['label' => 'Localisation', 'col' => 4],
            'date_blessure' => ['label' => 'Date de la blessure', 'type' => 'date', 'col' => 4],
            'date_retour_prevue' => ['label' => 'Retour prévu', 'type' => 'date', 'col' => 4],
            'jours_indisponibilite' => ['label' => 'Jours d\'indisponibilité', 'type' => 'number', 'col' => 4, 'regles' => ['nullable', 'integer', 'min:0']],
            'cloture' => ['label' => 'Dossier clôturé (athlète rétabli)', 'type' => 'checkbox', 'col' => 12],
            'avis_medical' => ['label' => 'Avis médecin / préparateur', 'type' => 'textarea', 'col' => 12],
        ];
    }

    protected function colonnes(): array
    {
        return [
            'Date' => fn ($m) => $m->date_consultation->format('d/m/Y'),
            'Statut' => fn ($m) => badge($m->statut_medical),
            'Blessure' => fn ($m) => $m->type_blessure ?: 'RAS',
            'Indispo.' => fn ($m) => $m->jours_indisponibilite.' j',
            'Retour prévu' => fn ($m) => $m->date_retour_prevue?->format('d/m/Y'),
            'Dossier' => fn ($m) => $m->cloture ? badge('Clôturé', 'secondary') : badge('Ouvert', 'warning'),
            'Avis' => fn ($m) => $m->avis_medical,
        ];
    }

    protected function filtres(): array
    {
        return ['statut_medical' => ['Statut', self::options(Referentiel::liste('statut_medical'))]];
    }

    protected function filtrerRequete(Builder $query, Request $request): void
    {
        $query->when($request->query('dossier') === 'ouverts', fn ($q) => $q->where('cloture', false));
    }

    protected function avantSauvegarde(Request $request, Model $objet): void
    {
        $objet->jours_indisponibilite ??= 0;
    }
}
