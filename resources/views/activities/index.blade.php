@extends('layouts.dashboard', ['active' => 'activities'])

@section('title', 'Activités réalisées')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/activity-list.css') }}">
@endpush

@php
    $fcfa = fn ($montant) => number_format((float) $montant, 0, ',', ' ').' FCFA';
    // Période courte : « 12–26 sept. 2026 », « 28 août – 3 sept. 2026 ».
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
    $urlFiltre = fn ($statut) => route('activities.index', array_filter([
        'statut' => $statut === 'tous' ? null : $statut,
        'annee' => $year ?: null,
        'q' => $search !== '' ? $search : null,
    ]));
    $onglets = [
        'tous' => ['Toutes', $counts['total']],
        'a_faire' => ['À faire', $counts['a_faire']],
        'en_examen' => ['En examen DGF', $counts['en_examen']],
        'valide' => ['Validées', $counts['valide']],
    ];
@endphp

@section('content')
    <div class="al-page">
        <x-page-heading title="Activités réalisées" description="Déclarez chaque activité réalisée avec ses pièces justificatives. Une fois validée par la DGF, elle est versée automatiquement à votre rapport d’activité.">
            <a href="{{ route('activities.create') }}" class="btn primary"><x-ui-icon name="plus" /> Déclarer une activité</a>
        </x-page-heading>

        @foreach ($ouvertures as $ouverture)
            <div class="notice notice-warning al-opening">
                <strong>Ouverture exceptionnelle : exercice {{ $ouverture->year }}</strong>{{ $ouverture->expires_on ? ' jusqu’au '.$ouverture->expires_on->format('d/m/Y') : '' }}.
                L’administration vous permet de déclarer et compléter des activités de cet exercice. Motif : {{ $ouverture->motif }}
            </div>
        @endforeach

        {{-- À faire : ce qui attend la fédération, toutes années ouvertes confondues --}}
        @if ($aTraiter->isNotEmpty())
            <section class="al-todo" aria-labelledby="alTodoTitre">
                <header class="al-todo-head">
                    <h2 id="alTodoTitre">À faire <span class="al-todo-count">{{ $aTraiter->count() }}</span></h2>
                    <p>{{ $aTraiter->count() > 1 ? 'Ces activités attendent' : 'Cette activité attend' }} une action de votre part.</p>
                </header>
                <ul>
                    @foreach ($aTraiter->take(5) as $item)
                        @php
                            [$ton, $raison, $libelle, $url] = match (true) {
                                $item->status === 'soumis' => ['warning', 'Soumise sans justificatif : la DGF ne peut pas l’examiner', 'Ajouter un justificatif', route('activities.show', $item).'#adPieces'],
                                $item->status === 'rejete' => ['danger', 'Rejetée par la DGF'.($item->rejection_reason ? ' : « '.\Illuminate\Support\Str::limit($item->rejection_reason, 90).' »' : ''), 'Corriger', route('activities.edit', $item)],
                                $item->documents_count > 0 => ['neutral', 'Brouillon prêt : il reste à le soumettre à la DGF', 'Continuer', route('activities.show', $item)],
                                default => ['neutral', 'Brouillon : ajoutez un justificatif puis soumettez', 'Continuer', route('activities.show', $item)],
                            };
                        @endphp
                        <li class="al-todo-item is-{{ $ton }}">
                            <span class="al-todo-dot" aria-hidden="true"></span>
                            <div class="al-todo-body">
                                <a href="{{ route('activities.show', $item) }}" class="al-todo-name cap-first">{{ $item->designation ?: 'Activité sans intitulé' }}</a>
                                <span>{{ $raison }} · {{ $item->year }}</span>
                            </div>
                            <a href="{{ $url }}" class="btn btn-sm {{ $ton === 'neutral' ? '' : 'primary' }}">{{ $libelle }} <x-ui-icon name="arrow" /></a>
                        </li>
                    @endforeach
                </ul>
                @if ($aTraiter->count() > 5)
                    <a href="{{ $urlFiltre('a_faire') }}" class="al-more">Voir les {{ $aTraiter->count() }} activités à faire →</a>
                @endif
            </section>
        @endif

        <section class="al-card" aria-label="Liste des activités">
            <div class="al-toolbar">
                <nav class="al-tabs" aria-label="Filtrer par état">
                    @foreach ($onglets as $cle => [$libelle, $nombre])
                        <a href="{{ $urlFiltre($cle) }}" @class(['al-tab', 'is-active' => $status === $cle, 'is-alert' => $cle === 'a_faire' && $nombre > 0]) @if ($status === $cle) aria-current="page" @endif>
                            {{ $libelle }} <span class="al-tab-count">{{ $nombre }}</span>
                        </a>
                    @endforeach
                </nav>
                @if ($montantValide > 0)
                    <p class="al-verse">Versé au rapport{{ $year ? ' '.$year : '' }} : <strong>{{ $fcfa($montantValide) }}</strong></p>
                @endif
            </div>

            <form method="GET" action="{{ route('activities.index') }}" class="al-filters" role="search">
                @if ($status !== 'tous')<input type="hidden" name="statut" value="{{ $status }}">@endif
                <div class="al-search">
                    <x-ui-icon name="search" />
                    <label class="sr-only" for="alRecherche">Rechercher une activité</label>
                    <input id="alRecherche" type="search" name="q" value="{{ $search }}" placeholder="Rechercher une activité…" autocomplete="off">
                </div>
                @if ($availableYears->isNotEmpty())
                    <label class="sr-only" for="alAnnee">Année</label>
                    <select id="alAnnee" name="annee" class="form-select al-year" data-auto-submit>
                        <option value="">Toutes les années</option>
                        @foreach ($availableYears as $annee)
                            <option value="{{ $annee }}" @selected((string) $year === (string) $annee)>{{ $annee }}</option>
                        @endforeach
                    </select>
                @endif
                <button type="submit" class="btn">Rechercher</button>
                @if ($search !== '' || $year)
                    <a href="{{ route('activities.index', $status !== 'tous' ? ['statut' => $status] : []) }}" class="al-reset">Effacer</a>
                @endif
            </form>

            @if ($activities->isEmpty())
                @if ($counts['total'] === 0 && $search === '' && ! $year)
                    <div class="al-empty">
                        <strong>Aucune activité déclarée pour le moment</strong>
                        <p>Déclarez vos activités au fil de l’année : chacune validée par la DGF alimente votre rapport d’activité.</p>
                        <a href="{{ route('activities.create') }}" class="btn primary"><x-ui-icon name="plus" /> Déclarer une activité</a>
                    </div>
                @else
                    <div class="al-empty">
                        <strong>Aucune activité ne correspond</strong>
                        <p>Modifiez la recherche ou choisissez un autre onglet.</p>
                    </div>
                @endif
            @else
                <div class="al-table-wrap">
                    <table class="al-table">
                        <thead>
                            <tr>
                                <th scope="col">Activité</th>
                                <th scope="col">Période</th>
                                <th scope="col" class="is-amount">Montant</th>
                                <th scope="col">Justificatifs</th>
                                <th scope="col">Statut</th>
                                <th scope="col"><span class="sr-only">Ouvrir</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($activities as $activity)
                                @php $manque = $activity->documents_count === 0 && $activity->status !== 'valide'; @endphp
                                <tr data-href="{{ route('activities.show', $activity) }}">
                                    <td class="al-activity">
                                        <a href="{{ route('activities.show', $activity) }}" class="al-name cap-first">{{ $activity->designation ?: 'Activité sans intitulé' }}</a>
                                        <span class="al-axe">
                                            @if ($activity->axeNumber())<span class="al-chip">Axe {{ $activity->axeNumber() }}</span>@endif
                                            <span>{{ $activity->sous_axe_code }} · {{ $activity->sous_axe_label }}</span>
                                        </span>
                                    </td>
                                    <td class="al-period">{{ $periode($activity) ?? '—' }}</td>
                                    <td class="is-amount">{!! $activity->montant !== null ? e($fcfa($activity->montant)) : '<span class="al-muted">Non renseigné</span>' !!}</td>
                                    <td>
                                        @if ($activity->documents_count > 0)
                                            <span class="al-pieces">{{ $activity->documents_count }} {{ $activity->documents_count > 1 ? 'pièces' : 'pièce' }}</span>
                                        @elseif ($manque)
                                            <span class="al-pieces is-missing">Aucune pièce</span>
                                        @else
                                            <span class="al-muted">—</span>
                                        @endif
                                    </td>
                                    <td><x-status-badge :status="$activity->status" /></td>
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
            // Changer d'année relance la recherche.
            // Un changement de liste relance la recherche ; l'adresse ne garde que les filtres renseignés.
            document.querySelectorAll('[data-auto-submit]').forEach(select => {
                select.addEventListener('change', () => select.form.requestSubmit());
            });
            document.querySelectorAll('.al-filters').forEach(form => {
                form.addEventListener('submit', () => {
                    form.querySelectorAll('input[name], select[name]').forEach(champ => { if (champ.value === '') champ.disabled = true; });
                });
            });
            // Toute la ligne ouvre l'activité ; le lien reste la cible clavier.
            document.querySelectorAll('tr[data-href]').forEach(ligne => {
                ligne.addEventListener('click', event => {
                    if (event.target.closest('a, button') || window.getSelection().toString()) return;
                    window.location.href = ligne.dataset.href;
                });
            });
        })();
    </script>
@endpush
