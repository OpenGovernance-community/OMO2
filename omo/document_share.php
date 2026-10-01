<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\DocumentShareLink;

$token = trim((string)($_GET['token'] ?? ''));
$shareLink = DocumentShareLink::findValidByToken($token);

if (!$shareLink) {
    http_response_code(404);
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lien de partage invalide</title>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <main class="auth-state-layout">
        <div class="auth-state-card">
            <h1>Lien invalide</h1>
            <p>Ce lien de partage est invalide, inactif ou expire.</p>
        </div>
    </main>
</body>
</html>
    <?php
    exit;
}

$document = $shareLink->getDocument();
if (!($document instanceof \dbObject\Document) || $document->isFolder()) {
    http_response_code(404);
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document introuvable</title>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <main class="auth-state-layout">
        <div class="auth-state-card">
            <h1>Document introuvable</h1>
            <p>Le document partage n est plus disponible.</p>
        </div>
    </main>
</body>
</html>
    <?php
    exit;
}

$passwordError = '';
if ($shareLink->requiresPassword() && !commonIsSharePasswordVerified($token)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = (string)($_POST['share_password'] ?? '');
        if ($shareLink->verifyPassword($password)) {
            commonRememberSharePasswordVerified($token);
            header('Location: ' . $shareLink->buildShareUrl());
            exit;
        }

        $passwordError = 'Mot de passe invalide.';
    }

    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe requis</title>
    <link rel="stylesheet" href="/common/assets/auth.css">
</head>
<body class="auth-state-page">
    <main class="auth-state-layout">
        <div class="auth-state-card">
            <h1>Acces protege</h1>
            <p>Un mot de passe est requis pour ouvrir ce document partage.</p>
            <form method="post" class="auth-state-form">
                <label class="auth-state-label" for="omoDocumentSharePasswordInput">Mot de passe</label>
                <input class="auth-state-input" type="password" id="omoDocumentSharePasswordInput" name="share_password" autofocus>
                <?php if ($passwordError !== ''): ?>
                    <p class="auth-state-error"><?= htmlspecialchars($passwordError, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <div class="auth-state-actions">
                    <button class="auth-state-btn auth-state-btn--primary" type="submit">Ouvrir le lien</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
    <?php
    exit;
}

if ($shareLink->allowsPvContribution()) {
    header('Location: ' . $shareLink->buildPvParticipationUrl());
    exit;
}

$livePayload = $document->buildLiveSharePayload($shareLink->allowsLiveFollow());
$updatedAtToken = trim((string)($livePayload['updatedAt'] ?? ''));
$updatedAt = $updatedAtToken !== '' ? date_create_immutable($updatedAtToken) : null;
$documentTitle = trim((string)($livePayload['title'] ?? ''));
$documentDescription = trim((string)($livePayload['description'] ?? ''));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($documentTitle !== '' ? $documentTitle : 'Document partage', ENT_QUOTES, 'UTF-8') ?></title>
    <script src="/shared_functions.js"></script>
    <script>sharedApplyDocumentTheme();</script>
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/components.css') ?>">
    <?= commonStylesheetTags('/omo/assets/css/styles.css') ?>
    <link rel="stylesheet" href="<?= commonAssetUrl('/omo/document_share.css') ?>">
</head>
<body>
    <main class="omo-document-share">
        <section class="omo-document-share__hero generic-hero-panel">
            <h1 class="generic-card-title generic-card-title--large" id="omoDocumentShareTitle"><?= htmlspecialchars($documentTitle, ENT_QUOTES, 'UTF-8') ?></h1>
            <p id="omoDocumentShareDescription"<?= $documentDescription !== '' ? '' : ' hidden' ?>><?= htmlspecialchars($documentDescription, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="omo-document-share__meta">
                <?php if ($shareLink->allowsLiveFollow()): ?>
                    <span class="omo-pill">Suivi temps reel</span>
                <?php endif; ?>
                <?php if ($updatedAt instanceof DateTimeInterface): ?>
                    <span class="omo-pill" id="omoDocumentShareUpdatedAt">Mise a jour <?= htmlspecialchars($updatedAt->format('d.m.Y H:i'), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <span class="omo-pill" id="omoDocumentShareDraftBadge"<?= !empty($livePayload['isDraft']) ? '' : ' hidden' ?>>Brouillon en cours</span>
            </div>
            <div class="omo-document-share__status" id="omoDocumentShareStatus">
                <?php if (!empty($livePayload['isDraft']) && trim((string)($livePayload['editingUserName'] ?? '')) !== ''): ?>
                    Edition en cours par <?= htmlspecialchars((string)$livePayload['editingUserName'], ENT_QUOTES, 'UTF-8') ?>.
                <?php else: ?>
                    Lecture partagee du document.
                <?php endif; ?>
            </div>
        </section>

        <section class="omo-document-share__body generic-section generic-section--stack">
            <div class="omo-document-share__content prose" id="omoDocumentShareContent"><?= (string)($livePayload['content'] ?? '') ?></div>
        </section>
    </main>

    <?php if ($shareLink->allowsLiveFollow()): ?>
    <?= commonPageScriptTags('/omo/document_share.js', [
    'token' => $token,
    'knownUpdatedAt' => $livePayload['updatedAt'] ?? null,
    'knownContentHash' => $livePayload['contentHash'] ?? '',
    'knownStateHash' => $livePayload['stateHash'] ?? '',
]) ?>
    <?php endif; ?>
</body>
</html>
