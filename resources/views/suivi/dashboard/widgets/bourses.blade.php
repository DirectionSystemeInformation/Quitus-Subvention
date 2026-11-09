<div class="row text-center g-2 mb-3">
    <div class="col-4"><div class="bg-light rounded p-2 h-100"><div class="fw-bold">{{ montant($d['montantActif']) }}</div><small class="text-muted">Engagé (en cours)</small></div></div>
    <div class="col-4"><div class="bg-light rounded p-2 h-100"><div class="fs-5 fw-bold">{{ $d['beneficiaires'] }}</div><small class="text-muted">Bénéficiaires</small></div></div>
    <div class="col-4"><a href="{{ route('suivi.bourses.index', ['statut' => 'Demandée']) }}" class="d-block bg-warning-subtle rounded p-2 h-100 text-decoration-none text-dark">
        <div class="fs-5 fw-bold">{{ $d['demandes'] }}</div><small>Demandes à instruire</small></a></div>
</div>
@include('suivi.dashboard.widgets._graphique', ['d' => $d['parType'], 'type' => 'bar', 'hauteur' => 170, 'serie' => 'FCFA'])
