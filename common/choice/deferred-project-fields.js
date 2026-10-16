(function (window) {
    'use strict';

    window.omoInitDeferredProjectFields = function (root) {
        root.querySelectorAll('[data-deferred-project-status-row]').forEach(function (row) {
            var status = row.querySelector('[name="status"]');
            if (!status) return;
            function sync() {
                row.querySelectorAll('[data-deferred-project-blocked-field]').forEach(function (field) {
                    field.hidden = status.value !== 'blocked';
                });
            }
            if (!row.dataset.deferredProjectFieldsReady) {
                status.addEventListener('change', sync);
                row.dataset.deferredProjectFieldsReady = '1';
            }
            sync();
        });
    };
})(window);
