{{-- Une activité du programme : ligne compacte sur deux rangées (désignation,
     montant, date / contribution, observations). Libellés visibles dans
     l'en-tête de colonnes du sous-axe, et restitués aux lecteurs d'écran. --}}
@php
    $valeur = fn ($champ) => is_scalar($row[$champ] ?? '') ? ($row[$champ] ?? '') : '';
    $montant = $valeur('montant');
    $montant = is_numeric($montant) ? 0 + $montant : $montant;
    $id = fn ($champ) => 'line-'.$sousAxe['id'].'-'.$index.'-'.$champ;
    $nom = fn ($champ) => 'lignes['.$code.']['.$index.']['.$champ.']';
    $cle = fn ($champ) => 'lignes.'.$code.'.'.$index.'.'.$champ;
@endphp
<div class="pf-line" data-line>
    <span class="pf-line-num" data-line-num aria-hidden="true">{{ $index }}</span>
    <div class="pf-field pf-designation">
        <label class="sr-only" for="{{ $id('designation') }}">Désignation de l’activité</label>
        <input id="{{ $id('designation') }}" type="text" class="form-input" name="{{ $nom('designation') }}" data-field="designation" data-error-key="{{ $cle('designation') }}" value="{{ $valeur('designation') }}" placeholder="Désignation de l’activité" maxlength="255">
    </div>
    <div class="pf-field pf-montant">
        <label class="sr-only" for="{{ $id('montant') }}">Montant en FCFA</label>
        <input id="{{ $id('montant') }}" type="text" class="form-input" name="{{ $nom('montant') }}" data-field="montant" data-money data-error-key="{{ $cle('montant') }}" value="{{ $montant }}" placeholder="0" inputmode="numeric" autocomplete="off">
        <span class="pf-suffix" aria-hidden="true">FCFA</span>
    </div>
    <div class="pf-field pf-date">
        <label class="sr-only" for="{{ $id('date') }}">Date prévue</label>
        <input id="{{ $id('date') }}" type="date" class="form-input" name="{{ $nom('date') }}" data-field="date" data-error-key="{{ $cle('date') }}" value="{{ $valeur('date') }}">
    </div>
    <button type="button" class="pf-remove" data-remove aria-label="Retirer cette activité du sous-axe {{ $code }}" title="Retirer l’activité"><x-ui-icon name="trash" /></button>
    <div class="pf-field pf-contribution">
        <label class="sr-only" for="{{ $id('contribution_partenaires') }}">Contribution des partenaires</label>
        <input id="{{ $id('contribution_partenaires') }}" type="text" class="form-input" name="{{ $nom('contribution_partenaires') }}" data-field="contribution_partenaires" data-error-key="{{ $cle('contribution_partenaires') }}" value="{{ $valeur('contribution_partenaires') }}" placeholder="Contribution des partenaires (facultatif)" maxlength="255">
    </div>
    <div class="pf-field pf-observations">
        <label class="sr-only" for="{{ $id('observations') }}">Observations</label>
        <input id="{{ $id('observations') }}" type="text" class="form-input" name="{{ $nom('observations') }}" data-field="observations" data-error-key="{{ $cle('observations') }}" value="{{ $valeur('observations') }}" placeholder="Observations (facultatif)" maxlength="255">
    </div>
</div>
