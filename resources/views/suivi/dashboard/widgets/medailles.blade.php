@if (empty($d['labels']))
    @include('suivi.dashboard.widgets._vide')
@else
    <div style="height:240px"><canvas id="{{ $id }}"></canvas></div>
    @push('widgets')
        <script>
            graphique(@json($id), 'bar', @json($d['labels']),
                @json(collect($d['series'])->map(fn ($s) => ['label' => $s['label'], 'data' => $s['valeurs'], 'backgroundColor' => $s['couleur']])),
                {scales: {x: {stacked: true}, y: {stacked: true, beginAtZero: true, ticks: {precision: 0}}}});
        </script>
    @endpush
@endif
