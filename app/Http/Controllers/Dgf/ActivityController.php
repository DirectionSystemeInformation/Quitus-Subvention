<?php

namespace App\Http\Controllers\Dgf;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BudgetLine;
use App\Models\FederationActivity;
use App\Models\FederationActivityDocument;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'statut' => ['nullable', 'in:soumis,brouillon'],
            'annee' => ['nullable', 'integer', 'between:2000,2100'],
            'federation' => ['nullable', 'integer'],
        ]);
        $status = $filters['statut'] ?? 'soumis';
        $query = FederationActivity::query()
            ->when($filters['annee'] ?? null, fn ($q, $year) => $q->where('year', $year))
            ->when($filters['federation'] ?? null, fn ($q, $id) => $q->where('user_id', $id));
        $counts = [
            'soumis' => (clone $query)->where('status', 'soumis')->count(),
            'brouillon' => (clone $query)->where('status', 'brouillon')->count(),
        ];
        $activities = $query->with('user', 'documents')->where('status', $status)
            ->orderBy('created_at')->paginate(25)->withQueryString();
        $federations = User::where('role', 'federation')->orderBy('federation_name')->get(['id', 'federation_name']);
        $years = FederationActivity::whereIn('status', ['soumis', 'brouillon'])->distinct()->orderByDesc('year')->pluck('year');

        return view('dgf.activities.index', compact('activities', 'status', 'counts', 'federations', 'years'));
    }

    public function show(FederationActivity $activity)
    {
        $activity->load('user', 'documents', 'validator');

        return view('dgf.activities.show', compact('activity'));
    }

    public function validate_(FederationActivity $activity)
    {
        abort_unless($activity->status === 'soumis', 404);

        if ($activity->documents()->doesntExist()) {
            return back()->withErrors(['pieces' => 'Impossible de valider : aucune pièce justificative jointe. Rejetez l\'activité pour que la fédération en ajoute une.']);
        }

        DB::transaction(function () use ($activity) {
            $report = Report::updateOrCreate(
                ['user_id' => $activity->user_id, 'type' => 'rapport_activite', 'year' => $activity->year],
                ['status' => 'soumis', 'rejection_reason' => null]
            );

            $nextNumero = BudgetLine::where('report_id', $report->id)
                ->where('sous_axe_code', $activity->sous_axe_code)
                ->max('numero_ligne');

            $budgetLine = BudgetLine::updateOrCreate(
                ['activity_id' => $activity->id],
                [
                    'report_id' => $report->id,
                    'canvas_sous_axe_id' => $activity->canvas_sous_axe_id,
                    'axe' => $activity->axe,
                    'axe_label' => $activity->axe_label,
                    'sous_axe_code' => $activity->sous_axe_code,
                    'sous_axe_label' => $activity->sous_axe_label,
                    'numero_ligne' => $nextNumero ? $nextNumero + 1 : 1,
                    'designation' => $activity->designation,
                    'montant' => $activity->montant,
                    'contribution_partenaires' => $activity->contribution_partenaires,
                    'date' => $activity->date,
                    'observations' => $activity->observations,
                ]
            );

            $activity->update([
                'status' => 'valide',
                'validated_by' => Auth::id(),
                'validated_at' => now(),
                'budget_line_id' => $budgetLine->id,
            ]);
        });

        ActivityLog::record(
            'validated',
            "a validé l'activité « {$activity->designation} » ({$activity->year}) de {$activity->user->federation_name}",
            $activity->id,
            $activity->user->federation_name
        );

        return back()->with('status', 'Activité validée et versée au rapport d\'activité.');
    }

    public function reject(Request $request, FederationActivity $activity)
    {
        abort_unless($activity->status === 'soumis', 404);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $activity->update(['status' => 'rejete', 'rejection_reason' => $data['rejection_reason']]);

        ActivityLog::record(
            'rejected',
            "a rejeté l'activité « {$activity->designation} » ({$activity->year}) de {$activity->user->federation_name} (motif : {$data['rejection_reason']})",
            $activity->id,
            $activity->user->federation_name
        );

        return back()->with('status', 'Activité rejetée.');
    }

    public function downloadDocument(FederationActivity $activity, FederationActivityDocument $document)
    {
        abort_unless($document->federation_activity_id === $activity->id, 404);

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }
}
