(function (window) {
    'use strict';
    function element(tag, className, text) {
        var node = document.createElement(tag);
        node.className = className;
        if (text !== undefined) { node.textContent = text; }
        return node;
    }
    function text(template, values) {
        return String(template || '').replace(/\{(\w+)\}/g, function (match, key) {
            return Object.prototype.hasOwnProperty.call(values, key) ? String(values[key]) : match;
        });
    }
    function slotTime(index) {
        return String(Math.floor(index / 2)).padStart(2, '0') + ':' + (index % 2 ? '30' : '00');
    }
    function paintDay(button, day, dateLabel, labels, selected) {
        var label = day.workingCount ? text(labels.day_availability, {
            free: day.workingCount - day.busySlotCount, total: day.workingCount
        }) : labels[day.state];
        button.dataset.state = day.state;
        if (day.busySlotCount) {
            button.dataset.busySlots = String(day.busySlotCount);
            button.style.setProperty('--param-freebusy-busy-hue', String(window.omoCalendarAvailabilityModel.occupationHue(day.busySlotCount, day.workingCount)));
        } else {
            delete button.dataset.busySlots;
            button.style.removeProperty('--param-freebusy-busy-hue');
        }
        button.title = label;
        button.setAttribute('aria-label', dateLabel + ' : ' + label);
        if (selected) { button.setAttribute('aria-current', 'date'); }
        else { button.removeAttribute('aria-current'); }
    }
    // Cache is owned by one screen/profile. Failed loads can be retried, concurrent loads are shared.
    function monthCache(loader) {
        var entries = new Map();
        return {
            set: function (key, value) { entries.set(key, Promise.resolve(value)); },
            load: function (key, url) {
                if (!entries.has(key)) {
                    var request = Promise.resolve().then(function () { return loader(url); }).catch(function (error) {
                        if (entries.get(key) === request) { entries.delete(key); }
                        throw error;
                    });
                    entries.set(key, request);
                }
                return entries.get(key);
            }
        };
    }
    function renderProfile(host, data, date) {
        var model = window.omoCalendarAvailabilityModel;
        var labels = data.labels;
        var days = {};
        Object.keys(data.dates).forEach(function (key) { days[key] = model.aggregateDay(data.people, key); });
        host.querySelectorAll('.calendar-freebusy-day[data-user-availability-url]').forEach(function (button) {
            var key = new URL(button.getAttribute('data-user-availability-url'), location.href).searchParams.get('date');
            paintDay(button, days[key], data.dates[key], labels, key === date);
        });
        var panel = host.querySelector('.calendar-freebusy-day-panel');
        panel.replaceChildren();
        var day = days[date];
        var heading = data.dates[date] || labels.select_day;
        var header = element('div', day && day.slots.length ? 'calendar-freebusy-day-head' : 'calendar-freebusy-empty');
        header.appendChild(element('strong', 'generic-card-title generic-card-title--small', heading));
        panel.appendChild(header);
        if (!day || !day.slots.length) {
            header.appendChild(element('span', '', day ? labels.no_hours : labels.select_day_hint));
            return;
        }
        var list = element('div', 'calendar-freebusy-slots');
        var previousPause = -2;
        day.slots.forEach(function (slot) {
            if (slot.pause) {
                if (slot.index !== previousPause + 1) {
                    var pause = element('div', 'calendar-freebusy-pause');
                    pause.appendChild(element('span', '', labels.pause));
                    list.appendChild(pause);
                }
                previousPause = slot.index;
                return;
            }
            var row = element('div', 'calendar-freebusy-slot');
            row.dataset.state = slot.busy ? 'busy' : 'free';
            var range = slotTime(slot.index) + ' - ' + slotTime(slot.index + 1);
            row.setAttribute('aria-label', range + ' : ' + labels[slot.busy ? 'busy' : 'available']);
            var time = element('time', '', range);
            time.dateTime = date + 'T' + slotTime(slot.index);
            row.appendChild(time);
            if (slot.busy) { row.appendChild(element('span', '', labels.busy)); }
            list.appendChild(row);
        });
        panel.appendChild(list);
    }
    window.omoCalendarAvailabilityView = {element: element, text: text, slotTime: slotTime,
        paintDay: paintDay, monthCache: monthCache, renderProfile: renderProfile};
})(window);
