@if ($t->photo_url)
    <img src="{{ $t->photo_url }}" alt="" class="avatar {{ $taille }}">
@else
    <span class="avatar {{ $taille }}">{{ $t->initiales }}</span>
@endif
