<?php

namespace Tests\Feature;

use App\Models\FederationActivity;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\Support\UiDatabase;
use Tests\TestCase;

/**
 * Fiche DGF d'une activité : la décision proposée dépend du statut et des
 * justificatifs, et la file de contrôle mène à l'activité suivante.
 */
class DgfActivityShowTest extends TestCase
{
    private User $federation;

    private User $dgf;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        UiDatabase::create();
        // Le schéma de test simplifié précède l'ajout de la période et de la date de soumission.
        Schema::table('federation_activities', function (Blueprint $table) {
            foreach (['date_debut', 'date_fin'] as $colonne) {
                if (! Schema::hasColumn('federation_activities', $colonne)) {
                    $table->date($colonne)->nullable();
                }
            }
            if (! Schema::hasColumn('federation_activities', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable();
            }
        });
        $this->federation = User::create(['name' => 'Président', 'email' => 'fede@example.test', 'password' => 'Test-only-password', 'role' => 'federation', 'status' => 'active', 'federation_name' => 'Fédération Burkinabè de Judo']);
        $this->dgf = User::create(['name' => 'Agent DGF', 'email' => 'dgf@example.test', 'password' => 'Test-only-password', 'role' => 'dgf', 'status' => 'active']);
    }

    private function activite(string $status, bool $avecPiece, array $attributs = []): FederationActivity
    {
        $activite = FederationActivity::create($attributs + [
            'user_id' => $this->federation->id, 'year' => 2026, 'status' => $status, 'designation' => 'Stage des arbitres',
            'axe' => 'II', 'axe_label' => 'Politique Nationale des Sports AXE-2 : Élite', 'sous_axe_code' => 'II.3', 'sous_axe_label' => 'Activités régionales',
            'montant' => 250000, 'submitted_at' => now()->subDay(),
        ]);
        if ($avecPiece) {
            $activite->documents()->create(['file_path' => 'activites/test.pdf', 'original_filename' => 'facture.pdf']);
        }

        return $activite;
    }

    public function test_une_activite_avec_justificatif_attend_une_decision_et_mene_a_la_suivante(): void
    {
        $activite = $this->activite('soumis', true);
        $suivante = $this->activite('soumis', true, ['designation' => 'Tournoi des jeunes']);

        $this->actingAs($this->dgf)->get(route('dgf.activities.show', $activite))
            ->assertOk()
            ->assertSee('Décision attendue')
            ->assertSee('Fédération Burkinabè de Judo')
            ->assertSee('Valider l’activité', false)
            ->assertSee('Rejeter avec un motif')
            ->assertSee(route('dgf.activities.show', $suivante), false)
            ->assertSee('2 activités attendent une décision');
    }

    public function test_sans_justificatif_la_validation_n_est_pas_proposee(): void
    {
        $activite = $this->activite('soumis', false);

        $this->actingAs($this->dgf)->get(route('dgf.activities.show', $activite))
            ->assertOk()
            ->assertSee('Justificatif manquant : validation impossible')
            ->assertSee('Rejeter avec un motif')
            ->assertDontSee('Valider l’activité', false);
    }

    public function test_une_activite_validee_indique_le_controleur(): void
    {
        $activite = $this->activite('valide', true, ['validated_at' => now(), 'validated_by' => $this->dgf->id]);

        $this->actingAs($this->dgf)->get(route('dgf.activities.show', $activite))
            ->assertOk()
            ->assertSee('Activité validée')
            ->assertSee('Validée par Agent DGF')
            ->assertDontSee('Rejeter avec un motif');
    }

    public function test_une_activite_rejetee_rappelle_le_motif(): void
    {
        $activite = $this->activite('rejete', true, ['rejection_reason' => 'Facture illisible']);

        $this->actingAs($this->dgf)->get(route('dgf.activities.show', $activite))
            ->assertOk()
            ->assertSee('en attente de correction par la fédération', false)
            ->assertSee('Facture illisible')
            ->assertDontSee('Valider l’activité', false);
    }
}
