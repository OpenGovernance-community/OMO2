<?php

define('OMO_COLLABORA_WOPI_TOKEN_ACCESS', true);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/collabora.php';

use dbObject\Document;
use dbObject\User;

$documentId = (int)($_GET['id'] ?? 0);
$requestPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$uiTheme = strtolower(trim((string)($_GET['ui_theme'] ?? ''))) === 'dark' ? 'dark' : 'light';
if (preg_match('#/wopi\.php/\d+/theme/(dark|light)(?:/|$)#', $requestPath, $themeMatches)) {
    $uiTheme = $themeMatches[1];
}
$isContentsRequest = isset($_GET['contents']);
$isUserSettingsRequest = false;
$isBrowserSettingsRequest = false;
$isSettingsUploadRequest = false;
if ($documentId <= 0) {
    $pathInfo = trim((string)($_SERVER['PATH_INFO'] ?? ''), '/');
    if (preg_match('/^(\d+)(?:\/|$)/', $pathInfo, $matches)) {
        $documentId = (int)$matches[1];
    }
    if (preg_match('/^\d+\/contents$/', $pathInfo)) {
        $isContentsRequest = true;
    }
}
if ($documentId <= 0) {
    if (preg_match('#/wopi\.php/(\d+)$#', $requestPath, $matches)) {
        $documentId = (int)$matches[1];
    }
}
if (preg_match('#/wopi\.php/\d+(?:/theme/(?:dark|light))?/contents$#', $requestPath)) {
    $isContentsRequest = true;
}
if (preg_match('#/wopi\.php/\d+/settings$#', $requestPath)) {
    $isUserSettingsRequest = true;
}
if (preg_match('#/wopi\.php/\d+/browser-settings$#', $requestPath)) {
    $isBrowserSettingsRequest = true;
}
if (preg_match('#/wopi\.php/\d+(?:/theme/(?:dark|light))?/settings/upload$#', $requestPath)) {
    $isSettingsUploadRequest = true;
}
$accessToken = trim((string)($_GET['access_token'] ?? ''));
if ($accessToken === '' && preg_match('/^Bearer\s+(.+)$/i', (string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''), $matches)) {
    $accessToken = trim((string)$matches[1]);
}

$document = new Document();
if ($documentId <= 0 || !$document->load($documentId) || !$document->isCollaboraDocument() || !$document->hasStoredFile()) {
    http_response_code(404);
    exit;
}

$tokenPayload = omoCollaboraVerifyWopiToken($document, $accessToken);
if (!is_array($tokenPayload)) {
    http_response_code(401);
    exit;
}

$userId = (int)($tokenPayload['userId'] ?? 0);
$organizationId = (int)$document->get('IDorganization');
$holonId = (int)$document->get('IDholon');
if (
    $userId <= 0
    || $organizationId <= 0
    || !commonUserHasOrganizationAccess($userId, $organizationId)
    || !$document->canViewInOrganizationContext($organizationId, $holonId > 0 ? $holonId : null, $userId)
) {
    http_response_code(403);
    exit;
}

$organization = new \dbObject\Organization();
if (!$organization->load($organizationId) || !$organization->hasDocumentStorage()) {
    http_response_code(503);
    exit;
}

$collaboraConfig = omoCollaboraGetConfig($organization);
$canEdit = $document->canEditInOrganizationContext($organizationId, $userId, false);
$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$override = strtoupper((string)($_SERVER['HTTP_X_WOPI_OVERRIDE'] ?? ''));

if ($method === 'GET' && $isUserSettingsRequest) {
    $browserSettingsUrl = omoCollaboraBuildWopiSource($documentId)
        . '/browser-settings?access_token=' . rawurlencode($accessToken)
        . '&ui_theme=' . rawurlencode($uiTheme);

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array(
        'browsersetting' => array(
            array(
                'uri' => $browserSettingsUrl,
                'stamp' => 'omo-collabora-theme-' . $uiTheme . '-1',
            ),
        ),
    ), JSON_UNESCAPED_SLASHES);
    exit;
}

