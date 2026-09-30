<?php

namespace App\Support;

use App\Models\Campaign;
use App\Models\User;

final class CampaignPresentation
{
    /** Presentation only: write permissions remain in the campaign controller. */
    public static function forUser(Campaign $campaign, User $user): array
    {
        [$role, $owner, $instruction] = match ((int) $campaign->etape) {
            3 => ['dshn', 'DSHN', 'Vérifiez les rapports et programmes validés, puis lancez la pondération.'],
            4 => ['dshn', 'DSHN', 'Saisissez et enregistrez les scores de chaque fédération avant de confirmer la pondération.'],
            5 => ['dshn', 'DSHN', 'Consultez les catégories calculées, puis confirmez le passage à la répartition.'],
            6 => ['dshn', 'DSHN', 'Enregistrez les montants proposés, puis transmettez la répartition au Directeur Général.'],
            7 => ['dg', 'Direction générale', 'Examinez les montants proposés avant de pré-valider ou de motiver un rejet.'],
            8 => ['comite_arbitrage', 'Comité d’arbitrage', 'Enregistrez les montants arbitrés, puis transmettez la répartition au Ministre.'],
            9 => ['ministre', 'Ministre', 'Examinez la répartition arbitrée avant de la valider ou de motiver un rejet.'],
            10 => ['comite_arbitrage', 'Comité d’arbitrage', 'Renseignez la date de la session une fois celle-ci organisée.'],
            11, 12 => ['dshn', 'DSHN et fédérations', 'Examinez les programmes réaménagés reçus et délivrez les quitus des dossiers validés.'],
            default => [null, 'À préciser', 'Consultez le récapitulatif de la campagne.'],
        };
        $complete = $campaign->statut === 'termine';
        $canAct = ! $complete && ($user->role === $role || ($role === 'dshn' && $user->isDshn()));

        return [
            'complete' => $complete,
            'canAct' => $canAct,
            'owner' => $owner,
            'label' => $complete ? 'Campagne terminée' : ($canAct ? 'À votre tour' : 'Suivi de la campagne'),
            'instruction' => $complete ? 'Les quitus délivrés et les montants retenus restent consultables.' : ($canAct ? $instruction : 'Cette étape est suivie par : '.$owner.'. Vous pouvez consulter le récapitulatif ci-dessous.'),
        ];
    }

    public static function phases(): array
    {
        return [
            ['label' => 'Préparation', 'from' => 3, 'to' => 6],
            ['label' => 'Pré-validation DG', 'from' => 7, 'to' => 7],
            ['label' => 'Arbitrage et validation', 'from' => 8, 'to' => 9],
            ['label' => 'Réaménagement', 'from' => 10, 'to' => 11],
            ['label' => 'Délivrance du quitus', 'from' => 12, 'to' => 12],
        ];
    }
}
