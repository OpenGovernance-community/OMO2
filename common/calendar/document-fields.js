(function (window, document) {
    'use strict';
    if (window.omoCalendarDocumentFields) { window.omoCalendarDocumentFields.init(document); return; }

    function sync(form) {
        var type = form.querySelector('[data-omo-calendar-document-type]');
        var template = form.querySelector('[name="document_template_id"]');
        [
            ['[data-omo-calendar-document-url-field]', 'external_link'],
            ['[data-omo-calendar-document-file-field]', 'uploaded_file']
        ].forEach(function (field) {
            var container = form.querySelector(field[0]);
            if (!container) { return; }
            var needed = type && type.value === field[1] && (!template || Number(template.value) === 0);
            var input = container.querySelector('input');
            container.hidden = !needed;
            input.required = !!needed;
            input.disabled = !needed;
        });
    }

    function init(scope) {
        var forms = Array.from(scope.querySelectorAll('[data-omo-calendar-create-form]'));
        var parentForm = scope.closest && scope.closest('[data-omo-calendar-create-form]');
        if (parentForm) { forms.push(parentForm); }
        forms.forEach(sync);
    }

    document.addEventListener('change', function (event) {
        var form = event.target.closest('[data-omo-calendar-create-form]');
        if (form) { sync(form); }
    });
    window.omoCalendarDocumentFields = {init: init};
    new MutationObserver(function (records) {
        records.forEach(function (record) {
            record.addedNodes.forEach(function (node) { if (node.nodeType === 1) { init(node); } });
        });
    }).observe(document.documentElement, {childList: true, subtree: true});
    init(document);
})(window, document);
