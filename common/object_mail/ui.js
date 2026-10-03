(function () {
    'use strict';
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
        if (form.getAttribute('aria-busy') === 'true') { event.preventDefault(); return; }
        form.setAttribute('aria-busy', 'true');
        var button = form.querySelector('button[type="submit"]');
        if (button) button.disabled = true;
        var progress = form.querySelector('[data-object-mail-progress]');
        if (progress) { progress.hidden = false; progress.textContent = form.getAttribute('data-object-mail-sending'); }
    });
}());
