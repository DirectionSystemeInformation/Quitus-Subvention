<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - {{ config('app.name') }}</title>
    <script>
        // Load theme immediately to prevent flash
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-enhancements.css') }}">
</head>
<body class="auth-page">
    @php
        $registerHasErrors = $errors->has('federation_name') || $errors->has('arrete_numero') || $errors->has('arrete_date') || $errors->has('password_confirmation') || old('federation_name') || request()->query('tab') === 'register';
    @endphp

    <!-- Left Side - Branding -->
    <div class="auth-branding">
        <div class="branding-content">
            <img src="{{ asset('img/armoiries.png') }}" alt="Armoiries du Burkina Faso" class="branding-logo branding-crest">
            <h1 class="branding-title branding-title-ministry">Ministère des Sports, de la Jeunesse et de l'Emploi</h1>
            <p class="branding-subtitle">Portail des fédérations sportives et de loisirs pour le dépôt des rapports d'activité et l'obtention du quitus de déblocage de subvention</p>

            <div class="branding-features">
                <div class="branding-feature">
                    <div class="feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    Compte validé par la Direction du Sport de Haut Niveau
                </div>
                <div class="branding-feature">
                    <div class="feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                    </div>
                    Dépôt du rapport d'activité et du programme budgétisé
                </div>
                <div class="branding-feature">
                    <div class="feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    Suivi du statut de votre demande de subvention
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side - Form -->
    <div class="auth-form-container">
        <button class="theme-toggle-btn" id="themeToggle">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="5"/>
                <line x1="12" y1="1" x2="12" y2="3"/>
                <line x1="12" y1="21" x2="12" y2="23"/>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                <line x1="1" y1="12" x2="3" y2="12"/>
                <line x1="21" y1="12" x2="23" y2="12"/>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
            </svg>
        </button>

        <div class="auth-form-wrapper">
            <div class="form-header">
                <h1>Bienvenue</h1>
                <p>Connectez-vous ou créez le compte de votre fédération</p>
            </div>

            @if (session('status'))
                <div id="flashStatus" data-message="{{ session('status') }}" data-type="success" style="display:none;"></div>
            @endif

            @if ($errors->has('identifiant') && !$registerHasErrors)
                <p class="strength-text" style="color: var(--loss, #c27878); margin-bottom: 16px;">{{ $errors->first('identifiant') }}</p>
            @endif

            <!-- Tab Switcher -->
            <div class="auth-tabs">
                <button type="button" class="auth-tab {{ $registerHasErrors ? '' : 'active' }}" data-form="login">Connexion</button>
                <button type="button" class="auth-tab {{ $registerHasErrors ? 'active' : '' }}" data-form="register">Créer un compte</button>
            </div>

            <!-- Login Form -->
            <form class="auth-form {{ $registerHasErrors ? '' : 'active' }}" id="loginForm" method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Email ou numéro d'arrêté</label>
                    <div class="form-input-wrapper">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input type="text" name="identifiant" class="form-input" placeholder="votre@email.com ou numéro d'arrêté" value="{{ old('identifiant') }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Mot de passe</label>
                    <div class="form-input-wrapper">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                        </svg>
                        <input type="password" name="password" class="form-input" id="loginPassword" placeholder="Votre mot de passe" required>
                        <button type="button" class="password-toggle" data-target="loginPassword">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-row">
                    <div class="checkbox-wrapper">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="remember" style="width:16px;height:16px;">
                            <span class="checkbox-label">Se souvenir de moi</span>
                        </label>
                    </div>
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
                <div class="form-group">
                    <label class="form-label">Dénomination de la fédération</label>
                    <div class="form-input-wrapper">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <input type="text" name="federation_name" class="form-input" placeholder="Ex. Fédération Burkinabè de Football" value="{{ old('federation_name') }}" required>
                    </div>
                    @error('federation_name')
                        <p class="strength-text" style="color: var(--loss, #c27878);">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Numéro de l'arrêté de validation du MSJE</label>
                    <div class="form-input-wrapper">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        <input type="text" name="arrete_numero" class="form-input" placeholder="Ex. 2024-0123/MSJE/SG" value="{{ old('arrete_numero') }}" required>
                    </div>
                    @error('arrete_numero')
                        <p class="strength-text" style="color: var(--loss, #c27878);">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Date de l'arrêté de validation</label>
                    <div class="form-input-wrapper">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <input type="date" name="arrete_date" class="form-input" value="{{ old('arrete_date') }}" required>
                    </div>
                    @error('arrete_date')
                        <p class="strength-text" style="color: var(--loss, #c27878);">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Adresse e-mail</label>
                    <div class="form-input-wrapper">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input type="email" name="email" class="form-input" placeholder="votre@email.com" value="{{ old('email') }}" required>
                    </div>
                    @error('email')
                        <p class="strength-text" style="color: var(--loss, #c27878);">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Mot de passe</label>
                    <div class="form-input-wrapper">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                        </svg>
                        <input type="password" name="password" class="form-input" id="registerPassword" placeholder="Créer un mot de passe" required>
                        <button type="button" class="password-toggle" data-target="registerPassword">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="strength-text" style="color: var(--loss, #c27878);">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <div class="form-input-wrapper">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                        </svg>
                        <input type="password" name="password_confirmation" class="form-input" placeholder="Confirmer le mot de passe" required>
                    </div>
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
