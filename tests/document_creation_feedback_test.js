'use strict';
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');

(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        let reply = {status: 422, body: {status: false, message: 'Invalid document scope.'}};
        await page.route('https://documents.test/**', route => {
            if (route.request().url().endsWith('/save.php')) {
                return route.fulfill({status: reply.status, contentType: 'application/json', body: JSON.stringify(reply.body)});
            }
            return route.fulfill({body: '<!doctype html><body></body>'});
        });
        await page.goto('https://documents.test/');
        await page.setContent('<form id="create" action="/save.php"><input name="title" value="Folder">'
            + '<select data-omo-document-type name="document_type"><option value="folder">Folder</option></select>'
            + '<div data-omo-document-editor-status hidden></div><button type="submit">Save</button></form>');
        await page.addScriptTag({path: path.join(__dirname, '..', 'common/document/html-ai-tools.js')});
        await page.addScriptTag({path: path.join(__dirname, '..', 'omo/api/documents/create.js')});
        await page.evaluate(() => {
            window.notices = [];
            window.commonNotify = (message, type) => window.notices.push({message, type});
            window.commonPageScripts['/omo/api/documents/create.js']({documentFormId: 'create', uiText: {}, editingDocumentId: 0});
        });
        for (const status of [422, 403, 200, 500]) {
            reply = {status, body: {status: false, message: 'Server explanation ' + status}};
            await page.getByRole('button', {name: 'Save', exact: true}).click();
            await page.waitForFunction(message => window.notices.at(-1)?.message === message, reply.body.message);
            assert.equal(await page.locator('[data-omo-document-editor-status]').isHidden(), true, 'Error appears only in the shared notification.');
            assert.equal(await page.evaluate(() => window.notices.at(-1).type), 'error');
            await page.waitForFunction(() => !document.querySelector('button').disabled);
        }
        reply = {status: 500, body: null};
        await page.getByRole('button', {name: 'Save', exact: true}).click();
        await page.waitForFunction(() => window.notices.at(-1)?.message.includes('Impossible'));
        await page.waitForFunction(() => !document.querySelector('button').disabled);
        reply = {status: 200, body: {status: true, id: 123}};
        await page.evaluate(() => {
            window.omoRefreshDocumentsPanel = async () => { window.refreshed = true; };
            window.omoCloseDocumentEditorDrawer = () => { window.editorClosed = true; };
        });
        await page.getByRole('button', {name: 'Save', exact: true}).click();
        await page.waitForFunction(() => window.editorClosed && window.refreshed && !document.querySelector('button').disabled);
        assert.equal(await page.evaluate(() => window.notices.length), 5, 'Successful save produces no error.');
        assert.deepEqual(errors, []);
        console.log('document_creation_feedback_test: OK (server errors, fallback, shared notifications, retry, success)');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
