var omoUiText = window.omoUiText || function (key, fallback) { return fallback; };
var omoUiHtml = window.omoUiHtml || function (key, fallback) { return fallback; };
window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/documents/create.js"] = function (pageConfig, pageScript) {
(function () {
    const form = document.getElementById(pageConfig.documentFormId);
    if (!form || form.dataset.omoDocumentCreateReady === '1') {
        return;
    }

    form.dataset.omoDocumentCreateReady = '1';

    const editorHost = String(form.getAttribute('data-omo-document-editor-host') || 'documents');

    const htmlHost = form.querySelector('[data-omo-document-editor-html]');
    const statusNode = form.querySelector('[data-omo-document-editor-status]');
    const dictationStatusNode = form.querySelector('[data-omo-document-dictation-status]');
    const cancelButton = form.querySelector('[data-omo-document-editor-cancel]')
        || document.querySelector('[data-omo-document-editor-cancel][form="' + form.id + '"]');
    const typeSelect = form.querySelector('[data-omo-document-type]');
    const tagsEditor = form.querySelector('[data-omo-document-tags-editor]');
    const tagsList = form.querySelector('[data-omo-document-tags-list]');
    const tagsInput = form.querySelector('[data-omo-document-tags-input]');
    const tagsHiddenInput = form.querySelector('[data-omo-document-tags-hidden]');
    const contentSection = form.querySelector('[data-omo-document-content-section]');
    const pvSection = form.querySelector('[data-omo-document-pv-section]');
    const externalSection = form.querySelector('[data-omo-document-external-section]');
    const uploadSection = form.querySelector('[data-omo-document-upload-section]');
    const nextcloudFolderSection = form.querySelector('[data-omo-document-nextcloud-folder-section]');
    const nextcloudFolderPathField = form.querySelector('[data-omo-document-nextcloud-folder-path]');
    const nextcloudBrowseButton = form.querySelector('[data-omo-document-nextcloud-browse]');
    const nextcloudBrowser = form.querySelector('[data-omo-document-nextcloud-browser]');
    const etherpadSection = form.querySelector('[data-omo-document-etherpad-section]');
    const collaboraSection = form.querySelector('[data-omo-document-collabora-section]');
    const whiteboardSection = form.querySelector('[data-omo-document-whiteboard-section]');
    const ethercalcSection = form.querySelector('[data-omo-document-ethercalc-section]');
    const externalUrlField = form.querySelector('[data-omo-document-external-url]');
    const externalOpenInNewWindowField = form.querySelector('input[name="open_in_new_window"]');
    const uploadInput = form.querySelector('[data-omo-document-upload-input]');
    const uploadHasExistingFile = pageConfig.uploadHasExistingFile;
    const aiToolsEnabled = pageConfig.aiToolsEnabled;
    const textToolsEnabled = pageConfig.textToolsEnabled;
    const transcriptionEnabled = pageConfig.transcriptionEnabled;
    const initialHtmlValue = pageConfig.initialHtmlValue;
    const embeddableDocuments = pageConfig.embeddableDocuments;
    const uiText = pageConfig.uiText;
    const editingDocumentId = pageConfig.editingDocumentId;
    const editLockEndpointUrl = '/omo/api/documents/edit_lock.php';
    const editLockHeartbeatIntervalMs = pageConfig.editLockHeartbeatIntervalMs;
    const draftSyncDebounceMs = 1000;
    let keywordTags = [];
    let htmlField = null;
    let htmlValueCache = String(initialHtmlValue || '');
    let editLockLost = false;
    const aiTools = window.omoHtmlAiTools.create({
        host: htmlHost,
        statusNode: dictationStatusNode,
        textToolsEnabled: aiToolsEnabled && textToolsEnabled,
        transcriptionEnabled: aiToolsEnabled && transcriptionEnabled,
        isAvailable: function () { return !editLockLost && isHtmlTypeSelected(); },
        getContext: function () {
            const title = form.querySelector('input[name="title"]');
            return {organizationId: pageConfig.organizationId, holonId: pageConfig.holonId, title: title ? title.value : ''};
        },
        onBusyChange: function (busy) {
            const submit = form.querySelector('[data-omo-document-editor-submit]');
            if (submit) submit.disabled = editLockLost || busy;
        }
    });
    let editLockHeartbeatTimer = null;
    let draftSyncTimer = null;

    function closeDocumentEditor(options) {
        if (editorHost === 'project' && typeof window.omoCloseProjectDocumentEditorDrawer === 'function') {
            window.omoCloseProjectDocumentEditorDrawer(options);
            return;
        }
        if (editorHost === 'project_picker' && typeof window.commonTopbarCloseModal === 'function') {
            window.commonTopbarCloseModal();
            return;
        }
        if (typeof window.omoCloseDocumentEditorDrawer === 'function') {
            window.omoCloseDocumentEditorDrawer(options);
        }
    }

    function getSelectedDocumentType() {
        if (!typeSelect) {
            return 'html';
        }

        return String(typeSelect.value || 'html').trim().toLowerCase() || 'html';
    }

    function isFolderTypeSelected() {
        return getSelectedDocumentType() === 'folder';
    }

    function isHtmlTypeSelected() {
        return getSelectedDocumentType() === 'html';
    }

    function isUploadedFileTypeSelected() {
        return getSelectedDocumentType() === 'uploaded_file';
    }

    function isNextcloudFolderTypeSelected() {
        return getSelectedDocumentType() === 'nextcloud_folder';
    }

    function syncTypeUi() {
        const isHtmlDocument = isHtmlTypeSelected();
        const isPvDocument = getSelectedDocumentType() === 'pv';
        const isExternalLink = getSelectedDocumentType() === 'external_link';
        const isUploadedFile = isUploadedFileTypeSelected();
		const isNextcloudFolder = isNextcloudFolderTypeSelected();
        const isEtherpad = getSelectedDocumentType() === 'etherpad';
        const isCollabora = ['collabora_document', 'collabora_spreadsheet', 'collabora_presentation', 'collabora_drawing'].includes(getSelectedDocumentType());
        const isWhiteboard = getSelectedDocumentType() === 'whiteboard';
        const isEthercalc = getSelectedDocumentType() === 'ethercalc';

        if (contentSection) {
            contentSection.hidden = !isHtmlDocument;
        }

        if (pvSection) {
            pvSection.hidden = !isPvDocument;
        }

        if (externalSection) {
            externalSection.hidden = !isExternalLink;
        }

        if (externalUrlField) {
            externalUrlField.required = isExternalLink;
        }

        if (uploadSection) {
            uploadSection.hidden = !isUploadedFile;
        }

        if (nextcloudFolderSection) {
            nextcloudFolderSection.hidden = !isNextcloudFolder;
        }

        if (nextcloudFolderPathField) {
            nextcloudFolderPathField.required = isNextcloudFolder;
        }

        if (etherpadSection) {
            etherpadSection.hidden = !isEtherpad;
        }

        if (collaboraSection) {
            collaboraSection.hidden = !isCollabora;
        }

        if (whiteboardSection) {
            whiteboardSection.hidden = !isWhiteboard;
        }

        if (ethercalcSection) {
            ethercalcSection.hidden = !isEthercalc;
        }

        if (uploadInput) {
            uploadInput.required = isUploadedFile && !uploadHasExistingFile;
        }

        if (!isHtmlDocument) {
            aiTools.cancel();
            destroyHtmlField();
        } else {
            ensureHtmlFieldMounted();
        }
    }

    function hasEditLockSupport() {
        return Number.isInteger(editingDocumentId) && editingDocumentId > 0;
    }

    function clearEditLockHeartbeatTimer() {
        if (editLockHeartbeatTimer) {
            window.clearTimeout(editLockHeartbeatTimer);
            editLockHeartbeatTimer = null;
        }
    }

    function clearDraftSyncTimer() {
        if (draftSyncTimer) {
            window.clearTimeout(draftSyncTimer);
            draftSyncTimer = null;
        }
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizeKeywordTag(value) {
        return String(value || '')
            .replace(/[\r\n\t]+/g, ' ')
            .replace(/^#+/g, '')
            .replace(/\s+/g, ' ')
            .replace(/^,+|,+$/g, '')
            .trim();
    }

    function syncKeywordTagsField() {
        if (!tagsHiddenInput) {
            return;
        }

        tagsHiddenInput.value = keywordTags.join(',');
    }

    function renderKeywordTags() {
        if (!tagsList) {
            return;
        }

        tagsList.replaceChildren();

        keywordTags.forEach(function (tagLabel, tagIndex) {
            const chip = document.createElement('span');
            chip.className = 'omo-document-editor__tag';

            const label = document.createElement('span');
            label.textContent = tagLabel;
            chip.appendChild(label);

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'omo-document-editor__tag-remove';
            removeButton.innerHTML = '&times;';
            removeButton.setAttribute('aria-label', String(uiText.tagRemove || 'Retirer le tag') + ' ' + tagLabel);
            removeButton.setAttribute('title', String(uiText.tagRemove || 'Retirer le tag') + ' ' + tagLabel);
            removeButton.addEventListener('click', function () {
                keywordTags.splice(tagIndex, 1);
                syncKeywordTagsField();
                renderKeywordTags();
                if (tagsInput) {
                    tagsInput.focus();
                }
            });
            chip.appendChild(removeButton);

            tagsList.appendChild(chip);
        });
    }

    function addKeywordTag(rawValue) {
        const normalizedTag = normalizeKeywordTag(rawValue);
        if (normalizedTag === '') {
            return false;
        }

        const normalizedLookup = normalizedTag.toLocaleLowerCase();
        const alreadyExists = keywordTags.some(function (existingTag) {
            return String(existingTag || '').toLocaleLowerCase() === normalizedLookup;
        });

        if (alreadyExists) {
            return false;
        }

        keywordTags.push(normalizedTag);
        syncKeywordTagsField();
        renderKeywordTags();
        return true;
    }

    function commitKeywordInput() {
        if (!tagsInput) {
            return false;
        }

        const didAddTag = addKeywordTag(tagsInput.value);
        tagsInput.value = '';
        return didAddTag;
    }

    function flushKeywordInputDelimiters(commitTrailingValue) {
        if (!tagsInput) {
            return false;
        }

        const rawValue = String(tagsInput.value || '').replace(/[\r\n]+/g, ',');
        if (rawValue.indexOf(',') < 0) {
            if (!commitTrailingValue) {
                return false;
            }

            return commitKeywordInput();
        }

        const parts = rawValue.split(',');
        const trailingValue = commitTrailingValue ? '' : parts.pop();
        let didChange = false;

        parts.forEach(function (part) {
            didChange = addKeywordTag(part) || didChange;
        });

        tagsInput.value = normalizeKeywordTag(trailingValue);
        return didChange;
    }

    function initializeKeywordTags() {
        if (!tagsInput || !tagsHiddenInput) {
            return;
        }

        keywordTags = [];
        String(tagsHiddenInput.value || '').split(',').forEach(function (part) {
            addKeywordTag(part);
        });
        tagsInput.value = '';
        syncKeywordTagsField();
        renderKeywordTags();
    }

    function buildDocumentEmbedHtml(documentItem) {
        if (!documentItem) {
            return '';
        }

        const numericId = Number.parseInt(String(documentItem.id || ''), 10);
        if (!Number.isInteger(numericId) || numericId <= 0) {
            return '';
        }

        const title = String(documentItem.title || '').trim();
        const description = String(documentItem.description || '').trim();
        const resolvedTitle = title !== '' ? title : omoUiText("document_2", "Document #") + String(numericId);
        let html = ''
            + '<span class="omo-document-embed"'
            + " data-omo-embed-type=\"document\""
            + ' data-omo-document-id="' + escapeHtml(String(numericId)) + '"'
            + ' data-omo-document-title="' + escapeHtml(resolvedTitle) + '"';

        if (description !== '') {
            html += ' data-omo-document-description="' + escapeHtml(description) + '"';
        }

        html += ' contenteditable="false">'
            + ("<strong>" + omoUiHtml("document_lie", "Document lié") + "</strong><br>")
            + '<strong>' + escapeHtml(resolvedTitle) + '</strong>';

        if (description !== '') {
            html += '<br>' + escapeHtml(description);
        }

        html += '</span>';
        return html;
    }

    function getEmbedNodeDocumentId(targetNode) {
        if (!targetNode || !targetNode.getAttribute) {
            return 0;
        }

        return Number.parseInt(String(targetNode.getAttribute('data-omo-document-id') || ''), 10) || 0;
    }

    function applyDocumentEmbedSelection(documentItem, targetNode, insertionMarker) {
        if (!htmlField || !documentItem) {
            return;
        }

        const embedHtml = buildDocumentEmbedHtml(documentItem);
        if (embedHtml === '') {
            return;
        }

        if (targetNode && typeof htmlField.replaceNodeWithHtml === 'function') {
            htmlField.replaceNodeWithHtml(targetNode, embedHtml);
            return;
        }

        if (insertionMarker && typeof htmlField.replaceMarkerWithHtml === 'function') {
            htmlField.replaceMarkerWithHtml(insertionMarker, embedHtml);
            return;
        }

        if (typeof htmlField.insertHtmlAtCursor === 'function') {
            htmlField.insertHtmlAtCursor(embedHtml);
        }
    }

    function openDocumentEmbedPicker(options) {
        if (!htmlField || !window.omoDocumentEmbedPicker) return;
        const settings = options && typeof options === 'object' ? options : {};
        const targetNode = settings.targetNode || null;
        let insertionMarker = targetNode ? null : htmlField.createTemporaryCursorMarker();
        let resolved = false;
        const cleanup = function () {
            if (insertionMarker) htmlField.removeTemporaryMarker(insertionMarker);
            insertionMarker = null;
        };
        const picker = window.omoDocumentEmbedPicker.open({
            items: embeddableDocuments,
            organizationId: pageConfig.organizationId,
            initialHolonId: pageConfig.contextHolonId,
            scopeLabels: uiText.embedScope,
            selectedId: settings.selectedDocumentId || (targetNode ? getEmbedNodeDocumentId(targetNode) : 0),
            filter: function (item) { return item.documentType === 'html' && Number(item.id) !== Number(editingDocumentId); },
            labels: {
                modalTitle: uiText.embedModalTitle, search: uiText.embedSearch,
                quickSearchPlaceholder: uiText.embedSearchPlaceholder, visibleDocuments: uiText.embedVisible,
                none: uiText.embedNone, fallbackTitle: uiText.embedFallbackTitle,
                cancel: uiText.actionCancel, remove: uiText.embedRemove,
                insert: targetNode ? uiText.embedUpdate : uiText.embedInsert
            },
            onSelect: function (item) {
                applyDocumentEmbedSelection(item, targetNode, insertionMarker);
                resolved = true;
                insertionMarker = null;
            },
            onRemove: targetNode ? function () { resolved = htmlField.removeNode(targetNode); } : null,
            onClose: function () { if (!resolved) cleanup(); }
        });
        if (!picker) cleanup();
    }

    function getCurrentDraftContent() {
        if (!isHtmlTypeSelected()) {
            return '';
        }

        if (htmlField && typeof htmlField.getValue === 'function') {
            return String(htmlField.getValue() || '');
        }

        return String(htmlValueCache || '');
    }

    function postEditLockAction(action, options) {
        if (!hasEditLockSupport()) {
            return Promise.resolve({ status: true });
        }

        const settings = options && typeof options === 'object' ? options : {};
        const body = new URLSearchParams();
        body.set('id', String(editingDocumentId));
        body.set('action', String(action || 'heartbeat'));
        if (settings.clearDraft) {
            body.set('clear_draft', '1');
        }
        if (settings.includeDraft) {
            body.set('draft_content', getCurrentDraftContent());
        }

        return fetch(editLockEndpointUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString(),
            cache: 'no-store',
            keepalive: !!settings.keepalive
        }).then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (payload) {
                return {
                    ok: response.ok,
                    payload: payload
                };
            });
        });
    }

    function markEditLockLost(message) {
        editLockLost = true;
        aiTools.cancel();
        clearEditLockHeartbeatTimer();
        clearDraftSyncTimer();
        setStatus(String(message || omoUiText("ce_document_n_est_plus_verrouille_pour_votre_edition", "Ce document n’est plus verrouillé pour votre édition.")));
        aiTools.sync();
    }

    function scheduleDraftSync(delayMs) {
        if (!hasEditLockSupport() || editLockLost || !isHtmlTypeSelected()) {
            return;
        }

        clearDraftSyncTimer();
        draftSyncTimer = window.setTimeout(function () {
            postEditLockAction('heartbeat', { includeDraft: true })
                .then(function (result) {
                    if (!result || !result.ok || !result.payload || result.payload.status !== true) {
                        const message = result && result.payload && result.payload.message
                            ? result.payload.message
                            : omoUiText("ce_document_est_desormais_edite_ailleurs", "Ce document est désormais édité ailleurs.");
                        markEditLockLost(message);
                    }
                })
                .catch(function () {
                });
        }, Math.max(250, Number(delayMs || 0)));
    }

    function scheduleEditLockHeartbeat() {
        if (!hasEditLockSupport() || editLockLost) {
            return;
        }

        clearEditLockHeartbeatTimer();
        editLockHeartbeatTimer = window.setTimeout(function () {
            postEditLockAction('heartbeat', { includeDraft: true })
                .then(function (result) {
                    if (!result || !result.ok || !result.payload || result.payload.status !== true) {
                        const message = result && result.payload && result.payload.message
                            ? result.payload.message
                            : omoUiText("ce_document_est_desormais_edite_ailleurs", "Ce document est désormais édité ailleurs.");
                        markEditLockLost(message);
                        return;
                    }

                    scheduleEditLockHeartbeat();
                })
                .catch(function () {
                    scheduleEditLockHeartbeat();
                });
        }, editLockHeartbeatIntervalMs);
    }

    function releaseEditLock(options) {
        if (!hasEditLockSupport()) {
            return Promise.resolve();
        }

        const settings = options && typeof options === 'object' ? options : {};
        clearEditLockHeartbeatTimer();
        clearDraftSyncTimer();

        if (settings.beacon && navigator && typeof navigator.sendBeacon === 'function') {
            const body = new URLSearchParams();
            body.set('id', String(editingDocumentId));
            body.set('action', 'release');
            if (settings.clearDraft) {
                body.set('clear_draft', '1');
            }
            navigator.sendBeacon(editLockEndpointUrl, body);
            return Promise.resolve();
        }

        return postEditLockAction('release', {
            keepalive: !!settings.keepalive,
            clearDraft: !!settings.clearDraft
        })
            .catch(function () {
            });
    }

    function destroyHtmlField() {
        aiTools.cancel();
        if (!htmlField) {
            return;
        }

        if (typeof htmlField.getValue === 'function') {
            htmlValueCache = String(htmlField.getValue() || '');
        }

        if (typeof htmlField.destroy === 'function') {
            htmlField.destroy();
        }

        htmlField = null;
        if (htmlHost) {
            htmlHost.innerHTML = '';
        }
    }

    function mountHtmlField() {
        if (
            !htmlHost
            || !isHtmlTypeSelected()
            || htmlField
            || !window.omoSimpleHtmlField
            || typeof window.omoSimpleHtmlField.mount !== 'function'
        ) {
            return;
        }

        const customButtons = [];

        customButtons.push({
            name: 'omoDocumentEmbed',
            group: 'omo-embed',
            label: omoUiText("document", "Document"),
            title: omoUiText("inserer_un_document", "Insérer un document"),
            className: 'note-btn-light',
            focusForInsertion: true,
            onClick: function () {
                openDocumentEmbedPicker();
            }
        });

        customButtons.push(...aiTools.buttons);

        htmlField = window.omoSimpleHtmlField.mount(htmlHost, {
            value: htmlValueCache,
            placeholder: omoUiText("redigez_le_contenu_du_document", "Rédigez le contenu du document…"),
            customButtons: customButtons,
            resourceGapHelperLabel: String(uiText.embedAddLine || ''),
            onChange: function (value) {
                htmlValueCache = String(value || '');
                scheduleDraftSync(draftSyncDebounceMs);
            },
            onDoubleClick: function (context) {
                const rawTarget = context && context.target && context.target.closest
                    ? context.target.closest('.omo-document-embed[data-omo-embed-type="document"]')
                    : null;

                if (!rawTarget) {
                    return;
                }

                openDocumentEmbedPicker({
                    targetNode: rawTarget,
                    selectedDocumentId: getEmbedNodeDocumentId(rawTarget)
                });
            }
        });

        aiTools.bind(htmlField);
    }

    function ensureHtmlFieldMounted() {
        if (!isHtmlTypeSelected()) {
            destroyHtmlField();
            return;
        }

        const htmlFieldVersion = '20261029-translations';
        if (
            window.omoSimpleHtmlField
            && typeof window.omoSimpleHtmlField.mount === 'function'
            && String(window.omoSimpleHtmlField.version || '') === htmlFieldVersion
        ) {
            mountHtmlField();
            return;
        }

        const scriptSelector = 'script[data-omo-simple-html-field-script="1"][data-omo-simple-html-field-version="' + htmlFieldVersion + '"]';
        const existingScript = document.querySelector(scriptSelector);
        if (existingScript) {
            if (existingScript.getAttribute('data-loaded') === '1') {
                mountHtmlField();
            } else {
                existingScript.addEventListener('load', mountHtmlField, { once: true });
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
            mountHtmlField();
        };
        document.head.appendChild(script);
    }

    ensureHtmlFieldMounted();
    initializeKeywordTags();

    aiTools.sync();

    function setStatus(message) {
        if (message && typeof window.commonNotify === 'function') {
            var notice = statusNode;
            if (notice) { notice.textContent = ''; notice.hidden = true; }
            window.commonNotify(String(message), 'error');
            return;
        }

        if (!statusNode) {
            return;
        }

        const text = String(message || '').trim();
        statusNode.hidden = text === '';
        statusNode.textContent = text;
    }

    function setSavingState(isSaving) {
        form.querySelectorAll('input, textarea, select, button').forEach(function (field) {
            if (isSaving) {
                field.dataset.omoDisabledBeforeSave = field.disabled ? '1' : '0';
                field.disabled = true;
            } else if (field.dataset.omoDisabledBeforeSave !== undefined) {
                field.disabled = field.dataset.omoDisabledBeforeSave === '1';
                delete field.dataset.omoDisabledBeforeSave;
            }
        });
        document.querySelectorAll('[form="' + form.id + '"]').forEach(function (field) {
            field.disabled = !!isSaving;
        });
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            aiTools.cancel();
            releaseEditLock({ keepalive: true });
            closeDocumentEditor({ returnToDetail: true });
        });
    }

    const handleDrawerClose = function () {
        aiTools.cancel();
        releaseEditLock({ keepalive: true });
        destroyHtmlField();
        window.removeEventListener('omo-document-editor-drawer-close', handleDrawerClose);
    };

    window.addEventListener('omo-document-editor-drawer-close', handleDrawerClose);
    if (editorHost === 'project_picker') {
        window.addEventListener('common-topbar-modal-close', handleDrawerClose, { once: true });
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', syncTypeUi);
    }

    if (tagsEditor && tagsInput) {
        tagsEditor.addEventListener('click', function (event) {
            if (event.target instanceof Element && event.target.closest('.omo-document-editor__tag-remove')) {
                return;
            }

            tagsInput.focus();
        });

        tagsInput.addEventListener('keydown', function (event) {
            const key = String(event.key || '');
            const hasTypedValue = normalizeKeywordTag(tagsInput.value) !== '';

            if (key === 'Enter') {
                event.preventDefault();
                if (hasTypedValue) {
                    commitKeywordInput();
                }
                return;
            }

            if (key === ',') {
                event.preventDefault();
                if (hasTypedValue) {
                    commitKeywordInput();
                }
                return;
            }

            if (key === 'Tab' && hasTypedValue) {
                event.preventDefault();
                commitKeywordInput();
                return;
            }

            if (key === 'Backspace' && !hasTypedValue && keywordTags.length > 0) {
                event.preventDefault();
                keywordTags.splice(keywordTags.length - 1, 1);
                syncKeywordTagsField();
                renderKeywordTags();
            }
        });

        tagsInput.addEventListener('input', function () {
            flushKeywordInputDelimiters(false);
        });

        tagsInput.addEventListener('blur', function () {
            commitKeywordInput();
        });

        tagsInput.addEventListener('paste', function () {
            window.setTimeout(function () {
                flushKeywordInputDelimiters(true);
            }, 0);
        });
    }

    if (nextcloudBrowseButton && nextcloudBrowser && nextcloudFolderPathField) {
        const browseNextcloudFolder = function (requestedPath) {
            const path = String(requestedPath === undefined ? nextcloudFolderPathField.value : requestedPath).trim().replace(/^\/+|\/+$/g, '');
            const endpoint = new URL('/omo/api/documents/nextcloud/browse.php', window.location.origin);
            endpoint.searchParams.set('oid', String(pageConfig.organizationId));
            endpoint.searchParams.set('cid', String(pageConfig.contextHolonId));
            endpoint.searchParams.set('path', path);
            nextcloudBrowseButton.disabled = true;
            nextcloudBrowser.hidden = false;
            nextcloudBrowser.textContent = omoUiText("chargement", "Chargement...");

            fetch(endpoint.toString(), {credentials: 'same-origin', cache: 'no-store'})
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok || !payload || payload.status !== true) {
                            throw new Error(String(payload && payload.message || omoUiText("impossible_de_lire_nextcloud", "Impossible de lire NextCloud.")));
                        }
                        return payload;
                    });
                })
                .then(function (payload) {
                    const currentPath = String(payload.path || '');
                    const entries = Array.isArray(payload.entries) ? payload.entries : [];
                    nextcloudBrowser.replaceChildren();
                    const title = document.createElement('div');
                    title.className = 'generic-card-title';
                    title.textContent = currentPath === '' ? '/' : '/' + currentPath;
                    nextcloudBrowser.appendChild(title);
                    const parentPath = currentPath.split('/').filter(Boolean).slice(0, -1).join('/');
                    if (currentPath !== '') {
                        const parentButton = document.createElement('button');
                        parentButton.type = 'button';
                        parentButton.className = 'generic-action-button generic-action-button--secondary';
                        parentButton.textContent = '..';
                        parentButton.addEventListener('click', function () { browseNextcloudFolder(parentPath); });
                        nextcloudBrowser.appendChild(parentButton);
                    }
                    if (entries.length === 0) {
                        const empty = document.createElement('div');
                        empty.className = 'generic-help-text';
                        empty.textContent = pageConfig.documentsCreateNextcloudEmpty;
                        nextcloudBrowser.appendChild(empty);
                        return;
                    }
                    entries.forEach(function (entry) {
                        if (!entry || !entry.isFolder) {
                            return;
                        }
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'generic-action-button generic-action-button--secondary';
                        button.textContent = String(entry.name || entry.path || omoUiText("dossier", "Dossier"));
                        button.addEventListener('click', function () {
                            nextcloudFolderPathField.value = String(entry.path || '');
                            browseNextcloudFolder(entry.path || '');
                        });
                        nextcloudBrowser.appendChild(button);
                    });
                })
                .catch(function (error) {
                    nextcloudBrowser.textContent = String(error && error.message || omoUiText("impossible_de_lire_nextcloud", "Impossible de lire NextCloud."));
                })
                .finally(function () {
                    nextcloudBrowseButton.disabled = false;
                });
        };

        nextcloudBrowseButton.addEventListener('click', function () {
            browseNextcloudFolder();
        });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (aiTools.isBusy()) return;
        setStatus('');

        if (editLockLost) {
            setStatus(omoUiText("ce_document_n_est_plus_verrouille_pour_votre_edition_rechargez_le_formulaire", "Ce document n’est plus verrouillé pour votre édition. Rechargez le formulaire."));
            return;
        }

        commitKeywordInput();

        if (
            uploadHasExistingFile
            && uploadInput
            && uploadInput.files
            && uploadInput.files.length > 0
            && !window.confirm(String(uiText.uploadReplaceConfirm || 'Le fichier actuel sera remplacé. Continuer ?'))
        ) {
            return;
        }

        const formData = new FormData(form);
        formData.set('content', !isHtmlTypeSelected()
            ? ''
            : (htmlField && typeof htmlField.getValue === 'function'
                ? String(htmlField.getValue() || '')
                : htmlValueCache));

        const shouldCloseDocumentDrawerAfterSave = editingDocumentId > 0
            && getSelectedDocumentType() === 'external_link'
            && !!(externalOpenInNewWindowField && externalOpenInNewWindowField.checked);
        const usesSharedPendingState = typeof window.omoBeginPendingAction === 'function';

        if (usesSharedPendingState && !window.omoBeginPendingAction(form)) {
            return;
        }
        setSavingState(true);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            cache: 'no-store'
        })
            .then(async function (response) {
                const payload = await response.json().catch(function () { return null; });
                if (!response.ok || !payload || payload.status !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'save_failed');
                }

                return payload;
            })
            .then(function (payload) {
                const savedDocumentId = Number(payload.id || editingDocumentId || 0);
                if (typeof window.commonDrawerMarkSaved === 'function') window.commonDrawerMarkSaved(form);

                const refreshPromise = editorHost !== 'project' && editorHost !== 'project_picker' && typeof window.omoRefreshDocumentsPanel === 'function'
                    ? window.omoRefreshDocumentsPanel()
                    : Promise.resolve(null);

                return Promise.resolve(refreshPromise).finally(function () {
                    releaseEditLock({ keepalive: true });
                    aiTools.cancel();

                    if (editorHost === 'project' || editorHost === 'project_picker') {
                        window.dispatchEvent(new CustomEvent('omo-project-document-saved', {
                            detail: {
                                projectId: Number(form.querySelector('input[name="project_id"]') && form.querySelector('input[name="project_id"]').value || 0),
                                documentId: savedDocumentId
                            }
                        }));
                        closeDocumentEditor();
                    } else if (savedDocumentId > 0) {
                        if (shouldCloseDocumentDrawerAfterSave) {
                            closeDocumentEditor();
                            return;
                        }

                        closeDocumentEditor({
                            returnToDetail: true,
                            force: true
                        });

                        if (
                            !(typeof window.omoIsPvApplicationTabContext === 'function' && window.omoIsPvApplicationTabContext(form))
                            && typeof window.omoOpenDrawerHashState === 'function'
                        ) {
                            window.omoOpenDrawerHashState('documents-d' + String(savedDocumentId));
                        }
                    } else {
                        closeDocumentEditor({ returnToDetail: true });
                    }
                });
            })
            .catch(function (error) {
                setStatus(error && error.message && error.message !== 'save_failed'
                    ? error.message
                    : omoUiText("impossible_d_enregistrer_ce_document_pour_le_moment", "Impossible d’enregistrer ce document pour le moment."));
            })
            .finally(function () {
                setSavingState(false);
                if (usesSharedPendingState && typeof window.omoEndPendingAction === 'function') {
                    window.omoEndPendingAction(form);
                }
                aiTools.sync();
            });
    });

    syncTypeUi();
    scheduleEditLockHeartbeat();
    scheduleDraftSync(400);
    window.addEventListener('pagehide', function () {
        releaseEditLock({ beacon: true });
    }, { once: true });
})();
};
