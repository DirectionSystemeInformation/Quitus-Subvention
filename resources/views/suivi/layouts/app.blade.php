<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titre', 'Tableau de bord') · {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root { --bf-rouge: #c8102e; --bf-vert: #00843d; --bf-jaune: #fcd116; --sidebar: 256px; }
        body { background: #f3f5f7; font-size: .925rem; }
        .sidebar { width: var(--sidebar); position: fixed; inset: 0 auto 0 0; background: #0f2a1d; color: #cfe3d6; overflow-y: auto; z-index: 1030; transition: transform .2s; }
        .sidebar .marque { padding: 1rem 1.25rem; border-bottom: 3px solid var(--bf-rouge); background: #0b2016; }
        .sidebar .marque .etoile { color: var(--bf-jaune); }
        .sidebar .section { font-size: .7rem; text-transform: uppercase; letter-spacing: .08em; color: #7fa28e; padding: 1rem 1.25rem .35rem; }
        .sidebar a { color: #cfe3d6; text-decoration: none; display: flex; align-items: center; gap: .65rem; padding: .45rem 1.25rem; border-left: 3px solid transparent; }
        .sidebar a:hover { background: rgba(255,255,255,.06); color: #fff; }
        .sidebar a.active { background: rgba(0,132,61,.35); color: #fff; border-left-color: var(--bf-jaune); }
        .contenu { margin-left: var(--sidebar); min-height: 100vh; }
        .topbar { background: #fff; border-bottom: 1px solid #e3e7eb; }
        .card { border: 0; box-shadow: 0 1px 2px rgba(16,24,40,.06), 0 1px 3px rgba(16,24,40,.08); }
        .card-header { background: #fff; font-weight: 600; }
        .kpi .valeur { font-size: 1.75rem; font-weight: 700; line-height: 1; }
        .kpi .icone { width: 44px; height: 44px; border-radius: 10px; display: grid; place-items: center; font-size: 1.25rem; }
        .avatar { width: 38px; height: 38px; border-radius: 50%; display: inline-grid; place-items: center; background: #e6f2eb; color: var(--bf-vert); font-weight: 700; object-fit: cover; }
        .avatar-lg { width: 96px; height: 96px; font-size: 2rem; }
        .btn-bf { background: var(--bf-vert); color: #fff; } .btn-bf:hover { background: #006b31; color: #fff; }
        .table > :not(caption) > * > * { vertical-align: middle; }
        .filtres .form-select, .filtres .form-control { font-size: .85rem; }
        @media (max-width: 991px) { .sidebar { transform: translateX(-100%); } .sidebar.ouverte { transform: none; } .contenu { margin-left: 0; } }
        @media print { .sidebar, .topbar, .no-print { display: none !important; } .contenu { margin: 0; } }
    </style>
    @stack('styles')
</head>
<body>
<nav class="sidebar" id="sidebar">
    <div class="marque">
        <div class="fw-bold text-white"><i class="bi bi-star-fill etoile"></i> Talents Sportifs BF</div>
        <small class="text-white-50">Plateforme nationale de suivi</small>
    </div>
    @foreach (config('talents.menu') as $groupe)
        @php($liens = collect($groupe['liens'])->filter(fn ($l) => ! $l[3] || auth()->user()->can($l[3])))
        @if ($liens->isNotEmpty())
            @if ($groupe['titre'])<div class="section">{{ $groupe['titre'] }}</div>@endif
            @foreach ($liens as [$libelle, $route, $icone])
                @php($prefixe = \Illuminate\Support\Str::beforeLast($route, '.'))
                <a href="{{ route($route) }}" class="{{ request()->routeIs($route) || ($route !== 'suivi.talents.validations' && str_ends_with($route, '.index') && request()->routeIs($prefixe.'.*') && ! request()->routeIs('suivi.talents.validations')) ? 'active' : '' }}">
                    <i class="bi {{ $icone }}"></i> {{ $libelle }}
                </a>
            @endforeach
        @endif
    @endforeach
    @if (auth()->user()->aAccesQuitus())
        <div class="section">Autre portail</div>
        <a href="{{ route(home_route_name(auth()->user())) }}"><i class="bi bi-file-earmark-text"></i> Quitus de subvention</a>
    @endif
    <div class="p-3"></div>
</nav>

<div class="contenu">
    <header class="topbar d-flex align-items-center px-3 py-2 gap-3 sticky-top">
        <button class="btn btn-light d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('ouverte')"><i class="bi bi-list"></i></button>
        <form action="{{ route('suivi.talents.index') }}" class="d-none d-md-block flex-grow-1" style="max-width:420px">
            @can('talents.voir')
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input name="q" class="form-control" placeholder="Rechercher un talent (nom, ID TLT-FBF-...)">
                </div>
            @endcan
        </form>
        <div class="ms-auto dropdown">
            <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-dark" data-bs-toggle="dropdown">
                <span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                <span class="d-none d-sm-block lh-sm">
                    <strong>{{ auth()->user()->name }}</strong><br>
                    <small class="text-muted">{{ auth()->user()->getRoleNames()->implode(', ') }}{{ auth()->user()->federationTalents() ? ' · '.auth()->user()->federationTalents()->sigle : '' }}</small>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                @if (auth()->user()->aAccesQuitus())
                    <li><a class="dropdown-item" href="{{ route('portails') }}"><i class="bi bi-arrow-left-right"></i> Changer de portail</a></li>
                @endif
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person"></i> Mon profil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item"><i class="bi bi-box-arrow-right"></i> Déconnexion</button></form></li>
            </ul>
        </div>
    </header>

    <main class="p-3 p-lg-4">
        @foreach (['succes' => 'success', 'erreur' => 'danger'] as $cle => $type)
            @if (session($cle))
                <div class="alert alert-{{ $type }} alert-dismissible fade show">{{ session($cle) }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
        @endforeach
        @if ($errors->any())
            <div class="alert alert-danger"><strong>Veuillez corriger :</strong>
                <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('contenu')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.querySelectorAll('select.recherche').forEach(el => new TomSelect(el, {allowEmptyOption: true, maxOptions: 500}));
    document.querySelectorAll('form[data-confirmer]').forEach(f => f.addEventListener('submit', e => {
        if (!confirm(f.dataset.confirmer)) e.preventDefault();
    }));
    window.PALETTE = ['#00843d', '#c8102e', '#e0a800', '#1f6feb', '#6f42c1', '#20c997', '#fd7e14', '#6c757d', '#0dcaf0', '#d63384', '#198754', '#adb5bd'];
</script>
@stack('scripts')
</body>
</html>
