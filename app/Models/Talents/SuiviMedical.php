<?php

namespace App\Models\Talents;

use App\Models\Talents\Concerns\AppartientAuTalent;
use App\Models\Talents\Concerns\Journalise;
use Illuminate\Database\Eloquent\Model;

class SuiviMedical extends Model
{
    use AppartientAuTalent, Journalise;

    protected $table = 'talent_suivis_medicaux';

    protected $fillable = ['talent_id', 'date_consultation', 'statut_medical', 'type_blessure', 'localisation', 'date_blessure', 'date_retour_prevue', 'jours_indisponibilite', 'cloture', 'medecin', 'avis_medical', 'saisi_par'];

    protected $casts = ['date_consultation' => 'date', 'date_blessure' => 'date', 'date_retour_prevue' => 'date', 'cloture' => 'boolean'];
}
