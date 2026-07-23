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

    public function index(Request $request)
    {
        $type = $request->query('type', 'rapport_activite');

        abort_unless(array_key_exists($type, self::TITLES), 404);

        $computedDefaultYear = $type === 'programme_budgetise' ? now()->year + 1 : now()->year;

        $reports = Auth::user()->reports()
            ->where('type', $type)
            ->orderByDesc('year')
            ->get();

        $selectedYear = (int) $request->query('annee', $computedDefaultYear);
        $selectedReport = $reports->firstWhere('year', $selectedYear);

        $minYear = min($computedDefaultYear - 5, $reports->min('year') ?? $computedDefaultYear);
        $maxYear = max($computedDefaultYear + 2, $reports->max('year') ?? $computedDefaultYear);
        $availableYears = collect(range($maxYear, $minYear));

        return view('activity-form.index', [
            'type' => $type,
            'title' => self::TITLES[$type],
            'selectedYear' => $selectedYear,
            'selectedReport' => $selectedReport,
            'availableYears' => $availableYears,
            'reports' => $reports,
        ]);
    }

    public function create(Request $request, string $type)
    {
        abort_unless(array_key_exists($type, self::TITLES), 404);

        $year = (int) $request->query('annee', $type === 'programme_budgetise' ? now()->year + 1 : now()->year);

        $report = Auth::user()->reports()
            ->where('type', $type)
            ->where('year', $year)
            ->with('budgetLines')
            ->first();

        if ($report && $report->status === 'valide') {
            return redirect()->route('activity-form.show', $report)
                ->with('status', 'Ce document a déjà été validé et ne peut plus être modifié.');
        }

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
            'lignes.*' => ['array', 'max:100'],
            'lignes.*.*.designation' => ['nullable', 'string', 'max:255'],
            'lignes.*.*.montant' => ['nullable', 'numeric', 'min:0'],
            'lignes.*.*.contribution_partenaires' => ['nullable', 'string', 'max:255'],
            'lignes.*.*.date' => ['nullable', 'date'],
            'lignes.*.*.observations' => ['nullable', 'string', 'max:255'],
        ]);

        $existing = Auth::user()->reports()
            ->where('type', $type)
            ->where('year', $data['year'])
            ->first();

        if ($existing && $existing->status === 'valide') {
            return redirect()->route('activity-form.show', $existing)
                ->with('status', 'Ce document a déjà été validé et ne peut plus être modifié.');
        }

        // Métadonnées du canevas en vigueur, indexées par code de sous-axe.
        $sousAxeIndex = collect(ActivityCanvasStructure::axes())
            ->flatMap(fn ($axe) => collect($axe['sous_axes'])->map(fn ($sousAxe) => [
                'code' => $sousAxe['code'],
                'canvas_sous_axe_id' => $sousAxe['id'],
                'axe' => $axe['code'],
                'axe_label' => $axe['label'],
                'sous_axe_label' => $sousAxe['label'],
            ]))
            ->keyBy('code');

        $report = Auth::user()->reports()->updateOrCreate(
            ['type' => $type, 'year' => $data['year']],
            ['status' => 'soumis']
        );

        $report->budgetLines()->delete();

        foreach ($data['lignes'] ?? [] as $sousAxeCode => $rows) {
            $meta = $sousAxeIndex->get($sousAxeCode);

            // Sous-axe supprimé du canevas entre l'affichage du formulaire et la soumission.
            if (! $meta) {
                continue;
            }

            $numero = 1;
            foreach ($rows as $input) {
                if (empty(array_filter($input))) {
                    continue;
                }

                $report->budgetLines()->create([
                    'canvas_sous_axe_id' => $meta['canvas_sous_axe_id'],
                    'axe' => $meta['axe'],
                    'axe_label' => $meta['axe_label'],
                    'sous_axe_code' => $sousAxeCode,
                    'sous_axe_label' => $meta['sous_axe_label'],
                    'numero_ligne' => $numero++,
                    'designation' => $input['designation'] ?? null,
                    'montant' => $input['montant'] ?? null,
                    'contribution_partenaires' => $input['contribution_partenaires'] ?? null,
                    'date' => $input['date'] ?? null,
                    'observations' => $input['observations'] ?? null,
                ]);
            }
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

        // Regroupement à partir des données propres au rapport (snapshot au moment
        // de la soumission), indépendant de la version actuelle du canevas.
        $sousAxeGroups = $report->budgetLines
            ->sortBy('numero_ligne')
            ->groupBy('sous_axe_code')
            ->map(fn ($lines, $sousAxeCode) => [
                'axe' => $lines->first()->axe,
                'axe_label' => $lines->first()->axe_label,
                'sous_axe_code' => $sousAxeCode,
                'sous_axe_label' => $lines->first()->sous_axe_label,
                'lines' => $lines->values(),
            ])
            ->sortBy(fn ($group) => $group['axe'].'|'.$group['sous_axe_code']);

        $axeGroups = $sousAxeGroups
            ->groupBy('axe_label')
            ->map(fn ($groups, $axeLabel) => [
                'label' => $axeLabel,
                'sous_axes' => $groups->values(),
            ])
            ->values();

        return view('activity-form.show', [
            'type' => $report->type,
            'title' => self::TITLES[$report->type],
            'report' => $report,
            'axeGroups' => $axeGroups,
        ]);
    }
}
