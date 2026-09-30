{{-- Ligne « nouveau palier » en édition. $nom préfixe les champs
     (ex. nouveaux_paliers[1003]) ; « __NOM__ » dans le gabarit JS. --}}
<tr class="gp-palier is-new" data-gp-palier data-gp-new>
    <td><input type="text" name="{{ $nom }}[code]" value="{{ $valeurs['code'] ?? '' }}" class="form-input gp-input-code" data-gp-code placeholder="A1" aria-label="Code du nouveau palier" maxlength="20" required></td>
    <td><input type="text" name="{{ $nom }}[categorie]" value="{{ $valeurs['categorie'] ?? '' }}" class="form-input gp-input-code" data-gp-categorie placeholder="A" aria-label="Catégorie du nouveau palier" maxlength="20" required></td>
    <td class="gp-points"><input type="number" name="{{ $nom }}[seuil_min]" value="{{ $valeurs['seuil_min'] ?? '' }}" class="form-input gp-input-points" data-gp-seuil placeholder="0" min="0" max="100" step="0.5" aria-label="Seuil du nouveau palier, en points" required></td>
    <td class="gp-plage" data-gp-plage>—</td>
    <td class="gp-actions"><button type="button" class="gp-delete" data-gp-remove title="Retirer ce palier"><x-ui-icon name="trash" /><span class="sr-only">Retirer ce nouveau palier</span></button></td>
</tr>
