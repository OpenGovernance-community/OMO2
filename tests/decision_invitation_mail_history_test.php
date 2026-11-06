<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{ArrayApplication, DecisionParticipant, DecisionProcess, DecisionResponse, ObjectMail, ObjectMailRecipient, OrganizationApplication, User, UserOrganization};

// Run the real endpoint in a separate request, against a loopback-only SMTP sink.
if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = ['currentUser' => $request['uid'], 'currentOrganization' => $request['oid'],
        'auth_security_version' => $request['security_version']];
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/omo/api/decision/send_invitations_popup.php';
    $_GET = ['oid' => $request['oid'], 'cid' => $request['hid'], 'id' => $request['id']];
    $_POST = ['message' => $request['message'], 'send_scope' => $request['scope']];
    $_REQUEST = array_merge($_GET, $_POST);
    $GLOBALS['mailHost'] = '127.0.0.1';
    $GLOBALS['mailPort'] = $request['port'];
    $GLOBALS['mailUser'] = 'fixture@example.invalid';
    $GLOBALS['mailSecure'] = '';
    $GLOBALS['mailAuth'] = false;
    $GLOBALS['mailTimeout'] = 3;
    require dirname(__DIR__) . '/omo/api/decision/send_invitations_popup.php';
    exit;
}

$before = $_SESSION;
$items = mcpFixtures();
$smtp = null;
$pipes = [];
$capture = tempnam(sys_get_temp_dir(), 'omo-decision-mail-');
try {
    $oid = (int)$items['org']->getId();
    $uid = (int)$items['user']->getId();
    $hid = (int)$items['root']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'value' => 'decision']], 'limit' => 1]);
    mcpCheck(count($apps) === 1, 'Decision app exists.');
    $items['decision_app'] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid,
        'IDapplication' => $apps[0]->getId(), 'active' => 1]);
    $items['decision'] = mcpFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $hid,
        'IDuser' => $uid, 'title' => 'Fixture scrutiny', 'status' => DecisionProcess::STATUS_EVALUATION,
        'evaluation_method' => DecisionProcess::METHOD_SIMPLE_VOTE, 'evaluation_start_at' => '2020-01-01 00:00:00',
        'evaluation_end_at' => '2090-01-01 00:00:00']);
    foreach (['pending', 'answered'] as $name) {
        $items[$name] = mcpFixture(DecisionParticipant::class, ['IDdecision_process' => $items['decision']->getId(),
            'email' => $name . '@example.invalid', 'display_name' => $name, 'role' => DecisionParticipant::ROLE_PARTICIPANT,
            'status' => DecisionParticipant::STATUS_INVITED, 'active' => 1]);
    }
    $items['owner'] = mcpFixture(DecisionParticipant::class, ['IDdecision_process' => $items['decision']->getId(),
        'IDuser' => $uid, 'role' => DecisionParticipant::ROLE_OWNER, 'status' => DecisionParticipant::STATUS_ACTIVE, 'active' => 1]);
    $group = $items['decision']->getPrimaryGroup(true);
    $items['response'] = mcpFixture(DecisionResponse::class, ['IDdecision_process' => $items['decision']->getId(),
        'IDdecision_group' => $group->getId(), 'IDdecision_participant' => $items['answered']->getId(),
        'status' => DecisionResponse::STATUS_SUBMITTED, 'submitted_at' => '2026-01-01 00:00:00']);
    $owner = DecisionParticipant::findByDecisionAndUser((int)$items['decision']->getId(), $uid);
    mcpCheck($owner instanceof DecisionParticipant, 'Decision author is also a participant.');
    $items['owner_response'] = mcpFixture(DecisionResponse::class, ['IDdecision_process' => $items['decision']->getId(),
        'IDdecision_group' => $group->getId(), 'IDdecision_participant' => $owner->getId(),
        'status' => DecisionResponse::STATUS_SUBMITTED, 'submitted_at' => '2026-01-01 00:00:00']);

    $smtp = proc_open([PHP_BINARY, __DIR__ . '/object_mail_smtp_fixture.php', $capture],
        [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($smtp), 'Local SMTP sink starts.');
    $address = trim(fgets($pipes[1]));
    mcpCheck(str_starts_with($address, '127.0.0.1:'), 'No external SMTP delivery.');
    $port = (int)substr(strrchr($address, ':'), 1);
    $request = static function (string $scope, string $message, ?int $smtpPort = null) use ($items, $uid, $oid, $hid, $port): array {
        $args = ['uid' => $uid, 'oid' => $oid, 'hid' => $hid, 'id' => (int)$items['decision']->getId(),
            'security_version' => (int)$items['user']->get('security_version'), 'port' => $smtpPort ?? $port,
            'message' => $message, 'scope' => $scope];
        $process = proc_open([PHP_BINARY, __FILE__, '--request', base64_encode(json_encode($args, JSON_THROW_ON_ERROR))],
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $requestPipes);
        mcpCheck(is_resource($process), 'Endpoint request starts.');
        fclose($requestPipes[0]);
        $output = stream_get_contents($requestPipes[1]); fclose($requestPipes[1]);
        $errors = stream_get_contents($requestPipes[2]); fclose($requestPipes[2]);
        mcpCheck(proc_close($process) === 0, 'Endpoint request failed: ' . $output . $errors);
        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    };

    $pending = $request('pending', "Custom reminder\nSecond line");
    mcpCheck(!empty($pending['status']), 'Pending invitation succeeds.');
    $items['pending_mail'] = new ObjectMail();
    mcpCheck($items['pending_mail']->load($pending['mail_id']), 'Endpoint returns the stored history ID.');
    $history = ObjectMail::getHistory($oid);
    mcpCheck(count($history) === 1 && $history[0]['object_type'] === 'decision_invitation'
        && $history[0]['delivery'] === ['sent' => 1], 'Sent invitation appears in the topbar history: ' . json_encode($history));
    $detail = ObjectMail::getHistoryDetail($oid, $pending['mail_id']);
    mcpCheck(count($detail['recipients']) === 1 && $detail['recipients'][0]->get('email') === 'pending@example.invalid',
        'Pending scope archives only the non-respondent.');
    mcpCheck($detail['mail']->get('message') === "Custom reminder\nSecond line"
        && $detail['mail']->get('message_format') === 'plain', 'History stores only the custom text.');
    $mailHtml = quoted_printable_decode(json_decode(file($capture, FILE_IGNORE_NEW_LINES)[0], true)['data']);
    $items['pending']->load((int)$items['pending']->getId(), true);
    $token = (string)$items['pending']->get('access_token');
    mcpCheck($token !== '' && str_contains($mailHtml, '/decision/access/' . $token), 'SMTP email still contains its personal access link.');
    $stored = json_encode([$history, $detail['mail']->get('message'),
        array_map(static fn($recipient) => [$recipient->get('email'), $recipient->get('member_id')], iterator_to_array($detail['recipients']))], JSON_THROW_ON_ERROR);
    mcpCheck(!str_contains($stored, $token) && !str_contains($stored, '/decision/access/'), 'No access token or personal link enters history.');
    mcpCheck(ObjectMail::processBatch(10, $pending['mail_id']) === 0, 'Maintenance cannot resend native decision invitations.');

    $all = $request('all', 'Message to everyone');
    mcpCheck(!empty($all['status']), 'All-recipient send succeeds.');
    $items['all_mail'] = new ObjectMail(); $items['all_mail']->load($all['mail_id']);
    $detail = ObjectMail::getHistoryDetail($oid, $all['mail_id']);
    mcpCheck(count($detail['recipients']) === 3 && ObjectMail::status($oid, $all['mail_id'])['delivery']['sent'] === 3,
        'All scope keeps guests and author in a separate campaign.');
    mcpCheck(count(file($capture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) === 4, 'Exactly four fixture emails were sent.');

    $failed = $request('all', 'Failed SMTP attempt', 1);
    mcpCheck(empty($failed['status']) && !empty($failed['mail_id']), 'A wholly failed send still returns its history ID.');
    $items['failed_mail'] = new ObjectMail(); $items['failed_mail']->load($failed['mail_id']);
    mcpCheck(ObjectMail::status($oid, $failed['mail_id'])['delivery']['failed'] === 3, 'SMTP failures are retained for all recipients.');

    $recipient = ['email' => 'pending@example.invalid'];
    $items['interrupted_mail'] = ObjectMail::beginDecisionInvitationDelivery($items['decision'], 'Interrupted send', 'Custom text', [$recipient]);
    $delivery = new ObjectMailRecipient();
    mcpCheck($delivery->load([['IDobject_mail', $items['interrupted_mail']->getId()], ['email', $recipient['email']]]), 'Initial tracking exists.');
    $delivery->set('updated_at', time() - 700); $delivery->save();
    ObjectMail::processBatch(10, (int)$items['interrupted_mail']->getId());
    mcpCheck(ObjectMail::status($oid, (int)$items['interrupted_mail']->getId())['delivery']['unknown'] === 1,
        'Interrupted sends become uncertain without automatic retry.');
    $count = count(ObjectMail::getHistory($oid));
    $invalid = $request('all', str_repeat('x', 20001));
    mcpCheck(empty($invalid['status']) && count(ObjectMail::getHistory($oid)) === $count
        && count(file($capture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) === 4,
        'A journal storage failure rolls back and prevents all SMTP sends.');

    $items['other_user'] = mcpFixture(User::class, ['email' => 'other-' . bin2hex(random_bytes(5)) . '@example.invalid', 'active' => 1]);
    $items['other_membership'] = mcpFixture(UserOrganization::class, ['IDorganization' => $oid,
        'IDuser' => $items['other_user']->getId(), 'active' => 1]);
    $_SESSION['currentUser'] = (int)$items['other_user']->getId();
    mcpCheck(ObjectMail::getHistory($oid) === [], 'Another active member cannot list the sender history.');
    $denied = false;
    try { ObjectMail::getHistoryDetail($oid, $pending['mail_id']); } catch (DomainException $error) { $denied = true; }
    mcpCheck($denied, 'Another active member cannot read the sender message or recipients.');
    $denied = false;
    try { ObjectMail::beginDecisionInvitationDelivery($items['decision'], 'Unauthorized', 'Custom text', [$recipient]); }
    catch (DomainException $error) { $denied = true; }
    mcpCheck($denied, 'Organization membership alone does not authorize a decision invitation.');
    $_SESSION['currentUser'] = $uid;
    $items['decision']->set('status', DecisionProcess::STATUS_DRAFT); $items['decision']->save();
    $denied = false;
    try { ObjectMail::beginDecisionInvitationDelivery($items['decision'], 'Draft', 'Custom text', [$recipient]); }
    catch (DomainException $error) { $denied = true; }
    mcpCheck($denied, 'Draft decisions cannot create invitation campaigns.');
    $_SESSION['currentUser'] = 0;
    $denied = false;
    try { ObjectMail::getHistoryDetail($oid, $pending['mail_id']); } catch (DomainException $error) { $denied = true; }
    mcpCheck($denied, 'The journal remains private to its sender.');
    echo "decision_invitation_mail_history_test: OK\n";
} finally {
    if (is_resource($smtp)) {
        proc_terminate($smtp);
        foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
        proc_close($smtp);
    }
    @unlink($capture);
    mcpCleanup($items);
    $_SESSION = $before;
}
