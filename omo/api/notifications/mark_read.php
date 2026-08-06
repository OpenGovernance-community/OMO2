<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

function omoNotificationMarkReadRespond($statusCode, array $payload)
{
    http_response_code((int)$statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = commonGetCurrentUserId();
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
if ($userId <= 0 || $organizationId <= 0) {
    omoNotificationMarkReadRespond(403, ['status' => false, 'message' => 'Connexion ou organisation requise.']);
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    omoNotificationMarkReadRespond(405, ['status' => false, 'message' => 'Methode non autorisee.']);
}
$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input) || !hash_equals((string)($_SESSION['omo_notification_inbox_csrf'] ?? ''), (string)($input['csrf_token'] ?? ''))) {
    omoNotificationMarkReadRespond(403, ['status' => false, 'message' => 'Verification de securite invalide.']);
}

$notificationId = (int)($input['notification_id'] ?? 0);
$url = trim((string)($input['url'] ?? ''));
$marked = \dbObject\Notification::markReadForUser($userId, $organizationId, $notificationId, $url);
omoNotificationMarkReadRespond($marked ? 200 : 422, ['status' => (bool)$marked]);
