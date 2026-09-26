window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/decision/edit_shared.js"] = function (pageConfig, pageScript) {
(function () {
    function openEditorUrl(url, title) {
        if (!url) {
            return;
        }

        if (typeof window.omoDecisionOpenNestedDrawer === 'function') {
            window.omoDecisionOpenNestedDrawer(title || 'Prises de decision', url, '');
            return;
        }

        if (typeof window.commonTopbarOpenDrawer === 'function') {
            window.commonTopbarOpenDrawer(title || 'Prises de decision', url, 'fetch');
            return;
        }

        window.location.href = url;
    }

    function notifyQuestionSwitch(message, type) {
        if (typeof window.commonNotify === 'function') {
            window.commonNotify(message, type || 'error');
            return;
        }

        window.alert(message);
    }

    function executeQuestionScripts(container) {
        return window.commonExecuteFragmentScripts(container);
    }

    const questionFormSelector = [
        'form[data-omo-decision-consent-form]',
        'form[data-omo-decision-vote-form]',
        'form[data-omo-decision-majority-judgment-form]'
    ].join(',');
    const unsavedQuestionLabel = pageConfig.unsavedQuestionLabel;
    const questionItemTemplate = pageConfig.questionItemTemplate;

    function getCompleteFormData(form) {
        const data = new FormData(form);
        form.querySelectorAll('[name]:disabled').forEach(function (field) {
            if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) {
                return;
            }
            if (field.name) {
                if (field.name.slice(-2) === '[]') {
                    data.append(field.name, field.value || '');
                } else {
                    data.set(field.name, field.value || '');
                }
            }
        });
        return data;
    }

    function appendSignature(values, prefix, form) {
        getCompleteFormData(form).forEach(function (value, key) {
            if (typeof value === 'string') {
                values.push([prefix, key, value]);
                return;
            }
            values.push([prefix, key, value.name || '', value.size || 0, value.lastModified || 0]);
        });
    }

    function getMultiEditorSignature(root) {
        const values = [];
        const processForm = root.querySelector('[data-omo-decision-process-form]');
        if (processForm) {
            appendSignature(values, 'process', processForm);
        }
        root.querySelectorAll('[data-omo-decision-question-panel]').forEach(function (panel) {
            const form = panel.querySelector(questionFormSelector);
            if (form) {
                appendSignature(values, panel.getAttribute('data-question-key') || 'question', form);
            }
        });
        return JSON.stringify(values);
    }

    function getActiveQuestionKey(root) {
        const panel = root.querySelector('[data-omo-decision-question-panel]:not([hidden])');
        return panel ? panel.getAttribute('data-question-key') || '' : '';
    }

    function switchLocalQuestion(root, questionKey) {
        let matched = false;
        root.querySelectorAll('[data-omo-decision-question-panel]').forEach(function (panel) {
            const active = panel.getAttribute('data-question-key') === questionKey;
            panel.hidden = !active;
            matched = matched || active;
        });
        if (!matched) {
            return false;
        }
        root.querySelectorAll('[data-omo-decision-question-link]').forEach(function (link) {
            const active = link.getAttribute('data-question-key') === questionKey;
            link.classList.toggle('is-active', active);
            if (active) {
                link.setAttribute('aria-current', 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        });
        return true;
    }

    function makePanelIdsUnique(panel, questionKey) {
        panel.querySelectorAll('[id]').forEach(function (field) {
            const previousId = field.id;
            const nextId = previousId + '-' + questionKey;
            panel.querySelectorAll('label[for="' + CSS.escape(previousId) + '"]').forEach(function (label) {
                label.setAttribute('for', nextId);
            });
            field.id = nextId;
        });
    }

    function appendLocalQuestionTab(root, questionKey) {
        const tabs = root.querySelector('.omo-decision-edit__question-tabs');
        if (!tabs) {
            return;
        }
        const questionNumber = root.querySelectorAll('[data-omo-decision-question-panel]').length;
        const link = document.createElement('a');
        link.href = '#';
        link.className = 'omo-decision-edit__question-tab';
        link.setAttribute('data-omo-decision-question-link', '');
        link.setAttribute('data-question-key', questionKey);
        link.textContent = String(questionItemTemplate || 'Question __INDEX__').replace('__INDEX__', String(questionNumber));
        const badge = document.createElement('span');
        badge.className = 'omo-decision-edit__question-tab-badge';
        badge.textContent = unsavedQuestionLabel;
        link.appendChild(badge);
        tabs.appendChild(link);
    }

    function addLocalQuestion(root, button) {
        const url = button.getAttribute('data-fragment-url') || '';
        const nav = button.closest('[data-omo-decision-question-nav]');
        const errorMessage = root.getAttribute('data-error-message') || 'Impossible d’ajouter cette question.';
        if (!url || (nav && nav.getAttribute('aria-busy') === 'true')) {
            return;
        }
        if (nav) {
            nav.setAttribute('aria-busy', 'true');
        }
        fetch(typeof window.omoResolveAppUrl === 'function' ? window.omoResolveAppUrl(url) : url, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(errorMessage);
                }
                return response.text();
            })
            .then(function (html) {
                const documentFragment = new DOMParser().parseFromString(html, 'text/html');
                const source = documentFragment.querySelector('[data-omo-decision-question-fragment]');
                const panels = root.querySelector('[data-omo-decision-question-panels]');
                if (!source || !panels) {
                    throw new Error(errorMessage);
                }
                const questionKey = 'new-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 7);
                source.removeAttribute('data-omo-decision-question-fragment');
                source.setAttribute('data-omo-decision-question-panel', '');
                source.setAttribute('data-question-key', questionKey);
                source.classList.add('omo-decision-edit__question-panel');
                makePanelIdsUnique(source, questionKey);
                panels.appendChild(source);
                appendLocalQuestionTab(root, questionKey);
                switchLocalQuestion(root, questionKey);
                return executeQuestionScripts(source);
            })
            .catch(function (error) {
                notifyQuestionSwitch(error && error.message ? error.message : errorMessage, 'error');
            })
            .finally(function () {
                if (nav) {
                    nav.removeAttribute('aria-busy');
                    const chooser = nav.querySelector('[data-omo-decision-question-methods]');
                    const toggle = nav.querySelector('[data-omo-decision-question-add-toggle]');
                    if (chooser) {
                        chooser.hidden = true;
                    }
                    if (toggle) {
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                }
            });
    }

    function validateMultiEditor(root) {
        const processForm = root.querySelector('[data-omo-decision-process-form]');
        if (processForm && !processForm.reportValidity()) {
            return false;
        }
        const panels = Array.prototype.slice.call(root.querySelectorAll('[data-omo-decision-question-panel]'));
        for (let index = 0; index < panels.length; index += 1) {
            const form = panels[index].querySelector(questionFormSelector);
            if (form && !form.checkValidity()) {
                switchLocalQuestion(root, panels[index].getAttribute('data-question-key') || '');
                form.reportValidity();
                return false;
            }
        }
        return true;
    }

    function saveMultiEditor(root) {
        if (root.getAttribute('aria-busy') === 'true' || !validateMultiEditor(root)) {
            return;
        }
        if (typeof window.omoDecisionEnsureLifecycleStatusConsistency === 'function'
            && !window.omoDecisionEnsureLifecycleStatusConsistency(root)) {
            return;
        }
        const processForm = root.querySelector('[data-omo-decision-process-form]');
        const saveButton = getMultiSaveButton(root);
        const feedback = root.querySelector('[data-omo-decision-multi-feedback]');
        const activeQuestionKey = getActiveQuestionKey(root);
        const originalStatus = root.getAttribute('data-original-status') || 'draft';
        const commonData = getCompleteFormData(processForm);
        const panels = Array.prototype.slice.call(root.querySelectorAll('[data-omo-decision-question-panel]'));
        const activeQuestionIndex = panels.findIndex(function (panel) {
            return (panel.getAttribute('data-question-key') || '') === activeQuestionKey;
        });
        const batchData = new FormData();
        let activeRedirectUrl = '';
        let lastRedirectUrl = '';

        root.setAttribute('aria-busy', 'true');
        if (saveButton) {
            saveButton.disabled = true;
            saveButton.textContent = root.getAttribute('data-saving-label') || 'Enregistrement…';
        }
        if (feedback) {
            feedback.textContent = '';
        }

        panels.forEach(function (panel, index) {
            const form = panel.querySelector(questionFormSelector);
            const formData = getCompleteFormData(form);
            const serialized = new URLSearchParams();
            commonData.forEach(function (value, key) {
                formData.set(key, value);
            });
            if (index < panels.length - 1) {
                formData.set('status', originalStatus);
            }
            formData.forEach(function (value, key) {
                serialized.append(key, typeof value === 'string' ? value : '');
            });
            batchData.append('groups[]', serialized.toString());
        });

        fetch('/omo/api/decision/save_multi.php', {
            method: 'POST',
            body: batchData,
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(function (response) {
                return response.json().catch(function () { return null; }).then(function (data) {
                    if (!response.ok || !data || !data.status) {
                        throw new Error(data && data.message ? data.message : (root.getAttribute('data-error-message') || 'Erreur.'));
                    }
                    return data;
                });
            })
            .then(function (data) {
                const results = Array.isArray(data.results) ? data.results : [];
                results.forEach(function (result, index) {
                    lastRedirectUrl = String(result && result.redirectUrl ? result.redirectUrl : lastRedirectUrl);
                    if (index === activeQuestionIndex) {
                        activeRedirectUrl = String(result && result.redirectUrl ? result.redirectUrl : '');
                    }
                });
                root.dataset.multiInitialSignature = getMultiEditorSignature(root);
                notifyQuestionSwitch(root.getAttribute('data-saved-message') || 'Scrutin enregistré.', 'success');
                openEditorUrl(activeRedirectUrl || lastRedirectUrl, 'Prises de decision');
            })
            .catch(function (error) {
                notifyQuestionSwitch(
                    error && error.message ? error.message : (root.getAttribute('data-error-message') || 'Erreur.'),
                    'error'
                );
            })
            .finally(function () {
                root.removeAttribute('aria-busy');
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.textContent = root.getAttribute('data-save-label') || 'Enregistrer le scrutin';
                }
            });
    }

    function getMultiSaveButton(root) {
        const editorId = root.id || '';

        if (editorId !== '') {
            return document.querySelector('[data-omo-decision-multi-save-for="' + CSS.escape(editorId) + '"]');
        }

        return null;
    }

    document.querySelectorAll('[data-omo-decision-multi-editor]').forEach(function (root) {
        if (root.dataset.omoDecisionMultiReady === '1') {
            return;
        }
        root.dataset.omoDecisionMultiReady = '1';
        [
            window.omoDecisionConsentInit,
            window.omoDecisionVoteInit,
            window.omoDecisionMajorityJudgmentInit
        ].forEach(function (initializer) {
            if (typeof initializer === 'function') {
                initializer(root);
            }
        });
        root.dataset.multiInitialSignature = getMultiEditorSignature(root);

        root.addEventListener('click', function (event) {
            const questionLink = event.target.closest('[data-omo-decision-question-link]');
            if (questionLink && root.contains(questionLink)) {
                event.preventDefault();
                switchLocalQuestion(root, questionLink.getAttribute('data-question-key') || '');
                return;
            }
            const toggle = event.target.closest('[data-omo-decision-question-add-toggle]');
            if (toggle && root.contains(toggle)) {
                const chooser = toggle.parentNode.querySelector('[data-omo-decision-question-methods]');
                if (chooser) {
                    chooser.hidden = !chooser.hidden;
                    toggle.setAttribute('aria-expanded', chooser.hidden ? 'false' : 'true');
                }
                return;
            }
            const methodButton = event.target.closest('[data-omo-decision-question-add-method]');
            if (methodButton && root.contains(methodButton)) {
                addLocalQuestion(root, methodButton);
                return;
            }
            const generalSettingsButton = event.target.closest('[data-omo-decision-general-settings-open]');
            if (generalSettingsButton && root.contains(generalSettingsButton)) {
                const settingsTemplate = root.querySelector('[data-omo-decision-general-settings-template]');
                if (!settingsTemplate || typeof window.commonTopbarOpenModal !== 'function') {
                    return;
                }
                window.commonTopbarOpenModal(
                    generalSettingsButton.getAttribute('data-omo-decision-general-settings-title') || generalSettingsButton.textContent || 'Paramètres généraux',
                    settingsTemplate.innerHTML,
                    'html'
                );
                const modalBody = document.getElementById('commonTopbarModalBody');
                if (!modalBody) {
                    return;
                }
                const processForm = root.querySelector('[data-omo-decision-process-form]');
                const readHidden = function (selector) {
                    const input = processForm ? processForm.querySelector(selector) : null;
                    return !!(input && input.value);
                };
                const setSummary = function (selector, value) {
                    const node = root.querySelector(selector);
                    if (node) {
                        node.textContent = value ? (node.getAttribute('data-yes-label') || 'Oui') : (node.getAttribute('data-no-label') || 'Non');
                    }
                };
                const fields = {
                    owner: modalBody.querySelector('[data-omo-decision-general-owner-intermediate-results]'),
                    participant: modalBody.querySelector('[data-omo-decision-general-participant-intermediate-results]'),
                    editable: modalBody.querySelector('[data-omo-decision-general-participant-responses-editable]'),
                };
                if (!fields.owner || !fields.participant || !fields.editable) {
                    return;
                }
                fields.owner.checked = readHidden('[data-omo-decision-general-hidden-owner-intermediate-results]');
                fields.participant.checked = readHidden('[data-omo-decision-general-hidden-participant-intermediate-results]');
                fields.editable.checked = readHidden('[data-omo-decision-general-hidden-participant-responses-editable]');

                const setHidden = function (selector, value) {
                    const input = processForm ? processForm.querySelector(selector) : null;
                    if (input) {
                        input.value = value ? '1' : '';
                    }
                };
                const cancelButton = modalBody.querySelector('[data-omo-decision-general-settings-cancel]');
                const applyButton = modalBody.querySelector('[data-omo-decision-general-settings-apply]');
                if (cancelButton) {
                    cancelButton.addEventListener('click', function () {
                        if (typeof window.commonTopbarCloseModal === 'function') {
                            window.commonTopbarCloseModal();
                        }
                    });
                }
                if (applyButton) {
                    applyButton.addEventListener('click', function () {
                        setHidden('[data-omo-decision-general-hidden-owner-intermediate-results]', fields.owner.checked);
                        setHidden('[data-omo-decision-general-hidden-participant-intermediate-results]', fields.participant.checked);
                        setHidden('[data-omo-decision-general-hidden-participant-responses-editable]', fields.editable.checked);
                        setSummary('[data-omo-decision-general-owner-summary]', fields.owner.checked);
                        setSummary('[data-omo-decision-general-participant-summary]', fields.participant.checked);
                        setSummary('[data-omo-decision-general-editable-summary]', fields.editable.checked);
                        if (typeof window.commonTopbarCloseModal === 'function') {
                            window.commonTopbarCloseModal();
                        }
                    });
                }
                return;
            }
            const invitationButton = event.target.closest('[data-omo-decision-invitations-open]');
            if (invitationButton && root.contains(invitationButton)) {
                const url = invitationButton.getAttribute('data-omo-decision-invitations-url') || '';
                if (url && typeof window.commonTopbarOpenModal === 'function') {
                    window.commonTopbarOpenModal(
                        invitationButton.getAttribute('data-omo-decision-invitations-title') || invitationButton.textContent || 'Invitations',
                        url,
                        'fetch'
                    );
                }
                return;
            }
            const invitationSendButton = event.target.closest('[data-omo-decision-invitations-send-open]');
            if (invitationSendButton && root.contains(invitationSendButton)) {
                const url = invitationSendButton.getAttribute('data-omo-decision-invitations-send-url') || '';
                if (url && typeof window.commonTopbarOpenModal === 'function') {
                    window.commonTopbarOpenModal(
                        invitationSendButton.getAttribute('data-omo-decision-invitations-send-title') || invitationSendButton.textContent || 'Invitations',
                        url,
                        'fetch'
                    );
                }
                return;
            }
            const saveButton = event.target.closest('[data-omo-decision-multi-save]');
            if (saveButton && root.contains(saveButton)) {
                saveMultiEditor(root);
            }
        });

        root.addEventListener('submit', function (event) {
            if (event.target.matches(questionFormSelector)) {
                event.preventDefault();
                event.stopImmediatePropagation();
                saveMultiEditor(root);
            }
        }, true);

        document.addEventListener('click', function (event) {
            if (!document.contains(root) || !event.target.closest('[data-topbar-drawer-close]')) {
                return;
            }
            if (root.dataset.multiInitialSignature === getMultiEditorSignature(root)) {
                return;
            }
            if (!window.confirm(root.getAttribute('data-unsaved-warning') || 'Des modifications ne sont pas enregistrées.')) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }, true);
    });

    if (!window.omoDecisionMultiHeaderSaveReady) {
        window.omoDecisionMultiHeaderSaveReady = true;
        document.addEventListener('click', function (event) {
            const saveButton = event.target.closest('[data-omo-decision-multi-save-for]');
            const editorId = saveButton ? saveButton.getAttribute('data-omo-decision-multi-save-for') || '' : '';
            const root = editorId !== '' ? document.getElementById(editorId) : null;

            if (!root) {
                return;
            }

            event.preventDefault();
            saveMultiEditor(root);
        });
    }

    if (!window.omoDecisionMultiBeforeUnloadReady) {
        window.omoDecisionMultiBeforeUnloadReady = true;
        window.addEventListener('beforeunload', function (event) {
            const dirty = Array.prototype.some.call(document.querySelectorAll('[data-omo-decision-multi-editor]'), function (root) {
                return root.dataset.multiInitialSignature !== getMultiEditorSignature(root);
            });
            if (dirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    }

    document.querySelectorAll('[data-omo-decision-editor-link]').forEach(function (link) {
        if (link.hasAttribute('data-omo-decision-question-link')) {
            return;
        }
        if (link.dataset.omoDecisionEditorReady === '1') {
            return;
        }

        link.dataset.omoDecisionEditorReady = '1';
        link.addEventListener('click', function (event) {
            event.preventDefault();
            openEditorUrl(
                link.getAttribute('href') || '',
                link.getAttribute('data-omo-decision-editor-title') || 'Prises de decision'
            );
        });
    });

    document.querySelectorAll('[data-omo-decision-import-form]').forEach(function (form) {
        if (form.dataset.omoDecisionImportReady === '1') {
            return;
        }

        form.dataset.omoDecisionImportReady = '1';

        var submitButton = form.querySelector('[data-omo-decision-import-submit]');
        var feedback = form.querySelector('[data-omo-decision-import-feedback]');
        var fileInput = form.querySelector('input[type="file"][name="import_file"]');
        var defaultLabel = submitButton ? submitButton.textContent : '';

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!fileInput || !fileInput.files || !fileInput.files.length) {
                if (feedback) {
                    feedback.textContent = pageConfig.decisionsEditImportNoFile;
                }
                return;
            }

            var formData = new FormData(form);
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = pageConfig.decisionsEditImportLoading;
            }
            if (feedback) {
                feedback.textContent = '';
            }

            fetch(form.getAttribute('action') || '/omo/api/decision/import.php', {
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
                        throw new Error(
                            result.data && result.data.message
                                ? String(result.data.message)
                                : pageConfig.decisionsEditImportError
                        );
                    }

                    openEditorUrl(
                        String(result.data.redirectUrl || ''),
                        String(result.data.drawerTitle || 'Prises de decision')
                    );
                })
                .catch(function (error) {
                    if (feedback) {
                        feedback.textContent = error && error.message
                            ? error.message
                            : pageConfig.decisionsEditImportError;
                    }
                })
                .finally(function () {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = defaultLabel;
                    }
                });
        });
    });
})();
};
