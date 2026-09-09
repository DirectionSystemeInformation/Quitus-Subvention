@extends('layouts.dashboard', ['active' => 'dashboard'])
@section('title', 'Mon dossier')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/federation-dashboard.css') }}">
@endpush
@section('content')
    @php
        $user = auth()->user();
        $dossier = \App\Support\FederationDossier::forUser($user, request('annee'));
        extract($dossier);
    @endphp
    <header class="page-header dossier-header">
        <div><p class="dossier-eyebrow">{{ $user->federation_name }}</p><h1>Mon dossier — Campagne {{ $programmeYear }}</h1><p>Rapport {{ $rapportYear }} et programme {{ $programmeYear }}</p></div>
        <form method="GET" action="{{ route('dashboard') }}" class="dossier-year">
            <label for="dossierYear">Campagne</label>
            <select id="dossierYear" name="annee" class="form-select">
                @foreach ($availableYears as $availableYear)<option value="{{ $availableYear }}" @selected((int) $availableYear === $programmeYear)>{{ $availableYear }}</option>@endforeach
            </select>
            <button class="btn" type="submit">Afficher</button>
        </form>
    </header>
    <section class="card dossier-next tone-{{ $state['tone'] }}" aria-labelledby="nextActionTitle">
        <div>
            <p class="dossier-eyebrow">{{ $state['tone'] === 'complete' ? 'Dossier abouti' : ($state['tone'] === 'waiting' ? 'Suivi du dossier' : 'Votre prochaine action') }}</p>
            <h2 id="nextActionTitle">{{ $state['title'] }}</h2>
            <p>{{ $state['detail'] }}</p>
            @if ($allocation && $adjustmentOpen && $allocation->montant_final !== null)
                <p class="dossier-amount">Montant alloué : <strong>{{ number_format((float) $allocation->montant_final, 0, ',', ' ') }} FCFA</strong></p>
            @endif
        </div>
        <a href="{{ $state['url'] }}" class="btn primary">{{ $state['action'] }}</a>
    </section>
    <ol class="dossier-progress" aria-label="Progression du dossier">
        @foreach ($steps as $step)
            <li class="dossier-progress-step tone-{{ $step['tone'] }}" @if ($loop->iteration === $state['step']) aria-current="step" @endif>
                <span class="dossier-step-number" aria-hidden="true">{{ $step['tone'] === 'complete' ? '✓' : $loop->iteration }}</span>
                <div><strong>{{ $step['label'] }}</strong>@if ($step['year'])<span class="dossier-step-year">{{ $step['year'] }}</span>@endif<span class="dossier-step-state">{{ $step['status'] }}</span></div>
            </li>
        @endforeach
    </ol>
    @if ($lastEvent)
        <p class="dossier-event"><strong>Dernière mise à jour</strong> · {{ $lastEvent['date']->format('d/m/Y à H:i') }} — @if ($lastEvent['url'])<a href="{{ $lastEvent['url'] }}">{{ $lastEvent['label'] }}</a>@else{{ $lastEvent['label'] }}@endif</p>
    @endif
    <div class="dossier-section-heading"><h2>Les documents de mon dossier</h2><a href="{{ $documentsUrl }}">Tous mes documents</a></div>
    <div class="dossier-documents">
        @php
            $documentCards = [
                ['title' => 'Rapport d’activité '.$rapportYear, 'report' => $rapport, 'help' => 'Constitué automatiquement à partir de vos activités validées par la DGF.', 'edit' => $activitiesUrl, 'action' => 'Gérer mes activités'],
                ['title' => 'Programme budgétisé '.$programmeYear, 'report' => $programme, 'help' => 'Les activités prévues et leur budget, à soumettre à la DSHN.', 'edit' => $programmeUrl, 'action' => $programme ? 'Reprendre le programme' : 'Préparer le programme'],
            ];
            if ($adjustmentOpen || $reamenage) $documentCards[] = ['title' => 'Programme réaménagé '.$programmeYear, 'report' => $reamenage, 'help' => 'Le programme adapté au montant retenu après l’arbitrage.', 'edit' => $reamenageUrl, 'action' => 'Préparer le programme réaménagé'];
        @endphp
        @foreach ($documentCards as $document)
            <article class="card dossier-document">
                <h3>{{ $document['title'] }}</h3>
                <x-status-badge :status="$document['report']?->status ?? 'manquant'" />
                <p>{{ $document['help'] }}</p>
                @if ($document['report']?->status === 'rejete' && $document['report']->rejection_reason)<p class="dossier-rejection"><strong>À corriger :</strong> {{ $document['report']->rejection_reason }}</p>@endif
                <div class="btn-group">
                    @if ($document['report'])<a class="btn" href="{{ route('activity-form.show', $document['report']) }}">Consulter</a>@endif
                    @if ($document['report']?->status !== 'valide')<a class="btn" href="{{ $document['edit'] }}">{{ $document['action'] }}</a>@endif
                </div>
            </article>
        @endforeach
    </div>
    <section class="card dossier-activity-summary" aria-labelledby="activityOverviewTitle">
        <div><h2 id="activityOverviewTitle">Mes activités {{ $rapportYear }}</h2><p>Les activités validées alimentent votre rapport automatiquement.</p></div>
        <dl>
            @foreach (['brouillon' => 'Brouillons', 'soumis' => 'À vérifier par la DGF', 'valide' => 'Validées', 'rejete' => 'À corriger'] as $status => $label)
                <div><dt>{{ $label }}</dt><dd>{{ $activityCounts->get($status, 0) }}</dd></div>
            @endforeach
        </dl>
        <a class="btn" href="{{ $activitiesUrl }}">Voir mes activités</a>
    </section>
    <section class="card" aria-labelledby="archiveTitle">
        <h2 class="card-title" id="archiveTitle">Historique des documents</h2>
        @if ($reports->isEmpty())
            <x-empty-state icon="inbox" title="Vos documents apparaîtront ici après leur premier enregistrement." />
        @else
            <div class="table-responsive"><table class="market-table"><thead><tr><th scope="col">Document</th><th scope="col">Année</th><th scope="col">Statut</th><th scope="col">Mis à jour le</th><th scope="col">Action</th></tr></thead><tbody>
                @foreach ($reports as $archivedReport)
                    <tr><td>{{ $archivedReport->typeLabel() }}</td><td>{{ $archivedReport->year }}</td><td><x-status-badge :status="$archivedReport->status" /></td><td>{{ $archivedReport->updated_at->format('d/m/Y') }}</td><td><a class="security-btn" href="{{ route('activity-form.show', $archivedReport) }}">Consulter</a></td></tr>
                @endforeach
            </tbody></table></div>
        @endif
    </section>
@endsection
