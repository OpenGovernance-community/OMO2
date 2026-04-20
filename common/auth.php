<?php

function commonGetDemoOrganizationId()
{
    return 1;
}

function commonGetRequestHost()
{
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    return preg_replace('/:\d+$/', '', $host);
}

function commonGetRequestScheme()
{
    $https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
    if ($https !== '' && $https !== 'off') {
        return 'https';
    }

    if ((string)($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return 'https';
    }

    if (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
        return 'https';
    }

    return 'http';
}

function commonGetRootHost($host = null)
{
    $host = is_string($host) && $host !== '' ? strtolower($host) : strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $host = trim($host);

    if ($host === '') {
        return '';
    }

    $port = '';
    if (preg_match('/:(\d+)$/', $host, $matches)) {
        $port = ':' . $matches[1];
        $host = substr($host, 0, -strlen($port));
    }

    $parts = array_values(array_filter(explode('.', $host)));
    $isLocalhostSubdomain = count($parts) === 2 && ($parts[1] ?? '') === 'localhost';

    if ($isLocalhostSubdomain) {
        return 'localhost' . $port;
    }

    if (count($parts) >= 3) {
        $host = implode('.', array_slice($parts, -2));
    }

    return $host . $port;
}

function commonBuildUrl($path = '/', $host = null, $scheme = null)
{
    $path = commonNormalizeLocalPath($path, '/');
    $host = is_string($host) && $host !== '' ? $host : (string)($_SERVER['HTTP_HOST'] ?? '');
    $scheme = is_string($scheme) && $scheme !== '' ? strtolower($scheme) : commonGetRequestScheme();

    return $scheme . '://' . $host . $path;
}

function commonBuildOrganizationHost($shortname, $baseHost = null)
{
    $shortname = strtolower(trim((string)$shortname));
    $baseHost = commonGetRootHost($baseHost);

    if ($shortname === '' || $baseHost === '') {
        return $baseHost;
    }

    $hostOnly = $baseHost;
    $port = '';
    if (preg_match('/:(\d+)$/', $baseHost, $matches)) {
        $port = ':' . $matches[1];
        $hostOnly = substr($baseHost, 0, -strlen($port));
    }

    return $shortname . '.' . $hostOnly . $port;
}

function commonGetRequestSubdomain($host = null)
{
    $host = is_string($host) && $host !== '' ? strtolower($host) : commonGetRequestHost();
    $parts = array_values(array_filter(explode('.', $host)));

    $isLocalhostSubdomain = count($parts) === 2 && ($parts[1] ?? '') === 'localhost';

    if (count($parts) < 3 && !$isLocalhostSubdomain) {
        return '';
    }

    return (string)$parts[0];
}

function commonIsDemoHost($host = null)
{
    return commonGetRequestSubdomain($host) === 'demo';
}

function commonCanAccessWithoutLogin(?array $organizationContext = null)
{
    if ($organizationContext === null) {
        return commonIsDemoHost() && (int)($_SESSION['currentOrganization'] ?? -1) === commonGetDemoOrganizationId();
    }

    return commonIsDemoHost($organizationContext['host'] ?? null) && (int)($organizationContext['id'] ?? -1) === commonGetDemoOrganizationId();
}

function commonNormalizeLocalPath($path, $fallback = '/')
{
    $path = is_string($path) ? trim($path) : '';
    $fallback = is_string($fallback) && $fallback !== '' ? $fallback : '/';

    if ($path === '') {
        return $fallback;
    }

    $path = str_replace(["\r", "\n"], '', $path);

    if (preg_match('/^[a-z][a-z0-9+\-.]*:/i', $path)) {
        return $fallback;
    }

    if (strpos($path, '//') === 0) {
        return $fallback;
    }

    if ($path[0] !== '/') {
        $path = '/' . ltrim($path, '/');
    }

    return $path;
}

function commonGetCookieDomain()
{
    if (function_exists('appGetCookieDomain')) {
        return appGetCookieDomain(commonGetRequestHost());
    }

    $host = commonGetRequestHost();
    if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
        return '';
    }

    if ($host === 'localhost' || preg_match('/(^|\.)localhost$/', $host)) {
        return '';
    }

    $parts = array_values(array_filter(explode('.', $host)));
    if (count($parts) < 2) {
        return '';
    }

    return '.' . $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
}

