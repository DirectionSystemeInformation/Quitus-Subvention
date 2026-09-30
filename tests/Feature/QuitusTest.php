<?php

namespace Tests\Feature;

use App\Models\BudgetLine;
use App\Models\Campaign;
use App\Models\CampaignAllocation;
use App\Models\Report;
use App\Models\User;
use App\Support\Quitus;
use Illuminate\Support\Facades\URL;
use Tests\Support\UiDatabase;
use Tests\TestCase;

/**
 * Quitus sur le modèle officiel : activités du programme réaménagé validé,
 * délai de justification fixé par la DSHN à la délivrance.
 */
class QuitusTest extends TestCase
{
    private Campaign $campaign;

    private User $federation;

    private User $dshn;

    /** @var array<int, BudgetLine> */
    private array $lignes;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        UiDatabase::create();
        (require database_path('migrations/2026_09_30_100000_add_delai_justification_to_budget_lines_table.php'))->up();

        $this->dshn = User::create(['name' => 'Agent DSHN', 'email' => 'dshn@example.test', 'password' => 'Test-only-password', 'role' => 'dshn', 'status' => 'active']);
        $this->federation = User::create(['name' => 'Président', 'email' => 'fede@example.test', 'password' => 'Test-only-password', 'role' => 'federation', 'status' => 'active', 'federation_name' => 'Fédération Burkinabè de Judo']);

        $this->campaign = Campaign::create(['annee_n1' => 2027, 'etape' => 11, 'statut' => 'en_cours']);
        CampaignAllocation::create(['campaign_id' => $this->campaign->id, 'user_id' => $this->federation->id, 'montant_final' => 140000]);

        $programme = Report::create(['user_id' => $this->federation->id, 'type' => 'programme_reamenage', 'year' => 2027, 'status' => 'valide']);
        $ligne = fn (string $designation, int $montant, ?string $date) => $programme->budgetLines()->create([
            'axe' => 'I', 'sous_axe_code' => 'I.1', 'sous_axe_label' => 'Compétitions', 'numero_ligne' => 1,
            'designation' => $designation, 'montant' => $montant, 'date' => $date,
        ]);
        $this->lignes = [$ligne('Championnat national', 100000, '2027-03-10'), $ligne('Stage des arbitres', 40000, null)];
        // Emplacement vide du canevas : ignoré par le quitus.
        $programme->budgetLines()->create(['axe' => 'I', 'sous_axe_code' => 'I.1', 'sous_axe_label' => 'Compétitions', 'numero_ligne' => 2]);
    }

    private function url(string $nom): string
    {
        return route('campagnes.quitus.'.$nom, [$this->campaign, $this->federation]);
    }

    public function test_la_preparation_liste_les_activites_du_programme_reamenage(): void
    {
        $this->actingAs($this->dshn)->get($this->url('prepare'))
            ->assertOk()
            ->assertSee('Championnat national')
            ->assertSee('Stage des arbitres')
            ->assertSee('name="delais['.$this->lignes[0]->id.']"', false)
            ->assertSee('min="2027-03-10"', false);

        $this->actingAs($this->federation)->get($this->url('prepare'))->assertForbidden();
    }

    public function test_chaque_activite_doit_avoir_un_delai_posterieur_a_sa_date(): void
    {
        [$championnat, $stage] = $this->lignes;

        $this->actingAs($this->dshn)->from($this->url('prepare'))
            ->post($this->url('deliver'), ['delais' => [$championnat->id => '2027-03-01']])
            ->assertSessionHasErrors(["delais.{$championnat->id}", "delais.{$stage->id}"]);

        $this->assertNull(CampaignAllocation::first()->quitus_delivered_at);
    }

    public function test_la_delivrance_enregistre_les_delais_et_le_quitus(): void
    {
        [$championnat, $stage] = $this->lignes;

        $this->actingAs($this->dshn)
            ->post($this->url('deliver'), ['delais' => [$championnat->id => '2027-04-10', $stage->id => '2027-12-15']])
            ->assertRedirect($this->url('prepare'));

        $allocation = CampaignAllocation::first();
        $this->assertNotNull($allocation->quitus_delivered_at);
        $this->assertSame('QUITUS-2027-'.str_pad((string) $this->federation->id, 4, '0', STR_PAD_LEFT), $allocation->quitus_reference);
        $this->assertSame('2027-04-10', $championnat->fresh()->delai_justification->format('Y-m-d'));
        $this->assertSame('termine', $this->campaign->fresh()->statut);

        // Délivré : lecture seule, téléchargeable par la fédération.
        $this->get($this->url('prepare'))->assertOk()->assertSee('10/04/2027')->assertDontSee('name="delais[', false);
        $this->actingAs($this->federation)->get($this->url('download'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_l_apercu_produit_le_pdf_sans_rien_enregistrer(): void
    {
        $this->actingAs($this->dshn)
            ->post($this->url('preview'), ['delais' => [$this->lignes[0]->id => '2027-04-10']])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertNull($this->lignes[0]->fresh()->delai_justification);
        $this->assertNull(CampaignAllocation::first()->quitus_delivered_at);
    }

    public function test_le_montant_est_ecrit_en_lettres(): void
    {
        $this->assertSame('cent quarante mille', Quitus::enLettres(140000));
        $this->assertSame('deux millions quatre cent quatre-vingt-cinq mille', Quitus::enLettres(2485000));
        $this->assertSame('140 000', Quitus::fcfa(140000));
    }
}
