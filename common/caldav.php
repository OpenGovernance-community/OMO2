<?php

require_once __DIR__ . '/carddav.php';

use dbObject\ArrayEvent;
use dbObject\ArrayUserOrganization;
use dbObject\Event;
use dbObject\Organization;
use dbObject\User;

if (!function_exists('commonCalDavServicePath')) {
    function commonCalDavServicePath()
    {
        return '/omo/api/caldav';
    }
}

if (!function_exists('commonCalDavNamespaceMap')) {
    function commonCalDavNamespaceMap()
    {
        return array(
            'd' => 'DAV:',
            'cal' => 'urn:ietf:params:xml:ns:caldav',
            'cs' => 'http://calendarserver.org/ns/',
        );
    }
}

if (!function_exists('commonCalDavAuthRealm')) {
    function commonCalDavAuthRealm()
    {
        return 'OpenMyOrganization CalDAV';
    }
}

if (!function_exists('commonCalDavAuthenticateRequest')) {
    function commonCalDavAuthenticateRequest()
    {
        return commonCardDavAuthenticateRequest();
    }
}

if (!function_exists('commonCalDavBuildHref')) {
    function commonCalDavBuildHref($suffix = '')
    {
        $base = rtrim(commonCalDavServicePath(), '/');
        $suffix = trim((string)$suffix);

        if ($suffix === '' || $suffix === '/') {
            return $base . '/';
        }

        return $base . '/' . ltrim($suffix, '/');
    }
}

if (!function_exists('commonCalDavGetRequestPath')) {
    function commonCalDavGetRequestPath()
    {
        $requestUri = (string)($_SERVER['REQUEST_URI'] ?? commonCalDavBuildHref());
        $path = parse_url($requestUri, PHP_URL_PATH);
        return is_string($path) && $path !== '' ? $path : commonCalDavBuildHref();
    }
}

if (!function_exists('commonCalDavGetRoutePath')) {
    function commonCalDavGetRoutePath()
    {
        $path = commonCalDavGetRequestPath();
        $base = rtrim(commonCalDavServicePath(), '/');

        if (strpos($path, $base) !== 0) {
            return '/';
        }

        $relative = substr($path, strlen($base));
        if (!is_string($relative) || $relative === '') {
            return '/';
        }

        return '/' . ltrim($relative, '/');
    }
}

if (!function_exists('commonCalDavSendUnauthorized')) {
    function commonCalDavSendUnauthorized()
    {
        http_response_code(401);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('WWW-Authenticate: Basic realm="' . addslashes(commonCalDavAuthRealm()) . '", charset="UTF-8"');
        commonCardDavSendAuthDebugHeaders();
        echo 'Authentication required.';
        exit;
    }
}

if (!function_exists('commonCalDavSendStatusText')) {
    function commonCalDavSendStatusText($statusCode, $message)
    {
        http_response_code((int)$statusCode);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        commonCardDavSendDebugHeaders();
        echo (string)$message;
        exit;
    }
}

if (!function_exists('commonCalDavSendOptions')) {
    function commonCalDavSendOptions(array $allowedMethods)
    {
        $allow = implode(', ', array_values(array_unique(array_map('strtoupper', $allowedMethods))));
        header('Allow: ' . $allow);
        header('DAV: 1, 2, access-control, calendar-access');
        header('MS-Author-Via: DAV');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        commonCardDavSendDebugHeaders();
        http_response_code(204);
        exit;
    }
}

if (!function_exists('commonCalDavNormalizeDepth')) {
    function commonCalDavNormalizeDepth()
    {
        $depthHeader = strtolower(trim((string)($_SERVER['HTTP_DEPTH'] ?? '0')));
        if ($depthHeader === '1') {
            return 1;
        }
        return 0;
    }
}

if (!function_exists('commonCalDavCreateDom')) {
    function commonCalDavCreateDom($rootLocalName, $rootNamespace = 'DAV:', $rootPrefix = 'd')
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $root = $document->createElementNS($rootNamespace, $rootPrefix . ':' . $rootLocalName);
        foreach (commonCalDavNamespaceMap() as $prefix => $namespace) {
            if ($prefix === $rootPrefix && $namespace === $rootNamespace) {
                continue;
            }
            $root->setAttribute('xmlns:' . $prefix, $namespace);
        }
        $document->appendChild($root);
        return array($document, $root);
    }
}

if (!function_exists('commonCalDavAppendHref')) {
    function commonCalDavAppendHref(DOMDocument $document, DOMElement $parent, $href)
    {
        $element = $document->createElementNS('DAV:', 'd:href');
        $element->appendChild($document->createTextNode((string)$href));
        $parent->appendChild($element);
        return $element;
    }
}

