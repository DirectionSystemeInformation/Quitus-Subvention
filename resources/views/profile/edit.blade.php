@extends('layouts.dashboard', ['active' => 'profile'])

@section('title', 'Mon profil')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Mon profil</h1>
        <p>Modifiez vos informations de compte et votre mot de passe</p>
    </div>

    <div class="card" style="max-width: 560px;">
        <div class="card-header">
            <h2 class="card-title">Informations du compte</h2>
        </div>

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            @if ($user->isFederation())
                <div class="form-group">
                    <label class="form-label">Dénomination de la fédération</label>
                    <input type="text" name="federation_name" class="form-input" value="{{ old('federation_name', $user->federation_name) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Numéro de l'arrêté de validation du MSJE</label>
                    <input type="text" name="arrete_numero" class="form-input" value="{{ old('arrete_numero', $user->arrete_numero) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Date de l'arrêté de validation</label>
                    <input type="date" name="arrete_date" class="form-input" value="{{ old('arrete_date', optional($user->arrete_date)->format('Y-m-d')) }}" required>
                </div>
            @else
                <div class="form-group">
                    <label class="form-label">Nom</label>
                    <input type="text" name="name" class="form-input" value="{{ old('name', $user->name) }}" required>
                </div>
            @endif

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required>
            </div>

            <hr style="border-color: var(--border, #333); margin: 20px 0;">

            <p class="strength-text" style="margin-bottom: 12px;">Laissez les champs suivants vides si vous ne souhaitez pas changer de mot de passe.</p>

            <div class="form-group">
                <label class="form-label">Mot de passe actuel</label>
                <input type="password" name="current_password" class="form-input" autocomplete="current-password">
            </div>

            <div class="form-group">
                <label class="form-label">Nouveau mot de passe</label>
                <input type="password" name="password" class="form-input" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label class="form-label">Confirmer le nouveau mot de passe</label>
                <input type="password" name="password_confirmation" class="form-input" autocomplete="new-password">
            </div>

            <div class="modal-actions" style="justify-content: flex-start;">
                <button type="submit" class="btn primary">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
@endsection
