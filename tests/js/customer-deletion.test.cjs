const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../public/assets/js/customer-deletion.js'), 'utf8');

function setup(inList = true) {
    const documentEvents = {};
    const elements = {};
    const requests = [];
    const emitted = [];
    const stored = new Map();
    let redirected = null;
    for (const id of ['customer-delete-dialog', 'customer-delete-form', 'customer-delete-password', 'customer-delete-summary', 'customer-delete-error', 'customer-delete-submit', 'customer-delete-cancel']) {
        const listeners = {};
        const classes = new Set();
        elements[id] = { listeners, value: '', textContent: '', disabled: false,
            addEventListener: (name, cb) => listeners[name] = cb,
            focus() {},
            classList: { add: name => classes.add(name), remove: name => classes.delete(name) },
        };
    }
    const dialog = elements['customer-delete-dialog'];
    dialog.open = false;
    dialog.dataset = { returnUrl: '/clientes' };
    dialog.showModal = () => dialog.open = true;
    dialog.close = () => { dialog.open = false; dialog.listeners.close(); };
    const form = elements['customer-delete-form'];
    const password = elements['customer-delete-password'];
    const submit = elements['customer-delete-submit'];
    form.reset = () => password.value = '';
    form.reportValidity = () => password.value.length > 0;
    const document = {
        getElementById: id => id === 'customers-list' ? (inList ? {} : null) : elements[id],
        addEventListener: (name, cb) => documentEvents[name] = cb,
        dispatchEvent: event => emitted.push(event),
    };
    vm.runInNewContext(source, {
        document, AbortController, URLSearchParams,
        CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
        sessionStorage: { setItem: (key, value) => stored.set(key, value) },
        window: { location: { assign: url => redirected = url } },
        fetch: (url, options) => new Promise((resolve, reject) => {
            requests.push({ url, options, resolve, reject });
            options.signal?.addEventListener('abort', () => reject(Object.assign(new Error('abort'), { name: 'AbortError' })));
        }),
    });
    const button = { dataset: { previewUrl: '/clientes/test/eliminacion', deleteUrl: '/clientes/test' }, focus() {} };
    const open = () => documentEvents.click({ target: { closest: () => button }, preventDefault() {} });
    const send = () => form.listeners.submit({ preventDefault() {} });
    const flush = () => new Promise(resolve => setImmediate(resolve));
    const reply = async (index, data, status = 200, redirectedResponse = false) => {
        requests[index].resolve({ ok: status < 400, redirected: redirectedResponse, headers: { get: () => 'application/json' }, json: async () => data });
        await flush();
    };
    const preview = async () => {
        open();
        await reply(requests.length - 1, {
            csrf: { header: 'X-CSRF-TOKEN', hash: 'token1' },
            summary: { customer_name: 'Cliente Prueba', applications: 2, loans: 1, installments: 3, payments: 4, notifications: 0 },
        });
    };
    return { requests, emitted, stored, dialog, form, password, submit, elements, preview, open, send, reply, flush, redirect: () => redirected };
}

test('confirmation shows counts and sends password, method and CSRF before refreshing the list', async () => {
    const s = setup();
    await s.preview();
    assert.match(s.elements['customer-delete-summary'].textContent, /2 solicitudes, 1 préstamos, 3 cuotas, 4 pagos/);
    assert.equal(s.submit.disabled, false);
    s.password.value = 'admin-password';
    s.send();
    const request = s.requests[1];
    assert.equal(request.options.method, 'POST');
    assert.equal(request.options.body.get('_method'), 'DELETE');
    assert.equal(request.options.body.get('admin_password'), 'admin-password');
    assert.equal(request.options.headers['X-CSRF-TOKEN'], 'token1');
    assert.equal(request.options.credentials, 'same-origin');
    assert.equal(s.submit.disabled, true);
    let prevented = false;
    s.dialog.listeners.cancel({ preventDefault: () => prevented = true });
    assert.equal(prevented, true);
    await s.reply(1, { message: 'Eliminado', csrf: { header: 'X-CSRF-TOKEN', hash: 'token2' } });
    assert.equal(s.dialog.open, false);
    assert.equal(s.password.value, '');
    assert.equal(s.emitted[0].type, 'customer:deleted');
    assert.equal(s.emitted[0].detail.message, 'Eliminado');
    assert.equal(s.redirect(), null);
});

test('wrong password keeps the dialog open and retry uses the regenerated CSRF token', async () => {
    const s = setup();
    await s.preview();
    s.password.value = 'wrong';
    s.send();
    await s.reply(1, { message: 'Contraseña incorrecta', csrf: { header: 'X-CSRF-TOKEN', hash: 'token2' } }, 422);
    assert.equal(s.dialog.open, true);
    assert.equal(s.password.value, '');
    assert.equal(s.emitted.length, 0);
    assert.equal(s.elements['customer-delete-error'].textContent, 'Contraseña incorrecta');
    s.password.value = 'correct';
    s.send();
    assert.equal(s.requests[2].options.headers['X-CSRF-TOKEN'], 'token2');
    await s.reply(2, { message: 'Eliminado' });
});

test('canceling preview aborts its request without deleting anything', async () => {
    const s = setup();
    s.open();
    s.elements['customer-delete-cancel'].listeners.click();
    await s.flush();
    assert.equal(s.requests[0].options.signal.aborted, true);
    assert.equal(s.dialog.open, false);
    assert.equal(s.requests.length, 1);
    assert.equal(s.emitted.length, 0);
});

test('forbidden preview leaves deletion disabled', async () => {
    const s = setup();
    s.open();
    await s.reply(0, { message: 'Sin permiso', csrf: { header: 'X-CSRF-TOKEN', hash: 'token1' } }, 403);
    assert.equal(s.submit.disabled, true);
    s.password.value = 'password';
    s.send();
    assert.equal(s.requests.length, 1);
    assert.equal(s.elements['customer-delete-error'].textContent, 'Sin permiso');
});

test('session redirect reports an error without announcing a successful deletion', async () => {
    const s = setup();
    await s.preview();
    s.password.value = 'password';
    s.send();
    await s.reply(1, {}, 200, true);
    assert.equal(s.dialog.open, true);
    assert.equal(s.emitted.length, 0);
    assert.match(s.elements['customer-delete-error'].textContent, /sesión/);
});

test('deletion from customer detail retains the cleanup warning for the list', async () => {
    const s = setup(false);
    await s.preview();
    s.password.value = 'password';
    s.send();
    await s.reply(1, { message: 'Eliminado; contrato pendiente', pending_contracts: 1 });
    assert.equal(s.redirect(), '/clientes');
    assert.equal(s.stored.get('customers-deletion-message'), 'Eliminado; contrato pendiente');
});
