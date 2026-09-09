@extends('layouts.dashboard', ['active' => 'reports'])

@section('title', 'Rapports reçus')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Rapports reçus</h1>
        <p>Consultez et traitez les documents transmis par les fédérations.</p>
    </div>

    @if ($reports->isNotEmpty())
        <div class="js-visual-tabs queue-tabs" data-syncs="reportsStatusFilter" style="margin-bottom: 20px;" aria-label="Filtrer par statut">
            <button type="button" class="btn active primary" data-value="">Tous ({{ $counts['total'] }})</button>
            <button type="button" class="btn" data-value="soumis">À traiter ({{ $counts['soumis'] }})</button>
            <button type="button" class="btn" data-value="valide">Validés ({{ $counts['valide'] }})</button>
            <button type="button" class="btn" data-value="rejete">Rejetés ({{ $counts['rejete'] }})</button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Documents déposés</h2>
        </div>

        @if ($reports->isNotEmpty())
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom: 16px;">
                <input type="search" class="form-input js-table-search" data-target="reports-table" placeholder="Rechercher une fédération..." style="flex:1; min-width:220px;">
                <select class="form-select js-table-filter" data-target="reports-table" data-field="group" style="max-width: 240px;">
                    <option value="">Tous les types</option>
                    @foreach ($documentTypes as $type => $label)
                        <option value="{{ $type }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select class="form-select js-table-filter" data-target="reports-table" data-field="annee" style="max-width: 140px;">
                    <option value="">Toutes les années</option>
                    @foreach ($years as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
                <select id="reportsStatusFilter" class="form-select js-table-status-filter" data-target="reports-table" style="display:none;">
                    <option value="">Tous les statuts</option>
                    <option value="soumis">Soumis</option>
                    <option value="valide">Validé</option>
                    <option value="rejete">Rejeté</option>
                </select>
            </div>

            <p class="table-summary-line">
                <strong>{{ $counts['total'] }}</strong> document{{ $counts['total'] > 1 ? 's' : '' }}
                &bull; {{ $counts['valide'] }} validé{{ $counts['valide'] > 1 ? 's' : '' }}
                &bull; {{ $counts['soumis'] }} en attente
                &bull; {{ $counts['rejete'] }} rejeté{{ $counts['rejete'] > 1 ? 's' : '' }}
            </p>
        @endif

        @if ($reports->isEmpty())
            <x-empty-state icon="inbox" title="Aucun document déposé pour le moment." />
        @else
            <div class="table-responsive">
            <table class="market-table" id="reports-table">
                <thead>
                    <tr>
                        <th>Fédération</th>
                        <th>Année</th>
                        <th>Déposé le</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportsByType as $group)
                        <tr class="table-group-header" data-group-header="{{ $group['type'] }}">
                            <td colspan="5">
                                <svg class="group-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                                {{ $group['label'] }} ({{ $group['reports']->count() }})
                            </td>
                        </tr>
                        @foreach ($group['reports'] as $report)
                            <tr data-search-row data-status="{{ $report->status }}" data-group="{{ $group['type'] }}" data-annee="{{ $report->year }}">
                                <td>{{ $report->user->federation_name }}</td>
                                <td>{{ $report->year }}</td>
                                <td>
                                    <div class="cell-datetime">
                                        <span>{{ $report->created_at->format('d/m/Y') }}</span>
                                        <span class="cell-datetime-time">{{ $report->created_at->format('H:i') }}</span>
                                    </div>
                                </td>
                                <td><x-status-badge :status="$report->status" /></td>
                                <td>
                                    <div style="display:flex;gap:12px;align-items:center;">
                                        <a href="{{ route('activity-form.show', $report) }}" class="security-btn">Consulter →</a>
                                        @if ($report->status !== 'valide')
                                            <form method="POST" action="{{ role_route('reports.validate', $report) }}">
                                                @csrf
                                                <button type="submit" class="security-btn primary">Valider</button>
                                            </form>
                                        @endif
                                        @if ($report->status === 'soumis')
                                            <button type="button" class="security-btn js-reject-reason" data-action="{{ role_route('reports.reject', $report) }}">Rejeter</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            </div>
            <x-empty-state icon="search" title="Aucun résultat." :compact="true" class="js-table-empty" data-target="reports-table" style="display:none;" />
        @endif
    </div>
@endsection
