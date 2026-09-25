window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/deferred_proposals/pv_rule_editor.js"] = function (pageConfig, pageScript) {
(function () {
    const root = document.querySelector('[data-deferred-pv-rule-editor]'); if (!root) return;
    const form = root.querySelector('form'), operation = root.querySelector('[data-deferred-operation]'), existing = root.querySelector('[data-deferred-existing]'), ruleId = root.querySelector('[data-deferred-rule-id]'), fields = root.querySelector('[data-deferred-fields]'), feedback = root.querySelector('[data-deferred-feedback]'), submit = root.querySelector('[data-deferred-submit]');
    const holonInput = root.querySelector('[data-deferred-holon-id]'), holonLabel = root.querySelector('[data-deferred-holon-label]'), permissionMessage = root.querySelector('[data-deferred-holon-permission]');
    const pickerLayer = root.querySelector('[data-deferred-holon-picker]'), pickerHost = root.querySelector('[data-deferred-holon-map]'), candidateLabel = root.querySelector('[data-deferred-holon-candidate-label]'), candidateStatus = root.querySelector('[data-deferred-holon-candidate-status]'), chooseHolon = root.querySelector('[data-deferred-holon-choose]');
    const labels = pageConfig.labels;
    const permissions = pageConfig.permissions;
    let ruleStates = pageConfig.ruleStates;
    const proposalId = pageConfig.proposalId;
    const workflow = pageConfig.workflow;
    const texts = pageConfig.texts;
    let previousOperation = operation.value, picker = null, candidateHolonId = Number(holonInput.value || 0), contextRequest = 0;

    function allowedIds() { return Array.isArray(permissions[operation.value]) ? permissions[operation.value].map(Number) : []; }
    function isAllowed(id) { return allowedIds().indexOf(Number(id)) !== -1; }
    function showFeedback(message) { feedback.hidden = !message; feedback.textContent = message || ''; }
    function populateFromSelectedRule() { const state = ruleStates[String(ruleId.value || '')]; if (!state) return; ['title','intention','description','review_date','expiration_date','scope','IDauthority'].forEach(function (name) { const field = form.elements.namedItem(name); if (field && Object.prototype.hasOwnProperty.call(state, name)) field.value = String(state[name] == null ? '' : state[name]); }); window.omoInitRuleScopeFields(form, null, state); }
    function updateSubmitState() { const allowed = isAllowed(holonInput.value); permissionMessage.hidden = allowed; submit.disabled = !allowed || (operation.value !== 'create' && !Number(ruleId.value)); }
    function sync(populateRule) { const deletion = operation.value === 'delete'; window.omoInitRuleScopeFields(form); fields.querySelectorAll('select').forEach(function (field) { field.disabled = deletion; }); existing.hidden = workflow || operation.value === 'create'; fields.hidden = deletion; fields.querySelectorAll('input,textarea').forEach(function (field) { field.required = !deletion && ['title','description','review_date','expiration_date'].indexOf(field.name) !== -1; }); if (populateRule && operation.value === 'update') populateFromSelectedRule(); updateSubmitState(); }
    function replaceRules(items, selectedId, populateRule) {
        ruleStates = {}; ruleId.innerHTML = '';
        (Array.isArray(items) ? items : []).forEach(function (item) { const option = document.createElement('option'); option.value = String(item.id || ''); option.textContent = String(item.title || ''); if (Number(item.id) === Number(selectedId)) option.selected = true; ruleId.appendChild(option); ruleStates[String(item.id)] = item.state || {}; });
        if (!ruleId.options.length) { const option = document.createElement('option'); option.value = ''; option.textContent = texts.emptyRules; ruleId.appendChild(option); ruleId.disabled = true; } else { ruleId.disabled = false; }
        if (populateRule && operation.value === 'update') populateFromSelectedRule(); updateSubmitState();
    }
    function loadContext(selectedId, populateRule) {
        const selectedHolonId = Number(selectedId || holonInput.value || 0), requestId = ++contextRequest;
        if (!isAllowed(selectedHolonId)) { replaceRules([], 0, false); updateSubmitState(); return Promise.resolve(false); }
        submit.disabled = true; showFeedback('');
        const url = ("/omo/api/deferred_proposals/pv_rule_context.php?oid=" + pageConfig.organizationId + "&point_id=" + pageConfig.pointId + "&holon_id=") + encodeURIComponent(String(selectedHolonId)) + '&operation=' + encodeURIComponent(operation.value);
        return fetch(url, {credentials:'same-origin'}).then(function (response) { return response.json().then(function (payload) { return {ok:response.ok,payload:payload}; }); }).then(function (result) { if (requestId !== contextRequest) return false; if (!result.ok || !result.payload.status) throw new Error(result.payload.message || texts.contextError); if (result.payload.holon && result.payload.holon.label) { labels[String(selectedHolonId)] = String(result.payload.holon.label); holonLabel.value = String(result.payload.holon.label); } window.omoInitRuleScopeFields(form, result.payload.scopeContext); replaceRules(result.payload.rules, 0, populateRule); sync(false); return true; }).catch(function (error) { if (requestId === contextRequest) { replaceRules([], 0, false); showFeedback(error.message || texts.contextError); } return false; });
    }
    function updateCandidate(id) { candidateHolonId = Number(id || 0); const allowed = isAllowed(candidateHolonId); candidateLabel.textContent = String(labels[String(candidateHolonId)] || (picker && picker.getSelectedHolonLabel ? picker.getSelectedHolonLabel() : '') || ''); candidateStatus.textContent = allowed ? texts.allowed : texts.denied; chooseHolon.disabled = !allowed; }
    function closePicker() { pickerLayer.hidden = true; if (picker && typeof picker.destroy === 'function') picker.destroy(); picker = null; pickerHost.innerHTML = ''; }
    function openPicker() { if (typeof window.omoMountHolonScopePicker !== 'function') { showFeedback(texts.pickerUnavailable); return; } candidateHolonId = Number(holonInput.value || 0); pickerLayer.hidden = false; picker = window.omoMountHolonScopePicker({host:pickerHost, organizationId:pageConfig.organizationId, initialHolonId:candidateHolonId, selectableHolonIds:allowedIds(), showModes:false, initialScope:'local', labelMode:'context', suppressInitialChange:true, onChange:updateCandidate, onReady:updateCandidate}); updateCandidate(candidateHolonId); }

    operation.addEventListener('change', function () { const populateRule = operation.value === 'update' && operation.value !== previousOperation; previousOperation = operation.value; sync(false); loadContext(holonInput.value, populateRule); });
    ruleId.addEventListener('change', function () { if (operation.value === 'update') populateFromSelectedRule(); updateSubmitState(); });
    root.querySelector('[data-deferred-holon-open]').addEventListener('click', openPicker);
    root.querySelectorAll('[data-deferred-holon-close]').forEach(function (button) { button.addEventListener('click', closePicker); });
    pickerLayer.addEventListener('click', function (event) { if (event.target === pickerLayer) closePicker(); });
    chooseHolon.addEventListener('click', function () { if (!isAllowed(candidateHolonId)) return; holonInput.value = String(candidateHolonId); holonLabel.value = String(labels[String(candidateHolonId)] || candidateLabel.textContent || ''); closePicker(); loadContext(candidateHolonId, operation.value === 'update'); });
    root.querySelector('[data-deferred-cancel]').addEventListener('click', function () { if (pageConfig.workflow2) { window.dispatchEvent(new CustomEvent('omo-deferred-proposal-editor-back')); return; } if (window.commonTopbarCloseModal) window.commonTopbarCloseModal(); });
    window.omoInitRuleScopeFields(form);
    sync(proposalId <= 0 && operation.value === 'update');
    form.addEventListener('submit', function (event) { event.preventDefault(); showFeedback(''); if (!isAllowed(holonInput.value)) { showFeedback(texts.noPermission); return; } if (!form.reportValidity()) return; submit.disabled = true; fetch(form.action, {method:'POST', body:new FormData(form), credentials:'same-origin'}).then(function (response) { return response.json().then(function (payload) { return {ok:response.ok,payload:payload}; }); }).then(function (result) { if (!result.ok || !result.payload.status) throw new Error(result.payload.message || texts.saveError); window.dispatchEvent(new CustomEvent('omo-deferred-proposal-saved', {detail:{pointId:Number(result.payload.pointId || pageConfig.pointId),proposalId:Number(result.payload.id || 0)}})); if (window.commonTopbarCloseModal) window.commonTopbarCloseModal(); }).catch(function (error) { showFeedback(error.message || texts.saveError); updateSubmitState(); }); });
}());
};
