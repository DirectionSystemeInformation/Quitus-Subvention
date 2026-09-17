<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ponderation_rubriques', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ponderation_criteres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ponderation_rubrique_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('label');
            $table->decimal('points_max', 5, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ponderation_paliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('categorie');
            $table->decimal('seuil_min', 5, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $this->seedGrilleOfficielle();
    }

    /**
     * Grille officielle de pondération (Plénière 2025) : 7 rubriques, 23 critères,
     * 100 points, et les 12 paliers de catégorie ajustée. Reprise telle quelle de
     * l'ancienne classe App\Support\PonderationCriteria, désormais paramétrable
     * par la DSHN.
     */
    private function seedGrilleOfficielle(): void
    {
        $rubriques = [
            ['Gouvernance', [
                ['gouv_1', 'Tenues des instances statutaires', 1],
                ['gouv_2', 'Existence de cadre juridique', 2],
                ['gouv_3', "Mise en œuvre du programme d'activité", 6],
                ['gouv_4', 'Existence du document de planification', 4],
                ['gouv_5', 'Prise en compte du genre dans les instances', 1],
                ['gouv_6', 'Formation des cadres administratifs et techniques', 4],
            ]],
            ['Activités sportives et de loisirs', [
                ['acti_1', 'Tenues des championnats nationaux', 8],
                ['acti_2', 'Participation aux compétitions au plan international', 6],
            ]],
            ['Performances sportives', [
                ['perf_1', 'Médailles remportées par niveau de compétitions', 8],
                ['perf_2', 'Inscription de la discipline au CIO', 5],
                ['perf_3', "Existence de centre d'excellence pour sportifs fonctionnel", 4],
                ['perf_4', 'Existence de championnats de catégories jeunes (relève sportive)', 8],
                ['perf_5', 'Athlètes licenciés', 5],
            ]],
            ['Financement', [
                ['fin_1', 'Capacité de mobilisation des ressources financières', 5],
                ['fin_2', 'Reddition des comptes dans les délais', 5],
                ['fin_3', 'Capacité à générer des ressources propres', 5],
            ]],
            ['Couverture territoriale', [
                ['couv_1', 'Capacité à couvrir le territoire national', 12],
            ]],
            ['Infrastructures propres à la structure', [
                ['infra_1', 'Siège fonctionnel', 1],
                ['infra_2', "Existence d'un centre de formation opérationnel", 2],
                ['infra_3', "Existence d'installations sportives", 1],
            ]],
            ['Capacité opérationnelle', [
                ['capa_1', 'Disponibilité de moyens roulants', 1],
                ['capa_2', 'Disponibilité de personnels permanents', 2],
                ['capa_3', 'Disponibilité de personnels techniques qualifiés', 4],
            ]],
        ];

        $now = now();

        foreach ($rubriques as $rubriqueIndex => [$label, $criteres]) {
            $rubriqueId = DB::table('ponderation_rubriques')->insertGetId([
                'label' => $label,
                'sort_order' => $rubriqueIndex,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($criteres as $critereIndex => [$slug, $critereLabel, $pointsMax]) {
                DB::table('ponderation_criteres')->insert([
                    'ponderation_rubrique_id' => $rubriqueId,
                    'slug' => $slug,
                    'label' => $critereLabel,
                    'points_max' => $pointsMax,
                    'sort_order' => $critereIndex,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // seuil_min = borne inférieure incluse ; un score est classé dans le palier
        // au plus grand seuil_min qui lui est inférieur ou égal.
        $paliers = [
            ['D3', 'D', 0], ['D2', 'D', 20], ['D1', 'D', 23],
            ['C3', 'C', 26], ['C2', 'C', 36], ['C1', 'C', 46],
            ['B3', 'B', 51], ['B2', 'B', 56], ['B1', 'B', 61],
            ['A3', 'A', 76], ['A2', 'A', 86], ['A1', 'A', 96],
        ];

        foreach ($paliers as $index => [$code, $categorie, $seuilMin]) {
            DB::table('ponderation_paliers')->insert([
                'code' => $code,
                'categorie' => $categorie,
                'seuil_min' => $seuilMin,
                'sort_order' => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ponderation_criteres');
        Schema::dropIfExists('ponderation_rubriques');
        Schema::dropIfExists('ponderation_paliers');
    }
};
