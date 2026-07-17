<?php

require_once dirname(__DIR__, 3) . '/common/caldav.php';

header('Vary: Authorization');
header('X-Robots-Tag: noindex, nofollow');

$viewer = commonCalDavAuthenticateRequest();
if (!$viewer) {
    commonCalDavSendUnauthorized();
}

$resource = commonCalDavResolveRouteResource($viewer, commonCalDavGetRoutePath());
if (!is_array($resource) || empty($resource['type'])) {
    commonCalDavSendStatusText(404, 'CalDAV resource not found.');
}

$resourceType = (string)$resource['type'];
$allowedMethods = array('OPTIONS', 'PROPFIND');

if ($resourceType === 'calendar') {
    $allowedMethods[] = 'REPORT';
    $allowedMethods[] = 'GET';
    $allowedMethods[] = 'HEAD';
} elseif ($resourceType === 'event') {
    $allowedMethods[] = 'GET';
    $allowedMethods[] = 'HEAD';
} else {
    $allowedMethods[] = 'GET';
    $allowedMethods[] = 'HEAD';
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method === 'OPTIONS') {
    commonCalDavSendOptions($allowedMethods);
}

if (!in_array($method, $allowedMethods, true)) {
    header('Allow: ' . implode(', ', $allowedMethods));
    commonCalDavSendStatusText(405, 'Method not allowed.');
}

if ($method === 'PROPFIND') {
    commonCalDavHandlePropfind($viewer, $resource);
}

if ($method === 'REPORT') {
    commonCalDavHandleReport($viewer, $resource);
}

if ($resourceType === 'event' && ($method === 'GET' || $method === 'HEAD')) {
    commonCalDavSendEvent($resource, $method === 'GET');
}

http_response_code(200);
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($method === 'GET') {
    echo "OpenMyOrganization CalDAV endpoint.\n";
    echo 'Principal: ' . commonCalDavBuildHref('principals/' . (int)$viewer->getId() . '/') . "\n";
    echo 'Calendar home: ' . commonCalDavBuildHref('calendars/' . (int)$viewer->getId() . '/') . "\n";
}
