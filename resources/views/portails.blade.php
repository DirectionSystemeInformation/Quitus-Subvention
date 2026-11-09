<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choisir un portail - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-enhancements.css') }}">
    <style>
        .portails { display: grid; gap: 1rem; margin-top: 1.5rem; }
        .portail { display: flex; gap: 1rem; align-items: flex-start; padding: 1.25rem; border: 1px solid rgba(0, 0, 0, .12);
            border-radius: 14px; text-decoration: none; color: inherit; background: #fff; transition: border-color .15s, box-shadow .15s, transform .15s; }
        .portail:hover, .portail:focus-visible { border-color: var(--accent-copper, #b5652d); box-shadow: 0 6px 18px rgba(0, 0, 0, .08); transform: translateY(-1px); }
        .portail-icone { flex: 0 0 48px; height: 48px; border-radius: 12px; display: grid; place-items: center; color: #fff; }
        .portail-icone.quitus { background: #1f3b2c; }
        .portail-icone.talents { background: linear-gradient(180deg, #c8102e 0 50%, #00843d 50% 100%); }
        .portail h2 { font-size: 1.05rem; margin: 0 0 .25rem; }
        .portail p { margin: 0; font-size: .9rem; opacity: .75; line-height: 1.45; }
        .portail-fleche { margin-left: auto; align-self: center; opacity: .5; }
        .portails-pied { margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; font-size: .9rem; }
        .portails-pied button { background: none; border: 0; padding: 0; color: inherit; text-decoration: underline; cursor: pointer; font: inherit; }
    </style>
</head>
<body class="auth-page">
    <div class="auth-branding">
        <div class="branding-content">
            <img src="{{ asset('img/armoiries.png') }}" alt="Armoiries du Burkina Faso" class="branding-logo branding-crest">
            <h2 class="branding-title branding-title-ministry">Ministère des Sports, de la Jeunesse et de l'Emploi</h2>
            <p class="branding-subtitle">Un seul compte pour les deux plateformes du Ministère : les subventions des fédérations et le suivi national des talents sportifs.</p>
        </div>
    </div>

    <div class="auth-form-container">
        <div class="auth-form-wrapper">
            <div class="form-header">
                <h1>Bonjour {{ $user->federation_name ?: $user->name }}</h1>
                <p>Choisissez le portail sur lequel vous souhaitez travailler. Vous pourrez changer à tout moment depuis le menu.</p>
            </div>

            <nav class="portails" aria-label="Portails disponibles">
                <a class="portail" href="{{ route(home_route_name($user)) }}">
                    <span class="portail-icone quitus" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/></svg>
                    </span>
                    <span>
                        <h2>Quitus de subvention</h2>
                        <p>Rapports et programmes d'activités, campagnes de subvention, délivrance du quitus.</p>
                    </span>
                    <span class="portail-fleche" aria-hidden="true">→</span>
                </a>
                <a class="portail" href="{{ route('suivi.dashboard') }}">
                    <span class="portail-icone talents" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="#fcd116"><polygon points="12,2 15,9 22,9.3 16.6,14 18.5,21 12,17 5.5,21 7.4,14 2,9.3 9,9"/></svg>
                    </span>
                    <span>
                        <h2>Suivi des talents sportifs</h2>
                        <p>Athlètes, licences, performances, suivi médical, bourses et reconversion.</p>
                    </span>
                    <span class="portail-fleche" aria-hidden="true">→</span>
                </a>
            </nav>

            <div class="portails-pied">
                <span>{{ \App\Support\PlatformNavigation::roleLabel($user) }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Se déconnecter</button></form>
            </div>
        </div>
    </div>
</body>
</html>
