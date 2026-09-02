<?php

require_once dirname(__DIR__, 3) . '/common/caldav.php';

header('Vary: Authorization');
header('X-Robots-Tag: noindex, nofollow');

$viewer = commonCalDavAuthenticateRequest();
if (!$viewer) {
    commonCalDavSendUnauthorized();
}

$routePath = commonCalDavGetRoutePath();
$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$scope = commonCalDavParseScopedRoute($routePath);
$cacheHandle = null;

if (
    is_array($scope)
    && in_array($method, array('PROPFIND', 'REPORT', 'GET'), true)
    && commonCalDavCanUseScopedResponseCache($viewer, $scope)
) {
    $rawInput = file_get_contents('php://input');
    $rawInput = is_string($rawInput) ? $rawInput : '';
    $GLOBALS['commonCardDavRawInput'] = $rawInput;

    $organizationId = (int)$scope['organizationId'];
    $revision = \dbObject\CalDavCache::getOrganizationRevision($organizationId);
    $cacheKey = \dbObject\CalDavCache::buildResponseKey(array(
        'schema' => 1,
        'viewer' => (int)$viewer->getId(),
        'organization' => $organizationId,
        'revision' => $revision,
        'method' => $method,
        'route' => '/' . trim((string)$routePath, '/'),
        'depth' => trim((string)($_SERVER['HTTP_DEPTH'] ?? '')),
        'body' => hash('sha256', $rawInput),
    ));

    $cachedResponse = \dbObject\CalDavCache::readResponse($organizationId, $revision, $cacheKey);
    if (is_array($cachedResponse)) {
        \dbObject\CalDavCache::sendResponse($cachedResponse, 'HIT');
        exit;
    }

    $cacheHandle = \dbObject\CalDavCache::acquireBuildLock($organizationId, $revision, $cacheKey);
    if (is_resource($cacheHandle)) {
        $cachedResponse = \dbObject\CalDavCache::readResponse($organizationId, $revision, $cacheKey);
        if (is_array($cachedResponse)) {
            \dbObject\CalDavCache::releaseBuildLock($cacheHandle);
            \dbObject\CalDavCache::sendResponse($cachedResponse, 'HIT');
            exit;
        }

        ob_start();
        header('X-OMO-CalDAV-Cache: MISS');
        register_shutdown_function(static function () use ($organizationId, $revision, $cacheKey, $cacheHandle) {
            $body = ob_get_contents();
            $status = http_response_code();
            $lastError = error_get_last();
            $fatalTypes = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
            $hasFatalError = is_array($lastError) && in_array((int)($lastError['type'] ?? 0), $fatalTypes, true);

            if (
                !$hasFatalError
                && is_string($body)
                && is_int($status)
                && $status >= 200
                && $status < 300
            ) {
                \dbObject\CalDavCache::writeResponse($organizationId, $revision, $cacheKey, array(
                    'status' => $status,
                    'headers' => headers_list(),
                    'body' => $body,
                ));
            }

            \dbObject\CalDavCache::releaseBuildLock($cacheHandle);
        });
    } else {
        $cachedResponse = \dbObject\CalDavCache::readResponse($organizationId, $revision, $cacheKey);
        if (is_array($cachedResponse)) {
            \dbObject\CalDavCache::sendResponse($cachedResponse, 'HIT');
            exit;
        }
        header('X-OMO-CalDAV-Cache: BYPASS');
    }
}

$resource = commonCalDavResolveRouteResource($viewer, $routePath);
if (!is_array($resource) || empty($resource['type'])) {
    commonCalDavSendStatusText(404, 'CalDAV resource not found.');
}

$resourceType = (string)$resource['type'];
$allowedMethods = array('OPTIONS', 'PROPFIND');

if ($resourceType === 'calendar') {
    $allowedMethods[] = 'REPORT';
    $allowedMethods[] = 'GET';
    $allowedMethods[] = 'HEAD';
    if (empty($resource['readOnly'])) {
        $allowedMethods[] = 'PUT';
    }
} elseif ($resourceType === 'event') {
    $allowedMethods[] = 'GET';
    $allowedMethods[] = 'HEAD';
    if (empty($resource['readOnly'])) {
        $allowedMethods[] = 'PUT';
    }
} else {
    $allowedMethods[] = 'GET';
    $allowedMethods[] = 'HEAD';
}

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

if ($resourceType === 'event' && $method === 'PUT') {
    commonCalDavHandleEventPut($viewer, $resource);
}

if ($resourceType === 'calendar' && $method === 'PUT') {
    commonCalDavSendStatusText(405, 'Creating calendar events is not supported.');
}

http_response_code(200);
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($method === 'GET') {
    echo "OpenMyOrganization CalDAV endpoint.\n";
    echo 'Principal: ' . (string)($resource['principalHref'] ?? commonCalDavBuildHref('principals/' . (int)$viewer->getId() . '/')) . "\n";
    echo 'Calendar home: ' . (string)($resource['calendarHomeHref'] ?? commonCalDavBuildHref('calendars/' . (int)$viewer->getId() . '/')) . "\n";
}
