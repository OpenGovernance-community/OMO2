window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts['/omo/api/parameters/calendar/index.js'] = function (pageConfig) {
    document.querySelectorAll('[data-omo-calendar-settings]').forEach(function (form) {
        if (form.dataset.calendarSettingsReady === '1') { return; }
        form.dataset.calendarSettingsReady = '1';
        var feedback = form.querySelector('[data-calendar-settings-feedback]');
        var button = form.querySelector('[type="submit"]');
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (button.disabled) { return; }
            button.disabled = true;
            feedback.textContent = '';
            try {
                var response = await fetch(form.action, {
                    method: 'POST', credentials: 'same-origin', body: new FormData(form),
                    headers: { Accept: 'application/json' }
                });
                var payload = await response.json();
                if (!response.ok || payload.status !== 'ok') { throw new Error(payload.message || pageConfig.error); }
                if (typeof window.commonDrawerMarkSaved === 'function') { window.commonDrawerMarkSaved(form); }
                if (typeof window.commonNotify === 'function') { window.commonNotify(payload.message, 'success'); }
                else { feedback.textContent = payload.message; }
            } catch (error) {
                var message = error.message || pageConfig.error;
                if (typeof window.commonNotify === 'function') { window.commonNotify(message, 'error'); }
                else { feedback.textContent = message; }
            } finally {
                button.disabled = false;
            }
        });
    });
};
