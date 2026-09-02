<?php

require_once __DIR__ . '/carddav.php';
require_once __DIR__ . '/omo_context_scope.php';
require_once dirname(__DIR__) . '/omo/api/calendar/permissions_shared.php';

use dbObject\ArrayEvent;
use dbObject\ArrayUserOrganization;
use dbObject\Event;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\User;
use dbObject\UserOrganization;

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
            'ical' => 'http://apple.com/ns/ical/',
        );
    }
}

if (!function_exists('commonCalDavAuthRealm')) {
    function commonCalDavAuthRealm()
    {
        return 'OpenMyOrganization CalDAV';
    }
}

if (!function_exists('commonCalDavParseScopedRoute')) {
    function commonCalDavParseScopedRoute($routePath)
    {
        $routePath = '/' . trim((string)$routePath, '/');
        if (!preg_match(
            '#^/scoped/(\d+)/(\d+)/(contextual|children|descendants)/([0-9a-fA-F]{6})(?:/(editable|readonly)(?:/[^/]+\.ics)?)?$#',
            $routePath,
            $matches
        )) {
            return null;
        }

        return array(
            'organizationId' => (int)$matches[1],
            'holonId' => (int)$matches[2],
            'range' => (string)$matches[3],
            'color' => strtolower((string)$matches[4]),
        );
    }
}

