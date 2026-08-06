<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/web_push.php';

$sourceLang = [
    'notifications.title' => ['text' => 'Notifications', 'context' => 'Title of the personal push notification settings panel.'],
    'notifications.description' => ['text' => 'Autorisez OMO a vous prevenir sur cet appareil. Les reglages par type de notification seront ajoutes ensuite.', 'context' => 'Description of the personal push notification settings panel.'],
    'notifications.permission.label' => ['text' => 'Autoriser les notifications sur cet appareil', 'context' => 'Label of the toggle that controls browser push notifications.'],
    'notifications.permission.help' => ['text' => 'Une alerte apparaitra dans le centre de notifications de votre telephone ou ordinateur, meme lorsque OMO est ferme.', 'context' => 'Help shown under the push notification toggle.'],
    'notifications.status.loading' => ['text' => 'Verification de cet appareil...', 'context' => 'Status while the browser push state is loading.'],
    'notifications.status.enabled' => ['text' => 'Les notifications sont actives pour cet appareil.', 'context' => 'Success status after subscribing this browser to push notifications.'],
    'notifications.status.disabled' => ['text' => 'Les notifications sont desactivees pour cet appareil.', 'context' => 'Status after unsubscribing this browser from push notifications.'],
    'notifications.status.denied' => ['text' => 'Les notifications ont ete bloquees dans ce navigateur. Modifiez cette autorisation dans les reglages du navigateur pour les reactiver.', 'context' => 'Status when browser notification permission was denied.'],
    'notifications.status.unsupported' => ['text' => 'Ce navigateur ne prend pas en charge les notifications push.', 'context' => 'Status when browser push APIs are not available.'],
    'notifications.status.insecure' => ['text' => 'Les notifications necessitent une connexion HTTPS.', 'context' => 'Status when the page is not served securely.'],
    'notifications.status.configuration' => ['text' => 'Les notifications ne sont pas encore configurees sur ce serveur.', 'context' => 'Status when server VAPID keys are missing.'],
    'notifications.status.service_worker' => ['text' => 'Le service de notifications est encore en cours de demarrage. Reessayez dans quelques secondes.', 'context' => 'Status when the service worker could not become active before a push subscription request.'],
    'notifications.status.brave_push' => ['text' => 'Brave bloque son service Push. Ouvrez Parametres > Confidentialite et securite, puis activez "Utiliser les services Google pour les messages Push" avant de reessayer.', 'context' => 'Help shown only in Brave when the browser push service rejects a subscription.'],
    'notifications.status.error' => ['text' => 'Impossible de modifier les notifications pour cet appareil.', 'context' => 'Fallback error while subscribing or unsubscribing.'],
];
$lang = omoLoadTranslationBundle('omo_notification_settings', $sourceLang);
$translate = static function ($key) use (&$lang, &$sourceLang) {
    return t($key, [], $lang, $sourceLang);
};
$userId = commonGetCurrentUserId();
if ($userId <= 0) {
    http_response_code(401);
    echo '<div class="omo-empty-state">Connexion requise.</div>';
    exit;
}

if (empty($_SESSION['omo_notification_push_csrf'])) {
    $_SESSION['omo_notification_push_csrf'] = bin2hex(random_bytes(32));
}
$vapidConfiguration = webPushGetVapidConfiguration();
$vapidPublicKey = is_array($vapidConfiguration) ? (string)$vapidConfiguration['publicKeyBase64Url'] : '';
$configuration = [
    'csrfToken' => (string)$_SESSION['omo_notification_push_csrf'],
    'vapidPublicKey' => $vapidPublicKey,
    'endpointUrl' => '/omo/api/notifications/push_subscription.php',
    'texts' => [
        'loading' => $translate('notifications.status.loading'),
        'enabled' => $translate('notifications.status.enabled'),
        'disabled' => $translate('notifications.status.disabled'),
        'denied' => $translate('notifications.status.denied'),
        'unsupported' => $translate('notifications.status.unsupported'),
        'insecure' => $translate('notifications.status.insecure'),
        'configuration' => $translate('notifications.status.configuration'),
        'serviceWorker' => $translate('notifications.status.service_worker'),
        'bravePush' => $translate('notifications.status.brave_push'),
        'error' => $translate('notifications.status.error'),
    ],
];
?>
<div class="omo-notification-settings generic-stack generic-stack--roomy" data-omo-notification-settings>
    <div class="generic-stack generic-stack--compact">
        <h2 class="generic-card-title generic-card-title--large"><?= htmlspecialchars($translate('notifications.title'), ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="generic-description"><?= htmlspecialchars($translate('notifications.description'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <section class="generic-section generic-section--stack generic-section--roomy">
        <label class="generic-form-field">
            <span class="generic-card-title generic-card-title--medium"><?= htmlspecialchars($translate('notifications.permission.label'), ENT_QUOTES, 'UTF-8') ?></span>
            <span class="generic-description"><?= htmlspecialchars($translate('notifications.permission.help'), ENT_QUOTES, 'UTF-8') ?></span>
            <span>
                <input type="checkbox" data-omo-notification-toggle>
            </span>
        </label>
        <p class="generic-feedback" data-omo-notification-feedback aria-live="polite"></p>
    </section>
</div>
<script>
(function () {
    var root = document.querySelector('[data-omo-notification-settings]');
    if (!root || root.dataset.omoNotificationSettingsReady === '1') {
        return;
    }
    root.dataset.omoNotificationSettingsReady = '1';
    var configuration = <?= json_encode($configuration, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
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

    async function getRegistration() {
        var registration = await navigator.serviceWorker.getRegistration('/omo/');
        if (!registration) {
            registration = await navigator.serviceWorker.register('/omo/sw.js', {scope: '/omo/', updateViaCache: 'none'});
        }
        if (!registration.active) {
            await navigator.serviceWorker.ready;
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
            showFeedback(configuration.texts.error, 'error');
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

    showFeedback(configuration.texts.loading, '');
    refreshState();
})();
</script>
