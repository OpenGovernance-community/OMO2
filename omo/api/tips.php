<?php
require_once __DIR__ . '/bootstrap.php';

$userId = (int)commonGetCurrentUserId();
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
if ($userId <= 0 || commonGetCurrentShareLink() || !commonUserHasOrganizationAccess($userId, $organizationId)) {
    http_response_code(403);
    exit;
}
commonReleaseReadOnlySession();
header('Content-Type: application/json; charset=UTF-8');
$today = new DateTimeImmutable('today', new DateTimeZone('Europe/Zurich'));
$cookieName = 'omo_tip_' . $userId;
$cookie = json_decode((string)($_COOKIE[$cookieName] ?? ''), true);
$currentId = is_array($cookie) && ($cookie['day'] ?? '') === $today->format('Y-m-d') ? (int)($cookie['id'] ?? 0) : 0;
$next = ($_GET['next'] ?? '') === '1';
$tip = \dbObject\Tip::pick($currentId, $next);
echo json_encode(['tip' => $tip?->toPayload(), 'cookieName' => $cookieName,
    'day' => $today->format('Y-m-d'), 'expires' => $today->modify('+1 day')->setTimezone(new DateTimeZone('UTC'))->format('D, d M Y H:i:s \\G\\M\\T')],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
