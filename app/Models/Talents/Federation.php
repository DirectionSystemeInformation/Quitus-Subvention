<?php

namespace App\Models\Talents;

use App\Models\Talents\Concerns\Journalise;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Fédération du suivi des talents : prolongement d'un compte fédération de Quitus.
 * Le nom et l'e-mail viennent du compte Quitus ; seuls les réglages propres aux talents sont stockés ici.
 */
class Federation extends Model
{
    use Journalise;

    protected $table = 'talent_federations';

    protected $fillable = ['user_id', 'sigle', 'prefixe_licence', 'president', 'telephone', 'adresse',
        'api_url', 'api_token', 'actif'];

    protected $hidden = ['api_token'];

    protected $casts = ['actif' => 'boolean', 'api_token' => 'encrypted'];

    protected $with = ['user'];

    /** Compte fédération de Quitus. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getNomAttribute(): string
    {
        return $this->user?->federation_name ?: $this->user?->name ?: $this->sigle;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    public function disciplines(): HasMany
    {
        return $this->hasMany(Discipline::class);
    }

    public function talents(): HasMany
    {
        return $this->hasMany(Talent::class);
    }

    /** Comptes complémentaires (DTN, médecin...) rattachés à la fédération. */
    public function comptes(): HasMany
    {
        return $this->hasMany(User::class, 'talent_federation_id');
    }

    /**
     * Sigle proposé à partir du nom Quitus : « Fédération Burkinabè d'Athlétisme » → FBA.
     * Un suffixe numérique garantit l'unicité ; le Ministère peut ensuite le corriger.
     */
    public static function sigleDisponible(string $nom): string
    {
        $mots = preg_split('/[\s\-]+/u', trim(Str::ascii($nom)), -1, PREG_SPLIT_NO_EMPTY);
        $initiales = '';
        foreach ($mots as $mot) {
            $mot = preg_replace("/^(d|l)'/i", '', $mot);
            if ($mot !== '' && ! in_array(mb_strtolower($mot), ['de', 'du', 'des', 'la', 'le', 'les', 'et', 'pour', 'en'], true)) {
                $initiales .= mb_strtoupper(mb_substr($mot, 0, 1));
            }
        }
        $base = mb_substr(strlen($initiales) >= 2 ? $initiales : mb_strtoupper(mb_substr(Str::ascii($nom), 0, 3)), 0, 15) ?: 'FED';

        $sigle = $base;
        for ($i = 2; static::where('sigle', $sigle)->exists(); $i++) {
            $sigle = $base.$i;
        }

        return $sigle;
    }

    public function libelleJournal(): string
    {
        return "{$this->sigle} - {$this->nom}";
    }
}
