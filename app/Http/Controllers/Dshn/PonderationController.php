<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\PonderationCritere;
use App\Models\PonderationPalier;
use App\Models\PonderationRubrique;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Grille de pondération en vigueur : critères de notation et paliers de
 * catégorisation. Chacun se modifie en lot (comme dans le tableur d'origine),
 * puis s'enregistre en une fois.
 */
class PonderationController extends Controller
{
    public function index(Request $request)
    {
        $rubriques = PonderationRubrique::with('criteres')->orderBy('sort_order')->get();
        $paliers = PonderationPalier::orderBy('seuil_min')->get();

        // Un échec de validation rouvre le mode édition concerné avec la saisie.
        $edition = match (true) {
            old('_form') === 'grille', $request->query('edition') === 'grille' => 'grille',
            old('_form') === 'paliers', $request->query('edition') === 'paliers' => 'paliers',
            default => null,
        };

        $derniereModification = collect([
            PonderationRubrique::max('updated_at'),
            PonderationCritere::max('updated_at'),
            PonderationPalier::max('updated_at'),
        ])->filter()->max();

        return view('dshn.ponderation', [
            'rubriques' => $rubriques,
            'paliers' => $paliers,
            'edition' => $edition,
            'campagnesFigees' => Campaign::whereNotNull('grille_ponderation')->orderBy('annee_n1')->pluck('annee_n1'),
            'derniereModification' => $derniereModification ? Carbon::parse($derniereModification) : null,
        ]);
    }

    public function updateGrille(Request $request)
    {
        $data = $request->validate([
            'rubriques' => ['nullable', 'array'],
            'rubriques.*.label' => ['required', 'string', 'max:255'],
            'criteres' => ['nullable', 'array'],
            'criteres.*.label' => ['required', 'string', 'max:255'],
            'criteres.*.points_max' => ['required', 'numeric', 'min:0', 'max:100'],
            'supprimer_rubriques' => ['nullable', 'array'],
            'supprimer_rubriques.*' => ['integer'],
            'supprimer_criteres' => ['nullable', 'array'],
            'supprimer_criteres.*' => ['integer'],
            'nouveaux_criteres' => ['nullable', 'array'],
            'nouveaux_criteres.*' => ['array'],
            'nouveaux_criteres.*.*.label' => ['required', 'string', 'max:255'],
            'nouveaux_criteres.*.*.points_max' => ['required', 'numeric', 'min:0', 'max:100'],
            'nouvelles_rubriques' => ['nullable', 'array'],
            'nouvelles_rubriques.*.label' => ['required', 'string', 'max:255'],
            'nouvelles_rubriques.*.criteres' => ['nullable', 'array'],
            'nouvelles_rubriques.*.criteres.*.label' => ['required', 'string', 'max:255'],
            'nouvelles_rubriques.*.criteres.*.points_max' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [], [
            'rubriques.*.label' => 'libellé de la rubrique',
            'nouvelles_rubriques.*.label' => 'libellé de la rubrique',
            'criteres.*.label' => 'libellé du critère',
            'nouveaux_criteres.*.*.label' => 'libellé du critère',
            'nouvelles_rubriques.*.criteres.*.label' => 'libellé du critère',
            'criteres.*.points_max' => 'barème du critère',
            'nouveaux_criteres.*.*.points_max' => 'barème du critère',
            'nouvelles_rubriques.*.criteres.*.points_max' => 'barème du critère',
        ]);

        $bilan = ['modifies' => 0, 'ajoutes' => 0, 'supprimes' => 0];

        DB::transaction(function () use ($data, &$bilan) {
            $rubriquesSupprimees = collect($data['supprimer_rubriques'] ?? [])->map(fn ($id) => (int) $id);
            $criteresSupprimes = collect($data['supprimer_criteres'] ?? [])->map(fn ($id) => (int) $id);

            // Les critères d'une rubrique supprimée partent avec elle (clé étrangère en cascade).
            $bilan['supprimes'] += PonderationCritere::whereIn('id', $criteresSupprimes)
                ->whereNotIn('ponderation_rubrique_id', $rubriquesSupprimees)->delete();
            $bilan['supprimes'] += PonderationRubrique::whereIn('id', $rubriquesSupprimees)->delete();

            $rubriques = PonderationRubrique::whereIn('id', array_keys($data['rubriques'] ?? []))->get()->keyBy('id');
            foreach ($data['rubriques'] ?? [] as $id => $valeurs) {
                $rubrique = $rubriques->get((int) $id);
                if ($rubrique && $rubrique->fill(['label' => $valeurs['label']])->isDirty()) {
                    $rubrique->save();
                    $bilan['modifies']++;
                }
            }

            $criteres = PonderationCritere::whereIn('id', array_keys($data['criteres'] ?? []))->get()->keyBy('id');
            foreach ($data['criteres'] ?? [] as $id => $valeurs) {
                $critere = $criteres->get((int) $id);
                // Le slug identifie le critère dans les scores déjà saisis : seuls
                // le libellé et le barème changent.
                if ($critere && $critere->fill(['label' => $valeurs['label'], 'points_max' => $valeurs['points_max']])->isDirty()) {
                    $critere->save();
                    $bilan['modifies']++;
                }
            }

            foreach ($data['nouveaux_criteres'] ?? [] as $rubriqueId => $nouveaux) {
                $rubrique = PonderationRubrique::find($rubriqueId);
                if (! $rubrique) {
                    continue;
                }
                foreach ($nouveaux as $critere) {
                    $this->creerCritere($rubrique, $critere);
                    $bilan['ajoutes']++;
                }
            }

            foreach ($data['nouvelles_rubriques'] ?? [] as $nouvelle) {
                $rubrique = PonderationRubrique::create([
                    'label' => $nouvelle['label'],
                    'sort_order' => (PonderationRubrique::max('sort_order') ?? -1) + 1,
                ]);
                $bilan['ajoutes']++;
                foreach ($nouvelle['criteres'] ?? [] as $critere) {
                    $this->creerCritere($rubrique, $critere);
                    $bilan['ajoutes']++;
                }
            }
        });

        return redirect()->to(role_route('ponderation.index'))->withFragment('criteres')
            ->with('status', $this->message('Grille de notation enregistrée', $bilan));
    }

    public function updatePaliers(Request $request)
    {
        $data = $request->validate([
            'paliers' => ['nullable', 'array'],
            'paliers.*.code' => ['required', 'string', 'max:20'],
            'paliers.*.categorie' => ['required', 'string', 'max:20'],
            'paliers.*.seuil_min' => ['required', 'numeric', 'min:0', 'max:100'],
            'supprimer_paliers' => ['nullable', 'array'],
            'supprimer_paliers.*' => ['integer'],
            'nouveaux_paliers' => ['nullable', 'array'],
            'nouveaux_paliers.*.code' => ['required', 'string', 'max:20'],
            'nouveaux_paliers.*.categorie' => ['required', 'string', 'max:20'],
            'nouveaux_paliers.*.seuil_min' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [], [
            'paliers.*.code' => 'code du palier',
            'nouveaux_paliers.*.code' => 'code du palier',
            'paliers.*.categorie' => 'catégorie',
            'nouveaux_paliers.*.categorie' => 'catégorie',
            'paliers.*.seuil_min' => 'seuil',
            'nouveaux_paliers.*.seuil_min' => 'seuil',
        ]);

        $supprimes = collect($data['supprimer_paliers'] ?? [])->map(fn ($id) => (int) $id);

        // Paliers tels qu'ils seront après enregistrement, pour contrôler la cohérence.
        $existants = PonderationPalier::whereNotIn('id', $supprimes)->get()->keyBy('id');
        $final = $existants->map(fn (PonderationPalier $palier) => [
            'code' => $data['paliers'][$palier->id]['code'] ?? $palier->code,
            'seuil_min' => (float) ($data['paliers'][$palier->id]['seuil_min'] ?? $palier->seuil_min),
        ])->values()->concat(collect($data['nouveaux_paliers'] ?? [])->map(fn ($palier) => [
            'code' => $palier['code'],
            'seuil_min' => (float) $palier['seuil_min'],
        ]));
        $this->verifierPaliers($final);

        $bilan = ['modifies' => 0, 'ajoutes' => 0, 'supprimes' => $supprimes->count()];

        DB::transaction(function () use ($data, $supprimes, $existants, &$bilan) {
            PonderationPalier::whereIn('id', $supprimes)->delete();

            foreach ($data['paliers'] ?? [] as $id => $valeurs) {
                $palier = $existants->get((int) $id);
                if ($palier && $palier->fill($valeurs)->isDirty()) {
                    $palier->save();
                    $bilan['modifies']++;
                }
            }

            foreach ($data['nouveaux_paliers'] ?? [] as $valeurs) {
                PonderationPalier::create($valeurs);
                $bilan['ajoutes']++;
            }

            $this->reordonnerPaliers();
        });

        return redirect()->to(role_route('ponderation.index'))->withFragment('paliers')
            ->with('status', $this->message('Paliers de catégorisation enregistrés', $bilan));
    }

    /**
     * Deux paliers ne peuvent partager ni un code (il sert de clé au barème de
     * répartition) ni un seuil (le classement deviendrait ambigu).
     */
    private function verifierPaliers(Collection $paliers): void
    {
        $erreurs = [];

        $codes = $paliers->pluck('code')->map(fn ($code) => Str::upper(trim($code)));
        if ($doublons = $codes->duplicates()->unique()->implode(', ')) {
            $erreurs[] = "Plusieurs paliers portent le même code : {$doublons}.";
        }

        $seuils = $paliers->pluck('seuil_min');
        if ($doublons = $seuils->duplicates()->unique()->map(fn ($seuil) => rtrim(rtrim(number_format($seuil, 2, ',', ' '), '0'), ','))->implode(', ')) {
            $erreurs[] = "Plusieurs paliers commencent au même seuil : {$doublons} points.";
        }

        if ($erreurs) {
            throw ValidationException::withMessages(['paliers' => $erreurs]);
        }
    }

    private function message(string $enregistre, array $bilan): string
    {
        $parties = array_filter([
            $bilan['modifies'] ? $bilan['modifies'].' '.($bilan['modifies'] > 1 ? 'modifications' : 'modification') : null,
            $bilan['ajoutes'] ? $bilan['ajoutes'].' '.($bilan['ajoutes'] > 1 ? 'ajouts' : 'ajout') : null,
            $bilan['supprimes'] ? $bilan['supprimes'].' '.($bilan['supprimes'] > 1 ? 'suppressions' : 'suppression') : null,
        ]);

        return $parties ? $enregistre.' : '.implode(', ', $parties).'.' : 'Aucune modification à enregistrer.';
    }

    private function creerCritere(PonderationRubrique $rubrique, array $valeurs): PonderationCritere
    {
        return $rubrique->criteres()->create([
            'slug' => $this->genererSlug($valeurs['label']),
            'label' => $valeurs['label'],
            'points_max' => $valeurs['points_max'],
            'sort_order' => ($rubrique->criteres()->max('sort_order') ?? -1) + 1,
        ]);
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
