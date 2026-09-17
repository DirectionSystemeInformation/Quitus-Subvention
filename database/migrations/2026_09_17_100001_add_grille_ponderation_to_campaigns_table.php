<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Copie figée de la grille de pondération au moment où la campagne entre en
     * pondération : une campagne conserve les critères et les paliers qui lui ont
     * été appliqués, même si la DSHN fait évoluer le paramétrage ensuite.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->json('grille_ponderation')->nullable()->after('bareme_repartition');
        });

        $this->figerGrilleDesCampagnesDejaPonderees();
    }

    /**
     * Les campagnes déjà entrées en pondération l'ont été avec la grille
     * officielle, celle que la migration précédente vient d'installer : on la
     * leur fige pour qu'une évolution ultérieure du paramétrage ne réécrive pas
     * rétroactivement leurs critères.
     */
    private function figerGrilleDesCampagnesDejaPonderees(): void
    {
        $campagnes = DB::table('campaigns')->where('etape', '>=', 4)->pluck('id');

        if ($campagnes->isEmpty()) {
            return;
        }

        $rubriques = DB::table('ponderation_rubriques')->orderBy('sort_order')->get()
            ->map(fn ($rubrique) => [
                'label' => $rubrique->label,
                'criteres' => DB::table('ponderation_criteres')
                    ->where('ponderation_rubrique_id', $rubrique->id)
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn ($critere) => [
                        'slug' => $critere->slug,
                        'label' => $critere->label,
                        'points_max' => (float) $critere->points_max,
                    ])->all(),
            ])->all();

        $paliers = DB::table('ponderation_paliers')->orderBy('seuil_min')->get()
            ->map(fn ($palier) => [
                'code' => $palier->code,
                'categorie' => $palier->categorie,
                'seuil_min' => (float) $palier->seuil_min,
            ])->all();

        DB::table('campaigns')->whereIn('id', $campagnes)->update([
            'grille_ponderation' => json_encode(['rubriques' => $rubriques, 'paliers' => $paliers]),
        ]);
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('grille_ponderation');
        });
    }
};
