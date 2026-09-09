<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FederationActivity extends Model
{
    protected $fillable = [
        'user_id',
        'canvas_sous_axe_id',
        'axe',
        'axe_label',
        'sous_axe_code',
        'sous_axe_label',
        'year',
        'designation',
        'montant',
        'contribution_partenaires',
        'date',
        'observations',
        'status',
        'rejection_reason',
        'validated_by',
        'validated_at',
        'budget_line_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'montant' => 'decimal:2',
            'validated_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(FederationActivityDocument::class);
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function budgetLine()
    {
        return $this->belongsTo(BudgetLine::class);
    }
}
