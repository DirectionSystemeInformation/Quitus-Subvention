<?php

namespace Tests\Feature\Talents;

use App\Models\Talents\Competition;
use App\Models\Talents\Discipline;
use App\Models\Talents\Entraineur;
use App\Models\Talents\Federation;
use App\Models\Talents\Licence;
use App\Models\Talents\Parcours;
use App\Models\Talents\Performance;
use App\Models\Talents\Talent;
use App\Models\User;
use Database\Seeders\Talents\DemoSeeder;
use Database\Seeders\Talents\ReferentielSeeder;
use Database\Seeders\Talents\RolesPermissionsSeeder;
use Tests\Support\UiDatabase;
use Tests\TestCase;

/**
 * Un compte fédération (ici FBF, football) ne doit rien voir ni pouvoir atteindre des autres fédérations.
 */
class CloisonnementFederationTest extends TestCase
{
    /** Données appartenant à d'autres fédérations dans le jeu de démonstration. */
    private const TRACES_AUTRES = ['SAWADOGO', 'Aminata', 'SANOU', 'ZONGO', 'COMPAORE', 'DABO', 'ILBOUDO',
        'FBA', 'FBB', 'FBJ', 'FBT', 'FBC', 'Athlétisme', 'Basketball', 'Judo', 'Taekwondo', 'Cyclisme',
        'Meeting International de Dakar', 'NCAA', 'Duke', 'INSEP', 'SMITH', 'DUPONT'];

    private User $fbf;

    protected function setUp(): void
    {
        parent::setUp();
        UiDatabase::create();
        $this->seed([RolesPermissionsSeeder::class, ReferentielSeeder::class, DemoSeeder::class]);
        $this->fbf = User::where('email', 'fbf@talents.bf')->firstOrFail();
    }

    private function talentAutreFederation(): Talent
    {
        return Talent::where('nom', 'SAWADOGO')->firstOrFail(); // FBA
    }

    public function test_aucun_ecran_ne_montre_les_donnees_des_autres_federations(): void
    {
        $ecrans = ['dashboard', 'talents.index', 'licences.index', 'competitions.index', 'performances.index',
            'evaluations.index', 'parcours.index', 'bourses.index', 'reconversions.index', 'entraineurs.index',
            'disciplines.index', 'rapports.index', 'talents.create', 'licences.create', 'performances.create',
            'parcours.create', 'bourses.create', 'reconversions.create', 'competitions.create', 'entraineurs.create'];

        foreach ($ecrans as $ecran) {
            $page = $this->actingAs($this->fbf)->get(route('suivi.'.$ecran))->assertOk();
            foreach (self::TRACES_AUTRES as $trace) {
                $page->assertDontSee($trace, false);
            }
        }

        // Exports CSV
        foreach (['talents', 'licences', 'performances', 'parcours', 'bourses', 'reconversions'] as $module) {
            $csv = $this->actingAs($this->fbf)->get(route("suivi.$module.index", ['export' => 'csv']))->streamedContent();
            $this->assertStringNotContainsString('SAWADOGO', $csv, "Export $module");
            $this->assertStringNotContainsString('OUEDRAOGO', $csv, "Export $module");
        }

        // Même en forçant le filtre sur une autre fédération
        $fba = Federation::where('sigle', 'FBA')->value('id');
        $this->actingAs($this->fbf)->get(route('suivi.talents.index', ['federation_id' => $fba]))->assertDontSee('SAWADOGO');
        $this->actingAs($this->fbf)->get(route('suivi.dashboard', ['federation_id' => $fba]))->assertDontSee('SAWADOGO');
    }

    public function test_acces_direct_aux_fiches_des_autres_federations_renvoie_404(): void
    {
        $autre = $this->talentAutreFederation();
        $this->actingAs($this->fbf)->get(route('suivi.talents.show', $autre))->assertNotFound();
        $this->actingAs($this->fbf)->get(route('suivi.talents.edit', $autre))->assertNotFound();
        $this->actingAs($this->fbf)->get(route('suivi.talents.imprimer', $autre))->assertNotFound();
        $this->actingAs($this->fbf)->put(route('suivi.talents.update', $autre), [])->assertNotFound();
        $this->actingAs($this->fbf)->post(route('suivi.talents.soumettre', $autre))->assertNotFound();

        $licence = Licence::where('talent_id', $autre->id)->first();
        $this->actingAs($this->fbf)->get(route('suivi.licences.edit', $licence->id))->assertNotFound();
        $this->actingAs($this->fbf)->post(route('suivi.licences.verifier', $licence))->assertNotFound();

        $perf = Performance::where('talent_id', $autre->id)->first();
        $this->actingAs($this->fbf)->get(route('suivi.performances.edit', $perf->id))->assertNotFound();

        $parcours = Parcours::where('talent_id', $autre->id)->first();
        $this->actingAs($this->fbf)->get(route('suivi.parcours.edit', $parcours->id))->assertNotFound();

        $coachFba = Entraineur::where('nom', 'KABORE')->first();
        $this->actingAs($this->fbf)->get(route('suivi.entraineurs.edit', $coachFba->id))->assertNotFound();
    }

