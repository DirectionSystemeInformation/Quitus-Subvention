<?php

namespace App\Http\Controllers\Talents;

use App\Http\Controllers\Controller;
use App\Models\Talents\Discipline;
use App\Models\Talents\Entraineur;
use App\Models\Talents\Federation;
use App\Models\Talents\JournalActivite;
use App\Models\Talents\Licence;
use App\Models\Talents\Referentiel;
use App\Models\Talents\Talent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TalentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:talents.voir', only: ['index', 'show', 'imprimer']),
            new Middleware('can:talents.creer', only: ['create', 'store']),
            new Middleware('can:talents.modifier', only: ['edit', 'update', 'soumettre']),
            new Middleware('can:talents.supprimer', only: ['destroy']),
            new Middleware('can:talents.valider', only: ['validations', 'valider', 'rejeter']),
        ];
    }

    public function index(Request $request)
    {
        $filtres = $request->only('q', 'federation_id', 'discipline_id', 'genre', 'statut_carriere', 'statut_profil', 'region', 'categorie', 'haut_potentiel');

        $query = Talent::visiblePar($request->user())->filtrer($filtres)
            ->with(['discipline', 'federation', 'licenceNationale', 'licenceInternationale'])
            ->orderBy($request->query('tri', 'nom') === 'recent' ? 'created_at' : 'nom', $request->query('tri') === 'recent' ? 'desc' : 'asc');

        if ($request->query('export') === 'csv') {
            abort_unless($request->user()->can('talents.exporter'), 403);

            return $this->exporter($query);
        }

        return view('suivi.talents.index', [
            'talents' => $query->paginate(20)->withQueryString(),
            'federations' => $request->user()->aAccesNational() ? Federation::orderBy('sigle')->get() : collect(),
            'disciplines' => Discipline::visiblePar($request->user())->orderBy('nom')->get(),
            'vue' => $request->query('vue', 'liste'),
        ]);
    }

    public function show(Request $request, Talent $talent)
    {
        $this->autoriser($request, $talent);
        $talent->load(['federation', 'discipline', 'entraineur', 'licences', 'performances.competition',
            'evaluations', 'suivisMedicaux', 'parcours.entraineur', 'bourses', 'reconversion', 'validateur']);

        return view('suivi.talents.show', [
            't' => $talent,
            'onglet' => $request->query('onglet', 'identite'),
            'historique' => JournalActivite::where('module', 'Talent')->where('objet_id', $talent->id)
                ->with('user')->latest('created_at')->limit(15)->get(),
        ]);
    }

    public function imprimer(Request $request, Talent $talent)
    {
        $this->autoriser($request, $talent);
        $talent->load(['federation', 'discipline', 'licences', 'performances.competition', 'evaluations', 'parcours', 'reconversion']);

        return view('suivi.talents.imprimer', ['t' => $talent]);
    }

    public function create()
    {
        return view('suivi.talents.form', $this->donneesFormulaire(new Talent(['nationalites' => 'Burkinabè', 'statut_carriere' => 'Actif'])));
    }

    public function store(Request $request)
    {
        $donnees = $this->validerDonnees($request);

        $talent = DB::transaction(function () use ($request, $donnees) {
            $talent = new Talent($donnees);
            $talent->statut_profil = 'Brouillon';
            $this->traiterPhoto($request, $talent);
            $talent->save();

            if ($request->filled('licence_numero')) {
                Licence::create([
                    'talent_id' => $talent->id, 'portee' => 'Nationale', 'type_licence' => 'Nationale',
                    'numero' => $request->input('licence_numero'), 'organisme' => $talent->federation->sigle,
                    'date_expiration' => $request->input('licence_expiration'),
                ]);
            }

            if ($request->input('action') === 'soumettre') {
                $this->passerEnValidation($talent);
            }

            return $talent;
        });

        return redirect()->route('suivi.talents.show', $talent)->with('succes', "Talent {$talent->code} enregistré.");
    }

    public function edit(Request $request, Talent $talent)
    {
        $this->autoriser($request, $talent);

        return view('suivi.talents.form', $this->donneesFormulaire($talent));
    }

    public function update(Request $request, Talent $talent)
    {
        $this->autoriser($request, $talent);
        $talent->fill($this->validerDonnees($request, $talent));
        $this->traiterPhoto($request, $talent);

        // Une modification d'un profil rejeté le renvoie en brouillon pour correction.
        if ($talent->statut_profil === 'Rejeté') {
            $talent->statut_profil = 'Brouillon';
        }
        $talent->save();

        if ($request->input('action') === 'soumettre') {
            $this->passerEnValidation($talent);
        }

        return redirect()->route('suivi.talents.show', $talent)->with('succes', 'Fiche mise à jour.');
    }

    public function destroy(Request $request, Talent $talent)
    {
        $this->autoriser($request, $talent);
        $talent->delete();

        return redirect()->route('suivi.talents.index')->with('succes', "Talent {$talent->code} archivé.");
    }

    // ---- Circuit de validation ----

    public function soumettre(Request $request, Talent $talent)
    {
        $this->autoriser($request, $talent);
        $this->passerEnValidation($talent);

        return back()->with('succes', "Profil soumis : statut « {$talent->statut_profil} ».");
    }

    public function validations(Request $request)
    {
        $base = Talent::visiblePar($request->user())->with(['federation', 'discipline', 'licenceNationale']);

        return view('suivi.talents.validations', [
            'enAttente' => (clone $base)->where('statut_profil', 'En attente validation')->orderBy('updated_at')->get(),
            'attenteApi' => (clone $base)->where('statut_profil', 'En attente API')->orderBy('updated_at')->get(),
            'rejetes' => (clone $base)->where('statut_profil', 'Rejeté')->latest('updated_at')->limit(20)->get(),
        ]);
    }

    public function valider(Request $request, Talent $talent)
    {
        $this->autoriser($request, $talent);
        $talent->forceFill(['statut_profil' => 'Validé', 'valide_par' => $request->user()->id, 'valide_le' => now(), 'motif_rejet' => null])->save();
        $talent->journaliser('validation');

        return back()->with('succes', "Profil {$talent->code} validé.");
    }

    public function rejeter(Request $request, Talent $talent)
    {
        $this->autoriser($request, $talent);
        $data = $request->validate(['motif_rejet' => ['required', 'string', 'max:1000']]);
        $talent->forceFill(['statut_profil' => 'Rejeté', 'motif_rejet' => $data['motif_rejet']])->save();
        $talent->journaliser('rejet', $data);

        return back()->with('succes', "Profil {$talent->code} rejeté.");
    }

    // ---------------------------------------------------------------

    private function passerEnValidation(Talent $talent): void
    {
        $licence = $talent->licenceNationale()->first();
        $talent->update([
            'statut_profil' => $licence && $licence->verification_api !== 'Vérifiée' ? 'En attente API' : 'En attente validation',
        ]);
    }

    private function autoriser(Request $request, Talent $talent): void
    {
        // 404 et non 403 : on ne confirme pas l'existence d'un athlète d'une autre fédération.
        abort_unless(Talent::visiblePar($request->user())->whereKey($talent->id)->exists(), 404);
    }

    private function validerDonnees(Request $request, ?Talent $talent = null): array
    {
        $user = $request->user();
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['required', 'string', 'max:150'],
            'date_naissance' => ['required', 'date', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:100'],
            'genre' => ['required', Rule::in(['Masculin', 'Féminin'])],
            'nationalites' => ['required', 'string', 'max:150'],
            'region' => ['nullable', 'string'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email'],
            'federation_id' => ['required', 'exists:talent_federations,id'],
            'discipline_id' => ['required', Rule::exists('talent_disciplines', 'id')->where('federation_id',
                $user->aAccesNational() ? $request->input('federation_id') : $user->federationCloisonnee())],
            'specialite' => ['nullable', 'string', 'max:100'],
            'entraineur_id' => ['nullable', Rule::in(Entraineur::visiblePar($user)->pluck('id'))],
            'statut_carriere' => ['required', 'string'],
            'haut_potentiel' => ['nullable'],
            'observations' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'licence_numero' => ['nullable', 'string', 'max:60', Rule::unique('talent_licences', 'numero')],
            'licence_expiration' => ['nullable', 'date'],
        ], ['licence_numero.unique' => Licence::MESSAGE_DOUBLON]);

        // Un compte fédération ne peut enregistrer que dans sa propre fédération.
        if (! $user->aAccesNational()) {
            $donnees['federation_id'] = $user->federationCloisonnee();
        }
        $donnees['haut_potentiel'] = $request->boolean('haut_potentiel');
        unset($donnees['photo'], $donnees['licence_numero'], $donnees['licence_expiration']);

        return $donnees;
    }

    private function traiterPhoto(Request $request, Talent $talent): void
    {
        if ($request->hasFile('photo')) {
            if ($talent->photo) {
                Storage::disk('public')->delete($talent->photo);
            }
            $talent->photo = $request->file('photo')->store('photos', 'public');
        }
    }

    private function donneesFormulaire(Talent $talent): array
    {
        $user = request()->user();

        return [
            'talent' => $talent,
            'federations' => Federation::where('actif', true)
                ->when(! $user->aAccesNational(), fn ($q) => $q->whereKey($user->federationCloisonnee()))->orderBy('sigle')->get(),
            'disciplines' => Discipline::visiblePar($user)->with('specialites')->where('actif', true)->orderBy('nom')->get(),
            'entraineurs' => Entraineur::visiblePar($user)->where('actif', true)->orderBy('nom')->get(),
            'regions' => Referentiel::liste('region'),
            'statutsCarriere' => Referentiel::liste('statut_carriere'),
        ];
    }

    private function exporter($query)
    {
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID Talent', 'Nom', 'Prénom(s)', 'Date Naissance', 'Genre', 'Nationalité(s)', 'Discipline', 'Spécialité',
                'Type de Licence', 'N° Licence Nationale', 'Fédération', 'N° Licence Int.', 'Club Expatrié', 'Statut Carrière', 'Statut Profil'], ';');
            $query->chunk(500, function ($lot) use ($out) {
                foreach ($lot as $t) {
                    fputcsv($out, [$t->code, $t->nom, $t->prenoms, $t->date_naissance->format('Y-m-d'), $t->genre, $t->nationalites,
                        $t->discipline->nom, $t->specialite, $t->licenceInternationale?->type_licence ?? $t->licenceNationale?->type_licence,
                        $t->licenceNationale?->numero, $t->federation->nom, $t->licenceInternationale?->numero,
                        $t->licenceInternationale?->club, $t->statut_carriere, $t->statut_profil], ';');
                }
            });
            fclose($out);
        }, 'talents-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