function commonShouldUseSecureCookies()
{
    if (function_exists('appShouldUseSecureCookies')) {
        return appShouldUseSecureCookies();
    }

    return commonGetRequestScheme() === 'https';
}

function commonSetCookieValue($name, $value, $expires, $httpOnly = true)
{
    if (function_exists('appSetCookie')) {
        return appSetCookie($name, $value, $expires, $httpOnly, commonGetRequestHost());
    }

    $cookieDomain = commonGetCookieDomain();
    $options = [
        'expires' => (int)$expires,
        'path' => '/',
        'secure' => commonShouldUseSecureCookies(),
        'httponly' => (bool)$httpOnly,
        'samesite' => 'Lax',
    ];

    if ($cookieDomain !== '') {
        $options['domain'] = $cookieDomain;
    }

    return setcookie($name, $value, $options);
}

function commonExpireCookieValue($name, $httpOnly = true)
{
    return commonSetCookieValue($name, '', time() - 3600, $httpOnly);
}

function commonResolveOrganizationContext($defaultOrganizationId = 1)
{
    $host = commonGetRequestHost();

    if (commonIsDemoHost($host)) {
        $organization = new \dbObject\Organization();
        $organization = $organization->load(commonGetDemoOrganizationId()) ? $organization : false;
    } else {
        $organization = \dbObject\Organization::resolveFromHost($host, (int)$defaultOrganizationId);
    }

    if ($organization === false) {
        $_SESSION['currentOrganization'] = -1;

        return [
            'isValid' => false,
            'id' => -1,
            'name' => 'Organisation introuvable',
            'shortname' => '',
            'domain' => '',
            'logo' => '',
            'banner' => '',
            'color' => '#CCCCCC',
            'host' => $host,
            'error' => 'invalid_subdomain',
            'isDemo' => commonIsDemoHost($host),
        ];
    }

    $context = [
        'isValid' => true,
        'id' => (int)$organization->getId(),
        'name' => (string)$organization->get('name'),
        'shortname' => (string)$organization->get('shortname'),
        'domain' => (string)$organization->get('domain'),
        'logo' => (string)$organization->get('logo'),
        'banner' => (string)$organization->get('banner'),
        'color' => (string)($organization->get('color') ?: '#4CAF50'),
        'host' => $host,
        'error' => null,
        'isDemo' => commonIsDemoHost($host),
    ];

    $_SESSION['currentOrganization'] = $context['id'];

    return $context;
}

function commonRestoreRememberedUser()
{
    if (commonIsDemoHost()) {
        unset($_SESSION['currentUser']);
        unset($_SESSION['userRef']);
        unset($_SESSION['challenge']);
        return 0;
    }

    if (isset($_SESSION['currentUser']) && (int)$_SESSION['currentUser'] > 0) {
        return (int)$_SESSION['currentUser'];
    }

    if (!isset($_COOKIE['remember_token']) || $_COOKIE['remember_token'] === '') {
        return 0;
    }

    $remember = \dbObject\UserRemember::findValidByToken($_COOKIE['remember_token']);
    if (!$remember) {
        return 0;
    }

    $_SESSION['currentUser'] = (int)$remember->get('IDuser');
    return (int)$_SESSION['currentUser'];
}

function commonGetCurrentUserId()
{
    if (commonIsDemoHost()) {
        return 0;
    }

    return (int)($_SESSION['currentUser'] ?? 0);
}

function commonGetCurrentUserDisplayName()
{
    $userId = commonGetCurrentUserId();
    if ($userId <= 0) {
        return '';
    }

    $user = new \dbObject\User();
    if (!$user->load($userId)) {
        return '';
    }

    $fullName = trim((string)$user->get('firstname') . ' ' . (string)$user->get('lastname'));
    if ($fullName !== '') {
        return $fullName;
    }

    if ((string)$user->get('username') !== '') {
        return (string)$user->get('username');
    }

    return (string)$user->get('email');
}

