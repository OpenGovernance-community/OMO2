(function () {
    'use strict';
    var recipientPicker = document.querySelector('[data-object-mail-picker]');
    if (recipientPicker) {
        document.addEventListener('pointerdown', function (event) {
            if (recipientPicker.open && !recipientPicker.contains(event.target)) recipientPicker.open = false;
        });
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape' || !recipientPicker.open) return;
            event.preventDefault();
            event.stopPropagation();
            recipientPicker.open = false;
            recipientPicker.querySelector('summary').focus();
        });
    }
    var editorHost = document.querySelector('[data-object-mail-editor]');
    var editor = null;
    var messageField = document.querySelector('[data-object-mail-form] [name="message"]');
    if (editorHost && messageField && window.omoSimpleHtmlField) {
        var formatField = messageField.form.querySelector('[name="message_format"]');
        var initial = messageField.value;
        if (formatField.value !== 'html') {
            var text = document.createElement('div');
            text.textContent = initial;
            initial = text.innerHTML.replace(/\r\n?|\n/g, '<br>');
        }
        editor = window.omoSimpleHtmlField.mount(editorHost, {
            value: initial, simpleOnly: true, surfaceId: 'object-mail-message-html',
            placeholder: editorHost.getAttribute('data-placeholder'),
            onChange: function (value) {
                messageField.value = value;
                var error = document.querySelector('[data-object-mail-message-error]');
                if (error) error.hidden = true;
            }
        });
        editorHost.querySelector('.generic-html-editor').classList.add('generic-html-editor--fill');
        messageField.hidden = true;
        messageField.required = false;
        formatField.value = 'html';
        document.querySelector('label[for="object-mail-message"]').htmlFor = 'object-mail-message-html';
    }
    function updateSelection() {
        var form = document.querySelector('[data-object-mail-form]');
        if (!form) return true;
        var count = document.querySelectorAll('[data-object-mail-recipient]:checked').length;
        var counter = document.querySelector('[data-object-mail-count]');
        if (counter) counter.textContent = count === 1 ? counter.getAttribute('data-one') : counter.getAttribute('data-other').replace('{count}', String(count));
        var error = document.querySelector('[data-object-mail-selection-error]');
        var invalid = count < 1 || count > Number(form.getAttribute('data-object-mail-max'));
        if (error) {
            error.hidden = !invalid;
            error.textContent = invalid ? error.getAttribute(count < 1 ? 'data-empty' : 'data-too-many') : '';
        }
        var submit = form.querySelector('button[type="submit"]');
        if (submit) submit.disabled = invalid || form.getAttribute('aria-busy') === 'true';
        return !invalid;
    }
    updateSelection();
    document.addEventListener('change', function (event) {
        if (event.target.matches('[data-object-mail-recipient]')) updateSelection();
    });
    if (document.body.classList.contains('object-mail-page') && window.parent !== window) {
        // Reuse the application's theme inside the standalone composer iframe.
        ['data-theme', 'data-color-style'].forEach(function (attribute) {
            var value = window.parent.document.documentElement.getAttribute(attribute);
            if (value) document.documentElement.setAttribute(attribute, value);
        });
        if (typeof window.parent.commonTopbarCloseModal === 'function') {
            document.querySelectorAll('[data-object-mail-close]').forEach(function (button) {
                button.hidden = false;
                button.addEventListener('click', function () { window.parent.commonTopbarCloseModal(); });
            });
        }
    }
    document.addEventListener('click', function (event) {
        var selection = event.target.closest('[data-object-mail-select]');
        if (selection) {
            event.preventDefault();
            event.stopPropagation();
            var checked = selection.getAttribute('data-object-mail-select') === 'all';
            document.querySelectorAll('[data-object-mail-recipient]:not(:disabled)').forEach(function (input) { input.checked = checked; });
            updateSelection();
            return;
        }
        var button = event.target.closest('[data-object-mail-open]');
        if (!button) return;
        event.preventDefault();
        var url = new URL(button.getAttribute('data-object-mail-open'), window.location.href);
        if (url.origin !== window.location.origin || url.pathname !== '/omo/api/object_mail/index.php') return;
        if (typeof window.commonTopbarOpenModal === 'function') {
            window.commonTopbarOpenModal(button.textContent.trim(), url.pathname + url.search, 'iframe');
        } else if (typeof window.commonNotify === 'function') {
            window.commonNotify(button.getAttribute('data-object-mail-error'), 'error');
        }
    });
    document.addEventListener('submit', function (event) {
        var form = event.target.closest('[data-object-mail-form]');
        if (!form) return;
        if (!updateSelection()) { event.preventDefault(); return; }
        if (editor) {
            messageField.value = editor.getValue();
            var content = document.createElement('div');
            content.innerHTML = messageField.value;
            if (!content.textContent.replace(/\u00a0/g, ' ').trim() || Array.from(messageField.value).length > 20000) {
                event.preventDefault();
                var error = document.querySelector('[data-object-mail-message-error]');
                error.hidden = false;
                error.textContent = editorHost.getAttribute('data-invalid');
                editor.focus();
                return;
            }
        }
        if (form.getAttribute('aria-busy') === 'true') { event.preventDefault(); return; }
        form.setAttribute('aria-busy', 'true');
        var button = form.querySelector('button[type="submit"]');
        if (button) button.disabled = true;
        var progress = form.querySelector('[data-object-mail-progress]');
        if (progress) { progress.hidden = false; progress.textContent = form.getAttribute('data-object-mail-sending'); }
    });
}());
