@extends('layouts.dashboard', ['active' => 'reports'])

@section('title', 'Rapports reçus')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Rapports reçus</h1>
        <p>Rapports d'activité et projets de programmes budgétisés déposés par les fédérations</p>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Documents déposés</h2>
            @if ($reports->isNotEmpty())
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <select class="form-select js-table-status-filter" data-target="reports-table" style="max-width: 170px;">
                        <option value="">Tous les statuts</option>
                        <option value="soumis">Soumis</option>
                        <option value="valide">Validé</option>
                        <option value="rejete">Rejeté</option>
                    </select>
                    <input type="search" class="form-input js-table-search" data-target="reports-table" placeholder="Rechercher..." style="max-width: 260px;">
                </div>
            @endif
        </div>

        @if ($reports->isEmpty())
            <x-empty-state icon="inbox" title="Aucun document déposé pour le moment." />
        @else
            <div class="table-responsive">
            <table class="market-table sortable" id="reports-table">
                <thead>
                    <tr>
                        <th data-sort="text">Fédération</th>
                        <th data-sort="text">Document</th>
                        <th data-sort="number">Année</th>
                        <th data-sort="number">Déposé le</th>
                        <th data-sort="text">Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr data-search-row data-status="{{ $report->status }}">
                            <td>{{ $report->user->federation_name }}</td>
                            <td>{{ $report->typeLabel() }}</td>
                            <td>{{ $report->year }}</td>
                            <td data-sort-value="{{ $report->created_at->timestamp }}">{{ $report->created_at->format('d/m/Y H:i') }}</td>
                            <td><x-status-badge :status="$report->status" /></td>
                            <td>
                                <div style="display:flex;gap:12px;align-items:center;">
                                    <a href="{{ route('activity-form.show', $report) }}" class="security-btn">Voir le détail</a>
                                    @if ($report->status !== 'valide')
                                        <form method="POST" action="{{ role_route('reports.validate', $report) }}">
                                            @csrf
                                            <button type="submit" class="security-btn primary">Valider</button>
                                        </form>
                                    @endif
                                    @if ($report->status !== 'rejete')
                                        <button type="button" class="security-btn js-reject-reason" data-action="{{ role_route('reports.reject', $report) }}">Rejeter</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            <x-empty-state icon="search" title="Aucun résultat." :compact="true" class="js-table-empty" data-target="reports-table" style="display:none;" />
        @endif
    </div>
@endsection
