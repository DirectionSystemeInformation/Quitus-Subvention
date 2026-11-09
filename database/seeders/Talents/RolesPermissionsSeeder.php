<?php

namespace Database\Seeders\Talents;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('talents.modules') as $module => $def) {
            foreach ($def['actions'] as $action) {
                Permission::findOrCreate("$module.$action");
            }
        }

        $toutVoir = Permission::where('name', 'like', '%.voir')->pluck('name')->all();

        // Rôles par défaut : modifiables ensuite depuis l'écran "Rôles & permissions".
        $roles = [
            'Super administrateur' => Permission::pluck('name')->all(),

            'Ministère des Sports' => [
                ...$toutVoir, 'acces.national', 'talents.valider', 'talents.exporter', 'performances.exporter',
                'bourses.creer', 'bourses.modifier', 'bourses.decider',
                'reconversions.creer', 'reconversions.modifier',
                'federations.modifier', 'disciplines.gerer',
            ],

            'Fédération' => [
                'talents.voir', 'talents.creer', 'talents.modifier', 'talents.exporter',
                'licences.voir', 'licences.creer', 'licences.modifier', 'licences.verifier',
                'competitions.voir', 'competitions.creer', 'competitions.modifier',
                'performances.voir', 'performances.creer', 'performances.modifier', 'performances.exporter',
                'evaluations.voir', 'parcours.voir', 'parcours.creer', 'parcours.modifier',
                'bourses.voir', 'bourses.creer', 'reconversions.voir', 'reconversions.creer', 'reconversions.modifier',
                'entraineurs.voir', 'entraineurs.creer', 'entraineurs.modifier', 'disciplines.voir', 'disciplines.gerer', 'rapports.voir',
            ],

            'Direction Technique (DTN)' => [
                'talents.voir', 'talents.modifier', 'licences.voir',
                'competitions.voir', 'competitions.creer', 'competitions.modifier',
                'performances.voir', 'performances.creer', 'performances.modifier', 'performances.exporter',
                'evaluations.voir', 'evaluations.creer', 'evaluations.modifier',
                'parcours.voir', 'parcours.creer', 'parcours.modifier', 'entraineurs.voir', 'rapports.voir',
            ],

            'Médical & préparation physique' => [
                'acces.national', 'talents.voir', 'evaluations.voir', 'evaluations.creer', 'evaluations.modifier',
                'medical.voir', 'medical.creer', 'medical.modifier',
            ],

            'Entraîneur' => [
                'talents.voir', 'performances.voir', 'performances.creer',
                'evaluations.voir', 'evaluations.creer', 'parcours.voir',
            ],

            'Observateur national' => array_values(array_diff([...$toutVoir, 'acces.national'], ['medical.voir', 'journal.voir'])),
        ];

        foreach ($roles as $nom => $permissions) {
            Role::findOrCreate($nom)->syncPermissions(array_unique($permissions));
        }
    }
}
