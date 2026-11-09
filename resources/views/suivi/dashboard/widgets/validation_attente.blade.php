@if ($d['talents']->isEmpty())
    <div class="text-center text-muted py-4"><i class="bi bi-check2-all fs-3 d-block text-success"></i>Aucun profil en attente.</div>
@else
    <ul class="list-group list-group-flush">
        @foreach ($d['talents'] as $t)
            <li class="list-group-item px-0 d-flex align-items-center gap-2">
                @include('suivi.talents._avatar', ['t' => $t, 'taille' => ''])
                <div class="flex-grow-1"><a href="{{ route('suivi.talents.show', $t) }}" class="text-decoration-none fw-semibold">{{ $t->nom_complet }}</a>
                    <br><small class="text-muted">{{ $t->discipline->nom }} · {{ $t->federation->sigle }} · {{ $t->updated_at->diffForHumans() }}</small></div>
                {{ badge($t->statut_profil) }}
            </li>
        @endforeach
    </ul>
    <a href="{{ route('suivi.talents.validations') }}" class="btn btn-sm btn-outline-success mt-2">Traiter les validations</a>
@endif
