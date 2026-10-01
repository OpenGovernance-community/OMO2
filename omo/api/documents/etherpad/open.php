<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/etherpad.php';

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
if (!$document->load($documentId) || !$document->isEtherpadDocument()) {
    http_response_code(404);
    echo 'Document introuvable.';
    exit;
}

$documentOrganizationId = (int)$document->get('IDorganization');
$documentHolonId = (int)$document->get('IDholon');
$organizationId = $documentOrganizationId;
if (
    $organizationId <= 0
    || !commonCurrentUserHasOrganizationAccess($organizationId)
    || !$document->canViewInOrganizationContext($organizationId, $documentHolonId > 0 ? $documentHolonId : null)
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

$padId = $document->getEtherpadPadId();
if ($padId === '' || !omoEtherpadHasConfig($organization)) {
    http_response_code(503);
    echo 'Etherpad n’est pas disponible pour cette organisation.';
    exit;
}

$canEdit = $document->canEditInOrganizationContext($organizationId, $userId, false);
if (!$canEdit) {
    $readOnlyResult = omoEtherpadApiRequest($organization, 'getReadOnlyID', array('padID' => $padId));
    $readOnlyId = trim((string)($readOnlyResult['data']['readOnlyID'] ?? ''));
    if (!($readOnlyResult['status'] ?? false) || $readOnlyId === '') {
        http_response_code(503);
        echo 'Impossible d ouvrir ce pad en lecture seule.';
        exit;
    }

    $padUrl = omoEtherpadBuildPadUrl($organization, $readOnlyId);
    if ($padUrl === '') {
        http_response_code(503);
        echo 'URL Etherpad invalide.';
        exit;
    }
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Location: ' . $padUrl, true, 302);
    exit;
}

$cookieDomain = omoEtherpadResolveCookieDomain($organization);
if ($cookieDomain === null) {
    http_response_code(503);
    echo 'Le domaine de cookie Etherpad ne permet pas de transmettre la session OMO.';
    exit;
}

$user = new User();
if (!$user->load($userId)) {
    http_response_code(503);
    echo 'Identité OMO introuvable.';
    exit;
}

$userName = trim((string)$user->getScopedDisplayName($organizationId));
$authorResult = omoEtherpadApiRequest($organization, 'createAuthorIfNotExistsFor', array(
    'authorMapper' => 'omo-organization-' . $organizationId . '-user-' . $userId,
    'name' => $userName !== '' ? $userName : ('Utilisateur ' . $userId),
));
$authorId = trim((string)($authorResult['data']['authorID'] ?? ''));
if (!($authorResult['status'] ?? false) || $authorId === '') {
    http_response_code(503);
    echo 'Impossible de créer l’identité Etherpad.';
    exit;
}

$groupId = trim((string)strtok($padId, '$'));
$sessionResult = omoEtherpadGetOrCreateSession($organization, $groupId, $authorId);
if (!($sessionResult['status'] ?? false)) {
    http_response_code(503);
    echo 'Impossible de créer la session Etherpad.';
    exit;
}

$existingCookieValue = is_string($_COOKIE['sessionID'] ?? null) ? (string)$_COOKIE['sessionID'] : '';
$sessionCookieValue = omoEtherpadBuildSessionCookieValue(
    (string)$sessionResult['sessionId'],
    $existingCookieValue
);
$cookieOptions = array(
    'expires' => (int)($sessionResult['validUntil'] ?? (time() + 3600)),
    'path' => '/',
    'secure' => strtolower((string)parse_url(omoEtherpadGetConfig($organization)['baseUrl'], PHP_URL_SCHEME)) === 'https',
    // Etherpad reads sessionID from document.cookie before it opens its socket.
    // This cookie is an Etherpad session credential, not an OMO login cookie.
    'httponly' => false,
    'samesite' => 'None',
);
if ($cookieDomain !== '') {
    $cookieOptions['domain'] = $cookieDomain;
}
if ($sessionCookieValue === '' || !setcookie('sessionID', $sessionCookieValue, $cookieOptions)) {
    http_response_code(503);
    echo 'Impossible de transmettre la session Etherpad.';
    exit;
}

$padUrl = omoEtherpadBuildPadUrl($organization, $padId);
if ($padUrl === '') {
    http_response_code(503);
    echo 'URL Etherpad invalide.';
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Location: ' . $padUrl, true, 302);
exit;
