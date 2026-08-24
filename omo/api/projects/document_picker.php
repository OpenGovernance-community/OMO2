<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayDocument;
use dbObject\Holon;
use dbObject\Project;

header('Content-Type: application/json; charset=UTF-8');

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$projectId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$context = omoProjectsResolveContext($organizationId, isset($_GET['cid']) ? (int)$_GET['cid'] : 0);

$respond = static function (bool $success, array $payload = [], int $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(array_merge(['success' => $success], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (empty($context['status']) || $projectId <= 0) {
    $respond(false, ['message' => omoProjectsT('projects.error.not_found')], empty($context['status']) ? 403 : 404);
}

$project = new Project();
if (
    !$project->load($projectId)
    || (int)$project->get('IDorganization') !== $organizationId
    || (int)$project->get('active') !== 1
) {
    $respond(false, ['message' => omoProjectsT('projects.error.not_found')], 404);
}

$projectHolon = $project->getHolon();
$rootHolon = $context['rootHolon'] ?? null;
if (
    !($projectHolon instanceof Holon)
    || !($rootHolon instanceof Holon)
    || !$projectHolon->isDescendantOf((int)$rootHolon->getId(), true)
    || !$projectHolon->canViewDetail()
) {
    $respond(false, ['message' => omoProjectsT('projects.error.not_found')], 404);
}

$currentUserId = (int)commonGetCurrentUserId();
if (!omoProjectsCanCreateDocument($project, $currentUserId)) {
    $respond(false, ['message' => omoProjectsT('projects.error.forbidden')], 403);
}

$attachedDocumentIds = [];
foreach ($project->getDocuments() as $projectDocument) {
    $documentId = $projectDocument instanceof \dbObject\ProjectDocument
        ? (int)$projectDocument->get('IDdocument')
        : 0;
    if ($documentId > 0) {
        $attachedDocumentIds[$documentId] = true;
    }
}

$documents = new ArrayDocument();
$documents->loadVisibleForOrganization($organizationId);
$payload = [];
foreach ($documents as $document) {
    if (
        !($document instanceof \dbObject\Document)
        || !$document->canBeEmbedded()
        || (int)$document->getId() <= 0
        || isset($attachedDocumentIds[(int)$document->getId()])
    ) {
        continue;
    }

    $payload[] = [
        'id' => (int)$document->getId(),
        'contextHolonId' => (int)$document->get('IDholon'),
        'title' => trim((string)$document->get('title')),
        'description' => trim((string)$document->get('description')),
        'contextLabel' => trim((string)$document->getOrganizationContextLabel()),
    ];
}

usort($payload, static function (array $left, array $right): int {
    return strnatcasecmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
});

$respond(true, [
    'projectId' => $projectId,
    'projectHolonId' => (int)$projectHolon->getId(),
    'canCreate' => true,
    'createUrl' => '/omo/api/documents/create.php?oid=' . rawurlencode((string)$organizationId)
        . '&cid=' . rawurlencode((string)(int)$projectHolon->getId())
        . '&project_id=' . rawurlencode((string)$projectId)
        . '&editor_host=project_picker',
    'documents' => $payload,
    'scopeLabels' => [
        'local' => omoProjectsT('projects.scope.contextual'),
        'children' => omoProjectsT('projects.scope.children'),
        'descendants' => omoProjectsT('projects.scope.descendants'),
    ],
]);
