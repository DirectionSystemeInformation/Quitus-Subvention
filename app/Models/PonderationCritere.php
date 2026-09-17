<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PonderationCritere extends Model
{
    protected $fillable = [
        'ponderation_rubrique_id',
        'slug',
        'label',
        'points_max',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'points_max' => 'decimal:2',
        ];
    }

    public function rubrique()
    {
        return $this->belongsTo(PonderationRubrique::class, 'ponderation_rubrique_id');
    }
}
