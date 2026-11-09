<?php

namespace App\Models\Talents;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discipline extends Model
{
    protected $table = 'talent_disciplines';

    protected $fillable = ['federation_id', 'nom', 'collectif', 'actif'];

    protected $casts = ['collectif' => 'boolean', 'actif' => 'boolean'];

    public function federation(): BelongsTo
    {
        return $this->belongsTo(Federation::class);
    }

    public function specialites(): HasMany
    {
        return $this->hasMany(Specialite::class)->orderBy('nom');
    }

    public function talents(): HasMany
    {
        return $this->hasMany(Talent::class);
    }

    public function scopeVisiblePar(Builder $query, User $user): Builder
    {
        return $user->aAccesNational() ? $query : $query->where('talent_disciplines.federation_id', $user->federationCloisonnee());
    }
}
