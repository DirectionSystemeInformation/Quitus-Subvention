<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignAllocation;
use App\Models\CanvasAxe;
use App\Models\CanvasSousAxe;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Tests\Support\UiDatabase;
use Tests\TestCase;

/**
 * Formulaire de programme : un sous-axe sans activité reste vide, et le
 * programme réaménagé s'appuie sur la subvention accordée et sur le
 * programme budgétisé de la même année.
 */
class ProgrammeFormTest extends TestCase
{
    private User $federation;

    /** Année ouverte de droit pour les programmes : N+1. */
    private int $annee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->annee = now()->year + 1;
        URL::forceRootUrl('http://localhost');
        UiDatabase::create();

        $this->federation = User::create(['name' => 'Président', 'email' => 'fede@example.test', 'password' => 'Test-only-password', 'role' => 'federation', 'status' => 'active', 'federation_name' => 'Fédération de test']);
        $axe = CanvasAxe::create(['code' => 'I', 'label' => 'Politique Nationale des Sports AXE-1 : Masse', 'sort_order' => 1]);
        CanvasSousAxe::create(['canvas_axe_id' => $axe->id, 'code' => 'I.1', 'label' => 'Compétitions', 'lignes_count' => 1, 'sort_order' => 1]);
    }

    private function budgetise(): Report
    {
        $report = Report::create(['user_id' => $this->federation->id, 'type' => 'programme_budgetise', 'year' => $this->annee, 'status' => 'valide']);
        $report->budgetLines()->create(['axe' => 'I', 'sous_axe_code' => 'I.1', 'sous_axe_label' => 'Compétitions', 'numero_ligne' => 1, 'designation' => 'Championnat national', 'montant' => 900000]);

        return $report;
    }

    public function test_un_sous_axe_sans_activite_ne_propose_pas_de_ligne_vide(): void
    {
        $this->actingAs($this->federation)->get(route('programme-budgetise.create', ['annee' => $this->annee]))
            ->assertOk()
            ->assertSee('Ajouter une activité')
            ->assertDontSee('lignes[I.1][1][designation]', false)
            ->assertDontSee('Subvention accordée');
    }

    public function test_le_reamenage_affiche_la_subvention_et_propose_de_reprendre_le_budgetise(): void
    {
        $this->budgetise();
        $campaign = Campaign::create(['annee_n1' => $this->annee, 'etape' => 11]);
        CampaignAllocation::create(['campaign_id' => $campaign->id, 'user_id' => $this->federation->id, 'montant_final' => 750000]);

        $this->actingAs($this->federation)->get(route('programme-reamenage.create', ['annee' => $this->annee]))
            ->assertOk()
            ->assertSee('Subvention accordée')
            ->assertSee('data-subvention="750000"', false)
            ->assertSee('Reprendre le programme budgétisé')
            ->assertDontSee('Championnat national');
    }

    public function test_la_reprise_pre_remplit_le_reamenage_sans_rien_enregistrer(): void
    {
        $this->budgetise();

        $this->actingAs($this->federation)->get(route('programme-reamenage.create', ['annee' => $this->annee, 'reprendre' => 1]))
            ->assertOk()
            ->assertSee('activités reprises de votre programme budgétisé '.$this->annee, false)
            ->assertSee('value="Championnat national"', false)
            ->assertSee('value="900000"', false)
            ->assertSee('data-unsaved="true"', false);

        $this->assertFalse(Report::where('type', 'programme_reamenage')->exists());
    }
}
