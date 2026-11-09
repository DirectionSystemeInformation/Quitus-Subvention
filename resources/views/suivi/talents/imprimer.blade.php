<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche {{ $t->code }} — {{ $t->nom_complet }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 12px; } h2 { font-size: 14px; border-bottom: 2px solid #00843d; padding-bottom: 4px; margin-top: 18px; }
        .bandeau { border-top: 6px solid #c8102e; border-bottom: 6px solid #00843d; }
        @page { size: A4; margin: 14mm; }
    </style>
</head>
<body onload="window.print()" class="p-3">
<div class="bandeau py-2 mb-3 d-flex justify-content-between align-items-center">
    <div><strong>MINISTÈRE DES SPORTS — BURKINA FASO</strong><br>Plateforme nationale de suivi des talents sportifs</div>
    <div class="text-end"><strong>{{ $t->code }}</strong><br>Édité le {{ now()->format('d/m/Y') }}</div>
</div>

<h1 class="h5">{{ $t->nom_complet }}</h1>
<table class="table table-sm table-bordered">
    <tr><th>Date de naissance</th><td>{{ $t->date_naissance->format('d/m/Y') }} ({{ $t->age }} ans, {{ $t->categorie_age }})</td><th>Genre</th><td>{{ $t->genre }}</td></tr>
    <tr><th>Discipline</th><td>{{ $t->discipline->nom }} — {{ $t->specialite }}</td><th>Fédération</th><td>{{ $t->federation->nom }}</td></tr>
    <tr><th>Statut carrière</th><td>{{ $t->statut_carriere }}</td><th>Statut profil</th><td>{{ $t->statut_profil }}</td></tr>
</table>

<h2>Licences</h2>
<table class="table table-sm table-bordered"><tr><th>Portée</th><th>N°</th><th>Organisme</th><th>Club</th><th>Statut</th></tr>
    @forelse ($t->licences as $l)<tr><td>{{ $l->portee }}</td><td>{{ $l->numero }}</td><td>{{ $l->organisme }}</td><td>{{ $l->club }}</td><td>{{ $l->statut }}</td></tr>
    @empty<tr><td colspan="5">—</td></tr>@endforelse
</table>

<h2>Performances</h2>
<table class="table table-sm table-bordered"><tr><th>Date</th><th>Compétition</th><th>Niveau</th><th>Performance</th><th>Rang</th><th>Distinction</th></tr>
    @forelse ($t->performances as $p)<tr><td>{{ $p->date_evenement->format('d/m/Y') }}</td><td>{{ $p->competition->nom }}</td><td>{{ $p->competition->niveau }}</td><td>{{ $p->performance }}</td><td>{{ $p->rang }}</td><td>{{ $p->distinction }}</td></tr>
    @empty<tr><td colspan="6">—</td></tr>@endforelse
</table>

<h2>Dernière évaluation physique</h2>
@if ($e = $t->evaluations->first())
    <p>{{ $e->date_evaluation->format('d/m/Y') }} — Taille {{ $e->taille_cm }} cm, poids {{ $e->poids_kg }} kg (IMC {{ $e->imc }}), VMA {{ $e->vma_kmh }} km/h, détente {{ $e->detente_cm }} cm, sprint 30 m {{ $e->sprint_30m_s }} s. {{ $e->avis }}</p>
@else<p>—</p>@endif

<h2>Parcours</h2>
<table class="table table-sm table-bordered"><tr><th>Saison</th><th>Club</th><th>Pays</th><th>Statut</th><th>Scolarité</th></tr>
    @forelse ($t->parcours as $p)<tr><td>{{ $p->saison }}</td><td>{{ $p->club }}</td><td>{{ $p->pays }}</td><td>{{ $p->statut_contractuel }}</td><td>{{ $p->niveau_scolaire }}</td></tr>
    @empty<tr><td colspan="5">—</td></tr>@endforelse
</table>

@if ($r = $t->reconversion)
    <h2>Reconversion</h2>
    <p>Fin de carrière {{ $r->annee_fin_carriere }} ({{ $r->motif_retraite }}). Projet : {{ $r->projet }} — {{ $r->domaine }}. Accompagnement : {{ $r->statut_accompagnement }}.</p>
@endif
<p class="text-muted small mt-4">Document confidentiel — les données médicales ne figurent pas sur cette fiche.</p>
</body>
</html>
