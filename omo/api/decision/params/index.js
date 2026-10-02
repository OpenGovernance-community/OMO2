window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/decision/params/index.js"] = function (pageConfig, pageScript) {
(function () {
    var root = document.querySelector('[data-omo-decision-params]');
    if (!root || root.dataset.omoDecisionParamsBound === '1') return;
    root.dataset.omoDecisionParamsBound = '1';
    var form = root.querySelector('[data-omo-decision-params-form]');
    var governanceToggle = root.querySelector('[data-omo-decision-params-governance-enabled]');
    var governanceFields = root.querySelector('[data-omo-decision-params-governance-fields]');
    var liveVotesToggle = root.querySelector('[data-omo-decision-params-live-votes]');
    var anonymousFields = root.querySelector('[data-omo-decision-params-anonymous-fields]');
    var feedback = root.querySelector('[data-omo-decision-params-feedback]');
    var submit = root.querySelector('[data-omo-decision-params-submit]');
    var saveLabel = pageConfig.saveLabel;
    var savingLabel = pageConfig.savingLabel;
    var successLabel = pageConfig.successLabel;
    function sync() {
        var governanceEnabled = !!(governanceToggle && governanceToggle.checked);
        var liveVotesEnabled = governanceEnabled && !!(liveVotesToggle && liveVotesToggle.checked);
        if (governanceFields) governanceFields.hidden = !governanceEnabled;
        if (anonymousFields) anonymousFields.hidden = !liveVotesEnabled;
    }
    function showFeedback(message, error) {
        if (message && typeof window.commonNotify === 'function') {
            var notice = feedback;
            if (notice) { notice.textContent = ''; notice.hidden = true; }
            window.commonNotify(String(message), error ? 'error' : 'success');
            return;
        }

        if (!feedback) return;
        feedback.hidden = false;
        feedback.textContent = String(message || '');
        feedback.classList.toggle('is-error', !!error);
    }
    if (governanceToggle) governanceToggle.addEventListener('change', sync);
    if (liveVotesToggle) liveVotesToggle.addEventListener('change', sync);
    if (form) form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!form.reportValidity()) return;
        if (submit) { submit.disabled = true; submit.textContent = savingLabel; }
        window.fetch(form.action, {method: 'POST', credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}, body: new FormData(form)})
            .then(function (response) { return response.json().then(function (payload) { return {ok: response.ok, payload: payload}; }); })
            .then(function (response) {
                if (!response.ok || !response.payload || response.payload.status !== true) throw new Error(response.payload && response.payload.message ? response.payload.message : 'Erreur');
                showFeedback(response.payload.message || successLabel, false);
            })
            .catch(function (error) { showFeedback(error && error.message ? error.message : 'Erreur', true); })
            .finally(function () { if (submit) { submit.disabled = false; submit.textContent = saveLabel; } });
    });
    sync();
})();
};