if (!function_exists('commonCalDavAppendPropertyValue')) {
    function commonCalDavAppendPropertyValue(DOMDocument $document, DOMElement $propertyElement, array $value)
    {
        $type = (string)($value['type'] ?? 'text');

        if ($type === 'text') {
            $propertyElement->appendChild($document->createTextNode((string)($value['value'] ?? '')));
            return;
        }

        if ($type === 'href') {
            commonCalDavAppendHref($document, $propertyElement, (string)($value['value'] ?? ''));
            return;
        }

        if ($type === 'href-list') {
            foreach ((array)($value['value'] ?? array()) as $href) {
                commonCalDavAppendHref($document, $propertyElement, (string)$href);
            }
            return;
        }

        if ($type === 'resourcetype') {
            foreach ((array)($value['value'] ?? array()) as $resourceType) {
                $namespace = (string)($resourceType['namespace'] ?? 'DAV:');
                $prefix = (string)($resourceType['prefix'] ?? 'd');
                $localName = (string)($resourceType['localName'] ?? '');
                if ($localName === '') {
                    continue;
                }
                $propertyElement->appendChild($document->createElementNS($namespace, $prefix . ':' . $localName));
            }
            return;
        }

        if ($type === 'supported-report-set') {
            foreach ((array)($value['value'] ?? array()) as $report) {
                $reportElement = $document->createElementNS('DAV:', 'd:supported-report');
                $nameElement = $document->createElementNS('DAV:', 'd:report');
                $namespace = (string)($report['namespace'] ?? 'DAV:');
                $prefix = (string)($report['prefix'] ?? 'd');
                $localName = (string)($report['localName'] ?? '');
                if ($localName === '') {
                    continue;
                }
                $nameElement->appendChild($document->createElementNS($namespace, $prefix . ':' . $localName));
                $reportElement->appendChild($nameElement);
                $propertyElement->appendChild($reportElement);
            }
            return;
        }

        if ($type === 'raw-xml') {
            $xml = trim((string)($value['value'] ?? ''));
            if ($xml === '') {
                return;
            }

            $fragment = $document->createDocumentFragment();
            $fragment->appendXML($xml);
            $propertyElement->appendChild($fragment);
        }
    }
}

if (!function_exists('commonCalDavRenderPropstat')) {
    function commonCalDavRenderPropstat(DOMDocument $document, DOMElement $responseElement, array $properties, $statusLine, $propnameMode = false)
    {
        if (count($properties) === 0) {
            return;
        }

        $propstatElement = $document->createElementNS('DAV:', 'd:propstat');
        $propElement = $document->createElementNS('DAV:', 'd:prop');

        foreach ($properties as $qname => $value) {
            if (!preg_match('/^\{(.*)\}([^}]+)$/', (string)$qname, $matches)) {
                continue;
            }

            $namespace = (string)$matches[1];
            $localName = (string)$matches[2];
            $prefix = 'x';
            foreach (commonCalDavNamespaceMap() as $mappedPrefix => $mappedNamespace) {
                if ($mappedNamespace === $namespace) {
                    $prefix = $mappedPrefix;
                    break;
                }
            }

            $propertyElement = $document->createElementNS($namespace, $prefix . ':' . $localName);
            if (!$propnameMode && is_array($value) && !empty($value['type'])) {
                commonCalDavAppendPropertyValue($document, $propertyElement, $value);
            }
            $propElement->appendChild($propertyElement);
        }

        $propstatElement->appendChild($propElement);
        $statusElement = $document->createElementNS('DAV:', 'd:status');
        $statusElement->appendChild($document->createTextNode((string)$statusLine));
        $propstatElement->appendChild($statusElement);
        $responseElement->appendChild($propstatElement);
    }
}

