(function () {
    'use strict';
    if (window.omoPropertyListConversion) return;
    let texts = {
        toAuthority: 'Les textes de cette liste vont etre convertis en nouveaux objets autorites. Est-ce ce que vous souhaitez ?',
        toText: 'Les autorites de cette liste seront remplacees par leurs noms. Les objets autorites et leurs instances de modele seront supprimes, avec leurs descriptions. Leurs regles seront conservees dans leur holon et detachees des autorites. Les sous-autorites seront conservees et detachees. Est-ce ce que vous souhaitez ?',
        scope: 'La conversion sera appliquee a l enregistrement, dans tous les holons qui utilisent cette definition de liste.',
        pending: 'Conversion confirmee pour le prochain enregistrement. Les valeurs ci-dessous sont conservees jusque-la. Revenez au type initial pour annuler.'
    };
    fetch('/common/jstranslation/property_list_conversion.php', { credentials: 'same-origin' })
        .then(function (response) { return response.ok ? response.json() : {}; })
        .then(function (payload) { texts = Object.assign(texts, payload); }).catch(function () {});

    window.omoPropertyListConversion = {
        change: function (draft, previousType, field, catalog) {
            const target = String(field.value || 'text');
            const source = String(draft.listConversionFrom || previousType || 'text');
            if (draft.listConversionFrom && target === source) {
                delete draft.listConversionFrom;
                return true;
            }
            if (draft.listConversionFrom && target !== previousType) {
                field.value = previousType;
                return false;
            }
            if (source === target || !['text', 'authority'].includes(source) || !['text', 'authority'].includes(target)) return true;
            if (!window.confirm(texts[target === 'authority' ? 'toAuthority' : 'toText'] + '\n\n' + texts.scope)) {
                field.value = previousType;
                return false;
            }
            if (Number(draft.id || 0) > 0) {
                draft.listConversionFrom = source;
            } else {
                let parts;
                try { parts = JSON.parse(draft.value || (Number(draft.formatId) === 7 ? '{"items":[]}' : '[]')); }
                catch (error) { parts = String(draft.value || '').split(/\r?\n|\|/).filter(Boolean); }
                const items = Number(draft.formatId) === 7 ? parts.items : parts;
                const converted = items.map(function (item) {
                    if (target === 'authority') return { label: String(item), isLocal: true };
                    const entry = (catalog || []).find(function (authority) { return Number(authority.id) === Number(item && item.id || item); });
                    return String(item && item.label || entry && entry.label || '');
                });
                if (Number(draft.formatId) === 7) parts.items = converted;
                draft.value = JSON.stringify(Number(draft.formatId) === 7 ? parts : converted);
            }
            return true;
        },
        sourceProperty: function (property) {
            return property.listConversionFrom ? Object.assign({}, property, { listItemType: property.listConversionFrom }) : property;
        },
        mount: function (row, property, valueSelector, formatSelector) {
            row.dataset.listItemType = String(property.listItemType || 'text');
            row.dataset.listConversionFrom = String(property.listConversionFrom || '');
            if (!property.listConversionFrom) return;
            row.dataset.conversionValue = String(property.value || '');
            const values = row.querySelector(valueSelector);
            if (values) {
                values.inert = true;
                values.querySelectorAll('input, textarea, select, button').forEach(function (field) { field.disabled = true; });
                const note = document.createElement('p');
                note.className = 'generic-description';
                note.textContent = texts.pending;
                values.before(note);
            }
            const format = row.querySelector(formatSelector);
            if (format) format.disabled = true;
        }
    };
})();
