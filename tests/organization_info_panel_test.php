<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{DbObject, Organization, User, UserOrganization};

function organizationInfoCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function organizationInfoFixture(string $class, array $values): DbObject
{
    $object = new $class();
    foreach ($values as $field => $value) $object->set($field, $value);
    $result = $object->save();
    organizationInfoCheck(!empty($result['status']), 'Fixture save failed: ' . json_encode($result));
    return $object;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
// getOrg.php exits after rendering an organization without a structural root.
// Validate its complete response and roll back fixtures even on that exit path.
ob_start();
register_shutdown_function(static function () use ($pdo): void {
    $html = (string)ob_get_clean();
    if ($pdo->inTransaction()) $pdo->rollBack();
    $error = error_get_last();
    organizationInfoCheck(!($error && in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR], true)), 'Endpoint failed: ' . json_encode($error));
    organizationInfoCheck(http_response_code() === false || http_response_code() === 200, 'Endpoint must accept the organization member');
    organizationInfoCheck(str_contains($html, 'data-omo-org-info-panel="1"'), 'Organization panel must render');
    organizationInfoCheck(str_contains($html, 'Identifiant URL'), 'URL identifier label must render');
    organizationInfoCheck(str_contains($html, 'panel-test-'), 'URL identifier value must render');
    organizationInfoCheck(str_contains($html, 'panel.example.invalid'), 'Domain after the URL identifier must render');
    organizationInfoCheck(str_contains($html, 'omoRefreshOrganizationInfoPanel') && str_contains($html, '</script>'), 'Response must include the final panel script');
    echo "organization_info_panel_test: OK (getOrg without model or structural root)\n";
});

$nonce = bin2hex(random_bytes(6));
$organization = organizationInfoFixture(Organization::class, [
    'name' => 'Panel test', 'shortname' => 'panel-test-' . $nonce, 'domain' => 'panel.example.invalid',
]);
$user = organizationInfoFixture(User::class, ['firstname' => 'Panel test', 'email' => 'panel-' . $nonce . '@example.invalid', 'active' => 1]);
organizationInfoFixture(UserOrganization::class, [
    'IDorganization' => $organization->getId(), 'IDuser' => $user->getId(), 'active' => 1,
]);
organizationInfoCheck($organization->getEnabledStructuralRootHolon() === null, 'Fixture must have no structural root');
$_SESSION['currentUser'] = $user->getId();
$_SESSION['currentOrganization'] = $organization->getId();
$_GET = $_REQUEST = ['oid' => $organization->getId()];
$_SERVER['REQUEST_URI'] = '/omo/api/getOrg.php?oid=' . $organization->getId();
require dirname(__DIR__) . '/omo/api/getOrg.php';
