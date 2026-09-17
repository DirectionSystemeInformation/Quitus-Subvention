@props(['allocations', 'avecSurcharge' => false])

{{-- Reprend la feuille officielle « CLASSEMENT PAR FEDERATION », classée par
     points décroissants. La colonne de surcharge n'apparaît que dans le
     formulaire de répartition : elle n'existe pas dans le document. --}}
@php
    $points = fn ($valeur) => rtrim(rtrim(number_format((float) $valeur, 2, ',', ' '), '0'), ',');
@endphp

<div class="table-responsive">
    <table class="market-table classement-federations">
        <thead>
            <tr>
                <th class="cl-num">N°</th>
                <th>Fédérations sportives et de loisirs</th>
                <th class="cl-points">Nbre de points</th>
                <th class="cl-cat">Catégories</th>
                <th class="cl-cat">Catégorie ajustée</th>
                <th class="cl-montant">Montant proposé</th>
                @if ($avecSurcharge)
                    <th class="cl-override">Surcharge manuelle (optionnel)</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($allocations as $index => $allocation)
                <tr>
                    <td class="cl-num">{{ $index + 1 }}</td>
                    <td>{{ $allocation->federation->federation_name }}</td>
                    <td class="cl-points">{{ $points($allocation->score_total ?? 0) }}</td>
                    <td class="cl-cat {{ $allocation->categorie ? 'cat-'.strtolower($allocation->categorie) : '' }}">{{ $allocation->categorie ?? '—' }}</td>
                    <td class="cl-cat">{{ $allocation->categorie_ajustee ?? '—' }}</td>
                    <td class="cl-montant">{{ $allocation->montant_propose !== null ? number_format($allocation->montant_propose, 0, ',', ' ').' FCFA' : '—' }}</td>
                    @if ($avecSurcharge)
                        <td class="cl-override">
                            <input type="number" name="overrides[{{ $allocation->id }}]" class="form-input" min="0" step="1000"
                                placeholder="Barème par défaut"
                                aria-label="Surcharge manuelle du montant pour {{ $allocation->federation->federation_name }}">
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
