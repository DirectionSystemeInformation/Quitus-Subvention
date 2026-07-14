@extends('layouts.dashboard', ['active' => 'dashboard'])

@section('title', 'Tableau de bord')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Bonjour, {{ auth()->user()->federation_name }}</h1>
        <p>Dépôt du rapport d'activité et du projet de programme budgétisé pour l'année N+1</p>
    </div>

    @if (session('status'))
        <div class="card" style="margin-bottom: 24px; border-left: 4px solid var(--gain, #6b8e6b);">
            {{ session('status') }}
        </div>
    @endif

    @php $user = auth()->user(); @endphp

    <div class="market-stats" style="margin-bottom: 24px;">
        <div class="market-stat">
            <div class="market-stat-label">Statut du compte</div>
            <div class="market-stat-value" style="font-size: 20px;">
                @if ($user->status === 'active')
                    Actif
                @elseif ($user->status === 'pending')
                    En attente
                @else
                    Rejeté
                @endif
            </div>
        </div>
        <div class="market-stat">
            <div class="market-stat-label">Rapports d'activité déposés</div>
            <div class="market-stat-value">{{ $user->reports->where('type', 'rapport_activite')->count() }}</div>
        </div>
        <div class="market-stat">
            <div class="market-stat-label">Programmes budgétisés déposés</div>
            <div class="market-stat-value">{{ $user->reports->where('type', 'programme_budgetise')->count() }}</div>
        </div>
    </div>

    <div class="content-grid">
        <!-- Programme d'activités budgétisé -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Programme d'activités budgétisé (N+1)</h2>
            </div>
            <p class="strength-text">Formulaire structuré reproduisant le canevas officiel de la DSHN.</p>
            <div class="btn-group">
                <a href="{{ route('programme-budgetise.create') }}" class="btn primary">Remplir / modifier le programme</a>
            </div>
        </div>

        <!-- Rapport d'activité -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Rapport d'activité (année en cours)</h2>
            </div>
            <p class="strength-text">Formulaire structuré reproduisant le canevas officiel de la DSHN.</p>
            <div class="btn-group">
                <a href="{{ route('rapport-activite.create') }}" class="btn primary">Remplir / modifier le rapport</a>
            </div>
        </div>
    </div>

    <!-- Historique des dépôts -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Mes documents déposés</h2>
        </div>

        @if ($user->reports->isEmpty())
            <p class="strength-text">Aucun document déposé pour le moment.</p>
        @else
            <div class="transaction-list">
                @foreach ($user->reports->sortByDesc('created_at') as $report)
                    <div class="transaction-item">
                        <div class="transaction-icon transfer">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                        </div>
                        <div class="transaction-details">
                            <a href="{{ route('activity-form.show', $report) }}" class="transaction-title" style="color: var(--accent-copper, #b87333);">{{ $report->typeLabel() }} ({{ $report->year }})</a>
                            <span class="transaction-date">{{ $report->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="transaction-amount">
                            @if ($report->status === 'valide')
                                <span style="color: var(--gain, #6b8e6b);">Validé</span>
                            @elseif ($report->status === 'rejete')
                                <span style="color: var(--loss, #c27878);">Rejeté</span>
                            @else
                                <span>Soumis</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
