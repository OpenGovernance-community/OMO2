var omoUiText = window.omoUiText || function (key, fallback) { return fallback; };
var omoUiHtml = window.omoUiHtml || function (key, fallback) { return fallback; };
window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/shares/popup.js"] = function (pageConfig, pageScript) {
(function () {
    const defaultLabel = pageConfig.defaultLabel;
    const text = pageConfig.text;

    window.omoSharePopupInit = function (popupRoot) {
        const root = popupRoot || document.getElementById('omoSharePopupRoot');
        if (!root || root.dataset.ready === '1') {
            return;
        }

        root.dataset.ready = '1';

        const hasExistingLinks = root.dataset.hasLinks === '1';
        const popupUrl = root.dataset.popupUrl || 'api/shares/popup.php';
        const feedback = document.getElementById('omoSharePopupFeedback');
        const listSection = document.getElementById('omoSharePopupListSection');
        const formSection = document.getElementById('omoSharePopupFormSection');
        const form = document.getElementById('omoSharePopupForm');
        const title = document.getElementById('omoSharePopupFormTitle');
        const intro = document.getElementById('omoSharePopupFormIntro');
        const shareIdInput = document.getElementById('omoSharePopupShareId');
        const labelInput = document.getElementById('omoSharePopupLabel');
        const expirationInput = document.getElementById('omoSharePopupExpiration');
        const passwordHint = document.getElementById('omoSharePopupPasswordHint');
        const clearPasswordWrap = document.getElementById('omoSharePopupClearPasswordWrap');
        const clearPasswordInput = document.getElementById('omoSharePopupClearPassword');
        const allowStructure = document.getElementById('omoSharePopupAllowStructure');
        const allowPeople = document.getElementById('omoSharePopupAllowPeople');
        const allowPeopleDetail = document.getElementById('omoSharePopupAllowPeopleDetail');
        const submitButton = document.getElementById('omoSharePopupSubmit');

        const resolveUrl = function (url) {
            if (typeof window.omoResolveAppUrl === 'function') {
                return window.omoResolveAppUrl(url);
            }

            return url;
        };

        const setFeedback = function (message, isSuccess) {
            if (message && typeof window.commonNotify === 'function') {
                var notice = feedback;
                if (notice) { notice.textContent = ''; notice.hidden = true; }
                window.commonNotify(String(message), isSuccess ? 'success' : 'error');
                return;
            }

            if (!feedback) {
                return;
            }

            feedback.hidden = false;
            feedback.textContent = message || '';
            feedback.classList.toggle('is-success', Boolean(isSuccess));
        };

        const syncPermissionDependencies = function () {
            if (!allowPeople || !allowPeopleDetail) {
                return;
            }

            if (!allowPeople.checked) {
                allowPeopleDetail.checked = false;
            }

            allowPeopleDetail.disabled = !allowPeople.checked;
        };

        const resetForm = function () {
            form.reset();
            shareIdInput.value = '';
            labelInput.value = defaultLabel;
            clearPasswordInput.checked = false;
            clearPasswordWrap.hidden = true;
            allowStructure.checked = true;
            allowPeople.checked = false;
            allowPeopleDetail.checked = false;
            passwordHint.textContent = text.passwordHint;
            submitButton.textContent = omoUiText("creer_le_lien", "Créer le lien");
            title.textContent = text.titleNew;
            intro.textContent = text.intro;
            syncPermissionDependencies();
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
            labelInput.focus({preventScroll: true});
        };

        const openFormForEdit = function (card) {
            if (!card) {
                return;
            }

            resetForm();
            shareIdInput.value = card.dataset.shareId || '';
            labelInput.value = card.dataset.label || defaultLabel;
            expirationInput.value = card.dataset.dateexpiration || '';
            allowStructure.checked = card.dataset.allowStructure === '1';
            allowPeople.checked = card.dataset.allowPeople === '1';
            allowPeopleDetail.checked = card.dataset.allowPeopleDetail === '1';
            clearPasswordWrap.hidden = card.dataset.hasPassword !== '1';
            passwordHint.textContent = text.passwordHintEdit;
            submitButton.textContent = omoUiText("enregistrer", "Enregistrer");
            title.textContent = text.titleEdit;
            intro.textContent = text.intro;
            syncPermissionDependencies();

            if (listSection) {
                listSection.hidden = true;
            }
            if (formSection) {
                formSection.hidden = false;
            }
            setFeedback('', false);
            labelInput.focus({preventScroll: true});
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
            window.omoSharePopupFlash = flashMessage ? {
                message: flashMessage,
                success: Boolean(isSuccess)
            } : null;

            const response = await fetch(resolveUrl(popupUrl), {
                method: 'GET',
                credentials: 'same-origin'
            });

            const html = await response.text();
            const container = root.parentNode;
            if (container) {
                container.innerHTML = html;
                if (typeof window.omoSharePopupInit === 'function') {
                    window.omoSharePopupInit(container.querySelector('#omoSharePopupRoot'));
                }
            }
        };

        if (window.omoSharePopupFlash && window.omoSharePopupFlash.message) {
            setFeedback(window.omoSharePopupFlash.message, window.omoSharePopupFlash.success);
            window.omoSharePopupFlash = null;
        }

        syncPermissionDependencies();
        if (allowPeople) {
            allowPeople.addEventListener('change', syncPermissionDependencies);
        }

        root.addEventListener('click', async function (event) {
            const newButton = event.target.closest('#omoSharePopupNewButton');
            if (newButton && root.contains(newButton)) {
                openFormForCreate();
                return;
            }

            const cancelButton = event.target.closest('#omoSharePopupCancelButton');
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
                    setFeedback(omoUiText("lien_copie_2", "Lien copié."), true);
                } catch (error) {
                    setFeedback(omoUiText("copie_impossible_automatiquement", "Copie impossible automatiquement."), false);
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

            if (!window.confirm(omoUiText("share_delete_confirm", 'Supprimer le lien « ' + label + ' » ?', {label: label}))) {
                return;
            }

            setFeedback('', false);
            deleteButton.disabled = true;

            try {
                const formData = new FormData();
                formData.append('oid', root.dataset.oid || '');
                formData.append('cid', root.dataset.cid || '');
                formData.append('share_id', String(shareId));

                const response = await fetch(resolveUrl('api/shares/delete.php'), {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || !payload.status) {
                    throw new Error(payload && payload.message ? payload.message : omoUiText("erreur_de_suppression", "Erreur de suppression."));
                }

                await refreshPopup(payload.message || omoUiText("lien_supprime_2", "Lien supprimé."), true);
            } catch (error) {
                setFeedback(error && error.message ? error.message : omoUiText("erreur_de_suppression", "Erreur de suppression."), false);
                deleteButton.disabled = false;
            }
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            setFeedback('', false);
            submitButton.disabled = true;
            syncPermissionDependencies();

            try {
                const response = await fetch(resolveUrl('api/shares/create.php'), {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin'
                });

                const payload = await response.json();
                if (!response.ok || !payload || !payload.status) {
                    throw new Error(payload && payload.message ? payload.message : omoUiText("erreur_de_sauvegarde", "Erreur de sauvegarde."));
                }

                await refreshPopup(payload.message || omoUiText("lien_enregistre_2", "Lien enregistré."), true);
            } catch (error) {
                setFeedback(error && error.message ? error.message : omoUiText("erreur_de_sauvegarde", "Erreur de sauvegarde."), false);
                submitButton.disabled = false;
            }
        });
    };

    window.omoSharePopupInit(document.getElementById('omoSharePopupRoot'));
})();
};
