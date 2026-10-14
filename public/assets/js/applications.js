(() => {
    'use strict';
    const list = document.getElementById('applications-list');
    const form = document.getElementById('applications-filters');
    const summary = document.getElementById('applications-summary');
    const error = document.getElementById('applications-error');
    if (!list || !form || !error || !summary) return;

    const storageKey = 'applications-list-state';
    const filterNames = ['buscar', 'estado', 'moneda', 'sistema'];
    let page = Number(list.dataset.page) || 1;
    let activeRequest = null;
    let lastHtml = null;
    let lastSummary = null;
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
            if (typeof data.html !== 'string' || typeof data.summary_html !== 'string' || !Number.isInteger(data.pagination?.page)) {
                throw new Error('La respuesta del listado no es válida. Intentá nuevamente.');
            }
            page = data.pagination.page;
            list.dataset.page = String(page);
            if (data.html !== lastHtml) {
                list.innerHTML = data.html;
                lastHtml = data.html;
            }
            if (data.summary_html !== lastSummary) {
                summary.innerHTML = data.summary_html;
                lastSummary = data.summary_html;
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
    window.addEventListener('pagehide', saveState);

    // Returning from the existing application forms restores the last list context.
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
