(function (window) {
    'use strict';

    function escapeHtml(value) {
        return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    window.omoDocumentEmbedPicker = {
        open: function (settings) {
            if (typeof window.commonTopbarOpenModal !== 'function') return null;
            const labels = settings.labels || {};
            const tabs = Array.isArray(settings.tabs) ? settings.tabs : [];
            let activeTab = tabs[0] || null;
            const html = (tabs.length ? '<div class="generic-tabs generic-tabs--embedded generic-tabs--spaced-top"><div class="generic-tabs__list">' + tabs.map(function (tab, index) {
                return '<button type="button" class="generic-tabs__tab' + (index === 0 ? ' is-active' : '')
                    + '" data-document-picker-tab="' + escapeHtml(tab.value) + '" aria-pressed="' + (index === 0 ? 'true' : 'false') + '">' + escapeHtml(tab.label) + '</button>';
            }).join('') + '</div><div class="generic-tabs__panels">' : '')
                + '<div class="omo-document-embed-picker omo-resource-picker omo-resource-picker--mobile-scope generic-drawer-content">'
                + '<aside class="omo-resource-picker__navigation" data-document-picker-scope></aside>'
                + '<div class="omo-resource-picker__content generic-form-stack generic-form-stack--compact">'
                + '<label class="omo-resource-picker__quick-search"><img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true">'
                + '<input type="search" class="generic-form-control" data-document-picker-search aria-label="' + escapeHtml(labels.search) + '" placeholder="' + escapeHtml(labels.quickSearchPlaceholder) + '"></label>'
                + '<div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select" data-document-picker-select size="10" aria-label="' + escapeHtml(labels.visibleDocuments) + '"></select></div>'
                + '<div class="omo-document-embed-picker__preview"><div class="omo-document-embed-picker__preview-title" data-document-picker-title></div>'
                + '<div class="omo-document-embed-picker__preview-context" data-document-picker-context hidden></div>'
                + '<div class="omo-document-embed-picker__preview-description" data-document-picker-description hidden></div></div>'
                + '<div class="omo-document-embed-picker__actions generic-form-actions">'
                + (settings.onRemove ? '<button type="button" class="generic-action-button generic-action-button--danger" data-document-picker-remove>' + escapeHtml(labels.remove) + '</button>' : '')
                + '<button type="button" class="generic-action-button generic-action-button--secondary" data-document-picker-cancel>' + escapeHtml(labels.cancel) + '</button>'
                + '<button type="button" class="generic-action-button generic-action-button--main" data-document-picker-apply disabled>' + escapeHtml(labels.insert) + '</button></div></div></div>'
                + (tabs.length ? '</div></div>' : '');
            window.commonTopbarOpenModal(labels.modalTitle || '', html, 'html', {help: labels.hint});
            const body = document.getElementById('commonTopbarModalBody');
            if (!body) return null;
            const host = body.querySelector('.omo-document-embed-picker');
            const search = host.querySelector('[data-document-picker-search]');
            const select = host.querySelector('[data-document-picker-select]');
            const apply = host.querySelector('[data-document-picker-apply]');
            let selectedId = Number(settings.selectedId || 0);
            let matches = [];
            let scope = null;
            let closed = false;
            let busy = false;

            function title(item) {
                return String(item.title || '').trim() || String(labels.fallbackTitle || '').replace('{id}', String(item.id));
            }
            function selectedItem() {
                return matches.find(function (item) { return Number(item.id) === Number(select.value); }) || null;
            }
            function updatePreview() {
                const item = selectedItem();
                selectedId = item ? Number(item.id) : 0;
                host.querySelector('[data-document-picker-title]').textContent = item ? title(item) : String(labels.none || '');
                ['context', 'description'].forEach(function (key) {
                    const node = host.querySelector('[data-document-picker-' + key + ']');
                    node.textContent = item ? String(item[key === 'context' ? 'contextLabel' : key] || '') : '';
                    node.hidden = node.textContent === '';
                });
                apply.disabled = busy || !item;
            }
            function render() {
                if (closed || !host.isConnected) return;
                const query = search.value.trim().toLowerCase();
                matches = (settings.items || []).filter(function (item) {
                    return (!activeTab || String(item.source) === String(activeTab.value))
                        && (!settings.filter || settings.filter(item))
                        && (!scope || (activeTab && activeTab.scope === false)
                            || (settings.includeUnscoped && Number(item.contextHolonId || 0) <= 0) || scope.matches(item.contextHolonId))
                        && (!query || [item.title, item.description, item.contextLabel].join(' ').toLowerCase().includes(query));
                });
                select.replaceChildren();
                matches.forEach(function (item) {
                    const option = document.createElement('option');
                    option.value = String(item.id);
                    option.textContent = title(item);
                    select.appendChild(option);
                });
                select.disabled = matches.length === 0;
                const preferred = matches.find(function (item) { return Number(item.id) === selectedId; }) || matches[0];
                select.value = preferred ? String(preferred.id) : '';
                updatePreview();
            }
            const scopeHost = host.querySelector('[data-document-picker-scope]');
            if (typeof window.omoMountHolonScopePicker === 'function' && Number(settings.organizationId) > 0) {
                scope = window.omoMountHolonScopePicker({host: scopeHost, organizationId: settings.organizationId,
                    initialHolonId: settings.initialHolonId, initialScope: 'local', labels: settings.scopeLabels || {}, onChange: render});
            } else {
                scopeHost.remove();
                host.classList.remove('omo-resource-picker');
            }
            function close() {
                if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal();
            }
            function applySelection() {
                const item = selectedItem();
                if (!item || busy) return;
                const result = settings.onSelect(item);
                if (result && typeof result.then === 'function') {
                    busy = true;
                    apply.disabled = true;
                    Promise.resolve(result).then(function (accepted) {
                        if (accepted !== false && !closed) close();
                    }).catch(function (error) {
                        if (typeof window.commonNotify === 'function') window.commonNotify(String(error.message || labels.error || ''), 'error');
                    }).finally(function () { busy = false; if (!closed) updatePreview(); });
                } else if (result !== false) close();
            }
            body.querySelectorAll('[data-document-picker-tab]').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (busy) return;
                    activeTab = tabs.find(function (tab) { return String(tab.value) === button.getAttribute('data-document-picker-tab'); });
                    body.querySelectorAll('[data-document-picker-tab]').forEach(function (tabButton) {
                        const selected = tabButton === button;
                        tabButton.classList.toggle('is-active', selected);
                        tabButton.setAttribute('aria-pressed', selected ? 'true' : 'false');
                    });
                    const withScope = !activeTab || activeTab.scope !== false;
                    scopeHost.hidden = !withScope;
                    host.classList.toggle('omo-resource-picker', withScope && !!scope);
                    render();
                });
            });
            search.addEventListener('input', render);
            select.addEventListener('change', updatePreview);
            select.addEventListener('dblclick', applySelection);
            apply.addEventListener('click', applySelection);
            host.querySelector('[data-document-picker-cancel]').addEventListener('click', close);
            const remove = host.querySelector('[data-document-picker-remove]');
            if (remove) remove.addEventListener('click', function () { settings.onRemove(); close(); });
            function finish(event) {
                // A nested modal returns to its parent through pop, not close.
                if (event.type === 'common-topbar-modal-pop' && !body.contains(host)) return;
                closed = true;
                window.removeEventListener('common-topbar-modal-close', finish);
                window.removeEventListener('common-topbar-modal-pop', finish);
                if (scope && typeof scope.destroy === 'function') scope.destroy();
                if (typeof settings.onClose === 'function') settings.onClose();
            }
            window.addEventListener('common-topbar-modal-close', finish);
            window.addEventListener('common-topbar-modal-pop', finish);
            render();
            search.focus({preventScroll: true});
            return {host: host, render: render};
        }
    };
})(window);
