<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/notification_center.php';

header('Content-Type: application/json; charset=UTF-8');

function omoNotificationPreferencesRespond($statusCode, array $payload)
{
    http_response_code((int)$statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = commonGetCurrentUserId();
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
if ($userId <= 0 || $organizationId <= 0) {
    omoNotificationPreferencesRespond(403, ['status' => false, 'message' => 'Contexte utilisateur ou organisation indisponible.']);
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    omoNotificationPreferencesRespond(405, ['status' => false, 'message' => 'Methode non autorisee.']);
}
if (!hash_equals((string)($_SESSION['omo_notification_push_csrf'] ?? ''), (string)($_POST['csrf_token'] ?? ''))) {
    omoNotificationPreferencesRespond(403, ['status' => false, 'message' => 'Verification de securite invalide.']);
}

$preferences = isset($_POST['preferences']) && is_array($_POST['preferences']) ? $_POST['preferences'] : [];
$pushConfigured = is_array(webPushGetVapidConfiguration());
$telegramConfigured = defined('TOKEN') && trim((string)TOKEN) !== '';
$eventKeys = [];
foreach (notificationCenterGetActiveEventGroups($organizationId, $userId) as $eventGroup) {
    $eventKeys = array_merge($eventKeys, $eventGroup['eventKeys'] ?? []);
}
foreach (array_values(array_unique($eventKeys)) as $eventKey) {
    $values = isset($preferences[$eventKey]) && is_array($preferences[$eventKey]) ? $preferences[$eventKey] : [];
    $channels = \dbObject\NotificationPreference::getChannelsFor($userId, $organizationId, $eventKey);
    if ($pushConfigured) {
        $channels['push'] = !empty($values['push']);
    }
    if ($telegramConfigured) {
        $channels['telegram'] = !empty($values['telegram']);
    }
    $channels['email'] = !empty($values['email']);
    $channels['days'] = isset($values['days']) && is_array($values['days']) ? $values['days'] : [];
    $channels['lead_time'] = trim((string)($values['lead_time'] ?? ''));
    if (!\dbObject\NotificationPreference::saveChannels($userId, $organizationId, $eventKey, $channels)) {
        omoNotificationPreferencesRespond(500, ['status' => false, 'message' => 'Impossible d enregistrer les reglages de notifications.']);
    }
}

omoNotificationPreferencesRespond(200, ['status' => true]);
