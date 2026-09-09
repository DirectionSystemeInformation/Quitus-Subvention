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
        'date_debut',
        'date_fin',
        'observations',
        'status',
        'submitted_at',
        'rejection_reason',
        'validated_by',
        'validated_at',
        'budget_line_id',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'montant' => 'decimal:2',
            'validated_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function dateRangeLabel(): ?string
    {
        if ($this->date_debut && $this->date_fin && ! $this->date_debut->isSameDay($this->date_fin)) {
            return $this->date_debut->format('d/m/Y').' – '.$this->date_fin->format('d/m/Y');
        }

        return optional($this->date_debut ?? $this->date_fin)->format('d/m/Y');
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
