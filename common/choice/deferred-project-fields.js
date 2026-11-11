(function (window) {
    'use strict';

    window.omoSetDeferredProjectResponsibleOptions = function (root, groups, selectedValue) {
        var select = root.querySelector('[data-deferred-project-responsible]');
        if (!select) return;
        var value = selectedValue == null ? select.value : String(selectedValue || '');
        var previous = Array.from(select.options).find(function (option) { return option.value === value; });
        var fragment = document.createDocumentFragment();
        fragment.appendChild(new Option('', ''));
        var seen = new Set();
        ['role', 'circle', 'organization'].forEach(function (key) {
            var group = document.createElement('optgroup');
            group.label = select.getAttribute('data-group-' + key) || '';
            Object.entries((groups || {})[key] || {}).sort(function (left, right) {
                return left[1].localeCompare(right[1], undefined, {sensitivity: 'base', numeric: true});
            }).forEach(function (entry) {
                if (seen.has(entry[0])) return;
                seen.add(entry[0]);
                group.appendChild(new Option(entry[1], entry[0]));
            });
            // Preserve an existing assignment to a member who has since left.
            if (key === 'organization' && value && !seen.has(value)) {
                group.appendChild(new Option(previous ? previous.textContent : select.dataset.personFallback + value, value));
            }
            if (group.children.length) fragment.appendChild(group);
        });
        select.replaceChildren(fragment);
        select.value = value;
    };

    window.omoInitDeferredProjectFields = function (root) {
        root.querySelectorAll('[data-deferred-project-status-row]').forEach(function (row) {
            var status = row.querySelector('[name="status"]');
            if (!status) return;
            function sync() {
                var blockedRow = row.parentElement.querySelector('[data-deferred-project-blocked-row]');
                if (!blockedRow) return;
                blockedRow.hidden = status.value !== 'blocked';
                blockedRow.querySelectorAll('[data-deferred-project-blocked-field]').forEach(function (field) {
                    field.hidden = status.value !== 'blocked';
                });
            }
            if (!row.dataset.deferredProjectFieldsReady) {
                status.addEventListener('change', sync);
                row.dataset.deferredProjectFieldsReady = '1';
            }
            sync();
        });
        root.querySelectorAll('[data-deferred-project-context-url]').forEach(function (form) {
            if (form.dataset.deferredProjectMembersReady) return;
            var holon = form.elements.namedItem('holon_id');
            if (!holon) return;
            form.dataset.deferredProjectMembersReady = '1';
            var request = 0;
            holon.addEventListener('change', function () {
                var current = ++request;
                fetch(form.dataset.deferredProjectContextUrl + encodeURIComponent(holon.value), {credentials: 'same-origin'})
                    .then(function (response) { return response.json().then(function (data) {
                        if (!response.ok || !data.status) throw new Error(data.message);
                        return data;
                    }); })
                    .then(function (data) {
                        if (current === request) window.omoSetDeferredProjectResponsibleOptions(form, data.projectResponsibleGroups);
                    })
                    .catch(function () {
                        if (current !== request) return;
                        var select = form.querySelector('[data-deferred-project-responsible]');
                        if (select && window.commonNotify) window.commonNotify(select.dataset.membersError, 'error');
                    });
            });
        });
    };
})(window);
