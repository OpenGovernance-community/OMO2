<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common/external_calendar.php';

function expectDiscovery(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function davResponse(string $href, string $properties, string $status = '200 OK'): string
{
    return '<d:response><d:href>' . htmlspecialchars($href, ENT_XML1) . '</d:href><d:propstat><d:prop>'
        . $properties . '</d:prop><d:status>HTTP/1.1 ' . $status . '</d:status></d:propstat></d:response>';
}

function davBody(string $responses): string
{
    return '<d:multistatus xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav" xmlns:a="http://apple.com/ns/ical/">'
        . $responses . '</d:multistatus>';
}

function calendarProperties(string $name, string $component = 'VEVENT'): string
{
    return '<d:resourcetype><d:collection/><c:calendar/></d:resourcetype><d:displayname>' . $name . '</d:displayname>'
        . '<c:supported-calendar-component-set><c:comp name="' . $component . '"/></c:supported-calendar-component-set>';
}

// Common three-step discovery works for both Nextcloud and a kSuite-like principal layout.
foreach ([
    ['https://cloud.example/remote.php/dav', '/remote.php/dav/principals/users/DavidD/', '/remote.php/dav/calendars/DavidD/'],
    ['https://sync.example/', '/principals/AB12345/', '/calendars/AB12345/'],
] as [$entry, $principal, $home]) {
    $origin = 'https://' . parse_url($entry, PHP_URL_HOST);
    $calls = [];
    $request = static function ($url, $user, $password, $body, $method, $depth) use (&$calls, $entry, $origin, $principal, $home) {
        $calls[] = [$url, $depth];
        expectDiscovery($method === 'PROPFIND' && $user === 'account' && $password === 'app-password', 'Authenticated PROPFIND expected.');
        if ($url === $entry && $depth === 0) {
            $xml = davResponse(parse_url($entry, PHP_URL_PATH) ?: '/', '<d:current-user-principal><d:href>' . $principal . '</d:href></d:current-user-principal>');
        } elseif ($url === $origin . $principal && $depth === 0) {
            $xml = davResponse($principal, '<c:calendar-home-set><d:href>' . $home . '</d:href></c:calendar-home-set>');
        } elseif ($url === $origin . $home && $depth === 1) {
            $xml = davResponse($home, '<d:resourcetype><d:collection/></d:resourcetype>')
                . davResponse($home . 'personal/', calendarProperties('Personnel &amp; famille') . '<a:calendar-color>#AABBCCFF</a:calendar-color>')
                . davResponse($home . 'shared%20team/', calendarProperties('Equipe') . '<d:current-user-privilege-set><d:privilege><d:read/></d:privilege></d:current-user-privilege-set>')
                . davResponse($home . 'tasks/', calendarProperties('Tasks', 'VTODO'))
                . davResponse($home . 'private/', calendarProperties('Private'), '403 Forbidden')
                . davResponse($home . 'busy/', calendarProperties('Busy') . '<d:current-user-privilege-set><d:privilege><c:read-free-busy/></d:privilege></d:current-user-privilege-set>')
                . davResponse('https://other.example/calendar/', calendarProperties('Foreign'));
        } else {
            throw new RuntimeException('Unexpected discovery URL: ' . $url);
        }
        return ['status' => true, 'url' => $url, 'body' => davBody($xml)];
    };
    $result = commonExternalCalendarDiscover($entry, 'account', 'app-password', $request);
    expectDiscovery($result['status'] && count($result['calendars']) === 2, 'Only readable VEVENT calendars should be proposed.');
    expectDiscovery(count($calls) === 3, 'Root, principal, then calendar home should require three requests.');
    expectDiscovery($result['calendars'][0]['title'] === 'Personnel & famille', 'Calendar names must be decoded as text.');
    expectDiscovery($result['calendars'][0]['color'] === '#aabbcc', 'RGBA color must become an HTML RGB color.');
    expectDiscovery(str_contains($result['calendars'][1]['url'], 'shared%20team/'), 'Encoded hrefs must remain encoded.');
}

$entry = 'https://cloud.example/dav/personal/';
$direct = commonExternalCalendarDiscover($entry, 'u', 'p', static fn($url) => [
    'status' => true, 'url' => $url, 'body' => davBody(davResponse('/dav/personal/', calendarProperties('Personal'))),
]);
expectDiscovery($direct['status'] && count($direct['calendars']) === 1, 'Direct calendar URLs remain supported.');

// Root can be an HTML landing page; well-known leads to a DAV home.
$calls = [];
$fallback = commonExternalCalendarDiscover('https://cloud.example/', 'u', 'p', static function ($url, $u, $p, $body, $method, $depth) use (&$calls) {
    $calls[] = $url;
    if ($url === 'https://cloud.example/.well-known/caldav') {
        return ['status' => true, 'url' => 'https://cloud.example/dav/', 'body' => davBody(davResponse('/dav/', '<c:calendar-home-set><d:href>/home/</d:href></c:calendar-home-set>'))];
    }
    if ($url === 'https://cloud.example/home/' && $depth === 1) {
        return ['status' => true, 'url' => $url, 'body' => davBody(davResponse('/home/test/', calendarProperties('Test')))];
    }
    return ['status' => true, 'url' => $url, 'body' => '<html>Landing page</html>'];
});
expectDiscovery($fallback['status'] && count($calls) === 3, 'Well-known fallback must work after an HTML root response.');

$calls = 0;
$denied = commonExternalCalendarDiscover('https://cloud.example/', 'u', 'p', static function () use (&$calls) {
    $calls++;
    return ['status' => false, 'code' => 401, 'message' => 'Denied'];
});
expectDiscovery(!$denied['status'] && $calls === 1, 'Authentication errors must stop discovery immediately.');

$noCalendars = commonExternalCalendarDiscover('https://cloud.example/', 'u', 'p', static fn($url) => [
    'status' => true, 'url' => $url, 'body' => davBody(davResponse('/', '<d:resourcetype><d:collection/></d:resourcetype>')),
]);
expectDiscovery(!$noCalendars['status'], 'Empty collections must not look like a connected calendar.');

foreach (['https://evil.example/a', '//evil.example/a', 'http://cloud.example/a', 'https://cloud.example:444/a', 'https://user:pass@cloud.example/a', "/path\\evil", "/path\nbad"] as $href) {
    expectDiscovery(commonExternalCalendarResolveHref($entry, $href) === null, 'Unsafe DAV href accepted.');
}
expectDiscovery(commonExternalCalendarResolveHref($entry, '../team/') === 'https://cloud.example/dav/team/', 'Relative href resolution failed.');
expectDiscovery(commonExternalCalendarResolveHref($entry, 'https://CLOUD.example:443/a/') === 'https://cloud.example/a/', 'Default HTTPS port should match.');
expectDiscovery(commonExternalCalendarNormalizeUrl('https://127.0.0.1/dav/') === null, 'Loopback must be blocked by default.');
expectDiscovery(commonExternalCalendarNormalizeUrl('https://169.254.169.254/dav/') === null, 'Link-local metadata endpoint must be blocked.');
expectDiscovery(commonExternalCalendarNormalizeUrl('https://[::1]/dav/') === null, 'IPv6 loopback must be blocked.');
expectDiscovery(commonExternalCalendarNormalizeUrl('https://[2606:4700:4700::1111]/dav/') !== null, 'Public IPv6 literals should be accepted.');
try {
    commonExternalCalendarDiscoveryRecords('<!DOCTYPE test [<!ENTITY x SYSTEM "file:///etc/passwd">]><test>&x;</test>', $entry);
    throw new RuntimeException('DOCTYPE accepted.');
} catch (RuntimeException $exception) {
    expectDiscovery($exception->getMessage() !== 'DOCTYPE accepted.', 'DOCTYPE must be rejected.');
}

echo "external_calendar_discovery_test: OK\n";
