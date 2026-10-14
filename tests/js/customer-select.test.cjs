const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../public/assets/js/customer-select.js'), 'utf8');

class Element {
    constructor(tagName = 'div') {
        this.tagName = tagName.toUpperCase(); this.dataset = {}; this.children = []; this.attributes = {};
        this.listeners = {}; this.value = ''; this.textContent = ''; this.hidden = false;
        this.classes = new Set();
        this.classList = { toggle: (name, on) => on ? this.classes.add(name) : this.classes.delete(name) };
    }
    append(...items) { this.children.push(...items); for (const item of items) item.parent = this; }
    replaceChildren(...items) { this.children = []; this.append(...items); }
    setAttribute(key, value) { this.attributes[key] = value; }
    removeAttribute(key) { delete this.attributes[key]; }
    addEventListener(name, listener) { (this.listeners[name] ||= []).push(listener); }
    dispatchEvent(event) { for (const listener of this.listeners[event.type] || []) listener(event); }
    setCustomValidity(message) { this.validationMessage = message; }
    scrollIntoView() {}
    focus() { this.dispatchEvent({ type: 'focus' }); }
    contains(item) { return this.children.includes(item); }
    closest() { return this.dataset.customerOption !== undefined ? this : this.parent?.closest(); }
    insertAdjacentElement(_, element) { this.enhanced = element; }
}

function setup(value = '', disabled = false, empty = false) {
    const select = new Element('select');
    select.id = 'customer-select'; select.value = value; select.disabled = disabled; select.required = true;
    const label = { htmlFor: select.id }; select.labels = [label];
    select.options = [{ value: '', textContent: 'Seleccionar cliente', dataset: {} }, ...(empty ? [] : [
        { value: 'c1', textContent: 'María José López — 20-33.095.790-6', dataset: { customerName: 'María José López', customerDocument: '20-33.095.790-6' } },
        { value: 'c2', textContent: 'Mario López — 34194797', dataset: { customerName: 'Mario López', customerDocument: '34194797' } },
        { value: 'c3', textContent: 'María José López — 42081542', dataset: { customerName: 'María José López', customerDocument: '42081542' } },
    ])];
    select.form = new Element('form');
    const scheduled = [];
    vm.runInNewContext(source, {
        document: { querySelectorAll: () => [select], createElement: tag => new Element(tag) },
        Event: class { constructor(type) { this.type = type; } }, setTimeout: callback => scheduled.push(callback),
    });
    const [input, button, panel] = select.enhanced?.children || [];
    const [list, status] = panel?.children || [];
    const type = query => { input.value = query; input.dispatchEvent({ type: 'input' }); };
    const key = name => { let prevented = false; input.dispatchEvent({ type: 'keydown', key: name, preventDefault: () => prevented = true }); return prevented; };
    const choose = index => list.dispatchEvent({ type: 'click', target: list.children[index] });
    return { select, input, button, panel, list, status, label, scheduled, type, key, choose };
}

test('search ignores case and accents and supports multiple name tokens', () => {
    const s = setup();
    s.type('LOPEZ maria');
    assert.equal(s.list.children.length, 2);
    assert.match(s.list.children[0].textContent, /20-33.095.790-6/);
    assert.match(s.list.children[1].textContent, /42081542/);
    s.type('mario');
    assert.equal(s.list.children.length, 1);
    assert.match(s.list.children[0].textContent, /Mario/);
});

test('document search matches complete or partial numbers with or without punctuation', () => {
    const s = setup();
    for (const query of ['20330957906', '33095790', '20-33.095.790-6', 'maria 33095790']) {
        s.type(query);
        assert.equal(s.list.children.length, 1, query);
        s.choose(0);
        assert.equal(s.select.value, 'c1');
    }
});

test('mouse selection sends the customer identifier and editing clears the stale selection', () => {
    const s = setup();
    s.type('34194797');
    s.choose(0);
    assert.equal(s.select.value, 'c2');
    assert.equal(s.input.value, 'Mario López — 34194797');
    assert.equal(s.input.validationMessage, '');
    assert.equal(s.panel.hidden, true);
    s.type('Unknown client');
    assert.equal(s.select.value, '');
    assert.match(s.input.validationMessage, /Seleccioná/);
    assert.equal(s.status.textContent, 'No se encontraron clientes.');
});

test('keyboard arrows and Enter choose an option while Escape closes the list', () => {
    const s = setup();
    s.input.focus();
    assert.equal(s.key('ArrowDown'), true);
    assert.equal(s.input.attributes['aria-activedescendant'], 'customer-select-options-0');
    s.key('ArrowDown');
    s.key('Enter');
    assert.equal(s.select.value, 'c2');
    s.input.focus();
    s.key('Escape');
    assert.equal(s.panel.hidden, true);
    assert.equal(s.input.attributes['aria-expanded'], 'false');
    assert.equal(s.select.value, 'c2');
});

test('preselected customer is retained and the visible input handles required validation', () => {
    const s = setup('c3');
    assert.equal(s.input.value, 'María José López — 42081542');
    assert.equal(s.select.value, 'c3');
    assert.equal(s.select.hidden, true);
    assert.equal(s.select.required, false);
    assert.equal(s.input.required, true);
    assert.equal(s.label.htmlFor, s.input.id);
    assert.equal(s.input.attributes.role, 'combobox');
    assert.equal(s.list.attributes.role, 'listbox');
});

test('locked customer keeps its disabled native control without an editable search field', () => {
    const s = setup('c1', true);
    assert.equal(s.select.enhanced, undefined);
    assert.equal(s.select.value, 'c1');
    assert.equal(s.select.disabled, true);
});

test('native changes and form resets synchronize the selected label', () => {
    const s = setup('c1');
    s.select.value = 'c2';
    s.select.dispatchEvent({ type: 'change' });
    assert.equal(s.input.value, 'Mario López — 34194797');
    s.select.value = '';
    s.select.dispatchEvent({ type: 'change' });
    assert.equal(s.input.value, '');
    s.type('Unsaved search');
    s.select.form.dispatchEvent({ type: 'reset' });
    s.select.value = 'c1';
    for (const callback of s.scheduled) callback();
    assert.equal(s.input.value, 'María José López — 20-33.095.790-6');
    assert.equal(s.input.validationMessage, '');
});

test('no matches cannot select a customer and Tab or blur closes the dropdown', () => {
    const s = setup();
    s.type('Missing');
    s.key('Enter');
    assert.equal(s.select.value, '');
    assert.match(s.input.validationMessage, /Seleccioná/);
    s.key('Tab');
    assert.equal(s.panel.hidden, true);
    s.input.focus();
    s.input.dispatchEvent({ type: 'blur' });
    assert.equal(s.panel.hidden, true);
});

test('empty customer list shows an explicit message', () => {
    const s = setup('', false, true);
    s.input.focus();
    assert.equal(s.list.children.length, 0);
    assert.equal(s.status.textContent, 'No se encontraron clientes.');
});
