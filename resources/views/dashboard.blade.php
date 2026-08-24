@extends('layouts.dashboard', ['active' => 'dashboard'])

@section('title', 'Tableau de bord')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    @php
        $user = auth()->user();
        $programmeYear = now()->year + 1;
        $rapportYear = now()->year;
        $programmeReport = $user->reports->first(fn ($r) => $r->type === 'programme_budgetise' && $r->year === $programmeYear);
        $rapportReport = $user->reports->first(fn ($r) => $r->type === 'rapport_activite' && $r->year === $rapportYear);

        $rapportStatus = $rapportReport->status ?? 'manquant';
        $programmeStatus = $programmeReport->status ?? 'manquant';

        $stepClass = fn ($status) => match ($status) {
            'valide' => 'is-valide',
            'rejete' => 'is-rejete',
            'soumis' => 'is-soumis',
            default => 'is-manquant',
        };

        $stepIcon = fn ($status) => match ($status) {
            'valide' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>',
            'rejete' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
            'soumis' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            default => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9" stroke-dasharray="3 3"/></svg>',
        };

        $campaign = \App\Models\Campaign::where('annee_n1', $programmeYear)->first();
        $allocation = $campaign?->allocations()->where('user_id', $user->id)->first();
    @endphp

    <div class="page-header">
        <h1>Bonjour, {{ $user->federation_name }}</h1>
        <p>Rapport d'activité {{ $rapportYear }} et programme budgétisé {{ $programmeYear }} à soumettre à la DSHN</p>
    </div>

    <div class="dossier-stepper">
        <div class="dossier-step {{ $stepClass($rapportStatus) }}">
            <div class="dossier-step-icon">{!! $stepIcon($rapportStatus) !!}</div>
            <div class="dossier-step-body">
                <span class="dossier-step-label">Rapport d'activité ({{ $rapportYear }})</span>
                <x-status-badge :status="$rapportStatus" />
            </div>
        </div>
        <div class="dossier-step-connector {{ $rapportStatus === 'valide' ? 'is-filled' : '' }}"></div>
        <div class="dossier-step {{ $stepClass($programmeStatus) }}">
            <div class="dossier-step-icon">{!! $stepIcon($programmeStatus) !!}</div>
            <div class="dossier-step-body">
                <span class="dossier-step-label">Programme budgétisé ({{ $programmeYear }})</span>
                <x-status-badge :status="$programmeStatus" />
            </div>
        </div>
    </div>
    @if ($rapportStatus === 'valide' && $programmeStatus === 'valide')
        <p class="strength-text" style="margin: -16px 0 24px; color: var(--gain);">Les deux documents de l'année sont validés — votre dossier est complet pour la suite de la procédure.</p>
    @endif

    @if ($allocation && $allocation->quitus_delivered_at)
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Quitus de déblocage de subvention {{ $programmeYear }}</h2>
            </div>
            <p class="strength-text">Votre quitus a été délivré le {{ $allocation->quitus_delivered_at->format('d/m/Y') }} pour un montant de {{ number_format((float) $allocation->montant_final, 0, ',', ' ') }} FCFA.</p>
            <div class="btn-group">
                <a href="{{ route('campagnes.quitus.download', [$campaign, $user]) }}" class="btn primary">Télécharger le quitus</a>
            </div>
        </div>
    @elseif ($campaign && $campaign->etape >= 11 && $allocation)
        @php
            $reamenageReport = $user->reports->first(fn ($r) => $r->type === 'programme_reamenage' && $r->year === $programmeYear);
        @endphp
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Programme d'activités budgétisé réaménagé {{ $programmeYear }}</h2>
            </div>
            @if ($reamenageReport)
                <p class="strength-text">Statut : <x-status-badge :status="$reamenageReport->status" /></p>
                @if ($reamenageReport->status === 'rejete' && $reamenageReport->rejection_reason)
                    <p class="strength-text" style="color: var(--color-danger, #E5484D);">Motif : {{ $reamenageReport->rejection_reason }}</p>
                @endif
            @else
                <p class="strength-text">Suite à l'arbitrage budgétaire, veuillez soumettre votre programme d'activités réaménagé selon le montant qui vous a été alloué.</p>
            @endif
            <div class="btn-group">
                @if ($reamenageReport && $reamenageReport->status === 'valide')
                    <a href="{{ route('activity-form.show', $reamenageReport) }}" class="btn primary">Voir le programme réaménagé validé</a>
                @else
                    <a href="{{ route('programme-reamenage.create') }}" class="btn primary">Remplir / modifier le programme réaménagé</a>
                @endif
            </div>
        </div>
    @endif

    <div class="market-stats" style="margin-bottom: 24px;">
        <div class="market-stat">
            <div class="market-stat-label">Statut du compte</div>
            <div class="market-stat-value" style="font-size: 20px;">
                <x-status-badge :status="$user->status" />
            </div>
            @if ($user->status === 'rejected' && $user->rejection_reason)
                <div class="market-stat-change" style="color: var(--color-danger, #E5484D); margin-top: 8px;">Motif : {{ $user->rejection_reason }}</div>
            @endif
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
                <h2 class="card-title">Programme d'activités budgétisé ({{ $programmeYear }})</h2>
            </div>
            <p class="strength-text">Projet d'activités et de budget pour l'année {{ $programmeYear }}, à soumettre pour validation.</p>
            <div class="btn-group">
                @if ($programmeReport && $programmeReport->status === 'valide')
                    <a href="{{ route('activity-form.show', $programmeReport) }}" class="btn primary">Voir le programme validé</a>
                @else
                    <a href="{{ route('programme-budgetise.create') }}" class="btn primary">Remplir / modifier le programme</a>
                @endif
            </div>
        </div>

        <!-- Rapport d'activité -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Rapport d'activité ({{ $rapportYear }})</h2>
            </div>
            <p class="strength-text">Bilan des activités menées durant l'année {{ $rapportYear }}, à soumettre pour validation.</p>
            <div class="btn-group">
                @if ($rapportReport && $rapportReport->status === 'valide')
                    <a href="{{ route('activity-form.show', $rapportReport) }}" class="btn primary">Voir le rapport validé</a>
                @else
                    <a href="{{ route('rapport-activite.create') }}" class="btn primary">Remplir / modifier le rapport</a>
                @endif
            </div>
        </div>
    </div>

    <!-- Historique par année -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Historique par année</h2>
        </div>

        @php $reportsByYear = $user->reports->groupBy('year')->sortKeysDesc(); @endphp

        @if ($reportsByYear->isEmpty())
            <x-empty-state icon="inbox" title="Aucun document déposé pour le moment." />
        @else
            <div class="table-responsive">
            <table class="market-table">
                <thead>
                    <tr>
                        <th>Année</th>
                        <th>Rapport d'activité</th>
                        <th>Programme budgétisé</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportsByYear as $year => $reportsForYear)
                        @php
                            $rapportForYear = $reportsForYear->firstWhere('type', 'rapport_activite');
                            $programmeForYear = $reportsForYear->firstWhere('type', 'programme_budgetise');
                        @endphp
                        <tr>
                            <td>{{ $year }}</td>
                            <td>
                                @if ($rapportForYear)
                                    <a href="{{ route('activity-form.show', $rapportForYear) }}" style="text-decoration:none;">
                                        <x-status-badge :status="$rapportForYear->status" />
                                    </a>
                                @else
                                    <x-status-badge status="manquant" />
                                @endif
                            </td>
                            <td>
                                @if ($programmeForYear)
                                    <a href="{{ route('activity-form.show', $programmeForYear) }}" style="text-decoration:none;">
                                        <x-status-badge :status="$programmeForYear->status" />
                                    </a>
                                @else
                                    <x-status-badge status="manquant" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
@endsection
