@extends('layouts.dashboard', ['active' => 'dgf-activities'])

@section('title', 'Contrôle des activités')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/activity-list.css') }}">
@endpush

@php
    $fcfa = fn ($montant) => number_format((float) $montant, 0, ',', ' ').' FCFA';
    // Ancienneté d'une soumission, en jours entiers, avec un ton selon l'attente.
    $attente = function ($activity) {
        if (! $activity->submitted_at) {
            return null;
        }
        $jours = (int) floor($activity->submitted_at->diffInDays(now()));

        return [
            'texte' => match (true) { $jours === 0 => 'aujourd’hui', $jours === 1 => 'depuis 1 jour', default => 'depuis '.$jours.' jours' },
            'ton' => $jours >= 14 ? 'danger' : ($jours >= 7 ? 'warning' : 'ok'),
        ];
    };
    $periode = function ($activity) {
        $debut = $activity->date_debut;
        $fin = $activity->date_fin;
        if (! $debut && ! $fin) {
            return null;
        }
        $court = fn ($date, $format) => $date->locale('fr')->translatedFormat($format);
        if (! $debut || ! $fin || $debut->isSameDay($fin)) {
            return $court($debut ?? $fin, 'j M Y');
        }
        if ($debut->isSameMonth($fin)) {
            return $debut->format('j').'–'.$court($fin, 'j M Y');
        }

        return $debut->isSameYear($fin) ? $court($debut, 'j M').' – '.$court($fin, 'j M Y') : $court($debut, 'j M Y').' – '.$court($fin, 'j M Y');
    };
    $filtresCourants = array_filter(request()->only('q', 'annee', 'federation'), fn ($valeur) => $valeur !== null && $valeur !== '');
    $urlFiltre = fn (array $changements) => route('dgf.activities.index', array_filter($changements + $filtresCourants + ['statut' => $statut !== 'soumis' ? $statut : null], fn ($valeur) => $valeur !== null && $valeur !== ''));
    $onglets = [
        'soumis' => ['Soumises', $counts['soumis']],
        'brouillon' => ['Brouillons', $counts['brouillon']],
        'rejete' => ['Rejetées', $counts['rejete']],
        'valide' => ['Validées', $counts['valide']],
    ];
    $plusAncienne = $aExaminer->first();
@endphp

