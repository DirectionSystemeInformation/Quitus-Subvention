@props(['document', 'viewUrl', 'downloadUrl'])

@php
    $ext = strtolower($document->extensionLabel());
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png'], true);
@endphp

<div {{ $attributes->merge(['class' => 'document-card']) }}>
    <div class="document-card-main">
        <div class="document-card-icon document-card-icon-{{ $ext }}" @if ($ext === 'pdf') data-pdf-thumb="{{ $viewUrl }}" @endif>
            @if ($isImage)
                <img class="document-card-thumb" src="{{ $viewUrl }}" alt="" loading="lazy">
            @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>
                </svg>
            @endif
        </div>
        <div class="document-card-body">
            <p class="document-card-name">{{ $document->nameWithoutExtension() }}</p>
            <p class="document-card-meta">
                <span class="document-card-ext">{{ $document->extensionLabel() }}</span>
                @if ($document->sizeLabel())
                    <span>&middot; {{ $document->sizeLabel() }}</span>
                @endif
                <span>&middot; Ajoutée le {{ $document->created_at->locale('fr')->translatedFormat('d F Y') }} à {{ $document->created_at->format('H:i') }}</span>
            </p>
        </div>
    </div>
    <div class="document-card-actions">
        <a href="{{ $viewUrl }}" target="_blank" rel="noopener" class="btn btn-sm">Consulter</a>
        <a href="{{ $downloadUrl }}" class="btn btn-sm info"><x-ui-icon name="download" /> Télécharger</a>
    </div>
</div>
