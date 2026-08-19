<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/omo_fake_cron.php';

header('Content-Type: application/json; charset=UTF-8');
@set_time_limit(0);

$currentUserId = (int)commonGetCurrentUserId();
if ($currentUserId <= 0) {
    http_response_code(401);
    echo json_encode(array('status' => false, 'message' => 'Connexion requise.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_FILES['omo1_export_file']) || !is_array($_FILES['omo1_export_file'])) {
    http_response_code(400);
    echo json_encode(array('status' => false, 'message' => 'Aucun fichier JSON n a ete transmis.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$upload = $_FILES['omo1_export_file'];
if ((int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(array('status' => false, 'message' => 'Le fichier n a pas pu etre televerse.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$rawPayload = @file_get_contents((string)($upload['tmp_name'] ?? ''));
if (!is_string($rawPayload) || trim($rawPayload) === '') {
    http_response_code(400);
    echo json_encode(array('status' => false, 'message' => 'Le fichier d import est vide.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$payload = json_decode($rawPayload, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(array('status' => false, 'message' => 'Le fichier d import n est pas un JSON valide.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$availableModules = array('structure', 'rules', 'members', 'documents', 'projects', 'tasks', 'checklists', 'indicators', 'calendar', 'pv');
$requestedModules = array();
$postedModules = isset($_POST['modules']) && is_array($_POST['modules']) ? $_POST['modules'] : array();
foreach ($availableModules as $module) {
    $requestedModules[$module] = in_array($module, $postedModules, true);
}

$organizationName = trim((string)($_POST['organization_name'] ?? ''));
$importOptions = array(
    'sendMemberInvitationEmails' => !empty($_POST['send_member_invitations']),
);
$templateCalibration = array(
    'templateRootHolonId' => (int)($_POST['organization_template_id'] ?? 0),
    'mappings' => array(),
    'excludedTemplateIds' => array(),
    'propertyMappings' => array(),
    'templatePropertyMappings' => array(),
);
$rawTemplateMappings = trim((string)($_POST['template_mappings'] ?? ''));
if ($rawTemplateMappings !== '') {
    $postedTemplateMappings = json_decode($rawTemplateMappings, true);
    if (!is_array($postedTemplateMappings)) {
        http_response_code(400);
        echo json_encode(array('status' => false, 'message' => 'Les correspondances de templates ne sont pas valides.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    foreach ($postedTemplateMappings as $sourceTemplateId => $targetTemplateId) {
        $sourceTemplateId = (int)$sourceTemplateId;
        $targetTemplateId = (int)$targetTemplateId;
        if ($sourceTemplateId > 0 && $targetTemplateId > 0) {
            $templateCalibration['mappings'][$sourceTemplateId] = $targetTemplateId;
        } elseif ($sourceTemplateId > 0 && $targetTemplateId === -1) {
            $templateCalibration['excludedTemplateIds'][$sourceTemplateId] = true;
        }
    }
}
$rawPropertyMappings = trim((string)($_POST['property_mappings'] ?? ''));
if ($rawPropertyMappings !== '') {
    $postedPropertyMappings = json_decode($rawPropertyMappings, true);
    if (!is_array($postedPropertyMappings)) {
        http_response_code(400);
        echo json_encode(array('status' => false, 'message' => 'Les correspondances de proprietes ne sont pas valides.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    foreach ($postedPropertyMappings as $sourceId => $targetOrMappings) {
        if (is_array($targetOrMappings)) {
            $sourceTemplateId = (int)$sourceId;
            if ($sourceTemplateId <= 0) {
                continue;
            }
            foreach ($targetOrMappings as $sourcePropertyId => $targetPropertyId) {
                $sourcePropertyId = (int)$sourcePropertyId;
                $targetPropertyId = (int)$targetPropertyId;
                if ($sourcePropertyId > 0 && $targetPropertyId >= 0) {
                    $templateCalibration['templatePropertyMappings'][$sourceTemplateId][$sourcePropertyId] = $targetPropertyId;
                }
            }
            continue;
        }

        $sourcePropertyId = (int)$sourceId;
        $targetPropertyId = (int)$targetOrMappings;
        if ($sourcePropertyId > 0 && $targetPropertyId > 0) {
            $templateCalibration['propertyMappings'][$sourcePropertyId] = $targetPropertyId;
        }
    }
}
if (
    (count($templateCalibration['mappings']) > 0 || count($templateCalibration['excludedTemplateIds']) > 0)
    && $templateCalibration['templateRootHolonId'] <= 0
) {
    http_response_code(400);
    echo json_encode(array('status' => false, 'message' => 'Selectionnez le modele d organisation utilise pour les correspondances.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
$result = \dbObject\Organization::importOmo1ExportAsNewOrganization(
    $payload,
    $requestedModules,
    $currentUserId,
    $organizationName,
    $templateCalibration,
    $importOptions
);

if (empty($result['status']) || !($result['organization'] ?? null) instanceof \dbObject\Organization) {
    http_response_code(422);
    $journalReference = trim((string)($result['importJournalReference'] ?? ''));
    $message = (string)($result['message'] ?? 'L import de la nouvelle organisation a echoue.');
    if ($journalReference !== '') {
        $message .= ' Reference du journal : ' . $journalReference . '.';
    }
    echo json_encode(array(
        'status' => false,
        'message' => $message,
        'importJournalReference' => $journalReference,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$organization = $result['organization'];
$maintenance = omo_run_fake_cron_maintenance(50);
echo json_encode(array(
    'status' => true,
    'message' => (string)($result['message'] ?? 'La nouvelle organisation a ete importee.'),
    'organizationId' => (int)$organization->getId(),
    'organizationName' => (string)$organization->get('name'),
    'redirect' => commonBuildOrganizationHomeUrl(
        (int)$organization->getId(),
        trim((string)$organization->get('shortname')),
        commonGetRootHost()
    ),
    'stats' => $result['stats'] ?? array(),
    'warnings' => $result['warnings'] ?? array(),
    'maintenance' => $maintenance,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