function commonUserHasOrganizationAccess($userId, $organizationId)
{
    $userId = (int)$userId;
    $organizationId = (int)$organizationId;

    if ($organizationId <= 0) {
        return false;
    }

    if (commonCanAccessWithoutLogin([
        'host' => $_SERVER['HTTP_HOST'] ?? '',
        'id' => $organizationId,
    ])) {
        return true;
    }

    if ($userId <= 0) {
        return false;
    }

    $row = \dbObject\DbObject::fetchRow(
        "SELECT id
         FROM user_organization
         WHERE IDuser = :user_id
           AND IDorganization = :organization_id
           AND active = 1
         LIMIT 1",
        [
            'user_id' => $userId,
            'organization_id' => $organizationId,
        ]
    );

    return $row !== false;
}

function commonCurrentUserHasOrganizationAccess($organizationId = null)
{
    $organizationId = $organizationId !== null
        ? (int)$organizationId
        : (int)($_SESSION['currentOrganization'] ?? 0);

    return commonUserHasOrganizationAccess(commonGetCurrentUserId(), $organizationId);
}

function commonGetAccessibleOrganizations($userId)
{
    $userId = (int)$userId;
    if ($userId <= 0) {
        return [];
    }

    $rows = \dbObject\DbObject::fetchAll(
        "SELECT o.id, o.name, o.shortname, o.domain, o.logo, o.banner, o.color
         FROM user_organization uo
         INNER JOIN organization o ON o.id = uo.IDorganization
         WHERE uo.IDuser = :user_id
           AND uo.active = 1
         ORDER BY o.name ASC",
        ['user_id' => $userId]
    );

    return is_array($rows) ? $rows : [];
}

function commonLogoutUser()
{
    unset($_SESSION['currentUser']);
    unset($_SESSION['userRef']);
    unset($_SESSION['challenge']);

    commonExpireCookieValue('remember_token', true);
    setcookie('remember_token', '', time() - 3600, '/', '', commonShouldUseSecureCookies(), true);

    commonExpireCookieValue('currentUser', false);
    commonExpireCookieValue('currentCode', false);
    setcookie('currentUser', '', time() - 3600, '/');
    setcookie('currentCode', '', time() - 3600, '/');
}

function commonGetRequestIp()
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function commonNormalizeLoginCode($code)
{
    $code = strtoupper(trim((string)$code));
    return preg_replace('/[^A-Z0-9]/', '', $code);
}

function commonGenerateLoginCode($length = 6)
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $maxIndex = strlen($alphabet) - 1;
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= $alphabet[random_int(0, $maxIndex)];
    }

    return $code;
}

function commonStorePendingLoginToken($token)
{
    if ($token === null || $token === '') {
        unset($_SESSION['pending_login_token']);
        return;
    }

    $_SESSION['pending_login_token'] = (string)$token;
}

