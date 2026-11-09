@extends('suivi.layouts.app')
@section('titre', 'Tableau de bord')

@section('contenu')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-0">Tableau de bord</h1>
        <small class="text-muted">
            {{ auth()->user()->aAccesNational() ? 'Vue nationale — toutes fédérations' : 'Périmètre : '.auth()->user()->federationTalents()?->nom }}
        </small>
    </div>
    <button class="btn btn-outline-secondary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#personnaliser"><i class="bi bi-sliders"></i> Personnaliser</button>
</div>

<form class="card card-body filtres mb-3 py-2">
    <div class="row g-2 align-items-end">
        @if ($federations->isNotEmpty())
            <div class="col-md-2"><select name="federation_id" class="form-select form-select-sm"><option value="">Toutes fédérations</option>
                @foreach ($federations as $f)<option value="{{ $f->id }}" @selected(($filtres['federation_id'] ?? '') == $f->id)>{{ $f->sigle }}</option>@endforeach</select></div>
        @endif
        <div class="col-md-2"><select name="discipline_id" class="form-select form-select-sm"><option value="">Toutes disciplines</option>
            @foreach ($disciplines as $d)<option value="{{ $d->id }}" @selected(($filtres['discipline_id'] ?? '') == $d->id)>{{ $d->nom }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="genre" class="form-select form-select-sm"><option value="">Tous genres</option>
            @foreach (['Masculin', 'Féminin'] as $g)<option @selected(($filtres['genre'] ?? '') === $g)>{{ $g }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="categorie" class="form-select form-select-sm"><option value="">Toutes catégories</option>
            @foreach (array_keys(\App\Models\Talents\Talent::CATEGORIES_AGE) as $c)<option @selected(($filtres['categorie'] ?? '') === $c)>{{ $c }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="annee" class="form-select form-select-sm"><option value="">Toutes saisons</option>
            @foreach (range(now()->year, now()->year - 5) as $a)<option @selected(($filtres['annee'] ?? '') == $a)>{{ $a }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-sm btn-success"><i class="bi bi-funnel"></i> Appliquer</button>
            <a href="{{ route('suivi.dashboard') }}" class="btn btn-sm btn-link">Réinitialiser</a></div>
    </div>
</form>

<div class="row g-3">
    @forelse ($widgets as $cle => $w)
        <div class="col-12 col-lg-{{ $w['largeur'] }}">
            <div class="card h-100">
                @unless ($cle === 'kpi_talents')
                    <div class="card-header d-flex align-items-center">{{ $w['titre'] }}</div>
                @endunless
                <div class="card-body {{ $cle === 'kpi_talents' ? 'p-0' : '' }}">
                    @include('suivi.dashboard.widgets.'.$cle, ['d' => $w['donnees'], 'id' => 'w_'.$cle])
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info">Tous les widgets sont masqués. Utilisez « Personnaliser » pour en réafficher.</div></div>
    @endforelse
</div>

{{-- Personnalisation : choix et ordre des widgets, enregistrés par utilisateur --}}
<div class="modal fade" id="personnaliser" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <form method="POST" action="{{ route('suivi.dashboard.preferences') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Personnaliser mon tableau de bord</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted">Cochez les widgets à afficher et ordonnez-les avec les flèches.</p>
                <ul class="list-group" id="liste-widgets">
                    @foreach (array_unique([...array_keys($widgets), ...array_keys($disponibles)]) as $cle)
                        <li class="list-group-item d-flex align-items-center gap-2">
                            <input type="hidden" name="ordre[]" value="{{ $cle }}">
                            <input type="checkbox" class="form-check-input" name="visibles[]" value="{{ $cle }}" id="v_{{ $cle }}" @checked(! in_array($cle, $masques))>
                            <label for="v_{{ $cle }}" class="flex-grow-1">{{ $disponibles[$cle]['titre'] }}</label>
                            <button type="button" class="btn btn-sm btn-light monter"><i class="bi bi-arrow-up"></i></button>
                            <button type="button" class="btn btn-sm btn-light descendre"><i class="bi bi-arrow-down"></i></button>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="modal-footer"><button class="btn btn-bf">Enregistrer</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('liste-widgets').addEventListener('click', e => {
        const btn = e.target.closest('button'); if (!btn) return;
        const li = btn.closest('li');
        if (btn.classList.contains('monter') && li.previousElementSibling) li.parentNode.insertBefore(li, li.previousElementSibling);
        if (btn.classList.contains('descendre') && li.nextElementSibling) li.parentNode.insertBefore(li.nextElementSibling, li);
    });

    // Fabrique de graphiques partagée par les widgets
    window.graphique = (id, type, labels, datasets, options = {}) => {
        const el = document.getElementById(id); if (!el) return;
        new Chart(el, {type, data: {labels, datasets}, options: Object.assign({
            maintainAspectRatio: false, plugins: {legend: {display: ['doughnut', 'pie'].includes(type) || datasets.length > 1, position: 'bottom'}}
        }, options)});
    };
</script>
@stack('widgets')
@endpush
