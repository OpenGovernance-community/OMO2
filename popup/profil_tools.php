<?php

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/account_merge.php';
require_once dirname(__DIR__) . '/common/account_deletion.php';
require_once __DIR__ . '/profil_translation_helper.php';

$connected = checklogin();
$currentUserId = function_exists('commonGetCurrentUserId')
    ? (int)commonGetCurrentUserId()
    : (int)($_SESSION['currentUser'] ?? 0);
if (!$connected || $currentUserId <= 0) {
    die(htmlspecialchars(profilPopupT('profile.popup.error.login_required'), ENT_QUOTES, 'UTF-8'));
}

$currentUser = new \dbObject\User();
if (!$currentUser->load($currentUserId)) {
    die(htmlspecialchars(profilPopupT('profile.popup.error.unknown_user'), ENT_QUOTES, 'UTF-8'));
}

$state = commonAccountMergeGetState($currentUserId);
$configuration = array(
    'endpoint' => '/ajax/user_account_merge.php',
    'csrfToken' => commonAccountMergeGetCsrfToken(),
    'deletionEndpoint' => '/ajax/user_account_delete.php',
    'deletionCsrfToken' => commonAccountDeletionGetCsrfToken(),
    'currentEmail' => (string)$currentUser->get('email'),
    'otherEmail' => is_array($state) ? (string)($state['other_email'] ?? '') : '',
    'phase' => is_array($state) ? (string)($state['phase'] ?? 'code') : 'start',
    'token' => is_array($state) ? (string)($state['login_token'] ?? '') : '',
    'passwordLoginEnabled' => is_array($state) && !empty($state['password_login_enabled']),
    'currentIsSiteAdmin' => is_array($state) ? !empty($state['current_is_siteadmin']) : $currentUser->isSiteAdmin(),
    'otherIsSiteAdmin' => is_array($state) && !empty($state['other_is_siteadmin']),
    'initialToken' => trim((string)($_GET['token'] ?? '')),
    'initialCode' => trim((string)($_GET['code'] ?? '')),
    'text' => array(
        'verified' => profilPopupT('profile.popup.merge.status.verified'),
        'processing' => profilPopupT('profile.popup.merge.status.processing'),
        'invalidResponse' => profilPopupT('profile.popup.js.invalid_response'),
        'deletionLoading' => profilPopupT('profile.popup.delete.loading'),
        'deletionInvalidResponse' => profilPopupT('profile.popup.delete.invalid_response'),
        'deletionSuccess' => profilPopupT('profile.popup.delete.success'),
        'deletionWillLeave' => profilPopupT('profile.popup.delete.plan.leave'),
        'deletionWillDeleteOrganizations' => profilPopupT('profile.popup.delete.plan.organizations'),
        'deletionBlocked' => profilPopupT('profile.popup.delete.plan.blocked'),
        'deletionNoOrganization' => profilPopupT('profile.popup.delete.plan.no_organization'),
        'deletionConfirmationPrefix' => profilPopupT('profile.popup.delete.confirmation_prefix'),
        'deletionProcessing' => profilPopupT('profile.popup.delete.processing'),
        'mergeOpen' => profilPopupT('profile.popup.merge.reveal'),
        'deletionOpen' => profilPopupT('profile.popup.delete.open'),
        'collapse' => profilPopupT('profile.popup.tools.collapse'),
    ),
);
?>
<link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/profile-tools.css') ?>">

