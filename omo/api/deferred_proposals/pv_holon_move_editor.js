window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts['/omo/api/deferred_proposals/pv_holon_move_editor.js'] = function (config) {
    const form = document.querySelector('[data-deferred-holon-move]');
    if (!form || form.dataset.ready) return;
    form.dataset.ready = '1';
    const submit = form.querySelector('[type="submit"]');
    const feedback = form.querySelector('[data-feedback]');
    const destination = form.elements.namedItem('parent_id');
    submit.disabled = destination.options.length <= 1;
    if (submit.disabled) { feedback.textContent = config.emptyMessage; feedback.hidden = false; }
    form.querySelector('[data-cancel]').addEventListener('click', function () {
        if (config.workflow) window.dispatchEvent(new CustomEvent('omo-deferred-proposal-editor-back'));
        else if (window.commonTopbarCloseModal) window.commonTopbarCloseModal();
    });
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!form.reportValidity() || submit.disabled) return;
        submit.disabled = true;
        feedback.hidden = true;
        const body = new FormData();
        body.set('oid', config.organizationId);
        body.set('point_id', config.pointId);
        body.set('proposal_id', config.proposalId);
        body.set('holon_id', config.holonId);
        body.set('operation', 'move');
        body.set('payload', JSON.stringify({parent_id: Number(destination.value)}));
        try {
            const response = await fetch('/omo/api/deferred_proposals/pv_holon_save.php', {method: 'POST', credentials: 'same-origin', body: body});
            const result = await response.json();
            if (!response.ok || !result.status) throw new Error(result.message || config.saveError);
            window.dispatchEvent(new CustomEvent('omo-deferred-proposal-saved', {detail: {pointId: Number(result.pointId), proposalId: Number(result.id)}}));
            if (window.commonTopbarCloseModal) window.commonTopbarCloseModal();
        } catch (error) {
            feedback.textContent = error.message || config.saveError;
            feedback.hidden = false;
            submit.disabled = false;
        }
    });
};
