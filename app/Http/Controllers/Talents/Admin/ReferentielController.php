<?php

namespace App\Http\Controllers\Talents\Admin;

use App\Http\Controllers\Controller;
use App\Models\Talents\Referentiel;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

/**
 * Gestion des listes déroulantes (équivalent de l'onglet "Dictionnaire & Listes").
 */
class ReferentielController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:referentiels.gerer')];
    }

    public function index(Request $request)
    {
        $type = $request->query('type', array_key_first(Referentiel::TYPES));
        abort_unless(isset(Referentiel::TYPES[$type]), 404);

        return view('suivi.admin.referentiels', [
            'types' => Referentiel::TYPES,
            'type' => $type,
            'valeurs' => Referentiel::where('type', $type)->orderBy('ordre')->orderBy('valeur')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Referentiel::TYPES))],
            'valeur' => ['required', 'max:150', Rule::unique('talent_referentiels')->where('type', $request->input('type'))],
        ]);
        Referentiel::create([...$data, 'ordre' => Referentiel::where('type', $data['type'])->max('ordre') + 1]);

        return back()->with('succes', 'Valeur ajoutée.');
    }

    public function update(Request $request, Referentiel $referentiel)
    {
        $data = $request->validate([
            'valeur' => ['required', 'max:150', Rule::unique('talent_referentiels')->where('type', $referentiel->type)->ignore($referentiel->id)],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ]);
        $referentiel->update([...$data, 'ordre' => $data['ordre'] ?? $referentiel->ordre, 'actif' => $request->boolean('actif')]);

        return back()->with('succes', 'Valeur mise à jour.');
    }

    public function destroy(Referentiel $referentiel)
    {
        $referentiel->delete();

        return back()->with('succes', 'Valeur supprimée (les fiches existantes conservent leur libellé).');
    }
}
