/*
 * Couche d'interactions commune aux pages connectées (voir interactions.css).
 *
 * - Confirmation : ton rouge pour une suppression / un rejet, vert sinon.
 * - Notifications : le message de confirmation du serveur devient une
 *   notification visible quel que soit le défilement.
 * - Barre de chargement pendant une navigation ou un enregistrement.
 * - Suppression confirmée : la ligne concernée s'efface pendant l'envoi.
 * - Totaux : impulsion visuelle à chaque changement ; chiffres clés comptés.
 * - window.QuitusMotion.enter(el) / leave(el, fin) pour les scripts de page.
 *
 * Tout mouvement est ignoré si l'utilisateur a demandé moins d'animations.
 */
(function () {
    'use strict';

    const calme = window.matchMedia('(prefers-reduced-motion: reduce)');
    const bouge = () => !calme.matches;

    /* --------------------------------------------------- Aides réutilisables */

    const QuitusMotion = {
        enter(el) {
            if (!el || !bouge()) return;
            el.classList.remove('qt-enter');
            void el.offsetWidth;
            el.classList.add('qt-enter');
            el.addEventListener('animationend', () => el.classList.remove('qt-enter'), { once: true });
        },

        // Replie l'élément (hauteur, marges, opacité) puis appelle fin().
        leave(el, fin) {
            if (!el || !bouge() || typeof el.animate !== 'function') { fin && fin(); return; }
            const style = getComputedStyle(el);
            // Une ligne de tableau ne se replie pas : elle s'estompe en glissant.
            if (/^table-(row|row-group)$/.test(style.display)) {
                el.style.pointerEvents = 'none';
                el.animate([{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'translateX(16px)' }],
                    { duration: 200, easing: 'cubic-bezier(.65, 0, .35, 1)' }).onfinish = () => { el.style.pointerEvents = ''; fin && fin(); };
                return;
            }
            const hauteur = el.offsetHeight;
            el.style.overflow = 'hidden';
            el.style.pointerEvents = 'none';
            const animation = el.animate([
                { height: hauteur + 'px', opacity: 1, transform: 'none', paddingTop: style.paddingTop, paddingBottom: style.paddingBottom, marginTop: style.marginTop, marginBottom: style.marginBottom },
                { height: '0px', opacity: 0, transform: 'translateX(16px)', paddingTop: '0px', paddingBottom: '0px', marginTop: '0px', marginBottom: '0px' },
            ], { duration: 260, easing: 'cubic-bezier(.65, 0, .35, 1)' });
            // Styles rétablis avant fin() : l'élément peut être réinséré (annulation).
            animation.onfinish = () => { el.style.overflow = ''; el.style.pointerEvents = ''; fin && fin(); };
        },

        bump(el) {
            if (!el || !bouge()) return;
            if (getComputedStyle(el).display === 'inline') el.style.display = 'inline-block';
            el.classList.remove('qt-bump');
            void el.offsetWidth;
            el.classList.add('qt-bump');
        },

        toast(message, options = {}) {
            return afficherNotification(message, options);
        },
    };
    window.QuitusMotion = QuitusMotion;

    /* ------------------------------------------------------- Notifications */

    const icones = {
        success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path class="qt-draw" d="M5 12.5l4.2 4.2L19 7"/></svg>',
        error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path class="qt-draw" d="M12 7v6M12 17h.01"/></svg>',
        info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path class="qt-draw" d="M12 11v6M12 7h.01"/></svg>',
    };

    function conteneur() {
        let zone = document.querySelector('.qt-toasts');
        if (!zone) {
            zone = document.createElement('div');
            zone.className = 'qt-toasts';
            zone.setAttribute('aria-live', 'polite');
            document.body.appendChild(zone);
        }
        return zone;
    }

    function afficherNotification(message, { type = 'success', titre = null, duree = 6000 } = {}) {
        const toast = document.createElement('div');
        toast.className = 'qt-toast is-' + type;
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toast.style.setProperty('--qt-duration', duree + 'ms');
        toast.innerHTML = '<span class="qt-toast-icon">' + (icones[type] || icones.success) + '</span>'
            + '<div class="qt-toast-body"></div>'
            + '<button type="button" class="qt-toast-close" aria-label="Fermer la notification">×</button>'
            + '<span class="qt-toast-timer" aria-hidden="true"></span>';
        const corps = toast.querySelector('.qt-toast-body');
        if (titre) {
            const strong = document.createElement('strong');
            strong.className = 'qt-toast-title';
            strong.textContent = titre;
            corps.appendChild(strong);
        }
        corps.appendChild(document.createTextNode(message));
        conteneur().appendChild(toast);

        // Minuterie suspendue au survol ou au focus, comme la barre de temps.
        let restant = duree;
        let depart = Date.now();
        let minuterie = setTimeout(fermer, restant);
        const pause = () => { clearTimeout(minuterie); restant -= Date.now() - depart; };
        const reprise = () => { depart = Date.now(); clearTimeout(minuterie); minuterie = setTimeout(fermer, Math.max(restant, 1200)); };
        toast.addEventListener('mouseenter', pause);
        toast.addEventListener('mouseleave', reprise);
        toast.addEventListener('focusin', pause);
        toast.addEventListener('focusout', reprise);

        function fermer() {
            clearTimeout(minuterie);
            if (!bouge()) { toast.remove(); return; }
            toast.classList.add('is-leaving');
            toast.addEventListener('animationend', () => toast.remove(), { once: true });
        }
        toast.querySelector('.qt-toast-close').addEventListener('click', fermer);
        return toast;
    }

    // Le message de confirmation du serveur devient une notification.
    const flash = document.getElementById('flashStatus');
    if (flash && flash.textContent.trim()) {
        afficherNotification(flash.textContent.trim(), { type: 'success' });
        flash.hidden = true;
        flash.removeAttribute('role');
    }

    /* ------------------------------------------------ Ton de la confirmation */

    const modale = document.getElementById('confirmModal');
    if (modale) {
        const boite = modale.querySelector('.modal-box');
        const icone = document.createElement('span');
        icone.className = 'qt-confirm-icon';
        icone.setAttribute('aria-hidden', 'true');
        boite.insertBefore(icone, boite.firstChild);
        const titre = document.getElementById('confirmModalTitle');
        const bouton = document.getElementById('confirmModalConfirm');
        const destructif = /^(supprimer|retirer|rejeter|refuser|désactiver|effacer|annuler la|clôturer)/i;

        // Capture : s'exécute avant le gestionnaire qui ouvre la fenêtre.
        document.addEventListener('submit', event => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm') || form.dataset.confirmed === 'true') return;
            const declencheur = event.submitter;
            const danger = form.dataset.confirmTone === 'danger'
                || destructif.test(form.dataset.confirm.trim())
                || Boolean(declencheur && declencheur.matches('.danger, .is-danger, .gp-delete, .pf-remove'))
                || (form.querySelector('input[name="_method"]')?.value || '').toUpperCase() === 'DELETE';
            modale.dataset.tone = danger ? 'danger' : 'primary';
            titre.textContent = danger ? 'Confirmer la suppression' : 'Confirmer l’action';
            if (/^(rejeter|refuser)/i.test(form.dataset.confirm.trim())) titre.textContent = 'Confirmer le rejet';
            // Libellé explicite fourni par le formulaire (ex. « Refermer »), sinon déduit de l'action.
            bouton.textContent = form.dataset.confirmLabel
                || (danger ? (/^(rejeter|refuser)/i.test(form.dataset.confirm.trim()) ? 'Rejeter' : 'Supprimer') : 'Confirmer');
            if (form.dataset.confirmLabel) titre.textContent = 'Confirmer : ' + form.dataset.confirmLabel.toLowerCase();
            icone.innerHTML = danger
                ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m5 5v6m4-6v6"/></svg>'
                : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.2 4.2L19 7"/></svg>';
            form.dataset.qtTone = modale.dataset.tone;
        }, true);
    }

    /* ------------------------------------------ Barre de chargement, sorties */

    const barre = document.createElement('div');
    barre.className = 'qt-progress';
    barre.setAttribute('aria-hidden', 'true');
    document.body.appendChild(barre);
    let delaiBarre = null;

    // La barre n'apparaît que si le serveur tarde un peu (au-delà de 150 ms).
    let securite = null;
    const demarrer = () => {
        clearTimeout(delaiBarre);
        clearTimeout(securite);
        delaiBarre = setTimeout(() => barre.classList.add('is-running'), 150);
        // Un téléchargement ne quitte pas la page : la barre ne doit pas rester affichée.
        securite = setTimeout(arreter, 10000);
    };
    const arreter = () => { clearTimeout(delaiBarre); clearTimeout(securite); barre.classList.remove('is-running'); };

    document.addEventListener('click', event => {
        const lien = event.target.closest('a[href]');
        if (!lien || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        if (lien.target && lien.target !== '_self') return;
        if (lien.hasAttribute('download')) return;
        const url = new URL(lien.href, location.href);
        if (url.origin !== location.origin || (url.pathname === location.pathname && url.search === location.search && url.hash)) return;
        // Les téléchargements (PDF, Excel) ne quittent pas la page.
        if (/\/(telecharger|export|pdf|quitus|pieces\/\d+)(\/|$)|\.(pdf|xlsx)$/i.test(url.pathname) && !/preparer/.test(url.pathname)) return;
        demarrer();
    });

    // Enregistrement : en phase de bouillonnement, après les éventuelles
    // annulations (validation, confirmation).
    document.addEventListener('submit', event => {
        if (event.defaultPrevented) return;
        const form = event.target;
        const declencheur = event.submitter;
        if ((declencheur && declencheur.formTarget === '_blank') || form.target === '_blank') return;
        if (form.method.toLowerCase() === 'get' && /\/(export|pdf)/i.test(form.action)) return;
        demarrer();

        // Suppression confirmée : on efface visuellement l'élément concerné.
        if (form.dataset.qtTone === 'danger' && form.dataset.confirmed === 'true') {
            const cible = form.closest('tr, li, .ref-row, .document-card, [data-line], .card');
            if (cible && !cible.matches('.card') ) cible.classList.add('qt-leaving');
        }
    });

    window.addEventListener('pageshow', arreter);
    window.addEventListener('pagehide', arreter);

    /* --------------------------------------------------------- Totaux vivants */

    // Impulsion quand un total est recalculé pendant la saisie.
    const totaux = '#pbTotalAmount, #pfBalance, .gp-total-value [data-gp-total], [data-gp-subtotal], .score-total [data-total], [data-rubrique-total], [data-axis-total]';
    const observateur = new MutationObserver(mutations => {
        const vus = new Set();
        mutations.forEach(mutation => {
            const el = (mutation.target.nodeType === 1 ? mutation.target : mutation.target.parentElement)?.closest(totaux);
            if (el && !vus.has(el)) { vus.add(el); QuitusMotion.bump(el); }
        });
    });
    document.querySelectorAll(totaux).forEach(el => observateur.observe(el, { childList: true, characterData: true, subtree: true }));

    // Chiffres clés comptés depuis zéro à leur apparition (« 140 000 FCFA »).
    if (bouge() && 'IntersectionObserver' in window) {
        // Pas les totaux recalculés pendant la saisie : ils ont leur propre impulsion.
        const cibles = document.querySelectorAll('.platform-metrics dd, .report-summary-stat-value, .quitus-summary strong');
        const compteur = new IntersectionObserver(entrees => {
            entrees.forEach(entree => {
                if (!entree.isIntersecting) return;
                compteur.unobserve(entree.target);
                const el = entree.target;
                // Le premier nœud texte porte le nombre ; un suffixe (<small>FCFA</small>) est conservé.
                const noeud = Array.from(el.childNodes).find(n => n.nodeType === 3 && /\d/.test(n.textContent));
                if (!noeud) return;
                const correspondance = noeud.textContent.match(/^(\s*)([\d\s  ]+)(.*)$/s);
                // Seulement un nombre, éventuellement suivi d'une unité (pas une date).
                if (!correspondance || !/^\s*(FCFA|%|pts?|points?)?\s*$/i.test(correspondance[3])) return;
                const cible = Number(correspondance[2].replace(/[^\d]/g, ''));
                // Une année (« 2027 ») ne se compte pas.
                if (!cible || cible < 3 || /^\s*(19|20)\d{2}\s*$/.test(noeud.textContent)) return;
                const format = new Intl.NumberFormat('fr-FR');
                const debut = performance.now();
                const duree = Math.min(1100, 500 + String(cible).length * 60);
                const etape = maintenant => {
                    const t = Math.min(1, (maintenant - debut) / duree);
                    const courbe = 1 - Math.pow(1 - t, 4);
                    noeud.textContent = correspondance[1] + format.format(Math.round(cible * courbe)) + correspondance[3];
                    if (t < 1) requestAnimationFrame(etape);
                };
                requestAnimationFrame(etape);
            });
        }, { threshold: .4 });
        cibles.forEach(el => compteur.observe(el));
    }
})();
