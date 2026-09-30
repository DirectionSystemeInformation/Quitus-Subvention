{{-- Groupe « nouvelle rubrique » de la grille en édition, avec ses critères.
     $nom préfixe les champs (ex. nouvelles_rubriques[1002]). --}}
<tbody class="gp-rubrique is-new" data-gp-rubrique data-gp-new>
    <tr class="gp-rubrique-row">
        <td class="gp-num"><span class="gp-new">Nouvelle</span></td>
        <th scope="rowgroup" class="gp-rubrique-cell"><input type="text" name="{{ $nom }}[label]" value="{{ $valeurs['label'] ?? '' }}" class="form-input gp-input-rubrique" placeholder="Libellé de la rubrique" aria-label="Libellé de la nouvelle rubrique" maxlength="255" required></th>
        <td class="gp-points"><span class="gp-share" aria-hidden="true"><span data-gp-share style="width: 0%"></span></span><strong data-gp-subtotal>0</strong></td>
        <td class="gp-actions"><button type="button" class="gp-delete" data-gp-remove title="Retirer cette rubrique"><x-ui-icon name="trash" /><span class="sr-only">Retirer cette nouvelle rubrique et ses critères</span></button></td>
    </tr>
    @foreach ($valeurs['criteres'] ?? [] as $cle => $critere)
        @include('dshn.partials.grille-nouveau-critere', ['nom' => $nom.'[criteres]['.$cle.']', 'valeurs' => $critere])
    @endforeach
    <tr class="gp-add-row">
        <td></td>
        <td colspan="3"><button type="button" class="gp-add" data-gp-add-critere="{{ $nom }}[criteres]"><x-ui-icon name="plus" /> Ajouter un critère</button></td>
    </tr>
</tbody>
