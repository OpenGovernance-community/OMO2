<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');

$token = trim((string)($_GET['token'] ?? ''));
$notification = \dbObject\Notification::findByOpenToken($token);
$targetUrl = $notification instanceof \dbObject\Notification
    ? trim((string)$notification->get('url'))
    : '';

if ($notification instanceof \dbObject\Notification) {
    \dbObject\Notification::markReadByOpenToken($token);
}

if ($targetUrl === '' || substr($targetUrl, 0, 1) !== '/' || substr($targetUrl, 0, 2) === '//') {
    $targetUrl = '/omo/';
}

header('Location: ' . $targetUrl, true, 302);
exit;
