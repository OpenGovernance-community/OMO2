<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

header('Content-Type: application/json; charset=UTF-8');
$respond = static function (int $status, string $key, array $extra = []): never {
    http_response_code($status);
    echo json_encode(array_merge(['status' => $status === 200 ? 'ok' : 'error', 'message' => omoSecurityT($key)], $extra), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
};
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { $respond(405, 'error'); }
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
if (!omoSecurityCanManage($organizationId)) { $respond(403, 'forbidden'); }
$expected = (string)($_SESSION['omo_security_csrf'] ?? '');
if ($expected === '' || !is_string($_POST['csrf'] ?? null) || !hash_equals($expected, $_POST['csrf'])
    || (int)($_POST['organization_id'] ?? 0) !== $organizationId) { $respond(403, 'csrf'); }
$organization = new \dbObject\Organization();
if (!$organization->load($organizationId)) { $respond(404, 'organization'); }
$email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : null;
$frequency = $_POST['frequency'] ?? null;
if (!in_array($_POST['enabled'] ?? null, ['0', '1'], true) || $email === null || strlen($email) > 254
    || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
    || !in_array($frequency, \dbObject\OrganizationBackup::FREQUENCIES, true)) { $respond(422, 'invalid'); }
$backup = \dbObject\OrganizationBackup::forOrganization($organizationId);
$backup->set('enabled', (int)$_POST['enabled']);
$backup->set('email', $email);
$backup->set('frequency', $frequency);
if (($_POST['backup_now'] ?? '') === '1') {
    require_once dirname(__DIR__, 4) . '/common/organization_backup.php';
    require_once dirname(__DIR__, 4) . '/common/omo_maintenance_lock.php';
    try {
        $result = omoRunMaintenanceLocked(static function () use ($backup): array {
            if (empty($backup->save()['status'])) { throw new RuntimeException('Unable to save backup settings.'); }
            omoSendOrganizationBackup($backup);
            return ['sent' => true];
        }, 'organization_backup_manual', true);
        if (isset($result['skipped'])) { $respond(409, 'busy'); }
    } catch (Throwable $exception) {
        error_log('OMO manual organization backup failed for ' . $organizationId . ': ' . $exception->getMessage());
        $respond(500, 'send_error');
    }
    $respond(200, 'sent', [
        'lastSentLabel' => omoSecurityT('last', ['date' => $backup->get('last_sent_at')->format('d.m.Y H:i')]),
        'nextDueLabel' => $backup->get('enabled') ? omoSecurityT('next', ['date' => $backup->nextDueAt()->format('d.m.Y H:i')]) : '',
    ]);
}
$result = $backup->save();
if (empty($result['status'])) { $respond(500, 'error'); }
$next = $backup->nextDueAt();
$respond(200, 'saved', [
    'nextDueLabel' => $backup->get('enabled')
        ? ($next ? omoSecurityT('next', ['date' => $next->format('d.m.Y H:i')]) : omoSecurityT('pending'))
        : '',
]);