function commonSendLoginCode($userId, $email, array $organizationContext, $remember, $returnTo)
{
    $requestToken = bin2hex(random_bytes(32));
    $loginCode = commonGenerateLoginCode(6);
    $codeHash = password_hash(commonNormalizeLoginCode($loginCode), PASSWORD_DEFAULT);
    $requestIp = commonGetRequestIp();
    $loginToken = \dbObject\UserLoginToken::issue($userId, $requestToken, $codeHash, $requestIp, $remember);

    if (!$loginToken) {
        return false;
    }

    $returnTo = commonNormalizeLocalPath($returnTo, '/');
    $link = commonGetRequestScheme() . "://" . ($_SERVER['HTTP_HOST'] ?? '') . "/common/login_verify.php?token=" . urlencode($requestToken) . "&code=" . urlencode($loginCode) . "&return_to=" . urlencode($returnTo);

    $subject = "Code de connexion";
    $orgName = htmlspecialchars($organizationContext['name'] ?: ($_SERVER['HTTP_HOST'] ?? 'Organisation'));
    $color = htmlspecialchars($organizationContext['color'] ?: '#4CAF50');
    $logo = $organizationContext['logo'] ?? '';
    $banner = $organizationContext['banner'] ?? '';

    $message = "
<html>
<body style='margin:0; font-family:Arial, sans-serif; background:#f5f5f5;'>
<table width='100%' cellpadding='0' cellspacing='0'>
<tr>
<td align='center'>
<table width='600' cellpadding='0' cellspacing='0' style='background:white; border-radius:8px; overflow:hidden;'>
<tr>
<td style='background:$color; text-align:center; padding:30px 20px; position:relative;'>
    " . ($banner ? "<div style='background:url($banner) center/cover; opacity:0.3; position:absolute; inset:0;'></div>" : "") . "
    <div style='position:relative;'>
        " . ($logo ? "
        <div style='width:80px;height:80px;border-radius:50%;background:white;margin:0 auto 10px;padding:5px;'>
            <img src='$logo' style='width:100%;height:100%;object-fit:cover;border-radius:50%;'>
        </div>
        " : "") . "
        <h2 style='color:white; margin:0;'>$orgName</h2>
    </div>
</td>
</tr>
<tr>
<td style='padding:30px; text-align:center;'>
    <h3 style='margin-top:0;'>Connexion a votre espace</h3>
    <p style='color:#555;'>Saisissez ce code dans l'application pour vous connecter :</p>
    <div style='display:inline-block;padding:16px 22px;background:#f3f4f6;border-radius:12px;border:1px solid #e5e7eb;font:700 32px/1.2 Consolas, Monaco, monospace;letter-spacing:0.22em;color:#111827;margin-top:10px;'>
        $loginCode
    </div>
    <p style='margin:22px 0 0; color:#555;'>Ou cliquez simplement sur ce lien depuis le meme appareil :</p>
    <p style='margin:14px 0 0;'>
        <a href='$link' style='display:inline-block;padding:12px 20px;background:$color;color:white;text-decoration:none;border-radius:999px;font-weight:bold;'>
            Continuer la connexion
        </a>
    </p>
    <p style='margin-top:12px; font-size:12px; word-break:break-all; color:#666;'><a href='$link' style='color:#2563eb; text-decoration:underline;'>$link</a></p>
    <p style='margin-top:20px; font-size:12px; color:#888;'>Ce code est valable 5 minutes et doit etre saisi depuis le meme reseau.</p>
    <p style='margin-top:12px; font-size:12px; color:#999;'>Si votre reseau change, demandez simplement un nouveau code.</p>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";

    $fromAddress = trim((string)($GLOBALS['mailUser'] ?? ''));
    if ($fromAddress === '') {
        $host = preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $fromAddress = 'noreply@' . ($host !== '' ? $host : 'localhost');
    }

    $fromName = (string)($organizationContext['name'] ?: 'Organisation');

    commonStorePendingLoginToken($requestToken);

    if (!myHTMLMail([$fromAddress, $fromName], $email, $subject, $message)) {
        return [
            'request_token' => $requestToken,
            'return_to' => $returnTo,
            'delivery_failed' => true,
        ];
    }

    return [
        'request_token' => $requestToken,
        'return_to' => $returnTo,
    ];
}

