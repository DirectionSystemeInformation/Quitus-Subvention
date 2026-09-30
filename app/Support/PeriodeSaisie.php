<?php

namespace App\Support;

use App\Models\OuvertureSaisie;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Années pour lesquelles une fédération peut saisir.
 *
 * De droit : l'année en cours pour les activités réalisées, l'année N+1 pour
 * les programmes budgétisé et réaménagé. Toute autre année n'est ouverte que
 * par une ouverture exceptionnelle accordée par l'administration.
 */
final class PeriodeSaisie
{
    public const ACTIVITES = 'activites';

    public const PROGRAMME_BUDGETISE = 'programme_budgetise';

    public const PROGRAMME_REAMENAGE = 'programme_reamenage';

    public const LIBELLES = [
        self::ACTIVITES => 'Activités réalisées',
        self::PROGRAMME_BUDGETISE => 'Programme budgétisé',
        self::PROGRAMME_REAMENAGE => 'Programme réaménagé',
    ];

    public static function anneeDeDroit(string $type): int
    {
        return $type === self::ACTIVITES ? (int) now()->year : (int) now()->year + 1;
    }

    /** Ouvertures exceptionnelles en cours pour cette fédération et ce type. */
    public static function ouvertures(User $federation, string $type): Collection
    {
        return OuvertureSaisie::active()
            ->where('user_id', $federation->id)
            ->where('type', $type)
            ->orderByDesc('year')
            ->get();
    }

    /** @return Collection<int, int> années ouvertes, la plus récente d'abord */
    public static function anneesOuvertes(User $federation, string $type): Collection
    {
        return collect([self::anneeDeDroit($type)])
            ->merge(self::ouvertures($federation, $type)->pluck('year'))
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values();
    }

    public static function estOuverte(User $federation, string $type, int|string|null $year): bool
    {
        return $year !== null && self::anneesOuvertes($federation, $type)->contains((int) $year);
    }

    public static function messageFerme(string $type, int|string $year): string
    {
        return 'La saisie « '.self::LIBELLES[$type].' » '.$year.' est close : seule l’année '
            .self::anneeDeDroit($type).' est ouverte. Contactez l’administration pour une ouverture exceptionnelle.';
    }
}
