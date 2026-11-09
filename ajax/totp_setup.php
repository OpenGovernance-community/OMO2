<?php
require_once __DIR__ . '/../common/account-translations.php';


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

function totpSetupSave(\dbObject\User $user): array
{
    try {
        $result = $user->saveSecurityChanges(true);
        $_SESSION['auth_security_version'] = (int)$user->get('security_version');
        commonExpireCookieValue(commonGetRememberCookieName(), true);
        session_regenerate_id(true);
        return $result;
    } catch (\Throwable $error) {
        error_log('TOTP security save failed: ' . get_class($error));
        return ['status' => false];
    }
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
    totpSetupReply(['status' => false, 'message' => commonAccountUiT('copy.requete_refusee')], 403);
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
        totpSetupReply(['status' => false, 'message' => commonAccountUiT('copy.la_double_authentification_est_deja_activee')], 409);
    }
    if (commonTotpGetEncryptionKey() === null) {
        totpSetupReply(['status' => false, 'message' => commonAccountUiT('copy.la_cle_de_protection_totp_du_serveur_est_manquante')], 503);
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
        totpSetupReply(['status' => false, 'message' => commonAccountUiT('copy.la_configuration_a_expire_recommencez_la_procedure')], 400);
    }

    if (!commonTotpVerifyCode($pending['secret'], $_POST['code'] ?? '')) {
        totpSetupReply(['status' => false, 'message' => commonAccountUiT('copy.le_code_de_verification_est_invalide')], 422);
    }

    $encryptedSecret = commonTotpEncryptSecret($pending['secret']);
    if ($encryptedSecret === null) {
        totpSetupReply(['status' => false, 'message' => commonAccountUiT('copy.le_secret_ne_peut_pas_etre_protege_par_le_serveur')], 503);
    }

    $user->set('totp_secret', $encryptedSecret);
    $user->set('totp_enabled', 1);
    $result = totpSetupSave($user);
    if (!is_array($result) || empty($result['status'])) {
        totpSetupReply(['status' => false, 'message' => commonAccountUiT('copy.impossible_d_activer_la_double_authentification')], 500);
    }

    unset($_SESSION['pending_totp_setup']);
    commonAuthSecurityLog('totp_setup', 'enabled', ['user_id' => $userId]);
    totpSetupReply(['status' => true, 'message' => commonAccountUiT('copy.la_double_authentification_est_activee')]);
}

if ($action === 'disable') {
    $secret = commonUserGetTotpSecret($user);
    if ($secret === null || !is_string($_POST['code'] ?? null) || !commonTotpVerifyCode($secret, $_POST['code'])) {
        totpSetupReply(['status' => false, 'message' => commonAccountUiT('copy.le_code_de_verification_est_invalide')], 422);
    }
    $user->set('totp_secret', null);
    $user->set('totp_enabled', 0);
    $result = totpSetupSave($user);
    if (!is_array($result) || empty($result['status'])) {
        totpSetupReply(['status' => false, 'message' => commonAccountUiT('copy.impossible_de_desactiver_la_double_authentification')], 500);
    }

    unset($_SESSION['pending_totp_setup']);
    commonAuthSecurityLog('totp_setup', 'disabled', ['user_id' => $userId]);
    totpSetupReply(['status' => true, 'message' => commonAccountUiT('copy.la_double_authentification_est_desactivee')]);
}

totpSetupReply(['status' => false, 'message' => 'Action invalide.'], 400);
