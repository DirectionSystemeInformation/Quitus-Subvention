@props(['step', 'title', 'description' => null])
<section {{ $attributes->class(['creation-section']) }}>
    <div class="creation-section-heading">
        <span class="creation-step" aria-hidden="true">{{ $step }}</span>
        <div><h2>{{ $title }}</h2>@if ($description)<p>{{ $description }}</p>@endif</div>
    </div>
    <div class="creation-section-body">{{ $slot }}</div>
</section>
