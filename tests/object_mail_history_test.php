<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{ObjectMail, ObjectMailRecipient};

$before = $_SESSION;
$items = mcpFixtures();
try {
    $oid = (int)$items['org']->getId();
    $uid = (int)$items['user']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    mcpCheck(ObjectMail::getHistory($oid) === [], 'New sender history is empty');
    for ($i = 0; $i < 33; $i++) {
        $items['mail_' . $i] = mcpFixture(ObjectMail::class, [
            'IDuser' => $uid, 'IDorganization' => $oid, 'object_type' => 'organization', 'object_id' => $oid,
            'IDmcp_oauth_grant' => $i % 2 ? 123456789 : null,
            'request_hash' => hash('sha256', 'history-' . $i), 'payload_hash' => hash('sha256', 'history-' . $i),
            'subject' => 'History ' . $i, 'message' => "Plain text <script>test</script>\nSecond line", 'recipient_count' => 1,
            'created_at' => time() + $i,
        ]);
    }
    $lastId = (int)$items['mail_32']->getId();
    $items['recipient'] = mcpFixture(ObjectMailRecipient::class, ['IDobject_mail' => $lastId,
        'member_id' => 'user:' . $uid, 'email' => 'history@example.invalid', 'status' => 'sent', 'updated_at' => time()]);
    $history = ObjectMail::getHistory($oid, 500);
    mcpCheck(count($history) === 30 && (int)$history[0]['id'] === $lastId && $history[29]['subject'] === 'History 3', 'History is newest first and capped at 30');
    mcpCheck(!isset($history[0]['message']) && !isset($history[0]['request_hash']), 'List exposes only summary fields');
    mcpCheck($history[0]['delivery'] === ['sent' => 1] && !$history[0]['content_expired'], 'Stored content and SMTP status are reflected in summary');
    mcpCheck(str_contains($history[0]['preview'], '<script>test</script>'), 'Legacy text is not treated as HTML');
    mcpCheck(str_contains(ObjectMail::renderMessage('<b>literal</b>', 'plain'), '&lt;b&gt;'), 'Plain text renders literally');
    $items['mail_31']->set('message_format', 'html');
    $items['mail_31']->set('message', '<p>Bonjour <strong>equipe</strong></p><p>Suite</p>');
    $items['mail_31']->save();
    $richHistory = ObjectMail::getHistory($oid, 2);
    mcpCheck($richHistory[1]['preview'] === "Bonjour equipe\nSuite", 'HTML previews contain readable text with paragraph separation');
    mcpCheck(count(ObjectMail::getHistory($oid, 2)) === 2, 'Smaller history limit is honored');
    mcpCheck(count(ObjectMail::getHistory($oid, -1)) === 1, 'Invalid limits are clamped');
    $detail = ObjectMail::getHistoryDetail($oid, $lastId);
    mcpCheck(count($detail['recipients']) === 1 && $detail['recipients'][0]->get('email') === 'history@example.invalid', 'Details contain original recipients');
    $items['membership']->set('active', false); $items['membership']->save();
    $denied = false;
    try { ObjectMail::getHistory($oid); } catch (DomainException $error) { $denied = true; }
    mcpCheck($denied, 'Former members cannot list history');
    $denied = false;
    try { ObjectMail::getHistoryDetail($oid, $lastId); } catch (DomainException $error) { $denied = true; }
    mcpCheck($denied, 'Former members cannot retrieve history detail');
    echo "object_mail_history_test: OK\n";
} finally {
    mcpCleanup($items);
    $_SESSION = $before;
}
