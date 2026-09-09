@extends('layouts.dashboard', ['active' => 'dgf-activities'])

@section('title', 'Activités à valider')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Activités à valider</h1>
        <p>Consultez les activités soumises par les fédérations et vérifiez leurs justificatifs.</p>
    </div>

    <div class="card">
        <nav class="queue-tabs" aria-label="Justificatif des activités">
            @foreach (['avec' => 'À vérifier', 'sans' => 'En attente de justificatif'] as $value => $label)
                <a href="{{ route('dgf.activities.index', array_merge(request()->only('annee', 'federation'), ['justificatif' => $value])) }}" class="btn {{ $justificatif === $value ? 'primary' : '' }}" @if ($justificatif === $value) aria-current="page" @endif>{{ $label }} ({{ $counts[$value] }})</a>
            @endforeach
        </nav>
        <form method="GET" class="queue-filters" action="{{ route('dgf.activities.index') }}">
            <input type="hidden" name="justificatif" value="{{ $justificatif }}">
            <div class="form-group">
                <label class="form-label" for="queueSearch">Rechercher</label>
                <input type="search" class="form-input js-table-search" data-target="dgf-activities-table" id="queueSearch" placeholder="Fédération ou activité...">
            </div>
            <div class="form-group">
                <label class="form-label" for="queueFederation">Fédération</label>
                <select class="form-select" name="federation" id="queueFederation">
                    <option value="">Toutes les fédérations</option>
                    @foreach ($federations as $federation)
                        <option value="{{ $federation->id }}" @selected((string) request('federation') === (string) $federation->id)>{{ $federation->federation_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="queueYear">Année</label>
                <select class="form-select" name="annee" id="queueYear">
                    <option value="">Toutes les années</option>
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected((string) request('annee') === (string) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn primary" type="submit">Filtrer</button>
            <a class="btn" href="{{ route('dgf.activities.index', ['justificatif' => $justificatif]) }}">Réinitialiser</a>
        </form>
        @if ($justificatif === 'sans')
            <p class="strength-text">Ces activités n'ont pas encore de pièce jointe. La validation ne sera possible qu'une fois un justificatif ajouté par la fédération.</p>
        @endif

        @if ($activities->isEmpty())
            <x-empty-state icon="inbox" :title="$justificatif === 'avec' ? 'Aucune activité à vérifier pour ces filtres.' : 'Aucune activité en attente de justificatif pour ces filtres.'" />
        @else
            <div class="table-responsive">
            <table class="market-table sortable" id="dgf-activities-table">
                <thead>
                    <tr>
                        <th data-sort="text">Fédération</th>
                        <th data-sort="text">Activité</th>
                        <th data-sort="text">Axe / Sous-axe</th>
                        <th data-sort="number">Année</th>
                        <th data-sort="number">Soumis le</th>
                        <th data-sort="text">Justificatifs</th>
                        <th data-sort="text">Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activities as $activity)
                        @php $piecesCount = $activity->documents->count(); @endphp
                        <tr data-search-row>
                            <td>{{ $activity->user->federation_name }}</td>
                            <td>{{ $activity->designation }}</td>
                            <td class="cell-clamp" title="{{ $activity->axe_label }} — {{ $activity->sous_axe_label }}">{{ $activity->axe_label }} — {{ $activity->sous_axe_label }}</td>
                            <td>{{ $activity->year }}</td>
                            <td data-sort-value="{{ optional($activity->submitted_at)->timestamp ?? 0 }}">{{ $activity->submitted_at?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                @if ($piecesCount > 0)
                                    <span class="justificatif-chip">📎 {{ $piecesCount }} {{ $piecesCount > 1 ? 'pièces' : 'pièce' }}</span>
                                @else
                                    <span class="justificatif-chip is-empty">Aucune pièce</span>
                                @endif
                            </td>
                            <td>
                                @if ($activity->status === 'soumis')
                                    <x-status-badge status="soumis" label="À vérifier" />
                                @else
                                    <x-status-badge :status="$activity->status" />
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('dgf.activities.show', $activity) }}" class="security-btn {{ $activity->status === 'soumis' ? 'primary' : '' }}">{{ $activity->status === 'soumis' ? 'Vérifier →' : 'Voir' }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            <x-empty-state icon="search" title="Aucun résultat." :compact="true" class="js-table-empty" data-target="dgf-activities-table" style="display:none;" />
            {{ $activities->links() }}
        @endif
    </div>
@endsection
