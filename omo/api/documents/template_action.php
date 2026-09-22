<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Document;
use dbObject\Project;
use dbObject\ProjectDocument;

header('Content-Type: application/json; charset=UTF-8');

$payload = json_decode(file_get_contents('php://input'), true);
$payload = is_array($payload) ? $payload : [];
$action = trim(mb_strtolower((string)($payload['action'] ?? $_POST['action'] ?? ''), 'UTF-8'));
$documentId = (int)($payload['id'] ?? $_POST['id'] ?? 0);
$organizationId = (int)($payload['oid'] ?? $_POST['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$holonId = max(0, (int)($payload['cid'] ?? $_POST['cid'] ?? 0));
$projectId = max(0, (int)($payload['project_id'] ?? $_POST['project_id'] ?? 0));
$userId = (int)commonGetCurrentUserId();

$error = static function (string $message, int $status = 422): void {
    http_response_code($status);
    echo json_encode(['status' => false, 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if ($userId <= 0 || $documentId <= 0 || !in_array($action, ['set_template', 'duplicate'], true)) {
    $error('Demande invalide.', 400);
}

$template = new Document();
if (!$template->load($documentId) || (int)$template->get('IDorganization') !== $organizationId) {
    $error('Document introuvable.', 404);
}

if (!commonCurrentUserHasOrganizationAccess($organizationId)) {
    $error('Accès refusé.', 403);
}

if ($action === 'set_template') {
    $isTemplate = !empty($payload['is_template']) || !empty($_POST['is_template']);
    $result = $template->updateDocumentTemplateState($organizationId, $userId, $isTemplate);
    if (!is_array($result) || empty($result['status'])) {
        $error(trim((string)($result['text'] ?? 'Impossible de modifier le modèle.')));
    }

    echo json_encode([
        'status' => true,
        'isTemplate' => $template->isDocumentTemplate(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!Document::canCreateInOrganizationContext($organizationId, $holonId > 0 ? $holonId : null, $userId, 0, false)) {
    $error('Accès refusé.', 403);
}

if ($projectId > 0) {
    $project = new Project();
    $projectHolon = null;
    if (
        !$project->load($projectId)
        || (int)$project->get('IDorganization') !== $organizationId
        || (int)$project->get('active') !== 1
        || !(($projectHolon = $project->getHolon()) instanceof \dbObject\Holon)
        || $holonId !== (int)$projectHolon->getId()
        || !$projectHolon->isAllowed('CAN_CREATE_DOCUMENT', true, $userId)
    ) {
        $error('Accès refusé.', 403);
    }
}

$duplicate = new Document();
$result = $duplicate->createFromDocumentTemplateInOrganizationContext(
    $template,
    $organizationId,
    $holonId > 0 ? $holonId : null,
    $userId
);
if (!is_array($result) || empty($result['status'])) {
    $error(trim((string)($result['text'] ?? 'Impossible de créer le document depuis ce modèle.')));
}

if ($projectId > 0) {
    $projectDocument = new ProjectDocument();
    if (!$projectDocument->load([['IDproject', $projectId], ['IDdocument', (int)$duplicate->getId()]])) {
        $projectDocument->set('IDproject', $projectId);
        $projectDocument->set('IDdocument', (int)$duplicate->getId());
        $projectDocumentResult = $projectDocument->save();
        if (!is_array($projectDocumentResult) || empty($projectDocumentResult['status'])) {
            $duplicate->delete();
            $error('Impossible d associer le document au projet.');
        }
    }
}

echo json_encode([
    'status' => true,
    'id' => (int)$duplicate->getId(),
    'projectId' => $projectId,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
