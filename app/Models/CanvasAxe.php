<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CanvasAxe extends Model
{
    protected $fillable = [
        'code',
        'label',
        'sort_order',
    ];

    public function sousAxes()
    {
        return $this->hasMany(CanvasSousAxe::class)->orderBy('sort_order');
    }
}
