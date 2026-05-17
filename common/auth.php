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

function commonAuthEnsureTranslationHelpers()
{
    if (!function_exists('loadTranslationBundle')) {
        require_once __DIR__ . '/translation_bundles.php';
    }
}

function commonAuthGetTranslationLocale()
{
    return translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr');
}

function commonGetAuthSharedSourceLang(): array
{
    return [
        'auth.button.resend_code' => [
            'text' => 'Envoyer un nouveau code',
            'context' => 'Resend button label in the shared authentication user interface.'
        ],
        'auth.button.send_code' => [
            'text' => 'Envoyer le code',
            'context' => 'Primary action button label in the shared authentication user interface.'
        ],
        'auth.button.validate' => [
            'text' => 'Valider',
            'context' => 'Validation button label in the shared authentication user interface.'
        ],
        'auth.button.validate_code' => [
            'text' => 'Valider le code',
            'context' => 'Code verification button label in the shared authentication user interface.'
        ],
        'auth.challenge.answer_placeholder' => [
            'text' => 'Votre reponse',
            'context' => 'Placeholder in the anti-spam challenge answer field of the shared authentication user interface.'
        ],
        'auth.code.instructions' => [
            'text' => 'Entrez le code recu par e-mail sur cet appareil.',
            'context' => 'Instruction shown above the email code field in the shared authentication user interface.'
        ],
        'auth.code.placeholder' => [
            'text' => 'ABC123',
            'context' => 'Placeholder shown in the email code input field in the shared authentication user interface.'
        ],
        'auth.copy.login_code' => [
            'text' => 'Un code de connexion vous sera envoye par e-mail. Il reste valable 5 minutes.',
            'context' => 'Introductory helper text in the shared authentication user interface.'
        ],
        'auth.placeholder.full_email' => [
            'text' => 'nom@domaine.ch',
            'context' => 'Email input placeholder when a full email address is expected in the shared authentication user interface.'
        ],
        'auth.placeholder.username' => [
            'text' => 'username',
            'context' => 'Email input placeholder when the organization domain is appended automatically in the shared authentication user interface.'
        ],
        'auth.remember_me' => [
            'text' => 'Se souvenir de moi sur cet appareil',
            'context' => 'Checkbox label to remember the user on the current device in the shared authentication user interface.'
        ],
        'auth.toggle.use_other_email' => [
            'text' => 'Utiliser une autre adresse e-mail',
            'context' => 'Toggle label to switch away from the organization email mode in the shared authentication user interface.'
        ],
    ];
}

