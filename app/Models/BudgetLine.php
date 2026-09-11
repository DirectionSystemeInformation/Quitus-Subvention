<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetLine extends Model
{
    protected $fillable = [
        'report_id',
        'canvas_sous_axe_id',
        'activity_id',
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
     * axe_label est stocké comme "Politique Nationale des Sports AXE-1 :
     * Masse et relève sportive" — sépare le nom du programme ministériel de
     * la description propre à l'axe (même logique que
     * FederationActivity::axeLabelParts()).
     */
    public function axeLabelParts(): array
    {
        if (preg_match('/^(.+?)\s+AXE[- ]\S+\s*:\s*(.+)$/u', (string) $this->axe_label, $matches)) {
            return ['prefix' => trim($matches[1]), 'description' => trim($matches[2])];
        }

        return ['prefix' => $this->axe_label, 'description' => null];
    }

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function activity()
    {
        return $this->belongsTo(FederationActivity::class, 'activity_id');
    }
}
