<?php

namespace Tests\Feature\Talents;

use App\Models\Talents\Federation;
use App\Models\User;
use Database\Seeders\Talents\ReferentielSeeder;
use Database\Seeders\Talents\RolesPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\UiDatabase;
use Tests\TestCase;

/**
 * Quitus et le suivi des talents partagent les comptes : après connexion, chacun choisit son portail.
 */
class PortailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        UiDatabase::create();
        $this->seed([RolesPermissionsSeeder::class, ReferentielSeeder::class]);
    }

    private function compte(string $role, array $attributs = []): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $role.'@example.test', 'password' => 'Test-only-password',
            'role' => $role, 'status' => 'active', ...$attributs,
        ]);
    }

    private function connexion(User $user)
    {
        return $this->post(route('login'), ['identifiant' => $user->email, 'password' => 'Test-only-password']);
    }

    public function test_une_federation_quitus_choisit_son_portail_apres_connexion(): void
    {
        $federation = $this->compte('federation', ['federation_name' => 'Fédération Burkinabè de Judo']);

        $this->connexion($federation)->assertRedirect(route('portails'));
        $this->get(route('portails'))->assertOk()
            ->assertSee('Quitus de subvention')->assertSee('Suivi des talents sportifs')
            ->assertSee(route('dashboard'), false)->assertSee(route('suivi.dashboard'), false);

        // Même compte, même mot de passe : la fiche fédération du suivi des talents est créée automatiquement
        $fiche = Federation::where('user_id', $federation->id)->firstOrFail();
        $this->assertSame('FBJ', $fiche->sigle);
        $this->assertSame('Fédération Burkinabè de Judo', $fiche->nom);
        $this->assertTrue($federation->fresh()->hasRole('Fédération'));

        $this->get(route('suivi.dashboard'))->assertOk()->assertSee('Fédération Burkinabè de Judo');
        $this->get(route('dashboard'))->assertOk(); // l'espace Quitus reste accessible
    }

    public function test_les_roles_du_ministere_sont_relies_automatiquement(): void
    {
        foreach (['admin' => 'Super administrateur', 'dshn' => 'Ministère des Sports', 'ministre' => 'Observateur national',
            'dg' => 'Observateur national', 'comite_arbitrage' => 'Observateur national'] as $role => $roleTalents) {
            $user = $this->compte($role);
            $this->connexion($user)->assertRedirect(route('portails'));
            $this->assertTrue($user->fresh()->hasRole($roleTalents), "$role → $roleTalents");
            $this->post(route('logout'));
        }
    }

    public function test_un_role_quitus_sans_correspondance_va_directement_sur_quitus(): void
    {
        $dgf = $this->compte('dgf');

        $this->connexion($dgf)->assertRedirect(route('dgf.activities.index'));
        $this->get(route('portails'))->assertRedirect(route('dgf.activities.index'));
        $this->get(route('suivi.dashboard'))->assertForbidden();
    }

    public function test_un_compte_complementaire_va_directement_au_suivi_des_talents(): void
    {
        $dtn = $this->compte('talents');
        $dtn->assignRole('Direction Technique (DTN)');

        $this->connexion($dtn)->assertRedirect(route('suivi.dashboard'));
        $this->get(route('portails'))->assertRedirect(route('suivi.dashboard'));
        $this->get(route('suivi.dashboard'))->assertOk()->assertDontSee('Quitus de subvention');

        // Aucun accès aux subventions
        $this->get(route('dashboard'))->assertForbidden();
        $this->get(route('dshn.dashboard'))->assertForbidden();
        $this->get(route('campagnes.index'))->assertForbidden();
    }

    public function test_un_compte_complementaire_desactive_ne_peut_pas_se_connecter(): void
    {
        $dtn = $this->compte('talents', ['status' => 'pending']);
        $dtn->assignRole('Direction Technique (DTN)');

        $this->connexion($dtn)->assertSessionHasErrors('identifiant');
        $this->assertGuest();
    }

    public function test_une_federation_desactivee_dans_quitus_perd_l_acces_aux_talents(): void
    {
        $federation = $this->compte('federation', ['federation_name' => 'Fédération Burkinabè de Judo']);
        $this->connexion($federation);
        $this->get(route('suivi.dashboard'))->assertOk();

        // La DSHN rejette le compte dans Quitus : l'accès talents tombe à la requête suivante
        $federation->forceFill(['status' => 'rejected'])->save();
        $this->app['auth']->forgetGuards(); // comme en production : le compte est relu en base à chaque requête
        $this->get(route('suivi.dashboard'))->assertForbidden();
        $this->assertFalse($federation->fresh()->hasRole('Fédération'));
    }

    public function test_les_portails_se_croisent_dans_les_menus(): void
    {
        $federation = $this->compte('federation', ['federation_name' => 'Fédération Burkinabè de Judo']);
        $this->connexion($federation);

        $this->get(route('dashboard'))->assertSee(route('suivi.dashboard'), false)->assertSee('Suivi des talents');
        $this->get(route('suivi.dashboard'))->assertSee(route('portails'), false)->assertSee('Quitus de subvention');
    }

    public function test_l_installation_rattache_les_federations_existantes(): void
    {
        $this->compte('federation', ['federation_name' => 'Fédération Burkinabè de Football', 'email' => 'fbf@example.test']);
        $this->compte('federation', ['federation_name' => 'Fédération Burkinabè de Basketball', 'email' => 'fbb@example.test']);
        User::create(['name' => 'En attente', 'email' => 'attente@example.test', 'password' => 'x', 'role' => 'federation',
            'status' => 'pending', 'federation_name' => 'Fédération en attente']);

        Artisan::call('talents:installer');

        $this->assertEqualsCanonicalizing(['FBF', 'FBB'], Federation::pluck('sigle')->all());
        $this->assertSame(0, Federation::whereHas('user', fn ($q) => $q->where('status', 'pending'))->count());

        Artisan::call('talents:installer'); // idempotent
        $this->assertSame(2, Federation::count());
    }

    public function test_le_sigle_reste_unique(): void
    {
        $this->compte('federation', ['federation_name' => 'Fédération Burkinabè de Boxe', 'email' => 'boxe@example.test']);
        $this->compte('federation', ['federation_name' => 'Fédération Burkinabè de Basketball', 'email' => 'basket@example.test']);

        Artisan::call('talents:installer');

        $this->assertEqualsCanonicalizing(['FBB', 'FBB2'], Federation::pluck('sigle')->all());
    }
}
