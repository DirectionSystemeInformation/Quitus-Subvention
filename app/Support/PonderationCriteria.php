<?php

namespace App\Support;

class PonderationCriteria
{
    /**
     * Grille officielle de pondération des activités (Plénière 2025) : 7 rubriques,
     * 25 critères, 100 points au total. Fixe — ne dépend d'aucune donnée déjà saisie
     * dans l'application (aucune donnée médailles/licenciés/gouvernance n'existe en
     * base), la DSHN saisit directement le nombre de points obtenu par critère.
     *
     * @return array<int, array{rubrique: string, rubrique_max: int, criteres: array<int, array{slug: string, label: string, max: float}>}>
     */
    public static function rubriques(): array
    {
        return [
            [
                'rubrique' => 'Gouvernance',
                'rubrique_max' => 18,
                'criteres' => [
                    ['slug' => 'gouv_1', 'label' => 'Tenues des instances statutaires', 'max' => 1],
                    ['slug' => 'gouv_2', 'label' => 'Existence de cadre juridique', 'max' => 2],
                    ['slug' => 'gouv_3', 'label' => "Mise en œuvre du programme d'activité", 'max' => 6],
                    ['slug' => 'gouv_4', 'label' => 'Existence du document de planification', 'max' => 4],
                    ['slug' => 'gouv_5', 'label' => 'Prise en compte du genre dans les instances', 'max' => 1],
                    ['slug' => 'gouv_6', 'label' => 'Formation des cadres administratifs et techniques', 'max' => 4],
                ],
            ],
            [
                'rubrique' => 'Activités sportives et de loisirs',
                'rubrique_max' => 14,
                'criteres' => [
                    ['slug' => 'acti_1', 'label' => 'Tenues des championnats nationaux', 'max' => 8],
                    ['slug' => 'acti_2', 'label' => 'Participation aux compétitions au plan international', 'max' => 6],
                ],
            ],
            [
                'rubrique' => 'Performances sportives',
                'rubrique_max' => 30,
                'criteres' => [
                    ['slug' => 'perf_1', 'label' => 'Médailles remportées par niveau de compétitions', 'max' => 8],
                    ['slug' => 'perf_2', 'label' => 'Inscription de la discipline au CIO', 'max' => 5],
                    ['slug' => 'perf_3', 'label' => "Existence de centre d'excellence pour sportifs fonctionnel", 'max' => 4],
                    ['slug' => 'perf_4', 'label' => 'Existence de championnats de catégories jeunes (relève sportive)', 'max' => 8],
                    ['slug' => 'perf_5', 'label' => 'Athlètes licenciés', 'max' => 5],
                ],
            ],
            [
                'rubrique' => 'Financement',
                'rubrique_max' => 15,
                'criteres' => [
                    ['slug' => 'fin_1', 'label' => 'Capacité de mobilisation des ressources financières', 'max' => 5],
                    ['slug' => 'fin_2', 'label' => 'Reddition des comptes dans les délais', 'max' => 5],
                    ['slug' => 'fin_3', 'label' => 'Capacité à générer des ressources propres', 'max' => 5],
                ],
            ],
            [
                'rubrique' => 'Couverture territoriale',
                'rubrique_max' => 12,
                'criteres' => [
                    ['slug' => 'couv_1', 'label' => 'Capacité à couvrir le territoire national', 'max' => 12],
                ],
            ],
            [
                'rubrique' => 'Infrastructures propres à la structure',
                'rubrique_max' => 4,
                'criteres' => [
                    ['slug' => 'infra_1', 'label' => 'Siège fonctionnel', 'max' => 1],
                    ['slug' => 'infra_2', 'label' => "Existence d'un centre de formation opérationnel", 'max' => 2],
                    ['slug' => 'infra_3', 'label' => "Existence d'installations sportives", 'max' => 1],
                ],
            ],
            [
                'rubrique' => 'Capacité opérationnelle',
                'rubrique_max' => 7,
                'criteres' => [
                    ['slug' => 'capa_1', 'label' => 'Disponibilité de moyens roulants', 'max' => 1],
                    ['slug' => 'capa_2', 'label' => 'Disponibilité de personnels permanents', 'max' => 2],
                    ['slug' => 'capa_3', 'label' => 'Disponibilité de personnels techniques qualifiés', 'max' => 4],
                ],
            ],
        ];
    }

    /**
     * Aplati tous les critères de toutes les rubriques en une seule liste.
     *
     * @return array<int, array{slug: string, label: string, max: float, rubrique: string}>
     */
    public static function criteres(): array
    {
        $criteres = [];

        foreach (self::rubriques() as $rubrique) {
            foreach ($rubrique['criteres'] as $critere) {
                $criteres[] = $critere + ['rubrique' => $rubrique['rubrique']];
            }
        }

        return $criteres;
    }

    /**
     * Calcule le sous-total de chaque rubrique à partir des scores saisis.
     *
     * @param  array<string, float>  $scores  scores indexés par slug de critère
     * @return array<string, float>  sous-totaux indexés par nom de rubrique
     */
    public static function sousTotauxParRubrique(array $scores): array
    {
        $sousTotaux = [];

        foreach (self::rubriques() as $rubrique) {
            $sousTotaux[$rubrique['rubrique']] = 0.0;
            foreach ($rubrique['criteres'] as $critere) {
                $sousTotaux[$rubrique['rubrique']] += (float) ($scores[$critere['slug']] ?? 0);
            }
        }

        return $sousTotaux;
    }

    /**
     * Calcule le score total (0 à 100) à partir des scores saisis.
     *
     * @param  array<string, float>  $scores
     */
    public static function total(array $scores): float
    {
        return array_sum(self::sousTotauxParRubrique($scores));
    }

    /**
     * Catégorie principale (formule officielle) : D/C/B/A.
     */
    public static function categorie(float $total): string
    {
        return match (true) {
            $total <= 25 => 'D',
            $total < 51 => 'C',
            $total < 75 => 'B',
            default => 'A',
        };
    }

    /**
     * Catégorie ajustée à 12 paliers (formule officielle).
     */
    public static function categorieAjustee(float $total): string
    {
        return match (true) {
            $total == 0.0 => 'NON_CLASSEE',
            $total < 20 => 'D3',
            $total < 23 => 'D2',
            $total < 26 => 'D1',
            $total < 36 => 'C3',
            $total < 46 => 'C2',
            $total < 51 => 'C1',
            $total < 56 => 'B3',
            $total < 61 => 'B2',
            $total < 76 => 'B1',
            $total < 86 => 'A3',
            $total < 96 => 'A2',
            default => 'A1',
        };
    }

    /**
     * Liste ordonnée des 12 paliers de catégorie ajustée (pour construire le barème
     * de répartition, du plus faible au plus élevé).
     *
     * @return array<int, string>
     */
    public static function paliersCategorieAjustee(): array
    {
        return ['D3', 'D2', 'D1', 'C3', 'C2', 'C1', 'B3', 'B2', 'B1', 'A3', 'A2', 'A1'];
    }
}
