<?php
require_once dirname(__DIR__) . '/shared_functions.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
$fail = static function (int $status, string $message): never {
    http_response_code($status);
    echo json_encode(['status' => false, 'message' => $message]);
    exit;
};
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') $fail(405, 'Requete invalide.');
$code = $_POST['code'] ?? null;
$user = \dbObject\User::findByPasswordResetCode($code);
if (!$user || !is_scalar($_POST['id'] ?? null) || (int)$_POST['id'] !== (int)$user->getId()) {
    $fail(403, 'Lien invalide ou expire.');
}
$password = $_POST['password'] ?? null;
$confirmation = $_POST['password2'] ?? null;
if (!is_string($password) || !is_string($confirmation) || $password !== $confirmation
    || empty(commonEvaluatePasswordComplexity($password, (string)$user->get('email'))['valid'])) {
    $fail(422, commonGetPasswordPolicyValidationMessage());
}
try {
    if (!$user->consumePasswordResetCode($code, commonHashUserPassword($password), $_POST)) {
        $fail(403, 'Lien invalide ou expire.');
    }
} catch (Throwable $error) {
    error_log('Account recovery failed: ' . get_class($error));
    $fail(503, 'Impossible de mettre a jour le compte.');
}
commonAuthSecurityLog('password_reset', 'success', ['user_id' => (int)$user->getId()]);
echo json_encode(['status' => true, 'message' => 'Compte mis a jour.', 'script' => "document.location='/';"]);
