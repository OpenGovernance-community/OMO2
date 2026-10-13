(function (window, document) {
    'use strict';
    if (window.omoProposalDescription) return;

    var selector = '[data-omo-proposal-description]';
    var states = new WeakMap();
    var pending = new Set();
    var frame = 0;
    var nextId = 0;
    var resizeObserver = window.ResizeObserver ? new ResizeObserver(function (entries) {
        entries.forEach(function (entry) { schedule(entry.target.closest(selector)); });
    }) : null;

    function refresh(host) {
        var state = states.get(host);
        if (!state || !host.isConnected || !state.content.getClientRects().length || !state.content.clientWidth) return;
        var lineHeight = parseFloat(window.getComputedStyle(state.content).lineHeight);
        if (!Number.isFinite(lineHeight)) return;
        var isLong = state.content.scrollHeight > lineHeight * 5 + 1;
        var expanded = isLong && state.button.getAttribute('aria-expanded') === 'true';
        state.content.setAttribute('data-collapsed', isLong && !expanded ? 'true' : 'false');
        state.button.hidden = !isLong;
        state.button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        state.more.hidden = expanded;
        state.less.hidden = !expanded;
    }

    function schedule(host) {
        if (!host || !states.has(host)) return;
        pending.add(host);
        if (!frame) frame = window.requestAnimationFrame(function () {
            frame = 0;
            pending.forEach(refresh);
            pending.clear();
        });
    }

    function mount(host) {
        if (states.has(host)) { schedule(host); return; }
        var content = host.querySelector('[data-omo-proposal-description-content]');
        var button = host.querySelector('[data-omo-proposal-description-toggle]');
        if (!content || !button) return;
        if (!content.id) {
            var id;
            do { id = 'omo-proposal-description-' + (++nextId); } while (document.getElementById(id));
            content.id = id;
        }
        button.setAttribute('aria-controls', content.id);
        states.set(host, {content: content, button: button,
            more: button.querySelector('[data-omo-proposal-description-more]'),
            less: button.querySelector('[data-omo-proposal-description-less]')});
        if (resizeObserver) resizeObserver.observe(content);
        schedule(host);
    }

    function visit(root, callback) {
        if (root.nodeType !== 1 && root !== document) return;
        if (root.matches && root.matches(selector)) callback(root);
        root.querySelectorAll(selector).forEach(callback);
    }

    function unmount(host) {
        var state = states.get(host);
        if (state && resizeObserver) resizeObserver.unobserve(state.content);
        states.delete(host);
        pending.delete(host);
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-omo-proposal-description-toggle]');
        if (!button) return;
        var host = button.closest(selector);
        mount(host);
        button.setAttribute('aria-expanded', button.getAttribute('aria-expanded') === 'true' ? 'false' : 'true');
        refresh(host);
    });
    document.addEventListener('load', function (event) {
        if (event.target.closest) schedule(event.target.closest(selector));
    }, true);

    function start() {
        visit(document, mount);
        if (window.MutationObserver) new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                // Text and rich HTML can change after a proposal is edited or polled.
                var target = mutation.target.nodeType === 1 ? mutation.target : mutation.target.parentElement;
                if (target && target.closest) schedule(target.closest(selector));
                Array.prototype.forEach.call(mutation.removedNodes || [], function (node) { visit(node, unmount); });
                Array.prototype.forEach.call(mutation.addedNodes || [], function (node) { visit(node, mount); });
            });
        }).observe(document.documentElement, {childList: true, characterData: true, subtree: true});
        if (document.fonts) document.fonts.ready.then(function () { visit(document, schedule); });
    }

    window.omoProposalDescription = {
        create: function () {
            var template = document.querySelector('[data-omo-proposal-description-template]');
            return template ? template.content.firstElementChild.cloneNode(true) : null;
        }
    };
    if (!resizeObserver) window.addEventListener('resize', function () { visit(document, schedule); });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once: true});
    else start();
})(window, document);
