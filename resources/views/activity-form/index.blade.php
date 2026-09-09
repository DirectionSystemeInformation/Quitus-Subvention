@extends('layouts.dashboard', ['active' => 'documents'])

@section('title', 'Rapport & Programme budgétisé')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Rapport &amp; Programme budgétisé</h1>
        <p>Retrouvez vos brouillons, vos programmes et le rapport constitué à partir de vos activités validées.</p>
    </div>

    <div class="btn-group" style="margin-bottom: 24px;">
        <a href="{{ route('documents.index', ['type' => 'rapport_activite', 'annee' => $type === 'rapport_activite' ? $selectedYear : $selectedYear - 1]) }}" class="security-btn {{ $type === 'rapport_activite' ? 'primary' : '' }}">Rapport d'activité</a>
        <a href="{{ route('documents.index', ['type' => 'programme_budgetise', 'annee' => $type === 'rapport_activite' ? $selectedYear + 1 : $selectedYear]) }}" class="security-btn {{ $type === 'programme_budgetise' ? 'primary' : '' }}">Programme budgétisé</a>
        <a href="{{ route('documents.index', ['type' => 'programme_reamenage', 'annee' => $type === 'rapport_activite' ? $selectedYear + 1 : $selectedYear]) }}" class="security-btn {{ $type === 'programme_reamenage' ? 'primary' : '' }}">Programme réaménagé</a>
    </div>

    @php $hasOtherYears = $reports->where('year', '!=', $selectedYear)->isNotEmpty(); @endphp

    <div class="content-grid" style="margin-bottom: {{ $hasOtherYears ? '24px' : '0' }};">
        <div class="card">
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

            @if ($type === 'rapport_activite')
                <p class="strength-text" style="margin-bottom: 16px;">Ce rapport est constitué automatiquement à partir de vos activités validées par la DGF.</p>
            @endif

            <div class="btn-group">
                @if ($selectedReport)
                    <a href="{{ route('activity-form.show', $selectedReport) }}" class="btn">Voir le détail</a>
                @endif
                @if ($type === 'rapport_activite')
                    <a href="{{ route('activities.index') }}" class="btn primary">Gérer mes activités</a>
                @elseif (! $selectedReport || $selectedReport->status !== 'valide')
                    <a href="{{ route(str_replace('_', '-', $type).'.create', ['annee' => $selectedYear]) }}" class="btn primary">
                        {{ $selectedReport?->status === 'brouillon' ? 'Reprendre le brouillon' : ($selectedReport ? 'Modifier' : 'Préparer') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">À savoir</h2>
            </div>
            @if ($type === 'rapport_activite')
                <p class="strength-text">Le rapport d'activité n'est plus rempli manuellement : chaque activité que vous déclarez et faites valider par la DGF y est automatiquement ajoutée comme ligne budgétaire.</p>
                <p class="strength-text" style="margin-top: 12px;">Le statut de ce rapport passe à « Soumis » dès qu'une nouvelle activité est validée, pour repasser en revue par la DSHN.</p>
            @else
                <p class="strength-text">Le programme budgétisé décrit vos projets d'activités et le budget prévisionnel de l'année à venir.</p>
                <p class="strength-text" style="margin-top: 12px;">Une fois soumis, il est examiné par la DSHN puis intègre le circuit de répartition budgétaire.</p>
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
                        <th>Mis à jour le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr style="{{ $report->year === $selectedYear ? 'background: var(--bg-secondary, rgba(255,255,255,0.04));' : '' }}">
                            <td>{{ $report->year }}</td>
                            <td><x-status-badge :status="$report->status" /></td>
                            <td>{{ $report->updated_at->format('d/m/Y H:i') }}</td>
                            <td><a href="{{ route('activity-form.show', $report) }}" class="security-btn">Voir le détail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    @endif
@endsection
