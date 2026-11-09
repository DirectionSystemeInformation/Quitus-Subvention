<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Choix du portail après connexion : Quitus (subventions) ou Suivi des talents.
 * Un compte qui n'a accès qu'à un seul portail y est envoyé directement.
 */
class PortailController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if (! ($user->aAccesQuitus() && $user->aAccesTalents())) {
            return redirect()->route(portail_accueil($user));
        }

        return view('portails', ['user' => $user]);
    }
}
