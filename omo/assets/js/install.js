(function () {
    const OMO_INSTALL_NEXT_PROMPT_COOKIE = 'omo-install-next-prompt-at';
    const OMO_INSTALL_REFUSAL_COUNT_COOKIE = 'omo-install-refusal-count';
    const OMO_INSTALL_NEVER_COOKIE = 'omo-install-never';
    const OMO_INSTALL_DELAY_DAYS = [1, 3, 7, 14, 30, 90];
    const OMO_SW_URL = '/omo/sw.js';
    const OMO_SW_SCOPE = '/omo/';
    let deferredInstallPrompt = null;
    let bannerElement = null;

    function omoIsStandalone() {
        const standaloneQuery = typeof window.matchMedia === 'function'
            ? window.matchMedia('(display-mode: standalone)')
            : null;

        return (standaloneQuery && standaloneQuery.matches) || window.navigator.standalone === true;
    }

    function omoSetCookie(name, value, expiresAt) {
        const parts = [
            encodeURIComponent(name) + '=' + encodeURIComponent(value),
            'path=/',
            'SameSite=Lax'
        ];

        if (expiresAt instanceof Date) {
            parts.push('expires=' + expiresAt.toUTCString());
        }

        document.cookie = parts.join('; ');
    }

    function omoGetCookie(name) {
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

    function omoDeleteCookie(name) {
        omoSetCookie(name, '', new Date(0));
    }

    function omoGetRefusalCount() {
        const value = Number(omoGetCookie(OMO_INSTALL_REFUSAL_COUNT_COOKIE));

        if (!Number.isFinite(value) || value < 0) {
            return 0;
        }

        return Math.floor(value);
    }

    function omoGetNextDelayDays(refusalCount) {
        if (refusalCount < 0) {
            return OMO_INSTALL_DELAY_DAYS[0];
        }

        return OMO_INSTALL_DELAY_DAYS[Math.min(refusalCount, OMO_INSTALL_DELAY_DAYS.length - 1)];
    }

    function omoScheduleNextPrompt() {
        const nextRefusalCount = omoGetRefusalCount() + 1;
        const delayDays = omoGetNextDelayDays(nextRefusalCount - 1);
        const nextPromptAt = new Date();

        nextPromptAt.setDate(nextPromptAt.getDate() + delayDays);

        omoSetCookie(OMO_INSTALL_REFUSAL_COUNT_COOKIE, String(nextRefusalCount), new Date(Date.now() + (365 * 24 * 60 * 60 * 1000)));
        omoSetCookie(OMO_INSTALL_NEXT_PROMPT_COOKIE, nextPromptAt.toISOString(), nextPromptAt);
    }

    function omoClearPromptSchedule() {
        omoDeleteCookie(OMO_INSTALL_NEXT_PROMPT_COOKIE);
        omoDeleteCookie(OMO_INSTALL_REFUSAL_COUNT_COOKIE);
    }

    function omoNeverShowPromptAgain() {
        const expiresAt = new Date();
        expiresAt.setFullYear(expiresAt.getFullYear() + 10);
        omoSetCookie(OMO_INSTALL_NEVER_COOKIE, '1', expiresAt);
        omoHideBanner();
    }

    function omoShouldSuppressBanner() {
        if (omoGetCookie(OMO_INSTALL_NEVER_COOKIE) === '1') {
            return true;
        }

        const nextPromptValue = omoGetCookie(OMO_INSTALL_NEXT_PROMPT_COOKIE);
        if (nextPromptValue === '') {
            return false;
        }

        const nextPromptAt = new Date(nextPromptValue);
        if (Number.isNaN(nextPromptAt.getTime())) {
            return false;
        }

        return nextPromptAt.getTime() > Date.now();
    }

    function omoFindBannerHost() {
        const main = document.querySelector('.main');

        if (main) {
            return main;
        }

        const authShell = document.querySelector('.auth-shell');

        if (authShell && authShell.parentNode) {
            return authShell.parentNode;
        }

        return document.body;
    }

    function omoEnsureBanner() {
        if (bannerElement) {
            return bannerElement;
        }

        bannerElement = document.createElement('section');
        bannerElement.className = 'omo-install-banner';
        bannerElement.setAttribute('hidden', 'hidden');
        bannerElement.innerHTML = [
            '<div class="omo-install-banner__content">',
            '    <div class="omo-install-banner__title">Installer OMO comme application</div>',
            '    <div class="omo-install-banner__text">Ajoutez OMO sur votre appareil pour l ouvrir comme une application dediee.</div>',
            '</div>',
            '<div class="omo-install-banner__actions">',
            '    <button type="button" class="omo-install-banner__button omo-install-banner__button--secondary" data-omo-install-dismiss>Plus tard</button>',
            '    <button type="button" class="omo-install-banner__button omo-install-banner__button--primary" data-omo-install-confirm>Installer</button>',
            '</div>',
            '<button type="button" class="omo-install-banner__never" data-omo-install-never>Ne plus me demander</button>'
        ].join('');

        const host = omoFindBannerHost();

        if (host.firstChild) {
            host.insertBefore(bannerElement, host.firstChild);
        } else {
            host.appendChild(bannerElement);
        }

        bannerElement.querySelector('[data-omo-install-dismiss]').addEventListener('click', function () {
            omoScheduleNextPrompt();
            omoHideBanner();
        });

        bannerElement.querySelector('[data-omo-install-confirm]').addEventListener('click', omoPromptInstall);
        bannerElement.querySelector('[data-omo-install-never]').addEventListener('click', omoNeverShowPromptAgain);

        return bannerElement;
    }

    function omoShowBanner() {
        if (!deferredInstallPrompt || omoIsStandalone() || omoShouldSuppressBanner()) {
            return;
        }

        const banner = omoEnsureBanner();
        banner.removeAttribute('hidden');
        banner.classList.add('is-visible');
    }

    function omoHideBanner() {
        if (!bannerElement) {
            return;
        }

        bannerElement.classList.remove('is-visible');
        bannerElement.setAttribute('hidden', 'hidden');
    }

    async function omoPromptInstall() {
        if (!deferredInstallPrompt) {
            return;
        }

        const installButton = bannerElement
            ? bannerElement.querySelector('[data-omo-install-confirm]')
            : null;

        if (installButton) {
            installButton.disabled = true;
        }

        try {
            deferredInstallPrompt.prompt();
            const result = await deferredInstallPrompt.userChoice;

            if (!result || result.outcome !== 'accepted') {
                omoScheduleNextPrompt();
            } else {
                omoClearPromptSchedule();
            }
        } catch (error) {
            console.error('Impossible d\'ouvrir le prompt d\'installation OMO.', error);
        } finally {
            deferredInstallPrompt = null;
            omoHideBanner();

            if (installButton) {
                installButton.disabled = false;
            }
        }
    }

    function omoRegisterServiceWorker() {
        if (!('serviceWorker' in navigator) || !window.isSecureContext) {
            return;
        }

        window.addEventListener('load', function () {
            navigator.serviceWorker.register(OMO_SW_URL, {
                scope: OMO_SW_SCOPE,
                updateViaCache: 'none'
            }).then(function (registration) {
                if (registration && typeof registration.update === 'function') {
                    registration.update();
                }
            }).catch(function (error) {
                console.error('Impossible d\'enregistrer le service worker OMO.', error);
            });
        }, { once: true });
    }

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredInstallPrompt = event;
        omoShowBanner();
    });

    window.addEventListener('appinstalled', function () {
        deferredInstallPrompt = null;
        omoClearPromptSchedule();
        omoHideBanner();
    });

    if (!omoIsStandalone()) {
        omoRegisterServiceWorker();
    }
})();
