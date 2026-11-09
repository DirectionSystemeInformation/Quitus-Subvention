<?php

namespace App\Models\Talents;

use App\Models\Talents\Concerns\Journalise;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competition extends Model
{
    use Journalise;

    protected $table = 'talent_competitions';

    protected $fillable = ['nom', 'federation_id', 'discipline_id', 'niveau', 'lieu', 'pays', 'date_debut', 'date_fin'];

    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date'];

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class);
    }

    /**
     * Une fédération voit ses propres compétitions et celles du calendrier national
     * (créées par le Ministère) dans ses disciplines ou multisports.
     */
    public function scopeVisiblePar(Builder $query, User $user): Builder
    {
        if ($user->aAccesNational()) {
            return $query;
        }
        $federation = $user->federationCloisonnee();

        return $query->where(fn ($q) => $q->where('talent_competitions.federation_id', $federation)
            ->orWhere(fn ($q) => $q->whereNull('talent_competitions.federation_id')
                ->where(fn ($q) => $q->whereNull('talent_competitions.discipline_id')
                    ->orWhereIn('talent_competitions.discipline_id', Discipline::where('federation_id', $federation)->select('id')))));
    }

    /** Seul le niveau national ou la fédération qui l'a créée peut modifier une compétition. */
    public function estModifiablePar(User $user): bool
    {
        return $user->aAccesNational() || ($this->federation_id && $this->federation_id === $user->federationCloisonnee());
    }
}