function commonHandleMagicLoginSend($defaultReturnTo = '/')
{
    header('Content-Type: application/json; charset=UTF-8');

    $organizationContext = commonResolveOrganizationContext(1);
    $email = trim((string)($_POST['email'] ?? ''));
    $answer = $_POST['answer'] ?? null;
    $remember = isset($_POST['remember']) ? (int)$_POST['remember'] : 0;
    $returnTo = commonNormalizeLocalPath($_POST['return_to'] ?? $defaultReturnTo, $defaultReturnTo);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'email']);
        exit;
    }

    $user = new \dbObject\User();
    if ($user->load(['email', $email])) {
        $loginRequest = commonSendLoginCode((int)$user->getId(), $email, $organizationContext, $remember, $returnTo);
        if ($loginRequest === false) {
            echo json_encode(['error' => 'send_failed']);
            exit;
        }
        if (!empty($loginRequest['delivery_failed'])) {
            echo json_encode([
                'status' => 'code_pending',
                'request_token' => $loginRequest['request_token'],
                'warning' => 'delivery_uncertain',
            ]);
            exit;
        }
        echo json_encode(['status' => 'code_sent', 'request_token' => $loginRequest['request_token']]);
        exit;
    }

    if ($answer === null) {
        $numbers = [
            1 => "un", 2 => "deux", 3 => "trois", 4 => "quatre",
            5 => "cinq", 6 => "six", 7 => "sept", 8 => "huit", 9 => "neuf"
        ];

        $a = rand(2, 9);
        $b = rand(2, 9);

        $_SESSION['challenge'] = [
            'answer' => $a + $b,
            'expires' => time() + 300,
        ];

        echo json_encode(['challenge' => $numbers[$a] . " plus " . $numbers[$b]]);
        exit;
    }

    if (!isset($_SESSION['challenge'])) {
        echo json_encode(['error' => 'no_challenge']);
        exit;
    }

    $challenge = $_SESSION['challenge'];
    if (($challenge['expires'] ?? 0) < time()) {
        unset($_SESSION['challenge']);
        echo json_encode(['error' => 'expired']);
        exit;
    }

    if ((int)$answer !== (int)$challenge['answer']) {
        echo json_encode(['error' => 'wrong_answer']);
        exit;
    }

    unset($_SESSION['challenge']);

    $user = new \dbObject\User();
    $user->set('email', $email);
    $user->set('active', 0);
    $saveResult = $user->save();

    if (empty($saveResult['status'])) {
        echo json_encode(['error' => 'user_creation']);
        exit;
    }

    $loginRequest = commonSendLoginCode((int)$user->getId(), $email, $organizationContext, $remember, $returnTo);
    if ($loginRequest === false) {
        echo json_encode(['error' => 'send_failed']);
        exit;
    }
    if (!empty($loginRequest['delivery_failed'])) {
        echo json_encode([
            'status' => 'code_pending',
            'request_token' => $loginRequest['request_token'],
            'warning' => 'delivery_uncertain',
        ]);
        exit;
    }
    echo json_encode(['status' => 'code_sent', 'request_token' => $loginRequest['request_token']]);
    exit;
}

