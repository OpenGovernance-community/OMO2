<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
if (($argv[1] ?? '') === '--page') {
    $_SESSION = ['currentUser' => (int)$argv[2], 'currentOrganization' => (int)$argv[3], 'isSiteAdminModeEnabled' => true];
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['oid'] = (int)$argv[3];
    require dirname(__DIR__) . (($argv[4] ?? '') === 'documents'
        ? '/omo/api/documents/params/index.php' : '/omo/api/parameters/notifications/index.php');
    exit;
}

// Capture external delivery, including a transient guest email failure; never send real messages.
$pvTestEmails = []; $pvTestFailGuest = false;
function notificationCenterSendEmail($user, $organization, $title, $body, $url): bool
{
    $email = $user->get('email');
    if ($GLOBALS['pvTestFailGuest'] && str_contains($email, '-guest@')) return false;
    $GLOBALS['pvTestEmails'][] = compact('email', 'title', 'body', 'url');
    return true;
}
require_once dirname(__DIR__) . '/common/notification_center.php';
require_once dirname(__DIR__) . '/omo/api/documents/params/shared.php';

use dbObject\{DbObject, Document, DocumentInvitation, DocumentAttendance, DocumentPvNotification, Event,
    EventInvitation, EventAttendance, Notification, NotificationPreference, Organization, User, UserOrganization};

class PvLifecycleTestOrganization extends Organization { public function delete() { return DbObject::delete(); } }

function pvLifecycleCheck(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
}
function pvLifecycleSave($object): void
{
    $result = $object->save();
    pvLifecycleCheck(!empty($result['status']), 'Save failed: ' . json_encode($result));
}
$items = [];
function pvLifecycleFixture(string $class, array $fields)
{
    $item = new $class();
    foreach ($fields as $key => $value) $item->set($key, $value);
    pvLifecycleSave($item);
    $GLOBALS['items'][] = $item;
    return $item;
}
function pvLifecycleQueued(Document $document, string $stage): DocumentPvNotification
{
    $item = new DocumentPvNotification();
    pvLifecycleCheck($item->load([['IDdocument', $document->getId()], ['stage', $stage]], true), 'One queued transition per stage');
    return $item;
}
function pvLifecycleNotification(DocumentPvNotification $transition, int $uid): ?Notification
{
    $item = new Notification();
    return $item->load([['IDuser', $uid], ['source_key', 'document-pv-stage-' . $transition->getId()]], true) ? $item : null;
}

