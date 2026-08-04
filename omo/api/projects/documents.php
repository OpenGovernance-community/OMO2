<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Holon;
use dbObject\Document;
use dbObject\Project;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$projectId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$context = omoProjectsResolveContext($organizationId, isset($_GET['cid']) ? (int)$_GET['cid'] : 0);

if (empty($context['status']) || $projectId <= 0) {
    http_response_code(empty($context['status']) ? 403 : 404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

$project = new Project();
if (
    !$project->load($projectId)
    || (int)$project->get('IDorganization') !== $organizationId
    || (int)$project->get('active') !== 1
) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

$projectHolon = $project->getHolon();
$rootHolon = $context['rootHolon'];
if (
    $projectHolon instanceof Holon
    && (
        !($rootHolon instanceof Holon)
        || !$projectHolon->isDescendantOf((int)$rootHolon->getId(), true)
        || !$projectHolon->canViewDetail()
    )
) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

$documents = omoProjectsGetVisibleDocuments($project, $organizationId, $projectHolon);
$canCreateDocument = Document::canCreateInOrganizationContext(
    $organizationId,
    null,
    (int)commonGetCurrentUserId(),
    0,
    true
);
$createDocumentUrl = '/omo/api/documents/create.php?oid=' . rawurlencode((string)$organizationId)
    . '&project_id=' . rawurlencode((string)$projectId)
    . '&editor_host=project';
$createDocumentButton = '<button type="button" class="generic-action-button generic-action-button--main"'
    . ' data-omo-project-detail-add-document'
    . ' data-omo-project-detail-add-document-url="' . omoApiEscape($createDocumentUrl) . '">'
    . omoApiEscape(omoProjectsT('projects.detail.documents.new'))
    . '</button>';
if (count($documents) === 0) {
    echo '<div class="omo-project-detail__documents-empty">'
        . '<h3 class="generic-card-title generic-card-title--medium">' . omoApiEscape(omoProjectsT('projects.detail.documents.empty')) . '</h3>';
    if ($canCreateDocument) {
        echo '<p class="generic-description generic-description--small">' . omoApiEscape(omoProjectsT('projects.detail.documents.empty_hint')) . '</p>'
            . $createDocumentButton;
    }
    echo '</div>';
    exit;
}
?>
<?php if ($canCreateDocument): ?>
    <div class="omo-project-detail__documents-actions">
        <?= $createDocumentButton ?>
    </div>
<?php endif; ?>
<div class="omo-project-detail__documents-list">
    <?php foreach ($documents as $documentItem): ?>
        <a
            class="omo-project-detail__document-item"
            href="#documents-d<?= (int)$documentItem['id'] ?>"
            data-omo-project-detail-document-link
            data-document-id="<?= (int)$documentItem['id'] ?>"
        >
            <span class="omo-project-detail__document-copy">
                <strong><?= omoApiEscape($documentItem['title'] !== '' ? $documentItem['title'] : ('Document #' . (int)$documentItem['id'])) ?></strong>
                <span><?= omoApiEscape($documentItem['type']) ?><?php if ($documentItem['addedAt'] !== ''): ?> · <?= omoApiEscape(omoProjectsT('projects.detail.documents.added', ['date' => $documentItem['addedAt']])) ?><?php endif; ?></span>
            </span>
            <span class="omo-project-detail__document-arrow" aria-hidden="true">&#8250;</span>
        </a>
    <?php endforeach; ?>
</div>
