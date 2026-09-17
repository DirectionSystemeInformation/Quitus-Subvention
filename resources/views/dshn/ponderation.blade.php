@extends('layouts.dashboard', ['active' => 'ponderation'])

@section('title', 'Pondération')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <style>
        .pond-rubrique { margin-bottom: 24px; }

        .pond-item { display: flex; gap: 12px; align-items: flex-start; padding: 12px 0; border-bottom: 1px solid var(--border, #333); }
        .pond-item:last-child { border-bottom: none; }
        .pond-rubrique-header { padding-top: 0; margin-bottom: 8px; }

        .pond-edit-form { display: flex; gap: 10px; align-items: flex-start; flex: 1; }
        .pond-label-input { flex: 1; min-width: 0; }
        .pond-points-input { width: 90px; flex-shrink: 0; text-align: center; }
        .pond-item-actions { display: flex; gap: 4px; flex-shrink: 0; }

        .pond-add-form { display: flex; gap: 10px; align-items: flex-start; margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border, #333); }

        .pond-rubrique-total { font-size: 13px; color: var(--text-muted); white-space: nowrap; align-self: center; }

        .pond-paliers-table td { vertical-align: middle; }
        .pond-paliers-table .form-input { margin: 0; }
        .pond-code-input { width: 90px; }
        .pond-cat-input { width: 90px; }
        .pond-seuil-input { width: 110px; }
    </style>
@endpush

@section('content')
    @php
        $saveIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
        $trashIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>';

        $totalPoints = $rubriques->sum(fn ($rubrique) => $rubrique->criteres->sum('points_max'));
        $criteresCount = $rubriques->sum(fn ($rubrique) => $rubrique->criteres->count());
        $formatPoints = fn ($valeur) => rtrim(rtrim(number_format((float) $valeur, 2, ',', ' '), '0'), ',');
    @endphp

    <div class="page-header">
        <h1>Pondération</h1>
        <p>Grille de notation des fédérations et paliers de catégorisation. Une campagne fige la grille au lancement de sa pondération : modifier ce paramétrage n'affecte que les campagnes lancées ensuite.</p>
    </div>

    <div class="report-summary-stats" style="margin-bottom: 28px;">
        <div>
            <div class="report-summary-stat-value">{{ $formatPoints($totalPoints) }}</div>
            <div class="report-summary-stat-label">points au total</div>
        </div>
        <div>
            <div class="report-summary-stat-value">{{ $rubriques->count() }}</div>
            <div class="report-summary-stat-label">{{ $rubriques->count() > 1 ? 'rubriques' : 'rubrique' }}</div>
        </div>
        <div>
            <div class="report-summary-stat-value">{{ $criteresCount }}</div>
            <div class="report-summary-stat-label">{{ $criteresCount > 1 ? 'critères' : 'critère' }}</div>
        </div>
        <div>
            <div class="report-summary-stat-value">{{ $paliers->count() }}</div>
            <div class="report-summary-stat-label">{{ $paliers->count() > 1 ? 'paliers' : 'palier' }}</div>
        </div>
    </div>

    <h2 class="form-section-title">Grille de pondération</h2>

    @forelse ($rubriques as $rubrique)
        <div class="card pond-rubrique">
            <div class="pond-item pond-rubrique-header">
                <form method="POST" action="{{ role_route('ponderation.rubriques.update', $rubrique) }}" class="pond-edit-form">
                    @csrf
                    @method('PUT')
                    <input type="text" name="label" class="form-input pond-label-input" value="{{ $rubrique->label }}" aria-label="Libellé de la rubrique" required>
                    <span class="pond-rubrique-total">{{ $formatPoints($rubrique->criteres->sum('points_max')) }} pts</span>
                    <div class="pond-item-actions">
                        <button type="submit" class="icon-btn" title="Enregistrer">{!! $saveIcon !!}</button>
                    </div>
                </form>
                <form method="POST" action="{{ role_route('ponderation.rubriques.destroy', $rubrique) }}" data-confirm="Supprimer la rubrique « {{ $rubrique->label }} » et ses {{ $rubrique->criteres->count() }} critère(s) ?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="icon-btn danger" title="Supprimer la rubrique">{!! $trashIcon !!}</button>
                </form>
            </div>

            @foreach ($rubrique->criteres as $critere)
                <div class="pond-item">
                    <form method="POST" action="{{ role_route('ponderation.criteres.update', $critere) }}" class="pond-edit-form">
                        @csrf
                        @method('PUT')
                        <input type="text" name="label" class="form-input pond-label-input" value="{{ $critere->label }}" aria-label="Libellé du critère" required>
                        <input type="number" name="points_max" class="form-input pond-points-input" value="{{ 0 + $critere->points_max }}" min="0" max="100" step="0.5" aria-label="Points maximum" required>
                        <div class="pond-item-actions">
                            <button type="submit" class="icon-btn" title="Enregistrer">{!! $saveIcon !!}</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ role_route('ponderation.criteres.destroy', $critere) }}" data-confirm="Supprimer le critère « {{ $critere->label }} » ?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-btn danger" title="Supprimer le critère">{!! $trashIcon !!}</button>
                    </form>
                </div>
            @endforeach

            <form method="POST" action="{{ role_route('ponderation.criteres.store', $rubrique) }}" class="pond-add-form">
                @csrf
                <input type="text" name="label" class="form-input pond-label-input" placeholder="Libellé du critère" required>
                <input type="number" name="points_max" class="form-input pond-points-input" placeholder="Pts" min="0" max="100" step="0.5" aria-label="Points maximum" required>
                <button type="submit" class="btn primary" style="flex-shrink:0;">+ Ajouter</button>
            </form>
        </div>
    @empty
        <div class="card" style="margin-bottom: 24px;">
            <x-empty-state icon="list" title="Aucune rubrique — la pondération ne peut pas être lancée tant que la grille est vide." :compact="true" />
        </div>
    @endforelse

    <div class="card" style="margin-bottom: 32px;">
        <div class="card-header">
            <h2 class="card-title">Ajouter une rubrique</h2>
        </div>
        <form method="POST" action="{{ role_route('ponderation.rubriques.store') }}" class="pond-add-form" style="margin-top:0; padding-top:0; border-top:none;">
            @csrf
            <input type="text" name="label" class="form-input pond-label-input" placeholder="Libellé de la rubrique (ex. Gouvernance)" required>
            <button type="submit" class="btn primary" style="flex-shrink:0;">+ Ajouter</button>
        </form>
    </div>

    <h2 class="form-section-title">Paliers de catégorisation</h2>

    <div class="card" style="margin-bottom: 24px;">
        <p class="strength-text" style="margin-bottom: 16px;">
            Une fédération est classée dans le palier dont le seuil est le plus élevé sans dépasser son score, sur les {{ $formatPoints($totalPoints) }} points de la grille. Un score nul reste non classé.
        </p>

        @if ($paliers->isEmpty())
            <x-empty-state icon="list" title="Aucun palier défini." :compact="true" />
        @else
            <form method="POST" action="{{ role_route('ponderation.paliers.update') }}">
                @csrf
                @method('PUT')
                <div class="table-responsive">
                    <table class="market-table pond-paliers-table">
                        <thead>
                            <tr>
                                <th>Palier</th>
                                <th>Catégorie</th>
                                <th>À partir de</th>
                                <th>Plage</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($paliers as $index => $palier)
                                @php $suivant = $paliers[$index + 1] ?? null; @endphp
                                <tr>
                                    <td>
                                        <input type="text" name="paliers[{{ $palier->id }}][code]" class="form-input pond-code-input" value="{{ $palier->code }}" aria-label="Code du palier" required>
                                    </td>
                                    <td>
                                        <input type="text" name="paliers[{{ $palier->id }}][categorie]" class="form-input pond-cat-input" value="{{ $palier->categorie }}" aria-label="Catégorie principale" required>
                                    </td>
                                    <td>
                                        <input type="number" name="paliers[{{ $palier->id }}][seuil_min]" class="form-input pond-seuil-input" value="{{ 0 + $palier->seuil_min }}" min="0" max="100" step="0.5" aria-label="Seuil minimum" required>
                                    </td>
                                    <td class="strength-text">
                                        {{ $formatPoints($palier->seuil_min) }}
                                        @if ($suivant)
                                            à {{ $formatPoints($suivant->seuil_min) }} (exclu)
                                        @else
                                            et plus
                                        @endif
                                    </td>
                                    <td>
                                        <button type="submit" form="palier-delete-{{ $palier->id }}" class="icon-btn danger" title="Supprimer le palier">{!! $trashIcon !!}</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="btn-group" style="margin-top: 16px;">
                    <button type="submit" class="btn primary">Enregistrer les paliers</button>
                </div>
            </form>

            @foreach ($paliers as $palier)
                <form method="POST" action="{{ role_route('ponderation.paliers.destroy', $palier) }}" id="palier-delete-{{ $palier->id }}" data-confirm="Supprimer le palier {{ $palier->code }} ?">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Ajouter un palier</h2>
        </div>
        <form method="POST" action="{{ role_route('ponderation.paliers.store') }}" class="pond-add-form" style="margin-top:0; padding-top:0; border-top:none;">
            @csrf
            <input type="text" name="code" class="form-input pond-code-input" placeholder="Palier" required>
            <input type="text" name="categorie" class="form-input pond-cat-input" placeholder="Catégorie" required>
            <input type="number" name="seuil_min" class="form-input pond-seuil-input" placeholder="Seuil" min="0" max="100" step="0.5" required>
            <button type="submit" class="btn primary" style="flex-shrink:0;">+ Ajouter</button>
        </form>
    </div>
@endsection
