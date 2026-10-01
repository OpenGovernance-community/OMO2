<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/external_calendar.php';

use dbObject\DbObject;
use dbObject\ExternalCalendar;

function editExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function editFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $name => $value) { $object->set($name, $value); }
    editExpect(!empty($object->save()['status']), 'Fixture could not be saved: ' . $class);
    return $object;
}

// Local fixtures only. The closed loopback port exercises a failed resync without contacting a provider.
putenv('EXTERNAL_CALENDAR_ENCRYPTION_KEY=calendar-edit-test-key');
putenv('OMO_EXTERNAL_CALENDAR_ALLOW_PRIVATE_HOSTS=1');
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
$mode = $argv[1] ?? 'ics';
$nonce = bin2hex(random_bytes(6));
$user = editFixture(\dbObject\User::class, ['firstname' => 'Calendar editor', 'email' => 'calendar-edit-' . $nonce . '@example.invalid']);
$org = editFixture(\dbObject\Organization::class, ['name' => 'Calendar edit fixture', 'shortname' => 'edit-' . $nonce]);
editFixture(\dbObject\UserOrganization::class, ['IDuser' => $user->getId(), 'IDorganization' => $org->getId(), 'active' => 1]);
$url = 'https://127.0.0.1:1/original';
$secret = commonExternalCalendarEncryptPassword($mode === 'caldav' || $mode === 'credentials' ? 'old-password' : $url);
$isIcs = !in_array($mode, ['caldav', 'credentials'], true);
$calendar = editFixture(ExternalCalendar::class, ['IDuser' => $user->getId(), 'provider' => $isIcs ? 'ics' : 'caldav',
    'title' => 'Original', 'color' => '#112233', 'calendar_url' => $isIcs ? 'ics:' . hash('sha256', $url) : $url,
    'username' => $isIcs ? 'ics' : 'old-user', 'password_encrypted' => $secret, 'active' => 1,
    'last_sync_at' => new DateTimeImmutable('2026-09-01 12:00:00')]);
$_SESSION['currentUser'] = (int)$user->getId();
$_SESSION['currentOrganization'] = (int)$org->getId();
$_SESSION['omo_external_calendar_csrf'] = 'test-csrf';
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['REQUEST_URI'] = '/omo/api/calendar/external_calendars.php';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = $_REQUEST = ['oid' => $org->getId()];
$_POST = ['action' => 'update', 'calendar_id' => $calendar->getId(), 'csrf_token' => 'test-csrf',
    'title' => 'Changed', 'color' => '#ABCDEF', 'ics_url' => '', 'calendar_url' => $url, 'username' => 'old-user', 'password' => ''];
if ($mode === 'render') {
    register_shutdown_function(static function () use ($pdo): void { if ($pdo->inTransaction()) { $pdo->rollBack(); } });
    $holon = editFixture(\dbObject\Holon::class, ['name' => 'Calendar fixture', 'IDorganization' => $org->getId(), 'active' => 1, 'visible' => 1]);
    foreach (['calendar', 'structure'] as $hash) {
        $app = new \dbObject\Application();
        if ($app->load([['hash', $hash]])) {
            editFixture(\dbObject\OrganizationApplication::class, ['IDorganization' => $org->getId(), 'IDapplication' => $app->getId(), 'active' => 1]);
        }
    }
    editFixture(ExternalCalendar::class, ['IDuser' => $user->getId(), 'provider' => 'caldav', 'title' => 'Work calendar',
        'calendar_url' => $url, 'username' => 'test-user', 'password_encrypted' => $secret, 'active' => 1, 'color' => '#663399']);
    $_GET = $_REQUEST = ['oid' => $org->getId(), 'cid' => $holon->getId()];
    require dirname(__DIR__) . '/omo/api/calendar/connect.php';
    exit;
}
if ($mode === 'csrf') { $_POST['csrf_token'] = 'wrong'; }
if ($mode === 'invalid') { $_POST['ics_url'] = 'http://127.0.0.1/private'; }
if ($mode === 'replacement') { $_POST['ics_url'] = 'https://127.0.0.1:1/replaced'; }
if ($mode === 'credentials') { $_POST['username'] = 'new-user'; $_POST['password'] = 'new-password'; $_POST['calendar_url'] = 'https://127.0.0.1:1/replaced'; }
if (in_array($mode, ['owner', 'sync-owner'], true)) {
    $other = editFixture(\dbObject\User::class, ['email' => 'other-edit-' . $nonce . '@example.invalid']);
    $calendar->set('IDuser', $other->getId()); $calendar->save();
    if ($mode === 'sync-owner') { $_POST['action'] = 'sync'; }
}
if ($mode === 'duplicate') {
    $replacement = 'https://127.0.0.1:1/duplicate';
    editFixture(ExternalCalendar::class, ['IDuser' => $user->getId(), 'provider' => 'ics', 'title' => 'Other',
        'calendar_url' => 'ics:' . hash('sha256', $replacement), 'username' => 'ics', 'password_encrypted' => $secret, 'active' => 1]);
    $_POST['ics_url'] = $replacement;
}
ob_start();
register_shutdown_function(static function () use ($pdo, $mode, $calendar, $secret, $isIcs): void {
    $body = ob_get_clean();
    try {
        $result = json_decode($body, true);
        $accepted = in_array($mode, ['ics', 'caldav', 'replacement', 'credentials'], true);
        editExpect(is_array($result) && ($result['status'] ?? null) === $accepted, 'Unexpected response: ' . $body);
        $calendar->load((int)$calendar->getId(), true);
        editExpect($calendar->get('title') === ($accepted ? 'Changed' : 'Original'), 'Unexpected title mutation.');
        editExpect($calendar->get('color') === ($accepted ? '#abcdef' : '#112233'), 'Color update must be normalized and owner scoped.');
        if (in_array($mode, ['replacement', 'credentials'], true)) {
            editExpect(($result['synced'] ?? null) === false, 'Failed resync must be reported while keeping settings.');
            $expected = $isIcs ? 'https://127.0.0.1:1/replaced' : 'new-password';
            editExpect(commonExternalCalendarDecryptPassword($calendar->get('password_encrypted')) === $expected, 'Replacement credential not persisted.');
            editExpect($calendar->get('calendar_url') === ($isIcs ? 'ics:' . hash('sha256', $expected) : 'https://127.0.0.1:1/replaced'), 'Replacement URL not persisted safely.');
            if (!$isIcs) { editExpect($calendar->get('username') === 'new-user', 'Username not updated.'); }
        } else {
            editExpect($calendar->get('password_encrypted') === $secret, 'Blank credentials or rejected requests must keep the secret.');
            editExpect($calendar->get('last_sync_at')->format('Y-m-d') === '2026-09-01', 'Display-only edit must not trigger synchronization.');
        }
        echo 'external_calendar_edit_test ' . $mode . ": OK\n";
    } finally {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
    }
});
require dirname(__DIR__) . '/omo/api/calendar/external_calendars.php';
