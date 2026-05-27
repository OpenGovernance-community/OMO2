<script src="https://player.vimeo.com/api/player.js"></script>

<script id="videoinit">
function initVideoPlayer() {
    const iframe = document.getElementById('vimeoPlayer');
    if (!iframe) return null;

    const videoPortal = document.querySelector('.video-portal');
    const playBtn = document.getElementById('playBtn');
    const progressvideoBar = document.querySelector('.progressvideo-bar');
    const progressvideo = document.querySelector('.progressvideo');
    const timeDisplay = document.getElementById('time');
    const controls = document.querySelector('.custom-controls');

    const disableVideoControls = () => {
        if (controls) {
            controls.style.display = 'none';
        }
    };

    if (
        typeof window.Vimeo === 'undefined'
        || typeof window.Vimeo.Player !== 'function'
    ) {
        disableVideoControls();
        return null;
    }

    const src = iframe.getAttribute('src') || '';
    if (!/^https:\/\/player\.vimeo\.com\/video\/\d+/i.test(src)) {
        disableVideoControls();
        return null;
    }

    let player;
    try {
        player = new Vimeo.Player(iframe);
    } catch (error) {
        console.warn('Impossible d initialiser le lecteur Vimeo.', error);
        disableVideoControls();
        return null;
    }

    const applyVideoAspectRatio = (width, height) => {
        const safeWidth = Number(width) || 0;
        const safeHeight = Number(height) || 0;
        const safeRatio = safeHeight > 0 ? (safeWidth / safeHeight) : 0;

        if (!videoPortal || safeWidth <= 0 || safeHeight <= 0 || safeRatio <= 0) {
            return;
        }

        videoPortal.style.setProperty('--video-aspect-ratio', safeWidth + ' / ' + safeHeight);
        videoPortal.style.setProperty('--video-ratio-number', String(safeRatio));
    };

    Promise.all([
        player.getVideoWidth().catch(() => 0),
        player.getVideoHeight().catch(() => 0)
    ]).then(([width, height]) => {
        applyVideoAspectRatio(width, height);
    }).catch(() => {});

    if (!playBtn || !progressvideoBar || !progressvideo || !timeDisplay) {
        return player;
    }

    let duration = 0;

    player.getDuration()
        .then(d => {
            duration = d || 0;
        })
        .catch(() => {
            duration = 0;
        });

    playBtn.addEventListener('click', async () => {
        try {
            const paused = await player.getPaused();
            if (paused) {
                await player.play();
                playBtn.textContent = "Pause";
            } else {
                await player.pause();
                playBtn.textContent = "Lire";
            }
        } catch (error) {
            console.warn('Lecture Vimeo indisponible.', error);
        }
    });

    player.on('timeupdate', data => {
        const percent = duration > 0 ? (data.seconds / duration) * 100 : 0;
        progressvideoBar.style.width = percent + "%";
        timeDisplay.textContent = formatTime(data.seconds);
    });

    progressvideo.addEventListener('click', e => {
        const rect = progressvideo.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        if (duration > 0) {
            player.setCurrentTime(percent * duration).catch(() => {});
        }
    });

    player.on('error', error => {
        console.warn('Erreur Vimeo.', error);
        disableVideoControls();
    });

    function formatTime(seconds) {
        const min = Math.floor(seconds / 60);
        const sec = Math.floor(seconds % 60);
        return min + ":" + (sec < 10 ? "0" : "") + sec;
    }

    return player;
}
</script>
