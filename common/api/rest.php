<?php
require_once __DIR__ . '/operations.php';
require_once __DIR__ . '/responses.php';

/** The transport mapping is the only REST-specific operation inventory. */
function omoRestRoutes(): array
{
    return [
        ['GET', '/connection', 'omo_connection_info'],
        ['GET', '/catalog', 'omo_catalog'],
        ['GET', '/structure', 'omo_list_structure'],
        ['GET', '/structure/{holon_id}', 'omo_get_holon'],
        ['GET', '/members/{user_id}', 'omo_get_member'],
        ['GET', '/records/{module}', 'omo_list_records'],
        ['GET', '/records/{module}/{record_id}', 'omo_read_record'],
        ['GET', '/assignments', 'omo_list_assignments'],
        ['GET', '/search', 'omo_search'],
        ['GET', '/objects/{object_type}/{object_id}/members', 'omo_list_object_members'],
        ['GET', '/document-spaces', 'omo_list_document_spaces'],
        ['POST', '/documents', 'omo_create_document'],
        ['POST', '/emails', 'omo_send_object_email'],
        ['GET', '/emails/{mail_id}', 'omo_object_email_status'],
        ['GET', '/availability', 'omo_get_availability'],
        ['GET', '/event-spaces', 'omo_list_event_spaces'],
        ['POST', '/events', 'omo_create_event'],
    ];
}

function omoRestMatchRoute(string $path): ?array
{
    foreach (omoRestRoutes() as [$method, $template, $operation]) {
        $pattern = preg_replace('/\\\{([a-z_]+)\\\}/', '(?P<$1>[^/]+)', preg_quote($template, '#'));
        if (preg_match('#^' . $pattern . '/?$#D', $path, $matches)) {
            return ['method' => $method, 'operation' => $operation,
                'parameters' => array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY)];
        }
    }
    return null;
}

function omoRestQueryValue(string $value, array $schema): mixed
{
    if (!mb_check_encoding($value, 'UTF-8')) throw new InvalidArgumentException('Query parameters must use UTF-8.');
    if ($schema['type'] === 'integer') {
        if (!preg_match('/^(?:0|[1-9][0-9]*)$/D', $value) || filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('Expected a decimal integer.');
        }
        return (int)$value;
    }
    if ($schema['type'] === 'array') {
        return $value === '' ? [] : array_map(static fn ($item) => omoRestQueryValue($item, $schema['items']), explode(',', $value));
    }
    return $value;
}

/** Parse the raw query so PHP cannot silently drop duplicate, dotted or excessive parameters. */
function omoRestArguments(array $route, string $query, ?stdClass $body = null): array
{
    $name = $route['operation'];
    $schema = array_column(omoMcpTools(), 'inputSchema', 'name')[$name];
    $properties = (array)$schema['properties'];
    $args = [];
    if ($route['method'] === 'POST') {
        if ($query !== '') throw new InvalidArgumentException('POST arguments belong exclusively in the JSON body.');
        if ($body === null) throw new InvalidArgumentException('Expected a JSON object.');
        $args = get_object_vars($body);
    } else {
        if (strlen($query) > 32768) throw new InvalidArgumentException('Query too large.');
        foreach ($query === '' ? [] : explode('&', $query) as $pair) {
            if ($pair === '') continue;
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $key = urldecode($key); $value = urldecode($value);
            if (!isset($properties[$key]) || array_key_exists($key, $args) || array_key_exists($key, $route['parameters'])) {
                throw new InvalidArgumentException('Unknown, duplicate or conflicting query parameter: ' . $key);
            }
            $args[$key] = omoRestQueryValue($value, $properties[$key]);
        }
    }
    foreach ($route['parameters'] as $key => $value) $args[$key] = omoRestQueryValue(rawurldecode($value), $properties[$key]);
    return omoMcpToolArguments($name, (object)$args);
}

