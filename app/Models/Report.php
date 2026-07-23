<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'year',
        'file_path',
        'original_filename',
        'status',
        'rejection_reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function budgetLines()
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'rapport_activite' => "Rapport d'activité",
            'programme_budgetise' => "Projet de programme d'activités budgétisé",
            default => $this->type,
        };
    }
}
