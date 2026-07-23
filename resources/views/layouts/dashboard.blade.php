<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ config('app.name') }}</title>
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
    <link rel="stylesheet" href="{{ asset('css/app-enhancements.css') }}">
    @stack('styles')
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
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
                        </a>
                        <a href="{{ role_route('reports.index') }}" class="nav-item {{ ($active ?? '') === 'reports' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                            Rapports reçus
                        </a>
                        <a href="{{ role_route('canevas.index') }}" class="nav-item {{ ($active ?? '') === 'canevas' ? 'active' : '' }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 11l3 3L22 4"/>
                                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                            </svg>
                            Canevas
                        </a>
                        @if (auth()->user()->isAdmin())
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
                @endif
            @endauth

            <div class="sidebar-footer">
                <div class="theme-toggle">
                    <div class="theme-toggle-label">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                        Light Mode
                    </div>
                    <div class="theme-switch" id="themeSwitch"></div>
                </div>
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
        <main class="main-content">
            @if (session('status'))
                <div id="flashStatus" data-message="{{ session('status') }}" data-type="success" style="display:none;"></div>
            @endif
            @if ($errors->any())
                <ul id="flashErrors" style="display:none;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            @yield('content')

            <!-- Copyright -->
            <footer class="copyright">
                &copy; {{ date('Y') }} Ministère des Sports, de la Jeunesse et de l'Emploi &mdash; Direction du Sport de Haut Niveau
            </footer>
        </main>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="confirmModal">
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
    <div class="modal-overlay" id="rejectReasonModal">
        <div class="modal-box">
            <h3>Rejeter</h3>
            <form method="POST" id="rejectReasonForm">
                @csrf
                <div class="form-group">
                    <label class="form-label">Motif du rejet</label>
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
