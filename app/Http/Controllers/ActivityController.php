<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FederationActivity;
use App\Support\ActivityCanvasStructure;
use App\Support\PeriodeSaisie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->query('annee');
        $search = mb_substr(trim((string) $request->query('q')), 0, 150);
        $status = in_array($request->query('statut'), ['a_faire', 'en_examen', 'brouillon', 'soumis', 'valide', 'rejete'], true) ? $request->query('statut') : 'tous';

        // « À faire » : ce qui attend la fédération (brouillon, rejet à corriger,
        // activité soumise que la DGF ne peut pas examiner faute de justificatif).
        $aFaire = fn ($query) => $query->where(fn ($q) => $q
            ->whereIn('status', ['brouillon', 'rejete'])
            ->orWhere(fn ($soumis) => $soumis->where('status', 'soumis')->doesntHave('documents')));
        $enExamen = fn ($query) => $query->where('status', 'soumis')->has('documents');

        $query = Auth::user()->activities()
            ->when($year, fn ($q) => $q->where('year', $year))
            ->when($search !== '', fn ($q) => $q->where('designation', 'like', '%'.$search.'%'));
        $counts = (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->all();
        $counts = array_replace(['brouillon' => 0, 'soumis' => 0, 'valide' => 0, 'rejete' => 0], $counts);
        $counts['total'] = array_sum($counts);
        $counts['a_faire'] = (clone $query)->where($aFaire)->count();
        $counts['en_examen'] = (clone $query)->where($enExamen)->count();
        $montantValide = (float) (clone $query)->where('status', 'valide')->sum('montant');

        $activities = $query->withCount('documents')
            ->when($status === 'a_faire', $aFaire)
            ->when($status === 'en_examen', $enExamen)
            ->when(in_array($status, ['brouillon', 'soumis', 'valide', 'rejete'], true), fn ($q) => $q->where('status', $status))
            ->orderByDesc('year')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)->withQueryString();

        // Actions à mener, toutes années confondues, les plus urgentes d'abord.
        $anneesOuvertes = PeriodeSaisie::anneesOuvertes(Auth::user(), PeriodeSaisie::ACTIVITES);
        $aTraiter = Auth::user()->activities()->where($aFaire)->whereIn('year', $anneesOuvertes)->withCount('documents')->get()
            ->sortBy(fn ($activity) => [match ($activity->status) { 'soumis' => 0, 'rejete' => 1, default => 2 }, -$activity->updated_at->timestamp])
            ->values();

        $availableYears = Auth::user()->activities()
            ->select('year')->distinct()->orderByDesc('year')->pluck('year');

        $ouvertures = PeriodeSaisie::ouvertures(Auth::user(), PeriodeSaisie::ACTIVITES);

        return view('activities.index', compact('activities', 'availableYears', 'year', 'search', 'status', 'counts', 'aTraiter', 'montantValide', 'anneesOuvertes', 'ouvertures'));
    }

    public function create(Request $request)
    {
        // L'année en cours, ou une année rouverte par l'administration.
        $annees = PeriodeSaisie::anneesOuvertes(Auth::user(), PeriodeSaisie::ACTIVITES);
        $demandee = (int) $request->query('annee');

        return view('activities.create', [
            'axes' => ActivityCanvasStructure::axes(),
            'activity' => new FederationActivity(),
            'year' => $annees->contains($demandee) ? $demandee : PeriodeSaisie::anneeDeDroit(PeriodeSaisie::ACTIVITES),
            'anneesOuvertes' => $annees,
            'ouvertures' => PeriodeSaisie::ouvertures(Auth::user(), PeriodeSaisie::ACTIVITES),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateActivity($request);
        $data += $this->resolveStatusData($request, new FederationActivity());

        $activity = Auth::user()->activities()->create($data);

        $this->storeDocuments($request, $activity);

        if ($activity->status === 'soumis') {
            ActivityLog::record(
                'created',
                "a soumis l'activité « {$activity->designation} » ({$activity->year}) pour vérification",
                $activity->id,
                Auth::user()->federation_name
            );

            return redirect()->route('activities.show', $activity)->with('status', 'Activité soumise pour vérification.');
        }

        return redirect()->route('activities.show', $activity)->with('status', 'Activité enregistrée en brouillon.');
    }

    public function show(FederationActivity $activity)
    {
        abort_unless($activity->user_id === Auth::id(), 403);

        $activity->load('documents');
        $exerciceOuvert = PeriodeSaisie::estOuverte(Auth::user(), PeriodeSaisie::ACTIVITES, $activity->year);

        return view('activities.show', compact('activity', 'exerciceOuvert'));
    }

    public function edit(FederationActivity $activity)
    {
        abort_unless($activity->user_id === Auth::id(), 403);
        abort_unless($activity->status !== 'valide', 403);
        if ($refus = $this->exerciceClos($activity)) {
            return $refus;
        }

        $activity->load('documents');

        return view('activities.create', [
            'axes' => ActivityCanvasStructure::axes(),
            'activity' => $activity,
            'year' => $activity->year,
            'anneesOuvertes' => collect([$activity->year]),
            'ouvertures' => collect(),
        ]);
    }

    public function update(Request $request, FederationActivity $activity)
    {
        abort_unless($activity->user_id === Auth::id(), 403);
        abort_unless($activity->status !== 'valide', 403);
        if ($refus = $this->exerciceClos($activity)) {
            return $refus;
        }

        $wasSubmittable = in_array($activity->status, ['brouillon', 'rejete'], true);

        $data = $this->validateActivity($request);
        $data += $this->resolveStatusData($request, $activity);

        $activity->update($data);

        $this->storeDocuments($request, $activity);

        if ($wasSubmittable && $activity->status === 'soumis') {
            ActivityLog::record(
                'created',
                "a soumis l'activité « {$activity->designation} » ({$activity->year}) pour vérification",
                $activity->id,
                Auth::user()->federation_name
            );

            return redirect()->route('activities.show', $activity)->with('status', 'Activité soumise pour vérification.');
        }

        return redirect()->route('activities.show', $activity)->with('status', 'Activité mise à jour.');
    }

    public function addDocument(Request $request, FederationActivity $activity)
    {
        abort_unless($activity->user_id === Auth::id(), 403);
        abort_unless($activity->status !== 'valide', 403);
        if ($refus = $this->exerciceClos($activity)) {
            return $refus;
        }

        $request->validate([
            'pieces' => ['required', 'array'],
            'pieces.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $this->storeDocuments($request, $activity);

        return redirect()->route('activities.show', $activity)->with('status', 'Pièce justificative ajoutée.');
    }

    public function submit(FederationActivity $activity)
    {
        abort_unless($activity->user_id === Auth::id(), 403);
        abort_unless($activity->status !== 'valide', 403);
        if ($refus = $this->exerciceClos($activity)) {
            return $refus;
        }

        $activity->update(['status' => 'soumis', 'rejection_reason' => null, 'submitted_at' => now()]);

        ActivityLog::record(
            'created',
            "a soumis l'activité « {$activity->designation} » ({$activity->year}) pour vérification",
            $activity->id,
            Auth::user()->federation_name
        );

        return redirect()->route('activities.show', $activity)->with('status', 'Activité soumise pour vérification.');
    }

    public function destroy(FederationActivity $activity)
    {
        abort_unless($activity->user_id === Auth::id(), 403);
        abort_unless($activity->status === 'brouillon', 403);

        foreach ($activity->documents as $document) {
            Storage::disk('local')->delete($document->file_path);
        }

        $activity->delete();

        return redirect()->route('activities.index')->with('status', 'Activité supprimée.');
    }

    public function downloadDocument(FederationActivity $activity, \App\Models\FederationActivityDocument $document)
    {
        abort_unless($activity->user_id === Auth::id(), 403);
        abort_unless($document->federation_activity_id === $activity->id, 404);

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }

    public function viewDocument(FederationActivity $activity, \App\Models\FederationActivityDocument $document)
    {
        abort_unless($activity->user_id === Auth::id(), 403);
        abort_unless($document->federation_activity_id === $activity->id, 404);

        return Storage::disk('local')->response($document->file_path, $document->original_filename);
    }

    private function resolveStatusData(Request $request, FederationActivity $activity): array
    {
        $intent = $request->input('intent', 'draft');
        $canSubmit = ! $activity->exists || in_array($activity->status, ['brouillon', 'rejete'], true);

        if ($intent === 'submit' && $canSubmit) {
            return [
                'status' => 'soumis',
                'submitted_at' => now(),
                'rejection_reason' => null,
            ];
        }

        if (! $activity->exists) {
            return ['status' => 'brouillon'];
        }

        return [];
    }

    /**
     * Une activité d'une année close ne peut plus être modifiée, complétée ni
     * soumise, sauf ouverture exceptionnelle accordée par l'administration.
     */
    private function exerciceClos(FederationActivity $activity)
    {
        if (PeriodeSaisie::estOuverte(Auth::user(), PeriodeSaisie::ACTIVITES, $activity->year)) {
            return null;
        }

        return redirect()->route('activities.show', $activity)
            ->withErrors(['year' => PeriodeSaisie::messageFerme(PeriodeSaisie::ACTIVITES, $activity->year)]);
    }

    private function validateActivity(Request $request): array
    {
        $request->merge([
            'montant' => $request->filled('montant') ? preg_replace('/[^\d.]/', '', $request->input('montant')) : null,
            'contribution_partenaires' => $request->filled('contribution_partenaires') ? preg_replace('/[^\d.]/', '', $request->input('contribution_partenaires')) : null,
        ]);

        $validator = Validator::make($request->all(), [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'sous_axe_code' => ['required', 'string'],
            'designation' => ['required', 'string', 'max:255'],
            'montant' => ['nullable', 'numeric', 'min:0'],
            'contribution_partenaires' => ['nullable', 'numeric', 'min:0'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'observations' => ['nullable', 'string', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($request) {
            // Seules l'année en cours et les années rouvertes par l'administration sont acceptées.
            if (filled($request->input('year')) && ! PeriodeSaisie::estOuverte(Auth::user(), PeriodeSaisie::ACTIVITES, $request->input('year'))) {
                $validator->errors()->add('year', PeriodeSaisie::messageFerme(PeriodeSaisie::ACTIVITES, $request->input('year')));
            }

            $montant = $request->input('montant');
            $contribution = $request->input('contribution_partenaires');

            if ($montant !== null && $contribution !== null && (float) $contribution > (float) $montant) {
                $validator->errors()->add(
                    'contribution_partenaires',
                    "La contribution des partenaires ne peut pas dépasser le montant total de l'activité."
                );
            }
        });

        $data = $validator->validate();

        $sousAxeIndex = collect(ActivityCanvasStructure::axes())
            ->flatMap(fn ($axe) => collect($axe['sous_axes'])->map(fn ($sousAxe) => [
                'code' => $sousAxe['code'],
                'canvas_sous_axe_id' => $sousAxe['id'],
                'axe' => $axe['code'],
                'axe_label' => $axe['label'],
                'sous_axe_label' => $sousAxe['label'],
            ]))
            ->keyBy('code');

        $meta = $sousAxeIndex->get($data['sous_axe_code']);

        abort_unless($meta, 422, 'Sous-axe invalide.');

        return [
            'year' => $data['year'],
            'canvas_sous_axe_id' => $meta['canvas_sous_axe_id'],
            'axe' => $meta['axe'],
            'axe_label' => $meta['axe_label'],
            'sous_axe_code' => $data['sous_axe_code'],
            'sous_axe_label' => $meta['sous_axe_label'],
            'designation' => $data['designation'],
            'montant' => $data['montant'] ?? null,
            'contribution_partenaires' => $data['contribution_partenaires'] ?? null,
            'date_debut' => $data['date_debut'] ?? null,
            'date_fin' => $data['date_fin'] ?? null,
            'observations' => $data['observations'] ?? null,
        ];
    }

    private function storeDocuments(Request $request, FederationActivity $activity): void
    {
        if (! $request->hasFile('pieces')) {
            return;
        }

        $request->validate([
            'pieces' => ['array'],
            'pieces.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        foreach ($request->file('pieces') as $file) {
            $path = $file->store("activites/{$activity->id}", 'local');

            $activity->documents()->create([
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
            ]);
        }
    }
}