if (!function_exists('commonCalDavSendMultistatus')) {
    function commonCalDavSendMultistatus(array $responses, $propnameMode = false, $extraTopLevel = array())
    {
        list($document, $root) = commonCalDavCreateDom('multistatus');

        foreach ($responses as $response) {
            $responseElement = $document->createElementNS('DAV:', 'd:response');
            commonCalDavAppendHref($document, $responseElement, (string)($response['href'] ?? '/'));

            if (!empty($response['statusOnly'])) {
                $statusElement = $document->createElementNS('DAV:', 'd:status');
                $statusElement->appendChild($document->createTextNode((string)$response['statusOnly']));
                $responseElement->appendChild($statusElement);
                $root->appendChild($responseElement);
                continue;
            }

            commonCalDavRenderPropstat(
                $document,
                $responseElement,
                (array)($response['ok'] ?? array()),
                'HTTP/1.1 200 OK',
                $propnameMode
            );
            commonCalDavRenderPropstat(
                $document,
                $responseElement,
                (array)($response['missing'] ?? array()),
                'HTTP/1.1 404 Not Found',
                $propnameMode
            );

            $root->appendChild($responseElement);
        }

        foreach ($extraTopLevel as $elementDefinition) {
            $namespace = (string)($elementDefinition['namespace'] ?? 'DAV:');
            $prefix = (string)($elementDefinition['prefix'] ?? 'd');
            $localName = (string)($elementDefinition['localName'] ?? '');
            if ($localName === '') {
                continue;
            }
            $element = $document->createElementNS($namespace, $prefix . ':' . $localName);
            $element->appendChild($document->createTextNode((string)($elementDefinition['value'] ?? '')));
            $root->appendChild($element);
        }

        http_response_code(207);
        header('Content-Type: application/xml; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        commonCardDavSendDebugHeaders();
        echo $document->saveXML();
        exit;
    }
}

if (!function_exists('commonCalDavEscapeText')) {
    function commonCalDavEscapeText($value)
    {
        $value = str_replace("\r\n", "\n", (string)$value);
        $value = str_replace("\r", "\n", $value);
        $value = str_replace("\\", "\\\\", $value);
        $value = str_replace(';', '\;', $value);
        $value = str_replace(',', '\,', $value);
        return str_replace("\n", '\\n', $value);
    }
}

if (!function_exists('commonCalDavFoldLine')) {
    function commonCalDavFoldLine($line)
    {
        return commonCardDavFoldVCardLine($line);
    }
}

if (!function_exists('commonCalDavBuildEventUid')) {
    function commonCalDavBuildEventUid(Event $event)
    {
        $host = preg_replace('/:\d+$/', '', commonGetRequestHost());
        if ($host === '') {
            $host = 'localhost';
        }

        return 'omo-event-' . (int)$event->getId() . '@' . $host;
    }
}

if (!function_exists('commonCalDavResolveEventTimestamp')) {
    function commonCalDavResolveEventTimestamp(Event $event, $fieldNames)
    {
        foreach ((array)$fieldNames as $fieldName) {
            $value = $event->get((string)$fieldName);
            if ($value instanceof DateTimeInterface) {
                return DateTimeImmutable::createFromInterface($value);
            }
        }

        return null;
    }
}

if (!function_exists('commonCalDavResolveEventCreatedAt')) {
    function commonCalDavResolveEventCreatedAt(Event $event)
    {
        return commonCalDavResolveEventTimestamp($event, array('created_at', 'start_at', 'end_at'));
    }
}

if (!function_exists('commonCalDavResolveEventUpdatedAt')) {
    function commonCalDavResolveEventUpdatedAt(Event $event)
    {
        return commonCalDavResolveEventTimestamp($event, array('updated_at', 'created_at', 'start_at', 'end_at'));
    }
}

if (!function_exists('commonCalDavNormalizeEventStatus')) {
    function commonCalDavNormalizeEventStatus(Event $event)
    {
        $status = trim((string)$event->get('status'));

        if ($status === Event::STATUS_CONFIRMED) {
            return 'CONFIRMED';
        }

        if ($status === Event::STATUS_CANCELLED) {
            return 'CANCELLED';
        }

        return 'TENTATIVE';
    }
}

if (!function_exists('commonCalDavBuildTimedDateLine')) {
    function commonCalDavBuildTimedDateLine($label, ?DateTimeInterface $dateTime = null)
    {
        return $label . ':' . commonCardDavFormatTimestamp($dateTime);
    }
}

if (!function_exists('commonCalDavBuildAllDayDateLine')) {
    function commonCalDavBuildAllDayDateLine($label, ?DateTimeInterface $dateTime = null)
    {
        if (!$dateTime) {
            return $label . ';VALUE=DATE:';
        }

        $value = DateTimeImmutable::createFromInterface($dateTime)->format('Ymd');
        return $label . ';VALUE=DATE:' . $value;
    }
}

if (!function_exists('commonCalDavResolveAllDayEndExclusive')) {
    function commonCalDavResolveAllDayEndExclusive(Event $event)
    {
        $startAt = $event->get('start_at');
        $endAt = $event->get('end_at');

        if (!($startAt instanceof DateTimeInterface)) {
            return null;
        }

        $start = DateTimeImmutable::createFromInterface($startAt)->setTime(0, 0, 0);
        $end = $endAt instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($endAt)->setTime(0, 0, 0)
            : $start;

        if ($end < $start) {
            $end = $start;
        }

        return $end->modify('+1 day');
    }
}

if (!function_exists('commonCalDavBuildEventCalendarData')) {
    function commonCalDavBuildEventCalendarData(Organization $organization, Event $event)
    {
        $updatedAt = commonCalDavResolveEventUpdatedAt($event);
        $createdAt = commonCalDavResolveEventCreatedAt($event);
        $title = trim((string)$event->get('title'));
        $description = trim((string)$event->get('description'));
        $lines = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//OpenMyOrganization//CalDAV//EN',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:' . commonCalDavBuildEventUid($event),
            'DTSTAMP:' . commonCardDavFormatTimestamp($updatedAt ?: $createdAt ?: new DateTimeImmutable('now', new DateTimeZone('UTC'))),
            'SUMMARY:' . commonCalDavEscapeText($title !== '' ? $title : ('Event ' . (int)$event->getId())),
            'STATUS:' . commonCalDavNormalizeEventStatus($event),
            'TRANSP:OPAQUE',
        );

        if ($createdAt) {
            $lines[] = 'CREATED:' . commonCardDavFormatTimestamp($createdAt);
        }

        if ($updatedAt) {
            $lines[] = 'LAST-MODIFIED:' . commonCardDavFormatTimestamp($updatedAt);
        }

        if ($description !== '') {
            $lines[] = 'DESCRIPTION:' . commonCalDavEscapeText($description);
        }

        $organizationName = trim((string)$organization->get('name'));
        if ($organizationName !== '') {
            $lines[] = 'CATEGORIES:' . commonCalDavEscapeText($organizationName);
        }

        $startAt = $event->get('start_at');
        $endAt = $event->get('end_at');
        $isAllDay = (int)$event->get('is_all_day') === 1;

        if ($isAllDay) {
            $lines[] = commonCalDavBuildAllDayDateLine('DTSTART', $startAt instanceof DateTimeInterface ? $startAt : null);
            $lines[] = commonCalDavBuildAllDayDateLine('DTEND', commonCalDavResolveAllDayEndExclusive($event));
        } else {
            $lines[] = commonCalDavBuildTimedDateLine('DTSTART', $startAt instanceof DateTimeInterface ? $startAt : null);
            $lines[] = commonCalDavBuildTimedDateLine('DTEND', $endAt instanceof DateTimeInterface ? $endAt : null);
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        $foldedLines = array();
        foreach ($lines as $line) {
            $foldedLines[] = commonCalDavFoldLine($line);
        }

        return implode("\r\n", $foldedLines) . "\r\n";
    }
}

if (!function_exists('commonCalDavBuildEventResource')) {
    function commonCalDavBuildEventResource(User $viewer, Organization $organization, Event $event)
    {
        $viewerUserId = (int)$viewer->getId();
        $organizationId = (int)$organization->getId();
        $eventId = (int)$event->getId();
        $calendarSlug = 'organization-' . $organizationId;
        $fileName = 'event-' . $eventId . '.ics';
        $href = commonCalDavBuildHref('calendars/' . $viewerUserId . '/' . $calendarSlug . '/' . $fileName);
        $calendarData = commonCalDavBuildEventCalendarData($organization, $event);
        $updatedAt = commonCalDavResolveEventUpdatedAt($event);
        $createdAt = commonCalDavResolveEventCreatedAt($event);
        $etag = '"' . sha1($calendarData . '|' . ($updatedAt ? $updatedAt->format('c') : '')) . '"';

        return array(
            'type' => 'event',
            'href' => $href,
            'calendarHref' => commonCalDavBuildHref('calendars/' . $viewerUserId . '/' . $calendarSlug . '/'),
            'fileName' => $fileName,
            'eventId' => $eventId,
            'organizationId' => $organizationId,
            'organizationName' => trim((string)$organization->get('name')),
            'displayName' => trim((string)$event->get('title')),
            'resourceId' => 'urn:uuid:' . commonCardDavBuildStableUuid('caldav:event:' . $organizationId . ':' . $eventId),
            'contentType' => 'text/calendar; charset=utf-8; component=VEVENT',
            'contentLength' => strlen($calendarData),
            'etag' => $etag,
            'calendarData' => $calendarData,
            'createdAt' => $createdAt,
            'lastModified' => $updatedAt,
            'status' => trim((string)$event->get('status')),
            'event' => $event,
        );
    }
}

if (!function_exists('commonCalDavBuildCalendarSyncToken')) {
    function commonCalDavBuildCalendarSyncToken($organizationId, array $eventResources)
    {
        $parts = array('org:' . (int)$organizationId);

        foreach ($eventResources as $eventResource) {
            $parts[] = (string)($eventResource['eventId'] ?? 0)
                . ':'
                . (string)($eventResource['etag'] ?? '');
        }

        return 'data:,' . sha1(implode('|', $parts));
    }
}

if (!function_exists('commonCalDavLoadCalendarsForViewer')) {
    function commonCalDavLoadCalendarsForViewer(User $viewer)
    {
        static $cache = array();

        $viewerUserId = (int)$viewer->getId();
        if ($viewerUserId <= 0) {
            return array();
        }

        if (isset($cache[$viewerUserId])) {
            return $cache[$viewerUserId];
        }

        $memberships = new ArrayUserOrganization();
        $memberships->loadActiveForUser($viewerUserId);

        $calendarMap = array();
        $seenOrganizationIds = array();

        foreach ($memberships as $membership) {
            if (!$membership instanceof \dbObject\UserOrganization) {
                continue;
            }

            $organizationId = (int)$membership->get('IDorganization');
            if ($organizationId <= 0 || isset($seenOrganizationIds[$organizationId])) {
                continue;
            }

            $seenOrganizationIds[$organizationId] = true;
            $organization = new Organization();
            if (!$organization->load($organizationId) || !$organization->isApplicationEnabled('calendar', $viewerUserId)) {
                continue;
            }

            $events = new ArrayEvent();
            $events->loadForOrganization($organizationId, false);

            $eventResources = array();
            foreach ($events as $event) {
                if (!$event instanceof Event) {
                    continue;
                }

                $eventResource = commonCalDavBuildEventResource($viewer, $organization, $event);
                $eventResources[(string)$eventResource['fileName']] = $eventResource;
            }

            $calendarSlug = 'organization-' . $organizationId;
            $syncToken = commonCalDavBuildCalendarSyncToken($organizationId, array_values($eventResources));

            $calendarMap[$calendarSlug] = array(
                'type' => 'calendar',
                'href' => commonCalDavBuildHref('calendars/' . $viewerUserId . '/' . $calendarSlug . '/'),
                'calendarSlug' => $calendarSlug,
                'organizationId' => $organizationId,
                'displayName' => trim((string)$organization->get('name')),
                'description' => 'Events for ' . trim((string)$organization->get('name')),
                'resourceId' => 'urn:uuid:' . commonCardDavBuildStableUuid('caldav:calendar:' . $organizationId),
                'syncToken' => $syncToken,
                'ctag' => sha1($syncToken),
                'organization' => $organization,
                'events' => array_values($eventResources),
                'eventMap' => $eventResources,
            );
        }

        $cache[$viewerUserId] = $calendarMap;
        return $cache[$viewerUserId];
    }
}

if (!function_exists('commonCalDavResolveRouteResource')) {
    function commonCalDavResolveRouteResource(User $viewer, $routePath)
    {
        $viewerUserId = (int)$viewer->getId();
        $routePath = '/' . trim((string)$routePath, '/');

        if ($routePath === '/') {
            return array(
                'type' => 'root',
                'href' => commonCalDavBuildHref(),
            );
        }

        if ($routePath === '/principals') {
            return array(
                'type' => 'principals',
                'href' => commonCalDavBuildHref('principals/'),
            );
        }

        if ($routePath === '/principals/' . $viewerUserId) {
            return array(
                'type' => 'principal',
                'href' => commonCalDavBuildHref('principals/' . $viewerUserId . '/'),
            );
        }

        if ($routePath === '/calendars') {
            return array(
                'type' => 'calendars',
                'href' => commonCalDavBuildHref('calendars/'),
            );
        }

        if ($routePath === '/calendars/' . $viewerUserId) {
            return array(
                'type' => 'calendar-home',
                'href' => commonCalDavBuildHref('calendars/' . $viewerUserId . '/'),
            );
        }

        $calendarMap = commonCalDavLoadCalendarsForViewer($viewer);

        if (preg_match('#^/calendars/' . preg_quote((string)$viewerUserId, '#') . '/(organization-\d+)$#', $routePath, $matches)) {
            $calendarSlug = (string)$matches[1];
            return isset($calendarMap[$calendarSlug]) ? $calendarMap[$calendarSlug] : null;
        }

        if (preg_match('#^/calendars/' . preg_quote((string)$viewerUserId, '#') . '/(organization-\d+)/(event-\d+\.ics)$#', $routePath, $matches)) {
            $calendarSlug = (string)$matches[1];
            $fileName = (string)$matches[2];

            if (!isset($calendarMap[$calendarSlug]['eventMap'][$fileName])) {
                return null;
            }

            return $calendarMap[$calendarSlug]['eventMap'][$fileName];
        }

        return null;
    }
}

if (!function_exists('commonCalDavListChildResources')) {
    function commonCalDavListChildResources(array $resource, User $viewer)
    {
        $viewerUserId = (int)$viewer->getId();
        $calendarMap = commonCalDavLoadCalendarsForViewer($viewer);

        switch ((string)($resource['type'] ?? '')) {
            case 'root':
                return array(
                    array(
                        'type' => 'principals',
                        'href' => commonCalDavBuildHref('principals/'),
                    ),
                    array(
                        'type' => 'calendars',
                        'href' => commonCalDavBuildHref('calendars/'),
                    ),
                );

            case 'principals':
                return array(
                    array(
                        'type' => 'principal',
                        'href' => commonCalDavBuildHref('principals/' . $viewerUserId . '/'),
                    ),
                );

            case 'calendars':
                return array(
                    array(
                        'type' => 'calendar-home',
                        'href' => commonCalDavBuildHref('calendars/' . $viewerUserId . '/'),
                    ),
                );

            case 'calendar-home':
                return array_values($calendarMap);

            case 'calendar':
                return array_values((array)($resource['events'] ?? array()));
        }

        return array();
    }
}

if (!function_exists('commonCalDavSupportedReports')) {
    function commonCalDavSupportedReports()
    {
        return array(
            array(
                'namespace' => 'urn:ietf:params:xml:ns:caldav',
                'prefix' => 'cal',
                'localName' => 'calendar-query',
            ),
            array(
                'namespace' => 'urn:ietf:params:xml:ns:caldav',
                'prefix' => 'cal',
                'localName' => 'calendar-multiget',
            ),
            array(
                'namespace' => 'DAV:',
                'prefix' => 'd',
                'localName' => 'sync-collection',
            ),
        );
    }
}

if (!function_exists('commonCalDavBuildPropertyMap')) {
    function commonCalDavBuildPropertyMap(array $resource, User $viewer)
    {
        $viewerUserId = (int)$viewer->getId();
        $principalHref = commonCalDavBuildHref('principals/' . $viewerUserId . '/');
        $principalCollectionHref = commonCalDavBuildHref('principals/');
        $calendarRootHref = commonCalDavBuildHref('calendars/' . $viewerUserId . '/');
        $viewerEmail = trim((string)$viewer->get('email'));

        switch ((string)($resource['type'] ?? '')) {
            case 'root':
                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => commonCalDavAuthRealm()),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                        ),
                    ),
                    '{DAV:}current-user-principal' => array('type' => 'href', 'value' => $principalHref),
                    '{DAV:}principal-collection-set' => array('type' => 'href-list', 'value' => array($principalCollectionHref)),
                    '{DAV:}supported-report-set' => array('type' => 'supported-report-set', 'value' => commonCalDavSupportedReports()),
                );

            case 'principals':
                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => 'Principals'),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                        ),
                    ),
                );

            case 'principal':
                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => (string)$viewer->getScopedDisplayName()),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'principal'),
                        ),
                    ),
                    '{DAV:}principal-URL' => array('type' => 'href', 'value' => $principalHref),
                    '{DAV:}principal-collection-set' => array('type' => 'href-list', 'value' => array($principalCollectionHref)),
                    '{DAV:}current-user-principal' => array('type' => 'href', 'value' => $principalHref),
                    '{DAV:}supported-report-set' => array('type' => 'supported-report-set', 'value' => commonCalDavSupportedReports()),
                    '{urn:ietf:params:xml:ns:caldav}calendar-home-set' => array('type' => 'href', 'value' => $calendarRootHref),
                    '{http://calendarserver.org/ns/}email-address-set' => array(
                        'type' => 'raw-xml',
                        'value' => $viewerEmail !== '' ? '<cs:email-address>' . htmlspecialchars($viewerEmail, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</cs:email-address>' : '',
                    ),
                    '{DAV:}resource-id' => array('type' => 'href', 'value' => 'urn:uuid:' . commonCardDavBuildStableUuid('caldav:principal:' . $viewerUserId)),
                );

            case 'calendars':
                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => 'Calendars'),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                        ),
                    ),
                );

            case 'calendar-home':
                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => 'Calendar home'),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                        ),
                    ),
                    '{urn:ietf:params:xml:ns:caldav}calendar-home-set' => array('type' => 'href', 'value' => $calendarRootHref),
                );

            case 'calendar':
                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => (string)($resource['displayName'] ?? 'Calendar')),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                            array('namespace' => 'urn:ietf:params:xml:ns:caldav', 'prefix' => 'cal', 'localName' => 'calendar'),
                        ),
                    ),
                    '{DAV:}owner' => array('type' => 'href', 'value' => $principalHref),
                    '{DAV:}supported-report-set' => array('type' => 'supported-report-set', 'value' => commonCalDavSupportedReports()),
                    '{urn:ietf:params:xml:ns:caldav}calendar-description' => array('type' => 'text', 'value' => (string)($resource['description'] ?? '')),
                    '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => array(
                        'type' => 'raw-xml',
                        'value' => '<cal:comp name="VEVENT"/>',
                    ),
                    '{urn:ietf:params:xml:ns:caldav}supported-calendar-data' => array(
                        'type' => 'raw-xml',
                        'value' => '<cal:calendar-data content-type="text/calendar" version="2.0"/>',
                    ),
                    '{DAV:}sync-token' => array('type' => 'text', 'value' => (string)($resource['syncToken'] ?? '')),
                    '{http://calendarserver.org/ns/}getctag' => array('type' => 'text', 'value' => (string)($resource['ctag'] ?? '')),
                    '{DAV:}resource-id' => array('type' => 'href', 'value' => (string)($resource['resourceId'] ?? '')),
                );

            case 'event':
                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => (string)($resource['displayName'] ?? $resource['fileName'] ?? 'Event')),
                    '{DAV:}resourcetype' => array('type' => 'resourcetype', 'value' => array()),
                    '{DAV:}getetag' => array('type' => 'text', 'value' => (string)($resource['etag'] ?? '')),
                    '{DAV:}getcontenttype' => array('type' => 'text', 'value' => (string)($resource['contentType'] ?? 'text/calendar; charset=utf-8; component=VEVENT')),
                    '{DAV:}getcontentlength' => array('type' => 'text', 'value' => (string)($resource['contentLength'] ?? 0)),
                    '{DAV:}getlastmodified' => array('type' => 'text', 'value' => commonCardDavFormatHttpDate($resource['lastModified'] ?? null)),
                    '{DAV:}creationdate' => array(
                        'type' => 'text',
                        'value' => !empty($resource['createdAt']) && $resource['createdAt'] instanceof DateTimeInterface
                            ? DateTimeImmutable::createFromInterface($resource['createdAt'])->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z')
                            : '',
                    ),
                    '{DAV:}resource-id' => array('type' => 'href', 'value' => (string)($resource['resourceId'] ?? '')),
                    '{urn:ietf:params:xml:ns:caldav}calendar-data' => array('type' => 'text', 'value' => (string)($resource['calendarData'] ?? '')),
                );
        }

        return array();
    }
}

