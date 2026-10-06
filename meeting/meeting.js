(function () {
    'use strict';
    var payload = document.querySelector('[data-meeting-month-data]');
    var layout = document.querySelector('.meeting-layout');
    if (!payload || !layout) { return; }
    var view = window.omoCalendarAvailabilityView;
    var model = window.omoCalendarAvailabilityModel;
    var data = JSON.parse(payload.textContent);
    var durationSelect = document.querySelector('[data-meeting-duration]');
    var durationMinutes = durationSelect ? Number(durationSelect.value) : data.durationSlots * 30;
    var methodSelect = document.querySelector('[data-meeting-method]');
    var methodId = methodSelect ? methodSelect.value : '';
    var calendar = layout.querySelector('section');
    var panel = layout.querySelector('#meeting-times');
    var requestId = 0;
    var selection = null;
    var selectedDate = new URL(location.href).searchParams.get('date') || '';
    var notice = view.element('p', 'generic-feedback is-error');
    notice.hidden = true;
    notice.setAttribute('role', 'alert');
    layout.before(notice);

    var months = view.monthCache(function (url) {
        return fetch(url, {credentials: 'same-origin'}).then(function (response) {
            if (!response.ok) { throw new Error('load'); }
            return response.text();
        }).then(function (html) {
            var page = new DOMParser().parseFromString(html, 'text/html');
            var json = page.querySelector('[data-meeting-month-data]');
            var section = page.querySelector('.meeting-layout > section');
            if (!json || !section) { throw new Error('load'); }
            return {data: JSON.parse(json.textContent), calendar: section.outerHTML};
        });
    });
    months.set(data.month, {data: data, calendar: calendar.outerHTML});

    function rangeError() {
        return view.text(data.labels.range_unavailable, {count: durationMinutes / 30, duration: durationMinutes});
    }
    function showError(message) {
        notice.hidden = true;
        if (typeof window.commonNotify === 'function') {
            window.commonNotify(message, {type: 'error', duration: 5000});
        } else {
            notice.textContent = message;
            notice.hidden = false;
            window.setTimeout(function () { notice.hidden = true; }, 5000);
        }
    }

    function refreshDays() {
        var now = Date.now() / 1000;
        Object.keys(data.days).forEach(function (date) {
            var day = data.days[date];
            day.slots.forEach(function (slot) { if (slot.startEpoch <= now) { slot.free = false; } });
            var ranges = day.slots.map(function (slot) {
                return {free: slot.free && !slot.pause, start: slot.startEpoch, end: slot.startEpoch + 1800,
                    beforeFree: slot.beforeFree !== false && slot.startEpoch - (data.preparationMinutes || 0) * 60 > now,
                    afterFree: slot.afterFree !== false};
            });
            day.ranges = day.slots.map(function (_slot, index) { return model.selectRange(ranges, index, durationMinutes / 30); });
            day.workingCount = day.slots.filter(function (slot) { return !slot.pause; }).length;
            day.busySlotCount = day.slots.filter(function (slot, index) { return !slot.pause && !day.ranges[index]; }).length;
            if (day.workingCount) {
                day.state = day.busySlotCount === 0 ? 'free' : (day.busySlotCount === day.workingCount ? 'full' : 'partial');
            }
        });
        calendar.querySelectorAll('[data-date]').forEach(function (button) {
            var date = button.dataset.date;
            view.paintDay(button, data.days[date], data.dates[date], data.labels, date === selectedDate);
        });
        syncMethodLinks();
    }
    function syncMethodLinks() {
        layout.querySelectorAll('a[href]').forEach(function (link) {
            var url = new URL(link.getAttribute('href'), location.href);
            if (url.pathname === data.path && (url.searchParams.has('month') || url.searchParams.has('date'))) {
                url.searchParams.set('duration', String(durationMinutes));
                if (methodId) { url.searchParams.set('method', methodId); }
                else { url.searchParams.delete('method'); }
                link.href = url.href;
            }
        });
    }
    function updateSteps(details) {
        document.querySelectorAll('.meeting-steps li').forEach(function (step, index) {
            var active = details ? 1 : 0;
            if (index === active) { step.setAttribute('aria-current', 'step'); }
            else { step.removeAttribute('aria-current'); }
            step.dataset.complete = index < active ? 'true' : 'false';
            step.querySelector('.meeting-steps__number').textContent = String(index + 1);
        });
    }
    function renderDay(date) {
        selectedDate = date;
        selection = null;
        notice.hidden = true;
        refreshDays();
        panel.replaceChildren();
        updateSteps(false);
        var day = data.days[date];
        var heading = view.element('div', '');
        if (day) { heading.appendChild(view.element('p', 'meeting-eyebrow', data.weekdays[date])); }
        var title = view.element('h2', 'generic-card-title generic-card-title--large', day ? data.dates[date] : data.labels.select_day);
        title.id = 'meeting-day';
        heading.appendChild(title);
        panel.appendChild(heading);
        panel.appendChild(view.element('p', 'meeting-muted', day ? data.labels.select_time : data.labels.day_hint));
        if (!day) { return; }
        if (!day.slots.length || day.state === 'full') { panel.appendChild(view.element('p', 'generic-help-text', data.labels.no_slots)); }
        var slots = view.element('div', 'meeting-slots');
        var inPause = false;
        day.slots.forEach(function (slot, index) {
            if (slot.pause) {
                if (!inPause) {
                    var pause = view.element('div', 'meeting-slots__pause');
                    pause.setAttribute('role', 'separator');
                    pause.setAttribute('aria-label', data.labels.pause);
                    slots.appendChild(pause);
                }
                inPause = true;
                return;
            }
            inPause = false;
            var selectable = !!day.ranges[index];
            var button = view.element('button', 'generic-action-button meeting-slot', slot.time + ' - ' + slot.end);
            button.type = 'button';
            button.dataset.slotIndex = String(index);
            updateSlotState(button, slot, selectable);
            button.setAttribute('aria-pressed', 'false');
            slots.appendChild(button);
        });
        panel.appendChild(slots);
        var next = view.element('a', 'generic-action-button generic-action-button--main generic-action-button--wide', data.labels.continue);
        next.setAttribute('data-meeting-continue', '');
        next.hidden = true;
        panel.appendChild(next);
    }
    function updateSlotState(button, slot, selectable) {
        var limited = slot.free && !selectable;
        var label = data.labels[limited ? 'duration_limited' : (selectable ? 'free' : 'occupied')];
        button.setAttribute('aria-disabled', selectable ? 'false' : 'true');
        button.setAttribute('aria-label', button.textContent + ' : ' + label);
        button.classList.toggle('generic-action-button--choice', slot.free);
        button.classList.toggle('generic-action-button--choice-limited', limited);
        button.classList.toggle('generic-action-button--secondary', !slot.free);
        button.classList.toggle('generic-action-button--unavailable', !slot.free);
        if (selectable) { button.removeAttribute('title'); }
        else { button.title = label; }
    }
    function selectSlot(index) {
        refreshDays();
        var day = data.days[selectedDate];
        selection = day.ranges[index];
        panel.querySelectorAll('[data-slot-index]').forEach(function (button) {
            var slotIndex = Number(button.dataset.slotIndex);
            var selectable = !!day.ranges[slotIndex];
            updateSlotState(button, day.slots[slotIndex], selectable);
            button.setAttribute('aria-pressed', selection && slotIndex >= selection.first && slotIndex <= selection.last ? 'true' : 'false');
        });
        var next = panel.querySelector('[data-meeting-continue]');
        next.hidden = !selection;
        notice.hidden = true;
        if (!selection) { showError(rangeError()); return; }
        next.href = data.path + '?date=' + selectedDate + '&time=' + day.slots[selection.first].time + '&duration=' + durationMinutes + (methodId ? '&method=' + encodeURIComponent(methodId) : '') + '#meeting-times';
    }
    function scrollToSlots() {
        if (!data.days[selectedDate] || !window.matchMedia || !window.matchMedia('(max-width: 860px)').matches) { return; }
        var heading = panel.querySelector('#meeting-day');
        if (heading) {
            heading.setAttribute('tabindex', '-1');
            heading.focus({preventScroll: true});
        }
        panel.scrollIntoView({block: 'start', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth'});
    }
    function navigate(url, push, focusSlots) {
        var requestedMethod = url.searchParams.get('method');
        if (methodSelect && requestedMethod && Array.from(methodSelect.options).some(function (option) { return option.value === requestedMethod; })) {
            methodId = requestedMethod;
            methodSelect.value = methodId;
        }
        if (methodId) { url.searchParams.set('method', methodId); }
        var requestedDuration = Number(url.searchParams.get('duration') || durationMinutes);
        if (requestedDuration >= 30 && requestedDuration % 30 === 0 && requestedDuration <= data.maxDuration) {
            durationMinutes = requestedDuration;
            if (durationSelect) { durationSelect.value = String(durationMinutes); }
        }
        url.searchParams.set('duration', String(durationMinutes));
        var date = url.searchParams.get('date') || '';
        var month = date.slice(0, 7) || url.searchParams.get('month') || data.month;
        var request = ++requestId;
        if (month === data.month) {
            layout.removeAttribute('aria-busy');
            renderDay(date);
            if (push) { history.pushState(null, '', url); }
            if (focusSlots) { scrollToSlots(); }
            return;
        }
        layout.setAttribute('aria-busy', 'true');
        notice.hidden = false;
        notice.className = 'generic-feedback';
        notice.textContent = data.labels.loading_month;
        months.load(month, data.path + '?month=' + encodeURIComponent(month)).then(function (entry) {
            if (request !== requestId) { return; }
            var template = document.createElement('template');
            template.innerHTML = entry.calendar;
            var replacement = template.content.firstElementChild;
            calendar.replaceWith(replacement);
            calendar = replacement;
            data = entry.data;
            renderDay(date);
            if (push) { history.pushState(null, '', url); }
            if (focusSlots) { scrollToSlots(); }
        }).catch(function () {
            if (request === requestId) {
                showError(data.labels.unavailable);
            }
        }).finally(function () {
            if (request === requestId) { layout.removeAttribute('aria-busy'); notice.className = 'generic-feedback is-error'; }
        });
    }
    layout.addEventListener('click', function (event) {
        var slot = event.target.closest('[data-slot-index]');
        if (slot) { selectSlot(Number(slot.dataset.slotIndex)); return; }
        var link = event.target.closest('a[href]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) { return; }
        var url = new URL(link.href);
        // The details/review step still visits the server and revalidates the whole appointment.
        if (url.pathname !== data.path || url.searchParams.has('time')) { return; }
        if (!url.searchParams.has('month') && !url.searchParams.has('date')) { return; }
        event.preventDefault();
        navigate(url, true, url.searchParams.has('date'));
    });
    window.addEventListener('popstate', function () {
        var url = new URL(location.href);
        if (url.searchParams.has('time')) { location.reload(); return; }
        navigate(url, false);
    });
    if (durationSelect) {
        durationSelect.addEventListener('change', function () {
            durationMinutes = Number(durationSelect.value);
            var url = new URL(location.href);
            url.searchParams.delete('time');
            url.searchParams.set('duration', String(durationMinutes));
            navigate(url, true);
        });
    }
    if (methodSelect) {
        methodSelect.addEventListener('change', function () {
            methodId = methodSelect.value;
            syncMethodLinks();
            var field = panel.querySelector('input[name="method"]');
            if (field) { field.value = methodId; }
            var url = new URL(location.href);
            url.searchParams.set('method', methodId);
            history.replaceState(null, '', url);
        });
    }
    refreshDays();
    if (!panel.querySelector('form')) { renderDay(selectedDate); }
})();
