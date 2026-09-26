window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/parameters/notifications/index.js"] = function (pageConfig, pageScript) {
(function () {
    var root = document.querySelector('[data-omo-notification-settings]');
    if (!root || root.dataset.omoNotificationSettingsReady === '1') {
        return;
    }
    root.dataset.omoNotificationSettingsReady = '1';
    var configuration = pageConfig.configuration;
    var toggle = root.querySelector('[data-omo-notification-toggle]');
    var feedback = root.querySelector('[data-omo-notification-feedback]');

    function showFeedback(message, kind) {
        if (!feedback) {
            return;
        }
        feedback.textContent = String(message || '');
        feedback.className = 'generic-feedback' + (kind ? ' is-' + kind : '');
    }

    function base64UrlToUint8Array(value) {
        var base64 = String(value || '').replace(/-/g, '+').replace(/_/g, '/');
        var padding = '='.repeat((4 - (base64.length % 4)) % 4);
        var raw = window.atob(base64 + padding);
        var output = new Uint8Array(raw.length);
        for (var index = 0; index < raw.length; index += 1) {
            output[index] = raw.charCodeAt(index);
        }
        return output;
    }

    function supportsPush() {
        return window.isSecureContext
            && 'serviceWorker' in navigator
            && 'PushManager' in window
            && 'Notification' in window;
    }

    async function isBraveBrowser() {
        if (!navigator.brave || typeof navigator.brave.isBrave !== 'function') {
            return false;
        }

        try {
            return await navigator.brave.isBrave();
        } catch (error) {
            return false;
        }
    }

    async function getSubscriptionErrorMessage(error) {
        var message = error && error.message ? String(error.message) : '';
        if (message.toLowerCase().indexOf('push service') !== -1 && await isBraveBrowser()) {
            return configuration.texts.bravePush;
        }
        return message || configuration.texts.error;
    }

    function requestServer(method, subscription) {
        return window.fetch(configuration.endpointUrl, {
            method: method,
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                csrf_token: configuration.csrfToken,
                subscription: subscription.toJSON()
            })
        }).then(function (response) {
            return response.json().catch(function () {
                return {status: false, message: configuration.texts.error};
            }).then(function (payload) {
                if (!response.ok || !payload || payload.status !== true) {
                    throw new Error(String(payload && payload.message ? payload.message : configuration.texts.error));
                }
                return payload;
            });
        });
    }

    function waitForActiveServiceWorker() {
        return Promise.race([
            navigator.serviceWorker.ready,
            new Promise(function (resolve, reject) {
                window.setTimeout(function () {
                    reject(new Error(configuration.texts.serviceWorker));
                }, 10000);
            })
        ]);
    }

    async function getRegistration() {
        var registration = await navigator.serviceWorker.getRegistration('/omo/');
        if (!registration) {
            registration = await navigator.serviceWorker.register('/omo/sw.js', {scope: '/omo/', updateViaCache: 'none'});
        }
        if (!registration.active) {
            await waitForActiveServiceWorker();
            registration = await navigator.serviceWorker.getRegistration('/omo/') || registration;
        }
        if (!registration.active) {
            throw new Error(configuration.texts.serviceWorker);
        }
        return registration;
    }

    async function refreshState() {
        if (!toggle) {
            return;
        }
        toggle.disabled = true;
        if (!window.isSecureContext) {
            showFeedback(configuration.texts.insecure, 'error');
            return;
        }
        if (!supportsPush()) {
            showFeedback(configuration.texts.unsupported, 'error');
            return;
        }
        if (!configuration.vapidPublicKey) {
            showFeedback(configuration.texts.configuration, 'error');
            return;
        }
        if (Notification.permission === 'denied') {
            showFeedback(configuration.texts.denied, 'error');
            return;
        }

        try {
            var registration = await getRegistration();
            var subscription = await registration.pushManager.getSubscription();
            if (subscription) {
                await requestServer('POST', subscription);
            }
            toggle.checked = subscription !== null;
            toggle.disabled = false;
            showFeedback(subscription ? configuration.texts.enabled : configuration.texts.disabled, subscription ? 'success' : '');
        } catch (error) {
            toggle.disabled = false;
            showFeedback(await getSubscriptionErrorMessage(error), 'error');
        }
    }

    if (toggle) {
        toggle.addEventListener('change', async function () {
            toggle.disabled = true;
            try {
                var registration = await getRegistration();
                var subscription = await registration.pushManager.getSubscription();
                if (toggle.checked) {
                    var permission = Notification.permission === 'granted'
                        ? 'granted'
                        : await Notification.requestPermission();
                    if (permission !== 'granted') {
                        toggle.checked = false;
                        showFeedback(configuration.texts.denied, 'error');
                        return;
                    }
                    if (!subscription) {
                        subscription = await registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: base64UrlToUint8Array(configuration.vapidPublicKey)
                        });
                    }
                    await requestServer('POST', subscription);
                    showFeedback(configuration.texts.enabled, 'success');
                    return;
                }

                if (subscription) {
                    await requestServer('DELETE', subscription);
                    await subscription.unsubscribe();
                }
                showFeedback(configuration.texts.disabled, '');
            } catch (error) {
                toggle.checked = !toggle.checked;
                showFeedback(await getSubscriptionErrorMessage(error), 'error');
            } finally {
                toggle.disabled = false;
            }
        });
    }

    var preferencesForm = root.querySelector('[data-omo-notification-preferences]');
    if (preferencesForm) {
        preferencesForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var submit = preferencesForm.querySelector('button[type="submit"]');
            var preferencesFeedback = preferencesForm.querySelector('[data-omo-notification-preferences-feedback]');
            if (submit) {
                submit.disabled = true;
            }
            window.fetch(configuration.preferencesUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: new FormData(preferencesForm),
                headers: {'Accept': 'application/json'}
            }).then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok || !payload || payload.status !== true) {
                        throw new Error(payload && payload.message ? payload.message : configuration.texts.error);
                    }
                    return payload;
                });
            }).then(function () {
                if (preferencesFeedback) {
                    preferencesFeedback.textContent = pageConfig.notificationsPreferencesSaved;
                    preferencesFeedback.className = 'generic-feedback is-success';
                }
            }).catch(function (error) {
                if (preferencesFeedback) {
                    preferencesFeedback.textContent = error && error.message ? error.message : pageConfig.message;
                    preferencesFeedback.className = 'generic-feedback is-error';
                }
            }).finally(function () {
                if (submit) {
                    submit.disabled = false;
                }
            });
        });
    }

    showFeedback(configuration.texts.loading, '');
    refreshState();
})();
};
