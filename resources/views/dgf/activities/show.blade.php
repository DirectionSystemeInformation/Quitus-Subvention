@extends('layouts.dashboard', ['active' => 'dgf-activities'])

@section('title', $activity->designation ?? 'Activité')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/activity-detail.css') }}">
@endpush

@php
    $statut = $activity->status;
    $pieces = $activity->documents;
    $sansPiece = $pieces->isEmpty();
    $aDecider = $statut === 'soumis';
    $axeParts = $activity->axeLabelParts();
    $jour = fn ($date) => $date?->locale('fr')->translatedFormat('j F Y');
    $fcfa = fn ($montant) => number_format((float) $montant, 0, ',', ' ').' FCFA';
    $federation = $activity->user->federation_name ?? $activity->user->name;
    $controleur = $activity->validator?->name;

    // Période de réalisation : « du 12 au 26 septembre 2026 ».
    $debut = $activity->date_debut;
    $fin = $activity->date_fin;
    $periode = match (true) {
        ! $debut && ! $fin => null,
        ! $debut || ! $fin || $debut->isSameDay($fin) => $jour($debut ?? $fin),
        $debut->isSameMonth($fin) => 'Du '.$debut->format('j').' au '.$jour($fin),
        $debut->isSameYear($fin) => 'Du '.$debut->locale('fr')->translatedFormat('j F').' au '.$jour($fin),
        default => 'Du '.$jour($debut).' au '.$jour($fin),
    };

    // Décision attendue de la DGF, ou état de l'activité si rien n'est à décider.
    $etape = match (true) {
        $aDecider && $sansPiece => ['ton' => 'warning', 'label' => 'Décision', 'titre' => 'Justificatif manquant : validation impossible',
            'texte' => 'La fédération n’a joint aucune pièce. Rejetez l’activité avec un motif pour lui demander de la compléter.'],
        $aDecider => ['ton' => 'primary', 'label' => 'Décision attendue', 'titre' => 'Vérifiez les justificatifs puis décidez',
            'texte' => 'Soumise le '.$jour($activity->submitted_at).' par '.$federation.'. Une fois validée, l’activité sera versée automatiquement au rapport d’activité '.$activity->year.' de la fédération.'],
        $statut === 'valide' => ['ton' => 'success', 'label' => 'Statut', 'titre' => 'Activité validée',
            'texte' => 'Validée le '.$jour($activity->validated_at).($controleur ? ' par '.$controleur : '').' et versée au rapport d’activité '.$activity->year.' de la fédération.'],
        $statut === 'rejete' => ['ton' => 'danger', 'label' => 'Statut', 'titre' => 'Rejetée : en attente de correction par la fédération',
            'texte' => $activity->rejection_reason ? 'Motif communiqué : « '.$activity->rejection_reason.' »' : 'La fédération doit corriger l’activité puis la soumettre de nouveau.'],
        default => ['ton' => 'neutral', 'label' => 'Statut', 'titre' => 'En préparation par la fédération',
            'texte' => 'L’activité n’a pas encore été soumise : aucune décision n’est possible pour l’instant.'],
    };

    // Suivi, du point de vue du contrôle.
    $miseAJour = $activity->submitted_at && $activity->updated_at->gt($activity->submitted_at->copy()->addMinute()) && $statut === 'soumis'
        ? 'Modifiée par la fédération le '.$jour($activity->updated_at) : null;
    $suivi = [
        ['libelle' => 'Créée par la fédération', 'detail' => $jour($activity->created_at), 'etat' => 'fait'],
        ['libelle' => 'Soumise pour contrôle', 'detail' => $activity->submitted_at ? $jour($activity->submitted_at) : 'Pas encore soumise', 'etat' => $statut === 'brouillon' ? 'courant' : 'fait'],
        ['libelle' => 'Contrôle DGF',
            'detail' => match ($statut) { 'soumis' => $sansPiece ? 'Bloqué : justificatif manquant' : 'À vous de décider', 'rejete' => 'Rejetée, correction attendue', 'valide' => 'Validée'.($controleur ? ' par '.$controleur : ''), default => 'À venir' },
            'etat' => match ($statut) { 'soumis' => 'courant', 'rejete' => 'erreur', 'valide' => 'fait', default => 'a-venir' }],
        ['libelle' => 'Versée au rapport '.$activity->year, 'detail' => $statut === 'valide' ? $jour($activity->validated_at) : 'À venir', 'etat' => $statut === 'valide' ? 'fait' : 'a-venir'],
    ];
