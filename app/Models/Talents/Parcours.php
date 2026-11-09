<?php

namespace App\Models\Talents;

use App\Models\Talents\Concerns\AppartientAuTalent;
use App\Models\Talents\Concerns\Journalise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Parcours extends Model
{
    use AppartientAuTalent, Journalise;

    protected $table = 'talent_parcours';

    protected $fillable = ['talent_id', 'saison', 'club', 'pays', 'statut_contractuel', 'date_debut', 'date_fin', 'entraineur_id', 'niveau_scolaire', 'commentaires'];

    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date'];

    public function entraineur(): BelongsTo
    {
        return $this->belongsTo(Entraineur::class);
    }
}