@section('content')
    <div class="al-page">
        <x-page-heading title="Contrôle des activités" eyebrow="Vérification DGF" description="Examinez les justificatifs des activités déclarées par les fédérations. Chaque activité validée est versée automatiquement au rapport d’activité de la fédération." />

        {{-- Décisions en attente, toutes fédérations confondues ------------- --}}
        @if ($aExaminer->isNotEmpty())
            <section class="al-todo is-review" aria-labelledby="alTodoTitre">
                <header class="al-todo-head">
                    <h2 id="alTodoTitre">À examiner maintenant <span class="al-todo-count">{{ $aExaminer->count() }}</span></h2>
                    <p>
                        {{ $aExaminer->count() > 1 ? 'Activités soumises avec justificatif, de la plus ancienne à la plus récente.' : 'Activité soumise avec justificatif.' }}
                        @if ($plusAncienne && ($ancien = $attente($plusAncienne)) && $ancien['ton'] !== 'ok')
                            <strong class="al-age is-{{ $ancien['ton'] }}">La plus ancienne attend {{ $ancien['texte'] }}.</strong>
                        @endif
                    </p>
                </header>
                <ul>
                    @foreach ($aExaminer->take(5) as $item)
                        @php $age = $attente($item); @endphp
                        <li class="al-todo-item is-{{ $age['ton'] ?? 'ok' }}">
                            <span class="al-todo-dot" aria-hidden="true"></span>
                            <div class="al-todo-body">
                                <a href="{{ route('dgf.activities.show', $item) }}" class="al-todo-name cap-first">{{ $item->designation ?: 'Activité sans intitulé' }}</a>
                                <span>{{ $item->user->federation_name }} · {{ $item->montant !== null ? $fcfa($item->montant) : 'montant non renseigné' }}@if ($age) · soumise {{ $age['texte'] }}@endif</span>
                            </div>
                            <a href="{{ route('dgf.activities.show', $item) }}" class="btn btn-sm primary">Examiner <x-ui-icon name="arrow" /></a>
                        </li>
                    @endforeach
                </ul>
                @if ($aExaminer->count() > 5)
                    <a href="{{ route('dgf.activities.index', ['justificatif' => 'avec']) }}" class="al-more">Voir les {{ $aExaminer->count() }} activités à examiner →</a>
                @endif
            </section>
        @else
            <p class="al-clear"><x-ui-icon name="check" /> Aucune activité n’attend votre décision pour le moment.</p>
        @endif

        <section class="al-card" aria-label="File de contrôle">
            <div class="al-toolbar">
                <nav class="al-tabs" aria-label="Filtrer par statut">
                    @foreach ($onglets as $cle => [$libelle, $nombre])
                        <a href="{{ route('dgf.activities.index', array_filter($filtresCourants + ['statut' => $cle === 'soumis' ? null : $cle])) }}" @class(['al-tab', 'is-active' => $statut === $cle, 'is-alert' => $cle === 'soumis' && $nombre > 0]) @if ($statut === $cle) aria-current="page" @endif>
                            {{ $libelle }} <span class="al-tab-count">{{ $nombre }}</span>
                        </a>
                    @endforeach
                </nav>
                @if ($montantValide > 0)
                    <p class="al-verse">Versé aux rapports : <strong>{{ $fcfa($montantValide) }}</strong></p>
                @endif
            </div>

            <form method="GET" action="{{ route('dgf.activities.index') }}" class="al-filters" role="search">
                @if ($statut !== 'soumis')<input type="hidden" name="statut" value="{{ $statut }}">@endif
                @if ($justificatif)<input type="hidden" name="justificatif" value="{{ $justificatif }}">@endif
                <div class="al-search">
                    <x-ui-icon name="search" />
                    <label class="sr-only" for="alRecherche">Rechercher une fédération ou une activité</label>
                    <input id="alRecherche" type="search" name="q" value="{{ request('q') }}" placeholder="Fédération ou activité…" maxlength="150" autocomplete="off">
                </div>
                <label class="sr-only" for="alFederation">Fédération</label>
                <select id="alFederation" name="federation" class="form-select al-year" data-auto-submit>
                    <option value="">Toutes les fédérations</option>
                    @foreach ($federations as $federation)
                        <option value="{{ $federation->id }}" @selected((string) request('federation') === (string) $federation->id)>{{ $federation->federation_name }}</option>
                    @endforeach
                </select>
                <label class="sr-only" for="alAnnee">Exercice</label>
                <select id="alAnnee" name="annee" class="form-select al-year" data-auto-submit>
                    <option value="">Tous les exercices</option>
                    @foreach ($years as $annee)
                        <option value="{{ $annee }}" @selected((string) request('annee') === (string) $annee)>{{ $annee }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn">Rechercher</button>
                @if ($filtresCourants)
                    <a href="{{ route('dgf.activities.index', array_filter(['statut' => $statut !== 'soumis' ? $statut : null, 'justificatif' => $justificatif])) }}" class="al-reset">Effacer</a>
                @endif
            </form>

            {{-- Justificatifs : sous-filtre de l'onglet courant --}}
            @if ($counts[$statut] > 0 && $statut !== 'valide')
                <div class="al-chips" role="group" aria-label="Filtrer par justificatif">
                    @foreach ([null => ['Toutes', $counts['avec'] + $counts['sans']], 'avec' => ['Avec justificatif', $counts['avec']], 'sans' => ['Sans justificatif', $counts['sans']]] as $cle => [$libelle, $nombre])
                        <a href="{{ $urlFiltre(['justificatif' => $cle ?: null]) }}" @class(['al-filter-chip', 'is-active' => (string) $justificatif === (string) $cle, 'is-missing' => $cle === 'sans' && $nombre > 0]) @if ((string) $justificatif === (string) $cle) aria-current="true" @endif>{{ $libelle }} <span>{{ $nombre }}</span></a>
                    @endforeach
                </div>
            @endif

            @if ($activities->isEmpty())
                <div class="al-empty">
                    <strong>{{ $statut === 'soumis' && ! $filtresCourants && ! $justificatif ? 'Aucune activité à traiter' : 'Aucune activité dans cette sélection' }}</strong>
                    <p>{{ $statut === 'soumis' && ! $filtresCourants && ! $justificatif ? 'Les activités soumises par les fédérations apparaîtront ici.' : 'Changez d’onglet ou retirez un filtre pour élargir la recherche.' }}</p>
                </div>
            @else
                <div class="al-table-wrap">
                    <table class="al-table">
                        <thead>
                            <tr>
                                <th scope="col">Activité et fédération</th>
                                <th scope="col">{{ $statut === 'valide' ? 'Validée le' : ($statut === 'soumis' ? 'Soumise' : 'Période') }}</th>
                                <th scope="col" class="is-amount">Montant</th>
                                <th scope="col">Justificatifs</th>
                                <th scope="col">Statut</th>
                                <th scope="col"><span class="sr-only">Ouvrir</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($activities as $activity)
                                @php $age = $statut === 'soumis' ? $attente($activity) : null; @endphp
                                <tr data-href="{{ route('dgf.activities.show', $activity) }}">
                                    <td class="al-activity">
                                        <a href="{{ route('dgf.activities.show', $activity) }}" class="al-name cap-first">{{ $activity->designation ?: 'Activité sans intitulé' }}</a>
                                        <span class="al-fede"><x-ui-icon name="users" /> {{ $activity->user->federation_name ?? $activity->user->name }}</span>
                                        <span class="al-axe">
                                            @if ($activity->axeNumber())<span class="al-chip">Axe {{ $activity->axeNumber() }}</span>@endif
                                            <span>{{ $activity->sous_axe_code }} · {{ $activity->sous_axe_label }} · {{ $activity->year }}</span>
                                        </span>
                                    </td>
                                    <td class="al-period">
                                        @if ($statut === 'soumis')
                                            {{ $activity->submitted_at?->format('d/m/Y') ?? '—' }}
                                            @if ($age)<span class="al-age is-{{ $age['ton'] }}">{{ $age['texte'] }}</span>@endif
                                        @elseif ($statut === 'valide')
                                            {{ $activity->validated_at?->format('d/m/Y') ?? '—' }}
                                        @else
                                            {{ $periode($activity) ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="is-amount">{!! $activity->montant !== null ? e($fcfa($activity->montant)) : '<span class="al-muted">Non renseigné</span>' !!}</td>
                                    <td>
                                        @if ($activity->documents_count > 0)
                                            <span class="al-pieces">{{ $activity->documents_count }} {{ $activity->documents_count > 1 ? 'pièces' : 'pièce' }}</span>
                                        @else
                                            <span class="al-pieces is-missing">Aucune pièce</span>
                                        @endif
                                    </td>
                                    <td><x-status-badge :status="$activity->status" :label="$activity->status === 'soumis' ? ($activity->documents_count ? 'À examiner' : 'Bloquée') : null" /></td>
                                    <td class="al-go" aria-hidden="true"><x-ui-icon name="arrow" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($activities->hasPages())
                    <div class="platform-pagination">{{ $activities->links() }}</div>
                @endif
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            'use strict';
            // Un changement de liste relance la recherche ; l'adresse ne garde que les filtres renseignés.
            document.querySelectorAll('[data-auto-submit]').forEach(select => {
                select.addEventListener('change', () => select.form.requestSubmit());
            });
            document.querySelectorAll('.al-filters').forEach(form => {
                form.addEventListener('submit', () => {
                    form.querySelectorAll('input[name], select[name]').forEach(champ => { if (champ.value === '') champ.disabled = true; });
                });
            });
            document.querySelectorAll('tr[data-href]').forEach(ligne => {
                ligne.addEventListener('click', event => {
                    if (event.target.closest('a, button') || window.getSelection().toString()) return;
                    window.location.href = ligne.dataset.href;
                });
            });
        })();
    </script>
@endpush