if (!function_exists('commonCalDavCanUseScopedResponseCache')) {
    function commonCalDavCanUseScopedResponseCache(User $viewer, array $scope)
    {
        static $cache = array();

        $viewerUserId = (int)$viewer->getId();
        $organizationId = (int)($scope['organizationId'] ?? 0);
        $holonId = (int)($scope['holonId'] ?? 0);
        $range = commonCalDavNormalizeScopedRange($scope['range'] ?? '');
        $cacheKey = implode(':', array($viewerUserId, $organizationId, $holonId, $range));
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        if ($viewerUserId <= 0 || $organizationId <= 0 || $holonId <= 0) {
            return $cache[$cacheKey] = false;
        }

        if (!UserOrganization::hasActiveMembership($viewerUserId, $organizationId)) {
            return $cache[$cacheKey] = false;
        }

        $organization = new Organization();
        if (!$organization->load($organizationId) || !$organization->isApplicationEnabled('calendar', $viewerUserId)) {
            return $cache[$cacheKey] = false;
        }

        $rootHolon = $organization->getEnabledStructuralRootHolon();
        $holon = new Holon();
        if (
            !($rootHolon instanceof Holon)
            || !$holon->load($holonId)
            || !$holon->isDescendantOf((int)$rootHolon->getId(), true)
        ) {
            return $cache[$cacheKey] = false;
        }

        return $cache[$cacheKey] = in_array(
            $range,
            omoApiGetAvailableContextScopes(true, $holon, $rootHolon),
            true
        );
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

if (!function_exists('commonCalDavNormalizeScopedRange')) {
    function commonCalDavNormalizeScopedRange($range)
    {
        $range = strtolower(trim((string)$range));
        if ($range === 'global') {
            $range = 'descendants';
        }

        return in_array($range, array('contextual', 'children', 'descendants'), true)
            ? $range
            : 'contextual';
    }
}

if (!function_exists('commonCalDavNormalizeScopedColor')) {
    function commonCalDavNormalizeScopedColor($color)
    {
        $color = strtolower(trim((string)$color));
        $color = ltrim($color, '#');

        return preg_match('/^[0-9a-f]{6}$/', $color) === 1 ? $color : '2563eb';
    }
}

if (!function_exists('commonCalDavBuildScopedCalendarHref')) {
    function commonCalDavBuildScopedCalendarHref($organizationId, $holonId, $range, $color = null)
    {
        $suffix = 'scoped/'
            . (int)$organizationId
            . '/'
            . (int)$holonId
            . '/'
            . commonCalDavNormalizeScopedRange($range)
            . '/';

        if ($color !== null) {
            $suffix .= commonCalDavNormalizeScopedColor($color) . '/';
        }

        return commonCalDavBuildHref($suffix);
    }
}

if (!function_exists('commonCalDavBuildScopedPrincipalHref')) {
    function commonCalDavBuildScopedPrincipalHref($organizationId, $holonId, $range, $color)
    {
        return rtrim(commonCalDavBuildScopedCalendarHref($organizationId, $holonId, $range, $color), '/') . '/principal/';
    }
}

if (!function_exists('commonCalDavBuildScopedCalendarHomeHref')) {
    function commonCalDavBuildScopedCalendarHomeHref($organizationId, $holonId, $range, $color)
    {
        return rtrim(commonCalDavBuildScopedCalendarHref($organizationId, $holonId, $range, $color), '/') . '/home/';
    }
}

if (!function_exists('commonCalDavNormalizeScopedCalendarGroup')) {
    function commonCalDavNormalizeScopedCalendarGroup($group)
    {
        return strtolower(trim((string)$group)) === 'readonly' ? 'readonly' : 'editable';
    }
}

if (!function_exists('commonCalDavBuildScopedCalendarGroupHref')) {
    function commonCalDavBuildScopedCalendarGroupHref($organizationId, $holonId, $range, $color, $group)
    {
        return rtrim(commonCalDavBuildScopedCalendarHref($organizationId, $holonId, $range, $color), '/')
            . '/'
            . commonCalDavNormalizeScopedCalendarGroup($group)
            . '/';
    }
}

if (!function_exists('commonCalDavBuildAbsoluteHref')) {
    function commonCalDavBuildAbsoluteHref($href)
    {
        $href = '/' . ltrim((string)$href, '/');
        $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '') {
            return $href;
        }

        $forwardedProtocol = trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $protocol = strtolower(trim((string)explode(',', $forwardedProtocol)[0]));
        if ($protocol !== 'http' && $protocol !== 'https') {
            $protocol = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
        }

        return $protocol . '://' . $host . $href;
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

        if ($type === 'current-user-privilege-set') {
            foreach ((array)($value['value'] ?? array()) as $privilege) {
                $namespace = (string)($privilege['namespace'] ?? 'DAV:');
                $prefix = (string)($privilege['prefix'] ?? 'd');
                $localName = (string)($privilege['localName'] ?? '');
                if ($localName === '') {
                    continue;
                }

                $privilegeElement = $document->createElementNS('DAV:', 'd:privilege');
                $privilegeElement->appendChild($document->createElementNS($namespace, $prefix . ':' . $localName));
                $propertyElement->appendChild($privilegeElement);
            }
            return;
        }

        if ($type === 'email-address-set') {
            foreach ((array)($value['value'] ?? array()) as $emailAddress) {
                $emailAddress = trim((string)$emailAddress);
                if ($emailAddress === '') {
                    continue;
                }

                $element = $document->createElementNS('http://calendarserver.org/ns/', 'cs:email-address');
                $element->appendChild($document->createTextNode($emailAddress));
                $propertyElement->appendChild($element);
            }
            return;
        }

        if ($type === 'calendar-component-set') {
            foreach ((array)($value['value'] ?? array()) as $componentName) {
                $componentName = trim((string)$componentName);
                if ($componentName === '') {
                    continue;
                }

                $element = $document->createElementNS('urn:ietf:params:xml:ns:caldav', 'cal:comp');
                $element->setAttribute('name', $componentName);
                $propertyElement->appendChild($element);
            }
            return;
        }

        if ($type === 'calendar-data-support') {
            foreach ((array)($value['value'] ?? array()) as $format) {
                $element = $document->createElementNS('urn:ietf:params:xml:ns:caldav', 'cal:calendar-data');
                $contentType = trim((string)($format['contentType'] ?? 'text/calendar'));
                if ($contentType !== '') {
                    $element->setAttribute('content-type', $contentType);
                }

                $version = trim((string)($format['version'] ?? ''));
                if ($version !== '') {
                    $element->setAttribute('version', $version);
                }

                $propertyElement->appendChild($element);
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
    function commonCalDavBuildTimedDateLine($label, ?DateTimeInterface $dateTime = null, $timezoneName = '')
    {
        if (!$dateTime) {
            return $label . ':';
        }

        $timezone = commonCalDavResolveIcalendarTimezone($timezoneName, $dateTime->getTimezone()->getName());
        $localDateTime = DateTimeImmutable::createFromInterface($dateTime)->setTimezone($timezone);
        $timezoneId = $timezone->getName();
        if (in_array(strtoupper($timezoneId), array('UTC', 'GMT'), true)) {
            return $label . ':' . $localDateTime->format('Ymd\\THis\\Z');
        }

        return $label . ';TZID=' . $timezoneId . ':' . $localDateTime->format('Ymd\\THis');
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
        $locationData = method_exists($event, 'getLocationDisplayData')
            ? (array)$event->getLocationDisplayData()
            : array();
        $locationAddress = trim((string)($locationData['address'] ?? ''));
        $videoMeetingUrl = trim((string)($locationData['videoUrl'] ?? ''));
        $eventTimezone = commonCalDavResolveIcalendarTimezone((string)$event->get('timezone'), date_default_timezone_get());
        $eventTimezoneName = $eventTimezone->getName();
        $lines = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//OpenMyOrganization//CalDAV//EN',
            'CALSCALE:GREGORIAN',
            'X-WR-TIMEZONE:' . $eventTimezoneName,
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

        if ($locationAddress !== '') {
            $lines[] = 'LOCATION:' . commonCalDavEscapeText($locationAddress);
        } elseif ($videoMeetingUrl !== '') {
            $lines[] = 'LOCATION:' . commonCalDavEscapeText('Visio');
        }

        if ($videoMeetingUrl !== '') {
            $lines[] = 'URL:' . commonCalDavEscapeText($videoMeetingUrl);
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
            $lines[] = commonCalDavBuildTimedDateLine('DTSTART', $startAt instanceof DateTimeInterface ? $startAt : null, $eventTimezoneName);
            $lines[] = commonCalDavBuildTimedDateLine('DTEND', $endAt instanceof DateTimeInterface ? $endAt : null, $eventTimezoneName);
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
    function commonCalDavBuildEventResource(User $viewer, Organization $organization, Event $event, array $options = array())
    {
        $viewerUserId = (int)$viewer->getId();
        $organizationId = (int)$organization->getId();
        $eventId = (int)$event->getId();
        $calendarSlug = 'organization-' . $organizationId;
        $fileName = 'event-' . $eventId . '.ics';
        $calendarHref = trim((string)($options['calendarHref'] ?? ''));
        if ($calendarHref === '') {
            $calendarHref = commonCalDavBuildHref('calendars/' . $viewerUserId . '/' . $calendarSlug . '/');
        }
        $calendarHref = rtrim($calendarHref, '/') . '/';
        $calendarIdentity = trim((string)($options['calendarIdentity'] ?? ''));
        $href = $calendarHref . $fileName;
        $calendarData = commonCalDavBuildEventCalendarData($organization, $event);
        $updatedAt = commonCalDavResolveEventUpdatedAt($event);
        $createdAt = commonCalDavResolveEventCreatedAt($event);
        $etag = '"' . sha1($calendarData . '|' . ($updatedAt ? $updatedAt->format('c') : '')) . '"';

        return array(
            'type' => 'event',
            'href' => $href,
            'calendarHref' => $calendarHref,
            'fileName' => $fileName,
            'eventId' => $eventId,
            'organizationId' => $organizationId,
            'organizationName' => trim((string)$organization->get('name')),
            'displayName' => trim((string)$event->get('title')),
            'resourceId' => 'urn:uuid:' . commonCardDavBuildStableUuid(
                'caldav:event:'
                . ($calendarIdentity !== '' ? $calendarIdentity . ':' : '')
                . $organizationId
                . ':'
                . $eventId
            ),
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
    function commonCalDavBuildCalendarSyncToken($organizationId, array $eventResources, $calendarIdentity = '')
    {
        $parts = array('org:' . (int)$organizationId);
        $calendarIdentity = trim((string)$calendarIdentity);
        if ($calendarIdentity !== '') {
            $parts[] = 'calendar:' . $calendarIdentity;
        }

        foreach ($eventResources as $eventResource) {
            $parts[] = (string)($eventResource['eventId'] ?? 0)
                . ':'
                . (string)($eventResource['etag'] ?? '');
        }

        return 'data:,' . sha1(implode('|', $parts));
    }
}

if (!function_exists('commonCalDavLoadScopedCalendarForViewer')) {
    function commonCalDavLoadScopedCalendarForViewer(User $viewer, $organizationId, $holonId, $range, $color)
    {
        static $cache = array();

        $viewerUserId = (int)$viewer->getId();
        $organizationId = (int)$organizationId;
        $holonId = (int)$holonId;
        $range = commonCalDavNormalizeScopedRange($range);
        $color = commonCalDavNormalizeScopedColor($color);
        $cacheKey = implode(':', array($viewerUserId, $organizationId, $holonId, $range, $color));

        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        if ($viewerUserId <= 0 || $organizationId <= 0 || $holonId <= 0) {
            return $cache[$cacheKey] = null;
        }

        $memberships = new ArrayUserOrganization();
        $memberships->loadActiveForUser($viewerUserId);
        $hasOrganizationAccess = false;
        foreach ($memberships as $membership) {
            if ($membership instanceof \dbObject\UserOrganization && (int)$membership->get('IDorganization') === $organizationId) {
                $hasOrganizationAccess = true;
                break;
            }
        }

        if (!$hasOrganizationAccess) {
            return $cache[$cacheKey] = null;
        }

        $organization = new Organization();
        if (!$organization->load($organizationId) || !$organization->isApplicationEnabled('calendar', $viewerUserId)) {
            return $cache[$cacheKey] = null;
        }

        $rootHolon = $organization->getEnabledStructuralRootHolon();
        $holon = new Holon();
        if (
            !($rootHolon instanceof Holon)
            || !$holon->load($holonId)
            || !$holon->isDescendantOf((int)$rootHolon->getId(), true)
        ) {
            return $cache[$cacheKey] = null;
        }

        $availableRanges = omoApiGetAvailableContextScopes(true, $holon, $rootHolon);
        if (!in_array($range, $availableRanges, true)) {
            return $cache[$cacheKey] = null;
        }

        $visibleHolonIds = array((int)$holon->getId() => true);
        if ($range === 'children') {
            $visibleHolonIds += omoApiGetDirectChildHolonIdMap($holon);
        } elseif ($range === 'descendants') {
            $visibleHolonIds += omoApiGetDescendantHolonIdMap($holon);
        }

        $calendarHref = commonCalDavBuildScopedCalendarHref($organizationId, $holonId, $range, $color);
        $principalHref = commonCalDavBuildScopedPrincipalHref($organizationId, $holonId, $range, $color);
        $calendarHomeHref = commonCalDavBuildScopedCalendarHomeHref($organizationId, $holonId, $range, $color);
        $calendarSlug = 'scoped-' . $organizationId . '-' . $holonId . '-' . $range . '-' . $color;
        $viewerScopedEmail = trim(mb_strtolower((string)$viewer->getScopedEmail($organizationId), 'UTF-8'));
        $events = new ArrayEvent();
        $events->loadForOrganization($organizationId, false);
        $eventResources = array();

        foreach ($events as $event) {
            if (!($event instanceof Event) || !$event->isVisibleToInvitationViewer($viewerUserId, $organizationId, $viewerScopedEmail)) {
                continue;
            }

            $eventHolonId = (int)$event->get('IDholon');
            if ($eventHolonId > 0 && !isset($visibleHolonIds[$eventHolonId])) {
                continue;
            }

            $eventResource = commonCalDavBuildEventResource($viewer, $organization, $event, array(
                'calendarHref' => $calendarHref,
                'calendarIdentity' => $calendarSlug,
            ));
            $eventResources[(string)$eventResource['fileName']] = $eventResource;
        }

        $scopeLabels = array(
            'contextual' => 'Current context',
            'children' => 'Direct children',
            'descendants' => 'Descendants',
        );
        $holonLabel = trim((string)$holon->getDisplayName());
        $organizationName = trim((string)$organization->get('name'));
        $displayName = trim($organizationName . ' - ' . ($holonLabel !== '' ? $holonLabel : 'Calendar'));
        $syncToken = commonCalDavBuildCalendarSyncToken($organizationId, array_values($eventResources), $calendarSlug);

        return $cache[$cacheKey] = array(
            'type' => 'calendar',
            'href' => $calendarHref,
            'isScopedCalendar' => true,
            'principalHref' => $principalHref,
            'calendarHomeHref' => $calendarHomeHref,
            'calendarSlug' => $calendarSlug,
            'organizationId' => $organizationId,
            'holonId' => $holonId,
            'range' => $range,
            'color' => '#' . $color . 'FF',
            'displayName' => $displayName,
            'description' => trim($organizationName . ' - ' . ($scopeLabels[$range] ?? $range)),
            'resourceId' => 'urn:uuid:' . commonCardDavBuildStableUuid('caldav:calendar:' . $calendarSlug),
            'syncToken' => $syncToken,
            'ctag' => sha1($syncToken),
            'organization' => $organization,
            'events' => array_values($eventResources),
            'eventMap' => $eventResources,
        );
    }
}

if (!function_exists('commonCalDavLoadScopedCalendarGroupsForViewer')) {
    function commonCalDavLoadScopedCalendarGroupsForViewer(User $viewer, $organizationId, $holonId, $range, $color)
    {
        static $cache = array();

        $cacheKey = implode(':', array(
            (int)$viewer->getId(),
            (int)$organizationId,
            (int)$holonId,
            commonCalDavNormalizeScopedRange($range),
            commonCalDavNormalizeScopedColor($color),
        ));
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $baseCalendar = commonCalDavLoadScopedCalendarForViewer($viewer, $organizationId, $holonId, $range, $color);
        if (!is_array($baseCalendar)) {
            return $cache[$cacheKey] = array();
        }

        $organization = $baseCalendar['organization'] ?? null;
        if (!($organization instanceof Organization)) {
            return $cache[$cacheKey] = array();
        }

        $rootHolon = $organization->getEnabledStructuralRootHolon((int)$viewer->getId());
        if (!($rootHolon instanceof Holon)) {
            return $cache[$cacheKey] = array();
        }

        $organizationId = (int)$baseCalendar['organizationId'];
        $holonId = (int)$baseCalendar['holonId'];
        $range = (string)$baseCalendar['range'];
        $color = commonCalDavNormalizeScopedColor($color);
        $groupDefinitions = array(
            'editable' => array(
                'label' => 'Modifiables',
                'description' => 'Events you can edit',
                'readOnly' => false,
            ),
            'readonly' => array(
                'label' => 'Lecture seule',
                'description' => 'Events you cannot edit',
                'readOnly' => true,
            ),
        );
        $groupEvents = array(
            'editable' => array(),
            'readonly' => array(),
        );
        $groupEventMaps = array(
            'editable' => array(),
            'readonly' => array(),
        );

        foreach ((array)($baseCalendar['events'] ?? array()) as $eventResource) {
            $event = $eventResource['event'] ?? null;
            if (!($event instanceof Event)) {
                continue;
            }

            $group = omoCalendarCanEditEvent(
                $event,
                $organizationId,
                (int)$viewer->getId(),
                $rootHolon,
                false
            ) ? 'editable' : 'readonly';
            $groupHref = commonCalDavBuildScopedCalendarGroupHref($organizationId, $holonId, $range, $color, $group);
            $groupSlug = (string)$baseCalendar['calendarSlug'] . '-' . $group;
            $groupEventResource = commonCalDavBuildEventResource($viewer, $organization, $event, array(
                'calendarHref' => $groupHref,
                'calendarIdentity' => $groupSlug,
            ));
            if (!empty($groupDefinitions[$group]['readOnly'])) {
                $groupEventResource['readOnly'] = true;
            }

            $groupEvents[$group][] = $groupEventResource;
            $groupEventMaps[$group][(string)$groupEventResource['fileName']] = $groupEventResource;
        }

        $groupMap = array();
        foreach ($groupDefinitions as $group => $definition) {
            $groupSlug = (string)$baseCalendar['calendarSlug'] . '-' . $group;
            $events = $groupEvents[$group];
            $syncToken = commonCalDavBuildCalendarSyncToken($organizationId, $events, $groupSlug);
            $groupMap[$group] = array_merge($baseCalendar, array(
                'href' => commonCalDavBuildScopedCalendarGroupHref($organizationId, $holonId, $range, $color, $group),
                'calendarSlug' => $groupSlug,
                'displayName' => trim((string)$baseCalendar['displayName'] . ' - ' . (string)$definition['label']),
                'description' => trim((string)$baseCalendar['description'] . ' - ' . (string)$definition['description']),
                'resourceId' => 'urn:uuid:' . commonCardDavBuildStableUuid('caldav:calendar:' . $groupSlug),
                'syncToken' => $syncToken,
                'ctag' => sha1($syncToken),
                'readOnly' => !empty($definition['readOnly']),
                'events' => $events,
                'eventMap' => $groupEventMaps[$group],
            ));
        }

        return $cache[$cacheKey] = $groupMap;
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
            $viewerScopedEmail = trim(mb_strtolower((string)$viewer->getScopedEmail($organizationId), 'UTF-8'));

            $eventResources = array();
            foreach ($events as $event) {
                if (!$event instanceof Event) {
                    continue;
                }

                if (!$event->isVisibleToInvitationViewer($viewerUserId, $organizationId, $viewerScopedEmail)) {
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

        if (preg_match('#^/scoped/(\d+)/(\d+)/(contextual|children|descendants)/([0-9a-fA-F]{6})$#', $routePath, $matches)) {
            $calendar = commonCalDavLoadScopedCalendarForViewer($viewer, (int)$matches[1], (int)$matches[2], (string)$matches[3], (string)$matches[4]);
            if (!is_array($calendar)) {
                return null;
            }

            return array(
                'type' => 'scoped-entrypoint',
                'href' => (string)$calendar['href'],
                'principalHref' => (string)$calendar['principalHref'],
                'calendarHomeHref' => (string)$calendar['calendarHomeHref'],
                'calendar' => $calendar,
                'calendarMap' => commonCalDavLoadScopedCalendarGroupsForViewer($viewer, (int)$matches[1], (int)$matches[2], (string)$matches[3], (string)$matches[4]),
            );
        }

        if (preg_match('#^/scoped/(\d+)/(\d+)/(contextual|children|descendants)/([0-9a-fA-F]{6})/(editable|readonly)$#', $routePath, $matches)) {
            $calendarGroups = commonCalDavLoadScopedCalendarGroupsForViewer($viewer, (int)$matches[1], (int)$matches[2], (string)$matches[3], (string)$matches[4]);
            $group = commonCalDavNormalizeScopedCalendarGroup($matches[5]);
            return $calendarGroups[$group] ?? null;
        }

        if (preg_match('#^/scoped/(\d+)/(\d+)/(contextual|children|descendants)/([0-9a-fA-F]{6})/(editable|readonly)/(event-\d+\.ics)$#', $routePath, $matches)) {
            $calendarGroups = commonCalDavLoadScopedCalendarGroupsForViewer($viewer, (int)$matches[1], (int)$matches[2], (string)$matches[3], (string)$matches[4]);
            $group = commonCalDavNormalizeScopedCalendarGroup($matches[5]);
            $fileName = (string)$matches[6];
            return isset($calendarGroups[$group]['eventMap'][$fileName]) ? $calendarGroups[$group]['eventMap'][$fileName] : null;
        }

        if (preg_match('#^/scoped/(\d+)/(\d+)/(contextual|children|descendants)/([0-9a-fA-F]{6})/principal$#', $routePath, $matches)) {
            $calendar = commonCalDavLoadScopedCalendarForViewer($viewer, (int)$matches[1], (int)$matches[2], (string)$matches[3], (string)$matches[4]);
            if (!is_array($calendar)) {
                return null;
            }

            return array(
                'type' => 'scoped-principal',
                'href' => (string)$calendar['principalHref'],
                'calendarHomeHref' => (string)$calendar['calendarHomeHref'],
                'calendar' => $calendar,
            );
        }

        if (preg_match('#^/scoped/(\d+)/(\d+)/(contextual|children|descendants)/([0-9a-fA-F]{6})/home$#', $routePath, $matches)) {
            $calendar = commonCalDavLoadScopedCalendarForViewer($viewer, (int)$matches[1], (int)$matches[2], (string)$matches[3], (string)$matches[4]);
            if (!is_array($calendar)) {
                return null;
            }

            $calendarGroups = commonCalDavLoadScopedCalendarGroupsForViewer($viewer, (int)$matches[1], (int)$matches[2], (string)$matches[3], (string)$matches[4]);

            return array(
                'type' => 'scoped-calendar-home',
                'href' => (string)$calendar['calendarHomeHref'],
                'principalHref' => (string)$calendar['principalHref'],
                'calendar' => $calendar,
                'calendarMap' => $calendarGroups,
            );
        }

        if (preg_match('#^/scoped/(\d+)/(\d+)/(contextual|children|descendants)/([0-9a-fA-F]{6})/(event-\d+\.ics)$#', $routePath, $matches)) {
            $calendar = commonCalDavLoadScopedCalendarForViewer($viewer, (int)$matches[1], (int)$matches[2], (string)$matches[3], (string)$matches[4]);
            $fileName = (string)$matches[5];
            return is_array($calendar) && isset($calendar['eventMap'][$fileName]) ? $calendar['eventMap'][$fileName] : null;
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
                return array_values(commonCalDavLoadCalendarsForViewer($viewer));

            case 'scoped-principal':
                $calendar = $resource['calendar'] ?? null;
                if (!is_array($calendar)) {
                    return array();
                }

                return array(
                    array(
                        'type' => 'scoped-calendar-home',
                        'href' => (string)($resource['calendarHomeHref'] ?? $calendar['calendarHomeHref'] ?? ''),
                        'principalHref' => (string)($resource['href'] ?? $calendar['principalHref'] ?? ''),
                        'calendar' => $calendar,
                    ),
                );

            case 'scoped-calendar-home':
                return array_values((array)($resource['calendarMap'] ?? array()));

            case 'scoped-entrypoint':
                return array_values((array)($resource['calendarMap'] ?? array()));

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
                        'type' => 'email-address-set',
                        'value' => $viewerEmail !== '' ? array($viewerEmail) : array(),
                    ),
                    '{DAV:}resource-id' => array('type' => 'href', 'value' => 'urn:uuid:' . commonCardDavBuildStableUuid('caldav:principal:' . $viewerUserId)),
                );

            case 'scoped-principal':
                $calendar = is_array($resource['calendar'] ?? null) ? $resource['calendar'] : array();
                $scopedPrincipalHref = (string)($resource['href'] ?? $calendar['principalHref'] ?? '');
                $scopedCalendarHomeHref = (string)($resource['calendarHomeHref'] ?? $calendar['calendarHomeHref'] ?? '');
                $scopeKey = (string)($calendar['calendarSlug'] ?? $scopedPrincipalHref);

                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => (string)$viewer->getScopedDisplayName()),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'principal'),
                        ),
                    ),
                    '{DAV:}principal-URL' => array('type' => 'href', 'value' => $scopedPrincipalHref),
                    '{DAV:}current-user-principal' => array('type' => 'href', 'value' => $scopedPrincipalHref),
                    '{DAV:}supported-report-set' => array('type' => 'supported-report-set', 'value' => commonCalDavSupportedReports()),
                    '{urn:ietf:params:xml:ns:caldav}calendar-home-set' => array('type' => 'href', 'value' => $scopedCalendarHomeHref),
                    '{http://calendarserver.org/ns/}email-address-set' => array(
                        'type' => 'email-address-set',
                        'value' => $viewerEmail !== '' ? array($viewerEmail) : array(),
                    ),
                    '{DAV:}resource-id' => array('type' => 'href', 'value' => 'urn:uuid:' . commonCardDavBuildStableUuid('caldav:scoped-principal:' . $scopeKey)),
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

            case 'scoped-calendar-home':
                $calendar = is_array($resource['calendar'] ?? null) ? $resource['calendar'] : array();
                $scopedCalendarHomeHref = (string)($resource['href'] ?? $calendar['calendarHomeHref'] ?? '');
                $scopedPrincipalHref = (string)($resource['principalHref'] ?? $calendar['principalHref'] ?? '');
                $scopeKey = (string)($calendar['calendarSlug'] ?? $scopedCalendarHomeHref);

                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => 'Calendar home'),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                        ),
                    ),
                    '{DAV:}current-user-principal' => array('type' => 'href', 'value' => $scopedPrincipalHref),
                    '{urn:ietf:params:xml:ns:caldav}calendar-home-set' => array('type' => 'href', 'value' => $scopedCalendarHomeHref),
                    '{DAV:}resource-id' => array('type' => 'href', 'value' => 'urn:uuid:' . commonCardDavBuildStableUuid('caldav:scoped-calendar-home:' . $scopeKey)),
                );

            case 'scoped-entrypoint':
                $calendar = is_array($resource['calendar'] ?? null) ? $resource['calendar'] : array();
                $scopedPrincipalHref = (string)($resource['principalHref'] ?? $calendar['principalHref'] ?? '');
                $scopedCalendarHomeHref = (string)($resource['calendarHomeHref'] ?? $calendar['calendarHomeHref'] ?? '');
                $scopeKey = (string)($calendar['calendarSlug'] ?? $resource['href'] ?? '');

                return array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => (string)($calendar['displayName'] ?? 'Calendar connection')),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                        ),
                    ),
                    '{DAV:}current-user-principal' => array('type' => 'href', 'value' => $scopedPrincipalHref),
                    '{urn:ietf:params:xml:ns:caldav}calendar-home-set' => array('type' => 'href', 'value' => $scopedCalendarHomeHref),
                    '{DAV:}resource-id' => array('type' => 'href', 'value' => 'urn:uuid:' . commonCardDavBuildStableUuid('caldav:scoped-entrypoint:' . $scopeKey)),
                );

            case 'calendar':
                $calendarPrincipalHref = !empty($resource['isScopedCalendar'])
                    ? (string)($resource['principalHref'] ?? '')
                    : $principalHref;
                $calendarHomeHref = !empty($resource['isScopedCalendar'])
                    ? (string)($resource['calendarHomeHref'] ?? '')
                    : $calendarRootHref;
                $calendarPrivileges = array(
                    array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'read'),
                    array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'read-current-user-privilege-set'),
                );
                if (empty($resource['readOnly'])) {
                    $calendarPrivileges[] = array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'write');
                    $calendarPrivileges[] = array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'write-content');
                    $calendarPrivileges[] = array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'bind');
                    $calendarPrivileges[] = array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'unbind');
                }

                $properties = array(
                    '{DAV:}displayname' => array('type' => 'text', 'value' => (string)($resource['displayName'] ?? 'Calendar')),
                    '{DAV:}resourcetype' => array(
                        'type' => 'resourcetype',
                        'value' => array(
                            array('namespace' => 'DAV:', 'prefix' => 'd', 'localName' => 'collection'),
                            array('namespace' => 'urn:ietf:params:xml:ns:caldav', 'prefix' => 'cal', 'localName' => 'calendar'),
                        ),
                    ),
                    '{DAV:}owner' => array('type' => 'href', 'value' => $calendarPrincipalHref),
                    '{DAV:}current-user-principal' => array('type' => 'href', 'value' => $calendarPrincipalHref),
                    '{DAV:}current-user-privilege-set' => array('type' => 'current-user-privilege-set', 'value' => $calendarPrivileges),
                    '{DAV:}supported-report-set' => array('type' => 'supported-report-set', 'value' => commonCalDavSupportedReports()),
                    '{urn:ietf:params:xml:ns:caldav}calendar-home-set' => array('type' => 'href', 'value' => $calendarHomeHref),
                    '{urn:ietf:params:xml:ns:caldav}calendar-description' => array('type' => 'text', 'value' => (string)($resource['description'] ?? '')),
                    '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => array(
                        'type' => 'calendar-component-set',
                        'value' => array('VEVENT'),
                    ),
                    '{urn:ietf:params:xml:ns:caldav}supported-calendar-data' => array(
                        'type' => 'calendar-data-support',
                        'value' => array(
                            array(
                                'contentType' => 'text/calendar',
                                'version' => '2.0',
                            ),
                        ),
                    ),
                    '{DAV:}sync-token' => array('type' => 'text', 'value' => (string)($resource['syncToken'] ?? '')),
                    '{http://calendarserver.org/ns/}getctag' => array('type' => 'text', 'value' => (string)($resource['ctag'] ?? '')),
                    '{DAV:}resource-id' => array('type' => 'href', 'value' => (string)($resource['resourceId'] ?? '')),
                );

                $calendarColor = trim((string)($resource['color'] ?? ''));
                if ($calendarColor !== '') {
                    $properties['{http://apple.com/ns/ical/}calendar-color'] = array('type' => 'text', 'value' => $calendarColor);
                }

                return $properties;

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

