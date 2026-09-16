<?php

use dbObject\ArrayExternalCalendar;
use dbObject\ExternalCalendar;
use dbObject\ExternalCalendarEvent;

function commonExternalCalendarReadEnvironmentValue($key, $default = '')
{
    if (function_exists('commonReadRuntimeEnvValue')) {
        return commonReadRuntimeEnvValue((string)$key, $default);
    }

    $value = getenv((string)$key);
    if ($value !== false) {
        return $value;
    }

    return $_ENV[$key] ?? ($_SERVER[$key] ?? $default);
}

function commonExternalCalendarGetEncryptionKey()
{
    $configured = trim((string)commonExternalCalendarReadEnvironmentValue('EXTERNAL_CALENDAR_ENCRYPTION_KEY', ''));
    if ($configured === '' || !function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {
        return null;
    }

    return hash('sha256', $configured, true);
}

function commonExternalCalendarEncryptPassword($password)
{
    $password = (string)$password;
    $key = commonExternalCalendarGetEncryptionKey();
    if ($password === '' || $key === null) {
        return null;
    }

    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($password, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if (!is_string($ciphertext) || strlen($tag) !== 16) {
        return null;
    }

    return 'v1:' . base64_encode($iv . $tag . $ciphertext);
}

function commonExternalCalendarDecryptPassword($encrypted)
{
    $encrypted = (string)$encrypted;
    $key = commonExternalCalendarGetEncryptionKey();
    if ($key === null || !str_starts_with($encrypted, 'v1:')) {
        return null;
    }

    $payload = base64_decode(substr($encrypted, 3), true);
    if (!is_string($payload) || strlen($payload) < 29) {
        return null;
    }

    $password = openssl_decrypt(
        substr($payload, 28),
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        substr($payload, 0, 12),
        substr($payload, 12, 16)
    );

    return is_string($password) && $password !== '' ? $password : null;
}

function commonExternalCalendarAllowPrivateHosts()
{
    return in_array(
        strtolower(trim((string)commonExternalCalendarReadEnvironmentValue('OMO_EXTERNAL_CALENDAR_ALLOW_PRIVATE_HOSTS', ''))),
        ['1', 'true', 'yes', 'on'],
        true
    );
}

function commonExternalCalendarIsPublicIpAddress($address)
{
    return filter_var((string)$address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

function commonExternalCalendarNormalizeUrl($value)
{
    $value = trim((string)$value);
    if ($value === '' || strlen($value) > 2000 || filter_var($value, FILTER_VALIDATE_URL) === false) {
        return null;
    }

    $parts = parse_url($value);
    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    $host = trim((string)($parts['host'] ?? ''));
    if ($scheme !== 'https' || $host === '' || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
        || preg_match('/[\x00-\x20\x7f\\\\]/', $value)) {
        return null;
    }

    if (!commonExternalCalendarAllowPrivateHosts()) {
        $addresses = [];
        $ipHost = trim($host, '[]');
        if (filter_var($ipHost, FILTER_VALIDATE_IP) !== false) {
            $addresses[] = $ipHost;
        } else {
            $ipv4 = gethostbynamel($host);
            if (is_array($ipv4)) {
                $addresses = array_merge($addresses, $ipv4);
            }
            if (function_exists('dns_get_record')) {
                $records = @dns_get_record($host, DNS_AAAA);
                if (is_array($records)) {
                    foreach ($records as $record) {
                        if (!empty($record['ipv6'])) {
                            $addresses[] = (string)$record['ipv6'];
                        }
                    }
                }
            }
        }

        if ($addresses === [] || array_filter($addresses, 'commonExternalCalendarIsPublicIpAddress') !== $addresses) {
            return null;
        }
    }

    return 'https://' . strtolower($host) . (isset($parts['port']) && (int)$parts['port'] !== 443 ? ':' . (int)$parts['port'] : '')
        . ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
}

function commonExternalCalendarHttpReport($url, $username, $password, $requestBody, ?float $deadline = null)
{
    return commonExternalCalendarHttpRequest($url, $username, $password, $requestBody, 'REPORT', 1, 0, [], $deadline);
}

// Resolve DAV hrefs without ever forwarding credentials to another origin.
function commonExternalCalendarResolveHref($base, $href)
{
    $href = trim((string)$href);
    $parts = parse_url($base);
    if (!$parts || $href === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $href)) {
        return null;
    }
    $origin = 'https://' . strtolower($parts['host']) . (isset($parts['port']) && (int)$parts['port'] !== 443 ? ':' . (int)$parts['port'] : '');
    if (str_starts_with($href, '//')) {
        $href = 'https:' . $href;
    } elseif (!preg_match('/^[a-z][a-z0-9+.-]*:/i', $href)) {
        $href = $origin . ($href[0] === '/' ? $href : substr($parts['path'] ?? '/', 0, strrpos($parts['path'] ?? '/', '/') + 1) . $href);
    }
    $target = parse_url($href);
    if (!$target || strtolower($target['scheme'] ?? '') !== 'https'
        || strtolower($target['host'] ?? '') !== strtolower($parts['host'])
        || (int)($target['port'] ?? 443) !== (int)($parts['port'] ?? 443)
        || isset($target['user']) || isset($target['pass']) || isset($target['fragment'])) {
        return null;
    }
    $segments = [];
    foreach (explode('/', $target['path'] ?? '/') as $segment) {
        if ($segment === '..') {
            array_pop($segments);
        } elseif ($segment !== '.' && $segment !== '') {
            $segments[] = $segment;
        }
    }
    $path = '/' . implode('/', $segments);
    if ($path !== '/' && str_ends_with($target['path'] ?? '/', '/')) {
        $path .= '/';
    }
    return $origin . $path . (isset($target['query']) ? '?' . $target['query'] : '');
}

function commonExternalCalendarHttpRequest($url, $username, $password, $requestBody, $method = 'PROPFIND', $depth = 0, $redirects = 0, array $extraHeaders = [], ?float $deadline = null)
{
    if ($deadline !== null && microtime(true) >= $deadline) {
        return ['status' => false, 'message' => 'Le delai de synchronisation est depasse.'];
    }
    if (!function_exists('curl_init')) {
        return ['status' => false, 'message' => 'L extension cURL est indisponible sur le serveur.'];
    }

    $url = commonExternalCalendarNormalizeUrl($url);
    if ($url === null) {
        return ['status' => false, 'message' => 'Adresse CalDAV HTTPS invalide ou non autorisee.'];
    }
    // Pin the checked DNS result so validation and connection use the same address.
    $host = parse_url($url, PHP_URL_HOST);
    $ipHost = trim($host, '[]');
    $addresses = filter_var($ipHost, FILTER_VALIDATE_IP) ? [$ipHost] : (gethostbynamel($host) ?: []);
    if (!$addresses && function_exists('dns_get_record')) {
        $addresses = array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6');
    }
    if (!$addresses || (!commonExternalCalendarAllowPrivateHosts() && count(array_filter($addresses, 'commonExternalCalendarIsPublicIpAddress')) !== count($addresses))) {
        return ['status' => false, 'message' => 'Adresse du serveur CalDAV non autorisee.'];
    }
    $responseBody = '';
    $location = '';
    $curl = curl_init((string)$url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_POSTFIELDS, (string)$requestBody);
    curl_setopt($curl, CURLOPT_USERPWD, (string)$username . ':' . (string)$password);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    $timeoutMs = $deadline === null ? 45000 : max(1, min(45000, (int)(($deadline - microtime(true)) * 1000)));
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, min(10000, $timeoutMs));
    curl_setopt($curl, CURLOPT_TIMEOUT_MS, $timeoutMs);
    curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
    curl_setopt($curl, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS);
    curl_setopt($curl, CURLOPT_PROXY, '');
    if (!filter_var($ipHost, FILTER_VALIDATE_IP)) {
        $address = str_contains($addresses[0], ':') ? '[' . $addresses[0] . ']' : $addresses[0];
        curl_setopt($curl, CURLOPT_RESOLVE, [$host . ':' . (parse_url($url, PHP_URL_PORT) ?: 443) . ':' . $address]);
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Depth: ' . (int)$depth,
        'Content-Type: ' . ($method === 'PUT' ? 'text/calendar' : 'application/xml') . '; charset=UTF-8',
        'Accept: application/xml, text/calendar',
        'User-Agent: OpenMyOrganization CalDAV sync',
        ...$extraHeaders,
    ]);
    curl_setopt($curl, CURLOPT_WRITEFUNCTION, static function ($handle, $chunk) use (&$responseBody) {
        if (strlen($responseBody) + strlen($chunk) > 5 * 1024 * 1024) {
            return 0;
        }
        $responseBody .= $chunk;
        return strlen($chunk);
    });
    curl_setopt($curl, CURLOPT_HEADERFUNCTION, static function ($handle, $line) use (&$location) {
        if (stripos($line, 'Location:') === 0) {
            $location = trim(substr($line, 9));
        }
        return strlen($line);
    });

    $result = curl_exec($curl);
    $statusCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    if ($result === false) {
        return ['status' => false, 'message' => 'Connexion CalDAV impossible (reseau, certificat ou reponse trop volumineuse).'];
    }
    if (in_array($statusCode, [301, 302, 303, 307, 308], true)) {
        $target = commonExternalCalendarResolveHref($url, $location);
        if ($target === null || $redirects >= 4) {
            return ['status' => false, 'message' => 'Redirection CalDAV refusee. Utilisez directement l adresse HTTPS du serveur de synchronisation.'];
        }
        return commonExternalCalendarHttpRequest($target, $username, $password, $requestBody, $method, $depth, $redirects + 1, $extraHeaders, $deadline);
    }
    if ($statusCode < 200 || $statusCode >= 300) {
        return ['status' => false, 'code' => $statusCode, 'message' => in_array($statusCode, [401, 403], true)
            ? 'Acces refuse : verifiez l identifiant de synchronisation et le mot de passe d application.'
            : 'Le serveur CalDAV a repondu avec le code HTTP ' . $statusCode . '.'];
    }

    return ['status' => true, 'code' => $statusCode, 'body' => $responseBody, 'url' => $url];
}

function commonExternalCalendarCanCreate(ExternalCalendar $calendar, ?callable $request = null): bool
{
    $password = commonExternalCalendarDecryptPassword($calendar->get('password_encrypted'));
    if ($password === null || !(int)$calendar->get('active')) { return false; }
    $request ??= 'commonExternalCalendarHttpRequest';
    $result = $request($calendar->get('calendar_url'), $calendar->get('username'), $password,
        '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav"><d:prop>'
        . '<d:resourcetype/><d:current-user-privilege-set/><c:supported-calendar-component-set/></d:prop></d:propfind>', 'PROPFIND', 0);
    if (empty($result['status']) || stripos($result['body'], '<!DOCTYPE') !== false) { return false; }
    $document = new \DOMDocument();
    if (!@$document->loadXML($result['body'], LIBXML_NONET)) { return false; }
    $xpath = new \DOMXPath($document);
    $xpath->registerNamespace('d', 'DAV:');
    $xpath->registerNamespace('c', 'urn:ietf:params:xml:ns:caldav');
    $base = $result['url'] ?? $calendar->get('calendar_url');
    foreach ($xpath->query('/d:multistatus/d:response') as $response) {
        if (commonExternalCalendarResolveHref($base, $xpath->evaluate('string(d:href)', $response)) !== commonExternalCalendarResolveHref($base, $base)) { continue; }
        $props = 'd:propstat[contains(d:status, " 200 ")]/d:prop/';
        $priv = $props . 'd:current-user-privilege-set/d:privilege/';
        $isCalendar = $xpath->evaluate('boolean(' . $props . 'd:resourcetype/c:calendar)', $response);
        $events = !$xpath->evaluate('boolean(' . $props . 'c:supported-calendar-component-set)', $response)
            || $xpath->evaluate('boolean(' . $props . 'c:supported-calendar-component-set/c:comp[@name="VEVENT"])', $response);
        return $isCalendar && $events && (bool)$xpath->evaluate('boolean(' . $priv . 'd:bind | ' . $priv . 'd:write | ' . $priv . 'd:all)', $response);
    }
    return false;
}

function commonExternalCalendarDiscoveryRecords($xml, $baseUrl)
{
    if (!class_exists('DOMDocument') || stripos($xml, '<!DOCTYPE') !== false) {
        throw new \RuntimeException('Reponse CalDAV XML invalide ou extension DOM indisponible.');
    }
    $document = new \DOMDocument();
    if (!@$document->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA)
        || $document->documentElement->namespaceURI !== 'DAV:' || $document->documentElement->localName !== 'multistatus') {
        throw new \RuntimeException('Le serveur ne renvoie pas une reponse CalDAV valide.');
    }
    $xpath = new \DOMXPath($document);
    $xpath->registerNamespace('d', 'DAV:');
    $xpath->registerNamespace('c', 'urn:ietf:params:xml:ns:caldav');
    $xpath->registerNamespace('a', 'http://apple.com/ns/ical/');
    $records = [];
    foreach ($xpath->query('/d:multistatus/d:response') as $response) {
        $url = commonExternalCalendarResolveHref($baseUrl, $xpath->evaluate('string(d:href)', $response));
        if ($url === null) {
            continue;
        }
        $record = ['url' => $url, 'calendar' => false, 'title' => '', 'color' => '#0f766e', 'homes' => [], 'principals' => []];
        $components = null;
        $readable = null;
        foreach ($xpath->query('d:propstat', $response) as $propstat) {
            if (!preg_match('/\s200(?:\s|$)/', $xpath->evaluate('string(d:status)', $propstat))) {
                continue;
            }
            $record['calendar'] = $record['calendar'] || (bool)$xpath->evaluate('boolean(d:prop/d:resourcetype/c:calendar)', $propstat);
            $name = trim($xpath->evaluate('string(d:prop/d:displayname)', $propstat));
            if ($name !== '') {
                $record['title'] = mb_substr($name, 0, 190, 'UTF-8');
            }
            $color = trim($xpath->evaluate('string(d:prop/a:calendar-color)', $propstat));
            if (preg_match('/^#[0-9a-f]{6}([0-9a-f]{2})?$/i', $color)) {
                $record['color'] = strtolower(substr($color, 0, 7));
            }
            if ($xpath->evaluate('boolean(d:prop/c:supported-calendar-component-set)', $propstat)) {
                $components = (bool)$xpath->evaluate('boolean(d:prop/c:supported-calendar-component-set/c:comp[@name="VEVENT"])', $propstat);
            }
            if ($xpath->evaluate('boolean(d:prop/d:current-user-privilege-set)', $propstat)) {
                $readable = (bool)$xpath->evaluate('boolean(d:prop/d:current-user-privilege-set/d:privilege/d:read | d:prop/d:current-user-privilege-set/d:privilege/d:all)', $propstat);
            }
            foreach (['homes' => 'c:calendar-home-set', 'principals' => 'd:current-user-principal'] as $field => $property) {
                foreach ($xpath->query('d:prop/' . $property . '/d:href', $propstat) as $href) {
                    $target = commonExternalCalendarResolveHref($baseUrl, $href->textContent);
                    if ($target !== null) {
                        $record[$field][] = $target;
                    }
                }
            }
        }
        $record['calendar'] = $record['calendar'] && $components !== false && $readable !== false;
        $records[] = $record;
    }
    return $records;
}

