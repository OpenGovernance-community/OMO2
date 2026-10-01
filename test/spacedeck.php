<?php

require_once dirname(__DIR__) . '/omo/api/bootstrap.php';
require_once dirname(__DIR__) . '/common/spacedeck.php';

use dbObject\User;

$whiteboardBaseUrl = 'https://whiteboard.localtest.me';
$spaceId = 'omo-local-demo';
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$userId = (int)commonGetCurrentUserId();
$user = new User();
$userName = $userId > 0 && $user->load($userId)
    ? trim((string)$user->getScopedDisplayName($organizationId))
    : '';
if ($userName === '') {
    $userName = 'Utilisateur ' . $userId;
}

$mode = strtolower(trim((string)($_GET['mode'] ?? 'edit')));
$accessLevels = array('deny' => 0, 'read' => 1, 'edit' => 2);
$accessLevel = $accessLevels[$mode] ?? 2;
$externalToken = omoSpacedeckBuildExternalAccessToken(
    $spaceId,
    $userId,
    $userName,
    $accessLevel,
    0,
    omoSpacedeckGetCurrentLanguage()
);
if ($externalToken === '') {
    http_response_code(503);
    echo 'La cle de controle d acces SpaceDeck n est pas configuree.';
    exit;
}

?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test SpaceDeck Open</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; }
        body { margin: 0; background: #f3f5f7; color: #18212b; }
        main { width: min(1400px, calc(100% - 32px)); margin: 16px auto; }
        h1 { margin: 0 0 8px; font-size: 1.35rem; }
        p { margin: 0 0 12px; }
        .frame { width: 100%; height: calc(100vh - 140px); min-height: 620px; border: 1px solid #cbd3da; border-radius: 8px; background: #fff; }
    </style>
</head>
<body>
<main>
    <h1>Test SpaceDeck Open</h1>
    <p>Connecte comme <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>. Mode de test: <?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>.</p>
    <p><a href="?mode=edit">Edition</a> | <a href="?mode=read">Lecture seule</a> | <a href="?mode=deny">Acces refuse</a></p>
    <iframe class="frame" title="Tableau blanc SpaceDeck Open" data-whiteboard-frame></iframe>
</main>
<script>
    (function () {
        var baseUrl = <?= json_encode($whiteboardBaseUrl, JSON_UNESCAPED_SLASHES) ?>;
        var path = <?= json_encode('/spaces/' . $spaceId, JSON_UNESCAPED_SLASHES) ?>;
        var externalToken = <?= json_encode($externalToken, JSON_UNESCAPED_SLASHES) ?>;
        var url = new URL(path, baseUrl);

        url.searchParams.set('externalToken', externalToken);
        url.searchParams.set('embedded', '1');

        document.querySelector('[data-whiteboard-frame]').src = url.toString();
    }());
</script>
</body>
</html>
