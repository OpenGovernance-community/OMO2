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
) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

$isArchivedProject = (int)$project->get('active') !== 1;

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
$currentUserId = (int)commonGetCurrentUserId();
$canCreateDocument = !$isArchivedProject && omoProjectsCanCreateDocument($project, $currentUserId);
$createDocumentUrl = '/omo/api/projects/document_picker.php?oid=' . rawurlencode((string)$organizationId)
    . '&id=' . rawurlencode((string)$projectId);
if ($projectHolon instanceof Holon) {
    $createDocumentUrl .= '&cid=' . rawurlencode((string)(int)$projectHolon->getId());
}
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
        <?php
        $canRemoveDocument = !$isArchivedProject && $canCreateDocument;
        $shouldDeleteDocument = $canRemoveDocument
            && empty($documentItem['visibleInHolon'])
            && (int)($documentItem['otherProjectCount'] ?? 0) === 0
            && !empty($documentItem['canDelete'])
            && !empty($documentItem['canManageLifecycle']);
        $removeLabel = omoProjectsT($shouldDeleteDocument
            ? 'projects.detail.documents.delete'
            : 'projects.detail.documents.detach');
        $removeConfirm = omoProjectsT($shouldDeleteDocument
            ? 'projects.detail.documents.confirm_delete'
            : 'projects.detail.documents.confirm_detach');
        ?>
        <div class="omo-project-detail__document-item">
            <a
                class="omo-project-detail__document-link"
                href="#documents-d<?= (int)$documentItem['id'] ?>"
                data-omo-project-detail-document-link
                data-document-id="<?= (int)$documentItem['id'] ?>"
            >
                <span class="omo-project-detail__document-icon" aria-hidden="true">
                    <img src="<?= omoApiEscape((string)$documentItem['iconUrl']) ?>" alt="" class="black-icon" loading="lazy">
                </span>
                <span class="omo-project-detail__document-copy">
                    <strong><?= omoApiEscape($documentItem['title'] !== '' ? $documentItem['title'] : ('Document #' . (int)$documentItem['id'])) ?></strong>
                    <span><?= omoApiEscape($documentItem['type']) ?><?php if ($documentItem['addedAt'] !== ''): ?> · <?= omoApiEscape(omoProjectsT('projects.detail.documents.added', ['date' => $documentItem['addedAt']])) ?><?php endif; ?></span>
                </span>
            </a>
            <div class="generic-menu omo-project-detail__document-menu" data-omo-project-detail-document-menu>
                <button
                    type="button"
                    class="generic-menu-toggle omo-project-detail__document-menu-toggle"
                    data-omo-project-detail-document-menu-toggle
                    aria-label="<?= omoApiEscape(omoProjectsT('projects.detail.documents.menu')) ?>"
                    aria-expanded="false"
                >&#8230;</button>
                <div class="generic-menu-panel omo-project-detail__document-menu-panel" data-omo-project-detail-document-menu-panel hidden>
                    <a
                        class="generic-menu-item"
                        href="/omo/#documents-d<?= (int)$documentItem['id'] ?>"
                        target="_blank"
                        rel="noopener"
                    ><?= omoApiEscape(omoProjectsT('projects.detail.documents.open_new_window')) ?></a>
                    <?php if ($canRemoveDocument): ?>
                        <button
                            type="button"
                            class="generic-menu-item generic-menu-item--danger"
                            data-omo-project-detail-document-remove
                            data-project-id="<?= (int)$projectId ?>"
                            data-document-id="<?= (int)$documentItem['id'] ?>"
                            data-confirm="<?= omoApiEscape($removeConfirm) ?>"
                        ><?= omoApiEscape($removeLabel) ?></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
