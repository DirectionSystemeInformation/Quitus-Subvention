@extends('layouts.dashboard', ['active' => 'dshn-dashboard'])
@section('title', 'Vue d’ensemble')
@push('styles')<link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">@endpush
@section('content')
    <x-page-heading title="Chaque dossier, une prochaine étape." eyebrow="Vue d’ensemble" :description="'Bonjour '.auth()->user()->name.'. Retrouvez les éléments qui attendent votre intervention.'" />
    @php $nextItem = $actionItems->first(); @endphp
    @if ($nextItem)
        <section class="platform-feature" aria-labelledby="priorityTitle">
            <div><p class="platform-eyebrow">Le dossier en attente depuis le plus longtemps</p><h2 id="priorityTitle">{{ $nextItem['federation'] }}</h2><p>{{ $nextItem['type'] }} · En attente depuis {{ (int) $nextItem['since']->diffInDays(now()) }} jour(s).</p></div>
            <a class="btn" href="{{ $nextItem['url'] }}">Examiner le dossier <x-ui-icon name="arrow" /></a>
        </section>
    @else
        <section class="platform-feature"><div><p class="platform-eyebrow">Votre file de traitement</p><h2>Aucun dossier en attente.</h2><p>Les nouvelles transmissions apparaîtront ici.</p></div><a class="btn" href="{{ role_route('reports.index', ['statut' => 'tous']) }}">Consulter les documents</a></section>
    @endif
    <div class="platform-action-grid">
        <x-action-card title="Documents à examiner" :count="$stats['reports_soumis']" :href="role_route('reports.index')" description="Rapports et programmes transmis." icon="file" />
        <x-action-card title="Fédérations à valider" :count="$stats['pending_federations']" :href="role_route('federations.index')" description="Demandes d’accès à la plateforme." icon="users" />
        @if ($isAdmin)<x-action-card title="Activités soumises" :count="$stats['activities_soumis']" :href="route('dgf.activities.index')" description="Justificatifs à contrôler par la DGF." icon="check" />@endif
    </div>
    <section class="platform-panel platform-queue">
        <div class="platform-queue-header"><h2>À votre attention</h2><p>Les dossiers les plus anciens en premier · {{ $actionItems->count() }} élément(s) affiché(s)</p></div>
        @if ($actionItems->isEmpty())
            <x-empty-state icon="check" title="Votre file est à jour." description="Vous pouvez suivre les campagnes et consulter les dossiers déjà traités." />
        @else
            <div class="table-responsive" tabindex="0" role="region" aria-label="Liste des dossiers"><table class="market-table"><thead><tr><th scope="col">Fédération</th><th scope="col">À examiner</th><th scope="col">En attente depuis</th><th scope="col">Action</th></tr></thead><tbody>
                @foreach ($actionItems as $item)
                    <tr><td><strong>{{ $item['federation'] }}</strong></td><td data-label="À examiner">{{ $item['type'] }}</td><td data-label="En attente depuis">{{ $item['since']->format('d/m/Y') }}<small>{{ (int) $item['since']->diffInDays(now()) }} jour(s)</small></td><td><a class="security-btn" href="{{ $item['url'] }}">Examiner <x-ui-icon name="arrow" /></a></td></tr>
                @endforeach
            </tbody></table></div>
        @endif
    </section>
    <div class="platform-section-title"><h2>Repères de suivi</h2><p>Tous exercices confondus</p></div>
    <dl class="platform-metrics"><div><dt>Fédérations actives</dt><dd>{{ $stats['active_federations'] }}</dd></div><div><dt>Documents validés</dt><dd>{{ $stats['reports_valide'] }}</dd></div><div><dt>Documents rejetés</dt><dd>{{ $stats['reports_rejete'] }}</dd></div></dl>
@endsection
