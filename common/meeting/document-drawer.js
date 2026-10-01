(function () {
    'use strict';
    const drawer = document.querySelector('[data-meeting-document-drawer]');
    if (!drawer || typeof drawer.showModal !== 'function') return;
    const frame = drawer.querySelector('iframe');
    const title = drawer.querySelector('[data-meeting-document-title]');
    const externalLink = drawer.querySelector('[data-meeting-document-external]');
    const closeButton = drawer.querySelector('[data-meeting-document-close]');
    document.addEventListener('click', function (event) {
        const link = event.target.closest('[data-meeting-document-open]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        title.textContent = link.dataset.meetingDocumentTitle;
        frame.title = title.textContent;
        externalLink.href = link.href;
        frame.src = link.href;
        drawer.showModal();
        document.body.classList.add('meeting-document-open');
        closeButton.focus();
    });
    closeButton.addEventListener('click', function () { drawer.close(); });
    drawer.addEventListener('click', function (event) {
        if (event.target !== drawer) return;
        const rect = drawer.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) drawer.close();
    });
    drawer.addEventListener('close', function () {
        frame.removeAttribute('src');
        document.body.classList.remove('meeting-document-open');
    });
}());
