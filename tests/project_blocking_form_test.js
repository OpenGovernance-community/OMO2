'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const source = file => fs.readFileSync(path.join(__dirname, '../omo/api/projects', file), 'utf8');
const tick = () => new Promise(resolve => setTimeout(resolve, 10));

(async () => {
    for (const reasonRequired of [true, false]) {
        for (const dateRequired of [true, false]) {
            const dom = new JSDOM(`<div id="omo-projects-root" data-omo-projects-oid="7"
                data-omo-projects-require-blocked-reason="${+reasonRequired}"
                data-omo-projects-require-blocked-review-date="${+dateRequired}">
                <div data-omo-project-subproject data-project-id="42"><select data-omo-project-detail-status-select data-previous-status="ready">
                    <option value="ready">Ready</option><option value="blocked">Blocked</option></select></div></div>
                <div id="commonTopbarModalBody"></div>`, {url: 'https://omo.test/omo/', runScripts: 'outside-only'});
            const {window} = dom;
            const posts = [];
            window.resetGenericExpandedMenu = () => {};
            window.commonTopbarOpenModal = (title, html) => { window.document.getElementById('commonTopbarModalBody').innerHTML = html; };
            window.commonTopbarCloseModal = () => {};
            window.omoNotify = () => {};
            window.fetch = async (url, options) => {
                posts.push(Object.fromEntries(options.body.entries()));
                return {ok: true, text: async () => JSON.stringify({status: true})};
            };
            try {
                window.eval(source('projects.js'));
                const select = window.document.querySelector('select');
                select.value = 'blocked';
                select.dispatchEvent(new window.Event('change', {bubbles: true}));
                await tick();
                const form = window.document.querySelector('[data-omo-project-blocked-dialog]');
                const reason = form.querySelector('textarea');
                const date = form.querySelector('input[type=date]');
                const auto = form.querySelector('input[type=checkbox]');
                assert.equal(reason.required, reasonRequired);
                assert.equal(date.required, dateRequired);
                assert.equal(form.checkValidity(), !reasonRequired && !dateRequired);
                assert.equal(auto.disabled, true);
                date.value = '2030-01-04';
                date.dispatchEvent(new window.Event('input'));
                assert.equal(auto.disabled, false);
                auto.checked = true;
                date.value = '';
                date.dispatchEvent(new window.Event('input'));
                assert.equal(auto.checked, false, 'Clearing date clears automatic reactivation');
                if (reasonRequired) reason.value = 'Waiting';
                if (dateRequired) date.value = '2030-01-04';
                form.dispatchEvent(new window.Event('submit', {bubbles: true, cancelable: true}));
                await tick();
                assert.equal(posts.length, 1, 'Valid blocking form submits');
                assert.equal(posts[0].blocked_reason, reasonRequired ? 'Waiting' : '');
                assert.equal(posts[0].blocked_until, dateRequired ? '2030-01-04' : '');
            } finally { window.close(); }

            const edit = new JSDOM(`<div data-omo-project-form-root><form id="project-edit"
                data-require-blocked-reason="${+reasonRequired}" data-require-blocked-review-date="${+dateRequired}">
                <button type="button" data-omo-project-parent-picker>Parent</button>
                <select name="status"><option value="blocked">Blocked</option><option value="ready">Ready</option></select>
                <section data-omo-project-blocked-section><textarea data-omo-project-blocked-reason></textarea>
                <input type="date" data-omo-project-blocked-until><input type="checkbox" data-omo-project-blocked-auto>
                <label data-omo-project-blocked-target-field><select data-omo-project-blocked-target></select></label></section>
                </form></div>`, {url: 'https://omo.test/omo/', runScripts: 'outside-only'});
            try {
                edit.window.eval(source('create.js'));
                edit.window.commonPageScripts['/omo/api/projects/create.js']({formId: 'project-edit', parentCandidates: [], texts: {}});
                const form = edit.window.document.querySelector('form');
                assert.equal(form.querySelector('textarea').required, reasonRequired);
                assert.equal(form.querySelector('input[type=date]').required, dateRequired);
                assert.equal(form.checkValidity(), !reasonRequired && !dateRequired);
                const status = form.querySelector('[name=status]');
                status.value = 'ready';
                status.dispatchEvent(new edit.window.Event('change'));
                assert.equal(form.querySelector('textarea').required, false);
                assert.equal(form.querySelector('input[type=date]').required, false);
                assert.equal(form.querySelector('section').hidden, true);
            } finally { edit.window.close(); }
        }
    }
    console.log('Project blocking form tests passed (dialog and editor, four combinations).');
})().catch(error => { console.error(error); process.exitCode = 1; });
