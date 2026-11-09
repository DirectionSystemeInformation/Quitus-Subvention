@php
    $type = $champ['type'] ?? 'text';
    $valeur = old($nom, $valeur instanceof \Carbon\CarbonInterface ? $valeur->format('Y-m-d') : $valeur);
    $requis = $champ['requis'] ?? false;
@endphp
<div class="col-md-{{ $champ['col'] ?? 6 }}">
    @if ($type === 'checkbox')
        <div class="form-check form-switch mt-md-4">
            <input type="checkbox" class="form-check-input" name="{{ $nom }}" id="{{ $nom }}" value="1" @checked($valeur)>
            <label class="form-check-label" for="{{ $nom }}">{{ $champ['label'] }}</label>
        </div>
    @else
        <label class="form-label" for="{{ $nom }}">{{ $champ['label'] }} @if ($requis)<span class="text-danger">*</span>@endif</label>
        @switch($type)
            @case('talent')
                <select name="{{ $nom }}" id="{{ $nom }}" class="form-select recherche @error($nom) is-invalid @enderror" @required($requis)>
                    <option value="">— Choisir un talent —</option>
                    @foreach ($talents as $t)
                        <option value="{{ $t->id }}" @selected($valeur == $t->id)>{{ $t->code }} — {{ $t->nom_complet }}</option>
                    @endforeach
                </select>
                @break
            @case('select')
                <select name="{{ $nom }}" id="{{ $nom }}" class="form-select @error($nom) is-invalid @enderror" @required($requis)>
                    <option value="">—</option>
                    @foreach ($champ['options'] as $cle => $libelle)
                        <option value="{{ $cle }}" @selected((string) $valeur === (string) $cle)>{{ $libelle }}</option>
                    @endforeach
                    {{-- Conserve une ancienne valeur retirée des listes de référence --}}
                    @if ($valeur && ! array_key_exists($valeur, $champ['options']))
                        <option value="{{ $valeur }}" selected>{{ $valeur }} (archivé)</option>
                    @endif
                </select>
                @break
            @case('textarea')
                <textarea name="{{ $nom }}" id="{{ $nom }}" rows="3" class="form-control @error($nom) is-invalid @enderror" @required($requis)>{{ $valeur }}</textarea>
                @break
            @case('password')
                <input type="password" name="{{ $nom }}" id="{{ $nom }}" class="form-control" autocomplete="new-password">
                @break
            @default
                <input type="{{ $type }}" name="{{ $nom }}" id="{{ $nom }}" value="{{ $valeur }}" step="{{ $champ['step'] ?? 'any' }}"
                       class="form-control @error($nom) is-invalid @enderror" @required($requis)>
        @endswitch
        @error($nom)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @isset($champ['aide'])<div class="form-text">{{ $champ['aide'] }}</div>@endisset
    @endif
</div>
