<?php

namespace App\Models\Talents;

use App\Models\Talents\Concerns\AppartientAuTalent;
use App\Models\Talents\Concerns\Journalise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Performance extends Model
{
    use AppartientAuTalent, Journalise;

    protected $table = 'talent_performances';

    protected $fillable = ['talent_id', 'competition_id', 'date_evenement', 'epreuve', 'performance', 'rang', 'distinction', 'minima_atteint', 'observations_dtn', 'saisi_par'];

    protected $casts = ['date_evenement' => 'date', 'minima_atteint' => 'boolean'];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }
}
