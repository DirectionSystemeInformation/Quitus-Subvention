@extends('layouts.dashboard', ['active' => 'activities'])

@section('title', $activity->designation ?? 'Activité')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="{{ route('activities.index') }}">Activités</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">{{ $activity->designation }}</span>
        </nav>
        <h1 style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            {{ $activity->designation }}
            <x-status-badge :status="$activity->status" />
        </h1>
        <p>{{ $activity->axe_label }} — {{ $activity->sous_axe_label }} — Année {{ $activity->year }}</p>
        @if ($activity->status === 'rejete' && $activity->rejection_reason)
            <p style="color: var(--color-danger, #E5484D); margin-top: 8px;">Motif du rejet : {{ $activity->rejection_reason }}</p>
        @endif
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h2 class="card-title">Détails</h2>
        </div>
        <table class="market-table">
            <tbody>
                <tr><td style="width:220px;">Montant</td><td>{{ $activity->montant !== null ? number_format((float) $activity->montant, 0, ',', ' ').' FCFA' : '—' }}</td></tr>
                <tr><td>Contribution des partenaires</td><td>{{ $activity->contribution_partenaires ?? '—' }}</td></tr>
                <tr><td>Date de réalisation</td><td>{{ optional($activity->date)->format('d/m/Y') ?? '—' }}</td></tr>
                <tr><td>Observations</td><td>{{ $activity->observations ?? '—' }}</td></tr>
            </tbody>
        </table>
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
                <label for="showPiecesInput" class="dropzone">
                    <svg class="dropzone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1"/>
                        <path d="M12 3v12"/>
                        <path d="M7 8l5-5 5 5"/>
                    </svg>
                    <div class="dropzone-text"><strong>Cliquez pour parcourir</strong> ou glissez-déposez vos fichiers ici</div>
                    <div class="dropzone-hint">PDF, JPG ou PNG — 5 Mo max par fichier</div>
                    <input type="file" name="pieces[]" id="showPiecesInput" class="js-file-input" data-preview-target="showPiecesPreview" multiple accept=".pdf,.jpg,.jpeg,.png" required>
                </label>
                <div class="file-preview-list" id="showPiecesPreview"></div>
                <button type="submit" class="btn" style="margin-top: 12px;">Joindre une pièce</button>
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
        @if ($activity->status !== 'valide')
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
