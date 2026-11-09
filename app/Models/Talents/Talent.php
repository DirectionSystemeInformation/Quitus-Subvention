<?php

namespace App\Models\Talents;

use App\Models\Talents\Concerns\Journalise;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Talent extends Model
{
    use Journalise, SoftDeletes;

    protected $table = 'talents';

    public const STATUTS_PROFIL = ['Brouillon', 'En attente API', 'En attente validation', 'Validé', 'Rejeté'];

    public const CATEGORIES_AGE = [
        'U17' => [0, 16], 'U20' => [17, 19], 'U23' => [20, 22], 'Senior' => [23, 34], 'Vétéran' => [35, 120],
    ];

    protected $fillable = ['nom', 'prenoms', 'date_naissance', 'lieu_naissance', 'genre', 'nationalites',
        'region', 'telephone', 'email', 'photo', 'federation_id', 'discipline_id', 'specialite',
        'entraineur_id', 'statut_carriere', 'statut_profil', 'haut_potentiel', 'observations'];

    protected $casts = [
        'date_naissance' => 'date',
        'valide_le' => 'datetime',
        'haut_potentiel' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Talent $talent) {
            $talent->code ??= static::genererCode($talent->federation_id);
            $talent->created_by ??= auth()->id();
        });
    }

    /**
     * Identifiant unique : TLT-SIGLE-AAAA-NNN. La séquence est propre à chaque fédération,
     * pour qu'un numéro ne trahisse pas le nombre de talents des autres fédérations.
     */
    public static function genererCode(int $federationId, ?int $annee = null): string
    {
        $sigle = Federation::whereKey($federationId)->value('sigle');
        $prefixe = 'TLT-'.$sigle.'-'.($annee ?? now()->year).'-';
        $dernier = static::withTrashed()->where('code', 'like', $prefixe.'%')->max('code');
        $suivant = $dernier ? ((int) substr($dernier, strlen($prefixe))) + 1 : 1;

        return $prefixe.str_pad((string) $suivant, 3, '0', STR_PAD_LEFT);
    }

    // ---- Accesseurs ----

    public function getNomCompletAttribute(): string
    {
        return mb_strtoupper($this->nom).' '.$this->prenoms;
    }

    public function getAgeAttribute(): int
    {
        return $this->date_naissance->age;
    }

    public function getCategorieAgeAttribute(): string
    {
        return static::categoriePourAge($this->age);
    }

    public static function categoriePourAge(int $age): string
    {
        foreach (static::CATEGORIES_AGE as $categorie => [$min, $max]) {
            if ($age >= $min && $age <= $max) {
                return $categorie;
            }
        }

        return 'Vétéran';
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::url($this->photo) : null;
    }

    public function getInitialesAttribute(): string
    {
        return mb_substr($this->nom, 0, 1).mb_substr($this->prenoms, 0, 1);
    }

    // ---- Relations ----

    public function federation(): BelongsTo
    {
        return $this->belongsTo(Federation::class);
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    public function entraineur(): BelongsTo
    {
        return $this->belongsTo(Entraineur::class);
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function licences(): HasMany
    {
        return $this->hasMany(Licence::class);
    }

    public function licenceNationale(): HasOne
    {
        return $this->hasOne(Licence::class)->where('portee', 'Nationale')->latestOfMany();
    }

    public function licenceInternationale(): HasOne
    {
        return $this->hasOne(Licence::class)->where('portee', 'Internationale')->latestOfMany();
    }

    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class)->orderByDesc('date_evenement');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(EvaluationPhysique::class)->orderByDesc('date_evaluation');
    }

    public function suivisMedicaux(): HasMany
    {
        return $this->hasMany(SuiviMedical::class)->orderByDesc('date_consultation');
    }

    public function parcours(): HasMany
    {
        return $this->hasMany(Parcours::class)->orderByDesc('saison');
    }

    public function bourses(): HasMany
    {
        return $this->hasMany(Bourse::class)->orderByDesc('date_debut');
    }

    public function reconversion(): HasOne
    {
        return $this->hasOne(Reconversion::class);
    }

    // ---- Scopes ----

    /** Un compte fédération ne voit que les talents de sa fédération ; le niveau national voit tout. */
    public function scopeVisiblePar(Builder $query, User $user): Builder
    {
        return $user->aAccesNational() ? $query : $query->where('talents.federation_id', $user->federationCloisonnee());
    }

    /** Filtres communs aux listes, au tableau de bord et aux exports. */
    public function scopeFiltrer(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q
                ->where('talents.nom', 'like', "%$v%")
                ->orWhere('talents.prenoms', 'like', "%$v%")
                ->orWhere('talents.code', 'like', "%$v%")))
            ->when($f['federation_id'] ?? null, fn ($q, $v) => $q->where('talents.federation_id', $v))
            ->when($f['discipline_id'] ?? null, fn ($q, $v) => $q->where('talents.discipline_id', $v))
            ->when($f['genre'] ?? null, fn ($q, $v) => $q->where('talents.genre', $v))
            ->when($f['statut_carriere'] ?? null, fn ($q, $v) => $q->where('talents.statut_carriere', $v))
            ->when($f['statut_profil'] ?? null, fn ($q, $v) => $q->where('talents.statut_profil', $v))
            ->when($f['region'] ?? null, fn ($q, $v) => $q->where('talents.region', $v))
            ->when(! empty($f['haut_potentiel']), fn ($q) => $q->where('talents.haut_potentiel', true))
            ->when($f['categorie'] ?? null, function ($q, $categorie) {
                [$min, $max] = static::CATEGORIES_AGE[$categorie] ?? [0, 120];
                $q->whereBetween('talents.date_naissance', [
                    now()->subYears($max + 1)->addDay()->toDateString(),
                    now()->subYears($min)->toDateString(),
                ]);
            });
    }

    public function libelleJournal(): string
    {
        return "{$this->code} - {$this->nom_complet}";
    }
}
