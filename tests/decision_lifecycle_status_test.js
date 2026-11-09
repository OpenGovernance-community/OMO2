'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const source = fs.readFileSync(path.join(__dirname, '../omo/api/decision/modules/lifecycle_status.js'), 'utf8');

function fixture(phase, value, confirmation = false) {
    const dom = new JSDOM('<form data-omo-decision-lifecycle-confirm-template="Evaluation at {date}" data-omo-decision-lifecycle-consultation-confirm-template="Elaboration at {date}">'
        + '<select name="status"><option value="draft">Draft</option><option value="scheduled">Scheduled</option><option value="consultation">Elaboration</option><option value="evaluation">Evaluation</option></select>'
        + '<input type="datetime-local" name="consultation_start_at"><input type="datetime-local" name="consultation_end_at">'
        + '<input type="datetime-local" name="evaluation_start_at"><input type="datetime-local" name="evaluation_end_at"></form>', {runScripts: 'outside-only'});
    const {window} = dom;
    const RealDate = window.Date;
    const now = new RealDate('2026-10-05T12:00:00').getTime();
    window.Date = class extends RealDate {
        constructor(...args) { super(...(args.length ? args : [now])); }
    };
    const calls = [];
    window.confirm = message => { calls.push(message); return confirmation; };
    window.eval(source);
    const form = window.document.querySelector('form');
    const select = form.querySelector('select');
    const start = form.querySelector('[name="' + phase + '_start_at"]');
    start.value = value;
    select.dispatchEvent(new window.Event('focusin', {bubbles: true}));
    select.value = phase === 'consultation' ? 'consultation' : 'evaluation';
    return {dom, window, form, select, start, calls};
}

for (const phase of ['consultation', 'evaluation']) {
    const missing = fixture(phase, '');
    missing.select.dispatchEvent(new missing.window.Event('change', {bubbles: true}));
    assert.equal(missing.calls.length, 0, phase + ': no confirmation without a planned start');
    assert.equal(missing.start.value, '2026-10-05T12:00', phase + ': record the immediate start for saving');
    assert.equal(missing.select.value, phase, phase + ': keep the selected status');
    const submit = new missing.window.Event('submit', {bubbles: true, cancelable: true});
    missing.form.dispatchEvent(submit);
    assert.equal(submit.defaultPrevented, false, phase + ': the form can be saved');
    assert.equal(missing.calls.length, 0, phase + ': submitting does not prompt again');
    missing.dom.window.close();

    const direct = fixture(phase, '');
    assert.equal(direct.window.omoDecisionEnsureLifecycleStatusConsistency(direct.form), true);
    assert.equal(direct.calls.length, 0, phase + ': direct save validation is also silent');
    assert.equal(direct.start.value, '2026-10-05T12:00');
    direct.dom.window.close();

    const past = fixture(phase, '2026-10-04T09:00');
    assert.equal(past.window.omoDecisionEnsureLifecycleStatusConsistency(past.form), true);
    assert.equal(past.calls.length, 0, phase + ': no confirmation for a past start');
    assert.equal(past.start.value, '2026-10-04T09:00', phase + ': preserve the existing start');
    past.dom.window.close();

    const future = fixture(phase, '2026-10-10T09:00');
    assert.equal(future.window.omoDecisionEnsureLifecycleStatusConsistency(future.form), false);
    assert.equal(future.calls.length, 1, phase + ': a future start still requires confirmation');
    assert(!future.calls[0].includes('{date}'), phase + ': the confirmation includes the planned date');
    assert.equal(future.select.value, 'draft', phase + ': rejecting restores the previous status');
    assert.equal(future.start.value, '2026-10-10T09:00', phase + ': rejecting preserves the schedule');
    future.dom.window.close();

    const accepted = fixture(phase, '2026-10-10T09:00', true);
    assert.equal(accepted.window.omoDecisionEnsureLifecycleStatusConsistency(accepted.form), true);
    assert.equal(accepted.calls.length, 1);
    assert.equal(accepted.start.value, '2026-10-05T12:00', phase + ': confirmed early starts are adjusted');
    accepted.dom.window.close();
}

for (const consultationEnd of ['', '2026-10-20T17:00']) {
    const directEvaluation = fixture('evaluation', '');
    directEvaluation.form.querySelector('[name="consultation_end_at"]').value = consultationEnd;
    directEvaluation.form.querySelector('[name="evaluation_end_at"]').value = '2026-10-30T17:00';
    assert.equal(directEvaluation.window.omoDecisionEnsureLifecycleStatusConsistency(directEvaluation.form), true);
    assert.equal(directEvaluation.form.querySelector('[name="consultation_start_at"]').value, '');
    assert.equal(directEvaluation.form.querySelector('[name="consultation_end_at"]').value, consultationEnd, 'Without elaboration start, preserve its optional end');
    assert.equal(directEvaluation.form.querySelector('[name="evaluation_end_at"]').value, '2026-10-30T17:00', 'Evaluation end is never filled or corrected automatically');
    directEvaluation.dom.window.close();
}
const withElaboration = fixture('evaluation', '');
withElaboration.form.querySelector('[name="consultation_start_at"]').value = '2026-10-10T09:00';
withElaboration.form.querySelector('[name="consultation_end_at"]').value = '2026-10-20T17:00';
assert.equal(withElaboration.window.omoDecisionEnsureLifecycleStatusConsistency(withElaboration.form), true);
assert.equal(withElaboration.form.querySelector('[name="consultation_start_at"]').value, '2026-10-05T12:00');
assert.equal(withElaboration.form.querySelector('[name="consultation_end_at"]').value, '2026-10-05T12:00', 'An elaboration with a start closes when evaluation starts');
assert.equal(withElaboration.form.querySelector('[name="evaluation_end_at"]').value, '', 'Evaluation end remains optional');
withElaboration.dom.window.close();
console.log('Decision lifecycle status: passed.');
