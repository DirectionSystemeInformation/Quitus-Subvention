@extends('layouts.dashboard', ['active' => 'activities'])

@section('title', 'Gestion des activités')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Gestion des activités</h1>
        <p>Déclarez chaque activité réalisée avec ses pièces justificatives. Une fois validée par la DGF, elle est automatiquement versée à votre rapport d'activité.</p>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Vos activités</h2>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                @if ($availableYears->isNotEmpty())
                    <form method="GET" action="{{ route('activities.index') }}">
                        <select name="annee" aria-label="Année des activités" class="form-select" onchange="this.form.submit()" style="max-width: 200px;">
                            <option value="">Toutes les années</option>
                            @foreach ($availableYears as $y)
                                <option value="{{ $y }}" {{ (string) $year === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <a href="{{ route('activities.create') }}" class="btn primary">+ Nouvelle activité</a>
            </div>
        </div>

        @if ($activities->isEmpty())
            <x-empty-state icon="list" title="Aucune activité déclarée pour le moment." />
        @else
            <div class="table-responsive">
            <table class="market-table sortable">
                <thead>
                    <tr>
                        <th data-sort="number">Année</th>
                        <th data-sort="text">Axe / Sous-axe</th>
                        <th data-sort="text">Désignation</th>
                        <th data-sort="number">Montant</th>
                        <th data-sort="text">Statut</th>
                        <th data-sort="text">Pièce justificative</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activities as $activity)
                        <tr>
                            <td>{{ $activity->year }}</td>
                            <td class="cell-clamp" title="{{ $activity->axe_label }} — {{ $activity->sous_axe_label }}">{{ $activity->axe_label }} — {{ $activity->sous_axe_label }}</td>
                            <td>{{ $activity->designation }}</td>
                            <td data-sort-value="{{ $activity->montant ?? 0 }}">{{ $activity->montant !== null ? number_format((float) $activity->montant, 0, ',', ' ').' FCFA' : '—' }}</td>
                            <td><x-status-badge :status="$activity->status" /></td>
                            <td>
                                @if ($activity->documents->isNotEmpty())
                                    <span class="status-badge status-gain">Jointe</span>
                                @else
                                    <span class="status-badge status-loss">Absente</span>
                                @endif
                            </td>
                            <td><a href="{{ route('activities.show', $activity) }}" class="security-btn">Voir</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
@endsection