function commonExternalCalendarDiscover($url, $username, $password, ?callable $request = null)
{
    $request = $request ?? 'commonExternalCalendarHttpRequest';
    $body = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<d:propfind xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:a="http://apple.com/ns/ical/">'
        . '<d:prop><d:current-user-principal/><c:calendar-home-set/><d:resourcetype/><d:displayname/>'
        . '<c:supported-calendar-component-set/><d:current-user-privilege-set/><a:calendar-color/></d:prop></d:propfind>';
    $queue = [[$url, 0]];
    $visited = [];
    $calendars = [];
    $error = 'Aucun agenda lisible trouve sur ce serveur.';
    $fallbackUsed = false;
    for ($count = 0; $count < 12; $count++) {
        if (!$queue) {
            if ($calendars || $fallbackUsed) {
                break;
            }
            $queue[] = [commonExternalCalendarResolveHref($url, '/.well-known/caldav'), 0];
            $fallbackUsed = true;
        }
        [$target, $depth] = array_shift($queue);
        if (!$target || isset($visited[$depth . ':' . $target])) {
            continue;
        }
        $visited[$depth . ':' . $target] = true;
        $result = $request($target, $username, $password, $body, 'PROPFIND', $depth);
        if (empty($result['status'])) {
            $error = $result['message'] ?? $error;
            if (in_array($result['code'] ?? 0, [401, 403], true)) {
                return ['status' => false, 'message' => $error];
            }
            continue;
        }
        $effectiveUrl = $result['url'] ?? $target;
        try {
            $records = commonExternalCalendarDiscoveryRecords($result['body'], $effectiveUrl);
        } catch (\RuntimeException $exception) {
            $error = $exception->getMessage();
            continue;
        }
        foreach ($records as $record) {
            if ($record['calendar']) {
                $calendars[$record['url']] = [
                    'url' => $record['url'],
                    'title' => $record['title'] ?: rawurldecode(basename(rtrim(parse_url($record['url'], PHP_URL_PATH), '/'))),
                    'color' => $record['color'],
                ];
                if (count($calendars) > 200) {
                    return ['status' => false, 'message' => 'Trop d agendas. Utilisez une adresse CalDAV plus precise.'];
                }
            }
            // Only follow discovery properties on the requested resource, not on every calendar.
            if ($depth === 0) {
                foreach ($record['homes'] as $home) {
                    $queue[] = [$home, 1];
                }
                if (!$record['homes'] && !$record['calendar']) {
                    foreach ($record['principals'] as $principal) {
                        $queue[] = [$principal, 0];
                    }
                }
            }
        }
        if ($depth === 0 && !$queue && !$calendars) {
            // A calendar-home URL may not expose a calendar-home-set pointing to itself.
            $queue[] = [$effectiveUrl, 1];
        }
    }
    if ($queue) {
        return ['status' => false, 'message' => 'Decouverte CalDAV trop longue. Utilisez une adresse plus precise.'];
    }
    return $calendars ? ['status' => true, 'calendars' => array_values($calendars)] : ['status' => false, 'message' => $error];
}

