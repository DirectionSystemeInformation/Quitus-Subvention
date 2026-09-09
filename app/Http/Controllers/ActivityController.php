<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FederationActivity;
use App\Support\ActivityCanvasStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->query('annee');

        $activities = Auth::user()->activities()
            ->with('documents')
            ->when($year, fn ($q) => $q->where('year', $year))
            ->orderByDesc('year')
            ->orderByDesc('created_at')
            ->get();

        $availableYears = Auth::user()->activities()
            ->select('year')->distinct()->orderByDesc('year')->pluck('year');

        return view('activities.index', compact('activities', 'availableYears', 'year'));
    }

    public function create()
    {
        return view('activities.create', [
            'axes' => ActivityCanvasStructure::axes(),
            'activity' => new FederationActivity(),
            'year' => now()->year,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateActivity($request);

        $activity = Auth::user()->activities()->create($data + ['status' => 'brouillon']);

        $this->storeDocuments($request, $activity);

        return redirect()->route('activities.show', $activity)->with('status', 'Activité enregistrée en brouillon.');
    }

    public function show(FederationActivity $activity)
    {
        abort_unless($activity->user_id === Auth::id(), 403);

        $activity->load('documents');

        return view('activities.show', compact('activity'));
    }

    public function edit(FederationActivity $activity)
    {
        abort_unless($activity->user_id === Auth::id(), 403);
        abort_unless($activity->status !== 'valide', 403);

        $activity->load('documents');

        return view('activities.create', [
            'axes' => ActivityCanvasStructure::axes(),
            'activity' => $activity,
            'year' => $activity->year,
        ]);
    }

    public function update(Request $request, FederationActivity $activity)
    {
        abort_unless($activity->user_id === Auth::id(), 403);
        abort_unless($activity->status !== 'valide', 403);

        $data = $this->validateActivity($request);

        $activity->update($data);

        $this->storeDocuments($request, $activity);

        return redirect()->route('activities.show', $activity)->with('status', 'Activité mise à jour.');
    }

    public function addDocument(Request $request, FederationActivity $activity)
    {
        abort_unless($activity->user_id === Auth::id(), 403);
        abort_unless($activity->status !== 'valide', 403);

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
        abort_unless(in_array($activity->status, ['brouillon', 'rejete'], true), 403);

        $activity->update(['status' => 'soumis', 'rejection_reason' => null]);

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

    private function validateActivity(Request $request): array
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'sous_axe_code' => ['required', 'string'],
            'designation' => ['required', 'string', 'max:255'],
            'montant' => ['nullable', 'numeric', 'min:0'],
            'contribution_partenaires' => ['nullable', 'string', 'max:255'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'observations' => ['nullable', 'string', 'max:255'],
        ]);

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
