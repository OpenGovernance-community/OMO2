<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once __DIR__ . '/share-feed.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('Content-Type: text/plain; charset=UTF-8');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD'); http_response_code(405); exit;
}
// This endpoint authenticates only with the capability, never with the visitor's session.
if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
try {
    if (!\dbObject\CalendarShare::isStorageAvailable()) { http_response_code(503); exit; }
    $share = \dbObject\CalendarShare::resolveToken((string)($_GET['token'] ?? ''));
    if (!$share) { http_response_code(404); exit; }
    $content = calendarShareBuildFeed($share);
    $etag = '"' . hash('sha256', $content) . '"';
    header('ETag: ' . $etag);
    if (trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) { http_response_code(304); exit; }
    header('Content-Type: text/calendar; charset=UTF-8');
    header('Content-Disposition: inline; filename="agenda.ics"');
    if ($method !== 'HEAD') { echo $content; }
} catch (Throwable $exception) {
    // Do not log the capability URL or calendar contents.
    error_log('Calendar share feed failed: ' . get_class($exception));
    http_response_code(503);
}
