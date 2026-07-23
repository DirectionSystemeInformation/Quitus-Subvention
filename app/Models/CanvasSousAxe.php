<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CanvasSousAxe extends Model
{
    protected $fillable = [
        'canvas_axe_id',
        'code',
        'label',
        'lignes_count',
        'sort_order',
    ];

    public function axe()
    {
        return $this->belongsTo(CanvasAxe::class, 'canvas_axe_id');
    }
}
