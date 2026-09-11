@extends('layouts.dashboard', ['active' => 'profile'])

@section('title', 'Mon compte')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    @php
        $identityLocked = $user->isFederation() && $user->status === 'active';
    @endphp

    <div class="page-header">
        <h1>Mon compte</h1>
        <p>{{ $user->isFederation() ? 'Consultez les informations de votre fédération et gérez votre compte.' : 'Gérez vos informations et votre mot de passe.' }}</p>
    </div>

    <div class="profile-grid">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">{{ $user->isFederation() ? 'Informations de la fédération' : 'Informations du compte' }}</h2>
            </div>

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                @if ($user->isFederation())
                    @if ($identityLocked)
                        <p class="strength-text" style="margin-bottom: 20px;">Ces informations ont été vérifiées lors de la validation de votre compte et ne sont plus modifiables directement. Contactez la DSHN pour toute correction.</p>

                        <div class="form-group">
                            <label class="form-label">Dénomination de la fédération</label>
                            <input type="text" class="form-input" value="{{ $user->federation_name }}" disabled>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Numéro de l'arrêté de validation du MSJE</label>
                            <input type="text" class="form-input" value="{{ $user->arrete_numero }}" disabled>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Date de l'arrêté de validation</label>
                            <input type="text" class="form-input" value="{{ optional($user->arrete_date)->format('d/m/Y') }}" disabled>
                        </div>
                    @else
                        <div class="form-group">
                            <label class="form-label">Dénomination de la fédération</label>
                            <input type="text" name="federation_name" class="form-input" value="{{ old('federation_name', $user->federation_name) }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Numéro de l'arrêté de validation du MSJE</label>
                            <input type="text" name="arrete_numero" class="form-input" value="{{ old('arrete_numero', $user->arrete_numero) }}" required>
                            @error('arrete_numero')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Date de l'arrêté de validation</label>
                            <input type="date" name="arrete_date" class="form-input" value="{{ old('arrete_date', optional($user->arrete_date)->format('Y-m-d')) }}" required>
                        </div>
                    @endif
                @else
                    <div class="form-group">
                        <label class="form-label">Nom</label>
                        <input type="text" name="name" class="form-input" value="{{ old('name', $user->name) }}" required>
                    </div>
                @endif

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required>
                    @error('email')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn primary">Enregistrer les informations</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Sécurité du compte</h2>
            </div>

            <form method="POST" action="{{ route('profile.password') }}" id="passwordForm">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Mot de passe actuel</label>
                    <div class="form-input-wrapper">
                        <input type="password" name="current_password" class="form-input" id="currentPasswordInput" autocomplete="current-password">
                        <button type="button" class="password-toggle" data-target="currentPasswordInput" aria-label="Afficher le mot de passe" aria-pressed="false">
                            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    @error('current_password')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Nouveau mot de passe</label>
                    <div class="form-input-wrapper">
                        <input type="password" name="password" class="form-input" id="newPasswordInput" autocomplete="new-password">
                        <button type="button" class="password-toggle" data-target="newPasswordInput" aria-label="Afficher le mot de passe" aria-pressed="false">
                            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <p class="strength-text" style="margin-top: 6px;">8 caractères minimum.</p>
                    @error('password')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label">Confirmer le nouveau mot de passe</label>
                    <div class="form-input-wrapper">
                        <input type="password" name="password_confirmation" class="form-input" id="newPasswordConfirmInput" autocomplete="new-password">
                        <button type="button" class="password-toggle" data-target="newPasswordConfirmInput" aria-label="Afficher le mot de passe" aria-pressed="false">
                            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <p id="passwordMatchHint" class="strength-text" style="margin-top: 6px; display:none;"></p>
                </div>

                <button type="submit" class="btn primary">Modifier le mot de passe</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const newPassword = document.getElementById('newPasswordInput');
            const confirmPassword = document.getElementById('newPasswordConfirmInput');
            const hint = document.getElementById('passwordMatchHint');
            if (!newPassword || !confirmPassword || !hint) return;

            function checkMatch() {
                if (!confirmPassword.value) {
                    hint.style.display = 'none';
                    return;
                }
                const matches = newPassword.value === confirmPassword.value;
                hint.style.display = '';
                hint.textContent = matches ? '✓ Les mots de passe correspondent' : 'Les mots de passe ne correspondent pas';
                hint.style.color = matches ? 'var(--color-success)' : 'var(--color-danger)';
            }

            newPassword.addEventListener('input', checkMatch);
            confirmPassword.addEventListener('input', checkMatch);
        })();
    </script>
@endpush
