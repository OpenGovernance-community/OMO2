(function () {
    'use strict';
    const drawer = document.querySelector('[data-pv-document-url]');
    if (!drawer) return;
    const defaultTitle = drawer.querySelector('[data-meeting-document-title]').textContent;
    function openHash(hash, title) {
        const match = /^#documents-d(\d+)$/.exec(hash);
        if (!match) return false;
        const url = new URL(drawer.dataset.pvDocumentUrl, window.location.origin);
        url.searchParams.set('id', match[1]);
        drawer.dispatchEvent(new CustomEvent('meeting-document-open', {detail: {url: url.href, title: title || defaultTitle}}));
        return true;
    }
    // Capture before the embedded editor's app-only navigation handler.
    function routeDocumentClick(event) {
        const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
        if (!link) return;
        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin || url.pathname !== window.location.pathname || !/^#documents-d\d+$/.test(url.hash)) return;
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0 || link.target === '_blank') {
            // Old embedded external links omitted the participation token.
            const external = new URL(window.location.href);
            external.hash = url.hash;
            link.href = external.href;
            event.stopPropagation();
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        if (window.location.hash !== url.hash) history.pushState(null, '', url.hash);
        openHash(url.hash, link.textContent.trim());
    }
    window.addEventListener('click', routeDocumentClick, true);
    window.addEventListener('auxclick', routeDocumentClick, true);
    window.addEventListener('hashchange', function () {
        if (!openHash(window.location.hash) && drawer.open) drawer.close();
    });
    drawer.addEventListener('close', function () {
        if (/^#documents-d\d+$/.test(window.location.hash)) {
            history.replaceState(null, '', window.location.pathname + window.location.search);
        }
    });
    openHash(window.location.hash);
}());
