<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\CampaignAllocation;
use App\Models\CanvasSousAxe;
use App\Models\FederationActivity;
use App\Models\Report;
use App\Models\User;
use App\Support\PonderationCriteria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Peuple la base avec un jeu de données de démonstration réaliste : comptes de
 * chaque rôle du circuit budgétaire, une trentaine de fédérations sportives
 * burkinabè à divers stades (en attente, actives avec historique plus ou moins
 * riche, rejetées), et des campagnes de répartition à différentes étapes.
 *
 * Volontairement non idempotent au-delà d'une garde de comptage : à lancer une
 * seule fois sur une base de test/démo, jamais en production.
 */
class DemoDataSeeder extends Seeder
{
    private const PASSWORD = 'Demo@2026';

    /** @var array<int, CanvasSousAxe> indexé par code de sous-axe */
    private array $sousAxes = [];

    private array $designations = [
        'I' => [
            'Organisation du championnat national des jeunes',
            'Championnat de la relève sportive',
            'Détection et suivi des jeunes talents',
            'Camp de vacances sportif',
            'Championnat scolaire et universitaire',
            'Journée nationale de masse sportive',
        ],
        'II' => [
            "Championnat national d'élite 1ère division",
            "Championnat national d'élite 2ème division",
            'Stage de préparation de l’équipe nationale',
            'Participation aux éliminatoires continentales',
            'Participation aux Jeux Africains',
            'Participation au championnat du monde',
            'Tournoi sous-régional UFOA/CEDEAO',
        ],
        'III' => [
            'Réhabilitation des infrastructures sportives',
            'Acquisition de matériel et équipements sportifs',
            'Fonctionnement du secrétariat général',
            'Assemblée générale ordinaire',
            'Réunion du bureau exécutif fédéral',
            'Formation des arbitres et officiels',
            'Formation des entraîneurs et cadres techniques',
            'Cotisation à la fédération internationale',
            'Cotisation au CNOSB',
        ],
    ];

    private array $montantRanges = [
        'I' => [500_000, 3_000_000],
        'II' => [2_000_000, 15_000_000],
        'III' => [300_000, 5_000_000],
    ];

    private array $newAccounts = [];

