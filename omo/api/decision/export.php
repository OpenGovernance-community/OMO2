<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/modules/context.php';
require_once __DIR__ . '/modules/export_common.php';

use dbObject\DecisionGroup;
use dbObject\DecisionProcess;

function omoDecisionExportFail($statusCode, $message)
{
    http_response_code((int)$statusCode);
    header('Content-Type: text/plain; charset=UTF-8');
    echo (string)$message;
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    omoDecisionExportFail(405, 'Methode non autorisee.');
}

$context = omoDecisionResolveEditorContext($_GET);
if (empty($context['status'])) {
    omoDecisionExportFail((int)($context['code'] ?? 400), 'Contexte de prise de decision invalide.');
}

if (($context['accessMode'] ?? '') === 'public' || empty($context['canManage'])) {
    omoDecisionExportFail(403, 'Acces refuse a cet export.');
}

$decision = $context['decision'] ?? null;
if (!($decision instanceof DecisionProcess) || (int)$decision->getId() <= 0) {
    omoDecisionExportFail(404, 'Prise de decision introuvable.');
}

$requestedGroupId = (int)($_GET['gid'] ?? 0);
$decisionGroup = $context['decisionGroup'] ?? null;
if ($requestedGroupId > 0 && !($decisionGroup instanceof DecisionGroup)) {
    $decisionGroup = $decision->getPrimaryGroup(true);
}

$bundleList = omoDecisionExportBuildBundleList(
    $decision,
    $requestedGroupId > 0 && $decisionGroup instanceof DecisionGroup ? $decisionGroup : null,
    $requestedGroupId <= 0
);
if (count($bundleList) === 0) {
    omoDecisionExportFail(422, 'Aucun bloc exportable trouve pour ce scrutin.');
}

$methods = [];
foreach ($bundleList as $bundleItem) {
    $bundleMethod = trim((string)($bundleItem['method'] ?? ''));
    if ($bundleMethod !== '') {
        $methods[$bundleMethod] = true;
    }
}

if (count($methods) > 1) {
    omoDecisionExportFail(422, 'Cet export multi-blocs demande pour l instant des blocs utilisant la meme methode.');
}

$method = count($methods) > 0
    ? (string)array_key_first($methods)
    : DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method'));

$definition = omoDecisionEnsureMethodExportLoaded($method);
if (!$definition || empty($definition['export_function']) || !function_exists((string)$definition['export_function'])) {
    omoDecisionExportFail(501, 'Export indisponible pour cette methode.');
}

$format = trim((string)($_GET['format'] ?? ''));
if (!in_array($format, ['csv', 'json', 'xml'], true)) {
    if ($format === 'pdf') {
        omoDecisionExportFail(501, 'Export PDF non disponible pour le moment.');
    }

    omoDecisionExportFail(400, 'Format d export invalide.');
}

$bundle = count($bundleList) === 1
    ? $bundleList[0]
    : [
        'decision' => $decision,
        'decisionGroup' => null,
        'methodOwner' => $decision,
        'method' => $method,
        'bundles' => $bundleList,
    ];
$exportResult = call_user_func((string)$definition['export_function'], $decision, $decisionGroup, $context, $format, $bundle);

if (!is_array($exportResult) || empty($exportResult['status'])) {
    omoDecisionExportFail(422, (string)($exportResult['message'] ?? 'Impossible de generer cet export.'));
}

$filename = trim((string)($exportResult['filename'] ?? ''));
if ($filename === '') {
    $filename = omoDecisionExportBuildFileBaseName($decision, $format) . '.' . $format;
}

header('Content-Type: ' . (string)($exportResult['mimeType'] ?? 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

echo (string)($exportResult['content'] ?? '');
exit;
