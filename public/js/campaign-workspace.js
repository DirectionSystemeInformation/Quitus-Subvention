(function () {
    'use strict';
    const editor = document.querySelector('[data-campaign-edit]');
    if (!editor) return;
    const controls = Array.from(editor.querySelectorAll('input:not([type="hidden"]), select, textarea'));
    const snapshot = () => JSON.stringify(controls.map(field => [field.name, field.value]));
    const saved = snapshot();
    let submitting = false;
    let dirty = false;
    const advanceButtons = document.querySelectorAll('[data-campaign-advance] button[type="submit"]');
    const notices = document.querySelectorAll('[data-campaign-save-state]');

    function update() {
        dirty = snapshot() !== saved;
        advanceButtons.forEach(button => { button.disabled = dirty; });
        notices.forEach(notice => {
            notice.textContent = dirty ? 'Modifications non enregistrées. Enregistrez pour pouvoir poursuivre.' : 'Enregistrez votre saisie avant de passer à l’étape suivante.';
        });
        const format = value => value.toLocaleString('fr-FR', { maximumFractionDigits: 2 });
        const sum = inputs => inputs.reduce((total, input) => total + (Number(input.value) || 0), 0);
        editor.querySelectorAll('[data-allocation-row]').forEach(row => {
            const inputs = Array.from(row.querySelectorAll('.critere-input'));
            row.querySelector('[data-total]').textContent = format(sum(inputs));
            row.querySelectorAll('[data-rubrique]').forEach(rubrique => {
                const target = rubrique.querySelector('[data-rubrique-total]');
                if (target) target.textContent = format(sum(Array.from(rubrique.querySelectorAll('.critere-input'))));
            });
            const filled = row.querySelector('[data-filled]');
            if (filled) {
                const count = inputs.filter(input => input.value.trim() !== '').length;
                filled.textContent = count + ' / ' + inputs.length + ' critères renseignés';
                filled.classList.toggle('is-complete', count === inputs.length);
            }
        });
    }
    editor.addEventListener('input', update);
    editor.addEventListener('change', update);
    editor.addEventListener('submit', () => { submitting = true; });
    // Un champ hors barème dans une fiche repliée bloquerait l'envoi sans
    // message visible : on déplie sa fiche pour que le navigateur l'affiche.
    let invalidShown = false;
    editor.addEventListener('invalid', event => {
        const fiche = event.target.closest('details');
        if (fiche) fiche.open = true;
        if (invalidShown) return;
        invalidShown = true;
        requestAnimationFrame(() => {
            event.target.scrollIntoView({ block: 'center' });
            event.target.reportValidity(); // relance « invalid », ignoré tant que le verrou est posé
            invalidShown = false;
        });
    }, true);
    document.querySelectorAll('[data-campaign-advance]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!dirty) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            editor.scrollIntoView({ block: 'start' });
            controls[0]?.focus();
        }, true);
    });
    window.addEventListener('beforeunload', event => {
        if (!dirty || submitting) return;
        event.preventDefault();
        event.returnValue = '';
    });
    update();
})();
