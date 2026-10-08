'use strict';
// Render with pv_invitation_recipient_selection_test.php --render; all browser sends are mocked.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[3] || 'playwright');

(async () => {
    const browser = await chromium.launch({headless: true, channel: process.env.PV_BROWSER_CHANNEL || 'msedge'});
    try {
        const page = await browser.newPage({viewport: {width: 390, height: 850}});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent(fs.readFileSync(process.argv[2], 'utf8'));
        for (const file of ['common/assets/theme.css', 'common/assets/components.css']) {
            await page.addStyleTag({path: path.join(__dirname, '..', file)});
        }
        await page.addStyleTag({content: 'body { font-family: system-ui, sans-serif; margin: 0; }'});
        await page.evaluate(() => {
            window.fixtureSends = [];
            window.fixtureNotifications = [];
            window.commonNotify = (...args) => window.fixtureNotifications.push(args);
            window.fetch = (url, options) => {
                window.fixtureSends.push(options.body.getAll('recipient_emails[]'));
                return new Promise((resolve, reject) => { window.fixtureResolve = resolve; window.fixtureReject = reject; });
            };
        });
        const checkbox = page.locator('[name="recipient_emails[]"]');
        const counter = page.locator('[data-pv-invitation-count]');
        const submit = page.locator('#omoPvSendInvitationsSubmit');
        const message = page.locator('[name="message"]');
        const originalMessage = await message.inputValue();
        assert.equal(await checkbox.count(), 3);
        assert.equal(await checkbox.evaluateAll(inputs => inputs.every(input => input.checked)), true, 'Everyone is selected initially.');
        assert.equal(await page.locator('details').getAttribute('open'), null, 'The list starts collapsed.');
        await page.locator('summary').click();
        assert.equal(await checkbox.first().isVisible(), true);
        assert((await page.locator('fieldset').innerText()).includes('Bob <guest>'), 'Recipient names are rendered as text.');
        await checkbox.nth(1).uncheck();
        assert((await counter.innerText()).startsWith('2 destinataires'));
        await checkbox.nth(2).uncheck();
        assert((await counter.innerText()).startsWith('1 destinataire recevra'));
        await checkbox.nth(0).uncheck();
        assert.equal(await submit.isDisabled(), true);
        assert.equal(await page.locator('[data-pv-invitation-selection-error]').isVisible(), true);
        await page.locator('form').evaluate(form => form.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true})));
        assert.deepEqual(await page.evaluate(() => window.fixtureSends), [], 'Empty selection never sends.');
        await checkbox.nth(2).check();
        await submit.click();
        assert.deepEqual(await page.evaluate(() => window.fixtureSends), [['guest@example.invalid']], 'The posted form contains only checked recipients.');
        assert.equal(await checkbox.nth(2).isDisabled(), true, 'Selection is frozen during delivery.');
        await page.evaluate(() => window.fixtureReject(new Error('Fixture network error')));
        await page.waitForFunction(() => !document.getElementById('omoPvSendInvitationsSubmit').disabled);
        assert.equal(await checkbox.nth(2).isChecked(), true, 'Failures preserve the selection for retry.');
        await submit.click();
        await page.evaluate(() => window.fixtureResolve({ok: true, json: () => Promise.resolve({status: true, message: 'Fixture success'})}));
        await page.waitForFunction(() => !document.querySelector('[data-pv-invitation-recipients]').disabled);
        assert.equal(await submit.isDisabled(), true, 'A successful send keeps the button disabled.');
        assert.equal(await submit.getAttribute('title'), 'Le message a déjà été envoyé');
        assert.equal(await submit.evaluate(button => getComputedStyle(button).cursor), 'not-allowed', 'A completed send does not show a waiting cursor.');
        await page.locator('form').evaluate(form => form.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true})));
        assert.equal(await page.evaluate(() => window.fixtureSends.length), 2, 'An unchanged message cannot be resubmitted.');
        assert.deepEqual(await page.evaluate(() => window.fixtureNotifications.map(item => item[1])), ['error', 'success']);
        await message.fill(originalMessage + '\nUpdated');
        assert.equal(await submit.isEnabled(), true, 'Editing the message permits another send.');
        assert.equal(await submit.getAttribute('title'), null);
        await message.fill(originalMessage);
        assert.equal(await submit.isDisabled(), true, 'Restoring the sent content also restores the duplicate guard.');
        await checkbox.nth(0).check();
        assert.equal(await submit.isEnabled(), true, 'Changing recipients permits another send.');
        await checkbox.nth(0).uncheck();
        assert.equal(await submit.isDisabled(), true, 'Restoring the sent recipients restores the duplicate guard.');
        await message.fill(originalMessage + '\nSecond message');
        await submit.click();
        await message.fill(originalMessage + '\nEdited while sending');
        assert.equal(await submit.isDisabled(), true, 'An edit during delivery does not allow concurrent sends.');
        await page.evaluate(() => window.fixtureResolve({ok: true, json: () => Promise.resolve({status: true, message: 'Fixture success'})}));
        await page.waitForFunction(() => !document.getElementById('omoPvSendInvitationsSubmit').disabled);
        assert.equal(await submit.getAttribute('title'), null, 'The guard remembers the submitted content, not later edits.');
        await submit.click();
        await page.evaluate(() => window.fixtureResolve({ok: true, json: () => Promise.resolve({status: true, message: 'Fixture success'})}));
        await page.waitForFunction(() => !document.querySelector('[data-pv-invitation-recipients]').disabled);
        assert.equal(await submit.isDisabled(), true, 'Sending the modified content locks the button again.');
        assert.equal(await submit.getAttribute('title'), 'Le message a déjà été envoyé');
        assert.deepEqual(errors, []);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, 'The recipient list fits a mobile screen.');
        console.log('pv_invitation_recipient_selection_browser_test: OK (selection, retry, duplicate guard, message and recipient edits, in-flight edits, tooltip, mobile)');
    } finally {
        await browser.close();
    }
})().catch(error => {console.error(error); process.exitCode = 1;});
