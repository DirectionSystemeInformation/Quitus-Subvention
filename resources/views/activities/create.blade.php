@extends('layouts.dashboard', ['active' => 'activities'])

@section('title', $activity->exists ? "Modifier l'activité" : 'Nouvelle activité')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="{{ route('activities.index') }}">Gestion des activités</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">{{ $activity->exists ? "Modifier l'activité" : 'Nouvelle activité' }}</span>
        </nav>
        <h1>{{ $activity->exists ? "Modifier l'activité" : 'Nouvelle activité' }}</h1>
        <p>Remplissez le formulaire en suivant le même canevas que le rapport d'activité, puis joignez vos pièces justificatives.</p>
    </div>

    <div class="card">
        <form method="POST" action="{{ $activity->exists ? route('activities.update', $activity) : route('activities.store') }}" enctype="multipart/form-data" class="form-grid js-validate" novalidate>
            @csrf
            @if ($activity->exists)
                @method('PUT')
            @endif

            <div class="form-group">
                <label class="form-label">Année</label>
                <input type="number" name="year" class="form-input" min="2000" max="2100" value="{{ old('year', $year) }}" required>
                @error('year')
                    <p class="strength-text" style="color: var(--color-danger, #E5484D); margin-top: 6px;">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Axe / Sous-axe</label>
                <select name="sous_axe_code" class="form-select" required>
                    <option value="">— Sélectionner —</option>
                    @foreach ($axes as $axe)
                        <optgroup label="{{ $axe['label'] }}">
                            @foreach ($axe['sous_axes'] as $sousAxe)
                                <option value="{{ $sousAxe['code'] }}" {{ old('sous_axe_code', $activity->sous_axe_code) === $sousAxe['code'] ? 'selected' : '' }}>
                                    {{ $sousAxe['label'] }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('sous_axe_code')
                    <p class="strength-text" style="color: var(--color-danger, #E5484D); margin-top: 6px;">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group full-width">
                <label class="form-label">Désignation de l'activité</label>
                <input type="text" name="designation" class="form-input" value="{{ old('designation', $activity->designation) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Montant (FCFA)</label>
                <input type="number" step="0.01" min="0" name="montant" class="form-input" value="{{ old('montant', $activity->montant) }}">
            </div>

            <div class="form-group">
                <label class="form-label">Date de réalisation</label>
                <input type="date" name="date" class="form-input" value="{{ old('date', optional($activity->date)->format('Y-m-d')) }}">
            </div>

            <div class="form-group full-width">
                <label class="form-label">Contribution des partenaires</label>
                <input type="text" name="contribution_partenaires" class="form-input" value="{{ old('contribution_partenaires', $activity->contribution_partenaires) }}">
            </div>

            <div class="form-group full-width">
                <label class="form-label">Observations</label>
                <input type="text" name="observations" class="form-input" value="{{ old('observations', $activity->observations) }}">
            </div>

            <div class="form-group full-width">
                <label class="form-label">Pièces justificatives {{ $activity->exists ? '(ajouter de nouvelles pièces)' : '' }}</label>
                <label for="piecesInput" class="dropzone">
                    <svg class="dropzone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1"/>
                        <path d="M12 3v12"/>
                        <path d="M7 8l5-5 5 5"/>
                    </svg>
                    <div class="dropzone-text"><strong>Cliquez pour parcourir</strong> ou glissez-déposez vos fichiers ici</div>
                    <div class="dropzone-hint">PDF, JPG ou PNG — 5 Mo max par fichier</div>
                    <input type="file" name="pieces[]" id="piecesInput" class="js-file-input" data-preview-target="piecesPreview" multiple accept=".pdf,.jpg,.jpeg,.png">
                </label>
                <div class="file-preview-list" id="piecesPreview"></div>
                <p class="strength-text" style="margin-top: 6px;">Enregistrez votre brouillon, puis soumettez l'activité depuis sa fiche après avoir ajouté au moins une pièce justificative.</p>
                @error('pieces.*')
                    <p class="strength-text" style="color: var(--color-danger, #E5484D); margin-top: 6px;">{{ $message }}</p>
                @enderror
            </div>

            @if ($activity->exists && $activity->documents->isNotEmpty())
                <div class="form-group full-width">
                    <label class="form-label">Pièces déjà jointes</label>
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($activity->documents as $document)
                            <li><a href="{{ route('activities.documents.download', [$activity, $document]) }}" class="file-link">{{ $document->original_filename }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="form-group full-width">
                <button type="submit" class="btn primary">{{ $activity->exists ? 'Enregistrer' : 'Enregistrer en brouillon' }}</button>
            </div>
        </form>
    </div>
@endsection