if (!function_exists('commonCalDavBuildResponseForResource')) {
    function commonCalDavBuildResponseForResource(array $resource, User $viewer, array $requestedProperties, $propnameMode = false)
    {
        $propertyMap = commonCalDavBuildPropertyMap($resource, $viewer);
        $properties = count($requestedProperties) > 0
            ? array_values(array_unique($requestedProperties))
            : array_keys($propertyMap);

        $ok = array();
        $missing = array();

        foreach ($properties as $propertyName) {
            if (isset($propertyMap[$propertyName])) {
                $ok[$propertyName] = $propertyMap[$propertyName];
            } else {
                $missing[$propertyName] = array('type' => 'text', 'value' => '');
            }
        }

        return array(
            'href' => (string)($resource['href'] ?? commonCalDavBuildHref()),
            'ok' => $ok,
            'missing' => $missing,
            'propnameMode' => (bool)$propnameMode,
        );
    }
}

if (!function_exists('commonCalDavParseReportPropRequest')) {
    function commonCalDavParseReportPropRequest(?DOMDocument $document = null)
    {
        if (!$document || !$document->documentElement) {
            return array(
                'mode' => 'prop',
                'properties' => array(
                    '{DAV:}getetag',
                    '{urn:ietf:params:xml:ns:caldav}calendar-data',
                ),
            );
        }

        return commonCardDavParsePropRequest($document);
    }
}

