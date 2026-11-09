<?php

namespace App\Http\Controllers\Talents\Modules;

use App\Http\Controllers\Talents\RessourceController;
use App\Models\Talents\Federation;
use App\Services\Talents\AccesTalents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Réglages « talents » des fédérations. Les fédérations elles-mêmes sont créées, validées
 * et désactivées dans Quitus (espace DSHN) : ce module n'en crée ni n'en supprime.
 */
class FederationController extends RessourceController
{
    protected static string $module = 'federations';

    protected string $modele = Federation::class;

    protected string $route = 'suivi.federations';

    protected string $titre = 'Fédérations (comptes Quitus)';

    protected string $singulier = 'Fédération';

    protected string $icone = 'bi-bank';

    protected bool $lieAuTalent = false;

    public function index(Request $request)
    {
        // Une fédération validée dans Quitus apparaît ici sans autre démarche.
        AccesTalents::synchroniserFederations();

        return parent::index($request);
    }

    protected function champs(?Model $objet = null): array
    {
        return [
            'sigle' => ['label' => 'Sigle', 'requis' => true, 'col' => 3, 'aide' => 'Utilisé dans les codes talents (TLT-SIGLE-...)',
                'regles' => ['required', 'max:20', 'alpha_dash', Rule::unique('talent_federations')->ignore($objet?->id)]],
            'president' => ['label' => 'Président(e)', 'col' => 5],
            'telephone' => ['label' => 'Téléphone', 'col' => 4],
            'adresse' => ['label' => 'Adresse', 'col' => 8],
            'prefixe_licence' => ['label' => 'Préfixe des licences', 'col' => 4, 'aide' => 'Ex : FBF → FBF-2026-001'],
            'api_url' => ['label' => 'URL de l\'API licences', 'type' => 'url', 'col' => 8, 'regles' => ['nullable', 'url'],
                'aide' => 'La plateforme appelle {URL}/licences/{numéro} pour vérifier une licence.'],
            'api_token' => ['label' => 'Jeton API (vide = inchangé)', 'type' => 'password', 'col' => 4],
            'actif' => ['label' => 'Active dans le suivi des talents', 'type' => 'checkbox', 'col' => 12, 'defaut' => true],
        ];
    }

    protected function colonnes(): array
    {
        return [
            'Sigle' => fn ($f) => badge($f->sigle, 'dark'),
            'Fédération (Quitus)' => fn ($f) => $f->nom,
            'E-mail' => fn ($f) => $f->email,
            'Disciplines' => fn ($f) => $f->disciplines->pluck('nom')->implode(', '),
            'Talents' => fn ($f) => $f->talents_count,
            'API' => fn ($f) => $f->api_url ? badge('Connectée', 'success') : badge('Manuelle', 'secondary'),
            'Compte Quitus' => fn ($f) => badge($f->user?->status === 'active' ? 'Actif' : 'Inactif'),
            'Suivi talents' => fn ($f) => $f->actif ? badge('Active') : badge('Inactive', 'secondary'),
        ];
    }

    protected function requete(Request $request): Builder
    {
        return parent::requete($request)->with('disciplines')->withCount('talents')->reorder('sigle');
    }

    protected function filtrerRequete(Builder $query, Request $request): void
    {
        $query->when($request->query('q'), fn ($q, $v) => $q->where(fn ($q) => $q->where('sigle', 'like', "%$v%")
            ->orWhereHas('user', fn ($u) => $u->where('federation_name', 'like', "%$v%"))));
    }

    protected function avantSauvegarde(Request $request, Model $objet): void
    {
        if (! $request->filled('api_token')) {
            $objet->api_token = $objet->getOriginal('api_token');
        }
    }
}
