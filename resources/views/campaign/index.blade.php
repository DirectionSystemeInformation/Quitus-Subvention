@extends('layouts.dashboard', ['active' => 'campagnes'])

@section('title', 'Campagnes')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Campagnes</h1>
        <p>Suivi du circuit de répartition de la subvention (pondération, arbitrage, délivrance du quitus)</p>
    </div>

    @if (auth()->user()->isDshn())
        <div class="card" style="margin-bottom: 24px; max-width: 480px;">
            <div class="card-header">
                <h2 class="card-title">Nouvelle campagne</h2>
            </div>
            <form method="POST" action="{{ route('campagnes.store') }}" class="form-grid">
                @csrf
                <div class="form-group full-width">
                    <label class="form-label">Année du programme budgétisé (N+1)</label>
                    <input type="number" name="annee_n1" class="form-input" min="2000" max="2100" value="{{ old('annee_n1', now()->year + 1) }}" required>
                    @error('annee_n1')
                        <p class="strength-text" style="color: var(--color-danger, #E5484D); margin-top: 6px;">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-group full-width">
                    <button type="submit" class="btn primary">Créer la campagne</button>
                </div>
            </form>
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
                            <td>Étape {{ $campaign->etape }} — {{ $campaign->etapeLabel() }}</td>
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
