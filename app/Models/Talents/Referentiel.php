<?php

namespace App\Models\Talents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Listes de valeurs paramétrables par l'administrateur (statuts, motifs, types...).
 */
class Referentiel extends Model
{
    protected $table = 'talent_referentiels';

    public const TYPES = [
        'type_licence' => 'Types de licence',
        'statut_carriere' => 'Statuts de carrière',
        'niveau_competition' => 'Niveaux de compétition',
        'distinction' => 'Distinctions / médailles',
        'statut_medical' => 'Statuts médicaux',
        'statut_contractuel' => 'Statuts contractuels',
        'type_bourse' => 'Types de bourse / appui',
        'motif_retraite' => 'Motifs de retraite',
        'domaine_reconversion' => 'Domaines de reconversion',
        'statut_accompagnement' => 'Statuts d\'accompagnement',
        'region' => 'Régions',
    ];

    protected $fillable = ['type', 'valeur', 'couleur', 'ordre', 'actif'];

    protected $casts = ['actif' => 'boolean'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('referentiels'));
        static::deleted(fn () => Cache::forget('referentiels'));
    }

    public static function liste(string $type): Collection
    {
        $tous = Cache::rememberForever('referentiels', fn () => static::where('actif', true)
            ->orderBy('ordre')->orderBy('valeur')->get(['type', 'valeur', 'couleur'])->groupBy('type'));

        return ($tous[$type] ?? collect())->pluck('valeur');
    }
}
