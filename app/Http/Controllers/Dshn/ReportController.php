<?php

namespace App\Http\Controllers\Dshn;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::with('user')->latest()->get();

        return view('dshn.reports', compact('reports'));
    }

    public function download(Report $report)
    {
        abort_unless($report->file_path, 404);

        return Storage::disk('local')->download($report->file_path, $report->original_filename);
    }

    public function validate_(Report $report)
    {
        $report->update(['status' => 'valide']);

        return back()->with('status', $report->typeLabel().' ('.$report->year.') validé.');
    }

    public function reject(Report $report)
    {
        $report->update(['status' => 'rejete']);

        return back()->with('status', $report->typeLabel().' ('.$report->year.') rejeté.');
    }
}
