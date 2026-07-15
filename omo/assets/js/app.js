const OMO_THEME_STORAGE_KEY = typeof SHARED_THEME_STORAGE_KEY === 'string'
    ? SHARED_THEME_STORAGE_KEY
    : 'omo-theme-preference';
const OMO_THEME_MEDIA_QUERY = typeof SHARED_THEME_MEDIA_QUERY === 'string'
    ? SHARED_THEME_MEDIA_QUERY
    : '(prefers-color-scheme: dark)';

function omoGetThemePreference() {
    if (typeof sharedGetThemePreference === 'function') {
        return sharedGetThemePreference(OMO_THEME_STORAGE_KEY);
    }

    return 'system';
}

function omoGetThemeMediaQuery() {
    if (typeof window.matchMedia !== 'function') {
        return null;
    }

    return window.matchMedia(OMO_THEME_MEDIA_QUERY);
}

function omoResolveTheme(preference) {
    if (typeof sharedResolveTheme === 'function') {
        return sharedResolveTheme(preference, OMO_THEME_MEDIA_QUERY);
    }

    if (preference === 'dark' || preference === 'light') {
        return preference;
    }

    const mediaQuery = omoGetThemeMediaQuery();

    return mediaQuery && mediaQuery.matches ? 'dark' : 'light';
}

function omoApplyTheme(preference, persistPreference = false) {
    const safePreference = (preference === 'light' || preference === 'dark' || preference === 'system')
        ? preference
        : 'system';
    let resolvedTheme = omoResolveTheme(safePreference);

    if (typeof sharedApplyDocumentTheme === 'function') {
        resolvedTheme = sharedApplyDocumentTheme({
            storageKey: OMO_THEME_STORAGE_KEY,
            preference: safePreference
        }).theme;
    } else {
        const root = document.documentElement;

        root.dataset.themePreference = safePreference;
        root.dataset.theme = resolvedTheme;
        root.style.colorScheme = resolvedTheme;
    }

    if (persistPreference) {
        try {
            localStorage.setItem(OMO_THEME_STORAGE_KEY, safePreference);
        } catch (error) {
        }

        if (typeof sharedApplyDocumentTheme === 'function') {
            resolvedTheme = sharedApplyDocumentTheme({
                storageKey: OMO_THEME_STORAGE_KEY,
                preference: safePreference
            }).theme;
        }
    }

    document.querySelectorAll('[data-omo-theme-select]').forEach(function (select) {
        select.value = safePreference;
    });

    document.querySelectorAll('[data-omo-theme-preference-input]').forEach(function (input) {
        input.checked = input.value === safePreference;
    });

    window.dispatchEvent(new CustomEvent('omo-theme-change', {
        detail: {
            preference: safePreference,
            theme: resolvedTheme
        }
    }));

    if (typeof window.sharedBroadcastThemeToChildFrames === 'function') {
        window.sharedBroadcastThemeToChildFrames({
            preference: safePreference
        });
    }
}

function omoEscapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function omoIsExecutableScriptTag(script) {
    if (!script || script.tagName !== 'SCRIPT') {
        return false;
    }

    const rawType = String(script.getAttribute('type') || '').trim().toLowerCase();

    if (rawType === '' || rawType === 'text/javascript' || rawType === 'application/javascript') {
        return true;
    }

    return rawType === 'module';
}

function omoGetUserProfile() {
    const profile = window.omoConfig && window.omoConfig.userProfile ? window.omoConfig.userProfile : {};
    const displayName = (profile.displayName || window.omoConfig.currentUserName || 'Profil').trim();
    const initial = (displayName.charAt(0) || 'P').toUpperCase();

    return `
        <div class="omo-profile-panel" data-omo-profile-panel>
            <section class="omo-profile-panel__section omo-profile-panel__section--media">
                <div class="omo-profile-card generic-section">
                    ${profile.photoUrl
                        ? `<img src="${omoEscapeHtml(profile.photoUrl)}" alt="${omoEscapeHtml(displayName)}" class="omo-profile-card__photo">`
                        : `<div class="omo-profile-card__placeholder" aria-hidden="true">${omoEscapeHtml(initial)}</div>`
                    }
                    <div class="omo-profile-card__identity">
                        <strong>${omoEscapeHtml(displayName)}</strong>
                        <span>${profile.email ? omoEscapeHtml(profile.email) : 'Photo à venir'}</span>
                    </div>
                    <div class="omo-theme-menu" data-omo-theme-menu>
                        <div class="omo-theme-menu__label">Apparence</div>
                        <div class="omo-theme-toggle" role="radiogroup" aria-label="Choix du thème">
                            <input type="radio" name="omoThemePreference" id="omoThemeSystem" value="system" data-omo-theme-preference-input>
                            <label for="omoThemeSystem" class="omo-theme-toggle__option">Auto</label>

                            <input type="radio" name="omoThemePreference" id="omoThemeLight" value="light" data-omo-theme-preference-input>
                            <label for="omoThemeLight" class="omo-theme-toggle__option">Clair</label>

                            <input type="radio" name="omoThemePreference" id="omoThemeDark" value="dark" data-omo-theme-preference-input>
                            <label for="omoThemeDark" class="omo-theme-toggle__option">Sombre</label>
                        </div>
                    </div>
                </div>
            </section>

            <section class="omo-profile-panel__section omo-profile-panel__section--details">
                <div class="omo-profile-details generic-section">
                    <div class="omo-profile-details__row">
                        <span class="omo-profile-details__label">Nom</span>
                        <span class="omo-profile-details__value">${omoEscapeHtml(displayName)}</span>
                    </div>
                    <div class="omo-profile-details__row">
                        <span class="omo-profile-details__label">E-mail</span>
                        <span class="omo-profile-details__value">${profile.email ? omoEscapeHtml(profile.email) : 'Non renseigné'}</span>
                    </div>
                    <div class="omo-profile-details__row">
                        <span class="omo-profile-details__label">Téléphone</span>
                        <span class="omo-profile-details__value omo-profile-details__value--muted">${profile.phone ? omoEscapeHtml(profile.phone) : 'Non renseigné'}</span>
                    </div>
                    <div class="omo-profile-details__row">
                        <span class="omo-profile-details__label">Identifiant</span>
                        <span class="omo-profile-details__value">${profile.username ? omoEscapeHtml(profile.username) : 'Non renseigné'}</span>
                    </div>
                </div>
            </section>

            <section class="omo-profile-panel__section omo-profile-panel__section--actions">
                <div class="omo-profile-actions generic-section">
                    <button type="button" class="common-topbar__menu-item omo-profile-actions__button" data-topbar-profile-edit>Modifier le profil</button>
                    <button type="button" class="common-topbar__menu-item common-topbar__menu-item--danger omo-profile-actions__button" data-topbar-logout>Se déconnecter</button>
                </div>
            </section>
        </div>
    `;
}

let omoPatreonWelcomePromptHandled = false;

function omoGetPatreonWelcomePromptCookieName() {
    const currentUserId = window.omoConfig && window.omoConfig.currentUserId
        ? Number(window.omoConfig.currentUserId)
        : 0;

    return 'omo_patreon_prompt_seen_' + String(currentUserId > 0 ? currentUserId : 'guest');
}

function omoGetTodayDateToken() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return year + '-' + month + '-' + day;
}

function omoReadCookie(name) {
    const cookiePrefix = encodeURIComponent(name) + '=';
    const cookies = document.cookie ? document.cookie.split(';') : [];

    for (let index = 0; index < cookies.length; index += 1) {
        const cookie = cookies[index].trim();

        if (cookie.indexOf(cookiePrefix) === 0) {
            return decodeURIComponent(cookie.slice(cookiePrefix.length));
        }
    }

    return '';
}

function omoMarkPatreonWelcomePromptSeenToday() {
    const tomorrow = new Date();
    tomorrow.setHours(24, 0, 0, 0);

    document.cookie = [
        encodeURIComponent(omoGetPatreonWelcomePromptCookieName()) + '=' + encodeURIComponent(omoGetTodayDateToken()),
        'expires=' + tomorrow.toUTCString(),
        'path=/',
        'SameSite=Lax'
    ].join('; ');
}

function omoHasSeenPatreonWelcomePromptToday() {
    return omoReadCookie(omoGetPatreonWelcomePromptCookieName()) === omoGetTodayDateToken();
}

function omoMaybeOpenPatreonWelcomeModal() {
    const promptConfig = window.omoConfig && window.omoConfig.patreonPrompt
        ? window.omoConfig.patreonPrompt
        : null;

    if (
        !promptConfig
        || promptConfig.shouldShow !== true
        || typeof window.commonTopbarOpenModal !== 'function'
        || omoPatreonWelcomePromptHandled
        || omoHasSeenPatreonWelcomePromptToday()
    ) {
        return false;
    }

    const routePopupState = typeof window.omoParsePopupHashState === 'function'
        ? window.omoParsePopupHashState()
        : null;
    const modal = document.getElementById('commonTopbarModal');

    if ((routePopupState && routePopupState.popupToken) || (modal && !modal.hidden)) {
        return false;
    }

    omoPatreonWelcomePromptHandled = true;
    omoMarkPatreonWelcomePromptSeenToday();
    window.commonTopbarOpenModal(
        promptConfig.title || 'Soutenir le projet',
        promptConfig.url || '/omo/api/patreon_welcome_popup.php',
        promptConfig.mode || 'fetch'
    );

    return true;
}

function omoEnsureProfilePanel() {
    const profileMenu = document.querySelector('[data-topbar-menu="profile"]');

    if (
        !profileMenu
        || profileMenu.querySelector('[data-omo-profile-panel]')
        || profileMenu.querySelector('[data-common-topbar-profile-panel]')
    ) {
        return;
    }

    profileMenu.innerHTML = omoGetUserProfile();
    omoApplyTheme(omoGetThemePreference());
}

function omoInitThemePreference() {
    omoApplyTheme(omoGetThemePreference());
    omoEnsureProfilePanel();

    const mediaQuery = omoGetThemeMediaQuery();

    if (mediaQuery) {
        const syncSystemTheme = function () {
            if (omoGetThemePreference() === 'system') {
                omoApplyTheme('system');
            }
        };

        if (typeof mediaQuery.addEventListener === 'function') {
            mediaQuery.addEventListener('change', syncSystemTheme);
        } else if (typeof mediaQuery.addListener === 'function') {
            mediaQuery.addListener(syncSystemTheme);
        }
    }
}

document.addEventListener('change', function (event) {
    const radio = event.target.closest('[data-omo-theme-preference-input]');

    if (radio) {
        omoApplyTheme(radio.value, true);
        return;
    }

    const select = event.target.closest('[data-omo-theme-select]');

    if (!select) {
        return;
    }

    omoApplyTheme(select.value, true);
});

$(document).ready(function () {
    omoInitThemePreference();
});

function omoGetLeftPanelContentSelector() {
    return document.getElementById('panel-left-context')
        ? '#panel-left-context'
        : '#panel-left';
}

function omoGetLeftPanelContentElement() {
    return $(omoGetLeftPanelContentSelector());
}

function omoSetLeftPanelHtml(html) {
    omoGetLeftPanelContentElement().html(html);
}

$(document).ready(function () {

    const leftPanel = $('#panel-left');
    const container = $('.content');
    const resizer = $('#resizer');
    const leftShell = $('#omoLeftPanelShell');
    const structurePanel = $('#panel-left-structure');
    const structureResizer = $('#panel-left-structure-resizer');
    let isResizing = false;
    let isStructureResizing = false;
    function applyLeftPanelStructureHeight(heightValue) {
        const numericHeight = Number(heightValue);

        if (!leftShell.length || !structurePanel.length || !Number.isFinite(numericHeight) || numericHeight <= 0) {
            return;
        }

        const shellHeight = leftShell.height() || leftPanel.height() || 0;
        const minHeight = 120;
        const maxHeight = shellHeight > 0 ? Math.max(minHeight, Math.floor(shellHeight * 0.72)) : numericHeight;
        const nextHeight = Math.max(minHeight, Math.min(maxHeight, Math.round(numericHeight)));

        leftShell.css('--omo-left-structure-height', nextHeight + 'px');
    }

    // Charger largeur sauvegardée
    let savedWidth = localStorage.getItem('panelLeftWidth');
    if (savedWidth) {
        leftPanel.css({
            width: savedWidth + 'px',
            flexBasis: savedWidth + 'px'
        });
    }

    const savedStructureHeight = localStorage.getItem('omoLeftStructureHeight');
    if (savedStructureHeight) {
        applyLeftPanelStructureHeight(savedStructureHeight);
    }

    resizer.on('mousedown', function (e) {
        if (e.button !== 0) return; // uniquement clic gauche

        isResizing = true;

        $('body').addClass('resizing');

        // Empêche sélection texte
        e.preventDefault();
    });

    structureResizer.on('mousedown', function (e) {
        if (e.button !== 0 || !leftShell.length || !structurePanel.length) {
            return;
        }

        isStructureResizing = true;
        $('body').addClass('resizing');
        e.preventDefault();
    });

    $(document).on('mousemove', function (e) {
        if (isResizing) {
            let containerOffset = container.offset().left;
            let newWidth = e.pageX - containerOffset;

            // limites
            const minWidth = 250;
            const maxWidth = container.width() * 0.7;

            if (newWidth < minWidth) newWidth = minWidth;
            if (newWidth > maxWidth) newWidth = maxWidth;

            leftPanel.css({
                width: newWidth + 'px',
                flexBasis: newWidth + 'px'
            });

            syncOpenDrawers();
        }

        if (isStructureResizing) {
            const shellOffset = leftShell.offset();
            const shellHeight = leftShell.height() || 0;

            if (!shellOffset || shellHeight <= 0) {
                return;
            }

            const pointerOffset = e.pageY - shellOffset.top;
            const newHeight = shellHeight - pointerOffset;

            applyLeftPanelStructureHeight(newHeight);
            window.dispatchEvent(new CustomEvent('omo-left-structure-resize'));
        }
    });

    function stopResizing() {
        if (isResizing) {
            isResizing = false;

            // sauvegarde
            let finalWidth = leftPanel.width();
            localStorage.setItem('panelLeftWidth', finalWidth);
            syncOpenDrawers();
        }

        if (isStructureResizing) {
            isStructureResizing = false;

            const finalHeight = structurePanel.outerHeight();
            if (finalHeight) {
                localStorage.setItem('omoLeftStructureHeight', finalHeight);
            }

            window.dispatchEvent(new CustomEvent('omo-left-structure-resize'));
        }

        $('body').removeClass('resizing');
    }

    $(document).on('mouseup', stopResizing);

    // 🔥 FIX IMPORTANT : si la souris sort de la fenêtre
    $(window).on('blur', stopResizing);
    $(window).on('resize', function () {
        if (structurePanel.length) {
            applyLeftPanelStructureHeight(structurePanel.outerHeight());
            window.dispatchEvent(new CustomEvent('omo-left-structure-resize'));
        }

        syncOpenDrawers();
    });

});

function getResolvedOrganizationId() {

    const oid = window.omoConfig && window.omoConfig.oid;

    if (oid === null || oid === undefined || oid === '') {
        return null;
    }

    const parsedOid = Number(oid);

    return Number.isNaN(parsedOid) ? null : parsedOid;
}

function getOmoRouteMode() {
    const routeMode = window.omoConfig && window.omoConfig.routeMode;

    return routeMode === 'path' ? 'path' : 'host';
}

function omoIsShareMode() {
    return Boolean(window.omoConfig && window.omoConfig.mode === 'share');
}

function omoGetShareToken() {
    const token = window.omoConfig && typeof window.omoConfig.shareToken === 'string'
        ? window.omoConfig.shareToken.trim()
        : '';

    return token !== '' ? token : null;
}

function omoGetTranslationLocale() {
    const locale = window.omoConfig && typeof window.omoConfig.translationLocale === 'string'
        ? window.omoConfig.translationLocale.trim().toLowerCase()
        : '';

    return /^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/.test(locale) ? locale : '';
}

function omoGetUrlResolutionBase() {
    if (typeof document !== 'undefined' && typeof document.baseURI === 'string') {
        const baseUri = document.baseURI.trim();
        if (baseUri !== '') {
            return baseUri;
        }
    }

    return window.location.href;
}

function omoResolveAppUrl(url) {
    if (typeof url !== 'string' || url.trim() === '') {
        return url;
    }

    const shareToken = omoGetShareToken();
    let resolvedUrl = url;
    let hashPart = '';
    const hashIndex = resolvedUrl.indexOf('#');

    if (hashIndex >= 0) {
        hashPart = resolvedUrl.slice(hashIndex);
        resolvedUrl = resolvedUrl.slice(0, hashIndex);
    }

    const translationLocale = omoGetTranslationLocale();

    try {
        const parsedUrl = new URL(resolvedUrl, omoGetUrlResolutionBase());
        const isAbsolute = /^[a-z][a-z0-9+\-.]*:/i.test(resolvedUrl);

        if (parsedUrl.origin !== window.location.origin) {
            return resolvedUrl + hashPart;
        }

        if (omoIsShareMode() && shareToken && !parsedUrl.searchParams.has('token')) {
            parsedUrl.searchParams.set('token', shareToken);
        }

        if (translationLocale !== '' && !parsedUrl.searchParams.has('lang')) {
            parsedUrl.searchParams.set('lang', translationLocale);
        }

        if (isAbsolute) {
            return parsedUrl.toString() + hashPart;
        }

        return parsedUrl.pathname + parsedUrl.search + parsedUrl.hash + hashPart;
    } catch (error) {
        if (/[?&]lang=/.test(resolvedUrl)) {
            return resolvedUrl + hashPart;
        }

        if (translationLocale === '') {
            return resolvedUrl + hashPart;
        }

        resolvedUrl += (resolvedUrl.indexOf('?') === -1 ? '?' : '&') + 'lang=' + encodeURIComponent(translationLocale);
        return resolvedUrl + hashPart;
    }
}

function getNormalizedOmoPath() {

    const path = window.location.pathname || '';

    if (path.length > 1) {
        return path.replace(/\/+$/, '');
    }

    return path;
}

function omoNormalizeRouteCid(cid = null) {
    const resolvedCid = Number(cid);
    if (!Number.isInteger(resolvedCid) || resolvedCid <= 0) {
        return null;
    }

    const rootHolonId = window.omoConfig && window.omoConfig.rootHolonId
        ? Number(window.omoConfig.rootHolonId)
        : 0;

    if (Number.isInteger(rootHolonId) && rootHolonId > 0 && resolvedCid === rootHolonId) {
        return null;
    }

    return resolvedCid;
}

