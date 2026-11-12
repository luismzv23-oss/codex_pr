// Loads pages of a [data-ajax-list] container in the background (no full reload).
// Pager links (a[data-page]) keep real hrefs, so the listing still works without JS.
// The server answers AJAX requests with JSON { html, pagination } for the same URL.
(() => {
    'use strict';
    const list = document.querySelector('[data-ajax-list]');
    if (!list) return;

    let activeRequest = null;

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
            list.scrollIntoView({ block: 'start', behavior: 'smooth' });
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
        load(link.href, true);
    });

    window.addEventListener('popstate', () => load(window.location.href, false));
})();
