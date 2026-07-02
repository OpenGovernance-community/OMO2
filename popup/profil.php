<?php
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");
require_once("../common/patreon.php");
require_once("../common/user_profile_ui.php");
require_once(__DIR__ . "/profil_translation_helper.php");

$connected = checklogin();
if (!$connected) {
    die(profilPopupT('profile.popup.error.login_required'));
}

$currentUserId = function_exists('commonGetCurrentUserId')
    ? (int)commonGetCurrentUserId()
    : (int)($_SESSION["currentUser"] ?? 0);
$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);

$user = new \dbObject\User();
$user->load($currentUserId);
if (!($user->get("id") > 0)) {
    die(profilPopupT('profile.popup.error.unknown_user'));
}

$organization = null;
$hasOrganizationScope = false;

if ($currentOrganizationId > 0) {
    $organization = new \dbObject\Organization();
    if ($organization->load($currentOrganizationId)) {
        $hasOrganizationScope = true;
    }
}

$activeEmail = $user->getScopedEmail($currentOrganizationId);
$activeUsername = $user->getScopedUsername($currentOrganizationId);
$activePhotoUrl = $user->getScopedProfilePhotoUrl($currentOrganizationId);
$activePresentation = $user->getScopedPresentation($currentOrganizationId);
$activeFullName = trim((string)$user->get('firstname') . ' ' . (string)$user->get('lastname'));
$birthdate = $user->get('birthdate');
$birthdaySummary = commonUserProfileBuildBirthdaySummary($birthdate);
$birthdateLabel = commonUserProfileFormatBirthDate($birthdate);
$requestedScope = isset($_GET['scope']) && $_GET['scope'] === 'organization' ? 'organization' : 'general';
$requestedTab = isset($_GET['tab']) ? (string)$_GET['tab'] : '';
$initialTab = 'general';

if (in_array($requestedTab, array('current', 'general', 'organization', 'competences', 'patreon'), true)) {
    $initialTab = $requestedTab;
} elseif (isset($_GET['scope'])) {
    $initialTab = $requestedScope === 'organization' ? 'organization' : 'general';
}

if ($initialTab === 'organization' && !$hasOrganizationScope) {
    $initialTab = 'general';
}

$patreonUiEnabled = patreonSupportUiIsEnabled();
$patreonConnection = false;
$patreonConnected = false;

if ($patreonUiEnabled) {
    $patreonConnection = \dbObject\UserPatreon::findByUserId((int)$user->getId());
    $patreonConnected = $patreonConnection !== false && $patreonConnection->isConnected();
}

if ($initialTab === 'patreon' && !$patreonUiEnabled) {
    $initialTab = 'general';
}

function profilFormatDateTime($value)
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('d.m.Y H:i');
    }

    return profilPopupT('profile.popup.value.not_provided');
}

