@props(['width' => 30, 'label' => 'Drapeau du Burkina Faso'])
{{-- Drapeau national : bande rouge, bande verte, étoile jaune à cinq branches au centre (proportions 3:2). --}}
<svg {{ $attributes->class(['bf-flag']) }} width="{{ $width }}" height="{{ round($width * 2 / 3) }}" viewBox="0 0 30 20" @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif focusable="false">
    <rect width="30" height="10" fill="#EF2B2D"/>
    <rect y="10" width="30" height="10" fill="#009E49"/>
    <polygon fill="#FCD116" points="15.00,5.70 16.01,8.61 19.09,8.67 16.64,10.53 17.53,13.48 15.00,11.72 12.47,13.48 13.36,10.53 10.91,8.67 13.99,8.61"/>
</svg>
