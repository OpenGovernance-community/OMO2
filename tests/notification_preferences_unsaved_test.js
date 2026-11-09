'use strict';
// node tests/notification_preferences_unsaved_test.js /path/to/playwright /path/to/chrome
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const browser = await chromium.launch({headless: true, ...(process.argv[3] ? {executablePath: process.argv[3]} : {})});
    try {
        const page = await browser.newPage();
        await page.route('http://notifications.test/**', route => route.fulfill({contentType: 'text/html', body: '<html lang="fr"><body></body></html>'}));
        await page.goto('http://notifications.test/');
        await page.setContent(`<div class="omo-overlay-drawer is-open" id="drawer">
            <button class="omo-overlay-drawer__close">Close</button>
            <div data-omo-notification-settings>
                <form data-omo-notification-preferences>
                    <input type="hidden" name="csrf_token" value="fixture">
                    <input type="checkbox" name="preferences[personal][email]">
                    <select name="preferences[calendar_event_starting][lead_time]">
                        <option value="">None</option><option value="1h">One hour</option>
                    </select>
                    <button type="submit">Save</button>
                    <p data-omo-notification-preferences-feedback></p>
                </form>
            </div></div>`);
        await page.evaluate(() => {
            window.messages = [];
            window.confirmations = [];
            window.commonNotify = (message, type) => messages.push({message, type});
            window.confirm = message => { confirmations.push(message); return false; };
            window.fetch = url => url.startsWith('/common/jstranslation/')
                ? Promise.resolve({ok: true, json: async () => ({discard: 'Discard changes?'})})
                : new Promise((resolve, reject) => { window.deliver = resolve; window.fail = reject; });
            document.querySelector('.omo-overlay-drawer__close').onclick = () => { document.getElementById('drawer').hidden = true; };
        });
        for (const file of ['common/drawer/subdrawer.js', 'omo/api/parameters/notifications/index.js']) {
            await page.addScriptTag({content: fs.readFileSync(path.join(__dirname, '..', file), 'utf8')});
        }
        await page.evaluate(() => window.commonPageScripts['/omo/api/parameters/notifications/index.js']({
            configuration: {preferencesUrl: '/preferences', texts: {error: 'Save failed'}},
            notificationsPreferencesSaved: 'Saved', message: 'Save failed'
        }));
        const dirty = () => page.evaluate(() => commonDrawerHasUnsavedChanges(document.getElementById('drawer')));
        const save = () => page.locator('[type="submit"]').click();
        const reply = async (ok, status) => {
            await page.evaluate(({ok, status}) => window.deliver({ok, json: async () => ({status})}), {ok, status});
            await page.waitForFunction(() => !document.querySelector('[type="submit"]').disabled);
        };

        assert.equal(await dirty(), false);
        await page.locator('[type="checkbox"]').check();
        await page.locator('select').selectOption('1h');
        assert.equal(await dirty(), true, 'Changed preferences must be protected.');
        await save();
        assert.equal(await dirty(), true, 'Pending saves must retain protection.');
        await reply(true, true);
        assert.equal(await dirty(), false, 'A successful save must clear the dirty state.');
        await page.locator('.omo-overlay-drawer__close').click();
        assert.deepEqual(await page.evaluate(() => confirmations), [], 'Closing after saving must not warn.');
        assert.equal(await page.locator('#drawer').isHidden(), true);

        await page.evaluate(() => { document.getElementById('drawer').hidden = false; });
        await page.locator('select').selectOption('');
        assert.equal(await dirty(), true, 'Later edits must remain protected.');
        for (const [ok, status] of [[false, false], [true, false]]) {
            await save();
            await reply(ok, status);
            assert.equal(await dirty(), true, 'HTTP and application errors must retain protection.');
        }
        await save();
        await page.evaluate(() => window.fail(new Error('Network failed')));
        await page.waitForFunction(() => !document.querySelector('[type="submit"]').disabled);
        assert.equal(await dirty(), true, 'Network errors must retain protection.');
        await page.locator('.omo-overlay-drawer__close').click();
        assert.equal(await page.locator('#drawer').isVisible(), true, 'Declining the warning must keep unsaved preferences open.');
        await save();
        await reply(true, true);
        assert.equal(await dirty(), false, 'Retrying successfully must allow closing again.');
        assert.deepEqual(await page.evaluate(() => messages.map(item => item.type)), ['success', 'error', 'error', 'error', 'success']);
        console.log('notification_preferences_unsaved_test: OK');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