function commonExternalCalendarReadSourceCtag($url, $username, $password, ?float $deadline = null)
{
    if (!function_exists('curl_init') || !class_exists('DOMDocument')) {
        return null;
    }
    $body = '<?xml version="1.0" encoding="UTF-8"?><d:propfind xmlns:d="DAV:" xmlns:cs="http://calendarserver.org/ns/"><d:prop><cs:getctag/><d:sync-token/></d:prop></d:propfind>';
    $result = commonExternalCalendarHttpRequest($url, $username, $password, $body, 'PROPFIND', 0, 0, [], $deadline);
    if (empty($result['status'])) {
        return null;
    }
    $response = $result['body'];
    $document = new \DOMDocument();
    if (!@$document->loadXML($response, LIBXML_NONET | LIBXML_NOCDATA)) {
        return null;
    }
    $xpath = new \DOMXPath($document);
    $xpath->registerNamespace('d', 'DAV:');
    $xpath->registerNamespace('cs', 'http://calendarserver.org/ns/');
    $ctag = trim((string)$xpath->evaluate('string(/d:multistatus/d:response/d:propstat[contains(d:status, " 200 ")]/d:prop/cs:getctag)'));
    if ($ctag !== '') {
        return $ctag;
    }
    $token = trim((string)$xpath->evaluate('string(/d:multistatus/d:response/d:propstat[contains(d:status, " 200 ")]/d:prop/d:sync-token)'));
    return $token !== '' ? $token : null;
}

