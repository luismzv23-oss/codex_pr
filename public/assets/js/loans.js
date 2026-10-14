(() => {
    'use strict';
    const list = document.getElementById('loans-list');
    const form = document.getElementById('loans-filters');
    const groups = document.getElementById('loans-groups');
    const error = document.getElementById('loans-error');
    if (!list || !form || !error || !groups) return;

    const storageKey = 'loans-list-state';
    const filterNames = ['buscar', 'estado', 'estado_detalle', 'moneda'];
    let page = Number(list.dataset.page) || 1;
    let activeRequest = null;
    let lastHtml = null;
    let filters = new URLSearchParams(new FormData(form));

    function readUrl() {
        const params = new URLSearchParams(window.location.search);
        filters = new URLSearchParams();
        for (const name of filterNames) {
            const value = name === 'estado' ? (params.get(name) === 'cancelled' ? 'cancelled' : 'active') : (params.get(name) || '');
            form.elements.namedItem(name).value = value;
            filters.set(name, value);
        }
        page = Math.max(1, Number.parseInt(params.get('page'), 10) || 1);
        syncGroup();
    }

    function syncGroup() {
        const group = filters.get('estado') === 'cancelled' ? 'cancelled' : 'active';
        for (const option of form.elements.namedItem('estado_detalle').options) {
            if (!option.dataset.statusGroup) continue;
            option.hidden = option.disabled = option.dataset.statusGroup !== group;
        }
        for (const link of groups.querySelectorAll('[data-loan-group]')) {
            const selected = link.dataset.loanGroup === group;
            link.setAttribute('aria-current', selected ? 'page' : 'false');
            link.className = 'inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm transition ' + (selected
                ? 'border-slate-900 bg-slate-900 text-white dark:border-white dark:bg-white dark:text-slate-950'
                : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800');
            const url = currentUrl();
            url.searchParams.set('estado', link.dataset.loanGroup);
            url.searchParams.delete('estado_detalle');
            url.searchParams.set('page', '1');
            link.href = url.href;
        }
    }

    function currentUrl() {
        const url = new URL(form.action, window.location.origin);
        for (const name of filterNames) {
            const value = filters.get(name);
            if (value) url.searchParams.set(name, value);
        }
        url.searchParams.set('page', String(page));
        return url;
    }

    function saveState() {
        try { sessionStorage.setItem(storageKey, currentUrl().search); } catch (_) { /* Storage can be disabled. */ }
    }

    // Silent refresh never interrupts a page or filter request already in progress.
    async function refresh({ silent = false, push = false } = {}) {
        if (silent && (activeRequest || document.hidden)) return;
        if (activeRequest) activeRequest.abort();
        const request = new AbortController();
        activeRequest = request;
        list.setAttribute('aria-busy', 'true');
        const url = new URL(list.dataset.endpoint, window.location.origin);
        url.search = currentUrl().search;
        try {
            const response = await fetch(url, {
                credentials: 'same-origin', cache: 'no-store', signal: request.signal,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok || response.redirected || !(response.headers.get('content-type') || '').includes('application/json')) {
                throw new Error('No se pudo actualizar el listado. Comprobá tu conexión o sesión e intentá nuevamente.');
            }
            const data = await response.json();
            if (activeRequest !== request) return;
            if (typeof data.html !== 'string' || !Number.isInteger(data.pagination?.page)) {
                throw new Error('La respuesta del listado no es válida. Intentá nuevamente.');
            }
            page = data.pagination.page;
            list.dataset.page = String(page);
            if (data.html !== lastHtml) {
                list.innerHTML = data.html;
                lastHtml = data.html;
            }
            syncGroup();
            error.textContent = '';
            error.classList.add('hidden');
            const nextUrl = currentUrl();
            if (push && nextUrl.href !== window.location.href) {
                history.pushState(null, '', nextUrl);
            } else {
                history.replaceState(null, '', nextUrl);
            }
            saveState();
        } catch (exception) {
            if (exception.name !== 'AbortError' && activeRequest === request) {
                error.textContent = exception.message || 'No se pudo actualizar el listado.';
                error.classList.remove('hidden');
            }
        } finally {
            if (activeRequest === request) {
                activeRequest = null;
                list.setAttribute('aria-busy', 'false');
            }
        }
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        filters = new URLSearchParams(new FormData(form));
        page = 1;
        refresh({ push: true });
    });
    form.querySelector('[data-clear-filters]').addEventListener('click', event => {
        event.preventDefault();
        for (const name of filterNames) {
            if (name !== 'estado') form.elements.namedItem(name).value = '';
        }
        filters = new URLSearchParams(new FormData(form));
        page = 1;
        refresh({ push: true });
    });
    list.addEventListener('click', event => {
        const link = event.target.closest('a[data-page]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        page = Number(link.dataset.page);
        refresh({ push: true });
    });
    groups.addEventListener('click', event => {
        const link = event.target.closest('[data-loan-group]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        filters.set('estado', link.dataset.loanGroup);
        filters.set('estado_detalle', '');
        form.elements.namedItem('estado').value = link.dataset.loanGroup;
        form.elements.namedItem('estado_detalle').value = '';
        page = 1;
        syncGroup();
        refresh({ push: true });
    });
    window.addEventListener('popstate', () => { readUrl(); refresh(); });
    window.addEventListener('focus', () => refresh({ silent: true }));
    window.addEventListener('pageshow', () => refresh({ silent: true }));
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh({ silent: true }); });
    window.addEventListener('pagehide', saveState);

    // Returning from the existing loan pages restores the last list context.
    if (!window.location.search) {
        try {
            const saved = sessionStorage.getItem(storageKey);
            if (saved) history.replaceState(null, '', new URL(form.action, window.location.origin).pathname + saved);
        } catch (_) { /* Use the initial page when storage is unavailable. */ }
    }
    readUrl();
    refresh({ silent: true });
    window.setInterval(() => refresh({ silent: true }), 15000);
})();
