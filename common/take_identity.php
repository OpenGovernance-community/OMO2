<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once __DIR__ . '/auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}

$token = (string)($_POST['csrf'] ?? '');
$expectedToken = (string)($_SESSION['take_identity_csrf'] ?? '');
if ($token === '' || $expectedToken === '' || !hash_equals($expectedToken, $token)) {
    http_response_code(403);
    exit;
}

$adminUserId = (int)commonGetCurrentUserId();
$targetUserId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$organizationId = filter_var($_POST['organization_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!commonCurrentUserIsSiteAdminModeEnabled()
    || $targetUserId === false
    || $organizationId === false
    || $targetUserId === $adminUserId
    || $organizationId !== (int)($_SESSION['currentOrganization'] ?? 0)
) {
    http_response_code(403);
    exit;
}

$organization = new \dbObject\Organization();
$targetUser = new \dbObject\User();
if (!$organization->load($organizationId)
    || !$targetUser->load($targetUserId)
    || !(bool)$targetUser->get('active')
    || $targetUser->isHistoricalPlaceholder()
    || !\dbObject\UserOrganization::hasActiveMembership($targetUserId, $organizationId)
) {
    http_response_code(403);
    exit;
}

commonAuthSecurityLog('take_identity', 'success', [
    'user_id' => $adminUserId,
    'target_user_id' => $targetUserId,
    'organization_id' => $organizationId,
]);

commonExpireCookieValue(commonGetRememberCookieName(), true);
commonExpireLegacyRememberCookie();
commonExpireLegacyAuthCookies();
session_regenerate_id(true);
$_SESSION = [
    'currentUser' => $targetUserId,
    'currentOrganization' => $organizationId,
];
session_write_close();

header('Location: ' . commonBuildOrganizationEntryPath($organizationId), true, 303);
exit;
