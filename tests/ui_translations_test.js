'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../omo/assets/js/ui-translations.js'), 'utf8');

(async function () {
    const window = {omoConfig: {uiTexts: {
        greeting: 'Hello, {name}.', count: {one: '{count} member', other: '{count} members'},
    }}};
    vm.runInNewContext(source, {window, Promise});
    await window.omoUiReady;
    assert.equal(window.omoUiText('greeting', 'Bonjour.', {name: 'Zoé'}), 'Hello, Zoé.');
    assert.equal(window.omoUiText('count', '', {count: 1}), '1 member');
    assert.equal(window.omoUiText('count', '', {count: 3}), '3 members');
    assert.equal(window.omoUiText('missing', 'Échec de l’envoi.'), 'Échec de l’envoi.');
    assert.equal(window.omoUiHtml('greeting', '', {name: '<img src=x onerror="run()">'}), 'Hello, &lt;img src=x onerror=&quot;run()&quot;&gt;.');
    assert.equal(window.omoUiText('greeting', '', {name: '{name}'}), 'Hello, {name}.');

    let requested = '';
    const standalone = {};
    vm.runInNewContext(source, {window: standalone, Promise, fetch: async (url, options) => {
        requested = url;
        assert.equal(options.credentials, 'same-origin');
        return {ok: true, json: async () => ({greeting: 'Guten Tag, {name}.'})};
    }});
    assert.equal(standalone.omoUiText('greeting', 'Bonjour.'), 'Bonjour.');
    await standalone.omoUiReady;
    assert.equal(requested, '/common/jstranslation/omo_ui.php');
    assert.equal(standalone.omoUiText('greeting', '', {name: 'Zoé'}), 'Guten Tag, Zoé.');
    const offline = {};
    vm.runInNewContext(source, {window: offline, Promise, fetch: async () => { throw new Error('offline'); }});
    await offline.omoUiReady;
    assert.equal(offline.omoUiText('missing', 'À réessayer.'), 'À réessayer.');
    console.log('ui_translations_test: OK');
}()).catch(error => { console.error(error); process.exitCode = 1; });
