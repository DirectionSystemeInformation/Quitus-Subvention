@extends('layouts.dashboard', ['active' => auth()->user()->isDshn() ? 'reports' : 'documents'])

@section('title', $title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <style>
        .pb-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        .pb-table th, .pb-table td { border: 1px solid var(--border, #333); padding: 8px; font-size: 13px; }
        .pb-table th { background: var(--bg-secondary, rgba(255,255,255,0.04)); text-align: left; }
        .pb-axe-row td { background: var(--color-primary, #129850); color: #1c1c1e; font-weight: 700; }
        .pb-sousaxe-row td { background: var(--bg-secondary, rgba(255,255,255,0.06)); font-weight: 600; }
        .pb-total-row td { font-weight: 700; background: var(--bg-secondary, rgba(255,255,255,0.06)); }
        .pb-col-num { width: 40px; text-align: center; }
        .pb-col-montant, .pb-col-contrib { width: 130px; }
        .pb-col-date { width: 130px; }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <h1>{{ $title }} {{ $report->year }}</h1>
        <p style="display:flex; align-items:center; gap:8px;">Fédération : {{ $report->user->federation_name }} — statut : <x-status-badge :status="$report->status" /></p>
        @if ($report->status === 'rejete' && $report->rejection_reason)
            <p style="color: var(--color-danger, #E5484D); margin-top: 8px;">Motif du rejet : {{ $report->rejection_reason }}</p>
        @endif
    </div>

    @if ($axeGroups->isEmpty())
        <div class="card">
            <x-empty-state icon="list" title="Aucune ligne renseignée." />
        </div>
    @else
        @php $total = $report->budgetLines->sum('montant'); @endphp

        @foreach ($axeGroups as $axeGroup)
            <div class="table-responsive">
            <table class="pb-table">
                <thead>
                    <tr class="pb-axe-row">
                        <td colspan="6">{{ $axeGroup['label'] }}</td>
                    </tr>
                    <tr>
                        <th class="pb-col-num">N°</th>
                        <th>Désignation de l'activité</th>
                        <th class="pb-col-montant">Montant (FCFA)</th>
                        <th class="pb-col-contrib">Contribution des partenaires</th>
                        <th class="pb-col-date">Date</th>
                        <th>Observations</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($axeGroup['sous_axes'] as $sousAxe)
                        <tr class="pb-sousaxe-row">
                            <td colspan="6">{{ $sousAxe['sous_axe_code'] }}- {{ $sousAxe['sous_axe_label'] }}</td>
                        </tr>
                        @foreach ($sousAxe['lines'] as $line)
                            <tr>
                                <td class="pb-col-num">{{ $line->numero_ligne }}</td>
                                <td>{{ $line->designation }}</td>
                                <td class="pb-col-montant">{{ $line->montant !== null ? number_format($line->montant, 0, ',', ' ') : '' }}</td>
                                <td class="pb-col-contrib">{{ $line->contribution_partenaires }}</td>
                                <td class="pb-col-date">{{ optional($line->date)->format('d/m/Y') }}</td>
                                <td>{{ $line->observations }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            </div>
        @endforeach

        <div class="table-responsive">
        <table class="pb-table">
            <tbody>
                <tr class="pb-total-row">
                    <td colspan="2">TOTAL GÉNÉRAL (FCFA)</td>
                    <td class="pb-col-montant">{{ number_format($total, 0, ',', ' ') }}</td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>
        </div>
    @endif

    @if (auth()->user()->isFederation() && $report->status !== 'valide')
        <div class="btn-group">
            @if ($type === 'rapport_activite')
                <a href="{{ route('activities.index', ['annee' => $report->year]) }}" class="btn primary">Gérer les activités du rapport</a>
            @else
                <a href="{{ route(str_replace('_', '-', $type).'.create', ['annee' => $report->year]) }}" class="btn primary">{{ $report->status === 'brouillon' ? 'Reprendre le brouillon' : 'Modifier le programme' }}</a>
            @endif
        </div>
    @endif

    @if (auth()->user()->isDshn())
        <div class="btn-group">
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