if (!function_exists('commonCalDavParseDateTimeValue')) {
    function commonCalDavParseDateTimeValue($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $utc = new DateTimeZone('UTC');
        $formats = array(
            'Ymd\THis\Z',
            'Ymd',
        );

        foreach ($formats as $format) {
            $dateTime = DateTimeImmutable::createFromFormat($format, $value, $utc);
            if ($dateTime instanceof DateTimeImmutable) {
                if ($format === 'Ymd') {
                    $dateTime = $dateTime->setTime(0, 0, 0);
                }
                return $dateTime;
            }
        }

        try {
            return new DateTimeImmutable($value, $utc);
        } catch (Exception $exception) {
            return null;
        }
    }
}

if (!function_exists('commonCalDavExtractCalendarQueryRange')) {
    function commonCalDavExtractCalendarQueryRange(?DOMDocument $document = null)
    {
        if (!$document || !$document->documentElement) {
            return array('start' => null, 'end' => null);
        }

        foreach ($document->documentElement->getElementsByTagNameNS('urn:ietf:params:xml:ns:caldav', 'time-range') as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            return array(
                'start' => commonCalDavParseDateTimeValue($node->getAttribute('start')),
                'end' => commonCalDavParseDateTimeValue($node->getAttribute('end')),
            );
        }

        return array('start' => null, 'end' => null);
    }
}

