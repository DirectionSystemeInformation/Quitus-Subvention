@extends('layouts.dashboard', ['active' => 'canevas'])

@section('title', 'Canevas des activités')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/referentiels.css') }}">
@endpush

@section('content')
    @php
        // Formulaire à rouvrir après un échec de validation.
        $editing = old('_edit');
        $creating = old('_creation');

        $sousAxesCount = $axes->sum(fn ($axe) => $axe->sousAxes->count());

        $romain = function (int $nombre): string {
            $resultat = '';
            foreach (['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1] as $chiffre => $valeur) {
                $resultat .= str_repeat($chiffre, intdiv($nombre, $valeur));
                $nombre %= $valeur;
            }

            return $resultat;
        };
    @endphp

    <div class="ref-page">
        <x-page-heading title="Canevas des activités" eyebrow="Référentiel" description="Axes et sous-axes du programme d'activités budgétisé et du rapport d'activité. Les fédérations voient toujours la version en vigueur ; les rapports déjà soumis conservent leurs libellés.">
            <button type="button" class="btn primary" data-ref-open aria-controls="add-axe" aria-expanded="{{ $creating === 'axis' ? 'true' : 'false' }}"><x-ui-icon name="plus" /> Ajouter un axe</button>
        </x-page-heading>

        <ul class="ref-summary" aria-label="Synthèse du canevas">
            <li><strong>{{ $axes->count() }}</strong> {{ $axes->count() > 1 ? 'axes' : 'axe' }}</li>
            <li><strong>{{ $sousAxesCount }}</strong> {{ $sousAxesCount > 1 ? 'sous-axes' : 'sous-axe' }}</li>
        </ul>

        <div class="ref-stack">
            @foreach ($axes as $axe)
                @php $cle = 'axe-'.$axe->id; @endphp
                <section class="ref-group" id="axe-{{ $axe->id }}" aria-labelledby="axe-titre-{{ $axe->id }}">
                    <header class="ref-group-head">
                        <div class="ref-group-view" id="axe-vue-{{ $axe->id }}" @if ($editing === $cle) hidden @endif>
                            <span class="ref-code is-axe">{{ $axe->code }}</span>
                            <div class="ref-group-title">
                                <h2 id="axe-titre-{{ $axe->id }}">{{ $axe->label }}</h2>
                                <p>{{ $axe->sousAxes->count() }} {{ $axe->sousAxes->count() > 1 ? 'sous-axes' : 'sous-axe' }}</p>
                            </div>
                            <div class="ref-actions">
                                <button type="button" class="ref-icon-btn" data-ref-open aria-controls="axe-form-{{ $axe->id }}" aria-expanded="false" aria-label="Modifier l’axe {{ $axe->code }}" title="Modifier l’axe"><x-ui-icon name="edit" /></button>
                                <form method="POST" action="{{ role_route('canevas.axes.destroy', $axe) }}" data-confirm="Supprimer l’axe {{ $axe->code }} et ses {{ $axe->sousAxes->count() }} sous-axe(s) ? Les rapports déjà soumis conservent leurs données.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ref-icon-btn is-danger" aria-label="Supprimer l’axe {{ $axe->code }}" title="Supprimer l’axe"><x-ui-icon name="trash" /></button>
                                </form>
                            </div>
                        </div>
                        <form id="axe-form-{{ $axe->id }}" method="POST" action="{{ role_route('canevas.axes.update', $axe) }}" class="ref-form" data-ref-panel data-ref-replaces="axe-vue-{{ $axe->id }}" @if ($editing === $cle) data-error-scope @else hidden @endif>
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="_edit" value="{{ $cle }}">
                            <div class="ref-field is-code">
                                <label for="axe-code-{{ $axe->id }}">Code</label>
                                <input id="axe-code-{{ $axe->id }}" type="text" name="code" class="form-input" value="{{ $editing === $cle ? old('code') : $axe->code }}" maxlength="20" required>
                            </div>
                            <div class="ref-field is-grow">
                                <label for="axe-libelle-{{ $axe->id }}">Libellé de l’axe</label>
                                <input id="axe-libelle-{{ $axe->id }}" type="text" name="label" class="form-input" value="{{ $editing === $cle ? old('label') : $axe->label }}" maxlength="255" required>
                            </div>
                            <div class="ref-form-actions">
                                <button type="submit" class="btn primary">Enregistrer</button>
                                <button type="button" class="btn" data-ref-cancel>Annuler</button>
                            </div>
                        </form>
                    </header>

                    @if ($axe->sousAxes->isEmpty())
                        <p class="ref-empty">Aucun sous-axe pour l’instant.</p>
                    @else
                        <ol class="ref-list" aria-label="Sous-axes de l’axe {{ $axe->code }}">
                            @foreach ($axe->sousAxes as $sousAxe)
                                @php $cle = 'sous-axe-'.$sousAxe->id; @endphp
                                <li class="ref-row" id="sous-axe-{{ $sousAxe->id }}">
                                    <div class="ref-row-view" id="sous-axe-vue-{{ $sousAxe->id }}" @if ($editing === $cle) hidden @endif>
                                        <span class="ref-code">{{ $sousAxe->code }}</span>
                                        <span class="ref-label">{{ $sousAxe->label }}</span>
                                        <div class="ref-actions">
                                            <button type="button" class="ref-icon-btn" data-ref-open aria-controls="sous-axe-form-{{ $sousAxe->id }}" aria-expanded="false" aria-label="Modifier le sous-axe {{ $sousAxe->code }}" title="Modifier"><x-ui-icon name="edit" /></button>
                                            <form method="POST" action="{{ role_route('canevas.sous-axes.destroy', $sousAxe) }}" data-confirm="Supprimer le sous-axe {{ $sousAxe->code }} ? Les rapports déjà soumis conservent leurs données.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ref-icon-btn is-danger" aria-label="Supprimer le sous-axe {{ $sousAxe->code }}" title="Supprimer"><x-ui-icon name="trash" /></button>
                                            </form>
                                        </div>
                                    </div>
                                    <form id="sous-axe-form-{{ $sousAxe->id }}" method="POST" action="{{ role_route('canevas.sous-axes.update', $sousAxe) }}" class="ref-form" data-ref-panel data-ref-replaces="sous-axe-vue-{{ $sousAxe->id }}" @if ($editing === $cle) data-error-scope @else hidden @endif>
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="_edit" value="{{ $cle }}">
                                        <div class="ref-field is-code">
                                            <label for="sous-axe-code-{{ $sousAxe->id }}">Code</label>
                                            <input id="sous-axe-code-{{ $sousAxe->id }}" type="text" name="code" class="form-input" value="{{ $editing === $cle ? old('code') : $sousAxe->code }}" maxlength="20" required>
                                        </div>
                                        <div class="ref-field is-grow">
                                            <label for="sous-axe-libelle-{{ $sousAxe->id }}">Libellé du sous-axe</label>
                                            <input id="sous-axe-libelle-{{ $sousAxe->id }}" type="text" name="label" class="form-input" value="{{ $editing === $cle ? old('label') : $sousAxe->label }}" maxlength="255" required>
                                        </div>
                                        <div class="ref-form-actions">
                                            <button type="submit" class="btn primary">Enregistrer</button>
                                            <button type="button" class="btn" data-ref-cancel>Annuler</button>
                                        </div>
                                    </form>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @php $cle = 'subaxis-'.$axe->id; @endphp
                    <div class="ref-add">
                        <button type="button" class="ref-add-toggle" id="ajout-sous-axe-bouton-{{ $axe->id }}" data-ref-open aria-controls="ajout-sous-axe-{{ $axe->id }}" aria-expanded="false" @if ($creating === $cle) hidden @endif><x-ui-icon name="plus" /> Ajouter un sous-axe</button>
                        <form id="ajout-sous-axe-{{ $axe->id }}" method="POST" action="{{ role_route('canevas.sous-axes.store', $axe) }}" class="ref-form" data-ref-panel data-ref-replaces="ajout-sous-axe-bouton-{{ $axe->id }}" @if ($creating === $cle) data-error-scope @else hidden @endif>
                            @csrf
                            <input type="hidden" name="_creation" value="{{ $cle }}">
                            <div class="ref-field is-code">
                                <label for="ajout-sous-axe-code-{{ $axe->id }}">Code</label>
                                <input id="ajout-sous-axe-code-{{ $axe->id }}" type="text" name="code" class="form-input" value="{{ $creating === $cle ? old('code') : $axe->code.'.'.($axe->sousAxes->count() + 1) }}" maxlength="20" required>
                            </div>
                            <div class="ref-field is-grow">
                                <label for="ajout-sous-axe-libelle-{{ $axe->id }}">Libellé du sous-axe</label>
                                <input id="ajout-sous-axe-libelle-{{ $axe->id }}" type="text" name="label" class="form-input" value="{{ $creating === $cle ? old('label') : '' }}" placeholder="Ex. Compétitions régionales" maxlength="255" required>
                            </div>
                            <div class="ref-form-actions">
                                <button type="submit" class="btn primary">Ajouter</button>
                                <button type="button" class="btn" data-ref-cancel>Annuler</button>
                            </div>
                        </form>
                    </div>
                </section>
            @endforeach

            <section class="ref-group is-new" aria-label="Nouvel axe">
                <button type="button" class="ref-add-toggle" id="ajout-axe-bouton" data-ref-open aria-controls="add-axe" aria-expanded="false" @if ($creating === 'axis') hidden @endif>
                    <span><x-ui-icon name="plus" /> Ajouter un axe</span>
                    <small>Un axe regroupe des sous-axes d’activités de même nature.</small>
                </button>
                <form id="add-axe" method="POST" action="{{ role_route('canevas.axes.store') }}" class="ref-form" data-ref-panel data-ref-replaces="ajout-axe-bouton" @if ($creating === 'axis') data-error-scope @else hidden @endif>
                    @csrf
                    <input type="hidden" name="_creation" value="axis">
                    <p class="ref-form-title">Nouvel axe</p>
                    <div class="ref-field is-code">
                        <label for="ajout-axe-code">Code</label>
                        <input id="ajout-axe-code" type="text" name="code" class="form-input" value="{{ $creating === 'axis' ? old('code') : $romain($axes->count() + 1) }}" maxlength="20" required>
                    </div>
                    <div class="ref-field is-grow">
                        <label for="ajout-axe-libelle">Libellé de l’axe</label>
                        <input id="ajout-axe-libelle" type="text" name="label" class="form-input" value="{{ $creating === 'axis' ? old('label') : '' }}" placeholder="Intitulé de l’axe stratégique" maxlength="255" required>
                    </div>
                    <div class="ref-form-actions">
                        <button type="submit" class="btn primary">Créer l’axe</button>
                        <button type="button" class="btn" data-ref-cancel>Annuler</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/referentiels.js') }}"></script>
@endpush
