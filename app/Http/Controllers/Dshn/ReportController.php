<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'statut' => ['nullable', 'in:tous,soumis,valide,rejete'],
            'type' => ['nullable', 'in:rapport_activite,programme_budgetise,programme_reamenage'],
            'annee' => ['nullable', 'integer', 'between:2000,2100'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);
        $status = $filters['statut'] ?? 'soumis';
        $query = Report::whereIn('status', ['soumis', 'valide', 'rejete'])
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['annee'] ?? null, fn ($q, $year) => $q->where('year', $year))
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->whereHas('user', fn ($users) => $users->where('federation_name', 'like', '%'.$search.'%')));
        $statusCounts = (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $counts = ['total' => $statusCounts->sum(), 'soumis' => $statusCounts->get('soumis', 0), 'valide' => $statusCounts->get('valide', 0), 'rejete' => $statusCounts->get('rejete', 0)];
        $reports = $query->with('user')->when($status !== 'tous', fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE status WHEN 'soumis' THEN 0 ELSE 1 END")
            ->orderBy('updated_at')->orderBy('id')->paginate(20)->withQueryString();
        $documentTypes = collect(['rapport_activite', 'programme_budgetise', 'programme_reamenage'])
            ->mapWithKeys(fn ($type) => [$type => (new Report(['type' => $type]))->typeLabel()]);
        $years = Report::whereIn('status', ['soumis', 'valide', 'rejete'])->distinct()->orderByDesc('year')->pluck('year');

        return view('dshn.reports', compact('reports', 'documentTypes', 'years', 'counts', 'status'));
    }

    public function download(Report $report)
    {
        abort_if($report->status === 'brouillon', 403);
        abort_unless($report->file_path, 404);

        abort_unless(Storage::disk('local')->exists($report->file_path), 404, 'Le fichier demandé est introuvable.');

        return Storage::disk('local')->download($report->file_path, $report->original_filename);
    }

    public function validate_(Report $report)
    {
        abort_if($report->status === 'brouillon', 403);
        $report->update([
            'status' => 'valide',
            'rejection_reason' => null,
            'validated_by' => Auth::id(),
            'validated_at' => now(),
        ]);

        ActivityLog::record(
            'validated',
            "a validé le {$report->typeLabel()} ({$report->year}) de {$report->user->federation_name}",
            $report->id,
            $report->user->federation_name
        );

        return back()->with('status', $report->typeLabel().' ('.$report->year.') validé.');
    }

    public function reject(Request $request, Report $report)
    {
        // Un brouillon reste privé à sa fédération, comme pour la validation.
        abort_if($report->status === 'brouillon', 403);
        abort_unless($report->status === 'soumis', 404);
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $report->update(['status' => 'rejete', 'rejection_reason' => $data['rejection_reason']]);

        ActivityLog::record(
            'rejected',
            "a rejeté le {$report->typeLabel()} ({$report->year}) de {$report->user->federation_name} (motif : {$data['rejection_reason']})",
            $report->id,
            $report->user->federation_name
        );

        return back()->with('status', $report->typeLabel().' ('.$report->year.') rejeté.');
    }
}
