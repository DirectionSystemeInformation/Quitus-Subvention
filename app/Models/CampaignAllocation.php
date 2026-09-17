<?php

namespace App\Models;

use App\Support\PonderationGrille;
use Illuminate\Database\Eloquent\Model;

class CampaignAllocation extends Model
{
    protected $fillable = [
        'campaign_id',
        'user_id',
        'criteres_scores',
        'score_total',
        'categorie',
        'categorie_ajustee',
        'montant_propose',
        'montant_arbitre',
        'montant_final',
        'quitus_delivered_at',
        'quitus_reference',
    ];

    protected function casts(): array
    {
        return [
            'criteres_scores' => 'array',
            'score_total' => 'decimal:2',
            'montant_propose' => 'decimal:2',
            'montant_arbitre' => 'decimal:2',
            'montant_final' => 'decimal:2',
            'quitus_delivered_at' => 'datetime',
        ];
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function federation()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Recalcule score_total, categorie et categorie_ajustee à partir de criteres_scores.
     * À appeler après toute modification des scores, avant sauvegarde.
     *
     * La grille peut être passée pour éviter de la recharger à chaque allocation
     * lors d'une mise à jour en lot ; sinon celle de la campagne est utilisée.
     */
    public function recalculerScores(?PonderationGrille $grille = null): void
    {
        $grille ??= $this->campaign->grille();

        $scores = $this->criteres_scores ?? [];
        $total = $grille->total($scores);

        $this->score_total = $total;
        $this->categorie = $grille->categorie($total);
        $this->categorie_ajustee = $grille->categorieAjustee($total);
    }
}
