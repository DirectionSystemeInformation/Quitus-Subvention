<div class="d-flex gap-4 mb-2">
    <div><span class="fs-4 fw-bold text-danger">{{ $d['total'] }}</span> <small class="text-muted">athlète(s) indisponible(s)</small></div>
    <div><span class="fs-4 fw-bold">{{ $d['jours'] }}</span> <small class="text-muted">jours d'indisponibilité cumulés</small></div>
</div>
@forelse ($d['dossiers'] as $m)
    <div class="d-flex justify-content-between align-items-center border-bottom py-1 small">
        <span><a href="{{ route('suivi.talents.show', [$m->talent_id, 'onglet' => 'medical']) }}" class="text-decoration-none">{{ $m->talent->nom_complet }}</a>
            — {{ $m->type_blessure ?? $m->statut_medical }}</span>
        <span>{{ badge($m->statut_medical) }}
            @if ($m->date_retour_prevue)<span class="text-muted">retour {{ $m->date_retour_prevue->format('d/m') }}</span>@endif</span>
    </div>
@empty
    <p class="text-muted small mb-0"><i class="bi bi-check-circle text-success"></i> Aucun dossier médical ouvert.</p>
@endforelse