if (!function_exists('commonCalDavUnfoldCalendarLines')) {
    function commonCalDavUnfoldCalendarLines($rawCalendar)
    {
        $rawCalendar = str_replace(array("\r\n", "\r"), "\n", (string)$rawCalendar);
        $sourceLines = explode("\n", $rawCalendar);
        $lines = array();

        foreach ($sourceLines as $line) {
            if ($line !== '' && ($line[0] === ' ' || $line[0] === "\t") && count($lines) > 0) {
                $lines[count($lines) - 1] .= substr($line, 1);
                continue;
            }

            $lines[] = $line;
        }

        return $lines;
    }
}

if (!function_exists('commonCalDavSplitCalendarPropertyLine')) {
    function commonCalDavSplitCalendarPropertyLine($line)
    {
        $line = (string)$line;
        $quoted = false;
        $length = strlen($line);

        for ($index = 0; $index < $length; $index++) {
            if ($line[$index] === '"') {
                $quoted = !$quoted;
                continue;
            }

            if ($line[$index] === ':' && !$quoted) {
                return array(substr($line, 0, $index), substr($line, $index + 1));
            }
        }

        return null;
    }
}

if (!function_exists('commonCalDavParseCalendarProperty')) {
    function commonCalDavParseCalendarProperty($property)
    {
        $parts = explode(';', (string)$property);
        $name = strtoupper(trim((string)array_shift($parts)));
        if ($name === '') {
            return null;
        }

        $parameters = array();
        foreach ($parts as $part) {
            $parameterParts = explode('=', $part, 2);
            $parameterName = strtoupper(trim((string)($parameterParts[0] ?? '')));
            if ($parameterName === '' || count($parameterParts) !== 2) {
                continue;
            }

            $parameters[$parameterName] = trim((string)$parameterParts[1], ' "');
        }

        return array(
            'name' => $name,
            'parameters' => $parameters,
        );
    }
}

