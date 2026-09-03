(function () {
    'use strict';

    function boot() {
        var config = window.timerConfig || {};
        var pickerHost = document.querySelector('[data-timer-holon-picker]');
        var projectList = document.querySelector('[data-timer-project-list]');
        var projectStatus = document.querySelector('[data-timer-project-status]');
        var swipeSurfaces = document.querySelectorAll('[data-timer-pane-content]');
        var swipeTrack = document.querySelector('[data-timer-swipe-track]');
        var toggleButton = document.querySelector('[data-timer-toggle]');
        var toggleLabel = document.querySelector('[data-timer-toggle-label]');
        var clock = document.querySelector('[data-timer-clock]');
        var status = document.querySelector('[data-timer-status]');
        var statusDot = document.querySelector('[data-timer-status-dot]');
        var feedback = document.querySelector('[data-timer-feedback]');
        var lastSignal = document.querySelector('[data-timer-last-signal]');
        var selectedHolonLabel = document.querySelector('[data-timer-selected-holon]');
        var currentTarget = document.querySelector('[data-timer-current-target]');
        var organizations = Array.isArray(config.organizations) ? config.organizations : [];
        var translations = config.translations || {};
        var activeEntry = config.activeEntry || null;
        var state = {
            organizationId: normalizeId(config.selectedOrganizationId),
            selectedHolonId: activeEntry ? normalizeId(activeEntry.holonId) : 0,
            selectedHolonName: '',
            selectedProjectId: activeEntry ? normalizeId(activeEntry.projectId) : 0,
            selectedProjectName: '',
            projectStatus: projectStatus ? String(projectStatus.value || 'in_progress') : 'in_progress',
            activeEntry: activeEntry,
            activePane: 0,
            picker: null,
            pickerReady: false,
            projects: {},
            requestInProgress: false,
            serverOffsetMs: 0,
            lastSwitchTarget: ''
        };

        function normalizeId(value) {
            var numericValue = Number(value || 0);
            return Number.isInteger(numericValue) && numericValue > 0 ? numericValue : 0;
        }

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>'"]/g, function (character) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    "'": '&#39;',
                    '"': '&quot;'
                }[character];
            });
        }

        function findOrganization(organizationId) {
            organizationId = normalizeId(organizationId);
            return organizations.find(function (organization) {
                return normalizeId(organization.id) === organizationId;
            }) || null;
        }

        function replaceTokens(template, values) {
            return String(template || '').replace(/\{([A-Za-z]+)\}/g, function (placeholder, key) {
                return values[key] || '';
            });
        }

        function showFeedback(message, isSuccess) {
            if (!feedback) {
                return;
            }
            feedback.textContent = String(message || '');
            feedback.classList.toggle('is-success', Boolean(isSuccess));
            feedback.classList.toggle('is-error', !isSuccess && Boolean(message));
        }

        function formatDuration(totalSeconds) {
            var safeSeconds = Math.max(0, Math.floor(Number(totalSeconds) || 0));
            var hours = Math.floor(safeSeconds / 3600);
            var minutes = Math.floor((safeSeconds % 3600) / 60);
            var seconds = safeSeconds % 60;
            return [hours, minutes, seconds].map(function (value) {
                return String(value).padStart(2, '0');
            }).join(':');
        }

        function formatSignalDate(unixTimestamp) {
            var timestamp = Number(unixTimestamp || 0);
            if (!timestamp) {
                return '';
            }
            return new Intl.DateTimeFormat(undefined, {
                dateStyle: 'short',
                timeStyle: 'short'
            }).format(new Date(timestamp * 1000));
        }

        function setServerClock(serverNow) {
            var numericServerNow = Number(serverNow || 0);
            if (numericServerNow > 0) {
                state.serverOffsetMs = (numericServerNow * 1000) - Date.now();
            }
        }

        function getServerNowSeconds() {
            return (Date.now() + state.serverOffsetMs) / 1000;
        }

        function renderClock() {
            if (!clock) {
                return;
            }
            var startedAt = state.activeEntry ? Number(state.activeEntry.startedAtUnix || 0) : 0;
            clock.textContent = formatDuration(startedAt > 0 ? getServerNowSeconds() - startedAt : 0);
        }

        function renderTarget() {
            if (!currentTarget) {
                return;
            }
            var organization = findOrganization(state.organizationId);
            var organizationName = organization && organization.name ? organization.name : '';
            var text = translations.selectionNone || '';
            if (organizationName && state.selectedHolonId > 0 && state.selectedHolonName) {
                text = replaceTokens(state.selectedProjectId > 0 && state.selectedProjectName
                    ? translations.selectionProject
                    : translations.selectionHolon, {
                    organizationName: organizationName,
                    holonName: state.selectedHolonName,
                    projectName: state.selectedProjectName
                });
            } else if (organizationName) {
                text = replaceTokens(translations.selectionOrganization, { organizationName: organizationName });
            }
            currentTarget.textContent = text;
        }

        function renderStatus() {
            var isActive = Boolean(state.activeEntry && state.activeEntry.isOpen !== false);
            if (status) {
                status.textContent = isActive ? translations.active : translations.ready;
            }
            if (statusDot) {
                statusDot.classList.toggle('is-active', isActive);
            }
            if (toggleLabel) {
                toggleLabel.textContent = isActive ? translations.stop : translations.start;
            }
            if (toggleButton) {
                toggleButton.classList.toggle('timer-toggle--stop', isActive);
                toggleButton.setAttribute('aria-label', isActive ? translations.stop : translations.start);
            }
            if (lastSignal) {
                var heartbeat = state.activeEntry ? Number(state.activeEntry.lastHeartbeatAtUnix || 0) : 0;
                lastSignal.textContent = heartbeat
                    ? String(translations.lastSignal || '').replace('{date}', formatSignalDate(heartbeat))
                    : '';
            }
            renderClock();
            renderTarget();
        }

        function updateSelectedLabel() {
            if (selectedHolonLabel) {
                selectedHolonLabel.textContent = state.selectedHolonName
                    || (state.pickerReady ? translations.selectHolon : translations.structureLoading)
                    || '';
            }
        }

        function updateOrganizationButtons() {
            document.querySelectorAll('[data-timer-organization-id]').forEach(function (button) {
                var isSelected = normalizeId(button.getAttribute('data-timer-organization-id')) === state.organizationId;
                button.classList.toggle('is-selected', isSelected);
                button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
            });
        }

        function updateTopbarBrand() {
            var organization = findOrganization(state.organizationId);
            if (!organization) {
                return;
            }
            var organizationName = String(organization.name || 'Organisation');
            var logoUrl = String(organization.logo || '/img/logo-OGC.png');
            document.querySelectorAll('[data-common-topbar-brand-logo]').forEach(function (logo) {
                logo.src = logoUrl;
                logo.alt = organizationName;
            });
            document.querySelectorAll('[data-common-topbar-brand-link]').forEach(function (link) {
                link.title = organizationName;
                link.setAttribute('aria-label', organizationName);
            });
        }

        function canOpenPane(pane) {
            if (pane <= 0) {
                return true;
            }
            if (pane === 1) {
                return state.organizationId > 0;
            }
            return state.organizationId > 0 && state.selectedHolonId > 0;
        }

        function setPane(pane, feedbackIfBlocked) {
            pane = Math.max(0, Math.min(2, Number(pane) || 0));
            if (!canOpenPane(pane)) {
                if (feedbackIfBlocked) {
                    showFeedback(pane === 1 ? translations.needOrganization : translations.needHolon, false);
                }
                return false;
            }
            state.activePane = pane;
            if (swipeTrack) {
                swipeTrack.style.transform = 'translateX(-' + (pane * (100 / 3)) + '%)';
            }
            document.querySelectorAll('[data-timer-pane]').forEach(function (button) {
                var isActive = Number(button.getAttribute('data-timer-pane')) === pane;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            if (pane === 2) {
                loadProjects();
            }
            return true;
        }

        function postAction(action, payload) {
            var body = Object.assign({}, payload || {}, { csrf_token: config.csrfToken || '' });
            return fetch(config.apiUrl + '?action=' + encodeURIComponent(action), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(body)
            }).then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (result) {
                    if (!response.ok || result.error) {
                        throw new Error(result.message || translations.genericError);
                    }
                    return result;
                });
            });
        }

        function applyResponse(result, options) {
            options = options || {};
            setServerClock(result.serverNow);
            state.activeEntry = result.active && result.entry ? result.entry : null;
            if (options.followEntry && state.activeEntry) {
                state.organizationId = normalizeId(state.activeEntry.organizationId);
                state.selectedHolonId = normalizeId(state.activeEntry.holonId);
                state.selectedProjectId = normalizeId(state.activeEntry.projectId);
                updateOrganizationButtons();
            }
            renderStatus();
            if (options.recovered) {
                showFeedback(translations.recovered, true);
            }
        }

        function targetKey() {
            return [state.organizationId, state.selectedHolonId, state.selectedProjectId].join(':');
        }

        function switchTarget() {
            if (!state.activeEntry || state.selectedHolonId <= 0 || state.requestInProgress) {
                return;
            }
            var nextTarget = targetKey();
            if (state.activeEntry.organizationId === state.organizationId
                && state.activeEntry.holonId === state.selectedHolonId
                && normalizeId(state.activeEntry.projectId) === state.selectedProjectId) {
                return;
            }
            if (state.lastSwitchTarget === nextTarget) {
                return;
            }
            state.lastSwitchTarget = nextTarget;
            state.requestInProgress = true;
            postAction('switch', {
                organization_id: state.organizationId,
                holon_id: state.selectedHolonId,
                project_id: state.selectedProjectId
            }).then(function (result) {
                applyResponse(result);
                showFeedback('', true);
            }).catch(function (error) {
                showFeedback(error.message || translations.genericError, false);
            }).finally(function () {
                state.requestInProgress = false;
            });
        }

        function projectCacheKey() {
            return state.organizationId + ':' + state.selectedHolonId + ':' + state.projectStatus;
        }

        function renderProjects(projects) {
            if (!projectList) {
                return;
            }
            var content = '<button type="button" class="timer-project-choice' + (state.selectedProjectId === 0 ? ' is-selected' : '')
                + '" data-timer-project-id="0" aria-pressed="' + (state.selectedProjectId === 0 ? 'true' : 'false') + '">'
                + escapeHtml(translations.projectNone || '') + '</button>';
            if (projects.length === 0) {
                content += '<p class="timer-project-message">' + escapeHtml(translations.projectEmpty || '') + '</p>';
            } else {
                projects.forEach(function (project) {
                    var projectId = normalizeId(project.id);
                    if (projectId <= 0) {
                        return;
                    }
                    var isSelected = projectId === state.selectedProjectId;
                    content += '<button type="button" class="timer-project-choice' + (isSelected ? ' is-selected' : '')
                        + '" data-timer-project-id="' + projectId + '" aria-pressed="' + (isSelected ? 'true' : 'false') + '">'
                        + escapeHtml(project.title || '') + '</button>';
                });
            }
            projectList.innerHTML = content;
        }

        function loadProjects(force) {
            if (!projectList || state.organizationId <= 0 || state.selectedHolonId <= 0) {
                return;
            }
            var cacheKey = projectCacheKey();
            if (!force && Array.isArray(state.projects[cacheKey])) {
                renderProjects(state.projects[cacheKey]);
                return;
            }
            projectList.innerHTML = '<p class="timer-project-message">' + escapeHtml(translations.projectLoading || '') + '</p>';
            var requestedOrganizationId = state.organizationId;
            var requestedHolonId = state.selectedHolonId;
            var requestedStatus = state.projectStatus;
            fetch(config.apiUrl + '?action=projects&organization_id=' + encodeURIComponent(requestedOrganizationId)
                + '&holon_id=' + encodeURIComponent(requestedHolonId)
                + '&status=' + encodeURIComponent(requestedStatus), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            }).then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (result) {
                    if (!response.ok || result.error) {
                        throw new Error(result.message || translations.genericError);
                    }
                    return result;
                });
            }).then(function (result) {
                if (state.organizationId !== requestedOrganizationId
                    || state.selectedHolonId !== requestedHolonId
                    || state.projectStatus !== requestedStatus) {
                    return;
                }
                var projects = Array.isArray(result.projects) ? result.projects : [];
                state.projects[cacheKey] = projects;
                var selectedProject = projects.find(function (project) {
                    return normalizeId(project.id) === state.selectedProjectId;
                });
                if (selectedProject) {
                    state.selectedProjectName = String(selectedProject.title || '');
                    renderTarget();
                }
                renderProjects(projects);
            }).catch(function (error) {
                projectList.innerHTML = '<p class="timer-project-message">' + escapeHtml(error.message || translations.genericError) + '</p>';
            });
        }

        function selectProject(projectId) {
            projectId = normalizeId(projectId);
            var projects = state.projects[projectCacheKey()] || [];
            var selectedProject = projects.find(function (project) {
                return normalizeId(project.id) === projectId;
            });
            if (projectId > 0 && !selectedProject) {
                return;
            }
            state.selectedProjectId = projectId;
            state.selectedProjectName = selectedProject ? String(selectedProject.title || '') : '';
            renderTarget();
            renderProjects(projects);
            switchTarget();
        }

        function mountPicker(initialHolonId) {
            if (!pickerHost || state.organizationId <= 0 || typeof window.omoMountHolonScopePicker !== 'function') {
                return;
            }
            var mountedOrganizationId = state.organizationId;
            state.selectedHolonId = normalizeId(initialHolonId);
            state.selectedHolonName = '';
            state.pickerReady = false;
            updateSelectedLabel();
            state.picker = window.omoMountHolonScopePicker({
                host: pickerHost,
                organizationId: state.organizationId,
                initialHolonId: state.selectedHolonId,
                allowEmptySelection: true,
                labelMode: 'children',
                showModes: false,
                suppressInitialChange: true,
                labels: {},
                onReady: function (holonId) {
                    if (state.organizationId !== mountedOrganizationId) {
                        return;
                    }
                    state.selectedHolonId = normalizeId(holonId);
                    state.pickerReady = true;
                    state.selectedHolonName = state.picker && typeof state.picker.getSelectedHolonLabel === 'function'
                        ? state.picker.getSelectedHolonLabel()
                        : '';
                    updateSelectedLabel();
                    renderTarget();
                    if (state.selectedHolonId > 0 && state.selectedProjectId > 0) {
                        loadProjects();
                    }
                },
                onChange: function (holonId) {
                    if (state.organizationId !== mountedOrganizationId) {
                        return;
                    }
                    state.selectedHolonId = normalizeId(holonId);
                    state.selectedHolonName = state.picker && typeof state.picker.getSelectedHolonLabel === 'function'
                        ? state.picker.getSelectedHolonLabel()
                        : '';
                    state.selectedProjectId = 0;
                    state.selectedProjectName = '';
                    updateSelectedLabel();
                    renderTarget();
                    switchTarget();
                    if (state.activePane === 2) {
                        loadProjects();
                    }
                }
            });
        }

        function selectOrganization(organizationId, keepHolon, autoAdvance) {
            organizationId = normalizeId(organizationId);
            if (organizationId <= 0) {
                return;
            }
            var initialHolonId = keepHolon ? state.selectedHolonId : 0;
            state.organizationId = organizationId;
            state.selectedHolonId = initialHolonId;
            state.selectedHolonName = '';
            state.selectedProjectId = keepHolon ? state.selectedProjectId : 0;
            state.selectedProjectName = '';
            updateOrganizationButtons();
            updateTopbarBrand();
            renderTarget();
            mountPicker(initialHolonId);
            var url = new URL(window.location.href);
            url.searchParams.set('oid', String(organizationId));
            window.history.replaceState({}, '', url.toString());
            if (autoAdvance !== false) {
                setPane(1, false);
            }
        }

        function sendHeartbeat(silent) {
            if (!state.activeEntry || state.requestInProgress) {
                return Promise.resolve(null);
            }
            state.requestInProgress = true;
            return postAction('heartbeat', { entry_id: normalizeId(state.activeEntry.id) })
                .then(function (result) {
                    applyResponse(result);
                    if (!silent) {
                        showFeedback('', true);
                    }
                    return result;
                }).catch(function () {
                    showFeedback(translations.networkError, false);
                    return null;
                }).finally(function () {
                    state.requestInProgress = false;
                });
        }

        function toggleTimer() {
            if (state.requestInProgress) {
                return;
            }
            if (state.activeEntry) {
                state.requestInProgress = true;
                postAction('stop', { entry_id: normalizeId(state.activeEntry.id) })
                    .then(function (result) {
                        applyResponse(result);
                        showFeedback('', true);
                    }).catch(function (error) {
                        showFeedback(error.message || translations.genericError, false);
                    }).finally(function () {
                        state.requestInProgress = false;
                    });
                return;
            }
            if (state.organizationId <= 0 || state.selectedHolonId <= 0) {
                showFeedback(translations.noHolon, false);
                return;
            }
            state.requestInProgress = true;
            postAction('start', {
                organization_id: state.organizationId,
                holon_id: state.selectedHolonId,
                project_id: state.selectedProjectId
            }).then(function (result) {
                applyResponse(result);
                showFeedback('', true);
            }).catch(function (error) {
                showFeedback(error.message || translations.genericError, false);
            }).finally(function () {
                state.requestInProgress = false;
            });
        }

        document.querySelectorAll('[data-timer-organization-id]').forEach(function (button) {
            button.addEventListener('click', function () {
                selectOrganization(button.getAttribute('data-timer-organization-id'), false);
            });
        });
        document.querySelectorAll('[data-timer-pane]').forEach(function (button) {
            button.addEventListener('click', function () {
                setPane(button.getAttribute('data-timer-pane'), true);
            });
        });
        if (projectList) {
            projectList.addEventListener('click', function (event) {
                var button = event.target.closest('[data-timer-project-id]');
                if (button) {
                    selectProject(button.getAttribute('data-timer-project-id'));
                }
            });
        }
        if (projectStatus) {
            projectStatus.addEventListener('change', function () {
                state.projectStatus = String(projectStatus.value || 'in_progress');
                loadProjects(true);
            });
        }
        if (toggleButton) {
            toggleButton.addEventListener('click', toggleTimer);
        }
        function bindSwipeSurface(swipeSurface) {
            var swipeStart = null;
            function startSwipe(clientX, clientY) {
                swipeStart = { x: clientX, y: clientY };
            }
            function moveSwipe(clientX, clientY) {
                if (!swipeStart) {
                    return false;
                }
                var deltaX = clientX - swipeStart.x;
                var deltaY = clientY - swipeStart.y;
                if (Math.abs(deltaX) < 24 || Math.abs(deltaX) < Math.abs(deltaY) * 0.6) {
                    return false;
                }
                swipeStart = null;
                setPane(state.activePane + (deltaX < 0 ? 1 : -1), true);
                return true;
            }
            function finishSwipe(clientX, clientY) {
                moveSwipe(clientX, clientY);
                swipeStart = null;
            }
            swipeSurface.addEventListener('pointerdown', function (event) {
                if (event.pointerType === 'mouse' && event.button !== 0) {
                    return;
                }
                startSwipe(event.clientX, event.clientY);
            }, true);
            swipeSurface.addEventListener('pointerup', function (event) {
                finishSwipe(event.clientX, event.clientY);
            }, true);
            swipeSurface.addEventListener('pointermove', function (event) {
                moveSwipe(event.clientX, event.clientY);
            }, true);
            swipeSurface.addEventListener('pointercancel', function () {
                swipeStart = null;
            }, true);
            swipeSurface.addEventListener('touchstart', function (event) {
                var touch = event.touches && event.touches[0];
                if (touch) {
                    startSwipe(touch.clientX, touch.clientY);
                }
            }, { capture: true, passive: true });
            swipeSurface.addEventListener('touchend', function (event) {
                var touch = event.changedTouches && event.changedTouches[0];
                if (touch) {
                    finishSwipe(touch.clientX, touch.clientY);
                }
            }, { capture: true, passive: true });
            swipeSurface.addEventListener('touchmove', function (event) {
                var touch = event.touches && event.touches[0];
                if (touch && moveSwipe(touch.clientX, touch.clientY)) {
                    event.preventDefault();
                }
            }, { capture: true, passive: false });
            swipeSurface.addEventListener('touchcancel', function () {
                swipeStart = null;
            }, { capture: true, passive: true });
        }
        swipeSurfaces.forEach(function (swipeSurface) {
            bindSwipeSurface(swipeSurface);
        });
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                sendHeartbeat(true);
            }
        });
        window.setInterval(renderClock, 1000);
        window.setInterval(function () { sendHeartbeat(true); }, 30 * 1000);

        renderStatus();
        updateOrganizationButtons();
        if (state.organizationId > 0) {
            mountPicker(state.selectedHolonId);
        }

        fetch(config.apiUrl + '?action=state', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        }).then(function (response) {
            return response.ok ? response.json() : null;
        }).then(function (result) {
            if (!result) {
                return;
            }
            var shouldRestore = Boolean(result.active && result.entry);
            applyResponse(result, { followEntry: shouldRestore, recovered: Boolean(shouldRestore && !config.activeEntry) });
            if (shouldRestore) {
                selectOrganization(state.organizationId, true, false);
                if (state.selectedProjectId > 0) {
                    loadProjects();
                }
            }
        }).catch(function () {});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
