(function () {
    const OMO_INSTALL_SESSION_KEY = 'omo-install-banner-dismissed';
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

    function omoMarkBannerDismissed() {
        try {
            window.sessionStorage.setItem(OMO_INSTALL_SESSION_KEY, '1');
        } catch (error) {
        }
    }

    function omoIsBannerDismissed() {
        try {
            return window.sessionStorage.getItem(OMO_INSTALL_SESSION_KEY) === '1';
        } catch (error) {
            return false;
        }
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
        ].join('');

        const host = omoFindBannerHost();

        if (host.firstChild) {
            host.insertBefore(bannerElement, host.firstChild);
        } else {
            host.appendChild(bannerElement);
        }

        bannerElement.querySelector('[data-omo-install-dismiss]').addEventListener('click', function () {
            omoMarkBannerDismissed();
            omoHideBanner();
        });

        bannerElement.querySelector('[data-omo-install-confirm]').addEventListener('click', omoPromptInstall);

        return bannerElement;
    }

    function omoShowBanner() {
        if (!deferredInstallPrompt || omoIsStandalone() || omoIsBannerDismissed()) {
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
            await deferredInstallPrompt.userChoice;
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
        omoHideBanner();
    });

    if (!omoIsStandalone()) {
        omoRegisterServiceWorker();
    }
})();
