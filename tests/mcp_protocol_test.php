<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/common/environment_subdomains.php';
require_once dirname(__DIR__) . '/common/mcp/server.php';
function mcpProtocolCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
commonLoadRuntimeEnvIfAvailable();
$_ENV['MCP_PUBLIC_URL'] = 'https://mcp.example.invalid/mcp';
$_ENV['MCP_ALLOW_LOCAL_HTTP'] = '0';
mcpProtocolCheck(omoMcpPublicUrl() === 'https://mcp.example.invalid/mcp', 'Canonical resource URL');
mcpProtocolCheck(omoMcpAuthorizationMetadata()['issuer'] === 'https://mcp.example.invalid', 'OAuth issuer');
$_ENV['MCP_PUBLIC_URL'] = 'https://mcp.example.invalid/mcp/';
mcpProtocolCheck(omoMcpPublicUrl() === 'https://mcp.example.invalid/mcp/', 'Canonical trailing slash is preserved');
mcpProtocolCheck(omoMcpIssuer() === 'https://mcp.example.invalid', 'Issuer excludes endpoint and trailing slash');
mcpProtocolCheck(omoMcpResourceMetadata()['resource'] === 'https://mcp.example.invalid/mcp/', 'Resource matches exact MCP URL');
mcpProtocolCheck(omoMcpResourceMetadataUrl() === 'https://mcp.example.invalid/.well-known/oauth-protected-resource/mcp/', 'Discovery preserves the canonical resource path');
$_ENV['MCP_PUBLIC_URL'] = 'https://mcp.example.invalid/mcp//';
$rejected = false;
try { omoMcpPublicUrl(); } catch (RuntimeException $error) { $rejected = true; }
mcpProtocolCheck($rejected, 'Ambiguous endpoint rejected');
$_ENV['MCP_PUBLIC_URL'] = 'https://mcp.example.invalid/mcp';
foreach (['https://evil.invalid/#fragment', 'https://user:pass@evil.invalid/cb', 'javascript:alert(1)',
    'http://evil.invalid/cb', "https://evil.invalid/\r\nLocation:x", 'http://127.0.0.1.evil.invalid/cb'] as $uri) {
    mcpProtocolCheck(!omoMcpValidRedirect($uri, true), 'Unsafe callback accepted');
}
mcpProtocolCheck(omoMcpValidRedirect('https://chatgpt.com/connector_platform_oauth_redirect'), 'HTTPS callback');
mcpProtocolCheck(!omoMcpValidRedirect('http://localhost:6274/oauth/callback'), 'HTTP callback disabled');
mcpProtocolCheck(omoMcpValidRedirect('http://localhost:6274/oauth/callback', true), 'Explicit local callback');
$request = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize',
    'params' => (object)['protocolVersion' => 'future-version', 'capabilities' => new stdClass(), 'clientInfo' => (object)['name' => 'test', 'version' => '1']]];
$reply = omoMcpDispatch($request, []);
mcpProtocolCheck($reply['result']['protocolVersion'] === OMO_MCP_VERSIONS[0], 'Version negotiation');
$serverInfo = $reply['result']['serverInfo'];
mcpProtocolCheck($serverInfo['name'] === 'OpenMyOrganization' && $serverInfo['title'] === $serverInfo['name']
    && $serverInfo['description'] === 'Toutes les infos sur votre organisation dans OMO'
    && $serverInfo['websiteUrl'] === 'https://omo2.org', 'Server announces its public identity');
mcpProtocolCheck($serverInfo['icons'][0]['src'] === 'https://mcp.example.invalid/mcp/assets/omo-icon-256.jpg'
    && $serverInfo['icons'][0]['mimeType'] === 'image/jpeg' && $serverInfo['icons'][0]['sizes'] === ['256x256'], 'Icon uses the canonical server origin');
