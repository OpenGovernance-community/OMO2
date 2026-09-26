<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/github_bug_report.php';
require_once dirname(__DIR__, 2) . '/common/patreon.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => false,
        'message' => githubBugReportT('request_method'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function omoBugReportLimitString($value, $limit = 0)
{
    $value = trim((string)$value);
    if ($limit <= 0 || $value === '') {
        return $value;
    }

    return function_exists('mb_substr')
        ? mb_substr($value, 0, $limit, 'UTF-8')
        : substr($value, 0, $limit);
}

function omoBugReportCleanLine($value, $limit = 0)
{
    $value = str_replace(["\r", "\n", "\t"], ' ', (string)$value);
    $value = preg_replace('/\s+/', ' ', $value);
    return omoBugReportLimitString($value, $limit);
}

function omoBugReportCleanText($value, $limit = 0)
{
    $value = str_replace(["\r\n", "\r"], "\n", (string)$value);
    $value = trim((string)preg_replace('/\0+/', '', $value));

    if ($limit > 0 && $value !== '') {
        $value = function_exists('mb_substr')
            ? mb_substr($value, 0, $limit, 'UTF-8')
            : substr($value, 0, $limit);
    }

    return $value;
}

function omoBugReportDetectBrowser($userAgent)
{
    $ua = strtolower((string)$userAgent);
    if ($ua === '') {
        return '';
    }

    $map = [
        'edg/' => 'Edge',
        'opr/' => 'Opera',
        'firefox/' => 'Firefox',
        'chrome/' => 'Chrome',
        'safari/' => 'Safari',
    ];

    foreach ($map as $needle => $label) {
        if (strpos($ua, $needle) !== false) {
            return $label;
        }
    }

    return githubBugReportT('browser_unknown');
}

function omoBugReportDetectOs($userAgent, $platform = '')
{
    $ua = strtolower((string)$userAgent . ' ' . (string)$platform);
    if ($ua === '') {
        return '';
    }

    $map = [
        'windows' => 'Windows',
        'android' => 'Android',
        'iphone' => 'iPhone',
        'ipad' => 'iPad',
        'ios' => 'iOS',
        'mac os' => 'macOS',
        'macintosh' => 'macOS',
        'linux' => 'Linux',
    ];

    foreach ($map as $needle => $label) {
        if (strpos($ua, $needle) !== false) {
            return $label;
        }
    }

    return githubBugReportT('system_unknown');
}

function omoBugReportNormalizeUploads($fieldName)
{
    if (empty($_FILES[$fieldName])) {
        return [];
    }

    $upload = $_FILES[$fieldName];
    if (!is_array($upload)) {
        return [];
    }

    if (!is_array($upload['name'] ?? null)) {
        return [$upload];
    }

    $files = [];
    $count = count($upload['name']);
    for ($index = 0; $index < $count; $index++) {
        $files[] = [
            'name' => $upload['name'][$index] ?? '',
            'type' => $upload['type'][$index] ?? '',
            'tmp_name' => $upload['tmp_name'][$index] ?? '',
            'error' => $upload['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $upload['size'][$index] ?? 0,
        ];
    }

    return $files;
}

function omoBugReportAttachmentRules()
{
    return [
        'image/png' => ['png'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp'],
        'application/pdf' => ['pdf'],
        'text/plain' => ['txt', 'log'],
        'application/zip' => ['zip'],
        'application/x-zip-compressed' => ['zip'],
    ];
}

function omoBugReportUploadDir()
{
    $documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
    if ($documentRoot === '') {
        throw new RuntimeException(githubBugReportT('upload_root_missing'));
    }

    return $documentRoot . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'bugreport';
}

function omoBugReportStoreAttachment(array $upload)
{
    $errorCode = (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException(githubBugReportT('attachment_error'));
    }

    $tmpName = (string)($upload['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException(githubBugReportT('temporary_file_invalid'));
    }

    $size = (int)($upload['size'] ?? 0);
    if ($size <= 0) {
        throw new RuntimeException(githubBugReportT('empty_attachment'));
    }

    $maxSize = 15 * 1024 * 1024;
    if ($size > $maxSize) {
        throw new RuntimeException(githubBugReportT('attachment_size'));
    }

    $originalName = trim((string)($upload['name'] ?? ''));
    $originalName = preg_replace('/[^\w.\- ]+/u', '_', $originalName);
    $originalName = trim((string)$originalName);
    if ($originalName === '') {
        $originalName = 'attachment';
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $rules = omoBugReportAttachmentRules();
    $detectedMime = '';

    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = (string)$finfo->file($tmpName);
    } elseif (function_exists('mime_content_type')) {
        $detectedMime = (string)mime_content_type($tmpName);
    }

    if ($detectedMime === '' || !isset($rules[$detectedMime])) {
        throw new RuntimeException(githubBugReportT('attachment_type'));
    }

    if ($extension === '' || !in_array($extension, $rules[$detectedMime], true)) {
        $extension = $rules[$detectedMime][0];
    }

    $targetDir = omoBugReportUploadDir();
    if (!is_dir($targetDir) && !@mkdir($targetDir, 0777, true)) {
        throw new RuntimeException(githubBugReportT('attachment_directory'));
    }

    $storedBaseName = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $storedBaseName;
    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException(githubBugReportT('attachment_store'));
    }

    $publicPath = '/img/upload/bugreport/' . $storedBaseName;
    if (function_exists('appBuildAbsoluteUrl')) {
        $publicUrl = appBuildAbsoluteUrl($publicPath);
    } else {
        $publicUrl = commonBuildUrl($publicPath);
    }

    return [
        'name' => $originalName,
        'size' => $size,
        'mime' => $detectedMime,
        'url' => $publicUrl,
    ];
}

function omoBugReportStoreAttachments($fieldName)
{
    $uploads = omoBugReportNormalizeUploads($fieldName);
    if ($uploads === []) {
        return [];
    }

    if (count($uploads) > 5) {
        throw new RuntimeException(githubBugReportT('attachment_limit'));
    }

    $storedAttachments = [];
    foreach ($uploads as $upload) {
        $stored = omoBugReportStoreAttachment($upload);
        if ($stored !== null) {
            $storedAttachments[] = $stored;
        }
    }

    return $storedAttachments;
}

$currentUserId = (int)commonGetCurrentUserId();
if ($currentUserId <= 0) {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'message' => githubBugReportT('login_error'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!githubBugReportUiIsEnabled()) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'message' => githubBugReportT('module_unavailable'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$patreonConnection = \dbObject\UserPatreon::findByUserId($currentUserId);
$patreonConnected = $patreonConnection !== false && $patreonConnection->isConnected();
if (!$patreonConnected) {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'message' => githubBugReportT('patreon_error'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$title = omoBugReportCleanLine($_POST['title'] ?? '', 180);
$description = omoBugReportCleanText($_POST['description'] ?? '', 8000);

if ($title === '' || $description === '') {
    http_response_code(422);
    echo json_encode([
        'status' => false,
        'message' => githubBugReportT('required_fields'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$user = new \dbObject\User();
if (!$user->load($currentUserId)) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'message' => githubBugReportT('user_missing'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organizationName = '';
$organizationShortname = '';
if ($currentOrganizationId > 0) {
    $organization = new \dbObject\Organization();
    if ($organization->load($currentOrganizationId)) {
        $organizationName = trim((string)$organization->get('name'));
        $organizationShortname = trim((string)$organization->get('shortname'));
    }
}

$userDisplayName = trim((string)$user->getScopedDisplayName($currentOrganizationId));
if ($userDisplayName === '') {
    $userDisplayName = trim((string)commonGetCurrentUserDisplayName());
}

$userUsername = trim((string)$user->getScopedUsername($currentOrganizationId));
$context = [
    githubBugReportT('issue_url') => omoBugReportCleanLine($_POST['page_url'] ?? '', 1800),
    githubBugReportT('issue_page_title') => omoBugReportCleanLine($_POST['page_title'] ?? '', 250),
    githubBugReportT('issue_application') => omoBugReportCleanLine($_POST['app_key'] ?? '', 80),
    githubBugReportT('issue_theme') => omoBugReportCleanLine($_POST['theme'] ?? '', 80),
    githubBugReportT('issue_user') => $userDisplayName !== '' ? $userDisplayName : ('Utilisateur #' . $currentUserId),
    githubBugReportT('issue_user_id') => (string)$currentUserId,
    githubBugReportT('issue_username') => $userUsername,
    githubBugReportT('issue_organization') => $organizationName,
    githubBugReportT('issue_organization_id') => $currentOrganizationId > 0 ? (string)$currentOrganizationId : '',
    githubBugReportT('issue_organization_shortname') => $organizationShortname,
    githubBugReportT('issue_browser') => omoBugReportDetectBrowser($_POST['user_agent'] ?? ''),
    githubBugReportT('issue_os') => omoBugReportDetectOs($_POST['user_agent'] ?? '', $_POST['platform'] ?? ''),
    githubBugReportT('issue_user_agent') => omoBugReportCleanLine($_POST['user_agent'] ?? '', 1000),
    githubBugReportT('issue_platform') => omoBugReportCleanLine($_POST['platform'] ?? '', 120),
    githubBugReportT('issue_language') => omoBugReportCleanLine($_POST['language'] ?? '', 80),
    githubBugReportT('issue_languages') => omoBugReportCleanLine($_POST['languages'] ?? '', 200),
    githubBugReportT('issue_timezone') => omoBugReportCleanLine($_POST['timezone'] ?? '', 80),
    githubBugReportT('issue_viewport') => omoBugReportCleanLine($_POST['viewport'] ?? '', 40),
    githubBugReportT('issue_screen') => omoBugReportCleanLine($_POST['screen_size'] ?? '', 40),
    githubBugReportT('issue_pixel_ratio') => omoBugReportCleanLine($_POST['pixel_ratio'] ?? '', 20),
    githubBugReportT('issue_referrer') => omoBugReportCleanLine($_POST['referrer'] ?? '', 500),
    githubBugReportT('issue_client_timestamp') => omoBugReportCleanLine($_POST['client_timestamp'] ?? '', 60),
    githubBugReportT('issue_server_timestamp') => gmdate('c'),
];

$issueLines = [
    '## ' . githubBugReportT('issue_description_heading'),
    '',
    $description,
    '',
    '## ' . githubBugReportT('issue_context_heading'),
];

foreach ($context as $label => $value) {
    if ($value === '' || $value === '0') {
        continue;
    }

    $issueLines[] = '- ' . $label . ': ' . $value;
}

try {
    $attachments = omoBugReportStoreAttachments('attachments');
    if ($attachments !== []) {
        $issueLines[] = '';
        $issueLines[] = '## ' . githubBugReportT('issue_attachments_heading');
        $issueLines[] = '';

        foreach ($attachments as $attachment) {
            $issueLines[] = '- [' . (string)$attachment['name'] . '](' . (string)$attachment['url'] . ')';
        }
    }

    $issueTitle = preg_match('/^\[BTS\]/i', $title) ? $title : '[BTS] ' . $title;
    $issueBody = implode("\n", $issueLines) . "\n";

    $issue = githubBugReportCreateIssue($issueTitle, $issueBody, [
        'type' => 'Bug',
    ]);

    $responseMessage = githubBugReportT('issue_sent');

    echo json_encode([
        'status' => true,
        'message' => $responseMessage,
        'issue_number' => (int)($issue['number'] ?? 0),
        'issue_url' => (string)($issue['html_url'] ?? ''),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (RuntimeException $exception) {
    http_response_code(502);
    echo json_encode([
        'status' => false,
        'message' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
