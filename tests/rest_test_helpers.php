<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/api/rest.php';

function restHttp(string $method, string $path, ?string $token = null, ?array $body = null, array $headers = [], ?string $rawBody = null, bool $decodeJson = true): array
{
    global $jar;
    $curl = curl_init(omoMcpIssuer() . $path); $responseHeaders = [];
    if ($token !== null) $headers[] = 'Authorization: Bearer ' . $token;
    if ($body !== null) { $headers[] = 'Content-Type: application/json'; $rawBody = json_encode((object)$body, JSON_THROW_ON_ERROR); }
    curl_setopt_array($curl, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_RESOLVE => ['localtest.me:443:127.0.0.1'],
        CURLOPT_HTTPHEADER => $headers, CURLOPT_COOKIEFILE => $jar, CURLOPT_COOKIEJAR => $jar,
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$responseHeaders): int {
            $parts = explode(':', trim($line), 2);
            if (count($parts) === 2) $responseHeaders[strtolower($parts[0])] = trim($parts[1]);
            return strlen($line);
        }]);
    if ($rawBody !== null) curl_setopt($curl, CURLOPT_POSTFIELDS, $rawBody);
    $raw = curl_exec($curl);
    if ($raw === false) throw new RuntimeException('Local REST test HTTP failure: ' . curl_error($curl));
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE); unset($curl);
    return ['status' => $status, 'headers' => $responseHeaders, 'data' => $raw === '' ? null : ($decodeJson ? json_decode($raw, true, 64, JSON_THROW_ON_ERROR) : $raw)];
}
function restRpc(string $token, string $name, array $args): array
{
    return restHttp('POST', parse_url(omoMcpPublicUrl(), PHP_URL_PATH), $token,
        ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => $name, 'arguments' => (object)$args]]);
}
/** Check the JSON Schema keywords used by our response contracts against real decoded JSON. */
function restSchemaMatches(mixed $value, array $schema, array $schemas): bool
{
    if (isset($schema['$ref'])) return restSchemaMatches($value, $schemas[basename($schema['$ref'])], $schemas);
    foreach (['anyOf', 'oneOf'] as $keyword) if (isset($schema[$keyword])) {
        $matches = count(array_filter($schema[$keyword], static fn ($rule) => restSchemaMatches($value, $rule, $schemas)));
        if ($keyword === 'oneOf' ? $matches !== 1 : $matches === 0) return false;
    }
    if (array_key_exists('const', $schema) && $value !== $schema['const']) return false;
    if (isset($schema['enum']) && !in_array($value, $schema['enum'], true)) return false;
    if (isset($schema['type'])) {
        $type = match (true) { $value === null => 'null', is_int($value) => 'integer', is_float($value) => 'number',
            is_bool($value) => 'boolean', is_string($value) => 'string', is_array($value) => 'array', default => 'object' };
        if (!in_array($type, (array)$schema['type'], true) && !($type === 'integer' && in_array('number', (array)$schema['type'], true))) return false;
    }
    if (is_string($value) && isset($schema['pattern']) && !preg_match('~' . $schema['pattern'] . '~D', $value)) return false;
    if (is_array($value)) {
        if (isset($schema['maxItems']) && count($value) > $schema['maxItems']) return false;
        foreach ($value as $item) if (isset($schema['items']) && !restSchemaMatches($item, $schema['items'], $schemas)) return false;
    }
    if ($value instanceof stdClass) {
        foreach ($schema['required'] ?? [] as $key) if (!property_exists($value, $key)) return false;
        foreach (get_object_vars($value) as $key => $item) {
            $rule = $schema['properties'][$key] ?? $schema['additionalProperties'] ?? true;
            if ($rule === false || (is_array($rule) && !restSchemaMatches($item, $rule, $schemas))) return false;
        }
    }
    return true;
}
function restCheckSchema(string $operation, array $response): void
{
    $value = json_decode(json_encode($response['data'], JSON_THROW_ON_ERROR), false, 64, JSON_THROW_ON_ERROR);
    $schemas = omoApiResponseSchemas(); $name = omoApiResponseName($operation, $response['status']);
    mcpCheck(restSchemaMatches($value, $schemas[$name], $schemas), 'Actual response must match ' . $name . ' for ' . $operation);
}
