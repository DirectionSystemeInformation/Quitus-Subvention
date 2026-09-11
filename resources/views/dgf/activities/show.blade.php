@extends('layouts.dashboard', ['active' => 'dgf-activities'])

@section('title', $activity->designation ?? 'Activité')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="{{ route('dgf.activities.index') }}">Activités à valider</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">{{ $activity->designation }}</span>
        </nav>
        <h1 style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <span class="cap-first">{{ $activity->designation }}</span>
            <x-status-badge :status="$activity->status" />
        </h1>
        <p style="margin-top: 8px; color: var(--text-secondary); font-size: 14px;">{{ $activity->user->federation_name }}</p>
        @php $axeParts = $activity->axeLabelParts(); @endphp
        <p style="margin-top: 4px; color: var(--text-muted); font-size: 13px;">
            {{ $axeParts['prefix'] }}@if ($axeParts['description']) — {{ $axeParts['description'] }}@endif
        </p>
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top: 8px;">
            @if ($activity->axeNumber())
                <span class="info-badge">Axe {{ $activity->axeNumber() }}</span>
            @endif
            <span class="info-badge">{{ $activity->sous_axe_label }}</span>
            <span class="info-badge">{{ $activity->year }}</span>
        </div>
        @if ($activity->status === 'rejete' && $activity->rejection_reason)
            <p style="color: var(--color-danger, #E5484D); margin-top: 8px;">Motif du rejet : {{ $activity->rejection_reason }}</p>
        @endif
        @if ($activity->status === 'brouillon')
            <p class="strength-text" style="margin-top: 8px;">Cette activité est encore en brouillon — la fédération ne l'a pas encore soumise.</p>
        @endif
    </div>

    @if ($activity->status === 'valide' && $activity->validator)
        <div class="validation-block">
            <span>✓ Validée le {{ $activity->validated_at?->locale('fr')->translatedFormat('d F Y') }} à {{ $activity->validated_at?->format('H:i') }}</span>
            <span class="validation-block-sep">&middot;</span>
            <span>Validée par : {{ $activity->validator->name }}</span>
        </div>
    @endif

    <div class="card card-compact" style="margin-bottom: 24px;">
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
            <div class="details-grid-full">
                <dt>Observations</dt>
                <dd>{{ $activity->observations ?? 'Aucune' }}</dd>
            </div>
        </dl>
    </div>

    <div class="card card-compact" style="margin-bottom: 24px;">
        <div class="card-header">
            <h2 class="card-title">Pièces justificatives ({{ $activity->documents->count() }})</h2>
        </div>
        @if ($activity->documents->isEmpty())
            <x-empty-state icon="inbox" title="Aucune pièce jointe." :compact="true" />
        @else
            <div class="documents-grid">
                @foreach ($activity->documents as $document)
                    <x-document-card
                        :document="$document"
                        :view-url="route('dgf.activities.documents.view', [$activity, $document])"
                        :download-url="route('dgf.activities.documents.download', [$activity, $document])"
                    />
                @endforeach
            </div>
        @endif
    </div>

    @if ($activity->status === 'soumis')
        @if ($activity->documents->isEmpty())
            <p class="strength-text" style="color: var(--color-danger, #E5484D); margin-bottom: 16px;">Aucune pièce justificative jointe — la validation est impossible tant que la fédération n'en a pas ajouté une. Vous pouvez rejeter l'activité pour le lui demander.</p>
        @endif
        <div class="btn-group">
            @if ($activity->documents->isNotEmpty())
                <form method="POST" action="{{ route('dgf.activities.validate', $activity) }}"
                    data-confirm="Valider cette activité ? Elle sera versée au rapport d'activité de la fédération.">
                    @csrf
                    <button type="submit" class="btn primary">Valider</button>
                </form>
            @endif
            <button type="button" class="btn danger js-reject-reason" data-action="{{ route('dgf.activities.reject', $activity) }}">Rejeter</button>
        </div>
    @endif

    @push('scripts')
        <script src="{{ asset('js/vendor/pdfjs/pdf.min.js') }}"></script>
        <script>pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('js/vendor/pdfjs/pdf.worker.min.js') }}";</script>
        <script src="{{ asset('js/pdf-thumbnails.js') }}"></script>
    @endpush
@endsection
