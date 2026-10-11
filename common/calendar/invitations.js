(function (window, document) {
    'use strict';
    if (window.omoCalendarInitInvitationTreeScroll) {
        window.omoCalendarInitInvitationTreeScroll(document);
        return;
    }

    var pending = new Map();

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
    }).observe(document.documentElement, {childList: true, subtree: true});
    init(document);
})(window, document);
