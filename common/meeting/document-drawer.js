(function () {
    'use strict';
    const drawer = document.querySelector('[data-meeting-document-drawer]');
    if (!drawer || typeof drawer.showModal !== 'function') return;
    let frame = drawer.querySelector('iframe');
    const title = drawer.querySelector('[data-meeting-document-title]');
    const externalLink = drawer.querySelector('[data-meeting-document-external]');
    const closeButton = drawer.querySelector('[data-meeting-document-close]');
    function openDocument(url, label) {
        title.textContent = label;
        frame.title = title.textContent;
        externalLink.href = url;
        frame.src = url;
        if (!drawer.open) drawer.showModal();
        document.body.classList.add('meeting-document-open');
        closeButton.focus();
    }
    drawer.addEventListener('meeting-document-open', function (event) {
        openDocument(event.detail.url, event.detail.title);
    });
    document.addEventListener('click', function (event) {
        const link = event.target.closest('[data-meeting-document-open]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        openDocument(link.href, link.dataset.meetingDocumentTitle);
    });
    closeButton.addEventListener('click', function () { drawer.close(); });
    drawer.addEventListener('click', function (event) {
        if (event.target !== drawer) return;
        const rect = drawer.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) drawer.close();
    });
    drawer.addEventListener('close', function () {
        // Removing the browsing context also removes its entries from browser history.
        const emptyFrame = frame.cloneNode(false);
        emptyFrame.removeAttribute('src');
        frame.replaceWith(emptyFrame);
        frame = emptyFrame;
        document.body.classList.remove('meeting-document-open');
    });
}());
