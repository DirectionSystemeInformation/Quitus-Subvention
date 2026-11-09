<?php

namespace App\Http\Controllers\Talents;

use App\Http\Controllers\Controller;
use App\Models\Talents\Discipline;
use App\Models\Talents\Federation;
use App\Services\Talents\TableauDeBord;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filtres = $request->only('federation_id', 'discipline_id', 'genre', 'annee', 'categorie');
        $tableau = new TableauDeBord($user, $filtres);

        $widgets = [];
        foreach ($tableau->widgetsAffiches() as $cle => $def) {
            $widgets[$cle] = [...$def, 'donnees' => $tableau->donnees($cle)];
        }

        return view('suivi.dashboard.index', [
            'widgets' => $widgets,
            'disponibles' => $tableau->widgetsDisponibles(),
            'masques' => $user->talent_preferences['masques'] ?? [],
            'filtres' => $filtres,
            'federations' => $user->aAccesNational() ? Federation::orderBy('sigle')->get() : collect(),
            'disciplines' => Discipline::visiblePar($user)->orderBy('nom')->get(),
        ]);
    }

    /** Personnalisation : widgets masqués et ordre d'affichage propres à chaque utilisateur. */
    public function preferences(Request $request)
    {
        $user = $request->user();
        $cles = array_keys((new TableauDeBord($user))->widgetsDisponibles());
        $visibles = array_intersect($request->input('visibles', []), $cles);
        $ordre = array_values(array_intersect($request->input('ordre', $cles), $cles));

        $user->update(['talent_preferences' => [
            'masques' => array_values(array_diff($cles, $visibles)),
            'ordre' => $ordre ?: $cles,
        ]]);

        return back()->with('succes', 'Tableau de bord personnalisé.');
    }
}
