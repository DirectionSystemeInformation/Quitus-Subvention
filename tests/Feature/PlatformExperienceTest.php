<?php

namespace Tests\Feature;

use App\Models\FederationActivity;
use App\Models\Report;
use App\Models\User;
use App\Support\PlatformNavigation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\Support\UiDatabase;
use Tests\TestCase;

class PlatformExperienceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        UiDatabase::create();
        Schema::table('federation_activities', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable();
        });
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
        });
    }

    private function user(string $role, string $name = 'Fédération de test'): User
    {
        return User::create(['name' => 'Agent '.$role, 'email' => uniqid().'@example.test', 'password' => 'Test-only-password', 'role' => $role, 'status' => 'active', 'federation_name' => $name]);
    }

    private function report(User $owner, string $status, string $type = 'programme_budgetise', int $year = 2027): Report
    {
        return Report::create(['user_id' => $owner->id, 'type' => $type, 'year' => $year, 'status' => $status]);
    }

    public function test_role_navigation_has_resolvable_destinations_without_admin_links_for_other_roles(): void
    {
        foreach (['federation', 'dshn', 'dgf', 'dg', 'comite_arbitrage', 'ministre', 'admin'] as $role) {
            $groups = PlatformNavigation::groups(new User(['role' => $role]));
            $this->assertNotEmpty($groups);
            foreach ($groups as $items) {
                foreach ($items as $item) {
                    $this->assertTrue(Route::has($item[1]), $item[1]);
                    if ($role !== 'admin') {
                        $this->assertFalse(str_starts_with($item[1], 'admin.'));
                    }
                    if (! in_array($role, ['dgf', 'admin'])) {
                        $this->assertFalse(str_starts_with($item[1], 'dgf.'));
                    }
                }
            }
        }
    }

    public function test_reports_default_to_pending_and_filters_search_beyond_the_first_page(): void
    {
        $agent = $this->user('dshn');
        $owner = $this->user('federation', 'Cible nationale');
        $other = $this->user('federation', 'Autre fédération');
        for ($i = 0; $i < 23; $i++) {
            $this->report($other, 'soumis');
        }
        $target = $this->report($owner, 'soumis');
        $this->report($owner, 'valide');
        $this->report($owner, 'brouillon');
        $this->report($owner, 'soumis', 'programme_budgetise', 2026);

        $this->actingAs($agent)->get(route('dshn.reports.index'))->assertOk()
            ->assertViewHas('reports', fn ($reports) => $reports->total() === 25 && $reports->count() === 20)
            ->assertViewHas('status', 'soumis');
        $this->get(route('dshn.reports.index', ['q' => 'Cible', 'annee' => 2027, 'type' => 'programme_budgetise']))->assertOk()
            ->assertViewHas('reports', fn ($reports) => $reports->total() === 1 && $reports->first()->id === $target->id)
            ->assertViewHas('counts', fn ($counts) => (int) $counts['total'] === 2 && (int) $counts['valide'] === 1)
            ->assertSee('Cible nationale')->assertDontSee('Autre fédération');
    }

    public function test_report_pagination_keeps_filters_and_drafts_are_never_in_the_queue(): void
    {
        $agent = $this->user('admin');
        $owner = $this->user('federation', 'Recherche conservée');
        for ($i = 0; $i < 21; $i++) {
            $this->report($owner, 'valide');
        }
        $this->report($owner, 'brouillon');
        $this->actingAs($agent)->get(route('admin.reports.index', ['q' => 'Recherche', 'statut' => 'tous', 'annee' => 2027]))
            ->assertOk()->assertViewHas('reports', fn ($reports) => $reports->total() === 21
                && str_contains($reports->url(2), 'q=Recherche') && str_contains($reports->url(2), 'statut=tous'));
    }

    public function test_related_documents_are_from_the_same_campaign_and_hide_other_federations_and_drafts(): void
    {
        $owner = $this->user('federation');
        $agent = $this->user('dshn');
        $report = $this->report($owner, 'soumis');
        $related = $this->report($owner, 'valide', 'rapport_activite', 2026);
        $draft = $this->report($owner, 'brouillon', 'programme_reamenage');
        $this->report($owner, 'soumis', 'rapport_activite', 2025);
        $this->report($this->user('federation', 'Privée'), 'soumis', 'rapport_activite', 2026);

        $this->actingAs($agent)->get(route('activity-form.show', $report))->assertOk()
            ->assertViewHas('relatedReports', fn ($reports) => $reports->modelKeys() === [$related->id]);
        $this->actingAs($owner)->get(route('activity-form.show', $report))->assertOk()
            ->assertViewHas('relatedReports', fn ($reports) => $reports->modelKeys() === [$related->id, $draft->id]);
    }

    public function test_validated_document_does_not_offer_a_reject_action(): void
    {
        $report = $this->report($this->user('federation'), 'valide');
        $this->actingAs($this->user('dshn'))->get(route('activity-form.show', $report))
            ->assertOk()->assertSee('Télécharger le PDF')->assertDontSee('Rejeter avec un motif');
    }

    public function test_dgf_search_finds_activity_outside_first_page_and_respects_attachment_filter(): void
    {
        $owner = $this->user('federation', 'Fédération Alpha');
        for ($i = 0; $i < 26; $i++) {
            FederationActivity::create(['user_id' => $owner->id, 'year' => 2026, 'designation' => 'Rencontre '.$i, 'status' => 'soumis']);
        }
        $target = FederationActivity::create(['user_id' => $owner->id, 'year' => 2026, 'designation' => 'Finale nationale', 'status' => 'soumis']);
        $this->actingAs($this->user('dgf'))->get(route('dgf.activities.index', ['q' => 'Finale', 'justificatif' => 'sans']))
            ->assertOk()->assertViewHas('activities', fn ($activities) => $activities->total() === 1 && $activities->first()->id === $target->id);
        $this->get(route('dgf.activities.index', ['q' => 'Finale', 'justificatif' => 'avec']))->assertOk()
            ->assertViewHas('activities', fn ($activities) => $activities->isEmpty());
    }
}