function commonGetAuthPhpSourceLang(): array
{
    return array_merge(commonGetAuthSharedSourceLang(), [
        'auth.challenge.number.1' => [
            'text' => 'un',
            'context' => 'Word for the number one in the arithmetic anti-spam challenge generated by the authentication PHP endpoint.'
        ],
        'auth.challenge.number.2' => [
            'text' => 'deux',
            'context' => 'Word for the number two in the arithmetic anti-spam challenge generated by the authentication PHP endpoint.'
        ],
        'auth.challenge.number.3' => [
            'text' => 'trois',
            'context' => 'Word for the number three in the arithmetic anti-spam challenge generated by the authentication PHP endpoint.'
        ],
        'auth.challenge.number.4' => [
            'text' => 'quatre',
            'context' => 'Word for the number four in the arithmetic anti-spam challenge generated by the authentication PHP endpoint.'
        ],
        'auth.challenge.number.5' => [
            'text' => 'cinq',
            'context' => 'Word for the number five in the arithmetic anti-spam challenge generated by the authentication PHP endpoint.'
        ],
        'auth.challenge.number.6' => [
            'text' => 'six',
            'context' => 'Word for the number six in the arithmetic anti-spam challenge generated by the authentication PHP endpoint.'
        ],
        'auth.challenge.number.7' => [
            'text' => 'sept',
            'context' => 'Word for the number seven in the arithmetic anti-spam challenge generated by the authentication PHP endpoint.'
        ],
        'auth.challenge.number.8' => [
            'text' => 'huit',
            'context' => 'Word for the number eight in the arithmetic anti-spam challenge generated by the authentication PHP endpoint.'
        ],
        'auth.challenge.number.9' => [
            'text' => 'neuf',
            'context' => 'Word for the number nine in the arithmetic anti-spam challenge generated by the authentication PHP endpoint.'
        ],
        'auth.challenge.operator.plus' => [
            'text' => 'plus',
            'context' => 'Operator word used between the two numbers in the arithmetic anti-spam challenge generated by the authentication PHP endpoint.'
        ],
        'auth.challenge.prompt' => [
            'text' => '{left} {operator} {right}',
            'context' => 'Arithmetic anti-spam challenge text returned by the authentication PHP endpoint.'
        ],
        'auth.button.continue' => [
            'text' => 'Continuer',
            'context' => 'Fallback form submit button label on the authentication verification page.'
        ],
        'auth.email.body.connection_heading' => [
            'text' => 'Connexion a votre espace',
            'context' => 'Heading shown in the authentication email containing the login code.'
        ],
        'auth.email.body.from_name_fallback' => [
            'text' => 'Organisation',
            'context' => 'Fallback sender name used in the authentication email when no organization name is available.'
        ],
        'auth.email.body.continue_button' => [
            'text' => 'Continuer la connexion',
            'context' => 'Primary call to action button label in the authentication email.'
        ],
        'auth.email.body.enter_code' => [
            'text' => "Saisissez ce code dans l'application pour vous connecter :",
            'context' => 'Instruction shown above the login code in the authentication email.'
        ],
        'auth.email.body.network_notice' => [
            'text' => 'Si votre reseau change, demandez simplement un nouveau code.',
            'context' => 'Security notice shown at the bottom of the authentication email.'
        ],
        'auth.email.body.open_link' => [
            'text' => 'Ou cliquez simplement sur ce lien depuis le meme appareil :',
            'context' => 'Instruction shown above the direct login link in the authentication email.'
        ],
        'auth.email.body.validity_notice' => [
            'text' => 'Ce code est valable 5 minutes et doit etre saisi depuis le meme reseau.',
            'context' => 'Validity and security notice shown at the bottom of the authentication email.'
        ],
        'auth.email.subject' => [
            'text' => 'Code de connexion',
            'context' => 'Email subject for the authentication code email.'
        ],
        'auth.page.invalid_request' => [
            'text' => "Veuillez retourner dans l'application et saisir le code recu par e-mail.",
            'context' => 'Message shown when the authentication verification page is opened without a valid token and code.'
        ],
        'auth.page.language_label' => [
            'text' => 'Langue',
            'context' => 'Label of the language selector shown on the shared authentication login page.'
        ],
        'auth.page.language_system_label' => [
            'text' => 'Systeme',
            'context' => 'System language option label shown in the shared authentication login page selector.'
        ],
        'auth.page.login.app_default' => [
            'text' => 'Espace',
            'context' => 'Default application name shown in the shared authentication page hero.'
        ],
        'auth.page.login.intro_default' => [
            'text' => 'Connectez-vous pour continuer.',
            'context' => 'Default introductory text shown in the shared authentication page hero.'
        ],
        'auth.page.login.title_default' => [
            'text' => 'Connexion',
            'context' => 'Default page title shown in the shared authentication page.'
        ],
        'auth.page.logo_alt' => [
            'text' => 'Logo',
            'context' => 'Alternative text for the organization logo in the shared authentication page.'
        ],
        'auth.page.verify.auto_unavailable' => [
            'text' => 'Verification impossible automatiquement. Utilisez le bouton ci-dessous.',
            'context' => 'Message shown when the automatic verification submission is unavailable on the authentication verification page.'
        ],
        'auth.page.verify.description' => [
            'text' => 'Nous verifions votre code sur cet appareil.',
            'context' => 'Description shown on the authentication verification page before automatic form submission.'
        ],
        'auth.page.verify.heading' => [
            'text' => 'Connexion en cours',
            'context' => 'Heading shown on the authentication verification page.'
        ],
        'auth.page.verify.status' => [
            'text' => 'Verification...',
            'context' => 'Status text shown on the authentication verification page while the code is being checked.'
        ],
        'auth.page.verify.title' => [
            'text' => 'Connexion en cours',
            'context' => 'HTML page title for the authentication verification page.'
        ],
    ]);
}

