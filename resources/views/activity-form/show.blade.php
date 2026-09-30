@extends('layouts.dashboard', ['active' => auth()->user()->isDshn() ? 'reports' : 'documents'])
@section('title', $title)
@push('styles')<link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">@endpush
@section('content')
    <nav class="breadcrumb" aria-label="Fil d’Ariane">
        <a href="{{ auth()->user()->isDshn() ? role_route('reports.index') : route('documents.index', ['type' => $type, 'annee' => $report->year]) }}">← Rapports & programmes</a>
        <span class="breadcrumb-separator">/</span><span>Document #{{ $report->id }}</span>
    </nav>
    <x-page-heading :title="$title.' '.$report->year" :eyebrow="'Dossier de subvention · Campagne '.$campaignYear" :description="$report->user->federation_name">
        <x-status-badge :status="$report->status" />
    </x-page-heading>
    <nav class="record-section-nav" aria-label="Dans ce document"><a href="#recordContent">Contenu du document</a><a href="#recordRelated">Documents du dossier</a><a href="#recordDecision">{{ auth()->user()->isDshn() ? 'Décision et suivi' : 'Statut et suivi' }}</a></nav>
    @if ($report->status === 'rejete' && $report->rejection_reason)
        <section class="record-rejection"><h2>Motif du rejet</h2><p>{{ $report->rejection_reason }}</p></section>
    @endif
    <div class="platform-record-grid">
        <div class="platform-record-content">
            <dl class="record-summary"><div><dt>{{ $type === 'rapport_activite' ? 'Activités validées' : 'Lignes budgétaires' }}</dt><dd>{{ $linesCount }}</dd></div><div><dt>Axes renseignés</dt><dd>{{ $axesCount }}</dd></div><div><dt>Montant total</dt><dd>{{ number_format((float) $totalMontant, 0, ',', ' ') }} <small>FCFA</small></dd></div></dl>
            <section id="recordContent" aria-label="Contenu du document">
                @if ($programmeLabel)<p class="platform-eyebrow">{{ $programmeLabel }}</p>@endif
                @if ($axeGroups->isEmpty())
                    <div class="platform-panel"><x-empty-state icon="list" title="Aucune ligne renseignée." description="Les activités et leurs montants apparaîtront ici après leur enregistrement." /></div>
                @else
                    @foreach ($axeGroups as $axeGroup)
                        <article class="platform-panel" style="margin-bottom: 22px;">
                            <h2 class="axe-heading">Axe {{ $axeGroup['number'] ?? $axeGroup['axe'] }}@if ($axeGroup['label_parts']['description']) : {{ $axeGroup['label_parts']['description'] }}@endif</h2>
                            @foreach ($axeGroup['sous_axes'] as $sousAxe)
                                <h3 class="sousaxe-heading">{{ $sousAxe['sous_axe_code'] }} : {{ $sousAxe['sous_axe_label'] }}</h3>
                                <div class="table-responsive" tabindex="0" role="region" aria-label="Tableau : défilement horizontal disponible"><table class="pb-table"><thead><tr><th scope="col">Activité</th><th scope="col">Montant (FCFA)</th><th scope="col">Partenaires (FCFA)</th><th scope="col">Date</th><th scope="col">Observations</th></tr></thead><tbody>
                                    @foreach ($sousAxe['lines'] as $line)
                                        <tr><td>{{ $line->designation ?: 'Non renseignée' }}</td><td>{{ $line->montant !== null ? number_format((float) $line->montant, 0, ',', ' ') : '—' }}</td><td>{{ is_numeric($line->contribution_partenaires) ? number_format((float) $line->contribution_partenaires, 0, ',', ' ') : ($line->contribution_partenaires ?: '—') }}</td><td>{{ optional($line->date)->format('d/m/Y') ?? '—' }}</td><td>{{ $line->observations ?: '—' }}</td></tr>
                                    @endforeach
                                </tbody></table></div>
                            @endforeach
                        </article>
                    @endforeach
                    <div class="report-totals"><div class="report-totals-row"><span>Montant total</span><strong>{{ number_format((float) $totalMontant, 0, ',', ' ') }} FCFA</strong></div><div class="report-totals-row"><span>Contribution des partenaires</span><strong>{{ number_format((float) $totalContribution, 0, ',', ' ') }} FCFA</strong></div></div>
                @endif
            </section>
            <section class="platform-panel" id="recordRelated" style="margin-top: 24px;">
                <h2>Les autres documents du dossier</h2>
                @forelse ($relatedReports as $related)
                    <a class="record-related" href="{{ route('activity-form.show', $related) }}"><span>{{ $related->typeLabel() }} · {{ $related->year }}</span><x-status-badge :status="$related->status" /></a>
                @empty
                    <x-empty-state icon="file" title="Aucun autre document accessible pour cette campagne." :compact="true" />
                @endforelse
            </section>
        </div>
        <aside class="platform-record-aside" aria-label="Décision et suivi du document">
            <section class="platform-panel record-decision" id="recordDecision">
                <p class="platform-eyebrow">Document #{{ $report->id }}</p><h2>{{ auth()->user()->isDshn() ? 'Décision sur le document' : 'Suivi du document' }}</h2><x-status-badge :status="$report->status" />
                @if ($report->status === 'valide')
                    <p>Le document a été validé par la DSHN. Sa version PDF est disponible.</p>
                    <a href="{{ route('activity-form.pdf', $report) }}" class="btn info"><x-ui-icon name="download" /> Télécharger le PDF</a>
                @elseif (auth()->user()->isDshn())
                    <p>Examinez les activités, les montants et les observations avant de prendre votre décision.</p>
                    <form method="POST" action="{{ role_route('reports.validate', $report) }}" data-confirm="Valider ce document pour la campagne {{ $campaignYear }} ? La fédération pourra télécharger sa version validée.">@csrf<button type="submit" class="btn primary">Valider le document</button></form>
                    @if ($report->status === 'soumis')<button type="button" class="btn danger js-reject-reason" data-action="{{ role_route('reports.reject', $report) }}">Rejeter avec un motif</button>@endif
                @else
                    <p>{{ $report->status === 'brouillon' ? 'Ce brouillon reste dans votre espace tant que vous ne le transmettez pas.' : ($report->status === 'rejete' ? 'Consultez le motif, corrigez votre document puis transmettez-le à nouveau.' : 'Votre document est transmis. La DSHN doit maintenant l’examiner.') }}</p>
                    @if ($type === 'rapport_activite')
                        <a href="{{ route('activities.index', ['annee' => $report->year]) }}" class="btn primary">Gérer les activités du rapport</a>
                    @else
                        <a href="{{ route(str_replace('_', '-', $type).'.create', ['annee' => $report->year]) }}" class="btn primary">{{ $report->status === 'brouillon' ? 'Reprendre le brouillon' : 'Modifier le programme' }}</a>
                    @endif
                @endif
                @if (auth()->user()->isDshn() && $report->file_path)<a class="btn" href="{{ role_route('reports.download', $report) }}">Pièce originale jointe</a>@endif
            </section>
            <x-record-history :record="$report" />
        </aside>
    </div>
@endsection
