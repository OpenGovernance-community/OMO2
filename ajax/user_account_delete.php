<?php
require_once __DIR__ . '/../common/account-translations.php';


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
    accountDeletionRespond(array('status' => false, 'message' => commonAccountUiT('copy.requete_invalide')), 405);
}

$connected = checklogin();
$currentUserId = function_exists('commonGetCurrentUserId')
    ? (int)commonGetCurrentUserId()
    : (int)($_SESSION['currentUser'] ?? 0);
if (!$connected || $currentUserId <= 0) {
    accountDeletionRespond(array('status' => false, 'message' => 'Connexion requise.'), 401);
}
if (!commonAccountDeletionVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
    accountDeletionRespond(array('status' => false, 'message' => commonAccountUiT('copy.la_demande_a_expire_rechargez_la_page_avant_de_recommencer')), 403);
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
    'message' => commonAccountUiT('copy.votre_profil_a_ete_supprime'),
    'redirect' => '/',
));

