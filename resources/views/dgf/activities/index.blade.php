@extends('layouts.dashboard', ['active' => 'dgf-activities'])

@section('title', 'Activités à valider')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Activités à valider</h1>
        <p>Vérifiez les activités soumises et leurs justificatifs. Les brouillons restent consultables dans un onglet séparé.</p>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">{{ $status === 'soumis' ? 'Activités à vérifier' : 'Activités en préparation' }}</h2>
        </div>

        <nav class="queue-tabs" aria-label="Statut des activités">
            @foreach (['soumis' => 'À vérifier', 'brouillon' => 'Brouillons'] as $value => $label)
                <a href="{{ route('dgf.activities.index', array_merge(request()->only('annee', 'federation'), ['statut' => $value])) }}" class="btn {{ $status === $value ? 'primary' : '' }}" @if ($status === $value) aria-current="page" @endif>{{ $label }} ({{ $counts[$value] }})</a>
            @endforeach
        </nav>
        <form method="GET" class="queue-filters" action="{{ route('dgf.activities.index') }}">
            <input type="hidden" name="statut" value="{{ $status }}">
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
            <a class="btn" href="{{ route('dgf.activities.index', ['statut' => $status]) }}">Réinitialiser</a>
        </form>
        @if ($status === 'brouillon')
            <p class="strength-text">Ces activités n'ont pas encore été soumises. La fédération doit les compléter et les soumettre avant votre vérification.</p>
        @endif

        @if ($activities->isEmpty())
            <x-empty-state icon="inbox" :title="$status === 'soumis' ? 'Aucune activité à vérifier pour ces filtres.' : 'Aucun brouillon pour ces filtres.'" />
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
                            <td>{{ $activity->axe_label }} — {{ $activity->sous_axe_label }}</td>
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
