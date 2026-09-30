@php
    $navigationUser = auth()->user();
    $navigationBadges = ['reports' => $sidebarSoumisReports ?? 0, 'federations' => $sidebarPendingFederations ?? 0];
@endphp
<aside class="platform-sidebar" id="sidebar" aria-label="Navigation principale">
    <a class="platform-brand" href="{{ route(home_route_name($navigationUser)) }}" aria-label="Quitus — Accueil">
        <img src="{{ asset('img/armoiries.png') }}" alt="" width="38" height="46">
        <span><strong>QUITUS<span class="brand-point">.</span></strong><small>Subventions sportives</small></span>
    </a>
    <div class="platform-workspace"><span class="workspace-indicator" aria-hidden="true"></span>{{ \App\Support\PlatformNavigation::roleLabel($navigationUser) }}</div>
    <nav class="platform-navigation">
        @foreach (\App\Support\PlatformNavigation::groups($navigationUser) as $groupLabel => $items)
            <div class="platform-nav-group">
                <p class="platform-nav-label">{{ $groupLabel }}</p>
                @foreach ($items as $item)
                    <a class="platform-nav-link {{ ($active ?? '') === $item[0] ? 'active' : '' }}" href="{{ route($item[1]) }}" @if (($active ?? '') === $item[0]) aria-current="page" @endif>
                        <x-ui-icon :name="$item[3]" /><span>{{ $item[2] }}</span>
                        @if (($navigationBadges[$item[4] ?? ''] ?? 0) > 0)<span class="platform-nav-count">{{ $navigationBadges[$item[4]] }}<span class="sr-only"> en attente</span></span>@endif
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>
    <div class="platform-sidebar-footer">
        <a class="platform-account platform-nav-link {{ ($active ?? '') === 'profile' ? 'active' : '' }}" href="{{ route('profile.edit') }}" @if (($active ?? '') === 'profile') aria-current="page" @endif>
            <span class="platform-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($navigationUser->name, 0, 1)) }}</span>
            <span><strong>{{ $navigationUser->name }}</strong><small>Mon profil et mes accès</small></span>
        </a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="platform-logout"><x-ui-icon name="logout" />Déconnexion</button></form>
    </div>
</aside>
