<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PonderationRubrique extends Model
{
    protected $fillable = [
        'label',
        'sort_order',
    ];

    public function criteres()
    {
        return $this->hasMany(PonderationCritere::class)->orderBy('sort_order');
    }

    /**
     * Total des points de la rubrique — somme des points max de ses critères,
     * jamais saisi à la main pour rester cohérent avec la grille.
     */
    public function pointsMax(): float
    {
        return (float) $this->criteres->sum('points_max');
    }
}
