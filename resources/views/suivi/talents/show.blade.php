@extends('suivi.layouts.app')
@section('titre', $t->nom_complet)

@php
    $onglets = [
        'identite' => ['Identité & licences', 'bi-person-vcard', 'talents.voir'],
        'performances' => ['Performances', 'bi-trophy', 'performances.voir'],
        'physique' => ['Évaluations physiques', 'bi-speedometer2', 'evaluations.voir'],
        'medical' => ['Suivi médical', 'bi-heart-pulse', 'medical.voir'],
        'parcours' => ['Parcours & clubs', 'bi-signpost-split', 'parcours.voir'],
        'bourses' => ['Bourses', 'bi-cash-coin', 'bourses.voir'],
        'reconversion' => ['Reconversion', 'bi-mortarboard', 'reconversions.voir'],
        'historique' => ['Historique', 'bi-clock-history', 'talents.voir'],
    ];
    $onglets = array_filter($onglets, fn ($o) => auth()->user()->can($o[2]));
    $onglet = isset($onglets[$onglet]) ? $onglet : 'identite';
    $ajouter = fn ($route, $module) => auth()->user()->can($module.'.creer')
        ? '<a href="'.route('suivi.'.$route.'.create', ['talent_id' => $t->id]).'" class="btn btn-sm btn-bf"><i class="bi bi-plus-lg"></i> Ajouter</a>' : '';
@endphp

@section('contenu')
<nav class="mb-2 small"><a href="{{ route('suivi.talents.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Talents</a></nav>

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-3 align-items-center">
        @include('suivi.talents._avatar', ['t' => $t, 'taille' => 'avatar-lg'])
        <div class="flex-grow-1">
            <h1 class="h4 mb-1">{{ $t->nom_complet }} @if ($t->haut_potentiel)<i class="bi bi-star-fill text-warning" title="Haut potentiel"></i>@endif</h1>
            <div class="text-muted mb-2"><code>{{ $t->code }}</code> · {{ $t->discipline->nom }}{{ $t->specialite ? ' — '.$t->specialite : '' }} · {{ $t->federation->sigle }} · {{ $t->age }} ans ({{ $t->categorie_age }})</div>
            {{ badge($t->statut_carriere) }} {{ badge($t->statut_profil) }}
            @if ($t->licences->firstWhere('portee', 'Internationale'))<span class="badge rounded-pill text-bg-primary"><i class="bi bi-globe"></i> International</span>@endif
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            @can('talents.modifier')
                <a href="{{ route('suivi.talents.edit', $t) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i> Modifier</a>
                @if (in_array($t->statut_profil, ['Brouillon', 'Rejeté']))
                    <form method="POST" action="{{ route('suivi.talents.soumettre', $t) }}">@csrf<button class="btn btn-outline-success btn-sm"><i class="bi bi-send"></i> Soumettre</button></form>
                @endif
            @endcan
            @can('talents.valider')
                @if (in_array($t->statut_profil, ['En attente validation', 'En attente API']))
                    <form method="POST" action="{{ route('suivi.talents.valider', $t) }}">@csrf<button class="btn btn-success btn-sm"><i class="bi bi-check2-circle"></i> Valider</button></form>
                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejet"><i class="bi bi-x-circle"></i> Rejeter</button>
                @endif
            @endcan
            <a href="{{ route('suivi.talents.imprimer', $t) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Fiche PDF</a>
            @can('talents.supprimer')
                <form method="POST" action="{{ route('suivi.talents.destroy', $t) }}" data-confirmer="Archiver ce talent ?">@csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-archive"></i></button></form>
            @endcan
        </div>
    </div>
    @if ($t->statut_profil === 'Rejeté' && $t->motif_rejet)
        <div class="alert alert-danger rounded-0 mb-0"><strong>Rejeté :</strong> {{ $t->motif_rejet }}</div>
    @endif
</div>

<ul class="nav nav-tabs mb-3">
    @foreach ($onglets as $cle => [$libelle, $icone])
        <li class="nav-item"><a class="nav-link {{ $onglet === $cle ? 'active' : '' }}" href="?onglet={{ $cle }}"><i class="bi {{ $icone }}"></i> {{ $libelle }}</a></li>
    @endforeach
