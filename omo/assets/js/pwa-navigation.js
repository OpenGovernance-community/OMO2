(function () {
    'use strict';

    const config = window.omoPwaNavigationConfig;
    if (!config) return;

    const standaloneQuery = typeof window.matchMedia === 'function'
        ? window.matchMedia('(display-mode: standalone)') : null;
    const storageKey = 'omo.pwa.home';
    let appHome = null;

    function isStandalone() {
        return Boolean((standaloneQuery && standaloneQuery.matches) || window.navigator.standalone === true);
    }

    function rememberAppHome() {
        if (!isStandalone()) {
            appHome = null;
            return;
        }
        if (appHome) return;

        // Session storage belongs to this window, unlike cookies/localStorage
        // shared by catalog and organization installations on the same origin.
        try {
            const saved = JSON.parse(window.sessionStorage.getItem(storageKey));
            if (saved && Number.isSafeInteger(saved.organizationId) && saved.organizationId >= 0) {
                const url = new URL(saved.homeUrl);
                if ((saved.organizationId === 0 && url.href === config.catalogUrl)
                    || (saved.organizationId > 0 && url.origin === window.location.origin
                    && /^\/omo\/(?:o\/[1-9]\d*\/?)?$/.test(url.pathname) && !url.search && !url.hash)) {
                    appHome = saved;
                }
            }
        } catch (error) { /* Storage can be unavailable in private sessions. */ }

        if (!appHome) {
            const marker = new URL(window.location.href).searchParams.get('pwa');
            // Older installations have no launch marker: use their first page.
            const organizationId = marker !== null && /^\d+$/.test(marker) && Number.isSafeInteger(Number(marker))
                ? Number(marker) : config.organizationId;
            appHome = {
                organizationId: organizationId,
                homeUrl: organizationId === 0 ? config.catalogUrl
                    : organizationId === config.organizationId ? config.homeUrl
                    : new URL('/omo/o/' + organizationId, window.location.origin).href
            };
            try { window.sessionStorage.setItem(storageKey, JSON.stringify(appHome)); } catch (error) {}
        }
    }

    function enforceAppHome() {
        rememberAppHome();
        if (appHome && appHome.organizationId > 0 && config.organizationId !== appHome.organizationId) {
            const url = new URL(appHome.homeUrl);
            url.searchParams.set('pwa', String(appHome.organizationId));
            // A deleted/unavailable organization must not cause a redirect loop.
            if (url.href !== window.location.href) window.location.replace(url.href);
            return false;
        }
        return true;
    }

    function isOrganizationHome() {
        const url = new URL(window.location.href);
        const home = new URL(config.homeUrl);
        const path = url.pathname.replace(/\/$/, '');
        const homePath = home.pathname.replace(/\/$/, '');
        const isHomePath = path === homePath
            || config.hostOrganizationId > 0 && path === '/omo/o/' + config.organizationId
            || homePath === '/omo' && path === '/omo/index.php';
        return url.origin === home.origin
            && isHomePath
            && url.hash.replace(/^#/, '').trim() === ''
            && !['cid', 'modal', 'popup'].some(function (key) { return url.searchParams.has(key); });
    }

    function updateBrand() {
        const navigation = (window.commonTopbarConfig || {}).brandNavigation;
        if (!navigation || !navigation.enabled || !enforceAppHome()) return;

        const logo = document.querySelector('[data-common-topbar-brand-link]');
        if (!logo) return;
        const catalogAllowed = !appHome || appHome.organizationId === 0;
        const showCatalog = catalogAllowed && isOrganizationHome();
        const label = showCatalog ? navigation.catalogLabel : navigation.homeLabel;
        logo.href = showCatalog ? config.catalogUrl : config.homeUrl;
        logo.title = label;
        logo.setAttribute('aria-label', label);
        const badge = logo.querySelector('[data-common-topbar-catalog-badge]');
        if (badge) badge.hidden = !showCatalog;
        const name = document.querySelector('.common-topbar__brand-link--name');
        if (name) name.href = config.homeUrl;
    }

    function getEntryOrganization(url) {
        const root = new URL(config.catalogUrl);
        if (url.protocol !== root.protocol || url.port !== root.port
            || (url.hostname !== root.hostname && !url.hostname.endsWith('.' + root.hostname))) return null;
        const match = url.pathname.match(/^\/omo(?:\/index\.php|(?:\/o\/(\d+))?(?:\/c\/\d+)?)?\/?$/);
        if (!match) return null;
        if (match[1]) return Number(match[1]);
        if (url.searchParams.has('oid')) return Number(url.searchParams.get('oid'));
        if (url.origin === window.location.origin) return config.hostOrganizationId;
        return url.origin === root.origin ? 0 : -1;
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[href]');
        if (!link || link.hasAttribute('download')) return;
        let url = new URL(link.href, window.location.href);
        rememberAppHome();

        if (appHome) {
            const targetOrganization = getEntryOrganization(url);
            if (targetOrganization !== null) {
                if (appHome.organizationId > 0
                    && (targetOrganization !== appHome.organizationId || url.origin !== window.location.origin)) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    return;
                }
                const card = link.closest('[data-organization-id]');
                if (appHome.organizationId === 0 && targetOrganization === -1 && card) {
                    const organizationId = Number(card.getAttribute('data-organization-id'));
                    if (Number.isSafeInteger(organizationId) && organizationId > 0) {
                        // Catalog installations stay in their own window/scope,
                        // including organizations normally opened on a subdomain.
                        url = new URL('/omo/o/' + organizationId + url.search + url.hash, config.catalogUrl);
                    }
                }
                // Carry the catalog origin across organization subdomains.
                url.searchParams.set('pwa', String(appHome.organizationId));
                link.href = url.href;
            }
        }

        const navigation = (window.commonTopbarConfig || {}).brandNavigation;
        if (!navigation || !navigation.enabled || event.defaultPrevented || event.button !== 0
            || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey
            || link.target && link.target !== '_self'
            || !link.matches('.common-topbar__brand-link')) return;

        const home = new URL(config.homeUrl);
        if (url.origin === home.origin && url.pathname === home.pathname
            && window.omoConfig && typeof window.navigate === 'function') {
            event.preventDefault();
            window.navigate(config.organizationId, null, null);
        }
    }, true);

    window.omoPwaNavigation = {isStandalone: isStandalone};
    ['hashchange', 'popstate', 'omo-route-change', 'omo-structure-route-sync', 'pageshow'].forEach(function (name) {
        window.addEventListener(name, function () {
            if (enforceAppHome()) updateBrand();
        });
    });
    if (standaloneQuery && typeof standaloneQuery.addEventListener === 'function') {
        standaloneQuery.addEventListener('change', function () {
            rememberAppHome();
            updateBrand();
        });
    }
    if (enforceAppHome()) updateBrand();
})();
