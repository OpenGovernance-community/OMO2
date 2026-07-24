<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Checklist;

header('Content-Type: application/json; charset=UTF-8');

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$checklistId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$checklist = $checklistId > 0 ? omoChecklistLoad($checklistId, $organizationId) : null;
if (!($checklist instanceof Checklist) || !omoChecklistCanView($checklist)) {
    http_response_code(404);
    echo json_encode(['success' => false], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$summary = $checklist->getPvReviewSummary();
echo json_encode([
    'success' => true,
    'isContainer' => !empty($summary['isContainer']),
    'total' => (int)($summary['total'] ?? 0),
    'counts' => (array)($summary['counts'] ?? []),
    'overdueCount' => (int)($summary['overdueCount'] ?? 0),
    'entries' => (array)($summary['entries'] ?? []),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
