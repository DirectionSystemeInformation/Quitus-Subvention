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
        <div class="market-stat">
            <div class="market-stat-label">Fédérations en attente</div>
            <div class="market-stat-value">{{ $stats['pending_federations'] }}</div>
        </div>
        <div class="market-stat">
            <div class="market-stat-label">Fédérations actives</div>
            <div class="market-stat-value">{{ $stats['active_federations'] }}</div>
        </div>
        <div class="market-stat">
            <div class="market-stat-label">Rapports à traiter</div>
            <div class="market-stat-value">{{ $stats['reports_soumis'] }}</div>
        </div>
        <div class="market-stat">
            <div class="market-stat-label">Rapports validés</div>
            <div class="market-stat-value">{{ $stats['reports_valide'] }}</div>
        </div>
        <div class="market-stat">
            <div class="market-stat-label">Rapports rejetés</div>
            <div class="market-stat-value">{{ $stats['reports_rejete'] }}</div>
        </div>
    </div>

    <div class="content-grid">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Demandes de compte en attente</h2>
                <a href="{{ role_route('federations.index') }}" class="view-all">Voir tout</a>
            </div>
            @if ($recentPending->isEmpty())
                <p class="strength-text">Aucune demande en attente.</p>
            @else
                <div class="transaction-list">
                    @foreach ($recentPending as $federation)
                        <div class="transaction-item">
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
                            <div class="transaction-amount">
                                <x-status-badge :status="$federation->status" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Rapports à traiter</h2>
                <a href="{{ role_route('reports.index') }}" class="view-all">Voir tout</a>
            </div>
            @if ($recentReports->isEmpty())
                <p class="strength-text">Aucun rapport en attente de traitement.</p>
            @else
                <div class="transaction-list">
                    @foreach ($recentReports as $report)
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
                            <div class="transaction-amount">
                                <x-status-badge :status="$report->status" />
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
