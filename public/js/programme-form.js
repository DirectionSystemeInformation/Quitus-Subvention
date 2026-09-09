(function () {
    'use strict';
    const form = document.getElementById('activityForm');
    if (!form) return;
    const review = document.getElementById('programmeReview');
    const checkbox = form.elements.namedItem('confirm_submission');
    const currency = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 });
    let dirty = form.dataset.unsaved === 'true';
    let submitting = false;
    let lastRemoved = null;
    function amountText(value) { return currency.format(value) + ' FCFA'; }
    function rows() { return Array.from(form.querySelectorAll('.programme-lines .programme-line')); }
    function hasContent(row) { return Array.from(row.querySelectorAll('input')).some(function (input) { return input.value.trim() !== ''; }); }
    function update() {
        let total = 0, count = 0;
        form.querySelectorAll('.programme-axis').forEach(function (axis) {
            let subtotal = 0;
            axis.querySelectorAll('.programme-lines .programme-line').forEach(function (row) {
                if (hasContent(row)) count++;
                subtotal += Number(row.querySelector('[data-field="montant"]').value) || 0;
            });
            axis.querySelector('.axis-total').textContent = amountText(subtotal);
            total += subtotal;
        });
        document.getElementById('pbTotalAmount').textContent = amountText(total);
        document.getElementById('pbFilledCount').textContent = count;
        return { total: total, count: count };
    }
    function changed() {
        dirty = true;
        document.getElementById('saveState').textContent = 'Modifications non enregistrées';
        checkbox.checked = false;
        checkbox.setCustomValidity('');
        review.hidden = true;
        update();
    }
    form.addEventListener('input', function (event) { if (event.target !== checkbox) changed(); });
    form.addEventListener('change', function (event) { if (event.target !== checkbox) changed(); });
    form.querySelectorAll('.programme-subaxis').forEach(function (section) {
        section.querySelector('.programme-add').addEventListener('click', function () {
            if (section.querySelectorAll('.programme-line').length >= 100) return;
            const index = Number(section.dataset.nextIndex);
            section.dataset.nextIndex = index + 1;
            const holder = document.createElement('template');
            holder.innerHTML = section.querySelector('template').innerHTML.replace(/__INDEX__/g, String(index));
            const row = holder.content.firstElementChild;
            section.querySelector('.programme-lines').appendChild(row);
            changed(); row.querySelector('input').focus();
        });
    });
    form.addEventListener('click', function (event) {
        const remove = event.target.closest('.programme-remove');
        if (!remove) return;
        const row = remove.closest('.programme-line');
        const parent = row.parentElement;
        const next = row.nextSibling;
        if (lastRemoved) lastRemoved.remove();
        const undo = document.createElement('button');
        undo.type = 'button'; undo.className = 'btn'; undo.textContent = 'Annuler le retrait de l’activité';
        parent.insertBefore(undo, row);
        row.remove(); lastRemoved = undo;
        undo.addEventListener('click', function () {
            parent.insertBefore(row, next && next.parentNode === parent ? next : undo);
            undo.remove(); lastRemoved = null; changed(); row.querySelector('input').focus();
        });
        changed(); undo.focus();
    });
    function showReview() {
        rows().forEach(function (row) {
            row.querySelectorAll('input').forEach(function (input) {
                input.required = hasContent(row) && ['designation', 'montant'].includes(input.dataset.field);
                if (!input.validity.valid) row.closest('details').open = true;
            });
        });
        if (!form.reportValidity()) return false;
        const summary = update();
        review.hidden = false;
        document.getElementById('reviewSummary').textContent = summary.count + ' activité(s) · Budget total : ' + amountText(summary.total);
        const list = document.getElementById('reviewAxes'); list.textContent = '';
        form.querySelectorAll('.programme-axis').forEach(function (axis) {
            const li = document.createElement('li');
            li.textContent = axis.querySelector('summary > span').textContent + ' : ' + axis.querySelector('.axis-total').textContent;
            list.appendChild(li);
        });
        if (!summary.count) document.getElementById('reviewSummary').textContent = 'Ajoutez au moins une activité avec sa désignation et son montant avant de soumettre.';
        review.querySelector('[value="submit"]').disabled = summary.count === 0;
        review.focus(); review.scrollIntoView({ block: 'center' });
        return summary.count > 0;
    }
    document.getElementById('reviewProgramme').addEventListener('click', showReview);
    document.getElementById('backToProgramme').addEventListener('click', function () { review.hidden = true; checkbox.checked = false; checkbox.setCustomValidity(''); document.getElementById('reviewProgramme').focus(); });
    form.addEventListener('submit', function (event) {
        if (!event.submitter) { event.preventDefault(); showReview(); return; }
        if (event.submitter.value === 'submit' && (review.hidden || !checkbox.checked)) {
            event.preventDefault(); showReview(); checkbox.focus(); checkbox.setCustomValidity('Confirmez que vous avez vérifié le programme.'); checkbox.reportValidity(); return;
        }
        submitting = true;
    });
    checkbox.addEventListener('change', function () { checkbox.setCustomValidity(''); });
    window.addEventListener('beforeunload', function (event) {
        if (!dirty || submitting) return;
        event.preventDefault(); event.returnValue = '';
        // If the user stays, keep the year selector usable after cancelling navigation.
        setTimeout(function () {
            document.querySelectorAll('.programme-year[data-submitting]').forEach(function (yearForm) {
                delete yearForm.dataset.submitting; yearForm.removeAttribute('aria-busy');
                yearForm.querySelectorAll('.is-loading').forEach(function (button) {
                    button.classList.remove('is-loading'); button.removeAttribute('aria-disabled');
                    button.querySelectorAll('.btn-spinner').forEach(function (spinner) { spinner.remove(); });
                });
            });
        }, 0);
    });
    window.addEventListener('pageshow', function () { submitting = false; });
    if (dirty) document.getElementById('saveState').textContent = 'Modifications non enregistrées — corrigez les erreurs puis enregistrez.';
    update();
})();
