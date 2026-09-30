@extends('layouts.dashboard', ['active' => 'activities'])

@section('title', $activity->designation ?? 'Activité')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/activity-detail.css') }}">
@endpush

@php
    $statut = $activity->status;
    $pieces = $activity->documents;
    $sansPiece = $pieces->isEmpty();
    // Une année close (hors ouverture exceptionnelle) se consulte sans modification.
    $modifiable = $statut !== 'valide' && $exerciceOuvert;
    $soumettable = $exerciceOuvert && in_array($statut, ['brouillon', 'rejete'], true);
    $axeParts = $activity->axeLabelParts();
    $jour = fn ($date) => $date?->locale('fr')->translatedFormat('j F Y');
    $fcfa = fn ($montant) => number_format((float) $montant, 0, ',', ' ').' FCFA';
    $rapport = $activity->budgetLine?->report;

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

    // Prochaine étape : ce que la fédération doit faire, ou rien.
    $etape = match (true) {
        $statut === 'valide' => ['ton' => 'success', 'titre' => 'Activité validée par la DGF',
            'texte' => 'Validée le '.$jour($activity->validated_at).'. Elle a été versée automatiquement à votre rapport d’activité '.$activity->year.'. Aucune action n’est requise.'],
        $statut === 'rejete' => ['ton' => 'danger', 'titre' => 'Corrections demandées par la DGF',
            'texte' => $activity->rejection_reason ? '« '.$activity->rejection_reason.' »' : 'Corrigez l’activité puis soumettez-la de nouveau.'],
        $statut === 'soumis' && $sansPiece => ['ton' => 'warning', 'titre' => 'Ajoutez un justificatif',
            'texte' => 'L’activité est soumise, mais la DGF ne peut pas l’examiner sans au moins une pièce justificative.'],
        $statut === 'soumis' => ['ton' => 'info', 'titre' => 'En cours d’examen par la DGF',
            'texte' => 'Soumise le '.$jour($activity->submitted_at).'. Aucune action n’est requise ; vous serez fixé à la validation. Vous pouvez encore ajouter des pièces.'],
        $sansPiece => ['ton' => 'neutral', 'titre' => 'Brouillon : ajoutez vos justificatifs puis soumettez',
            'texte' => 'La DGF ne pourra valider l’activité qu’avec au moins une pièce justificative (PDF, photo…).'],
        default => ['ton' => 'primary', 'titre' => 'Prête à être soumise',
            'texte' => 'Vos justificatifs sont joints. Soumettez l’activité pour qu’elle soit examinée par la DGF.'],
    };

    if (! $exerciceOuvert && $statut !== 'valide') {
        $etape = ['ton' => 'neutral', 'titre' => 'Exercice '.$activity->year.' clos',
            'texte' => 'Cette activité ne peut plus être modifiée, complétée ni soumise. Si nécessaire, l’administration peut rouvrir exceptionnellement cet exercice.'];
    }

    // Suivi : Brouillon → Soumise → Examen DGF → Validée et versée au rapport.
    $suivi = [
        ['libelle' => 'Brouillon enregistré', 'detail' => $jour($activity->created_at), 'etat' => 'fait'],
        ['libelle' => 'Soumise à la DGF', 'detail' => $activity->submitted_at ? $jour($activity->submitted_at) : 'À faire', 'etat' => $statut === 'brouillon' ? 'courant' : 'fait'],
        ['libelle' => 'Examen par la DGF',
            'detail' => match ($statut) { 'soumis' => $sansPiece ? 'En attente d’un justificatif' : 'En cours', 'rejete' => 'Corrections demandées', 'valide' => 'Terminé', default => 'À venir' },
            'etat' => match ($statut) { 'soumis' => 'courant', 'rejete' => 'erreur', 'valide' => 'fait', default => 'a-venir' }],
        ['libelle' => 'Validée et versée au rapport '.$activity->year, 'detail' => $statut === 'valide' ? $jour($activity->validated_at) : 'À venir', 'etat' => $statut === 'valide' ? 'fait' : 'a-venir'],
    ];
@endphp

