{{-- Graphique simple réutilisable : $d = ['labels' => [], 'valeurs' => []], $type = bar|doughnut|pie --}}
@if (empty($d['labels']) || array_sum($d['valeurs']) == 0)
    @include('suivi.dashboard.widgets._vide')
@else
    <div style="height:{{ $hauteur ?? 240 }}px"><canvas id="{{ $id }}"></canvas></div>
    @push('widgets')
        <script>
            graphique(@json($id), @json($type), @json($d['labels']),
                [{label: @json($serie ?? 'Talents'), data: @json($d['valeurs']), backgroundColor: @json($type === 'bar') ? PALETTE[0] : PALETTE}],
                @json($type === 'bar') ? {plugins: {legend: {display: false}}, scales: {y: {beginAtZero: true, ticks: {precision: 0}}}} : {});
        </script>
    @endpush
@endif
