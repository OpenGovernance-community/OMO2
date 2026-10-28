<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

// Run inside the local app container; all requests stay on the Docker network.
$_SERVER['HTTP_HOST'] = 'org1.localtest.me';
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/common/spacedeck.php';

function spacedeckLocalCheck(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

function spacedeckLocalRequest(string $url, array $headers = []): array
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = curl_exec($curl);
    spacedeckLocalCheck(is_string($body), 'Local HTTP request failed.');
    return ['status' => (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'body' => $body];
}

$spaceId = 'omo-local-demo';
$callback = 'http://app/omo/api/documents/spacedeck/access.php';
foreach ([0, 1, 2] as $level) {
    $token = omoSpacedeckBuildExternalAccessToken($spaceId, 1, 'Local integration test', $level, time() + 60, 'fr');
    spacedeckLocalCheck($token !== '', 'Local signing key is missing.');
    $headers = ['X-Spacedeck-Space-Id: ' . $spaceId, 'X-Spacedeck-Request-Method: GET'];
    $response = spacedeckLocalRequest($callback . '/' . rawurlencode($token) . '/GET', $headers);
    $data = json_decode($response['body'], true);
    spacedeckLocalCheck($response['status'] === 200 && ($data['access_level'] ?? null) === $level,
        'The real PHP callback did not return the expected permission level.');
    if ($level > 0) {
        spacedeckLocalCheck(($data['user']['name'] ?? '') === 'Local integration test'
            && ($data['user']['language'] ?? '') === 'fr', 'User identity or language was lost.');
    }

    $board = spacedeckLocalRequest('http://spacedeck:9666/spaces/' . $spaceId . '?externalToken=' . rawurlencode($token));
    spacedeckLocalCheck($level > 0 ? $board['status'] === 200 : $board['status'] >= 400,
        'SpaceDeck did not enforce the permission returned by OMO.');
}

foreach ([
    ['invalid', $spaceId, 'GET', 'GET'],
    [$token, 'another-board', 'GET', 'GET'],
    [$token, $spaceId, 'POST', 'GET'],
    [$token, $spaceId, 'GET', 'PATCH'],
] as [$candidate, $boardId, $headerMethod, $pathMethod]) {
    $response = spacedeckLocalRequest($callback . '/' . rawurlencode($candidate) . '/' . $pathMethod, [
        'X-Spacedeck-Space-Id: ' . $boardId,
        'X-Spacedeck-Request-Method: ' . $headerMethod,
    ]);
    $data = json_decode($response['body'], true);
    spacedeckLocalCheck($response['status'] === 200 && ($data['access_level'] ?? null) === 0,
        'An invalid token, board or method was accepted.');
}

$blocked = spacedeckLocalRequest('http://app/test/spacedeck_access.php/invalid/GET');
spacedeckLocalCheck($blocked['status'] === 403, 'The protected test directory must remain blocked.');
echo "SpaceDeck local integration: OK (real PHP callback, identity, permissions, invalid requests, protected test directory).\n";
