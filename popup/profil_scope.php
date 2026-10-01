<?php
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");
require_once("../common/patreon.php");
require_once("../common/user_competence_ui.php");
require_once("../common/user_profile_ui.php");
require_once("../common/leaflet_helper.php");
require_once(__DIR__ . "/profil_translation_helper.php");

$connected = checklogin();
if (!$connected) {
    die(profilPopupT('profile.popup.error.login_required'));
}

$currentUserId = function_exists('commonGetCurrentUserId')
    ? (int)commonGetCurrentUserId()
    : (int)($_SESSION["currentUser"] ?? 0);
$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$requestedScope = 'general';
if (isset($_GET['scope']) && $_GET['scope'] === 'organization') {
    $requestedScope = 'organization';
} elseif (isset($_GET['scope']) && $_GET['scope'] === 'all') {
    $requestedScope = 'all';
}
$requestedSection = isset($_GET['section']) ? (string)$_GET['section'] : 'all';

$user = new \dbObject\User();
$user->load($currentUserId);
if (!($user->get("id") > 0)) {
    die(profilPopupT('profile.popup.error.unknown_user'));
}

$scope = 'general';
$organizationMembership = null;
$hasOrganizationScope = false;

if ($currentOrganizationId > 0) {
    $organization = new \dbObject\Organization();
    if ($organization->load($currentOrganizationId)) {
        $hasOrganizationScope = true;
        if ($requestedScope === 'organization') {
            $organizationMembership = $user->getOrganizationMembership($currentOrganizationId);
            if (!$organizationMembership) {
                $organizationMembership = new \dbObject\UserOrganization();
                $organizationMembership->set('IDuser', (int)$user->getId());
                $organizationMembership->set('IDorganization', $currentOrganizationId);
                $organizationMembership->set('active', true);
            }
            $scope = 'organization';
        }
    }
}

$canLimitCompetenceToOrganization = $currentOrganizationId > 0;
$leafletMapsEnabled = function_exists('commonLeafletMapsEnabled') && commonLeafletMapsEnabled();
$userHasPassword = trim((string)$user->get('password')) !== '';
$userAllowsPasswordLogin = commonUserAllowsPasswordLogin($user);

function profilScopeFormatDateTime($value)
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('d.m.Y H:i');
    }

    return profilPopupT('profile.popup.value.not_provided');
}

function profilScopeFormatAmountCents($value)
{
    $amount = ((int)$value) / 100;
    return number_format($amount, 2, '.', "'");
}

