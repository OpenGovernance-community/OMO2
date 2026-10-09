'use strict';

// Real Chromium MediaRecorder: the upload must be a finalized, seekable WebM.
// No microphone, API key or paid request: an oscillator supplies the test audio.
const assert = require('node:assert/strict');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.join(__dirname, '..');

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('https://memo.test/**', route => route.fulfill({contentType: 'text/html', body: '<!doctype html>'}));
        await page.goto('https://memo.test/');
        await page.setContent('<form id="memo"><div data-omo-document-editor-html></div>'
            + '<div data-omo-document-dictation-status hidden></div></form>');
        await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
        await page.addStyleTag({url: 'https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-lite.min.css'});
        await page.addScriptTag({url: 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js'});
        await page.addScriptTag({path: path.join(root, 'omo/assets/js/simple-html-field.js')});
        await page.addScriptTag({path: path.join(root, 'omo/api/documents/create.js')});
        await page.evaluate(() => {
            window.audioContext = new AudioContext();
            window.streams = [];
            window.notices = [];
            window.commonNotify = (message, type) => window.notices.push({message, type});
            Object.defineProperty(navigator.mediaDevices, 'getUserMedia', {value: async () => {
                await window.audioContext.resume();
                const oscillator = window.audioContext.createOscillator();
                const destination = window.audioContext.createMediaStreamDestination();
                oscillator.connect(destination);
                oscillator.start();
                const track = destination.stream.getAudioTracks()[0];
                const stop = track.stop.bind(track);
                track.stop = () => { oscillator.stop(); stop(); };
                window.streams.push(destination.stream);
                return destination.stream;
            }});
            const nativeFetch = window.fetch;
            window.fetch = async (url, options) => {
                if (url !== '/omo/api/documents/html/transcribe.php') return nativeFetch(url, options);
                window.upload = options.body.get('audio');
                return new Response(JSON.stringify({status: true, text: 'Recorded audio'}), {headers: {'Content-Type': 'application/json'}});
            };
            window.commonPageScripts['/omo/api/documents/create.js']({
                documentFormId: 'memo', aiToolsEnabled: true, textToolsEnabled: false, transcriptionEnabled: true,
                initialHtmlValue: '', uiText: {}, editingDocumentId: 0, organizationId: 12, holonId: 34
            });
        });
        const button = name => page.locator('[data-omo-toolbar-button-name="' + name + '"]');
        await page.locator('[data-html-editor-surface]').focus();
        await button('omoDocumentAiToggle').click();
        for (let recording = 0; recording < 2; recording++) {
            await page.evaluate(() => { window.upload = null; });
            await button('omoDocumentDictate').click();
            await button('omoDocumentTranscript').waitFor({state: 'visible'});
            await page.waitForFunction(() => !document.querySelector('[data-omo-toolbar-button-name="omoDocumentTranscript"]').disabled);
            await page.waitForTimeout(650);
            await button('omoDocumentTranscript').click();
            await page.waitForFunction(() => !!window.upload);
            const audio = await page.evaluate(async () => {
                const file = window.upload;
                const bytes = new Uint8Array(await file.arrayBuffer());
                const element = document.createElement('audio');
                const url = URL.createObjectURL(file);
                try {
                    const duration = await new Promise((resolve, reject) => {
                        element.onloadedmetadata = () => resolve(element.duration);
                        element.onerror = () => reject(new Error('The recorded upload cannot be played.'));
                        element.src = url;
                    });
                    const decoded = await window.audioContext.decodeAudioData(bytes.buffer.slice(0));
                    const samples = decoded.getChannelData(0);
                    return {
                        name: file.name, type: file.type, size: file.size, duration,
                        decodedDuration: decoded.duration,
                        peak: samples.reduce((peak, sample) => Math.max(peak, Math.abs(sample)), 0),
                        magic: Array.from(bytes.slice(0, 4)),
                        tracksStopped: window.streams.every(stream => stream.getTracks().every(track => track.readyState === 'ended'))
                    };
                } finally {
                    element.removeAttribute('src');
                    element.load();
                    URL.revokeObjectURL(url);
                }
            });
            assert.equal(audio.name, 'document-dictation.webm');
            assert(audio.type.startsWith('audio/webm') && audio.size > 1000);
            assert.deepEqual(audio.magic, [0x1a, 0x45, 0xdf, 0xa3], 'The upload contains an actual WebM container.');
            assert(Number.isFinite(audio.duration) && audio.duration > 0.4 && audio.duration < 5,
                'The WebM must expose its final duration; flushing before stop leaves an unfinalized stream.');
            assert(Math.abs(audio.duration - audio.decodedDuration) < 0.15, 'Header duration matches the decoded samples.');
            assert(audio.peak > 0.1, 'The upload contains real encoded audio samples.');
            assert(audio.tracksStopped, 'The microphone stream is released after the final data.');
        }
        assert.deepEqual(errors, []);
        console.log('PASS real dictation audio: finalized WebM duration, decoding, repeated recordings and track cleanup');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
