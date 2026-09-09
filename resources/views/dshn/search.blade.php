@extends('layouts.dashboard', ['active' => 'search'])

@section('title', 'Recherche')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <h1>Recherche</h1>
        <p>Fédérations, rapports{{ auth()->user()->isAdmin() ? ' et activités' : '' }}</p>
    </div>

    <form method="GET" action="{{ role_route('search.index') }}" class="topbar-search" style="max-width: 100%; margin-bottom: 24px;">
        <svg class="topbar-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="search" name="q" placeholder="Rechercher une fédération, un rapport, une activité..." value="{{ $query }}" autofocus>
    </form>

    @if ($query === '')
        <x-empty-state icon="search" title="Saisissez un terme pour lancer la recherche." />
    @else
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Fédérations ({{ $federations->count() }})</h2>
            </div>
            @if ($federations->isEmpty())
                <x-empty-state icon="users" title="Aucune fédération trouvée." :compact="true" />
            @else
                <div class="transaction-list">
                    @foreach ($federations as $federation)
                        <a href="{{ role_route('federations.show', $federation) }}" class="transaction-item" style="text-decoration:none; color:inherit;">
                            <div class="transaction-icon transfer">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                            <div class="transaction-details">
                                <span class="transaction-title">{{ $federation->federation_name }}</span>
                                <span class="transaction-date">{{ $federation->email }}</span>
                            </div>
                            <div class="transaction-amount">
                                <x-status-badge :status="$federation->status" />
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Rapports ({{ $reports->count() }})</h2>
            </div>
            @if ($reports->isEmpty())
                <x-empty-state icon="inbox" title="Aucun rapport trouvé." :compact="true" />
            @else
                <div class="transaction-list">
                    @foreach ($reports as $report)
                        <a href="{{ route('activity-form.show', $report) }}" class="transaction-item" style="text-decoration:none; color:inherit;">
                            <div class="transaction-icon transfer">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                            </div>
                            <div class="transaction-details">
                                <span class="transaction-title">{{ $report->user->federation_name }}</span>
                                <span class="transaction-date">{{ $report->typeLabel() }} ({{ $report->year }})</span>
                            </div>
                            <div class="transaction-amount">
                                <x-status-badge :status="$report->status" />
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if (auth()->user()->isAdmin())
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Activités ({{ $activities->count() }})</h2>
                </div>
                @if ($activities->isEmpty())
                    <x-empty-state icon="list" title="Aucune activité trouvée." :compact="true" />
                @else
                    <div class="transaction-list">
                        @foreach ($activities as $activity)
                            <a href="{{ route('dgf.activities.show', $activity) }}" class="transaction-item" style="text-decoration:none; color:inherit;">
                                <div class="transaction-icon transfer">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                </div>
                                <div class="transaction-details">
                                    <span class="transaction-title">{{ $activity->designation }}</span>
                                    <span class="transaction-date">{{ $activity->user->federation_name }} — {{ $activity->year }}</span>
                                </div>
                                <div class="transaction-amount">
                                    <x-status-badge :status="$activity->status" />
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    @endif
@endsection
