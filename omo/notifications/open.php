<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__, 2) . '/common/auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');

commonRestoreRememberedUser();

if (commonGetCurrentUserId() <= 0) {
    require dirname(__DIR__) . '/index.php';
    exit;
}

$userId = commonGetCurrentUserId();
$notificationId = (int)($_GET['id'] ?? 0);
$token = trim((string)($_GET['token'] ?? ''));
$notification = $notificationId > 0
    ? \dbObject\Notification::findForUser($notificationId, $userId)
    : \dbObject\Notification::findByOpenToken($token);

if (!($notification instanceof \dbObject\Notification) || (int)$notification->get('IDuser') !== $userId) {
    header('Location: /omo/', true, 302);
    exit;
}

$targetUrl = trim((string)$notification->get('url'));
\dbObject\Notification::markReadForUser($userId, (int)$notification->get('IDorganization'), (int)$notification->getId());

if ($targetUrl === '' || substr($targetUrl, 0, 1) !== '/' || substr($targetUrl, 0, 2) === '//') {
    $targetUrl = '/omo/';
}

header('Location: ' . $targetUrl, true, 302);
exit;
