<div class="row">
    <div class="col-md-6">
        <h6 class="small text-muted text-uppercase">Accompagnement</h6>
        @include('suivi.dashboard.widgets._graphique', ['d' => $d['parStatut'], 'type' => 'doughnut', 'hauteur' => 190, 'id' => $id.'_s'])
    </div>
    <div class="col-md-6">
        <h6 class="small text-muted text-uppercase">Domaines</h6>
        @include('suivi.dashboard.widgets._graphique', ['d' => $d['parDomaine'], 'type' => 'doughnut', 'hauteur' => 190, 'id' => $id.'_d'])
    </div>
</div>
<a href="{{ route('suivi.reconversions.index') }}" class="small">Voir les dossiers de reconversion</a>
