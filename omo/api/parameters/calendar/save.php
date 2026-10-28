<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

header('Content-Type: application/json; charset=UTF-8');
$respond = static function (int $status, string $key): never {
    http_response_code($status);
    echo json_encode(['status' => $status === 200 ? 'ok' : 'error', 'message' => omoCalendarSettingsT($key)], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
};
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { $respond(405, 'error'); }
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
if (!omoCalendarSettingsCanManage($organizationId)) { $respond(403, 'forbidden'); }
$expected = (string)($_SESSION['omo_calendar_settings_csrf'] ?? '');
if ($expected === '' || !is_string($_POST['csrf'] ?? null) || !hash_equals($expected, $_POST['csrf'])
    || (int)($_POST['organization_id'] ?? 0) !== $organizationId) { $respond(403, 'csrf'); }
$organization = new \dbObject\Organization();
if (!$organization->load($organizationId)) { $respond(404, 'organization'); }
try {
    $organization->setCalendarSettings(['dayStart' => $_POST['dayStart'] ?? null, 'dayEnd' => $_POST['dayEnd'] ?? null]);
} catch (InvalidArgumentException $exception) {
    $respond(422, 'invalid');
}
if (empty($organization->save()['status'])) { $respond(500, 'error'); }
$respond(200, 'saved');
