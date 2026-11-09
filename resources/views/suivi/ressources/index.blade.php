@extends('suivi.layouts.app')
@section('titre', $c['titre'])

@section('contenu')
@php($controleur = request()->route()->getController())
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0"><i class="bi {{ $c['icone'] }} text-success"></i> {{ $c['titre'] }}</h1>
    <span class="badge text-bg-light border">{{ $objets->total() }} enregistrement(s)</span>
    <div class="ms-auto d-flex gap-2">
        @canany([$c['module'].'.exporter', 'rapports.voir'])
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv"></i> Exporter</a>
        @endcanany
        @if (Route::has($c['route'].'.create'))
        @can($c['module'].'.creer')
            <a href="{{ route($c['route'].'.create') }}" class="btn btn-bf btn-sm"><i class="bi bi-plus-lg"></i> {{ $c['singulier'] }}</a>
        @endcan
        @endif
    </div>
</div>

<form class="card card-body filtres mb-3 py-2">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="{{ $c['lieAuTalent'] ? 'Talent : nom, prénom, ID...' : 'Rechercher...' }}">
        </div>
        @if ($c['lieAuTalent'])
            @if (auth()->user()->aAccesNational())
                <div class="col-md-2">
                    <select name="federation_id" class="form-select form-select-sm">
                        <option value="">Toutes fédérations</option>
                        @foreach ($federations as $f)<option value="{{ $f->id }}" @selected(request('federation_id') == $f->id)>{{ $f->sigle }}</option>@endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-2">
                <select name="discipline_id" class="form-select form-select-sm">
                    <option value="">Toutes disciplines</option>
                    @foreach ($disciplines as $d)<option value="{{ $d->id }}" @selected(request('discipline_id') == $d->id)>{{ $d->nom }}</option>@endforeach
                </select>
            </div>
        @endif
        @foreach ($filtres as $nom => [$label, $options])
            <div class="col-md-2">
                <select name="{{ $nom }}" class="form-select form-select-sm">
                    <option value="">{{ $label }} : tous</option>
                    @foreach ($options as $valeur => $libelle)<option value="{{ $valeur }}" @selected(request($nom) == $valeur)>{{ $libelle }}</option>@endforeach
                </select>
            </div>
        @endforeach
        <div class="col-auto">
            <button class="btn btn-sm btn-success"><i class="bi bi-funnel"></i> Filtrer</button>
            <a href="{{ route($c['route'].'.index') }}" class="btn btn-sm btn-link">Réinitialiser</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                @if ($c['lieAuTalent'])<th>Talent</th>@endif
                @foreach (array_keys($colonnes) as $titre)<th>{{ $titre }}</th>@endforeach
                <th class="text-end no-print">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($objets as $o)
                <tr>
                    @if ($c['lieAuTalent'])
                        <td class="text-nowrap">
                            <a href="{{ route('suivi.talents.show', $o->talent_id) }}" class="fw-semibold text-decoration-none">{{ $o->talent?->nom_complet }}</a><br>
                            <small class="text-muted">{{ $o->talent?->code }} · {{ $o->talent?->discipline?->nom }}</small>
                        </td>
                    @endif
                    @foreach ($colonnes as $valeur)
                        <td>{{ $valeur($o) }}</td>
                    @endforeach
                    <td class="text-end text-nowrap no-print">
                        @if (method_exists($controleur, 'actionsLigne'))
                            @foreach ($controleur::actionsLigne($o) as $action)
                                @can($action['permission'])
                                    <form method="POST" action="{{ $action['route'] }}" class="d-inline">@csrf
                                        <button class="btn btn-sm btn-outline-success" title="{{ $action['label'] }}"><i class="bi {{ $action['icone'] }}"></i> {{ $action['label'] }}</button>
                                    </form>
                                @endcan
                            @endforeach
                        @endif
                        @php($modifiable = ! method_exists($o, 'estModifiablePar') || $o->estModifiablePar(auth()->user()))
                        @if (! $modifiable)<span class="badge text-bg-light border" title="Géré par le Ministère"><i class="bi bi-lock"></i> National</span>@endif
                        @if ($modifiable)
                        @can($c['module'].'.modifier')
                            <a href="{{ route($c['route'].'.edit', $o->id) }}" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="bi bi-pencil"></i></a>
                        @endcan
                        @can($c['module'].'.supprimer')
                            @if (Route::has($c['route'].'.destroy'))
                            <form method="POST" action="{{ route($c['route'].'.destroy', $o->id) }}" class="d-inline" data-confirmer="Supprimer cet enregistrement ?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        @endcan
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ count($colonnes) + 2 }}" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block"></i>Aucun enregistrement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($objets->hasPages())<div class="card-footer bg-white">{{ $objets->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
