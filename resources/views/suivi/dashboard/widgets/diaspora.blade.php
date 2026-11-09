@if (empty($d['parPays']['labels']))
    @include('suivi.dashboard.widgets._vide')
@else
    <div class="row">
        <div class="col-md-5">@include('suivi.dashboard.widgets._graphique', ['d' => $d['parPays'], 'type' => 'doughnut', 'hauteur' => 200])</div>
        <div class="col-md-7">
            <ul class="list-unstyled small mb-0">
                @foreach ($d['liste'] as $l)
                    <li class="mb-2"><a href="{{ route('suivi.talents.show', $l->talent_id) }}" class="fw-semibold text-decoration-none">{{ $l->talent->nom_complet }}</a>
                        <br><span class="text-muted">{{ $l->talent->discipline->nom }} · {{ $l->club }} ({{ $l->pays_club }})</span></li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
