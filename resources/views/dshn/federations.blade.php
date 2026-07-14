@extends('layouts.dashboard', ['active' => 'federations'])

@section('title', 'Fédérations')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Fédérations</h1>
        <p>Validation des demandes de création de compte et suivi des fédérations enregistrées</p>
    </div>

    @if (session('status'))
        <div class="card" style="margin-bottom: 24px; border-left: 4px solid var(--gain, #6b8e6b);">
            {{ session('status') }}
        </div>
    @endif

    @php
        $pending = $federations->where('status', 'pending');
        $others = $federations->where('status', '!=', 'pending');
    @endphp

    <div class="market-stats" style="margin-bottom: 24px;">
        <div class="market-stat">
            <div class="market-stat-label">En attente de validation</div>
            <div class="market-stat-value">{{ $pending->count() }}</div>
        </div>
        <div class="market-stat">
            <div class="market-stat-label">Comptes actifs</div>
            <div class="market-stat-value">{{ $federations->where('status', 'active')->count() }}</div>
        </div>
        <div class="market-stat">
            <div class="market-stat-label">Comptes rejetés</div>
            <div class="market-stat-value">{{ $federations->where('status', 'rejected')->count() }}</div>
        </div>
    </div>

    @if ($pending->isNotEmpty())
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Demandes en attente</h2>
            </div>
            <table class="market-table">
                <thead>
                    <tr>
                        <th>Fédération</th>
                        <th>Responsable</th>
                        <th>Email</th>
                        <th>Demandée le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pending as $federation)
                        <tr>
                            <td>{{ $federation->federation_name }}</td>
                            <td>{{ $federation->name }}</td>
                            <td>{{ $federation->email }}</td>
                            <td>{{ $federation->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div style="display:flex;gap:8px;">
                                    <form method="POST" action="{{ route('dshn.federations.validate', $federation) }}">
                                        @csrf
                                        <button type="submit" class="security-btn primary">Valider</button>
                                    </form>
                                    <form method="POST" action="{{ route('dshn.federations.reject', $federation) }}">
                                        @csrf
                                        <button type="submit" class="security-btn">Rejeter</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Toutes les fédérations</h2>
        </div>
        <table class="market-table">
            <thead>
                <tr>
                    <th>Fédération</th>
                    <th>Responsable</th>
                    <th>Email</th>
                    <th>Statut</th>
                    <th>Documents déposés</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($others as $federation)
                    <tr>
                        <td>{{ $federation->federation_name }}</td>
                        <td>{{ $federation->name }}</td>
                        <td>{{ $federation->email }}</td>
                        <td>
                            @if ($federation->status === 'active')
                                <span style="color: var(--gain, #6b8e6b);">Actif</span>
                            @else
                                <span style="color: var(--loss, #c27878);">Rejeté</span>
                            @endif
                        </td>
                        <td>{{ $federation->reports()->count() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Aucune autre fédération enregistrée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
