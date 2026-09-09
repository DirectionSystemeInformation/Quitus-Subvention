<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::with('user')->where('status', '!=', 'brouillon')->latest()->get();

        $grouped = $reports->groupBy('type');
        $reportsByType = collect(['rapport_activite', 'programme_budgetise', 'programme_reamenage'])
            ->filter(fn ($type) => $grouped->has($type))
            ->map(fn ($type) => [
                'type' => $type,
                'label' => $grouped[$type]->first()->typeLabel(),
                'reports' => $grouped[$type],
            ])
            ->values();

        $documentTypes = collect(['rapport_activite', 'programme_budgetise', 'programme_reamenage'])
            ->filter(fn ($type) => $grouped->has($type))
            ->mapWithKeys(fn ($type) => [$type => $grouped[$type]->first()->typeLabel()]);

        $years = $reports->pluck('year')->unique()->sortDesc()->values();

        $counts = [
            'total' => $reports->count(),
            'soumis' => $reports->where('status', 'soumis')->count(),
            'valide' => $reports->where('status', 'valide')->count(),
            'rejete' => $reports->where('status', 'rejete')->count(),
        ];

        return view('dshn.reports', compact('reports', 'reportsByType', 'documentTypes', 'years', 'counts'));
    }

    public function download(Report $report)
    {
        abort_if($report->status === 'brouillon', 403);
        abort_unless($report->file_path, 404);

        return Storage::disk('local')->download($report->file_path, $report->original_filename);
    }

    public function validate_(Report $report)
    {
        abort_if($report->status === 'brouillon', 403);
        $report->update(['status' => 'valide', 'rejection_reason' => null]);

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
