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
$featureEnabled = githubBugReportUiIsEnabled();
$patreonConnection = false;
$patreonConnected = false;

if ($currentUserId > 0 && $featureEnabled) {
    $patreonConnection = \dbObject\UserPatreon::findByUserId($currentUserId);
    $patreonConnected = $patreonConnection !== false && $patreonConnection->isConnected();
}
?>
<div class="omo-bug-report-popup generic-stack generic-stack--flush" id="omoBugReportPopup" data-submit-url="/omo/api/bug_report_submit.php">
    <link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/bug_report_popup.css') ?>">

    <div class="omo-bug-report-popup__header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy omo-bug-report-popup__header-copy omo-bug-report-popup__hero">
            <div class="generic-card-title generic-card-title--eyebrow"><?= omoApiEscape(githubBugReportT('eyebrow')) ?></div>
            <h2 class="generic-card-title generic-card-title--large"><?= omoApiEscape(githubBugReportT('title')) ?></h2>
            <p><?= omoApiEscape(githubBugReportT('intro')) ?></p>
            <div class="omo-bug-report-popup__meta">
                <?php if ($destination['repo'] !== ''): ?>
                    <span class="generic-badge"><?= omoApiEscape(githubBugReportT('repository', ['value' => $destination['repo']])) ?></span>
                <?php endif; ?>
                <?php if ($profileLabel !== ''): ?>
                    <span class="generic-badge"><?= omoApiEscape(githubBugReportT('user', ['value' => $profileLabel])) ?></span>
                <?php endif; ?>
                <?php if ($organizationLabel !== ''): ?>
                    <span class="generic-badge"><?= omoApiEscape(githubBugReportT('organization', ['value' => $organizationLabel])) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="omo-bug-report-popup__hero-figure" aria-hidden="true">
            <img src="/img/punaise.png" alt="">
        </div>
    </div>
    <div class="omo-bug-report-popup__shell generic-drawer-content">

    <?php if ($currentUserId <= 0): ?>
        <div class="omo-bug-report-popup__error generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(githubBugReportT('login_required')) ?></h3>
            <p><?= omoApiEscape(githubBugReportT('login_required_help')) ?></p>
        </div>
    <?php elseif (!$featureEnabled): ?>
        <div class="omo-bug-report-popup__error generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(githubBugReportT('unavailable')) ?></h3>
            <p><?= omoApiEscape(githubBugReportT('unavailable_help')) ?></p>
            <?php if ($configurationIssues !== []): ?>
                <ul>
                    <?php foreach ($configurationIssues as $issue): ?>
                        <li><?= htmlspecialchars((string)$issue, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if (!patreonSupportUiIsEnabled()): ?>
                <p><?= omoApiEscape(githubBugReportT('patreon_required')) ?></p>
            <?php endif; ?>
        </div>
    <?php elseif (!$patreonConnected): ?>
        <div class="omo-bug-report-popup__error generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(githubBugReportT('patreon_access')) ?></h3>
            <p><?= omoApiEscape(githubBugReportT('patreon_access_help')) ?></p>
            <p><?= omoApiEscape(githubBugReportT('patreon_connect_help')) ?></p>
            <div class="omo-bug-report-popup__connect-actions">
                <button type="button" class="generic-action-button generic-action-button--main" id="omoBugReportPatreonConnect"><?= omoApiEscape(githubBugReportT('connect')) ?></button>
            </div>
        </div>
    <?php else: ?>
        <div class="omo-bug-report-popup__panel generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(githubBugReportT('describe')) ?></h3>
            <p class="omo-bug-report-popup__hint generic-help-text"><?= omoApiEscape(githubBugReportT('context_help')) ?></p>

            <form class="omo-bug-report-popup__form generic-stack generic-stack--roomy" id="omoBugReportForm">
                <div class="omo-bug-report-popup__field generic-stack generic-stack--compact">
                    <label for="omoBugReportTitle"><?= omoApiEscape(githubBugReportT('title_label')) ?></label>
                    <input
                        type="text"
                        id="omoBugReportTitle"
                        name="title"
                        class="generic-form-control"
                        maxlength="180"
                        required
                        placeholder="<?= omoApiEscape(githubBugReportT('title_placeholder')) ?>"
                    >
                </div>

                <div class="omo-bug-report-popup__field generic-stack generic-stack--compact">
                    <label for="omoBugReportDescription"><?= omoApiEscape(githubBugReportT('description_label')) ?></label>
                    <textarea
                        id="omoBugReportDescription"
                        name="description"
                        class="generic-form-control"
                        rows="8"
                        required
                        placeholder="<?= omoApiEscape(githubBugReportT('description_placeholder')) ?>"
                    ></textarea>
                </div>

                <div class="omo-bug-report-popup__field generic-stack generic-stack--compact">
                    <label for="omoBugReportAttachments"><?= omoApiEscape(githubBugReportT('attachments_label')) ?></label>
                    <input
                        type="file"
                        id="omoBugReportAttachments"
                        name="attachments[]"
                        class="omo-bug-report-popup__file-input"
                        accept=".png,.jpg,.jpeg,.gif,.webp,.pdf,.txt,.log,.zip"
                        multiple
                    >
                    <p class="omo-bug-report-popup__hint generic-help-text"><?= omoApiEscape(githubBugReportT('attachments_help')) ?></p>
                    <div class="omo-bug-report-popup__file-list" id="omoBugReportFileList" hidden></div>
                </div>

                <div class="omo-bug-report-popup__feedback generic-feedback" id="omoBugReportFeedback" aria-live="polite"></div>

                <div class="omo-bug-report-popup__actions generic-action-row">
                    <button type="button" class="generic-action-button generic-action-button--secondary" id="omoBugReportClose"><?= omoApiEscape(githubBugReportT('close')) ?></button>
                    <button type="submit" class="generic-action-button generic-action-button--main" id="omoBugReportSubmit"><?= omoApiEscape(githubBugReportT('send')) ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>
    </div>
</div>

<?= commonPageScriptTags('/omo/api/bug_report_popup.js', [
    'texts' => [
        'endpointMissing' => githubBugReportT('endpoint_missing'),
        'attachmentWarning' => githubBugReportT('attachment_warning'),
        'sending' => githubBugReportT('sending'),
        'sent' => githubBugReportT('sent'),
        'sendFailed' => githubBugReportT('send_failed'),
        'viewIssue' => githubBugReportT('view_issue'),
        'file' => githubBugReportT('file'),
    ],
    'patreonConnectOrigin' => patreonGetConnectOrigin(),
]) ?>
