<?php

namespace App\Http\Controllers\Talents\Modules;

use App\Http\Controllers\Talents\RessourceController;
use App\Models\Talents\Discipline;
use App\Models\Talents\Entraineur;
use App\Models\Talents\Federation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class EntraineurController extends RessourceController
{
    protected static string $module = 'entraineurs';

    protected string $modele = Entraineur::class;

    protected string $route = 'suivi.entraineurs';

    protected string $titre = 'Encadrement technique (entraîneurs)';

    protected string $singulier = 'Entraîneur';

    protected string $icone = 'bi-person-badge';

    protected bool $lieAuTalent = false;

    protected array $with = ['discipline', 'federation'];

    protected function champs(?Model $objet = null): array
    {
        $user = request()->user();

        return [
            'nom' => ['label' => 'Nom', 'requis' => true, 'col' => 4],
            'prenoms' => ['label' => 'Prénom(s)', 'requis' => true, 'col' => 4],
            'diplome' => ['label' => 'Diplôme / certification', 'col' => 4, 'aide' => 'CAF A, DEF, BEES...'],
            'discipline_id' => ['label' => 'Discipline', 'type' => 'select', 'options' => Discipline::visiblePar($user)->orderBy('nom')->pluck('nom', 'id')->all(), 'col' => 4],
            'federation_id' => ['label' => 'Fédération', 'type' => 'select', 'col' => 4,
                'options' => Federation::when(! $user->aAccesNational(), fn ($q) => $q->whereKey($user->federationCloisonnee()))->pluck('sigle', 'id')->all()],
            'structure' => ['label' => 'Club / structure', 'col' => 4],
            'pays' => ['label' => 'Pays', 'requis' => true, 'col' => 4, 'defaut' => 'Burkina Faso'],
            'telephone' => ['label' => 'Téléphone', 'col' => 4],
            'email' => ['label' => 'E-mail', 'type' => 'email', 'col' => 4, 'regles' => ['nullable', 'email']],
            'actif' => ['label' => 'En activité', 'type' => 'checkbox', 'col' => 12, 'defaut' => true],
        ];
    }

    protected function colonnes(): array
    {
        return [
            'Nom' => fn ($e) => $e->nom_complet,
            'Diplôme' => fn ($e) => $e->diplome,
            'Discipline' => fn ($e) => $e->discipline?->nom,
            'Fédération' => fn ($e) => $e->federation?->sigle,
            'Structure' => fn ($e) => trim($e->structure.' ('.$e->pays.')'),
            'Contact' => fn ($e) => trim($e->telephone.' '.$e->email),
            'Athlètes suivis' => fn ($e) => $e->talents_count,
            'Statut' => fn ($e) => $e->actif ? badge('Actif') : badge('Inactif', 'secondary'),
        ];
    }

    protected function filtres(): array
    {
        return ['discipline_id' => ['Discipline', Discipline::visiblePar(request()->user())->orderBy('nom')->pluck('nom', 'id')->all()]];
    }

    protected function requete(Request $request): Builder
    {
        return parent::requete($request)->withCount('talents');
    }

    protected function filtrerRequete(Builder $query, Request $request): void
    {
        $query->when($request->query('q'), fn ($q, $v) => $q->where(fn ($q) => $q->where('nom', 'like', "%$v%")->orWhere('prenoms', 'like', "%$v%")));
    }

    protected function avantSauvegarde(Request $request, Model $objet): void
    {
        if (! $request->user()->aAccesNational()) {
            $objet->federation_id = $request->user()->federationCloisonnee();
        }
    }
}
