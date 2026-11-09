<?php

namespace App\Http\Controllers\Talents\Modules;

use App\Http\Controllers\Talents\RessourceController;
use App\Models\Talents\Licence;
use App\Models\Talents\Referentiel;
use App\Services\Talents\VerificationLicence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class LicenceController extends RessourceController
{
    protected static string $module = 'licences';

    protected string $modele = Licence::class;

    protected string $route = 'suivi.licences';

    protected string $titre = 'Licences nationales & internationales';

    protected string $singulier = 'Licence';

    protected string $icone = 'bi-card-heading';

    public static function middleware(): array
    {
        return [...parent::middleware(), new Middleware('can:licences.verifier', only: ['verifier'])];
    }

    protected function champs(?Model $objet = null): array
    {
        return [
            'talent_id' => ['label' => 'Talent', 'type' => 'talent', 'requis' => true, 'col' => 6],
            'portee' => ['label' => 'Portée', 'type' => 'select', 'options' => self::options(['Nationale', 'Internationale']), 'requis' => true, 'col' => 3],
            'type_licence' => ['label' => 'Type de licence', 'type' => 'select', 'options' => self::options(Referentiel::liste('type_licence')), 'requis' => true, 'col' => 3],
            'numero' => ['label' => 'N° de licence / ID', 'requis' => true, 'col' => 4, 'aide' => 'Ex : FBF-2026-089 ou FIFA-ID-99210',
                'regles' => ['required', 'string', 'max:60', Rule::unique('talent_licences')->where('organisme', request('organisme'))->ignore($objet?->id)]],
            'organisme' => ['label' => 'Organisme émetteur', 'requis' => true, 'col' => 4, 'aide' => 'Fédération nationale ou instance internationale (FIFA, CAF...)'],
            'statut' => ['label' => 'Statut', 'type' => 'select', 'options' => self::options(Licence::STATUTS), 'requis' => true, 'col' => 4],
            'club' => ['label' => 'Club / organisme expatrié', 'col' => 6],
            'pays_club' => ['label' => 'Pays du club', 'col' => 6],
            'date_delivrance' => ['label' => 'Date de délivrance', 'type' => 'date', 'col' => 6],
            'date_expiration' => ['label' => 'Date d\'expiration', 'type' => 'date', 'col' => 6, 'regles' => ['nullable', 'date', 'after_or_equal:date_delivrance']],
        ];
    }

    protected function messages(): array
    {
        return ['numero.unique' => Licence::MESSAGE_DOUBLON];
    }

    protected function colonnes(): array
    {
        return [
            'Portée' => fn ($l) => badge($l->portee, $l->portee === 'Internationale' ? 'primary' : 'dark'),
            'Type' => fn ($l) => $l->type_licence,
            'N° licence' => fn ($l) => $l->numero,
            'Organisme' => fn ($l) => $l->organisme,
            'Club / pays' => fn ($l) => trim($l->club.($l->pays_club ? " ({$l->pays_club})" : '')),
            'Expiration' => fn ($l) => $l->date_expiration?->format('d/m/Y').($l->expire_bientot ? ' ⚠' : ''),
            'Statut' => fn ($l) => badge($l->statut),
            'Vérif. API' => fn ($l) => badge($l->verification_api),
        ];
    }

    protected function filtres(): array
    {
        return [
            'portee' => ['Portée', self::options(['Nationale', 'Internationale'])],
            'statut' => ['Statut', self::options(Licence::STATUTS)],
            'verification_api' => ['Vérification', self::options(['Non vérifiée', 'Vérifiée', 'Échec'])],
        ];
    }

    protected function filtrerRequete(Builder $query, Request $request): void
    {
        $query->when($request->boolean('expire_bientot'),
            fn ($q) => $q->whereBetween('date_expiration', [now()->toDateString(), now()->addDays(60)->toDateString()]));
    }

    /** Actions supplémentaires affichées sur chaque ligne de la liste. */
    public static function actionsLigne(Model $licence): array
    {
        return $licence->verification_api !== 'Vérifiée'
            ? [['route' => route('suivi.licences.verifier', $licence), 'label' => 'Vérifier via API', 'icone' => 'bi-patch-check', 'permission' => 'licences.verifier']]
            : [];
    }

    public function verifier(Request $request, Licence $licence, VerificationLicence $service)
    {
        $this->trouver($request, $licence->id);
        [$ok, $message] = $service->verifier($licence);

        return back()->with($ok ? 'succes' : 'erreur', $message);
    }
}
