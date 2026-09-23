// Build each view/scope only on first use; authorized event data stays in the payload.
function omoCreateCalendarViews(root, config) {
    const host = root.querySelector('[data-omo-calendar-views]');
    const built = new Set();
    const escape = value => String(value == null ? '' : value).replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]));
    const text = key => escape(config.labels['calendar.' + key] || '');
    const tag = (name, css, attributes, html) => '<' + name + (css ? ' class="' + escape(css) + '"' : '')
        + Object.entries(attributes || {}).map(([key, value]) => ' ' + key + '="' + escape(value) + '"').join('')
        + '>' + (html || '') + '</' + name + '>';
    const attr = (key, value = '') => ({['data-omo-calendar-' + key]: value});
    const span = (css, value) => tag('span', css, {}, escape(value));
    const item = id => config.items[id];
    function eventTag(name, css, event, attributes, content) {
        css += ' is-status-' + event.status;
        [['isExternal', 'is-external-calendar'], ['isFaded', 'is-faded'], ['isRouteTarget', 'is-route-target'],
            ['isOtherOrganization', 'is-other-organization'], ['isOutsideScope', 'is-outside-scope']].forEach(([key, cls]) => { if (event[key]) css += ' ' + cls; });
        attributes = Object.assign(attr('event-id', event.id), attr('search-item'), attributes);
        if (event.isOtherOrganization) Object.assign(attributes, attr('other-organization'));
        if (event.isExternal) {
            Object.assign(attributes, attr('external-event'), attr('external-event-data', JSON.stringify(event.externalDrawerData || {})));
            // CSS color is the only non-numeric style value coming from event data.
            if (/^#[0-9a-f]{6}$/i.test(event.externalColor || '')) attributes.style = '--param-external-calendar-color: ' + event.externalColor + ';' + (attributes.style || '');
        }
        return tag(name, css, attributes, content);
    }
    function documentButton(event) {
        if (!event.documentUrl) return '';
        return tag('button', 'omo-calendar__document-link', Object.assign({type: 'button', title: event.documentTitle,
            'aria-label': config.labels['calendar.action.open_document']}, attr('open-url', event.documentUrl),
        attr('open-url-title', event.documentTitle), attr('open-pv-editor-url', event.documentPvEditorUrl)),
        '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M6 3.5h8l4 4v13H6z"></path><path d="M14 3.5v4h4M9 12h6M9 15.5h6"></path></svg>');
    }
    function timeRow(event, prefix) {
        return event.timeLabel || event.documentUrl ? tag('span', prefix + '-row', {},
            (event.timeLabel ? span(prefix, event.timeLabel) : '') + documentButton(event)) : '';
    }
    function badge(count, label) {
        return tag('span', 'omo-calendar__timeline-count-badge', {'aria-label': label, title: label}, escape(count));
    }
    function toolbar(view, timeline) {
        function button(direction, path) {
            const label = config.labels['calendar.navigation.' + direction];
            return tag('button', 'generic-action-button generic-action-button--icon-only generic-action-button--quiet-icon',
                Object.assign({type: 'button', 'aria-label': label, title: label}, attr('nav-url', view[direction === 'previous' ? 'prevUrl' : 'nextUrl'])),
                '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="' + path + '"></path></svg>');
        }
        return tag('div', 'omo-calendar__toolbar', {}, button('previous', 'm14 6-6 6 6 6')
            + tag('div', 'omo-calendar__period-title' + (timeline ? ' omo-calendar__period-title--compact' : ''), {},
                tag('strong', '', {}, escape(view.title)) + (timeline ? badge(view.count, view.subtitle) : span('', view.subtitle)))
            + button('next', 'm10 6 6 6-6 6'));
    }
    function month(view) {
        const days = view.days.map(day => {
            const events = day.items.map((id, index) => {
                const event = item(id);
                return eventTag('div', 'omo-calendar__event-chip', event, index >= 3 ? Object.assign({hidden: ''}, attr('overflow-event')) : {},
                    timeRow(event, 'omo-calendar__event-time') + span('omo-calendar__event-title', event.title)
                    + (event.holonLabel ? span('omo-calendar__event-holon', event.holonLabel) : ''));
            }).join('');
            const more = day.more ? tag('button', 'omo-calendar__more', Object.assign({type: 'button', 'aria-expanded': 'false'}, attr('more')), escape(day.more)) : '';
            return tag('div', 'omo-calendar__cell' + (day.outside ? ' is-outside' : '') + (day.isToday ? ' is-today' : ''), attr('day', day.dayKey),
                tag('div', 'omo-calendar__cell-head', {}, span('omo-calendar__cell-day', day.label)) + tag('div', 'omo-calendar__cell-items', {}, events + more));
        }).join('');
        return tag('div', 'omo-calendar__month-scroll', {},
            tag('div', 'omo-calendar__month-sticky', {}, toolbar(view, false) + tag('div', 'omo-calendar__weekday-row', {},
                config.weekdays.map(day => tag('div', 'omo-calendar__weekday', {}, escape(day))).join('')))
            + tag('div', 'omo-calendar__grid', {}, days));
    }
    function timeline(view, mode) {
        const head = view.days.map(day => tag('div', 'omo-calendar__time-day-header' + (day.isToday ? ' is-today' : ''), attr('day', day.dayKey),
            tag('strong', '', {}, escape(day.label)) + badge(day.count, day.countLabel))).join('');
        const allDay = view.days.map(day => tag('div', 'omo-calendar__time-all-day-cell' + (day.isToday ? ' is-today' : ''), attr('day', day.dayKey),
            day.allDay.map(id => {
                const event = item(id);
                return eventTag('div', 'omo-calendar__time-all-day-chip', event, {}, tag('div', 'omo-calendar__time-all-day-title-row', {},
                    tag('strong', '', {}, escape(event.title)) + documentButton(event)) + (event.holonLabel ? span('', event.holonLabel) : ''));
            }).join(''))).join('');
        const columns = view.days.map(day => tag('div', 'omo-calendar__time-column' + (day.isToday ? ' is-today' : ''), attr('time-column-day', day.dayKey),
            tag('div', 'omo-calendar__time-column-grid', {}, '') + tag('div', 'omo-calendar__now-indicator',
                Object.assign({hidden: '', title: config.labels['calendar.axis.now']}, attr('now-indicator'), attr('now-day', day.dayKey)), '<span aria-hidden="true"></span>')
            + day.timed.map(id => {
                const event = item(id);
                const top = Math.max(0, Math.min(100, event.startMinute / 1440 * 100));
                const height = Math.min(100 - top, Math.max(30 / 1440 * 100, (event.endMinute - event.startMinute) / 1440 * 100));
                const width = 100 / Math.max(1, Number(event.columnCount) || 1);
                const left = Math.max(0, Number(event.column) || 0) * width;
                return eventTag('article', 'omo-calendar__time-event', event, {style: 'top:' + top + '%;height:' + height + '%;left:calc(' + left + '% + 4px);width:calc(' + width + '% - 8px);'},
                    tag('strong', 'omo-calendar__time-event-title', {}, escape(event.title)) + timeRow(event, 'omo-calendar__time-event-time')
                    + (event.holonLabel ? span('omo-calendar__time-event-context', event.holonLabel) : ''));
            }).join(''))).join('');
        return toolbar(view, true) + tag('div', 'omo-calendar__time-view', Object.assign({style: '--omo-calendar-time-columns:' + view.columnCount + ';'}, attr('time-view', mode)),
            tag('div', 'omo-calendar__time-sticky', attr('time-sticky'),
                tag('div', 'omo-calendar__time-head', {}, tag('div', 'omo-calendar__time-axis-spacer', {}, '') + head)
                + tag('div', 'omo-calendar__time-all-day', {}, tag('div', 'omo-calendar__time-axis-label', {}, text('axis.all_day')) + allDay))
            + tag('div', 'omo-calendar__time-body', {}, tag('div', 'omo-calendar__time-axis', {}, Object.entries(config.hours).map(([hour, label]) =>
                tag('div', 'omo-calendar__time-hour-label', attr('hour-index', hour), escape(label))).join('')) + columns));
    }
    function menu(event) {
        if (!event.canEdit && !event.canDelete) return '';
        let actions = event.canEdit ? tag('button', 'generic-menu-item', Object.assign({type: 'button', role: 'menuitem'}, attr('open-edit-url', event.editUrl)), text('action.edit')) : '';
        if (event.canDelete) {
            const attributes = Object.assign({type: 'button', role: 'menuitem'}, attr('delete-url', event.deleteUrl), attr('delete-has-documents', event.hasAssociatedDocuments ? '1' : '0'));
            [['confirm', 'confirm.delete'], ['error', 'error.delete'], ['documents-title', 'delete.documents.title'], ['documents-question', 'delete.documents.question'],
                ['documents-yes', 'delete.documents.yes'], ['documents-no', 'delete.documents.no']].forEach(([key, label]) => Object.assign(attributes, attr('delete-' + key, config.labels['calendar.' + label])));
            actions += tag('button', 'generic-menu-item generic-menu-item--danger', attributes, text('action.delete'));
        }
        return tag('div', 'omo-calendar__event-menu generic-menu generic-file-list__menu', attr('event-menu'),
            tag('button', 'generic-menu-toggle generic-file-list__menu-toggle', Object.assign({type: 'button', 'aria-haspopup': 'menu', 'aria-expanded': 'false', 'aria-label': config.labels['calendar.action.more']}, attr('event-menu-toggle')), '...')
            + tag('div', 'generic-menu-panel', Object.assign({role: 'menu', hidden: ''}, attr('event-menu-panel')), actions));
    }
    function list(view) {
        if (!view.sections.length) return tag('div', 'omo-empty-state', attr('default-empty'), text('empty.list'));
        const columns = ['date', 'event', 'schedule', 'context'];
        return tag('div', 'omo-calendar__results generic-file-list generic-file-list--structured generic-file-list--stacked-sticky', {}, view.sections.map(section => {
            const header = tag('div', 'omo-calendar__list-header generic-file-list__header', {}, columns.map(column => tag('div', 'omo-calendar__list-header-cell generic-file-list__header-cell', {}, text('list.column.' + column))).join(''));
            const events = section.items.map(id => {
                const event = item(id);
                const cell = (column, css, html) => tag('div', css, {'data-label': config.labels['calendar.list.column.' + column]}, html);
                const title = tag('div', 'omo-calendar__list-title-row generic-file-list__title-row', {}, tag('strong', 'omo-calendar__list-title generic-file-list__title', {}, escape(event.title))
                    + (event.statusLabel ? span('omo-calendar__list-status generic-file-list__count', event.statusLabel) : ''));
                const row = cell('date', 'omo-calendar__list-date generic-file-list__cell generic-file-list__cell--date', span('omo-calendar__list-weekday', event.weekdayLabel) + tag('strong', '', {}, escape(event.dateLabel)))
                    + cell('event', 'omo-calendar__list-cell omo-calendar__list-cell--name generic-file-list__cell generic-file-list__cell--name',
                        tag('div', 'omo-calendar__list-name-main generic-file-list__name-main', {}, tag('div', 'omo-calendar__list-title-block generic-file-list__title-block', {}, title
                            + (event.description ? tag('div', 'omo-calendar__list-description generic-file-list__meta-line', {}, escape(event.description)) : ''))))
                    + cell('schedule', 'omo-calendar__list-cell generic-file-list__cell', timeRow(event, 'omo-calendar__list-time'))
                    + cell('context', 'omo-calendar__list-cell generic-file-list__cell', tag('div', 'omo-calendar__list-context generic-file-list__meta-line', {}, span('omo-calendar__list-holon', event.holonLabel || config.labels['calendar.context.organization'])));
                return eventTag('article', 'omo-calendar__item-shell generic-file-list__item-shell', event, {}, tag('div', 'omo-calendar__list-item generic-file-list__row', {}, row) + menu(event));
            }).join('');
            return tag('section', 'omo-calendar__group omo-panel-group generic-file-list__group', attr('search-group'),
                tag('h3', 'omo-panel-group__title generic-file-list__group-title', {}, escape(section.label)) + tag('div', 'omo-calendar__list generic-file-list__table', {}, header + events));
        }).join(''));
    }
    return function (mode, scope) {
        const key = scope + ':' + mode;
        const view = config.views[scope] && config.views[scope][mode];
        if (!view || built.has(key)) return null;
        const panel = root.ownerDocument.createElement(mode === 'list' ? 'div' : 'section');
        panel.className = 'omo-calendar__view-panel ' + (mode === 'list' ? 'omo-calendar__view-panel--list' : 'omo-calendar__panel omo-calendar__panel--' + (mode === 'month' ? 'month' : 'timeline'));
        panel.setAttribute('data-omo-calendar-view-panel', mode);
        panel.setAttribute('data-omo-calendar-view-scope', scope);
        if (mode === 'week' || mode === 'day') panel.setAttribute('data-omo-calendar-timeline-panel', mode);
        panel.innerHTML = mode === 'month' ? month(view) : mode === 'list' ? list(view) : timeline(view, mode);
        host.appendChild(panel);
        built.add(key);
        if (typeof window.initGenericComponents === 'function') window.initGenericComponents(panel);
        if (typeof window.syncGenericFileLists === 'function') window.syncGenericFileLists(panel);
        return panel;
    };
}

