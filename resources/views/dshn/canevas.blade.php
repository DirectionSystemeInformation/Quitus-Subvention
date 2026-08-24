@extends('layouts.dashboard', ['active' => 'canevas'])

@section('title', 'Canevas')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <style>
        .canevas-axe { margin-bottom: 32px; }

        .canevas-item { display: flex; gap: 12px; align-items: flex-start; padding: 14px 0; border-bottom: 1px solid var(--border, #333); }
        .canevas-item:last-child { border-bottom: none; }
        .canevas-axe-header { padding-top: 0; margin-bottom: 12px; }

        .canevas-item form.canevas-edit-form { display: flex; gap: 10px; align-items: flex-start; flex: 1; }
        .canevas-code-input { width: 80px; flex-shrink: 0; }
        .canevas-label-input { flex: 1; min-height: 42px; resize: vertical; font-family: inherit; font-size: 14px; line-height: 1.4; padding: 10px 12px; }

        .canevas-item-actions { display: flex; gap: 4px; flex-shrink: 0; }

        .canevas-add-form { display: flex; gap: 10px; align-items: flex-start; margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border, #333); }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <h1>Canevas</h1>
        <p>Structure du programme d'activités budgétisé et du rapport d'activité — les fédérations voient toujours cette version en vigueur.</p>
    </div>

    @php
        $saveIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
        $trashIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>';
    @endphp

    @foreach ($axes as $axe)
        <div class="card canevas-axe">
            <div class="canevas-item canevas-axe-header">
                <form method="POST" action="{{ role_route('canevas.axes.update', $axe) }}" class="canevas-edit-form">
                    @csrf
                    @method('PUT')
                    <input type="text" name="code" class="form-input canevas-code-input" value="{{ $axe->code }}" required>
                    <textarea name="label" class="form-input canevas-label-input" required rows="2">{{ $axe->label }}</textarea>
                    <div class="canevas-item-actions">
                        <button type="submit" class="icon-btn" title="Enregistrer">{!! $saveIcon !!}</button>
                    </div>
                </form>
                <form method="POST" action="{{ role_route('canevas.axes.destroy', $axe) }}" data-confirm="Supprimer l'axe {{ $axe->code }} et tous ses sous-axes ?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="icon-btn danger" title="Supprimer l'axe">{!! $trashIcon !!}</button>
                </form>
            </div>

            @foreach ($axe->sousAxes as $sousAxe)
                <div class="canevas-item">
                    <form method="POST" action="{{ role_route('canevas.sous-axes.update', $sousAxe) }}" class="canevas-edit-form">
                        @csrf
                        @method('PUT')
                        <input type="text" name="code" class="form-input canevas-code-input" value="{{ $sousAxe->code }}" required>
                        <textarea name="label" class="form-input canevas-label-input" required rows="2">{{ $sousAxe->label }}</textarea>
                        <div class="canevas-item-actions">
                            <button type="submit" class="icon-btn" title="Enregistrer">{!! $saveIcon !!}</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ role_route('canevas.sous-axes.destroy', $sousAxe) }}" data-confirm="Supprimer le sous-axe {{ $sousAxe->code }} ?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-btn danger" title="Supprimer">{!! $trashIcon !!}</button>
                    </form>
                </div>
            @endforeach

            <form method="POST" action="{{ role_route('canevas.sous-axes.store', $axe) }}" class="canevas-add-form">
                @csrf
                <input type="text" name="code" class="form-input canevas-code-input" placeholder="Code (ex. {{ $axe->code }}.{{ $axe->sousAxes->count() + 1 }})" required>
                <textarea name="label" class="form-input canevas-label-input" placeholder="Libellé du sous-axe" required rows="2"></textarea>
                <button type="submit" class="btn primary" style="flex-shrink:0;">+ Ajouter</button>
            </form>
        </div>
    @endforeach

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Ajouter un axe</h2>
        </div>
        <form method="POST" action="{{ role_route('canevas.axes.store') }}" class="canevas-add-form">
            @csrf
            <input type="text" name="code" class="form-input canevas-code-input" placeholder="Code (ex. IV)" required>
            <textarea name="label" class="form-input canevas-label-input" placeholder="Libellé de l'axe" required rows="2"></textarea>
            <button type="submit" class="btn primary" style="flex-shrink:0;">+ Ajouter</button>
        </form>
    </div>
@endsection