function profilBuildPasswordSectionHtml($userHasPassword, $userAllowsPasswordLogin)
{
    $passwordPolicyStatusEmpty = htmlspecialchars(profilPopupT('profile.popup.password.policy.status.empty'), ENT_QUOTES, 'UTF-8');
    $passwordPolicyStatusValid = htmlspecialchars(profilPopupT('profile.popup.password.policy.status.valid'), ENT_QUOTES, 'UTF-8');
    $passwordPolicyStatusInvalid = htmlspecialchars(profilPopupT('profile.popup.password.policy.status.invalid'), ENT_QUOTES, 'UTF-8');
    $passwordPolicyMatchEmpty = htmlspecialchars(profilPopupT('profile.popup.password.policy.match.empty'), ENT_QUOTES, 'UTF-8');
    $passwordPolicyMatchValid = htmlspecialchars(profilPopupT('profile.popup.password.policy.match.valid'), ENT_QUOTES, 'UTF-8');
    $passwordPolicyMatchInvalid = htmlspecialchars(profilPopupT('profile.popup.password.policy.match.invalid'), ENT_QUOTES, 'UTF-8');
    $sectionTitle = htmlspecialchars(profilPopupT('profile.popup.password.section.title'), ENT_QUOTES, 'UTF-8');
    $sectionHelp = htmlspecialchars(profilPopupT('profile.popup.password.section.help'), ENT_QUOTES, 'UTF-8');
    $toggleLabel = htmlspecialchars(profilPopupT('profile.popup.password.toggle.label'), ENT_QUOTES, 'UTF-8');
    $toggleHelp = htmlspecialchars(profilPopupT('profile.popup.password.toggle.help'), ENT_QUOTES, 'UTF-8');
    $loginPermissionLabel = htmlspecialchars(profilPopupT('profile.popup.password.login_permission.label'), ENT_QUOTES, 'UTF-8');
    $loginPermissionHelp = htmlspecialchars(
        profilPopupT($userHasPassword
            ? 'profile.popup.password.login_permission.help'
            : 'profile.popup.password.login_permission.unavailable'),
        ENT_QUOTES,
        'UTF-8'
    );
    $statusText = htmlspecialchars(
        $userHasPassword
            ? profilPopupT('profile.popup.password.status.defined')
            : profilPopupT('profile.popup.password.status.missing'),
        ENT_QUOTES,
        'UTF-8'
    );
    $currentFieldHtml = '';

    if ($userHasPassword) {
        $currentFieldHtml = '
            <label class="profile-panel__password-field">
                <span>' . htmlspecialchars(profilPopupT('profile.popup.password.current.label'), ENT_QUOTES, 'UTF-8') . '</span>
                <input
                    type="password"
                    class="generic-form-control"
                    name="current_password"
                    id="current_password"
                    autocomplete="current-password"
                    placeholder="' . htmlspecialchars(profilPopupT('profile.popup.password.current.placeholder'), ENT_QUOTES, 'UTF-8') . '"
                    data-profile-password-field="1"
                >
            </label>
        ';
    }

    return '
        <section class="profile-panel__password-toggle generic-soft-panel generic-soft-panel--stack">
            <input type="hidden" name="allow_password_login" value="0">
            <label class="profile-panel__password-toggle-label">
                <input type="checkbox" name="allow_password_login" value="1"' . ($userAllowsPasswordLogin ? ' checked' : '') . ($userHasPassword ? '' : ' disabled') . '>
                <span>' . $loginPermissionLabel . '</span>
            </label>
            <div class="profile-panel__scope-help">' . $loginPermissionHelp . '</div>
        </section>
        <div class="profile-panel__password-toggle generic-soft-panel generic-soft-panel--stack">
            <label class="profile-panel__password-toggle-label">
                <input type="checkbox" id="profile_password_toggle" data-profile-password-toggle="1">
                <span>' . $toggleLabel . '</span>
            </label>
            <div class="profile-panel__scope-help">' . $toggleHelp . '</div>
        </div>
        <section class="profile-panel__password-section generic-soft-panel generic-soft-panel--stack" data-profile-password-section="1" hidden>
            <div class="profile-panel__password-head">
                <h4 class="generic-card-title generic-card-title--section">' . $sectionTitle . '</h4>
                <p class="profile-panel__scope-help">' . $sectionHelp . '</p>
                <div class="profile-panel__password-status">' . $statusText . '</div>
            </div>
            <div class="profile-panel__password-grid">
                ' . $currentFieldHtml . '
                <label class="profile-panel__password-field">
                    <span>' . htmlspecialchars(profilPopupT('profile.popup.password.new.label'), ENT_QUOTES, 'UTF-8') . '</span>
                    <input
                        type="password"
                        class="generic-form-control"
                        name="new_password"
                        id="new_password"
                        autocomplete="new-password"
                        placeholder="' . htmlspecialchars(profilPopupT('profile.popup.password.new.placeholder'), ENT_QUOTES, 'UTF-8') . '"
                        data-profile-password-field="1"
                    >
                </label>
                <label class="profile-panel__password-field">
                    <span>' . htmlspecialchars(profilPopupT('profile.popup.password.confirm.label'), ENT_QUOTES, 'UTF-8') . '</span>
                    <input
                        type="password"
                        class="generic-form-control"
                        name="new_password_confirm"
                        id="new_password_confirm"
                        autocomplete="new-password"
                        placeholder="' . htmlspecialchars(profilPopupT('profile.popup.password.confirm.placeholder'), ENT_QUOTES, 'UTF-8') . '"
                        data-profile-password-field="1"
                    >
                </label>
            </div>
            <div
                class="common-password-policy"
                data-password-policy="1"
                data-password-policy-password-selector="#new_password"
                data-password-policy-confirm-selector="#new_password_confirm"
                data-password-policy-email-selector="#email"
                data-password-policy-min-length="' . (int)commonGetPasswordPolicyMinLength() . '"
                data-password-policy-required-keys="length,lower,upper,digit,special"
                data-password-policy-status-empty="' . $passwordPolicyStatusEmpty . '"
                data-password-policy-status-valid="' . $passwordPolicyStatusValid . '"
                data-password-policy-status-invalid="' . $passwordPolicyStatusInvalid . '"
                data-password-policy-match-empty="' . $passwordPolicyMatchEmpty . '"
                data-password-policy-match-valid="' . $passwordPolicyMatchValid . '"
                data-password-policy-match-invalid="' . $passwordPolicyMatchInvalid . '"
            >
                <span class="common-password-policy__status" data-password-status aria-live="polite">' . $passwordPolicyStatusEmpty . '</span>
                <ul class="common-password-policy__rules">
                    <li class="common-password-policy__rule" data-password-rule="length">' . htmlspecialchars(profilPopupT('profile.popup.password.policy.rule.length'), ENT_QUOTES, 'UTF-8') . '</li>
                    <li class="common-password-policy__rule" data-password-rule="lower">' . htmlspecialchars(profilPopupT('profile.popup.password.policy.rule.lower'), ENT_QUOTES, 'UTF-8') . '</li>
                    <li class="common-password-policy__rule" data-password-rule="upper">' . htmlspecialchars(profilPopupT('profile.popup.password.policy.rule.upper'), ENT_QUOTES, 'UTF-8') . '</li>
                    <li class="common-password-policy__rule" data-password-rule="digit">' . htmlspecialchars(profilPopupT('profile.popup.password.policy.rule.digit'), ENT_QUOTES, 'UTF-8') . '</li>
                    <li class="common-password-policy__rule" data-password-rule="special">' . htmlspecialchars(profilPopupT('profile.popup.password.policy.rule.special'), ENT_QUOTES, 'UTF-8') . '</li>
                    <li class="common-password-policy__rule" data-password-rule="email">' . htmlspecialchars(profilPopupT('profile.popup.password.policy.rule.email'), ENT_QUOTES, 'UTF-8') . '</li>
                </ul>
                <span class="common-password-policy__match" data-password-match aria-live="polite">' . $passwordPolicyMatchEmpty . '</span>
            </div>
            <div class="profile-panel__password-note">' . htmlspecialchars(profilPopupT('profile.popup.password.no_paste'), ENT_QUOTES, 'UTF-8') . '</div>
        </section>
    ';
}

