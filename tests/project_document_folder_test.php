<?php
declare(strict_types=1);

function assertProjectDocumentFolder(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$documentsSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/projects/documents.php');
$folderSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/projects/document_folder.php');
$scriptSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/projects/projects.js');
$documentSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/document.class.php');
$nextcloudSharedSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/nextcloud/shared.php');

assertProjectDocumentFolder(
    str_contains($documentsSource, 'data-omo-project-detail-folder-toggle')
        && str_contains($documentsSource, 'data-omo-project-detail-folder-content'),
    'Project folder documents must be expandable directly in the document list.'
);
assertProjectDocumentFolder(
    str_contains($folderSource, 'getDirectChildren()')
        && str_contains($folderSource, 'listNextcloudDocumentsDirectory(')
        && str_contains($folderSource, 'omoProjectsFolderRenderRemoteEntry'),
    'Project folder loading must support both local and NextCloud folders.'
);
assertProjectDocumentFolder(
    str_contains($scriptSource, 'function loadProjectDocumentFolder(')
        && str_contains($scriptSource, 'document_folder.php')
        && str_contains($scriptSource, 'data-omo-project-detail-remote-document'),
    'Project documents must load expanded folder contents and open remote files in the nested drawer.'
);
assertProjectDocumentFolder(
    str_contains($documentSource, "trim((string)(\$currentLocation['fileId'] ?? '')) === ''"),
    'Existing NextCloud folders must remain readable when their WebDAV response does not expose a file identifier.'
);
assertProjectDocumentFolder(
    str_contains($nextcloudSharedSource, '$fallbackRemotePath = $folder->buildNextcloudFolderRemotePath($organization);'),
    'NextCloud folder reads must retain a path-based fallback when the optional location lookup fails.'
);
assertProjectDocumentFolder(
    !str_contains($nextcloudSharedSource, '$resolvedPath === \'\' || $resolvedFileId === \'\'')
        && str_contains($nextcloudSharedSource, 'if ($resolvedFileId !== \'\')'),
    'A valid NextCloud folder path must not require an optional file identifier.'
);

echo "project_document_folder_test: OK\n";
