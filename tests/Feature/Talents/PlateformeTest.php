<?php

namespace Tests\Feature\Talents;

use App\Models\Talents\Bourse;
use App\Models\Talents\Federation;
use App\Models\Talents\Talent;
use App\Models\User;
use Database\Seeders\Talents\DemoSeeder;
use Database\Seeders\Talents\ReferentielSeeder;
use Database\Seeders\Talents\RolesPermissionsSeeder;
use Tests\Support\UiDatabase;
use Tests\TestCase;

class PlateformeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        UiDatabase::create();
        $this->seed([RolesPermissionsSeeder::class, ReferentielSeeder::class, DemoSeeder::class]);
    }

    private const ECRANS = ['dashboard', 'talents.index', 'talents.validations', 'licences.index', 'competitions.index',
        'performances.index', 'evaluations.index', 'medical.index', 'parcours.index', 'bourses.index',
        'reconversions.index', 'entraineurs.index', 'federations.index', 'disciplines.index', 'referentiels.index',
        'utilisateurs.index', 'roles.index', 'rapports.index', 'journal.index'];

    private function compte(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_la_connexion_est_requise(): void
    {
        $this->get(route('suivi.dashboard'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();
    }

    public function test_tous_les_ecrans_s_affichent_pour_l_administrateur(): void
    {
        $admin = $this->compte('admin@talents.bf');
        foreach (self::ECRANS as $ecran) {
            $this->actingAs($admin)->get(route('suivi.'.$ecran))->assertOk();
        }
        foreach (['licences', 'competitions', 'performances', 'evaluations', 'medical', 'parcours', 'bourses', 'reconversions', 'entraineurs'] as $module) {
            $this->actingAs($admin)->get(route("suivi.$module.create"))->assertOk();
            $this->actingAs($admin)->get(route("suivi.$module.index", ['export' => 'csv']))->assertOk();
        }
        $talent = Talent::first();
        // Les fédérations viennent de Quitus : uniquement consultation et réglages
        $this->actingAs($admin)->get(route('suivi.federations.edit', Federation::first()->id))->assertOk();

        foreach (['identite', 'performances', 'physique', 'medical', 'parcours', 'bourses', 'reconversion', 'historique'] as $onglet) {
            $this->actingAs($admin)->get(route('suivi.talents.show', [$talent, 'onglet' => $onglet]))->assertOk();
        }
        $this->actingAs($admin)->get(route('suivi.talents.edit', $talent))->assertOk();
        $this->actingAs($admin)->get(route('suivi.talents.imprimer', $talent))->assertOk();
        $this->actingAs($admin)->get(route('suivi.talents.create'))->assertOk();
    }

    public function test_chaque_role_voit_ses_ecrans_et_est_bloque_ailleurs(): void
    {
        foreach (['ministere@talents.bf', 'fbf@talents.bf', 'dtn.fbf@talents.bf', 'medecin@talents.bf', 'observateur@talents.bf'] as $email) {
            $user = $this->compte($email);
            $this->actingAs($user)->get(route('suivi.dashboard'))->assertOk();
            foreach (self::ECRANS as $ecran) {
                $statut = $this->actingAs($user)->get(route('suivi.'.$ecran))->status();
                $this->assertContains($statut, [200, 403], "$email sur $ecran : HTTP $statut");
            }
        }

        $this->actingAs($this->compte('fbf@talents.bf'))->get(route('suivi.medical.index'))->assertForbidden();
        $this->actingAs($this->compte('medecin@talents.bf'))->get(route('suivi.medical.index'))->assertOk();
        $this->actingAs($this->compte('observateur@talents.bf'))->get(route('suivi.utilisateurs.index'))->assertForbidden();
    }

    public function test_une_federation_ne_voit_que_ses_athletes(): void
    {
        $fbf = $this->compte('fbf@talents.bf');
        $athleteFba = Talent::whereHas('federation', fn ($q) => $q->where('sigle', 'FBA'))->first();

        $this->actingAs($fbf)->get(route('suivi.talents.show', $athleteFba))->assertNotFound();
        $this->actingAs($fbf)->get(route('suivi.talents.index'))->assertOk()
            ->assertSee('KABORE')->assertDontSee('SAWADOGO');
    }

    public function test_circuit_creation_soumission_validation(): void
    {
        $fbf = $this->compte('fbf@talents.bf');
        $federation = Federation::where('sigle', 'FBF')->first();

        $this->actingAs($fbf)->post(route('suivi.talents.store'), [
            'nom' => 'BAMBARA', 'prenoms' => 'Ali', 'date_naissance' => '2008-02-01', 'genre' => 'Masculin',
            'nationalites' => 'Burkinabè', 'federation_id' => $federation->id,
            'discipline_id' => $federation->disciplines()->first()->id, 'statut_carriere' => 'Actif',
            'licence_numero' => 'FBF-2026-900', 'action' => 'soumettre',
        ])->assertRedirect();

        $talent = Talent::where('nom', 'BAMBARA')->firstOrFail();
        // 3 footballeurs FBF dans les données de démo → 4e de la séquence FBF, quel que soit le total national
        $this->assertSame('TLT-FBF-'.now()->year.'-004', $talent->code);
        $this->assertSame('En attente API', $talent->statut_profil);

        // Vérification de la licence (pas d'API configurée → vérification manuelle) puis validation ministérielle
        $this->actingAs($fbf)->post(route('suivi.licences.verifier', $talent->licenceNationale))->assertRedirect();
        $this->assertSame('En attente validation', $talent->fresh()->statut_profil);

        $this->actingAs($fbf)->post(route('suivi.talents.valider', $talent))->assertForbidden();
        $this->actingAs($this->compte('ministere@talents.bf'))->post(route('suivi.talents.valider', $talent))->assertRedirect();
        $this->assertSame('Validé', $talent->fresh()->statut_profil);
    }

    public function test_bourse_demandee_par_federation_et_decidee_par_ministere(): void
    {
        $talent = Talent::where('nom', 'KABORE')->first();
        $this->actingAs($this->compte('fbf@talents.bf'))->post(route('suivi.bourses.store'), [
            'talent_id' => $talent->id, 'type_bourse' => 'Bourse Haute Performance', 'financeur' => 'Ministère des Sports',
            'montant' => 500000, 'devise' => 'XOF', 'statut' => 'Accordée',
        ])->assertRedirect();

        $bourse = Bourse::where('talent_id', $talent->id)->latest('id')->first();
        $this->assertSame('Demandée', $bourse->statut, 'Une fédération ne peut pas s\'accorder une bourse');

        $this->actingAs($this->compte('ministere@talents.bf'))->post(route('suivi.bourses.decider', [$bourse, 'statut' => 'Accordée']))->assertRedirect();
        $this->assertSame('Accordée', $bourse->fresh()->statut);
    }

    public function test_personnalisation_du_tableau_de_bord(): void
    {
        $user = $this->compte('ministere@talents.bf');
        $this->actingAs($user)->post(route('suivi.dashboard.preferences'), ['visibles' => ['kpi_talents', 'bourses'], 'ordre' => ['bourses', 'kpi_talents']])
            ->assertRedirect();

        $this->actingAs($user)->get(route('suivi.dashboard'))->assertOk()
            ->assertSee('Demandes à instruire')->assertDontSee('Dernières performances</div>', false);
        $this->assertContains('repartition_genre', $user->fresh()->talent_preferences['masques']);
    }
}
