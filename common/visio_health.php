<?php
declare(strict_types=1);

/** Fixed, server-configured targets only. Never forward visitor credentials or follow redirects. */
function visioHealthHttp(string $url, ?string $xml = null, int $timeoutMs = 2000): array
{
    $curl = curl_init($url);
    if ($curl === false) return ['code' => 0, 'body' => ''];
    $body = '';
    curl_setopt_array($curl, [
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT_MS => 1000,
        CURLOPT_TIMEOUT_MS => $timeoutMs,
        CURLOPT_USERAGENT => 'OMO2-Visio-Health/1.0',
        CURLOPT_HTTPHEADER => $xml === null ? ['Accept: */*'] : ['Content-Type: text/xml; charset=UTF-8'],
        CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
            if (strlen($body) + strlen($chunk) > 524288) return 0;
            $body .= $chunk;
            return strlen($chunk);
        },
    ]);
    // This option also depends on the libcurl build, not only the PHP version.
    if (defined('CURLOPT_PROTOCOLS_STR')) {
        curl_setopt($curl, CURLOPT_PROTOCOLS_STR, 'http,https');
    } else {
        curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
    }
    if ($xml !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $xml]);
    $success = curl_exec($curl);
    $code = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    if ($success === false || $code !== 200) {
        // Keep diagnostics private and omit target hosts, query strings, credentials and response bodies.
        error_log('Visio HTTP probe failed (path=' . (string)parse_url($url, PHP_URL_PATH)
            . ', curl=' . curl_errno($curl) . ', http=' . $code . ').');
    }
    return ['code' => $success === false ? 0 : $code, 'body' => $body];
}

/** Opens then closes an anonymous BOSH session; no room, JWT or media is created. */
function visioHealthSignaling(string $url, string $domain, callable $request): bool
{
    $rid = random_int(1000000, 999999999);
    $namespace = 'http://jabber.org/protocol/httpbind';
    $xml = '<body xmlns="' . $namespace . '" xmlns:xmpp="urn:xmpp:xbosh" xmpp:version="1.0"'
        . ' rid="' . $rid . '" to="' . htmlspecialchars($domain, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '" wait="1" hold="0" ver="1.6"/>';
    $response = $request($url . '/http-bind', $xml);
    if ($response['code'] !== 200 || str_contains($response['body'], '<!DOCTYPE')) return false;
    $previous = libxml_use_internal_errors(true);
    try {
        $document = new DOMDocument();
        if (!$document->loadXML($response['body'], LIBXML_NONET)) return false;
        $root = $document->documentElement;
        if ($root === null || $root->localName !== 'body' || $root->namespaceURI !== $namespace
            || $root->getAttribute('type') === 'terminate') return false;
        $sid = $root->getAttribute('sid');
        if ($sid === '' || strlen($sid) > 256) return false;
        $close = '<body xmlns="' . $namespace . '" rid="' . ($rid + 1) . '" sid="'
            . htmlspecialchars($sid, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '" type="terminate"/>';
        $closed = $request($url . '/http-bind', $close, 1000);
        return $closed['code'] === 200;
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
}

function visioHealthCheck(array $config, callable $database, ?callable $request = null): array
{
    $checks = array_fill_keys(['configuration', 'database', 'frontend', 'signaling', 'jicofo', 'videobridge'], 'unavailable');
    if (empty($config['enabled'])) return ['status' => 'error', 'checks' => $checks];
    $checks['configuration'] = 'ok';
    try {
        $checks['database'] = $database() ? 'ok' : 'unavailable';
    } catch (Throwable $exception) {
        error_log('Visio database health check failed (' . get_class($exception) . ').');
    }
    $request ??= 'visioHealthHttp';
    $url = $config['url'];
    foreach (['frontend' => '/external_api.js', 'jicofo' => '/omo-health/jicofo', 'videobridge' => '/omo-health/videobridge'] as $name => $path) {
        try {
            $response = $request($url . $path);
            $healthy = $response['code'] === 200;
            // Reject a login page or Jitsi's fallback HTML with a misleading 200.
            $healthy = $healthy && ($name === 'frontend'
                ? str_contains($response['body'], 'JitsiMeetExternalAPI')
                : in_array(trim($response['body']), $name === 'jicofo' ? ['', 'OK'] : [''], true));
            $checks[$name] = $healthy ? 'ok' : 'unavailable';
        } catch (Throwable $exception) {
            error_log('Visio ' . $name . ' health check failed (' . get_class($exception) . ').');
        }
    }
    try {
        $response = $request($url . '/config.js');
        if ($response['code'] !== 200 || !str_contains($response['body'], 'config.hosts')) $checks['frontend'] = 'unavailable';
        $checks['signaling'] = visioHealthSignaling($url, $config['xmpp'], $request) ? 'ok' : 'unavailable';
    } catch (Throwable $exception) {
        error_log('Visio signaling health check failed (' . get_class($exception) . ').');
    }
    return ['status' => in_array('unavailable', $checks, true) ? 'error' : 'ok', 'checks' => $checks];
}
