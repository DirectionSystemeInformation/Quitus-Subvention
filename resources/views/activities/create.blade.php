@extends('layouts.dashboard', ['active' => 'activities'])

@section('title', $activity->exists ? "Modifier l'activité" : 'Nouvelle activité')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@php
    $selectedSousAxe = old('sous_axe_code', $activity->sous_axe_code);
    $selectedAxe = collect($axes)->first(
        fn ($axe) => collect($axe['sous_axes'])->pluck('code')->contains($selectedSousAxe)
    )['code'] ?? null;

    $canSubmit = ! $activity->exists || in_array($activity->status, ['brouillon', 'rejete'], true);
    $primaryLabel = (! $activity->exists || $activity->status === 'brouillon')
        ? 'Enregistrer comme brouillon'
        : 'Enregistrer les modifications';
@endphp

@section('content')
    <div class="page-header">
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="{{ route('activities.index') }}">Activités</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">{{ $activity->exists ? "Modifier l'activité" : 'Nouvelle activité' }}</span>
        </nav>
        <h1>{{ $activity->exists ? "Modifier l'activité" : 'Nouvelle activité' }}</h1>
        <p>Remplissez le formulaire en suivant le même canevas que le rapport d'activité, puis joignez vos pièces justificatives.</p>
    </div>

    <div class="card">
        <p class="form-required-note"><span class="required-mark">*</span> Champs obligatoires</p>

        <form method="POST" action="{{ $activity->exists ? route('activities.update', $activity) : route('activities.store') }}" enctype="multipart/form-data" class="form-grid form-compact js-validate" novalidate>
            @csrf
            @if ($activity->exists)
                @method('PUT')
            @endif
            <input type="hidden" name="year" value="{{ old('year', $year) }}">

            <div class="form-group full-width form-section">
                <h2 class="form-section-title">1 — Identification de l'activité</h2>
            </div>

            <div class="form-group">
                <label class="form-label">Année d'exercice</label>
                <span class="form-static-value">{{ old('year', $year) }}</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="axeSelect">Axe <span class="required-mark">*</span></label>
                <select id="axeSelect" class="form-select" required>
                    <option value="">— Sélectionner —</option>
                    @foreach ($axes as $axe)
                        <option value="{{ $axe['code'] }}" {{ $selectedAxe === $axe['code'] ? 'selected' : '' }}>{{ $axe['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="sousAxeSelect">Sous-axe <span class="required-mark">*</span></label>
                <select name="sous_axe_code" id="sousAxeSelect" class="form-select" required @if (! $selectedAxe) disabled @endif>
                    @if ($selectedAxe)
                        @foreach (collect($axes)->firstWhere('code', $selectedAxe)['sous_axes'] as $sousAxe)
                            <option value="{{ $sousAxe['code'] }}" {{ $selectedSousAxe === $sousAxe['code'] ? 'selected' : '' }}>{{ $sousAxe['label'] }}</option>
                        @endforeach
                    @else
                        <option value="">— Sélectionnez d'abord un axe —</option>
                    @endif
                </select>
            </div>

            <div class="form-group full-width">
                <label class="form-label" for="designationInput">Désignation de l'activité <span class="required-mark">*</span></label>
                <input type="text" id="designationInput" name="designation" class="form-input" maxlength="255" data-char-counter="designationCounter" value="{{ old('designation', $activity->designation) }}" required>
                <div class="form-field-hint" style="justify-content: flex-end;">
                    <span id="designationCounter"></span>
                </div>
            </div>

            <div class="form-group full-width form-section">
                <h2 class="form-section-title">2 — Informations de réalisation</h2>
            </div>

            <div class="form-group">
                <label class="form-label">Montant (FCFA)</label>
                <div class="input-money">
                    <input type="text" inputmode="numeric" name="montant" class="form-input js-money-input" value="{{ old('montant', $activity->montant) }}">
                    <span class="input-money-suffix">FCFA</span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Contribution des partenaires (FCFA)</label>
                <div class="input-money">
                    <input type="text" inputmode="numeric" name="contribution_partenaires" class="form-input js-money-input" value="{{ old('contribution_partenaires', $activity->contribution_partenaires) }}">
                    <span class="input-money-suffix">FCFA</span>
                </div>
            </div>

            <div class="form-group full-width">
                <label class="form-label">Période de réalisation</label>
                <div class="date-range-group">
                    <div class="period-field">
                        <span class="period-field-label">Date de début</span>
                        <input type="date" name="date_debut" class="form-input" aria-label="Date de début" value="{{ old('date_debut', optional($activity->date_debut)->format('Y-m-d')) }}">
                    </div>
                    <span class="date-range-sep">au</span>
                    <div class="period-field">
                        <span class="period-field-label">Date de fin</span>
                        <input type="date" name="date_fin" class="form-input" aria-label="Date de fin" value="{{ old('date_fin', optional($activity->date_fin)->format('Y-m-d')) }}">
                    </div>
                </div>
            </div>

            <div class="form-group full-width">
                <label class="form-label" for="observationsInput">Observations</label>
                <textarea id="observationsInput" name="observations" class="form-textarea" rows="3" maxlength="255">{{ old('observations', $activity->observations) }}</textarea>
            </div>

            <div class="form-group full-width form-section">
                <h2 class="form-section-title">3 — Pièces justificatives</h2>
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
            </div>

            @if ($activity->exists && $activity->documents->isNotEmpty())
                <div class="form-group full-width">
                    <label class="form-label">Pièces déjà jointes</label>
                    <div class="documents-grid">
                        @foreach ($activity->documents as $document)
                            <x-document-card
                                :document="$document"
                                :view-url="route('activities.documents.view', [$activity, $document])"
                                :download-url="route('activities.documents.download', [$activity, $document])"
                            />
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="form-group full-width" style="margin-bottom: 0;">
                @if ($canSubmit)
                    <p class="strength-text" style="margin-bottom: 12px;">Le brouillon reste modifiable à tout moment. Après soumission, les informations de l'activité sont verrouillées, mais vous pourrez encore ajouter des pièces justificatives — au moins une est nécessaire pour que la DGF puisse valider.</p>
                @endif
                <div class="btn-group">
                    <button type="submit" name="intent" value="draft" class="btn">{{ $primaryLabel }}</button>
                    @if ($canSubmit)
                        <button type="submit" name="intent" value="submit" class="btn primary">Soumettre l'activité</button>
                    @endif
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script id="axesData" type="application/json">{!! json_encode($axes) !!}</script>
        <script>
            (function () {
                const axesData = JSON.parse(document.getElementById('axesData').textContent);
                const axeSelect = document.getElementById('axeSelect');
                const sousAxeSelect = document.getElementById('sousAxeSelect');

                function populateSousAxes(axeCode, preselect) {
                    const axe = axesData.find(function (a) { return a.code === axeCode; });
                    sousAxeSelect.innerHTML = '';

                    if (!axe) {
                        sousAxeSelect.disabled = true;
                        const opt = document.createElement('option');
                        opt.value = '';
                        opt.textContent = "— Sélectionnez d'abord un axe —";
                        sousAxeSelect.appendChild(opt);
                        return;
                    }

                    sousAxeSelect.disabled = false;
                    axe.sous_axes.forEach(function (sousAxe) {
                        const opt = document.createElement('option');
                        opt.value = sousAxe.code;
                        opt.textContent = sousAxe.label;
                        if (preselect && sousAxe.code === preselect) opt.selected = true;
                        sousAxeSelect.appendChild(opt);
                    });
                }

                axeSelect.addEventListener('change', function () {
                    populateSousAxes(axeSelect.value, null);
                });
            })();
        </script>
    @endpush
@endsection
