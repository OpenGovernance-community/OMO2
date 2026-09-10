<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__) . '/documents/nextcloud/shared.php';

use dbObject\Document;
use dbObject\Holon;
use dbObject\Project;

function omoProjectsFolderFail(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    echo '<p class="omo-project-detail__folder-feedback generic-description generic-description--small">'
        . omoApiEscape($message) . '</p>';
    exit;
}

function omoProjectsFolderCanViewDocument(Document $document, int $organizationId): bool
{
    return (int)$document->get('IDorganization') === $organizationId
        && !$document->isArchived()
        && (
            $document->canViewInOrganizationContext($organizationId, (int)$document->get('IDholon'))
            || $document->canViewDirectlyInOrganization($organizationId)
        );
}

function omoProjectsFolderRenderLocalEntry(Document $document, int $projectId): string
{
    $documentId = (int)$document->getId();
    $title = trim((string)$document->get('title'));
    $title = $title !== '' ? $title : ('Document #' . $documentId);
    $type = $document->getDocumentTypeLabel();
    $iconUrl = omoProjectsGetDocumentTypeIconUrl($document);
    $isFolder = $document->isFolder();
    $attributes = $isFolder
        ? ' data-omo-project-detail-folder-toggle data-document-id="' . $documentId . '" data-project-id="' . $projectId . '" aria-expanded="false"'
        : ' data-omo-project-detail-document-link data-document-id="' . $documentId . '"';
    $html = '<div class="omo-project-detail__folder-entry"' . ($isFolder ? ' data-omo-project-detail-folder' : '') . '>';
    $html .= '<button type="button" class="omo-project-detail__folder-entry-link"' . $attributes . '>';
    $html .= '<span class="omo-project-detail__document-icon" aria-hidden="true"><img src="' . omoApiEscape($iconUrl) . '" alt="" class="black-icon" loading="lazy"></span>';
    $html .= '<span class="omo-project-detail__document-copy"><strong>' . omoApiEscape($title) . '</strong><span>' . omoApiEscape($type) . '</span></span>';
    $html .= '</button>';
    if ($isFolder) {
        $html .= '<div class="omo-project-detail__folder-content" data-omo-project-detail-folder-content hidden></div>';
    }
    return $html . '</div>';
}

