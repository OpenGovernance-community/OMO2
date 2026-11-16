<?php
require_once __DIR__ . '/bootstrap.php';
omoMcpCheckOrigin();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'OPTIONS'], true)) {
    header('Allow: GET, OPTIONS');
    omoMcpJson(['error' => 'Use GET.'], 405);
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
omoMcpJson(str_starts_with(commonGetRequestPath(), '/.well-known/oauth-authorization-server')
    ? omoMcpAuthorizationMetadata() : omoMcpResourceMetadata());
