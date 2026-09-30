/*
 * Référentiels DSHN (canevas, grille de pondération) : lecture par défaut,
 * édition en ligne à la demande.
 *
 * - [data-ref-open][aria-controls=id] ouvre le formulaire #id ;
 * - le formulaire [data-ref-panel] masque l'élément désigné par
 *   data-ref-replaces (la ligne en lecture, ou le bouton « Ajouter ») ;
 * - [data-ref-cancel] ou Échap referme le formulaire, le réinitialise et
 *   rend le focus au bouton d'origine.
 */
(function () {
    'use strict';

    const firstField = panel => panel.querySelector('input:not([type="hidden"]), select, textarea');
    const triggersOf = panel => document.querySelectorAll('[data-ref-open][aria-controls="' + panel.id + '"]');
    const replacedOf = panel => panel.dataset.refReplaces ? document.getElementById(panel.dataset.refReplaces) : null;

    function open(panel) {
        const replaced = replacedOf(panel);
        if (replaced) replaced.hidden = true;
        panel.hidden = false;
        window.QuitusMotion?.enter(panel);
        triggersOf(panel).forEach(trigger => trigger.setAttribute('aria-expanded', 'true'));
        panel.scrollIntoView({ block: 'nearest' });
        const field = firstField(panel);
        if (field) {
            field.focus();
            if (field.type === 'text' && field.value) field.select();
        }
    }

    function close(panel) {
        panel.reset();
        // Les messages d'erreur serveur ne valent plus pour une saisie annulée.
        panel.querySelectorAll('.server-field-error').forEach(error => error.remove());
        panel.querySelectorAll('[aria-invalid]').forEach(field => {
            field.removeAttribute('aria-invalid');
            field.classList.remove('is-invalid');
        });
        panel.hidden = true;
        triggersOf(panel).forEach(trigger => trigger.setAttribute('aria-expanded', 'false'));

        const replaced = replacedOf(panel);
        if (replaced) replaced.hidden = false;
        const back = replaced && (replaced.matches('[data-ref-open]') ? replaced : replaced.querySelector('[data-ref-open]'));
        (back || triggersOf(panel)[0])?.focus();
    }

    document.addEventListener('click', event => {
        const opener = event.target.closest('[data-ref-open]');
        if (opener) {
            const panel = document.getElementById(opener.getAttribute('aria-controls'));
            if (!panel) return;
            event.preventDefault();
            if (panel.hidden) {
                open(panel);
            } else {
                panel.scrollIntoView({ block: 'nearest' });
                firstField(panel)?.focus();
            }
            return;
        }

        const cancel = event.target.closest('[data-ref-cancel]');
        if (cancel) {
            const panel = cancel.closest('[data-ref-panel]');
            if (panel) close(panel);
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || !(event.target instanceof Element)) return;
        const panel = event.target.closest('[data-ref-panel]');
        if (panel && !panel.hidden) {
            event.preventDefault();
            close(panel);
        }
    });
})();
