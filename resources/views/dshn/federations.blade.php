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

    <div class="{{ $pending->isNotEmpty() ? 'federations-layout' : '' }}">
        <div class="card federations-main">
            <div class="card-header">
                <h2 class="card-title">Toutes les fédérations</h2>
            </div>
            <div class="list-toolbar">
                <div class="list-toolbar-filter">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    <select class="js-table-status-filter" data-target="federations-table" aria-label="Filtrer par statut">
                        <option value="">Tous les statuts</option>
                        <option value="pending">En attente</option>
                        <option value="active">Actif</option>
                        <option value="rejected">Rejeté</option>
                    </select>
                </div>
                <div class="topbar-search">
                    <svg aria-hidden="true" class="topbar-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="search" class="js-table-search" data-target="federations-table" placeholder="Rechercher une fédération..." aria-label="Rechercher une fédération">
                </div>
            </div>
            <div class="table-responsive">
            <table class="market-table sortable" id="federations-table">
                <thead>
                    <tr>
                        <th data-sort="text">Fédération</th>
                        <th data-sort="text">N° arrêté</th>
                        <th data-sort="text">Email</th>
                        <th data-sort="text">Statut</th>
                        <th data-sort="number">Documents déposés</th>
                        @if (auth()->user()->isDshn())
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($others as $federation)
                        <tr data-search-row>
                            <td style="min-width: 220px;"><a href="{{ role_route('federations.show', $federation) }}" class="table-link">{{ $federation->federation_name }}</a></td>
                            <td>{{ $federation->arrete_numero }}</td>
                            <td class="cell-clamp" style="max-width: 140px;" title="{{ $federation->email }}">{{ $federation->email }}</td>
                            <td><x-status-badge :status="$federation->status" /></td>
                            <td>{{ $federation->reports()->count() }}</td>
                            @if (auth()->user()->isDshn())
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <button type="button" class="icon-btn js-edit-federation"
                                            data-id="{{ $federation->id }}"
                                            data-federation-name="{{ $federation->federation_name }}"
                                            data-arrete-numero="{{ $federation->arrete_numero }}"
                                            data-arrete-date="{{ optional($federation->arrete_date)->format('Y-m-d') }}"
                                            data-email="{{ $federation->email }}"
                                            data-status="{{ $federation->status }}"
                                            data-action="{{ role_route('federations.update', $federation) }}"
                                            title="Modifier">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                        <form method="POST" action="{{ role_route('federations.destroy', $federation) }}" data-confirm="Supprimer le compte de {{ $federation->federation_name }} ?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-btn danger" title="Supprimer">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"><x-empty-state icon="users" title="Aucune autre fédération enregistrée." /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            <x-empty-state icon="search" title="Aucun résultat." :compact="true" class="js-table-empty" data-target="federations-table" style="display:none;" />
        </div>

        @if ($pending->isNotEmpty())
            <div class="card federations-aside">
                <div class="card-header">
                    <h2 class="card-title">Demandes en attente</h2>
                    <span class="section-tab-count" style="background: var(--color-accent-gold-soft); color: #92720a; border-color: transparent;">{{ $pending->count() }}</span>
                </div>

                <form method="POST" action="{{ role_route('federations.bulk-validate') }}" id="bulkValidateForm">
                    @csrf
                </form>

                <label class="pending-select-all">
                    <input type="checkbox" id="selectAllPending">
                    Tout sélectionner
                </label>

                <div class="pending-list">
                    @foreach ($pending as $federation)
                        <div class="pending-item">
                            <div class="pending-item-top">
                                <input type="checkbox" name="ids[]" value="{{ $federation->id }}" form="bulkValidateForm" class="js-pending-checkbox">
                                <div class="pending-item-body">
                                    <a href="{{ role_route('federations.show', $federation) }}" class="table-link">{{ $federation->federation_name }}</a>
                                    <span class="pending-item-meta">{{ $federation->arrete_numero }} · {{ $federation->created_at->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            <div class="pending-item-actions">
                                <form method="POST" action="{{ role_route('federations.validate', $federation) }}">
                                    @csrf
                                    <button type="submit" class="security-btn primary">Valider</button>
                                </form>
                                <button type="button" class="security-btn js-reject-reason" data-action="{{ role_route('federations.reject', $federation) }}">Rejeter</button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="submit" form="bulkValidateForm" class="btn primary" id="bulkValidateBtn" disabled style="width: 100%; margin-top: 16px;">Valider la sélection (<span id="bulkSelectedCount">0</span>)</button>
            </div>
        @endif
    </div>

    @if (auth()->user()->isDshn())
        @php
            // Si la soumission précédente a échoué, on rouvre la fenêtre avec ce que
            // l'agent avait saisi (via old()) plutôt que de la laisser fermée ou de la
            // re-remplir avec les anciennes valeurs de la fédération.
            $federationEditFailed = $errors->any() && old('_federation_id');
        @endphp
        <div class="modal-overlay" id="editFederationModal" @if ($federationEditFailed) data-reopen="1" @endif>
            <div class="modal-box">
                <h3>Modifier la fédération</h3>
                <form method="POST" id="editFederationForm" action="{{ $federationEditFailed ? role_route('federations.update', old('_federation_id')) : '' }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_federation_id" id="editFedId" value="{{ old('_federation_id') }}">
                    <div class="form-group">
                        <label class="form-label">Dénomination de la fédération</label>
                        <input type="text" name="federation_name" id="editFedFederationName" class="form-input" value="{{ old('federation_name') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Numéro de l'arrêté de validation</label>
                        <input type="text" name="arrete_numero" id="editFedArreteNumero" class="form-input" value="{{ old('arrete_numero') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date de l'arrêté de validation</label>
                        <input type="date" name="arrete_date" id="editFedArreteDate" class="form-input" value="{{ old('arrete_date') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="editFedEmail" class="form-input" value="{{ old('email') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Statut</label>
                        <select name="status" id="editFedStatus" class="form-select">
                            <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Actif</option>
                            <option value="rejected" {{ old('status') === 'rejected' ? 'selected' : '' }}>Rejeté</option>
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
                    <div class="modal-actions">
                        <button type="button" class="btn" id="editFederationCancel">Annuler</button>
                        <button type="submit" class="btn primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            const modal = document.getElementById('editFederationModal');
            if (!modal) return;

            const form = document.getElementById('editFederationForm');

            document.querySelectorAll('.js-edit-federation').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    form.action = btn.dataset.action;
                    document.getElementById('editFedId').value = btn.dataset.id;
                    document.getElementById('editFedFederationName').value = btn.dataset.federationName;
                    document.getElementById('editFedArreteNumero').value = btn.dataset.arreteNumero;
                    document.getElementById('editFedArreteDate').value = btn.dataset.arreteDate;
                    document.getElementById('editFedEmail').value = btn.dataset.email;
                    document.getElementById('editFedStatus').value = btn.dataset.status;
                    modal.classList.add('active');
                });
            });

            document.getElementById('editFederationCancel').addEventListener('click', function () {
                modal.classList.remove('active');
            });

            modal.addEventListener('click', function (e) {
                if (e.target === modal) modal.classList.remove('active');
            });

            // La soumission précédente a échoué la validation : rouvrir la fenêtre avec
            // ce que l'agent avait saisi (rendu côté serveur via old()) au lieu de la
            // laisser fermée sans indication de la fédération concernée.
            if (modal.dataset.reopen === '1') {
                modal.classList.add('active');
            }
        })();

        (function () {
            const selectAll = document.getElementById('selectAllPending');
            if (!selectAll) return;

            const checkboxes = document.querySelectorAll('.js-pending-checkbox');
            const bulkBtn = document.getElementById('bulkValidateBtn');
            const countEl = document.getElementById('bulkSelectedCount');

            function updateBulkState() {
                const checked = document.querySelectorAll('.js-pending-checkbox:checked').length;
                countEl.textContent = checked;
                bulkBtn.disabled = checked === 0;
            }

            selectAll.addEventListener('change', function () {
                checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
                updateBulkState();
            });

            checkboxes.forEach(function (cb) {
                cb.addEventListener('change', function () {
                    if (!cb.checked) selectAll.checked = false;
                    updateBulkState();
                });
            });
        })();
    </script>
@endpush
