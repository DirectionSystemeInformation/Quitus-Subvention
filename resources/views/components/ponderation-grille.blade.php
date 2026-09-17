@props(['rubriques', 'allocations', 'pointsMax'])

{{-- Reprend la feuille officielle « PONDERATION PAR ACTI PAR FEDE » : les
     critères en lignes avec leur barème, une colonne par fédération, un
     sous-total par rubrique, puis le total et la catégorie. Lecture seule :
     la saisie se fait dans le tableau de l'étape de pondération. --}}
@php
    $points = fn ($valeur) => rtrim(rtrim(number_format((float) $valeur, 2, ',', ' '), '0'), ',');

    // Sous-totaux par rubrique, indexés par allocation puis par rubrique.
    $sousTotaux = [];
    foreach ($allocations as $allocation) {
        $scores = $allocation->criteres_scores ?? [];
        foreach ($rubriques as $index => $rubrique) {
            $sousTotaux[$allocation->id][$index] = collect($rubrique['criteres'])
                ->sum(fn ($critere) => (float) ($scores[$critere['slug']] ?? 0));
        }
    }
@endphp

<div class="table-responsive">
    <table class="ponderation-grille">
        <thead>
            <tr>
                <th class="pg-num">N°</th>
                <th class="pg-rubrique">Rubriques</th>
                <th class="pg-critere">Critères</th>
                <th class="pg-bareme">Pondération</th>
                @foreach ($allocations as $allocation)
                    <th class="pg-fede" title="{{ $allocation->federation->federation_name }}">
                        <span>{{ $allocation->federation->federation_name }}</span>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php $numero = 1; @endphp
            @foreach ($rubriques as $rubriqueIndex => $rubrique)
                @foreach ($rubrique['criteres'] as $critereIndex => $critere)
                    <tr>
                        <td class="pg-num">{{ $numero++ }}</td>
                        @if ($critereIndex === 0)
                            <td class="pg-rubrique" rowspan="{{ count($rubrique['criteres']) }}">{{ $rubrique['rubrique'] }}</td>
                        @endif
                        <td class="pg-critere">{{ $critere['label'] }}</td>
                        <td class="pg-bareme">{{ $points($critere['max']) }}</td>
                        @foreach ($allocations as $allocation)
                            <td class="pg-fede">{{ $points($allocation->criteres_scores[$critere['slug']] ?? 0) }}</td>
                        @endforeach
                    </tr>
                @endforeach
                <tr class="pg-total-row">
                    <td colspan="3" class="pg-libelle-total">Total {{ $rubrique['rubrique'] }}</td>
                    <td class="pg-bareme">{{ $points($rubrique['rubrique_max']) }}</td>
                    @foreach ($allocations as $allocation)
                        <td class="pg-fede">{{ $points($sousTotaux[$allocation->id][$rubriqueIndex] ?? 0) }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="pg-grand-total">
                <td colspan="3" class="pg-libelle-total">Total</td>
                <td class="pg-bareme">{{ $points($pointsMax) }}</td>
                @foreach ($allocations as $allocation)
                    <td class="pg-fede">{{ $points($allocation->score_total ?? 0) }}</td>
                @endforeach
            </tr>
            <tr class="pg-categorie-row">
                <td colspan="3" class="pg-libelle-total">Catégorie</td>
                <td class="pg-bareme"></td>
                @foreach ($allocations as $allocation)
                    <td class="pg-fede {{ $allocation->categorie ? 'cat-'.strtolower($allocation->categorie) : '' }}">{{ $allocation->categorie ?? '—' }}</td>
                @endforeach
            </tr>
        </tfoot>
    </table>
</div>
