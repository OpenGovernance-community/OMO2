const OMO_THEME_STORAGE_KEY = 'omo-theme-preference';
const OMO_THEME_MEDIA_QUERY = '(prefers-color-scheme: dark)';

function omoGetThemePreference() {
    try {
        const storedPreference = localStorage.getItem(OMO_THEME_STORAGE_KEY);

        if (storedPreference === 'light' || storedPreference === 'dark' || storedPreference === 'system') {
            return storedPreference;
        }
    } catch (error) {
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
    const resolvedTheme = omoResolveTheme(safePreference);
    const root = document.documentElement;

    root.dataset.themePreference = safePreference;
    root.dataset.theme = resolvedTheme;
    root.style.colorScheme = resolvedTheme;

    if (persistPreference) {
        try {
            localStorage.setItem(OMO_THEME_STORAGE_KEY, safePreference);
        } catch (error) {
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
}

function omoEscapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function omoGetUserProfile() {
    const profile = window.omoConfig && window.omoConfig.userProfile ? window.omoConfig.userProfile : {};
    const displayName = (profile.displayName || window.omoConfig.currentUserName || 'Profil').trim();
    const initial = (displayName.charAt(0) || 'P').toUpperCase();

    return `
        <div class="omo-profile-panel" data-omo-profile-panel>
            <section class="omo-profile-panel__section omo-profile-panel__section--media">
                <div class="omo-profile-card">
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
                <div class="omo-profile-details">
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
                <div class="omo-profile-actions">
                    <button type="button" class="common-topbar__menu-item omo-profile-actions__button" data-topbar-profile-edit>Modifier le profil</button>
                    <button type="button" class="common-topbar__menu-item common-topbar__menu-item--danger omo-profile-actions__button" data-topbar-logout>Se déconnecter</button>
                </div>
            </section>
        </div>
    `;
}

function omoEnsureProfilePanel() {
    const profileMenu = document.querySelector('[data-topbar-menu="profile"]');

    if (!profileMenu || profileMenu.querySelector('[data-omo-profile-panel]')) {
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

$(document).ready(function () {

    let isResizing = false;

    const leftPanel = $('#panel-left');
    const container = $('.content');
    const resizer = $('#resizer');

    // Charger largeur sauvegardée
    let savedWidth = localStorage.getItem('panelLeftWidth');
    if (savedWidth) {
        leftPanel.css({
            width: savedWidth + 'px',
            flexBasis: savedWidth + 'px'
        });
    }

    resizer.on('mousedown', function (e) {
        if (e.button !== 0) return; // uniquement clic gauche

        isResizing = true;

        $('body').addClass('resizing');

        // Empêche sélection texte
        e.preventDefault();
    });

    $(document).on('mousemove', function (e) {
        if (!isResizing) return;

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
    });

    function stopResizing() {
        if (!isResizing) return;

        isResizing = false;

        $('body').removeClass('resizing');

        // sauvegarde
        let finalWidth = leftPanel.width();
        localStorage.setItem('panelLeftWidth', finalWidth);
    }

    $(document).on('mouseup', stopResizing);

    // 🔥 FIX IMPORTANT : si la souris sort de la fenêtre
    $(window).on('blur', stopResizing);

});

function getResolvedOrganizationId() {

    const oid = window.omoConfig && window.omoConfig.oid;

    if (oid === null || oid === undefined || oid === '') {
        return null;
    }

    const parsedOid = Number(oid);

    return Number.isNaN(parsedOid) ? null : parsedOid;
}

function getNormalizedOmoPath() {

    const path = window.location.pathname || '';

    if (path.length > 1) {
        return path.replace(/\/+$/, '');
    }

    return path;
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

function loadContent(target, url, type = 'panel', onLoaded = null) {

    $(target).html(getSkeleton(type));

    $.ajax({
        url: url,
        method: 'GET',
        cache: false,

        success: function (data) {

            $(target).html(data);

            // 👉 callback après injection DOM
            if (typeof onLoaded === 'function') {
                onLoaded();
            }
        },

        error: function () {
            $(target).html('<div class="error">Erreur de chargement</div>');
        }
    });
}

function loadContent(target, url, type = 'panel', onLoaded = null) {

    const $target = $(target);
    const previousRequest = $target.data('omoXhr');
    const requestId = `${Date.now()}_${Math.random().toString(36).slice(2)}`;

    if (previousRequest && previousRequest.readyState !== 4) {
        previousRequest.abort();
    }

    $target.data('omoRequestId', requestId);
    $target.html(getSkeleton(type));

    const xhr = $.ajax({
        url: url,
        method: 'GET',
        cache: false,

        success: function (data) {
            if ($target.data('omoRequestId') !== requestId) {
                return;
            }

            $target.html(data);

            if (typeof onLoaded === 'function') {
                onLoaded();
            }

        },

        error: function () {
            if ($target.data('omoRequestId') !== requestId) {
                return;
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

function openDrawer(id, url) {

    let drawer = $('#' + id);

    // 👉 si déjà ouvert → on ferme tout et on stop
    if (drawer.length && drawer.hasClass('open')) {
        closeAllDrawers();
        return;
    }

    // 👉 fermer les autres
    closeAllDrawers();

    // 👉 créer si inexistant
    if (drawer.length === 0) {

        drawer = $(`
            <div class="drawer" id="${id}">
                <div class="drawer-content"></div>
            </div>
        `);

        $('.content').append(drawer);

        drawer.find('.drawer-content').html(getSkeleton('panel'));

        $.ajax({
            url: url,
            success: function (data) {
                drawer.find('.drawer-content').html(data);
            },
            error: function () {
                drawer.find('.drawer-content').html('Erreur');
            }
        });
    }

    updateDrawerPosition(drawer);

    requestAnimationFrame(() => {
        drawer.addClass('open');
    });
}

function updateDrawerPosition(drawer) {

    const leftWidth = $('#panel-left').outerWidth();

    drawer.css({
        left: leftWidth + 'px',
        width: 'calc(100% - ' + leftWidth + 'px)'
    });
}

function closeDrawer(id) {
    $('#' + id).removeClass('open');
}

function closeAllDrawers() {
    $('.drawer.open').removeClass('open');
}

$(document).on('click', '[data-hash]', function (e) {

    e.preventDefault();

    const hash = $(this).data('hash');
    const { oid, cid } = parseUrl();

    navigate(oid, cid, hash);

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

    if (!hash) return;

    const route = hash.split('/')[0];

    const item = $(`[data-hash="${route}"]`);

    if (item.length) {
        item.addClass('active');
    }
}

function navigate(oid, cid = null, hash = null) {

    let url = '/omo';

    if (cid) {
        url += `/c/${cid}`;
    }

    if (hash) {
        url += `#${hash}`;
    }

    history.pushState({}, '', url);

    handleRoute();
}

function parseUrl() {

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
    hash: null
};

function omoFocusStructureNode(cid = null) {
    window.dispatchEvent(new CustomEvent('omo-structure-focus', {
        detail: {
            cid: cid === null || cid === undefined || cid === '' ? null : Number(cid)
        }
    }));
}

function handleRoute() {

    const { oid, cid, hash } = parseUrl();

    if (!Number.isInteger(Number(oid)) || Number(oid) <= 0) {

        const errorMessage = '<div class="error">Organisation introuvable pour ce sous-domaine.</div>';

        $('#panel-left').html(errorMessage);
        $('#panel-right').html(errorMessage);
        closeAllDrawers();
        updateActiveMenu(null);

        currentState = { oid, cid, hash };
        return;
    }

    // 👉 détection des changements
    const organizationChanged = (oid !== currentState.oid);
    const cidChanged = (cid !== currentState.cid);
    const hashChanged = (hash !== currentState.hash);

    // 👉 mise à jour state
    currentState = { oid, cid, hash };

    // 👉 menu actif
    updateActiveMenu(hash);

    // 🧱 1. Charger panels seulement si contexte change
    if (organizationChanged) {

        let leftUrl = `api/getOrg.php?oid=${oid}`;
        if (cid) leftUrl += `&cid=${cid}`;

        loadContent('#panel-left', leftUrl);

        let rightUrl = `api/getStructure.php?oid=${oid}`;
        if (cid) rightUrl += `&cid=${cid}`;

        loadContent('#panel-right', rightUrl);
    } else if (cidChanged) {

        let leftUrl = `api/getOrg.php?oid=${oid}`;
        if (cid) leftUrl += `&cid=${cid}`;

        loadContent('#panel-left', leftUrl);
        omoFocusStructureNode(cid);
    }

    // 🧩 2. Gérer les drawers (modules)
    if (hashChanged) {

        if (hash) {

            const drawerId = `drawer_${hash}`;
            const url = `api/${hash}/index.php?oid=${oid}${cid ? '&cid=' + cid : ''}`;

            openDrawer(drawerId, url);

        } else {
            closeAllDrawers();
        }
    }
}

function activateMenu(hash) {

    $('.menu-item').removeClass('active');

    if (!hash) return;

    $(`[data-hash="${hash}"]`).addClass('active');
}

$(document).ready(function () {
    handleRoute();
});

$(window).on('hashchange', handleRoute);

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

    $('body')
        .removeClass('view-menu view-left view-right')
        .addClass('view-' + view);

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

function omoHandleTopbarSearch(query) {
    const normalized = (query || '').trim().toLowerCase();

    document.querySelectorAll('#menu_sidebar .menu-item').forEach(function (item) {
        const text = (item.textContent || '').toLowerCase();
        item.style.display = normalized === '' || text.indexOf(normalized) !== -1 ? '' : 'none';
    });

    document.querySelectorAll('#panel-left .circle-section, #panel-left .breadcrumb, #panel-right .role-item, .drawer .kanban-card').forEach(function (node) {
        const text = (node.textContent || '').toLowerCase();
        node.style.display = normalized === '' || text.indexOf(normalized) !== -1 ? '' : 'none';
    });
}

$(document).on('click', '[data-view]', function () {

    const view = $(this).data('view');

    $('body')
        .removeClass('view-menu view-left view-right')
        .addClass('view-' + view);

});
