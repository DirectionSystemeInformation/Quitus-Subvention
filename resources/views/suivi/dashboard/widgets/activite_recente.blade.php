<ul class="list-group list-group-flush">
    @forelse ($d['activites'] as $a)
        <li class="list-group-item px-0 d-flex gap-3 small">
            <span class="text-muted text-nowrap" style="min-width:110px">{{ $a->created_at->diffForHumans() }}</span>
            <span><strong>{{ $a->user?->name ?? 'Système' }}</strong> — {{ $a->action }} · {{ $a->module }} <span class="text-muted">{{ $a->libelle }}</span></span>
        </li>
    @empty
        <li class="list-group-item text-muted">Aucune activité.</li>
    @endforelse
</ul>
<a href="{{ route('suivi.journal.index') }}" class="small">Journal complet</a>
