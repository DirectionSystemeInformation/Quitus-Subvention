@extends('suivi.layouts.app')
@section('titre', 'Rapports & exports')

@section('contenu')
<div class="d-flex align-items-center mb-3">
    <h1 class="h4 mb-0"><i class="bi bi-bar-chart-line text-success"></i> Rapports & exports</h1>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm ms-auto no-print"><i class="bi bi-printer"></i> Imprimer</button>
</div>

<form class="card card-body filtres mb-3 py-2 no-print"><div class="row g-2">
    @if ($federations->isNotEmpty())
        <div class="col-md-2"><select name="federation_id" class="form-select form-select-sm"><option value="">Toutes fédérations</option>
            @foreach ($federations as $f)<option value="{{ $f->id }}" @selected(($filtres['federation_id'] ?? '') == $f->id)>{{ $f->sigle }}</option>@endforeach</select></div>
    @endif
    <div class="col-md-2"><select name="discipline_id" class="form-select form-select-sm"><option value="">Toutes disciplines</option>
        @foreach ($disciplines as $d)<option value="{{ $d->id }}" @selected(($filtres['discipline_id'] ?? '') == $d->id)>{{ $d->nom }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="genre" class="form-select form-select-sm"><option value="">Tous genres</option>
        @foreach (['Masculin', 'Féminin'] as $g)<option @selected(($filtres['genre'] ?? '') === $g)>{{ $g }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="categorie" class="form-select form-select-sm"><option value="">Toutes catégories</option>
        @foreach (array_keys(\App\Models\Talents\Talent::CATEGORIES_AGE) as $c)<option @selected(($filtres['categorie'] ?? '') === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="annee" class="form-select form-select-sm"><option value="">Toutes saisons</option>
        @foreach (range(now()->year, now()->year - 5) as $a)<option @selected(($filtres['annee'] ?? '') == $a)>{{ $a }}</option>@endforeach</select></div>
    <div class="col-auto"><button class="btn btn-sm btn-success">Appliquer</button></div>
</div></form>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header">Effectifs par discipline</div>
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Discipline</th><th class="text-end">Hommes</th><th class="text-end">Femmes</th><th class="text-end">Actifs</th><th class="text-end">Retraités</th><th class="text-end">Haut pot.</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                @forelse ($croise as $l)
                    <tr><td>{{ $l->discipline }}</td><td class="text-end">{{ $l->hommes }}</td><td class="text-end">{{ $l->femmes }}</td><td class="text-end">{{ $l->actifs }}</td>
                        <td class="text-end">{{ $l->retraites }}</td><td class="text-end">{{ $l->haut_potentiel }}</td><td class="text-end fw-bold">{{ $l->total }}</td></tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Aucune donnée.</td></tr>
                @endforelse
                </tbody>
                @if ($croise->isNotEmpty())
                    <tfoot class="table-light fw-bold"><tr><td>Total</td>
                        @foreach (['hommes', 'femmes', 'actifs', 'retraites', 'haut_potentiel', 'total'] as $col)<td class="text-end">{{ $croise->sum($col) }}</td>@endforeach</tr></tfoot>
                @endif
            </table></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header">Palmarès par fédération</div>
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Fédération</th><th class="text-end">🥇</th><th class="text-end">🥈</th><th class="text-end">🥉</th><th class="text-end">Minimas</th><th class="text-end">Résultats</th></tr></thead>
                <tbody>
                @forelse ($palmares as $p)
                    <tr><td>{{ $p->sigle }}</td><td class="text-end">{{ $p->ors }}</td><td class="text-end">{{ $p->argents }}</td><td class="text-end">{{ $p->bronzes }}</td>
                        <td class="text-end">{{ $p->minimas }}</td><td class="text-end">{{ $p->resultats }}</td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Aucun résultat.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
    <div class="col-12 no-print">
        <div class="card">
            <div class="card-header">Exports CSV (compatibles Excel, mêmes colonnes que le canevas)</div>
            <div class="card-body d-flex flex-wrap gap-2">
                @foreach ($exports as [$route, $libelle])
                    <a href="{{ route('suivi.'.$route, array_merge(array_filter($filtres), ['export' => 'csv'])) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-filetype-csv"></i> {{ $libelle }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
