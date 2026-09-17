@props(['rubriques', 'lignes', 'pointsMax'])

@php
    // Notation à la française, sans décimale inutile : 9,5 mais 17.
    $points = fn ($valeur) => rtrim(rtrim(number_format((float) $valeur, 2, ',', ' '), '0'), ',');
@endphp

{{-- Reprend la maquette officielle "RECAP PAR RUBRIQUE PAR FEDE" : une colonne
     par rubrique, la ligne des barèmes, et les couleurs de catégorie du
     document de la plénière. --}}
<div class="table-responsive">
    <table class="market-table recap-rubriques">
        <thead>
            <tr>
                <th class="recap-col-num">N°</th>
                <th class="recap-col-structure">Structures</th>
                @foreach ($rubriques as $rubrique)
                    <th class="recap-col-rubrique">{{ $rubrique['rubrique'] }}</th>
                @endforeach
                <th class="recap-col-total">Total points</th>
                <th class="recap-col-cat">Catégories</th>
                <th class="recap-col-cat">Catégories ajustées</th>
                <th class="recap-col-montant">Montant</th>
            </tr>
            <tr class="recap-max-row">
                <td></td>
                <td></td>
                @foreach ($rubriques as $rubrique)
                    <td class="recap-max-cell">{{ $points($rubrique['rubrique_max']) }}</td>
                @endforeach
                <td class="recap-max-cell">{{ $points($pointsMax) }}</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </thead>
        <tbody>
            @foreach ($lignes as $index => $ligne)
                @php
                    $allocation = $ligne['allocation'];
                    $categorie = $allocation->categorie;
                    $montant = $allocation->montant_final ?? $allocation->montant_arbitre ?? $allocation->montant_propose;
                @endphp
                <tr>
                    <td class="recap-col-num">{{ $index + 1 }}</td>
                    <td class="recap-col-structure">{{ $allocation->federation->federation_name }}</td>
                    @foreach ($rubriques as $rubrique)
                        <td class="recap-col-rubrique">{{ $points($ligne['sous_totaux'][$rubrique['rubrique']] ?? 0) }}</td>
                    @endforeach
                    <td class="recap-col-total">{{ $points($allocation->score_total ?? 0) }}</td>
                    <td class="recap-col-cat {{ $categorie ? 'cat-'.strtolower($categorie) : '' }}">{{ $categorie ?? '—' }}</td>
                    <td class="recap-col-cat">{{ $allocation->categorie_ajustee ?? '—' }}</td>
                    <td class="recap-col-montant">{{ $montant !== null ? number_format((float) $montant, 0, ',', ' ') : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
