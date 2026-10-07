(function (window, document) {
    'use strict';
    if (window.omoOpenResourceArchives) return;
    var current = null;
    window.omoOpenResourceArchives = function (options) {
        if (typeof window.commonTopbarOpenModal !== 'function') return false;
        current = options;
        window.commonTopbarOpenModal(options.title, options.url, 'fetch');
        return true;
    };
    document.addEventListener('click', function (event) {
        var link = event.target.closest('[data-resource-archives] [data-resource-archive-link]');
        if (!link || !current || !current.root.isConnected) return;
        var id = Number(link.getAttribute('data-resource-id'));
        if (!Number.isInteger(id) || id <= 0) return;
        event.preventDefault();
        event.stopPropagation();
        var options = current;
        current = null;
        if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal();
        options.onOpen(id);
    }, true);
})(window, document);