function commonExternalCalendarBuildReport(\DateTimeInterface $rangeStart, \DateTimeInterface $rangeEnd)
{
    $start = \DateTimeImmutable::createFromInterface($rangeStart)->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\\THis\\Z');
    $end = \DateTimeImmutable::createFromInterface($rangeEnd)->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\\THis\\Z');
    return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">'
        . '<d:prop><d:getetag/><c:calendar-data><c:expand start="' . $start . '" end="' . $end . '"/></c:calendar-data></d:prop>'
        . '<c:filter><c:comp-filter name="VCALENDAR"><c:comp-filter name="VEVENT"><c:time-range start="' . $start . '" end="' . $end . '"/></c:comp-filter></c:comp-filter></c:filter>'
        . '</c:calendar-query>';
}

function commonExternalCalendarUnfoldLines($calendarData)
{
    $lines = preg_split('/\r\n|\n|\r/', (string)$calendarData);
    $unfolded = [];
    foreach ($lines as $line) {
        if ($line !== '' && ($line[0] === ' ' || $line[0] === "\t") && $unfolded !== []) {
            $unfolded[count($unfolded) - 1] .= substr($line, 1);
            continue;
        }
        $unfolded[] = $line;
    }
    return $unfolded;
}

function commonExternalCalendarParseProperty($line)
{
    $separator = strpos((string)$line, ':');
    if ($separator === false) {
        return null;
    }

    $rawName = substr((string)$line, 0, $separator);
    $parts = str_getcsv($rawName, ';', '"', '\\');
    $name = strtoupper(trim((string)array_shift($parts)));
    if ($name === '') {
        return null;
    }

    $parameters = [];
    foreach ($parts as $part) {
        $parameterParts = explode('=', (string)$part, 2);
        if (count($parameterParts) !== 2) {
            continue;
        }
        $parameters[strtoupper(trim($parameterParts[0]))] = trim($parameterParts[1], '"');
    }

    return [
        'name' => $name,
        'parameters' => $parameters,
        'value' => substr((string)$line, $separator + 1),
    ];
}

