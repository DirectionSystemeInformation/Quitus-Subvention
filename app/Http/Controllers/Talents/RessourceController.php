<?php

namespace App\Http\Controllers\Talents;

use App\Http\Controllers\Controller;
use App\Models\Talents\Discipline;
use App\Models\Talents\Federation;
use App\Models\Talents\Talent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Contrôleur générique d'un module (liste filtrable, formulaire, export CSV).
 *
 * Chaque module déclare ses champs, colonnes et filtres : ajouter une donnée à suivre
 * revient à compléter ces tableaux, sans dupliquer les vues.
 */
abstract class RessourceController extends Controller implements HasMiddleware
{
    /** Préfixe des permissions (ex : "performances" => performances.voir, performances.creer...) */
    protected static string $module;

    /** @var class-string<Model> */
    protected string $modele;

    protected string $route;

    protected string $titre;

    protected string $singulier;

    protected string $icone = 'bi-folder';

    protected bool $lieAuTalent = true;

    protected array $with = [];

    protected string $ordre = 'created_at';

    abstract protected function champs(?Model $objet = null): array;

    abstract protected function colonnes(): array;

    protected function regles(?Model $objet = null): array
    {
        $regles = [];
        foreach ($this->champs($objet) as $nom => $champ) {
            $regles[$nom] = $champ['regles'] ?? [($champ['requis'] ?? false) ? 'required' : 'nullable'];
        }

        return $regles;
    }

    /** Messages de validation propres au module. */
    protected function messages(): array
    {
        return [];
    }

    /** Filtres spécifiques au module : nom => [label, options] (égalité stricte sur la colonne). */
    protected function filtres(): array
    {
        return [];
    }

    public static function middleware(): array
    {
        $m = static::$module;

        return [
            new Middleware("can:$m.voir", only: ['index', 'show']),
            new Middleware("can:$m.creer", only: ['create', 'store']),
            new Middleware("can:$m.modifier", only: ['edit', 'update']),
            new Middleware("can:$m.supprimer", only: ['destroy']),
        ];
    }

    // ---------------------------------------------------------------

    public function index(Request $request)
    {
        $query = $this->requete($request);

        if ($request->query('export') === 'csv') {
            abort_unless($request->user()->canAny([static::$module.'.exporter', 'rapports.voir']), 403);

            return $this->exporterCsv($query);
        }

        return view('suivi.ressources.index', [
            'c' => $this->config(),
            'objets' => $query->paginate(20)->withQueryString(),
            'colonnes' => $this->colonnes(),
            'filtres' => $this->filtres(),
            'federations' => $this->lieAuTalent && $request->user()->aAccesNational() ? Federation::orderBy('sigle')->get() : collect(),
            'disciplines' => $this->lieAuTalent ? Discipline::visiblePar($request->user())->orderBy('nom')->get() : collect(),
        ]);
    }

    public function create(Request $request)
    {
        $objet = new $this->modele;
        if ($this->lieAuTalent && $request->filled('talent_id')) {
            $objet->talent_id = $request->integer('talent_id');
        }

        return $this->formulaire($objet);
    }

    public function store(Request $request)
    {
        $objet = new $this->modele;
        $this->enregistrer($request, $objet);

        return $this->apresEnregistrement($request, "{$this->singulier} enregistré(e) avec succès.");
    }

    public function edit(Request $request, string $id)
    {
        return $this->formulaire($this->trouver($request, $id));
    }

    public function update(Request $request, string $id)
    {
        $this->enregistrer($request, $this->trouver($request, $id));

        return $this->apresEnregistrement($request, "{$this->singulier} mis(e) à jour.");
    }

    public function destroy(Request $request, string $id)
    {
        $this->trouver($request, $id)->delete();

        return back()->with('succes', "{$this->singulier} supprimé(e).");
    }

    // ---------------------------------------------------------------

