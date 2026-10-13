<?php
// Local Docker HTTP transport shared by CalDAV cache and rate-limit tests.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
function calendarCacheHttp(array $items, string $path, string $method, string $body = '', array $extraHeaders = []): array
{
    $headers = [];
    if (!array_filter($extraHeaders, static fn($header) => stripos($header, 'Depth:') === 0)) $extraHeaders[] = 'Depth: 0';
    $curl = curl_init('https://localtest.me' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_USERPWD => $items['user']->get('email') . ':' . $items['password'], CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_RESOLVE => ['localtest.me:443:127.0.0.1'], CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/xml'], $extraHeaders),
        CURLOPT_POSTFIELDS => $body, CURLOPT_HEADERFUNCTION => static function ($curl, $line) use (&$headers) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            return strlen($line);
        }]);
    $response = curl_exec($curl);
    mcpCheck(is_string($response), 'Local HTTP request failed: ' . curl_error($curl));
    return ['status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'body' => $response, 'headers' => $headers,
        'seconds' => curl_getinfo($curl, CURLINFO_TOTAL_TIME)];
}
