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
    document.querySelector('[data-visio-reload]')?.addEventListener('click', () => window.location.reload());
    const setStatus = message => {
        status.textContent = message;
        status.hidden = !message;
        document.getElementById('visio-status-row').hidden = !message;
    };
    const stage = document.getElementById('visio-stage');
    const frame = document.getElementById('visio-frame');
    const shell = document.querySelector('main');
    let pendingVideoLayout = false;
    const resizeVideo = () => {
        pendingVideoLayout = false;
        if (stage.hidden) return;
        const stageTop = stage.getBoundingClientRect().top;
        const initialTop = stageTop + window.scrollY;
        const progress = initialTop > 0 ? Math.max(0, Math.min(1, window.scrollY / initialTop)) : 1;
        const shellStyle = getComputedStyle(shell);
        const initialWidth = shell.clientWidth - parseFloat(shellStyle.paddingLeft) - parseFloat(shellStyle.paddingRight);
        const viewportWidth = document.documentElement.clientWidth;
        const viewportHeight = stage.clientHeight;
        // Fit the opening preview below the page content, keeping the viewport ratio.
        // Use document coordinates so scrolling cannot change the starting scale.
        const availableHeight = Math.max(0, viewportHeight - initialTop);
        const minimumScale = 360 / viewportHeight;
        const initialScale = Math.max(minimumScale, Math.min(1, initialWidth / viewportWidth, availableHeight / viewportHeight));
        const scale = initialScale + (Math.max(1, minimumScale) - initialScale) * progress;
        frame.style.setProperty('--visio-video-width', viewportWidth * scale + 'px');
        frame.style.setProperty('--visio-video-height', viewportHeight * scale + 'px');
    };
    const scheduleVideoLayout = () => {
        if (!pendingVideoLayout) {
            pendingVideoLayout = true;
            requestAnimationFrame(resizeVideo);
        }
    };
    window.addEventListener('scroll', scheduleVideoLayout, {passive: true});
    window.addEventListener('resize', scheduleVideoLayout);
    new ResizeObserver(scheduleVideoLayout).observe(shell);
    resizeVideo();
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
            configOverwrite: {brandingRoomAlias: config.room.replace(/^omo-/, ''), subject: config.title, callDisplayName: config.title, prejoinConfig: {enabled: true}, disableDeepLinking: true,
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
        api.addListener('videoConferenceJoined', () => { setStatus(''); enforceCameraOff(); });
        api.addListener('participantRoleChanged', event => {
            if (event.role === 'moderator' && config.access.moderator) api.executeCommand('subject', config.title);
        });
        api.addListener('readyToClose', () => {
            api.dispose();
            stage.hidden = true;
            window.removeEventListener('scroll', scheduleVideoLayout);
            window.removeEventListener('resize', scheduleVideoLayout);
            setStatus(config.messages.ended);
            document.getElementById('visio-rejoin').hidden = false;
        });
        api.addListener('errorOccurred', event => {
            if (event.type === 'CONNECTION' || event.isFatal) setStatus(config.messages.error);
        });
        window.addEventListener('pagehide', () => api.dispose(), {once: true});
    } catch (_) {
        setStatus(config.messages.error);
    }
})();