function profilFormatAmountCents($value)
{
    $amount = ((int)$value) / 100;
    return number_format($amount, 2, '.', "'");
}
?>
<style>
    .profile-panel {
        padding: 18px;
        background: var(--color-bg, var(--auth-page-bg, #f8fafc));
        color: var(--color-text, #0f172a);
        font-family: system-ui, sans-serif;
    }

    .profile-panel__sections {
        display: grid;
        gap: 18px;
    }

    .profile-panel__section {
        --generic-section-padding-block: 18px;
        --generic-section-padding-inline: 18px;
        --generic-section-border: var(--color-border, #dbe4ee);
    }

    .profile-panel__section h3 {
        margin: 0 0 14px;
    }

    .profile-panel__summary {
        display: grid;
        gap: 10px;
    }

    .profile-panel__item {
        --generic-soft-panel-padding-block: 12px;
        --generic-soft-panel-padding-inline: 14px;
        --generic-soft-panel-border: var(--color-border, #dbe4ee);
        --generic-soft-panel-background: var(--color-surface-alt, #f8fafc);
        --generic-soft-panel-radius: 12px;
    }

    .profile-panel__item strong {
        display: block;
        margin-bottom: 4px;
    }

    .profile-panel__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 16px;
    }

    .profile-panel button[type='button'],
    .profile-panel button[type='submit'] {
        font: inherit;
    }

    .profile-panel button[type='button'].profile-panel__button-secondary,
    .profile-panel button[type='submit'].profile-panel__button-secondary {
        background: color-mix(in srgb, var(--color-text, #0f172a) 92%, var(--color-surface, #ffffff));
        color: var(--color-text-inverse, #ffffff);
    }

    .profile-panel button[type='button'].profile-panel__button-muted {
        background: var(--color-surface-alt, #e2e8f0);
        color: var(--color-text, #0f172a);
    }

    .profile-panel button[disabled] {
        cursor: not-allowed;
        opacity: .75;
    }

    .profile-panel__note {
        margin-top: 14px;
        padding: 12px 14px;
        border-radius: 12px;
        background: color-mix(in srgb, #f59e0b 10%, var(--color-surface, #ffffff));
        border: 1px solid color-mix(in srgb, #f59e0b 22%, var(--color-border, #fed7aa));
        color: #9a3412;
    }

    .profile-panel .dbobjecttable {
        width: 100%;
    }

    .profile-panel .dbobjecttable th,
    .profile-panel .dbobjecttable td {
        vertical-align: top;
    }

    .profile-panel .dbobjecttable th {
        padding-right: 12px;
        text-align: left;
        white-space: nowrap;
    }

    .profile-panel__tabs {
        --generic-tabs-list-padding-inline: 0;
        --generic-tabs-panel-padding-block: 18px;
        --generic-tabs-panel-padding-inline: 18px;
        --generic-tabs-panel-radius: 18px;
    }

    .profile-panel__tab-panel {
        display: grid;
        gap: 16px;
    }

    .profile-panel__fragment-host {
        display: grid;
        gap: 14px;
        min-height: 72px;
    }

    .profile-panel__photo {
        width: 72px;
        height: 72px;
        border-radius: 999px;
        background: var(--color-surface-alt, #dbe4ee) center center / cover no-repeat;
        border: 1px solid var(--color-border-strong, #cbd5e1);
    }

    .profile-panel__field small,
    .profile-panel__scope-help,
    .profile-panel__feedback {
        color: var(--color-text-light, #64748b);
        line-height: 1.45;
    }

    .profile-panel__feedback {
        min-height: 22px;
        font-weight: 600;
    }

    .profile-panel__feedback.is-success {
        color: #15803d;
    }

    .profile-panel__feedback.is-error {
        color: #b91c1c;
    }

    .profile-panel__photo-empty {
        color: var(--color-text-light, #64748b);
        font-size: 0.92rem;
    }

    .profile-panel__password-section {
        margin-top: 18px;
    }

    .profile-panel__password-section[hidden] {
        display: none !important;
    }

    .profile-panel__password-toggle {
        display: grid;
        gap: 8px;
        margin-top: 18px;
    }

    .profile-panel__password-toggle-label {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        color: var(--color-text, #334155);
    }

    .profile-panel__password-toggle-label input {
        margin: 0;
    }

    .profile-panel__password-head {
        display: grid;
        gap: 6px;
        margin-bottom: 14px;
    }

    .profile-panel__password-status,
    .profile-panel__password-note {
        color: var(--color-text-light, #64748b);
        line-height: 1.45;
    }

    .profile-panel__password-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    .profile-panel__password-field {
        display: grid;
        gap: 6px;
    }

    .profile-panel__password-field span {
        font-size: 13px;
        font-weight: 700;
        color: var(--color-text, #334155);
    }

    .profile-panel__competence-section {
        --generic-section-gap: 14px;
    }

    .profile-panel__competence-head {
        display: grid;
        gap: 6px;
    }

    .profile-panel__competence-list {
        display: grid;
        gap: 12px;
    }

    .profile-panel__competence-card {
        display: grid;
        gap: 12px;
    }

    .profile-panel__competence-row {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: start;
    }

    .profile-panel__competence-row-main {
        display: grid;
        gap: 8px;
        min-width: 0;
    }

    .profile-panel__competence-row-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--color-text, #0f172a);
    }

    .profile-panel__competence-row-description {
        color: var(--color-text-light, #64748b);
        line-height: 1.45;
    }

    .profile-panel__competence-row-actions {
        display: flex;
        justify-content: flex-end;
    }

    .profile-panel__competence-card--new {
        border-style: dashed;
    }

    .profile-panel__competence-editor[hidden] {
        display: none !important;
    }

    .profile-panel__competence-editor-header {
        display: grid;
        gap: 6px;
    }

    .profile-panel__competence-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    }

    .profile-panel__competence-field {
        display: grid;
        gap: 6px;
    }

    .profile-panel__competence-field span,
    .profile-panel__competence-validators-label {
        font-size: 13px;
        font-weight: 700;
        color: var(--color-text, #334155);
    }

    .profile-panel__competence-field input,
    .profile-panel__competence-field select {
        width: 100%;
        min-height: 44px;
        padding: 10px 12px;
        border: 1px solid var(--color-border, #dbe4ee);
        border-radius: 12px;
        background: var(--color-surface, #fff);
        color: inherit;
        font: inherit;
        box-sizing: border-box;
    }

    .profile-panel__competence-meta,
    .profile-panel__competence-actions,
    .profile-panel__competence-validators,
    .profile-panel__competence-avatar-stack {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    .profile-panel__competence-badge {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-primary, #2563eb) 14%, var(--color-surface, #ffffff));
        color: var(--color-primary, #1d4ed8);
        font-size: 12px;
        font-weight: 700;
    }

    .profile-panel__competence-badge--muted {
        background: var(--color-surface-alt, #e2e8f0);
        color: var(--color-text-light, #475569);
    }

    .profile-panel__competence-avatar,
    .profile-panel__competence-avatar--placeholder {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        border: 1px solid var(--color-border-strong, #cbd5e1);
        background: var(--color-surface-alt, #e2e8f0);
    }

    .profile-panel__competence-avatar {
        object-fit: cover;
        display: block;
    }

    .profile-panel__competence-avatar--placeholder {
        display: inline-grid;
        place-items: center;
        font-size: 11px;
        font-weight: 700;
        color: color-mix(in srgb, var(--color-primary, #2563eb) 78%, var(--color-text, #0f172a));
    }

    .profile-panel__competence-empty,
    .profile-panel__competence-feedback {
        color: var(--color-text-light, #64748b);
        line-height: 1.45;
    }

    .profile-panel__competence-feedback.is-success {
        color: #15803d;
    }

    .profile-panel__competence-feedback.is-error {
        color: #b91c1c;
    }

    .profile-panel__competence-scope-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }

    .profile-panel__competence-scope-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 40px;
        padding: 8px 12px;
        border: 1px solid var(--color-border, #dbe4ee);
        border-radius: 12px;
        background: var(--color-surface, #fff);
        color: var(--color-text, #334155);
        font-size: 13px;
        font-weight: 600;
    }

    .profile-panel__competence-scope-toggle input {
        margin: 0;
    }
</style>
<script src="/common/assets/password_policy.js"></script>

<div class="profile-panel" id="profilePanelRoot">
    <div class="profile-panel__sections">
        <section class="profile-panel__section generic-section">
            <h3 class="generic-card-title generic-card-title--section"><?= htmlspecialchars(profilPopupT('profile.popup.section.edit.title')) ?></h3>


            <div class="generic-tabs profile-panel__tabs" data-generic-tabs>
                <div class="generic-tabs__list" aria-label="<?= htmlspecialchars(profilPopupT('profile.popup.tabs.aria')) ?>">
                    <button
                        type="button"
                        class="generic-tabs__tab<?= $initialTab === 'general' ? ' is-active' : '' ?>"
                        data-generic-tab
                        data-generic-tab-target="profile-panel-tab-general"
                        data-profile-fragment-panel="profile-panel-tab-general"
                    ><?= htmlspecialchars(profilPopupT('profile.popup.tabs.general')) ?></button>
                    <?php if ($hasOrganizationScope): ?>
                    <button
                        type="button"
                        class="generic-tabs__tab<?= $initialTab === 'organization' ? ' is-active' : '' ?>"
                        data-generic-tab
                        data-generic-tab-target="profile-panel-tab-organization"
                        data-profile-fragment-panel="profile-panel-tab-organization"
                    ><?= htmlspecialchars(profilPopupT('profile.popup.tabs.organization')) ?></button>
                    <?php endif; ?>
                    <button
                        type="button"
                        class="generic-tabs__tab<?= $initialTab === 'current' ? ' is-active' : '' ?>"
                        data-generic-tab
                        data-generic-tab-target="profile-panel-tab-current"
                    ><?= htmlspecialchars(profilPopupT('profile.popup.tabs.current')) ?></button>
                    <button
                        type="button"
                        class="generic-tabs__tab<?= $initialTab === 'competences' ? ' is-active' : '' ?>"
                        data-generic-tab
                        data-generic-tab-target="profile-panel-tab-competences"
                        data-profile-fragment-panel="profile-panel-tab-competences"
                    ><?= htmlspecialchars(profilPopupT('profile.popup.tabs.competences')) ?></button>
                    <?php if ($patreonUiEnabled): ?>
                    <button
                        type="button"
                        class="generic-tabs__tab<?= $initialTab === 'patreon' ? ' is-active' : '' ?>"
                        data-generic-tab
                        data-generic-tab-target="profile-panel-tab-patreon"
                    ><?= htmlspecialchars(profilPopupT('profile.popup.tabs.patreon')) ?></button>
                    <?php endif; ?>
                </div>

                <div class="generic-tabs__panels">
                    <div
                        id="profile-panel-tab-current"
                        class="generic-tabs__panel profile-panel__tab-panel"
                        data-generic-tab-panel
                        <?= $initialTab !== 'current' ? ' hidden' : '' ?>
                    >
                        <div class="profile-panel__summary">
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.active.context.label')) ?></strong>
                                <?= htmlspecialchars($hasOrganizationScope && $organization
                                    ? profilPopupT('profile.popup.active.context.organization', ['organizationName' => (string)$organization->get('name')])
                                    : profilPopupT('profile.popup.active.context.general')) ?>
                            </div>
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.active.photo.label')) ?></strong>
                                <div class="profile-panel__photo"<?= $activePhotoUrl !== '' ? ' style="background-image:url(' . htmlspecialchars($activePhotoUrl, ENT_QUOTES, 'UTF-8') . ')"' : '' ?>></div>
                            </div>
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.active.email.label')) ?></strong>
                                <?= htmlspecialchars($activeEmail !== '' ? $activeEmail : profilPopupT('profile.popup.value.not_provided')) ?>
                            </div>
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.active.username.label')) ?></strong>
                                <?= htmlspecialchars($activeUsername !== '' ? $activeUsername : profilPopupT('profile.popup.value.not_provided')) ?>
                            </div>
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.active.fullname.label')) ?></strong>
                                <?= htmlspecialchars($activeFullName !== '' ? $activeFullName : profilPopupT('profile.popup.value.not_provided')) ?>
                            </div>
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.active.presentation.label')) ?></strong>
                                <?= nl2br(htmlspecialchars($activePresentation !== '' ? $activePresentation : profilPopupT('profile.popup.value.no_presentation'), ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.active.birthdate.label')) ?></strong>
                                <?= htmlspecialchars($birthdateLabel !== '' ? $birthdateLabel : profilPopupT('profile.popup.value.not_provided')) ?>
                            </div>
                            <?php if (is_array($birthdaySummary)): ?>
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.active.birthday.label')) ?></strong>
                                <div><?= htmlspecialchars((string)$birthdaySummary['headline'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if ((string)($birthdaySummary['detail'] ?? '') !== ''): ?>
                                    <small><?= htmlspecialchars((string)$birthdaySummary['detail'], ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($hasOrganizationScope): ?>
                    <div
                        id="profile-panel-tab-organization"
                        class="generic-tabs__panel profile-panel__tab-panel"
                        data-generic-tab-panel
                        <?= $initialTab !== 'organization' ? ' hidden' : '' ?>
                    >
                        <div
                            class="profile-panel__fragment-host"
                            data-profile-fragment-host="1"
                            data-profile-fragment-kind="profile"
                            data-profile-fragment-url="/popup/profil_scope.php?section=profile&amp;scope=organization"
                        >
                            <?php if ($initialTab === 'organization'): ?>
                            <div class="profile-panel__feedback"><?= htmlspecialchars(profilPopupT('profile.popup.scope.loading')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div
                        id="profile-panel-tab-general"
                        class="generic-tabs__panel profile-panel__tab-panel"
                        data-generic-tab-panel
                        <?= $initialTab !== 'general' ? ' hidden' : '' ?>
                    >
                        <div
                            class="profile-panel__fragment-host"
                            data-profile-fragment-host="1"
                            data-profile-fragment-kind="profile"
                            data-profile-fragment-url="/popup/profil_scope.php?section=profile&amp;scope=general"
                        >
                            <?php if ($initialTab === 'general'): ?>
                            <div class="profile-panel__feedback"><?= htmlspecialchars(profilPopupT('profile.popup.scope.loading')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div
                        id="profile-panel-tab-competences"
                        class="generic-tabs__panel profile-panel__tab-panel"
                        data-generic-tab-panel
                        <?= $initialTab !== 'competences' ? ' hidden' : '' ?>
                    >
                        <div
                            class="profile-panel__fragment-host"
                            data-profile-fragment-host="1"
                            data-profile-fragment-kind="competence"
                            data-profile-fragment-url="/popup/profil_scope.php?section=competence&amp;scope=<?= $hasOrganizationScope ? 'all' : 'general' ?>"
                        >
                            <?php if ($initialTab === 'competences'): ?>
                            <div class="profile-panel__feedback"><?= htmlspecialchars(profilPopupT('profile.popup.scope.loading')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($patreonUiEnabled): ?>
                    <div
                        id="profile-panel-tab-patreon"
                        class="generic-tabs__panel profile-panel__tab-panel"
                        data-generic-tab-panel
                        <?= $initialTab !== 'patreon' ? ' hidden' : '' ?>
                    >
                        <h3 class="generic-card-title generic-card-title--section"><?= htmlspecialchars(profilPopupT('profile.popup.section.patreon.title')) ?></h3>
                        <div class="profile-panel__summary">
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.connection.label')) ?></strong>
                                <?= htmlspecialchars($patreonConnected
                                    ? profilPopupT('profile.popup.patreon.connection.connected')
                                    : profilPopupT('profile.popup.patreon.connection.disconnected')) ?>
                            </div>
                            <?php if ($patreonConnection !== false): ?>
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.name.label')) ?></strong>
                                <?= htmlspecialchars((string)($patreonConnection->get('full_name') ?: profilPopupT('profile.popup.value.not_provided'))) ?>
                            </div>
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.status.label')) ?></strong>
                                <?= htmlspecialchars((string)($patreonConnection->get('patron_status') ?: profilPopupT('profile.popup.value.not_provided'))) ?>
                            </div>
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.payment.label')) ?></strong>
                                <?= htmlspecialchars((string)($patreonConnection->get('last_charge_status') ?: profilPopupT('profile.popup.value.not_provided'))) ?>
                            </div>
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.tiers.label')) ?></strong>
                                <?= nl2br(htmlspecialchars(trim((string)$patreonConnection->get('tier_titles')) !== '' ? (string)$patreonConnection->get('tier_titles') : profilPopupT('profile.popup.patreon.tiers.none'))) ?>
                            </div>
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.amount.label')) ?></strong>
                                <?= htmlspecialchars(profilFormatAmountCents((int)$patreonConnection->get('currently_entitled_amount_cents'))) ?>
                            </div>
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.sync_at.label')) ?></strong>
                                <?= htmlspecialchars(profilFormatDateTime($patreonConnection->get('last_sync_at'))) ?>
                            </div>
                            <?php if (trim((string)$patreonConnection->get('last_sync_error')) !== ''): ?>
                            <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
                                <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.sync_error.label')) ?></strong>
                                <?= nl2br(htmlspecialchars((string)$patreonConnection->get('last_sync_error'))) ?>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="profile-panel__actions">
                            <button
                                type="button"
                                id="patreon_connect"
                                class="generic-action-button generic-action-button--main"
                            ><?= htmlspecialchars($patreonConnected ? profilPopupT('profile.popup.patreon.reconnect') : profilPopupT('profile.popup.patreon.connect')) ?></button>
                            <?php if ($patreonConnected): ?>
                            <button type="button" id="patreon_sync" class="profile-panel__button-secondary generic-action-button"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.sync')) ?></button>
                            <button type="button" id="patreon_disconnect" class="profile-panel__button-muted generic-action-button generic-action-button--secondary"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.disconnect')) ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
(function () {
    var root = document.getElementById("profilePanelRoot");
    var initialTabButton = root ? root.querySelector(".generic-tabs__tab.is-active[data-profile-fragment-panel]") : null;
    var invalidResponseMessage = <?= json_encode(profilPopupT('profile.popup.js.invalid_response'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var scopeLoadingMessage = <?= json_encode(profilPopupT('profile.popup.scope.loading'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var scopeLoadErrorMessage = <?= json_encode(profilPopupT('profile.popup.scope.load_error'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var disconnectConfirmMessage = <?= json_encode(profilPopupT('profile.popup.js.disconnect_confirm'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function parseJsonResponse(text) {
        try {
            return JSON.parse(text);
        } catch (error) {
            return {
                status: false,
                message: invalidResponseMessage
            };
        }
    }

    function handleLegacyAjaxResponse(payload) {
        if (payload && payload.script) {
            try {
                eval(payload.script);
            } catch (error) {
                console.error(error);
            }
        }

        if (payload && payload.message) {
            alert(payload.message);
        }
    }

    function postUrl(url) {
        return fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        }).then(function (response) {
            return response.text();
        }).then(parseJsonResponse);
    }

    function executeEmbeddedScripts(container) {
        if (!container) {
            return;
        }

        Array.prototype.forEach.call(container.querySelectorAll("script"), function (script) {
            var replacement = document.createElement("script");

            Array.prototype.forEach.call(script.attributes, function (attribute) {
                replacement.setAttribute(attribute.name, attribute.value);
            });

            if (!replacement.src) {
                replacement.textContent = script.textContent || "";
            }

            script.parentNode.replaceChild(replacement, script);
        });
    }

    function buildProfileModalUrl(tabName, scopeName) {
        var normalizedTab = tabName === "organization" || tabName === "general" || tabName === "competences" || tabName === "patreon" ? tabName : "current";
        var normalizedScope = scopeName === "organization" ? "organization" : "general";
        return "/popup/profil.php?tab=" + encodeURIComponent(normalizedTab) + "&scope=" + encodeURIComponent(normalizedScope);
    }

    function getActiveProfileTabName() {
        var activeTab = root ? root.querySelector(".generic-tabs__tab.is-active") : null;
        if (!activeTab) {
            return "current";
        }

        if (activeTab.getAttribute("data-generic-tab-target") === "profile-panel-tab-organization") {
            return "organization";
        }
        if (activeTab.getAttribute("data-generic-tab-target") === "profile-panel-tab-general") {
            return "general";
        }
        if (activeTab.getAttribute("data-generic-tab-target") === "profile-panel-tab-competences") {
            return "competences";
        }
        if (activeTab.getAttribute("data-generic-tab-target") === "profile-panel-tab-patreon") {
            return "patreon";
        }

        return "current";
    }

    function loadFragmentHost(host, forceReload) {
        var fragmentUrl;

        if (!host) {
            return;
        }

        fragmentUrl = host.getAttribute("data-profile-fragment-url") || "";
        if (fragmentUrl === "") {
            return;
        }

        if (!forceReload && host.getAttribute("data-profile-fragment-loaded") === "1") {
            return;
        }

        if (host.getAttribute("data-profile-fragment-kind") === "profile" && root) {
            Array.prototype.forEach.call(root.querySelectorAll('[data-profile-fragment-host="1"][data-profile-fragment-kind="profile"]'), function (otherHost) {
                if (otherHost === host) {
                    return;
                }

                otherHost.removeAttribute("data-profile-fragment-loaded");
                otherHost.innerHTML = "";
            });
        }

        host.innerHTML = '<div class="profile-panel__feedback">' + scopeLoadingMessage + '</div>';

        fetch(fragmentUrl, {
            credentials: "same-origin",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("Erreur de chargement");
                }

                return response.text();
            })
            .then(function (html) {
                host.innerHTML = html;
                host.setAttribute("data-profile-fragment-loaded", "1");
                executeEmbeddedScripts(host);
            })
            .catch(function () {
                host.innerHTML = '<div class="profile-panel__feedback is-error">' + scopeLoadErrorMessage + '</div>';
            });
    }

    window.profileHandleGeneralSaved = function () {
        var targetUrl = buildProfileModalUrl("general", "general");
        if (window.commonTopbarRefreshModalContent) {
            window.commonTopbarRefreshModalContent(targetUrl);
        }
        if (window.jQuery && document.getElementById("popup_content")) {
            window.jQuery("#popup_content").load(targetUrl);
        }
    };

    window.profileHandleOrganizationSaved = function () {
        var targetUrl = buildProfileModalUrl("organization", "organization");
        if (window.commonTopbarRefreshModalContent) {
            window.commonTopbarRefreshModalContent(targetUrl);
        }
        if (window.jQuery && document.getElementById("popup_content")) {
            window.jQuery("#popup_content").load(targetUrl);
        }
    };

    Array.prototype.forEach.call(document.querySelectorAll("[data-profile-fragment-panel]"), function (button) {
        button.addEventListener("click", function () {
            var panelId = button.getAttribute("data-profile-fragment-panel") || "";
            var panel = panelId !== "" ? document.getElementById(panelId) : null;
            var host = panel ? panel.querySelector('[data-profile-fragment-host="1"]') : null;
            loadFragmentHost(host, false);
        });
    });

    if (initialTabButton) {
        var initialPanelId = initialTabButton.getAttribute("data-profile-fragment-panel") || "";
        var initialPanel = initialPanelId !== "" ? document.getElementById(initialPanelId) : null;
        var initialHost = initialPanel ? initialPanel.querySelector('[data-profile-fragment-host="1"]') : null;
        loadFragmentHost(initialHost, false);
    }

    var connectButton = document.getElementById("patreon_connect");
    if (connectButton) {
        connectButton.addEventListener("click", function () {
            var width = 720;
            var height = 860;
            var left = Math.max(0, (window.screen.width - width) / 2);
            var top = Math.max(0, (window.screen.height - height) / 2);
            window.open(
                "/common/patreon_connect.php",
                "patreon_connect",
                "width=" + width + ",height=" + height + ",left=" + left + ",top=" + top + ",resizable=yes,scrollbars=yes"
            );
        });
    }

    var disconnectButton = document.getElementById("patreon_disconnect");
    if (disconnectButton) {
        disconnectButton.addEventListener("click", function () {
            if (!confirm(disconnectConfirmMessage)) {
                return;
            }

            disconnectButton.disabled = true;
            postUrl("/ajax/patreon_disconnect.php")
                .then(handleLegacyAjaxResponse)
                .finally(function () {
                    disconnectButton.disabled = false;
                });
        });
    }

    var syncButton = document.getElementById("patreon_sync");
    if (syncButton) {
        syncButton.addEventListener("click", function () {
            syncButton.disabled = true;
            postUrl("/ajax/patreon_sync.php")
                .then(handleLegacyAjaxResponse)
                .finally(function () {
                    syncButton.disabled = false;
                });
        });
    }

    window.addEventListener("message", function (event) {
        if (event.origin !== window.location.origin) {
            return;
        }

        if (event.data && event.data.type === "patreon-connected") {
            var activeTabName = getActiveProfileTabName();
            var activeScopeName = activeTabName === "organization" ? "organization" : "general";
            var targetUrl = buildProfileModalUrl(activeTabName, activeScopeName);
            if (window.commonTopbarRefreshModalContent) {
                window.commonTopbarRefreshModalContent(targetUrl);
            }
            if (window.jQuery && document.getElementById("popup_content")) {
                window.jQuery("#popup_content").load(targetUrl);
            }
        }
    });
})();
</script>
