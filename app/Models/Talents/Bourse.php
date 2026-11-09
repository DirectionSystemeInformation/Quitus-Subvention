<?php

namespace App\Models\Talents;

use App\Models\Talents\Concerns\AppartientAuTalent;
use App\Models\Talents\Concerns\Journalise;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bourse extends Model
{
    use AppartientAuTalent, Journalise;

    protected $table = 'talent_bourses';

    protected $fillable = ['talent_id', 'type_bourse', 'financeur', 'montant', 'devise', 'date_debut', 'date_fin', 'statut', 'observations', 'decide_par', 'decide_le'];

    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date', 'decide_le' => 'datetime'];

    public const STATUTS = ['Demandée', 'Accordée', 'En cours', 'Terminée', 'Refusée'];

    public function decideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decide_par');
    }
}
