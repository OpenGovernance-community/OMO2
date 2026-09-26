window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/parameters/server_env_popup.js"] = function (pageConfig, pageScript) {
(function () {
    var root = document.getElementById('omoServerEnvPopup');
    if (!root) {
        return;
    }

    var envTargetLabel = pageConfig.envTargetLabel;
    var texts = pageConfig.texts;
    var popupUrl = root.getAttribute('data-popup-url') || '/omo/api/parameters/server_env_popup.php';
    var unlockUrl = root.getAttribute('data-unlock-url') || '/omo/api/parameters/server_env_unlock.php';
    var saveUrl = root.getAttribute('data-save-url') || '/omo/api/parameters/server_env_save.php';
    var testUrl = root.getAttribute('data-test-url') || '/omo/api/parameters/server_env_test_connection.php';

    function closeModal() {
        if (typeof window.commonTopbarCloseModal === 'function') {
            window.commonTopbarCloseModal();
        }
    }

    function refreshPopup() {
        if (typeof window.commonTopbarRefreshModalContent === 'function') {
            window.commonTopbarRefreshModalContent(popupUrl);
            return;
        }

        window.location.href = popupUrl;
    }

    function bindCloseButtons() {
        root.querySelectorAll('#omoServerEnvClose').forEach(function (button) {
            button.addEventListener('click', closeModal);
        });
    }

    function setFeedback(node, message, type, inline) {
        if (!node) {
            return;
        }

        if (message && !inline && typeof window.commonNotify === 'function') {
            window.commonNotify(message, type === 'success' ? 'success' : 'error');
            node.textContent = '';
            node.className = 'omo-server-env-popup__feedback generic-soft-panel generic-feedback';
            return;
        }

        node.textContent = message || '';
        node.className = 'omo-server-env-popup__feedback generic-soft-panel generic-feedback';

        if (!message) {
            return;
        }

        node.classList.add('is-visible');
        if (type === 'error') {
            node.classList.add('is-error');
            return;
        }

        if (type === 'success') {
            node.classList.add('is-success');
        }
    }

    bindCloseButtons();

    var unlockForm = document.getElementById('omoServerEnvUnlockForm');
    if (unlockForm) {
        var unlockFeedback = document.getElementById('omoServerEnvUnlockFeedback');
        var unlockSubmit = document.getElementById('omoServerEnvUnlockSubmit');
        var unlockInput = document.getElementById('omoServerEnvUnlockPassword');

        if (unlockInput) {
            unlockInput.focus();
        }

        unlockForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (unlockSubmit) {
                unlockSubmit.disabled = true;
            }

            setFeedback(unlockFeedback, '', '');

            fetch(unlockUrl, {
                method: 'POST',
                body: new FormData(unlockForm),
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (response) {
                return response.json().catch(function () {
                        return {
                            status: false,
                            message: texts.invalidResponse || ''
                        };
                    });
            })
            .then(function (payload) {
                if (!payload || !payload.status) {
                    setFeedback(unlockFeedback, payload && payload.message ? payload.message : (texts.unlockFailed || ''), 'error');
                    if (unlockInput) {
                        unlockInput.value = '';
                        unlockInput.focus();
                    }
                    return;
                }

                setFeedback(unlockFeedback, payload.message || (texts.unlockSuccess || ''), 'success');
                window.setTimeout(refreshPopup, 150);
            })
            .catch(function () {
                setFeedback(unlockFeedback, texts.unlockFailed || '', 'error');
            })
            .finally(function () {
                if (unlockSubmit) {
                    unlockSubmit.disabled = false;
                }
            });
        });

        return;
    }

    var form = document.getElementById('omoServerEnvForm');
    if (!form) {
        return;
    }

    var feedback = document.getElementById('omoServerEnvFeedback');
    var submitButton = document.getElementById('omoServerEnvSubmit');
    var firstInput = form.querySelector('input, select, textarea');
    if (firstInput) {
        firstInput.focus();
    }

    root.querySelectorAll('[data-server-env-test]').forEach(function (button) {
        button.addEventListener('click', function () {
            var service = button.getAttribute('data-server-env-test') || '';
            var testFeedback = root.querySelector('[data-server-env-test-feedback="' + service + '"]');
            button.disabled = true;
            setFeedback(testFeedback, '', '', true);

            var data = new FormData(form);
            data.append('service', service);
            fetch(testUrl, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (response) {
                return response.json().catch(function () {
                    return {
                        status: false,
                        message: texts.invalidResponse || ''
                    };
                });
            })
            .then(function (payload) {
                if (payload && payload.requiresUnlock) {
                    refreshPopup();
                    return;
                }

                setFeedback(
                    testFeedback,
                    payload && payload.message ? payload.message : (texts.testFailed || ''),
                    payload && payload.status ? 'success' : 'error',
                    true
                );
            })
            .catch(function () {
                setFeedback(testFeedback, texts.testFailed || '', 'error', true);
            })
            .finally(function () {
                button.disabled = false;
            });
        });
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (submitButton) {
            submitButton.disabled = true;
        }

        setFeedback(feedback, '', '');

        fetch(saveUrl, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function (response) {
            return response.json().catch(function () {
                return {
                    status: false,
                    message: texts.invalidResponse || ''
                };
            });
        })
        .then(function (payload) {
            if (payload && payload.requiresUnlock) {
                refreshPopup();
                return;
            }

            setFeedback(
                feedback,
                payload && payload.message ? payload.message : (texts.operationDone || ''),
                payload && payload.status ? 'success' : 'error'
            );

            if (!(payload && payload.status)) {
                return;
            }

            if (payload.configuredSecrets) {
                Object.keys(payload.configuredSecrets).forEach(function (key) {
                    var statusNode = root.querySelector('[data-server-env-field-status="' + key + '"]');
                    if (!statusNode) {
                        return;
                    }

                    var configured = !!payload.configuredSecrets[key];
                    statusNode.textContent = configured ? (texts.secretConfigured || '') : (texts.secretEmpty || '');
                    statusNode.classList.toggle('is-configured', configured);
                });
            }

            form.querySelectorAll('input[type="password"]').forEach(function (input) {
                input.value = '';
            });
        })
        .catch(function () {
            setFeedback(feedback, texts.saveFailed || ('Impossible d enregistrer le fichier ' + envTargetLabel + '.'), 'error');
        })
        .finally(function () {
            if (submitButton) {
                submitButton.disabled = false;
            }
        });
    });
})();
};
