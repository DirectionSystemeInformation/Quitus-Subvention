<?php

namespace App\Services\Talents;

use App\Models\Talents\Federation;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Accès au portail « Suivi des talents » pour les comptes de Quitus.
 *
 * Les comptes Quitus (fédérations, DSHN, cabinet...) reçoivent automatiquement leur rôle talents
 * d'après config('talents.correspondance_roles'). Les comptes complémentaires (role = talents)
 * gardent les rôles qui leur sont attribués dans le portail talents.
 */
class AccesTalents
{
    public static function synchroniser(User $user): void
    {
        if ($user->role === 'talents') {
            return;
        }

        $role = config('talents.correspondance_roles.'.$user->role);
        $actif = $user->status === 'active';

        if ($actif && $user->isFederation()) {
            static::federationPour($user);
        }

        $user->syncRoles($role && $actif && Role::where('name', $role)->exists() ? [$role] : []);
        $user->unsetRelation('roles');
    }

    /** Fiche fédération du suivi des talents associée à un compte fédération Quitus (créée au besoin). */
    public static function federationPour(User $user): Federation
    {
        return Federation::firstOrCreate(
            ['user_id' => $user->id],
            ['sigle' => Federation::sigleDisponible($user->federation_name ?: $user->name), 'actif' => true]
        );
    }

    /** Crée les fiches manquantes pour toutes les fédérations actives de Quitus. */
    public static function synchroniserFederations(): int
    {
        $creees = 0;
        User::where('role', 'federation')->where('status', 'active')
            ->whereNotIn('id', Federation::select('user_id'))
            ->each(function (User $user) use (&$creees) {
                static::federationPour($user);
                $creees++;
            });

        return $creees;
    }
}
