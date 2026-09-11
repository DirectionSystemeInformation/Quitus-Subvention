@extends('layouts.dashboard', ['active' => 'activities'])

@section('title', 'Activités')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Activités</h1>
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

        @if ($activities->isNotEmpty())
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom: 20px;">
                <input type="search" class="form-input js-table-search" data-target="activities-table" placeholder="Rechercher une activité..." style="flex:1; min-width:220px;">
                <select class="form-select js-table-status-filter" data-target="activities-table" style="max-width: 200px;">
                    <option value="">Tous les statuts</option>
                    <option value="brouillon">Brouillon</option>
                    <option value="soumis">Soumis</option>
                    <option value="valide">Validé</option>
                    <option value="rejete">Rejeté</option>
                </select>
            </div>
        @endif

        @if ($activities->isEmpty())
            <x-empty-state icon="list" title="Aucune activité déclarée pour le moment." />
        @else
            <div class="table-responsive">
            <table class="market-table sortable" id="activities-table">
                <thead>
                    <tr>
                        <th data-sort="number">Année</th>
                        <th data-sort="text">Activité</th>
                        <th data-sort="text">Axe / Sous-axe</th>
                        <th data-sort="number">Montant</th>
                        <th data-sort="text">Justificatifs</th>
                        <th data-sort="text">Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activities as $activity)
                        @php
                            $piecesCount = $activity->documents->count();
                            $actionUrl = $activity->status === 'brouillon'
                                ? route('activities.edit', $activity)
                                : route('activities.show', $activity);
                            $actionLabel = match ($activity->status) {
                                'brouillon' => 'Continuer',
                                'rejete' => 'Corriger',
                                default => 'Consulter',
                            };
                        @endphp
                        <tr data-search-row data-status="{{ $activity->status }}">
                            <td>{{ $activity->year }}</td>
                            <td>{{ $activity->designation }}</td>
                            <td>
                                <div class="axe-cell">
                                    <span class="axe-cell-axe">{{ $activity->axe_label }}</span>
                                    <span class="axe-cell-sous-axe">{{ $activity->sous_axe_label }}</span>
                                </div>
                            </td>
                            <td data-sort-value="{{ $activity->montant ?? 0 }}">{{ $activity->montant !== null ? number_format((float) $activity->montant, 0, ',', ' ').' FCFA' : 'Non renseigné' }}</td>
                            <td><span class="justificatif-chip">{{ $piecesCount > 0 ? $piecesCount.' pièce'.($piecesCount > 1 ? 's' : '') : 'Aucune pièce' }}</span></td>
                            <td><x-status-badge :status="$activity->status" /></td>
                            <td><a href="{{ $actionUrl }}" class="security-btn {{ $activity->status === 'brouillon' ? 'primary' : '' }}">{{ $actionLabel }} →</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            <x-empty-state icon="search" title="Aucun résultat." :compact="true" class="js-table-empty" data-target="activities-table" style="display:none;" />
        @endif
    </div>
@endsection
