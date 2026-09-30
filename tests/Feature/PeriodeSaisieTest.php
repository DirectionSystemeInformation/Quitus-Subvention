<?php

namespace Tests\Feature;

use App\Models\FederationActivity;
use App\Models\OuvertureSaisie;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\Support\UiDatabase;
use Tests\TestCase;

/**
 * Une fédération ne saisit ses activités que pour l'année en cours et ses
 * programmes que pour N+1 ; seule l'administration peut rouvrir une autre
 * année (ouverture exceptionnelle).
 */
class PeriodeSaisieTest extends TestCase
{
    private User $federation;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 9, 30));
        URL::forceRootUrl('http://localhost');
        UiDatabase::create();
        // Le schéma de test simplifié précède la période et la date de soumission.
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
        $this->admin = User::create(['name' => 'Administrateur', 'email' => 'admin@example.test', 'password' => 'Test-only-password', 'role' => 'admin', 'status' => 'active']);
        $axe = \App\Models\CanvasAxe::create(['code' => 'I', 'label' => 'Politique Nationale des Sports AXE-1 : Masse', 'sort_order' => 1]);
        \App\Models\CanvasSousAxe::create(['canvas_axe_id' => $axe->id, 'code' => 'I.1', 'label' => 'Compétitions', 'lignes_count' => 1, 'sort_order' => 1]);
    }

    private function activitePayload(int $annee): array
    {
        return ['year' => $annee, 'sous_axe_code' => 'I.1', 'designation' => 'Tournoi régional', 'montant' => '10000'];
    }

    private function ouvrir(string $type, int $annee, ?string $limite = null): OuvertureSaisie
    {
        return OuvertureSaisie::create(['user_id' => $this->federation->id, 'type' => $type, 'year' => $annee, 'motif' => 'Pièces reçues tardivement', 'expires_on' => $limite, 'granted_by' => $this->admin->id]);
    }

    public function test_une_activite_ne_peut_etre_declaree_que_pour_l_annee_en_cours(): void
    {
        $this->actingAs($this->federation)->post(route('activities.store'), $this->activitePayload(2025))
            ->assertSessionHasErrors('year');
        $this->assertSame(0, FederationActivity::count());

        $this->post(route('activities.store'), $this->activitePayload(2026))->assertSessionHasNoErrors();
        $this->assertSame(2026, FederationActivity::first()->year);
    }

    public function test_une_activite_d_une_annee_close_se_consulte_sans_modification(): void
    {
        $activite = FederationActivity::create(['user_id' => $this->federation->id, 'year' => 2025, 'status' => 'rejete', 'designation' => 'Stage 2025',
            'axe' => 'I', 'axe_label' => 'Politique Nationale des Sports AXE-1 : Masse', 'sous_axe_code' => 'I.1', 'sous_axe_label' => 'Compétitions']);

        $this->actingAs($this->federation)->get(route('activities.show', $activite))
            ->assertOk()->assertSee('Exercice 2025 clos')->assertDontSee('Modifier l’activité', false);
        $this->get(route('activities.edit', $activite))->assertRedirect(route('activities.show', $activite));
        $this->post(route('activities.submit', $activite))->assertSessionHasErrors('year');
        $this->assertSame('rejete', $activite->fresh()->status);
    }

    public function test_le_programme_ne_se_saisit_que_pour_n_plus_un(): void
    {
        $this->actingAs($this->federation)->get(route('programme-budgetise.create', ['annee' => 2027]))->assertOk()->assertSee('Année 2027');
        $this->get(route('programme-budgetise.create', ['annee' => 2026]))
            ->assertRedirect(route('documents.index', ['type' => 'programme_budgetise', 'annee' => 2026]));
        $this->get(route('documents.index', ['type' => 'programme_budgetise', 'annee' => 2026]))
            ->assertOk()->assertSee('2026 est close', false)->assertDontSee(route('programme-budgetise.create', ['annee' => 2026]), false);
        $this->post(route('programme-budgetise.store'), ['action' => 'draft', 'year' => 2028, 'form_loaded' => '1'])
            ->assertSessionHasErrors('year');
    }

    public function test_une_ouverture_de_l_administration_rouvre_l_annee_demandee(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.ouvertures.store', $this->federation), ['type' => 'activites', 'year' => 2025, 'motif' => 'Rapport 2025 incomplet'])
            ->assertRedirect();
        $this->assertDatabaseHas('ouvertures_saisie', ['user_id' => $this->federation->id, 'type' => 'activites', 'year' => 2025, 'granted_by' => $this->admin->id]);

        $this->actingAs($this->federation)->get(route('activities.create'))
            ->assertOk()->assertSee('Ouverture exceptionnelle pour 2025')->assertSee('<option value="2025"', false);
        $this->post(route('activities.store'), $this->activitePayload(2025))->assertSessionHasNoErrors();
        $this->assertSame(2025, FederationActivity::first()->year);

        // L'ouverture ne vaut que pour le type accordé.
        $this->get(route('programme-budgetise.create', ['annee' => 2025]))
            ->assertRedirect(route('documents.index', ['type' => 'programme_budgetise', 'annee' => 2025]));
    }

    public function test_une_ouverture_expiree_ou_refermee_ne_vaut_plus(): void
    {
        $this->ouvrir('activites', 2024, now()->subDay()->toDateString());
        $ouverture = $this->ouvrir('programme_budgetise', 2025);

        $this->actingAs($this->federation)->post(route('activities.store'), $this->activitePayload(2024))->assertSessionHasErrors('year');
        $this->get(route('programme-budgetise.create', ['annee' => 2025]))->assertOk();

        $this->actingAs($this->admin)->delete(route('admin.ouvertures.destroy', $ouverture))->assertRedirect();
        $this->actingAs($this->federation)->get(route('programme-budgetise.create', ['annee' => 2025]))
            ->assertRedirect(route('documents.index', ['type' => 'programme_budgetise', 'annee' => 2025]));
    }

    public function test_seule_l_administration_peut_ouvrir_une_annee(): void
    {
        $dshn = User::create(['name' => 'Agent DSHN', 'email' => 'dshn@example.test', 'password' => 'Test-only-password', 'role' => 'dshn', 'status' => 'active']);

        $this->actingAs($dshn)->post(route('admin.ouvertures.store', $this->federation), ['type' => 'activites', 'year' => 2025, 'motif' => 'Test'])
            ->assertForbidden();
        $this->actingAs($this->federation)->post(route('admin.ouvertures.store', $this->federation), ['type' => 'activites', 'year' => 2025, 'motif' => 'Test'])
            ->assertForbidden();
        // L'année ouverte de droit n'a pas besoin d'ouverture.
        $this->actingAs($this->admin)->post(route('admin.ouvertures.store', $this->federation), ['type' => 'activites', 'year' => 2026, 'motif' => 'Test'])
            ->assertSessionHasErrors('year');
        $this->assertSame(0, OuvertureSaisie::count());
    }
}
