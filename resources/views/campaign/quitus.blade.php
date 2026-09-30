@extends('layouts.dashboard', ['active' => 'campagnes'])

@php
    /** @var \App\Support\Quitus $quitus */
    $campaign = $quitus->campaign;
    $federation = $quitus->federation;
    $allocation = $quitus->allocation;
    $delivre = (bool) $allocation->quitus_delivered_at;
    $fcfa = fn ($montant) => \App\Support\Quitus::fcfa((float) $montant);
    $ecart = $quitus->ecart();
@endphp

@section('title', 'Quitus : '.$federation->federation_name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
@endpush

@section('content')
    <nav class="breadcrumb" aria-label="Fil d’Ariane">
        <a href="{{ route('campagnes.index') }}">← Campagnes</a><span class="breadcrumb-separator">/</span>
        <a href="{{ route('campagnes.show', $campaign) }}">{{ $campaign->annee_n1 }}</a><span class="breadcrumb-separator">/</span>
        <span>Quitus</span>
    </nav>

    <x-page-heading :title="$federation->federation_name" :eyebrow="'Quitus de la saison '.$campaign->annee_n1" description="Quitus pour le retrait de la subvention accordée, établi sur le modèle officiel à partir du programme réaménagé validé.">
        @if ($delivre)
            <span class="status-badge status-gain">Délivré le {{ $allocation->quitus_delivered_at->format('d/m/Y') }}</span>
            <a href="{{ route('campagnes.quitus.download', [$campaign, $federation]) }}" class="btn info"><x-ui-icon name="download" /> Télécharger le quitus</a>
        @else
            <span class="status-badge status-neutral">À délivrer</span>
        @endif
    </x-page-heading>

    @if (! $quitus->programmeValide())
        <div class="card">
            <x-empty-state icon="file" title="Le programme réaménagé de cette fédération n’est pas encore validé." />
            <p style="text-align: center; margin-top: 12px;"><a href="{{ route('campagnes.show', $campaign) }}" class="btn">Retour à la campagne</a></p>
        </div>
    @else
        <section class="quitus-summary" aria-label="Montants">
            <div>
                <span>Subvention accordée</span>
                <strong>{{ $fcfa($quitus->montantSubvention()) }} <small>FCFA</small></strong>
            </div>
            <div>
                <span>Total des activités</span>
                <strong>{{ $fcfa($quitus->totalActivites()) }} <small>FCFA</small></strong>
            </div>
            <div>
                <span>Activités</span>
                <strong>{{ $quitus->lignes->count() }}</strong>
            </div>
        </section>

        @if (abs($ecart) >= 1)
            <div class="notice notice-warning quitus-ecart" role="status">
                Le total des activités {{ $ecart > 0 ? 'dépasse' : 'est inférieur à' }} la subvention accordée de <strong>{{ $fcfa(abs($ecart)) }} FCFA</strong>.
                Le quitus arrête la subvention au montant accordé ; vérifiez le programme réaménagé avant de le délivrer.
            </div>
        @endif

        @unless ($delivre)
            <form method="POST" action="{{ route('campagnes.quitus.deliver', [$campaign, $federation]) }}" id="quitusForm"
                data-confirm="Délivrer le quitus à {{ $federation->federation_name }} ? Il deviendra téléchargeable par la fédération et ne pourra plus être modifié.">
                @csrf
        @endunless

        <section class="quitus-card" aria-labelledby="quitusActivitesTitre">
            <header class="quitus-card-head">
                <div>
                    <h2 id="quitusActivitesTitre">Activités et délais de justification</h2>
                    <p>{{ $delivre ? 'Délais fixés à la délivrance du quitus.' : 'Fixez la date limite à laquelle la fédération devra justifier chaque activité ; elle figure sur le quitus.' }}</p>
                </div>
                @unless ($delivre)
                    <div class="quitus-fill">
                        <label for="quitusJours">Délai uniforme</label>
                        <input type="number" id="quitusJours" class="form-input" value="30" min="0" max="365" step="1" inputmode="numeric">
                        <span>jours après chaque activité</span>
                        <button type="button" class="btn" data-quitus-fill>Appliquer</button>
                    </div>
                @endunless
            </header>

            @if ($quitus->lignes->isEmpty())
                <x-empty-state icon="list" title="Le programme réaménagé ne contient aucune activité." :compact="true" />
            @else
                <div class="table-responsive" tabindex="0" role="region" aria-label="Activités du quitus : défilement horizontal">
                    <table class="quitus-table">
                        <thead>
                            <tr>
                                <th scope="col" class="is-num">N°</th>
                                <th scope="col">Activité</th>
                                <th scope="col" class="is-amount">Montant (FCFA)</th>
                                <th scope="col">Date de l’activité</th>
                                <th scope="col">Délai de justification</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($quitus->lignes as $index => $ligne)
                                <tr data-quitus-ligne data-date="{{ $ligne->date?->format('Y-m-d') }}">
                                    <td class="is-num">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="quitus-activite">{{ $ligne->designation ?: $ligne->sous_axe_label }}</span>
                                        <span class="quitus-sous-axe">{{ $ligne->sous_axe_code }} · {{ $ligne->sous_axe_label }}</span>
                                    </td>
                                    <td class="is-amount">{{ $fcfa($ligne->montant) }}</td>
                                    <td>{{ $ligne->date?->format('d/m/Y') ?? '—' }}</td>
                                    <td>
                                        @if ($delivre)
                                            {{ $ligne->delai_justification?->format('d/m/Y') ?? '—' }}
                                        @else
                                            <input type="date" name="delais[{{ $ligne->id }}]" class="form-input quitus-delai"
                                                value="{{ old('delais.'.$ligne->id, $ligne->delai_justification?->format('Y-m-d')) }}"
                                                @if ($ligne->date) min="{{ $ligne->date->format('Y-m-d') }}" @endif
                                                aria-label="Délai de justification de l’activité n° {{ $index + 1 }}" required>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td></td>
                                <th scope="row">Total</th>
                                <td class="is-amount">{{ $fcfa($quitus->totalActivites()) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </section>

        @unless ($delivre)
                <div class="creation-actions campaign-save-bar">
                    <p>Le PDF reprend le modèle officiel : visas du DSHN et de la DG des sports, observations de la DGF et dates sont apposés à la main sur le document imprimé.</p>
                    <div class="btn-group">
                        <button type="submit" class="btn info" formaction="{{ route('campagnes.quitus.preview', [$campaign, $federation]) }}" formtarget="_blank" formnovalidate data-skip-confirm><x-ui-icon name="eye" /> Aperçu du PDF</button>
                        <button type="submit" class="btn primary" @disabled($quitus->lignes->isEmpty())>Délivrer le quitus</button>
                    </div>
                </div>
            </form>
        @endunless
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            'use strict';
            const bouton = document.querySelector('[data-quitus-fill]');
            if (!bouton) return;
            // Remplit chaque délai à partir de la date de l'activité ; les activités
            // sans date restent à saisir.
            bouton.addEventListener('click', () => {
                const jours = parseInt(document.getElementById('quitusJours').value, 10);
                if (!Number.isFinite(jours) || jours < 0) return;
                let remplis = 0;
                document.querySelectorAll('[data-quitus-ligne]').forEach(ligne => {
                    if (!ligne.dataset.date) return;
                    const date = new Date(ligne.dataset.date + 'T00:00:00');
                    date.setDate(date.getDate() + jours);
                    const champ = ligne.querySelector('.quitus-delai');
                    champ.value = [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
                    champ.dispatchEvent(new Event('input', { bubbles: true }));
                    remplis++;
                });
                const sansDate = document.querySelectorAll('[data-quitus-ligne][data-date=""]').length;
                bouton.textContent = remplis + ' délai' + (remplis > 1 ? 's' : '') + ' rempli' + (remplis > 1 ? 's' : '');
                if (sansDate) bouton.title = sansDate + ' activité(s) sans date : délai à saisir';
                setTimeout(() => { bouton.textContent = 'Appliquer'; }, 2200);
            });
        })();
    </script>
@endpush
