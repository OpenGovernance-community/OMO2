'use strict';

// Run with jsdom on NODE_PATH. No application server or database writes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');

async function renderChange(before, after, withWordDiff = true) {
    const dom = new JSDOM('<!doctype html><body><button data-omo-chat-open data-omo-chat-readonly="1" data-omo-chat-endpoint="/chat-test"></button><div id="commonTopbarModalBody"></div></body>', {runScripts: 'outside-only'});
    const {window} = dom;
    await new Promise(resolve => window.addEventListener('load', resolve, {once: true}));
    window.commonTopbarOpenModal = (title, html) => {
        window.document.getElementById('commonTopbarModalBody').innerHTML = html;
    };
    window.fetch = async url => ({ok: true, json: async () => url === '/common/jstranslation/change_details.php' ? {} : {
        status: true, canPost: false,
        messages: [{type: 'system', content: 'Point modifie', changes: [{field: 'content', before, after}]}]
    }});
    if (withWordDiff) window.eval(read('common/choice/word-diff.js'));
    window.eval(read('common/choice/change-details.js'));
    window.eval(read('common/chat/thread.js'));
    window.document.querySelector('button').click();
    await new Promise(resolve => window.setTimeout(resolve, 0));
    const result = {
        before: window.document.querySelector('.omo-change-details__value.is-before .omo-change-details__value-content').textContent,
        after: window.document.querySelector('.omo-change-details__value.is-after .omo-change-details__value-content').textContent,
        removed: Array.from(window.document.querySelectorAll('.omo-change-details__removed'), node => node.textContent).join('')
    };
    dom.window.close();
    return result;
}

(async () => {
    const before = '<p>rencontre</p><p>Ordre :</p><p>Todo :</p><p>Dans</p>';
    const after = '<p>rencontre</p><p>Todo :</p><p>Dans</p>';
    for (const withWordDiff of [true, false]) {
        const result = await renderChange(before, after, withWordDiff);
        assert.equal(result.before, 'rencontre Ordre : Todo : Dans');
        assert.equal(result.after, 'rencontre Todo : Dans');
        if (withWordDiff) assert.equal(result.removed.trim(), 'Ordre :', 'Deleting a heading must not highlight adjacent paragraphs');
    }

    const cases = [
        ['<div>Un</div><div>Deux</div>', 'Un Deux'],
        ['Un<br>Deux<BR />Trois<hr>Fin', 'Un Deux Trois Fin'],
        ['<h2>Titre</h2><p>Texte</p><ul><li>Un</li><li>Deux</li></ul>', 'Titre Texte Un Deux'],
        ['<table><tr><td>Un</td><td>Deux</td></tr><tr><th>Trois</th></tr></table>', 'Un Deux Trois'],
        ['Avant<div>Bloc</div>Apres', 'Avant Bloc Apres'],
        ['<p>ren<strong>contre</strong> <em>ici</em></p>', 'rencontre ici'],
        ['<p>Un&nbsp;&amp;&nbsp;deux</p>\n<p>Trois</p>', 'Un & deux Trois'],
        ['<p>Avant<span data-omo-embed-type="document">Document</span>Apres</p>', 'Avant [Document] Apres'],
        ['<div><p>Un</p><p><br></p><p>Deux</p></div>', 'Un Deux']
    ];
    for (const [html, expected] of cases) {
        const result = await renderChange(html, html);
        assert.equal(result.before, expected);
        assert.equal(result.after, expected);
        assert.equal(result.removed, '', 'Formatting must not introduce word changes');
    }
    console.log('PV review chat excerpts: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
