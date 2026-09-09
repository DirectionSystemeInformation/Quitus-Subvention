@extends('layouts.dashboard', ['active' => 'users'])

@section('title', 'Comptes DSHN')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <style>
        .market-table td input, .market-table td select { min-width: 140px; }
        .market-table td.password-cell { min-width: 150px; }
        .market-table tfoot tr { border-top: 1px dashed #cbd5e1; background: var(--bg-primary); }
        .market-table tfoot td { padding-top: 18px; padding-bottom: 18px; }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <h1>Comptes DSHN</h1>
        <p>Gestion des comptes agents DSHN et administrateurs.</p>
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
    <form id="add-user" method="POST" action="{{ role_route('users.store') }}" style="display:none;">
        @csrf
    </form>

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
                    @php $fid = $user->id; @endphp
                    <tr data-search-row>
                        <td><input type="text" name="name_{{ $fid }}" form="edit-user-{{ $fid }}" class="form-input" value="{{ old("name_$fid", $user->name) }}" required></td>
                        <td><input type="email" name="email_{{ $fid }}" form="edit-user-{{ $fid }}" class="form-input" value="{{ old("email_$fid", $user->email) }}" required></td>
                        <td>
                            <select name="role_{{ $fid }}" form="edit-user-{{ $fid }}" class="form-select">
                                @foreach ($roleOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old("role_$fid", $user->role) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="password-cell"><input type="password" name="password_{{ $fid }}" form="edit-user-{{ $fid }}" class="form-input" placeholder="Optionnel"></td>
                        <td class="password-cell"><input type="password" name="password_{{ $fid }}_confirmation" form="edit-user-{{ $fid }}" class="form-input" placeholder="Confirmer"></td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <button type="submit" form="edit-user-{{ $fid }}" class="icon-btn" title="Enregistrer">{!! $saveIcon !!}</button>
                                <button type="submit" form="delete-user-{{ $fid }}" class="icon-btn danger" title="Supprimer">{!! $trashIcon !!}</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td><input type="text" name="name" form="add-user" class="form-input" placeholder="Nom" required></td>
                    <td><input type="email" name="email" form="add-user" class="form-input" placeholder="Email" required></td>
                    <td>
                        <select name="role" form="add-user" class="form-select">
                            @foreach ($roleOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="password-cell"><input type="password" name="password" form="add-user" class="form-input" placeholder="Mot de passe" required></td>
                    <td class="password-cell"><input type="password" name="password_confirmation" form="add-user" class="form-input" placeholder="Confirmer" required></td>
                    <td><button type="submit" form="add-user" class="btn primary" style="white-space:nowrap;">+ Ajouter</button></td>
                </tr>
            </tfoot>
        </table>
        </div>
        <x-empty-state icon="search" title="Aucun résultat." :compact="true" class="js-table-empty" data-target="users-table" style="display:none;" />
    </div>
@endsection
