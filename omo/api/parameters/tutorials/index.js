window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts['/omo/api/parameters/tutorials/index.js'] = function (pageConfig, pageScript) {
    const form = pageScript.previousElementSibling;
    if (!form || form.dataset.tutorialSettingsReady === '1') return;
    form.dataset.tutorialSettingsReady = '1';
    const checkbox = form.elements.customVideoLayout;
    const mask = form.querySelector('[data-tutorial-mask]');
    const feedback = form.querySelector('[data-tutorial-settings-feedback]');
    const button = form.querySelector('[type="submit"]');
    checkbox.addEventListener('change', () => { mask.hidden = !checkbox.checked; });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (button.disabled) return;
        feedback.textContent = '';
        button.disabled = true;
        try {
            const response = await fetch(form.action, {method: 'POST', credentials: 'same-origin', body: new FormData(form), headers: {Accept: 'application/json'}});
            const payload = await response.json();
            if (response.status === 422) { feedback.textContent = payload.message; return; }
            if (!response.ok || payload.status !== 'ok') throw new Error(payload.message || pageConfig.error);
            if (typeof window.commonDrawerMarkSaved === 'function') window.commonDrawerMarkSaved(form);
            if (typeof window.commonNotify === 'function') window.commonNotify(payload.message, 'success');
            else feedback.textContent = payload.message;
        } catch (error) {
            const message = error.message || pageConfig.error;
            if (typeof window.commonNotify === 'function') window.commonNotify(message, 'error');
            else feedback.textContent = message;
        } finally {
            button.disabled = false;
        }
    });
};
