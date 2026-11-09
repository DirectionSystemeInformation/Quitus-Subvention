@extends('suivi.layouts.app')
@section('titre', 'Talents')

@section('contenu')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0"><i class="bi bi-people text-success"></i> Talents sportifs</h1>
    <span class="badge text-bg-light border">{{ $talents->total() }} talent(s)</span>
    <div class="ms-auto d-flex gap-2">
        <div class="btn-group btn-group-sm">
            <a href="{{ request()->fullUrlWithQuery(['vue' => 'liste']) }}" class="btn btn-outline-secondary {{ $vue === 'liste' ? 'active' : '' }}"><i class="bi bi-list-ul"></i></a>
            <a href="{{ request()->fullUrlWithQuery(['vue' => 'cartes']) }}" class="btn btn-outline-secondary {{ $vue === 'cartes' ? 'active' : '' }}"><i class="bi bi-grid-3x3-gap"></i></a>
        </div>
        @can('talents.exporter')
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv"></i> Exporter</a>
        @endcan
        @can('talents.creer')
            <a href="{{ route('suivi.talents.create') }}" class="btn btn-bf btn-sm"><i class="bi bi-person-plus"></i> Nouveau talent</a>
        @endcan
    </div>
</div>

<form class="card card-body filtres mb-3 py-2">
    <input type="hidden" name="vue" value="{{ $vue }}">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Nom, prénom ou ID TLT-FBF-..."></div>
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
        <div class="col-md-1">
            <select name="genre" class="form-select form-select-sm">
                <option value="">Genre</option>
                @foreach (['Masculin', 'Féminin'] as $g)<option @selected(request('genre') === $g)>{{ $g }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-1">
            <select name="categorie" class="form-select form-select-sm">
                <option value="">Âge</option>
                @foreach (array_keys(\App\Models\Talents\Talent::CATEGORIES_AGE) as $cat)<option @selected(request('categorie') === $cat)>{{ $cat }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-1">
            <select name="statut_carriere" class="form-select form-select-sm">
                <option value="">Carrière</option>
                @foreach (\App\Models\Talents\Referentiel::liste('statut_carriere') as $s)<option @selected(request('statut_carriere') === $s)>{{ $s }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="statut_profil" class="form-select form-select-sm">
                <option value="">Statut du profil</option>
                @foreach (\App\Models\Talents\Talent::STATUTS_PROFIL as $s)<option @selected(request('statut_profil') === $s)>{{ $s }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="region" class="form-select form-select-sm">
                <option value="">Toutes régions</option>
                @foreach (\App\Models\Talents\Referentiel::liste('region') as $r)<option @selected(request('region') === $r)>{{ $r }}</option>@endforeach
            </select>
        </div>
        <div class="col-auto form-check ms-2">
            <input type="checkbox" class="form-check-input" name="haut_potentiel" value="1" id="hp" @checked(request('haut_potentiel'))>
            <label for="hp" class="form-check-label small">Haut potentiel</label>
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-success"><i class="bi bi-funnel"></i> Filtrer</button>
            <a href="{{ route('suivi.talents.index') }}" class="btn btn-sm btn-link">Réinitialiser</a>
        </div>
    </div>
</form>

@if ($vue === 'cartes')
    <div class="row g-3">
        @forelse ($talents as $t)
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <a href="{{ route('suivi.talents.show', $t) }}" class="card h-100 text-decoration-none text-dark">
                    <div class="card-body text-center">
                        @include('suivi.talents._avatar', ['t' => $t, 'taille' => 'avatar-lg'])
                        <h6 class="mt-2 mb-0">{{ $t->nom_complet }} @if ($t->haut_potentiel)<i class="bi bi-star-fill text-warning" title="Haut potentiel"></i>@endif</h6>
                        <small class="text-muted">{{ $t->code }}</small>
                        <div class="my-2">{{ $t->discipline->nom }} · {{ $t->specialite }}</div>
                        <div class="d-flex justify-content-center gap-1 flex-wrap">
                            {{ badge($t->categorie_age, 'light text-dark border') }} {{ badge($t->statut_carriere) }} {{ badge($t->statut_profil) }}
                        </div>
                        @if ($t->licenceInternationale?->club)<small class="d-block mt-2 text-primary"><i class="bi bi-globe"></i> {{ $t->licenceInternationale->club }}</small>@endif
                    </div>
                </a>
            </div>
        @empty
            <p class="text-muted">Aucun talent ne correspond aux filtres.</p>
        @endforelse
    </div>
    <div class="mt-3">{{ $talents->links('pagination::bootstrap-5') }}</div>
@else
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                <tr><th>Talent</th><th>Âge</th><th>Discipline / spécialité</th><th>Fédération</th><th>Licence nat.</th><th>Licence int. / club</th><th>Carrière</th><th>Profil</th><th></th></tr>
                </thead>
                <tbody>
                @forelse ($talents as $t)
                    <tr>
                        <td class="text-nowrap">
                            <div class="d-flex align-items-center gap-2">
                                @include('suivi.talents._avatar', ['t' => $t, 'taille' => ''])
                                <div><a href="{{ route('suivi.talents.show', $t) }}" class="fw-semibold text-decoration-none">{{ $t->nom_complet }}</a>
                                    @if ($t->haut_potentiel)<i class="bi bi-star-fill text-warning" title="Haut potentiel"></i>@endif
                                    <br><small class="text-muted">{{ $t->code }} · {{ $t->genre }}</small></div>
                            </div>
                        </td>
                        <td>{{ $t->age }} ans<br><small class="text-muted">{{ $t->categorie_age }}</small></td>
                        <td>{{ $t->discipline->nom }}<br><small class="text-muted">{{ $t->specialite }}</small></td>
                        <td>{{ $t->federation->sigle }}</td>
                        <td>{{ $t->licenceNationale?->numero ?? '—' }}</td>
                        <td>@if ($t->licenceInternationale){{ $t->licenceInternationale->numero }}<br><small class="text-muted">{{ $t->licenceInternationale->club }}</small>@else — @endif</td>
                        <td>{{ badge($t->statut_carriere) }}</td>
                        <td>{{ badge($t->statut_profil) }}</td>
                        <td class="text-end"><a href="{{ route('suivi.talents.show', $t) }}" class="btn btn-sm btn-outline-success">Fiche</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-5">Aucun talent ne correspond aux filtres.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($talents->hasPages())<div class="card-footer bg-white">{{ $talents->links('pagination::bootstrap-5') }}</div>@endif
    </div>
@endif
@endsection
