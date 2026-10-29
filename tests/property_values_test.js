'use strict';
// Run with jsdom on NODE_PATH. Exercises the shared controls and both actual editor serializers.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const {JSDOM} = require('jsdom');

(async () => {
    const dom = new JSDOM('<main></main>', {runScripts:'outside-only'});
    const {window} = dom;
    window.fetch = () => Promise.resolve({ok:true, json:() => Promise.resolve({title:'Value', principle:'Principle', add:'Add value'})});
    let mounted = 0;
    window.omoSimpleHtmlField = {
        mount(host, options) {
            mounted++;
            let value = options.value;
            host.__omoSimpleHtmlField = {getValue:() => value, setValue:next => { value = next; options.onChange(); }};
            host.dataset.editorDisabled = String(options.disabled);
            return host.__omoSimpleHtmlField;
        },
        renderPreviewHtml:html => '<div>' + html + '</div>'
    };
    window.eval(fs.readFileSync('common/assets/property-values.js', 'utf8'));
    const api = window.omoPropertyValues;
    const main = window.document.querySelector('main');
    main.dataset.canEditValue = '1';
    main.dataset.formatId = '2';
    main.dataset.listItemType = 'value';
    main.innerHTML = api.render([{id:7, title:'Care <team>', principle:'<p>Listen.</p>'}], false);
    const flush = () => new Promise(resolve => window.setTimeout(resolve, 0));
    await flush();
    assert.equal(mounted, 1, 'Each principle uses the shared HTML editor once');
    assert.equal(main.querySelector('[data-value-title]').value, 'Care <team>');
    assert.equal(main.querySelector('team'), null, 'Titles are escaped');
    assert.equal(main.querySelector('[data-value-text="title"]').textContent, 'Value', 'Shared translations load once');
    main.querySelector('[data-value-action="add"]').click();
    await flush();
    let entries = main.querySelectorAll('[data-value-entry]');
    assert.equal(entries.length, 2);
    assert.equal(mounted, 2, 'Adding a value mounts only its new editor');
    entries[1].querySelector('[data-value-title]').value = 'Courage';
    entries[1].querySelector('[data-value-principle]').__omoSimpleHtmlField.setValue('<p>Try.</p>');
    entries[1].querySelector('[data-value-action="up"]').click();
    const items = JSON.parse(api.serialize(main, 2));
    assert.deepEqual(items.map(item => item.title), ['Courage', 'Care <team>']);
    assert.equal(items[1].id, 7, 'Reordering retains identity');
    assert.equal(items[0].principle, '<p>Try.</p>', 'Serialization reads the active HTML API');
    function loadFunction(file, name) {
        const source = fs.readFileSync(file, 'utf8');
        const start = source.lastIndexOf('function ' + name + '(');
        const end = source.indexOf('\nfunction ', start + 1);
        window.eval(source.slice(start, end < 0 ? undefined : end));
    }
    loadFunction('omo/api/holons/editor.js', 'serializePropertyValue');
    loadFunction('omo/api/parameters/holon-templates/templates.js', 'omoHolonTemplateSerializePropertyValue');
    assert.deepEqual(JSON.parse(window.serializePropertyValue(main)), items, 'Holon editor retains structured values');
    assert.deepEqual(JSON.parse(window.omoHolonTemplateSerializePropertyValue(main, 2, 'value')), items, 'Template editor retains structured values');
    for (const name of ['before', 'after']) {
        const host = window.document.createElement('div');
        host.dataset.omoCompositeHtml = name;
        host.__omoSimpleHtmlField = {getValue:() => '<p>' + name + '</p>'};
        main.append(host);
    }
    main.dataset.formatId = '7';
    const composite = {before:'<p>before</p>', items, after:'<p>after</p>'};
    assert.deepEqual(JSON.parse(window.serializePropertyValue(main)), composite, 'Holon composite lists retain value objects and HTML');
    assert.deepEqual(JSON.parse(window.omoHolonTemplateSerializePropertyValue(main, 7, 'value')), composite, 'Template composite lists retain value objects and HTML');
    const preview = api.previewStored(JSON.stringify(composite), 7);
    assert(preview.includes('<summary>Courage</summary>') && preview.includes('<p>Try.</p>') && preview.includes('<p>before</p>') && preview.includes('<p>after</p>'));
    main.querySelector('[data-value-entry] [data-value-action="remove"]').click();
    assert.equal(api.read(main).length, 1);
    main.innerHTML = api.render(items, true);
    await flush();
    assert(main.querySelector('[data-value-title]').disabled);
    assert.equal(main.querySelector('[data-value-action]'), null);
    assert.equal(main.querySelector('[data-value-principle]').dataset.editorDisabled, 'true');
    dom.window.close();
    console.log('property_values_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
