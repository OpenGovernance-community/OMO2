    (function () {
        var form = document.getElementById('omoAccessRequestPopupForm');
        var feedback = document.getElementById('omoAccessRequestPopupFeedback');
        var submitButton = document.getElementById('omoAccessRequestPopupSubmit');

        if (!form || !feedback || !submitButton) {
            return;
        }

        function closeModal() {
            if (typeof window.commonTopbarCloseModal === 'function') {
                window.commonTopbarCloseModal();
                return;
            }

            if (window.parent && window.parent !== window && typeof window.parent.commonTopbarCloseModal === 'function') {
                window.parent.commonTopbarCloseModal();
            }
        }

        function notifyParent(result) {
            try {
                window.dispatchEvent(new CustomEvent('omo-access-request-submitted', {
                    detail: result || {}
                }));
            } catch (error) {
            }

            if (window.parent && window.parent !== window) {
                try {
                    window.parent.dispatchEvent(new CustomEvent('omo-access-request-submitted', {
                        detail: result || {}
                    }));
                } catch (error) {
                }
            }
        }

        function showFeedback(message, isError) {
            if (typeof window.commonNotify === 'function') {
                window.commonNotify(String(message || ''), isError ? 'error' : 'success');
                feedback.textContent = '';
                feedback.classList.remove('is-success');
                return;
            }

            feedback.textContent = String(message || '');
            feedback.classList.toggle('is-success', !isError && String(message || '') !== '');
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            feedback.textContent = '';
            feedback.classList.remove('is-success');
            submitButton.disabled = true;

            fetch(form.getAttribute('action'), {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
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
                    showFeedback(result.data && result.data.message ? result.data.message : 'Une erreur est survenue.', true);
                    submitButton.disabled = false;
                    return;
                }

                showFeedback(result.data.message || 'Demande envoyee.', false);
                notifyParent(result.data);

                window.setTimeout(function () {
                    closeModal();
                }, 220);
            })
            .catch(function () {
                showFeedback('Une erreur est survenue.', true);
                submitButton.disabled = false;
            });
        });
    })();
