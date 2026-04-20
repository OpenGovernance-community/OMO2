<script src="https://player.vimeo.com/api/player.js"></script>
 
<script id="videoinit">
function initVideoPlayer() {
    const iframe = document.getElementById('vimeoPlayer');
    if (!iframe) return;

    const player = new Vimeo.Player(iframe);

    const playBtn = document.getElementById('playBtn');
    const progressvideoBar = document.querySelector('.progressvideo-bar');
    const progressvideo = document.querySelector('.progressvideo');
    const timeDisplay = document.getElementById('time');

    let duration = 0;

    player.getDuration().then(d => duration = d);

    playBtn.addEventListener('click', async () => {
        const paused = await player.getPaused();
        if (paused) {
            player.play();
            playBtn.textContent = "⏸";
        } else {
            player.pause();
            playBtn.textContent = "▶";
        }
    });

    player.on('timeupdate', data => {
        const percent = (data.seconds / duration) * 100;
        progressvideoBar.style.width = percent + "%";
        timeDisplay.textContent = formatTime(data.seconds);
    });

    progressvideo.addEventListener('click', e => {
        const rect = progressvideo.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        player.setCurrentTime(percent * duration);
    });

    function formatTime(seconds) {
        const min = Math.floor(seconds / 60);
        const sec = Math.floor(seconds % 60);
        return min + ":" + (sec < 10 ? "0" : "") + sec;
    }
}
</script>