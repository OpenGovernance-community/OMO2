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
