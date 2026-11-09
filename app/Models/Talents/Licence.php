<?php

namespace App\Models\Talents;

use App\Models\Talents\Concerns\AppartientAuTalent;
use App\Models\Talents\Concerns\Journalise;
use Illuminate\Database\Eloquent\Model;

class Licence extends Model
{
    use AppartientAuTalent, Journalise;

    protected $table = 'talent_licences';

    protected $fillable = ['talent_id', 'type_licence', 'portee', 'numero', 'organisme', 'club', 'pays_club', 'date_delivrance', 'date_expiration', 'statut', 'verification_api', 'verifie_le'];

    protected $casts = ['date_delivrance' => 'date', 'date_expiration' => 'date', 'verifie_le' => 'datetime'];

    public const STATUTS = ['Active', 'Expirée', 'Suspendue'];

    /** Volontairement neutre : ne révèle pas qu'un numéro existe déjà (peut-être dans une autre fédération). */
    public const MESSAGE_DOUBLON = 'Ce numéro de licence ne peut pas être enregistré. Vérifiez-le auprès de votre fédération ou contactez le Ministère.';

    public function getExpireBientotAttribute(): bool
    {
        return $this->date_expiration && $this->date_expiration->between(now(), now()->addDays(60));
    }
}
