@if ($d['performances']->isEmpty())
    @include('suivi.dashboard.widgets._vide')
@else
    <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Date</th><th>Talent</th><th>Compétition</th><th>Niveau</th><th>Épreuve</th><th>Performance</th><th>Rang</th><th>Distinction</th></tr></thead>
        <tbody>
        @foreach ($d['performances'] as $p)
            <tr>
                <td>{{ $p->date_evenement->format('d/m/Y') }}</td>
                <td><a href="{{ route('suivi.talents.show', [$p->talent_id, 'onglet' => 'performances']) }}" class="text-decoration-none">{{ $p->talent->nom_complet }}</a>
                    <small class="text-muted">{{ $p->talent->discipline->nom }}</small></td>
                <td>{{ $p->competition->nom }}</td>
                <td>{{ badge($p->competition->niveau, 'info') }}</td>
                <td>{{ $p->epreuve }}</td>
                <td class="fw-semibold">{{ $p->performance }}</td>
                <td>{{ $p->rang }}</td>
                <td>{{ $p->distinction ? badge($p->distinction) : '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
@endif
