'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const source = fs.readFileSync(path.join(__dirname, '../omo/api/decision/edit_shared.js'), 'utf8');
async function scenario({confirmed = true, ok = true}) {
    const dom = new JSDOM('<div class="omo-decision-edit"><button data-omo-decision-interrupt data-request-url="/action.php" data-request-payload=\'{"oid":7,"id":9,"decision_action":"interrupt"}\'>Interrompre le scrutin</button></div>', {runScripts: 'outside-only', url: 'https://example.test/'});
    const {window} = dom; const calls = [];
    window.confirm = () => confirmed;
    window.commonNotify = (message, type) => calls.push(['notify', message, type]);
    window.commonDrawerMarkSaved = editor => calls.push(['clean', editor.className]);
    window.omoDecisionOpenNestedDrawer = (title, url) => calls.push(['open', url]);
    let resolve;
    window.fetch = (url, options) => { calls.push(['fetch', url, Array.from(options.body.entries())]); return new Promise(done => { resolve = done; }); };
    window.eval(source);
    window.commonPageScripts['/omo/api/decision/edit_shared.js']({interruptConfirm: 'Confirm', interruptPending: 'Pending', interruptError: 'Failed'});
    const button = window.document.querySelector('button'); button.click();
    if (!confirmed) {
        assert.deepEqual(calls, []); assert(!button.disabled);
    } else {
        assert(button.disabled); assert.equal(button.textContent, 'Pending');
        resolve({ok, json: async () => ({status: ok, message: ok ? 'Interrupted' : 'Response already saved', redirectUrl: '/edit.php?id=9'})});
        await new Promise(done => window.setTimeout(done, 0));
        assert(!button.disabled); assert.equal(button.textContent, 'Interrompre le scrutin');
        assert.deepEqual(calls[0], ['fetch', '/action.php', [['oid', '7'], ['id', '9'], ['decision_action', 'interrupt']]]);
        if (ok) {
            assert.deepEqual(calls.slice(1), [['clean', 'omo-decision-edit'], ['notify', 'Interrupted', 'success'], ['open', '/edit.php?id=9']]);
        } else {
            assert.deepEqual(calls.slice(1), [['notify', 'Response already saved', 'error']]);
        }
    }
    window.close();
}
(async () => {
    await scenario({confirmed: false}); await scenario({}); await scenario({ok: false});
    console.log('decision_interrupt_ui_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
