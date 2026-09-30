/*
 * Grille de pondération (DSHN).
 * - Onglets « Critères » / « Paliers » (motif ARIA tabs, flèches du clavier).
 * - Édition en lot : totaux et sous-totaux en direct, lignes ajoutées depuis
 *   des <template>, suppressions réversibles jusqu'à l'enregistrement.
 * - Paliers : plages, échelle et contrôles de cohérence recalculés en direct.
 */
(function () {
    'use strict';

    const page = document.querySelector('[data-gp-page]');
    if (!page) return;

    const reference = Number(page.dataset.gpReference) || 100;
    const tons = JSON.parse(page.dataset.gpTons || '[]');
    const nombre = valeur => valeur.toLocaleString('fr-FR', { maximumFractionDigits: 2 });
    const valeurDe = input => {
        const n = parseFloat(String(input.value).replace(',', '.'));
        return Number.isFinite(n) ? n : 0;
    };
    const pluriel = (n, singulier, plurielForme) => n + ' ' + (n > 1 ? plurielForme : singulier);

    /* ---------------------------------------------------------------- Onglets */

    const tablist = page.querySelector('[data-gp-tabs]');
    if (tablist) {
        const tabs = Array.from(tablist.querySelectorAll('[role="tab"]'));
        const panelOf = tab => document.getElementById(tab.getAttribute('aria-controls'));

        const select = (tab, { focus = false, memorise = true } = {}) => {
            tabs.forEach(item => {
                const actif = item === tab;
                item.setAttribute('aria-selected', String(actif));
                item.tabIndex = actif ? 0 : -1;
                panelOf(item).hidden = !actif;
            });
            if (focus) tab.focus();
            if (memorise) {
                history.replaceState(null, '', '#' + tab.getAttribute('aria-controls'));
                window.QuitusMotion?.enter(panelOf(tab));
            }
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', event => {
                event.preventDefault();
                select(tab);
            });
            tab.addEventListener('keydown', event => {
                const cible = {
                    ArrowRight: tabs[(index + 1) % tabs.length],
                    ArrowLeft: tabs[(index - 1 + tabs.length) % tabs.length],
                    Home: tabs[0],
                    End: tabs[tabs.length - 1],
                }[event.key];
                if (!cible) return;
                event.preventDefault();
                select(cible, { focus: true });
            });
        });

        page.classList.add('has-tabs');
        const depuisAncre = tabs.find(tab => '#' + tab.getAttribute('aria-controls') === location.hash);
        select(depuisAncre || tabs.find(tab => tab.getAttribute('aria-selected') === 'true') || tabs[0], { memorise: false });

    }

    /* ------------------------------------------------------- Éditeurs en lot */

    let compteur = 1000; // clés des lignes ajoutées, distinctes de celles rendues par le serveur

    const compterOnglet = (id, n) => {
        const pastille = document.querySelector('#' + id + ' .gp-tab-count');
        if (pastille) pastille.textContent = n;
    };

    page.querySelectorAll('[data-gp-editor]').forEach(form => {
        const type = form.dataset.gpEditor;
        const changesText = form.querySelector('[data-gp-changes]');
        const submit = form.querySelector('[data-gp-submit]');
        const aCorriger = form.hasAttribute('data-gp-dirty');
        let envoi = false;
        let modifie = false;

        const template = nom => form.querySelector('[data-gp-template="' + nom + '"]');
        const inserer = (nomTemplate, prefixe, reperer) => {
            const nom = prefixe + '[' + (compteur++) + ']';
            const html = template(nomTemplate).innerHTML.split('__NOM__').join(nom);
            reperer.insertAdjacentHTML('beforebegin', html);
            const ajout = reperer.previousElementSibling;
            window.QuitusMotion?.enter(ajout);
            ajout.querySelector('input:not([type="checkbox"])')?.focus();
            return ajout;
        };

        // Les champs d'un élément supprimé ne sont ni validés ni envoyés.
        const synchroniserSuppressions = () => {
            form.querySelectorAll('[data-gp-delete]').forEach(box => {
                const portee = box.closest('.gp-rubrique-row') ? box.closest('tbody') : box.closest('tr');
                portee.classList.toggle('is-deleted', box.checked);
                const bouton = box.closest('.gp-delete');
                bouton.dataset.title ??= bouton.title;
                bouton.title = box.checked ? 'Rétablir' : bouton.dataset.title;
            });
            form.querySelectorAll('.form-input').forEach(input => {
                input.disabled = Boolean(input.closest('.is-deleted'));
            });
        };

        const bilan = () => {
            // Même décompte que le serveur : une nouvelle rubrique et chacun de ses critères.
            const ajoutes = form.querySelectorAll('tr[data-gp-new], tbody[data-gp-new]').length;
            const supprimes = Array.from(form.querySelectorAll('[data-gp-delete]:checked'))
                .filter(box => box.closest('.gp-rubrique-row') || !box.closest('tbody.is-deleted')).length;
            const modifies = Array.from(form.querySelectorAll('tr:not([data-gp-new]):not(.is-deleted)'))
                .filter(tr => !tr.closest('tbody.is-deleted'))
                .filter(tr => Array.from(tr.querySelectorAll('.form-input')).some(input => input.value !== input.defaultValue)).length;
            return { modifies, ajoutes, supprimes };
        };

        const afficherBilan = () => {
            const { modifies, ajoutes, supprimes } = bilan();
            const parties = [
                modifies && pluriel(modifies, 'modification', 'modifications'),
                ajoutes && pluriel(ajoutes, 'ajout', 'ajouts'),
                supprimes && pluriel(supprimes, 'suppression', 'suppressions'),
            ].filter(Boolean);
            modifie = parties.length > 0;
            changesText.textContent = parties.length ? parties.join(', ') + ' à enregistrer' : (aCorriger ? 'Saisie à corriger' : 'Aucune modification');
            changesText.classList.toggle('is-dirty', modifie || aCorriger);
            submit.disabled = !modifie && !aCorriger;
        };

        /* --- Critères : sous-totaux, total, écart à la référence */
        const calculerGrille = () => {
            const rubriques = Array.from(form.querySelectorAll('tbody[data-gp-rubrique]'));
            const sousTotaux = rubriques.map(tbody => tbody.classList.contains('is-deleted') ? 0
                : Array.from(tbody.querySelectorAll('tr[data-gp-critere]:not(.is-deleted) [data-gp-points]'))
                    .reduce((somme, input) => somme + valeurDe(input), 0));
            const total = sousTotaux.reduce((a, b) => a + b, 0);
            const plusLourde = Math.max(0, ...sousTotaux);

            rubriques.forEach((tbody, index) => {
                tbody.querySelector('[data-gp-subtotal]').textContent = nombre(sousTotaux[index]);
                const part = tbody.querySelector('[data-gp-share]');
                if (part) part.style.width = (plusLourde > 0 ? sousTotaux[index] / plusLourde * 100 : 0) + '%';
            });
            page.querySelectorAll('#criteres [data-gp-total]').forEach(el => { el.textContent = nombre(total); });
            compterOnglet('onglet-criteres', form.querySelectorAll('tr[data-gp-critere]').length
                - Array.from(form.querySelectorAll('tr[data-gp-critere]')).filter(tr => tr.matches('.is-deleted') || tr.closest('tbody.is-deleted')).length);

            const statut = page.querySelector('#criteres [data-gp-status]');
            const ecart = Math.round((total - reference) * 100) / 100;
            statut.classList.toggle('is-ok', ecart === 0);
            statut.classList.toggle('is-warning', ecart !== 0);
            statut.textContent = ecart === 0 ? 'Grille équilibrée'
                : (ecart > 0 ? '+' : '−') + nombre(Math.abs(ecart)) + (Math.abs(ecart) < 2 ? ' point' : ' points') + ' par rapport à ' + reference;
        };

        /* --- Paliers : plages, échelle, cohérence */
        const tonDe = (categorie, categories) => tons[Math.max(0, categories.indexOf(categorie)) % tons.length] || ['#62756b', '#eef2ef'];

        const calculerPaliers = () => {
            const lignes = Array.from(form.querySelectorAll('tr[data-gp-palier]'));
            const actifs = lignes.filter(tr => !tr.classList.contains('is-deleted')).map(tr => ({
                tr,
                code: tr.querySelector('[data-gp-code]').value.trim(),
                categorie: tr.querySelector('[data-gp-categorie]').value.trim(),
                saisi: tr.querySelector('[data-gp-seuil]').value.trim() !== '',
                seuil: valeurDe(tr.querySelector('[data-gp-seuil]')),
            }));
            const tries = actifs.filter(p => p.saisi).sort((a, b) => a.seuil - b.seuil);

            lignes.forEach(tr => { tr.querySelector('[data-gp-plage]').textContent = tr.classList.contains('is-deleted') ? 'Supprimé' : '—'; });
            tries.forEach((p, i) => {
                const suivant = tries[i + 1];
                p.tr.querySelector('[data-gp-plage]').textContent = suivant
                    ? 'de ' + nombre(p.seuil) + ' à moins de ' + nombre(suivant.seuil)
                    : nombre(p.seuil) + ' et plus';
            });

            // Échelle : même rendu que côté serveur.
            const categories = [...tries].reverse().map(p => p.categorie).filter((c, i, liste) => c && liste.indexOf(c) === i);
            const echelle = page.querySelector('[data-gp-scale]');
            const bandeau = page.querySelector('[data-gp-scale-cats]');
            echelle.replaceChildren();
            bandeau.replaceChildren();
            const segment = (parent, largeur, texte, ton, classe) => {
                const span = document.createElement('span');
                if (classe) span.className = classe;
                span.style.flexGrow = String(Math.max(0.5, largeur));
                if (ton) { span.style.setProperty('--ink', ton[0]); span.style.setProperty('--tint', ton[1]); }
                span.textContent = texte;
                parent.appendChild(span);
            };
            if (tries.length && tries[0].seuil > 0) {
                segment(echelle, tries[0].seuil, '', null, 'gp-scale-seg is-unranked');
                segment(bandeau, tries[0].seuil, '');
            }
            tries.forEach((p, i) => {
                const fin = tries[i + 1] ? tries[i + 1].seuil : Math.max(reference, p.seuil);
                segment(echelle, fin - p.seuil, p.code, tonDe(p.categorie, categories), 'gp-scale-seg');
            });
            tries.forEach((p, i) => {
                if (i > 0 && tries[i - 1].categorie === p.categorie) return;
                let j = i;
                while (tries[j + 1] && tries[j + 1].categorie === p.categorie) j++;
                const fin = tries[j + 1] ? tries[j + 1].seuil : Math.max(reference, tries[j].seuil);
                segment(bandeau, fin - p.seuil, p.categorie ? 'Catégorie ' + p.categorie : '', tonDe(p.categorie, categories));
            });

            // Cohérence : doublons (bloquants à l'enregistrement), trous et seuils hors d'atteinte.
            const verifications = [];
            const doublons = (liste, cle) => {
                const vus = new Map();
                liste.forEach(p => { const k = cle(p); if (k !== '') vus.set(k, (vus.get(k) || []).concat(p)); });
                return Array.from(vus.values()).filter(groupe => groupe.length > 1);
            };
            form.querySelectorAll('.is-conflict').forEach(input => input.classList.remove('is-conflict'));
            const codes = doublons(actifs, p => p.code.toUpperCase());
            codes.flat().forEach(p => p.tr.querySelector('[data-gp-code]').classList.add('is-conflict'));
            if (codes.length) verifications.push(['erreur', 'Code en double : ' + codes.map(g => g[0].code.toUpperCase()).join(', ') + '.']);
            const seuils = doublons(actifs.filter(p => p.saisi), p => String(p.seuil));
            seuils.flat().forEach(p => p.tr.querySelector('[data-gp-seuil]').classList.add('is-conflict'));
            if (seuils.length) verifications.push(['erreur', 'Plusieurs paliers commencent à ' + seuils.map(g => nombre(g[0].seuil)).join(', ') + ' points.']);
            if (tries.length && tries[0].seuil > 0) verifications.push(['alerte', 'Les scores inférieurs à ' + nombre(tries[0].seuil) + ' points ne seront pas classés.']);
            const horsEchelle = tries.filter(p => p.seuil > reference).map(p => p.code);
            if (horsEchelle.length) verifications.push(['alerte', 'Hors d’atteinte (au-delà de ' + reference + ' points) : ' + horsEchelle.join(', ') + '.']);
            if (!verifications.length && tries.length) {
                verifications.push(['ok', 'Échelle cohérente : ' + tries.length + ' paliers de 0 à ' + reference + ' points, sans doublon.']);
            }

            const liste = page.querySelector('[data-gp-checks]');
            liste.replaceChildren(...verifications.map(([niveau, texte]) => {
                const li = document.createElement('li');
                li.className = 'is-' + niveau;
                li.textContent = texte;
                return li;
            }));
            const compte = form.querySelector('[data-gp-count]');
            if (compte) compte.textContent = actifs.length;
            compterOnglet('onglet-paliers', actifs.length);
        };

        const calculer = () => {
            synchroniserSuppressions();
            if (type === 'grille') calculerGrille(); else calculerPaliers();
            afficherBilan();
        };

        form.addEventListener('input', calculer);
        form.addEventListener('change', calculer);

        form.addEventListener('click', event => {
            const ajoutCritere = event.target.closest('[data-gp-add-critere]');
            if (ajoutCritere) {
                inserer('critere', ajoutCritere.dataset.gpAddCritere, ajoutCritere.closest('tr'));
                calculer();
                return;
            }
            if (event.target.closest('[data-gp-add-rubrique]')) {
                inserer('rubrique', 'nouvelles_rubriques', form.querySelector('tfoot'));
                calculer();
                return;
            }
            if (event.target.closest('[data-gp-add-palier]')) {
                const corps = form.querySelector('[data-gp-paliers]');
                const repere = document.createElement('tr');
                corps.appendChild(repere);
                inserer('palier', 'nouveaux_paliers', repere);
                repere.remove();
                calculer();
                return;
            }
            const retrait = event.target.closest('[data-gp-remove]');
            if (retrait) {
                const ligne = retrait.closest('.gp-rubrique-row') ? retrait.closest('tbody') : retrait.closest('tr');
                const suivant = ligne.closest('tbody')?.querySelector('[data-gp-add-critere]');
                (ligne.tagName === 'TBODY' ? form.querySelector('[data-gp-add-rubrique]') : suivant || form.querySelector('[data-gp-add-palier]'))?.focus();
                const retirer = () => { ligne.remove(); calculer(); };
                if (window.QuitusMotion) window.QuitusMotion.leave(ligne, retirer); else retirer();
            }
        });

        // Une valeur invalide dans un champ hors de vue : on amène le premier au centre.
        let invalideMontre = false;
        form.addEventListener('invalid', event => {
            if (invalideMontre) return;
            invalideMontre = true;
            event.target.scrollIntoView({ block: 'center' });
            requestAnimationFrame(() => { invalideMontre = false; });
        }, true);

        form.addEventListener('submit', () => { envoi = true; });
        window.addEventListener('beforeunload', event => {
            if ((!modifie && !aCorriger) || envoi) return;
            event.preventDefault();
            event.returnValue = '';
        });

        calculer();
    });
})();
