window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/documents/list.js"] = function (pageConfig, pageScript) {
            
            (function () {
                window.omoDocumentsFindRoot = function () {
                    return typeof window.omoFindApplicationRoot === 'function'
                        ? window.omoFindApplicationRoot('omo-documents-root')
                        : document.getElementById('omo-documents-root');
                };
                const omoDocumentsSavedViewsStorageKey = 'omo.documents.saved-views.v2';
                const omoDocumentsLegacySavedViewsStorageKey = 'omo.documents.saved-views.v1';
                const omoDocumentsSessionViewsStorageKey = 'omo.documents.session-views.v1';
                const omoDocumentsSearchStorageKey = 'omo.documents.quick-search.v1';
                const omoDocumentsFileIconUrl = '/omo/assets/images/documents/file.png';
                const omoDocumentsDownloadIconUrl = '/omo/assets/images/documents/download.png';
                const omoDocumentsFolderOpenIconUrl = '/omo/assets/images/documents/folder-open.png';
                const omoDocumentsFolderClosedIconUrl = '/omo/assets/images/documents/folder-closed.png';
                const omoDocumentsLinkIconUrl = '/omo/assets/images/documents/link.png';
                const omoDocumentsPvIconUrl = '/omo/assets/images/documents/pv.png';
                 const omoDocumentsEtherpadIconUrl = '/omo/assets/images/documents/collaborative.png';
                 const omoDocumentsEthercalcIconUrl = '/omo/assets/images/documents/spreadsheet.png';
                 const omoDocumentsWhiteboardIconUrl = '/omo/assets/images/documents/whiteboard.png';
                const omoDocumentsStoredKindIconUrls = {
                    image: '/omo/assets/images/documents/image.png',
                    video: '/omo/assets/images/documents/video.png',
                    audio: '/omo/assets/images/documents/audio.png',
                    text: '/omo/assets/images/documents/text.png',
                    spreadsheet: '/omo/assets/images/documents/spreadsheet-kind.png',
                    presentation: '/omo/assets/images/documents/presentation.png',
                    drawing: '/omo/assets/images/documents/drawing.png'
                };
                const omoDocumentsPvType = 'pv';
                 const omoDocumentsEtherpadType = 'etherpad';
                 const omoDocumentsEthercalcType = 'ethercalc';
                 const omoDocumentsWhiteboardType = 'whiteboard';
                const omoDocumentsMissingUploadedFileLabel = pageConfig.omoDocumentsMissingUploadedFileLabel;
				const omoDocumentsNextcloudInitialLabel = pageConfig.omoDocumentsNextcloudInitialLabel;
				const omoDocumentsNextcloudLoadingLabel = pageConfig.omoDocumentsNextcloudLoadingLabel;
				const omoDocumentsNextcloudErrorLabel = pageConfig.omoDocumentsNextcloudErrorLabel;
				const omoDocumentsNextcloudEmptyLabel = pageConfig.omoDocumentsNextcloudEmptyLabel;
				const omoDocumentsNextcloudFolderLabel = pageConfig.omoDocumentsNextcloudFolderLabel;
                const omoDocumentsFolderUnloadedLabel = pageConfig.omoDocumentsFolderUnloadedLabel;
                const omoDocumentsFolderLoadingLabel = pageConfig.omoDocumentsFolderLoadingLabel;
                const omoDocumentsFolderEmptyLabel = pageConfig.omoDocumentsFolderEmptyLabel;
                const omoDocumentsFolderErrorLabel = pageConfig.omoDocumentsFolderErrorLabel;
                 const omoDocumentsEtherpadIconLabel = pageConfig.omoDocumentsEtherpadIconLabel;
                 const omoDocumentsEthercalcIconLabel = pageConfig.omoDocumentsEthercalcIconLabel;
                 const omoDocumentsWhiteboardIconLabel = pageConfig.omoDocumentsWhiteboardIconLabel;

                const omoDocumentsGetFolderIconUrl = function (isExpanded) {
                    return isExpanded ? omoDocumentsFolderOpenIconUrl : omoDocumentsFolderClosedIconUrl;
                };

                const omoDocumentsGetIconUrl = function (documentItem) {
                    if (documentItem && documentItem.isFolder) {
                        return omoDocumentsGetFolderIconUrl(false);
                    }

                    if (documentItem && documentItem.isExternalLink) {
                        return omoDocumentsLinkIconUrl;
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === 'uploaded_file') {
                        const storedFileKind = String(documentItem.storedFileKind || '').trim().toLowerCase();
                        return omoDocumentsStoredKindIconUrls[storedFileKind] || omoDocumentsDownloadIconUrl;
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsPvType) {
                        return omoDocumentsPvIconUrl;
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsEtherpadType) {
                        return omoDocumentsEtherpadIconUrl;
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsEthercalcType) {
                        return omoDocumentsEthercalcIconUrl;
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsWhiteboardType) {
                        return omoDocumentsWhiteboardIconUrl;
                    }

                    return omoDocumentsFileIconUrl;
                };

                const omoDocumentsGetIconAlt = function (documentItem) {
                    if (documentItem && documentItem.isFolder) {
                        return 'Dossier';
                    }

                    if (documentItem && documentItem.isExternalLink) {
                        return 'Lien externe';
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === 'uploaded_file') {
                        const storedFileKind = String(documentItem.storedFileKind || '').trim().toLowerCase();
                        const storedKindLabels = {
                            image: pageConfig.image,
                            video: pageConfig.video,
                            audio: pageConfig.audio,
                            text: pageConfig.text,
                            spreadsheet: pageConfig.spreadsheet,
                            presentation: pageConfig.presentation,
                            drawing: pageConfig.drawing
                        };
                        return storedKindLabels[storedFileKind] || 'Fichier a telecharger';
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsPvType) {
                        return 'Proces verbal';
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsEtherpadType) {
                        return omoDocumentsEtherpadIconLabel;
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsEthercalcType) {
                        return omoDocumentsEthercalcIconLabel;
                    }

                    if (documentItem && String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsWhiteboardType) {
                        return omoDocumentsWhiteboardIconLabel;
                    }

                    return 'Fichier';
                };

                const omoDocumentsNormalizeSortPreference = function (value) {
                    const normalizedValue = String(value || '').trim().toLowerCase();

                    if (normalizedValue === 'alpha') {
                        return 'alpha';
                    }

                    if (normalizedValue === 'created' || normalizedValue === 'creation') {
                        return 'created';
                    }

                    return 'updated';
                };

                const omoDocumentsNormalizeDensityPreference = function (value) {
                    return String(value || '').trim().toLowerCase() === 'compact'
                        ? 'compact'
                        : 'detail';
                };

                const omoDocumentsNormalizeScope = function (value) {
                    const normalizedScope = String(value || '').trim().toLowerCase();
                    if (normalizedScope === 'global') {
                        return 'descendants';
                    }
                    return normalizedScope === 'children' || normalizedScope === 'descendants' ? normalizedScope : 'contextual';
                };

                const omoDocumentsReadSessionCookie = function (name) {
                    if (typeof window.omoReadCookie === 'function') {
                        return String(window.omoReadCookie(name) || '');
                    }

                    const cookiePrefix = encodeURIComponent(name) + '=';
                    const cookies = document.cookie ? document.cookie.split(';') : [];

                    for (let index = 0; index < cookies.length; index += 1) {
                        const cookie = cookies[index].trim();

                        if (cookie.indexOf(cookiePrefix) === 0) {
                            return decodeURIComponent(cookie.slice(cookiePrefix.length));
                        }
                    }

                    return '';
                };

                const omoDocumentsWriteSessionCookie = function (name, value) {
                    document.cookie = [
                        encodeURIComponent(name) + '=' + encodeURIComponent(String(value || '')),
                        'path=/',
                        'SameSite=Lax'
                    ].join('; ');
                };

                const omoDocumentsBuildFolderStateCookieName = function (organizationId, holonId, scope) {
                    const normalizedOrganizationId = Number(organizationId || 0) > 0
                        ? String(Number(organizationId || 0))
                        : '0';
                    const normalizedHolonId = Number(holonId || 0) > 0
                        ? String(Number(holonId || 0))
                        : '0';
                    const normalizedScope = omoDocumentsNormalizeScope(scope);

                    return 'omo_documents_folders_' + normalizedOrganizationId + '_' + normalizedHolonId + '_' + normalizedScope;
                };

                const omoDocumentsParseFolderState = function (rawValue) {
                    const folderIds = new Set();

                    String(rawValue || '')
                        .split(',')
                        .forEach(function (part) {
                            const folderId = Number(String(part || '').trim());
                            if (Number.isInteger(folderId) && folderId > 0) {
                                folderIds.add(folderId);
                            }
                        });

                    return folderIds;
                };

                const omoDocumentsGetPreferencesContextKey = function (panel) {
                    const regularKey = String(panel && panel.getAttribute('data-omo-document-oid') || '0')
                        + ':' + String(panel && panel.getAttribute('data-omo-document-cid') || '0');
                    return typeof window.omoApplicationViewPreferencesGetStorageContextKey === 'function'
                        ? window.omoApplicationViewPreferencesGetStorageContextKey(panel, regularKey)
                        : regularKey;
                };

                const omoDocumentsCreatePreferences = function (preferences) {
                    return {
                        scope: omoDocumentsNormalizeScope(preferences && preferences.scope),
                        sort: omoDocumentsNormalizeSortPreference(preferences && preferences.sort),
                        density: omoDocumentsNormalizeDensityPreference(preferences && preferences.density)
                    };
                };

                const omoDocumentsGetSavedViewsStore = function () {
                    try {
                        const rawValue = window.localStorage.getItem(omoDocumentsSavedViewsStorageKey);
                        const savedViews = rawValue ? JSON.parse(rawValue) : null;
                        if (savedViews && typeof savedViews === 'object' && savedViews.contexts && typeof savedViews.contexts === 'object') {
                            return {
                                defaultView: savedViews.defaultView && typeof savedViews.defaultView === 'object'
                                    ? savedViews.defaultView
                                    : null,
                                contexts: savedViews.contexts
                            };
                        }

                        const legacyValue = window.localStorage.getItem(omoDocumentsLegacySavedViewsStorageKey);
                        const legacyViews = legacyValue ? JSON.parse(legacyValue) : null;
                        return {
                            defaultView: null,
                            contexts: legacyViews && typeof legacyViews === 'object' ? legacyViews : {}
                        };
                    } catch (error) {
                        return {defaultView: null, contexts: {}};
                    }
                };

                const omoDocumentsSaveViewsStore = function (store) {
                    try {
                        window.localStorage.setItem(omoDocumentsSavedViewsStorageKey, JSON.stringify({
                            defaultView: store.defaultView && typeof store.defaultView === 'object'
                                ? store.defaultView
                                : null,
                            contexts: store.contexts && typeof store.contexts === 'object' ? store.contexts : {}
                        }));
                    } catch (error) {
                    }
                };

                const omoDocumentsGetStoredPreferences = function (panel) {
                    const preferences = omoDocumentsGetSavedViewsStore().contexts[
                        omoDocumentsGetPreferencesContextKey(panel)
                    ];
                    return preferences && typeof preferences === 'object' ? preferences : null;
                };

                const omoDocumentsGetDefaultPreferences = function () {
                    return omoDocumentsGetSavedViewsStore().defaultView;
                };

                const omoDocumentsStorePreferences = function (panel, preferences) {
                    const store = omoDocumentsGetSavedViewsStore();
                    store.contexts[omoDocumentsGetPreferencesContextKey(panel)] = omoDocumentsCreatePreferences(preferences);
                    omoDocumentsSaveViewsStore(store);
                };

                const omoDocumentsStoreDefaultPreferences = function (preferences) {
                    const store = omoDocumentsGetSavedViewsStore();
                    store.defaultView = omoDocumentsCreatePreferences(preferences);
                    omoDocumentsSaveViewsStore(store);
                };

                const omoDocumentsClearStoredPreferences = function (panel) {
                    const store = omoDocumentsGetSavedViewsStore();
                    delete store.contexts[omoDocumentsGetPreferencesContextKey(panel)];
                    omoDocumentsSaveViewsStore(store);
                };

                const omoDocumentsReadStoredValue = function (storage, storageKey, panel) {
                    try {
                        const rawValue = storage.getItem(storageKey);
                        const values = rawValue ? JSON.parse(rawValue) : null;
                        return values && typeof values === 'object'
                            ? values[omoDocumentsGetPreferencesContextKey(panel)] || null
                            : null;
                    } catch (error) {
                        return null;
                    }
                };

                const omoDocumentsReadPreferences = function (panel) {
                    const temporary = omoDocumentsReadStoredValue(
                        window.sessionStorage,
                        omoDocumentsSessionViewsStorageKey,
                        panel
                    );
                    const canUseLegacyPersonal = typeof window.omoApplicationViewPreferencesCanUseLegacyPersonal === 'function'
                        && window.omoApplicationViewPreferencesCanUseLegacyPersonal(panel);
                    const saved = canUseLegacyPersonal ? omoDocumentsGetStoredPreferences(panel) : null;
                    const defaultView = canUseLegacyPersonal ? omoDocumentsGetDefaultPreferences() : null;
                    const serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
                        ? window.omoApplicationViewPreferencesGetDefault(panel)
                        : null;
                    const personalView = typeof window.omoApplicationViewPreferencesGetPersonal === 'function'
                        ? window.omoApplicationViewPreferencesGetPersonal(panel)
                        : null;
                    const preferences = temporary || personalView || serverDefault || saved || defaultView || {};

                    return {
                        scope: omoDocumentsNormalizeScope(
                            preferences.scope || (panel && panel.getAttribute('data-omo-document-scope')) || 'contextual'
                        ),
                        sort: omoDocumentsNormalizeSortPreference(preferences.sort),
                        density: omoDocumentsNormalizeDensityPreference(preferences.density)
                    };
                };

                const omoDocumentsWritePreferences = function (panel, preferences, saveView) {
                    const normalizedPreferences = omoDocumentsCreatePreferences(preferences);

                    if (saveView) {
                        omoDocumentsStorePreferences(panel, normalizedPreferences);
                        omoDocumentsClearTemporaryPreferences(panel);
                        return;
                    }

                    try {
                        const rawValue = window.sessionStorage.getItem(omoDocumentsSessionViewsStorageKey);
                        let values = rawValue ? JSON.parse(rawValue) : {};
                        if (!values || typeof values !== 'object') {
                            values = {};
                        }
                        values[omoDocumentsGetPreferencesContextKey(panel)] = normalizedPreferences;
                        window.sessionStorage.setItem(omoDocumentsSessionViewsStorageKey, JSON.stringify(values));
                    } catch (error) {
                    }
                };

                const omoDocumentsClearTemporaryPreferences = function (panel) {
                    try {
                        const rawValue = window.sessionStorage.getItem(omoDocumentsSessionViewsStorageKey);
                        const values = rawValue ? JSON.parse(rawValue) : {};
                        if (!values || typeof values !== 'object') {
                            return;
                        }
                        delete values[omoDocumentsGetPreferencesContextKey(panel)];
                        window.sessionStorage.setItem(omoDocumentsSessionViewsStorageKey, JSON.stringify(values));
                    } catch (error) {
                    }
                };

                const omoDocumentsClearAllTemporaryPreferences = function () {
                    try {
                        window.sessionStorage.removeItem(omoDocumentsSessionViewsStorageKey);
                    } catch (error) {
                    }
                };

                const omoDocumentsReadSearch = function (panel) {
                    const storedSearch = omoDocumentsReadStoredValue(
                        window.sessionStorage,
                        omoDocumentsSearchStorageKey,
                        panel
                    );
                    return typeof storedSearch === 'string' ? storedSearch : '';
                };

                const omoDocumentsWriteSearch = function (panel, searchValue) {
                    try {
                        const rawValue = window.sessionStorage.getItem(omoDocumentsSearchStorageKey);
                        let values = rawValue ? JSON.parse(rawValue) : {};
                        if (!values || typeof values !== 'object') {
                            values = {};
                        }
                        values[omoDocumentsGetPreferencesContextKey(panel)] = String(searchValue || '');
                        window.sessionStorage.setItem(omoDocumentsSearchStorageKey, JSON.stringify(values));
                    } catch (error) {
                    }
                };

                window.omoDocumentsReadViewPreferences = omoDocumentsReadPreferences;

                window.omoInitDocumentsPanels = function (root) {
                        const scope = root instanceof Element ? root : document;

                        scope.querySelectorAll('.omo-documents').forEach(function (panel) {
                            if (panel.dataset.omoDocumentsReady === '1') {
                                return;
                            }

                            const results = panel.querySelector('[data-omo-documents-results]');
                            const dataNode = panel.querySelector('[data-omo-documents-data]');
                            const detailDrawer = panel.querySelector('[data-omo-document-detail-drawer]');
                            const detailBody = detailDrawer ? detailDrawer.querySelector('[data-omo-document-detail-body]') : null;
                            const detailTitle = detailDrawer ? detailDrawer.querySelector('[data-omo-document-detail-title]') : null;
                            const detailDescription = detailDrawer ? detailDrawer.querySelector('[data-omo-document-detail-description]') : null;
                            const detailDrawerController = detailDrawer && typeof window.omoCreateSubdrawerController === 'function'
                                ? (detailDrawer.__omoSubdrawerController || window.omoCreateSubdrawerController({ drawer: detailDrawer }))
                                : null;

                            if (detailDrawerController) {
                                detailDrawer.__omoSubdrawerController = detailDrawerController;
                                window.omoDocumentsDetailDrawer = detailDrawerController;
                            }

                            if (!results || !dataNode) {
                                return;
                            }

                            let payload = null;

                            try {
                                payload = JSON.parse(dataNode.textContent || '{}');
                            } catch (error) {
                                return;
                            }

                            const documents = Array.isArray(payload.documents) ? payload.documents.slice() : [];
                            const canUploadToCurrentContext = panel.getAttribute('data-omo-document-can-upload') === '1';
                            const canMoveToCurrentContext = panel.getAttribute('data-omo-document-can-move-here') === '1';
                            const directUploadEndpoint = '/omo/api/documents/save.php';
                            const directMoveEndpoint = '/omo/api/documents/move_action.php';
                            const directUploadTexts = {
                                root: pageConfig.root,
                                folder: pageConfig.folder,
                                success: pageConfig.success,
                                error: pageConfig.error,
                                forbidden: pageConfig.forbidden
                            };
                            const directMoveTexts = {
                                root: pageConfig.root2,
                                folder: pageConfig.folder2,
                                forbidden: pageConfig.forbidden2
                            };
                            const isPvApplicationTab = pageConfig.isPvApplicationTab
                                || (
                                    typeof window.omoIsPvApplicationTabContext === 'function'
                                    && window.omoIsPvApplicationTabContext(panel)
                                );
                            const requestedDocument = payload && payload.requestedDocument && typeof payload.requestedDocument === 'object'
                                ? payload.requestedDocument
                                : null;
                            const groups = Array.isArray(payload.groups) ? payload.groups : [];
                            const emptyStateMessage = pageConfig.emptyStateMessage;
                            const folderStateCookieName = omoDocumentsBuildFolderStateCookieName(
                                Number(panel.getAttribute('data-omo-document-oid') || 0),
                                Number(panel.getAttribute('data-omo-document-cid') || 0),
                                panel.getAttribute('data-omo-document-scope') || 'contextual'
                            );

                            panel.dataset.omoDocumentsReady = '1';
                            const savedPreferences = omoDocumentsReadPreferences(panel);

                            const state = {
                                sort: savedPreferences.sort,
                                density: savedPreferences.density,
                                query: omoDocumentsReadSearch(panel),
                                openFolderIds: omoDocumentsParseFolderState(
                                    omoDocumentsReadSessionCookie(folderStateCookieName)
                                ),
                                loadingFolderIds: new Set(),
                                selectedDocumentIds: new Set(),
                                activeDocumentId: detailDrawer && detailDrawer.dataset.omoDocumentActiveId
                                    ? Number(detailDrawer.dataset.omoDocumentActiveId)
                                    : null
                            };
                            let detailRequestToken = 0;
                            let pendingDisplayFilters = null;
                            let filterPanelOpen = false;
                            let directUploadInProgress = false;
                            let directMoveInProgress = false;
                            let draggedDocumentId = 0;
                            const progressiveLists = new Set();
                            const renderedCounts = new Map();
                            const startProgressiveList = function (container, items, appendItem, key) {
                                const controller = window.omoCreateDocumentProgressiveList({
                                    container: container,
                                    items: items,
                                    appendItem: appendItem,
                                    initialCount: renderedCounts.get(key) || 30,
                                    moreLabel: pageConfig.moreLabel,
                                    afterAppend: function (count) {
                                        renderedCounts.set(key, count);
                                        if (typeof window.initGenericComponents === 'function') window.initGenericComponents(container);
                                        syncFolderAccordionState(false);
                                        syncDocumentSelection();
                                        if (typeof window.syncGenericFileLists === 'function') window.syncGenericFileLists(results);
                                        results.querySelectorAll('[data-generic-accordion]:not(.is-collapsed)').forEach(function (accordion) {
                                            if (accordion.dataset.omoDocumentsAutoLoaded === '1') return;
                                            accordion.dataset.omoDocumentsAutoLoaded = '1';
                                            if (accordion.hasAttribute('data-omo-nextcloud-folder-id')) loadNextcloudFolder(accordion);
                                            else loadDocumentFolder(accordion);
                                        });
                                    }
                                });
                                progressiveLists.add(controller);
                            };

                            const collator = typeof Intl !== 'undefined' && typeof Intl.Collator === 'function'
                                ? new Intl.Collator('fr', { sensitivity: 'base', numeric: true })
                                : null;

                            const compareText = function (left, right) {
                                const normalizedLeft = String(left || '');
                                const normalizedRight = String(right || '');

                                if (collator) {
                                    return collator.compare(normalizedLeft, normalizedRight);
                                }

                                return normalizedLeft.localeCompare(normalizedRight);
                            };

                            const normalizeQuickSearch = function (value) {
                                return String(value || '')
                                    .toLocaleLowerCase()
                                    .normalize('NFD')
                                    .replace(/[\u0300-\u036f]/g, '')
                                    .trim();
                            };

                            const documentMatchesQuickSearch = function (documentItem, query) {
                                if (query === '') {
                                    return true;
                                }
                                const breadcrumb = Array.isArray(documentItem && documentItem.contextBreadcrumb)
                                    ? documentItem.contextBreadcrumb.map(function (item) {
                                        return item && item.label ? item.label : '';
                                    }).join(' ')
                                    : '';
                                const searchText = [
                                    documentItem && documentItem.title,
                                    documentItem && documentItem.listTitle,
                                    documentItem && documentItem.description,
                                    documentItem && documentItem.keywords,
                                    documentItem && documentItem.contextLabel,
                                    breadcrumb,
                                    documentItem && documentItem.documentType
                                ].join(' ');
                                return normalizeQuickSearch(searchText).indexOf(query) !== -1;
                            };

                            const getTemporalSortMode = function (sortMode) {
                                return sortMode === 'created'
                                    ? 'created'
                                    : 'updated';
                            };

                            const getDocumentTimestamp = function (documentItem, sortMode) {
                                if (getTemporalSortMode(sortMode) === 'created') {
                                    return Number(documentItem && documentItem.createdTimestamp ? documentItem.createdTimestamp : 0);
                                }

                                return Number(documentItem && documentItem.updatedTimestamp ? documentItem.updatedTimestamp : 0);
                            };

                            const getDocumentDateLabel = function (documentItem, sortMode, includeFullDate) {
                                const temporalSortMode = getTemporalSortMode(sortMode);

                                if (temporalSortMode === 'created') {
                                    return includeFullDate
                                        ? String(documentItem && documentItem.createdFullDateLabel ? documentItem.createdFullDateLabel : '')
                                        : String(documentItem && documentItem.createdDateLabel ? documentItem.createdDateLabel : '');
                                }

                                return includeFullDate
                                    ? String(documentItem && documentItem.updatedFullDateLabel ? documentItem.updatedFullDateLabel : '')
                                    : String(documentItem && documentItem.updatedDateLabel ? documentItem.updatedDateLabel : '');
                            };

                            const getDocumentGroupKey = function (documentItem, sortMode) {
                                return getTemporalSortMode(sortMode) === 'created'
                                    ? String(documentItem && documentItem.createdGroupKey ? documentItem.createdGroupKey : 'too_far')
                                    : String(documentItem && documentItem.updatedGroupKey ? documentItem.updatedGroupKey : 'too_far');
                            };

                            const getDateColumnLabel = function (sortMode) {
                                return getTemporalSortMode(sortMode) === 'created'
                                    ? pageConfig.documentsDateColumnCreated
                                    : pageConfig.documentsDateColumnUpdated;
                            };

                            const sortByTemporal = function (items, sortMode) {
                                return items.slice().sort(function (left, right) {
                                    const timestampDiff = getDocumentTimestamp(right, sortMode) - getDocumentTimestamp(left, sortMode);

                                    if (timestampDiff !== 0) {
                                        return timestampDiff;
                                    }

                                    return compareText(left.sortTitle || left.title, right.sortTitle || right.title);
                                });
                            };

                            const sortByAlpha = function (items) {
                                return items.slice().sort(function (left, right) {
                                    const titleDiff = compareText(left.sortTitle || left.title, right.sortTitle || right.title);

                                    if (titleDiff !== 0) {
                                        return titleDiff;
                                    }

                                    return getDocumentTimestamp(right, 'updated') - getDocumentTimestamp(left, 'updated');
                                });
                            };

                            const buildExternalFaviconUrl = function (externalUrl) {
                                const trimmedUrl = String(externalUrl || '').trim();
                                if (trimmedUrl === '') {
                                    return '';
                                }

                                try {
                                    const parsedUrl = new URL(trimmedUrl, window.location.origin);
                                    if (!/^https?:$/i.test(parsedUrl.protocol)) {
                                        return '';
                                    }

                                    return parsedUrl.origin.replace(/\/+$/, '') + '/favicon.ico';
                                } catch (error) {
                                    return '';
                                }
                            };

                            const appendExternalFaviconBadge = function (iconBox, externalUrl) {
                                if (!iconBox) {
                                    return;
                                }

                                const faviconUrl = buildExternalFaviconUrl(externalUrl);
                                if (faviconUrl === '') {
                                    return;
                                }

                                const faviconBadge = document.createElement('span');
                                faviconBadge.className = 'omo-documents__favicon-badge';
                                faviconBadge.hidden = true;

                                const faviconImage = document.createElement('img');
                                faviconImage.className = 'omo-documents__favicon-image';
                                faviconImage.src = faviconUrl;
                                faviconImage.alt = '';
                                faviconImage.loading = 'lazy';
                                faviconImage.referrerPolicy = 'no-referrer';
                                faviconImage.addEventListener('load', function () {
                                    faviconBadge.hidden = false;
                                }, { once: true });
                                faviconImage.addEventListener('error', function () {
                                    faviconBadge.remove();
                                }, { once: true });

                                faviconBadge.appendChild(faviconImage);
                                iconBox.appendChild(faviconBadge);
                            };

                            const createVisibilityCapsule = function (documentItem) {
                                const visibilityLabel = String(documentItem && documentItem.visibilityBadge ? documentItem.visibilityBadge : '').trim();
                                const visibilityIconUrl = String(documentItem && documentItem.visibilityIconUrl ? documentItem.visibilityIconUrl : '').trim();
                                const editVisibilityLabel = String(documentItem && documentItem.editVisibilityBadge ? documentItem.editVisibilityBadge : '').trim();
                                const editVisibilityIconUrl = String(documentItem && documentItem.editVisibilityIconUrl ? documentItem.editVisibilityIconUrl : '').trim();

                                if (
                                    visibilityLabel === ''
                                    || visibilityIconUrl === ''
                                    || editVisibilityLabel === ''
                                    || editVisibilityIconUrl === ''
                                ) {
                                    return null;
                                }

                                const tooltipLabel = pageConfig.tooltipLabel
                                    + ': ' + visibilityLabel
                                    + ' | '
                                    + pageConfig.documentsScopeEdit
                                    + ': ' + editVisibilityLabel;

                                const capsule = document.createElement('span');
                                capsule.className = 'omo-documents__scope-capsule';
                                capsule.setAttribute('aria-label', tooltipLabel);
                                capsule.setAttribute('title', tooltipLabel);

                                const buildScopeIcon = function (iconUrl) {
                                    const icon = document.createElement('span');
                                    icon.className = 'omo-documents__scope-icon';
                                    icon.setAttribute('aria-hidden', 'true');

                                    const image = document.createElement('img');
                                    image.className = 'black-icon';
                                    image.src = iconUrl;
                                    image.alt = '';
                                    image.loading = 'lazy';

                                    icon.appendChild(image);
                                    return icon;
                                };

                                const separator = document.createElement('span');
                                separator.className = 'omo-documents__scope-separator';
                                separator.setAttribute('aria-hidden', 'true');

                                capsule.appendChild(buildScopeIcon(visibilityIconUrl));
                                capsule.appendChild(separator);
                                capsule.appendChild(buildScopeIcon(editVisibilityIconUrl));

                                return capsule;
                            };

                            const childrenByParentId = new Map();
                            documents.forEach(function (documentItem) {
                                const parentDocumentId = Number(documentItem && documentItem.parentDocumentId ? documentItem.parentDocumentId : 0);
                                const normalizedParentDocumentId = Number.isInteger(parentDocumentId) && parentDocumentId > 0
                                    ? parentDocumentId
                                    : 0;

                                if (!childrenByParentId.has(normalizedParentDocumentId)) {
                                    childrenByParentId.set(normalizedParentDocumentId, []);
                                }

                                childrenByParentId.get(normalizedParentDocumentId).push(documentItem);
                            });

                            const findDocumentItemById = function (documentId) {
                                const resolvedDocumentId = Number(documentId || 0);
                                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                                    return null;
                                }

                                return documents.find(function (documentItem) {
                                    return Number(documentItem && documentItem.id || 0) === resolvedDocumentId;
                                }) || null;
                            };

                            const getSortedTree = function (sortMode, parentDocumentId) {
                                const normalizedParentDocumentId = Number(parentDocumentId || 0) > 0
                                    ? Number(parentDocumentId || 0)
                                    : 0;
                                const sourceItems = childrenByParentId.get(normalizedParentDocumentId) || [];
                                const sortedItems = sortMode === 'alpha'
                                    ? sortByAlpha(sourceItems)
                                    : sortByTemporal(sourceItems, sortMode);

                                const query = normalizeQuickSearch(state.query);
                                return sortedItems.map(function (documentItem) {
                                    const clonedItem = Object.assign({}, documentItem);
                                    clonedItem.children = getSortedTree(sortMode, Number(documentItem.id || 0));
                                    return query === ''
                                        || documentMatchesQuickSearch(documentItem, query)
                                        || clonedItem.children.length > 0
                                        ? clonedItem
                                        : null;
                                }).filter(function (documentItem) {
                                    return documentItem !== null;
                                });
                            };

                            const formatSelectionText = function (template, values) {
                                return String(template || '').replace(/\{([a-z_]+)\}/gi, function (match, key) {
                                    return values && values[key] !== undefined ? String(values[key]) : match;
                                });
                            };

                            const getSelectedDocumentIds = function () {
                                return Array.from(state.selectedDocumentIds).filter(function (documentId) {
                                    return Number.isInteger(documentId) && documentId > 0;
                                });
                            };

                            const selectedDocumentsCanBeDeleted = function (selectedDocumentIds) {
                                return selectedDocumentIds.every(function (documentId) {
                                    return documents.some(function (documentItem) {
                                        return Number(documentItem && documentItem.id || 0) === documentId
                                            && documentItem.canDelete === true;
                                    });
                                });
                            };

                            const selectedDocumentsCanBeMoved = function (selectedDocumentIds) {
                                return selectedDocumentIds.every(function (documentId) {
                                    return documents.some(function (documentItem) {
                                        return Number(documentItem && documentItem.id || 0) === documentId
                                            && documentItem.canMove === true;
                                    });
                                });
                            };

                            const selectedDocumentsCanBeMerged = function (selectedDocumentIds) {
                                return selectedDocumentIds.length > 1 && selectedDocumentIds.every(function (documentId) {
                                    return documents.some(function (documentItem) {
                                        return Number(documentItem && documentItem.id || 0) === documentId
                                            && documentItem.documentType === 'html'
                                            && documentItem.canMerge === true;
                                    });
                                });
                            };

                            const syncDocumentSelection = function () {
                                const selectedDocumentIds = getSelectedDocumentIds();
                                const selectedCount = selectedDocumentIds.length;
                                const bulkActions = panel.querySelector('[data-omo-documents-bulk-actions]');
                                const bulkCount = panel.querySelector('[data-omo-documents-bulk-count]');
                                const moveButton = panel.querySelector('[data-omo-documents-bulk-action="move"]');
                                const mergeButton = panel.querySelector('[data-omo-documents-bulk-action="merge"]');
                                const deleteButton = panel.querySelector('[data-omo-documents-bulk-action="delete"]');

                                if (bulkActions) {
                                    bulkActions.hidden = selectedCount === 0;
                                }
                                if (bulkCount) {
                                    bulkCount.textContent = formatSelectionText(
                                        pageConfig.documentsSelectionCount,
                                        {count: selectedCount}
                                    );
                                }
                                if (moveButton) {
                                    moveButton.hidden = selectedCount === 0 || !selectedDocumentsCanBeMoved(selectedDocumentIds);
                                }
                                if (mergeButton) {
                                    mergeButton.hidden = !selectedDocumentsCanBeMerged(selectedDocumentIds);
                                }
                                if (deleteButton) {
                                    deleteButton.hidden = selectedCount === 0 || !selectedDocumentsCanBeDeleted(selectedDocumentIds);
                                }

                                results.querySelectorAll('[data-omo-document-select]').forEach(function (input) {
                                    const documentId = Number(input.value || 0);
                                    input.checked = Number.isInteger(documentId) && state.selectedDocumentIds.has(documentId);
                                    const documentNode = input.closest('.omo-documents__item');
                                    if (documentNode) {
                                        documentNode.classList.toggle(
                                            'is-selected',
                                            Number.isInteger(documentId) && state.selectedDocumentIds.has(documentId)
                                        );
                                    }
                                });
                            };

                            const createDocumentSelectionControl = function (documentItem) {
								if (documentItem && documentItem.isRemoteFile) {
									return null;
								}
                                if (!documentItem || (!documentItem.canArchive && !documentItem.canMove && !documentItem.canMerge)) {
                                    return null;
                                }

                                const documentId = Number(documentItem.id || 0);
                                if (!Number.isInteger(documentId) || documentId <= 0) {
                                    return null;
                                }

                                const control = document.createElement('label');
                                control.className = 'omo-documents__selection-control';
                                const input = document.createElement('input');
                                input.type = 'checkbox';
                                input.value = String(documentId);
                                input.checked = state.selectedDocumentIds.has(documentId);
                                input.setAttribute('data-omo-document-select', '1');
                                input.setAttribute('data-document-can-delete', documentItem.canDelete ? '1' : '0');
                                input.setAttribute('aria-label', pageConfig.documentsSelectionToggle);
                                control.appendChild(input);
                                return control;
                            };

                            const createDocumentSelectionPlaceholder = function () {
                                const placeholder = document.createElement('span');
                                placeholder.className = 'omo-documents__selection-placeholder';
                                placeholder.setAttribute('aria-hidden', 'true');
                                return placeholder;
                            };

                            const postBulkDocumentAction = function (action) {
                                return fetch('/omo/api/documents/lifecycle_action.php', {
                                    method: 'POST',
                                    credentials: 'same-origin',
                                    headers: {'Content-Type': 'application/json'},
                                    body: JSON.stringify({ids: getSelectedDocumentIds(), action: action})
                                }).then(function (response) {
                                    return response.json().then(function (payload) {
                                        if (!response.ok || !payload || payload.status !== true) {
                                            throw new Error(String(payload && payload.message || pageConfig.documentsMenuActionError));
                                        }
                                        return payload;
                                    });
                                });
                            };

                            window.addEventListener('omo-documents-bulk-move-complete', function (event) {
                                const completedIds = event && event.detail && Array.isArray(event.detail.ids)
                                    ? event.detail.ids
                                    : [];
                                completedIds.forEach(function (documentId) {
                                    const normalizedId = Number(documentId || 0);
                                    if (Number.isInteger(normalizedId) && normalizedId > 0) {
                                        state.selectedDocumentIds.delete(normalizedId);
                                    }
                                });
                                syncDocumentSelection();
                            });

                            window.addEventListener('omo-documents-merge-complete', function (event) {
                                const completedIds = event && event.detail && Array.isArray(event.detail.ids) ? event.detail.ids : [];
                                completedIds.forEach(function (documentId) { state.selectedDocumentIds.delete(Number(documentId || 0)); });
                                syncDocumentSelection();
                            });

                            const appendDocumentCardContent = function (container, documentItem, options) {
                                const settings = options && typeof options === 'object' ? options : {};

                                if (!container) {
                                    return;
                                }

                                if (state.density === 'compact') {
                                    container.classList.add('omo-documents__item--compact');
                                }

                                if (settings.interactive) {
                                    container.classList.add('omo-card--interactive');
                                    container.setAttribute('role', 'button');
                                    container.setAttribute('tabindex', '0');
                                    container.setAttribute('data-omo-document-id', documentItem.id || '');
                                    container.setAttribute('data-omo-document-href', documentItem.href || '');
                                    container.setAttribute('data-omo-document-context-url', documentItem.contextUrl || '');
                                    container.setAttribute('data-omo-document-external-url', documentItem.externalUrl || '');
                                    container.setAttribute('data-omo-document-open-in-new-window', documentItem.openInNewWindow ? '1' : '0');
                                    container.setAttribute('data-omo-document-type', documentItem.documentType || '');
                                    container.setAttribute('data-omo-document-pv-editor-url', documentItem.pvPreparationUrl || '');
                                    container.setAttribute('data-omo-document-can-open-in-pv-tab', documentItem.canOpenInPvApplicationTab ? '1' : '0');
                                    container.setAttribute('data-omo-document-title', documentItem.title || '');
                                    container.setAttribute('data-omo-document-full-date', documentItem.fullDateLabel || '');
									if (documentItem.isRemoteFile) {
										container.setAttribute('data-omo-nextcloud-file', '1');
									}
                                }

                                if (documentItem.canMove === true && !documentItem.isRemoteFile) {
                                    container.classList.add('omo-documents__item--draggable');
                                    container.setAttribute('draggable', 'true');
                                    container.setAttribute('data-omo-document-drag-id', String(documentItem.id || '0'));
                                }

                                container.classList.add(
                                    documentItem.isFolder
                                        ? 'omo-documents__item--folder-card'
                                        : 'omo-documents__item--file-card'
                                );
                                container.classList.toggle('omo-documents__item--missing-upload', !!documentItem.isMissingUploadedFile);

                                const frame = document.createElement('div');
                                frame.className = 'omo-documents__item-frame omo-documents__item-frame--with-selection';
                                const selectionControl = createDocumentSelectionControl(documentItem);
                                frame.appendChild(selectionControl || createDocumentSelectionPlaceholder());

                                const visual = document.createElement('div');
                                visual.className = 'omo-documents__visual';

                                const iconBox = document.createElement('div');
                                iconBox.className = 'omo-documents__icon-box';

                                const icon = document.createElement('img');
                                icon.className = 'omo-documents__icon black-icon';
                                icon.src = omoDocumentsGetIconUrl(documentItem);
                                icon.alt = omoDocumentsGetIconAlt(documentItem);
                                icon.loading = 'lazy';
                                if (documentItem.isFolder) {
                                    icon.setAttribute('data-omo-document-folder-icon', '1');
                                }

                                iconBox.appendChild(icon);
                                if (documentItem.isExternalLink) {
                                    appendExternalFaviconBadge(iconBox, documentItem.externalUrl || '');
                                }
                                visual.appendChild(iconBox);

                                const content = document.createElement('div');
                                content.className = 'omo-documents__content';

                                const keywordList = String(documentItem.keywords || '')
                                    .split(',')
                                    .map(function (keyword) {
                                        return String(keyword || '').trim();
                                    })
                                    .filter(function (keyword) {
                                        return keyword !== '';
                                    });

                                if (state.density === 'compact') {
                                    const frame = document.createElement('div');
                                    frame.className = 'omo-documents__item-frame omo-documents__item-frame--compact-list generic-file-list__row';
                                    const showsContext = omoDocumentsNormalizeScope(panel.getAttribute('data-omo-document-scope') || 'contextual') !== 'contextual';

                                    const compactNameCell = document.createElement('div');
                                    compactNameCell.className = 'omo-documents__compact-cell omo-documents__compact-cell--name generic-file-list__cell generic-file-list__cell--name';

                                    const compactNameMain = document.createElement('div');
                                    compactNameMain.className = 'omo-documents__compact-name-main generic-file-list__name-main';

                                    const compactTitleBlock = document.createElement('div');
                                    compactTitleBlock.className = 'omo-documents__compact-title-block generic-file-list__title-block';

                                    const compactTitleStack = document.createElement('div');
                                    compactTitleStack.className = 'omo-documents__compact-title-stack generic-file-list__title-row';

                                    const compactTitle = document.createElement('strong');
                                    compactTitle.className = 'omo-documents__compact-title generic-file-list__title';
                                    compactTitle.textContent = documentItem.listTitle || documentItem.title || '';
                                    compactTitleStack.appendChild(compactTitle);
                                    if (documentItem.isTemplate) {
                                        const templateBadge = document.createElement('span');
                                        templateBadge.className = 'omo-documents__template-star';
                                        templateBadge.textContent = '★';
                                        templateBadge.setAttribute('title', pageConfig.documentsTemplateBadge);
                                        templateBadge.setAttribute('aria-label', pageConfig.documentsTemplateBadge);
                                        compactTitleStack.appendChild(templateBadge);
                                    }
                                    if (documentItem.isMissingUploadedFile) {
                                        const compactMissingUpload = document.createElement('span');
                                        compactMissingUpload.className = 'omo-documents__missing-upload-badge';
                                        compactMissingUpload.textContent = omoDocumentsMissingUploadedFileLabel;
                                        compactTitleStack.appendChild(compactMissingUpload);
                                    }
                                    const compactScopeCapsule = createVisibilityCapsule(documentItem);
                                    if (compactScopeCapsule) {
                                        compactTitleStack.appendChild(compactScopeCapsule);
                                    }

                                    if (documentItem.isFolder) {
                                        const count = Array.isArray(documentItem.children) ? documentItem.children.length : 0;
                                        const childrenLoaded = documentItem.childrenLoaded === true;
                                        const compactCount = document.createElement('span');
                                        compactCount.className = 'omo-documents__compact-count generic-file-list__count';
										compactCount.textContent = documentItem.isNextcloudFolder
											? omoDocumentsNextcloudFolderLabel
											: (!childrenLoaded
												? omoDocumentsFolderUnloadedLabel
												: (count > 0
                                            ? String(count) + ' element' + (count > 1 ? 's' : '')
													: omoDocumentsFolderEmptyLabel));
                                        compactTitleStack.appendChild(compactCount);
                                    }

                                    compactTitleBlock.appendChild(compactTitleStack);

                                    if (showsContext) {
                                        let compactContextLine = null;

                                        if (Array.isArray(documentItem.contextBreadcrumb) && documentItem.contextBreadcrumb.length > 0) {
                                            compactContextLine = document.createElement('div');
                                            compactContextLine.className = 'omo-documents__compact-context-line generic-file-list__meta-line';

                                            documentItem.contextBreadcrumb.forEach(function (breadcrumbItem, breadcrumbIndex) {
                                                if (breadcrumbIndex > 0) {
                                                    const separator = document.createElement('span');
                                                    separator.className = 'omo-documents__compact-context-separator';
                                                    separator.textContent = '>';
                                                    compactContextLine.appendChild(separator);
                                                }

                                                if (settings.plainContext === true) {
                                                    const contextText = document.createElement('span');
                                                    contextText.textContent = String(breadcrumbItem && breadcrumbItem.label ? breadcrumbItem.label : '');
                                                    compactContextLine.appendChild(contextText);
                                                } else {
                                                    const contextButton = document.createElement('button');
                                                    contextButton.type = 'button';
                                                    contextButton.className = 'omo-documents__context-link omo-documents__context-link--compact';
                                                    contextButton.setAttribute('data-omo-document-context-jump', '1');
                                                    contextButton.setAttribute('data-omo-document-context-jump-oid', String(breadcrumbItem && breadcrumbItem.organizationId ? breadcrumbItem.organizationId : ''));
                                                    contextButton.setAttribute('data-omo-document-context-jump-cid', String(breadcrumbItem && breadcrumbItem.holonId ? breadcrumbItem.holonId : '0'));
                                                    contextButton.textContent = String(breadcrumbItem && breadcrumbItem.label ? breadcrumbItem.label : '');
                                                    compactContextLine.appendChild(contextButton);
                                                }
                                            });
                                        } else if (documentItem.contextLabel) {
                                            compactContextLine = document.createElement('div');
                                            compactContextLine.className = 'omo-documents__compact-context-line generic-file-list__meta-line';
                                            compactContextLine.textContent = documentItem.contextLabel;
                                        }

                                        if (compactContextLine) {
                                            const compactContextInline = document.createElement('div');
                                            compactContextInline.className = 'omo-documents__compact-context-inline';
                                            compactContextInline.appendChild(compactContextLine);
                                            compactTitleBlock.appendChild(compactContextInline);
                                        }
                                    }

                                    compactNameMain.appendChild(selectionControl || createDocumentSelectionPlaceholder());
                                    compactNameMain.appendChild(visual);
                                    compactNameMain.appendChild(compactTitleBlock);
                                    compactNameCell.appendChild(compactNameMain);

                                    const compactTagsCell = document.createElement('div');
                                    compactTagsCell.className = 'omo-documents__compact-cell omo-documents__compact-cell--tags generic-file-list__cell';
                                    compactTagsCell.setAttribute('data-label', 'Tags');
                                    const compactTags = document.createElement('div');
                                    compactTags.className = 'omo-documents__compact-tags generic-file-list__tag-list';

                                    if (keywordList.length > 0) {
                                        keywordList.forEach(function (keyword) {
                                            const keywordTag = document.createElement('span');
                                            keywordTag.className = 'omo-documents__keyword-tag generic-file-list__tag';
                                            keywordTag.textContent = '#' + keyword.replace(/^#+/, '');
                                            compactTags.appendChild(keywordTag);
                                        });
                                    } else {
                                        compactTags.classList.add('omo-documents__compact-tags--empty');
                                        compactTags.textContent = '-';
                                    }

                                    compactTagsCell.appendChild(compactTags);

                                    const compactDateCell = document.createElement('div');
                                    compactDateCell.className = 'omo-documents__compact-cell omo-documents__compact-cell--date generic-file-list__cell generic-file-list__cell--date';
                                    compactDateCell.setAttribute('data-label', getDateColumnLabel(state.sort));
                                    compactDateCell.textContent = state.sort === 'alpha'
                                        ? getDocumentDateLabel(documentItem, 'updated', true)
                                        : getDocumentDateLabel(documentItem, state.sort, false);

                                    frame.appendChild(compactNameCell);
                                    frame.appendChild(compactTagsCell);
                                    frame.appendChild(compactDateCell);
                                    container.appendChild(frame);
                                    return;
                                }

                                const eyebrow = document.createElement('div');
                                eyebrow.className = 'omo-documents__eyebrow';

                                const date = document.createElement('span');
                                date.className = 'omo-documents__date';
                                date.textContent = state.sort === 'alpha'
                                    ? getDocumentDateLabel(documentItem, 'updated', true)
                                    : getDocumentDateLabel(documentItem, state.sort, false);

                                if (documentItem.isFolder) {
                                    const count = Array.isArray(documentItem.children) ? documentItem.children.length : 0;
                                    const childrenLoaded = documentItem.childrenLoaded === true;
                                    const countLabel = document.createElement('span');
                                    countLabel.className = 'omo-documents__kind-detail';
									countLabel.textContent = documentItem.isNextcloudFolder
										? omoDocumentsNextcloudFolderLabel
										: (!childrenLoaded
											? omoDocumentsFolderUnloadedLabel
											: (count > 0
                                        ? String(count) + ' element' + (count > 1 ? 's' : '')
												: omoDocumentsFolderEmptyLabel));
                                    eyebrow.appendChild(countLabel);
                                } else if (String(documentItem.documentType || '').trim().toLowerCase() === omoDocumentsPvType) {
                                    const countLabel = document.createElement('span');
                                    countLabel.className = 'omo-documents__kind-detail';
                                    countLabel.textContent = 'PV';
                                    eyebrow.appendChild(countLabel);
                                }

                                eyebrow.appendChild(date);

                                const head = document.createElement('div');
                                head.className = 'omo-documents__item-head';
                                const titleLine = document.createElement('span');
                                titleLine.className = 'omo-documents__title-line';
                                const title = document.createElement('strong');
                                title.className = 'omo-documents__title generic-title generic-title--item';
                                title.textContent = documentItem.listTitle || documentItem.title || '';
                                titleLine.appendChild(title);
                                if (documentItem.isTemplate) {
                                    const templateBadge = document.createElement('span');
                                    templateBadge.className = 'omo-documents__template-star';
                                    templateBadge.textContent = '★';
                                    templateBadge.setAttribute('title', pageConfig.documentsTemplateBadge);
                                    templateBadge.setAttribute('aria-label', pageConfig.documentsTemplateBadge);
                                    titleLine.appendChild(templateBadge);
                                }
                                if (documentItem.isMissingUploadedFile) {
                                    const missingUpload = document.createElement('span');
                                    missingUpload.className = 'omo-documents__missing-upload-badge';
                                    missingUpload.textContent = omoDocumentsMissingUploadedFileLabel;
                                    titleLine.appendChild(missingUpload);
                                }
                                const scopeCapsule = createVisibilityCapsule(documentItem);
                                if (scopeCapsule) {
                                    titleLine.appendChild(scopeCapsule);
                                }
                                head.appendChild(titleLine);

                                content.appendChild(eyebrow);
                                content.appendChild(head);

                                if (Array.isArray(documentItem.contextBreadcrumb) && documentItem.contextBreadcrumb.length > 0) {
                                    const context = document.createElement('div');
                                    context.className = 'omo-documents__context';
                                    context.setAttribute('aria-label', 'Contexte du document');

                                    documentItem.contextBreadcrumb.forEach(function (breadcrumbItem, breadcrumbIndex) {
                                        if (breadcrumbIndex > 0) {
                                            const separator = document.createElement('span');
                                            separator.className = 'omo-documents__context-separator';
                                            separator.textContent = '›';
                                            context.appendChild(separator);
                                        }

                                        if (settings.plainContext === true) {
                                            const contextText = document.createElement('span');
                                            contextText.textContent = String(breadcrumbItem && breadcrumbItem.label ? breadcrumbItem.label : '');
                                            context.appendChild(contextText);
                                        } else {
                                            const contextButton = document.createElement('button');
                                            contextButton.type = 'button';
                                            contextButton.className = 'omo-documents__context-link';
                                            contextButton.setAttribute('data-omo-document-context-jump', '1');
                                            contextButton.setAttribute('data-omo-document-context-jump-oid', String(breadcrumbItem && breadcrumbItem.organizationId ? breadcrumbItem.organizationId : ''));
                                            contextButton.setAttribute('data-omo-document-context-jump-cid', String(breadcrumbItem && breadcrumbItem.holonId ? breadcrumbItem.holonId : '0'));
                                            contextButton.textContent = String(breadcrumbItem && breadcrumbItem.label ? breadcrumbItem.label : '');
                                            context.appendChild(contextButton);
                                        }
                                    });

                                    content.appendChild(context);
                                } else if (documentItem.contextLabel) {
                                    const context = document.createElement('div');
                                    context.className = 'omo-documents__context';
                                    context.textContent = documentItem.contextLabel;
                                    content.appendChild(context);
                                }

                                if (state.density !== 'compact' && documentItem.description) {
                                    const description = document.createElement('p');
                                    description.className = 'omo-documents__description generic-description';
                                    description.textContent = documentItem.description;
                                    content.appendChild(description);
                                }

                                if (state.density !== 'compact' && documentItem.keywords) {
                                    const keywords = document.createElement('div');
                                    keywords.className = 'omo-documents__keywords';
                                    keywordList.forEach(function (keyword) {
                                        const keywordTag = document.createElement('span');
                                        keywordTag.className = 'omo-documents__keyword-tag';
                                        keywordTag.textContent = '#' + keyword.replace(/^#+/, '');
                                        keywords.appendChild(keywordTag);
                                    });
                                    content.appendChild(keywords);
                                }

                                if (selectionControl) {
                                    frame.appendChild(selectionControl);
                                }
                                frame.appendChild(visual);
                                frame.appendChild(content);
                                container.appendChild(frame);
                            };

                            const createMenu = function (documentItem) {
                                if (
                                    (!documentItem.canEdit || !documentItem.editUrl)
                                    && !documentItem.canMove
                                    && !documentItem.canArchive
                                    && !documentItem.canDelete
                                    && !documentItem.canShare
                                    && !documentItem.canExportPdf
                                    && !documentItem.canManageTemplate
                                ) {
                                    return null;
                                }

                                const menu = document.createElement('div');
                                menu.className = 'omo-documents__menu generic-menu generic-file-list__menu';
                                menu.setAttribute('data-omo-document-menu', '1');

                                const toggle = document.createElement('button');
                                toggle.type = 'button';
                                toggle.className = 'omo-documents__menu-toggle generic-menu-toggle generic-file-list__menu-toggle';
                                toggle.setAttribute('data-omo-document-menu-toggle', '1');
                                toggle.setAttribute('data-omo-document-menu-document-id', String(documentItem.id || '0'));
                                toggle.setAttribute('data-omo-document-menu-title', String(documentItem.title || ''));
                                toggle.setAttribute('data-omo-document-menu-edit-url', String(documentItem.editUrl || ''));
                                toggle.setAttribute('data-omo-document-menu-can-edit', documentItem.canEdit ? '1' : '0');
                                toggle.setAttribute('data-omo-document-menu-can-move', documentItem.canMove ? '1' : '0');
                                toggle.setAttribute('data-omo-document-menu-can-archive', documentItem.canArchive ? '1' : '0');
                                toggle.setAttribute('data-omo-document-menu-can-delete', documentItem.canDelete ? '1' : '0');
                                toggle.setAttribute('data-omo-document-menu-is-folder', documentItem.isFolder ? '1' : '0');
                                toggle.setAttribute('data-omo-document-menu-can-share', documentItem.canShare ? '1' : '0');
                                toggle.setAttribute('data-omo-document-menu-can-export-pdf', documentItem.canExportPdf ? '1' : '0');
                                toggle.setAttribute('data-omo-document-menu-pdf-url', String(documentItem.pdfExportUrl || ''));
                                toggle.setAttribute('data-omo-document-menu-can-manage-template', documentItem.canManageTemplate ? '1' : '0');
                                toggle.setAttribute('data-omo-document-menu-is-template', documentItem.isTemplate ? '1' : '0');
                                toggle.setAttribute('aria-haspopup', 'menu');
                                toggle.setAttribute('aria-expanded', 'false');
                                toggle.setAttribute('aria-label', 'Actions pour ' + String(documentItem.title || 'ce document'));
                                toggle.textContent = '...';
                                menu.appendChild(toggle);

                                return menu;
                            };

                            const createCompactListHeader = function (sortMode) {
                                const header = document.createElement('div');
                                header.className = 'omo-documents__list-header generic-file-list__header';

                                [
                                    { label: 'Nom', className: 'omo-documents__list-header-cell--name' },
                                    { label: 'Tags', className: 'omo-documents__list-header-cell--tags' },
                                    { label: getDateColumnLabel(sortMode), className: 'omo-documents__list-header-cell--date' }
                                ].forEach(function (column) {
                                    const cell = document.createElement('div');
                                    cell.className = 'omo-documents__list-header-cell generic-file-list__header-cell ' + column.className;
                                    cell.textContent = column.label;
                                    header.appendChild(cell);
                                });

                                return header;
                            };

                            const createItem = function (documentItem) {
                                const shell = document.createElement('article');
                                shell.className = 'omo-documents__item-shell generic-file-list__item-shell';
								const isNextcloudFolder = documentItem.isNextcloudFolder === true || documentItem.isRemoteFolder === true;
								const nextcloudFolderId = Number(documentItem.remoteFolderId || (documentItem.isNextcloudFolder ? documentItem.id : 0));
								const nextcloudPath = String(documentItem.remotePath || '');

                                if (state.density === 'compact') {
                                    shell.classList.add('omo-documents__item-shell--compact');
                                }
                                if (documentItem.isMissingUploadedFile) {
                                    shell.classList.add('omo-documents__item-shell--missing-upload');
                                }

                                if (documentItem.isFolder) {
                                    shell.classList.add('omo-documents__item-shell--folder', 'generic-file-list__item-shell--folder');
                                    const folderId = Number(documentItem.id || 0);
                                    const isExpanded = normalizeQuickSearch(state.query) !== ''
                                        ? Array.isArray(documentItem.children) && documentItem.children.length > 0
                                        : Number.isInteger(folderId)
                                            && folderId > 0
                                            && state.openFolderIds.has(folderId);

                                    const accordion = document.createElement('div');
                                    accordion.className = 'generic-accordion generic-accordion--collapsible omo-documents__folder'
                                        + (isExpanded ? '' : ' is-collapsed');
                                    accordion.setAttribute('data-generic-accordion', '1');
                                    accordion.setAttribute('data-omo-document-folder', String(documentItem.id || '0'));
									if (documentItem.canUpload === true) {
										accordion.setAttribute('data-omo-document-can-upload', '1');
									}
									if (documentItem.canMoveInto === true) {
										accordion.setAttribute('data-omo-document-can-move-into', '1');
									}
									if (isNextcloudFolder && Number.isInteger(nextcloudFolderId) && nextcloudFolderId > 0) {
										accordion.setAttribute('data-omo-nextcloud-folder-id', String(nextcloudFolderId));
										accordion.setAttribute('data-omo-nextcloud-path', nextcloudPath);
									}

                                    const header = document.createElement('div');
                                    header.className = 'generic-accordion__header omo-documents__folder-header generic-file-list__folder-header';

                                    const headerToggle = document.createElement('div');
                                    headerToggle.className = 'omo-documents__folder-toggle generic-file-list__folder-toggle';
                                    headerToggle.setAttribute('data-generic-accordion-toggle', '1');
                                    headerToggle.setAttribute('data-omo-document-folder-toggle', '1');
                                    headerToggle.setAttribute('tabindex', '0');
                                    headerToggle.setAttribute('role', 'button');
                                    headerToggle.setAttribute('aria-expanded', 'false');

                                    const folderCard = document.createElement('div');
                                    folderCard.className = 'omo-documents__item omo-card omo-documents__folder-card';
                                    appendDocumentCardContent(folderCard, documentItem, { interactive: false, plainContext: true });
                                    const folderIcon = folderCard.querySelector('[data-omo-document-folder-icon]');
                                    if (folderIcon instanceof HTMLImageElement) {
                                        folderIcon.src = omoDocumentsGetFolderIconUrl(isExpanded);
                                    }

                                    const folderMenu = createMenu(documentItem);
                                    headerToggle.appendChild(folderCard);
                                    header.appendChild(headerToggle);

                                    if (folderMenu) {
                                        shell.classList.add('omo-documents__item-shell--has-menu', 'generic-file-list__item-shell--with-menu');
                                        folderMenu.classList.add('omo-documents__menu--folder-header');
                                        header.appendChild(folderMenu);
                                    }

                                    const content = document.createElement('div');
                                    content.className = 'generic-accordion__content omo-documents__folder-content generic-file-list__folder-content';

									if (isNextcloudFolder) {
										const remoteLoading = document.createElement('div');
										remoteLoading.className = 'omo-documents__folder-empty generic-file-list__empty';
										remoteLoading.setAttribute('data-omo-nextcloud-folder-content', '1');
										remoteLoading.textContent = omoDocumentsNextcloudInitialLabel;
										content.appendChild(remoteLoading);
									} else if (documentItem.childrenLoaded !== true) {
										const localLoading = document.createElement('div');
										localLoading.className = 'omo-documents__folder-empty generic-file-list__empty';
										localLoading.setAttribute('data-omo-document-folder-content', '1');
										localLoading.textContent = omoDocumentsFolderUnloadedLabel;
										content.appendChild(localLoading);
									} else if (Array.isArray(documentItem.children) && documentItem.children.length > 0) {
                                        const childList = document.createElement('div');
                                        childList.className = 'omo-documents__folder-children generic-file-list__children';

                                        if (state.density === 'compact') {
                                            childList.classList.add('omo-documents__folder-children--compact');
                                        }

                                        startProgressiveList(childList, documentItem.children, function (childDocument, sentinel) {
                                            childList.insertBefore(createItem(childDocument), sentinel);
                                        }, 'folder:' + folderId);

                                        content.appendChild(childList);
                                    } else {
                                        const emptyFolder = document.createElement('div');
                                        emptyFolder.className = 'omo-documents__folder-empty generic-file-list__empty';
                                        emptyFolder.textContent = omoDocumentsFolderEmptyLabel;
                                        content.appendChild(emptyFolder);
                                    }

                                    accordion.appendChild(header);
                                    accordion.appendChild(content);
                                    shell.appendChild(accordion);
                                } else {
                                    const link = document.createElement('div');
                                    link.className = 'omo-documents__item omo-card';
                                    const canOpenDocument = !isPvApplicationTab || documentItem.canOpenInPvApplicationTab === true;
                                    appendDocumentCardContent(link, documentItem, { interactive: canOpenDocument });
                                    if (!canOpenDocument) {
                                        link.classList.add('omo-documents__item--unavailable');
                                        link.setAttribute('aria-disabled', 'true');
                                    }
                                    shell.appendChild(link);
                                }

                                const menu = documentItem.isFolder ? null : createMenu(documentItem);
                                if (menu) {
                                    shell.classList.add('omo-documents__item-shell--has-menu', 'generic-file-list__item-shell--with-menu');
                                    shell.appendChild(menu);
                                }

                                return shell;
                            };

							const getNextcloudStoredFileKind = function (filename, mimeType) {
								const extension = String(filename || '').split('.').pop().toLowerCase();
								const type = String(mimeType || '').toLowerCase();
								if (type.indexOf('image/') === 0 || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(extension)) return 'image';
								if (type.indexOf('video/') === 0 || ['mp4', 'webm', 'mov'].includes(extension)) return 'video';
								if (type.indexOf('audio/') === 0 || ['mp3', 'wav', 'ogg'].includes(extension)) return 'audio';
								if (['ods', 'xlsx', 'xls', 'csv'].includes(extension)) return 'spreadsheet';
								if (['odp', 'pptx', 'ppt'].includes(extension)) return 'presentation';
								if (['odg'].includes(extension)) return 'drawing';
								if (['txt', 'md', 'odt', 'docx', 'doc', 'pdf'].includes(extension)) return 'text';
								return '';
							};

							const createNextcloudRemoteItem = function (folderId, entry) {
								const path = String(entry && entry.path || '');
								const name = String(entry && entry.name || path.split('/').pop() || 'Fichier');
								const isFolder = !!(entry && entry.isFolder);
								const query = 'id=' + encodeURIComponent(String(folderId)) + '&path=' + encodeURIComponent(path);
								return {
									id: 0,
									title: name,
									listTitle: name,
									documentType: 'uploaded_file',
									storedFileKind: getNextcloudStoredFileKind(name, entry && entry.mimeType),
									isFolder: isFolder,
									isNextcloudFolder: false,
									isRemoteFolder: isFolder,
									isRemoteFile: !isFolder,
									remoteFolderId: folderId,
									remotePath: path,
									remoteKey: String(folderId) + ':' + path,
									contextUrl: isFolder ? '' : '/omo/api/documents/nextcloud/detail.php?' + query + '&mime=' + encodeURIComponent(String(entry && entry.mimeType || '')),
									isExternalLink: false,
									openInNewWindow: false,
									canOpenInPvApplicationTab: true,
									canEdit: false,
									canMove: false,
									canArchive: false,
									canDelete: false,
									canShare: false,
									canExportPdf: false,
									keywords: '',
									description: '',
									dateLabel: '',
									fullDateLabel: String(entry && entry.modifiedAt || ''),
									children: []
								};
							};

							const loadNextcloudFolder = function (accordion) {
								if (!(accordion instanceof HTMLElement)) return;
								const folderId = Number(accordion.getAttribute('data-omo-nextcloud-folder-id') || 0);
								const path = String(accordion.getAttribute('data-omo-nextcloud-path') || '');
								const content = accordion.querySelector('[data-omo-nextcloud-folder-content]');
								if (!Number.isInteger(folderId) || folderId <= 0 || !(content instanceof HTMLElement)) return;
								content.textContent = omoDocumentsNextcloudLoadingLabel;
								const endpoint = new URL('/omo/api/documents/nextcloud/browse.php', window.location.origin);
								endpoint.searchParams.set('id', String(folderId));
								endpoint.searchParams.set('path', path);
								fetch(endpoint.toString(), {credentials: 'same-origin', cache: 'no-store'})
									.then(function (response) { return response.json().then(function (payload) {
										if (!response.ok || !payload || payload.status !== true) throw new Error(String(payload && payload.message || omoDocumentsNextcloudErrorLabel));
										return payload;
									}); })
									.then(function (payload) {
										const entries = Array.isArray(payload.entries) ? payload.entries : [];
										if (entries.length === 0) { content.textContent = omoDocumentsNextcloudEmptyLabel; return; }
										const list = document.createElement('div');
										list.className = 'omo-documents__folder-children generic-file-list__children';
										if (state.density === 'compact') list.classList.add('omo-documents__folder-children--compact');
										content.replaceChildren(list);
                                        startProgressiveList(list, entries, function (entry, sentinel) {
                                            list.insertBefore(createItem(createNextcloudRemoteItem(folderId, entry)), sentinel);
                                        }, 'remote:' + folderId + ':' + path);
									})
									.catch(function (error) { content.textContent = String(error && error.message || omoDocumentsNextcloudErrorLabel); });
							};

                            const loadDocumentFolder = function (accordion) {
                                if (!(accordion instanceof HTMLElement)) {
                                    return;
                                }

                                const folderId = Number(accordion.getAttribute('data-omo-document-folder') || 0);
                                const content = accordion.querySelector('[data-omo-document-folder-content]');
                                const folder = findDocumentItemById(folderId);
                                if (
                                    !Number.isInteger(folderId)
                                    || folderId <= 0
                                    || !folder
                                    || folder.isFolder !== true
                                    || folder.childrenLoaded === true
                                    || state.loadingFolderIds.has(folderId)
                                    || !(content instanceof HTMLElement)
                                ) {
                                    return;
                                }

                                state.loadingFolderIds.add(folderId);
                                content.textContent = omoDocumentsFolderLoadingLabel;

                                const endpoint = new URL('/omo/api/documents/children.php', window.location.origin);
                                endpoint.searchParams.set('id', String(folderId));
                                endpoint.searchParams.set('oid', String(panel.getAttribute('data-omo-document-oid') || '0'));
                                endpoint.searchParams.set('cid', String(panel.getAttribute('data-omo-document-cid') || '0'));
                                endpoint.searchParams.set(
                                    'document_scope',
                                    String(panel.getAttribute('data-omo-document-scope') || 'contextual')
                                );
                                endpoint.searchParams.set(
                                    'is_pv_application_tab',
                                    panel.getAttribute('data-omo-document-pv-application-tab') === '1' ? '1' : '0'
                                );

                                fetch(endpoint.toString(), {credentials: 'same-origin', cache: 'no-store'})
                                    .then(function (response) {
                                        return response.json().catch(function () { return null; }).then(function (payload) {
                                            if (!response.ok || !payload || payload.status !== true) {
                                                throw new Error(String(payload && payload.message || omoDocumentsFolderErrorLabel));
                                            }
                                            return payload;
                                        });
                                    })
                                    .then(function (responsePayload) {
                                        const entries = Array.isArray(responsePayload.entries) ? responsePayload.entries : [];
                                        const childItems = [];

                                        entries.forEach(function (entry) {
                                            if (!entry || Number(entry.parentDocumentId || 0) !== folderId) {
                                                return;
                                            }
                                            const entryId = Number(entry.id || 0);
                                            if (!Number.isInteger(entryId) || entryId <= 0 || findDocumentItemById(entryId)) {
                                                return;
                                            }
                                            if (entry.isFolder === true) {
                                                entry.childrenLoaded = false;
                                            }
                                            documents.push(entry);
                                            childItems.push(entry);
                                        });

                                        childrenByParentId.set(folderId, childItems);
                                        folder.childrenLoaded = true;
                                        state.loadingFolderIds.delete(folderId);
                                        state.openFolderIds.add(folderId);
                                        render({preserveVisible: true});
                                    })
                                    .catch(function (error) {
                                        state.loadingFolderIds.delete(folderId);
                                        content.textContent = String(error && error.message || omoDocumentsFolderErrorLabel);
                                    });
                            };

                            const getDroppedFiles = function (event) {
                                const transfer = event && event.dataTransfer ? event.dataTransfer : null;
                                if (!transfer || !transfer.files || transfer.files.length === 0) {
                                    return [];
                                }

                                return Array.prototype.slice.call(transfer.files).filter(function (file) {
                                    return file instanceof File;
                                });
                            };

                            const isFileDrag = function (event) {
                                const transfer = event && event.dataTransfer ? event.dataTransfer : null;
                                if (!transfer) {
                                    return false;
                                }

                                return Array.prototype.indexOf.call(transfer.types || [], 'Files') !== -1
                                    || (transfer.files && transfer.files.length > 0);
                            };

                            const getDirectUploadTarget = function (event) {
                                const folder = event.target instanceof Element
                                    ? event.target.closest('[data-omo-document-folder]')
                                    : null;
                                if (folder && panel.contains(folder)) {
                                    const parentDocumentId = Number(folder.getAttribute('data-omo-document-folder') || 0);
                                    if (Number.isInteger(parentDocumentId) && parentDocumentId > 0) {
                                        return {
                                            element: folder,
                                            parentDocumentId: parentDocumentId,
                                            allowed: folder.getAttribute('data-omo-document-can-upload') === '1',
                                            label: folder.getAttribute('data-omo-document-can-upload') === '1'
                                                ? directUploadTexts.folder
                                                : directUploadTexts.forbidden
                                        };
                                    }
                                }

                                if (results.contains(event.target)) {
                                    return {
                                        element: results,
                                        parentDocumentId: 0,
                                        allowed: canUploadToCurrentContext,
                                        label: canUploadToCurrentContext
                                            ? directUploadTexts.root
                                            : directUploadTexts.forbidden
                                    };
                                }

                                return null;
                            };

                            const clearDirectUploadTarget = function () {
                                results.querySelectorAll('.is-file-drop-target').forEach(function (element) {
                                    element.classList.remove('is-file-drop-target');
                                    element.removeAttribute('data-file-drop-message');
                                    element.removeAttribute('data-file-drop-state');
                                });
                                results.classList.remove('is-file-drop-target');
                                results.removeAttribute('data-file-drop-message');
                                results.removeAttribute('data-file-drop-state');
                            };

                            const getDirectUploadTitle = function (file) {
                                const name = String(file && file.name || '').trim();
                                return (name !== '' ? name : 'Fichier').slice(0, 100);
                            };

                            const formatDirectUploadText = function (template, count) {
                                return String(template || '').replace('{count}', String(count));
                            };

                            const notifyDirectUpload = function (message, type) {
                                if (typeof window.omoNotify === 'function') {
                                    window.omoNotify(message, type);
                                }
                            };

                            const uploadDroppedFiles = async function (files, target) {
                                if (directUploadInProgress || !target || files.length === 0) {
                                    return;
                                }

                                const organizationId = Number(panel.getAttribute('data-omo-document-oid') || 0);
                                const holonId = Number(panel.getAttribute('data-omo-document-cid') || 0);
                                if (!Number.isInteger(organizationId) || organizationId <= 0) {
                                    notifyDirectUpload(directUploadTexts.error, 'error');
                                    return;
                                }

                                directUploadInProgress = true;
                                const errors = [];
                                let uploadedCount = 0;

                                for (const file of files) {
                                    const body = new FormData();
                                    body.set('oid', String(organizationId));
                                    if (Number.isInteger(holonId) && holonId > 0) {
                                        body.set('cid', String(holonId));
                                    }
                                    body.set('title', getDirectUploadTitle(file));
                                    body.set('document_type', 'uploaded_file');
                                    if (target.parentDocumentId > 0) {
                                        body.set('parent_document_id', String(target.parentDocumentId));
                                    }
                                    body.set('uploaded_file', file, file.name || getDirectUploadTitle(file));

                                    try {
                                        const response = await fetch(directUploadEndpoint, {
                                            method: 'POST',
                                            credentials: 'same-origin',
                                            headers: {'X-Requested-With': 'XMLHttpRequest'},
                                            body: body,
                                            cache: 'no-store'
                                        });
                                        const responsePayload = await response.json().catch(function () { return null; });
                                        if (!response.ok || !responsePayload || responsePayload.status !== true) {
                                            throw new Error(String(responsePayload && responsePayload.message || directUploadTexts.error));
                                        }
                                        uploadedCount++;
                                    } catch (error) {
                                        errors.push(String(error && error.message || directUploadTexts.error));
                                    }
                                }

                                directUploadInProgress = false;
                                if (uploadedCount > 0 && typeof window.omoRefreshDocumentsPanel === 'function') {
                                    await window.omoRefreshDocumentsPanel();
                                }
                                if (uploadedCount > 0) {
                                    notifyDirectUpload(formatDirectUploadText(directUploadTexts.success, uploadedCount), 'success');
                                }
                                if (errors.length > 0) {
                                    notifyDirectUpload(errors[0], 'error');
                                }
                            };

                            const getDraggedDocumentId = function (event) {
                                const transfer = event && event.dataTransfer ? event.dataTransfer : null;
                                const transferredId = transfer ? Number(transfer.getData('application/x-omo-document-id') || 0) : 0;
                                return Number.isInteger(transferredId) && transferredId > 0
                                    ? transferredId
                                    : draggedDocumentId;
                            };

                            const isInternalDocumentDrag = function (event) {
                                const transfer = event && event.dataTransfer ? event.dataTransfer : null;
                                if (!transfer) {
                                    return false;
                                }

                                return draggedDocumentId > 0
                                    || Array.prototype.indexOf.call(transfer.types || [], 'application/x-omo-document-id') !== -1;
                            };

                            const wouldMoveIntoOwnDescendant = function (documentItem, targetParentDocumentId) {
                                if (!documentItem || !documentItem.isFolder || targetParentDocumentId <= 0) {
                                    return false;
                                }

                                let candidateParentId = targetParentDocumentId;
                                const visitedParentIds = new Set();
                                while (candidateParentId > 0 && !visitedParentIds.has(candidateParentId)) {
                                    if (candidateParentId === Number(documentItem.id || 0)) {
                                        return true;
                                    }
                                    visitedParentIds.add(candidateParentId);
                                    const parent = documents.find(function (candidate) {
                                        return Number(candidate && candidate.id || 0) === candidateParentId;
                                    });
                                    candidateParentId = Number(parent && parent.parentDocumentId || 0);
                                }

                                return false;
                            };

                            const getDirectMoveTarget = function (event, documentItem) {
                                const folder = event.target instanceof Element
                                    ? event.target.closest('[data-omo-document-folder]')
                                    : null;
                                if (folder && panel.contains(folder)) {
                                    const parentDocumentId = Number(folder.getAttribute('data-omo-document-folder') || 0);
                                    if (!Number.isInteger(parentDocumentId) || parentDocumentId <= 0) {
                                        return null;
                                    }
                                    if (parentDocumentId === Number(documentItem && documentItem.parentDocumentId || 0)) {
                                        return null;
                                    }
                                    const canMoveIntoFolder = folder.getAttribute('data-omo-document-can-move-into') === '1'
                                        && !wouldMoveIntoOwnDescendant(documentItem, parentDocumentId);
                                    return {
                                        element: folder,
                                        parentDocumentId: parentDocumentId,
                                        holonId: 0,
                                        allowed: canMoveIntoFolder,
                                        label: canMoveIntoFolder ? directMoveTexts.folder : directMoveTexts.forbidden
                                    };
                                }

                                const itemShell = event.target instanceof Element
                                    ? event.target.closest('.omo-documents__item-shell')
                                    : null;
                                if (itemShell || !results.contains(event.target)) {
                                    return null;
                                }

                                const targetHolonId = Number(panel.getAttribute('data-omo-document-cid') || 0);
                                if (
                                    Number(documentItem && documentItem.parentDocumentId || 0) === 0
                                    && Number(documentItem && documentItem.holonId || 0) === targetHolonId
                                ) {
                                    return null;
                                }

                                return {
                                    element: results,
                                    parentDocumentId: 0,
                                    holonId: Number.isInteger(targetHolonId) && targetHolonId > 0 ? targetHolonId : 0,
                                    allowed: canMoveToCurrentContext,
                                    label: canMoveToCurrentContext ? directMoveTexts.root : directMoveTexts.forbidden
                                };
                            };

                            const clearDirectMoveTarget = function () {
                                results.querySelectorAll('.is-document-move-target').forEach(function (element) {
                                    element.classList.remove('is-document-move-target');
                                    element.removeAttribute('data-document-move-message');
                                    element.removeAttribute('data-document-move-state');
                                });
                                results.classList.remove('is-document-move-target');
                                results.removeAttribute('data-document-move-message');
                                results.removeAttribute('data-document-move-state');
                            };

                            const moveDraggedDocument = async function (documentItem, target) {
                                if (directMoveInProgress || !documentItem || !target) {
                                    return;
                                }

                                directMoveInProgress = true;
                                try {
                                    const response = await fetch(directMoveEndpoint, {
                                        method: 'POST',
                                        credentials: 'same-origin',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-Requested-With': 'XMLHttpRequest'
                                        },
                                        body: JSON.stringify({
                                            id: Number(documentItem.id || 0),
                                            targetHolonId: target.holonId,
                                            targetParentDocumentId: target.parentDocumentId
                                        }),
                                        cache: 'no-store'
                                    });
                                    const responsePayload = await response.json().catch(function () { return null; });
                                    if (!response.ok || !responsePayload || responsePayload.status !== 'ok') {
                                        throw new Error(String(responsePayload && responsePayload.message || directMoveTexts.forbidden));
                                    }
                                    if (Number(responsePayload.movedCount || 0) > 0) {
                                        if (typeof window.omoRefreshDocumentsPanel === 'function') {
                                            await window.omoRefreshDocumentsPanel();
                                        } else {
                                            window.location.reload();
                                            return;
                                        }
                                    }
                                    notifyDirectUpload(String(responsePayload.message || ''), 'success');
                                } catch (error) {
                                    notifyDirectUpload(String(error && error.message || directMoveTexts.forbidden), 'error');
                                } finally {
                                    directMoveInProgress = false;
                                }
                            };

                            results.addEventListener('dragstart', function (event) {
                                const card = event.target instanceof Element
                                    ? event.target.closest('[data-omo-document-drag-id]')
                                    : null;
                                if (!card || !results.contains(card) || !event.dataTransfer) {
                                    return;
                                }
                                const documentId = Number(card.getAttribute('data-omo-document-drag-id') || 0);
                                if (!Number.isInteger(documentId) || documentId <= 0) {
                                    return;
                                }
                                draggedDocumentId = documentId;
                                event.dataTransfer.effectAllowed = 'move';
                                event.dataTransfer.setData('application/x-omo-document-id', String(documentId));
                                event.dataTransfer.setData('text/plain', String(documentId));
                                card.classList.add('is-document-dragging');
                            });

                            results.addEventListener('dragend', function () {
                                draggedDocumentId = 0;
                                results.querySelectorAll('.is-document-dragging').forEach(function (element) {
                                    element.classList.remove('is-document-dragging');
                                });
                                clearDirectMoveTarget();
                            });

                            results.addEventListener('dragenter', function (event) {
                                if (!isInternalDocumentDrag(event)) {
                                    return;
                                }
                                const documentItem = documents.find(function (candidate) {
                                    return Number(candidate && candidate.id || 0) === getDraggedDocumentId(event);
                                });
                                const target = getDirectMoveTarget(event, documentItem);
                                if (!documentItem || !target) {
                                    return;
                                }
                                event.preventDefault();
                                clearDirectMoveTarget();
                                target.element.classList.add('is-document-move-target');
                                target.element.setAttribute('data-document-move-message', target.label);
                                target.element.setAttribute('data-document-move-state', target.allowed ? 'allowed' : 'forbidden');
                            });

                            results.addEventListener('dragover', function (event) {
                                if (!isInternalDocumentDrag(event)) {
                                    return;
                                }
                                const documentItem = documents.find(function (candidate) {
                                    return Number(candidate && candidate.id || 0) === getDraggedDocumentId(event);
                                });
                                const target = getDirectMoveTarget(event, documentItem);
                                if (!documentItem || !target) {
                                    return;
                                }
                                event.preventDefault();
                                if (event.dataTransfer) {
                                    event.dataTransfer.dropEffect = target.allowed ? 'move' : 'none';
                                }
                                clearDirectMoveTarget();
                                target.element.classList.add('is-document-move-target');
                                target.element.setAttribute('data-document-move-message', target.label);
                                target.element.setAttribute('data-document-move-state', target.allowed ? 'allowed' : 'forbidden');
                            });

                            results.addEventListener('dragleave', function (event) {
                                if (!isInternalDocumentDrag(event)) {
                                    return;
                                }
                                const relatedTarget = event.relatedTarget;
                                if (relatedTarget instanceof Node && results.contains(relatedTarget)) {
                                    return;
                                }
                                clearDirectMoveTarget();
                            });

                            results.addEventListener('drop', function (event) {
                                if (!isInternalDocumentDrag(event)) {
                                    return;
                                }
                                const documentItem = documents.find(function (candidate) {
                                    return Number(candidate && candidate.id || 0) === getDraggedDocumentId(event);
                                });
                                const target = getDirectMoveTarget(event, documentItem);
                                clearDirectMoveTarget();
                                if (!documentItem || !target) {
                                    return;
                                }
                                event.preventDefault();
                                if (!target.allowed) {
                                    notifyDirectUpload(directMoveTexts.forbidden, 'error');
                                    return;
                                }
                                moveDraggedDocument(documentItem, target);
                            });

                            results.addEventListener('dragenter', function (event) {
                                if (!isFileDrag(event)) {
                                    return;
                                }
                                const target = getDirectUploadTarget(event);
                                if (!target) {
                                    return;
                                }
                                event.preventDefault();
                                clearDirectUploadTarget();
                                target.element.classList.add('is-file-drop-target');
                                target.element.setAttribute('data-file-drop-message', target.label);
                                target.element.setAttribute('data-file-drop-state', target.allowed ? 'allowed' : 'forbidden');
                            });

                            results.addEventListener('dragover', function (event) {
                                if (!isFileDrag(event)) {
                                    return;
                                }
                                const target = getDirectUploadTarget(event);
                                if (!target) {
                                    return;
                                }
                                event.preventDefault();
                                if (event.dataTransfer) {
                                    event.dataTransfer.dropEffect = target.allowed ? 'copy' : 'none';
                                }
                                clearDirectUploadTarget();
                                target.element.classList.add('is-file-drop-target');
                                target.element.setAttribute('data-file-drop-message', target.label);
                                target.element.setAttribute('data-file-drop-state', target.allowed ? 'allowed' : 'forbidden');
                            });

                            results.addEventListener('dragleave', function (event) {
                                const relatedTarget = event.relatedTarget;
                                if (relatedTarget instanceof Node && results.contains(relatedTarget)) {
                                    return;
                                }
                                clearDirectUploadTarget();
                            });

                            results.addEventListener('drop', function (event) {
                                const files = getDroppedFiles(event);
                                const target = getDirectUploadTarget(event);
                                clearDirectUploadTarget();
                                if (!target || files.length === 0) {
                                    return;
                                }
                                event.preventDefault();
                                if (!target.allowed) {
                                    notifyDirectUpload(directUploadTexts.forbidden, 'error');
                                    return;
                                }
                                uploadDroppedFiles(files, target);
                            });

                            const persistOpenFolderState = function () {
                                omoDocumentsWriteSessionCookie(
                                    folderStateCookieName,
                                    Array.from(state.openFolderIds).join(',')
                                );
                            };

                            const syncFolderAccordionState = function (persistState) {
                                const nextOpenFolderIds = new Set();
                                const renderedFolderIds = new Set();

                                results.querySelectorAll('[data-omo-document-folder-toggle]').forEach(function (toggle) {
                                    const accordion = toggle.closest('[data-generic-accordion]');
                                    const isExpanded = !!accordion && !accordion.classList.contains('is-collapsed');
                                    const folderId = accordion
                                        ? Number(accordion.getAttribute('data-omo-document-folder') || 0)
                                        : 0;
                                    toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
                                    const folderIcon = accordion
                                        ? accordion.querySelector('[data-omo-document-folder-icon]')
                                        : null;
                                    if (folderIcon instanceof HTMLImageElement) {
                                        folderIcon.src = omoDocumentsGetFolderIconUrl(isExpanded);
                                    }

                                    if (isExpanded && Number.isInteger(folderId) && folderId > 0) {
                                        nextOpenFolderIds.add(folderId);
                                    }
                                    if (Number.isInteger(folderId) && folderId > 0) {
                                        renderedFolderIds.add(folderId);
                                    }
                                });

                                if (normalizeQuickSearch(state.query) === '') {
                                    state.openFolderIds.forEach(function (folderId) {
                                        if (!renderedFolderIds.has(folderId)) {
                                            nextOpenFolderIds.add(folderId);
                                        }
                                    });
                                    state.openFolderIds = nextOpenFolderIds;
                                }

                                if (persistState === true && normalizeQuickSearch(state.query) === '') {
                                    persistOpenFolderState();
                                }
                            };

                            const setDetailHeader = function (documentItem) {
                                if (!detailTitle || !detailDescription) {
                                    return;
                                }

                                if (detailDrawerController) {
                                    detailDrawerController.setHeader({
                                        title: documentItem && documentItem.title
                                            ? documentItem.title
                                            : pageConfig.title,
                                        description: documentItem && documentItem.fullDateLabel
                                            ? 'Document créé le ' + documentItem.fullDateLabel + '.'
                                            : pageConfig.documentsDrawerDetailDescription,
                                        actions: []
                                    });
                                    return;
                                }

                                detailTitle.textContent = documentItem && documentItem.title
                                    ? documentItem.title
                                    : 'Détail du document';
                                detailDescription.textContent = documentItem && documentItem.fullDateLabel
                                    ? 'Document créé le ' + documentItem.fullDateLabel + '.'
                                    : pageConfig.documentsDrawerDetailDescription;
                            };

                            const openDetailDrawer = function () {
                                if (!detailDrawer) {
                                    return;
                                }

                                if (detailDrawerController && typeof detailDrawerController.open === 'function') {
                                    detailDrawerController.open();
                                    return;
                                }

                                detailDrawer.hidden = false;
                                void detailDrawer.offsetWidth;
                                requestAnimationFrame(function () {
                                    detailDrawer.classList.add('is-open');
                                });
                            };

                            const clearCollaboraTokenRefresh = function () {
                                if (!detailDrawer || typeof detailDrawer.__omoCollaboraTokenRefreshCleanup !== 'function') {
                                    return;
                                }

                                detailDrawer.__omoCollaboraTokenRefreshCleanup();
                                delete detailDrawer.__omoCollaboraTokenRefreshCleanup;
                            };

                            const setupCollaboraTokenRefresh = function () {
                                clearCollaboraTokenRefresh();
                                if (!detailDrawer || !detailBody) {
                                    return;
                                }

                                const frame = detailBody.querySelector('.omo-document-collabora__frame');
                                const detail = detailBody.querySelector('[data-omo-collabora-origin]');
                                const documentId = frame instanceof HTMLIFrameElement
                                    ? Number(frame.getAttribute('data-omo-collabora-document-id') || 0)
                                    : 0;
								const remotePath = frame instanceof HTMLIFrameElement
									? String(frame.getAttribute('data-omo-collabora-remote-path') || '').trim()
									: '';
                                const collaboraOrigin = detail instanceof HTMLElement
                                    ? String(detail.getAttribute('data-omo-collabora-origin') || '').trim()
                                    : '';

                                if (!(frame instanceof HTMLIFrameElement) || !Number.isInteger(documentId) || documentId <= 0 || collaboraOrigin === '') {
                                    return;
                                }

                                let refreshTimer = 0;
                                let refreshInFlight = false;
                                let editorReady = false;

                                const clearRefreshTimer = function () {
                                    if (refreshTimer) {
                                        window.clearTimeout(refreshTimer);
                                        refreshTimer = 0;
                                    }
                                };

                                const postToCollabora = function (messageId, values) {
                                    if (!frame.contentWindow || !document.body.contains(frame)) {
                                        return false;
                                    }

                                    frame.contentWindow.postMessage(JSON.stringify({
                                        MessageId: messageId,
                                        SendTime: Date.now(),
                                        Values: values || {},
                                    }), collaboraOrigin);
                                    return true;
                                };

                                const scheduleRefresh = function (delay) {
                                    clearRefreshTimer();
                                    refreshTimer = window.setTimeout(function () {
                                        refreshWopiToken();
                                    }, Math.max(60000, Number(delay) || 0));
                                };

                                const refreshWopiToken = function () {
                                    if (refreshInFlight || !editorReady || !document.body.contains(frame)) {
                                        return;
                                    }

                                    refreshInFlight = true;
                                    const body = new URLSearchParams();
                                    body.set('id', String(documentId));
									if (remotePath !== '') {
										body.set('path', remotePath);
									}

                                    fetch('/omo/api/documents/collabora/token.php', {
                                        method: 'POST',
                                        credentials: 'same-origin',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                                        },
                                        body: body.toString(),
                                    })
                                        .then(function (response) {
                                            if (!response.ok) {
                                                throw new Error('Unable to renew the Collabora token.');
                                            }
                                            return response.json();
                                        })
                                        .then(function (payload) {
                                            const accessToken = payload && typeof payload.accessToken === 'string'
                                                ? payload.accessToken.trim()
                                                : '';
                                            if (!payload || payload.status !== true || accessToken === '' || !postToCollabora('Reset_Access_Token', {token: accessToken})) {
                                                throw new Error('Invalid Collabora token renewal response.');
                                            }

                                            const expiresAt = Number(payload.expiresAt || 0) * 1000;
                                            const refreshDelay = Number.isFinite(expiresAt)
                                                ? expiresAt - Date.now() - (15 * 60 * 1000)
                                                : 30 * 60 * 1000;
                                            scheduleRefresh(refreshDelay);
                                        })
                                        .catch(function () {
                                            if (document.body.contains(frame)) {
                                                scheduleRefresh(60 * 1000);
                                            }
                                        })
                                        .finally(function () {
                                            refreshInFlight = false;
                                        });
                                };

                                const onCollaboraMessage = function (event) {
                                    if (event.origin !== collaboraOrigin || event.source !== frame.contentWindow) {
                                        return;
                                    }

                                    let message = event.data;
                                    if (typeof message === 'string') {
                                        try {
                                            message = JSON.parse(message);
                                        } catch (error) {
                                            return;
                                        }
                                    }

                                    if (!message || typeof message !== 'object') {
                                        return;
                                    }

                                    const messageId = String(message.MessageId || '').trim();
                                    const values = message.Values && typeof message.Values === 'object' ? message.Values : {};
                                    if (messageId === 'App_LoadingStatus' && values.Status === 'Document_Loaded') {
                                        editorReady = postToCollabora('Host_PostmessageReady');
                                        if (editorReady) {
                                            scheduleRefresh(20 * 60 * 1000);
                                        }
                                        return;
                                    }

                                    if (messageId === 'App_TokenExpired') {
                                        refreshWopiToken();
                                    }
                                };

                                window.addEventListener('message', onCollaboraMessage);
                                detailDrawer.__omoCollaboraTokenRefreshCleanup = function () {
                                    clearRefreshTimer();
                                    window.removeEventListener('message', onCollaboraMessage);
                                };
                            };

                            const closeDetailDrawer = function () {
                                if (!detailDrawer) {
                                    return;
                                }

                                clearCollaboraTokenRefresh();
                                detailDrawer.classList.remove('is-open');
                                detailDrawer.dataset.omoDocumentActiveId = '';
                                state.activeDocumentId = null;

                                window.setTimeout(function () {
                                    if (!detailDrawer.classList.contains('is-open')) {
                                        detailDrawer.hidden = true;
                                    }
                                }, 200);
                            };

                            if (detailDrawer) {
                                const notifyEthercalcFrameResize = function () {
                                    const frame = detailDrawer.querySelector('.omo-document-ethercalc__frame');
                                    if (!(frame instanceof HTMLIFrameElement) || !frame.contentWindow) {
                                        return;
                                    }

                                    [0, 80, 260, 700].forEach(function (delay) {
                                        window.setTimeout(function () {
                                            const bounds = frame.getBoundingClientRect();
                                            frame.contentWindow.postMessage({
                                                type: 'omo-ethercalc-resize',
                                                viewport: {
                                                    width: Math.round(bounds.width),
                                                    height: Math.round(bounds.height),
                                                },
                                            }, '*');
                                        }, delay);
                                    });
                                };

                                const syncDocumentFullscreenButton = function () {
                                    const fullscreenButton = detailDrawer.querySelector('[data-omo-document-fullscreen]');
                                     const frame = detailDrawer.querySelector('.omo-document-etherpad__frame, .omo-document-ethercalc__frame, .omo-document-collabora__frame, .omo-document-spacedeck__frame, .omo-document-file__pdf-frame');
                                    if (!(fullscreenButton instanceof HTMLButtonElement) || !(frame instanceof HTMLElement)) {
                                        return;
                                    }

                                    const isFullscreen = document.fullscreenElement === frame;
                                    fullscreenButton.setAttribute('aria-pressed', isFullscreen ? 'true' : 'false');
                                    fullscreenButton.textContent = isFullscreen
                                        ? (fullscreenButton.dataset.omoDocumentExitFullscreenLabel || '')
                                        : (fullscreenButton.dataset.omoDocumentFullscreenLabel || '');

                                    if (frame.classList.contains('omo-document-ethercalc__frame')) {
                                        notifyEthercalcFrameResize();
                                    }
                                };

                                document.addEventListener('fullscreenchange', syncDocumentFullscreenButton);
                                detailDrawer.addEventListener('click', function (event) {
                                    const deleteButton = event.target.closest('[data-omo-document-delete-id]');
                                    if (deleteButton) {
                                        event.preventDefault();

                                        const documentId = Number(deleteButton.getAttribute('data-omo-document-delete-id') || 0);
                                        const confirmation = String(deleteButton.getAttribute('data-omo-document-delete-confirm') || '');
                                        const fallbackError = String(deleteButton.getAttribute('data-omo-document-delete-error') || pageConfig.documentsMenuActionError);
                                        if (!Number.isInteger(documentId) || documentId <= 0 || !window.confirm(confirmation)) {
                                            return;
                                        }

                                        deleteButton.disabled = true;
                                        window.fetch('/omo/api/documents/lifecycle_action.php', {
                                            method: 'POST',
                                            credentials: 'same-origin',
                                            headers: {
                                                'Accept': 'application/json',
                                                'Content-Type': 'application/json',
                                            },
                                            body: JSON.stringify({id: documentId, action: 'delete', allow_event_document: true}),
                                        })
                                            .then(function (response) {
                                                return response.json()
                                                    .catch(function () { return null; })
                                                    .then(function (payload) {
                                                        if (!response.ok || !payload || payload.status !== true) {
                                                            throw new Error(String(payload && payload.message || fallbackError));
                                                        }
                                                    });
                                            })
                                            .then(function () {
                                                closeDetailDrawer();
                                                if (typeof window.omoRefreshDocumentsPanel === 'function') {
                                                    return window.omoRefreshDocumentsPanel();
                                                }
                                                window.location.reload();
                                                return null;
                                            })
                                            .catch(function (error) {
                                                window.omoNotify(String(error && error.message || fallbackError), 'error');
                                            })
                                            .finally(function () {
                                                deleteButton.disabled = false;
                                            });
                                        return;
                                    }

                                    const fullscreenButton = event.target.closest('[data-omo-document-fullscreen]');
                                    if (fullscreenButton) {
                                        event.preventDefault();
                                         const frame = detailDrawer.querySelector('.omo-document-etherpad__frame, .omo-document-ethercalc__frame, .omo-document-collabora__frame, .omo-document-spacedeck__frame, .omo-document-file__pdf-frame');
                                        if (!(frame instanceof HTMLElement)) {
                                            return;
                                        }

                                        if (document.fullscreenElement === frame) {
                                            document.exitFullscreen().catch(function () {});
                                        } else if (typeof frame.requestFullscreen === 'function') {
                                            frame.requestFullscreen().then(function () {
                                                if (frame.classList.contains('omo-document-ethercalc__frame')) {
                                                    notifyEthercalcFrameResize();
                                                }
                                            }).catch(function () {});
                                        }
                                        return;
                                    }

                                    const editButton = event.target.closest('[data-omo-document-open-editor-url]');
                                    if (!editButton) {
                                        return;
                                    }

                                    event.preventDefault();
                                    const editUrl = String(editButton.getAttribute('data-omo-document-open-editor-url') || '').trim();
                                    if (editUrl !== '' && typeof window.omoOpenDocumentEditorDrawer === 'function') {
                                        window.omoOpenDocumentEditorDrawer(editUrl, 'Modifier le document', 'Modification du document dans le contexte courant.');
                                    }
                                });
                            }

                            const renderDetailLoading = function () {
                                clearCollaboraTokenRefresh();
                                if (!detailBody) {
                                    return;
                                }

                                detailBody.innerHTML = window.getSkeleton
                                    ? getSkeleton('panel')
                                    : ("<div class=\"loading\">" + pageConfig.documentsActionLoading + "</div>");
                            };

                            const renderDetailError = function () {
                                clearCollaboraTokenRefresh();
                                if (!detailBody) {
                                    return;
                                }

                                detailBody.innerHTML = ("<div class=\"loading\"><div class=\"omo-empty-state\">" + pageConfig.documentsErrorLoadDocument + "</div></div>");
                            };

                            const openExternalDocumentWindow = function (documentItem) {
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
                            };

                            const openDocumentDetail = function (documentItem) {
								if (!detailDrawer || !detailBody || !documentItem || (!documentItem.id && !documentItem.isRemoteFile) || documentItem.isFolder) {
                                    return;
                                }

                                if (
                                    isPvApplicationTab
                                    && String(documentItem.documentType || '').trim().toLowerCase() === 'pv'
                                    && documentItem.canOpenInPvApplicationTab !== true
                                ) {
                                    return;
                                }

                                if (
                                    String(documentItem.documentType || '').trim().toLowerCase() === 'pv'
                                    && String(documentItem.pvPreparationUrl || '').trim() !== ''
                                    && typeof window.omoOpenDocumentPvPreparationByPayload === 'function'
                                    && window.omoOpenDocumentPvPreparationByPayload(documentItem)
                                ) {
                                    return;
                                }

                                if (documentItem.openInNewWindow && documentItem.externalUrl) {
                                    openExternalDocumentWindow(documentItem);
                                    return;
                                }

								const activeDocumentKey = documentItem.isRemoteFile
									? String(documentItem.remoteKey || documentItem.contextUrl || '')
									: String(Number(documentItem.id));
								state.activeDocumentId = activeDocumentKey;
								detailDrawer.dataset.omoDocumentActiveId = activeDocumentKey;
                                setDetailHeader(documentItem);
                                renderDetailLoading();
                                openDetailDrawer();

                                const requestToken = ++detailRequestToken;
                                const detailUrl = documentItem.contextUrl
                                    ? String(documentItem.contextUrl)
                                    : '/omo/api/documents/detail.php?id=' + encodeURIComponent(documentItem.id);

                                $.ajax({
                                    url: detailUrl,
                                    method: 'GET',
                                    cache: false,
                                    success: function (data) {
										if (requestToken !== detailRequestToken || state.activeDocumentId !== activeDocumentKey) {
                                            return;
                                        }

                                        detailBody.innerHTML = data;
                                        setupCollaboraTokenRefresh();
                                        const temp = document.createElement('div');
                                        temp.innerHTML = data;
                                        syncDocumentDetailDrawerMetadata(temp, detailDrawer, documentItem.title || '', detailDescription ? detailDescription.textContent : '');
                                        if (detailDrawerController) {
                                            detailDrawerController.applyContentHeader(detailBody);
                                        }
                                    },
                                    error: function () {
                                        if (requestToken !== detailRequestToken) {
                                            return;
                                        }

                                        const responseHtml = arguments.length > 0
                                            && arguments[0]
                                            && typeof arguments[0].responseText === 'string'
                                            ? String(arguments[0].responseText).trim()
                                            : '';

                                        if (responseHtml !== '') {
                                            detailBody.innerHTML = responseHtml;
                                            setupCollaboraTokenRefresh();
                                            return;
                                        }

                                        renderDetailError();
                                    }
                                });
                            };

                            const renderEmptyState = function () {
                                const emptyState = document.createElement('div');
                                emptyState.className = 'omo-documents__empty omo-empty-state';
                                emptyState.textContent = emptyStateMessage;
                                results.replaceChildren(emptyState);
                            };

                            const renderSearchEmptyState = function () {
                                const emptyState = document.createElement('div');
                                emptyState.className = 'omo-documents__empty omo-empty-state';
                                emptyState.textContent = pageConfig.documentsSearchEmpty;
                                results.replaceChildren(emptyState);
                            };

                            const renderByTemporal = function (sortMode, rootItems) {
                                const groupedDocuments = new Map();

                                rootItems.forEach(function (documentItem) {
                                    const groupKey = getDocumentGroupKey(documentItem, sortMode);

                                    if (!groupedDocuments.has(groupKey)) {
                                        groupedDocuments.set(groupKey, []);
                                    }

                                    groupedDocuments.get(groupKey).push(documentItem);
                                });

                                const entries = groups.flatMap(function (group) {
                                    return (groupedDocuments.get(group.key || '') || []).map(function (item) {
                                        return {group: group, item: item};
                                    });
                                });
                                const lists = new Map();
                                results.replaceChildren();
                                startProgressiveList(results, entries, function (entry, sentinel) {
                                    const group = entry.group;
                                    let list = lists.get(group.key);
                                    if (!list) {
                                        const section = document.createElement('section');
                                        section.className = 'omo-documents__group omo-panel-group generic-file-list__group';

                                        const title = document.createElement('h3');
                                        title.className = 'omo-panel-group__title generic-file-list__group-title';
                                        title.textContent = group.label || '';

                                        list = document.createElement('div');
                                        list.className = 'omo-documents__list omo-panel-view__body_content';

                                        if (state.density === 'compact') {
                                            list.classList.add('omo-documents__list--compact', 'generic-file-list__table');
                                            list.appendChild(createCompactListHeader(sortMode));
                                        }

                                        section.appendChild(title);
                                        section.appendChild(list);
                                        results.insertBefore(section, sentinel);
                                        lists.set(group.key, list);
                                    }
                                    list.appendChild(createItem(entry.item));
                                }, 'root');
                            };

                            const renderByAlpha = function (rootItems) {
                                const list = document.createElement('div');
                                list.className = 'omo-documents__list omo-documents__list--alphabetical omo-panel-view__body_content';

                                if (state.density === 'compact') {
                                    list.classList.add('omo-documents__list--compact', 'generic-file-list__table');
                                    list.appendChild(createCompactListHeader('updated'));
                                }

                                results.replaceChildren(list);
                                startProgressiveList(list, rootItems, function (documentItem, sentinel) {
                                    list.insertBefore(createItem(documentItem), sentinel);
                                }, 'root');
                            };

                            const syncButtons = function (selector, activeValue, attributeName) {
                                panel.querySelectorAll(selector).forEach(function (button) {
                                    const isActive = button.getAttribute(attributeName) === activeValue;
                                    button.classList.toggle('is-active', isActive);
                                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                                });
                            };

                            const syncFilterChips = function () {
                                const currentScope = omoDocumentsNormalizeScope(
                                    panel.getAttribute('data-omo-document-scope') || 'contextual'
                                );
                                [
                                    {button: '[data-omo-document-scope-toggle="' + currentScope + '"]', chip: '[data-omo-documents-scope-chip]'},
                                    {button: '[data-omo-documents-sort="' + state.sort + '"]', chip: '[data-omo-documents-sort-chip]'},
                                    {button: '[data-omo-documents-density="' + state.density + '"]', chip: '[data-omo-documents-density-chip]'}
                                ].forEach(function (entry) {
                                    const button = panel.querySelector(entry.button);
                                    const chip = panel.querySelector(entry.chip);
                                    if (button && chip) {
                                        chip.textContent = button.textContent.trim();
                                    }
                                });
                            };

                            const render = function (options = {}) {
                                progressiveLists.forEach(function (list) { list.destroy(); });
                                progressiveLists.clear();
                                if (!options.preserveVisible) renderedCounts.clear();
                                if (typeof closeDocumentMenus === 'function') {
                                    closeDocumentMenus();
                                }

                                panel.classList.toggle('omo-documents--compact', state.density === 'compact');
                                panel.classList.toggle(
                                    'omo-documents--compact-date-sort',
                                    state.density === 'compact' && state.sort !== 'alpha'
                                );
                                results.classList.toggle('generic-file-list--structured', state.density === 'compact');
                                results.classList.toggle('generic-file-list--stacked-sticky', state.density === 'compact');
                                panel.setAttribute('data-omo-document-sort', state.sort);
                                panel.setAttribute('data-omo-document-density', state.density);
                                const query = normalizeQuickSearch(state.query);
                                const visibleTree = getSortedTree(state.sort, 0);

                                if (documents.length === 0) {
                                    renderEmptyState();
                                    syncDocumentSelection();
                                    syncButtons('[data-omo-documents-sort]', state.sort, 'data-omo-documents-sort');
                                    syncButtons('[data-omo-documents-density]', state.density, 'data-omo-documents-density');
                                    syncFilterChips();
                                    return;
                                }

                                if (query !== '' && visibleTree.length === 0) {
                                    renderSearchEmptyState();
                                } else if (state.sort === 'alpha') {
                                    renderByAlpha(visibleTree);
                                } else {
                                    renderByTemporal(state.sort, visibleTree);
                                }

                                syncButtons('[data-omo-documents-sort]', state.sort, 'data-omo-documents-sort');
                                syncButtons('[data-omo-documents-density]', state.density, 'data-omo-documents-density');
                                syncFilterChips();
                                const headerCount = panel.querySelector('[data-omo-documents-header-count]');
                                if (headerCount) {
                                    headerCount.textContent = query === ''
                                        ? pageConfig.visibleDocumentsCount
                                        : String(documents.filter(function (documentItem) {
                                            return documentMatchesQuickSearch(documentItem, query);
                                        }).length);
                                }
                                if (typeof window.syncGenericFileLists === 'function') {
                                    window.syncGenericFileLists(results);
                                }
                            };

                            const normalizeDocumentOpenMode = function (value) {
                                return String(value || '').trim().toLowerCase() === 'edit'
                                    ? 'edit'
                                    : 'detail';
                            };

                            const buildPanelDocumentOpenUrl = function (documentId, mode, scopeOverride) {
                                const resolvedDocumentId = Number(documentId || 0);
                                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                                    return '';
                                }

                                const normalizedMode = normalizeDocumentOpenMode(mode || 'detail');
                                const organizationId = Number(panel.getAttribute('data-omo-document-oid') || 0);
                                const holonId = Number(panel.getAttribute('data-omo-document-cid') || 0);
                                const requestedScope = String(scopeOverride || '').trim().toLowerCase();
                                const normalizedScope = requestedScope !== ''
                                    ? normalizeDocumentScope(requestedScope)
                                    : normalizeDocumentScope(panel.getAttribute('data-omo-document-scope') || 'contextual');
                                const query = [];

                                if (organizationId > 0) {
                                    query.push('oid=' + encodeURIComponent(String(organizationId)));
                                }

                                if (holonId > 0) {
                                    query.push('cid=' + encodeURIComponent(String(holonId)));
                                }

                                query.push('open_document_id=' + encodeURIComponent(String(resolvedDocumentId)));

                                if (normalizedMode === 'edit') {
                                    query.push('open_document_mode=edit');
                                }

                                query.push('document_scope=' + encodeURIComponent(normalizedScope));

                                return '/omo/api/documents/index.php' + (query.length > 0 ? '?' + query.join('&') : '');
                            };

                            const buildDirectDocumentPayload = function (documentId) {
                                const resolvedDocumentId = Number(documentId || 0);
                                if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
                                    return null;
                                }

                                const organizationId = Number(panel.getAttribute('data-omo-document-oid') || 0);
                                if (!Number.isInteger(organizationId) || organizationId <= 0) {
                                    return null;
                                }

                                return {
                                    id: resolvedDocumentId,
                                    contextUrl: '/omo/api/documents/detail.php?id='
                                        + encodeURIComponent(String(resolvedDocumentId))
                                        + '&oid='
                                        + encodeURIComponent(String(organizationId)),
                                    title: '',
                                    fullDateLabel: '',
                                    isFolder: false,
                                    openInNewWindow: false,
                                    externalUrl: '',
                                    pvPreparationUrl: ''
                                };
                            };

                            const refreshPanelForDocumentRoute = function (documentId, mode, scopeOverride) {
                                if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
                                    return false;
                                }

                                const targetUrl = buildPanelDocumentOpenUrl(documentId, mode, scopeOverride);
                                if (targetUrl === '') {
                                    return false;
                                }

                                window.omoReplaceFetchedPanelRoot({
                                    rootSelector: '#omo-documents-root',
                                    currentRoot: panel,
                                    url: targetUrl,
                                    setLoadingState: function (isLoading) {
                                        if (typeof window.omoSetDocumentsPanelLoadingState === 'function') {
                                            window.omoSetDocumentsPanelLoadingState(panel, isLoading, {
                                                showResultsPlaceholder: true
                                            });
                                        } else {
                                            panel.classList.toggle('is-loading', !!isLoading);
                                        }
                                    }
                                }).then(function (nextPanel) {
                                    if (!(nextPanel instanceof Element) || !document.body.contains(nextPanel)) {
                                        return;
                                    }

                                    const nextDataNode = nextPanel.querySelector('[data-omo-documents-data]');
                                    if (!(nextDataNode instanceof Element)) {
                                        return;
                                    }

                                    let nextPayload = null;
                                    try {
                                        nextPayload = JSON.parse(nextDataNode.textContent || '{}');
                                    } catch (error) {
                                        return;
                                    }

                                    if (Number(nextPayload && nextPayload.openDocumentId || 0) !== documentId) {
                                        return;
                                    }

                                    if (typeof nextPanel.__omoDocumentsApplyRouteChange === 'function') {
                                        nextPanel.__omoDocumentsApplyRouteChange({
                                            documentId: documentId,
                                            mode: normalizeDocumentOpenMode(mode || 'detail'),
                                            forcedScope: scopeOverride || ''
                                        });
                                    }
                                }).catch(function () {
                                    if (typeof window.omoSetDocumentsPanelLoadingState === 'function') {
                                        window.omoSetDocumentsPanelLoadingState(panel, false);
                                    } else {
                                        panel.classList.remove('is-loading');
                                    }
                                });

                                return true;
                            };

                            const openInitialDocumentFromPayload = function () {
                                const documentId = Number(payload.openDocumentId || 0);
                                if (!Number.isInteger(documentId) || documentId <= 0) {
                                    return false;
                                }

                                const openMode = normalizeDocumentOpenMode(payload.openDocumentMode || 'detail');

                                const documentItem = findDocumentItemById(documentId)
                                    || (
                                        requestedDocument
                                        && Number(requestedDocument.id || 0) === documentId
                                        ? requestedDocument
                                        : null
                                    );
                                if (!documentItem) {
                                    return false;
                                }

                                let opened = false;
                                if (openMode === 'edit' && typeof window.omoOpenDocumentEditorByPayload === 'function') {
                                    opened = window.omoOpenDocumentEditorByPayload(documentItem, panel) === true;
                                } else if (openMode !== 'edit' && typeof window.omoOpenDocumentDetailByPayload === 'function') {
                                    opened = window.omoOpenDocumentDetailByPayload(documentItem, panel) === true;
                                }

                                if (!opened) {
                                    return false;
                                }

                                payload.openDocumentId = 0;
                                payload.openDocumentMode = 'detail';
                                payload.requestedDocument = null;
                                return true;
                            };

                            const retryInitialDocumentOpen = function (attempt) {
                                if (!document.body.contains(panel)) {
                                    return;
                                }

                                if (openInitialDocumentFromPayload() || attempt >= 8) {
                                    return;
                                }

                                window.setTimeout(function () {
                                    retryInitialDocumentOpen(attempt + 1);
                                }, 50);
                            };

                            const getActiveDisplayFilters = function () {
                                return {
                                    scope: omoDocumentsNormalizeScope(
                                        panel.getAttribute('data-omo-document-scope') || 'contextual'
                                    ),
                                    sort: omoDocumentsNormalizeSortPreference(state.sort),
                                    density: omoDocumentsNormalizeDensityPreference(state.density)
                                };
                            };

                            const normalizeDisplayFilters = function (filters) {
                                const active = getActiveDisplayFilters();
                                let scope = omoDocumentsNormalizeScope(filters && filters.scope);
                                if (!panel.querySelector('[data-omo-document-scope-toggle="' + scope + '"]')) {
                                    scope = active.scope;
                                }
                                return {
                                    scope: scope,
                                    sort: omoDocumentsNormalizeSortPreference(filters && filters.sort),
                                    density: omoDocumentsNormalizeDensityPreference(filters && filters.density)
                                };
                            };

                            const syncFilterChoices = function () {
                                if (!pendingDisplayFilters) {
                                    return;
                                }
                                pendingDisplayFilters = normalizeDisplayFilters(pendingDisplayFilters);
                                [
                                    {selector: '[data-omo-document-scope-toggle]', attribute: 'data-omo-document-scope-toggle', value: pendingDisplayFilters.scope},
                                    {selector: '[data-omo-documents-sort]', attribute: 'data-omo-documents-sort', value: pendingDisplayFilters.sort},
                                    {selector: '[data-omo-documents-density]', attribute: 'data-omo-documents-density', value: pendingDisplayFilters.density}
                                ].forEach(function (choice) {
                                    panel.querySelectorAll(choice.selector).forEach(function (button) {
                                        const active = button.getAttribute(choice.attribute) === choice.value;
                                        button.classList.toggle('is-active', active);
                                        button.setAttribute('aria-pressed', active ? 'true' : 'false');
                                    });
                                });
                            };

                            const applyDisplayFilters = function (filters, active) {
                                const next = normalizeDisplayFilters(filters);
                                const previous = active || getActiveDisplayFilters();
                                state.sort = next.sort;
                                state.density = next.density;

                                if (next.scope !== previous.scope && typeof window.omoSetDocumentsScope === 'function') {
                                    window.omoSetDocumentsScope(next.scope, {panel: panel});
                                    return;
                                }

                                const completeRender = function () {
                                    render();
                                    if (typeof window.omoSetDocumentsPanelLoadingState === 'function') {
                                        window.omoSetDocumentsPanelLoadingState(panel, false);
                                    }
                                };
                                if (typeof window.omoSetDocumentsPanelLoadingState === 'function') {
                                    window.omoSetDocumentsPanelLoadingState(panel, true);
                                    if (typeof window.requestAnimationFrame === 'function') {
                                        window.requestAnimationFrame(completeRender);
                                    } else {
                                        window.setTimeout(completeRender, 0);
                                    }
                                    return;
                                }

                                render();
                            };

                            const closeFilterMoreMenu = function () {
                                panel.querySelectorAll('[data-omo-documents-filter-more-menu]').forEach(function (menu) {
                                    const morePanel = menu.querySelector('[data-omo-documents-filter-more-panel]');
                                    const moreToggle = menu.querySelector('[data-omo-documents-filter-more-toggle]');
                                    if (morePanel) {
                                        morePanel.hidden = true;
                                    }
                                    menu.classList.remove('is-open');
                                    if (moreToggle) {
                                        moreToggle.setAttribute('aria-expanded', 'false');
                                    }
                                });
                            };

                            const handleFilterOutsidePointerDown = function (event) {
                                const control = panel.querySelector('[data-omo-documents-filter-control]');
                                if (control && control.contains(event.target)) {
                                    return;
                                }
                                closeFilterPanel(true, false);
                            };

                            const openFilterPanel = function () {
                                const filterPanel = panel.querySelector('[data-omo-documents-filter-panel]');
                                if (!filterPanel || filterPanelOpen) {
                                    return;
                                }
                                pendingDisplayFilters = getActiveDisplayFilters();
                                closeFilterMoreMenu();
                                syncFilterChoices();
                                filterPanel.hidden = false;
                                filterPanelOpen = true;
                                panel.querySelectorAll('[data-omo-documents-filter-toggle]').forEach(function (button) {
                                    button.setAttribute('aria-expanded', 'true');
                                });
                                document.addEventListener('pointerdown', handleFilterOutsidePointerDown, true);
                            };

                            const closeFilterPanel = function (applyChanges, saveView) {
                                const filterPanel = panel.querySelector('[data-omo-documents-filter-panel]');
                                if (!filterPanelOpen) {
                                    return;
                                }
                                filterPanelOpen = false;
                                if (filterPanel) {
                                    filterPanel.hidden = true;
                                }
                                panel.querySelectorAll('[data-omo-documents-filter-toggle]').forEach(function (button) {
                                    button.setAttribute('aria-expanded', 'false');
                                });
                                document.removeEventListener('pointerdown', handleFilterOutsidePointerDown, true);
                                closeFilterMoreMenu();

                                if (!applyChanges || !pendingDisplayFilters) {
                                    pendingDisplayFilters = null;
                                    return;
                                }

                                const active = getActiveDisplayFilters();
                                const next = normalizeDisplayFilters(pendingDisplayFilters);
                                pendingDisplayFilters = null;
                                omoDocumentsWritePreferences(panel, next, saveView);
                                applyDisplayFilters(next, active);
                            };

                            const applyFilterMoreAction = function (action) {
                                if (!filterPanelOpen || !pendingDisplayFilters) {
                                    return;
                                }

                                const active = getActiveDisplayFilters();
                                const next = normalizeDisplayFilters(pendingDisplayFilters);
                                closeFilterPanel(false, false);

                                if (action === 'set-default') {
                                    omoDocumentsClearStoredPreferences(panel);
                                    omoDocumentsClearTemporaryPreferences(panel);
                                    omoDocumentsStoreDefaultPreferences(next);
                                    applyDisplayFilters(next, active);
                                    return;
                                }

                                if (action === 'apply-everywhere') {
                                    const store = omoDocumentsGetSavedViewsStore();
                                    store.defaultView = omoDocumentsCreatePreferences(next);
                                    store.contexts = {};
                                    omoDocumentsSaveViewsStore(store);
                                    omoDocumentsClearAllTemporaryPreferences();
                                    applyDisplayFilters(next, active);
                                    return;
                                }

                                if (action === 'restore-default') {
                                    omoDocumentsClearStoredPreferences(panel);
                                    omoDocumentsClearTemporaryPreferences(panel);
                                    const store = omoDocumentsGetSavedViewsStore();
                                    store.defaultView = null;
                                    omoDocumentsSaveViewsStore(store);
                                    const serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
                                        ? window.omoApplicationViewPreferencesGetDefault(root)
                                        : null;
                                    applyDisplayFilters(serverDefault || {
                                        scope: 'contextual',
                                        sort: 'updated',
                                        density: 'detail'
                                    }, active);
                                }
                            };

                            panel.querySelectorAll('[data-omo-documents-filter-toggle]').forEach(function (button) {
                                button.addEventListener('click', function () {
                                    if (filterPanelOpen) {
                                        closeFilterPanel(true, false);
                                    } else {
                                        openFilterPanel();
                                    }
                                });
                            });

                            const filterPanel = panel.querySelector('[data-omo-documents-filter-panel]');
                            if (filterPanel) {
                                filterPanel.addEventListener('click', function (event) {
                                    const moreToggle = event.target.closest('[data-omo-documents-filter-more-toggle]');
                                    if (moreToggle) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        const moreMenu = moreToggle.closest('[data-omo-documents-filter-more-menu]');
                                        const morePanel = moreMenu ? moreMenu.querySelector('[data-omo-documents-filter-more-panel]') : null;
                                        const isMoreMenuOpen = !!morePanel && !morePanel.hidden;
                                        closeFilterMoreMenu();
                                        if (!isMoreMenuOpen && morePanel) {
                                            morePanel.hidden = false;
                                            moreMenu.classList.add('is-open');
                                            moreToggle.setAttribute('aria-expanded', 'true');
                                        }
                                        return;
                                    }
                                    const moreAction = event.target.closest('[data-omo-documents-filter-more-action]');
                                    if (moreAction) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        applyFilterMoreAction(moreAction.getAttribute('data-omo-documents-filter-more-action') || '');
                                        return;
                                    }
                                    const applyButton = event.target.closest('[data-omo-documents-filter-apply]');
                                    if (applyButton) {
                                        event.preventDefault();
                                        closeFilterPanel(true, false);
                                        return;
                                    }
                                    const saveButton = event.target.closest('[data-omo-documents-filter-save]');
                                    if (saveButton) {
                                        event.preventDefault();
                                        closeFilterPanel(true, true);
                                        return;
                                    }
                                    const scopeButton = event.target.closest('[data-omo-document-scope-toggle]');
                                    if (scopeButton && pendingDisplayFilters) {
                                        pendingDisplayFilters.scope = omoDocumentsNormalizeScope(
                                            scopeButton.getAttribute('data-omo-document-scope-toggle')
                                        );
                                        syncFilterChoices();
                                        return;
                                    }
                                    const sortButton = event.target.closest('[data-omo-documents-sort]');
                                    if (sortButton && pendingDisplayFilters) {
                                        pendingDisplayFilters.sort = omoDocumentsNormalizeSortPreference(
                                            sortButton.getAttribute('data-omo-documents-sort')
                                        );
                                        syncFilterChoices();
                                        return;
                                    }
                                    const densityButton = event.target.closest('[data-omo-documents-density]');
                                    if (densityButton && pendingDisplayFilters) {
                                        pendingDisplayFilters.density = omoDocumentsNormalizeDensityPreference(
                                            densityButton.getAttribute('data-omo-documents-density')
                                        );
                                        syncFilterChoices();
                                    }
                                });
                            }

                            const quickSearch = panel.querySelector('[data-omo-documents-quick-search]');
                            if (quickSearch) {
                                quickSearch.value = state.query;
                                const applySearch = function () {
                                    state.query = quickSearch.value || '';
                                    omoDocumentsWriteSearch(panel, state.query);
                                    render();
                                };
                                quickSearch.addEventListener('input', applySearch);
                                quickSearch.addEventListener('search', applySearch);
                            }

                            panel.addEventListener('keydown', function (event) {
                                if (event.key === 'Escape' && filterPanelOpen) {
                                    closeFilterPanel(false, false);
                                }
                            });

                            panel.addEventListener('change', function (event) {
                                const selectionInput = event.target.closest('[data-omo-document-select]');
                                if (!selectionInput || !panel.contains(selectionInput)) {
                                    return;
                                }

                                event.stopPropagation();
                                const documentId = Number(selectionInput.value || 0);
                                if (!Number.isInteger(documentId) || documentId <= 0) {
                                    return;
                                }

                                if (selectionInput.checked) {
                                    state.selectedDocumentIds.add(documentId);
                                } else {
                                    state.selectedDocumentIds.delete(documentId);
                                }
                                syncDocumentSelection();
                            });

                            panel.addEventListener('click', function (event) {
                                const bulkActionButton = event.target.closest('[data-omo-documents-bulk-action]');
                                if (bulkActionButton && panel.contains(bulkActionButton)) {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    const selectedDocumentIds = getSelectedDocumentIds();
                                    const selectedCount = selectedDocumentIds.length;
                                    const action = String(bulkActionButton.getAttribute('data-omo-documents-bulk-action') || '').trim();
                                    const isDelete = action === 'delete';
                                    const isMove = action === 'move';
                                    const isMerge = action === 'merge';

                                    if (selectedCount <= 0 || (!isDelete && !isMove && !isMerge && action !== 'archive')) {
                                        return;
                                    }
                                    if (isMerge) {
                                        if (selectedDocumentsCanBeMerged(selectedDocumentIds) && typeof window.omoOpenDocumentMergePopup === 'function') {
                                            window.omoOpenDocumentMergePopup(selectedDocumentIds);
                                        }
                                        return;
                                    }
                                    if (isMove) {
                                        if (!selectedDocumentsCanBeMoved(selectedDocumentIds)) {
                                            return;
                                        }
                                        window.omoOpenDocumentMovePopup(null, selectedDocumentIds);
                                        return;
                                    }
                                    if (isDelete && !selectedDocumentsCanBeDeleted(selectedDocumentIds)) {
                                        return;
                                    }

                                    const confirmation = isDelete
                                        ? pageConfig.documentsSelectionConfirmDelete
                                        : pageConfig.documentsSelectionConfirmArchive;
                                    if (!window.confirm(formatSelectionText(confirmation, {count: selectedCount}))) {
                                        return;
                                    }

                                    bulkActionButton.disabled = true;
                                    postBulkDocumentAction(isDelete ? 'delete' : 'archive').then(function () {
                                        state.selectedDocumentIds.clear();
                                        if (typeof window.omoRefreshDocumentsPanel === 'function') {
                                            return window.omoRefreshDocumentsPanel();
                                        }
                                        window.location.reload();
                                        return null;
                                    }).catch(function (error) {
                                        window.omoNotify(String(error && error.message || pageConfig.documentsMenuActionError), 'error');
                                    }).finally(function () {
                                        bulkActionButton.disabled = false;
                                    });
                                    return;
                                }

                                const selectionInput = event.target.closest('[data-omo-document-select]');
                                if (selectionInput && panel.contains(selectionInput)) {
                                    event.stopPropagation();
                                    return;
                                }

                                const contextJump = event.target.closest('[data-omo-document-context-jump]');
                                if (contextJump) {
                                    event.preventDefault();
                                    event.stopPropagation();

                                    const targetOrganizationId = Number(contextJump.getAttribute('data-omo-document-context-jump-oid') || panel.getAttribute('data-omo-document-oid') || 0);
                                    const targetHolonId = Number(contextJump.getAttribute('data-omo-document-context-jump-cid') || 0);
                                    if (!Number.isInteger(targetOrganizationId) || targetOrganizationId <= 0) {
                                        return;
                                    }

                                    const hashState = typeof window.omoParsePopupHashState === 'function'
                                        ? window.omoParsePopupHashState()
                                        : { popupToken: null };
                                    const nextHash = typeof window.omoBuildHashFromState === 'function'
                                        ? window.omoBuildHashFromState('documents', hashState && hashState.popupToken ? String(hashState.popupToken) : null)
                                        : 'documents';

                                    if (typeof window.omoNavigate === 'function') {
                                        window.omoNavigate(
                                            targetOrganizationId,
                                            Number.isInteger(targetHolonId) && targetHolonId > 0 ? targetHolonId : null,
                                            nextHash
                                        );
                                        return;
                                    }

                                    window.location.href = '/omo/' + (Number.isInteger(targetHolonId) && targetHolonId > 0 ? ('c/' + encodeURIComponent(String(targetHolonId))) : '') + (nextHash ? ('#' + nextHash) : '');
                                    return;
                                }

                                const closeTrigger = event.target.closest('[data-omo-document-detail-close]');

                                if (closeTrigger) {
                                    event.preventDefault();
                                    closeDetailDrawer();
                                    return;
                                }

                                const folderToggle = event.target.closest('[data-omo-document-folder-toggle]');
                                if (folderToggle && panel.contains(folderToggle)) {
                                    window.setTimeout(function () {
                                        const accordion = folderToggle.closest('[data-generic-accordion]');
                                        if (accordion instanceof HTMLElement && !accordion.classList.contains('is-collapsed')) {
                                            if (accordion.hasAttribute('data-omo-nextcloud-folder-id')) {
                                                loadNextcloudFolder(accordion);
                                            } else {
                                                loadDocumentFolder(accordion);
                                            }
                                        }
                                        syncFolderAccordionState(true);
                                    }, 0);
                                    return;
                                }

								const remoteFileTrigger = event.target.closest('[data-omo-nextcloud-file]');
								if (remoteFileTrigger && panel.contains(remoteFileTrigger)) {
									event.preventDefault();
									openDocumentDetail({
										isRemoteFile: true,
										remoteKey: String(remoteFileTrigger.getAttribute('data-omo-document-context-url') || ''),
										contextUrl: String(remoteFileTrigger.getAttribute('data-omo-document-context-url') || ''),
										title: String(remoteFileTrigger.getAttribute('data-omo-document-title') || ''),
										fullDateLabel: String(remoteFileTrigger.getAttribute('data-omo-document-full-date') || ''),
										documentType: 'uploaded_file',
										isFolder: false,
										canOpenInPvApplicationTab: true
									});
									return;
								}

                                const trigger = event.target.closest('[data-omo-document-id]');

                                if (!trigger || !panel.contains(trigger)) {
                                    return;
                                }

                                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                                    return;
                                }

                                if (typeof window.omoOpenDocumentDetailFromTrigger === 'function') {
                                    window.omoOpenDocumentDetailFromTrigger(trigger, event);
                                    return;
                                }

                                event.preventDefault();

                                const documentId = Number(trigger.getAttribute('data-omo-document-id'));

                                if (!documentId || Number.isNaN(documentId)) {
                                    return;
                                }

                                const documentItem = documents.find(function (item) {
                                    return Number(item.id) === documentId;
                                });

                                if (!documentItem) {
                                    return;
                                }

                                openDocumentDetail(documentItem);
                            });

                            panel.addEventListener('keydown', function (event) {
                                const folderToggle = event.target.closest('[data-omo-document-folder-toggle]');
                                if (
                                    folderToggle
                                    && panel.contains(folderToggle)
                                    && (event.key === 'Enter' || event.key === ' ')
                                ) {
                                    event.preventDefault();
                                    folderToggle.click();
                                    return;
                                }

                                const card = event.target.closest('[data-omo-document-id]');
                                if (
                                    card
                                    && panel.contains(card)
                                    && (event.key === 'Enter' || event.key === ' ')
                                    && !event.target.closest('[data-omo-document-context-jump]')
                                    && !event.target.closest('input, button, a')
                                ) {
                                    event.preventDefault();
									if (card.hasAttribute('data-omo-nextcloud-file')) {
										card.click();
										return;
									}
                                    if (typeof window.omoOpenDocumentDetailFromTrigger === 'function') {
                                        window.omoOpenDocumentDetailFromTrigger(card);
                                        return;
                                    }

                                    const documentItem = findDocumentItemById(Number(card.getAttribute('data-omo-document-id') || 0));
                                    if (documentItem) {
                                        openDocumentDetail(documentItem);
                                    }
                                    return;
                                }

                                if (event.key === 'Escape' && detailDrawer && detailDrawer.classList.contains('is-open')) {
                                    closeDetailDrawer();
                                }
                            });

                            render();
                            window.setTimeout(function () {
                                retryInitialDocumentOpen(0);
                            }, 0);

                            if (!panel.__omoDocumentsApplyRouteChange) {
                                panel.__omoDocumentsApplyRouteChange = function (detail) {
                                    if (!document.body.contains(panel)) {
                                        return false;
                                    }

                                    const routeDetail = detail && typeof detail === 'object' ? detail : {};
                                    const targetDocumentId = Number(routeDetail.documentId || 0);
                                    const targetMode = normalizeDocumentOpenMode(routeDetail.mode || 'detail');
                                    const previousDocumentId = Number(routeDetail.previousDocumentId || 0);
                                    const previousMode = normalizeDocumentOpenMode(routeDetail.previousMode || 'detail');
                                    const rawForcedScope = String(routeDetail.forcedScope || '').trim().toLowerCase();
                                    const forcedScope = rawForcedScope !== ''
                                        ? normalizeDocumentScope(rawForcedScope)
                                        : '';
                                    const fallbackDocumentScope = forcedScope !== ''
                                        ? forcedScope
                                        : 'descendants';

                                    if (targetDocumentId > 0) {
                                        // A document linked to a project is normally absent from the
                                        // contextual document list. The route response nevertheless
                                        // carries its payload, which is enough to open its detail drawer.
                                        const documentItem = findDocumentItemById(targetDocumentId)
                                            || (
                                                requestedDocument
                                                && Number(requestedDocument.id || 0) === targetDocumentId
                                                ? requestedDocument
                                                : null
                                            );
                                        const shouldPreferPanelRefresh = targetMode !== 'edit'
                                            && !documentItem;

                                        if (targetMode === 'edit') {
                                            if (
                                                !isPvApplicationTab
                                                && (
                                                    typeof window.omoPreserveDocumentPvPreparationDrawer !== 'function'
                                                    || !window.omoPreserveDocumentPvPreparationDrawer()
                                                )
                                            ) {
                                                if (typeof window.omoCloseDocumentPvPreparationDrawer === 'function') {
                                                    window.omoCloseDocumentPvPreparationDrawer({ force: true });
                                                }
                                            }
                                            if (typeof window.omoOpenDocumentEditorFromDocumentId === 'function' && window.omoOpenDocumentEditorFromDocumentId(targetDocumentId)) {
                                                return true;
                                            }
                                            if (refreshPanelForDocumentRoute(targetDocumentId, targetMode, fallbackDocumentScope)) {
                                                return true;
                                            }
                                        }

                                        if (shouldPreferPanelRefresh && refreshPanelForDocumentRoute(targetDocumentId, targetMode, fallbackDocumentScope)) {
                                            return true;
                                        }

                                        if (targetMode !== 'edit' && documentItem && typeof window.omoOpenDocumentDetailByPayload === 'function') {
                                            window.omoCloseDocumentEditorDrawer({ force: true, preserveDrawer: true });
                                            if (window.omoOpenDocumentDetailByPayload(documentItem, panel) === true) {
                                                if (documentItem === requestedDocument) {
                                                    payload.openDocumentId = 0;
                                                    payload.openDocumentMode = 'detail';
                                                    payload.requestedDocument = null;
                                                }
                                                return true;
                                            }
                                        }

                                        if (targetMode !== 'edit') {
                                            window.omoCloseDocumentEditorDrawer({ force: true, preserveDrawer: true });
                                            if (refreshPanelForDocumentRoute(targetDocumentId, targetMode, fallbackDocumentScope)) {
                                                return true;
                                            }

                                            if (typeof window.omoOpenDocumentDetailByPayload === 'function') {
                                                const directDocumentPayload = buildDirectDocumentPayload(targetDocumentId);
                                                if (
                                                    directDocumentPayload
                                                    && window.omoOpenDocumentDetailByPayload(directDocumentPayload, panel) === true
                                                ) {
                                                    return true;
                                                }
                                            }

                                            return false;
                                        }
                                        return false;
                                    }

                                    if (forcedScope !== '' && typeof window.omoSetDocumentsScope === 'function') {
                                        window.omoSetDocumentsScope(forcedScope, {
                                            panel: panel
                                        });
                                    }

                                    window.omoCloseDocumentEditorDrawer({ force: true });
                                    if (
                                        !isPvApplicationTab
                                        && (
                                            typeof window.omoPreserveDocumentPvPreparationDrawer !== 'function'
                                            || !window.omoPreserveDocumentPvPreparationDrawer()
                                        )
                                    ) {
                                        if (typeof window.omoCloseDocumentPvPreparationDrawer === 'function') {
                                            window.omoCloseDocumentPvPreparationDrawer({ force: true });
                                        }
                                    }
                                    closeDetailDrawer();
                                    return true;
                                };
                            }

                            window.omoHandleDocumentsRouteChange = function (detail) {
                                const activePanel = window.omoDocumentsFindRoot();
                                if (!(activePanel instanceof Element) || !document.body.contains(activePanel)) {
                                    return false;
                                }

                                if (typeof activePanel.__omoDocumentsApplyRouteChange !== 'function') {
                                    return false;
                                }

                                return activePanel.__omoDocumentsApplyRouteChange(detail) === true;
                            };

                            if (!panel.__omoDocumentsRouteHandler) {
                                panel.__omoDocumentsRouteHandler = function (routeEvent) {
                                    const detail = routeEvent && routeEvent.detail ? routeEvent.detail : {};
                                    if (typeof panel.__omoDocumentsApplyRouteChange === 'function') {
                                        panel.__omoDocumentsApplyRouteChange(detail);
                                    }
                                };

                                window.addEventListener('omo-documents-route-change', panel.__omoDocumentsRouteHandler);
                            }

                        });
                    };

                window.omoInitDocumentsPanels();
            })();
};
