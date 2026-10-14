const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../public/assets/js/payments.js'), 'utf8');

function setup(saved = '', search = '', savedGroups = []) {
    const events = {};
    const windowEvents = {};
    const documentEvents = {};
    const fields = Object.fromEntries(['buscar', 'metodo', 'moneda'].map(name => [name, { value: '' }]));
    const classes = new Set(['hidden']);
    const error = { textContent: '', classList: { add: x => classes.add(x), remove: x => classes.delete(x) } };
    const list = { dataset: { page: '1', endpoint: 'http://localhost/pagos/listado' }, innerHTML: '', setAttribute() {}, addEventListener: (name, cb) => events['list:' + name] = cb };
    let currentDetails = [];
    let currentHtml = '';
    list.querySelectorAll = () => currentDetails;
    list.contains = details => currentDetails.includes(details);
    Object.defineProperty(list, 'innerHTML', {
        get: () => currentHtml,
        set: html => {
            currentHtml = html;
            currentDetails = html.split('|').filter(key => key.startsWith('customer:') || key.startsWith('loan:')).map(key => ({ dataset: { groupKey: key }, open: false, matches: () => true }));
        }
    });
    list.innerHTML = 'customer:c1|loan:c1:l1';
    const clear = { addEventListener: (name, cb) => events['clear:' + name] = cb };
    const form = { action: 'http://localhost/pagos', elements: { namedItem: name => fields[name] }, querySelector: () => clear, addEventListener: (name, cb) => events['form:' + name] = cb };
    const requests = [];
    const location = { origin: 'http://localhost' };
    const setLocation = value => { const url = new URL(value, location.origin); location.search = url.search; location.href = url.href; };
    setLocation('/pagos' + search);
    const stored = new Map(saved ? [['payments-list-state', saved]] : []);
    stored.set('payments-open-groups', JSON.stringify(savedGroups));
    const window = { location, addEventListener: (name, cb) => windowEvents[name] = cb, setInterval: (cb, ms) => { assert.equal(ms, 15000); events.interval = cb; } };
    const document = { hidden: false, getElementById: id => ({ 'payments-list': list, 'payments-filters': form, 'payments-error': error })[id], addEventListener: (name, cb) => documentEvents[name] = cb };
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
    return { events, windowEvents, documentEvents, fields, error, list, requests, location, stored, settle, document, details: () => currentDetails };
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
    s.location.search = '?page=2&metodo=transfer';
    s.windowEvents.popstate();
    assert.equal(s.requests[1].url.searchParams.get('metodo'), 'transfer');
    assert.equal(s.requests[1].url.searchParams.get('page'), '2');
    await s.settle(s.requests[1], 2);
});



test('both expanded levels survive an automatic refresh with changed data', async () => {
    const s = setup();
    await s.settle(s.requests[0], 1, 'customer:c1|loan:c1:l1');
    for (const details of s.details()) {
        details.open = true;
        s.events['list:toggle']({ target: details });
    }
    s.windowEvents.focus();
    await s.settle(s.requests[1], 1, 'customer:c1|loan:c1:l1|revision2');
    assert.equal(s.details().every(d => d.open), true);
    const stored = JSON.parse(s.stored.get('payments-open-groups'));
    assert.deepEqual(stored, ['customer:c1', 'loan:c1:l1']);
});

test('closed loan stays closed while its customer remains open after refresh', async () => {
    const s = setup();
    await s.settle(s.requests[0], 1, 'customer:c1|loan:c1:l1');
    const [customer, loan] = s.details();
    customer.open = true;
    s.events['list:toggle']({ target: customer });
    loan.open = true;
    s.events['list:toggle']({ target: loan });
    loan.open = false;
    s.events['list:toggle']({ target: loan });
    s.events.interval();
    await s.settle(s.requests[1], 1, 'customer:c1|loan:c1:l1|revision2');
    assert.equal(s.details()[0].open, true);
    assert.equal(s.details()[1].open, false);
});

test('expanded groups are restored when revisiting a page', async () => {
    const s = setup();
    await s.settle(s.requests[0], 1, 'customer:c1|loan:c1:l1');
    for (const details of s.details()) details.open = true;
    s.events['list:click'](click(2));
    await s.settle(s.requests[1], 2, 'customer:c2|loan:c2:l2');
    assert.equal(s.details().every(d => !d.open), true);
    s.events['list:click'](click(1));
    await s.settle(s.requests[2], 1, 'customer:c1|loan:c1:l1');
    assert.equal(s.details().every(d => d.open), true);
});

test('returning from a payment restores filters, page and expanded groups', async () => {
    const s = setup('?buscar=Ana&metodo=transfer&moneda=ARS&page=3', '', ['customer:c1', 'loan:c1:l1']);
    assert.equal(s.details().every(d => d.open), true);
    assert.equal(s.requests[0].url.searchParams.get('page'), '3');
    assert.equal(s.requests[0].url.searchParams.get('metodo'), 'transfer');
    await s.settle(s.requests[0], 2, 'customer:c1|loan:c1:l1');
    assert.equal(s.details().every(d => d.open), true);
    assert.equal(s.list.dataset.page, '2');
});

test('a delayed toggle from removed DOM cannot overwrite the current expansion state', async () => {
    const s = setup();
    await s.settle(s.requests[0], 1, 'customer:c1|loan:c1:l1');
    const oldLoan = s.details()[1];
    oldLoan.open = true;
    s.events['list:toggle']({ target: oldLoan });
    s.events.interval();
    await s.settle(s.requests[1], 1, 'customer:c1|loan:c1:l1|revision2');
    oldLoan.open = false;
    s.events['list:toggle']({ target: oldLoan });
    assert.equal(s.details()[1].open, true);
    assert.equal(JSON.parse(s.stored.get('payments-open-groups')).includes('loan:c1:l1'), true);
});
