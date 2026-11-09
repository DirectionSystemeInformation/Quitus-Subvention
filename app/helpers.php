<?php

if (! function_exists('home_route_name')) {
    /**
     * The dashboard route name for the given user's role. Used both for the
     * root "/" redirect and to send an already-authenticated user somewhere
     * sane if they land on a guest-only route (e.g. /login) instead of the
     * framework's generic "dashboard" route, which only federations can see.
     */
    function home_route_name(\App\Models\User $user): string
    {
        return match (true) {
            $user->isAdmin() => 'admin.dashboard',
            $user->isDshn() => 'dshn.dashboard',
            $user->isDg(), $user->isComiteArbitrage(), $user->isMinistre() => 'campagnes.index',
            $user->isDgf() => 'dgf.activities.index',
            $user->isComptesTalents() => 'suivi.dashboard',
            default => 'dashboard',
        };
    }
}

if (! function_exists('portail_accueil')) {
    /**
     * Where to land after login: the portal chooser when the account can use both
     * Quitus and the talent-tracking portal, otherwise straight into the only one it can use.
     */
    function portail_accueil(\App\Models\User $user): string
    {
        return match (true) {
            $user->aAccesQuitus() && $user->aAccesTalents() => 'portails',
            $user->aAccesTalents() => 'suivi.dashboard',
            default => home_route_name($user),
        };
    }
}

if (! function_exists('role_route')) {
    /**
     * Resolve a back-office route name for the current user's role: 'admin.*' for
     * admins, 'dshn.*' otherwise. Lets shared DSHN/admin views build the correct
     * URL without knowing which role is currently logged in.
     */
    function role_route(string $suffix, mixed $parameters = [], bool $absolute = true): string
    {
        $prefix = auth()->user()?->isAdmin() ? 'admin' : 'dshn';

        return route("{$prefix}.{$suffix}", $parameters, $absolute);
    }
}
