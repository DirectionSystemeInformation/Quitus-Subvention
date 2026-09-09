<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }
}
