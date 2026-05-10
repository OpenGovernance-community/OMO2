<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/github_bug_report.php';
require_once dirname(__DIR__, 2) . '/common/patreon.php';

$currentUserId = (int)commonGetCurrentUserId();
$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$profileLabel = trim((string)commonGetCurrentUserDisplayName());
$organizationLabel = '';

if ($currentOrganizationId > 0) {
    $organization = new \dbObject\Organization();
    if ($organization->load($currentOrganizationId)) {
        $organizationLabel = trim((string)$organization->get('name'));
    }
}

$destination = githubBugReportGetDestinationSummary();
$configurationIssues = githubBugReportGetConfigurationIssues();
$isConfigured = githubBugReportIsConfigured();
$patreonConnection = false;
$patreonConnected = false;

if ($currentUserId > 0 && $isConfigured && patreonIsConfigured('oauth')) {
    $patreonConnection = \dbObject\UserPatreon::findByUserId($currentUserId);
    $patreonConnected = $patreonConnection !== false && $patreonConnection->isConnected();
}
?>
<div class="omo-bug-report-popup" id="omoBugReportPopup" data-submit-url="/omo/api/bug_report_submit.php">
    <style>
    .omo-bug-report-popup {
        display: grid;
        gap: 16px;
        color: var(--color-text, #1f2937);
    }

    .omo-bug-report-popup__hero,
    .omo-bug-report-popup__panel,
    .omo-bug-report-popup__error {
        --generic-section-padding-block: 18px;
        --generic-section-padding-inline: 18px;
        --generic-section-radius: 18px;
    }

    .omo-bug-report-popup__hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 16px;
        align-items: center;
    }

    .omo-bug-report-popup__hero p,
    .omo-bug-report-popup__error p,
    .omo-bug-report-popup__error li,
    .omo-bug-report-popup__hint {
        margin: 0;
        line-height: 1.5;
        color: var(--color-text-light, #6b7280);
    }

    .omo-bug-report-popup__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .omo-bug-report-popup__hero-copy {
        display: grid;
        gap: 10px;
        min-width: 0;
    }

    .omo-bug-report-popup__hero-figure {
        width: 96px;
        height: 96px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 20px;
        background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface, #ffffff));
        border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 18%, var(--color-border, #e5e7eb));
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
    }

    .omo-bug-report-popup__hero-figure img {
        width: 72px;
        height: 72px;
        object-fit: contain;
        display: block;
    }

    .omo-bug-report-popup__badge {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-primary, #2563eb) 12%, var(--color-surface, #ffffff));
        border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 24%, var(--color-border, #e5e7eb));
        color: var(--color-primary, #2563eb);
        font-size: 0.8rem;
        font-weight: 700;
    }

    .omo-bug-report-popup__error {
        display: grid;
        gap: 10px;
        border: 1px solid rgba(185, 28, 28, 0.18);
        background: rgba(185, 28, 28, 0.06);
        color: #991b1b;
    }

    .omo-bug-report-popup__error h3,
    .omo-bug-report-popup__panel h3 {
        margin: 0;
    }

    .omo-bug-report-popup__error ul {
        margin: 0;
        padding-left: 18px;
    }

    .omo-bug-report-popup__form {
        display: grid;
        gap: 16px;
    }

    .omo-bug-report-popup__field {
        display: grid;
        gap: 8px;
    }

    .omo-bug-report-popup__field label {
        font-weight: 700;
    }

    .omo-bug-report-popup__file-input {
        padding: 10px 12px;
        border: 1px dashed var(--color-border, #cbd5e1);
        border-radius: 12px;
        background: var(--color-surface-alt, #f8fafc);
    }

    .omo-bug-report-popup__file-list {
        display: grid;
        gap: 6px;
        font-size: 0.92rem;
        color: var(--color-text-light, #6b7280);
    }

    .omo-bug-report-popup__feedback {
        min-height: 24px;
        font-size: 0.95rem;
        line-height: 1.5;
        color: var(--color-text-light, #6b7280);
    }

    .omo-bug-report-popup__feedback.is-error {
        color: #b91c1c;
    }

    .omo-bug-report-popup__feedback.is-success {
        color: #166534;
    }

    .omo-bug-report-popup__feedback a {
        color: inherit;
        font-weight: 700;
    }

    .omo-bug-report-popup__connect-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .omo-bug-report-popup__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
    }

    @media (max-width: 640px) {
        .omo-bug-report-popup__hero {
            grid-template-columns: 1fr;
        }

        .omo-bug-report-popup__hero-figure {
            order: -1;
            width: 82px;
            height: 82px;
        }

        .omo-bug-report-popup__hero-figure img {
            width: 60px;
            height: 60px;
        }
    }
    </style>

    <div class="omo-bug-report-popup__hero generic-hero-panel">
        <div class="omo-bug-report-popup__hero-copy">
            <div class="generic-card-title generic-card-title--eyebrow">Signalement rapide</div>
            <h2 class="generic-card-title generic-card-title--large">Signaler un bug</h2>
            <p>Ce formulaire cree une issue GitHub avec ta description et le contexte technique de la page en cours.</p>
            <div class="omo-bug-report-popup__meta">
                <?php if ($destination['repo'] !== ''): ?>
                    <span class="omo-bug-report-popup__badge">Repo: <?= htmlspecialchars($destination['repo'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if ($profileLabel !== ''): ?>
                    <span class="omo-bug-report-popup__badge">User: <?= htmlspecialchars($profileLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if ($organizationLabel !== ''): ?>
                    <span class="omo-bug-report-popup__badge">Organisation: <?= htmlspecialchars($organizationLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="omo-bug-report-popup__hero-figure" aria-hidden="true">
            <img src="/img/punaise.png" alt="">
        </div>
    </div>

    <?php if ($currentUserId <= 0): ?>
        <div class="omo-bug-report-popup__error generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium">Connexion requise</h3>
            <p>Le signalement automatique vers GitHub n est disponible que pour un utilisateur connecte.</p>
        </div>
    <?php elseif (!$isConfigured): ?>
        <div class="omo-bug-report-popup__error generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium">Configuration GitHub incomplete</h3>
            <p>Le bouton est en place, mais le serveur n a pas encore les acces GitHub necessaires.</p>
            <?php if ($configurationIssues !== []): ?>
                <ul>
                    <?php foreach ($configurationIssues as $issue): ?>
                        <li><?= htmlspecialchars((string)$issue, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <p>Le token doit avoir au minimum le droit repository Issues: write.</p>
        </div>
    <?php elseif (!$patreonConnected): ?>
        <div class="omo-bug-report-popup__error generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium">Acces reserve aux comptes Patreon connectes</h3>
            <p>Le Bug Tracking System est reserve aux personnes qui ont connecte un compte Patreon actif, meme sans abonnement payant.</p>
            <p>Pour continuer, connecte ton compte Patreon puis reviens sur ce formulaire.</p>
            <div class="omo-bug-report-popup__connect-actions">
                <button type="button" class="generic-action-button generic-action-button--main" id="omoBugReportPatreonConnect">Se connecter avec Patreon</button>
            </div>
        </div>
    <?php else: ?>
        <div class="omo-bug-report-popup__panel generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium">Decrire le bug</h3>
            <p class="omo-bug-report-popup__hint">Le contexte courant sera ajoute automatiquement: URL, titre de page, navigateur, langue, fuseau horaire, viewport, theme et utilisateur.</p>

            <form class="omo-bug-report-popup__form" id="omoBugReportForm">
                <div class="omo-bug-report-popup__field">
                    <label for="omoBugReportTitle">Titre</label>
                    <input
                        type="text"
                        id="omoBugReportTitle"
                        name="title"
                        class="generic-form-control"
                        maxlength="180"
                        required
                        placeholder="Ex: La popup de partage reste vide"
                    >
                </div>

                <div class="omo-bug-report-popup__field">
                    <label for="omoBugReportDescription">Description</label>
                    <textarea
                        id="omoBugReportDescription"
                        name="description"
                        class="generic-form-control"
                        rows="8"
                        required
                        placeholder="Explique ce que tu faisais, ce qui s est passe, et ce que tu attendais."
                    ></textarea>
                </div>

                <div class="omo-bug-report-popup__field">
                    <label for="omoBugReportAttachments">Fichiers ou captures</label>
                    <input
                        type="file"
                        id="omoBugReportAttachments"
                        name="attachments[]"
                        class="omo-bug-report-popup__file-input"
                        accept=".png,.jpg,.jpeg,.gif,.webp,.pdf,.txt,.log,.zip"
                        multiple
                    >
                    <p class="omo-bug-report-popup__hint">Les fichiers joints seront stockes sur le serveur et leur lien sera public dans l issue GitHub. Formats acceptes: PNG, JPG, GIF, WEBP, PDF, TXT, LOG, ZIP.</p>
                    <div class="omo-bug-report-popup__file-list" id="omoBugReportFileList" hidden></div>
                </div>

                <div class="omo-bug-report-popup__feedback" id="omoBugReportFeedback" aria-live="polite"></div>

                <div class="omo-bug-report-popup__actions">
                    <button type="button" class="generic-action-button generic-action-button--secondary" id="omoBugReportClose">Fermer</button>
                    <button type="submit" class="generic-action-button generic-action-button--main" id="omoBugReportSubmit">Envoyer vers GitHub</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    const root = document.getElementById('omoBugReportPopup');
    if (!root) {
        return;
    }

    const patreonConnectButton = document.getElementById('omoBugReportPatreonConnect');
    function openPatreonConnect() {
        const width = 720;
        const height = 860;
        const left = Math.max(0, (window.screen.width - width) / 2);
        const top = Math.max(0, (window.screen.height - height) / 2);

        window.open(
            '/common/patreon_connect.php',
            'patreon_connect',
            'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes'
        );
    }

    function handlePatreonMessage(event) {
        if (event.origin !== window.location.origin) {
            return;
        }

        if (!event.data || event.data.type !== 'patreon-connected') {
            return;
        }

        if (typeof window.commonTopbarRefreshModalContent === 'function') {
            window.commonTopbarRefreshModalContent('/omo/api/bug_report_popup.php');
        }
    }

    if (patreonConnectButton) {
        patreonConnectButton.addEventListener('click', openPatreonConnect);
    }
    window.addEventListener('message', handlePatreonMessage);

    const closeButton = document.getElementById('omoBugReportClose');
    if (closeButton) {
        closeButton.addEventListener('click', function () {
            if (typeof window.commonTopbarCloseModal === 'function') {
                window.commonTopbarCloseModal();
            }
        });
    }

    const form = document.getElementById('omoBugReportForm');
    const feedback = document.getElementById('omoBugReportFeedback');
    const submitButton = document.getElementById('omoBugReportSubmit');
    if (!form || !feedback || !submitButton) {
        return;
    }

    const titleInput = document.getElementById('omoBugReportTitle');
    const descriptionInput = document.getElementById('omoBugReportDescription');
    const attachmentsInput = document.getElementById('omoBugReportAttachments');
    const fileList = document.getElementById('omoBugReportFileList');
    if (titleInput && !titleInput.value) {
        titleInput.focus();
    } else if (descriptionInput) {
        descriptionInput.focus();
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setFeedback(message, type, links) {
        feedback.classList.remove('is-error', 'is-success');

        if (!message) {
            feedback.textContent = '';
            return;
        }

        if (type === 'error') {
            feedback.classList.add('is-error');
            feedback.textContent = message;
            return;
        }

        if (type === 'success') {
            feedback.classList.add('is-success');
        }

        let html = escapeHtml(message);
        if (links && links.issue_url) {
            html += ' <a href="' + escapeHtml(links.issue_url) + '" target="_blank" rel="noopener">Voir l issue</a>';
        }
        feedback.innerHTML = html;
    }

    function renderSelectedFiles() {
        if (!attachmentsInput || !fileList) {
            return;
        }

        const files = Array.from(attachmentsInput.files || []);
        if (!files.length) {
            fileList.hidden = true;
            fileList.innerHTML = '';
            return;
        }

        fileList.hidden = false;
        fileList.innerHTML = files.map(function (file) {
            const sizeMb = file && file.size ? (file.size / (1024 * 1024)).toFixed(2) : '0.00';
            return '<div>' + escapeHtml(file.name || 'Fichier') + ' (' + escapeHtml(sizeMb) + ' MB)</div>';
        }).join('');
    }

    function collectContext() {
        const nav = window.navigator || {};
        const pageUrl = window.location && window.location.href ? window.location.href : '';
        const themeNode = document.documentElement;
        const screenInfo = window.screen && window.screen.width && window.screen.height
            ? String(window.screen.width) + 'x' + String(window.screen.height)
            : '';
        const viewport = typeof window.innerWidth === 'number' && typeof window.innerHeight === 'number'
            ? String(window.innerWidth) + 'x' + String(window.innerHeight)
            : '';
        const topbar = document.querySelector('.common-topbar');

        return {
            page_url: pageUrl,
            page_title: document.title || '',
            user_agent: nav.userAgent || '',
            language: nav.language || '',
            languages: Array.isArray(nav.languages) ? nav.languages.join(', ') : '',
            timezone: window.Intl && typeof window.Intl.DateTimeFormat === 'function'
                ? (Intl.DateTimeFormat().resolvedOptions().timeZone || '')
                : '',
            viewport: viewport,
            screen_size: screenInfo,
            pixel_ratio: window.devicePixelRatio ? String(window.devicePixelRatio) : '',
            referrer: document.referrer || '',
            theme: themeNode ? (themeNode.getAttribute('data-theme') || '') : '',
            app_key: topbar ? (topbar.getAttribute('data-app-key') || '') : '',
            platform: nav.platform || '',
            client_timestamp: (new Date()).toISOString()
        };
    }

    if (attachmentsInput) {
        attachmentsInput.addEventListener('change', renderSelectedFiles);
        renderSelectedFiles();
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const submitUrl = root.getAttribute('data-submit-url') || '';
        if (!submitUrl) {
            setFeedback('Endpoint de signalement introuvable.', 'error');
            return;
        }

        const formData = new FormData(form);
        const context = collectContext();
        Object.keys(context).forEach(function (key) {
            if (context[key]) {
                formData.append(key, context[key]);
            }
        });

        const attachedFiles = attachmentsInput ? Array.from(attachmentsInput.files || []) : [];
        if (attachedFiles.length) {
            const warningMessage = 'Attention: les donnees contenues dans les fichiers transmis seront visibles de tous via l issue GitHub. Continuer ?';
            if (!window.confirm(warningMessage)) {
                return;
            }
        }

        submitButton.disabled = true;
        if (closeButton) {
            closeButton.disabled = true;
        }
        setFeedback('Envoi vers GitHub...', 'info');

        fetch(submitUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.json().catch(function () {
                    return null;
                }).then(function (data) {
                    return {
                        ok: response.ok,
                        data: data
                    };
                });
            })
            .then(function (result) {
                submitButton.disabled = false;
                if (closeButton) {
                    closeButton.disabled = false;
                }

                if (!result.ok || !result.data || !result.data.status) {
                    setFeedback(
                        result.data && result.data.message ? result.data.message : 'Impossible d envoyer le signalement pour le moment.',
                        'error'
                    );
                    return;
                }

                setFeedback(result.data.message || 'Signalement envoye.', 'success', result.data);
                form.reset();
                renderSelectedFiles();
                if (titleInput) {
                    titleInput.focus();
                }
            })
            .catch(function () {
                submitButton.disabled = false;
                if (closeButton) {
                    closeButton.disabled = false;
                }
                setFeedback('Impossible d envoyer le signalement pour le moment.', 'error');
            });
    });

    window.__omoPopupCleanup = function () {
        window.removeEventListener('message', handlePatreonMessage);
        if (patreonConnectButton) {
            patreonConnectButton.removeEventListener('click', openPatreonConnect);
        }
    };
})();
</script>
