window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/documents/pv/editor.js"] = function (pageConfig, pageScript) {
(function () {
    const root = document.querySelector('[data-omo-pv-editor-root="1"]');
    if (!root || root.dataset.omoPvEditorReady === '1') {
        return;
    }

    const actionUrl = String(root.getAttribute('data-omo-pv-editor-action-url') || '').trim();
    const documentId = Number(root.getAttribute('data-omo-pv-editor-document-id') || 0);
    const organizationId = Number(root.getAttribute('data-omo-pv-editor-oid') || 0);
    const currentUserId = Number(root.getAttribute('data-omo-pv-editor-user-id') || 0);
    const editorToken = String(root.getAttribute('data-omo-pv-editor-token') || '').trim();
    const nav = root.querySelector('[data-omo-pv-editor-nav]');
    const pointsContainer = root.querySelector('[data-omo-pv-editor-points]');
    const mainPanel = root.querySelector('.omo-pv-editor__main');
    const addButton = root.querySelector('[data-omo-pv-editor-add-point]');
    const addGroupButton = root.querySelector('[data-omo-pv-editor-add-group]');
    const sidebar = root.querySelector('.omo-pv-editor__sidebar');
    const timingPanel = root.querySelector('[data-omo-pv-timing-panel]');
    const timingResizer = root.querySelector('[data-omo-pv-timing-resizer]');
    const sortMenu = root.querySelector('[data-omo-pv-sort-menu]');
    const sortForm = root.querySelector('[data-omo-pv-sort-form]');
    const sortSubmitButton = root.querySelector('[data-omo-pv-sort-submit]');
    const deleteDropzone = root.querySelector('[data-omo-pv-delete-dropzone]');
    const resizer = root.querySelector('[data-omo-pv-editor-resizer]');
    const stageButtons = Array.from(root.querySelectorAll('[data-omo-pv-stage-option]'));
    const stageChoice = root.querySelector('[data-omo-pv-stage-choice]');
    const attendanceRoot = root.querySelector('[data-omo-pv-attendance-root]');
    const attendanceList = root.querySelector('[data-omo-pv-attendance-list]');
    const attendanceCount = root.querySelector('[data-omo-pv-attendance-count]');
    const attendanceEmpty = root.querySelector('[data-omo-pv-attendance-empty]');
    const secretaryName = root.querySelector('[data-omo-pv-secretary-name]');
    const secretaryState = root.querySelector('[data-omo-pv-secretary-state]');
    const claimSecretaryButton = root.querySelector('[data-omo-pv-claim-secretary]');
    const templateToggleButton = root.querySelector('[data-omo-pv-template-toggle]');
    const invitationsMenu = root.querySelector('[data-omo-pv-invitations-menu]');
    const invitationsButton = root.querySelector('[data-omo-pv-invitations-url]');
    const invitationsMenuToggle = root.querySelector('[data-omo-pv-invitations-menu-toggle]');
    const invitationsMenuPanel = root.querySelector('[data-omo-pv-invitations-menu-panel]');
    const invitationsSendButton = root.querySelector('[data-omo-pv-invitations-send-url]');
    const eventScheduleValue = root.querySelector('[data-omo-pv-event-schedule]');
    const eventExtensionMenu = root.querySelector('[data-omo-pv-event-extension-menu]');
    const eventExtensionToggle = root.querySelector('[data-omo-pv-event-extension-toggle]');
    const eventExtensionPanel = root.querySelector('[data-omo-pv-event-extension-panel]');
    const documentTitleInput = root.querySelector('[data-omo-pv-document-title]');
    const documentDescriptionInput = root.querySelector('[data-omo-pv-document-description]');
    const documentVisibilitySelect = root.querySelector('[data-omo-pv-document-visibility]');
    const documentMetaSaveButton = root.querySelector('[data-omo-pv-document-meta-save]');
    const documentAutoSummaryButton = root.querySelector('[data-omo-pv-document-auto-summary]');
    const documentMetaStatus = root.querySelector('[data-omo-pv-document-meta-status]');
    const documentTitleDisplay = root.querySelector('[data-omo-pv-document-title-display]');
    const documentDescriptionDisplay = root.querySelector('[data-omo-pv-document-description-display]');
    const applicationTabsNav = root.querySelector('[data-omo-pv-application-tabs]');
    const applicationWorkspace = root.querySelector('[data-omo-pv-application-workspace]');
    const applicationTabsActionUrl = String(root.getAttribute('data-omo-pv-application-tabs-action-url') || '').trim();
    const applicationTabsCsrf = String(root.getAttribute('data-omo-pv-application-tabs-csrf') || '').trim();
    const canManageApplicationTabs = root.getAttribute('data-omo-pv-application-tabs-manage') === '1';
    const applicationContextHolonId = Number(root.getAttribute('data-omo-pv-application-tabs-cid') || 0);
    const pvEditorSwitchableSurfaces = [mainPanel].filter(Boolean);
    const initialApplicationCatalog = pageConfig.initialApplicationCatalog;
    const initialApplicationTabs = pageConfig.initialApplicationTabs;
    const applicationTabsUi = pageConfig.applicationTabsUi;
    const initialPointPayloads = pageConfig.initialPointPayloads;
    const initialDocumentPayload = pageConfig.initialDocumentPayload;
    const initialAttendancePayload = pageConfig.initialAttendancePayload;
    const initialPollingRevision = pageConfig.initialPollingRevision;
    const attendanceEnabled = pageConfig.attendanceEnabled;
    let eventStartAtIso = pageConfig.eventStartAtIso;
    let eventEndAtIso = pageConfig.eventEndAtIso;
    const saveLabel = pageConfig.saveLabel;
    const savingLabel = pageConfig.savingLabel;
    const savedLabel = pageConfig.savedLabel;
    const takeOverLockLabel = pageConfig.takeOverLockLabel;
    const takeOverWaitingLabel = pageConfig.takeOverWaitingLabel;
    const takeoverDraftCopiedMessage = pageConfig.takeoverDraftCopiedMessage;
    const takeoverDraftCopyFailedMessage = pageConfig.takeoverDraftCopyFailedMessage;
    const autoSummaryLoadingLabel = pageConfig.autoSummaryLoadingLabel;
    const autoSummaryReadyLabel = pageConfig.autoSummaryReadyLabel;
    const autoSummaryAvailable = pageConfig.autoSummaryAvailable;
    let autoSummaryPending = false;
    const dirtyLabel = pageConfig.dirtyLabel;
    const unsavedCloseMessage = pageConfig.unsavedCloseMessage;
    const deletePointMessage = pageConfig.deletePointMessage;
    const deleteItemMessage = pageConfig.deleteItemMessage;
    const reviewIrreversibleMessage = pageConfig.reviewIrreversibleMessage;
    const validateIrreversibleMessage = pageConfig.validateIrreversibleMessage;
    const unsavedHandoverMessage = pageConfig.unsavedHandoverMessage;
    const notStartedValue = pageConfig.notStartedValue;
    const marginLegendLabel = pageConfig.marginLegendLabel;
    const overrunLegendLabel = pageConfig.overrunLegendLabel;
    const groupPointsLabel = pageConfig.groupPointsLabel;
    const groupMinutesLabel = pageConfig.groupMinutesLabel;
    const activeSyncPollDelayMs = 5000;
    const idleSyncPollDelayMs = 15000;
    const pointChangeVersions = new Map();
    let documentMetadataSaving = false;
    const canEmbedDocuments = pageConfig.canEmbedDocuments;
    const resourcePickerOrganizationId = pageConfig.resourcePickerOrganizationId;
    const resourcePickerInitialHolonId = pageConfig.resourcePickerInitialHolonId;
    const resourcePickerScopeUi = pageConfig.resourcePickerScopeUi;
    const embeddableDocuments = pageConfig.embeddableDocuments;
    const documentEmbedUi = pageConfig.documentEmbedUi;
    const canEmbedDecisions = pageConfig.canEmbedDecisions;
    const embeddableDecisions = pageConfig.embeddableDecisions;
    const decisionEmbedUi = pageConfig.decisionEmbedUi;
    const canEmbedProjects = true;
    const embeddableProjects = pageConfig.embeddableProjects;
    const projectEmbedUi = pageConfig.projectEmbedUi;
    const projectEmbedCreateContext = pageConfig.projectEmbedCreateContext;
    const pvPriorityLabels = pageConfig.pvPriorityLabels;
    const pvPriorityFieldLabel = pageConfig.pvPriorityFieldLabel;
    const canEmbedChecklists = pageConfig.canEmbedChecklists;
    const canCompleteChecklistProjects = pageConfig.canCompleteChecklistProjects;
    const embeddableChecklists = pageConfig.embeddableChecklists;
    const checklistEmbedUi = pageConfig.checklistEmbedUi;
    const checklistRunReviewCache = new Map();
    const canEmbedEvents = pageConfig.canEmbedEvents;
    const embeddableEvents = pageConfig.embeddableEvents;
    const eventEmbedUi = pageConfig.eventEmbedUi;
    const eventEmbedCreateContext = pageConfig.eventEmbedCreateContext;
    const canEmbedIndicators = pageConfig.canEmbedIndicators;
    const embeddableIndicators = pageConfig.embeddableIndicators;
    const indicatorEmbedUi = pageConfig.indicatorEmbedUi;
    const editorClientUi = pageConfig.editorClientUi;
    const indicatorValueUi = {
        enabled: true,
        allowedIndicatorIds: embeddableIndicators
            .filter(function (item) { return String(item && item.kind || 'indicator') !== 'group' && item && item.canAddValue; })
            .map(function (item) { return Number(item.id); }),
        placeholder: pageConfig.placeholder,
        inputLabel: pageConfig.placeholder,
        addLabel: pageConfig.addLabel
    };
    const activeLockPointIds = new Set();
    const pendingLockPointIds = new Set();
    const pendingUnlockPointIds = new Set();
    const locallyEngagedPointIds = new Set();
    const pendingTakeoverPointIds = new Set();
    const yieldingTakeoverPointIds = new Set();
    const recoveredTakeoverDraftPointIds = new Set();
    const preMountEditorDrafts = new Map();
    const preMountEditorFocusPointIds = new Set();
    let knownPointSignatures = {};
    let currentPointPayloads = {};
    let currentDocumentPayload = initialDocumentPayload && typeof initialDocumentPayload === 'object'
        ? initialDocumentPayload
        : {};
    let knownDocumentSyncVersion = String(currentDocumentPayload.syncVersion || '');
    let currentAttendancePayload = initialAttendancePayload && typeof initialAttendancePayload === 'object'
        ? initialAttendancePayload
        : null;
    let currentAttendanceSignature = '';
    let knownPollingRevision = String(initialPollingRevision || '');
    let syncPollTimer = null;
    let syncPollPending = null;
    let lockHeartbeatTimer = null;
    let editorLifecycleObserver = null;
    let editorWasPollingActive = false;
    let allowNextExternalClose = false;

    function escapeDocumentEmbedHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    const applicationCatalogById = new Map(initialApplicationCatalog.map(function (application) {
        return [Number(application.applicationId || 0), application];
    }));
    const applicationTabsById = new Map(initialApplicationTabs.map(function (applicationTab) {
        return [Number(applicationTab.tabId || 0), applicationTab];
    }));
    let activeApplicationTabId = 0;

    function buildPvApplicationUrl(applicationTab) {
        const sourceUrl = String(applicationTab && applicationTab.url || '').trim();
        if (sourceUrl === '') {
            return '';
        }

        const resolvedUrl = typeof window.omoResolveAppUrl === 'function'
            ? window.omoResolveAppUrl(sourceUrl)
            : sourceUrl;
        const url = new URL(resolvedUrl, window.location.origin);
        url.searchParams.set('oid', String(organizationId));
        if (applicationContextHolonId > 0) {
            url.searchParams.set('cid', String(applicationContextHolonId));
        }
        url.searchParams.set('pv_application_tab_id', String(Number(applicationTab.tabId || 0)));
        if (initialDocumentPayload.isPvEditor && initialDocumentPayload.pvStage === 'meeting' && editorToken !== '') {
            url.searchParams.set('pv_meeting_document_id', String(documentId));
            url.searchParams.set('pv_meeting_editor_token', editorToken);
        }
        return url.origin === window.location.origin
            ? url.pathname + url.search + url.hash
            : url.toString();
    }

    function setActiveApplicationTab(tabId) {
        const normalizedTabId = Number(tabId || 0);
        const applicationTab = applicationTabsById.get(normalizedTabId) || null;
        if (normalizedTabId > 0 && !applicationTab) {
            return;
        }

        activeApplicationTabId = normalizedTabId;
        if (applicationTabsNav) {
            applicationTabsNav.querySelectorAll('[data-omo-pv-application-tab]').forEach(function (button) {
                const isActive = Number(button.getAttribute('data-omo-pv-application-tab') || 0) === normalizedTabId;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
        }

        pvEditorSwitchableSurfaces.forEach(function (surface) {
            surface.hidden = normalizedTabId > 0;
        });
        if (!applicationWorkspace) {
            return;
        }

        applicationWorkspace.hidden = normalizedTabId === 0;
        applicationWorkspace.querySelectorAll('[data-omo-pv-application-panel]').forEach(function (panel) {
            panel.hidden = Number(panel.getAttribute('data-omo-pv-application-panel') || 0) !== normalizedTabId;
        });
        if (normalizedTabId === 0) {
            return;
        }

        let panel = applicationWorkspace.querySelector('[data-omo-pv-application-panel="' + normalizedTabId + '"]');
        if (!panel) {
            panel = document.createElement('section');
            panel.className = 'omo-pv-editor__application-panel';
            panel.setAttribute('data-omo-pv-application-panel', String(normalizedTabId));
            applicationWorkspace.appendChild(panel);
        }
        panel.hidden = false;
        if (panel.getAttribute('data-omo-pv-application-loaded') === '1') {
            return;
        }

        const applicationUrl = buildPvApplicationUrl(applicationTab);
        if (applicationUrl === '' || typeof window.omoLoadContent !== 'function') {
            panel.innerHTML = '<div class="omo-empty-state">' + escapeDocumentEmbedHtml(applicationTabsUi.error || '') + '</div>';
            return;
        }
        panel.setAttribute('data-omo-pv-application-loaded', '1');
        window.omoLoadContent(panel, applicationUrl, 'panel');
    }

    function renderPvApplicationTab(applicationTab) {
        if (!applicationTabsNav) {
            return;
        }
        const tabId = Number(applicationTab && applicationTab.tabId || 0);
        if (tabId <= 0 || applicationTabsNav.querySelector('[data-omo-pv-application-tab="' + tabId + '"]')) {
            return;
        }

        const item = document.createElement('span');
        item.className = 'omo-pv-editor__application-tab-item';
        item.setAttribute('data-omo-pv-application-tab-item', String(tabId));

        const tabButton = document.createElement('button');
        tabButton.type = 'button';
        tabButton.className = 'generic-tabs__tab omo-pv-editor__application-tab';
        tabButton.setAttribute('data-omo-pv-application-tab', String(tabId));
        tabButton.setAttribute('role', 'tab');
        tabButton.setAttribute('aria-selected', 'false');
        if (String(applicationTab.icon || '').trim() !== '') {
            const icon = document.createElement('img');
            icon.className = 'omo-pv-editor__application-tab-icon black-icon';
            icon.src = String(applicationTab.icon);
            icon.alt = '';
            icon.setAttribute('aria-hidden', 'true');
            tabButton.appendChild(icon);
        }
        const label = document.createElement('span');
        label.className = 'omo-pv-editor__application-tab-label';
        label.textContent = String(applicationTab.label || '');
        tabButton.appendChild(label);
        item.appendChild(tabButton);

        const addButtonNode = applicationTabsNav.querySelector('[data-omo-pv-application-tab-add]');
        applicationTabsNav.insertBefore(item, addButtonNode || null);
    }

    function postPvApplicationTabAction(action, values) {
        if (applicationTabsActionUrl === '' || applicationTabsCsrf === '') {
            return Promise.reject(new Error(applicationTabsUi.error || 'application_tabs_unavailable'));
        }
        return window.fetch(applicationTabsActionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify(Object.assign({
                action: action,
                documentId: documentId,
                organizationId: organizationId,
                csrfToken: applicationTabsCsrf
            }, values || {}))
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (payload) {
                if (!response.ok || !payload.status) {
                    throw new Error(payload.message || applicationTabsUi.error || 'application_tab_action_failed');
                }
                return payload;
            });
        });
    }

    function openPvApplicationPicker() {
        if (!canManageApplicationTabs || typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }

        const existingApplicationIds = new Set(Array.from(applicationTabsById.values()).map(function (tab) {
            return Number(tab.applicationId || 0);
        }));
        let itemsHtml = '';
        initialApplicationCatalog.forEach(function (application) {
            const applicationId = Number(application.applicationId || 0);
            const iconHtml = String(application.icon || '').trim() !== ''
                ? '<img class="omo-pv-editor__application-picker-icon black-icon" src="' + escapeDocumentEmbedHtml(application.icon) + '" alt="" aria-hidden="true">'
                : '<span class="omo-pv-editor__application-picker-icon" aria-hidden="true"></span>';
            itemsHtml += '<label class="generic-soft-panel omo-pv-editor__application-picker-item">'
                + '<input type="checkbox" value="' + applicationId + '" data-omo-pv-application-picker-choice'
                + (existingApplicationIds.has(applicationId) ? ' checked' : '') + '>'
                + iconHtml
                + '<strong>' + escapeDocumentEmbedHtml(application.label || '') + '</strong>'
                + '</label>';
        });

        const pickerHtml = '<form class="omo-pv-editor__application-picker generic-drawer-content" data-omo-pv-application-picker>'
            + '<p>' + escapeDocumentEmbedHtml(applicationTabsUi.pickerDescription || '') + '</p>'
            + (itemsHtml !== ''
                ? '<div class="omo-pv-editor__application-picker-list">' + itemsHtml + '</div>'
                : '<div class="omo-empty-state">' + escapeDocumentEmbedHtml(applicationTabsUi.pickerEmpty || '') + '</div>')
            + '<div class="omo-pv-editor__application-picker-actions">'
            + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-application-picker-cancel>' + escapeDocumentEmbedHtml(applicationTabsUi.pickerCancel || '') + '</button>'
            + (itemsHtml !== '' ? '<button type="submit" class="generic-action-button generic-action-button--main" data-omo-pv-application-picker-submit disabled>' + escapeDocumentEmbedHtml(applicationTabsUi.pickerSubmit || '') + '</button>' : '')
            + '</div></form>';

        window.commonTopbarOpenModal(applicationTabsUi.pickerTitle || '', pickerHtml, 'html');
        const modalBody = document.getElementById('commonTopbarModalBody');
        const form = modalBody ? modalBody.querySelector('[data-omo-pv-application-picker]') : null;
        if (!form) {
            return;
        }
        const submitButton = form.querySelector('[data-omo-pv-application-picker-submit]');
        const getSelectedApplicationIds = function () {
            return new Set(Array.from(form.querySelectorAll('[data-omo-pv-application-picker-choice]:checked')).map(function (input) {
                return Number(input.value || 0);
            }).filter(function (applicationId) {
                return applicationId > 0;
            }));
        };
        const updateSubmitState = function () {
            if (!submitButton) {
                return;
            }
            const selectedApplicationIds = getSelectedApplicationIds();
            const currentApplicationIds = new Set(Array.from(applicationTabsById.values()).map(function (tab) {
                return Number(tab.applicationId || 0);
            }));
            submitButton.disabled = selectedApplicationIds.size === currentApplicationIds.size
                && Array.from(selectedApplicationIds).every(function (applicationId) {
                    return currentApplicationIds.has(applicationId);
                });
        };
        form.addEventListener('change', updateSubmitState);
        const cancelButton = form.querySelector('[data-omo-pv-application-picker-cancel]');
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                window.commonTopbarCloseModal();
            });
        }
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const selectedApplicationIds = getSelectedApplicationIds();
            const currentTabsByApplicationId = new Map(Array.from(applicationTabsById.values()).map(function (tab) {
                return [Number(tab.applicationId || 0), tab];
            }));
            const applicationIdsToAdd = Array.from(selectedApplicationIds).filter(function (applicationId) {
                return !currentTabsByApplicationId.has(applicationId);
            });
            const tabsToRemove = Array.from(currentTabsByApplicationId.values()).filter(function (tab) {
                return !selectedApplicationIds.has(Number(tab.applicationId || 0));
            });
            if (applicationIdsToAdd.length === 0 && tabsToRemove.length === 0) {
                updateSubmitState();
                return;
            }
            if (submitButton) {
                submitButton.disabled = true;
            }
            form.querySelectorAll('[data-omo-pv-application-picker-choice]').forEach(function (input) {
                input.disabled = true;
            });

            const operations = applicationIdsToAdd.map(function (applicationId) {
                return postPvApplicationTabAction('add', {applicationId: applicationId}).then(function (result) {
                    return {type: 'add', applicationId: applicationId, result: result};
                });
            }).concat(tabsToRemove.map(function (applicationTab) {
                const tabId = Number(applicationTab.tabId || 0);
                return postPvApplicationTabAction('remove', {tabId: tabId}).then(function (result) {
                    return {type: 'remove', applicationId: Number(applicationTab.applicationId || 0), tabId: tabId, result: result};
                });
            }));

            Promise.allSettled(operations).then(function (results) {
                let firstAddedTabId = 0;
                let firstError = null;
                results.forEach(function (operationResult) {
                    if (operationResult.status !== 'fulfilled') {
                        firstError = firstError || operationResult.reason;
                        return;
                    }

                    const operation = operationResult.value;
                    if (operation.type === 'add') {
                        const application = applicationCatalogById.get(operation.applicationId);
                        const tabId = Number(operation.result && operation.result.tabId || 0);
                        if (!application || tabId <= 0) {
                            return;
                        }
                        const applicationTab = Object.assign({}, application, {tabId: tabId});
                        applicationTabsById.set(tabId, applicationTab);
                        renderPvApplicationTab(applicationTab);
                        if (firstAddedTabId === 0) {
                            firstAddedTabId = tabId;
                        }
                        return;
                    }

                    const tabId = Number(operation.tabId || 0);
                    if (activeApplicationTabId === tabId) {
                        setActiveApplicationTab(0);
                    }
                    applicationTabsById.delete(tabId);
                    const item = applicationTabsNav.querySelector('[data-omo-pv-application-tab-item="' + tabId + '"]');
                    const panel = applicationWorkspace ? applicationWorkspace.querySelector('[data-omo-pv-application-panel="' + tabId + '"]') : null;
                    if (panel && window.jQuery) {
                        const request = window.jQuery(panel).data('omoXhr');
                        if (request && request.readyState !== 4) {
                            request.abort();
                        }
                    }
                    if (item) item.remove();
                    if (panel) panel.remove();
                });

                if (firstError) {
                    form.querySelectorAll('[data-omo-pv-application-picker-choice]').forEach(function (input) {
                        const applicationId = Number(input.value || 0);
                        input.disabled = false;
                        input.checked = Array.from(applicationTabsById.values()).some(function (tab) {
                            return Number(tab.applicationId || 0) === applicationId;
                        });
                    });
                    updateSubmitState();
                    window.alert(firstError && firstError.message ? firstError.message : applicationTabsUi.error || '');
                    return;
                }

                window.commonTopbarCloseModal();
                if (firstAddedTabId > 0) {
                    setActiveApplicationTab(firstAddedTabId);
                }
            });
        });
    }

    if (applicationTabsNav) {
        applicationTabsNav.addEventListener('click', function (event) {
            if (event.target.closest('[data-omo-pv-application-tab-add]')) {
                event.preventDefault();
                openPvApplicationPicker();
                return;
            }

            const tabButton = event.target.closest('[data-omo-pv-application-tab]');
            if (tabButton) {
                event.preventDefault();
                setActiveApplicationTab(Number(tabButton.getAttribute('data-omo-pv-application-tab') || 0));
            }
        });
    }

    function truncateDocumentEmbedSummary(value, maximumLength, maximumSentences) {
        const limit = Number.isInteger(maximumLength) ? maximumLength : 420;
        const sentenceLimit = Number.isInteger(maximumSentences) ? maximumSentences : 3;
        let summary = String(value || '').replace(/\s+/g, ' ').trim();
        if (summary === '') {
            return '';
        }

        const sentences = summary.split(/(?<=[.!?])\s+/);
        if (sentences.length > sentenceLimit) {
            summary = sentences.slice(0, sentenceLimit).join(' ').trim() + '...';
        }

        return summary.length > limit
            ? summary.slice(0, Math.max(1, limit - 3)).trim() + '...'
            : summary;
    }

    function mountPvResourceScopePicker(modalBody, selector, render) {
        const host = modalBody instanceof Element ? modalBody.querySelector(selector) : null;
        if (!(host instanceof Element) || typeof window.omoMountHolonScopePicker !== 'function') {
            const picker = host instanceof Element ? host.closest('.omo-resource-picker') : null;
            if (picker instanceof Element) {
                picker.classList.remove('omo-resource-picker');
            }
            return null;
        }

        return window.omoMountHolonScopePicker({
            host: host,
            organizationId: resourcePickerOrganizationId,
            initialHolonId: resourcePickerInitialHolonId,
            initialScope: 'local',
            labels: resourcePickerScopeUi,
            onChange: render
        });
    }

    function openPvEmbeddedResourceByHash(resourceHash) {
        const normalizedHash = String(resourceHash || '').replace(/^#/, '');
        if (!/^(?:(?:documents|decision|projects)-d\d+|(?:processus|checklist)-c\d+|calendar-e\d+|stats(?:-(?:i|g)\d+)?)$/.test(normalizedHash)) {
            return;
        }

        if (/^projects-d\d+$/i.test(normalizedHash) && typeof window.omoOpenProjectEmbedRoute === 'function') {
            window.omoOpenProjectEmbedRoute(normalizedHash, {sourceElement: root});
            return;
        }

        if (typeof window.omoPeekPersistentExternalPanelDrawer === 'function') {
            window.omoPeekPersistentExternalPanelDrawer({
                persistKeyPrefix: 'omo-pv-preparation-',
                contentSelector: '[data-omo-pv-editor-root]'
            });
        }

        if (typeof window.omoSetDrawerHashState === 'function') {
            window.omoSetDrawerHashState({routeToken: normalizedHash, open: true});
            return;
        }

        window.location.hash = '#' + normalizedHash;
    }

    if (root instanceof Element) {
        root.addEventListener('focusin', function (event) {
            const target = event.target instanceof Element ? event.target : null;
            const card = target ? target.closest('[data-omo-pv-point-card]') : null;
            const pointId = card ? Number(card.getAttribute('data-omo-pv-point-card') || 0) : 0;
            if (pointId > 0) {
                locallyEngagedPointIds.add(pointId);
                if (
                    target instanceof HTMLTextAreaElement
                    && target.closest('[data-omo-pv-point-editor-host]')
                ) {
                    preMountEditorFocusPointIds.add(pointId);
                }
                setFocusedPoint(pointId);
                syncPointLockState(pointId);
            }
        }, true);

        const protectImmediatePointInput = function (event) {
            if (event.isTrusted !== true) {
                return;
            }
            const target = event.target instanceof Element ? event.target : null;
            if (target && target.closest('[data-omo-pv-point-handled]')) {
                return;
            }
            const card = target ? target.closest('[data-omo-pv-point-card][data-omo-pv-point-editable="1"]') : null;
            const pointId = card ? Number(card.getAttribute('data-omo-pv-point-card') || 0) : 0;
            if (pointId <= 0) {
                return;
            }

            locallyEngagedPointIds.add(pointId);
            if (
                event.type === 'input'
                && target instanceof HTMLTextAreaElement
                && target.closest('[data-omo-pv-point-editor-host]')
            ) {
                preMountEditorDrafts.set(pointId, String(target.value || ''));
            }
            if (card.getAttribute('data-omo-pv-point-dirty') === '1') {
                return;
            }
            setPointDirtySuppressed(pointId, false);
            ensurePointLock(pointId);
            markPointDirty(pointId, true);
        };
        root.addEventListener('beforeinput', protectImmediatePointInput, true);
        root.addEventListener('input', protectImmediatePointInput, true);
        root.addEventListener('change', protectImmediatePointInput, true);

        root.addEventListener('focusout', function (event) {
            const target = event.target instanceof Element ? event.target : null;
            const card = target ? target.closest('[data-omo-pv-point-card]') : null;
            if (!card) {
                return;
            }

            const pointId = Number(card.getAttribute('data-omo-pv-point-card') || 0);
            window.setTimeout(function () {
                const activeElement = document.activeElement;
                const activeCard = activeElement instanceof Element
                    ? activeElement.closest('[data-omo-pv-point-card]')
                    : null;
                const activePointId = activeCard ? Number(activeCard.getAttribute('data-omo-pv-point-card') || 0) : 0;
                if (pointId > 0 && activePointId !== pointId) {
                    const focusTemporarilyLostDuringLock = pendingLockPointIds.has(pointId)
                        && (activeElement === document.body || activeElement === document.documentElement);
                    if (focusTemporarilyLostDuringLock) {
                        return;
                    }
                    locallyEngagedPointIds.delete(pointId);
                    preMountEditorFocusPointIds.delete(pointId);
                    clearFocusedPoint(pointId);
                    syncPointLockState(pointId);
                }
            }, 0);
        }, true);

        root.addEventListener('click', function (event) {
            const targetNode = event.target && event.target.closest ? event.target : null;
            const checklistArchiveButton = targetNode ? targetNode.closest('[data-omo-checklist-complete-archive]') : null;
            if (checklistArchiveButton instanceof HTMLButtonElement) {
                event.preventDefault();
                event.stopPropagation();
                completePvChecklistProject(checklistArchiveButton);
                return;
            }
            const checklistContainerToggle = targetNode ? targetNode.closest('[data-omo-checklist-container-toggle]') : null;
            if (checklistContainerToggle instanceof HTMLButtonElement) {
                event.preventDefault();
                event.stopPropagation();
                loadPvChecklistContainerReview(checklistContainerToggle);
                return;
            }
            const checklistRunToggle = targetNode ? targetNode.closest('[data-omo-checklist-run-toggle]') : null;
            if (checklistRunToggle instanceof HTMLButtonElement) {
                event.preventDefault();
                event.stopPropagation();
                loadPvChecklistRunReview(checklistRunToggle);
                return;
            }
            const documentLink = targetNode ? targetNode.closest('.omo-document-embed a[href^="#documents-d"]') : null;
            const decisionLink = targetNode ? targetNode.closest('.omo-decision-embed a[href^="#decision-d"]') : null;
            const eventLink = targetNode ? targetNode.closest('.omo-event-embed a[href^="#calendar-e"]') : null;
            const indicatorLink = targetNode ? targetNode.closest('.omo-indicator-embed a[href^="#stats"]') : null;
            const projectLink = targetNode ? targetNode.closest('.omo-project-embed a[href^="#projects-d"], .omo-checklist-embed .omo-project-embed__child-title[href^="#projects-d"], .omo-checklist-embed .omo-checklist-embed__item-segment[href^="#projects-d"]') : null;
            const checklistLink = targetNode ? targetNode.closest('.omo-checklist-embed a[href^="#processus-c"], .omo-checklist-embed a[href^="#checklist-c"]') : null;
            const resourceLink = documentLink || decisionLink || eventLink || indicatorLink || projectLink || checklistLink;
            if (!resourceLink || resourceLink.matches('[data-omo-document-embed-external], .omo-document-embed__external, .omo-project-embed__external')) {
                return;
            }

            const resourceHash = String(resourceLink.getAttribute('href') || '');
            if (!/^#(?:(?:documents|decision|projects)-d\d+|(?:processus|checklist)-c\d+|calendar-e\d+|stats(?:-(?:i|g)\d+)?)$/.test(resourceHash)) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            openPvEmbeddedResourceByHash(resourceHash);
        }, true);
    }

    function insertPvEmbedIntoField(field, targetNode, markerNode, embedHtml) {
        if (!field || String(embedHtml || '').trim() === '') {
            return false;
        }

        if (targetNode instanceof Element && typeof field.replaceNodeWithHtml === 'function') {
            return String(field.replaceNodeWithHtml(targetNode, embedHtml) || '') !== '';
        }

        if (typeof field.replaceMarkerWithHtml === 'function') {
            return String(field.replaceMarkerWithHtml(markerNode || null, embedHtml) || '') !== '';
        }

        if (typeof field.insertHtmlAtCursor === 'function') {
            return String(field.insertHtmlAtCursor(embedHtml) || '') !== '';
        }

        return false;
    }

    function buildPvDocumentEmbedHtml(documentItem) {
        const documentId = Number.parseInt(String(documentItem && documentItem.id || ''), 10);
        if (!Number.isInteger(documentId) || documentId <= 0) {
            return '';
        }

        const title = String(documentItem.title || '').trim() || String(documentEmbedUi.fallbackTitle || '').replace('{id}', String(documentId));
        const description = truncateDocumentEmbedSummary(documentItem.description || '');
        const documentHash = '#documents-d' + String(documentId);
        const externalUrl = String(window.location.pathname || '/omo/') + documentHash;
        let html = '<span class="omo-document-embed" contenteditable="false" data-omo-embed-type="document"'
            + ' data-omo-document-id="' + String(documentId) + '"'
            + ' data-omo-document-title="' + escapeDocumentEmbedHtml(title) + '"';

        if (description !== '') {
            html += ' data-omo-document-description="' + escapeDocumentEmbedHtml(description) + '"';
        }

        html += '><strong><a class="omo-document-embed__title" href="' + documentHash + '">' + escapeDocumentEmbedHtml(title) + '</a>'
            + '<a class="omo-document-embed__external" href="' + escapeDocumentEmbedHtml(externalUrl) + '" target="_blank" rel="noopener noreferrer" title="' + escapeDocumentEmbedHtml(documentEmbedUi.openExternal || '') + '" aria-label="' + escapeDocumentEmbedHtml(documentEmbedUi.openExternal || '') + '">&#8599;</a></strong>';
        if (description !== '') {
            html += '<em>' + escapeDocumentEmbedHtml(description) + '</em>';
        }
        return html + '</span>';
    }

    function openPvDocumentEmbedPicker(field, targetNode) {
        if (
            !canEmbedDocuments
            || !field
            || typeof field.createTemporaryCursorMarker !== 'function'
            || typeof field.replaceMarkerWithHtml !== 'function'
            || typeof window.commonTopbarOpenModal !== 'function'
        ) {
            return;
        }

        const currentDocumentId = targetNode instanceof Element
            ? Number.parseInt(String(targetNode.getAttribute('data-omo-document-id') || ''), 10)
            : 0;
        let marker = targetNode ? null : field.createTemporaryCursorMarker();
        let resolved = false;
        const modalHtml = ''
            + '<div class="omo-document-embed-picker omo-resource-picker generic-drawer-content">'
            + '<aside class="omo-resource-picker__navigation" data-omo-pv-document-embed-scope></aside>'
            + '<div class="omo-resource-picker__content">'
            + '<label class="omo-resource-picker__quick-search"><img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true"><input type="search" class="generic-form-control" data-omo-pv-document-embed-search aria-label="' + escapeDocumentEmbedHtml(documentEmbedUi.search || '') + '" placeholder="' + escapeDocumentEmbedHtml(documentEmbedUi.quickSearchPlaceholder || '') + '"></label>'
            + '<div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select" data-omo-pv-document-embed-select aria-label="' + escapeDocumentEmbedHtml(documentEmbedUi.visibleDocuments || '') + '" size="10"></select></div>'
            + '<div class="omo-document-embed-picker__preview"><div class="omo-document-embed-picker__preview-title" data-omo-pv-document-embed-title></div>'
            + '<div class="omo-document-embed-picker__preview-description" data-omo-pv-document-embed-description hidden></div></div>'
            + '<div class="omo-document-embed-picker__actions">'
            + (targetNode ? '<button type="button" class="generic-action-button generic-action-button--danger" data-omo-pv-embed-remove>' + escapeDocumentEmbedHtml(documentEmbedUi.remove || '') + '</button>' : '')
            + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-document-embed-cancel>' + escapeDocumentEmbedHtml(documentEmbedUi.cancel || '') + '</button>'
            + '<button type="button" class="generic-action-button generic-action-button--main" data-omo-pv-document-embed-insert disabled>' + escapeDocumentEmbedHtml(documentEmbedUi.insert || '') + '</button></div></div></div>';

        window.commonTopbarOpenModal(documentEmbedUi.modalTitle || '', modalHtml, 'html');
        const modalBody = document.getElementById('commonTopbarModalBody');
        if (!(modalBody instanceof Element)) {
            if (marker) field.removeTemporaryMarker(marker);
            return;
        }

        const searchNode = modalBody.querySelector('[data-omo-pv-document-embed-search]');
        const selectNode = modalBody.querySelector('[data-omo-pv-document-embed-select]');
        const titleNode = modalBody.querySelector('[data-omo-pv-document-embed-title]');
        const descriptionNode = modalBody.querySelector('[data-omo-pv-document-embed-description]');
        const cancelButton = modalBody.querySelector('[data-omo-pv-document-embed-cancel]');
        const insertButton = modalBody.querySelector('[data-omo-pv-document-embed-insert]');
        const removeButton = modalBody.querySelector('[data-omo-pv-embed-remove]');
        let selectedItem = null;
        let scopePicker = null;

        const cleanup = function () {
            if (marker && typeof field.removeTemporaryMarker === 'function') {
                field.removeTemporaryMarker(marker);
            }
            marker = null;
        };
        const render = function () {
            const query = String(searchNode && searchNode.value || '').trim().toLowerCase();
            const matches = embeddableDocuments.filter(function (item) {
                return (!scopePicker || scopePicker.matches(item.contextHolonId))
                    && (query === '' || [item.title, item.description, item.contextLabel].join(' ').toLowerCase().indexOf(query) >= 0);
            });
            if (selectNode) {
                selectNode.innerHTML = '';
                matches.forEach(function (item) {
                    const option = document.createElement('option');
                    option.value = String(item.id || '');
                    option.textContent = String(item.title || '').trim() || String(documentEmbedUi.fallbackTitle || '').replace('{id}', String(item.id || ''));
                    selectNode.appendChild(option);
                });
                selectNode.disabled = matches.length === 0;
            }
            selectedItem = matches.find(function (item) { return Number(item.id) === currentDocumentId; }) || matches[0] || null;
            if (selectNode && selectedItem) selectNode.value = String(selectedItem.id);
            updatePreview();
        };
        const updatePreview = function () {
            if (selectNode && selectNode.value !== '') {
                selectedItem = embeddableDocuments.find(function (item) { return String(item.id || '') === String(selectNode.value); }) || null;
            }
            const title = selectedItem ? (String(selectedItem.title || '').trim() || String(documentEmbedUi.fallbackTitle || '').replace('{id}', String(selectedItem.id || ''))) : String(documentEmbedUi.none || '');
            if (titleNode) titleNode.textContent = title;
            if (descriptionNode) {
                descriptionNode.textContent = selectedItem ? String(selectedItem.description || '') : '';
                descriptionNode.hidden = descriptionNode.textContent === '';
            }
            if (insertButton) insertButton.disabled = !selectedItem;
        };

        scopePicker = mountPvResourceScopePicker(modalBody, '[data-omo-pv-document-embed-scope]', render);

        window.addEventListener('common-topbar-modal-close', function () {
            if (!resolved) cleanup();
        }, {once: true});
        if (searchNode) {
            searchNode.addEventListener('input', render);
            searchNode.focus();
        }
        if (selectNode) selectNode.addEventListener('change', updatePreview);
        if (cancelButton) cancelButton.addEventListener('click', function () {
            cleanup();
            if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal();
        });
        if (removeButton) removeButton.addEventListener('click', function () {
            if (targetNode && typeof field.removeNode === 'function') {
                resolved = field.removeNode(targetNode);
            }
            if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal();
        });
        if (insertButton) insertButton.addEventListener('click', function () {
            const embedHtml = buildPvDocumentEmbedHtml(selectedItem);
            resolved = insertPvEmbedIntoField(field, targetNode, marker, embedHtml);
            if (resolved) {
                marker = null;
            }
            if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal();
        });
        render();
    }

    function buildPvDecisionEmbedHtml(decisionItem) {
        const decisionId = Number.parseInt(String(decisionItem && decisionItem.id || ''), 10);
        if (!Number.isInteger(decisionId) || decisionId <= 0) {
            return '';
        }

        const title = String(decisionItem.title || '').trim() || String(decisionEmbedUi.fallbackTitle || '').replace('{id}', String(decisionId));
        const typeLabel = String(decisionItem.typeLabel || '').trim();
        const summary = String(decisionItem.summary || '').trim();
        const displaySummary = [typeLabel, summary].filter(function (value) { return value !== ''; }).join(' - ');
        return '<span class="omo-decision-embed" contenteditable="false" data-omo-embed-type="decision"'
            + ' data-omo-decision-id="' + String(decisionId) + '"'
            + ' data-omo-decision-title="' + escapeDocumentEmbedHtml(title) + '"'
            + (typeLabel !== '' ? ' data-omo-decision-type="' + escapeDocumentEmbedHtml(typeLabel) + '"' : '')
            + (summary !== '' ? ' data-omo-decision-summary="' + escapeDocumentEmbedHtml(summary) + '"' : '')
            + '><strong><a class="omo-decision-embed__title" href="#decision-d' + String(decisionId) + '">' + escapeDocumentEmbedHtml(title) + '</a></strong>'
            + (displaySummary !== '' ? '<em>' + escapeDocumentEmbedHtml(displaySummary) + '</em>' : '')
            + '</span>';
    }

    function openPvDecisionEmbedPicker(field, targetNode) {
        if (!canEmbedDecisions || !field || typeof field.createTemporaryCursorMarker !== 'function' || typeof field.replaceMarkerWithHtml !== 'function' || typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }

        const currentDecisionId = targetNode instanceof Element
            ? Number.parseInt(String(targetNode.getAttribute('data-omo-decision-id') || ''), 10)
            : 0;
        let marker = targetNode ? null : field.createTemporaryCursorMarker();
        let resolved = false;
        const modalHtml = '<div class="omo-document-embed-picker omo-resource-picker generic-drawer-content">'
            + '<aside class="omo-resource-picker__navigation" data-omo-pv-decision-embed-scope></aside>'
            + '<div class="omo-resource-picker__content">'
            + '<label class="omo-resource-picker__quick-search"><img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true"><input type="search" class="generic-form-control" data-omo-pv-decision-embed-search aria-label="' + escapeDocumentEmbedHtml(decisionEmbedUi.search || '') + '" placeholder="' + escapeDocumentEmbedHtml(decisionEmbedUi.quickSearchPlaceholder || '') + '"></label>'
            + '<div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select" data-omo-pv-decision-embed-select aria-label="' + escapeDocumentEmbedHtml(decisionEmbedUi.visibleDecisions || '') + '" size="10"></select></div>'
            + '<div class="omo-document-embed-picker__preview"><div class="omo-document-embed-picker__preview-title" data-omo-pv-decision-embed-title></div><div class="omo-document-embed-picker__preview-context" data-omo-pv-decision-embed-type hidden></div><div class="omo-document-embed-picker__preview-description" data-omo-pv-decision-embed-summary hidden></div></div>'
            + '<div class="omo-document-embed-picker__actions">'
            + (targetNode ? '<button type="button" class="generic-action-button generic-action-button--danger" data-omo-pv-embed-remove>' + escapeDocumentEmbedHtml(decisionEmbedUi.remove || '') + '</button>' : '')
            + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-decision-embed-cancel>' + escapeDocumentEmbedHtml(decisionEmbedUi.cancel || '') + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-pv-decision-embed-insert disabled>' + escapeDocumentEmbedHtml(decisionEmbedUi.insert || '') + '</button></div></div></div>';

        window.commonTopbarOpenModal(decisionEmbedUi.modalTitle || '', modalHtml, 'html');
        const modalBody = document.getElementById('commonTopbarModalBody');
        if (!(modalBody instanceof Element)) {
            if (marker) field.removeTemporaryMarker(marker);
            return;
        }

        const searchNode = modalBody.querySelector('[data-omo-pv-decision-embed-search]');
        const selectNode = modalBody.querySelector('[data-omo-pv-decision-embed-select]');
        const titleNode = modalBody.querySelector('[data-omo-pv-decision-embed-title]');
        const typeNode = modalBody.querySelector('[data-omo-pv-decision-embed-type]');
        const summaryNode = modalBody.querySelector('[data-omo-pv-decision-embed-summary]');
        const cancelButton = modalBody.querySelector('[data-omo-pv-decision-embed-cancel]');
        const insertButton = modalBody.querySelector('[data-omo-pv-decision-embed-insert]');
        const removeButton = modalBody.querySelector('[data-omo-pv-embed-remove]');
        let selectedItem = null;
        let scopePicker = null;
        const cleanup = function () { if (marker) field.removeTemporaryMarker(marker); marker = null; };
        const updatePreview = function () {
            if (selectNode && selectNode.value !== '') selectedItem = embeddableDecisions.find(function (item) { return String(item.id) === String(selectNode.value); }) || null;
            if (titleNode) titleNode.textContent = selectedItem ? (String(selectedItem.title || '').trim() || String(decisionEmbedUi.fallbackTitle || '').replace('{id}', String(selectedItem.id))) : String(decisionEmbedUi.none || '');
            if (typeNode) { typeNode.textContent = selectedItem ? String(selectedItem.typeLabel || '') : ''; typeNode.hidden = typeNode.textContent === ''; }
            if (summaryNode) { summaryNode.textContent = selectedItem ? String(selectedItem.summary || '') : ''; summaryNode.hidden = summaryNode.textContent === ''; }
            if (insertButton) insertButton.disabled = !selectedItem;
        };
        const render = function () {
            const query = String(searchNode && searchNode.value || '').trim().toLowerCase();
            const matches = embeddableDecisions.filter(function (item) { return (!scopePicker || scopePicker.matches(item.contextHolonId)) && (query === '' || [item.title, item.typeLabel, item.summary].join(' ').toLowerCase().indexOf(query) >= 0); });
            if (selectNode) {
                selectNode.innerHTML = '';
                matches.forEach(function (item) { const option = document.createElement('option'); option.value = String(item.id); option.textContent = (String(item.title || '').trim() || String(decisionEmbedUi.fallbackTitle || '').replace('{id}', String(item.id))) + (item.typeLabel ? ' - ' + String(item.typeLabel) : ''); selectNode.appendChild(option); });
                selectNode.disabled = matches.length === 0;
            }
            selectedItem = matches.find(function (item) { return Number(item.id) === currentDecisionId; }) || matches[0] || null;
            if (selectNode && selectedItem) selectNode.value = String(selectedItem.id);
            updatePreview();
        };
        scopePicker = mountPvResourceScopePicker(modalBody, '[data-omo-pv-decision-embed-scope]', render);
        window.addEventListener('common-topbar-modal-close', function () { if (!resolved) cleanup(); }, {once: true});
        if (searchNode) { searchNode.addEventListener('input', render); searchNode.focus(); }
        if (selectNode) selectNode.addEventListener('change', updatePreview);
        if (cancelButton) cancelButton.addEventListener('click', function () { cleanup(); window.commonTopbarCloseModal(); });
        if (removeButton) removeButton.addEventListener('click', function () { if (targetNode && typeof field.removeNode === 'function') resolved = field.removeNode(targetNode); window.commonTopbarCloseModal(); });
        if (insertButton) insertButton.addEventListener('click', function () { const embedHtml = buildPvDecisionEmbedHtml(selectedItem); resolved = insertPvEmbedIntoField(field, targetNode, marker, embedHtml); if (resolved) marker = null; window.commonTopbarCloseModal(); });
        render();
    }

    function buildPvChecklistEmbedHtml(item) {
        const checklistId = Number.parseInt(String(item && item.id || ''), 10);
        if (!Number.isInteger(checklistId) || checklistId <= 0) return '';
        const title = String(item.title || '').trim() || ('Processus #' + String(checklistId));
        const summary = String(item.contextLabel || '').trim();
        return '<span class="omo-checklist-embed" contenteditable="false" data-omo-embed-type="checklist" data-omo-checklist-id="' + String(checklistId) + '" data-omo-checklist-title="' + escapeDocumentEmbedHtml(title) + '"><strong><a href="#processus-c' + String(checklistId) + '">' + escapeDocumentEmbedHtml(title) + '</a></strong>' + (summary ? '<em>' + escapeDocumentEmbedHtml(summary) + '</em>' : '') + '</span>';
    }

    function openPvChecklistEmbedPicker(field, targetNode) {
        if (!canEmbedChecklists || !field || typeof field.createTemporaryCursorMarker !== 'function' || typeof window.commonTopbarOpenModal !== 'function') return;
        const currentId = targetNode instanceof Element ? Number(targetNode.getAttribute('data-omo-checklist-id') || 0) : 0;
        let marker = targetNode ? null : field.createTemporaryCursorMarker(), resolved = false, selected = null;
        const html = '<div class="omo-document-embed-picker omo-resource-picker"><aside class="omo-resource-picker__navigation" data-omo-pv-checklist-scope></aside><div class="omo-resource-picker__content"><label class="omo-resource-picker__quick-search"><input type="search" class="generic-form-control" data-omo-pv-checklist-search placeholder="' + escapeDocumentEmbedHtml(checklistEmbedUi.quickSearchPlaceholder || '') + '"></label><div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select" data-omo-pv-checklist-select size="10" aria-label="' + escapeDocumentEmbedHtml(checklistEmbedUi.visible || '') + '"></select></div><div class="omo-document-embed-picker__preview" data-omo-pv-checklist-preview></div><div class="omo-document-embed-picker__actions">' + (targetNode ? '<button type="button" class="generic-action-button generic-action-button--danger" data-omo-pv-checklist-remove>' + escapeDocumentEmbedHtml(checklistEmbedUi.remove || '') + '</button>' : '') + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-checklist-cancel>' + escapeDocumentEmbedHtml(checklistEmbedUi.cancel || '') + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-pv-checklist-insert disabled>' + escapeDocumentEmbedHtml(checklistEmbedUi.insert || '') + '</button></div></div></div>';
        window.commonTopbarOpenModal(checklistEmbedUi.modalTitle || '', html, 'html');
        const body = document.getElementById('commonTopbarModalBody'); if (!(body instanceof Element)) { if (marker) field.removeTemporaryMarker(marker); return; }
        const checklistPicker = body.querySelector('.omo-document-embed-picker');
        if (checklistPicker) checklistPicker.classList.add('generic-drawer-content');
        const search = body.querySelector('[data-omo-pv-checklist-search]'), select = body.querySelector('[data-omo-pv-checklist-select]'), preview = body.querySelector('[data-omo-pv-checklist-preview]'), insert = body.querySelector('[data-omo-pv-checklist-insert]');
        let scopePicker = null;
        const cleanup = function () { if (marker) field.removeTemporaryMarker(marker); marker = null; };
        const update = function () { selected = embeddableChecklists.find(function (entry) { return String(entry.id) === String(select && select.value || ''); }) || null; if (preview) preview.innerHTML = selected ? buildPvChecklistEmbedHtml(selected) : escapeDocumentEmbedHtml(checklistEmbedUi.none || ''); if (insert) insert.disabled = !selected; };
        const render = function () { const query = String(search && search.value || '').trim().toLowerCase(); const matches = embeddableChecklists.filter(function (entry) { const holonId = Number(entry.contextHolonId || 0); const inScope = !scopePicker || scopePicker.matches(holonId); return inScope && (query === '' || [entry.title, entry.contextLabel, entry.summary].join(' ').toLowerCase().indexOf(query) >= 0); }); if (select) { select.innerHTML = ''; matches.forEach(function (entry) { const option = document.createElement('option'); option.value = String(entry.id); option.textContent = String(entry.title || ''); select.appendChild(option); }); select.value = String((matches.find(function (entry) { return Number(entry.id) === currentId; }) || matches[0] || {}).id || ''); } update(); };
        scopePicker = mountPvResourceScopePicker(body, '[data-omo-pv-checklist-scope]', render);
        body.querySelectorAll('[data-omo-pv-checklist-cancel]').forEach(function (button) { button.addEventListener('click', function () { cleanup(); window.commonTopbarCloseModal(); }); });
        const remove = body.querySelector('[data-omo-pv-checklist-remove]'); if (remove) remove.addEventListener('click', function () { if (targetNode) resolved = field.removeNode(targetNode); window.commonTopbarCloseModal(); });
        if (search) search.addEventListener('input', render); if (select) select.addEventListener('change', update); if (insert) insert.addEventListener('click', function () { const embed = buildPvChecklistEmbedHtml(selected); if (!embed) return; resolved = insertPvEmbedIntoField(field, targetNode, marker, embed); if (resolved) marker = null; if (resolved) window.setTimeout(function () { refreshPvChecklistEmbedReviews(field); }, 0); window.commonTopbarCloseModal(); });
        window.addEventListener('common-topbar-modal-close', function () { if (!resolved) cleanup(); }, {once: true}); render();
    }

    function buildPvProjectEmbedHtml(projectItem) {
        const projectId = Number.parseInt(String(projectItem && projectItem.id || ''), 10);
        if (!Number.isInteger(projectId) || projectId <= 0) return '';
        const title = String(projectItem.title || '').trim() || String(projectEmbedUi.fallbackTitle || '').replace('{id}', String(projectId));
        const contextLabel = String(projectItem.contextLabel || '').trim();
        const responsibleLabel = String(projectItem.responsibleLabel || '').trim();
        const status = String(projectItem.status || '').trim();
        const statusLabel = String(projectItem.statusLabel || '').trim();
        const priorityLabel = String(projectItem.priorityLabel || '').trim();
        const sizeLabel = String(projectItem.sizeLabel || '').trim();
        const plannedStartLabel = String(projectItem.plannedStartLabel || '').trim();
        const plannedEndLabel = String(projectItem.plannedEndLabel || '').trim();
        const projectHash = '#projects-d' + String(projectId);
        const externalUrl = String(window.location.pathname || '/omo/') + projectHash;
        const metadata = [contextLabel, responsibleLabel];
        if (plannedStartLabel !== '') metadata.push(String(projectEmbedUi.plannedDate || '').replace('{date}', plannedStartLabel));
        if (plannedEndLabel !== '') metadata.push(String(projectEmbedUi.endDate || '').replace('{date}', plannedEndLabel));
        const statusClass = /^(?:someday|ready|in_progress|blocked|review|done)$/.test(status) ? status : '';
        const statusCapsule = statusLabel !== ''
            ? '<em class="omo-project-embed__status' + (statusClass !== '' ? ' omo-project-embed__status--' + statusClass : '') + '">' + escapeDocumentEmbedHtml(statusLabel) + '</em>'
            : '';
        const priorityCapsule = statusCapsule + (priorityLabel !== '' ? '<em>' + escapeDocumentEmbedHtml(priorityLabel) + '</em>' : '');
        return '<span class="omo-project-embed" contenteditable="false" data-omo-embed-type="project" data-omo-project-id="' + String(projectId) + '" data-omo-project-title="' + escapeDocumentEmbedHtml(title) + '" data-omo-project-status="' + statusClass + '" data-omo-project-status-label="' + escapeDocumentEmbedHtml(statusLabel) + '"><strong><a href="' + projectHash + '">' + escapeDocumentEmbedHtml(title) + '</a><a class="omo-project-embed__external" href="' + escapeDocumentEmbedHtml(externalUrl) + '" target="_blank" rel="noopener noreferrer" title="' + escapeDocumentEmbedHtml(projectEmbedUi.openExternal || '') + '" aria-label="' + escapeDocumentEmbedHtml(projectEmbedUi.openExternal || '') + '">&#8599;</a>' + priorityCapsule + (sizeLabel !== '' ? '<em>' + escapeDocumentEmbedHtml(sizeLabel) + '</em>' : '') + '</strong>' + (metadata.length > 0 ? '<em>' + escapeDocumentEmbedHtml(metadata.join(' · ')) + '</em>' : '') + '</span>';
    }

    function getPvProjectCreationContext(field, pointCard) {
        if (!field || typeof field.getSelectedText !== 'function' || !(pointCard instanceof Element)) {
            return {};
        }

        const title = String(field.getSelectedText() || '').trim();
        const pointId = Number(pointCard.getAttribute('data-omo-pv-point-card') || 0);
        const authorField = pointId > 0
            ? pointCard.querySelector('[data-omo-pv-point-author="' + pointId + '"]')
            : null;
        const concernedHolonField = pointId > 0
            ? pointCard.querySelector('[data-omo-pv-point-concerned-holon="' + pointId + '"]')
            : null;
        const priorityField = pointId > 0
            ? pointCard.querySelector('[data-omo-pv-point-priority="' + pointId + '"]')
            : null;
        const pointPayload = currentPointPayloads[String(pointId)] || {};
        const authorValue = authorField instanceof HTMLSelectElement
            ? String(authorField.value || '')
            : (Number(pointPayload.authorUserId || 0) > 0 ? 'user:' + String(pointPayload.authorUserId) : '');
        const authorMatch = /^user:(\d+)$/.exec(authorValue);
        const authorOption = authorField instanceof HTMLSelectElement ? authorField.selectedOptions[0] : null;
        const holonOption = concernedHolonField instanceof HTMLSelectElement ? concernedHolonField.selectedOptions[0] : null;

        return {
            title: title,
            holonId: concernedHolonField instanceof HTMLSelectElement
                ? Number(concernedHolonField.value || 0)
                : Number(pointPayload.concernedHolonId || 0),
            holonLabel: holonOption
                ? String(holonOption.textContent || '').trim()
                : String(pointPayload.concernedHolonLabel || '').trim(),
            responsibleId: authorMatch ? Number(authorMatch[1] || 0) : 0,
            responsibleLabel: authorOption
                ? String(authorOption.textContent || '').trim()
                : String(pointPayload.authorLabel || '').trim(),
            priority: priorityField instanceof HTMLInputElement
                ? Number(priorityField.value || 0)
                : Number(pointPayload.priority || 0),
        };
    }

    function openPvProjectEmbedPicker(field, targetNode, creationContext = {}) {
        if (!field || typeof field.createTemporaryCursorMarker !== 'function' || typeof window.commonTopbarOpenModal !== 'function') return;
        const currentProjectId = targetNode instanceof Element ? Number.parseInt(String(targetNode.getAttribute('data-omo-project-id') || ''), 10) : 0;
        const creationTitle = targetNode ? '' : String(creationContext.title || '').trim();
        const startsInCreationMode = creationTitle !== '';
        const projectCreateContext = Object.assign({}, projectEmbedCreateContext);
        if (Number(creationContext.holonId || 0) > 0) {
            projectCreateContext.holonId = Number(creationContext.holonId);
            projectCreateContext.holonLabel = String(creationContext.holonLabel || '').trim();
        }
        if (Number(creationContext.responsibleId || 0) > 0) {
            projectCreateContext.responsibleId = Number(creationContext.responsibleId);
            projectCreateContext.responsibleLabel = String(creationContext.responsibleLabel || '').trim();
        }
        if (Number(creationContext.priority || 0) >= 1 && Number(creationContext.priority || 0) <= 5) {
            projectCreateContext.priority = Number(creationContext.priority);
        }
        let marker = targetNode ? null : field.createTemporaryCursorMarker({preserveSelection: startsInCreationMode}), resolved = false;
        const tabPrefix = 'omo-pv-project-picker-' + Math.random().toString(36).slice(2, 10), existingTabId = tabPrefix + '-existing', newTabId = tabPrefix + '-new';
        const statusOptions = Array.isArray(projectEmbedUi.statusOptions) ? projectEmbedUi.statusOptions : [], sizeOptions = Array.isArray(projectEmbedUi.sizeOptions) ? projectEmbedUi.sizeOptions : [];
        const defaultStatus = statusOptions.some(function (option) { return String(option.value || '') === 'in_progress'; })
            ? 'in_progress'
            : String(statusOptions[0] && statusOptions[0].value || '');
        const rawPriorityValue = Number(projectCreateContext.priority || 0);
        const priorityValue = rawPriorityValue >= 1 && rawPriorityValue <= 5 ? rawPriorityValue : 0;
        const priorityOptionsHtml = [1, 2, 3, 4, 5].map(function (level) {
            return '<option value="' + level + '"' + (level === priorityValue ? ' selected' : '') + '>P' + level + '</option>';
        }).join('');
        const today = new Date();
        const todayIso = [today.getFullYear(), String(today.getMonth() + 1).padStart(2, '0'), String(today.getDate()).padStart(2, '0')].join('-');
        const statusOptionsHtml = statusOptions.map(function (option) {
            const value = String(option.value || '');
            return '<option value="' + escapeDocumentEmbedHtml(value) + '"' + (value === defaultStatus ? ' selected' : '') + '>' + escapeDocumentEmbedHtml(option.label || value) + '</option>';
        }).join('');
        const sizeOptionsHtml = sizeOptions.map(function (size) { return '<option value="' + escapeDocumentEmbedHtml(size) + '"' + (String(size) === 'M' ? ' selected' : '') + '>' + escapeDocumentEmbedHtml(size) + '</option>'; }).join('');
        const html = '<div class="generic-tabs omo-document-embed-picker" data-generic-tabs><div class="generic-tabs__list" aria-label="' + escapeDocumentEmbedHtml(projectEmbedUi.tabsAria || '') + '"><button type="button" class="generic-tabs__tab' + (startsInCreationMode ? '' : ' is-active') + '" data-generic-tab data-generic-tab-target="' + existingTabId + '">' + escapeDocumentEmbedHtml(projectEmbedUi.tabExisting || '') + '</button><button type="button" class="generic-tabs__tab' + (startsInCreationMode ? ' is-active' : '') + '" data-generic-tab data-generic-tab-target="' + newTabId + '">' + escapeDocumentEmbedHtml(projectEmbedUi.tabNew || '') + '</button></div><div class="generic-tabs__panels"><section id="' + existingTabId + '" class="generic-tabs__panel" data-generic-tab-panel' + (startsInCreationMode ? ' hidden' : '') + '><div class="omo-resource-picker"><aside class="omo-resource-picker__navigation" data-omo-pv-project-embed-scope></aside><div class="omo-resource-picker__content"><div class="omo-segmented" role="group" aria-label="' + escapeDocumentEmbedHtml(projectEmbedUi.assignmentAria || '') + '"><button type="button" class="omo-segmented__button" data-omo-pv-project-embed-assignment="mine" aria-pressed="false">' + escapeDocumentEmbedHtml(projectEmbedUi.assignmentMine || '') + '</button><button type="button" class="omo-segmented__button" data-omo-pv-project-embed-assignment="followed" aria-pressed="false">' + escapeDocumentEmbedHtml(projectEmbedUi.assignmentFollowed || '') + '</button><button type="button" class="omo-segmented__button is-active" data-omo-pv-project-embed-assignment="all" aria-pressed="true">' + escapeDocumentEmbedHtml(projectEmbedUi.assignmentEveryone || '') + '</button></div><label class="omo-resource-picker__quick-search"><input type="search" class="generic-form-control" data-omo-pv-project-embed-search placeholder="' + escapeDocumentEmbedHtml(projectEmbedUi.quickSearchPlaceholder || '') + '"></label><div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select" data-omo-pv-project-embed-select size="10"></select></div><div class="omo-document-embed-picker__preview" data-omo-pv-project-embed-preview></div><div class="omo-document-embed-picker__actions">' + (targetNode ? '<button type="button" class="generic-action-button generic-action-button--danger" data-omo-pv-embed-remove>' + escapeDocumentEmbedHtml(projectEmbedUi.remove || '') + '</button>' : '') + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-project-embed-cancel>' + escapeDocumentEmbedHtml(projectEmbedUi.cancel || '') + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-pv-project-embed-insert disabled>' + escapeDocumentEmbedHtml(projectEmbedUi.insert || '') + '</button></div></div></div></section><section id="' + newTabId + '" class="generic-tabs__panel" data-generic-tab-panel' + (startsInCreationMode ? '' : ' hidden') + '><form data-omo-pv-project-create-form class="omo-document-embed-picker__quick-form"><label>' + escapeDocumentEmbedHtml(projectEmbedUi.titleLabel || '') + '<input required name="title" class="generic-form-control" type="text" value="' + escapeDocumentEmbedHtml(creationTitle) + '"></label><label>' + escapeDocumentEmbedHtml(projectEmbedUi.descriptionLabel || '') + '<textarea name="description" class="generic-form-control" rows="3"></textarea></label><div class="omo-document-embed-picker__quick-form-grid"><label>' + escapeDocumentEmbedHtml(projectEmbedUi.statusLabel || '') + '<select name="status" class="generic-form-control">' + statusOptionsHtml + '</select></label><label>' + escapeDocumentEmbedHtml(projectEmbedUi.priorityLabel || '') + '<select name="priority" class="generic-form-control"><option value=""></option>' + priorityOptionsHtml + '</select></label><label>' + escapeDocumentEmbedHtml(projectEmbedUi.sizeLabel || '') + '<select name="project_size" class="generic-form-control">' + sizeOptionsHtml + '</select></label><label>' + escapeDocumentEmbedHtml(projectEmbedUi.startDateLabel || '') + '<input name="planned_start_date" class="generic-form-control" type="date" value="' + todayIso + '"></label><label>' + escapeDocumentEmbedHtml(projectEmbedUi.endDateLabel || '') + '<input name="planned_end_date" class="generic-form-control" type="date"></label></div><div class="omo-document-embed-picker__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-project-embed-cancel>' + escapeDocumentEmbedHtml(projectEmbedUi.cancel || '') + '</button><button type="submit" class="generic-action-button generic-action-button--main" data-omo-pv-project-create-submit>' + escapeDocumentEmbedHtml(projectEmbedUi.createInsert || '') + '</button></div></form></section></div></div>';
        window.commonTopbarOpenModal(projectEmbedUi.modalTitle || '', html, 'html');
        const body = document.getElementById('commonTopbarModalBody'); if (!(body instanceof Element)) { if (marker) field.removeTemporaryMarker(marker); return; }
        const projectPickerTabs = body.querySelector('.generic-tabs.omo-document-embed-picker');
        if (projectPickerTabs) projectPickerTabs.classList.add('generic-drawer-content');
        const projectScopeHost = body.querySelector('[data-omo-pv-project-embed-scope]');
        const projectScopeNavigation = projectScopeHost ? projectScopeHost.closest('.omo-resource-picker__navigation') : null;
        if (projectPickerTabs instanceof Element && projectScopeNavigation instanceof Element) {
            const resourcePicker = projectScopeNavigation.closest('.omo-resource-picker');
            if (resourcePicker instanceof Element) resourcePicker.classList.remove('omo-resource-picker');
            projectPickerTabs.classList.add('omo-project-embed-picker');
            projectPickerTabs.appendChild(projectScopeNavigation);
        }
        if (typeof window.initGenericComponents === 'function') window.initGenericComponents(body);
        const search = body.querySelector('[data-omo-pv-project-embed-search]'), select = body.querySelector('[data-omo-pv-project-embed-select]'), preview = body.querySelector('[data-omo-pv-project-embed-preview]'), cancelButtons = Array.from(body.querySelectorAll('[data-omo-pv-project-embed-cancel]')), insert = body.querySelector('[data-omo-pv-project-embed-insert]'), remove = body.querySelector('[data-omo-pv-embed-remove]'), createForm = body.querySelector('[data-omo-pv-project-create-form]'), createSubmit = body.querySelector('[data-omo-pv-project-create-submit]'); let selected = null, projectPickerController = null;
        if (createForm instanceof HTMLFormElement) {
            if (Number(projectCreateContext.pvMeetingDocumentId || 0) > 0 && String(projectCreateContext.pvMeetingEditorToken || '') !== '') {
                const meetingDocumentInput = document.createElement('input');
                meetingDocumentInput.type = 'hidden';
                meetingDocumentInput.name = 'pv_meeting_document_id';
                meetingDocumentInput.value = String(projectCreateContext.pvMeetingDocumentId);
                createForm.appendChild(meetingDocumentInput);
                const meetingTokenInput = document.createElement('input');
                meetingTokenInput.type = 'hidden';
                meetingTokenInput.name = 'pv_meeting_editor_token';
                meetingTokenInput.value = String(projectCreateContext.pvMeetingEditorToken);
                createForm.appendChild(meetingTokenInput);
            }
            const enforceHolonMember = document.createElement('input');
            enforceHolonMember.type = 'hidden';
            enforceHolonMember.name = 'enforce_holon_member';
            enforceHolonMember.value = '1';
            createForm.appendChild(enforceHolonMember);
            const responsibleField = document.createElement('label');
            responsibleField.textContent = String(projectEmbedUi.responsibleLabel || '');
            const responsibleSelect = document.createElement('select');
            responsibleSelect.name = 'IDuser';
            responsibleSelect.className = 'generic-form-control';
            responsibleSelect.disabled = true;
            const responsibleNote = document.createElement('small');
            responsibleField.appendChild(responsibleSelect);
            responsibleField.appendChild(responsibleNote);
            createForm.insertBefore(responsibleField, createForm.firstChild);
            let membersRequestId = 0;
            const loadMembers = function (holonId) {
                const requestId = ++membersRequestId;
                responsibleSelect.disabled = true;
                responsibleSelect.innerHTML = '<option value="">' + escapeDocumentEmbedHtml(projectEmbedUi.membersLoading || '') + '</option>';
                responsibleNote.textContent = '';
                fetch('/omo/api/projects/members.php?oid=' + encodeURIComponent(String(projectCreateContext.organizationId || 0)) + '&hid=' + encodeURIComponent(String(holonId || 0)), {credentials: 'same-origin'})
                    .then(function (response) { return response.json(); })
                    .then(function (payload) {
                        if (requestId !== membersRequestId || !payload || !payload.success) return;
                        projectCreateContext.holonId = Number(payload.holon && payload.holon.id || 0);
                        projectCreateContext.holonLabel = String(payload.holon && payload.holon.label || '');
                        const members = Array.isArray(payload.members) ? payload.members : [];
                        responsibleSelect.innerHTML = '<option value="">' + escapeDocumentEmbedHtml(projectEmbedUi.responsibleEmpty || '') + '</option>';
                        members.forEach(function (member) {
                            const option = document.createElement('option');
                            option.value = String(member.id || '');
                            option.textContent = String(member.label || '');
                            if (Number(member.id || 0) === Number(projectCreateContext.responsibleId || 0)) option.selected = true;
                            responsibleSelect.appendChild(option);
                        });
                        if (members.length === 0) responsibleNote.textContent = String(projectEmbedUi.membersEmpty || '');
                        responsibleSelect.disabled = false;
                        const selectedOption = responsibleSelect.selectedOptions[0];
                        projectCreateContext.responsibleId = Number(responsibleSelect.value || 0);
                        projectCreateContext.responsibleLabel = selectedOption ? String(selectedOption.textContent || '') : '';
                    })
                    .catch(function () {
                        if (requestId !== membersRequestId) return;
                        responsibleSelect.innerHTML = '<option value="">' + escapeDocumentEmbedHtml(projectEmbedUi.responsibleEmpty || '') + '</option>';
                        responsibleSelect.disabled = false;
                    });
            };
            responsibleSelect.addEventListener('change', function () {
                const selectedOption = responsibleSelect.selectedOptions[0];
                projectCreateContext.responsibleId = Number(responsibleSelect.value || 0);
                projectCreateContext.responsibleLabel = selectedOption ? String(selectedOption.textContent || '') : '';
            });
            createForm.__omoPvProjectLoadMembers = loadMembers;
        }
        const cleanup = function () { if (marker) field.removeTemporaryMarker(marker); marker = null; };
        const insertProject = function (project) { const embed = buildPvProjectEmbedHtml(project); if (!embed) return false; resolved = insertPvEmbedIntoField(field, targetNode, marker, embed); if (!resolved) return false; marker = null; window.setTimeout(function () { refreshPvProjectEmbedReviews(field); }, 0); window.commonTopbarCloseModal(); return true; };
        const update = function (projects) { selected = Array.isArray(projects) ? (projects[0] || null) : selected; if (preview) preview.innerHTML = selected ? buildPvProjectEmbedHtml(selected) : escapeDocumentEmbedHtml(projectEmbedUi.none || ''); if (insert) insert.disabled = !selected; };
        if (typeof window.commonMountProjectPicker === 'function') {
            projectPickerController = window.commonMountProjectPicker({
                root: body,
                scopeHost: projectScopeHost,
                searchInput: search,
                selectElement: select,
                assignmentButtons: body.querySelectorAll('[data-omo-pv-project-embed-assignment]'),
                assignmentAttribute: 'data-omo-pv-project-embed-assignment',
                projects: embeddableProjects,
                organizationId: resourcePickerOrganizationId,
                initialHolonId: Number(projectCreateContext.holonId || resourcePickerInitialHolonId || 0),
                initialScope: 'local',
                scopeLabels: resourcePickerScopeUi,
                selectedIds: currentProjectId > 0 ? [currentProjectId] : [],
                getHolonId: function (item) { return Number(item.contextHolonId || 0); },
                getSearchText: function (item) { return [item.title, item.contextLabel, item.summary].join(' '); },
                getOptionLabel: function (item) { return String(item.title || '').trim() || String(projectEmbedUi.fallbackTitle || '').replace('{id}', String(item.id)); },
                matchesAssignment: function (item, assignment) { return assignment === 'mine' ? Boolean(item.isMine) : (assignment === 'followed' ? Boolean(item.isFollowed) : true); },
                onChange: update,
                onHolonChange: function (holonId) { if (createForm && typeof createForm.__omoPvProjectLoadMembers === 'function') createForm.__omoPvProjectLoadMembers(holonId); }
            });
        }
        window.addEventListener('common-topbar-modal-close', function () { if (!resolved) cleanup(); if (projectPickerController && typeof projectPickerController.destroy === 'function') projectPickerController.destroy(); projectPickerController = null; }, {once: true}); cancelButtons.forEach(function (button) { button.addEventListener('click', function () { cleanup(); window.commonTopbarCloseModal(); }); }); if (remove) remove.addEventListener('click', function () { if (targetNode && typeof field.removeNode === 'function') resolved = field.removeNode(targetNode); window.commonTopbarCloseModal(); }); if (insert) insert.addEventListener('click', function () { if (selected) insertProject(selected); });
        if (createForm instanceof HTMLFormElement) createForm.addEventListener('submit', function (event) { event.preventDefault(); const titleInput = createForm.elements.namedItem('title'), title = titleInput ? String(titleInput.value || '').trim() : ''; if (title === '') { if (titleInput && typeof titleInput.focus === 'function') titleInput.focus(); return; } if (createSubmit) createSubmit.disabled = true; const formData = new FormData(createForm); formData.set('project_action', 'save_project'); formData.set('oid', String(projectCreateContext.organizationId || 0)); formData.set('cid', String(projectCreateContext.holonId || 0)); formData.set('IDuser', String(projectCreateContext.responsibleId || 0)); fetch('/omo/api/projects/action.php', {method: 'POST', body: formData, credentials: 'same-origin'}).then(function (response) { return response.json(); }).then(function (payload) { const projectId = Number(payload && payload.id || 0); if (!payload || !payload.success || projectId <= 0) throw new Error(payload && payload.message ? payload.message : projectEmbedUi.createError || ''); const statusSelect = createForm.elements.namedItem('status'), sizeSelect = createForm.elements.namedItem('project_size'), prioritySelect = createForm.elements.namedItem('priority'), startInput = createForm.elements.namedItem('planned_start_date'), endInput = createForm.elements.namedItem('planned_end_date'), statusValue = statusSelect ? String(statusSelect.value || '') : '', statusOption = statusOptions.find(function (option) { return String(option.value) === statusValue; }) || {}, toDateLabel = function (input) { const value = input ? String(input.value || '') : ''; return /^\d{4}-\d{2}-\d{2}$/.test(value) ? value.slice(8, 10) + '.' + value.slice(5, 7) + '.' + value.slice(0, 4) : ''; }, project = {id: projectId, title: title, summary: String(formData.get('description') || ''), contextHolonId: Number(projectCreateContext.holonId || 0), contextLabel: String(projectCreateContext.holonLabel || ''), isMine: true, isFollowed: false, responsibleLabel: String(projectCreateContext.responsibleLabel || ''), status: statusValue, statusLabel: String(statusOption.label || statusValue), priorityLabel: prioritySelect && prioritySelect.value ? 'P' + String(prioritySelect.value) : '', sizeLabel: sizeSelect ? String(sizeSelect.value || 'M') : 'M', plannedStartLabel: toDateLabel(startInput), plannedEndLabel: toDateLabel(endInput)}; embeddableProjects.push(project); if (typeof window.omoRefreshProjectsDrawerAfterMutation === 'function') window.omoRefreshProjectsDrawerAfterMutation(); insertProject(project); }).catch(function (error) { window.alert(String(error && error.message || projectEmbedUi.createError || '')); }).finally(function () { if (createSubmit) createSubmit.disabled = false; }); });
        render();
    }

    function buildPvEventEmbedHtml(eventItem) {
        const eventId = Number.parseInt(String(eventItem && eventItem.id || ''), 10);
        if (!Number.isInteger(eventId) || eventId <= 0) {
            return '';
        }

        const title = String(eventItem.title || '').trim() || String(eventEmbedUi.fallbackTitle || '').replace('{id}', String(eventId));
        const scheduleLabel = String(eventItem.scheduleLabel || '').trim();
        const locationLabel = truncateDocumentEmbedSummary(eventItem.locationLabel || '', 420, 1);
        const summary = [scheduleLabel, locationLabel].filter(function (value) { return value !== ''; }).join(' - ');
        return '<span class="omo-event-embed" contenteditable="false" data-omo-embed-type="event"'
            + ' data-omo-event-id="' + String(eventId) + '"'
            + ' data-omo-event-title="' + escapeDocumentEmbedHtml(title) + '"'
            + (scheduleLabel !== '' ? ' data-omo-event-schedule="' + escapeDocumentEmbedHtml(scheduleLabel) + '"' : '')
            + (locationLabel !== '' ? ' data-omo-event-location="' + escapeDocumentEmbedHtml(locationLabel) + '"' : '')
            + '><strong><a class="omo-event-embed__title" href="#calendar-e' + String(eventId) + '">' + escapeDocumentEmbedHtml(title) + '</a></strong>'
            + (summary !== '' ? '<em>' + escapeDocumentEmbedHtml(summary) + '</em>' : '')
            + '</span>';
    }

    function openPvEventEmbedPicker(field, targetNode) {
        if (!canEmbedEvents || !field || typeof field.createTemporaryCursorMarker !== 'function' || typeof field.replaceMarkerWithHtml !== 'function' || typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }

        const currentEventId = targetNode instanceof Element
            ? Number.parseInt(String(targetNode.getAttribute('data-omo-event-id') || ''), 10)
            : 0;
        let marker = targetNode ? null : field.createTemporaryCursorMarker();
        let resolved = false;
        const tabPrefix = 'omo-pv-event-picker-' + Math.random().toString(36).slice(2, 10);
        const existingTabId = tabPrefix + '-existing';
        const newTabId = tabPrefix + '-new';
        const modalHtml = '<div class="generic-tabs omo-document-embed-picker generic-drawer-content" data-generic-tabs>'
            + '<div class="generic-tabs__list" aria-label="' + escapeDocumentEmbedHtml(eventEmbedUi.tabsAria || '') + '">'
            + '<button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="' + existingTabId + '">' + escapeDocumentEmbedHtml(eventEmbedUi.tabExisting || '') + '</button>'
            + '<button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="' + newTabId + '">' + escapeDocumentEmbedHtml(eventEmbedUi.tabNew || '') + '</button></div>'
            + '<div class="generic-tabs__panels">'
            + '<section id="' + existingTabId + '" class="generic-tabs__panel" data-generic-tab-panel><div class="omo-resource-picker">'
            + '<aside class="omo-resource-picker__navigation" data-omo-pv-event-embed-scope></aside>'
            + '<div class="omo-resource-picker__content">'
            + '<label class="omo-resource-picker__quick-search"><img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true"><input type="search" class="generic-form-control" data-omo-pv-event-embed-search aria-label="' + escapeDocumentEmbedHtml(eventEmbedUi.search || '') + '" placeholder="' + escapeDocumentEmbedHtml(eventEmbedUi.quickSearchPlaceholder || '') + '"></label>'
            + '<div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select" data-omo-pv-event-embed-select aria-label="' + escapeDocumentEmbedHtml(eventEmbedUi.visibleEvents || '') + '" size="10"></select></div>'
            + '<div class="omo-document-embed-picker__preview"><div class="omo-document-embed-picker__preview-title" data-omo-pv-event-embed-title></div><div class="omo-document-embed-picker__preview-context" data-omo-pv-event-embed-schedule hidden></div><div class="omo-document-embed-picker__preview-description" data-omo-pv-event-embed-description hidden></div></div>'
            + '<div class="omo-document-embed-picker__actions">'
            + (targetNode ? '<button type="button" class="generic-action-button generic-action-button--danger" data-omo-pv-embed-remove>' + escapeDocumentEmbedHtml(eventEmbedUi.remove || '') + '</button>' : '')
            + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-event-embed-cancel>' + escapeDocumentEmbedHtml(eventEmbedUi.cancel || '') + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-pv-event-embed-insert disabled>' + escapeDocumentEmbedHtml(eventEmbedUi.insert || '') + '</button></div></div></div></section>'
            + '<section id="' + newTabId + '" class="generic-tabs__panel" data-generic-tab-panel hidden><form data-omo-pv-event-create-form class="omo-document-embed-picker__quick-form">'
            + '<label>' + escapeDocumentEmbedHtml(eventEmbedUi.titleLabel || '') + '<input required name="title" class="generic-form-control" type="text"></label>'
            + '<label>' + escapeDocumentEmbedHtml(eventEmbedUi.descriptionLabel || '') + '<textarea name="description" class="generic-form-control" rows="3"></textarea></label>'
            + '<div class="omo-document-embed-picker__quick-form-grid"><label>' + escapeDocumentEmbedHtml(eventEmbedUi.startAtLabel || '') + '<input required name="start_at" class="generic-form-control" type="datetime-local"></label><label>' + escapeDocumentEmbedHtml(eventEmbedUi.endAtLabel || '') + '<input required name="end_at" class="generic-form-control" type="datetime-local"></label></div>'
            + '<div class="omo-document-embed-picker__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-event-embed-cancel>' + escapeDocumentEmbedHtml(eventEmbedUi.cancel || '') + '</button><button type="submit" class="generic-action-button generic-action-button--main" data-omo-pv-event-create-submit>' + escapeDocumentEmbedHtml(eventEmbedUi.createInsert || '') + '</button></div></form></section>'
            + '</div></div>';

        window.commonTopbarOpenModal(eventEmbedUi.modalTitle || '', modalHtml, 'html');
        const modalBody = document.getElementById('commonTopbarModalBody');
        if (!(modalBody instanceof Element)) {
            if (marker) field.removeTemporaryMarker(marker);
            return;
        }

        const eventPickerTabs = modalBody.querySelector('.generic-tabs.omo-document-embed-picker');
        const eventScopeHost = modalBody.querySelector('[data-omo-pv-event-embed-scope]');
        const eventScopeNavigation = eventScopeHost ? eventScopeHost.closest('.omo-resource-picker__navigation') : null;
        if (eventPickerTabs instanceof Element && eventScopeNavigation instanceof Element) {
            const resourcePicker = eventScopeNavigation.closest('.omo-resource-picker');
            if (resourcePicker instanceof Element) resourcePicker.classList.remove('omo-resource-picker');
            eventPickerTabs.classList.add('omo-event-embed-picker');
            eventPickerTabs.appendChild(eventScopeNavigation);
        }
        if (typeof window.initGenericComponents === 'function') window.initGenericComponents(modalBody);

        const searchNode = modalBody.querySelector('[data-omo-pv-event-embed-search]');
        const selectNode = modalBody.querySelector('[data-omo-pv-event-embed-select]');
        const titleNode = modalBody.querySelector('[data-omo-pv-event-embed-title]');
        const scheduleNode = modalBody.querySelector('[data-omo-pv-event-embed-schedule]');
        const descriptionNode = modalBody.querySelector('[data-omo-pv-event-embed-description]');
        const cancelButtons = Array.from(modalBody.querySelectorAll('[data-omo-pv-event-embed-cancel]'));
        const insertButton = modalBody.querySelector('[data-omo-pv-event-embed-insert]');
        const removeButton = modalBody.querySelector('[data-omo-pv-embed-remove]');
        const createForm = modalBody.querySelector('[data-omo-pv-event-create-form]');
        const createSubmit = modalBody.querySelector('[data-omo-pv-event-create-submit]');
        let selectedItem = null;
        let scopePicker = null;
        const cleanup = function () { if (marker) field.removeTemporaryMarker(marker); marker = null; };
        const insertEvent = function (eventItem) {
            const embedHtml = buildPvEventEmbedHtml(eventItem);
            if (embedHtml === '') return false;
            resolved = insertPvEmbedIntoField(field, targetNode, marker, embedHtml);
            if (!resolved) {
                return false;
            }
            marker = null;
            window.commonTopbarCloseModal();
            return true;
        };
        const updatePreview = function () {
            if (selectNode && selectNode.value !== '') selectedItem = embeddableEvents.find(function (item) { return String(item.id) === String(selectNode.value); }) || null;
            if (titleNode) titleNode.textContent = selectedItem ? (String(selectedItem.title || '').trim() || String(eventEmbedUi.fallbackTitle || '').replace('{id}', String(selectedItem.id))) : String(eventEmbedUi.none || '');
            if (scheduleNode) { scheduleNode.textContent = selectedItem ? String(selectedItem.scheduleLabel || '') : ''; scheduleNode.hidden = scheduleNode.textContent === ''; }
            if (descriptionNode) { descriptionNode.textContent = selectedItem ? String(selectedItem.locationLabel || '') : ''; descriptionNode.hidden = descriptionNode.textContent === ''; }
            if (insertButton) insertButton.disabled = !selectedItem;
        };
        const render = function () {
            const query = String(searchNode && searchNode.value || '').trim().toLowerCase();
            const matches = embeddableEvents.filter(function (item) { return (!scopePicker || scopePicker.matches(item.contextHolonId)) && (query === '' || [item.title, item.scheduleLabel, item.locationLabel].join(' ').toLowerCase().indexOf(query) >= 0); });
            if (selectNode) {
                selectNode.innerHTML = '';
                matches.forEach(function (item) { const option = document.createElement('option'); option.value = String(item.id); option.textContent = (String(item.title || '').trim() || String(eventEmbedUi.fallbackTitle || '').replace('{id}', String(item.id))) + (item.scheduleLabel ? ' - ' + String(item.scheduleLabel) : ''); selectNode.appendChild(option); });
                selectNode.disabled = matches.length === 0;
            }
            selectedItem = matches.find(function (item) { return Number(item.id) === currentEventId; }) || matches[0] || null;
            if (selectNode && selectedItem) selectNode.value = String(selectedItem.id);
            updatePreview();
        };
        if (createForm instanceof HTMLFormElement) {
            const startInput = createForm.elements.namedItem('start_at');
            const endInput = createForm.elements.namedItem('end_at');
            const shiftDateTimeLocalValue = function (value, hours) {
                const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/);
                if (!match) return '';
                const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), Number(match[4]), Number(match[5]));
                if (Number.isNaN(date.getTime())) return '';
                date.setHours(date.getHours() + Number(hours || 0));
                const pad = function (number) { return String(number).padStart(2, '0'); };
                return String(date.getFullYear()) + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
            };
            const validateEventEnd = function () {
                if (!(startInput instanceof HTMLInputElement) || !(endInput instanceof HTMLInputElement)) return;
                const isBeforeStart = startInput.value !== '' && endInput.value !== '' && endInput.value < startInput.value;
                endInput.setCustomValidity(isBeforeStart ? String(eventEmbedUi.endAfterStart || '') : '');
            };
            const syncEventEndWithStart = function () {
                if (!(startInput instanceof HTMLInputElement) || !(endInput instanceof HTMLInputElement)) return;
                const startValue = String(startInput.value || '');
                if (startValue === '') {
                    endInput.removeAttribute('min');
                    validateEventEnd();
                    return;
                }

                endInput.min = startValue;
                const shouldSetAutomaticEnd = endInput.value === ''
                    || endInput.dataset.omoEventAutoEnd === '1'
                    || endInput.value < startValue;
                if (shouldSetAutomaticEnd) {
                    const automaticEnd = shiftDateTimeLocalValue(startValue, 1);
                    if (automaticEnd !== '') {
                        endInput.value = automaticEnd;
                        endInput.dataset.omoEventAutoEnd = '1';
                    }
                }
                validateEventEnd();
            };
            const syncEventStartWithEnd = function () {
                if (!(startInput instanceof HTMLInputElement) || !(endInput instanceof HTMLInputElement)) return;
                const endValue = String(endInput.value || '');
                if (endValue === '') {
                    startInput.removeAttribute('max');
                    validateEventEnd();
                    return;
                }

                startInput.max = endValue;
                const shouldSetAutomaticStart = startInput.value === ''
                    || startInput.dataset.omoEventAutoStart === '1'
                    || startInput.value > endValue;
                if (shouldSetAutomaticStart) {
                    const automaticStart = shiftDateTimeLocalValue(endValue, -1);
                    if (automaticStart !== '') {
                        startInput.value = automaticStart;
                        startInput.dataset.omoEventAutoStart = '1';
                    }
                }
                syncEventEndWithStart();
            };
            if (startInput instanceof HTMLInputElement) {
                startInput.addEventListener('input', function () { startInput.dataset.omoEventAutoStart = '0'; });
                startInput.addEventListener('change', syncEventEndWithStart);
            }
            if (endInput instanceof HTMLInputElement) {
                endInput.addEventListener('input', function () {
                    endInput.dataset.omoEventAutoEnd = '0';
                    syncEventStartWithEnd();
                });
                endInput.addEventListener('change', syncEventStartWithEnd);
            }
            syncEventEndWithStart();
        }
        if (eventScopeHost instanceof Element && typeof window.omoMountHolonScopePicker === 'function') {
            scopePicker = window.omoMountHolonScopePicker({
                host: eventScopeHost,
                organizationId: resourcePickerOrganizationId,
                initialHolonId: Number(eventEmbedCreateContext.holonId || resourcePickerInitialHolonId || 0),
                initialScope: 'local',
                labels: resourcePickerScopeUi,
                onChange: function (holonId) {
                    eventEmbedCreateContext.holonId = Number(holonId || 0);
                    render();
                }
            });
        } else {
            scopePicker = mountPvResourceScopePicker(modalBody, '[data-omo-pv-event-embed-scope]', render);
        }
        window.addEventListener('common-topbar-modal-close', function () { if (!resolved) cleanup(); }, {once: true});
        if (searchNode) { searchNode.addEventListener('input', render); searchNode.focus(); }
        if (selectNode) selectNode.addEventListener('change', updatePreview);
        cancelButtons.forEach(function (button) { button.addEventListener('click', function () { cleanup(); window.commonTopbarCloseModal(); }); });
        if (removeButton) removeButton.addEventListener('click', function () { if (targetNode && typeof field.removeNode === 'function') resolved = field.removeNode(targetNode); window.commonTopbarCloseModal(); });
        if (insertButton) insertButton.addEventListener('click', function () { if (selectedItem) insertEvent(selectedItem); });
        if (createForm instanceof HTMLFormElement) createForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const titleInput = createForm.elements.namedItem('title');
            const title = titleInput ? String(titleInput.value || '').trim() : '';
            if (title === '') {
                if (titleInput && typeof titleInput.focus === 'function') titleInput.focus();
                return;
            }
            if (createSubmit) createSubmit.disabled = true;
            const formData = new FormData(createForm);
            formData.set('action', 'create_event');
            formData.set('document_id', String(documentId));
            formData.set('oid', String(eventEmbedCreateContext.organizationId || 0));
            formData.set('editor_token', editorToken);
            formData.set('IDholon', String(eventEmbedCreateContext.holonId || 0));
            fetch(actionUrl, {method: 'POST', body: formData, credentials: 'same-origin'})
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    const newEvent = payload && payload.event && typeof payload.event === 'object' ? payload.event : null;
                    if (!payload || !payload.status || !newEvent || Number(newEvent.id || 0) <= 0) {
                        throw new Error(payload && payload.message ? payload.message : eventEmbedUi.createError || '');
                    }
                    embeddableEvents.push(newEvent);
                    embeddableEvents.sort(function (left, right) { return String(left.startAt || '').localeCompare(String(right.startAt || '')); });
                    insertEvent(newEvent);
                })
                .catch(function (error) { window.alert(String(error && error.message || eventEmbedUi.createError || '')); })
                .finally(function () { if (createSubmit) createSubmit.disabled = false; });
        });
        render();
    }

    function buildPvIndicatorEmbedHtml(indicatorItem) {
        const indicatorId = Number.parseInt(String(indicatorItem && indicatorItem.id || ''), 10);
        if (!Number.isInteger(indicatorId) || indicatorId <= 0) {
            return '';
        }

        const title = String(indicatorItem.title || '').trim() || String(indicatorEmbedUi.fallbackTitle || '').replace('{id}', String(indicatorId));
        const description = String(indicatorItem.description || '').trim();
        const indicatorKind = String(indicatorItem && indicatorItem.kind || '').trim() === 'group' ? 'group' : 'indicator';
        const routeHash = indicatorKind === 'group' ? ('stats-g' + String(indicatorId)) : ('stats-i' + String(indicatorId));
        const valueLabel = String(indicatorItem.valueLabel || '').trim();
        const dateLabel = String(indicatorItem.dateLabel || '').trim();
        const statusLabel = String(indicatorItem.statusLabel || '').trim();
        const contextLabel = String(indicatorItem.contextLabel || '').trim();
        const chartMinLabel = String(indicatorItem.chartMinLabel || '').trim();
        const chartMaxLabel = String(indicatorItem.chartMaxLabel || '').trim();
        const chartHtml = String(indicatorItem.chartHtml || '').trim();
        const overdueSeverity = indicatorItem && indicatorItem.overdueSeverity === 'warning' ? 'warning' : 'error';
        const statusClass = indicatorItem && indicatorItem.isOverdue
            ? (overdueSeverity === 'warning' ? ' omo-indicator-embed--warning' : ' omo-indicator-embed--overdue')
            : (statusLabel !== '' ? ' omo-indicator-embed--current' : '');
        const statusDotClass = indicatorItem && indicatorItem.isOverdue
            ? (overdueSeverity === 'warning' ? ' omo-indicator-embed__status-dot--warning' : ' omo-indicator-embed__status-dot--overdue')
            : (statusLabel !== '' ? ' omo-indicator-embed__status-dot--current' : ' omo-indicator-embed__status-dot--unknown');
        return '<span class="omo-indicator-embed' + statusClass + '" contenteditable="false" data-omo-embed-type="indicator"'
            + ' data-omo-indicator-id="' + String(indicatorId) + '"'
            + ' data-omo-indicator-kind="' + indicatorKind + '"'
            + ' data-omo-indicator-title="' + escapeDocumentEmbedHtml(title) + '"'
            + (description !== '' ? ' data-omo-indicator-description="' + escapeDocumentEmbedHtml(description) + '"' : '')
            + (valueLabel !== '' ? ' data-omo-indicator-value="' + escapeDocumentEmbedHtml(valueLabel) + '"' : '')
            + (dateLabel !== '' ? ' data-omo-indicator-date="' + escapeDocumentEmbedHtml(dateLabel) + '"' : '')
            + (statusLabel !== '' ? ' data-omo-indicator-status="' + escapeDocumentEmbedHtml(statusLabel) + '"' : '')
            + (contextLabel !== '' ? ' data-omo-indicator-context="' + escapeDocumentEmbedHtml(contextLabel) + '"' : '')
            + (chartMinLabel !== '' ? ' data-omo-indicator-chart-min="' + escapeDocumentEmbedHtml(chartMinLabel) + '"' : '')
            + (chartMaxLabel !== '' ? ' data-omo-indicator-chart-max="' + escapeDocumentEmbedHtml(chartMaxLabel) + '"' : '')
            + (indicatorItem && indicatorItem.isOverdue ? ' data-omo-indicator-overdue-severity="' + overdueSeverity + '"' : '')
            + (indicatorItem && indicatorItem.isOverdue ? ' data-omo-indicator-overdue="1"' : '')
            + '><span class="omo-indicator-embed__main">'
            + '<span class="omo-indicator-embed__chart">'
            + '<span class="omo-indicator-embed__chart-plot">'
            + (chartHtml !== '' ? '<span class="omo-indicator-embed__chart-svg">' + chartHtml + '</span>' : '')
            + '</span></span>'
            + '<span class="omo-indicator-embed__copy"><strong><a class="omo-indicator-embed__title" href="#' + routeHash + '"><span class="omo-indicator-embed__status-dot' + statusDotClass + '" aria-hidden="true"></span><span>' + escapeDocumentEmbedHtml(title) + '</span></a></strong>'
            + (description !== '' ? '<span class="omo-indicator-embed__description">' + escapeDocumentEmbedHtml(description) + '</span>' : '')
            + '</span>'
            + '<span class="omo-indicator-embed__values"><b>' + escapeDocumentEmbedHtml(valueLabel) + '</b>'
            + (dateLabel !== '' ? '<time>' + escapeDocumentEmbedHtml(dateLabel) + '</time>' : '')
            + (statusLabel !== '' ? '<em>' + escapeDocumentEmbedHtml(statusLabel) + '</em>' : '')
            + '</span></span></span>';
    }

    function addPvIndicatorValue(field, pointId, context) {
        if (!field || !context || !context.node || !context.input || !context.button) {
            return;
        }

        const rawValue = String(context.input.value || '').trim();
        const indicatorId = Number.parseInt(String(context.indicatorId || ''), 10);
        if (!rawValue || !Number.isInteger(indicatorId) || indicatorId <= 0) {
            context.input.focus();
            return;
        }

        context.input.disabled = true;
        context.button.disabled = true;
        const previousLabel = context.button.textContent;
        context.button.textContent = pageConfig.documentsPvEditorIndicatorValueSaving;
        const formData = new FormData();
        formData.append('action', 'add_indicator_value');
        formData.append('document_id', String(documentId));
        formData.append('oid', String(organizationId));
        formData.append('editor_token', editorToken);
        formData.append('point_id', String(pointId));
        formData.append('indicator_id', String(indicatorId));
        formData.append('value', rawValue);

        fetch(actionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok || !payload || payload.status !== true || !payload.indicator) {
                        throw payload || new Error('indicator_value_failed');
                    }
                    return payload;
                });
            })
            .then(function (payload) {
                if (typeof field.replaceNodeWithHtml === 'function') {
                    field.replaceNodeWithHtml(context.node, buildPvIndicatorEmbedHtml(payload.indicator), false);
                }
            })
            .catch(function (payload) {
                context.input.disabled = false;
                context.button.disabled = false;
                context.button.textContent = previousLabel;
                if (window.alert) {
                    window.alert(String(payload && payload.message || pageConfig.documentsPvEditorIndicatorValueError));
                }
            });
    }

    function refreshPvIndicatorEmbedSnapshots(field) {
        if (!field || typeof field.getEditableElement !== 'function' || typeof field.replaceNodeWithHtml !== 'function') {
            return;
        }

        const editable = field.getEditableElement();
        if (!editable) {
            return;
        }

        editable.querySelectorAll('.omo-indicator-embed[data-omo-embed-type="indicator"]').forEach(function (embedNode) {
            if (String(embedNode.getAttribute('data-omo-indicator-kind') || '') === 'group') {
                return;
            }

            const indicatorId = Number.parseInt(String(embedNode.getAttribute('data-omo-indicator-id') || ''), 10);
            const indicatorItem = embeddableIndicators.find(function (item) {
                return String(item && item.kind || 'indicator') !== 'group' && Number(item && item.id) === indicatorId;
            });
            if (indicatorItem) {
                field.replaceNodeWithHtml(embedNode, buildPvIndicatorEmbedHtml(indicatorItem), false);
            }
        });
    }

    function refreshPvProjectEmbedReviews(field) {
        if (!field || typeof field.getEditableElement !== 'function') {
            return;
        }

        const editable = field.getEditableElement();
        if (!(editable instanceof Element)) {
            return;
        }

        editable.querySelectorAll('[data-omo-project-embed-runtime]').forEach(function (runtime) {
            runtime.remove();
        });

        editable.querySelectorAll('.omo-project-embed[data-omo-embed-type="project"]').forEach(function (embedNode) {
            const projectId = Number.parseInt(String(embedNode.getAttribute('data-omo-project-id') || ''), 10);
            if (!Number.isInteger(projectId) || projectId <= 0) {
                return;
            }

            fetch('/omo/api/projects/children.php?id=' + encodeURIComponent(String(projectId)) + '&embed=pv&format=json', {
                credentials: 'same-origin',
                cache: 'no-store'
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error(String(projectEmbedUi.reviewLoadError || ''));
                }
                return response.json();
            }).then(function (payload) {
                if (!payload || !payload.success || !embedNode.isConnected) {
                    return;
                }

                const status = String(payload.status || '').trim();
                const statusLabel = String(payload.statusLabel || '').trim();
                embedNode.setAttribute('data-omo-project-status', status);
                embedNode.setAttribute('data-omo-project-status-label', statusLabel);
                const header = embedNode.querySelector(':scope > strong');
                if (header instanceof Element) {
                    Array.from(header.children).filter(function (childNode) {
                        if (childNode.tagName !== 'EM') {
                            return false;
                        }
                        const capsuleLabel = String(childNode.textContent || '').trim().toUpperCase();
                        return !/^P[1-5]$/.test(capsuleLabel) && !/^(?:S|M|L|XL|XXL)$/.test(capsuleLabel);
                    }).forEach(function (oldStatusNode) {
                        oldStatusNode.remove();
                    });
                    const metadata = Array.from(embedNode.children).find(function (child) {
                        return child instanceof HTMLElement && child.tagName === 'EM';
                    });
                    if (metadata instanceof HTMLElement) {
                        const parts = String(metadata.textContent || '').split(' · ');
                        const lastPart = String(parts[parts.length - 1] || '').trim();
                        const knownStatusLabels = projectEmbedUi.statusOptions.map(function (option) {
                            return String(option && option.label || '').trim();
                        });
                        if (knownStatusLabels.indexOf(lastPart) >= 0) {
                            parts.pop();
                            metadata.textContent = parts.join(' · ');
                            if (String(metadata.textContent || '').trim() === '') {
                                metadata.remove();
                            }
                        }
                    }

                    if (statusLabel !== '') {
                        const currentStatusNode = document.createElement('em');
                        currentStatusNode.className = 'omo-project-embed__status'
                            + (/^(?:someday|ready|in_progress|blocked|review|done)$/.test(status) ? ' omo-project-embed__status--' + status : '');
                        currentStatusNode.textContent = statusLabel;
                        const firstCapsule = Array.from(header.children).find(function (child) {
                            return child.tagName === 'EM';
                        });
                        header.insertBefore(currentStatusNode, firstCapsule || null);
                    }
                }

                if (!payload.hasChildren || !payload.statusBarHtml) {
                    return;
                }

                const runtime = document.createElement('span');
                runtime.className = 'omo-pv-editor__project-review';
                runtime.setAttribute('contenteditable', 'false');
                runtime.setAttribute('data-omo-project-embed-runtime', '1');
                runtime.setAttribute('data-omo-project-node', '');
                runtime.setAttribute('data-omo-project-id', String(projectId));
                runtime.setAttribute('data-omo-project-children-loading', String(projectEmbedUi.childrenLoading || ''));
                runtime.setAttribute('data-omo-project-children-empty', String(projectEmbedUi.childrenEmpty || ''));
                runtime.setAttribute('data-omo-project-children-error', String(projectEmbedUi.childrenError || ''));
                runtime.innerHTML = '<button type="button" class="omo-pv-editor__project-review-toggle" data-omo-project-embed-toggle aria-expanded="false">'
                    + '<span class="omo-pv-editor__project-review-label">' + escapeDocumentEmbedHtml(projectEmbedUi.childrenLabel || '') + '</span>'
                    + String(payload.statusBarHtml) + '</button>'
                    + '<span class="omo-pv-editor__project-review-children omo-project-embed__children" data-omo-project-embed-children hidden></span>';

                embedNode.appendChild(runtime);
            }).catch(function () {
                // The embedded project remains editable when its live review cannot be loaded.
            });
        });
    }

    function getPvChecklistRunReview(runId) {
        const normalizedRunId = Number(runId || 0);
        if (!Number.isInteger(normalizedRunId) || normalizedRunId <= 0) {
            return Promise.reject(new Error(String(checklistEmbedUi.runInvalid || '')));
        }
        if (checklistRunReviewCache.has(normalizedRunId)) {
            return checklistRunReviewCache.get(normalizedRunId);
        }
        const request = fetch('/omo/api/checklist/pv_run_review.php?id=' + encodeURIComponent(String(normalizedRunId)), {
            credentials: 'same-origin',
            cache: 'no-store'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error(String(checklistEmbedUi.runLoadError || ''));
            }
            return response.json();
        }).then(function (payload) {
            if (!payload || !payload.success) {
                throw new Error(String(checklistEmbedUi.runLoadError || ''));
            }
            return payload;
        }).catch(function (error) {
            checklistRunReviewCache.delete(normalizedRunId);
            throw error;
        });
        checklistRunReviewCache.set(normalizedRunId, request);
        return request;
    }

    function buildPvChecklistRunItemsBar(items) {
        let html = '<span class="omo-project-status-bar omo-checklist-embed__items-bar">';
        (Array.isArray(items) ? items : []).forEach(function (item) {
            const projectId = Number(item && item.projectId || 0);
            const status = String(item && item.status || 'someday');
            const statusClass = /^(?:someday|ready|in_progress|blocked|review|done)$/.test(status) ? status : 'someday';
            const title = escapeDocumentEmbedHtml(String(item && item.title || checklistEmbedUi.itemFallback || ''));
            const statusLabel = escapeDocumentEmbedHtml(String(item && item.statusLabel || checklistEmbedUi.statusPending || ''));
            const size = escapeDocumentEmbedHtml(String(item && item.size || 'M'));
            const weight = Math.max(1, Number(item && item.weight || 1));
            const tooltip = title + ' - ' + statusLabel + ' - ' + size;
            const segment = '<span class="omo-project-status-bar__segment omo-project-status-bar__segment--' + statusClass + '" style="flex:1 1 0" title="' + tooltip + '"></span>';
            html += projectId > 0
                ? '<a href="#projects-d' + String(projectId) + '" class="omo-checklist-embed__item-segment" style="flex:' + String(weight) + ' 1 0">' + segment + '</a>'
                : '<span class="omo-project-status-bar__segment omo-project-status-bar__segment--' + statusClass + '" style="flex:' + String(weight) + ' 1 0" title="' + tooltip + '"></span>';
        });
        return html + '</span>';
    }

    function buildPvChecklistItemsList(items) {
        let html = '<span class="omo-checklist-embed__items-list">';
        (Array.isArray(items) ? items : []).forEach(function (item) {
            const projectId = Number(item && item.projectId || 0);
            const status = String(item && item.status || 'someday');
            const statusClass = /^(?:someday|ready|in_progress|blocked|review|done)$/.test(status) ? status : 'someday';
            const title = escapeDocumentEmbedHtml(String(item && item.title || checklistEmbedUi.itemFallback || ''));
            const statusLabel = escapeDocumentEmbedHtml(String(item && item.statusLabel || checklistEmbedUi.statusPending || ''));
            const roleLabel = String(item && item.holonLabel || '').trim();
            const responsibleLabel = String(item && item.responsibleLabel || '').trim();
            const metadata = [roleLabel, responsibleLabel].filter(function (label) { return label !== ''; });
            const titleHtml = projectId > 0
                ? '<a class="omo-project-embed__child-title" href="#projects-d' + String(projectId) + '">' + title + '</a>'
                : '<span class="omo-project-embed__child-title">' + title + '</span>';
            html += '<span class="omo-checklist-embed__item"><span class="omo-project-embed__child-copy omo-checklist-embed__item-copy">' + titleHtml
                + (metadata.length > 0 ? '<span class="omo-project-embed__child-meta">' + escapeDocumentEmbedHtml(metadata.join(' - ')) + '</span>' : '')
                + '</span>'
                + '<span class="omo-checklist-embed__item-summary"' + (projectId > 0 ? ' data-omo-checklist-item-project-summary="' + String(projectId) + '"' : '') + '><span class="omo-project-embed__status omo-project-embed__status--' + statusClass + '">' + statusLabel + '</span></span>'
                + (canCompleteChecklistProjects && projectId > 0 ? '<button type="button" class="generic-action-button generic-action-button--secondary omo-checklist-embed__complete-archive" data-omo-checklist-complete-archive data-omo-project-id="' + String(projectId) + '">' + escapeDocumentEmbedHtml(String(checklistEmbedUi.completeArchive || '')) + '</button>' : '')
                + '</span>';
        });
        return html + '</span>';
    }

    function refreshPvChecklistRunItemSubprojectBars(childrenHost) {
        childrenHost.querySelectorAll('[data-omo-checklist-item-project-summary]').forEach(function (summaryNode) {
            const projectId = Number(summaryNode.getAttribute('data-omo-checklist-item-project-summary') || 0);
            if (!Number.isInteger(projectId) || projectId <= 0) return;
            fetch('/omo/api/projects/children.php?id=' + encodeURIComponent(String(projectId)) + '&embed=pv&format=json', {
                credentials: 'same-origin',
                cache: 'no-store'
            }).then(function (response) {
                if (!response.ok) throw new Error(String(projectEmbedUi.reviewLoadError || ''));
                return response.json();
            }).then(function (payload) {
                if (!payload || !payload.success || !payload.hasChildren || !payload.statusBarHtml || !summaryNode.isConnected) return;
                summaryNode.innerHTML = String(payload.statusBarHtml);
                summaryNode.classList.add('is-project-summary');
            }).catch(function () {});
        });
    }

    function refreshPvChecklistEmbedReviews(field) {
        const editable = field && typeof field.getEditableElement === 'function' ? field.getEditableElement() : null;
        if (!(editable instanceof Element)) return;
        const expandedStates = new Map();
        editable.querySelectorAll('.omo-checklist-embed[data-omo-embed-type="checklist"]').forEach(function (embedNode) {
            const checklistId = Number(embedNode.getAttribute('data-omo-checklist-id') || 0);
            const runtime = embedNode.querySelector('[data-omo-checklist-embed-runtime]');
            const childrenHost = runtime ? runtime.querySelector('[data-omo-checklist-run-children]') : null;
            if (checklistId <= 0 || !(runtime instanceof Element) || !(childrenHost instanceof Element) || childrenHost.hidden) return;
            const containerToggle = runtime.querySelector('[data-omo-checklist-container-toggle][aria-expanded="true"]');
            if (containerToggle instanceof HTMLButtonElement) {
                expandedStates.set(checklistId, {type: 'container'});
                return;
            }
            const runToggle = runtime.querySelector('[data-omo-checklist-run-toggle][aria-expanded="true"]');
            const runId = runToggle instanceof HTMLButtonElement ? Number(runToggle.getAttribute('data-omo-checklist-run-id') || 0) : 0;
            if (Number.isInteger(runId) && runId > 0) {
                expandedStates.set(checklistId, {type: 'run', runId: runId});
            }
        });
        checklistRunReviewCache.clear();
        editable.querySelectorAll('[data-omo-checklist-embed-runtime]').forEach(function (node) { node.remove(); });
        editable.querySelectorAll('.omo-checklist-embed[data-omo-embed-type="checklist"]').forEach(function (embedNode) {
            const checklistId = Number(embedNode.getAttribute('data-omo-checklist-id') || 0); if (checklistId <= 0) return;
            fetch('/omo/api/checklist/pv_review.php?id=' + encodeURIComponent(String(checklistId)), {credentials: 'same-origin', cache: 'no-store'}).then(function (response) { return response.json(); }).then(function (payload) {
                if (!payload || !payload.success || !embedNode.isConnected) return;
                const runtime = document.createElement('span'); runtime.className = 'omo-checklist-embed__review' + (Number(payload.overdueCount || 0) > 0 ? ' is-overdue' : ''); runtime.setAttribute('contenteditable', 'false'); runtime.setAttribute('data-omo-checklist-embed-runtime', '1');
                const entries = Array.isArray(payload.entries) ? payload.entries : [];
                const hasNoRuns = !payload.isContainer && entries.length === 0;
                const label = payload.isContainer
                    ? String(checklistEmbedUi.reviewContainer || '')
                    : String(checklistEmbedUi.reviewRuns || '');
                let overview = '';
                if (payload.isContainer) {
                    overview = '<button type="button" class="omo-checklist-embed__container-toggle" data-omo-checklist-container-toggle aria-expanded="false"><span class="omo-project-status-bar">';
                    entries.forEach(function (entry) {
                        const projectId = Number(entry && entry.projectId || 0);
                        const status = String(entry && entry.status || 'someday');
                        const weight = Math.max(1, Number(entry && entry.weight || 1));
                        if (projectId > 0 && /^(?:someday|ready|in_progress|blocked|review|done)$/.test(status)) overview += '<span class="omo-project-status-bar__segment omo-project-status-bar__segment--' + status + '" style="flex:' + String(weight) + ' 1 0" title="' + escapeDocumentEmbedHtml(String(entry.title || '')) + '"></span>';
                    });
                    overview += '</span></button>';
                } else {
                    overview = '<span class="omo-checklist-embed__instances">';
                    if (!hasNoRuns) {
                        entries.forEach(function (entry) {
                            const runId = Number(entry && entry.runId || 0);
                            const status = String(entry && entry.status || 'someday');
                            if (runId <= 0) return;
                            const statusClass = /^(?:someday|ready|in_progress|blocked|review|done)$/.test(status) ? status : 'someday';
                            overview += '<span class="omo-checklist-embed__instance"><button type="button" class="omo-checklist-embed__instance-toggle" data-omo-checklist-run-toggle data-omo-checklist-run-id="' + String(runId) + '" aria-expanded="false">'
                                + '<span class="omo-checklist-embed__instance-title">' + escapeDocumentEmbedHtml(String(entry.title || checklistEmbedUi.runFallback || '')) + '</span>'
                                + '<span class="omo-project-embed__status omo-project-embed__status--' + statusClass + '">' + escapeDocumentEmbedHtml(String(entry.statusLabel || status)) + '</span></button>'
                                + '<span class="omo-checklist-embed__instance-bar" data-omo-checklist-run-bar="' + String(runId) + '"></span></span>';
                        });
                    }
                    overview += '</span>';
                }
                const labelHtml = hasNoRuns
                    ? '<em class="omo-checklist-embed__empty-runs">' + escapeDocumentEmbedHtml(String(checklistEmbedUi.emptyRuns || '')) + '</em>'
                    : escapeDocumentEmbedHtml(label) + (Number(payload.overdueCount || 0) > 0 ? ' &#9888;' : '');
                runtime.innerHTML = '<span class="omo-checklist-embed__review-label">' + labelHtml + '</span>' + overview
                    + '<span class="omo-pv-editor__project-review-children omo-project-embed__children omo-checklist-embed__children" data-omo-checklist-run-children hidden></span>';
                if (payload.isContainer) {
                    runtime.__omoChecklistContainerItems = entries;
                }
                embedNode.appendChild(runtime);
                if (!payload.isContainer) {
                    entries.forEach(function (entry) {
                        const runId = Number(entry && entry.runId || 0);
                        const barHost = runtime.querySelector('[data-omo-checklist-run-bar="' + String(runId) + '"]');
                        if (!(barHost instanceof Element) || runId <= 0) return;
                        getPvChecklistRunReview(runId).then(function (runPayload) {
                            if (!barHost.isConnected) return;
                            const items = Array.isArray(runPayload.items) ? runPayload.items : [];
                            if (items.length > 0) barHost.innerHTML = buildPvChecklistRunItemsBar(items);
                        }).catch(function () {});
                    });
                }
                const expandedState = expandedStates.get(checklistId);
                if (expandedState && expandedState.type === 'container') {
                    const containerToggle = runtime.querySelector('[data-omo-checklist-container-toggle]');
                    if (containerToggle instanceof HTMLButtonElement) loadPvChecklistContainerReview(containerToggle);
                } else if (expandedState && expandedState.type === 'run') {
                    const runToggle = runtime.querySelector('[data-omo-checklist-run-toggle][data-omo-checklist-run-id="' + String(expandedState.runId) + '"]');
                    if (runToggle instanceof HTMLButtonElement) loadPvChecklistRunReview(runToggle);
                }
            }).catch(function () {});
        });
    }

    function loadPvChecklistRunReview(toggle) {
        const runtime = toggle.closest('[data-omo-checklist-embed-runtime]');
        const childrenHost = runtime ? runtime.querySelector('[data-omo-checklist-run-children]') : null;
        const runId = Number(toggle.getAttribute('data-omo-checklist-run-id') || 0);
        if (!(runtime instanceof Element) || !(childrenHost instanceof Element) || !Number.isInteger(runId) || runId <= 0) {
            return;
        }

        if (childrenHost.dataset.omoChecklistRunId === String(runId) && !childrenHost.hidden) {
            childrenHost.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            toggle.classList.remove('is-expanded');
            return;
        }

        runtime.querySelectorAll('[data-omo-checklist-run-toggle]').forEach(function (button) {
            button.setAttribute('aria-expanded', 'false');
            button.classList.remove('is-expanded');
        });
        childrenHost.hidden = false;
        childrenHost.textContent = String(checklistEmbedUi.itemsLoading || '');
        childrenHost.className = 'omo-pv-editor__project-review-children omo-project-embed__children omo-checklist-embed__children omo-project-embed__children-loading';
        toggle.disabled = true;

        getPvChecklistRunReview(runId).then(function (payload) {
            if (!runtime.isConnected) {
                throw new Error(String(checklistEmbedUi.runLoadError || ''));
            }
            const items = Array.isArray(payload.items) ? payload.items : [];
            childrenHost.dataset.omoChecklistRunId = String(runId);
            childrenHost.className = 'omo-pv-editor__project-review-children omo-project-embed__children omo-checklist-embed__children';
            if (items.length === 0) {
                childrenHost.textContent = String(checklistEmbedUi.itemsEmpty || '');
            } else {
                childrenHost.innerHTML = buildPvChecklistItemsList(items);
                refreshPvChecklistRunItemSubprojectBars(childrenHost);
            }
            toggle.setAttribute('aria-expanded', 'true');
            toggle.classList.add('is-expanded');
        }).catch(function () {
            childrenHost.className = 'omo-pv-editor__project-review-children omo-project-embed__children omo-checklist-embed__children omo-project-embed__children-error';
            childrenHost.textContent = String(checklistEmbedUi.itemsLoadError || '');
            childrenHost.hidden = false;
            toggle.setAttribute('aria-expanded', 'false');
        }).finally(function () {
            toggle.disabled = false;
        });
    }

    function loadPvChecklistContainerReview(toggle) {
        const runtime = toggle.closest('[data-omo-checklist-embed-runtime]');
        const childrenHost = runtime ? runtime.querySelector('[data-omo-checklist-run-children]') : null;
        const items = runtime && Array.isArray(runtime.__omoChecklistContainerItems) ? runtime.__omoChecklistContainerItems : [];
        if (!(runtime instanceof Element) || !(childrenHost instanceof Element)) {
            return;
        }
        if (childrenHost.dataset.omoChecklistReviewType === 'container' && !childrenHost.hidden) {
            childrenHost.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            toggle.classList.remove('is-expanded');
            return;
        }

        childrenHost.hidden = false;
        childrenHost.dataset.omoChecklistReviewType = 'container';
        childrenHost.className = 'omo-pv-editor__project-review-children omo-project-embed__children omo-checklist-embed__children';
        if (items.length === 0) {
            childrenHost.textContent = String(checklistEmbedUi.activitiesEmpty || '');
        } else {
            childrenHost.innerHTML = buildPvChecklistItemsList(items);
            refreshPvChecklistRunItemSubprojectBars(childrenHost);
        }
        toggle.setAttribute('aria-expanded', 'true');
        toggle.classList.add('is-expanded');
    }

    function completePvChecklistProject(button) {
        const projectId = Number(button.getAttribute('data-omo-project-id') || 0);
        if (!canCompleteChecklistProjects || button.disabled || !Number.isInteger(projectId) || projectId <= 0) {
            return;
        }
        const previousLabel = button.textContent;
        button.disabled = true;
        button.textContent = String(checklistEmbedUi.completeArchiving || '');
        const formData = new FormData();
        formData.append('action', 'complete_archive_checklist_project');
        formData.append('document_id', String(documentId));
        formData.append('oid', String(organizationId));
        formData.append('editor_token', editorToken);
        formData.append('project_id', String(projectId));
        fetch(actionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload || payload.status !== true) {
                    throw payload || new Error('checklist_project_archive_failed');
                }
                return payload;
            });
        }).then(function () {
            if (typeof window.omoRefreshProjectsDrawerAfterMutation === 'function') {
                window.omoRefreshProjectsDrawerAfterMutation();
            }
            const editorHost = button.closest('[data-omo-pv-point-editor-host]');
            const fieldContainer = button.closest('.omo-simple-html-field');
            const field = editorHost && editorHost.__omoPvPointField
                ? editorHost.__omoPvPointField
                : (fieldContainer && fieldContainer.__omoSimpleHtmlField);
            if (field && typeof field.getEditableElement === 'function') {
                refreshPvChecklistEmbedReviews(field);
            }
        }).catch(function (payload) {
            if (window.alert) {
                window.alert(String(payload && payload.message || checklistEmbedUi.completeArchiveError || ''));
            }
        }).finally(function () {
            if (button.isConnected) {
                button.disabled = false;
                button.textContent = previousLabel;
            }
        });
    }

    function getPvIndicatorEmbedItemKey(indicatorItem) {
        const kind = String(indicatorItem && indicatorItem.kind || '') === 'group' ? 'group' : 'indicator';
        return kind + ':' + String(indicatorItem && indicatorItem.id || '');
    }

    function openPvIndicatorEmbedPicker(field, targetNode) {
        if (!canEmbedIndicators || !field || typeof field.createTemporaryCursorMarker !== 'function' || typeof field.replaceMarkerWithHtml !== 'function' || typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }

        const currentIndicatorId = targetNode instanceof Element
            ? Number.parseInt(String(targetNode.getAttribute('data-omo-indicator-id') || ''), 10)
            : 0;
        const currentIndicatorKind = targetNode instanceof Element && String(targetNode.getAttribute('data-omo-indicator-kind') || '') === 'group'
            ? 'group'
            : 'indicator';
        let marker = targetNode ? null : field.createTemporaryCursorMarker();
        let resolved = false;
        const modalHtml = '<div class="omo-document-embed-picker omo-resource-picker generic-drawer-content">'
            + '<aside class="omo-resource-picker__navigation" data-omo-pv-indicator-embed-scope></aside>'
            + '<div class="omo-resource-picker__content">'
            + '<label class="omo-resource-picker__quick-search"><img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true"><input type="search" class="generic-form-control" data-omo-pv-indicator-embed-search aria-label="' + escapeDocumentEmbedHtml(indicatorEmbedUi.search || '') + '" placeholder="' + escapeDocumentEmbedHtml(indicatorEmbedUi.quickSearchPlaceholder || '') + '"></label>'
            + '<div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select" data-omo-pv-indicator-embed-select aria-label="' + escapeDocumentEmbedHtml(indicatorEmbedUi.visibleIndicators || '') + '" size="10"></select></div>'
            + '<div class="omo-document-embed-picker__preview omo-document-embed-picker__preview--indicator" data-omo-pv-indicator-embed-preview></div>'
            + '<div class="omo-document-embed-picker__actions">'
            + (targetNode ? '<button type="button" class="generic-action-button generic-action-button--danger" data-omo-pv-embed-remove>' + escapeDocumentEmbedHtml(indicatorEmbedUi.remove || '') + '</button>' : '')
            + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-pv-indicator-embed-cancel>' + escapeDocumentEmbedHtml(indicatorEmbedUi.cancel || '') + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-pv-indicator-embed-insert disabled>' + escapeDocumentEmbedHtml(indicatorEmbedUi.insert || '') + '</button></div></div></div>';

        window.commonTopbarOpenModal(indicatorEmbedUi.modalTitle || '', modalHtml, 'html');
        const modalBody = document.getElementById('commonTopbarModalBody');
        if (!(modalBody instanceof Element)) {
            if (marker) field.removeTemporaryMarker(marker);
            return;
        }

        const searchNode = modalBody.querySelector('[data-omo-pv-indicator-embed-search]');
        const selectNode = modalBody.querySelector('[data-omo-pv-indicator-embed-select]');
        const previewNode = modalBody.querySelector('[data-omo-pv-indicator-embed-preview]');
        const cancelButton = modalBody.querySelector('[data-omo-pv-indicator-embed-cancel]');
        const insertButton = modalBody.querySelector('[data-omo-pv-indicator-embed-insert]');
        const removeButton = modalBody.querySelector('[data-omo-pv-embed-remove]');
        let selectedItem = null;
        let scopePicker = null;
        const cleanup = function () { if (marker) field.removeTemporaryMarker(marker); marker = null; };
        const updatePreview = function () {
            if (selectNode && selectNode.value !== '') selectedItem = embeddableIndicators.find(function (item) { return getPvIndicatorEmbedItemKey(item) === String(selectNode.value); }) || null;
            if (previewNode) previewNode.innerHTML = selectedItem ? buildPvIndicatorEmbedHtml(selectedItem) : escapeDocumentEmbedHtml(indicatorEmbedUi.none || '');
            if (insertButton) insertButton.disabled = !selectedItem;
        };
        const render = function () {
            const query = String(searchNode && searchNode.value || '').trim().toLowerCase();
            const matches = embeddableIndicators.filter(function (item) { return (!scopePicker || scopePicker.matches(item.contextHolonId)) && (query === '' || [item.title, item.contextLabel, item.valueLabel, item.statusLabel].join(' ').toLowerCase().indexOf(query) >= 0); });
            if (selectNode) {
                selectNode.innerHTML = '';
                matches.forEach(function (item) { const option = document.createElement('option'); option.value = getPvIndicatorEmbedItemKey(item); option.textContent = (String(item.title || '').trim() || String(indicatorEmbedUi.fallbackTitle || '').replace('{id}', String(item.id))) + (item.valueLabel ? ' - ' + String(item.valueLabel) : ''); selectNode.appendChild(option); });
                selectNode.disabled = matches.length === 0;
            }
            selectedItem = matches.find(function (item) { return Number(item.id) === currentIndicatorId && String(item.kind || 'indicator') === currentIndicatorKind; }) || matches[0] || null;
            if (selectNode && selectedItem) selectNode.value = getPvIndicatorEmbedItemKey(selectedItem);
            updatePreview();
        };
        scopePicker = mountPvResourceScopePicker(modalBody, '[data-omo-pv-indicator-embed-scope]', render);
        window.addEventListener('common-topbar-modal-close', function () { if (!resolved) cleanup(); }, {once: true});
        if (searchNode) { searchNode.addEventListener('input', render); searchNode.focus(); }
        if (selectNode) selectNode.addEventListener('change', updatePreview);
        if (cancelButton) cancelButton.addEventListener('click', function () { cleanup(); window.commonTopbarCloseModal(); });
        if (removeButton) removeButton.addEventListener('click', function () { if (targetNode && typeof field.removeNode === 'function') resolved = field.removeNode(targetNode); window.commonTopbarCloseModal(); });
        if (insertButton) insertButton.addEventListener('click', function () { const embedHtml = buildPvIndicatorEmbedHtml(selectedItem); resolved = insertPvEmbedIntoField(field, targetNode, marker, embedHtml); if (resolved) marker = null; window.commonTopbarCloseModal(); });
        render();
    }

    function getDocumentVisibilityValue() {
        if (!(documentVisibilitySelect instanceof Element)) {
            return '';
        }

        const checked = documentVisibilitySelect.querySelector('.omo-visibility-choice__input:checked');
        return checked instanceof HTMLInputElement ? checked.value : '';
    }

    function setDocumentVisibilityValue(value) {
        if (!(documentVisibilitySelect instanceof Element)) {
            return;
        }

        const input = Array.from(documentVisibilitySelect.querySelectorAll('.omo-visibility-choice__input'))
            .find(function (candidate) {
                return candidate instanceof HTMLInputElement && candidate.value === String(value);
            });
        if (input instanceof HTMLInputElement) {
            input.checked = true;
            if (typeof window.omoSyncVisibilityChoices === 'function') {
                window.omoSyncVisibilityChoices(documentVisibilitySelect);
            }
        }
    }

    function resizeDocumentDescriptionInput() {
        if (!(documentDescriptionInput instanceof HTMLTextAreaElement)) {
            return;
        }

        documentDescriptionInput.style.height = 'auto';
        const maxHeight = 150;
        const contentHeight = Math.max(24, documentDescriptionInput.scrollHeight);
        const nextHeight = Math.min(maxHeight, contentHeight);
        documentDescriptionInput.style.height = nextHeight + 'px';
        documentDescriptionInput.style.overflowY = contentHeight > maxHeight ? 'auto' : 'hidden';
    }

    function normalizeDocumentMetadataText(value) {
        return String(value || '').replace(/\r\n?/g, '\n').trim();
    }

    function documentMetadataIsDirty() {
        if (
            !(documentTitleInput instanceof HTMLInputElement)
            || !(documentDescriptionInput instanceof HTMLTextAreaElement)
            || !(documentVisibilitySelect instanceof Element)
        ) {
            return false;
        }

        return normalizeDocumentMetadataText(documentTitleInput.value) !== normalizeDocumentMetadataText(currentDocumentPayload.title)
            || normalizeDocumentMetadataText(documentDescriptionInput.value) !== normalizeDocumentMetadataText(currentDocumentPayload.description)
            || getDocumentVisibilityValue() !== String(currentDocumentPayload.visibilityType || '');
    }

    function syncDocumentMetadataUi() {
        const isDirty = documentMetadataIsDirty();
        const canGenerateAutoSummary = autoSummaryAvailable && currentDocumentPayload.pvStage === 'review';
        if (documentAutoSummaryButton instanceof HTMLButtonElement) {
            documentAutoSummaryButton.hidden = !canGenerateAutoSummary;
            documentAutoSummaryButton.disabled = autoSummaryPending;
        }
        if (documentMetaSaveButton instanceof HTMLButtonElement) {
            documentMetaSaveButton.disabled = !isDirty || documentMetadataSaving;
            documentMetaSaveButton.classList.toggle('generic-action-button--main', isDirty);
            documentMetaSaveButton.classList.toggle('is-saving', documentMetadataSaving);
            documentMetaSaveButton.textContent = documentMetadataSaving
                ? savingLabel
                : (isDirty ? saveLabel : savedLabel);
        }
    }

    function documentPayloadHasRemoteChanges(documentPayload) {
        if (!documentPayload || typeof documentPayload !== 'object') {
            return false;
        }

        const nextSyncVersion = String(documentPayload.syncVersion || '');
        return nextSyncVersion !== '' && nextSyncVersion !== knownDocumentSyncVersion;
    }

    if (!actionUrl || !Number.isInteger(documentId) || documentId <= 0 || !pointsContainer || !nav) {
        return;
    }

    root.dataset.omoPvEditorReady = '1';

    (function initResizer() {
        if (!(resizer instanceof Element)) {
            return;
        }

        let isResizing = false;

        const applySidebarWidth = function (nextWidth) {
            const numericWidth = Number(nextWidth || 0);
            if (!Number.isFinite(numericWidth) || numericWidth <= 0) {
                return;
            }

            const rootRect = root.getBoundingClientRect();
            const minWidth = 220;
            const maxWidth = Math.max(minWidth, Math.floor(rootRect.width * 0.58));
            const clampedWidth = Math.max(minWidth, Math.min(maxWidth, Math.round(numericWidth)));
            root.style.setProperty('--omo-pv-editor-sidebar-width', clampedWidth + 'px');
        };

        resizer.addEventListener('mousedown', function (event) {
            if (event.button !== 0 || window.matchMedia('(max-width: 980px)').matches) {
                return;
            }

            isResizing = true;
            document.body.classList.add('resizing');
            event.preventDefault();
        });

        document.addEventListener('mousemove', function (event) {
            if (!isResizing) {
                return;
            }

            const rootRect = root.getBoundingClientRect();
            applySidebarWidth(event.clientX - rootRect.left - 5);
        });

        document.addEventListener('mouseup', function () {
            if (!isResizing) {
                return;
            }

            isResizing = false;
            document.body.classList.remove('resizing');
        });
    })();

    (function initTimingResizer() {
        if (!(sidebar instanceof HTMLElement) || !(timingPanel instanceof HTMLElement) || !(timingResizer instanceof HTMLElement)) {
            return;
        }

        const storageKey = 'omo.pv.editor.timing-height.v1';
        const minHeight = 92;
        let isResizing = false;

        const timingChartShell = root.querySelector('.omo-pv-editor__timing-chart-shell');
        const getTimingPanelPaddingHeight = function () {
            const panelStyle = window.getComputedStyle(timingPanel);
            return (parseFloat(panelStyle.paddingTop) || 0) + (parseFloat(panelStyle.paddingBottom) || 0);
        };
        const getTimingChartMaxSize = function () {
            if (!(timingChartShell instanceof HTMLElement)) {
                return 56;
            }

            const chartShellStyle = window.getComputedStyle(timingChartShell);
            const chartGap = parseFloat(chartShellStyle.columnGap) || 12;
            const maxByWidth = timingChartShell.clientWidth - chartGap - 96;
            return Math.max(56, Math.min(180, Math.floor(maxByWidth)));
        };
        const syncTimingChartSize = function () {
            if (!(timingChartShell instanceof HTMLElement) || sidebar.classList.contains('is-timing-collapsed')) {
                return;
            }

            const maxByHeight = timingPanel.clientHeight - getTimingPanelPaddingHeight();
            const chartSize = Math.max(56, Math.min(getTimingChartMaxSize(), Math.floor(maxByHeight)));

            timingPanel.style.setProperty('--omo-pv-editor-timing-chart-size', chartSize + 'px');
        };

        const applyTimingHeight = function (nextHeight) {
            const numericHeight = Number(nextHeight);
            if (!Number.isFinite(numericHeight) || numericHeight < 0) {
                return null;
            }

            const sidebarHeight = sidebar.getBoundingClientRect().height;
            const collapseThreshold = minHeight / 2;
            const maxHeightFromChart = getTimingChartMaxSize() + getTimingPanelPaddingHeight();
            const maxHeight = sidebarHeight > 0
                ? Math.max(minHeight, Math.min(Math.floor(sidebarHeight * 0.72), Math.ceil(maxHeightFromChart)))
                : Math.max(minHeight, Math.ceil(maxHeightFromChart));

            if (numericHeight < collapseThreshold) {
                sidebar.classList.add('is-timing-collapsed');
                sidebar.style.setProperty('--omo-pv-editor-timing-height', '0px');
                return 0;
            }

            const clampedHeight = Math.max(minHeight, Math.min(maxHeight, Math.round(numericHeight)));
            sidebar.classList.remove('is-timing-collapsed');
            sidebar.style.setProperty('--omo-pv-editor-timing-height', clampedHeight + 'px');
            window.requestAnimationFrame(syncTimingChartSize);
            return clampedHeight;
        };

        try {
            const savedHeight = localStorage.getItem(storageKey);
            if (savedHeight !== null) {
                applyTimingHeight(savedHeight);
            } else {
                applyTimingHeight(timingPanel.getBoundingClientRect().height);
            }
        } catch (error) {
            // Keep the default height when browser storage is unavailable.
            applyTimingHeight(timingPanel.getBoundingClientRect().height);
        }

        timingResizer.addEventListener('mousedown', function (event) {
            if (event.button !== 0 || window.matchMedia('(max-width: 980px)').matches) {
                return;
            }

            isResizing = true;
            document.body.classList.add('resizing');
            event.preventDefault();
        });

        document.addEventListener('mousemove', function (event) {
            if (!isResizing) {
                return;
            }

            const sidebarRect = sidebar.getBoundingClientRect();
            applyTimingHeight(sidebarRect.bottom - event.clientY);
        });

        const stopResizing = function () {
            if (!isResizing) {
                return;
            }

            isResizing = false;
            document.body.classList.remove('resizing');
            try {
                localStorage.setItem(storageKey, sidebar.classList.contains('is-timing-collapsed') ? '0' : String(Math.round(timingPanel.getBoundingClientRect().height)));
            } catch (error) {
                // The chosen height remains active for the current page.
            }
        };

        document.addEventListener('mouseup', stopResizing);
        window.addEventListener('blur', stopResizing);
        window.addEventListener('resize', function () {
            applyTimingHeight(sidebar.classList.contains('is-timing-collapsed') ? 0 : timingPanel.getBoundingClientRect().height);
            syncTimingChartSize();
        });

        if (typeof ResizeObserver === 'function') {
            const timingResizeObserver = new ResizeObserver(function () {
                if (!sidebar.classList.contains('is-timing-collapsed')) {
                    applyTimingHeight(timingPanel.getBoundingClientRect().height);
                }
                syncTimingChartSize();
            });
            timingResizeObserver.observe(sidebar);
        }
        syncTimingChartSize();
    })();

    function ensureHtmlFieldLibrary(callback) {
        const ensureHighlightPalette = function (next) {
            if (window.omoHighlightPalette) {
                next();
                return;
            }

            const selector = 'script[data-omo-highlight-palette-script="1"]';
            const existing = document.querySelector(selector);
            if (existing) {
                if (existing.getAttribute('data-loaded') === '1') {
                    next();
                } else {
                    existing.addEventListener('load', next, { once: true });
                }
                return;
            }

            const script = document.createElement('script');
            script.src = '/common/choice/highlight-palette.js?v=20260904-highlight-clear';
            script.async = false;
            script.setAttribute('data-omo-highlight-palette-script', '1');
            script.onload = function () {
                script.setAttribute('data-loaded', '1');
                next();
            };
            document.head.appendChild(script);
        };
        const onReady = function () {
            ensureHighlightPalette(callback);
        };
        const htmlFieldVersion = '20260912-toolbar-always-visible';
        if (
            window.omoSimpleHtmlField
            && typeof window.omoSimpleHtmlField.mount === 'function'
            && String(window.omoSimpleHtmlField.version || '') === htmlFieldVersion
        ) {
            onReady();
            return;
        }

        const selector = 'script[data-omo-simple-html-field-script="1"][data-omo-simple-html-field-version="' + htmlFieldVersion + '"]';
        const existing = document.querySelector(selector);
        if (existing) {
            if (existing.getAttribute('data-loaded') === '1') {
                onReady();
            } else {
                existing.addEventListener('load', onReady, { once: true });
            }
            return;
        }

        const script = document.createElement('script');
        script.src = '/omo/assets/js/simple-html-field.js?v=' + encodeURIComponent(htmlFieldVersion);
        script.async = false;
        script.setAttribute('data-omo-simple-html-field-script', '1');
        script.setAttribute('data-omo-simple-html-field-version', htmlFieldVersion);
        script.onload = function () {
            script.setAttribute('data-loaded', '1');
            onReady();
        };
        document.head.appendChild(script);
    }

    function syncEmptyNavState() {
        if (!nav) {
            return;
        }

        const hasItems = nav.querySelector('[data-omo-pv-nav-node]') !== null;
        const emptyState = nav.querySelector('.omo-pv-editor__empty');
        if (hasItems && emptyState) {
            emptyState.remove();
            return;
        }

        if (!hasItems && !emptyState) {
            const state = document.createElement('div');
            state.className = 'omo-empty-state omo-pv-editor__empty';
            state.textContent = pageConfig.documentsPvEditorNavEmpty;
            nav.appendChild(state);
        }
    }

    function setPointDirtySuppressed(pointId, isSuppressed) {
        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        if (!card) {
            return;
        }

        if (isSuppressed) {
            card.setAttribute('data-omo-pv-point-suppress-dirty', '1');
        } else {
            card.removeAttribute('data-omo-pv-point-suppress-dirty');
        }
    }

    function isPointDirtySuppressed(pointId) {
        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        return !!card && card.getAttribute('data-omo-pv-point-suppress-dirty') === '1';
    }

    function suppressPointDirtyDuring(pointId, callback, releaseDelayMs) {
        setPointDirtySuppressed(pointId, true);
        try {
            callback();
        } finally {
            window.setTimeout(function () {
                setPointDirtySuppressed(pointId, false);
            }, Math.max(0, Number(releaseDelayMs || 140)));
        }
    }

    function syncPointDirtyUi(pointId) {
        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        const isDirty = !!card && card.getAttribute('data-omo-pv-point-dirty') === '1';
        const isSaving = !!card && card.getAttribute('data-omo-pv-point-saving') === '1';

        if (!card) {
            return;
        }

        const saveButton = root.querySelector('[data-omo-pv-point-save="' + pointId + '"]');
        if (saveButton) {
            saveButton.disabled = !isDirty || isSaving;
            saveButton.setAttribute('aria-disabled', (!isDirty || isSaving) ? 'true' : 'false');
            saveButton.classList.toggle('generic-action-button--main', isDirty);
            saveButton.classList.toggle('is-saving', isSaving);
            saveButton.textContent = isSaving ? savingLabel : (isDirty ? saveLabel : savedLabel);
        }
    }

    function setFocusedPoint(pointId) {
        if (!nav) {
            return;
        }

        nav.querySelectorAll('[data-omo-pv-point-nav-row].is-focused').forEach(function (row) {
            if (Number(row.getAttribute('data-omo-pv-point-nav-row') || 0) !== pointId) {
                row.classList.remove('is-focused');
            }
        });

        const row = nav.querySelector('[data-omo-pv-point-nav-row="' + pointId + '"]');
        if (row) {
            row.classList.add('is-focused');
        }
    }

    function clearFocusedPoint(pointId) {
        if (!nav) {
            return;
        }

        const row = nav.querySelector('[data-omo-pv-point-nav-row="' + pointId + '"]');
        if (row) {
            row.classList.remove('is-focused');
        }
    }

    function pointWantsLock(pointId) {
        if (yieldingTakeoverPointIds.has(pointId)) {
            return false;
        }

        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        if (!card) {
            return false;
        }

        return locallyEngagedPointIds.has(pointId)
            || card.getAttribute('data-omo-pv-point-dirty') === '1'
            || card.contains(document.activeElement);
    }

    function syncPointLockState(pointId) {
        if (!Number.isInteger(pointId) || pointId <= 0) {
            return Promise.resolve(null);
        }

        return pointWantsLock(pointId)
            ? ensurePointLock(pointId)
            : releasePointLock(pointId);
    }

    function syncDirtyUi() {
        root.querySelectorAll('[data-omo-pv-point-card]').forEach(function (card) {
            const pointId = Number(card.getAttribute('data-omo-pv-point-card') || 0);
            if (Number.isInteger(pointId) && pointId > 0) {
                syncPointDirtyUi(pointId);
            }
        });
    }

    function stopPvEditorBackgroundWork() {
        if (syncPollTimer !== null) {
            window.clearTimeout(syncPollTimer);
            syncPollTimer = null;
        }
        syncPollPending = null;
        if (lockHeartbeatTimer !== null) {
            window.clearInterval(lockHeartbeatTimer);
            lockHeartbeatTimer = null;
        }
        if (editorLifecycleObserver instanceof MutationObserver) {
            editorLifecycleObserver.disconnect();
            editorLifecycleObserver = null;
        }

        activeLockPointIds.clear();
        pendingLockPointIds.clear();
        pendingUnlockPointIds.clear();
        locallyEngagedPointIds.clear();
        pendingTakeoverPointIds.clear();
        yieldingTakeoverPointIds.clear();
        recoveredTakeoverDraftPointIds.clear();
        preMountEditorDrafts.clear();
        preMountEditorFocusPointIds.clear();
    }

    function markDocumentMetadataDirty() {
        syncDocumentMetadataUi();
    }

    function markPointDirty(pointId, isDirty) {
        if (isDirty && isPointDirtySuppressed(pointId)) {
            return;
        }

        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        if (card) {
            card.setAttribute('data-omo-pv-point-dirty', isDirty ? '1' : '0');
            if (!isDirty) {
                card.removeAttribute('data-omo-pv-point-saving');
            } else {
                pointChangeVersions.set(pointId, (pointChangeVersions.get(pointId) || 0) + 1);
            }
        }

        syncPointDirtyUi(pointId);
        syncPointLockState(pointId);
    }

    function buildPointSignatureMap(pointPayloads) {
        const signatures = {};
        if (!Array.isArray(pointPayloads)) {
            return signatures;
        }

        pointPayloads.forEach(function (pointPayload) {
            if (!pointPayload || !pointPayload.id) {
                return;
            }

            const lock = pointPayload.lock && typeof pointPayload.lock === 'object' ? pointPayload.lock : {};
            const takeover = pointPayload.takeover && typeof pointPayload.takeover === 'object' ? pointPayload.takeover : {};
            signatures[String(pointPayload.id)] = [
                String(pointPayload.syncVersion || ''),
                lock.isActive ? 1 : 0,
                Number(lock.userId || 0),
                lock.isOwnedByCurrentSession ? 1 : 0,
                String(lock.token || ''),
                takeover.isActive ? 1 : 0,
                takeover.isRequestedByCurrentSession ? 1 : 0,
                takeover.mustYield ? 1 : 0,
                String(takeover.requestedAtIso || ''),
                Number(pointPayload.discussionMessageCount || 0)
            ].join('|');
        });

        return signatures;
    }

    function updateKnownPointSignatures(pointPayloads) {
        knownPointSignatures = buildPointSignatureMap(pointPayloads);
    }

    function mergeKnownPointSignature(pointPayload) {
        if (!pointPayload || !pointPayload.id) {
            return;
        }

        const singleSignatureMap = buildPointSignatureMap([pointPayload]);
        knownPointSignatures[String(pointPayload.id)] = String(singleSignatureMap[String(pointPayload.id)] || '');
        mergeCurrentPointPayload(pointPayload);
    }

    function pointCollectionHasRemoteChanges(pointPayloads) {
        const nextSignatures = buildPointSignatureMap(pointPayloads);
        const nextKeys = Object.keys(nextSignatures).sort();
        const currentKeys = Object.keys(knownPointSignatures).sort();
        if (nextKeys.length !== currentKeys.length) {
            return true;
        }

        for (let index = 0; index < nextKeys.length; index += 1) {
            const nextKey = nextKeys[index];
            const currentKey = currentKeys[index];
            if (nextKey !== currentKey) {
                return true;
            }

            if (String(nextSignatures[nextKey]) !== String(knownPointSignatures[currentKey] || '')) {
                return true;
            }
        }

        return false;
    }

    function syncCurrentPointPayloads(pointPayloads) {
        currentPointPayloads = {};
        if (!Array.isArray(pointPayloads)) {
            return;
        }

        pointPayloads.forEach(function (pointPayload) {
            if (!pointPayload || !pointPayload.id) {
                return;
            }

            currentPointPayloads[String(pointPayload.id)] = pointPayload;
        });
    }

    function mergeCurrentPointPayload(pointPayload) {
        if (!pointPayload || !pointPayload.id) {
            return;
        }

        currentPointPayloads[String(pointPayload.id)] = pointPayload;
    }

    function syncDocumentStageUi(documentPayload) {
        if (stageButtons.length === 0 || !documentPayload || typeof documentPayload !== 'object') {
            return;
        }

        const nextStage = String(documentPayload.pvStage || '').trim();
        const canManageStage = documentPayload.canManagePvStage === true;
        let activeIndex = 0;
        stageButtons.forEach(function (button) {
            const isActive = String(button.getAttribute('data-omo-pv-stage-value') || '') === nextStage;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            button.disabled = !canManageStage
                || (nextStage === 'review' && String(button.getAttribute('data-omo-pv-stage-value') || '') !== 'validated');
            if (isActive) {
                activeIndex = stageButtons.indexOf(button);
            }
        });
        if (stageChoice instanceof Element) {
            stageChoice.style.setProperty('--omo-visibility-option-count', String(Math.max(1, stageButtons.length)));
            stageChoice.style.setProperty('--omo-visibility-active-index', String(activeIndex));
        }
    }

    function refreshPvEditorAfterReview() {
        const drawer = root.closest('[data-omo-external-panel-drawer="1"]');
        const contentUrl = drawer instanceof Element
            ? String(drawer.dataset.omoExternalContentUrl || '').trim()
            : '';
        if (!(drawer instanceof Element) || contentUrl === '' || typeof window.omoOpenExternalPanelDrawer !== 'function') {
            return false;
        }

        const titleNode = drawer.querySelector('[data-omo-external-panel-drawer-title]');
        const descriptionNode = drawer.querySelector('[data-omo-external-panel-drawer-description]');
        return window.omoOpenExternalPanelDrawer({
            url: contentUrl,
            mode: 'fetch',
            title: titleNode instanceof Element ? String(titleNode.textContent || '').trim() : '',
            description: descriptionNode instanceof Element ? String(descriptionNode.textContent || '').trim() : '',
            variant: drawer.classList.contains('omo-external-panel-drawer--top-sheet') ? 'top-sheet' : '',
            persistKey: String(drawer.dataset.omoPersistKey || '').trim(),
            keepMountedOnClose: drawer.dataset.omoKeepMounted === '1',
            forceReload: true
        }) === true;
    }

    function syncPvEditorUi(documentPayload) {
        if (!documentPayload || typeof documentPayload !== 'object') {
            return;
        }

        syncEventExtensionUi(documentPayload);

        if (secretaryName instanceof Element) {
            const label = String(documentPayload.pvEditorLabel || '').trim();
            secretaryName.textContent = label !== '' ? label : pageConfig.label;
        }

        const isCurrentEditor = currentUserId > 0
            && Number(documentPayload.pvEditorUserId || 0) === currentUserId;
        const isWaitingForReplacement = isCurrentEditor && documentPayload.pvEditorHandoverOpen === true;
        if (secretaryState instanceof Element) {
            secretaryState.hidden = !isCurrentEditor;
            secretaryState.classList.toggle('is-waiting', isWaitingForReplacement);
            secretaryState.textContent = isWaitingForReplacement
                ? pageConfig.pvEditorHandoverWaiting
                : pageConfig.pvEditorActive;
        }

        if (claimSecretaryButton instanceof HTMLButtonElement) {
            const canClaimEditor = documentPayload.canClaimPvEditor === true;
            const canReplaceEditor = documentPayload.canReplacePvEditor === true;
            const hasEditor = Number(documentPayload.pvEditorUserId || 0) > 0;
            const isReview = String(documentPayload.pvStage || '') === 'review';
            claimSecretaryButton.hidden = isReview || (!isCurrentEditor && !canClaimEditor && !canReplaceEditor);
            claimSecretaryButton.disabled = claimSecretaryButton.hidden || isWaitingForReplacement;
            claimSecretaryButton.classList.toggle('is-waiting', isWaitingForReplacement);
            claimSecretaryButton.dataset.omoPvSecretaryAction = isCurrentEditor
                ? 'pass_pv_editor'
                : (canClaimEditor ? 'claim_pv_editor' : 'replace_pv_editor');
            claimSecretaryButton.replaceChildren();
            if (isWaitingForReplacement) {
                const spinner = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                spinner.setAttribute('class', 'omo-pv-editor__secretary-claim-spinner');
                spinner.setAttribute('viewBox', '0 0 24 24');
                spinner.setAttribute('aria-hidden', 'true');
                const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                circle.setAttribute('cx', '12');
                circle.setAttribute('cy', '12');
                circle.setAttribute('r', '8');
                circle.setAttribute('fill', 'none');
                circle.setAttribute('stroke', 'currentColor');
                circle.setAttribute('stroke-width', '3');
                circle.setAttribute('stroke-linecap', 'round');
                circle.setAttribute('stroke-dasharray', '32 18');
                spinner.appendChild(circle);
                claimSecretaryButton.appendChild(spinner);
                claimSecretaryButton.appendChild(document.createTextNode(pageConfig.pvEditorHandoverWaiting));
            } else {
                claimSecretaryButton.textContent = isCurrentEditor
                    ? pageConfig.passPvEditor
                    : (canClaimEditor
                        ? (hasEditor
                            ? pageConfig.reclaimPvEditor
                            : pageConfig.claimPvEditor)
                        : pageConfig.replacePvEditor);
            }
        }

        const canManageInvitations = documentPayload.canManagePvDocument === true
            && String(documentPayload.pvStage || '') === 'preparation';
        if (invitationsMenu instanceof HTMLElement) {
            invitationsMenu.hidden = !canManageInvitations;
            if (!canManageInvitations && invitationsMenuPanel instanceof HTMLElement && invitationsMenuToggle instanceof HTMLButtonElement) {
                invitationsMenuPanel.hidden = true;
                invitationsMenuToggle.setAttribute('aria-expanded', 'false');
            }
        }
        if (invitationsButton instanceof HTMLButtonElement) {
            invitationsButton.hidden = !canManageInvitations;
            invitationsButton.disabled = !canManageInvitations;
        }
        if (invitationsMenuToggle instanceof HTMLButtonElement) {
            invitationsMenuToggle.disabled = !canManageInvitations;
        }

        if (addButton instanceof HTMLButtonElement) {
            addButton.disabled = documentPayload.isPvValidated === true;
        }
        if (templateToggleButton instanceof HTMLButtonElement) {
            const isTemplate = documentPayload.isPvTemplate === true;
            templateToggleButton.dataset.omoPvTemplateState = isTemplate ? '1' : '0';
            templateToggleButton.textContent = isTemplate
                ? String(templateToggleButton.dataset.omoPvTemplateUnmarkLabel || '')
                : String(templateToggleButton.dataset.omoPvTemplateMarkLabel || '');
            templateToggleButton.disabled = documentPayload.canManagePvTemplate !== true;
        }

        if (documentTitleDisplay instanceof Element) {
            documentTitleDisplay.textContent = String(documentPayload.title || '');
        }
        if (documentDescriptionDisplay instanceof Element) {
            const description = String(documentPayload.description || '').trim();
            documentDescriptionDisplay.textContent = description;
            documentDescriptionDisplay.hidden = description === '';
        }

        if (!documentMetadataIsDirty()) {
            if (documentTitleInput instanceof HTMLInputElement) {
                documentTitleInput.value = String(documentPayload.title || '');
            }
            if (documentDescriptionInput instanceof HTMLTextAreaElement) {
                documentDescriptionInput.value = String(documentPayload.description || '');
                resizeDocumentDescriptionInput();
            }
            if (documentVisibilitySelect instanceof Element && documentPayload.visibilityType) {
                setDocumentVisibilityValue(documentPayload.visibilityType);
            }
        }
        syncDocumentMetadataUi();
    }

    function mergeCurrentDocumentPayload(documentPayload) {
        if (!documentPayload || typeof documentPayload !== 'object') {
            return;
        }

        currentDocumentPayload = Object.assign({}, currentDocumentPayload, documentPayload);
        applyAssociatedEventSchedule(currentDocumentPayload.associatedEvent);
        knownDocumentSyncVersion = String(currentDocumentPayload.syncVersion || knownDocumentSyncVersion || '');
        if (currentDocumentPayload.isPvValidated === true) {
            stopPvEditorBackgroundWork();
        }
        syncDocumentStageUi(currentDocumentPayload);
        syncPvEditorUi(currentDocumentPayload);
        syncDocumentMetadataUi();
        if (attendanceEnabled) {
            renderAttendancePayload(currentAttendancePayload);
        }
    }

    function formatAttendanceCount(presentCount, totalCount) {
        return pageConfig.attendanceCount
            .replace('{present}', String(Math.max(0, Number(presentCount || 0))))
            .replace('{total}', String(Math.max(0, Number(totalCount || 0))));
    }

    function canManageAttendance() {
        return currentDocumentPayload
            && currentDocumentPayload.canManagePvDocument === true
            && String(currentDocumentPayload.pvStage || '') !== 'review';
    }

    function buildAttendanceSignature(attendancePayload) {
        if (!attendancePayload || typeof attendancePayload !== 'object') {
            return '';
        }

        const entries = Array.isArray(attendancePayload.entries) ? attendancePayload.entries : [];
        return JSON.stringify({
            eventId: Number(attendancePayload.eventId || 0),
            presentCount: Number(attendancePayload.presentCount || 0),
            totalCount: Number(attendancePayload.totalCount || entries.length || 0),
            canManage: canManageAttendance() ? 1 : 0,
            entries: entries.map(function (entry) {
                return [
                    String(entry && entry.identityKey ? entry.identityKey : ''),
                    String(entry && entry.displayLabel ? entry.displayLabel : ''),
                    String(entry && entry.secondaryLabel ? entry.secondaryLabel : ''),
                    entry && entry.isPresent ? 1 : 0
                ];
            })
        });
    }

    function renderAttendancePayload(attendancePayload) {
        const nextAttendancePayload = attendancePayload && typeof attendancePayload === 'object'
            ? attendancePayload
            : null;
        const nextAttendanceSignature = buildAttendanceSignature(nextAttendancePayload);
        if (nextAttendanceSignature === currentAttendanceSignature) {
            currentAttendancePayload = nextAttendancePayload;
            return;
        }

        const scrollAnchor = captureMainScrollAnchor();
        const focusedEditor = captureFocusedEditor();
        currentAttendancePayload = nextAttendancePayload;
        currentAttendanceSignature = nextAttendanceSignature;

        if (!(attendanceRoot instanceof Element) || !(attendanceList instanceof Element) || !(attendanceCount instanceof Element) || !(attendanceEmpty instanceof Element)) {
            return;
        }

        const entries = currentAttendancePayload && Array.isArray(currentAttendancePayload.entries)
            ? currentAttendancePayload.entries
            : [];
        const presentCount = currentAttendancePayload ? Number(currentAttendancePayload.presentCount || 0) : 0;
        const totalCount = currentAttendancePayload ? Number(currentAttendancePayload.totalCount || entries.length || 0) : 0;

        attendanceRoot.hidden = currentAttendancePayload === null;
        attendanceList.innerHTML = '';
        attendanceCount.textContent = formatAttendanceCount(presentCount, totalCount);

        if (entries.length === 0) {
            attendanceEmpty.hidden = false;
            restoreMainScrollAnchor(scrollAnchor);
            restoreFocusedEditor(focusedEditor);
            return;
        }

        attendanceEmpty.hidden = true;
        entries.forEach(function (entry) {
            if (!entry || !entry.identityKey) {
                return;
            }

            const label = document.createElement('label');
            label.className = 'omo-pv-editor__attendance-item';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.checked = !!entry.isPresent;
            checkbox.disabled = !canManageAttendance();
            checkbox.setAttribute('data-omo-pv-attendance-toggle', String(entry.identityKey));

            const copy = document.createElement('span');
            copy.className = 'omo-pv-editor__attendance-copy';

            const name = document.createElement('span');
            name.className = 'omo-pv-editor__attendance-name';
            name.textContent = String(entry.displayLabel || '');
            copy.appendChild(name);

            const secondaryLabel = String(entry.secondaryLabel || '').trim();
            if (secondaryLabel !== '' && secondaryLabel !== name.textContent) {
                label.title = secondaryLabel;
            }

            label.appendChild(checkbox);
            label.appendChild(copy);
            attendanceList.appendChild(label);
        });

        restoreMainScrollAnchor(scrollAnchor);
        restoreFocusedEditor(focusedEditor);
    }

    function formatMinutesLabel(minutes) {
        const numericMinutes = Number(minutes || 0);
        if (!Number.isFinite(numericMinutes)) {
            return notStartedValue;
        }

        return Math.max(0, Math.round(numericMinutes)) + "'";
    }

    function applyAssociatedEventSchedule(eventPayload) {
        if (!eventPayload || typeof eventPayload !== 'object') {
            return;
        }

        const startAt = String(eventPayload.startAt || '').trim();
        const endAt = String(eventPayload.endAt || '').trim();
        if (startAt !== '') {
            eventStartAtIso = startAt;
        }
        if (endAt !== '') {
            eventEndAtIso = endAt;
        }

        const scheduleLabel = String(eventPayload.scheduleLabel || '').trim();
        if (eventScheduleValue instanceof Element && scheduleLabel !== '') {
            eventScheduleValue.textContent = scheduleLabel;
        }
    }

    function syncEventExtensionUi(documentPayload) {
        if (!(eventExtensionMenu instanceof HTMLElement)) {
            return;
        }

        const canExtendEvent = documentPayload
            && documentPayload.canManagePvDocument === true
            && String(documentPayload.pvStage || '') === 'meeting';
        eventExtensionMenu.hidden = !canExtendEvent;
        if (eventExtensionToggle instanceof HTMLButtonElement) {
            eventExtensionToggle.disabled = !canExtendEvent;
        }
        if (!canExtendEvent) {
            closeEventExtensionMenu();
        }
    }

    function getMeetingDurationMinutes() {
        if (!eventStartAtIso || !eventEndAtIso) {
            return 0;
        }

        const startAt = new Date(eventStartAtIso);
        const endAt = new Date(eventEndAtIso);
        const diff = Math.round((endAt.getTime() - startAt.getTime()) / 60000);
        return Number.isFinite(diff) && diff > 0 ? diff : 0;
    }

    function getTimerRemainingMeetingMinutes(meetingDurationMinutes, fallbackDurationMinutes) {
        const fallbackMinutes = Math.max(0, Number(fallbackDurationMinutes || 0));
        if (!eventStartAtIso || !eventEndAtIso) {
            return fallbackMinutes;
        }

        const startAt = new Date(eventStartAtIso);
        const endAt = new Date(eventEndAtIso);
        const now = new Date();
        if (now < startAt) {
            return Math.max(0, Number(meetingDurationMinutes || 0));
        }

        if (now > endAt) {
            return 0;
        }

        return Math.max(0, Math.round((endAt.getTime() - now.getTime()) / 60000));
    }

    function collectTimingPointState() {
        return Object.keys(currentPointPayloads).filter(function (pointId) {
            const payload = currentPointPayloads[pointId] || {};
            return payload.isGroup !== true;
        }).map(function (pointId) {
            const payload = currentPointPayloads[pointId] || {};
            const numericPointId = Number(pointId || 0);
            const card = root.querySelector('[data-omo-pv-point-card="' + numericPointId + '"]');
            const durationField = card ? card.querySelector('[data-omo-pv-point-duration="' + numericPointId + '"]') : null;
            const handledInput = root.querySelector('[data-omo-pv-point-handled="' + numericPointId + '"]');

            return {
                id: numericPointId,
                durationMinutes: durationField
                    ? Math.max(0, parseInt(String(durationField.value || '0'), 10) || 0)
                    : Math.max(0, parseInt(String(payload.desiredDurationMinutes || 0), 10) || 0),
                isHandled: handledInput ? !!handledInput.checked : !!payload.isHandled,
            };
        });
    }

    function polarPoint(radius, angleDeg) {
        return {
            x: 50 + radius * Math.cos(angleDeg * Math.PI / 180),
            y: 50 + radius * Math.sin(angleDeg * Math.PI / 180),
        };
    }

    function buildTimerSectorPath(ratio, radius) {
        const normalizedRatio = Math.max(0, Math.min(1, Number(ratio || 0)));
        if (normalizedRatio <= 0) {
            return '';
        }

        const centerX = 50;
        const centerY = 50;
        const sectorRadius = Math.max(1, Number(radius || 34));
        const angle = (normalizedRatio * 360) - 90;
        const endPoint = polarPoint(sectorRadius, angle);
        const largeArcFlag = normalizedRatio > 0.5 ? 1 : 0;

        if (normalizedRatio >= 0.999) {
            const topY = centerY - sectorRadius;
            const bottomY = centerY + sectorRadius;
            return [
                'M', centerX, centerY,
                'L', centerX, topY,
                'A', sectorRadius, sectorRadius, 0, 1, 1, centerX, bottomY,
                'A', sectorRadius, sectorRadius, 0, 1, 1, centerX, topY,
                'Z',
            ].join(' ');
        }

        return [
            'M', centerX, centerY,
            'L', centerX, centerY - sectorRadius,
            'A', sectorRadius, sectorRadius, 0, largeArcFlag, 1, endPoint.x, endPoint.y,
            'Z',
        ].join(' ');
    }

    function buildDonutSegmentPath(startRatio, endRatio, outerRadius, innerRadius) {
        const start = Math.max(0, Math.min(0.999999, Number(startRatio || 0)));
        const end = Math.max(start, Math.min(0.999999, Number(endRatio || 0)));
        if (end <= start) {
            return '';
        }

        const startAngle = (start * 360) - 90;
        const endAngle = (end * 360) - 90;
        const largeArcFlag = (end - start) > 0.5 ? 1 : 0;
        const outerStart = polarPoint(outerRadius, startAngle);
        const outerEnd = polarPoint(outerRadius, endAngle);
        const innerEnd = polarPoint(innerRadius, endAngle);
        const innerStart = polarPoint(innerRadius, startAngle);

        return [
            'M', outerStart.x, outerStart.y,
            'A', outerRadius, outerRadius, 0, largeArcFlag, 1, outerEnd.x, outerEnd.y,
            'L', innerEnd.x, innerEnd.y,
            'A', innerRadius, innerRadius, 0, largeArcFlag, 0, innerStart.x, innerStart.y,
            'Z',
        ].join(' ');
    }

    function renderTimingSummary() {
        const meetingDurationMinutes = getMeetingDurationMinutes();
        const pointState = collectTimingPointState();
        let totalPointMinutes = 0;
        let remainingPointMinutes = 0;

        pointState.forEach(function (point) {
            totalPointMinutes += point.durationMinutes;
            if (!point.isHandled) {
                remainingPointMinutes += point.durationMinutes;
            }
        });

        const handledPointMinutes = Math.max(0, totalPointMinutes - remainingPointMinutes);
        const plannedDurationMinutes = meetingDurationMinutes > 0 ? meetingDurationMinutes : totalPointMinutes;
        const timerRemainingMinutes = getTimerRemainingMeetingMinutes(meetingDurationMinutes, plannedDurationMinutes);
        const timerRatio = plannedDurationMinutes > 0
            ? Math.max(0, Math.min(1, timerRemainingMinutes / plannedDurationMinutes))
            : 0;
        const marginMinutes = meetingDurationMinutes > totalPointMinutes
            ? meetingDurationMinutes - totalPointMinutes
            : 0;
        const overrunMinutes = meetingDurationMinutes > 0 && totalPointMinutes > meetingDurationMinutes
            ? totalPointMinutes - meetingDurationMinutes
            : 0;
        const bufferMinutes = marginMinutes > 0 ? marginMinutes : overrunMinutes;
        const outerTotalMinutes = handledPointMinutes + remainingPointMinutes + bufferMinutes;
        const chartNode = root.querySelector('[data-omo-pv-timing-chart="1"]');
        const sectorNode = root.querySelector('[data-omo-pv-timing-sector]');
        const outerHandledNode = root.querySelector('[data-omo-pv-timing-outer-handled]');
        const outerRemainingNode = root.querySelector('[data-omo-pv-timing-outer-remaining]');
        const outerBufferNode = root.querySelector('[data-omo-pv-timing-outer-buffer]');
        const outerBaseNode = root.querySelector('[data-omo-pv-timing-outer-base]');
        const centerNode = root.querySelector('[data-omo-pv-timing-center]');
        const handledLegendNode = root.querySelector('[data-omo-pv-timing-legend-handled]');
        const remainingLegendNode = root.querySelector('[data-omo-pv-timing-legend-remaining]');
        const bufferLegendNode = root.querySelector('[data-omo-pv-timing-legend-buffer]');
        const bufferLegendLabelNode = root.querySelector('[data-omo-pv-timing-buffer-label]');
        const bufferLegendSwatchNode = root.querySelector('[data-omo-pv-timing-buffer-swatch]');

        if (centerNode) {
            centerNode.textContent = plannedDurationMinutes > 0 ? formatMinutesLabel(timerRemainingMinutes) : notStartedValue;
        }
        if (handledLegendNode) {
            handledLegendNode.textContent = formatMinutesLabel(handledPointMinutes);
        }
        if (remainingLegendNode) {
            remainingLegendNode.textContent = formatMinutesLabel(remainingPointMinutes);
        }
        if (bufferLegendNode) {
            bufferLegendNode.textContent = formatMinutesLabel(marginMinutes > 0 ? marginMinutes : overrunMinutes);
        }
        if (bufferLegendLabelNode) {
            bufferLegendLabelNode.textContent = overrunMinutes > 0 ? overrunLegendLabel : marginLegendLabel;
        }
        if (bufferLegendSwatchNode) {
            bufferLegendSwatchNode.style.background = overrunMinutes > 0 ? '#ef4444' : '#cbd5e1';
        }
        if (sectorNode) {
            sectorNode.setAttribute('d', buildTimerSectorPath(timerRatio, 34));
        }
        if (outerHandledNode || outerRemainingNode || outerBufferNode) {
            if (outerBaseNode) {
                outerBaseNode.setAttribute('fill', meetingDurationMinutes > 0 ? '#e2e8f0' : 'transparent');
            }

            let cursorRatio = 0;
            const applyOuterSegment = function (node, minutes) {
                if (!node) {
                    return;
                }

                const segmentRatio = outerTotalMinutes > 0 ? Math.max(0, Number(minutes || 0)) / outerTotalMinutes : 0;
                node.setAttribute('d', buildDonutSegmentPath(cursorRatio, cursorRatio + segmentRatio, 50, 37));
                cursorRatio += segmentRatio;
            };

            applyOuterSegment(outerHandledNode, handledPointMinutes);
            applyOuterSegment(outerRemainingNode, remainingPointMinutes);
            applyOuterSegment(outerBufferNode, bufferMinutes);
            if (outerBufferNode) {
                outerBufferNode.setAttribute('fill', overrunMinutes > 0 ? '#ef4444' : '#cbd5e1');
            }
        }
        if (chartNode) {
            chartNode.setAttribute('title', plannedDurationMinutes > 0
                ? formatMinutesLabel(timerRemainingMinutes)
                : notStartedValue);
        }
    }

    function mountEditableCard(card) {
        if (!(card instanceof Element)) {
            return;
        }

        if (String(card.getAttribute('data-omo-pv-point-editable') || '') !== '1') {
            return;
        }

        const pointId = Number(card.getAttribute('data-omo-pv-point-card') || 0);
        if (!Number.isInteger(pointId) || pointId <= 0) {
            return;
        }

        const titleField = card.querySelector('[data-omo-pv-point-title="' + pointId + '"]');
        const typeField = card.querySelector('[data-omo-pv-point-type="' + pointId + '"]');
        const durationField = card.querySelector('[data-omo-pv-point-duration="' + pointId + '"]');
        const priorityField = card.querySelector('[data-omo-pv-point-priority="' + pointId + '"]');
        const authorField = card.querySelector('[data-omo-pv-point-author="' + pointId + '"]');
        const concernedHolonField = card.querySelector('[data-omo-pv-point-concerned-holon="' + pointId + '"]');
        const confidentialField = card.querySelector('[data-omo-pv-point-confidential="' + pointId + '"]');
        const editorHost = card.querySelector('[data-omo-pv-point-editor-host="' + pointId + '"]');
        const sourceField = card.querySelector('[data-omo-pv-point-content-source="' + pointId + '"]');

        if (!editorHost || !sourceField || editorHost.__omoPvPointMounted === true) {
            return;
        }

        ensureHtmlFieldLibrary(function () {
            if (!window.omoSimpleHtmlField || typeof window.omoSimpleHtmlField.mount !== 'function') {
                return;
            }

            let field = null;
            suppressPointDirtyDuring(pointId, function () {
                const customButtons = [];
                customButtons.push({
                    name: 'omoPvHighlight',
                    group: 'color',
                    label: String(editorClientUi.highlightLabel || ''),
                    contents: '<img src="/omo/images/tools/surligneur.png" alt="" class="omo-simple-html-highlight-icon black-icon">',
                    className: 'note-btn-light omo-pv-editor__highlight-button',
                    title: String(editorClientUi.highlightTitle || ''),
                    onClick: function (context) {
                        const api = context && context.api ? context.api : field;
                        if (!api || typeof api.applyBackgroundColor !== 'function' || !window.omoHighlightPalette) {
                            return;
                        }

                        if (typeof api.saveRange === 'function') {
                            api.saveRange();
                        }

                        window.omoHighlightPalette.open({
                            anchor: context && context.event ? context.event.currentTarget : null,
                            onSelect: function (color) {
                                api.applyBackgroundColor(color);
                            }
                        });
                    }
                });
                if (canEmbedDocuments) {
                    customButtons.push({
                        name: 'omoPvDocumentEmbed',
                        group: 'omo-pv-document-embed',
                        label: String(documentEmbedUi.toolbarLabel || ''),
                        title: String(documentEmbedUi.buttonTitle || ''),
                        className: 'note-btn-light omo-pv-editor__document-embed-button',
                        focusForInsertion: true,
                        onClick: function (context) {
                            openPvDocumentEmbedPicker(context && context.api ? context.api : field);
                        }
                    });
                }
                if (canEmbedDecisions) {
                    customButtons.push({
                        name: 'omoPvDecisionEmbed',
                        group: 'omo-pv-decision-embed',
                        label: String(decisionEmbedUi.toolbarLabel || ''),
                        title: String(decisionEmbedUi.buttonTitle || ''),
                        className: 'note-btn-light omo-pv-editor__decision-embed-button',
                        focusForInsertion: true,
                        onClick: function (context) {
                            openPvDecisionEmbedPicker(context && context.api ? context.api : field);
                        }
                    });
                }
                if (canEmbedProjects) {
                    customButtons.push({
                        name: 'omoPvProjectEmbed', group: 'omo-pv-project-embed', label: String(projectEmbedUi.toolbarLabel || ''), title: String(projectEmbedUi.buttonTitle || ''), className: 'note-btn-light omo-pv-editor__project-embed-button', focusForInsertion: true,
                        onClick: function (context) {
                            const api = context && context.api ? context.api : field;
                            openPvProjectEmbedPicker(api, null, getPvProjectCreationContext(api, card));
                        }
                    });
                }
                if (canEmbedChecklists) {
                    customButtons.push({ name: 'omoPvChecklistEmbed', group: 'omo-pv-checklist-embed', label: String(checklistEmbedUi.toolbarLabel || ''), title: String(checklistEmbedUi.buttonTitle || ''), className: 'note-btn-light omo-pv-editor__checklist-embed-button', focusForInsertion: true, onClick: function (context) { openPvChecklistEmbedPicker(context && context.api ? context.api : field); } });
                }
                if (canEmbedEvents) {
                    customButtons.push({
                        name: 'omoPvEventEmbed',
                        group: 'omo-pv-event-embed',
                        label: String(eventEmbedUi.toolbarLabel || ''),
                        title: String(eventEmbedUi.buttonTitle || ''),
                        className: 'note-btn-light omo-pv-editor__event-embed-button',
                        focusForInsertion: true,
                        onClick: function (context) {
                            openPvEventEmbedPicker(context && context.api ? context.api : field);
                        }
                    });
                }
                if (canEmbedIndicators) {
                    customButtons.push({
                        name: 'omoPvIndicatorEmbed',
                        group: 'omo-pv-indicator-embed',
                        label: String(indicatorEmbedUi.toolbarLabel || ''),
                        title: String(indicatorEmbedUi.buttonTitle || ''),
                        className: 'note-btn-light omo-pv-editor__indicator-embed-button',
                        focusForInsertion: true,
                        onClick: function (context) {
                            openPvIndicatorEmbedPicker(context && context.api ? context.api : field);
                        }
                    });
                }
                field = window.omoSimpleHtmlField.mount(editorHost, {
                    value: String(sourceField.value || ''),
                    placeholder: pageConfig.placeholder2,
                    customButtons: customButtons,
                    indicatorValueUi: indicatorValueUi,
                    resourceGapHelperLabel: pageConfig.resourceGapHelperLabel,
                    onChange: function () {
                        if (isPointDirtySuppressed(pointId)) {
                            return;
                        }

                        ensurePointLock(pointId);
                        markPointDirty(pointId, true);
                    },
                    onIndicatorValueAdd: function (context) {
                        addPvIndicatorValue(field, pointId, context);
                    },
                    onReady: function (api) {
                        refreshPvIndicatorEmbedSnapshots(api);
                        refreshPvProjectEmbedReviews(api);
                        refreshPvChecklistEmbedReviews(api);
                        const preMountDraft = preMountEditorDrafts.get(pointId);
                        const shouldRestoreInitialFocus = preMountEditorFocusPointIds.has(pointId);
                        preMountEditorDrafts.delete(pointId);
                        preMountEditorFocusPointIds.delete(pointId);
                        if (preMountDraft !== undefined && api && typeof api.setValue === 'function') {
                            api.setValue(String(preMountDraft || ''));
                        }
                        if (shouldRestoreInitialFocus && api) {
                            window.requestAnimationFrame(function () {
                                const activeElement = document.activeElement;
                                const currentCard = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
                                const currentEditorHost = currentCard instanceof Element
                                    ? currentCard.querySelector('[data-omo-pv-point-editor-host="' + pointId + '"]')
                                    : null;
                                if (!(currentEditorHost instanceof Element) || currentEditorHost.__omoPvPointField !== api) {
                                    return;
                                }
                                const mayRestoreFocus = activeElement === document.body
                                    || activeElement === document.documentElement
                                    || (currentCard instanceof Element && currentCard.contains(activeElement));
                                if (!mayRestoreFocus) {
                                    return;
                                }
                                if (typeof api.focusForInsertion === 'function') {
                                    api.focusForInsertion();
                                } else if (typeof api.focus === 'function') {
                                    api.focus();
                                }
                            });
                        }
                    },
                    onDoubleClick: function (context) {
                        const targetNode = context && context.target && context.target.closest
                            ? context.target
                            : null;
                        const documentEmbed = targetNode ? targetNode.closest('.omo-document-embed[data-omo-embed-type="document"]') : null;
                        const decisionEmbed = targetNode ? targetNode.closest('.omo-decision-embed[data-omo-embed-type="decision"]') : null;
                        const projectEmbed = targetNode ? targetNode.closest('.omo-project-embed[data-omo-embed-type="project"]') : null;
                        const checklistEmbed = targetNode ? targetNode.closest('.omo-checklist-embed[data-omo-embed-type="checklist"]') : null;
                        const eventEmbed = targetNode ? targetNode.closest('.omo-event-embed[data-omo-embed-type="event"]') : null;
                        const indicatorEmbed = targetNode ? targetNode.closest('.omo-indicator-embed[data-omo-embed-type="indicator"]') : null;
                        if (!documentEmbed && !decisionEmbed && !projectEmbed && !checklistEmbed && !eventEmbed && !indicatorEmbed) {
                            return;
                        }

                        if (context && context.event && typeof context.event.preventDefault === 'function') {
                            context.event.preventDefault();
                        }
                        if (documentEmbed) {
                            openPvDocumentEmbedPicker(field, documentEmbed);
                        } else if (decisionEmbed) {
                            openPvDecisionEmbedPicker(field, decisionEmbed);
                        } else if (projectEmbed) {
                            openPvProjectEmbedPicker(field, projectEmbed);
                        } else if (checklistEmbed) {
                            openPvChecklistEmbedPicker(field, checklistEmbed);
                        } else if (eventEmbed) {
                            openPvEventEmbedPicker(field, eventEmbed);
                        } else if (indicatorEmbed) {
                            openPvIndicatorEmbedPicker(field, indicatorEmbed);
                        }
                    }
                });
            }, 220);

            editorHost.__omoPvPointMounted = true;
            editorHost.__omoPvPointField = field;

            if (titleField) {
                titleField.addEventListener('focus', function () {
                    setFocusedPoint(pointId);
                    ensurePointLock(pointId);
                });
                titleField.addEventListener('input', function () {
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                });
            }

            if (typeField) {
                typeField.addEventListener('focus', function () {
                    setFocusedPoint(pointId);
                    ensurePointLock(pointId);
                });
                typeField.addEventListener('change', function () {
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                });
            }

            if (durationField) {
                durationField.addEventListener('focus', function () {
                    setFocusedPoint(pointId);
                    ensurePointLock(pointId);
                });
                durationField.addEventListener('input', function () {
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                    renderTimingSummary();
                });
            }

            if (priorityField) {
                priorityField.addEventListener('change', function () {
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                });
            }

            if (authorField) {
                authorField.addEventListener('focus', function () {
                    setFocusedPoint(pointId);
                    ensurePointLock(pointId);
                });
                authorField.addEventListener('change', function () {
                    if (concernedHolonField instanceof HTMLSelectElement) {
                        let optionsByUser = {};
                        try {
                            optionsByUser = JSON.parse(String(authorField.getAttribute('data-omo-pv-point-author-holons') || '{}')) || {};
                        } catch (error) {
                            optionsByUser = {};
                        }

                        const nextOptions = Array.isArray(optionsByUser[String(authorField.value || '')])
                            ? optionsByUser[String(authorField.value || '')]
                            : [];
                        concernedHolonField.innerHTML = '';
                        const emptyOption = document.createElement('option');
                        emptyOption.value = '0';
                        emptyOption.textContent = pageConfig.concernedHolonEmpty;
                        concernedHolonField.appendChild(emptyOption);
                        const hasLocalOptions = nextOptions.some(function (option) {
                            return option && option.isLocal === true;
                        });
                        const hasExternalOptions = nextOptions.some(function (option) {
                            return option && option.isLocal !== true;
                        });
                        let externalSeparatorRendered = false;
                        nextOptions.forEach(function (option) {
                            if (!option || !option.id || !option.label) {
                                return;
                            }
                            if (hasLocalOptions && hasExternalOptions && option.isLocal !== true && !externalSeparatorRendered) {
                                const separatorNode = document.createElement('option');
                                separatorNode.disabled = true;
                                separatorNode.setAttribute('aria-hidden', 'true');
                                separatorNode.textContent = '----------';
                                concernedHolonField.appendChild(separatorNode);
                                externalSeparatorRendered = true;
                            }
                            const optionNode = document.createElement('option');
                            optionNode.value = String(option.id);
                            optionNode.textContent = String(option.label);
                            concernedHolonField.appendChild(optionNode);
                        });
                        concernedHolonField.value = '0';
                    }
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                });
            }

            if (concernedHolonField) {
                concernedHolonField.addEventListener('focus', function () {
                    setFocusedPoint(pointId);
                    ensurePointLock(pointId);
                });
                concernedHolonField.addEventListener('change', function () {
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                });
            }

            if (confidentialField) {
                confidentialField.addEventListener('change', function () {
                    ensurePointLock(pointId);
                    markPointDirty(pointId, true);
                });
            }

            editorHost.addEventListener('focusin', function () {
                setFocusedPoint(pointId);
                ensurePointLock(pointId);
            });
        });
    }

    function mountEditableCards(scope) {
        const container = scope instanceof Element ? scope : root;
        container.querySelectorAll('[data-omo-pv-point-card]').forEach(mountEditableCard);
    }

    function getDirtyPointIds() {
        return Array.from(root.querySelectorAll('[data-omo-pv-point-card][data-omo-pv-point-dirty="1"]')).map(function (card) {
            return Number(card.getAttribute('data-omo-pv-point-card') || 0);
        }).filter(function (pointId) {
            return Number.isInteger(pointId) && pointId > 0;
        });
    }

    function hasUnsavedPointChanges() {
        if (!root.isConnected) {
            return false;
        }

        return documentMetadataSaving
            || root.querySelector('[data-omo-pv-point-save]:not(:disabled), [data-omo-pv-document-meta-save]:not(:disabled)') !== null;
    }

    function releaseActiveLocksWithBeacon() {
        if (activeLockPointIds.size === 0) {
            return;
        }

        const pointIds = Array.from(activeLockPointIds).filter(function (pointId) {
            return Number.isInteger(pointId) && pointId > 0;
        });
        const formData = new FormData();
        formData.append('action', 'release_locks');
        formData.append('document_id', String(documentId));
        formData.append('oid', String(organizationId));
        formData.append('editor_token', editorToken);
        formData.append('point_ids', pointIds.join(','));

        const beaconSent = typeof navigator.sendBeacon === 'function'
            && navigator.sendBeacon(actionUrl, formData);
        if (!beaconSent && typeof window.fetch === 'function') {
            window.fetch(actionUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                keepalive: true
            }).catch(function () {
                // A stale lock will still expire server-side after the timeout.
            });
        }
        activeLockPointIds.clear();
        pendingLockPointIds.clear();
        pendingUnlockPointIds.clear();
    }

    function confirmCloseWithUnsavedChanges() {
        if (!hasUnsavedPointChanges()) {
            releaseActiveLocksWithBeacon();
            return true;
        }

        const canClose = window.confirm(unsavedCloseMessage);
        if (canClose) {
            releaseActiveLocksWithBeacon();
        }

        return canClose;
    }

    const previousPvEditorCloseGuard = typeof window.omoPvEditorConfirmCanClose === 'function'
        ? window.omoPvEditorConfirmCanClose
        : null;
    window.omoPvEditorConfirmCanClose = function () {
        if (allowNextExternalClose) {
            allowNextExternalClose = false;
            return true;
        }

        if (root.isConnected) {
            return confirmCloseWithUnsavedChanges();
        }

        return previousPvEditorCloseGuard ? previousPvEditorCloseGuard() : true;
    };

    document.addEventListener('click', function (event) {
        const closeTarget = event.target && event.target.closest
            ? event.target.closest('[data-omo-external-panel-drawer-close="1"], [data-omo-external-panel-drawer-peek-dismiss="1"]')
            : null;
        if (!closeTarget || !root.isConnected) {
            return;
        }

        const drawer = root.closest('[data-omo-external-panel-drawer="1"]');
        if (!drawer || !drawer.contains(closeTarget)) {
            return;
        }

        if (!confirmCloseWithUnsavedChanges()) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            return;
        }

        allowNextExternalClose = true;
        window.setTimeout(function () {
            allowNextExternalClose = false;
        }, 0);
    }, true);

    function captureDraftState() {
        const drafts = {};

        root.querySelectorAll('[data-omo-pv-point-card][data-omo-pv-point-editable="1"]').forEach(function (card) {
            const pointId = Number(card.getAttribute('data-omo-pv-point-card') || 0);
            if (!Number.isInteger(pointId) || pointId <= 0) {
                return;
            }

            const titleField = card.querySelector('[data-omo-pv-point-title="' + pointId + '"]');
            const typeField = card.querySelector('[data-omo-pv-point-type="' + pointId + '"]');
            const durationField = card.querySelector('[data-omo-pv-point-duration="' + pointId + '"]');
            const priorityField = card.querySelector('[data-omo-pv-point-priority="' + pointId + '"]');
            const authorField = card.querySelector('[data-omo-pv-point-author="' + pointId + '"]');
            const concernedHolonField = card.querySelector('[data-omo-pv-point-concerned-holon="' + pointId + '"]');
            const confidentialField = card.querySelector('[data-omo-pv-point-confidential="' + pointId + '"]');
            const editorHost = card.querySelector('[data-omo-pv-point-editor-host="' + pointId + '"]');
            const sourceField = card.querySelector('[data-omo-pv-point-content-source="' + pointId + '"]');
            const statusNode = card.querySelector('[data-omo-pv-point-status="' + pointId + '"]');
            const saveButton = card.querySelector('[data-omo-pv-point-save="' + pointId + '"]');
            const htmlField = editorHost && editorHost.__omoPvPointField ? editorHost.__omoPvPointField : null;
            const hasActiveSaveButton = !!saveButton && !saveButton.disabled;

            drafts[pointId] = {
                title: titleField ? String(titleField.value || '') : '',
                pointType: typeField ? String(typeField.value || '') : 'information',
                desiredDurationMinutes: durationField ? String(durationField.value || '') : '',
                priority: priorityField ? String(priorityField.value || '3') : '3',
                authorValue: authorField ? String(authorField.value || '') : '',
                concernedHolonId: concernedHolonField ? String(concernedHolonField.value || '0') : '0',
                isConfidential: confidentialField ? !!confidentialField.checked : false,
                content: htmlField && typeof htmlField.getValue === 'function'
                    ? String(htmlField.getValue() || '')
                    : (sourceField ? String(sourceField.value || '') : ''),
                statusText: statusNode ? String(statusNode.textContent || '') : '',
                isDirty: card.getAttribute('data-omo-pv-point-dirty') === '1' || hasActiveSaveButton,
            };
        });

        return drafts;
    }

    function updatePointTypeSwitch(card, pointId, pointType) {
        if (!(card instanceof Element)) {
            return;
        }

        const resolvedType = String(pointType || '').trim();
        card.querySelectorAll('[data-omo-pv-point-type-option="' + pointId + '"]').forEach(function (button) {
            const isActive = String(button.getAttribute('data-omo-pv-point-type-value') || '') === resolvedType;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-checked', isActive ? 'true' : 'false');
            button.tabIndex = isActive ? 0 : -1;
        });

        const activeOption = card.querySelector('[data-omo-pv-point-type-option="' + pointId + '"][data-omo-pv-point-type-value="' + resolvedType + '"]');
        const navIcon = nav ? nav.querySelector('[data-omo-pv-point-nav-type-icon="' + pointId + '"]') : null;
        const optionIcon = activeOption ? activeOption.querySelector('img') : null;
        if (navIcon instanceof HTMLImageElement && optionIcon instanceof HTMLImageElement) {
            navIcon.src = optionIcon.currentSrc || optionIcon.src;
            navIcon.alt = String(activeOption.getAttribute('aria-label') || '');
        }
    }

    function selectPointTypeOption(typeButton, focusSelectedButton) {
        if (!(typeButton instanceof HTMLButtonElement)) {
            return;
        }

        const pointId = Number(typeButton.getAttribute('data-omo-pv-point-type-option') || 0);
        const nextType = String(typeButton.getAttribute('data-omo-pv-point-type-value') || '').trim();
        const card = pointId > 0 ? root.querySelector('[data-omo-pv-point-card="' + pointId + '"]') : null;
        const typeField = card ? card.querySelector('[data-omo-pv-point-type="' + pointId + '"]') : null;
        if (!(card instanceof Element) || !(typeField instanceof HTMLInputElement) || nextType === '') {
            return;
        }

        typeField.value = nextType;
        updatePointTypeSwitch(card, pointId, nextType);
        typeField.dispatchEvent(new Event('change', { bubbles: true }));
        if (focusSelectedButton) {
            typeButton.focus();
        }
    }

    function updatePointPriorityMenu(card, pointId, priority) {
        if (!(card instanceof Element)) {
            return;
        }

        const level = Math.max(1, Math.min(5, parseInt(String(priority || '3'), 10) || 3));
        const priorityLabel = String(pvPriorityLabels[level] || pvPriorityLabels[String(level)] || ('P' + level));
        const summary = card.querySelector('[data-omo-pv-point-priority-menu="' + pointId + '"] > summary');
        if (summary instanceof Element) {
            summary.className = 'omo-pv-editor__priority-circle omo-pv-editor__priority-circle--p' + level;
            const label = String(pvPriorityFieldLabel || 'Priorite') + ' ' + priorityLabel;
            summary.setAttribute('aria-label', label);
            summary.setAttribute('title', label);
            const hiddenLabel = summary.querySelector('.omo-pv-editor__sr-only');
            if (hiddenLabel) {
                hiddenLabel.textContent = label;
            }
        }

        card.querySelectorAll('[data-omo-pv-point-priority-option="' + pointId + '"]').forEach(function (button) {
            const isActive = Number(button.getAttribute('data-omo-pv-point-priority-value') || 0) === level;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        const navRow = nav ? nav.querySelector('[data-omo-pv-point-nav-row="' + pointId + '"]') : null;
        if (navRow instanceof Element) {
            for (let currentLevel = 1; currentLevel <= 5; currentLevel++) {
                navRow.classList.remove('omo-pv-editor__nav-row--priority-p' + currentLevel);
            }
            navRow.classList.add('omo-pv-editor__nav-row--priority-p' + level);
        }

        if (currentPointPayloads[String(pointId)]) {
            currentPointPayloads[String(pointId)].priority = level;
        }
    }

    function selectPointPriorityOption(priorityButton) {
        if (!(priorityButton instanceof HTMLButtonElement)) {
            return;
        }

        const pointId = Number(priorityButton.getAttribute('data-omo-pv-point-priority-option') || 0);
        const priority = Number(priorityButton.getAttribute('data-omo-pv-point-priority-value') || 0);
        const card = pointId > 0 ? root.querySelector('[data-omo-pv-point-card="' + pointId + '"]') : null;
        const priorityField = card ? card.querySelector('[data-omo-pv-point-priority="' + pointId + '"]') : null;
        if (!(card instanceof Element) || !(priorityField instanceof HTMLInputElement) || priority < 1 || priority > 5) {
            return;
        }

        priorityField.value = String(priority);
        updatePointPriorityMenu(card, pointId, priority);
        const menu = priorityButton.closest('[data-omo-pv-point-priority-menu]');
        if (menu instanceof HTMLDetailsElement) {
            menu.open = false;
        }
        priorityField.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function restoreDraftState(drafts) {
        if (!drafts || typeof drafts !== 'object') {
            return;
        }

        function applyEditorDraft(pointId, content, attempt) {
            const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
            if (!(card instanceof Element)) {
                return;
            }

            const editorHost = card.querySelector('[data-omo-pv-point-editor-host="' + pointId + '"]');
            const sourceField = card.querySelector('[data-omo-pv-point-content-source="' + pointId + '"]');
            const htmlField = editorHost && editorHost.__omoPvPointField ? editorHost.__omoPvPointField : null;
            if (sourceField) {
                sourceField.value = String(content || '');
            }

            if (htmlField && typeof htmlField.setValue === 'function') {
                suppressPointDirtyDuring(pointId, function () {
                    htmlField.setValue(String(content || ''));
                }, 180);
                return;
            }

            if ((attempt || 0) >= 12) {
                return;
            }

            window.setTimeout(function () {
                applyEditorDraft(pointId, content, (attempt || 0) + 1);
            }, 60);
        }

        Object.keys(drafts).forEach(function (pointKey) {
            const pointId = Number(pointKey || 0);
            if (!Number.isInteger(pointId) || pointId <= 0) {
                return;
            }

            const draft = drafts[pointKey];
            const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
            if (!(card instanceof Element) || !draft) {
                return;
            }

            const titleField = card.querySelector('[data-omo-pv-point-title="' + pointId + '"]');
            const typeField = card.querySelector('[data-omo-pv-point-type="' + pointId + '"]');
            const durationField = card.querySelector('[data-omo-pv-point-duration="' + pointId + '"]');
            const priorityField = card.querySelector('[data-omo-pv-point-priority="' + pointId + '"]');
            const authorField = card.querySelector('[data-omo-pv-point-author="' + pointId + '"]');
            const concernedHolonField = card.querySelector('[data-omo-pv-point-concerned-holon="' + pointId + '"]');
            const confidentialField = card.querySelector('[data-omo-pv-point-confidential="' + pointId + '"]');
            const editorHost = card.querySelector('[data-omo-pv-point-editor-host="' + pointId + '"]');
            const sourceField = card.querySelector('[data-omo-pv-point-content-source="' + pointId + '"]');
            const statusNode = card.querySelector('[data-omo-pv-point-status="' + pointId + '"]');
            const htmlField = editorHost && editorHost.__omoPvPointField ? editorHost.__omoPvPointField : null;

            if (titleField) {
                titleField.value = String(draft.title || '');
            }

            if (typeField) {
                typeField.value = String(draft.pointType || 'information');
                updatePointTypeSwitch(card, pointId, typeField.value);
            }

            if (durationField) {
                durationField.value = String(draft.desiredDurationMinutes || '');
            }

            if (priorityField) {
                priorityField.value = String(draft.priority || '3');
                updatePointPriorityMenu(card, pointId, priorityField.value);
            }

            if (authorField) {
                authorField.value = String(draft.authorValue || '');
            }

            if (concernedHolonField) {
                concernedHolonField.value = String(draft.concernedHolonId || '0');
            }

            if (confidentialField) {
                confidentialField.checked = draft.isConfidential === true;
            }

            applyEditorDraft(pointId, draft.content || '', 0);

            if (statusNode) {
                statusNode.textContent = String(draft.statusText || '');
            }

            if (draft.isDirty === true) {
                card.setAttribute('data-omo-pv-point-dirty', '1');
                syncPointDirtyUi(pointId);
            } else {
                markPointDirty(pointId, false);
            }
        });
    }

    function syncPendingTakeoverUi(pointId) {
        const button = root.querySelector('[data-omo-pv-point-take-over-lock="' + pointId + '"]');
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        const isPending = pendingTakeoverPointIds.has(pointId);
        button.disabled = isPending;
        button.textContent = isPending ? takeOverWaitingLabel : takeOverLockLabel;
    }

    function replacePointHtml(pointPayload) {
        if (!pointPayload || !pointPayload.id) {
            return null;
        }

        const pointId = Number(pointPayload.id || 0);
        if (pointPayload.isGroup === true) {
            mergeKnownPointSignature(pointPayload);
            mergeCurrentPointPayload(pointPayload);
            renderNavTreeFromPayloads();
            return null;
        }
        const temp = document.createElement('div');
        temp.innerHTML = String(pointPayload.cardHtml || '').trim();
        const nextCard = temp.firstElementChild;
        if (!(nextCard instanceof Element)) {
            return null;
        }

        const currentCard = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        const currentPayload = currentPointPayloads[String(pointId)] || null;
        const cardContentIsEquivalent = currentCard instanceof Element
            && currentCard.className === nextCard.className
            && currentCard.innerHTML === nextCard.innerHTML;
        const navContentMayHaveChanged = !currentPayload
            || String(currentPayload.navHtml || '') !== String(pointPayload.navHtml || '')
            || Number(currentPayload.parentId || 0) !== Number(pointPayload.parentId || 0)
            || Number(currentPayload.position || 0) !== Number(pointPayload.position || 0);

        if (cardContentIsEquivalent) {
            mergeKnownPointSignature(pointPayload);
            mergeCurrentPointPayload(pointPayload);
            if (navContentMayHaveChanged) {
                replacePointNavHtml(pointPayload);
            }
            syncPendingTakeoverUi(pointId);
            renderTimingSummary();
            return currentCard;
        }

        if (currentCard && currentCard.parentNode) {
            currentCard.parentNode.replaceChild(nextCard, currentCard);
        } else {
            pointsContainer.appendChild(nextCard);
        }

        replacePointNavHtml(pointPayload);

        syncEmptyNavState();
        mountEditableCard(nextCard);
        hydrateDeferredProposalDetails(nextCard);
        if (!(nextCard.querySelector('[data-omo-pv-point-editor-host]') instanceof Element)) {
            locallyEngagedPointIds.delete(pointId);
            preMountEditorDrafts.delete(pointId);
            preMountEditorFocusPointIds.delete(pointId);
        }
        mergeKnownPointSignature(pointPayload);
        mergeCurrentPointPayload(pointPayload);
        syncPendingTakeoverUi(pointId);
        renderTimingSummary();
        return nextCard;
    }

    function replacePointNavHtml(pointPayload) {
        if (!pointPayload || !pointPayload.id) {
            return null;
        }

        mergeKnownPointSignature(pointPayload);
        mergeCurrentPointPayload(pointPayload);
        renderNavTreeFromPayloads();
        renderTimingSummary();
        return nav.querySelector('[data-omo-pv-nav-node="' + Number(pointPayload.id || 0) + '"]');
    }

    function getOrderedPointIdsFromPayloads() {
        const childrenByParent = {};
        Object.keys(currentPointPayloads).forEach(function (key) {
            const item = currentPointPayloads[key];
            if (!item || !item.id) return;
            const parentId = Math.max(0, Number(item.parentId || 0));
            if (!childrenByParent[parentId]) childrenByParent[parentId] = [];
            childrenByParent[parentId].push(item);
        });
        Object.keys(childrenByParent).forEach(function (parentId) {
            childrenByParent[parentId].sort(function (left, right) {
                return Number(left.position || 0) - Number(right.position || 0) || Number(left.id || 0) - Number(right.id || 0);
            });
        });

        const orderedPointIds = [];
        const visited = new Set();
        const walk = function (parentId) {
            (childrenByParent[parentId] || []).forEach(function (item) {
                const id = Number(item.id || 0);
                if (id <= 0 || visited.has(id)) return;
                visited.add(id);
                if (item.isGroup === true) {
                    walk(id);
                } else {
                    orderedPointIds.push(id);
                }
            });
        };
        walk(0);
        Object.keys(currentPointPayloads).forEach(function (key) {
            const item = currentPointPayloads[key];
            const id = Number(item && item.id || 0);
            if (id > 0 && item.isGroup !== true && !visited.has(id)) orderedPointIds.push(id);
        });
        return orderedPointIds;
    }

    function renderNavTreeFromPayloads() {
        if (!(nav instanceof Element)) return;

        const collapsedIds = new Set(Array.from(nav.querySelectorAll('[data-omo-pv-group].is-collapsed')).map(function (node) {
            return Number(node.getAttribute('data-omo-pv-group') || 0);
        }));
        const focusedRow = nav.querySelector('[data-omo-pv-point-nav-row].is-focused');
        const focusedPointId = focusedRow ? Number(focusedRow.getAttribute('data-omo-pv-point-nav-row') || 0) : 0;
        const activeInput = document.activeElement instanceof HTMLInputElement && document.activeElement.matches('[data-omo-pv-group-title]')
            ? document.activeElement
            : null;
        const activeGroupId = activeInput ? Number(activeInput.getAttribute('data-omo-pv-group-title') || 0) : 0;
        const activeValue = activeInput ? activeInput.value : '';
        const selectionStart = activeInput ? activeInput.selectionStart : null;
        const selectionEnd = activeInput ? activeInput.selectionEnd : null;
        const activeMoveButton = document.activeElement instanceof HTMLButtonElement
            && nav.contains(document.activeElement)
            && document.activeElement.matches('[data-omo-pv-point-move]')
            ? document.activeElement
            : null;
        const activeMovePointId = activeMoveButton
            ? Number(activeMoveButton.getAttribute('data-omo-pv-point-move') || 0)
            : 0;
        const activeMoveDirection = activeMoveButton
            ? String(activeMoveButton.getAttribute('data-omo-pv-point-move-direction') || '').trim().toLowerCase()
            : '';

        const items = Object.keys(currentPointPayloads).map(function (key) { return currentPointPayloads[key]; }).filter(function (item) {
            return item && Number(item.id || 0) > 0;
        });
        if (items.length === 0) {
            nav.innerHTML = '<div class="omo-empty-state omo-pv-editor__empty">' + escapeDocumentEmbedHtml(pageConfig.documentsPvEditorNavEmpty) + '</div>';
            return;
        }

        const childrenByParent = {};
        const itemsById = {};
        items.forEach(function (item) { itemsById[Number(item.id || 0)] = item; });
        items.forEach(function (item) {
            const requestedParentId = Math.max(0, Number(item.parentId || 0));
            const parentItem = itemsById[requestedParentId];
            const parentId = requestedParentId > 0 && parentItem && parentItem.isGroup === true && requestedParentId !== Number(item.id || 0)
                ? requestedParentId
                : 0;
            if (!childrenByParent[parentId]) childrenByParent[parentId] = [];
            childrenByParent[parentId].push(item);
        });
        Object.keys(childrenByParent).forEach(function (parentId) {
            childrenByParent[parentId].sort(function (left, right) {
                return Number(left.position || 0) - Number(right.position || 0) || Number(left.id || 0) - Number(right.id || 0);
            });
        });

        const rootNode = document.createElement('div');
        rootNode.className = 'omo-pv-editor__nav-root generic-stack generic-stack--compact';
        rootNode.setAttribute('data-omo-pv-nav-children', '0');
        const visited = new Set();
        const appendChildren = function (parentId, container) {
            (childrenByParent[parentId] || []).forEach(function (item) {
                const id = Number(item.id || 0);
                if (visited.has(id)) return;
                visited.add(id);
                const temp = document.createElement('div');
                temp.innerHTML = String(item.navHtml || '').trim();
                const node = temp.firstElementChild;
                if (!(node instanceof Element)) return;
                container.appendChild(node);
                if (item.isGroup === true) {
                    if (collapsedIds.has(id)) {
                        node.classList.add('is-collapsed');
                        const toggle = node.querySelector(':scope > .omo-pv-editor__group-head [data-omo-pv-group-toggle]');
                        if (toggle) toggle.setAttribute('aria-expanded', 'false');
                    }
                    const childContainer = node.querySelector('[data-omo-pv-nav-children="' + id + '"]');
                    if (childContainer) appendChildren(id, childContainer);
                }
            });
        };
        appendChildren(0, rootNode);
        nav.replaceChildren(rootNode);
        updateGroupSummaryLabels();

        if (focusedPointId > 0) {
            const nextFocusedRow = nav.querySelector('[data-omo-pv-point-nav-row="' + focusedPointId + '"]');
            if (nextFocusedRow) {
                nextFocusedRow.classList.add('is-focused');
            }
        }

        if (activeGroupId > 0) {
            const nextInput = nav.querySelector('[data-omo-pv-group-title="' + activeGroupId + '"]');
            if (nextInput instanceof HTMLInputElement) {
                nextInput.value = activeValue;
                nextInput.focus({preventScroll: true});
                if (Number.isInteger(selectionStart) && Number.isInteger(selectionEnd)) nextInput.setSelectionRange(selectionStart, selectionEnd);
            }
        }

        if (activeMovePointId > 0 && (activeMoveDirection === 'up' || activeMoveDirection === 'down')) {
            focusPointMoveButton(activeMovePointId, activeMoveDirection);
        }
    }

    function updateGroupSummaryLabels() {
        if (!nav) {
            return;
        }

        const itemsById = {};
        const childrenByParent = {};
        Object.keys(currentPointPayloads).forEach(function (key) {
            const item = currentPointPayloads[key];
            const itemId = Number(item && item.id || 0);
            if (itemId <= 0) {
                return;
            }
            const parentId = Math.max(0, Number(item.parentId || 0));
            itemsById[itemId] = item;
            if (!childrenByParent[parentId]) {
                childrenByParent[parentId] = [];
            }
            childrenByParent[parentId].push(itemId);
        });

        const summaries = {};
        const buildSummary = function (groupId, trail) {
            if (summaries[groupId]) {
                return summaries[groupId];
            }
            trail = trail || {};
            if (trail[groupId]) {
                return { pointCount: 0, durationMinutes: 0 };
            }
            trail[groupId] = true;
            const summary = { pointCount: 0, durationMinutes: 0 };
            (childrenByParent[groupId] || []).forEach(function (childId) {
                const child = itemsById[childId];
                if (!child) {
                    return;
                }
                if (child.isGroup === true) {
                    const childSummary = buildSummary(childId, Object.assign({}, trail));
                    summary.pointCount += childSummary.pointCount;
                    summary.durationMinutes += childSummary.durationMinutes;
                } else {
                    summary.pointCount += 1;
                    summary.durationMinutes += Math.max(0, Number(child.desiredDurationMinutes || 0));
                }
            });
            summaries[groupId] = summary;
            return summary;
        };

        Object.keys(itemsById).forEach(function (itemId) {
            if (itemsById[itemId].isGroup === true) {
                const summary = buildSummary(Number(itemId));
                const summaryNode = nav.querySelector('[data-omo-pv-group="' + itemId + '"] .omo-pv-editor__group-summary');
                if (summaryNode) {
                    summaryNode.textContent = summary.pointCount + ' ' + groupPointsLabel + ' | ' + summary.durationMinutes + ' ' + groupMinutesLabel;
                }
                const pointCountNode = nav.querySelector('[data-omo-pv-group="' + itemId + '"] .omo-pv-editor__group-point-count');
                if (pointCountNode) {
                    const pointCountLabel = summary.pointCount + ' ' + groupPointsLabel;
                    pointCountNode.textContent = String(summary.pointCount);
                    pointCountNode.setAttribute('aria-label', pointCountLabel);
                    pointCountNode.setAttribute('title', pointCountLabel);
                }
            }
        });
    }

    function isPointLockTakenOverRemotely(card, pointPayload) {
        if (!(card instanceof Element) || !pointPayload || !pointPayload.lock) {
            return false;
        }

        const pointId = Number(card.getAttribute('data-omo-pv-point-card') || 0);
        return Number.isInteger(pointId)
            && pointId > 0
            && (activeLockPointIds.has(pointId) || card.querySelector('[data-omo-pv-point-editor-host]') instanceof Element)
            && pointPayload.lock.isLockedByOther === true;
    }

    function isPointCardProtectedFromRemoteRefresh(card, pointPayload) {
        if (!(card instanceof Element)) {
            return false;
        }

        if (isPointLockTakenOverRemotely(card, pointPayload)) {
            return false;
        }

        return card.getAttribute('data-omo-pv-point-dirty') === '1'
            || card.getAttribute('data-omo-pv-point-saving') === '1'
            || locallyEngagedPointIds.has(Number(card.getAttribute('data-omo-pv-point-card') || 0))
            || card.contains(document.activeElement);
    }

    function captureTakeoverDraft(card, pointId) {
        const titleField = card.querySelector('[data-omo-pv-point-title="' + pointId + '"]');
        const editorHost = card.querySelector('[data-omo-pv-point-editor-host="' + pointId + '"]');
        const htmlField = editorHost && editorHost.__omoPvPointField ? editorHost.__omoPvPointField : null;
        const title = titleField instanceof HTMLInputElement ? String(titleField.value || '').trim() : '';
        const contentHtml = htmlField && typeof htmlField.getValue === 'function'
            ? String(htmlField.getValue() || '')
            : '';
        const textContainer = document.createElement('div');
        textContainer.innerHTML = contentHtml;
        const contentText = String(textContainer.innerText || textContainer.textContent || '').trim();
        const plainText = [title, contentText].filter(function (value) {
            return value !== '';
        }).join('\n\n');
        const clipboardHtml = (title !== '' ? '<h1>' + escapeDocumentEmbedHtml(title) + '</h1>' : '') + contentHtml;

        return {
            plainText: plainText,
            html: clipboardHtml
        };
    }

    function copyTakeoverDraftToClipboard(draft) {
        const plainText = String(draft && draft.plainText || '');
        const html = String(draft && draft.html || '');

        const copyPlainText = function () {
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                return navigator.clipboard.writeText(plainText).then(function () { return true; });
            }

            const textarea = document.createElement('textarea');
            textarea.value = plainText;
            textarea.setAttribute('readonly', 'readonly');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            let copied = false;
            try {
                copied = document.execCommand('copy');
            } catch (error) {
                copied = false;
            }
            textarea.remove();
            return Promise.resolve(copied);
        };

        if (
            navigator.clipboard
            && typeof navigator.clipboard.write === 'function'
            && typeof window.ClipboardItem === 'function'
            && typeof window.Blob === 'function'
        ) {
            const clipboardItem = new window.ClipboardItem({
                'text/plain': new window.Blob([plainText], {type: 'text/plain'}),
                'text/html': new window.Blob([html], {type: 'text/html'})
            });
            return navigator.clipboard.write([clipboardItem])
                .then(function () { return true; })
                .catch(copyPlainText);
        }

        return copyPlainText();
    }

    function preserveDraftBeforeRemoteTakeover(card, pointId) {
        if (!(card instanceof Element) || recoveredTakeoverDraftPointIds.has(pointId)) {
            return;
        }

        recoveredTakeoverDraftPointIds.add(pointId);
        const draft = captureTakeoverDraft(card, pointId);
        copyTakeoverDraftToClipboard(draft)
            .then(function (copied) {
                if (copied) {
                    window.alert(takeoverDraftCopiedMessage);
                    return;
                }
                window.prompt(takeoverDraftCopyFailedMessage, draft.plainText);
            })
            .catch(function () {
                window.prompt(takeoverDraftCopyFailedMessage, draft.plainText);
            });
    }

    function pointHasRemoteChange(pointPayload) {
        if (!pointPayload || !pointPayload.id) {
            return false;
        }

        const signatures = buildPointSignatureMap([pointPayload]);
        const pointId = String(pointPayload.id);
        return String(signatures[pointId] || '') !== String(knownPointSignatures[pointId] || '');
    }

    function captureMainScrollAnchor() {
        if (!(mainPanel instanceof Element)) {
            return null;
        }

        const mainRect = mainPanel.getBoundingClientRect();
        const activeCard = document.activeElement instanceof Element
            ? document.activeElement.closest('[data-omo-pv-point-card]')
            : null;
        let anchorCard = activeCard instanceof Element && pointsContainer.contains(activeCard)
            ? activeCard
            : null;

        if (!(anchorCard instanceof Element)) {
            const centerY = mainRect.top + (mainRect.height / 2);
            let bestDistance = Number.POSITIVE_INFINITY;
            pointsContainer.querySelectorAll('[data-omo-pv-point-card]').forEach(function (card) {
                const rect = card.getBoundingClientRect();
                if (rect.bottom < mainRect.top || rect.top > mainRect.bottom) {
                    return;
                }

                const distance = Math.abs((rect.top + (rect.height / 2)) - centerY);
                if (distance < bestDistance) {
                    anchorCard = card;
                    bestDistance = distance;
                }
            });
        }

        if (!(anchorCard instanceof Element)) {
            return { scrollTop: mainPanel.scrollTop };
        }

        return {
            pointId: Number(anchorCard.getAttribute('data-omo-pv-point-card') || 0),
            offsetTop: anchorCard.getBoundingClientRect().top - mainRect.top,
            scrollTop: mainPanel.scrollTop,
        };
    }

    function captureFocusedEditor() {
        const activeElement = document.activeElement;
        const isDocumentMetadataEditor = activeElement === documentTitleInput || activeElement === documentDescriptionInput;
        if (
            !(activeElement instanceof Element)
            || (!isDocumentMetadataEditor && !pointsContainer.contains(activeElement))
        ) {
            return null;
        }

        const pointCard = isDocumentMetadataEditor
            ? null
            : activeElement.closest('[data-omo-pv-point-card]');
        if (!isDocumentMetadataEditor && !(pointCard instanceof Element)) {
            return null;
        }

        const state = { element: activeElement };
        if (activeElement instanceof HTMLInputElement || activeElement instanceof HTMLTextAreaElement) {
            state.selectionStart = activeElement.selectionStart;
            state.selectionEnd = activeElement.selectionEnd;
            return state;
        }

        const selection = window.getSelection();
        if (selection && selection.rangeCount > 0 && activeElement.contains(selection.anchorNode)) {
            state.range = selection.getRangeAt(0).cloneRange();
        }
        return state;
    }

    function restoreFocusedEditor(state) {
        if (!state || !(state.element instanceof Element) || !state.element.isConnected) {
            return;
        }

        window.requestAnimationFrame(function () {
            try {
                state.element.focus({ preventScroll: true });
            } catch (error) {
                state.element.focus();
            }

            if (state.element instanceof HTMLInputElement || state.element instanceof HTMLTextAreaElement) {
                if (Number.isInteger(state.selectionStart) && Number.isInteger(state.selectionEnd)) {
                    state.element.setSelectionRange(state.selectionStart, state.selectionEnd);
                }
                return;
            }

            if (state.range) {
                const selection = window.getSelection();
                if (selection) {
                    selection.removeAllRanges();
                    selection.addRange(state.range);
                }
            }
        });
    }

    function restoreMainScrollAnchor(anchor) {
        if (!(mainPanel instanceof Element) || !anchor) {
            return;
        }

        window.requestAnimationFrame(function () {
            const pointId = Number(anchor.pointId || 0);
            const anchorCard = pointId > 0
                ? pointsContainer.querySelector('[data-omo-pv-point-card="' + pointId + '"]')
                : null;
            if (anchorCard instanceof Element && Number.isFinite(Number(anchor.offsetTop))) {
                const nextOffsetTop = anchorCard.getBoundingClientRect().top - mainPanel.getBoundingClientRect().top;
                mainPanel.scrollTop += nextOffsetTop - Number(anchor.offsetTop);
                return;
            }

            mainPanel.scrollTop = Number(anchor.scrollTop || 0);
        });
    }

    function syncPointHandledBadge(pointId, pointPayload) {
        const currentCard = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        if (!(currentCard instanceof Element)) {
            return;
        }

        const topline = currentCard.querySelector('.omo-document-pv__point-topline');
        if (!(topline instanceof Element)) {
            return;
        }

        const temp = document.createElement('div');
        temp.innerHTML = String(pointPayload.cardHtml || '').trim();
        const nextCard = temp.firstElementChild;
        if (!(nextCard instanceof Element)) {
            return;
        }

        const currentBadge = topline.querySelector('.omo-pv-editor__point-ownership');
        if (currentBadge) {
            currentBadge.remove();
        }

        const nextBadge = nextCard.querySelector('.omo-pv-editor__point-ownership');
        if (nextBadge) {
            topline.appendChild(nextBadge.cloneNode(true));
        }
    }

    function applyPointOrderToCards(pointIds) {
        if (!Array.isArray(pointIds) || !pointsContainer) {
            return;
        }

        const currentPointIds = Array.from(pointsContainer.querySelectorAll('[data-omo-pv-point-card]')).map(function (card) {
            return Number(card.getAttribute('data-omo-pv-point-card') || 0);
        });
        if (currentPointIds.join('|') === pointIds.join('|')) {
            return;
        }

        pointIds.forEach(function (pointId) {
            const card = pointsContainer.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
            if (card) {
                pointsContainer.appendChild(card);
            }
        });
    }

    function applyPointOrderToNav(pointIds) {
        renderNavTreeFromPayloads();
    }

    function collectNavPointIds() {
        if (!nav) {
            return [];
        }

        return Array.from(nav.querySelectorAll('[data-omo-pv-point-nav-row]')).map(function (row) {
            return Number(row.getAttribute('data-omo-pv-point-nav-row') || 0);
        }).filter(function (pointId) {
            return Number.isInteger(pointId) && pointId > 0;
        });
    }

    function focusPointMoveButton(pointId, direction) {
        if (!(nav instanceof Element) || pointId <= 0 || (direction !== 'up' && direction !== 'down')) {
            return;
        }

        const restore = function () {
            const moveButton = nav.querySelector('[data-omo-pv-point-move="' + pointId + '"][data-omo-pv-point-move-direction="' + direction + '"]');
            if (moveButton instanceof HTMLElement) {
                try {
                    moveButton.focus({preventScroll: true});
                } catch (error) {
                    moveButton.focus();
                }
                moveButton.scrollIntoView({behavior: 'smooth', block: 'nearest', inline: 'nearest'});
            }

            const pointCard = pointsContainer instanceof Element
                ? pointsContainer.querySelector('[data-omo-pv-point-card="' + pointId + '"]')
                : null;
            if (pointCard instanceof Element) {
                pointCard.scrollIntoView({behavior: 'smooth', block: 'nearest', inline: 'nearest'});
            }
        };

        restore();
        window.requestAnimationFrame(function () {
            const activeElement = document.activeElement;
            const activeMoveButton = activeElement instanceof HTMLElement
                ? activeElement.closest('[data-omo-pv-point-move]')
                : null;
            const activePointId = activeMoveButton instanceof HTMLElement
                ? Number(activeMoveButton.getAttribute('data-omo-pv-point-move') || 0)
                : 0;
            const activeDirection = activeMoveButton instanceof HTMLElement
                ? String(activeMoveButton.getAttribute('data-omo-pv-point-move-direction') || '').trim().toLowerCase()
                : '';
            if (
                activeElement === document.body
                || activeElement === document.documentElement
                || (activePointId === pointId && activeDirection === direction)
            ) {
                restore();
            }
        });
    }

    function movePointByDirection(pointId, direction, requestedFocusDirection) {
        if (!nav) {
            return false;
        }

        const requestedDirection = String(requestedFocusDirection || '').trim().toLowerCase();
        const activeElement = document.activeElement;
        const activeMoveButton = activeElement instanceof HTMLElement
            ? activeElement.closest('[data-omo-pv-point-move]')
            : null;
        const restoreFocusDirection = requestedDirection === 'up' || requestedDirection === 'down'
            ? requestedDirection
            : (activeMoveButton instanceof HTMLElement
            && Number(activeMoveButton.getAttribute('data-omo-pv-point-move') || 0) === pointId
            ? String(activeMoveButton.getAttribute('data-omo-pv-point-move-direction') || '').trim().toLowerCase()
            : '');

        const row = pointId > 0 ? nav.querySelector('[data-omo-pv-nav-node="' + pointId + '"]') : null;
        if (!(row instanceof Element)) {
            return false;
        }

        const sibling = direction === 'up'
            ? row.previousElementSibling
            : row.nextElementSibling;
        if (sibling instanceof Element && sibling.matches('[data-omo-pv-nav-node]')) {
            if (direction === 'up') {
                sibling.insertAdjacentElement('beforebegin', row);
            } else {
                sibling.insertAdjacentElement('afterend', row);
            }
        } else {
            const parentGroup = row.parentElement instanceof Element
                ? row.parentElement.closest('[data-omo-pv-group]')
                : null;
            if (!(parentGroup instanceof Element)) {
                return false;
            }

            const adjacentGroup = direction === 'up'
                ? parentGroup.previousElementSibling
                : parentGroup.nextElementSibling;
            if (adjacentGroup instanceof Element && adjacentGroup.matches('[data-omo-pv-group]')) {
                const targetContainer = adjacentGroup.querySelector(':scope > [data-omo-pv-nav-children]');
                if (!(targetContainer instanceof Element)) {
                    return false;
                }
                if (direction === 'up') {
                    targetContainer.appendChild(row);
                } else {
                    targetContainer.insertAdjacentElement('afterbegin', row);
                }
                adjacentGroup.classList.remove('is-collapsed');
                const toggle = adjacentGroup.querySelector(':scope > .omo-pv-editor__group-head [data-omo-pv-group-toggle]');
                if (toggle) toggle.setAttribute('aria-expanded', 'true');
            } else if (direction === 'up') {
                parentGroup.insertAdjacentElement('beforebegin', row);
            } else {
                parentGroup.insertAdjacentElement('afterend', row);
            }
        }

        const layout = collectNavLayout();
        applyPointOrderToCards(layout.filter(function (item) {
            const payload = currentPointPayloads[String(item.id)] || {};
            return payload.isGroup !== true;
        }).map(function (item) { return item.id; }));
        if (restoreFocusDirection === 'up' || restoreFocusDirection === 'down') {
            focusPointMoveButton(pointId, restoreFocusDirection);
        }
        persistPointOrder(layout, restoreFocusDirection ? {
            pointId: pointId,
            direction: restoreFocusDirection
        } : null);
        return true;
    }

    function collectNavLayout() {
        const layout = [];
        const walk = function (container, parentId) {
            Array.from(container.children || []).forEach(function (node) {
                if (!(node instanceof Element) || !node.matches('[data-omo-pv-nav-node]')) return;
                const id = Number(node.getAttribute('data-omo-pv-nav-node') || 0);
                if (!Number.isInteger(id) || id <= 0) return;
                layout.push({id: id, parentId: parentId});
                const childContainer = node.querySelector(':scope > [data-omo-pv-nav-children]');
                if (childContainer instanceof Element) walk(childContainer, id);
            });
        };
        const rootContainer = nav.querySelector(':scope > [data-omo-pv-nav-children="0"]');
        if (rootContainer instanceof Element) walk(rootContainer, 0);
        return layout;
    }

    function postPointAction(action, pointId, extraFields) {
        const formData = new FormData();
        formData.append('action', String(action || ''));
        formData.append('document_id', String(documentId));
        formData.append('oid', String(organizationId));
        formData.append('editor_token', editorToken);
        if (Number(pointId || 0) > 0) {
            formData.append('point_id', String(pointId));
        }

        if (extraFields && typeof extraFields === 'object') {
            Object.keys(extraFields).forEach(function (key) {
                if (extraFields[key] === undefined || extraFields[key] === null) {
                    return;
                }

                formData.append(String(key), String(extraFields[key]));
            });
        }

        return fetch(actionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload || payload.status !== true) {
                    throw payload || new Error('request_failed');
                }

                return payload;
            });
        });
    }

    function closeDeferredProposalMenus(exceptMenu) {
        root.querySelectorAll('[data-omo-deferred-proposal-menu]').forEach(function (menu) {
            if (menu === exceptMenu) return;
            const toggle = menu.querySelector('[data-omo-deferred-proposal-menu-toggle]');
            const panel = menu.querySelector('[data-omo-deferred-proposal-menu-panel]');
            menu.classList.remove('is-open');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
            if (panel) panel.hidden = true;
        });
    }

    function hydrateDeferredProposalDetails(scope) {
        if (window.omoChoiceChangeDetails && typeof window.omoChoiceChangeDetails.hydrate === 'function') {
            window.omoChoiceChangeDetails.hydrate(scope || root);
        }
    }

    window.addEventListener('omo-deferred-proposal-saved', function (event) {
        const pointId = Number(event && event.detail ? event.detail.pointId : 0);
        if (!Number.isInteger(pointId) || pointId <= 0) {
            return;
        }
        postPointAction('refresh_point', pointId)
            .then(function (payload) {
                if (payload && payload.point) {
                    replacePointHtml(payload.point);
                }
            });
    });

    function renderPointCollection(pointPayloads, forceRefresh) {
        if (!Array.isArray(pointPayloads)) {
            return;
        }

        forceRefresh = forceRefresh === true;

        const scrollAnchor = captureMainScrollAnchor();
        const focusedEditor = captureFocusedEditor();
        const activeElement = document.activeElement;
        const focusedMoveButton = activeElement instanceof HTMLElement
            ? activeElement.closest('[data-omo-pv-point-move]')
            : null;
        const focusedMovePointId = focusedMoveButton instanceof HTMLElement
            ? Number(focusedMoveButton.getAttribute('data-omo-pv-point-move') || 0)
            : 0;
        const focusedMoveDirection = focusedMoveButton instanceof HTMLElement
            ? String(focusedMoveButton.getAttribute('data-omo-pv-point-move-direction') || '').trim().toLowerCase()
            : '';
        const nextPointIds = [];
        pointPayloads.forEach(function (pointPayload) {
            const pointId = Number(pointPayload && pointPayload.id ? pointPayload.id : 0);
            if (!Number.isInteger(pointId) || pointId <= 0) {
                return;
            }

            nextPointIds.push(pointId);
            const currentCard = pointsContainer.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
            if (!currentCard) {
                replacePointHtml(pointPayload);
                return;
            }

            if (!forceRefresh && !pointHasRemoteChange(pointPayload)) {
                return;
            }

            if (isPointCardProtectedFromRemoteRefresh(currentCard, pointPayload)) {
                // Keep the local draft DOM, but still accept its server-side position.
                // Otherwise a reordered point in edit mode is rebuilt from its old
                // position until the point itself is saved.
                mergeKnownPointSignature(pointPayload);
                return;
            }

            if (isPointLockTakenOverRemotely(currentCard, pointPayload)) {
                if (
                    currentCard.getAttribute('data-omo-pv-point-dirty') === '1'
                    || currentCard.getAttribute('data-omo-pv-point-saving') === '1'
                ) {
                    preserveDraftBeforeRemoteTakeover(currentCard, pointId);
                }
                activeLockPointIds.delete(pointId);
                pendingLockPointIds.delete(pointId);
                pendingUnlockPointIds.delete(pointId);
                locallyEngagedPointIds.delete(pointId);
                yieldingTakeoverPointIds.delete(pointId);
            }

            replacePointHtml(pointPayload);
        });

        const nextPointIdSet = new Set(nextPointIds.map(String));
        pointsContainer.querySelectorAll('[data-omo-pv-point-card]').forEach(function (card) {
            const pointId = String(card.getAttribute('data-omo-pv-point-card') || '');
            if (!nextPointIdSet.has(pointId) && !isPointCardProtectedFromRemoteRefresh(card)) {
                const navRow = nav.querySelector('[data-omo-pv-point-nav-row="' + pointId + '"]');
                if (navRow) {
                    navRow.remove();
                }
                card.remove();
                delete knownPointSignatures[pointId];
                delete currentPointPayloads[pointId];
            }
        });

        Object.keys(currentPointPayloads).forEach(function (pointId) {
            if (!nextPointIdSet.has(String(pointId))) {
                delete knownPointSignatures[String(pointId)];
                delete currentPointPayloads[String(pointId)];
            }
        });

        applyPointOrderToNav(nextPointIds);
        applyPointOrderToCards(getOrderedPointIdsFromPayloads());
        syncEmptyNavState();
        renderTimingSummary();
        restoreMainScrollAnchor(scrollAnchor);
        restoreFocusedEditor(focusedEditor);
        if (focusedMovePointId > 0 && (focusedMoveDirection === 'up' || focusedMoveDirection === 'down')) {
            focusPointMoveButton(focusedMovePointId, focusedMoveDirection);
        }
    }

    function restorePointFocusAfterLock(pointId, focusState) {
        if (!locallyEngagedPointIds.has(pointId)) {
            return;
        }

        window.requestAnimationFrame(function () {
            if (!locallyEngagedPointIds.has(pointId)) {
                return;
            }
            const activeElement = document.activeElement;
            if (activeElement !== document.body && activeElement !== document.documentElement) {
                return;
            }

            if (focusState && focusState.element instanceof Element && focusState.element.isConnected) {
                const editorHost = focusState.element.closest('[data-omo-pv-point-editor-host]');
                if (!(editorHost instanceof Element) || !editorHost.querySelector('.note-editable')) {
                    restoreFocusedEditor(focusState);
                    return;
                }
            }

            const currentCard = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
            const currentEditorHost = currentCard instanceof Element
                ? currentCard.querySelector('[data-omo-pv-point-editor-host="' + pointId + '"]')
                : null;
            const field = currentEditorHost && currentEditorHost.__omoPvPointField
                ? currentEditorHost.__omoPvPointField
                : null;
            if (field && typeof field.focusForInsertion === 'function') {
                field.focusForInsertion();
            } else if (field && typeof field.focus === 'function') {
                field.focus();
            }
        });
    }

    function ensurePointLock(pointId) {
        if (!Number.isInteger(pointId) || pointId <= 0 || !editorToken || !pointWantsLock(pointId)) {
            return Promise.resolve(null);
        }

        if (activeLockPointIds.has(pointId) || pendingLockPointIds.has(pointId) || pendingUnlockPointIds.has(pointId)) {
            return Promise.resolve(null);
        }

        const focusStateAtRequest = captureFocusedEditor();
        pendingLockPointIds.add(pointId);
        return postPointAction('lock_point', pointId)
            .then(function (payload) {
                pendingLockPointIds.delete(pointId);
                activeLockPointIds.add(pointId);
                recoveredTakeoverDraftPointIds.delete(pointId);
                if (payload && payload.point) {
                    mergeKnownPointSignature(payload.point);
                } else if (payload && payload.lock && currentPointPayloads[String(pointId)]) {
                    const pointPayload = Object.assign({}, currentPointPayloads[String(pointId)], {
                        lock: Object.assign({}, currentPointPayloads[String(pointId)].lock || {}, payload.lock)
                    });
                    mergeKnownPointSignature(pointPayload);
                }
                restorePointFocusAfterLock(pointId, focusStateAtRequest);
                if (!pointWantsLock(pointId)) {
                    return releasePointLock(pointId);
                }
                return payload;
            })
            .catch(function (payload) {
                pendingLockPointIds.delete(pointId);
                if (payload && payload.point) {
                    replacePointHtml(payload.point);
                }
                return null;
            });
    }

    function releasePointLock(pointId) {
        if (!Number.isInteger(pointId) || pointId <= 0 || pendingUnlockPointIds.has(pointId)) {
            return Promise.resolve(null);
        }

        if (!activeLockPointIds.has(pointId)) {
            return Promise.resolve(null);
        }

        activeLockPointIds.delete(pointId);
        pendingUnlockPointIds.add(pointId);
        return postPointAction('unlock_point', pointId)
            .then(function (payload) {
                if (payload && payload.point && !pointWantsLock(pointId)) {
                    replacePointHtml(payload.point);
                }
                return payload;
            })
            .catch(function () {
                return null;
            })
            .finally(function () {
                pendingUnlockPointIds.delete(pointId);
                if (pointWantsLock(pointId)) {
                    ensurePointLock(pointId);
                }
            });
    }

    function pointHasTakeoverYieldRequest(pointPayload) {
        return Boolean(
            pointPayload
            && pointPayload.takeover
            && pointPayload.takeover.mustYield === true
        );
    }

    function yieldPointLockForTakeover(pointPayload) {
        const pointId = Number(pointPayload && pointPayload.id ? pointPayload.id : 0);
        if (!Number.isInteger(pointId) || pointId <= 0 || !pointHasTakeoverYieldRequest(pointPayload)) {
            return Promise.resolve(false);
        }
        if (yieldingTakeoverPointIds.has(pointId)) {
            return Promise.resolve(false);
        }

        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        if (!(card instanceof Element) || !(card.querySelector('[data-omo-pv-point-editor-host]') instanceof Element)) {
            return Promise.resolve(false);
        }

        yieldingTakeoverPointIds.add(pointId);
        card.setAttribute('inert', '');
        card.setAttribute('aria-busy', 'true');
        let yieldRequest;
        if (card.getAttribute('data-omo-pv-point-saving') === '1') {
            yieldRequest = Promise.resolve(false);
        } else if (card.getAttribute('data-omo-pv-point-dirty') === '1') {
            yieldRequest = savePoint(pointId);
        } else if (activeLockPointIds.has(pointId)) {
            yieldRequest = releasePointLock(pointId).then(function () { return true; });
        } else {
            yieldRequest = postPointAction('unlock_point', pointId)
                .then(function (payload) {
                    if (payload && payload.point) {
                        replacePointHtml(payload.point);
                    }
                    return true;
                })
                .catch(function () { return false; });
        }

        return Promise.resolve(yieldRequest)
            .finally(function () {
                yieldingTakeoverPointIds.delete(pointId);
                const currentCard = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
                if (currentCard instanceof Element) {
                    currentCard.removeAttribute('inert');
                    currentCard.removeAttribute('aria-busy');
                }
                scheduleNextSyncPoll(0);
            });
    }

    function processIncomingTakeoverRequests(pointPayloads) {
        if (!Array.isArray(pointPayloads)) {
            return Promise.resolve([]);
        }

        return Promise.all(pointPayloads.map(function (pointPayload) {
            if (!pointHasTakeoverYieldRequest(pointPayload)) {
                return Promise.resolve(pointPayload);
            }

            return yieldPointLockForTakeover(pointPayload).then(function () {
                const currentPayload = currentPointPayloads[String(pointPayload.id)] || null;
                if (
                    currentPayload
                    && (pointHasTakeoverYieldRequest(currentPayload)
                        || (currentPayload.lock && currentPayload.lock.isLockedByOther === true))
                ) {
                    return currentPayload;
                }
                return pointPayload;
            });
        }));
    }

    function takeOverPointLock(pointId, triggerButton) {
        if (!Number.isInteger(pointId) || pointId <= 0 || pendingTakeoverPointIds.has(pointId)) {
            return;
        }

        pendingTakeoverPointIds.add(pointId);
        if (triggerButton instanceof HTMLButtonElement) {
            triggerButton.disabled = true;
            triggerButton.textContent = takeOverWaitingLabel;
        }

        const requestTakeover = function () {
            return postPointAction('take_over_point_lock', pointId)
                .then(function (payload) {
                    if (payload && payload.pending === true) {
                        const retryAfterMs = Math.max(250, Math.min(1000, Number(payload.retryAfterMs || 600)));
                        return new Promise(function (resolve) {
                            window.setTimeout(resolve, retryAfterMs);
                        }).then(requestTakeover);
                    }
                    return payload;
                });
        };

        return requestTakeover()
            .then(function (payload) {
                if (!payload || !payload.point) {
                    throw new Error('take_over_lock_failed');
                }

                activeLockPointIds.add(pointId);
                recoveredTakeoverDraftPointIds.delete(pointId);
                const nextCard = replacePointHtml(payload.point);
                const titleField = nextCard
                    ? nextCard.querySelector('[data-omo-pv-point-title="' + pointId + '"]')
                    : null;
                if (titleField instanceof HTMLInputElement) {
                    setFocusedPoint(pointId);
                    titleField.focus();
                }
            })
            .catch(function (error) {
                if (error && error.point) {
                    replacePointHtml(error.point);
                }
                const message = String(error && (error.message || error.text) || editorClientUi.genericError || '');
                window.alert(message);
            })
            .finally(function () {
                pendingTakeoverPointIds.delete(pointId);
                const currentButton = root.querySelector('[data-omo-pv-point-take-over-lock="' + pointId + '"]');
                if (currentButton instanceof HTMLButtonElement) {
                    currentButton.disabled = false;
                    currentButton.textContent = takeOverLockLabel;
                }
            });
    }

    function savePoint(pointId) {
        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        if (
            !card
            || card.getAttribute('data-omo-pv-point-dirty') !== '1'
            || card.getAttribute('data-omo-pv-point-saving') === '1'
        ) {
            return Promise.resolve(false);
        }

        const savedChangeVersion = pointChangeVersions.get(pointId) || 0;

        const titleField = card.querySelector('[data-omo-pv-point-title="' + pointId + '"]');
        const typeField = card.querySelector('[data-omo-pv-point-type="' + pointId + '"]');
        const durationField = card.querySelector('[data-omo-pv-point-duration="' + pointId + '"]');
        const priorityField = card.querySelector('[data-omo-pv-point-priority="' + pointId + '"]');
        const authorField = card.querySelector('[data-omo-pv-point-author="' + pointId + '"]');
        const concernedHolonField = card.querySelector('[data-omo-pv-point-concerned-holon="' + pointId + '"]');
        const confidentialField = card.querySelector('[data-omo-pv-point-confidential="' + pointId + '"]');
        const editorHost = card.querySelector('[data-omo-pv-point-editor-host="' + pointId + '"]');
        const statusNode = card.querySelector('[data-omo-pv-point-status="' + pointId + '"]');
        const htmlField = editorHost && editorHost.__omoPvPointField ? editorHost.__omoPvPointField : null;

        const formData = new FormData();
        formData.append('action', 'save_point');
        formData.append('document_id', String(documentId));
        formData.append('oid', String(organizationId));
        formData.append('editor_token', editorToken);
        formData.append('point_id', String(pointId));
        formData.append('title', titleField ? String(titleField.value || '') : '');
        formData.append('pointtype', typeField ? String(typeField.value || '') : 'information');
        formData.append('desired_duration_minutes', durationField ? String(durationField.value || '') : '');
        formData.append('priority', priorityField ? String(priorityField.value || '3') : '3');
        formData.append('author', authorField ? String(authorField.value || '') : '');
        formData.append('concerned_holon_id', concernedHolonField ? String(concernedHolonField.value || '0') : '0');
        formData.append('is_confidential', confidentialField && confidentialField.checked ? '1' : '0');
        formData.append('content', htmlField && typeof htmlField.getValue === 'function' ? String(htmlField.getValue() || '') : '');

        card.setAttribute('data-omo-pv-point-saving', '1');
        syncPointDirtyUi(pointId);
        if (statusNode) {
            statusNode.textContent = savingLabel;
        }

        return fetch(actionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok || !payload || payload.status !== true || (!payload.point && !payload.hiddenPointId)) {
                        throw payload || new Error('save_failed');
                    }

                    return payload;
                });
            })
            .then(function (payload) {
                const hasChangesAfterSaveStarted = (pointChangeVersions.get(pointId) || 0) !== savedChangeVersion;
                const drafts = hasChangesAfterSaveStarted ? captureDraftState() : null;
                if (payload.hiddenPointId) {
                    activeLockPointIds.delete(pointId);
                    pendingLockPointIds.delete(pointId);
                    pendingUnlockPointIds.delete(pointId);
                    renderPointCollection(Array.isArray(payload.points) ? payload.points : [], true);
                    return true;
                }
                const nextCard = replacePointHtml(payload.point);
                if (hasChangesAfterSaveStarted && drafts && drafts[pointId]) {
                    restoreDraftState({ [pointId]: drafts[pointId] });
                } else {
                    markPointDirty(pointId, false);
                }
                const nextStatus = nextCard ? nextCard.querySelector('[data-omo-pv-point-status="' + pointId + '"]') : null;
                if (nextStatus) {
                    nextStatus.textContent = payload.message || savedLabel;
                }
                syncPointLockState(pointId);
                return !hasChangesAfterSaveStarted;
            })
            .catch(function (error) {
                const lockWasTakenOver = error && error.point && isPointLockTakenOverRemotely(card, error.point);
                if (lockWasTakenOver) {
                    preserveDraftBeforeRemoteTakeover(card, pointId);
                    activeLockPointIds.delete(pointId);
                    pendingLockPointIds.delete(pointId);
                    pendingUnlockPointIds.delete(pointId);
                    locallyEngagedPointIds.delete(pointId);
                    yieldingTakeoverPointIds.delete(pointId);
                }
                if (error && error.point) {
                    replacePointHtml(error.point);
                }
                if (!lockWasTakenOver) {
                    markPointDirty(pointId, true);
                }
                if (!lockWasTakenOver && statusNode) {
                    statusNode.textContent = error && error.message ? String(error.message) : (error && error.text ? String(error.text) : String(editorClientUi.genericError || ''));
                }
                return false;
            })
            .finally(function () {
                const currentCard = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
                if (currentCard) {
                    currentCard.removeAttribute('data-omo-pv-point-saving');
                    syncPointDirtyUi(pointId);
                }
            });
    }

    function removePointFromEditor(pointId) {
        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        const navRow = nav.querySelector('[data-omo-pv-point-nav-row="' + pointId + '"]');
        if (card) {
            card.remove();
        }
        if (navRow) {
            navRow.remove();
        }
        activeLockPointIds.delete(pointId);
        pendingLockPointIds.delete(pointId);
        pendingUnlockPointIds.delete(pointId);
        locallyEngagedPointIds.delete(pointId);
        pendingTakeoverPointIds.delete(pointId);
        preMountEditorDrafts.delete(pointId);
        preMountEditorFocusPointIds.delete(pointId);
        delete knownPointSignatures[String(pointId)];
        delete currentPointPayloads[String(pointId)];
        syncEmptyNavState();
    }

    function deletePoint(pointId, triggerButton, confirmationMessage) {
        if (!Number.isInteger(pointId) || pointId <= 0 || (triggerButton && triggerButton.disabled)) {
            return;
        }

        if (!window.confirm(confirmationMessage || deletePointMessage)) {
            return;
        }

        if (triggerButton) {
            triggerButton.disabled = true;
        }

        postPointAction('delete_point', pointId)
            .then(function (payload) {
                removePointFromEditor(pointId);
                renderPointCollection(payload && Array.isArray(payload.points) ? payload.points : [], true);
            })
            .catch(function (error) {
                if (triggerButton) {
                    triggerButton.disabled = false;
                }
                const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
                const status = card ? card.querySelector('[data-omo-pv-point-status="' + pointId + '"]') : null;
                if (status) {
                    status.textContent = error && (error.message || error.text) ? String(error.message || error.text) : String(editorClientUi.genericError || '');
                }
            });
    }

    function togglePointHandled(pointId, isHandled, input) {
        if (input) {
            input.disabled = true;
        }

        const card = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
        const saveBeforeHandling = isHandled
            && card instanceof Element
            && card.getAttribute('data-omo-pv-point-dirty') === '1';
        const toggleRequest = saveBeforeHandling
            ? savePoint(pointId).then(function (wasSaved) {
                const currentCard = root.querySelector('[data-omo-pv-point-card="' + pointId + '"]');
                if (!wasSaved || !(currentCard instanceof Element) || currentCard.getAttribute('data-omo-pv-point-dirty') === '1') {
                    throw new Error('point_save_required');
                }
                return postPointAction('toggle_handled', pointId, { is_handled: '1' });
            })
            : postPointAction('toggle_handled', pointId, { is_handled: isHandled ? '1' : '0' });

        toggleRequest
            .then(function (payload) {
                if (payload && payload.point) {
                    replacePointHtml(payload.point);
                }
            })
            .catch(function (error) {
                if (error && error.point) {
                    replacePointHtml(error.point);
                }
                if (input) {
                    input.checked = !isHandled;
                }
                renderTimingSummary();
            })
            .finally(function () {
                if (input) {
                    input.disabled = false;
                }
            });
    }

    function toggleAttendance(identityKey, isPresent, input) {
        if (!identityKey) {
            return;
        }

        if (input) {
            input.disabled = true;
        }

        postPointAction('toggle_attendance', 0, {
            identity_key: identityKey,
            is_present: isPresent ? '1' : '0'
        })
            .then(function (payload) {
                if (attendanceEnabled) {
                    renderAttendancePayload(payload && payload.attendance ? payload.attendance : null);
                }
            })
            .catch(function (payload) {
                if (input) {
                    input.checked = !isPresent;
                }
                if (attendanceEnabled && payload && payload.attendance) {
                    renderAttendancePayload(payload.attendance);
                }
            })
            .finally(function () {
                if (input instanceof HTMLInputElement && input.isConnected) {
                    input.disabled = false;
                }
            });
    }

    function persistPointOrder(layout, focusContext) {
        const formData = new FormData();
        formData.append('action', 'reorder_points');
        formData.append('document_id', String(documentId));
        formData.append('oid', String(organizationId));
        formData.append('editor_token', editorToken);
        formData.append('layout', JSON.stringify(Array.isArray(layout) ? layout : []));

        fetch(actionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                if (!payload || payload.status !== true || !Array.isArray(payload.points)) {
                    throw new Error(payload && payload.message ? payload.message : 'reorder_failed');
                }

                renderPointCollection(payload.points);
                if (focusContext && Number(focusContext.pointId || 0) > 0) {
                    focusPointMoveButton(
                        Number(focusContext.pointId || 0),
                        String(focusContext.direction || '').trim().toLowerCase()
                    );
                }
            })
            .catch(function () {
                // Keep the local order even if the save fails; the user can retry by dragging again.
            });
    }

    function applyAgendaSort() {
        if (!(sortForm instanceof HTMLFormElement) || !(sortSubmitButton instanceof HTMLButtonElement)) {
            return;
        }

        const selectedMode = sortForm.querySelector('input[name="omo_pv_sort_mode"]:checked');
        const randomizeTies = sortForm.querySelector('input[name="omo_pv_sort_randomize_ties"]');
        const handledLast = sortForm.querySelector('input[name="omo_pv_sort_handled_last"]');
        const groupByType = sortForm.querySelector('input[name="omo_pv_sort_group_by_type"]');
        const compactDisplay = sortForm.querySelector('input[name="omo_pv_sort_compact_display"]');
        const defaultLabel = String(sortSubmitButton.getAttribute('data-omo-pv-sort-label') || sortSubmitButton.textContent || '');
        const applyingLabel = String(sortSubmitButton.getAttribute('data-omo-pv-sort-applying-label') || defaultLabel);

        if (nav instanceof HTMLElement) {
            nav.classList.toggle('is-compact', compactDisplay instanceof HTMLInputElement && compactDisplay.checked);
        }
        sortSubmitButton.disabled = true;
        sortSubmitButton.textContent = applyingLabel;
        postPointAction('sort_points', 0, {
            sort_mode: selectedMode instanceof HTMLInputElement ? selectedMode.value : 'none',
            randomize_ties: randomizeTies instanceof HTMLInputElement && randomizeTies.checked ? '1' : '0',
            handled_last: handledLast instanceof HTMLInputElement && handledLast.checked ? '1' : '0',
            group_by_type: groupByType instanceof HTMLInputElement && groupByType.checked ? '1' : '0'
        })
            .then(function (payload) {
                if (!payload || !Array.isArray(payload.points)) {
                    throw new Error('sort_failed');
                }
                renderPointCollection(payload.points, true);
                if (sortMenu instanceof HTMLDetailsElement) {
                    sortMenu.open = false;
                }
            })
            .catch(function (error) {
                window.alert(String(error && (error.message || error.text) || editorClientUi.genericError || ''));
            })
            .finally(function () {
                sortSubmitButton.disabled = false;
                sortSubmitButton.textContent = defaultLabel;
            });
    }

    if (sortForm instanceof HTMLFormElement) {
        const sortStorageKey = 'omo.pv.editor.sort-options.v2';
        const restoreAgendaSortPreferences = function () {
            try {
                const stored = JSON.parse(localStorage.getItem(sortStorageKey) || '{}');
                const mode = ['none', 'priority', 'creation', 'person', 'role', 'duration'].includes(String(stored.mode || ''))
                    ? String(stored.mode)
                    : 'none';
                const modeInput = sortForm.querySelector('input[name="omo_pv_sort_mode"][value="' + mode + '"]');
                const randomizeInput = sortForm.querySelector('input[name="omo_pv_sort_randomize_ties"]');
                const handledInput = sortForm.querySelector('input[name="omo_pv_sort_handled_last"]');
                const groupInput = sortForm.querySelector('input[name="omo_pv_sort_group_by_type"]');
                const compactInput = sortForm.querySelector('input[name="omo_pv_sort_compact_display"]');
                if (modeInput instanceof HTMLInputElement) modeInput.checked = true;
                if (randomizeInput instanceof HTMLInputElement) randomizeInput.checked = stored.randomizeTies === true;
                if (handledInput instanceof HTMLInputElement) handledInput.checked = stored.handledLast === true;
                if (groupInput instanceof HTMLInputElement) groupInput.checked = stored.groupByType === true;
                if (compactInput instanceof HTMLInputElement) compactInput.checked = stored.compactDisplay === true;
            } catch (error) {
                // Ignore unavailable or malformed local browser storage.
            }
        };
        const saveAgendaSortPreferences = function () {
            try {
                const selectedMode = sortForm.querySelector('input[name="omo_pv_sort_mode"]:checked');
                const randomizeInput = sortForm.querySelector('input[name="omo_pv_sort_randomize_ties"]');
                const handledInput = sortForm.querySelector('input[name="omo_pv_sort_handled_last"]');
                const groupInput = sortForm.querySelector('input[name="omo_pv_sort_group_by_type"]');
                const compactInput = sortForm.querySelector('input[name="omo_pv_sort_compact_display"]');
                localStorage.setItem(sortStorageKey, JSON.stringify({
                    mode: selectedMode instanceof HTMLInputElement ? selectedMode.value : 'none',
                    randomizeTies: randomizeInput instanceof HTMLInputElement && randomizeInput.checked,
                    handledLast: handledInput instanceof HTMLInputElement && handledInput.checked,
                    groupByType: groupInput instanceof HTMLInputElement && groupInput.checked,
                    compactDisplay: compactInput instanceof HTMLInputElement && compactInput.checked
                }));
            } catch (error) {
                // Ignore unavailable browser storage.
            }
        };
        restoreAgendaSortPreferences();
        sortForm.querySelectorAll('input').forEach(function (input) {
            input.addEventListener('change', saveAgendaSortPreferences);
        });
        sortForm.addEventListener('submit', function (event) {
            event.preventDefault();
            saveAgendaSortPreferences();
            applyAgendaSort();
        });
    }

    function positionAgendaSortMenu() {
        if (!(sortMenu instanceof HTMLDetailsElement) || !sortMenu.open || !(sortForm instanceof HTMLElement)) {
            return;
        }
        const summary = sortMenu.querySelector('summary');
        if (!(summary instanceof HTMLElement)) {
            return;
        }

        const summaryRect = summary.getBoundingClientRect();
        const panelWidth = sortForm.offsetWidth;
        const left = Math.min(
            Math.max(12, summaryRect.left),
            Math.max(12, window.innerWidth - panelWidth - 12)
        );
        sortForm.style.left = Math.round(left) + 'px';
        sortForm.style.top = Math.round(summaryRect.bottom + 6) + 'px';
    }

    if (sortMenu instanceof HTMLDetailsElement) {
        sortMenu.addEventListener('toggle', positionAgendaSortMenu);
        window.addEventListener('resize', positionAgendaSortMenu);
        document.addEventListener('scroll', positionAgendaSortMenu, true);
    }

    function addPoint() {
        if (!addButton) {
            return;
        }

        addButton.disabled = true;

        const formData = new FormData();
        formData.append('action', 'add_point');
        formData.append('document_id', String(documentId));
        formData.append('oid', String(organizationId));
        formData.append('editor_token', editorToken);

        fetch(actionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                if (!payload || payload.status !== true || !payload.point) {
                    throw new Error(payload && payload.message ? payload.message : 'add_failed');
                }

                const nextCard = replacePointHtml(payload.point);
                if (nextCard) {
                    nextCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    const titleField = nextCard.querySelector('[data-omo-pv-point-title="' + payload.point.id + '"]');
                    if (titleField) {
                        titleField.focus();
                        titleField.select();
                    }
                }
            })
            .finally(function () {
                addButton.disabled = false;
            });
    }

    function addGroup() {
        if (!(addGroupButton instanceof HTMLButtonElement)) return;
        addGroupButton.disabled = true;
        postPointAction('add_group', 0)
            .then(function (payload) {
                if (!payload || !payload.point) throw new Error('add_group_failed');
                mergeKnownPointSignature(payload.point);
                mergeCurrentPointPayload(payload.point);
                renderNavTreeFromPayloads();
                const input = nav.querySelector('[data-omo-pv-group-title="' + Number(payload.point.id || 0) + '"]');
                if (input instanceof HTMLInputElement) {
                    input.scrollIntoView({behavior: 'smooth', block: 'nearest'});
                    input.focus();
                    input.select();
                }
            })
            .finally(function () {
                addGroupButton.disabled = false;
            });
    }

    function saveGroupTitle(input) {
        if (!(input instanceof HTMLInputElement)) return;
        const groupId = Number(input.getAttribute('data-omo-pv-group-title') || 0);
        const title = input.value.trim();
        if (groupId <= 0 || title === '') return;
        input.disabled = true;
        postPointAction('update_group', groupId, {title: title})
            .then(function (payload) {
                if (payload && payload.point) {
                    mergeKnownPointSignature(payload.point);
                    mergeCurrentPointPayload(payload.point);
                    renderNavTreeFromPayloads();
                }
            })
            .finally(function () {
                if (input.isConnected) input.disabled = false;
            });
    }

    function saveDocumentMetadata() {
        if (
            !(documentTitleInput instanceof HTMLInputElement)
            || !(documentDescriptionInput instanceof HTMLTextAreaElement)
            || !(documentVisibilitySelect instanceof Element)
            || documentMetadataSaving
            || !documentMetadataIsDirty()
        ) {
            return;
        }

        documentMetadataSaving = true;
        syncDocumentMetadataUi();
        if (documentMetaStatus instanceof Element) {
            documentMetaStatus.textContent = savingLabel;
        }

        postPointAction('update_document_metadata', 0, {
            title: documentTitleInput.value.trim(),
            description: documentDescriptionInput.value.trim(),
            visibility_type: getDocumentVisibilityValue()
            })
            .then(function (payload) {
                mergeCurrentDocumentPayload(payload && payload.document ? payload.document : {});
                if (documentMetaStatus instanceof Element) {
                    documentMetaStatus.textContent = savedLabel;
                }
            })
            .catch(function (payload) {
                if (documentMetaStatus instanceof Element) {
                    documentMetaStatus.textContent = String(payload && payload.message ? payload.message : editorClientUi.documentSaveError || '');
                }
            })
            .finally(function () {
                documentMetadataSaving = false;
                syncDocumentMetadataUi();
        });
    }

    function generateDocumentAutoSummary() {
        if (
            autoSummaryPending
            || !(documentAutoSummaryButton instanceof HTMLButtonElement)
            || !(documentDescriptionInput instanceof HTMLTextAreaElement)
            || currentDocumentPayload.pvStage !== 'review'
        ) {
            return;
        }

        autoSummaryPending = true;
        documentAutoSummaryButton.disabled = true;
        if (documentMetaStatus instanceof Element) {
            documentMetaStatus.textContent = autoSummaryLoadingLabel;
        }

        const formData = new FormData();
        formData.append('oid', String(organizationId));
        formData.append('document_id', String(documentId));
        fetch('/omo/api/documents/pv/summarize.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: formData
        })
            .then(function (response) {
                return response.json().catch(function () { return null; }).then(function (payload) {
                    if (!response.ok || !payload || payload.status !== true) {
                        throw new Error(payload && payload.message ? payload.message : String(editorClientUi.autoSummaryError || ''));
                    }
                    return payload;
                });
            })
            .then(function (payload) {
                const summary = String(payload.text || '').trim();
                if (summary === '') {
                    throw new Error(String(editorClientUi.autoSummaryEmpty || ''));
                }
                documentDescriptionInput.value = summary;
                resizeDocumentDescriptionInput();
                markDocumentMetadataDirty();
                if (documentMetaStatus instanceof Element) {
                    documentMetaStatus.textContent = autoSummaryReadyLabel;
                }
            })
            .catch(function (error) {
                if (documentMetaStatus instanceof Element) {
                    documentMetaStatus.textContent = error && error.message ? error.message : String(editorClientUi.autoSummaryError || '');
                }
            })
            .finally(function () {
                autoSummaryPending = false;
                syncDocumentMetadataUi();
            });
    }

    function openValidatedPvViewer() {
        const routeToken = 'documents-d' + String(documentId);
        releaseActiveLocksWithBeacon();

        if (typeof window.omoCloseExternalPanelDrawer === 'function') {
            window.omoCloseExternalPanelDrawer({
                force: true,
                forceReset: true,
                skipCloseGuard: true
            });
        }

        window.setTimeout(function () {
            if (typeof window.omoOpenDrawerHashState === 'function') {
                window.omoOpenDrawerHashState(routeToken);
                return;
            }

            window.location.hash = routeToken;
        }, 210);
    }

    root.addEventListener('click', function (event) {
        if (!event.target.closest('[data-omo-deferred-proposal-menu]')) {
            closeDeferredProposalMenus(null);
        }
        const templateToggle = event.target.closest('[data-omo-pv-template-toggle]');
        if (templateToggle instanceof HTMLButtonElement && root.contains(templateToggle)) {
            event.preventDefault();
            const nextState = templateToggle.dataset.omoPvTemplateState === '1' ? 0 : 1;
            templateToggle.disabled = true;
            postPointAction('set_pv_template', 0, { is_template: nextState })
                .then(function (payload) {
                    mergeCurrentDocumentPayload(payload && payload.document ? payload.document : {});
                    const menu = templateToggle.closest('[data-omo-pv-more-actions]');
                    if (menu instanceof HTMLDetailsElement) {
                        menu.open = false;
                    }
                })
                .finally(function () {
                    templateToggle.disabled = currentDocumentPayload.canManagePvTemplate !== true;
                });
            return;
        }

        const documentMetaSave = event.target.closest('[data-omo-pv-document-meta-save]');
        if (documentMetaSave && root.contains(documentMetaSave)) {
            event.preventDefault();
            saveDocumentMetadata();
            return;
        }

        const documentAutoSummary = event.target.closest('[data-omo-pv-document-auto-summary]');
        if (documentAutoSummary && root.contains(documentAutoSummary)) {
            event.preventDefault();
            generateDocumentAutoSummary();
            return;
        }

        const stageButton = event.target.closest('[data-omo-pv-stage-option]');
        if (stageButton instanceof HTMLButtonElement && root.contains(stageButton) && !stageButton.disabled) {
            const previousStage = String(currentDocumentPayload && currentDocumentPayload.pvStage ? currentDocumentPayload.pvStage : '').trim();
            const nextStage = String(stageButton.getAttribute('data-omo-pv-stage-value') || '').trim();
            if (nextStage === '' || nextStage === previousStage) {
                return;
            }

            if (nextStage === 'review' && !window.confirm(reviewIrreversibleMessage)) {
                return;
            }

            if (nextStage === 'validated') {
                if (hasUnsavedPointChanges() && !window.confirm(unsavedCloseMessage)) {
                    return;
                }

                if (!window.confirm(validateIrreversibleMessage)) {
                    return;
                }
            }

            stageButtons.forEach(function (button) {
                button.disabled = true;
            });

            postPointAction('update_stage', 0, { pv_stage: nextStage })
                .then(function (payload) {
                    const nextDocumentPayload = payload && payload.document ? payload.document : { pvStage: nextStage };
                    mergeCurrentDocumentPayload(nextDocumentPayload);
                    if (nextStage === 'review') {
                        if (!refreshPvEditorAfterReview()) {
                            window.location.reload();
                        }
                        return;
                    }
                    if (nextStage === 'validated' && currentDocumentPayload.isPvValidated === true) {
                        openValidatedPvViewer();
                    }
                })
                .finally(function () {
                    syncDocumentStageUi(currentDocumentPayload);
                });
            return;
        }

        const typeButton = event.target.closest('[data-omo-pv-point-type-option]');
        if (typeButton instanceof HTMLButtonElement && root.contains(typeButton)) {
            event.preventDefault();
            event.stopPropagation();
            selectPointTypeOption(typeButton, false);
            return;
        }

        const priorityButton = event.target.closest('[data-omo-pv-point-priority-option]');
        if (priorityButton instanceof HTMLButtonElement && root.contains(priorityButton)) {
            event.preventDefault();
            event.stopPropagation();
            selectPointPriorityOption(priorityButton);
            return;
        }

        const moveButton = event.target.closest('[data-omo-pv-point-move]');
        if (moveButton && root.contains(moveButton)) {
            event.preventDefault();
            event.stopPropagation();

            const pointId = Number(moveButton.getAttribute('data-omo-pv-point-move') || 0);
            const direction = String(moveButton.getAttribute('data-omo-pv-point-move-direction') || '').trim().toLowerCase();
            if (pointId > 0 && (direction === 'up' || direction === 'down')) {
                movePointByDirection(pointId, direction, direction);
            }
            return;
        }

        const deleteButton = event.target.closest('[data-omo-pv-point-delete]');
        if (deleteButton && root.contains(deleteButton)) {
            event.preventDefault();
            event.stopPropagation();
            const pointId = Number(deleteButton.getAttribute('data-omo-pv-point-delete') || 0);
            if (pointId > 0) {
                deletePoint(pointId, deleteButton);
            }
            return;
        }

        const navButton = event.target.closest('[data-omo-pv-point-nav-target]');
        if (navButton && root.contains(navButton)) {
            const pointId = Number(navButton.getAttribute('data-omo-pv-point-nav-target') || 0);
            const target = pointId > 0 ? root.querySelector('#omo-pv-editor-point-' + pointId) : null;
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return;
        }

        const takeOverLockButton = event.target.closest('[data-omo-pv-point-take-over-lock]');
        if (takeOverLockButton && root.contains(takeOverLockButton)) {
            event.preventDefault();
            const pointId = Number(takeOverLockButton.getAttribute('data-omo-pv-point-take-over-lock') || 0);
            if (pointId > 0) {
                takeOverPointLock(pointId, takeOverLockButton);
            }
            return;
        }

        const saveButton = event.target.closest('[data-omo-pv-point-save]');
        if (saveButton && root.contains(saveButton)) {
            const pointId = Number(saveButton.getAttribute('data-omo-pv-point-save') || 0);
            if (pointId > 0) {
                savePoint(pointId);
            }
            return;
        }

        const proposalMenuToggle = event.target.closest('[data-omo-deferred-proposal-menu-toggle]');
        if (proposalMenuToggle && root.contains(proposalMenuToggle)) {
            event.preventDefault();
            event.stopPropagation();
            const menu = proposalMenuToggle.closest('[data-omo-deferred-proposal-menu]');
            const panel = menu ? menu.querySelector('[data-omo-deferred-proposal-menu-panel]') : null;
            const willOpen = panel ? panel.hidden : false;
            closeDeferredProposalMenus(willOpen ? menu : null);
            if (menu && panel) {
                panel.hidden = !willOpen;
                menu.classList.toggle('is-open', willOpen);
                proposalMenuToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            }
            return;
        }

        const proposalEditButton = event.target.closest('[data-omo-deferred-proposal-edit]');
        if (proposalEditButton && root.contains(proposalEditButton)) {
            event.preventDefault();
            event.stopPropagation();
            const url = String(proposalEditButton.getAttribute('data-omo-deferred-proposal-edit-url') || '');
            const modalTitle = String(proposalEditButton.getAttribute('data-omo-deferred-proposal-edit-title') || 'Éditer une modification');
            closeDeferredProposalMenus(null);
            if (url !== '' && typeof window.commonTopbarOpenModal === 'function') {
                window.commonTopbarOpenModal(modalTitle, url, 'fetch');
            }
            return;
        }

        const proposalDeleteButton = event.target.closest('[data-omo-deferred-proposal-delete]');
        if (proposalDeleteButton && root.contains(proposalDeleteButton)) {
            event.preventDefault();
            event.stopPropagation();
            const proposalId = Number(proposalDeleteButton.getAttribute('data-omo-deferred-proposal-delete') || 0);
            const pointId = Number(proposalDeleteButton.getAttribute('data-omo-deferred-proposal-point-id') || 0);
            const confirmation = String(proposalDeleteButton.getAttribute('data-omo-deferred-proposal-delete-confirm') || 'Supprimer cette modification ?');
            if (proposalId <= 0 || pointId <= 0 || !window.confirm(confirmation)) return;
            proposalDeleteButton.disabled = true;
            closeDeferredProposalMenus(null);
            postPointAction('remove_deferred_proposal', pointId, { proposal_id: proposalId })
                .then(function (payload) {
                    if (payload && payload.point) replacePointHtml(payload.point);
                })
                .catch(function (payload) {
                    window.alert(String(payload && payload.message || 'Impossible de supprimer la modification.'));
                    if (proposalDeleteButton.isConnected) proposalDeleteButton.disabled = false;
                });
            return;
        }

        const proposalToggle = event.target.closest('[data-omo-deferred-proposal-toggle]');
        if (proposalToggle && root.contains(proposalToggle)) {
            event.preventDefault();
            const detailId = String(proposalToggle.getAttribute('aria-controls') || '');
            const detail = detailId !== '' ? document.getElementById(detailId) : null;
            if (detail instanceof Element) {
                const willOpen = detail.hidden;
                detail.hidden = !willOpen;
                proposalToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                if (willOpen) hydrateDeferredProposalDetails(detail.parentElement || detail);
            }
            return;
        }

        const addProposalButton = event.target.closest('[data-omo-pv-point-add-proposal]');
        if (addProposalButton && root.contains(addProposalButton)) {
            event.preventDefault();
            const url = String(addProposalButton.getAttribute('data-omo-pv-point-proposal-url') || '');
            const modalTitle = String(addProposalButton.getAttribute('data-omo-pv-point-proposal-title') || 'Ajouter une modification');
            if (url !== '' && typeof window.commonTopbarOpenModal === 'function') {
                window.commonTopbarOpenModal(modalTitle, url, 'fetch');
            }
            return;
        }

        const addPointButton = event.target.closest('[data-omo-pv-editor-add-point]');
        if (addPointButton && root.contains(addPointButton)) {
            addPoint();
            return;
        }

        const addGroupTrigger = event.target.closest('[data-omo-pv-editor-add-group]');
        if (addGroupTrigger && root.contains(addGroupTrigger)) {
            addGroup();
            return;
        }

        const groupToggle = event.target.closest('[data-omo-pv-group-toggle]');
        if (groupToggle && nav.contains(groupToggle)) {
            const groupNode = groupToggle.closest('[data-omo-pv-group]');
            if (groupNode) {
                const collapsed = groupNode.classList.toggle('is-collapsed');
                groupToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            }
            return;
        }

        const groupTitleSaveButton = event.target.closest('[data-omo-pv-group-title-save]');
        if (groupTitleSaveButton && nav.contains(groupTitleSaveButton)) {
            const groupId = Number(groupTitleSaveButton.getAttribute('data-omo-pv-group-title-save') || 0);
            const groupTitleInput = groupId > 0 ? nav.querySelector('[data-omo-pv-group-title="' + groupId + '"]') : null;
            if (groupTitleInput instanceof HTMLInputElement) {
                saveGroupTitle(groupTitleInput);
            }
            return;
        }

        const claimSecretaryButton = event.target.closest('[data-omo-pv-claim-secretary]');
        if (claimSecretaryButton && root.contains(claimSecretaryButton)) {
            const secretaryAction = String(claimSecretaryButton.dataset.omoPvSecretaryAction || 'claim_pv_editor');
            if (secretaryAction === 'pass_pv_editor' && hasUnsavedPointChanges()) {
                window.alert(unsavedHandoverMessage);
                return;
            }
            claimSecretaryButton.disabled = true;
            postPointAction(secretaryAction, 0, {})
                .then(function (payload) {
                    mergeCurrentDocumentPayload(payload && payload.document ? payload.document : {});
                    if (payload && Array.isArray(payload.points)) {
                        renderPointCollection(payload.points, true);
                    }
                })
                .finally(function () {
                    syncPvEditorUi(currentDocumentPayload);
                });
        }
    });

    root.addEventListener('change', function (event) {
        const attendanceInput = event.target.closest('[data-omo-pv-attendance-toggle]');
        if (attendanceInput && root.contains(attendanceInput)) {
            toggleAttendance(
                String(attendanceInput.getAttribute('data-omo-pv-attendance-toggle') || '').trim(),
                !!attendanceInput.checked,
                attendanceInput
            );
            return;
        }

        const handledInput = event.target.closest('[data-omo-pv-point-handled]');
        if (!handledInput || !root.contains(handledInput)) {
            return;
        }

        const pointId = Number(handledInput.getAttribute('data-omo-pv-point-handled') || 0);
        if (pointId > 0) {
            renderTimingSummary();
            togglePointHandled(pointId, !!handledInput.checked, handledInput);
        }
    });

    root.addEventListener('keydown', function (event) {
        const prioritySummary = event.target.closest('[data-omo-pv-point-priority-menu] > summary');
        if (prioritySummary instanceof HTMLElement && root.contains(prioritySummary)) {
            if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') {
                return;
            }

            const priorityMenu = prioritySummary.parentElement;
            const pointId = priorityMenu instanceof HTMLElement
                ? Number(priorityMenu.getAttribute('data-omo-pv-point-priority-menu') || 0)
                : 0;
            const card = pointId > 0 ? root.querySelector('[data-omo-pv-point-card="' + pointId + '"]') : null;
            const priorityField = card ? card.querySelector('[data-omo-pv-point-priority="' + pointId + '"]') : null;
            if (!(card instanceof Element) || !(priorityField instanceof HTMLInputElement)) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            const currentPriority = Math.max(1, Math.min(5, Number(priorityField.value || 3) || 3));
            const nextPriority = Math.max(1, Math.min(5, currentPriority + (event.key === 'ArrowDown' ? 1 : -1)));
            if (nextPriority === currentPriority) {
                return;
            }
            const nextButton = card.querySelector('[data-omo-pv-point-priority-option="' + pointId + '"][data-omo-pv-point-priority-value="' + nextPriority + '"]');
            if (nextButton instanceof HTMLButtonElement) {
                selectPointPriorityOption(nextButton);
            }
            return;
        }

        const moveButton = event.target.closest('[data-omo-pv-point-move]');
        if (moveButton instanceof HTMLButtonElement && root.contains(moveButton)) {
            if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') {
                return;
            }

            const pointId = Number(moveButton.getAttribute('data-omo-pv-point-move') || 0);
            if (pointId <= 0) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            const direction = event.key === 'ArrowUp' ? 'up' : 'down';
            const focusDirection = String(moveButton.getAttribute('data-omo-pv-point-move-direction') || '').trim().toLowerCase();
            movePointByDirection(pointId, direction, focusDirection);
            return;
        }

        const typeButton = event.target.closest('[data-omo-pv-point-type-option]');
        if (!(typeButton instanceof HTMLButtonElement) || !root.contains(typeButton)) {
            return;
        }

        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
            return;
        }

        const pointId = Number(typeButton.getAttribute('data-omo-pv-point-type-option') || 0);
        const card = pointId > 0 ? root.querySelector('[data-omo-pv-point-card="' + pointId + '"]') : null;
        const buttons = card instanceof Element
            ? Array.from(card.querySelectorAll('[data-omo-pv-point-type-option="' + pointId + '"]'))
            : [];
        const currentIndex = buttons.indexOf(typeButton);
        if (currentIndex < 0 || buttons.length < 2) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        const direction = event.key === 'ArrowRight' ? 1 : -1;
        const nextIndex = (currentIndex + direction + buttons.length) % buttons.length;
        selectPointTypeOption(buttons[nextIndex], true);
    });

    (function initPointReorder() {
        if (!nav) {
            return;
        }

        let draggedPointId = 0;
        let dropIntent = null;
        const dropInsideLabel = pageConfig.dropInsideLabel;
        const dropIndicator = document.createElement('div');
        dropIndicator.className = 'omo-pv-editor__drop-indicator';
        dropIndicator.setAttribute('aria-hidden', 'true');

        const showDropIndicator = function (rect, mode) {
            if (!(rect instanceof DOMRect) && (!rect || typeof rect.left !== 'number')) return;
            if (!dropIndicator.isConnected) document.body.appendChild(dropIndicator);

            if (mode === 'inside') {
                dropIndicator.className = 'omo-pv-editor__drop-indicator omo-pv-editor__drop-indicator--inside';
                dropIndicator.textContent = String(dropInsideLabel || '');
                dropIndicator.style.left = Math.round(rect.left) + 'px';
                dropIndicator.style.top = Math.round(rect.top) + 'px';
                dropIndicator.style.width = Math.max(24, Math.round(rect.width)) + 'px';
                dropIndicator.style.height = Math.max(38, Math.round(rect.height)) + 'px';
                return;
            }

            dropIndicator.className = 'omo-pv-editor__drop-indicator omo-pv-editor__drop-indicator--line';
            dropIndicator.textContent = '';
            dropIndicator.style.left = Math.round(rect.left + 8) + 'px';
            dropIndicator.style.top = Math.round((mode === 'after' ? rect.bottom : rect.top) - 3) + 'px';
            dropIndicator.style.width = Math.max(24, Math.round(rect.width - 16)) + 'px';
            dropIndicator.style.height = '6px';
        };

        const clearDragState = function () {
            nav.querySelectorAll('.is-drop-before, .is-drop-after, .is-drop-inside, .is-dragging').forEach(function (node) {
                node.classList.remove('is-drop-before', 'is-drop-after', 'is-drop-inside', 'is-dragging');
            });
            if (dropIndicator.isConnected) dropIndicator.remove();
            if (deleteDropzone) {
                deleteDropzone.classList.remove('is-visible', 'is-active');
            }
            dropIntent = null;
        };

        nav.addEventListener('dragstart', function (event) {
            const handle = event.target.closest('[data-omo-pv-point-drag-handle]');
            if (!handle) {
                event.preventDefault();
                return;
            }

            draggedPointId = Number(handle.getAttribute('data-omo-pv-point-drag-handle') || 0);
            const row = draggedPointId > 0 ? nav.querySelector('[data-omo-pv-nav-node="' + draggedPointId + '"]') : null;
            if (!(row instanceof Element)) {
                draggedPointId = 0;
                event.preventDefault();
                return;
            }

            row.classList.add('is-dragging');
            if (deleteDropzone && row.getAttribute('data-omo-pv-can-delete') === '1') {
                deleteDropzone.classList.add('is-visible');
            }
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', String(draggedPointId));
            }
        });

        nav.addEventListener('dragover', function (event) {
            if (!draggedPointId) {
                return;
            }

            const sourceNode = nav.querySelector('[data-omo-pv-nav-node="' + draggedPointId + '"]');
            const targetNode = event.target.closest('[data-omo-pv-nav-node]');
            if (!(sourceNode instanceof Element)) return;

            if (!targetNode) {
                const targetContainer = event.target.closest('[data-omo-pv-nav-children]');
                if (!(targetContainer instanceof Element) || sourceNode.contains(targetContainer)) return;
                event.preventDefault();
                nav.querySelectorAll('.is-drop-before, .is-drop-after, .is-drop-inside').forEach(function (node) {
                    node.classList.remove('is-drop-before', 'is-drop-after', 'is-drop-inside');
                });
                dropIntent = { mode: 'inside', container: targetContainer };
                const containerGroup = targetContainer.closest('[data-omo-pv-group]');
                if (containerGroup instanceof Element) {
                    containerGroup.classList.add('is-drop-inside');
                    const groupHead = containerGroup.querySelector(':scope > .omo-pv-editor__group-head');
                    if (groupHead instanceof Element) showDropIndicator(groupHead.getBoundingClientRect(), 'inside');
                } else {
                    const lastNode = targetContainer.querySelector(':scope > [data-omo-pv-nav-node]:last-child');
                    showDropIndicator((lastNode || targetContainer).getBoundingClientRect(), 'after');
                }
                if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
                return;
            }

            if (targetNode === sourceNode || sourceNode.contains(targetNode)) return;

            event.preventDefault();
            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'move';
            }
            nav.querySelectorAll('.is-drop-before, .is-drop-after, .is-drop-inside').forEach(function (node) {
                node.classList.remove('is-drop-before', 'is-drop-after', 'is-drop-inside');
            });

            const isGroup = targetNode.matches('[data-omo-pv-group]');
            const targetHitbox = isGroup
                ? targetNode.querySelector(':scope > .omo-pv-editor__group-head')
                : targetNode;
            const targetRect = (targetHitbox || targetNode).getBoundingClientRect();
            const relativeY = Math.max(0, Math.min(targetRect.height, event.clientY - targetRect.top));
            const dropMode = isGroup && relativeY >= targetRect.height * 0.28 && relativeY <= targetRect.height * 0.72
                ? 'inside'
                : (relativeY > targetRect.height / 2 ? 'after' : 'before');
            targetNode.classList.add(dropMode === 'inside' ? 'is-drop-inside' : (dropMode === 'after' ? 'is-drop-after' : 'is-drop-before'));
            showDropIndicator(targetRect, dropMode);
            if (dropMode === 'inside') {
                targetNode.classList.remove('is-collapsed');
                const toggle = targetNode.querySelector(':scope > .omo-pv-editor__group-head [data-omo-pv-group-toggle]');
                if (toggle) toggle.setAttribute('aria-expanded', 'true');
                const childContainer = targetNode.querySelector(':scope > [data-omo-pv-nav-children]');
                if (!(childContainer instanceof Element)) return;
                dropIntent = { mode: 'inside', container: childContainer };
            } else {
                dropIntent = { mode: dropMode, reference: targetNode };
            }
        });

        nav.addEventListener('drop', function (event) {
            const sourceRow = draggedPointId > 0 ? nav.querySelector('[data-omo-pv-nav-node="' + draggedPointId + '"]') : null;
            if (!(sourceRow instanceof Element) || !dropIntent) {
                clearDragState();
                draggedPointId = 0;
                return;
            }

            event.preventDefault();
            if (dropIntent.mode === 'inside' && dropIntent.container instanceof Element) {
                dropIntent.container.appendChild(sourceRow);
            } else if (dropIntent.reference instanceof Element) {
                if (dropIntent.mode === 'after') {
                    dropIntent.reference.insertAdjacentElement('afterend', sourceRow);
                } else {
                    dropIntent.reference.insertAdjacentElement('beforebegin', sourceRow);
                }
            } else {
                clearDragState();
                draggedPointId = 0;
                return;
            }

            const layout = collectNavLayout();
            applyPointOrderToCards(layout.filter(function (item) {
                const payload = currentPointPayloads[String(item.id)] || {};
                return payload.isGroup !== true;
            }).map(function (item) { return item.id; }));
            persistPointOrder(layout);
            clearDragState();
            draggedPointId = 0;
        });

        if (deleteDropzone) {
            deleteDropzone.addEventListener('dragenter', function (event) {
                if (!draggedPointId) {
                    return;
                }
                const sourceRow = nav.querySelector('[data-omo-pv-nav-node="' + draggedPointId + '"]');
                if (!(sourceRow instanceof Element) || sourceRow.getAttribute('data-omo-pv-can-delete') !== '1') {
                    return;
                }
                event.preventDefault();
                deleteDropzone.classList.add('is-active');
            });

            deleteDropzone.addEventListener('dragover', function (event) {
                if (!draggedPointId) {
                    return;
                }
                const sourceRow = nav.querySelector('[data-omo-pv-nav-node="' + draggedPointId + '"]');
                if (!(sourceRow instanceof Element) || sourceRow.getAttribute('data-omo-pv-can-delete') !== '1') {
                    return;
                }
                event.preventDefault();
                deleteDropzone.classList.add('is-active');
                if (event.dataTransfer) {
                    event.dataTransfer.dropEffect = 'move';
                }
            });

            deleteDropzone.addEventListener('dragleave', function (event) {
                if (event.relatedTarget instanceof Node && deleteDropzone.contains(event.relatedTarget)) {
                    return;
                }
                deleteDropzone.classList.remove('is-active');
            });

            deleteDropzone.addEventListener('drop', function (event) {
                const pointId = draggedPointId;
                const sourceRow = pointId > 0 ? nav.querySelector('[data-omo-pv-nav-node="' + pointId + '"]') : null;
                if (!(sourceRow instanceof Element) || sourceRow.getAttribute('data-omo-pv-can-delete') !== '1') {
                    clearDragState();
                    draggedPointId = 0;
                    return;
                }
                event.preventDefault();
                clearDragState();
                draggedPointId = 0;
                deletePoint(pointId, null, deleteItemMessage);
            });
        }

        nav.addEventListener('dragend', function () {
            clearDragState();
            draggedPointId = 0;
        });

        nav.addEventListener('dragleave', function (event) {
            const relatedTarget = event.relatedTarget;
            if (relatedTarget instanceof Node && nav.contains(relatedTarget)) {
                return;
            }

            nav.querySelectorAll('.is-drop-before, .is-drop-after, .is-drop-inside').forEach(function (node) {
                node.classList.remove('is-drop-before', 'is-drop-after', 'is-drop-inside');
            });
            if (dropIndicator.isConnected) dropIndicator.remove();
            dropIntent = null;
        });
    })();

    function isPvEditorSessionActive() {
        if (!root.isConnected || document.hidden) {
            return false;
        }

        const externalDrawer = root.closest('[data-omo-external-panel-drawer="1"]');
        if (externalDrawer instanceof HTMLElement) {
            return !externalDrawer.hidden && externalDrawer.classList.contains('is-open');
        }

        return root.getClientRects().length > 0;
    }

    function isPvEditorPollingActive() {
        if (!isPvEditorSessionActive()) {
            return false;
        }

        const externalDrawer = root.closest('[data-omo-external-panel-drawer="1"]');
        return !(externalDrawer instanceof HTMLElement && externalDrawer.classList.contains('is-peek'));
    }

    function getNextSyncPollDelay() {
        return String(currentDocumentPayload.pvStage || '') === 'meeting'
            || activeLockPointIds.size > 0
            || hasUnsavedPointChanges()
            ? activeSyncPollDelayMs
            : idleSyncPollDelayMs;
    }

    function scheduleNextSyncPoll(delayMs) {
        if (syncPollTimer !== null) {
            window.clearTimeout(syncPollTimer);
            syncPollTimer = null;
        }
        if (currentDocumentPayload.isPvValidated === true || !root.isConnected) {
            return;
        }

        const nextDelay = Number.isFinite(delayMs) ? Math.max(0, delayMs) : getNextSyncPollDelay();
        syncPollTimer = window.setTimeout(function () {
            syncPollTimer = null;
            renderTimingSummary();
            if (!root.isConnected) {
                stopPvEditorBackgroundWork();
                return;
            }
            if (!isPvEditorPollingActive()) {
                scheduleNextSyncPoll(idleSyncPollDelayMs);
                return;
            }

            syncEditorFromServer().then(function () {
                scheduleNextSyncPoll();
            });
        }, nextDelay);
    }

    function startSyncPolling() {
        scheduleNextSyncPoll(activeSyncPollDelayMs);
    }

    function startEditorLifecycleObserver() {
        const externalDrawer = root.closest('[data-omo-external-panel-drawer="1"]');
        editorWasPollingActive = isPvEditorPollingActive();
        if (!(externalDrawer instanceof HTMLElement) || typeof MutationObserver !== 'function') {
            return;
        }

        editorLifecycleObserver = new MutationObserver(function () {
            const pollingActive = isPvEditorPollingActive();
            if (pollingActive && !editorWasPollingActive) {
                scheduleNextSyncPoll(0);
            }
            editorWasPollingActive = pollingActive;
        });
        editorLifecycleObserver.observe(externalDrawer, {
            attributes: true,
            attributeFilter: ['class', 'hidden']
        });
    }

    function syncEditorFromServer() {
        if (currentDocumentPayload.isPvValidated === true) {
            return Promise.resolve(null);
        }
        if (syncPollPending instanceof Promise) {
            return syncPollPending;
        }

        syncPollPending = postPointAction('poll_updates', 0, {
            poll_revision: knownPollingRevision
        })
            .then(function (payload) {
                if (payload && payload.pollRevision) {
                    knownPollingRevision = String(payload.pollRevision);
                }
                if (payload && payload.unchanged === true) {
                    return payload;
                }

                const remoteDocumentPayload = payload && payload.document ? payload.document : null;
                if (documentPayloadHasRemoteChanges(remoteDocumentPayload)) {
                    mergeCurrentDocumentPayload(remoteDocumentPayload);
                }
                if (attendanceEnabled) {
                    renderAttendancePayload(payload && payload.attendance ? payload.attendance : null);
                }
                if (payload && Array.isArray(payload.points) && pointCollectionHasRemoteChanges(payload.points)) {
                    return processIncomingTakeoverRequests(payload.points).then(function (pointPayloads) {
                        renderPointCollection(pointPayloads);
                        return payload;
                    });
                }
                return payload;
            })
            .catch(function () {
                // Silent polling failure: keep the editor usable locally.
                return null;
            })
            .finally(function () {
                syncPollPending = null;
            });
        return syncPollPending;
    }

    function startLockHeartbeat() {
        if (lockHeartbeatTimer !== null) {
            window.clearInterval(lockHeartbeatTimer);
        }
        if (currentDocumentPayload.isPvValidated === true) {
            lockHeartbeatTimer = null;
            return;
        }

        lockHeartbeatTimer = window.setInterval(function () {
            if (currentDocumentPayload.isPvValidated === true || !isPvEditorSessionActive() || activeLockPointIds.size === 0) {
                return;
            }

            const pointIds = Array.from(activeLockPointIds).filter(function (pointId) {
                return Number.isInteger(pointId) && pointId > 0;
            });
            if (pointIds.length === 0) {
                return;
            }

            postPointAction('heartbeat_locks', 0, {
                point_ids: pointIds.join(',')
            }).catch(function () {
                // Polling will reconcile visible lock state if needed.
            });
        }, 30000);
    }

    window.addEventListener('beforeunload', function (event) {
        if (hasUnsavedPointChanges()) {
            event.preventDefault();
            event.returnValue = '';
            return '';
        }

        releaseActiveLocksWithBeacon();
    });

    window.addEventListener('pagehide', function (event) {
        if (!event.persisted) {
            releaseActiveLocksWithBeacon();
        }
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && isPvEditorPollingActive()) {
            scheduleNextSyncPoll(0);
        }
    });

    if (root.querySelector('.omo-simple-html-render')) {
        ensureHtmlFieldLibrary(function () {});
    }

    mountEditableCards(root);
    [documentTitleInput, documentDescriptionInput].forEach(function (input) {
        if (!(input instanceof Element)) {
            return;
        }
        input.addEventListener('input', function () {
            resizeDocumentDescriptionInput();
            markDocumentMetadataDirty();
        });
    });
    if (documentVisibilitySelect instanceof Element) {
        documentVisibilitySelect.addEventListener('change', function () {
            markDocumentMetadataDirty();
        });
    }
    function closeEventExtensionMenu() {
        if (eventExtensionPanel instanceof HTMLElement) {
            eventExtensionPanel.hidden = true;
        }
        if (eventExtensionToggle instanceof HTMLButtonElement) {
            eventExtensionToggle.setAttribute('aria-expanded', 'false');
        }
    }
    if (eventExtensionToggle instanceof HTMLButtonElement && eventExtensionPanel instanceof HTMLElement) {
        eventExtensionToggle.addEventListener('click', function () {
            const willOpen = eventExtensionPanel.hidden;
            eventExtensionPanel.hidden = !willOpen;
            eventExtensionToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
        document.addEventListener('pointerdown', function (event) {
            if (eventExtensionMenu instanceof HTMLElement && !eventExtensionMenu.contains(event.target)) {
                closeEventExtensionMenu();
            }
        }, true);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeEventExtensionMenu();
            }
        });
        eventExtensionPanel.querySelectorAll('[data-omo-pv-event-extension-minutes]').forEach(function (button) {
            button.addEventListener('click', function () {
                const minutes = Number(button.getAttribute('data-omo-pv-event-extension-minutes') || 0);
                if (![5, 10, 15, 30].includes(minutes)) {
                    return;
                }

                const menuButtons = Array.from(eventExtensionPanel.querySelectorAll('[data-omo-pv-event-extension-minutes]'));
                const originalLabel = button.textContent;
                menuButtons.forEach(function (menuButton) { menuButton.disabled = true; });
                button.textContent = editorClientUi.eventExtensionSaving || originalLabel;
                closeEventExtensionMenu();

                postPointAction('extend_associated_event', 0, {minutes: minutes})
                    .then(function (payload) {
                        applyAssociatedEventSchedule(payload && payload.event ? payload.event : null);
                        if (payload && payload.document) {
                            mergeCurrentDocumentPayload(payload.document);
                        }
                        renderTimingSummary();
                    })
                    .catch(function (error) {
                        if (window.alert) {
                            window.alert(String(error && error.message || editorClientUi.genericError || ''));
                        }
                    })
                    .finally(function () {
                        button.textContent = originalLabel;
                        menuButtons.forEach(function (menuButton) { menuButton.disabled = false; });
                    });
            });
        });
    }
    if (invitationsButton instanceof HTMLButtonElement) {
        invitationsButton.addEventListener('click', function () {
            const invitationUrl = String(invitationsButton.getAttribute('data-omo-pv-invitations-url') || '').trim();
            const invitationTitle = String(invitationsButton.getAttribute('data-omo-pv-invitations-title') || '').trim();
            if (invitationUrl && typeof window.commonTopbarOpenModal === 'function') {
                window.commonTopbarOpenModal(invitationTitle || 'Invités', invitationUrl, 'fetch');
            }
        });
    }
    function closeInvitationsMenu() {
        if (invitationsMenuPanel instanceof HTMLElement) {
            invitationsMenuPanel.hidden = true;
        }
        if (invitationsMenuToggle instanceof HTMLButtonElement) {
            invitationsMenuToggle.setAttribute('aria-expanded', 'false');
        }
    }
    if (invitationsMenuToggle instanceof HTMLButtonElement && invitationsMenuPanel instanceof HTMLElement) {
        invitationsMenuToggle.addEventListener('click', function () {
            const willOpen = invitationsMenuPanel.hidden;
            invitationsMenuPanel.hidden = !willOpen;
            invitationsMenuToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
        document.addEventListener('pointerdown', function (event) {
            if (invitationsMenu instanceof HTMLElement && !invitationsMenu.contains(event.target)) {
                closeInvitationsMenu();
            }
        }, true);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeInvitationsMenu();
            }
        });
    }
    if (invitationsSendButton instanceof HTMLButtonElement) {
        invitationsSendButton.addEventListener('click', function () {
            const invitationUrl = String(invitationsSendButton.getAttribute('data-omo-pv-invitations-send-url') || '').trim();
            const invitationTitle = String(invitationsSendButton.getAttribute('data-omo-pv-invitations-send-title') || '').trim();
            closeInvitationsMenu();
            if (invitationUrl && typeof window.commonTopbarOpenModal === 'function') {
                window.commonTopbarOpenModal(invitationTitle || 'Envoyer les invitations', invitationUrl, 'fetch');
            }
        });
    }
    window.addEventListener('omo:pv-invitations-updated', function (event) {
        const updatedDocumentId = Number(event && event.detail ? event.detail.documentId : 0);
        if (updatedDocumentId === documentId) {
            syncEditorFromServer();
        }
    });
    resizeDocumentDescriptionInput();
    syncEmptyNavState();
    mergeCurrentDocumentPayload(initialDocumentPayload);
    if (attendanceEnabled) {
        renderAttendancePayload(initialAttendancePayload);
    }
    updateKnownPointSignatures(initialPointPayloads);
    syncCurrentPointPayloads(initialPointPayloads);
    renderNavTreeFromPayloads();
    applyPointOrderToCards(getOrderedPointIdsFromPayloads());
    renderTimingSummary();
    startEditorLifecycleObserver();
    startSyncPolling();
    startLockHeartbeat();
})();
};
