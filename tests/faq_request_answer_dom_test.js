'use strict';
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.join(__dirname, '..');

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage();
        page.setDefaultTimeout(10000);
        const errors = [], requests = [], saves = [];
        page.on('pageerror', error => errors.push(error.message));
        const detail = `<div data-faq-form-shell><form id="formulaire-edit" action="/ajax/faq_update.php?id=42">
            <input name="question" value="Question"><textarea name="answer">Short answer</textarea>
            <label for="detail">Detailed answer</label><textarea class="summernote" id="detail" name="detail">&lt;p&gt;Initial &lt;strong&gt;HTML&lt;/strong&gt;&lt;/p&gt;</textarea>
            <input type="hidden" name="faq_answer_request" value="1">
            <label><input type="checkbox" name="faq_publish_answer" value="1" data-faq-publish-answer> Enregistrer dans la FAQ</label>
            <div data-faq-request-publication hidden><fieldset data-faq-scope-section><div data-faq-scope-fields>
                <input type="hidden" name="IDorganization" value="7"><input type="hidden" name="IDholon"><input type="hidden" name="IDparcours">
                <select data-faq-scope-kind><option value="organization">Organization</option><option value="generic">Generic</option></select>
                <div data-faq-scope-organization-shell><select data-faq-scope-organization><option value="7">One</option><option value="9">Other administered organization</option></select></div>
                <select name="IDapplication"><option value="">All apps</option><option value="2">App</option></select>
            </div></fieldset></div>
            </form><button type="button" data-faq-save data-faq-answer-save data-reply-label="Repondre" data-publish-label="Repondre et enregistrer">Repondre</button></div>`;
        await page.route('**/*', async route => {
            const url = new URL(route.request().url());
            if (url.hostname === 'cdnjs.cloudflare.com') {
                const file = url.pathname.split('/').slice(-1)[0];
                const modulePath = /\.(woff2?|ttf)$/.test(file) ? 'summernote/dist/font/' + file
                    : file.startsWith('summernote-fr') ? 'summernote/dist/lang/' + file : 'summernote/dist/' + file;
                return route.fulfill({path: require.resolve(modulePath)});
            }
            if (url.pathname === '/ajax/faq_detail.php') return route.fulfill({body: detail, contentType: 'text/html'});
            if (url.pathname === '/ajax/faq_update.php') {
                saves.push(route.request().postData());
                return route.fulfill({json: {status: false, message: 'Synthetic save response'}});
            }
            if (['/common/assets/admin-edit.js', '/omo/assets/js/simple-html-field.js'].includes(url.pathname)) {
                requests.push(url.pathname);
                return route.fulfill({path: path.join(root, url.pathname.slice(1)), contentType: 'text/javascript'});
            }
            return route.fulfill({body: '<html></html>', contentType: 'text/html'});
        });
        await page.goto('https://faq.test/omo/');
        await page.setContent('<div id="faqPopupRoot" data-faq-oid="7" data-faq-initial-id="42" data-faq-list-loaded="0"><div data-faq-detail-view></div></div>');
        await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
        await page.addScriptTag({path: require.resolve('jquery/dist/jquery.js')});
        await page.evaluate(() => { window.notifications = []; window.omoNotify = (message, type) => window.notifications.push({message, type}); });
        await page.addScriptTag({path: path.join(root, 'omo/assets/js/faq.js')});
        const surface = page.locator('[data-html-editor-surface]');
        await surface.waitFor({state: 'visible'});
        assert.deepEqual(requests, ['/common/assets/admin-edit.js', '/omo/assets/js/simple-html-field.js'], 'A direct email link loads the shared HTML adapter explicitly.');
        assert.equal(await page.locator('textarea[name="detail"]').isVisible(), false);
        assert.equal(await surface.innerHTML(), '<p>Initial <strong>HTML</strong></p>');
        await surface.click();
        await page.locator('.note-editable').waitFor({state: 'visible'});
        assert.equal(await page.locator('.note-toolbar').isVisible(), true, 'Focusing the detailed response activates real Summernote.');
        await page.locator('.note-editable').fill('Updated rich answer');
        const save = page.locator('[data-faq-answer-save]');
        assert.equal(await save.textContent(), 'Repondre');
        assert.equal(await page.locator('[data-faq-request-publication]').isVisible(), false);
        await save.click();
        await page.waitForFunction(() => window.notifications.length === 1);
        assert(saves[0].includes('Updated rich answer') && saves[0].includes('faq_answer_request') && !saves[0].includes('faq_publish_answer'), 'Private replies synchronize HTML and omit publication intent.');
        const checkbox = page.locator('[data-faq-publish-answer]');
        await checkbox.check();
        assert.equal(await save.textContent(), 'Repondre et enregistrer');
        assert.equal(await page.locator('[data-faq-request-publication]').isVisible(), true);
        assert.equal(await page.locator('form [data-faq-request-publication] [data-faq-scope-fields]').count(), 1, 'Scope controls stay inside their optional publication section.');
        await page.locator('[data-faq-scope-organization]').selectOption('9');
        await page.locator('[name="IDapplication"]').selectOption('2');
        await save.click();
        await page.waitForFunction(() => window.notifications.length === 2);
        assert(saves[1].includes('faq_publish_answer') && saves[1].includes('name="IDorganization"\r\n\r\n9')
            && saves[1].includes('name="IDapplication"\r\n\r\n2'), 'Publish intent, chosen organization and app are submitted together.');
        await checkbox.uncheck();
        assert.equal(await save.textContent(), 'Repondre');
        assert.equal(await page.locator('[data-faq-request-publication]').isVisible(), false);
        await page.locator('form').evaluate(form => form.requestSubmit());
        await page.waitForFunction(() => window.notifications.length === 3);
        assert.equal(saves.length, 3, 'Keyboard form submission uses the FAQ AJAX response flow.');
        assert.deepEqual(errors, []);
        console.log('PASS FAQ reply browser: lazy HTML adapter, real Summernote, private response and optional publication');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
