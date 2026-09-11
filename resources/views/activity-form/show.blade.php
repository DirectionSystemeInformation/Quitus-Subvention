@extends('layouts.dashboard', ['active' => auth()->user()->isDshn() ? 'reports' : 'documents'])

@section('title', $title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <style>
        .pb-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .pb-table th, .pb-table td { border: 1px solid var(--border, #333); padding: 8px; font-size: 13px; }
        .pb-table th { background: var(--bg-secondary, rgba(255,255,255,0.04)); text-align: left; }
        .pb-col-num { width: 4%; text-align: center; }
        .pb-col-designation { width: 35%; }
        .pb-col-montant, .pb-col-contrib { width: 12%; }
        .pb-col-contrib { width: 15%; }
        .pb-col-date { width: 12%; }
        .pb-col-obs { width: 22%; }
        .axe-heading { margin: 28px 0 4px; font-size: 16px; }
        .axe-heading:first-of-type { margin-top: 0; }
        .sousaxe-heading { margin: 16px 0 8px; font-size: 14px; color: var(--text-secondary); }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            @if (auth()->user()->isDshn())
                <a href="{{ role_route('reports.index') }}">&larr; Retour aux rapports</a>
            @else
                <a href="{{ route('documents.index', ['type' => $type, 'annee' => $report->year]) }}">&larr; Retour aux rapports &amp; programmes</a>
            @endif
        </nav>
        <div class="page-header-row">
            <div>
                <h1 style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                    {{ $title }} {{ $report->year }}
                    <x-status-badge :status="$report->status" />
                </h1>
                @if ($programmeLabel)
                    <p style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">{{ $programmeLabel }}</p>
                @endif
                <p style="color: var(--text-secondary); margin-top: 4px;">{{ $report->user->federation_name }}</p>
            </div>
            @if ($report->status === 'valide')
                <a href="{{ route('activity-form.pdf', $report) }}" class="btn">Télécharger le PDF</a>
            @endif
        </div>

        @if ($report->status === 'valide' && $report->validated_at)
            <div class="validation-block" style="margin-top: 12px;">
                <span>✓ Validé par la DSHN le {{ $report->validated_at->locale('fr')->translatedFormat('d F Y') }} à {{ $report->validated_at->format('H:i') }}</span>
            </div>
        @elseif ($report->status !== 'brouillon')
            <p class="strength-text" style="margin-top: 8px;">Dernière mise à jour : {{ $report->updated_at->locale('fr')->translatedFormat('d F Y') }} à {{ $report->updated_at->format('H:i') }}</p>
        @endif

        @if ($report->status === 'rejete' && $report->rejection_reason)
            <p style="color: var(--color-danger, #E5484D); margin-top: 8px;">Motif du rejet : {{ $report->rejection_reason }}</p>
        @endif
    </div>

    @if ($axeGroups->isEmpty())
        <div class="card">
            <x-empty-state icon="list" title="Aucune ligne renseignée." />
        </div>
    @else
        @php $isPlural = $linesCount > 1; @endphp
        <div class="report-summary-stats" style="margin-bottom: 28px;">
            <div>
                <div class="report-summary-stat-value">{{ $linesCount }}</div>
                <div class="report-summary-stat-label">{{ $type === 'rapport_activite' ? ($isPlural ? 'activités validées' : 'activité validée') : ($isPlural ? 'lignes budgétaires' : 'ligne budgétaire') }}</div>
            </div>
            <div>
                <div class="report-summary-stat-value">{{ $axesCount }}</div>
                <div class="report-summary-stat-label">{{ $axesCount > 1 ? 'axes' : 'axe' }}</div>
            </div>
            <div>
                <div class="report-summary-stat-value">{{ number_format((float) $totalMontant, 0, ',', ' ') }} FCFA</div>
                <div class="report-summary-stat-label">Montant total</div>
            </div>
        </div>

        @foreach ($axeGroups as $axeGroup)
            <h2 class="axe-heading">
                Axe {{ $axeGroup['number'] ?? $axeGroup['axe'] }}@if ($axeGroup['label_parts']['description']) — {{ $axeGroup['label_parts']['description'] }}@endif
            </h2>

            @foreach ($axeGroup['sous_axes'] as $sousAxe)
                <h3 class="sousaxe-heading">{{ $sousAxe['sous_axe_code'] }} — {{ $sousAxe['sous_axe_label'] }}</h3>
                <div class="table-responsive">
                <table class="pb-table">
                    <thead>
                        <tr>
                            <th class="pb-col-num">N°</th>
                            <th class="pb-col-designation">Désignation de l'activité</th>
                            <th class="pb-col-montant">Montant (FCFA)</th>
                            <th class="pb-col-contrib">Contribution (FCFA)</th>
                            <th class="pb-col-date">Date</th>
                            <th class="pb-col-obs">Observations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sousAxe['lines'] as $line)
                            <tr>
                                <td class="pb-col-num">{{ $line->numero_ligne }}</td>
                                <td class="pb-col-designation">{{ $line->designation }}</td>
                                <td class="pb-col-montant">{{ $line->montant !== null ? number_format((float) $line->montant, 0, ',', ' ') : '—' }}</td>
                                <td class="pb-col-contrib">{{ is_numeric($line->contribution_partenaires) ? number_format((float) $line->contribution_partenaires, 0, ',', ' ') : ($line->contribution_partenaires ?: '—') }}</td>
                                <td class="pb-col-date">{{ optional($line->date)->format('d/m/Y') ?? '—' }}</td>
                                <td class="pb-col-obs">{{ $line->observations ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @endforeach
        @endforeach

        <div class="report-totals">
            <div class="report-totals-row">
                <span>Montant total</span>
                <strong>{{ number_format((float) $totalMontant, 0, ',', ' ') }} FCFA</strong>
            </div>
            <div class="report-totals-row">
                <span>Contribution totale des partenaires</span>
                <strong>{{ number_format((float) $totalContribution, 0, ',', ' ') }} FCFA</strong>
            </div>
        </div>
    @endif

    @if (auth()->user()->isFederation() && $report->status !== 'valide')
        <div class="btn-group" style="margin-top: 24px;">
            @if ($type === 'rapport_activite')
                <a href="{{ route('activities.index', ['annee' => $report->year]) }}" class="btn primary">Gérer les activités du rapport</a>
            @else
                <a href="{{ route(str_replace('_', '-', $type).'.create', ['annee' => $report->year]) }}" class="btn primary">{{ $report->status === 'brouillon' ? 'Reprendre le brouillon' : 'Modifier le programme' }}</a>
            @endif
        </div>
    @endif

    @if (auth()->user()->isDshn())
        <div class="btn-group" style="margin-top: 24px;">
            @if ($report->status !== 'valide')
                <form method="POST" action="{{ role_route('reports.validate', $report) }}">
                    @csrf
                    <button type="submit" class="btn primary">Valider</button>
                </form>
            @endif
            @if ($report->status !== 'rejete')
                <button type="button" class="btn danger js-reject-reason" data-action="{{ role_route('reports.reject', $report) }}">Rejeter</button>
            @endif
        </div>
    @endif
@endsection
