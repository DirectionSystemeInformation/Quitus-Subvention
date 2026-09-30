@props(['title', 'count', 'href', 'description', 'icon' => 'file'])
<a class="platform-action-card" href="{{ $href }}">
    <div class="action-card-top"><x-ui-icon :name="$icon" /><span class="action-card-count">{{ $count }}</span></div>
    <h2>{{ $title }}</h2><p>{{ $description }}</p><span class="action-card-link">Ouvrir la liste <x-ui-icon name="arrow" /></span>
</a>