if (!function_exists('commonCalDavUnescapeText')) {
    function commonCalDavUnescapeText($value)
    {
        $value = str_replace(array('\\\\', '\\n', '\\N', '\\,', '\\;'), array("\0", "\n", "\n", ',', ';'), (string)$value);
        return str_replace("\0", '\\', $value);
    }
}

if (!function_exists('commonCalDavResolveIcalendarTimezone')) {
    function commonCalDavResolveIcalendarTimezone($timezoneName, $fallbackTimezoneName = '')
    {
        $candidates = array(
            trim((string)$timezoneName),
            trim((string)$fallbackTimezoneName),
            date_default_timezone_get(),
            'UTC',
        );

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            try {
                return new DateTimeZone($candidate);
            } catch (Exception $exception) {
            }
        }

        return new DateTimeZone('UTC');
    }
}

if (!function_exists('commonCalDavParseIcalendarDateTime')) {
    function commonCalDavParseIcalendarDateTime($rawValue, array $parameters, $fallbackTimezoneName = '')
    {
        $rawValue = trim((string)$rawValue);
        $isAllDay = strtoupper((string)($parameters['VALUE'] ?? '')) === 'DATE' || preg_match('/^\d{8}$/', $rawValue) === 1;
        $timezoneName = trim((string)($parameters['TZID'] ?? ''));
        $isUtc = substr($rawValue, -1) === 'Z';
        if ($isUtc) {
            $timezoneName = 'UTC';
            $rawValue = substr($rawValue, 0, -1);
        }

        $timezone = commonCalDavResolveIcalendarTimezone($timezoneName, $fallbackTimezoneName);
        $format = $isAllDay ? '!Ymd' : '!Ymd\\THis';
        if (!$isAllDay && preg_match('/^\d{8}T\d{4}$/', $rawValue) === 1) {
            $format = '!Ymd\\THi';
        }

        $dateTime = DateTimeImmutable::createFromFormat($format, $rawValue, $timezone);
        $errors = DateTimeImmutable::getLastErrors();
        if (
            !($dateTime instanceof DateTimeImmutable)
            || ($errors !== false && ((int)($errors['warning_count'] ?? 0) > 0 || (int)($errors['error_count'] ?? 0) > 0))
        ) {
            return null;
        }

        return array(
            'value' => $dateTime,
            'isAllDay' => $isAllDay,
            'timezone' => $timezone->getName(),
        );
    }
}

