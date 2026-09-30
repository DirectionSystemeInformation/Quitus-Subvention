<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session expirée - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-enhancements.css') }}">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; background: var(--canvas); color: var(--ink); font-size: 16px; }
        body::before { content: ''; position: fixed; inset: 0 0 auto; height: 6px; background: var(--flag-band); }
        .error-card { position: relative; overflow: hidden; }
        .error-card::after { content: ''; position: absolute; right: -40px; bottom: -40px; width: 160px; height: 152px; background: var(--flag-star) center / contain no-repeat; opacity: .12; transform: rotate(-12deg); pointer-events: none; }
        .error-card > * { position: relative; z-index: 1; }
        .error-country { display: flex; align-items: center; justify-content: center; gap: 8px; margin: -8px 0 20px; font-size: 13px; font-weight: 650; letter-spacing: .06em; text-transform: uppercase; color: var(--ink-3); }
        .error-card { max-width: 480px; width: 100%; padding: 48px 40px; text-align: center; background: var(--surface); border: 1px solid var(--line); border-radius: 20px; box-shadow: var(--shadow-md); }
        .error-card:hover { transform: none; box-shadow: var(--shadow-md); }
        .error-logo { width: 56px; height: 66px; margin-bottom: 24px; object-fit: contain; }
        .error-code { display: inline-block; margin-bottom: 16px; padding: 4px 14px; border-radius: 999px; background: var(--brand-50); color: var(--brand-600); font-size: 14px; font-weight: 700; letter-spacing: .08em; }
        .error-title { margin-bottom: 10px; font-size: 26px; font-weight: 800; letter-spacing: -.02em; color: var(--ink); }
        .error-text { margin-bottom: 28px; font-size: 16px; line-height: 1.6; color: var(--ink-3); }
        .error-card .btn { display: inline-flex; align-items: center; min-height: 46px; padding: 12px 22px; border-radius: 12px; background: var(--brand-600); border: 0; color: #fff; font-weight: 650; text-decoration: none; }
        .error-card .btn:hover { background: var(--brand-700); }
        .error-card .btn:focus-visible { outline: 2px solid var(--brand-600); outline-offset: 3px; }
    </style>
</head>
<body>
    <div class="card error-card reveal">
        <img src="{{ asset('img/armoiries.png') }}" alt="Armoiries du Burkina Faso" class="error-logo">
        <p class="error-country"><x-flag :width="24" label="" />Burkina Faso</p>
        <div class="error-code">Erreur 419</div>
        <h1 class="error-title">Session expirée</h1>
        <p class="error-text">Votre session a expiré par inactivité. Merci de vous reconnecter.</p>
        <a href="{{ route('login') }}" class="btn primary">Se reconnecter</a>
    </div>
</body>
</html>