function commonHandleMagicLoginVerify($defaultReturnTo = '/')
{
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        $token = (string)($_GET['token'] ?? '');
        $code = commonNormalizeLoginCode($_GET['code'] ?? '');
        $returnTo = commonNormalizeLocalPath($_GET['return_to'] ?? $defaultReturnTo, $defaultReturnTo);

        if ($token !== '' && $code !== '') {
            $tokenJs = json_encode($token, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $codeJs = json_encode($code, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $returnToJs = json_encode($returnTo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion en cours</title>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <div class="auth-state-card">
        <h1>Connexion en cours</h1>
        <p>Nous verifions votre code sur cet appareil.</p>
        <div class="auth-state-status" id="verifyStatus">Verification...</div>
        <form id="verifyFallbackForm" method="post" action="/common/login_verify.php">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <input type="hidden" name="code" value="<?= htmlspecialchars($code) ?>">
            <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo) ?>">
            <button type="submit" class="auth-state-btn auth-state-btn--primary">Continuer</button>
        </form>
    </div>
    <script>
        (function () {
            var status = document.getElementById('verifyStatus');
            var body = new URLSearchParams();
            body.set('token', <?= $tokenJs ?>);
            body.set('code', <?= $codeJs ?>);
            body.set('return_to', <?= $returnToJs ?>);

            fetch('/common/login_verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (data.status === 'ok') {
                        status.textContent = 'Connexion reussie, redirection...';
                        window.location.href = data.redirect_to || <?= $returnToJs ?>;
                        return;
                    }

                    var message = 'Impossible de verifier ce code.';
                    if (data.error === 'ip_changed') {
                        message = 'Votre reseau a change. Demandez un nouveau code depuis l application.';
                    } else if (data.error === 'expired') {
                        message = 'Ce code a expire. Demandez un nouveau code.';
                    } else if (data.error === 'locked') {
                        message = 'Trop de tentatives. Demandez un nouveau code.';
                    }
                    status.textContent = message;
                    status.className = 'auth-state-status error';
                })
                .catch(function () {
                    status.textContent = 'Verification impossible automatiquement. Utilisez le bouton ci-dessous.';
                    status.className = 'auth-state-status error';
                });
        })();
    </script>
</body>
</html>
<?php
            exit;
        }

        http_response_code(405);
        die("Veuillez retourner dans l'application et saisir le code recu par e-mail.");
    }

    header('Content-Type: application/json; charset=UTF-8');

    $token = (string)($_POST['token'] ?? ($_SESSION['pending_login_token'] ?? ''));
    $code = commonNormalizeLoginCode($_POST['code'] ?? '');
    $returnTo = commonNormalizeLocalPath($_POST['return_to'] ?? $defaultReturnTo, $defaultReturnTo);
    $currentIp = commonGetRequestIp();

    if ($token === '' || $code === '') {
        echo json_encode(['error' => 'missing_code']);
        exit;
    }

    $loginToken = \dbObject\UserLoginToken::findByToken($token);
    if (!$loginToken) {
        commonStorePendingLoginToken(null);
        echo json_encode(['error' => 'invalid']);
        exit;
    }

    if ((int)$loginToken->get('used') > 0) {
        commonStorePendingLoginToken(null);
        echo json_encode(['error' => 'expired']);
        exit;
    }

    $expiresAt = $loginToken->get('expires_at');
    if (!$expiresAt instanceof \DateTimeInterface || $expiresAt <= new \DateTime()) {
        commonStorePendingLoginToken(null);
        echo json_encode(['error' => 'expired']);
        exit;
    }

    if ((int)$loginToken->get('attempt_count') >= 5) {
        commonStorePendingLoginToken(null);
        echo json_encode(['error' => 'locked']);
        exit;
    }

    if ((string)$loginToken->get('request_ip') !== $currentIp) {
        $loginToken->markUsed();
        commonStorePendingLoginToken(null);
        echo json_encode(['error' => 'ip_changed']);
        exit;
    }

    if (!password_verify($code, (string)$loginToken->get('code_hash'))) {
        $loginToken->incrementAttemptCount();
        $remainingAttempts = max(0, 5 - (int)$loginToken->get('attempt_count'));

        echo json_encode([
            'error' => $remainingAttempts > 0 ? 'wrong_code' : 'locked',
            'remaining_attempts' => $remainingAttempts,
        ]);
        exit;
    }

    if ((int)$loginToken->get('remember') > 0) {
        $rememberToken = bin2hex(random_bytes(32));
        $ip = $currentIp;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $browser = (strpos($ua, 'Chrome') !== false) ? 'Chrome' : ((strpos($ua, 'Firefox') !== false) ? 'Firefox' : ((strpos($ua, 'Safari') !== false) ? 'Safari' : 'Unknown'));
        $os = (strpos($ua, 'Windows') !== false) ? 'Windows' : ((strpos($ua, 'Mac') !== false) ? 'MacOS' : ((strpos($ua, 'Linux') !== false) ? 'Linux' : ((strpos($ua, 'Android') !== false) ? 'Android' : ((strpos($ua, 'iPhone') !== false) ? 'iOS' : 'Unknown'))));

        \dbObject\UserRemember::issue(
            (int)$loginToken->get('IDuser'),
            $rememberToken,
            $ip,
            $ua,
            $browser,
            $os
        );

        commonSetCookieValue('remember_token', $rememberToken, time() + (60 * 60 * 24 * 30), true);
    }

    $loginToken->markUsed();
    commonStorePendingLoginToken(null);

    $user = new \dbObject\User();
    if ($user->load((int)$loginToken->get('IDuser'))) {
        $user->set('active', 1);
        $user->save();
    }

    session_regenerate_id(true);
    $_SESSION['currentUser'] = (int)$loginToken->get('IDuser');
    echo json_encode(['status' => 'ok', 'redirect_to' => $returnTo]);
    exit;
}

