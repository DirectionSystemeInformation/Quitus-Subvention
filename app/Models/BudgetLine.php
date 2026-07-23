<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetLine extends Model
{
    protected $fillable = [
        'report_id',
        'canvas_sous_axe_id',
        'axe',
        'axe_label',
        'sous_axe_code',
        'sous_axe_label',
        'numero_ligne',
        'designation',
        'montant',
        'contribution_partenaires',
        'date',
        'observations',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'montant' => 'decimal:2',
        ];
    }

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}
