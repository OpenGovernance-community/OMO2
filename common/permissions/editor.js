(function () {
    'use strict';
    if (window.omoPermissionEditorEnhance) return;
    const translations = fetch('/common/jstranslation/permissions.php', { credentials: 'same-origin' })
        .then(response => response.ok ? response.json() : {})
        .catch(() => ({}));
    const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

    window.omoPermissionEditorEnhance = function (root, inherited) {
        if (!root || !root.querySelector('[data-permission-key]')) return;
        if (root.permissionEditorObserver) root.permissionEditorObserver.disconnect();
        if (root.permissionEditorResizeObserver) root.permissionEditorResizeObserver.disconnect();
        if (root.permissionEditorAlign) root.removeEventListener('toggle', root.permissionEditorAlign, true);
        if (root.permissionEditorRefresh) root.removeEventListener('change', root.permissionEditorRefresh);
        const labels = [];
        const label = (element, key, fallback, attribute) => {
            labels.push({ element, key, attribute });
            if (attribute) element.setAttribute(attribute, fallback);
            else element.textContent = fallback;
            return element;
        };
        const toolbar = document.createElement('div');
        toolbar.className = 'generic-form-stack';
        const help = label(document.createElement('p'), 'help', '');
        help.className = 'generic-description';
        const search = label(document.createElement('input'), 'search', 'Rechercher', 'placeholder');
        search.type = 'search';
        search.className = 'generic-form-control';
        label(search, 'search', 'Rechercher', 'aria-label');
        const actions = document.createElement('div');
        actions.className = 'generic-form-actions';
        const filterLabel = document.createElement('label');
        filterLabel.className = 'generic-form-label';
        const assigned = document.createElement('input');
        assigned.type = 'checkbox';
        filterLabel.append(assigned, label(document.createElement('span'), 'assigned', 'Attribues'));
        actions.append(filterLabel);
        const groups = [];
        for (const section of root.querySelectorAll('[data-permission-group]')) {
            const details = document.createElement('details');
            details.className = 'generic-accordion generic-soft-panel';
            details.dataset.permissionGroup = section.dataset.permissionGroup;
            const summary = document.createElement('summary');
            const title = document.createElement('span');
            title.textContent = section.firstElementChild.textContent;
            const count = document.createElement('span');
            count.className = 'generic-meta';
            summary.append(title, document.createTextNode(' - '), count);
            details.append(summary);
            const table = section.lastElementChild;
            details.append(table);
            section.replaceWith(details);
            const rows = Array.from(table.querySelectorAll('[data-permission-key]')).map(row => {
                row.classList.add('omo-permission-editor__row');
                const key = row.dataset.permissionKey;
                const inheritedItems = [];
                for (const profile of ['member', 'admin', 'collective']) {
                    const inheritedEntry = inherited && inherited[profile] && inherited[profile][key];
                    const profileItems = inheritedEntry && Array.isArray(inheritedEntry.visibleItems) ? inheritedEntry.visibleItems : [];
                    if (!profileItems.length) continue;
                    const profileLabel = profile === 'member' ? 'Membre' : (profile === 'admin' ? 'Admin' : 'Collectif');
                    inheritedItems.push(profileLabel + ' : ' + profileItems.map(item => item.label || item.id).join(', '));
                }
                const main = row.firstElementChild;
                const info = document.createElement('details');
                info.className = 'generic-accordion generic-meta generic-meta--compact';
                info.append(label(document.createElement('summary'), 'details', 'Details'));
                for (const item of Array.from(main.children).slice(1)) info.append(item);
                if (inheritedItems.length) {
                    const note = document.createElement('p');
                    note.className = 'generic-meta';
                    note.append(label(document.createElement('strong'), 'inherited', 'Herite'), document.createTextNode(' : ' + inheritedItems.join(' ; ')));
                    main.append(note);
                }
                const picker = row.lastElementChild;
                picker.classList.add('omo-permission-editor__picker');
                const select = row.querySelector('[data-permission-select]');
                if (select) {
                    select.classList.add('generic-form-control');
                    select.setAttribute('aria-label', row.firstElementChild.firstElementChild.textContent);
                }
                picker.append(info);
                return { row, inherited: inheritedItems.length > 0, search: normalize(title.textContent + ' ' + row.textContent + ' ' + key) };
            });
            details.open = rows.some(item => item.inherited || item.row.querySelector('[data-permission-token]'));
            groups.push({ details, count, rows });
        }
        for (const [key, fallback, open] of [['expand', '+', true], ['collapse', '-', false]]) {
            const button = label(document.createElement('button'), key, fallback);
            button.type = 'button';
            button.className = 'generic-action-button generic-action-button--secondary';
            button.addEventListener('click', () => groups.forEach(group => { if (!group.details.hidden) group.details.open = open; }));
            actions.append(button);
        }
        const empty = label(document.createElement('p'), 'empty', 'Aucun resultat');
        empty.className = 'generic-description';
        empty.setAttribute('role', 'status');
        toolbar.append(help, search, actions, empty);
        const profileLegend = document.createElement('div');
        profileLegend.className = 'omo-permission-editor__profile-legend';
        profileLegend.append(
            label(document.createElement('span'), 'profile_legend', 'M : Membres · A : Admin · C : Collectif'),
            (() => {
                const columns = document.createElement('span');
                columns.className = 'omo-permission-editor__profile-legend-columns';
                for (const profile of ['M', 'A', 'C']) {
                    const column = document.createElement('span');
                    column.textContent = profile;
                    columns.append(column);
                }
                return columns;
            })()
        );
        root.prepend(toolbar);
        toolbar.after(profileLegend);
        // Use the actual card inset: templates and spaces have different padding.
        const alignLegend = () => {
            if (!profileLegend.isConnected) return;
            const profiles = Array.from(root.querySelectorAll('.omo-permission-editor__profiles'))
                .find(element => element.getBoundingClientRect().width > 0);
            if (!profiles) return;
            const legendBox = profileLegend.getBoundingClientRect();
            const profilesBox = profiles.getBoundingClientRect();
            const border = parseFloat(getComputedStyle(profileLegend).borderRightWidth) || 0;
            profileLegend.style.setProperty('--permission-legend-inset',
                Math.max(0, legendBox.right - profilesBox.right - border) + 'px');
        };
        const resizeObserver = new ResizeObserver(alignLegend);
        resizeObserver.observe(root);
        for (const item of groups.flatMap(group => group.rows)) resizeObserver.observe(item.row);
        root.permissionEditorResizeObserver = resizeObserver;
        root.permissionEditorAlign = alignLegend;
        root.addEventListener('toggle', alignLegend, true);
        const refresh = () => {
            const query = normalize(search.value).trim();
            let visible = 0;
            for (const group of groups) {
                let shown = 0, configured = 0;
                for (const item of group.rows) {
                    const hasAssignment = item.inherited || !!item.row.querySelector('[data-permission-profile]:checked');
                    if (hasAssignment) configured++;
                    item.row.hidden = (assigned.checked && !hasAssignment) || !item.search.includes(query);
                    if (!item.row.hidden) shown++;
                }
                group.details.hidden = shown === 0;
                group.count.textContent = configured + ' / ' + group.rows.length;
                if (query || assigned.checked) group.details.open = shown > 0;
                visible += shown;
            }
            empty.hidden = visible > 0;
            alignLegend();
        };
        search.addEventListener('input', refresh);
        assigned.addEventListener('change', refresh);
        root.addEventListener('change', refresh);
        root.permissionEditorRefresh = refresh;
        const observer = new MutationObserver(refresh);
        for (const container of root.querySelectorAll('[data-permission-tokens]')) observer.observe(container, { childList: true });
        root.permissionEditorObserver = observer;
        translations.then(texts => {
            if (!toolbar.isConnected) return;
            for (const item of labels) {
                if (!texts[item.key]) continue;
                if (item.attribute) item.element.setAttribute(item.attribute, texts[item.key]);
                else item.element.textContent = texts[item.key];
            }
        });
        refresh();
    };
})();
