@extends('layouts.dashboard', ['active' => 'reports'])

@section('title', 'Rapports reçus')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Rapports reçus</h1>
        <p>Rapports d'activité et projets de programmes budgétisés déposés par les fédérations</p>
    </div>

    @if (session('status'))
        <div class="card" style="margin-bottom: 24px; border-left: 4px solid var(--gain, #6b8e6b);">
            {{ session('status') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Documents déposés</h2>
        </div>

        @if ($reports->isEmpty())
            <p class="strength-text">Aucun document déposé pour le moment.</p>
        @else
            <table class="market-table">
                <thead>
                    <tr>
                        <th>Fédération</th>
                        <th>Document</th>
                        <th>Année</th>
                        <th>Déposé le</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr>
                            <td>{{ $report->user->federation_name }}</td>
                            <td>{{ $report->typeLabel() }}</td>
                            <td>{{ $report->year }}</td>
                            <td>{{ $report->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if ($report->status === 'valide')
                                    <span style="color: var(--gain, #6b8e6b);">Validé</span>
                                @elseif ($report->status === 'rejete')
                                    <span style="color: var(--loss, #c27878);">Rejeté</span>
                                @else
                                    <span>Soumis</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:8px;">
                                    <a href="{{ route('activity-form.show', $report) }}" class="security-btn">Voir le détail</a>
                                    @if ($report->status !== 'valide')
                                        <form method="POST" action="{{ route('dshn.reports.validate', $report) }}">
                                            @csrf
                                            <button type="submit" class="security-btn primary">Valider</button>
                                        </form>
                                    @endif
                                    @if ($report->status !== 'rejete')
                                        <form method="POST" action="{{ route('dshn.reports.reject', $report) }}">
                                            @csrf
                                            <button type="submit" class="security-btn">Rejeter</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