function commonRenderMagicLoginPage(array $options = [])
{
    $organizationContext = $options['organization'] ?? commonResolveOrganizationContext(1);
    $title = $options['title'] ?? 'Connexion';
    $appName = $options['appName'] ?? 'Espace';
    $intro = $options['intro'] ?? 'Connectez-vous pour continuer.';
    $returnTo = commonNormalizeLocalPath($options['returnTo'] ?? ($_SERVER['REQUEST_URI'] ?? '/'), '/');
    $loginSendPath = $options['loginSendPath'] ?? '/common/login_send.php';
    $headHtml = (string)($options['headHtml'] ?? '');
    $bodyEndHtml = (string)($options['bodyEndHtml'] ?? '');

    $config = [
        'loginSendPath' => $loginSendPath,
        'loginVerifyPath' => '/common/login_verify.php',
        'returnTo' => $returnTo,
        'orgDomain' => $organizationContext['domain'] ?? '',
        'orgName' => $organizationContext['name'] ?? '',
        'hasOrgDomain' => !empty($organizationContext['domain']),
    ];

    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="/common/assets/auth.css">
    <style>
        :root {
            --auth-primary: <?= htmlspecialchars($organizationContext['color'] ?: '#4CAF50') ?>;
        }
    </style>
    <?php if ($headHtml !== ''): ?>
    <?= $headHtml . PHP_EOL ?>
    <?php endif; ?>
</head>
<body class="auth-page">
    <div class="auth-shell">
        <div class="auth-hero" style="background-color: <?= htmlspecialchars($organizationContext['color'] ?: '#4CAF50') ?>;">
            <?php if (!empty($organizationContext['banner'])) { ?>
                <div class="auth-hero-bg" style="background-image:url('<?= htmlspecialchars($organizationContext['banner']) ?>')"></div>
                <?php } else {?>
                <div class="auth-hero-bg" style="background-image:url('/img/home.jpg')"></div>
 
                <?php } ?>
            <div class="auth-hero-content">
                <?php if (!empty($organizationContext['logo'])){ ?>
                    <div class="auth-logo">
                        <img src="<?= htmlspecialchars($organizationContext['logo']) ?>" alt="Logo">
                    </div>
                    <? } else { ?>
                       <div class="auth-logo">
                        <img src="/img/logo-OGC.png" alt="Logo">
                    </div>
                <?php } ?>
                
                <h1><?= htmlspecialchars($organizationContext['name'] ?: $appName) ?></h1>
                <p><?= htmlspecialchars($intro) ?></p>
                <div class="auth-kicker"><?= htmlspecialchars($appName) ?></div>
            </div>
        </div>

        <div class="auth-card">
            <h2>Connexion</h2>
            <p class="auth-copy">Un lien de connexion vous sera envoyé par e-mail.</p>

            <div class="auth-email-row" id="authEmailRow">
                <input type="text" id="authEmailInput" placeholder="<?= !empty($organizationContext['domain']) ? 'username' : 'nom@domaine.ch' ?>" autofocus>
                <?php if (!empty($organizationContext['domain'])): ?>
                    <div class="auth-email-domain" id="authEmailDomain">@<?= htmlspecialchars($organizationContext['domain']) ?></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($organizationContext['domain'])): ?>
                <button type="button" class="auth-link-btn" id="authToggleMode">Utiliser une autre adresse e-mail</button>
            <?php endif; ?>

            <label class="auth-remember">
                <input type="checkbox" id="authRememberMe"> Se souvenir de moi sur cet appareil
            </label>

            <div id="authChallengeBox" class="auth-challenge" style="display:none;">
                <p id="authChallengeQuestion"></p>
                <input type="text" id="authChallengeAnswer" placeholder="Votre réponse">
                <button type="button" id="authChallengeSubmit">Valider</button>
            </div>

            <div id="authCodeBox" class="auth-code-box" style="display:none;">
                <p>Entrez le code recu par e-mail sur cet appareil.</p>
                <input type="text" id="authCodeInput" inputmode="text" autocomplete="one-time-code" maxlength="6" placeholder="ABC123">
                <button type="button" id="authCodeSubmit">Valider le code</button>
            </div>

            <button type="button" class="auth-link-btn auth-resend" id="authResendLink" style="display:none;">Envoyer un nouveau code</button>
            <button type="button" class="auth-submit" id="authLoginSubmit">Envoyer le code</button>
            <div id="authStatus" class="auth-status" aria-live="polite"></div>
        </div>
    </div>

    <script>
        window.commonLoginConfig = <?= json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="/common/assets/auth.js"></script>
    <?php if ($bodyEndHtml !== ''): ?>
    <?= $bodyEndHtml . PHP_EOL ?>
    <?php endif; ?>
</body>
</html>
<?php
    exit;
}
