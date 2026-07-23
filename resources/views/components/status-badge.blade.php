@php
    $map = [
        'valide' => ['label' => 'Validé', 'class' => 'status-gain'],
        'active' => ['label' => 'Actif', 'class' => 'status-gain'],
        'rejete' => ['label' => 'Rejeté', 'class' => 'status-loss'],
        'rejected' => ['label' => 'Rejeté', 'class' => 'status-loss'],
        'soumis' => ['label' => 'Soumis', 'class' => 'status-neutral'],
        'pending' => ['label' => 'En attente', 'class' => 'status-neutral'],
        'manquant' => ['label' => 'Non déposé', 'class' => 'status-muted'],
    ];

    $info = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'status-neutral'];
@endphp

<span class="status-badge {{ $info['class'] }}">{{ $info['label'] }}</span>
