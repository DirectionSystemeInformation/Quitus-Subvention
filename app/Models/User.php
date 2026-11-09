<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Talents\Federation as FederationTalents;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    private ?FederationTalents $federationTalentsResolue = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'federation_name',
        'arrete_numero',
        'arrete_date',
        'rejection_reason',
        'talent_federation_id',
        'telephone',
        'fonction',
        'talent_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'arrete_date' => 'date',
            'talent_preferences' => 'array',
        ];
    }

    public function isFederation(): bool
    {
        return $this->role === 'federation';
    }

    public function isDshn(): bool
    {
        return in_array($this->role, ['dshn', 'admin'], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDg(): bool
    {
        return $this->role === 'dg';
    }

    public function isComiteArbitrage(): bool
    {
        return $this->role === 'comite_arbitrage';
    }

    public function isMinistre(): bool
    {
        return $this->role === 'ministre';
    }

    public function isDgf(): bool
    {
        return $this->role === 'dgf';
    }

    public function isCampaignActor(): bool
    {
        return $this->isDshn() || $this->isDg() || $this->isComiteArbitrage() || $this->isMinistre();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function activities()
    {
        return $this->hasMany(FederationActivity::class);
    }

    // ---- Portail « Suivi des talents » ----

    /** Compte complémentaire du suivi des talents (DTN, médecin...) : pas d'accès à Quitus. */
    public function isComptesTalents(): bool
    {
        return $this->role === 'talents';
    }

    public function aAccesQuitus(): bool
    {
        return ! $this->isComptesTalents();
    }

    public function aAccesTalents(): bool
    {
        return $this->isActive() && $this->roles->isNotEmpty();
    }

    /** Ministère, administrateurs, observateurs nationaux : vision sur toutes les fédérations. */
    public function aAccesNational(): bool
    {
        return $this->can('acces.national');
    }

    /** Fédération du suivi des talents : la sienne pour un compte fédération, celle de rattachement sinon. */
    public function federationTalents(): ?FederationTalents
    {
        return $this->federationTalentsResolue ??= $this->isFederation()
            ? FederationTalents::where('user_id', $this->id)->first()
            : ($this->talent_federation_id ? FederationTalents::find($this->talent_federation_id) : null);
    }

    /**
     * Fédération à laquelle limiter les données. 0 = aucune : un compte sans fédération
     * ni accès national ne voit rien, plutôt que tout.
     */
    public function federationCloisonnee(): int
    {
        return (int) $this->federationTalents()?->id;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }
}
