(function (window, document) {
    'use strict';
    if (window.omoCalendarShowAvailability) { return; }

    window.omoCalendarShowAvailability = function (form, payload) {
        var panel = form.querySelector('[data-calendar-availability]');
        if (!panel || !payload || !payload.availability) { return; }
        var report = payload.availability;
        var list = panel.querySelector('[data-calendar-availability-messages]');
        list.replaceChildren();
        (report.messages || []).forEach(function (message) {
            var item = document.createElement('li');
            item.textContent = message;
            list.appendChild(item);
        });
        // Only the explicit override button sends the acknowledgement.
        panel.dataset.acknowledgement = report.acknowledgement || '';
        panel.hidden = false;
        panel.focus();
        panel.scrollIntoView({block: 'nearest'});
    };

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-calendar-availability-confirm]');
        if (!button) { return; }
        var form = button.closest('form');
        if (!form || form.dataset.omoCalendarSubmitPending === '1') { return; }
        form.elements.availability_ack.value = button.closest('[data-calendar-availability]').dataset.acknowledgement || '';
        form.requestSubmit();
    });

    function invalidate(event) {
        var form = event.target.closest('[data-omo-calendar-create-form]');
        if (!form || !form.elements.availability_ack) { return; }
        form.elements.availability_ack.value = '';
        var panel = form.querySelector('[data-calendar-availability]');
        if (panel) { panel.hidden = true; delete panel.dataset.acknowledgement; }
    }
    document.addEventListener('input', invalidate);
    document.addEventListener('change', invalidate);
})(window, document);
