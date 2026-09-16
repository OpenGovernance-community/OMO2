(function (window, document) {
    'use strict';
    if (window.omoCalendarOpenShare) { return; }
    window.omoCalendarOpenShare = function (url, title, errorText) {
        if (typeof window.commonTopbarOpenModal !== 'function') { return; }
        var placeholder = document.createElement('p');
        placeholder.textContent = title + '...';
        window.commonTopbarOpenModal(title, placeholder.outerHTML, 'html');

        function load(message) {
            return fetch(url, {credentials: 'same-origin'}).then(function (response) {
                if (!response.ok) { throw new Error('load'); }
                return response.text();
            }).then(function (html) {
                window.commonTopbarOpenModal(title, html, 'html');
                var panel = document.querySelector('#commonTopbarModalBody [data-calendar-share]');
                if (!panel) { throw new Error('load'); }
                var text = JSON.parse(panel.dataset.text);
                var feedback = panel.querySelector('[data-calendar-share-feedback]');
                var pending = false;
                function show(value, success) {
                    feedback.textContent = value;
                    feedback.classList.toggle('is-success', !!success);
                }
                if (message) { show(message, true); }
                function post(data) {
                    if (pending) { return; }
                    pending = true;
                    panel.setAttribute('aria-busy', 'true');
                    panel.querySelectorAll('button').forEach(function (button) { button.disabled = true; });
                    show(text.saving, false);
                    fetch(url, {method: 'POST', credentials: 'same-origin', body: data}).then(function (response) {
                        return response.json();
                    }).then(function (result) {
                        if (result.status) { return load(result.message); }
                        show(result.message || text.storage, false);
                    }).catch(function () { show(text.storage, false); }).finally(function () {
                        pending = false;
                        panel.removeAttribute('aria-busy');
                        panel.querySelectorAll('button').forEach(function (button) { button.disabled = false; });
                    });
                }
                panel.querySelector('form').addEventListener('submit', function (event) {
                    event.preventDefault(); post(new FormData(event.target));
                });
                panel.addEventListener('click', function (event) {
                    var copy = event.target.closest('[data-calendar-share-copy]');
                    if (copy) {
                        var input = copy.closest('[data-calendar-share-row]').querySelector('[data-calendar-share-link]');
                        function manual() { input.focus(); input.select(); show(text.copy_manual, false); }
                        if (!navigator.clipboard) { manual(); return; }
                        navigator.clipboard.writeText(input.value).then(function () { show(text.copied, true); }).catch(manual);
                    }
                    var revoke = event.target.closest('[data-calendar-share-revoke]');
                    if (revoke) {
                        var data = new FormData();
                        data.set('action', 'revoke'); data.set('id', revoke.dataset.calendarShareRevoke); data.set('csrf', panel.dataset.csrf);
                        post(data);
                    }
                });
            });
        }
        load().catch(function () {
            placeholder.textContent = errorText;
            window.commonTopbarOpenModal(title, placeholder.outerHTML, 'html');
        });
    };
})(window, document);
