@props(['campaign'])
<ol class="campaign-progress" aria-label="Phases de la campagne">
    @foreach (\App\Support\CampaignPresentation::phases() as $phase)
        @php
            $done = $campaign->statut === 'termine' || $campaign->etape > $phase['to'];
            $current = ! $done && $campaign->etape >= $phase['from'] && $campaign->etape <= $phase['to'];
        @endphp
        <li class="{{ $done ? 'is-done' : ($current ? 'is-current' : '') }}" @if ($current) aria-current="step" @endif>
            <span class="campaign-phase-marker" aria-hidden="true">{{ $done ? '✓' : $loop->iteration }}</span>
            <div><strong>{{ $phase['label'] }}</strong><small>{{ $done ? 'Parcourue' : ($current ? 'En cours' : 'À venir') }}</small></div>
        </li>
    @endforeach
</ol>
