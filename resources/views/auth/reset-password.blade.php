<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-enhancements.css') }}">
</head>
<body class="auth-page">
    <div class="flag-ribbon" aria-hidden="true"></div>
    <div class="auth-branding">
        <div class="branding-content">
            <img src="{{ asset('img/armoiries.png') }}" alt="Armoiries du Burkina Faso" class="branding-logo branding-crest">
            <div class="crest-divider" aria-hidden="true"></div>
            <h2 class="branding-title branding-title-ministry">Ministère des Sports, de la Jeunesse et de l'Emploi</h2>
            <p class="branding-subtitle">Quitus de subvention : votre espace pour déclarer vos activités, préparer votre programme et suivre votre dossier.</p>
        </div>
    </div>

    <div class="auth-form-container">
        <div class="auth-form-wrapper">
            <a href="{{ url('/') }}" class="auth-home-link">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                Retour à l'accueil
            </a>
            <div class="form-header">
                <h1>Réinitialiser le mot de passe</h1>
                <p>Choisissez un nouveau mot de passe pour votre compte</p>
            </div>

            @if ($errors->any())
                <div class="auth-feedback auth-feedback-error" role="alert">
                    <strong>Vérifiez les informations saisies.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form class="auth-form active" method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <p class="auth-required-hint">Tous les champs sont obligatoires.</p>

                <div class="form-group">
                    <label for="resetEmail" class="form-label">Adresse e-mail</label>
                    <div class="form-input-wrapper">
                        <svg aria-hidden="true" class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input type="email" id="resetEmail" name="email" class="form-input" value="{{ old('email', $email) }}" autocomplete="email" autocapitalize="none" spellcheck="false" @if ($errors->has('email')) aria-invalid="true" aria-describedby="resetEmailError" @endif required>
                    </div>
                    @error('email')
                        <p id="resetEmailError" class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="resetPassword" class="form-label">Nouveau mot de passe</label>
                    <div class="form-input-wrapper">
                        <svg aria-hidden="true" class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                        </svg>
                        <input type="password" name="password" class="form-input" id="resetPassword" placeholder="Créer un mot de passe" autocomplete="new-password" @if ($errors->has('password')) aria-invalid="true" aria-describedby="resetPasswordError" @endif required>
                        <button type="button" class="password-toggle" data-target="resetPassword" aria-controls="resetPassword" aria-label="Afficher le mot de passe" aria-pressed="false">
                            <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p id="resetPasswordError" class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="resetPasswordConfirmation" class="form-label">Confirmer le mot de passe</label>
                    <div class="form-input-wrapper">
                        <svg aria-hidden="true" class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                        </svg>
                        <input type="password" id="resetPasswordConfirmation" name="password_confirmation" class="form-input" placeholder="Confirmer le mot de passe" autocomplete="new-password" @if ($errors->has('password_confirmation')) aria-invalid="true" aria-describedby="resetPasswordConfirmationError" @endif required>
                    </div>
                    @error('password_confirmation')
                        <p id="resetPasswordConfirmationError" class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="submit-btn">Réinitialiser le mot de passe</button>

                <p class="form-footer">
                    <a href="{{ route('login') }}">Retour à la connexion</a>
                </p>
            </form>
        </div>

        <p class="copyright">
            &copy; {{ date('Y') }} Ministère des Sports, de la Jeunesse et de l'Emploi
        </p>
    </div>

    <script src="{{ asset('js/templatemo-crypto-script.js') }}"></script>
</body>
</html>
