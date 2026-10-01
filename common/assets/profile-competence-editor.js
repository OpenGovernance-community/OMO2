window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/common/assets/profile-competence-editor.js"] = function (pageConfig, pageScript) {
    (function () {
        var currentScript = pageScript;
        var fragment = currentScript ? currentScript.closest('.profile-panel__scope-fragment') : null;
        var feedback = fragment ? fragment.querySelector('[data-profile-competence-feedback="1"]') : null;
        var reloadErrorMessage = pageConfig.reloadErrorMessage;
        var invalidResponseMessage = pageConfig.invalidResponseMessage;
        var saveErrorMessage = pageConfig.saveErrorMessage;
        var saveSuccessMessage = pageConfig.saveSuccessMessage;
        var deleteConfirmMessage = pageConfig.deleteConfirmMessage;
        var deleteErrorMessage = pageConfig.deleteErrorMessage;
        var deleteSuccessMessage = pageConfig.deleteSuccessMessage;
        var createTitle = pageConfig.createTitle;
        var editTitle = pageConfig.editTitle;
        var addLabel = pageConfig.addLabel;
        var saveLabel = pageConfig.saveLabel;
        var profileDirtyKey = pageConfig.profileDirtyKey;
        var editorForm = fragment.querySelector('[data-profile-competence-form="1"]');
        var editorTitle = editorForm ? editorForm.querySelector('[data-profile-competence-editor-title]') : null;
        var submitButton = editorForm ? editorForm.querySelector('[data-profile-competence-submit-label="1"]') : null;
        var deleteButton = editorForm ? editorForm.querySelector('[data-profile-competence-delete="1"]') : null;
        var cancelButton = editorForm ? editorForm.querySelector('[data-profile-competence-cancel="1"]') : null;
        var createButton = fragment.querySelector('[data-profile-competence-create="1"]');

        if (!fragment) {
            return;
        }

        if (editorForm) {
            Array.prototype.forEach.call(editorForm.querySelectorAll('input, select, textarea'), function (field) {
                field.addEventListener('input', function () {
                    if (typeof window.profileMarkDirty === 'function') {
                        window.profileMarkDirty(profileDirtyKey);
                    }
                });
                field.addEventListener('change', function () {
                    if (typeof window.profileMarkDirty === 'function') {
                        window.profileMarkDirty(profileDirtyKey);
                    }
                });
            });
        }

        function setFeedback(message, type) {
            if (!feedback) {
                return;
            }

            feedback.textContent = message || '';
            feedback.className = 'profile-panel__competence-feedback';
            if (type === 'success') {
                feedback.classList.add('is-success');
            } else if (type === 'error') {
                feedback.classList.add('is-error');
            }
        }

        function parseResponse(response) {
            return response.text().then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (error) {
                    return {
                        status: false,
                        message: invalidResponseMessage
                    };
                }
            });
        }

        function executeEmbeddedScripts(container) {
        return window.commonExecuteFragmentScripts(container);
    }

        function reloadFragment() {
            var fragmentUrl = fragment.getAttribute('data-profile-fragment-url') || '';
            if (fragmentUrl === '') {
                return;
            }

            fetch(fragmentUrl, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('load');
                    }

                    return response.text();
                })
                .then(function (html) {
                    var wrapper = document.createElement('div');
                    wrapper.innerHTML = html;
                    var replacement = wrapper.firstElementChild;

                    if (!replacement || !fragment.parentNode) {
                        throw new Error('fragment');
                    }

                    fragment.parentNode.replaceChild(replacement, fragment);
                    return executeEmbeddedScripts(replacement);
                })
                .catch(function () {
                    setFeedback(reloadErrorMessage, 'error');
                });
        }

        function setFieldValue(fieldName, value) {
            var field = editorForm ? editorForm.querySelector('[name="' + fieldName + '"]') : null;
            if (field) {
                field.value = value;
            }
        }

        function setCheckboxValue(fieldName, checked) {
            var field = editorForm ? editorForm.querySelector('[name="' + fieldName + '"]') : null;
            if (field) {
                field.checked = !!checked;
            }
        }

        function openEditor(payload) {
            var isEdit = !!(payload && payload.id);

            if (!editorForm) {
                return;
            }

            setFieldValue('id', isEdit ? String(payload.id) : '');
            setFieldValue('scope', payload && payload.scope ? String(payload.scope) : (createButton ? (createButton.getAttribute('data-profile-competence-default-scope') || 'general') : 'general'));
            setFieldValue('name', payload && payload.name ? String(payload.name) : '');
            setFieldValue('description', payload && payload.description ? String(payload.description) : '');
            setFieldValue('category', payload && payload.category ? String(payload.category) : 'technical');
            setFieldValue('level', payload && payload.level ? String(payload.level) : '');
            setCheckboxValue('limit_to_organization', !!(payload && payload.limitToOrganization));

            if (editorTitle) {
                editorTitle.textContent = isEdit ? editTitle : createTitle;
            }
            if (submitButton) {
                submitButton.textContent = isEdit ? saveLabel : addLabel;
            }
            if (deleteButton) {
                deleteButton.hidden = !isEdit;
            }

            editorForm.hidden = false;
            editorForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function closeEditor() {
            if (!editorForm) {
                return;
            }

            editorForm.reset();
            setFieldValue('id', '');
            setFieldValue('scope', createButton ? (createButton.getAttribute('data-profile-competence-default-scope') || 'general') : 'general');
            setCheckboxValue('limit_to_organization', createButton && createButton.getAttribute('data-profile-competence-default-scope') === 'organization');
            if (editorTitle) {
                editorTitle.textContent = createTitle;
            }
            if (submitButton) {
                submitButton.textContent = addLabel;
            }
            if (deleteButton) {
                deleteButton.hidden = true;
            }
            editorForm.hidden = true;
            if (typeof window.profileMarkClean === 'function') {
                window.profileMarkClean(profileDirtyKey);
            }
        }

        if (createButton) {
            createButton.addEventListener('click', function () {
                openEditor({
                    scope: createButton.getAttribute('data-profile-competence-default-scope') || 'general',
                    limitToOrganization: (createButton.getAttribute('data-profile-competence-default-scope') || 'general') === 'organization'
                });
            });
        }

        Array.prototype.forEach.call(fragment.querySelectorAll('[data-profile-competence-edit="1"]'), function (button) {
            button.addEventListener('click', function () {
                var payloadText = button.getAttribute('data-profile-competence-payload') || '';
                var payload = null;

                try {
                    payload = JSON.parse(payloadText);
                } catch (error) {
                    payload = null;
                }

                openEditor(payload || {});
            });
        });

        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                closeEditor();
            });
        }

        if (editorForm) {
            editorForm.addEventListener('submit', function (event) {
                event.preventDefault();

                if (typeof window.omoBeginPendingAction === 'function' && !window.omoBeginPendingAction(editorForm)) {
                    return;
                }

                setFeedback('', '');

                var formData = new FormData(editorForm);

                fetch('/ajax/user_competence_save.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(parseResponse)
                    .then(function (result) {
                        if (!result || !result.status) {
                            setFeedback(result && result.message ? result.message : saveErrorMessage, 'error');
                            return;
                        }

                        setFeedback(result.message || saveSuccessMessage, 'success');
                        if (typeof window.profileMarkClean === 'function') {
                            window.profileMarkClean(profileDirtyKey);
                        }
                        reloadFragment();
                    })
                    .catch(function () {
                        setFeedback(saveErrorMessage, 'error');
                    })
                    .finally(function () {
                        if (typeof window.omoEndPendingAction === 'function') {
                            window.omoEndPendingAction(editorForm);
                        }
                    });
            });
        }

        if (deleteButton) {
            deleteButton.addEventListener('click', function () {
                var identifier = editorForm ? editorForm.querySelector('input[name="id"]') : null;
                if (!identifier || !identifier.value) {
                    return;
                }

                if (!confirm(deleteConfirmMessage)) {
                    return;
                }

                if (typeof window.omoBeginPendingAction === 'function' && !window.omoBeginPendingAction(editorForm)) {
                    return;
                }

                setFeedback('', '');

                var formData = new FormData();
                formData.append('id', identifier.value);

                fetch('/ajax/user_competence_delete.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(parseResponse)
                    .then(function (result) {
                        if (!result || !result.status) {
                            setFeedback(result && result.message ? result.message : deleteErrorMessage, 'error');
                            return;
                        }

                        setFeedback(result.message || deleteSuccessMessage, 'success');
                        if (typeof window.profileMarkClean === 'function') {
                            window.profileMarkClean(profileDirtyKey);
                        }
                        reloadFragment();
                    })
                    .catch(function () {
                        setFeedback(deleteErrorMessage, 'error');
                    })
                    .finally(function () {
                        if (typeof window.omoEndPendingAction === 'function') {
                            window.omoEndPendingAction(editorForm);
                        }
                    });
            });
        }

        if (editorForm) {
            closeEditor();
        }
    })();
};
