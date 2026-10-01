<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/public_access.php';

use dbObject\DecisionParticipant;
use dbObject\DecisionProcess;

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$token = trim((string)($_POST['token'] ?? ''));
$participant = omoDecisionResolvePublicParticipantByToken($token);
$returnTo = '/';
$logoutSucceeded = false;

if ($participant instanceof DecisionParticipant) {
    $decision = $participant->getDecisionProcess();
    if ($decision instanceof DecisionProcess) {
        $returnTo = DecisionProcess::buildGenericPublicAccessPath(
            (int)$decision->get('IDorganization'),
            (int)$decision->getId(),
            (int)$decision->get('IDholon'),
            'view'
        );
    }

    $logoutSucceeded = $participant->revokePublicAccess();
}

if ($logoutSucceeded) {
    commonLogoutUser();
}

if (stripos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code($logoutSucceeded ? 200 : 403);
    echo json_encode([
        'status' => $logoutSucceeded,
        'returnTo' => $returnTo,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$logoutSucceeded) {
    http_response_code(403);
    exit;
}

header('Location: ' . $returnTo);
exit;