if (!function_exists('commonCalDavParseEventUpdate')) {
    function commonCalDavParseEventUpdate($rawCalendar, Event $event)
    {
        $rawCalendar = (string)$rawCalendar;
        if ($rawCalendar === '' || strlen($rawCalendar) > 524288 || strpos($rawCalendar, "\0") !== false) {
            return array('status' => false, 'code' => 400, 'text' => 'Invalid calendar data.');
        }

        $inEvent = false;
        $nestedComponentDepth = 0;
        $eventCount = 0;
        $properties = array();
        foreach (commonCalDavUnfoldCalendarLines($rawCalendar) as $line) {
            $line = rtrim((string)$line, "\n");
            if ($line === '') {
                continue;
            }

            $parts = commonCalDavSplitCalendarPropertyLine($line);
            if (!is_array($parts)) {
                return array('status' => false, 'code' => 400, 'text' => 'Invalid iCalendar property.');
            }

            $property = commonCalDavParseCalendarProperty($parts[0]);
            if (!is_array($property)) {
                return array('status' => false, 'code' => 400, 'text' => 'Invalid iCalendar property.');
            }

            $name = (string)$property['name'];
            $value = (string)$parts[1];
            if ($name === 'BEGIN' && strtoupper($value) === 'VEVENT') {
                if ($inEvent || $eventCount > 0) {
                    return array('status' => false, 'code' => 409, 'text' => 'Only one VEVENT can be updated at a time.');
                }
                $inEvent = true;
                $nestedComponentDepth = 0;
                $eventCount++;
                continue;
            }

            if ($name === 'END' && strtoupper($value) === 'VEVENT') {
                if (!$inEvent || $nestedComponentDepth > 0) {
                    return array('status' => false, 'code' => 400, 'text' => 'Invalid VEVENT component.');
                }
                $inEvent = false;
                continue;
            }

            if (!$inEvent) {
                continue;
            }

            if ($name === 'BEGIN') {
                $nestedComponentDepth++;
                continue;
            }

            if ($name === 'END') {
                if ($nestedComponentDepth > 0) {
                    $nestedComponentDepth--;
                }
                continue;
            }

            if ($nestedComponentDepth > 0) {
                continue;
            }

            if (in_array($name, array('RRULE', 'RDATE', 'EXDATE', 'RECURRENCE-ID'), true)) {
                return array('status' => false, 'code' => 409, 'text' => 'Recurring events are not supported.');
            }

            if (!in_array($name, array('UID', 'SUMMARY', 'DESCRIPTION', 'LOCATION', 'URL', 'DTSTART', 'DTEND'), true)) {
                continue;
            }

            if (isset($properties[$name])) {
                return array('status' => false, 'code' => 400, 'text' => 'Duplicate iCalendar property.');
            }

            $properties[$name] = array(
                'value' => $value,
                'parameters' => (array)$property['parameters'],
            );
        }

        if ($inEvent || $eventCount !== 1) {
            return array('status' => false, 'code' => 400, 'text' => 'A single VEVENT component is required.');
        }

        $expectedUid = commonCalDavBuildEventUid($event);
        $receivedUid = trim((string)commonCalDavUnescapeText($properties['UID']['value'] ?? ''));
        if ($receivedUid !== '' && !hash_equals($expectedUid, $receivedUid)) {
            return array('status' => false, 'code' => 409, 'text' => 'The event UID does not match this resource.');
        }

        $summary = trim((string)commonCalDavUnescapeText($properties['SUMMARY']['value'] ?? ''));
        if ($summary === '') {
            return array('status' => false, 'code' => 400, 'text' => 'A calendar event title is required.');
        }

        if (!isset($properties['DTSTART'])) {
            return array('status' => false, 'code' => 400, 'text' => 'DTSTART is required.');
        }

        $fallbackTimezone = trim((string)$event->get('timezone'));
        $start = commonCalDavParseIcalendarDateTime(
            $properties['DTSTART']['value'],
            (array)$properties['DTSTART']['parameters'],
            $fallbackTimezone
        );
        if (!is_array($start)) {
            return array('status' => false, 'code' => 400, 'text' => 'Invalid DTSTART value.');
        }

        $end = null;
        if (isset($properties['DTEND'])) {
            $end = commonCalDavParseIcalendarDateTime(
                $properties['DTEND']['value'],
                (array)$properties['DTEND']['parameters'],
                (string)$start['timezone']
            );
            if (!is_array($end) || (bool)$end['isAllDay'] !== (bool)$start['isAllDay']) {
                return array('status' => false, 'code' => 400, 'text' => 'Invalid DTEND value.');
            }
        }

        $storageTimezone = commonCalDavResolveIcalendarTimezone(
            (string)($properties['DTSTART']['parameters']['TZID'] ?? ''),
            $fallbackTimezone
        );
        $startAt = DateTimeImmutable::createFromInterface($start['value'])->setTimezone($storageTimezone);
        if ((bool)$start['isAllDay']) {
            $startAt = $startAt->setTime(0, 0, 0);
            $endAt = is_array($end)
                ? DateTimeImmutable::createFromInterface($end['value'])->setTimezone($storageTimezone)->setTime(0, 0, 0)->modify('-1 second')
                : $startAt->setTime(23, 59, 59);
        } else {
            $endAt = is_array($end)
                ? DateTimeImmutable::createFromInterface($end['value'])->setTimezone($storageTimezone)
                : $startAt->modify('+1 hour');
        }

        if ($endAt < $startAt) {
            return array('status' => false, 'code' => 400, 'text' => 'DTEND must not be before DTSTART.');
        }

        $location = trim((string)commonCalDavUnescapeText($properties['LOCATION']['value'] ?? ''));
        $videoUrl = Event::sanitizeVideoMeetingUrl(commonCalDavUnescapeText($properties['URL']['value'] ?? ''));
        if ($videoUrl !== '' && mb_strtolower($location, 'UTF-8') === 'visio') {
            $location = '';
        }

        return array(
            'status' => true,
            'values' => array(
                'title' => $summary,
                'description' => isset($properties['DESCRIPTION']) ? commonCalDavUnescapeText($properties['DESCRIPTION']['value']) : '',
                'locationaddress' => $location,
                'videomeetingurl' => $videoUrl,
                'timezone' => $storageTimezone->getName(),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'is_all_day' => !empty($start['isAllDay']) ? 1 : 0,
            ),
        );
    }
}

