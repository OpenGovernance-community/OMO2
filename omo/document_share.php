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
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/omo/assets/css/styles.css?v=20260807-notification-channels">
    <style>
    body {
        margin: 0;
        background: var(--color-bg, #f8fafc);
        color: var(--color-text, #1f2937);
        font-family: sans-serif;
    }
    .omo-document-share {
        max-width: 960px;
        margin: 0 auto;
        padding: 20px;
        display: grid;
        gap: 18px;
    }
    .omo-document-share__hero,
    .omo-document-share__body {
        display: grid;
        gap: 12px;
    }
    .omo-document-share__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .omo-document-share__status {
        color: var(--color-text-light, #6b7280);
        font-size: 14px;
    }
    .omo-document-share__content {
        line-height: 1.7;
        word-break: break-word;
    }
    .omo-document-share__content > :first-child {
        margin-top: 0;
    }
    .omo-document-share__content > :last-child {
        margin-bottom: 0;
    }
    .omo-document-share__content .omo-document-embed {
        display: grid;
        gap: 10px;
        margin: 0 0 1em;
        padding: 14px 16px;
        border-radius: var(--radius-md);
        border: 1px solid color-mix(in srgb, var(--color-border, #d1d5db) 85%, #2563eb 15%);
        background: color-mix(in srgb, var(--color-surface, #fff) 90%, #eff6ff 10%);
    }
    .omo-document-share__content .omo-document-embed--resolved {
        display: block;
        margin: 0;
        padding: 0;
        border: 0;
        border-radius: var(--radius-md);
        background: transparent;
        transition: background-color 140ms ease;
    }
    .omo-document-share__content .omo-document-embed--resolved:hover:not(:has(.omo-document-embed--resolved:hover)) {
        background: color-mix(in srgb, var(--color-surface, #fff) 94%, var(--color-text, #1f2937) 6%);
    }
    .omo-document-share__content .omo-document-embed:last-child {
        margin-bottom: 0;
    }
    .omo-document-share__content .omo-document-embed__label {
        color: var(--color-text-light, #6b7280);
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .omo-document-share__content .omo-document-embed__title {
        font-weight: 700;
        color: var(--color-text, #1f2937);
    }
    .omo-document-share__content .omo-document-embed__description,
    .omo-document-share__content .omo-document-embed__message {
        color: var(--color-text-light, #6b7280);
        line-height: 1.6;
    }
    .omo-document-share__content .omo-document-embed__body {
        display: grid;
        gap: 0.9em;
        padding-top: 2px;
    }
    .omo-document-share__content .omo-document-embed__body > :first-child {
        margin-top: 0;
    }
    .omo-document-share__content .omo-document-embed__body > :last-child {
        margin-bottom: 0;
    }
    </style>
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
    <script>
    (function () {
        const token = <?= json_encode($token, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const titleNode = document.getElementById('omoDocumentShareTitle');
        const descriptionNode = document.getElementById('omoDocumentShareDescription');
        const updatedAtNode = document.getElementById('omoDocumentShareUpdatedAt');
        const draftBadgeNode = document.getElementById('omoDocumentShareDraftBadge');
        const statusNode = document.getElementById('omoDocumentShareStatus');
        const contentNode = document.getElementById('omoDocumentShareContent');
        const pollDelayMs = 2000;
        let knownUpdatedAt = <?= json_encode($livePayload['updatedAt'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || '';
        let knownContentHash = <?= json_encode($livePayload['contentHash'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || '';
        let knownStateHash = <?= json_encode($livePayload['stateHash'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || '';

        function updateSnapshot(payload) {
            if (!payload || payload.status !== true || !contentNode) {
                return;
            }

            if (payload.stateHash) {
                knownStateHash = String(payload.stateHash);
            }

            if (payload.contentHash) {
                knownContentHash = String(payload.contentHash);
            }

            if (payload.updatedAt) {
                knownUpdatedAt = String(payload.updatedAt);
            }

            if (payload.changed === false) {
                return;
            }

            const nextTitle = String(payload.title || '').trim();
            const nextDescription = String(payload.description || '').trim();

            if (nextTitle !== '' && titleNode) {
                titleNode.textContent = nextTitle;
            }

            if (descriptionNode) {
                descriptionNode.textContent = nextDescription;
                descriptionNode.hidden = nextDescription === '';
            }

            if (payload.contentChanged !== false && Object.prototype.hasOwnProperty.call(payload, 'content')) {
                contentNode.innerHTML = String(payload.content || '');
            }

            if (updatedAtNode && payload.updatedAt) {
                const date = new Date(payload.updatedAt);
                if (!Number.isNaN(date.getTime())) {
                    updatedAtNode.textContent = 'Mise a jour ' + date.toLocaleString('fr-CH');
                }
            }

            if (draftBadgeNode) {
                draftBadgeNode.hidden = !payload.isDraft;
            }

            if (statusNode) {
                if (payload.isDraft && payload.editingUserName) {
                    statusNode.textContent = 'Edition en cours par ' + payload.editingUserName + '.';
                } else if (payload.isDraft) {
                    statusNode.textContent = 'Edition en cours.';
                } else {
                    statusNode.textContent = 'Lecture partagee du document.';
                }
            }
        }

        function poll() {
            const params = new URLSearchParams();
            params.set('token', token);
            if (knownUpdatedAt) {
                params.set('known_updated_at', knownUpdatedAt);
            }
            if (knownContentHash) {
                params.set('known_content_hash', knownContentHash);
            }
            if (knownStateHash) {
                params.set('known_state_hash', knownStateHash);
            }

            fetch('/omo/api/documents/share_live.php?' + params.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('share_live_failed');
                    }
                    return response.json();
                })
                .then(updateSnapshot)
                .catch(function () {
                })
                .finally(function () {
                    window.setTimeout(poll, pollDelayMs);
                });
        }

        window.setTimeout(poll, pollDelayMs);
    })();
    </script>
    <?php endif; ?>
</body>
</html>
