'use strict';

// Run with playwright on NODE_PATH, local Edge and the PHP 8.5 Docker app.
const assert = require('node:assert/strict');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {chromium} = require('playwright');
const root = path.join(__dirname, '..');
const markup = execFileSync('docker', ['compose', 'exec', '-T', '--user', 'www-data', 'app', 'php'], {
    cwd: root, encoding: 'utf8', input: `<?php
require 'shared_functions.php';
require 'common/translation_bundles.php';
require 'omo/translations.php';
require 'omo/api/decision/modules/export_common.php';
$escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$long = '<h4>Creer la regle</h4><ul><li><strong>Portee</strong> : Locale</li><li><strong>Regle</strong> : '
    . str_repeat('Texte de la regle avec tous ses details. ', 80) . '</li></ul>';
$cases = ['short' => 'Courte description', 'five' => "Un\nDeux\nTrois\nQuatre\nCinq",
    'long' => $long, 'responsive' => str_repeat('Description lisible. ', 16),
    'hidden' => $long, 'empty' => ''];
foreach ($cases as $name => $description) {
    echo '<section id="' . $name . '"' . ($name === 'hidden' ? ' hidden' : '') . '>'
        . omoDecisionRenderProposalSupplementHtml($description, null, $escape, 'omo-decision-vote__text') . '</section>';
}
echo '<template data-omo-proposal-description-template>' . omoDecisionRenderProposalDescription('', $escape) . '</template>';
echo '<div data-omo-proposal-html-field><textarea hidden data-omo-proposal-html-value>Editor content</textarea></div>';
`});
assert(!markup.includes('Fatal error'), markup);

(async () => {
    const browser = await chromium.launch({channel: 'msedge', headless: true});
    try {
        const page = await browser.newPage({viewport: {width: 1200, height: 900}});
        const errors = [];
        page.on('pageerror', error => errors.push(error.stack));
        await page.setContent('<form>' + markup + '</form>');
        await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
        await page.addStyleTag({path: path.join(root, 'common/choice/proposal-discussion.css')});
        await page.addStyleTag({content: 'body {font: 16px Arial; margin: 20px} section {margin-bottom: 16px}'});
        await page.evaluate(() => {
            document.querySelector('form').addEventListener('submit', event => { event.preventDefault(); window.submitted = true; });
        });
        await page.addScriptTag({path: path.join(root, 'common/choice/proposal-description.js')});
        const content = id => page.locator('#' + id + ' [data-omo-proposal-description-content]');
        const toggle = id => page.locator('#' + id + ' [data-omo-proposal-description-toggle]');
        await toggle('long').waitFor({state: 'visible'});
        assert.equal(await toggle('short').isVisible(), false);
        assert.equal(await toggle('five').isVisible(), false, 'Exactly five lines need no toggle.');
        assert.equal(await page.locator('#empty [data-omo-proposal-description]').count(), 0);
        assert.equal(await toggle('responsive').isVisible(), false);
        const fullHtml = await content('long').innerHTML();
        const collapsed = await content('long').evaluate(el => ({height: el.clientHeight, full: el.scrollHeight,
            limit: parseFloat(getComputedStyle(el).lineHeight) * 5}));
        assert(collapsed.height <= collapsed.limit + 1 && collapsed.full > collapsed.height, 'Rich HTML is clipped to five line heights.');
        assert.equal(await toggle('long').innerText(), 'Afficher la suite');
        const controlledId = await toggle('long').getAttribute('aria-controls');
        assert.equal(await content('long').getAttribute('id'), controlledId);
        await toggle('long').click();
        assert.equal(await toggle('long').getAttribute('aria-expanded'), 'true');
        assert.equal(await toggle('long').innerText(), 'Afficher moins');
        assert(await content('long').evaluate(el => el.clientHeight >= el.scrollHeight - 1));
        assert.equal(await content('long').innerHTML(), fullHtml, 'Expanding preserves all original rich HTML.');
        await toggle('long').focus();
        await page.keyboard.press('Space');
        assert.equal(await toggle('long').getAttribute('aria-expanded'), 'false');
        await page.keyboard.press('Enter');
        assert.equal(await toggle('long').getAttribute('aria-expanded'), 'true');
        assert.equal(await page.evaluate(() => !!window.submitted), false, 'Disclosure buttons never submit ballots.');
        await toggle('long').click();

        await page.setViewportSize({width: 390, height: 844});
        await toggle('responsive').waitFor({state: 'visible'});
        await page.setViewportSize({width: 1200, height: 900});
        await toggle('responsive').waitFor({state: 'hidden'});
        await page.locator('#hidden').evaluate(el => { el.hidden = false; });
        await toggle('hidden').waitFor({state: 'visible'});

        // Dynamically inserted descriptions use the same translated template as server-rendered cards.
        await page.evaluate(() => {
            const wrapper = window.omoProposalDescription.create();
            wrapper.querySelector('[data-omo-proposal-description-content]').innerHTML = '<p>' + 'Later content. '.repeat(300) + '</p>';
            document.querySelector('#empty').appendChild(wrapper);
        });
        await toggle('empty').waitFor({state: 'visible'});
        assert.equal(await toggle('empty').innerText(), 'Afficher la suite');
        await content('empty').evaluate(el => { el.textContent = 'Now short'; });
        await toggle('empty').waitFor({state: 'hidden'});
        // Changed content is observed even when a collapsed element keeps the same dimensions.
        await content('empty').evaluate(el => { el.innerHTML = '<p>' + 'Edited content. '.repeat(300) + '</p>'; });
        await toggle('empty').waitFor({state: 'visible'});
        const ids = await page.locator('[data-omo-proposal-description-content]').evaluateAll(nodes => nodes.map(node => node.id));
        assert.equal(new Set(ids).size, ids.length, 'Each toggle controls a unique description.');
        assert.equal(await page.locator('[data-omo-proposal-html-value]').inputValue(), 'Editor content', 'Editing fields stay intact.');
        await page.emulateMedia({media: 'print'});
        assert.equal(await toggle('long').isVisible(), false, 'Print hides disclosure controls.');
        assert(await content('long').evaluate(el => el.clientHeight >= el.scrollHeight - 1), 'Print includes the full description.');
        assert.deepEqual(errors, []);
        console.log('decision_proposal_description_collapse_test: OK');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
