<?php

namespace App\Support;

use App\Models\Campaign;
use App\Models\User;

class FederationDossier
{
    public static function forUser(User $user, mixed $requestedYear = null): array
    {
        $reports = $user->reports()->orderByDesc('updated_at')->get();
        $campaigns = Campaign::with(['allocations' => fn ($query) => $query->where('user_id', $user->id)])
            ->orderByDesc('annee_n1')->get();
        $defaultYear = now()->year + 1;

        // Keep an unfinished dossier visible even when the calendar year changes.
        $relevantCampaign = $campaigns->first(fn ($campaign) => $campaign->statut === 'en_cours' && (
            $campaign->allocations->isNotEmpty() || $reports->contains(fn ($report) => (int) $report->year + ($report->type === 'rapport_activite' ? 1 : 0) === (int) $campaign->annee_n1
            )
        ));
        $defaultCampaign = $relevantCampaign
            ?? $campaigns->firstWhere('annee_n1', $defaultYear)
            ?? $campaigns->firstWhere('statut', 'en_cours');
        $requestedYear = filter_var($requestedYear, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2001, 'max_range' => 2100]]);
        $programmeYear = $requestedYear ?: (int) ($defaultCampaign?->annee_n1 ?? $defaultYear);
        $rapportYear = $programmeYear - 1;
        $campaign = $campaigns->firstWhere('annee_n1', $programmeYear);
        $allocation = $campaign?->allocations->first();
        $rapport = $reports->first(fn ($report) => $report->type === 'rapport_activite' && (int) $report->year === $rapportYear);
        $programme = $reports->first(fn ($report) => $report->type === 'programme_budgetise' && (int) $report->year === $programmeYear);
        $reamenage = $reports->first(fn ($report) => $report->type === 'programme_reamenage' && (int) $report->year === $programmeYear);
        $activities = $user->activities()->where('year', $rapportYear)->orderByDesc('updated_at')->get();
        $activityCounts = $activities->countBy('status');
        $activitiesUrl = route('activities.index', ['annee' => $rapportYear]);
        $programmeUrl = route('programme-budgetise.create', ['annee' => $programmeYear]);
        $reamenageUrl = route('programme-reamenage.create', ['annee' => $programmeYear]);
        $documentsUrl = route('documents.index', ['type' => 'programme_budgetise', 'annee' => $programmeYear]);
        $bothValidated = $rapport?->status === 'valide' && $programme?->status === 'valide';
        $adjustmentOpen = $allocation && $campaign->etape >= 11;

        $state = [
            'title' => 'Votre dossier est à préparer',
            'detail' => "Déclarez vos activités de {$rapportYear} et joignez leurs justificatifs. Après validation par la DGF, elles alimentent automatiquement votre rapport d’activité.",
            'action' => 'Gérer mes activités', 'url' => $activitiesUrl, 'step' => 1, 'tone' => 'action',
        ];

        if ($allocation?->quitus_delivered_at) {
            $state = [
                'title' => 'Votre quitus est disponible',
                'detail' => 'Le quitus a été délivré. Vous pouvez le télécharger et le conserver avec les documents de votre dossier.',
                'action' => 'Télécharger le quitus', 'url' => route('campagnes.quitus.download', [$campaign, $user]),
                'step' => 5, 'tone' => 'complete',
            ];
        } elseif (! $user->isActive()) {
            $state = [
                'title' => $user->status === 'rejected' ? 'Votre compte nécessite une correction' : 'Votre compte attend sa validation',
                'detail' => $user->rejection_reason ?: 'La DSHN doit valider votre compte pour que votre fédération puisse être retenue dans la campagne.',
                'action' => 'Consulter mon profil', 'url' => route('profile.edit'), 'step' => 1, 'tone' => 'waiting',
            ];
        } elseif ($adjustmentOpen) {
            $state = match ($reamenage?->status) {
                'valide' => [
                    'title' => 'Votre programme réaménagé est validé',
                    'detail' => 'La prochaine étape est la délivrance de votre quitus par la DSHN. Aucune nouvelle saisie n’est demandée à ce stade.',
                    'action' => 'Voir le programme validé', 'url' => route('activity-form.show', $reamenage), 'step' => 5, 'tone' => 'waiting',
                ],
                'soumis' => [
                    'title' => 'Votre programme réaménagé est en cours d’examen',
                    'detail' => 'La DSHN examine votre programme réaménagé avant la délivrance du quitus.',
                    'action' => 'Voir le programme soumis', 'url' => route('activity-form.show', $reamenage), 'step' => 4, 'tone' => 'waiting',
                ],
                'rejete' => [
                    'title' => 'Votre programme réaménagé est à corriger',
                    'detail' => $reamenage->rejection_reason ?: 'Consultez le programme, apportez les corrections demandées puis soumettez-le à nouveau.',
                    'action' => 'Corriger le programme réaménagé', 'url' => $reamenageUrl, 'step' => 4, 'tone' => 'attention',
                ],
                default => [
                    'title' => $reamenage ? 'Votre programme réaménagé est en brouillon' : 'Votre programme réaménagé est attendu',
                    'detail' => 'Adaptez vos activités et votre budget au montant final de la subvention. Enregistrez votre travail, puis soumettez votre programme à la DSHN.',
                    'action' => $reamenage ? 'Reprendre le programme réaménagé' : 'Préparer le programme réaménagé',
                    'url' => $reamenageUrl, 'step' => 4, 'tone' => 'action',
                ],
            };
        } elseif ($programme?->status === 'rejete') {
            $state = [
                'title' => 'Votre programme budgétisé est à corriger',
                'detail' => $programme->rejection_reason ?: 'Apportez les corrections demandées, puis soumettez votre programme à nouveau.',
                'action' => 'Corriger le programme', 'url' => $programmeUrl, 'step' => 2, 'tone' => 'attention',
            ];
        } elseif ($rapport?->status === 'rejete') {
            $state = [
                'title' => 'Votre rapport d’activité est à revoir',
                'detail' => $rapport->rejection_reason ?: 'Consultez le motif du rejet et vérifiez vos activités. Le rapport est alimenté par les activités validées par la DGF.',
                'action' => 'Consulter le rapport et son motif', 'url' => route('activity-form.show', $rapport), 'step' => 1, 'tone' => 'attention',
            ];
        } elseif (! $rapport && ($activityCounts->get('rejete', 0) || $activityCounts->get('brouillon', 0))) {
            $state['title'] = $activityCounts->get('rejete', 0) ? 'Des activités sont à corriger' : 'Vos activités sont en brouillon';
            $state['detail'] = 'Complétez les activités et leurs pièces justificatives, puis soumettez-les à la DGF. Seules les activités validées alimentent le rapport.';
            $state['action'] = 'Compléter mes activités';
        } elseif (! $rapport && ! $activityCounts->get('soumis', 0)) {
            // The default action starts the report from actual activities, never an upload.
        } elseif (! $programme || $programme->status === 'brouillon') {
            $state = [
                'title' => $programme ? 'Votre programme budgétisé est en brouillon' : 'Votre programme budgétisé est à préparer',
                'detail' => 'Renseignez vos activités prévisionnelles et leurs montants en FCFA. Vous pouvez enregistrer un brouillon avant de soumettre le programme à la DSHN.',
                'action' => $programme ? 'Reprendre mon brouillon' : 'Préparer mon programme',
                'url' => $programmeUrl, 'step' => 2, 'tone' => 'action',
            ];
        } elseif (! $rapport) {
            $state = [
                'title' => 'Vos activités sont en cours de vérification par la DGF',
                'detail' => 'Le rapport se constitue dès la validation de vos activités par la DGF. Vous pouvez consulter leur statut et leurs pièces justificatives.',
                'action' => 'Suivre mes activités', 'url' => $activitiesUrl, 'step' => 1, 'tone' => 'waiting',
            ];
        } elseif (! $bothValidated) {
            $state = [
                'title' => 'Vos documents sont en cours d’examen par la DSHN',
                'detail' => 'La DSHN vérifie le rapport d’activité et le programme budgétisé avant leur intégration au circuit de répartition.',
                'action' => 'Consulter mes documents', 'url' => $documentsUrl, 'step' => 3, 'tone' => 'waiting',
            ];
        } elseif (! $allocation) {
            $state = [
                'title' => $campaign && $campaign->etape > 3 ? 'Votre participation à la campagne reste à confirmer' : 'Vos documents sont validés',
                'detail' => $campaign && $campaign->etape > 3
                    ? 'Aucune allocation n’est enregistrée pour votre fédération dans cette campagne. La DSHN doit confirmer votre participation à la suite du circuit.'
                    : ($campaign ? 'Votre dossier est prêt pour son intégration au circuit de répartition par la DSHN.' : 'La campagne n’est pas encore ouverte. Votre dossier sera suivi ici dès son ouverture.'),
                'action' => 'Consulter mes documents', 'url' => $documentsUrl, 'step' => 3, 'tone' => 'waiting',
            ];
        } else {
            $state = [
                'title' => 'Votre dossier est dans le circuit de répartition',
                'detail' => 'Étape en cours : '.$campaign->etapeLabel().'. Le programme réaménagé sera demandé après la session d’arbitrage.',
                'action' => 'Consulter mes documents', 'url' => $documentsUrl, 'step' => 3, 'tone' => 'waiting',
            ];
        }

        $reportStep = fn ($report) => match ($report?->status) {
            'valide' => ['status' => 'Validé', 'tone' => 'complete'],
            'rejete' => ['status' => 'À corriger', 'tone' => 'attention'],
            'soumis' => ['status' => 'Examen DSHN', 'tone' => 'waiting'],
            'brouillon' => ['status' => 'Brouillon', 'tone' => 'action'],
            default => ['status' => 'À préparer', 'tone' => 'upcoming'],
        };
        $steps = [
            ['label' => 'Activités et rapport', 'year' => $rapportYear] + ($rapport ? $reportStep($rapport) : [
                'status' => $activityCounts->get('soumis', 0) ? 'Vérification DGF' : 'À préparer', 'tone' => 'upcoming',
            ]),
            ['label' => 'Programme budgétisé', 'year' => $programmeYear] + $reportStep($programme),
            ['label' => 'Instruction et arbitrage', 'year' => null,
                'status' => $allocation ? ($adjustmentOpen || $allocation->quitus_delivered_at ? 'Arbitrage effectué' : $campaign->etapeLabel()) : ($bothValidated ? 'Participation à confirmer' : ($state['step'] === 3 ? 'Examen des documents' : 'À venir')),
                'tone' => $allocation ? ($adjustmentOpen || $allocation->quitus_delivered_at ? 'complete' : 'waiting') : ($state['step'] === 3 ? 'waiting' : 'upcoming')],
            ['label' => 'Programme réaménagé', 'year' => $programmeYear] + ($reamenage ? $reportStep($reamenage) : [
                'status' => $adjustmentOpen ? 'À préparer' : 'À venir', 'tone' => $adjustmentOpen ? 'action' : 'upcoming',
            ]),
            ['label' => 'Quitus', 'year' => $programmeYear,
                'status' => $allocation?->quitus_delivered_at ? 'Disponible' : ($adjustmentOpen && $reamenage?->status === 'valide' ? 'Délivrance attendue' : 'À venir'),
                'tone' => $allocation?->quitus_delivered_at ? 'complete' : 'upcoming'],
        ];

        // updated_at is presented as an update, not as a submission or validation date.
        $events = collect();
        foreach (['Rapport d’activité' => $rapport, 'Programme budgétisé' => $programme, 'Programme réaménagé' => $reamenage] as $label => $report) {
            if ($report?->updated_at) {
                $events->push(['label' => $label.' mis à jour', 'date' => $report->updated_at, 'url' => route('activity-form.show', $report)]);
            }
        }
        if ($activity = $activities->first()) {
            $events->push(['label' => 'Activité mise à jour : '.($activity->designation ?: 'sans intitulé'), 'date' => $activity->updated_at, 'url' => route('activities.show', $activity)]);
        }
        if ($allocation) {
            foreach (['dg_decided_at' => 'Décision du DG enregistrée', 'ministre_decided_at' => 'Décision du Ministre enregistrée', 'session_arbitrage_organized_at' => 'Session d’arbitrage organisée'] as $field => $label) {
                if ($campaign->$field) {
                    $events->push(['label' => $label, 'date' => $campaign->$field, 'url' => null]);
                }
            }
            if ($allocation->quitus_delivered_at) {
                $events->push(['label' => 'Quitus délivré', 'date' => $allocation->quitus_delivered_at, 'url' => route('campagnes.quitus.download', [$campaign, $user])]);
            }
        }
        $lastEvent = $events->filter(fn ($event) => $event['date'])->sortByDesc(fn ($event) => $event['date']->getTimestamp())->first();
        $availableYears = $campaigns->pluck('annee_n1')->merge($reports->map(fn ($report) => (int) $report->year + ($report->type === 'rapport_activite' ? 1 : 0)))
            ->push($defaultYear, $programmeYear)->unique()->sortDesc()->values();

        return compact('reports', 'campaigns', 'campaign', 'allocation', 'programmeYear', 'rapportYear', 'rapport', 'programme', 'reamenage', 'activities', 'activityCounts', 'activitiesUrl', 'programmeUrl', 'reamenageUrl', 'documentsUrl', 'adjustmentOpen', 'state', 'steps', 'lastEvent', 'availableYears');
    }
}
