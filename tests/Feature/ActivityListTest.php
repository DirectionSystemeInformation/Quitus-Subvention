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
 * Liste des activités d'une fédération : l'encart « À faire » et les onglets
 * « À faire » / « En examen DGF » séparent ce qui attend la fédération de ce
 * qui attend la DGF.
 */
class ActivityListTest extends TestCase
{
    private User $federation;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
        UiDatabase::create();
        // Le schéma de test simplifié précède l'ajout de la période et de la date de soumission.
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
        $this->federation = User::create(['name' => 'Président', 'email' => 'fede@example.test', 'password' => 'Test-only-password', 'role' => 'federation', 'status' => 'active', 'federation_name' => 'Fédération de test']);

        $this->activite('Soumise sans pièce', 'soumis');
        $this->activite('Soumise avec pièce', 'soumis', true);
        $this->activite('Brouillon en cours', 'brouillon');
        $this->activite('Rejetée à corriger', 'rejete', false, ['rejection_reason' => 'Facture illisible']);
        $this->activite('Validée', 'valide', true, ['montant' => 75000]);
    }

    private function activite(string $designation, string $status, bool $avecPiece = false, array $attributs = []): FederationActivity
    {
        $activite = FederationActivity::create($attributs + [
            'user_id' => $this->federation->id, 'year' => now()->year, 'status' => $status, 'designation' => $designation,
            'axe' => 'I', 'axe_label' => 'Politique Nationale des Sports AXE-1 : Masse', 'sous_axe_code' => 'I.1', 'sous_axe_label' => 'Compétitions',
            'montant' => 10000, 'date_debut' => '2026-09-12', 'date_fin' => '2026-09-26',
        ]);
        if ($avecPiece) {
            $activite->documents()->create(['file_path' => 'activites/test.pdf', 'original_filename' => 'facture.pdf']);
        }

        return $activite;
    }

    public function test_l_encart_a_faire_liste_ce_qui_attend_la_federation(): void
    {
        $this->actingAs($this->federation)->get(route('activities.index'))
            ->assertOk()
            ->assertSeeInOrder(['À faire', 'Soumise sans pièce', 'Ajouter un justificatif', 'Rejetée à corriger', 'Facture illisible', 'Brouillon en cours', 'Continuer'])
            ->assertSee('12–26 sept. 2026')
            ->assertSee('Versé au rapport')
            ->assertSee('75 000 FCFA');
    }

    public function test_les_onglets_separent_a_faire_et_en_examen(): void
    {
        $this->actingAs($this->federation)->get(route('activities.index', ['statut' => 'a_faire']))
            ->assertOk()
            ->assertSee('class="al-name cap-first">Soumise sans pièce', false)
            ->assertSee('class="al-name cap-first">Rejetée à corriger', false)
            ->assertDontSee('class="al-name cap-first">Soumise avec pièce', false)
            ->assertDontSee('class="al-name cap-first">Validée', false);

        $this->get(route('activities.index', ['statut' => 'en_examen']))
            ->assertOk()
            ->assertSee('class="al-name cap-first">Soumise avec pièce', false)
            ->assertDontSee('class="al-name cap-first">Soumise sans pièce', false);
    }
}
