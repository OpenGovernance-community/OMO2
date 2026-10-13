window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts['/omo/api/holons/archive.js'] = function (config) {
    const panel = document.querySelector('[data-holon-archives]');
    if (!panel) return;
    panel.addEventListener('click', function (event) {
        const button = event.target.closest('[data-archive-open]');
        if (button) window.commonTopbarOpenModal(config.title, button.dataset.archiveOpen, 'fetch');
    });
    const form = panel.querySelector('form');
    if (!form) return;
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const submit = form.querySelector('[type="submit"]');
        if (!submit || submit.disabled) return;
        submit.disabled = true;
        try {
            const response = await fetch(form.getAttribute('action'), {method: 'POST', body: new FormData(form), credentials: 'same-origin'});
            const result = await response.json().catch(() => null);
            if (!response.ok || !result || !result.status) throw new Error(result && result.message || config.error);
            window.omoNotify(result.message, 'success');
            window.commonTopbarCloseModal();
            const cid = Number(result.targetId) === Number(config.rootId) ? null : Number(result.targetId);
            if (typeof navigate === 'function') navigate(config.organizationId, cid, null);
            if (typeof window.omoReloadStructureAndFocus === 'function') window.omoReloadStructureAndFocus(cid, {quickZoom: true});
            else window.dispatchEvent(new CustomEvent('omo-structure-refresh', {detail: {cid: cid}}));
        } catch (error) {
            window.omoNotify(error.message || config.error, 'error');
            submit.disabled = false;
        }
    });
};
