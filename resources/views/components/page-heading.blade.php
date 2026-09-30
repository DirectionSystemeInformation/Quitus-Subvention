@props(['title', 'description' => null, 'eyebrow' => null])
<header {{ $attributes->class(['platform-page-heading']) }}>
    <div>@if ($eyebrow)<p class="platform-eyebrow">{{ $eyebrow }}</p>@endif<h1>{{ $title }}</h1>@if ($description)<p class="platform-page-description">{{ $description }}</p>@endif</div>
    @if ($slot->isNotEmpty())<div class="platform-heading-actions">{{ $slot }}</div>@endif
</header>
