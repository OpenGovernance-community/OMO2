<?php

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/collabora.php';

use dbObject\Document;
use dbObject\User;

$documentId = (int)($_GET['id'] ?? 0);
$userId = (int)commonGetCurrentUserId();

if ($documentId <= 0 || $userId <= 0) {
    http_response_code(400);
    echo 'Demande invalide.';
    exit;
}

$document = new Document();
if (!$document->load($documentId) || !$document->canOpenWithCollabora()) {
    http_response_code(404);
    echo 'Document introuvable.';
    exit;
}

$organizationId = (int)$document->get('IDorganization');
$holonId = (int)$document->get('IDholon');
if (
    $organizationId <= 0
    || !commonUserHasOrganizationAccess($userId, $organizationId)
    || !$document->canViewInOrganizationContext($organizationId, $holonId > 0 ? $holonId : null, $userId)
) {
    http_response_code(403);
    echo 'Accès refusé.';
    exit;
}

$organization = new \dbObject\Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    echo 'Organisation introuvable.';
    exit;
}

$config = omoCollaboraGetConfig($organization);
if (!omoCollaboraHasConfig($organization) || !$organization->hasDocumentStorage()) {
    http_response_code(503);
    echo 'Collabora ou le stockage de documents n’est pas disponible.';
    exit;
}

$discoveryResult = omoCollaboraFetchDiscovery($config);
if (!($discoveryResult['status'] ?? false)) {
    http_response_code(503);
    echo htmlspecialchars((string)($discoveryResult['text'] ?? 'Impossible de joindre Collabora.'), ENT_QUOTES, 'UTF-8');
    exit;
}

$extension = strtolower((string)pathinfo($document->getStoredFileDownloadName(), PATHINFO_EXTENSION));
$canEdit = $document->canEditInOrganizationContext($organizationId, $userId, false);
$actionName = $canEdit ? 'edit' : 'view';
$actionUrl = '';
$discoveryDocument = new DOMDocument();
if (@$discoveryDocument->loadXML((string)$discoveryResult['xml'])) {
    foreach ($discoveryDocument->getElementsByTagName('action') as $action) {
        if (
            strtolower((string)$action->getAttribute('name')) !== $actionName
            || strtolower((string)$action->getAttribute('ext')) !== $extension
        ) {
            continue;
        }

        $actionUrl = trim((string)$action->getAttribute('urlsrc'));
        break;
    }
}

if ($actionUrl === '') {
    http_response_code(503);
    echo 'Le format de ce fichier n’est pas disponible dans Collabora.';
    exit;
}

$publicUrl = rtrim((string)$config['baseUrl'], '/');
$actionUrl = preg_replace('#^https?://[^/]+#i', $publicUrl, $actionUrl);
$tokenExpiresAt = time() + 3600;
$accessToken = omoCollaboraBuildWopiToken($document, $userId, $tokenExpiresAt);
$wopiSource = omoCollaboraBuildWopiSource($documentId);
$uiDefaults = 'UIMode=tabbed;SavedUIState=false;';
$cssVariables = omoCollaboraBuildOrganizationCssVariables($organization);
$separator = str_contains($actionUrl, '?') ? '&' : '?';
$actionUrl .= $separator . 'WOPISrc=' . rawurlencode($wopiSource);
$actionUrl .= '&access_token=' . rawurlencode($accessToken);
$actionUrl .= '&access_token_ttl=' . rawurlencode((string)$tokenExpiresAt . '000');
$actionUrl .= '&permission=' . rawurlencode($canEdit ? 'edit' : 'view');

$user = new User();
$displayName = $user->load($userId) ? trim((string)$user->getScopedDisplayName($organizationId)) : '';
if ($displayName === '') {
    $displayName = 'Utilisateur ' . $userId;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($document->getStoredFileDownloadName(), ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body>
    <form id="collabora-launch" method="post" action="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="WOPISrc" value="<?= htmlspecialchars($wopiSource, ENT_QUOTES, 'UTF-8') ?>" data-collabora-wopi-source>
        <input type="hidden" name="access_token" value="<?= htmlspecialchars($accessToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="access_token_ttl" value="<?= (int)$tokenExpiresAt ?>000">
        <input type="hidden" name="permission" value="<?= htmlspecialchars($canEdit ? 'edit' : 'view', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="ui_defaults" value="<?= htmlspecialchars($uiDefaults, ENT_QUOTES, 'UTF-8') ?>" data-collabora-ui-defaults>
        <input type="hidden" name="css_variables" value="<?= htmlspecialchars($cssVariables, ENT_QUOTES, 'UTF-8') ?>">
        <noscript>Activez JavaScript pour ouvrir le document.</noscript>
    </form>
    <script>
        (function () {
            var form = document.getElementById('collabora-launch');
            var uiDefaultsInput = document.querySelector('[data-collabora-ui-defaults]');
            var wopiSourceInput = document.querySelector('[data-collabora-wopi-source]');
            var theme = 'light';

            try {
                var parentTheme = window.parent && window.parent.document
                    ? window.parent.document.documentElement.getAttribute('data-theme')
                    : '';
                if (parentTheme === 'dark' || parentTheme === 'light') {
                    theme = parentTheme;
                } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    theme = 'dark';
                }
            } catch (error) {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    theme = 'dark';
                }
            }

            if (uiDefaultsInput) {
                uiDefaultsInput.value = 'UIMode=tabbed;UITheme=' + theme + ';SavedUIState=false;';
            }

            if (form) {
                var action = form.getAttribute('action') || '';
                var uiDefaults = uiDefaultsInput ? uiDefaultsInput.value : 'UIMode=tabbed;UITheme=' + theme + ';SavedUIState=false;';
                var wopiSource = wopiSourceInput ? wopiSourceInput.value : '';
                var themedWopiSource = wopiSource.replace(/\/$/, '') + '/theme/' + theme;

                if (wopiSourceInput) {
                    wopiSourceInput.value = themedWopiSource;
                }

                try {
                    var actionUrl = new URL(action, window.location.href);
                    actionUrl.searchParams.set('WOPISrc', themedWopiSource);
                    actionUrl.searchParams.set('ui_defaults', uiDefaults);
                    form.setAttribute('action', actionUrl.toString());
                } catch (error) {
                    var separator = action.indexOf('?') === -1 ? '?' : '&';
                    form.setAttribute('action', action + separator + 'ui_defaults=' + encodeURIComponent(uiDefaults));
                }
            }

            form.submit();
        }());
    </script>
</body>
</html>
