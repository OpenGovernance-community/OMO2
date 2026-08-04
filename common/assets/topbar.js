(function () {
    var modalDragState = {
        active: false,
        pointerId: null,
        startX: 0,
        startY: 0,
        offsetX: 0,
        offsetY: 0
    };

    function getConfig() {
        return window.commonTopbarConfig || {};
    }

    function getConfigValue(path) {
        var config = getConfig();
        var current = config;

        String(path || '').split('.').forEach(function (part) {
            if (!part || !current || typeof current !== 'object' || !(part in current)) {
                current = null;
                return;
            }

            current = current[part];
        });

        return current;
    }

    function getConfigTextValue(path, fallback) {
        var current = getConfigValue(path);

        return typeof current === 'string' && current !== '' ? current : fallback;
    }

    function isProfileModalUrl(url) {
        return /(?:^|\/)popup\/profil\.php(?:[?#]|$)/i.test(String(url || ''));
    }

    function setProfileText(selector, value, fallback) {
        var node = document.querySelector(selector);

        if (node) {
            node.textContent = value || fallback;
        }
    }

    function renderProfileAvatar(container, profile, imageClass, initialClass) {
        var image;
        var initial;

        if (!container) {
            return;
        }

        container.innerHTML = '';
        if (profile.photoUrl) {
            image = document.createElement('img');
            image.src = String(profile.photoUrl);
            image.alt = String(profile.displayName || 'Profil');
            image.className = imageClass;
            container.appendChild(image);
            container.removeAttribute('style');
            return;
        }

        initial = document.createElement('span');
        initial.className = initialClass;
        initial.setAttribute('aria-hidden', 'true');
        initial.textContent = String(profile.initials || 'P');
        container.appendChild(initial);
        if (profile.avatarStyle) {
            container.setAttribute('style', String(profile.avatarStyle));
        }
    }

    function updateProfileMenu(profile) {
        var triggerAvatar = document.querySelector('[data-common-topbar-avatar]');
        var media = document.querySelector('[data-common-topbar-profile-media]');
        var mediaAvatar = media
            ? media.querySelector('.common-topbar-profile-card__photo, .common-topbar-profile-card__placeholder')
            : null;
        var emptyValue = getConfigTextValue('profile.details.emptyValueLabel', 'Non renseigne');
        var summaryFallback = getConfigTextValue('profile.summaryFallback', 'Resume du profil');

        profile = profile && typeof profile === 'object' ? profile : {};
        renderProfileAvatar(triggerAvatar, profile, 'common-topbar__avatar-image', 'common-topbar__avatar-initial');

        if (triggerAvatar && profile.photoUrl) {
            triggerAvatar.removeAttribute('style');
        }

        if (media && mediaAvatar) {
            if (profile.photoUrl) {
                var mediaImage = document.createElement('img');
                mediaImage.src = String(profile.photoUrl);
                mediaImage.alt = String(profile.displayName || 'Profil');
                mediaImage.className = 'common-topbar-profile-card__photo';
                mediaImage.setAttribute('data-common-topbar-profile-photo', '');
                mediaAvatar.replaceWith(mediaImage);
            } else {
                var mediaPlaceholder = document.createElement('div');
                mediaPlaceholder.className = 'common-topbar-profile-card__placeholder';
                mediaPlaceholder.setAttribute('data-common-topbar-profile-placeholder', '');
                mediaPlaceholder.setAttribute('aria-hidden', 'true');
                mediaPlaceholder.textContent = String(profile.initials || 'P');
                if (profile.avatarStyle) {
                    mediaPlaceholder.setAttribute('style', String(profile.avatarStyle));
                }
                mediaAvatar.replaceWith(mediaPlaceholder);
            }
        }

        setProfileText('[data-common-topbar-display-name]', String(profile.displayName || ''), 'Profil');
        setProfileText('[data-common-topbar-email]', String(profile.email || ''), summaryFallback);
        setProfileText('[data-common-topbar-detail-name]', String(profile.displayName || ''), emptyValue);
        setProfileText('[data-common-topbar-detail-email]', String(profile.email || ''), emptyValue);
        setProfileText('[data-common-topbar-detail-username]', String(profile.username || ''), emptyValue);
    }

    function notifyUserProfileChanged(reason) {
        var profileUrl = '/ajax/user_profile.php?_=' + Date.now();

        window.dispatchEvent(new CustomEvent('common-user-profile-change', {
            detail: {
                reason: String(reason || 'change')
            }
        }));

        fetch(profileUrl, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('profile_load');
                }

                return response.json();
            })
            .then(function (payload) {
                if (!payload || !payload.status || !payload.profile) {
                    throw new Error('profile_payload');
                }

                updateProfileMenu(payload.profile);
                window.dispatchEvent(new CustomEvent('common-user-profile-updated', {
                    detail: {
                        reason: String(reason || 'change'),
                        profile: payload.profile
                    }
                }));
            })
            .catch(function () {
            });
    }

    function runContainerCleanup(container) {
        if (!container || container.id !== 'commonTopbarModalBody') {
            return;
        }

        if (typeof window.__omoPopupCleanup === 'function') {
            window.__omoPopupCleanup();
            window.__omoPopupCleanup = null;
        } else if (typeof window.__omoFaqPopupCleanup === 'function') {
            window.__omoFaqPopupCleanup();
            window.__omoFaqPopupCleanup = null;
        }
    }

    function executeEmbeddedScripts(container) {
        var scripts;
        var sequence;

        if (!container) {
            return Promise.resolve();
        }

        scripts = Array.prototype.slice.call(container.querySelectorAll('script'));
        sequence = Promise.resolve();

        scripts.forEach(function (script) {
            sequence = sequence.then(function () {
                return new Promise(function (resolve) {
                    var replacement = document.createElement('script');

                    Array.prototype.forEach.call(script.attributes, function (attribute) {
                        replacement.setAttribute(attribute.name, attribute.value);
                    });

                    if (replacement.src) {
                        replacement.async = false;
                        replacement.addEventListener('load', function () {
                            resolve();
                        }, { once: true });
                        replacement.addEventListener('error', function () {
                            resolve();
                        }, { once: true });
                    } else {
                        replacement.textContent = script.textContent || '';
                    }

                    script.parentNode.replaceChild(replacement, script);

                    if (!replacement.src) {
                        resolve();
                    }
                });
            });
        });

        return sequence;
    }

    function enhanceScrollablePanel(container) {
        if (!container) {
            return;
        }

        Array.prototype.forEach.call(
            container.querySelectorAll('.common-topbar__sticky-actions'),
            function (node) {
                node.classList.remove('common-topbar__sticky-actions');
            }
        );

        var selectors = [
            '[class*="__actions"]',
            '[class$="__footer"]'
        ];
        var candidates = Array.prototype.filter.call(
            container.querySelectorAll(selectors.join(',')),
            function (node) {
                if (!node || node.offsetParent === null) {
                    return false;
                }

                var buttons = node.querySelectorAll('button, input[type="submit"], a.generic-action-button');
                if (!buttons.length) {
                    return false;
                }

                if (node.querySelector('button[type="submit"], input[type="submit"], .generic-action-button--main')) {
                    return true;
                }

                return buttons.length >= 2;
            }
        );

        if (!candidates.length) {
            return;
        }

        candidates[candidates.length - 1].classList.add('common-topbar__sticky-actions');
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderRemoteError(container) {
        var errorLabel = getConfigTextValue('translations.loadErrorLabel', 'Erreur de chargement');
        var errorDescription = getConfigTextValue(
            'translations.loadErrorDescription',
            'Le contenu n’a pas pu être chargé. Vérifiez votre connexion puis réessayez.'
        );
        var retryLabel = getConfigTextValue('translations.retryLabel', 'Réessayer');

        container.innerHTML = ''
            + '<div class="common-topbar-remote-state common-topbar-remote-state--error" role="alert">'
            + '  <div class="common-topbar-remote-state__icon" aria-hidden="true">!</div>'
            + '  <div class="common-topbar-remote-state__content">'
            + '    <h3 class="common-topbar-remote-state__title">' + escapeHtml(errorLabel) + '</h3>'
            + '    <p class="common-topbar-remote-state__description">' + escapeHtml(errorDescription) + '</p>'
            + '    <button type="button" class="generic-action-button generic-action-button--main" data-topbar-remote-retry>' + escapeHtml(retryLabel) + '</button>'
            + '  </div>'
            + '</div>';
    }

    function renderRemoteContent(container, url) {
        if (!container) {
            return;
        }

        var resolvedUrl = (typeof window.omoResolveAppUrl === 'function')
            ? window.omoResolveAppUrl(url)
            : url;
        var requestId = String(Date.now()) + '_' + Math.random().toString(36).slice(2);

        container.setAttribute('data-topbar-remote-container', '1');
        container.setAttribute('data-topbar-remote-url', String(url || ''));
        container.__commonTopbarRemoteRequestId = requestId;
        runContainerCleanup(container);
        container.innerHTML = '<div class="loading">' + getConfigTextValue('translations.loadingLabel', 'Chargement...') + '</div>';

        fetch(resolvedUrl, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(getConfigTextValue('translations.loadErrorLabel', 'Erreur de chargement'));
                }

                return response.text();
            })
            .then(function (html) {
                if (container.__commonTopbarRemoteRequestId !== requestId) {
                    return;
                }

                container.innerHTML = html;
                executeEmbeddedScripts(container);
                enhanceScrollablePanel(container);
                if (container.id === 'commonTopbarModalBody') {
                    syncModalPanelPreferredWidth(container);
                }
                window.setTimeout(function () {
                    enhanceScrollablePanel(container);
                }, 0);
            })
            .catch(function () {
                if (container.__commonTopbarRemoteRequestId !== requestId) {
                    return;
                }

                renderRemoteError(container);
            });
    }

    function syncIframeTheme(frame) {
        if (!frame || typeof window.sharedBroadcastThemeToChildFrames !== 'function') {
            return;
        }

        window.sharedBroadcastThemeToChildFrames({
            preference: getThemePreference(),
            colorStyle: getColorStylePreference()
        });
    }

    function bindIframeThemeSync(frame) {
        if (!frame) {
            return;
        }

        frame.addEventListener('load', function () {
            syncIframeTheme(frame);
            window.setTimeout(function () {
                syncIframeTheme(frame);
            }, 60);
        }, { once: true });
    }

    function closeMenus() {
        closePreferenceMenus();
        document.querySelectorAll('.common-topbar__menu.is-open').forEach(function (menu) {
            menu.classList.remove('is-open');
        });
    }

    function closePreferenceMenus(exceptContainer) {
        Array.prototype.forEach.call(
            document.querySelectorAll('[data-topbar-preference-menu]'),
            function (container) {
                var popup;
                var trigger;

                if (!container || container === exceptContainer) {
                    return;
                }

                popup = container.querySelector('[data-topbar-preference-popup]');
                trigger = container.querySelector('[data-topbar-preference-trigger]');

                container.classList.remove('is-open');
                if (popup) {
                    popup.hidden = true;
                }
                if (trigger) {
                    trigger.setAttribute('aria-expanded', 'false');
                }
            }
        );
    }

    function focusSearchInput(input) {
        if (!input) {
            return;
        }

        window.setTimeout(function () {
            input.focus();
            if (typeof input.select === 'function') {
                input.select();
                return;
            }

            if (typeof input.setSelectionRange === 'function') {
                var valueLength = String(input.value || '').length;
                input.setSelectionRange(0, valueLength);
            }
        }, 20);
    }

    function getModalPanel() {
        return document.querySelector('#commonTopbarModal .common-topbar-modal__panel');
    }

    function syncModalPanelPreferredWidth(body) {
        var panel = getModalPanel();
        var widthSource;
        var requestedWidth;
        if (!panel) {
            return;
        }

        panel.style.removeProperty('--common-topbar-modal-max-width');
        if (!body) {
            return;
        }

        widthSource = body.querySelector('[data-topbar-modal-max-width]');
        requestedWidth = widthSource
            ? String(widthSource.getAttribute('data-topbar-modal-max-width') || '').trim()
            : '';
        if (/^(?:[1-9]\d{1,3})(?:\.\d+)?(?:px|rem|em|vw)$/.test(requestedWidth)) {
            panel.style.setProperty('--common-topbar-modal-max-width', requestedWidth);
        }
    }

    function applyModalPanelOffset(offsetX, offsetY) {
        var panel = getModalPanel();
        if (!panel) {
            return;
        }

        modalDragState.offsetX = Number(offsetX) || 0;
        modalDragState.offsetY = Number(offsetY) || 0;
        panel.style.transform = 'translate(' + modalDragState.offsetX + 'px, ' + modalDragState.offsetY + 'px)';
    }

    function resetModalPanelOffset() {
        var panel = getModalPanel();
        modalDragState.offsetX = 0;
        modalDragState.offsetY = 0;

        if (!panel) {
            return;
        }

        panel.style.transform = '';
        panel.classList.remove('is-dragging');
    }

    function clampModalOffset(offsetX, offsetY) {
        var panel = getModalPanel();
        if (!panel) {
            return {
                x: Number(offsetX) || 0,
                y: Number(offsetY) || 0
            };
        }

        var rect = panel.getBoundingClientRect();
        var viewportWidth = window.innerWidth || document.documentElement.clientWidth || rect.width;
        var viewportHeight = window.innerHeight || document.documentElement.clientHeight || rect.height;
        var currentOffsetX = modalDragState.offsetX || 0;
        var currentOffsetY = modalDragState.offsetY || 0;
        var baseLeft = rect.left - currentOffsetX;
        var baseRight = rect.right - currentOffsetX;
        var baseTop = rect.top - currentOffsetY;
        var baseBottom = rect.bottom - currentOffsetY;

        var minX = 12 - baseLeft;
        var maxX = viewportWidth - 12 - baseRight;
        var minY = 12 - baseTop;
        var maxY = viewportHeight - 12 - baseBottom;

        return {
            x: Math.min(Math.max(Number(offsetX) || 0, minX), maxX),
            y: Math.min(Math.max(Number(offsetY) || 0, minY), maxY)
        };
    }

    function stopModalDrag() {
        var panel = getModalPanel();

        modalDragState.active = false;
        modalDragState.pointerId = null;

        if (!panel) {
            return;
        }

        panel.classList.remove('is-dragging');
    }

    function handleModalHeaderPointerDown(event) {
        var header = event.target && event.target.closest
            ? event.target.closest('.common-topbar-modal__header')
            : null;
        var panel = getModalPanel();

        if (!header || !panel) {
            return;
        }

        if (event.button !== 0) {
            return;
        }

        if (event.target && event.target.closest && event.target.closest('[data-topbar-modal-close]')) {
            return;
        }

        modalDragState.active = true;
        modalDragState.pointerId = event.pointerId;
        modalDragState.startX = event.clientX - modalDragState.offsetX;
        modalDragState.startY = event.clientY - modalDragState.offsetY;
        panel.classList.add('is-dragging');

        if (typeof header.setPointerCapture === 'function') {
            try {
                header.setPointerCapture(event.pointerId);
            } catch (error) {
            }
        }

        event.preventDefault();
    }

    function handleModalHeaderPointerMove(event) {
        if (!modalDragState.active) {
            return;
        }

        if (modalDragState.pointerId !== null && event.pointerId !== modalDragState.pointerId) {
            return;
        }

        var nextOffset = clampModalOffset(
            event.clientX - modalDragState.startX,
            event.clientY - modalDragState.startY
        );

        applyModalPanelOffset(nextOffset.x, nextOffset.y);
    }

    function openDrawer(title, content, mode) {
        var drawer = document.getElementById('commonTopbarDrawer');
        var body = document.getElementById('commonTopbarDrawerBody');
        var titleNode = document.getElementById('commonTopbarDrawerTitle');
        var resolvedContent = mode === 'iframe' && typeof window.omoResolveAppUrl === 'function'
            ? window.omoResolveAppUrl(content)
            : content;

        if (!drawer || !body || !titleNode) {
            return;
        }

        closeModal();
        closeDrawer();

        titleNode.textContent = title || getConfigTextValue('drawer.defaultTitle', 'Panneau lateral');
        body.classList.remove('common-topbar-drawer__body--iframe');
        if (mode === 'iframe') {
            body.classList.add('common-topbar-drawer__body--iframe');
            body.innerHTML = '<iframe class="common-topbar-drawer__iframe" src="' + resolvedContent + '"></iframe>';
            bindIframeThemeSync(body.querySelector('iframe'));
        } else if (mode === 'fetch') {
            renderRemoteContent(body, content);
        } else {
            body.innerHTML = content || '';
            enhanceScrollablePanel(body);
        }

        drawer.hidden = false;
        document.body.classList.add('common-topbar-drawer-open');
    }

    function openModal(title, content, mode) {
        var modal = document.getElementById('commonTopbarModal');
        var body = document.getElementById('commonTopbarModalBody');
        var titleNode = document.getElementById('commonTopbarModalTitle');
        var resolvedContent = mode === 'iframe' && typeof window.omoResolveAppUrl === 'function'
            ? window.omoResolveAppUrl(content)
            : content;

        if (!modal || !body || !titleNode) {
            return;
        }

        closeDrawer();
        closeModal();
        resetModalPanelOffset();
        syncModalPanelPreferredWidth(null);
        body.setAttribute('data-topbar-modal-url', String(content || ''));
        titleNode.textContent = title || getConfigTextValue('modal.defaultTitle', 'Panneau');
        if (mode === 'iframe') {
            body.innerHTML = '<iframe class="common-topbar-modal__iframe" src="' + resolvedContent + '"></iframe>';
            bindIframeThemeSync(body.querySelector('iframe'));
        } else if (mode === 'fetch') {
            renderRemoteContent(body, content);
        } else {
            body.innerHTML = content || '';
            enhanceScrollablePanel(body);
            syncModalPanelPreferredWidth(body);
        }

        modal.hidden = false;
        document.body.classList.add('common-topbar-modal-open');
        window.dispatchEvent(new CustomEvent('common-topbar-modal-open', {
            detail: {
                title: title || getConfigTextValue('modal.defaultTitle', 'Panneau'),
                content: content,
                mode: mode || 'html'
            }
        }));
    }

    function closeDrawer() {
        var drawer = document.getElementById('commonTopbarDrawer');
        var body = document.getElementById('commonTopbarDrawerBody');
        if (!drawer) {
            return;
        }
        drawer.hidden = true;
        if (body) {
            body.innerHTML = '';
        }
        document.body.classList.remove('common-topbar-drawer-open');
    }

    function closeModal() {
        var modal = document.getElementById('commonTopbarModal');
        var body = document.getElementById('commonTopbarModalBody');
        var wasHidden = !modal || modal.hidden;
        var modalUrl = body ? body.getAttribute('data-topbar-modal-url') || '' : '';
        var closeGuard = window.commonTopbarModalCanClose;
        if (!modal) {
            return;
        }
        if (!wasHidden && typeof closeGuard === 'function' && closeGuard() === false) {
            return;
        }
        stopModalDrag();
        modal.hidden = true;
        syncModalPanelPreferredWidth(null);
        if (body) {
            runContainerCleanup(body);
            body.innerHTML = '';
            body.removeAttribute('data-omo-faq-modal');
            body.removeAttribute('data-omo-popup-key');
            body.removeAttribute('data-omo-popup-url');
            body.removeAttribute('data-omo-popup-live-sync');
            body.removeAttribute('data-topbar-modal-url');
        }
        document.body.classList.remove('common-topbar-modal-open');
        if (!wasHidden) {
            if (window.commonTopbarModalCanClose === closeGuard) {
                window.commonTopbarModalCanClose = null;
            }
            window.dispatchEvent(new CustomEvent('common-topbar-modal-close', {
                detail: {
                    url: modalUrl
                }
            }));
            if (isProfileModalUrl(modalUrl)) {
                notifyUserProfileChanged('close');
            }
        }
    }

    document.addEventListener('pointerdown', handleModalHeaderPointerDown);
    document.addEventListener('pointermove', handleModalHeaderPointerMove);
    document.addEventListener('pointerup', stopModalDrag);
    document.addEventListener('pointercancel', stopModalDrag);

    function callNamedFunction(name) {
        if (!name || typeof window[name] !== 'function') {
            return false;
        }

        var args = Array.prototype.slice.call(arguments, 1);
        window[name].apply(window, args);
        return true;
    }

    function renderSearchScopes(menu) {
        if (!menu) {
            return [];
        }

        var config = getConfig();
        var searchConfig = config.search || {};
        var scopeProvider = searchConfig.scopeProvider || '';
        var scopesContainer = menu.querySelector('[data-topbar-search-scopes]');
        var scopeList = menu.querySelector('[data-topbar-search-scope-list]');

        if (!scopesContainer || !scopeList) {
            return [];
        }

        scopeList.innerHTML = '';

        if (!scopeProvider || typeof window[scopeProvider] !== 'function') {
            scopesContainer.hidden = true;
            return [];
        }

        var scopes = window[scopeProvider](config);
        if (!Array.isArray(scopes) || scopes.length === 0) {
            scopesContainer.hidden = true;
            return [];
        }

        scopes.forEach(function (scope) {
            if (!scope || !scope.id || !scope.label) {
                return;
            }

            var wrapper = document.createElement('label');
            wrapper.className = 'common-topbar__search-scope';

            var input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'common-topbar__search-scope-input';
            input.setAttribute('data-topbar-search-scope-input', '1');
            input.value = String(scope.id);
            input.checked = !!scope.checked;

            var label = document.createElement('span');
            label.className = 'common-topbar__search-scope-label';
            label.textContent = String(scope.label);

            wrapper.appendChild(input);
            wrapper.appendChild(label);
            scopeList.appendChild(wrapper);
        });

        scopesContainer.hidden = scopeList.children.length === 0;
        return scopes;
    }

    function readSelectedSearchScopes(form) {
        if (!form) {
            return [];
        }

        return Array.prototype.map.call(
            form.querySelectorAll('[data-topbar-search-scope-input]:checked'),
            function (input, index) {
                var label = input.closest('.common-topbar__search-scope');
                var labelNode = label ? label.querySelector('.common-topbar__search-scope-label') : null;

                return {
                    id: input.value,
                    label: labelNode ? labelNode.textContent : input.value,
                    position: index
                };
            }
        );
    }

    function getSearchPeriodState(form) {
        if (!form) {
            return { startDate: '', endDate: '' };
        }

        var startInput = form.querySelector('[data-topbar-search-period-start]');
        var endInput = form.querySelector('[data-topbar-search-period-end]');
        return {
            startDate: startInput ? String(startInput.value || '') : '',
            endDate: endInput ? String(endInput.value || '') : ''
        };
    }

    function initializeSearchPeriod(form) {
        if (!form) {
            return;
        }

        var period = form.querySelector('[data-topbar-search-period]');
        if (!period || period.getAttribute('data-topbar-search-period-bound') === '1') {
            return;
        }

        var startInput = period.querySelector('[data-topbar-search-period-start]');
        var endInput = period.querySelector('[data-topbar-search-period-end]');
        var startSlider = period.querySelector('[data-topbar-search-period-start-slider]');
        var endSlider = period.querySelector('[data-topbar-search-period-end-slider]');
        if (!startInput || !endInput || !startSlider || !endSlider) {
            return;
        }

        function toDay(value) {
            var match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return match ? Math.floor(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])) / 86400000) : null;
        }

        function toDate(day) {
            var date = new Date(Number(day) * 86400000);
            return date.getUTCFullYear() + '-' + String(date.getUTCMonth() + 1).padStart(2, '0') + '-' + String(date.getUTCDate()).padStart(2, '0');
        }

        var minDay = toDay(startInput.min);
        var maxDay = toDay(startInput.max);
        if (minDay === null || maxDay === null || minDay > maxDay) {
            return;
        }

        function renderYearMarks() {
            var yearContainer = period.querySelector('[data-topbar-search-period-years]');
            if (!yearContainer) {
                return;
            }

            var marks = [];
            var seenYears = {};
            var minYear = new Date(minDay * 86400000).getUTCFullYear();
            var maxYear = new Date(maxDay * 86400000).getUTCFullYear();

            function addMark(year, day) {
                if (seenYears[year]) {
                    return;
                }
                seenYears[year] = true;
                marks.push({ year: year, day: day });
            }

            addMark(minYear, minDay);
            for (var year = minYear + 1; year <= maxYear; year += 1) {
                var yearDay = Math.floor(Date.UTC(year, 0, 1) / 86400000);
                if (yearDay >= minDay && yearDay <= maxDay) {
                    addMark(year, yearDay);
                }
            }

            if (!seenYears[maxYear]) {
                addMark(maxYear, maxDay);
            }

            yearContainer.innerHTML = '';
            marks.forEach(function (mark) {
                var label = document.createElement('span');
                var ratio = (mark.day - minDay) / Math.max(1, maxDay - minDay);
                label.className = 'common-topbar__search-period-year';
                label.style.left = String(Math.max(0, Math.min(1, ratio)) * 100) + '%';
                label.textContent = String(mark.year);
                yearContainer.appendChild(label);
            });
        }

        function clampDay(value) {
            return Math.max(minDay, Math.min(maxDay, value));
        }

        function dayToSlider(day) {
            return Math.round(((clampDay(day) - minDay) / Math.max(1, maxDay - minDay)) * 1000);
        }

        function sliderToDay(value) {
            return clampDay(minDay + Math.round((Number(value) / 1000) * (maxDay - minDay)));
        }

        function syncFromDates(changed) {
            var startDay = toDay(startInput.value);
            var endDay = toDay(endInput.value);
            startDay = startDay === null ? minDay : clampDay(startDay);
            endDay = endDay === null ? maxDay : clampDay(endDay);
            if (startDay > endDay) {
                if (changed === 'end') {
                    startDay = endDay;
                } else {
                    endDay = startDay;
                }
            }
            startInput.value = toDate(startDay);
            endInput.value = toDate(endDay);
            startSlider.value = String(dayToSlider(startDay));
            endSlider.value = String(dayToSlider(endDay));
        }

        function syncFromSliders(changed) {
            var startDay = sliderToDay(startSlider.value);
            var endDay = sliderToDay(endSlider.value);
            if (startDay > endDay) {
                if (changed === 'end') {
                    startDay = endDay;
                    startSlider.value = String(dayToSlider(startDay));
                } else {
                    endDay = startDay;
                    endSlider.value = String(dayToSlider(endDay));
                }
            }
            startInput.value = toDate(startDay);
            endInput.value = toDate(endDay);
        }

        startInput.addEventListener('change', function () { syncFromDates('start'); });
        endInput.addEventListener('change', function () { syncFromDates('end'); });
        startSlider.addEventListener('input', function () { syncFromSliders('start'); });
        endSlider.addEventListener('input', function () { syncFromSliders('end'); });
        renderYearMarks();
        syncFromDates('start');
        period.setAttribute('data-topbar-search-period-bound', '1');
    }

    window.commonTopbarInitializeSearchPeriod = initializeSearchPeriod;

    function handleSearchSubmit(event) {
        event.preventDefault();

        var config = getConfig();
        var form = event.target && event.target.matches('[data-topbar-search-form]')
            ? event.target
            : document.querySelector('[data-topbar-search-form]');
        var input = form ? form.querySelector('[data-topbar-search-input]') : document.querySelector('[data-topbar-search-input]');
        var query = input ? input.value.trim() : '';
        var searchState = {
            query: query,
            scopes: readSelectedSearchScopes(form),
            dateRange: getSearchPeriodState(form),
            config: config
        };

        closeMenus();

        if (callNamedFunction(config.search && config.search.callback, query, config, searchState)) {
            return;
        }

        window.dispatchEvent(new CustomEvent('common-topbar-search', {
            detail: searchState
        }));
    }

    function handleHelpItemClick(item) {
        closeMenus();

        if (callNamedFunction(item.callback, item, getConfig())) {
            return;
        }

        if (item.url) {
            if (item.mode === 'drawer') {
                openDrawer(
                    item.title || item.label || getConfigTextValue('translations.helpFallbackLabel', 'Aide'),
                    item.url,
                    item.contentMode === 'html' ? 'html' : (item.contentMode === 'fetch' ? 'fetch' : 'iframe')
                );
                return;
            }
            openModal(
                item.title || item.label || getConfigTextValue('translations.helpFallbackLabel', 'Aide'),
                item.url,
                item.mode === 'fetch' ? 'fetch' : (item.mode === 'iframe' ? 'iframe' : 'html')
            );
            return;
        }

        openModal(
            item.title || item.label || getConfigTextValue('translations.helpFallbackLabel', 'Aide'),
            item.html || getConfigTextValue('translations.helpPendingHtml', '<p>Contenu a venir.</p>'),
            'html'
        );
    }

    function shouldBypassHelpLinkInterception(event, link) {
        if (!event || !link) {
            return true;
        }

        if (event.defaultPrevented || event.button !== 0) {
            return true;
        }

        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return true;
        }

        var target = link.getAttribute('target');

        return !!target && target.toLowerCase() !== '_self';
    }

    function handleProfileEdit() {
        var config = getConfig();
        var profile = config.profile || {};
        closeMenus();

        if (callNamedFunction(profile.editCallback, profile, config)) {
            return;
        }

        if (profile.editUrl) {
            openModal(
                profile.editTitle || getConfigTextValue('profile.editTitle', 'Profil'),
                profile.editUrl,
                profile.editMode === 'fetch' ? 'fetch' : (profile.editMode === 'html' ? 'html' : 'iframe')
            );
        }
    }

    function handleBugReport() {
        var config = getConfig();
        var bugReport = config.bugReport || {};
        closeMenus();

        if (callNamedFunction(bugReport.callback, bugReport, config)) {
            return;
        }

        if (bugReport.url) {
            openModal(
                bugReport.title || getConfigTextValue('bugReport.title', 'Signaler un bug'),
                bugReport.url,
                bugReport.mode === 'fetch' ? 'fetch' : (bugReport.mode === 'html' ? 'html' : 'iframe')
            );
            return;
        }

        openModal(
            getConfigTextValue('bugReport.title', 'Signaler un bug'),
            getConfigTextValue('translations.bugReportUnavailableHtml', '<p>Formulaire indisponible.</p>'),
            'html'
        );
    }

    function appendCurrentRouteContextToUrl(url) {
        var rawUrl = String(url || '').trim();
        var base;
        var currentUrl;
        var path;
        var currentMatch;
        var legacyMatch;
        var resolvedCid = 0;

        if (rawUrl === '') {
            return '';
        }

        try {
            base = new URL(rawUrl, (typeof document !== 'undefined' && typeof document.baseURI === 'string' && document.baseURI.trim() !== '')
                ? document.baseURI
                : window.location.href);
        } catch (error) {
            return rawUrl;
        }

        if (window.omoConfig && Number(window.omoConfig.oid || 0) > 0 && !base.searchParams.has('oid')) {
            base.searchParams.set('oid', String(Number(window.omoConfig.oid || 0)));
        }

        try {
            currentUrl = new URL(window.location.href);
            resolvedCid = Number(currentUrl.searchParams.get('cid') || 0);
            if (resolvedCid <= 0) {
                path = currentUrl.pathname || '';
                currentMatch = path.match(/\/omo(?:\/c\/(\d+))?$/);
                legacyMatch = path.match(/\/omo\/o\/(\d+)(?:\/c\/(\d+))?$/);

                if (currentMatch && currentMatch[1]) {
                    resolvedCid = Number(currentMatch[1] || 0);
                } else if (legacyMatch && legacyMatch[2]) {
                    resolvedCid = Number(legacyMatch[2] || 0);
                }
            }
        } catch (error) {
        }

        if (typeof window.omoNormalizeRouteCid === 'function') {
            resolvedCid = Number(window.omoNormalizeRouteCid(resolvedCid) || 0);
        }

        if (resolvedCid > 0 && !base.searchParams.has('cid')) {
            base.searchParams.set('cid', String(resolvedCid));
        }

        return base.pathname + base.search + base.hash;
    }

    function handleTensionReport() {
        var config = getConfig();
        var tension = config.tension || {};
        var targetUrl = tension.url || '';
        closeMenus();

        if (callNamedFunction(tension.callback, tension, config)) {
            return;
        }

        if (tension.appendCurrentRouteContext) {
            targetUrl = appendCurrentRouteContextToUrl(targetUrl);
        }

        if (targetUrl) {
            openModal(
                tension.title || getConfigTextValue('tension.title', 'Declarer une tension'),
                targetUrl,
                tension.mode === 'fetch' ? 'fetch' : (tension.mode === 'html' ? 'html' : 'iframe')
            );
            return;
        }

        openModal(
            getConfigTextValue('tension.title', 'Declarer une tension'),
            getConfigTextValue('translations.tensionUnavailableHtml', '<p>Formulaire indisponible.</p>'),
            'html'
        );
    }

    function handleLogout() {
        var config = getConfig();
        var target = (config.logoutReturnTo || window.location.pathname || '/');

        fetch((config.logoutPath || '/common/logout.php') + '?return_to=' + encodeURIComponent(target), {
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function () {
                window.location.href = target;
            })
            .catch(function () {
                window.location.href = target;
            });
    }

    function handleAdminModeToggle(button) {
        var targetUrl = String(button && button.getAttribute('data-admin-mode-url') || '').trim();
        var organizationId = String(button && button.getAttribute('data-admin-mode-organization-id') || '').trim();

        if (!button || targetUrl === '') {
            return;
        }

        var formData = new FormData();
        formData.append('enabled', button.getAttribute('data-admin-mode-enabled') === '1' ? '1' : '0');
        formData.append('return_to', window.location.pathname + window.location.search);
        if (organizationId !== '') {
            formData.append('organization_id', organizationId);
        }

        button.disabled = true;

        fetch(targetUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
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
                    throw new Error('admin_mode_toggle_failed');
                }

                window.location.reload();
            })
            .catch(function () {
                button.disabled = false;
            });
    }

    function handleLanguageChange(select) {
        if (!select) {
            return;
        }

        if (typeof window.sharedSetLanguagePreference === 'function') {
            window.sharedSetLanguagePreference(select.value, true);
            return;
        }

        if (String(select.value || '').toLowerCase() === 'system') {
            document.cookie = [
                'lang=',
                'path=/',
                'expires=Thu, 01 Jan 1970 00:00:00 GMT',
                'SameSite=Lax'
            ].join('; ');
        } else {
            document.cookie = [
                'lang=' + encodeURIComponent(String(select.value || '').toLowerCase()),
                'path=/',
                'max-age=' + String(365 * 24 * 60 * 60),
                'SameSite=Lax'
            ].join('; ');
        }
        window.location.reload();
    }

    function getThemePreference() {
        if (typeof window.sharedGetThemePreference === 'function') {
            return window.sharedGetThemePreference();
        }

        try {
            var storedPreference = window.localStorage.getItem('omo-theme-preference');
            if (storedPreference === 'light' || storedPreference === 'dark' || storedPreference === 'system') {
                return storedPreference;
            }
        } catch (error) {
        }

        return 'system';
    }

    function getColorStylePreference() {
        if (typeof window.sharedGetColorStylePreference === 'function') {
            return window.sharedGetColorStylePreference();
        }

        try {
            var storedPreference = window.localStorage.getItem('omo-color-style-preference');
            if (storedPreference === 'mono' || storedPreference === 'turquoise' || storedPreference === 'ocean-blue') {
                return storedPreference;
            }
        } catch (error) {
        }

        return 'mono';
    }

    function syncPreferenceOptions(selector, value) {
        Array.prototype.forEach.call(
            document.querySelectorAll(selector),
            function (option) {
                var optionValue = option.getAttribute(selector === '[data-topbar-theme-option]'
                    ? 'data-topbar-theme-option'
                    : 'data-topbar-color-style-option');

                option.classList.toggle('is-active', optionValue === value);
            }
        );
    }

    function applyThemePreference(preference, persistPreference) {
        var safePreference = (preference === 'light' || preference === 'dark' || preference === 'system')
            ? preference
            : 'system';
        var resolvedTheme = safePreference;

        if (persistPreference) {
            try {
                window.localStorage.setItem('omo-theme-preference', safePreference);
            } catch (error) {
            }
        }

        if (typeof window.sharedApplyDocumentTheme === 'function') {
            resolvedTheme = window.sharedApplyDocumentTheme({
                preference: safePreference
            }).theme;
        } else {
            var root = document.documentElement;
            var prefersDark = typeof window.matchMedia === 'function'
                && window.matchMedia('(prefers-color-scheme: dark)').matches;

            resolvedTheme = safePreference === 'system'
                ? (prefersDark ? 'dark' : 'light')
                : safePreference;

            root.dataset.themePreference = safePreference;
            root.dataset.theme = resolvedTheme;
            root.style.colorScheme = resolvedTheme;
        }

        Array.prototype.forEach.call(
            document.querySelectorAll('[data-omo-theme-select]'),
            function (select) {
                select.value = safePreference;
            }
        );

        syncPreferenceOptions('[data-topbar-theme-option]', safePreference);

        window.dispatchEvent(new CustomEvent('omo-theme-change', {
            detail: {
                preference: safePreference,
                theme: resolvedTheme
            }
        }));

        if (typeof window.sharedBroadcastThemeToChildFrames === 'function') {
            window.sharedBroadcastThemeToChildFrames({
                preference: safePreference,
                colorStyle: getColorStylePreference()
            });
        }
    }

    function handleThemeChange(select) {
        if (!select) {
            return;
        }

        applyThemePreference(select.value, true);
    }

    function applyColorStylePreference(preference, persistPreference) {
        var safePreference = 'mono';
        var appliedState = null;

        if (preference === 'turquoise' || preference === 'ocean-blue') {
            safePreference = preference;
        }

        if (persistPreference) {
            try {
                window.localStorage.setItem('omo-color-style-preference', safePreference);
            } catch (error) {
            }
        }

        if (typeof window.sharedApplyDocumentTheme === 'function') {
            appliedState = window.sharedApplyDocumentTheme({
                preference: getThemePreference(),
                colorStyle: safePreference
            });
        } else {
            document.documentElement.dataset.colorStyle = safePreference;
        }

        syncPreferenceOptions('[data-topbar-color-style-option]', safePreference);

        window.dispatchEvent(new CustomEvent('omo-color-style-change', {
            detail: {
                colorStyle: safePreference,
                theme: appliedState && appliedState.theme ? appliedState.theme : document.documentElement.dataset.theme
            }
        }));

        if (typeof window.sharedBroadcastThemeToChildFrames === 'function') {
            window.sharedBroadcastThemeToChildFrames({
                preference: getThemePreference(),
                colorStyle: safePreference
            });
        }
    }

    function togglePreferenceMenu(trigger) {
        var container = trigger && trigger.closest ? trigger.closest('[data-topbar-preference-menu]') : null;
        var popup = container ? container.querySelector('[data-topbar-preference-popup]') : null;
        var shouldOpen;

        if (!container || !popup) {
            return;
        }

        shouldOpen = !container.classList.contains('is-open');
        closePreferenceMenus(container);
        container.classList.toggle('is-open', shouldOpen);
        popup.hidden = !shouldOpen;
        trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
    }

    function bindPreferenceSelects(root) {
        var scope = root || document;

        Array.prototype.forEach.call(
            scope.querySelectorAll('[data-topbar-language-select]'),
            function (select) {
                if (!select || select.dataset.topbarLanguageReady === '1') {
                    return;
                }

                select.dataset.topbarLanguageReady = '1';
                select.addEventListener('change', function () {
                    handleLanguageChange(select);
                });
            }
        );

        Array.prototype.forEach.call(
            scope.querySelectorAll('[data-omo-theme-select]'),
            function (select) {
                if (!select || select.dataset.topbarThemeReady === '1') {
                    return;
                }

                select.dataset.topbarThemeReady = '1';
                select.value = getThemePreference();
                select.addEventListener('change', function () {
                    handleThemeChange(select);
                });
            }
        );

        syncPreferenceOptions('[data-topbar-theme-option]', getThemePreference());
        syncPreferenceOptions('[data-topbar-color-style-option]', getColorStylePreference());
    }

    document.addEventListener('click', function (event) {
        var remoteRetry = event.target.closest('[data-topbar-remote-retry]');
        if (remoteRetry) {
            event.preventDefault();
            var remoteContainer = remoteRetry.closest('[data-topbar-remote-container]');
            var remoteUrl = remoteContainer ? remoteContainer.getAttribute('data-topbar-remote-url') || '' : '';
            if (remoteContainer && remoteUrl) {
                renderRemoteContent(remoteContainer, remoteUrl);
            }
            return;
        }

        var preferenceTrigger = event.target.closest('[data-topbar-preference-trigger]');
        if (preferenceTrigger) {
            event.preventDefault();
            togglePreferenceMenu(preferenceTrigger);
            return;
        }

        var languageOption = event.target.closest('[data-topbar-language-option]');
        if (languageOption) {
            event.preventDefault();
            handleLanguageChange({
                value: languageOption.getAttribute('data-topbar-language-option')
            });
            closePreferenceMenus();
            return;
        }

        var themeOption = event.target.closest('[data-topbar-theme-option]');
        if (themeOption) {
            event.preventDefault();
            applyThemePreference(themeOption.getAttribute('data-topbar-theme-option'), true);
            closePreferenceMenus();
            return;
        }

        var colorStyleOption = event.target.closest('[data-topbar-color-style-option]');
        if (colorStyleOption) {
            event.preventDefault();
            applyColorStylePreference(colorStyleOption.getAttribute('data-topbar-color-style-option'), true);
            closePreferenceMenus();
            return;
        }

        var trigger = event.target.closest('[data-topbar-menu-trigger]');
        if (trigger) {
            var name = trigger.getAttribute('data-topbar-menu-trigger');
            var menu = document.querySelector('[data-topbar-menu="' + name + '"]');
            var isOpen = menu && menu.classList.contains('is-open');
            closeMenus();
            if (menu && !isOpen) {
                menu.classList.add('is-open');
                if (name === 'search') {
                    renderSearchScopes(menu);
                    initializeSearchPeriod(menu.querySelector('[data-topbar-search-form]'));
                    focusSearchInput(menu.querySelector('[data-topbar-search-input]'));
                }
            }
            return;
        }

        var helpItem = event.target.closest('[data-topbar-help-item]');
        if (helpItem) {
            try {
                handleHelpItemClick(JSON.parse(helpItem.getAttribute('data-topbar-help-item')));
            } catch (e) {
                handleHelpItemClick({
                    label: getConfigTextValue('translations.helpFallbackLabel', 'Aide'),
                    html: getConfigTextValue('translations.helpUnavailableHtml', '<p>Contenu indisponible.</p>')
                });
            }
            return;
        }

        var helpLink = event.target.closest('[data-topbar-help-link-item]');
        if (helpLink) {
            if (shouldBypassHelpLinkInterception(event, helpLink)) {
                return;
            }

            event.preventDefault();
            try {
                handleHelpItemClick(JSON.parse(helpLink.getAttribute('data-topbar-help-link-item')));
            } catch (e) {
                window.location.href = helpLink.getAttribute('href') || '/';
            }
            return;
        }

        if (event.target.closest('[data-topbar-profile-edit]')) {
            handleProfileEdit();
            return;
        }

        var adminModeToggle = event.target.closest('[data-topbar-admin-mode-toggle]');
        if (adminModeToggle) {
            event.preventDefault();
            handleAdminModeToggle(adminModeToggle);
            return;
        }

        if (event.target.closest('[data-topbar-bug-report]')) {
            handleBugReport();
            return;
        }

        if (event.target.closest('[data-topbar-tension-report]')) {
            handleTensionReport();
            return;
        }

        if (event.target.closest('[data-topbar-logout]')) {
            handleLogout();
            return;
        }

        if (event.target.closest('[data-topbar-modal-close]')) {
            closeModal();
            return;
        }

        if (event.target.closest('[data-topbar-drawer-close]')) {
            closeDrawer();
            return;
        }

        if (event.target.closest('[data-topbar-language-select]') || event.target.closest('[data-omo-theme-select]')) {
            return;
        }

        if (event.target.closest('[data-topbar-preference-menu]')) {
            return;
        }

        if (!event.target.closest('.common-topbar__menu-wrap')) {
            closeMenus();
            return;
        }

        closePreferenceMenus();
    });

    document.addEventListener('submit', function (event) {
        if (event.target.matches('[data-topbar-search-form]')) {
            handleSearchSubmit(event);
        }
    });

    document.addEventListener('change', function (event) {
        var select = event.target.closest('[data-topbar-language-select]');

        if (select) {
            handleLanguageChange(select);
            return;
        }

        select = event.target.closest('[data-omo-theme-select]');
        if (select) {
            handleThemeChange(select);
        }
    });

    window.commonTopbarOpenModal = openModal;
    window.commonTopbarCloseModal = closeModal;
    window.commonTopbarOpenDrawer = openDrawer;
    window.commonTopbarCloseDrawer = closeDrawer;
    window.commonTopbarRefreshUserProfile = notifyUserProfileChanged;
    window.commonTopbarRefreshModalContent = function (url) {
        var body = document.getElementById('commonTopbarModalBody');
        if (!body || !url) {
            return;
        }

        renderRemoteContent(body, url);
    };
    window.commonTopbarRefreshDrawerContent = function (url) {
        var body = document.getElementById('commonTopbarDrawerBody');
        if (!body || !url) {
            return;
        }

        renderRemoteContent(body, url);
    };

    if (typeof window.sharedApplyDocumentTheme === 'function') {
        window.sharedApplyDocumentTheme({
            preference: getThemePreference(),
            colorStyle: getColorStylePreference()
        });
    } else {
        document.documentElement.dataset.colorStyle = getColorStylePreference();
    }

    bindPreferenceSelects(document);
})();
