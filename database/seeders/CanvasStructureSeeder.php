<?php

namespace Database\Seeders;

use App\Models\CanvasAxe;
use Illuminate\Database\Seeder;

class CanvasStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $axes = [
            [
                'code' => 'I',
                'label' => 'Politique Nationale des Sports AXE-1 : Masse et relève sportive',
                'sous_axes' => [
                    ['code' => 'I.1', 'label' => 'Championnat de jeunes et relève sportive', 'lignes_count' => 3],
                    ['code' => 'I.2', 'label' => 'Autres activités', 'lignes_count' => 3],
                ],
            ],
            [
                'code' => 'II',
                'label' => 'Politique Nationale des Sports AXE-2 : Élite',
                'sous_axes' => [
                    ['code' => 'II.1', 'label' => "Championnat national d'élite (1ère et 2ème divisions)", 'lignes_count' => 3],
                    ['code' => 'II.2', 'label' => 'Activités sous-régionales', 'lignes_count' => 3],
                    ['code' => 'II.3', 'label' => 'Activités régionales', 'lignes_count' => 3],
                    ['code' => 'II.4', 'label' => 'Activités continentales', 'lignes_count' => 3],
                    ['code' => 'II.5', 'label' => 'Activités mondiales', 'lignes_count' => 3],
                ],
            ],
            [
                'code' => 'III',
                'label' => "Politique Nationale des Sports AXE 3 : Cadre d'évolution et conditions de mise en œuvre",
                'sous_axes' => [
                    ['code' => 'III.1', 'label' => 'Infrastructures', 'lignes_count' => 2],
                    ['code' => 'III.2', 'label' => 'Equipements', 'lignes_count' => 2],
                    ['code' => 'III.3', 'label' => 'Fonctionnement', 'lignes_count' => 2],
                    ['code' => 'III.4', 'label' => 'Réunions des instances nationales et internationales', 'lignes_count' => 3],
                    ['code' => 'III.5', 'label' => 'Formations des cadres techniques et administratifs', 'lignes_count' => 3],
                    ['code' => 'III.6', 'label' => 'Côtisations internationales', 'lignes_count' => 2],
                ],
            ],
        ];

        foreach ($axes as $axeIndex => $axeData) {
            $axe = CanvasAxe::create([
                'code' => $axeData['code'],
                'label' => $axeData['label'],
                'sort_order' => $axeIndex,
            ]);

            foreach ($axeData['sous_axes'] as $sousAxeIndex => $sousAxeData) {
                $axe->sousAxes()->create([
                    'code' => $sousAxeData['code'],
                    'label' => $sousAxeData['label'],
                    'lignes_count' => $sousAxeData['lignes_count'],
                    'sort_order' => $sousAxeIndex,
                ]);
            }
        }
    }
}
