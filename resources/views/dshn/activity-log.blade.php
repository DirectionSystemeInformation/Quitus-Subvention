@extends('layouts.dashboard', ['active' => 'activity-log'])

@section('title', 'Historique')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <style>
        .log-item { display: flex; gap: 14px; align-items: flex-start; padding: 14px 0; border-bottom: 1px solid var(--border, #333); }
        .log-item:last-child { border-bottom: none; }
        .log-icon {
            width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: var(--bg-secondary, rgba(255,255,255,0.08));
        }
        .log-icon svg { width: 16px; height: 16px; }
        .log-icon.created { color: var(--color-success, #5CBE8D); }
        .log-icon.validated { color: var(--color-success, #5CBE8D); }
        .log-icon.updated { color: var(--color-primary, #129850); }
        .log-icon.rejected, .log-icon.deleted { color: var(--color-danger, #E5484D); }
        .log-desc { font-size: 14px; }
        .log-meta { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <h1>Historique</h1>
        <p>Journal des actions effectuées sur les comptes fédérations, DSHN et administrateurs</p>
    </div>

    <div class="card">
        @if ($logs->isEmpty())
            <x-empty-state icon="clock" title="Aucune action enregistrée pour le moment." />
        @else
            @foreach ($logs as $log)
                @php
                    $icons = [
                        'created' => '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
                        'validated' => '<polyline points="20 6 9 17 4 12"/>',
                        'rejected' => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
                        'updated' => '<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>',
                        'deleted' => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>',
                    ];
                @endphp
                <div class="log-item">
                    <div class="log-icon {{ $log->action }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icons[$log->action] ?? $icons['updated'] !!}</svg>
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

            <div style="margin-top: 20px;">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
