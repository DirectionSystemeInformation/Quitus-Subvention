@extends('layouts.dashboard', ['active' => 'documents'])
@section('title', $title)
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/templatemo-crypto-pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/programme-form.css') }}">
@endpush
@section('content')
    @php
        $routePrefix = str_replace('_', '-', $type);
        $oldLines = old('lignes', []);
        $hasOldForm = old('form_loaded') === '1';
    @endphp
    <div class="page-header">
        <nav class="breadcrumb" aria-label="Fil d’Ariane"><a href="{{ route('documents.index', ['type' => $type, 'annee' => $year]) }}">Mes documents</a><span aria-hidden="true"> / </span><span aria-current="page">Préparer le programme</span></nav>
        <h1>{{ $title }} {{ $year }}</h1>
        <p>{{ auth()->user()->federation_name }} · Renseignez les activités prévues, puis vérifiez votre programme avant de le soumettre.</p>
    </div>
    <form method="GET" action="{{ route($routePrefix.'.create') }}" class="programme-year">
        <label for="programmeYear">Année du programme</label>
        <input id="programmeYear" type="number" name="annee" class="form-input" min="2000" max="2100" value="{{ $year }}" required>
        <button class="btn" type="submit">Changer d’année</button>
    </form>
    @if ($report?->status === 'rejete' && $report->rejection_reason)
        <div class="notice notice-error"><strong>Corrections demandées</strong><p>{{ $report->rejection_reason }}</p></div>
    @endif
    <form method="POST" action="{{ route($routePrefix.'.store') }}" id="activityForm" data-unsaved="{{ $hasOldForm ? 'true' : 'false' }}">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="form_loaded" value="1">
        <div class="card programme-summary">
            <div><span class="programme-caption">Activités renseignées</span><strong id="pbFilledCount">0</strong></div>
            <div><span class="programme-caption">Budget prévisionnel total</span><strong id="pbTotalAmount">0 FCFA</strong></div>
            <p id="saveState" role="status">{{ $report ? 'Dernier enregistrement : '.$report->updated_at->format('d/m/Y à H:i') : 'Aucun brouillon enregistré' }}</p>
        </div>
        <p class="programme-help">Pour soumettre, chaque activité renseignée doit avoir une désignation et un montant (0 si aucun coût). Les autres champs sont facultatifs. Les rubriques sans activité peuvent rester vides.</p>
        @if ($report?->status === 'soumis')
            <div class="notice notice-success">Ce programme est déjà soumis. Vous pouvez soumettre une version corrigée ; il ne peut plus être enregistré en brouillon.</div>
        @endif
        <div class="programme-actions">
            @if ($report?->status !== 'soumis')
                <button class="btn" type="submit" name="action" value="draft" formnovalidate>Enregistrer le brouillon</button>
            @endif
            <button class="btn primary" id="reviewProgramme" type="button">Vérifier et soumettre</button>
        </div>
        @foreach ($axes as $axe)
            <details class="card programme-axis" @if ($loop->first || $hasOldForm) open @endif>
                <summary><span>{{ $axe['label'] }}</span><span class="axis-total">0 FCFA</span></summary>
                @foreach ($axe['sous_axes'] as $sousAxe)
                    @php
                        $code = $sousAxe['code'];
                        if ($hasOldForm) {
                            $rows = is_array($oldLines) && is_array($oldLines[$code] ?? null) ? $oldLines[$code] : [];
                        } else {
                            $rows = $existingLines->filter(fn ($line) => $line->sous_axe_code === $code)->mapWithKeys(fn ($line) => [$line->numero_ligne => [
                                'designation' => $line->designation, 'montant' => $line->montant,
                                'contribution_partenaires' => $line->contribution_partenaires,
                                'date' => optional($line->date)->format('Y-m-d'), 'observations' => $line->observations,
                            ]])->all();
                            if (!count($rows)) $rows = [1 => []];
                        }
                        $nextIndex = count($rows) ? max(array_map('intval', array_keys($rows))) + 1 : 1;
                    @endphp
                    <section class="programme-subaxis" data-next-index="{{ $nextIndex }}" aria-labelledby="subaxis-{{ $sousAxe['id'] }}">
                        <h2 id="subaxis-{{ $sousAxe['id'] }}">{{ $code }} — {{ $sousAxe['label'] }}</h2>
                        <div class="programme-lines">
                            @foreach ($rows as $index => $row)
                                @include('activity-form.partials.programme-line', ['index' => $index, 'row' => is_array($row) ? $row : []])
                            @endforeach
                        </div>
                        <template>@include('activity-form.partials.programme-line', ['index' => '__INDEX__', 'row' => []])</template>
                        <button class="btn programme-add" type="button">+ Ajouter une activité</button>
                    </section>
                @endforeach
            </details>
        @endforeach
        <section class="card programme-review" id="programmeReview" hidden aria-labelledby="reviewTitle" tabindex="-1">
            <h2 id="reviewTitle">Vérifiez votre programme {{ $year }}</h2>
            <p id="reviewSummary"></p>
            <ul id="reviewAxes"></ul>
            <p>La soumission transmet le programme à la DSHN pour examen. Votre brouillon ne sera plus disponible comme brouillon.</p>
            <label class="programme-confirm"><input type="checkbox" name="confirm_submission" value="1"> J’ai vérifié les activités et les montants du programme.</label>
            <div class="btn-group"><button type="button" class="btn" id="backToProgramme">Revenir à la saisie</button><button type="submit" class="btn primary" name="action" value="submit">Confirmer la soumission</button></div>
        </section>
        <noscript><div class="notice notice-error">Activez JavaScript pour afficher le récapitulatif et soumettre. Vous pouvez enregistrer le brouillon avec le bouton ci-dessus.</div></noscript>
    </form>
@endsection
@push('scripts')
    <script src="{{ asset('js/programme-form.js') }}"></script>
@endpush
