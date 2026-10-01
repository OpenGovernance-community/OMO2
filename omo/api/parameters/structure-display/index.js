window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/parameters/structure-display/index.js"] = function (pageConfig, pageScript) {
(function () {
    var root = document.querySelector('[data-omo-structure-display-settings]');
    if (!root || root.dataset.omoStructureDisplaySettingsReady === '1') {
        return;
    }

    root.dataset.omoStructureDisplaySettingsReady = '1';
    var form = root.querySelector('[data-omo-structure-display-form]');
    var feedback = root.querySelector('[data-omo-structure-display-feedback]');
    var saveButton = root.querySelector('[data-omo-structure-display-save]');
    var defaults = pageConfig.defaults;

    function setFeedback(message, kind) {
        if (!feedback) {
            return;
        }

        feedback.textContent = String(message || '');
        feedback.className = 'generic-feedback generic-feedback--collapse-empty' + (kind ? ' is-' + kind : '');
    }

    root.querySelector('[data-omo-structure-display-reset]').addEventListener('click', function () {
        form.elements.fadeOpacityStep.value = String(defaults.fadeOpacityStep);
        form.elements.maxDescendantDepth.value = String(defaults.maxDescendantDepth);
        form.elements.labelAutoMinRadius.value = String(defaults.labelAutoMinRadius);
        form.elements.labelHoverMinRadius.value = String(defaults.labelHoverMinRadius);
        form.elements.labelMinFontSize.value = String(defaults.labelMinFontSize);
        form.elements.textOutlineEnabled.checked = Boolean(defaults.textOutlineEnabled);
        form.elements.showTerminalMembers.checked = Boolean(defaults.showTerminalMembers);
        setFeedback('', '');
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        saveButton.disabled = true;
        setFeedback('', '');

        fetch('/omo/api/parameters/structure-display/save.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: new FormData(form),
            headers: { Accept: 'application/json' }
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok || !payload || payload.status !== 'ok') {
                        throw new Error(payload && payload.message ? payload.message : pageConfig.message);
                    }
                    return payload;
                });
            })
            .then(function (payload) {
                setFeedback(payload.message || pageConfig.parametersStructureDisplayStatusSaved, 'success');
                if (typeof window.omoInvalidateStructureDataCache === 'function') {
                    window.omoInvalidateStructureDataCache();
                }
                if (typeof window.omoReloadStructureAndFocus === 'function') {
                    var holonId = typeof window.omoGetCurrentStructureHolonId === 'function'
                        ? window.omoGetCurrentStructureHolonId()
                        : null;
                    window.omoReloadStructureAndFocus(holonId || null, { quickZoom: true });
                }
            })
            .catch(function (error) {
                setFeedback(error && error.message ? error.message : pageConfig.message, 'error');
            })
            .finally(function () {
                saveButton.disabled = false;
            });
    });
}());
};
