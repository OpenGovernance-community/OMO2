window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/documents/share_popup.js"] = function (pageConfig, pageScript) {
(function () {
    const defaultLabel = pageConfig.defaultLabel;
    const text = pageConfig.text;

    window.omoDocumentSharePopupInit = function (popupRoot) {
        const root = popupRoot || document.getElementById('omoDocumentSharePopupRoot');
        if (!root || root.dataset.ready === '1') {
            return;
        }

        root.dataset.ready = '1';

        const hasExistingLinks = root.dataset.hasLinks === '1';
        const popupUrl = root.dataset.popupUrl || '/omo/api/documents/share_popup.php';
        const feedback = document.getElementById('omoDocumentSharePopupFeedback');
        const listSection = document.getElementById('omoDocumentSharePopupListSection');
        const formSection = document.getElementById('omoDocumentSharePopupFormSection');
        const form = document.getElementById('omoDocumentSharePopupForm');
        const title = document.getElementById('omoDocumentSharePopupFormTitle');
        const intro = document.getElementById('omoDocumentSharePopupFormIntro');
        const shareIdInput = document.getElementById('omoDocumentSharePopupShareId');
        const labelInput = document.getElementById('omoDocumentSharePopupLabel');
        const expirationInput = document.getElementById('omoDocumentSharePopupExpiration');
        const allowLiveFollow = document.getElementById('omoDocumentSharePopupAllowLiveFollow');
        const passwordHint = document.getElementById('omoDocumentSharePopupPasswordHint');
        const clearPasswordWrap = document.getElementById('omoDocumentSharePopupClearPasswordWrap');
        const clearPasswordInput = document.getElementById('omoDocumentSharePopupClearPassword');
        const submitButton = document.getElementById('omoDocumentSharePopupSubmit');

        const setFeedback = function (message, isSuccess) {
            if (!feedback) {
                return;
            }

            feedback.textContent = message || '';
            feedback.classList.toggle('is-success', Boolean(isSuccess));
        };

        const resetForm = function () {
            form.reset();
            shareIdInput.value = '';
            labelInput.value = defaultLabel;
            allowLiveFollow.checked = false;
            clearPasswordInput.checked = false;
            clearPasswordWrap.hidden = true;
            passwordHint.textContent = text.passwordHint || '';
            submitButton.textContent = text.submitCreate || '';
            title.textContent = hasExistingLinks ? (text.titleNew || '') : (text.titleFirst || '');
            intro.textContent = hasExistingLinks
                ? (text.introNew || '')
                : (text.introFirst || '');
        };

        const openFormForCreate = function () {
            resetForm();
            if (listSection) {
                listSection.hidden = true;
            }
            if (formSection) {
                formSection.hidden = false;
            }
            setFeedback('', false);
        };

        const openFormForEdit = function (card) {
            if (!card) {
                return;
            }

            resetForm();
            shareIdInput.value = card.dataset.shareId || '';
            labelInput.value = card.dataset.label || defaultLabel;
            expirationInput.value = card.dataset.dateexpiration || '';
            allowLiveFollow.checked = card.dataset.allowLiveFollow === '1';
            clearPasswordWrap.hidden = card.dataset.hasPassword !== '1';
            passwordHint.textContent = text.passwordHintEdit || '';
            submitButton.textContent = text.submitSave || '';
            title.textContent = text.titleEdit || '';
            intro.textContent = text.introEdit || '';

            if (listSection) {
                listSection.hidden = true;
            }
            if (formSection) {
                formSection.hidden = false;
            }
            setFeedback('', false);
        };

        const showList = function () {
            if (!hasExistingLinks) {
                return;
            }

            if (listSection) {
                listSection.hidden = false;
            }
            if (formSection) {
                formSection.hidden = true;
            }
            setFeedback('', false);
        };

        const refreshPopup = async function (flashMessage, isSuccess) {
            window.omoDocumentSharePopupFlash = flashMessage ? {
                message: flashMessage,
                success: Boolean(isSuccess)
            } : null;

            const response = await fetch(popupUrl, {
                method: 'GET',
                credentials: 'same-origin'
            });

            const html = await response.text();
            const container = root.parentNode;
            if (container) {
                container.innerHTML = html;
                if (typeof window.omoDocumentSharePopupInit === 'function') {
                    window.omoDocumentSharePopupInit(container.querySelector('#omoDocumentSharePopupRoot'));
                }
            }
        };

        if (window.omoDocumentSharePopupFlash && window.omoDocumentSharePopupFlash.message) {
            setFeedback(window.omoDocumentSharePopupFlash.message, window.omoDocumentSharePopupFlash.success);
            window.omoDocumentSharePopupFlash = null;
        }

        root.addEventListener('click', async function (event) {
            const newButton = event.target.closest('#omoDocumentSharePopupNewButton');
            if (newButton && root.contains(newButton)) {
                openFormForCreate();
                return;
            }

            const cancelButton = event.target.closest('#omoDocumentSharePopupCancelButton');
            if (cancelButton && root.contains(cancelButton)) {
                showList();
                return;
            }

            const copyButton = event.target.closest('[data-share-copy="1"]');
            if (copyButton && root.contains(copyButton)) {
                const card = copyButton.closest('[data-share-card="1"]');
                const url = card ? (card.dataset.url || '') : '';

                if (!url) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(url);
                    setFeedback('Lien copié.', true);
                } catch (error) {
                    setFeedback('Impossible de copier automatiquement.', false);
                }
                return;
            }

            const editButton = event.target.closest('[data-share-edit="1"]');
            if (editButton && root.contains(editButton)) {
                openFormForEdit(editButton.closest('[data-share-card="1"]'));
                return;
            }

            const deleteButton = event.target.closest('[data-share-delete="1"]');
            if (!deleteButton || !root.contains(deleteButton)) {
                return;
            }

            const card = deleteButton.closest('[data-share-card="1"]');
            const shareId = card ? Number(card.dataset.shareId || 0) : 0;
            const label = card ? (card.dataset.label || defaultLabel) : defaultLabel;

            if (!shareId) {
                return;
            }

            if (!window.confirm('Supprimer le lien "' + label + '" ?')) {
                return;
            }

            const response = await fetch('/omo/api/documents/share_delete.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    id: String(root.dataset.documentId || ''),
                    share_id: String(shareId)
                }).toString()
            });

            const payload = await response.json().catch(function () {
                return null;
            });

            if (!response.ok || !payload || payload.status !== true) {
                setFeedback(payload && payload.message ? payload.message : 'Suppression impossible.', false);
                return;
            }

            refreshPopup('Lien supprimé.', true);
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            setFeedback('', false);

            const formData = new FormData(form);
            if (
                typeof window.omoBeginPendingAction === 'function'
                && !window.omoBeginPendingAction(form)
            ) {
                return;
            }

            const submitButton = form.querySelector('[type="submit"]');
            if (typeof window.omoBeginPendingAction !== 'function' && submitButton) {
                submitButton.disabled = true;
            }

            try {
                const response = await fetch('/omo/api/documents/share_create.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const payload = await response.json().catch(function () {
                    return null;
                });

                if (!response.ok || !payload || payload.status !== true) {
                    setFeedback(payload && payload.message ? payload.message : 'Enregistrement impossible.', false);
                    return;
                }

                refreshPopup(payload.message || 'Lien enregistré.', true);
            } catch (error) {
                setFeedback('Enregistrement impossible.', false);
            } finally {
                if (typeof window.omoEndPendingAction === 'function') {
                    window.omoEndPendingAction(form);
                } else if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        });
    };

    window.omoDocumentSharePopupInit();
})();
};
