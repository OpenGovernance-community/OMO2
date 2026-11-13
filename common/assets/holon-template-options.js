(function () {
    'use strict';

    window.omoHolonTemplateOptions = function (select, templates, settings) {
        const translate = window.omoUiText || function (key, fallback) { return fallback; };
        const groups = [
            { label: translate('parameters.holon_templates.parent_group.base', 'Modèles de base · nom libre'), templates: [] },
            { label: translate('parameters.holon_templates.parent_group.inherited', 'Modèles dérivés · nom libre'), templates: [] },
            { label: translate('parameters.holon_templates.parent_group.base_locked', 'Modèles de base · nom fixe'), templates: [] },
            { label: translate('parameters.holon_templates.parent_group.inherited_locked', 'Modèles dérivés · nom fixe'), templates: [] }
        ];

        templates.forEach(function (template) {
            if (Number(template.id) === Number(settings.currentTemplateId || 0)) {
                return;
            }
            const groupIndex = (template.lockedName ? 2 : 0) + (Number(template.inheritsFromId || 0) > 0 ? 1 : 0);
            groups[groupIndex].templates.push(template);
        });

        select.innerHTML = '';
        if (settings.emptyLabel !== undefined) {
            const option = document.createElement('option');
            option.value = '0';
            option.textContent = settings.emptyLabel;
            select.appendChild(option);
        }

        groups.forEach(function (group) {
            if (!group.templates.length) {
                return;
            }
            group.templates.sort(function (left, right) {
                return String(left.name || '').localeCompare(String(right.name || ''), 'fr', { sensitivity: 'base' });
            });
            const optgroup = document.createElement('optgroup');
            optgroup.label = group.label;
            group.templates.forEach(function (template) {
                const contextName = String(template.definedInName || template.definedInLabel || '').trim();
                const contextSuffix = Number(template.definedInId || 0) > 0
                    && Number(template.definedInId) !== Number(settings.contextHolonId || 0)
                    && contextName !== ''
                    ? ' > ' + contextName
                    : '';
                const option = document.createElement('option');
                option.value = Number(template.id);
                option.textContent = String(template.name || '').trim() + contextSuffix;
                optgroup.appendChild(option);
            });
            select.appendChild(optgroup);
        });

        select.value = String(Number(settings.selectedId || 0));
        if (!select.value && select.options.length) {
            select.selectedIndex = 0;
        }
    };
})();
