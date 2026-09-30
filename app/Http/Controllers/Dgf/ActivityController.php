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
            'statut' => ['nullable', 'in:soumis,brouillon,rejete,valide'],
            'justificatif' => ['nullable', 'in:avec,sans'],
            'annee' => ['nullable', 'integer', 'between:2000,2100'],
            'federation' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);
        $statut = $filters['statut'] ?? 'soumis';
        $justificatif = $filters['justificatif'] ?? null;

        $query = FederationActivity::query()
            ->when($filters['annee'] ?? null, fn ($q, $year) => $q->where('year', $year))
            ->when($filters['federation'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->where(fn ($matches) => $matches
                ->where('designation', 'like', '%'.$search.'%')
                ->orWhereHas('user', fn ($users) => $users->where('federation_name', 'like', '%'.$search.'%'))));

        $counts = array_replace(['soumis' => 0, 'brouillon' => 0, 'rejete' => 0, 'valide' => 0],
            (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->all());
        $counts['avec'] = (clone $query)->where('status', $statut)->has('documents')->count();
        $counts['sans'] = (clone $query)->where('status', $statut)->doesntHave('documents')->count();
        $montantValide = (float) (clone $query)->where('status', 'valide')->sum('montant');

        $activities = $query->where('status', $statut)
            ->with('user')->withCount('documents')
            ->when($justificatif === 'avec', fn ($q) => $q->has('documents'))
            ->when($justificatif === 'sans', fn ($q) => $q->doesntHave('documents'))
            // À traiter : d'abord ce qui peut être examiné, du plus ancien au plus récent.
            ->when($statut === 'soumis', fn ($q) => $q->orderByRaw('documents_count = 0')->orderByRaw('submitted_at IS NULL')->orderBy('submitted_at'))
            ->when($statut === 'valide', fn ($q) => $q->orderByDesc('validated_at'))
            ->when(in_array($statut, ['brouillon', 'rejete'], true), fn ($q) => $q->orderByDesc('updated_at'))
            ->orderBy('id')
            ->paginate(25)->withQueryString();

        // File prioritaire, indépendante des filtres : les décisions en attente.
        $aExaminer = FederationActivity::where('status', 'soumis')->has('documents')
            ->with('user')->orderByRaw('submitted_at IS NULL')->orderBy('submitted_at')->orderBy('id')->get();

        $federations = User::where('role', 'federation')->orderBy('federation_name')->get(['id', 'federation_name']);
        $years = FederationActivity::distinct()->orderByDesc('year')->pluck('year');

        return view('dgf.activities.index', compact('activities', 'statut', 'justificatif', 'counts', 'montantValide', 'aExaminer', 'federations', 'years'));
    }

    public function show(FederationActivity $activity)
    {
        $activity->load('user', 'documents', 'validator');

        // File de contrôle : activités soumises avec justificatif, les plus anciennes
        // d'abord, pour enchaîner les décisions sans repasser par la liste.
        $aExaminer = FederationActivity::where('status', 'soumis')->whereHas('documents');
        $suivante = (clone $aExaminer)->whereKeyNot($activity->id)->orderBy('submitted_at')->orderBy('id')->first();
        $enAttente = $aExaminer->count();

        return view('dgf.activities.show', compact('activity', 'suivante', 'enAttente'));
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
                ['status' => 'soumis', 'rejection_reason' => null, 'validated_by' => null, 'validated_at' => null]
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
                    'date' => $activity->date_debut,
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

    public function viewDocument(FederationActivity $activity, FederationActivityDocument $document)
    {
        abort_unless($document->federation_activity_id === $activity->id, 404);

        return Storage::disk('local')->response($document->file_path, $document->original_filename);
    }
}
