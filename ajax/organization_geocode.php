<?php

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

header('Content-Type: application/json; charset=UTF-8');

function organizationGeocodeRespond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ((int)commonGetCurrentUserId() <= 0) {
    organizationGeocodeRespond(401, array('success' => false, 'message' => 'Connexion requise.'));
}

$organizationId = (int)($_GET['oid'] ?? 0);
if ($organizationId > 0) {
    $organization = new \dbObject\Organization();
    if (!$organization->load($organizationId) || !$organization->canEdit()) {
        organizationGeocodeRespond(403, array('success' => false, 'message' => 'Accès refusé.'));
    }
}

$address = trim((string)($_GET['address'] ?? ''));
if (strlen($address) < 3 || strlen($address) > 400 || preg_match('/[\x00-\x1F\x7F]/', $address)) {
    organizationGeocodeRespond(422, array('success' => false, 'message' => 'Adresse invalide.'));
}

$cacheDirectory = sys_get_temp_dir() . '/omo-geocode-' . hash('sha256', __DIR__);
if (!is_dir($cacheDirectory) && !mkdir($cacheDirectory, 0700, true) && !is_dir($cacheDirectory)) {
    organizationGeocodeRespond(503, array('success' => false, 'message' => 'Recherche indisponible.'));
}

$cacheFile = $cacheDirectory . '/' . hash('sha256', $address) . '.json';
$lock = fopen($cacheDirectory . '/request.lock', 'c+');
if ($lock === false || !flock($lock, LOCK_EX)) {
    organizationGeocodeRespond(503, array('success' => false, 'message' => 'Recherche indisponible.'));
}

$cached = is_file($cacheFile) && filemtime($cacheFile) >= time() - 604800
    ? json_decode((string)file_get_contents($cacheFile), true)
    : null;
if (is_array($cached)) {
    flock($lock, LOCK_UN);
    fclose($lock);
    organizationGeocodeRespond(200, $cached);
}

// The public Nominatim service permits at most one request per second per application.
$lastRequestFile = $cacheDirectory . '/last-request';
$lastRequest = is_file($lastRequestFile) ? (float)file_get_contents($lastRequestFile) : 0.0;
$delay = 1.0 - (microtime(true) - $lastRequest);
if ($delay > 0) {
    usleep((int)ceil($delay * 1000000));
}
file_put_contents($lastRequestFile, (string)microtime(true));

$baseUrl = function_exists('envValue') ? trim((string)envValue('NOMINATIM_SEARCH_URL', 'https://nominatim.openstreetmap.org/search')) : 'https://nominatim.openstreetmap.org/search';
if (!filter_var($baseUrl, FILTER_VALIDATE_URL) || parse_url($baseUrl, PHP_URL_SCHEME) !== 'https') {
    flock($lock, LOCK_UN);
    fclose($lock);
    organizationGeocodeRespond(503, array('success' => false, 'message' => 'Service de recherche mal configuré.'));
}

$url = $baseUrl . '?' . http_build_query(array('q' => $address, 'format' => 'jsonv2', 'limit' => 1, 'addressdetails' => 0), '', '&', PHP_QUERY_RFC3986);
$curl = curl_init($url);
if ($curl === false) {
    flock($lock, LOCK_UN);
    fclose($lock);
    organizationGeocodeRespond(503, array('success' => false, 'message' => 'Recherche indisponible.'));
}
curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 4,
    CURLOPT_TIMEOUT => 8,
    CURLOPT_HTTPHEADER => array('Accept: application/json', 'Accept-Language: fr'),
    CURLOPT_USERAGENT => 'OpenMyOrganization/1.0 (organization location search; https://omo2.org)',
));
$body = curl_exec($curl);
$httpStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);

if ($body === false || $httpStatus !== 200) {
    flock($lock, LOCK_UN);
    fclose($lock);
    organizationGeocodeRespond(502, array('success' => false, 'message' => 'Service de recherche indisponible.'));
}

$results = json_decode($body, true);
if (!is_array($results) || !array_is_list($results)) {
    flock($lock, LOCK_UN);
    fclose($lock);
    organizationGeocodeRespond(502, array('success' => false, 'message' => 'Réponse de recherche invalide.'));
}
$first = is_array($results) ? ($results[0] ?? null) : null;
$latitude = is_array($first) ? ($first['lat'] ?? null) : null;
$longitude = is_array($first) ? ($first['lon'] ?? null) : null;
if ($first === null) {
    $payload = array('success' => true, 'found' => false);
} elseif (is_numeric($latitude) && is_numeric($longitude) && abs((float)$latitude) <= 90 && abs((float)$longitude) <= 180) {
    $payload = array(
        'success' => true,
        'found' => true,
        'lat' => (float)$latitude,
        'long' => (float)$longitude,
        'place' => (string)($first['display_name'] ?? $address),
    );
} else {
    flock($lock, LOCK_UN);
    fclose($lock);
    organizationGeocodeRespond(502, array('success' => false, 'message' => 'Réponse de recherche invalide.'));
}

file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
flock($lock, LOCK_UN);
fclose($lock);
organizationGeocodeRespond(200, $payload);
