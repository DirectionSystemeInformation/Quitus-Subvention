<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PonderationPalier extends Model
{
    protected $fillable = [
        'code',
        'categorie',
        'seuil_min',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'seuil_min' => 'decimal:2',
        ];
    }
}
