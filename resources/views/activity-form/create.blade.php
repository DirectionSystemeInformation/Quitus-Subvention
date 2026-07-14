@extends('layouts.dashboard', ['active' => 'dashboard'])

@section('title', $title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <style>
        .pb-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        .pb-table th, .pb-table td { border: 1px solid var(--border, #333); padding: 8px; font-size: 13px; }
        .pb-table th { background: var(--bg-secondary, rgba(255,255,255,0.04)); text-align: left; }
        .pb-axe-row td { background: var(--accent-copper, #b87333); color: #1c1c1e; font-weight: 700; }
        .pb-sousaxe-row td { background: var(--bg-secondary, rgba(255,255,255,0.06)); font-weight: 600; }
        .pb-table input { width: 100%; background: transparent; border: none; color: inherit; font-size: 13px; padding: 4px; }
        .pb-table input:focus { outline: 1px solid var(--accent-copper, #b87333); background: rgba(184,115,51,0.08); }
        .pb-num { width: 100%; }
        .pb-col-num { width: 40px; text-align: center; }
        .pb-col-montant, .pb-col-contrib { width: 130px; }
        .pb-col-date { width: 130px; }
    </style>
@endpush

@section('content')
    @php $routePrefix = str_replace('_', '-', $type); @endphp

    <div class="page-header">
        <h1>{{ $title }}</h1>
        <p>Fédération : {{ auth()->user()->federation_name }} — reproduit le canevas officiel de la DSHN</p>
    </div>

    @if (session('status'))
        <div class="card" style="margin-bottom: 24px; border-left: 4px solid var(--gain, #6b8e6b);">
            {{ session('status') }}
        </div>
    @endif

    <div class="card" style="margin-bottom: 24px;">
        <form method="GET" action="{{ route($routePrefix.'.create') }}" style="display:flex;gap:12px;align-items:end;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Année</label>
                <input type="number" name="annee" class="form-input" value="{{ $year }}" min="2000" max="2100">
            </div>
            <button type="submit" class="btn">Changer d'année</button>
        </form>
    </div>

    <form method="POST" action="{{ route($routePrefix.'.store') }}">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">

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
                                $key = $sousAxe['code'].'-'.$i;
                                $existing = $existingLines->get($sousAxe['code'].'|'.$i);
                            @endphp
                            <tr>
                                <td class="pb-col-num">{{ $i }}</td>
                                <td>
                                    <input type="text" name="lignes[{{ $key }}][designation]" value="{{ old("lignes.$key.designation", $existing->designation ?? '') }}">
                                </td>
                                <td class="pb-col-montant">
                                    <input type="number" step="0.01" min="0" class="pb-num" name="lignes[{{ $key }}][montant]" value="{{ old("lignes.$key.montant", $existing->montant ?? '') }}">
                                </td>
                                <td class="pb-col-contrib">
                                    <input type="text" name="lignes[{{ $key }}][contribution_partenaires]" value="{{ old("lignes.$key.contribution_partenaires", $existing->contribution_partenaires ?? '') }}">
                                </td>
                                <td class="pb-col-date">
                                    <input type="date" name="lignes[{{ $key }}][date]" value="{{ old("lignes.$key.date", optional($existing?->date)->format('Y-m-d')) }}">
                                </td>
                                <td>
                                    <input type="text" name="lignes[{{ $key }}][observations]" value="{{ old("lignes.$key.observations", $existing->observations ?? '') }}">
                                </td>
                            </tr>
                        @endfor
                    @endforeach
                </tbody>
            </table>
        @endforeach

        <div class="btn-group">
            <button type="submit" class="btn primary">Soumettre</button>
        </div>
    </form>
@endsection
