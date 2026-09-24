<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/notification_center.php';

$sourceLang = [
    'notifications.title' => ['text' => 'Notifications', 'context' => 'Title of the personal push notification settings panel.'],
    'notifications.description' => ['text' => 'Autorisez OMO à vous prévenir sur cet appareil, puis choisissez les événements et canaux qui vous conviennent.', 'context' => 'Description of the personal push notification settings panel.'],
    'notifications.permission.label' => ['text' => 'Autoriser les notifications sur cet appareil', 'context' => 'Label of the toggle that controls browser push notifications.'],
    'notifications.permission.help' => ['text' => 'Une alerte apparaîtra dans le centre de notifications de votre téléphone ou ordinateur, même lorsque OMO est fermé.', 'context' => 'Help shown under the push notification toggle.'],
    'notifications.status.loading' => ['text' => 'Vérification de cet appareil…', 'context' => 'Status while the browser push state is loading.'],
    'notifications.status.enabled' => ['text' => 'Les notifications sont activées pour cet appareil.', 'context' => 'Success status after subscribing this browser to push notifications.'],
    'notifications.status.disabled' => ['text' => 'Les notifications sont désactivées pour cet appareil.', 'context' => 'Status after unsubscribing this browser from push notifications.'],
    'notifications.status.denied' => ['text' => 'Les notifications ont été bloquées dans ce navigateur. Modifiez cette autorisation dans les réglages du navigateur pour les réactiver.', 'context' => 'Status when browser notification permission was denied.'],
    'notifications.status.unsupported' => ['text' => 'Ce navigateur ne prend pas en charge les notifications push.', 'context' => 'Status when browser push APIs are not available.'],
    'notifications.status.insecure' => ['text' => 'Les notifications nécessitent une connexion HTTPS.', 'context' => 'Status when the page is not served securely.'],
    'notifications.status.configuration' => ['text' => 'Les notifications ne sont pas encore configurées sur ce serveur.', 'context' => 'Status when server VAPID keys are missing.'],
    'notifications.status.service_worker' => ['text' => 'Le service de notifications est encore en cours de démarrage. Réessayez dans quelques secondes.', 'context' => 'Status when the service worker could not become active before a push subscription request.'],
    'notifications.status.brave_push' => ['text' => 'Brave bloque son service Push. Ouvrez Paramètres > Confidentialité et sécurité, puis activez « Utiliser les services Google pour les messages Push » avant de réessayer.', 'context' => 'Help shown only in Brave when the browser push service rejects a subscription.'],
    'notifications.status.error' => ['text' => 'Impossible de modifier les notifications pour cet appareil.', 'context' => 'Fallback error while subscribing or unsubscribing.'],
    'notifications.preferences.title' => ['text' => 'Événements et canaux', 'context' => 'Title of the notification preference form.'],
    'notifications.preferences.description' => ['text' => 'Ces réglages s’appliquent uniquement à cette organisation. Désactivez tous les canaux pour ne rien recevoir.', 'context' => 'Description of the organization notification preference form.'],
    'notifications.preferences.group.decisions' => ['text' => 'Décisions', 'context' => 'Title of the decision notification preference group.'],
    'notifications.preferences.group.calendar' => ['text' => 'Calendrier', 'context' => 'Title of the calendar notification preference group.'],
    'notifications.preferences.group.projects' => ['text' => 'Projets', 'context' => 'Title of the project notification preference group.'],
    'notifications.preferences.header.event' => ['text' => 'Événement', 'context' => 'Header for the event column in notification preferences.'],
    'notifications.preferences.event.decision_proposal_owner' => ['text' => 'Ajout d’une proposition à mes scrutins', 'context' => 'Preference label for proposals added to decisions owned by the user.'],
    'notifications.preferences.event.decision_proposal_participant' => ['text' => 'Ajout d’une proposition aux scrutins auxquels je participe', 'context' => 'Preference label for proposals added to decisions where the user participates.'],
    'notifications.preferences.event.decision_chat_proposal_owner' => ['text' => 'Commentaire sur le chat de mes propositions', 'context' => 'Preference label for comments on proposals authored by the user.'],
    'notifications.preferences.event.decision_chat_participant' => ['text' => 'Commentaire dans un chat auquel je participe', 'context' => 'Preference label for comments in chats where the user has posted.'],
    'notifications.preferences.event.decision_consultation_started' => ['text' => 'Passage de mes scrutins invités en consultation', 'context' => 'Preference label for invited decisions entering consultation.'],
    'notifications.preferences.event.decision_evaluation_started' => ['text' => 'Passage de mes scrutins invités en vote', 'context' => 'Preference label for invited decisions entering voting.'],
    'notifications.preferences.event.decision_consultation_ending' => ['text' => 'Fin prochaine de la consultation', 'context' => 'Preference label for consultation deadline reminders.'],
    'notifications.preferences.event.decision_evaluation_ending' => ['text' => 'Fin prochaine du vote', 'context' => 'Preference label for voting deadline reminders.'],
    'notifications.preferences.event.decision_consultation_finished' => ['text' => 'Fin de la consultation de mes scrutins : me rappeler de traiter les propositions et la suite', 'context' => 'Preference label for consultation completion notifications sent to the decision owner.'],
    'notifications.preferences.event.decision_evaluation_finished' => ['text' => 'Fin du vote de mes scrutins : me rappeler de traiter ou publier la suite du scrutin', 'context' => 'Preference label for voting completion notifications sent to the decision owner.'],
    'notifications.preferences.event.project_proposal_refused' => ['text' => 'Refus de mes propositions de projet', 'context' => 'Preference label for refused project proposals.'],
    'notifications.preferences.event.project_status_changed' => ['text' => 'Changement de statut des projets que je suis', 'context' => 'Preference label for status changes on followed projects.'],
    'notifications.preferences.event.project_chat_owner' => ['text' => 'Commentaire sur mes projets ou propositions de projet', 'context' => 'Preference label for comments on projects the viewer is responsible for or proposed.'],
    'notifications.preferences.event.project_chat_participant' => ['text' => 'Commentaire dans une discussion de projet à laquelle je participe', 'context' => 'Preference label for comments in project chats where the viewer has posted.'],
    'notifications.preferences.event.calendar_event_invited' => ['text' => 'Invitation à un nouvel événement', 'context' => 'Preference label for event creation or first invitation.'],
    'notifications.preferences.event.calendar_event_location_changed' => ['text' => 'Modification du lieu d’un événement auquel je suis invité', 'context' => 'Preference label for event location changes.'],
    'notifications.preferences.event.calendar_event_schedule_changed' => ['text' => 'Modification de l’horaire d’un événement auquel je suis invité', 'context' => 'Preference label for event schedule changes.'],
    'notifications.preferences.event.calendar_event_starting' => ['text' => 'Début prochain d’un événement auquel je suis invité', 'context' => 'Preference label for event start reminders.'],
    'notifications.preferences.reminder_days' => ['text' => 'Me rappeler', 'context' => 'Label for the day choices of a notification deadline reminder.'],
    'notifications.preferences.reminder_day' => ['one' => '{count} jour avant', 'other' => '{count} jours avant', 'context' => 'Label for one selected reminder delay.'],
    'notifications.preferences.reminder_none' => ['text' => 'Ne pas envoyer de rappel', 'context' => 'Empty option for an event reminder lead time.'],
    'notifications.preferences.reminder_option.1h' => ['text' => '1 heure avant', 'context' => 'Event notification reminder lead time.'],
    'notifications.preferences.reminder_option.2h' => ['text' => '2 heures avant', 'context' => 'Event notification reminder lead time.'],
    'notifications.preferences.reminder_option.3h' => ['text' => '3 heures avant', 'context' => 'Event notification reminder lead time.'],
    'notifications.preferences.reminder_option.5h' => ['text' => '5 heures avant', 'context' => 'Event notification reminder lead time.'],
    'notifications.preferences.reminder_option.8h' => ['text' => '8 heures avant', 'context' => 'Event notification reminder lead time.'],
    'notifications.preferences.reminder_option.12h' => ['text' => '12 heures avant', 'context' => 'Event notification reminder lead time.'],
    'notifications.preferences.reminder_option.1d' => ['text' => '1 jour avant', 'context' => 'Event notification reminder lead time.'],
    'notifications.preferences.reminder_option.2d' => ['text' => '2 jours avant', 'context' => 'Event notification reminder lead time.'],
    'notifications.preferences.reminder_option.3d' => ['text' => '3 jours avant', 'context' => 'Event notification reminder lead time.'],
    'notifications.preferences.reminder_option.5d' => ['text' => '5 jours avant', 'context' => 'Event notification reminder lead time.'],
    'notifications.preferences.channel.in_app' => ['text' => 'Dans OMO', 'context' => 'Preference channel label for the OMO notification inbox.'],
    'notifications.preferences.channel.push' => ['text' => 'Notification', 'context' => 'Preference channel label for browser push.'],
    'notifications.preferences.channel.telegram' => ['text' => 'Telegram', 'context' => 'Preference channel label for Telegram.'],
    'notifications.preferences.channel.email' => ['text' => 'E-mail', 'context' => 'Preference channel label for email.'],
    'notifications.preferences.save' => ['text' => 'Enregistrer les reglages', 'context' => 'Submit label for notification preferences.'],
    'notifications.preferences.saved' => ['text' => 'Réglages de notifications enregistrés.', 'context' => 'Success feedback after saving notification preferences.'],
    'notifications.preferences.save_error' => ['text' => 'Impossible d’enregistrer les réglages de notifications.', 'context' => 'Failure feedback after saving notification preferences.'],
    'notifications.preferences.telegram_unavailable' => ['text' => 'Telegram n’est pas connecté à ce compte.', 'context' => 'Help shown when Telegram is unavailable.'],
    'notifications.warning.push.title' => ['text' => 'Notifications navigateur non configurées', 'context' => 'Warning title shown to organization administrators when browser push is unavailable on the server.'],
    'notifications.warning.push.description' => ['text' => 'Les clés VAPID ne sont pas configurées sur ce serveur. Les membres ne peuvent pas activer les notifications navigateur.', 'context' => 'Warning body shown to organization administrators when browser push is unavailable on the server.'],
    'notifications.warning.telegram.title' => ['text' => 'Telegram non configuré', 'context' => 'Warning title shown to organization administrators when Telegram is unavailable on the server.'],
    'notifications.warning.telegram.description' => ['text' => 'Le jeton du bot Telegram n’est pas configuré sur ce serveur. Les membres ne peuvent pas recevoir de messages Telegram.', 'context' => 'Warning body shown to organization administrators when Telegram is unavailable on the server.'],
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
$pushConfigured = $vapidPublicKey !== '';
$telegramConfigured = defined('TOKEN') && trim((string)TOKEN) !== '';
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$isOrganizationAdmin = commonCurrentUserCanUseAdminMode($organizationId);
$eventGroups = notificationCenterGetActiveEventGroups($organizationId, $userId);
$eventKeys = [];
foreach ($eventGroups as $eventGroup) {
    $eventKeys = array_merge($eventKeys, $eventGroup['eventKeys'] ?? []);
}
$preferenceSettings = \dbObject\NotificationPreference::getAllForUserOrganization($userId, $organizationId, $eventKeys);
$reminderEventKeys = ['decision_consultation_ending', 'decision_evaluation_ending'];
$eventStartReminderEventKeys = ['calendar_event_starting'];
$eventReminderLeadTimes = \dbObject\NotificationPreference::getReminderLeadTimes();
$currentUser = new \dbObject\User();
$telegramAvailable = $telegramConfigured
    && $currentUser->load($userId)
    && trim((string)$currentUser->get('telegramID')) !== '';
$preferenceChannels = ['in_app'];
if ($pushConfigured) {
    $preferenceChannels[] = 'push';
}
if ($telegramConfigured) {
    $preferenceChannels[] = 'telegram';
}
$preferenceChannels[] = 'email';
$configuration = [
    'csrfToken' => (string)$_SESSION['omo_notification_push_csrf'],
    'vapidPublicKey' => $vapidPublicKey,
    'endpointUrl' => '/omo/api/notifications/push_subscription.php',
    'preferencesUrl' => '/omo/api/parameters/notifications/preferences.php',
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
    <?php if ($isOrganizationAdmin && !$pushConfigured): ?>
    <section class="generic-section generic-section--stack generic-section--roomy generic-section--warning">
        <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars($translate('notifications.warning.push.title'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p class="generic-description"><?= htmlspecialchars($translate('notifications.warning.push.description'), ENT_QUOTES, 'UTF-8') ?></p>
    </section>
    <?php endif; ?>
    <?php if ($isOrganizationAdmin && !$telegramConfigured): ?>
    <section class="generic-section generic-section--stack generic-section--roomy generic-section--warning">
        <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars($translate('notifications.warning.telegram.title'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p class="generic-description"><?= htmlspecialchars($translate('notifications.warning.telegram.description'), ENT_QUOTES, 'UTF-8') ?></p>
    </section>
    <?php endif; ?>
    <?php if ($pushConfigured): ?>
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
    <?php endif; ?>
    <?php if ($eventGroups !== []): ?>
    <form class="generic-section generic-section--stack generic-section--roomy" data-omo-notification-preferences>
        <div class="generic-stack generic-stack--compact">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars($translate('notifications.preferences.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="generic-description"><?= htmlspecialchars($translate('notifications.preferences.description'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($telegramConfigured && !$telegramAvailable): ?>
            <p class="generic-help-text"><?= htmlspecialchars($translate('notifications.preferences.telegram_unavailable'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$_SESSION['omo_notification_push_csrf'], ENT_QUOTES, 'UTF-8') ?>">
        <?php foreach ($eventGroups as $groupKey => $eventGroup): ?>
        <section class="omo-notification-preferences-group generic-stack generic-stack--compact" aria-labelledby="omo-notification-group-<?= htmlspecialchars($groupKey, ENT_QUOTES, 'UTF-8') ?>">
            <h4 id="omo-notification-group-<?= htmlspecialchars($groupKey, ENT_QUOTES, 'UTF-8') ?>" class="generic-card-title generic-card-title--medium"><?= htmlspecialchars($translate('notifications.preferences.group.' . $groupKey), ENT_QUOTES, 'UTF-8') ?></h4>
            <div class="omo-notification-preferences-grid" role="table" aria-label="<?= htmlspecialchars($translate('notifications.preferences.group.' . $groupKey), ENT_QUOTES, 'UTF-8') ?>" style="--param-notification-channel-count: <?= count($preferenceChannels) ?>;">
                <div class="omo-notification-preferences-grid__row omo-notification-preferences-grid__row--header" role="row">
                    <span role="columnheader"><?= htmlspecialchars($translate('notifications.preferences.header.event'), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php foreach ($preferenceChannels as $channelKey): ?>
                    <span role="columnheader"><?= htmlspecialchars($translate('notifications.preferences.channel.' . $channelKey), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                </div>
                <?php foreach ($eventGroup['eventKeys'] as $eventKey): ?>
                <?php
                    $channels = $preferenceSettings[$eventKey] ?? ['in_app' => true, 'push' => false, 'telegram' => false, 'email' => false];
                    $eventLabel = $translate('notifications.preferences.event.' . $eventKey);
                ?>
                <div class="omo-notification-preferences-grid__row" role="row">
                    <span class="omo-notification-preferences-grid__event" role="rowheader">
                        <span><?= htmlspecialchars($eventLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (in_array($eventKey, $reminderEventKeys, true)): ?>
                        <span class="omo-notification-preferences-grid__reminder-days">
                            <span><?= htmlspecialchars($translate('notifications.preferences.reminder_days'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php foreach ([1, 2, 3, 5] as $day): ?>
                            <label><input type="checkbox" name="preferences[<?= htmlspecialchars($eventKey, ENT_QUOTES, 'UTF-8') ?>][days][]" value="<?= $day ?>"<?= in_array($day, $channels['days'] ?? [], true) ? ' checked' : '' ?>> <?= htmlspecialchars(t('notifications.preferences.reminder_day', ['count' => $day], $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></label>
                            <?php endforeach; ?>
                        </span>
                        <?php endif; ?>
                        <?php if (in_array($eventKey, $eventStartReminderEventKeys, true)): ?>
                        <label class="omo-notification-preferences-grid__reminder-select">
                            <span><?= htmlspecialchars($translate('notifications.preferences.reminder_days'), ENT_QUOTES, 'UTF-8') ?></span>
                            <select name="preferences[<?= htmlspecialchars($eventKey, ENT_QUOTES, 'UTF-8') ?>][lead_time]" class="generic-form-control">
                                <option value=""><?= htmlspecialchars($translate('notifications.preferences.reminder_none'), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php foreach ($eventReminderLeadTimes as $leadTime): ?>
                                <option value="<?= htmlspecialchars($leadTime, ENT_QUOTES, 'UTF-8') ?>"<?= ($channels['lead_time'] ?? '') === $leadTime ? ' selected' : '' ?>><?= htmlspecialchars($translate('notifications.preferences.reminder_option.' . $leadTime), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <?php endif; ?>
                    </span>
                    <?php foreach ($preferenceChannels as $channelKey): ?>
                    <label class="omo-notification-preferences-grid__channel"><input type="checkbox" name="preferences[<?= htmlspecialchars($eventKey, ENT_QUOTES, 'UTF-8') ?>][<?= htmlspecialchars($channelKey, ENT_QUOTES, 'UTF-8') ?>]" value="1"<?= !empty($channels[$channelKey]) ? ' checked' : '' ?><?= $channelKey === 'telegram' && !$telegramAvailable ? ' disabled' : '' ?> aria-label="<?= htmlspecialchars($translate('notifications.preferences.channel.' . $channelKey) . ' - ' . $eventLabel, ENT_QUOTES, 'UTF-8') ?>"></label>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
        <div class="generic-form-actions">
            <button type="submit" class="generic-action-button generic-action-button--main"><?= htmlspecialchars($translate('notifications.preferences.save'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
        <p class="generic-feedback" data-omo-notification-preferences-feedback aria-live="polite"></p>
    </form>
    <?php endif; ?>
</div>
<?= commonPageScriptTags('/omo/api/parameters/notifications/index.js', [
    'configuration' => $configuration,
    'notificationsPreferencesSaved' => $translate('notifications.preferences.saved'),
    'message' => $translate('notifications.preferences.save_error'),
]) ?>
