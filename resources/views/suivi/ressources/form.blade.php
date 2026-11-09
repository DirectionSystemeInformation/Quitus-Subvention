@extends('suivi.layouts.app')
@section('titre', ($objet->exists ? 'Modifier' : 'Nouveau').' · '.$c['singulier'])

@section('contenu')
<nav class="mb-2 small"><a href="{{ route($c['route'].'.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> {{ $c['titre'] }}</a></nav>
<h1 class="h4 mb-3"><i class="bi {{ $c['icone'] }} text-success"></i> {{ $objet->exists ? 'Modifier' : 'Nouveau' }} : {{ $c['singulier'] }}</h1>

<form method="POST" action="{{ $objet->exists ? route($c['route'].'.update', $objet->id) : route($c['route'].'.store') }}" class="card">
    @csrf
    @if ($objet->exists) @method('PUT') @endif
    <input type="hidden" name="retour" value="{{ old('retour', url()->previous()) }}">
    <div class="card-body">
        <div class="row g-3">
            @foreach ($champs as $nom => $champ)
                @include('suivi.partials.champ', ['nom' => $nom, 'champ' => $champ, 'valeur' => $objet->{$nom} ?? ($champ['defaut'] ?? null)])
            @endforeach
        </div>
    </div>
    <div class="card-footer bg-white d-flex gap-2">
        <button class="btn btn-bf"><i class="bi bi-check-lg"></i> Enregistrer</button>
        <a href="{{ old('retour', url()->previous()) }}" class="btn btn-light">Annuler</a>
    </div>
</form>
@endsection
