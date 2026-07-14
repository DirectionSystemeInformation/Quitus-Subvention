<?php

namespace App\Support;

class ActivityCanvasStructure
{
    /**
     * Structure fixe reproduisant le canevas officiel de la DSHN, utilisée à la fois
     * pour le programme d'activités budgétisé et pour le rapport d'activité.
     * Chaque sous-axe précise son nombre de lignes à remplir.
     *
     * @return array<int, array{code: string, label: string, sous_axes: array<int, array{code: string, label: string, lignes: int}>}>
     */
    public static function axes(): array
    {
        return [
            [
                'code' => 'I',
                'label' => 'Politique Nationale des Sports AXE-1 : MASSE ET RELEVE SPORTIVE',
                'sous_axes' => [
                    ['code' => 'I.1', 'label' => 'Championnat de jeunes et relève sportive', 'lignes' => 3],
                    ['code' => 'I.2', 'label' => 'Autres activités', 'lignes' => 3],
                ],
            ],
            [
                'code' => 'II',
                'label' => 'Politique Nationale des Sports AXE-2 : ELITE',
                'sous_axes' => [
                    ['code' => 'II.1', 'label' => "Championnat national d'élite (1ère et 2ème divisions)", 'lignes' => 3],
                    ['code' => 'II.2', 'label' => 'Activités sous-régionales', 'lignes' => 3],
                    ['code' => 'II.3', 'label' => 'Activités régionales', 'lignes' => 3],
                    ['code' => 'II.4', 'label' => 'Activités continentales', 'lignes' => 3],
                    ['code' => 'II.5', 'label' => 'Activités mondiales', 'lignes' => 3],
                ],
            ],
            [
                'code' => 'III',
                'label' => "Politique Nationale des Sports AXE 3 : CADRE D'EVOLUTION ET CONDITIONS DE MISE EN ŒUVRE",
                'sous_axes' => [
                    ['code' => 'III.1', 'label' => 'Infrastructures', 'lignes' => 2],
                    ['code' => 'III.2', 'label' => 'Equipements', 'lignes' => 2],
                    ['code' => 'III.3', 'label' => 'Fonctionnement', 'lignes' => 2],
                    ['code' => 'III.4', 'label' => 'Réunions des instances nationales et internationales', 'lignes' => 3],
                    ['code' => 'III.5', 'label' => 'Formations des cadres techniques et administratifs', 'lignes' => 3],
                    ['code' => 'III.6', 'label' => 'Côtisations internationales', 'lignes' => 2],
                ],
            ],
        ];
    }

    /**
     * Aplati la structure en une liste de clés uniques "axe|sous_axe_code|numero_ligne"
     * utilisées pour valider et indexer les lignes soumises dans le formulaire.
     *
     * @return array<int, array{axe: string, sous_axe_code: string, sous_axe_label: string, numero_ligne: int}>
     */
    public static function lignes(): array
    {
        $lignes = [];

        foreach (self::axes() as $axe) {
            foreach ($axe['sous_axes'] as $sousAxe) {
                for ($i = 1; $i <= $sousAxe['lignes']; $i++) {
                    $lignes[] = [
                        'axe' => $axe['code'],
                        'sous_axe_code' => $sousAxe['code'],
                        'sous_axe_label' => $sousAxe['label'],
                        'numero_ligne' => $i,
                    ];
                }
            }
        }

        return $lignes;
    }
}