mcpProtocolCheck(omoMcpResourceMetadata()['resource_name'] === $serverInfo['title'], 'OAuth and MCP names agree');
mcpProtocolCheck(omoMcpDispatch(['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], []) === null, 'Notification has no response');
mcpProtocolCheck(omoMcpDispatch(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'delete_everything'], [])['error']['code'] === -32601, 'Unknown method');
mcpProtocolCheck(count(omoMcpTools()) === 17, 'Seventeen tools, including availability and event creation');
$byName = array_column(omoMcpTools(), null, 'name');
mcpProtocolCheck($byName['omo_get_availability']['annotations']['readOnlyHint'] && $byName['omo_list_event_spaces']['annotations']['readOnlyHint'], 'Availability and destination discovery are read-only');
mcpProtocolCheck(!$byName['omo_create_event']['annotations']['readOnlyHint'] && $byName['omo_create_event']['securitySchemes'][0]['scopes'] === [OMO_MCP_SCOPE, OMO_MCP_EVENT_SCOPE], 'Event creation has distinct consent');
mcpProtocolCheck(omoMcpNormalizeScope('events:create organization:read mail:send documents:create') === 'organization:read documents:create mail:send events:create', 'New scope preserves existing scopes');
$availabilityArgs = ['user_ids' => [2, 1], 'date_from' => '2026-10-01', 'date_to' => '2026-10-31', 'duration_minutes' => 60];
mcpProtocolCheck(omoMcpToolArguments('omo_get_availability', (object)$availabilityArgs)['user_ids'] === [1, 2], 'Thirty-one inclusive days and member list accepted');
foreach ([['user_ids' => []], ['user_ids' => ['1']], ['user_ids' => range(1, 21)], ['date_from' => '2026-02-30'],
    ['date_to' => '2026-11-01'], ['date_to' => '2026-09-30'], ['duration_minutes' => 45], ['organization_id' => 7]] as $change) {
    $rejected = false;
    try { omoMcpToolArguments('omo_get_availability', (object)array_replace($availabilityArgs, $change)); } catch (InvalidArgumentException $error) { $rejected = true; }
    mcpProtocolCheck($rejected, 'Invalid availability query rejected');
}
$eventArgs = ['title' => 'Meeting', 'holon_id' => 1, 'start_at' => '2026-10-05T09:00:00+02:00', 'end_at' => '2026-10-05T10:00:00+02:00', 'request_key' => 'event-request-1'];
mcpProtocolCheck(omoMcpToolArguments('omo_create_event', (object)$eventArgs) === $eventArgs, 'Event with default invitations accepted');
foreach ([['title' => ' '], ['holon_id' => 0], ['start_at' => '2026-10-05 09:00'], ['start_at' => '2026-10-05T25:00:00+02:00'],
    ['start_at' => '2026-10-05T09:00:00+99:00'], ['end_at' => $eventArgs['start_at']], ['timezone' => 'invalid'],
    ['status' => 'cancelled'], ['invitation_user_ids' => []], ['invitation_user_ids' => ['1']], ['invitation_emails' => ['bad']],
    ['allow_conflicts' => 1], ['request_key' => 'short'], ['IDorganization' => 8], ['videomeetingurl' => 'javascript:alert(1)']] as $change) {
    $rejected = false;
    try { omoMcpToolArguments('omo_create_event', (object)array_replace($eventArgs, $change)); } catch (InvalidArgumentException $error) { $rejected = true; }
    mcpProtocolCheck($rejected, 'Invalid event or invitation arguments rejected');
}
$eventDenied = omoMcpDispatch(['jsonrpc' => '2.0', 'id' => 8, 'method' => 'tools/call',
    'params' => (object)['name' => 'omo_create_event', 'arguments' => (object)$eventArgs]], ['scope' => OMO_MCP_SCOPE]);
mcpProtocolCheck($eventDenied['result']['isError'] && str_contains($eventDenied['result']['_meta']['mcp/www_authenticate'][0], 'events:create'), 'Read grant triggers event consent');
mcpProtocolCheck($byName['omo_get_member']['annotations']['readOnlyHint'] && $byName['omo_get_member']['securitySchemes'][0]['scopes'] === [OMO_MCP_SCOPE], 'Member exploration uses read consent only');
mcpProtocolCheck(omoMcpToolArguments('omo_get_member', (object)['user_id' => 42]) === ['user_id' => 42], 'Member lookup accepts a user ID');
foreach ([(object)[], (object)['user_id' => 0], (object)['user_id' => '42'], (object)['user_id' => 42, 'organization_id' => 9]] as $input) {
    $rejected = false;
    try { omoMcpToolArguments('omo_get_member', $input); } catch (InvalidArgumentException $error) { $rejected = true; }
    mcpProtocolCheck($rejected, 'Invalid member lookup rejected');
}
foreach (['effective_member', 'invited', 'participant'] as $relation) {
    mcpProtocolCheck(omoMcpToolArguments('omo_list_records', (object)['module' => 'calendar', 'user_id' => 42, 'user_relation' => $relation])['user_relation'] === $relation, 'Computed relationship accepted by protocol');
}
mcpProtocolCheck(!$byName['omo_send_object_email']['annotations']['readOnlyHint']
    && $byName['omo_send_object_email']['annotations']['openWorldHint']
    && $byName['omo_send_object_email']['securitySchemes'][0]['scopes'] === [OMO_MCP_SCOPE, OMO_MCP_MAIL_SCOPE], 'Mail has explicit write metadata and consent');
mcpProtocolCheck(omoMcpNormalizeScope('mail:send documents:create organization:read') === 'organization:read documents:create mail:send', 'Mail scope preserves document scope');
$mailArgs = ['object_type' => 'holon', 'object_id' => 1, 'subject' => 'Test', 'message' => 'Body', 'request_key' => 'mail-request-test', 'audience_token' => str_repeat('a', 64)];
mcpProtocolCheck(omoMcpToolArguments('omo_send_object_email', (object)$mailArgs) === $mailArgs, 'Mail arguments accepted');
foreach ([['emails' => ['victim@example.invalid']], ['cc' => 'victim@example.invalid'], ['subject' => "Subject\r\nBcc: victim@example.invalid"], ['object_type' => 'user'], ['audience_token' => ''], ['request_key' => 'short']] as $change) {
    $rejected = false;
    try { omoMcpToolArguments('omo_send_object_email', (object)array_replace($mailArgs, $change)); } catch (InvalidArgumentException $error) { $rejected = true; }
    mcpProtocolCheck($rejected, 'Free recipients and header injection rejected');
}
$mailReply = omoMcpDispatch(['jsonrpc' => '2.0', 'id' => 4, 'method' => 'tools/call', 'params' => (object)['name' => 'omo_send_object_email', 'arguments' => (object)$mailArgs]], ['scope' => OMO_MCP_SCOPE]);
mcpProtocolCheck($mailReply['result']['isError'] && str_contains($mailReply['result']['_meta']['mcp/www_authenticate'][0], 'mail:send'), 'Read grant gets a mail authorization challenge');
mcpProtocolCheck(!$byName['omo_create_document']['annotations']['readOnlyHint']
    && !$byName['omo_create_document']['annotations']['destructiveHint'], 'Creation is declared as a non-destructive write');
mcpProtocolCheck($byName['omo_create_document']['_meta']['openai/fileParams'] === ['file'], 'ChatGPT file input metadata');
mcpProtocolCheck($byName['omo_create_document']['inputSchema']['properties']['file']['required'] === ['download_url', 'file_id'], 'File input follows official schema');
mcpProtocolCheck($byName['omo_create_document']['inputSchema']['properties']['content_format']['enum'] === ['text', 'html', 'markdown', 'md'], 'Memo formats exposed to clients');
mcpProtocolCheck(isset($byName['omo_create_document']['inputSchema']['properties']['external_url']), 'External links exposed to clients');
mcpProtocolCheck(omoMcpNormalizeScope('documents:create organization:read') === 'organization:read documents:create', 'Scope order normalized');
mcpProtocolCheck(omoMcpNormalizeScope('documents:create') === null && omoMcpNormalizeScope('organization:read admin') === null, 'Unsupported scopes rejected');
foreach ([(object)['title' => 'Test', 'request_key' => 'creation-test'],
    (object)['title' => 'Test', 'request_key' => 'creation-test', 'content' => 'Body', 'file' => (object)[]],
    (object)['title' => 'Test', 'request_key' => 'creation-test', 'content' => 'Body', 'organization_id' => 9],
    (object)['title' => 'Test', 'request_key' => 'creation-test', 'file' => (object)['file_id' => 'id']],
    (object)['title' => 'Test', 'request_key' => 'creation-test', 'file' => (object)['file_id' => 'id', 'download_url' => 'file:///etc/passwd']],
    (object)['title' => 'Test', 'request_key' => 'short', 'content' => 'Body'],
    (object)['title' => 'Test', 'request_key' => 'creation-test', 'content' => 'Body', 'visibility_type' => 'everyone']] as $args) {
    $rejected = false;
    try { omoMcpToolArguments('omo_create_document', $args); } catch (InvalidArgumentException $error) { $rejected = true; }
    mcpProtocolCheck($rejected, 'Unsafe/incomplete creation arguments rejected');
}
mcpProtocolCheck(omoMcpToolArguments('omo_create_document', (object)['title' => 'Test', 'request_key' => 'creation-test', 'content' => 'Body', 'holon_id' => 0])['holon_id'] === 0, 'Organization destination accepted');
$memoArgs = ['title' => 'Test', 'request_key' => 'creation-test'];
foreach (['html', 'markdown', 'md'] as $format) {
    mcpProtocolCheck(omoMcpToolArguments('omo_create_document', (object)($memoArgs + ['content' => '# Body', 'content_format' => $format]))['content_format'] === $format, 'Formatted Memo accepted');
}
foreach (['https://example.org/path?q=hello#section', 'http://example.org/'] as $url) {
    mcpProtocolCheck(omoMcpToolArguments('omo_create_document', (object)($memoArgs + ['external_url' => $url]))['external_url'] === $url, 'HTTP/HTTPS link accepted with query and fragment');
}
foreach (['javascript:alert(1)', 'data:text/html,test', 'file:///etc/passwd', '//example.org/', 'https://user:pass@example.org/',
    'https://user@example.org/', "https://example.org/\r\nHeader:value", 'https://example.org/a b', '', str_repeat('a', 8193), null, 7] as $url) {
    $rejected = false;
    try { omoMcpToolArguments('omo_create_document', (object)($memoArgs + ['external_url' => $url])); } catch (InvalidArgumentException $error) { $rejected = true; }
    mcpProtocolCheck($rejected, 'Invalid external link rejected');
}
foreach ([['content' => 'Body', 'external_url' => 'https://example.org/'],
    ['external_url' => 'https://example.org/', 'content_format' => 'html'],
    ['external_url' => 'https://example.org/', 'file' => (object)['file_id' => 'file-id', 'download_url' => 'https://example.org/file.pdf']],
    ['file' => (object)['file_id' => 'file-id', 'download_url' => 'https://example.org/file.pdf'], 'content_format' => 'markdown'],
    ['content' => 'Body', 'content_format' => 'rtf']] as $payload) {
    $rejected = false;
    try { omoMcpToolArguments('omo_create_document', (object)($memoArgs + $payload)); } catch (InvalidArgumentException $error) { $rejected = true; }
    mcpProtocolCheck($rejected, 'Ambiguous document input and misplaced format rejected');
}
foreach ([['omo_list_records', (object)[]],
    ['omo_list_records', (object)['module' => 'projects', 'date_from' => '2026-02-30']],
    ['omo_list_records', (object)['module' => 'projects', 'date_from' => '2026-10-02', 'date_to' => '2026-10-01']],
    ['omo_list_records', (object)['module' => 'projects', 'user_relation' => 'responsible']],
    ['omo_list_records', (object)['module' => 'projects', 'status' => 'x OR 1=1']],
    ['omo_list_records', (object)['module' => 'team', 'user_id' => '1']],
    ['omo_list_assignments', (object)['organization_id' => 1]]] as [$tool, $arguments]) {
    $rejected = false;
    try { omoMcpToolArguments($tool, $arguments); } catch (InvalidArgumentException $error) { $rejected = true; }
    mcpProtocolCheck($rejected, 'Invalid browse arguments accepted');
}
mcpProtocolCheck(omoMcpToolArguments('omo_list_records', (object)['module' => 'projects', 'parent_id' => 0])['parent_id'] === 0, 'Root parent filter');
foreach ([['omo_search', (object)['query' => ' ']], ['omo_search', (object)['query' => 'test', 'modules' => ['unknown']]],
    ['omo_search', (object)['query' => 'test', 'modules' => ['pv', 'pv']]],
    ['omo_search', (object)['query' => 'test', 'offset' => 651]],
    ['omo_read_record', (object)['module' => 'documents', 'record_id' => 1, 'mission_id' => 1]],
    ['omo_read_record', (object)['module' => '../../config', 'record_id' => 1]]] as [$tool, $args]) {
    $rejected = false;
    try { omoMcpToolArguments($tool, $args); } catch (InvalidArgumentException $error) { $rejected = true; }
    mcpProtocolCheck($rejected, 'Invalid read/search arguments accepted');
}
mcpProtocolCheck(omoMcpToolArguments('omo_search', (object)['query' => ' budget '])['query'] === 'budget', 'Search trims keywords');
foreach ([(object)['limit' => 51], (object)['organization_id' => 1], (object)['limit' => '20'], (object)['after_id' => -1], []] as $args) {
    $rejected = false;
    try { omoMcpToolArguments('omo_list_structure', $args); } catch (InvalidArgumentException $error) { $rejected = true; }
    mcpProtocolCheck($rejected, 'Invalid tool argument accepted');
}
$rejected = false;
try { omoMcpToolArguments('omo_get_holon', new stdClass()); } catch (InvalidArgumentException $error) { $rejected = true; }
mcpProtocolCheck($rejected, 'Missing ID rejected');
$_ENV['MCP_PUBLIC_URL'] = 'https://user:secret@mcp.example.invalid/mcp';
$rejected = false;
try { omoMcpPublicUrl(); } catch (RuntimeException $error) { $rejected = true; }
mcpProtocolCheck($rejected, 'Credentials forbidden in resource URL');
echo "mcp_protocol_test: OK\n";