if (!function_exists('commonCalDavEventMatchesTimeRange')) {
    function commonCalDavEventMatchesTimeRange(Event $event, ?DateTimeImmutable $rangeStart = null, ?DateTimeImmutable $rangeEnd = null)
    {
        if (!$rangeStart && !$rangeEnd) {
            return true;
        }

        $startAt = $event->get('start_at');
        $endAt = $event->get('end_at');

        if (!($startAt instanceof DateTimeInterface)) {
            return false;
        }

        $eventStart = DateTimeImmutable::createFromInterface($startAt);
        $eventEnd = $endAt instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($endAt)
            : $eventStart;

        if ((int)$event->get('is_all_day') === 1) {
            $eventStart = $eventStart->setTime(0, 0, 0);
            $eventEnd = commonCalDavResolveAllDayEndExclusive($event) ?: $eventEnd->modify('+1 day');
        }

        if ($rangeStart && $eventEnd < $rangeStart) {
            return false;
        }

        if ($rangeEnd && $eventStart > $rangeEnd) {
            return false;
        }

        return true;
    }
}

if (!function_exists('commonCalDavHandlePropfind')) {
    function commonCalDavHandlePropfind(User $viewer, array $resource)
    {
        $xmlPayload = commonCardDavReadXmlBody();
        $request = commonCardDavParsePropRequest($xmlPayload['xml']);
        $propnameMode = $request['mode'] === 'propname';
        $requestedProperties = $request['mode'] === 'prop'
            ? (array)$request['properties']
            : array();

        commonCardDavSetDebugValue('request_method', 'PROPFIND');
        commonCardDavSetDebugValue('resource_type', (string)($resource['type'] ?? 'unknown'));
        commonCardDavSetDebugValue('request_depth', (string)commonCalDavNormalizeDepth());
        commonCardDavSetDebugValue('requested_property_count', (string)count($requestedProperties));

        $responses = array(
            commonCalDavBuildResponseForResource($resource, $viewer, $requestedProperties, $propnameMode),
        );

        if (commonCalDavNormalizeDepth() === 1) {
            foreach (commonCalDavListChildResources($resource, $viewer) as $childResource) {
                $responses[] = commonCalDavBuildResponseForResource($childResource, $viewer, $requestedProperties, $propnameMode);
            }
        }

        commonCalDavSendMultistatus($responses, $propnameMode);
    }
}

