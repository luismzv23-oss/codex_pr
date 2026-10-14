(() => {
    'use strict';
    const normalize = value => String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim().replace(/\s+/g, ' ');
    const documentText = value => normalize(value).replace(/[.\s-]/g, '');

    for (const select of document.querySelectorAll('select[data-customer-search]')) {
        // A request originating from a customer record must stay bound to that customer.
        if (select.disabled || select.dataset.customerSearchBound) continue;
        select.dataset.customerSearchBound = '1';
        const options = Array.from(select.options).filter(option => option.value && !option.disabled).map(option => ({
            value: option.value, label: option.textContent.trim(),
            name: normalize(option.dataset.customerName || option.textContent),
            document: documentText(option.dataset.customerDocument || ''),
        }));
        let chosen = options.find(option => option.value === select.value) || null;
        let matches = options;
        let activeIndex = -1;
        let open = false;
        const required = select.required;
        const container = document.createElement('div');
        container.className = 'relative';
        const input = document.createElement('input');
        input.type = 'text';
        input.id = select.id + '-search';
        input.autocomplete = 'off';
        input.placeholder = 'Buscar por nombre o documento…';
        input.className = 'w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pr-12 dark:border-slate-700 dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500';
        input.required = required;
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-haspopup', 'listbox');
        input.setAttribute('aria-expanded', 'false');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'absolute right-2 top-2 rounded-xl px-3 py-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800';
        button.textContent = '⌄';
        button.setAttribute('aria-label', 'Abrir selección de cliente');
        const panel = document.createElement('div');
        panel.className = 'absolute z-30 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900';
        panel.hidden = true;
        const list = document.createElement('div');
        list.id = select.id + '-options';
        list.className = 'max-h-64 overflow-y-auto py-1';
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', 'Clientes');
        input.setAttribute('aria-controls', list.id);
        const status = document.createElement('p');
        status.className = 'border-t border-slate-100 px-4 py-2 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400';
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');
        panel.append(list, status);
        container.append(input, button, panel);
        const labels = Array.from(select.labels || []);
        select.insertAdjacentElement('afterend', container);
        for (const label of labels) label.htmlFor = input.id;
        // The native select remains the named form value and the fallback without JS.
        select.hidden = true;
        select.required = false;
        input.value = chosen?.label || '';

        function validity() {
            input.setCustomValidity(input.value && !chosen ? 'Seleccioná un cliente de la lista.' : '');
        }

        function close() {
            open = false;
            panel.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
        }

        function activate(index) {
            activeIndex = index;
            Array.from(list.children).forEach((item, i) => {
                item.classList.toggle('bg-sky-100', i === index);
                item.classList.toggle('dark:bg-sky-900', i === index);
            });
            const item = list.children[index];
            if (item) {
                input.setAttribute('aria-activedescendant', item.id);
                item.scrollIntoView({ block: 'nearest' });
            } else input.removeAttribute('aria-activedescendant');
        }

        function render(query = '') {
            const tokens = normalize(query).split(' ').filter(Boolean);
            matches = options.filter(option => tokens.every(token => option.name.includes(token)
                || (documentText(token) !== '' && option.document.includes(documentText(token)))));
            list.replaceChildren();
            matches.forEach((option, index) => {
                const item = document.createElement('div');
                item.id = list.id + '-' + index;
                item.dataset.customerOption = String(index);
                item.className = 'cursor-pointer px-4 py-3 text-sm text-slate-900 hover:bg-sky-50 dark:text-slate-100 dark:hover:bg-slate-800';
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', String(option.value === select.value));
                item.textContent = option.label;
                list.append(item);
            });
            status.textContent = matches.length ? matches.length + ' clientes encontrados' : 'No se encontraron clientes.';
            panel.hidden = false;
            open = true;
            input.setAttribute('aria-expanded', 'true');
            activate(chosen ? matches.findIndex(option => option.value === chosen.value) : -1);
        }

        function choose(index) {
            if (!matches[index]) return;
            chosen = matches[index];
            select.value = chosen.value;
            input.value = chosen.label;
            validity();
            close();
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        input.addEventListener('focus', () => render(chosen ? '' : input.value));
        input.addEventListener('input', () => {
            chosen = null;
            select.value = '';
            validity();
            select.dispatchEvent(new Event('change', { bubbles: true }));
            render(input.value);
        });
        input.addEventListener('keydown', event => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (!open) render(chosen ? '' : input.value);
                if (matches.length) activate(event.key === 'ArrowDown'
                    ? Math.min(activeIndex + 1, matches.length - 1)
                    : (activeIndex < 0 ? matches.length - 1 : Math.max(0, activeIndex - 1)));
            } else if (event.key === 'Enter' && open) {
                event.preventDefault();
                choose(activeIndex < 0 ? 0 : activeIndex);
            } else if (event.key === 'Escape') {
                event.preventDefault();
                close();
            } else if (event.key === 'Tab') close();
        });
        input.addEventListener('blur', () => { close(); });
        button.addEventListener('mousedown', event => event.preventDefault());
        button.addEventListener('click', () => {
            if (open) close();
            else { input.focus(); render(chosen ? '' : input.value); }
        });
        list.addEventListener('mousedown', event => event.preventDefault());
        list.addEventListener('click', event => {
            const item = event.target.closest('[data-customer-option]');
            if (item && list.contains(item)) choose(Number(item.dataset.customerOption));
        });
        select.addEventListener('change', () => {
            const next = options.find(option => option.value === select.value) || null;
            if (next === chosen) return;
            chosen = next;
            input.value = chosen?.label || '';
            validity();
            close();
        });
        select.form?.addEventListener('reset', () => {
            // Native controls reset after the reset event finishes.
            setTimeout(() => {
                chosen = options.find(option => option.value === select.value) || null;
                input.value = chosen?.label || '';
                validity();
                close();
            }, 0);
        });
        validity();
    }
})();
