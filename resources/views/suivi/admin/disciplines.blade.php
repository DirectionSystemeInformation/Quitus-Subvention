@extends('suivi.layouts.app')
@section('titre', 'Disciplines & spécialités')

@php($national = auth()->user()->aAccesNational())

@section('contenu')
<h1 class="h4 mb-1"><i class="bi bi-diagram-3 text-success"></i> {{ $national ? 'Disciplines & spécialités' : 'Mes disciplines & spécialités' }}</h1>
<p class="text-muted small">
    @if ($national)
        Vue nationale : toutes les disciplines, quelle que soit la fédération de rattachement.
    @else
        Disciplines pratiquées au sein de {{ auth()->user()->federationTalents()?->nom }}. Elles alimentent les listes de vos fiches talents, entraîneurs et compétitions.
    @endif
</p>

@can('disciplines.gerer')
<form method="POST" action="{{ route('suivi.disciplines.store') }}" class="card card-body mb-3">
    @csrf
    <div class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label">Nouvelle discipline</label><input name="nom" value="{{ old('nom') }}" class="form-control" required></div>
        @if ($national)
            <div class="col-md-3"><label class="form-label">Fédération de tutelle</label>
                <select name="federation_id" class="form-select"><option value="">—</option>@foreach ($federations as $f)<option value="{{ $f->id }}">{{ $f->sigle }}</option>@endforeach</select></div>
        @endif
        <div class="col-auto form-check"><input type="checkbox" class="form-check-input" name="collectif" value="1" id="coll"><label for="coll" class="form-check-label">Sport collectif</label></div>
        <input type="hidden" name="actif" value="1">
        <div class="col-auto"><button class="btn btn-bf"><i class="bi bi-plus-lg"></i> Ajouter</button></div>
    </div>
</form>
@endcan

<div class="row g-3">
    @forelse ($disciplines as $d)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 {{ $d->actif ? '' : 'opacity-50' }}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ $d->nom }}
                        @if ($national)<small class="text-muted">· {{ $d->federation?->sigle ?? 'sans fédération' }}</small>@endif
                        @unless ($d->actif)<span class="badge text-bg-secondary">inactive</span>@endunless
                    </span>
                    <span class="badge text-bg-light border">{{ $d->talents_count }} talent(s)</span>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        @forelse ($d->specialites as $s)
                            <span class="badge text-bg-light border d-inline-flex align-items-center gap-1">{{ $s->nom }}
                                @can('disciplines.gerer')
                                    <form method="POST" action="{{ route('suivi.specialites.destroy', $s) }}" class="d-inline" data-confirmer="Supprimer la spécialité « {{ $s->nom }} » ?">@csrf @method('DELETE')
                                        <button class="btn btn-link p-0 text-danger lh-1" style="font-size:.75rem" title="Supprimer">×</button></form>
                                @endcan
                            </span>
                        @empty
                            <small class="text-muted">Aucune spécialité.</small>
                        @endforelse
                    </div>
                    @can('disciplines.gerer')
                        <form method="POST" action="{{ route('suivi.specialites.store', $d) }}" class="input-group input-group-sm mb-2">@csrf
                            <input name="nom" class="form-control" placeholder="Ajouter une spécialité / poste / catégorie" required>
                            <button class="btn btn-outline-success"><i class="bi bi-plus"></i></button>
                        </form>
                        <details>
                            <summary class="small text-muted">Modifier la discipline</summary>
                            <form method="POST" action="{{ route('suivi.disciplines.update', $d) }}" class="mt-2">@csrf @method('PUT')
                                <input name="nom" value="{{ $d->nom }}" class="form-control form-control-sm mb-2" required>
                                @if ($national)
                                    <select name="federation_id" class="form-select form-select-sm mb-2"><option value="">—</option>
                                        @foreach ($federations as $f)<option value="{{ $f->id }}" @selected($d->federation_id == $f->id)>{{ $f->sigle }}</option>@endforeach</select>
                                @endif
                                <div class="form-check form-check-inline"><input type="checkbox" class="form-check-input" name="collectif" value="1" @checked($d->collectif)><label class="form-check-label small">Collectif</label></div>
                                <div class="form-check form-check-inline"><input type="checkbox" class="form-check-input" name="actif" value="1" @checked($d->actif)><label class="form-check-label small">Active</label></div>
                                <button class="btn btn-sm btn-outline-primary">Enregistrer</button>
                            </form>
                            @if ($d->talents_count === 0)
                                <form method="POST" action="{{ route('suivi.disciplines.destroy', $d) }}" class="mt-1" data-confirmer="Supprimer cette discipline ?">@csrf @method('DELETE')
                                    <button class="btn btn-sm btn-link text-danger p-0">Supprimer</button></form>
                            @else
                                <small class="text-muted d-block mt-1">Discipline utilisée : désactivez-la plutôt que de la supprimer.</small>
                            @endif
                        </details>
                    @endcan
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info mb-0">Aucune discipline pour le moment. Ajoutez celles pratiquées dans votre fédération.</div></div>
    @endforelse
</div>
@endsection
