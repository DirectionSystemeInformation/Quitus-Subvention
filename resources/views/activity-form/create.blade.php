@extends('layouts.dashboard', ['active' => 'dashboard'])

@section('title', $title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <style>
        .pb-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        .pb-table th, .pb-table td { border: 1px solid var(--border, #333); padding: 8px; font-size: 13px; }
        .pb-table th { background: var(--bg-secondary, rgba(255,255,255,0.04)); text-align: left; }
        .pb-axe-row td { background: var(--color-primary, #129850); color: #1c1c1e; font-weight: 700; }
        .pb-sousaxe-row td { background: var(--bg-secondary, rgba(255,255,255,0.06)); font-weight: 600; }
        .pb-table input { width: 100%; background: transparent; border: none; color: inherit; font-size: 13px; padding: 4px; }
        .pb-table input:focus { outline: 1px solid var(--color-primary, #129850); background: rgba(18,152,80,0.08); }
        .pb-num { width: 100%; }
        .pb-col-num { width: 40px; text-align: center; }
        .pb-col-montant, .pb-col-contrib { width: 130px; }
        .pb-col-date { width: 130px; }
        .pb-col-actions { width: 36px; text-align: center; }
        .pb-add-row td { background: transparent; padding: 6px 8px; }
        .pb-add-btn { background: none; border: none; color: var(--color-primary, #129850); font-size: 13px; font-weight: 600; cursor: pointer; padding: 4px 0; }
        .pb-add-btn:hover { text-decoration: underline; }
        .pb-remove-btn { background: none; border: none; color: var(--color-danger, #E5484D); font-size: 16px; cursor: pointer; line-height: 1; padding: 4px; }
        .pb-remove-btn:hover { opacity: 0.7; }
        .pb-summary-bar {
            display: flex; gap: 40px; align-items: center;
            margin-bottom: 24px; position: sticky; top: 0; z-index: 10;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
@endpush

@section('content')
    @php $routePrefix = str_replace('_', '-', $type); @endphp

    <div class="page-header">
        <h1>{{ $title }}</h1>
        <p>Fédération : {{ auth()->user()->federation_name }} </p>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <form method="GET" action="{{ route($routePrefix.'.create') }}" style="display:flex;gap:12px;align-items:end;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Année</label>
                <input type="number" name="annee" class="form-input" value="{{ $year }}" min="2000" max="2100">
            </div>
            <button type="submit" class="btn">Changer d'année</button>
        </form>
    </div>

    <form method="POST" action="{{ route($routePrefix.'.store') }}" id="activityForm">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">

        <div class="card pb-summary-bar">
            <div style="flex: 1; min-width: 200px;">
                <div class="market-stat-label">Lignes remplies</div>
                <div class="market-stat-value" id="pbFilledCount">0</div>
                <div class="pb-progress-track">
                    <div class="pb-progress-fill" id="pbProgressFill"></div>
                </div>
            </div>
            <div>
                <div class="market-stat-label">Total général</div>
                <div class="market-stat-value" id="pbTotalAmount">0</div>
            </div>
        </div>

        @foreach ($axes as $axe)
            <div class="table-responsive">
            <table class="pb-table">
                <thead>
                    <tr class="pb-axe-row">
                        <td colspan="7">{{ $axe['label'] }}</td>
                    </tr>
                    <tr>
                        <th class="pb-col-num">N°</th>
                        <th>Désignation de l'activité</th>
                        <th class="pb-col-montant">Montant</th>
                        <th class="pb-col-contrib">Contribution des partenaires</th>
                        <th class="pb-col-date">Date</th>
                        <th>Observations</th>
                        <th class="pb-col-actions"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($axe['sous_axes'] as $sousAxe)
                        <tr class="pb-sousaxe-row" data-sous-axe-header="{{ $sousAxe['code'] }}">
                            <td colspan="7">{{ $sousAxe['code'] }}- {{ $sousAxe['label'] }}</td>
                        </tr>
                        @php
                            $existingForSousAxe = $existingLines->filter(fn ($l, $k) => str_starts_with($k, $sousAxe['code'].'|'));
                            $rowCount = max($sousAxe['lignes'], $existingForSousAxe->count());
                        @endphp
                        @for ($i = 1; $i <= $rowCount; $i++)
                            @php
                                $key = $sousAxe['code'].'-'.$i;
                                $existing = $existingLines->get($sousAxe['code'].'|'.$i);
                            @endphp
                            <tr class="pb-line-row" data-sous-axe-code="{{ $sousAxe['code'] }}">
                                <td class="pb-col-num">{{ $i }}</td>
                                <td>
                                    <input type="text" name="lignes[{{ $sousAxe['code'] }}][{{ $i }}][designation]" value="{{ old("lignes.{$sousAxe['code']}.$i.designation", $existing->designation ?? '') }}">
                                </td>
                                <td class="pb-col-montant">
                                    <input type="number" step="0.01" min="0" class="pb-num" name="lignes[{{ $sousAxe['code'] }}][{{ $i }}][montant]" value="{{ old("lignes.{$sousAxe['code']}.$i.montant", $existing->montant ?? '') }}">
                                </td>
                                <td class="pb-col-contrib">
                                    <input type="text" name="lignes[{{ $sousAxe['code'] }}][{{ $i }}][contribution_partenaires]" value="{{ old("lignes.{$sousAxe['code']}.$i.contribution_partenaires", $existing->contribution_partenaires ?? '') }}">
                                </td>
                                <td class="pb-col-date">
                                    <input type="date" name="lignes[{{ $sousAxe['code'] }}][{{ $i }}][date]" value="{{ old("lignes.{$sousAxe['code']}.$i.date", optional($existing?->date)->format('Y-m-d')) }}">
                                </td>
                                <td>
                                    <input type="text" name="lignes[{{ $sousAxe['code'] }}][{{ $i }}][observations]" value="{{ old("lignes.{$sousAxe['code']}.$i.observations", $existing->observations ?? '') }}">
                                </td>
                                <td class="pb-col-actions">
                                    <button type="button" class="pb-remove-btn" title="Supprimer la ligne">&times;</button>
                                </td>
                            </tr>
                        @endfor
                        <tr class="pb-add-row" data-sous-axe-code="{{ $sousAxe['code'] }}" data-next-index="{{ $rowCount + 1 }}">
                            <td colspan="7">
                                <button type="button" class="pb-add-btn" data-sous-axe="{{ $sousAxe['code'] }}">+ Ajouter une ligne</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endforeach

        <div class="btn-group">
            <button type="submit" class="btn primary">Soumettre</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        function pbUpdateSummary() {
            var form = document.getElementById('activityForm');
            var montantInputs = form.querySelectorAll('input[name*="[montant]"]');
            var designationInputs = form.querySelectorAll('input[name*="[designation]"]');

            var total = 0;
            montantInputs.forEach(function (input) {
                var val = parseFloat(input.value);
                if (!isNaN(val)) total += val;
            });

            var filledCount = 0;
            designationInputs.forEach(function (input) {
                if (input.value.trim() !== '') filledCount++;
            });

            document.getElementById('pbFilledCount').textContent = filledCount;
            document.getElementById('pbTotalAmount').textContent = total.toLocaleString('fr-FR');

            var totalRows = designationInputs.length;
            var percent = totalRows > 0 ? Math.round((filledCount / totalRows) * 100) : 0;
            document.getElementById('pbProgressFill').style.width = percent + '%';
        }

        document.getElementById('activityForm').addEventListener('input', pbUpdateSummary);
        pbUpdateSummary();

        function pbRenumber(code) {
            var rows = document.querySelectorAll('.pb-line-row[data-sous-axe-code="' + code + '"]');
            rows.forEach(function (row, index) {
                row.querySelector('.pb-col-num').textContent = index + 1;
            });
        }

        document.querySelectorAll('.pb-add-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var code = this.dataset.sousAxe;
                var markerRow = this.closest('tr');
                var nextIndex = parseInt(markerRow.dataset.nextIndex, 10);

                var newRow = document.createElement('tr');
                newRow.className = 'pb-line-row';
                newRow.dataset.sousAxeCode = code;
                newRow.innerHTML =
                    '<td class="pb-col-num">' + nextIndex + '</td>' +
                    '<td><input type="text" name="lignes[' + code + '][' + nextIndex + '][designation]"></td>' +
                    '<td class="pb-col-montant"><input type="number" step="0.01" min="0" class="pb-num" name="lignes[' + code + '][' + nextIndex + '][montant]"></td>' +
                    '<td class="pb-col-contrib"><input type="text" name="lignes[' + code + '][' + nextIndex + '][contribution_partenaires]"></td>' +
                    '<td class="pb-col-date"><input type="date" name="lignes[' + code + '][' + nextIndex + '][date]"></td>' +
                    '<td><input type="text" name="lignes[' + code + '][' + nextIndex + '][observations]"></td>' +
                    '<td class="pb-col-actions"><button type="button" class="pb-remove-btn" title="Supprimer la ligne">&times;</button></td>';

                markerRow.parentNode.insertBefore(newRow, markerRow);
                markerRow.dataset.nextIndex = nextIndex + 1;
                pbUpdateSummary();
            });
        });

        document.addEventListener('click', function (e) {
            if (!e.target.classList.contains('pb-remove-btn')) {
                return;
            }

            var row = e.target.closest('.pb-line-row');
            var code = row.dataset.sousAxeCode;
            var siblingRows = document.querySelectorAll('.pb-line-row[data-sous-axe-code="' + code + '"]');

            if (siblingRows.length <= 1) {
                return;
            }

            row.remove();
            pbRenumber(code);
            pbUpdateSummary();
        });
    </script>
@endpush