if (!function_exists('commonCalDavHandleCalendarQueryReport')) {
    function commonCalDavHandleCalendarQueryReport(User $viewer, array $resource, ?DOMDocument $document = null)
    {
        $reportRequest = commonCalDavParseReportPropRequest($document);
        $requestedProperties = (array)$reportRequest['properties'];
        $range = commonCalDavExtractCalendarQueryRange($document);
        $responses = array();

        commonCardDavSetDebugValue('request_method', 'REPORT-query');
        commonCardDavSetDebugValue('resource_type', (string)($resource['type'] ?? 'unknown'));
        commonCardDavSetDebugValue('requested_property_count', (string)count($requestedProperties));

        foreach ((array)($resource['events'] ?? array()) as $eventResource) {
            $event = $eventResource['event'] ?? null;
            if (!$event instanceof Event) {
                continue;
            }

            if (!commonCalDavEventMatchesTimeRange($event, $range['start'], $range['end'])) {
                continue;
            }

            $responses[] = commonCalDavBuildResponseForResource($eventResource, $viewer, $requestedProperties, false);
        }

        commonCardDavSetDebugValue('report_matches', (string)count($responses));
        commonCalDavSendMultistatus($responses, false);
    }
}

if (!function_exists('commonCalDavHandleCalendarMultigetReport')) {
    function commonCalDavHandleCalendarMultigetReport(User $viewer, array $resource, ?DOMDocument $document = null)
    {
        $requestedProperties = (array)commonCalDavParseReportPropRequest($document)['properties'];
        $responses = array();
        $hrefIndex = array();

        foreach ((array)($resource['events'] ?? array()) as $eventResource) {
            $hrefIndex[(string)$eventResource['href']] = $eventResource;
        }

        commonCardDavSetDebugValue('request_method', 'REPORT-multiget');
        commonCardDavSetDebugValue('resource_type', (string)($resource['type'] ?? 'unknown'));
        commonCardDavSetDebugValue('requested_property_count', (string)count($requestedProperties));

        foreach (commonCardDavExtractRequestedHrefs($document) as $href) {
            if (!isset($hrefIndex[$href])) {
                $responses[] = array(
                    'href' => $href,
                    'statusOnly' => 'HTTP/1.1 404 Not Found',
                );
                continue;
            }

            $responses[] = commonCalDavBuildResponseForResource($hrefIndex[$href], $viewer, $requestedProperties, false);
        }

        commonCardDavSetDebugValue('report_matches', (string)count($responses));
        commonCalDavSendMultistatus($responses, false);
    }
}

