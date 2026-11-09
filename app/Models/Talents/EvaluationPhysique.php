<?php

namespace App\Models\Talents;

use App\Models\Talents\Concerns\AppartientAuTalent;
use App\Models\Talents\Concerns\Journalise;
use Illuminate\Database\Eloquent\Model;

class EvaluationPhysique extends Model
{
    use AppartientAuTalent, Journalise;

    protected $table = 'talent_evaluations_physiques';

    protected $fillable = ['talent_id', 'date_evaluation', 'taille_cm', 'poids_kg', 'vma_kmh', 'detente_cm', 'sprint_30m_s', 'autres_tests', 'evaluateur', 'avis', 'saisi_par'];

    protected $casts = ['date_evaluation' => 'date', 'autres_tests' => 'array'];

    public function getImcAttribute(): ?float
    {
        return $this->taille_cm && $this->poids_kg
            ? round($this->poids_kg / (($this->taille_cm / 100) ** 2), 1)
            : null;
    }
}
