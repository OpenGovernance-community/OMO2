<?php

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

header('Content-Type: application/json; charset=UTF-8');

function totpSetupReply(array $payload, $statusCode = 200)
{
    http_response_code((int)$statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function totpSetupIsSameOriginRequest()
{
    $currentHost = strtolower((string)preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
    if ($currentHost === '') {
        return true;
    }

    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $serverKey) {
        $value = trim((string)($_SERVER[$serverKey] ?? ''));
        if ($value === '') {
            continue;
        }

        $requestHost = strtolower((string)parse_url($value, PHP_URL_HOST));
        return $requestHost !== '' && $requestHost === $currentHost;
    }

    return true;
}

if ((string)($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !totpSetupIsSameOriginRequest()) {
    totpSetupReply(['status' => false, 'message' => 'Requete refusee.'], 403);
}

if (!checklogin()) {
    totpSetupReply(['status' => false, 'message' => 'Connexion requise.'], 401);
}

$userId = (int)commonGetCurrentUserId();
$user = new \dbObject\User();
if ($userId <= 0 || !$user->load($userId)) {
    totpSetupReply(['status' => false, 'message' => 'Utilisateur inconnu.'], 404);
}

$action = strtolower(trim((string)($_POST['action'] ?? '')));
if ($action === 'start') {
    if (commonUserHasTotpEnabled($user)) {
        totpSetupReply(['status' => false, 'message' => 'La double authentification est deja activee.'], 409);
    }
    if (commonTotpGetEncryptionKey() === null) {
        totpSetupReply(['status' => false, 'message' => 'La cle de protection TOTP du serveur est manquante.'], 503);
    }

    $secret = commonTotpGenerateSecret();
    $_SESSION['pending_totp_setup'] = [
        'user_id' => $userId,
        'secret' => $secret,
        'email' => (string)$user->get('email'),
        'created_at' => time(),
        'qr_nonce' => bin2hex(random_bytes(16)),
    ];

    totpSetupReply([
        'status' => true,
        'qr_url' => '/ajax/totp_qr.php?nonce=' . rawurlencode($_SESSION['pending_totp_setup']['qr_nonce']),
        'manual_secret' => $secret,
    ]);
}

if ($action === 'confirm') {
    $pending = $_SESSION['pending_totp_setup'] ?? null;
    if (!is_array($pending)
        || (int)($pending['user_id'] ?? 0) !== $userId
        || (int)($pending['created_at'] ?? 0) < (time() - 900)
        || !is_string($pending['secret'] ?? null)) {
        unset($_SESSION['pending_totp_setup']);
        totpSetupReply(['status' => false, 'message' => 'La configuration a expire. Recommencez la procedure.'], 400);
    }

    if (!commonTotpVerifyCode($pending['secret'], $_POST['code'] ?? '')) {
        totpSetupReply(['status' => false, 'message' => 'Le code de verification est invalide.'], 422);
    }

    $encryptedSecret = commonTotpEncryptSecret($pending['secret']);
    if ($encryptedSecret === null) {
        totpSetupReply(['status' => false, 'message' => 'Le secret ne peut pas etre protege par le serveur.'], 503);
    }

    $user->set('totp_secret', $encryptedSecret);
    $user->set('totp_enabled', 1);
    $result = $user->save();
    if (!is_array($result) || empty($result['status'])) {
        totpSetupReply(['status' => false, 'message' => 'Impossible d activer la double authentification.'], 500);
    }

    unset($_SESSION['pending_totp_setup']);
    commonAuthSecurityLog('totp_setup', 'enabled', ['user_id' => $userId]);
    totpSetupReply(['status' => true, 'message' => 'La double authentification est activee.']);
}

if ($action === 'disable') {
    $user->set('totp_secret', null);
    $user->set('totp_enabled', 0);
    $result = $user->save();
    if (!is_array($result) || empty($result['status'])) {
        totpSetupReply(['status' => false, 'message' => 'Impossible de desactiver la double authentification.'], 500);
    }

    unset($_SESSION['pending_totp_setup']);
    commonAuthSecurityLog('totp_setup', 'disabled', ['user_id' => $userId]);
    totpSetupReply(['status' => true, 'message' => 'La double authentification est desactivee.']);
}

totpSetupReply(['status' => false, 'message' => 'Action invalide.'], 400);
