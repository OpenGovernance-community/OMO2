<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/github_bug_report.php';
require_once dirname(__DIR__, 2) . '/common/patreon.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => false,
        'message' => 'Methode non autorisee.',
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

    return 'Navigateur inconnu';
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

    return 'Systeme inconnu';
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
        throw new RuntimeException('Document root introuvable pour stocker la piece jointe.');
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
        throw new RuntimeException('Une piece jointe n a pas pu etre televersee correctement.');
    }

    $tmpName = (string)($upload['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Fichier temporaire invalide pour la piece jointe.');
    }

    $size = (int)($upload['size'] ?? 0);
    if ($size <= 0) {
        throw new RuntimeException('Une piece jointe est vide.');
    }

    $maxSize = 15 * 1024 * 1024;
    if ($size > $maxSize) {
        throw new RuntimeException('Chaque piece jointe doit faire moins de 15 MB.');
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
        throw new RuntimeException('Type de fichier non autorise pour la piece jointe.');
    }

    if ($extension === '' || !in_array($extension, $rules[$detectedMime], true)) {
        $extension = $rules[$detectedMime][0];
    }

    $targetDir = omoBugReportUploadDir();
    if (!is_dir($targetDir) && !@mkdir($targetDir, 0777, true)) {
        throw new RuntimeException('Impossible de creer le dossier de stockage des pieces jointes.');
    }

    $storedBaseName = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $storedBaseName;
    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('Impossible de stocker une piece jointe sur le serveur.');
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
        throw new RuntimeException('Maximum 5 pieces jointes par signalement.');
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
        'message' => 'Connexion requise.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!githubBugReportIsConfigured()) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Configuration GitHub incomplete: ' . implode(' ; ', githubBugReportGetConfigurationIssues()) . '.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$patreonConnection = \dbObject\UserPatreon::findByUserId($currentUserId);
$patreonConnected = $patreonConnection !== false && $patreonConnection->isConnected();
if (!$patreonConnected) {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'message' => 'Le Bug Tracking System est reserve aux comptes Patreon connectes.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$title = omoBugReportCleanLine($_POST['title'] ?? '', 180);
$description = omoBugReportCleanText($_POST['description'] ?? '', 8000);

if ($title === '' || $description === '') {
    http_response_code(422);
    echo json_encode([
        'status' => false,
        'message' => 'Titre et description obligatoires.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$user = new \dbObject\User();
if (!$user->load($currentUserId)) {
    http_response_code(404);
    echo json_encode([
        'status' => false,
        'message' => 'Utilisateur introuvable.',
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
    'URL' => omoBugReportCleanLine($_POST['page_url'] ?? '', 1800),
    'Page title' => omoBugReportCleanLine($_POST['page_title'] ?? '', 250),
    'Application' => omoBugReportCleanLine($_POST['app_key'] ?? '', 80),
    'Theme' => omoBugReportCleanLine($_POST['theme'] ?? '', 80),
    'User' => $userDisplayName !== '' ? $userDisplayName : ('User #' . $currentUserId),
    'User id' => (string)$currentUserId,
    'Username' => $userUsername,
    'Organisation' => $organizationName,
    'Organisation id' => $currentOrganizationId > 0 ? (string)$currentOrganizationId : '',
    'Organisation shortname' => $organizationShortname,
    'Browser' => omoBugReportDetectBrowser($_POST['user_agent'] ?? ''),
    'OS' => omoBugReportDetectOs($_POST['user_agent'] ?? '', $_POST['platform'] ?? ''),
    'User agent' => omoBugReportCleanLine($_POST['user_agent'] ?? '', 1000),
    'Platform' => omoBugReportCleanLine($_POST['platform'] ?? '', 120),
    'Language' => omoBugReportCleanLine($_POST['language'] ?? '', 80),
    'Languages' => omoBugReportCleanLine($_POST['languages'] ?? '', 200),
    'Timezone' => omoBugReportCleanLine($_POST['timezone'] ?? '', 80),
    'Viewport' => omoBugReportCleanLine($_POST['viewport'] ?? '', 40),
    'Screen' => omoBugReportCleanLine($_POST['screen_size'] ?? '', 40),
    'Pixel ratio' => omoBugReportCleanLine($_POST['pixel_ratio'] ?? '', 20),
    'Referrer' => omoBugReportCleanLine($_POST['referrer'] ?? '', 500),
    'Client timestamp' => omoBugReportCleanLine($_POST['client_timestamp'] ?? '', 60),
    'Server timestamp' => gmdate('c'),
];

$issueLines = [
    '## Description',
    '',
    $description,
    '',
    '## Contexte',
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
        $issueLines[] = '## Pieces jointes publiques';
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

    $responseMessage = 'Signalement envoye sur GitHub.';

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
