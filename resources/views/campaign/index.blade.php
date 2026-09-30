@extends('layouts.dashboard', ['active' => 'campagnes'])

@section('title', 'Campagnes')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <x-page-heading title="Campagnes de subvention" eyebrow="Pilotage et répartition" description="Suivez chaque campagne, de la pondération des fédérations à la délivrance du quitus." />

    @if (auth()->user()->isDshn())
        <div class="creation-ui">
        <x-form-section step="+" title="Nouvelle campagne" description="Ouvrez un nouveau cycle de répartition des subventions.">
            <form method="POST" action="{{ route('campagnes.store') }}" class="creation-inline">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="campaignYear">Année du programme budgétisé <span class="required-mark">*</span></label>
                    <input id="campaignYear" type="number" name="annee_n1" class="form-input" min="2000" max="2100" value="{{ old('annee_n1', now()->year + 1) }}" required aria-describedby="campaignYearHint">
                    <p class="creation-hint" id="campaignYearHint">Année N+1 : celle des activités prévisionnelles à financer.</p>
                    @error('annee_n1')
                        <p class="strength-text" style="color: var(--color-danger, #E5484D); margin-top: 6px;">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn primary">Créer la campagne</button>
            </form>
        </x-form-section>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Toutes les campagnes</h2>
        </div>

        @if ($campaigns->isEmpty())
            <x-empty-state icon="list" title="Aucune campagne créée pour le moment." />
        @else
            <div class="table-responsive">
            <table class="market-table">
                <thead>
                    <tr>
                        <th>Année N+1</th>
                        <th>Étape en cours</th>
                        <th>Statut</th>
                        <th>Fédérations</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($campaigns as $campaign)
                        <tr>
                            <td>{{ $campaign->annee_n1 }}</td>
                            <td>Étape {{ $campaign->etape }} : {{ $campaign->etapeLabel() }}</td>
                            <td>
                                <span class="status-badge {{ $campaign->statut === 'termine' ? 'status-gain' : 'status-neutral' }}">
                                    {{ $campaign->statut === 'termine' ? 'Terminée' : 'En cours' }}
                                </span>
                            </td>
                            <td>{{ $campaign->allocations_count }}</td>
                            <td><a href="{{ route('campagnes.show', $campaign) }}" class="security-btn">Ouvrir</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
@endsection
