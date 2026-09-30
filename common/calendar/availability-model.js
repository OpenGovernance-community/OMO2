(function (root) {
    'use strict';

    // Codes supplied by the server: 0 outside working hours, 1 free, 2 busy, 3 pause.
    function aggregateDay(people, date) {
        var slots = [];
        var working = 0;
        var occupied = 0;
        if (!people.length) { return {state: 'closed', slots: slots, workingCount: 0, busySlotCount: 0}; }
        for (var index = 0; index < 48; index++) {
            var codes = people.map(function (person) { return (person.days[date] || '')[index] || '0'; });
            if (codes.includes('0')) { continue; }
            var pause = codes.includes('3');
            var busyPeople = people.filter(function (person, personIndex) { return codes[personIndex] === '2'; });
            var busyCount = busyPeople.length;
            if (!pause) {
                working++;
                if (busyCount) { occupied++; }
            }
            slots.push({index: index, pause: pause, busy: busyCount > 0, busyCount: busyCount, participantCount: people.length,
                busyNames: busyPeople.map(function (person) { return person.name; })});
        }
        return {state: !working ? 'closed' : (!occupied ? 'free' : (occupied === working ? 'full' : 'partial')),
            slots: slots, workingCount: working, busySlotCount: occupied};
    }

    // Green is reserved for zero conflicts; occupied slots run continuously from amber to red.
    function occupationHue(busyCount, participantCount) {
        return 48 * (1 - Math.max(0, Math.min(1, busyCount / Math.max(1, participantCount))));
    }

    // Prefer following slots, then shift backwards while retaining the clicked slot.
    function selectRange(slots, clicked, count) {
        if (!Number.isInteger(count) || count < 1 || clicked < 0 || clicked >= slots.length || !slots[clicked].free) { return null; }
        for (var first = clicked; first >= Math.max(0, clicked - count + 1); first--) {
            var last = first + count - 1;
            if (last >= slots.length) { continue; }
            var valid = true;
            for (var index = first; index <= last; index++) {
                if (!slots[index].free || (index > first && slots[index - 1].end !== slots[index].start)) {
                    valid = false;
                    break;
                }
            }
            if (valid) { return {first: first, last: last}; }
        }
        return null;
    }

    var model = {aggregateDay: aggregateDay, occupationHue: occupationHue, selectRange: selectRange};
    if (typeof module === 'object' && module.exports) { module.exports = model; }
    else { root.omoCalendarAvailabilityModel = model; }
})(typeof window === 'undefined' ? globalThis : window);
