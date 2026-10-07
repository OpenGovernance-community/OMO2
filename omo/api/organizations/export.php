<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;
use dbObject\OrganizationExport;

function omoOrganizationExportError($statusCode, $message)
{
    http_response_code((int)$statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'status' => false,
        'message' => (string)$message,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentUserId = (int)commonGetCurrentUserId();
if ($organizationId <= 0 || $currentUserId <= 0) {
    omoOrganizationExportError(401, 'Connexion requise.');
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    omoOrganizationExportError(404, 'Organisation introuvable.');
}
$canExportOrganization = commonCurrentUserCanUseAdminMode($organizationId)
    || commonCurrentUserIsSiteAdminModeEnabled();
if (!$canExportOrganization) {
    omoOrganizationExportError(403, 'Acces reserve aux administrateurs de cette organisation.');
}

$requestedModules = array_key_exists('modules', $_GET) ? $_GET['modules'] : OrganizationExport::MODULES;
if (!is_array($requestedModules)) {
    $requestedModules = [$requestedModules];
}
$selectedModules = [];
foreach (OrganizationExport::MODULES as $module) {
    $selectedModules[$module] = in_array($module, $requestedModules, true);
}
$selectedModules['structure'] = true;
if ($selectedModules['tasks']) {
    $selectedModules['projects'] = true;
}
if ($selectedModules['pv']) {
    $selectedModules['calendar'] = true;
}

try {
    $payload = OrganizationExport::build($organization, $selectedModules);
    $name = trim((string)$organization->get('shortname')) ?: trim((string)$organization->get('name'));
    $name = strtolower((string)@iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name));
    $name = trim((string)preg_replace('/[^a-z0-9]+/', '-', $name), '-');
    $name = $name !== '' ? $name : 'organisation';
    $filename = 'omo2-export-' . $name . '-' . date('Ymd-His') . '.zip';
    $temporary = tempnam(sys_get_temp_dir(), 'omo-export-');
    if ($temporary === false) { throw new \RuntimeException('Impossible de creer le fichier temporaire.'); }
    try {
        \dbObject\OrganizationArchive::create($payload, $temporary);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($temporary));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        readfile($temporary);
    } finally { @unlink($temporary); }
} catch (\Throwable $exception) {
    omoOrganizationExportError(500, $exception->getMessage());
}
