(function (window) {
    'use strict';
    if (window.omoCreateDocumentProgressiveList) return;

    const activeLists = new Set();
    let removalObserver = null;

    window.omoCreateDocumentProgressiveList = function (options) {
        const container = options.container;
        const items = options.items;
        const more = container.ownerDocument.createElement('button');
        more.type = 'button';
        more.className = 'generic-action-button generic-action-button--secondary';
        more.textContent = options.moreLabel;
        more.setAttribute('data-omo-documents-load-more', '1');
        let offset = 0;
        let stopped = false;
        let observer = null;
        const controller = {container: container, destroy: destroy};

        function destroy() {
            stopped = true;
            if (observer) observer.disconnect();
            more.removeEventListener('click', appendNext);
            more.remove();
            activeLists.delete(controller);
            if (activeLists.size === 0 && removalObserver) {
                removalObserver.disconnect();
                removalObserver = null;
            }
        }

        function appendNext(count) {
            if (stopped) return;
            if (observer) observer.unobserve(more);
            const end = Math.min(items.length, offset + (typeof count === 'number' ? count : 30));
            while (offset < end) options.appendItem(items[offset++], more);
            if (options.afterAppend) options.afterAppend(offset);
            if (offset === items.length) {
                destroy();
            } else if (observer) {
                // Re-arm if a short batch still leaves the sentinel in the viewport.
                observer.observe(more);
            }
        }

        container.appendChild(more);
        more.addEventListener('click', appendNext);
        if (typeof window.IntersectionObserver === 'function') {
            observer = new window.IntersectionObserver(function (entries) {
                if (!container.isConnected) { destroy(); return; }
                if (entries.some(function (entry) { return entry.isIntersecting; })) appendNext();
            }, {rootMargin: '150px 0px'});
        }
        activeLists.add(controller);
        if (!removalObserver && typeof window.MutationObserver === 'function') {
            removalObserver = new window.MutationObserver(function (records) {
                if (!records.some(function (record) { return record.removedNodes.length > 0; })) return;
                activeLists.forEach(function (list) {
                    if (!list.container.isConnected) list.destroy();
                });
            });
            removalObserver.observe(container.ownerDocument.documentElement, {childList: true, subtree: true});
        }
        appendNext(Math.max(30, Number(options.initialCount) || 30));
        return controller;
    };
})(window);
