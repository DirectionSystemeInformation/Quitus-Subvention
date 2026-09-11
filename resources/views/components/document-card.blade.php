@props(['document', 'viewUrl', 'downloadUrl'])

@php
    $ext = strtolower($document->extensionLabel());
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png'], true);
@endphp

<div {{ $attributes->merge(['class' => 'document-card']) }}>
    <div class="document-card-icon document-card-icon-{{ $ext }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            @if ($isImage)
                <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>
            @else
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>
            @endif
        </svg>
    </div>
    <div class="document-card-body">
        <p class="document-card-name">{{ $document->nameWithoutExtension() }}</p>
        <p class="document-card-meta">
            <span class="document-card-ext">{{ $document->extensionLabel() }}</span>
            @if ($document->sizeLabel())
                <span>&middot; {{ $document->sizeLabel() }}</span>
            @endif
            <span>&middot; Ajoutée le {{ $document->created_at->locale('fr')->translatedFormat('d F Y') }}</span>
        </p>
    </div>
    <div class="document-card-actions">
        <a href="{{ $viewUrl }}" target="_blank" rel="noopener" class="btn btn-sm">Voir</a>
        <a href="{{ $downloadUrl }}" class="btn btn-sm">Télécharger</a>
    </div>
</div>
