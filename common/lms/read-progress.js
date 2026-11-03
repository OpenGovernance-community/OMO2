window.commonLmsWatchMissionReadProgress = function (container, content, onChange) {
    if (!container || !content) return () => {};
    let readHeight = 0;
    let canMarkRead = false;
    let frame = null;
    let stopped = false;

    function check() {
        frame = null;
        if (!content.isConnected) { stop(); return; }
        const height = container.scrollHeight;
        const visible = container.clientHeight;
        if (visible > 0 && container.scrollTop + visible >= height - 2) {
            readHeight = Math.max(readHeight, height);
        }
        const next = visible > 0 && height <= readHeight + 2;
        if (next !== canMarkRead) {
            canMarkRead = next;
            onChange(next);
        }
    }

    function schedule() {
        if (!stopped && frame === null) frame = requestAnimationFrame(check);
    }

    const resizeObserver = new ResizeObserver(schedule);
    resizeObserver.observe(container);
    resizeObserver.observe(content);
    const removalObserver = new MutationObserver(() => {
        if (!content.isConnected) stop();
    });
    removalObserver.observe(container, {childList: true, subtree: true});
    container.addEventListener('scroll', schedule, {passive: true});
    content.addEventListener('load', schedule, true);
    window.addEventListener('resize', schedule);

    function stop() {
        if (stopped) return;
        stopped = true;
        if (frame !== null) cancelAnimationFrame(frame);
        resizeObserver.disconnect();
        removalObserver.disconnect();
        container.removeEventListener('scroll', schedule);
        content.removeEventListener('load', schedule, true);
        window.removeEventListener('resize', schedule);
    }
    schedule();
    return stop;
};
