<script src="https://player.vimeo.com/api/player.js"></script>

<script id="videoinit">
const LMS_VIDEO_VOLUME_STORAGE_KEY = 'omo-lms-video-volume';

function clampLmsVideoVolume(volumeValue) {
    if (volumeValue === null || typeof volumeValue === 'undefined' || volumeValue === '') {
        return 1;
    }

    const numericVolume = Number(volumeValue);
    if (!Number.isFinite(numericVolume)) {
        return 1;
    }

    return Math.max(0, Math.min(1, numericVolume));
}

function readStoredLmsVideoVolume() {
    try {
        return clampLmsVideoVolume(window.localStorage.getItem(LMS_VIDEO_VOLUME_STORAGE_KEY));
    } catch (error) {
        return 1;
    }
}

function storeLmsVideoVolume(volumeValue) {
    try {
        window.localStorage.setItem(
            LMS_VIDEO_VOLUME_STORAGE_KEY,
            String(clampLmsVideoVolume(volumeValue))
        );
    } catch (error) {
    }
}

window.lmsDestroyCurrentVideoPlayer = function(options) {
    if (typeof window.lmsCurrentVideoPlayerCleanup === 'function') {
        return window.lmsCurrentVideoPlayerCleanup(options);
    }

    return Promise.resolve();
};

function initVideoPlayer() {
    if (typeof window.lmsDestroyCurrentVideoPlayer === 'function') {
        window.lmsDestroyCurrentVideoPlayer({ unload: true, resetFrame: false }).catch(() => {});
    }

    const iframe = document.getElementById('vimeoPlayer');
    if (!iframe) return null;

    const videoPortal = document.querySelector('.video-portal');
    const playBtn = document.getElementById('playBtn');
    const volumeBtn = document.getElementById('volumeBtn');
    const volumeSlider = document.getElementById('volumeSlider');
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
    let isPaused = true;
    let lastNonZeroVolume = 1;
    let isCleaningUp = false;
    const initialStoredVolume = readStoredLmsVideoVolume();

    const updateVolumeUI = (volumeValue) => {
        const safeVolume = clampLmsVideoVolume(volumeValue);

        if (safeVolume > 0) {
            lastNonZeroVolume = safeVolume;
        }

        if (volumeSlider) {
            volumeSlider.value = String(Math.round(safeVolume * 100));
        }

        if (volumeBtn) {
            volumeBtn.textContent = safeVolume <= 0 ? "Muet" : "Son";
        }

        storeLmsVideoVolume(safeVolume);
    };

    player.getDuration()
        .then(d => {
            duration = d || 0;
        })
        .catch(() => {
            duration = 0;
        });

    player.setVolume(initialStoredVolume)
        .then(() => {
            updateVolumeUI(initialStoredVolume);
        })
        .catch(() => {
            player.getVolume()
                .then(volume => {
                    updateVolumeUI(volume);
                })
                .catch(() => {
                    updateVolumeUI(initialStoredVolume);
                });
        });

    player.getPaused()
        .then(paused => {
            isPaused = !!paused;
            playBtn.textContent = isPaused ? "Lire" : "Pause";
        })
        .catch(() => {
            isPaused = true;
            playBtn.textContent = "Lire";
        });

    playBtn.addEventListener('click', () => {
        if (isPaused) {
            const desiredVolume = volumeSlider
                ? Math.max(0, Math.min(1, Number(volumeSlider.value) / 100 || 0))
                : lastNonZeroVolume;

            if (desiredVolume > 0) {
                player.setVolume(desiredVolume).catch(() => {});
            }

            player.play()
                .then(() => {
                    if (desiredVolume > 0) {
                        player.setVolume(desiredVolume).catch(() => {});
                    }
                })
                .catch(error => {
                    console.warn('Lecture Vimeo indisponible.', error);
                });
            return;
        }

        player.pause().catch(error => {
            console.warn('Pause Vimeo indisponible.', error);
        });
    });

    if (volumeSlider) {
        volumeSlider.addEventListener('input', e => {
            const nextVolume = Math.max(0, Math.min(1, Number(e.target.value) / 100 || 0));
            player.setVolume(nextVolume)
                .then(() => {
                    updateVolumeUI(nextVolume);
                })
                .catch(() => {});
        });
    }

    if (volumeBtn) {
        volumeBtn.addEventListener('click', () => {
            player.getVolume()
                .then(currentVolume => {
                    const nextVolume = (Number(currentVolume) || 0) > 0 ? 0 : lastNonZeroVolume;
                    return player.setVolume(nextVolume).then(() => {
                        updateVolumeUI(nextVolume);
                    });
                })
                .catch(() => {});
        });
    }

    player.on('timeupdate', data => {
        const percent = duration > 0 ? (data.seconds / duration) * 100 : 0;
        progressvideoBar.style.width = percent + "%";
        timeDisplay.textContent = formatTime(data.seconds);
    });

    player.on('play', () => {
        isPaused = false;
        playBtn.textContent = "Pause";
    });

    player.on('pause', () => {
        isPaused = true;
        playBtn.textContent = "Lire";
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

    player.on('volumechange', data => {
        if (!data || typeof data.volume === 'undefined') {
            return;
        }

        updateVolumeUI(data.volume);
    });

    const cleanupPlayer = (options = {}) => {
        if (isCleaningUp) {
            return Promise.resolve();
        }

        isCleaningUp = true;

        const shouldUnload = !!options.unload;
        const shouldResetFrame = !!options.resetFrame;
        const cleanupTasks = [
            player.pause().catch(() => {})
        ];

        if (shouldUnload) {
            cleanupTasks.push(player.unload().catch(() => {}));
        }

        if (shouldResetFrame) {
            cleanupTasks.push(Promise.resolve().then(() => {
                iframe.setAttribute('src', 'about:blank');
            }));
        }

        return Promise.all(cleanupTasks).finally(() => {
            if (window.lmsCurrentVideoPlayer === player) {
                window.lmsCurrentVideoPlayer = null;
            }
            if (window.lmsCurrentVideoPlayerCleanup === cleanupPlayer) {
                window.lmsCurrentVideoPlayerCleanup = null;
            }
            isCleaningUp = false;
        });
    };

    window.lmsCurrentVideoPlayer = player;
    window.lmsCurrentVideoPlayerCleanup = cleanupPlayer;

    function formatTime(seconds) {
        const min = Math.floor(seconds / 60);
        const sec = Math.floor(seconds % 60);
        return min + ":" + (sec < 10 ? "0" : "") + sec;
    }

    return player;
}
</script>
