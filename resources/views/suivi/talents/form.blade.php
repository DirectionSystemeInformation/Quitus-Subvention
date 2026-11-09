@extends('suivi.layouts.app')
@section('titre', $talent->exists ? 'Modifier '.$talent->nom_complet : 'Nouveau talent')

@section('contenu')
<nav class="mb-2 small"><a href="{{ $talent->exists ? route('suivi.talents.show', $talent) : route('suivi.talents.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Retour</a></nav>
<h1 class="h4 mb-3">
    <i class="bi bi-person-vcard text-success"></i>
    {{ $talent->exists ? 'Modifier la fiche '.$talent->code : 'Identification d\'un nouveau talent' }}
</h1>

<form method="POST" enctype="multipart/form-data" action="{{ $talent->exists ? route('suivi.talents.update', $talent) : route('suivi.talents.store') }}">
    @csrf
    @if ($talent->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-person"></i> État civil</div>
                <div class="card-body row g-3">
                    <div class="col-md-4"><label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input name="nom" value="{{ old('nom', $talent->nom) }}" class="form-control text-uppercase" required></div>
                    <div class="col-md-5"><label class="form-label">Prénom(s) <span class="text-danger">*</span></label>
                        <input name="prenoms" value="{{ old('prenoms', $talent->prenoms) }}" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Genre <span class="text-danger">*</span></label>
                        <select name="genre" class="form-select" required>
                            @foreach (['Masculin', 'Féminin'] as $g)<option @selected(old('genre', $talent->genre) === $g)>{{ $g }}</option>@endforeach
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Date de naissance <span class="text-danger">*</span></label>
                        <input type="date" name="date_naissance" value="{{ old('date_naissance', $talent->date_naissance?->format('Y-m-d')) }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Lieu de naissance</label>
                        <input name="lieu_naissance" value="{{ old('lieu_naissance', $talent->lieu_naissance) }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Nationalité(s) <span class="text-danger">*</span></label>
                        <input name="nationalites" value="{{ old('nationalites', $talent->nationalites) }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Région d'origine</label>
                        <select name="region" class="form-select"><option value="">—</option>
                            @foreach ($regions as $r)<option @selected(old('region', $talent->region) === $r)>{{ $r }}</option>@endforeach
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Téléphone</label>
                        <input name="telephone" value="{{ old('telephone', $talent->telephone) }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">E-mail</label>
                        <input type="email" name="email" value="{{ old('email', $talent->email) }}" class="form-control"></div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-trophy"></i> Rattachement sportif</div>
                <div class="card-body row g-3">
                    <div class="col-md-4"><label class="form-label">Fédération <span class="text-danger">*</span></label>
                        <select name="federation_id" id="federation_id" class="form-select" required>
                            @foreach ($federations as $f)<option value="{{ $f->id }}" @selected(old('federation_id', $talent->federation_id) == $f->id)>{{ $f->sigle }} — {{ $f->nom }}</option>@endforeach
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Discipline <span class="text-danger">*</span></label>
                        <select name="discipline_id" id="discipline_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach ($disciplines as $d)<option value="{{ $d->id }}" data-federation="{{ $d->federation_id }}" @selected(old('discipline_id', $talent->discipline_id) == $d->id)>{{ $d->nom }}</option>@endforeach
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Spécialité / poste</label>
                        <input name="specialite" id="specialite" list="liste_specialites" value="{{ old('specialite', $talent->specialite) }}" class="form-control">
                        <datalist id="liste_specialites"></datalist></div>
                    <div class="col-md-6"><label class="form-label">Entraîneur référent</label>
                        <select name="entraineur_id" class="form-select recherche"><option value="">—</option>
                            @foreach ($entraineurs as $e)<option value="{{ $e->id }}" @selected(old('entraineur_id', $talent->entraineur_id) == $e->id)>{{ $e->nom_complet }} ({{ $e->structure }})</option>@endforeach
                        </select></div>
                    <div class="col-md-3"><label class="form-label">Statut de carrière <span class="text-danger">*</span></label>
                        <select name="statut_carriere" class="form-select" required>
                            @foreach ($statutsCarriere as $s)<option @selected(old('statut_carriere', $talent->statut_carriere) === $s)>{{ $s }}</option>@endforeach
                        </select></div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="haut_potentiel" id="haut_potentiel" value="1" @checked(old('haut_potentiel', $talent->haut_potentiel))>
                            <label for="haut_potentiel" class="form-check-label">Haut potentiel <i class="bi bi-star-fill text-warning"></i></label>
                        </div>
                    </div>
                    <div class="col-12"><label class="form-label">Observations</label>
                        <textarea name="observations" rows="3" class="form-control">{{ old('observations', $talent->observations) }}</textarea></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-camera"></i> Photo</div>
                <div class="card-body text-center">
                    @if ($talent->exists)@include('suivi.talents._avatar', ['t' => $talent, 'taille' => 'avatar-lg'])@endif
                    <input type="file" name="photo" accept="image/*" class="form-control mt-2">
                    <div class="form-text">JPG/PNG, 2 Mo max.</div>
                </div>
            </div>
            @unless ($talent->exists)
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-card-heading"></i> Licence nationale (facultatif)</div>
                    <div class="card-body">
                        <label class="form-label">N° de licence</label>
                        <input name="licence_numero" value="{{ old('licence_numero') }}" class="form-control mb-2" placeholder="FBF-2026-001">
                        <label class="form-label">Expiration</label>
                        <input type="date" name="licence_expiration" value="{{ old('licence_expiration') }}" class="form-control">
                        <div class="form-text">Les licences internationales s'ajoutent ensuite depuis la fiche.</div>
                    </div>
                </div>
            @endunless
            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button name="action" value="brouillon" class="btn btn-outline-success"><i class="bi bi-save"></i> Enregistrer</button>
                    @if (! $talent->exists || in_array($talent->statut_profil, ['Brouillon', 'Rejeté']))
                        <button name="action" value="soumettre" class="btn btn-bf"><i class="bi bi-send"></i> Enregistrer et soumettre à validation</button>
                    @endif
                    @if ($talent->motif_rejet)
                        <div class="alert alert-danger small mb-0"><strong>Motif du rejet :</strong> {{ $talent->motif_rejet }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    // Spécialités proposées selon la discipline ; disciplines filtrées selon la fédération.
    const specialites = @json($disciplines->mapWithKeys(fn ($d) => [$d->id => $d->specialites->pluck('nom')]));
    const fed = document.getElementById('federation_id'), disc = document.getElementById('discipline_id');
    function majSpecialites() {
        document.getElementById('liste_specialites').innerHTML = (specialites[disc.value] || []).map(s => `<option value="${s}">`).join('');
    }
    function majDisciplines() {
        [...disc.options].forEach(o => { if (o.value) o.hidden = o.dataset.federation && o.dataset.federation !== fed.value; });
        const choix = disc.selectedOptions[0];
        if (choix && choix.hidden) { disc.value = [...disc.options].find(o => o.value && !o.hidden)?.value || ''; }
        majSpecialites();
    }
    fed.addEventListener('change', majDisciplines);
    disc.addEventListener('change', majSpecialites);
    majDisciplines();
</script>
@endpush
