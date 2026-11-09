@extends('suivi.layouts.app')
@section('titre', $u->exists ? 'Modifier un compte' : 'Nouveau compte')

@section('contenu')
<nav class="mb-2 small"><a href="{{ route('suivi.utilisateurs.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Utilisateurs</a></nav>
<h1 class="h4 mb-1">{{ $u->exists ? 'Modifier le compte de '.$u->name : 'Nouveau compte complémentaire' }}</h1>
<p class="text-muted small">Compte réservé au suivi des talents (DTN, médecin, entraîneur, observateur...) : il ne donne pas accès aux subventions de Quitus.</p>

<form method="POST" action="{{ $u->exists ? route('suivi.utilisateurs.update', $u) : route('suivi.utilisateurs.store') }}" class="card">
    @csrf @if ($u->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label">Nom complet *</label><input name="name" value="{{ old('name', $u->name) }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">E-mail (identifiant de connexion) *</label><input type="email" name="email" value="{{ old('email', $u->email) }}" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Fonction</label><input name="fonction" value="{{ old('fonction', $u->fonction) }}" class="form-control" placeholder="DTN, médecin fédéral..."></div>
        <div class="col-md-4"><label class="form-label">Téléphone</label><input name="telephone" value="{{ old('telephone', $u->telephone) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Fédération de rattachement</label>
            <select name="talent_federation_id" class="form-select"><option value="">Niveau national (Ministère, CNOSB...)</option>
                @foreach ($federations as $f)<option value="{{ $f->id }}" @selected(old('talent_federation_id', $u->talent_federation_id) == $f->id)>{{ $f->sigle }} — {{ $f->nom }}</option>@endforeach</select>
            <div class="form-text">Rattaché à une fédération, le compte ne voit que ses athlètes (sauf permission « Vue nationale »).</div></div>
        <div class="col-md-6"><label class="form-label">Rôle(s)</label>
            @foreach ($roles as $r)
                <div class="form-check"><input type="checkbox" class="form-check-input" name="roles[]" value="{{ $r }}" id="r{{ $loop->index }}" @checked(in_array($r, old('roles', $u->getRoleNames()->all())))>
                    <label class="form-check-label" for="r{{ $loop->index }}">{{ $r }}</label></div>
            @endforeach
        </div>
        <div class="col-md-6">
            <label class="form-label">Mot de passe {{ $u->exists ? '(laisser vide pour ne pas changer)' : '*' }}</label>
            <input type="password" name="password" class="form-control mb-2" autocomplete="new-password" @required(! $u->exists)>
            <input type="password" name="password_confirmation" class="form-control" placeholder="Confirmation" autocomplete="new-password">
            <div class="form-check form-switch mt-3"><input type="checkbox" class="form-check-input" name="actif" value="1" id="actif" @checked(old('actif', $u->status === 'active'))><label for="actif" class="form-check-label">Compte actif</label></div>
        </div>
    </div>
    <div class="card-footer bg-white"><button class="btn btn-bf">Enregistrer</button></div>
</form>
@endsection
