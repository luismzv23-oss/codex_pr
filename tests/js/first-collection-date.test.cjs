const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../public/assets/js/first-collection-date.js'), 'utf8');

function setup(checked) {
    const events = {};
    const pending = [];
    const input = { disabled: false, required: false, value: '2096-01-31' };
    const field = { hidden: false, querySelector: () => input };
    const checkbox = { checked, addEventListener: (name, callback) => events[name] = callback,
        form: { addEventListener: (name, callback) => events[name] = callback } };
    const container = { querySelector: selector => selector === '[data-collection-toggle]' ? checkbox : field };
    vm.runInNewContext(source, { document: { querySelectorAll: () => [container] }, setTimeout: callback => pending.push(callback) });
    return { input, field, checkbox, events, pending };
}

test('unchecked uses automatic dates and excludes the date field from submission', () => {
    const s = setup(false);
    assert.equal(s.field.hidden, true);
    assert.equal(s.input.disabled, true);
    assert.equal(s.input.required, false);
    s.checkbox.checked = true;
    s.events.change();
    assert.equal(s.field.hidden, false);
    assert.equal(s.input.disabled, false);
    assert.equal(s.input.required, true);
});

test('restored checkbox retains the chosen date and reset synchronizes controls', () => {
    const s = setup(true);
    assert.equal(s.input.required, true);
    assert.equal(s.input.value, '2096-01-31');
    s.events.reset();
    s.checkbox.checked = false;
    for (const callback of s.pending) callback();
    assert.equal(s.field.hidden, true);
    assert.equal(s.input.disabled, true);
});
