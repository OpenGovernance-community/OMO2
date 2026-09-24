<?php
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");
require_once("../common/patreon.php");
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
$activeFullName = trim((string)$user->get('firstname') . ' ' . (string)$user->get('lastname'));
$requestedScope = isset($_GET['scope']) && $_GET['scope'] === 'organization' ? 'organization' : 'general';
$requestedTab = isset($_GET['tab']) ? (string)$_GET['tab'] : '';
$initialTab = 'general';

if (in_array($requestedTab, array('general', 'organization', 'competences', 'patreon', 'tools'), true)) {
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

?>
<link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/profile.css') ?>">
<script src="/common/assets/password_policy.js"></script>

<div class="profile-panel" id="profilePanelRoot">
    <div class="profile-panel__shell">
        <div class="profile-panel__intro generic-soft-panel">
            <?php if ($activePhotoUrl !== ''): ?>
                <div class="profile-panel__photo" style="background-image:url('<?= htmlspecialchars($activePhotoUrl, ENT_QUOTES, 'UTF-8') ?>');" aria-hidden="true"></div>
            <?php endif; ?>
            <div class="profile-panel__intro-copy">
                <div class="generic-card-title generic-card-title--section"><?= htmlspecialchars($activeFullName !== '' ? $activeFullName : ($activeUsername !== '' ? $activeUsername : profilPopupT('profile.popup.section.edit.title'))) ?></div>
                <div class="profile-panel__header-meta">
                    <?php if ($activeEmail !== ''): ?>
                        <span class="profile-panel__competence-badge profile-panel__competence-badge--muted"><?= htmlspecialchars($activeEmail) ?></span>
                    <?php endif; ?>
                    <?php if ($hasOrganizationScope && $organization instanceof \dbObject\Organization): ?>
                        <span class="profile-panel__competence-badge profile-panel__competence-badge--muted"><?= htmlspecialchars((string)$organization->getLabel()) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
            <div class="generic-tabs generic-tabs--embedded profile-panel__tabs" data-generic-tabs>
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
                        data-profile-fragment-panel="profile-panel-tab-patreon"
                    ><?= htmlspecialchars(profilPopupT('profile.popup.tabs.patreon')) ?></button>
                    <?php endif; ?>
                    <button
                        type="button"
                        class="generic-tabs__tab<?= $initialTab === 'tools' ? ' is-active' : '' ?>"
                        data-generic-tab
                        data-generic-tab-target="profile-panel-tab-tools"
                        data-profile-fragment-panel="profile-panel-tab-tools"
                    ><?= htmlspecialchars(profilPopupT('profile.popup.tabs.tools')) ?></button>
                </div>

                <div class="generic-tabs__panels">
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
                        <div
                            class="profile-panel__fragment-host"
                            data-profile-fragment-host="1"
                            data-profile-fragment-kind="patreon"
                            data-profile-fragment-url="/popup/profil_scope.php?section=patreon&amp;scope=general"
                        >
                            <?php if ($initialTab === 'patreon'): ?>
                            <div class="profile-panel__feedback"><?= htmlspecialchars(profilPopupT('profile.popup.scope.loading')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div
                        id="profile-panel-tab-tools"
                        class="generic-tabs__panel profile-panel__tab-panel"
                        data-generic-tab-panel
                        <?= $initialTab !== 'tools' ? ' hidden' : '' ?>
                    >
                        <div
                            class="profile-panel__fragment-host"
                            data-profile-fragment-host="1"
                            data-profile-fragment-kind="tools"
                            data-profile-fragment-url="/popup/profil_tools.php<?= isset($_GET['token'], $_GET['code']) ? '?token=' . rawurlencode((string)$_GET['token']) . '&amp;code=' . rawurlencode((string)$_GET['code']) : '' ?>"
                        >
                            <?php if ($initialTab === 'tools'): ?>
                            <div class="profile-panel__feedback"><?= htmlspecialchars(profilPopupT('profile.popup.scope.loading')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
  
    </div>
</div>

<?= commonPageScriptTags('/common/assets/profile.js', [
    'patreonConnectOrigin' => patreonGetConnectOrigin(),
    'invalidResponseMessage' => profilPopupT('profile.popup.js.invalid_response'),
    'scopeLoadingMessage' => profilPopupT('profile.popup.scope.loading'),
    'scopeLoadErrorMessage' => profilPopupT('profile.popup.scope.load_error'),
    'disconnectConfirmMessage' => profilPopupT('profile.popup.js.disconnect_confirm'),
    'unsavedChangesMessage' => profilPopupT('profile.popup.js.unsaved_changes'),
]) ?>
