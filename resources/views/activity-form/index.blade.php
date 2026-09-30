@extends('layouts.dashboard', ['active' => 'documents'])

@section('title', 'Rapport & Programme budgétisé')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <div class="page-header-row">
            <div>
                <h1>Rapport &amp; Programme budgétisé</h1>
                <p>Retrouvez vos brouillons, vos programmes et le rapport constitué à partir de vos activités validées.</p>
            </div>
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
    </div>

    <div class="doc-tabs">
        @foreach ($shortLabels as $tabType => $tabLabel)
            <a href="{{ route('documents.index', ['type' => $tabType, 'annee' => $tabYears[$tabType]]) }}" class="doc-tab {{ $type === $tabType ? 'active' : '' }}">
                <span class="doc-tab-title">{{ $tabLabel }}</span>
                <x-status-badge :status="$tabReports->get($tabType)->status ?? 'manquant'" />
            </a>
        @endforeach
    </div>

    @if ($type === 'rapport_activite')
        <div class="workflow-steps">
            <span class="workflow-step {{ $workflowStep === 'activites' ? 'is-current' : '' }}">Activités</span>
            <span class="workflow-step-arrow">→</span>
            <span class="workflow-step">Validation DGF</span>
            <span class="workflow-step-arrow">→</span>
            <span class="workflow-step {{ $workflowStep === 'rapport' ? 'is-current' : '' }}">Rapport généré</span>
            <span class="workflow-step-arrow">→</span>
            <span class="workflow-step {{ $workflowStep === 'dshn' ? 'is-current' : '' }}">Validation DSHN</span>
        </div>
    @endif

    @php $hasOtherYears = $reports->where('year', '!=', $selectedYear)->isNotEmpty(); @endphp

    <div class="content-grid" style="margin-bottom: {{ $hasOtherYears ? '24px' : '0' }};">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">{{ $shortTitle }} : {{ $selectedYear }}</h2>
            </div>

            <div style="display:flex; align-items:center; gap:16px; flex-wrap: wrap; margin-bottom: 16px;">
                <x-status-badge :status="$selectedReport->status ?? 'manquant'" />
                @if ($selectedReport && $selectedReport->status === 'rejete' && $selectedReport->rejection_reason)
                    <span class="strength-text" style="color: var(--color-danger, #E5484D);">Motif : {{ $selectedReport->rejection_reason }}</span>
                @endif
            </div>

            @if ($selectedReport && $selectedReport->status === 'valide' && $selectedReport->validated_at)
                <div class="validation-block">
                    <span>✓ Validé par la DSHN le {{ $selectedReport->validated_at->locale('fr')->translatedFormat('d F Y') }} à {{ $selectedReport->validated_at->format('H:i') }}</span>
                </div>
            @endif

            @if ($type === 'rapport_activite')
                <p class="strength-text" style="margin-bottom: 16px;">Ce rapport est constitué automatiquement à partir de vos activités validées par la DGF.</p>
            @endif

            @if ($reportStats)
                @php
                    $isPlural = $reportStats['lines_count'] > 1;
                    $linesLabel = $type === 'rapport_activite'
                        ? ($isPlural ? 'activités intégrées' : 'activité intégrée')
                        : ($isPlural ? 'lignes budgétaires' : 'ligne budgétaire');
                @endphp
                <div class="report-summary-stats">
                    <div>
                        <div class="report-summary-stat-value">{{ $reportStats['lines_count'] }}</div>
                        <div class="report-summary-stat-label">{{ $linesLabel }}</div>
                    </div>
                    <div>
                        <div class="report-summary-stat-value">{{ number_format((float) $reportStats['total_montant'], 0, ',', ' ') }} FCFA</div>
                        <div class="report-summary-stat-label">Montant total</div>
                    </div>
                    <div>
                        <div class="report-summary-stat-value">{{ $selectedReport->updated_at->locale('fr')->translatedFormat('d F Y') }}</div>
                        <div class="report-summary-stat-label">Dernière mise à jour</div>
                    </div>
                </div>
            @endif

            <div class="btn-group">
                @if ($type === 'rapport_activite')
                    @if ($selectedReport)
                        <a href="{{ route('activity-form.show', $selectedReport) }}" class="btn primary">Consulter le rapport</a>
                        <a href="{{ route('activities.index') }}" class="btn">Gérer mes activités</a>
                    @else
                        <a href="{{ route('activities.index') }}" class="btn primary">Gérer mes activités</a>
                    @endif
                @else
                    @if ((! $selectedReport || $selectedReport->status !== 'valide') && $saisieOuverte)
                        <a href="{{ route(str_replace('_', '-', $type).'.create', ['annee' => $selectedYear]) }}" class="btn primary">
                            {{ $selectedReport?->status === 'brouillon' ? 'Reprendre le brouillon' : ($selectedReport ? 'Modifier' : 'Préparer') }}
                        </a>
                    @elseif (! $saisieOuverte && (! $selectedReport || $selectedReport->status !== 'valide'))
                        <p class="doc-closed-note">La saisie du {{ mb_strtolower($shortTitle) }} {{ $selectedYear }} est close : seule l’année {{ \App\Support\PeriodeSaisie::anneeDeDroit($type) }} est ouverte. L’administration peut rouvrir exceptionnellement cet exercice.</p>
                    @endif
                    @if ($selectedReport)
                        <a href="{{ route('activity-form.show', $selectedReport) }}" class="btn">Consulter le programme</a>
                    @endif
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Comment fonctionne {{ $type === 'rapport_activite' ? 'le rapport' : 'ce document' }} ?</h2>
            </div>
            @if ($type === 'rapport_activite')
                <p class="strength-text">Les activités validées par la DGF sont automatiquement ajoutées au rapport. Toute nouvelle activité validée entraîne une nouvelle vérification du rapport par la DSHN.</p>
                <details class="info-callout-more">
                    <summary>En savoir plus</summary>
                    <p class="strength-text">Le rapport d'activité n'est plus rempli manuellement : chaque activité que vous déclarez et faites valider par la DGF y est automatiquement ajoutée comme ligne budgétaire.</p>
                </details>
            @else
                <p class="strength-text">Le {{ mb_strtolower($shortTitle) }} décrit vos projets d'activités et le budget prévisionnel de l'année à venir.</p>
                <details class="info-callout-more">
                    <summary>En savoir plus</summary>
                    <p class="strength-text">Une fois soumis, il est examiné par la DSHN puis intègre le circuit de répartition budgétaire.</p>
                </details>
            @endif
        </div>
    </div>

    @if ($hasOtherYears)
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Historique : {{ $shortTitle }}</h2>
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
                            <td><a href="{{ route('activity-form.show', $report) }}" class="security-btn">Consulter</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    @endif
@endsection
