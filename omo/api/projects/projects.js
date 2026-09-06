(function (window, document) {
    'use strict';

    var root = typeof window.omoFindApplicationRoot === 'function'
        ? window.omoFindApplicationRoot('omo-projects-root')
        : document.getElementById('omo-projects-root');
    if (!root || root.dataset.omoProjectsReady === '1') {
        return;
    }
    root.dataset.omoProjectsReady = '1';

    var useLocalDrawerNavigation = typeof window.omoIsPvApplicationTabContext === 'function'
        && window.omoIsPvApplicationTabContext(root);

    if (!window.omoProjectArchiveLinksBound) {
        document.addEventListener('click', function (event) {
            var archiveLink = event.target && event.target.closest
                ? event.target.closest('[data-omo-project-archive-link]')
                : null;
            var projectId;
            if (!archiveLink) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            projectId = Number(archiveLink.getAttribute('data-project-id') || 0);
            if (!Number.isInteger(projectId) || projectId <= 0) {
                return;
            }
            if (
                typeof window.omoIsPvApplicationTabContext === 'function'
                && window.omoIsPvApplicationTabContext(archiveLink.closest('#omo-projects-root'))
                && typeof window.omoOpenProjectRoute === 'function'
            ) {
                window.omoOpenProjectRoute('projects-d' + String(projectId));
                return;
            }
            if (typeof window.omoOpenDrawerHashState === 'function') {
                window.omoOpenDrawerHashState('projects-d' + String(projectId));
            } else {
                window.location.hash = 'projects-d' + String(projectId);
            }
        });
        window.omoProjectArchiveLinksBound = true;
    }

    var drawer = root.querySelector('[data-omo-projects-drawer]');
    var drawerBody = root.querySelector('[data-omo-projects-drawer-body]');
    var drawerController = drawer && typeof window.omoCreateSubdrawerController === 'function'
        ? window.omoCreateSubdrawerController({ drawer: drawer })
        : null;
    var documentDrawer = root.querySelector('[data-omo-projects-document-drawer]');
    var documentDrawerBody = root.querySelector('[data-omo-projects-document-drawer-body]');
    var documentDrawerController = documentDrawer && typeof window.omoCreateSubdrawerController === 'function'
        ? window.omoCreateSubdrawerController({ drawer: documentDrawer })
        : null;
    var currentUrl = root.getAttribute('data-omo-projects-current-url') || '';
    var createUrl = root.getAttribute('data-omo-projects-create-url') || '';
    var detailUrl = root.getAttribute('data-omo-projects-detail-url') || '';
    var actionUrl = root.getAttribute('data-omo-projects-action-url') || '';
    var routeCid = Number(root.getAttribute('data-omo-projects-cid') || 0);
    var declaredView = root.getAttribute('data-omo-projects-view');
    var currentView = declaredView === 'list' || declaredView === 'gantt' ? declaredView : 'kanban';
    var currentAssignment = root.getAttribute('data-omo-projects-assignment') === 'mine' ? 'mine' : 'all';
    var currentQuickSearch = root.getAttribute('data-omo-projects-query') || '';
    var currentListSort = ['planned', 'priority', 'importance', 'holon'].indexOf(root.getAttribute('data-omo-projects-list-sort')) !== -1
        ? root.getAttribute('data-omo-projects-list-sort')
        : 'importance';
    var displayPreferencesStorageKey = 'omo.projects.saved-views.v2';
    var legacyDisplayPreferencesStorageKey = 'omo.projects.saved-views.v1';
    var temporaryDisplayPreferencesStorageKey = 'omo.projects.session-views.v1';
    var columns = [];
    var texts = {
        loading: 'Chargement du projet…',
        loadingError: 'Impossible de charger ce projet.',
        emptyColumn: 'Aucun projet dans cette colonne.',
        statusUpdateError: 'Impossible de changer le statut.',
        actionError: 'Impossible de mettre à jour le projet.',
        deleteConfirm: 'Supprimer définitivement ce projet et ses {count} sous-projets ? Cette action est irréversible.',
        archiveConfirm: "Ce projet n'est pas terminé. L'archiver quand même ?",
        moveTitle: 'Déplacer le projet',
        moveHint: 'Choisissez le holon de destination dans la structure.',
        moveSubmit: 'Déplacer ici',
        moveSelectRequired: 'Choisissez un holon de destination.',
        attachTitle: 'Attacher un projet',
        attachHint: 'Choisissez un projet sans parent dans la structure.',
        attachSearch: 'Rechercher un projet',
        attachEmpty: 'Aucun projet sans parent ne correspond à la recherche.',
        attachSubmit: 'Attacher',
        attachSelectRequired: 'Choisissez un projet à attacher.',
        cancel: 'Annuler',
        documentsPickerTitle: 'Ajouter un document',
        documentsPickerTabs: 'Choix du document',
        documentsPickerExisting: 'Document existant',
        documentsPickerNew: 'Nouveau document',
        documentsPickerSearch: 'Rechercher un document',
        documentsPickerVisible: 'Documents visibles',
        documentsPickerNone: 'Aucun document ne correspond à votre recherche.',
        documentsPickerAttach: 'Associer au projet',
        documentsPickerSelectRequired: 'Choisissez un document à associer.',
        documentsPickerError: 'Impossible de charger les documents disponibles.',
        documentsPickerAttachError: "Impossible d'associer ce document au projet.",
        documentsPickerCreateError: 'Impossible de créer ce document.'
    };
    var mobileColumnIndex = 0;
    var requestToken = 0;
    var currentDrawerUrl = '';
    var documentDrawerRequestToken = 0;
    var rootNeedsRefresh = false;
    var initialOpenProjectId = Number(root.getAttribute('data-omo-projects-open-project-id') || 0);
    var initialOpenProjectMode = root.getAttribute('data-omo-projects-open-project-mode') || '';
    var pendingCreateStatus = '';
    var pendingCreateUrl = '';
    var draggedProjectCard = null;
    var filterControl = root.querySelector('[data-omo-projects-filter-control]');
    var filterPanel = root.querySelector('[data-omo-projects-filter-panel]');
    var quickSearchInput = root.querySelector('[data-omo-projects-quick-search]');
    var quickSearchEmpty = root.querySelector('[data-omo-projects-search-empty]');
    var pendingDisplayFilters = null;
    var filterPanelIsOpen = false;
    var selectedProjectIds = {};

    try {
        columns = JSON.parse(root.getAttribute('data-omo-projects-columns') || '[]');
    } catch (error) {
        columns = [];
    }
    try {
        texts = Object.assign(texts, JSON.parse(root.getAttribute('data-omo-projects-texts') || '{}'));
    } catch (error) {
        // Keep the local fallback strings when the server payload is unavailable.
    }

    function resolveUrl(url) {
        return typeof window.omoResolveAppUrl === 'function' ? window.omoResolveAppUrl(url) : url;
    }

    function setLoading(isLoading) {
        root.classList.toggle('is-loading', Boolean(isLoading));
        if (typeof window.omoSetPanelResultsLoadingSkeleton === 'function') {
            window.omoSetPanelResultsLoadingSkeleton(root, isLoading, {
                contentSelector: '.omo-projects__body'
            });
        }
        root.querySelectorAll('button, select, input').forEach(function (element) {
            if (element.hasAttribute('data-omo-projects-drawer-close')) {
                return;
            }
            element.disabled = Boolean(isLoading);
        });
    }

    function loadProjectDocuments(tab) {
        if (!tab) {
            return;
        }
        var targetId = tab.getAttribute('data-generic-tab-target') || '';
        var panel = targetId ? document.getElementById(targetId) : null;
        var content = panel ? panel.querySelector('[data-omo-project-detail-documents-content]') : null;
        if (!panel || !content || panel.getAttribute('data-omo-project-detail-documents-loaded') === '1' || panel.getAttribute('data-omo-project-detail-documents-loading') === '1') {
            return;
        }

        var url = panel.getAttribute('data-omo-project-detail-documents-url') || '';
        if (!url) {
            return;
        }
        panel.setAttribute('data-omo-project-detail-documents-loading', '1');
        content.innerHTML = '<p class="omo-project-detail__muted generic-description generic-description--small">' + escapeHtml(texts.documentsLoading || 'Chargement...') + '</p>';
        fetch(resolveUrl(url), {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('documents');
                }
                return response.text();
            })
            .then(function (html) {
                content.innerHTML = html;
                panel.setAttribute('data-omo-project-detail-documents-loaded', '1');
                panel.removeAttribute('data-omo-project-detail-documents-loading');
            })
            .catch(function () {
                content.innerHTML = '<p class="omo-project-detail__muted generic-description generic-description--small">' + escapeHtml(texts.documentsError || 'Impossible de charger les documents.') + '</p>';
                panel.removeAttribute('data-omo-project-detail-documents-loading');
            });
    }

    function loadProjectEvents(tab) {
        if (!tab) {
            return;
        }
        var targetId = tab.getAttribute('data-generic-tab-target') || '';
        var panel = targetId ? document.getElementById(targetId) : null;
        var content = panel ? panel.querySelector('[data-omo-project-detail-events-content]') : null;
        if (!panel || !content || panel.getAttribute('data-omo-project-detail-events-loaded') === '1' || panel.getAttribute('data-omo-project-detail-events-loading') === '1') {
            return;
        }

        var url = panel.getAttribute('data-omo-project-detail-events-url') || '';
        if (!url) {
            return;
        }
        panel.setAttribute('data-omo-project-detail-events-loading', '1');
        content.innerHTML = '<p class="omo-project-detail__muted generic-description generic-description--small">' + escapeHtml(texts.eventsLoading || 'Chargement...') + '</p>';
        fetch(resolveUrl(url), {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('events');
                }
                return response.text();
            })
            .then(function (html) {
                content.innerHTML = html;
                panel.setAttribute('data-omo-project-detail-events-loaded', '1');
                panel.removeAttribute('data-omo-project-detail-events-loading');
            })
            .catch(function () {
                content.innerHTML = '<p class="omo-project-detail__muted generic-description generic-description--small">' + escapeHtml(texts.eventsError || 'Impossible de charger les événements.') + '</p>';
                panel.removeAttribute('data-omo-project-detail-events-loading');
            });
    }

    function revealRoot() {
        root.removeAttribute('data-omo-projects-preferences-pending');
        root.removeAttribute('aria-busy');
    }

    function captureScrollPosition(element) {
        return element ? {left: element.scrollLeft, top: element.scrollTop} : null;
    }

    function restoreScrollPosition(element, position) {
        if (!element || !position) {
            return;
        }
        element.scrollLeft = position.left || 0;
        element.scrollTop = position.top || 0;
    }

    function captureKanbanColumnScrollPositions() {
        var columns = {};
        root.querySelectorAll('[data-omo-projects-cards]').forEach(function (container) {
            var key = container.getAttribute('data-omo-projects-cards') || '';
            if (
                key === ''
                || (container.scrollHeight <= container.clientHeight && container.scrollWidth <= container.clientWidth)
            ) {
                return;
            }
            columns[key] = captureScrollPosition(container);
        });
        return columns;
    }

    function restoreKanbanColumnScrollPositions(nextRoot, positions) {
        if (!nextRoot || !positions) {
            return;
        }
        nextRoot.querySelectorAll('[data-omo-projects-cards]').forEach(function (container) {
            var key = container.getAttribute('data-omo-projects-cards') || '';
            if (key !== '' && positions[key]) {
                restoreScrollPosition(container, positions[key]);
            }
        });
    }

    function captureContentScrollPosition() {
        var body = root.querySelector('.omo-panel-view__body');
        if (currentView === 'list') {
            var list = root.querySelector('[data-omo-projects-list]');
            return {
                view: 'list',
                body: captureScrollPosition(body),
                content: captureScrollPosition(list)
            };
        }

        if (currentView === 'gantt') {
            var gantt = root.querySelector('[data-omo-projects-gantt-scroll]');
            return {
                view: 'gantt',
                body: captureScrollPosition(body),
                content: captureScrollPosition(gantt)
            };
        }

        var board = root.querySelector('[data-omo-projects-board]');
        return {
            view: 'kanban',
            body: captureScrollPosition(body),
            content: captureScrollPosition(board),
            columns: captureKanbanColumnScrollPositions()
        };
    }

    function restoreContentScrollPosition(nextRoot, position, afterRestore) {
        if (!nextRoot) {
            return;
        }

        if (!position) {
            if (typeof afterRestore === 'function') {
                afterRestore();
            }
            return;
        }

        window.requestAnimationFrame(function () {
            restoreScrollPosition(nextRoot.querySelector('.omo-panel-view__body'), position.body);

            if (position.view === 'list') {
                var list = nextRoot.querySelector('[data-omo-projects-list]');
                restoreScrollPosition(list, position.content);
                if (typeof afterRestore === 'function') {
                    afterRestore();
                }
                return;
            }

            if (position.view === 'gantt') {
                var gantt = nextRoot.querySelector('[data-omo-projects-gantt-scroll]');
                restoreScrollPosition(gantt, position.content);
                if (typeof afterRestore === 'function') {
                    afterRestore();
                }
                return;
            }

            var board = nextRoot.querySelector('[data-omo-projects-board]');
            restoreScrollPosition(board, position.content);
            restoreKanbanColumnScrollPositions(nextRoot, position.columns);
            if (typeof afterRestore === 'function') {
                afterRestore();
            }
        });
    }

    function revealKanbanProjectIfNeeded(nextRoot, projectId) {
        var resolvedProjectId = Number(projectId || 0);
        if (!nextRoot || !Number.isInteger(resolvedProjectId) || resolvedProjectId <= 0) {
            return;
        }

        window.requestAnimationFrame(function () {
            var board = nextRoot.querySelector('[data-omo-projects-board]');
            var card = nextRoot.querySelector('[data-omo-project-card][data-project-id="' + String(resolvedProjectId) + '"]');
            if (!board || !card) {
                return;
            }

            var boardRect = board.getBoundingClientRect();
            var header = board.querySelector('.omo-projects__kanban-grid-header');
            var headerRect = header ? header.getBoundingClientRect() : null;
            var visibleTop = Math.max(boardRect.top, headerRect ? headerRect.bottom : boardRect.top) + 8;
            var visibleBottom = boardRect.bottom - 12;
            var cardRect = card.getBoundingClientRect();
            var nextTop = board.scrollTop;

            if (cardRect.top < visibleTop) {
                nextTop += cardRect.top - visibleTop;
            } else if (cardRect.bottom > visibleBottom) {
                nextTop += cardRect.bottom - visibleBottom;
            }

            nextTop = Math.max(0, Math.min(nextTop, board.scrollHeight - board.clientHeight));
            if (nextTop !== board.scrollTop) {
                board.scrollTop = nextTop;
            }
        });
    }

    function scrollGanttToToday() {
        if (currentView !== 'gantt') {
            return;
        }
        var gantt = root.querySelector('[data-omo-projects-gantt-scroll]');
        var timeline = root.querySelector('.omo-project-gantt-row__timeline');
        var today = root.querySelector('.omo-projects__gantt-today');
        if (!gantt || !timeline || !today) {
            return;
        }

        window.requestAnimationFrame(function () {
            var todayPosition = timeline.offsetLeft + today.offsetLeft;
            var targetPosition = todayPosition - (gantt.clientWidth * .48);
            gantt.scrollLeft = Math.max(0, Math.min(targetPosition, gantt.scrollWidth - gantt.clientWidth));
        });
    }

    function scrollGanttBarIntoView(projectRow) {
        var gantt = root.querySelector('[data-omo-projects-gantt-scroll]');
        var bar = projectRow ? projectRow.querySelector('[data-omo-project-gantt-bar]') : null;
        var projectCell = projectRow ? projectRow.querySelector('[data-omo-project-gantt-project]') : null;
        if (!gantt || !bar || !projectCell) {
            return false;
        }

        var ganttRect = gantt.getBoundingClientRect();
        var projectRect = projectCell.getBoundingClientRect();
        var barRect = bar.getBoundingClientRect();
        var visibleLeft = Math.max(ganttRect.left, projectRect.right) + 8;
        var visibleRight = ganttRect.right - 12;
        if (barRect.right > visibleLeft && barRect.left < visibleRight) {
            return true;
        }

        var visibleWidth = Math.max(1, visibleRight - visibleLeft);
        var targetLeft;
        if (barRect.width < visibleWidth) {
            targetLeft = gantt.scrollLeft + barRect.left - visibleLeft - ((visibleWidth - barRect.width) / 2);
        } else {
            targetLeft = gantt.scrollLeft + barRect.left - visibleLeft;
        }
        targetLeft = Math.max(0, Math.min(targetLeft, gantt.scrollWidth - gantt.clientWidth));
        if (typeof gantt.scrollTo === 'function') {
            gantt.scrollTo({left: targetLeft, behavior: 'smooth'});
        } else {
            gantt.scrollLeft = targetLeft;
        }
        return true;
    }

    function waitForRootInitialization(nextRoot) {
        if (!nextRoot || nextRoot.dataset.omoProjectsReady === '1') {
            return Promise.resolve();
        }

        return new Promise(function (resolve) {
            var deadline = Date.now() + 1000;
            function checkReady() {
                if (nextRoot.dataset.omoProjectsReady === '1' || Date.now() >= deadline) {
                    resolve();
                    return;
                }
                window.requestAnimationFrame(checkReady);
            }
            checkReady();
        });
    }

    function refreshRoot(url, options) {
        var targetUrl = url || currentUrl;
        var preserveScroll = !(options && options.preserveScroll === false);
        var revealProjectId = Number(options && options.revealProjectId || 0);
        var scrollPosition = preserveScroll ? captureContentScrollPosition() : null;
        if (!targetUrl) {
            return Promise.resolve(null);
        }
        if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
            window.location.href = resolveUrl(targetUrl);
            return Promise.resolve(null);
        }
        return window.omoReplaceFetchedPanelRoot({
            rootSelector: '#omo-projects-root',
            currentRoot: root,
            url: resolveUrl(targetUrl),
            setLoadingState: setLoading,
            beforeReplace: function () {
                removeProjectFilterOutsideHandler();
                window.removeEventListener('omo-projects-route-change', handleProjectRouteChange);
                window.removeEventListener('omo-runtime-maintenance', handleRuntimeMaintenance);
            }
        }).then(function (nextRoot) {
            restoreContentScrollPosition(nextRoot, scrollPosition, function () {
                revealKanbanProjectIfNeeded(nextRoot, revealProjectId);
            });
            return nextRoot;
        });
    }

    function setDrawerMessage(message, isError) {
        if (!drawerBody) {
            return;
        }
        if (drawerController) {
            drawerController.resetHeader();
        }
        drawerBody.innerHTML = '<div class="generic-section omo-projects-feedback' + (isError ? ' is-error' : '') + '"></div>';
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
        refreshRoot(currentUrl, {preserveScroll: true});
    }

    function executeFetchedScripts(container) {
        var scripts = Array.prototype.slice.call(container.querySelectorAll('script'));
        return scripts.reduce(function (promise, script) {
            return promise.then(function () {
                if (script.src) {
                    return new Promise(function (resolve, reject) {
                        var loadedScript = document.createElement('script');
                        loadedScript.src = script.src;
                        loadedScript.onload = resolve;
                        loadedScript.onerror = reject;
                        document.head.appendChild(loadedScript);
                    });
                }

                try {
                    window.eval(script.textContent || '');
                    return Promise.resolve();
                } catch (error) {
                    return Promise.reject(error);
                }
            });
        }, Promise.resolve());
    }

    function openDrawerWithUrl(url) {
        if (!drawer || !drawerBody || !url) {
            return Promise.resolve(false);
        }

        currentDrawerUrl = url;
        setDrawerMessage(texts.loading, false);
        if (drawerController && typeof drawerController.open === 'function') {
            drawerController.open();
        } else {
            drawer.hidden = false;
            void drawer.offsetWidth;
            window.requestAnimationFrame(function () {
                drawer.classList.add('is-open');
            });
        }

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
            return executeFetchedScripts(drawerBody).then(function () {
                if (typeof window.initGenericComponents === 'function') {
                    window.initGenericComponents(drawerBody);
                }
                return true;
            });
        }).catch(function () {
            if (localToken === requestToken) {
                setDrawerMessage(texts.loadingError, true);
            }
            return false;
        });
    }

    function setDocumentDrawerMessage(message, isError) {
        if (!documentDrawerBody) {
            return;
        }
        if (documentDrawerController) {
            documentDrawerController.resetHeader();
        }
        documentDrawerBody.innerHTML = '<div class="generic-section omo-projects-feedback' + (isError ? ' is-error' : '') + '"></div>';
        documentDrawerBody.firstElementChild.textContent = String(message || '');
    }

    function openProjectDocumentDrawer(url) {
        if (!documentDrawer || !documentDrawerBody || !url) {
            return Promise.resolve(false);
        }

        setDocumentDrawerMessage(texts.loading, false);
        documentDrawer.classList.remove('is-calendar-event-editor');
        if (documentDrawerController && typeof documentDrawerController.open === 'function') {
            documentDrawerController.open();
        } else {
            documentDrawer.hidden = false;
            void documentDrawer.offsetWidth;
            window.requestAnimationFrame(function () {
                documentDrawer.classList.add('is-open');
            });
        }

        var localToken = ++documentDrawerRequestToken;
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
            if (localToken !== documentDrawerRequestToken) {
                return false;
            }
            documentDrawerBody.innerHTML = html;
            documentDrawer.classList.toggle('is-calendar-event-editor', Boolean(documentDrawerBody.querySelector('.omo-calendar-create')));
            if (documentDrawerController) {
                documentDrawerController.applyContentHeader(documentDrawerBody);
            }
            if (typeof window.initGenericComponents === 'function') {
                window.initGenericComponents(documentDrawerBody);
            }
            return executeFetchedScripts(documentDrawerBody).then(function () {
                if (typeof window.initGenericComponents === 'function') {
                    window.initGenericComponents(documentDrawerBody);
                }
                if (typeof window.omoInitCalendarEventEditor === 'function') {
                    window.omoInitCalendarEventEditor(documentDrawerBody, {
                        onSave: function (result) {
                            window.dispatchEvent(new CustomEvent('omo-project-event-saved', {
                                detail: {
                                    projectId: Number(result && result.projectId || 0),
                                    eventId: Number(result && result.eventId || 0)
                                }
                            }));
                            closeProjectDocumentDrawer();
                        }
                    });
                }
                return true;
            });
        }).catch(function () {
            if (localToken === documentDrawerRequestToken) {
                setDocumentDrawerMessage(texts.loadingError, true);
            }
            return false;
        });
    }

    function openProjectDocumentPicker(url) {
        if (!url || typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }

        window.commonTopbarOpenModal(
            texts.documentsPickerTitle || 'Ajouter un document',
            '<p class="omo-project-document-picker__loading generic-description generic-description--small">' + escapeHtml(texts.documentsLoading || 'Chargement...') + '</p>',
            'html'
        );

        fetch(resolveUrl(url), {
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            cache: 'no-store'
        }).then(function (response) {
            return response.text().then(function (body) {
                var data;
                try {
                    data = JSON.parse(body);
                } catch (error) {
                    data = null;
                }
                if (!response.ok || !data || !data.success) {
                    throw new Error(data && data.message ? data.message : texts.documentsPickerError);
                }
                return data;
            });
        }).then(function (data) {
            var modalBody = document.getElementById('commonTopbarModalBody');
            if (!(modalBody instanceof Element)) {
                return;
            }

            var existingTabId = 'omo-project-document-picker-existing-' + String(Date.now());
            var newTabId = 'omo-project-document-picker-new-' + String(Date.now() + 1);
            var html = '<div class="generic-tabs omo-document-embed-picker omo-project-document-picker" data-generic-tabs>'
                + '<div class="generic-tabs__list" aria-label="' + escapeHtml(texts.documentsPickerTabs || 'Choix du document') + '">'
                + '<button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="' + existingTabId + '">' + escapeHtml(texts.documentsPickerExisting || 'Document existant') + '</button>'
                + '<button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="' + newTabId + '">' + escapeHtml(texts.documentsPickerNew || 'Nouveau document') + '</button>'
                + '</div><div class="generic-tabs__panels">'
                + '<section id="' + existingTabId + '" class="generic-tabs__panel" data-generic-tab-panel>'
                + '<div class="omo-resource-picker"><aside class="omo-resource-picker__navigation" data-omo-project-document-picker-scope></aside>'
                + '<div class="omo-resource-picker__content">'
                + '<label class="omo-resource-picker__quick-search"><img src="/common/assets/icon-topbar-search.png" alt="" aria-hidden="true"><input type="search" class="generic-form-control" data-omo-project-document-picker-search aria-label="' + escapeHtml(texts.documentsPickerSearch || 'Rechercher un document') + '" placeholder="' + escapeHtml(texts.documentsPickerSearch || 'Rechercher un document') + '"></label>'
                + '<div class="omo-document-embed-picker__field"><select class="generic-form-control omo-document-embed-picker__select" data-omo-project-document-picker-select aria-label="' + escapeHtml(texts.documentsPickerVisible || 'Documents visibles') + '" size="10"></select></div>'
                + '<div class="omo-document-embed-picker__preview"><div class="omo-document-embed-picker__preview-title" data-omo-project-document-picker-title></div><div class="omo-document-embed-picker__preview-description" data-omo-project-document-picker-description hidden></div></div>'
                + '<p class="omo-project-document-picker__error" data-omo-project-document-picker-error hidden></p>'
                + '<div class="omo-document-embed-picker__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-project-document-picker-cancel>' + escapeHtml(texts.cancel || 'Annuler') + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-project-document-picker-attach disabled>' + escapeHtml(texts.documentsPickerAttach || 'Associer au projet') + '</button></div>'
                + '</div></div></section>'
                + '<section id="' + newTabId + '" class="generic-tabs__panel" data-generic-tab-panel hidden><div data-omo-project-document-picker-create-content><p class="omo-project-document-picker__loading generic-description generic-description--small">' + escapeHtml(texts.documentsLoading || 'Chargement...') + '</p></div></section></div></div>';

            modalBody.innerHTML = html;
            if (typeof window.initGenericComponents === 'function') {
                window.initGenericComponents(modalBody);
            }

            var dialog = modalBody.querySelector('[data-generic-tabs].omo-project-document-picker');
            if (!dialog) {
                return;
            }

            var search = dialog.querySelector('[data-omo-project-document-picker-search]');
            var select = dialog.querySelector('[data-omo-project-document-picker-select]');
            var title = dialog.querySelector('[data-omo-project-document-picker-title]');
            var description = dialog.querySelector('[data-omo-project-document-picker-description]');
            var error = dialog.querySelector('[data-omo-project-document-picker-error]');
            var attachButton = dialog.querySelector('[data-omo-project-document-picker-attach]');
            var createContent = dialog.querySelector('[data-omo-project-document-picker-create-content]');
            var newTabButton = dialog.querySelector('[data-generic-tab-target="' + newTabId + '"]');
            var selectedDocumentId = 0;
            var scopePicker = null;
            var documents = Array.isArray(data.documents) ? data.documents : [];

            function showError(target, message) {
                if (target) {
                    target.textContent = String(message || '');
                    target.hidden = false;
                }
            }

            function renderDocuments() {
                if (!select) {
                    return;
                }
                var query = String(search && search.value || '').trim().toLowerCase();
                var matches = documents.filter(function (item) {
                    var contextHolonId = Number(item && item.contextHolonId || 0);
                    var matchesScope = !scopePicker || contextHolonId <= 0 || scopePicker.matches(contextHolonId);
                    var text = [item && item.title, item && item.description, item && item.contextLabel].join(' ').toLowerCase();
                    return matchesScope && (query === '' || text.indexOf(query) !== -1);
                });
                select.innerHTML = '';
                matches.forEach(function (item) {
                    var option = document.createElement('option');
                    option.value = String(item.id || 0);
                    option.textContent = String(item.title || '') + (item.contextLabel ? ' - ' + String(item.contextLabel) : '');
                    select.appendChild(option);
                });
                selectedDocumentId = matches.some(function (item) { return Number(item.id || 0) === selectedDocumentId; }) ? selectedDocumentId : 0;
                select.value = selectedDocumentId > 0 ? String(selectedDocumentId) : '';
                if (title) {
                    title.textContent = selectedDocumentId > 0 ? String((documents.find(function (item) { return Number(item.id || 0) === selectedDocumentId; }) || {}).title || '') : (texts.documentsPickerNone || '');
                }
                var selectedItem = documents.find(function (item) { return Number(item.id || 0) === selectedDocumentId; });
                if (description) {
                    description.textContent = selectedItem && selectedItem.description ? String(selectedItem.description) : '';
                    description.hidden = !selectedItem || !selectedItem.description;
                }
                if (attachButton) {
                    attachButton.disabled = selectedDocumentId <= 0;
                }
            }

            scopePicker = typeof window.omoMountHolonScopePicker === 'function'
                ? window.omoMountHolonScopePicker({
                    host: dialog.querySelector('[data-omo-project-document-picker-scope]'),
                    organizationId: Number(root.getAttribute('data-omo-projects-oid') || 0),
                    initialHolonId: Number(data.projectHolonId || routeCid || 0),
                    initialScope: 'local',
                    labels: data.scopeLabels || {},
                    onChange: renderDocuments
                })
                : null;

            if (search) {
                search.addEventListener('input', renderDocuments);
                search.focus();
            }
            if (select) {
                select.addEventListener('change', function () {
                    selectedDocumentId = Number(select.value || 0);
                    renderDocuments();
                    if (error) {
                        error.hidden = true;
                    }
                });
            }
            dialog.querySelectorAll('[data-omo-project-document-picker-cancel]').forEach(function (button) {
                button.addEventListener('click', function () {
                    window.commonTopbarCloseModal();
                });
            });
            if (attachButton) {
                attachButton.addEventListener('click', function () {
                    if (selectedDocumentId <= 0) {
                        showError(error, texts.documentsPickerSelectRequired);
                        return;
                    }
                    attachButton.disabled = true;
                    postProjectAction(Number(data.projectId || 0), 'attach_document', {document_id: selectedDocumentId}).then(function (result) {
                        window.dispatchEvent(new CustomEvent('omo-project-document-saved', {detail: {projectId: Number(result.projectId || data.projectId || 0), documentId: Number(result.documentId || selectedDocumentId)}}));
                        window.commonTopbarCloseModal();
                    }).catch(function (actionError) {
                        attachButton.disabled = false;
                        showError(error, actionError.message || texts.documentsPickerAttachError);
                    });
                });
            }
            function loadCreateForm() {
                if (!createContent || createContent.dataset.omoProjectDocumentCreateLoaded === '1' || createContent.dataset.omoProjectDocumentCreateLoading === '1') {
                    return;
                }

                var createUrl = String(data.createUrl || '');
                if (createUrl === '') {
                    createContent.innerHTML = '<p class="omo-project-document-picker__error"></p>';
                    showError(createContent.firstElementChild, texts.documentsPickerCreateError);
                    return;
                }

                createContent.dataset.omoProjectDocumentCreateLoading = '1';
                fetch(resolveUrl(createUrl + '&editor_instance=' + encodeURIComponent(String(Date.now()))), {
                    credentials: 'same-origin',
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    cache: 'no-store'
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error('load_failed');
                    }
                    return response.text();
                }).then(function (html) {
                    if (!createContent.isConnected) {
                        return;
                    }
                    createContent.innerHTML = html;
                    createContent.dataset.omoProjectDocumentCreateLoaded = '1';
                    createContent.removeAttribute('data-omo-project-document-create-loading');
                    if (typeof window.initGenericComponents === 'function') {
                        window.initGenericComponents(createContent);
                    }
                    return executeFetchedScripts(createContent).then(function () {
                        if (typeof window.initGenericComponents === 'function') {
                            window.initGenericComponents(createContent);
                        }
                    });
                }).catch(function () {
                    if (!createContent.isConnected) {
                        return;
                    }
                    createContent.innerHTML = '<p class="omo-project-document-picker__error"></p>';
                    showError(createContent.firstElementChild, texts.documentsPickerCreateError);
                }).finally(function () {
                    if (createContent.isConnected) {
                        createContent.removeAttribute('data-omo-project-document-create-loading');
                    }
                });
            }

            if (newTabButton) {
                newTabButton.addEventListener('click', loadCreateForm);
            }
            renderDocuments();
        }).catch(function (error) {
            var modalBody = document.getElementById('commonTopbarModalBody');
            if (modalBody) {
                modalBody.innerHTML = '<p class="omo-project-document-picker__error"></p>';
                var message = modalBody.querySelector('.omo-project-document-picker__error');
                if (message) {
                    message.textContent = error.message || texts.documentsPickerError;
                }
            }
        });
    }

    function closeProjectDocumentDrawer() {
        if (!documentDrawer) {
            return;
        }
        window.dispatchEvent(new CustomEvent('omo-document-editor-drawer-close'));
        window.dispatchEvent(new CustomEvent('omo-calendar-event-editor-drawer-close'));
        documentDrawer.classList.remove('is-open');
        documentDrawer.classList.remove('is-calendar-event-editor');
        window.setTimeout(function () {
            if (!documentDrawer.classList.contains('is-open')) {
                documentDrawer.hidden = true;
                if (documentDrawerBody) {
                    documentDrawerBody.innerHTML = '';
                }
                if (documentDrawerController) {
                    documentDrawerController.resetHeader();
                }
            }
        }, 180);
    }

    function closeDrawer() {
        if (!drawer) {
            return;
        }
        drawer.classList.remove('is-open');
        window.setTimeout(function () {
            if (!drawer.classList.contains('is-open')) {
                drawer.hidden = true;
                drawerBody.innerHTML = '';
                if (rootNeedsRefresh) {
                    rootNeedsRefresh = false;
                    refreshRoot(currentUrl, {preserveScroll: true});
                }
                if (drawerController) {
                    drawerController.resetHeader();
                }
            }
        }, 180);
    }

    function buildProjectsUrl(scope, view, listSort, assignment, quickSearch) {
        var organizationId = Number(root.getAttribute('data-omo-projects-oid') || 0);
        var query = ['oid=' + encodeURIComponent(String(organizationId))];
        var nextScope = scope === 'global' ? 'descendants' : (scope === 'descendants' || scope === 'children' ? scope : 'contextual');
        var nextView = view === 'list' || view === 'gantt' ? view : 'kanban';
        var nextListSort = listSort === 'planned' || listSort === 'priority' || listSort === 'importance' || listSort === 'holon' ? listSort : 'importance';
        var nextAssignment = assignment === 'mine' ? 'mine' : 'all';
        var nextQuickSearch = String(quickSearch || '').trim();
        if (routeCid > 0) {
            query.push('cid=' + encodeURIComponent(String(routeCid)));
        }
        if (nextScope !== 'contextual') {
            query.push('project_scope=' + encodeURIComponent(nextScope));
        }
        if (nextView !== 'kanban') {
            query.push('project_view=' + encodeURIComponent(nextView));
        }
        if (nextListSort !== 'importance') {
            query.push('project_sort=' + encodeURIComponent(nextListSort));
        }
        if (nextAssignment === 'mine') {
            query.push('project_assignment=mine');
        }
        if (nextQuickSearch !== '') {
            query.push('project_query=' + encodeURIComponent(nextQuickSearch));
        }
        return '/omo/api/projects/index.php?' + query.join('&');
    }

    function buildArchivesUrl() {
        var organizationId = Number(root.getAttribute('data-omo-projects-oid') || 0);
        var scope = root.getAttribute('data-omo-projects-scope') || 'contextual';
        var query = ['oid=' + encodeURIComponent(String(organizationId))];
        if (routeCid > 0) {
            query.push('cid=' + encodeURIComponent(String(routeCid)));
        }
        if (scope !== 'contextual') {
            query.push('project_scope=' + encodeURIComponent(scope));
        }
        if (currentAssignment === 'mine') {
            query.push('project_assignment=mine');
        }
        if (String(currentQuickSearch || '').trim() !== '') {
            query.push('project_query=' + encodeURIComponent(String(currentQuickSearch).trim()));
        }
        return '/omo/api/projects/archives.php?' + query.join('&');
    }

    function getDisplayPreferencesContextKey() {
        var regularKey = String(root.getAttribute('data-omo-projects-oid') || '0')
            + ':' + String(root.getAttribute('data-omo-projects-cid') || '0');
        return typeof window.omoApplicationViewPreferencesGetStorageContextKey === 'function'
            ? window.omoApplicationViewPreferencesGetStorageContextKey(root, regularKey)
            : regularKey;
    }

    function getDisplayPreferencesStore() {
        try {
            var storedValue = window.localStorage.getItem(displayPreferencesStorageKey);
            var savedViews = storedValue ? JSON.parse(storedValue) : null;
            if (savedViews && typeof savedViews === 'object' && savedViews.contexts && typeof savedViews.contexts === 'object') {
                return {
                    defaultView: savedViews.defaultView && typeof savedViews.defaultView === 'object' ? savedViews.defaultView : null,
                    contexts: savedViews.contexts
                };
            }

            var legacyValue = window.localStorage.getItem(legacyDisplayPreferencesStorageKey);
            var legacyViews = legacyValue ? JSON.parse(legacyValue) : null;
            return {
                defaultView: null,
                contexts: legacyViews && typeof legacyViews === 'object' ? legacyViews : {}
            };
        } catch (error) {
            return {defaultView: null, contexts: {}};
        }
    }

    function saveDisplayPreferencesStore(store) {
        try {
            window.localStorage.setItem(displayPreferencesStorageKey, JSON.stringify({
                defaultView: store.defaultView && typeof store.defaultView === 'object' ? store.defaultView : null,
                contexts: store.contexts && typeof store.contexts === 'object' ? store.contexts : {}
            }));
        } catch (error) {
            // Local storage can be unavailable in private or restricted browsing contexts.
        }
    }

    function createDisplayPreferences(scope, view, listSort, assignment) {
        return {
            scope: scope === 'global' ? 'descendants' : (scope === 'descendants' || scope === 'children' ? scope : 'contextual'),
            view: view === 'list' || view === 'gantt' ? view : 'kanban',
            sort: listSort === 'planned' || listSort === 'priority' || listSort === 'importance' || listSort === 'holon' ? listSort : 'importance',
            assignment: assignment === 'mine' ? 'mine' : 'all'
        };
    }

    function getStoredDisplayPreferences() {
        var preferences = getDisplayPreferencesStore().contexts[getDisplayPreferencesContextKey()];
        return preferences && typeof preferences === 'object' ? preferences : null;
    }

    function getDefaultDisplayPreferences() {
        return getDisplayPreferencesStore().defaultView;
    }

    function getTemporaryDisplayPreferences() {
        try {
            var storedValue = window.sessionStorage.getItem(temporaryDisplayPreferencesStorageKey);
            var temporaryViews = storedValue ? JSON.parse(storedValue) : null;
            if (!temporaryViews || typeof temporaryViews !== 'object') {
                return null;
            }
            var preferences = temporaryViews[getDisplayPreferencesContextKey()];
            return preferences && typeof preferences === 'object' ? preferences : null;
        } catch (error) {
            return null;
        }
    }

    function storeTemporaryDisplayPreferences(scope, view, listSort, assignment) {
        try {
            var storedValue = window.sessionStorage.getItem(temporaryDisplayPreferencesStorageKey);
            var temporaryViews = storedValue ? JSON.parse(storedValue) : {};
            if (!temporaryViews || typeof temporaryViews !== 'object') {
                temporaryViews = {};
            }
            temporaryViews[getDisplayPreferencesContextKey()] = createDisplayPreferences(scope, view, listSort, assignment);
            window.sessionStorage.setItem(temporaryDisplayPreferencesStorageKey, JSON.stringify(temporaryViews));
        } catch (error) {
            // Session storage can be unavailable in private or restricted browsing contexts.
        }
    }

    function clearTemporaryDisplayPreferences() {
        try {
            var storedValue = window.sessionStorage.getItem(temporaryDisplayPreferencesStorageKey);
            var temporaryViews = storedValue ? JSON.parse(storedValue) : {};
            if (!temporaryViews || typeof temporaryViews !== 'object') {
                return;
            }
            delete temporaryViews[getDisplayPreferencesContextKey()];
            window.sessionStorage.setItem(temporaryDisplayPreferencesStorageKey, JSON.stringify(temporaryViews));
        } catch (error) {
            // Session storage can be unavailable in private or restricted browsing contexts.
        }
    }

    function clearAllTemporaryDisplayPreferences() {
        try {
            window.sessionStorage.removeItem(temporaryDisplayPreferencesStorageKey);
        } catch (error) {
            // Session storage can be unavailable in private or restricted browsing contexts.
        }
    }

    function storeDisplayPreferences(scope, view, listSort, assignment) {
        var store = getDisplayPreferencesStore();
        store.contexts[getDisplayPreferencesContextKey()] = createDisplayPreferences(scope, view, listSort, assignment);
        saveDisplayPreferencesStore(store);
    }

    function storeDefaultDisplayPreferences(scope, view, listSort, assignment) {
        var store = getDisplayPreferencesStore();
        store.defaultView = createDisplayPreferences(scope, view, listSort, assignment);
        saveDisplayPreferencesStore(store);
    }

    function clearCurrentDisplayPreferences() {
        var store = getDisplayPreferencesStore();
        delete store.contexts[getDisplayPreferencesContextKey()];
        saveDisplayPreferencesStore(store);
    }

    function applyDisplayPreferences(next, active) {
        if (next.scope === active.scope && next.assignment === active.assignment && next.sort === active.sort && next.view === active.view) {
            return;
        }
        refreshRoot(buildProjectsUrl(next.scope, next.view, next.sort, next.assignment, currentQuickSearch));
    }

    function applyStoredDisplayPreferences() {
        var serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
            ? window.omoApplicationViewPreferencesGetDefault(root)
            : null;
        var personalView = typeof window.omoApplicationViewPreferencesGetPersonal === 'function'
            ? window.omoApplicationViewPreferencesGetPersonal(root)
            : null;
        var preferences = getTemporaryDisplayPreferences() || personalView || serverDefault || getStoredDisplayPreferences() || getDefaultDisplayPreferences();
        if (!preferences) {
            return false;
        }

        var currentScope = root.getAttribute('data-omo-projects-scope') || 'contextual';
        var currentAssignmentValue = root.getAttribute('data-omo-projects-assignment') === 'mine' ? 'mine' : 'all';
        currentAssignment = currentAssignmentValue;
        var rawPreferredScope = String(preferences.scope || '');
        var preferredScope = ['contextual', 'children', 'descendants', 'global'].indexOf(rawPreferredScope) !== -1
            ? (rawPreferredScope === 'global' ? 'descendants' : rawPreferredScope)
            : '';
        var scopeButton = preferredScope
            ? root.querySelector('[data-omo-projects-scope="' + preferredScope + '"]')
            : null;
        var nextScope = scopeButton ? preferredScope : currentScope;
        if (!scopeButton && (preferredScope === 'children' || preferredScope === 'descendants')) {
            nextScope = 'contextual';
        }
        var nextView = preferences.view === 'list' || preferences.view === 'gantt' ? preferences.view : currentView;
        var rawPreferredAssignment = String(preferences.assignment || '');
        var nextAssignment = rawPreferredAssignment === 'mine' || rawPreferredAssignment === 'all'
            ? rawPreferredAssignment
            : currentAssignment;
        var preferredSort = String(preferences.sort || '');
        var canUseHolonSort = nextScope === 'descendants' || nextScope === 'children';
        var nextSort = preferredSort === 'priority' || preferredSort === 'importance' || preferredSort === 'planned'
            ? preferredSort
            : (preferredSort === 'holon' && canUseHolonSort ? 'holon' : currentListSort);

        if (nextScope === currentScope && nextView === currentView && nextSort === currentListSort && nextAssignment === currentAssignment) {
            return false;
        }

        var refreshUrl = buildProjectsUrl(nextScope, nextView, nextSort, nextAssignment, currentQuickSearch);
        if (initialOpenProjectMode !== '') {
            refreshUrl += '&open_project_mode=' + encodeURIComponent(initialOpenProjectMode);
            if (initialOpenProjectId > 0) {
                refreshUrl += '&open_project_id=' + encodeURIComponent(String(initialOpenProjectId));
            }
        }

        refreshRoot(refreshUrl).catch(function () {
            revealRoot();
        });
        return true;
    }

    function buildProjectRouteToken(projectId, mode) {
        if (typeof window.omoBuildProjectRouteToken === 'function') {
            return window.omoBuildProjectRouteToken(projectId, mode);
        }

        var resolvedProjectId = Number(projectId || 0);
        if (mode === 'create') {
            return 'projects-new';
        }
        if (!Number.isInteger(resolvedProjectId) || resolvedProjectId <= 0) {
            return '';
        }
        return mode === 'edit' ? 'projects-e' + String(resolvedProjectId) : 'projects-d' + String(resolvedProjectId);
    }

    function getCurrentRouteToken() {
        if (useLocalDrawerNavigation) {
            return '';
        }
        if (typeof window.omoParsePopupHashState !== 'function') {
            return '';
        }
        var state = window.omoParsePopupHashState();
        return state && state.routeToken ? String(state.routeToken) : '';
    }

    function isProjectDrawerRoute(routeToken) {
        return /^projects-(?:new|d\d+|e\d+)$/i.test(String(routeToken || ''));
    }

    function buildDetailUrl(projectId) {
        return detailUrl + '&id=' + encodeURIComponent(String(projectId)) + '&cid=' + encodeURIComponent(String(routeCid));
    }

    function buildEditUrl(projectId) {
        return createUrl + '&id=' + encodeURIComponent(String(projectId));
    }

    function buildCreateUrl(status) {
        if (pendingCreateUrl !== '') {
            return pendingCreateUrl;
        }
        var normalizedStatus = String(status || '').trim().toLowerCase();
        if (columns.indexOf(normalizedStatus) === -1) {
            normalizedStatus = '';
        }
        return createUrl + (normalizedStatus !== '' ? '&status=' + encodeURIComponent(normalizedStatus) : '');
    }

    function navigateProject(projectId, mode, fallbackUrl) {
        var routeToken = buildProjectRouteToken(projectId, mode);
        if (!useLocalDrawerNavigation && routeToken && typeof window.omoOpenDrawerHashState === 'function' && routeToken !== getCurrentRouteToken()) {
            window.omoOpenDrawerHashState(routeToken);
            return;
        }

        openDrawerWithUrl(fallbackUrl || (mode === 'create'
            ? buildCreateUrl(pendingCreateStatus)
            : (mode === 'edit' ? buildEditUrl(projectId) : buildDetailUrl(projectId))));
    }

    function updateColumnCounts() {
        root.querySelectorAll('[data-omo-projects-column]').forEach(function (column) {
            var status = column.getAttribute('data-omo-projects-column') || '';
            var cards = Array.prototype.slice.call(root.querySelectorAll('[data-omo-projects-cards="' + status + '"]'));
            var count = cards.length > 0
                ? cards.reduce(function (total, cardsContainer) {
                    return total + Array.prototype.filter.call(cardsContainer.querySelectorAll('[data-omo-project-card]'), function (card) {
                        return !card.hidden;
                    }).length;
                }, 0)
                : 0;
            var countNode = column.querySelector('[data-omo-projects-column-count]');
            if (countNode) {
                countNode.textContent = String(count);
            }

            if (cards.length !== 1 || !column.contains(cards[0])) {
                return;
            }
            var cardsContainer = cards[0];
            var emptyNode = column.querySelector('[data-omo-projects-column-empty]');
            if (count === 0 && !emptyNode) {
                emptyNode = document.createElement('div');
                emptyNode.className = 'omo-projects__column-empty';
                emptyNode.setAttribute('data-omo-projects-column-empty', '');
                emptyNode.textContent = texts.emptyColumn;
                cardsContainer.appendChild(emptyNode);
            } else if (count > 0 && emptyNode) {
                emptyNode.remove();
            }
        });
    }

    function normalizeQuickSearch(value) {
        return String(value || '')
            .toLocaleLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function applyQuickSearch() {
        var query = normalizeQuickSearch(currentQuickSearch);
        var visibleCount = 0;
        root.querySelectorAll('[data-omo-project-card], [data-omo-project-list-item], [data-omo-project-gantt-item]').forEach(function (projectNode) {
            var searchableText = projectNode.getAttribute('data-project-search') || projectNode.textContent || '';
            var matches = query === '' || normalizeQuickSearch(searchableText).indexOf(query) !== -1;
            projectNode.hidden = !matches;
            if (matches) {
                visibleCount++;
            }
        });

        root.querySelectorAll('[data-omo-projects-list-group]').forEach(function (group) {
            group.hidden = !group.querySelector('[data-omo-project-list-item]:not([hidden])');
        });
        root.querySelectorAll('[data-omo-projects-kanban-row]').forEach(function (row) {
            row.hidden = !row.querySelector('[data-omo-project-card]:not([hidden])');
        });
        var board = root.querySelector('[data-omo-projects-board]');
        var list = root.querySelector('[data-omo-projects-list]');
        var gantt = root.querySelector('[data-omo-projects-gantt]');
        if (board) {
            board.hidden = query !== '' && visibleCount === 0;
        }
        if (list) {
            list.hidden = query !== '' && visibleCount === 0;
        }
        if (gantt) {
            gantt.hidden = query !== '' && visibleCount === 0;
        }
        updateColumnCounts();
        if (quickSearchEmpty) {
            quickSearchEmpty.hidden = query === '' || visibleCount > 0;
        }
    }

    function getActiveDisplayFilters() {
        return {
            scope: root.getAttribute('data-omo-projects-scope') || 'contextual',
            assignment: root.getAttribute('data-omo-projects-assignment') === 'mine' ? 'mine' : 'all',
            sort: currentListSort,
            view: currentView
        };
    }

    function normalizeDisplayFilters(filters) {
        var next = filters || {};
        var active = getActiveDisplayFilters();
        var scope = next.scope === 'children' || next.scope === 'descendants' ? next.scope : 'contextual';
        if (!filterPanel || !filterPanel.querySelector('[data-omo-projects-scope="' + scope + '"]')) {
            scope = active.scope;
        }
        var assignment = next.assignment === 'mine' ? 'mine' : 'all';
        var view = next.view === 'list' || next.view === 'gantt' ? next.view : 'kanban';
        var sort = next.sort === 'planned' || next.sort === 'priority' || next.sort === 'importance' || next.sort === 'holon' ? next.sort : 'importance';
        if (sort === 'holon' && (scope !== 'children' && scope !== 'descendants')) {
            sort = 'importance';
        }
        return {scope: scope, assignment: assignment, sort: sort, view: view};
    }

    function syncFilterPanelChoices() {
        if (!filterPanel || !pendingDisplayFilters) {
            return;
        }
        var filters = normalizeDisplayFilters(pendingDisplayFilters);
        pendingDisplayFilters = filters;
        filterPanel.querySelectorAll('[data-omo-projects-scope]').forEach(function (button) {
            var active = button.getAttribute('data-omo-projects-scope') === filters.scope;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        filterPanel.querySelectorAll('[data-omo-projects-assignment]').forEach(function (button) {
            var active = button.getAttribute('data-omo-projects-assignment') === filters.assignment;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        filterPanel.querySelectorAll('[data-omo-projects-sort]').forEach(function (button) {
            var isHolonSort = button.getAttribute('data-omo-projects-sort') === 'holon';
            var canUseHolonSort = filters.scope === 'children' || filters.scope === 'descendants';
            if (isHolonSort) {
                button.disabled = !canUseHolonSort;
                button.setAttribute('aria-disabled', canUseHolonSort ? 'false' : 'true');
            }
            var active = button.getAttribute('data-omo-projects-sort') === filters.sort;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        filterPanel.querySelectorAll('[data-omo-projects-view]').forEach(function (button) {
            var active = button.getAttribute('data-omo-projects-view') === filters.view;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function removeProjectFilterOutsideHandler() {
        document.removeEventListener('pointerdown', handleProjectFilterOutsidePointerDown, true);
    }

    function closeProjectFilterMoreMenu() {
        if (!filterPanel) {
            return;
        }
        filterPanel.querySelectorAll('[data-omo-projects-filter-more-menu]').forEach(function (menu) {
            var panel = menu.querySelector('[data-omo-projects-filter-more-panel]');
            var toggle = menu.querySelector('[data-omo-projects-filter-more-toggle]');
            if (panel) {
                panel.hidden = true;
            }
            menu.classList.remove('is-open');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function closeProjectFilterPanel(applyChanges, saveView) {
        if (!filterPanelIsOpen) {
            return;
        }
        filterPanelIsOpen = false;
        if (filterPanel) {
            filterPanel.hidden = true;
        }
        root.querySelectorAll('[data-omo-projects-filter-toggle]').forEach(function (button) {
            button.setAttribute('aria-expanded', 'false');
        });
        removeProjectFilterOutsideHandler();
        closeProjectFilterMoreMenu();

        if (!applyChanges || !pendingDisplayFilters) {
            pendingDisplayFilters = null;
            return;
        }

        var active = getActiveDisplayFilters();
        var next = normalizeDisplayFilters(pendingDisplayFilters);
        pendingDisplayFilters = null;
        if (saveView) {
            storeDisplayPreferences(next.scope, next.view, next.sort, next.assignment);
            clearTemporaryDisplayPreferences();
        } else {
            storeTemporaryDisplayPreferences(next.scope, next.view, next.sort, next.assignment);
        }
        if (next.scope === active.scope && next.assignment === active.assignment && next.sort === active.sort && next.view === active.view) {
            return;
        }
        applyDisplayPreferences(next, active);
    }

    function applyProjectFilterMoreAction(action) {
        if (!filterPanelIsOpen || !pendingDisplayFilters) {
            return;
        }

        var active = getActiveDisplayFilters();
        var next = normalizeDisplayFilters(pendingDisplayFilters);
        closeProjectFilterPanel(false, false);

        if (action === 'set-default') {
            clearCurrentDisplayPreferences();
            clearTemporaryDisplayPreferences();
            storeDefaultDisplayPreferences(next.scope, next.view, next.sort, next.assignment);
            applyDisplayPreferences(next, active);
            return;
        }

        if (action === 'apply-everywhere') {
            var store = getDisplayPreferencesStore();
            store.defaultView = createDisplayPreferences(next.scope, next.view, next.sort, next.assignment);
            store.contexts = {};
            saveDisplayPreferencesStore(store);
            clearAllTemporaryDisplayPreferences();
            applyDisplayPreferences(next, active);
            return;
        }

        if (action === 'restore-default') {
            clearCurrentDisplayPreferences();
            clearTemporaryDisplayPreferences();
            var store = getDisplayPreferencesStore();
            store.defaultView = null;
            saveDisplayPreferencesStore(store);
            var serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
                ? window.omoApplicationViewPreferencesGetDefault(root)
                : null;
            var defaultView = serverDefault || {
                scope: 'contextual',
                view: 'kanban',
                sort: 'importance',
                assignment: 'all'
            };
            applyDisplayPreferences(normalizeDisplayFilters(defaultView), active);
        }
    }

    function handleProjectFilterOutsidePointerDown(event) {
        if (!filterControl || filterControl.contains(event.target)) {
            return;
        }
        closeProjectFilterPanel(true);
    }

    function openProjectFilterPanel() {
        if (!filterControl || !filterPanel || filterPanelIsOpen) {
            return;
        }
        pendingDisplayFilters = getActiveDisplayFilters();
        closeProjectFilterMoreMenu();
        syncFilterPanelChoices();
        filterPanel.hidden = false;
        filterPanelIsOpen = true;
        root.querySelectorAll('[data-omo-projects-filter-toggle]').forEach(function (button) {
            button.setAttribute('aria-expanded', 'true');
        });
        document.addEventListener('pointerdown', handleProjectFilterOutsidePointerDown, true);
    }

    function clearRelatedProjectHighlights() {
        root.querySelectorAll('.is-showing-children, .is-related-child').forEach(function (card) {
            card.classList.remove('is-showing-children', 'is-related-child');
        });
    }

    function highlightProjectChildren(projectNode) {
        var projectId = Number(projectNode && projectNode.getAttribute('data-project-id') || 0);
        clearRelatedProjectHighlights();
        if (projectId <= 0 || !projectNode) {
            return;
        }
        projectNode.classList.add('is-showing-children');
        root.querySelectorAll('[data-omo-project-card][data-project-parent-id="' + String(projectId) + '"], [data-omo-project-list-item][data-project-parent-id="' + String(projectId) + '"], [data-omo-project-gantt-item][data-project-parent-id="' + String(projectId) + '"]').forEach(function (childNode) {
            childNode.classList.add('is-related-child');
        });
    }

    function updateMobileColumn() {
        var columnNodes = Array.prototype.slice.call(root.querySelectorAll('[data-omo-projects-column]'));
        if (!columnNodes.length) {
            return;
        }
        mobileColumnIndex = Math.max(0, Math.min(mobileColumnIndex, columnNodes.length - 1));
        columnNodes.forEach(function (column, index) {
            column.classList.toggle('is-mobile-active', index === mobileColumnIndex);
        });
        var currentColumn = columnNodes[mobileColumnIndex];
        var currentStatus = currentColumn ? (currentColumn.getAttribute('data-omo-projects-column') || '') : '';
        root.querySelectorAll('[data-omo-projects-kanban-cell]').forEach(function (cell) {
            cell.classList.toggle('is-mobile-active', cell.getAttribute('data-status') === currentStatus);
        });
        var labelNode = root.querySelector('[data-omo-projects-column-label]');
        var previousButton = root.querySelector('[data-omo-projects-column-prev]');
        var nextButton = root.querySelector('[data-omo-projects-column-next]');
        var titleNode = currentColumn ? currentColumn.querySelector('h3') : null;
        if (labelNode) {
            labelNode.textContent = titleNode ? titleNode.textContent : '';
        }
        if (previousButton) {
            previousButton.disabled = mobileColumnIndex <= 0;
        }
        if (nextButton) {
            nextButton.disabled = mobileColumnIndex >= columnNodes.length - 1;
        }
    }

    function syncGroupedKanbanHeaderOffset() {
        root.querySelectorAll('.omo-projects__board--grouped').forEach(function (board) {
            var header = board.querySelector('.omo-projects__kanban-grid-header');
            if (!header) {
                return;
            }
            board.style.setProperty('--omo-projects-kanban-header-offset', String(header.offsetHeight) + 'px');
        });
    }

    function getKanbanRowData(element) {
        var row = element ? element.closest('[data-omo-projects-kanban-row]') : null;
        if (!row) {
            return null;
        }
        return {
            node: row,
            kind: row.getAttribute('data-omo-projects-kanban-group-kind') || '',
            value: row.getAttribute('data-omo-projects-kanban-group-value') || ''
        };
    }

    function getKanbanDropMutation(card, target) {
        var sourceRow = getKanbanRowData(card);
        var targetRow = getKanbanRowData(target);
        if (!sourceRow || !targetRow || sourceRow.node === targetRow.node) {
            return {allowed: true, fields: null};
        }
        if (targetRow.kind === 'holon' && Number(targetRow.value || 0) > 0) {
            return {
                allowed: true,
                fields: {
                    group_kind: 'holon',
                    target_holon_id: targetRow.value
                }
            };
        }
        if (targetRow.kind === 'priority') {
            return {
                allowed: true,
                fields: {
                    group_kind: 'priority',
                    target_priority: targetRow.value
                }
            };
        }
        return {allowed: false, fields: null};
    }

    function moveCard(card, nextStatus, targetOverride) {
        var group = card ? card.closest('[data-omo-projects-kanban-row]') : null;
        var target = targetOverride || (group
            ? group.querySelector('[data-omo-projects-cards="' + nextStatus + '"]')
            : root.querySelector('[data-omo-projects-cards="' + nextStatus + '"]'));
        if (!card || !target) {
            return;
        }
        var currentStatus = card.getAttribute('data-project-status') || '';
        var mutation = getKanbanDropMutation(card, target);
        if (!mutation.allowed) {
            return;
        }
        if (currentStatus === nextStatus && !mutation.fields) {
            updateColumnCounts();
            return;
        }
        var statusSelect = card.querySelector('[data-omo-project-status-select]');
        card.classList.add('is-pending-status');
        var request = mutation.fields
            ? postProjectAction(card.getAttribute('data-project-id'), 'update_kanban_position', Object.assign({status: nextStatus}, mutation.fields))
            : postStatus(card.getAttribute('data-project-id'), nextStatus);
        request.then(function () {
            refreshRoot(currentUrl, {
                preserveScroll: true,
                revealProjectId: Number(card.getAttribute('data-project-id') || 0)
            });
        }).catch(function (error) {
            card.classList.remove('is-pending-status');
            if (statusSelect) {
                statusSelect.value = currentStatus;
            }
            window.omoNotify(error.message || texts.statusUpdateError, 'error');
        });
    }

    function postProjectAction(projectId, action, fields) {
        var payload = new FormData();
        payload.append('project_action', String(action || ''));
        payload.append('oid', root.getAttribute('data-omo-projects-oid') || '0');
        payload.append('cid', String(routeCid));
        payload.append('id', String(projectId || '0'));
        Object.keys(fields || {}).forEach(function (name) {
            payload.append(name, String(fields[name] || ''));
        });
        return fetch(resolveUrl(actionUrl), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: payload
        }).then(function (response) {
            return response.text().then(function (text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (error) {
                    data = null;
                }
                if (!response.ok || !data || !data.success) {
                    throw new Error(data && data.message ? data.message : texts.actionError);
                }
                return data;
            });
        });
    }

    function postStatus(projectId, status) {
        return postProjectAction(projectId, 'update_status', {status: status});
    }

    function getSelectedProjectIds() {
        return Object.keys(selectedProjectIds).map(function (projectId) {
            return Number(projectId);
        }).filter(function (projectId) {
            return Number.isInteger(projectId) && projectId > 0;
        });
    }

    function selectedProjectsCanBeDeleted(selectedIds) {
        var deleteAllowedByProjectId = {};
        root.querySelectorAll('[data-omo-project-select]').forEach(function (input) {
            var projectId = Number(input.value || 0);
            if (!Number.isInteger(projectId) || projectId <= 0) {
                return;
            }
            deleteAllowedByProjectId[projectId] = input.getAttribute('data-project-can-delete') === '1';
        });
        return selectedIds.every(function (projectId) {
            return deleteAllowedByProjectId[projectId] === true;
        });
    }

    function syncProjectSelection() {
        var selectedIds = getSelectedProjectIds();
        var selectedCount = selectedIds.length;
        var bulkActions = root.querySelector('[data-omo-projects-bulk-actions]');
        var bulkCount = root.querySelector('[data-omo-projects-bulk-count]');
        var deleteButton = root.querySelector('[data-omo-projects-bulk-action="delete"]');
        if (bulkActions) {
            bulkActions.hidden = selectedCount === 0;
        }
        if (bulkCount) {
            bulkCount.textContent = formatText(texts.selectionCount || '{count} sélectionnés', {count: selectedCount});
        }
        if (deleteButton) {
            deleteButton.hidden = selectedCount === 0 || !selectedProjectsCanBeDeleted(selectedIds);
        }
        root.querySelectorAll('[data-omo-project-select]').forEach(function (input) {
            var projectId = Number(input.value || 0);
            input.checked = Number.isInteger(projectId) && !!selectedProjectIds[projectId];
        });
        root.querySelectorAll('[data-omo-project-card], [data-omo-project-list-item], [data-omo-project-gantt-item]').forEach(function (projectNode) {
            var projectId = Number(projectNode.getAttribute('data-project-id') || 0);
            projectNode.classList.toggle('is-selected', Number.isInteger(projectId) && !!selectedProjectIds[projectId]);
        });
    }

    function postBulkProjectAction(action) {
        var projectIds = getSelectedProjectIds();
        var payload = new FormData();
        payload.append('project_action', action);
        payload.append('oid', root.getAttribute('data-omo-projects-oid') || '0');
        payload.append('cid', String(routeCid));
        projectIds.forEach(function (projectId) {
            payload.append('project_ids[]', String(projectId));
        });
        return fetch(resolveUrl(actionUrl), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: payload
        }).then(function (response) {
            return response.text().then(function (text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (error) {
                    data = null;
                }
                if (!response.ok || !data || !data.success) {
                    throw new Error(data && data.message ? data.message : texts.actionError);
                }
                return data;
            });
        });
    }

    function formatText(template, values) {
        return String(template || '').replace(/\{([a-z_]+)\}/gi, function (match, key) {
            return values && values[key] !== undefined ? String(values[key]) : match;
        });
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function closeProjectMenus(exceptMenu) {
        root.querySelectorAll('[data-omo-project-menu], [data-omo-projects-header-menu], [data-omo-project-detail-document-menu]').forEach(function (menu) {
            if (menu === exceptMenu) {
                return;
            }
            var panel = menu.querySelector('[data-omo-project-menu-panel], [data-omo-projects-header-menu-panel], [data-omo-project-detail-document-menu-panel]');
            var toggle = menu.querySelector('[data-omo-project-menu-toggle], [data-omo-projects-header-menu-toggle], [data-omo-project-detail-document-menu-toggle]');
            if (panel) {
                panel.hidden = true;
            }
            menu.classList.remove('is-open');
            var card = menu.closest('[data-omo-project-card]');
            if (card) {
                card.classList.remove('is-menu-open');
            }
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function openArchivesModal() {
        if (typeof window.commonTopbarOpenModal !== 'function') {
            window.omoNotify(texts.actionError, 'error');
            return;
        }
        window.commonTopbarOpenModal(
            texts.archivesTitle || 'Archives des projets',
            buildArchivesUrl(),
            'fetch'
        );
    }

    function openMoveDialog(card) {
        if (!card || typeof window.commonTopbarOpenModal !== 'function' || typeof window.omoMountHolonScopePicker !== 'function') {
            window.omoNotify(texts.actionError, 'error');
            return;
        }

        var projectId = Number(card.getAttribute('data-project-id') || 0);
        var currentHolonId = Number(card.getAttribute('data-project-holon-id') || 0);
        var organizationId = Number(root.getAttribute('data-omo-projects-oid') || 0);
        var html = '<div class="omo-project-move-dialog">'
            + '<p class="omo-project-move-dialog__hint generic-help-text">' + escapeHtml(texts.moveHint) + '</p>'
            + '<div data-omo-project-move-picker></div>'
            + '<p class="omo-project-move-dialog__error" data-omo-project-move-error hidden></p>'
            + '<div class="omo-project-move-dialog__actions">'
            + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-project-move-cancel>' + escapeHtml(texts.cancel) + '</button>'
            + '<button type="button" class="generic-action-button generic-action-button--main" data-omo-project-move-submit disabled>' + escapeHtml(texts.moveSubmit) + '</button>'
            + '</div></div>';

        window.commonTopbarOpenModal(texts.moveTitle, html, 'html');
        window.setTimeout(function () {
            var modal = document.getElementById('commonTopbarModalBody');
            var dialog = modal ? modal.querySelector('.omo-project-move-dialog') : null;
            if (!dialog) {
                return;
            }
            var submit = dialog.querySelector('[data-omo-project-move-submit]');
            var error = dialog.querySelector('[data-omo-project-move-error]');
            var selectedHolonId = 0;
            window.omoMountHolonScopePicker({
                host: dialog.querySelector('[data-omo-project-move-picker]'),
                organizationId: organizationId,
                initialHolonId: currentHolonId,
                showModes: false,
                onChange: function (holonId) {
                    selectedHolonId = Number(holonId || 0);
                    if (submit) {
                        submit.disabled = selectedHolonId <= 0 || selectedHolonId === currentHolonId;
                    }
                }
            });
            dialog.addEventListener('click', function (event) {
                if (event.target.closest('[data-omo-project-move-cancel]')) {
                    window.commonTopbarCloseModal();
                    return;
                }
                if (!event.target.closest('[data-omo-project-move-submit]')) {
                    return;
                }
                if (selectedHolonId <= 0 || selectedHolonId === currentHolonId) {
                    if (error) {
                        error.textContent = texts.moveSelectRequired;
                        error.hidden = false;
                    }
                    return;
                }
                submit.disabled = true;
                postProjectAction(projectId, 'move_project', {IDholon: selectedHolonId}).then(function () {
                    window.commonTopbarCloseModal();
                    refreshRoot(currentUrl);
                }).catch(function (actionError) {
                    submit.disabled = false;
                    if (error) {
                        error.textContent = actionError.message || texts.actionError;
                        error.hidden = false;
                    }
                });
            });
        }, 0);
    }

    function openAttachSubprojectDialog(detail) {
        if (!detail || typeof window.commonTopbarOpenModal !== 'function' || typeof window.omoMountHolonScopePicker !== 'function') {
            window.omoNotify(texts.actionError, 'error');
            return;
        }

        var parentId = Number(detail.getAttribute('data-omo-project-detail') || 0);
        var parentHolonId = Number(detail.getAttribute('data-omo-project-holon-id') || 0);
        var organizationId = Number(root.getAttribute('data-omo-projects-oid') || 0);
        var candidates = [];
        try {
            candidates = JSON.parse(detail.getAttribute('data-omo-project-attach-candidates') || '[]');
        } catch (error) {
            candidates = [];
        }
        if (parentId <= 0 || !Array.isArray(candidates) || candidates.length === 0) {
            return;
        }

        var html = '<div class="omo-project-attach-dialog omo-resource-picker">'
            + '<aside class="omo-resource-picker__navigation" data-omo-project-attach-scope></aside>'
            + '<div class="omo-resource-picker__content">'
            + '<p class="omo-project-move-dialog__hint generic-help-text">' + escapeHtml(texts.attachHint) + '</p>'
            + '<label class="omo-resource-picker__quick-search"><input class="generic-form-control" type="search" data-omo-project-attach-search aria-label="' + escapeHtml(texts.attachSearch) + '" placeholder="' + escapeHtml(texts.attachSearch) + '"></label>'
            + '<select class="generic-form-control omo-project-parent-picker__select" size="10" data-omo-project-attach-select></select>'
            + '<p class="omo-project-parent-picker__empty generic-description generic-description--small" data-omo-project-attach-empty hidden></p>'
            + '<p class="omo-project-move-dialog__error" data-omo-project-attach-error hidden></p>'
            + '<div class="omo-project-parent-picker__actions">'
            + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-project-attach-cancel>' + escapeHtml(texts.cancel) + '</button>'
            + '<button type="button" class="generic-action-button generic-action-button--main" data-omo-project-attach-submit disabled>' + escapeHtml(texts.attachSubmit) + '</button>'
            + '</div></div></div>';

        window.commonTopbarOpenModal(texts.attachTitle, html, 'html');
        window.setTimeout(function () {
            var modal = document.getElementById('commonTopbarModalBody');
            var dialog = modal ? modal.querySelector('.omo-project-attach-dialog') : null;
            if (!dialog) {
                return;
            }
            var search = dialog.querySelector('[data-omo-project-attach-search]');
            var select = dialog.querySelector('[data-omo-project-attach-select]');
            var empty = dialog.querySelector('[data-omo-project-attach-empty]');
            var error = dialog.querySelector('[data-omo-project-attach-error]');
            var submit = dialog.querySelector('[data-omo-project-attach-submit]');
            var selectedId = 0;
            var scopePicker = null;

            function updateSubmit() {
                if (submit) {
                    submit.disabled = selectedId <= 0;
                }
            }

            function render() {
                if (!select || !empty) {
                    return;
                }
                var query = String(search && search.value || '').trim().toLowerCase();
                var selectedHolonId = scopePicker && typeof scopePicker.getSelectedHolonId === 'function'
                    ? Number(scopePicker.getSelectedHolonId() || 0)
                    : 0;
                var matches = candidates.filter(function (candidate) {
                    var candidateHolonId = Number(candidate && candidate.holonId || 0);
                    var matchesScope = !scopePicker
                        || candidateHolonId <= 0
                        || scopePicker.matches(candidateHolonId)
                        || (selectedHolonId > 0 && candidateHolonId === selectedHolonId);
                    var candidateText = [candidate && candidate.title, candidate && candidate.context].join(' ').toLowerCase();
                    return matchesScope && (query === '' || candidateText.indexOf(query) !== -1);
                });
                select.innerHTML = '<option value="0">' + escapeHtml(texts.attachSelectRequired) + '</option>';
                matches.forEach(function (candidate) {
                    var option = document.createElement('option');
                    option.value = String(candidate.id || 0);
                    option.textContent = String(candidate.title || '') + (candidate.context ? ' - ' + String(candidate.context) : '');
                    select.appendChild(option);
                });
                select.value = String(selectedId);
                if (select.value !== String(selectedId)) {
                    selectedId = 0;
                    select.value = '0';
                }
                empty.textContent = texts.attachEmpty;
                empty.hidden = matches.length > 0;
                updateSubmit();
            }

            scopePicker = window.omoMountHolonScopePicker({
                host: dialog.querySelector('[data-omo-project-attach-scope]'),
                organizationId: organizationId,
                initialHolonId: parentHolonId,
                onChange: render
            });
            if (search) {
                search.addEventListener('input', render);
                search.focus();
            }
            if (select) {
                select.addEventListener('change', function () {
                    selectedId = Number(select.value || 0);
                    if (error) {
                        error.hidden = true;
                    }
                    updateSubmit();
                });
            }
            dialog.addEventListener('click', function (event) {
                if (event.target.closest('[data-omo-project-attach-cancel]')) {
                    window.commonTopbarCloseModal();
                    return;
                }
                if (!event.target.closest('[data-omo-project-attach-submit]')) {
                    return;
                }
                if (selectedId <= 0) {
                    if (error) {
                        error.textContent = texts.attachSelectRequired;
                        error.hidden = false;
                    }
                    return;
                }
                submit.disabled = true;
                postProjectAction(parentId, 'attach_subproject', {child_id: selectedId}).then(function () {
                    rootNeedsRefresh = true;
                    window.commonTopbarCloseModal();
                    return currentDrawerUrl ? openDrawerWithUrl(currentDrawerUrl) : null;
                }).catch(function (actionError) {
                    submit.disabled = false;
                    if (error) {
                        error.textContent = actionError.message || texts.actionError;
                        error.hidden = false;
                    }
                });
            });
            render();
        }, 0);
    }

    function runProjectContextAction(action, card) {
        if (!card) {
            return;
        }
        var projectId = Number(card.getAttribute('data-project-id') || 0);
        var subprojectCount = Number(card.getAttribute('data-project-subproject-count') || 0);
        var status = card.getAttribute('data-project-status') || '';
        if (action === 'edit' && projectId > 0) {
            navigateProject(projectId, 'edit', buildEditUrl(projectId));
            return;
        }
        if (action === 'move') {
            openMoveDialog(card);
            return;
        }
        if (action === 'delete' && !window.confirm(formatText(texts.deleteConfirm, {count: subprojectCount}))) {
            return;
        }
        if (action === 'archive' && status !== 'done' && !window.confirm(texts.archiveConfirm)) {
            return;
        }
        var actionName = action === 'delete' ? 'delete_project' : 'archive_project';
        postProjectAction(projectId, actionName).then(function () {
            refreshRoot(currentUrl);
        }).catch(function (actionError) {
            window.omoNotify(actionError.message || texts.actionError, 'error');
        });
    }

    root.querySelectorAll('[data-omo-projects-filter-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (filterPanelIsOpen) {
                closeProjectFilterPanel(true);
            } else {
                openProjectFilterPanel();
            }
        });
    });

    if (filterPanel) {
        filterPanel.addEventListener('click', function (event) {
            var moreToggle = event.target.closest('[data-omo-projects-filter-more-toggle]');
            if (moreToggle) {
                event.preventDefault();
                event.stopPropagation();
                var moreMenu = moreToggle.closest('[data-omo-projects-filter-more-menu]');
                var morePanel = moreMenu ? moreMenu.querySelector('[data-omo-projects-filter-more-panel]') : null;
                var isMoreMenuOpen = !!morePanel && !morePanel.hidden;
                closeProjectFilterMoreMenu();
                if (!isMoreMenuOpen && morePanel) {
                    morePanel.hidden = false;
                    moreMenu.classList.add('is-open');
                    moreToggle.setAttribute('aria-expanded', 'true');
                }
                return;
            }
            var moreAction = event.target.closest('[data-omo-projects-filter-more-action]');
            if (moreAction) {
                event.preventDefault();
                event.stopPropagation();
                applyProjectFilterMoreAction(moreAction.getAttribute('data-omo-projects-filter-more-action') || '');
                return;
            }
            var applyButton = event.target.closest('[data-omo-projects-filter-apply]');
            if (applyButton) {
                closeProjectFilterPanel(true, false);
                return;
            }
            var saveButton = event.target.closest('[data-omo-projects-filter-save]');
            if (saveButton) {
                closeProjectFilterPanel(true, true);
                return;
            }
            var button = event.target.closest('[data-omo-projects-scope], [data-omo-projects-assignment], [data-omo-projects-sort], [data-omo-projects-view]');
            if (!button || !pendingDisplayFilters) {
                return;
            }
            if (button.hasAttribute('data-omo-projects-scope')) {
                pendingDisplayFilters.scope = button.getAttribute('data-omo-projects-scope') || 'contextual';
            } else if (button.hasAttribute('data-omo-projects-assignment')) {
                pendingDisplayFilters.assignment = button.getAttribute('data-omo-projects-assignment') === 'mine' ? 'mine' : 'all';
            } else if (button.hasAttribute('data-omo-projects-sort')) {
                pendingDisplayFilters.sort = button.getAttribute('data-omo-projects-sort') || 'importance';
            } else if (button.hasAttribute('data-omo-projects-view')) {
                var selectedView = button.getAttribute('data-omo-projects-view');
                pendingDisplayFilters.view = selectedView === 'list' || selectedView === 'gantt' ? selectedView : 'kanban';
            }
            syncFilterPanelChoices();
        });
    }

    if (quickSearchInput) {
        var handleQuickSearchInput = function () {
            currentQuickSearch = quickSearchInput.value || '';
            currentUrl = buildProjectsUrl(
                root.getAttribute('data-omo-projects-scope') || 'contextual',
                currentView,
                currentListSort,
                currentAssignment,
                currentQuickSearch
            );
            applyQuickSearch();
        };
        quickSearchInput.addEventListener('input', handleQuickSearchInput);
        quickSearchInput.addEventListener('search', handleQuickSearchInput);
    }

    root.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && filterPanelIsOpen) {
            event.preventDefault();
            closeProjectFilterPanel(false);
        }
    });

    var previousColumnButton = root.querySelector('[data-omo-projects-column-prev]');
    var nextColumnButton = root.querySelector('[data-omo-projects-column-next]');
    if (previousColumnButton) {
        previousColumnButton.addEventListener('click', function () {
            mobileColumnIndex -= 1;
            updateMobileColumn();
        });
    }
    if (nextColumnButton) {
        nextColumnButton.addEventListener('click', function () {
            mobileColumnIndex += 1;
            updateMobileColumn();
        });
    }

    root.querySelectorAll('[data-omo-projects-open-create]').forEach(function (button) {
        button.addEventListener('click', function () {
            pendingCreateStatus = '';
            pendingCreateUrl = '';
            navigateProject(0, 'create', buildCreateUrl(''));
        });
    });

    root.querySelectorAll('[data-omo-projects-column], [data-omo-projects-kanban-cell]').forEach(function (column) {
        column.addEventListener('dblclick', function (event) {
            if (event.target.closest('[data-omo-project-card], button, select, input, textarea, a, [contenteditable="true"]')) {
                return;
            }

            pendingCreateStatus = column.getAttribute('data-omo-projects-column') || column.getAttribute('data-status') || '';
            pendingCreateUrl = '';
            navigateProject(0, 'create', buildCreateUrl(pendingCreateStatus));
        });
    });

    root.addEventListener('click', function (event) {
        var documentsTab = event.target.closest('[data-omo-project-detail-documents-tab]');
        if (documentsTab) {
            loadProjectDocuments(documentsTab);
            return;
        }

        var eventsTab = event.target.closest('[data-omo-project-detail-events-tab]');
        if (eventsTab) {
            loadProjectEvents(eventsTab);
            return;
        }

        var addDocumentButton = event.target.closest('[data-omo-project-detail-add-document]');
        if (addDocumentButton) {
            event.preventDefault();
            event.stopPropagation();
            openProjectDocumentPicker(addDocumentButton.getAttribute('data-omo-project-detail-add-document-url') || '');
            return;
        }

        var addEventButton = event.target.closest('[data-omo-project-detail-add-event]');
        if (addEventButton) {
            event.preventDefault();
            event.stopPropagation();
            openProjectDocumentDrawer(addEventButton.getAttribute('data-omo-project-detail-add-event-url') || '');
            return;
        }

        var archivesLink = event.target.closest('[data-omo-project-detail-archives-link]');
        if (archivesLink) {
            event.preventDefault();
            event.stopPropagation();
            if (typeof window.commonTopbarOpenModal === 'function') {
                window.commonTopbarOpenModal(
                    texts.archivesTitle || 'Projets archives',
                    archivesLink.getAttribute('data-omo-project-detail-archives-url') || '',
                    'fetch'
                );
            }
            return;
        }

        var documentMenuToggle = event.target.closest('[data-omo-project-detail-document-menu-toggle]');
        if (documentMenuToggle) {
            event.preventDefault();
            event.stopPropagation();
            var documentMenu = documentMenuToggle.closest('[data-omo-project-detail-document-menu]');
            var documentMenuPanel = documentMenu ? documentMenu.querySelector('[data-omo-project-detail-document-menu-panel]') : null;
            var isDocumentMenuOpen = !!documentMenuPanel && !documentMenuPanel.hidden;
            closeProjectMenus(documentMenu);
            if (documentMenuPanel) {
                documentMenuPanel.hidden = isDocumentMenuOpen;
            }
            if (documentMenu) {
                documentMenu.classList.toggle('is-open', !isDocumentMenuOpen);
            }
            documentMenuToggle.setAttribute('aria-expanded', isDocumentMenuOpen ? 'false' : 'true');
            return;
        }

        var removeDocumentButton = event.target.closest('[data-omo-project-detail-document-remove]');
        if (removeDocumentButton) {
            event.preventDefault();
            event.stopPropagation();
            var removeProjectId = Number(removeDocumentButton.getAttribute('data-project-id') || 0);
            var removeDocumentId = Number(removeDocumentButton.getAttribute('data-document-id') || 0);
            var removeConfirm = removeDocumentButton.getAttribute('data-confirm') || '';
            if (!Number.isInteger(removeProjectId) || removeProjectId <= 0 || !Number.isInteger(removeDocumentId) || removeDocumentId <= 0) {
                return;
            }
            if (removeConfirm !== '' && !window.confirm(removeConfirm)) {
                return;
            }
            removeDocumentButton.disabled = true;
            closeProjectMenus();
            postProjectAction(removeProjectId, 'remove_document', {document_id: removeDocumentId}).then(function () {
                window.dispatchEvent(new CustomEvent('omo-project-document-saved', {
                    detail: {projectId: removeProjectId, documentId: removeDocumentId}
                }));
            }).catch(function (error) {
                if (typeof window.omoNotify === 'function') {
                    window.omoNotify(error.message || texts.documentsRemoveError || texts.actionError, 'error');
                }
                removeDocumentButton.disabled = false;
            });
            return;
        }

        var documentLink = event.target.closest('[data-omo-project-detail-document-link]');
        if (documentLink) {
            event.preventDefault();
            event.stopPropagation();
            var documentId = Number(documentLink.getAttribute('data-document-id') || 0);
            if (documentId > 0 && useLocalDrawerNavigation) {
                openProjectDocumentDrawer('/omo/api/documents/detail.php?oid=' + encodeURIComponent(String(root.getAttribute('data-omo-projects-oid') || ''))
                    + '&cid=' + encodeURIComponent(String(routeCid || ''))
                    + '&id=' + encodeURIComponent(String(documentId)));
            } else if (documentId > 0 && typeof window.omoOpenDrawerHashState === 'function') {
                window.omoOpenDrawerHashState('documents-d' + documentId);
            } else if (documentId > 0) {
                window.location.hash = 'documents-d' + documentId;
            }
            return;
        }

        var eventLink = event.target.closest('[data-omo-project-detail-event-link]');
        if (eventLink) {
            event.preventDefault();
            event.stopPropagation();
            var calendarEventId = Number(eventLink.getAttribute('data-event-id') || 0);
            if (calendarEventId > 0 && useLocalDrawerNavigation) {
                openProjectDocumentDrawer('/omo/api/calendar/detail.php?oid=' + encodeURIComponent(String(root.getAttribute('data-omo-projects-oid') || ''))
                    + '&cid=' + encodeURIComponent(String(routeCid || ''))
                    + '&id=' + encodeURIComponent(String(calendarEventId)));
            } else if (calendarEventId > 0 && typeof window.omoOpenDrawerHashState === 'function') {
                window.omoOpenDrawerHashState('calendar-e' + String(calendarEventId));
            } else if (calendarEventId > 0) {
                window.location.hash = 'calendar-e' + String(calendarEventId);
            }
            return;
        }

        var headerMenuToggle = event.target.closest('[data-omo-projects-header-menu-toggle]');
        if (headerMenuToggle) {
            event.preventDefault();
            event.stopPropagation();
            var headerMenu = headerMenuToggle.closest('[data-omo-projects-header-menu]');
            var headerMenuPanel = headerMenu ? headerMenu.querySelector('[data-omo-projects-header-menu-panel]') : null;
            var isHeaderMenuOpen = !!headerMenuPanel && !headerMenuPanel.hidden;
            closeProjectMenus(headerMenu);
            if (headerMenuPanel) {
                headerMenuPanel.hidden = isHeaderMenuOpen;
            }
            if (headerMenu) {
                headerMenu.classList.toggle('is-open', !isHeaderMenuOpen);
            }
            headerMenuToggle.setAttribute('aria-expanded', isHeaderMenuOpen ? 'false' : 'true');
            return;
        }

        var archivesAction = event.target.closest('[data-omo-projects-view-archives]');
        if (archivesAction) {
            event.preventDefault();
            event.stopPropagation();
            closeProjectMenus();
            openArchivesModal();
            return;
        }

        var menuToggle = event.target.closest('[data-omo-project-menu-toggle]');
        if (menuToggle) {
            event.preventDefault();
            event.stopPropagation();
            var menu = menuToggle.closest('[data-omo-project-menu]');
            var menuPanel = menu ? menu.querySelector('[data-omo-project-menu-panel]') : null;
            var isOpen = !!menuPanel && !menuPanel.hidden;
            closeProjectMenus(menu);
            if (menuPanel) {
                menuPanel.hidden = isOpen;
            }
            if (menu) {
                menu.classList.toggle('is-open', !isOpen);
                var menuCard = menu.closest('[data-omo-project-card]');
                if (menuCard) {
                    menuCard.classList.toggle('is-menu-open', !isOpen);
                }
            }
            menuToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            return;
        }

        var actionButton = event.target.closest('[data-omo-project-action]');
        if (actionButton) {
            event.preventDefault();
            event.stopPropagation();
            var actionCard = actionButton.closest('[data-omo-project-card]');
            closeProjectMenus();
            runProjectContextAction(actionButton.getAttribute('data-omo-project-action') || '', actionCard);
            return;
        }

        closeProjectMenus();
        var closeButton = event.target.closest('[data-omo-projects-drawer-close], [data-omo-projects-cancel-create]');
        if (closeButton) {
            event.preventDefault();
            if (isProjectDrawerRoute(getCurrentRouteToken()) && typeof window.omoOpenDrawerHashState === 'function') {
                window.omoOpenDrawerHashState('projects');
                return;
            }
            closeDrawer();
            return;
        }

        var documentCloseButton = event.target.closest('[data-omo-projects-document-drawer-close]');
        if (documentCloseButton) {
            event.preventDefault();
            closeProjectDocumentDrawer();
            return;
        }

        var editButton = event.target.closest('[data-omo-projects-open-edit-url]');
        if (editButton) {
            event.preventDefault();
            event.stopPropagation();
            var editProjectId = Number(editButton.getAttribute('data-omo-projects-edit-project-id') || 0);
            navigateProject(editProjectId, 'edit', editButton.getAttribute('data-omo-projects-open-edit-url') || '');
            return;
        }

        var breadcrumb = event.target.closest('[data-omo-project-breadcrumb]');
        if (breadcrumb) {
            event.preventDefault();
            var breadcrumbProjectId = Number(breadcrumb.getAttribute('data-project-id') || 0);
            if (breadcrumbProjectId > 0) {
                navigateProject(breadcrumbProjectId, 'detail', buildDetailUrl(breadcrumbProjectId));
            }
            return;
        }

        var expandBreadcrumb = event.target.closest('[data-omo-project-breadcrumb-expand]');
        if (expandBreadcrumb) {
            event.preventDefault();
            var breadcrumbNavigation = expandBreadcrumb.closest('.omo-project-detail__breadcrumb');
            var extraBreadcrumbs = breadcrumbNavigation ? breadcrumbNavigation.querySelector('[data-omo-project-breadcrumb-extra]') : null;
            if (extraBreadcrumbs) {
                extraBreadcrumbs.hidden = false;
                expandBreadcrumb.setAttribute('aria-expanded', 'true');
                var collapsedBreadcrumb = breadcrumbNavigation.querySelector('[data-omo-project-breadcrumb-collapse]');
                if (collapsedBreadcrumb) collapsedBreadcrumb.hidden = true;
            }
            return;
        }

        var newSubprojectButton = event.target.closest('[data-omo-project-detail-new-subproject-url]');
        if (newSubprojectButton) {
            event.preventDefault();
            event.stopPropagation();
            pendingCreateStatus = '';
            pendingCreateUrl = newSubprojectButton.getAttribute('data-omo-project-detail-new-subproject-url') || '';
            navigateProject(0, 'create', pendingCreateUrl);
            return;
        }

        var attachSubprojectButton = event.target.closest('[data-omo-project-detail-attach-subproject]');
        if (attachSubprojectButton) {
            event.preventDefault();
            event.stopPropagation();
            openAttachSubprojectDialog(attachSubprojectButton.closest('[data-omo-project-detail]'));
            return;
        }

        var subproject = event.target.closest('[data-omo-project-subproject]');
        if (subproject && !event.target.closest('select, option, button, a')) {
            event.preventDefault();
            var subprojectId = Number(subproject.getAttribute('data-project-id') || 0);
            if (subprojectId > 0) {
                navigateProject(subprojectId, 'detail', buildDetailUrl(subprojectId));
            }
            return;
        }

        var ganttRow = event.target.closest('[data-omo-project-gantt-item]');
        var ganttTimeline = event.target.closest('[data-omo-project-gantt-timeline]');
        var ganttBar = event.target.closest('[data-omo-project-gantt-bar]');
        if (ganttRow && ganttTimeline && !ganttBar && scrollGanttBarIntoView(ganttRow)) {
            event.preventDefault();
            return;
        }

        var listItem = event.target.closest('[data-omo-project-list-item], [data-omo-project-gantt-item]');
        if (listItem && !event.target.closest('select, option, button, input, a')) {
            event.preventDefault();
            var listProjectId = Number(listItem.getAttribute('data-project-id') || 0);
            if (listProjectId > 0) {
                navigateProject(listProjectId, 'detail', buildDetailUrl(listProjectId));
            }
            return;
        }

        var card = event.target.closest('[data-omo-project-card]');
        if (!card || event.target.closest('select, option, button, input, a')) {
            return;
        }
        var projectId = Number(card.getAttribute('data-project-id') || 0);
        if (projectId > 0) {
            navigateProject(projectId, 'detail', buildDetailUrl(projectId));
        }
    });

    root.addEventListener('click', function (event) {
        var bulkActionButton = event.target.closest('[data-omo-projects-bulk-action]');
        if (!bulkActionButton) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        var selectedCount = getSelectedProjectIds().length;
        if (selectedCount <= 0) {
            return;
        }
        var action = bulkActionButton.getAttribute('data-omo-projects-bulk-action') || '';
        var isDelete = action === 'delete';
        if (isDelete && !selectedProjectsCanBeDeleted(getSelectedProjectIds())) {
            return;
        }
        var confirmation = isDelete ? texts.deleteSelectedConfirm : texts.archiveSelectedConfirm;
        if (!window.confirm(formatText(confirmation || '', {count: selectedCount}))) {
            return;
        }
        bulkActionButton.disabled = true;
        postBulkProjectAction(isDelete ? 'bulk_delete_projects' : 'bulk_archive_projects').then(function () {
            selectedProjectIds = {};
            return refreshRoot(currentUrl, {preserveScroll: true});
        }).catch(function (error) {
            window.omoNotify(error.message || texts.actionError, 'error');
        }).finally(function () {
            bulkActionButton.disabled = false;
        });
    });

    root.addEventListener('change', function (event) {
        var selectionInput = event.target.closest('[data-omo-project-select]');
        if (!selectionInput) {
            return;
        }
        event.stopPropagation();
        var projectId = Number(selectionInput.value || 0);
        if (!Number.isInteger(projectId) || projectId <= 0) {
            return;
        }
        if (selectionInput.checked) {
            selectedProjectIds[projectId] = true;
        } else {
            delete selectedProjectIds[projectId];
        }
        syncProjectSelection();
    });

    root.addEventListener('pointerover', function (event) {
        var projectNode = event.target.closest('[data-omo-project-card], [data-omo-project-list-item], [data-omo-project-gantt-item]');
        if (projectNode && !projectNode.contains(event.relatedTarget)) {
            highlightProjectChildren(projectNode);
        }
    });

    root.addEventListener('pointerout', function (event) {
        var projectNode = event.target.closest('[data-omo-project-card], [data-omo-project-list-item], [data-omo-project-gantt-item]');
        if (projectNode && !projectNode.contains(event.relatedTarget)) {
            clearRelatedProjectHighlights();
        }
    });

    root.addEventListener('focusin', function (event) {
        var projectNode = event.target.closest('[data-omo-project-card], [data-omo-project-list-item], [data-omo-project-gantt-item]');
        if (projectNode) {
            highlightProjectChildren(projectNode);
        }
    });

    root.addEventListener('focusout', function (event) {
        if (!root.contains(event.relatedTarget)) {
            clearRelatedProjectHighlights();
        }
    });

    root.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }
        var card = event.target.closest('[data-omo-project-card]');
        if (card && event.target === card) {
            event.preventDefault();
            card.click();
            return;
        }

        var subproject = event.target.closest('[data-omo-project-subproject]');
        if (subproject && event.target === subproject) {
            event.preventDefault();
            subproject.click();
            return;
        }

        var listItem = event.target.closest('[data-omo-project-list-item], [data-omo-project-gantt-item]');
        if (listItem && event.target === listItem) {
            event.preventDefault();
            listItem.click();
        }
    });

    root.addEventListener('change', function (event) {
        var select = event.target.closest('[data-omo-project-detail-status-select]');
        if (!select) {
            return;
        }
        event.stopPropagation();
        var item = select.closest('[data-omo-project-subproject]');
        var projectId = select.getAttribute('data-project-id') || (item ? item.getAttribute('data-project-id') : '0');
        var previousStatus = select.getAttribute('data-previous-status') || select.value;
        var selectedAction = select.value;
        if (selectedAction === '__archive__' || selectedAction === '__delete__') {
            var isDelete = selectedAction === '__delete__';
            if (isDelete && !window.confirm(texts.taskDeleteConfirm || 'Supprimer définitivement cette tâche ?')) {
                select.value = previousStatus;
                return;
            }
            var projectAction = isDelete ? 'delete_project' : 'archive_project';
            var projectStatus = item ? item.className : '';
            if (!isDelete && projectStatus.indexOf('--done') === -1 && !window.confirm(texts.archiveConfirm)) {
                select.value = previousStatus;
                return;
            }
            select.disabled = true;
            postProjectAction(projectId, projectAction).then(function () {
                if (item) {
                    item.remove();
                }
            }).catch(function (error) {
                select.value = previousStatus;
                select.disabled = false;
                window.omoNotify(error.message || texts.actionError, 'error');
            });
            return;
        }
        select.disabled = true;
        postStatus(projectId, select.value).then(function () {
            if (item) {
                columns.forEach(function (status) {
                    item.classList.remove('omo-project-detail__subproject-item--' + status);
                });
                item.classList.add('omo-project-detail__subproject-item--' + select.value);
            }
            select.setAttribute('data-previous-status', select.value);
            select.disabled = false;
            rootNeedsRefresh = true;
            if (currentDrawerUrl) {
                return openDrawerWithUrl(currentDrawerUrl);
            }
        }).catch(function (error) {
            select.value = previousStatus;
            select.disabled = false;
            window.omoNotify(error.message || texts.statusUpdateError, 'error');
        });
    });

    root.querySelectorAll('[data-omo-project-status-select]').forEach(function (select) {
        select.addEventListener('click', function (event) {
            event.stopPropagation();
        });
        select.addEventListener('change', function () {
            var card = select.closest('[data-omo-project-card]');
            moveCard(card, select.value);
        });
    });

    root.querySelectorAll('[data-omo-project-card][draggable="true"]').forEach(function (card) {
        card.addEventListener('dragstart', function (event) {
            draggedProjectCard = card;
            card.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.getAttribute('data-project-id') || '');
        });
        card.addEventListener('dragend', function () {
            draggedProjectCard = null;
            card.classList.remove('dragging');
            root.querySelectorAll('[data-omo-projects-cards].is-drag-over, [data-omo-projects-cards].is-drop-forbidden').forEach(function (container) {
                container.classList.remove('is-drag-over');
                container.classList.remove('is-drop-forbidden');
            });
        });
    });

    root.querySelectorAll('[data-omo-projects-cards]').forEach(function (container) {
        container.addEventListener('dragover', function (event) {
            var mutation = getKanbanDropMutation(draggedProjectCard, container);
            if (!mutation.allowed) {
                if (event.dataTransfer) {
                    event.dataTransfer.dropEffect = 'none';
                }
                container.classList.remove('is-drag-over');
                container.classList.add('is-drop-forbidden');
                return;
            }
            event.preventDefault();
            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'move';
            }
            container.classList.remove('is-drop-forbidden');
            container.classList.add('is-drag-over');
        });
        container.addEventListener('dragleave', function () {
            container.classList.remove('is-drag-over');
            container.classList.remove('is-drop-forbidden');
        });
        container.addEventListener('drop', function (event) {
            event.preventDefault();
            container.classList.remove('is-drag-over');
            container.classList.remove('is-drop-forbidden');
            var projectId = event.dataTransfer.getData('text/plain');
            var card = root.querySelector('[data-omo-project-card][data-project-id="' + projectId + '"]');
            moveCard(card, container.getAttribute('data-status') || '', container);
        });
    });

    window.omoProjectsAfterSave = function () {
        var hashState = !useLocalDrawerNavigation && typeof window.omoParsePopupHashState === 'function'
            ? window.omoParsePopupHashState()
            : null;
        var routeToken = hashState && hashState.routeToken ? String(hashState.routeToken) : '';
        var editMatch = routeToken.match(/^projects-e(\d+)$/i);
        return refreshRoot(currentUrl, {preserveScroll: true}).then(function (nextRoot) {
            return waitForRootInitialization(nextRoot);
        }).then(function () {
            if (editMatch && typeof window.omoOpenDrawerHashState === 'function') {
                window.omoOpenDrawerHashState(buildProjectRouteToken(Number(editMatch[1]), 'detail'), {replace: true});
            } else if (routeToken === 'projects-new' && typeof window.omoOpenDrawerHashState === 'function') {
                window.omoOpenDrawerHashState('projects', {replace: true});
            }
        });
    };

    window.omoCloseProjectDocumentEditorDrawer = function () {
        closeProjectDocumentDrawer();
    };

    window.omoCloseProjectEventEditorDrawer = function () {
        closeProjectDocumentDrawer();
    };

    function refreshProjectDocumentsAfterSave(event) {
        var details = event && event.detail ? event.detail : {};
        var projectId = Number(details.projectId || 0);
        if (!Number.isInteger(projectId) || projectId <= 0) {
            return;
        }
        var panel = root.querySelector('#omo-project-detail-documents-' + String(projectId));
        var tab = root.querySelector('[data-omo-project-detail-documents-tab][data-generic-tab-target="omo-project-detail-documents-' + String(projectId) + '"]');
        var content = panel ? panel.querySelector('[data-omo-project-detail-documents-content]') : null;
        if (!panel || !content) {
            return;
        }
        panel.removeAttribute('data-omo-project-detail-documents-loaded');
        panel.removeAttribute('data-omo-project-detail-documents-loading');
        content.innerHTML = '';
        if (!panel.hidden && tab) {
            loadProjectDocuments(tab);
        }
    }

    function refreshProjectEventsAfterSave(event) {
        var details = event && event.detail ? event.detail : {};
        var projectId = Number(details.projectId || 0);
        if (!Number.isInteger(projectId) || projectId <= 0) {
            return;
        }
        var panel = root.querySelector('#omo-project-detail-events-' + String(projectId));
        var tab = root.querySelector('[data-omo-project-detail-events-tab][data-generic-tab-target="omo-project-detail-events-' + String(projectId) + '"]');
        var content = panel ? panel.querySelector('[data-omo-project-detail-events-content]') : null;
        if (!panel || !content) {
            return;
        }
        panel.removeAttribute('data-omo-project-detail-events-loaded');
        panel.removeAttribute('data-omo-project-detail-events-loading');
        content.innerHTML = '';
        if (!panel.hidden && tab) {
            loadProjectEvents(tab);
        }
    }

    function maybeOpenInitialProject() {
        var projectId = initialOpenProjectId;
        var mode = initialOpenProjectMode;
        if (typeof window.omoParsePopupHashState === 'function') {
            var hashState = window.omoParsePopupHashState();
            var routeToken = hashState && hashState.routeToken ? String(hashState.routeToken) : '';
            if (routeToken === 'projects-new') {
                projectId = 0;
                mode = 'create';
            } else {
                var editMatch = routeToken.match(/^projects-e(\d+)$/i);
                var detailMatch = routeToken.match(/^projects-d(\d+)$/i);
                if (editMatch) {
                    projectId = Number(editMatch[1]);
                    mode = 'edit';
                } else if (detailMatch) {
                    projectId = Number(detailMatch[1]);
                    mode = 'detail';
                }
            }
        }

        if (mode === 'create') {
            openDrawerWithUrl(createUrl);
            return;
        }
        if (!Number.isInteger(projectId) || projectId <= 0) {
            return;
        }
        openDrawerWithUrl(mode === 'edit'
            ? buildEditUrl(projectId)
            : buildDetailUrl(projectId));
    }

    window.omoOpenProjectRoute = function (routeToken) {
        if (!root.isConnected) {
            return false;
        }

        var normalizedRouteToken = String(routeToken || '').replace(/^#/, '').trim();
        var mode = '';
        var projectId = 0;
        if (normalizedRouteToken === 'projects-new') {
            mode = 'create';
        } else {
            var editMatch = normalizedRouteToken.match(/^projects-e(\d+)$/i);
            var detailMatch = normalizedRouteToken.match(/^projects-d(\d+)$/i);
            if (editMatch) {
                mode = 'edit';
                projectId = Number(editMatch[1]);
            } else if (detailMatch) {
                mode = 'detail';
                projectId = Number(detailMatch[1]);
            }
        }

        if (mode === '') {
            return false;
        }

        initialOpenProjectId = projectId;
        initialOpenProjectMode = mode;

        var targetUrl = mode === 'create'
            ? buildCreateUrl(pendingCreateStatus)
            : (mode === 'edit' ? buildEditUrl(projectId) : buildDetailUrl(projectId));
        if (!targetUrl) {
            return false;
        }

        if (
            currentDrawerUrl !== ''
            && resolveUrl(currentDrawerUrl) === resolveUrl(targetUrl)
            && drawer
            && !drawer.hidden
        ) {
            return true;
        }

        openDrawerWithUrl(targetUrl);
        return true;
    };

    function handleProjectRouteChange(event) {
        var route = event && event.detail ? event.detail : {};
        var projectId = Number(route.projectId || 0);
        var mode = String(route.mode || '').toLowerCase();
        var routeToken = '';
        if (mode === 'create') {
            routeToken = 'projects-new';
        } else if (projectId > 0 && mode === 'edit') {
            routeToken = 'projects-e' + String(projectId);
        } else if (projectId > 0) {
            routeToken = 'projects-d' + String(projectId);
        }

        if (routeToken !== '' && window.omoOpenProjectRoute(routeToken)) {
            return;
        }
        initialOpenProjectId = 0;
        initialOpenProjectMode = '';
        closeDrawer();
    }

    window.addEventListener('omo-projects-route-change', handleProjectRouteChange);
    window.addEventListener('omo-runtime-maintenance', handleRuntimeMaintenance);
    window.addEventListener('omo-project-document-saved', refreshProjectDocumentsAfterSave);
    window.addEventListener('omo-project-event-saved', refreshProjectEventsAfterSave);
    window.addEventListener('resize', syncGroupedKanbanHeaderOffset);

    applyQuickSearch();
    syncGroupedKanbanHeaderOffset();
    updateMobileColumn();
    scrollGanttToToday();
    var isApplyingStoredDisplayPreferences = applyStoredDisplayPreferences();
    if (!isApplyingStoredDisplayPreferences) {
        revealRoot();
    }
    if (!isApplyingStoredDisplayPreferences) {
        window.setTimeout(maybeOpenInitialProject, 40);
    }
})(window, document);
