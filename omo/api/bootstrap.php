<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__, 2) . '/common/auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

commonResolveOrganizationContext((int)($_SESSION['currentOrganization'] ?? 1));
commonRestoreRememberedUser();

if (!commonGetCurrentUserId() && !commonCanAccessWithoutLogin()) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

if (!commonCanAccessWithoutLogin() && !commonCurrentUserHasOrganizationAccess()) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}
