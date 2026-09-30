@extends('layouts.dashboard', ['active' => 'ponderation'])

@section('title', 'Grille de pondération')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/grille-ponderation.css') }}">
@endpush

@section('content')
    @php
        // Barème officiel : la grille est notée sur 100 points et les seuils des
        // paliers sont exprimés sur cette base.
        $reference = 100;

        $nombre = fn ($valeur) => rtrim(rtrim(number_format((float) $valeur, 2, ',', ' '), '0'), ',');
        $pointsRubrique = fn ($rubrique) => (float) $rubrique->criteres->sum('points_max');
        $totalPoints = $rubriques->sum($pointsRubrique);
        // Les barres comparent les rubriques entre elles : la plus lourde remplit sa barre.
        $plusLourde = $rubriques->map($pointsRubrique)->max() ?: 0;
        $criteresCount = $rubriques->sum(fn ($rubrique) => $rubrique->criteres->count());
        $ecart = $totalPoints - $reference;

        $editionGrille = $edition === 'grille';
        $editionPaliers = $edition === 'paliers';
        $urlIndex = role_route('ponderation.index');

        // Une teinte par catégorie, de la meilleure (seuil le plus haut) à la plus faible.
        $tons = [['#08664f', '#dcefe5'], ['#4d7a2a', '#e6efd6'], ['#85651a', '#f5ead0'], ['#a0482c', '#f7e0d6'], ['#6a4f8a', '#ebe4f3']];
        $categories = $paliers->sortByDesc('seuil_min')->pluck('categorie')->unique()->values();
        $ton = fn ($categorie) => $tons[max(0, (int) $categories->search($categorie)) % count($tons)];

        // Contrôles de cohérence de l'échelle (recalculés en direct en édition).
        $verifications = [];
        $codesEnDouble = $paliers->pluck('code')->map(fn ($code) => mb_strtoupper(trim($code)))->duplicates()->unique();
        $seuilsEnDouble = $paliers->pluck('seuil_min')->map(fn ($seuil) => (float) $seuil)->duplicates()->unique();
        if ($codesEnDouble->isNotEmpty()) {
            $verifications[] = ['erreur', 'Code en double : '.$codesEnDouble->implode(', ').'.'];
        }
        if ($seuilsEnDouble->isNotEmpty()) {
            $verifications[] = ['erreur', 'Plusieurs paliers commencent à '.$seuilsEnDouble->map($nombre)->implode(', ').' points.'];
        }
        if ($paliers->isNotEmpty() && (float) $paliers->first()->seuil_min > 0) {
            $verifications[] = ['alerte', 'Les scores inférieurs à '.$nombre($paliers->first()->seuil_min).' points ne seront pas classés.'];
        }
        if ($horsEchelle = $paliers->filter(fn ($palier) => (float) $palier->seuil_min > $reference)->pluck('code')->implode(', ')) {
            $verifications[] = ['alerte', "Hors d'atteinte (au-delà de {$reference} points) : {$horsEchelle}."];
        }
    @endphp

    <div class="gp-page" data-gp-page data-gp-reference="{{ $reference }}" data-gp-tons='@json($tons)'>
        <x-page-heading title="Grille de pondération" eyebrow="Référentiel" description="Critères de notation des fédérations et paliers qui les classent en catégories, sur la base desquelles les montants sont proposés." />

        <aside class="gp-context">
            <x-ui-icon name="lock" />
            <div>
                <p><strong>Grille en vigueur.</strong> Chaque campagne la fige au lancement de sa pondération : vos modifications s’appliqueront aux campagnes suivantes.</p>
                <p class="gp-context-meta">
                    @if ($campagnesFigees->isNotEmpty())
                        <span>Déjà figée pour {{ $campagnesFigees->count() > 1 ? 'les campagnes' : 'la campagne' }} {{ $campagnesFigees->join(', ', ' et ') }}</span>
                    @endif
                    @if ($derniereModification)
                        <span>Modifiée le {{ $derniereModification->locale('fr')->translatedFormat('j F Y') }}</span>
                    @endif
                </p>
            </div>
        </aside>

        <div class="gp-tabs" role="tablist" aria-label="Paramétrage de la grille" data-gp-tabs>
            <a class="gp-tab" role="tab" id="onglet-criteres" href="#criteres" aria-controls="criteres" aria-selected="{{ $editionPaliers ? 'false' : 'true' }}">Critères<span class="gp-tab-suite"> de notation</span> <span class="gp-tab-count">{{ $criteresCount }}</span></a>
            <a class="gp-tab" role="tab" id="onglet-paliers" href="#paliers" aria-controls="paliers" aria-selected="{{ $editionPaliers ? 'true' : 'false' }}">Paliers<span class="gp-tab-suite"> de catégorisation</span> <span class="gp-tab-count">{{ $paliers->count() }}</span></a>
        </div>

        {{-- ================================================================
             Critères de notation
             ================================================================ --}}
        <section class="gp-panel" id="criteres" role="tabpanel" aria-labelledby="onglet-criteres" tabindex="-1">
            @if ($editionGrille)
                <form method="POST" action="{{ role_route('ponderation.grille.update') }}" class="gp-editor" data-gp-editor="grille" @if (old('_form') === 'grille') data-gp-dirty data-error-scope @endif>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_form" value="grille">
            @endif

            <div class="gp-toolbar">
                <div class="gp-total-block">
                    <span class="gp-total-label">Total de la grille</span>
                    <span class="gp-total-value"><strong data-gp-total>{{ $nombre($totalPoints) }}</strong> / {{ $reference }} points</span>
                    <span @class(['gp-status', 'is-ok' => abs($ecart) < 0.001, 'is-warning' => abs($ecart) >= 0.001]) data-gp-status>
                        @if (abs($ecart) < 0.001)
                            Grille équilibrée
                        @else
                            {{ $ecart > 0 ? '+' : '−' }}{{ $nombre(abs($ecart)) }} {{ abs($ecart) < 2 ? 'point' : 'points' }} par rapport à {{ $reference }}
                        @endif
                    </span>
                </div>
                @if ($editionGrille)
                    <span class="gp-mode">Modification en cours</span>
                @else
                    <a href="{{ $urlIndex }}?edition=grille#criteres" class="btn primary"><x-ui-icon name="edit" /> Modifier la grille</a>
                @endif
            </div>

            @if ($rubriques->isEmpty() && ! $editionGrille)
                <div class="gp-empty">
                    <p>La grille est vide : la pondération d’une campagne ne peut pas être lancée tant qu’aucun critère n’est défini.</p>
                    <a href="{{ $urlIndex }}?edition=grille#criteres" class="btn primary"><x-ui-icon name="plus" /> Composer la grille</a>
                </div>
            @else
                <div class="gp-table-wrap">
                    <table class="gp-table gp-grille" data-gp-table>
                        <caption class="sr-only">Critères de notation par rubrique, avec leur barème en points</caption>
                        <colgroup>
                            <col class="gp-col-num">
                            <col>
                            <col class="gp-col-points">
                            @if ($editionGrille)<col class="gp-col-actions">@endif
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">N°</th>
                                <th scope="col">Rubrique et critère</th>
                                <th scope="col" class="gp-points">Barème (pts)</th>
                                @if ($editionGrille)<th scope="col"><span class="sr-only">Supprimer</span></th>@endif
                            </tr>
                        </thead>

                        @foreach ($rubriques as $i => $rubrique)
                            @php
                                $numero = $i + 1;
                                $sousTotal = $pointsRubrique($rubrique);
                                $rubriqueSupprimee = in_array($rubrique->id, old('supprimer_rubriques', []));
                            @endphp
                            <tbody @class(['gp-rubrique', 'is-deleted' => $rubriqueSupprimee]) data-gp-rubrique="{{ $rubrique->id }}">
                                <tr class="gp-rubrique-row">
                                    <td class="gp-num">{{ $numero }}</td>
                                    <th scope="rowgroup" class="gp-rubrique-cell">
                                        @if ($editionGrille)
                                            <input type="text" name="rubriques[{{ $rubrique->id }}][label]" value="{{ old('rubriques.'.$rubrique->id.'.label', $rubrique->label) }}" class="form-input gp-input-rubrique" aria-label="Libellé de la rubrique {{ $numero }}" maxlength="255" required>
                                        @else
                                            <span class="gp-rubrique-name">{{ $rubrique->label }}</span>
                                            <span class="gp-rubrique-meta">{{ $rubrique->criteres->count() }} {{ $rubrique->criteres->count() > 1 ? 'critères' : 'critère' }}</span>
                                        @endif
                                    </th>
                                    <td class="gp-points">
                                        <span class="gp-share" aria-hidden="true"><span data-gp-share style="width: {{ $plusLourde > 0 ? round($sousTotal / $plusLourde * 100, 2) : 0 }}%"></span></span>
                                        <strong data-gp-subtotal>{{ $nombre($sousTotal) }}</strong>
                                    </td>
                                    @if ($editionGrille)
                                        <td class="gp-actions">
                                            <label class="gp-delete" title="Supprimer la rubrique et ses critères">
                                                <input type="checkbox" name="supprimer_rubriques[]" value="{{ $rubrique->id }}" data-gp-delete @checked($rubriqueSupprimee)>
                                                <x-ui-icon name="trash" class="gp-icon-delete" /><x-ui-icon name="undo" class="gp-icon-restore" />
                                                <span class="sr-only">Supprimer la rubrique {{ $numero }} et ses critères</span>
                                            </label>
                                        </td>
                                    @endif
                                </tr>

                                @foreach ($rubrique->criteres as $j => $critere)
                                    @php $critereSupprime = in_array($critere->id, old('supprimer_criteres', [])); @endphp
                                    <tr @class(['gp-critere', 'is-deleted' => $critereSupprime]) data-gp-critere>
                                        <td class="gp-num">{{ $numero }}.{{ $j + 1 }}</td>
                                        <td>
                                            @if ($editionGrille)
                                                <input type="text" name="criteres[{{ $critere->id }}][label]" value="{{ old('criteres.'.$critere->id.'.label', $critere->label) }}" class="form-input" aria-label="Libellé du critère {{ $numero }}.{{ $j + 1 }}" maxlength="255" required>
                                            @else
                                                {{ $critere->label }}
                                            @endif
                                        </td>
                                        <td class="gp-points">
                                            @if ($editionGrille)
                                                <input type="number" name="criteres[{{ $critere->id }}][points_max]" value="{{ old('criteres.'.$critere->id.'.points_max', 0 + $critere->points_max) }}" class="form-input gp-input-points" data-gp-points min="0" max="100" step="0.5" aria-label="Barème du critère {{ $numero }}.{{ $j + 1 }}, en points" required>
                                            @else
                                                {{ $nombre($critere->points_max) }}
                                            @endif
                                        </td>
                                        @if ($editionGrille)
                                            <td class="gp-actions">
                                                <label class="gp-delete" title="Supprimer le critère">
                                                    <input type="checkbox" name="supprimer_criteres[]" value="{{ $critere->id }}" data-gp-delete @checked($critereSupprime)>
                                                    <x-ui-icon name="trash" class="gp-icon-delete" /><x-ui-icon name="undo" class="gp-icon-restore" />
                                                    <span class="sr-only">Supprimer le critère {{ $numero }}.{{ $j + 1 }}</span>
                                                </label>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach

                                @if ($editionGrille)
                                    @foreach (old('nouveaux_criteres.'.$rubrique->id, []) as $cle => $valeurs)
                                        @include('dshn.partials.grille-nouveau-critere', ['nom' => 'nouveaux_criteres['.$rubrique->id.']['.$cle.']', 'valeurs' => $valeurs])
                                    @endforeach
                                    <tr class="gp-add-row">
                                        <td></td>
                                        <td colspan="3"><button type="button" class="gp-add" data-gp-add-critere="nouveaux_criteres[{{ $rubrique->id }}]"><x-ui-icon name="plus" /> Ajouter un critère</button></td>
                                    </tr>
                                @endif
                            </tbody>
                        @endforeach

                        @if ($editionGrille)
                            @foreach (old('nouvelles_rubriques', []) as $cle => $valeurs)
                                @include('dshn.partials.grille-nouvelle-rubrique', ['nom' => 'nouvelles_rubriques['.$cle.']', 'valeurs' => $valeurs])
                            @endforeach
                        @endif

                        <tfoot>
                            <tr>
                                <td></td>
                                <th scope="row">Total de la grille</th>
                                <td class="gp-points"><strong data-gp-total>{{ $nombre($totalPoints) }}</strong></td>
                                @if ($editionGrille)<td></td>@endif
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif

            @if ($editionGrille)
                    <button type="button" class="gp-add is-block" data-gp-add-rubrique><x-ui-icon name="plus" /> Ajouter une rubrique</button>

                    <template data-gp-template="critere">@include('dshn.partials.grille-nouveau-critere', ['nom' => '__NOM__', 'valeurs' => []])</template>
                    <template data-gp-template="rubrique">@include('dshn.partials.grille-nouvelle-rubrique', ['nom' => '__NOM__', 'valeurs' => ['criteres' => [0 => []]]])</template>

                    <div class="gp-savebar">
                        <p><strong>Total <span data-gp-total>{{ $nombre($totalPoints) }}</span> / {{ $reference }} points</strong><span class="gp-savebar-sep" aria-hidden="true">·</span><span data-gp-changes role="status">{{ old('_form') === 'grille' ? 'Saisie à corriger' : 'Aucune modification' }}</span></p>
                        <div class="gp-savebar-actions">
                            <a href="{{ $urlIndex }}#criteres" class="btn">Annuler</a>
                            <button type="submit" class="btn primary" data-gp-submit>Enregistrer la grille</button>
                        </div>
                    </div>
                </form>
            @endif
        </section>

        {{-- ================================================================
             Paliers de catégorisation
             ================================================================ --}}
        <section class="gp-panel" id="paliers" role="tabpanel" aria-labelledby="onglet-paliers" tabindex="-1">
            @if ($editionPaliers)
                <form method="POST" action="{{ role_route('ponderation.paliers.update') }}" class="gp-editor" data-gp-editor="paliers" @if (old('_form') === 'paliers') data-gp-dirty data-error-scope @endif>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_form" value="paliers">
            @endif

            <div class="gp-toolbar">
                <p class="gp-toolbar-text">Une fédération est classée dans le palier le plus élevé dont le seuil ne dépasse pas son score. Un score nul reste non classé.</p>
                @if ($editionPaliers)
                    <span class="gp-mode">Modification en cours</span>
                @else
                    <a href="{{ $urlIndex }}?edition=paliers#paliers" class="btn primary"><x-ui-icon name="edit" /> Modifier les paliers</a>
                @endif
            </div>

            <div class="gp-scale-card">
                <div class="gp-scale" data-gp-scale aria-hidden="true">
                    @if ($paliers->isNotEmpty() && (float) $paliers->first()->seuil_min > 0)
                        <span class="gp-scale-seg is-unranked" style="flex-grow: {{ (float) $paliers->first()->seuil_min }}" title="Non classé"></span>
                    @endif
                    @foreach ($paliers as $index => $palier)
                        @php
                            $fin = (float) ($paliers[$index + 1]->seuil_min ?? $reference);
                            [$encre, $teinte] = $ton($palier->categorie);
                        @endphp
                        <span class="gp-scale-seg" style="flex-grow: {{ max(0.5, $fin - (float) $palier->seuil_min) }}; --ink: {{ $encre }}; --tint: {{ $teinte }}" title="{{ $palier->code }} : de {{ $nombre($palier->seuil_min) }} à {{ $nombre($fin) }} points">{{ $palier->code }}</span>
                    @endforeach
                </div>
                <div class="gp-scale-cats" data-gp-scale-cats aria-hidden="true">
                    @if ($paliers->isNotEmpty() && (float) $paliers->first()->seuil_min > 0)
                        <span style="flex-grow: {{ (float) $paliers->first()->seuil_min }}"></span>
                    @endif
                    @foreach ($paliers->groupBy('categorie')->sortBy(fn ($groupe) => $groupe->min('seuil_min')) as $categorie => $groupe)
                        @php
                            $suivant = $paliers->first(fn ($palier) => (float) $palier->seuil_min > (float) $groupe->max('seuil_min'));
                            $fin = $suivant ? (float) $suivant->seuil_min : $reference;
                        @endphp
                        <span style="flex-grow: {{ max(0.5, $fin - (float) $groupe->min('seuil_min')) }}; --ink: {{ $ton($categorie)[0] }}">Catégorie {{ $categorie }}</span>
                    @endforeach
                </div>
                <div class="gp-scale-axis" aria-hidden="true"><span>0</span><span>{{ $reference / 2 }}</span><span>{{ $reference }} points</span></div>
            </div>

            <ul class="gp-checks" data-gp-checks aria-live="polite">
                @forelse ($verifications as [$niveau, $texte])
                    <li class="is-{{ $niveau }}">{{ $texte }}</li>
                @empty
                    @if ($paliers->isNotEmpty())
                        <li class="is-ok">Échelle cohérente : {{ $paliers->count() }} paliers de 0 à {{ $reference }} points, sans doublon.</li>
                    @endif
                @endforelse
            </ul>

            @if ($paliers->isEmpty() && ! $editionPaliers)
                <div class="gp-empty">
                    <p>Aucun palier : les fédérations ne peuvent pas être catégorisées.</p>
                    <a href="{{ $urlIndex }}?edition=paliers#paliers" class="btn primary"><x-ui-icon name="plus" /> Définir les paliers</a>
                </div>
            @else
                <div class="gp-table-wrap">
                    <table class="gp-table gp-paliers">
                        <caption class="sr-only">Paliers de catégorisation, du plus faible au plus élevé</caption>
                        <colgroup>
                            <col class="gp-col-code">
                            <col class="gp-col-code">
                            <col class="gp-col-points">
                            <col>
                            @if ($editionPaliers)<col class="gp-col-actions">@endif
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">Palier</th>
                                <th scope="col">Catégorie</th>
                                <th scope="col" class="gp-points">À partir de (pts)</th>
                                <th scope="col">Plage de score</th>
                                @if ($editionPaliers)<th scope="col"><span class="sr-only">Supprimer</span></th>@endif
                            </tr>
                        </thead>
                        <tbody data-gp-paliers>
                            @foreach ($paliers as $index => $palier)
                                @php
                                    $suivant = $paliers[$index + 1] ?? null;
                                    [$encre, $teinte] = $ton($palier->categorie);
                                    $palierSupprime = in_array($palier->id, old('supprimer_paliers', []));
                                @endphp
                                <tr @class(['gp-palier', 'is-deleted' => $palierSupprime]) data-gp-palier>
                                    <td>
                                        @if ($editionPaliers)
                                            <input type="text" name="paliers[{{ $palier->id }}][code]" value="{{ old('paliers.'.$palier->id.'.code', $palier->code) }}" class="form-input gp-input-code" data-gp-code aria-label="Code du palier {{ $palier->code }}" maxlength="20" required>
                                        @else
                                            <span class="gp-tier" style="--ink: {{ $encre }}; --tint: {{ $teinte }}">{{ $palier->code }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($editionPaliers)
                                            <input type="text" name="paliers[{{ $palier->id }}][categorie]" value="{{ old('paliers.'.$palier->id.'.categorie', $palier->categorie) }}" class="form-input gp-input-code" data-gp-categorie aria-label="Catégorie du palier {{ $palier->code }}" maxlength="20" required>
                                        @else
                                            {{ $palier->categorie }}
                                        @endif
                                    </td>
                                    <td class="gp-points">
                                        @if ($editionPaliers)
                                            <input type="number" name="paliers[{{ $palier->id }}][seuil_min]" value="{{ old('paliers.'.$palier->id.'.seuil_min', 0 + $palier->seuil_min) }}" class="form-input gp-input-points" data-gp-seuil min="0" max="100" step="0.5" aria-label="Seuil du palier {{ $palier->code }}, en points" required>
                                        @else
                                            {{ $nombre($palier->seuil_min) }}
                                        @endif
                                    </td>
                                    <td class="gp-plage" data-gp-plage>
                                        @if ($suivant)
                                            de {{ $nombre($palier->seuil_min) }} à moins de {{ $nombre($suivant->seuil_min) }}
                                        @else
                                            {{ $nombre($palier->seuil_min) }} et plus
                                        @endif
                                    </td>
                                    @if ($editionPaliers)
                                        <td class="gp-actions">
                                            <label class="gp-delete" title="Supprimer le palier">
                                                <input type="checkbox" name="supprimer_paliers[]" value="{{ $palier->id }}" data-gp-delete @checked($palierSupprime)>
                                                <x-ui-icon name="trash" class="gp-icon-delete" /><x-ui-icon name="undo" class="gp-icon-restore" />
                                                <span class="sr-only">Supprimer le palier {{ $palier->code }}</span>
                                            </label>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            @if ($editionPaliers)
                                @foreach (old('nouveaux_paliers', []) as $cle => $valeurs)
                                    @include('dshn.partials.grille-nouveau-palier', ['nom' => 'nouveaux_paliers['.$cle.']', 'valeurs' => $valeurs])
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($editionPaliers)
                    <button type="button" class="gp-add is-block" data-gp-add-palier><x-ui-icon name="plus" /> Ajouter un palier</button>
                    <template data-gp-template="palier">@include('dshn.partials.grille-nouveau-palier', ['nom' => '__NOM__', 'valeurs' => []])</template>

                    <div class="gp-savebar">
                        <p><strong><span data-gp-count>{{ $paliers->count() }}</span> paliers</strong><span class="gp-savebar-sep" aria-hidden="true">·</span><span data-gp-changes role="status">{{ old('_form') === 'paliers' ? 'Saisie à corriger' : 'Aucune modification' }}</span></p>
                        <div class="gp-savebar-actions">
                            <a href="{{ $urlIndex }}#paliers" class="btn">Annuler</a>
                            <button type="submit" class="btn primary" data-gp-submit>Enregistrer les paliers</button>
                        </div>
                    </div>
                </form>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/grille-ponderation.js') }}"></script>
@endpush
