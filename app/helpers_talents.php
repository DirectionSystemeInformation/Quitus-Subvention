<?php

use Illuminate\Support\HtmlString;

if (! function_exists('badge')) {
    /** Pastille colorée ; la couleur est déduite du libellé si elle n'est pas fournie. */
    function badge(?string $texte, ?string $couleur = null): HtmlString
    {
        if ($texte === null || $texte === '') {
            return new HtmlString('<span class="text-muted">—</span>');
        }
        $couleur ??= couleur_statut($texte);

        return new HtmlString('<span class="badge rounded-pill text-bg-'.$couleur.'">'.e($texte).'</span>');
    }
}

if (! function_exists('couleur_statut')) {
    function couleur_statut(string $statut): string
    {
        return match (true) {
            in_array($statut, ['Validé', 'Actif', 'Active', 'Vérifiée', 'Aptitude Totale', 'Accordée', 'En cours', 'Reconverti', 'Autonome']) => 'success',
            in_array($statut, ['En attente API', 'En attente validation', 'En attente', 'Demandée', 'Dossier Soumis', 'En Soins', 'Aptitude avec réserve', 'En pause', 'Non vérifiée', 'En cours de formation']) => 'warning',
            in_array($statut, ['Rejeté', 'Refusée', 'Expirée', 'Suspendue', 'Échec', 'Inapte temporaire', 'Inapte définitif']) => 'danger',
            in_array($statut, ['Retraité', 'Terminée', 'Brouillon']) => 'secondary',
            str_contains($statut, 'Or') => 'warning',
            default => 'info',
        };
    }
}

if (! function_exists('montant')) {
    function montant($valeur, string $devise = 'FCFA'): string
    {
        return $valeur === null ? '—' : number_format((float) $valeur, 0, ',', ' ').' '.$devise;
    }
}
