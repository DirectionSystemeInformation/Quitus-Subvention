<?php

namespace App\Http\Controllers\Talents\Modules;

use App\Http\Controllers\Talents\RessourceController;
use App\Models\Talents\EvaluationPhysique;
use Illuminate\Database\Eloquent\Model;

class EvaluationPhysiqueController extends RessourceController
{
    protected static string $module = 'evaluations';

    protected string $modele = EvaluationPhysique::class;

    protected string $route = 'suivi.evaluations';

    protected string $titre = 'Évaluations physiques & biométriques';

    protected string $singulier = 'Évaluation';

    protected string $icone = 'bi-speedometer2';

    protected string $ordre = 'date_evaluation';

    protected function champs(?Model $objet = null): array
    {
        return [
            'talent_id' => ['label' => 'Talent', 'type' => 'talent', 'requis' => true, 'col' => 6],
            'date_evaluation' => ['label' => 'Date du test', 'type' => 'date', 'requis' => true, 'col' => 3],
            'evaluateur' => ['label' => 'Évaluateur', 'col' => 3],
            'taille_cm' => ['label' => 'Taille (cm)', 'type' => 'number', 'step' => '0.1', 'col' => 2, 'regles' => ['nullable', 'numeric', 'between:50,250']],
            'poids_kg' => ['label' => 'Poids (kg)', 'type' => 'number', 'step' => '0.1', 'col' => 2, 'regles' => ['nullable', 'numeric', 'between:20,250']],
            'vma_kmh' => ['label' => 'VMA (km/h)', 'type' => 'number', 'step' => '0.1', 'col' => 2, 'regles' => ['nullable', 'numeric', 'between:5,30']],
            'detente_cm' => ['label' => 'Détente verticale (cm)', 'type' => 'number', 'step' => '0.1', 'col' => 3, 'regles' => ['nullable', 'numeric', 'between:0,150']],
            'sprint_30m_s' => ['label' => 'Sprint 30 m (s)', 'type' => 'number', 'step' => '0.01', 'col' => 3, 'regles' => ['nullable', 'numeric', 'between:2,10']],
            'avis' => ['label' => 'Avis du préparateur physique', 'type' => 'textarea', 'col' => 12],
        ];
    }

    protected function colonnes(): array
    {
        return [
            'Date' => fn ($e) => $e->date_evaluation->format('d/m/Y'),
            'Taille' => fn ($e) => $e->taille_cm ? "{$e->taille_cm} cm" : '',
            'Poids' => fn ($e) => $e->poids_kg ? "{$e->poids_kg} kg" : '',
            'IMC' => fn ($e) => $e->imc,
            'VMA' => fn ($e) => $e->vma_kmh,
            'Détente' => fn ($e) => $e->detente_cm,
            'Sprint 30m' => fn ($e) => $e->sprint_30m_s,
            'Avis' => fn ($e) => $e->avis,
        ];
    }
}
