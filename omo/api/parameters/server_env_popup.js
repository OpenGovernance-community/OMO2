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

    var tabs = document.getElementById('omoServerEnvTabs');
    if (typeof window.initGenericComponents === 'function') {
        window.initGenericComponents(root);
    } else if (tabs && typeof window.initGenericTabs === 'function') {
        window.initGenericTabs(tabs);
    }
    var technical = document.getElementById('omoServerEnvTechnical');
    var aiProvider = form.elements.namedItem('AI_PROVIDER');
    var transcriptionProvider = form.elements.namedItem('TRANSCRIPTION_PROVIDER');
    var infomaniakLoad = document.getElementById('omoServerEnvInfomaniakLoad');
    var infomaniakControls = document.getElementById('omoServerEnvInfomaniakControls');
    var infomaniakFeedback = document.getElementById('omoServerEnvInfomaniakFeedback');
    var productInput = form.elements.namedItem('AI_INFOMANIAK_PRODUCT_ID');
    var productSuggestions = document.getElementById('omoServerEnvInfomaniakProducts');
    var discoveryGeneration = 0;
    var discoveryController = null;
    var previousProvider = aiProvider ? aiProvider.value : '';
    var providerModels = {};

    function modelControl(name, asSelect) {
        var control = form.elements.namedItem(name);
        if (!control || (control.tagName === 'SELECT') === asSelect) return control;
        var replacement = document.createElement(asSelect ? 'select' : 'input');
        ['name', 'id', 'class'].forEach(function (attribute) {
            replacement.setAttribute(attribute, control.getAttribute(attribute));
        });
        if (asSelect) replacement.add(new Option(control.value || (name === 'TRANSCRIPTION_MODEL' ? 'Whisper V3 (whisper)' : (name === 'AI_MODEL' ? texts.infomaniakChooseModel : texts.infomaniakMainModel)), control.value || (name === 'TRANSCRIPTION_MODEL' ? 'whisper' : '')));
        else { replacement.type = 'text'; replacement.value = control.value; replacement.autocomplete = 'off'; }
        control.replaceWith(replacement);
        return replacement;
    }

    function fillSelect(select, items, selected, placeholder) {
        select.replaceChildren(new Option(placeholder, ''));
        items.forEach(function (item) { select.add(new Option(item.label, item.id)); });
        select.value = items.some(function (item) { return item.id === selected; }) ? selected : '';
    }

    function cancelDiscovery() {
        discoveryGeneration++;
        if (discoveryController) discoveryController.abort();
        discoveryController = null;
        if (infomaniakLoad) infomaniakLoad.disabled = false;
        if (infomaniakLoad) {
            infomaniakLoad.textContent = texts.infomaniakRefresh;
            infomaniakLoad.removeAttribute('aria-busy');
        }
        setFeedback(infomaniakFeedback, '', '');
    }

    function loadInfomaniak(productId) {
        cancelDiscovery();
        var generation = discoveryGeneration;
        var token = form.elements.namedItem('AI_API_KEY').value.trim();
        var data = new FormData();
        data.set('token', token);
        data.set('productId', productId || '');
        discoveryController = new AbortController();
        var controller = discoveryController;
        var timedOut = false;
        var timeout = window.setTimeout(function () {
            timedOut = true;
            controller.abort();
        }, 45000);
        infomaniakLoad.disabled = true;
        infomaniakLoad.textContent = texts.infomaniakLoading;
        infomaniakLoad.setAttribute('aria-busy', 'true');
        setFeedback(infomaniakFeedback, texts.infomaniakLoading, '', true);
        fetch('/omo/api/parameters/server_env_infomaniak.php', {
            method: 'POST', body: data, credentials: 'same-origin', signal: discoveryController.signal,
            headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(function (response) {
            return response.json().catch(function () {
                throw new Error(response.ok ? texts.infomaniakResponse : texts.infomaniakHttp.replace('{status}', response.status));
            });
        })
        .then(function (payload) {
            if (generation !== discoveryGeneration || aiProvider.value !== 'infomaniak') return;
            setFeedback(infomaniakFeedback, '', '');
            if (payload && payload.requiresUnlock) {
                setFeedback(infomaniakFeedback, texts.infomaniakUnlock, 'error');
                return;
            }
            if (!payload || !payload.status) {
                setFeedback(infomaniakFeedback, payload && payload.message ? payload.message : texts.infomaniakResponse, 'error');
                return;
            }
            if (!Array.isArray(payload.products) || !Array.isArray(payload.models) || typeof payload.productId !== 'string') {
                throw new Error(texts.infomaniakResponse);
            }
            productInput.value = payload.productId;
            if (productSuggestions) {
                productSuggestions.replaceChildren();
                payload.products.forEach(function (item) { productSuggestions.appendChild(new Option(item.label, item.id)); });
            }
            ['AI_MODEL', 'AI_TRANSLATION_MODEL'].forEach(function (name) {
                var control = modelControl(name, true);
                fillSelect(control, payload.models, control.value, name === 'AI_MODEL' ? texts.infomaniakChooseModel : texts.infomaniakMainModel);
            });
            if (!payload.productId) {
                setFeedback(infomaniakFeedback, texts.infomaniakChooseProduct, '', true);
                productInput.focus();
            } else {
                setFeedback(infomaniakFeedback, texts.infomaniakLoaded, 'success');
            }
        })
        .catch(function (error) {
            if (generation !== discoveryGeneration) return;
            if (timedOut) setFeedback(infomaniakFeedback, texts.infomaniakTimeout, 'error');
            else if (error.name !== 'AbortError') setFeedback(infomaniakFeedback,
                error instanceof TypeError ? texts.infomaniakNetwork : error.message, 'error');
        })
        .finally(function () {
            window.clearTimeout(timeout);
            if (generation !== discoveryGeneration) return;
            infomaniakLoad.disabled = false;
            infomaniakLoad.textContent = texts.infomaniakRefresh;
            infomaniakLoad.removeAttribute('aria-busy');
            discoveryController = null;
            // Clear inline progress without removing a fallback error when notifications are unavailable.
            if (infomaniakFeedback.textContent === texts.infomaniakLoading) setFeedback(infomaniakFeedback, '', '');
        });
    }
    if (infomaniakLoad) infomaniakLoad.addEventListener('click', function () {
        if (productInput.value && !productInput.checkValidity()) {
            productInput.reportValidity();
            return;
        }
        loadInfomaniak(productInput.value.trim());
    });
    if (productInput) productInput.addEventListener('input', cancelDiscovery);
    var tokenInput = form.elements.namedItem('AI_API_KEY');
    if (tokenInput) tokenInput.addEventListener('input', cancelDiscovery);
    function syncAiFields() {
        var infomaniak = aiProvider && aiProvider.value === 'infomaniak';
        if (infomaniakControls) infomaniakControls.hidden = !infomaniak;
        ['AI_MODEL', 'AI_TRANSLATION_MODEL'].forEach(function (name) { modelControl(name, infomaniak); });
        var audioInfomaniak = transcriptionProvider && transcriptionProvider.value === 'infomaniak';
        var audioModel = modelControl('TRANSCRIPTION_MODEL', audioInfomaniak);
        if (audioInfomaniak && audioModel) {
            var whisper = Array.from(audioModel.options).find(function (option) { return option.value === 'whisper'; });
            if (!whisper) audioModel.add(new Option('Whisper V3 (whisper)', 'whisper'));
            else whisper.textContent = 'Whisper V3 (whisper)';
            if (!audioModel.value) audioModel.value = 'whisper';
        }
        form.querySelectorAll('[name^="AI_"], [name^="TRANSCRIPTION_"]').forEach(function (input) {
            if (input === aiProvider || input === transcriptionProvider) return;
            var provider = input.name.indexOf('TRANSCRIPTION_') === 0 ? transcriptionProvider : aiProvider;
            var inactive = provider && provider.value === 'disabled';
            if (input.name === 'AI_BASE_URL') inactive = !aiProvider || aiProvider.value !== 'openai_compatible';
            if (input.name === 'AI_INFOMANIAK_PRODUCT_ID') inactive = !infomaniak;
            if (input.name === 'TRANSCRIPTION_INFOMANIAK_PRODUCT_ID') inactive = !transcriptionProvider || transcriptionProvider.value !== 'infomaniak';
            input.closest('.generic-form-field').hidden = inactive;
            input.disabled = inactive;
        });
    }
    if (aiProvider) aiProvider.addEventListener('change', function () {
        cancelDiscovery();
        providerModels[previousProvider] = ['AI_MODEL', 'AI_TRANSLATION_MODEL'].map(function (name) { return form.elements.namedItem(name).value; });
        syncAiFields();
        var values = providerModels[aiProvider.value] || ['', ''];
        ['AI_MODEL', 'AI_TRANSLATION_MODEL'].forEach(function (name, index) {
            var control = form.elements.namedItem(name);
            if (control.tagName === 'SELECT' && values[index] && !Array.from(control.options).some(function (option) { return option.value === values[index]; })) {
                control.add(new Option(values[index], values[index]));
            }
            control.value = values[index];
        });
        previousProvider = aiProvider.value;
    });
    if (transcriptionProvider) {
        transcriptionProvider.addEventListener('change', function () {
            var model = modelControl('TRANSCRIPTION_MODEL', false);
            var defaults = pageConfig.transcriptionDefaultModels || {};
            if (model && defaults[transcriptionProvider.value]
                && (!model.value.trim() || Object.values(defaults).indexOf(model.value.trim()) !== -1)) {
                model.value = defaults[transcriptionProvider.value];
            }
            syncAiFields();
        });
    }
    syncAiFields();
    var enableDatabase = document.getElementById('omoServerEnvEnableDatabase');
    if (enableDatabase) {
        enableDatabase.addEventListener('change', function () {
            form.querySelectorAll('[data-server-env-database-access]').forEach(function (input) {
                input.disabled = !enableDatabase.checked;
            });
        });
    }

    // Reveal the first invalid field before asking the browser to focus it.
    form.noValidate = true;

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
        if (!form.checkValidity()) {
            var invalidField = form.querySelector(':invalid');
            var panel = invalidField && invalidField.closest('[data-generic-tab-panel]');
            var tab = panel && tabs && tabs.querySelector('[data-generic-tab-target="' + panel.id + '"]');
            if (tab) {
                tab.click();
            }
            if (invalidField && technical && technical.contains(invalidField)) {
                var accordions = [];
                var accordion = invalidField.closest('[data-generic-accordion]');
                while (accordion && technical.contains(accordion)) {
                    accordions.unshift(accordion);
                    accordion = accordion.parentElement.closest('[data-generic-accordion]');
                }
                accordions.forEach(function (node) {
                    if (node.classList.contains('is-collapsed')) {
                        node.querySelector('[data-generic-accordion-toggle]').click();
                    }
                });
            }
            if (invalidField) {
                invalidField.reportValidity();
            }
            return;
        }
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
            if (enableDatabase) {
                enableDatabase.checked = false;
                form.querySelectorAll('[data-server-env-database-access]').forEach(function (input) {
                    input.disabled = true;
                });
            }
            if (typeof window.commonDrawerMarkSaved === 'function') {
                window.commonDrawerMarkSaved(form);
            }
        })
        .catch(function () {
            setFeedback(feedback, texts.saveFailed || ("Impossible d’enregistrer le fichier " + envTargetLabel + '.'), 'error');
        })
        .finally(function () {
            if (submitButton) {
                submitButton.disabled = false;
            }
        });
    });
})();
};
