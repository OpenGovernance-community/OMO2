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
            const html = '<div class="omo-document-embed-picker omo-resource-picker generic-drawer-content">'
                + '<aside class="omo-resource-picker__navigation" data-document-picker-scope></aside>'
                + '<div class="omo-resource-picker__content">'
                + '<label class="omo-resource-picker__quick-search"><img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true">'
                + '<input type="search" class="generic-form-control" data-document-picker-search aria-label="' + escapeHtml(labels.search) + '" placeholder="' + escapeHtml(labels.quickSearchPlaceholder) + '"></label>'
                + '<div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select" data-document-picker-select size="10" aria-label="' + escapeHtml(labels.visibleDocuments) + '"></select></div>'
                + '<div class="omo-document-embed-picker__preview"><div class="omo-document-embed-picker__preview-title" data-document-picker-title></div>'
                + '<div class="omo-document-embed-picker__preview-context" data-document-picker-context hidden></div>'
                + '<div class="omo-document-embed-picker__preview-description" data-document-picker-description hidden></div></div>'
                + '<div class="omo-document-embed-picker__actions">'
                + (settings.onRemove ? '<button type="button" class="generic-action-button generic-action-button--danger" data-document-picker-remove>' + escapeHtml(labels.remove) + '</button>' : '')
                + '<button type="button" class="generic-action-button generic-action-button--secondary" data-document-picker-cancel>' + escapeHtml(labels.cancel) + '</button>'
                + '<button type="button" class="generic-action-button generic-action-button--main" data-document-picker-apply disabled>' + escapeHtml(labels.insert) + '</button></div></div></div>';
            window.commonTopbarOpenModal(labels.modalTitle || '', html, 'html');
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
                apply.disabled = !item;
            }
            function render() {
                if (closed || !host.isConnected) return;
                const query = search.value.trim().toLowerCase();
                matches = (settings.items || []).filter(function (item) {
                    return (!settings.filter || settings.filter(item))
                        && (!scope || scope.matches(item.contextHolonId))
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
                if (item && settings.onSelect(item) !== false) close();
            }
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
