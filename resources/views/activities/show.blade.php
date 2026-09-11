@extends('layouts.dashboard', ['active' => 'activities'])

@section('title', $activity->designation ?? 'Activité')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="{{ route('activities.index') }}">&larr; Retour aux activités</a>
        </nav>
        <h1 style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <span class="cap-first">{{ $activity->designation }}</span>
            <x-status-badge :status="$activity->status" />
        </h1>
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top: 8px;">
            @if ($activity->axeNumber())
                <span class="info-badge">Axe {{ $activity->axeNumber() }}</span>
            @endif
            <span class="info-badge">{{ $activity->year }}</span>
        </div>
        <p style="margin-top: 10px;">
            <span style="color: var(--text-muted); font-size: 13px; display:block;">{{ $activity->axe_label }}</span>
            {{ $activity->sous_axe_label }}
        </p>
        @if ($activity->status === 'rejete' && $activity->rejection_reason)
            <p style="color: var(--color-danger, #E5484D); margin-top: 8px;">Motif du rejet : {{ $activity->rejection_reason }}</p>
        @endif
    </div>

    @if ($activity->status === 'soumis' && $activity->documents->isEmpty())
        <div class="notice notice-warning">
            <h2>Justificatif requis</h2>
            <p>Cette activité a été soumise, mais elle ne pourra être examinée par la DGF qu'après l'ajout d'au moins une pièce justificative.</p>
        </div>
    @endif

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h2 class="card-title">Détails</h2>
        </div>
        <dl class="details-grid">
            <div>
                <dt>Montant</dt>
                <dd>{{ $activity->montant !== null ? number_format((float) $activity->montant, 0, ',', ' ').' FCFA' : 'Non renseigné' }}</dd>
            </div>
            <div>
                <dt>Contribution des partenaires</dt>
                <dd>
                    @if ($activity->contribution_partenaires === null || $activity->contribution_partenaires === '')
                        Non renseignée
                    @elseif (is_numeric($activity->contribution_partenaires))
                        {{ number_format((float) $activity->contribution_partenaires, 0, ',', ' ') }} FCFA
                    @else
                        {{ $activity->contribution_partenaires }}
                    @endif
                </dd>
            </div>
            <div>
                <dt>Date de réalisation</dt>
                <dd>{{ $activity->date_debut?->locale('fr')->translatedFormat('d F Y') ?? $activity->dateRangeLabel() ?? 'Non renseignée' }}</dd>
            </div>
            <div>
                <dt>Statut</dt>
                <dd><x-status-badge :status="$activity->status" /></dd>
            </div>
            <div class="details-grid-full">
                <dt>Observations</dt>
                <dd>{{ $activity->observations ?? 'Aucune' }}</dd>
            </div>
        </dl>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h2 class="card-title">Pièces justificatives</h2>
        </div>
        @if ($activity->documents->isEmpty())
            <x-empty-state icon="inbox" title="Aucune pièce jointe." :compact="true" />
        @else
            <ul style="margin: 0 0 16px; padding-left: 20px;">
                @foreach ($activity->documents as $document)
                    <li><a href="{{ route('activities.documents.download', [$activity, $document]) }}">{{ $document->original_filename }}</a></li>
                @endforeach
            </ul>
        @endif

        @if ($activity->status !== 'valide')
            <form method="POST" action="{{ route('activities.documents.store', $activity) }}" enctype="multipart/form-data" class="js-validate" novalidate>
                @csrf
                <label for="showPiecesInput" class="dropzone is-compact">
                    <svg class="dropzone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1"/>
                        <path d="M12 3v12"/>
                        <path d="M7 8l5-5 5 5"/>
                    </svg>
                    <div class="dropzone-text"><strong>Ajouter un justificatif</strong> — cliquez ou glissez-déposez</div>
                    <div class="dropzone-hint">PDF, JPG ou PNG — 5 Mo max par fichier</div>
                    <input type="file" name="pieces[]" id="showPiecesInput" class="js-file-input" data-preview-target="showPiecesPreview" multiple accept=".pdf,.jpg,.jpeg,.png" required>
                </label>
                <div class="file-preview-list" id="showPiecesPreview"></div>
                <button type="submit" class="btn primary" style="margin-top: 12px;">Envoyer le justificatif</button>
            </form>
            @error('pieces.*')
                <p class="strength-text" style="color: var(--color-danger, #E5484D); margin-top: 8px;">{{ $message }}</p>
            @enderror
        @endif
    </div>

    @if (in_array($activity->status, ['brouillon', 'rejete'], true) && $activity->documents->isEmpty())
        <p class="strength-text" style="margin-bottom: 16px;">Vous pouvez soumettre sans pièce jointe, mais la DGF ne pourra pas valider l'activité tant qu'une pièce justificative n'aura pas été ajoutée.</p>
    @endif

    <div class="btn-group">
        @if (in_array($activity->status, ['brouillon', 'rejete'], true))
            <a href="{{ route('activities.edit', $activity) }}" class="btn">Modifier</a>
        @endif
        @if (in_array($activity->status, ['brouillon', 'rejete'], true))
            <form method="POST" action="{{ route('activities.submit', $activity) }}">
                @csrf
                <button type="submit" class="btn primary">Soumettre pour vérification</button>
            </form>
        @endif
        @if ($activity->status === 'brouillon')
            <form method="POST" action="{{ route('activities.destroy', $activity) }}" data-confirm="Supprimer cette activité ?">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Supprimer</button>
            </form>
        @endif
    </div>
@endsection
