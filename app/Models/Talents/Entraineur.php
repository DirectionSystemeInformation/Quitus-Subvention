<?php

namespace App\Models\Talents;

use App\Models\Talents\Concerns\Journalise;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entraineur extends Model
{
    use Journalise;

    protected $table = 'talent_entraineurs';

    protected $fillable = ['nom', 'prenoms', 'discipline_id', 'federation_id', 'diplome',
        'structure', 'pays', 'telephone', 'email', 'actif'];

    protected $casts = ['actif' => 'boolean'];

    public function getNomCompletAttribute(): string
    {
        return "{$this->nom} {$this->prenoms}";
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    public function federation(): BelongsTo
    {
        return $this->belongsTo(Federation::class);
    }

    public function talents(): HasMany
    {
        return $this->hasMany(Talent::class);
    }

    public function scopeVisiblePar(Builder $query, User $user): Builder
    {
        return $user->aAccesNational() ? $query : $query->where('talent_entraineurs.federation_id', $user->federationCloisonnee());
    }

    public function libelleJournal(): string
    {
        return $this->nom_complet;
    }
}
