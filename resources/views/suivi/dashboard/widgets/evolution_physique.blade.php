<div class="row text-center g-2 mb-3">
    @foreach ([['Tests réalisés', $d['nombre'], ''], ['VMA moyenne', $d['vma'], 'km/h'], ['Détente moy.', $d['detente'], 'cm'], ['Sprint 30m moy.', $d['sprint'], 's']] as [$label, $valeur, $unite])
        <div class="col-6 col-md-3">
            <div class="bg-light rounded p-2"><div class="fs-5 fw-bold">{{ $valeur }} <small class="fs-6 fw-normal">{{ $unite }}</small></div><small class="text-muted">{{ $label }}</small></div>
        </div>
    @endforeach
</div>
<h6 class="small text-muted text-uppercase">Meilleures VMA</h6>
@forelse ($d['meilleurs'] as $e)
    <div class="d-flex justify-content-between small border-bottom py-1">
        <a href="{{ route('suivi.talents.show', [$e->talent_id, 'onglet' => 'physique']) }}" class="text-decoration-none">{{ $e->talent->nom_complet }}</a>
        <strong>{{ $e->vma_kmh }} km/h</strong>
    </div>
@empty
    <p class="small text-muted mb-0">Aucune évaluation.</p>
@endforelse
