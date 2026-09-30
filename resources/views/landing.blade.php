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
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-enhancements.css') }}">
    <style>
        body.landing-page { display: block; font-size: 16px; background: var(--canvas); color: var(--ink); -webkit-font-smoothing: antialiased; }
        .landing-page a { text-decoration: none; }
        .landing-page :focus-visible { outline: 2px solid var(--gold-500); outline-offset: 3px; }

        /* Boutons */
        .l-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 48px; padding: 12px 22px; border: 1px solid transparent; border-radius: 12px; font: inherit; font-size: 15px; font-weight: 650; line-height: 1.2; white-space: nowrap; transition: background .15s, border-color .15s, color .15s, transform .15s; }
        .l-btn svg { width: 18px; height: 18px; transition: transform .15s; }
        .l-btn:hover svg { transform: translateX(3px); }
        .l-btn-gold { background: var(--gold-500); color: var(--brand-950); }
        .l-btn-gold:hover { background: #ffdc3d; }
        .l-btn-ghost { border-color: rgb(255 255 255 / .35); color: #fff; }
        .l-btn-ghost:hover { background: rgb(255 255 255 / .1); border-color: #fff; }
        .l-btn-outline { border-color: var(--line); background: #fff; color: var(--ink); }
        .l-btn-outline:hover { border-color: var(--brand-200); background: var(--brand-50); }
        .l-btn-primary { background: var(--brand-600); color: #fff; }
        .l-btn-primary:hover { background: var(--brand-700); }
        .l-btn-sm { min-height: 42px; padding: 9px 18px; font-size: 14.5px; }

        /* Navigation */
        .landing-nav { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px clamp(20px, 5vw, 64px); background: rgb(255 255 255 / .92); backdrop-filter: blur(10px); border-bottom: 1px solid var(--line); }
        .landing-brand { display: flex; align-items: center; gap: 12px; color: var(--ink); }
        .landing-brand img { width: 40px; height: 48px; object-fit: contain; }
        .landing-brand strong { display: block; font-size: 21px; font-weight: 800; letter-spacing: -.04em; line-height: 1; }
        .landing-brand strong span { color: var(--gold-500); }
        .landing-brand small { display: flex; align-items: center; gap: 7px; margin-top: 5px; font-size: 12.5px; color: var(--ink-3); }
        .copyright-flag, .landing-footer .bf-flag { margin-right: 8px; transform: translateY(-1px); }
        .landing-nav-actions { display: flex; gap: 10px; }

        /* Hero */
        .landing-hero { position: relative; overflow: hidden; min-height: min(640px, 82vh); display: flex; align-items: center; padding: 80px clamp(20px, 5vw, 64px); background: var(--brand-900); }
        .landing-hero-slide { position: absolute; inset: 0; background-image: linear-gradient(95deg, rgb(0 33 15 / .96) 0%, rgb(0 48 25 / .84) 45%, rgb(0 48 25 / .3) 100%), var(--slide-image); background-size: cover; background-position: center; opacity: 0; transition: opacity 1.5s ease-in-out; }
        .landing-hero-slide.active { opacity: 1; }
        .landing-hero::before { content: ''; position: absolute; z-index: 1; right: 6%; top: 50%; width: min(34vw, 420px); aspect-ratio: 1; background: var(--flag-star) center / contain no-repeat; opacity: .12; transform: translateY(-50%) rotate(-12deg); pointer-events: none; }
        .landing-hero::after { content: ''; position: absolute; z-index: 1; inset: auto 0 0; height: 8px; background: var(--flag-band); }
        .landing-hero-content { position: relative; z-index: 1; width: 100%; max-width: 1180px; margin: 0 auto; }
        .landing-hero-content > div { max-width: 640px; }
        .landing-hero-badge { display: inline-flex; align-items: center; gap: 10px; margin-bottom: 24px; padding: 7px 14px; border: 1px solid rgb(252 209 22 / .45); border-radius: var(--radius-full); background: rgb(252 209 22 / .12); color: var(--flag-yellow); font-size: 13.5px; font-weight: 600; }
        .landing-hero h1 { margin: 0 0 20px; font-size: clamp(36px, 5vw, 56px); font-weight: 800; line-height: 1.08; letter-spacing: -.035em; color: #fff; }
        .landing-hero h1 em { font-style: normal; color: var(--gold-500); }
        .landing-hero p { margin: 0 0 34px; font-size: 18px; line-height: 1.65; color: rgb(236 244 240 / .88); }
        .landing-hero-actions { display: flex; flex-wrap: wrap; gap: 12px; }
        .landing-hero-facts { display: flex; flex-wrap: wrap; gap: 10px 28px; margin: 40px 0 0; padding: 0; list-style: none; color: rgb(236 244 240 / .8); font-size: 14.5px; }
        .landing-hero-facts li { display: flex; align-items: center; gap: 8px; }
        .landing-hero-facts svg { width: 18px; height: 18px; color: var(--gold-500); }

        /* Parcours */
        .landing-section { max-width: 1180px; margin: 0 auto; padding: 88px clamp(20px, 5vw, 64px); }
        .landing-section-head { max-width: 640px; margin-bottom: 44px; }
        .landing-section-head .landing-eyebrow { margin-bottom: 10px; font-size: 13px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--brand-600); }
        .landing-section h2 { margin: 0 0 12px; font-size: clamp(26px, 3vw, 34px); font-weight: 750; letter-spacing: -.03em; line-height: 1.2; color: var(--ink); }
        .landing-section-head p { font-size: 17px; line-height: 1.6; color: var(--ink-3); }
        .landing-steps { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin: 0; padding: 0; list-style: none; counter-reset: step; }
        .landing-step { position: relative; padding: 26px 26px 28px; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-lg); box-shadow: var(--shadow-xs); transition: border-color .15s, box-shadow .15s, transform .15s; }
        .landing-step:hover { border-color: var(--brand-200); box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .landing-step-number { display: grid; place-items: center; width: 40px; height: 40px; margin-bottom: 18px; border-radius: 12px; background: var(--brand-600); color: #fff; font-size: 16px; font-weight: 750; }
        .landing-step:last-child .landing-step-number { background: var(--gold-500); color: var(--brand-950); }
        .landing-step h3 { margin: 0 0 8px; font-size: 18px; font-weight: 700; letter-spacing: -.015em; color: var(--ink); }
        .landing-step p { margin: 0; font-size: 15px; line-height: 1.6; color: var(--ink-3); }

        /* Appel final */
        .landing-cta { position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 24px; max-width: 1180px; margin: 0 auto 88px; padding: 36px 40px; border-radius: 20px; background: linear-gradient(120deg, var(--brand-900), var(--brand-700) 70%, var(--brand-600)); color: #fff; }
        .landing-cta::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 8px; background: var(--flag-band); }
        .landing-cta::after { content: ''; position: absolute; right: 22%; top: 50%; width: 200px; height: 190px; background: var(--flag-star) center / contain no-repeat; opacity: .12; transform: translateY(-50%) rotate(-12deg); pointer-events: none; }
        .landing-cta > * { position: relative; z-index: 1; }
        .landing-cta h2 { margin: 0 0 6px; font-size: 24px; font-weight: 750; letter-spacing: -.02em; color: #fff; }
        .landing-cta p { margin: 0; color: rgb(236 244 240 / .85); }
        .landing-cta-wrap { padding: 0 clamp(20px, 5vw, 64px); }

        .landing-footer { padding: 28px clamp(20px, 5vw, 64px); border-top: 1px solid var(--line); background: var(--surface); color: var(--ink-3); font-size: 14px; text-align: center; }

        @media (max-width: 900px) {
            .landing-steps { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .landing-cta { flex-direction: column; align-items: flex-start; padding: 28px; }
        }
        @media (max-width: 600px) {
            .landing-hero::before { width: 60vw; right: -12vw; opacity: .1; }
            .landing-nav { position: static; }
            .landing-brand small { display: none; }
            .landing-nav-actions .l-btn-primary { display: none; }
            .landing-hero { min-height: 0; padding: 56px 20px 64px; }
            .landing-hero p { font-size: 16.5px; }
            .landing-hero-actions .l-btn { width: 100%; }
            .landing-steps { grid-template-columns: minmax(0, 1fr); }
            .landing-section { padding: 56px 20px; }
            .landing-cta { margin-bottom: 56px; }
            .landing-cta .l-btn { width: 100%; }
        }
        @media (prefers-reduced-motion: reduce) {
            .landing-hero-slide, .l-btn, .l-btn svg, .landing-step { transition: none; }
        }
    </style>
</head>
<body class="landing-page">
    <div class="bf-band" aria-hidden="true"></div>
    <nav class="landing-nav" aria-label="Navigation principale">
        <a class="landing-brand" href="{{ url('/') }}">
            <img src="{{ asset('img/armoiries.png') }}" alt="Armoiries du Burkina Faso">
            <span><strong>QUITUS<span>.</span></strong><small><x-flag :width="18" label="" />Ministère des Sports, de la Jeunesse et de l'Emploi</small></span>
        </a>
        <div class="landing-nav-actions">
            <a href="{{ route('login') }}" class="l-btn l-btn-outline l-btn-sm">Se connecter</a>
            <a href="{{ route('login', ['tab' => 'register']) }}" class="l-btn l-btn-primary l-btn-sm">Créer un compte</a>
        </div>
    </nav>

    <main>
        <section class="landing-hero" aria-labelledby="landing-title">
            <div class="landing-hero-slide active" style="--slide-image: url('{{ asset('img/burkina-faso-foot.jpg') }}');"></div>
            <div class="landing-hero-slide" style="--slide-image: url('{{ asset('img/hero-2.jpg') }}');"></div>
            <div class="landing-hero-slide" style="--slide-image: url('{{ asset('img/hero-3.jpg') }}');"></div>

            <div class="landing-hero-content">
                <div>
                    <span class="landing-hero-badge"><x-flag :width="24" label="" />Burkina Faso · Portail officiel de la Direction du Sport de Haut Niveau</span>
                    <h1 id="landing-title">Votre subvention, <em>du dossier au quitus.</em></h1>
                    <p>Fédérations sportives et de loisirs : déclarez vos activités, préparez votre programme budgétisé et suivez votre dossier jusqu'à l'obtention du quitus de déblocage.</p>
                    <div class="landing-hero-actions">
                        <a href="{{ route('login', ['tab' => 'register']) }}" class="l-btn l-btn-gold">Créer un compte fédération
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                        <a href="{{ route('login') }}" class="l-btn l-btn-ghost">Se connecter</a>
                    </div>
                    <ul class="landing-hero-facts">
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Compte validé par la DSHN</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>Justificatifs vérifiés par la DGF</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Suivi en temps réel</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="landing-section" aria-labelledby="parcoursTitle">
            <div class="landing-section-head">
                <p class="landing-eyebrow">Comment ça marche</p>
                <h2 id="parcoursTitle">Votre parcours jusqu'au quitus</h2>
                <p>Retrouvez chaque étape dans votre espace fédération, avec le statut de votre dossier et les actions à effectuer.</p>
            </div>

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

        <div class="landing-cta-wrap">
            <section class="landing-cta" aria-labelledby="ctaTitle">
                <div>
                    <h2 id="ctaTitle">Votre fédération n'a pas encore d'accès ?</h2>
                    <p>La création du compte prend quelques minutes. La DSHN valide ensuite votre demande.</p>
                </div>
                <a href="{{ route('login', ['tab' => 'register']) }}" class="l-btn l-btn-gold">Créer un compte fédération</a>
            </section>
        </div>
    </main>

    <footer class="landing-footer">
        <x-flag :width="21" label="" />&copy; {{ date('Y') }} Ministère des Sports, de la Jeunesse et de l'Emploi : Direction du Sport de Haut Niveau
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