function profilBuildTotpSectionHtml($totpEnabled)
{
    $label = htmlspecialchars(profilPopupT('profile.popup.totp.label'), ENT_QUOTES, 'UTF-8');
    $help = htmlspecialchars(profilPopupT($totpEnabled ? 'profile.popup.totp.help.enabled' : 'profile.popup.totp.help.disabled'), ENT_QUOTES, 'UTF-8');

    return '
        <section class="profile-panel__password-toggle generic-soft-panel generic-soft-panel--stack" data-profile-totp-section="1">
            <label class="profile-panel__password-toggle-label">
                <input type="checkbox" data-profile-totp-toggle="1"' . ($totpEnabled ? ' checked' : '') . '>
                <span>' . $label . '</span>
            </label>
            <div class="profile-panel__scope-help" data-profile-totp-help="1">' . $help . '</div>
            <div data-profile-totp-setup="1"></div>
        </section>
        ' . commonPageScriptTags('/common/assets/profile-totp.js', [
        'disableConfirm' => profilPopupT('profile.popup.totp.disable.confirm'),
        'setupTitle' => profilPopupT('profile.popup.totp.setup.title'),
        'setupInstructions' => profilPopupT('profile.popup.totp.setup.instructions'),
        'manualLabel' => profilPopupT('profile.popup.totp.setup.manual_label'),
        'codePlaceholder' => profilPopupT('profile.popup.totp.setup.code_placeholder'),
        'confirmLabel' => profilPopupT('profile.popup.totp.setup.confirm'),
    ]) . '
    ';
}

