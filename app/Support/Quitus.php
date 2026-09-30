<?php

namespace App\Support;

use App\Models\Campaign;
use App\Models\CampaignAllocation;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Collection;
use NumberFormatter;

/**
 * Contenu du quitus de retrait d'une subvention (modèle officiel DSHN) : les
 * activités du programme réaménagé validé, leur total et le montant accordé.
 */
final class Quitus
{
    private function __construct(
        public readonly Campaign $campaign,
        public readonly User $federation,
        public readonly CampaignAllocation $allocation,
        public readonly ?Report $programme,
        public readonly Collection $lignes,
    ) {}

    public static function pour(Campaign $campaign, User $federation): self
    {
        $allocation = $campaign->allocations()->where('user_id', $federation->id)->firstOrFail();

        $programme = $federation->reports()
            ->where('type', 'programme_reamenage')
            ->where('year', $campaign->annee_n1)
            ->first();

        // Les emplacements vides du canevas (ni désignation ni montant) ne sont pas des activités.
        $lignes = $programme
            ? $programme->budgetLines()->orderBy('id')->get()
                ->filter(fn ($ligne) => filled($ligne->designation) || (float) $ligne->montant > 0)
                ->values()
            : collect();

        return new self($campaign, $federation, $allocation, $programme, $lignes);
    }

    public function programmeValide(): bool
    {
        return $this->programme?->status === 'valide';
    }

    public function totalActivites(): float
    {
        return (float) $this->lignes->sum('montant');
    }

    /** Montant accordé à la fédération, tel qu'arrêté par le quitus. */
    public function montantSubvention(): float
    {
        return (float) ($this->allocation->montant_final ?? $this->allocation->montant_arbitre ?? 0);
    }

    public function ecart(): float
    {
        return round($this->totalActivites() - $this->montantSubvention(), 2);
    }

    public static function fcfa(float $montant): string
    {
        return number_format($montant, 0, ',', ' ');
    }

    /** « cent quarante mille » pour 140 000. */
    public static function enLettres(float $montant): string
    {
        return (new NumberFormatter('fr', NumberFormatter::SPELLOUT))->format((int) round($montant));
    }
}
