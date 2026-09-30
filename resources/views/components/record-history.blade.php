@props(['record', 'submittedAt' => null])
<section class="platform-panel record-history" aria-label="Repères de suivi">
    <h2>Repères de suivi</h2>
    <ol>
        <li><strong>Création</strong><time datetime="{{ $record->created_at->toIso8601String() }}">{{ $record->created_at->format('d/m/Y à H:i') }}</time></li>
        @if ($submittedAt)<li><strong>Transmission</strong><time datetime="{{ $submittedAt->toIso8601String() }}">{{ $submittedAt->format('d/m/Y à H:i') }}</time></li>@endif
        @if ($record->status === 'valide' && $record->validated_at)<li class="is-complete"><strong>Validation</strong><time datetime="{{ $record->validated_at->toIso8601String() }}">{{ $record->validated_at->format('d/m/Y à H:i') }}</time></li>@endif
        <li><strong>Dernière mise à jour</strong><time datetime="{{ $record->updated_at->toIso8601String() }}">{{ $record->updated_at->format('d/m/Y à H:i') }}</time></li>
    </ol>
</section>
