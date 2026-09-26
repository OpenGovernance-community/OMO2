window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/deferred_proposals/pv_holon_editor.js"] = function (pageConfig, pageScript) {
(function () {
    const root = document.querySelector('[data-deferred-holon-selector]'); if (!root) return;
    const operation = root.querySelector('[data-deferred-operation]'), label = root.querySelector('[data-deferred-holon-label]'), permission = root.querySelector('[data-deferred-holon-permission]'), proceed = root.querySelector('[data-deferred-continue]'), pickerLayer = root.querySelector('[data-deferred-holon-picker]'), pickerHost = root.querySelector('[data-deferred-holon-map]');
    const labels = pageConfig.labels, permissions = pageConfig.permissions, texts = pageConfig.tr;
    let targetId = pageConfig.targetId, candidateId = targetId, picker = null;
    function allowedIds() { return Array.isArray(permissions[operation.value]) ? permissions[operation.value].map(Number) : []; }
    function allowed(id) { return allowedIds().indexOf(Number(id)) !== -1; }
    function sync() { const ok = allowed(targetId); label.value = String(labels[String(targetId)] || ''); permission.hidden = ok; permission.textContent = ok ? '' : texts.denied; proceed.disabled = !ok; }
    function closePicker() { pickerLayer.hidden = true; if (picker && typeof picker.destroy === 'function') picker.destroy(); picker = null; pickerHost.innerHTML = ''; }
    function updateCandidate(id) { candidateId = Number(id || 0); root.querySelector('[data-deferred-holon-choose]').disabled = !allowed(candidateId); }
    function openPicker() { if (typeof window.omoMountHolonScopePicker !== 'function') return; candidateId = targetId; pickerLayer.hidden = false; picker = window.omoMountHolonScopePicker({host:pickerHost, organizationId:pageConfig.organizationId, initialHolonId:targetId, selectableHolonIds:allowedIds(), showModes:false, initialScope:'local', labelMode:'context', suppressInitialChange:true, onChange:updateCandidate, onReady:updateCandidate}); updateCandidate(targetId); }
    operation.addEventListener('change', sync); root.querySelector('[data-deferred-holon-open]').addEventListener('click', openPicker); root.querySelectorAll('[data-deferred-holon-close]').forEach(function (button) { button.addEventListener('click', closePicker); }); root.querySelector('[data-deferred-holon-choose]').addEventListener('click', function () { if (!allowed(candidateId)) return; targetId = candidateId; closePicker(); sync(); }); root.querySelector('[data-deferred-cancel]').addEventListener('click', function () { if (window.commonTopbarCloseModal) window.commonTopbarCloseModal(); }); proceed.addEventListener('click', function () { if (!allowed(targetId) || typeof window.commonTopbarOpenModal !== 'function') return; const url = ("/omo/api/deferred_proposals/pv_holon_editor.php?stage=capture&oid=" + pageConfig.organizationId + "&point_id=" + pageConfig.pointId + "&proposal_id=" + pageConfig.proposalId + "&operation=") + encodeURIComponent(operation.value) + '&holon_id=' + encodeURIComponent(String(targetId)); window.commonTopbarOpenModal(pageConfig.tr2, url, 'fetch'); }); sync();
}());
};