if (!function_exists('commonCalDavRequestMatchesEventEtag')) {
    function commonCalDavRequestMatchesEventEtag(array $resource)
    {
        $normalizeEtag = static function ($value) {
            $value = trim((string)$value);
            return strtoupper(substr($value, 0, 2)) === 'W/' ? trim(substr($value, 2)) : $value;
        };
        $etag = $normalizeEtag($resource['etag'] ?? '');
        $ifMatch = trim((string)($_SERVER['HTTP_IF_MATCH'] ?? ''));
        if ($ifMatch !== '') {
            $ifMatchValues = array_map($normalizeEtag, explode(',', $ifMatch));
            if ($ifMatch !== '*' && !in_array($etag, $ifMatchValues, true)) {
                return false;
            }
        }

        $ifNoneMatch = trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
        $ifNoneMatchValues = array_map($normalizeEtag, explode(',', $ifNoneMatch));
        if ($ifNoneMatch !== '' && ($ifNoneMatch === '*' || in_array($etag, $ifNoneMatchValues, true))) {
            return false;
        }

        return true;
    }
}

if (!function_exists('commonCalDavHandleEventPut')) {
    function commonCalDavBuildEditDeniedMessage(User $viewer, Event $event, $organizationId, $rootHolon = null)
    {
        $message = 'You are not allowed to edit this event.';
        $environment = function_exists('appGetEnvironmentSubdomain')
            ? appGetEnvironmentSubdomain()
            : '';
        if ($environment !== 'beta' && !commonCardDavCanExposeDebug()) {
            return $message;
        }

        $organizationId = (int)$organizationId;
        $eventHolonId = (int)$event->get('IDholon');
        $permissionHolon = omoCalendarResolveEventPermissionHolon(
            $event,
            $rootHolon instanceof Holon ? $rootHolon : null
        );
        $viewerEmail = trim((string)$viewer->get('email'));

        return $message
            . ' CalDAV diagnostic:'
            . ' viewer_id=' . (int)$viewer->getId()
            . '; viewer_email=' . ($viewerEmail !== '' ? $viewerEmail : '-')
            . '; organization_id=' . $organizationId
            . '; event_id=' . (int)$event->getId()
            . '; event_holon_id=' . $eventHolonId
            . '; root_holon_id=' . ($rootHolon instanceof Holon ? (int)$rootHolon->getId() : 0)
            . '; permission_holon_id=' . ($permissionHolon instanceof Holon ? (int)$permissionHolon->getId() : 0);
    }
}

