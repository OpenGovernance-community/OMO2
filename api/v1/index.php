<?php
// Capture transport input before the legacy bootstrap sees any share/meeting context.
$restQuery = (string)($_SERVER['QUERY_STRING'] ?? '');
$restPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$_GET = $_POST = $_REQUEST = [];
require_once dirname(__DIR__, 2) . '/common/api/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/api/rest.php';
omoMcpCheckOrigin();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') { http_response_code(204); exit; }
$path = str_starts_with($restPath, '/api/v1') ? substr($restPath, 7) : '';
if (in_array($path, ['', '/', '/openapi.json'], true)) {
    if ($method !== 'GET') {
        header('Allow: GET, OPTIONS');
        omoMcpJson(['error' => 'method_not_allowed', 'error_description' => 'Use GET.'], 405);
    }
    if ($path === '/openapi.json') omoMcpJson(omoRestOpenApi());
    omoMcpJson(['name' => 'OpenMyOrganization REST API', 'version' => '1',
        'openapi_url' => omoMcpIssuer() . '/api/v1/openapi.json',
        'documentation_url' => omoMcpIssuer() . '/developer/',
        'oauth_resource' => omoMcpPublicUrl(), 'oauth_metadata_url' => omoMcpIssuer() . '/.well-known/oauth-authorization-server']);
}
$grant = omoApiAuthenticate();
$route = omoRestMatchRoute($path);
if ($route === null) omoMcpJson(['error' => 'not_found', 'error_description' => 'Unknown REST route.'], 404);
if ($method !== $route['method']) {
    header('Allow: ' . $route['method'] . ', OPTIONS');
    omoMcpJson(['error' => 'method_not_allowed', 'error_description' => 'Use ' . $route['method'] . '.'], 405);
}
try {
    $body = null;
    if ($method === 'POST') {
        if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
            omoMcpJson(['error' => 'unsupported_media_type', 'error_description' => 'Use application/json.'], 415);
        }
        $body = (object)omoMcpInput();
    } elseif ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 || isset($_SERVER['HTTP_TRANSFER_ENCODING'])) {
        throw new InvalidArgumentException('GET arguments belong in the URL, without a request body.');
    }
    $args = omoRestArguments($route, $restQuery, $body);
    $data = omoApiExecuteOperation($route['operation'], $args, $grant);
    $status = 200;
    if ($route['operation'] === 'omo_create_event' && !empty($data['requires_confirmation'])) $status = 409;
    if (in_array($route['operation'], ['omo_create_document', 'omo_create_event', 'omo_create_decision'], true) && !empty($data['created'])) {
        $status = empty($data['replayed']) ? 201 : 200;
        $module = match ($route['operation']) { 'omo_create_document' => 'documents', 'omo_create_event' => 'calendar', default => 'decision' };
        $recordId = $data['record']['record_id'] ?? $data['event_id'] ?? $data['decision_id'];
        header('Location: ' . omoMcpIssuer() . '/api/v1/records/' . $module . '/' . $recordId);
    }
    if ($route['operation'] === 'omo_send_object_email') {
        $status = empty($data['complete']) ? 202 : 200;
        header('Location: ' . omoMcpIssuer() . '/api/v1/emails/' . $data['mail_id']);
    }
    error_log(sprintf('OMO REST operation=%s grant=%d user=%d organization=%d status=%d', $route['operation'],
        $grant['id'], $grant['IDuser'], $grant['IDorganization'], $status));
    omoMcpJson($data, $status);
} catch (JsonException $error) {
    omoMcpJson(['error' => 'invalid_request', 'error_description' => 'Invalid JSON.'], 400);
} catch (InvalidArgumentException $error) {
    omoMcpJson(['error' => 'invalid_request', 'error_description' => $error->getMessage()], 400);
} catch (OmoApiScopeException $error) {
    header('WWW-Authenticate: ' . omoApiScopeChallenge($grant, $error->requiredScope));
    omoMcpJson(['error' => 'insufficient_scope', 'error_description' => $error->getMessage(), 'required_scope' => $error->requiredScope], 403);
} catch (DomainException $error) {
    omoMcpJson(['error' => 'operation_rejected', 'error_description' => $error->getMessage()], 422);
}
