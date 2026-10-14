(() => {
    'use strict';
    const list = document.getElementById('payments-list');
    const form = document.getElementById('payments-filters');
    const error = document.getElementById('payments-error');
    if (!list || !form || !error ) return;

    const storageKey = 'payments-list-state';
    const openGroups = new Set();
    const filterNames = ['buscar', 'metodo', 'moneda'];
    let page = Number(list.dataset.page) || 1;
    let activeRequest = null;
    let lastHtml = null;
    let filters = new URLSearchParams(new FormData(form));

    function readUrl() {
        const params = new URLSearchParams(window.location.search);
        filters = new URLSearchParams();
        for (const name of filterNames) {
            const value = params.get(name) || '';
            form.elements.namedItem(name).value = value;
            filters.set(name, value);
        }
        page = Math.max(1, Number.parseInt(params.get('page'), 10) || 1);
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
        try {
            sessionStorage.setItem(storageKey, currentUrl().search);
            sessionStorage.setItem('payments-open-groups', JSON.stringify([...openGroups]));
        } catch (_) { /* Storage can be disabled. */ }
    }

    function captureGroups() {
        for (const details of list.querySelectorAll('details[data-group-key]')) {
            if (details.open) openGroups.add(details.dataset.groupKey);
            else openGroups.delete(details.dataset.groupKey);
        }
    }

    function restoreGroups() {
        for (const details of list.querySelectorAll('details[data-group-key]')) {
            details.open = openGroups.has(details.dataset.groupKey);
        }
    }

    // toggle does not bubble, so listen in capture phase at the list container.
    list.addEventListener('toggle', event => {
        const details = event.target;
        if (!details.matches('details[data-group-key]') || !list.contains(details)) return;
        if (details.open) openGroups.add(details.dataset.groupKey);
        else openGroups.delete(details.dataset.groupKey);
        saveState();
    }, true);

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
                captureGroups();
                list.innerHTML = data.html;
                restoreGroups();
                lastHtml = data.html;
            }
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
        for (const name of filterNames) form.elements.namedItem(name).value = '';
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
    window.addEventListener('popstate', () => { readUrl(); refresh(); });
    window.addEventListener('focus', () => refresh({ silent: true }));
    window.addEventListener('pageshow', () => refresh({ silent: true }));
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh({ silent: true }); });
    window.addEventListener('pagehide', () => { captureGroups(); saveState(); });

    // Returning from the existing payment forms restores the last list context.
    if (!window.location.search) {
        try {
            const saved = sessionStorage.getItem(storageKey);
            if (saved) history.replaceState(null, '', new URL(form.action, window.location.origin).pathname + saved);
        } catch (_) { /* Use the initial page when storage is unavailable. */ }
    }
    try {
        const savedGroups = JSON.parse(sessionStorage.getItem('payments-open-groups') || '[]');
        if (Array.isArray(savedGroups)) {
            for (const key of savedGroups) if (typeof key === 'string') openGroups.add(key);
        }
    } catch (_) { /* Ignore unavailable storage or invalid saved state. */ }
    restoreGroups();
    readUrl();
    refresh({ silent: true });
    window.setInterval(() => refresh({ silent: true }), 15000);
})();
