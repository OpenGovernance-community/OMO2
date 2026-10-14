(function (window, document) {
    'use strict';
    var root = ['omo-scrum-picker', 'omo-scrum-detail', 'omo-scrum-root'].map(function (id) {
        return typeof window.omoFindApplicationRoot === 'function' ? window.omoFindApplicationRoot(id) : document.getElementById(id);
    }).find(function (node) { return node && node.dataset.ready !== '1'; });
    if (!root) return;
    root.dataset.ready = '1';
    var texts = JSON.parse(root.dataset.texts || '{}');
    var busy = false;
    var isPicker = root.id === 'omo-scrum-picker';
    var drawer = root.querySelector('[data-scrum-drawer]');
    var drawerBody = drawer && drawer.querySelector('[data-scrum-drawer-body]');
    var drawerController = drawer && window.omoCreateSubdrawerController({drawer: drawer});
    var requestToken = 0;
    var opener = null;
    function dispose(container) {
        var projects = container.querySelector('#omo-projects-root');
        if (projects && typeof projects.omoDisposeProjects === 'function') projects.omoDisposeProjects();
    }
    function openSprint(url, trigger) {
        if (!drawer) return navigate(url);
        opener = trigger || document.activeElement;
        var token = ++requestToken;
        dispose(drawerBody);
        window.commonRenderLoadingState(drawerBody, texts.loading);
        drawerController.open();
        return fetch(url, {credentials: 'same-origin', cache: 'no-store'}).then(function (response) {
            if (!response.ok) throw new Error(texts.save);
            return response.text();
        }).then(function (html) {
            if (token !== requestToken) return;
            drawerBody.innerHTML = html;
            var stylesReady = window.genericAwaitStylesheets ? window.genericAwaitStylesheets(drawerBody) : Promise.resolve();
            return stylesReady.then(function () {
                if (token !== requestToken) return;
                return window.commonExecuteFragmentScripts(drawerBody);
            }).then(function () {
                if (token !== requestToken) return;
                if (window.initGenericComponents) window.initGenericComponents(drawerBody);
                if (window.omoInitMobileHeaderMenus) window.omoInitMobileHeaderMenus(drawerBody);
                drawerBody.querySelector('[data-scrum-close]')?.focus({preventScroll: true});
            });
        }).catch(function (error) {
            if (token !== requestToken) return;
            drawer.hidden = true; drawer.classList.remove('is-open');
            notify(error.message || texts.save, 'error');
        });
    }
    function closeSprint() {
        var owner = root.id === 'omo-scrum-root' ? root : root.closest('#omo-scrum-root');
        if (owner && owner !== root) return owner.omoCloseSprint();
        if (drawer) {
            if (window.commonDrawerConfirmDiscard && !window.commonDrawerConfirmDiscard(drawer)) return;
            ++requestToken; dispose(drawerBody); drawer.hidden = true; drawer.classList.remove('is-open'); drawerBody.replaceChildren();
            if (opener && opener.isConnected) opener.focus({preventScroll: true});
            return navigate(root.dataset.url).catch(function () { notify(texts.save, 'error'); });
        }
    }
    if (drawer) root.omoCloseSprint = closeSprint;
    function notify(message, type) { (window.omoNotify || window.commonNotify)(message, type); }
    function navigate(url) {
        return window.omoReplaceFetchedPanelRoot({ rootSelector: '#' + root.id, currentRoot: root, url: url,
            beforeReplace: function () {
                dispose(root);
            }
        });
    }
    function send(action, data) {
        if (busy) return Promise.resolve(null);
        busy = true;
        root.setAttribute('aria-busy', 'true');
        data = data || new FormData();
        data.set('action', action); data.set('id', root.dataset.id || '0'); data.set('_csrf', root.dataset.csrf);
        return fetch(root.dataset.actionUrl, { method: 'POST', body: data, credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (!result.status) throw new Error(result.message || texts.save);
                if (window.commonDrawerMarkSaved) window.commonDrawerMarkSaved(root);
                notify(result.message, 'success');
                if (isPicker) {
                    window.commonTopbarCloseModal();
                    var detail = document.getElementById('omo-scrum-detail');
                    if (detail && detail.omoRefreshSprint) return detail.omoRefreshSprint(result.url);
                    return;
                }
                if (['delete', 'archive', 'restore'].includes(action)) return closeSprint();
                return navigate(result.url);
            }).catch(function (error) { notify(error.message || texts.save, 'error'); })
            .finally(function () { busy = false; root.removeAttribute('aria-busy'); });
    }
    root.omoRefreshSprint = navigate;
    root.addEventListener('click', function (event) {
        if (event.target.closest('[data-scrum-root]') !== root) return;
        var close = event.target.closest('[data-scrum-close]');
        if (close) { event.preventDefault(); closeSprint(); return; }
        var popup = event.target.closest('[data-scrum-popup]');
        if (popup) { window.commonTopbarOpenModal(texts[popup.dataset.scrumPopupTitle] || texts.add, popup.dataset.scrumPopup, 'fetch'); return; }
        if (event.target.closest('[data-scrum-cancel]')) { window.commonTopbarCloseModal(); return; }
        var open = event.target.closest('[data-scrum-open]');
        if (open && !event.ctrlKey && !event.metaKey && !event.shiftKey) { event.preventDefault(); openSprint(open.href, open); return; }
        var link = event.target.closest('[data-scrum-link]');
        if (link && !event.ctrlKey && !event.metaKey && !event.shiftKey) { event.preventDefault(); navigate(link.href).catch(function () { notify(texts.save, 'error'); }); return; }
        var card = event.target.closest('[data-scrum-card]');
        if (card && !event.target.closest('a, button') && !String(window.getSelection())) {
            openSprint(card.dataset.scrumCard, card); return;
        }
        var button = event.target.closest('[data-scrum-action]');
        if (!button) return;
        var action = button.dataset.scrumAction;
        if (action === 'stop' && !window.confirm(texts.stop_confirm)) return;
        if (action === 'resume' && button.dataset.restart === '1' && !window.confirm(texts.restart_confirm)) return;
        if (action === 'delete' && !window.confirm(texts.delete_confirm)) return;
        var data = new FormData(); data.set('project_id', button.dataset.projectId || '0'); send(action, data);
    });
    var editor = root.querySelector('#formulaire-edit');
    if (editor) editor.addEventListener('submit', function (event) { event.preventDefault(); send('save', new FormData(editor)); });
    root.addEventListener('keydown', function (event) {
        if (!event.defaultPrevented && event.key === 'Escape' && event.target.closest('[data-scrum-root]') === root && !event.target.closest('.generic-menu')) {
            if (root.id === 'omo-scrum-detail' && !root.querySelector('.omo-overlay-drawer.is-open')) { event.stopPropagation(); closeSprint(); }
        }
    });
    var pickerHost = root.querySelector('[data-scrum-picker]');
    if (!pickerHost) return;
    var estimatesOnly = root.dataset.estimatesOnly === '1';
    if (!estimatesOnly) pickerHost.querySelector('[data-scrum-selection-surface]').innerHTML = window.commonProjectPickerMarkup({labels: texts, prefix: 'data-scrum', multiple: true});
    var nodes = JSON.parse(pickerHost.dataset.nodes || '[]');
    var byId = new Map(nodes.map(function (node) { return [node.id, node]; }));
    var existing = JSON.parse(pickerHost.dataset.existing || '[]');
    var selected = []; var sizes = {}; var included = []; var removed = new Set();
    var weights = { S: 1, M: 4, L: 16, XL: 64, XXL: 256 };
    var selectionStep = root.querySelector('[data-scrum-step="selection"]');
    var sizesStep = root.querySelector('[data-scrum-step="sizes"]');
    root.querySelector('[data-scrum-next]').addEventListener('click', function () {
        selectionStep.hidden = true; sizesStep.hidden = false; render();
        sizesStep.querySelector('select')?.focus();
    });
    root.querySelector('[data-scrum-previous]')?.addEventListener('click', function () {
        sizesStep.hidden = true; selectionStep.hidden = false;
        pickerHost.querySelector('select')?.focus();
    });
    var preview = root.querySelector('[data-scrum-preview]');
    var total = root.querySelector('[data-scrum-total]');
    function render() {
        var ids = new Set(existing.concat(selected));
        var previousCount;
        do {
            previousCount = ids.size;
            nodes.forEach(function (node) { if (ids.has(node.parent)) ids.add(node.id); });
        } while (ids.size > previousCount);
        removed.forEach(function (id) { ids.delete(id); });
        included = nodes.filter(function (node) { return ids.has(node.id); }).sort(function (a, b) { return a.title.localeCompare(b.title); });
        var ordered = []; var seen = new Set();
        function appendTree(node, depth) {
            if (seen.has(node.id)) return;
            seen.add(node.id); node.depth = depth; ordered.push(node);
            included.filter(function (child) { return child.parent === node.id; }).forEach(function (child) { appendTree(child, depth + 1); });
        }
        included.filter(function (node) { return !ids.has(node.parent); }).forEach(function (node) { appendTree(node, 0); });
        included.forEach(function (node) { appendTree(node, 0); });
        included = ordered;
        var memo = new Map(); var visiting = new Set();
        function weight(id) {
            if (memo.has(id)) return memo.get(id);
            if (visiting.has(id)) return 0;
            visiting.add(id);
            var node = byId.get(id); var children = included.filter(function (child) { return child.parent === id; });
            var sum = children.reduce(function (value, child) { return value + weight(child.id); }, 0);
            var value = Math.max(weights[sizes[id] || node.size] || 0, sum);
            memo.set(id, value); visiting.delete(id); return value;
        }
        total.textContent = texts.selection + ' : ' + included.length;
        preview.replaceChildren();
        included.forEach(function (node) {
            var row = document.createElement('div'); row.className = 'scrum-estimate-row';
            var title = document.createElement('label'); title.textContent = node.title;
            title.className = 'scrum-estimate-row__title'; title.style.setProperty('--param-scrum-project-depth', String(Math.min(node.depth, 5)));
            if (node.depth > 0) title.prepend(document.createTextNode('\u21b3 '));
            var select = document.createElement('select'); select.className = 'generic-form-control'; select.dataset.projectId = node.id;
            select.id = 'scrum-estimate-' + node.id; title.htmlFor = select.id;
            var blank = document.createElement('option'); blank.value = ''; blank.textContent = texts.estimate; select.appendChild(blank);
            Object.keys(weights).forEach(function (size) { var option = document.createElement('option'); option.value = size; option.textContent = size; select.appendChild(option); });
            select.value = sizes[node.id] || node.size || ''; select.disabled = !node.editable;
            var heading = document.createElement('div'); heading.className = 'generic-action-row generic-action-row--start'; heading.appendChild(title);
            if (estimatesOnly && node.removable) {
                var remove = document.createElement('button'); remove.type = 'button';
                remove.className = 'generic-action-button generic-action-button--icon-only generic-action-button--inline-icon generic-action-button--quiet-icon';
                remove.dataset.scrumRemoveTree = node.id;
                remove.title = texts.remove; remove.setAttribute('aria-label', texts.remove + ' : ' + node.title);
                remove.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 6h18M9 6V4h6v2M5 6l1 14h12l1-14M10 10v6m4-6v6"/></svg>';
                heading.appendChild(remove);
            }
            row.append(heading, select);
            var childWeight = included.filter(function (child) { return child.parent === node.id; }).reduce(function (value, child) { return value + weight(child.id); }, 0);
            if (childWeight > (weights[select.value] || 0)) { var hint = document.createElement('small'); hint.className = 'generic-help-text'; hint.textContent = texts.small_parent; row.appendChild(hint); }
            preview.appendChild(row);
        });
        root.querySelector('[data-scrum-next]').disabled = !included.length;
        root.querySelector('[data-scrum-import]').disabled = (!estimatesOnly && !included.length) || included.some(function (node) { return !weights[sizes[node.id] || node.size]; });
    }
    preview.addEventListener('click', function (event) {
        var button = event.target.closest('[data-scrum-remove-tree]');
        if (!button || busy) return;
        removed.add(Number(button.dataset.scrumRemoveTree));
        var previousCount;
        do {
            previousCount = removed.size;
            nodes.forEach(function (node) { if (removed.has(node.parent)) removed.add(node.id); });
        } while (removed.size > previousCount);
        render();
        (preview.querySelector('select') || root.querySelector('[data-scrum-import]')).focus();
    });
    preview.addEventListener('change', function (event) { if (event.target.dataset.projectId) { sizes[event.target.dataset.projectId] = event.target.value; render(); } });
    var projectPicker = !estimatesOnly && window.commonMountProjectPicker({ root: pickerHost, scopeHost: '[data-scrum-scope]', searchInput: '[data-scrum-search]', selectElement: '[data-scrum-select]',
        assignmentAttribute: 'data-scrum-assignment',
        projects: nodes.filter(function (node) { return node.active === 1; }), multiple: true,
        organizationId: Number(pickerHost.dataset.oid), initialHolonId: Number(pickerHost.dataset.cid), initialScope: 'local',
        scopeLabels: texts.scopeLabels,
        onChange: function (projects, state) { selected = state.selectedIds; render(); }
    });
    window.__omoPopupCleanup = function () { if (projectPicker) projectPicker.destroy(); };
    root.querySelector('[data-scrum-import]').addEventListener('click', function () {
        var data = new FormData(); selected.forEach(function (id) { data.append('projects[]', id); });
        included.forEach(function (node) { data.set('sizes[' + node.id + ']', sizes[node.id] || node.size || ''); });
        nodes.forEach(function (node) { if (node.removable && removed.has(node.id)) data.append('removed[]', node.id); });
        send(estimatesOnly ? 'estimates' : 'import', data);
    });
    render();
})(window, document);
