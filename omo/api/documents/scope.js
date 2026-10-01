        (function () {
            const normalizeDocumentScope = function (scopeValue) {
                const normalizedScope = String(scopeValue || '').trim().toLowerCase();
                if (normalizedScope === 'global') {
                    return 'descendants';
                }
                return normalizedScope === 'children' || normalizedScope === 'descendants' ? normalizedScope : 'contextual';
            };

            const resolveDocumentsScopePanel = function (panelCandidate) {
                if (panelCandidate instanceof Element) {
                    return panelCandidate.closest('.omo-documents') || panelCandidate;
                }

                return window.omoDocumentsFindRoot();
            };

            const getCurrentDocumentsScope = function (panel) {
                return normalizeDocumentScope(panel && panel.getAttribute('data-omo-document-scope') || 'contextual');
            };

            window.omoSetDocumentsPanelLoadingState = function (panel, isLoading, options = {}) {
                if (!(panel instanceof Element)) {
                    return;
                }

                const loading = Boolean(isLoading);
                const showResultsPlaceholder = options && options.showResultsPlaceholder === true;
                panel.classList.toggle('is-loading', loading);
                const indicator = panel.querySelector('[data-omo-documents-loading-indicator]');
                if (indicator) {
                    indicator.hidden = !loading;
                }

                if (showResultsPlaceholder && typeof window.omoSetPanelResultsLoadingSkeleton === 'function') {
                    window.omoSetPanelResultsLoadingSkeleton(panel, loading, {
                        contentSelector: '[data-omo-documents-results]'
                    });
                }

                if (loading) {
                    panel.setAttribute('aria-busy', 'true');
                } else {
                    panel.removeAttribute('aria-busy');
                }

                panel.querySelectorAll('[data-omo-documents-filter-toggle], [data-omo-documents-filter-apply], [data-omo-documents-filter-save], [data-omo-document-scope-toggle], [data-omo-documents-sort], [data-omo-documents-density]').forEach(function (button) {
                    button.disabled = loading;
                });
            };

            const buildDocumentsScopeUrl = function (panel, scopeValue) {
                const resolvedScope = normalizeDocumentScope(scopeValue);
                const organizationId = Number(panel && panel.getAttribute('data-omo-document-oid') || 0);
                const holonId = Number(panel && panel.getAttribute('data-omo-document-cid') || 0);
                const query = [];

                if (organizationId > 0) {
                    query.push('oid=' + encodeURIComponent(String(organizationId)));
                }

                if (holonId > 0) {
                    query.push('cid=' + encodeURIComponent(String(holonId)));
                }

                query.push('document_scope=' + encodeURIComponent(resolvedScope));

                return '/omo/api/documents/index.php' + (query.length > 0 ? '?' + query.join('&') : '');
            };

            const setDocumentsScopeLoadingState = function (panel, isLoading, targetScope) {
                if (!(panel instanceof Element)) {
                    return;
                }

                window.omoSetDocumentsPanelLoadingState(panel, isLoading, {
                    showResultsPlaceholder: true
                });
                let activeScopeIndex = 0;

                panel.querySelectorAll('[data-omo-document-scope-toggle]').forEach(function (button) {
                    const buttonScope = String(button.getAttribute('data-omo-document-scope-toggle') || '').trim().toLowerCase();
                    const isActive = buttonScope === targetScope;

                    button.disabled = Boolean(isLoading);
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    if (isActive) {
                        activeScopeIndex = parseInt(button.getAttribute('data-omo-scope-index') || '0', 10) || 0;
                    }
                });

                const toggle = panel.querySelector('[data-omo-scope-switch]');
                if (toggle) {
                    toggle.setAttribute('data-omo-scope-switch', targetScope);
                    toggle.style.setProperty('--omo-scope-active-index', String(activeScopeIndex));
                }
            };

            window.omoSetDocumentsScope = function (scopeValue, options = {}) {
                const panel = resolveDocumentsScopePanel(options.panel);
                if (!(panel instanceof Element)) {
                    return false;
                }

                const targetScope = normalizeDocumentScope(scopeValue);
                if (targetScope === getCurrentDocumentsScope(panel)) {
                    setDocumentsScopeLoadingState(panel, false, targetScope);
                    return true;
                }

                if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
                    window.location.href = buildDocumentsScopeUrl(panel, targetScope);
                    return true;
                }

                const requestId = (parseInt(panel.dataset.omoDocumentsScopeRequestId || '0', 10) || 0) + 1;
                panel.dataset.omoDocumentsScopeRequestId = String(requestId);

                const triggerRefresh = function () {
                    window.omoReplaceFetchedPanelRoot({
                        rootSelector: '#omo-documents-root',
                        currentRoot: panel,
                        url: buildDocumentsScopeUrl(panel, targetScope),
                        setLoadingState: function (isLoading) {
                            if ((panel.dataset.omoDocumentsScopeRequestId || '') !== String(requestId) && !isLoading) {
                                return;
                            }

                            setDocumentsScopeLoadingState(panel, isLoading, targetScope);
                        }
                    }).catch(function () {
                        if ((panel.dataset.omoDocumentsScopeRequestId || '') !== String(requestId)) {
                            return;
                        }

                        setDocumentsScopeLoadingState(panel, false, getCurrentDocumentsScope(panel));
                        panel.removeAttribute('data-omo-view-filter-pending');
                        panel.removeAttribute('aria-busy');
                    });
                };

                setDocumentsScopeLoadingState(panel, true, targetScope);

                if (typeof window.requestAnimationFrame === 'function') {
                    window.requestAnimationFrame(triggerRefresh);
                } else {
                    triggerRefresh();
                }

                return true;
            };

            window.omoInitDocumentsScopePanels = function (root) {
                    const scope = root instanceof Element ? root : document;

                    scope.querySelectorAll('.omo-documents').forEach(function (panel) {
                        if (panel.dataset.omoDocumentsScopeReady === '1') {
                            return;
                        }

                        panel.addEventListener('click', function (event) {
                            const button = event.target.closest('[data-omo-document-scope-toggle]');
                            if (!button || !panel.contains(button)) {
                                return;
                            }
                            if (button.closest('[data-omo-documents-filter-panel]')) {
                                return;
                            }

                            const targetScope = normalizeDocumentScope(button.getAttribute('data-omo-document-scope-toggle') || '');

                            if (targetScope === getCurrentDocumentsScope(panel)) {
                                return;
                            }

                            window.omoSetDocumentsScope(targetScope, {
                                panel: panel
                            });
                        });

                        panel.dataset.omoDocumentsScopeReady = '1';
                    });
                };

            window.omoInitDocumentsScopePanels();
            const activeDocumentsPanel = window.omoDocumentsFindRoot();
            if (activeDocumentsPanel) {
                const initialPreferences = typeof window.omoDocumentsReadViewPreferences === 'function'
                    ? window.omoDocumentsReadViewPreferences(activeDocumentsPanel)
                    : null;
                const hasInitialDocument = Number(activeDocumentsPanel.getAttribute('data-omo-document-open-id') || 0) > 0;
                let initialScope = normalizeDocumentScope(
                    hasInitialDocument
                        ? activeDocumentsPanel.getAttribute('data-omo-document-scope')
                        : initialPreferences && initialPreferences.scope
                            ? initialPreferences.scope
                            : activeDocumentsPanel.getAttribute('data-omo-document-scope')
                );
                if (!activeDocumentsPanel.querySelector('[data-omo-document-scope-toggle="' + initialScope + '"]')) {
                    initialScope = getCurrentDocumentsScope(activeDocumentsPanel);
                }
                if (initialScope !== getCurrentDocumentsScope(activeDocumentsPanel)) {
                    window.omoSetDocumentsScope(initialScope, {panel: activeDocumentsPanel});
                } else {
                    activeDocumentsPanel.removeAttribute('data-omo-view-filter-pending');
                    activeDocumentsPanel.removeAttribute('aria-busy');
                }
            }
        })();
