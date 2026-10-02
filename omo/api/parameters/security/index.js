window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts['/omo/api/parameters/security/index.js'] = function (pageConfig) {
    var root = document.querySelector('[data-omo-security]');
    if (!root || root.dataset.ready === '1') { return; }
    root.dataset.ready = '1';
    var form = root.querySelector('form');
    var button = root.querySelector('[data-omo-security-save]');
    var backupButton = root.querySelector('[data-omo-security-backup-now]');
    var feedback = root.querySelector('[data-omo-security-feedback]');
    var enabled = form.querySelector('input[type="checkbox"][name="enabled"]');
    function showMessage(message, type) {
        feedback.textContent = '';
        feedback.className = 'generic-feedback generic-feedback--collapse-empty';
        if (typeof window.commonNotify === 'function') {
            window.commonNotify(message, type, { duration: type === 'error' ? 7000 : 5000 });
            return;
        }
        feedback.className += type === 'error' ? ' is-error' : ' is-success';
        feedback.textContent = message;
    }
    function syncFields() {
        form.elements.email.disabled = false;
        form.elements.frequency.disabled = !enabled.checked;
        form.elements.email.type = 'email';
    }
    enabled.addEventListener('change', syncFields);
    syncFields();
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (button.disabled) { return; }
        var data = new FormData(form);
        var sendNow = event.submitter === backupButton;
        if (sendNow) { data.set('backup_now', '1'); }
        data.set('enabled', enabled.checked ? '1' : '0');
        // Preserve configuration when disabling backups.
        data.set('email', form.elements.email.value);
        data.set('frequency', form.elements.frequency.value);
        data.set('organization_id', root.dataset.organizationId);
        data.set('csrf', root.dataset.csrf);
        button.disabled = true;
        backupButton.disabled = true;
        feedback.className = 'generic-feedback generic-feedback--collapse-empty';
        feedback.textContent = sendNow ? pageConfig.sending : '';
        fetch('/omo/api/parameters/security/save.php', {
            method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json' }, body: data
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || payload.status !== 'ok') { throw new Error(payload.message || pageConfig.error); }
                return payload;
            });
        }).then(function (payload) {
            showMessage(payload.message, 'success');
            if (payload.lastSentLabel) {
                root.querySelector('[data-omo-security-last]').textContent = payload.lastSentLabel;
            }
            if (typeof payload.nextDueLabel === 'string') {
                var next = root.querySelector('[data-omo-security-next]');
                next.textContent = payload.nextDueLabel;
                next.hidden = !payload.nextDueLabel;
            }
        }).catch(function (error) {
            showMessage(error.message || pageConfig.error, 'error');
        }).finally(function () { button.disabled = false; backupButton.disabled = false; });
    });
};
