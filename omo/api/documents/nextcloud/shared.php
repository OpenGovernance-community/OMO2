<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

function omoDocumentsNextcloudLoadFolder(int $folderId, int $organizationId = 0): array
{
    $userId = (int)commonGetCurrentUserId();
    $folder = new \dbObject\Document();
    if ($folderId <= 0 || $userId <= 0 || !$folder->load($folderId) || !$folder->isNextcloudFolder()) {
        return array('status' => false, 'httpCode' => 404, 'text' => 'Dossier NextCloud introuvable.');
    }

    $resolvedOrganizationId = $organizationId > 0 ? $organizationId : (int)$folder->get('IDorganization');
    $holonId = (int)$folder->get('IDholon');
    if (
        $resolvedOrganizationId <= 0
        || $resolvedOrganizationId !== (int)$folder->get('IDorganization')
        || !commonUserHasOrganizationAccess($userId, $resolvedOrganizationId)
        || !$folder->canViewInOrganizationContext($resolvedOrganizationId, $holonId > 0 ? $holonId : null, $userId)
    ) {
        return array('status' => false, 'httpCode' => 403, 'text' => 'Acces refuse.');
    }

    $organization = new \dbObject\Organization();
    if (!$organization->load($resolvedOrganizationId) || !$organization->hasDocumentStorage()) {
        return array('status' => false, 'httpCode' => 503, 'text' => 'Le stockage de documents est indisponible.');
    }

	$location = $folder->resolveRemoteFolderStorageLocation($organization);
	if (empty($location['status'])) {
		$fallbackRemotePath = $folder->buildRemoteFolderStoragePath($organization);
		if ($fallbackRemotePath === '') {
			return array('status' => false, 'httpCode' => 404, 'text' => trim((string)($location['text'] ?? 'Dossier NextCloud introuvable.')));
		}

		return array(
			'status' => true,
			'folder' => $folder,
			'organization' => $organization,
			'organizationId' => $resolvedOrganizationId,
			'holonId' => $holonId,
			'userId' => $userId,
		);
	}
	$resolvedPath = trim((string)($location['relativePath'] ?? ''));
	$resolvedFileId = trim((string)($location['fileId'] ?? ''));
	if ($resolvedPath === '') {
		return array('status' => false, 'httpCode' => 404, 'text' => 'Dossier NextCloud introuvable.');
	}
	if (
		$resolvedPath !== $folder->getNextcloudFolderPath()
		|| ($resolvedFileId !== '' && $resolvedFileId !== $folder->getNextcloudFolderFileId())
	) {
		$folder->set('nextcloudfolderpath', $resolvedPath);
		if ($resolvedFileId !== '') {
			$folder->set('nextcloudfolderfileid', $resolvedFileId);
		}
		$saveResult = $folder->save();
		if (!is_array($saveResult) || empty($saveResult['status'])) {
			return array('status' => false, 'httpCode' => 500, 'text' => 'Impossible de mettre a jour le chemin du dossier NextCloud.');
		}
	}

    return array(
        'status' => true,
        'folder' => $folder,
        'organization' => $organization,
        'organizationId' => $resolvedOrganizationId,
        'holonId' => $holonId,
        'userId' => $userId,
    );
}

function omoDocumentsNextcloudGetRemotePath(\dbObject\Document $folder, \dbObject\Organization $organization, $rawPath, bool $allowFolderRoot = false): string
{
    $requestedPath = \dbObject\Document::normalizeNextcloudFolderPath($rawPath);
    if ($requestedPath === '' && $allowFolderRoot) {
        return $folder->buildRemoteFolderStoragePath($organization);
    }

    return $folder->isRemoteFolderStoragePathAllowed($organization, $requestedPath)
        ? $requestedPath
        : '';
}

function omoDocumentsNextcloudFileMimeType(string $filename, string $mimeType = ''): string
{
    $mimeType = trim($mimeType);
    if ($mimeType !== '') {
        return $mimeType;
    }

    return match (strtolower((string)pathinfo($filename, PATHINFO_EXTENSION))) {
        'pdf' => 'application/pdf',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        default => 'application/octet-stream',
    };
}
