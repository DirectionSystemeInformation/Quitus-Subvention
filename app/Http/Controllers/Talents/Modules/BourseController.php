<?php

namespace App\Http\Controllers\Talents\Modules;

use App\Http\Controllers\Talents\RessourceController;
use App\Models\Talents\Bourse;
use App\Models\Talents\Referentiel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class BourseController extends RessourceController
{
    protected static string $module = 'bourses';

    protected string $modele = Bourse::class;

    protected string $route = 'suivi.bourses';

    protected string $titre = 'Bourses & appuis de l\'État';

    protected string $singulier = 'Bourse';

    protected string $icone = 'bi-cash-coin';

    public static function middleware(): array
    {
        return [...parent::middleware(), new Middleware('can:bourses.decider', only: ['decider'])];
    }

    protected function champs(?Model $objet = null): array
    {
        $champs = [
            'talent_id' => ['label' => 'Talent', 'type' => 'talent', 'requis' => true, 'col' => 6],
            'type_bourse' => ['label' => 'Type de bourse / appui', 'type' => 'select', 'options' => self::options(Referentiel::liste('type_bourse')), 'requis' => true, 'col' => 6],
            'financeur' => ['label' => 'Financeur', 'requis' => true, 'col' => 4, 'defaut' => 'Ministère des Sports'],
            'montant' => ['label' => 'Montant annuel', 'type' => 'number', 'col' => 4, 'regles' => ['nullable', 'numeric', 'min:0']],
            'devise' => ['label' => 'Devise', 'type' => 'select', 'options' => self::options(['XOF', 'EUR', 'USD']), 'requis' => true, 'col' => 4],
            'date_debut' => ['label' => 'Début', 'type' => 'date', 'col' => 6],
            'date_fin' => ['label' => 'Fin', 'type' => 'date', 'col' => 6, 'regles' => ['nullable', 'date', 'after_or_equal:date_debut']],
            'observations' => ['label' => 'Observations / justification', 'type' => 'textarea', 'col' => 12],
        ];

        // Seul un décideur (Ministère) fixe le statut ; les fédérations soumettent des demandes.
        if (request()->user()->can('bourses.decider')) {
            $champs['statut'] = ['label' => 'Statut', 'type' => 'select', 'options' => self::options(Bourse::STATUTS), 'requis' => true, 'col' => 4,
                'regles' => ['required', Rule::in(Bourse::STATUTS)]];
        }

        return $champs;
    }

    protected function colonnes(): array
    {
        return [
            'Type' => fn ($b) => $b->type_bourse,
            'Financeur' => fn ($b) => $b->financeur,
            'Montant' => fn ($b) => montant($b->montant, $b->devise === 'XOF' ? 'FCFA' : $b->devise),
            'Période' => fn ($b) => trim($b->date_debut?->format('m/Y').' → '.$b->date_fin?->format('m/Y'), ' →'),
            'Statut' => fn ($b) => badge($b->statut),
            'Observations' => fn ($b) => $b->observations,
        ];
    }

    protected function filtres(): array
    {
        return [
            'statut' => ['Statut', self::options(Bourse::STATUTS)],
            'type_bourse' => ['Type', self::options(Referentiel::liste('type_bourse'))],
        ];
    }

    public static function actionsLigne(Model $bourse): array
    {
        if ($bourse->statut !== 'Demandée') {
            return [];
        }

        return [
            ['route' => route('suivi.bourses.decider', [$bourse, 'statut' => 'Accordée']), 'label' => 'Accorder', 'icone' => 'bi-check-lg', 'permission' => 'bourses.decider'],
            ['route' => route('suivi.bourses.decider', [$bourse, 'statut' => 'Refusée']), 'label' => 'Refuser', 'icone' => 'bi-x-lg', 'permission' => 'bourses.decider'],
        ];
    }

    public function decider(Request $request, Bourse $bourse)
    {
        $this->trouver($request, $bourse->id);
        $data = $request->validate(['statut' => ['required', Rule::in(['Accordée', 'Refusée'])]]);
        $bourse->update([...$data, 'decide_par' => $request->user()->id, 'decide_le' => now()]);

        return back()->with('succes', "Bourse {$data['statut']}.");
    }
}
