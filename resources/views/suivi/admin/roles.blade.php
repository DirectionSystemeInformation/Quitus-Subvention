@extends('suivi.layouts.app')
@section('titre', 'Rôles & permissions')

@php($libellesActions = ['voir' => 'Consulter', 'creer' => 'Créer', 'modifier' => 'Modifier', 'supprimer' => 'Supprimer', 'valider' => 'Valider',
    'exporter' => 'Exporter', 'verifier' => 'Vérifier API', 'decider' => 'Décider', 'gerer' => 'Gérer', 'national' => 'Vue nationale'])

@section('contenu')
<h1 class="h4 mb-1"><i class="bi bi-shield-lock text-success"></i> Rôles & permissions</h1>
<p class="text-muted small">Chaque acteur (Ministère, fédération, DTN, médecin, entraîneur, observateur...) dispose d'un rôle dont les droits sont réglables module par module.</p>

<div class="row g-3">
    <div class="col-md-3">
        <div class="list-group mb-3">
            @foreach ($roles as $r)
                <a href="{{ route('suivi.roles.index', ['role' => $r->id]) }}" class="list-group-item list-group-item-action d-flex justify-content-between {{ $role->id === $r->id ? 'active' : '' }}">
                    {{ $r->name }} <span class="badge text-bg-light">{{ $r->users_count }}</span></a>
            @endforeach
        </div>
        <form method="POST" action="{{ route('suivi.roles.store') }}" class="card card-body">@csrf
            <label class="form-label small">Nouveau rôle</label>
            <input name="name" class="form-control form-control-sm mb-2" placeholder="Ex : Comité olympique" required>
            <select name="copie_de" class="form-select form-select-sm mb-2"><option value="">Partir de zéro</option>
                @foreach ($roles as $r)<option value="{{ $r->id }}">Copier « {{ $r->name }} »</option>@endforeach</select>
            <button class="btn btn-sm btn-bf">Créer</button>
        </form>
    </div>
    <div class="col-md-9">
        <form method="POST" action="{{ route('suivi.roles.update', $role) }}" class="card">
            @csrf @method('PUT')
            <div class="card-header d-flex align-items-center">Permissions : {{ $role->name }}
                @if ($role->name === 'Super administrateur')<span class="badge text-bg-secondary ms-2">verrouillé</span>@endif
            </div>
            <div class="table-responsive"><table class="table table-sm mb-0 align-middle">
                <thead class="table-light"><tr><th>Module</th><th>Droits</th><th class="text-end">Tout</th></tr></thead>
                <tbody>
                @foreach ($modules as $module => $def)
                    <tr>
                        <td class="fw-semibold">{{ $def['label'] }}</td>
                        <td>
                            @foreach ($def['actions'] as $action)
                                @php($perm = "$module.$action")
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" class="form-check-input perm-{{ $module }}" name="permissions[]" value="{{ $perm }}" id="{{ $perm }}" @checked($role->hasPermissionTo($perm))>
                                    <label class="form-check-label small" for="{{ $perm }}">{{ $libellesActions[$action] ?? $action }}</label>
                                </div>
                            @endforeach
                        </td>
                        <td class="text-end"><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.perm-{{ $module }}').forEach(c => c.checked = this.checked)"></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            @unless ($role->name === 'Super administrateur')
                <div class="card-footer bg-white d-flex">
                    <button class="btn btn-bf">Enregistrer les permissions</button>
                    @if ($role->users_count === 0)
                        <button form="suppr-role" class="btn btn-link text-danger ms-auto">Supprimer ce rôle</button>
                    @endif
                </div>
            @endunless
        </form>
        <form id="suppr-role" method="POST" action="{{ route('suivi.roles.destroy', $role) }}" data-confirmer="Supprimer ce rôle ?">@csrf @method('DELETE')</form>
    </div>
</div>
@endsection
