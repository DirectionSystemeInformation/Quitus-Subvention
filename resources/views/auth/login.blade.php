<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-enhancements.css') }}">
</head>
<body class="auth-page">
    @php
        $registerHasErrors = $errors->has('federation_name') || $errors->has('arrete_numero') || $errors->has('arrete_date') || $errors->has('email') || $errors->has('password_confirmation') || old('federation_name') || old('email') || request()->query('tab') === 'register';
    @endphp

    <!-- Left Side - Branding -->
    <div class="auth-branding">
        <div class="branding-content">
            <img src="{{ asset('img/armoiries.png') }}" alt="Armoiries du Burkina Faso" class="branding-logo branding-crest">
            <h2 class="branding-title branding-title-ministry">Ministère des Sports, de la Jeunesse et de l'Emploi</h2>
            <p class="branding-subtitle">Quitus de subvention : votre espace pour déclarer vos activités, préparer votre programme et suivre votre dossier.</p>

            <div class="branding-features">
                <div class="branding-feature">
                    <div class="feature-icon">
                        <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    Compte validé par la Direction du Sport de Haut Niveau
                </div>
                <div class="branding-feature">
                    <div class="feature-icon">
                        <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                    </div>
                    Rapport constitué après validation de vos activités par la DGF
                </div>
                <div class="branding-feature">
                    <div class="feature-icon">
                        <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    Suivi du programme budgétisé jusqu'au quitus
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side - Form -->
    <div class="auth-form-container">
        <div class="auth-form-wrapper">
            <a href="{{ url('/') }}" class="auth-home-link">← Retour à l'accueil</a>
            <div class="form-header">
                <h1>Bienvenue</h1>
                <p>Connectez-vous ou créez le compte de votre fédération</p>
            </div>

            @if (session('status'))
                <div class="auth-feedback auth-feedback-success" role="status">{{ session('status') }}</div>
            @endif

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

            <!-- Tab Switcher -->
            <div class="auth-tabs" role="group" aria-label="Accès au compte">
                <button type="button" class="auth-tab {{ $registerHasErrors ? '' : 'active' }}" data-form="login" aria-controls="loginForm" aria-pressed="{{ $registerHasErrors ? 'false' : 'true' }}">Connexion</button>
                <button type="button" class="auth-tab {{ $registerHasErrors ? 'active' : '' }}" data-form="register" aria-controls="registerForm" aria-pressed="{{ $registerHasErrors ? 'true' : 'false' }}">Créer un compte</button>
            </div>

            <!-- Login Form -->
            <form class="auth-form {{ $registerHasErrors ? '' : 'active' }}" id="loginForm" method="POST" action="{{ route('login') }}">
                @csrf
                <p class="auth-required-hint">Les deux champs sont obligatoires.</p>
                <div class="form-group">
                    <label for="loginIdentifier" class="form-label">E-mail ou numéro d'arrêté</label>
                    <div class="form-input-wrapper">
                        <svg aria-hidden="true" class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input type="text" id="loginIdentifier" name="identifiant" class="form-input" placeholder="votre@email.com ou numéro d'arrêté" value="{{ old('identifiant') }}" autocomplete="username" autocapitalize="none" spellcheck="false" @if ($errors->has('identifiant')) aria-invalid="true" aria-describedby="loginIdentifierError" @endif required>
                    </div>
                    @error('identifiant')
                        <p id="loginIdentifierError" class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="loginPassword" class="form-label">Mot de passe</label>
                    <div class="form-input-wrapper">
                        <svg aria-hidden="true" class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                        </svg>
                        <input type="password" name="password" class="form-input" id="loginPassword" placeholder="Votre mot de passe" autocomplete="current-password" @if (!$registerHasErrors && $errors->has('password')) aria-invalid="true" aria-describedby="loginPasswordError" @endif required>
                        <button type="button" class="password-toggle" data-target="loginPassword" aria-controls="loginPassword" aria-label="Afficher le mot de passe" aria-pressed="false">
                            <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    @if (!$registerHasErrors && $errors->has('password'))
                        <p id="loginPasswordError" class="field-error">{{ $errors->first('password') }}</p>
                    @endif
                </div>

                <div class="form-row">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="remember">
                        <span class="checkbox-label">Se souvenir de moi</span>
                    </label>
                    <a href="{{ route('password.request') }}" style="font-size: 13px;">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="submit-btn">Se connecter</button>

                <p class="form-footer">
                    Pas encore de compte ? <a href="#" id="switchToRegister">Créer un compte fédération</a>
                </p>
            </form>

            <!-- Register Form -->
            <form class="auth-form {{ $registerHasErrors ? 'active' : '' }}" id="registerForm" method="POST" action="{{ route('register') }}">
                @csrf
                <p class="auth-required-hint">Tous les champs sont obligatoires. Votre demande sera examinée par la DSHN avant l'activation du compte.</p>
                <div class="form-group">
                    <label for="registerFederation" class="form-label">Dénomination de la fédération</label>
                    <div class="form-input-wrapper">
                        <svg aria-hidden="true" class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <input type="text" id="registerFederation" name="federation_name" class="form-input" placeholder="Ex. Fédération Burkinabè de Football" value="{{ old('federation_name') }}" autocomplete="organization" maxlength="255" @if ($errors->has('federation_name')) aria-invalid="true" aria-describedby="registerFederationError" @endif required>
                    </div>
                    @error('federation_name')
                        <p id="registerFederationError" class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="registerArrete" class="form-label">Numéro de l'arrêté de validation du MSJE</label>
                    <div class="form-input-wrapper">
                        <svg aria-hidden="true" class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        <input type="text" id="registerArrete" name="arrete_numero" class="form-input" placeholder="Ex. 2024-0123/MSJE/SG" value="{{ old('arrete_numero') }}" maxlength="255" spellcheck="false" @if ($errors->has('arrete_numero')) aria-invalid="true" aria-describedby="registerArreteError" @endif required>
                    </div>
                    @error('arrete_numero')
                        <p id="registerArreteError" class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="registerArreteDate" class="form-label">Date de l'arrêté de validation</label>
                    <div class="form-input-wrapper">
                        <svg aria-hidden="true" class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <input type="date" id="registerArreteDate" name="arrete_date" class="form-input" value="{{ old('arrete_date') }}" @if ($errors->has('arrete_date')) aria-invalid="true" aria-describedby="registerArreteDateError" @endif required>
                    </div>
                    @error('arrete_date')
                        <p id="registerArreteDateError" class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="registerEmail" class="form-label">Adresse e-mail</label>
                    <div class="form-input-wrapper">
                        <svg aria-hidden="true" class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input type="email" id="registerEmail" name="email" class="form-input" placeholder="votre@email.com" value="{{ old('email') }}" autocomplete="email" autocapitalize="none" spellcheck="false" maxlength="255" @if ($errors->has('email')) aria-invalid="true" aria-describedby="registerEmailError" @endif required>
                    </div>
                    @error('email')
                        <p id="registerEmailError" class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="registerPassword" class="form-label">Mot de passe</label>
                    <div class="form-input-wrapper">
                        <svg aria-hidden="true" class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                        </svg>
                        <input type="password" name="password" class="form-input" id="registerPassword" placeholder="Créer un mot de passe" autocomplete="new-password" @if ($errors->has('password')) aria-invalid="true" aria-describedby="registerPasswordError" @endif required>
                        <button type="button" class="password-toggle" data-target="registerPassword" aria-controls="registerPassword" aria-label="Afficher le mot de passe" aria-pressed="false">
                            <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p id="registerPasswordError" class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="registerPasswordConfirmation" class="form-label">Confirmer le mot de passe</label>
                    <div class="form-input-wrapper">
                        <svg aria-hidden="true" class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                        </svg>
                        <input type="password" id="registerPasswordConfirmation" name="password_confirmation" class="form-input" placeholder="Confirmer le mot de passe" autocomplete="new-password" @if ($errors->has('password_confirmation')) aria-invalid="true" aria-describedby="registerPasswordConfirmationError" @endif required>
                    </div>
                    @error('password_confirmation')
                        <p id="registerPasswordConfirmationError" class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="submit-btn">Créer le compte</button>

                <p class="form-footer">
                    Déjà un compte ? <a href="#" id="switchToLogin">Se connecter</a>
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
