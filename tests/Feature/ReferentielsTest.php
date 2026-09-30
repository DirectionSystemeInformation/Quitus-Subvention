<?php

namespace Tests\Feature;

use App\Models\CanvasAxe;
use App\Models\CanvasSousAxe;
use App\Models\PonderationCritere;
use App\Models\PonderationPalier;
use App\Models\PonderationRubrique;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Tests\Support\UiDatabase;
use Tests\TestCase;

/**
 * Référentiels DSHN. Canevas : lecture par défaut, édition ligne à ligne avec
 * retour sur l'élément modifié. Grille de pondération : édition en lot.
 */
class ReferentielsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        UiDatabase::create();
        // Crée les tables et la grille officielle (7 rubriques, 23 critères, 12 paliers).
        (require database_path('migrations/2026_09_17_100000_create_ponderation_tables.php'))->up();
        (require database_path('migrations/2026_09_17_100001_add_grille_ponderation_to_campaigns_table.php'))->up();

        $this->actingAs(User::create(['name' => 'Agent DSHN', 'email' => 'dshn@example.test', 'password' => 'Test-only-password', 'role' => 'dshn', 'status' => 'active']));
    }

    private function sousAxe(): CanvasSousAxe
    {
        $axe = CanvasAxe::create(['code' => 'I', 'label' => 'Masse et relève sportive', 'sort_order' => 0]);

        return $axe->sousAxes()->create(['code' => 'I.1', 'label' => 'Championnat de jeunes', 'lignes_count' => 1, 'sort_order' => 0]);
    }

    public function test_le_canevas_s_affiche_en_lecture_avec_des_codes_proposes(): void
    {
        $sousAxe = $this->sousAxe();

        $page = $this->get(route('dshn.canevas.index'))->assertOk()->assertSee('Championnat de jeunes')->getContent();

        $this->assertMatchesRegularExpression('/<form id="sous-axe-form-'.$sousAxe->id.'"[^>]*\shidden\s*>/', $page);
        // Codes proposés pour le prochain sous-axe de l'axe I et pour le prochain axe.
        $this->assertStringContainsString('value="I.2"', $page);
        $this->assertStringContainsString('value="II"', $page);
    }

    public function test_une_modification_revient_sur_la_ligne_modifiee(): void
    {
        $sousAxe = $this->sousAxe();

        $this->from(route('dshn.canevas.index'))
            ->put(route('dshn.canevas.sous-axes.update', $sousAxe), ['_edit' => 'sous-axe-'.$sousAxe->id, 'code' => 'I.1', 'label' => 'Championnats jeunes'])
            ->assertRedirect(route('dshn.canevas.index').'#sous-axe-'.$sousAxe->id);

        $this->assertSame('Championnats jeunes', $sousAxe->fresh()->label);
    }

    public function test_une_erreur_rouvre_le_formulaire_de_la_ligne_concernee(): void
    {
        $sousAxe = $this->sousAxe();

        $page = $this->from(route('dshn.canevas.index'))->followingRedirects()
            ->put(route('dshn.canevas.sous-axes.update', $sousAxe), ['_edit' => 'sous-axe-'.$sousAxe->id, 'code' => 'I.1', 'label' => ''])
            ->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<form id="sous-axe-form-'.$sousAxe->id.'"[^>]*data-error-scope/', $page);
        $this->assertMatchesRegularExpression('/id="sous-axe-vue-'.$sousAxe->id.'"\s+hidden/', $page);
        $this->assertSame(1, substr_count($page, 'data-error-scope'));
    }

    public function test_la_grille_s_affiche_en_lecture_seule_avec_son_total(): void
    {
        $page = $this->get(route('dshn.ponderation.index'))
            ->assertOk()
            ->assertSee('Grille équilibrée')
            ->assertSee('Modifier la grille')
            ->assertSee('de 20 à moins de 23')
            ->assertSee('96 et plus')
            ->getContent();

        $this->assertStringNotContainsString('name="criteres[', $page);
        $this->assertStringContainsString('name="criteres[', $this->get(route('dshn.ponderation.index', ['edition' => 'grille']))->getContent());
    }

    public function test_la_grille_s_enregistre_en_lot(): void
    {
        $gouvernance = PonderationRubrique::where('label', 'Gouvernance')->firstOrFail();
        [$premier, $deuxieme] = $gouvernance->criteres()->orderBy('sort_order')->take(2)->get()->all();

        $this->from(route('dshn.ponderation.index', ['edition' => 'grille']))
            ->put(route('dshn.ponderation.grille.update'), [
                '_form' => 'grille',
                'rubriques' => [$gouvernance->id => ['label' => 'Gouvernance fédérale']],
                'criteres' => [
                    $premier->id => ['label' => $premier->label, 'points_max' => 3],
                    $deuxieme->id => ['label' => $deuxieme->label, 'points_max' => $deuxieme->points_max],
                ],
                'supprimer_criteres' => [$deuxieme->id],
                'nouveaux_criteres' => [$gouvernance->id => [1001 => ['label' => 'Parité du bureau', 'points_max' => 1]]],
                'nouvelles_rubriques' => [1002 => ['label' => 'Numérique', 'criteres' => [0 => ['label' => 'Site web à jour', 'points_max' => 2]]]],
            ])
            ->assertRedirect(route('dshn.ponderation.index').'#criteres')
            ->assertSessionHas('status', 'Grille de notation enregistrée : 2 modifications, 3 ajouts, 1 suppression.');

        $this->assertSame('Gouvernance fédérale', $gouvernance->fresh()->label);
        $this->assertEquals(3, (float) $premier->fresh()->points_max);
        $this->assertNull($deuxieme->fresh());
        $this->assertTrue($gouvernance->criteres()->where('label', 'Parité du bureau')->exists());
        $numerique = PonderationRubrique::where('label', 'Numérique')->firstOrFail();
        $this->assertSame(['Site web à jour'], $numerique->criteres()->pluck('label')->all());
        // Chaque critère garde une clé unique pour les scores.
        $this->assertSame(PonderationCritere::count(), PonderationCritere::distinct()->count('slug'));
    }

    public function test_une_erreur_rouvre_l_edition_avec_la_saisie_et_les_lignes_ajoutees(): void
    {
        $critere = PonderationCritere::firstOrFail();

        $page = $this->from(route('dshn.ponderation.index', ['edition' => 'grille']))->followingRedirects()
            ->put(route('dshn.ponderation.grille.update'), [
                '_form' => 'grille',
                'criteres' => [$critere->id => ['label' => '', 'points_max' => 1]],
                'nouveaux_criteres' => [$critere->ponderation_rubrique_id => [1001 => ['label' => 'Critère à conserver', 'points_max' => 2]]],
            ])
            ->assertOk()->getContent();

        $this->assertStringContainsString('data-gp-editor="grille"', $page);
        $this->assertStringContainsString('value="Critère à conserver"', $page);
        $this->assertStringContainsString('libellé du critère', $page);
        $this->assertFalse(PonderationCritere::where('label', 'Critère à conserver')->exists());
    }

    public function test_les_paliers_s_enregistrent_en_lot(): void
    {
        $a1 = PonderationPalier::where('code', 'A1')->firstOrFail();
        $a2 = PonderationPalier::where('code', 'A2')->firstOrFail();

        $this->from(route('dshn.ponderation.index', ['edition' => 'paliers']))
            ->put(route('dshn.ponderation.paliers.update'), [
                '_form' => 'paliers',
                'paliers' => [$a1->id => ['code' => 'A1', 'categorie' => 'A', 'seuil_min' => 95]],
                'supprimer_paliers' => [$a2->id],
                'nouveaux_paliers' => [1001 => ['code' => 'A0', 'categorie' => 'A', 'seuil_min' => 99]],
            ])
            ->assertRedirect(route('dshn.ponderation.index').'#paliers')
            ->assertSessionHas('status', 'Paliers de catégorisation enregistrés : 1 modification, 1 ajout, 1 suppression.');

        $this->assertEquals(95, (float) $a1->fresh()->seuil_min);
        $this->assertNull($a2->fresh());
        $this->assertSame('A0', PonderationPalier::orderByDesc('sort_order')->value('code'));
    }

    public function test_deux_paliers_ne_peuvent_pas_partager_un_seuil_ni_un_code(): void
    {
        $d1 = PonderationPalier::where('code', 'D1')->firstOrFail();

        $this->from(route('dshn.ponderation.index', ['edition' => 'paliers']))
            ->put(route('dshn.ponderation.paliers.update'), [
                '_form' => 'paliers',
                'paliers' => [$d1->id => ['code' => 'D2', 'categorie' => 'D', 'seuil_min' => 20]],
            ])
            ->assertSessionHasErrors('paliers');

        $this->assertSame('D1', $d1->fresh()->code);
        $this->assertEquals(23, (float) $d1->fresh()->seuil_min);
    }
}
