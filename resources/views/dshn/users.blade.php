@extends('layouts.dashboard', ['active' => 'users'])

@section('title', 'Comptes agents')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <style>
        .market-table td input, .market-table td select { min-width: 140px; }
        .market-table td.password-cell { min-width: 150px; }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <h1>Comptes agents</h1>
        <p>Gérez les comptes et les rôles des intervenants de la plateforme.</p>
    </div>

    @php
        $saveIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
        $trashIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>';
        $roleOptions = [
            'dshn' => 'Agent DSHN',
            'admin' => 'Administrateur',
            'dg' => 'Directeur Général',
            'comite_arbitrage' => "Comité d'arbitrage budgétaire",
            'ministre' => 'Ministre',
            'dgf' => 'DGF',
        ];
        $isUserCreation = old('_creation') === 'user';
    @endphp

    {{-- Forms live outside the table markup (a <form> is not a valid child of <tbody>/<tr>);
         inputs inside the table cells reference them via the HTML `form="..."` attribute. --}}
    @foreach ($users as $user)
        <form id="edit-user-{{ $user->id }}" method="POST" action="{{ role_route('users.update', $user) }}" style="display:none;">
            @csrf
            @method('PUT')
        </form>
        <form id="delete-user-{{ $user->id }}" method="POST" action="{{ role_route('users.destroy', $user) }}" data-confirm="Supprimer le compte de {{ $user->name }} ?" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
    <div class="creation-ui">
        <x-form-section step="+" title="Créer un compte" description="Renseignez l’identité de l’agent et les accès nécessaires à sa mission.">
            <form id="add-user" method="POST" action="{{ role_route('users.store') }}" @if (old('_creation') === 'user') data-error-scope @endif>
                @csrf
                <input type="hidden" name="_creation" value="user">
                <p class="creation-required">Tous les champs sont obligatoires.</p>
                <div class="creation-grid">
                    <div class="form-group"><label class="form-label" for="newUserName">Nom complet</label><input id="newUserName" type="text" name="name" class="form-input" value="{{ $isUserCreation ? old('name') : '' }}" placeholder="Prénom et nom" autocomplete="name" maxlength="255" required></div>
                    <div class="form-group"><label class="form-label" for="newUserEmail">Adresse e-mail</label><input id="newUserEmail" type="email" name="email" class="form-input" value="{{ $isUserCreation ? old('email') : '' }}" placeholder="prenom.nom@exemple.bf" autocomplete="email" maxlength="255" required></div>
                    <div class="form-group full-width"><label class="form-label" for="newUserRole">Rôle dans la plateforme</label><select id="newUserRole" name="role" class="form-select" aria-describedby="newUserRoleHint" required>@foreach ($roleOptions as $value => $label)<option value="{{ $value }}" @selected(($isUserCreation ? old('role', 'dshn') : 'dshn') === $value)>{{ $label }}</option>@endforeach</select><p class="creation-hint" id="newUserRoleHint">Le rôle détermine les écrans et les actions accessibles à ce compte.</p></div>
                    <div class="form-group"><label class="form-label" for="newUserPassword">Mot de passe</label><input id="newUserPassword" type="password" name="password" class="form-input" autocomplete="new-password" aria-describedby="newPasswordHint" required><p id="newPasswordHint" class="creation-hint">8 caractères minimum.</p></div>
                    <div class="form-group"><label class="form-label" for="newUserConfirmation">Confirmer le mot de passe</label><input id="newUserConfirmation" type="password" name="password_confirmation" class="form-input" autocomplete="new-password" required></div>
                </div>
                <div class="creation-actions"><p>Le compte sera actif dès sa création.</p><button type="submit" class="btn primary">Créer le compte</button></div>
            </form>
        </x-form-section>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Comptes</h2>
            @if ($users->isNotEmpty())
                <input type="search" class="form-input js-table-search" data-target="users-table" placeholder="Rechercher..." style="max-width: 260px;">
            @endif
        </div>

        <div class="table-responsive">
        <table class="market-table sortable" id="users-table">
            <thead>
                <tr>
                    <th data-sort="text">Nom</th>
                    <th data-sort="text">Email</th>
                    <th data-sort="text">Rôle</th>
                    <th>Nouveau mot de passe</th>
                    <th>Confirmer</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr data-search-row>
                        <td><input type="text" name="name" form="edit-user-{{ $user->id }}" class="form-input" value="{{ $user->name }}" required></td>
                        <td><input type="email" name="email" form="edit-user-{{ $user->id }}" class="form-input" value="{{ $user->email }}" required></td>
                        <td>
                            <select name="role" form="edit-user-{{ $user->id }}" class="form-select">
                                @foreach ($roleOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $user->role === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="password-cell"><input type="password" name="password" form="edit-user-{{ $user->id }}" class="form-input" placeholder="Optionnel"></td>
                        <td class="password-cell"><input type="password" name="password_confirmation" form="edit-user-{{ $user->id }}" class="form-input" placeholder="Confirmer"></td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <button type="submit" form="edit-user-{{ $user->id }}" class="icon-btn" title="Enregistrer">{!! $saveIcon !!}</button>
                                <button type="submit" form="delete-user-{{ $user->id }}" class="icon-btn danger" title="Supprimer">{!! $trashIcon !!}</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        <x-empty-state icon="search" title="Aucun résultat." :compact="true" class="js-table-empty" data-target="users-table" style="display:none;" />
    </div>
@endsection
