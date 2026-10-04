'use strict';
(() => {
    const index = document.querySelector('.developer-index');
    const links = [...document.querySelectorAll('.developer-nav-link')];
    const functionLinks = links.filter(link => link.dataset.function);
    const sections = [...document.querySelectorAll('.developer-section')];
    const filter = document.getElementById('function-filter');
    const empty = document.getElementById('function-empty');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let selectedLink = null;
    const revealSelection = () => {
        if (!index.open || !selectedLink || selectedLink.hidden) return;
        const indexBounds = index.getBoundingClientRect();
        const linkBounds = selectedLink.getBoundingClientRect();
        const top = indexBounds.top + index.clientTop + 8;
        const bottom = indexBounds.top + index.clientTop + index.clientHeight - 8;
        // Scroll only the index; scrollIntoView can also move the document.
        const offset = linkBounds.top < top ? linkBounds.top - top
            : linkBounds.bottom > bottom ? linkBounds.bottom - bottom : 0;
        if (offset !== 0) index.scrollTo({
            top: index.scrollTop + offset,
            behavior: reducedMotion.matches ? 'auto' : 'smooth',
        });
    };
    const selectLink = nextLink => {
        links.forEach(link => {
            if (link === nextLink) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
        });
        if (selectedLink !== nextLink) {
            selectedLink = nextLink;
            revealSelection();
        }
    };
    document.querySelector('.developer-index-tools').hidden = false;
    filter.addEventListener('input', () => {
        const query = filter.value.trim().toLocaleLowerCase();
        functionLinks.forEach(link => { link.hidden = !link.dataset.function.toLocaleLowerCase().includes(query); });
        empty.hidden = functionLinks.some(link => !link.hidden);
        revealSelection();
    });
    const mobile = window.matchMedia('(max-width: 700px)');
    index.open = !mobile.matches;
    mobile.addEventListener('change', event => { index.open = !event.matches; });
    index.addEventListener('toggle', revealSelection);
    window.addEventListener('resize', revealSelection);
    let scheduled = false;
    let syncPaused = false;
    let resumeTimer;
    const updateCurrent = () => {
        scheduled = false;
        if (syncPaused) return;
        let current = sections[0];
        for (const section of sections) {
            if (section.getBoundingClientRect().top <= 100) current = section;
            else break;
        }
        if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) current = sections.at(-1);
        selectLink(links.find(link => link.hash === '#' + current.id) ?? null);
    };
    const resumeSync = () => {
        if (!syncPaused) return;
        clearTimeout(resumeTimer);
        syncPaused = false;
        updateCurrent();
    };
    const waitForScrollEnd = () => {
        clearTimeout(resumeTimer);
        // Fallback for browsers without scrollend, including a click that needs no scrolling.
        resumeTimer = setTimeout(resumeSync, 180);
    };
    links.forEach(link => link.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        syncPaused = true;
        if (mobile.matches) index.open = false;
        selectLink(link);
        waitForScrollEnd();
        // Keep native anchor navigation, keyboard focus and history handling.
    }));
    document.addEventListener('scrollend', event => {
        if (event.target === document) resumeSync();
    });
    window.addEventListener('scroll', () => {
        if (syncPaused) { waitForScrollEnd(); return; }
        if (!scheduled) { scheduled = true; window.requestAnimationFrame(updateCurrent); }
    }, { passive: true });
    updateCurrent();
})();
