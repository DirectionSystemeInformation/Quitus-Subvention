<?php

namespace App\Http\Controllers\Talents\Modules;

use App\Http\Controllers\Talents\RessourceController;
use App\Models\Talents\Competition;
use App\Models\Talents\Performance;
use App\Models\Talents\Referentiel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PerformanceController extends RessourceController
{
    protected static string $module = 'performances';

    protected string $modele = Performance::class;

    protected string $route = 'suivi.performances';

    protected string $titre = 'Suivi des performances en compétition';

    protected string $singulier = 'Performance';

    protected string $icone = 'bi-trophy';

    protected array $with = ['competition'];

    protected string $ordre = 'date_evenement';

    protected function champs(?Model $objet = null): array
    {
        return [
            'talent_id' => ['label' => 'Talent', 'type' => 'talent', 'requis' => true, 'col' => 6],
            'competition_id' => ['label' => 'Compétition', 'type' => 'select', 'requis' => true, 'col' => 6,
                'regles' => ['required', Rule::in(Competition::visiblePar(request()->user())->pluck('id'))],
                'options' => Competition::visiblePar(request()->user())->orderByDesc('date_debut')->get()
                    ->mapWithKeys(fn ($c) => [$c->id => "{$c->nom} ({$c->niveau}, {$c->date_debut->format('m/Y')})"])->all(),
                'aide' => 'Compétition absente ? Créez-la d\'abord dans le calendrier.'],
            'date_evenement' => ['label' => 'Date de l\'épreuve', 'type' => 'date', 'requis' => true, 'col' => 4],
            'epreuve' => ['label' => 'Épreuve / poste', 'col' => 4],
            'performance' => ['label' => 'Performance / score', 'col' => 4, 'aide' => 'Chrono, points, temps de jeu...'],
            'rang' => ['label' => 'Rang / résultat', 'col' => 4],
            'distinction' => ['label' => 'Distinction / médaille', 'type' => 'select', 'options' => self::options(Referentiel::liste('distinction')), 'col' => 4],
            'minima_atteint' => ['label' => 'Minima de qualification atteint', 'type' => 'checkbox', 'col' => 4],
            'observations_dtn' => ['label' => 'Observations DTN', 'type' => 'textarea', 'col' => 12],
        ];
    }

    protected function colonnes(): array
    {
        return [
            'Date' => fn ($p) => $p->date_evenement->format('d/m/Y'),
            'Compétition' => fn ($p) => $p->competition->nom,
            'Niveau' => fn ($p) => badge($p->competition->niveau, 'info'),
            'Épreuve' => fn ($p) => $p->epreuve,
            'Performance' => fn ($p) => $p->performance,
            'Rang' => fn ($p) => $p->rang,
            'Distinction' => fn ($p) => $p->distinction ? badge($p->distinction) : '',
            'Minima' => fn ($p) => $p->minima_atteint ? badge('Oui', 'success') : '',
            'Observations DTN' => fn ($p) => $p->observations_dtn,
        ];
    }

    protected function filtres(): array
    {
        return [
            'distinction' => ['Distinction', self::options(Referentiel::liste('distinction'))],
            'competition_id' => ['Compétition', Competition::visiblePar(request()->user())->orderBy('nom')->pluck('nom', 'id')->all()],
        ];
    }

    protected function filtrerRequete(Builder $query, Request $request): void
    {
        $query->when($request->query('niveau'), fn ($q, $v) => $q->whereHas('competition', fn ($c) => $c->where('niveau', $v)))
            ->when($request->query('annee'), fn ($q, $v) => $q->whereYear('date_evenement', $v));
    }
}
