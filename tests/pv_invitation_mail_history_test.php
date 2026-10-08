<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{Document, ObjectMail, ObjectMailRecipient};

$before = $_SESSION;
$items = mcpFixtures();
try {
    $oid = (int)$items['org']->getId();
    $uid = (int)$items['user']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $items['pv'] = mcpFixture(Document::class, ['title' => 'Fixture invitation PV', 'documenttype' => Document::TYPE_PV,
        'pvstage' => Document::PV_STAGE_PREPARATION, 'IDorganization' => $oid, 'IDuser' => $uid,
        'IDusercreation' => $uid, 'active' => 1]);
    $recipients = [
        ['email' => 'selected-member@example.invalid', 'user_id' => $uid],
        ['email' => 'selected-guest@example.invalid', 'user_id' => 0],
    ];
    $items['mail'] = ObjectMail::beginPvInvitationDelivery($items['pv'], 'Fixture PV invitation', "Invitation message\nSecond line", $recipients);
    $mailId = (int)$items['mail']->getId();
    $history = ObjectMail::getHistory($oid);
    mcpCheck(count($history) === 1 && (int)$history[0]['id'] === $mailId && $history[0]['delivery'] === ['sending' => 2], 'PV invitations appear in the topbar history before SMTP.');
    $detail = ObjectMail::getHistoryDetail($oid, $mailId);
    mcpCheck($detail['mail']->get('message') === "Invitation message\nSecond line" && $detail['mail']->get('message_format') === 'plain', 'History keeps the original invitation text.');
    mcpCheck(array_map(static fn($recipient) => $recipient->get('email'), iterator_to_array($detail['recipients'])) === array_column($recipients, 'email'), 'History stores the selected audience.');
    $items['mail']->recordRecipientDelivery($recipients[0]['email'], 'sent');
    $items['mail']->recordRecipientDelivery($recipients[1]['email'], 'failed');
    mcpCheck(ObjectMail::getHistory($oid)[0]['delivery'] === ['failed' => 1, 'sent' => 1], 'Partial failures remain visible in the shared history.');
    mcpCheck(ObjectMail::processBatch(10, $mailId) === 0, 'Maintenance never resends native PV invitations.');
    $rejected = false;
    try {
        ObjectMail::beginPvInvitationDelivery($items['pv'], 'Invalid audience', 'Fixture message', [$recipients[0], ['email' => 'invalid']]);
    } catch (InvalidArgumentException $error) { $rejected = true; }
    mcpCheck($rejected && count(ObjectMail::getHistory($oid)) === 1, 'Invalid recipient storage rolls back the entire history snapshot.');
    $items['interrupted'] = ObjectMail::beginPvInvitationDelivery($items['pv'], 'Interrupted invitation', 'Fixture message', [$recipients[1]]);
    $pending = new ObjectMailRecipient();
    mcpCheck($pending->load([['IDobject_mail', $items['interrupted']->getId()], ['email', $recipients[1]['email']]]), 'Interrupted recipient exists.');
    $pending->set('updated_at', time() - 700); $pending->save();
    ObjectMail::processBatch(10, (int)$items['interrupted']->getId());
    mcpCheck(ObjectMail::status($oid, (int)$items['interrupted']->getId())['delivery']['unknown'] === 1, 'An interrupted native send becomes uncertain without retrying.');
    $_SESSION['currentUser'] = 0;
    $denied = false;
    try { ObjectMail::getHistoryDetail($oid, $mailId); } catch (DomainException $error) { $denied = true; }
    mcpCheck($denied, 'PV invitation history remains private to its sender.');
    echo "pv_invitation_mail_history_test: OK\n";
} finally {
    mcpCleanup($items);
    $_SESSION = $before;
}
