<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Holon;
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
if (count($documents) === 0) {
    echo '<div class="generic-soft-panel omo-project-detail__documents-empty">'
        . '<p class="omo-project-detail__muted generic-description generic-description--small">' . omoApiEscape(omoProjectsT('projects.detail.documents.empty')) . '</p>'
        . '<button type="button" class="generic-action-button generic-action-button--secondary" data-omo-project-detail-add-document>'
        . omoApiEscape(omoProjectsT('projects.detail.documents.add'))
        . '</button></div>';
    exit;
}
?>
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
