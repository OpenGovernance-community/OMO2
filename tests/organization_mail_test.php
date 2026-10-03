<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{ObjectAudience, ObjectMail, User, UserOrganization, McpOauthGrant};

function organizationMailDenied(callable $action): void
{
    $denied = false;
    try { $action(); } catch (DomainException | InvalidArgumentException $error) { $denied = true; }
    mcpCheck($denied, 'Invalid organization audience must be rejected');
}

$before = $_SESSION; $items = mcpFixtures(); $smtp = null; $pipes = [];
$capture = tempnam(sys_get_temp_dir(), 'omo-org-mail-');
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $items['app']->set('active', 0); $items['app']->save(); // No Team/Structure/Calendar app or management role required.
    foreach (['member', 'duplicate', 'pending', 'inactive', 'foreign'] as $key) {
        $items[$key] = mcpFixture(User::class, ['firstname' => $key, 'email' => $key . '-global@example.invalid', 'active' => $key === 'inactive' ? 0 : 1]);
        $items[$key . '_org'] = mcpFixture(UserOrganization::class, [
            'IDorganization' => $key === 'foreign' ? $items['other_org']->getId() : $oid,
            'IDuser' => $items[$key]->getId(), 'active' => $key === 'pending' ? 0 : 1,
            'email' => in_array($key, ['member', 'duplicate'], true) ? 'selected@example.invalid' : $key . '@example.invalid']);
    }
    $mid = (int)$items['member']->getId(); $did = (int)$items['duplicate']->getId();
    $base = ['object_type' => 'organization', 'object_id' => $oid];
    $all = ObjectAudience::page($oid, $base + ['limit' => 2]);
    mcpCheck($all['can_send'] && $all['total'] === 3 && $all['recipient_count'] === 2 && !$all['complete'], 'Organization members are available without a holon and emails are deduplicated');
    $next = ObjectAudience::page($oid, $base + ['offset' => $all['next_offset'], 'limit' => 2]);
    mcpCheck($next['complete'] && count($next['items']) === 1, 'Organization members are paginated');
    foreach (['pending', 'inactive', 'foreign'] as $key) {
        organizationMailDenied(fn () => ObjectAudience::page($oid, $base + ['user_ids' => [(int)$items[$key]->getId()]]));
    }
    organizationMailDenied(fn () => ObjectAudience::page($oid, ['object_type' => 'organization', 'object_id' => (int)$items['other_org']->getId()]));
    $selected = ObjectAudience::page($oid, $base + ['user_ids' => [$mid, $did]]);
    mcpCheck($selected['total'] === 2 && $selected['recipient_count'] === 1 && $selected['can_send'], 'Explicit selection need not include sender');
    mcpCheck(!str_contains(json_encode($selected), '-global@'), 'Only organization-scoped contacts are exposed');
    mcpCheck(ObjectAudience::page($oid, $base + ['user_ids' => [$did, $mid]])['audience_token'] === $selected['audience_token'], 'Selection order is immaterial');

    $authorization = mcpAuthorizationRequest($items['client']); $authorization['scope'] = OMO_MCP_SCOPE . ' ' . OMO_MCP_MAIL_SCOPE;
    $code = McpOauthGrant::issueCode($items['client'], $uid, $oid, $authorization);
    $tokens = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));
    $grant = McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl());
    $single = ObjectAudience::page($oid, $base + ['user_ids' => [$mid]]);
    $args = $base + ['user_ids' => [$mid], 'subject' => 'Organization message', 'message' => 'Selected member only',
        'request_key' => 'org-member-mail', 'audience_token' => $single['audience_token']];
    $queued = ObjectMail::enqueue($oid, $args, $grant);
    $items['mail'] = new ObjectMail(); $items['mail']->load($queued['mail_id']);
    mcpCheck($queued['recipient_count'] === 1, 'Only selected member is queued');
    mcpCheck(ObjectMail::enqueue($oid, $args, $grant)['replayed'], 'Retry does not create a second delivery');
    organizationMailDenied(fn () => ObjectMail::enqueue($oid, array_replace($args, ['user_ids' => [$did]]), $grant));
    $withoutSelection = $args; unset($withoutSelection['user_ids']); $withoutSelection['request_key'] = 'no-selection-mail';
    organizationMailDenied(fn () => ObjectMail::enqueue($oid, $withoutSelection, $grant));

    $smtp = proc_open([PHP_BINARY, __DIR__ . '/object_mail_smtp_fixture.php', $capture], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($smtp), 'Local SMTP sink starts');
    $address = trim(fgets($pipes[1]));
    mcpCheck(str_starts_with($address, '127.0.0.1:'), 'No external SMTP delivery');
    $GLOBALS['mailHost'] = '127.0.0.1'; $GLOBALS['mailPort'] = (int)substr(strrchr($address, ':'), 1);
    $GLOBALS['mailSecure'] = ''; $GLOBALS['mailAuth'] = false; $GLOBALS['mailTimeout'] = 3;
    ObjectMail::processBatch(5, $queued['mail_id']);
    mcpCheck(ObjectMail::status($oid, $queued['mail_id'])['delivery']['sent'] === 1, 'Selected member receives message');
    $captured = array_map(static fn ($line) => json_decode($line, true), file($capture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    mcpCheck(count($captured) === 1 && count($captured[0]['recipients']) === 1
        && str_contains(json_encode($captured[0]['recipients']), 'selected@example.invalid'), 'SMTP targets only the scoped address');

    $pending = ObjectMail::enqueue($oid, array_replace($args, ['request_key' => 'withdrawn-member']), $grant);
    $items['pending_mail'] = new ObjectMail(); $items['pending_mail']->load($pending['mail_id']);
    $items['member_org']->set('active', 0); $items['member_org']->save();
    ObjectMail::processBatch(5, $pending['mail_id']);
    mcpCheck(ObjectMail::status($oid, $pending['mail_id'])['delivery']['skipped'] === 1, 'Membership withdrawal blocks delivery even if another member shares the address');
    mcpCheck(count(file($capture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) === 1, 'Withdrawn member receives no mail');
    $items['membership']->set('active', 0); $items['membership']->save();
    organizationMailDenied(fn () => ObjectAudience::page($oid, $base));
    echo "organization_mail_test: OK\n";
} finally {
    if (is_resource($smtp)) { proc_terminate($smtp); foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe); proc_close($smtp); }
    @unlink($capture); mcpCleanup($items); $_SESSION = $before;
}