if (!function_exists('commonCalDavHandleSyncCollectionReport')) {
    function commonCalDavHandleSyncCollectionReport(User $viewer, array $resource, ?DOMDocument $document = null)
    {
        $requestedProperties = (array)commonCalDavParseReportPropRequest($document)['properties'];
        if (count($requestedProperties) === 0) {
            $requestedProperties = array('{DAV:}getetag');
        }

        $responses = array();
        foreach ((array)($resource['events'] ?? array()) as $eventResource) {
            $responses[] = commonCalDavBuildResponseForResource($eventResource, $viewer, $requestedProperties, false);
        }

        commonCardDavSetDebugValue('request_method', 'REPORT-sync');
        commonCardDavSetDebugValue('resource_type', (string)($resource['type'] ?? 'unknown'));
        commonCardDavSetDebugValue('requested_property_count', (string)count($requestedProperties));
        commonCardDavSetDebugValue('report_matches', (string)count($responses));

        commonCalDavSendMultistatus(
            $responses,
            false,
            array(
                array(
                    'namespace' => 'DAV:',
                    'prefix' => 'd',
                    'localName' => 'sync-token',
                    'value' => (string)($resource['syncToken'] ?? ''),
                ),
            )
        );
    }
}

if (!function_exists('commonCalDavHandleReport')) {
    function commonCalDavHandleReport(User $viewer, array $resource)
    {
        if ((string)($resource['type'] ?? '') !== 'calendar') {
            commonCalDavSendStatusText(405, 'REPORT is only supported on calendar collections.');
        }

        $xmlPayload = commonCardDavReadXmlBody();
        $document = $xmlPayload['xml'];
        if (!$document || !$document->documentElement) {
            commonCalDavSendStatusText(400, 'Invalid REPORT body.');
        }

        $rootName = commonCardDavClarkName($document->documentElement);
        if ($rootName === '{urn:ietf:params:xml:ns:caldav}calendar-query') {
            commonCalDavHandleCalendarQueryReport($viewer, $resource, $document);
        }

        if ($rootName === '{urn:ietf:params:xml:ns:caldav}calendar-multiget') {
            commonCalDavHandleCalendarMultigetReport($viewer, $resource, $document);
        }

        if ($rootName === '{DAV:}sync-collection') {
            commonCalDavHandleSyncCollectionReport($viewer, $resource, $document);
        }

        commonCalDavSendStatusText(403, 'Unsupported REPORT.');
    }
}

if (!function_exists('commonCalDavSendEvent')) {
    function commonCalDavSendEvent(array $resource, $sendBody = true)
    {
        header('Content-Type: ' . (string)($resource['contentType'] ?? 'text/calendar; charset=utf-8; component=VEVENT'));
        header('ETag: ' . (string)($resource['etag'] ?? ''));
        header('Content-Length: ' . (string)($resource['contentLength'] ?? 0));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        if (!empty($resource['lastModified']) && $resource['lastModified'] instanceof DateTimeInterface) {
            header('Last-Modified: ' . commonCardDavFormatHttpDate($resource['lastModified']));
        }

        commonCardDavSetDebugValue('request_method', $sendBody ? 'GET' : 'HEAD');
        commonCardDavSetDebugValue('resource_type', (string)($resource['type'] ?? 'event'));
        http_response_code(200);
        commonCardDavSendDebugHeaders();
        if ($sendBody) {
            echo (string)($resource['calendarData'] ?? '');
        }
        exit;
    }
}
