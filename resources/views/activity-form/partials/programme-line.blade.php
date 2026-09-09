<fieldset class="programme-line">
    <legend>Activité {{ $index }}</legend>
    <div class="programme-line-fields">
        @foreach (['designation' => ['Désignation de l’activité', 'text'], 'montant' => ['Montant (FCFA)', 'number'], 'contribution_partenaires' => ['Contribution des partenaires', 'text'], 'date' => ['Date prévue', 'date'], 'observations' => ['Observations', 'text']] as $field => [$label, $inputType])
            @php $inputId = 'line-'.$sousAxe['id'].'-'.$index.'-'.$field; @endphp
            <div class="programme-field field-{{ $field }}">
                <label for="{{ $inputId }}">{{ $label }}</label>
                <input id="{{ $inputId }}" type="{{ $inputType }}" class="form-input" name="lignes[{{ $code }}][{{ $index }}][{{ $field }}]" data-field="{{ $field }}" data-error-key="lignes.{{ $code }}.{{ $index }}.{{ $field }}" value="{{ is_scalar($row[$field] ?? '') ? ($row[$field] ?? '') : '' }}" @if ($inputType === 'number') min="0" max="999999999999.99" step="0.01" inputmode="decimal" @elseif ($inputType === 'text') maxlength="255" @endif>
            </div>
        @endforeach
    </div>
    <button type="button" class="programme-remove" aria-label="Retirer l’activité {{ $index }} du sous-axe {{ $code }}">Retirer cette activité</button>
</fieldset>
