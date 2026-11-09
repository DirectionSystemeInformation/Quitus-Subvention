@extends('suivi.layouts.app')
@section('titre', 'Validation des profils')

@section('contenu')
<h1 class="h4 mb-3"><i class="bi bi-patch-check text-success"></i> Validation des profils</h1>

<div class="row g-3">
    @foreach ([
        ['En attente de validation', $enAttente, 'warning', 'Licence vérifiée : le profil peut être validé.'],
        ['En attente de vérification API', $attenteApi, 'info', 'La licence nationale n\'est pas encore confirmée par la fédération.'],
    ] as [$titre, $liste, $couleur, $aide])
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between"><span>{{ $titre }}</span><span class="badge text-bg-{{ $couleur }}">{{ $liste->count() }}</span></div>
                <div class="card-body py-2 small text-muted">{{ $aide }}</div>
                <ul class="list-group list-group-flush">
                    @forelse ($liste as $t)
                        <li class="list-group-item d-flex align-items-center gap-2">
                            @include('suivi.talents._avatar', ['t' => $t, 'taille' => ''])
                            <div class="flex-grow-1">
                                <a href="{{ route('suivi.talents.show', $t) }}" class="fw-semibold text-decoration-none">{{ $t->nom_complet }}</a>
                                <br><small class="text-muted">{{ $t->code }} · {{ $t->discipline->nom }} · {{ $t->federation->sigle }} · licence {{ $t->licenceNationale?->numero ?? 'absente' }}</small>
                            </div>
                            @if ($t->licenceNationale && $t->licenceNationale->verification_api !== 'Vérifiée')
                                @can('licences.verifier')
                                    <form method="POST" action="{{ route('suivi.licences.verifier', $t->licenceNationale) }}">@csrf<button class="btn btn-sm btn-outline-info"><i class="bi bi-patch-check"></i> API</button></form>
                                @endcan
                            @endif
                            <form method="POST" action="{{ route('suivi.talents.valider', $t) }}">@csrf<button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Valider</button></form>
                            <a href="{{ route('suivi.talents.show', $t) }}" class="btn btn-sm btn-outline-danger" title="Ouvrir pour rejeter"><i class="bi bi-x-lg"></i></a>
                        </li>
                    @empty
                        <li class="list-group-item text-muted text-center py-4">Rien en attente.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endforeach

    <div class="col-12">
        <div class="card">
            <div class="card-header">Derniers profils rejetés</div>
            <div class="table-responsive"><table class="table mb-0">
                <thead class="table-light"><tr><th>Talent</th><th>Fédération</th><th>Motif</th><th>Date</th></tr></thead>
                <tbody>
                @forelse ($rejetes as $t)
                    <tr><td><a href="{{ route('suivi.talents.show', $t) }}">{{ $t->nom_complet }}</a> <small class="text-muted">{{ $t->code }}</small></td>
                        <td>{{ $t->federation->sigle }}</td><td>{{ $t->motif_rejet }}</td><td>{{ $t->updated_at->format('d/m/Y') }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center py-3">Aucun rejet récent.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
</div>
@endsection
