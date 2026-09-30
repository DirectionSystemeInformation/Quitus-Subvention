@props(['title', 'description', 'eyebrow' => 'Nouvelle saisie'])
<header {{ $attributes->class(['creation-heading']) }}>
    <div class="creation-heading-copy">
        <p class="creation-eyebrow"><span aria-hidden="true"></span>{{ $eyebrow }}</p>
        <h1>{{ $title }}</h1>
        <p class="creation-description">{{ $description }}</p>
    </div>
    @if ($slot->isNotEmpty())<div class="creation-context">{{ $slot }}</div>@endif
</header>