<div class="profile-tools-list" data-profile-tools-root>
<section class="profile-merge-tool generic-section generic-accordion generic-accordion--card generic-accordion--collapsible generic-accordion--action-only is-collapsed profile-panel__section" data-profile-merge-root>
    <div class="generic-accordion__header profile-account-tool__header">
        <div class="profile-account-tool__copy">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(profilPopupT('profile.popup.merge.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars(profilPopupT('profile.popup.merge.intro'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <button type="button" class="generic-action-button generic-action-button--main" aria-expanded="false" data-merge-reveal>
            <?= htmlspecialchars(profilPopupT('profile.popup.merge.reveal'), ENT_QUOTES, 'UTF-8') ?>
        </button>
    </div>

    <div class="generic-accordion__content profile-account-tool__content">
    <div class="generic-soft-panel generic-soft-panel--stack">
        <strong><?= htmlspecialchars(profilPopupT('profile.popup.merge.current_email'), ENT_QUOTES, 'UTF-8') ?></strong>
        <span class="profile-merge-tool__current-email"><?= htmlspecialchars((string)$currentUser->get('email'), ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <div class="profile-merge-tool__step" data-merge-email-step hidden>
        <label class="profile-merge-tool__field">
            <span><?= htmlspecialchars(profilPopupT('profile.popup.merge.other_email_label'), ENT_QUOTES, 'UTF-8') ?></span>
            <input type="email" class="generic-form-control" autocomplete="email" placeholder="<?= htmlspecialchars(profilPopupT('profile.popup.merge.other_email_placeholder'), ENT_QUOTES, 'UTF-8') ?>" data-merge-email>
        </label>
        <div class="profile-merge-tool__actions">
            <button type="button" class="generic-action-button generic-action-button--main" data-merge-start><?= htmlspecialchars(profilPopupT('profile.popup.merge.start'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-merge-cancel><?= htmlspecialchars(profilPopupT('profile.popup.merge.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>

    <div class="profile-merge-tool__step" data-merge-code-step hidden>
        <label class="profile-merge-tool__field">
            <span><?= htmlspecialchars(profilPopupT('profile.popup.merge.code_label'), ENT_QUOTES, 'UTF-8') ?></span>
            <input type="text" class="generic-form-control" inputmode="text" autocomplete="one-time-code" maxlength="6" data-merge-code>
        </label>
        <div class="profile-merge-tool__actions">
            <button type="button" class="generic-action-button generic-action-button--main" data-merge-verify-code><?= htmlspecialchars(profilPopupT('profile.popup.merge.verify_code'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="profile-merge-tool__method" data-merge-use-password hidden><?= htmlspecialchars(profilPopupT('profile.popup.merge.use_password'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="profile-merge-tool__method" data-merge-resend><?= htmlspecialchars(profilPopupT('profile.popup.merge.resend_code'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-merge-cancel><?= htmlspecialchars(profilPopupT('profile.popup.merge.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>

    <div class="profile-merge-tool__step" data-merge-password-step hidden>
        <label class="profile-merge-tool__field">
            <span><?= htmlspecialchars(profilPopupT('profile.popup.merge.password_label'), ENT_QUOTES, 'UTF-8') ?></span>
            <input type="password" class="generic-form-control" autocomplete="current-password" data-merge-password>
        </label>
        <div class="profile-merge-tool__actions">
            <button type="button" class="generic-action-button generic-action-button--main" data-merge-verify-password><?= htmlspecialchars(profilPopupT('profile.popup.merge.verify_password'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="profile-merge-tool__method" data-merge-use-code><?= htmlspecialchars(profilPopupT('profile.popup.merge.use_code'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-merge-cancel><?= htmlspecialchars(profilPopupT('profile.popup.merge.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>

    <div class="profile-merge-tool__step" data-merge-totp-step hidden>
        <label class="profile-merge-tool__field">
            <span><?= htmlspecialchars(profilPopupT('profile.popup.merge.totp_label'), ENT_QUOTES, 'UTF-8') ?></span>
            <input type="text" class="generic-form-control" inputmode="numeric" autocomplete="one-time-code" maxlength="6" data-merge-totp>
        </label>
        <div class="profile-merge-tool__actions">
            <button type="button" class="generic-action-button generic-action-button--main" data-merge-verify-totp><?= htmlspecialchars(profilPopupT('profile.popup.merge.verify_totp'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-merge-cancel><?= htmlspecialchars(profilPopupT('profile.popup.merge.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>

    <div class="profile-merge-tool__step" data-merge-confirm-step hidden>
        <div>
            <h4 class="generic-card-title generic-card-title--section"><?= htmlspecialchars(profilPopupT('profile.popup.merge.keep_title'), ENT_QUOTES, 'UTF-8') ?></h4>
            <p><?= htmlspecialchars(profilPopupT('profile.popup.merge.keep_help'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="profile-merge-tool__choices">
            <label class="profile-merge-tool__choice generic-soft-panel">
                <input type="radio" name="profile_merge_keep" value="current" checked data-merge-keep>
                <span class="profile-merge-tool__choice-copy">
                    <strong><?= htmlspecialchars(profilPopupT('profile.popup.merge.keep_current'), ENT_QUOTES, 'UTF-8') ?></strong>
                    <small data-merge-current-email><?= htmlspecialchars((string)$currentUser->get('email'), ENT_QUOTES, 'UTF-8') ?></small>
                </span>
            </label>
            <label class="profile-merge-tool__choice generic-soft-panel">
                <input type="radio" name="profile_merge_keep" value="other" data-merge-keep>
                <span class="profile-merge-tool__choice-copy">
                    <strong><?= htmlspecialchars(profilPopupT('profile.popup.merge.keep_other'), ENT_QUOTES, 'UTF-8') ?></strong>
                    <small data-merge-other-email></small>
                </span>
            </label>
        </div>
        <div class="generic-soft-panel generic-soft-panel--stack" hidden data-merge-superadmin-note>
            <?= htmlspecialchars(profilPopupT('profile.popup.merge.superadmin_kept'), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="profile-merge-tool__warning generic-soft-panel generic-soft-panel--stack">
            <?= htmlspecialchars(profilPopupT('profile.popup.merge.warning'), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <label class="profile-merge-tool__confirmation">
            <input type="checkbox" value="1" data-merge-confirm-check>
            <span><?= htmlspecialchars(profilPopupT('profile.popup.merge.confirm_label'), ENT_QUOTES, 'UTF-8') ?></span>
        </label>
        <div class="profile-merge-tool__actions">
            <button type="button" class="generic-action-button generic-action-button--danger" disabled data-merge-complete><?= htmlspecialchars(profilPopupT('profile.popup.merge.complete'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-merge-cancel><?= htmlspecialchars(profilPopupT('profile.popup.merge.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>

    <div class="profile-merge-tool__status" aria-live="polite" data-merge-status></div>
    </div>
</section>

<section class="profile-account-delete generic-section generic-accordion generic-accordion--card generic-accordion--collapsible generic-accordion--action-only is-collapsed profile-panel__section" data-profile-delete-root>
    <div class="generic-accordion__header profile-account-tool__header">
        <div class="profile-account-tool__copy">
            <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(profilPopupT('profile.popup.delete.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars(profilPopupT('profile.popup.delete.intro'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <button type="button" class="generic-action-button generic-action-button--danger" aria-expanded="false" data-delete-open><?= htmlspecialchars(profilPopupT('profile.popup.delete.open'), ENT_QUOTES, 'UTF-8') ?></button>
    </div>
    <div class="generic-accordion__content profile-account-delete__plan" data-delete-content>
        <div class="profile-account-delete__status" aria-live="polite" data-delete-status></div>
        <div data-delete-plan></div>
        <div class="profile-account-delete__confirmation" data-delete-confirmation hidden>
            <label class="profile-merge-tool__field">
                <span data-delete-confirmation-label></span>
                <input type="text" class="generic-form-control" autocomplete="off" data-delete-confirmation-input>
            </label>
            <button type="button" class="generic-action-button generic-action-button--danger" disabled data-delete-complete><?= htmlspecialchars(profilPopupT('profile.popup.delete.complete'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</section>
</div>

<?= commonPageScriptTags('/common/assets/profile-tools.js', [
    'config' => $configuration,
    'profilePopupMergeStatusMfaRequired' => profilPopupT('profile.popup.merge.status.mfa_required'),
]) ?>
