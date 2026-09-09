<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-enhancements.css') }}">
    <style>
        body.landing-page {
            display: block;
        }

        .landing-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 48px;
            border-bottom: 1px solid var(--border);
            background-color: var(--bg-secondary);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .landing-nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .landing-nav-brand img {
            width: 40px;
            height: 46px;
            object-fit: contain;
        }

        .landing-nav-brand span {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.3;
            max-width: 260px;
        }

        .landing-nav-actions {
            display: flex;
            gap: 12px;
        }

        .landing-hero {
            position: relative;
            padding: 100px 48px;
            text-align: center;
            overflow: hidden;
        }

        .landing-hero-slide {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(180deg, rgba(10, 10, 12, 0.6) 0%, rgba(10, 10, 12, 0.85) 100%), var(--slide-image);
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
        }

        .landing-hero-slide.active {
            opacity: 1;
        }

        .landing-hero-content {
            position: relative;
            z-index: 1;
        }

        .landing-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: var(--radius-full);
            background: var(--color-accent-gold-soft);
            border: 1px solid rgba(252, 209, 22, 0.4);
            color: var(--color-accent-gold);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .landing-hero h1 {
            font-size: 40px;
            font-weight: 700;
            color: #ffffff;
            max-width: 700px;
            margin: 0 auto 20px;
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.4);
            position: relative;
            padding-bottom: 20px;
        }

        .landing-hero h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 64px;
            height: 3px;
            border-radius: var(--radius-full);
            background: var(--color-accent-gold);
        }

        .landing-hero p {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            max-width: 560px;
            margin: 0 auto 36px;
        }

        .landing-hero .btn-group {
            display: flex;
            gap: 16px;
            justify-content: center;
        }

        .landing-hero .btn.secondary {
            color: #14532d;
            background: #ffffff;
            border-color: #ffffff;
        }

        .landing-hero .btn.secondary:hover {
            background: #f0fdf4;
            border-color: #f0fdf4;
        }

        .landing-section {
            padding: 80px 48px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .landing-section h2 {
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 12px;
            color: var(--text-primary);
        }

        .landing-section > p {
            text-align: center;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto 48px;
        }

        .landing-steps {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
            padding: 0;
            list-style: none;
        }

        .landing-step {
            text-align: center;
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: var(--space-7) var(--space-6);
        }

        .landing-step-number {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--accent-gradient);
            color: #1c1c1e;
            font-weight: 700;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .landing-step h3 {
            font-size: 17px;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .landing-step p {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .landing-footer {
            text-align: center;
            padding: 32px;
            border-top: 1px solid var(--border);
            color: var(--text-secondary);
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .landing-nav {
                padding: 16px 20px;
                gap: 16px;
                flex-wrap: wrap;
                position: static;
            }

            .landing-nav-actions {
                flex-wrap: wrap;
            }

            .landing-hero {
                padding: 56px 20px;
            }

            .landing-hero h1 {
                font-size: 32px;
            }

            .landing-hero p {
                font-size: 16px;
            }

            .landing-hero .btn-group {
                flex-wrap: wrap;
            }

            .landing-section {
                padding: 48px 20px;
            }

            .landing-steps {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
            }
        }

        @media (max-width: 480px) {
            .landing-steps {
                grid-template-columns: 1fr;
            }

            .landing-hero .btn-group .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .landing-hero-slide {
                transition: none;
            }
        }
    </style>
</head>
<body class="landing-page">
    <nav class="landing-nav" aria-label="Navigation principale">
        <div class="landing-nav-brand">
            <img src="{{ asset('img/armoiries.png') }}" alt="Armoiries du Burkina Faso">
            <span>Ministère des Sports, de la Jeunesse et de l'Emploi</span>
        </div>
        <div class="landing-nav-actions">
            <a href="{{ route('login') }}" class="btn">Se connecter</a>
            <a href="{{ route('login', ['tab' => 'register']) }}" class="btn primary">Créer un compte</a>
        </div>
    </nav>

    <main>
    <section class="landing-hero" aria-labelledby="landing-title">
        <div class="landing-hero-slide active" style="--slide-image: url('{{ asset('img/burkina-faso-foot.jpg') }}');"></div>
        <div class="landing-hero-slide" style="--slide-image: url('{{ asset('img/hero-2.jpg') }}');"></div>
        <div class="landing-hero-slide" style="--slide-image: url('{{ asset('img/hero-3.jpg') }}');"></div>

        <div class="landing-hero-content">
            <span class="landing-hero-badge">Portail officiel — Ministère des Sports, de la Jeunesse et de l'Emploi</span>
            <h1 id="landing-title">Quitus de subvention</h1>
            <p>Fédérations sportives et de loisirs : déclarez vos activités, préparez votre programme budgétisé et suivez votre dossier jusqu'à l'obtention du quitus de déblocage.</p>
            <div class="btn-group">
                <a href="{{ route('login', ['tab' => 'register']) }}" class="btn primary">Créer un compte fédération</a>
                <a href="{{ route('login') }}" class="btn secondary">Se connecter</a>
            </div>
        </div>
    </section>

    <section class="landing-section">
        <h2>Votre parcours jusqu'au quitus</h2>
        <p>Retrouvez chaque étape dans votre espace fédération, avec le statut de votre dossier et les actions à effectuer.</p>

        <ol class="landing-steps">
            <li class="landing-step">
                <div class="landing-step-number" aria-hidden="true">1</div>
                <h3>Créez votre compte</h3>
                <p>Renseignez les informations de votre fédération. La Direction du Sport de Haut Niveau (DSHN) vérifie votre demande avant d'activer votre accès.</p>
            </li>
            <li class="landing-step">
                <div class="landing-step-number" aria-hidden="true">2</div>
                <h3>Déclarez vos activités</h3>
                <p>Ajoutez vos activités réalisées et leurs justificatifs. Après validation par la DGF, votre rapport d'activité est constitué automatiquement.</p>
            </li>
            <li class="landing-step">
                <div class="landing-step-number" aria-hidden="true">3</div>
                <h3>Préparez votre programme</h3>
                <p>Renseignez les activités prévues et leur budget, puis soumettez votre programme budgétisé à la DSHN.</p>
            </li>
            <li class="landing-step">
                <div class="landing-step-number" aria-hidden="true">4</div>
                <h3>Suivez l'instruction et l'arbitrage</h3>
                <p>Consultez l'avancement de votre dossier et les éventuelles corrections demandées pendant son examen.</p>
            </li>
            <li class="landing-step">
                <div class="landing-step-number" aria-hidden="true">5</div>
                <h3>Réaménagez votre programme</h3>
                <p>Après l'arbitrage, adaptez votre programme au montant alloué et soumettez-le pour validation.</p>
            </li>
            <li class="landing-step">
                <div class="landing-step-number" aria-hidden="true">6</div>
                <h3>Téléchargez votre quitus</h3>
                <p>Une fois le quitus délivré, retrouvez-le dans votre espace fédération pour le télécharger.</p>
            </li>
        </ol>
    </section>
    </main>

    <footer class="landing-footer">
        &copy; {{ date('Y') }} Ministère des Sports, de la Jeunesse et de l'Emploi — Direction du Sport de Haut Niveau
    </footer>
    <script>
        (function() {
            const slides = document.querySelectorAll('.landing-hero-slide');
            let current = 0;
            if (slides.length > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                setInterval(function() {
                    slides[current].classList.remove('active');
                    current = (current + 1) % slides.length;
                    slides[current].classList.add('active');
                }, 5000);
            }
        })();
    </script>
</body>
</html>