function commonExternalCalendarUnescapeText($value)
{
    return str_replace(['\\n', '\\N', '\\,', '\\;', '\\\\'], ["\n", "\n", ',', ';', '\\'], (string)$value);
}

function commonExternalCalendarParseDateTime($value, array $parameters, \DateTimeZone $defaultTimezone)
{
    $value = trim((string)$value);
    $isAllDay = preg_match('/^\d{8}$/', $value) === 1;
    $timezone = $defaultTimezone;
    if (!$isAllDay && str_ends_with($value, 'Z')) {
        $timezone = new \DateTimeZone('UTC');
    } elseif (!empty($parameters['TZID'])) {
        try {
            $timezone = new \DateTimeZone((string)$parameters['TZID']);
        } catch (\Exception $exception) {
            $timezone = $defaultTimezone;
        }
    }

    $format = $isAllDay ? '!Ymd' : (str_ends_with($value, 'Z') ? '!Ymd\\THis\\Z' : '!Ymd\\THis');
    $date = \DateTimeImmutable::createFromFormat($format, $value, $timezone);
    $errors = \DateTimeImmutable::getLastErrors();
    if (!$date instanceof \DateTimeImmutable || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        return null;
    }

    return ['value' => $date, 'is_all_day' => $isAllDay, 'timezone' => $timezone];
}

