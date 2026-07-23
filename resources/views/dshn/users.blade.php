@extends('layouts.dashboard', ['active' => 'users'])

@section('title', 'Comptes DSHN')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <style>
        .user-item { display: flex; gap: 10px; align-items: flex-start; padding: 14px 0; border-bottom: 1px solid var(--border, #333); flex-wrap: wrap; }
        .user-item:last-child { border-bottom: none; }
        .user-item form.user-edit-form { display: flex; gap: 10px; align-items: flex-start; flex: 1; flex-wrap: wrap; }
        .user-edit-form input, .user-edit-form select { min-width: 160px; }
        .user-edit-form input[type="password"] { min-width: 160px; }
        .user-icon-btn {
            display: flex; align-items: center; justify-content: center;
            width: 38px; height: 38px; border-radius: 8px;
            background: var(--bg-secondary, rgba(255,255,255,0.06)); border: 1px solid var(--border, #333);
            color: var(--text-primary); cursor: pointer; flex-shrink: 0;
        }
        .user-icon-btn:hover { background: var(--bg-card-hover); }
        .user-icon-btn.danger { color: var(--loss, #c27878); }
        .user-icon-btn svg { width: 18px; height: 18px; }
        .user-add-form { display: flex; gap: 10px; align-items: flex-start; margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border, #333); flex-wrap: wrap; }
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
    @endphp

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Comptes</h2>
            @if ($users->isNotEmpty())
                <input type="search" class="form-input js-table-search" data-target="users-list" placeholder="Rechercher..." style="max-width: 260px;">
            @endif
        </div>

        <div id="users-list">
            @foreach ($users as $user)
                <div class="user-item" data-search-row>
                <form method="POST" action="{{ role_route('users.update', $user) }}" class="user-edit-form">
                    @csrf
                    @method('PUT')
                    <input type="text" name="name" class="form-input" value="{{ $user->name }}" required placeholder="Nom">
                    <input type="email" name="email" class="form-input" value="{{ $user->email }}" required placeholder="Email">
                    <select name="role" class="form-select">
                        <option value="dshn" {{ $user->role === 'dshn' ? 'selected' : '' }}>Agent DSHN</option>
                        <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Administrateur</option>
                    </select>
                    <input type="password" name="password" class="form-input" placeholder="Nouveau mot de passe (optionnel)">
                    <input type="password" name="password_confirmation" class="form-input" placeholder="Confirmer">
                    <button type="submit" class="user-icon-btn" title="Enregistrer">{!! $saveIcon !!}</button>
                </form>
                    <form method="POST" action="{{ role_route('users.destroy', $user) }}" data-confirm="Supprimer le compte de {{ $user->name }} ?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="user-icon-btn danger" title="Supprimer">{!! $trashIcon !!}</button>
                    </form>
                </div>
            @endforeach
            <p class="strength-text js-table-empty" data-target="users-list" style="display:none;">Aucun résultat.</p>
        </div>

        <form method="POST" action="{{ role_route('users.store') }}" class="user-add-form">
            @csrf
            <input type="text" name="name" class="form-input" placeholder="Nom" required>
            <input type="email" name="email" class="form-input" placeholder="Email" required>
            <select name="role" class="form-select">
                <option value="dshn">Agent DSHN</option>
                <option value="admin">Administrateur</option>
            </select>
            <input type="password" name="password" class="form-input" placeholder="Mot de passe" required>
            <input type="password" name="password_confirmation" class="form-input" placeholder="Confirmer" required>
            <button type="submit" class="btn primary">+ Ajouter un compte</button>
        </form>
    </div>
@endsection