function profilRenderProfileFragment($scope, \dbObject\User $user, $organizationMembership, $userHasPassword, $userAllowsPasswordLogin, $leafletMapsEnabled)
{
    $fragmentUrl = '/popup/profil_scope.php?section=profile&scope=' . rawurlencode($scope);
    ob_start();
    ?>
<div
    class="profile-panel__scope-fragment"
    data-profile-fragment-kind="profile"
    data-profile-loaded-scope="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>"
    data-profile-fragment-url="<?= htmlspecialchars($fragmentUrl, ENT_QUOTES, 'UTF-8') ?>"
>
    <?php if ($scope === 'organization' && $organizationMembership): ?>
    <p class="profile-panel__scope-help">
        <?= htmlspecialchars(profilPopupT('profile.popup.scope.organization_intro')) ?>
    </p>
    <?php
    $params = array(
        "buttons" => false,
        "action" => "/ajax/saveaccount_organization.php?origin=profil&scope=organization",
        "success" => "profileHandleOrganizationSaved()",
        "sections" => array(
            array(
                "title" => profilPopupT('profile.popup.form.identity.organization'),
                "fields" => array("image", "username", "presentation"),
            ),
            array(
                "title" => profilPopupT('profile.popup.form.contact.organization'),
                "fields" => array("email", "phone"),
            ),
        ),
        "fields" => array(
            "image",
            "username",
            "email",
			"phone",
            "presentation",
        ),
    );
    $organizationMembership->display("adminEdit.php", $params);
    ?>
    <div class="profile-panel__actions">
        <button
            type="button"
            class="generic-action-button generic-action-button--main"
            data-profile-submit-button="1"
        ><?= htmlspecialchars(profilPopupT('profile.popup.scope.organization_submit')) ?></button>
    </div>
    <?php else: ?>
    <?php
    $params = array(
        "buttons" => false,
        "action" => "/ajax/saveaccount.php?origin=profil&scope=general",
        "success" => "profileHandleGeneralSaved()",
        "allowProtectedFields" => true,
        "afterTableHtml" => '<section class="profile-panel__security generic-form-section">'
            . '<h3 class="generic-card-title generic-card-title--section">'
            . htmlspecialchars(profilPopupT('profile.popup.form.security'), ENT_QUOTES, 'UTF-8')
            . '</h3>'
            . profilBuildPasswordSectionHtml($userHasPassword, $userAllowsPasswordLogin)
            . profilBuildTotpSectionHtml(commonUserHasTotpEnabled($user))
            . '</section>',
        "sections" => array(
            array(
                "title" => profilPopupT('profile.popup.form.identity.general'),
                "fields" => array("image", "username", "firstname", "lastname", "presentation"),
            ),
            array(
                "title" => profilPopupT('profile.popup.form.contact.general'),
                "fields" => array("birthdate", "latlong", "email", "phone"),
            ),
        ),
        "fields" => array(
            "image",
            "username",
            "firstname",
            "lastname",
            "presentation",
            "birthdate",
            "email",
			"phone",
        ),
    );
    if ($leafletMapsEnabled) {
        array_splice($params["fields"], 5, 0, "latlong");
    }
    $user->display("adminEdit.php", $params);
    ?>
    <div class="profile-panel__actions">
        <button
            type="button"
            class="generic-action-button generic-action-button--main"
            data-profile-submit-button="1"
        ><?= htmlspecialchars(profilPopupT('profile.popup.scope.general_submit')) ?></button>
    </div>
    <?php endif; ?>

    <?= commonPageScriptTags('/common/assets/profile-competences.js', [
    'profileDirtyKey' => 'profile_' . $scope,
    'passwordFieldActionBlockedMessage' => profilPopupT('profile.popup.password.js.paste_blocked'),
    'jqueryRequiredMessage' => profilPopupT('profile.popup.scope.jquery_required'),
]) ?>
</div>
    <?php

    return ob_get_clean();
}

