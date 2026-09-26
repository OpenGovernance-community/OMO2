window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/common/choice/public.js"] = function (pageConfig, pageScript) {
        (function () {
            if (pageConfig.hasPersonalPublicAccess) {
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });
            }

            var decisionPublicTranslations = pageConfig.decisionPublicTranslations;
            var body = document.body;
            var leftPanel = document.getElementById('panel-left');
            var content = document.querySelector('.decision-public-workspace');
            var resizer = document.getElementById('resizer');
            var mobileNav = document.getElementById('omo-mobile-nav');
            var storageKey = 'decisionPublicLeftPanelWidth';
            var isResizing = false;

            function setView(view) {
                if (!body) {
                    return;
                }

                var resolvedView = view === 'left' ? 'left' : 'right';
                body.classList.remove('view-left', 'view-right');
                body.classList.add('view-' + resolvedView);
            }

            function getViewportWidth() {
                return window.innerWidth || document.documentElement.clientWidth || 0;
            }

            function clampWidth(width) {
                if (!content) {
                    return width;
                }

                var maxWidth = Math.floor(content.clientWidth * 0.7);
                if (maxWidth < 250) {
                    maxWidth = 250;
                }

                return Math.max(250, Math.min(width, maxWidth));
            }

            function applyWidth(width) {
                if (!leftPanel || !content || getViewportWidth() <= 768) {
                    return;
                }

                var clampedWidth = clampWidth(width);
                leftPanel.style.width = String(clampedWidth) + 'px';
                leftPanel.style.flexBasis = String(clampedWidth) + 'px';
            }

            function clearWidth() {
                if (!leftPanel) {
                    return;
                }

                leftPanel.style.width = '';
                leftPanel.style.flexBasis = '';
            }

            function stopResizing() {
                if (!isResizing) {
                    return;
                }

                isResizing = false;
                body.classList.remove('is-resizing');

                if (!leftPanel || !window.localStorage || getViewportWidth() <= 768) {
                    return;
                }

                window.localStorage.setItem(storageKey, String(Math.round(leftPanel.getBoundingClientRect().width)));
            }

            if (mobileNav) {
                mobileNav.addEventListener('click', function (event) {
                    var button = event.target.closest('button[data-view]');
                    if (!button) {
                        return;
                    }

                    setView(button.getAttribute('data-view') || 'right');
                });
            }

            if (leftPanel && content && resizer) {
                if (window.localStorage) {
                    var savedWidth = parseInt(window.localStorage.getItem(storageKey) || '', 10);
                    if (!Number.isNaN(savedWidth)) {
                        applyWidth(savedWidth);
                    }
                }

                resizer.addEventListener('mousedown', function (event) {
                    if (event.button !== 0 || getViewportWidth() <= 768) {
                        return;
                    }

                    isResizing = true;
                    body.classList.add('is-resizing');
                    event.preventDefault();
                });

                document.addEventListener('mousemove', function (event) {
                    if (!isResizing || !content) {
                        return;
                    }

                    var contentRect = content.getBoundingClientRect();
                    var nextWidth = event.clientX - contentRect.left;
                    applyWidth(nextWidth);
                });

                document.addEventListener('mouseup', stopResizing);
                window.addEventListener('blur', stopResizing);
                window.addEventListener('resize', function () {
                    if (getViewportWidth() <= 768) {
                        clearWidth();
                        return;
                    }

                    if (window.localStorage) {
                        var storedWidth = parseInt(window.localStorage.getItem(storageKey) || '', 10);
                        if (!Number.isNaN(storedWidth)) {
                            applyWidth(storedWidth);
                        }
                    }
                });
            }
        })();

        (function () {
            if (typeof window.omoRefreshDecisionView !== 'function') {
                window.omoRefreshDecisionView = function (url) {
                    var targetUrl = String(url || '').trim();
                    if (targetUrl !== '') {
                        window.location.href = targetUrl;
                    }
                };
            }

            var accordions = document.querySelectorAll('[data-decision-public-timeline]');
            var desktopMedia = typeof window.matchMedia === 'function'
                ? window.matchMedia('(min-width: 769px)')
                : null;

            function syncAccordionState() {
                for (var syncIndex = 0; syncIndex < accordions.length; syncIndex += 1) {
                    var syncAccordion = accordions[syncIndex];
                    var syncToggle = syncAccordion.querySelector('[data-decision-public-timeline-toggle]');
                    if (!syncToggle) {
                        continue;
                    }

                    if (syncAccordion.dataset.decisionPublicTimelineTouched === '1') {
                        continue;
                    }

                    syncAccordion.classList.add('is-collapsed');
                    syncToggle.setAttribute('aria-expanded', 'false');
                }
            }

            for (var index = 0; index < accordions.length; index += 1) {
                var accordion = accordions[index];
                var toggle = accordion.querySelector('[data-decision-public-timeline-toggle]');
                if (!toggle) {
                    continue;
                }

                toggle.addEventListener('click', function () {
                    var parentAccordion = this.closest('[data-decision-public-timeline]');
                    if (!parentAccordion) {
                        return;
                    }

                    parentAccordion.dataset.decisionPublicTimelineTouched = '1';
                    var isCollapsed = parentAccordion.classList.toggle('is-collapsed');
                    this.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                });
            }

            syncAccordionState();
            if (desktopMedia) {
                if (typeof desktopMedia.addEventListener === 'function') {
                    desktopMedia.addEventListener('change', syncAccordionState);
                } else if (typeof desktopMedia.addListener === 'function') {
                    desktopMedia.addListener(syncAccordionState);
                }
            }
        })();

        (function () {
            var forms = document.querySelectorAll('[data-omo-decision-consultation-proposal-form]');

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function setFeedback(form, type, message) {
                var container = form.querySelector('[data-omo-decision-consultation-proposal-feedback]');
                if (!container) {
                    return;
                }

                if (!message) {
                    container.hidden = true;
                    container.innerHTML = '';
                    return;
                }

                if (typeof window.commonNotify === 'function') {
                    window.commonNotify(String(message), type || 'error');
                    container.hidden = true;
                    container.innerHTML = '';
                    return;
                }

                var tint = 'var(--color-warning, #f59e0b)';
                if (type === 'success') {
                    tint = 'var(--color-success, #16a34a)';
                } else if (type === 'error') {
                    tint = 'var(--color-danger, #dc2626)';
                }

                container.hidden = false;
                container.innerHTML = ''
                    + '<div class="generic-soft-panel generic-soft-panel--stack"'
                    + ' style="background:color-mix(in srgb, ' + tint + ' 10%, var(--color-surface, #ffffff));'
                    + 'border-color:color-mix(in srgb, ' + tint + ' 28%, var(--color-surface, #ffffff));">'
                    + '<p style="margin:0;line-height:1.5;">' + escapeHtml(message) + '</p>'
                    + '</div>';
            }

            function showPendingConsultationProposalNotifications() {
                var notifications = document.querySelectorAll('[data-omo-decision-consultation-proposal-notification]');
                var handledByTopbar = false;
                for (var notificationIndex = 0; notificationIndex < notifications.length; notificationIndex += 1) {
                    var notification = notifications[notificationIndex];
                    var message = String(notification.getAttribute('data-omo-decision-consultation-proposal-notification-message') || '');
                    var type = String(notification.getAttribute('data-omo-decision-consultation-proposal-notification-type') || 'warning');

                    if (message === '') {
                        notification.remove();
                        continue;
                    }

                    if (typeof window.commonNotify === 'function') {
                        window.commonNotify(message, type);
                        notification.remove();
                        handledByTopbar = true;
                        continue;
                    }

                    notification.hidden = false;
                }

                if (handledByTopbar && typeof window.history !== 'undefined' && typeof window.history.replaceState === 'function') {
                    var currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.delete('consultation_proposal_status');
                    currentUrl.searchParams.delete('consultation_proposal_count');
                    window.history.replaceState({}, document.title, currentUrl.pathname + currentUrl.search + currentUrl.hash);
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', showPendingConsultationProposalNotifications, { once: true });
            } else {
                showPendingConsultationProposalNotifications();
            }

            function setSubmitting(form, isSubmitting) {
                var submitButtons = form.querySelectorAll('button[type="submit"]');
                for (var buttonIndex = 0; buttonIndex < submitButtons.length; buttonIndex += 1) {
                    submitButtons[buttonIndex].disabled = !!isSubmitting;
                }
            }

            function reloadDecisionView(form, redirectUrl) {
                var targetUrl = String(redirectUrl || form.getAttribute('data-omo-decision-return-url') || '').trim();
                if (targetUrl === '') {
                    return;
                }

                var drawerTitleNode = document.getElementById('commonTopbarDrawerTitle');
                var drawerTitle = drawerTitleNode ? String(drawerTitleNode.textContent || '').trim() : '';
                if (typeof window.omoRefreshDecisionView === 'function') {
                    window.omoRefreshDecisionView(targetUrl, {
                        title: drawerTitle || decisionPublicTranslations.defaultTitle,
                        source: 'consultation_proposal'
                    });
                    return;
                }

                var isEmbeddedTarget = /(?:\?|&)embedded=1(?:&|$)/.test(targetUrl);
                var drawer = document.getElementById('commonTopbarDrawer');
                if (
                    typeof window.commonTopbarOpenDrawer === 'function'
                    && (
                        isEmbeddedTarget
                        || (drawer && !drawer.hidden)
                    )
                ) {
                    window.commonTopbarOpenDrawer(drawerTitle || decisionPublicTranslations.defaultTitle, targetUrl, 'fetch');
                    return;
                }

                window.location.href = targetUrl;
            }

            function refreshRows(list) {
                var rows = list.querySelectorAll('[data-omo-decision-consultation-proposal-row]');
                for (var rowIndex = 0; rowIndex < rows.length; rowIndex += 1) {
                    var input = rows[rowIndex].querySelector('input[name="consultation_proposals[]"]');
                    var removeButton = rows[rowIndex].querySelector('[data-omo-decision-consultation-proposal-remove]');
                    if (input) {
                        input.setAttribute('placeholder', decisionPublicTranslations.proposalPlaceholderPrefix + ' ' + String(rowIndex + 1));
                    }
                    if (removeButton) {
                        removeButton.disabled = rows.length <= 1;
                    }
                }
            }

            function buildRow(list) {
                var row = document.createElement('div');
                row.style.display = 'grid';
                row.style.gridTemplateColumns = 'minmax(0,1fr) auto';
                row.style.gap = '8px';
                row.style.alignItems = 'start';
                row.setAttribute('data-omo-decision-consultation-proposal-row', '');

                var input = document.createElement('input');
                input.type = 'text';
                input.name = 'consultation_proposals[]';
                input.className = 'generic-form-control';
                input.value = '';

                var removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'generic-action-button generic-action-button--secondary';
                removeButton.textContent = decisionPublicTranslations.removeProposal;
                removeButton.setAttribute('data-omo-decision-consultation-proposal-remove', '');

                row.appendChild(input);
                row.appendChild(removeButton);
                list.appendChild(row);
                refreshRows(list);
                input.focus();
            }

            for (var formIndex = 0; formIndex < forms.length; formIndex += 1) {
                var form = forms[formIndex];
                var list = form.querySelector('[data-omo-decision-consultation-proposal-list]');
                var addButton = form.querySelector('[data-omo-decision-consultation-proposal-add]');
                if (list) {
                    refreshRows(list);
                }

                if (list && addButton) {
                    addButton.addEventListener('click', function (event) {
                        event.preventDefault();
                        var targetForm = this.closest('[data-omo-decision-consultation-proposal-form]');
                        if (!targetForm) {
                            return;
                        }

                        var targetList = targetForm.querySelector('[data-omo-decision-consultation-proposal-list]');
                        if (!targetList) {
                            return;
                        }

                        buildRow(targetList);
                    });
                }

                if (list) {
                    list.addEventListener('click', function (event) {
                        var removeButton = event.target.closest('[data-omo-decision-consultation-proposal-remove]');
                        if (!removeButton) {
                            return;
                        }

                        var targetList = this;
                        var rows = targetList.querySelectorAll('[data-omo-decision-consultation-proposal-row]');
                        if (rows.length <= 1) {
                            return;
                        }

                        var row = removeButton.closest('[data-omo-decision-consultation-proposal-row]');
                        if (!row) {
                            return;
                        }

                        row.remove();
                        refreshRows(targetList);
                    });
                }

                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    var targetForm = this;
                    setFeedback(targetForm, '', '');
                    setSubmitting(targetForm, true);

                    var formData = new FormData(targetForm);
                    fetch(targetForm.action, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'fetch'
                        }
                    })
                        .then(function (response) {
                            return response.json().catch(function () {
                                return {
                                    status: false,
                                    feedbackStatus: 'error',
                                    message: decisionPublicTranslations.invalidResponse,
                                    redirectUrl: ''
                                };
                            });
                        })
                        .then(function (payload) {
                            setSubmitting(targetForm, false);

                            if (payload && payload.status) {
                                reloadDecisionView(targetForm, payload.redirectUrl || '');
                                return;
                            }

                            var feedbackType = payload && payload.feedbackStatus ? payload.feedbackStatus : 'error';
                            if (feedbackType !== 'success' && feedbackType !== 'warning') {
                                feedbackType = feedbackType === 'empty' || feedbackType === 'duplicate' ? 'warning' : 'error';
                            }

                            setFeedback(
                                targetForm,
                                feedbackType,
                                payload && payload.message ? payload.message : decisionPublicTranslations.proposalAddFailed
                            );
                        })
                        .catch(function () {
                            setSubmitting(targetForm, false);
                            setFeedback(targetForm, 'error', decisionPublicTranslations.proposalAddFailed);
                        });
                });
            }

            var accessRequestForm = document.getElementById('decisionPublicAccessRequestForm');
            var accessRequestAction = document.getElementById('decisionPublicAccessRequestAction');
            var accessRequestEmail = document.getElementById('decisionPublicAccessRequestEmail');
            var accessRequestCodeRow = document.getElementById('decisionPublicAccessRequestCodeRow');
            var accessRequestCode = document.getElementById('decisionPublicAccessRequestCode');
            var accessRequestSendActions = document.getElementById('decisionPublicAccessRequestSendActions');
            var accessRequestVerifyActions = document.getElementById('decisionPublicAccessRequestVerifyActions');
            var accessRequestFeedback = document.getElementById('decisionPublicAccessRequestFeedback');
            var accessRequestSendSubmit = document.getElementById('decisionPublicAccessRequestSendSubmit');
            var accessRequestVerifySubmit = document.getElementById('decisionPublicAccessRequestVerifySubmit');
            var accessRequestResend = document.getElementById('decisionPublicAccessRequestResend');

            function setAccessRequestMode(mode) {
                var verifyMode = mode === 'verify_code';
                if (accessRequestAction) {
                    accessRequestAction.value = verifyMode ? 'verify_code' : 'request_code';
                }
                if (accessRequestCodeRow) {
                    accessRequestCodeRow.hidden = !verifyMode;
                }
                if (accessRequestCode) {
                    accessRequestCode.required = verifyMode;
                    if (!verifyMode) {
                        accessRequestCode.value = '';
                    }
                }
                if (accessRequestSendActions) {
                    accessRequestSendActions.hidden = verifyMode;
                }
                if (accessRequestVerifyActions) {
                    accessRequestVerifyActions.hidden = !verifyMode;
                }
            }

            function setAccessRequestSubmitting(isSubmitting) {
                if (accessRequestSendSubmit) {
                    accessRequestSendSubmit.disabled = !!isSubmitting;
                }
                if (accessRequestVerifySubmit) {
                    accessRequestVerifySubmit.disabled = !!isSubmitting;
                }
                if (accessRequestResend) {
                    accessRequestResend.disabled = !!isSubmitting;
                }
            }

            function submitAccessRequest(action) {
                if (!accessRequestForm || !accessRequestFeedback) {
                    return;
                }

                if (accessRequestAction) {
                    accessRequestAction.value = action === 'verify_code' ? 'verify_code' : 'request_code';
                }

                accessRequestFeedback.textContent = '';
                accessRequestFeedback.classList.remove('is-success');
                setAccessRequestSubmitting(true);

                fetch(accessRequestForm.getAttribute('action') || window.location.href, {
                    method: 'POST',
                    body: new FormData(accessRequestForm),
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return {
                                ok: response.ok,
                                data: data
                            };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok || !result.data || !result.data.status) {
                            accessRequestFeedback.textContent = result.data && result.data.message
                                ? result.data.message
                                : decisionPublicTranslations.accessProcessFailed;
                            setAccessRequestSubmitting(false);
                            return;
                        }

                        accessRequestFeedback.textContent = result.data.message || decisionPublicTranslations.codeSent;
                        accessRequestFeedback.classList.add('is-success');

                        if (result.data && result.data.redirectUrl) {
                            window.location.href = String(result.data.redirectUrl);
                            return;
                        }

                        if (result.data && result.data.nextAction === 'verify_code') {
                            setAccessRequestMode('verify_code');
                            if (accessRequestCode) {
                                accessRequestCode.focus();
                                accessRequestCode.select();
                            }
                        } else {
                            accessRequestForm.reset();
                            setAccessRequestMode('request_code');
                        }

                        setAccessRequestSubmitting(false);
                    })
                    .catch(function () {
                        accessRequestFeedback.textContent = decisionPublicTranslations.accessProcessFailed;
                        setAccessRequestSubmitting(false);
                    });
            }

            if (accessRequestForm && accessRequestFeedback && accessRequestSendSubmit && accessRequestVerifySubmit) {
                setAccessRequestMode('request_code');
                accessRequestForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    submitAccessRequest(accessRequestAction ? accessRequestAction.value : 'request_code');
                });

                accessRequestSendSubmit.addEventListener('click', function () {
                    if (accessRequestAction) {
                        accessRequestAction.value = 'request_code';
                    }
                });

                accessRequestVerifySubmit.addEventListener('click', function () {
                    if (accessRequestAction) {
                        accessRequestAction.value = 'verify_code';
                    }
                });

                if (accessRequestResend) {
                    accessRequestResend.addEventListener('click', function () {
                        submitAccessRequest('request_code');
                    });
                }
            }
        })();
};
