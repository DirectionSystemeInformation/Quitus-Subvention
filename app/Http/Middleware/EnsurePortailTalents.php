<?php

namespace App\Http\Middleware;

use App\Services\Talents\AccesTalents;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accès au portail « Suivi des talents ». Le rôle talents d'un compte Quitus est resynchronisé
 * dès que son rôle ou son statut Quitus change (ex. fédération désactivée par la DSHN).
 */
class EnsurePortailTalents
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $signature = $user->role.'|'.$user->status;

        if ($request->session()->get('talents.synchro') !== $signature) {
            AccesTalents::synchroniser($user);
            $request->session()->put('talents.synchro', $signature);
        }

        abort_unless($user->aAccesTalents(), 403, 'Votre compte n\'a pas accès au suivi des talents.');

        return $next($request);
    }
}
