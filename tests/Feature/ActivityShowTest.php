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
 * Fiche d'une activité : la prochaine étape et les actions proposées
 * dépendent du statut et de la présence de pièces justificatives.
 */
class ActivityShowTest extends TestCase
{
    private User $federation;

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
        $this->federation = User::create(['name' => 'Président', 'email' => 'fede@example.test', 'password' => 'Test-only-password', 'role' => 'federation', 'status' => 'active', 'federation_name' => 'Fédération de test']);
    }

    private function activite(string $status, array $attributs = []): FederationActivity
    {
        return FederationActivity::create($attributs + [
            'user_id' => $this->federation->id, 'year' => now()->year, 'status' => $status, 'designation' => 'Organiser une marche',
            'axe' => 'I', 'axe_label' => 'Politique Nationale des Sports AXE-1 : Masse', 'sous_axe_code' => 'I.1', 'sous_axe_label' => 'Compétitions',
            'montant' => 10000, 'date_debut' => '2026-09-12', 'date_fin' => '2026-09-26',
        ]);
    }

    public function test_une_activite_soumise_sans_piece_demande_un_justificatif(): void
    {
        $activite = $this->activite('soumis', ['submitted_at' => now()]);

        $this->actingAs($this->federation)->get(route('activities.show', $activite))
            ->assertOk()
            ->assertSee('Action requise')
            ->assertSee('Ajoutez un justificatif')
            ->assertSee('En attente d’un justificatif', false)
            ->assertSee('Du 12 au 26 septembre 2026')
            ->assertDontSee('Supprimer l’activité', false);
    }

    public function test_un_brouillon_propose_de_soumettre_et_de_supprimer(): void
    {
        $activite = $this->activite('brouillon');

        $this->actingAs($this->federation)->get(route('activities.show', $activite))
            ->assertOk()
            ->assertSee('Prochaine étape')
            ->assertSee('Soumettre à la DGF')
            ->assertSee('Supprimer l’activité', false);
    }

    public function test_une_activite_rejetee_affiche_le_motif_et_la_correction(): void
    {
        $activite = $this->activite('rejete', ['rejection_reason' => 'Pièce illisible', 'submitted_at' => now()]);

        $this->actingAs($this->federation)->get(route('activities.show', $activite))
            ->assertOk()
            ->assertSee('Corrections demandées par la DGF')
            ->assertSee('Pièce illisible')
            ->assertSee('Corriger l’activité', false)
            ->assertSee('Soumettre de nouveau');
    }

    public function test_une_activite_validee_ne_propose_plus_d_action(): void
    {
        $activite = $this->activite('valide', ['submitted_at' => now(), 'validated_at' => now()]);

        $this->actingAs($this->federation)->get(route('activities.show', $activite))
            ->assertOk()
            ->assertSee('Activité validée par la DGF')
            ->assertSee('ne peut plus être modifiée', false)
            ->assertDontSee('Modifier l’activité', false)
            ->assertDontSee('name="pieces[]"', false);
    }
}
