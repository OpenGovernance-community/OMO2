(function (window, document) {
    'use strict';
    var root = typeof window.omoFindApplicationRoot === 'function'
        ? window.omoFindApplicationRoot('omo-scrum-root') : document.getElementById('omo-scrum-root');
    if (!root || root.dataset.ready === '1') return;
    root.dataset.ready = '1';
    var texts = JSON.parse(root.dataset.texts || '{}');
    var busy = false;
    function notify(message, type) { (window.omoNotify || window.commonNotify)(message, type); }
    function navigate(url) {
        return window.omoReplaceFetchedPanelRoot({ rootSelector: '#omo-scrum-root', currentRoot: root, url: url,
            beforeReplace: function () {
                var projectsRoot = root.querySelector('#omo-projects-root');
                if (projectsRoot && typeof projectsRoot.omoDisposeProjects === 'function') projectsRoot.omoDisposeProjects();
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
                notify(result.message, 'success'); return navigate(result.url);
            }).catch(function (error) { notify(error.message || texts.save, 'error'); })
            .finally(function () { busy = false; root.removeAttribute('aria-busy'); });
    }
    root.addEventListener('click', function (event) {
        var link = event.target.closest('[data-scrum-link]');
        if (link && !event.ctrlKey && !event.metaKey && !event.shiftKey) { event.preventDefault(); navigate(link.href).catch(function () { notify(texts.save, 'error'); }); return; }
        var card = event.target.closest('[data-scrum-card]');
        if (card && !event.target.closest('a, button') && !String(window.getSelection())) {
            navigate(card.dataset.scrumCard).catch(function () { notify(texts.save, 'error'); }); return;
        }
        var button = event.target.closest('[data-scrum-action]');
        if (!button) return;
        var action = button.dataset.scrumAction;
        if (action === 'stop' && !window.confirm(texts.stop_confirm)) return;
        if (action === 'delete' && !window.confirm(texts.delete_confirm)) return;
        var data = new FormData(); data.set('project_id', button.dataset.projectId || '0'); send(action, data);
    });
    var editor = root.querySelector('#formulaire-edit');
    if (editor) editor.addEventListener('submit', function (event) { event.preventDefault(); send('save', new FormData(editor)); });
    var pickerHost = root.querySelector('[data-scrum-picker]');
    if (!pickerHost) return;
    var nodes = JSON.parse(pickerHost.dataset.nodes || '[]');
    var byId = new Map(nodes.map(function (node) { return [node.id, node]; }));
    var existing = JSON.parse(pickerHost.dataset.existing || '[]');
    var selected = []; var sizes = {}; var included = [];
    var weights = { S: 1, M: 4, L: 16, XL: 64, XXL: 256 };
    var preview = root.querySelector('[data-scrum-preview]');
    var total = root.querySelector('[data-scrum-total]');
    function render() {
        var ids = new Set(existing.concat(selected));
        var previousCount;
        do {
            previousCount = ids.size;
            nodes.forEach(function (node) { if (ids.has(node.parent)) ids.add(node.id); });
        } while (ids.size > previousCount);
        included = nodes.filter(function (node) { return ids.has(node.id); });
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
        var sum = included.filter(function (node) { return !ids.has(node.parent); }).reduce(function (value, node) { return value + weight(node.id); }, 0);
        total.textContent = texts.load + ' : ' + sum + ' ' + (sum === 1 ? texts.point : texts.points);
        preview.replaceChildren();
        included.forEach(function (node) {
            var row = document.createElement('label'); row.className = 'scrum-estimate-row';
            var title = document.createElement('span'); title.textContent = node.title;
            var select = document.createElement('select'); select.className = 'generic-form-control'; select.dataset.projectId = node.id;
            var blank = document.createElement('option'); blank.value = ''; blank.textContent = texts.estimate; select.appendChild(blank);
            Object.keys(weights).forEach(function (size) { var option = document.createElement('option'); option.value = size; option.textContent = size + ' (' + weights[size] + ')'; select.appendChild(option); });
            select.value = sizes[node.id] || node.size || ''; select.disabled = !node.editable;
            row.append(title, select);
            var childWeight = included.filter(function (child) { return child.parent === node.id; }).reduce(function (value, child) { return value + weight(child.id); }, 0);
            if (childWeight > (weights[select.value] || 0)) { var hint = document.createElement('small'); hint.className = 'generic-help-text'; hint.textContent = texts.small_parent + ' (' + childWeight + ')'; row.appendChild(hint); }
            preview.appendChild(row);
        });
        root.querySelector('[data-scrum-import]').disabled = !included.length || included.some(function (node) { return !weights[sizes[node.id] || node.size]; });
    }
    preview.addEventListener('change', function (event) { if (event.target.dataset.projectId) { sizes[event.target.dataset.projectId] = event.target.value; render(); } });
    window.commonMountProjectPicker({ root: pickerHost, scopeHost: '[data-scrum-scope]', searchInput: '[data-scrum-search]', selectElement: '[data-scrum-select]',
        projects: nodes.filter(function (node) { return node.active === 1; }), multiple: true,
        organizationId: Number(pickerHost.dataset.oid), initialHolonId: Number(pickerHost.dataset.cid), initialScope: 'local',
        onChange: function (projects, state) { selected = state.selectedIds; render(); }
    });
    root.querySelector('[data-scrum-import]').addEventListener('click', function () {
        var data = new FormData(); selected.forEach(function (id) { data.append('projects[]', id); });
        included.forEach(function (node) { data.set('sizes[' + node.id + ']', sizes[node.id] || node.size || ''); });
        send('import', data);
    });
    render();
})(window, document);
