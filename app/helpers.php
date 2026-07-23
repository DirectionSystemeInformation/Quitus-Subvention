<?php

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
