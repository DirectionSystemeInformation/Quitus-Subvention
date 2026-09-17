@extends('layouts.dashboard', ['active' => 'campagnes'])

@section('title', 'Campagne '.$campaign->annee_n1)

@php
    $user = auth()->user();

    // Le classement n'est un document qu'une fois les montants repartis.
    $repartitionEnregistree = $campaign->allocations->contains(fn ($allocation) => $allocation->montant_propose !== null);

    // Les montants ne sont definitifs qu'une fois le Ministre passe : c'est sa
    // validation qui reporte l'arbitrage en montant final.
    $repartitionValideeMinistre = $campaign->ministre_decision === 'valide';
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
                <form method="POST" action="{{ route('campagnes.ponderation.update', $campaign) }}" id="ponderationForm">
                    @csrf
                    <div class="table-responsive">
                    <table class="market-table pb-table ponderation-table" id="ponderationTable">
                        <thead>
                            <tr>
                                <th rowspan="2">Fédération</th>
                                @foreach ($rubriques as $rubrique)
                                    <th colspan="{{ count($rubrique['criteres']) }}">{{ $rubrique['rubrique'] }} ({{ $rubrique['rubrique_max'] }})</th>
                                @endforeach
                                <th rowspan="2">Total</th>
                            </tr>
                            <tr>
                                @foreach ($criteres as $critere)
                                    <th scope="col" class="criterion-heading">
                                        <details>
                                            <summary>{{ \Illuminate\Support\Str::limit($critere['label'], 36) }} · /{{ rtrim(rtrim(number_format($critere['max'], 1), '0'), '.') }}</summary>
                                            <span>{{ $critere['label'] }}</span>
                                        </details>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($campaign->allocations as $allocation)
                                <tr data-allocation-row data-allocation-id="{{ $allocation->id }}">
                                    <td>{{ $allocation->federation->federation_name }}</td>
                                    @foreach ($criteres as $critere)
                                        <td>
                                            <input type="number" step="0.5" min="0" max="{{ $critere['max'] }}"
                                                name="scores[{{ $allocation->id }}][{{ $critere['slug'] }}]"
                                                aria-label="{{ $allocation->federation->federation_name }} — {{ $critere['label'] }} (sur {{ $critere['max'] }})"
                                                value="{{ $allocation->criteres_scores[$critere['slug']] ?? '' }}"
                                                class="pb-num critere-input">
                                        </td>
                                    @endforeach
                                    <td class="ponderation-total" data-total>{{ $allocation->score_total ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn primary">Enregistrer les scores</button>
                    </div>
                </form>

                @if ($campaign->allocations->contains(fn ($allocation) => filled($allocation->criteres_scores)))
                    <div class="btn-group" style="margin-top: 12px;">
                        <button type="button" class="btn js-open-modal" data-modal="ponderationGrilleModal">Pondération par activité par fédération</button>
                        <button type="button" class="btn js-open-modal" data-modal="ponderationRecapModal">Récapitulatif par rubrique par fédération</button>
                    </div>
                @endif

                <form method="POST" action="{{ route('campagnes.ponderation.confirm', $campaign) }}" class="btn-group" style="margin-top: 12px;">
                    @csrf
                    <button type="submit" class="btn">Valider la pondération et calculer les catégories</button>
                </form>
            @endif
        </div>
    @endif

    {{-- Étape 5 et suivantes : documents de référence de la campagne --}}
    @if ($campaign->etape >= 5 && $user->isDshn() && $campaign->allocations->isNotEmpty())
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">{{ $campaign->etape === 5 ? 'Catégorisation des fédérations' : 'Documents de la campagne' }}</h2>
            </div>
            <p class="strength-text" style="margin-bottom: 16px;">
                @if ($campaign->etape === 5)
                    Les catégories sont calculées à partir du score de pondération, sur {{ 0 + $pointsMax }} points. Consultez le détail avant de confirmer.
                @else
                    Les tableaux de référence de la campagne, consultables et téléchargeables au format Excel.
                @endif
            </p>

            <div class="btn-group">
                <button type="button" class="btn js-open-modal" data-modal="ponderationGrilleModal">Pondération par activité par fédération</button>
                <button type="button" class="btn js-open-modal" data-modal="ponderationRecapModal">Récapitulatif par rubrique par fédération</button>
                @if ($repartitionEnregistree)
                    <button type="button" class="btn js-open-modal" data-modal="classementModal">Classement par fédération</button>
                @endif
                @if ($repartitionValideeMinistre)
                    <button type="button" class="btn js-open-modal" data-modal="repartitionDefinitiveModal">Répartition définitive par fédération</button>
                @endif
            </div>

            @if ($campaign->etape === 5)
                <form method="POST" action="{{ route('campagnes.categorisation.confirm', $campaign) }}" class="btn-group" style="margin-top: 16px;">
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

                <x-classement-federations :allocations="$campaign->allocations" :avec-surcharge="true" />

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
    {{-- Grille officielle, en consultation : elle reprend les scores enregistrés,
         pas ceux en cours de saisie. --}}
    @if ($campaign->etape >= 4 && $user->isDshn() && $campaign->allocations->isNotEmpty())
        <div class="modal-overlay js-table-modal" id="ponderationGrilleModal" role="dialog" aria-modal="true" aria-labelledby="ponderationGrilleTitre">
            <div class="modal-box modal-box-large">
                <div class="modal-head">
                    <h3 id="ponderationGrilleTitre">Pondération par activité par fédération</h3>
                    <div class="modal-head-actions">
                        <a href="{{ route('campagnes.ponderation.export', $campaign) }}" class="btn btn-sm">Télécharger en Excel</a>
                        <button type="button" class="btn btn-sm js-close-modal">Fermer</button>
                    </div>
                </div>
                <x-ponderation-grille :rubriques="$rubriques" :allocations="$campaign->allocations" :points-max="$pointsMax" />
            </div>
        </div>

        <div class="modal-overlay js-table-modal" id="ponderationRecapModal" role="dialog" aria-modal="true" aria-labelledby="ponderationRecapTitre">
            <div class="modal-box modal-box-large">
                <div class="modal-head">
                    <h3 id="ponderationRecapTitre">Récapitulatif par rubrique par fédération</h3>
                    <div class="modal-head-actions">
                        <a href="{{ route('campagnes.recap.export', $campaign) }}" class="btn btn-sm">Télécharger en Excel</a>
                        <button type="button" class="btn btn-sm js-close-modal">Fermer</button>
                    </div>
                </div>
                <x-ponderation-recap :rubriques="$rubriques" :lignes="$recapLignes" :points-max="$pointsMax" />
            </div>
        </div>

        @if ($repartitionEnregistree)
            <div class="modal-overlay js-table-modal" id="classementModal" role="dialog" aria-modal="true" aria-labelledby="classementTitre">
                <div class="modal-box modal-box-large">
                    <div class="modal-head">
                        <h3 id="classementTitre">Classement par fédération</h3>
                        <div class="modal-head-actions">
                            <a href="{{ route('campagnes.classement.export', $campaign) }}" class="btn btn-sm">Télécharger en Excel</a>
                            <button type="button" class="btn btn-sm js-close-modal">Fermer</button>
                        </div>
                    </div>
                    <x-classement-federations :allocations="$campaign->allocations" />
                </div>
            </div>
        @endif

        @if ($repartitionValideeMinistre)
            <div class="modal-overlay js-table-modal" id="repartitionDefinitiveModal" role="dialog" aria-modal="true" aria-labelledby="repartitionDefinitiveTitre">
                <div class="modal-box modal-box-large">
                    <div class="modal-head">
                        <h3 id="repartitionDefinitiveTitre">Répartition définitive par fédération</h3>
                        <div class="modal-head-actions">
                            <a href="{{ route('campagnes.repartition-definitive.export', $campaign) }}" class="btn btn-sm">Télécharger en Excel</a>
                            <button type="button" class="btn btn-sm js-close-modal">Fermer</button>
                        </div>
                    </div>
                    <p class="strength-text" style="margin-bottom: 16px;">
                        Validée par le Ministre le {{ optional($campaign->ministre_decided_at)->locale('fr')->translatedFormat('d F Y') }}.
                    </p>
                    <x-classement-federations :allocations="$campaign->allocations" :avec-montants-finaux="true" />
                </div>
            </div>
        @endif
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            // Total par federation pendant la saisie des scores.
            const table = document.getElementById('ponderationTable');

            if (table) {
                function updateRowTotal(row) {
                    let total = 0;
                    row.querySelectorAll('.critere-input').forEach(function (input) {
                        total += parseFloat(input.value) || 0;
                    });
                    const cell = row.querySelector('[data-total]');
                    if (cell) cell.textContent = total.toFixed(2).replace(/\.00$/, '');
                }

                table.querySelectorAll('[data-allocation-row]').forEach(updateRowTotal);

                table.addEventListener('input', function (e) {
                    if (!e.target.classList.contains('critere-input')) return;
                    const row = e.target.closest('[data-allocation-row]');
                    if (row) updateRowTotal(row);
                });
            }

            // Tableaux de reference consultes en modale.
            const ouvrir = (modale) => { modale.classList.add('active'); document.body.style.overflow = 'hidden'; };
            const fermer = (modale) => { modale.classList.remove('active'); document.body.style.overflow = ''; };

            document.querySelectorAll('.js-open-modal').forEach(function (bouton) {
                bouton.addEventListener('click', function () {
                    const modale = document.getElementById(bouton.dataset.modal);
                    if (modale) ouvrir(modale);
                });
            });

            document.querySelectorAll('.modal-overlay.js-table-modal').forEach(function (modale) {
                modale.querySelectorAll('.js-close-modal').forEach((bouton) => bouton.addEventListener('click', () => fermer(modale)));
                modale.addEventListener('click', function (e) {
                    if (e.target === modale) fermer(modale);
                });
            });

            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                document.querySelectorAll('.modal-overlay.js-table-modal.active').forEach(fermer);
            });

        })();
    </script>
@endpush