    public function test_impossible_d_ecrire_sur_les_donnees_des_autres_federations(): void
    {
        $autre = $this->talentAutreFederation();
        $competitionFbf = Competition::where('nom', 'Ligue 2 BKT')->first();

        // Ajouter une performance à un athlète d'une autre fédération
        $this->actingAs($this->fbf)->post(route('suivi.performances.store'), [
            'talent_id' => $autre->id, 'competition_id' => $competitionFbf->id, 'date_evenement' => '2026-05-01',
        ])->assertNotFound();

        // Utiliser une compétition d'une autre discipline
        $dakar = Competition::where('nom', 'like', 'Meeting%')->first();
        $this->actingAs($this->fbf)->post(route('suivi.performances.store'), [
            'talent_id' => Talent::where('nom', 'KABORE')->value('id'), 'competition_id' => $dakar->id, 'date_evenement' => '2026-05-01',
        ])->assertSessionHasErrors('competition_id');

        // Créer un talent dans une discipline / avec un entraîneur d'une autre fédération
        $this->actingAs($this->fbf)->post(route('suivi.talents.store'), [
            'nom' => 'TEST', 'prenoms' => 'Fuite', 'date_naissance' => '2008-01-01', 'genre' => 'Masculin',
            'nationalites' => 'Burkinabè', 'federation_id' => Federation::where('sigle', 'FBA')->value('id'),
            'discipline_id' => Discipline::where('nom', 'Athlétisme')->value('id'),
            'entraineur_id' => Entraineur::where('nom', 'SMITH')->value('id'), 'statut_carriere' => 'Actif',
        ])->assertSessionHasErrors(['discipline_id', 'entraineur_id']);
        $this->assertDatabaseMissing('talents', ['nom' => 'TEST']);

        // Modifier une compétition du calendrier national
        $this->actingAs($this->fbf)->get(route('suivi.competitions.edit', $competitionFbf->id))->assertForbidden();
    }

    public function test_une_competition_creee_par_une_federation_reste_privee(): void
    {
        $this->actingAs($this->fbf)->post(route('suivi.competitions.store'), [
            'nom' => 'Tournoi interne FBF', 'niveau' => 'National', 'date_debut' => '2026-11-10',
        ])->assertRedirect();

        $competition = Competition::where('nom', 'Tournoi interne FBF')->firstOrFail();
        $this->assertSame(Federation::where('sigle', 'FBF')->value('id'), $competition->federation_id);
        $this->actingAs($this->fbf)->get(route('suivi.competitions.edit', $competition->id))->assertOk();

        $fba = User::where('email', 'fba@talents.bf')->first();
        $this->actingAs($fba)->get(route('suivi.competitions.index'))->assertDontSee('Tournoi interne FBF');
        $this->actingAs($fba)->get(route('suivi.competitions.edit', $competition->id))->assertNotFound();
    }

    public function test_les_codes_talent_suivent_une_sequence_par_federation(): void
    {
        $annee = now()->year;
        $fba = User::where('email', 'fba@talents.bf')->first();
        $talentFba = fn (string $nom) => [
            'nom' => $nom, 'prenoms' => 'Test', 'date_naissance' => '2008-01-01', 'genre' => 'Féminin',
            'nationalites' => 'Burkinabè', 'federation_id' => $fba->federationCloisonnee(), 'statut_carriere' => 'Actif',
            'discipline_id' => Discipline::where('nom', 'Athlétisme')->value('id'),
        ];

        $this->actingAs($fba)->post(route('suivi.talents.store'), $talentFba('ALPHA'))->assertRedirect();
        $this->actingAs($fba)->post(route('suivi.talents.store'), $talentFba('BETA'))->assertRedirect();

        // FBA avait 2 talents : la séquence FBA continue, indépendamment des 8 autres talents du pays.
        $this->assertSame("TLT-FBA-$annee-003", Talent::where('nom', 'ALPHA')->value('code'));
        $this->assertSame("TLT-FBA-$annee-004", Talent::where('nom', 'BETA')->value('code'));
        $this->assertSame("TLT-FBF-$annee-001", Talent::where('nom', 'KABORE')->value('code'));
    }

    public function test_le_doublon_de_licence_ne_revele_pas_son_existence(): void
    {
        $numeroFba = Licence::where('numero', 'FBA-2026-089')->firstOrFail(); // licence de SAWADOGO (FBA)
        $kabore = Talent::where('nom', 'KABORE')->value('id');

        $reponse = $this->actingAs($this->fbf)->post(route('suivi.licences.store'), [
            'talent_id' => $kabore, 'portee' => 'Nationale', 'type_licence' => 'Nationale',
            'numero' => $numeroFba->numero, 'organisme' => $numeroFba->organisme, 'statut' => 'Active',
        ]);
        $reponse->assertSessionHasErrors(['numero' => Licence::MESSAGE_DOUBLON]);
        $this->assertStringNotContainsString('déjà', session('errors')->first('numero'));

        // Même message neutre depuis le formulaire de création d'un talent
        $this->actingAs($this->fbf)->post(route('suivi.talents.store'), [
            'nom' => 'GAMMA', 'prenoms' => 'Test', 'date_naissance' => '2008-01-01', 'genre' => 'Masculin',
            'nationalites' => 'Burkinabè', 'federation_id' => $this->fbf->federationCloisonnee(), 'statut_carriere' => 'Actif',
            'discipline_id' => Discipline::where('nom', 'Football')->value('id'), 'licence_numero' => $numeroFba->numero,
        ])->assertSessionHasErrors(['licence_numero' => Licence::MESSAGE_DOUBLON]);
        $this->assertDatabaseMissing('talents', ['nom' => 'GAMMA']);
    }

    public function test_un_compte_sans_federation_ni_acces_national_ne_voit_rien(): void
    {
        $orphelin = User::create(['name' => 'Sans périmètre', 'email' => 'orphelin@talents.bf', 'password' => 'password',
            'role' => 'talents', 'status' => 'active']);
        $orphelin->assignRole('Fédération');

        $this->actingAs($orphelin)->get(route('suivi.talents.index'))->assertOk()
            ->assertDontSee('SAWADOGO')->assertDontSee('KABORE');
    }
}
