// Loads pages of a [data-ajax-list] container in the background (no full reload).
// Pager links (a[data-page]) keep real hrefs, so the listing still works without JS.
// An optional form[data-ajax-filter] (GET) is applied the same way: on submit, when a
// select changes, and via its [data-clear-filters] link. The server answers AJAX requests
// with JSON { html, pagination } for the same URL.
(() => {
    'use strict';
    const list = document.querySelector('[data-ajax-list]');
    if (!list) return;
    const form = document.querySelector('form[data-ajax-filter]');

    let activeRequest = null;

    function filterUrl() {
        const url = new URL(form.action, window.location.origin);
        for (const [name, value] of new FormData(form)) {
            if (String(value) !== '') url.searchParams.set(name, String(value));
        }
        return url.href; // Filtering always starts at page 1.
    }

    function syncForm(url) {
        if (!form) return;
        const params = new URL(url, window.location.origin).searchParams;
        for (const field of form.elements) {
            if (!field.name || field.disabled) continue;
            const value = params.get(field.name) || '';
            if (field.value !== value) {
                field.value = value;
                field.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }

    async function load(url, push) {
        if (activeRequest) activeRequest.abort();
        activeRequest = new AbortController();
        list.setAttribute('aria-busy', 'true');
        list.style.opacity = '0.6';
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                credentials: 'same-origin',
                signal: activeRequest.signal,
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();
            list.innerHTML = data.html;
            if (push) history.pushState({ ajaxList: true }, '', url);
        } catch (error) {
            if (error.name === 'AbortError') return;
            window.location.href = url; // Fallback: regular navigation (e.g. session expired).
        } finally {
            list.removeAttribute('aria-busy');
            list.style.opacity = '';
        }
    }

    list.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-page]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
        event.preventDefault();
        load(link.href, true).then(() => list.scrollIntoView({ block: 'start', behavior: 'smooth' }));
    });

    if (form) {
        let syncing = false;
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            load(filterUrl(), true);
        });
        form.addEventListener('change', (event) => {
            if (!syncing && event.target.matches('select')) load(filterUrl(), true);
        });
        form.querySelector('[data-clear-filters]')?.addEventListener('click', (event) => {
            event.preventDefault();
            syncing = true;
            syncForm(form.action);
            syncing = false;
            load(filterUrl(), true);
        });
        window.addEventListener('popstate', () => {
            syncing = true;
            syncForm(window.location.href);
            syncing = false;
        });
    }

    window.addEventListener('popstate', () => load(window.location.href, false));
})();
