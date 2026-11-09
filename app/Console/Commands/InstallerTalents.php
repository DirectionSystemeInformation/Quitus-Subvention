<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Talents\AccesTalents;
use Database\Seeders\Talents\ReferentielSeeder;
use Database\Seeders\Talents\RolesPermissionsSeeder;
use Illuminate\Console\Command;

/**
 * Prépare le portail « Suivi des talents » sur une base Quitus existante (sans données fictives) :
 * rôles et permissions, listes de référence, fiches des fédérations actives, rôles des comptes Quitus.
 * Idempotente : peut être relancée après chaque déploiement.
 */
class InstallerTalents extends Command
{
    protected $signature = 'talents:installer';

    protected $description = 'Installe ou met à jour le portail Suivi des talents (rôles, listes, fédérations)';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => RolesPermissionsSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => ReferentielSeeder::class, '--force' => true]);

        $creees = AccesTalents::synchroniserFederations();
        $this->info("Fédérations Quitus rattachées au suivi des talents : $creees nouvelle(s).");

        $comptes = 0;
        User::where('role', '!=', 'talents')->each(function (User $user) use (&$comptes) {
            AccesTalents::synchroniser($user);
            $comptes++;
        });
        $this->info("Rôles talents synchronisés pour $comptes compte(s) Quitus.");

        return self::SUCCESS;
    }
}
