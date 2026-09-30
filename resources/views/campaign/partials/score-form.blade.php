<form method="POST" action="{{ route('campagnes.ponderation.update', $campaign) }}" id="ponderationForm" data-campaign-edit>
    @csrf
    <p class="section-intro">Ouvrez une fédération, puis renseignez les notes par rubrique. Le total se met à jour pendant la saisie.</p>
    <div id="ponderationTable" class="score-federations">
        @foreach ($campaign->allocations as $allocation)
            <details class="score-federation" data-allocation-row data-allocation-id="{{ $allocation->id }}" @if ($loop->first || old('scores.'.$allocation->id)) open @endif>
                @php $renseignes = count(array_filter($allocation->criteres_scores ?? [], fn ($score) => $score !== null && $score !== '')); @endphp
                <summary>
                    <span class="score-federation-name">{{ $allocation->federation->federation_name }}</span>
                    <span @class(['score-filled', 'is-complete' => $renseignes === count($criteres)]) data-filled>{{ $renseignes }} / {{ count($criteres) }} critères renseignés</span>
                    <span class="score-total"><span data-total>{{ 0 + ($allocation->score_total ?? 0) }}</span> / {{ 0 + $pointsMax }} <small>points</small></span>
                </summary>
                <div class="score-rubrics">
                    @foreach ($rubriques as $rubrique)
                        <fieldset class="score-rubric" data-rubrique>
                            <legend>
                                <span class="score-rubric-name">{{ $rubrique['rubrique'] }}</span>
                                <span class="score-rubric-total"><span data-rubrique-total>{{ 0 + collect($rubrique['criteres'])->sum(fn ($critere) => (float) ($allocation->criteres_scores[$critere['slug']] ?? 0)) }}</span> / {{ 0 + $rubrique['rubrique_max'] }}</span>
                            </legend>
                            <div class="score-criteria">
                                @foreach ($rubrique['criteres'] as $critere)
                                    @php $scoreId = 'score-'.$allocation->id.'-'.$critere['slug']; @endphp
                                    <div class="score-criterion">
                                        <label for="{{ $scoreId }}">{{ $critere['label'] }}</label>
                                        <div class="score-input-wrap">
                                            <input id="{{ $scoreId }}" type="number" step="0.5" min="0" max="{{ $critere['max'] }}" name="scores[{{ $allocation->id }}][{{ $critere['slug'] }}]" value="{{ old('scores.'.$allocation->id.'.'.$critere['slug'], $allocation->criteres_scores[$critere['slug']] ?? '') }}" class="form-input critere-input" aria-describedby="{{ $scoreId }}-max" placeholder="–">
                                            <span id="{{ $scoreId }}-max">/ {{ 0 + $critere['max'] }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
            </details>
        @endforeach
    </div>
    <div class="creation-actions campaign-save-bar"><p data-campaign-save-state role="status">Enregistrez les scores avant de passer à l’étape suivante.</p><button type="submit" class="btn primary">Enregistrer les scores</button></div>
</form>
