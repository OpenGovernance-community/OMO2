<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ChecklistRun;

header('Content-Type: application/json; charset=UTF-8');

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$runId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$run = new ChecklistRun();
if ($runId <= 0 || !$run->load($runId) || (int)$run->get('IDorganization') !== $organizationId) {
    http_response_code(404);
    echo json_encode(['success' => false], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
$checklist = $run->getChecklist();
if (!$checklist || !omoChecklistCanView($checklist)) {
    http_response_code(403);
    echo json_encode(['success' => false], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
echo json_encode(['success' => true, 'items' => $run->getPvReviewItems()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
