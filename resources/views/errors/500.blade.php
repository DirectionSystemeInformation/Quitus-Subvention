<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur du serveur - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-enhancements.css') }}">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
        .error-card { max-width: 460px; width: 100%; text-align: center; padding: 48px 40px; }
        .error-logo { width: 56px; height: 56px; margin-bottom: 24px; }
        .error-code { font-size: 64px; font-weight: 800; color: var(--color-danger); line-height: 1; margin-bottom: 16px; }
        .error-title { font-size: 22px; font-weight: 700; margin-bottom: 12px; }
        .error-text { color: var(--text-secondary); margin-bottom: 28px; }
    </style>
</head>
<body>
    <div class="flag-ribbon" aria-hidden="true"></div>
    <div class="card error-card reveal">
        <img src="{{ asset('img/armoiries.png') }}" alt="Armoiries du Burkina Faso" class="error-logo">
        <div class="error-code">500</div>
        <h1 class="error-title">Une erreur est survenue</h1>
        <p class="error-text">Un problème technique est survenu de notre côté. Merci de réessayer dans un instant.</p>
        <a href="{{ url('/') }}" class="btn primary">Retour à l'accueil</a>
    </div>
</body>
</html>
