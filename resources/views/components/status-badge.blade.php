@php
    $map = [
        'valide' => ['label' => 'Validé', 'class' => 'status-gain', 'icon' => 'check'],
        'active' => ['label' => 'Actif', 'class' => 'status-gain', 'icon' => 'check'],
        'rejete' => ['label' => 'Rejeté', 'class' => 'status-loss', 'icon' => 'cross'],
        'rejected' => ['label' => 'Rejeté', 'class' => 'status-loss', 'icon' => 'cross'],
        'soumis' => ['label' => 'Soumis', 'class' => 'status-neutral', 'icon' => 'clock'],
        'pending' => ['label' => 'En attente', 'class' => 'status-neutral', 'icon' => 'clock'],
        'manquant' => ['label' => 'Non déposé', 'class' => 'status-muted', 'icon' => 'dash'],
        'brouillon' => ['label' => 'Brouillon', 'class' => 'status-muted', 'icon' => 'dash'],
    ];

    $info = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'status-neutral', 'icon' => 'dash'];

    $icons = [
        'check' => '<polyline points="20 6 9 17 4 12"/>',
        'cross' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'dash' => '<line x1="5" y1="12" x2="19" y2="12"/>',
    ];
@endphp

<span class="status-badge {{ $info['class'] }}">
    <svg class="status-badge-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
        {!! $icons[$info['icon']] !!}
    </svg>
    {{ $info['label'] }}
</span>
