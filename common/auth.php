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
    $host = commonGetRequestHost();

    if (
        $host === '' ||
        $host === 'localhost' ||
        preg_match('/(^|\.)localhost$/', $host) ||
        filter_var($host, FILTER_VALIDATE_IP)
    ) {
        return '';
    }

    $parts = array_values(array_filter(explode('.', $host)));
    if (count($parts) < 2) {
        return '';
    }

    return '.' . $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
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

function commonLogoutUser()
{
    unset($_SESSION['currentUser']);
    unset($_SESSION['userRef']);
    unset($_SESSION['challenge']);

    $cookieDomain = commonGetCookieDomain();

    if ($cookieDomain !== '') {
        setcookie('remember_token', '', time() - 3600, '/', $cookieDomain, true, true);
    }
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);

    if (!empty($_SERVER['HTTP_HOST'])) {
        setcookie('currentUser', '', time() - 3600, '/', $_SERVER['HTTP_HOST'], false);
        setcookie('currentCode', '', time() - 3600, '/', $_SERVER['HTTP_HOST'], false);
    }
    setcookie('currentUser', '', time() - 3600, '/');
    setcookie('currentCode', '', time() - 3600, '/');
}

function commonSendMagicLoginLink($userId, $email, array $organizationContext, $remember, $returnTo)
{
    $token = bin2hex(random_bytes(32));
    \dbObject\UserLoginToken::issue($userId, $token, $remember);

    $returnTo = commonNormalizeLocalPath($returnTo, '/');
    $link = "https://" . ($_SERVER['HTTP_HOST'] ?? '') . "/common/login_verify.php?token=" . urlencode($token) . "&return_to=" . urlencode($returnTo);

    $subject = "Connexion a votre espace";
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
    <p style='color:#555;'>Cliquez sur le bouton ci-dessous pour vous connecter :</p>
    <a href='$link' style='display:inline-block;padding:12px 20px;background:$color;color:white;text-decoration:none;border-radius:6px;font-weight:bold;margin-top:10px;'>
        Se connecter
    </a>
    <p style='margin-top:20px; font-size:12px; color:#888;'>Ce lien est valable 15 minutes.</p>
    <p style='margin-top:20px; font-size:12px; color:#999;'>Si le bouton ne fonctionne pas :</p>
    <p style='font-size:12px; word-break:break-all; color:#666;'>$link</p>
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

    return mail($email, $subject, $message, $headers);
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
        commonSendMagicLoginLink((int)$user->getId(), $email, $organizationContext, $remember, $returnTo);
        echo json_encode(['status' => 'ok']);
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

    commonSendMagicLoginLink((int)$user->getId(), $email, $organizationContext, $remember, $returnTo);
    echo json_encode(['status' => 'ok']);
    exit;
}

function commonHandleMagicLoginVerify($defaultReturnTo = '/')
{
    $token = (string)($_GET['token'] ?? '');
    $returnTo = commonNormalizeLocalPath($_GET['return_to'] ?? $defaultReturnTo, $defaultReturnTo);

    $loginToken = \dbObject\UserLoginToken::findValidByToken($token);
    if (!$loginToken) {
        http_response_code(400);
        die("Lien invalide ou expire");
    }

    if ((int)$loginToken->get('remember') > 0) {
        $rememberToken = bin2hex(random_bytes(32));
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
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

        $cookieDomain = commonGetCookieDomain();
        if ($cookieDomain !== '') {
            setcookie('remember_token', $rememberToken, time() + (60 * 60 * 24 * 30), '/', $cookieDomain, true, true);
        } else {
            setcookie('remember_token', $rememberToken, time() + (60 * 60 * 24 * 30), '/', '', true, true);
        }
    }

    $loginToken->markUsed();

    $user = new \dbObject\User();
    if ($user->load((int)$loginToken->get('IDuser'))) {
        $user->set('active', 1);
        $user->save();
    }

    $_SESSION['currentUser'] = (int)$loginToken->get('IDuser');
    header("Location: " . $returnTo);
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
            <?php if (!empty($organizationContext['banner'])): ?>
                <div class="auth-hero-bg" style="background-image:url('<?= htmlspecialchars($organizationContext['banner']) ?>')"></div>
            <?php endif; ?>
            <div class="auth-hero-content">
                <?php if (!empty($organizationContext['logo'])): ?>
                    <div class="auth-logo">
                        <img src="<?= htmlspecialchars($organizationContext['logo']) ?>" alt="Logo">
                    </div>
                <?php endif; ?>
                <div class="auth-kicker"><?= htmlspecialchars($appName) ?></div>
                <h1><?= htmlspecialchars($organizationContext['name'] ?: $appName) ?></h1>
                <p><?= htmlspecialchars($intro) ?></p>
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

            <button type="button" class="auth-submit" id="authLoginSubmit">Se connecter</button>
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
