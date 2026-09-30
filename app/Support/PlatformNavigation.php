<?php

namespace App\Support;

use App\Models\User;

final class PlatformNavigation
{
    public static function roleLabel(User $user): string
    {
        return match ($user->role) {
            'federation' => 'Espace fédération',
            'admin' => 'Administration',
            'dshn' => 'Instruction DSHN',
            'dgf' => 'Contrôle DGF',
            'dg' => 'Direction générale',
            'comite_arbitrage' => 'Comité d’arbitrage',
            'ministre' => 'Cabinet du ministre',
            default => 'Mon espace',
        };
    }

    /** Each item is [active key, route name, label, icon, optional badge key]. */
    public static function groups(User $user): array
    {
        if ($user->isFederation()) {
            return ['Mon suivi' => [
                ['dashboard', 'dashboard', 'Mon dossier', 'grid'],
                ['documents', 'documents.index', 'Rapports & programmes', 'file'],
                ['activities', 'activities.index', 'Activités réalisées', 'check'],
            ]];
        }

        if ($user->isDshn()) {
            $prefix = $user->isAdmin() ? 'admin.' : 'dshn.';
            $groups = ['Traitement' => [
                ['dshn-dashboard', $prefix.'dashboard', 'Vue d’ensemble', 'grid'],
                ['reports', $prefix.'reports.index', 'Rapports & programmes', 'file', 'reports'],
                ['federations', $prefix.'federations.index', 'Fédérations', 'users', 'federations'],
            ]];
            if ($user->isAdmin()) {
                $groups['Traitement'][] = ['dgf-activities', 'dgf.activities.index', 'Contrôle des activités', 'check'];
            }
            $groups['Pilotage'] = [['campagnes', 'campagnes.index', 'Campagnes de subvention', 'calendar']];
            $groups['Référentiels'] = [
                ['canevas', $prefix.'canevas.index', 'Canevas des activités', 'list'],
                ['ponderation', $prefix.'ponderation.index', 'Grille de pondération', 'scales'],
            ];
            if ($user->isAdmin()) {
                $groups['Administration'] = [
                    ['users', 'admin.users.index', 'Comptes agents', 'users'],
                    ['activity-log', 'admin.activity-log.index', 'Journal des actions', 'clock'],
                ];
            }

            return $groups;
        }

        if ($user->isDgf()) {
            return ['Contrôle des justificatifs' => [
                ['dgf-activities', 'dgf.activities.index', 'Activités à vérifier', 'check'],
            ]];
        }

        if ($user->isCampaignActor()) {
            return ['Répartition des subventions' => [
                ['campagnes', 'campagnes.index', 'Campagnes', 'calendar'],
            ]];
        }

        return [];
    }
}
