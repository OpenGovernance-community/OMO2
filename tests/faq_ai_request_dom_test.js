'use strict';

const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage();
        page.setDefaultTimeout(10000);
        const errors = [];
        const dialogs = [];
        const requests = [];
        let mode = 'faq';
        let failEscalation = true;
        page.on('pageerror', error => errors.push(error.message));
        page.on('dialog', dialog => { dialogs.push(dialog.message()); return dialog.dismiss(); });
        await page.route('https://faq.test/**', async route => {
            const url = new URL(route.request().url());
            if (url.pathname !== '/ajax/faq_question_request.php') return route.fulfill({body: '<html></html>', contentType: 'text/html'});
            assert.equal(url.searchParams.get('oid'), '7');
            assert.equal(url.searchParams.get('cid'), '8');
            assert.equal(url.searchParams.get('faq_scope'), 'descendants');
            const body = route.request().postData();
            requests.push(body);
            const escalate = body.includes('send_admin');
            if (escalate && failEscalation) {
                failEscalation = false;
                return route.fulfill({status: 502, json: {success: false, message: 'Envoi impossible'}});
            }
            if (escalate || mode === 'no_access') return route.fulfill({json: {success: true, status: 'submitted', message: 'Envoye a un administrateur'}});
            return route.fulfill({json: {success: true, status: 'answered', answer: 'Une reponse <img src=x onerror=alert(1)>\n\nSuite.', sources: mode === 'faq'
                ? [{type: 'faq', id: 42, title: 'Question <script>malicious</script>'}]
                : [{type: 'document', title: 'NOUVEAUTES.md'}]}});
        });
        await page.goto('https://faq.test/omo/c/8/');
        await page.setContent(`<div id="commonTopbarDrawer"><button type="button" data-common-drawer-dismiss>Fermer</button>
            <div id="faqPopupRoot" data-faq-oid="7" data-faq-cid="8" data-faq-scope="descendants">
            <div data-faq-detail-view hidden></div><div data-faq-list></div><button data-faq-ask>Poser la question</button>
            <div data-faq-request-view><form data-faq-request-form data-searching="Recherche..." data-sending="Envoi..." data-error="Erreur">
                <input name="question" required><textarea name="request_description" required></textarea>
                <div data-faq-request-search><button type="submit">Chercher</button></div>
                <div data-faq-request-message></div><p data-faq-ai-notice hidden>Reponse IA</p>
                <div data-faq-ai-draft hidden><div data-faq-ai-draft-text></div>
                    <small data-faq-ai-sources>Sources : <span data-faq-ai-source-list></span></small>
                    <div data-faq-ai-escalation><p>Ca ne repond pas a votre question ?</p><button type="button" data-faq-send-admin>Envoyer a un administrateur</button></div>
                </div>
            </form><div data-faq-request-sent hidden><p data-faq-sent-message></p><h5 data-faq-sent-question></h5><div data-faq-sent-description></div></div>
            </div></div></div>`);
        await page.evaluate(() => {
            window.notifications = []; window.omoNotify = (text, type) => window.notifications.push({text, type});
            document.querySelector('[data-common-drawer-dismiss]').addEventListener('click', () => { document.getElementById('commonTopbarDrawer').hidden = true; });
        });
        await page.addScriptTag({path: path.join(__dirname, '../common/drawer/subdrawer.js')});
        await page.addScriptTag({path: path.join(__dirname, '../omo/assets/js/faq.js')});
        await page.locator('[data-faq-ask]').click();
        const question = page.locator('[name="question"]');
        const description = page.locator('[name="request_description"]');
        const submit = page.locator('[type="submit"]');
        const send = page.locator('[data-faq-send-admin]');
        const answer = page.locator('[data-faq-ai-draft]');
        await question.fill('Original question');
        await description.fill('Original description');
        assert(await page.evaluate(() => window.commonDrawerHasUnsavedChanges(document.getElementById('commonTopbarDrawer'))), 'An unsent question remains protected by the shared close guard.');
        await submit.click();
        await answer.waitFor({state: 'visible'});
        assert.equal(requests.length, 1, 'Showing an answer makes no escalation request.');
        assert.equal(await page.locator('[data-faq-ai-draft-text] p').count(), 2);
        assert.equal(await page.locator('[data-faq-ai-draft] img, [data-faq-ai-source-list] script').count(), 0, 'Answers and references are plain text.');
        assert.equal(await page.locator('[data-faq-ai-source-list] a').getAttribute('href'), 'https://faq.test/omo/c/8/#|faq-42');
        assert.equal(await page.locator('[data-faq-ai-source-list] a').getAttribute('target'), '_blank');
        assert.equal(await submit.isVisible(), false);
        assert.deepEqual(await page.evaluate(() => window.notifications), [], 'No false notification about sending to an administrator.');
        await send.click();
        await page.waitForFunction(() => window.notifications.length === 1);
        assert.equal(await answer.isVisible(), true, 'An escalation error preserves the answer and original input for retry.');
        assert.equal(await question.inputValue(), 'Original question');
        assert(await page.evaluate(() => window.commonDrawerHasUnsavedChanges(document.getElementById('commonTopbarDrawer'))), 'Failed sending must retain unsaved-change protection.');
        await send.click();
        await page.waitForFunction(() => document.querySelector('form').dataset.submitted === '1');
        assert.equal(await send.isVisible(), false);
        assert.equal(await page.locator('[data-faq-request-form]').isVisible(), false, 'The editable form is replaced after sending.');
        assert.equal(await page.locator('[data-faq-sent-question]').textContent(), 'Original question');
        assert.equal(await page.locator('[data-faq-sent-description]').textContent(), 'Original description');
        assert.equal(await page.locator('[data-faq-sent-message]').textContent(), 'Envoye a un administrateur');
        assert.equal(await page.locator('[data-faq-request-view] input:visible, [data-faq-request-view] textarea:visible').count(), 0);
        assert.equal(await page.evaluate(() => window.commonDrawerHasUnsavedChanges(document.getElementById('commonTopbarDrawer'))), false, 'Sending marks the form saved.');
        assert.equal((await page.evaluate(() => window.notifications)).length, 1, 'Persistent confirmation replaces a duplicate success notification.');
        assert(requests[1].includes('Original question') && requests[1].includes('Original description') && requests[2].includes('send_admin'));
        await page.locator('form').evaluate(form => form.requestSubmit());
        assert.equal(requests.length, 3, 'A completed request cannot be submitted twice.');
        await page.locator('[data-common-drawer-dismiss]').click();
        assert.equal(await page.locator('#commonTopbarDrawer').isVisible(), false, 'The shared close guard allows closing after sending.');
        assert.deepEqual(dialogs, [], 'Closing a submitted question must show no discard confirmation.');

        mode = 'changelog';
        await page.locator('#commonTopbarDrawer').evaluate(drawer => { drawer.hidden = false; });
        await page.locator('[data-faq-ask]').click();
        await question.fill('Another question');
        await description.fill('New description');
        assert.equal(await answer.isVisible(), false, 'Editing the question invalidates its previous answer.');
        await submit.click();
        await answer.waitFor({state: 'visible'});
        assert.equal(await page.locator('[data-faq-ai-source-list]').textContent(), 'NOUVEAUTES.md');
        assert.equal(await page.locator('[data-faq-ai-source-list] a').count(), 0, 'The internal changelog reference is not clickable.');
        mode = 'no_access';
        await question.fill('Question without AI');
        await submit.click();
        await page.waitForFunction(() => document.querySelector('form').dataset.submitted === '1');
        assert.equal(await answer.isVisible(), false);
        assert.equal(await page.locator('[data-faq-request-message]').textContent(), '', 'Clear progress after submission.');
        assert.equal(await page.locator('[data-faq-sent-question]').textContent(), 'Question without AI');
        assert.equal(await page.locator('[data-faq-sent-message]').isVisible(), true, 'Direct sending also leaves a persistent in-popup confirmation.');
        assert.equal(await page.evaluate(() => {
            const event = new Event('beforeunload', {cancelable: true});
            window.dispatchEvent(event);
            return event.defaultPrevented;
        }), false, 'Page navigation after sending needs no unsaved-data warning.');
        assert.deepEqual(errors, []);
        console.log('PASS FAQ browser answer, citations, escalation retry, editing, no access and duplicate guard');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