function omoProjectsFolderRenderRemoteEntry(array $entry, int $folderId, int $projectId): string
{
    $path = Document::normalizeNextcloudFolderPath($entry['path'] ?? '');
    if ($path === '') {
        return '';
    }
    $name = trim((string)($entry['name'] ?? ''));
    $name = $name !== '' ? $name : basename($path);
    $isFolder = !empty($entry['isFolder']);
    $mimeType = trim((string)($entry['mimeType'] ?? ''));
    $attributes = $isFolder
        ? ' data-omo-project-detail-folder-toggle data-document-id="' . $folderId . '" data-project-id="' . $projectId . '" data-remote-path="' . omoApiEscape($path) . '" aria-expanded="false"'
        : ' data-omo-project-detail-remote-document data-folder-id="' . $folderId . '" data-remote-path="' . omoApiEscape($path) . '" data-mime-type="' . omoApiEscape($mimeType) . '"';
    $html = '<div class="omo-project-detail__folder-entry"' . ($isFolder ? ' data-omo-project-detail-folder' : '') . '>';
    $html .= '<button type="button" class="omo-project-detail__folder-entry-link"' . $attributes . '>';
    $html .= '<span class="omo-project-detail__document-icon" aria-hidden="true"><img src="' . ($isFolder ? '/omo/assets/images/documents/folder.png' : '/omo/assets/images/documents/download.png') . '" alt="" class="black-icon" loading="lazy"></span>';
    $html .= '<span class="omo-project-detail__document-copy"><strong>' . omoApiEscape($name) . '</strong><span>' . omoApiEscape($isFolder ? 'Dossier NextCloud' : 'NextCloud') . '</span></span>';
    $html .= '</button>';
    if ($isFolder) {
        $html .= '<div class="omo-project-detail__folder-content" data-omo-project-detail-folder-content hidden></div>';
    }
    return $html . '</div>';
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$projectId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$folderId = isset($_GET['folder_id']) && is_numeric($_GET['folder_id']) ? (int)$_GET['folder_id'] : 0;
$context = omoProjectsResolveContext($organizationId, isset($_GET['cid']) ? (int)$_GET['cid'] : 0);
if (empty($context['status']) || $projectId <= 0 || $folderId <= 0) {
    omoProjectsFolderFail(404, omoProjectsT('projects.error.not_found'));
}

$project = new Project();
if (!$project->load($projectId) || (int)$project->get('IDorganization') !== $organizationId || !omoProjectsCanViewProject($project, $context)) {
    omoProjectsFolderFail(404, omoProjectsT('projects.error.not_found'));
}

$projectHolon = $project->getHolon();
$rootHolon = $context['rootHolon'];
if ($projectHolon instanceof Holon && (!($rootHolon instanceof Holon) || !$projectHolon->isDescendantOf((int)$rootHolon->getId(), true))) {
    omoProjectsFolderFail(404, omoProjectsT('projects.error.not_found'));
}

$folder = new Document();
if (!$folder->load($folderId) || !$folder->isFolder() || !omoProjectsFolderCanViewDocument($folder, $organizationId)) {
    omoProjectsFolderFail(404, omoProjectsT('projects.error.not_found'));
}

$associatedDocumentIds = array_flip(array_map(static fn(array $item): int => (int)($item['id'] ?? 0), omoProjectsGetVisibleDocuments($project, $organizationId, $projectHolon)));
$ancestor = $folder;
$isAssociatedBranch = false;
for ($depth = 0; $depth < 30 && $ancestor instanceof Document; $depth++) {
    if (isset($associatedDocumentIds[(int)$ancestor->getId()])) {
        $isAssociatedBranch = true;
        break;
    }
    $ancestor = $ancestor->getParentDocument();
}
if (!$isAssociatedBranch) {
    omoProjectsFolderFail(403, omoProjectsT('projects.error.not_found'));
}

$html = '<div class="omo-project-detail__folder-children">';
if ($folder->isNextcloudFolder()) {
    $nextcloud = omoDocumentsNextcloudLoadFolder($folderId, $organizationId);
    if (empty($nextcloud['status'])) {
        omoProjectsFolderFail((int)($nextcloud['httpCode'] ?? 502), (string)($nextcloud['text'] ?? omoProjectsT('projects.detail.documents.folder_error')));
    }
    $remotePath = omoDocumentsNextcloudGetRemotePath($nextcloud['folder'], $nextcloud['organization'], $_GET['path'] ?? '', true);
    if ($remotePath === '') {
        omoProjectsFolderFail(400, omoProjectsT('projects.detail.documents.folder_error'));
    }
    $result = $nextcloud['organization']->listNextcloudDocumentsDirectory($remotePath);
    if (empty($result['status'])) {
        omoProjectsFolderFail(502, (string)($result['text'] ?? omoProjectsT('projects.detail.documents.folder_error')));
    }
    foreach ((array)($result['entries'] ?? []) as $entry) {
        $html .= omoProjectsFolderRenderRemoteEntry((array)$entry, $folderId, $projectId);
    }
} else {
    foreach ($folder->getDirectChildren() as $childDocument) {
        if ($childDocument instanceof Document && omoProjectsFolderCanViewDocument($childDocument, $organizationId)) {
            $html .= omoProjectsFolderRenderLocalEntry($childDocument, $projectId);
        }
    }
}

if ($html === '<div class="omo-project-detail__folder-children">') {
    $html .= '<p class="omo-project-detail__folder-feedback generic-description generic-description--small">'
        . omoApiEscape(omoProjectsT('projects.detail.documents.folder_empty')) . '</p>';
}
echo $html . '</div>';