function profilRenderCompetenceEditorSection($scope, \dbObject\User $user, $currentOrganizationId, $currentUserId, $canLimitCompetenceToOrganization)
{
    $competenceRows = $user->getCompetenceRowsForScope($scope, $currentOrganizationId, $currentUserId);
    $competenceSectionTitle = $scope === 'organization'
        ? profilPopupT('profile.popup.competence.section.organization_title')
        : profilPopupT('profile.popup.competence.section.general_title');
    $competenceSectionHelp = $scope === 'organization'
        ? profilPopupT('profile.popup.competence.section.organization_help')
        : profilPopupT('profile.popup.competence.section.general_help');
    ?>
    <section class="profile-panel__competence-section generic-section generic-section--stack" data-profile-competence-scope="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>">
        <div class="profile-panel__competence-head">
            <h4 class="generic-card-title generic-card-title--section"><?= htmlspecialchars($competenceSectionTitle, ENT_QUOTES, 'UTF-8') ?></h4>
            <p class="profile-panel__scope-help"><?= htmlspecialchars($competenceSectionHelp, ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="profile-panel__competence-list">
            <?php if (count($competenceRows) === 0): ?>
                <div class="profile-panel__competence-empty"><?= htmlspecialchars(profilPopupT('profile.popup.competence.empty')) ?></div>
            <?php else: ?>
                <?php foreach ($competenceRows as $competenceRow): ?>
                    <?php
                    $editorPayload = array(
                        'id' => (int)$competenceRow['id'],
                        'scope' => (string)($competenceRow['scope'] ?? $scope),
                        'name' => (string)$competenceRow['name'],
                        'description' => (string)($competenceRow['description'] ?? ''),
                        'category' => (string)$competenceRow['category'],
                        'level' => (int)$competenceRow['level'],
                        'limitToOrganization' => (string)($competenceRow['scope'] ?? 'general') === 'organization',
                    );
                    ?>
                    <article class="profile-panel__competence-card profile-panel__competence-row generic-soft-panel">
                        <div class="profile-panel__competence-row-main">
                            <div class="profile-panel__competence-row-title"><?= htmlspecialchars((string)$competenceRow['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (trim((string)($competenceRow['description'] ?? '')) !== ''): ?>
                                <div class="profile-panel__competence-row-description"><?= htmlspecialchars((string)$competenceRow['description'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>

                            <div class="profile-panel__competence-meta">
                                <span class="profile-panel__competence-badge"><?= htmlspecialchars((string)$competenceRow['levelLabel'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="profile-panel__competence-badge profile-panel__competence-badge--muted"><?= htmlspecialchars((string)$competenceRow['categoryLabel'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ((string)($competenceRow['scope'] ?? 'general') === 'organization'): ?>
                                    <span class="profile-panel__competence-badge profile-panel__competence-badge--muted"><?= htmlspecialchars((string)$competenceRow['scopeLabel'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                                <?php if ((int)$competenceRow['validationCount'] > 0): ?>
                                    <span class="profile-panel__competence-badge profile-panel__competence-badge--muted"><?= htmlspecialchars(profilPopupT('profile.popup.competence.validation_count', ['count' => (int)$competenceRow['validationCount']])) ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($competenceRow['validators'])): ?>
                                <div class="profile-panel__competence-validators">
                                    <div class="profile-panel__competence-validators-label"><?= htmlspecialchars(profilPopupT('profile.popup.competence.validators_label')) ?></div>
                                    <div class="profile-panel__competence-avatar-stack">
                                        <?php foreach ($competenceRow['validators'] as $validator): ?>
                                            <?php omoRenderCompetenceAvatar([
                                                'photoUrl' => (string)($validator['photoUrl'] ?? ''),
                                                'displayName' => (string)($validator['displayName'] ?? ''),
                                                'initials' => (string)($validator['initials'] ?? 'P'),
                                                'levelLabel' => (string)($validator['levelLabel'] ?? ''),
                                            ], 'profile-panel__competence-avatar'); ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="profile-panel__competence-row-actions">
                            <button
                                type="button"
                                class="generic-action-button generic-action-button--secondary"
                                data-profile-competence-edit="1"
                                data-profile-competence-payload="<?= htmlspecialchars(json_encode($editorPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"
                            ><?= htmlspecialchars(profilPopupT('profile.popup.competence.edit')) ?></button>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </section>
    <?php
}

function profilRenderCompetenceCreateForm($defaultScope, $canLimitCompetenceToOrganization)
{
    ?>
    <form class="profile-panel__competence-card profile-panel__competence-card--new generic-soft-panel profile-panel__competence-editor" data-profile-competence-form="1" hidden>
        <input type="hidden" name="scope" value="<?= htmlspecialchars($defaultScope, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id" value="">

        <div class="profile-panel__competence-editor-header">
            <h4 class="generic-card-title generic-card-title--section" data-profile-competence-editor-title><?= htmlspecialchars(profilPopupT('profile.popup.competence.editor.create_title')) ?></h4>
        </div>

        <div class="profile-panel__competence-grid">
            <label class="profile-panel__competence-field">
                <span><?= htmlspecialchars(profilPopupT('profile.popup.competence.field.new_name')) ?></span>
                <input type="text" class="generic-form-control" name="name" value="" maxlength="190" required>
            </label>

            <label class="profile-panel__competence-field">
                <span><?= htmlspecialchars(profilPopupT('profile.popup.competence.field.description')) ?></span>
                <input type="text" class="generic-form-control" name="description" value="" maxlength="500" placeholder="<?= htmlspecialchars(profilPopupT('profile.popup.competence.field.description_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
            </label>

            <label class="profile-panel__competence-field">
                <span><?= htmlspecialchars(profilPopupT('profile.popup.competence.field.category')) ?></span>
                <select name="category" class="generic-form-control">
                    <?php omoRenderCompetenceTypeOptions('technical'); ?>
                </select>
            </label>

            <label class="profile-panel__competence-field">
                <span><?= htmlspecialchars(profilPopupT('profile.popup.competence.field.level')) ?></span>
                <select name="level" class="generic-form-control" required>
                    <?php omoRenderCompetenceLevelOptions(0, true); ?>
                </select>
            </label>
        </div>

        <?php if ($canLimitCompetenceToOrganization): ?>
            <div class="profile-panel__competence-scope-row">
                <?php omoRenderCompetenceScopeToggle($defaultScope === 'organization'); ?>
            </div>
        <?php endif; ?>

        <div class="profile-panel__competence-actions">
            <button type="submit" class="generic-action-button generic-action-button--main" data-profile-competence-submit-label="1"><?= htmlspecialchars(profilPopupT('profile.popup.competence.add')) ?></button>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-profile-competence-delete="1" hidden><?= htmlspecialchars(profilPopupT('profile.popup.competence.delete')) ?></button>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-profile-competence-cancel="1"><?= htmlspecialchars(profilPopupT('profile.popup.competence.cancel')) ?></button>
        </div>
    </form>
    <?php
}

function profilRenderCompetenceFragment(array $scopes, \dbObject\User $user, $currentOrganizationId, $currentUserId, $canLimitCompetenceToOrganization)
{
    $scopeValue = count($scopes) > 1 ? 'all' : $scopes[0];
    $fragmentUrl = '/popup/profil_scope.php?section=competence&scope=' . rawurlencode($scopeValue);
    ob_start();
    ?>
<div
    class="profile-panel__scope-fragment"
    data-profile-fragment-kind="competence"
    data-profile-loaded-scope="<?= htmlspecialchars($scopeValue, ENT_QUOTES, 'UTF-8') ?>"
    data-profile-fragment-url="<?= htmlspecialchars($fragmentUrl, ENT_QUOTES, 'UTF-8') ?>"
>
    <?php foreach ($scopes as $editorScope): ?>
        <?php profilRenderCompetenceEditorSection($editorScope, $user, $currentOrganizationId, $currentUserId, $canLimitCompetenceToOrganization); ?>
    <?php endforeach; ?>

    <?php
    $defaultCreateScope = count($scopes) === 1 && $scopes[0] === 'organization'
        ? 'organization'
        : 'general';
    ?>

    <div class="profile-panel__competence-actions">
        <button
            type="button"
            class="generic-action-button generic-action-button--main"
            data-profile-competence-create="1"
            data-profile-competence-default-scope="<?= htmlspecialchars($defaultCreateScope, ENT_QUOTES, 'UTF-8') ?>"
        ><?= htmlspecialchars(profilPopupT('profile.popup.competence.create_button')) ?></button>
    </div>

    <?php profilRenderCompetenceCreateForm($defaultCreateScope, $canLimitCompetenceToOrganization); ?>

    <div class="profile-panel__competence-feedback" data-profile-competence-feedback="1"></div>

    <?= commonPageScriptTags('/common/assets/profile-competence-editor.js', [
    'reloadErrorMessage' => profilPopupT('profile.popup.competence.js.reload_error'),
    'invalidResponseMessage' => profilPopupT('profile.popup.js.invalid_response'),
    'saveErrorMessage' => profilPopupT('profile.popup.competence.js.save_error'),
    'saveSuccessMessage' => profilPopupT('profile.popup.competence.js.save_success'),
    'deleteConfirmMessage' => profilPopupT('profile.popup.competence.js.delete_confirm'),
    'deleteErrorMessage' => profilPopupT('profile.popup.competence.js.delete_error'),
    'deleteSuccessMessage' => profilPopupT('profile.popup.competence.js.delete_success'),
    'createTitle' => profilPopupT('profile.popup.competence.editor.create_title'),
    'editTitle' => profilPopupT('profile.popup.competence.editor.edit_title'),
    'addLabel' => profilPopupT('profile.popup.competence.add'),
    'saveLabel' => profilPopupT('profile.popup.competence.save'),
    'profileDirtyKey' => 'profile_competence_' . $scopeValue,
]) ?>
</div>
    <?php

    return ob_get_clean();
}

function profilRenderPatreonFragment(\dbObject\User $user)
{
    if (!patreonSupportUiIsEnabled()) {
        return '<div class="profile-panel__scope-fragment" data-profile-fragment-kind="patreon"><div class="profile-panel__feedback">Patreon indisponible.</div></div>';
    }

    $patreonConnection = \dbObject\UserPatreon::findByUserId((int)$user->getId());
    $patreonConnected = $patreonConnection !== false && $patreonConnection->isConnected();

    ob_start();
    ?>
<div class="profile-panel__scope-fragment" data-profile-fragment-kind="patreon">
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
            <?= htmlspecialchars(profilScopeFormatAmountCents((int)$patreonConnection->get('currently_entitled_amount_cents'))) ?>
        </div>
        <div class="profile-panel__item generic-soft-panel generic-soft-panel--stack">
            <strong class="generic-card-title generic-card-title--small"><?= htmlspecialchars(profilPopupT('profile.popup.patreon.sync_at.label')) ?></strong>
            <?= htmlspecialchars(profilScopeFormatDateTime($patreonConnection->get('last_sync_at'))) ?>
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
    <?php

    return ob_get_clean();
}


if ($requestedSection === 'profile') {
    echo profilRenderProfileFragment($scope, $user, $organizationMembership, $userHasPassword, $userAllowsPasswordLogin, $leafletMapsEnabled);
    return;
}

if ($requestedSection === 'competence') {
    $scopes = array('general');
    if ($requestedScope === 'organization' && $hasOrganizationScope) {
        $scopes = array('organization');
    } elseif ($requestedScope === 'all' && $hasOrganizationScope) {
        $scopes = array('general', 'organization');
    }

    echo profilRenderCompetenceFragment($scopes, $user, $currentOrganizationId, $currentUserId, $canLimitCompetenceToOrganization);
    return;
}

if ($requestedSection === 'patreon') {
    echo profilRenderPatreonFragment($user);
    return;
}

echo profilRenderProfileFragment($scope, $user, $organizationMembership, $userHasPassword, $userAllowsPasswordLogin, $leafletMapsEnabled);
echo profilRenderCompetenceFragment(array($scope), $user, $currentOrganizationId, $currentUserId, $canLimitCompetenceToOrganization);
