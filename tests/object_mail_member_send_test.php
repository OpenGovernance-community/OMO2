<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{ObjectAudience, ObjectMail, User, UserOrganization, UserHolon, McpOauthGrant};

$before = $_SESSION;
$items = mcpFixtures();
$smtp = null; $pipes = [];
$capture = tempnam(sys_get_temp_dir(), 'omo-member-mail-');
try {
    $_ENV['MAIL_FROM'] = 'noreply@omo.example.invalid';
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['role']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    mcpCheck(!commonCurrentUserCanUseAdminMode($oid), 'Sender is an ordinary organization member');
    for ($i = 0; $i < 6; $i++) {
        $items['recipient_' . $i] = mcpFixture(User::class, ['firstname' => 'Colleague ' . $i, 'email' => 'colleague-' . $i . '@example.invalid', 'active' => 1]);
        $items['membership_' . $i] = mcpFixture(UserOrganization::class, ['IDuser' => $items['recipient_' . $i]->getId(), 'IDorganization' => $oid, 'active' => 1]);
        $items['assignment_' . $i] = mcpFixture(UserHolon::class, ['IDuser' => $items['recipient_' . $i]->getId(), 'IDholon' => $hid, 'active' => $i < 4 ? 1 : 0, 'is_membership' => 1]);
    }
    $audience = ObjectAudience::resolve($oid, 'holon', $hid);
    mcpCheck($audience['can_send'] && count($audience['recipients']) === 4
        && !in_array($uid, array_column($audience['members'], 'user_id'), true), 'Sender outside the holon can contact its four colleagues');
    $smtp = proc_open([PHP_BINARY, __DIR__ . '/object_mail_smtp_fixture.php', $capture], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($smtp), 'Local SMTP sink starts');
    $address = trim(fgets($pipes[1]));
    mcpCheck(str_starts_with($address, '127.0.0.1:'), 'No external SMTP delivery');
    $GLOBALS['mailHost'] = '127.0.0.1'; $GLOBALS['mailPort'] = (int)substr(strrchr($address, ':'), 1);
    $GLOBALS['mailSecure'] = ''; $GLOBALS['mailAuth'] = false; $GLOBALS['mailTimeout'] = 3;
    $args = ['object_type' => 'holon', 'object_id' => $hid, 'subject' => 'Ordinary member message', 'message' => 'Local SMTP test only',
        'request_key' => 'member-native-mail', 'audience_token' => $audience['audience_token']];
    $htmlArgs = array_replace($args, ['message_format' => 'html', 'message' => '<p>Bonjour <strong>equipe</strong></p><ul><li>Premier point</li></ul><script>alert(1)</script><img src="https://example.invalid/pixel"><a href="javascript:alert(2)">Lien</a>']);
    $native = ObjectMail::send($oid, $htmlArgs, null, array_column($audience['recipients'], 'member_id'));
    $items['native_mail'] = new ObjectMail(); $items['native_mail']->load($native['mail_id']);
    mcpCheck($native['delivery']['sent'] === 4 && $native['delivery']['skipped'] === 0, 'Native direct send reaches all four colleagues without admin or context membership');
    $storedHtml = $items['native_mail']->get('message');
    mcpCheck($items['native_mail']->get('message_format') === 'html' && str_contains($storedHtml, '<strong>equipe</strong>')
        && !str_contains($storedHtml, '<script') && !str_contains($storedHtml, '<img') && !str_contains($storedHtml, 'javascript:'), 'Stored rich email keeps formatting and removes active content and tracking images');
    $smtpData = quoted_printable_decode(json_decode(file($capture, FILE_IGNORE_NEW_LINES)[0], true)['data']);
    mcpCheck(str_contains($smtpData, '<strong>equipe</strong>') && str_contains($smtpData, 'text/plain')
        && str_contains($smtpData, 'text/html') && str_contains($smtpData, "Bonjour equipe"), 'SMTP contains formatted HTML and a readable plain text alternative');
    mcpCheck(ObjectMail::send($oid, $htmlArgs, null, array_column($audience['recipients'], 'member_id'))['replayed'], 'Formatted mail retries remain idempotent');
    foreach (['<p><br></p>', '<p>&nbsp;</p>', '<script>only script</script>'] as $emptyHtml) {
        $denied = false;
        try { ObjectMail::enqueue($oid, array_replace($htmlArgs, ['message' => $emptyHtml, 'request_key' => 'empty-rich-email'])); }
        catch (InvalidArgumentException $error) { $denied = true; }
        mcpCheck($denied, 'Visually empty HTML is rejected before enqueue');
    }

    $authorization = mcpAuthorizationRequest($items['client']); $authorization['scope'] = OMO_MCP_SCOPE . ' ' . OMO_MCP_MAIL_SCOPE;
    $code = McpOauthGrant::issueCode($items['client'], $uid, $oid, $authorization);
    $tokens = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));
    $grant = McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl());
    $mcp = ObjectMail::send($oid, array_replace($args, ['request_key' => 'member-mcp-mail']), $grant);
    $items['mcp_mail'] = new ObjectMail(); $items['mcp_mail']->load($mcp['mail_id']);
    mcpCheck($mcp['delivery']['sent'] === 4 && $mcp['delivery']['skipped'] === 0, 'MCP has the same member send permission');

    for ($i = 4; $i < 6; $i++) {
        $items['assignment_' . $i]->set('active', 1);
        $items['assignment_' . $i]->save();
    }
    $args['audience_token'] = ObjectAudience::resolve($oid, 'holon', $hid)['audience_token'];
    $large = ObjectMail::send($oid, array_replace($args, ['request_key' => 'member-background-mail']));
    $items['large_mail'] = new ObjectMail(); $items['large_mail']->load($large['mail_id']);
    mcpCheck($large['delivery_mode'] === 'queued' && $large['delivery']['queued'] === 6, 'Large native sends use the background queue');
    $workerSession = ['currentUser' => (int)$items['recipient_0']->getId(), 'currentOrganization' => (int)$items['other_org']->getId()];
    $_SESSION = $workerSession;
    ObjectMail::processBatch(20, $large['mail_id']);
    mcpCheck($_SESSION === $workerSession, 'Worker restores the caller session');
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    mcpCheck(ObjectMail::status($oid, $large['mail_id'])['delivery']['sent'] === 6, 'Background worker needs no administrator session flags');

    $withdrawn = ObjectMail::enqueue($oid, array_replace($args, ['request_key' => 'member-withdrawn-mail']));
    $items['withdrawn_mail'] = new ObjectMail(); $items['withdrawn_mail']->load($withdrawn['mail_id']);
    $items['membership']->set('active', 0); $items['membership']->save();
    ObjectMail::processBatch(20, $withdrawn['mail_id']);
    $rows = new \dbObject\ArrayObjectMailRecipient(); $rows->load(['where' => [['field' => 'IDobject_mail', 'value' => $withdrawn['mail_id']]]]);
    foreach ($rows as $row) mcpCheck($row->get('status') === 'skipped', 'Sender membership withdrawal still cancels queued deliveries');
    $capturedMessages = file($capture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    mcpCheck(count($capturedMessages) === 14, 'Only authorized fixture emails reached the local SMTP sink');
    foreach ($capturedMessages as $capturedMessage) {
        $headers = json_decode($capturedMessage, true)['data'];
        mcpCheck(preg_match('/^From:.*<noreply@omo\.example\.invalid>/m', $headers) === 1, 'Native, MCP and queued deliveries use the same technical sender');
        mcpCheck(preg_match('/^Reply-To:.*' . preg_quote($audience['sender']['email'], '/') . '/m', $headers) === 1, 'Native, MCP and queued replies return to the member');
    }
    echo "object_mail_member_send_test: OK\n";
} finally {
    if (is_resource($smtp)) { proc_terminate($smtp); foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe); proc_close($smtp); }
    @unlink($capture); mcpCleanup($items); $_SESSION = $before;
}
