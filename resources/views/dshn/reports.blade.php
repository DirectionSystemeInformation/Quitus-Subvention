@extends('layouts.dashboard', ['active' => 'reports'])

@section('title', 'Rapports reçus')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@php
    // Un onglet par type de document, dans l'ordre du circuit : rapport d'activité,
    // programme budgétisé, puis programme réaménagé (n'apparaît qu'à partir de l'étape 11).
    $sections = [
        'rapport_activite' => "Rapport d'activité",
        'programme_budgetise' => "Projet de programme d'activités budgétisé",
        'programme_reamenage' => "Programme d'activités budgétisé réaménagé",
    ];
@endphp

@section('content')
    <div class="page-header">
        <h1>Rapports reçus</h1>
        <p>Rapports d'activité et projets de programmes budgétisés déposés par les fédérations</p>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Documents déposés</h2>
        </div>

        @if ($reports->isEmpty())
            <x-empty-state icon="inbox" title="Aucun document déposé pour le moment." />
        @else
            <div class="section-tabs" role="tablist" aria-label="Type de document">
                @foreach ($sections as $type => $label)
                    <button type="button"
                        class="section-tab {{ $loop->first ? 'active' : '' }}"
                        role="tab"
                        id="tab-{{ $type }}"
                        aria-controls="panel-{{ $type }}"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        data-tab-target="panel-{{ $type }}">
                        {{ $label }}
                        <span class="section-tab-count">{{ $reports->get($type, collect())->count() }}</span>
                    </button>
                @endforeach
            </div>

            @foreach ($sections as $type => $label)
                @php
                    $sectionReports = $reports->get($type, collect());
                    $tableId = 'reports-table-'.$type;
                @endphp
                <div class="section-panel {{ $loop->first ? 'active' : '' }}" id="panel-{{ $type }}" role="tabpanel" aria-labelledby="tab-{{ $type }}">
                    @if ($sectionReports->isEmpty())
                        <x-empty-state icon="inbox" title="Aucun document de ce type pour le moment." />
                    @else
                        <div class="list-toolbar">
                            <div class="list-toolbar-filter">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                                </svg>
                                <select class="js-table-status-filter" data-target="{{ $tableId }}" aria-label="Filtrer par statut">
                                    <option value="">Tous les statuts</option>
                                    <option value="soumis">Soumis</option>
                                    <option value="valide">Validé</option>
                                    <option value="rejete">Rejeté</option>
                                </select>
                            </div>
                            <div class="topbar-search">
                                <svg aria-hidden="true" class="topbar-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"/>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                                <input type="search" class="js-table-search" data-target="{{ $tableId }}" placeholder="Rechercher une fédération..." aria-label="Rechercher une fédération">
                            </div>
                        </div>

                        <div class="table-responsive">
                        <table class="market-table sortable" id="{{ $tableId }}">
                            <thead>
                                <tr>
                                    <th data-sort="text">Fédération</th>
                                    <th data-sort="number">Année</th>
                                    <th data-sort="number">Déposé le</th>
                                    <th data-sort="text">Statut</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sectionReports as $report)
                                    <tr data-search-row data-status="{{ $report->status }}">
                                        <td>{{ $report->user->federation_name }}</td>
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
                        <x-empty-state icon="search" title="Aucun résultat." :compact="true" class="js-table-empty" data-target="{{ $tableId }}" style="display:none;" />
                    @endif
                </div>
            @endforeach
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            document.querySelectorAll('.section-tabs').forEach(function (tabs) {
                const buttons = tabs.querySelectorAll('.section-tab');

                buttons.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        buttons.forEach(function (b) {
                            b.classList.remove('active');
                            b.setAttribute('aria-selected', 'false');
                        });
                        btn.classList.add('active');
                        btn.setAttribute('aria-selected', 'true');

                        const targetId = btn.dataset.tabTarget;
                        document.querySelectorAll('.section-panel').forEach(function (panel) {
                            panel.classList.toggle('active', panel.id === targetId);
                        });
                    });
                });
            });
        })();
    </script>
@endpush
