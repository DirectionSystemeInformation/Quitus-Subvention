@extends('layouts.dashboard', ['active' => 'documents'])
@section('title', $title)
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/programme-form.css') }}">
@endpush
@section('content')
    @php
        $routePrefix = str_replace('_', '-', $type);
        $oldLines = old('lignes', []);
        $hasOldForm = old('form_loaded') === '1';
        $estReamenage = $type === 'programme_reamenage';
        $fcfa = fn ($montant) => number_format((float) $montant, 0, ',', ' ').' FCFA';
        $avecSaisie = $hasOldForm || $existingLines->isNotEmpty();
    @endphp
    <div class="creation-ui pf-page">
        <nav class="breadcrumb" aria-label="Fil d’Ariane"><a href="{{ route('documents.index', ['type' => $type, 'annee' => $year]) }}">Mes documents</a><span aria-hidden="true"> / </span><span aria-current="page">Préparer le programme</span></nav>

        <x-creation-heading :title="$title" :eyebrow="auth()->user()->federation_name" :description="$estReamenage
            ? 'Ajustez votre programme à la subvention accordée, activité par activité. Le total et le reste à répartir se mettent à jour pendant la saisie.'
            : 'Listez les activités prévues pour l’année, sous-axe par sous-axe. Le total se met à jour pendant la saisie.'">
            @if ($anneesOuvertes->count() > 1)
                {{-- Ouverture exceptionnelle : choix parmi les seules années ouvertes. --}}
                <form method="GET" action="{{ route($routePrefix.'.create') }}" class="pf-year">
                    <label for="programmeYear">Année</label>
                    <select id="programmeYear" name="annee" class="form-select" onchange="this.form.requestSubmit()">
                        @foreach ($anneesOuvertes as $annee)
                            <option value="{{ $annee }}" @selected($annee === $year)>{{ $annee }}</option>
                        @endforeach
                    </select>
                    <noscript><button class="btn" type="submit">Afficher</button></noscript>
                </form>
            @else
                <span class="pf-year-chip">Année {{ $year }}</span>
            @endif
        </x-creation-heading>

        @foreach ($ouvertures as $ouverture)
            <div class="notice notice-warning pf-notice">
                <strong>Ouverture exceptionnelle pour {{ $ouverture->year }}</strong>{{ $ouverture->expires_on ? ' jusqu’au '.$ouverture->expires_on->format('d/m/Y') : '' }} : l’administration vous permet de saisir ce programme pour cet exercice. Motif : {{ $ouverture->motif }}
            </div>
        @endforeach

        @if ($report?->status === 'rejete' && $report->rejection_reason)
            <div class="notice notice-error"><strong>Corrections demandées par la DSHN</strong><p>{{ $report->rejection_reason }}</p></div>
        @endif
        @if ($report?->status === 'soumis')
            <div class="notice notice-success">Ce programme est déjà soumis. Vous pouvez soumettre une version corrigée ; il ne peut plus redevenir un brouillon.</div>
        @endif

        @if ($reprise)
            <div class="notice notice-success pf-notice">
                <strong>{{ $existingLines->count() }} activités reprises de votre programme budgétisé {{ $year }}.</strong>
                Ajustez-les à la subvention accordée puis enregistrez : rien n’est encore enregistré.
            </div>
        @elseif ($estReamenage && $programmeBudgetise && ! $avecSaisie)
            <div class="pf-callout">
                <div>
                    <strong>Partir de votre programme budgétisé {{ $year }} ?</strong>
                    <p>Reprenez ses {{ $programmeBudgetise->budgetLines->count() }} activités ({{ $fcfa($programmeBudgetise->budgetLines->sum('montant')) }}) puis ajustez les montants à la subvention accordée.</p>
                </div>
                <a href="{{ route($routePrefix.'.create', ['annee' => $year, 'reprendre' => 1]) }}" class="btn primary">Reprendre le programme budgétisé</a>
            </div>
        @endif

        <form method="POST" action="{{ route($routePrefix.'.store') }}" id="activityForm" class="pf-layout" data-unsaved="{{ $hasOldForm || $reprise ? 'true' : 'false' }}" @if ($subvention !== null) data-subvention="{{ $subvention }}" @endif>
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="form_loaded" value="1">

            <div class="pf-main">
                <p class="pf-help">Chaque activité renseignée doit avoir une <strong>désignation</strong> et un <strong>montant</strong> (0 si aucun coût) ; la date, la contribution des partenaires et les observations sont facultatives. Un sous-axe sans activité reste vide.</p>

                @foreach ($axes as $axe)
                    <section class="pf-axis" id="axe-{{ $axe['code'] }}" data-axis aria-labelledby="axe-titre-{{ $axe['code'] }}">
                        <header class="pf-axis-head">
                            <span class="pf-axis-code">{{ $axe['code'] }}</span>
                            <h2 id="axe-titre-{{ $axe['code'] }}">{{ $axe['label'] }}</h2>
                            <span class="pf-axis-total" data-axis-total>0 FCFA</span>
                        </header>

                        @foreach ($axe['sous_axes'] as $sousAxe)
                            @php
                                $code = $sousAxe['code'];
                                if ($hasOldForm) {
                                    $rows = is_array($oldLines) && is_array($oldLines[$code] ?? null) ? $oldLines[$code] : [];
                                } else {
                                    $rows = $existingLines->filter(fn ($line) => $line->sous_axe_code === $code)->mapWithKeys(fn ($line) => [$line->numero_ligne => [
                                        'designation' => $line->designation, 'montant' => $line->montant,
                                        'contribution_partenaires' => $line->contribution_partenaires,
                                        'date' => optional($line->date)->format('Y-m-d'), 'observations' => $line->observations,
                                    ]])->all();
                                }
                                $nextIndex = count($rows) ? max(array_map('intval', array_keys($rows))) + 1 : 1;
                            @endphp
                            <div @class(['pf-subaxis', 'is-empty' => ! count($rows)]) data-subaxis data-next-index="{{ $nextIndex }}" aria-labelledby="subaxis-{{ $sousAxe['id'] }}" role="group">
                                <div class="pf-subaxis-head">
                                    <h3 id="subaxis-{{ $sousAxe['id'] }}"><span class="pf-code">{{ $code }}</span> {{ $sousAxe['label'] }}</h3>
                                    <span class="pf-subaxis-total" data-subaxis-total></span>
                                </div>
                                <div class="pf-columns" aria-hidden="true">
                                    <span>N°</span><span>Désignation</span><span>Montant</span><span>Date prévue</span>
                                </div>
                                <div class="pf-lines" data-lines>
                                    @foreach ($rows as $index => $row)
                                        @include('activity-form.partials.programme-line', ['index' => $index, 'row' => is_array($row) ? $row : []])
                                    @endforeach
                                </div>
                                <template>@include('activity-form.partials.programme-line', ['index' => '__INDEX__', 'row' => []])</template>
                                <button class="pf-add" type="button" data-add><x-ui-icon name="plus" /> Ajouter une activité</button>
                            </div>
                        @endforeach
                    </section>
                @endforeach

                <section class="pf-review" id="programmeReview" hidden aria-labelledby="reviewTitle" tabindex="-1">
                    <h2 id="reviewTitle">Vérifiez votre programme {{ $year }} avant de le soumettre</h2>
                    <p id="reviewSummary"></p>
                    <p id="reviewSubvention" class="pf-review-gap" hidden></p>
                    <ul id="reviewAxes"></ul>
                    <p class="pf-review-note">La soumission transmet le programme à la DSHN pour examen ; il ne pourra plus redevenir un brouillon.</p>
                    <label class="programme-confirm"><input type="checkbox" name="confirm_submission" value="1"> J’ai vérifié les activités et les montants du programme.</label>
                    <div class="btn-group"><button type="button" class="btn" id="backToProgramme">Revenir à la saisie</button><button type="submit" class="btn primary" name="action" value="submit">Confirmer la soumission</button></div>
                </section>
                <noscript><div class="notice notice-error">Activez JavaScript pour afficher le récapitulatif et soumettre. Vous pouvez enregistrer le brouillon.</div></noscript>
            </div>

            <aside class="pf-aside" aria-label="Synthèse du programme">
                <div class="pf-aside-card">
                    <p class="pf-aside-label">Total du programme {{ $year }}</p>
                    <p class="pf-aside-total"><strong id="pbTotalAmount">0 FCFA</strong></p>
                    <p class="pf-aside-count" id="pbFilledCount">Aucune activité</p>

                    @if ($subvention !== null)
                        <div class="pf-gauge-block">
                            <div class="pf-gauge-legend"><span>Subvention accordée</span><strong>{{ $fcfa($subvention) }}</strong></div>
                            <div class="pf-gauge" aria-hidden="true"><span id="pfGaugeFill"></span></div>
                            <p class="pf-balance" id="pfBalance" role="status"></p>
                        </div>
                    @elseif ($estReamenage)
                        <p class="pf-aside-note">La subvention accordée pour {{ $year }} n’est pas encore arrêtée : elle s’affichera ici après la validation de la répartition.</p>
                    @endif

                    <nav class="pf-axes-nav" aria-label="Aller à un axe">
                        <ul>
                            @foreach ($axes as $axe)
                                <li><a href="#axe-{{ $axe['code'] }}"><span class="pf-axis-code">{{ $axe['code'] }}</span><span class="pf-axes-nav-label">{{ \Illuminate\Support\Str::after($axe['label'], ': ') ?: $axe['label'] }}</span><span class="pf-axes-nav-total" data-nav-total="{{ $axe['code'] }}">0</span></a></li>
                            @endforeach
                        </ul>
                    </nav>

                    <p class="pf-save-state" id="saveState" role="status">{{ $report ? 'Enregistré le '.$report->updated_at->format('d/m/Y à H:i') : 'Aucun brouillon enregistré' }}</p>
                    <div class="pf-aside-actions">
                        @if ($report?->status !== 'soumis')
                            <button class="btn" type="submit" name="action" value="draft" formnovalidate>Enregistrer le brouillon</button>
                        @endif
                        <button class="btn primary" id="reviewProgramme" type="button">Vérifier et soumettre</button>
                    </div>
                </div>
            </aside>
        </form>
    </div>
@endsection
@push('scripts')
    <script src="{{ asset('js/programme-form.js') }}"></script>
@endpush
