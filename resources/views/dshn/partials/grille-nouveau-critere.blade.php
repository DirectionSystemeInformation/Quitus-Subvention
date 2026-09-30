{{-- Ligne « nouveau critère » de la grille en édition. $nom préfixe les champs
     (ex. nouveaux_criteres[3][1001]) ; « __NOM__ » dans le gabarit JS. --}}
<tr class="gp-critere is-new" data-gp-critere data-gp-new>
    <td class="gp-num"><span class="gp-new">Nouveau</span></td>
    <td><input type="text" name="{{ $nom }}[label]" value="{{ $valeurs['label'] ?? '' }}" class="form-input" placeholder="Libellé du critère" aria-label="Libellé du nouveau critère" maxlength="255" required></td>
    <td class="gp-points"><input type="number" name="{{ $nom }}[points_max]" value="{{ $valeurs['points_max'] ?? '' }}" class="form-input gp-input-points" data-gp-points placeholder="0" min="0" max="100" step="0.5" aria-label="Barème du nouveau critère" required></td>
    <td class="gp-actions"><button type="button" class="gp-delete" data-gp-remove title="Retirer ce critère"><x-ui-icon name="trash" /><span class="sr-only">Retirer ce nouveau critère</span></button></td>
</tr>
