<?php

namespace App\Models\Talents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Specialite extends Model
{
    protected $table = 'talent_specialites';

    protected $fillable = ['discipline_id', 'nom'];

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }
}
