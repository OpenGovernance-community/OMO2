<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/api/rest.php';
use dbObject\{McpDecisionCreation, McpOauthGrant, ArrayApplication, OrganizationApplication, User, UserOrganization,
    UserHolon, ArrayPermission, HolonPermission, DecisionProcess, Event, ObjectAudience, ArrayDecisionProcess};

function decisionApiHttp(string $token, string $path, ?array $body = null): array
{
    mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'HTTP fixtures only run on local Docker');
    $curl = curl_init(omoMcpIssuer() . $path); $location = null;
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_RESOLVE => ['localtest.me:443:127.0.0.1'],
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Bearer ' . $token],
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$location): int {
            if (str_starts_with(strtolower($line), 'location:')) $location = trim(substr($line, 9));
            return strlen($line);
        }]);
    if ($body !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode((object)$body, JSON_THROW_ON_ERROR)]);
    $raw = curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE); unset($curl);
    mcpCheck(is_string($raw), 'Decision HTTP response received');
    return ['status' => $status, 'data' => json_decode($raw, true, 64, JSON_THROW_ON_ERROR), 'location' => $location];
}
function decisionApiRejected(callable $action, string $message): void
{
    $rejected = false; try { $action(); } catch (DomainException | InvalidArgumentException $error) { $rejected = true; }
    mcpCheck($rejected, $message);
}
$before = $_SESSION; $items = mcpFixtures(); $decisions = []; $events = [];
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['role']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $apps = new ArrayApplication(); $apps->load(['where' => [['field' => 'hash', 'op' => 'IN', 'value' => ['decision', 'calendar', 'team']]]]);
    foreach ($apps as $app) $items['app_' . $app->get('hash')] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $items['member'] = mcpFixture(User::class, ['firstname' => 'Poll member', 'email' => 'poll-member@example.invalid', 'active' => 1]);
    $mid = (int)$items['member']->getId();
    $items['member_org'] = mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $mid, 'active' => 1]);
    foreach ([$uid, $mid] as $person) $items['assignment_' . $person] = mcpFixture(UserHolon::class,
        ['IDholon' => $hid, 'IDuser' => $person, 'active' => 1, 'is_membership' => 1]);
    $permissions = new ArrayPermission(); $permissions->load(['where' => [['field' => 'permission_key', 'value' => 'CAN_CREATE_DECISION']], 'limit' => 1]);
    mcpCheck(count($permissions) === 1, 'Native decision permission exists');
    $items['permission'] = mcpFixture(HolonPermission::class, ['IDholon' => $hid, 'IDpermission' => $permissions[0]->getId(), 'range' => 'self', 'member_type' => 'member']);
    $request = mcpAuthorizationRequest($items['client']); $request['scope'] .= ' ' . OMO_MCP_DECISION_SCOPE;
    $code = McpOauthGrant::issueCode($items['client'], $uid, $oid, $request);
    $tokens = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));
    $grant = McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl());
    $read = ['IDuser' => $uid, 'IDorganization' => $oid, 'scope' => OMO_MCP_SCOPE];
    $spaces = McpDecisionCreation::spaces($read, ['kind' => 'holons', 'limit' => 1]);
    mcpCheck(count($spaces['items']) === 1 && $spaces['items'][0]['holon_id'] === $hid && !$spaces['write_authorized'], 'Discovery uses native creation permission');
    mcpCheck(McpDecisionCreation::spaces($grant, ['kind' => 'holons', 'after_id' => $spaces['next_after_id']])['complete'], 'Discovery pagination completes');
    mcpCheck(McpDecisionCreation::spaces($grant, [])['items'] === [], 'Organization creation requires root permission');
    $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Zurich')); $day = $now->modify('+7 days')->setTime(10, 0);
    $base = ['holon_id' => $hid, 'title' => 'Plan our meeting', 'question' => 'Which slot works for you?', 'method' => 'majority_judgment',
        'consultation_start_at' => $now->modify('-1 hour')->format(DateTimeInterface::ATOM),
        'consultation_end_at' => $now->modify('+1 hour')->format(DateTimeInterface::ATOM),
        'evaluation_start_at' => $now->modify('+1 hour')->format(DateTimeInterface::ATOM),
        'evaluation_end_at' => $now->modify('+1 day')->format(DateTimeInterface::ATOM), 'request_key' => 'api-poll-native-dates',
        'proposals' => array_map(static fn($i) => ['start_at' => $day->modify('+' . $i . ' days')->format(DateTimeInterface::ATOM),
            'end_at' => $day->modify('+' . $i . ' days')->modify('+1 hour')->format(DateTimeInterface::ATOM)], [0, 1, 2])];
    decisionApiRejected(fn() => McpDecisionCreation::create($read, $base), 'Read-only consent cannot create ballots');
    foreach (['other_root', 'hidden', 'inactive', 'root'] as $fixture) decisionApiRejected(fn() => McpDecisionCreation::create($grant,
        array_replace($base, ['holon_id' => (int)$items[$fixture]->getId()])), 'Unauthorized destination rejected');
    foreach (['majority_judgment', 'simple_vote', 'consent'] as $method) {
        $args = array_replace($base, ['method' => $method, 'request_key' => 'api-poll-' . $method]);
        $http = decisionApiHttp($tokens['access_token'], '/api/v1/decisions', $args);
        mcpCheck($http['status'] === 201, 'REST creates native ' . $method . ': ' . json_encode($http['data']));
        $created = $http['data']; $decision = new DecisionProcess(); $decision->load($created['decision_id'], true); $decisions[] = $decision;
        foreach ($created['proposals'] as $proposal) { $event = new Event(); $event->load($proposal['event_id'], true); $events[] = $event; }
        mcpCheck($created['created'] && !$created['replayed'] && $created['question'] === $base['question']
            && $created['status'] === 'consultation' && $created['participant_count'] === 2 && !$created['emails_sent'], 'Question, phases and participants saved');
        mcpCheck(count($created['proposals']) === 3 && !array_filter($created['proposals'], static fn($p) => !$p['event_id'] || $p['calendar_status'] !== 'option'), 'Three date-only proposals reserve tentative events');
        foreach (array_slice($events, -3) as $event) mcpCheck($event->getEffectiveInvitationTargets($oid)['userIds'] === [$uid, $mid], 'Reservations have native participant invitations');
        mcpCheck(str_contains($created['public_url'], '/participate') && str_contains($http['location'], '/records/decision/'), 'Generic public and internal links returned');
        $rpc = decisionApiHttp($tokens['access_token'], parse_url(omoMcpPublicUrl(), PHP_URL_PATH),
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'omo_create_decision', 'arguments' => (object)$args]]);
        mcpCheck(empty($rpc['data']['result']['isError']) && $rpc['data']['result']['structuredContent']['replayed']
            && $rpc['data']['result']['structuredContent']['decision_id'] === $created['decision_id'], 'REST creation replayed through MCP without duplication');
        $replayed = decisionApiHttp($tokens['access_token'], '/api/v1/decisions', array_reverse($args, true));
        mcpCheck($replayed['status'] === 200 && $replayed['data']['decision_id'] === $created['decision_id'], 'Equivalent JSON key order replays safely');
        decisionApiRejected(fn() => McpDecisionCreation::create($grant, array_replace($args, ['title' => 'Changed'])), 'Changed payload cannot reuse key');
        $audience = ObjectAudience::page($oid, ['object_type' => 'decision', 'object_id' => $created['decision_id']]);
        mcpCheck($audience['can_send'] && $audience['recipient_count'] === 2 && strlen($audience['audience_token']) === 64, 'Existing mail preview accepts newly created decision audience');
    }
    $text = ['holon_id' => $hid, 'title' => 'Text poll', 'question' => 'Choose', 'method' => 'simple_vote',
        'invitation_user_ids' => [$mid], 'request_key' => 'api-text-only-poll', 'proposals' => [['title' => 'A'], ['description' => '<script>plain text</script>']]];
    $created = McpDecisionCreation::create($grant, $text); $decision = new DecisionProcess(); $decision->load($created['decision_id']); $decisions[] = $decision;
    mcpCheck($created['status'] === 'draft' && $created['participant_count'] === 2 && $created['proposals'][0]['event_id'] === null
        && !str_contains($created['proposals'][1]['description'], '<script>'), 'Mixed text proposals, explicit invitee plus owner, no calendar events or executable HTML');
    decisionApiRejected(fn() => McpDecisionCreation::create($grant, array_replace($text, ['request_key' => 'bad-invite-poll', 'invitation_user_ids' => [PHP_INT_MAX]])), 'Invalid invitee rolls back creation');
    $all = new ArrayDecisionProcess(); $all->load(['where' => [['field' => 'IDorganization', 'value' => $oid]]]);
    mcpCheck(count($all) === 4, 'Failed request leaves no orphan decision');
    foreach ([['proposals' => [[], []]], ['evaluation_start_at' => $now->format(DateTimeInterface::ATOM)],
        ['invitation_user_ids' => []], ['unknown' => 1], ['proposals' => [['start_at' => '2026-02-30T10:00:00Z'], ['title' => 'B']]]] as $bad) {
        decisionApiRejected(fn() => omoApiDecisionValidate('omo_create_decision', array_replace($text, $bad)), 'Malformed decision fields rejected');
    }
    $items['assignment_' . $uid]->set('active', 0); $items['assignment_' . $uid]->save();
    decisionApiRejected(fn() => McpDecisionCreation::create($grant, $base), 'Current permission removal prevents writes');
    mcpCheck(McpDecisionCreation::spaces($grant, ['kind' => 'holons'])['items'] === [], 'Discovery updates after permission removal');
    McpOauthGrant::revokeOwned((int)$grant['id'], $uid);
    decisionApiRejected(fn() => McpDecisionCreation::create($grant, $base), 'Revoked grant cannot create or replay');
    echo "api_decision_test: OK (three methods, date/text proposals, lifecycle, agenda reservations, participants, links, mail preview, REST/MCP retry parity, permissions and rollback)\n";
} finally {
    foreach (array_reverse($decisions) as $decision) $decision->deleteWithRelations();
    foreach ($events as $event) $event->delete();
    mcpCleanup($items); $_SESSION = $before;
}
