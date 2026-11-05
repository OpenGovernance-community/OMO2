(() => {
    let dialog;
    let opener;

    window.commonOpenIframePopup = ({url, title, closeLabel, trigger}) => {
        if (!dialog) {
            dialog = document.createElement('dialog');
            dialog.className = 'common-iframe-popup';
            dialog.setAttribute('aria-labelledby', 'commonIframePopupTitle');
            dialog.innerHTML = '<header><strong id="commonIframePopupTitle"></strong><button type="button" class="common-topbar-modal__close"><span aria-hidden="true">&times;</span></button></header>';
            dialog.querySelector('button').addEventListener('click', () => dialog.close());
            dialog.addEventListener('click', (event) => {
                if (event.target !== dialog) return;
                const rect = dialog.getBoundingClientRect();
                if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
            });
            dialog.addEventListener('close', () => {
                dialog.querySelector('iframe')?.remove();
                document.documentElement.classList.remove('common-iframe-popup-open');
                opener?.focus({preventScroll: true});
            });
            document.body.append(dialog);
        }
        opener = trigger || document.activeElement;
        dialog.querySelector('strong').textContent = title;
        const close = dialog.querySelector('button');
        close.title = closeLabel;
        close.setAttribute('aria-label', closeLabel);
        dialog.querySelector('iframe')?.remove();
        const frame = document.createElement('iframe');
        frame.title = title;
        frame.src = url;
        frame.addEventListener('load', () => {
            // The popup is intended for local pages; Escape must work inside the iframe too.
            try {
                frame.contentDocument?.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') dialog.close();
                });
            } catch {}
        });
        dialog.append(frame);
        if (!dialog.open) dialog.showModal();
        document.documentElement.classList.add('common-iframe-popup-open');
    };
})();
