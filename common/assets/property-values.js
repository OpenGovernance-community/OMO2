(function () {
    'use strict';
    if (window.omoPropertyValues) return;
    const escape = value => String(value == null ? '' : value).replace(/[&<>"']/g, char => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[char]));
    let texts = {title:'Valeur', principle:'Principe', add:'Ajouter une valeur', remove:'Retirer', up:'Monter', down:'Descendre'};
    fetch('/common/jstranslation/property_values.php', {credentials:'same-origin'}).then(response => response.ok ? response.json() : {}).then(payload => {
        texts = Object.assign(texts, payload);
        document.querySelectorAll('[data-value-text]').forEach(node => { node.textContent = texts[node.dataset.valueText] || ''; });
    }).catch(() => {});
    function row(item, disabled) {
        item = item && typeof item === 'object' ? item : {title:String(item || '')};
        const off = disabled ? ' disabled' : '';
        return '<div class="generic-soft-panel generic-form-stack" data-value-entry data-value-id="' + Number(item.id || 0) + '">'
            + '<label class="generic-form-field"><span class="generic-form-label" data-value-text="title">' + escape(texts.title) + '</span><input class="generic-form-control" data-value-title required maxlength="255" value="' + escape(item.title || '') + '"' + off + '></label>'
            + '<div class="generic-form-field"><span class="generic-form-label" data-value-text="principle">' + escape(texts.principle) + '</span><div data-value-principle data-initial="' + escape(item.principle || '') + '" data-disabled="' + (disabled ? '1' : '0') + '"></div></div>'
            + (disabled ? '' : '<div class="generic-action-row">' + [['up','&#8593;'],['down','&#8595;'],['remove','&times;']].map(action => '<button type="button" class="generic-action-button generic-action-button--secondary" data-value-action="' + action[0] + '" aria-label="' + escape(texts[action[0]]) + '">' + action[1] + '</button>').join('') + '</div>') + '</div>';
    }
    function mount(scope) {
        scope.querySelectorAll('[data-value-principle]').forEach(host => {
            if (host.__omoSimpleHtmlField || !window.omoSimpleHtmlField) return;
            window.omoSimpleHtmlField.mount(host, {
                value:host.dataset.initial || '', disabled:host.dataset.disabled === '1',
                onChange:() => host.dispatchEvent(new Event('input', {bubbles:true}))
            });
        });
    }
    function read(scope) {
        return Array.from(scope.querySelectorAll('[data-value-entry]')).map(entry => {
            const host = entry.querySelector('[data-value-principle]');
            return {id:Number(entry.dataset.valueId || 0), title:entry.querySelector('[data-value-title]').value.trim(), principle:host.__omoSimpleHtmlField ? String(host.__omoSimpleHtmlField.getValue() || '') : host.dataset.initial || ''};
        }).filter(item => item.id || item.title || item.principle);
    }
    window.omoPropertyValues = {
        render(items, disabled) {
            return '<div class="generic-form-stack" data-value-list><div class="generic-form-stack" data-value-items>' + (items || []).map(item => row(item, disabled)).join('') + '</div>'
                + (disabled ? '' : '<button type="button" class="generic-action-button generic-action-button--secondary" data-value-action="add" data-value-text="add">' + escape(texts.add) + '</button>') + '</div>';
        },
        read,
        previewStored(raw, formatId) {
            let parts;
            try { parts = JSON.parse(raw || '[]'); } catch (error) { return ''; }
            if (Number(formatId) === 2) return this.preview(Array.isArray(parts) ? parts : []);
            const html = value => window.omoSimpleHtmlField ? window.omoSimpleHtmlField.renderPreviewHtml(value || '') : escape(value || '');
            return html(parts.before) + this.preview(parts.items || []) + html(parts.after);
        },
        serialize(scope, formatId) {
            const items = read(scope);
            if (Number(formatId) === 2) return items.length ? JSON.stringify(items) : '';
            const html = name => {
                const host = scope.querySelector('[data-omo-composite-html="' + name + '"]');
                return host && host.__omoSimpleHtmlField ? String(host.__omoSimpleHtmlField.getValue() || '') : '';
            };
            return JSON.stringify({before:html('before'), items, after:html('after')});
        },
        preview(items) {
            return (items || []).map(item => '<details class="generic-accordion generic-accordion--inset"><summary>' + escape(item.title || '') + '</summary>'
                + (window.omoSimpleHtmlField ? window.omoSimpleHtmlField.renderPreviewHtml(item.principle || '', 'generic-section generic-section--plain') : escape(item.principle || '')) + '</details>').join('');
        }
    };
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-value-action]');
        if (!button) return;
        const list = button.closest('[data-value-list]');
        if (!list) return;
        const entry = button.closest('[data-value-entry]');
        const action = button.dataset.valueAction;
        if (action === 'add') list.querySelector('[data-value-items]').insertAdjacentHTML('beforeend', row({}, false));
        else if (action === 'remove') entry.remove();
        else if (action === 'up' && entry.previousElementSibling) entry.parentNode.insertBefore(entry, entry.previousElementSibling);
        else if (action === 'down' && entry.nextElementSibling) entry.parentNode.insertBefore(entry.nextElementSibling, entry);
        mount(list);
        list.dispatchEvent(new Event('input', {bubbles:true}));
        list.dispatchEvent(new Event('change', {bubbles:true}));
    });
    new MutationObserver(records => {
        records.forEach(record => record.addedNodes.forEach(node => { if (node.nodeType === 1) mount(node.parentElement || node); }));
    }).observe(document.documentElement, {childList:true, subtree:true});
    mount(document);
}());
