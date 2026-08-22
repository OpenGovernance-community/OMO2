<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__, 2) . '/common/auth.php';
require_once dirname(__DIR__, 2) . '/common/omo_context_scope.php';
require_once dirname(__DIR__, 2) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/translations.php';

if (!function_exists('omoApiEscape')) {
    function omoApiEscape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('omoApiNormalizeLabel')) {
    function omoApiNormalizeLabel($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($transliterated) && $transliterated !== '') {
            $value = $transliterated;
        }

        return trim((string)preg_replace('/[^a-z0-9]+/', ' ', $value));
    }
}

if (!function_exists('omoApiSortKey')) {
    function omoApiSortKey($value)
    {
        return omoApiNormalizeLabel($value);
    }
}

if (!function_exists('omoApiCanBypassOrganizationAccessCheck')) {
    function omoApiCanBypassOrganizationAccessCheck()
    {
        $requestPath = trim((string)commonGetRequestPath());
        if ($requestPath === '') {
            return false;
        }

        $allowedPaths = array(
            '/omo/api/organization/access_request_popup.php',
            '/omo/api/organizations/create_import_popup.php',
            '/omo/api/organizations/create_import.php',
        );

        return in_array($requestPath, $allowedPaths, true);
    }
}

if (!function_exists('omoApiCanUsePublicBasicLmsAccess')) {
    function omoApiCanUsePublicBasicLmsAccess()
    {
        if (trim((string)($_REQUEST['catalog'] ?? '')) !== 'basic') {
            return false;
        }

        $requestPath = trim((string)commonGetRequestPath());
        if ($requestPath === '') {
            return false;
        }

        $normalizedRequestPath = rtrim($requestPath, '/');
        if ($normalizedRequestPath === '') {
            $normalizedRequestPath = '/';
        }

        $allowedPaths = [
            '/omo/api/lms',
            '/omo/api/lms/',
            '/omo/api/lms/index.php',
            '/omo/api/lms/parcours.php',
            '/omo/api/lms/getmissions.php',
            '/omo/api/lms/getmissions_done.php',
            '/omo/api/lms/getmissions_next.php',
            '/omo/api/lms/getMissionDetail.php',
            '/omo/api/lms/action.php',
            '/omo/api/lms/homework_action.php',
            '/omo/api/lms/getMissionQuestions.php',
        ];

        return in_array($requestPath, $allowedPaths, true)
            || in_array($normalizedRequestPath, $allowedPaths, true);
    }
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$shareLink = function_exists('commonGetCurrentShareLink')
    ? commonGetCurrentShareLink()
    : null;
$publicDecisionTokenAccess = false;
$collaboraWopiTokenAccess = defined('OMO_COLLABORA_WOPI_TOKEN_ACCESS')
    && OMO_COLLABORA_WOPI_TOKEN_ACCESS === true;

if (!$shareLink) {
    require_once __DIR__ . '/decision/modules/public_access.php';

    $publicToken = trim((string)($_REQUEST['token'] ?? ''));
    if ($publicToken !== '' && omoDecisionCanUsePublicTokenForPath(commonGetRequestPath())) {
        $publicParticipant = omoDecisionResolvePublicParticipantByToken($publicToken);
        if ($publicParticipant) {
            $publicDecision = $publicParticipant->getDecisionProcess();
            if ($publicDecision) {
                $_SESSION['currentOrganization'] = (int)$publicDecision->get('IDorganization');
                $publicDecisionTokenAccess = true;
            }
        }
    }
}

if ($shareLink) {
    $_SESSION['currentOrganization'] = (int)$shareLink->get('IDorganization');
} else {
    commonResolveOrganizationContext((int)($_SESSION['currentOrganization'] ?? 1));
}

commonRestoreRememberedUser();

if (!commonGetCurrentUserId() && !commonCanAccessWithoutLogin() && !$shareLink && !$publicDecisionTokenAccess && !$collaboraWopiTokenAccess && !omoApiCanUsePublicBasicLmsAccess()) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

if (
    !commonCanAccessWithoutLogin()
    && !$shareLink
    && !$publicDecisionTokenAccess
    && !$collaboraWopiTokenAccess
    && !omoApiCanUsePublicBasicLmsAccess()
    && !omoApiCanBypassOrganizationAccessCheck()
    && !commonCurrentUserHasOrganizationAccess()
) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}
