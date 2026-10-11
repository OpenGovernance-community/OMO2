(function (window, document) {
    'use strict';
    if (window.omoCalendarInitInvitationTreeScroll) {
        window.omoCalendarInitInvitationTreeScroll(document);
        return;
    }

    var pending = new Map();
    var loading = new Map();

    function loadInvitations(host) {
        if (!host.isConnected || host.dataset.loadState || host.closest('[hidden], details:not([open])')) { return; }
        var controller = new AbortController();
        loading.set(host, controller);
        host.dataset.loadState = 'loading';
        var feedback = host.querySelector('[data-calendar-invitations-feedback]');
        feedback.textContent = host.dataset.loadingLabel || '';
        host.setAttribute('aria-busy', 'true');
        fetch(host.dataset.calendarInvitationsLazy, {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}, signal: controller.signal})
            .then(function (response) { if (!response.ok) { throw new Error('invitations'); } return response.text(); })
            .then(function (html) {
                if (!host.isConnected || controller.signal.aborted) { return; }
                // Keep the form's original/draft selections even if it was saved or changed during the fetch.
                var placeholder = host.querySelector('[data-omo-calendar-invitations-placeholder]');
                var selected = placeholder ? Array.from(placeholder.querySelectorAll('input:checked')).map(function (field) { return field.name + ':' + field.value; }) : null;
                var emails = placeholder ? placeholder.querySelector('[name="invitation_emails"]').value : '';
                var usesDefault = placeholder ? placeholder.dataset.omoCalendarUsesDefaultSelection : '';
                var defaultHolon = placeholder ? placeholder.dataset.omoCalendarDefaultHolonId : '';
                var fragment = document.createElement('template');
                fragment.innerHTML = html;
                if (placeholder && !fragment.content.querySelector('[data-omo-calendar-invitations-editor]')) { throw new Error('invalid editor'); }
                if (selected) {
                    fragment.content.querySelectorAll('input[type="checkbox"]').forEach(function (field) {
                        if (field.name === 'invitation_holon_ids[]' || field.name === 'invitation_user_ids[]') { field.checked = selected.includes(field.name + ':' + field.value); }
                    });
                    fragment.content.querySelector('[name="invitation_emails"]').value = emails;
                    var editor = fragment.content.querySelector('[data-omo-calendar-invitations-editor]');
                    editor.dataset.omoCalendarUsesDefaultSelection = usesDefault;
                    editor.dataset.omoCalendarDefaultHolonId = defaultHolon;
                }
                host.replaceChildren(fragment.content);
                host.dataset.loadState = 'loaded';
                if (window.initGenericComponents) { window.initGenericComponents(host); }
                if (window.omoCalendarInitInvitationEditors) { window.omoCalendarInitInvitationEditors(host); }
                host.dispatchEvent(new Event('omo-calendar-invitations-loaded', {bubbles: true}));
                init(host);
            }).catch(function (error) {
                if (error.name === 'AbortError' || !host.isConnected) { return; }
                host.dataset.loadState = 'error';
                var retry = document.createElement('button');
                retry.type = 'button';
                retry.className = 'generic-action-button generic-action-button--secondary';
                retry.textContent = host.dataset.errorLabel || '';
                retry.addEventListener('click', function () { delete host.dataset.loadState; loadInvitations(host); });
                feedback.replaceChildren(retry);
            }).finally(function () { loading.delete(host); host.removeAttribute('aria-busy'); });
    }

    function loadVisible(scope) {
        if (scope.matches && scope.matches('[data-calendar-invitations-lazy]')) { loadInvitations(scope); }
        scope.querySelectorAll('[data-calendar-invitations-lazy]').forEach(loadInvitations);
        loading.forEach(function (controller, host) { if (!host.isConnected) { controller.abort(); loading.delete(host); } });
    }

    function scrollToSelection(list) {
        // Hidden tabs have no layout. Wait until the tree and its styles are visible.
        if (!list.isConnected || !list.clientHeight) {
            return;
        }
        var scroller = list;
        while (scroller && !['auto', 'scroll'].includes(window.getComputedStyle(scroller).overflowY)) {
            scroller = scroller.parentElement;
        }
        if (!scroller || !scroller.clientHeight) { return; }
        var selected = list.querySelector('input[type="checkbox"]:checked');
        if (selected) {
            // Reveal only its ancestors, leaving the selected circle's own roles folded.
            var branch = selected.closest('[data-omo-calendar-holon-children]');
            while (branch && list.contains(branch)) {
                branch.hidden = false;
                var parent = branch.parentElement;
                var toggle = parent.querySelector('[data-omo-calendar-holon-toggle]');
                if (toggle) { toggle.setAttribute('aria-expanded', 'true'); }
                branch = parent.parentElement.closest('[data-omo-calendar-holon-children]');
            }
            var row = selected.closest('.omo-calendar-invitations-editor__tree-row');
            var bounds = row.getBoundingClientRect();
            scroller.scrollTop += bounds.top - scroller.getBoundingClientRect().top - Math.max(0, (scroller.clientHeight - bounds.height) / 2);
        }
        pending.get(list).disconnect();
        pending.delete(list);
    }

    function init(scope) {
        loadVisible(scope);
        var lists = Array.from(scope.querySelectorAll('[data-omo-calendar-holon-list]'));
        if (scope.matches && scope.matches('[data-omo-calendar-holon-list]')) { lists.push(scope); }
        lists.forEach(function (list) {
            if (list.dataset.omoCalendarScrollReady === '1') { return; }
            list.dataset.omoCalendarScrollReady = '1';
            var observer = new ResizeObserver(function () {
                window.requestAnimationFrame(function () {
                    if (pending.has(list)) { scrollToSelection(list); }
                });
            });
            pending.set(list, observer);
            observer.observe(list);
        });
    }

    window.omoCalendarInitInvitationTreeScroll = init;
    new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (node.nodeType === 1) { init(node); }
            });
        });
        pending.forEach(function (observer, list) {
            if (!list.isConnected) { observer.disconnect(); pending.delete(list); }
        });
        loadVisible(document);
    }).observe(document.documentElement, {childList: true, subtree: true});
    new MutationObserver(function () { loadVisible(document); }).observe(document.documentElement, {subtree: true, attributes: true, attributeFilter: ['hidden', 'open']});
    init(document);
})(window, document);
