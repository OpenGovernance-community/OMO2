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
    let mediaStream = null;
    let mediaRecorder = null;
    let recordedChunks = [];
    let recordingMimeType = '';
    let transcriptionController = null;
    let dictationMode = 'idle';
    let rewriteController = null;
    let rewriteMode = 'idle';
    let summarizeController = null;
    let summarizeMode = 'idle';
    let aiToolsOpen = false;
    let editLockLost = false;
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
            cleanupDictation({ discard: true });
            cleanupRewrite({ keepStatus: true });
            cleanupSummarize({ keepStatus: true });
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
        const resolvedTitle = title !== '' ? title : 'Document #' + String(numericId);
        let html = ''
            + '<span class="omo-document-embed"'
            + ' data-omo-embed-type="document"'
            + ' data-omo-document-id="' + escapeHtml(String(numericId)) + '"'
            + ' data-omo-document-title="' + escapeHtml(resolvedTitle) + '"';

        if (description !== '') {
            html += ' data-omo-document-description="' + escapeHtml(description) + '"';
        }

        html += ' contenteditable="false">'
            + '<strong>Document lié</strong><br>'
            + '<strong>' + escapeHtml(resolvedTitle) + '</strong>';

        if (description !== '') {
            html += '<br>' + escapeHtml(description);
        }

        html += '</span>';
        return html;
    }

    function findEmbeddableDocumentById(documentId) {
        const numericId = Number.parseInt(String(documentId || ''), 10);
        if (!Number.isInteger(numericId) || numericId <= 0) {
            return null;
        }

        return embeddableDocuments.find(function (documentItem) {
            return Number.parseInt(String(documentItem.id || ''), 10) === numericId;
        }) || null;
    }

    function getEmbedNodeDocumentId(targetNode) {
        if (!targetNode || !targetNode.getAttribute) {
            return 0;
        }

        return Number.parseInt(String(targetNode.getAttribute('data-omo-document-id') || ''), 10) || 0;
    }

    function updateDocumentEmbedPickerPreview(modalBody, selectedItem) {
        if (!modalBody) {
            return;
        }

        const titleNode = modalBody.querySelector('[data-omo-document-embed-preview-title]');
        const contextNode = modalBody.querySelector('[data-omo-document-embed-preview-context]');
        const descriptionNode = modalBody.querySelector('[data-omo-document-embed-preview-description]');
        const applyButton = modalBody.querySelector('[data-omo-document-embed-apply]');

        if (!selectedItem) {
            if (titleNode) {
                titleNode.textContent = uiText.embedNone || '';
            }
            if (contextNode) {
                contextNode.textContent = '';
                contextNode.hidden = true;
            }
            if (descriptionNode) {
                descriptionNode.textContent = '';
                descriptionNode.hidden = true;
            }
            if (applyButton) {
                applyButton.disabled = true;
            }
            return;
        }

        if (titleNode) {
            const title = String(selectedItem.title || '').trim();
            titleNode.textContent = title !== '' ? title : 'Document #' + String(selectedItem.id || '');
        }

        if (contextNode) {
            const contextLabel = String(selectedItem.contextLabel || '').trim();
            contextNode.textContent = contextLabel;
            contextNode.hidden = contextLabel === '';
        }

        if (descriptionNode) {
            const description = String(selectedItem.description || '').trim();
            descriptionNode.textContent = description;
            descriptionNode.hidden = description === '';
        }

        if (applyButton) {
            applyButton.disabled = false;
        }
    }

    function renderDocumentEmbedPickerOptions(selectNode, searchValue, selectedDocumentId) {
        if (!selectNode) {
            return null;
        }

        const normalizedSearch = String(searchValue || '').trim().toLowerCase();
        const matchingItems = embeddableDocuments.filter(function (documentItem) {
            if (normalizedSearch === '') {
                return true;
            }

            const haystack = [
                String(documentItem.title || ''),
                String(documentItem.description || ''),
                String(documentItem.contextLabel || '')
            ].join(' ').toLowerCase();

            return haystack.indexOf(normalizedSearch) >= 0;
        });

        selectNode.innerHTML = '';
        matchingItems.forEach(function (documentItem) {
            const option = document.createElement('option');
            const title = String(documentItem.title || '').trim();
            option.value = String(documentItem.id || '');
            option.textContent = title !== '' ? title : 'Document #' + String(documentItem.id || '');
            selectNode.appendChild(option);
        });

        let selectedItem = null;
        if (matchingItems.length > 0) {
            const preferredId = Number.parseInt(String(selectedDocumentId || ''), 10) || 0;
            selectedItem = matchingItems.find(function (documentItem) {
                return Number.parseInt(String(documentItem.id || ''), 10) === preferredId;
            }) || matchingItems[0];

            selectNode.value = String(selectedItem.id || '');
        }

        selectNode.disabled = matchingItems.length === 0;
        return selectedItem;
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
        if (!htmlField || typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }

        const settings = options && typeof options === 'object' ? options : {};
        const targetNode = settings.targetNode || null;
        let selectedDocumentId = Number.parseInt(String(settings.selectedDocumentId || ''), 10) || 0;
        let insertionMarker = null;
        let pickerResolved = false;

        if (!targetNode && typeof htmlField.createTemporaryCursorMarker === 'function') {
            insertionMarker = htmlField.createTemporaryCursorMarker();
        }

        const modalHtml = ''
            + '<div class="omo-document-embed-picker">'
            + '  <label class="omo-document-embed-picker__field">'
            + '    <span class="omo-document-embed-picker__label generic-form-label">Recherche</span>'
            + '    <input type="search" class="generic-form-control" data-omo-document-embed-search placeholder="' + escapeHtml(uiText.embedSearchPlaceholder || '') + '">'
            + '  </label>'
            + '  <label class="omo-document-embed-picker__field">'
            + '    <span class="omo-document-embed-picker__label generic-form-label">Documents visibles</span>'
            + '    <select class="generic-form-control omo-document-embed-picker__select" data-omo-document-embed-select size="10"></select>'
            + '  </label>'
            + '  <div class="omo-document-embed-picker__preview">'
            + '    <div class="omo-document-embed-picker__preview-title" data-omo-document-embed-preview-title>' + escapeHtml(uiText.embedNone || '') + '</div>'
            + '    <div class="omo-document-embed-picker__preview-context" data-omo-document-embed-preview-context hidden></div>'
            + '    <div class="omo-document-embed-picker__preview-description" data-omo-document-embed-preview-description hidden></div>'
            + '  </div>'
            + '  <div class="omo-document-embed-picker__actions">'
            + (targetNode
                ? '    <button type="button" class="generic-action-button generic-action-button--danger" data-omo-document-embed-delete>Supprimer</button>'
                : '')
            + '    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-document-embed-cancel>' + escapeHtml(uiText.actionCancel || '') + '</button>'
            + '    <button type="button" class="generic-action-button generic-action-button--main" data-omo-document-embed-apply disabled>' + escapeHtml(targetNode ? (uiText.embedUpdate || '') : (uiText.embedInsert || '')) + '</button>'
            + '  </div>'
            + '</div>';

        window.commonTopbarOpenModal(uiText.embedModalTitle || '', modalHtml, 'html');

        const modalBody = document.getElementById('commonTopbarModalBody');
        if (!modalBody) {
            if (
                insertionMarker
                && htmlField
                && typeof htmlField.removeTemporaryMarker === 'function'
            ) {
                htmlField.removeTemporaryMarker(insertionMarker);
            }
            return;
        }

        const searchNode = modalBody.querySelector('[data-omo-document-embed-search]');
        const selectNode = modalBody.querySelector('[data-omo-document-embed-select]');
        const deleteButton = modalBody.querySelector('[data-omo-document-embed-delete]');
        const cancelButton = modalBody.querySelector('[data-omo-document-embed-cancel]');
        const applyButton = modalBody.querySelector('[data-omo-document-embed-apply]');

        function cleanupInsertionMarker() {
            if (
                insertionMarker
                && htmlField
                && typeof htmlField.removeTemporaryMarker === 'function'
            ) {
                htmlField.removeTemporaryMarker(insertionMarker);
            }

            insertionMarker = null;
        }

        window.addEventListener('common-topbar-modal-close', function () {
            if (!pickerResolved) {
                cleanupInsertionMarker();
            }
        }, { once: true });

        function syncSelection(nextSelectedDocumentId) {
            selectedDocumentId = Number.parseInt(String(nextSelectedDocumentId || ''), 10) || 0;
            const selectedItem = findEmbeddableDocumentById(selectedDocumentId);
            updateDocumentEmbedPickerPreview(modalBody, selectedItem);
            return selectedItem;
        }

        function rerenderOptions() {
            const selectedItem = renderDocumentEmbedPickerOptions(
                selectNode,
                searchNode ? searchNode.value : '',
                selectedDocumentId > 0 ? selectedDocumentId : (targetNode ? getEmbedNodeDocumentId(targetNode) : 0)
            );

            selectedDocumentId = selectedItem ? Number.parseInt(String(selectedItem.id || ''), 10) : 0;
            updateDocumentEmbedPickerPreview(modalBody, selectedItem);
        }

        rerenderOptions();

        if (searchNode) {
            searchNode.focus();
            searchNode.addEventListener('input', rerenderOptions);
        }

        if (selectNode) {
            selectNode.addEventListener('change', function () {
                syncSelection(selectNode.value);
            });

            selectNode.addEventListener('dblclick', function () {
                const selectedItem = syncSelection(selectNode.value);
                if (!selectedItem) {
                    return;
                }

                pickerResolved = true;
                applyDocumentEmbedSelection(selectedItem, targetNode, insertionMarker);
                insertionMarker = null;
                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }
            });
        }

        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                pickerResolved = false;
                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }
            });
        }

        if (deleteButton) {
            deleteButton.addEventListener('click', function () {
                pickerResolved = true;
                cleanupInsertionMarker();
                if (targetNode && htmlField && typeof htmlField.removeNode === 'function') {
                    htmlField.removeNode(targetNode);
                }

                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }
            });
        }

        if (applyButton) {
            applyButton.addEventListener('click', function () {
                const selectedItem = syncSelection(selectNode ? selectNode.value : 0);
                if (!selectedItem) {
                    return;
                }

                pickerResolved = true;
                applyDocumentEmbedSelection(selectedItem, targetNode, insertionMarker);
                insertionMarker = null;
                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }
            });
        }
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
        clearEditLockHeartbeatTimer();
        clearDraftSyncTimer();
        setStatus(String(message || 'Ce document n’est plus verrouillé pour votre édition.'));
        syncDictationToolbarButtons();
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
                            : 'Ce document est desormais edite ailleurs.';
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
                            : 'Ce document est desormais edite ailleurs.';
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

    function setDictationStatus(message, type) {
        if (!dictationStatusNode) {
            return;
        }

        const text = String(message || '').trim();
        const statusType = String(type || '').trim().toLowerCase();

        dictationStatusNode.hidden = text === '';
        dictationStatusNode.textContent = text;
        dictationStatusNode.classList.toggle('is-live', statusType === 'live');
        dictationStatusNode.classList.toggle('is-error', statusType === 'error');
        dictationStatusNode.classList.toggle('is-success', statusType === 'success');
    }

    function getRecordingMimeType() {
        if (typeof window.MediaRecorder === 'undefined' || typeof window.MediaRecorder.isTypeSupported !== 'function') {
            return '';
        }

        const candidates = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/mp4',
            'audio/ogg;codecs=opus',
            'audio/ogg'
        ];

        for (let index = 0; index < candidates.length; index += 1) {
            if (window.MediaRecorder.isTypeSupported(candidates[index])) {
                return candidates[index];
            }
        }

        return '';
    }

    function getRecordingFileExtension(mimeType) {
        const normalizedMimeType = String(mimeType || '').toLowerCase();

        if (normalizedMimeType.indexOf('mp4') >= 0 || normalizedMimeType.indexOf('m4a') >= 0) {
            return 'm4a';
        }

        if (normalizedMimeType.indexOf('ogg') >= 0) {
            return 'ogg';
        }

        if (normalizedMimeType.indexOf('wav') >= 0) {
            return 'wav';
        }

        return 'webm';
    }

    function releaseMediaStream() {
        if (mediaStream && typeof mediaStream.getTracks === 'function') {
            mediaStream.getTracks().forEach(function (track) {
                try {
                    track.stop();
                } catch (error) {
                }
            });
        }

        mediaStream = null;
    }

    function resetDictationRecorderState() {
        mediaRecorder = null;
        recordedChunks = [];
        recordingMimeType = '';
        releaseMediaStream();
    }

    function syncDictationToolbarButtons() {
        if (!aiToolsEnabled || !htmlField || typeof htmlField.setToolbarButtonState !== 'function') {
            return;
        }

        const isRecording = dictationMode === 'recording';
        const isTranscribing = dictationMode === 'transcribing';
        const isRewriting = rewriteMode === 'pending';
        const isSummarizing = summarizeMode === 'pending';
        const isAiBusy = isRecording || isTranscribing || isRewriting || isSummarizing;
        const aiToolsVisible = aiToolsOpen || isAiBusy;
        const submitButton = form.querySelector('[data-omo-document-editor-submit]');

        htmlField.setToolbarButtonState('omoDocumentAiToggle', {
            label: 'IA',
            title: aiToolsVisible ? 'Masquer les outils IA' : 'Afficher les outils IA',
            active: aiToolsVisible,
            disabled: false
        });

        htmlField.setToolbarButtonState('omoDocumentDictate', {
            label: isRecording ? 'En cours…' : 'Dicter',
            title: isRecording ? 'Enregistrement en cours' : 'Démarrer une dictée',
            disabled: isRecording || isTranscribing || isRewriting || isSummarizing,
            hidden: !aiToolsVisible
        });
        htmlField.setToolbarButtonState('omoDocumentTranscript', {
            label: isTranscribing ? 'Transcription…' : 'Transcrire',
            title: 'Arrêter l’enregistrement et transcrire',
            disabled: !isRecording || isTranscribing || isRewriting || isSummarizing,
            hidden: !aiToolsVisible
        });
        htmlField.setToolbarButtonState('omoDocumentDictationCancel', {
            label: 'Annuler',
            title: 'Annuler la dictée en cours',
            disabled: (!isRecording && !isTranscribing),
            hidden: !aiToolsVisible || (!isRecording && !isTranscribing)
        });
        htmlField.setToolbarButtonState('omoDocumentRewrite', {
            label: isRewriting ? 'Réécriture…' : 'Réécrire',
            title: isRewriting ? 'Réécriture en cours' : 'Réécrire la sélection',
            disabled: isRecording || isTranscribing || isRewriting || isSummarizing,
            hidden: !aiToolsVisible
        });
        htmlField.setToolbarButtonState('omoDocumentSummarize', {
            label: isSummarizing ? 'Résumé…' : 'Résumer',
            title: isSummarizing ? 'Résumé en cours' : 'Résumer la sélection',
            disabled: isRecording || isTranscribing || isRewriting || isSummarizing,
            hidden: !aiToolsVisible
        });

        if (submitButton) {
            submitButton.disabled = editLockLost || isRecording || isTranscribing || isRewriting || isSummarizing;
        }
    }

    function setDictationMode(nextMode) {
        dictationMode = String(nextMode || 'idle').trim().toLowerCase() || 'idle';
        syncDictationToolbarButtons();
    }

    function toggleAiTools(forceOpen) {
        if (!aiToolsEnabled) {
            return;
        }

        const shouldOpen = forceOpen === undefined
            ? !aiToolsOpen
            : !!forceOpen;

        aiToolsOpen = shouldOpen;
        syncDictationToolbarButtons();
    }

    function formatDictationError(error) {
        const errorName = error && error.name ? String(error.name) : '';

        if (errorName === 'NotAllowedError' || errorName === 'SecurityError') {
            return 'L’accès au micro a été refusé.';
        }

        if (errorName === 'NotFoundError' || errorName === 'DevicesNotFoundError') {
            return 'Aucun micro n’est disponible sur cet appareil.';
        }

        if (errorName === 'AbortError') {
            return 'La dictée a été interrompue.';
        }

        if (error && error.message) {
            return String(error.message);
        }

        return 'Impossible d’utiliser la dictée pour le moment.';
    }

    function abortTranscriptionRequest() {
        if (transcriptionController && typeof transcriptionController.abort === 'function') {
            try {
                transcriptionController.abort();
            } catch (error) {
            }
        }

        transcriptionController = null;
    }

    function abortRewriteRequest() {
        if (rewriteController && typeof rewriteController.abort === 'function') {
            try {
                rewriteController.abort();
            } catch (error) {
            }
        }

        rewriteController = null;
    }

    function abortSummarizeRequest() {
        if (summarizeController && typeof summarizeController.abort === 'function') {
            try {
                summarizeController.abort();
            } catch (error) {
            }
        }

        summarizeController = null;
    }

    function stopRecorder(discardRecording) {
        return new Promise(function (resolve, reject) {
            if (!mediaRecorder) {
                resetDictationRecorderState();
                resolve(null);
                return;
            }

            const recorder = mediaRecorder;
            const mimeType = recordingMimeType || recorder.mimeType || 'audio/webm';

            const handleStop = function () {
                const blob = discardRecording
                    ? null
                    : new Blob(recordedChunks, { type: mimeType });
                resetDictationRecorderState();
                resolve(blob);
            };

            const handleError = function () {
                resetDictationRecorderState();
                reject(new Error('Impossible de finaliser l’enregistrement audio.'));
            };

            recorder.addEventListener('stop', handleStop, { once: true });
            recorder.addEventListener('error', handleError, { once: true });

            try {
                if (typeof recorder.requestData === 'function' && recorder.state === 'recording') {
                    recorder.requestData();
                }

                if (recorder.state !== 'inactive') {
                    recorder.stop();
                    return;
                }

                handleStop();
            } catch (error) {
                resetDictationRecorderState();
                reject(error);
            }
        });
    }

    function cleanupDictation(options) {
        const shouldDiscardRecording = !options || options.discard !== false;
        abortTranscriptionRequest();

        if (mediaRecorder) {
            stopRecorder(shouldDiscardRecording).catch(function () {
            });
        } else {
            resetDictationRecorderState();
        }

        setDictationMode('idle');
        if (options && options.keepStatus) {
            return;
        }

        setDictationStatus('', '');
    }

    function setRewriteMode(nextMode) {
        rewriteMode = String(nextMode || 'idle').trim().toLowerCase() || 'idle';
        syncDictationToolbarButtons();
    }

    function normalizeRewriteComparisonText(text) {
        return String(text || '')
            .replace(/\r\n?/g, '\n')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function cleanupRewrite(options) {
        abortRewriteRequest();
        setRewriteMode('idle');

        if (options && options.keepStatus) {
            return;
        }

        setDictationStatus('', '');
    }

    function setSummarizeMode(nextMode) {
        summarizeMode = String(nextMode || 'idle').trim().toLowerCase() || 'idle';
        syncDictationToolbarButtons();
    }

    function cleanupSummarize(options) {
        abortSummarizeRequest();
        setSummarizeMode('idle');

        if (options && options.keepStatus) {
            return;
        }

        setDictationStatus('', '');
    }

    function destroyHtmlField() {
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
            label: 'Document',
            title: 'Insérer un document',
            className: 'note-btn-light',
            focusForInsertion: true,
            onClick: function () {
                openDocumentEmbedPicker();
            }
        });

        if (aiToolsEnabled) {
            customButtons.push({
                name: 'omoDocumentAiToggle',
                group: 'omo-ai-toggle',
                label: 'IA',
                title: 'Afficher les outils IA',
                className: 'note-btn-light',
                onClick: function () {
                    toggleAiTools();
                }
            });
            customButtons.push({
                name: 'omoDocumentDictate',
                group: 'omo-ai-tools',
                label: 'Dicter',
                title: 'Démarrer une dictée',
                className: 'note-btn-light',
                hidden: true,
                onClick: function () {
                    startDictation();
                }
            });
            customButtons.push({
                name: 'omoDocumentTranscript',
                group: 'omo-ai-tools',
                label: 'Transcrire',
                title: 'Arrêter l’enregistrement et transcrire',
                className: 'note-btn-light',
                disabled: true,
                hidden: true,
                onClick: function () {
                    transcribeCurrentRecording();
                }
            });
            customButtons.push({
                name: 'omoDocumentDictationCancel',
                group: 'omo-ai-tools',
                label: 'Annuler',
                title: 'Annuler la dictée en cours',
                className: 'note-btn-light',
                hidden: true,
                disabled: true,
                onClick: function () {
                    cleanupDictation({ discard: true });
                    setDictationStatus('Dictée annulée.', 'error');
                }
            });
            customButtons.push({
                name: 'omoDocumentRewrite',
                group: 'omo-ai-tools',
                label: 'Rewrite',
                title: 'Réécrire la sélection',
                className: 'note-btn-light',
                hidden: true,
                onClick: function () {
                    rewriteSelectedPassage();
                }
            });
            customButtons.push({
                name: 'omoDocumentSummarize',
                group: 'omo-ai-tools',
                label: 'Résumer',
                title: 'Résumer la sélection',
                className: 'note-btn-light',
                hidden: true,
                onClick: function () {
                    summarizeSelectedPassage();
                }
            });
        }

        htmlField = window.omoSimpleHtmlField.mount(htmlHost, {
            value: htmlValueCache,
            placeholder: 'Rédigez le contenu du document…',
            height: 240,
            customButtons: customButtons,
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

        syncDictationToolbarButtons();

        if (aiToolsEnabled && htmlField && typeof htmlField.setToolbarButtonState === 'function') {
            htmlField.setToolbarButtonState('omoDocumentAiToggle', {
                label: 'IA',
                title: 'Afficher les outils IA'
            });
            htmlField.setToolbarButtonState('omoDocumentDictate', {
                label: 'Dicter',
                title: 'Démarrer une dictée'
            });
            htmlField.setToolbarButtonState('omoDocumentTranscript', {
                title: 'Arrêter l’enregistrement et transcrire'
            });
            htmlField.setToolbarButtonState('omoDocumentRewrite', {
                title: 'Réécrire la sélection'
            });
            htmlField.setToolbarButtonState('omoDocumentSummarize', {
                title: 'Résumer la sélection'
            });
        }

        syncDictationToolbarButtons();
    }

    function ensureHtmlFieldMounted() {
        if (!isHtmlTypeSelected()) {
            destroyHtmlField();
            return;
        }

        const htmlFieldVersion = '20260912-toolbar-always-visible';
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

    async function startDictation() {
        if (!aiToolsEnabled || dictationMode === 'recording' || dictationMode === 'transcribing') {
            return;
        }

        if (typeof window.MediaRecorder === 'undefined' || !navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            setDictationStatus('La dictée n’est pas disponible sur ce navigateur.', 'error');
            return;
        }

        try {
            if (htmlField && typeof htmlField.saveRange === 'function') {
                htmlField.saveRange();
            }

            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const mimeType = getRecordingMimeType();
            const recorder = mimeType !== ''
                ? new window.MediaRecorder(stream, { mimeType: mimeType })
                : new window.MediaRecorder(stream);

            mediaStream = stream;
            mediaRecorder = recorder;
            recordedChunks = [];
            recordingMimeType = mimeType || recorder.mimeType || 'audio/webm';

            recorder.addEventListener('dataavailable', function (recordEvent) {
                if (recordEvent.data && recordEvent.data.size > 0) {
                    recordedChunks.push(recordEvent.data);
                }
            });

            recorder.start();
            setDictationMode('recording');
            setDictationStatus('Enregistrement en cours. Quand vous avez terminé, cliquez sur Transcrire.', 'live');
        } catch (error) {
            cleanupDictation({ discard: true, keepStatus: true });
            setDictationStatus(formatDictationError(error), 'error');
        }
    }

    async function transcribeCurrentRecording() {
        if (dictationMode !== 'recording') {
            return;
        }

        setDictationMode('transcribing');
        setDictationStatus('Transcription en cours…', 'live');

        try {
            const audioBlob = await stopRecorder(false);
            if (!audioBlob || audioBlob.size <= 0) {
                throw new Error('Aucun son n’a été enregistré.');
            }

            const formData = new FormData();
            formData.set('oid', String(pageConfig.organizationId));
            formData.set('cid', String(pageConfig.holonId));
            formData.set('title', String(form.querySelector('input[name="title"]') ? form.querySelector('input[name="title"]').value || '' : ''));
            formData.set(
                'audio',
                audioBlob,
                'document-dictation.' + getRecordingFileExtension(audioBlob.type || recordingMimeType)
            );

            transcriptionController = typeof window.AbortController === 'function'
                ? new window.AbortController()
                : null;

            const response = await fetch('/omo/api/documents/html/transcribe.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: transcriptionController ? transcriptionController.signal : undefined,
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error('Impossible de transcrire cet enregistrement.');
            }

            const payload = await response.json();
            if (!payload || payload.status !== true) {
                throw new Error(payload && payload.message ? payload.message : 'Impossible de transcrire cet enregistrement.');
            }

            const transcriptText = String(payload.text || '').trim();
            if (transcriptText === '') {
                throw new Error('La transcription est vide.');
            }

            if (!htmlField || typeof htmlField.insertTextAtCursor !== 'function') {
                throw new Error('Impossible d’insérer la transcription dans l’éditeur.');
            }

            htmlField.insertTextAtCursor(transcriptText);
            if (typeof htmlField.focus === 'function') {
                htmlField.focus();
            }

            setDictationMode('idle');
            setDictationStatus('Transcription insérée dans le document.', 'success');
        } catch (error) {
            setDictationMode('idle');
            setDictationStatus(formatDictationError(error), 'error');
        } finally {
            transcriptionController = null;
        }
    }

    async function rewriteSelectedPassage() {
        if (!aiToolsEnabled || rewriteMode === 'pending' || summarizeMode === 'pending' || dictationMode === 'recording' || dictationMode === 'transcribing') {
            return;
        }

        if (!htmlField || typeof htmlField.getSelectedText !== 'function' || typeof htmlField.getPlainText !== 'function') {
            setDictationStatus('Impossible d’accéder à la sélection dans l’éditeur.', 'error');
            return;
        }

        if (typeof htmlField.saveRange === 'function') {
            htmlField.saveRange();
        }

        const selectedText = String(htmlField.getSelectedText() || '').trim();
        const fullText = String(htmlField.getPlainText() || '').trim();
        const rewritesWholeDocument = normalizeRewriteComparisonText(selectedText) === normalizeRewriteComparisonText(fullText);

        if (selectedText === '') {
            setDictationStatus('Sélectionnez un bloc de texte avant de lancer la réécriture.', 'error');
            return;
        }

        if (fullText === '') {
            setDictationStatus('Le document est vide.', 'error');
            return;
        }

        setRewriteMode('pending');
        setDictationStatus('Réécriture en cours…', 'live');

        try {
            const formData = new FormData();
            formData.set('oid', String(pageConfig.organizationId));
            formData.set('cid', String(pageConfig.holonId));
            formData.set('title', String(form.querySelector('input[name="title"]') ? form.querySelector('input[name="title"]').value || '' : ''));
            formData.set('selected_text', selectedText);
            formData.set('full_text', fullText);
            formData.set('rewrite_full_document', rewritesWholeDocument ? '1' : '0');

            rewriteController = typeof window.AbortController === 'function'
                ? new window.AbortController()
                : null;

            const response = await fetch('/omo/api/documents/html/rewrite.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: rewriteController ? rewriteController.signal : undefined,
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error('Impossible de réécrire cette sélection.');
            }

            const payload = await response.json();
            if (!payload || payload.status !== true) {
                throw new Error(payload && payload.message ? payload.message : 'Impossible de réécrire cette sélection.');
            }

            const rewrittenText = String(payload.text || '').trim();
            if (rewrittenText === '') {
                throw new Error('La réécriture est vide.');
            }

            if (typeof htmlField.replaceSelectionWithText !== 'function') {
                throw new Error('Impossible de remplacer la sélection dans l’éditeur.');
            }

            htmlField.replaceSelectionWithText(rewrittenText);
            if (typeof htmlField.focus === 'function') {
                htmlField.focus();
            }

            setRewriteMode('idle');
            setDictationStatus('Sélection réécrite.', 'success');
        } catch (error) {
            setRewriteMode('idle');
            setDictationStatus(
                error && error.message ? String(error.message) : 'Impossible de réécrire cette sélection.',
                'error'
            );
        } finally {
            rewriteController = null;
        }
    }

    async function summarizeSelectedPassage() {
        if (!aiToolsEnabled || summarizeMode === 'pending' || rewriteMode === 'pending' || dictationMode === 'recording' || dictationMode === 'transcribing') {
            return;
        }

        if (!htmlField || typeof htmlField.getSelectedText !== 'function' || typeof htmlField.getPlainText !== 'function') {
            setDictationStatus('Impossible d’accéder à la sélection dans l’éditeur.', 'error');
            return;
        }

        if (typeof htmlField.saveRange === 'function') {
            htmlField.saveRange();
        }

        const selectedText = String(htmlField.getSelectedText() || '').trim();
        const fullText = String(htmlField.getPlainText() || '').trim();

        if (selectedText === '') {
            setDictationStatus('Sélectionnez un bloc de texte avant de lancer le résumé.', 'error');
            return;
        }

        if (fullText === '') {
            setDictationStatus('Le document est vide.', 'error');
            return;
        }

        setSummarizeMode('pending');
        setDictationStatus('Résumé en cours…', 'live');

        try {
            const formData = new FormData();
            formData.set('oid', String(pageConfig.organizationId));
            formData.set('cid', String(pageConfig.holonId));
            formData.set('title', String(form.querySelector('input[name="title"]') ? form.querySelector('input[name="title"]').value || '' : ''));
            formData.set('selected_text', selectedText);
            formData.set('full_text', fullText);

            summarizeController = typeof window.AbortController === 'function'
                ? new window.AbortController()
                : null;

            const response = await fetch('/omo/api/documents/html/summarize.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: summarizeController ? summarizeController.signal : undefined,
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error('Impossible de résumer cette sélection.');
            }

            const payload = await response.json();
            if (!payload || payload.status !== true) {
                throw new Error(payload && payload.message ? payload.message : 'Impossible de résumer cette sélection.');
            }

            const summarizedText = String(payload.text || '').trim();
            if (summarizedText === '') {
                throw new Error('Le résumé est vide.');
            }

            if (typeof htmlField.replaceSelectionWithText !== 'function') {
                throw new Error('Impossible de remplacer la sélection dans l’éditeur.');
            }

            htmlField.replaceSelectionWithText(summarizedText);
            if (typeof htmlField.focus === 'function') {
                htmlField.focus();
            }

            setSummarizeMode('idle');
            setDictationStatus('Sélection résumée.', 'success');
        } catch (error) {
            setSummarizeMode('idle');
            setDictationStatus(
                error && error.message ? String(error.message) : 'Impossible de résumer cette sélection.',
                'error'
            );
        } finally {
            summarizeController = null;
        }
    }

    ensureHtmlFieldMounted();
    initializeKeywordTags();

    syncDictationToolbarButtons();

    function setStatus(message) {
        if (!statusNode) {
            return;
        }

        const text = String(message || '').trim();
        statusNode.hidden = text === '';
        statusNode.textContent = text;
    }

    function setSavingState(isSaving) {
        form.querySelectorAll('input, textarea, select, button').forEach(function (field) {
            field.disabled = !!isSaving;
        });
        document.querySelectorAll('[form="' + form.id + '"]').forEach(function (field) {
            field.disabled = !!isSaving;
        });
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            cleanupDictation({ discard: true });
            cleanupRewrite({ keepStatus: true });
            cleanupSummarize({ keepStatus: true });
            releaseEditLock({ keepalive: true });
            closeDocumentEditor({ returnToDetail: true });
        });
    }

    const handleDrawerClose = function () {
        cleanupDictation({ discard: true });
        cleanupRewrite({ keepStatus: true });
        cleanupSummarize({ keepStatus: true });
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
            nextcloudBrowser.textContent = 'Chargement...';

            fetch(endpoint.toString(), {credentials: 'same-origin', cache: 'no-store'})
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok || !payload || payload.status !== true) {
                            throw new Error(String(payload && payload.message || 'Impossible de lire NextCloud.'));
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
                        button.textContent = String(entry.name || entry.path || 'Dossier');
                        button.addEventListener('click', function () {
                            nextcloudFolderPathField.value = String(entry.path || '');
                            browseNextcloudFolder(entry.path || '');
                        });
                        nextcloudBrowser.appendChild(button);
                    });
                })
                .catch(function (error) {
                    nextcloudBrowser.textContent = String(error && error.message || 'Impossible de lire NextCloud.');
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
        setStatus('');

        if (editLockLost) {
            setStatus('Ce document n’est plus verrouillé pour votre édition. Rechargez le formulaire.');
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
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('save_failed');
                }

                return response.json();
            })
            .then(function (payload) {
                if (!payload || payload.status !== true) {
                    throw new Error(payload && payload.message ? payload.message : 'save_failed');
                }

                const savedDocumentId = Number(payload.id || editingDocumentId || 0);

                const refreshPromise = editorHost !== 'project' && editorHost !== 'project_picker' && typeof window.omoRefreshDocumentsPanel === 'function'
                    ? window.omoRefreshDocumentsPanel()
                    : Promise.resolve(null);

                return Promise.resolve(refreshPromise).finally(function () {
                    releaseEditLock({ keepalive: true });
                    cleanupDictation({ discard: true });
                    cleanupRewrite({ keepStatus: true });
                    cleanupSummarize({ keepStatus: true });

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
                    : 'Impossible d’enregistrer ce document pour le moment.');
            })
            .finally(function () {
                setSavingState(false);
                if (usesSharedPendingState && typeof window.omoEndPendingAction === 'function') {
                    window.omoEndPendingAction(form);
                }
                syncDictationToolbarButtons();
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