function commonExternalCalendarParseEvents($calendarData, $etag = '', $strict = false)
{
    $events = [];
    $eventCount = 0;
    $current = null;
    $nestedDepth = 0;
    foreach (commonExternalCalendarUnfoldLines($calendarData) as $line) {
        $property = commonExternalCalendarParseProperty($line);
        if (!is_array($property)) {
            continue;
        }
        $name = $property['name'];
        $value = strtoupper(trim((string)$property['value']));
        if ($name === 'BEGIN' && $value === 'VEVENT') {
            $eventCount++;
            $current = [];
            $nestedDepth = 0;
            continue;
        }
        if ($name === 'END' && $value === 'VEVENT') {
            if (is_array($current) && $nestedDepth === 0) {
                $events[] = $current;
            }
            $current = null;
            continue;
        }
        if (!is_array($current)) {
            continue;
        }
        if ($name === 'BEGIN') {
            $nestedDepth++;
            continue;
        }
        if ($name === 'END' && $nestedDepth > 0) {
            $nestedDepth--;
            continue;
        }
        if ($nestedDepth === 0 && !isset($current[$name])) {
            $current[$name] = $property;
        }
    }

    if ($strict && ($eventCount === 0 || count($events) !== $eventCount || $current !== null)) {
        throw new \RuntimeException('Incomplete event data');
    }
    $defaultTimezone = new \DateTimeZone(date_default_timezone_get());
    $parsed = [];
    foreach ($events as $properties) {
        if (strtoupper(trim((string)($properties['STATUS']['value'] ?? ''))) === 'CANCELLED') {
            continue;
        }
        $uid = trim((string)commonExternalCalendarUnescapeText($properties['UID']['value'] ?? ''));
        $startProperty = $properties['DTSTART'] ?? null;
        if ($uid === '' || !is_array($startProperty)) {
            if ($strict) { throw new \RuntimeException('Invalid calendar event'); }
            continue;
        }
        if ($strict && (isset($properties['RRULE']) || isset($properties['RDATE']))) {
            throw new \RuntimeException('The server did not expand recurring events');
        }
        if ($strict) {
            foreach (['DTSTART', 'DTEND'] as $dateProperty) {
                if (!empty($properties[$dateProperty]['parameters']['TZID'])) {
                    try { new \DateTimeZone($properties[$dateProperty]['parameters']['TZID']); }
                    catch (\Throwable $exception) { throw new \RuntimeException('Unsupported event timezone'); }
                }
            }
        }
        $start = commonExternalCalendarParseDateTime($startProperty['value'], (array)$startProperty['parameters'], $defaultTimezone);
        if (!is_array($start)) {
            if ($strict) { throw new \RuntimeException('Invalid event start'); }
            continue;
        }
        $endProperty = $properties['DTEND'] ?? null;
        $end = is_array($endProperty)
            ? commonExternalCalendarParseDateTime($endProperty['value'], (array)$endProperty['parameters'], $start['timezone'])
            : null;
        if (is_array($end) && (bool)$end['is_all_day'] !== (bool)$start['is_all_day']) {
            if ($strict) { throw new \RuntimeException('Invalid event end'); }
            continue;
        }
        $startAt = $start['value'];
        if (!is_array($end)) {
            if (isset($properties['DURATION'])) {
                try { $endAt = $startAt->add(new \DateInterval($properties['DURATION']['value'])); }
                catch (\Throwable $exception) { throw new \RuntimeException('Invalid event duration'); }
            } else {
                if ($strict && isset($properties['DTEND'])) { throw new \RuntimeException('Invalid event end'); }
                $endAt = $start['is_all_day'] ? $startAt->modify('+1 day') : $startAt->modify('+1 hour');
            }
        } elseif ($start['is_all_day']) {
            $endAt = $end['value']->setTime(0, 0, 0);
        } else {
            $endAt = $end['value'];
        }
        // OMO's display cache uses inclusive all-day ends; live free/busy uses CalDAV's exclusive end.
        if ($start['is_all_day'] && !$strict) { $endAt = $endAt->modify('-1 second'); }
        if ($endAt < $startAt) {
            if ($strict) { throw new \RuntimeException('Invalid event interval'); }
            continue;
        }

        $recurrence = trim((string)commonExternalCalendarUnescapeText($properties['RECURRENCE-ID']['value'] ?? ''));
        $sourceKey = $uid . ($recurrence !== '' ? '|' . $recurrence : '|' . $startAt->format('c'));
        if (strlen($sourceKey) > 512) {
            $sourceKey = hash('sha256', $sourceKey);
        }
        $parsed[$sourceKey] = [
            'source_key' => $sourceKey,
            'source_etag' => trim((string)$etag),
            'title' => trim((string)commonExternalCalendarUnescapeText($properties['SUMMARY']['value'] ?? '')) ?: 'Evenement externe',
            'description' => trim((string)commonExternalCalendarUnescapeText($properties['DESCRIPTION']['value'] ?? '')),
            'location' => trim((string)commonExternalCalendarUnescapeText($properties['LOCATION']['value'] ?? '')),
            'timezone' => $start['timezone']->getName(),
            'start_at' => $startAt->setTimezone($defaultTimezone),
            'end_at' => $endAt->setTimezone($defaultTimezone),
            'is_all_day' => !empty($start['is_all_day']) ? 1 : 0,
            'is_busy' => strtoupper(trim($properties['TRANSP']['value'] ?? 'OPAQUE')) === 'TRANSPARENT' ? 0 : 1,
        ];
    }

    return array_values($parsed);
}