$pdo = DbObject::getPdo();
try {
    $nonce = 'pv-review-' . bin2hex(random_bytes(6));
    $org = pvLifecycleFixture(PvLifecycleTestOrganization::class, ['name' => $nonce, 'shortname' => $nonce]);
    $oid = (int)$org->getId();
    $users = [];
    foreach (['owner', 'present', 'absent', 'muted', 'revoked'] as $name) {
        $users[$name] = pvLifecycleFixture(User::class, ['email' => "$nonce-$name@example.invalid", 'active' => 1, 'siteadmin' => $name === 'owner' ? 1 : 0]);
        pvLifecycleFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $users[$name]->getId(), 'active' => 1]);
    }
    $owner = (int)$users['owner']->getId();
    $_SESSION = ['currentUser' => $owner, 'currentOrganization' => $oid];
    $app = $org->ensureApplicationLinkByDirectory('documents');
    pvLifecycleCheck($app !== null, 'Documents application');
    $items[] = $app;
    $app->set('active', 1); pvLifecycleSave($app);
    pvLifecycleCheck($org->getPvDocumentSettings()['reviewDays'] === 5, 'Five days by default');
    foreach ([0, -1, 366, 'abc', '2.5'] as $invalid) {
        pvLifecycleCheck(empty(omoDocumentsParamsStorePvSettings($org, ['pv_review_days' => $invalid])['status']), 'Reject invalid review delay');
    }
    pvLifecycleCheck(!empty(omoDocumentsParamsStorePvSettings($org, ['pv_review_days' => 7])['status']), 'Save organization delay');
    pvLifecycleCheck($org->getPvDocumentSettings()['reviewDays'] === 7, 'Organization delay persisted');
    pvLifecycleCheck(!empty(omoDocumentsParamsStorePvSettings($org, [])['status'])
        && $org->getPvDocumentSettings()['reviewDays'] === 7, 'Legacy settings saves retain delay');
    foreach (['document_pv_review_started', 'document_pv_closed'] as $key) {
        pvLifecycleCheck(NotificationPreference::saveChannels($users['muted']->getId(), $oid, $key, []), 'Save muted channels');
        pvLifecycleCheck(NotificationPreference::saveChannels($users['present']->getId(), $oid, $key, ['in_app' => 1, 'email' => 1]), 'Save email channels');
    }
    $event = pvLifecycleFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $owner, 'title' => $nonce,
        'status' => Event::STATUS_CONFIRMED, 'active' => 1,
        'start_at' => new DateTimeImmutable('-10 days'), 'end_at' => new DateTimeImmutable('-10 days +1 hour')]);
    foreach ($users as $name => $user) {
        pvLifecycleFixture(EventInvitation::class, ['IDevent' => $event->getId(), 'IDuser' => $user->getId(),
            'invitation_type' => EventInvitation::TYPE_USER, 'active' => 1,
            'status' => $name === 'revoked' ? EventInvitation::STATUS_REVOKED : EventInvitation::STATUS_INVITED]);
        if (in_array($name, ['present', 'muted'], true)) {
            pvLifecycleFixture(EventAttendance::class, ['IDevent' => $event->getId(), 'IDuser' => $user->getId(), 'is_present' => 1, 'active' => 1]);
        }
    }
    $guestEmail = "$nonce-guest@example.invalid";
    pvLifecycleFixture(EventInvitation::class, ['IDevent' => $event->getId(), 'email' => $guestEmail,
        'invitation_type' => EventInvitation::TYPE_EMAIL, 'status' => EventInvitation::STATUS_INVITED, 'active' => 1]);
    pvLifecycleFixture(EventAttendance::class, ['IDevent' => $event->getId(), 'email' => $guestEmail, 'is_present' => 1, 'active' => 1]);
    $absentGuestEmail = "$nonce-absent-guest@example.invalid";
    pvLifecycleFixture(EventInvitation::class, ['IDevent' => $event->getId(), 'email' => $absentGuestEmail,
        'invitation_type' => EventInvitation::TYPE_EMAIL, 'status' => EventInvitation::STATUS_INVITED, 'active' => 1]);
    $makePv = static fn(array $fields = []): Document => pvLifecycleFixture(Document::class, $fields + [
        'IDorganization' => $oid, 'IDuser' => $owner, 'IDusercreation' => $owner,
        'IDuser_pv_editor' => $owner, 'IDuser_pv_official_editor' => $owner,
        'IDevent' => $event->getId(), 'title' => $nonce, 'documenttype' => Document::TYPE_PV,
        'pvstage' => Document::PV_STAGE_MEETING, 'active' => 1]);
    $pv = $makePv();
    pvLifecycleCheck(empty($pv->updatePvStageInOrganizationContext($oid, $users['absent']->getId(), Document::PV_STAGE_REVIEW)['status']), 'Only authorized editor changes stages');
    $pdo->beginTransaction();
    pvLifecycleCheck(!empty($pv->updatePvStageInOrganizationContext($oid, $owner, Document::PV_STAGE_REVIEW)['status']), 'Enter review');
    $startedAt = $pv->get('pv_review_started_at');
    pvLifecycleCheck($startedAt instanceof DateTimeInterface, 'Review timestamp stored');
    $queued = pvLifecycleQueued($pv, Document::PV_STAGE_REVIEW);
    $audience = $queued->getPayload();
    pvLifecycleCheck(in_array($users['present']->getId(), $audience['userIds'], true)
        && !in_array($users['absent']->getId(), $audience['userIds'], true), 'Review targets attendance only');
    pvLifecycleCheck($audience['guests'][0]['email'] === $guestEmail, 'Present email guest included');
    pvLifecycleCheck(DocumentPvNotification::processPending(20, (int)$pv->getId()) === 0 && $pvTestEmails === [], 'No delivery before commit');
    $pdo->rollBack();
    $pv->load($pv->getId(), true);
    pvLifecycleCheck($pv->getPvStage() === Document::PV_STAGE_MEETING, 'Rollback also rolls back review');
    pvLifecycleCheck(!empty($pv->updatePvStageInOrganizationContext($oid, $owner, Document::PV_STAGE_REVIEW)['status']), 'Review after rollback');
    $startedAt = $pv->get('pv_review_started_at')->format('Y-m-d H:i:s');
    $review = pvLifecycleQueued($pv, Document::PV_STAGE_REVIEW);
    pvLifecycleCheck(pvLifecycleNotification($review, (int)$users['present']->getId()) !== null, 'Attendee receives review notification');
    pvLifecycleCheck(pvLifecycleNotification($review, (int)$users['absent']->getId()) === null, 'Absent invitee receives no review notification');
    pvLifecycleCheck(pvLifecycleNotification($review, (int)$users['muted']->getId()) === null, 'Channel preferences respected');
    $sentCount = count($pvTestEmails);
    pvLifecycleCheck(!empty($pv->updatePvStageInOrganizationContext($oid, $owner, Document::PV_STAGE_REVIEW)['status']), 'Repeated stage request accepted');
    pvLifecycleCheck($startedAt === $pv->get('pv_review_started_at')->format('Y-m-d H:i:s') && count($pvTestEmails) === $sentCount, 'Repeated review does not reset timer or notify twice');
    pvLifecycleCheck(!empty($pv->updatePvStageInOrganizationContext($oid, $owner, Document::PV_STAGE_VALIDATED)['status']), 'Manual closure');
    $closed = pvLifecycleQueued($pv, Document::PV_STAGE_VALIDATED);
    pvLifecycleCheck(pvLifecycleNotification($closed, (int)$users['absent']->getId()) !== null, 'Absent invitee receives closure notification');
    pvLifecycleCheck(pvLifecycleNotification($closed, (int)$users['revoked']->getId()) === null, 'Revoked invitee excluded');
    pvLifecycleCheck(in_array($absentGuestEmail, array_column($closed->getPayload()['guests'], 'email'), true), 'Absent email guest receives closure too');

    $now = new DateTimeImmutable('2026-10-20 12:00:00');
    $due = $makePv(['pvstage' => Document::PV_STAGE_REVIEW, 'pv_review_started_at' => $now->modify('-7 days')]);
    $later = $makePv(['pvstage' => Document::PV_STAGE_REVIEW, 'pv_review_started_at' => $now->modify('-7 days +1 second')]);
    $legacy = $makePv(['pvstage' => Document::PV_STAGE_REVIEW]);
    $archived = $makePv(['pvstage' => Document::PV_STAGE_REVIEW, 'active' => 0, 'pv_review_started_at' => $now->modify('-30 days')]);
    $template = $makePv(['pvstage' => Document::PV_STAGE_REVIEW, 'is_template' => 1, 'pv_review_started_at' => $now->modify('-30 days')]);
    $undated = $makePv(['pvstage' => Document::PV_STAGE_REVIEW, 'IDevent' => null]);
    pvLifecycleCheck($undated->getPvReviewDeadline() === null, 'No invented date when neither review start nor meeting end exists');
    $stale = new Document(); $stale->load($due->getId(), true);
    pvLifecycleCheck($legacy->getPvReviewDeadline()->format('Y-m-d H:i:s') === DateTimeImmutable::createFromInterface($event->get('end_at'))->modify('+7 days')->format('Y-m-d H:i:s'), 'Missing review timestamp falls back to meeting end');
    pvLifecycleCheck(Document::closeDuePvReviewsBatch(200, $now, $oid) === 2, 'Cron closes exact deadline and legacy review only');
    $due->load($due->getId(), true); $later->load($later->getId(), true);
    pvLifecycleCheck($due->isPvValidated() && !$later->isPvValidated(), 'Deadline precision respected');
    pvLifecycleCheck(empty($stale->updateInOrganizationContext($oid, $owner, ['title' => 'Stale metadata', 'document_type' => Document::TYPE_PV])['status']), 'Older metadata form cannot reopen a closed PV');
    $due->load($due->getId(), true);
    pvLifecycleCheck($due->isPvValidated(), 'Automatic closure survives stale metadata save');
    pvLifecycleCheck(Document::closeDuePvReviewsBatch(200, $now, $oid) === 0, 'Repeated cron is idempotent');
    $pvTestFailGuest = true;
    pvLifecycleCheck(DocumentPvNotification::processPending(20, (int)$due->getId()) === 0, 'Failed guest delivery retained for retry');
    $auto = pvLifecycleQueued($due, Document::PV_STAGE_VALIDATED);
    pvLifecycleCheck(pvLifecycleNotification($auto, (int)$users['absent']->getId()) !== null && $auto->getPayload()['automatic'], 'Automatic closure notifies all invitees');
    $countAfterFailure = count($pvTestEmails);
    $pvTestFailGuest = false;
    pvLifecycleCheck(DocumentPvNotification::processPending(20, (int)$due->getId()) === 1, 'Retry completes pending guest notification');
    pvLifecycleCheck(count($pvTestEmails) === $countAfterFailure + 2, 'Retry sends both guest emails without resending member email');
    pvLifecycleCheck(DocumentPvNotification::processPending(20, (int)$due->getId()) === 0, 'No repeated delivery');

    $standalone = $makePv(['IDevent' => null]);
    pvLifecycleFixture(DocumentInvitation::class, ['IDdocument' => $standalone->getId(), 'IDuser' => $users['present']->getId(),
        'invitation_type' => DocumentInvitation::TYPE_USER, 'status' => DocumentInvitation::STATUS_INVITED, 'active' => 1]);
    pvLifecycleFixture(DocumentAttendance::class, ['IDdocument' => $standalone->getId(), 'IDuser' => $users['present']->getId(), 'is_present' => 1, 'active' => 1]);
    pvLifecycleCheck(!empty($standalone->updatePvStageInOrganizationContext($oid, $owner, Document::PV_STAGE_REVIEW)['status']), 'PV without meeting uses document attendance');
    pvLifecycleCheck(pvLifecycleNotification(pvLifecycleQueued($standalone, Document::PV_STAGE_REVIEW), (int)$users['present']->getId()) !== null, 'Standalone attendee notified');
    pvLifecycleCheck(isset(notificationCenterGetActiveEventGroups($oid, $owner)['documents']), 'Both PV notification settings are exposed for enabled Documents app');
    foreach (['documents', 'notifications'] as $page) {
        $process = proc_open([PHP_BINARY, __FILE__, '--page', (string)$owner, (string)$oid, $page],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        pvLifecycleCheck(is_resource($process), 'Start settings render');
        fclose($pipes[0]); $html = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
        pvLifecycleCheck(proc_close($process) === 0 && $errors === '', 'Settings render: ' . $errors);
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true); $dom->loadHTML($html); libxml_clear_errors(); libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($dom);
        if ($page === 'documents') {
            $input = $xpath->query('//input[@name="pv_review_days"]')->item(0);
            pvLifecycleCheck($input instanceof DOMElement && $input->getAttribute('value') === '7', 'Document settings show saved review duration');
        } else {
            foreach (['document_pv_review_started', 'document_pv_closed'] as $key) {
                foreach (['in_app', 'email'] as $channel) {
                    pvLifecycleCheck($xpath->query('//input[@name="preferences[' . $key . '][' . $channel . ']"]')->length === 1,
                        'Notification channel is configurable: ' . $key . ' / ' . $channel);
                }
            }
        }
    }
    echo "pv_review_lifecycle_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach (array_reverse($items) as $item) $item->delete();
    DbObject::$preload = [];
}