function commonGetAuthJsSourceLang(): array
{
    return array_merge(commonGetAuthSharedSourceLang(), [
        'auth.button.send_other_challenge' => [
            'text' => 'Envoyer un autre calcul',
            'context' => 'Primary action button label after a challenge is already displayed in the shared authentication JavaScript component.'
        ],
        'auth.button.validate_and_send_code' => [
            'text' => 'Valider et envoyer le code',
            'context' => 'Challenge validation button label when it also triggers the email code sending in the shared authentication JavaScript component.'
        ],
        'auth.error.ask_new_code_first' => [
            'text' => "Demandez d'abord un nouveau code.",
            'context' => 'Error shown in the shared authentication JavaScript component when the user tries to verify a code without a pending login token.'
        ],
        'auth.error.challenge_expired' => [
            'text' => 'Le defi a expire. Relancez la connexion.',
            'context' => 'Error shown in the shared authentication JavaScript component when the anti-spam challenge expired.'
        ],
        'auth.error.enter_full_code' => [
            'text' => 'Veuillez saisir le code complet a 6 caracteres.',
            'context' => 'Error shown in the shared authentication JavaScript component when the user entered an incomplete code.'
        ],
        'auth.error.expired' => [
            'text' => 'Le code a expire. Demandez un nouveau code.',
            'context' => 'Error shown in the shared authentication JavaScript component when the email code expired.'
        ],
        'auth.error.invalid_code' => [
            'text' => 'Code invalide. Demandez un nouveau code.',
            'context' => 'Fallback error shown in the shared authentication JavaScript component when the provided code is invalid.'
        ],
        'auth.error.invalid_email' => [
            'text' => 'Veuillez saisir une adresse e-mail valide.',
            'context' => 'Error shown in the shared authentication JavaScript component when the provided email is invalid.'
        ],
        'auth.error.ip_changed' => [
            'text' => 'Votre reseau a change. Pour votre securite, demandez un nouveau code.',
            'context' => 'Error shown in the shared authentication JavaScript component when the request IP changed between code request and verification.'
        ],
        'auth.error.locked' => [
            'text' => 'Trop d essais. Demandez un nouveau code.',
            'context' => 'Error shown in the shared authentication JavaScript component after too many invalid code attempts.'
        ],
        'auth.error.missing_code' => [
            'text' => 'Veuillez saisir le code recu par e-mail.',
            'context' => 'Error shown in the shared authentication JavaScript component when no verification code was provided.'
        ],
        'auth.error.request_failed' => [
            'text' => "Impossible d'envoyer la demande.",
            'context' => 'Error shown in the shared authentication JavaScript component when the AJAX request to start login fails.'
        ],
        'auth.error.restart_login' => [
            'text' => 'Merci de relancer la connexion.',
            'context' => 'Error shown in the shared authentication JavaScript component when the anti-spam flow must be restarted.'
        ],
        'auth.error.send_failed' => [
            'text' => "Impossible d'envoyer le code par e-mail.",
            'context' => 'Error shown in the shared authentication JavaScript component when the server cannot send the email code.'
        ],
        'auth.error.unexpected' => [
            'text' => 'Une erreur est survenue.',
            'context' => 'Generic error shown in the shared authentication JavaScript component for unexpected responses.'
        ],
        'auth.error.verify_failed' => [
            'text' => 'Impossible de verifier le code.',
            'context' => 'Error shown in the shared authentication JavaScript component when the AJAX request to verify the code fails.'
        ],
        'auth.error.wrong_answer' => [
            'text' => 'Reponse incorrecte. Merci de reessayer.',
            'context' => 'Error shown in the shared authentication JavaScript component when the anti-spam challenge answer is wrong.'
        ],
        'auth.error.wrong_code' => [
            'text' => 'Code incorrect. Il reste {count} essai(s).',
            'context' => 'Error shown in the shared authentication JavaScript component when the verification code is wrong and the remaining attempts count is displayed.'
        ],
        'auth.status.answer_verification' => [
            'text' => 'Veuillez repondre a la question de verification.',
            'context' => 'Status message shown in the shared authentication JavaScript component when a challenge question is displayed.'
        ],
        'auth.status.code_pending' => [
            'text' => "Le code a peut-etre deja ete envoye. Si vous l'avez recu, saisissez-le ci-dessous.",
            'context' => 'Success status shown in the shared authentication JavaScript component when code delivery is uncertain but a token was created.'
        ],
        'auth.status.code_sent' => [
            'text' => 'Le code de connexion a ete envoye par e-mail.',
            'context' => 'Success status shown in the shared authentication JavaScript component after the code email was sent.'
        ],
        'auth.status.enter_received_code' => [
            'text' => 'Saisissez le code recu par e-mail.',
            'context' => 'Status message shown in the shared authentication JavaScript component when a pending token already exists.'
        ],
        'auth.status.sending' => [
            'text' => 'Envoi en cours...',
            'context' => 'Status message shown in the shared authentication JavaScript component while the code request is being sent.'
        ],
        'auth.status.verifying_code' => [
            'text' => 'Verification du code...',
            'context' => 'Status message shown in the shared authentication JavaScript component while the verification code is being checked.'
        ],
        'auth.toggle.use_org_email' => [
            'text' => "Utiliser l'adresse de l'organisation",
            'context' => 'Toggle label to switch to the organization email mode in the shared authentication JavaScript component.'
        ],
    ]);
}

