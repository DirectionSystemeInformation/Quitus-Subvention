<?php

namespace Tests\Feature;

use App\Models\FederationActivity;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\Support\UiDatabase;
use Tests\TestCase;

/**
 * File de contrôle DGF : « À examiner maintenant » liste les activités
 * soumises avec justificatif, les plus anciennes d'abord ; les onglets
 * séparent les statuts et les pastilles filtrent par justificatif.
 */
class DgfActivityListTest extends TestCase
{
    private User $dgf;

    protected function setUp(): void
    {
        parent::setUp();
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
        $this->dgf = User::create(['name' => 'Agent DGF', 'email' => 'dgf@example.test', 'password' => 'Test-only-password', 'role' => 'dgf', 'status' => 'active']);
        $federation = User::create(['name' => 'Président', 'email' => 'fede@example.test', 'password' => 'Test-only-password', 'role' => 'federation', 'status' => 'active', 'federation_name' => 'Fédération Burkinabè de Judo']);

        foreach ([
            ['Récente avec pièce', 'soumis', 2, true],
            ['Ancienne avec pièce', 'soumis', 16, true],
            ['Soumise sans pièce', 'soumis', 5, false],
            ['Brouillon fédération', 'brouillon', null, false],
            ['Validée hier', 'valide', 20, true],
        ] as [$designation, $status, $jours, $piece]) {
            $activite = FederationActivity::create([
                'user_id' => $federation->id, 'year' => 2026, 'status' => $status, 'designation' => $designation,
                'axe' => 'I', 'axe_label' => 'Politique Nationale des Sports AXE-1 : Masse', 'sous_axe_code' => 'I.1', 'sous_axe_label' => 'Compétitions',
                'montant' => 10000, 'submitted_at' => $jours ? now()->subDays($jours) : null,
                'validated_at' => $status === 'valide' ? now()->subDay() : null,
            ]);
            if ($piece) {
                $activite->documents()->create(['file_path' => 'activites/test.pdf', 'original_filename' => 'facture.pdf']);
            }
        }
    }

    public function test_les_decisions_en_attente_sont_listees_des_plus_anciennes(): void
    {
        $this->actingAs($this->dgf)->get(route('dgf.activities.index'))
            ->assertOk()
            ->assertSeeInOrder(['À examiner maintenant', 'Ancienne avec pièce', 'soumise depuis 16 jours', 'Récente avec pièce', 'Examiner'])
            ->assertSee('La plus ancienne attend depuis 16 jours.')
            ->assertSee('class="al-name cap-first">Soumise sans pièce', false)
            ->assertDontSee('class="al-name cap-first">Brouillon fédération', false)
            ->assertDontSee('class="al-name cap-first">Validée hier', false);
    }

    public function test_les_pastilles_filtrent_par_justificatif_et_les_onglets_par_statut(): void
    {
        $this->actingAs($this->dgf)->get(route('dgf.activities.index', ['justificatif' => 'sans']))
            ->assertOk()
            ->assertSee('class="al-name cap-first">Soumise sans pièce', false)
            ->assertDontSee('class="al-name cap-first">Ancienne avec pièce', false);

        $this->get(route('dgf.activities.index', ['statut' => 'valide']))
            ->assertOk()
            ->assertSee('Validée le')
            ->assertSee('class="al-name cap-first">Validée hier', false)
            ->assertDontSee('class="al-name cap-first">Récente avec pièce', false);
    }
}
