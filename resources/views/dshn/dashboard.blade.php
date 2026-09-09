@extends('layouts.dashboard', ['active' => 'dshn-dashboard'])

@section('title', 'Tableau de bord')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Bonjour, {{ auth()->user()->name }}</h1>
        <p>Vue d'ensemble de l'activité de la plateforme</p>
    </div>

    <div class="market-stats" style="margin-bottom: 24px;">
        <div class="market-stat is-gold">
            <div class="market-stat-icon is-gold">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="market-stat-label">Fédérations en attente</div>
            <div class="market-stat-value">{{ $stats['pending_federations'] }}</div>
        </div>
        <div class="market-stat">
            <div class="market-stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="market-stat-label">Fédérations actives</div>
            <div class="market-stat-value">{{ $stats['active_federations'] }}</div>
        </div>
        <div class="market-stat is-gold">
            <div class="market-stat-icon is-gold">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="market-stat-label">Rapports à traiter</div>
            <div class="market-stat-value">{{ $stats['reports_soumis'] }}</div>
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

    @if ($reportsTotal > 0)
        @php
            $pValide = round($stats['reports_valide'] / $reportsTotal * 100, 2);
            $pSoumis = round($stats['reports_soumis'] / $reportsTotal * 100, 2);
        @endphp
        <div class="card" style="margin-bottom: 24px;">
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

    <div class="content-grid">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Demandes en attente depuis le plus longtemps</h2>
                <a href="{{ role_route('federations.index') }}" class="view-all">Voir tout</a>
            </div>
            @if ($recentPending->isEmpty())
                <x-empty-state icon="users" title="Aucune demande en attente." :compact="true" />
            @else
                <div class="transaction-list">
                    @foreach ($recentPending as $federation)
                        @php $waitingDays = (int) $federation->created_at->diffInDays(now()); @endphp
                        <a href="{{ role_route('federations.show', $federation) }}" class="transaction-item" style="text-decoration:none; color:inherit;">
                            <div class="transaction-icon transfer">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                            <div class="transaction-details">
                                <span class="transaction-title">{{ $federation->federation_name }}</span>
                                <span class="transaction-date">Demandé le {{ $federation->created_at->format('d/m/Y') }}</span>
                            </div>
                            <div class="transaction-amount" style="display:flex; flex-direction:column; align-items:flex-end; gap:6px;">
                                <x-status-badge :status="$federation->status" />
                                <span class="waiting-tag">{{ $waitingDays }} j.</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Rapports en attente depuis le plus longtemps</h2>
                <a href="{{ role_route('reports.index') }}" class="view-all">Voir tout</a>
            </div>
            @if ($recentReports->isEmpty())
                <x-empty-state icon="inbox" title="Aucun rapport en attente de traitement." :compact="true" />
            @else
                <div class="transaction-list">
                    @foreach ($recentReports as $report)
                        @php $waitingDays = (int) $report->created_at->diffInDays(now()); @endphp
                        <a href="{{ route('activity-form.show', $report) }}" class="transaction-item" style="text-decoration:none; color:inherit;">
                            <div class="transaction-icon transfer">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                            </div>
                            <div class="transaction-details">
                                <span class="transaction-title">{{ $report->user->federation_name }}</span>
                                <span class="transaction-date">{{ $report->typeLabel() }} ({{ $report->year }})</span>
                            </div>
                            <div class="transaction-amount" style="display:flex; flex-direction:column; align-items:flex-end; gap:6px;">
                                <x-status-badge :status="$report->status" />
                                <span class="waiting-tag">{{ $waitingDays }} j.</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