</ul>

@switch($onglet)
    @case('identite')
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card h-100"><div class="card-header">Identification</div>
                    <dl class="row card-body mb-0">
                        @foreach ([
                            'Date de naissance' => $t->date_naissance->format('d/m/Y').($t->lieu_naissance ? ' à '.$t->lieu_naissance : ''),
                            'Genre' => $t->genre, 'Nationalité(s)' => $t->nationalites, 'Région' => $t->region,
                            'Téléphone' => $t->telephone, 'E-mail' => $t->email, 'Fédération' => $t->federation->nom,
                            'Entraîneur référent' => $t->entraineur?->nom_complet,
                            'Validé par' => $t->validateur ? $t->validateur->name.' le '.$t->valide_le->format('d/m/Y') : null,
                            'Observations' => $t->observations,
                        ] as $label => $valeur)
                            <dt class="col-sm-5 text-muted fw-normal">{{ $label }}</dt><dd class="col-sm-7">{{ $valeur ?: '—' }}</dd>
                        @endforeach
                    </dl>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">Licences {!! $ajouter('licences', 'licences') !!}</div>
                    <div class="table-responsive"><table class="table mb-0">
                        <thead class="table-light"><tr><th>Portée</th><th>N°</th><th>Organisme</th><th>Club</th><th>Expiration</th><th>Statut</th><th>API</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($t->licences as $l)
                            <tr>
                                <td>{{ badge($l->portee, $l->portee === 'Internationale' ? 'primary' : 'dark') }}<br><small class="text-muted">{{ $l->type_licence }}</small></td>
                                <td>{{ $l->numero }}</td><td>{{ $l->organisme }}</td>
                                <td>{{ $l->club }}{{ $l->pays_club ? ' ('.$l->pays_club.')' : '' }}</td>
                                <td>{{ $l->date_expiration?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ badge($l->statut) }}</td><td>{{ badge($l->verification_api) }}</td>
                                <td class="text-nowrap">
                                    @can('licences.verifier')
                                        @if ($l->verification_api !== 'Vérifiée')
                                            <form method="POST" action="{{ route('suivi.licences.verifier', $l) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success" title="Vérifier via API"><i class="bi bi-patch-check"></i></button></form>
                                        @endif
                                    @endcan
                                    @can('licences.modifier')<a href="{{ route('suivi.licences.edit', $l->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>@endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted text-center py-4">Aucune licence enregistrée.</td></tr>
                        @endforelse
                        </tbody>
                    </table></div>
                </div>
            </div>
        </div>
        @break

    @case('performances')
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Résultats en compétition <span class="badge text-bg-warning">{{ $t->performances->filter(fn ($p) => str_starts_with((string) $p->distinction, 'Médaille'))->count() }} médaille(s)</span></span>
                {!! $ajouter('performances', 'performances') !!}
            </div>
            <div class="table-responsive"><table class="table mb-0">
                <thead class="table-light"><tr><th>Date</th><th>Compétition</th><th>Niveau</th><th>Lieu</th><th>Épreuve</th><th>Performance</th><th>Rang</th><th>Distinction</th><th>Observations DTN</th><th></th></tr></thead>
                <tbody>
                @forelse ($t->performances as $p)
                    <tr>
                        <td>{{ $p->date_evenement->format('d/m/Y') }}</td><td>{{ $p->competition->nom }}</td>
                        <td>{{ badge($p->competition->niveau, 'info') }}</td><td>{{ $p->competition->pays }}</td>
                        <td>{{ $p->epreuve }}</td><td class="fw-semibold">{{ $p->performance }}</td><td>{{ $p->rang }}</td>
                        <td>{{ $p->distinction ? badge($p->distinction) : '' }} @if ($p->minima_atteint){{ badge('Minima', 'success') }}@endif</td>
                        <td><small>{{ $p->observations_dtn }}</small></td>
                        <td>@can('performances.modifier')<a href="{{ route('suivi.performances.edit', $p->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>@endcan</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-muted text-center py-4">Aucune performance enregistrée.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
        @break

    @case('physique')
        <div class="row g-3">
            @if ($t->evaluations->count() > 1)
                <div class="col-lg-5"><div class="card h-100"><div class="card-header">Évolution</div><div class="card-body"><canvas id="evolution" height="220"></canvas></div></div></div>
            @endif
            <div class="col">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">Tests physiques & biométrie {!! $ajouter('evaluations', 'evaluations') !!}</div>
                    <div class="table-responsive"><table class="table mb-0">
                        <thead class="table-light"><tr><th>Date</th><th>Taille</th><th>Poids</th><th>IMC</th><th>VMA</th><th>Détente</th><th>Sprint 30m</th><th>Avis</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($t->evaluations as $e)
                            <tr><td>{{ $e->date_evaluation->format('d/m/Y') }}</td><td>{{ $e->taille_cm }} cm</td><td>{{ $e->poids_kg }} kg</td><td>{{ $e->imc }}</td>
                                <td>{{ $e->vma_kmh }} km/h</td><td>{{ $e->detente_cm }} cm</td><td>{{ $e->sprint_30m_s }} s</td><td><small>{{ $e->avis }}</small></td>
                                <td>@can('evaluations.modifier')<a href="{{ route('suivi.evaluations.edit', $e->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>@endcan</td></tr>
                        @empty
                            <tr><td colspan="9" class="text-muted text-center py-4">Aucune évaluation.</td></tr>
                        @endforelse
                        </tbody>
                    </table></div>
                </div>
            </div>
        </div>
        @if ($t->evaluations->count() > 1)
            @push('scripts')
            <script>
                @php($evals = $t->evaluations->sortBy('date_evaluation')->values())
                new Chart(document.getElementById('evolution'), {type: 'line', data: {
                    labels: @json($evals->map(fn ($e) => $e->date_evaluation->format('m/Y'))),
                    datasets: [
                        {label: 'VMA (km/h)', data: @json($evals->pluck('vma_kmh')), borderColor: PALETTE[0], tension: .3},
                        {label: 'Détente (cm)', data: @json($evals->pluck('detente_cm')), borderColor: PALETTE[1], tension: .3, yAxisID: 'y2'},
                    ]}, options: {scales: {y2: {position: 'right', grid: {display: false}}}}});
            </script>
            @endpush
        @endif
        @break

    @case('medical')
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center"><span><i class="bi bi-lock"></i> Dossier médical (confidentiel)</span> {!! $ajouter('medical', 'medical') !!}</div>
            <div class="table-responsive"><table class="table mb-0">
                <thead class="table-light"><tr><th>Date</th><th>Statut</th><th>Blessure</th><th>Localisation</th><th>Indispo.</th><th>Retour prévu</th><th>Dossier</th><th>Avis</th><th></th></tr></thead>
                <tbody>
                @forelse ($t->suivisMedicaux as $m)
                    <tr><td>{{ $m->date_consultation->format('d/m/Y') }}</td><td>{{ badge($m->statut_medical) }}</td><td>{{ $m->type_blessure ?: 'RAS' }}</td>
                        <td>{{ $m->localisation }}</td><td>{{ $m->jours_indisponibilite }} j</td><td>{{ $m->date_retour_prevue?->format('d/m/Y') }}</td>
                        <td>{{ $m->cloture ? badge('Clôturé', 'secondary') : badge('Ouvert', 'warning') }}</td><td><small>{{ $m->avis_medical }}</small></td>
                        <td>@can('medical.modifier')<a href="{{ route('suivi.medical.edit', $m->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>@endcan</td></tr>
                @empty
                    <tr><td colspan="9" class="text-muted text-center py-4">Aucun dossier médical.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
        @break

    @case('parcours')
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">Historique des clubs & encadrement {!! $ajouter('parcours', 'parcours') !!}</div>
            <div class="card-body">
                @forelse ($t->parcours as $p)
                    <div class="d-flex gap-3 pb-3 mb-3 border-bottom">
                        <div class="text-success fw-bold text-nowrap" style="min-width:90px">{{ $p->saison }}</div>
                        <div class="flex-grow-1">
                            <strong>{{ $p->club }}</strong> <span class="text-muted">({{ $p->pays }})</span> {{ badge($p->statut_contractuel, 'light text-dark border') }}
                            <div class="small text-muted">Entraîneur : {{ $p->entraineur?->nom_complet ?? '—' }} · Scolarité : {{ $p->niveau_scolaire ?? '—' }}</div>
                            @if ($p->commentaires)<div class="small">{{ $p->commentaires }}</div>@endif
                        </div>
                        @can('parcours.modifier')<a href="{{ route('suivi.parcours.edit', $p->id) }}" class="btn btn-sm btn-outline-primary align-self-start"><i class="bi bi-pencil"></i></a>@endcan
                    </div>
                @empty
                    <p class="text-muted text-center mb-0">Aucun parcours renseigné.</p>
                @endforelse
            </div>
        </div>
        @break

    @case('bourses')
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">Bourses & appuis de l'État {!! $ajouter('bourses', 'bourses') !!}</div>
            <div class="table-responsive"><table class="table mb-0">
                <thead class="table-light"><tr><th>Type</th><th>Financeur</th><th>Montant</th><th>Période</th><th>Statut</th><th>Observations</th><th></th></tr></thead>
                <tbody>
                @forelse ($t->bourses as $b)
                    <tr><td>{{ $b->type_bourse }}</td><td>{{ $b->financeur }}</td><td>{{ montant($b->montant, $b->devise === 'XOF' ? 'FCFA' : $b->devise) }}</td>
                        <td>{{ $b->date_debut?->format('m/Y') }} → {{ $b->date_fin?->format('m/Y') }}</td><td>{{ badge($b->statut) }}</td><td><small>{{ $b->observations }}</small></td>
                        <td>@can('bourses.modifier')<a href="{{ route('suivi.bourses.edit', $b->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>@endcan</td></tr>
                @empty
                    <tr><td colspan="7" class="text-muted text-center py-4">Aucune bourse.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
        @break

    @case('reconversion')
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">Retraite sportive & reconversion
                @if (! $t->reconversion) {!! $ajouter('reconversions', 'reconversions') !!} @endif
            </div>
            @if ($r = $t->reconversion)
                <dl class="row card-body mb-0">
                    @foreach (['Fin de carrière' => $r->annee_fin_carriere, 'Motif' => $r->motif_retraite, 'Niveau d\'études' => $r->niveau_etudes,
                               'Projet' => $r->projet, 'Domaine' => $r->domaine, 'Accompagnement' => badge($r->statut_accompagnement),
                               'Rôle actuel' => $r->role_actuel, 'Appui de l\'État' => $r->programme_appui, 'Besoins' => $r->besoins] as $label => $valeur)
                        <dt class="col-sm-3 text-muted fw-normal">{{ $label }}</dt><dd class="col-sm-9">{{ $valeur ?: '—' }}</dd>
                    @endforeach
                </dl>
                @can('reconversions.modifier')<div class="card-footer bg-white"><a href="{{ route('suivi.reconversions.edit', $r->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Modifier</a></div>@endcan
            @else
                <div class="card-body text-muted text-center">Athlète en activité : aucun dossier de reconversion ouvert.</div>
            @endif
        </div>
        @break

    @case('historique')
        <div class="card"><ul class="list-group list-group-flush">
            @forelse ($historique as $h)
                <li class="list-group-item d-flex gap-3">
                    <small class="text-muted text-nowrap">{{ $h->created_at->format('d/m/Y H:i') }}</small>
                    <span>{{ badge($h->action, 'light text-dark border') }} par <strong>{{ $h->user?->name ?? 'Système' }}</strong>
                        @if ($h->modifications)<small class="text-muted">— {{ implode(', ', array_keys($h->modifications)) }}</small>@endif</span>
                </li>
            @empty
                <li class="list-group-item text-muted">Aucun historique.</li>
            @endforelse
        </ul></div>
        @break
@endswitch

@can('talents.valider')
<div class="modal fade" id="rejet" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('suivi.talents.rejeter', $t) }}" class="modal-content">
    @csrf
    <div class="modal-header"><h5 class="modal-title">Rejeter le profil</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">Motif (transmis à la fédération)</label><textarea name="motif_rejet" class="form-control" rows="3" required></textarea></div>
    <div class="modal-footer"><button class="btn btn-danger">Rejeter</button></div>
</form></div></div>
@endcan
@endsection
