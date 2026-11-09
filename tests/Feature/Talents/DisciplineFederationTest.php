<?php

namespace Tests\Feature\Talents;

use App\Models\Talents\Discipline;
use App\Models\Talents\Federation;
use App\Models\Talents\Specialite;
use App\Models\User;
use Database\Seeders\Talents\DemoSeeder;
use Database\Seeders\Talents\ReferentielSeeder;
use Database\Seeders\Talents\RolesPermissionsSeeder;
use Tests\Support\UiDatabase;
use Tests\TestCase;

/**
 * Les fédérations gèrent elles-mêmes leurs disciplines et spécialités, sans toucher à celles des autres.
 */
class DisciplineFederationTest extends TestCase
{
    private User $fbf;

    protected function setUp(): void
    {
        parent::setUp();
        UiDatabase::create();
        $this->seed([RolesPermissionsSeeder::class, ReferentielSeeder::class, DemoSeeder::class]);
        $this->fbf = User::where('email', 'fbf@talents.bf')->firstOrFail();
    }

    public function test_une_federation_cree_une_discipline_rattachee_a_elle_meme(): void
    {
        $fba = Federation::where('sigle', 'FBA')->value('id');

        // Même si le formulaire est trafiqué pour viser une autre fédération
        $this->actingAs($this->fbf)->post(route('suivi.disciplines.store'), [
            'nom' => 'Beach soccer', 'federation_id' => $fba, 'collectif' => 1, 'actif' => 1,
        ])->assertSessionHasNoErrors();

        $discipline = Discipline::where('nom', 'Beach soccer')->firstOrFail();
        $this->assertSame($this->fbf->federationCloisonnee(), $discipline->federation_id);
        $this->assertTrue($discipline->collectif);

        // Elle apparaît dans le formulaire talent de la FBF, pas dans celui de la FBA
        $this->actingAs($this->fbf)->get(route('suivi.talents.create'))->assertSee('Beach soccer');
        $this->actingAs(User::where('email', 'fba@talents.bf')->first())->get(route('suivi.talents.create'))->assertDontSee('Beach soccer');
    }

    public function test_une_federation_gere_les_specialites_de_ses_disciplines(): void
    {
        $football = Discipline::where('nom', 'Football')->firstOrFail();

        $this->actingAs($this->fbf)->post(route('suivi.specialites.store', $football), ['nom' => 'Milieu défensif'])->assertSessionHasNoErrors();
        $specialite = Specialite::where('nom', 'Milieu défensif')->firstOrFail();

        $this->actingAs($this->fbf)->put(route('suivi.disciplines.update', $football), ['nom' => 'Football', 'collectif' => 1, 'actif' => 1])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->fbf)->delete(route('suivi.specialites.destroy', $specialite))->assertRedirect();
        $this->assertModelMissing($specialite);
    }

    public function test_une_federation_ne_peut_pas_toucher_aux_disciplines_des_autres(): void
    {
        $athletisme = Discipline::where('nom', 'Athlétisme')->firstOrFail(); // FBA
        $sprint = $athletisme->specialites()->first();

        $this->actingAs($this->fbf)->put(route('suivi.disciplines.update', $athletisme), ['nom' => 'Piraté'])->assertNotFound();
        $this->actingAs($this->fbf)->delete(route('suivi.disciplines.destroy', $athletisme))->assertNotFound();
        $this->actingAs($this->fbf)->post(route('suivi.specialites.store', $athletisme), ['nom' => 'Intrus'])->assertNotFound();
        $this->actingAs($this->fbf)->delete(route('suivi.specialites.destroy', $sprint))->assertNotFound();

        $this->assertSame('Athlétisme', $athletisme->fresh()->nom);
        $this->assertModelExists($sprint);
        $this->assertDatabaseMissing('talent_specialites', ['nom' => 'Intrus']);
    }

    public function test_doublon_de_nom_refuse_avec_un_message_neutre(): void
    {
        $this->actingAs($this->fbf)->post(route('suivi.disciplines.store'), ['nom' => 'Athlétisme', 'actif' => 1])
            ->assertSessionHasErrors('nom');

        $message = session('errors')->first('nom');
        $this->assertStringNotContainsString('FBA', $message);
        $this->assertStringContainsString('Ministère', $message);
    }

    public function test_une_discipline_utilisee_ne_peut_pas_etre_supprimee(): void
    {
        $football = Discipline::where('nom', 'Football')->firstOrFail();
        $this->actingAs($this->fbf)->delete(route('suivi.disciplines.destroy', $football))->assertSessionHas('erreur');
        $this->assertModelExists($football);
    }

    public function test_le_ministere_peut_rattacher_une_discipline_a_une_federation(): void
    {
        $ministere = User::where('email', 'ministere@talents.bf')->first();
        $fbf = Federation::where('sigle', 'FBF')->value('id');

        $this->actingAs($ministere)->post(route('suivi.disciplines.store'), ['nom' => 'Futsal', 'federation_id' => $fbf, 'actif' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame($fbf, Discipline::where('nom', 'Futsal')->value('federation_id'));
    }

    public function test_les_roles_sans_gestion_restent_en_lecture(): void
    {
        $observateur = User::where('email', 'observateur@talents.bf')->first();
        $this->actingAs($observateur)->get(route('suivi.disciplines.index'))->assertOk()->assertDontSee('Nouvelle discipline');
        $this->actingAs($observateur)->post(route('suivi.disciplines.store'), ['nom' => 'Rugby'])->assertForbidden();
    }
}