function commonExternalCalendarParseReport($xml, $strict = false)
{
    if (!class_exists('DOMDocument')) {
        return ['status' => false, 'message' => 'L extension DOM est indisponible sur le serveur.'];
    }
    $document = new \DOMDocument();
    if (stripos((string)$xml, '<!DOCTYPE') !== false || !@$document->loadXML((string)$xml, LIBXML_NONET | LIBXML_NOCDATA)
        || $document->documentElement->namespaceURI !== 'DAV:' || $document->documentElement->localName !== 'multistatus') {
        return ['status' => false, 'message' => 'La reponse CalDAV est invalide.'];
    }
    $xpath = new \DOMXPath($document);
    $xpath->registerNamespace('d', 'DAV:');
    $xpath->registerNamespace('c', 'urn:ietf:params:xml:ns:caldav');
    $events = [];
    foreach ($xpath->query('/d:multistatus/d:response') ?: [] as $response) {
        $calendarData = '';
        $etag = '';
        foreach ($xpath->query('./d:propstat', $response) ?: [] as $propstat) {
            $status = trim((string)$xpath->evaluate('string(./d:status)', $propstat));
            if (strpos($status, ' 200 ') === false) {
                continue;
            }
            $calendarData = trim((string)$xpath->evaluate('string(./d:prop/c:calendar-data)', $propstat));
            $etag = trim((string)$xpath->evaluate('string(./d:prop/d:getetag)', $propstat));
            if ($calendarData !== '') {
                break;
            }
        }
        if ($calendarData !== '') {
            if ($strict && (!str_contains($calendarData, 'BEGIN:VCALENDAR') || !str_contains($calendarData, 'END:VCALENDAR'))) {
                return ['status' => false, 'message' => 'Reponse calendrier incomplete.'];
            }
            try { $parsedEvents = commonExternalCalendarParseEvents($calendarData, $etag, $strict); }
            catch (\RuntimeException $exception) { return ['status' => false, 'message' => 'Les disponibilites ne peuvent pas etre verifiees.']; }
            foreach ($parsedEvents as $event) {
                $events[$event['source_key']] = $event;
            }
        } elseif ($strict) {
            return ['status' => false, 'message' => 'Les disponibilites ne peuvent pas etre verifiees.'];
        }
    }

    return ['status' => true, 'events' => array_values($events)];
}

