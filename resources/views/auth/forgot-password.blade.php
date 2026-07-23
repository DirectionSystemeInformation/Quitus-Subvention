<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - {{ config('app.name') }}</title>
    <script>
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
    <div class="auth-branding">
        <div class="branding-content">
            <img src="{{ asset('img/armoiries.png') }}" alt="Armoiries du Burkina Faso" class="branding-logo branding-crest">
            <h1 class="branding-title branding-title-ministry">Ministère des Sports, de la Jeunesse et de l'Emploi</h1>
            <p class="branding-subtitle">Portail des fédérations sportives et de loisirs pour le dépôt des rapports d'activité et l'obtention du quitus de déblocage de subvention</p>
        </div>
    </div>

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
                <h1>Mot de passe oublié</h1>
                <p>Indiquez votre adresse e-mail, nous vous enverrons un lien de réinitialisation</p>
            </div>

            @if (session('status'))
                <div id="flashStatus" data-message="{{ session('status') }}" data-type="success" style="display:none;"></div>
            @endif

            @if ($errors->has('email'))
                <p class="strength-text" style="color: var(--loss, #c27878); margin-bottom: 16px;">{{ $errors->first('email') }}</p>
            @endif

            <form class="auth-form active" method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Adresse e-mail</label>
                    <div class="form-input-wrapper">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input type="email" name="email" class="form-input" placeholder="votre@email.com" value="{{ old('email') }}" required autofocus>
                    </div>
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
