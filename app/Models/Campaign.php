<?php

namespace App\Models;

use App\Support\PonderationGrille;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'annee_n1',
        'etape',
        'statut',
        'dg_decision',
        'dg_decided_at',
        'dg_rejection_reason',
        'ministre_decision',
        'ministre_decided_at',
        'ministre_rejection_reason',
        'session_arbitrage_date',
        'session_arbitrage_organized_at',
        'bareme_repartition',
        'grille_ponderation',
    ];

    protected function casts(): array
    {
        return [
            'bareme_repartition' => 'array',
            'grille_ponderation' => 'array',
            'dg_decided_at' => 'datetime',
            'ministre_decided_at' => 'datetime',
            'session_arbitrage_date' => 'date',
            'session_arbitrage_organized_at' => 'datetime',
        ];
    }

    public function allocations()
    {
        return $this->hasMany(CampaignAllocation::class);
    }

    /**
     * Grille appliquée à cette campagne : celle figée à l'ouverture de la
     * pondération, ou à défaut la grille actuellement paramétrée (campagnes
     * antérieures à la mise en place du paramétrage, ou pas encore pondérées).
     */
    public function grille(): PonderationGrille
    {
        return $this->grille_ponderation
            ? PonderationGrille::fromSnapshot($this->grille_ponderation)
            : PonderationGrille::current();
    }

    public function etapeLabel(): string
    {
        return self::labelForEtape($this->etape);
    }

    public static function labelForEtape(int $etape): string
    {
        return match ($etape) {
            3 => 'Traitement des rapports',
            4 => 'Pondération des activités',
            5 => 'Catégorisation des fédérations',
            6 => 'Répartition de la subvention',
            7 => 'Pré-validation DG',
            8 => "Arbitrage budgétaire",
            9 => 'Validation Ministre',
            10 => "Session d'arbitrage avec les fédérations",
            11 => 'Réception des programmes réaménagés',
            12 => 'Délivrance du quitus',
            default => 'Étape '.$etape,
        };
    }
}
