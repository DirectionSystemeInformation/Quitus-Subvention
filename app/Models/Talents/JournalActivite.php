<?php

namespace App\Models\Talents;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalActivite extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'talent_journal';

    protected $fillable = ['user_id', 'action', 'module', 'objet_id', 'libelle', 'modifications', 'ip'];

    protected $casts = ['modifications' => 'array', 'created_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
