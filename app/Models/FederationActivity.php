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

    public function axeNumber(): ?int
    {
        return match ($this->axe) {
            'I' => 1,
            'II' => 2,
            'III' => 3,
            default => null,
        };
    }

    /**
     * axe_label is stored as "Politique Nationale des Sports AXE-1 : Masse et
     * relève sportive" — split the ministry-programme prefix from the axe's
     * own description so the UI doesn't have to repeat "AXE N" next to the
     * "Axe N" badge. Falls back to the raw label if the format ever changes.
     */
    public function axeLabelParts(): array
    {
        if (preg_match('/^(.+?)\s+AXE[- ]\S+\s*:\s*(.+)$/u', (string) $this->axe_label, $matches)) {
            return ['prefix' => trim($matches[1]), 'description' => trim($matches[2])];
        }

        return ['prefix' => $this->axe_label, 'description' => null];
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
