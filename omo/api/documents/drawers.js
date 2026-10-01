window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/documents/drawers.js"] = function (pageConfig, pageScript) {
        (function () {
            function getDocumentsRoot() {
                return window.omoDocumentsFindRoot();
            }

            function useLocalDrawerNavigation(rootOverride) {
                const root = rootOverride instanceof Element
                    ? rootOverride
                    : getDocumentsRoot();
                return typeof window.omoIsPvApplicationTabContext === 'function'
                    && window.omoIsPvApplicationTabContext(root);
            }

            function isPvDocumentBlockedInLocalNavigation(documentItem, rootOverride) {
                return useLocalDrawerNavigation(rootOverride)
                    && String(documentItem && documentItem.documentType ? documentItem.documentType : '').trim().toLowerCase() === 'pv'
                    && documentItem.canOpenInPvApplicationTab !== true;
            }

            function buildDocumentsPanelUrl(root) {
                if (!root) {
                    return '/omo/api/documents/index.php';
                }

                const normalizeDocumentScope = function (scopeValue) {
                    const normalizedScope = String(scopeValue || '').trim().toLowerCase();
                    if (normalizedScope === 'global') {
                        return 'descendants';
                    }
                    return normalizedScope === 'children' || normalizedScope === 'descendants' ? normalizedScope : 'contextual';
                };
                const organizationId = Number(root.getAttribute('data-omo-document-oid') || 0);
                const holonId = Number(root.getAttribute('data-omo-document-cid') || 0);
                const scope = normalizeDocumentScope(root.getAttribute('data-omo-document-scope') || 'contextual');
                const query = [];

                if (organizationId > 0) {
                    query.push('oid=' + encodeURIComponent(String(organizationId)));
                }

                if (holonId > 0) {
                    query.push('cid=' + encodeURIComponent(String(holonId)));
                }

                query.push('document_scope=' + encodeURIComponent(scope));

                query.push('_=' + String(Date.now()));

                return '/omo/api/documents/index.php' + (query.length > 0 ? '?' + query.join('&') : '');
            }

            function getDocumentsPayloadItems(rootOverride) {
                const root = rootOverride instanceof Element
                    ? rootOverride
                    : getDocumentsRoot();
                const dataNode = root ? root.querySelector('[data-omo-documents-data]') : null;

                if (!dataNode) {
                    return [];
                }

                try {
                    const payload = JSON.parse(dataNode.textContent || '{}');
                    return Array.isArray(payload.documents) ? payload.documents : [];
                } catch (error) {
                    return [];
                }
            }

            function findDocumentPayloadItemById(documentId, rootOverride) {
                const resolvedDocumentId = Number(documentId || 0);
                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                    return null;
                }

                return getDocumentsPayloadItems(rootOverride).find(function (item) {
                    return Number(item && item.id ? item.id : 0) === resolvedDocumentId;
                }) || null;
            }

            function executeFetchedScripts(container) {
        return window.commonExecuteFragmentScripts(container);
    }

            function openEditorDrawer(url, title, description) {
                const root = getDocumentsRoot();
                const drawer = root ? root.querySelector('[data-omo-document-detail-drawer]') : null;
                const body = drawer ? drawer.querySelector('[data-omo-document-detail-body]') : null;
                const titleNode = drawer ? drawer.querySelector('[data-omo-document-detail-title]') : null;
                const descriptionNode = drawer ? drawer.querySelector('[data-omo-document-detail-description]') : null;
                const drawerController = drawer && typeof window.omoCreateSubdrawerController === 'function'
                    ? (drawer.__omoSubdrawerController || window.omoCreateSubdrawerController({ drawer: drawer }))
                    : null;
                const targetUrl = String(url || '').trim();

                if (!drawer || !body || targetUrl === '') {
                    return;
                }

                if (drawer.dataset.omoDocumentDrawerMode === 'edit' && typeof cleanupDocumentEditorDrawer === 'function') {
                    cleanupDocumentEditorDrawer(drawer);
                }

                if (drawerController) {
                    drawer.__omoSubdrawerController = drawerController;
                    window.omoDocumentsEditorDrawer = drawerController;
                    drawerController.setHeader({
                        title: String(title || pageConfig.documentsDrawerEditorTitle).trim() || pageConfig.documentsDrawerEditorTitle,
                        description: String(description || pageConfig.documentsDrawerEditorDescription).trim()
                            || pageConfig.documentsDrawerEditorDescription,
                        actions: []
                    });
                }

                try {
                    const editorUrl = new URL(targetUrl, window.location.origin);
                    const documentId = Number(editorUrl.searchParams.get('id') || 0);
                    drawer.dataset.omoDocumentDrawerDocumentId = Number.isInteger(documentId) && documentId > 0
                        ? String(documentId)
                        : '';
                } catch (error) {
                    drawer.dataset.omoDocumentDrawerDocumentId = '';
                }
                drawer.dataset.omoDocumentDrawerMode = 'edit';

                if (titleNode) {
                    titleNode.textContent = String(title || pageConfig.documentsDrawerEditorTitle).trim() || pageConfig.documentsDrawerEditorTitle;
                }

                if (descriptionNode) {
                    descriptionNode.textContent = String(description || pageConfig.documentsDrawerEditorDescription).trim()
                        || pageConfig.documentsDrawerEditorDescription;
                }

                body.innerHTML = window.getSkeleton
                    ? getSkeleton('panel')
                    : ("<div class=\"loading\">" + pageConfig.documentsActionLoading + "</div>");

                if (drawerController && typeof drawerController.open === 'function') {
                    drawerController.open();
                } else {
                    drawer.hidden = false;
                    void drawer.offsetWidth;
                    requestAnimationFrame(function () {
                        drawer.classList.add('is-open');
                    });
                }

                fetch(targetUrl, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    cache: 'no-store'
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('document_editor_load_failed');
                        }

                        return response.text();
                    })
                    .then(function (html) {
                        const temp = document.createElement('div');
                        temp.innerHTML = html;
                        body.innerHTML = html;
                        if (drawerController) {
                            drawerController.applyContentHeader(body);
                        }
                        return executeFetchedScripts(body);
                    })
                    .catch(function () {
                        body.innerHTML = ("<div class=\"omo-empty-state\">" + pageConfig.documentsErrorLoadEditor + "</div>");
                    });
            }

            function buildDocumentEditorUrl(documentId, rootOverride) {
                const resolvedDocumentId = Number(documentId || 0);
                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                    return '';
                }

                const root = rootOverride instanceof Element
                    ? rootOverride
                    : getDocumentsRoot();
                const organizationId = Number(root && root.getAttribute('data-omo-document-oid') || 0);
                const holonId = Number(root && root.getAttribute('data-omo-document-cid') || 0);

                if (!Number.isInteger(organizationId) || organizationId <= 0) {
                    return '';
                }

                let url = '/omo/api/documents/create.php?oid=' + encodeURIComponent(String(organizationId));
                if (Number.isInteger(holonId) && holonId > 0) {
                    url += '&cid=' + encodeURIComponent(String(holonId));
                }

                url += '&id=' + encodeURIComponent(String(resolvedDocumentId));
                return url;
            }

            function buildDocumentDetailUrl(documentId, rootOverride) {
                const resolvedDocumentId = Number(documentId || 0);
                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                    return '';
                }

                const root = rootOverride instanceof Element
                    ? rootOverride
                    : getDocumentsRoot();
                const organizationId = Number(root && root.getAttribute('data-omo-document-oid') || 0);
                const holonId = Number(root && root.getAttribute('data-omo-document-cid') || 0);

                if (!Number.isInteger(organizationId) || organizationId <= 0) {
                    return '';
                }

                let url = '/omo/api/documents/detail.php?oid=' + encodeURIComponent(String(organizationId));
                if (Number.isInteger(holonId) && holonId > 0) {
                    url += '&cid=' + encodeURIComponent(String(holonId));
                }

                url += '&id=' + encodeURIComponent(String(resolvedDocumentId));
                return url;
            }

            function openExternalDocumentWindow(documentItem) {
                const externalUrl = documentItem && documentItem.externalUrl
                    ? String(documentItem.externalUrl).trim()
                    : '';

                if (externalUrl === '') {
                    return false;
                }

                const link = document.createElement('a');
                link.href = externalUrl;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                link.remove();

                return true;
            }

            function normalizeDocumentOpenMode(value) {
                return String(value || '').trim().toLowerCase() === 'edit'
                    ? 'edit'
                    : 'detail';
            }

            function buildDocumentRouteToken(documentId, mode) {
                const resolvedDocumentId = Number(documentId || 0);
                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                    return null;
                }

                if (typeof window.omoBuildDocumentRouteToken === 'function') {
                    return window.omoBuildDocumentRouteToken(resolvedDocumentId, normalizeDocumentOpenMode(mode));
                }

                return normalizeDocumentOpenMode(mode) === 'edit'
                    ? ('documents-de' + String(resolvedDocumentId))
                    : ('documents-d' + String(resolvedDocumentId));
            }

            window.omoOpenDocumentMergePopup = function (documentIds) {
                const resolvedDocumentIds = (Array.isArray(documentIds) ? documentIds : [])
                    .map(function (value) { return Number(value || 0); })
                    .filter(function (value, index, values) {
                        return Number.isInteger(value) && value > 0 && values.indexOf(value) === index;
                    });
                if (resolvedDocumentIds.length < 2 || typeof window.commonTopbarOpenModal !== 'function') {
                    return;
                }
                window.commonTopbarOpenModal(
                    pageConfig.documentsSelectionMerge,
                    '/omo/api/documents/merge.php?ids=' + encodeURIComponent(resolvedDocumentIds.join(',')),
                    'fetch'
                );
            };

            window.omoOpenDocumentMovePopup = function (documentId, documentIds) {
                const resolvedDocumentId = Number(documentId || 0);
                const resolvedDocumentIds = (Array.isArray(documentIds) ? documentIds : [resolvedDocumentId])
                    .map(function (value) { return Number(value || 0); })
                    .filter(function (value, index, values) {
                        return Number.isInteger(value) && value > 0 && values.indexOf(value) === index;
                    });

                if (resolvedDocumentIds.length === 0) {
                    return;
                }

                if (resolvedDocumentIds.length > 1 && typeof window.commonTopbarOpenModal === 'function') {
                    window.commonTopbarOpenModal(
                        pageConfig.documentsSelectionMove,
                        '/omo/api/documents/move.php?ids=' + encodeURIComponent(resolvedDocumentIds.join(',')),
                        'fetch'
                    );
                    return;
                }

                const singleDocumentId = resolvedDocumentIds[0];

                if (typeof window.omoOpenPopupHashState === 'function') {
                    window.omoOpenPopupHashState('document-move', singleDocumentId);
                    return;
                }

                if (typeof window.commonTopbarOpenModal === 'function') {
                    window.commonTopbarOpenModal(
                        'Déplacer',
                        '/omo/api/documents/move.php?id=' + encodeURIComponent(String(singleDocumentId)),
                        'fetch'
                    );
                }
            };

            function openDocumentSharePopup(documentId) {
                const resolvedDocumentId = Number(documentId || 0);

                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                    return;
                }

                if (typeof window.commonTopbarOpenModal === 'function') {
                    window.commonTopbarOpenModal(
                        'Partager le document',
                        '/omo/api/documents/share_popup.php?id=' + encodeURIComponent(String(resolvedDocumentId)),
                        'fetch'
                    );
                }
            }

            const cleanupDocumentEditorDrawer = function (drawer) {
                if (!(drawer instanceof Element) || drawer.dataset.omoDocumentDrawerMode !== 'edit') {
                    return;
                }

                window.dispatchEvent(new CustomEvent('omo-document-editor-drawer-close'));
                drawer.dataset.omoDocumentDrawerMode = '';
            };

            window.omoCloseDocumentEditorDrawer = function (options) {
                const settings = options && typeof options === 'object'
                    ? options
                    : {};
                const root = getDocumentsRoot();
                const drawer = root ? root.querySelector('[data-omo-document-detail-drawer]') : null;
                const documentId = Number(drawer && drawer.dataset.omoDocumentDrawerDocumentId || 0);
                const hashState = typeof window.omoParsePopupHashState === 'function'
                    ? window.omoParsePopupHashState()
                    : null;
                const routeToken = hashState && hashState.routeToken ? String(hashState.routeToken) : '';
                const editingDocumentMatch = routeToken.match(/^(?:documents|document)-de(\d+)$/i);

                if (!drawer) {
                    return;
                }

                cleanupDocumentEditorDrawer(drawer);

                if (settings.returnToDetail === true && Number.isInteger(documentId) && documentId > 0) {
                    if (!useLocalDrawerNavigation(root) && editingDocumentMatch && settings.force !== true && typeof window.omoOpenDrawerHashState === 'function') {
                        window.omoOpenDrawerHashState('documents-d' + editingDocumentMatch[1]);
                        return;
                    }

                    const documentItem = findDocumentPayloadItemById(documentId, root) || {
                        id: documentId,
                        contextUrl: buildDocumentDetailUrl(documentId, root)
                    };
                    drawer.dataset.omoDocumentDrawerMode = 'detail';
                    window.omoOpenDocumentDetailByPayload(documentItem, root);
                    return;
                }

                if (settings.preserveDrawer === true) {
                    return;
                }

                if (!useLocalDrawerNavigation(root) && settings.force !== true && editingDocumentMatch && typeof window.omoOpenDrawerHashState === 'function') {
                    window.omoOpenDrawerHashState('documents');
                }

                window.omoCloseDocumentDetailDrawer({ force: true });
            };

            window.omoRefreshDocumentsPanel = function () {
                const root = getDocumentsRoot();
                if (!root || typeof window.omoReplaceFetchedPanelRoot !== 'function') {
                    return Promise.resolve(null);
                }

                return window.omoReplaceFetchedPanelRoot({
                    rootSelector: '#omo-documents-root',
                    currentRoot: root,
                    url: buildDocumentsPanelUrl(root),
                    setLoadingState: function (isLoading) {
                        root.classList.toggle('is-loading', !!isLoading);
                    }
                });
            };

            window.omoOpenDocumentEditorDrawer = function (url, title, description) {
                openEditorDrawer(url, title, description);
            };

            window.omoCloseDocumentPvPreparationDrawer = function (options) {
                if (typeof window.omoCloseExternalPanelDrawer !== 'function') {
                    return;
                }

                window.omoCloseExternalPanelDrawer(options && typeof options === 'object'
                    ? options
                    : {});
            };

            window.omoPreserveDocumentPvPreparationDrawer = function () {
                if (typeof window.omoPeekPersistentExternalPanelDrawer !== 'function') {
                    return false;
                }

                return window.omoPeekPersistentExternalPanelDrawer({
                    persistKeyPrefix: 'omo-pv-preparation-',
                    contentSelector: '[data-omo-pv-editor-root]'
                }) === true;
            };

            window.omoCloseDocumentDetailDrawer = function (options) {
                const settings = options && typeof options === 'object'
                    ? options
                    : {};
                const hashState = typeof window.omoParsePopupHashState === 'function'
                    ? window.omoParsePopupHashState()
                    : null;
                const routeToken = hashState && hashState.routeToken ? String(hashState.routeToken) : '';
                if (!useLocalDrawerNavigation() && settings.force !== true && /^(?:documents|document)-(?:d(?:e)?)?\d+$/i.test(routeToken) && typeof window.omoOpenDrawerHashState === 'function') {
                    window.omoOpenDrawerHashState('documents');
                }

                const root = getDocumentsRoot();
                const drawer = root ? root.querySelector('[data-omo-document-detail-drawer]') : null;
                const body = drawer ? drawer.querySelector('[data-omo-document-detail-body]') : null;

                if (!drawer) {
                    return;
                }

                cleanupDocumentEditorDrawer(drawer);
                drawer.dataset.omoDocumentDrawerMode = '';
                drawer.dataset.omoDocumentDrawerDocumentId = '';
                drawer.classList.remove('is-open');
                window.setTimeout(function () {
                    if (!drawer.classList.contains('is-open')) {
                        drawer.hidden = true;
                        if (body) {
                            body.innerHTML = '';
                        }
                    }
                }, 200);
            };

            const syncDocumentDetailDrawerMetadata = function (sourceNode, drawer, fallbackTitle, fallbackDescription) {
                if (!(drawer instanceof Element) || !(sourceNode instanceof Element)) {
                    return;
                }

                const titleNode = drawer.querySelector('[data-omo-document-detail-title]');
                const descriptionNode = drawer.querySelector('[data-omo-document-detail-description]');
                const metadataNode = sourceNode.matches('[data-omo-document-drawer-title], [data-omo-document-drawer-description]')
                    ? sourceNode
                    : sourceNode.querySelector('[data-omo-document-drawer-title], [data-omo-document-drawer-description]');
                const resolvedTitle = metadataNode
                    ? String(metadataNode.getAttribute('data-omo-document-drawer-title') || '').trim()
                    : '';
                const resolvedDescription = metadataNode
                    ? String(metadataNode.getAttribute('data-omo-document-drawer-description') || '').trim()
                    : '';

                if (titleNode) {
                    titleNode.textContent = resolvedTitle !== ''
                        ? resolvedTitle
                        : String(fallbackTitle || '').trim() || 'Détail du document';
                }

                if (descriptionNode) {
                    descriptionNode.textContent = resolvedDescription !== ''
                        ? resolvedDescription
                        : String(fallbackDescription || '').trim()
                            || pageConfig.documentsDrawerDetailDescription;
                }
            };

            window.omoOpenDocumentPvPreparationByPayload = function (documentItem, rootOverride) {
                const preparationUrl = String(documentItem && documentItem.pvPreparationUrl ? documentItem.pvPreparationUrl : '').trim();
                const documentId = Number(documentItem && documentItem.id ? documentItem.id : 0);
                const title = String(documentItem && documentItem.title ? documentItem.title : '').trim();
                const fullDate = String(documentItem && documentItem.fullDateLabel ? documentItem.fullDateLabel : '').trim();
                const hasUpcomingPvEvent = !!(documentItem && documentItem.hasUpcomingPvEvent);
                const documentsRoot = rootOverride instanceof Element ? rootOverride : getDocumentsRoot();

                if (useLocalDrawerNavigation(documentsRoot)) {
                    return false;
                }

                if (
                    preparationUrl === ''
                    || !Number.isInteger(documentId)
                    || documentId <= 0
                    || typeof window.omoOpenExternalPanelDrawer !== 'function'
                ) {
                    return false;
                }

                window.omoCloseDocumentEditorDrawer({ force: true });
                window.omoCloseDocumentDetailDrawer({ force: true });

                return window.omoOpenExternalPanelDrawer({
                    url: preparationUrl,
                    mode: 'fetch',
                    title: title !== '' ? title : (hasUpcomingPvEvent ? 'Préparation du PV' : 'Édition du PV'),
                    description: hasUpcomingPvEvent
                        ? (fullDate !== ''
                            ? 'Préparation ouverte avant la réunion du ' + fullDate + '.'
                            : 'Préparation du PV avant la réunion.')
                        : '',
                    variant: 'top-sheet',
                    hideHeader: true,
                    persistKey: 'omo-pv-preparation-' + String(documentId),
                    keepMountedOnClose: true
                }) === true;
            };

            window.omoOpenDocumentDetailByPayload = function (documentItem, rootOverride) {
                const root = rootOverride instanceof Element
                    ? rootOverride
                    : getDocumentsRoot();

                if (isPvDocumentBlockedInLocalNavigation(documentItem, root)) {
                    return false;
                }

                if (documentItem && documentItem.openInNewWindow && documentItem.externalUrl) {
                    if (openExternalDocumentWindow(documentItem)) {
                        return true;
                    }
                }

                const drawer = root ? root.querySelector('[data-omo-document-detail-drawer]') : null;
                const body = drawer ? drawer.querySelector('[data-omo-document-detail-body]') : null;
                const titleNode = drawer ? drawer.querySelector('[data-omo-document-detail-title]') : null;
                const descriptionNode = drawer ? drawer.querySelector('[data-omo-document-detail-description]') : null;
                const detailUrl = String(documentItem && documentItem.contextUrl ? documentItem.contextUrl : '').trim();
                const title = String(documentItem && documentItem.title ? documentItem.title : '').trim();
                const fullDate = String(documentItem && documentItem.fullDateLabel ? documentItem.fullDateLabel : '').trim();

                if (!drawer || !body || detailUrl === '' || (documentItem && documentItem.isFolder)) {
                    return false;
                }

                const requestedDocumentId = Number(documentItem && documentItem.id ? documentItem.id : 0);
                const cachedDocumentId = Number(drawer.dataset.omoDocumentDrawerDocumentId || 0);
                const hasRenderedDetail = body.childElementCount > 0
                    && !body.querySelector('.loading, .skeleton');
                if (
                    Number.isInteger(requestedDocumentId)
                    && requestedDocumentId > 0
                    && cachedDocumentId === requestedDocumentId
                    && drawer.dataset.omoDocumentDrawerMode === 'detail'
                    && hasRenderedDetail
                ) {
                    const drawerController = drawer.__omoSubdrawerController;
                    if (drawerController && typeof drawerController.open === 'function') {
                        drawerController.open();
                    } else {
                        drawer.hidden = false;
                        requestAnimationFrame(function () {
                            drawer.classList.add('is-open');
                        });
                    }
                    return true;
                }

                if (!useLocalDrawerNavigation(root) && !window.omoPreserveDocumentPvPreparationDrawer()) {
                    window.omoCloseDocumentPvPreparationDrawer({ force: true });
                }

                cleanupDocumentEditorDrawer(drawer);
                drawer.dataset.omoDocumentDrawerMode = 'detail';
                drawer.dataset.omoDocumentDrawerDocumentId = String(documentItem && documentItem.id ? documentItem.id : '');

                if (titleNode) {
                    titleNode.textContent = title !== '' ? title : 'Détail du document';
                }

                if (descriptionNode) {
                    descriptionNode.textContent = fullDate !== ''
                        ? 'Document créé le ' + fullDate + '.'
                        : pageConfig.documentsDrawerDetailDescription;
                }

                body.innerHTML = window.getSkeleton
                    ? getSkeleton('panel')
                    : ("<div class=\"loading\">" + pageConfig.documentsActionLoading + "</div>");

                drawer.hidden = false;
                requestAnimationFrame(function () {
                    drawer.classList.add('is-open');
                });

                fetch(detailUrl, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    cache: 'no-store'
                })
                    .then(function (response) {
                        return response.text().then(function (html) {
                            return {
                                ok: response.ok,
                                html: html
                            };
                        });
                    })
                    .then(function (result) {
                        const html = typeof result.html === 'string' ? result.html : '';
                        if (html.trim() === '') {
                            if (!result.ok) {
                                throw new Error('document_detail_load_failed');
                            }

                            body.innerHTML = '';
                            return;
                        }

                        const temp = document.createElement('div');
                        temp.innerHTML = html;
                        body.innerHTML = html;
                        syncDocumentDetailDrawerMetadata(temp, drawer, title, descriptionNode ? descriptionNode.textContent : '');
                        if (drawer.__omoSubdrawerController) {
                            drawer.__omoSubdrawerController.applyContentHeader(body);
                        }
                        return executeFetchedScripts(body);
                    })
                    .catch(function () {
                        body.innerHTML = ("<div class=\"omo-empty-state\">" + pageConfig.documentsErrorLoadDocument + "</div>");
                    });

                return true;
            };

            window.omoOpenDocumentEditorByPayload = function (documentItem, rootOverride) {
                const root = rootOverride instanceof Element
                    ? rootOverride
                    : getDocumentsRoot();

                if (!documentItem || !documentItem.canEdit || isPvDocumentBlockedInLocalNavigation(documentItem, root)) {
                    return false;
                }

                if (String(documentItem.documentType || '').trim().toLowerCase() === 'pv') {
                    return window.omoOpenDocumentPvPreparationByPayload(documentItem, root);
                }

                if (!documentItem.editUrl) {
                    return false;
                }

                if (!useLocalDrawerNavigation(root) && !window.omoPreserveDocumentPvPreparationDrawer()) {
                    window.omoCloseDocumentPvPreparationDrawer({ force: true });
                }

                openEditorDrawer(
                    String(documentItem.editUrl || '').trim(),
                    pageConfig.text,
                    pageConfig.text2
                );
                return true;
            };

            window.omoOpenDocumentDetailFromTrigger = function (trigger, event) {
                if (!(trigger instanceof Element)) {
                    return true;
                }

                const documentId = Number(trigger.getAttribute('data-omo-document-id') || 0);
                const documentPayload = {
                    id: documentId,
                    contextUrl: String(trigger.getAttribute('data-omo-document-context-url') || '').trim(),
                    externalUrl: String(trigger.getAttribute('data-omo-document-external-url') || '').trim(),
                    openInNewWindow: String(trigger.getAttribute('data-omo-document-open-in-new-window') || '').trim() === '1',
                    documentType: String(trigger.getAttribute('data-omo-document-type') || '').trim(),
                    pvPreparationUrl: String(trigger.getAttribute('data-omo-document-pv-editor-url') || '').trim(),
                    canOpenInPvApplicationTab: String(trigger.getAttribute('data-omo-document-can-open-in-pv-tab') || '').trim() === '1',
                    title: String(trigger.getAttribute('data-omo-document-title') || '').trim(),
                    fullDateLabel: String(trigger.getAttribute('data-omo-document-full-date') || '').trim()
                };

                if (event) {
                    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return true;
                    }
                    event.preventDefault();
                }

                const root = trigger.closest('#omo-documents-root') || getDocumentsRoot();
                if (isPvDocumentBlockedInLocalNavigation(documentPayload, root)) {
                    return false;
                }

                if (documentPayload.openInNewWindow && documentPayload.externalUrl !== '') {
                    return openExternalDocumentWindow(documentPayload) ? false : true;
                }

                if (
                    documentPayload.documentType.toLowerCase() === 'pv'
                    && documentPayload.pvPreparationUrl !== ''
                    && window.omoOpenDocumentPvPreparationByPayload(documentPayload)
                ) {
                    return false;
                }

                const routeToken = buildDocumentRouteToken(documentId, 'detail');
                const hashState = typeof window.omoParsePopupHashState === 'function'
                    ? window.omoParsePopupHashState()
                    : null;
                const currentRouteToken = hashState && hashState.routeToken ? String(hashState.routeToken) : '';

                if (!useLocalDrawerNavigation(root) && routeToken && typeof window.omoOpenDrawerHashState === 'function' && routeToken !== currentRouteToken) {
                    window.omoOpenDrawerHashState(routeToken);
                    return false;
                }

                const opened = window.omoOpenDocumentDetailByPayload(documentPayload, root);

                if (!opened) {
                    return true;
                }

                return false;
            };

            window.omoOpenDocumentEditorFromDocumentId = function (documentId) {
                const resolvedDocumentId = Number(documentId || 0);
                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                    return false;
                }

                const root = getDocumentsRoot();
                const documentItem = findDocumentPayloadItemById(resolvedDocumentId, root);
                if (documentItem && isPvDocumentBlockedInLocalNavigation(documentItem, root)) {
                    return false;
                }
                if (
                    documentItem
                    && String(documentItem.documentType || '').trim().toLowerCase() === 'pv'
                    && window.omoOpenDocumentEditorByPayload(documentItem, root)
                ) {
                    return true;
                }

                const routeToken = buildDocumentRouteToken(resolvedDocumentId, 'edit');
                const hashState = typeof window.omoParsePopupHashState === 'function'
                    ? window.omoParsePopupHashState()
                    : null;
                const currentRouteToken = hashState && hashState.routeToken ? String(hashState.routeToken) : '';

                if (!useLocalDrawerNavigation(root) && routeToken && typeof window.omoOpenDrawerHashState === 'function' && routeToken !== currentRouteToken) {
                    window.omoOpenDrawerHashState(routeToken);
                    return true;
                }

                if (documentItem && window.omoOpenDocumentEditorByPayload(documentItem, root)) {
                    return true;
                }

                const editUrl = buildDocumentEditorUrl(resolvedDocumentId, root);
                if (editUrl === '') {
                    return false;
                }

                openEditorDrawer(
                    editUrl,
                    pageConfig.text,
                    pageConfig.text2
                );
                return true;
            };

            const root = getDocumentsRoot();
            if (!root || root.dataset.omoDocumentsDrawerReady === '1') {
                return;
            }

            root.dataset.omoDocumentsDrawerReady = '1';
            const ownerDocument = root.ownerDocument || document;
            const floatingMenu = ownerDocument.createElement('div');
            floatingMenu.className = 'omo-documents__menu-panel generic-menu-panel generic-menu-panel--floating omo-documents__menu-panel--floating';
            floatingMenu.setAttribute('data-omo-document-floating-menu', '1');
            floatingMenu.setAttribute('role', 'menu');
            floatingMenu.hidden = true;
            ownerDocument.body.appendChild(floatingMenu);

            let activeDocumentMenuToggle = null;

            function buildDocumentMenuItem(label, attributes) {
                const button = ownerDocument.createElement('button');
                button.type = 'button';
                button.className = 'omo-documents__menu-item generic-menu-item';
                button.setAttribute('role', 'menuitem');
                button.textContent = label;

                Object.keys(attributes || {}).forEach(function (attributeName) {
                    button.setAttribute(attributeName, String(attributes[attributeName] || ''));
                });

                return button;
            }

            function buildDocumentMenuSeparator() {
                const separator = ownerDocument.createElement('div');
                separator.className = 'generic-menu-separator';
                separator.setAttribute('role', 'separator');
                return separator;
            }

            function populateDocumentMenu(toggle) {
                const documentId = Number(toggle && toggle.getAttribute('data-omo-document-menu-document-id') || 0);
                const documentTitle = String(toggle && toggle.getAttribute('data-omo-document-menu-title') || '').trim();
                const editUrl = String(toggle && toggle.getAttribute('data-omo-document-menu-edit-url') || '').trim();
                const canEdit = String(toggle && toggle.getAttribute('data-omo-document-menu-can-edit') || '') === '1';
                const canMove = String(toggle && toggle.getAttribute('data-omo-document-menu-can-move') || '') === '1';
                const canArchive = String(toggle && toggle.getAttribute('data-omo-document-menu-can-archive') || '') === '1';
                const canDelete = String(toggle && toggle.getAttribute('data-omo-document-menu-can-delete') || '') === '1';
                const isFolder = String(toggle && toggle.getAttribute('data-omo-document-menu-is-folder') || '') === '1';
                const canShare = String(toggle && toggle.getAttribute('data-omo-document-menu-can-share') || '') === '1';
                const canExportPdf = String(toggle && toggle.getAttribute('data-omo-document-menu-can-export-pdf') || '') === '1';
                const pdfExportUrl = String(toggle && toggle.getAttribute('data-omo-document-menu-pdf-url') || '').trim();
                const canManageTemplate = String(toggle && toggle.getAttribute('data-omo-document-menu-can-manage-template') || '') === '1';
                const isTemplate = String(toggle && toggle.getAttribute('data-omo-document-menu-is-template') || '') === '1';
                const fragment = ownerDocument.createDocumentFragment();

                if (canEdit && editUrl !== '') {
                    fragment.appendChild(buildDocumentMenuItem('Éditer', {
                        'data-omo-document-menu-action': 'edit',
                        'data-omo-document-edit': '1',
                        'data-omo-document-edit-id': String(documentId),
                        'data-omo-document-edit-url': editUrl,
                        'data-omo-document-edit-title': documentTitle
                    }));
                }

                if (canExportPdf && pdfExportUrl !== '') {
                    fragment.appendChild(buildDocumentMenuItem(pageConfig.documentsMenuExportPdf, {
                        'data-omo-document-menu-action': 'export-pdf',
                        'data-omo-document-export-pdf-url': pdfExportUrl
                    }));
                }

                if (canMove && Number.isInteger(documentId) && documentId > 0) {
                    fragment.appendChild(buildDocumentMenuItem('Déplacer', {
                        'data-omo-document-menu-action': 'move',
                        'data-omo-document-move': '1',
                        'data-omo-document-move-id': String(documentId)
                    }));
                }

                if (Number.isInteger(documentId) && documentId > 0 && !isFolder && canShare) {
                    fragment.appendChild(buildDocumentMenuItem('Partager', {
                        'data-omo-document-menu-action': 'share',
                        'data-omo-document-share': '1',
                        'data-omo-document-share-id': String(documentId)
                    }));
                }

                if (canManageTemplate && Number.isInteger(documentId) && documentId > 0) {
                    fragment.appendChild(buildDocumentMenuItem(
                        isTemplate
                            ? pageConfig.documentsTemplateUnmark
                            : pageConfig.documentsTemplateMark,
                        {
                            'data-omo-document-menu-action': 'template',
                            'data-omo-document-template-id': String(documentId),
                            'data-omo-document-template-state': isTemplate ? '0' : '1'
                        }
                    ));
                }

                if (
                    fragment.childElementCount > 0
                    && (canArchive || canDelete)
                    && Number.isInteger(documentId)
                    && documentId > 0
                ) {
                    fragment.appendChild(buildDocumentMenuSeparator());
                }

                if (canArchive && Number.isInteger(documentId) && documentId > 0) {
                    fragment.appendChild(buildDocumentMenuItem(pageConfig.documentsMenuArchive, {
                        'data-omo-document-menu-action': 'archive',
                        'data-omo-document-lifecycle': 'archive',
                        'data-omo-document-lifecycle-id': String(documentId)
                    }));
                }

                if (canDelete && Number.isInteger(documentId) && documentId > 0) {
                    const deleteItem = buildDocumentMenuItem(pageConfig.documentsMenuDelete, {
                        'data-omo-document-menu-action': 'delete',
                        'data-omo-document-lifecycle': 'delete',
                        'data-omo-document-lifecycle-id': String(documentId)
                    });
                    deleteItem.classList.add('generic-menu-item--danger');
                    fragment.appendChild(deleteItem);
                }

                floatingMenu.replaceChildren(fragment);
            }

            function positionDocumentMenu(toggle) {
                if (!toggle || !toggle.isConnected) {
                    closeDocumentMenus();
                    return;
                }

                floatingMenu.hidden = false;
                floatingMenu.style.visibility = 'hidden';
                floatingMenu.style.top = '0px';
                floatingMenu.style.left = '0px';

                const toggleRect = toggle.getBoundingClientRect();
                const menuRect = floatingMenu.getBoundingClientRect();
                const viewportPadding = 12;
                const gap = 8;
                let top = toggleRect.bottom + gap;
                let left = toggleRect.right - menuRect.width;

                if (top + menuRect.height > window.innerHeight - viewportPadding) {
                    top = Math.max(viewportPadding, toggleRect.top - menuRect.height - gap);
                }

                if (left + menuRect.width > window.innerWidth - viewportPadding) {
                    left = Math.max(viewportPadding, window.innerWidth - menuRect.width - viewportPadding);
                }

                if (left < viewportPadding) {
                    left = viewportPadding;
                }

                floatingMenu.style.top = String(Math.round(top)) + 'px';
                floatingMenu.style.left = String(Math.round(left)) + 'px';
                floatingMenu.style.visibility = '';
            }

            async function runDocumentLifecycleAction(actionButton) {
                const action = String(actionButton && actionButton.getAttribute('data-omo-document-lifecycle') || '').trim().toLowerCase();
                const documentId = Number(actionButton && actionButton.getAttribute('data-omo-document-lifecycle-id') || 0);
                if (!['archive', 'delete'].includes(action) || !Number.isInteger(documentId) || documentId <= 0) {
                    return false;
                }

                const message = action === 'delete'
                    ? pageConfig.documentsMenuConfirmDelete
                    : pageConfig.documentsMenuConfirmArchive;
                if (!window.confirm(message)) {
                    return true;
                }

                const response = await fetch('/omo/api/documents/lifecycle_action.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: documentId, action: action})
                });
                const payload = await response.json();
                if (!response.ok || !payload || payload.status !== true) {
                    window.omoNotify(String(payload && payload.message || pageConfig.documentsMenuActionError), 'error');
                    return true;
                }

                if (typeof window.omoRefreshDocumentsPanel === 'function') {
                    await window.omoRefreshDocumentsPanel();
                } else {
                    window.location.reload();
                }
                return true;
            }

            function runDocumentMenuAction(actionButton) {
                const action = String(actionButton && actionButton.getAttribute('data-omo-document-menu-action') || '').trim().toLowerCase();
                if (action === '') {
                    return false;
                }

                closeDocumentMenus();

                if (action === 'export-pdf') {
                    const pdfExportUrl = String(actionButton.getAttribute('data-omo-document-export-pdf-url') || '').trim();
                    if (pdfExportUrl === '') {
                        return false;
                    }

                    const downloadLink = ownerDocument.createElement('a');
                    downloadLink.href = pdfExportUrl;
                    downloadLink.download = '';
                    downloadLink.hidden = true;
                    ownerDocument.body.appendChild(downloadLink);
                    downloadLink.click();
                    downloadLink.remove();
                    return true;
                }

                if (action === 'archive' || action === 'delete') {
                    runDocumentLifecycleAction(actionButton);
                    return true;
                }

                if (action === 'move') {
                    window.omoOpenDocumentMovePopup(actionButton.getAttribute('data-omo-document-move-id'));
                    return true;
                }

                if (action === 'share') {
                    openDocumentSharePopup(actionButton.getAttribute('data-omo-document-share-id'));
                    return true;
                }

                if (action === 'template') {
                    const documentId = Number(actionButton.getAttribute('data-omo-document-template-id') || 0);
                    const isTemplate = String(actionButton.getAttribute('data-omo-document-template-state') || '') === '1';
                    if (!Number.isInteger(documentId) || documentId <= 0) {
                        return false;
                    }
                    fetch('/omo/api/documents/template_action.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: documentId, action: 'set_template', is_template: isTemplate ? 1 : 0})
                    }).then(function (response) {
                        return response.json().then(function (payload) {
                            if (!response.ok || !payload || payload.status !== true) {
                                throw new Error(String(payload && payload.message || pageConfig.documentsMenuActionError));
                            }
                            return payload;
                        });
                    }).then(function () {
                        if (typeof window.omoRefreshDocumentsPanel === 'function') {
                            return window.omoRefreshDocumentsPanel();
                        }
                        window.location.reload();
                        return null;
                    }).catch(function (error) {
                        window.omoNotify(String(error && error.message || pageConfig.documentsMenuActionError), 'error');
                    });
                    return true;
                }

                if (action !== 'edit') {
                    return false;
                }

                const documentId = Number(actionButton.getAttribute('data-omo-document-edit-id') || 0);
                if (window.omoOpenDocumentEditorFromDocumentId(documentId)) {
                    return true;
                }

                const documentItem = findDocumentPayloadItemById(documentId);
                if (documentItem && window.omoOpenDocumentEditorByPayload(documentItem, root)) {
                    return true;
                }

                const editUrl = String(actionButton.getAttribute('data-omo-document-edit-url') || '').trim();
                if (editUrl === '') {
                    return false;
                }

                openEditorDrawer(
                    editUrl,
                    pageConfig.text,
                    pageConfig.text2
                );
                return true;
            }

            function closeDocumentMenus() {
                root.querySelectorAll('[data-omo-document-menu="1"]').forEach(function (menu) {
                    menu.classList.remove('is-open');
                });

                root.querySelectorAll('[data-omo-document-menu-toggle="1"]').forEach(function (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                });

                activeDocumentMenuToggle = null;
                floatingMenu.hidden = true;
                floatingMenu.style.visibility = '';
                floatingMenu.replaceChildren();
            }

            function openDocumentMenu(toggle) {
                const parentMenu = toggle ? toggle.closest('[data-omo-document-menu="1"]') : null;
                const shouldOpen = !!toggle && (!activeDocumentMenuToggle || activeDocumentMenuToggle !== toggle || floatingMenu.hidden);

                closeDocumentMenus();

                if (!toggle || !parentMenu || !shouldOpen) {
                    return;
                }

                populateDocumentMenu(toggle);
                if (!floatingMenu.childElementCount) {
                    return;
                }

                activeDocumentMenuToggle = toggle;
                parentMenu.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
                positionDocumentMenu(toggle);
            }

            root.addEventListener('click', function (event) {
                const toggle = event.target.closest('[data-omo-document-menu-toggle="1"]');
                if (!toggle) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                openDocumentMenu(toggle);
            });

            ownerDocument.addEventListener('click', function (event) {
                const toggle = event.target.closest('[data-omo-document-menu-toggle="1"]');
                if (toggle) {
                    return;
                }

                const actionButton = event.target.closest('[data-omo-document-menu-action]');
                if (actionButton && floatingMenu.contains(actionButton)) {
                    event.preventDefault();
                    event.stopPropagation();
                    runDocumentMenuAction(actionButton);
                    return;
                }

                const editButton = event.target.closest('[data-omo-document-edit="1"]');
                if (editButton && floatingMenu.contains(editButton)) {
                    event.preventDefault();
                    event.stopPropagation();

                    closeDocumentMenus();
                    if (window.omoOpenDocumentEditorFromDocumentId(Number(editButton.getAttribute('data-omo-document-edit-id') || 0))) {
                        return;
                    }

                    openEditorDrawer(
                        String(editButton.getAttribute('data-omo-document-edit-url') || '').trim(),
                        pageConfig.text,
                        pageConfig.text2
                    );
                    return;
                }

                const moveButton = event.target.closest('[data-omo-document-move="1"]');
                if (moveButton && floatingMenu.contains(moveButton)) {
                    event.preventDefault();
                    event.stopPropagation();

                    closeDocumentMenus();
                    window.omoOpenDocumentMovePopup(moveButton.getAttribute('data-omo-document-move-id'));
                    return;
                }

                const shareButton = event.target.closest('[data-omo-document-share="1"]');
                if (shareButton && floatingMenu.contains(shareButton)) {
                    event.preventDefault();
                    event.stopPropagation();

                    closeDocumentMenus();
                    openDocumentSharePopup(shareButton.getAttribute('data-omo-document-share-id'));
                    return;
                }

                if (!event.target.closest('[data-omo-document-floating-menu="1"]')) {
                    closeDocumentMenus();
                }
            });

            ownerDocument.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !floatingMenu.hidden) {
                    closeDocumentMenus();
                }
            });

            ownerDocument.addEventListener('scroll', function () {
                if (!activeDocumentMenuToggle || floatingMenu.hidden) {
                    return;
                }

                positionDocumentMenu(activeDocumentMenuToggle);
            }, true);

            window.addEventListener('resize', function () {
                if (!activeDocumentMenuToggle || floatingMenu.hidden) {
                    return;
                }

                positionDocumentMenu(activeDocumentMenuToggle);
            });

            const documentDrawer = root.querySelector('[data-omo-document-detail-drawer]');
            if (documentDrawer) {
                documentDrawer.addEventListener('click', function (event) {
                    if (!(event.target instanceof Element) || !event.target.closest('[data-omo-document-editor-cancel]')) {
                        return;
                    }

                    event.preventDefault();
                    event.stopImmediatePropagation();
                    window.omoCloseDocumentEditorDrawer({ returnToDetail: true });
                }, true);
            }

            root.querySelectorAll('[data-omo-document-detail-close]').forEach(function (button) {
                button.addEventListener('click', function () {
                    window.omoCloseDocumentDetailDrawer();
                });
            });

            const newButton = root.querySelector('[data-omo-documents-new]');
            if (newButton) {
                newButton.addEventListener('click', function () {
                    const targetUrl = String(newButton.getAttribute('data-omo-documents-new-url') || '').trim();
                    openEditorDrawer(
                        targetUrl,
                        pageConfig.documentsDrawerEditorTitle,
                        pageConfig.documentsDrawerEditorDescription
                    );
                });
            }

            const templatePicker = root.querySelector('[data-omo-document-template-picker]');
            if (templatePicker) {
                const templateToggle = templatePicker.querySelector('[data-omo-document-template-picker-toggle]');
                const templatePanel = templatePicker.querySelector('[data-omo-document-template-picker-panel]');
                const closeTemplatePicker = function () {
                    if (templatePanel) {
                        templatePanel.hidden = true;
                    }
                    if (templateToggle) {
                        templateToggle.setAttribute('aria-expanded', 'false');
                    }
                };

                if (templateToggle && templatePanel) {
                    templateToggle.addEventListener('click', function (event) {
                        event.preventDefault();
                        const willOpen = templatePanel.hidden;
                        closeTemplatePicker();
                        templatePanel.hidden = !willOpen;
                        templateToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                    });
                }

                templatePicker.querySelectorAll('[data-omo-document-template-create]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const templateId = Number(button.getAttribute('data-omo-document-template-create') || 0);
                        if (!Number.isInteger(templateId) || templateId <= 0) {
                            return;
                        }
                        button.disabled = true;
                        fetch('/omo/api/documents/template_action.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                id: templateId,
                                action: 'duplicate',
                                oid: Number(root.getAttribute('data-omo-document-oid') || 0),
                                cid: Number(root.getAttribute('data-omo-document-cid') || 0)
                            })
                        }).then(function (response) {
                            return response.json().then(function (payload) {
                                if (!response.ok || !payload || payload.status !== true) {
                                    throw new Error(String(payload && payload.message || pageConfig.documentsMenuActionError));
                                }
                                return payload;
                            });
                        }).then(function () {
                            closeTemplatePicker();
                            if (typeof window.omoRefreshDocumentsPanel === 'function') {
                                return window.omoRefreshDocumentsPanel();
                            }
                            window.location.reload();
                            return null;
                        }).catch(function (error) {
                            if (typeof window.omoNotify === 'function') {
                                window.omoNotify(String(error && error.message || pageConfig.documentsMenuActionError), 'error');
                            }
                        }).finally(function () {
                            button.disabled = false;
                        });
                    });
                });

                ownerDocument.addEventListener('click', function (event) {
                    if (!templatePicker.contains(event.target)) {
                        closeTemplatePicker();
                    }
                });
            }
        })();
};
