<?php

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config.php';
require_once BASE_PATH . '/shared_functions.php';
require_once BASE_PATH . '/common/auth.php';
require_once BASE_PATH . '/common/account_deletion.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function accountDeletionRespond(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    accountDeletionRespond(array('status' => false, 'message' => 'Requete invalide.'), 405);
}

$connected = checklogin();
$currentUserId = function_exists('commonGetCurrentUserId')
    ? (int)commonGetCurrentUserId()
    : (int)($_SESSION['currentUser'] ?? 0);
if (!$connected || $currentUserId <= 0) {
    accountDeletionRespond(array('status' => false, 'message' => 'Connexion requise.'), 401);
}
if (!commonAccountDeletionVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
    accountDeletionRespond(array('status' => false, 'message' => 'La demande a expire. Rechargez la page avant de recommencer.'), 403);
}

$action = trim((string)($_POST['action'] ?? 'plan'));
if ($action === 'plan') {
    accountDeletionRespond(\dbObject\User::getAccountDeletionPlan($currentUserId));
}
if ($action !== 'delete') {
    accountDeletionRespond(array('status' => false, 'message' => 'Action inconnue.'), 400);
}

$result = \dbObject\User::deleteOwnAccount($currentUserId, (string)($_POST['confirmation'] ?? ''));
if (empty($result['status'])) {
    accountDeletionRespond($result, 422);
}

commonLogoutUser();
unset($_SESSION['account_deletion_csrf']);
accountDeletionRespond(array(
    'status' => true,
    'message' => 'Votre profil a ete supprime.',
    'redirect' => '/',
));

