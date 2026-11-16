<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/email_layout.php';
require_once dirname(__DIR__) . '/popup/profil_translation_helper.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function profileEmailChangeReply(array $payload, int $httpStatus = 200): never
{
    http_response_code($httpStatus);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function profileEmailChangeFail(string $error, int $httpStatus = 400): never
{
    profileEmailChangeReply(['status' => false, 'error' => $error,
        'message' => profilPopupT('profile.email_change.error.' . $error)], $httpStatus);
}

function profileEmailChangeSend(array $request, string $side): bool
{
    $variables = ['oldEmail' => $request['old_email'], 'newEmail' => $request['new_email']];
    $subject = profilPopupT('profile.email_change.mail.' . $side . '_subject');
    $body = profilPopupT('profile.email_change.mail.' . $side . '_body', $variables);
    $code = profilPopupT('profile.email_change.mail.code', ['code' => $request[$side . '_code']]);
    $footer = profilPopupT('profile.email_change.mail.footer');
    $from = trim((string)($GLOBALS['mailUser'] ?? ''));
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $from = 'noreply@' . parse_url(commonSecurityBaseUrl(), PHP_URL_HOST);
    }
    $brand = (string)($GLOBALS['siteTitle'] ?? 'OMO');
    $html = commonRenderMailLayout(['brand_name' => $brand, 'heading' => $subject,
        'body_html' => commonMailTextToHtml($body) . '<p><strong>' . commonMailEscape($code) . '</strong></p>',
        'footer_html' => commonMailTextToHtml($footer)]);
    return myHTMLMail([$from, $brand], $request[$side . '_email'], $subject, $html,
        plainTextBody: $body . "\n\n" . $code . "\n\n" . $footer, organizationId: 0);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') profileEmailChangeFail('request', 405);
if (!checklogin()) {
    profileEmailChangeReply(['status' => false, 'message' => profilPopupT('profile.popup.error.login_required')], 401);
}
if (!commonBrowserMutationIsAllowed($_SERVER, $_POST, $_SESSION)) profileEmailChangeFail('request', 403);
foreach (['action', 'new_email', 'token', 'old_code', 'new_code'] as $field) {
    if (isset($_POST[$field]) && !is_string($_POST[$field])) profileEmailChangeFail('request');
}
$userId = (int)commonGetCurrentUserId();
$user = new \dbObject\User();
if (!$user->load($userId)) profileEmailChangeFail('unavailable', 404);
$securityVersion = (int)$user->get('security_version');

try {
    if (($_POST['action'] ?? '') === 'start') {
        $request = \dbObject\UserEmailChange::issue($userId, $_POST['new_email'] ?? '', $securityVersion);
        if (isset($request['error'])) profileEmailChangeFail($request['error']);
        foreach ([$request['old_email'], $request['new_email']] as $email) {
            $limit = commonAuthEmailDeliveryLimits('magic', $email);
            if (empty($limit['available'])) profileEmailChangeFail('unavailable', 503);
            if (empty($limit['allowed'])) {
                header('Retry-After: ' . max(1, (int)($limit['retry_after'] ?? 60)));
                profileEmailChangeFail('rate_limited', 429);
            }
        }
        // A partially delivered request stays unusable until a fresh pair is sent.
        if (!profileEmailChangeSend($request, 'old') || !profileEmailChangeSend($request, 'new')
            || !\dbObject\UserEmailChange::markDelivered($userId, $request['token'])) {
            commonAuthSecurityLog('email_change', 'delivery_failed', ['user_id' => $userId]);
            profileEmailChangeFail('delivery', 503);
        }
        commonAuthSecurityLog('email_change', 'sent', ['user_id' => $userId]);
        profileEmailChangeReply(['status' => true, 'token' => $request['token'],
            'old_email' => $request['old_email'], 'new_email' => $request['new_email'],
            'message' => profilPopupT('profile.email_change.sent')]);
    }

    if (($_POST['action'] ?? '') === 'confirm') {
        $result = \dbObject\UserEmailChange::confirm($userId, $_POST['token'] ?? '',
            trim($_POST['old_code'] ?? ''), trim($_POST['new_code'] ?? ''), $securityVersion);
        if (isset($result['error'])) {
            commonAuthSecurityLog('email_change', 'rejected', ['user_id' => $userId, 'reason' => $result['error']]);
            profileEmailChangeFail($result['error'], 422);
        }
        $_SESSION['auth_security_version'] = $result['security_version'];
        commonExpireCookieValue(commonGetRememberCookieName(), true);
        session_regenerate_id(true);
        commonAuthSecurityLog('email_change', 'changed', ['user_id' => $userId]);
        profileEmailChangeReply(['status' => true, 'email' => $result['email'],
            'message' => profilPopupT('profile.email_change.success')]);
    }
    profileEmailChangeFail('request');
} catch (\Throwable $error) {
    error_log('Profile email change failed: ' . get_class($error) . ' ' . $error->getMessage());
    profileEmailChangeFail('unavailable', 503);
}
