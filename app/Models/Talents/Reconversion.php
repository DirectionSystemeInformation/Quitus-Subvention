<?php

namespace App\Models\Talents;

use App\Models\Talents\Concerns\AppartientAuTalent;
use App\Models\Talents\Concerns\Journalise;
use Illuminate\Database\Eloquent\Model;

class Reconversion extends Model
{
    use AppartientAuTalent, Journalise;

    protected $table = 'talent_reconversions';

    protected $fillable = ['talent_id', 'annee_fin_carriere', 'motif_retraite', 'niveau_etudes', 'projet', 'domaine', 'statut_accompagnement', 'role_actuel', 'programme_appui', 'besoins'];

    protected $casts = [];
}
