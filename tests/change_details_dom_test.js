'use strict';

// Run with jsdom available on NODE_PATH; no application server or database writes.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');

const script = fs.readFileSync(path.join(__dirname, '../common/choice/change-details.js'), 'utf8');
const wordDiff = fs.readFileSync(path.join(__dirname, '../common/choice/word-diff.js'), 'utf8');
const encode = data => Buffer.from(JSON.stringify(data), 'utf8').toString('base64');
const action = (operation, title) => ({governanceAction: {
    type: 'rule.' + operation,
    before: operation === 'create' ? {} : {title: 'Ancien titre', description: 'Texte initial'},
    after: operation === 'delete' ? {} : {title, description: 'Texte changé'}
}});
const markup = data => '<details data-omo-change-details-payload="' + encode(data)
    + '"><summary>Détail</summary><div data-omo-change-details-container></div></details>';

(async function () {
    const dom = new JSDOM('<!doctype html><body>' + markup(action('create', 'Règle créée')) + '</body>', {runScripts:'outside-only'});
    const {window} = dom;
    const {document} = window;
    await new Promise(resolve => window.addEventListener('load', resolve, {once:true}));
    window.eval(wordDiff);
    window.eval(script);
    const scopeChanges = window.omoChoiceChangeDetails.governanceChanges({type: 'rule.update', before: {scope: 'local'}, after: {scope: 'circle'}}, []);
    assert.equal(scopeChanges.length, 1, 'Scope-only changes must remain visible to voters');
    assert.equal(scopeChanges[0].before, 'Locale');
    assert.equal(scopeChanges[0].after, 'Cercle');
    const settle = () => new Promise(resolve => window.setTimeout(resolve, 0));
    const first = document.querySelector('details');
    assert.equal(first.querySelectorAll('.omo-change-details__absence').length, 1, 'Late script must hydrate existing content');
    assert(first.textContent.includes('Règle créée'), 'UTF-8 payload must survive decoding');
    assert(first.textContent.includes('Cet élément n’existait pas.'));
    assert.equal(first.querySelectorAll('.is-before, .is-removed, .is-empty').length, 0, 'Creation has no red or empty before fields');

    document.body.insertAdjacentHTML('beforeend', markup(action('delete', '')));
    await settle();
    const deletion = document.querySelectorAll('details')[1];
    assert.equal(deletion.querySelectorAll('.omo-change-details__absence').length, 1, 'AJAX root itself must hydrate');
    assert(deletion.textContent.includes('Supprimé'));
    assert(deletion.textContent.includes('Ancien titre'));
    assert.equal(deletion.querySelectorAll('.is-after, .is-empty').length, 0, 'Deletion has no empty after fields');

    const fragment = document.createElement('section');
    fragment.innerHTML = markup(action('update', 'Nouveau titre'));
    document.body.appendChild(fragment);
    await settle();
    assert.equal(fragment.querySelectorAll('.omo-change-details__absence').length, 0, 'Modification keeps field-by-field comparison');
    assert(fragment.querySelector('.omo-change-details__removed'));
    assert(fragment.querySelector('.omo-change-details__added'));
    const comparison = fragment.querySelector('.omo-change-details__list');
    window.omoChoiceChangeDetails.hydrate(fragment);
    window.eval(script);
    assert.equal(fragment.querySelector('.omo-change-details__list'), comparison, 'Repeated initialization is idempotent');

    first.dataset.omoChangeDetailsPayload = encode(action('create', 'Titre actualisé'));
    await settle();
    assert(first.textContent.includes('Titre actualisé'), 'Payload changes update an existing accordion');
    assert(!first.textContent.includes('Règle créée'));
    first.querySelector('summary').click();
    assert(first.open, 'Native summary opens without a custom click handler');
    first.querySelector('summary').click();
    assert(!first.open);

    const indicatorChanges = window.omoChoiceChangeDetails.governanceChanges({
        type:'indicator.update',
        before:{name:'Suivi', IDuser_responsible:11, ethercalc_frequency:'daily', source_type:'ethercalc_cell'},
        after:{name:'Suivi', IDuser_responsible:12, ethercalc_frequency:'weekly', source_type:'ethercalc_cell'}
    }, [], {11:'Camille', 12:'Alex'});
    assert(indicatorChanges.some(change => change.label === 'Personne en charge' && change.before === 'Camille' && change.after === 'Alex'));
    assert(indicatorChanges.some(change => change.label === 'Fréquence de synchronisation EtherCalc' && change.before === 'Chaque jour' && change.after === 'Chaque semaine'));

    const holonChanges = window.omoChoiceChangeDetails.governanceChanges({
        type:'holon.update', before:{editor_payload:{properties:[
            {name:'Autorités',formatId:2,listItemType:'authority',value:'[17]',displayItems:[{id:17,label:'Comptabilité'}]},
            {name:'Projets',formatId:7,listItemType:'project',value:'{"before":"","items":[31],"after":""}',displayItems:[{id:31,label:'Ancien projet'}]}
        ]}}, after:{editor_payload:{properties:[
            {name:'Autorités',formatId:2,listItemType:'authority',value:'[18]',displayItems:[{id:18,label:'Finances'}]},
            {name:'Projets',formatId:7,listItemType:'project',value:'{"before":"","items":[32],"after":""}',displayItems:[{id:32,label:'Nouveau projet'}]}
        ]}}
    });
    assert(holonChanges.some(change => change.label === 'Autorités' && (String(change.before).includes('Comptabilité') || String(change.after).includes('Finances'))));
    assert(holonChanges.some(change => change.label === 'Projets' && (String(change.before).includes('Ancien projet') || String(change.after).includes('Nouveau projet'))));
    assert(!holonChanges.some(change => JSON.stringify(change).includes('31') || JSON.stringify(change).includes('32')));

    const root = document.createElement('section');
    root.dataset.governanceEditor = '';
    root.innerHTML = '<form data-governance-form><input name="oid" value="1"><input name="cid" value="2"><input data-governance-blueprint>'
        + '<button type="button" data-governance-intention-open aria-expanded="false">Ajouter une intention ou un contexte</button>'
        + '<label data-governance-intention-field hidden><textarea name="process_description"></textarea></label>'
        + '<div data-governance-proposals></div><p data-governance-feedback hidden></p></form>'
        + '<script type="application/json" data-governance-data></script>';
    root.querySelector('[data-governance-data]').textContent = JSON.stringify({editable:true, aiEnabled:true, blueprint:[{
        id:1, title:'Proposition du scrutin', description:'', actions:['create','update','delete'].map(operation => ({
            ...action(operation, 'Règle du scrutin').governanceAction, status:'pending'
        }))
    }]});
    document.body.appendChild(root);
    window.TextEncoder = TextEncoder;
    let sentSummary = null;
    let summaryNotification = null;
    let summaryRequestFails = false;
    window.commonNotify = (message, type) => { summaryNotification = {message, type}; };
    window.fetch = (url, options) => {
        if (url !== '/omo/api/decision/governance/summarize.php') throw new Error('Unexpected AI endpoint');
        sentSummary = JSON.parse(options.body.get('modifications'));
        if (summaryRequestFails) return Promise.resolve({ok:false, json:() => Promise.resolve({status:false, message:'Erreur de génération.'})});
        return Promise.resolve({ok:true, json:() => Promise.resolve({status:true, text:'La règle est renommée et complétée.'})});
    };
    window.eval(fs.readFileSync(path.join(__dirname, '../common/choice/governance-actions.js'), 'utf8'));
    await settle();
    assert.equal(root.querySelectorAll('details').length, 3, 'Late editor script initializes every proposal row');
    assert.equal(root.querySelectorAll('details details').length, 0, 'One accordion click opens the actual changes');
    assert.equal(root.querySelectorAll('.omo-change-details__absence').length, 2);
    const row = root.querySelector('details');
    row.querySelector('summary').click();
    assert(row.open);
    window.omoGovernanceEditorInit(document);
    assert.equal(root.querySelector('details'), row, 'Repeated editor initialization preserves accordion state');

    const intentionLink = root.querySelector('[data-governance-intention-open]');
    const intentionField = root.querySelector('[data-governance-intention-field]');
    intentionLink.click();
    assert(!intentionField.hidden, 'Optional intention becomes visible on demand');
    assert(intentionLink.hidden);

    const description = root.querySelector('[data-description]');
    const generateButton = root.querySelector('[data-generate-summary]');
    assert(generateButton, 'AI action is available to an eligible editor');
    generateButton.click();
    await settle();
    assert.equal(sentSummary.length, 3, 'Only the current proposal changes are sent to summarization');
    assert(sentSummary[1].fields.some(field => field.name === 'Titre' && field.before === 'Ancien titre' && field.after === 'Règle du scrutin'));
    assert.equal(description.value, 'La règle est renommée et complétée.');
    assert.equal(JSON.parse(root.querySelector('[data-governance-blueprint]').value)[0].description, description.value);
    assert.deepEqual(summaryNotification, {message:'Résumé ajouté à la description. Vous pouvez le modifier.', type:'success'});
    assert(root.querySelector('[data-summary-feedback]').hidden, 'Success is shown by the topbar, not inline');
    summaryRequestFails = true;
    generateButton.click();
    await settle();
    assert.deepEqual(summaryNotification, {message:'Erreur de génération.', type:'error'});
    assert(root.querySelector('[data-summary-feedback]').hidden, 'Generation errors are shown by the topbar, not inline');
    assert.equal(description.value, 'La règle est renommée et complétée.', 'A failed request does not erase the previous summary');
    description.value = 'Texte retouché par l’utilisateur.';
    description.dispatchEvent(new window.Event('input', {bubbles:true}));
    assert.equal(JSON.parse(root.querySelector('[data-governance-blueprint]').value)[0].description, description.value);

    const sharedCss = fs.readFileSync(path.join(__dirname, '../common/assets/components.css'), 'utf8');
    const anchoredRule = sharedCss.match(/\.generic-menu-panel--anchored\s*\{[^}]+\}/);
    assert(anchoredRule, 'Anchored menus must use a shared CSS primitive');
    const style = document.createElement('style');
    style.textContent = anchoredRule[0] + fs.readFileSync(path.join(__dirname, '../common/choice/governance-actions.css'), 'utf8');
    document.head.appendChild(style);
    const menu = root.querySelector('.omo-governance-action__menu');
    const toggle = menu.querySelector('.generic-menu-toggle');
    const panel = menu.querySelector('.generic-menu-panel');
    toggle.click();
    assert(!panel.hidden);
    assert.equal(toggle.getAttribute('aria-expanded'), 'true');
    assert.equal(window.getComputedStyle(panel).position, 'absolute', 'Open panel must not enlarge its container and shift the toggle');
    assert.equal(window.getComputedStyle(panel).right, '0px', 'Menu stays anchored to the right');
    assert(row.open, 'Menu click must not change the accordion state');
    toggle.click();
    assert(panel.hidden);
    const card = root.querySelector('.omo-governance-proposal');
    assert(card.querySelector('[data-add-action]').textContent.includes('Ajouter une modification'));
    assert.equal(toggle.getAttribute('aria-label'), 'Actions de la modification');
    assert(card.textContent.includes('Titre de la proposition'));
    assert(card.textContent.includes('Description de la proposition'));
    const removeProposal = card.querySelector('[data-remove-proposal]');
    assert.equal(removeProposal.parentElement, card.lastElementChild, 'Remove proposal belongs to the card footer');
    assert.equal(card.querySelector('[data-add-action]').parentElement.previousElementSibling, card.querySelector('[data-actions]'), 'Add modification follows the list of changes');
    removeProposal.click();
    assert.equal(root.querySelectorAll('.omo-governance-proposal').length, 0);
    assert.equal(root.querySelector('[data-governance-blueprint]').value, '[]');

    dom.window.close();
    console.log('change_details_dom_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