function buildOmoUrl(oid, cid = null, hash = null, options = {}) {
    const absolute = options && options.absolute === true;
    const shareToken = omoGetShareToken();
    const normalizedCid = omoNormalizeRouteCid(cid);

    if (omoIsShareMode() && shareToken) {
        let url = `/omo/share.php?token=${encodeURIComponent(shareToken)}`;

        if (normalizedCid) {
            url += `&cid=${encodeURIComponent(normalizedCid)}`;
        }

        if (hash) {
            url += `#${hash}`;
        }

        if (!absolute) {
            return url;
        }

        return `${window.location.origin}${url}`;
    }

    const resolvedOid = Number(oid);
    const routeMode = getOmoRouteMode();
    let url = routeMode === 'path' && Number.isInteger(resolvedOid) && resolvedOid > 0
        ? `/omo/o/${resolvedOid}`
        : '/omo';

    if (normalizedCid) {
        url += `/c/${normalizedCid}`;
    } else {
        url += '/';
    }

    if (hash) {
        url += `#${hash}`;
    }

    if (!absolute) {
        return url;
    }

    return `${window.location.origin}${url}`;
}

function canonicalizeOmoRootPath() {
    if (omoIsShareMode()) {
        return;
    }

    const normalizedPath = getNormalizedOmoPath();
    const rootHolonId = window.omoConfig && window.omoConfig.rootHolonId
        ? Number(window.omoConfig.rootHolonId)
        : 0;
    const rootContextMatch = normalizedPath.match(/^\/omo(?:\/o\/(\d+))?\/c\/(\d+)$/);

    if (rootContextMatch) {
        const currentRootHolonId = Number(rootContextMatch[2]);
        if (Number.isInteger(rootHolonId) && rootHolonId > 0 && currentRootHolonId === rootHolonId) {
            const routeOid = rootContextMatch[1]
                ? Number(rootContextMatch[1])
                : getResolvedOrganizationId();
            const hash = String(window.location.hash || '').replace(/^#/, '') || null;
            const canonicalBaseUrl = buildOmoUrl(routeOid, null, null);
            const canonicalUrl = canonicalBaseUrl + (window.location.search || '') + (hash ? `#${hash}` : '');

            history.replaceState({}, '', canonicalUrl);
        }
        return;
    }

    if (normalizedPath !== '/omo') {
        return;
    }

    const canonicalUrl = `/omo/${window.location.search || ''}${window.location.hash || ''}`;

    history.replaceState({}, '', canonicalUrl);
}

function getSkeleton(type) {
    if (type === 'sidebar') {
        return `<div class="loading">
            <div class="skeleton block"></div>
            <div class="skeleton block"></div>
        </div>`;
    }

    if (type === 'panel') {
        return `<div class="loading">
            <div class="skeleton title"></div>
            <div class="skeleton text"></div>
            <div class="skeleton text"></div>
            <div class="skeleton block"></div>
        </div>`;
    }
}

function syncOmoPanelLayout() {}

function omoIsDecisionIndexUrl(url = '') {
    return /(?:^|\/)api\/decision\/index\.php(?:[?#]|$)/i.test(String(url || ''));
}

function omoShouldTraceDecisionLoad(target, url = '') {
    if (omoIsDecisionIndexUrl(url)) {
        return true;
    }

    if (typeof target === 'string') {
        return target.indexOf('drawer_decisions') !== -1
            || target.indexOf('omo-decisions-root') !== -1;
    }

    const element = target && target.jquery
        ? target.get(0)
        : target;

    if (!(element instanceof Element)) {
        return false;
    }

    return element.id === 'drawer_decisions'
        || Boolean(element.closest && element.closest('#drawer_decisions'));
}

function omoTraceDecisionLoad(stage, details = null) {
    if (!window.console || typeof window.console.log !== 'function') {
        return;
    }

    if (details && typeof details === 'object') {
        console.log('[OMO][Decisions][load]', stage, details);
        return;
    }

    console.log('[OMO][Decisions][load]', stage, details);
}

function omoNormalizeDrawerId(id = '') {
    const normalizedId = String(id || '').trim();

    if (normalizedId === 'drawer_decision') {
        return 'drawer_decisions';
    }

    return normalizedId;
}

function omoResolveDrawer(id = '') {
    const normalizedId = omoNormalizeDrawerId(id);
    let drawer = normalizedId !== ''
        ? $('#' + normalizedId)
        : $();

    if (!drawer.length && normalizedId === 'drawer_decisions') {
        drawer = $('#drawer_decision');
        if (drawer.length) {
            drawer.attr('id', normalizedId);
        }
    }

    return {
        id: normalizedId,
        drawer: drawer
    };
}

function loadContent(target, url, type = 'panel', onLoaded = null) {

    const $target = $(target);
    const previousRequest = $target.data('omoXhr');
    const requestId = `${Date.now()}_${Math.random().toString(36).slice(2)}`;
    const resolvedUrl = omoResolveAppUrl(url);
    const shouldTraceDecisionLoad = omoShouldTraceDecisionLoad($target, resolvedUrl);

    if (shouldTraceDecisionLoad) {
        omoTraceDecisionLoad('loadContent:start', {
            requestId: requestId,
            target: $target.get(0) && $target.get(0).id ? $target.get(0).id : '',
            resolvedUrl: resolvedUrl,
            type: type
        });
    }

    if (previousRequest && previousRequest.readyState !== 4) {
        previousRequest.abort();
    }

    $target.data('omoRequestId', requestId);
    $target.html(getSkeleton(type));

    const xhr = $.ajax({
        url: resolvedUrl,
        method: 'GET',
        cache: false,

        success: function (data) {
            if ($target.data('omoRequestId') !== requestId) {
                return;
            }

            const temp = document.createElement('div');
            temp.innerHTML = String(data || '');
            const scriptSource = temp.cloneNode(true);
            const containsDecisionRoot = !!temp.querySelector('#omo-decisions-root');
            const executableScriptCount = Array.from(scriptSource.querySelectorAll('script')).filter(function (script) {
                return omoIsExecutableScriptTag(script);
            }).length;

            if (shouldTraceDecisionLoad || containsDecisionRoot) {
                omoTraceDecisionLoad('loadContent:success', {
                    requestId: requestId,
                    resolvedUrl: resolvedUrl,
                    htmlLength: String(data || '').length,
                    containsDecisionRoot: containsDecisionRoot,
                    executableScriptCount: executableScriptCount
                });
            }

            Array.from(temp.querySelectorAll('script')).forEach(function (script) {
                if (omoIsExecutableScriptTag(script)) {
                    script.remove();
                }
            });

            $target.html(temp.innerHTML);
            omoExecuteFetchedScripts(scriptSource);

            if (shouldTraceDecisionLoad || containsDecisionRoot) {
                omoTraceDecisionLoad('loadContent:afterScriptExecution', {
                    requestId: requestId,
                    resolvedUrl: resolvedUrl,
                    targetChildCount: $target.children().length
                });
            }

            if (typeof onLoaded === 'function') {
                onLoaded();
            }

        },

        error: function (xhr, textStatus, errorThrown) {
            if ($target.data('omoRequestId') !== requestId) {
                return;
            }

            if (shouldTraceDecisionLoad) {
                omoTraceDecisionLoad('loadContent:error', {
                    requestId: requestId,
                    resolvedUrl: resolvedUrl,
                    status: xhr && typeof xhr.status !== 'undefined' ? xhr.status : null,
                    textStatus: textStatus || '',
                    error: errorThrown || ''
                });
            }

            $target.html('<div class="error">Erreur de chargement</div>');

        },

        complete: function () {
            if ($target.data('omoRequestId') === requestId) {
                $target.removeData('omoXhr');
            }
        }
    });

    $target.data('omoXhr', xhr);
}

function omoResetMainRightPanel() {
    $('#panel-right').data('omo-main-right-loaded-url', '');
    $('#panel-right').empty();
}

function omoInvalidateMainRightPanel() {
    $('#panel-right').data('omo-main-right-loaded-url', '');
}

let omoMainRightPanelDesiredUrl = '';

function omoBuildMainRightPanelUrl(oid, cid = null) {
    const resolvedOrganizationId = Number(oid);
    if (!Number.isInteger(resolvedOrganizationId) || resolvedOrganizationId <= 0) {
        return '';
    }

    let url = omoIsShareMode()
        ? `api/getStructure.php?oid=${resolvedOrganizationId}`
        : `api/personal_space.php?oid=${resolvedOrganizationId}`;

    const resolvedHolonId = Number(cid);
    if (!omoIsShareMode() && Number.isInteger(resolvedHolonId) && resolvedHolonId > 0) {
        url += `&cid=${resolvedHolonId}`;
    }

    return url;
}

function omoGetMainRightPanelLoadedUrl() {
    return String($('#panel-right').data('omo-main-right-loaded-url') || '').trim();
}

function omoHasOpenDrawers() {
    return $('.drawer.open').length > 0;
}

function omoRefreshMainRightPanel(oid = null, cid = null, options = {}) {
    const route = typeof parseUrl === 'function' ? parseUrl() : { oid: null, cid: null };
    const resolvedOrganizationId = oid === null ? Number(route.oid) : Number(oid);
    const resolvedHolonId = cid === null || cid === undefined ? Number(route.cid || 0) : Number(cid);
    const rightUrl = omoBuildMainRightPanelUrl(resolvedOrganizationId, resolvedHolonId);
    const routeWillOpenDrawer = options.routeWillOpenDrawer === true;
    const forceRefresh = options.force === true;
    const $panelRight = $('#panel-right');

    omoMainRightPanelDesiredUrl = rightUrl;

    if (rightUrl === '') {
        omoResetMainRightPanel();
        return false;
    }

    if (routeWillOpenDrawer || omoHasOpenDrawers()) {
        return false;
    }

    const hasLoadedContent = $panelRight.children().length > 0;
    const loadedUrl = omoGetMainRightPanelLoadedUrl();
    if (!forceRefresh && hasLoadedContent && loadedUrl === rightUrl) {
        return true;
    }

    $panelRight.data('omo-main-right-loaded-url', rightUrl);
    loadContent('#panel-right', rightUrl, 'panel', function () {
        if (omoMainRightPanelDesiredUrl === rightUrl) {
            $('#panel-right').data('omo-main-right-loaded-url', rightUrl);
        }
    });

    return true;
}

function omoEnsureMainRightPanelCurrent(oid = null, cid = null, options = {}) {
    const route = typeof parseUrl === 'function' ? parseUrl() : { oid: null, cid: null };
    const resolvedOrganizationId = oid === null ? Number(route.oid) : Number(oid);
    const resolvedHolonId = cid === null || cid === undefined ? Number(route.cid || 0) : Number(cid);
    const rightUrl = omoBuildMainRightPanelUrl(resolvedOrganizationId, resolvedHolonId);
    const $panelRight = $('#panel-right');
    const loadedUrl = omoGetMainRightPanelLoadedUrl();
    const hasLoadedContent = $panelRight.children().length > 0;

    omoMainRightPanelDesiredUrl = rightUrl;

    if (rightUrl === '') {
        if (loadedUrl !== '' || hasLoadedContent) {
            omoResetMainRightPanel();
        }
        return true;
    }

    if (options.routeWillOpenDrawer === true || omoHasOpenDrawers()) {
        return false;
    }

    if (options.force === true || loadedUrl !== rightUrl || !hasLoadedContent) {
        return omoRefreshMainRightPanel(resolvedOrganizationId, resolvedHolonId, {
            force: options.force === true
        });
    }

    return true;
}

$(document).ready(function () {

    const sidebar = $('#sidebar');

    // Charger état sauvegardé
    let isExpanded = localStorage.getItem('sidebarExpanded') === 'true';

    if (isExpanded) {
        sidebar.addClass('expanded');
    }

    $('#sidebar-toggle').on('click', function () {
        sidebar.toggleClass('expanded');

        let state = sidebar.hasClass('expanded');
        localStorage.setItem('sidebarExpanded', state);
    });

});

let omoLastNonMenuView = 'left';
const OMO_APP_VIEWS = ['menu', 'left', 'right'];

function omoSetAppView(view, options = {}) {
    const resolvedView = view === 'menu' || view === 'left' || view === 'right'
        ? view
        : 'left';
    const allowToggleMenu = options.allowToggleMenu === true;
    const body = $('body');
    const isAlreadyActive = body.hasClass('view-' + resolvedView);

    if (allowToggleMenu && resolvedView === 'menu' && isAlreadyActive) {
        const fallbackView = omoLastNonMenuView === 'right' ? 'right' : 'left';
        body
            .removeClass('view-menu view-left view-right')
            .addClass('view-' + fallbackView);
        return fallbackView;
    }

    body
        .removeClass('view-menu view-left view-right')
        .addClass('view-' + resolvedView);

    if (resolvedView !== 'menu') {
        omoLastNonMenuView = resolvedView;
    }

    return resolvedView;
}

function omoGetCurrentAppView() {
    const body = document.body;

    if (!body) {
        return 'left';
    }

    if (body.classList.contains('view-menu')) {
        return 'menu';
    }

    if (body.classList.contains('view-right')) {
        return 'right';
    }

    return 'left';
}

function omoActivateAppViewButton(view) {
    const button = document.querySelector('#omo-mobile-nav [data-view="' + view + '"]');

    if (button instanceof HTMLButtonElement) {
        button.click();
        return true;
    }

    omoSetAppView(view, { allowToggleMenu: true });
    return true;
}

function omoActivateAdjacentAppView(direction) {
    const currentView = omoGetCurrentAppView();
    const currentIndex = OMO_APP_VIEWS.indexOf(currentView);

    if (currentIndex === -1) {
        return false;
    }

    const targetIndex = direction === 'left'
        ? currentIndex + 1
        : currentIndex - 1;

    if (targetIndex < 0 || targetIndex >= OMO_APP_VIEWS.length) {
        return false;
    }

    return omoActivateAppViewButton(OMO_APP_VIEWS[targetIndex]);
}

function omoBindMobileSwipeNavigation() {
    const swipeAreas = [
        document.getElementById('sidebar'),
        document.querySelector('.content'),
        document.getElementById('omo-mobile-nav')
    ].filter(function (element) {
        return element instanceof HTMLElement;
    });

    if (!swipeAreas.length) {
        return;
    }

    const mediaQuery = window.matchMedia('(max-width: 768px)');
    const swipeState = {
        active: false,
        startX: 0,
        startY: 0,
        lastX: 0,
        lastY: 0
    };

    function getTouchPoint(event) {
        if (event.changedTouches && event.changedTouches.length) {
            return event.changedTouches[0];
        }

        if (event.touches && event.touches.length) {
            return event.touches[0];
        }

        return null;
    }

    function resetSwipeState() {
        swipeState.active = false;
    }

    function handleTouchStart(event) {
        const touchPoint = getTouchPoint(event);

        if (!mediaQuery.matches || !touchPoint || (event.touches && event.touches.length !== 1)) {
            resetSwipeState();
            return;
        }

        swipeState.active = true;
        swipeState.startX = touchPoint.clientX;
        swipeState.startY = touchPoint.clientY;
        swipeState.lastX = touchPoint.clientX;
        swipeState.lastY = touchPoint.clientY;
    }

    function handleTouchMove(event) {
        const touchPoint = getTouchPoint(event);

        if (!swipeState.active || !touchPoint) {
            return;
        }

        swipeState.lastX = touchPoint.clientX;
        swipeState.lastY = touchPoint.clientY;
    }

    function handleTouchEnd(event) {
        const touchPoint = getTouchPoint(event);

        if (!swipeState.active) {
            return;
        }

        if (touchPoint) {
            swipeState.lastX = touchPoint.clientX;
            swipeState.lastY = touchPoint.clientY;
        }

        const deltaX = swipeState.lastX - swipeState.startX;
        const deltaY = swipeState.lastY - swipeState.startY;
        resetSwipeState();

        if (Math.abs(deltaX) < 60) {
            return;
        }

        if (Math.abs(deltaX) <= Math.abs(deltaY) * 1.2) {
            return;
        }

        omoActivateAdjacentAppView(deltaX < 0 ? 'left' : 'right');
    }

    swipeAreas.forEach(function (element) {
        element.addEventListener('touchstart', handleTouchStart, { passive: true });
        element.addEventListener('touchmove', handleTouchMove, { passive: true });
        element.addEventListener('touchend', handleTouchEnd, { passive: true });
        element.addEventListener('touchcancel', resetSwipeState, { passive: true });
    });
}

function openDrawer(id, url) {

    const drawerReference = omoResolveDrawer(id);
    const drawerId = drawerReference.id;
    let drawer = drawerReference.drawer;
    const resolvedUrl = omoResolveAppUrl(url);
    const currentUrl = drawer.length ? String(drawer.data('omo-drawer-url') || '') : '';
    const canReuseCachedDrawer = omoCanReuseCachedPanelDrawer(drawer, resolvedUrl, currentUrl);
    const shouldReloadContent = !drawer.length || (!canReuseCachedDrawer && currentUrl !== resolvedUrl);
    const hasLoadedContent = drawer.length
        ? drawer.find('.drawer-content').children().length > 0
        : false;
    const isReopeningCachedStructureDrawer = drawerId === 'drawer_structure'
        && drawer.length
        && !drawer.hasClass('open')
        && !shouldReloadContent
        && hasLoadedContent;

    clearDrawerRemoval(drawer);

    if (drawerId === 'drawer_decisions' || omoIsDecisionIndexUrl(resolvedUrl) || omoIsDecisionIndexUrl(currentUrl)) {
        omoTraceDecisionLoad('openDrawer', {
            requestedDrawerId: id,
            drawerId: drawerId,
            resolvedUrl: resolvedUrl,
            currentUrl: currentUrl,
            drawerExists: drawer.length > 0,
            drawerIsOpen: drawer.length ? drawer.hasClass('open') : false,
            hasLoadedContent: hasLoadedContent,
            canReuseCachedDrawer: canReuseCachedDrawer,
            shouldReloadContent: shouldReloadContent
        });
    }

    // 👉 si déjà ouvert → on ferme tout et on stop
    if (drawer.length && drawer.hasClass('open') && !shouldReloadContent) {
        closeAllDrawers();
        return;
    }

    // 👉 fermer les autres
    closeAllDrawers();

    // 👉 créer si inexistant
    if (drawer.length === 0) {

        drawer = $(`
            <div class="drawer" id="${drawerId}">
                <div class="drawer-content"></div>
            </div>
        `);

        $('.content').append(drawer);
    }

    drawer.data('omo-drawer-url', resolvedUrl);
    if (shouldReloadContent || !hasLoadedContent) {
        loadContent(drawer.find('.drawer-content'), resolvedUrl, 'panel');
    }

    updateDrawerPosition(drawer);

    requestAnimationFrame(() => {
        if (isReopeningCachedStructureDrawer && typeof window.omoStructureHandleDrawerOpen === 'function') {
            const route = typeof parseUrl === 'function' ? parseUrl() : { cid: null };

            window.omoStructureHandleDrawerOpen({
                cid: route && route.cid ? Number(route.cid) : null
            });
        }

        requestAnimationFrame(() => {
            drawer.addClass('open');

            if (drawerId === 'drawer_decisions' || omoIsDecisionIndexUrl(resolvedUrl)) {
                requestAnimationFrame(() => {
                    const node = drawer.get(0);
                    const contentNode = drawer.find('.drawer-content').get(0);
                    const decisionRoot = contentNode instanceof Element
                        ? contentNode.querySelector('#omo-decisions-root')
                        : null;
                    const decisionList = decisionRoot instanceof Element
                        ? decisionRoot.querySelector('[data-omo-decisions-list]')
                        : null;
                    const decisionState = decisionRoot instanceof Element
                        ? decisionRoot.querySelector('[data-omo-decisions-state]')
                        : null;

                    omoTraceDecisionLoad('openDrawer:afterOpen', {
                        requestedDrawerId: id,
                        drawerId: drawerId,
                        drawerIsOpen: drawer.hasClass('open'),
                        drawerWidth: node ? Math.round(node.getBoundingClientRect().width) : null,
                        drawerChildCount: contentNode ? contentNode.childElementCount : null,
                        hasDecisionRoot: !!decisionRoot,
                        listHidden: decisionList ? decisionList.hidden : null,
                        stateHidden: decisionState ? decisionState.hidden : null
                    });
                });
            }
        });
    });
}

function refreshDrawer(id, url) {
    const drawerReference = omoResolveDrawer(id);
    const drawer = drawerReference.drawer;
    const resolvedUrl = omoResolveAppUrl(url);

    if (!drawer.length) {
        return false;
    }

    drawer.data('omo-drawer-url', resolvedUrl);
    updateDrawerPosition(drawer);
    loadContent(drawer.find('.drawer-content'), resolvedUrl, 'panel');

    return true;
}

function omoIsMobileLayout() {
    return typeof window.matchMedia === 'function'
        ? window.matchMedia('(max-width: 768px)').matches
        : window.innerWidth <= 768;
}

function omoGetCompactSidebarWidth() {
    const cssValue = getComputedStyle(document.documentElement).getPropertyValue('--sidebar-width').trim();
    const parsedWidth = Number.parseFloat(cssValue);

    return Number.isFinite(parsedWidth) && parsedWidth > 0 ? parsedWidth : 48;
}

function omoGetDrawerResizerGap() {
    const cssValue = getComputedStyle(document.documentElement).getPropertyValue('--omo-drawer-resizer-gap').trim();
    const parsedGap = Number.parseFloat(cssValue);
    const fallbackGap = ($('#resizer').outerWidth() || 0) + 4;

    if (Number.isFinite(parsedGap) && parsedGap >= 0) {
        return parsedGap;
    }

    return fallbackGap;
}

function updateDrawerPosition(drawer) {
    if (!drawer || !drawer.length) {
        return;
    }

    if (omoIsMobileLayout()) {
        const compactSidebarWidth = omoGetCompactSidebarWidth();
        drawer.css({
            left: compactSidebarWidth + 'px',
            width: 'calc(100% - ' + compactSidebarWidth + 'px)'
        });
        return;
    }

    const leftWidth = $('#panel-left').outerWidth() || 0;
    const drawerLeft = leftWidth + omoGetDrawerResizerGap();

    drawer.css({
        left: drawerLeft + 'px',
        width: 'calc(100% - ' + drawerLeft + 'px)'
    });
}

function syncOpenDrawers() {
    $('.drawer').each(function () {
        updateDrawerPosition($(this));
    });

    updateExternalPanelDrawerPosition();
}

function clearDrawerRemoval(drawer) {
    if (!drawer || !drawer.length) {
        return;
    }

    const timeoutId = Number(drawer.data('omo-remove-timeout-id') || 0);

    if (timeoutId > 0) {
        window.clearTimeout(timeoutId);
    }

    drawer.off('transitionend.omoDrawerRemove');
    drawer.removeData('omo-remove-timeout-id');
    drawer.removeData('omo-remove-after-close');
}

function removeDrawerAfterTransition(drawer) {
    if (!drawer || !drawer.length) {
        return;
    }

    const node = drawer.get(0);

    clearDrawerRemoval(drawer);
    drawer.data('omo-remove-after-close', '1');

    const finalizeRemoval = function () {
        clearDrawerRemoval(drawer);

        if (!node || !node.isConnected || drawer.hasClass('open')) {
            return;
        }

        drawer.remove();
    };

    drawer.on('transitionend.omoDrawerRemove', function (event) {
        const originalEvent = event.originalEvent;

        if (event.target !== node) {
            return;
        }

        if (originalEvent && originalEvent.propertyName && originalEvent.propertyName !== 'transform') {
            return;
        }

        finalizeRemoval();
    });

    drawer.data('omo-remove-timeout-id', window.setTimeout(finalizeRemoval, 300));
}

function closeDrawer(id, removeAfterClose = false) {
    const drawer = omoResolveDrawer(id).drawer;

    if (!drawer.length) {
        return;
    }

    drawer.removeClass('open');

    if (removeAfterClose) {
        removeDrawerAfterTransition(drawer);
    }
}

function closeAllDrawers(removeAfterClose = false) {
    $('.drawer.open').each(function () {
        const drawer = $(this);

        drawer.removeClass('open');

        if (removeAfterClose) {
            removeDrawerAfterTransition(drawer);
        }
    });
}

function resetDrawers(activeDrawerId = null) {
    const normalizedActiveDrawerId = activeDrawerId ? omoNormalizeDrawerId(activeDrawerId) : null;
    $('.drawer').each(function () {
        const drawer = $(this);
        const drawerId = String(drawer.attr('id') || '');

        if (normalizedActiveDrawerId && drawerId === normalizedActiveDrawerId) {
            return;
        }

        if (drawerId === 'drawer_structure') {
            return;
        }

        if (drawer.hasClass('open')) {
            drawer.removeClass('open');
            removeDrawerAfterTransition(drawer);
            return;
        }

        if (drawer.data('omo-remove-after-close')) {
            return;
        }

        drawer.remove();
    });
}

function getSidebarMenuItem(hash = null) {
    if (!hash) {
        const structureItem = $('#menu_sidebar .menu-item[data-hash="structure"]').first();
        if (structureItem.length) {
            return structureItem;
        }

        return $('#menu_sidebar .menu-item[data-navigation-mode="panel"]').first();
    }

    return $(`#menu_sidebar .menu-item[data-hash="${hash}"]`).first();
}

function omoParseDecisionRouteToken(routeToken = null) {
    const normalizedRouteToken = omoNormalizeHashToken(routeToken);
    if (!normalizedRouteToken) {
        return null;
    }

    const decisionModeMatch = normalizedRouteToken.match(/^decision-([vgp])(\d+)$/i);
    if (decisionModeMatch) {
        const decisionModeKey = String(decisionModeMatch[1] || '').toLowerCase();
        const decisionId = Number(decisionModeMatch[2]);
        if (!Number.isInteger(decisionId) || decisionId <= 0) {
            return null;
        }

        return {
            decisionId: decisionId,
            mode: decisionModeKey === 'v'
                ? 'view'
                : (decisionModeKey === 'g' ? 'manage' : 'participate')
        };
    }

    const decisionMatch = normalizedRouteToken.match(/^decision-(?:d)?(\d+)$/i);
    if (!decisionMatch) {
        return null;
    }

    const decisionId = Number(decisionMatch[1]);
    if (!Number.isInteger(decisionId) || decisionId <= 0) {
        return null;
    }

    return {
        decisionId: decisionId,
        mode: 'default'
    };
}

function omoParseCalendarEventRouteToken(routeToken = null) {
    const normalizedRouteToken = omoNormalizeHashToken(routeToken);
    if (!normalizedRouteToken) {
        return null;
    }

    const eventMatch = normalizedRouteToken.match(/^(?:calendar-e|calendar-event-)(\d+)$/i);
    if (!eventMatch) {
        return null;
    }

    const eventId = Number(eventMatch[1]);
    if (!Number.isInteger(eventId) || eventId <= 0) {
        return null;
    }

    return {
        eventId: eventId
    };
}

function omoParseDocumentRouteToken(routeToken = null) {
    const normalizedRouteToken = omoNormalizeHashToken(routeToken);
    if (!normalizedRouteToken) {
        return null;
    }

    const documentEditMatch = normalizedRouteToken.match(/^(?:documents|document)-de(\d+)$/i);
    if (documentEditMatch) {
        const documentId = Number(documentEditMatch[1]);
        if (!Number.isInteger(documentId) || documentId <= 0) {
            return null;
        }

        return {
            documentId: documentId,
            mode: 'edit'
        };
    }

    const documentMatch = normalizedRouteToken.match(/^(?:documents|document)-(?:d)?(\d+)$/i);
    if (!documentMatch) {
        return null;
    }

    const documentId = Number(documentMatch[1]);
    if (!Number.isInteger(documentId) || documentId <= 0) {
        return null;
    }

    return {
        documentId: documentId,
        mode: 'detail'
    };
}

function omoBuildDecisionRouteToken(decisionId, mode = 'default') {
    const resolvedDecisionId = Number(decisionId);
    if (!Number.isInteger(resolvedDecisionId) || resolvedDecisionId <= 0) {
        return null;
    }

    const normalizedMode = String(mode || 'default').trim().toLowerCase();
    if (normalizedMode === 'view') {
        return `decision-v${resolvedDecisionId}`;
    }

    if (normalizedMode === 'manage') {
        return `decision-g${resolvedDecisionId}`;
    }

    if (normalizedMode === 'participate') {
        return `decision-p${resolvedDecisionId}`;
    }

    return `decision-d${resolvedDecisionId}`;
}

function omoBuildCalendarEventRouteToken(eventId) {
    const resolvedEventId = Number(eventId);
    if (!Number.isInteger(resolvedEventId) || resolvedEventId <= 0) {
        return null;
    }

    return `calendar-e${resolvedEventId}`;
}

function omoBuildDocumentRouteToken(documentId, mode = 'detail') {
    const resolvedDocumentId = Number(documentId);
    if (!Number.isInteger(resolvedDocumentId) || resolvedDocumentId <= 0) {
        return null;
    }

    const normalizedMode = String(mode || 'detail').trim().toLowerCase();
    if (normalizedMode === 'edit') {
        return `documents-de${resolvedDocumentId}`;
    }

    return `documents-d${resolvedDocumentId}`;
}

function omoGetMenuHashForRouteToken(routeToken = null) {
    const normalizedRouteToken = omoNormalizeHashToken(routeToken);
    if (!normalizedRouteToken) {
        return null;
    }

    if (omoParseDecisionRouteToken(normalizedRouteToken)) {
        return 'decision';
    }

    if (omoParseCalendarEventRouteToken(normalizedRouteToken)) {
        return 'calendar';
    }

    if (omoParseDocumentRouteToken(normalizedRouteToken)) {
        return 'documents';
    }

    return normalizedRouteToken;
}

let omoPendingDrawerRouteOptions = null;

function omoNormalizeDrawerForcedScope(scopeValue) {
    const normalizedScope = String(scopeValue || '').trim().toLowerCase();
    return normalizedScope === 'contextual' || normalizedScope === 'descendants' || normalizedScope === 'global'
        ? normalizedScope
        : '';
}

function omoSetPendingDrawerRouteOptions(routeToken, options = {}) {
    const normalizedRouteToken = omoNormalizeHashToken(routeToken);
    const forcedScope = omoNormalizeDrawerForcedScope(options.forcedScope);

    if (!normalizedRouteToken || forcedScope === '') {
        omoPendingDrawerRouteOptions = null;
        return;
    }

    omoPendingDrawerRouteOptions = {
        routeToken: normalizedRouteToken,
        forcedScope: forcedScope
    };
}

function omoResolveDrawerRouteOptions(routeToken = null, options = {}) {
    const normalizedRouteToken = omoNormalizeHashToken(routeToken);
    const explicitForcedScope = omoNormalizeDrawerForcedScope(options && options.forcedScope);
    if (explicitForcedScope !== '') {
        return {
            forcedScope: explicitForcedScope
        };
    }

    if (
        omoPendingDrawerRouteOptions
        && normalizedRouteToken
        && omoPendingDrawerRouteOptions.routeToken === normalizedRouteToken
    ) {
        return {
            forcedScope: omoPendingDrawerRouteOptions.forcedScope
        };
    }

    return {};
}

function omoClearPendingDrawerRouteOptions(routeToken = null) {
    const normalizedRouteToken = omoNormalizeHashToken(routeToken);
    if (!omoPendingDrawerRouteOptions) {
        return;
    }

    if (!normalizedRouteToken || omoPendingDrawerRouteOptions.routeToken === normalizedRouteToken) {
        omoPendingDrawerRouteOptions = null;
    }
}

function omoResolveSpecialDrawerRoute(routeToken, oid = null, cid = null, options = {}) {
    const normalizedRouteToken = omoNormalizeHashToken(routeToken);

    if (!normalizedRouteToken) {
        return null;
    }

    if (normalizedRouteToken === 'structure') {
        let url = 'api/getStructure.php?drawer=1';
        if (Number.isInteger(Number(oid)) && Number(oid) > 0) {
            url += '&oid=' + encodeURIComponent(Number(oid));
        }

        return {
            drawer: 'drawer_structure',
            url: '',
            resolvedUrl: omoResolveAppUrl(url),
            navigationMode: 'drawer'
        };
    }

    const holonCreateMatch = normalizedRouteToken.match(/^holon-create-(\d+)$/i);
    if (holonCreateMatch) {
        const contextHolonId = Number(holonCreateMatch[1]);
        if (!Number.isInteger(contextHolonId) || contextHolonId <= 0) {
            return null;
        }

        let url = `api/holons/create.php?cid=${encodeURIComponent(contextHolonId)}`;
        if (Number.isInteger(Number(oid)) && Number(oid) > 0) {
            url += `&oid=${encodeURIComponent(Number(oid))}`;
        }

        return {
            drawer: 'drawer_holon_create',
            url: '',
            resolvedUrl: omoResolveAppUrl(url),
            navigationMode: 'drawer'
        };
    }

    const holonEditMatch = normalizedRouteToken.match(/^holon-edit-(\d+)$/i);
    if (holonEditMatch) {
        const holonId = Number(holonEditMatch[1]);
        if (!Number.isInteger(holonId) || holonId <= 0) {
            return null;
        }

        let url = `api/holons/create.php?hid=${encodeURIComponent(holonId)}`;
        if (Number.isInteger(Number(oid)) && Number(oid) > 0) {
            url += `&oid=${encodeURIComponent(Number(oid))}`;
        }

        return {
            drawer: 'drawer_holon_create',
            url: '',
            resolvedUrl: omoResolveAppUrl(url),
            navigationMode: 'drawer'
        };
    }

    const holonTemplateEditMatch = normalizedRouteToken.match(/^holon-template-edit-(\d+)-(\d+)$/i);
    if (holonTemplateEditMatch) {
        const contextHolonId = Number(holonTemplateEditMatch[1]);
        const holonId = Number(holonTemplateEditMatch[2]);
        if (!Number.isInteger(contextHolonId) || contextHolonId <= 0 || !Number.isInteger(holonId) || holonId <= 0) {
            return null;
        }

        let url = `api/parameters/holon-templates/index.php?cid=${encodeURIComponent(contextHolonId)}&hid=${encodeURIComponent(holonId)}&compact=1`;
        if (Number.isInteger(Number(oid)) && Number(oid) > 0) {
            url += `&oid=${encodeURIComponent(Number(oid))}`;
        }

        return {
            drawer: 'drawer_holon_create',
            url: '',
            resolvedUrl: omoResolveAppUrl(url),
            navigationMode: 'drawer'
        };
    }

    const decisionRoute = omoParseDecisionRouteToken(normalizedRouteToken);
    if (decisionRoute) {
        let url = `api/decision/index.php?open_decision_id=${encodeURIComponent(decisionRoute.decisionId)}&decision_scope=global`;
        if (decisionRoute.mode && decisionRoute.mode !== 'default') {
            url += `&open_decision_mode=${encodeURIComponent(decisionRoute.mode)}`;
        }

        return {
            drawer: 'drawer_decisions',
            url: url,
            navigationMode: 'drawer'
        };
    }

    const calendarEventRoute = omoParseCalendarEventRouteToken(normalizedRouteToken);
    if (calendarEventRoute) {
        return {
            drawer: 'drawer_calendar',
            url: `api/calendar/index.php?open_event_id=${encodeURIComponent(calendarEventRoute.eventId)}`,
            navigationMode: 'drawer'
        };
    }

    const documentRoute = omoParseDocumentRouteToken(normalizedRouteToken);
    if (documentRoute) {
        let url = `api/documents/index.php?open_document_id=${encodeURIComponent(documentRoute.documentId)}&document_scope=global`;
        if (documentRoute.mode && documentRoute.mode !== 'detail') {
            url += `&open_document_mode=${encodeURIComponent(documentRoute.mode)}`;
        }

        return {
            drawer: 'drawer_documents',
            url: url,
            navigationMode: 'drawer'
        };
    }

    return null;
}

function getSidebarMenuConfig(hash = null, oid = null, cid = null, options = {}) {
    const route = omoParseHashState(hash).routeToken;
    const routeOptions = omoResolveDrawerRouteOptions(route, options);
    const specialRoute = omoResolveSpecialDrawerRoute(route, oid, cid, routeOptions);
    if (specialRoute) {
        specialRoute.routeOptions = routeOptions;
        return specialRoute;
    }

    const item = getSidebarMenuItem(omoGetMenuHashForRouteToken(route));

    if (!item.length) {
        return null;
    }

    return {
        drawer: omoNormalizeDrawerId(item.attr('data-drawer') || ''),
        url: item.attr('data-url') || '',
        navigationMode: String(item.attr('data-navigation-mode') || 'drawer').toLowerCase(),
        routeOptions: routeOptions
    };
}

function omoDetectStructureAvailabilityFromSidebar() {
    return document.querySelector('#menu_sidebar .menu-item[data-hash="structure"]') !== null;
}

function omoBroadcastStructureAvailability(enabled, options = {}) {
    const normalizedEnabled = enabled !== false;

    if (!window.omoConfig || typeof window.omoConfig !== 'object') {
        window.omoConfig = {};
    }

    window.omoConfig.structureEnabled = normalizedEnabled;

    window.dispatchEvent(new CustomEvent('omo-structure-availability-change', {
        detail: Object.assign({
            enabled: normalizedEnabled
        }, options || {})
    }));

    return normalizedEnabled;
}

function omoSyncStructureAvailabilityFromSidebar(options = {}) {
    return omoBroadcastStructureAvailability(
        omoDetectStructureAvailabilityFromSidebar(),
        options
    );
}

function omoRefreshSidebar(onLoaded = null) {
    loadContent('#menu_sidebar', 'api/getSidebar.php', 'sidebar', function () {
        const route = parseUrl();
        const hashState = omoParseHashState(route.hash || null);
        const routeToken = hashState.routeToken;
        const menuConfig = routeToken ? getSidebarMenuConfig(route.hash || null, route.oid, route.cid) : null;

        if (routeToken && !menuConfig) {
            const nextHash = omoBuildHashFromState(null, hashState.popupToken);
            history.replaceState({}, '', buildOmoUrl(route.oid, route.cid, nextHash));
            closeAllDrawers();
            handleRoute();
        } else {
            updateActiveMenu(route.hash || null);
        }

        omoSyncStructureAvailabilityFromSidebar({
            source: 'sidebar'
        });

        if (typeof onLoaded === 'function') {
            onLoaded();
        }
    });
}

function buildDrawerUrl(baseUrl, oid, cid = null, options = {}) {
    let resolvedCid = cid;
    const forcedScope = omoNormalizeDrawerForcedScope(options && options.forcedScope);

    if ((!resolvedCid || Number(resolvedCid) <= 0) && typeof baseUrl === 'string' && baseUrl.indexOf('api/decision/') !== -1) {
        if (typeof window.omoGetCurrentStructureHolonId === 'function') {
            const structureCid = Number(window.omoGetCurrentStructureHolonId() || 0);
            if (Number.isInteger(structureCid) && structureCid > 0) {
                resolvedCid = structureCid;
            }
        }
    }

    resolvedCid = omoNormalizeRouteCid(resolvedCid);

    const separator = baseUrl.indexOf('?') === -1 ? '?' : '&';
    let url = `${baseUrl}${separator}oid=${encodeURIComponent(oid)}`;

    if (resolvedCid) {
        url += `&cid=${encodeURIComponent(resolvedCid)}`;
    }

    if (forcedScope !== '') {
        if (baseUrl.indexOf('api/decision/') !== -1) {
            url += `&decision_scope=${encodeURIComponent(forcedScope)}`;
        } else if (baseUrl.indexOf('api/documents/') !== -1) {
            url += `&document_scope=${encodeURIComponent(forcedScope)}`;
        } else if (baseUrl.indexOf('api/calendar/') !== -1) {
            url += `&scope=${encodeURIComponent(forcedScope)}`;
        } else if (baseUrl.indexOf('api/team/') !== -1) {
            url += `&team_scope=${encodeURIComponent(forcedScope)}`;
        }
    }

    return omoResolveAppUrl(url);
}

function omoParseReusableDrawerUrl(url) {
    let parsedUrl = null;

    try {
        parsedUrl = new URL(
            String(url || ''),
            document.baseURI || window.location.href || window.location.origin
        );
    } catch (error) {
        return null;
    }

    if (!parsedUrl || !/\/api\/[^/?#]+\/index\.php$/i.test(parsedUrl.pathname || '')) {
        return null;
    }

    const searchKeys = Array.from(parsedUrl.searchParams.keys());
    if (searchKeys.some(function (key) {
        return /^open_/i.test(String(key || ''));
    })) {
        return null;
    }

    return parsedUrl;
}

function omoExtractDrawerContextFromRoot(root) {
    if (!(root instanceof Element)) {
        return null;
    }

    let oidAttribute = null;
    let cidAttribute = null;
    Array.from(root.attributes || []).forEach(function (attribute) {
        const attributeName = String(attribute && attribute.name ? attribute.name : '').trim().toLowerCase();
        if (!oidAttribute && /^data-omo-[a-z0-9-]+-oid$/i.test(attributeName)) {
            oidAttribute = attribute.value;
            return;
        }

        if (!cidAttribute && /^data-omo-[a-z0-9-]+-cid$/i.test(attributeName)) {
            cidAttribute = attribute.value;
        }
    });

    if (oidAttribute === null || cidAttribute === null) {
        return null;
    }

    return {
        oid: Number(oidAttribute || 0),
        cid: Number(omoNormalizeRouteCid(cidAttribute || 0) || 0)
    };
}

function omoFindDrawerContextRoot(drawer) {
    if (!drawer || !drawer.length) {
        return null;
    }

    const content = drawer.find('.drawer-content').get(0);
    if (!(content instanceof Element)) {
        return null;
    }

    const directChildren = Array.from(content.children || []);
    for (let index = 0; index < directChildren.length; index += 1) {
        const context = omoExtractDrawerContextFromRoot(directChildren[index]);
        if (context) {
            return {
                root: directChildren[index],
                context: context
            };
        }
    }

    const descendants = content.querySelectorAll('*');
    for (let index = 0; index < descendants.length; index += 1) {
        const context = omoExtractDrawerContextFromRoot(descendants[index]);
        if (context) {
            return {
                root: descendants[index],
                context: context
            };
        }
    }

    return null;
}

function omoCanReuseCachedDecisionDrawer(drawer, resolvedUrl) {
    if (!drawer || !drawer.length) {
        return false;
    }

    if (String(drawer.attr('id') || '') !== 'drawer_decisions') {
        return false;
    }

    const decisionRoot = drawer.find('#omo-decisions-root').get(0);
    if (!(decisionRoot instanceof HTMLElement)) {
        omoTraceDecisionLoad('reuseDecision:missingRoot', {
            resolvedUrl: resolvedUrl,
            currentUrl: String(drawer.data('omo-drawer-url') || '')
        });
        return false;
    }

    if (String(decisionRoot.getAttribute('data-omo-decisions-initialized') || '') !== '1') {
        omoTraceDecisionLoad('reuseDecision:notInitialized', {
            resolvedUrl: resolvedUrl,
            currentUrl: String(drawer.data('omo-drawer-url') || ''),
            initialized: String(decisionRoot.getAttribute('data-omo-decisions-initialized') || '')
        });
        return false;
    }

    const targetUrl = omoParseReusableDrawerUrl(resolvedUrl);
    if (!targetUrl) {
        omoTraceDecisionLoad('reuseDecision:invalidTargetUrl', {
            resolvedUrl: resolvedUrl,
            currentUrl: String(drawer.data('omo-drawer-url') || '')
        });
        return false;
    }

    const currentOid = Number(decisionRoot.getAttribute('data-omo-decision-oid') || 0);
    const currentCid = Number(omoNormalizeRouteCid(decisionRoot.getAttribute('data-omo-decision-cid') || 0) || 0);
    const targetOid = Number(targetUrl.searchParams.get('oid') || 0);
    const targetCid = Number(omoNormalizeRouteCid(targetUrl.searchParams.get('cid') || 0) || 0);
    const canReuse = currentOid > 0
        && targetOid > 0
        && currentOid === targetOid
        && currentCid === targetCid;

    omoTraceDecisionLoad('reuseDecision:contextCheck', {
        resolvedUrl: resolvedUrl,
        currentUrl: String(drawer.data('omo-drawer-url') || ''),
        currentOid: currentOid,
        currentCid: currentCid,
        targetOid: targetOid,
        targetCid: targetCid,
        canReuse: canReuse
    });

    return canReuse;
}

function omoCanReuseCachedPanelDrawer(drawer, resolvedUrl, currentUrl = '') {
    if (!drawer || !drawer.length) {
        return false;
    }

    const hasLoadedContent = drawer.find('.drawer-content').children().length > 0;
    if (!hasLoadedContent) {
        return false;
    }

    if (String(drawer.attr('id') || '') === 'drawer_decisions') {
        return omoCanReuseCachedDecisionDrawer(drawer, resolvedUrl);
    }

    const targetUrl = omoParseReusableDrawerUrl(resolvedUrl);
    const activeUrl = omoParseReusableDrawerUrl(currentUrl);
    if (!targetUrl || !activeUrl) {
        return false;
    }

    if ((targetUrl.pathname || '') !== (activeUrl.pathname || '')) {
        return false;
    }

    const drawerContext = omoFindDrawerContextRoot(drawer);
    if (!drawerContext || !drawerContext.root || !drawerContext.context) {
        return false;
    }

    const currentOid = Number(drawerContext.context.oid || 0);
    const currentCid = Number(omoNormalizeRouteCid(drawerContext.context.cid || 0) || 0);
    const targetOid = Number(targetUrl.searchParams.get('oid') || 0);
    const targetCid = Number(omoNormalizeRouteCid(targetUrl.searchParams.get('cid') || 0) || 0);

    return currentOid > 0
        && targetOid > 0
        && currentOid === targetOid
        && currentCid === targetCid;
}

function updateExternalPanelDrawerPosition(drawer = null) {
    const externalDrawer = drawer || document.getElementById('omoExternalPanelDrawer');
    if (!externalDrawer) {
        return;
    }

    if (externalDrawer.classList.contains('omo-external-panel-drawer--top-sheet')) {
        externalDrawer.style.left = '0';
        externalDrawer.style.width = '100%';
        return;
    }

    if (omoIsMobileLayout()) {
        const compactSidebarWidth = omoGetCompactSidebarWidth();
        externalDrawer.style.left = compactSidebarWidth + 'px';
        externalDrawer.style.width = 'calc(100% - ' + compactSidebarWidth + 'px)';
        return;
    }

    const leftWidth = $('#panel-left').outerWidth() || 0;
    externalDrawer.style.left = leftWidth + 'px';
    externalDrawer.style.width = 'calc(100% - ' + leftWidth + 'px)';
}

function omoEnsureExternalPanelDrawer() {
    const content = document.querySelector('.content');
    if (!content) {
        return null;
    }

    let drawer = document.getElementById('omoExternalPanelDrawer');
    if (!drawer) {
        drawer = document.createElement('div');
        drawer.id = 'omoExternalPanelDrawer';
        drawer.hidden = true;
        drawer.className = 'omo-overlay-drawer omo-external-panel-drawer';
        drawer.setAttribute('data-omo-external-panel-drawer', '1');
        drawer.innerHTML = ''
            + '<div class="omo-overlay-drawer__backdrop" data-omo-external-panel-drawer-close="1"></div>'
            + '<div class="omo-overlay-drawer__panel">'
            + '  <div class="omo-overlay-drawer__header generic-drawer-header">'
            + '    <div class="omo-overlay-drawer__header-copy generic-drawer-header__copy">'
            + '      <h3 class="omo-overlay-drawer__title" data-omo-external-panel-drawer-title>Edition</h3>'
            + '      <p class="omo-overlay-drawer__description" data-omo-external-panel-drawer-description hidden></p>'
            + '    </div>'
            + '    <div class="generic-drawer-header__actions">'
            + '      <button type="button" class="omo-overlay-drawer__close" data-omo-external-panel-drawer-close="1">Fermer</button>'
            + '    </div>'
            + '  </div>'
            + '  <div class="omo-overlay-drawer__body" data-omo-external-panel-drawer-body></div>'
            + '</div>'
            + '<button type="button" class="omo-external-panel-drawer__peek-toggle" data-omo-external-panel-drawer-peek-toggle="1" hidden>'
            + '  <span class="omo-external-panel-drawer__peek-label" data-omo-external-panel-drawer-peek-label>Edition</span>'
            + '  <span class="omo-external-panel-drawer__peek-dismiss" data-omo-external-panel-drawer-peek-dismiss="1" role="button" aria-label="Fermer la reunion" title="Fermer la reunion">&times;</span>'
            + '</button>';

        drawer.querySelectorAll('[data-omo-external-panel-drawer-close="1"]').forEach(function (button) {
            button.addEventListener('click', function () {
                omoCloseExternalPanelDrawer();
            });
        });

        const peekToggle = drawer.querySelector('[data-omo-external-panel-drawer-peek-toggle="1"]');
        if (peekToggle) {
            peekToggle.addEventListener('click', function () {
                omoToggleExternalPanelDrawerPeek();
            });
        }

        const peekDismiss = drawer.querySelector('[data-omo-external-panel-drawer-peek-dismiss="1"]');
        if (peekDismiss) {
            peekDismiss.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                omoDismissExternalPanelDrawer();
            });
        }

        content.appendChild(drawer);
    }

    updateExternalPanelDrawerPosition(drawer);
    return drawer;
}

function omoResolveExternalPanelHostContext() {
    const route = typeof parseUrl === 'function'
        ? parseUrl()
        : { oid: null, cid: null, hash: null };
    const hashState = omoParseHashState(route && route.hash ? route.hash : null);
    const hostDrawer = $('.drawer.open').last();

    if (hostDrawer.length) {
        return {
            hostKind: 'drawer',
            hostRouteToken: hashState.routeToken || '',
            hostDrawerId: String(hostDrawer.attr('id') || ''),
            hostUrl: String(hostDrawer.data('omo-drawer-url') || ''),
            hostPanelUrl: '',
            oid: Number(route && route.oid ? route.oid : 0),
            cid: Number(route && route.cid ? route.cid : 0)
        };
    }

    return {
        hostKind: 'panel-right',
        hostRouteToken: '',
        hostDrawerId: '',
        hostUrl: '',
        hostPanelUrl: omoGetMainRightPanelLoadedUrl() || omoBuildMainRightPanelUrl(route ? route.oid : null, route ? route.cid : null),
        oid: Number(route && route.oid ? route.oid : 0),
        cid: Number(route && route.cid ? route.cid : 0)
    };
}

function omoGetExternalPanelDrawerContext(target = null) {
    let drawer = null;

    if (target instanceof Element) {
        drawer = target.closest('[data-omo-external-panel-drawer="1"]');
    } else if (target && target.jquery && target.length) {
        drawer = target.get(0).closest('[data-omo-external-panel-drawer="1"]');
    }

    if (!drawer) {
        drawer = document.getElementById('omoExternalPanelDrawer');
    }

    if (!drawer) {
        return null;
    }

    return {
        drawer: drawer,
        hostKind: String(drawer.dataset.omoHostKind || ''),
        hostRouteToken: String(drawer.dataset.omoHostRouteToken || ''),
        hostDrawerId: String(drawer.dataset.omoHostDrawerId || ''),
        hostUrl: String(drawer.dataset.omoHostUrl || ''),
        hostPanelUrl: String(drawer.dataset.omoHostPanelUrl || ''),
        oid: Number(drawer.dataset.omoHostOid || 0),
        cid: Number(drawer.dataset.omoHostCid || 0)
    };
}

function omoRefreshExternalPanelDrawerHost(target = null) {
    const context = omoGetExternalPanelDrawerContext(target);
    if (!context) {
        return false;
    }

    const route = typeof parseUrl === 'function'
        ? parseUrl()
        : {
            oid: context.oid || null,
            cid: context.cid || null,
            hash: null
        };

    if (context.hostKind === 'drawer') {
        let resolvedUrl = context.hostUrl;

        if (context.hostRouteToken) {
            const menuConfig = getSidebarMenuConfig(context.hostRouteToken, route.oid, route.cid);
            if (menuConfig) {
                if (menuConfig.resolvedUrl) {
                    resolvedUrl = menuConfig.resolvedUrl;
                } else if (menuConfig.url) {
                    resolvedUrl = buildDrawerUrl(menuConfig.url, route.oid, route.cid);
                }
            }
        }

        if (context.hostDrawerId && resolvedUrl && typeof refreshDrawer === 'function') {
            return refreshDrawer(context.hostDrawerId, resolvedUrl);
        }

        return false;
    }

    const panelUrl = omoBuildMainRightPanelUrl(route.oid, route.cid) || context.hostPanelUrl;
    if (panelUrl && typeof loadContent === 'function') {
        $('#panel-right').data('omo-main-right-loaded-url', panelUrl);
        loadContent('#panel-right', panelUrl, 'panel', function () {
            $('#panel-right').data('omo-main-right-loaded-url', panelUrl);
        });
        return true;
    }

    return false;
}

function omoCanCloseExternalPanelDrawer(drawer = null, settings = {}) {
    if (settings && settings.skipCloseGuard === true) {
        return true;
    }

    if (typeof window.omoPvEditorConfirmCanClose !== 'function') {
        return true;
    }

    try {
        return window.omoPvEditorConfirmCanClose({
            drawer: drawer || document.getElementById('omoExternalPanelDrawer'),
            settings: settings || {}
        }) !== false;
    } catch (error) {
        return true;
    }
}

function omoCloseExternalPanelDrawer() {
    const settings = arguments.length > 0 && arguments[0] && typeof arguments[0] === 'object'
        ? arguments[0]
        : {};
    const drawer = document.getElementById('omoExternalPanelDrawer');
    if (!drawer) {
        return;
    }

    if (!omoCanCloseExternalPanelDrawer(drawer, settings)) {
        return;
    }

    const closeRouteToken = settings.force === true
        ? null
        : omoNormalizeHashToken(settings.closeRouteToken || drawer.dataset.omoCloseRouteToken || '');
    const keepMounted = settings.forceReset === true
        ? false
        : drawer.dataset.omoKeepMounted === '1';
    const peekToggle = drawer.querySelector('[data-omo-external-panel-drawer-peek-toggle="1"]');

    drawer.classList.remove('is-peek');
    drawer.classList.remove('is-open');
    drawer.dataset.omoPeekState = '0';
    if (peekToggle) {
        peekToggle.setAttribute('aria-expanded', 'false');
    }
    window.setTimeout(function () {
        if (drawer.classList.contains('is-open')) {
            return;
        }

        drawer.hidden = true;
        const body = drawer.querySelector('[data-omo-external-panel-drawer-body]');
        if (body && !keepMounted) {
            body.innerHTML = '';
        }
    }, 200);

    if (closeRouteToken && typeof window.omoOpenDrawerHashState === 'function') {
        window.omoOpenDrawerHashState(closeRouteToken);
    }
}

function omoFormatExternalDrawerTitle(routeToken = '') {
    const normalizedRouteToken = omoNormalizeHashToken(routeToken);

    if (!normalizedRouteToken) {
        return 'Edition';
    }

    if (/^holon-create-\d+$/i.test(normalizedRouteToken)) {
        return 'Ajouter';
    }

    if (/^holon-edit-\d+$/i.test(normalizedRouteToken)) {
        return 'Modifier';
    }

    if (/^holon-template-edit-\d+-\d+$/i.test(normalizedRouteToken)) {
        return 'Modifier';
    }

    return 'Edition';
}

function omoSetExternalPanelDrawerPeekState(drawer = null, shouldPeek = false) {
    const externalDrawer = drawer || document.getElementById('omoExternalPanelDrawer');
    if (!externalDrawer || !externalDrawer.classList.contains('omo-external-panel-drawer--top-sheet')) {
        return false;
    }

    const nextPeekState = shouldPeek === true;
    const peekToggle = externalDrawer.querySelector('[data-omo-external-panel-drawer-peek-toggle="1"]');

    externalDrawer.classList.toggle('is-peek', nextPeekState);
    externalDrawer.dataset.omoPeekState = nextPeekState ? '1' : '0';

    if (peekToggle) {
        peekToggle.setAttribute('aria-expanded', nextPeekState ? 'false' : 'true');
    }

    return true;
}

function omoToggleExternalPanelDrawerPeek(forcePeek = null) {
    const drawer = document.getElementById('omoExternalPanelDrawer');
    if (!drawer || !drawer.classList.contains('omo-external-panel-drawer--top-sheet') || !drawer.classList.contains('is-open')) {
        return false;
    }

    const nextPeekState = forcePeek === null
        ? !drawer.classList.contains('is-peek')
        : forcePeek === true;

    return omoSetExternalPanelDrawerPeekState(drawer, nextPeekState);
}

function omoPeekPersistentExternalPanelDrawer(options = {}) {
    const drawer = document.getElementById('omoExternalPanelDrawer');
    if (
        !drawer
        || !drawer.classList.contains('omo-external-panel-drawer--top-sheet')
        || !drawer.classList.contains('is-open')
        || drawer.dataset.omoKeepMounted !== '1'
    ) {
        return false;
    }

    const persistKeyPrefix = String(options.persistKeyPrefix || '').trim();
    const contentSelector = String(options.contentSelector || '').trim();
    if (persistKeyPrefix !== '' && !String(drawer.dataset.omoPersistKey || '').startsWith(persistKeyPrefix)) {
        return false;
    }
    if (contentSelector !== '' && !drawer.querySelector(contentSelector)) {
        return false;
    }

    return omoSetExternalPanelDrawerPeekState(drawer, true);
}

function omoDismissExternalPanelDrawer() {
    omoCloseExternalPanelDrawer({
        force: true,
        forceReset: true
    });
}

function omoOpenExternalPanelDrawer(options = {}) {
    const drawer = omoEnsureExternalPanelDrawer();
    if (!drawer) {
        return false;
    }

    const hostContext = omoResolveExternalPanelHostContext();
    const url = String(options.url || '').trim();
    const mode = String(options.mode || 'fetch').trim().toLowerCase();
    const title = String(options.title || 'Edition').trim() || 'Edition';
    const description = String(options.description || '').trim();
    const variant = String(options.variant || '').trim().toLowerCase();
    const persistKey = String(options.persistKey || '').trim();
    const keepMounted = options.keepMountedOnClose === true;
    const closeRouteToken = omoNormalizeHashToken(options.closeRouteToken || '');
    const body = drawer.querySelector('[data-omo-external-panel-drawer-body]');
    const titleNode = drawer.querySelector('[data-omo-external-panel-drawer-title]');
    const descriptionNode = drawer.querySelector('[data-omo-external-panel-drawer-description]');
    const peekToggle = drawer.querySelector('[data-omo-external-panel-drawer-peek-toggle="1"]');
    const peekLabelNode = drawer.querySelector('[data-omo-external-panel-drawer-peek-label]');
    const currentPersistKey = String(drawer.dataset.omoPersistKey || '').trim();
    const currentContentUrl = String(drawer.dataset.omoExternalContentUrl || '').trim();
    const canReuseMountedContent = keepMounted
        && persistKey !== ''
        && currentPersistKey === persistKey
        && currentContentUrl === url
        && body
        && body.childNodes.length > 0;

    if (!body || !url) {
        return false;
    }

    drawer.dataset.omoHostKind = hostContext.hostKind || '';
    drawer.dataset.omoHostRouteToken = hostContext.hostRouteToken || '';
    drawer.dataset.omoHostDrawerId = hostContext.hostDrawerId || '';
    drawer.dataset.omoHostUrl = hostContext.hostUrl || '';
    drawer.dataset.omoHostPanelUrl = hostContext.hostPanelUrl || '';
    drawer.dataset.omoHostOid = String(hostContext.oid || 0);
    drawer.dataset.omoHostCid = String(hostContext.cid || 0);
    drawer.dataset.omoPersistKey = persistKey;
    drawer.dataset.omoKeepMounted = keepMounted ? '1' : '0';
    drawer.dataset.omoExternalContentUrl = url;
    drawer.dataset.omoCloseRouteToken = closeRouteToken || '';
    drawer.classList.toggle('omo-external-panel-drawer--top-sheet', variant === 'top-sheet');
    drawer.classList.remove('is-peek');
    drawer.dataset.omoPeekState = '0';

    if (titleNode) {
        titleNode.textContent = title;
    }

    if (peekToggle) {
        const isTopSheet = variant === 'top-sheet';
        peekToggle.hidden = !isTopSheet;
        peekToggle.setAttribute('aria-expanded', 'true');
        peekToggle.setAttribute('title', title);
    }

    if (peekLabelNode) {
        peekLabelNode.textContent = title;
    }

    if (descriptionNode) {
        if (description !== '') {
            descriptionNode.hidden = false;
            descriptionNode.textContent = description;
        } else {
            descriptionNode.hidden = true;
            descriptionNode.textContent = '';
        }
    }

    updateExternalPanelDrawerPosition(drawer);
    drawer.hidden = false;
    window.requestAnimationFrame(function () {
        drawer.classList.add('is-open');
    });

    if (mode === 'fetch') {
        if (!canReuseMountedContent) {
            loadContent(body, url, 'panel');
        }
        return true;
    }

    if (mode === 'html') {
        body.innerHTML = url;
        return true;
    }

    window.location.href = url;
    return true;
}

function omoOpenExternalRouteDrawer(routeToken, options = {}) {
    const normalizedRouteToken = omoNormalizeHashToken(routeToken);
    if (!normalizedRouteToken) {
        return false;
    }

    const route = typeof parseUrl === 'function'
        ? parseUrl()
        : { oid: null, cid: null };
    const menuConfig = getSidebarMenuConfig(normalizedRouteToken, route.oid, route.cid, options);
    if (!menuConfig) {
        return false;
    }

    const resolvedUrl = menuConfig.resolvedUrl
        ? menuConfig.resolvedUrl
        : (menuConfig.url ? buildDrawerUrl(menuConfig.url, route.oid, route.cid, menuConfig.routeOptions || options) : '');

    if (!resolvedUrl) {
        return false;
    }

    return omoOpenExternalPanelDrawer({
        title: String(options.title || omoFormatExternalDrawerTitle(normalizedRouteToken)).trim() || 'Edition',
        description: String(options.description || '').trim(),
        url: resolvedUrl,
        mode: 'fetch'
    });
}

function omoNormalizeHashToken(token) {
    const normalizedToken = String(token || '').trim();

    return normalizedToken === '' ? null : normalizedToken;
}

const OMO_SEARCH_POPUP_STORAGE_KEY = 'omo.search.popup';
const OMO_SEARCH_POPUP_MAX_JOBS = 24;
const OMO_SEARCH_POPUP_MAX_AGE_MS = 12 * 60 * 60 * 1000;
let omoSearchPopupMemoryState = {
    pending: null,
    jobs: {}
};

function omoGetSearchPopupStorage() {
    try {
        return window.sessionStorage || null;
    } catch (error) {
        return null;
    }
}

function omoNormalizeSearchPopupContext(context = null) {
    const rawContext = context && typeof context === 'object'
        ? context
        : {};
    const parsedOid = Number(rawContext.oid || rawContext.organizationId || 0);
    const parsedCid = Number(omoNormalizeRouteCid(rawContext.cid || rawContext.currentHolonId || 0) || 0);

    return {
        oid: Number.isInteger(parsedOid) && parsedOid > 0 ? parsedOid : null,
        cid: Number.isInteger(parsedCid) && parsedCid > 0 ? parsedCid : null
    };
}

function omoNormalizeSearchPopupScopes(scopes = []) {
    const rawScopes = Array.isArray(scopes)
        ? scopes
        : [scopes];
    const seenScopes = new Set();
    const normalizedScopes = [];

    rawScopes.forEach(function (scope) {
        const normalizedScope = String(scope || '').trim();
        if (normalizedScope === '' || seenScopes.has(normalizedScope)) {
            return;
        }

        seenScopes.add(normalizedScope);
        normalizedScopes.push(normalizedScope);
    });

    return normalizedScopes;
}

function omoNormalizeSearchPopupEntry(entry = null) {
    const rawEntry = entry && typeof entry === 'object'
        ? entry
        : {};
    const normalizedContext = omoNormalizeSearchPopupContext(rawEntry);
    const query = String(rawEntry.query || '').trim();
    const scopes = omoNormalizeSearchPopupScopes(rawEntry.scopes || []);
    const parsedJobId = Number(rawEntry.jobId || 0);
    const jobToken = String(rawEntry.jobToken || '').trim();
    const parsedSavedAt = Number(rawEntry.savedAt || Date.now());
    const savedAt = Number.isFinite(parsedSavedAt) && parsedSavedAt > 0
        ? parsedSavedAt
        : Date.now();

    if (!normalizedContext.oid || (query === '' && (!Number.isInteger(parsedJobId) || parsedJobId <= 0))) {
        return null;
    }

    return {
        query: query,
        scopes: scopes,
        oid: normalizedContext.oid,
        cid: normalizedContext.cid,
        jobId: Number.isInteger(parsedJobId) && parsedJobId > 0 ? parsedJobId : null,
        jobToken: jobToken !== '' ? jobToken : null,
        savedAt: savedAt
    };
}

function omoNormalizeSearchPopupStorageState(state = null) {
    const rawState = state && typeof state === 'object'
        ? state
        : {};
    const now = Date.now();
    const jobEntries = [];
    const rawJobs = rawState.jobs && typeof rawState.jobs === 'object'
        ? rawState.jobs
        : {};

    Object.keys(rawJobs).forEach(function (jobKey) {
        const entry = omoNormalizeSearchPopupEntry(rawJobs[jobKey]);
        if (!entry || !entry.jobId || !entry.jobToken) {
            return;
        }

        if ((now - entry.savedAt) > OMO_SEARCH_POPUP_MAX_AGE_MS) {
            return;
        }

        jobEntries.push(entry);
    });

    jobEntries.sort(function (leftEntry, rightEntry) {
        return rightEntry.savedAt - leftEntry.savedAt;
    });

    const jobs = {};
    jobEntries.slice(0, OMO_SEARCH_POPUP_MAX_JOBS).forEach(function (entry) {
        jobs[String(entry.jobId)] = entry;
    });

    let pending = omoNormalizeSearchPopupEntry(rawState.pending);
    if (pending && (now - pending.savedAt) > OMO_SEARCH_POPUP_MAX_AGE_MS) {
        pending = null;
    }

    if (pending && pending.jobId && pending.jobToken && !jobs[String(pending.jobId)]) {
        jobs[String(pending.jobId)] = pending;
    }

    return {
        pending: pending,
        jobs: jobs
    };
}

function omoReadSearchPopupStorageState() {
    const storage = omoGetSearchPopupStorage();
    if (!storage) {
        return omoNormalizeSearchPopupStorageState(omoSearchPopupMemoryState);
    }

    try {
        const rawValue = storage.getItem(OMO_SEARCH_POPUP_STORAGE_KEY);
        if (!rawValue) {
            return omoNormalizeSearchPopupStorageState(omoSearchPopupMemoryState);
        }

        const normalizedState = omoNormalizeSearchPopupStorageState(JSON.parse(rawValue));
        omoSearchPopupMemoryState = normalizedState;
        return normalizedState;
    } catch (error) {
        return omoNormalizeSearchPopupStorageState(omoSearchPopupMemoryState);
    }
}

function omoWriteSearchPopupStorageState(state = null) {
    const storage = omoGetSearchPopupStorage();
    const normalizedState = omoNormalizeSearchPopupStorageState(state);
    if (!storage) {
        omoSearchPopupMemoryState = normalizedState;
        return true;
    }

    try {
        storage.setItem(OMO_SEARCH_POPUP_STORAGE_KEY, JSON.stringify(normalizedState));
        omoSearchPopupMemoryState = normalizedState;
        return true;
    } catch (error) {
        omoSearchPopupMemoryState = normalizedState;
        return true;
    }
}

function omoSearchPopupContextsMatch(entry, context = null) {
    const normalizedEntry = omoNormalizeSearchPopupEntry(entry);
    const normalizedContext = omoNormalizeSearchPopupContext(
        context && typeof context === 'object'
            ? context
            : (typeof parseUrl === 'function' ? parseUrl() : null)
    );

    if (!normalizedEntry || !normalizedContext.oid) {
        return false;
    }

    return normalizedEntry.oid === normalizedContext.oid
        && (normalizedEntry.cid || null) === (normalizedContext.cid || null);
}

function omoSetSearchPopupPendingState(query, scopes = [], context = null) {
    const normalizedContext = omoNormalizeSearchPopupContext(
        context && typeof context === 'object'
            ? context
            : (typeof parseUrl === 'function' ? parseUrl() : null)
    );
    const normalizedQuery = String(query || '').trim();
    const normalizedScopes = omoNormalizeSearchPopupScopes(scopes);

    if (!normalizedContext.oid || normalizedQuery === '') {
        return false;
    }

    const state = omoReadSearchPopupStorageState();
    state.pending = {
        query: normalizedQuery,
        scopes: normalizedScopes,
        oid: normalizedContext.oid,
        cid: normalizedContext.cid,
        jobId: null,
        jobToken: null,
        savedAt: Date.now()
    };

    return omoWriteSearchPopupStorageState(state);
}

function omoGetSearchPopupPendingState(context = null) {
    const state = omoReadSearchPopupStorageState();
    return omoSearchPopupContextsMatch(state.pending, context)
        ? state.pending
        : null;
}

function omoGetSearchPopupJobState(jobId, context = null) {
    const normalizedJobId = Number(jobId || 0);
    if (!Number.isInteger(normalizedJobId) || normalizedJobId <= 0) {
        return null;
    }

    const state = omoReadSearchPopupStorageState();
    const jobState = state.jobs[String(normalizedJobId)] || null;

    return omoSearchPopupContextsMatch(jobState, context)
        ? jobState
        : null;
}

function omoReplaceSearchPopupHashWithJobId(jobId) {
    const normalizedJobId = Number(jobId || 0);
    if (!Number.isInteger(normalizedJobId) || normalizedJobId <= 0 || typeof parseUrl !== 'function') {
        return false;
    }

    const route = parseUrl();
    const hashState = omoParseHashState(route.hash || null);
    if (hashState.popupKey !== 'search') {
        return false;
    }

    const nextHash = omoBuildHashFromState(
        hashState.routeToken,
        omoBuildPopupToken('search', normalizedJobId)
    );

    if ((nextHash || null) === (route.hash || null)) {
        return true;
    }

    history.replaceState({}, '', buildOmoUrl(route.oid, route.cid, nextHash));

    if (currentState && typeof currentState === 'object') {
        currentState = {
            oid: currentState.oid,
            cid: currentState.cid,
            hash: nextHash,
            routeToken: hashState.routeToken,
            popupToken: omoBuildPopupToken('search', normalizedJobId)
        };
    }

    return true;
}

function omoRegisterSearchPopupJobState(options = {}) {
    const rawOptions = options && typeof options === 'object'
        ? options
        : {};
    const normalizedEntry = omoNormalizeSearchPopupEntry({
        query: rawOptions.query,
        scopes: rawOptions.scopes,
        oid: rawOptions.oid || rawOptions.organizationId,
        cid: rawOptions.cid || rawOptions.currentHolonId,
        jobId: rawOptions.jobId,
        jobToken: rawOptions.jobToken,
        savedAt: Date.now()
    });

    if (!normalizedEntry || !normalizedEntry.jobId || !normalizedEntry.jobToken) {
        return false;
    }

    const state = omoReadSearchPopupStorageState();
    state.jobs[String(normalizedEntry.jobId)] = normalizedEntry;

    if (rawOptions.setPending !== false) {
        state.pending = normalizedEntry;
    }

    omoWriteSearchPopupStorageState(state);

    if (rawOptions.syncHash !== false) {
        omoReplaceSearchPopupHashWithJobId(normalizedEntry.jobId);
    }

    return true;
}

function omoParsePopupToken(token = null) {
    const rawToken = omoNormalizeHashToken(token);

    if (!rawToken || !/^[a-z0-9/_-]+$/i.test(rawToken)) {
        return {
            raw: null,
            key: null,
            id: null
        };
    }

    const tokenMatch = rawToken.match(/^(.*)-(\d+)$/);
    const popupKey = tokenMatch && tokenMatch[1]
        ? tokenMatch[1]
        : rawToken;
    const popupId = tokenMatch && tokenMatch[2]
        ? Number(tokenMatch[2])
        : null;

    return {
        raw: rawToken,
        key: popupKey,
        id: Number.isInteger(popupId) && popupId > 0 ? popupId : null
    };
}

function omoParseHashState(rawHash = null) {
    const normalizedHash = String(rawHash === null ? (window.location.hash || '') : rawHash)
        .replace(/^#/, '')
        .trim();
    const tokens = normalizedHash === ''
        ? []
        : normalizedHash.split('|');
    const routeToken = omoNormalizeHashToken(tokens.length > 0 ? tokens[0] : null);
    const popupState = omoParsePopupToken(tokens.length > 1 ? tokens[1] : null);

    return {
        raw: normalizedHash,
        tokens: tokens.map(function (token) {
            return String(token || '').trim();
        }),
        routeToken: routeToken,
        popupToken: popupState.raw,
        popupKey: popupState.key,
        popupId: popupState.id
    };
}

function omoBuildHashFromState(routeToken = null, popupToken = null) {
    const normalizedRouteToken = omoNormalizeHashToken(routeToken);
    const normalizedPopupToken = omoNormalizeHashToken(popupToken);

    if (!normalizedRouteToken && !normalizedPopupToken) {
        return null;
    }

    if (!normalizedPopupToken) {
        return normalizedRouteToken;
    }

    return `${normalizedRouteToken || ''}|${normalizedPopupToken}`;
}

function omoBuildPopupToken(key, id = null) {
    const popupKey = omoNormalizeHashToken(key);
    const parsedId = Number(id);

    if (!popupKey) {
        return null;
    }

    return Number.isInteger(parsedId) && parsedId > 0
        ? `${popupKey}-${parsedId}`
        : popupKey;
}

function omoSetPopupHashState(options = {}) {
    const route = parseUrl();
    const hashState = omoParseHashState(route.hash);
    const popupKey = options.key !== undefined
        ? options.key
        : hashState.popupKey;
    const popupToken = options.open === false
        ? null
        : omoBuildPopupToken(popupKey, options.id || null);
    const nextHash = omoBuildHashFromState(
        options.routeToken !== undefined ? options.routeToken : hashState.routeToken,
        popupToken
    );
    const currentHash = route.hash || null;

    if ((nextHash || null) === currentHash) {
        return;
    }

    const url = buildOmoUrl(route.oid, route.cid, nextHash);

    if (options.replace === true) {
        history.replaceState({}, '', url);
    } else {
        history.pushState({}, '', url);
    }

    handleRoute();
}

function omoSetDrawerHashState(options = {}) {
    const route = parseUrl();
    const hashState = omoParseHashState(route.hash);
    const routeToken = options.open === false
        ? null
        : omoNormalizeHashToken(options.routeToken !== undefined ? options.routeToken : hashState.routeToken);
    const nextHash = omoBuildHashFromState(
        routeToken,
        options.popupToken !== undefined ? options.popupToken : hashState.popupToken
    );
    const currentHash = route.hash || null;

    if ((nextHash || null) === currentHash) {
        return;
    }

    const url = buildOmoUrl(route.oid, route.cid, nextHash);

    if (options.replace === true) {
        history.replaceState({}, '', url);
    } else {
        history.pushState({}, '', url);
    }

    handleRoute();
}

let omoPopupBootstrapHandled = false;
let omoPopupModalSyncing = false;
let omoPopupModalManaged = false;

function omoEnsurePopupBootstrapState(oid, cid, routeToken, popupKey, popupId) {
    if (omoPopupBootstrapHandled) {
        return;
    }

    omoPopupBootstrapHandled = true;
    const currentHashState = omoParseHashState((window.location.hash || '').replace(/^#/, ''));
    const baseHash = omoBuildHashFromState(routeToken, null);
    const listHash = omoBuildHashFromState(routeToken, omoBuildPopupToken(popupKey));

    if (!currentHashState.popupToken || !popupKey) {
        return;
    }

    history.replaceState({}, '', buildOmoUrl(oid, cid, baseHash));

    if (currentHashState.popupId) {
        const detailHash = omoBuildHashFromState(routeToken, omoBuildPopupToken(popupKey, popupId));
        history.pushState({}, '', buildOmoUrl(oid, cid, listHash));
        history.pushState({}, '', buildOmoUrl(oid, cid, detailHash));
        return;
    }

    history.pushState({}, '', buildOmoUrl(oid, cid, listHash));
}

function omoGetTopbarHelpItems() {
    const config = window.commonTopbarConfig && typeof window.commonTopbarConfig === 'object'
        ? window.commonTopbarConfig
        : null;
    const helpItems = config && Array.isArray(config.helpItems)
        ? config.helpItems
        : [];

    return helpItems.filter(function (item) {
        return item && typeof item === 'object';
    });
}

function omoGetTopbarHelpItem(helpKey) {
    const normalizedKey = String(helpKey || '').trim().toLowerCase();
    if (!normalizedKey) {
        return null;
    }

    const helpItems = omoGetTopbarHelpItems();
    for (let index = 0; index < helpItems.length; index += 1) {
        const item = helpItems[index];
        const itemKey = String(item.key || '').trim().toLowerCase();
        if (itemKey === normalizedKey) {
            return item;
        }
    }

    return null;
}

function omoFormatPopupTitle(popupKey) {
    if (!popupKey) {
        return 'Aide';
    }

    if (popupKey === 'faq' || popupKey.indexOf('faq/') === 0) {
        const faqHelpItem = omoGetTopbarHelpItem('faq');
        return String((faqHelpItem && (faqHelpItem.title || faqHelpItem.label)) || 'FAQ OMO').trim() || 'FAQ OMO';
    }

    if (popupKey === 'tutorials' || popupKey.indexOf('tutorials/') === 0) {
        const tutorialsHelpItem = omoGetTopbarHelpItem('tutorials');
        return String((tutorialsHelpItem && (tutorialsHelpItem.title || tutorialsHelpItem.label)) || 'Tutoriels').trim() || 'Tutoriels';
    }

    if (popupKey === 'user') {
        return 'Profil membre';
    }

    if (popupKey === 'member-actions') {
        return 'Actions membre';
    }

    if (popupKey === 'document-move') {
        return 'Deplacer';
    }

    if (popupKey === 'holon-move') {
        return 'Deplacer';
    }

    if (popupKey === 'holon-delete') {
        return 'Supprimer';
    }

    if (popupKey === 'search') {
        return 'Recherche';
    }

    return (popupKey.split('/').pop() || popupKey)
        .replace(/[-_]+/g, ' ')
        .replace(/\b\w/g, function (character) {
            return character.toUpperCase();
        });
}

function omoResolveTutorialPopupRoute(popupKey, popupId, currentRoute) {
    const normalizedPopupKey = omoNormalizeHashToken(popupKey);
    const routeOrganizationId = Number(currentRoute && currentRoute.oid ? currentRoute.oid : 0);
    const routeParts = String(normalizedPopupKey || '').split('/');
    let targetPath = '/omo/api/lms/';
    let resolvedParcoursId = 0;
    const queryParts = ['embed=1'];

    if (!normalizedPopupKey || String(routeParts[0] || '').toLowerCase() !== 'tutorials') {
        return null;
    }

    if (routeOrganizationId > 0) {
        queryParts.push(`oid=${encodeURIComponent(routeOrganizationId)}`);
    } else {
        queryParts.push('catalog=basic');
    }

    if (String(routeParts[1] || '').toLowerCase() === 'parcours') {
        resolvedParcoursId = Number(routeParts[2] || 0);
        if (!Number.isInteger(resolvedParcoursId) || resolvedParcoursId <= 0) {
            return null;
        }

        targetPath = '/omo/api/lms/parcours.php';
        queryParts.push(`idp=${encodeURIComponent(resolvedParcoursId)}`);
    }

    if (Number.isInteger(Number(popupId)) && Number(popupId) > 0) {
        queryParts.push(`mid=${encodeURIComponent(Number(popupId))}`);
    }

    const resolvedUrl = `${targetPath}?${queryParts.join('&')}`;

    return {
        key: normalizedPopupKey,
        id: Number.isInteger(Number(popupId)) && Number(popupId) > 0 ? Number(popupId) : null,
        token: omoBuildPopupToken(normalizedPopupKey, popupId),
        title: omoFormatPopupTitle('tutorials'),
        url: omoResolveAppUrl(resolvedUrl),
        mode: 'iframe'
    };
}

function omoResolveSearchPopupRoute(popupId, currentRoute) {
    const normalizedContext = omoNormalizeSearchPopupContext(currentRoute);
    const parsedPopupId = Number(popupId || 0);
    const queryParts = [];
    const pendingState = omoGetSearchPopupPendingState(normalizedContext);
    const jobState = Number.isInteger(parsedPopupId) && parsedPopupId > 0
        ? omoGetSearchPopupJobState(parsedPopupId, normalizedContext)
        : null;
    let resolvedState = jobState;

    if (normalizedContext.oid) {
        queryParts.push(`oid=${encodeURIComponent(normalizedContext.oid)}`);
    }

    if (normalizedContext.cid) {
        queryParts.push(`cid=${encodeURIComponent(normalizedContext.cid)}`);
    }

    if (!resolvedState && pendingState) {
        resolvedState = pendingState;
    }

    if (resolvedState && resolvedState.jobId && resolvedState.jobToken && (!parsedPopupId || resolvedState.jobId === parsedPopupId)) {
        queryParts.push(`restore_job_id=${encodeURIComponent(resolvedState.jobId)}`);
        queryParts.push(`restore_job_token=${encodeURIComponent(resolvedState.jobToken)}`);
    } else if (resolvedState && resolvedState.query !== '') {
        queryParts.push(`q=${encodeURIComponent(resolvedState.query)}`);
        resolvedState.scopes.forEach(function (scopeId) {
            queryParts.push(`scopes[]=${encodeURIComponent(scopeId)}`);
        });
    }

    return {
        key: 'search',
        id: Number.isInteger(parsedPopupId) && parsedPopupId > 0 ? parsedPopupId : null,
        token: omoBuildPopupToken('search', parsedPopupId),
        title: 'Recherche',
        url: omoResolveAppUrl(`/omo/api/search_popup.php${queryParts.length > 0 ? `?${queryParts.join('&')}` : ''}`),
        mode: 'fetch'
    };
}

function omoResolvePopupRoute(popupKey, popupId = null) {
    const normalizedPopupKey = omoNormalizeHashToken(popupKey);
    const parsedPopupId = Number(popupId);
    const currentRoute = typeof parseUrl === 'function'
        ? parseUrl()
        : { oid: null, cid: null };

    if (!normalizedPopupKey) {
        return null;
    }

    if (normalizedPopupKey === 'tutorials' || normalizedPopupKey.indexOf('tutorials/') === 0) {
        return omoResolveTutorialPopupRoute(normalizedPopupKey, parsedPopupId, currentRoute);
    }

    if (normalizedPopupKey === 'search') {
        return omoResolveSearchPopupRoute(parsedPopupId, currentRoute);
    }

    let url = `/popup/${normalizedPopupKey}.php`;
    const queryParts = [];

    if (normalizedPopupKey === 'document-move') {
        url = '/omo/api/documents/move.php';
        if (Number.isInteger(parsedPopupId) && parsedPopupId > 0) {
            queryParts.push(`id=${encodeURIComponent(parsedPopupId)}`);
        }
    } else if (normalizedPopupKey === 'holon-move') {
        url = '/omo/api/holons/move.php';
        if (Number.isInteger(parsedPopupId) && parsedPopupId > 0) {
            queryParts.push(`hid=${encodeURIComponent(parsedPopupId)}`);
        }
    } else if (normalizedPopupKey === 'holon-delete') {
        url = '/omo/api/holons/delete_popup.php';
        if (Number.isInteger(parsedPopupId) && parsedPopupId > 0) {
            queryParts.push(`hid=${encodeURIComponent(parsedPopupId)}`);
        }
    } else if (Number.isInteger(parsedPopupId) && parsedPopupId > 0) {
        queryParts.push(`id=${encodeURIComponent(parsedPopupId)}`);
    }

    if (Number.isInteger(Number(currentRoute.oid)) && Number(currentRoute.oid) > 0) {
        queryParts.push(`oid=${encodeURIComponent(Number(currentRoute.oid))}`);
    }

    if (Number.isInteger(Number(currentRoute.cid)) && Number(currentRoute.cid) > 0) {
        queryParts.push(`cid=${encodeURIComponent(Number(currentRoute.cid))}`);
    }

    if (queryParts.length > 0) {
        url += `?${queryParts.join('&')}`;
    }

    url = omoResolveAppUrl(url);

    return {
        key: normalizedPopupKey,
        id: Number.isInteger(parsedPopupId) && parsedPopupId > 0 ? parsedPopupId : null,
        token: omoBuildPopupToken(normalizedPopupKey, parsedPopupId),
        title: omoFormatPopupTitle(normalizedPopupKey),
        url: url
    };
}

function omoOpenPopupModalFromRoute(popupKey, popupId = null) {
    const popupRoute = omoResolvePopupRoute(popupKey, popupId);

    if (!popupRoute) {
        return;
    }

    if (typeof window.commonTopbarOpenModal !== 'function') {
        return;
    }

    omoPopupModalManaged = true;

    const modal = document.getElementById('commonTopbarModal');
    const body = document.getElementById('commonTopbarModalBody');
    const bodyPopupKey = body ? body.getAttribute('data-omo-popup-key') : null;
    const bodyPopupUrl = body ? body.getAttribute('data-omo-popup-url') : null;
    const hasPopupContent = bodyPopupKey === popupRoute.key;
    const canLiveSync = body && body.getAttribute('data-omo-popup-live-sync') === '1';

    if (!modal || modal.hidden || !hasPopupContent) {
        omoPopupModalSyncing = true;
        window.commonTopbarOpenModal(popupRoute.title, popupRoute.url, popupRoute.mode || 'fetch');
        if (body) {
            body.setAttribute('data-omo-popup-key', popupRoute.key);
            body.setAttribute('data-omo-popup-url', popupRoute.url);
        }
        window.setTimeout(function () {
            omoPopupModalSyncing = false;
        }, 0);
        return;
    }

    if (body) {
        body.setAttribute('data-omo-popup-url', popupRoute.url);
    }

    if (canLiveSync) {
        window.dispatchEvent(new CustomEvent('omo-popup-route-update', {
            detail: {
                popupKey: popupRoute.key,
                popupId: popupRoute.id,
                popupToken: popupRoute.token
            }
        }));
        return;
    }

    if (bodyPopupUrl !== popupRoute.url) {
        omoPopupModalSyncing = true;
        window.commonTopbarOpenModal(popupRoute.title, popupRoute.url, popupRoute.mode || 'fetch');
        if (body) {
            body.setAttribute('data-omo-popup-key', popupRoute.key);
            body.setAttribute('data-omo-popup-url', popupRoute.url);
        }
        window.setTimeout(function () {
            omoPopupModalSyncing = false;
        }, 0);
    }
}

function omoClosePopupModalFromRoute() {
    omoPopupModalManaged = false;

    if (typeof window.commonTopbarCloseModal !== 'function') {
        return;
    }

    const modal = document.getElementById('commonTopbarModal');
    if (!modal || modal.hidden) {
        return;
    }

    omoPopupModalSyncing = true;
    window.commonTopbarCloseModal();
    window.setTimeout(function () {
        omoPopupModalSyncing = false;
    }, 0);
}

function omoOpenFaqHelp() {
    omoSetPopupHashState({
        open: true,
        key: 'faq'
    });
    return true;
}

function omoOpenTutorialHashState(parcoursId = null, missionId = null, options = {}) {
    const resolvedParcoursId = Number(parcoursId);
    const resolvedMissionId = Number(missionId);
    const popupKey = Number.isInteger(resolvedParcoursId) && resolvedParcoursId > 0
        ? `tutorials/parcours/${resolvedParcoursId}`
        : 'tutorials';

    omoSetPopupHashState({
        open: true,
        key: popupKey,
        id: Number.isInteger(resolvedMissionId) && resolvedMissionId > 0 ? resolvedMissionId : null,
        replace: options.replace === true
    });

    return true;
}

function omoOpenTutorialsHelp() {
    return omoOpenTutorialHashState();
}

function omoOpenSearchPopupHashState(query, scopes = [], options = {}) {
    const normalizedQuery = String(query || '').trim();
    const normalizedScopes = omoNormalizeSearchPopupScopes(scopes);
    const rawOptions = options && typeof options === 'object'
        ? options
        : {};
    const route = typeof parseUrl === 'function'
        ? parseUrl()
        : { oid: null, cid: null, hash: null };
    const context = omoNormalizeSearchPopupContext(route);
    const normalizedJobId = Number(rawOptions.jobId || 0);
    const popupId = Number.isInteger(normalizedJobId) && normalizedJobId > 0
        ? normalizedJobId
        : null;

    if (!context.oid || normalizedQuery === '') {
        return false;
    }

    omoSetSearchPopupPendingState(normalizedQuery, normalizedScopes, context);

    const hashState = omoParseHashState(route.hash || null);
    const nextHash = omoBuildHashFromState(
        hashState.routeToken,
        omoBuildPopupToken('search', popupId)
    );

    if ((nextHash || null) === (route.hash || null)) {
        omoOpenPopupModalFromRoute('search', popupId);
        return true;
    }

    omoSetPopupHashState({
        open: true,
        key: 'search',
        id: popupId,
        replace: rawOptions.replace === true
    });

    return true;
}

function omoOpenMemberActionsPopup(userId) {
    const resolvedUserId = Number(userId);

    if (!Number.isInteger(resolvedUserId) || resolvedUserId <= 0) {
        return false;
    }

    omoSetPopupHashState({
        open: true,
        key: 'member-actions',
        id: resolvedUserId
    });

    return true;
}

function omoOpenUserContextPopup(userId, options = {}) {
    const resolvedUserId = Number(userId);
    if (!Number.isInteger(resolvedUserId) || resolvedUserId <= 0) {
        return false;
    }

    omoSetPopupHashState({
        open: true,
        key: 'user',
        id: resolvedUserId,
        replace: options.replace === true
    });

    return true;
}

function omoOpenSearchTutorialResult(parcoursId, missionId = null) {
    const resolvedParcoursId = Number(parcoursId);
    const resolvedMissionId = Number(missionId);
    if (!Number.isInteger(resolvedParcoursId) || resolvedParcoursId <= 0) {
        return false;
    }

    omoClosePopupModalFromRoute();

    return omoOpenTutorialHashState(
        resolvedParcoursId,
        Number.isInteger(resolvedMissionId) && resolvedMissionId > 0 ? resolvedMissionId : null
    );
}

$(document).on('click', '[data-hash]', function (e) {

    e.preventDefault();

    const hash = String($(this).data('hash') || '').trim() || null;
    const { oid, cid } = parseUrl();
    const currentHash = parseUrl().hash;
    const currentHashState = omoParseHashState(currentHash);
    const navigationMode = String($(this).attr('data-navigation-mode') || 'drawer').toLowerCase();

    if (navigationMode === 'panel') {
        navigate(oid, cid, omoBuildHashFromState(null, currentHashState.popupToken));
        return;
    }

    if (currentHashState.routeToken === hash) {
        navigate(oid, cid, omoBuildHashFromState(null, currentHashState.popupToken));
        return;
    }

    navigate(oid, cid, omoBuildHashFromState(hash, currentHashState.popupToken));

});

$(document).on('click', '[data-omo-open-app-picker="1"]', function (e) {
    e.preventDefault();

    if (typeof window.commonTopbarOpenModal !== 'function') {
        return;
    }

    window.commonTopbarOpenModal(
        'Gerer les applications',
        omoResolveAppUrl('api/organization_applications_popup.php'),
        'fetch'
    );
});

$(document).on('click', '[data-omo-personal-space-route-token]', function (e) {
    e.preventDefault();

    const routeToken = String($(this).attr('data-omo-personal-space-route-token') || '').trim();
    const forcedScope = String($(this).attr('data-omo-personal-space-forced-scope') || '').trim().toLowerCase();
    if (routeToken === '' || typeof window.omoOpenDrawerHashState !== 'function') {
        return;
    }

    window.omoOpenDrawerHashState(routeToken, {
        forcedScope: forcedScope
    });
});

$(document).on('click', '[data-omo-personal-space-document-url]', function (e) {
    e.preventDefault();

    const documentUrl = String($(this).attr('data-omo-personal-space-document-url') || '').trim();
    const documentTitle = String($(this).attr('data-omo-personal-space-document-title') || '').trim();
    if (documentUrl === '' || typeof window.omoOpenSearchDocumentResult !== 'function') {
        return;
    }

    window.omoOpenSearchDocumentResult(documentUrl, documentTitle);
});

$(document).on('click', '[data-omo-personal-space-calendar-event-id]', function (e) {
    e.preventDefault();

    const eventId = Number($(this).attr('data-omo-personal-space-calendar-event-id') || 0);
    const holonId = Number($(this).attr('data-omo-personal-space-calendar-holon-id') || 0);
    if (!Number.isInteger(eventId) || eventId <= 0 || typeof window.omoOpenSearchCalendarEventResult !== 'function') {
        return;
    }

    window.omoOpenSearchCalendarEventResult(eventId, holonId);
});

$(document).on('click', '[data-omo-personal-space-user-id]', function (e) {
    e.preventDefault();

    const userId = Number($(this).attr('data-omo-personal-space-user-id') || 0);
    if (!Number.isInteger(userId) || userId <= 0 || typeof window.omoOpenUserContextPopup !== 'function') {
        return;
    }

    window.omoOpenUserContextPopup(userId);
});

$(document)
  .off('click.omoHistoryOpenHolonReference', '[data-omo-history-holon-id]')
  .on('click.omoHistoryOpenHolonReference', '[data-omo-history-holon-id]', function (e) {
      e.preventDefault();

      const holonId = Number($(this).attr('data-omo-history-holon-id') || 0);
      if (!Number.isInteger(holonId) || holonId <= 0 || typeof window.omoOpenSearchStructureResult !== 'function') {
          return;
      }

      window.omoOpenSearchStructureResult(holonId);
  });

$(document).on('click', '[data-omo-cid]', function (e) {

    e.preventDefault();

    if (typeof navigate !== 'function' || typeof parseUrl !== 'function') {
        return;
    }

    const cid = Number($(this).data('omo-cid'));
    const isRoot = String($(this).data('omo-root')) === '1';
    const route = parseUrl();

    if (!isRoot && (!cid || Number.isNaN(cid))) {
        return;
    }

    navigate(route.oid, isRoot ? null : cid, route.hash || null);

});

function updateActiveMenu(hash) {

    // reset global
    $('.menu-item').removeClass('active');

    const hashState = omoParseHashState(hash);
    const route = hashState.routeToken;

    if (!route) {
        return;
    }

    const item = $(`[data-hash="${omoGetMenuHashForRouteToken(route) || ''}"]`);

    if (item.length) {
        item.addClass('active');
    }
}

function navigate(oid, cid = null, hash = null) {

    const url = buildOmoUrl(oid, cid, hash);
    history.pushState({}, '', url);

    handleRoute();
}

function parseUrl() {
    if (omoIsShareMode()) {
        const hash = window.location.hash.replace('#', '');
        const params = new URLSearchParams(window.location.search || '');
        const oid = getResolvedOrganizationId();
        const cidParam = params.get('cid');
        const fallbackCid = window.omoConfig && window.omoConfig.initialCid
            ? String(window.omoConfig.initialCid)
            : null;
        const cid = cidParam || fallbackCid || null;

        return { oid, cid, hash };
    }

    const path = getNormalizedOmoPath();
    const hash = window.location.hash.replace('#', '');

    let oid = getResolvedOrganizationId();
    let cid = null;

    const currentMatch = path.match(/\/omo(?:\/c\/(\d+))?$/);
    const legacyMatch = path.match(/\/omo\/o\/(\d+)(?:\/c\/(\d+))?$/);

    if (currentMatch) {
        cid = currentMatch[1] || null;
    } else if (legacyMatch) {
        if (oid === null) {
            oid = Number(legacyMatch[1]);
        }

        cid = legacyMatch[2] || null;
    }

    return { oid, cid, hash };
}

let currentState = {
    oid: null,
    cid: null,
    hash: null,
    routeToken: null,
    popupToken: null
};

const omoStructureViewTargets = Object.create(null);

function omoFocusStructureNode(cid = null, options = {}) {
    window.dispatchEvent(new CustomEvent('omo-structure-focus', {
        detail: {
            cid: cid === null || cid === undefined || cid === '' ? null : Number(cid),
            quickZoom: Boolean(options.quickZoom)
        }
    }));
}

window.omoRegisterStructureViewTarget = function (key, handlers) {
    const normalizedKey = String(key || '').trim();
    if (!normalizedKey) {
        return function () {};
    }

    omoStructureViewTargets[normalizedKey] = handlers && typeof handlers === 'object'
        ? handlers
        : {};

    return function () {
        delete omoStructureViewTargets[normalizedKey];
    };
};

window.omoReloadStructureAndFocus = function (nodeId, options = {}) {
    const cid = nodeId === null || nodeId === undefined || nodeId === '' ? null : Number(nodeId);
    const quickZoom = Boolean(options && options.quickZoom);
    const tasks = Object.keys(omoStructureViewTargets).map(function (key) {
        const target = omoStructureViewTargets[key];

        if (!target || typeof target.reloadAndFocus !== 'function') {
            return Promise.resolve(null);
        }

        try {
            return Promise.resolve(target.reloadAndFocus(Number.isNaN(cid) ? null : cid, options));
        } catch (error) {
            return Promise.reject(error);
        }
    });

    if (!tasks.length) {
        window.dispatchEvent(new CustomEvent('omo-structure-refresh', {
            detail: {
                cid: Number.isNaN(cid) ? null : cid,
                quickZoom: quickZoom
            }
        }));

        return Promise.resolve();
    }

    return Promise.all(tasks.map(function (task) {
        return task.catch(function () {
            return null;
        });
    })).then(function () {
        return null;
    });
};

window.omoGetCurrentStructureHolonId = function () {
    const targetKeys = Object.keys(omoStructureViewTargets);

    for (let index = 0; index < targetKeys.length; index += 1) {
        const target = omoStructureViewTargets[targetKeys[index]];
        if (!target || typeof target.getCurrentHolonId !== 'function') {
            continue;
        }

        const holonId = Number(target.getCurrentHolonId());
        if (Number.isInteger(holonId) && holonId > 0) {
            return holonId;
        }
    }

    const route = typeof parseUrl === 'function' ? parseUrl() : null;
    const routeCid = route && route.cid !== null && route.cid !== undefined && route.cid !== ''
        ? Number(route.cid)
        : 0;

    if (Number.isInteger(routeCid) && routeCid > 0) {
        return routeCid;
    }

    const rootHolonId = Number(window.omoConfig && window.omoConfig.rootHolonId ? window.omoConfig.rootHolonId : 0);
    return Number.isInteger(rootHolonId) && rootHolonId > 0 ? rootHolonId : 0;
};

window.omoStructureHandleDrawerOpen = function (options = {}) {
    const cid = Object.prototype.hasOwnProperty.call(options, 'cid') ? Number(options.cid) : null;

    window.dispatchEvent(new CustomEvent('omo-structure-drawer-open', {
        detail: {
            cid: Number.isNaN(cid) ? null : cid
        }
    }));
};

function handleRoute() {

    const { oid, cid, hash } = parseUrl();
    const hashState = omoParseHashState(hash);
    const routeToken = hashState.routeToken;
    const popupToken = hashState.popupToken;
    const popupKey = hashState.popupKey;
    const popupId = hashState.popupId;
    const previousState = currentState;

    if (!Number.isInteger(Number(oid)) || Number(oid) <= 0) {

        const errorMessage = '<div class="error">Organisation introuvable pour ce sous-domaine.</div>';

        omoSetLeftPanelHtml(errorMessage);
        $('#panel-right').html(errorMessage);
        closeAllDrawers();
        omoClosePopupModalFromRoute();
        updateActiveMenu(null);

        currentState = { oid, cid, hash, routeToken, popupToken };
        return;
    }

    // 👉 détection des changements
    omoEnsurePopupBootstrapState(oid, cid, routeToken, popupKey, popupId);

    const organizationChanged = (oid !== previousState.oid);
    const cidChanged = (cid !== previousState.cid);
    const hashChanged = (hash !== previousState.hash);
    const routeChanged = (routeToken !== previousState.routeToken);
    const popupChanged = (popupToken !== previousState.popupToken);

    // 👉 mise à jour state
    currentState = { oid, cid, hash, routeToken, popupToken };

    // 👉 menu actif
    updateActiveMenu(hash);

    const menuConfig = routeToken ? getSidebarMenuConfig(routeToken, oid, cid) : null;
    const activeDrawerId = routeToken
        ? omoNormalizeDrawerId(menuConfig && menuConfig.drawer ? menuConfig.drawer : `drawer_${routeToken}`)
        : null;
    const activeBaseUrl = routeToken
        ? (menuConfig && menuConfig.url ? menuConfig.url : `api/${routeToken}/index.php`)
        : null;
    const activeDrawerUrl = menuConfig && menuConfig.resolvedUrl
        ? menuConfig.resolvedUrl
        : (activeBaseUrl ? buildDrawerUrl(activeBaseUrl, oid, cid, menuConfig && menuConfig.routeOptions ? menuConfig.routeOptions : {}) : null);
    const activeForcedScope = omoNormalizeDrawerForcedScope(
        menuConfig && menuConfig.routeOptions ? menuConfig.routeOptions.forcedScope : ''
    );
    const routeWillOpenDrawer = Boolean(routeToken && activeDrawerId && activeDrawerUrl);

    // 🧱 1. Charger panels seulement si contexte change
    if (organizationChanged) {

        let leftUrl = `api/getOrg.php?oid=${oid}`;
        if (cid) leftUrl += `&cid=${cid}`;

        loadContent(omoGetLeftPanelContentSelector(), leftUrl);

        omoRefreshMainRightPanel(oid, cid, {
            routeWillOpenDrawer: routeWillOpenDrawer
        });
    } else if (cidChanged) {
        let leftUrl = `api/getOrg.php?oid=${oid}`;
        if (cid) leftUrl += `&cid=${cid}`;

        loadContent(omoGetLeftPanelContentSelector(), leftUrl);
        if (!omoIsShareMode()) {
            omoRefreshMainRightPanel(oid, cid, {
                routeWillOpenDrawer: routeWillOpenDrawer
            });
        }
        if (omoIsShareMode() && omoGetMenuHashForRouteToken(routeToken) !== 'structure') {
            omoFocusStructureNode(cid);
        }
    }

    const activeMenuHash = routeToken ? omoGetMenuHashForRouteToken(routeToken) : null;
    const previousMenuHash = previousState.routeToken ? omoGetMenuHashForRouteToken(previousState.routeToken) : null;
    const isStructureRoute = activeMenuHash === 'structure';
    const drawerHandledByContextChange = organizationChanged || (cidChanged && !isStructureRoute);
    const decisionRoute = omoParseDecisionRouteToken(routeToken);
    const previousDecisionRoute = omoParseDecisionRouteToken(previousState.routeToken);
    const calendarEventRoute = omoParseCalendarEventRouteToken(routeToken);
    const previousCalendarEventRoute = omoParseCalendarEventRouteToken(previousState.routeToken);
    const documentRoute = omoParseDocumentRouteToken(routeToken);
    const previousDocumentRoute = omoParseDocumentRouteToken(previousState.routeToken);
    const isInSpecialDrawerOnlyRouteChange = !drawerHandledByContextChange
        && routeChanged
        && !popupChanged
        && activeMenuHash !== null
        && activeMenuHash === previousMenuHash
        && (
            activeMenuHash === 'decision'
            || activeMenuHash === 'calendar'
            || activeMenuHash === 'documents'
        );

    if (isInSpecialDrawerOnlyRouteChange && activeMenuHash === 'decision') {
        window.dispatchEvent(new CustomEvent('omo-decisions-route-change', {
            detail: {
                decisionId: decisionRoute ? Number(decisionRoute.decisionId) : 0,
                mode: decisionRoute && decisionRoute.mode ? String(decisionRoute.mode) : 'default',
                previousDecisionId: previousDecisionRoute ? Number(previousDecisionRoute.decisionId) : 0,
                previousMode: previousDecisionRoute && previousDecisionRoute.mode ? String(previousDecisionRoute.mode) : 'default',
                routeToken: routeToken,
                previousRouteToken: previousState.routeToken || null
            }
        }));
    }

    if (isInSpecialDrawerOnlyRouteChange && activeMenuHash === 'calendar') {
        window.dispatchEvent(new CustomEvent('omo-calendar-route-change', {
            detail: {
                eventId: calendarEventRoute ? Number(calendarEventRoute.eventId) : 0,
                previousEventId: previousCalendarEventRoute ? Number(previousCalendarEventRoute.eventId) : 0,
                routeToken: routeToken,
                previousRouteToken: previousState.routeToken || null
            }
        }));
    }

    if (isInSpecialDrawerOnlyRouteChange && activeMenuHash === 'documents') {
        const documentRouteDetail = {
            documentId: documentRoute ? Number(documentRoute.documentId) : 0,
            mode: documentRoute && documentRoute.mode ? String(documentRoute.mode) : 'detail',
            forcedScope: activeForcedScope,
            previousDocumentId: previousDocumentRoute ? Number(previousDocumentRoute.documentId) : 0,
            previousMode: previousDocumentRoute && previousDocumentRoute.mode ? String(previousDocumentRoute.mode) : 'detail',
            routeToken: routeToken,
            previousRouteToken: previousState.routeToken || null
        };
        let documentsRouteHandled = false;
        if (typeof window.omoHandleDocumentsRouteChange === 'function') {
            try {
                documentsRouteHandled = window.omoHandleDocumentsRouteChange(documentRouteDetail) === true;
            } catch (error) {
                documentsRouteHandled = false;
            }
        }

        if (!documentsRouteHandled) {
            window.dispatchEvent(new CustomEvent('omo-documents-route-change', {
                detail: documentRouteDetail
            }));
        }
    }

    if (drawerHandledByContextChange) {
        resetDrawers(activeDrawerId);

        if (routeToken && activeDrawerId && activeDrawerUrl) {
            if (!refreshDrawer(activeDrawerId, activeDrawerUrl)) {
                openDrawer(activeDrawerId, activeDrawerUrl);
            }
        }
    } else if (cidChanged && isStructureRoute) {
        omoFocusStructureNode(cid);
    }

    // 🧩 2. Gérer les drawers (modules)
    if (!drawerHandledByContextChange && hashChanged && routeChanged && !isInSpecialDrawerOnlyRouteChange) {

        if (routeToken && activeDrawerId && activeDrawerUrl) {
            openDrawer(activeDrawerId, activeDrawerUrl);
        } else {
            closeAllDrawers();
        }
    }

    if (!routeWillOpenDrawer) {
        omoEnsureMainRightPanelCurrent(oid, cid);
    }

    if (popupToken && popupKey) {
        omoOpenPopupModalFromRoute(popupKey, popupId);
    } else if (popupChanged || hashChanged) {
        omoClosePopupModalFromRoute();
    }

    window.dispatchEvent(new CustomEvent('omo-structure-route-sync', {
        detail: {
            oid: Number(oid),
            cid: cid === null || cid === undefined || cid === '' ? null : Number(cid),
            hash: hash || null,
            routeToken: routeToken || null,
            organizationChanged: organizationChanged,
            cidChanged: cidChanged
        }
    }));

    if (routeToken) {
        omoClearPendingDrawerRouteOptions(routeToken);
    }
}

function activateMenu(hash) {

    $('.menu-item').removeClass('active');

    if (!hash) return;

    $(`[data-hash="${hash}"]`).addClass('active');
}

let omoBrowserRouteSyncPending = false;

function scheduleBrowserRouteSync() {
    if (omoBrowserRouteSyncPending) {
        return;
    }

    omoBrowserRouteSyncPending = true;
    window.setTimeout(function () {
        omoBrowserRouteSyncPending = false;
        handleRoute();
    }, 0);
}

$(document).ready(function () {
    canonicalizeOmoRootPath();
    handleRoute();
    if (typeof window.omoInitSiteUpdateCheck === 'function' && window.omoConfig && window.omoConfig.siteUpdate) {
        window.omoInitSiteUpdateCheck(window.omoConfig.siteUpdate);
    }
    window.setTimeout(omoMaybeOpenPatreonWelcomeModal, 300);
});

$(window).on('hashchange', scheduleBrowserRouteSync);
$(window).on('popstate', scheduleBrowserRouteSync);

window.addEventListener('common-topbar-modal-close', function () {
    if (omoPopupModalSyncing || !omoPopupModalManaged) {
        return;
    }

    const hashState = omoParseHashState(parseUrl().hash);

    if (!hashState.popupToken) {
        return;
    }

    omoSetPopupHashState({
        open: false
    });
});

let tooltip = $('#tooltip');
let tooltipDelay;
let tooltipTarget = null;

function openTooltip(text, event, targetId = null) {
  tooltipTarget = targetId;

  tooltip
    .text(text)
    .css({
      top: event.clientY + 12 + "px",
      left: event.clientX + 12 + "px"
    })
    .addClass("visible");
}

function moveTooltip(event) {
  if (!tooltip.hasClass("visible")) return;

  tooltip.css({
    top: event.clientY + 12 + "px",
    left: event.clientX + 12 + "px"
  });
}

function closeTooltip() {
  tooltip.removeClass("visible");
  tooltipTarget = null;
}

$(document).on('mouseenter', '[data-tooltip]', function (e) {
  openTooltip($(this).data('tooltip'), e, this);
});

$(document).on('mousemove', '[data-tooltip]', function (e) {
  moveTooltip(e);
});

$(document).on('mouseleave', '[data-tooltip]', function () {
  closeTooltip();
});

$(document).on('click', '[data-view]', function () {

    const view = $(this).data('view');

    omoSetAppView(view, { allowToggleMenu: true });

});

$(document).ready(function () {
    omoBindMobileSwipeNavigation();
});

const OMO_DRIVER_SCRIPT_URL = 'https://cdn.jsdelivr.net/npm/driver.js@latest/dist/driver.js.iife.js';
const OMO_DRIVER_STYLE_URL = 'https://cdn.jsdelivr.net/npm/driver.js@latest/dist/driver.css';
const OMO_TOUR_STEPS_URL = '/omo/assets/js/tour-steps.js';

let omoTourAssetsPromise = null;
let omoActiveTour = null;

function omoLoadScript(src, isReady) {
    return new Promise(function (resolve, reject) {
        if (typeof isReady === 'function' && isReady()) {
            resolve();
            return;
        }

        const existing = document.querySelector('script[data-omo-src="' + src + '"]');
        if (existing) {
            if (existing.dataset.loaded === 'true') {
                resolve();
                return;
            }
            existing.addEventListener('load', function () {
                resolve();
            }, { once: true });
            existing.addEventListener('error', function () {
                reject(new Error('Impossible de charger ' + src));
            }, { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.dataset.omoSrc = src;

        script.addEventListener('load', function () {
            script.dataset.loaded = 'true';
            resolve();
        }, { once: true });

        script.addEventListener('error', function () {
            reject(new Error('Impossible de charger ' + src));
        }, { once: true });

        document.head.appendChild(script);
    });
}

function omoLoadStyle(href) {
    if (document.querySelector('link[data-omo-href="' + href + '"]')) {
        return Promise.resolve();
    }

    return new Promise(function (resolve, reject) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        link.dataset.omoHref = href;

        link.addEventListener('load', function () {
            resolve();
        }, { once: true });

        link.addEventListener('error', function () {
            reject(new Error('Impossible de charger ' + href));
        }, { once: true });

        document.head.appendChild(link);
    });
}

function omoEnsureTourAssets() {
    if (omoTourAssetsPromise) {
        return omoTourAssetsPromise;
    }

    omoTourAssetsPromise = Promise.all([
        omoLoadStyle(OMO_DRIVER_STYLE_URL),
        omoLoadScript(OMO_DRIVER_SCRIPT_URL, function () {
            return !!(window.driver && window.driver.js && typeof window.driver.js.driver === 'function');
        }),
        omoLoadScript(OMO_TOUR_STEPS_URL, function () {
            return typeof window.omoGetTourStepDefinitions === 'function';
        }),
    ]).then(function () {
        if (!window.driver || !window.driver.js || typeof window.driver.js.driver !== 'function') {
            throw new Error('Driver.js est introuvable après chargement.');
        }

        if (typeof window.omoGetTourStepDefinitions !== 'function') {
            throw new Error('Les étapes de visite OMO sont introuvables.');
        }
    }).catch(function (error) {
        omoTourAssetsPromise = null;
        throw error;
    });

    return omoTourAssetsPromise;
}

function omoShowTourMessage(title, message) {
    if (typeof window.commonTopbarOpenModal === 'function') {
        window.commonTopbarOpenModal(
            title,
            '<div class="common-help-list"><div class="common-help-card"><p>' + message + '</p></div></div>',
            'html'
        );
        return;
    }

    window.alert(message.replace(/<[^>]+>/g, ''));
}

function omoGetTourElement(stepDefinition) {
    const selectors = Array.isArray(stepDefinition.selectors)
        ? stepDefinition.selectors
        : [stepDefinition.selector];

    for (let index = 0; index < selectors.length; index += 1) {
        const selector = selectors[index];

        if (!selector) {
            continue;
        }

        const element = document.querySelector(selector);
        const rect = element ? element.getBoundingClientRect() : null;

        if (element && rect && rect.width > 0 && rect.height > 0) {
            return element;
        }
    }

    return null;
}

function omoGetTourSteps() {
    const definitions = window.omoGetTourStepDefinitions({
        isMobile: window.matchMedia('(max-width: 860px)').matches,
        oid: getResolvedOrganizationId(),
        currentPath: window.location.pathname,
    }) || [];

    return definitions.map(function (definition) {
        const element = omoGetTourElement(definition);

        if (!element) {
            return null;
        }

        return {
            element: element,
            popover: definition.popover || {},
        };
    }).filter(Boolean);
}

function omoStartGuidedTour() {
    omoEnsureTourAssets()
        .then(function () {
            window.setTimeout(function () {
                const steps = omoGetTourSteps();

                if (!steps.length) {
                    omoShowTourMessage(
                        'Visite guidée',
                        'Aucun élément de la visite guidée n’est disponible pour le moment.'
                    );
                    return;
                }

                if (omoActiveTour && typeof omoActiveTour.destroy === 'function') {
                    omoActiveTour.destroy();
                }

                omoActiveTour = window.driver.js.driver({
                    showProgress: true,
                    allowClose: true,
                    steps: steps,
                });

                omoActiveTour.drive();
            }, 250);
        })
        .catch(function (error) {
            console.error(error);
            omoShowTourMessage(
                'Visite guidée',
                'Impossible de charger la visite guidée pour le moment.'
            );
        });
}

function omoGetTopbarSearchStructureScope() {
    const structureItem = $('#menu_sidebar .menu-item[data-hash="structure"]').first();
    if (!structureItem.length) {
        return null;
    }

    const structureLabelNode = structureItem.find('.label').first();
    const structureLabel = structureLabelNode && structureLabelNode.length
        ? String(structureLabelNode.text() || '').trim()
        : 'Structure';
    const currentRoute = parseUrl();
    const currentHashState = omoParseHashState(currentRoute.hash || null);
    const currentRouteToken = omoGetMenuHashForRouteToken(currentHashState.routeToken || null);

    return {
        id: '__structure__',
        label: structureLabel || 'Structure',
        checked: !currentHashState.routeToken || currentRouteToken === 'structure'
    };
}

function omoGetTopbarSearchHelpScope(helpKey, popupKeyMatcher) {
    const helpItem = omoGetTopbarHelpItem(helpKey);
    if (!helpItem) {
        return null;
    }

    const currentRoute = parseUrl();
    const currentHashState = omoParseHashState(currentRoute.hash || null);
    const popupKey = String(currentHashState.popupKey || '').trim().toLowerCase();
    let isChecked = false;

    if (typeof popupKeyMatcher === 'function') {
        isChecked = popupKeyMatcher(popupKey);
    } else {
        isChecked = popupKey === String(helpKey || '').trim().toLowerCase();
    }

    return {
        id: String(helpKey || '').trim().toLowerCase(),
        label: String(helpItem.label || helpItem.title || helpKey).trim(),
        checked: isChecked
    };
}

function omoGetTopbarSearchScopes() {
    const currentRoute = parseUrl();
    const currentHashState = omoParseHashState(currentRoute.hash || null);
    const currentRouteToken = omoGetMenuHashForRouteToken(currentHashState.routeToken || null);
    const scopes = [];
    const structureScope = omoGetTopbarSearchStructureScope();
    if (structureScope) {
        scopes.push(structureScope);
    }
    const searchableRouteTokens = {
        team: true,
        calendar: true,
        documents: true,
        decision: true
    };

    document.querySelectorAll('#menu_sidebar .menu-item[data-hash][data-navigation-mode]').forEach(function (item) {
        if (item.hasAttribute('data-omo-open-app-picker')) {
            return;
        }

        const routeToken = String(item.getAttribute('data-hash') || '').trim();
        const navigationMode = String(item.getAttribute('data-navigation-mode') || '').trim().toLowerCase();
        const labelNode = item.querySelector('.label');
        const label = labelNode ? String(labelNode.textContent || '').trim() : routeToken;

        if (!routeToken || navigationMode !== 'drawer' || !label || !searchableRouteTokens[routeToken]) {
            return;
        }

        scopes.push({
            id: routeToken,
            label: label,
            checked: currentRouteToken === routeToken
        });
    });

    if (scopes.some(function (scope) { return scope.id === 'documents'; })
        && !scopes.some(function (scope) { return scope.id === 'pv'; })) {
        scopes.push({
            id: 'pv',
            label: 'PV',
            checked: false
        });
    }

    const faqScope = omoGetTopbarSearchHelpScope('faq');
    if (faqScope) {
        scopes.push(faqScope);
    }

    const tutorialsScope = omoGetTopbarSearchHelpScope('tutorials', function (popupKey) {
        return popupKey === 'tutorials' || popupKey.indexOf('tutorials/') === 0;
    });
    if (tutorialsScope) {
        scopes.push(tutorialsScope);
    }

    if (!scopes.some(function (scope) { return scope.checked; }) && scopes.length > 0) {
        scopes[0].checked = true;
    }

    return scopes;
}

function omoHandleTopbarSearch(query, config, searchState) {
    const selectedScopeIds = Array.isArray(searchState && searchState.scopes)
        ? searchState.scopes.map(function (scope) {
            return String(scope && scope.id ? scope.id : '').trim();
        }).filter(function (scopeId) {
            return scopeId !== '';
        })
        : [];
    const trimmedQuery = String(query || '').trim();

    if (trimmedQuery.length < 2) {
        if (typeof window.commonTopbarOpenModal === 'function') {
            window.commonTopbarOpenModal(
                'Recherche',
                '<div class="common-help-list"><div class="common-help-card"><p>Saisissez au moins 2 caracteres pour lancer la recherche.</p></div></div>',
                'html'
            );
        }
        return true;
    }

    if (selectedScopeIds.length === 0) {
        if (typeof window.commonTopbarOpenModal === 'function') {
            window.commonTopbarOpenModal(
                'Recherche',
                '<div class="common-help-list"><div class="common-help-card"><p>Choisissez au moins un module de recherche.</p></div></div>',
                'html'
            );
        }
        return true;
    }

    return omoOpenSearchPopupHashState(trimmedQuery, selectedScopeIds);
}

function omoOpenSearchStructureResult(holonId) {
    const resolvedHolonId = Number(holonId);
    if (!Number.isInteger(resolvedHolonId) || resolvedHolonId <= 0) {
        return false;
    }

    omoClosePopupModalFromRoute();

    const route = parseUrl();
    navigate(route.oid, resolvedHolonId, null);

    window.setTimeout(function () {
        omoFocusStructureNode(resolvedHolonId, { quickZoom: true });
    }, 180);

    return true;
}

function omoOpenSearchUserResult(userId) {
    const resolvedUserId = Number(userId);
    if (!Number.isInteger(resolvedUserId) || resolvedUserId <= 0) {
        return false;
    }

    omoClosePopupModalFromRoute();

    return omoOpenUserContextPopup(resolvedUserId);
}

function omoOpenSearchDocumentResult(documentUrl, title) {
    const resolvedUrl = String(documentUrl || '').trim();
    if (resolvedUrl === '') {
        return false;
    }

    let parsedUrl = null;
    try {
        parsedUrl = new URL(omoResolveAppUrl(resolvedUrl), omoGetUrlResolutionBase());
    } catch (error) {
        parsedUrl = null;
    }

    const documentId = parsedUrl ? Number(parsedUrl.searchParams.get('id') || 0) : 0;
    const routeToken = Number.isInteger(documentId) && documentId > 0
        ? omoBuildDocumentRouteToken(documentId, 'detail')
        : null;
    const route = parseUrl();
    const currentOid = Number(route.oid);
    const parsedOid = parsedUrl ? Number(parsedUrl.searchParams.get('oid') || 0) : 0;
    const parsedCid = parsedUrl ? Number(parsedUrl.searchParams.get('cid') || 0) : 0;
    const targetOid = Number.isInteger(parsedOid) && parsedOid > 0
        ? parsedOid
        : (Number.isInteger(currentOid) && currentOid > 0 ? currentOid : null);
    const targetCid = Number.isInteger(parsedCid) && parsedCid > 0
        ? parsedCid
        : null;

    if (routeToken && Number.isInteger(targetOid) && targetOid > 0) {
        omoClosePopupModalFromRoute();

        navigate(targetOid, targetCid, routeToken);
        return true;
    }

    if (typeof window.commonTopbarOpenModal !== 'function') {
        return false;
    }

    omoClosePopupModalFromRoute();

    window.commonTopbarOpenModal(
        String(title || 'Document'),
        omoResolveAppUrl(resolvedUrl),
        'fetch'
    );

    return true;
}

function omoOpenSearchDecisionResult(decisionId, holonId) {
    const decisionRouteToken = omoBuildDecisionRouteToken(decisionId);
    if (!decisionRouteToken) {
        return false;
    }

    omoClosePopupModalFromRoute();

    const route = parseUrl();
    if (!Number.isInteger(Number(route.oid)) || Number(route.oid) <= 0) {
        return false;
    }

    const resolvedHolonId = Number(holonId);
    const targetCid = Number.isInteger(resolvedHolonId) && resolvedHolonId > 0
        ? resolvedHolonId
        : null;

    navigate(route.oid, targetCid, decisionRouteToken);
    return true;
}

function omoOpenSearchCalendarEventResult(eventId, holonId) {
    const calendarEventRouteToken = omoBuildCalendarEventRouteToken(eventId);
    if (!calendarEventRouteToken) {
        return false;
    }

    omoClosePopupModalFromRoute();

    const route = parseUrl();
    if (!Number.isInteger(Number(route.oid)) || Number(route.oid) <= 0) {
        return false;
    }

    const resolvedHolonId = Number(holonId);
    const targetCid = Number.isInteger(resolvedHolonId) && resolvedHolonId > 0
        ? resolvedHolonId
        : null;

    navigate(route.oid, targetCid, calendarEventRouteToken);
    return true;
}

function omoExecuteFetchedScripts(container) {
    if (!container) {
        return;
    }

    const scripts = Array.from(container.querySelectorAll('script'));
    const containsDecisionRoot = !!container.querySelector('#omo-decisions-root');

    if (containsDecisionRoot) {
        omoTraceDecisionLoad('executeScripts:start', {
            scriptCount: scripts.length,
            executableScriptCount: scripts.filter(function (script) {
                return omoIsExecutableScriptTag(script);
            }).length
        });
    }

    scripts.forEach(function (script, index) {
        if (!omoIsExecutableScriptTag(script)) {
            return;
        }

        const executableScript = document.createElement('script');
        const sourceUrl = String(script.getAttribute('src') || '').trim();

        if (containsDecisionRoot) {
            omoTraceDecisionLoad('executeScripts:script', {
                index: index,
                sourceUrl: sourceUrl || 'inline',
                inlineLength: sourceUrl === '' ? String(script.textContent || '').length : 0
            });
        }

        Array.from(script.attributes).forEach(function (attribute) {
            executableScript.setAttribute(attribute.name, attribute.value);
        });
        if (sourceUrl !== '') {
            executableScript.async = false;
        }
        executableScript.text = script.textContent || '';
        document.body.appendChild(executableScript);
        document.body.removeChild(executableScript);
    });
}

function omoReplaceFetchedPanelRoot(options = {}) {
    const rootSelector = String(options.rootSelector || '').trim();
    const url = String(options.url || '').trim();
    const currentRoot = options.currentRoot || (rootSelector ? document.querySelector(rootSelector) : null);
    const setLoadingState = typeof options.setLoadingState === 'function'
        ? options.setLoadingState
        : null;
    const beforeReplace = typeof options.beforeReplace === 'function'
        ? options.beforeReplace
        : null;

    if (!rootSelector || !url || !currentRoot || !currentRoot.parentNode) {
        return Promise.reject(new Error('omo_panel_reload_invalid'));
    }

    if (setLoadingState) {
        setLoadingState(true);
    }

    const resolvedUrl = typeof window.omoResolveAppUrl === 'function'
        ? window.omoResolveAppUrl(url)
        : url;

    return fetch(resolvedUrl, {
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        cache: 'no-store'
    })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('omo_panel_reload_failed');
            }

            return response.text();
        })
        .then(function (html) {
            const temp = document.createElement('div');
            temp.innerHTML = html;

            const nextRoot = temp.querySelector(rootSelector);
            if (!nextRoot || !currentRoot.parentNode) {
                throw new Error('omo_panel_reload_invalid');
            }

            // Some module bootstraps live next to the replaced root instead of inside it.
            // Re-run scripts from the full fetched fragment so dynamic reloads keep working.
            const scriptSource = temp.cloneNode(true);

            if (beforeReplace) {
                beforeReplace(currentRoot);
            }

            currentRoot.parentNode.replaceChild(nextRoot, currentRoot);
            omoExecuteFetchedScripts(scriptSource);
            return nextRoot;
        })
        .finally(function () {
            if (setLoadingState) {
                setLoadingState(false);
            }
        });
}

window.omoRefreshSidebar = omoRefreshSidebar;
window.omoResetMainRightPanel = omoResetMainRightPanel;
window.omoInvalidateMainRightPanel = omoInvalidateMainRightPanel;
window.omoRefreshMainRightPanel = omoRefreshMainRightPanel;
window.omoMaybeOpenPatreonWelcomeModal = omoMaybeOpenPatreonWelcomeModal;
window.omoOpenMemberActionsPopup = omoOpenMemberActionsPopup;
window.omoNormalizeRouteCid = omoNormalizeRouteCid;
window.omoBuildDecisionRouteToken = omoBuildDecisionRouteToken;
window.omoBuildCalendarEventRouteToken = omoBuildCalendarEventRouteToken;
window.omoOpenSearchCalendarEventResult = omoOpenSearchCalendarEventResult;
window.omoOpenSearchDecisionResult = omoOpenSearchDecisionResult;
window.omoBuildDocumentRouteToken = omoBuildDocumentRouteToken;
window.omoOpenSearchDocumentResult = omoOpenSearchDocumentResult;
window.omoOpenSearchTutorialResult = omoOpenSearchTutorialResult;
window.omoOpenSearchPopupHashState = omoOpenSearchPopupHashState;
window.omoOpenTutorialsHelp = omoOpenTutorialsHelp;
window.omoOpenSearchStructureResult = omoOpenSearchStructureResult;
window.omoOpenSearchUserResult = omoOpenSearchUserResult;
window.omoRegisterSearchPopupJobState = omoRegisterSearchPopupJobState;
window.omoOpenUserContextPopup = omoOpenUserContextPopup;
window.omoReplaceFetchedPanelRoot = omoReplaceFetchedPanelRoot;
window.omoResolveAppUrl = omoResolveAppUrl;
window.omoIsShareMode = omoIsShareMode;
window.omoBuildHashFromState = omoBuildHashFromState;
window.omoBroadcastStructureAvailability = omoBroadcastStructureAvailability;
window.omoSyncStructureAvailabilityFromSidebar = omoSyncStructureAvailabilityFromSidebar;
window.omoGetExternalPanelDrawerContext = omoGetExternalPanelDrawerContext;
window.omoRefreshExternalPanelDrawerHost = omoRefreshExternalPanelDrawerHost;
window.omoCloseExternalPanelDrawer = omoCloseExternalPanelDrawer;
window.omoOpenExternalPanelDrawer = omoOpenExternalPanelDrawer;
window.omoOpenExternalRouteDrawer = omoOpenExternalRouteDrawer;
window.omoPeekPersistentExternalPanelDrawer = omoPeekPersistentExternalPanelDrawer;
window.omoParsePopupHashState = function () {
    return omoParseHashState(parseUrl().hash);
};
window.omoNavigate = navigate;
window.omoSetDrawerHashState = omoSetDrawerHashState;
window.omoOpenDrawerHashState = function (routeToken, options = {}) {
    const normalizedRouteToken = omoNormalizeHashToken(routeToken);
    const forcedScope = omoNormalizeDrawerForcedScope(options.forcedScope);
    const route = typeof parseUrl === 'function'
        ? parseUrl()
        : { oid: null, cid: null, hash: null };
    const hashState = omoParseHashState(route.hash || null);

    if (forcedScope !== '' && normalizedRouteToken) {
        omoSetPendingDrawerRouteOptions(normalizedRouteToken, {
            forcedScope: forcedScope
        });
    } else {
        omoClearPendingDrawerRouteOptions(normalizedRouteToken);
    }

    if (
        normalizedRouteToken === 'documents'
        && forcedScope !== ''
        && typeof window.omoSetDocumentsScope === 'function'
    ) {
        window.omoSetDocumentsScope(forcedScope);
    }

    if (normalizedRouteToken && hashState.routeToken === normalizedRouteToken) {
        if (
            normalizedRouteToken === 'documents'
            && forcedScope !== ''
            && typeof window.omoSetDocumentsScope === 'function'
        ) {
            omoClearPendingDrawerRouteOptions(normalizedRouteToken);
            return;
        }

        const menuConfig = getSidebarMenuConfig(normalizedRouteToken, route.oid, route.cid, {
            forcedScope: forcedScope
        });
        const drawerId = omoNormalizeDrawerId(menuConfig && menuConfig.drawer ? menuConfig.drawer : `drawer_${normalizedRouteToken}`);
        const resolvedUrl = menuConfig && menuConfig.resolvedUrl
            ? menuConfig.resolvedUrl
            : (menuConfig && menuConfig.url ? buildDrawerUrl(menuConfig.url, route.oid, route.cid, menuConfig.routeOptions || {}) : '');

        if (drawerId && resolvedUrl) {
            if (!refreshDrawer(drawerId, resolvedUrl)) {
                openDrawer(drawerId, resolvedUrl);
            }
            omoClearPendingDrawerRouteOptions(normalizedRouteToken);
            return;
        }
    }

    omoSetDrawerHashState({
        open: true,
        routeToken: routeToken,
        replace: options.replace === true
    });
};
window.omoSetPopupHashState = omoSetPopupHashState;
window.omoOpenPopupHashState = function (key, id, options = {}) {
    if (typeof id === 'object' && id !== null) {
        options = id;
        id = null;
    }

    omoSetPopupHashState({
        open: true,
        key: key,
        id: id,
        replace: options.replace === true
    });
};
window.omoParseFaqHashState = function () {
    const hashState = omoParseHashState(parseUrl().hash);

    return {
        faqToken: hashState.popupKey === 'faq' ? hashState.popupToken : null,
        faqId: hashState.popupKey === 'faq' ? hashState.popupId : null
    };
};
window.omoSetFaqHashState = function (options = {}) {
    omoSetPopupHashState({
        open: options.open,
        key: 'faq',
        id: options.id,
        replace: options.replace === true
    });
};
window.omoOpenFaqHashState = function (id, options = {}) {
    if (typeof id === 'object' && id !== null) {
        options = id;
        id = null;
    }

    omoSetPopupHashState({
        open: true,
        key: 'faq',
        id: id,
        replace: options.replace === true
    });
};
