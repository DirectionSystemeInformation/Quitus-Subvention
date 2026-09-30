<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Ouverture exceptionnelle, accordée par l'administration, de la saisie d'une
 * année hors exercice pour une fédération et un type de document.
 */
class OuvertureSaisie extends Model
{
    protected $table = 'ouvertures_saisie';

    protected $fillable = [
        'user_id',
        'type',
        'year',
        'motif',
        'expires_on',
        'granted_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'expires_on' => 'date',
        ];
    }

    /** Ouvertures sans date limite, ou dont la date limite n'est pas dépassée. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', now()->toDateString()));
    }

    public function federation()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
