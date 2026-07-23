<?php

namespace App\Support;

use App\Models\CanvasAxe;

class ActivityCanvasStructure
{
    /**
     * Structure du canevas officiel de la DSHN, utilisée à la fois pour le programme
     * d'activités budgétisé et pour le rapport d'activité. Modifiable par le DSHN
     * (tables canvas_axes / canvas_sous_axes) — toujours la version en vigueur.
     *
     * @return array<int, array{code: string, label: string, sous_axes: array<int, array{code: string, label: string, lignes: int}>}>
     */
    public static function axes(): array
    {
        return CanvasAxe::with('sousAxes')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (CanvasAxe $axe) => [
                'code' => $axe->code,
                'label' => $axe->label,
                'sous_axes' => $axe->sousAxes->map(fn ($sousAxe) => [
                    'id' => $sousAxe->id,
                    'code' => $sousAxe->code,
                    'label' => $sousAxe->label,
                    'lignes' => $sousAxe->lignes_count,
                ])->all(),
            ])
            ->all();
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
                        'canvas_sous_axe_id' => $sousAxe['id'],
                        'axe' => $axe['code'],
                        'axe_label' => $axe['label'],
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
