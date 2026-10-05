<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/api/rest.php';
use dbObject\{McpOauthGrant, Document, Event, ObjectMail, ArrayApplication, OrganizationApplication, ArrayPermission, HolonPermission,
    MeetingProfile, ExternalCalendar, User, UserOrganization};

$host = parse_url(omoMcpPublicUrl(), PHP_URL_HOST);
mcpCheck($host === 'localtest.me', 'REST integration tests only run against local Docker');
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$jar = tempnam(sys_get_temp_dir(), 'omo-rest-test-');
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
$items = mcpFixtures();
try {
    mcpEnableDocumentCreation($items);
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['role']->getId();
    $apps = new ArrayApplication(); $apps->load(['where' => [['field' => 'hash', 'op' => 'IN', 'value' => ['team', 'calendar', 'decision']]]]);
    foreach ($apps as $app) $items['app_' . $app->get('hash')] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $permissions = new ArrayPermission(); $permissions->load(['where' => [['field' => 'permission_key', 'value' => 'CAN_CREATE_EVENT']], 'limit' => 1]);
    $items['event_permission'] = mcpFixture(HolonPermission::class,
        ['IDholon' => $hid, 'IDpermission' => $permissions[0]->getId(), 'range' => 'self', 'member_type' => 'member']);
    $permissions = new ArrayPermission(); $permissions->load(['where' => [['field' => 'permission_key', 'value' => 'CAN_CREATE_DECISION']], 'limit' => 1]);
    $items['decision_permission'] = mcpFixture(HolonPermission::class,
        ['IDholon' => $hid, 'IDpermission' => $permissions[0]->getId(), 'range' => 'self', 'member_type' => 'member']);
    $tokens = [];
    foreach (['read' => OMO_MCP_SCOPE, 'write' => OMO_MCP_SCOPE . ' ' . OMO_MCP_CREATE_SCOPE . ' ' . OMO_MCP_MAIL_SCOPE . ' ' . OMO_MCP_EVENT_SCOPE . ' ' . OMO_MCP_DECISION_SCOPE] as $key => $scope) {
        $authorization = mcpAuthorizationRequest($items['client']); $authorization['scope'] = $scope;
        $code = McpOauthGrant::issueCode($items['client'], $uid, $oid, $authorization);
        $tokens[$key] = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));
    }
    $read = $tokens['read']['access_token']; $write = $tokens['write']['access_token'];
    $root = restHttp('GET', '/api/v1');
    mcpCheck($root['status'] === 200 && $root['data']['oauth_resource'] === omoMcpPublicUrl(), 'REST discovery preserves the shared OAuth resource');
    $spec = restHttp('GET', '/api/v1/openapi.json');
    $operations = []; foreach ($spec['data']['paths'] as $methods) foreach ($methods as $operation) $operations[] = $operation['operationId'];
    $toolNames = array_column(omoMcpTools(), 'name'); sort($operations); sort($toolNames);
    mcpCheck($spec['status'] === 200 && $operations === $toolNames, 'OpenAPI covers every current MCP operation');
    // Keep the distinction between {} and [] in synthetic examples.
    foreach (omoRestOpenApi()['paths'] as $methods) foreach ($methods as $operation) foreach ($operation['responses'] as $status => $response) {
        $media = $response['content']['application/json'];
        $example = json_decode(json_encode($media['example'], JSON_THROW_ON_ERROR), false, 64, JSON_THROW_ON_ERROR);
        mcpCheck(restSchemaMatches($example, $media['schema'], $spec['data']['components']['schemas']), 'Documented response example matches its schema: ' . $operation['operationId'] . ' ' . $status);
    }
    $documentation = restHttp('GET', '/developer/', decodeJson: false);
    mcpCheck($documentation['status'] === 200 && str_starts_with($documentation['headers']['content-type'], 'text/html'), 'Developer reference is public HTML without a bearer');
    mcpCheck(substr_count($documentation['data'], '>Format du retour<') === count($toolNames)
        && str_contains($documentation['data'], 'record.record_id') && str_contains($documentation['data'], 'delivery.sent')
        && !str_contains($documentation['data'], '>Réponses HTTP<'), 'Public reference documents return fields instead of standard HTTP code lists');
    foreach ($toolNames as $name) mcpCheck(str_contains($documentation['data'], 'id="' . $name . '"')
        && str_contains($documentation['data'], 'href="#' . $name . '"'), 'Every OpenAPI operation has documentation and a navigation anchor: ' . $name);
    mcpCheck(str_contains($documentation['data'], htmlspecialchars($spec['data']['components']['securitySchemes']['oauth2']['x-oauth-resource'], ENT_QUOTES, 'UTF-8')), 'Developer reference preserves the canonical OAuth resource');
    mcpCheck(restHttp('GET', '/developer', decodeJson: false)['status'] === 200, 'Developer URL without trailing slash');
    mcpCheck(restHttp('POST', '/developer/')['status'] === 405, 'Documentation never accepts API operations');
    $missing = restHttp('GET', '/api/v1/connection');
    mcpCheck($missing['status'] === 401 && str_contains($missing['headers']['www-authenticate'], 'resource_metadata='), 'Bearer token required');
    restCheckSchema('omo_connection_info', $missing);
    mcpCheck(restHttp('GET', '/api/v1/connection', 'invalid')['status'] === 401, 'Invalid bearer rejected');
    mcpCheck(restHttp('GET', '/api/v1/connection?access_token=' . $read)['status'] === 401, 'Query token never authenticates');
    $login = restHttp('POST', '/common/login_password.php', null, null,
        ['Content-Type: application/x-www-form-urlencoded', 'X-Requested-With: XMLHttpRequest'],
        http_build_query(['email' => $items['user']->get('email'), 'password' => $items['password']]));
    mcpCheck(($login['data']['status'] ?? '') === 'ok', 'Fixture browser session established');
    mcpCheck(restHttp('GET', '/api/v1/connection')['status'] === 401, 'A real logged-in browser cookie cannot authorize REST');
    mcpCheck(restHttp('GET', '/api/v1/connection', $read, null, ['Origin: https://evil.invalid'])['status'] === 403, 'Untrusted origin rejected');
    $preflight = restHttp('OPTIONS', '/api/v1/documents', null, null, ['Origin: ' . omoMcpIssuer()]);
    mcpCheck($preflight['status'] === 204 && $preflight['headers']['access-control-allow-origin'] === omoMcpIssuer(), 'Allowed origin preflight');
    $day = (new DateTimeImmutable('next monday', new DateTimeZone('Europe/Zurich')))->format('Y-m-d');
    foreach ([
        ['/connection', 'omo_connection_info', []], ['/catalog', 'omo_catalog', []],
        ['/structure?limit=1', 'omo_list_structure', ['limit' => 1]],
        ['/structure/' . $hid, 'omo_get_holon', ['holon_id' => $hid]],
        ['/members/' . $uid, 'omo_get_member', ['user_id' => $uid]],
        ['/records/team?user_id=' . $uid, 'omo_list_records', ['module' => 'team', 'user_id' => $uid]],
        ['/assignments?user_id=' . $uid, 'omo_list_assignments', ['user_id' => $uid]],
        ['/search?query=MCP&modules=structure,team', 'omo_search', ['query' => 'MCP', 'modules' => ['structure', 'team']]],
        ['/objects/organization/' . $oid . '/members?user_ids=' . $uid, 'omo_list_object_members', ['object_type' => 'organization', 'object_id' => $oid, 'user_ids' => [$uid]]],
        ['/document-spaces?kind=holons', 'omo_list_document_spaces', ['kind' => 'holons']],
        ['/event-spaces', 'omo_list_event_spaces', []],
        ['/decision-spaces?kind=holons', 'omo_list_decision_spaces', ['kind' => 'holons']],
        ['/availability?user_ids=' . $uid . '&date_from=' . $day . '&date_to=' . $day, 'omo_get_availability', ['user_ids' => [$uid], 'date_from' => $day, 'date_to' => $day]],
    ] as [$path, $name, $args]) {
        $rest = restHttp('GET', '/api/v1' . $path, $read); $mcp = restRpc($read, $name, $args);
        mcpCheck($rest['status'] === 200 && !$mcp['data']['result']['isError']
            && $rest['data'] === $mcp['data']['result']['structuredContent'], 'REST and MCP must agree: ' . $name);
        restCheckSchema($name, $rest);
    }
    mcpCheck(restHttp('GET', '/api/v1/connection', $read)['data']['user']['meeting_booking_url'] === null,
        'No profile returns an explicit null booking link');
    foreach (['owner' => $uid, 'colleague' => null] as $key => $personId) {
        if ($personId === null) {
            $items['booking_colleague'] = mcpFixture(User::class, ['firstname' => 'Booking colleague', 'active' => 1]);
            $personId = (int)$items['booking_colleague']->getId();
            $items['booking_colleague_membership'] = mcpFixture(UserOrganization::class, ['IDuser' => $personId, 'IDorganization' => $oid, 'active' => 1]);
        }
        $items['booking_calendar_' . $key] = mcpFixture(ExternalCalendar::class, ['IDuser' => $personId, 'provider' => 'caldav',
            'title' => 'PRIVATE-BOOKING-CALENDAR', 'calendar_url' => 'https://calendar.example.invalid/' . $personId,
            'username' => 'PRIVATE-BOOKING-LOGIN', 'password_encrypted' => 'PRIVATE-BOOKING-PASSWORD',
            'active' => 1, 'last_sync_at' => new DateTimeImmutable()]);
        $items['booking_profile_' . $key] = mcpFixture(MeetingProfile::class, ['IDuser' => $personId, 'enabled' => 1,
            'slug' => 'rest-booking-' . $personId, 'weekly_hours' => json_encode(MeetingProfile::defaultHours()),
            'IDexternalcalendar' => $items['booking_calendar_' . $key]->getId(), 'timezone' => 'Europe/Zurich']);
        $url = omoMcpIssuer() . '/meeting/rest-booking-' . $personId;
        foreach ([['/members/' . $personId, 'omo_get_member', ['user_id' => $personId]],
            ['/records/team?user_id=' . $personId, 'omo_list_records', ['module' => 'team', 'user_id' => $personId]],
            ['/records/team/' . $personId, 'omo_read_record', ['module' => 'team', 'record_id' => $personId]]] as [$path, $operation, $arguments]) {
            $member = restHttp('GET', '/api/v1' . $path, $read);
            $rpc = restRpc($read, $operation, $arguments);
            mcpCheck($member['status'] === 200 && $member['data'] === $rpc['data']['result']['structuredContent'], 'Booking link REST/MCP parity in ' . $operation);
            $record = $member['data']['member'] ?? $member['data']['record'] ?? $member['data']['items'][0];
            mcpCheck($record['meeting_booking_url'] === $url && !str_contains(json_encode($member['data']), 'PRIVATE-BOOKING'), 'Public link only; calendar credentials and settings stay private');
            restCheckSchema($operation, $member);
        }
        if ($key === 'owner') {
            $connection = restHttp('GET', '/api/v1/connection', $read);
            mcpCheck($connection['data']['user']['meeting_booking_url'] === $url, 'Connection returns the authenticated owner booking URL without extra scope');
            restCheckSchema('omo_connection_info', $connection);
            foreach (['disabled', 'inactive_calendar', 'availability_calendar', 'no_calendar'] as $case) {
                $profile = $items['booking_profile_owner']; $calendar = $items['booking_calendar_owner'];
                $profile->set('enabled', $case !== 'disabled');
                $profile->set('IDexternalcalendar', $case === 'no_calendar' ? null : $calendar->getId()); $profile->save();
                $calendar->set('active', $case !== 'inactive_calendar'); $calendar->set('availability_only', $case === 'availability_calendar'); $calendar->save();
                mcpCheck(restHttp('GET', '/api/v1/members/' . $uid, $read)['data']['member']['meeting_booking_url'] === null,
                    'Unusable booking configuration returns null: ' . $case);
            }
        } else {
            $items['booking_profile_colleague']->set('IDexternalcalendar', $items['booking_calendar_owner']->getId()); $items['booking_profile_colleague']->save();
            mcpCheck(restHttp('GET', '/api/v1/members/' . $personId, $read)['data']['member']['meeting_booking_url'] === null,
                'A different owner destination calendar cannot publish a booking link');
            $items['booking_profile_colleague']->set('IDexternalcalendar', $items['booking_calendar_colleague']->getId()); $items['booking_profile_colleague']->save();
            $items['booking_colleague_membership']->set('active', 0); $items['booking_colleague_membership']->save();
            $inaccessible = restHttp('GET', '/api/v1/members/' . $personId, $read);
            mcpCheck($inaccessible['status'] === 422 && !str_contains(json_encode($inaccessible['data']), 'rest-booking-'), 'Member access gates apply before disclosure, even to an enabled public profile');
        }
        // Keep availability/event tests independent of these synthetic remote calendar records.
        $items['booking_calendar_' . $key]->set('active', 0); $items['booking_calendar_' . $key]->save();
    }
    foreach (['/structure?limit=1&limit=2', '/structure?limit=1.5', '/structure?limit=999999999999999999999',
        '/structure?limit[]=1', '/connection?organization_id=999', '/records/team?module=calendar',
        '/search?query=MCP&modules=structure,unknown', '/objects/organization/' . $oid . '/members?user_ids=1,abc',
        '/structure/0', '/records/unknown'] as $path) {
        mcpCheck(restHttp('GET', '/api/v1' . $path, $read)['status'] === 400, 'Malformed or conflicting query denied: ' . $path);
    }
    mcpCheck(restHttp('GET', '/api/v1/unknown', $read)['status'] === 404, 'Unknown route');
    $method = restHttp('DELETE', '/api/v1/structure/' . $hid, $write);
    mcpCheck($method['status'] === 405 && $method['headers']['allow'] === 'GET, OPTIONS', 'Unsupported method cannot mutate');
    $args = ['title' => 'REST HTML Memo', 'request_key' => 'rest-document-once', 'holon_id' => $hid,
        'content_format' => 'html', 'content' => '<h2>REST saved content</h2><p><strong>Verification</strong></p><script>bad()</script>'];
    $denied = restHttp('POST', '/api/v1/documents', $read, $args);
    mcpCheck($denied['status'] === 403 && $denied['data']['required_scope'] === OMO_MCP_CREATE_SCOPE
        && str_contains($denied['headers']['www-authenticate'], 'insufficient_scope'), 'Document scope enforced');
    restCheckSchema('omo_create_document', $denied);
    mcpCheck(restHttp('POST', '/api/v1/documents', $write, null, [], 'test')['status'] === 415, 'JSON content type required');
    foreach (['{', '[]', 'null'] as $raw) mcpCheck(restHttp('POST', '/api/v1/documents', $write, null,
        ['Content-Type: application/json'], $raw)['status'] === 400, 'Malformed JSON/object rejected');
    mcpCheck(restHttp('POST', '/api/v1/documents?holon_id=' . $hid, $write, $args)['status'] === 400, 'POST cannot mix query and JSON');
    mcpCheck(restHttp('POST', '/api/v1/documents', $write, array_replace($args, ['holon_id' => (int)$items['other_root']->getId()]))['status'] === 422, 'Foreign destination denied');
    $created = restHttp('POST', '/api/v1/documents', $write, $args);
    mcpCheck($created['status'] === 201 && $created['data']['created'], 'REST Memo saved');
    restCheckSchema('omo_create_document', $created);
    $did = $created['data']['record']['record_id']; $items['rest_document'] = new Document();
    mcpCheck($items['rest_document']->load($did, true), 'Document persisted in database');
    mcpCheck(str_contains($items['rest_document']->get('content'), '<strong>Verification</strong>')
        && !str_contains($items['rest_document']->get('content'), '<script'), 'Shared sanitizer used');
    $record = restHttp('GET', parse_url($created['headers']['location'], PHP_URL_PATH), $write);
    mcpCheck($record['status'] === 200 && str_contains($record['data']['text'], 'REST saved content'), 'Location supports persisted document re-read');
    restCheckSchema('omo_read_record', $record);
    $retry = restRpc($write, 'omo_create_document', $args);
    mcpCheck($retry['data']['result']['structuredContent']['replayed'] && $retry['data']['result']['structuredContent']['record']['record_id'] === $did, 'REST creation is deduplicated through MCP');
    mcpCheck(restHttp('POST', '/api/v1/documents', $write, $args)['status'] === 200, 'REST replay returns 200');
    mcpCheck(restHttp('POST', '/api/v1/documents', $write, array_replace($args, ['title' => 'Changed']))['status'] === 422, 'Same key with a changed payload rejected');
    $eventArgs = ['title' => 'REST event', 'request_key' => 'rest-event-once', 'holon_id' => $hid,
        'start_at' => $day . 'T10:00:00+02:00', 'end_at' => $day . 'T11:00:00+02:00', 'allow_conflicts' => true];
    mcpCheck(restHttp('POST', '/api/v1/events', $read, $eventArgs)['status'] === 403, 'Event scope enforced');
    $createdEvent = restHttp('POST', '/api/v1/events', $write, $eventArgs);
    mcpCheck($createdEvent['status'] === 201 && $createdEvent['data']['created'], 'REST event saved');
    restCheckSchema('omo_create_event', $createdEvent);
    restCheckSchema('omo_read_record', restHttp('GET', parse_url($createdEvent['headers']['location'], PHP_URL_PATH), $write));
    $items['rest_event'] = new Event(); $items['rest_event']->load($createdEvent['data']['event_id']);
    mcpCheck(restRpc($write, 'omo_create_event', $eventArgs)['data']['result']['structuredContent']['replayed'], 'Event deduplication shared with MCP');
    $conflict = restHttp('POST', '/api/v1/events', $write, array_replace($eventArgs, ['request_key' => 'rest-event-conflict', 'allow_conflicts' => false]));
    mcpCheck($conflict['status'] === 409 && !$conflict['data']['created'] && $conflict['data']['requires_confirmation'], 'Conflicts never report creation');
    restCheckSchema('omo_create_event', $conflict);
    $decisionArgs = ['title' => 'REST poll', 'question' => 'Choose a proposal', 'method' => 'simple_vote',
        'holon_id' => $hid, 'request_key' => 'rest-decision-once', 'proposals' => [['title' => 'A'], ['title' => 'B']]];
    $deniedDecision = restHttp('POST', '/api/v1/decisions', $read, $decisionArgs);
    mcpCheck($deniedDecision['status'] === 403 && $deniedDecision['data']['required_scope'] === OMO_MCP_DECISION_SCOPE, 'Decision OAuth scope enforced');
    $createdDecision = restHttp('POST', '/api/v1/decisions', $write, $decisionArgs);
    mcpCheck($createdDecision['status'] === 201 && $createdDecision['data']['created'], 'REST decision saved');
    $items['rest_decision'] = new class extends \dbObject\DecisionProcess {
        public function delete() { return !empty($this->deleteWithRelations()['status']); }
    };
    $items['rest_decision']->load($createdDecision['data']['decision_id']);
    restCheckSchema('omo_create_decision', $createdDecision);
    restCheckSchema('omo_read_record', restHttp('GET', parse_url($createdDecision['headers']['location'], PHP_URL_PATH), $write));
    $decisionReplay = restRpc($write, 'omo_create_decision', $decisionArgs);
    mcpCheck($decisionReplay['data']['result']['structuredContent']['replayed'], 'Decision retries shared with MCP');
    // Never deliver email: scope denial and an inaccessible object both fail before queue/SMTP.
    $mailArgs = ['object_type' => 'holon', 'object_id' => PHP_INT_MAX, 'subject' => 'Test', 'message' => 'Test',
        'audience_token' => str_repeat('0', 64), 'request_key' => 'rest-mail-denied'];
    mcpCheck(restHttp('POST', '/api/v1/emails', $read, $mailArgs)['status'] === 403, 'Mail scope enforced');
    mcpCheck(restHttp('POST', '/api/v1/emails', $write, $mailArgs)['status'] === 422, 'Mail object permission enforced');
    mcpCheck(restHttp('GET', '/api/v1/emails/' . PHP_INT_MAX, $write)['status'] === 422, 'Inaccessible mail status rejected');
    // Seed an already completed zero-recipient test message to validate status/replay without any SMTP dispatch.
    $mailArgs = array_replace($mailArgs, ['object_id' => $hid, 'request_key' => 'rest-mail-schema-replay']);
    $items['rest_mail'] = mcpFixture(ObjectMail::class, ['IDuser' => $uid, 'IDorganization' => $oid,
        'object_type' => 'holon', 'object_id' => $hid, 'recipient_count' => 0, 'subject' => 'Test', 'message' => 'Test', 'created_at' => time(),
        'request_hash' => hash('sha256', $mailArgs['request_key']),
        'payload_hash' => hash('sha256', json_encode(['holon', $hid, 'Test', 'Test', $mailArgs['audience_token']], JSON_THROW_ON_ERROR))]);
    $mailStatus = restHttp('GET', '/api/v1/emails/' . $items['rest_mail']->getId(), $write);
    mcpCheck($mailStatus['status'] === 200 && $mailStatus['data']['complete'], 'Seeded mail is already complete');
    restCheckSchema('omo_object_email_status', $mailStatus);
    $mailReplay = restHttp('POST', '/api/v1/emails', $write, $mailArgs);
    mcpCheck($mailReplay['status'] === 200 && $mailReplay['data']['replayed'], 'Mail retry retrieves the seeded message without sending');
    restCheckSchema('omo_send_object_email', $mailReplay);
    $items['membership']->set('active', false); $items['membership']->save();
    mcpCheck(restHttp('GET', '/api/v1/connection', $read)['status'] === 401, 'Membership revocation enforced immediately');
    $items['membership']->set('active', true); $items['membership']->save();
    $grant = McpOauthGrant::authenticate($write, omoMcpPublicUrl()); McpOauthGrant::revokeOwned((int)$grant['id'], $uid);
    mcpCheck(restHttp('GET', '/api/v1/connection', $write)['status'] === 401, 'OAuth revocation shared with REST');
    echo "rest_http_test: OK (public return formats, response schemas/examples, all 19 routes, MCP parity, scopes, isolation, persisted documents/events/decisions, shared retries, revocation)\n";
} finally {
    mcpCleanup($items);
    if (is_file($jar)) unlink($jar);
}
