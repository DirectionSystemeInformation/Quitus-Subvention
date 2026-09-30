/*
 * Programme d'activités (budgétisé ou réaménagé), côté fédération.
 * - Totaux en direct : sous-axe, axe, programme ; pour le réaménagé, reste à
 *   répartir par rapport à la subvention accordée.
 * - Montants affichés avec séparateurs de milliers, envoyés en chiffres.
 * - Ajout / retrait d'activités (retrait annulable), numérotation par sous-axe.
 * - Vérification avant soumission, puis confirmation explicite.
 */
(function () {
    'use strict';
    const form = document.getElementById('activityForm');
    if (!form) return;

    const review = document.getElementById('programmeReview');
    const checkbox = form.elements.namedItem('confirm_submission');
    const saveState = document.getElementById('saveState');
    const subvention = form.dataset.subvention !== undefined ? Number(form.dataset.subvention) : null;
    const nombre = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 });
    const fcfa = value => nombre.format(value) + ' FCFA';
    let dirty = form.dataset.unsaved === 'true';
    let submitting = false;
    let lastRemoved = null;

    /* ------------------------------------------------------------ Montants */

    const chiffres = value => String(value).split(/[.,]/)[0].replace(/[^\d]/g, '');
    const montantDe = input => Number(chiffres(input.value)) || 0;
    const formater = input => {
        const brut = chiffres(input.value);
        input.value = brut ? nombre.format(Number(brut)) : '';
    };

    form.addEventListener('input', event => {
        const input = event.target;
        if (!input.matches('[data-money]')) return;
        // Conserve la position du curseur malgré l'insertion des espaces.
        const avant = input.value.slice(0, input.selectionStart).replace(/[^\d]/g, '').length;
        formater(input);
        let position = 0;
        for (let vus = 0; position < input.value.length && vus < avant; position++) {
            if (/\d/.test(input.value[position])) vus++;
        }
        input.setSelectionRange(position, position);
    });

    /* ------------------------------------------------------------- Calculs */

    const lignes = scope => Array.from(scope.querySelectorAll('[data-lines] [data-line]'));
    const renseignee = ligne => Array.from(ligne.querySelectorAll('input')).some(input => input.value.trim() !== '');

    function update() {
        let total = 0;
        let count = 0;
        form.querySelectorAll('[data-axis]').forEach(axe => {
            let totalAxe = 0;
            axe.querySelectorAll('[data-subaxis]').forEach(sousAxe => {
                const liste = lignes(sousAxe);
                let totalSousAxe = 0;
                liste.forEach((ligne, index) => {
                    ligne.querySelector('[data-line-num]').textContent = index + 1;
                    if (renseignee(ligne)) count++;
                    totalSousAxe += montantDe(ligne.querySelector('[data-field="montant"]'));
                });
                sousAxe.classList.toggle('is-empty', liste.length === 0);
                sousAxe.querySelector('[data-subaxis-total]').textContent = liste.length
                    ? liste.length + (liste.length > 1 ? ' activités · ' : ' activité · ') + fcfa(totalSousAxe)
                    : 'Aucune activité';
                totalAxe += totalSousAxe;
            });
            axe.querySelector('[data-axis-total]').textContent = fcfa(totalAxe);
            const nav = document.querySelector('[data-nav-total="' + axe.id.replace('axe-', '') + '"]');
            if (nav) nav.textContent = nombre.format(totalAxe);
            total += totalAxe;
        });

        document.getElementById('pbTotalAmount').textContent = fcfa(total);
        document.getElementById('pbFilledCount').textContent = count ? count + (count > 1 ? ' activités renseignées' : ' activité renseignée') : 'Aucune activité';

        if (subvention !== null) {
            const ecart = subvention - total;
            const jauge = document.getElementById('pfGaugeFill');
            jauge.style.width = Math.min(100, subvention > 0 ? total / subvention * 100 : 0) + '%';
            jauge.parentElement.classList.toggle('is-over', ecart < 0);
            jauge.parentElement.classList.toggle('is-exact', ecart === 0);
            const solde = document.getElementById('pfBalance');
            solde.className = 'pf-balance ' + (ecart === 0 ? 'is-exact' : ecart < 0 ? 'is-over' : 'is-left');
            solde.textContent = ecart === 0 ? 'Programme ajusté à la subvention'
                : ecart > 0 ? 'Reste à répartir : ' + fcfa(ecart)
                    : 'Dépassement de la subvention : ' + fcfa(-ecart);
        }
        return { total, count };
    }

    function changed() {
        dirty = true;
        saveState.textContent = 'Modifications non enregistrées';
        saveState.classList.add('is-dirty');
        checkbox.checked = false;
        checkbox.setCustomValidity('');
        review.hidden = true;
        update();
    }

    form.addEventListener('input', event => { if (event.target !== checkbox) changed(); });
    form.addEventListener('change', event => { if (event.target !== checkbox) changed(); });

    /* ---------------------------------------------------- Ajout et retrait */

    form.addEventListener('click', event => {
        const ajout = event.target.closest('[data-add]');
        if (ajout) {
            const sousAxe = ajout.closest('[data-subaxis]');
            if (lignes(sousAxe).length >= 100) return;
            const index = Number(sousAxe.dataset.nextIndex);
            sousAxe.dataset.nextIndex = index + 1;
            const holder = document.createElement('template');
            holder.innerHTML = sousAxe.querySelector('template').innerHTML.replace(/__INDEX__/g, String(index));
            const ligne = holder.content.firstElementChild;
            sousAxe.querySelector('[data-lines]').appendChild(ligne);
            window.QuitusMotion?.enter(ligne);
            changed();
            ligne.querySelector('input').focus();
            return;
        }

        const retrait = event.target.closest('[data-remove]');
        if (!retrait) return;
        const ligne = retrait.closest('[data-line]');
        const parent = ligne.parentElement;
        const suivant = ligne.nextSibling;
        if (lastRemoved) lastRemoved.remove();

        const designation = ligne.querySelector('[data-field="designation"]').value.trim();
        const annuler = document.createElement('div');
        annuler.className = 'pf-undo';
        annuler.innerHTML = '<span></span><button type="button" class="btn">Annuler le retrait</button>';
        annuler.querySelector('span').textContent = designation ? '« ' + designation + ' » retirée.' : 'Activité retirée.';
        parent.insertBefore(annuler, ligne);
        lastRemoved = annuler;
        // La ligne se replie avant d'être retirée ; le bandeau d'annulation apparaît.
        const retirer = () => { ligne.remove(); changed(); };
        if (window.QuitusMotion) window.QuitusMotion.leave(ligne, retirer); else retirer();
        window.QuitusMotion?.enter(annuler);
        annuler.querySelector('button').addEventListener('click', () => {
            parent.insertBefore(ligne, suivant && suivant.parentNode === parent ? suivant : annuler);
            annuler.remove();
            lastRemoved = null;
            window.QuitusMotion?.enter(ligne);
            changed();
            ligne.querySelector('input').focus();
        });
        annuler.querySelector('button').focus();
    });

    /* ---------------------------------------------------------- Soumission */

    function showReview() {
        lignes(form).forEach(ligne => {
            ligne.querySelectorAll('input').forEach(input => {
                input.required = renseignee(ligne) && ['designation', 'montant'].includes(input.dataset.field);
            });
        });
        if (!form.reportValidity()) return false;

        const bilan = update();
        review.hidden = false;
        document.getElementById('reviewSummary').textContent = bilan.count
            ? bilan.count + (bilan.count > 1 ? ' activités' : ' activité') + ' · total ' + fcfa(bilan.total)
            : 'Ajoutez au moins une activité avec sa désignation et son montant avant de soumettre.';

        const ecartTexte = document.getElementById('reviewSubvention');
        if (subvention !== null && bilan.count) {
            const ecart = subvention - bilan.total;
            ecartTexte.hidden = ecart === 0;
            ecartTexte.textContent = ecart > 0
                ? 'Le programme n’utilise pas toute la subvention accordée : il reste ' + fcfa(ecart) + ' à répartir.'
                : 'Le programme dépasse la subvention accordée de ' + fcfa(-ecart) + '.';
        }

        const liste = document.getElementById('reviewAxes');
        liste.textContent = '';
        form.querySelectorAll('[data-axis]').forEach(axe => {
            const li = document.createElement('li');
            li.textContent = axe.querySelector('h2').textContent + ' : ' + axe.querySelector('[data-axis-total]').textContent;
            liste.appendChild(li);
        });
        review.querySelector('[value="submit"]').disabled = bilan.count === 0;
        review.focus();
        review.scrollIntoView({ block: 'center' });
        return bilan.count > 0;
    }

    document.getElementById('reviewProgramme').addEventListener('click', showReview);
    document.getElementById('backToProgramme').addEventListener('click', () => {
        review.hidden = true;
        checkbox.checked = false;
        checkbox.setCustomValidity('');
        document.getElementById('reviewProgramme').focus();
    });

    form.addEventListener('submit', event => {
        if (!event.submitter) { event.preventDefault(); showReview(); return; }
        if (event.submitter.value === 'submit' && (review.hidden || !checkbox.checked)) {
            event.preventDefault();
            showReview();
            checkbox.focus();
            checkbox.setCustomValidity('Confirmez que vous avez vérifié le programme.');
            checkbox.reportValidity();
            return;
        }
        // Le serveur attend des chiffres : on retire les séparateurs d'affichage.
        form.querySelectorAll('[data-money]').forEach(input => { input.value = chiffres(input.value); });
        submitting = true;
    });
    checkbox.addEventListener('change', () => checkbox.setCustomValidity(''));

    window.addEventListener('beforeunload', event => {
        if (!dirty || submitting) return;
        event.preventDefault();
        event.returnValue = '';
        // Si l'on reste sur la page, le sélecteur d'année doit redevenir utilisable.
        setTimeout(() => {
            document.querySelectorAll('.pf-year[data-submitting]').forEach(annee => {
                delete annee.dataset.submitting;
                annee.removeAttribute('aria-busy');
                annee.querySelectorAll('.is-loading').forEach(bouton => {
                    bouton.classList.remove('is-loading');
                    bouton.removeAttribute('aria-disabled');
                    bouton.querySelectorAll('.btn-spinner').forEach(spinner => spinner.remove());
                });
            });
        }, 0);
    });
    window.addEventListener('pageshow', () => {
        submitting = false;
        form.querySelectorAll('[data-money]').forEach(formater);
    });

    form.querySelectorAll('[data-money]').forEach(formater);
    if (dirty) {
        saveState.textContent = form.querySelector('.server-field-error, [aria-invalid="true"]') || document.getElementById('flashErrors')
            ? 'Modifications non enregistrées — corrigez les erreurs puis enregistrez.'
            : 'Modifications non enregistrées';
        saveState.classList.add('is-dirty');
    }
    update();
})();
