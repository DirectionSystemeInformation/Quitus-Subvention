<?php

namespace App\Http\Controllers\Talents;

use App\Http\Controllers\Controller;
use App\Models\Talents\Discipline;
use App\Models\Talents\Federation;
use App\Models\Talents\Talent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

/**
 * Rapports consolidés (tableaux croisés) et point d'accès unique aux exports CSV de chaque module.
 */
class RapportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:rapports.voir')];
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $filtres = $request->only('federation_id', 'discipline_id', 'genre', 'categorie', 'annee');
        $base = fn () => Talent::visiblePar($user)->filtrer($filtres);

        // Tableau croisé discipline × statut de carrière / genre
        $croise = $base()->join('talent_disciplines', 'talent_disciplines.id', '=', 'talents.discipline_id')
            ->select('talent_disciplines.nom as discipline',
                DB::raw("sum(case when genre = 'Masculin' then 1 else 0 end) as hommes"),
                DB::raw("sum(case when genre = 'Féminin' then 1 else 0 end) as femmes"),
                DB::raw("sum(case when statut_carriere = 'Actif' then 1 else 0 end) as actifs"),
                DB::raw("sum(case when statut_carriere in ('Retraité','Reconverti') then 1 else 0 end) as retraites"),
                DB::raw('sum(case when haut_potentiel then 1 else 0 end) as haut_potentiel'),
                DB::raw('count(*) as total'))
            ->groupBy('talent_disciplines.nom')->orderByDesc('total')->get();

        // Palmarès par fédération
        $palmares = $base()->join('talent_federations', 'talent_federations.id', '=', 'talents.federation_id')
            ->join('talent_performances', 'talent_performances.talent_id', '=', 'talents.id')
            ->when($filtres['annee'] ?? null, fn ($q, $a) => $q->whereYear('talent_performances.date_evenement', $a))
            ->select('talent_federations.sigle',
                DB::raw("sum(case when distinction = 'Médaille d''Or' then 1 else 0 end) as ors"),
                DB::raw("sum(case when distinction = 'Médaille d''Argent' then 1 else 0 end) as argents"),
                DB::raw("sum(case when distinction = 'Médaille de Bronze' then 1 else 0 end) as bronzes"),
                DB::raw('sum(case when minima_atteint then 1 else 0 end) as minimas'),
                DB::raw('count(talent_performances.id) as resultats'))
            ->groupBy('talent_federations.sigle')->orderByDesc('ors')->get();

        return view('suivi.rapports.index', [
            'croise' => $croise,
            'palmares' => $palmares,
            'filtres' => $filtres,
            'federations' => $user->aAccesNational() ? Federation::orderBy('sigle')->get() : collect(),
            'disciplines' => Discipline::visiblePar($user)->orderBy('nom')->get(),
            'exports' => collect([
                ['talents.index', 'Talents & licences (onglet 1)', 'talents.exporter'],
                ['performances.index', 'Performances (onglet 2)', 'performances.voir'],
                ['evaluations.index', 'Évaluations physiques (onglet 3)', 'evaluations.voir'],
                ['medical.index', 'Suivi médical (onglet 3)', 'medical.voir'],
                ['parcours.index', 'Parcours & carrière (onglet 4)', 'parcours.voir'],
                ['bourses.index', 'Bourses & appuis (onglet 4)', 'bourses.voir'],
                ['reconversions.index', 'Retraite & reconversion (onglet 5)', 'reconversions.voir'],
                ['licences.index', 'Licences', 'licences.voir'],
            ])->filter(fn ($e) => $user->can($e[2])),
        ]);
    }
}
