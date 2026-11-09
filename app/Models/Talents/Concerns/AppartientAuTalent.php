<?php

namespace App\Models\Talents\Concerns;

use App\Models\Talents\Talent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Données rattachées à un talent : visibilité héritée de la fédération du talent.
 */
trait AppartientAuTalent
{
    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }

    public function scopeVisiblePar(Builder $query, User $user): Builder
    {
        return $query->whereHas('talent', fn ($q) => $q->visiblePar($user));
    }
}
