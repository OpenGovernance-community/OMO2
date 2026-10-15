<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__) . '/patreon.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); header('Allow: POST'); echo '{"status":false}'; exit; }
$input = json_decode(file_get_contents('php://input', false, null, 0, 4096), true);
$organizationId = (int)($input['organizationId'] ?? 0);
$userId = (int)commonGetCurrentUserId();
if (!is_string($input['csrf'] ?? null) || empty($_SESSION['patreon_organization_csrf'])
    || !hash_equals($_SESSION['patreon_organization_csrf'], $input['csrf'])
    || !patreonUserCanManageOrganization($userId, $organizationId)) {
    http_response_code(403); echo '{"status":false}'; exit;
}
try {
    $connection = \dbObject\OrganizationPatreon::findByOrganizationId($organizationId);
    switch ($input['action'] ?? '') {
        case 'sync':
            if (!$connection || !$connection->isConnected()) throw new DomainException('No connection.');
            patreonSyncConnection($connection);
            break;
        case 'disconnect':
            \dbObject\UserPatreon::withConnectionLock(static function () use ($connection) {
                if ($connection && empty($connection->disconnect()['status'])) throw new RuntimeException('Disconnect failed.');
            });
            break;
        case 'status': break;
        default: http_response_code(400); echo '{"status":false}'; exit;
    }
    echo json_encode(['status' => true, 'linked' => $connection && $connection->isConnected(), 'active' => patreonOrganizationCanUseAi($organizationId),
        'account' => $connection && $connection->isConnected() ? ((string)$connection->get('full_name') ?: (string)$connection->get('patreon_user_id')) : '']);
} catch (Throwable $error) {
    error_log('Organization Patreon action failed: ' . get_class($error));
    http_response_code(502); echo '{"status":false}';
}
