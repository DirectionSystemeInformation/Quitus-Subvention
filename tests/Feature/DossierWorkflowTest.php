<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignAllocation;
use App\Models\CanvasAxe;
use App\Models\CanvasSousAxe;
use App\Models\FederationActivity;
use App\Models\Report;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\Support\UiDatabase;
use Tests\TestCase;

class DossierWorkflowTest extends TestCase
{
    private User $federation;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        UiDatabase::create();
        $this->federation = $this->user('federation');
        $axe = CanvasAxe::create(['code' => 'I', 'label' => 'Activités sportives', 'sort_order' => 1]);
        CanvasSousAxe::create(['canvas_axe_id' => $axe->id, 'code' => 'I.1', 'label' => 'Compétitions', 'lignes_count' => 1, 'sort_order' => 1]);
    }

    private function user(string $role): User
    {
        return User::create(['name' => $role, 'email' => uniqid().'@example.test', 'password' => 'Test-only-password', 'role' => $role, 'status' => 'active', 'federation_name' => 'Fédération de test']);
    }

    private function payload(string $action = 'draft'): array
    {
        return ['action' => $action, 'year' => 2027, 'form_loaded' => '1', 'confirm_submission' => '1', 'lignes' => ['I.1' => [1 => ['designation' => 'Tournoi régional', 'montant' => '150000']]]];
    }

    public function test_draft_can_be_saved_reopened_and_submitted(): void
    {
        $this->actingAs($this->federation)->post(route('programme-budgetise.store'), $this->payload())
            ->assertSessionHasNoErrors()->assertRedirect(route('programme-budgetise.create', ['annee' => 2027]));
        $report = Report::firstOrFail();
        $this->assertSame('brouillon', $report->status);
        $this->assertSame('150000.00', $report->budgetLines->first()->montant);
        $this->get(route('programme-budgetise.create', ['annee' => 2027]))->assertOk()->assertSee('Tournoi régional')->assertSee('Enregistrer le brouillon');
        $this->post(route('programme-budgetise.store'), $this->payload('submit'))->assertSessionHasNoErrors();
        $this->assertSame('soumis', $report->fresh()->status);
        $this->assertSame(1, Report::count());
    }

    public function test_submission_requires_confirmation_and_nonempty_named_activities(): void
    {
        $payload = $this->payload('submit');
        unset($payload['confirm_submission']);
        $this->actingAs($this->federation)->post(route('programme-budgetise.store'), $payload)->assertSessionHasErrors('confirm_submission');
        $payload['confirm_submission'] = '1';
        $payload['lignes'] = [];
        $this->post(route('programme-budgetise.store'), $payload)->assertSessionHasErrors('lignes');
        $payload['lignes'] = ['I.1' => [1 => ['montant' => '0']]];
        $this->post(route('programme-budgetise.store'), $payload)->assertSessionHasErrors('lignes.I.1.1.designation');
        $this->assertSame(0, Report::count());
    }

    public function test_zero_cost_is_valid_and_partial_draft_is_allowed(): void
    {
        $payload = $this->payload();
        $payload['lignes']['I.1'][1] = ['montant' => '0'];
        $this->actingAs($this->federation)->post(route('programme-budgetise.store'), $payload)->assertSessionHasNoErrors();
        $this->assertSame('0.00', Report::first()->budgetLines->first()->montant);
        $payload['action'] = 'submit';
        $payload['lignes']['I.1'][1]['designation'] = 'Rencontre sans coût';
        $this->post(route('programme-budgetise.store'), $payload)->assertSessionHasNoErrors();
    }

    public function test_failed_validation_preserves_dynamic_rows_with_dotted_subaxis_codes(): void
    {
        $payload = $this->payload();
        $payload['lignes']['I.1'][9] = ['designation' => 'Neuvième ligne conservée', 'montant' => '-1'];
        $url = route('programme-budgetise.create', ['annee' => 2027]);
        $this->actingAs($this->federation)->from($url)->post(route('programme-budgetise.store'), $payload)->assertSessionHasErrors();
        $this->get($url)->assertOk()->assertSee('Neuvième ligne conservée')->assertSee('lignes[I.1][9][designation]', false)->assertSee('value="-1"', false);
        $this->assertSame(0, Report::count());
    }

    public function test_submitted_document_cannot_be_silently_withdrawn_to_draft(): void
    {
        $this->actingAs($this->federation)->post(route('programme-budgetise.store'), $this->payload('submit'))->assertSessionHasNoErrors();
        $this->post(route('programme-budgetise.store'), $this->payload())->assertSessionHasErrors('action');
        $this->assertSame('soumis', Report::first()->status);
    }

    public function test_failed_line_write_rolls_back_the_previous_programme(): void
    {
        $this->actingAs($this->federation)->post(route('programme-budgetise.store'), $this->payload())->assertSessionHasNoErrors();
        DB::unprepared("CREATE TRIGGER fail_new_line BEFORE INSERT ON budget_lines BEGIN SELECT RAISE(ABORT, 'test interruption'); END;");
        $payload = $this->payload();
        $payload['lignes']['I.1'][1]['designation'] = 'Modification interrompue';
        $this->post(route('programme-budgetise.store'), $payload)->assertServerError();
        $this->assertSame('Tournoi régional', Report::first()->budgetLines->first()->designation);
    }

    public function test_dshn_cannot_read_or_validate_a_federation_draft(): void
    {
        $this->actingAs($this->federation)->post(route('programme-budgetise.store'), $this->payload());
        $draft = Report::firstOrFail();
        $this->actingAs($this->user('dshn'));
        $this->get(route('activity-form.show', $draft))->assertForbidden();
        $this->post(route('dshn.reports.validate', $draft))->assertForbidden();
        $this->post(route('dshn.reports.reject', $draft), ['rejection_reason' => 'test'])->assertForbidden();
        $this->get(route('dshn.reports.index'))->assertOk()->assertViewHas('reports', fn ($reports) => $reports->isEmpty());
        $this->get(route('dshn.search.index', ['q' => 'programme']))->assertOk()->assertViewHas('reports', fn ($reports) => $reports->isEmpty());
        $this->assertSame('brouillon', $draft->fresh()->status);
    }

    public function test_quitus_download_is_available_only_to_owner_or_dshn_after_delivery(): void
    {
        $campaign = Campaign::create(['annee_n1' => 2027]);
        $allocation = CampaignAllocation::create(['campaign_id' => $campaign->id, 'user_id' => $this->federation->id]);
        $url = route('campagnes.quitus.download', [$campaign, $this->federation]);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->federation)->get($url)->assertNotFound();
        $allocation->update(['quitus_delivered_at' => now(), 'quitus_reference' => 'TEST-2027']);
        $this->actingAs($this->user('federation'))->get($url)->assertForbidden();
        $this->actingAs($this->user('dgf'))->get($url)->assertForbidden();
        Pdf::shouldReceive('loadView')->twice()->andReturnSelf();
        Pdf::shouldReceive('download')->twice()->andReturn(response('test-pdf', 200, ['Content-Type' => 'application/pdf']));
        $this->actingAs($this->federation)->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->user('dshn'))->get($url)->assertOk();
        $this->actingAs($this->federation)->post(route('campagnes.quitus.deliver', [$campaign, $this->federation]))->assertForbidden();
    }

    public function test_dashboard_exposes_draft_resume_and_the_delivered_quitus(): void
    {
        Report::create(['user_id' => $this->federation->id, 'type' => 'rapport_activite', 'year' => 2026, 'status' => 'valide']);
        Report::create(['user_id' => $this->federation->id, 'type' => 'programme_budgetise', 'year' => 2027, 'status' => 'brouillon']);
        $this->actingAs($this->federation)->get(route('dashboard', ['annee' => 2027]))->assertOk()->assertSee('Reprendre mon brouillon');
        $campaign = Campaign::create(['annee_n1' => 2027, 'etape' => 12]);
        CampaignAllocation::create(['campaign_id' => $campaign->id, 'user_id' => $this->federation->id, 'quitus_delivered_at' => now()]);
        $this->get(route('dashboard', ['annee' => 2027]))->assertOk()->assertSee('Votre quitus est disponible')->assertSee(route('campagnes.quitus.download', [$campaign, $this->federation]), false);
    }

    public function test_dgf_queue_filters_both_status_and_year(): void
    {
        foreach ([['soumis', 2026, 'À traiter'], ['brouillon', 2026, 'En préparation'], ['soumis', 2025, 'Ancienne activité']] as [$status, $year, $designation]) {
            FederationActivity::create(['user_id' => $this->federation->id, 'year' => $year, 'status' => $status, 'designation' => $designation]);
        }
        $this->actingAs($this->user('dgf'))->get(route('dgf.activities.index', ['annee' => 2026]))
            ->assertOk()->assertSee('À traiter')->assertDontSee('En préparation')->assertDontSee('Ancienne activité');
        $this->get(route('dgf.activities.index', ['annee' => 2026, 'statut' => 'brouillon']))
            ->assertOk()->assertSee('En préparation')->assertDontSee('À traiter');
    }

    public function test_activity_report_links_back_to_activities_instead_of_removed_form_route(): void
    {
        $report = Report::create(['user_id' => $this->federation->id, 'type' => 'rapport_activite', 'year' => 2026, 'status' => 'soumis']);
        $this->actingAs($this->federation)->get(route('activity-form.show', $report))->assertOk()->assertSee('Gérer les activités du rapport');
    }

    public function test_validated_programme_is_not_overwritten(): void
    {
        $this->actingAs($this->federation)->post(route('programme-budgetise.store'), $this->payload());
        $report = Report::firstOrFail();
        $report->update(['status' => 'valide']);
        $payload = $this->payload('submit');
        $payload['lignes']['I.1'][1]['designation'] = 'Remplacement interdit';
        $this->post(route('programme-budgetise.store'), $payload)->assertRedirect(route('activity-form.show', $report));
        $this->assertSame('Tournoi régional', $report->fresh()->budgetLines->first()->designation);
    }

    public function test_dashboard_does_not_claim_arbitration_without_an_allocation(): void
    {
        foreach (['rapport_activite' => 2026, 'programme_budgetise' => 2027] as $type => $year) {
            Report::create(['user_id' => $this->federation->id, 'type' => $type, 'year' => $year, 'status' => 'valide']);
        }
        Campaign::create(['annee_n1' => 2027, 'etape' => 11]);
        $this->actingAs($this->federation)->get(route('dashboard', ['annee' => 2027]))->assertOk()
            ->assertSee('Votre participation à la campagne reste à confirmer')->assertDontSee('Votre programme réaménagé est attendu');
    }
}
