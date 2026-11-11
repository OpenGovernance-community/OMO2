'use strict';

// Real PV mount, Summernote and shared AI controller; microphone/services are mocked.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');
const rootPath = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(rootPath, 'omo/api/documents/pv/editor.js'), 'utf8');
const functions = ['mountEditableCard', 'syncPointDirtyUi', 'pointWantsLock', 'ensurePointLock', 'savePoint'].map(name => {
    const start = source.indexOf('    function ' + name + '(');
    const end = source.indexOf('\n    function ', start + 1);
    assert(start >= 0 && end > start, name);
    return source.slice(start, end);
}).join('\n');
const translations = Object.fromEntries([...fs.readFileSync(path.join(rootPath, 'common/jstranslation/html_ai_tools.php'), 'utf8')
    .matchAll(/    '([^']+)' => \['text' => '([^']+)'/g)].map(match => [match[1], match[2]]));

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage();
        const errors = [], requests = [];
        let pending, fail = false;
        const waitForPending = async () => {
            for (let attempt = 0; attempt < 500 && !pending; attempt++) await new Promise(resolve => setTimeout(resolve, 10));
            assert(pending, 'The summary request must reach the service.');
        };
        page.on('pageerror', error => errors.push(error.message));
        await page.route('https://pv-ai.test/**', async route => {
            const url = route.request().url();
            if (url.endsWith('html_ai_tools.php')) {
                return route.fulfill({contentType: 'application/json', body: JSON.stringify(translations)});
            }
            if (/\/html\/(summarize|rewrite|transcribe)\.php$/.test(url)) {
                requests.push({url, body: route.request().postDataBuffer().toString()});
                if (url.endsWith('summarize.php')) await new Promise(resolve => { pending = resolve; });
                return route.fulfill({status: fail ? 403 : 200, contentType: 'application/json', body: JSON.stringify(fail
                    ? {status: false, message: 'AI access denied'}
                    : {status: true, text: url.endsWith('transcribe.php') ? 'Dictated text' : (url.endsWith('rewrite.php') ? 'Rewritten text' : '<Summary>')})});
            }
            return route.fulfill({contentType: 'text/html', body: '<!doctype html>'});
        });
        await page.goto('https://pv-ai.test/');
        await page.setContent('<main id="pv">' + [1, 2].map(id => '<section data-omo-pv-point-card="' + id + '" data-omo-pv-point-editable="' + (id === 1 ? '1' : '0') + '">'
            + '<input data-omo-pv-point-title="' + id + '" value="Point ' + id + '">'
            + '<textarea data-omo-pv-point-content-source="' + id + '">&lt;p&gt;First sentence. Second sentence.&lt;/p&gt;</textarea>'
            + '<div data-omo-pv-point-editor-host="' + id + '"></div>'
            + '<button data-omo-pv-point-save="' + id + '">Save</button><button data-omo-pv-point-cancel>Cancel</button></section>').join('')
            + '</main><button id="outside">Outside</button>');
        await page.addStyleTag({path: path.join(rootPath, 'common/assets/components.css')});
        await page.addStyleTag({url: 'https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-lite.min.css'});
        await page.addScriptTag({url: 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js'});
        await page.addScriptTag({path: path.join(rootPath, 'omo/assets/js/simple-html-field.js')});
        await page.addScriptTag({path: path.join(rootPath, 'common/document/html-ai-tools.js')});
        await page.evaluate(functions => {
            window.notices = [];
            window.commonNotify = (message, type) => window.notices.push({message, type});
            window.tracksStopped = 0;
            window.mediaRequests = [];
            Object.defineProperty(navigator.mediaDevices, 'getUserMedia', {value: () => new Promise(resolve => {
                window.mediaRequests.push(() => resolve({getTracks: () => [{stop: () => window.tracksStopped++}]}));
            })});
            window.MediaRecorder = class extends EventTarget {
                static isTypeSupported(type) { return type === 'audio/webm'; }
                constructor(stream, options) { super(); this.mimeType = options.mimeType; this.state = 'inactive'; }
                start() { this.state = 'recording'; }
                stop() {
                    this.state = 'inactive';
                    queueMicrotask(() => {
                        const data = new Event('dataavailable'); data.data = new Blob(['audio'], {type: this.mimeType});
                        this.dispatchEvent(data); this.dispatchEvent(new Event('stop'));
                    });
                }
            };
            const root = document.getElementById('pv'), pageConfig = {textToolsEnabled: true, transcriptionEnabled: true};
            const organizationId = 12, editorToken = 'test', editorClientUi = {}, documentEmbedUi = {};
            const canEmbedDocuments = false, canEmbedDecisions = false, canEmbedProjects = false, canEmbedChecklists = false, canEmbedEvents = false, canEmbedIndicators = false;
            const indicatorValueUi = {}, savingLabel = 'Saving', saveLabel = 'Save', savedLabel = 'Saved';
            const activeLockPointIds = new Set(), pendingLockPointIds = new Set(), pendingPointLockRequests = new Map(), pendingUnlockPointIds = new Set();
            const locallyEngagedPointIds = new Set(), yieldingTakeoverPointIds = new Set(), recoveredTakeoverDraftPointIds = new Set();
            const preMountEditorDrafts = new Map(), preMountEditorFocusPointIds = new Set(), saveOnPointLeaveIds = new Set(), currentPointPayloads = {};
            const captureDraftState = id => ({[id]: {}}), captureFocusedEditor = () => null;
            const ensureHtmlFieldLibrary = callback => callback(), suppressPointDirtyDuring = (id, callback) => callback(), isPointDirtySuppressed = () => false;
            const markPointDirty = id => { root.querySelector('[data-omo-pv-point-card="' + id + '"]').setAttribute('data-omo-pv-point-dirty', '1'); syncPointDirtyUi(id); };
            const refreshPvIndicatorEmbedSnapshots = () => {}, refreshPvProjectEmbedReviews = () => {}, refreshPvChecklistEmbedReviews = () => {};
            const setFocusedPoint = () => {}, mergeKnownPointSignature = () => {}, restorePointFocusAfterLock = () => {}, replacePointHtml = () => {};
            const releasePointLock = id => { activeLockPointIds.delete(id); };
            window.lockRequests = [];
            const postPointAction = (action, id) => new Promise(resolve => window.lockRequests.push({action, id, resolve}));
            eval(functions + '\nwindow.mountCard = mountEditableCard; window.attemptSave = () => savePoint(1);');
            window.mountCard(root.querySelector('[data-omo-pv-point-card="1"]'));
            window.mountCard(root.querySelector('[data-omo-pv-point-card="2"]'));
            window.field = root.querySelector('[data-omo-pv-point-editor-host="1"]').__omoPvPointField;
            window.selectText = text => {
                const editable = window.field.getEditableElement();
                const walker = document.createTreeWalker(editable, NodeFilter.SHOW_TEXT);
                let node;
                while ((node = walker.nextNode())) {
                    const offset = node.textContent.indexOf(text);
                    if (offset < 0) continue;
                    const range = document.createRange(); range.setStart(node, offset); range.setEnd(node, offset + text.length);
                    window.getSelection().removeAllRanges(); window.getSelection().addRange(range); window.field.saveRange(); return;
                }
                throw Error('Selection not found: ' + text);
            };
        }, functions);
        const button = name => page.locator('[data-omo-toolbar-button-name="omoDocument' + name + '"]');
        const card = page.locator('[data-omo-pv-point-card="1"]');
        await card.locator('[data-html-editor-surface]').focus();
        await button('AiToggle').click();
        assert.equal(await page.locator('[data-omo-pv-point-card="2"] .omo-simple-html-field').count(), 0, 'Read-only points have no editor or AI toolbar.');
        await page.evaluate(() => window.selectText('First sentence.'));
        await button('Summarize').click();
        assert.equal(requests.length, 0, 'An AI request waits for the point lock already requested by focus.');
        await page.evaluate(() => { window.lockRequests.forEach(item => item.resolve({})); });
        await page.waitForFunction(() => document.querySelector('[data-omo-pv-point-card="1"]').hasAttribute('data-omo-pv-point-ai-busy'));
        await waitForPending();
        assert.equal(await card.locator('[data-omo-pv-point-save]').isDisabled(), true);
        assert.equal(await page.evaluate(() => window.attemptSave()), false, 'Saving cannot discard an in-flight AI result.');
        await page.locator('#outside').focus();
        pending(); pending = null;
        await page.waitForFunction(() => window.field.getPlainText().includes('<Summary>'));
        assert.equal(await page.evaluate(() => window.field.getPlainText()), '<Summary> Second sentence.', 'Only the captured selection is replaced, even after blur.');
        assert.equal(await card.locator('[data-omo-pv-point-save]').isDisabled(), false);
        assert(requests[0].body.includes('First sentence.') && requests[0].body.includes('Second sentence.'), 'The service receives the selection and point context.');
        assert.equal(await card.locator('summary').count(), 0, 'AI output is inserted as text, not executable HTML.');

        await button('Dictate').click();
        await page.waitForFunction(() => window.mediaRequests.length === 1);
        await page.evaluate(() => window.mediaRequests.shift()());
        await button('Transcript').click();
        await page.waitForFunction(() => window.field.getPlainText().includes('Dictated text'));
        assert.equal(await page.evaluate(() => window.tracksStopped), 1);

        await page.evaluate(() => window.selectText('Second sentence.'));
        fail = true;
        await button('Summarize').click();
        await waitForPending();
        pending(); pending = null;
        await page.waitForFunction(() => window.notices.some(item => item.message === 'AI access denied'));
        assert((await page.evaluate(() => window.field.getPlainText())).includes('Second sentence.'), 'Provider/permission failures preserve the original text.');
        assert.equal(await card.locator('[data-omo-pv-point-ai-status]').isHidden(), true);
        fail = false;
        await page.evaluate(() => window.selectText('Second sentence.'));
        await button('Rewrite').click();
        await page.waitForFunction(() => window.field.getPlainText().includes('Rewritten text'));

        await page.evaluate(() => window.selectText('Rewritten text'));
        await button('Summarize').click();
        await waitForPending();
        await button('DictationCancel').click();
        pending(); pending = null;
        await page.waitForFunction(() => !document.querySelector('[data-omo-pv-point-card="1"]').hasAttribute('data-omo-pv-point-ai-busy'));
        assert((await page.evaluate(() => window.field.getPlainText())).includes('Rewritten text'), 'Cancelling a summary preserves the original selection.');

        await button('Dictate').click();
        await page.waitForFunction(() => window.mediaRequests.length === 1);
        await page.evaluate(() => document.getElementById('pv').remove());
        await page.evaluate(() => window.mediaRequests.shift()());
        await page.waitForFunction(() => window.tracksStopped === 2);
        assert.equal(requests.length, 5, 'Closing a PV while microphone permission is pending sends no audio.');
        assert.deepEqual(errors, []);
        console.log('pv_html_ai_tools_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
