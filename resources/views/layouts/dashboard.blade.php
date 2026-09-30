<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-enhancements.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/platform.css') }}">
    <link rel="stylesheet" href="{{ asset('css/creation-forms.css') }}">
    <link rel="stylesheet" href="{{ asset('css/interactions.css') }}">
</head>
<body class="platform-app">
    <a class="skip-link" href="#mainContent">Aller au contenu</a>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Ouvrir le menu" aria-controls="sidebar" aria-expanded="false">
        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </button>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="platform-layout">
        @include('layouts.partials.platform-navigation')

        <!-- Main Content -->
        <main class="platform-main" id="mainContent" tabindex="-1">
            <header class="platform-topbar">
                <div class="platform-institution-wrap"><x-flag :width="33" /><div class="platform-institution">Ministère des Sports, de la Jeunesse et de l’Emploi<span>Direction du Sport de Haut Niveau</span></div></div>
                <div class="platform-topbar-tools">
                    @if (auth()->user()->isDshn())
                        <form method="GET" action="{{ role_route('search.index') }}" class="platform-search" role="search">
                            <x-ui-icon name="search" />
                            <input type="search" name="q" aria-label="Rechercher dans la plateforme" placeholder="Rechercher une fédération, un document…" value="{{ request()->routeIs('*.search.index') ? request('q') : '' }}">
                            <button type="submit" aria-label="Lancer la recherche"><x-ui-icon name="arrow" /></button>
                        </form>
                    @else
                        <span class="platform-topbar-label"><span class="workspace-indicator" aria-hidden="true"></span>{{ \App\Support\PlatformNavigation::roleLabel(auth()->user()) }}</span>
                    @endif
                </div>
            </header>

            <div class="platform-content">
                @if (session('status'))
                    <div id="flashStatus" class="notice notice-success" role="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <section id="flashErrors" class="notice notice-error" role="alert" tabindex="-1" aria-labelledby="errorSummaryTitle">
                        <h2 id="errorSummaryTitle">Vérifiez les informations saisies</h2>
                        <ul>
                            @foreach ($errors->getMessages() as $field => $messages)
                                @foreach ($messages as $error)
                                    <li data-error-field="{{ $field }}">{{ $error }}</li>
                                @endforeach
                            @endforeach
                        </ul>
                    </section>
                @endif

                @yield('content')

                <footer class="copyright">
                    <x-flag :width="21" label="" /> &copy; {{ date('Y') }} Ministère des Sports, de la Jeunesse et de l'Emploi : Direction du Sport de Haut Niveau
                </footer>
            </div>
        </main>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle" aria-describedby="confirmModalMessage">
        <div class="modal-box">
            <h3 id="confirmModalTitle">Confirmer l'action</h3>
            <p id="confirmModalMessage"></p>
            <div class="modal-actions">
                <button type="button" class="btn" id="confirmModalCancel">Annuler</button>
                <button type="button" class="btn danger" id="confirmModalConfirm">Confirmer</button>
            </div>
        </div>
    </div>

    <!-- Reject Reason Modal -->
    <div class="modal-overlay" id="rejectReasonModal" role="dialog" aria-modal="true" aria-labelledby="rejectReasonTitle">
        <div class="modal-box">
            <h3 id="rejectReasonTitle">Motiver le rejet</h3>
            <form method="POST" id="rejectReasonForm">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="rejectReasonInput">Motif du rejet</label>
                    <textarea name="rejection_reason" id="rejectReasonInput" class="form-input" rows="3" required placeholder="Expliquez pourquoi ce document est rejeté..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" id="rejectReasonCancel">Annuler</button>
                    <button type="submit" class="btn danger">Rejeter</button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('js/templatemo-crypto-script.js') }}"></script>
    @stack('scripts')
    <script src="{{ asset('js/interactions.js') }}"></script>
</body>
</html>
