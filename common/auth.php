<?php

require_once __DIR__ . '/environment_subdomains.php';

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

function commonGetRequestPath()
{
    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $path = parse_url($requestUri, PHP_URL_PATH);

    if (!is_string($path) || $path === '') {
        return '/';
    }

    return $path;
}

function commonGetReservedEnvironmentSubdomains()
{
    if (function_exists('appGetReservedEnvironmentSubdomains')) {
        return appGetReservedEnvironmentSubdomains();
    }
    return commonGetConfiguredEnvironmentSubdomains();
}

function commonBuildOrganizationEntryPath($organizationId)
{
    return '/omo/o/' . (int)$organizationId;
}

function commonGetHostRootPartCount(array $parts)
{
    $partCount = count($parts);
    if ($partCount === 0) {
        return 0;
    }

    if ($partCount === 2 && ($parts[1] ?? '') === 'localhost') {
        return 1;
    }

    if ($partCount >= 3) {
        $environmentCandidate = strtolower((string)($parts[$partCount - 3] ?? ''));
        if (in_array($environmentCandidate, commonGetReservedEnvironmentSubdomains(), true)) {
            return 3;
        }
    }

    return min(2, $partCount);
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
    $rootPartCount = commonGetHostRootPartCount($parts);
    if ($rootPartCount > 0 && count($parts) > $rootPartCount) {
        $host = implode('.', array_slice($parts, -$rootPartCount));
    } elseif ($rootPartCount > 0) {
        $host = implode('.', $parts);
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

function commonBuildOrganizationHomeUrl($organizationId, $shortname = '', $baseHost = null, $scheme = null)
{
    $organizationId = (int)$organizationId;
    $shortname = strtolower(trim((string)$shortname));

    if (commonUseOrganizationSubdomains() && $shortname !== '') {
        return commonBuildUrl('/omo/', commonBuildOrganizationHost($shortname, commonGetRootHost($baseHost)), $scheme);
    }

    return commonBuildUrl(commonBuildOrganizationEntryPath($organizationId), commonGetRootHost($baseHost), $scheme);
}

function commonBuildOrganizationAccessLabel($organizationId, $shortname = '', $baseHost = null)
{
    $rootHost = commonGetRootHost($baseHost);
    $shortname = strtolower(trim((string)$shortname));

    if (commonUseOrganizationSubdomains() && $shortname !== '' && $rootHost !== '') {
        return $shortname . '.' . $rootHost;
    }

    if ($rootHost === '') {
        return commonBuildOrganizationEntryPath($organizationId);
    }

    return $rootHost . commonBuildOrganizationEntryPath($organizationId);
}

function commonGetRequestSubdomain($host = null)
{
    $host = is_string($host) && $host !== '' ? strtolower($host) : commonGetRequestHost();
    $parts = array_values(array_filter(explode('.', $host)));
    $rootPartCount = commonGetHostRootPartCount($parts);
    if (count($parts) <= $rootPartCount) {
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

function commonGetRequestedOrganizationId()
{
    $path = commonGetRequestPath();
    if (preg_match('#^/omo/o/(\d+)(?:/c/\d+)?/?$#', $path, $matches)) {
        return (int)$matches[1];
    }

    if (isset($_GET['oid']) && is_numeric($_GET['oid'])) {
        return (int)$_GET['oid'];
    }

    return 0;
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

    $rootHost = preg_replace('/:\d+$/', '', commonGetRootHost($host));
    if ($rootHost === '' || $rootHost === 'localhost' || preg_match('/(^|\.)localhost$/', $rootHost)) {
        return '';
    }

    return '.' . $rootHost;
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

function commonGetRememberDurationSeconds()
{
    return \dbObject\UserRemember::lifetimeSeconds();
}

function commonGetRememberCookieName()
{
    $cookieDomain = ltrim((string)commonGetCookieDomain(), '.');
    if ($cookieDomain === '') {
        $cookieDomain = commonGetRequestHost();
    }

    $suffix = preg_replace('/[^a-z0-9]+/i', '_', strtolower((string)$cookieDomain));
    $suffix = trim((string)$suffix, '_');

    if ($suffix === '') {
        return 'remember_token';
    }

    return 'remember_token_' . $suffix;
}

function commonGetRememberCookieValue()
{
    $cookieName = commonGetRememberCookieName();
    return isset($_COOKIE[$cookieName]) ? (string)$_COOKIE[$cookieName] : '';
}

function commonExpireLegacyRememberCookie()
{
    commonExpireCookieValue('remember_token', true);
}

function commonRefreshRememberedUser($remember)
{
    if (!$remember) {
        return;
    }

    $remember->renew();
    commonSetCookieValue(
        commonGetRememberCookieName(),
        (string)$remember->get('token'),
        time() + commonGetRememberDurationSeconds(),
        true
    );
    commonExpireLegacyRememberCookie();
}

function commonGetOrganizationExplicitColor(array $organizationContext)
{
    $color = trim((string)($organizationContext['color'] ?? ''));
    if ($color === '' || stripos($color, 'var(') !== false) {
        return '';
    }

    return $color;
}

function commonGetOrganizationAccentColor(array $organizationContext, $fallback = '#004663')
{
    $color = commonGetOrganizationExplicitColor($organizationContext);
    if ($color !== '') {
        return $color;
    }

    return (string)$fallback;
}

function commonResolveOrganizationContext($defaultOrganizationId = 1)
{
    $host = commonGetRequestHost();
    $requestedOrganizationId = commonGetRequestedOrganizationId();
    $organizationSubdomainsEnabled = commonUseOrganizationSubdomains();
    $requestSubdomain = commonGetRequestSubdomain($host);
    $usePathRouting = !commonIsDemoHost($host)
        && (!$organizationSubdomainsEnabled || $requestSubdomain === '')
        && $requestedOrganizationId > 0;
    $routeMode = commonIsDemoHost($host) ? 'host' : (($organizationSubdomainsEnabled && !$usePathRouting) ? 'host' : 'path');

    if (commonIsDemoHost($host)) {
        $organization = new \dbObject\Organization();
        $organization = $organization->load(commonGetDemoOrganizationId()) ? $organization : false;
    } elseif ($usePathRouting) {
        $organization = new \dbObject\Organization();
        $organization = $organization->load($requestedOrganizationId) ? $organization : false;
    } elseif (!$organizationSubdomainsEnabled && $requestSubdomain !== '') {
        $organization = false;
    } else {
        $organization = \dbObject\Organization::resolveFromHost($host, (int)$defaultOrganizationId);
    }

    if ($organization === false) {
        $_SESSION['currentOrganization'] = -1;
        $GLOBALS['common_request_organization_id'] = -1;

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
            'routeMode' => $routeMode,
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
        'color' => trim((string)$organization->get('color')),
        'host' => $host,
        'error' => null,
        'isDemo' => commonIsDemoHost($host),
        'routeMode' => $routeMode,
    ];

    $_SESSION['currentOrganization'] = $context['id'];
    $GLOBALS['common_request_organization_id'] = $context['id'];

    return $context;
}

function commonResolveLoginActivityOrganizationId($returnTo = null)
{
    $returnTo = is_string($returnTo) ? trim($returnTo) : '';
    if ($returnTo !== '') {
        $normalizedReturnTo = commonNormalizeLocalPath($returnTo, '/');
        $returnToPath = (string)parse_url($normalizedReturnTo, PHP_URL_PATH);
        if (preg_match('#^/omo/o/(\d+)(?:/c/\d+)?/?$#', $returnToPath, $matches)) {
            return (int)$matches[1];
        }

        $returnToQuery = (string)parse_url($normalizedReturnTo, PHP_URL_QUERY);
        if ($returnToQuery !== '') {
            $params = array();
            parse_str($returnToQuery, $params);
            if (isset($params['oid']) && is_numeric($params['oid'])) {
                return (int)$params['oid'];
            }
        }
    }

    $requestedOrganizationId = commonGetRequestedOrganizationId();
    if ($requestedOrganizationId > 0) {
        return $requestedOrganizationId;
    }

    $host = commonGetRequestHost();
    if (commonUseOrganizationSubdomains() && $host !== '' && !commonIsDemoHost($host) && commonGetRequestSubdomain($host) !== '') {
        $organization = \dbObject\Organization::resolveFromHost($host, 0);
        if ($organization) {
            return (int)$organization->getId();
        }
    }

    $requestOrganizationId = (int)($GLOBALS['common_request_organization_id'] ?? 0);
    if ($requestOrganizationId > 0) {
        return $requestOrganizationId;
    }

    return 0;
}

function commonUpdateLastConnection($userId, $returnTo = null, $activateUser = false)
{
    $userId = (int)$userId;
    if ($userId <= 0) {
        return false;
    }

    $now = new \DateTimeImmutable();
    $updated = false;

    $user = new \dbObject\User();
    if ($user->load($userId)) {
        $user->set('dateconnexion', $now);
        if ($activateUser) {
            $user->set('active', 1);
        }

        $saveResult = $user->save();
        $updated = !empty($saveResult['status']);
    }

    $organizationId = commonResolveLoginActivityOrganizationId($returnTo);
    if ($organizationId > 0) {
        $membership = new \dbObject\UserOrganization();
        if ($membership->load([
            ['IDuser', $userId],
            ['IDorganization', $organizationId],
            ['active', 1],
        ])) {
            $membership->set('dateconnexion', $now);
            $saveResult = $membership->save();
            $updated = !empty($saveResult['status']) || $updated;
        }
    }

    return $updated;
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
        $currentUserId = (int)$_SESSION['currentUser'];
        $rememberCookie = commonGetRememberCookieValue();
        if ($rememberCookie !== '') {
            $remember = \dbObject\UserRemember::findValidByToken($rememberCookie);
            if ($remember && (int)$remember->get('IDuser') === $currentUserId) {
                commonRefreshRememberedUser($remember);
            }
        }

        return $currentUserId;
    }

    $rememberCookie = commonGetRememberCookieValue();
    if ($rememberCookie === '') {
        return 0;
    }

    $remember = \dbObject\UserRemember::findValidByToken($rememberCookie);
    if (!$remember) {
        commonExpireCookieValue(commonGetRememberCookieName(), true);
        return 0;
    }

    $_SESSION['currentUser'] = (int)$remember->get('IDuser');
    commonUpdateLastConnection((int)$_SESSION['currentUser']);
    commonRefreshRememberedUser($remember);
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

    $organizationId = (int)($_SESSION['currentOrganization'] ?? 0);

    $fullName = trim((string)$user->get('firstname') . ' ' . (string)$user->get('lastname'));
    if ($fullName !== '') {
        return $fullName;
    }

    $username = $user->getScopedUsername($organizationId);
    if ($username !== '') {
        return $username;
    }

    return $user->getScopedEmail($organizationId);
}

function commonUserIsSiteAdmin($userId)
{
    static $cache = array();

    $userId = (int)$userId;
    if ($userId <= 0) {
        return false;
    }

    if (array_key_exists($userId, $cache)) {
        return $cache[$userId];
    }

    $user = new \dbObject\User();
    $cache[$userId] = $user->load($userId) && $user->isSiteAdmin();

    return $cache[$userId];
}

function commonCurrentUserIsSiteAdmin()
{
    return commonUserIsSiteAdmin(commonGetCurrentUserId());
}

function commonUserHasOrganizationMembership($userId, $organizationId)
{
    static $cache = array();

    $userId = (int)$userId;
    $organizationId = (int)$organizationId;
    $cacheKey = $userId . ':' . $organizationId;

    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    if ($userId <= 0 || $organizationId <= 0) {
        $cache[$cacheKey] = false;
        return false;
    }

    if (commonUserIsSiteAdmin($userId)) {
        $cache[$cacheKey] = true;
        return true;
    }

    $hasMembership = \dbObject\DbObject::fetchValue(
        "SELECT 1
        FROM user_organization
        WHERE IDuser = :user_id
          AND IDorganization = :organization_id
          AND active = 1
        LIMIT 1",
        array(
            'user_id' => $userId,
            'organization_id' => $organizationId,
        )
    );

    $cache[$cacheKey] = $hasMembership !== false && $hasMembership !== null;

    return $cache[$cacheKey];
}

function commonNormalizeShareToken($token)
{
    $token = trim((string)$token);
    if ($token === '') {
        return '';
    }

    return (string)preg_replace('/[^A-Za-z0-9\-_]/', '', $token);
}

function commonGetCurrentShareToken()
{
    static $resolved = false;
    static $token = '';

    if ($resolved) {
        return $token;
    }

    $resolved = true;

    if (isset($_POST['token'])) {
        $token = commonNormalizeShareToken($_POST['token']);
        return $token;
    }

    if (isset($_GET['token'])) {
        $token = commonNormalizeShareToken($_GET['token']);
        return $token;
    }

    return $token;
}

function commonIsSharePasswordVerified($token)
{
    $token = commonNormalizeShareToken($token);
    if ($token === '') {
        return false;
    }

    return !empty($_SESSION['omo_share_password_verified'][$token]);
}

function commonRememberSharePasswordVerified($token)
{
    $token = commonNormalizeShareToken($token);
    if ($token === '') {
        return false;
    }

    if (!isset($_SESSION['omo_share_password_verified']) || !is_array($_SESSION['omo_share_password_verified'])) {
        $_SESSION['omo_share_password_verified'] = array();
    }

    $_SESSION['omo_share_password_verified'][$token] = time();
    return true;
}

function commonForgetSharePasswordVerified($token = null)
{
    if ($token === null) {
        unset($_SESSION['omo_share_password_verified']);
        return;
    }

    $token = commonNormalizeShareToken($token);
    if ($token === '' || empty($_SESSION['omo_share_password_verified']) || !is_array($_SESSION['omo_share_password_verified'])) {
        return;
    }

    unset($_SESSION['omo_share_password_verified'][$token]);
    if (count($_SESSION['omo_share_password_verified']) === 0) {
        unset($_SESSION['omo_share_password_verified']);
    }
}

function commonGetCurrentShareLink($requirePasswordVerified = true)
{
    static $cache = array();

    $token = commonGetCurrentShareToken();
    if ($token === '') {
        return null;
    }

    $cacheKey = $token . ':' . ($requirePasswordVerified ? '1' : '0');
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $link = \dbObject\HolonShareLink::findValidByToken($token);
    if (!$link) {
        commonForgetSharePasswordVerified($token);
        $cache[$cacheKey] = null;
        return null;
    }

    if ($requirePasswordVerified && $link->requiresPassword() && !commonIsSharePasswordVerified($token)) {
        $cache[$cacheKey] = null;
        return null;
    }

    $cache[$cacheKey] = $link;
    return $cache[$cacheKey];
}

function commonCurrentShareCanViewOrganization($organizationId)
{
    $link = commonGetCurrentShareLink();
    return $link ? $link->canViewOrganization((int)$organizationId) : false;
}

function commonCurrentShareCanViewHolon($holon)
{
    $link = commonGetCurrentShareLink();
    return $link && $holon instanceof \dbObject\Holon ? $link->canViewHolon($holon) : false;
}

function commonCurrentShareContainsHolon($holon)
{
    $link = commonGetCurrentShareLink();
    return $link && $holon instanceof \dbObject\Holon ? $link->containsHolon($holon) : false;
}

function commonCurrentShareCanViewUser($user, $requireDetail = false)
{
    $link = commonGetCurrentShareLink();
    return $link && $user instanceof \dbObject\User ? $link->canViewUser($user, $requireDetail) : false;
}

function commonCurrentShareAllowsStructure()
{
    $link = commonGetCurrentShareLink();
    return $link ? $link->allowsStructure() : false;
}

function commonCurrentShareAllowsPeople()
{
    $link = commonGetCurrentShareLink();
    return $link ? $link->allowsPeople() : false;
}

function commonCurrentShareAllowsPeopleDetail()
{
    $link = commonGetCurrentShareLink();
    return $link ? $link->allowsPeopleDetail() : false;
}

function commonUserCanViewOrganization($userId, $organizationId)
{
    $userId = (int)$userId;
    $organizationId = (int)$organizationId;

    if ($organizationId <= 0) {
        return false;
    }

    if (commonCanAccessWithoutLogin(array(
        'host' => $_SERVER['HTTP_HOST'] ?? '',
        'id' => $organizationId,
    ))) {
        return true;
    }

    if (commonUserHasOrganizationMembership($userId, $organizationId)) {
        return true;
    }

    return commonCurrentShareCanViewOrganization($organizationId);
}

function commonUserHasOrganizationAccess($userId, $organizationId)
{
    return commonUserCanViewOrganization($userId, $organizationId);
}

function commonCurrentUserHasOrganizationAccess($organizationId = null)
{
    $organizationId = $organizationId !== null
        ? (int)$organizationId
        : (int)($_SESSION['currentOrganization'] ?? 0);

    if ($organizationId <= 0) {
        return false;
    }

    $organization = new \dbObject\Organization();
    if (!$organization->load($organizationId)) {
        return false;
    }

    return $organization->canViewDetail();
}

function commonLogoutUser()
{
    unset($_SESSION['currentUser']);
    unset($_SESSION['userRef']);
    unset($_SESSION['challenge']);

    commonExpireCookieValue(commonGetRememberCookieName(), true);
    commonExpireLegacyRememberCookie();

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

function commonHashUserPassword($password)
{
    return password_hash((string)$password, PASSWORD_DEFAULT);
}

function commonVerifyUserPassword($password, $hash)
{
    $password = (string)$password;
    $hash = (string)$hash;

    if ($password === '' || $hash === '') {
        return false;
    }

    return password_verify($password, $hash);
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

function commonStripLoginFeedbackParams($path, $fallback = '/')
{
    $normalized = commonNormalizeLocalPath($path, $fallback);
    $parsedPath = parse_url($normalized, PHP_URL_PATH);
    $query = parse_url($normalized, PHP_URL_QUERY);
    $fragment = parse_url($normalized, PHP_URL_FRAGMENT);

    $params = [];
    if (is_string($query) && $query !== '') {
        parse_str($query, $params);
    }

    unset(
        $params['login_error'],
        $params['login_message'],
        $params['login_status_type'],
        $params['login_token'],
        $params['login_remaining_attempts']
    );

    $rebuilt = is_string($parsedPath) && $parsedPath !== '' ? $parsedPath : $fallback;
    if ($params !== []) {
        $rebuilt .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
    if (is_string($fragment) && $fragment !== '') {
        $rebuilt .= '#' . $fragment;
    }

    return $rebuilt;
}

function commonBuildLoginFeedbackUrl($returnTo, array $params = [])
{
    $base = commonStripLoginFeedbackParams($returnTo, '/');
    $path = parse_url($base, PHP_URL_PATH);
    $query = parse_url($base, PHP_URL_QUERY);
    $fragment = parse_url($base, PHP_URL_FRAGMENT);

    $merged = [];
    if (is_string($query) && $query !== '') {
        parse_str($query, $merged);
    }

    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $merged[(string)$key] = (string)$value;
    }

    $rebuilt = is_string($path) && $path !== '' ? $path : '/';
    if ($merged !== []) {
        $rebuilt .= '?' . http_build_query($merged, '', '&', PHP_QUERY_RFC3986);
    }
    if (is_string($fragment) && $fragment !== '') {
        $rebuilt .= '#' . $fragment;
    }

    return $rebuilt;
}

function commonIsAjaxJsonRequest()
{
    $requestedWith = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    if ($requestedWith === 'xmlhttprequest') {
        return true;
    }

    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    return strpos($accept, 'application/json') !== false;
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
    $color = htmlspecialchars(commonGetOrganizationAccentColor($organizationContext, '#004663'));
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
        $host = preg_replace('/:\d+$/', '', commonGetRootHost() ?: 'localhost');
        $fromAddress = 'noreply@' . ($host !== '' ? $host : 'localhost');
    }

    $fromName = (string)($organizationContext['name'] ?: 'Organisation');

    commonStorePendingLoginToken($requestToken);

    if (!myHTMLMail([$fromAddress, $fromName], $email, $subject, $message)) {
        $response = [
            'request_token' => $requestToken,
            'return_to' => $returnTo,
            'delivery_failed' => true,
        ];
        if (function_exists('appShouldExposeDevDiagnostics') && appShouldExposeDevDiagnostics()) {
            $response['mail_error'] = function_exists('appGetLastMailError') ? appGetLastMailError() : '';
        }
        return $response;
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
            $response = [
                'status' => 'code_pending',
                'request_token' => $loginRequest['request_token'],
                'warning' => 'delivery_uncertain',
            ];
            if (!empty($loginRequest['mail_error'])) {
                $response['mail_error'] = (string)$loginRequest['mail_error'];
            }
            echo json_encode($response);
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
            var form = document.getElementById('verifyFallbackForm');
            if (!form) {
                status.textContent = 'Verification impossible automatiquement. Utilisez le bouton ci-dessous.';
                status.className = 'auth-state-status error';
                return;
            }
            form.submit();
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

    $token = (string)($_POST['token'] ?? ($_SESSION['pending_login_token'] ?? ''));
    $code = commonNormalizeLoginCode($_POST['code'] ?? '');
    $returnTo = commonNormalizeLocalPath($_POST['return_to'] ?? $defaultReturnTo, $defaultReturnTo);
    $currentIp = commonGetRequestIp();
    $wantsJson = commonIsAjaxJsonRequest();

    if ($wantsJson) {
        header('Content-Type: application/json; charset=UTF-8');
    }

    $respondError = function ($error, array $extra = []) use ($wantsJson, $returnTo) {
        $payload = array_merge(['error' => $error], $extra);

        if ($wantsJson) {
            echo json_encode($payload);
            exit;
        }

        $params = [
            'login_error' => $error,
            'login_token' => $extra['login_token'] ?? ($_POST['token'] ?? ''),
            'login_remaining_attempts' => $extra['remaining_attempts'] ?? null,
        ];

        header('Location: ' . commonBuildLoginFeedbackUrl($returnTo, $params));
        exit;
    };

    if ($token === '' || $code === '') {
        $respondError('missing_code');
    }

    $loginToken = \dbObject\UserLoginToken::findByToken($token);
    if (!$loginToken) {
        commonStorePendingLoginToken(null);
        $respondError('invalid');
    }

    if ((int)$loginToken->get('used') > 0) {
        commonStorePendingLoginToken(null);
        $respondError('expired');
    }

    $expiresAt = $loginToken->get('expires_at');
    if (!$expiresAt instanceof \DateTimeInterface || $expiresAt <= new \DateTime()) {
        commonStorePendingLoginToken(null);
        $respondError('expired');
    }

    if ((int)$loginToken->get('attempt_count') >= 5) {
        commonStorePendingLoginToken(null);
        $respondError('locked');
    }

    if ((string)$loginToken->get('request_ip') !== $currentIp) {
        $loginToken->markUsed();
        commonStorePendingLoginToken(null);
        $respondError('ip_changed');
    }

    if (!password_verify($code, (string)$loginToken->get('code_hash'))) {
        $loginToken->incrementAttemptCount();
        $remainingAttempts = max(0, 5 - (int)$loginToken->get('attempt_count'));
        $respondError(
            $remainingAttempts > 0 ? 'wrong_code' : 'locked',
            [
                'remaining_attempts' => $remainingAttempts,
                'login_token' => $token,
            ]
        );
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

        commonSetCookieValue(
            commonGetRememberCookieName(),
            $rememberToken,
            time() + commonGetRememberDurationSeconds(),
            true
        );
        commonExpireLegacyRememberCookie();
    }

    $loginToken->markUsed();
    commonStorePendingLoginToken(null);

    commonUpdateLastConnection((int)$loginToken->get('IDuser'), $returnTo, true);

    session_regenerate_id(true);
    $_SESSION['currentUser'] = (int)$loginToken->get('IDuser');
    session_write_close();

    if ($wantsJson) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => 'ok', 'redirect_to' => $returnTo]);
        exit;
    }

    header('Location: ' . $returnTo);
    exit;
}

function commonRenderMagicLoginPage(array $options = [])
{
    $organizationContext = $options['organization'] ?? commonResolveOrganizationContext(1);
    $title = $options['title'] ?? 'Connexion';
    $appName = $options['appName'] ?? 'Espace';
    $intro = $options['intro'] ?? 'Connectez-vous pour continuer.';
    $returnTo = commonStripLoginFeedbackParams($options['returnTo'] ?? ($_SERVER['REQUEST_URI'] ?? '/'), '/');
    $loginSendPath = $options['loginSendPath'] ?? '/common/login_send.php';
    $headHtml = (string)($options['headHtml'] ?? '');
    $bodyEndHtml = (string)($options['bodyEndHtml'] ?? '');
    $topbar = !empty($options['topbar']) && is_array($options['topbar']) ? $options['topbar'] : null;

    $config = [
        'loginSendPath' => $loginSendPath,
        'loginVerifyPath' => '/common/login_verify.php',
        'returnTo' => $returnTo,
        'orgDomain' => $organizationContext['domain'] ?? '',
        'orgName' => $organizationContext['name'] ?? '',
        'hasOrgDomain' => !empty($organizationContext['domain']),
        'initialPendingToken' => (string)($_GET['login_token'] ?? ''),
        'initialError' => (string)($_GET['login_error'] ?? ''),
        'initialRemainingAttempts' => (int)($_GET['login_remaining_attempts'] ?? 0),
    ];

    $organizationLogo = !empty($organizationContext['logo'])
        ? (string)$organizationContext['logo']
        : '/img/logo-OGC.png';
    $organizationBanner = !empty($organizationContext['banner'])
        ? (string)$organizationContext['banner']
        : '/img/home.jpg';
    $organizationColor = commonGetOrganizationExplicitColor($organizationContext);

    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="/common/assets/auth.css">
    <?php if ($organizationColor !== ''): ?>
    <style>
        :root {
            --color-primary: <?= htmlspecialchars($organizationColor) ?>;
            --auth-primary: <?= htmlspecialchars($organizationColor) ?>;
        }
    </style>
    <?php endif; ?>
    <?php if ($headHtml !== ''): ?>
    <?= $headHtml . PHP_EOL ?>
    <?php endif; ?>
</head>
<body class="auth-page<?= $topbar !== null ? ' auth-page--with-topbar' : '' ?>">
    <?php if ($topbar !== null && function_exists('commonRenderTopbar')): ?>
    <?php commonRenderTopbar($topbar); ?>
    <?php endif; ?>
    <div class="auth-shell">
        <div class="auth-hero" style="background-color: var(--auth-primary, var(--color-primary, #004663));">
            <div class="auth-hero-bg" style="background-image:url('<?= htmlspecialchars($organizationBanner) ?>')"></div>
            <div class="auth-hero-content">
                <div class="auth-logo">
                    <img src="<?= htmlspecialchars($organizationLogo) ?>" alt="Logo">
                </div>
                
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
            <form id="authVerifyForm" method="post" action="/common/login_verify.php" style="display:none;">
                <input type="hidden" name="token" id="authVerifyToken" value="">
                <input type="hidden" name="code" id="authVerifyCode" value="">
                <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo) ?>">
            </form>

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
