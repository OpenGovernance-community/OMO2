(() => {
    'use strict';
    const element = document.getElementById('visio-config');
    if (!element) return;
    const config = JSON.parse(element.textContent);
    if (config.saved) window.commonNotify(config.messages.saved, 'success');
    document.querySelectorAll('[data-visio-delete]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });
    if (!config.access) return;
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
        const cameraRestricted = !!config.access.settings.camera_restricted;
        const api = new window.JitsiMeetExternalAPI(config.domain, {
            roomName: config.room,
            jwt: config.access.jwt,
            parentNode: document.getElementById('visio-frame'),
            width: '100%', height: '100%', lang: config.language,
            userInfo: config.access.name ? {displayName: config.access.name} : {},
            configOverwrite: {subject: config.title, callDisplayName: config.title, prejoinConfig: {enabled: true}, disableDeepLinking: true,
                securityUi: {disableLobbyPassword: !config.access.nativePasswordEnabled},
                hiddenPremeetingButtons: cameraRestricted ? ['camera'] : [],
                startWithAudioMuted: !!config.access.settings.audio_muted,
                startWithVideoMuted: !!(config.access.settings.video_muted || config.access.settings.camera_restricted)}
        });
        let disablingCamera = false;
        const enforceCameraOff = async () => {
            if (!cameraRestricted || disablingCamera) return;
            disablingCamera = true;
            try { if (!await api.isVideoMuted()) api.executeCommand('toggleVideo'); }
            catch (_) { /* The iframe may already be closing. The browser policy still blocks capture. */ }
            finally { disablingCamera = false; }
        };
        api.addListener('videoMuteStatusChanged', event => { if (!event.muted) enforceCameraOff(); });
        api.addListener('videoConferenceJoined', () => { status.textContent = ''; enforceCameraOff(); });
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
