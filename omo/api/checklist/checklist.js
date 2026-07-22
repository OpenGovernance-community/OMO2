(function (window, document) {
    'use strict';

    var root = document.getElementById('omo-checklist-root');
    if (!root || root.dataset.checklistReady === '1') {
        return;
    }
    root.dataset.checklistReady = '1';

    var drawer = root.querySelector('[data-checklist-drawer]');
    var drawerBody = root.querySelector('[data-checklist-drawer-body]');
    var drawerController = drawer && typeof window.omoCreateSubdrawerController === 'function'
        ? window.omoCreateSubdrawerController({drawer: drawer})
        : null;
    var currentUrl = root.getAttribute('data-checklist-current-url') || '';
    var createUrl = root.getAttribute('data-checklist-create-url') || '';
    var detailUrl = root.getAttribute('data-checklist-detail-url') || '';
    var initialOpenChecklistId = Number(root.getAttribute('data-checklist-open-checklist-id') || 0);
    var requestToken = 0;
    var rootNeedsRefresh = false;
    var texts = {
        loading: 'Chargement de la checklist...',
        loadingError: 'Impossible de charger cette checklist.'
    };

    try {
        texts = Object.assign(texts, JSON.parse(root.getAttribute('data-checklist-texts') || '{}'));
    } catch (error) {
        // Keep local fallbacks when translated texts are unavailable.
    }

    function resolveUrl(url) {
        return typeof window.omoResolveAppUrl === 'function' ? window.omoResolveAppUrl(url) : url;
    }

    function setRootLoading(loading) {
        root.classList.toggle('is-loading', Boolean(loading));
    }

    function refreshRoot(url) {
        var targetUrl = url || currentUrl;
        if (!targetUrl) {
            return Promise.resolve(null);
        }
        if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
            window.location.href = resolveUrl(targetUrl);
            return Promise.resolve(null);
        }
        return window.omoReplaceFetchedPanelRoot({
            rootSelector: '#omo-checklist-root',
            currentRoot: root,
            url: resolveUrl(targetUrl),
            setLoadingState: setRootLoading,
            beforeReplace: function () {
                window.removeEventListener('omo-runtime-maintenance', handleRuntimeMaintenance);
                window.removeEventListener('omo-checklist-route-change', handleChecklistRouteChange);
            }
        });
    }

    function setDrawerMessage(message, isError) {
        if (!drawerBody) {
            return;
        }
        if (drawerController) {
            drawerController.resetHeader();
        }
        drawerBody.innerHTML = '<div class="generic-section omo-checklist-feedback' + (isError ? ' is-error' : '') + '"></div>';
        drawerBody.firstElementChild.textContent = String(message || '');
    }

    function handleRuntimeMaintenance(event) {
        var result = event && event.detail ? event.detail : {};
        var createdCount = Number(result.checklistProjectsCreated || 0);
        if (!Number.isInteger(createdCount) || createdCount <= 0) {
            return;
        }
        if (drawer && drawer.classList.contains('is-open')) {
            rootNeedsRefresh = true;
            return;
        }
        refreshRoot(currentUrl);
    }

    function openDrawerWithUrl(url) {
        if (!drawer || !drawerBody || !url) {
            return Promise.resolve(false);
        }
        setDrawerMessage(texts.loading, false);
        drawer.hidden = false;
        window.requestAnimationFrame(function () {
            drawer.classList.add('is-open');
        });

        var localToken = ++requestToken;
        return fetch(resolveUrl(url), {
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            cache: 'no-store'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('load_failed');
            }
            return response.text();
        }).then(function (html) {
            if (localToken !== requestToken) {
                return false;
            }
            drawerBody.innerHTML = html;
            if (drawerController) {
                drawerController.applyContentHeader(drawerBody);
            }
            if (typeof window.initGenericComponents === 'function') {
                window.initGenericComponents(drawerBody);
            }
            initializeForms(drawerBody);
            return true;
        }).catch(function () {
            if (localToken === requestToken) {
                setDrawerMessage(texts.loadingError, true);
            }
            return false;
        });
    }

    function closeDrawer() {
        if (!drawer) {
            return;
        }
        requestToken++;
        drawer.classList.remove('is-open');
        window.setTimeout(function () {
            if (drawer.classList.contains('is-open')) {
                return;
            }
            drawer.hidden = true;
            if (drawerBody) {
                drawerBody.innerHTML = '';
            }
            if (drawerController) {
                drawerController.resetHeader();
            }
            if (rootNeedsRefresh) {
                rootNeedsRefresh = false;
                refreshRoot(currentUrl);
            }
        }, 180);
    }

    function buildScopeUrl(scope) {
        var organizationId = Number(root.getAttribute('data-checklist-oid') || 0);
        var routeCid = Number(root.getAttribute('data-checklist-route-cid') || 0);
        var normalizedScope = ['children', 'descendants'].indexOf(scope) !== -1 ? scope : 'contextual';
        var query = ['oid=' + encodeURIComponent(String(organizationId))];
        if (routeCid > 0) {
            query.push('cid=' + encodeURIComponent(String(routeCid)));
        }
        if (normalizedScope !== 'contextual') {
            query.push('checklist_scope=' + encodeURIComponent(normalizedScope));
        }
        return '/omo/api/checklist/index.php?' + query.join('&');
    }

    function buildChecklistRouteToken(checklistId) {
        if (typeof window.omoBuildChecklistRouteToken === 'function') {
            return window.omoBuildChecklistRouteToken(checklistId);
        }
        var resolvedChecklistId = Number(checklistId || 0);
        return Number.isInteger(resolvedChecklistId) && resolvedChecklistId > 0
            ? 'checklist-c' + String(resolvedChecklistId)
            : '';
    }

    function getCurrentRouteToken() {
        if (typeof window.omoParsePopupHashState !== 'function') {
            return '';
        }
        var hashState = window.omoParsePopupHashState();
        return hashState && hashState.routeToken ? String(hashState.routeToken) : '';
    }

    function isChecklistDetailRoute(routeToken) {
        return /^checklist-c\d+$/i.test(String(routeToken || ''));
    }

    function buildDetailUrl(checklistId) {
        var resolvedChecklistId = Number(checklistId || 0);
        if (!Number.isInteger(resolvedChecklistId) || resolvedChecklistId <= 0) {
            return '';
        }
        return detailUrl + '&id=' + encodeURIComponent(String(resolvedChecklistId));
    }

    function navigateChecklistDetail(checklistId, fallbackUrl) {
        var routeToken = buildChecklistRouteToken(checklistId);
        if (routeToken && typeof window.omoOpenDrawerHashState === 'function' && routeToken !== getCurrentRouteToken()) {
            window.omoOpenDrawerHashState(routeToken);
            return;
        }
        openDrawerWithUrl(fallbackUrl || buildDetailUrl(checklistId));
    }

    function updateRecurrenceFields(form, keepSelected) {
        var triggerType = form.querySelector('[data-checklist-trigger-type]');
        var frequency = form.querySelector('[data-checklist-frequency]');
        var schedule = form.querySelector('[data-checklist-schedule]');
        var isScheduled = triggerType && triggerType.value === 'scheduled';
        var isContainer = triggerType && triggerType.value === 'container';
        form.querySelectorAll('[data-checklist-recurrence-field]').forEach(function (field) {
            field.hidden = !isScheduled;
        });
        form.querySelectorAll('[data-checklist-overlap-field]').forEach(function (field) {
            field.hidden = Boolean(isContainer);
        });
        if (!schedule || !frequency) {
            return;
        }
        var optionsByFrequency = {};
        try {
            optionsByFrequency = JSON.parse(form.getAttribute('data-schedule-options') || '{}');
        } catch (error) {
            optionsByFrequency = {};
        }
        var selectedValue = keepSelected && schedule.hasAttribute('data-selected-value')
            ? schedule.getAttribute('data-selected-value')
            : schedule.value;
        schedule.innerHTML = '';
        (optionsByFrequency[frequency.value] || []).forEach(function (optionData) {
            var option = document.createElement('option');
            option.value = String(optionData.value || '');
            option.textContent = String(optionData.label || '');
            schedule.appendChild(option);
        });
        if (Array.prototype.some.call(schedule.options, function (option) { return option.value === selectedValue; })) {
            schedule.value = selectedValue;
        }
        schedule.removeAttribute('data-selected-value');
    }

    function updateActivationFields(form) {
        var activation = form.querySelector('[data-checklist-activation-select]');
        if (!activation) {
            return;
        }
        var mode = activation.value;
        var delayInput = form.querySelector('input[name="delay_value"]');
        var dependencyField = form.querySelector('[data-checklist-dependency-field]');
        if (dependencyField) {
            dependencyField.hidden = mode !== 'after_completion';
        }
        form.querySelectorAll('[data-checklist-delay-field]').forEach(function (field) {
            field.hidden = mode === 'immediate';
        });
        if (delayInput) {
            delayInput.min = mode === 'after_completion' ? '0' : '-3650';
            if (mode === 'after_completion' && Number(delayInput.value || 0) < 0) {
                delayInput.value = '0';
            }
        }
    }

    function updateItemRecurrenceFields(form, keepSelected) {
        var frequency = form.querySelector('[data-checklist-item-frequency]');
        var schedule = form.querySelector('[data-checklist-item-schedule]');
        var scheduleField = form.querySelector('[data-checklist-item-schedule-field]');
        if (!frequency || !schedule || !scheduleField) {
            return;
        }
        var scheduleOptions = {};
        try {
            scheduleOptions = JSON.parse(form.getAttribute('data-item-schedule-options') || '{}');
        } catch (error) {
            scheduleOptions = {};
        }
        var selectedValue = keepSelected && schedule.hasAttribute('data-selected-value')
            ? schedule.getAttribute('data-selected-value')
            : schedule.value;
        schedule.innerHTML = '';
        (scheduleOptions[frequency.value] || []).forEach(function (optionData) {
            var option = document.createElement('option');
            option.value = String(optionData.value || '');
            option.textContent = String(optionData.label || '');
            schedule.appendChild(option);
        });
        if (Array.prototype.some.call(schedule.options, function (option) { return option.value === selectedValue; })) {
            schedule.value = selectedValue;
        }
        schedule.removeAttribute('data-selected-value');
        var isRecurring = frequency.value !== '';
        var isContainer = form.getAttribute('data-checklist-container') === '1';
        scheduleField.hidden = !isRecurring;
        form.querySelectorAll('[data-checklist-item-recurrence-option]').forEach(function (field) {
            field.hidden = !isRecurring;
        });
        form.querySelectorAll('[data-checklist-item-parent-field], [data-checklist-item-activation-field]').forEach(function (field) {
            field.hidden = isContainer || isRecurring;
        });
        if (isContainer) {
            form.querySelectorAll('[data-checklist-dependency-field], [data-checklist-delay-field]').forEach(function (field) {
                field.hidden = true;
            });
        }
        if (isRecurring) {
            var parent = form.querySelector('select[name="parent_item_id"]');
            var activation = form.querySelector('[data-checklist-activation-select]');
            if (parent) {
                parent.value = '0';
            }
            if (activation) {
                activation.value = 'immediate';
                updateActivationFields(form);
            }
        }
    }

    function initializeForms(container) {
        container.querySelectorAll('[data-checklist-editor-form]').forEach(function (form) {
            updateRecurrenceFields(form, true);
        });
        container.querySelectorAll('[data-checklist-item-form]').forEach(function (form) {
            updateActivationFields(form);
            updateItemRecurrenceFields(form, true);
        });
    }

    function setFormFeedback(form, message, type) {
        var feedback = form.querySelector('[data-checklist-editor-feedback]');
        if (!feedback) {
            return;
        }
        feedback.textContent = String(message || '');
        feedback.classList.toggle('is-error', type === 'error');
        feedback.classList.toggle('is-success', type === 'success');
    }

    function submitForm(form) {
        var formData;
        if (!form.reportValidity()) {
            return;
        }
        formData = new FormData(form);
        if (
            typeof window.omoBeginPendingAction === 'function'
            && !window.omoBeginPendingAction(form)
        ) {
            return;
        }
        var editor = form.closest('[data-checklist-editor]');
        if (editor) {
            editor.classList.add('is-saving');
        }
        setFormFeedback(form, '', '');
        fetch(resolveUrl(form.action), {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            cache: 'no-store'
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (payload) {
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || texts.loadingError);
                }
                return payload;
            });
        }).then(function (payload) {
            rootNeedsRefresh = true;
            navigateChecklistDetail(Number(payload.id || 0), payload.detailUrl || '');
            return true;
        }).catch(function (error) {
            setFormFeedback(form, error && error.message ? error.message : texts.loadingError, 'error');
        }).finally(function () {
            if (typeof window.omoEndPendingAction === 'function') {
                window.omoEndPendingAction(form);
            }
            if (editor) {
                editor.classList.remove('is-saving');
            }
        });
    }

    root.addEventListener('click', function (event) {
        var scopeButton = event.target.closest('[data-checklist-scope-option]');
        if (scopeButton) {
            refreshRoot(buildScopeUrl(scopeButton.getAttribute('data-checklist-scope-option') || 'contextual'));
            return;
        }
        if (event.target.closest('[data-checklist-open-create]')) {
            openDrawerWithUrl(createUrl);
            return;
        }
        var detailTarget = event.target.closest('[data-checklist-open-detail]');
        if (detailTarget) {
            var checklistId = Number(detailTarget.getAttribute('data-checklist-id') || 0);
            if (checklistId > 0) {
                navigateChecklistDetail(checklistId);
            }
        }
    });

    root.addEventListener('keydown', function (event) {
        var detailTarget = event.target.closest('[data-checklist-open-detail]');
        if (detailTarget && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            detailTarget.click();
        }
    });

    if (drawer) {
        drawer.addEventListener('click', function (event) {
            if (event.target.closest('[data-checklist-drawer-close]')) {
                if (isChecklistDetailRoute(getCurrentRouteToken()) && typeof window.omoOpenDrawerHashState === 'function') {
                    window.omoOpenDrawerHashState('checklist');
                    return;
                }
                closeDrawer();
                return;
            }
            var navigationButton = event.target.closest('[data-checklist-open-edit], [data-checklist-open-item-form], [data-checklist-open-activation], [data-checklist-back-detail], [data-checklist-editor-cancel]');
            if (navigationButton) {
                var targetUrl = navigationButton.getAttribute('data-url') || '';
                if (targetUrl) {
                    openDrawerWithUrl(targetUrl);
                } else {
                    closeDrawer();
                }
            }
        });

        drawer.addEventListener('change', function (event) {
            var form = event.target.closest('form');
            if (!form) {
                return;
            }
            if (event.target.matches('[data-checklist-trigger-type], [data-checklist-frequency]')) {
                updateRecurrenceFields(form, false);
            }
            if (event.target.matches('[data-checklist-activation-select]')) {
                updateActivationFields(form);
            }
            if (event.target.matches('[data-checklist-item-frequency]')) {
                updateItemRecurrenceFields(form, false);
            }
        });

        drawer.addEventListener('submit', function (event) {
            var form = event.target.closest('[data-checklist-editor-form], [data-checklist-item-form], [data-checklist-activation-form]');
            if (!form) {
                return;
            }
            event.preventDefault();
            submitForm(form);
        });
    }

    function maybeOpenInitialChecklist() {
        var checklistId = initialOpenChecklistId;
        var routeToken = getCurrentRouteToken();
        var routeMatch = String(routeToken || '').match(/^checklist-c(\d+)$/i);
        if (routeMatch) {
            checklistId = Number(routeMatch[1]);
        }
        if (Number.isInteger(checklistId) && checklistId > 0) {
            openDrawerWithUrl(buildDetailUrl(checklistId));
        }
    }

    window.omoOpenChecklistRoute = function (routeToken) {
        if (!root.isConnected) {
            return false;
        }
        var routeMatch = String(routeToken || '').replace(/^#/, '').trim().match(/^checklist-c(\d+)$/i);
        if (!routeMatch) {
            return false;
        }
        var checklistId = Number(routeMatch[1]);
        var targetUrl = buildDetailUrl(checklistId);
        if (!targetUrl) {
            return false;
        }
        openDrawerWithUrl(targetUrl);
        return true;
    };

    function handleChecklistRouteChange(event) {
        var route = event && event.detail ? event.detail : {};
        var checklistId = Number(route.checklistId || 0);
        if (checklistId > 0 && window.omoOpenChecklistRoute('checklist-c' + String(checklistId))) {
            return;
        }
        closeDrawer();
    }

    window.addEventListener('omo-runtime-maintenance', handleRuntimeMaintenance);
    window.addEventListener('omo-checklist-route-change', handleChecklistRouteChange);
    window.setTimeout(maybeOpenInitialChecklist, 40);
})(window, document);
