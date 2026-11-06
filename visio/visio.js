(() => {
    'use strict';
    const element = document.getElementById('visio-config');
    if (!element) return;
    const config = JSON.parse(element.textContent);
    const status = document.getElementById('visio-status');
    document.querySelector('[data-visio-copy]')?.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(document.getElementById('visio-share').value);
            window.commonNotify(config.messages.copied, 'success');
        } catch (_) {
            window.commonNotify(config.messages.copyFailed, 'error');
        }
    });
    try {
        const api = new window.JitsiMeetExternalAPI(config.domain, {
            roomName: config.room,
            jwt: config.access.jwt,
            parentNode: document.getElementById('visio-frame'),
            width: '100%', height: '100%', lang: config.language,
            userInfo: {displayName: config.access.name},
            configOverwrite: {prejoinConfig: {enabled: true}, disableDeepLinking: true}
        });
        api.addListener('videoConferenceJoined', () => { status.textContent = ''; });
        api.addListener('participantRoleChanged', event => {
            if (event.role === 'moderator' && config.access.moderator) api.executeCommand('subject', config.title);
        });
        api.addListener('readyToClose', () => {
            api.dispose();
            document.getElementById('visio-frame').hidden = true;
            status.textContent = config.messages.ended;
        });
        api.addListener('errorOccurred', event => {
            if (event.type === 'CONNECTION' || event.isFatal) status.textContent = config.messages.error;
        });
        window.addEventListener('pagehide', () => api.dispose(), {once: true});
    } catch (_) {
        status.textContent = config.messages.error;
    }
})();
