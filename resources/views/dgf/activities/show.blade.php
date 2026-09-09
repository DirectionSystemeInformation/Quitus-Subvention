@extends('layouts.dashboard', ['active' => 'dgf-activities'])

@section('title', $activity->designation ?? 'Activité')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="{{ route('dgf.activities.index') }}">Activités à valider</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">{{ $activity->designation }}</span>
        </nav>
        <h1 style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            {{ $activity->designation }}
            <x-status-badge :status="$activity->status" />
        </h1>
        <p>{{ $activity->user->federation_name }} — {{ $activity->axe_label }} — {{ $activity->sous_axe_label }} — Année {{ $activity->year }}</p>
        @if ($activity->status === 'rejete' && $activity->rejection_reason)
            <p style="color: var(--color-danger, #E5484D); margin-top: 8px;">Motif du rejet : {{ $activity->rejection_reason }}</p>
        @endif
        @if ($activity->status === 'valide' && $activity->validator)
            <p style="color: var(--color-success, #1DAA5E); margin-top: 8px;">Validée par {{ $activity->validator->name }} le {{ $activity->validated_at->format('d/m/Y à H:i') }}</p>
        @endif
        @if ($activity->status === 'brouillon')
            <p class="strength-text" style="margin-top: 8px;">Cette activité est encore en brouillon — la fédération ne l'a pas encore soumise.</p>
        @endif
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h2 class="card-title">Détails</h2>
        </div>
        <table class="market-table">
            <tbody>
                <tr><td style="width:220px;">Fédération</td><td>{{ $activity->user->federation_name }}</td></tr>
                <tr><td>Montant</td><td>{{ $activity->montant !== null ? number_format((float) $activity->montant, 0, ',', ' ').' FCFA' : '—' }}</td></tr>
                <tr><td>Contribution des partenaires</td><td>{{ $activity->contribution_partenaires ?? '—' }}</td></tr>
                <tr><td>Date de réalisation</td><td>{{ optional($activity->date)->format('d/m/Y') ?? '—' }}</td></tr>
                <tr><td>Observations</td><td>{{ $activity->observations ?? '—' }}</td></tr>
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h2 class="card-title">Pièces justificatives</h2>
        </div>
        @if ($activity->documents->isEmpty())
            <x-empty-state icon="inbox" title="Aucune pièce jointe." :compact="true" />
        @else
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($activity->documents as $document)
                    <li><a href="{{ route('dgf.activities.documents.download', [$activity, $document]) }}">{{ $document->original_filename }}</a></li>
                @endforeach
            </ul>
        @endif
    </div>

    @if ($activity->status === 'soumis')
        @if ($activity->documents->isEmpty())
            <p class="strength-text" style="color: var(--color-danger, #E5484D); margin-bottom: 16px;">Aucune pièce justificative jointe — la validation est impossible tant que la fédération n'en a pas ajouté une. Vous pouvez rejeter l'activité pour le lui demander.</p>
        @endif
        <div class="btn-group">
            @if ($activity->documents->isNotEmpty())
                <form method="POST" action="{{ route('dgf.activities.validate', $activity) }}"
                    data-confirm="Valider cette activité ? Elle sera versée au rapport d'activité de la fédération.">
                    @csrf
                    <button type="submit" class="btn primary">Valider</button>
                </form>
            @endif
            <button type="button" class="btn danger js-reject-reason" data-action="{{ route('dgf.activities.reject', $activity) }}">Rejeter</button>
        </div>
    @endif
@endsection
