<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Support\ActivityCanvasStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityFormController extends Controller
{
    private const TITLES = [
        'programme_budgetise' => "Programme d'activités budgétisé",
        'rapport_activite' => "Rapport d'activité",
    ];

    public function create(Request $request, string $type)
    {
        abort_unless(array_key_exists($type, self::TITLES), 404);

        $year = (int) $request->query('annee', $type === 'programme_budgetise' ? now()->year + 1 : now()->year);

        $report = Auth::user()->reports()
            ->where('type', $type)
            ->where('year', $year)
            ->with('budgetLines')
            ->first();

        $existingLines = collect();
        if ($report) {
            $existingLines = $report->budgetLines->keyBy(fn ($l) => $l->sous_axe_code.'|'.$l->numero_ligne);
        }

        return view('activity-form.create', [
            'type' => $type,
            'title' => self::TITLES[$type],
            'axes' => ActivityCanvasStructure::axes(),
            'year' => $year,
            'existingLines' => $existingLines,
            'report' => $report,
        ]);
    }

    public function store(Request $request, string $type)
    {
        abort_unless(array_key_exists($type, self::TITLES), 404);

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'lignes' => ['nullable', 'array'],
            'lignes.*.designation' => ['nullable', 'string', 'max:255'],
            'lignes.*.montant' => ['nullable', 'numeric', 'min:0'],
            'lignes.*.contribution_partenaires' => ['nullable', 'string', 'max:255'],
            'lignes.*.date' => ['nullable', 'date'],
            'lignes.*.observations' => ['nullable', 'string', 'max:255'],
        ]);

        $report = Auth::user()->reports()->updateOrCreate(
            ['type' => $type, 'year' => $data['year']],
            ['status' => 'soumis']
        );

        $report->budgetLines()->delete();

        foreach (ActivityCanvasStructure::lignes() as $ligne) {
            $key = $ligne['sous_axe_code'].'-'.$ligne['numero_ligne'];
            $input = $data['lignes'][$key] ?? [];

            if (empty(array_filter($input))) {
                continue;
            }

            $report->budgetLines()->create([
                'axe' => $ligne['axe'],
                'sous_axe_code' => $ligne['sous_axe_code'],
                'sous_axe_label' => $ligne['sous_axe_label'],
                'numero_ligne' => $ligne['numero_ligne'],
                'designation' => $input['designation'] ?? null,
                'montant' => $input['montant'] ?? null,
                'contribution_partenaires' => $input['contribution_partenaires'] ?? null,
                'date' => $input['date'] ?? null,
                'observations' => $input['observations'] ?? null,
            ]);
        }

        return redirect()->route('dashboard')->with('status', self::TITLES[$type].' soumis avec succès.');
    }

    public function show(Report $report)
    {
        abort_unless(array_key_exists($report->type, self::TITLES), 404);

        abort_unless(
            Auth::user()->id === $report->user_id || Auth::user()->isDshn(),
            403
        );

        $report->load('budgetLines', 'user');

        return view('activity-form.show', [
            'type' => $report->type,
            'title' => self::TITLES[$report->type],
            'axes' => ActivityCanvasStructure::axes(),
            'report' => $report,
            'lines' => $report->budgetLines->keyBy(fn ($l) => $l->sous_axe_code.'|'.$l->numero_ligne),
        ]);
    }
}
