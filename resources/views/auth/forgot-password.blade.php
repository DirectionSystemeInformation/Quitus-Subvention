<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - {{ config('app.name') }}</title>
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
                <h1>Mot de passe oublié</h1>
                <p>Indiquez votre adresse e-mail, nous vous enverrons un lien de réinitialisation</p>
            </div>

            @if (session('status'))
                <div class="auth-feedback auth-feedback-success" role="status">{{ session('status') }}</div>
            @endif

            <form class="auth-form active" method="POST" action="{{ route('password.email') }}">
                @csrf
                <p class="auth-required-hint">L'adresse e-mail est obligatoire.</p>
                <div class="form-group">
                    <label for="forgotEmail" class="form-label">Adresse e-mail</label>
                    <div class="form-input-wrapper">
                        <svg aria-hidden="true" class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input type="email" id="forgotEmail" name="email" class="form-input" placeholder="votre@email.com" value="{{ old('email') }}" autocomplete="email" autocapitalize="none" spellcheck="false" @if ($errors->has('email')) aria-invalid="true" aria-describedby="forgotEmailError" @endif required>
                    </div>
                    @error('email')
                        <p id="forgotEmailError" class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="submit-btn">Envoyer le lien de réinitialisation</button>

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