    public function run(): void
    {
        if (User::where('role', 'federation')->count() >= 15) {
            $this->command?->warn('Des fédérations de démonstration semblent déjà présentes — seeding ignoré.');

            return;
        }

        $this->sousAxes = CanvasSousAxe::with('axe')->get()->keyBy('code')->all();

        $dshn = User::where('role', 'dshn')->first() ?? $this->staff('dshn', 'Agent DSHN', 'dshn@sports.gov.bf');
        $admin = User::where('role', 'admin')->first();
        $dg = $this->staff('dg', 'Salif Zerbo', 'dg@sports.gov.bf');
        $comite = $this->staff('comite_arbitrage', "Comité d'Arbitrage Budgétaire", 'comite.arbitrage@sports.gov.bf');
        $ministre = $this->staff('ministre', 'Le Ministre des Sports, de la Jeunesse et de l’Emploi', 'ministre@sports.gov.bf');
        $dgf = $this->staff('dgf', 'Aïcha Konaté', 'dgf@sports.gov.bf');
        $dshn2 = $this->staff('dshn', 'Boureima Sawadogo', 'dshn2@sports.gov.bf');

        $arreteCounter = 1001;
        $nextArrete = function () use (&$arreteCounter) {
            do {
                $numero = sprintf('%04d/Sport/MSJE', $arreteCounter++);
            } while (in_array($numero, ['1002/Sport/MSJE', '1012/Sport/MSJE', '1022/Sport/MSJE'], true));

            return $numero;
        };

        // --- Fédérations déjà présentes : on les enrichit sans toucher à leur identité.
        $existing = User::where('role', 'federation')->whereIn('email', [
            'floorball@sports.gov.bf', 'volley@sports.gov.bf', 'handball@sports.gov.bf',
        ])->get()->keyBy('email');

        $richFederations = [
            'Fédération Burkinabè de Football',
            "Fédération Burkinabè d'Athlétisme",
            'Fédération Burkinabè de Basketball',
            'Fédération Burkinabè de Judo et Disciplines Associées',
            'Fédération Burkinabè de Karaté et Disciplines Assimilées',
            'Fédération Burkinabè de Cyclisme',
            'Fédération Burkinabè de Taekwondo',
            'Fédération Burkinabè de Boxe',
        ];

        $mediumFederations = [
            'Fédération Burkinabè de Rugby',
            'Fédération Burkinabè de Tennis',
            'Fédération Burkinabè de Tennis de Table',
            'Fédération Burkinabè de Natation et Disciplines Associées',
            'Fédération Burkinabè de Badminton',
            'Fédération Burkinabè des Luttes Associées',
            'Fédération Burkinabè de Golf',
        ];

        $lightFederations = [
            'Fédération Burkinabè de Pétanque et Jeu Provençal',
            'Fédération Burkinabè de Gymnastique',
            'Fédération Burkinabè des Échecs',
            'Fédération Burkinabè du Sport pour Tous',
            'Fédération Burkinabè de Kickboxing, Muaythaï et Disciplines Assimilées',
            'Fédération Burkinabè de Tir Sportif',
            'Fédération Burkinabè Handisport',
        ];

        $pendingFederations = [
            'Fédération Burkinabè de Cricket',
            "Fédération Burkinabè de Tir à l'Arc",
            'Fédération Burkinabè de Voile et Sports Nautiques',
        ];

        $rejectedFederations = [
            'Fédération Burkinabè de Baseball et Softball' => "Statuts non conformes aux textes de l'UEMOA/CNOSB. Merci de les mettre à jour et de soumettre à nouveau la demande.",
            'Fédération Burkinabè du Sport Scolaire et Universitaire' => "Dossier incomplet : procès-verbal de l'assemblée générale constitutive manquant.",
        ];

        // --- Création des comptes fédération.
        $richUsers = [];
        foreach ($richFederations as $i => $name) {
            $richUsers[] = $this->createFederation($name, $nextArrete());
        }

        $mediumUsers = array_values($existing->all());
        foreach ($mediumFederations as $name) {
            $mediumUsers[] = $this->createFederation($name, $nextArrete());
        }

        foreach ($lightFederations as $name) {
            $this->createFederation($name, $nextArrete());
        }

        foreach ($pendingFederations as $name) {
            $this->createFederation($name, $nextArrete(), 'pending');
        }

        foreach ($rejectedFederations as $name => $reason) {
            $this->createFederation($name, $nextArrete(), 'rejected', $reason);
        }

        // --- Historique documentaire.
        // Fédérations "riches" : dossier complet sur 2024-2027 + campagnes 2025 (terminée)
        // et 2026 (en cours). La moitié seulement est déjà éligible à la campagne 2027.
        foreach ($richUsers as $i => $fed) {
            $this->buildDirectReport($fed, 'programme_budgetise', 2025, 'valide');
            $this->buildDirectReport($fed, 'programme_reamenage', 2025, 'valide');
            $this->buildActivityYear($fed, 2024, valide: 13, soumis: 0, brouillon: 0, dgf: $dgf, dshnValidates: true);
            $this->buildActivityYear($fed, 2025, valide: 13, soumis: 0, brouillon: 0, dgf: $dgf, dshnValidates: true);
            $this->buildDirectReport($fed, 'programme_budgetise', 2026, 'valide');

            if ($i % 2 === 0) {
                $this->buildActivityYear($fed, 2026, valide: 13, soumis: 0, brouillon: 0, dgf: $dgf, dshnValidates: true);
                $this->buildDirectReport($fed, 'programme_budgetise', 2027, 'valide');
            } else {
                $this->buildActivityYear($fed, 2026, valide: 9, soumis: 3, brouillon: 1, dgf: $dgf, dshnValidates: false);
                $this->buildDirectReport($fed, 'programme_budgetise', 2027, 'soumis');
            }
        }

        // --- Fédérations "moyennes" : un dossier 2026/2027 en cours, jamais encore
        // intégrées à une campagne — pour tester la file d'attente DSHN/DGF.
        $mediumStatuses = ['valide', 'soumis', 'brouillon', 'rejete'];
        foreach ($mediumUsers as $i => $fed) {
            $status = $mediumStatuses[$i % count($mediumStatuses)];
            $reason = $status === 'rejete' ? 'Le montant total dépasse le plafond autorisé pour cette rubrique.' : null;
            $this->buildDirectReport($fed, 'programme_budgetise', 2026, $status, $reason);
            $this->buildActivityYear($fed, 2025, valide: 0, soumis: 5, brouillon: 3, dgf: $dgf, dshnValidates: false);
        }

        // --- Campagne 2025 : cycle complet et terminé (avec quitus) pour les 8
        // fédérations riches.
        $campaign2025 = Campaign::create([
            'annee_n1' => 2025,
            'etape' => 12,
            'statut' => 'en_cours',
            'dg_decision' => 'valide',
            'dg_decided_at' => now()->subMonths(10),
            'ministre_decision' => 'valide',
            'ministre_decided_at' => now()->subMonths(9),
            'session_arbitrage_date' => now()->subMonths(8)->toDateString(),
            'session_arbitrage_organized_at' => now()->subMonths(8),
            'bareme_repartition' => $this->defaultBareme(),
        ]);

        foreach ($richUsers as $fed) {
            $allocation = $this->allocateWithScores($campaign2025, $fed);
            $montant = $this->defaultBareme()[$allocation->categorie_ajustee] ?? 3_000_000;
            $allocation->update([
                'montant_propose' => $montant,
                'montant_arbitre' => $montant,
                'montant_final' => $montant,
                'quitus_delivered_at' => now()->subMonths(6),
                'quitus_reference' => 'QUITUS-2025-'.Str::padLeft((string) $fed->id, 4, '0'),
            ]);
        }
        $campaign2025->update(['statut' => 'termine']);

        // --- Campagne 2026 : arbitrage budgétaire en cours (étape 8). Le DG a déjà
        // validé, le Comité d'arbitrage a commencé à ajuster les montants pour la
        // moitié des fédérations retenues — l'autre moitié attend encore son passage.
        $campaign2026 = Campaign::create([
            'annee_n1' => 2026,
            'etape' => 8,
            'statut' => 'en_cours',
            'dg_decision' => 'valide',
            'dg_decided_at' => now()->subWeeks(3),
            'bareme_repartition' => $this->defaultBareme(),
        ]);

        foreach ($richUsers as $i => $fed) {
            $allocation = $this->allocateWithScores($campaign2026, $fed);
            $montant = $this->defaultBareme()[$allocation->categorie_ajustee] ?? 3_000_000;
            $allocation->update(['montant_propose' => $montant]);

            if ($i % 2 === 0) {
                $allocation->update(['montant_arbitre' => $montant]);
            }
        }

        // --- Campagne 2027 : vient d'être ouverte (étape 3, traitement des rapports).
        // Aucune allocation encore — c'est au DSHN de lancer la pondération depuis
        // l'écran "Campagnes" une fois les dossiers 2026/2027 validés.
        Campaign::create([
            'annee_n1' => 2027,
            'etape' => 3,
            'statut' => 'en_cours',
        ]);

        $this->report();
    }