if (!function_exists('commonCalDavHandleEventPut')) {
    function commonCalDavHandleEventPut(User $viewer, array $resource)
    {
        $event = $resource['event'] ?? null;
        $organizationId = (int)($resource['organizationId'] ?? 0);
        if (!($event instanceof Event) || $organizationId <= 0) {
            commonCalDavSendStatusText(404, 'CalDAV event not found.');
        }

        $organization = new Organization();
        $rootHolon = $organization->load($organizationId) ? $organization->getEnabledStructuralRootHolon() : null;
        if (!omoCalendarCanEditEvent($event, $organizationId, (int)$viewer->getId(), $rootHolon instanceof Holon ? $rootHolon : null, false)) {
            commonCalDavSendStatusText(403, commonCalDavBuildEditDeniedMessage($viewer, $event, $organizationId, $rootHolon));
        }

        if (!commonCalDavRequestMatchesEventEtag($resource)) {
            commonCalDavSendStatusText(412, 'The event has changed on the server.');
        }

        $parsed = commonCalDavParseEventUpdate(file_get_contents('php://input'), $event);
        if (empty($parsed['status'])) {
            commonCalDavSendStatusText((int)($parsed['code'] ?? 400), (string)($parsed['text'] ?? 'Invalid calendar data.'));
        }

        $values = (array)($parsed['values'] ?? array());
        $event->set('title', (string)$values['title']);
        $event->set('description', (string)$values['description'] !== '' ? (string)$values['description'] : null);
        $event->set('timezone', (string)$values['timezone']);
        $event->set('locationaddress', (string)$values['locationaddress'] !== '' ? (string)$values['locationaddress'] : null);
        $event->set('videomeetingurl', (string)$values['videomeetingurl'] !== '' ? (string)$values['videomeetingurl'] : null);
        $event->set('start_at', $values['start_at']);
        $event->set('end_at', $values['end_at']);
        $event->set('is_all_day', !empty($values['is_all_day']) ? 1 : 0);
        $event->set('updated_at', new DateTimeImmutable('now'));

        $saveResult = $event->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            commonCalDavSendStatusText(409, trim((string)($saveResult['text'] ?? 'The event could not be updated.')));
        }

        $updatedResource = commonCalDavBuildEventResource($viewer, $organization, $event, array(
            'calendarHref' => (string)($resource['calendarHref'] ?? ''),
        ));
        header('ETag: ' . (string)$updatedResource['etag']);
        header('Location: ' . (string)$updatedResource['href']);
        header('Content-Length: 0');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        commonCardDavSetDebugValue('request_method', 'PUT');
        commonCardDavSetDebugValue('resource_type', 'event');
        commonCardDavSendDebugHeaders();
        http_response_code(204);
        exit;
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
