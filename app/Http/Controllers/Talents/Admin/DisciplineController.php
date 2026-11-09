<?php

namespace App\Http\Controllers\Talents\Admin;

use App\Http\Controllers\Controller;
use App\Models\Talents\Discipline;
use App\Models\Talents\Federation;
use App\Models\Talents\Specialite;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

/**
 * Chaque fédération gère ses propres disciplines et spécialités ; le niveau national peut tout gérer
 * et rattacher une discipline à n'importe quelle fédération.
 */
class DisciplineController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:disciplines.voir', only: ['index']),
            new Middleware('can:disciplines.gerer', except: ['index']),
        ];
    }

    public function index(Request $request)
    {
        $user = $request->user();

        return view('suivi.admin.disciplines', [
            'disciplines' => Discipline::visiblePar($user)->with(['federation', 'specialites'])
                ->withCount(['talents' => fn ($q) => $q->visiblePar($user)])->orderBy('nom')->get(),
            'federations' => $user->aAccesNational() ? Federation::orderBy('sigle')->get() : collect(),
        ]);
    }

    public function store(Request $request)
    {
        Discipline::create($this->valider($request));

        return back()->with('succes', 'Discipline ajoutée.');
    }

    public function update(Request $request, Discipline $discipline)
    {
        $this->autoriser($request, $discipline);
        $discipline->update($this->valider($request, $discipline));

        return back()->with('succes', 'Discipline mise à jour.');
    }

    public function destroy(Request $request, Discipline $discipline)
    {
        $this->autoriser($request, $discipline);
        if ($discipline->talents()->withTrashed()->exists()) {
            return back()->with('erreur', 'Impossible : des talents sont rattachés à cette discipline. Désactivez-la plutôt.');
        }
        $discipline->delete();

        return back()->with('succes', 'Discipline supprimée.');
    }

    public function ajouterSpecialite(Request $request, Discipline $discipline)
    {
        $this->autoriser($request, $discipline);
        $data = $request->validate(
            ['nom' => ['required', 'max:100', Rule::unique('talent_specialites')->where('discipline_id', $discipline->id)]],
            ['nom.unique' => 'Cette spécialité existe déjà pour cette discipline.']
        );
        $discipline->specialites()->create($data);

        return back()->with('succes', 'Spécialité ajoutée.');
    }

    public function supprimerSpecialite(Request $request, Specialite $specialite)
    {
        $this->autoriser($request, $specialite->discipline);
        $specialite->delete();

        return back()->with('succes', 'Spécialité supprimée.');
    }

    /** 404 sur la discipline d'une autre fédération : on ne confirme pas son existence. */
    private function autoriser(Request $request, Discipline $discipline): void
    {
        abort_unless(Discipline::visiblePar($request->user())->whereKey($discipline->id)->exists(), 404);
    }

    private function valider(Request $request, ?Discipline $discipline = null): array
    {
        $user = $request->user();
        $donnees = $request->validate([
            'nom' => ['required', 'max:100', Rule::unique('talent_disciplines')->ignore($discipline?->id)],
            'federation_id' => ['nullable', 'exists:talent_federations,id'],
        ], [
            // Neutre : ne dit pas à quelle fédération la discipline est rattachée.
            'nom.unique' => 'Cette discipline est déjà enregistrée sur la plateforme. Contactez le Ministère pour qu\'elle soit rattachée à votre fédération.',
        ]);

        // Une fédération crée et garde ses disciplines chez elle, quoi que contienne le formulaire.
        if (! $user->aAccesNational()) {
            $donnees['federation_id'] = $user->federationCloisonnee();
        }

        return [...$donnees, 'collectif' => $request->boolean('collectif'), 'actif' => $request->boolean('actif')];
    }
}
