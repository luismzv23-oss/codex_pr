const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../public/assets/js/applications.js'), 'utf8');

function setup(saved = '', search = '') {
    const events = {};
    const windowEvents = {};
    const documentEvents = {};
    const fields = Object.fromEntries(['buscar', 'estado', 'moneda', 'sistema'].map(name => [name, { value: '' }]));
    const classes = new Set(['hidden']);
    const error = { textContent: '', classList: { add: x => classes.add(x), remove: x => classes.delete(x) } };
    const list = { dataset: { page: '1', endpoint: 'http://localhost/solicitudes/listado' }, innerHTML: '', setAttribute() {}, addEventListener: (name, cb) => events['list:' + name] = cb };
    const summary = { innerHTML: '' };
    const clear = { addEventListener: (name, cb) => events['clear:' + name] = cb };
    const form = { action: 'http://localhost/solicitudes', elements: { namedItem: name => fields[name] }, querySelector: () => clear, addEventListener: (name, cb) => events['form:' + name] = cb };
    const requests = [];
    const location = { origin: 'http://localhost' };
    const setLocation = value => { const url = new URL(value, location.origin); location.search = url.search; location.href = url.href; };
    setLocation('/solicitudes' + search);
    const stored = new Map(saved ? [['applications-list-state', saved]] : []);
    const window = { location, addEventListener: (name, cb) => windowEvents[name] = cb, setInterval: (cb, ms) => { assert.equal(ms, 15000); events.interval = cb; } };
    const document = { hidden: false, getElementById: id => ({ 'applications-list': list, 'applications-filters': form, 'applications-error': error, 'applications-summary': summary })[id], addEventListener: (name, cb) => documentEvents[name] = cb };
    const context = { window, document, URL, URLSearchParams, AbortController,
        FormData: class { constructor() { return Object.entries(fields).map(([name, field]) => [name, field.value]); } },
        sessionStorage: { getItem: key => stored.get(key), setItem: (key, value) => stored.set(key, value) },
        history: { replaceState: (_, __, url) => setLocation(url), pushState: (_, __, url) => setLocation(url) },
        fetch: (url, options) => new Promise((resolve, reject) => {
            requests.push({ url, options, resolve, reject });
            options.signal.addEventListener('abort', () => reject(Object.assign(new Error('abort'), { name: 'AbortError' })));
        })
    };
    vm.runInNewContext(source, context);
    const settle = async (request, page = 1, html = 'page-' + page) => {
        request.resolve({ ok: true, redirected: false, headers: { get: () => 'application/json' }, json: async () => ({ html, summary_html: 'summary-' + html, pagination: { page } }) });
        await new Promise(resolve => setImmediate(resolve));
    };
    return { events, windowEvents, documentEvents, fields, error, list, requests, location, stored, settle, document, summary };
}

const click = page => ({ target: { closest: () => ({ dataset: { page: String(page) } }) }, button: 0, preventDefault() {} });

test('AJAX navigation retains filters and uses the server-adjusted page', async () => {
    const s = setup('', '?buscar=Ana&page=2');
    assert.equal(s.requests[0].url.searchParams.get('buscar'), 'Ana');
    await s.settle(s.requests[0], 2);
    s.events['list:click'](click(3));
    assert.equal(s.requests[1].url.searchParams.get('page'), '3');
    assert.equal(s.requests[1].url.searchParams.get('buscar'), 'Ana');
    await s.settle(s.requests[1], 2);
    assert.equal(s.list.dataset.page, '2');
    assert.equal(new URLSearchParams(s.location.search).get('page'), '2');
    assert.equal(s.requests[1].options.credentials, 'same-origin');
});

test('submitting and clearing filters reset to the first page', async () => {
    const s = setup('', '?page=3');
    await s.settle(s.requests[0], 3);
    s.fields.buscar.value = 'Mario';
    s.events['form:submit']({ preventDefault() {} });
    assert.equal(s.requests[1].url.searchParams.get('page'), '1');
    assert.equal(s.requests[1].url.searchParams.get('buscar'), 'Mario');
    await s.settle(s.requests[1]);
    s.events['clear:click']({ preventDefault() {} });
    assert.equal(s.requests[2].url.searchParams.has('buscar'), false);
    await s.settle(s.requests[2]);
});

test('returning from editing restores list context and focus refreshes its data', async () => {
    const s = setup('?buscar=Ana&page=4');
    assert.equal(s.requests[0].url.searchParams.get('page'), '4');
    await s.settle(s.requests[0], 4, 'old');
    s.windowEvents.focus();
    await s.settle(s.requests[1], 4, 'updated');
    assert.equal(s.list.innerHTML, 'updated');
    s.document.hidden = true;
    s.events.interval();
    assert.equal(s.requests.length, 2);
    s.document.hidden = false;
    s.events.interval();
    assert.equal(s.requests.length, 3);
    await s.settle(s.requests[2], 4);
});

test('latest navigation wins over an earlier response and failures retain displayed data', async () => {
    const s = setup();
    await s.settle(s.requests[0], 1, 'initial');
    s.events['list:click'](click(2));
    s.events['list:click'](click(3));
    assert.equal(s.requests[1].options.signal.aborted, true);
    await s.settle(s.requests[2], 3, 'latest');
    await s.settle(s.requests[1], 2, 'obsolete');
    assert.equal(s.list.innerHTML, 'latest');
    s.events.interval();
    s.requests[3].reject(new Error('Connection failed'));
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(s.list.innerHTML, 'latest');
    assert.equal(s.error.textContent, 'Connection failed');
});

test('browser back navigation reads page and filters from the URL', async () => {
    const s = setup();
    await s.settle(s.requests[0]);
    s.location.search = '?page=2&sistema=verified';
    s.windowEvents.popstate();
    assert.equal(s.requests[1].url.searchParams.get('sistema'), 'verified');
    assert.equal(s.requests[1].url.searchParams.get('page'), '2');
    await s.settle(s.requests[1], 2);
});


test('all filters survive pagination and summaries update after a modification', async () => {
    const s = setup('', '?page=2&buscar=Ana&estado=approved&moneda=ARS&sistema=french');
    await s.settle(s.requests[0], 2, 'initial');
    s.windowEvents.focus();
    for (const [name, value] of Object.entries({ buscar: 'Ana', estado: 'approved', moneda: 'ARS', sistema: 'french', page: '2' })) {
        assert.equal(s.requests[1].url.searchParams.get(name), value);
    }
    await s.settle(s.requests[1], 1, 'changed');
    assert.equal(s.list.innerHTML, 'changed');
    assert.equal(s.summary.innerHTML, 'summary-changed');
    assert.equal(s.list.dataset.page, '1');
});

test('a malformed summary response preserves the displayed list and counters', async () => {
    const s = setup();
    await s.settle(s.requests[0], 1, 'initial');
    s.events.interval();
    s.requests[1].resolve({ ok: true, redirected: false, headers: { get: () => 'application/json' }, json: async () => ({ html: 'broken', pagination: { page: 2 } }) });
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(s.list.innerHTML, 'initial');
    assert.equal(s.summary.innerHTML, 'summary-initial');
    assert.match(s.error.textContent, /válida/);
});