if ($method === 'GET' && $isBrowserSettingsRequest) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array(
        'darkTheme' => $uiTheme === 'dark' ? 'true' : 'false',
        'darkBackgroundForTheme' => array(
            'dark' => 'false',
            'light' => 'false',
        ),
    ), JSON_UNESCAPED_SLASHES);
    exit;
}

if ($method === 'POST' && $isSettingsUploadRequest) {
    header('Content-Type: application/json; charset=UTF-8');
    echo '{}';
    exit;
}

if ($method === 'GET' && $isContentsRequest) {
    $fileResult = $organization->downloadDocumentFileFromStorage((string)$document->get('storedfilepath'));
    if (!is_array($fileResult) || empty($fileResult['status'])) {
        http_response_code(502);
        exit;
    }

    $body = (string)($fileResult['body'] ?? '');
    header('Content-Type: ' . $document->getStoredFileMimeType());
    header('Content-Length: ' . strlen($body));
    header('X-WOPI-ItemVersion: ' . sha1($body));
    echo $body;
    exit;
}

if ($method === 'GET') {
    $user = new User();
    $displayName = $user->load($userId) ? trim((string)$user->getScopedDisplayName($organizationId)) : '';
    $email = trim((string)$user->getScopedEmail($organizationId));
    if ($displayName === '') {
        $displayName = 'Utilisateur ' . $userId;
    }

    $lastModified = $document->get('datemodification');
    $lastModifiedVersion = $lastModified instanceof DateTimeInterface
        ? $lastModified->format(DateTimeInterface::ATOM)
        : (string)$lastModified;
    $version = sha1(implode('|', array(
        (string)$document->get('storedfilepath'),
        (string)$document->get('storedfilesize'),
        $lastModifiedVersion,
    )));
    $lastModifiedTime = $lastModified instanceof DateTimeInterface
        ? $lastModified->format(DateTimeInterface::ATOM)
        : gmdate(DateTimeInterface::ATOM);
    $userSettingsUrl = omoCollaboraBuildWopiSource($documentId)
        . '/settings?access_token=' . rawurlencode($accessToken)
        . '&ui_theme=' . rawurlencode($uiTheme);

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array(
        'BaseFileName' => $document->getStoredFileDownloadName(),
        'OwnerId' => (string)max(0, (int)$document->get('IDusercreation')),
        'UserId' => (string)$userId,
        'UserFriendlyName' => $displayName,
        'UserPrincipalName' => $email,
        'IsAnonymousUser' => false,
        'UserCanWrite' => $canEdit,
        'UserCanNotWriteRelative' => true,
        'Size' => $document->getStoredFileSize(),
        'Version' => $version,
        'LastModifiedTime' => $lastModifiedTime,
        'SupportsLocks' => false,
        'SupportsGetLock' => false,
        'SupportsUpdate' => true,
        'SupportsRename' => false,
        'SupportsDeleteFile' => false,
        'UserSettings' => array('uri' => $userSettingsUrl),
        'PostMessageOrigin' => $collaboraConfig['baseUrl'] !== ''
            ? $collaboraConfig['baseUrl']
            : omoCollaboraBuildRequestOrigin(),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($method !== 'POST' || $override !== 'PUT' || !$canEdit) {
    http_response_code($canEdit ? 501 : 403);
    exit;
}

$contents = file_get_contents('php://input');
if (!is_string($contents)) {
    http_response_code(400);
    exit;
}

$mimeType = $document->getStoredFileMimeType();
$updateResult = $organization->updateDocumentFileContentsOnStorage(
    (string)$document->get('storedfilepath'),
    $contents,
    $mimeType
);
if (!is_array($updateResult) || empty($updateResult['status'])) {
    http_response_code(502);
    exit;
}

$document->set('storedfilesize', strlen($contents));
$document->set('IDusermodification', $userId);
$document->set('datemodification', new DateTimeImmutable());
$saveResult = $document->save();
if (!is_array($saveResult) || empty($saveResult['status'])) {
    http_response_code(500);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
header('X-WOPI-ItemVersion: ' . sha1($contents));
echo json_encode(array('LastModifiedTime' => gmdate(DateTimeInterface::ATOM)), JSON_UNESCAPED_SLASHES);
