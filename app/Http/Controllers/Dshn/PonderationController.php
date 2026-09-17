<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\PonderationCritere;
use App\Models\PonderationPalier;
use App\Models\PonderationRubrique;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PonderationController extends Controller
{
    public function index()
    {
        return view('dshn.ponderation', [
            'rubriques' => PonderationRubrique::with('criteres')->orderBy('sort_order')->get(),
            'paliers' => PonderationPalier::orderBy('seuil_min')->get(),
        ]);
    }

    public function storeRubrique(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
        ]);

        PonderationRubrique::create([
            'label' => $data['label'],
            'sort_order' => PonderationRubrique::max('sort_order') + 1,
        ]);

        return back()->with('status', 'Rubrique ajoutée.');
    }

    public function updateRubrique(Request $request, PonderationRubrique $rubrique)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
        ]);

        $rubrique->update($data);

        return back()->with('status', 'Rubrique mise à jour.');
    }

    public function destroyRubrique(PonderationRubrique $rubrique)
    {
        $rubrique->delete();

        return back()->with('status', 'Rubrique supprimée. Les campagnes déjà pondérées conservent leur grille.');
    }

    public function storeCritere(Request $request, PonderationRubrique $rubrique)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'points_max' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $rubrique->criteres()->create([
            'slug' => $this->genererSlug($data['label']),
            'label' => $data['label'],
            'points_max' => $data['points_max'],
            'sort_order' => ($rubrique->criteres()->max('sort_order') ?? -1) + 1,
        ]);

        return back()->with('status', 'Critère ajouté.');
    }

    public function updateCritere(Request $request, PonderationCritere $critere)
    {
        // Le slug identifie le critère dans les scores déjà saisis : il n'est
        // jamais modifié, seuls le libellé et le barème le sont.
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'points_max' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $critere->update($data);

        return back()->with('status', 'Critère mis à jour.');
    }

    public function destroyCritere(PonderationCritere $critere)
    {
        $critere->delete();

        return back()->with('status', 'Critère supprimé. Les campagnes déjà pondérées conservent leur grille.');
    }

    public function updatePaliers(Request $request)
    {
        $data = $request->validate([
            'paliers' => ['required', 'array'],
            'paliers.*.code' => ['required', 'string', 'max:20'],
            'paliers.*.categorie' => ['required', 'string', 'max:20'],
            'paliers.*.seuil_min' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach ($data['paliers'] as $id => $valeurs) {
            $palier = PonderationPalier::find($id);

            if (! $palier) {
                continue;
            }

            $palier->update([
                'code' => $valeurs['code'],
                'categorie' => $valeurs['categorie'],
                'seuil_min' => $valeurs['seuil_min'],
            ]);
        }

        $this->reordonnerPaliers();

        return back()->with('status', 'Paliers de catégorisation enregistrés.');
    }

    public function storePalier(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:ponderation_paliers,code'],
            'categorie' => ['required', 'string', 'max:20'],
            'seuil_min' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        PonderationPalier::create($data);

        $this->reordonnerPaliers();

        return back()->with('status', 'Palier ajouté.');
    }

    public function destroyPalier(PonderationPalier $palier)
    {
        $palier->delete();

        $this->reordonnerPaliers();

        return back()->with('status', 'Palier supprimé. Les campagnes déjà pondérées conservent leur grille.');
    }

    /**
     * L'ordre d'affichage des paliers suit toujours leur seuil, du plus faible
     * au plus élevé — c'est aussi l'ordre du barème de répartition.
     */
    private function reordonnerPaliers(): void
    {
        PonderationPalier::orderBy('seuil_min')->get()
            ->each(fn (PonderationPalier $palier, int $index) => $palier->update(['sort_order' => $index]));
    }

    /**
     * Le slug sert de clé aux scores enregistrés : il est dérivé du libellé à la
     * création puis figé, et doit rester unique.
     */
    private function genererSlug(string $label): string
    {
        $base = Str::limit(Str::slug($label, '_'), 40, '') ?: 'critere';
        $slug = $base;
        $suffixe = 2;

        while (PonderationCritere::where('slug', $slug)->exists()) {
            $slug = $base.'_'.$suffixe++;
        }

        return $slug;
    }
}