window.omoInitCalendar = function (root) {
        if (!root || root.dataset.omoCalendarReady === '1') {
            return;
        }

        var config = JSON.parse(root.querySelector('[data-omo-calendar-data]').textContent);
        var ensureCalendarView = omoCreateCalendarViews(root, config);

        root.dataset.omoCalendarReady = '1';

        var useLocalDrawerNavigation = typeof window.omoIsPvApplicationTabContext === 'function'
            && window.omoIsPvApplicationTabContext(root);

        var drawer = root.querySelector('[data-omo-calendar-editor-drawer]');
        var drawerBody = root.querySelector('[data-omo-calendar-editor-body]');
        var drawerTitle = root.querySelector('[data-omo-calendar-editor-title]');
        var drawerDescription = root.querySelector('[data-omo-calendar-editor-description]');
        var drawerActions = root.querySelector('[data-omo-calendar-editor-actions]');
        var defaultDrawerTitle = drawerTitle ? drawerTitle.textContent : '';
        var defaultDrawerDescription = drawerDescription ? drawerDescription.textContent : '';
        var currentUrl = root.getAttribute('data-omo-calendar-current-url') || '';
        var currentView = root.getAttribute('data-omo-calendar-view') || 'month';
        var calendarTimezone = root.getAttribute('data-omo-calendar-timezone') || '';
        function normalizeScopeName(scopeName) {
            var normalizedScope = String(scopeName || '').trim().toLowerCase();
            if (normalizedScope === 'global') {
                return 'descendants';
            }
            return normalizedScope === 'children' || normalizedScope === 'descendants' ? normalizedScope : 'contextual';
        }

        var currentScope = normalizeScopeName(root.getAttribute('data-omo-calendar-scope') || 'contextual');
        var canCreateEvent = root.getAttribute('data-omo-calendar-can-create') === '1';
        var calendarSavedViewsStorageKey = 'omo.calendar.saved-views.v2';
        var legacyCalendarSavedViewsStorageKey = 'omo.calendar.saved-views.v1';
        var calendarSessionViewsStorageKey = 'omo.calendar.session-views.v1';
        var calendarSessionPositionStorageKey = 'omo.calendar.session-position.v1';
        var calendarSearchStorageKey = 'omo.calendar.quick-search.v1';
        var currentSearch = '';
        var pendingDisplayFilters = null;
        var filterPanelOpen = false;
        var createUrl = root.getAttribute('data-omo-calendar-create-url') || '';
        var detailUrl = root.getAttribute('data-omo-calendar-detail-url') || '';
        var connectUrl = config.connectUrl;
        var externalEventDrawerText = config.externalEventDrawerText;
        var headerCount = root.querySelector('[data-omo-calendar-header-count]');
        var headerSummary = root.querySelector('[data-omo-calendar-header-summary]');
        var requestToken = 0;
        var initialOpenEventId = Number(root.getAttribute('data-omo-calendar-open-event-id') || '0');
        if (!Number.isInteger(initialOpenEventId) || initialOpenEventId <= 0) {
            initialOpenEventId = 0;
        }
        var initialOpenEventDrawerOpened = false;
        var calendarMenuOwnerDocument = root.ownerDocument || document;
        var floatingCalendarMenu = calendarMenuOwnerDocument.querySelector('[data-omo-calendar-floating-menu="1"]');
        if (!floatingCalendarMenu) {
            floatingCalendarMenu = calendarMenuOwnerDocument.createElement('div');
            floatingCalendarMenu.className = 'generic-menu-panel generic-menu-panel--floating';
            floatingCalendarMenu.setAttribute('data-omo-calendar-floating-menu', '1');
            floatingCalendarMenu.setAttribute('role', 'menu');
            floatingCalendarMenu.hidden = true;
            calendarMenuOwnerDocument.body.appendChild(floatingCalendarMenu);
        }
        var activeCalendarMenuToggle = null;

        function positionCalendarMenu(toggle) {
            if (!(toggle instanceof Element) || !toggle.isConnected) {
                closeCalendarMenus();
                return;
            }

            floatingCalendarMenu.hidden = false;
            floatingCalendarMenu.style.visibility = 'hidden';
            floatingCalendarMenu.style.top = '0px';
            floatingCalendarMenu.style.left = '0px';

            var toggleRect = toggle.getBoundingClientRect();
            var menuRect = floatingCalendarMenu.getBoundingClientRect();
            var viewportPadding = 12;
            var gap = 8;
            var top = toggleRect.bottom + gap;
            var left = toggleRect.right - menuRect.width;

            if (top + menuRect.height > window.innerHeight - viewportPadding) {
                top = Math.max(viewportPadding, toggleRect.top - menuRect.height - gap);
            }
            if (left + menuRect.width > window.innerWidth - viewportPadding) {
                left = Math.max(viewportPadding, window.innerWidth - menuRect.width - viewportPadding);
            }
            if (left < viewportPadding) {
                left = viewportPadding;
            }

            floatingCalendarMenu.style.top = String(Math.round(top)) + 'px';
            floatingCalendarMenu.style.left = String(Math.round(left)) + 'px';
            floatingCalendarMenu.style.visibility = '';
        }

        function closeCalendarMenus() {
            root.querySelectorAll('[data-omo-calendar-event-menu]').forEach(function (menu) {
                menu.classList.remove('is-open');
            });
            root.querySelectorAll('[data-omo-calendar-event-menu-toggle]').forEach(function (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            });
            activeCalendarMenuToggle = null;
            floatingCalendarMenu.hidden = true;
            floatingCalendarMenu.style.visibility = '';
            floatingCalendarMenu.replaceChildren();
        }

        var calendarHeaderMenu = root.querySelector('[data-omo-calendar-header-menu]');
        var calendarHeaderMenuToggle = root.querySelector('[data-omo-calendar-header-menu-toggle]');
        var calendarHeaderMenuPanel = root.querySelector('[data-omo-calendar-header-menu-panel]');

        var calendarMobileMenu = window.matchMedia('(max-width: 768px)');

        function closeCalendarHeaderMenu() {
            window.resetGenericExpandedMenu(calendarHeaderMenu);
        }
        closeCalendarHeaderMenu();

        function openCalendarConnectPopup(externalTab) {
            if (!connectUrl || typeof window.commonTopbarOpenModal !== 'function') {
                return;
            }

            closeCalendarHeaderMenu();
            fetch(resolveUrl(connectUrl + '&scope=' + encodeURIComponent(currentScope)), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to load calendar connection details');
                }
                return response.text();
            }).then(function (html) {
                var preview = document.createElement('div');
                preview.innerHTML = html;
                var popup = preview.querySelector('[data-omo-calendar-connect-popup]');
                var popupTitle = popup ? popup.getAttribute('data-omo-calendar-connect-title') : config.labels['calendar.action.connect'];

                window.commonTopbarOpenModal(popupTitle, html, 'html');
                var modalBody = document.getElementById('commonTopbarModalBody');
                if (modalBody) {
                    if (typeof window.initGenericComponents === 'function') {
                        window.initGenericComponents(modalBody);
                    }
                    initCalendarConnectControls(modalBody);
                    if (externalTab === true) {
                        var externalToggle = modalBody.querySelector('[data-generic-tab-target="omoCalendarConnectExternal"]');
                        if (externalToggle) { externalToggle.click(); }
                    }
                }
            }).catch(function () {
                window.commonTopbarOpenModal(config.labels['calendar.action.connect'], '<div class="generic-section">' + escapeCalendarHtml(config.labels['calendar.error.load_form']) + '</div>', 'html');
            });
        }

        function openMeetingSettings() {
            closeCalendarHeaderMenu();
            var title = config.labels['calendar.action.meeting'];
            window.commonTopbarOpenModal(title, '<p>' + escapeCalendarHtml(config.labels['calendar.action.meeting']) + '...</p>', 'html');
            fetch(resolveUrl('/omo/api/calendar/meeting_settings.php'), {credentials: 'same-origin'})
                .then(function (response) { if (!response.ok) { throw new Error('load'); } return response.text(); })
                .then(function (html) {
                    window.commonTopbarOpenModal(title, html, 'html');
                    var form = document.querySelector('#commonTopbarModalBody [data-meeting-settings]');
                    if (!form) { return; }
                    var text = JSON.parse(form.dataset.text);
                    var message = form.querySelector('[data-meeting-feedback]');
                    var slugStatus = form.querySelector('[data-meeting-slug-status]');
                    var enabledField = form.elements.enabled;
                    var enabledContent = form.querySelector('[data-meeting-enabled-content]');
                    function show(node, value, success) { node.textContent = value; node.classList.toggle('is-success', success); }
                    function syncMeetingEnabledContent() {
                        if (enabledContent && enabledField) { enabledContent.hidden = !enabledField.checked; }
                    }
                    function post(data) {
                        return fetch(resolveUrl(form.action), {method: 'POST', credentials: 'same-origin', body: data})
                            .then(function (response) { return response.json(); });
                    }
                    form.addEventListener('change', function () {
                        syncMeetingEnabledContent();
                        form.querySelectorAll('[data-meeting-day]').forEach(function (day) {
                            day.querySelector('[data-meeting-hours]').hidden = !day.querySelector('[data-meeting-open]').checked;
                            day.querySelector('[data-meeting-break]').hidden = !day.querySelector('[data-meeting-pause]').checked;
                        });
                    });
                    syncMeetingEnabledContent();
                    form.elements.slug.addEventListener('input', function () {
                        show(slugStatus, '', false);
                        form.querySelector('[data-meeting-link]').value = window.location.origin + '/meeting/' + form.elements.slug.value.trim().toLowerCase();
                    });
                    form.elements.slug.addEventListener('blur', function () {
                        var value = form.elements.slug.value.trim().toLowerCase();
                        form.elements.slug.value = value;
                        var data = new FormData(form); data.set('action', 'check');
                        post(data).then(function (result) {
                            if (form.elements.slug.value === value) { show(slugStatus, result.message, result.status); }
                        }).catch(function () { show(slugStatus, text.unavailable, false); });
                    });
                    form.querySelector('[data-meeting-copy]').addEventListener('click', function () {
                        navigator.clipboard.writeText(form.querySelector('[data-meeting-link]').value)
                            .then(function () { show(message, text.copied, true); })
                            .catch(function () { form.querySelector('[data-meeting-link]').select(); });
                    });
                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        var button = form.querySelector('[type="submit"]');
                        if (button.disabled) { return; }
                        var data = new FormData(form);
                        button.disabled = true; show(message, text.saving, true);
                        post(data).then(function (result) {
                            show(message, result.message, result.status);
                            if (result.status) { form.querySelector('[data-meeting-link]').value = window.location.origin + result.path; }
                        }).catch(function () { show(message, text.unavailable, false); })
                            .finally(function () { button.disabled = false; });
                    });
                }).catch(function () {
                    window.commonTopbarOpenModal(title, '<p>' + escapeCalendarHtml(config.labels['calendar.error.load_form']) + '</p>', 'html');
                });
        }

        if (calendarHeaderMenuToggle && calendarHeaderMenuPanel) {
            calendarHeaderMenuToggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var isOpen = !calendarHeaderMenuPanel.hidden;
                closeCalendarHeaderMenu();
                if (!isOpen) {
                    calendarHeaderMenu.classList.add('is-open');
                    calendarHeaderMenuToggle.setAttribute('aria-expanded', 'true');
                    calendarHeaderMenuPanel.hidden = false;
                }
            });
        }

        if (calendarHeaderMenu) {
            calendarHeaderMenu.addEventListener('keydown', function (event) {
                if (calendarMobileMenu.matches) { return; }
                var items = Array.from(calendarHeaderMenuPanel.querySelectorAll('[role="menuitem"]'));
                var index = items.indexOf(document.activeElement);
                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeCalendarHeaderMenu();
                    calendarHeaderMenuToggle.focus();
                } else if (['ArrowDown', 'ArrowUp', 'Home', 'End'].indexOf(event.key) >= 0) {
                    event.preventDefault();
                    calendarHeaderMenu.classList.add('is-open');
                    calendarHeaderMenuToggle.setAttribute('aria-expanded', 'true');
                    calendarHeaderMenuPanel.hidden = false;
                    var next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1
                        : (index + (event.key === 'ArrowUp' ? -1 : 1) + items.length) % items.length;
                    if (index < 0 && event.key === 'ArrowUp') { next = items.length - 1; }
                    if (items[next]) { items[next].focus(); }
                } else if (event.key === 'Tab') {
                    closeCalendarHeaderMenu();
                }
            });
        }

        root.addEventListener('click', function (event) {
            var openDocumentButton = event.target.closest('[data-omo-calendar-open-url]');
            if (openDocumentButton) {
                event.preventDefault();
                event.stopPropagation();
                var documentUrl = openDocumentButton.getAttribute('data-omo-calendar-open-url') || '';
                var documentTitle = openDocumentButton.getAttribute('data-omo-calendar-open-url-title') || 'Document';
                var documentPvEditorUrl = openDocumentButton.getAttribute('data-omo-calendar-open-pv-editor-url') || '';
                if (typeof window.omoOpenAssociatedDocumentResult === 'function') {
                    window.omoOpenAssociatedDocumentResult(
                        documentUrl,
                        documentTitle,
                        documentPvEditorUrl,
                        'omo-pv-preparation-calendar-'
                    );
                }
                return;
            }

            var connectButton = event.target.closest('[data-omo-calendar-open-connect]');
            if (connectButton) {
                event.preventDefault();
                openCalendarConnectPopup();
                return;
            }

            if (event.target.closest('[data-omo-calendar-open-meeting]')) {
                event.preventDefault(); openMeetingSettings(); return;
            }
            if (event.target.closest('[data-omo-calendar-open-share]')) {
                event.preventDefault();
                closeCalendarHeaderMenu();
                window.omoCalendarOpenShare(resolveUrl('/omo/api/calendar/share.php'),
                    config.labels['calendar.action.share'],
                    config.labels['calendar.error.load_form']);
                return;
            }

            if (calendarHeaderMenu && !calendarHeaderMenu.contains(event.target)) {
                closeCalendarHeaderMenu();
            }
        });

        function openCalendarMenu(toggle) {
            var menu = toggle ? toggle.closest('[data-omo-calendar-event-menu]') : null;
            var panel = menu ? menu.querySelector('[data-omo-calendar-event-menu-panel]') : null;
            var shouldOpen = !!toggle && !!panel && (activeCalendarMenuToggle !== toggle || floatingCalendarMenu.hidden);
            closeCalendarMenus();

            if (!shouldOpen) {
                return;
            }

            var fragment = calendarMenuOwnerDocument.createDocumentFragment();
            Array.prototype.forEach.call(panel.children, function (originalAction) {
                if (!(originalAction instanceof Element)) {
                    return;
                }

                var floatingAction = originalAction.cloneNode(true);
                floatingAction.setAttribute('data-omo-calendar-floating-menu-action', '1');
                fragment.appendChild(floatingAction);
            });
            floatingCalendarMenu.replaceChildren(fragment);
            if (!floatingCalendarMenu.childElementCount) {
                return;
            }

            activeCalendarMenuToggle = toggle;
            menu.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            positionCalendarMenu(toggle);
        }

        window.addEventListener('resize', function () {
            if (activeCalendarMenuToggle) {
                positionCalendarMenu(activeCalendarMenuToggle);
            }
        });
        calendarMenuOwnerDocument.addEventListener('scroll', function () {
            if (activeCalendarMenuToggle) {
                positionCalendarMenu(activeCalendarMenuToggle);
            }
        }, true);

        calendarMenuOwnerDocument.addEventListener('click', function (event) {
            var floatingAction = event.target.closest('[data-omo-calendar-floating-menu-action]');
            if (floatingAction) {
                event.preventDefault();
                event.stopPropagation();
                if (floatingAction.hasAttribute('data-omo-calendar-delete-url')) {
                    closeCalendarMenus();
                    deleteCalendarEvent(floatingAction);
                    return;
                }
                var editUrl = floatingAction.getAttribute('data-omo-calendar-open-edit-url') || '';
                closeCalendarMenus();
                if (editUrl) {
                    openDrawerWithUrl(editUrl);
                }
                return;
            }

            var toggle = event.target.closest('[data-omo-calendar-event-menu-toggle]');
            if (toggle && root.contains(toggle)) {
                event.preventDefault();
                event.stopPropagation();
                openCalendarMenu(toggle);
                return;
            }

            if (event.target.closest('[data-omo-calendar-floating-menu], [data-omo-calendar-event-menu]')) {
                return;
            }

            closeCalendarMenus();
        });

        function getCurrentRouteToken() {
            if (useLocalDrawerNavigation) {
                return '';
            }
            if (typeof window.omoParsePopupHashState !== 'function') {
                return '';
            }

            var hashState = window.omoParsePopupHashState();
            return hashState && hashState.routeToken
                ? String(hashState.routeToken)
                : '';
        }

        function buildEventRouteToken(eventId) {
            var resolvedEventId = Number(eventId || 0);
            if (!Number.isInteger(resolvedEventId) || resolvedEventId <= 0) {
                return null;
            }

            if (typeof window.omoBuildCalendarEventRouteToken === 'function') {
                return window.omoBuildCalendarEventRouteToken(resolvedEventId);
            }

            return 'calendar-e' + String(resolvedEventId);
        }

        function normalizeViewPreference(viewName) {
            var normalizedView = String(viewName || '').trim().toLowerCase();
            return normalizedView === 'week' || normalizedView === 'day' || normalizedView === 'list'
                ? normalizedView
                : 'month';
        }

        function getCalendarPreferencesContextKey() {
            var regularKey = String(root.getAttribute('data-omo-calendar-oid') || '0')
                + ':' + String(root.getAttribute('data-omo-calendar-cid') || '0');
            return typeof window.omoApplicationViewPreferencesGetStorageContextKey === 'function'
                ? window.omoApplicationViewPreferencesGetStorageContextKey(root, regularKey)
                : regularKey;
        }

        function createCalendarPreferences(preferences) {
            return {
                scope: normalizeScopeName(preferences && preferences.scope),
                view: normalizeViewPreference(preferences && preferences.view)
            };
        }

        function getCalendarPreferencesStore() {
            try {
                var storedValue = window.localStorage.getItem(calendarSavedViewsStorageKey);
                var savedViews = storedValue ? JSON.parse(storedValue) : null;
                if (savedViews && typeof savedViews === 'object' && savedViews.contexts && typeof savedViews.contexts === 'object') {
                    return {
                        defaultView: savedViews.defaultView && typeof savedViews.defaultView === 'object' ? savedViews.defaultView : null,
                        contexts: savedViews.contexts
                    };
                }

                var legacyValue = window.localStorage.getItem(legacyCalendarSavedViewsStorageKey);
                var legacyViews = legacyValue ? JSON.parse(legacyValue) : null;
                return {
                    defaultView: null,
                    contexts: legacyViews && typeof legacyViews === 'object' ? legacyViews : {}
                };
            } catch (error) {
                return {defaultView: null, contexts: {}};
            }
        }

        function saveCalendarPreferencesStore(store) {
            try {
                window.localStorage.setItem(calendarSavedViewsStorageKey, JSON.stringify({
                    defaultView: store.defaultView && typeof store.defaultView === 'object' ? store.defaultView : null,
                    contexts: store.contexts && typeof store.contexts === 'object' ? store.contexts : {}
                }));
            } catch (error) {
            }
        }

        function getCalendarStoredPreferences() {
            var preferences = getCalendarPreferencesStore().contexts[getCalendarPreferencesContextKey()];
            return preferences && typeof preferences === 'object' ? preferences : null;
        }

        function getCalendarDefaultPreferences() {
            return getCalendarPreferencesStore().defaultView;
        }

        function storeCalendarPreferences(preferences) {
            var store = getCalendarPreferencesStore();
            store.contexts[getCalendarPreferencesContextKey()] = createCalendarPreferences(preferences);
            saveCalendarPreferencesStore(store);
        }

        function storeCalendarDefaultPreferences(preferences) {
            var store = getCalendarPreferencesStore();
            store.defaultView = createCalendarPreferences(preferences);
            saveCalendarPreferencesStore(store);
        }

        function clearCalendarStoredPreferences() {
            var store = getCalendarPreferencesStore();
            delete store.contexts[getCalendarPreferencesContextKey()];
            saveCalendarPreferencesStore(store);
        }

        function readCalendarStoredValue(storage, storageKey) {
            try {
                var rawValue = storage.getItem(storageKey);
                var values = rawValue ? JSON.parse(rawValue) : null;
                return values && typeof values === 'object'
                    ? values[getCalendarPreferencesContextKey()] || null
                    : null;
            } catch (error) {
                return null;
            }
        }

        function writeCalendarPreferences(storage, storageKey, preferences) {
            try {
                var rawValue = storage.getItem(storageKey);
                var values = rawValue ? JSON.parse(rawValue) : {};
                if (!values || typeof values !== 'object') {
                    values = {};
                }
                values[getCalendarPreferencesContextKey()] = createCalendarPreferences(preferences);
                storage.setItem(storageKey, JSON.stringify(values));
            } catch (error) {
            }
        }

        function clearCalendarTemporaryPreferences() {
            try {
                var rawValue = window.sessionStorage.getItem(calendarSessionViewsStorageKey);
                var values = rawValue ? JSON.parse(rawValue) : {};
                if (!values || typeof values !== 'object') {
                    return;
                }
                delete values[getCalendarPreferencesContextKey()];
                window.sessionStorage.setItem(calendarSessionViewsStorageKey, JSON.stringify(values));
            } catch (error) {
            }
        }

        function clearAllCalendarTemporaryPreferences() {
            try {
                window.sessionStorage.removeItem(calendarSessionViewsStorageKey);
            } catch (error) {
            }
        }

        function readCalendarTemporaryPositionUrl() {
            var position = readCalendarStoredValue(window.sessionStorage, calendarSessionPositionStorageKey);
            var storedUrl = position && typeof position.url === 'string' ? position.url : '';
            if (!storedUrl) {
                return '';
            }

            try {
                var storedLocation = new URL(resolveUrl(storedUrl), window.location.origin);
                var currentLocation = new URL(resolveUrl(currentUrl), window.location.origin);
                if (
                    storedLocation.origin !== currentLocation.origin
                    || storedLocation.pathname !== currentLocation.pathname
                    || storedLocation.searchParams.get('oid') !== currentLocation.searchParams.get('oid')
                    || storedLocation.searchParams.get('cid') !== currentLocation.searchParams.get('cid')
                ) {
                    return '';
                }
            } catch (error) {
                return '';
            }

            return storedUrl;
        }

        function rememberCalendarTemporaryPosition(url) {
            if (!url) {
                return;
            }

            try {
                var rawValue = window.sessionStorage.getItem(calendarSessionPositionStorageKey);
                var values = rawValue ? JSON.parse(rawValue) : {};
                if (!values || typeof values !== 'object') {
                    values = {};
                }
                values[getCalendarPreferencesContextKey()] = {url: String(url)};
                window.sessionStorage.setItem(calendarSessionPositionStorageKey, JSON.stringify(values));
            } catch (error) {
            }
        }

        function readCalendarSearch() {
            var storedSearch = readCalendarStoredValue(window.sessionStorage, calendarSearchStorageKey);
            return typeof storedSearch === 'string' ? storedSearch : '';
        }

        function writeCalendarSearch(searchValue) {
            try {
                var rawValue = window.sessionStorage.getItem(calendarSearchStorageKey);
                var values = rawValue ? JSON.parse(rawValue) : {};
                if (!values || typeof values !== 'object') {
                    values = {};
                }
                values[getCalendarPreferencesContextKey()] = String(searchValue || '');
                window.sessionStorage.setItem(calendarSearchStorageKey, JSON.stringify(values));
            } catch (error) {
            }
        }

        function resolveUrl(url) {
            if (!url) {
                return '';
            }

            if (typeof window.omoResolveAppUrl === 'function') {
                return window.omoResolveAppUrl(url);
            }

            return url;
        }

        function buildCreateUrl(dateValue, dateTimeValue) {
            var url = createUrl;
            if (!url) {
                return url;
            }

            if (dateTimeValue) {
                return url + (url.indexOf('?') === -1 ? '?' : '&') + 'datetime=' + encodeURIComponent(dateTimeValue);
            }

            if (!dateValue) {
                return url;
            }

            return url + (url.indexOf('?') === -1 ? '?' : '&') + 'date=' + encodeURIComponent(dateValue);
        }

        function buildDetailUrl(eventId) {
            var url = detailUrl;
            if (!url || !eventId) {
                return url;
            }

            return url + (url.indexOf('?') === -1 ? '?' : '&') + 'id=' + encodeURIComponent(String(eventId));
        }

        function buildEditUrl(eventId) {
            var url = createUrl;
            if (!url || !eventId) {
                return url;
            }

            return url + (url.indexOf('?') === -1 ? '?' : '&') + 'id=' + encodeURIComponent(String(eventId));
        }

        function resolveViewMeta(viewName, scopeName) {
            var viewButton = root.querySelector('[data-omo-calendar-set-view="' + viewName + '"]');
            var resolvedScope = normalizeScopeName(scopeName);

            if (!viewButton) {
                return {
                    url: currentUrl,
                    count: '',
                    summary: ''
                };
            }

            return {
                url: viewButton.getAttribute('data-omo-calendar-view-url-' + resolvedScope) || currentUrl,
                count: viewButton.getAttribute('data-omo-calendar-view-count-' + resolvedScope) || '',
                summary: viewButton.getAttribute('data-omo-calendar-view-summary-' + resolvedScope) || ''
            };
        }

        function parseLocalDateTime(value) {
            if (!value) {
                return null;
            }

            var parsed = new Date(value);
            return Number.isNaN(parsed.getTime()) ? null : parsed;
        }

        function formatLocalDateTimeValue(date) {
            if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
                return '';
            }

            var year = String(date.getFullYear());
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            var hours = String(date.getHours()).padStart(2, '0');
            var minutes = String(date.getMinutes()).padStart(2, '0');

            return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
        }

        function clampNumber(value, min, max) {
            return Math.min(Math.max(value, min), max);
        }

        function buildDateTimeFromColumnPosition(dayValue, clientY, columnNode) {
            if (!dayValue || !columnNode) {
                return '';
            }

            var dayDate = parseLocalDateTime(dayValue + 'T00:00');
            if (!dayDate) {
                return '';
            }

            var columnRect = columnNode.getBoundingClientRect();
            if (!columnRect || columnRect.height <= 0) {
                return '';
            }

            var offsetY = clampNumber(clientY - columnRect.top, 0, columnRect.height);
            var minuteRatio = offsetY / columnRect.height;
            var rawMinutes = Math.round(minuteRatio * 24 * 60);
            var roundedMinutes = Math.round(rawMinutes / 15) * 15;
            roundedMinutes = clampNumber(roundedMinutes, 0, (24 * 60) - 15);

            dayDate.setHours(Math.floor(roundedMinutes / 60), roundedMinutes % 60, 0, 0);
            return formatLocalDateTimeValue(dayDate);
        }

        function rememberScheduleState(form) {
            if (!form) {
                return;
            }

            var startField = form.querySelector('input[name="start_at"]');
            var endField = form.querySelector('input[name="end_at"]');
            if (!startField || !endField) {
                return;
            }

            form.dataset.omoCalendarLastStart = startField.value || '';
            form.dataset.omoCalendarLastEnd = endField.value || '';
        }

        function syncEndDateWithStart(form) {
            if (!form) {
                return;
            }

            var startField = form.querySelector('input[name="start_at"]');
            var endField = form.querySelector('input[name="end_at"]');
            if (!startField || !endField) {
                return;
            }

            var startDate = parseLocalDateTime(startField.value);
            if (!startDate) {
                return;
            }

            var previousStart = parseLocalDateTime(form.dataset.omoCalendarLastStart || '');
            var previousEnd = parseLocalDateTime(form.dataset.omoCalendarLastEnd || '');
            var durationMs = 0;

            if (previousStart && previousEnd) {
                durationMs = Math.max(0, previousEnd.getTime() - previousStart.getTime());
            } else {
                var currentEnd = parseLocalDateTime(endField.value);
                if (currentEnd) {
                    durationMs = Math.max(0, currentEnd.getTime() - startDate.getTime());
                }
            }

            var nextEnd = new Date(startDate.getTime() + durationMs);
            if (durationMs <= 0) {
                endField.value = startField.value;
            } else {
                endField.value = formatLocalDateTimeValue(nextEnd);
            }

            rememberScheduleState(form);
        }

        function syncLocationFields(form) {
            if (!form) {
                return;
            }

            var modeField = form.querySelector('[data-omo-calendar-location-mode]');
            var addressField = form.querySelector('[data-omo-calendar-location-address-field]');
            var videoField = form.querySelector('[data-omo-calendar-location-video-field]');
            var modeValue = modeField ? String(modeField.value || '').trim() : '';
            var showAddress = modeValue === 'in_person' || modeValue === 'hybrid';
            var showVideo = modeValue === 'virtual' || modeValue === 'hybrid';

            if (addressField) {
                addressField.hidden = !showAddress;
                var addressInput = addressField.querySelector('input[name="location_address"]');
                if (addressInput) {
                    addressInput.required = showAddress;
                }
            }

            if (videoField) {
                videoField.hidden = !showVideo;
                var videoInput = videoField.querySelector('input[name="video_meeting_url"]');
                if (videoInput) {
                    videoInput.required = showVideo;
                }
            }
        }

        function syncDocumentFields(form) {
            if (!form) {
                return;
            }

            var typeField = form.querySelector('[data-omo-calendar-document-type]');
            var allDocumentFields = form.querySelector('[data-omo-calendar-document-fields]');
            var documentTemplateField = form.querySelector('[data-omo-calendar-document-template-field]');
            var documentType = typeField ? String(typeField.value || '').trim() : '';
            var hasDocument = documentType !== '';

            if (allDocumentFields) {
                allDocumentFields.hidden = !hasDocument;
            }

            if (documentTemplateField) {
                documentTemplateField.hidden = !hasDocument;
            }

            syncDocumentTemplateOptions(form);
        }

        function syncDocumentTemplateOptions(form) {
            if (!form) {
                return;
            }

            var contextField = form.querySelector('[data-omo-calendar-context-holon]');
            var typeField = form.querySelector('[data-omo-calendar-document-type]');
            var templateField = form.querySelector('select[name="document_template_id"]');
            if (!contextField || !templateField) {
                return;
            }

            var contextId = Number(contextField.value || '0');
            var documentType = typeField ? String(typeField.value || '').trim() : '';
            var contextOption = contextField.options[contextField.selectedIndex];
            var contextPath = String(contextOption ? contextOption.getAttribute('data-omo-calendar-context-path') || '' : '')
                .split(',')
                .map(function (value) { return Number(value); });

            Array.prototype.forEach.call(templateField.options, function (option) {
                if (String(option.value || '0') === '0') {
                    return;
                }

                var templateType = String(option.getAttribute('data-omo-calendar-document-template-type') || '');
                var scope = String(option.getAttribute('data-omo-calendar-document-template-scope') || '');
                var targetId = Number(option.getAttribute('data-omo-calendar-document-template-target') || '0');
                var isAvailable = templateType === documentType && (scope === 'organization' || scope === 'everyone'
                    || (scope === 'circle' && targetId > 0 && contextPath.indexOf(targetId) !== -1)
                    || (scope === 'role' && targetId > 0 && contextId === targetId));
                option.hidden = !isAvailable;
                option.disabled = !isAvailable;
            });
            Array.prototype.forEach.call(templateField.querySelectorAll('optgroup'), function (group) {
                group.hidden = !Array.prototype.some.call(group.querySelectorAll('option'), function (option) {
                    return !option.hidden;
                });
            });

            if (templateField.selectedOptions.length > 0 && templateField.selectedOptions[0].disabled) {
                templateField.value = '0';
            }
        }

        function syncInvitationDefaultHolonSelection(editor, nextHolonId) {
            if (!editor || editor.getAttribute('data-omo-calendar-uses-default-selection') !== '1') {
                return;
            }

            var holonId = Number(nextHolonId || '0');
            if (!Number.isInteger(holonId) || holonId < 0) {
                holonId = 0;
            }

            Array.prototype.forEach.call(editor.querySelectorAll('input[name="invitation_holon_ids[]"]'), function (field) {
                field.checked = holonId > 0 && Number(field.value || '0') === holonId;
            });

            editor.dataset.omoCalendarDefaultHolonId = String(holonId);
            syncInvitationEditorFilters(editor);
        }

        function normalizeInvitationFilterText(value) {
            var normalized = String(value || '').toLowerCase();

            if (typeof normalized.normalize === 'function') {
                normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            }

            return normalized.replace(/\s+/g, ' ').trim();
        }

        function getInvitationHolonChildrenContainer(node) {
            if (!node || !node.children) {
                return null;
            }

            for (var index = 0; index < node.children.length; index += 1) {
                var child = node.children[index];
                if (child && child.hasAttribute && child.hasAttribute('data-omo-calendar-holon-children')) {
                    return child;
                }
            }

            return null;
        }

        function filterInvitationHolonNode(node, query) {
            if (!node) {
                return false;
            }

            var searchText = normalizeInvitationFilterText(node.getAttribute('data-omo-calendar-search-text') || node.textContent || '');
            var row = node.querySelector('.omo-calendar-invitations-editor__tree-row');
            var checkbox = row ? row.querySelector('input[type="checkbox"]') : null;
            var toggle = row ? row.querySelector('[data-omo-calendar-holon-toggle]') : null;
            var childrenContainer = getInvitationHolonChildrenContainer(node);
            var keepChecked = !!(checkbox && checkbox.checked);
            var matchesSelf = query === '' || searchText.indexOf(query) !== -1;
            var hasVisibleChild = false;

            if (childrenContainer && childrenContainer.children) {
                Array.prototype.forEach.call(childrenContainer.children, function (childNode) {
                    if (!childNode || !childNode.hasAttribute || !childNode.hasAttribute('data-omo-calendar-holon-node')) {
                        return;
                    }

                    if (filterInvitationHolonNode(childNode, query)) {
                        hasVisibleChild = true;
                    }
                });
            }

            var isVisible = query === '' ? true : (matchesSelf || keepChecked || hasVisibleChild);
            node.hidden = !isVisible;

            if (childrenContainer) {
                if (query === '') {
                    var isExpanded = !toggle || toggle.getAttribute('aria-expanded') === 'true';
                    childrenContainer.hidden = !isExpanded;
                } else {
                    childrenContainer.hidden = !hasVisibleChild;
                }
            }

            return isVisible;
        }

        function syncInvitationHolonFilter(editor) {
            if (!editor) {
                return;
            }

            var filterField = editor.querySelector('[data-omo-calendar-holon-filter]');
            var holonList = editor.querySelector('[data-omo-calendar-holon-list]');
            var emptyState = editor.querySelector('[data-omo-calendar-holon-empty]');
            var query = normalizeInvitationFilterText(filterField ? filterField.value : '');
            var hasVisibleResult = false;

            if (holonList && holonList.children) {
                Array.prototype.forEach.call(holonList.children, function (node) {
                    if (!node || !node.hasAttribute || !node.hasAttribute('data-omo-calendar-holon-node')) {
                        return;
                    }

                    if (filterInvitationHolonNode(node, query)) {
                        hasVisibleResult = true;
                    }
                });
            }

            if (emptyState) {
                emptyState.hidden = query === '' || hasVisibleResult;
            }
        }

        function syncInvitationMemberFilter(editor) {
            if (!editor) {
                return;
            }

            var filterField = editor.querySelector('[data-omo-calendar-member-filter]');
            var memberList = editor.querySelector('[data-omo-calendar-member-list]');
            var emptyState = editor.querySelector('[data-omo-calendar-member-empty]');
            var query = normalizeInvitationFilterText(filterField ? filterField.value : '');
            var hasVisibleResult = false;

            Array.prototype.forEach.call(editor.querySelectorAll('[data-omo-calendar-member-item]'), function (item) {
                var checkbox = item.querySelector('input[type="checkbox"]');
                var searchText = normalizeInvitationFilterText(item.getAttribute('data-omo-calendar-search-text') || item.textContent || '');
                var keepChecked = !!(checkbox && checkbox.checked);
                var isVisible = query === '' || searchText.indexOf(query) !== -1 || keepChecked;

                item.hidden = !isVisible;
                if (isVisible) {
                    hasVisibleResult = true;
                }
            });

            if (memberList) {
                memberList.hidden = false;
            }

            if (emptyState) {
                emptyState.hidden = query === '' || hasVisibleResult;
            }
        }

        function syncInvitationEditorFilters(editor) {
            if (!editor) {
                return;
            }

            syncInvitationHolonFilter(editor);
            syncInvitationMemberFilter(editor);
        }

        function initCalendarInvitationEditors(scope) {
            var rootScope = scope && scope.querySelectorAll ? scope : document;

            if (typeof window.initGenericComponents === 'function') {
                window.initGenericComponents(rootScope);
            }

            Array.prototype.forEach.call(rootScope.querySelectorAll('[data-omo-calendar-invitations-editor]'), function (editor) {
                if (editor.dataset.omoCalendarInvitationsReady === '1') {
                    return;
                }

                editor.dataset.omoCalendarInvitationsReady = '1';

                Array.prototype.forEach.call(editor.querySelectorAll('[data-omo-calendar-holon-toggle]'), function (toggle) {
                    toggle.addEventListener('click', function (event) {
                        var node;
                        var children;
                        var isExpanded;

                        event.preventDefault();
                        event.stopPropagation();

                        node = toggle.closest('[data-omo-calendar-holon-node]');
                        children = node ? node.querySelector('[data-omo-calendar-holon-children]') : null;
                        if (!children) {
                            return;
                        }

                        isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                        toggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
                        children.hidden = isExpanded;
                        syncInvitationEditorFilters(editor);
                    });
                });

                Array.prototype.forEach.call(editor.querySelectorAll('[data-omo-calendar-holon-filter], [data-omo-calendar-member-filter]'), function (field) {
                    field.addEventListener('input', function () {
                        syncInvitationEditorFilters(editor);
                    });
                });

                editor.addEventListener('change', function () {
                    if (editor.getAttribute('data-omo-calendar-uses-default-selection') === '1') {
                        editor.setAttribute('data-omo-calendar-uses-default-selection', '0');
                    }

                    syncInvitationEditorFilters(editor);
                });

                syncInvitationEditorFilters(editor);
            });
        }

        function syncCalendarCreateFormState(form) {
            if (!form) {
                return;
            }

            syncLocationFields(form);
            syncDocumentFields(form);
            initCalendarInvitationEditors(form);
            rememberScheduleState(form);
        }

        function setDrawerHeader(options) {
            var settings = options && typeof options === 'object' ? options : {};
            var hasTitle = Object.prototype.hasOwnProperty.call(settings, 'title');
            var hasDescription = Object.prototype.hasOwnProperty.call(settings, 'description')
                || Object.prototype.hasOwnProperty.call(settings, 'subtitle');
            var hasActions = Object.prototype.hasOwnProperty.call(settings, 'actions');
            var description = Object.prototype.hasOwnProperty.call(settings, 'subtitle')
                ? settings.subtitle
                : settings.description;

            if (drawerTitle && hasTitle) {
                drawerTitle.textContent = settings.title || '';
            }

            if (drawerDescription && hasDescription) {
                drawerDescription.textContent = description || '';
                drawerDescription.hidden = !description;
            }

            if (!drawerActions || !hasActions) {
                return;
            }

            drawerActions.innerHTML = '';
            (Array.isArray(settings.actions) ? settings.actions : []).forEach(function (action) {
                var button;
                if (action instanceof HTMLElement) {
                    drawerActions.appendChild(action);
                    return;
                }

                if (!action || typeof action !== 'object' || !action.label) {
                    return;
                }

                button = document.createElement('button');
                button.type = action.type || 'button';
                button.className = action.className || 'generic-action-button';
                button.textContent = action.label;
                if (action.attributes && typeof action.attributes === 'object') {
                    Object.keys(action.attributes).forEach(function (name) {
                        button.setAttribute(name, String(action.attributes[name]));
                    });
                }
                if (typeof action.onClick === 'function') {
                    button.addEventListener('click', action.onClick);
                }
                drawerActions.appendChild(button);
            });
        }

        function resetDrawerHeader() {
            setDrawerHeader({
                title: defaultDrawerTitle,
                description: defaultDrawerDescription,
                actions: []
            });
        }

        function applyDrawerHeaderFromContent(content) {
            var header = content ? content.querySelector('[data-omo-calendar-drawer-header]') : null;
            if (!header) {
                resetDrawerHeader();
                return;
            }

            setDrawerHeader({
                title: header.getAttribute('data-omo-calendar-drawer-title') || defaultDrawerTitle,
                description: header.getAttribute('data-omo-calendar-drawer-description') || '',
                actions: Array.prototype.slice.call(header.querySelectorAll('[data-omo-calendar-drawer-action]'))
            });
        }

        window.omoCalendarDrawer = window.omoCalendarDrawer || {};
        window.omoCalendarDrawer.setHeader = setDrawerHeader;
        window.omoCalendarDrawer.setTitle = function (title) {
            setDrawerHeader({ title: title });
        };
        window.omoCalendarDrawer.setSubtitle = function (subtitle) {
            setDrawerHeader({ subtitle: subtitle });
        };
        window.omoCalendarDrawer.addButton = function (button) {
            if (!drawerActions) {
                return;
            }
            setDrawerHeader({
                actions: Array.prototype.slice.call(drawerActions.children).concat([button])
            });
        };
        window.omoCalendarDrawer.resetHeader = resetDrawerHeader;

        function setDrawerLoading() {
            if (!drawerBody) {
                return;
            }

            resetDrawerHeader();
            drawerBody.innerHTML = '<div class="generic-section">' + escapeCalendarHtml(config.labels['calendar.loading']) + '</div>';
        }

        function setDrawerError() {
            if (!drawerBody) {
                return;
            }

            resetDrawerHeader();
            drawerBody.innerHTML = '<div class="generic-section">' + escapeCalendarHtml(config.labels['calendar.error.load_form']) + '</div>';
        }

        function closeDrawer(options) {
            var settings = options && typeof options === 'object'
                ? options
                : {};

            if (
                settings.force !== true
                && /^calendar-(?:e\d+|event-\d+)$/i.test(getCurrentRouteToken())
                && typeof window.omoOpenDrawerHashState === 'function'
            ) {
                window.omoOpenDrawerHashState('calendar');
                return;
            }

            if (!drawer) {
                return;
            }

            drawer.classList.remove('is-open');
            window.setTimeout(function () {
                if (!drawer.classList.contains('is-open')) {
                    drawer.hidden = true;
                    if (drawerBody) {
                        drawerBody.innerHTML = '';
                    }
                }
            }, 180);
        }

        function openDrawerWithUrl(url) {
            if (!drawer || !drawerBody || !url) {
                return;
            }

            setDrawerLoading();
            drawer.hidden = false;
            window.requestAnimationFrame(function () {
                drawer.classList.add('is-open');
            });

            var localToken = ++requestToken;

            fetch(resolveUrl(url), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('load_failed');
                }

                return response.text();
            }).then(function (html) {
                if (localToken !== requestToken || !drawerBody) {
                    return;
                }

                drawerBody.innerHTML = html;
                applyDrawerHeaderFromContent(drawerBody);
                if (typeof window.initGenericComponents === 'function') {
                    window.initGenericComponents(drawerBody);
                }
                syncCalendarCreateFormState(drawerBody.querySelector('[data-omo-calendar-create-form]'));
            }).catch(function () {
                if (localToken !== requestToken) {
                    return;
                }

                setDrawerError();
            });
        }

        function refreshCalendar(url) {
            var targetUrl = url || currentUrl;
            if (!targetUrl) {
                return null;
            }

            rememberCalendarTemporaryPosition(targetUrl);

            if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
                window.location.href = resolveUrl(targetUrl);
                return null;
            }

            // The connection popup can stay open after an earlier calendar reload.
            var activeRoot = root.isConnected ? root : document.querySelector('#omo-calendar-root');
            return window.omoReplaceFetchedPanelRoot({
                rootSelector: '#omo-calendar-root',
                currentRoot: activeRoot,
                url: resolveUrl(targetUrl),
                setLoadingState: function (isLoading) {
                    activeRoot.classList.toggle('is-loading', Boolean(isLoading));
                    if (typeof window.omoSetPanelResultsLoadingSkeleton === 'function') {
                        window.omoSetPanelResultsLoadingSkeleton(activeRoot, isLoading, {
                            contentSelector: '.omo-panel-view__body'
                        });
                    }
                }
            });
        }

        function escapeCalendarHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function openExternalEventDrawer(eventNode) {
            if (!drawer || !drawerBody || !(eventNode instanceof Element)) {
                return;
            }

            var rawData = eventNode.getAttribute('data-omo-calendar-external-event-data') || '';
            var eventData = {};
            try {
                eventData = JSON.parse(rawData);
            } catch (error) {
                return;
            }

            if (!eventData || typeof eventData !== 'object') {
                return;
            }

            var title = String(eventData.title || '').trim() || externalEventDrawerText.title;
            var description = String(eventData.description || '').trim();
            var schedule = String(eventData.schedule || '').trim();
            var calendar = String(eventData.calendar || '').trim();
            var location = String(eventData.location || '').trim();
            var color = String(eventData.color || '').trim();
            var colorStyle = /^#[0-9a-f]{6}$/i.test(color)
                ? ' style="--param-external-event-color: ' + color + ';"'
                : '';

            requestToken += 1;
            setDrawerHeader({
                title: externalEventDrawerText.title,
                description: externalEventDrawerText.description,
                actions: []
            });
            drawerBody.innerHTML = '<article class="omo-calendar__external-event-detail generic-drawer-content">'
                + '<section class="generic-section generic-section--stack omo-calendar__external-event-overview">'
                + '<h3 class="generic-card-title generic-card-title--large">' + escapeCalendarHtml(title) + '</h3>'
                + '<div class="omo-calendar__external-event-meta">'
                + '<div><span class="omo-calendar__external-event-meta-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><rect x="4" y="5" width="16" height="15" rx="2"></rect><path d="M8 3v4M16 3v4M4 10h16"></path></svg></span><div><span class="generic-meta-label">' + escapeCalendarHtml(externalEventDrawerText.schedule) + '</span><strong class="generic-meta-value">' + escapeCalendarHtml(schedule) + '</strong></div></div>'
                + '<div><span class="omo-calendar__external-event-meta-icon" aria-hidden="true"' + colorStyle + '><svg viewBox="0 0 24 24" focusable="false"><rect x="4" y="5" width="16" height="15" rx="2"></rect><path d="M8 3v4M16 3v4M4 10h16"></path></svg></span><div><span class="generic-meta-label">' + escapeCalendarHtml(externalEventDrawerText.calendar) + '</span><strong class="generic-meta-value">' + escapeCalendarHtml(calendar || externalEventDrawerText.calendar) + '</strong></div></div>'
                + (location ? '<div><span class="omo-calendar__external-event-meta-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M12 21s6-5.1 6-11a6 6 0 1 0-12 0c0 5.9 6 11 6 11Z"></path><circle cx="12" cy="10" r="2"></circle></svg></span><div><span class="generic-meta-label">' + escapeCalendarHtml(externalEventDrawerText.location) + '</span><strong class="generic-meta-value">' + escapeCalendarHtml(location) + '</strong></div></div>' : '')
                + '</div></section>'
                + (description ? '<section class="generic-section generic-section--stack omo-calendar__external-event-description"><h3 class="generic-card-title generic-card-title--medium">' + escapeCalendarHtml(externalEventDrawerText.descriptionLabel) + '</h3><div class="generic-description generic-description--primary generic-description--relaxed">' + escapeCalendarHtml(description).replace(/\r?\n/g, '<br>') + '</div></section>' : '')
                + '</article>';
            drawer.hidden = false;
            window.requestAnimationFrame(function () {
                drawer.classList.add('is-open');
            });
        }

        function askDeleteAssociatedDocuments(button) {
            if (button.getAttribute('data-omo-calendar-delete-has-documents') !== '1') {
                return Promise.resolve(false);
            }

            var title = button.getAttribute('data-omo-calendar-delete-documents-title') || 'Documents associés';
            var question = button.getAttribute('data-omo-calendar-delete-documents-question') || 'Voulez-vous supprimer les documents associés ?';
            var yesLabel = button.getAttribute('data-omo-calendar-delete-documents-yes') || 'Oui';
            var noLabel = button.getAttribute('data-omo-calendar-delete-documents-no') || 'Non';

            if (typeof window.commonTopbarOpenModal !== 'function') {
                return Promise.resolve(window.confirm(question));
            }

            return new Promise(function (resolve) {
                var settled = false;
                var modalCloseHandler;

                function settle(value) {
                    if (settled) {
                        return;
                    }

                    settled = true;
                    if (modalCloseHandler) {
                        window.removeEventListener('common-topbar-modal-close', modalCloseHandler);
                    }
                    if (typeof window.commonTopbarCloseModal === 'function') {
                        window.commonTopbarCloseModal();
                    }
                    resolve(value === true);
                }

                window.commonTopbarOpenModal(
                    title,
                    '<div class="omo-calendar__delete-documents-dialog generic-drawer-content">'
                        + '<p data-omo-calendar-delete-documents-question></p>'
                        + '<div class="omo-calendar__delete-documents-actions">'
                        + '<button type="button" class="generic-action-button generic-action-button--main" data-omo-calendar-delete-documents-choice="yes">' + escapeCalendarHtml(yesLabel) + '</button>'
                        + '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-calendar-delete-documents-choice="no">' + escapeCalendarHtml(noLabel) + '</button>'
                        + '</div>'
                        + '</div>',
                    'html'
                );

                var modalBody = document.getElementById('commonTopbarModalBody');
                if (!modalBody) {
                    settle(false);
                    return;
                }

                var questionNode = modalBody.querySelector('[data-omo-calendar-delete-documents-question]');
                if (questionNode) {
                    questionNode.textContent = question;
                }

                modalCloseHandler = function () {
                    settle(false);
                };
                window.addEventListener('common-topbar-modal-close', modalCloseHandler);
                modalBody.querySelectorAll('[data-omo-calendar-delete-documents-choice]').forEach(function (choiceButton) {
                    choiceButton.addEventListener('click', function () {
                        settle(choiceButton.getAttribute('data-omo-calendar-delete-documents-choice') === 'yes');
                    });
                });
            });
        }

        function deleteCalendarEvent(deleteButton) {
            if (!(deleteButton instanceof Element)) {
                return;
            }

            var deleteUrl = deleteButton.getAttribute('data-omo-calendar-delete-url') || '';
            var confirmationMessage = deleteButton.getAttribute('data-omo-calendar-delete-confirm') || '';
            var fallbackError = deleteButton.getAttribute('data-omo-calendar-delete-error') || 'Impossible de supprimer cet événement.';
            if (!deleteUrl || (confirmationMessage !== '' && !window.confirm(confirmationMessage))) {
                return;
            }

            deleteButton.disabled = true;
            askDeleteAssociatedDocuments(deleteButton).then(function (deleteDocuments) {
                var requestBody = new URLSearchParams();
                requestBody.set('delete_documents', deleteDocuments ? '1' : '0');

                return fetch(resolveUrl(deleteUrl), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: requestBody.toString()
                });
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                if (!payload || payload.status !== true) {
                    throw new Error(payload && payload.message ? payload.message : fallbackError);
                }

                if (typeof window.omoInvalidateMainRightPanel === 'function') {
                    window.omoInvalidateMainRightPanel();
                }

                closeDrawer();
                refreshCalendar(currentUrl);
            }).catch(function (error) {
                window.omoNotify(error && error.message ? error.message : fallbackError, 'error');
                deleteButton.disabled = false;
            });
        }

        function deleteAssociatedDocument(deleteButton) {
            if (!(deleteButton instanceof Element)) {
                return;
            }

            var documentId = Number(deleteButton.getAttribute('data-omo-calendar-document-delete-id') || '0');
            var confirmationMessage = deleteButton.getAttribute('data-omo-calendar-document-delete-confirm') || '';
            var fallbackError = deleteButton.getAttribute('data-omo-calendar-document-delete-error') || 'Impossible de supprimer le document.';
            var refreshUrl = deleteButton.getAttribute('data-omo-calendar-document-delete-refresh-url') || '';
            if (!Number.isInteger(documentId) || documentId <= 0 || (confirmationMessage !== '' && !window.confirm(confirmationMessage))) {
                return;
            }

            deleteButton.disabled = true;
            fetch('/omo/api/documents/lifecycle_action.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: documentId,
                    action: 'delete',
                    allow_event_document: true
                })
            }).then(function (response) {
                return response.json().catch(function () { return null; }).then(function (payload) {
                    if (!response.ok || !payload || payload.status !== true) {
                        throw new Error(String(payload && payload.message || fallbackError));
                    }
                });
            }).then(function () {
                if (typeof window.omoInvalidateMainRightPanel === 'function') {
                    window.omoInvalidateMainRightPanel();
                }
                if (refreshUrl) {
                    openDrawerWithUrl(refreshUrl);
                }
            }).catch(function (error) {
                window.omoNotify(error && error.message ? error.message : fallbackError, 'error');
                deleteButton.disabled = false;
            });
        }

        window.omoCalendarOpenEventDrawer = function (url) {
            if (!url) {
                return;
            }

            openDrawerWithUrl(url);
        };

        window.omoCalendarRefreshCurrentView = function () {
            if (drawer && !drawer.hidden && drawer.classList.contains('is-open')) {
                return;
            }

            refreshCalendar(currentUrl);
        };
        window.omoCalendarInitInvitationEditors = initCalendarInvitationEditors;

        function syncViewButtons(nextView) {
            root.querySelectorAll('[data-omo-calendar-set-view]').forEach(function (button) {
                var isActive = (button.getAttribute('data-omo-calendar-set-view') || '') === nextView;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        function syncScopeButtons(nextScope) {
            var resolvedScope = normalizeScopeName(nextScope);
            var scopeSwitch = root.querySelector('[data-omo-scope-switch]');
            if (scopeSwitch) {
                scopeSwitch.setAttribute('data-omo-scope-switch', resolvedScope);
            }

            root.querySelectorAll('[data-omo-calendar-scope-toggle]').forEach(function (button) {
                var isActive = (button.getAttribute('data-omo-calendar-scope-toggle') || '') === resolvedScope;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                if (isActive && scopeSwitch) {
                    scopeSwitch.style.setProperty(
                        '--omo-scope-active-index',
                        String(parseInt(button.getAttribute('data-omo-scope-index') || '0', 10) || 0)
                    );
                }
            });
        }

        function syncTodayButtons(nextView) {
            root.querySelectorAll('[data-omo-calendar-today-button]').forEach(function (button) {
                var matches = (button.getAttribute('data-omo-calendar-today-button') || '') === nextView;
                button.classList.toggle('is-hidden', !matches);
                if (matches) {
                    var nextUrl = button.getAttribute('data-omo-calendar-nav-url-' + currentScope) || '';
                    if (nextUrl) {
                        button.setAttribute('data-omo-calendar-nav-url', nextUrl);
                    }
                }
            });
        }

        function normalizeCalendarSearch(value) {
            return String(value || '')
                .toLocaleLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim();
        }

        function getActiveCalendarFilters() {
            return {
                scope: normalizeScopeName(currentScope),
                view: normalizeViewPreference(currentView)
            };
        }

        function normalizeCalendarFilters(filters) {
            var active = getActiveCalendarFilters();
            var scope = normalizeScopeName(filters && filters.scope);
            if (!root.querySelector('[data-omo-calendar-scope-toggle="' + scope + '"]')) {
                scope = active.scope;
            }
            return {
                scope: scope,
                view: normalizeViewPreference(filters && filters.view)
            };
        }

        function syncCalendarFilterChips() {
            [
                {button: '[data-omo-calendar-scope-toggle="' + currentScope + '"]', chip: '[data-omo-calendar-scope-chip]'},
                {button: '[data-omo-calendar-set-view="' + currentView + '"]', chip: '[data-omo-calendar-view-chip]'}
            ].forEach(function (entry) {
                var button = root.querySelector(entry.button);
                var chip = root.querySelector(entry.chip);
                if (button && chip) {
                    chip.textContent = button.textContent.trim();
                }
            });
        }

        function applyCalendarQuickSearch() {
            var query = normalizeCalendarSearch(currentSearch);
            root.classList.toggle('is-quick-searching', query !== '');
            root.querySelectorAll('[data-omo-calendar-search-item]').forEach(function (item) {
                var isOverflow = item.hasAttribute('data-omo-calendar-overflow-event');
                var overflowCell = item.closest('[data-omo-calendar-day]');
                var isOverflowExpanded = !!overflowCell && overflowCell.hasAttribute('data-omo-calendar-overflow-expanded');
                item.hidden = query === ''
                    ? isOverflow && !isOverflowExpanded
                    : normalizeCalendarSearch(item.textContent || '').indexOf(query) === -1;
            });
            root.querySelectorAll('[data-omo-calendar-more]').forEach(function (more) {
                var overflowCell = more.closest('[data-omo-calendar-day]');
                more.hidden = query !== '' || (!!overflowCell && overflowCell.hasAttribute('data-omo-calendar-overflow-expanded'));
            });
            root.querySelectorAll('[data-omo-calendar-search-group]').forEach(function (group) {
                group.hidden = query !== '' && !group.querySelector('[data-omo-calendar-search-item]:not([hidden])');
            });
            root.querySelectorAll('[data-omo-calendar-default-empty]').forEach(function (empty) {
                empty.hidden = query !== '';
            });

            var activePanel = findActiveViewPanel();
            var visibleEventIds = new Set();
            if (activePanel) {
                activePanel.querySelectorAll('[data-omo-calendar-search-item]:not([hidden])').forEach(function (item) {
                    var eventId = item.getAttribute('data-omo-calendar-event-id') || '';
                    if (eventId !== '') {
                        visibleEventIds.add(eventId);
                    }
                });
            }
            var empty = root.querySelector('[data-omo-calendar-search-empty]');
            if (empty) {
                empty.hidden = query === '' || visibleEventIds.size > 0;
            }
            if (headerCount) {
                var currentMeta = resolveViewMeta(currentView, currentScope);
                headerCount.textContent = query === '' ? String(currentMeta.count || '0') : String(visibleEventIds.size);
            }
        }

        function syncCalendarFilterChoices() {
            if (!pendingDisplayFilters) {
                return;
            }
            pendingDisplayFilters = normalizeCalendarFilters(pendingDisplayFilters);
            [
                {selector: '[data-omo-calendar-scope-toggle]', attribute: 'data-omo-calendar-scope-toggle', value: pendingDisplayFilters.scope},
                {selector: '[data-omo-calendar-set-view]', attribute: 'data-omo-calendar-set-view', value: pendingDisplayFilters.view}
            ].forEach(function (choice) {
                root.querySelectorAll(choice.selector).forEach(function (button) {
                    var active = button.getAttribute(choice.attribute) === choice.value;
                    button.classList.toggle('is-active', active);
                    button.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            });
        }

        function applyCalendarFilters(filters) {
            var next = normalizeCalendarFilters(filters);
            currentScope = next.scope;
            var nextMeta = resolveViewMeta(next.view, currentScope);
            setActiveView(next.view, nextMeta.url, nextMeta.count, nextMeta.summary);
        }

        function closeCalendarFilterMoreMenu() {
            root.querySelectorAll('[data-omo-calendar-filter-more-menu]').forEach(function (menu) {
                var panel = menu.querySelector('[data-omo-calendar-filter-more-panel]');
                var toggle = menu.querySelector('[data-omo-calendar-filter-more-toggle]');
                if (panel) {
                    panel.hidden = true;
                }
                menu.classList.remove('is-open');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        function handleCalendarFilterOutsidePointerDown(event) {
            var control = root.querySelector('[data-omo-calendar-filter-control]');
            if (control && control.contains(event.target)) {
                return;
            }
            closeCalendarFilterPanel(true, 'temporary');
        }

        function openCalendarFilterPanel() {
            var panel = root.querySelector('[data-omo-calendar-filter-panel]');
            if (!panel || filterPanelOpen) {
                return;
            }
            pendingDisplayFilters = getActiveCalendarFilters();
            closeCalendarFilterMoreMenu();
            syncCalendarFilterChoices();
            panel.hidden = false;
            filterPanelOpen = true;
            root.querySelectorAll('[data-omo-calendar-filter-toggle]').forEach(function (button) {
                button.setAttribute('aria-expanded', 'true');
            });
            document.addEventListener('pointerdown', handleCalendarFilterOutsidePointerDown, true);
        }

        function closeCalendarFilterPanel(applyChanges, storageMode) {
            var panel = root.querySelector('[data-omo-calendar-filter-panel]');
            if (!filterPanelOpen) {
                return;
            }
            filterPanelOpen = false;
            if (panel) {
                panel.hidden = true;
            }
            root.querySelectorAll('[data-omo-calendar-filter-toggle]').forEach(function (button) {
                button.setAttribute('aria-expanded', 'false');
            });
            document.removeEventListener('pointerdown', handleCalendarFilterOutsidePointerDown, true);
            closeCalendarFilterMoreMenu();

            if (!applyChanges || !pendingDisplayFilters) {
                pendingDisplayFilters = null;
                return;
            }

            var next = normalizeCalendarFilters(pendingDisplayFilters);
            pendingDisplayFilters = null;
            if (storageMode === 'device') {
                storeCalendarPreferences(next);
                clearCalendarTemporaryPreferences();
            } else if (storageMode === 'temporary') {
                writeCalendarPreferences(window.sessionStorage, calendarSessionViewsStorageKey, next);
            }
            applyCalendarFilters(next);
        }

        function applyCalendarFilterMoreAction(action) {
            if (!filterPanelOpen || !pendingDisplayFilters) {
                return;
            }

            var next = normalizeCalendarFilters(pendingDisplayFilters);
            closeCalendarFilterPanel(false);

            if (action === 'set-default') {
                clearCalendarStoredPreferences();
                clearCalendarTemporaryPreferences();
                storeCalendarDefaultPreferences(next);
                applyCalendarFilters(next);
                return;
            }

            if (action === 'apply-everywhere') {
                var store = getCalendarPreferencesStore();
                store.defaultView = createCalendarPreferences(next);
                store.contexts = {};
                saveCalendarPreferencesStore(store);
                clearAllCalendarTemporaryPreferences();
                applyCalendarFilters(next);
                return;
            }

            if (action === 'restore-default') {
                clearCalendarStoredPreferences();
                clearCalendarTemporaryPreferences();
                var store = getCalendarPreferencesStore();
                store.defaultView = null;
                saveCalendarPreferencesStore(store);
                var serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
                    ? window.omoApplicationViewPreferencesGetDefault(root)
                    : null;
                applyCalendarFilters(serverDefault || {scope: 'contextual', view: 'month'});
            }
        }

        function initializeCalendarViewFilter() {
            currentSearch = readCalendarSearch();
            var quickSearch = root.querySelector('[data-omo-calendar-quick-search]');
            if (quickSearch) {
                quickSearch.value = currentSearch;
            }
            var temporary = readCalendarStoredValue(window.sessionStorage, calendarSessionViewsStorageKey);
            var saved = getCalendarStoredPreferences();
            var defaultView = getCalendarDefaultPreferences();
            var serverDefault = typeof window.omoApplicationViewPreferencesGetDefault === 'function'
                ? window.omoApplicationViewPreferencesGetDefault(root)
                : null;
            var preferences = normalizeCalendarFilters(temporary || saved || defaultView || serverDefault || getActiveCalendarFilters());
            if (initialOpenEventId > 0) {
                preferences = getActiveCalendarFilters();
            }
            // A configured preference must win over an old session position, which can
            // otherwise restore a stale month or week after the page is reloaded.
            var temporaryPositionUrl = initialOpenEventId > 0 || temporary || saved || defaultView || serverDefault
                ? ''
                : readCalendarTemporaryPositionUrl();
            if (temporaryPositionUrl && resolveUrl(temporaryPositionUrl) !== resolveUrl(currentUrl)) {
                refreshCalendar(temporaryPositionUrl);
                return;
            }
            currentScope = preferences.scope;
            var nextMeta = resolveViewMeta(preferences.view, currentScope);
            setActiveView(preferences.view, nextMeta.url, nextMeta.count, nextMeta.summary);
            root.removeAttribute('data-omo-view-filter-pending');
            root.removeAttribute('aria-busy');
        }

        function getCalendarNow() {
            var now = new Date();
            try {
                var values = {};
                new Intl.DateTimeFormat('en-CA', {
                    timeZone: calendarTimezone,
                    year: 'numeric', month: '2-digit', day: '2-digit',
                    hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23'
                }).formatToParts(now).forEach(function (part) {
                    if (part.type !== 'literal') { values[part.type] = part.value; }
                });
                return {
                    day: values.year + '-' + values.month + '-' + values.day,
                    minute: Number(values.hour) * 60 + Number(values.minute) + Number(values.second) / 60
                };
            } catch (error) {
                return {
                    day: String(now.getFullYear()).padStart(4, '0') + '-'
                        + String(now.getMonth() + 1).padStart(2, '0') + '-'
                        + String(now.getDate()).padStart(2, '0'),
                    minute: now.getHours() * 60 + now.getMinutes() + now.getSeconds() / 60
                };
            }
        }

        function updateCurrentTimeIndicators() {
            var calendarNow = getCalendarNow();
            root.querySelectorAll('[data-omo-calendar-now-indicator]').forEach(function (indicator) {
                var visible = indicator.getAttribute('data-omo-calendar-now-day') === calendarNow.day;
                indicator.hidden = !visible;
                if (!visible) {
                    indicator.classList.remove('is-moving');
                    return;
                }
                indicator.style.top = String(Math.max(0, Math.min(100, calendarNow.minute / 1440 * 100))) + '%';
                if (!indicator.classList.contains('is-moving')) {
                    window.requestAnimationFrame(function () { indicator.classList.add('is-moving'); });
                }
            });
        }

        function scrollTimelineToRelevantTime(viewName) {
            if (viewName !== 'week' && viewName !== 'day') {
                return;
            }

            var panel = root.querySelector(
                '[data-omo-calendar-timeline-panel="' + viewName + '"][data-omo-calendar-view-scope="' + currentScope + '"]'
            );
            if (!panel) {
                return;
            }

            var timeView = panel.querySelector('[data-omo-calendar-time-view]');
            if (!timeView) {
                return;
            }

            var stickyBlock = timeView.querySelector('[data-omo-calendar-time-sticky]');
            var stickyHeight = stickyBlock ? stickyBlock.offsetHeight : 0;
            var nowIndicator = timeView.querySelector('[data-omo-calendar-now-indicator]:not([hidden])');
            var targetTop;
            if (nowIndicator) {
                var availableHeight = Math.max(0, timeView.clientHeight - stickyHeight);
                targetTop = timeView.scrollTop
                    + nowIndicator.getBoundingClientRect().top - timeView.getBoundingClientRect().top
                    - stickyHeight - availableHeight / 2;
            } else {
                var targetHour = timeView.querySelector('[data-omo-calendar-hour-index="7"]');
                if (!targetHour) { return; }
                targetTop = targetHour.offsetTop - stickyHeight - 8;
            }

            timeView.scrollTo({
                top: Math.max(0, Math.min(targetTop, timeView.scrollHeight - timeView.clientHeight)),
                behavior: 'auto'
            });
        }

        function findActiveViewPanel() {
            return root.querySelector(
                '[data-omo-calendar-view-panel="' + currentView + '"][data-omo-calendar-view-scope="' + currentScope + '"]'
            );
        }

        function findRouteTargetNode() {
            if (initialOpenEventId <= 0) {
                return null;
            }

            var activePanel = findActiveViewPanel();
            if (!activePanel) {
                return null;
            }

            return activePanel.querySelector('[data-omo-calendar-event-id="' + String(initialOpenEventId) + '"]');
        }

        function focusRouteTargetEvent(behavior) {
            var routeTargetNode = findRouteTargetNode();
            if (!routeTargetNode) {
                return false;
            }

            routeTargetNode.classList.remove('is-route-target-active');
            void routeTargetNode.offsetWidth;
            routeTargetNode.classList.add('is-route-target-active');

            try {
                routeTargetNode.scrollIntoView({
                    block: 'center',
                    inline: 'nearest',
                    behavior: behavior || 'smooth'
                });
            } catch (error) {
                routeTargetNode.scrollIntoView(true);
            }

            return true;
        }

        function maybeOpenInitialEventDetail() {
            if (initialOpenEventId <= 0 || initialOpenEventDrawerOpened) {
                return;
            }

            initialOpenEventDrawerOpened = true;
            window.setTimeout(function () {
                openDrawerWithUrl(buildDetailUrl(initialOpenEventId));
            }, 40);
        }

        function openEventFromRoute(eventId) {
            var resolvedEventId = Number(eventId || 0);
            if (!Number.isInteger(resolvedEventId) || resolvedEventId <= 0) {
                return false;
            }

            openDrawerWithUrl(buildDetailUrl(resolvedEventId));
            return true;
        }

        function setActiveView(nextView, nextUrl, nextCount, nextSummary) {
            if (!nextView) {
                return;
            }

            var newPanel = ensureCalendarView(nextView, currentScope);
            if (newPanel) bindCalendarView(newPanel);
            currentView = nextView;
            if (nextUrl) {
                currentUrl = nextUrl;
            }

            rememberCalendarTemporaryPosition(currentUrl);

            root.setAttribute('data-omo-calendar-view', nextView);
            root.setAttribute('data-omo-calendar-scope', currentScope);

            root.querySelectorAll('[data-omo-calendar-view-panel]').forEach(function (panel) {
                var panelView = panel.getAttribute('data-omo-calendar-view-panel') || '';
                var panelScope = normalizeScopeName(panel.getAttribute('data-omo-calendar-view-scope') || 'contextual');
                var isActive = panelView === nextView && panelScope === currentScope;
                panel.classList.toggle('is-active', isActive);
                panel.toggleAttribute('hidden', !isActive);
                panel.style.display = isActive ? '' : 'none';
            });

            syncViewButtons(nextView);
            syncScopeButtons(currentScope);
            syncTodayButtons(nextView);
            syncCalendarFilterChips();
            applyCalendarQuickSearch();

            if (headerCount && typeof nextCount === 'string') {
                headerCount.textContent = nextCount;
            }

            if (headerSummary && typeof nextSummary === 'string') {
                headerSummary.textContent = nextSummary;
            }

            window.requestAnimationFrame(function () {
                updateCurrentTimeIndicators();
                scrollTimelineToRelevantTime(nextView);
                if (initialOpenEventId > 0) {
                    focusRouteTargetEvent('auto');
                }
            });
        }

        root.querySelectorAll('[data-omo-calendar-editor-close]').forEach(function (button) {
            button.addEventListener('click', closeDrawer);
        });

        function initCalendarConnectControls(container) {
            container = container ? container.querySelector('[data-omo-calendar-connect-popup]') : null;
            if (!container || container.dataset.omoCalendarConnectReady === '1') {
                return;
            }
            container.dataset.omoCalendarConnectReady = '1';
            var text = JSON.parse(container.getAttribute('data-omo-external-calendar-text') || '{}');
            var csrf = container.getAttribute('data-omo-external-calendar-csrf') || '';
            var discoveryForm = container.querySelector('[data-omo-external-calendar-form]');
            var selectionForm = container.querySelector('[data-omo-external-calendar-selection]');
            var discoveryToken = '';
            var busy = false;
            function feedback(node, message, failed) {
                node.textContent = message;
                node.classList.toggle('is-error', !!failed);
            }
            function setBusy(value) {
                busy = value;
                container.querySelectorAll('button, input').forEach(function (node) {
                    node.disabled = value || node.dataset.calendarSaved === '1';
                });
                container.setAttribute('aria-busy', value ? 'true' : 'false');
            }
            function requestExternal(body) {
                body.set('csrf_token', csrf);
                return fetch(resolveUrl(discoveryForm.getAttribute('data-omo-external-calendar-action')), {
                    method: 'POST', credentials: 'same-origin', body: body
                }).then(function (response) { return response.json(); }).then(function (payload) {
                    if (!payload || payload.status !== true) {
                        throw new Error(payload && payload.message ? payload.message : text.failed);
                    }
                    return payload;
                });
            }
            container.addEventListener('click', function (event) {
                var button = event.target.closest('[data-omo-external-calendar-sync], [data-omo-external-calendar-delete]');
                if (!button || busy) { return; }
                var id = button.getAttribute('data-omo-external-calendar-id') || '';
                var action = button.hasAttribute('data-omo-external-calendar-delete') ? 'delete' : 'sync';
                if (!id || (action === 'delete' && !window.confirm(text.confirm_delete))) { return; }
                setBusy(true);
                var body = new URLSearchParams(); body.set('action', action); body.set('calendar_id', id);
                requestExternal(body).then(function () {
                    openCalendarConnectPopup(true);
                    return refreshCalendar(currentUrl);
                }).catch(function (error) { window.omoNotify(error.message || text.failed, 'error'); })
                    .finally(function () { setBusy(false); });
            });
            discoveryForm.addEventListener('input', function () {
                discoveryToken = '';
                selectionForm.hidden = true;
            });
            discoveryForm.addEventListener('submit', function (event) {
                event.preventDefault();
                if (busy) { return; }
                var body = new FormData(discoveryForm);
                var message = discoveryForm.querySelector('[data-omo-external-calendar-feedback]');
                discoveryToken = '';
                selectionForm.hidden = true;
                setBusy(true);
                feedback(message, text.searching, false);
                requestExternal(body).then(function (payload) {
                    discoveryToken = payload.discoveryToken;
                    discoveryForm.elements.password.value = '';
                    var results = selectionForm.querySelector('[data-omo-external-calendar-results]');
                    results.replaceChildren();
                    payload.calendars.forEach(function (calendar) {
                        var row = container.querySelector('[data-omo-external-calendar-template]').content.firstElementChild.cloneNode(true);
                        row.dataset.calendarIndex = String(calendar.index);
                        row.querySelector('[data-calendar-name]').textContent = calendar.title;
                        row.querySelector('[data-calendar-title]').value = calendar.title;
                        row.querySelector('[data-calendar-color]').value = calendar.color;
                        row.querySelector('[data-calendar-selected]').checked = payload.calendars.length === 1 && !calendar.connected;
                        row.querySelector('[data-calendar-connected]').hidden = !calendar.connected;
                        results.appendChild(row);
                    });
                    feedback(message, payload.message, false);
                    feedback(selectionForm.querySelector('[data-omo-external-calendar-feedback]'), '', false);
                    selectionForm.hidden = false;
                }).catch(function (error) { feedback(message, error.message || text.failed, true); })
                    .finally(function () { setBusy(false); });
            });
            selectionForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                if (busy || !discoveryToken) { return; }
                var rows = Array.from(selectionForm.querySelectorAll('[data-omo-external-calendar-result]')).filter(function (row) {
                    var checkbox = row.querySelector('[data-calendar-selected]');
                    return checkbox.checked && !checkbox.disabled;
                });
                var message = selectionForm.querySelector('[data-omo-external-calendar-feedback]');
                if (!rows.length) { feedback(message, text.choose, true); return; }
                setBusy(true);
                feedback(message, text.pending, false);
                var saved = false;
                for (var row of rows) {
                    var status = row.querySelector('[data-calendar-status]');
                    feedback(status, text.pending, false);
                    var body = new URLSearchParams();
                    body.set('action', 'save');
                    body.set('discovery_token', discoveryToken);
                    body.set('calendar_index', row.dataset.calendarIndex);
                    body.set('title', row.querySelector('[data-calendar-title]').value);
                    body.set('color', row.querySelector('[data-calendar-color]').value);
                    try {
                        var payload = await requestExternal(body);
                        saved = true;
                        row.querySelectorAll('input').forEach(function (input) { input.dataset.calendarSaved = '1'; });
                        feedback(status, payload.message, payload.synced === false);
                    } catch (error) {
                        feedback(status, error.message || text.failed, true);
                    }
                }
                feedback(message, text.finished, false);
                setBusy(false);
                if (saved) {
                    try { await refreshCalendar(currentUrl); }
                    catch (error) { window.omoNotify(text.failed, 'error'); }
                }
            });

            container.addEventListener('input', function (event) {
                var colorField = event.target.closest('[data-omo-calendar-connect-color]');
                var connectDetails = colorField ? colorField.closest('[data-omo-calendar-connect-details]') : null;
                var urlField = connectDetails ? connectDetails.querySelector('[data-omo-calendar-connect-url]') : null;
                var urlPrefix = connectDetails ? (connectDetails.getAttribute('data-omo-calendar-connect-url-prefix') || '') : '';
                var color = colorField ? String(colorField.value || '').replace(/^#/, '').toLowerCase() : '';

                if (urlField && /^[0-9a-f]{6}$/.test(color)) {
                    urlField.value = urlPrefix + color + '/';
                }
            });

            container.addEventListener('click', function (event) {
                var copyButton = event.target.closest('[data-omo-calendar-connect-copy]');
                if (!copyButton) {
                    return;
                }

                var copyTarget = copyButton.getAttribute('data-omo-calendar-connect-copy-target') || 'caldav';
                var urlField = copyTarget === 'ics'
                    ? container.querySelector('[data-omo-calendar-connect-ics-url]')
                    : container.querySelector('[data-omo-calendar-connect-url]');
                if (!urlField) {
                    return;
                }

                var value = String(urlField.value || '');
                var copyValue = function () {
                    urlField.focus();
                    urlField.select();
                    document.execCommand('copy');
                };

                if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                    navigator.clipboard.writeText(value).catch(copyValue);
                } else {
                    copyValue();
                }
            });
        }

        root.querySelectorAll('[data-omo-calendar-filter-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (filterPanelOpen) {
                    closeCalendarFilterPanel(true, 'temporary');
                } else {
                    openCalendarFilterPanel();
                }
            });
        });

        var calendarFilterPanel = root.querySelector('[data-omo-calendar-filter-panel]');
        if (calendarFilterPanel) {
            calendarFilterPanel.addEventListener('click', function (event) {
                var moreToggle = event.target.closest('[data-omo-calendar-filter-more-toggle]');
                if (moreToggle) {
                    event.preventDefault();
                    event.stopPropagation();
                    var moreMenu = moreToggle.closest('[data-omo-calendar-filter-more-menu]');
                    var morePanel = moreMenu ? moreMenu.querySelector('[data-omo-calendar-filter-more-panel]') : null;
                    var isMoreMenuOpen = !!morePanel && !morePanel.hidden;
                    closeCalendarFilterMoreMenu();
                    if (!isMoreMenuOpen && morePanel) {
                        morePanel.hidden = false;
                        moreMenu.classList.add('is-open');
                        moreToggle.setAttribute('aria-expanded', 'true');
                    }
                    return;
                }
                var moreAction = event.target.closest('[data-omo-calendar-filter-more-action]');
                if (moreAction) {
                    event.preventDefault();
                    event.stopPropagation();
                    applyCalendarFilterMoreAction(moreAction.getAttribute('data-omo-calendar-filter-more-action') || '');
                    return;
                }
                var applyButton = event.target.closest('[data-omo-calendar-filter-apply]');
                if (applyButton) {
                    event.preventDefault();
                    closeCalendarFilterPanel(true, 'temporary');
                    return;
                }
                var saveButton = event.target.closest('[data-omo-app-view-save-scope]');
                if (saveButton) {
                    event.preventDefault();
                    var isDeviceSave = saveButton.hasAttribute('data-omo-calendar-filter-save');
                    closeCalendarFilterPanel(true, isDeviceSave ? 'device' : 'none');
                    if (isDeviceSave) {
                        event.stopPropagation();
                    }
                    return;
                }
                var scopeButton = event.target.closest('[data-omo-calendar-scope-toggle]');
                if (scopeButton && pendingDisplayFilters) {
                    pendingDisplayFilters.scope = normalizeScopeName(scopeButton.getAttribute('data-omo-calendar-scope-toggle') || '');
                    syncCalendarFilterChoices();
                    return;
                }
                var viewButton = event.target.closest('[data-omo-calendar-set-view]');
                if (viewButton && pendingDisplayFilters) {
                    pendingDisplayFilters.view = normalizeViewPreference(viewButton.getAttribute('data-omo-calendar-set-view') || '');
                    syncCalendarFilterChoices();
                }
            });
        }

        var calendarQuickSearch = root.querySelector('[data-omo-calendar-quick-search]');
        if (calendarQuickSearch) {
            calendarQuickSearch.addEventListener('input', function () {
                currentSearch = calendarQuickSearch.value || '';
                writeCalendarSearch(currentSearch);
                applyCalendarQuickSearch();
            });
            calendarQuickSearch.addEventListener('search', function () {
                currentSearch = calendarQuickSearch.value || '';
                writeCalendarSearch(currentSearch);
                applyCalendarQuickSearch();
            });
        }

        root.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && filterPanelOpen) {
                closeCalendarFilterPanel(false);
            }
            if (event.key === 'Escape') {
                closeCalendarHeaderMenu();
            }
        });

        var openCreateButton = root.querySelector('[data-omo-calendar-open-create]');
        if (openCreateButton) {
            openCreateButton.addEventListener('click', function () {
                openDrawerWithUrl(createUrl);
            });
        }

        function bindCalendarView(panel) {
            panel.querySelectorAll('[data-omo-calendar-more]').forEach(function (more) {
                more.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    var overflowCell = more.closest('[data-omo-calendar-day]');
                    if (!overflowCell) {
                        return;
                    }
                    overflowCell.setAttribute('data-omo-calendar-overflow-expanded', '1');
                    more.setAttribute('aria-expanded', 'true');
                    applyCalendarQuickSearch();
                });
            });
            panel.querySelectorAll('[data-omo-calendar-nav-url], [data-omo-calendar-nav-url-contextual]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var url = button.getAttribute('data-omo-calendar-nav-url') || '';
                    if (!url) {
                        return;
                    }

                    refreshCalendar(url);
                });
            });

            if (canCreateEvent) {
                panel.querySelectorAll('[data-omo-calendar-day]').forEach(function (cell) {
                    cell.addEventListener('dblclick', function () {
                        var day = cell.getAttribute('data-omo-calendar-day') || '';
                        openDrawerWithUrl(buildCreateUrl(day));
                    });
                });

                panel.querySelectorAll('[data-omo-calendar-time-column-day]').forEach(function (columnNode) {
                    columnNode.addEventListener('dblclick', function (event) {
                        if (event.target && event.target.closest('[data-omo-calendar-event-id]')) {
                            return;
                        }

                        var day = columnNode.getAttribute('data-omo-calendar-time-column-day') || '';
                        var dateTimeValue = buildDateTimeFromColumnPosition(day, event.clientY, columnNode);
                        openDrawerWithUrl(buildCreateUrl(day, dateTimeValue));
                    });
                });
            }

            panel.querySelectorAll('[data-omo-calendar-event-id]').forEach(function (eventNode) {
                eventNode.addEventListener('dblclick', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (eventNode.hasAttribute('data-omo-calendar-external-event')) {
                        openExternalEventDrawer(eventNode);
                        return;
                    }
                    if (eventNode.hasAttribute('data-omo-calendar-other-organization')) {
                        return;
                    }

                    var eventId = eventNode.getAttribute('data-omo-calendar-event-id') || '';
                    if (!eventId) {
                        return;
                    }

                    var routeToken = buildEventRouteToken(eventId);
                    var currentRouteToken = getCurrentRouteToken();

                    if (!useLocalDrawerNavigation && routeToken && typeof window.omoOpenDrawerHashState === 'function' && routeToken !== currentRouteToken) {
                        window.omoOpenDrawerHashState(routeToken);
                        return;
                    }

                    openDrawerWithUrl(buildDetailUrl(eventId));
                });
            });

        }
        // Header navigation is present before the lazy view panels.
        bindCalendarView(root);

        if (drawer) {
            drawer.addEventListener('click', function (event) {
                var cancelButton = event.target.closest('[data-omo-calendar-open-detail-url]');
                if (cancelButton) {
                    event.preventDefault();
                    var cancelDetailUrl = cancelButton.getAttribute('data-omo-calendar-open-detail-url') || '';
                    if (cancelDetailUrl) {
                        openDrawerWithUrl(cancelDetailUrl);
                    }
                    return;
                }

                var documentDeleteButton = event.target.closest('[data-omo-calendar-document-delete-id]');
                if (documentDeleteButton) {
                    event.preventDefault();
                    event.stopPropagation();
                    deleteAssociatedDocument(documentDeleteButton);
                    return;
                }

                var deleteButton = event.target.closest('[data-omo-calendar-delete-url]');
                if (deleteButton) {
                    event.preventDefault();
                    deleteCalendarEvent(deleteButton);
                    return;
                }

                var editButton = event.target.closest('[data-omo-calendar-open-edit-url]');
                if (!editButton) {
                    var invitationButton = event.target.closest('[data-omo-calendar-open-invitations-url]');
                    if (invitationButton) {
                        event.preventDefault();
                        if (typeof window.commonTopbarOpenModal !== 'function') {
                            return;
                        }

                        var invitationUrl = invitationButton.getAttribute('data-omo-calendar-open-invitations-url') || '';
                var invitationTitle = invitationButton.getAttribute('data-omo-calendar-open-invitations-title') || 'Invités';
                        if (!invitationUrl) {
                            return;
                        }

                        window.commonTopbarOpenModal(invitationTitle, resolveUrl(invitationUrl), 'fetch');
                        return;
                    }

                    var openUrlButton = event.target.closest('[data-omo-calendar-open-url]');
                    if (!openUrlButton) {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();
                    var targetUrl = openUrlButton.getAttribute('data-omo-calendar-open-url') || '';
                    var targetTitle = openUrlButton.getAttribute('data-omo-calendar-open-url-title') || 'Document';
                    var targetPvEditorUrl = openUrlButton.getAttribute('data-omo-calendar-open-pv-editor-url') || '';
                    if (!targetUrl) {
                        return;
                    }

                    if (typeof window.omoOpenAssociatedDocumentResult === 'function') {
                        window.omoOpenAssociatedDocumentResult(
                            targetUrl,
                            targetTitle,
                            targetPvEditorUrl,
                            'omo-pv-preparation-calendar-'
                        );
                        return;
                    }

                    openDrawerWithUrl(targetUrl);
                    return;
                }

                event.preventDefault();
                var editUrl = editButton.getAttribute('data-omo-calendar-open-edit-url') || '';
                if (!editUrl) {
                    return;
                }

                openDrawerWithUrl(editUrl);
            });

        }

        if (drawerBody) {
            drawerBody.addEventListener('change', function (event) {
                var form = event.target && event.target.form ? event.target.form : null;
                if (form && form.matches('[data-omo-calendar-create-form]')) {
                    syncLocationFields(form);
                    syncDocumentFields(form);
                    if (event.target.matches('[data-omo-calendar-context-holon]')) {
                        syncInvitationDefaultHolonSelection(
                            form.querySelector('[data-omo-calendar-invitations-editor]'),
                            event.target.value
                        );
                    }
                }

                var startField = event.target.closest('input[name="start_at"]');
                if (!startField) {
                    var scheduleField = event.target.closest('input[name="end_at"]');
                    if (scheduleField && scheduleField.form) {
                        rememberScheduleState(scheduleField.form);
                    }
                    return;
                }

                syncEndDateWithStart(startField.form);
            });

            drawerBody.addEventListener('submit', function (event) {
                var form = event.target.closest('[data-omo-calendar-create-form]');
                if (!form) {
                    return;
                }

                event.preventDefault();
                if (form.dataset.omoCalendarSubmitPending === '1') {
                    return;
                }
                form.dataset.omoCalendarSubmitPending = '1';

                var feedback = form.querySelector('[data-omo-calendar-create-feedback]');
                var submitButton = form.querySelector('[data-omo-calendar-create-submit]');
                if (!submitButton && form.id) {
                    submitButton = drawer.querySelector('[data-omo-calendar-create-submit][form="' + form.id + '"]');
                }
                var formData = new FormData(form);
                var usesSharedPendingState = typeof window.omoBeginPendingAction === 'function';

                if (usesSharedPendingState && !window.omoBeginPendingAction(form)) {
                    return;
                }
                if (!usesSharedPendingState && submitButton) {
                    submitButton.disabled = true;
                }

                if (feedback) {
                    feedback.textContent = '';
                    feedback.className = 'omo-calendar-create__feedback';
                }

                if (window.omoCalendarSetAvailabilityPending) { window.omoCalendarSetAvailabilityPending(form, true); }
                fetch(resolveUrl(form.getAttribute('action') || createUrl), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                }).then(function (response) {
                    return response.json();
                }).then(function (payload) {
                    if (!payload || payload.status !== true) {
                        if (window.omoCalendarShowAvailability && window.omoCalendarShowAvailability(form, payload)) {
                            return;
                        }
                        throw payload || new Error('save_failed');
                    }

                    if (typeof window.omoInvalidateMainRightPanel === 'function') {
                        window.omoInvalidateMainRightPanel();
                    }

                    var refreshPromise = refreshCalendar(currentUrl);
                    if (payload.detailUrl) {
                        if (refreshPromise && typeof refreshPromise.then === 'function') {
                            refreshPromise.then(function () {
                                if (typeof window.omoCalendarOpenEventDrawer === 'function') {
                                    window.omoCalendarOpenEventDrawer(payload.detailUrl);
                                }
                            }).catch(function () {
                                openDrawerWithUrl(payload.detailUrl);
                            });
                        }
                        return;
                    }

                    closeDrawer();
                }).catch(function (error) {
                    if (!feedback) {
                        return;
                    }

                    var message = error && typeof error.message === 'string' && error.message !== ''
                        ? error.message
                        : "Impossible d'enregistrer cet événement.";
                    feedback.textContent = message;
                    feedback.className = 'omo-calendar-create__feedback is-error';
                }).finally(function () {
                    if (window.omoCalendarSetAvailabilityPending) { window.omoCalendarSetAvailabilityPending(form, false); }
                    delete form.dataset.omoCalendarSubmitPending;
                    if (usesSharedPendingState && typeof window.omoEndPendingAction === 'function') {
                        window.omoEndPendingAction(form);
                    } else if (submitButton) {
                        submitButton.disabled = false;
                    }
                });
            });
        }

        initializeCalendarViewFilter();

        updateCurrentTimeIndicators();
        var calendarNowIndicatorTimer = window.setInterval(function () {
            if (!document.body.contains(root)) {
                window.clearInterval(calendarNowIndicatorTimer);
                return;
            }
            updateCurrentTimeIndicators();
        }, 30000);

        if (!root.__omoCalendarRouteHandler) {
            root.__omoCalendarRouteHandler = function (routeEvent) {
                if (!document.body.contains(root)) {
                    return;
                }

                var detail = routeEvent && routeEvent.detail
                    ? routeEvent.detail
                    : {};
                var targetEventId = Number(detail.eventId || 0);

                if (targetEventId > 0) {
                    openEventFromRoute(targetEventId);
                    return;
                }

                closeDrawer({ force: true });
            };

            window.addEventListener('omo-calendar-route-change', root.__omoCalendarRouteHandler);
        }

        window.requestAnimationFrame(function () {
            updateCurrentTimeIndicators();
            scrollTimelineToRelevantTime(currentView);
            if (initialOpenEventId > 0) {
                focusRouteTargetEvent('auto');
                maybeOpenInitialEventDetail();
            }
        });
};