/** Generate schemas and permissions from the same registry exposed by MCP. */
function omoRestOpenApi(): array
{
    $tools = array_column(omoMcpTools(), null, 'name');
    $responseSchemas = omoApiResponseSchemas();
    $paths = [];
    foreach (omoRestRoutes() as [$method, $path, $name]) {
        $tool = $tools[$name]; $schema = $tool['inputSchema'];
        $responses = [];
        foreach ([200 => 'Success, including an idempotent replay', 400 => 'Invalid request',
            401 => 'Missing, invalid, expired or revoked bearer token', 403 => 'Missing scope or disallowed browser origin',
            405 => 'Unsupported HTTP method', 422 => 'Operation rejected by current permissions or business rules',
            503 => 'Server unavailable'] as $code => $description) {
            $responses[$code] = ['description' => $description];
        }
        if (in_array($name, ['omo_create_document', 'omo_create_event'], true)) $responses[201] = ['description' => 'New object saved; Location identifies its REST record'];
        if ($name === 'omo_create_event') $responses[409] = ['description' => 'Conflicts require confirmation; created=false, nothing saved'];
        if ($name === 'omo_send_object_email') $responses[202] = ['description' => 'Message accepted with pending deliveries; follow Location for status'];
        if ($method === 'POST') $responses[415] = ['description' => 'Use application/json'];
        foreach ($responses as $code => &$response) {
            $responseName = omoApiResponseName($name, $code);
            $response['content'] = ['application/json' => [
                'schema' => ['$ref' => '#/components/schemas/' . $responseName],
                'example' => omoApiResponseExample($responseSchemas[$responseName], $responseSchemas),
            ]];
            $example = $response['content']['application/json']['example'];
            if ($name === 'omo_create_document' && $code < 400) $example->record->module = 'documents';
            if ($code === 200 && in_array($name, ['omo_create_document', 'omo_create_event'], true)) $example->replayed = true;
            if ($code < 400 && in_array($name, ['omo_send_object_email', 'omo_object_email_status'], true)) {
                $example->complete = $code === 200;
                foreach ($example->delivery as $deliveryStatus => $_) $example->delivery->$deliveryStatus = 0;
                $example->delivery->{$code === 200 ? 'sent' : 'queued'} = 1;
            }
            if ($responseName === 'Error') $example->error = match ($code) {
                400 => 'invalid_request', 401 => 'unauthorized', 403 => 'insufficient_scope', 405 => 'method_not_allowed',
                415 => 'unsupported_media_type', 422 => 'operation_rejected', default => 'server_error',
            };
            if ($code < 400 && in_array($name, ['omo_create_document', 'omo_create_event', 'omo_send_object_email'], true)) {
                $response['headers']['Location'] = ['description' => 'REST URL of the saved record or email status.', 'schema' => ['type' => 'string', 'format' => 'uri']];
            }
            if (in_array($code, [401, 403], true)) $response['headers']['WWW-Authenticate'] = [
                'description' => 'Bearer challenge for authentication or missing scope; omitted for origin rejection.', 'schema' => ['type' => 'string']];
            if ($code === 405) $response['headers']['Allow'] = ['description' => 'Supported HTTP methods.', 'schema' => ['type' => 'string']];
        }
        unset($response);
        $entry = ['operationId' => $name, 'summary' => $tool['title'], 'description' => $tool['description'],
            'security' => [['oauth2' => $tool['securitySchemes'][0]['scopes']]], 'responses' => $responses];
        if ($method === 'POST') {
            $entry['requestBody'] = ['required' => true, 'content' => ['application/json' => ['schema' => $schema]]];
        } else {
            $entry['parameters'] = [];
            foreach ((array)$schema['properties'] as $key => $rule) {
                $inPath = str_contains($path, '{' . $key . '}');
                $parameter = ['name' => $key, 'in' => $inPath ? 'path' : 'query',
                    'required' => $inPath || in_array($key, $schema['required'] ?? [], true), 'schema' => $rule];
                if ($rule['type'] === 'array') $parameter += ['style' => 'form', 'explode' => false];
                $entry['parameters'][] = $parameter;
            }
        }
        $paths[$path][strtolower($method)] = $entry;
    }
    return ['openapi' => '3.1.0', 'info' => ['title' => 'OpenMyOrganization REST API', 'version' => '1.0.0',
        'description' => 'REST transport of the OMO integration API. Same OAuth resource, scopes, organization and permissions as MCP. Send resource=' . omoMcpPublicUrl() . ' during OAuth authorization and token exchange.'],
        'servers' => [['url' => omoMcpIssuer() . '/api/v1']], 'paths' => $paths,
        'externalDocs' => ['url' => omoMcpIssuer() . '/developer/'],
        'components' => ['schemas' => $responseSchemas, 'securitySchemes' => ['oauth2' => ['type' => 'oauth2',
            'description' => 'Authorization code + PKCE S256, public client. Register through /mcp/register.php. Access tokens expire after one hour; refresh and revocation use the existing OAuth endpoints.',
            'x-oauth-resource' => omoMcpPublicUrl(),
            'x-registration-url' => omoMcpIssuer() . '/mcp/register.php',
            'x-revocation-url' => omoMcpIssuer() . '/mcp/revoke.php',
            'flows' => ['authorizationCode' => [
                'authorizationUrl' => omoMcpIssuer() . '/mcp/authorize.php', 'tokenUrl' => omoMcpIssuer() . '/mcp/token.php',
                'refreshUrl' => omoMcpIssuer() . '/mcp/token.php', 'scopes' => [
                    OMO_MCP_SCOPE => 'Read accessible organization data', OMO_MCP_CREATE_SCOPE => 'Create documents',
                    OMO_MCP_MAIL_SCOPE => 'Send email to existing object audiences', OMO_MCP_EVENT_SCOPE => 'Create events']]]]]]];
}
