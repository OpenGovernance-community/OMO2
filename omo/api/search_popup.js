(function () {
    var root = document.querySelector('[data-omo-search-popup-root="1"]');
    if (!root || root.dataset.omoSearchPopupUiBound === '1') {
        return;
    }

    var searchForm = root.querySelector('[data-omo-search-popup-form]');
    var searchInput = root.querySelector('[data-omo-search-popup-input]');
    var organizationId = Number(root.getAttribute('data-omo-search-popup-oid') || '0');
    var currentHolonId = Number(root.getAttribute('data-omo-search-popup-cid') || '0');
    var previousCleanup = typeof window.__omoPopupCleanup === 'function'
        ? window.__omoPopupCleanup
        : null;

    root.dataset.omoSearchPopupUiBound = '1';

    var previewDrawer = root.querySelector('[data-omo-search-preview-drawer]');
    var previewPanel = previewDrawer.querySelector('[role="dialog"]');
    var previewBody = previewDrawer.querySelector('[data-omo-search-preview-body]');
    var previewTitle = previewDrawer.querySelector('[data-omo-subdrawer-title]');
    var previewAction = previewDrawer.querySelector('[data-omo-search-preview-action]');
    var previewRetry = previewDrawer.querySelector('[data-omo-search-preview-retry]');
    var previewController = window.omoCreateSubdrawerController({ drawer: previewDrawer });
    var previewRequest = null;
    var previewSource = null;
    var previewInertNodes = [];
    // Mount beside the scrolling modal body so the drawer fills the visible panel.
    var previewHost = root.closest('.common-topbar-modal__panel') || root.parentElement;
    previewHost.appendChild(previewDrawer);

    function closePreview(restoreFocus) {
        if (previewRequest) { previewRequest.abort(); previewRequest = null; }
        previewDrawer.classList.remove('is-open');
        previewDrawer.hidden = true;
        previewInertNodes.forEach(function (node) { node.inert = false; });
        previewInertNodes = [];
        if (restoreFocus && previewSource && previewSource.isConnected) {
            previewSource.focus({ preventScroll: true });
        }
    }

    function showPreview(button) {
        if (previewRequest) { previewRequest.abort(); }
        previewSource = button;
        previewTitle.textContent = button.closest('article').querySelector('h4').textContent;
        previewAction.replaceChildren();
        previewRetry.hidden = true;
        previewBody.classList.add('generic-drawer-content');
        previewBody.textContent = root.dataset.omoSearchPreviewLoading;
        previewBody.setAttribute('aria-busy', 'true');
        previewController.open();
        Array.prototype.forEach.call(previewHost.children, function (node) {
            if (node !== previewDrawer && !node.inert) { node.inert = true; previewInertNodes.push(node); }
        });
        previewPanel.focus({ preventScroll: true });
        var controller = new AbortController();
        previewRequest = controller;
        var params = new URLSearchParams({
            oid: organizationId, cid: currentHolonId,
            module: button.dataset.omoSearchPreview,
            id: button.dataset.omoSearchPreviewId,
            q: button.closest('[data-omo-search-result-query]').getAttribute('data-omo-search-result-query'),
            mission_id: button.dataset.omoSearchPreviewMission || '0'
        });
        fetch('/omo/api/search/preview.php?' + params.toString(), {
            credentials: 'same-origin', signal: controller.signal,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            if (!response.ok) { throw new Error('Preview unavailable'); }
            return response.json();
        }).then(function (preview) {
            if (controller.signal.aborted || previewRequest !== controller) { return; }
            previewTitle.textContent = preview.title || previewTitle.textContent;
            if (preview.titleHtml) { previewTitle.innerHTML = preview.titleHtml; }
            previewBody.classList.remove('generic-drawer-content');
            previewBody.innerHTML = preview.html;
            previewBody.removeAttribute('aria-busy');
            var openButton = button.cloneNode(false);
            openButton.removeAttribute('data-omo-search-preview');
            openButton.textContent = root.dataset.omoSearchPreviewOpenLabel;
            previewAction.replaceChildren(openButton);
        }).catch(function () {
            if (controller.signal.aborted || previewRequest !== controller) { return; }
            previewBody.textContent = root.dataset.omoSearchPreviewError;
            previewBody.removeAttribute('aria-busy');
            previewRetry.hidden = false;
        });
    }

    function handlePreviewClick(event) {
        if (event.target.closest('[data-omo-search-preview-close]')) {
            event.preventDefault();
            closePreview(true);
        } else if (event.target.closest('[data-omo-search-preview-retry]')) {
            showPreview(previewSource);
        } else {
            if (previewAction.contains(event.target)) { closePreview(false); }
            handleResultClick(event);
        }
    }

    function handlePreviewKeydown(event) {
        if (previewDrawer.hidden) { return; }
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopImmediatePropagation();
            closePreview(true);
        } else if (event.key === 'Tab') {
            var buttons = Array.prototype.filter.call(previewPanel.querySelectorAll('button'), function (button) {
                return !button.hidden && !button.disabled;
            });
            var first = buttons[0];
            var last = buttons[buttons.length - 1];
            if (event.shiftKey && (document.activeElement === first || document.activeElement === previewPanel)) {
                event.preventDefault(); last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault(); first.focus();
            }
        }
    }

    previewDrawer.addEventListener('click', handlePreviewClick);
    window.addEventListener('keydown', handlePreviewKeydown, true);

    if (typeof window.commonTopbarInitializeSearchPeriod === 'function') {
        window.commonTopbarInitializeSearchPeriod(searchForm);
    }

    function buildPopupUrl(query, scopes) {
        var queryParts = [
            'q=' + encodeURIComponent(String(query || '').trim())
        ];

        if (Number.isInteger(organizationId) && organizationId > 0) {
            queryParts.push('oid=' + encodeURIComponent(organizationId));
        }

        if (Number.isInteger(currentHolonId) && currentHolonId > 0) {
            queryParts.push('cid=' + encodeURIComponent(currentHolonId));
        }

        var startDateInput = root.querySelector('[data-topbar-search-period-start]');
        var endDateInput = root.querySelector('[data-topbar-search-period-end]');
        if (startDateInput && startDateInput.value) {
            queryParts.push('date_start=' + encodeURIComponent(startDateInput.value));
        }
        if (endDateInput && endDateInput.value) {
            queryParts.push('date_end=' + encodeURIComponent(endDateInput.value));
        }

        (Array.isArray(scopes) ? scopes : []).forEach(function (scopeId) {
            var normalizedScopeId = String(scopeId || '').trim();
            if (normalizedScopeId !== '') {
                queryParts.push('scopes[]=' + encodeURIComponent(normalizedScopeId));
            }
        });

        return '/omo/api/search_popup.php?' + queryParts.join('&');
    }

    function getSelectedScopes() {
        if (!searchForm) {
            return [];
        }

        return Array.prototype.map.call(
            searchForm.querySelectorAll('[data-omo-search-popup-scope-input]:checked'),
            function (input) {
                return String(input.value || '').trim();
            }
        ).filter(function (scopeId) {
            return scopeId !== '';
        });
    }

    function relaunchSearch(event) {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }

        if (!searchForm || !searchInput) {
            return;
        }

        var query = String(searchInput.value || '').trim();
        var scopes = getSelectedScopes();
        var startDateInput = root.querySelector('[data-topbar-search-period-start]');
        var endDateInput = root.querySelector('[data-topbar-search-period-end]');
        var dateRange = {
            startDate: startDateInput ? String(startDateInput.value || '') : '',
            endDate: endDateInput ? String(endDateInput.value || '') : ''
        };

        if (typeof window.omoOpenSearchPopupHashState === 'function' && window.omoOpenSearchPopupHashState(query, scopes, dateRange)) {
            return;
        }

        if (typeof window.commonTopbarOpenModal !== 'function') {
            return;
        }

        window.commonTopbarOpenModal(
            'Recherche',
            buildPopupUrl(query, scopes),
            'fetch'
        );
    }

    function applyClientSideModuleFilter(scopeId) {
        var normalizedScopeId = String(scopeId || '').trim();
        var activeScopeId = String(root.getAttribute('data-omo-search-popup-active-filter') || '').trim();
        var nextScopeId = normalizedScopeId !== '' && normalizedScopeId !== activeScopeId ? normalizedScopeId : '';
        var results = root.querySelectorAll('[data-omo-search-popup-result-module]');
        var statButtons = root.querySelectorAll('[data-omo-search-popup-stat-filter]');
        var filterEmpty = root.querySelector('[data-omo-search-popup-filter-empty]');
        var visibleCount = 0;

        Array.prototype.forEach.call(results, function (resultNode) {
            var resultScopeId = String(resultNode.getAttribute('data-omo-search-popup-result-module') || '').trim();
            var isVisible = nextScopeId === '' || resultScopeId === nextScopeId;
            resultNode.classList.toggle('is-filtered-out', !isVisible);
            resultNode.hidden = !isVisible;
            if (isVisible) {
                visibleCount += 1;
            }
        });

        Array.prototype.forEach.call(statButtons, function (button) {
            var buttonScopeId = String(button.getAttribute('data-omo-search-popup-stat-filter') || '').trim();
            var isActive = nextScopeId !== '' && buttonScopeId === nextScopeId;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('data-omo-search-popup-stat-active', isActive ? '1' : '0');
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        if (filterEmpty) {
            filterEmpty.hidden = visibleCount !== 0 || nextScopeId === '';
        }

        if (nextScopeId === '') {
            root.removeAttribute('data-omo-search-popup-active-filter');
        } else {
            root.setAttribute('data-omo-search-popup-active-filter', nextScopeId);
        }
    }

    function handleStatFilterClick(event) {
        var statButton = event.target.closest('[data-omo-search-popup-stat-filter]');
        if (!statButton) {
            return false;
        }

        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }

        var scopeId = String(statButton.getAttribute('data-omo-search-popup-stat-filter') || '').trim();
        if (scopeId === '') {
            return true;
        }

        applyClientSideModuleFilter(scopeId);
        return true;
    }

    function handleResultClick(event) {
        var previewButton = event.target.closest('[data-omo-search-preview]');
        if (previewButton) {
            event.preventDefault();
            showPreview(previewButton);
            return;
        }
        if (handleStatFilterClick(event)) {
            return;
        }

        var structureButton = event.target.closest('[data-omo-search-open-structure]');
        if (structureButton && typeof window.omoOpenSearchStructureResult === 'function') {
            window.omoOpenSearchStructureResult(Number(structureButton.getAttribute('data-omo-search-open-structure') || '0'));
            return;
        }

        var rulesButton = event.target.closest('[data-omo-search-open-rules-holon]');
        if (rulesButton && typeof window.omoOpenSearchRulesResult === 'function') {
            window.omoOpenSearchRulesResult(Number(rulesButton.getAttribute('data-omo-search-open-rules-holon') || '0'));
            return;
        }

        var userButton = event.target.closest('[data-omo-search-open-user]');
        if (userButton && typeof window.omoOpenSearchUserResult === 'function') {
            window.omoOpenSearchUserResult(Number(userButton.getAttribute('data-omo-search-open-user') || '0'));
            return;
        }

        var documentButton = event.target.closest('[data-omo-search-open-document]');
        if (documentButton && typeof window.omoOpenSearchDocumentResult === 'function') {
            window.omoOpenSearchDocumentResult(
                documentButton.getAttribute('data-omo-search-open-document') || '',
                documentButton.getAttribute('data-omo-search-document-title') || 'Document'
            );
            return;
        }

        var calendarEventButton = event.target.closest('[data-omo-search-open-calendar-event-id]');
        if (calendarEventButton && typeof window.omoOpenSearchCalendarEventResult === 'function') {
            window.omoOpenSearchCalendarEventResult(
                Number(calendarEventButton.getAttribute('data-omo-search-open-calendar-event-id') || '0'),
                Number(calendarEventButton.getAttribute('data-omo-search-open-calendar-event-holon') || '0')
            );
            return;
        }

        var decisionButton = event.target.closest('[data-omo-search-open-decision-id]');
        if (decisionButton && typeof window.omoOpenSearchDecisionResult === 'function') {
            window.omoOpenSearchDecisionResult(
                Number(decisionButton.getAttribute('data-omo-search-open-decision-id') || '0'),
                Number(decisionButton.getAttribute('data-omo-search-open-decision-holon') || '0')
            );
            return;
        }

        var projectButton = event.target.closest('[data-omo-search-open-project-id]');
        if (projectButton && typeof window.omoOpenSearchProjectResult === 'function') {
            window.omoOpenSearchProjectResult(
                Number(projectButton.getAttribute('data-omo-search-open-project-id') || '0'),
                Number(projectButton.getAttribute('data-omo-search-open-project-holon') || '0')
            );
            return;
        }

        var indicatorButton = event.target.closest('[data-omo-search-open-stat-indicator-id]');
        if (indicatorButton && typeof window.omoOpenSearchStatIndicatorResult === 'function') {
            window.omoOpenSearchStatIndicatorResult(
                Number(indicatorButton.getAttribute('data-omo-search-open-stat-indicator-id') || '0'),
                Number(indicatorButton.getAttribute('data-omo-search-open-stat-indicator-holon') || '0')
            );
            return;
        }

        var checklistButton = event.target.closest('[data-omo-search-open-checklist-id]');
        if (checklistButton && typeof window.omoOpenSearchChecklistResult === 'function') {
            window.omoOpenSearchChecklistResult(
                Number(checklistButton.getAttribute('data-omo-search-open-checklist-id') || '0'),
                Number(checklistButton.getAttribute('data-omo-search-open-checklist-holon') || '0')
            );
            return;
        }

        var activityButton = event.target.closest('[data-omo-search-open-activity-id]');
        if (activityButton && typeof window.omoOpenSearchActivityResult === 'function') {
            window.omoOpenSearchActivityResult(
                Number(activityButton.getAttribute('data-omo-search-open-activity-id') || '0'),
                Number(activityButton.getAttribute('data-omo-search-open-activity-holon') || '0')
            );
            return;
        }

        var faqButton = event.target.closest('[data-omo-search-open-faq]');
        if (faqButton && typeof window.omoOpenFaqHashState === 'function') {
            window.omoOpenFaqHashState(Number(faqButton.getAttribute('data-omo-search-open-faq') || '0'));
            return;
        }

        var tutorialButton = event.target.closest('[data-omo-search-open-tutorial-parcours]');
        if (tutorialButton && typeof window.omoOpenSearchTutorialResult === 'function') {
            window.omoOpenSearchTutorialResult(
                Number(tutorialButton.getAttribute('data-omo-search-open-tutorial-parcours') || '0'),
                Number(tutorialButton.getAttribute('data-omo-search-open-tutorial-mission') || '0')
            );
        }
    }

    if (searchForm) {
        searchForm.addEventListener('submit', relaunchSearch);
    }

    root.addEventListener('click', handleResultClick);

    window.__omoPopupCleanup = function () {
        closePreview(false);
        previewDrawer.removeEventListener('click', handlePreviewClick);
        window.removeEventListener('keydown', handlePreviewKeydown, true);
        previewDrawer.remove();
        if (searchForm) {
            searchForm.removeEventListener('submit', relaunchSearch);
        }

        root.removeEventListener('click', handleResultClick);
        root.dataset.omoSearchPopupUiBound = '0';

        if (previousCleanup) {
            previousCleanup();
        }
    };
})();
