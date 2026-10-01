<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

header('Content-Type: application/json; charset=UTF-8');
$respond = static function (int $statusCode, array $payload): void {
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    $respond(405, ['status' => false, 'message' => omoDecisionParamsT('decisions.params.error.method')]);
}
$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($_POST['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
if ($currentUserId <= 0) {
    $respond(403, ['status' => false, 'message' => omoDecisionParamsT('decisions.params.error.login')]);
}
$organization = new \dbObject\Organization();
if ($organizationId <= 0 || !$organization->load($organizationId)) {
    $respond(404, ['status' => false, 'message' => omoDecisionParamsT('decisions.params.error.organization')]);
}
if (!omoDecisionParamsGetApplicationLink($organizationId, false)) {
    $respond(404, ['status' => false, 'message' => omoDecisionParamsT('decisions.params.error.unavailable')]);
}
if (!omoDecisionParamsCanManage($organizationId, $currentUserId)) {
    $respond(403, ['status' => false, 'message' => omoDecisionParamsT('decisions.params.error.forbidden')]);
}
$result = omoDecisionParamsStoreConfig($organization, [
    'methods' => is_array($_POST['methods'] ?? null) ? $_POST['methods'] : [],
    'governance' => is_array($_POST['governance'] ?? null) ? $_POST['governance'] : [],
]);
if (empty($result['status'])) {
    $respond(422, ['status' => false, 'message' => (string)($result['text'] ?? omoDecisionParamsT('decisions.params.error.save_failed'))]);
}
$respond(200, ['status' => true, 'message' => omoDecisionParamsT('decisions.params.feedback.saved')]);
