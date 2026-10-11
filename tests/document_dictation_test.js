'use strict';

// Real memo toolbar/Summernote, with a fake microphone and transcription service.
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.join(__dirname, '..');

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage();
        const errors = [];
        const uploads = [];
        page.on('pageerror', error => errors.push(error.message));
        let reply = {status: 200, body: {status: true, text: ' dictated'}};
        await page.route('https://memo.test/**', async route => {
            if (route.request().url().endsWith('/html/transcribe.php')) {
                uploads.push(route.request().postDataBuffer().toString());
                await route.fulfill({status: reply.status, contentType: 'application/json', body: JSON.stringify(reply.body)});
            } else {
                await route.fulfill({contentType: 'text/html', body: '<!doctype html><html><head></head><body></body></html>'});
            }
        });
        await page.goto('https://memo.test/');
        await page.setContent('<form id="memo"><input name="title" value="Memo">'
            + '<div data-omo-document-editor-html></div><div data-omo-document-dictation-status hidden></div>'
            + '<button data-omo-document-editor-submit>Save</button></form><button id="outside">Outside</button>');
        await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
        await page.addStyleTag({url: 'https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-lite.min.css'});
        await page.addScriptTag({url: 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js'});
        await page.addScriptTag({path: path.join(root, 'omo/assets/js/simple-html-field.js')});
        await page.addScriptTag({path: path.join(root, 'omo/api/documents/create.js')});
        await page.evaluate(() => {
            window.notices = [];
            window.commonNotify = (message, type) => window.notices.push({message, type});
            window.stoppedTracks = 0;
            window.recorders = [];
            Object.defineProperty(navigator.mediaDevices, 'getUserMedia', {value: async () => ({
                getTracks: () => [{stop: () => window.stoppedTracks++}]
            })});
            window.MediaRecorder = class extends EventTarget {
                static isTypeSupported(type) { return type === 'audio/webm;codecs=opus'; }
                constructor(stream, options) {
                    super();
                    this.mimeType = options.mimeType;
                    this.state = 'inactive';
                    window.recorders.push(this);
                }
                start() { this.state = 'recording'; }
                requestData() { }
                stop() {
                    this.state = 'inactive';
                    queueMicrotask(() => {
                        const event = new Event('dataavailable');
                        event.data = new Blob(['fake recorded audio'], {type: this.mimeType});
                        this.dispatchEvent(event);
                        this.dispatchEvent(new Event('stop'));
                    });
                }
            };
            window.commonPageScripts['/omo/api/documents/create.js']({
                documentFormId: 'memo', aiToolsEnabled: true, textToolsEnabled: true, transcriptionEnabled: true,
                initialHtmlValue: '<p>Hello World</p>', uiText: {}, editingDocumentId: 0,
                organizationId: 12, holonId: 34, embeddableDocuments: []
            });
            window.field = document.querySelector('[data-omo-document-editor-html]').__omoSimpleHtmlField;
        });
        const toolbar = name => page.locator('[data-omo-toolbar-button-name="' + name + '"]');
        const surface = page.locator('[data-html-editor-surface]');
        const status = page.locator('[data-omo-document-dictation-status]');
        await surface.focus();
        await toolbar('omoDocumentAiToggle').waitFor();
        await toolbar('omoDocumentAiToggle').click();
        await page.evaluate(() => {
            const range = document.createRange();
            range.setStart(window.field.getEditableElement().querySelector('p').firstChild, 5);
            range.collapse(true);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            window.field.saveRange();
        });
        await toolbar('omoDocumentDictate').click();
        await page.waitForFunction(() => window.recorders.length === 1 && window.recorders[0].state === 'recording');
        await page.waitForTimeout(150);
        assert.equal(await page.locator('.note-editor').count(), 1, 'Starting dictation keeps the toolbar available.');
        assert.equal(await toolbar('omoDocumentTranscript').isEnabled(), true);
        assert.equal(await page.locator('[data-omo-document-editor-submit]').isDisabled(), true);

        await toolbar('omoDocumentTranscript').click();
        await page.waitForFunction(() => window.field.getPlainText().includes('dictated'));
        assert.equal(await page.evaluate(() => window.field.getPlainText()), 'Hellodictated World');
        assert.equal(await page.evaluate(() => window.stoppedTracks), 1);
        assert(uploads[0].includes('document-dictation.webm') && uploads[0].includes('fake recorded audio'));
        assert(uploads[0].includes('name="oid"\r\n\r\n12') && uploads[0].includes('name="cid"\r\n\r\n34'));
        assert.equal(await page.locator('[data-omo-document-editor-submit]').isDisabled(), false);

        // A transcript inserted into an empty memo must clear the hint without typing.
        await page.evaluate(() => window.field.setValue(''));
        assert.equal(await page.locator('.note-placeholder').isVisible(), true);
        await toolbar('omoDocumentDictate').click();
        await page.waitForFunction(() => window.recorders.at(-1).state === 'recording');
        await toolbar('omoDocumentTranscript').click();
        await page.waitForFunction(() => window.field.getPlainText() === 'dictated');
        assert.equal(await page.locator('.note-placeholder').isHidden(), true, 'Transcription hides the placeholder immediately.');

        // An intentional blur must not stop recording or prevent the async text insertion.
        await toolbar('omoDocumentDictate').click();
        await page.waitForFunction(() => window.recorders.at(-1).state === 'recording');
        await page.locator('#outside').focus();
        await page.locator('.note-editor').waitFor({state: 'detached'});
        assert.equal(await page.evaluate(() => window.recorders.at(-1).state), 'recording');
        await surface.focus();
        await toolbar('omoDocumentTranscript').click();
        await page.waitForFunction(() => window.stoppedTracks === 3 && window.notices.filter(notice => notice.type === 'success').length === 3);
        assert.equal((await page.evaluate(() => window.field.getPlainText())).match(/dictated/g).length, 2);

        for (const failure of [
            {status: 422, body: {status: false, message: 'Audio provider rejected this format.'}},
            {status: 503, body: {status: false, message: 'Configuration IA indisponible.'}},
            {status: 403, body: {status: false, message: 'Access denied.'}}
        ]) {
            reply = failure;
            await toolbar('omoDocumentDictate').click();
            await page.waitForFunction(() => window.recorders.at(-1).state === 'recording');
            await toolbar('omoDocumentTranscript').click();
            await page.waitForFunction(message => window.notices.some(notice => notice.message === message && notice.type === 'error'), failure.body.message);
            assert.equal(await status.isHidden(), true, 'Finished progress clears when the notification is shown.');
            assert.equal(await page.locator('[data-omo-document-editor-submit]').isDisabled(), false);
        }
        await toolbar('omoDocumentDictate').click();
        await page.waitForFunction(() => window.recorders.at(-1).state === 'recording');
        await toolbar('omoDocumentDictationCancel').click();
        await page.waitForFunction(() => window.recorders.at(-1).state === 'inactive');
        assert.equal(uploads.length, 6, 'Cancelling does not send audio.');
        assert.deepEqual(errors, []);
        console.log('Document dictation tests passed.');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
