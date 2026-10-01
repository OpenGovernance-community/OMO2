window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/documents/params/index.js"] = function (pageConfig, pageScript) {
(function () {
    document.querySelectorAll('[data-omo-documents-params-root]').forEach(function (root) {
        if (!root || root.dataset.omoDocumentsParamsReady === '1') {
            return;
        }

        root.dataset.omoDocumentsParamsReady = '1';

        var form = root.querySelector('[data-omo-documents-params-form]');
        var submitButton = root.querySelector('[data-omo-documents-params-submit]');
        var feedback = root.querySelector('[data-omo-documents-params-feedback]');
        var storageToggle = root.querySelector('[data-omo-documents-storage-toggle]');
        var storageChoice = root.querySelector('[data-omo-documents-storage-choice]');
        var storageTypeInputs = root.querySelectorAll('[data-omo-documents-storage-type]');
        var nextcloudFields = root.querySelector('[data-omo-documents-nextcloud-fields]');
        var kdriveFields = root.querySelector('[data-omo-documents-kdrive-fields]');
        var nextcloudTestButton = root.querySelector('[data-omo-documents-nextcloud-test]');
        var nextcloudTestFeedback = root.querySelector('[data-omo-documents-nextcloud-test-feedback]');
        var kdriveTestButton = root.querySelector('[data-omo-documents-storage-test]');
        var kdriveTestFeedback = root.querySelector('[data-omo-documents-storage-test-feedback]');
        var collaboraToggle = root.querySelector('[data-omo-documents-collabora-toggle]');
        var collaboraFields = root.querySelector('[data-omo-documents-collabora-fields]');
        var pvToggle = root.querySelector('[data-omo-documents-pv-toggle]');
        var pvFields = root.querySelector('[data-omo-documents-pv-fields]');
        var idleLabel = pageConfig.idleLabel;
        var busyLabel = pageConfig.busyLabel;
        var storageTestLabel = pageConfig.storageTestLabel;
        var storageTestingLabel = pageConfig.storageTestingLabel;

        if (!form || !submitButton || !feedback) {
            return;
        }

        function getSelectedStorageType() {
            var selected = root.querySelector('[data-omo-documents-storage-type]:checked');
            return selected ? selected.value : 'nextcloud';
        }

        function syncStorageFields() {
            var enabled = !!(storageToggle && storageToggle.checked);
            if (storageChoice) {
                storageChoice.hidden = !enabled;
            }
            var selectedType = getSelectedStorageType();
            if (nextcloudFields) {
                nextcloudFields.hidden = !enabled || selectedType !== 'nextcloud';
            }
            if (kdriveFields) {
                kdriveFields.hidden = !enabled || selectedType !== 'kdrive';
            }
            if (collaboraToggle && !enabled) {
                collaboraToggle.checked = false;
                collaboraToggle.disabled = true;
            } else if (collaboraToggle) {
                collaboraToggle.disabled = false;
            }
        }

        function syncCollaboraFields() {
            if (!collaboraFields || !collaboraToggle) {
                return;
            }

            collaboraFields.hidden = !collaboraToggle.checked;
        }

        if (storageToggle) {
            storageToggle.addEventListener('change', syncStorageFields);
        }
        storageTypeInputs.forEach(function (input) {
            input.addEventListener('change', syncStorageFields);
        });
        syncStorageFields();

        if (collaboraToggle) {
            collaboraToggle.addEventListener('change', syncCollaboraFields);
            syncCollaboraFields();
        }

        function syncPvFields() {
            if (pvFields && pvToggle) {
                pvFields.hidden = !pvToggle.checked;
            }
        }

        if (pvToggle) {
            pvToggle.addEventListener('change', syncPvFields);
            syncPvFields();
        }

        function testStorage(button, testFeedback) {
            if (!button) {
                return;
            }

            button.addEventListener('click', function () {
                var formData = new FormData(form);
                button.disabled = true;
                button.textContent = storageTestingLabel;
                if (testFeedback) {
                    testFeedback.textContent = '';
                    testFeedback.classList.remove('is-error', 'is-success');
                }

                window.fetch('/omo/api/documents/params/test_storage.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                }).then(function (response) {
                    return response.json().catch(function () {
                        return {
                            status: false,
                            message: pageConfig.message
                        };
                    });
                }).then(function (payload) {
                    var status = !!(payload && payload.status);
                    if (testFeedback) {
                        testFeedback.textContent = String(payload && payload.message ? payload.message : '');
                        testFeedback.classList.toggle('is-error', !status);
                        testFeedback.classList.toggle('is-success', status);
                    }
                }).catch(function () {
                    if (testFeedback) {
                        testFeedback.textContent = pageConfig.message;
                        testFeedback.classList.add('is-error');
                        testFeedback.classList.remove('is-success');
                    }
                }).finally(function () {
                    button.disabled = false;
                    button.textContent = storageTestLabel;
                });
            });
        }

        testStorage(nextcloudTestButton, nextcloudTestFeedback);
        testStorage(kdriveTestButton, kdriveTestFeedback);

        function renderFeedback(message, isError) {
            if (typeof window.commonNotify === 'function') {
                window.commonNotify(String(message || ''), isError ? 'error' : 'success');
                return;
            }

            feedback.hidden = false;
            feedback.textContent = String(message || '');
            feedback.classList.toggle('is-error', !!isError);
            feedback.classList.toggle('is-success', !isError);
        }

        function sendSettings(formData) {
            return window.fetch(form.getAttribute('action') || '/omo/api/documents/params/save.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json().catch(function () {
                    return {
                        status: false,
                        message: 'Invalid response.'
                    };
                });
            });
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var formData = new FormData(form);
            submitButton.disabled = true;
            submitButton.textContent = busyLabel;
            feedback.hidden = true;
            feedback.classList.remove('is-error', 'is-success');

            sendSettings(formData).then(function (payload) {
                if (payload && payload.confirmationRequired) {
                    if (window.confirm(String(payload.message || ''))) {
                        var confirmedFormData = new FormData(form);
                        confirmedFormData.append('storage_change_confirmed', '1');
                        return sendSettings(confirmedFormData);
                    }
                    return payload;
                }

                return payload;
            }).then(function (payload) {
                var status = !!(payload && payload.status);
                renderFeedback(payload && payload.message ? payload.message : (status ? idleLabel : 'Error'), !status);
            }).catch(function () {
                renderFeedback(pageConfig.documentsParamsErrorSaveFailed, true);
            }).finally(function () {
                submitButton.disabled = false;
                submitButton.textContent = idleLabel;
            });
        });
    });
})();
};
