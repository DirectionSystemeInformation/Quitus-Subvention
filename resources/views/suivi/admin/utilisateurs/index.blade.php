@extends('suivi.layouts.app')
@section('titre', 'Utilisateurs')

@section('contenu')
<div class="d-flex align-items-center mb-3">
    <h1 class="h4 mb-0"><i class="bi bi-person-gear text-success"></i> Utilisateurs du suivi des talents</h1>
    @if ($origine === 'talents')
        <a href="{{ route('suivi.utilisateurs.create') }}" class="btn btn-bf btn-sm ms-auto"><i class="bi bi-person-plus"></i> Nouveau compte</a>
    @endif
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link {{ $origine === 'talents' ? 'active' : '' }}" href="{{ route('suivi.utilisateurs.index') }}">Comptes complémentaires (DTN, médecin...)</a></li>
    <li class="nav-item"><a class="nav-link {{ $origine === 'quitus' ? 'active' : '' }}" href="{{ route('suivi.utilisateurs.index', ['origine' => 'quitus']) }}">Comptes Quitus</a></li>
</ul>
@if ($origine === 'quitus')
    <div class="alert alert-info small">Ces comptes sont gérés dans Quitus. Leur rôle dans le suivi des talents découle automatiquement de leur rôle Quitus (fédération → Fédération, DSHN → Ministère des Sports, cabinet → Observateur national...).</div>
@endif

<form class="card card-body filtres mb-3 py-2">
    <input type="hidden" name="origine" value="{{ $origine }}">
    <div class="row g-2">
        <div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Nom, e-mail ou fédération"></div>
        <div class="col-md-3"><select name="role" class="form-select form-select-sm"><option value="">Tous les rôles</option>
            @foreach ($roles as $r)<option @selected(request('role') === $r)>{{ $r }}</option>@endforeach</select></div>
        @if ($origine === 'talents')
            <div class="col-md-3"><select name="federation_id" class="form-select form-select-sm"><option value="">Toutes structures</option>
                @foreach ($federations as $f)<option value="{{ $f->id }}" @selected(request('federation_id') == $f->id)>{{ $f->sigle }}</option>@endforeach</select></div>
        @endif
        <div class="col-auto"><button class="btn btn-sm btn-success">Filtrer</button></div>
    </div>
</form>

<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead class="table-light"><tr><th>Nom</th><th>E-mail</th><th>Rôle(s) talents</th><th>Périmètre</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @forelse ($utilisateurs as $u)
        <tr>
            <td><strong>{{ $u->federation_name ?: $u->name }}</strong><br><small class="text-muted">{{ $u->fonction ?? \App\Support\PlatformNavigation::roleLabel($u) }}</small></td>
            <td>{{ $u->email }}</td>
            <td>@foreach ($u->roles as $r){{ badge($r->name, 'success') }} @endforeach</td>
            <td>{{ $u->federationTalents()?->sigle ?? 'National' }}</td>
            <td>{{ $u->status === 'active' ? badge('Actif') : badge('Désactivé', 'secondary') }}</td>
            <td class="text-end text-nowrap">
                @if ($u->isComptesTalents())
                    <a href="{{ route('suivi.utilisateurs.edit', $u) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="{{ route('suivi.utilisateurs.destroy', $u) }}" class="d-inline" data-confirmer="Supprimer ce compte ?">@csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                @else
                    <span class="badge text-bg-light border"><i class="bi bi-link-45deg"></i> Quitus</span>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center text-muted py-4">Aucun compte.</td></tr>
    @endforelse
    </tbody>
</table></div>
@if ($utilisateurs->hasPages())<div class="card-footer bg-white">{{ $utilisateurs->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
