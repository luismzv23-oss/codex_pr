(() => {
    'use strict';
    for (const container of document.querySelectorAll('[data-collection-date]')) {
        const checkbox = container.querySelector('[data-collection-toggle]');
        const field = container.querySelector('[data-collection-field]');
        const input = field.querySelector('input[type="date"]');
        const sync = () => {
            field.hidden = !checkbox.checked;
            input.disabled = !checkbox.checked;
            input.required = checkbox.checked;
        };
        checkbox.addEventListener('change', sync);
        checkbox.form?.addEventListener('reset', () => setTimeout(sync, 0));
        sync();
    }
})();
