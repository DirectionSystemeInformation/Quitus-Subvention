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
    <link rel="stylesheet" href="{{ asset('css/app-enhancements.css') }}">
    @stack('styles')
</head>
<body>
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

    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <img src="{{ asset('img/armoiries.png') }}" alt="Armoiries du Burkina Faso" class="logo-icon logo-crest">
                <span class="logo-text">Ministère des Sports, de la Jeunesse et de l'Emploi</span>
            </div>

            @auth
                @if (auth()->user()->isFederation())
                    <nav class="nav-section">
                        <div class="nav-label">Menu</div>
                        <a href="{{ route('dashboard') }}" class="nav-item {{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7" rx="1"/>
                                <rect x="14" y="3" width="7" height="7" rx="1"/>
                                <rect x="3" y="14" width="7" height="7" rx="1"/>
                                <rect x="14" y="14" width="7" height="7" rx="1"/>
                            </svg>
                            Tableau de bord
                        </a>
                        <a href="{{ route('documents.index') }}" class="nav-item {{ ($active ?? '') === 'documents' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                            Rapport &amp; Programme
                        </a>
                        <a href="{{ route('activities.index') }}" class="nav-item {{ ($active ?? '') === 'activities' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            Gestion des activités
                        </a>
                        <a href="{{ route('profile.edit') }}" class="nav-item {{ ($active ?? '') === 'profile' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            Mon profil
                        </a>
                    </nav>
                @elseif (auth()->user()->isDshn())
                    <nav class="nav-section">
                        <div class="nav-label">Menu</div>
                        <a href="{{ role_route('dashboard') }}" class="nav-item {{ ($active ?? '') === 'dshn-dashboard' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7" rx="1"/>
                                <rect x="14" y="3" width="7" height="7" rx="1"/>
                                <rect x="3" y="14" width="7" height="7" rx="1"/>
                                <rect x="14" y="14" width="7" height="7" rx="1"/>
                            </svg>
                            Tableau de bord
                        </a>
                        <a href="{{ role_route('federations.index') }}" class="nav-item {{ ($active ?? '') === 'federations' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            Fédérations
                            @if (($sidebarPendingFederations ?? 0) > 0)
                                <span class="nav-badge">{{ $sidebarPendingFederations }}</span>
                            @endif
                        </a>
                        <a href="{{ role_route('reports.index') }}" class="nav-item {{ ($active ?? '') === 'reports' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                            Rapports reçus
                            @if (($sidebarSoumisReports ?? 0) > 0)
                                <span class="nav-badge">{{ $sidebarSoumisReports }}</span>
                            @endif
                        </a>
                        <a href="{{ role_route('canevas.index') }}" class="nav-item {{ ($active ?? '') === 'canevas' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 11l3 3L22 4"/>
                                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                            </svg>
                            Canevas
                        </a>
                        <a href="{{ route('campagnes.index') }}" class="nav-item {{ ($active ?? '') === 'campagnes' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2l3 7h7l-5.5 4.5L18.5 21 12 16.5 5.5 21l2-7.5L2 9h7z"/>
                            </svg>
                            Campagnes
                        </a>
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('dgf.activities.index') }}" class="nav-item {{ ($active ?? '') === 'dgf-activities' ? 'active' : '' }}">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                                Activités
                            </a>
                            <a href="{{ role_route('activity-log.index') }}" class="nav-item {{ ($active ?? '') === 'activity-log' ? 'active' : '' }}">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                Historique
                            </a>
                            <a href="{{ role_route('users.index') }}" class="nav-item {{ ($active ?? '') === 'users' ? 'active' : '' }}">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 010 7.75"/>
                                </svg>
                                Comptes
                            </a>
                        @endif
                        <a href="{{ route('profile.edit') }}" class="nav-item {{ ($active ?? '') === 'profile' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 004.6 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                            </svg>
                            Mon profil
                        </a>
                    </nav>
                @elseif (auth()->user()->isCampaignActor())
                    <nav class="nav-section">
                        <div class="nav-label">Menu</div>
                        <a href="{{ route('campagnes.index') }}" class="nav-item {{ ($active ?? '') === 'campagnes' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2l3 7h7l-5.5 4.5L18.5 21 12 16.5 5.5 21l2-7.5L2 9h7z"/>
                            </svg>
                            Répartition des subventions
                        </a>
                        <a href="{{ route('profile.edit') }}" class="nav-item {{ ($active ?? '') === 'profile' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 004.6 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                            </svg>
                            Mon profil
                        </a>
                    </nav>
                @elseif (auth()->user()->isDgf())
                    <nav class="nav-section">
                        <div class="nav-label">Menu</div>
                        <a href="{{ route('dgf.activities.index') }}" class="nav-item {{ ($active ?? '') === 'dgf-activities' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            Activités à valider
                        </a>
                        <a href="{{ route('profile.edit') }}" class="nav-item {{ ($active ?? '') === 'profile' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 004.6 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                            </svg>
                            Mon profil
                        </a>
                    </nav>
                @endif
            @endauth

            <div class="sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Déconnexion
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="mainContent" tabindex="-1">
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

            @auth
                @if (auth()->user()->isDshn())
                    <form method="GET" action="{{ role_route('search.index') }}" class="topbar-search">
                        <svg class="topbar-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input type="search" name="q" aria-label="Rechercher dans la plateforme" placeholder="Rechercher une fédération, un rapport, une activité..." value="{{ request('q') }}">
                    </form>
                @endif
            @endauth

            @yield('content')

            <!-- Copyright -->
            <footer class="copyright">
                &copy; {{ date('Y') }} Ministère des Sports, de la Jeunesse et de l'Emploi &mdash; Direction du Sport de Haut Niveau
            </footer>
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
</body>
</html>
