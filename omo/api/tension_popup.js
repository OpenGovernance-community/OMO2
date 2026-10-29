var omoUiText = window.omoUiText || function (key, fallback) { return fallback; };
var omoUiHtml = window.omoUiHtml || function (key, fallback) { return fallback; };
(function () {
    if (typeof window.__omoPopupCleanup === 'function') {
        window.__omoPopupCleanup();
    }

    const root = document.getElementById('omoTensionPopup');
    if (!root) {
        return;
    }

    const form = document.getElementById('omoTensionForm');
    const feedback = document.getElementById('omoTensionFeedback');
    const titleInput = document.getElementById('omoTensionTitle');
    const titleHint = root.querySelector('[data-omo-tension-title-hint]');
    const submitButton = document.getElementById('omoTensionSubmit');
    const cancelButton = root.querySelector('[data-omo-tension-cancel]');

    function setFeedback(message, type) {
        if (message && typeof window.commonNotify === 'function') {
            var notice = feedback;
            if (notice) { notice.textContent = ''; notice.hidden = true; }
            window.commonNotify(String(message), type);
            return;
        }

        if (!feedback) {
            return;
        }

        feedback.hidden = false;
        feedback.textContent = message || '';
        feedback.classList.remove('is-error', 'is-success');
        if (type === 'error') {
            feedback.classList.add('is-error');
        } else if (type === 'success') {
            feedback.classList.add('is-success');
        }
    }

    function countWords(value) {
        const normalized = String(value || '').trim();
        if (normalized === '') {
            return 0;
        }

        return normalized.split(/\s+/).filter(Boolean).length;
    }

    function refreshTitleHint() {
        if (!titleInput || !titleHint) {
            return true;
        }

        const wordCount = countWords(titleInput.value);
        const isValid = wordCount <= 3;
        titleHint.textContent = wordCount > 0
            ? wordCount + omoUiText("3_mots", " / 3 mots")
            : omoUiText("3_mots_maximum", "3 mots maximum.");
        titleHint.style.color = isValid ? '' : '#b91c1c';

        return isValid;
    }

    if (titleInput) {
        titleInput.addEventListener('input', function () {
            refreshTitleHint();
        });
        refreshTitleHint();
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            if (typeof window.commonTopbarCloseModal === 'function') {
                window.commonTopbarCloseModal();
            }
        });
    }

    if (!form) {
        window.__omoPopupCleanup = function () {};
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!refreshTitleHint()) {
            setFeedback(omoUiText("le_titre_doit_contenir_au_maximum_3_mots", "Le titre doit contenir au maximum 3 mots."), 'error');
            if (titleInput) {
                titleInput.focus();
            }
            return;
        }

        const submitUrl = root.getAttribute('data-submit-url') || '/omo/api/tension_save.php';
        const formData = new FormData(form);
        setFeedback(omoUiText("enregistrement", "Enregistrement..."), '');

        if (submitButton) {
            submitButton.disabled = true;
        }

        fetch(submitUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.json().catch(function () {
                    return null;
                }).then(function (data) {
                    return {
                        ok: response.ok,
                        data: data
                    };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.status) {
                    throw new Error(result && result.data && result.data.message ? result.data.message : 'save_failed');
                }

                setFeedback(result.data.message || omoUiText("tension_enregistree", "Tension enregistrée."), 'success');
                form.reset();
                refreshTitleHint();

                window.setTimeout(function () {
                    if (typeof window.commonTopbarCloseModal === 'function') {
                        window.commonTopbarCloseModal();
                    }
                }, 700);
            })
            .catch(function (error) {
                const message = error && error.message && error.message !== 'save_failed'
                    ? error.message
                    : omoUiText("impossible_d_enregistrer_la_tension", "Impossible d’enregistrer la tension.");
                setFeedback(message, 'error');
            })
            .finally(function () {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            });
    });

    window.__omoPopupCleanup = function () {};
})();
