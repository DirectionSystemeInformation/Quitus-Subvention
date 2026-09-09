<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Support\ActivityCanvasStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ActivityFormController extends Controller
{
    private const TITLES = [
        'programme_budgetise' => "Programme d'activités budgétisé",
        'rapport_activite' => "Rapport d'activité",
        'programme_reamenage' => "Programme d'activités budgétisé réaménagé",
    ];

    public function index(Request $request)
    {
        $type = $request->query('type', 'rapport_activite');

        abort_unless(array_key_exists($type, self::TITLES), 404);

        $computedDefaultYear = in_array($type, ['programme_budgetise', 'programme_reamenage'], true) ? now()->year + 1 : now()->year;

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

        $request->validate(['annee' => ['nullable', 'integer', 'min:2000', 'max:2100']]);
        $year = (int) $request->old('year', $request->query('annee', in_array($type, ['programme_budgetise', 'programme_reamenage'], true) ? now()->year + 1 : now()->year));

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

        $validator = Validator::make($request->all(), [
            'action' => ['required', 'in:draft,submit'],
            'confirm_submission' => ['exclude_unless:action,submit', 'accepted'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'lignes' => ['nullable', 'array'],
            'lignes.*' => ['array', 'max:100'],
            'lignes.*.*' => ['array:designation,montant,contribution_partenaires,date,observations'],
            'lignes.*.*.designation' => ['nullable', 'string', 'max:255'],
            'lignes.*.*.montant' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'lignes.*.*.contribution_partenaires' => ['nullable', 'string', 'max:255'],
            'lignes.*.*.date' => ['nullable', 'date_format:Y-m-d'],
            'lignes.*.*.observations' => ['nullable', 'string', 'max:255'],
        ], [
            'action.required' => 'Choisissez « Enregistrer le brouillon » ou « Confirmer la soumission ».',
            'action.in' => 'L’action choisie est invalide. Rechargez le formulaire et réessayez.',
            'confirm_submission.accepted' => 'Vérifiez le récapitulatif puis cochez la confirmation avant de soumettre.',
            'year.*' => 'L’année doit être comprise entre 2000 et 2100.',
            'lignes.array' => 'Le format des activités est invalide.',
            'lignes.*.array' => 'Le format d’un sous-axe est invalide.',
            'lignes.*.max' => 'Un sous-axe peut contenir au maximum 100 activités.',
            'lignes.*.*.array' => 'Le format d’une activité est invalide.',
            'lignes.*.*.*.string' => ':attribute doit être un texte.',
            'lignes.*.*.*.max' => ':attribute dépasse la longueur ou le montant autorisé.',
            'lignes.*.*.montant.numeric' => ':attribute doit être un nombre en FCFA.',
            'lignes.*.*.montant.min' => ':attribute ne peut pas être négatif.',
            'lignes.*.*.montant.decimal' => ':attribute peut comporter au maximum deux décimales.',
            'lignes.*.*.date.date_format' => ':attribute doit être une date valide.',
        ]);

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

        $validator->setAttributeNames([
            'lignes.*.*.designation' => 'La désignation de l’activité',
            'lignes.*.*.montant' => 'Le montant de l’activité',
            'lignes.*.*.contribution_partenaires' => 'La contribution des partenaires',
            'lignes.*.*.date' => 'La date de l’activité',
            'lignes.*.*.observations' => 'Les observations',
        ]);
        $validator->after(function ($validator) use ($request, $sousAxeIndex) {
            $submittedLines = $request->input('lignes') ?? [];
            if (! is_array($submittedLines)) {
                return;
            }

            $filledCount = 0;
            foreach ($submittedLines as $code => $rows) {
                if (! $sousAxeIndex->has($code)) {
                    $validator->errors()->add('lignes', 'Le canevas a changé. Copiez vos activités avant de recharger le formulaire.');

                    continue;
                }
                if (! is_array($rows)) {
                    continue;
                }
                foreach ($rows as $index => $input) {
                    if (! ctype_digit((string) $index) || (int) $index < 1) {
                        $validator->errors()->add('lignes', 'Une ligne possède un numéro invalide. Rechargez le formulaire.');
                    }
                    if (! is_array($input) || ! $this->hasContent($input)) {
                        continue;
                    }
                    $filledCount++;
                    if ($request->input('action') === 'submit') {
                        if (! filled($input['designation'] ?? null)) {
                            $validator->errors()->add("lignes.$code.$index.designation", "Sous-axe $code, ligne $index : renseignez la désignation de l’activité.");
                        }
                        if (! filled($input['montant'] ?? null)) {
                            $validator->errors()->add("lignes.$code.$index.montant", "Sous-axe $code, ligne $index : renseignez le montant en FCFA (0 si aucun coût).");
                        }
                    }
                }
            }
            if ($request->input('action') === 'submit' && $filledCount === 0) {
                $validator->errors()->add('lignes', 'Ajoutez au moins une activité avec sa désignation et son montant avant de soumettre.');
            }
        });
        $data = $validator->validate();

        $report = DB::transaction(function () use ($data, $type, $sousAxeIndex) {
            // Le verrou de la fédération sérialise également la création du premier brouillon.
            Auth::user()->newQuery()->whereKey(Auth::id())->lockForUpdate()->firstOrFail();
            $report = Auth::user()->reports()->where('type', $type)->where('year', $data['year'])->lockForUpdate()->first();
            if ($report && $report->status === 'valide') {
                return ['report' => $report, 'locked' => true];
            }
            if ($report && $report->status === 'soumis' && $data['action'] === 'draft') {
                throw ValidationException::withMessages(['action' => 'Ce programme est déjà soumis. Vérifiez le récapitulatif et soumettez vos modifications ; il ne peut plus redevenir un brouillon.']);
            }
            $report = Auth::user()->reports()->updateOrCreate(
                ['type' => $type, 'year' => $data['year']],
                ['status' => $data['action'] === 'draft' ? 'brouillon' : 'soumis', 'rejection_reason' => null]
            );
            $report->budgetLines()->delete();

            foreach ($data['lignes'] ?? [] as $sousAxeCode => $rows) {
                $meta = $sousAxeIndex->get($sousAxeCode);
                $numero = 1;
                foreach ($rows as $input) {
                    if (! $this->hasContent($input)) {
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

            return ['report' => $report, 'locked' => false];
        });

        if ($report['locked']) {
            return redirect()->route('activity-form.show', $report['report'])
                ->with('status', 'Ce document a déjà été validé et ne peut plus être modifié.');
        }

        if ($data['action'] === 'draft') {
            return redirect()->route(str_replace('_', '-', $type).'.create', ['annee' => $data['year']])
                ->with('status', 'Brouillon enregistré. Vous pouvez reprendre votre saisie pour l’année '.$data['year'].'.');
        }

        return redirect()->route('dashboard', ['annee' => $data['year']])->with('status', self::TITLES[$type].' soumis avec succès à la DSHN.');
    }

    private function hasContent(array $input): bool
    {
        return collect($input)->contains(fn ($value) => filled($value));
    }

    public function show(Report $report)
    {
        abort_unless(array_key_exists($report->type, self::TITLES), 404);

        abort_unless(
            Auth::user()->id === $report->user_id || ($report->status !== 'brouillon' && Auth::user()->isDshn()),
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