@section('content')
    <div class="ad-page">
        <nav class="breadcrumb" aria-label="Fil d’Ariane"><a href="{{ route('activities.index') }}">← Activités réalisées</a></nav>

        <header class="ad-heading">
            <div>
                <p class="ad-eyebrow">Activité réalisée · {{ $activity->year }}</p>
                <h1 class="cap-first">{{ $activity->designation }}</h1>
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

        {{-- Prochaine étape ------------------------------------------------ --}}
        <section class="ad-next is-{{ $etape['ton'] }}" aria-labelledby="adNextTitle">
            <span class="ad-next-icon" aria-hidden="true">
                @switch($etape['ton'])
                    @case('success')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.2 4.2L19 7"/></svg>@break
                    @case('danger')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 7v6M12 17h.01"/><circle cx="12" cy="12" r="9.5"/></svg>@break
                    @case('warning')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2.5 20h19z"/><path d="M12 10v4M12 17h.01"/></svg>@break
                    @case('info')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>@break
                    @default<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                @endswitch
            </span>
            <div class="ad-next-body">
                <p class="ad-next-label">{{ match ($etape['ton']) { 'success', 'info' => 'Statut', 'warning', 'danger' => 'Action requise', default => 'Prochaine étape' } }}</p>
                <h2 id="adNextTitle">{{ $etape['titre'] }}</h2>
                <p>{{ $etape['texte'] }}</p>
            </div>
            <div class="ad-next-actions">
                @if (! $exerciceOuvert && $statut !== 'valide')
                    {{-- Exercice clos : aucune action possible. --}}
                @elseif ($statut === 'valide')
                    @if ($rapport)
                        <a href="{{ route('activity-form.show', $rapport) }}" class="btn info"><x-ui-icon name="file" /> Voir le rapport d’activité</a>
                    @endif
                @elseif ($statut === 'rejete')
                    <a href="{{ route('activities.edit', $activity) }}" class="btn primary"><x-ui-icon name="edit" /> Corriger l’activité</a>
                @elseif ($sansPiece)
                    <a href="#adPieces" class="btn primary" data-ad-focus-upload><x-ui-icon name="plus" /> Ajouter un justificatif</a>
                @elseif ($statut === 'brouillon')
                    <form method="POST" action="{{ route('activities.submit', $activity) }}" data-confirm="Soumettre cette activité à la DGF pour vérification ?">
                        @csrf
                        <button type="submit" class="btn primary">Soumettre à la DGF</button>
                    </form>
                @endif
            </div>
        </section>

        <div class="ad-layout">
            <div class="ad-main">
                {{-- Détails ---------------------------------------------------- --}}
                <section class="ad-card" aria-labelledby="adDetailsTitle">
                    <h2 id="adDetailsTitle" class="ad-card-title">Détails de l’activité</h2>
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
                            <dt>Observations</dt>
                            <dd>{!! filled($activity->observations) ? e($activity->observations) : '<span class="ad-muted">Aucune</span>' !!}</dd>
                        </div>
                    </dl>
                </section>

                {{-- Pièces justificatives ------------------------------------ --}}
                <section class="ad-card" id="adPieces" aria-labelledby="adPiecesTitle">
                    <div class="ad-card-head">
                        <h2 id="adPiecesTitle" class="ad-card-title">Pièces justificatives <span class="ad-count">{{ $pieces->count() }}</span></h2>
                        @if (! $sansPiece)
                            <p class="ad-muted">Cliquez sur « Consulter » pour ouvrir une pièce.</p>
                        @endif
                    </div>

                    @unless ($sansPiece)
                        <div class="documents-grid">
                            @foreach ($pieces as $document)
                                <x-document-card
                                    :document="$document"
                                    :view-url="route('activities.documents.view', [$activity, $document])"
                                    :download-url="route('activities.documents.download', [$activity, $document])"
                                />
                            @endforeach
                        </div>
                    @endunless

                    @if ($modifiable)
                        <form method="POST" action="{{ route('activities.documents.store', $activity) }}" enctype="multipart/form-data" class="creation-ui ad-upload js-validate" novalidate>
                            @csrf
                            <label for="showPiecesInput" @class(['dropzone', 'is-required' => $sansPiece])>
                                <svg class="dropzone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1"/><path d="M12 3v12"/><path d="M7 8l5-5 5 5"/>
                                </svg>
                                <div class="dropzone-text"><strong>{{ $sansPiece ? 'Ajoutez votre premier justificatif' : 'Ajouter d’autres pièces' }}</strong> : cliquez ou glissez-déposez</div>
                                <div class="dropzone-hint">PDF, JPG ou PNG · 5 Mo maximum par fichier · plusieurs fichiers possibles</div>
                                <input type="file" name="pieces[]" id="showPiecesInput" class="js-file-input" data-preview-target="showPiecesPreview" multiple accept=".pdf,.jpg,.jpeg,.png" required>
                            </label>
                            <div class="file-preview-list" id="showPiecesPreview"></div>
                            <div class="ad-upload-actions" data-ad-upload-actions>
                                <button type="submit" class="btn primary" data-ad-upload-submit><x-ui-icon name="download" class="ad-icon-up" /> <span>Envoyer les fichiers</span></button>
                            </div>
                        </form>
                        @error('pieces.*')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    @endif
                </section>
            </div>

            <aside class="ad-aside">
                {{-- Suivi ------------------------------------------------------ --}}
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
                </section>

                {{-- Actions ---------------------------------------------------- --}}
                <section class="ad-card" aria-labelledby="adActionsTitle">
                    <h2 id="adActionsTitle" class="ad-card-title">Actions</h2>
                    @if ($modifiable)
                        <div class="ad-actions">
                            @if ($soumettable)
                                <form method="POST" action="{{ route('activities.submit', $activity) }}" data-confirm="{{ $sansPiece ? 'Soumettre sans justificatif ? La DGF ne pourra pas valider l’activité tant qu’une pièce n’aura pas été ajoutée.' : 'Soumettre cette activité à la DGF pour vérification ?' }}">
                                    @csrf
                                    <button type="submit" class="btn primary">{{ $statut === 'rejete' ? 'Soumettre de nouveau' : 'Soumettre à la DGF' }}</button>
                                </form>
                            @endif
                            <a href="{{ route('activities.edit', $activity) }}" class="btn"><x-ui-icon name="edit" /> Modifier l’activité</a>
                            <a href="#adPieces" class="btn info" data-ad-focus-upload><x-ui-icon name="plus" /> Ajouter des pièces</a>
                            @if ($statut === 'brouillon')
                                <form method="POST" action="{{ route('activities.destroy', $activity) }}" data-confirm="Supprimer définitivement cette activité et ses pièces justificatives ?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn danger"><x-ui-icon name="trash" /> Supprimer l’activité</button>
                                </form>
                            @endif
                        </div>
                        @if ($statut === 'soumis')
                            <p class="ad-note">Modifier une activité soumise la laisse dans la file de la DGF avec vos corrections.</p>
                        @elseif ($statut === 'rejete')
                            <p class="ad-note">Une activité rejetée ne peut plus être supprimée : corrigez-la puis soumettez-la de nouveau.</p>
                        @endif
                    @elseif ($statut === 'valide')
                        <p class="ad-muted">L’activité est validée : elle ne peut plus être modifiée ni supprimée.</p>
                    @else
                        <p class="ad-muted">L’exercice {{ $activity->year }} est clos : l’activité se consulte sans modification.</p>
                        @if ($statut === 'brouillon')
                            <form method="POST" action="{{ route('activities.destroy', $activity) }}" data-confirm="Supprimer définitivement ce brouillon et ses pièces justificatives ?" class="ad-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn danger ad-block"><x-ui-icon name="trash" /> Supprimer le brouillon</button>
                            </form>
                        @endif
                    @endif
                </section>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/vendor/pdfjs/pdf.min.js') }}"></script>
    <script>pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('js/vendor/pdfjs/pdf.worker.min.js') }}";</script>
    <script src="{{ asset('js/pdf-thumbnails.js') }}"></script>
    <script>
        (function () {
            'use strict';
            // « Ajouter un justificatif » : amène la zone de dépôt et ouvre le choix de fichiers.
            document.querySelectorAll('[data-ad-focus-upload]').forEach(lien => {
                lien.addEventListener('click', event => {
                    const zone = document.querySelector('.ad-upload .dropzone');
                    if (!zone) return;
                    event.preventDefault();
                    zone.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    zone.classList.add('is-highlighted');
                    setTimeout(() => zone.classList.remove('is-highlighted'), 1600);
                    document.getElementById('showPiecesInput')?.focus({ preventScroll: true });
                });
            });

            // Le bouton d'envoi n'apparaît qu'avec des fichiers sélectionnés et en donne le nombre.
            const apercu = document.getElementById('showPiecesPreview');
            const actions = document.querySelector('[data-ad-upload-actions]');
            const bouton = document.querySelector('[data-ad-upload-submit] span');
            if (!apercu || !actions) return;
            const miseAJour = () => {
                const n = apercu.children.length;
                actions.hidden = n === 0;
                bouton.textContent = n > 1 ? 'Envoyer les ' + n + ' fichiers' : 'Envoyer le fichier';
                if (n && window.QuitusMotion) window.QuitusMotion.enter(actions);
            };
            new MutationObserver(miseAJour).observe(apercu, { childList: true });
            miseAJour();
        })();
    </script>
@endpush
