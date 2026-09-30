@extends('layouts.dashboard', ['active' => 'reports'])
@section('title', 'Rapports & programmes')
@push('styles')<link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">@endpush
@section('content')
    <x-page-heading title="Rapports & programmes" eyebrow="Instruction des dossiers" description="Retrouvez les documents transmis, examinez leur contenu et suivez les décisions." />
    <nav class="platform-tabs" aria-label="Statut des documents">
        @foreach (['soumis' => 'À examiner', 'valide' => 'Validés', 'rejete' => 'Rejetés', 'tous' => 'Tous les documents'] as $key => $label)
            <a href="{{ role_route('reports.index', array_merge(request()->only('q', 'type', 'annee'), ['statut' => $key])) }}" @if ($status === $key) aria-current="page" @endif>{{ $label }}<span>{{ $counts[$key === 'tous' ? 'total' : $key] }}</span></a>
        @endforeach
    </nav>
    <section class="platform-panel platform-queue">
        <div class="platform-queue-header"><h2>{{ $status === 'soumis' ? 'Votre file de traitement' : 'Documents transmis' }}</h2><p>Recherchez dans l’ensemble des documents, sur toutes les pages.</p></div>
        <form method="GET" action="{{ role_route('reports.index') }}" class="platform-filters">
            <input type="hidden" name="statut" value="{{ $status }}">
            <div class="form-group"><label class="form-label" for="reportSearch">Fédération</label><input id="reportSearch" name="q" value="{{ request('q') }}" class="form-input" type="search" placeholder="Nom de la fédération…" maxlength="150"></div>
            <div class="form-group"><label class="form-label" for="reportType">Type de document</label><select id="reportType" name="type" class="form-select"><option value="">Tous les types</option>@foreach ($documentTypes as $key => $label)<option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="form-group"><label class="form-label" for="reportYear">Exercice</label><select id="reportYear" name="annee" class="form-select"><option value="">Tous les exercices</option>@foreach ($years as $year)<option value="{{ $year }}" @selected((string) request('annee') === (string) $year)>{{ $year }}</option>@endforeach</select></div>
            <div class="filter-actions"><button class="btn primary" type="submit">Filtrer</button>@if (request('q') || request('type') || request('annee'))<a class="btn" href="{{ role_route('reports.index', ['statut' => $status]) }}">Effacer</a>@endif</div>
        </form>
        <p class="queue-result-line"><strong>{{ $reports->total() }}</strong> document(s) trouvé(s) @if ($reports->total()) · {{ $reports->firstItem() }}–{{ $reports->lastItem() }} affichés @endif</p>
        @if ($reports->isEmpty())
            <x-empty-state icon="search" title="Aucun document dans cette sélection." description="Essayez un autre statut ou retirez un filtre pour élargir la recherche." />
        @else
            <div class="table-responsive" tabindex="0" role="region" aria-label="Liste des dossiers"><table class="market-table" id="reports-table"><thead><tr><th scope="col">Fédération / document</th><th scope="col">Exercice</th><th scope="col">Mis à jour le</th><th scope="col">Statut</th><th scope="col">Action</th></tr></thead><tbody>
                @foreach ($reports as $report)
                    <tr><td><strong>{{ $report->user->federation_name }}</strong><small>{{ $report->typeLabel() }}</small></td><td data-label="Exercice">{{ $report->year }}</td><td data-label="Mis à jour le">{{ $report->updated_at->format('d/m/Y') }}<small>{{ $report->updated_at->format('H:i') }}</small></td><td data-label="Statut"><x-status-badge :status="$report->status" :label="$report->status === 'soumis' ? 'À examiner' : null" /></td><td><a class="security-btn {{ $report->status === 'soumis' ? 'primary' : '' }}" href="{{ route('activity-form.show', $report) }}">{{ $report->status === 'soumis' ? 'Examiner' : 'Consulter' }} <x-ui-icon name="arrow" /></a></td></tr>
                @endforeach
            </tbody></table></div>
            <div class="platform-pagination">{{ $reports->links() }}</div>
        @endif
    </section>
@endsection
