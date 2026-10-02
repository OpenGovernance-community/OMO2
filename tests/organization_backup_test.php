<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/organization_backup.php';
require_once dirname(__DIR__) . '/omo/api/parameters/security/shared.php';

use dbObject\{ArrayDocument, DbObject, Document, Holon, Organization, OrganizationBackup, OrganizationExport, User, UserOrganization};

class BackupRestoreOrganization extends Organization
{
    public function restoreDocuments(array $records, int $userId, array $holonMap): void
    {
        $documentMap = $projectMap = $parentMap = $warnings = [];
        $stats = ['documents' => 0];
        self::omo1ImportDocuments($this, $records, $userId, [$userId => $userId], $holonMap,
            $documentMap, $projectMap, $parentMap, $stats, $warnings);
    }
}

function backupCheck(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function backupFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $field => $value) { $object->set($field, $value); }
    backupCheck(!empty($object->save()['status']), 'Fixture save failed: ' . $class);
    return $object;
}

$settings = new OrganizationBackup();
$settings->set('enabled', 1);
foreach (['1w' => '2024-02-07', '2w' => '2024-02-14', '1m' => '2024-02-29', '2m' => '2024-03-31',
    '3m' => '2024-04-30', '5m' => '2024-06-30', '8m' => '2024-09-30', '12m' => '2025-01-31'] as $frequency => $due) {
    $settings->set('frequency', $frequency);
    $settings->set('last_sent_at', new DateTimeImmutable('2024-01-31 10:15:00'));
    backupCheck($settings->nextDueAt()->format('Y-m-d H:i:s') === $due . ' 10:15:00', 'Wrong calendar deadline for ' . $frequency);
    backupCheck(!$settings->isDue(new DateTimeImmutable($due . ' 10:14:59')), 'Backup sent before its deadline');
    backupCheck($settings->isDue(new DateTimeImmutable($due . ' 10:15:00')), 'Backup missed its deadline');
}
$settings->set('last_sent_at', null);
backupCheck($settings->isDue(new DateTimeImmutable()), 'First enabled backup should be due immediately');
$settings->set('last_attempt_at', new DateTimeImmutable());
backupCheck(!$settings->isDue(new DateTimeImmutable()), 'An unsuccessful attempt needs a retry delay');
$settings->set('enabled', 0);
backupCheck(!$settings->isDue(new DateTimeImmutable('+2 hours')), 'Disabled backup should never be due');
backupCheck(OrganizationBackup::loadEnabledForCron(0, false) === [], 'Anonymous browser maintenance must not run backups');

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
$sessionBefore = $_SESSION ?? [];
try {
    $nonce = bin2hex(random_bytes(6));
    $user = backupFixture(User::class, ['firstname' => 'Backup', 'email' => 'backup-' . $nonce . '@example.invalid']);
    $organization = backupFixture(Organization::class, ['name' => 'Backup-' . $nonce, 'shortname' => 'backup-' . $nonce]);
    $oid = (int)$organization->getId();
    $uid = (int)$user->getId();
    $root = backupFixture(Holon::class, ['IDorganization' => $oid, 'IDtypeholon' => 1, 'name' => 'Backup root', 'active' => 1, 'visible' => 1]);
    $hidden = backupFixture(Holon::class, ['IDorganization' => $oid, 'IDholon_parent' => $root->getId(),
        'IDtypeholon' => 2, 'name' => 'Hidden role', 'active' => 1, 'visible' => 0]);
    $membership = backupFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $uid,
        'active' => 1, 'email' => 'scoped-' . $nonce . '@example.invalid', 'parameters' => ['isAdmin' => true]]);
    $backup = OrganizationBackup::forOrganization($oid);
    backupCheck(!$backup->get('enabled') && $backup->get('frequency') === '1m', 'Backups must start disabled');
    $backup->set('enabled', 1);
    backupCheck(!empty($backup->save()['status']), 'New backup settings must save without a supplementary email');
    $backup->load($backup->getId(), true);
    backupCheck((string)$backup->get('email') === '' && $backup->getRecipients() === [$membership->get('email')],
        'An empty supplementary email must persist and send only to active administrators');
    $backup->set('email', 'extra-' . $nonce . '@example.invalid');
    backupCheck(!empty($backup->save()['status']), 'Save backup settings');
    $recipients = $backup->getRecipients();
    backupCheck(count($recipients) === 2 && in_array($membership->get('email'), $recipients, true), 'Active scoped administrator and extra email must receive backup');
    $backup->set('email', strtoupper((string)$membership->get('email')));
    backupCheck(count($backup->getRecipients()) === 1, 'Backup recipient addresses must be deduplicated');
    $backup->set('email', '   ');
    backupCheck(!empty($backup->save()['status']), 'An existing supplementary email must be removable');
    $backup->load($backup->getId(), true);
    backupCheck((string)$backup->get('email') === '' && $backup->getRecipients() === [$membership->get('email')],
        'Clearing the supplementary email must retain administrator recipients');
    $backup->set('email', 'extra-' . $nonce . '@example.invalid');
    $backup->save();
    $ids = array_map(static fn ($entry) => (int)$entry->get('IDorganization'), OrganizationBackup::loadEnabledForCron($uid, false));
    backupCheck($ids === [$oid], 'Browser maintenance must use only active user organizations');
    backupCheck(OrganizationBackup::loadEnabledForCron($uid + 1000000, false) === [], 'Unrelated user must not trigger organization backups');
    $olderConfig = OrganizationBackup::forOrganization($oid);
    $backup->recordAttempt(new DateTimeImmutable('2000-01-01'), true);
    $olderConfig->set('frequency', '2w');
    $olderConfig->save();
    $reloaded = new OrganizationBackup();
    $reloaded->load($backup->getId(), true);
    backupCheck($reloaded->get('last_sent_at')->format('Y-m-d') === '2000-01-01', 'Editing settings must preserve concurrent delivery state');

    $types = [Document::TYPE_HTML, Document::TYPE_EXTERNAL_LINK, Document::TYPE_FOLDER, Document::TYPE_UPLOADED_FILE,
        Document::TYPE_NEXTCLOUD_FOLDER, Document::TYPE_ETHERPAD, Document::TYPE_ETHERCALC,
        Document::TYPE_COLLABORA_DOCUMENT, Document::TYPE_COLLABORA_SPREADSHEET, Document::TYPE_COLLABORA_PRESENTATION,
        Document::TYPE_COLLABORA_DRAWING, Document::TYPE_WHITEBOARD];
    foreach ($types as $type) {
        backupFixture(Document::class, ['IDorganization' => $oid, 'IDholon' => $root->getId(), 'IDuser' => $uid,
            'title' => $type, 'documenttype' => $type, 'content' => 'CONTENTS-' . $type, 'active' => 1,
            'storedfilepath' => 'relative/file.odt', 'storedfilename' => 'file.odt', 'storedfilemime' => 'application/test',
            'storedfilesize' => 1234, 'etherpadpadid' => 'pad-reference', 'ethercalcroomid' => 'calc-reference',
            'spacedeckspaceid' => 'space-reference', 'nextcloudfolderpath' => 'remote-folder', 'nextcloudfolderfileid' => '42']);
    }
    // Export as a worker without a logged-in user, then as a member: both must be complete.
    unset($_SESSION['currentUser']);
    $payload = OrganizationExport::buildBackup($organization);
    backupCheck($payload['scope']['holonCount'] === 2, 'Hidden active structure must be included in backups');
    $records = $payload['modules']['documents']['records'];
    backupCheck(count($records) === count($types), 'All document types must be included in a backup');
    foreach ($records as $record) {
        backupCheck($record['fileReference']['etherpadpadid'] === 'pad-reference', 'External document reference missing');
        backupCheck($record['fileReference']['storedfilesize'] === 1234, 'File metadata missing');
        backupCheck($record['content'] === ($record['documentType'] === Document::TYPE_HTML ? 'CONTENTS-html' : ''), 'External document contents must not be exported');
    }
    backupCheck(!isset($payload['organization']['parameters']) && !$payload['source']['serverConfigurationIncluded'], 'Server configuration must be excluded');
    $normal = OrganizationExport::build($organization, array_fill_keys(OrganizationExport::MODULES, true));
    backupCheck(count($normal['modules']['documents']['records']) === 3, 'Manual export eligibility must stay unchanged');
    $restored = backupFixture(BackupRestoreOrganization::class, ['name' => 'Backup restore fixture']);
    $restoredRoot = backupFixture(Holon::class, ['IDorganization' => $restored->getId(), 'IDtypeholon' => 1,
        'name' => 'Restore root', 'active' => 1, 'visible' => 1]);
    $restored->restoreDocuments($records, $uid, [(int)$root->getId() => (int)$restoredRoot->getId()]);
    $restoredDocuments = new ArrayDocument();
    $restoredDocuments->load(['where' => [['field' => 'IDorganization', 'value' => $restored->getId()]]]);
    backupCheck(count($restoredDocuments) === count($types), 'All document references must be restored');
    foreach ($restoredDocuments as $document) {
        backupCheck($document->get('documenttype') === $document->get('title'), 'Restored document type changed');
        backupCheck($document->get('etherpadpadid') === 'pad-reference' && $document->get('storedfilepath') === 'relative/file.odt', 'Restored file references missing');
        backupCheck((int)$document->get('storedfilesize') === 1234, 'Restored file size missing');
    }
    $_SESSION['currentUser'] = $uid;
    $_SESSION['currentOrganization'] = $oid;
    backupCheck(!omoSecurityCanManage($oid), 'Security settings must require organization admin mode');
    $_SESSION['isAdminByOrganization'][$oid] = true;
    backupCheck(omoSecurityCanManage($oid) && $backup->canEdit(), 'An active administrator in admin mode can configure backups');
    backupCheck(!omoSecurityCanManage((int)$restored->getId()), 'Administration rights must not cross organizations');

    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
    $_SERVER['REQUEST_URI'] = '/omo/api/parameters/security/index.php';
    ob_start();
    include dirname(__DIR__) . '/omo/api/parameters/security/index.php';
    $screen = ob_get_clean();
    backupCheck(str_contains($screen, 'data-omo-security') && str_contains($screen, "name='enabled'")
        && str_contains($screen, "name='email'") && str_contains($screen, "name='frequency'"), 'Security form must render dbObject fields');
    backupCheck(!str_contains($screen, '/common/assets/admin-edit-form.js'), 'Custom backup form must not bind the generic submit handler too');
    backupCheck(str_contains($screen, 'data-omo-security-backup-now'), 'Immediate backup action must be available');
    backupCheck(preg_match('/<input\b[^>]*\bname=[\'\"]email[\'\"][^>]*>/i', $screen, $emailInput) === 1
        && !preg_match('/\brequired\b/i', $emailInput[0]), 'The supplementary email input must remain optional');

    // Mail delivery is opt-in and confined to the local Mailpit service.
    if (in_array('--mailpit', $argv, true)) {
        backupCheck(($GLOBALS['mailHost'] ?? '') === 'mailpit' && (int)$GLOBALS['mailPort'] === 1025 && !$GLOBALS['mailAuth'], 'Mailpit-only test required');
        $GLOBALS['mailUser'] = '';
        backupCheck(omoProcessOrganizationBackups('runtime_endpoint') === 1, 'First due backup should reach Mailpit');
        backupCheck(omoProcessOrganizationBackups('runtime_endpoint') === 0, 'Repeated cron must not send duplicate backup');
        $mailpitGet = static function (string $path): string {
            $curl = curl_init('http://mailpit:8025/api/v1/' . $path);
            curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_PROXY => '']);
            $body = curl_exec($curl);
            backupCheck(is_string($body) && curl_getinfo($curl, CURLINFO_RESPONSE_CODE) === 200, 'Mailpit API unavailable');
            return $body;
        };
        $messages = json_decode($mailpitGet('search?query=' . rawurlencode('subject:Backup-' . $nonce)), true, 512, JSON_THROW_ON_ERROR);
        backupCheck(count($messages['messages']) === 1, 'Exactly one backup message must be delivered');
        $messageId = $messages['messages'][0]['ID'];
        $detail = json_decode($mailpitGet('message/' . $messageId), true, 512, JSON_THROW_ON_ERROR);
        backupCheck(count($detail['To']) === 2, 'Both the administrator and extra recipient must receive the attachment');
        $attachment = $detail['Attachments'][0] ?? [];
        backupCheck(str_ends_with($attachment['FileName'] ?? '', '.json') && str_starts_with($attachment['ContentType'] ?? '', 'application/json'), 'Backup must be a JSON attachment');
        $received = json_decode($mailpitGet('message/' . $messageId . '/part/' . $attachment['PartID']), true, 512, JSON_THROW_ON_ERROR);
        backupCheck($received['modules']['documents']['records'] === $records && $received['holons'] === $payload['holons'], 'Mailed JSON must preserve the full exported structure and document references');
        $reloaded->load($backup->getId(), true);
        backupCheck($reloaded->get('last_sent_at') instanceof DateTimeInterface, 'Successful delivery timestamp missing');
        $lastSuccess = $reloaded->get('last_sent_at');
        $reloaded->recordAttempt(new DateTimeImmutable('-1 year'), true);
        $reloaded->recordAttempt(new DateTimeImmutable('-2 hours'));
        $GLOBALS['mailPort'] = 9;
        $failed = false;
        try { omoProcessOrganizationBackups('runtime_endpoint'); } catch (RuntimeException $exception) { $failed = true; }
        $GLOBALS['mailPort'] = 1025;
        backupCheck($failed, 'Failed mail must surface in cron logs');
        $reloaded->load($backup->getId(), true);
        backupCheck($reloaded->get('last_sent_at') < $lastSuccess && !$reloaded->isDue(new DateTimeImmutable()), 'Failed delivery must keep previous success and delay retry');
        $reloaded->set('enabled', 0);
        $reloaded->save();
        omoSendOrganizationBackup($reloaded);
        $reloaded->load($backup->getId(), true);
        backupCheck(!$reloaded->get('enabled') && $reloaded->get('last_sent_at') >= $lastSuccess,
            'Manual backup must bypass the automatic schedule and retry delay without enabling automatic backups');
        $messages = json_decode($mailpitGet('search?query=' . rawurlencode('subject:Backup-' . $nonce)), true, 512, JSON_THROW_ON_ERROR);
        backupCheck(count($messages['messages']) === 2, 'Immediate backup must deliver a second JSON email');
    }
} finally {
    $_SESSION = $sessionBefore;
    $pdo->rollBack();
}
echo "organization_backup_test: OK\n";
