<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/resource_catalog.php';

header('Content-Type: application/json; charset=UTF-8');
$respond = static function (int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') $respond(405, ['status' => false]);
$type = (string)($_GET['type'] ?? '');
if (!in_array($type, ['documents', 'decisions', 'projects', 'checklists', 'events', 'indicators'], true)) $respond(400, ['status' => false]);
$organizationId = (int)($_GET['oid'] ?? 0);
$documentId = (int)($_GET['id'] ?? 0);
$userId = (int)commonGetCurrentUserId();
$document = new \dbObject\Document();
$organization = new \dbObject\Organization();
if ($userId <= 0 || commonGetPublicPvParticipationLink() instanceof \dbObject\DocumentShareLink
    || $organizationId <= 0 || !$organization->load($organizationId) || !$organization->canViewDetail()
    || $documentId <= 0 || !$document->load($documentId)
    || (int)$document->get('IDorganization') !== $organizationId
    || !$document->canUserOpenPvEditor($userId, $organizationId)
    || !omoDocumentsPvEditorCanLoadResourceCatalog($organization, $userId, $type)) {
    $respond(403, ['status' => false]);
}
commonReleaseReadOnlySession();
\dbObject\DbObject::enableReadOnlyMemoization();
if ($type === 'indicators') require_once dirname(__DIR__, 2) . '/stats/shared.php';
if ($type === 'projects') require_once dirname(__DIR__, 2) . '/projects/shared.php';
$sourceLang = omoDocumentsPvEditorSourceLang();
$lang = omoLoadTranslationBundle('omo_documents_pv_editor', $sourceLang);
$translate = static fn (string $key, array $replace = []): string => t($key, $replace, $lang, $sourceLang);
$respond(200, ['status' => true, 'items' => omoDocumentsPvEditorLoadResourceCatalog($document, $organization, $userId, $type, $translate)]);
