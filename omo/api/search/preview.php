<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/preview_shared.php';

header('Content-Type: application/json; charset=utf-8');
$organizationId = (int)($_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$currentHolonId = (int)($_GET['cid'] ?? 0);
$module = (string)($_GET['module'] ?? '');
$id = (int)($_GET['id'] ?? 0);
$query = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 1000, 'UTF-8');
$renderers = [
    'documents' => 'documents', 'pv' => 'documents', 'faq' => 'faq', 'stats' => 'stats',
    'rules' => 'policy', 'projects' => 'projects', 'processus' => 'processes',
    'activities' => 'recurring_tasks', 'calendar' => 'calendar', 'decision' => 'decision',
    'structure' => 'structure', 'team' => 'team', 'tutorials' => 'lms',
];
$organization = new \dbObject\Organization();
try {
    if (!isset($renderers[$module]) || !$organization->load($organizationId) || !$organization->canViewDetail()) {
        throw new RuntimeException('Preview unavailable');
    }
    $viewerContext = \dbObject\SearchJob::buildViewerContextFromGlobals($organizationId, $currentHolonId);
    $object = $organization->loadTopbarSearchPreviewObject($module, $id, $viewerContext);
    if (!$object) {
        throw new RuntimeException('Preview unavailable');
    }
    // Each module owns its condensed view. Only this allowlist selects a file.
    $renderPreview = require dirname(__DIR__) . '/' . $renderers[$module] . '/search_preview.php';
    $preview = $renderPreview($object, $organization, $currentHolonId, $query);
    echo json_encode([
        'title' => omoSearchPreviewText($preview['title'] ?? ''),
        'titleHtml' => omoSearchPreviewHighlight(omoSearchPreviewText($preview['title'] ?? ''), $query),
        'html' => omoSearchPreviewRender($preview, $query),
    ], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $error) {
    if (!($error instanceof RuntimeException)) {
        error_log('Search preview [' . $module . ':' . $id . ']: ' . $error->getMessage());
    }
    http_response_code(404);
    echo json_encode(['error' => omoSearchPreviewT('error')]);
}
