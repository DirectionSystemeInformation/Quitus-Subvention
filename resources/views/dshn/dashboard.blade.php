@extends('layouts.dashboard', ['active' => 'dshn-dashboard'])

@section('title', 'Tableau de bord')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Bonjour, {{ auth()->user()->name }}</h1>
        <p>Voici les éléments nécessitant votre attention.</p>
    </div>

    <div class="dashboard-section-label">À traiter</div>
    <div class="market-stats market-stats-3col" style="margin-bottom: 24px;">
        <div class="market-stat is-gold">
            <div class="market-stat-icon is-gold">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="market-stat-label">Fédérations en attente de validation</div>
            <div class="market-stat-value">{{ $stats['pending_federations'] }}</div>
        </div>
        <div class="market-stat is-gold">
            <div class="market-stat-icon is-gold">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="market-stat-label">Rapports à traiter</div>
            <div class="market-stat-value">{{ $stats['reports_soumis'] }}</div>
        </div>
        @if ($isAdmin)
            <div class="market-stat is-gold">
                <div class="market-stat-icon is-gold">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div class="market-stat-label">Activités à vérifier</div>
                <div class="market-stat-value">{{ $stats['activities_soumis'] }}</div>
            </div>
        @endif
    </div>

    <div class="dashboard-section-label">Suivi</div>
    <div class="market-stats market-stats-3col" style="margin-bottom: 24px;">
        <div class="market-stat">
            <div class="market-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="market-stat-label">Fédérations actives</div>
            <div class="market-stat-value">{{ $stats['active_federations'] }}</div>
        </div>
        <div class="market-stat">
            <div class="market-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="market-stat-label">Rapports validés</div>
            <div class="market-stat-value">{{ $stats['reports_valide'] }}</div>
        </div>
        <div class="market-stat is-danger">
            <div class="market-stat-icon is-danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>
            <div class="market-stat-label">Rapports rejetés</div>
            <div class="market-stat-value">{{ $stats['reports_rejete'] }}</div>
        </div>
    </div>

    @if ($reportsTotal > 0 || $activitiesTotal > 0)
        <div class="dashboard-donut-grid" style="margin-bottom: 24px;">
            @if ($reportsTotal > 0)
                @php
                    $pValide = round($stats['reports_valide'] / $reportsTotal * 100, 2);
                    $pSoumis = round($stats['reports_soumis'] / $reportsTotal * 100, 2);
                @endphp
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Répartition des rapports</h2>
                    </div>
                    <div class="donut-chart-wrap">
                        <div class="donut-chart" style="--donut-bg: conic-gradient(var(--color-success) 0% {{ $pValide }}%, var(--color-accent-gold, #FCD116) {{ $pValide }}% {{ $pValide + $pSoumis }}%, var(--color-danger) {{ $pValide + $pSoumis }}% 100%);">
                            <div class="donut-chart-center">
                                <span class="donut-chart-total">{{ $reportsTotal }}</span>
                                <span class="donut-chart-label">Rapports</span>
                            </div>
                        </div>
                        <ul class="donut-legend">
                            <li><span class="dot" style="background: var(--color-success);"></span> Validés — {{ $stats['reports_valide'] }}</li>
                            <li><span class="dot" style="background: var(--color-accent-gold, #FCD116);"></span> Soumis — {{ $stats['reports_soumis'] }}</li>
                            <li><span class="dot" style="background: var(--color-danger);"></span> Rejetés — {{ $stats['reports_rejete'] }}</li>
                        </ul>
                    </div>
                </div>
            @endif

            @if ($isAdmin && $activitiesTotal > 0)
                @php
                    $pAValide = round($stats['activities_valide'] / $activitiesTotal * 100, 2);
                    $pASoumis = round($stats['activities_soumis'] / $activitiesTotal * 100, 2);
                @endphp
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Répartition des activités</h2>
                    </div>
                    <div class="donut-chart-wrap">
                        <div class="donut-chart" style="--donut-bg: conic-gradient(var(--color-success) 0% {{ $pAValide }}%, var(--color-accent-gold, #FCD116) {{ $pAValide }}% {{ $pAValide + $pASoumis }}%, var(--color-danger) {{ $pAValide + $pASoumis }}% 100%);">
                            <div class="donut-chart-center">
                                <span class="donut-chart-total">{{ $activitiesTotal }}</span>
                                <span class="donut-chart-label">Activités</span>
                            </div>
                        </div>
                        <ul class="donut-legend">
                            <li><span class="dot" style="background: var(--color-success);"></span> Validées — {{ $stats['activities_valide'] }}</li>
                            <li><span class="dot" style="background: var(--color-accent-gold, #FCD116);"></span> Soumises — {{ $stats['activities_soumis'] }}</li>
                            <li><span class="dot" style="background: var(--color-danger);"></span> Rejetées — {{ $stats['activities_rejete'] }}</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Éléments nécessitant une action</h2>
        </div>
        @if ($actionItems->isEmpty())
            <x-empty-state icon="check" title="Rien n'attend votre intervention pour le moment." :compact="true" />
        @else
            <div class="table-responsive">
            <table class="market-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Fédération</th>
                        <th>Depuis</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($actionItems as $item)
                        @php $waitingDays = (int) $item['since']->diffInDays(now()); @endphp
                        <tr>
                            <td>{{ $item['type'] }}</td>
                            <td>{{ $item['federation'] }}</td>
                            <td>{{ $waitingDays }} j.</td>
                            <td><x-status-badge :status="$item['status']" :label="$item['type'] === 'Activité' ? 'À vérifier' : null" /></td>
                            <td><a href="{{ $item['url'] }}" class="security-btn primary">Voir →</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
@endsection
