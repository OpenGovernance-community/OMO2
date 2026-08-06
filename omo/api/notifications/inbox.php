<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

function omoNotificationInboxRespond($statusCode, array $payload)
{
    http_response_code((int)$statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = commonGetCurrentUserId();
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
if ($userId <= 0 || $organizationId <= 0) {
    omoNotificationInboxRespond(403, ['status' => false, 'message' => 'Connexion ou organisation requise.']);
}

$items = [];
foreach (\dbObject\Notification::getInboxForUser($userId, $organizationId, 30) as $notification) {
    $readAt = $notification->get('read_at');
    $createdAt = $notification->get('created_at');
    $items[] = [
        'id' => (int)$notification->getId(),
        'title' => (string)$notification->get('title'),
        'body' => (string)$notification->get('body'),
        'url' => (string)$notification->get('url'),
        'readAt' => $readAt instanceof \DateTimeInterface ? $readAt->format(DATE_ATOM) : '',
        'createdAt' => $createdAt instanceof \DateTimeInterface ? $createdAt->format(DATE_ATOM) : '',
    ];
}

omoNotificationInboxRespond(200, [
    'status' => true,
    'unreadCount' => \dbObject\Notification::countUnreadForUser($userId, $organizationId),
    'notifications' => $items,
]);