    protected function requete(Request $request): Builder
    {
        $query = $this->modele::query()->with($this->with);
        $user = $request->user();

        if ($this->lieAuTalent) {
            $query->visiblePar($user)->with('talent.discipline')
                ->whereHas('talent', fn ($q) => $q->filtrer($request->only('q', 'federation_id', 'discipline_id', 'genre')));
        } elseif (method_exists($this->modele, 'scopeVisiblePar')) {
            $query->visiblePar($user);
        }

        foreach (array_keys($this->filtres()) as $champ) {
            $query->when($request->query($champ), fn ($q, $v) => $q->where($champ, $v));
        }

        $this->filtrerRequete($query, $request);

        return $query->orderByDesc($this->ordre);
    }

    /** Point d'extension pour une recherche libre propre au module. */
    protected function filtrerRequete(Builder $query, Request $request): void
    {
        if (! $this->lieAuTalent && $request->filled('q')) {
            $query->where('nom', 'like', '%'.$request->query('q').'%');
        }
    }

    protected function trouver(Request $request, string $id): Model
    {
        $query = $this->modele::query();
        if ($this->lieAuTalent || method_exists($this->modele, 'scopeVisiblePar')) {
            $query->visiblePar($request->user());
        }

        return $query->findOrFail($id);
    }

    protected function enregistrer(Request $request, Model $objet): void
    {
        $donnees = $request->validate($this->regles($objet), $this->messages());

        foreach ($this->champs($objet) as $nom => $champ) {
            if (($champ['type'] ?? null) === 'checkbox') {
                $donnees[$nom] = $request->boolean($nom);
            }
        }

        if ($this->lieAuTalent) {
            abort_unless(Talent::visiblePar($request->user())->whereKey($donnees['talent_id'])->exists(), 404);
        }

        $objet->fill($donnees);
        if (! $objet->exists && Schema::hasColumn($objet->getTable(), 'saisi_par')) {
            $objet->saisi_par = $request->user()->id;
        }
        $this->avantSauvegarde($request, $objet);
        $objet->save();
    }

    protected function avantSauvegarde(Request $request, Model $objet): void {}

    protected function apresEnregistrement(Request $request, string $message)
    {
        $retour = $request->input('retour');

        return ($retour && str_starts_with($retour, url('/')) ? redirect($retour) : redirect()->route("{$this->route}.index"))
            ->with('succes', $message);
    }

    protected function formulaire(Model $objet)
    {
        return view('suivi.ressources.form', [
            'c' => $this->config(),
            'objet' => $objet,
            'champs' => $this->champs($objet),
            'talents' => $this->lieAuTalent
                ? Talent::visiblePar(request()->user())->orderBy('nom')->get(['id', 'code', 'nom', 'prenoms'])
                : collect(),
        ]);
    }

    protected function config(): array
    {
        return [
            'module' => static::$module,
            'route' => $this->route,
            'titre' => $this->titre,
            'singulier' => $this->singulier,
            'icone' => $this->icone,
            'lieAuTalent' => $this->lieAuTalent,
        ];
    }

    protected function exporterCsv(Builder $query): StreamedResponse
    {
        $colonnes = $this->colonnes();
        $fichier = str_replace('suivi.', '', $this->route).'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query, $colonnes) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM pour Excel
            $entetes = array_keys($colonnes);
            if ($this->lieAuTalent) {
                array_unshift($entetes, 'ID Talent', 'Nom & Prénom');
            }
            fputcsv($out, $entetes, ';');

            $query->chunk(500, function ($lot) use ($out, $colonnes) {
                foreach ($lot as $objet) {
                    $ligne = array_map(fn ($valeur) => strip_tags((string) $valeur($objet)), $colonnes);
                    if ($this->lieAuTalent) {
                        array_unshift($ligne, $objet->talent?->code, $objet->talent?->nom_complet);
                    }
                    fputcsv($out, array_values($ligne), ';');
                }
            });
            fclose($out);
        }, $fichier, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Options d'un select à partir d'une liste simple (valeur = libellé). */
    protected static function options(iterable $valeurs): array
    {
        $options = [];
        foreach ($valeurs as $valeur) {
            $options[$valeur] = $valeur;
        }

        return $options;
    }
}
