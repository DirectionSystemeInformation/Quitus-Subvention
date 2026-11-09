<div class="row g-0 text-center">
    @foreach ([
        ['Talents suivis', $d['total'], 'bi-people', 'success', route('suivi.talents.index')],
        ['En activité', $d['actifs'], 'bi-activity', 'primary', route('suivi.talents.index', ['statut_carriere' => 'Actif'])],
        ['Athlètes féminines', $d['feminin'], 'bi-gender-female', 'danger', route('suivi.talents.index', ['genre' => 'Féminin'])],
        ['Licenciés internationaux', $d['internationaux'], 'bi-globe2', 'info', route('suivi.talents.index')],
        ['Hauts potentiels', $d['haut_potentiel'], 'bi-star', 'warning', route('suivi.talents.index', ['haut_potentiel' => 1])],
        ['Médailles', $d['medailles'], 'bi-award', 'warning', auth()->user()->can('performances.voir') ? route('suivi.performances.index') : '#'],
        ['Retraités / reconvertis', $d['retraites'], 'bi-mortarboard', 'secondary', route('suivi.talents.index', ['statut_carriere' => 'Retraité'])],
        ['Profils en attente', $d['en_attente'], 'bi-hourglass-split', 'danger', route('suivi.talents.index', ['statut_profil' => 'En attente validation'])],
    ] as [$label, $valeur, $icone, $couleur, $lien])
        <div class="col-6 col-md-3 border-end border-bottom">
            <a href="{{ $lien }}" class="d-flex align-items-center gap-3 p-3 text-decoration-none text-dark kpi">
                <span class="icone bg-{{ $couleur }}-subtle text-{{ $couleur }}"><i class="bi {{ $icone }}"></i></span>
                <span class="text-start"><span class="valeur d-block">{{ $valeur }}</span><small class="text-muted">{{ $label }}</small></span>
            </a>
        </div>
    @endforeach
</div>
