<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/web_push.php';

header('Content-Type: application/json; charset=UTF-8');

function omoPushSubscriptionRespond($statusCode, array $payload)
{
    http_response_code((int)$statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = commonGetCurrentUserId();
if ($userId <= 0) {
    omoPushSubscriptionRespond(401, ['status' => false, 'message' => 'Connexion requise.']);
}

$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($requestMethod, ['POST', 'DELETE'], true)) {
    omoPushSubscriptionRespond(405, ['status' => false, 'message' => 'Methode non autorisee.']);
}

$rawBody = file_get_contents('php://input');
$input = json_decode(is_string($rawBody) ? $rawBody : '', true);
if (!is_array($input)) {
    omoPushSubscriptionRespond(400, ['status' => false, 'message' => 'Donnees de notification invalides.']);
}

$csrfToken = trim((string)($input['csrf_token'] ?? ''));
$expectedCsrfToken = trim((string)($_SESSION['omo_notification_push_csrf'] ?? ''));
if ($csrfToken === '' || $expectedCsrfToken === '' || !hash_equals($expectedCsrfToken, $csrfToken)) {
    omoPushSubscriptionRespond(403, ['status' => false, 'message' => 'Verification de securite invalide.']);
}

$subscription = isset($input['subscription']) && is_array($input['subscription']) ? $input['subscription'] : [];
if (!is_array(\dbObject\NotificationPushSubscription::normalizeSubscription($subscription))) {
    omoPushSubscriptionRespond(422, ['status' => false, 'message' => 'Souscription navigateur invalide.']);
}

if ($requestMethod === 'DELETE') {
    $saved = \dbObject\NotificationPushSubscription::deactivateForUser($userId, $subscription);
    omoPushSubscriptionRespond($saved ? 200 : 500, [
        'status' => (bool)$saved,
        'message' => $saved ? 'Notifications desactivees pour cet appareil.' : 'Impossible de desactiver les notifications.',
    ]);
}

$savedSubscription = \dbObject\NotificationPushSubscription::upsertForUser(
    $userId,
    $subscription,
    (string)($_SERVER['HTTP_USER_AGENT'] ?? '')
);
omoPushSubscriptionRespond($savedSubscription instanceof \dbObject\NotificationPushSubscription ? 200 : 500, [
    'status' => $savedSubscription instanceof \dbObject\NotificationPushSubscription,
    'message' => $savedSubscription instanceof \dbObject\NotificationPushSubscription
        ? 'Notifications activees pour cet appareil.'
        : 'Impossible d enregistrer les notifications pour cet appareil.',
]);
?>
