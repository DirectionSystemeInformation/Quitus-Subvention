@extends('layouts.dashboard', ['active' => 'federations'])

@section('title', $federation->federation_name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <style>
        .log-icon { width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--bg-secondary, rgba(255,255,255,0.08)); }
        .log-icon svg { width: 16px; height: 16px; }
        .log-icon.created, .log-icon.validated { color: var(--color-success, #5CBE8D); }
        .log-icon.updated { color: var(--color-primary, #129850); }
        .log-icon.rejected, .log-icon.deleted { color: var(--color-danger, #E5484D); }
        .log-item { display: flex; gap: 14px; align-items: flex-start; padding: 14px 0; border-bottom: 1px solid var(--border, #333); }
        .log-item:last-child { border-bottom: none; }
        .log-desc { font-size: 14px; }
        .log-meta { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }
    </style>
@endpush

@section('content')
    @php
        $saveIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
        $logIcons = [
            'created' => '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'validated' => '<polyline points="20 6 9 17 4 12"/>',
            'rejected' => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
            'updated' => '<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>',
            'deleted' => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>',
        ];
    @endphp

    <div class="page-header">
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="{{ role_route('federations.index') }}">Fédérations</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">{{ $federation->federation_name }}</span>
        </nav>
        <h1 style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            {{ $federation->federation_name }}
            <x-status-badge :status="$federation->status" />
        </h1>
        <p>Responsable : {{ $federation->name }} — {{ $federation->email }} — inscrite le {{ $federation->created_at->format('d/m/Y') }}</p>
        @if ($federation->status === 'rejected' && $federation->rejection_reason)
            <p style="color: var(--color-danger, #E5484D); margin-top: 8px;">Motif du rejet : {{ $federation->rejection_reason }}</p>
        @endif
    </div>

    <div class="market-stats" style="margin-bottom: 24px;">
        <div class="market-stat">
            <div class="market-stat-label">Documents déposés</div>
            <div class="market-stat-value">{{ $stats['total'] }}</div>
        </div>
        <div class="market-stat">
            <div class="market-stat-label">Validés</div>
            <div class="market-stat-value">{{ $stats['valide'] }}</div>
        </div>
        <div class="market-stat">
            <div class="market-stat-label">Soumis</div>
            <div class="market-stat-value">{{ $stats['soumis'] }}</div>
        </div>
        <div class="market-stat">
            <div class="market-stat-label">Rejetés</div>
            <div class="market-stat-value">{{ $stats['rejete'] }}</div>
        </div>
    </div>

    @if ($federation->status === 'pending')
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Validation du compte</h2>
            </div>
            <div class="btn-group">
                <form method="POST" action="{{ role_route('federations.validate', $federation) }}">
                    @csrf
                    <button type="submit" class="btn primary">Valider le compte</button>
                </form>
                <button type="button" class="btn danger js-reject-reason" data-action="{{ role_route('federations.reject', $federation) }}">Rejeter</button>
            </div>
        </div>
    @endif

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h2 class="card-title">Documents déposés</h2>
        </div>
        @if ($reports->isEmpty())
            <x-empty-state icon="inbox" title="Aucun document déposé pour le moment." />
        @else
            <div class="table-responsive">
            <table class="market-table">
                <thead>
                    <tr>
                        <th>Année</th>
                        <th>Document</th>
                        <th>Déposé le</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr>
                            <td>{{ $report->year }}</td>
                            <td>{{ $report->typeLabel() }}</td>
                            <td>{{ $report->created_at->format('d/m/Y H:i') }}</td>
                            <td><x-status-badge :status="$report->status" /></td>
                            <td><a href="{{ route('activity-form.show', $report) }}" class="security-btn">Voir le détail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>

    @if (auth()->user()->isAdmin())
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Modifier le compte</h2>
            </div>
            <form method="POST" action="{{ role_route('federations.update', $federation) }}" class="form-grid">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Nom de la fédération</label>
                    <input type="text" name="federation_name" class="form-input" value="{{ $federation->federation_name }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Responsable</label>
                    <input type="text" name="name" class="form-input" value="{{ $federation->name }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="{{ $federation->email }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="pending" {{ $federation->status === 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="active" {{ $federation->status === 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="rejected" {{ $federation->status === 'rejected' ? 'selected' : '' }}>Rejeté</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nouveau mot de passe (optionnel)</label>
                    <input type="password" name="password" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" class="form-input">
                </div>
                <div class="form-group full-width">
                    <button type="submit" class="btn primary">Enregistrer les modifications</button>
                </div>
            </form>
        </div>

        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Zone de danger</h2>
            </div>
            <form method="POST" action="{{ role_route('federations.destroy', $federation) }}" data-confirm="Supprimer le compte de {{ $federation->federation_name }} ?">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Supprimer ce compte</button>
            </form>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Historique</h2>
        </div>
        @if ($logs->isEmpty())
            <x-empty-state icon="clock" title="Aucune action enregistrée pour cette fédération." />
        @else
            @foreach ($logs as $log)
                <div class="log-item">
                    <div class="log-icon {{ $log->action }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $logIcons[$log->action] ?? $logIcons['updated'] !!}</svg>
                    </div>
                    <div>
                        <div class="log-desc">
                            @if ($log->causer_name)
                                <strong>{{ $log->causer_name }}</strong> {{ $log->description }}
                            @else
                                {{ $log->description }}
                            @endif
                        </div>
                        <div class="log-meta">{{ $log->created_at->format('d/m/Y à H:i') }}</div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection
