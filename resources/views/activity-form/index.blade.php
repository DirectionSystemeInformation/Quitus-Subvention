@extends('layouts.dashboard', ['active' => 'documents'])

@section('title', 'Rapport & Programme budgétisé')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Rapport &amp; Programme budgétisé</h1>
        <p>Suivez le statut de vos dépôts et soumettez vos documents chaque année à la DSHN.</p>
    </div>

    <div class="btn-group" style="margin-bottom: 24px;">
        <a href="{{ route('documents.index', ['type' => 'rapport_activite']) }}" class="security-btn {{ $type === 'rapport_activite' ? 'primary' : '' }}">Rapport d'activité</a>
        <a href="{{ route('documents.index', ['type' => 'programme_budgetise']) }}" class="security-btn {{ $type === 'programme_budgetise' ? 'primary' : '' }}">Programme budgétisé</a>
    </div>

    @php $hasOtherYears = $reports->where('year', '!=', $selectedYear)->isNotEmpty(); @endphp

    <div class="card" style="margin-bottom: {{ $hasOtherYears ? '24px' : '0' }};">
        <div class="card-header">
            <h2 class="card-title">{{ $title }}</h2>
            <form method="GET" action="{{ route('documents.index') }}" class="year-select-form">
                <input type="hidden" name="type" value="{{ $type }}">
                <label for="yearSelect" class="year-select-label">Année</label>
                <select name="annee" id="yearSelect" class="year-select" onchange="this.form.submit()">
                    @foreach ($availableYears as $y)
                        <option value="{{ $y }}" {{ $y === $selectedYear ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div style="display:flex; align-items:center; gap:16px; flex-wrap: wrap; margin-bottom: 16px;">
            <x-status-badge :status="$selectedReport->status ?? 'manquant'" />
            @if ($selectedReport && $selectedReport->status === 'rejete' && $selectedReport->rejection_reason)
                <span class="strength-text" style="color: var(--color-danger, #E5484D);">Motif : {{ $selectedReport->rejection_reason }}</span>
            @endif
        </div>

        <div class="btn-group">
            @if ($selectedReport)
                <a href="{{ route('activity-form.show', $selectedReport) }}" class="btn">Voir le détail</a>
            @endif
            @if (! $selectedReport || $selectedReport->status !== 'valide')
                <a href="{{ route(str_replace('_', '-', $type).'.create', ['annee' => $selectedYear]) }}" class="btn primary">
                    {{ $selectedReport ? 'Modifier' : 'Remplir' }}
                </a>
            @endif
        </div>
    </div>

    @if ($hasOtherYears)
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Historique — {{ $title }}</h2>
            </div>

            <div class="table-responsive">
            <table class="market-table">
                <thead>
                    <tr>
                        <th>Année</th>
                        <th>Statut</th>
                        <th>Déposé le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr style="{{ $report->year === $selectedYear ? 'background: var(--bg-secondary, rgba(255,255,255,0.04));' : '' }}">
                            <td>{{ $report->year }}</td>
                            <td><x-status-badge :status="$report->status" /></td>
                            <td>{{ $report->created_at->format('d/m/Y H:i') }}</td>
                            <td><a href="{{ route('activity-form.show', $report) }}" class="security-btn">Voir le détail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    @endif
@endsection
