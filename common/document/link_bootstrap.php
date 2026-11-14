<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__, 2) . '/omo/translations.php';
require_once __DIR__ . '/share.php';
header('Cache-Control: private, no-store');
header('Referrer-Policy: no-referrer');

$documentLinkAccess = commonResolveDocumentLinkAccess();
if (!$documentLinkAccess) {
    http_response_code(404);
    exit;
}
