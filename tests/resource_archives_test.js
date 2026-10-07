'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const dom = new JSDOM('<div id="root"></div><div data-resource-archives><a href="#stats-i12" data-resource-archive-link data-resource-id="12">Archived resource</a></div>', {runScripts: 'outside-only', url: 'https://localtest.me/omo/o/828/'});
const w = dom.window;
const source = fs.readFileSync(path.join(__dirname, '../omo/assets/js/resource-archives.js'), 'utf8');
let opened = [], closed = 0, selected = [];
w.commonTopbarOpenModal = (...args) => opened.push(args);
w.commonTopbarCloseModal = () => closed++;
w.eval(source);
w.eval(source); // Ajax app navigation must not bind the shared listener twice.
const root = w.document.querySelector('#root');
w.omoOpenResourceArchives({root, title: 'Archives', url: '/omo/api/stats/archives.php?stats_scope=descendants', onOpen: id => selected.push(id)});
assert.deepEqual(opened[0], ['Archives', '/omo/api/stats/archives.php?stats_scope=descendants', 'fetch']);
w.document.querySelector('a').click();
assert.deepEqual(selected, [12]);
assert.equal(closed, 1);
// A new app must own the next archive selection, including local drawer callbacks.
w.omoOpenResourceArchives({root, title: 'Tasks', url: '/omo/api/activities/archives.php', onOpen: id => selected.push('task-' + id)});
w.document.querySelector('a').click();
assert.deepEqual(selected, [12, 'task-12']);
w.omoOpenResourceArchives({root, title: 'Stale', url: '/archives', onOpen: () => { throw Error('Stale app callback'); }});
root.remove();
w.document.querySelector('a').dispatchEvent(new w.MouseEvent('click', {bubbles: true}));
assert.equal(closed, 2);
console.log('Resource archive modal navigation tests passed.');
dom.window.close();
