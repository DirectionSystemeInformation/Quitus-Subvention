@extends('layouts.dashboard', ['active' => 'dgf-activities'])

@section('title', 'Activités à valider')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Activités à valider</h1>
        <p>Vérifiez les activités et leurs justificatifs. La validation n'est possible que pour une activité soumise avec au moins une pièce jointe.</p>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">{{ $justificatif === 'avec' ? 'Avec pièce justificative' : 'Sans pièce justificative' }}</h2>
        </div>

        <nav class="queue-tabs" aria-label="Justificatif des activités">
            @foreach (['avec' => 'Avec justificatif', 'sans' => 'Sans justificatif'] as $value => $label)
                <a href="{{ route('dgf.activities.index', array_merge(request()->only('annee', 'federation'), ['justificatif' => $value])) }}" class="btn {{ $justificatif === $value ? 'primary' : '' }}" @if ($justificatif === $value) aria-current="page" @endif>{{ $label }} ({{ $counts[$value] }})</a>
            @endforeach
        </nav>
        <form method="GET" class="queue-filters" action="{{ route('dgf.activities.index') }}">
            <input type="hidden" name="justificatif" value="{{ $justificatif }}">
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
            <x-empty-state icon="inbox" :title="$justificatif === 'avec' ? 'Aucune activité avec justificatif pour ces filtres.' : 'Aucune activité sans justificatif pour ces filtres.'" />
        @else
            <div class="table-responsive">
            <table class="market-table">
                <thead>
                    <tr>
                        <th data-sort="text">Fédération</th>
                        <th data-sort="number">Année</th>
                        <th data-sort="text">Axe / Sous-axe</th>
                        <th data-sort="text">Désignation</th>
                        <th data-sort="number">Montant</th>
                        <th data-sort="text">Statut</th>
                        <th data-sort="text">Justificatif</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activities as $activity)
                        <tr>
                            <td>{{ $activity->user->federation_name }}</td>
                            <td>{{ $activity->year }}</td>
                            <td class="cell-clamp" title="{{ $activity->axe_label }} — {{ $activity->sous_axe_label }}">{{ $activity->axe_label }} — {{ $activity->sous_axe_label }}</td>
                            <td>{{ $activity->designation }}</td>
                            <td data-sort-value="{{ $activity->montant ?? 0 }}">{{ $activity->montant !== null ? number_format((float) $activity->montant, 0, ',', ' ').' FCFA' : '—' }}</td>
                            <td><x-status-badge :status="$activity->status" /></td>
                            <td>{{ $activity->documents->isNotEmpty() ? 'Joint' : 'Absent' }}</td>
                            <td><a href="{{ route('dgf.activities.show', $activity) }}" class="security-btn">{{ $activity->status === 'soumis' ? 'Vérifier' : 'Voir' }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            {{ $activities->links() }}
        @endif
    </div>
@endsection