    private function staff(string $role, string $name, string $email): User
    {
        $existing = User::where('email', $email)->first();
        if ($existing) {
            return $existing;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(self::PASSWORD),
            'role' => $role,
            'status' => 'active',
        ]);

        $this->newAccounts[] = ['role' => $role, 'name' => $name, 'email' => $email, 'password' => self::PASSWORD];

        return $user;
    }

    private function createFederation(string $denomination, string $arreteNumero, string $status = 'active', ?string $rejectionReason = null): User
    {
        $short = preg_replace('/^Fédération Burkinab\S*\s*(de\s+|d\'|des\s+|du\s+)?/u', '', $denomination);
        $slug = Str::slug(Str::before($short, ',')) ?: Str::slug($denomination);

        $email = "contact@{$slug}.bf";
        $suffix = 1;
        while (User::where('email', $email)->exists()) {
            $email = "contact{$suffix}@{$slug}.bf";
            $suffix++;
        }

        $user = User::create([
            'name' => $denomination,
            'federation_name' => $denomination,
            'email' => $email,
            'arrete_numero' => $arreteNumero,
            'arrete_date' => now()->subDays(rand(365, 365 * 8))->toDateString(),
            'password' => Hash::make(self::PASSWORD),
            'role' => 'federation',
            'status' => $status,
            'rejection_reason' => $rejectionReason,
        ]);

        $this->newAccounts[] = ['role' => 'federation', 'name' => $denomination, 'email' => $email, 'password' => self::PASSWORD, 'status' => $status];

        return $user;
    }

    /**
     * Crée directement un rapport (programme budgétisé ou réaménagé) avec ses
     * lignes budgétaires — flux réel de saisie via le canevas, sans passer par
     * les activités.
     */
    private function buildDirectReport(User $fed, string $type, int $year, string $status, ?string $rejectionReason = null): Report
    {
        $report = Report::create([
            'user_id' => $fed->id,
            'type' => $type,
            'year' => $year,
            'status' => $status,
            'rejection_reason' => $rejectionReason,
        ]);

        foreach ($this->sousAxes as $sousAxe) {
            $axeCode = $sousAxe->axe->code;

            $report->budgetLines()->create([
                'canvas_sous_axe_id' => $sousAxe->id,
                'axe' => $axeCode,
                'axe_label' => $sousAxe->axe->label,
                'sous_axe_code' => $sousAxe->code,
                'sous_axe_label' => $sousAxe->label,
                'numero_ligne' => 1,
                'designation' => $this->pickDesignation($axeCode),
                'montant' => $this->randomMontant($axeCode),
                'contribution_partenaires' => rand(0, 1) ? number_format(rand(50_000, 500_000), 0, ',', ' ').' FCFA (partenaire technique)' : null,
                'date' => now()->setDate($year, rand(1, 12), rand(1, 28))->toDateString(),
                'observations' => null,
            ]);
        }

        return $report;
    }

    /**
     * Simule le cycle "activités" d'une année pour une fédération : crée des
     * FederationActivity dans les 3 statuts demandés, valide celles qui doivent
     * l'être (transaction identique à Dgf\ActivityController::validate_, avec une
     * pièce justificative réelle sur le disque), puis, si demandé, fait passer le
     * rapport d'activité résultant au statut "valide" (validation DSHN).
     */
    private function buildActivityYear(User $fed, int $year, int $valide, int $soumis, int $brouillon, User $dgf, bool $dshnValidates): void
    {
        $sousAxesList = array_values($this->sousAxes);
        $total = $valide + $soumis + $brouillon;
        $report = null;

        for ($i = 0; $i < $total; $i++) {
            $sousAxe = $sousAxesList[$i % count($sousAxesList)];
            $axeCode = $sousAxe->axe->code;

            $targetStatus = $i < $valide ? 'valide' : ($i < $valide + $soumis ? 'soumis' : 'brouillon');

            $activity = FederationActivity::create([
                'user_id' => $fed->id,
                'canvas_sous_axe_id' => $sousAxe->id,
                'axe' => $axeCode,
                'axe_label' => $sousAxe->axe->label,
                'sous_axe_code' => $sousAxe->code,
                'sous_axe_label' => $sousAxe->label,
                'year' => $year,
                'designation' => $this->pickDesignation($axeCode),
                'montant' => $this->randomMontant($axeCode),
                'contribution_partenaires' => null,
                'date' => now()->setDate($year, rand(1, 12), rand(1, 28))->toDateString(),
                'observations' => null,
                'status' => $targetStatus === 'brouillon' ? 'brouillon' : 'soumis',
            ]);

            if ($targetStatus === 'brouillon') {
                continue;
            }

            $path = "activites/{$activity->id}/piece-justificative.pdf";
            Storage::disk('local')->put($path, "Piece justificative demo - {$fed->federation_name} - {$activity->designation}");
            $activity->documents()->create([
                'file_path' => $path,
                'original_filename' => 'piece-justificative.pdf',
            ]);

            if ($targetStatus === 'soumis') {
                continue;
            }

            // --- Validation DGF (réplique la transaction du contrôleur réel).
            DB::transaction(function () use ($activity, $fed, $year, $dgf, &$report) {
                $report = Report::updateOrCreate(
                    ['user_id' => $fed->id, 'type' => 'rapport_activite', 'year' => $year],
                    ['status' => 'soumis', 'rejection_reason' => null]
                );

                $nextNumero = $report->budgetLines()->where('sous_axe_code', $activity->sous_axe_code)->max('numero_ligne');

                $budgetLine = $report->budgetLines()->create([
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
                ]);

                $activity->update([
                    'status' => 'valide',
                    'validated_by' => $dgf->id,
                    'validated_at' => now(),
                    'budget_line_id' => $budgetLine->id,
                ]);
            });
        }

        if ($dshnValidates && $report) {
            $report->update(['status' => 'valide']);
        }
    }

    private function allocateWithScores(Campaign $campaign, User $fed): CampaignAllocation
    {
        $scores = [];
        foreach (PonderationCriteria::criteres() as $critere) {
            // Score réaliste : entre 55 % et 100 % du maximum du critère.
            $scores[$critere['slug']] = round($critere['max'] * (rand(55, 100) / 100), 1);
        }

        $allocation = CampaignAllocation::create([
            'campaign_id' => $campaign->id,
            'user_id' => $fed->id,
            'criteres_scores' => $scores,
        ]);

        $allocation->recalculerScores();
        $allocation->save();

        return $allocation;
    }

    private function defaultBareme(): array
    {
        // Barème de répartition indicatif, croissant avec la catégorie ajustée.
        $paliers = PonderationCriteria::paliersCategorieAjustee();
        $bareme = [];
        foreach ($paliers as $i => $palier) {
            $bareme[$palier] = 1_000_000 + ($i * 900_000);
        }
        $bareme['NON_CLASSEE'] = 0;

        return $bareme;
    }

    private function pickDesignation(string $axeCode): string
    {
        $pool = $this->designations[$axeCode] ?? $this->designations['III'];

        return $pool[array_rand($pool)];
    }

    private function randomMontant(string $axeCode): int
    {
        [$min, $max] = $this->montantRanges[$axeCode] ?? $this->montantRanges['III'];

        return (int) (round(rand($min, $max) / 10_000) * 10_000);
    }

    private function report(): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->info('');
        $this->command->info('Comptes créés (mot de passe entre parenthèses) :');
        foreach ($this->newAccounts as $account) {
            $extra = isset($account['status']) ? " [{$account['status']}]" : '';
            $this->command->line("- {$account['role']} : {$account['email']} ({$account['password']}){$extra} — {$account['name']}");
        }
    }
}
