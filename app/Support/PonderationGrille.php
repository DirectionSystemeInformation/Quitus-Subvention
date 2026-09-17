<?php

namespace App\Support;

use App\Models\PonderationPalier;
use App\Models\PonderationRubrique;

/**
 * Grille de pondération et de catégorisation : rubriques, critères et paliers.
 *
 * Paramétrée par la DSHN (tables ponderation_*), mais figée par campagne : au
 * lancement de la pondération, la grille en vigueur est copiée dans la campagne
 * (campaigns.grille_ponderation) pour que ses résultats restent reproductibles
 * même si le paramétrage évolue ensuite.
 */
class PonderationGrille
{
    /**
     * @param  array<int, array{label: string, criteres: array<int, array{slug: string, label: string, points_max: float}>}>  $rubriques
     * @param  array<int, array{code: string, categorie: string, seuil_min: float}>  $paliers
     */
    public function __construct(
        private array $rubriques,
        private array $paliers,
    ) {}

    /**
     * Grille actuellement paramétrée par la DSHN.
     */
    public static function current(): self
    {
        $rubriques = PonderationRubrique::with('criteres')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (PonderationRubrique $rubrique) => [
                'label' => $rubrique->label,
                'criteres' => $rubrique->criteres
                    ->map(fn ($critere) => [
                        'slug' => $critere->slug,
                        'label' => $critere->label,
                        'points_max' => (float) $critere->points_max,
                    ])
                    ->all(),
            ])
            ->all();

        $paliers = PonderationPalier::orderBy('seuil_min')
            ->get()
            ->map(fn (PonderationPalier $palier) => [
                'code' => $palier->code,
                'categorie' => $palier->categorie,
                'seuil_min' => (float) $palier->seuil_min,
            ])
            ->all();

        return new self($rubriques, $paliers);
    }

    /**
     * Grille figée telle qu'enregistrée sur une campagne.
     */
    public static function fromSnapshot(array $snapshot): self
    {
        return new self($snapshot['rubriques'] ?? [], $snapshot['paliers'] ?? []);
    }

    public function toSnapshot(): array
    {
        return ['rubriques' => $this->rubriques, 'paliers' => $this->paliers];
    }

    public function isEmpty(): bool
    {
        return $this->criteres() === [];
    }

    /**
     * Rubriques avec leur total de points (somme des critères).
     *
     * @return array<int, array{rubrique: string, rubrique_max: float, criteres: array}>
     */
    public function rubriques(): array
    {
        return array_map(fn (array $rubrique) => [
            'rubrique' => $rubrique['label'],
            'rubrique_max' => array_sum(array_column($rubrique['criteres'], 'points_max')),
            'criteres' => array_map(fn (array $critere) => $critere + ['max' => $critere['points_max']], $rubrique['criteres']),
        ], $this->rubriques);
    }

    /**
     * Tous les critères, toutes rubriques confondues.
     *
     * @return array<int, array{slug: string, label: string, max: float, rubrique: string}>
     */
    public function criteres(): array
    {
        $criteres = [];

        foreach ($this->rubriques() as $rubrique) {
            foreach ($rubrique['criteres'] as $critere) {
                $criteres[] = $critere + ['rubrique' => $rubrique['rubrique']];
            }
        }

        return $criteres;
    }

    /**
     * Total des points de la grille (100 dans la grille officielle).
     */
    public function pointsMax(): float
    {
        return array_sum(array_column($this->rubriques(), 'rubrique_max'));
    }

    /**
     * @param  array<string, float>  $scores  scores indexés par slug de critère
     * @return array<string, float>  sous-totaux indexés par nom de rubrique
     */
    public function sousTotauxParRubrique(array $scores): array
    {
        $sousTotaux = [];

        foreach ($this->rubriques() as $rubrique) {
            $sousTotaux[$rubrique['rubrique']] = 0.0;
            foreach ($rubrique['criteres'] as $critere) {
                $sousTotaux[$rubrique['rubrique']] += (float) ($scores[$critere['slug']] ?? 0);
            }
        }

        return $sousTotaux;
    }

    /**
     * @param  array<string, float>  $scores
     */
    public function total(array $scores): float
    {
        return array_sum($this->sousTotauxParRubrique($scores));
    }

    /**
     * Palier correspondant à un score : celui dont le seuil minimum est le plus
     * élevé sans dépasser le score. Un score nul n'est pas classé — une
     * fédération sans aucun point n'entre dans aucune catégorie.
     *
     * @return array{code: string, categorie: string, seuil_min: float}|null
     */
    public function palierPour(float $total): ?array
    {
        if ($total <= 0.0) {
            return null;
        }

        $trouve = null;

        foreach ($this->paliers as $palier) {
            if ($total >= $palier['seuil_min'] && ($trouve === null || $palier['seuil_min'] >= $trouve['seuil_min'])) {
                $trouve = $palier;
            }
        }

        return $trouve;
    }

    /**
     * Catégorie principale (D/C/B/A dans la grille officielle).
     */
    public function categorie(float $total): string
    {
        return $this->palierPour($total)['categorie'] ?? 'NON_CLASSEE';
    }

    /**
     * Catégorie ajustée — le palier lui-même (D3 … A1 dans la grille officielle).
     */
    public function categorieAjustee(float $total): string
    {
        return $this->palierPour($total)['code'] ?? 'NON_CLASSEE';
    }

    /**
     * Codes des paliers, du plus faible au plus élevé (barème de répartition).
     *
     * @return array<int, string>
     */
    public function paliers(): array
    {
        $paliers = $this->paliers;
        usort($paliers, fn ($a, $b) => $a['seuil_min'] <=> $b['seuil_min']);

        return array_column($paliers, 'code');
    }
}
