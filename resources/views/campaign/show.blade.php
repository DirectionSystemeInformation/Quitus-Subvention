@extends('layouts.dashboard', ['active' => 'campagnes'])

@section('title', 'Campagne '.$campaign->annee_n1)

@php
    $user = auth()->user();
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <style>
        .ponderation-table th, .ponderation-table td { font-size: 12px; padding: 6px; white-space: nowrap; }
        .ponderation-table .critere-input { width: 52px; text-align: center; }
        .ponderation-table thead tr:first-child th { text-align: center; background: var(--bg-secondary); }
        .campaign-stepper { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px; }
        .campaign-step { padding: 6px 12px; border-radius: var(--radius-full); font-size: 12px; font-weight: 600; background: var(--bg-card); border: 1px solid var(--border); color: var(--text-muted); }
        .campaign-step.is-done { background: rgba(29, 170, 94, 0.12); border-color: var(--color-primary-light); color: var(--color-primary); }
        .campaign-step.is-current { background: var(--color-primary-gradient); border-color: transparent; color: #1c1c1e; }
        .repartition-bareme { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 12px; margin-bottom: 20px; }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="{{ route('campagnes.index') }}">Campagnes</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">{{ $campaign->annee_n1 }}</span>
        </nav>
        <h1>Campagne {{ $campaign->annee_n1 }}</h1>
        <p>Étape {{ $campaign->etape }} sur 12 — {{ $campaign->etapeLabel() }}</p>
    </div>

    <div class="campaign-stepper">
        @foreach (range(3, 12) as $step)
            <span class="campaign-step {{ $step < $campaign->etape ? 'is-done' : ($step === $campaign->etape ? 'is-current' : '') }}">
                {{ $step }}. {{ \App\Models\Campaign::labelForEtape($step) }}
            </span>
        @endforeach
    </div>

    {{-- Étape 3 : Traitement --}}
    @if ($campaign->etape === 3 && $user->isDshn())
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Traitement des rapports reçus</h2>
            </div>
            <p class="strength-text">Cette action retient, pour la pondération, toutes les fédérations actives ayant leur rapport d'activité ({{ $campaign->annee_n1 - 1 }}) et leur programme budgétisé ({{ $campaign->annee_n1 }}) validés.</p>
            <form method="POST" action="{{ route('campagnes.traitement', $campaign) }}" class="btn-group">
                @csrf
                <button type="submit" class="btn primary">Lancer la pondération</button>
            </form>
        </div>
    @endif

    {{-- Étape 4 : Pondération --}}
    @if ($campaign->etape === 4 && $user->isDshn())
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Pondération des activités</h2>
            </div>
            @if ($campaign->allocations->isEmpty())
                <x-empty-state icon="list" title="Aucune fédération retenue pour cette campagne." />
            @else
                @php
                    $points = fn ($valeur) => rtrim(rtrim(number_format((float) $valeur, 2, ',', ' '), '0'), ',');
                @endphp
                <p class="strength-text" style="margin-bottom: 16px;">Saisissez les points obtenus par chaque fédération, critère par critère. Les totaux de rubrique et la catégorie se recalculent à mesure.</p>
                <form method="POST" action="{{ route('campagnes.ponderation.update', $campaign) }}" id="ponderationForm">
                    @csrf
                    <div class="table-responsive">
                    <table class="ponderation-grille" id="ponderationTable">
                        <thead>
                            <tr>
                                <th class="pg-num">N°</th>
                                <th class="pg-rubrique">Rubriques</th>
                                <th class="pg-critere">Critères</th>
                                <th class="pg-bareme">Pondération</th>
                                @foreach ($campaign->allocations as $allocation)
                                    <th class="pg-fede" title="{{ $allocation->federation->federation_name }}">
                                        <span>{{ $allocation->federation->federation_name }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php $numero = 1; @endphp
                            @foreach ($rubriques as $rubriqueIndex => $rubrique)
                                @foreach ($rubrique['criteres'] as $critereIndex => $critere)
                                    <tr>
                                        <td class="pg-num">{{ $numero++ }}</td>
                                        @if ($critereIndex === 0)
                                            <td class="pg-rubrique" rowspan="{{ count($rubrique['criteres']) }}">{{ $rubrique['rubrique'] }}</td>
                                        @endif
                                        <td class="pg-critere">{{ $critere['label'] }}</td>
                                        <td class="pg-bareme">{{ $points($critere['max']) }}</td>
                                        @foreach ($campaign->allocations as $allocation)
                                            <td class="pg-fede">
                                                <input type="number" step="0.5" min="0" max="{{ $critere['max'] }}"
                                                    name="scores[{{ $allocation->id }}][{{ $critere['slug'] }}]"
                                                    aria-label="{{ $allocation->federation->federation_name }} — {{ $critere['label'] }} (sur {{ $critere['max'] }})"
                                                    value="{{ $allocation->criteres_scores[$critere['slug']] ?? '' }}"
                                                    data-allocation="{{ $allocation->id }}" data-rubrique="{{ $rubriqueIndex }}"
                                                    class="critere-input">
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                <tr class="pg-total-row">
                                    <td colspan="3" class="pg-libelle-total">Total {{ $rubrique['rubrique'] }}</td>
                                    <td class="pg-bareme">{{ $points($rubrique['rubrique_max']) }}</td>
                                    @foreach ($campaign->allocations as $allocation)
                                        <td class="pg-fede" data-total-rubrique="{{ $rubriqueIndex }}" data-allocation="{{ $allocation->id }}">0</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="pg-grand-total">
                                <td colspan="3" class="pg-libelle-total">Total</td>
                                <td class="pg-bareme">{{ $points($pointsMax) }}</td>
                                @foreach ($campaign->allocations as $allocation)
                                    <td class="pg-fede" data-total-general data-allocation="{{ $allocation->id }}">{{ $points($allocation->score_total ?? 0) }}</td>
                                @endforeach
                            </tr>
                            <tr class="pg-categorie-row">
                                <td colspan="3" class="pg-libelle-total">Catégorie</td>
                                <td class="pg-bareme"></td>
                                @foreach ($campaign->allocations as $allocation)
                                    <td class="pg-fede {{ $allocation->categorie ? 'cat-'.strtolower($allocation->categorie) : '' }}"
                                        data-categorie data-allocation="{{ $allocation->id }}">{{ $allocation->categorie ?? '—' }}</td>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn primary">Enregistrer les scores</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('campagnes.ponderation.confirm', $campaign) }}" class="btn-group" style="margin-top: 12px;">
                    @csrf
                    <button type="submit" class="btn">Valider la pondération et calculer les catégories</button>
                </form>
            @endif
        </div>
    @endif

    {{-- Étape 5 et suivantes : récapitulatif par rubrique --}}
    @if ($campaign->etape >= 5 && $user->isDshn() && $campaign->allocations->isNotEmpty())
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">{{ $campaign->etape === 5 ? 'Catégorisation des fédérations' : 'Récapitulatif par rubrique' }}</h2>
            </div>
            <p class="strength-text" style="margin-bottom: 16px;">
                Sous-total de chaque rubrique, score sur {{ 0 + $pointsMax }} points et catégorie qui en découle.
            </p>

            <x-ponderation-recap :rubriques="$rubriques" :lignes="$recapLignes" :points-max="$pointsMax" />

            @if ($campaign->etape === 5)
                <form method="POST" action="{{ route('campagnes.categorisation.confirm', $campaign) }}" class="btn-group" style="margin-top: 20px;">
                    @csrf
                    <button type="submit" class="btn primary">Confirmer et passer à la répartition</button>
                </form>
            @endif
        </div>
    @elseif ($campaign->etape === 5 && $user->isDshn())
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Catégorisation des fédérations</h2>
            </div>
            <x-empty-state icon="users" title="Aucune fédération retenue pour cette campagne." :compact="true" />
            <form method="POST" action="{{ route('campagnes.categorisation.confirm', $campaign) }}" class="btn-group" style="margin-top: 16px;">
                @csrf
                <button type="submit" class="btn primary">Confirmer et passer à la répartition</button>
            </form>
        </div>
    @endif

    {{-- Étape 6 : Répartition --}}
    @if ($campaign->etape === 6 && $user->isDshn())
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Répartition de la subvention</h2>
            </div>
            <p class="strength-text">Définissez le montant attribué à chaque catégorie ajustée ; le montant proposé de chaque fédération en est déduit automatiquement.</p>
            <form method="POST" action="{{ route('campagnes.repartition.update', $campaign) }}">
                @csrf
                <div class="repartition-bareme">
                    @foreach ($paliers as $palier)
                        <div class="form-group">
                            <label class="form-label">{{ $palier }}</label>
                            <input type="number" name="bareme[{{ $palier }}]" class="form-input" min="0" step="1000"
                                value="{{ $campaign->bareme_repartition[$palier] ?? '' }}">
                        </div>
                    @endforeach
                </div>

                <div class="table-responsive">
                <table class="market-table classement-federations">
                    <thead>
                        <tr>
                            <th class="cl-num">N°</th>
                            <th>Fédérations sportives et de loisirs</th>
                            <th class="cl-points">Nbre de points</th>
                            <th class="cl-cat">Catégories</th>
                            <th class="cl-cat">Catégorie ajustée</th>
                            <th class="cl-montant">Montant proposé</th>
                            <th class="cl-override">Surcharge manuelle (optionnel)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($campaign->allocations as $index => $allocation)
                            <tr>
                                <td class="cl-num">{{ $index + 1 }}</td>
                                <td>{{ $allocation->federation->federation_name }}</td>
                                <td class="cl-points">{{ rtrim(rtrim(number_format((float) ($allocation->score_total ?? 0), 2, ',', ' '), '0'), ',') }}</td>
                                <td class="cl-cat {{ $allocation->categorie ? 'cat-'.strtolower($allocation->categorie) : '' }}">{{ $allocation->categorie ?? '—' }}</td>
                                <td class="cl-cat">{{ $allocation->categorie_ajustee ?? '—' }}</td>
                                <td class="cl-montant">{{ $allocation->montant_propose !== null ? number_format($allocation->montant_propose, 0, ',', ' ').' FCFA' : '—' }}</td>
                                <td class="cl-override"><input type="number" name="overrides[{{ $allocation->id }}]" class="form-input" min="0" step="1000" placeholder="Barème par défaut" aria-label="Surcharge manuelle du montant pour {{ $allocation->federation->federation_name }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn primary">Enregistrer la répartition</button>
                </div>
            </form>
            <form method="POST" action="{{ route('campagnes.submit-dg', $campaign) }}" class="btn-group" style="margin-top: 12px;"
                data-confirm="Soumettre la répartition au Directeur Général ? Cette action est irréversible sans son avis.">
                @csrf
                <button type="submit" class="btn">Soumettre au Directeur Général</button>
            </form>
        </div>
    @endif

    {{-- Étape 7 : Pré-validation DG --}}
    @if ($campaign->etape === 7)
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Pré-validation du Directeur Général</h2>
            </div>
            @if ($campaign->dg_rejection_reason && $campaign->dg_decision === 'rejete')
                <p class="strength-text" style="color: var(--color-danger, #E5484D);">Précédent motif de rejet : {{ $campaign->dg_rejection_reason }}</p>
            @endif
            @if ($user->isDg())
                <div class="btn-group">
                    <form method="POST" action="{{ route('campagnes.dg.validate', $campaign) }}">
                        @csrf
                        <button type="submit" class="btn primary">Valider la répartition</button>
                    </form>
                    <button type="button" class="btn danger js-reject-reason" data-action="{{ route('campagnes.dg.reject', $campaign) }}">Rejeter</button>
                </div>
            @else
                <p class="strength-text">En attente de la décision du Directeur Général.</p>
            @endif
        </div>
    @endif

    {{-- Étape 8 : Arbitrage --}}
    @if ($campaign->etape === 8 && $user->isComiteArbitrage())
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Arbitrage de la répartition</h2>
            </div>
            @if ($campaign->ministre_rejection_reason && $campaign->ministre_decision === 'rejete')
                <p class="strength-text" style="color: var(--color-danger, #E5484D);">Précédent motif de rejet du Ministre : {{ $campaign->ministre_rejection_reason }}</p>
            @endif
            <form method="POST" action="{{ route('campagnes.arbitrage.update', $campaign) }}">
                @csrf
                <div class="table-responsive">
                <table class="market-table">
                    <thead>
                        <tr>
                            <th>Fédération</th>
                            <th>Catégorie</th>
                            <th>Montant proposé</th>
                            <th>Montant arbitré</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($campaign->allocations as $allocation)
                            <tr>
                                <td>{{ $allocation->federation->federation_name }}</td>
                                <td>{{ $allocation->categorie_ajustee ?? '—' }}</td>
                                <td>{{ $allocation->montant_propose !== null ? number_format($allocation->montant_propose, 0, ',', ' ').' FCFA' : '—' }}</td>
                                <td>
                                    <input type="number" name="montants[{{ $allocation->id }}]" class="form-input" min="0" step="1000"
                                        value="{{ $allocation->montant_arbitre ?? $allocation->montant_propose }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn primary">Enregistrer l'arbitrage</button>
                </div>
            </form>
            <form method="POST" action="{{ route('campagnes.arbitrage.finalize', $campaign) }}" class="btn-group" style="margin-top: 12px;"
                data-confirm="Finaliser l'arbitrage et soumettre au Ministre ?">
                @csrf
                <button type="submit" class="btn">Finaliser l'arbitrage</button>
            </form>
        </div>
    @endif

    {{-- Étape 9 : Validation Ministre --}}
    @if ($campaign->etape === 9)
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Validation du Ministre</h2>
            </div>
            @if ($user->isMinistre())
                <div class="btn-group">
                    <form method="POST" action="{{ route('campagnes.ministre.validate', $campaign) }}"
                        data-confirm="Valider définitivement la répartition de la subvention pour {{ $campaign->annee_n1 }} ?">
                        @csrf
                        <button type="submit" class="btn primary">Valider la répartition</button>
                    </form>
                    <button type="button" class="btn danger js-reject-reason" data-action="{{ route('campagnes.ministre.reject', $campaign) }}">Rejeter</button>
                </div>
            @else
                <p class="strength-text">En attente de la décision du Ministre.</p>
            @endif
        </div>
    @endif

    {{-- Étape 10 : Session d'arbitrage --}}
    @if ($campaign->etape === 10 && $user->isComiteArbitrage())
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Session d'arbitrage avec les fédérations</h2>
            </div>
            <form method="POST" action="{{ route('campagnes.session.organize', $campaign) }}" class="form-grid">
                @csrf
                <div class="form-group">
                    <label class="form-label">Date de la session</label>
                    <input type="date" name="session_arbitrage_date" class="form-input" required>
                </div>
                <div class="form-group full-width">
                    <button type="submit" class="btn primary">Marquer la session comme organisée</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Étape 11+ : Réception des réaménagements et délivrance du quitus --}}
    @if ($campaign->etape >= 11 && $user->isDshn())
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Programmes réaménagés et délivrance du quitus</h2>
            </div>
            <div class="table-responsive">
            <table class="market-table">
                <thead>
                    <tr>
                        <th>Fédération</th>
                        <th>Montant final</th>
                        <th>Réaménagement</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($campaign->allocations as $allocation)
                        @php $reamenage = $allocation->federation->reports->first(); @endphp
                        <tr>
                            <td>{{ $allocation->federation->federation_name }}</td>
                            <td>{{ $allocation->montant_final !== null ? number_format($allocation->montant_final, 0, ',', ' ').' FCFA' : '—' }}</td>
                            <td>
                                @if ($reamenage)
                                    <x-status-badge :status="$reamenage->status" />
                                @else
                                    <x-status-badge status="manquant" />
                                @endif
                            </td>
                            <td>
                                @if ($allocation->quitus_delivered_at)
                                    <a href="{{ route('campagnes.quitus.download', [$campaign, $allocation->federation]) }}" class="security-btn primary">Télécharger le quitus</a>
                                @elseif ($reamenage && $reamenage->status === 'valide')
                                    <form method="POST" action="{{ route('campagnes.quitus.deliver', [$campaign, $allocation->federation]) }}"
                                        data-confirm="Délivrer le quitus de déblocage de subvention à {{ $allocation->federation->federation_name }} ?">
                                        @csrf
                                        <button type="submit" class="security-btn primary">Délivrer le quitus</button>
                                    </form>
                                @else
                                    <span class="strength-text">En attente du réaménagement</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    @endif

    {{-- Tableau récapitulatif, toujours visible --}}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Récapitulatif des fédérations</h2>
        </div>
        @if ($campaign->allocations->isEmpty())
            <x-empty-state icon="users" title="Aucune fédération retenue pour cette campagne." />
        @else
            <div class="table-responsive">
            <table class="market-table">
                <thead>
                    <tr>
                        <th>Fédération</th>
                        <th>Score</th>
                        <th>Catégorie</th>
                        <th>Montant proposé</th>
                        <th>Montant arbitré</th>
                        <th>Montant final</th>
                        <th>Quitus</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($campaign->allocations as $allocation)
                        <tr>
                            <td>{{ $allocation->federation->federation_name }}</td>
                            <td>{{ $allocation->score_total ?? '—' }}</td>
                            <td>{{ $allocation->categorie_ajustee ?? '—' }}</td>
                            <td>{{ $allocation->montant_propose !== null ? number_format($allocation->montant_propose, 0, ',', ' ').' FCFA' : '—' }}</td>
                            <td>{{ $allocation->montant_arbitre !== null ? number_format($allocation->montant_arbitre, 0, ',', ' ').' FCFA' : '—' }}</td>
                            <td>{{ $allocation->montant_final !== null ? number_format($allocation->montant_final, 0, ',', ' ').' FCFA' : '—' }}</td>
                            <td>{{ $allocation->quitus_delivered_at ? 'Délivré' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script id="ponderationPaliers" type="application/json">@json($paliersDetail ?? [])</script>
    <script>
        (function () {
            const table = document.getElementById('ponderationTable');
            if (!table) return;

            // Paliers tries par seuil croissant : la categorie d'un score est
            // celle du dernier palier dont le seuil ne le depasse pas.
            const paliers = JSON.parse(document.getElementById('ponderationPaliers').textContent)
                .slice().sort((a, b) => a.seuil_min - b.seuil_min);

            const formate = (valeur) => (Math.round(valeur * 100) / 100).toString().replace('.', ',');

            function categoriePour(total) {
                if (total <= 0) return null;

                let trouve = null;
                paliers.forEach(function (palier) {
                    if (total >= palier.seuil_min) trouve = palier;
                });

                return trouve ? trouve.categorie : null;
            }

            function recalculer(allocationId) {
                const inputs = table.querySelectorAll('.critere-input[data-allocation="' + allocationId + '"]');
                const totauxRubrique = {};
                let total = 0;

                inputs.forEach(function (input) {
                    const valeur = parseFloat(input.value) || 0;
                    const rubrique = input.dataset.rubrique;
                    totauxRubrique[rubrique] = (totauxRubrique[rubrique] || 0) + valeur;
                    total += valeur;
                });

                Object.keys(totauxRubrique).forEach(function (rubrique) {
                    const cellule = table.querySelector('[data-total-rubrique="' + rubrique + '"][data-allocation="' + allocationId + '"]');
                    if (cellule) cellule.textContent = formate(totauxRubrique[rubrique]);
                });

                const cellTotal = table.querySelector('[data-total-general][data-allocation="' + allocationId + '"]');
                if (cellTotal) cellTotal.textContent = formate(total);

                const cellCategorie = table.querySelector('[data-categorie][data-allocation="' + allocationId + '"]');
                if (cellCategorie) {
                    const categorie = categoriePour(total);
                    cellCategorie.textContent = categorie || '—';
                    cellCategorie.className = 'pg-fede' + (categorie ? ' cat-' + categorie.toLowerCase() : '');
                }
            }

            const allocations = new Set();
            table.querySelectorAll('.critere-input').forEach((input) => allocations.add(input.dataset.allocation));
            allocations.forEach(recalculer);

            table.addEventListener('input', function (e) {
                if (!e.target.classList.contains('critere-input')) return;
                recalculer(e.target.dataset.allocation);
            });
        })();
    </script>
@endpush