@endphp

@section('content')
    <div class="ad-page">
        <nav class="breadcrumb" aria-label="Fil d’Ariane"><a href="{{ route('dgf.activities.index', ['justificatif' => $sansPiece ? 'sans' : 'avec']) }}">← Contrôle des activités</a></nav>

        <header class="ad-heading">
            <div>
                <p class="ad-eyebrow">Activité réalisée · {{ $activity->year }} · n° {{ $activity->id }}</p>
                <h1 class="cap-first">{{ $activity->designation ?: 'Activité sans intitulé' }}</h1>
                <p class="ad-federation"><x-ui-icon name="users" /> {{ $federation }}</p>
                <p class="ad-axe">
                    @if ($activity->axeNumber())<span class="ad-chip is-strong">Axe {{ $activity->axeNumber() }}</span>@endif
                    <span>{{ $axeParts['description'] ?? $axeParts['prefix'] }}</span>
                    <span class="ad-sep" aria-hidden="true">›</span>
                    <span class="ad-chip">{{ $activity->sous_axe_code }}</span>
                    <span>{{ $activity->sous_axe_label }}</span>
                </p>
            </div>
            <x-status-badge :status="$statut" />
        </header>

        {{-- Décision ---------------------------------------------------------- --}}
        <section class="ad-next is-{{ $etape['ton'] }}" aria-labelledby="adNextTitle">
            <span class="ad-next-icon" aria-hidden="true">
                @switch($etape['ton'])
                    @case('success')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.2 4.2L19 7"/></svg>@break
                    @case('danger')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 7v6M12 17h.01"/><circle cx="12" cy="12" r="9.5"/></svg>@break
                    @case('warning')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2.5 20h19z"/><path d="M12 10v4M12 17h.01"/></svg>@break
                    @case('primary')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M5 7l-3 6h6zm14 0-3 6h6zM5 7l7-2 7 2M8 21h8"/></svg>@break
                    @default<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                @endswitch
            </span>
            <div class="ad-next-body">
                <p class="ad-next-label">{{ $etape['label'] }}</p>
                <h2 id="adNextTitle">{{ $etape['titre'] }}</h2>
                <p>{{ $etape['texte'] }}</p>
            </div>
            <div class="ad-next-actions">
                @if ($aDecider)
                    <button type="button" class="btn danger js-reject-reason" data-action="{{ route('dgf.activities.reject', $activity) }}">Rejeter avec un motif</button>
                    @unless ($sansPiece)
                        <form method="POST" action="{{ route('dgf.activities.validate', $activity) }}" data-confirm="Valider cette activité ? Elle sera versée au rapport d’activité {{ $activity->year }} de {{ $federation }}.">
                            @csrf
                            <button type="submit" class="btn primary"><x-ui-icon name="check" /> Valider l’activité</button>
                        </form>
                    @endunless
                @elseif ($suivante)
                    <a href="{{ route('dgf.activities.show', $suivante) }}" class="btn primary">Activité suivante <x-ui-icon name="arrow" /></a>
                @endif
            </div>
        </section>

        <div class="ad-layout">
            <div class="ad-main">
                {{-- Détails ------------------------------------------------------ --}}
                <section class="ad-card" aria-labelledby="adDetailsTitle">
                    <h2 id="adDetailsTitle" class="ad-card-title">Détails déclarés par la fédération</h2>
                    <dl class="ad-facts">
                        <div class="is-amount">
                            <dt>Montant</dt>
                            <dd>{{ $activity->montant !== null ? $fcfa($activity->montant) : 'Non renseigné' }}</dd>
                        </div>
                        <div>
                            <dt>Période de réalisation</dt>
                            <dd>{{ $periode ?? 'Non renseignée' }}</dd>
                        </div>
                        <div>
                            <dt>Contribution des partenaires</dt>
                            <dd>
                                @if (blank($activity->contribution_partenaires))
                                    <span class="ad-muted">Aucune</span>
                                @elseif (is_numeric($activity->contribution_partenaires))
                                    {{ $fcfa($activity->contribution_partenaires) }}
                                @else
                                    {{ $activity->contribution_partenaires }}
                                @endif
                            </dd>
                        </div>
                        <div class="is-full">
                            <dt>Observations de la fédération</dt>
                            <dd>{!! filled($activity->observations) ? e($activity->observations) : '<span class="ad-muted">Aucune</span>' !!}</dd>
                        </div>
                    </dl>
                </section>

                {{-- Pièces justificatives -------------------------------------- --}}
                <section class="ad-card" id="adPieces" aria-labelledby="adPiecesTitle">
                    <div class="ad-card-head">
                        <h2 id="adPiecesTitle" class="ad-card-title">Pièces justificatives <span class="ad-count">{{ $pieces->count() }}</span></h2>
                        @if ($aDecider && ! $sansPiece)
                            <p class="ad-muted">Ouvrez chaque pièce avec « Consulter » avant de décider.</p>
                        @endif
                    </div>
                    @if ($sansPiece)
                        <div class="ad-empty is-warning">
                            <strong>Aucun justificatif joint</strong>
                            <span>La validation sera possible dès que la fédération aura ajouté une pièce.</span>
                        </div>
                    @else
                        <div class="documents-grid">
                            @foreach ($pieces as $document)
                                <x-document-card
                                    :document="$document"
                                    :view-url="route('dgf.activities.documents.view', [$activity, $document])"
                                    :download-url="route('dgf.activities.documents.download', [$activity, $document])"
                                />
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            <aside class="ad-aside">
                {{-- Suivi ---------------------------------------------------------- --}}
                <section class="ad-card" aria-labelledby="adSuiviTitle">
                    <h2 id="adSuiviTitle" class="ad-card-title">Suivi</h2>
                    <ol class="ad-timeline">
                        @foreach ($suivi as $point)
                            <li class="is-{{ $point['etat'] }}" @if ($point['etat'] === 'courant' || $point['etat'] === 'erreur') aria-current="step" @endif>
                                <span class="ad-dot" aria-hidden="true"></span>
                                <strong>{{ $point['libelle'] }}</strong>
                                <span>{{ $point['detail'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                    @if ($miseAJour)
                        <p class="ad-note">{{ $miseAJour }}, après sa soumission.</p>
                    @endif
                </section>

                {{-- Actions -------------------------------------------------------- --}}
                <section class="ad-card" aria-labelledby="adActionsTitle">
                    <h2 id="adActionsTitle" class="ad-card-title">Actions</h2>
                    <div class="ad-actions">
                        @if ($aDecider)
                            @unless ($sansPiece)
                                <form method="POST" action="{{ route('dgf.activities.validate', $activity) }}" data-confirm="Valider cette activité ? Elle sera versée au rapport d’activité {{ $activity->year }} de {{ $federation }}.">
                                    @csrf
                                    <button type="submit" class="btn primary"><x-ui-icon name="check" /> Valider l’activité</button>
                                </form>
                            @endunless
                            <button type="button" class="btn danger js-reject-reason" data-action="{{ route('dgf.activities.reject', $activity) }}">Rejeter avec un motif</button>
                        @endif
                        @if ($suivante)
                            <a href="{{ route('dgf.activities.show', $suivante) }}" class="btn">Activité suivante à examiner <x-ui-icon name="arrow" /></a>
                        @endif
                        <a href="{{ route('dgf.activities.index') }}" class="btn"><x-ui-icon name="list" /> Retour à la file de contrôle</a>
                    </div>
                    <p class="ad-note">
                        @if ($enAttente)
                            {{ $enAttente }} {{ $enAttente > 1 ? 'activités attendent' : 'activité attend' }} une décision (avec justificatif).
                        @else
                            Aucune autre activité n’attend de décision.
                        @endif
                        @if ($aDecider)
                            Un rejet renvoie l’activité à la fédération avec votre motif.
                        @endif
                    </p>
                </section>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/vendor/pdfjs/pdf.min.js') }}"></script>
    <script>pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('js/vendor/pdfjs/pdf.worker.min.js') }}";</script>
    <script src="{{ asset('js/pdf-thumbnails.js') }}"></script>
@endpush
