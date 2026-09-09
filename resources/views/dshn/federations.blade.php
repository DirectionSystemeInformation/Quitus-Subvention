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

    @if ($pending->isNotEmpty())
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2 class="card-title">Demandes en attente</h2>
                <button type="submit" form="bulkValidateForm" class="btn primary" id="bulkValidateBtn" disabled>Valider la sélection (<span id="bulkSelectedCount">0</span>)</button>
            </div>

            <form method="POST" action="{{ role_route('federations.bulk-validate') }}" id="bulkValidateForm">
                @csrf
            </form>

            <div class="table-responsive">
            <table class="market-table">
                <thead>
                    <tr>
                        <th style="width:32px;"><input type="checkbox" id="selectAllPending"></th>
                        <th>Fédération</th>
                        <th>N° arrêté</th>
                        <th>Email</th>
                        <th>Demandée le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pending as $federation)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $federation->id }}" form="bulkValidateForm" class="js-pending-checkbox"></td>
                            <td><a href="{{ role_route('federations.show', $federation) }}" class="table-link">{{ $federation->federation_name }}</a></td>
                            <td>{{ $federation->arrete_numero }}</td>
                            <td>{{ $federation->email }}</td>
                            <td>{{ $federation->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <form method="POST" action="{{ role_route('federations.validate', $federation) }}">
                                        @csrf
                                        <button type="submit" class="security-btn primary">Valider</button>
                                    </form>
                                    <button type="button" class="security-btn js-reject-reason" data-action="{{ role_route('federations.reject', $federation) }}">Rejeter</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Toutes les fédérations</h2>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <select class="form-select js-table-status-filter" data-target="federations-table" style="max-width: 170px;">
                    <option value="">Tous les statuts</option>
                    <option value="pending">En attente</option>
                    <option value="active">Actif</option>
                    <option value="rejected">Rejeté</option>
                </select>
                <input type="search" class="form-input js-table-search" data-target="federations-table" placeholder="Rechercher..." style="max-width: 260px;">
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
                        <td><a href="{{ role_route('federations.show', $federation) }}" class="table-link">{{ $federation->federation_name }}</a></td>
                        <td>{{ $federation->arrete_numero }}</td>
                        <td>{{ $federation->email }}</td>
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

    @if (auth()->user()->isDshn())
        <div class="modal-overlay" id="editFederationModal">
            <div class="modal-box">
                <h3>Modifier la fédération</h3>
                <form method="POST" id="editFederationForm">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label class="form-label">Dénomination de la fédération</label>
                        <input type="text" name="federation_name" id="editFedFederationName" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Numéro de l'arrêté de validation</label>
                        <input type="text" name="arrete_numero" id="editFedArreteNumero" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date de l'arrêté de validation</label>
                        <input type="date" name="arrete_date" id="editFedArreteDate" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="editFedEmail" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Statut</label>
                        <select name="status" id="editFedStatus" class="form-select">
                            <option value="pending">En attente</option>
                            <option value="active">Actif</option>
                            <option value="rejected">Rejeté</option>
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
