<div class="row g-3">
    <div class="col-md-5">
        @include('suivi.dashboard.widgets._graphique', ['d' => $d['parType'], 'type' => 'pie', 'hauteur' => 180, 'serie' => 'Licences'])
        <a href="{{ route('suivi.licences.index', ['verification_api' => 'Non vérifiée']) }}" class="d-block small mt-2 text-decoration-none">
            <i class="bi bi-exclamation-triangle text-warning"></i> {{ $d['nonVerifiees'] }} licence(s) non vérifiée(s) via API</a>
    </div>
    <div class="col-md-7">
        <h6 class="small text-muted text-uppercase">Expirent dans les 60 jours</h6>
        @forelse ($d['expirant'] as $l)
            <div class="d-flex justify-content-between small border-bottom py-1">
                <a href="{{ route('suivi.talents.show', $l->talent_id) }}" class="text-decoration-none">{{ $l->talent->nom_complet }}</a>
                <span>{{ $l->numero }} · <strong class="text-danger">{{ $l->date_expiration->format('d/m/Y') }}</strong></span>
            </div>
        @empty
            <p class="small text-muted">Aucune échéance proche.</p>
        @endforelse
        <a href="{{ route('suivi.licences.index', ['expire_bientot' => 1]) }}" class="small">Voir toutes les échéances</a>
    </div>
</div>
