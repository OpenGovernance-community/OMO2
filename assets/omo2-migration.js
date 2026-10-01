(() => {
    document.querySelectorAll('[data-omo-comparison]').forEach((comparison) => {
        const frame = comparison.querySelector('.omo2-migration-comparison__frame');
        const range = comparison.querySelector('.omo2-migration-comparison__range');
        const beforeImage = comparison.querySelector('[data-compare-before-image]');
        const afterImage = comparison.querySelector('[data-compare-after-image]');
        const screenButtons = [...comparison.querySelectorAll('[data-compare-screen]:not(:disabled)')];
        if (!frame || !range || !beforeImage || !afterImage) return;

        const update = (value) => {
            const position = Math.max(0, Math.min(100, Number(value)));
            range.value = String(Math.round(position));
            frame.style.setProperty('--compare-position', `${position}%`);
        };
        const updateFromPointer = (event) => {
            const bounds = frame.getBoundingClientRect();
            update((event.clientX - bounds.left) / bounds.width * 100);
        };

        let activePointer = null;
        frame.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) return;
            activePointer = event.pointerId;
            frame.setPointerCapture(event.pointerId);
            updateFromPointer(event);
        });
        frame.addEventListener('pointermove', (event) => {
            if (event.pointerId === activePointer) updateFromPointer(event);
        });
        const stopDrag = (event) => {
            if (event.pointerId === activePointer) activePointer = null;
        };
        frame.addEventListener('pointerup', stopDrag);
        frame.addEventListener('pointercancel', stopDrag);
        frame.addEventListener('lostpointercapture', stopDrag);
        range.addEventListener('input', () => update(range.value));
        update(range.value);

        const loadImage = (source) => new Promise((resolve, reject) => {
            const image = new Image();
            image.onload = resolve;
            image.onerror = reject;
            image.src = source;
        });
        let selectionToken = 0;
        screenButtons.forEach((button) => button.addEventListener('click', async () => {
            if (button.getAttribute('aria-pressed') === 'true') {
                ++selectionToken;
                comparison.removeAttribute('aria-busy');
                return;
            }
            const token = ++selectionToken;
            comparison.setAttribute('aria-busy', 'true');
            try {
                await Promise.all([loadImage(button.dataset.compareBefore), loadImage(button.dataset.compareAfter)]);
                if (token !== selectionToken) return;
                beforeImage.src = button.dataset.compareBefore;
                beforeImage.alt = button.dataset.compareAltBefore;
                afterImage.src = button.dataset.compareAfter;
                afterImage.alt = button.dataset.compareAltAfter;
                screenButtons.forEach((screenButton) => {
                    screenButton.setAttribute('aria-pressed', String(screenButton === button));
                });
            } catch {
                // Keep the current pair visible if either image cannot load.
            } finally {
                if (token === selectionToken) comparison.removeAttribute('aria-busy');
            }
        }));
    });
})();
