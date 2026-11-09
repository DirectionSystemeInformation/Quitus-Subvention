@extends('suivi.layouts.app')
@section('titre', 'Journal d\'activité')

@section('contenu')
<h1 class="h4 mb-3"><i class="bi bi-clock-history text-success"></i> Journal d'activité</h1>

<form class="card card-body filtres mb-3 py-2"><div class="row g-2">
    <div class="col-md-2"><select name="module" class="form-select form-select-sm"><option value="">Tous modules</option>
        @foreach ($modules as $m)<option @selected(request('module') === $m)>{{ $m }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="action" class="form-select form-select-sm"><option value="">Toutes actions</option>
        @foreach ($actions as $a)<option @selected(request('action') === $a)>{{ $a }}</option>@endforeach</select></div>
    <div class="col-md-3"><select name="user_id" class="form-select form-select-sm"><option value="">Tous utilisateurs</option>
        @foreach ($utilisateurs as $u)<option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><input type="date" name="du" value="{{ request('du') }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><input type="date" name="au" value="{{ request('au') }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-sm btn-success">Filtrer</button></div>
</div></form>

<div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
    <thead class="table-light"><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Module</th><th>Objet</th><th>Champs modifiés</th><th>IP</th></tr></thead>
    <tbody>
    @forelse ($activites as $a)
        <tr>
            <td class="text-nowrap">{{ $a->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $a->user?->name ?? 'Système' }}</td>
            <td>{{ badge($a->action, match ($a->action) { 'suppression', 'rejet' => 'danger', 'validation' => 'success', 'création' => 'primary', default => 'secondary' }) }}</td>
            <td>{{ $a->module }}</td>
            <td>{{ $a->libelle }}</td>
            <td><small class="text-muted">{{ $a->modifications ? implode(', ', array_keys($a->modifications)) : '' }}</small></td>
            <td><small>{{ $a->ip }}</small></td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-4">Aucune activité.</td></tr>
    @endforelse
    </tbody>
</table></div>
@if ($activites->hasPages())<div class="card-footer bg-white">{{ $activites->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
