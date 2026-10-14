(function (window, document) {
    'use strict';
    const sidebar = document.getElementById('sidebar');
    const menu = document.getElementById('menu_sidebar');
    if (!sidebar || !menu) return;
    let primary, secondary, toggle, panel;
    let items = [];
    let frame = 0;

    function close(restoreFocus = false) {
        if (!panel || panel.hidden) return;
        if (panel.matches(':popover-open')) panel.hidePopover();
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        if (restoreFocus && !toggle.hidden) toggle.focus();
    }

    function position() {
        const rect = toggle.getBoundingClientRect();
        const size = panel.getBoundingClientRect();
        panel.style.left = Math.max(12, Math.min(rect.right + 8, window.innerWidth - size.width - 12)) + 'px';
        panel.style.top = Math.max(12, Math.min(rect.top, window.innerHeight - size.height - 12)) + 'px';
    }

    function open(last = false) {
        if (!panel || toggle.hidden) return;
        panel.hidden = false;
        // The top layer escapes sidebar clipping, including its mobile transform.
        panel.showPopover();
        toggle.setAttribute('aria-expanded', 'true');
        position();
        const entries = panel.querySelectorAll('.menu-item');
        const entry = last ? entries[entries.length - 1] : entries[0];
        if (entry) entry.focus();
    }

    function layout() {
        frame = 0;
        if (!primary || !primary.isConnected) return;
        const focused = document.activeElement;
        const hadMenuFocus = panel.contains(focused);
        close();
        // Restore the original order before measuring actual desktop/mobile row sizes.
        items.forEach(function (item) {
            primary.insertBefore(item, toggle);
            item.removeAttribute('role');
            item.removeAttribute('tabindex');
        });
        toggle.hidden = true;
        const available = Math.max(0, menu.clientHeight - secondary.getBoundingClientRect().height);
        const heights = items.map(item => item.getBoundingClientRect().height);
        if (heights.reduce((sum, height) => sum + height, 0) <= available) {
            if (hadMenuFocus) { focused.tabIndex = -1; focused.focus(); }
            return;
        }
        toggle.hidden = false;
        const budget = Math.max(0, available - toggle.getBoundingClientRect().height);
        let used = 0;
        let overflowing = false;
        items.forEach(function (item, index) {
            used += heights[index];
            overflowing = overflowing || used > budget;
            if (overflowing) {
                item.setAttribute('role', 'menuitem');
                item.tabIndex = -1;
                panel.appendChild(item);
            }
        });
        if (hadMenuFocus || panel.contains(focused)) toggle.focus();
    }

    function schedule() {
        if (!frame) frame = window.requestAnimationFrame(layout);
    }

    const sizes = new ResizeObserver(schedule);
    sizes.observe(menu);
    sizes.observe(sidebar);

    function mount() {
        const nextPrimary = menu.querySelector('.menu-primary');
        if (nextPrimary === primary) return;
        close();
        if (secondary) sizes.unobserve(secondary);
        primary = nextPrimary;
        secondary = menu.querySelector('.menu-secondary');
        toggle = menu.querySelector('.sidebar-overflow-toggle');
        panel = menu.querySelector('#sidebar-overflow-menu');
        if (!primary || !secondary || !toggle || !panel) return;
        items = Array.from(primary.children).filter(item => item !== toggle);
        sizes.observe(secondary);
        toggle.addEventListener('click', function () {
            if (panel.hidden) open();
            else close(true);
        });
        toggle.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                open(event.key === 'ArrowUp');
            }
        });
        panel.addEventListener('keydown', function (event) {
            const entries = Array.from(panel.querySelectorAll('.menu-item'));
            const index = entries.indexOf(document.activeElement);
            let next;
            if (event.key === 'ArrowDown') next = (index + 1) % entries.length;
            else if (event.key === 'ArrowUp') next = (index + entries.length - 1) % entries.length;
            else if (event.key === 'Home') next = 0;
            else if (event.key === 'End') next = entries.length - 1;
            else if ((event.key === 'Enter' || event.key === ' ') && index >= 0) {
                event.preventDefault();
                entries[index].click();
            }
            if (next !== undefined) { event.preventDefault(); entries[next].focus(); }
        });
        // Capture also catches attention badges, which stop click propagation.
        panel.addEventListener('click', function (event) {
            if (event.target.closest('.menu-item')) close(true);
        }, true);
        schedule();
    }

    new MutationObserver(mount).observe(menu, {childList: true});
    new MutationObserver(function () { close(); schedule(); }).observe(sidebar, {attributes: true, attributeFilter: ['class']});
    new MutationObserver(function () { close(); }).observe(document.body, {attributes: true, attributeFilter: ['class']});
    window.addEventListener('resize', schedule);
    window.addEventListener('omo-route-change', function () { close(); });
    document.addEventListener('pointerdown', function (event) {
        if (panel && !panel.contains(event.target) && !toggle.contains(event.target)) close();
    });
    document.addEventListener('focusin', function (event) {
        if (panel && !panel.contains(event.target) && event.target !== toggle) close();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && panel && !panel.hidden) { event.preventDefault(); close(true); }
    });
    mount();
})(window, document);
