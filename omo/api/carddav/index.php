<?php

require_once dirname(__DIR__, 3) . '/common/carddav.php';

header('Vary: Authorization');
header('X-Robots-Tag: noindex, nofollow');

$viewer = commonCardDavAuthenticateRequest();
if (!$viewer) {
    commonCardDavSendUnauthorized();
}

$debugMode = trim((string)($_GET['debug'] ?? ''));
if ($debugMode === 'contacts' && commonCardDavCanExposeDebug()) {
    http_response_code(200);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo json_encode(
        commonCardDavBuildContactsDebugSummary($viewer),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    );
    exit;
}

$resource = commonCardDavResolveRouteResource($viewer, commonCardDavGetRoutePath());
if (!is_array($resource) || empty($resource['type'])) {
    commonCardDavSendStatusText(404, 'CardDAV resource not found.');
}

$resourceType = (string)$resource['type'];
$allowedMethods = array('OPTIONS', 'PROPFIND');

if ($resourceType === 'addressbook') {
    $allowedMethods[] = 'REPORT';
    $allowedMethods[] = 'GET';
    $allowedMethods[] = 'HEAD';
} elseif ($resourceType === 'card') {
    $allowedMethods[] = 'GET';
    $allowedMethods[] = 'HEAD';
} else {
    $allowedMethods[] = 'GET';
    $allowedMethods[] = 'HEAD';
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method === 'OPTIONS') {
    commonCardDavSendOptions($allowedMethods);
}

if (!in_array($method, $allowedMethods, true)) {
    header('Allow: ' . implode(', ', $allowedMethods));
    commonCardDavSendStatusText(405, 'Method not allowed.');
}

if ($method === 'PROPFIND') {
    commonCardDavHandlePropfind($viewer, $resource);
}

if ($method === 'REPORT') {
    commonCardDavHandleReport($viewer, $resource);
}

if ($resourceType === 'card' && ($method === 'GET' || $method === 'HEAD')) {
    commonCardDavSendCard($resource, $method === 'GET');
}

http_response_code(200);
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($method === 'GET') {
    echo "OpenMyOrganization CardDAV endpoint.\n";
    echo 'Principal: ' . commonCardDavBuildHref('principals/' . (int)$viewer->getId() . '/') . "\n";
    echo 'Addressbook: ' . commonCardDavBuildHref('addressbooks/' . (int)$viewer->getId() . '/members/') . "\n";
}