function commonExternalCalendarSynchronize(ExternalCalendar $calendar, $rangeStart = null, $rangeEnd = null, $force = false, ?float $deadline = null)
{
    $calendarId = (int)$calendar->getId();
    $url = commonExternalCalendarNormalizeUrl($calendar->get('calendar_url'));
    $password = commonExternalCalendarDecryptPassword($calendar->get('password_encrypted'));
    if ($calendarId <= 0 || $url === null || trim((string)$calendar->get('username')) === '' || $password === null) {
        $message = 'La configuration CalDAV est incomplete ou ne peut pas etre lue.';
        $calendar->markSyncResult(false, $message);
        return ['status' => false, 'message' => $message];
    }
    $rangeStart = $rangeStart instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($rangeStart) : new \DateTimeImmutable('-30 days');
    $rangeEnd = $rangeEnd instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($rangeEnd) : new \DateTimeImmutable('+400 days');
    if ($rangeEnd <= $rangeStart) {
        return ['status' => false, 'message' => 'La plage de synchronisation est invalide.'];
    }

    $sourceCtag = commonExternalCalendarReadSourceCtag($url, trim((string)$calendar->get('username')), $password, $deadline);
    if (!$force && $sourceCtag !== null && hash_equals((string)$calendar->get('source_ctag'), $sourceCtag)) {
        $calendar->markSyncResult(true, '', $sourceCtag);
        return ['status' => true, 'count' => 0, 'unchanged' => true];
    }

    $response = commonExternalCalendarHttpReport(
        $url,
        trim((string)$calendar->get('username')),
        $password,
        commonExternalCalendarBuildReport($rangeStart, $rangeEnd),
        $deadline
    );
    if (empty($response['status'])) {
        $message = trim((string)($response['message'] ?? 'Synchronisation CalDAV impossible.'));
        $calendar->markSyncResult(false, $message);
        return ['status' => false, 'message' => $message];
    }
    $parsed = commonExternalCalendarParseReport((string)$response['body']);
    if (empty($parsed['status'])) {
        $message = trim((string)($parsed['message'] ?? 'La reponse CalDAV ne peut pas etre lue.'));
        $calendar->markSyncResult(false, $message);
        return ['status' => false, 'message' => $message];
    }

    $pdo = \dbObject\DbObject::getPdo();
    if (!$pdo instanceof \PDO) {
        return ['status' => false, 'message' => 'La base de donnees est indisponible.'];
    }
    try {
        $pdo->beginTransaction();
        if (!ExternalCalendarEvent::deactivateInRange($calendarId, $rangeStart, $rangeEnd)) {
            throw new \RuntimeException('Impossible de preparer les evenements importes.');
        }
        foreach ((array)$parsed['events'] as $values) {
            $event = ExternalCalendarEvent::findForCalendarSourceKey($calendarId, (string)$values['source_key']);
            if (!$event instanceof ExternalCalendarEvent) {
                $event = new ExternalCalendarEvent();
                $event->set('IDexternalcalendar', $calendarId);
                $event->set('source_key', (string)$values['source_key']);
                $event->set('created_at', new \DateTimeImmutable('now'));
            }
            foreach (['source_etag', 'title', 'description', 'location', 'timezone', 'start_at', 'end_at', 'is_all_day', 'is_busy'] as $field) {
                $event->set($field, $values[$field] ?? null);
            }
            $event->set('active', 1);
            $event->set('updated_at', new \DateTimeImmutable('now'));
            $saveResult = $event->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                throw new \RuntimeException('Impossible d enregistrer les evenements importes.');
            }
        }
        $pdo->commit();
    } catch (\Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = 'La synchronisation CalDAV a echoue.';
        error_log('OMO external calendar synchronization failed: ' . $exception->getMessage());
        $calendar->markSyncResult(false, $message);
        return ['status' => false, 'message' => $message];
    }

    $calendar->markSyncResult(true, '', $sourceCtag);
    return ['status' => true, 'count' => count((array)$parsed['events'])];
}

/** Refresh only stale calendars of a validated invitee, within the request's shared time budget. */
function commonExternalCalendarRefreshForAvailability(int $userId, float $deadline, ?callable $synchronize = null): void
{
    if (!ExternalCalendar::isStorageAvailable() || microtime(true) >= $deadline) { return; }
    // Share the booking lock so a concurrent refresh cannot overwrite a new reservation.
    if (!\dbObject\MeetingProfile::lock($userId)) { return; }
    try {
        $calendars = new ArrayExternalCalendar();
        $calendars->loadForUser($userId, true);
        $synchronize ??= 'commonExternalCalendarSynchronize';
        foreach ($calendars as $calendar) {
            if (microtime(true) >= $deadline) { break; }
            $last = $calendar->get('last_sync_at');
            $failed = trim((string)$calendar->get('last_sync_error')) !== '';
            if ($last instanceof \DateTimeInterface && $last->getTimestamp() > time() - ($failed ? 60 : 7200)) { continue; }
            $synchronize($calendar, null, null, false, min($deadline, microtime(true) + 8));
        }
    } finally {
        \dbObject\MeetingProfile::unlock($userId);
    }
}

function commonExternalCalendarSynchronizeDue($limit = 10, $minimumAgeMinutes = 120)
{
    $calendars = new ArrayExternalCalendar();
    $calendars->loadForUser(0, true);
    if (count($calendars) === 0 && ExternalCalendar::isStorageAvailable()) {
        $calendars->load([
            'where' => [
                ['field' => 'active', 'value' => 1],
            ],
            'orderBy' => [
                ['field' => 'last_sync_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);
    }
    $processed = 0;
    $threshold = new \DateTimeImmutable('-' . max(1, (int)$minimumAgeMinutes) . ' minutes');
    foreach ($calendars as $calendar) {
        if (!($calendar instanceof ExternalCalendar) || $processed >= max(1, (int)$limit)) {
            continue;
        }
        $lastSyncAt = $calendar->get('last_sync_at');
        if ($lastSyncAt instanceof \DateTimeInterface && $lastSyncAt > $threshold) {
            continue;
        }
        commonExternalCalendarSynchronize($calendar);
        $processed++;
    }
    return $processed;
}