function commonAuthLoadBundle(string $bundleKey, array $sourceLang, ?string $locale = null): array
{
    commonAuthEnsureTranslationHelpers();

    $locale = $locale !== null && $locale !== '' ? $locale : commonAuthGetTranslationLocale();
    $cacheKey = $bundleKey . '|' . $locale;
    static $cache = [];

    if (!isset($cache[$cacheKey])) {
        $cache[$cacheKey] = loadTranslationBundle($bundleKey, $locale, $sourceLang);
    }

    return $cache[$cacheKey];
}

function commonAuthT(string $key, array $variables = [], ?array $bundle = null, ?array $sourceLang = null): string
{
    commonAuthEnsureTranslationHelpers();

    return translationBundleTranslate($key, $variables, $bundle, $sourceLang);
}

function commonBuildAuthChallengeLabel(int $leftNumber, int $rightNumber, ?array $bundle = null, ?array $sourceLang = null): string
{
    $sourceLang = is_array($sourceLang) ? $sourceLang : commonGetAuthPhpSourceLang();

    return commonAuthT('auth.challenge.prompt', [
        'left' => commonAuthT('auth.challenge.number.' . $leftNumber, [], $bundle, $sourceLang),
        'operator' => commonAuthT('auth.challenge.operator.plus', [], $bundle, $sourceLang),
        'right' => commonAuthT('auth.challenge.number.' . $rightNumber, [], $bundle, $sourceLang),
    ], $bundle, $sourceLang);
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
    $sourceLang = commonGetAuthPhpSourceLang();
    $lang = commonAuthLoadBundle('common_auth_page', $sourceLang);
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

    $subject = commonAuthT('auth.email.subject', [], $lang, $sourceLang);
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
    <h3 style='margin-top:0;'>" . htmlspecialchars(commonAuthT('auth.email.body.connection_heading', [], $lang, $sourceLang)) . "</h3>
    <p style='color:#555;'>" . htmlspecialchars(commonAuthT('auth.email.body.enter_code', [], $lang, $sourceLang)) . "</p>
    <div style='display:inline-block;padding:16px 22px;background:#f3f4f6;border-radius:12px;border:1px solid #e5e7eb;font:700 32px/1.2 Consolas, Monaco, monospace;letter-spacing:0.22em;color:#111827;margin-top:10px;'>
        $loginCode
    </div>
    <p style='margin:22px 0 0; color:#555;'>" . htmlspecialchars(commonAuthT('auth.email.body.open_link', [], $lang, $sourceLang)) . "</p>
    <p style='margin:14px 0 0;'>
        <a href='$link' style='display:inline-block;padding:12px 20px;background:$color;color:white;text-decoration:none;border-radius:999px;font-weight:bold;'>
            " . htmlspecialchars(commonAuthT('auth.email.body.continue_button', [], $lang, $sourceLang)) . "
        </a>
    </p>
    <p style='margin-top:12px; font-size:12px; word-break:break-all; color:#666;'><a href='$link' style='color:#2563eb; text-decoration:underline;'>$link</a></p>
    <p style='margin-top:20px; font-size:12px; color:#888;'>" . htmlspecialchars(commonAuthT('auth.email.body.validity_notice', [], $lang, $sourceLang)) . "</p>
    <p style='margin-top:12px; font-size:12px; color:#999;'>" . htmlspecialchars(commonAuthT('auth.email.body.network_notice', [], $lang, $sourceLang)) . "</p>
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

    $fromName = (string)($organizationContext['name'] ?: commonAuthT('auth.email.body.from_name_fallback', [], $lang, $sourceLang));

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

    $sourceLang = commonGetAuthPhpSourceLang();
    $lang = commonAuthLoadBundle('common_auth_page', $sourceLang);
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
        $a = rand(2, 9);
        $b = rand(2, 9);

        $_SESSION['challenge'] = [
            'answer' => $a + $b,
            'expires' => time() + 300,
        ];

        echo json_encode(['challenge' => commonBuildAuthChallengeLabel($a, $b, $lang, $sourceLang)]);
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
    $sourceLang = commonGetAuthPhpSourceLang();
    $lang = commonAuthLoadBundle('common_auth_page', $sourceLang);

    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        $token = (string)($_GET['token'] ?? '');
        $code = commonNormalizeLoginCode($_GET['code'] ?? '');
        $returnTo = commonNormalizeLocalPath($_GET['return_to'] ?? $defaultReturnTo, $defaultReturnTo);

        if ($token !== '' && $code !== '') {
            ?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(commonAuthGetTranslationLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(commonAuthT('auth.page.verify.title', [], $lang, $sourceLang)) ?></title>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <div class="auth-state-card">
        <h1><?= htmlspecialchars(commonAuthT('auth.page.verify.heading', [], $lang, $sourceLang)) ?></h1>
        <p><?= htmlspecialchars(commonAuthT('auth.page.verify.description', [], $lang, $sourceLang)) ?></p>
        <div class="auth-state-status" id="verifyStatus"><?= htmlspecialchars(commonAuthT('auth.page.verify.status', [], $lang, $sourceLang)) ?></div>
        <form id="verifyFallbackForm" method="post" action="/common/login_verify.php">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <input type="hidden" name="code" value="<?= htmlspecialchars($code) ?>">
            <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo) ?>">
            <button type="submit" class="auth-state-btn auth-state-btn--primary"><?= htmlspecialchars(commonAuthT('auth.button.continue', [], $lang, $sourceLang)) ?></button>
        </form>
    </div>
    <script>
        (function () {
            var status = document.getElementById('verifyStatus');
            var form = document.getElementById('verifyFallbackForm');
            if (!form) {
                status.textContent = <?= json_encode(commonAuthT('auth.page.verify.auto_unavailable', [], $lang, $sourceLang), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
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
        die(commonAuthT('auth.page.invalid_request', [], $lang, $sourceLang));
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
    $sourceLang = commonGetAuthPhpSourceLang();
    $lang = commonAuthLoadBundle('common_auth_page', $sourceLang);
    $languageOptions = translationBundleGetLanguageOptions();
    $currentLocalePreference = translationBundleGetRequestLocalePreference('lang');
    $resolvedLocale = commonAuthGetTranslationLocale();
    $organizationContext = $options['organization'] ?? commonResolveOrganizationContext(1);
    $title = $options['title'] ?? commonAuthT('auth.page.login.title_default', [], $lang, $sourceLang);
    $appName = $options['appName'] ?? commonAuthT('auth.page.login.app_default', [], $lang, $sourceLang);
    $intro = $options['intro'] ?? commonAuthT('auth.page.login.intro_default', [], $lang, $sourceLang);
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
<html lang="<?= htmlspecialchars(commonAuthGetTranslationLocale()) ?>">
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
                    <img src="<?= htmlspecialchars($organizationLogo) ?>" alt="<?= htmlspecialchars(commonAuthT('auth.page.logo_alt', [], $lang, $sourceLang)) ?>">
                </div>
                
                <h1><?= htmlspecialchars($organizationContext['name'] ?: $appName) ?></h1>
                <p><?= htmlspecialchars($intro) ?></p>
                <div class="auth-kicker"><?= htmlspecialchars($appName) ?></div>
            </div>
        </div>

        <div class="auth-card">
            <h2><?= htmlspecialchars(commonAuthT('auth.page.login.title_default', [], $lang, $sourceLang)) ?></h2>
            <p class="auth-copy"><?= htmlspecialchars(commonAuthT('auth.copy.login_code', [], $lang, $sourceLang)) ?></p>
            <label class="auth-language-picker" for="authLanguageSelect">
                <span><?= htmlspecialchars(commonAuthT('auth.page.language_label', [], $lang, $sourceLang)) ?></span>
                <select id="authLanguageSelect" data-auth-language-select>
                    <option value="system" <?= $currentLocalePreference === 'system' ? 'selected' : '' ?>><?= htmlspecialchars(commonAuthT('auth.page.language_system_label', [], $lang, $sourceLang)) ?> (<?= htmlspecialchars(strtoupper($resolvedLocale)) ?>)</option>
                    <?php foreach ($languageOptions as $languageOption): ?>
                    <option value="<?= htmlspecialchars((string)$languageOption['locale']) ?>" <?= $currentLocalePreference === (string)$languageOption['locale'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$languageOption['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="auth-email-row" id="authEmailRow">
                <input type="text" id="authEmailInput" placeholder="<?= htmlspecialchars(!empty($organizationContext['domain']) ? commonAuthT('auth.placeholder.username', [], $lang, $sourceLang) : commonAuthT('auth.placeholder.full_email', [], $lang, $sourceLang)) ?>" autofocus>
                <?php if (!empty($organizationContext['domain'])): ?>
                    <div class="auth-email-domain" id="authEmailDomain">@<?= htmlspecialchars($organizationContext['domain']) ?></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($organizationContext['domain'])): ?>
                <button type="button" class="auth-link-btn" id="authToggleMode"><?= htmlspecialchars(commonAuthT('auth.toggle.use_other_email', [], $lang, $sourceLang)) ?></button>
            <?php endif; ?>

            <label class="auth-remember">
                <input type="checkbox" id="authRememberMe"> <?= htmlspecialchars(commonAuthT('auth.remember_me', [], $lang, $sourceLang)) ?>
            </label>

            <div id="authChallengeBox" class="auth-challenge" style="display:none;">
                <p id="authChallengeQuestion"></p>
                <input type="text" id="authChallengeAnswer" placeholder="<?= htmlspecialchars(commonAuthT('auth.challenge.answer_placeholder', [], $lang, $sourceLang)) ?>">
                <button type="button" id="authChallengeSubmit"><?= htmlspecialchars(commonAuthT('auth.button.validate', [], $lang, $sourceLang)) ?></button>
            </div>

            <div id="authCodeBox" class="auth-code-box" style="display:none;">
                <p><?= htmlspecialchars(commonAuthT('auth.code.instructions', [], $lang, $sourceLang)) ?></p>
                <input type="text" id="authCodeInput" inputmode="text" autocomplete="one-time-code" maxlength="6" placeholder="<?= htmlspecialchars(commonAuthT('auth.code.placeholder', [], $lang, $sourceLang)) ?>">
                <button type="button" id="authCodeSubmit"><?= htmlspecialchars(commonAuthT('auth.button.validate_code', [], $lang, $sourceLang)) ?></button>
            </div>
            <form id="authVerifyForm" method="post" action="/common/login_verify.php" style="display:none;">
                <input type="hidden" name="token" id="authVerifyToken" value="">
                <input type="hidden" name="code" id="authVerifyCode" value="">
                <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo) ?>">
            </form>

            <button type="button" class="auth-link-btn auth-resend" id="authResendLink" style="display:none;"><?= htmlspecialchars(commonAuthT('auth.button.resend_code', [], $lang, $sourceLang)) ?></button>
            <button type="button" class="auth-submit" id="authLoginSubmit"><?= htmlspecialchars(commonAuthT('auth.button.send_code', [], $lang, $sourceLang)) ?></button>
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
