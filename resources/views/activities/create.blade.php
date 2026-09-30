@extends('layouts.dashboard', ['active' => 'activities'])
@section('title', $activity->exists ? "Modifier l'activité" : 'Nouvelle activité')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush
@php
    $selectedSousAxe = old('sous_axe_code', $activity->sous_axe_code);
    $selectedAxe = collect($axes)->first(fn ($axe) => collect($axe['sous_axes'])->pluck('code')->contains($selectedSousAxe))['code'] ?? null;
    $canSubmit = ! $activity->exists || in_array($activity->status, ['brouillon', 'rejete'], true);
    $primaryLabel = (! $activity->exists || $activity->status === 'brouillon') ? 'Enregistrer le brouillon' : 'Enregistrer les modifications';
    $choixAnnee = ! $activity->exists && $anneesOuvertes->count() > 1;
@endphp
@section('content')
<div class="creation-ui">
    <nav class="breadcrumb" aria-label="Fil d’Ariane"><a href="{{ route('activities.index', ['annee' => $year]) }}">Activités réalisées</a><span aria-hidden="true"> / </span><span aria-current="page">{{ $activity->exists ? 'Modifier' : 'Nouvelle activité' }}</span></nav>
    <x-creation-heading :title="$activity->exists ? 'Modifier l’activité' : 'Déclarer une activité'" description="Racontez votre activité, renseignez son financement et ajoutez les pièces utiles à sa vérification." eyebrow="Espace fédération">
        @if ($choixAnnee)
            {{-- Plusieurs années ouvertes (ouverture exceptionnelle) : choix de l'exercice. --}}
            <label class="creation-chip creation-chip-select"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 11h18"/></svg>Exercice
                <select name="year" form="activityForm" aria-label="Exercice de l’activité">
                    @foreach ($anneesOuvertes as $annee)
                        <option value="{{ $annee }}" @selected((int) old('year', $year) === $annee)>{{ $annee }}</option>
                    @endforeach
                </select>
            </label>
        @else
            <span class="creation-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 11h18"/></svg>Exercice {{ old('year', $year) }}</span>
        @endif
    </x-creation-heading>
    @foreach ($ouvertures as $ouverture)
        <div class="notice notice-warning creation-opening">
            <strong>Ouverture exceptionnelle pour {{ $ouverture->year }}</strong>{{ $ouverture->expires_on ? ' jusqu’au '.$ouverture->expires_on->format('d/m/Y') : '' }} :
            l’administration vous permet de déclarer des activités de cet exercice. <span class="creation-opening-motif">Motif : {{ $ouverture->motif }}</span>
        </div>
    @endforeach
    <div class="creation-layout">
        <form method="POST" action="{{ $activity->exists ? route('activities.update', $activity) : route('activities.store') }}" enctype="multipart/form-data" class="creation-main js-validate" id="activityForm" novalidate>
            @csrf
            @if ($activity->exists) @method('PUT') @endif
            @unless ($choixAnnee)<input type="hidden" name="year" value="{{ old('year', $year) }}">@endunless
            <p class="creation-required">Les champs marqués d’un <span>*</span> sont obligatoires.</p>
            <x-form-section step="01" title="L’essentiel de l’activité" description="Un intitulé clair et un classement dans le canevas national.">
                <div class="creation-grid">
                    <div class="form-group full-width">
                        <label class="form-label" for="designationInput">Désignation de l’activité <span class="required-mark">*</span></label>
                        <input type="text" id="designationInput" name="designation" class="form-input" placeholder="Ex. Championnat régional des jeunes" maxlength="255" data-char-counter="designationCounter" aria-describedby="designationHint" value="{{ old('designation', $activity->designation) }}" required>
                        <div class="creation-field-meta creation-hint"><p id="designationHint">Précisez l’événement ou l’action réalisée.</p><span id="designationCounter"></span></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="axeSelect">Axe stratégique <span class="required-mark">*</span></label>
                        <select id="axeSelect" class="form-select" required>
                            <option value="">Choisir un axe</option>
                            @foreach ($axes as $axe)<option value="{{ $axe['code'] }}" @selected($selectedAxe === $axe['code'])>{{ $axe['label'] }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="sousAxeSelect">Sous-axe <span class="required-mark">*</span></label>
                        <select name="sous_axe_code" id="sousAxeSelect" class="form-select" required @disabled(! $selectedAxe)>
                            @if ($selectedAxe)
                                @foreach (collect($axes)->firstWhere('code', $selectedAxe)['sous_axes'] as $sousAxe)<option value="{{ $sousAxe['code'] }}" @selected($selectedSousAxe === $sousAxe['code'])>{{ $sousAxe['label'] }}</option>@endforeach
                            @else
                                <option value="">Choisissez d’abord un axe</option>
                            @endif
                        </select>
                    </div>
                </div>
            </x-form-section>
            <x-form-section step="02" title="Financement et réalisation" description="Indiquez les montants en FCFA et la période de l’activité.">
                <div class="creation-grid">
                    <div class="form-group">
                        <label class="form-label" for="activityAmount">Coût total <span class="optional">Facultatif</span></label>
                        <div class="input-money"><input id="activityAmount" type="text" inputmode="numeric" name="montant" class="form-input js-money-input" placeholder="0" value="{{ old('montant', $activity->montant) }}" aria-describedby="amountHint"><span class="input-money-suffix" aria-hidden="true">FCFA</span></div>
                        <p class="creation-hint" id="amountHint">Montant total de l’activité, en FCFA.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="activityContribution">Contribution des partenaires <span class="optional">Facultatif</span></label>
                        <div class="input-money"><input id="activityContribution" type="text" inputmode="numeric" name="contribution_partenaires" class="form-input js-money-input" placeholder="0" value="{{ old('contribution_partenaires', $activity->contribution_partenaires) }}" aria-describedby="contributionHint"><span class="input-money-suffix" aria-hidden="true">FCFA</span></div>
                        <p class="creation-hint" id="contributionHint">Part incluse dans le coût total, en FCFA.</p>
                    </div>
                    <div class="form-group"><label class="form-label" for="activityStart">Date de début <span class="optional">Facultatif</span></label><input id="activityStart" type="date" name="date_debut" class="form-input" value="{{ old('date_debut', optional($activity->date_debut)->format('Y-m-d')) }}"></div>
                    <div class="form-group"><label class="form-label" for="activityEnd">Date de fin <span class="optional">Facultatif</span></label><input id="activityEnd" type="date" name="date_fin" class="form-input" value="{{ old('date_fin', optional($activity->date_fin)->format('Y-m-d')) }}"></div>
                    <div class="form-group full-width"><label class="form-label" for="observationsInput">Observations <span class="optional">Facultatif</span></label><textarea id="observationsInput" name="observations" class="form-textarea" rows="3" maxlength="255" placeholder="Un résultat marquant, une précision ou une difficulté rencontrée…">{{ old('observations', $activity->observations) }}</textarea></div>
                </div>
            </x-form-section>
            <x-form-section step="03" title="Pièces justificatives" description="Au moins une pièce sera nécessaire pour la validation par la DGF.">
                <label for="piecesInput" class="dropzone">
                    <svg class="dropzone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 3v12M7 8l5-5 5 5"/></svg>
                    <span class="dropzone-text"><strong>Ajouter des justificatifs</strong></span>
                    <span class="dropzone-hint" id="piecesHint">Parcourir ou glisser vos fichiers · PDF, JPG, PNG · 5 Mo par fichier</span>
                    <input type="file" name="pieces[]" id="piecesInput" class="js-file-input" data-preview-target="piecesPreview" multiple accept=".pdf,.jpg,.jpeg,.png" aria-label="Ajouter des justificatifs" aria-describedby="piecesHint">
                </label>
                <div class="file-preview-list" id="piecesPreview" aria-live="polite"></div>
                @if ($activity->exists && $activity->documents->isNotEmpty())
                    <div class="creation-inset"><h3>Pièces déjà jointes</h3><div class="documents-grid">
                        @foreach ($activity->documents as $document)<x-document-card :document="$document" :view-url="route('activities.documents.view', [$activity, $document])" :download-url="route('activities.documents.download', [$activity, $document])" />@endforeach
                    </div></div>
                @endif
            </x-form-section>
            <div class="creation-actions is-sticky">
                <p>{{ $canSubmit ? 'Enregistrez pour reprendre plus tard, ou transmettez votre activité à la DGF.' : 'Votre activité reste modifiable jusqu’à sa validation par la DGF.' }}</p>
                <div class="btn-group"><button type="submit" name="intent" value="draft" class="btn">{{ $primaryLabel }}</button>@if ($canSubmit)<button type="submit" name="intent" value="submit" class="btn primary">Soumettre l’activité</button>@endif</div>
            </div>
        </form>
        <aside class="creation-guide" aria-label="Aide à la déclaration">
            <h2>Une déclaration en 3 parties</h2>
            <p>Préparez les informations utiles avant de transmettre votre activité.</p>
            <ol><li>Identifier l’activité</li><li>Préciser sa réalisation</li><li>Joindre les justificatifs</li></ol>
            <hr><p>Une fois validée par la DGF, l’activité alimente automatiquement votre rapport.</p>
        </aside>
    </div>
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
