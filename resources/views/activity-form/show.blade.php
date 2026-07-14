@extends('layouts.dashboard', ['active' => auth()->user()->isDshn() ? 'reports' : 'dashboard'])

@section('title', $title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <style>
        .pb-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        .pb-table th, .pb-table td { border: 1px solid var(--border, #333); padding: 8px; font-size: 13px; }
        .pb-table th { background: var(--bg-secondary, rgba(255,255,255,0.04)); text-align: left; }
        .pb-axe-row td { background: var(--accent-copper, #b87333); color: #1c1c1e; font-weight: 700; }
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
        <p>Fédération : {{ $report->user->federation_name }} — statut :
            @if ($report->status === 'valide') <span style="color: var(--gain, #6b8e6b);">Validé</span>
            @elseif ($report->status === 'rejete') <span style="color: var(--loss, #c27878);">Rejeté</span>
            @else <span>Soumis</span>
            @endif
        </p>
    </div>

    @php $total = 0; @endphp

    @foreach ($axes as $axe)
        <table class="pb-table">
            <thead>
                <tr class="pb-axe-row">
                    <td colspan="6">{{ $axe['label'] }}</td>
                </tr>
                <tr>
                    <th class="pb-col-num">N°</th>
                    <th>Désignation de l'activité</th>
                    <th class="pb-col-montant">Montant</th>
                    <th class="pb-col-contrib">Contribution des partenaires</th>
                    <th class="pb-col-date">Date</th>
                    <th>Observations</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($axe['sous_axes'] as $sousAxe)
                    <tr class="pb-sousaxe-row">
                        <td colspan="6">{{ $sousAxe['code'] }}- {{ $sousAxe['label'] }}</td>
                    </tr>
                    @for ($i = 1; $i <= $sousAxe['lignes']; $i++)
                        @php
                            $line = $lines->get($sousAxe['code'].'|'.$i);
                            $total += $line->montant ?? 0;
                        @endphp
                        <tr>
                            <td class="pb-col-num">{{ $i }}</td>
                            <td>{{ $line->designation ?? '' }}</td>
                            <td class="pb-col-montant">{{ $line && $line->montant !== null ? number_format($line->montant, 0, ',', ' ') : '' }}</td>
                            <td class="pb-col-contrib">{{ $line->contribution_partenaires ?? '' }}</td>
                            <td class="pb-col-date">{{ optional($line?->date)->format('d/m/Y') }}</td>
                            <td>{{ $line->observations ?? '' }}</td>
                        </tr>
                    @endfor
                @endforeach
            </tbody>
        </table>
    @endforeach

    <table class="pb-table">
        <tbody>
            <tr class="pb-total-row">
                <td colspan="2">TOTAL GENERAL</td>
                <td class="pb-col-montant">{{ number_format($total, 0, ',', ' ') }}</td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>

    @if (auth()->user()->isFederation())
        <div class="btn-group">
            <a href="{{ route(str_replace('_', '-', $type).'.create', ['annee' => $report->year]) }}" class="btn primary">Modifier</a>
        </div>
    @endif

    @if (auth()->user()->isDshn())
        <div class="btn-group">
            @if ($report->status !== 'valide')
                <form method="POST" action="{{ route('dshn.reports.validate', $report) }}">
                    @csrf
                    <button type="submit" class="btn primary">Valider</button>
                </form>
            @endif
            @if ($report->status !== 'rejete')
                <form method="POST" action="{{ route('dshn.reports.reject', $report) }}">
                    @csrf
                    <button type="submit" class="btn danger">Rejeter</button>
                </form>
            @endif
        </div>
    @endif
@endsection
