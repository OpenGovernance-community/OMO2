<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/modules/context.php';
require_once __DIR__ . '/modules/registry.php';
require_once __DIR__ . '/modules/common.php';
require_once dirname(__DIR__, 3) . '/common/notification_center.php';

use dbObject\DbObject;
use dbObject\DecisionProcess;
use dbObject\DecisionProposal;

header('Content-Type: application/json; charset=UTF-8');

function omoDecisionMultiSaveRespond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function omoDecisionMultiSaveExecute(string $saveFile, array $postData): OmoDecisionModuleCapturedResponse
{
    $_POST = $postData;
    try {
        require $saveFile;
    } catch (OmoDecisionModuleCapturedResponse $response) {
        return $response;
    }

    throw new RuntimeException('Le module n’a retourné aucun résultat.');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    omoDecisionMultiSaveRespond(405, [
        'status' => false,
        'message' => 'Méthode non autorisée.',
    ]);
}

$serializedGroups = $_POST['groups'] ?? [];
if (!is_array($serializedGroups) || count($serializedGroups) < 1 || count($serializedGroups) > 100) {
    omoDecisionMultiSaveRespond(400, [
        'status' => false,
        'message' => 'La liste des questions est invalide.',
    ]);
}

$saveFiles = [
    DecisionProcess::METHOD_SIMPLE_VOTE => __DIR__ . '/modules/vote/save.php',
    DecisionProcess::METHOD_MAJORITY_JUDGMENT => __DIR__ . '/modules/majority_judgment/save.php',
    DecisionProcess::METHOD_CONSENT => __DIR__ . '/modules/consent/save.php',
];
$groupPayloads = [];
foreach ($serializedGroups as $groupIndex => $serializedGroup) {
    if (!is_string($serializedGroup) || strlen($serializedGroup) > 2000000) {
        omoDecisionMultiSaveRespond(400, [
            'status' => false,
            'message' => 'Le contenu d’une question est invalide.',
        ]);
    }
    $groupData = [];
    parse_str($serializedGroup, $groupData);
    $method = DecisionProcess::normalizeEvaluationMethod((string)($groupData['method'] ?? $groupData['evaluation_method'] ?? ''));
    if (!isset($saveFiles[$method])) {
        omoDecisionMultiSaveRespond(400, [
            'status' => false,
            'message' => 'Le mode d’une question est invalide.',
        ]);
    }
    $groupPayloads[] = [
        'originalIndex' => (int)$groupIndex,
        'method' => $method,
        'saveFile' => $saveFiles[$method],
        'postData' => $groupData,
    ];
}

$decisionId = (int)($groupPayloads[0]['postData']['id'] ?? 0);
$batchDecision = new DecisionProcess();
if ($decisionId <= 0 || !$batchDecision->load($decisionId)) {
    omoDecisionMultiSaveRespond(404, [
        'status' => false,
        'message' => 'Prise de décision introuvable.',
    ]);
}
foreach ($groupPayloads as $groupPayload) {
    if ((int)($groupPayload['postData']['id'] ?? 0) !== $decisionId) {
        omoDecisionMultiSaveRespond(400, [
            'status' => false,
            'message' => 'Les questions n’appartiennent pas au même scrutin.',
        ]);
    }
}

$primaryGroup = $batchDecision->getPrimaryGroup(false);
$primaryGroupId = $primaryGroup ? (int)$primaryGroup->getId() : 0;
$desiredStatus = DecisionProcess::normalizeStatus((string)($groupPayloads[count($groupPayloads) - 1]['postData']['status'] ?? $batchDecision->get('status')));
$originalStatus = DecisionProcess::normalizeStatus($batchDecision->get('status'));
usort($groupPayloads, static function (array $left, array $right) use ($primaryGroupId): int {
    $leftPrimary = (int)($left['postData']['gid'] ?? 0) === $primaryGroupId;
    $rightPrimary = (int)($right['postData']['gid'] ?? 0) === $primaryGroupId;
    if ($leftPrimary === $rightPrimary) {
        return $left['originalIndex'] <=> $right['originalIndex'];
    }
    return $leftPrimary ? 1 : -1;
});

$pdo = DbObject::getPdo();
if (!$pdo) {
    omoDecisionMultiSaveRespond(500, [
        'status' => false,
        'message' => 'Connexion à la base impossible.',
    ]);
}

$originalPost = $_POST;
$results = [];
$failureCode = 500;
$GLOBALS['omoDecisionCaptureModuleResponse'] = true;
$GLOBALS['omoDecisionDeferProposalNotifications'] = true;
$GLOBALS['omoDecisionDeferredProposalIds'] = [];

try {
    $pdo->beginTransaction();
    $lastExecutionIndex = count($groupPayloads) - 1;
    foreach ($groupPayloads as $executionIndex => $groupPayload) {
        $groupPayload['postData']['status'] = $executionIndex === $lastExecutionIndex ? $desiredStatus : $originalStatus;
        $response = omoDecisionMultiSaveExecute($groupPayload['saveFile'], $groupPayload['postData']);
        if ($response->statusCode >= 400 || empty($response->payload['status'])) {
            $failureCode = $response->statusCode;
            throw new RuntimeException((string)($response->payload['message'] ?? 'Impossible d’enregistrer cette question.'));
        }
        $results[$groupPayload['originalIndex']] = $response->payload;
    }
    $pdo->commit();
    ksort($results);
    $results = array_values($results);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_POST = $originalPost;
    unset($GLOBALS['omoDecisionCaptureModuleResponse'], $GLOBALS['omoDecisionDeferProposalNotifications']);
    $GLOBALS['omoDecisionDeferredProposalIds'] = [];
    omoDecisionMultiSaveRespond($failureCode, [
        'status' => false,
        'message' => trim($exception->getMessage()) !== ''
            ? $exception->getMessage()
            : 'Impossible d’enregistrer toutes les questions.',
    ]);
}

$_POST = $originalPost;
$proposalIds = array_values(array_unique(array_map('intval', (array)$GLOBALS['omoDecisionDeferredProposalIds'])));
unset(
    $GLOBALS['omoDecisionCaptureModuleResponse'],
    $GLOBALS['omoDecisionDeferProposalNotifications'],
    $GLOBALS['omoDecisionDeferredProposalIds']
);

foreach ($proposalIds as $proposalId) {
    if ($proposalId <= 0) {
        continue;
    }
    $proposal = new DecisionProposal();
    if (!$proposal->load($proposalId)) {
        continue;
    }
    try {
        notificationCenterDispatchDecisionProposal($proposal);
    } catch (Throwable $exception) {
        error_log('decision_proposal_notification_failed: ' . $exception->getMessage());
    }
}

omoDecisionMultiSaveRespond(200, [
    'status' => true,
    'message' => 'Scrutin enregistré.',
    'results' => $results,
]);
