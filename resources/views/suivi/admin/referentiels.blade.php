@extends('suivi.layouts.app')
@section('titre', 'Listes de référence')

@section('contenu')
<h1 class="h4 mb-1"><i class="bi bi-list-check text-success"></i> Listes de référence</h1>
<p class="text-muted small">Valeurs proposées dans les listes déroulantes de la plateforme (équivalent de l'onglet « Dictionnaire & Listes » du canevas). Désactiver une valeur la retire des formulaires sans modifier les fiches existantes.</p>

<div class="row g-3">
    <div class="col-md-3">
        <div class="list-group">
            @foreach ($types as $cle => $libelle)
                <a href="{{ route('suivi.referentiels.index', ['type' => $cle]) }}" class="list-group-item list-group-item-action {{ $cle === $type ? 'active' : '' }}">{{ $libelle }}</a>
            @endforeach
        </div>
    </div>
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">{{ $types[$type] }}</div>
            <ul class="list-group list-group-flush">
                @foreach ($valeurs as $v)
                    <li class="list-group-item">
                        <form method="POST" action="{{ route('suivi.referentiels.update', $v) }}" class="row g-2 align-items-center">@csrf @method('PUT')
                            <div class="col-md-6"><input name="valeur" value="{{ $v->valeur }}" class="form-control form-control-sm" required></div>
                            <div class="col-md-2"><input type="number" name="ordre" value="{{ $v->ordre }}" class="form-control form-control-sm" title="Ordre"></div>
                            <div class="col-md-2 form-check form-switch"><input type="checkbox" class="form-check-input" name="actif" value="1" @checked($v->actif)><label class="form-check-label small">Active</label></div>
                            <div class="col-md-2 text-end">
                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check"></i></button>
                                <button form="suppr{{ $v->id }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </div>
                        </form>
                        <form id="suppr{{ $v->id }}" method="POST" action="{{ route('suivi.referentiels.destroy', $v) }}" data-confirmer="Supprimer cette valeur ?">@csrf @method('DELETE')</form>
                    </li>
                @endforeach
            </ul>
            <form method="POST" action="{{ route('suivi.referentiels.store') }}" class="card-footer bg-white input-group">@csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <input name="valeur" class="form-control" placeholder="Nouvelle valeur" required>
                <button class="btn btn-bf"><i class="bi bi-plus-lg"></i> Ajouter</button>
            </form>
        </div>
    </div>
</div>
@endsection
