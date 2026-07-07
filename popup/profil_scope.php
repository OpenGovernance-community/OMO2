<?php
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");
require_once("../common/patreon.php");
require_once("../common/user_competence_ui.php");
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

function profilBuildPasswordSectionHtml($userHasPassword)
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

function profilRenderProfileFragment($scope, \dbObject\User $user, $organizationMembership, $userHasPassword, $leafletMapsEnabled)
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
        "fields" => array(
            "image",
            "username",
            "email",
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
        "afterTableHtml" => profilBuildPasswordSectionHtml($userHasPassword),
        "fields" => array(
            "image",
            "username",
            "firstname",
            "lastname",
            "presentation",
            "birthdate",
            "email",
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

    <script>
    (function () {
        var currentScript = document.currentScript;
        var fragment = currentScript ? currentScript.closest('.profile-panel__scope-fragment') : null;
        var passwordFieldActionBlockedMessage = <?= json_encode(profilPopupT('profile.popup.password.js.paste_blocked'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var jqueryRequiredMessage = <?= json_encode(profilPopupT('profile.popup.scope.jquery_required'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        if (!fragment) {
            return;
        }

        function decorateAdminEditForm() {
            var form = fragment.querySelector('#formulaire-edit');
            if (!form) {
                return;
            }

            Array.prototype.forEach.call(form.querySelectorAll('input, select, textarea'), function (field) {
                var type = String(field.type || '').toLowerCase();

                if (type === 'hidden' || type === 'checkbox' || type === 'radio' || type === 'button' || type === 'submit' || type === 'color' || type === 'file' || type === 'range') {
                    return;
                }

                field.classList.add('generic-form-control');
            });

            Array.prototype.forEach.call(form.querySelectorAll('[data-profile-password-field="1"]'), function (field) {
                ['paste', 'copy', 'cut', 'drop'].forEach(function (eventName) {
                    field.addEventListener(eventName, function (event) {
                        event.preventDefault();
                        alert(passwordFieldActionBlockedMessage);
                    });
                });
            });
        }

        function initPasswordToggle() {
            var toggle = fragment.querySelector('[data-profile-password-toggle="1"]');
            var section = fragment.querySelector('[data-profile-password-section="1"]');
            if (!toggle || !section) {
                return;
            }

            function applyPasswordVisibility() {
                var isVisible = !!toggle.checked;
                section.hidden = !isVisible;

                if (isVisible) {
                    return;
                }

                Array.prototype.forEach.call(section.querySelectorAll('[data-profile-password-field="1"]'), function (field) {
                    field.value = '';
                });
            }

            toggle.addEventListener('change', applyPasswordVisibility);
            applyPasswordVisibility();
        }

        decorateAdminEditForm();
        initPasswordToggle();
        if (typeof window.commonInitPasswordPolicy === 'function') {
            window.commonInitPasswordPolicy(fragment);
        }

        Array.prototype.forEach.call(fragment.querySelectorAll('[data-profile-submit-button="1"]'), function (button) {
            button.addEventListener('click', function () {
                var form = fragment.querySelector('#formulaire-edit');
                if (window.jQuery && form) {
                    window.jQuery(form).trigger('submit');
                    return;
                }

                alert(jqueryRequiredMessage);
            });
        });
    })();
    </script>
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

    <script>
    (function () {
        var currentScript = document.currentScript;
        var fragment = currentScript ? currentScript.closest('.profile-panel__scope-fragment') : null;
        var feedback = fragment ? fragment.querySelector('[data-profile-competence-feedback="1"]') : null;
        var reloadErrorMessage = <?= json_encode(profilPopupT('profile.popup.competence.js.reload_error'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var invalidResponseMessage = <?= json_encode(profilPopupT('profile.popup.js.invalid_response'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var saveErrorMessage = <?= json_encode(profilPopupT('profile.popup.competence.js.save_error'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var saveSuccessMessage = <?= json_encode(profilPopupT('profile.popup.competence.js.save_success'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var deleteConfirmMessage = <?= json_encode(profilPopupT('profile.popup.competence.js.delete_confirm'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var deleteErrorMessage = <?= json_encode(profilPopupT('profile.popup.competence.js.delete_error'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var deleteSuccessMessage = <?= json_encode(profilPopupT('profile.popup.competence.js.delete_success'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var createTitle = <?= json_encode(profilPopupT('profile.popup.competence.editor.create_title'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var editTitle = <?= json_encode(profilPopupT('profile.popup.competence.editor.edit_title'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var addLabel = <?= json_encode(profilPopupT('profile.popup.competence.add'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var saveLabel = <?= json_encode(profilPopupT('profile.popup.competence.save'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var editorForm = fragment.querySelector('[data-profile-competence-form="1"]');
        var editorTitle = editorForm ? editorForm.querySelector('[data-profile-competence-editor-title]') : null;
        var submitButton = editorForm ? editorForm.querySelector('[data-profile-competence-submit-label="1"]') : null;
        var deleteButton = editorForm ? editorForm.querySelector('[data-profile-competence-delete="1"]') : null;
        var cancelButton = editorForm ? editorForm.querySelector('[data-profile-competence-cancel="1"]') : null;
        var createButton = fragment.querySelector('[data-profile-competence-create="1"]');

        if (!fragment) {
            return;
        }

        function setFeedback(message, type) {
            if (!feedback) {
                return;
            }

            feedback.textContent = message || '';
            feedback.className = 'profile-panel__competence-feedback';
            if (type === 'success') {
                feedback.classList.add('is-success');
            } else if (type === 'error') {
                feedback.classList.add('is-error');
            }
        }

        function parseResponse(response) {
            return response.text().then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (error) {
                    return {
                        status: false,
                        message: invalidResponseMessage
                    };
                }
            });
        }

        function executeEmbeddedScripts(container) {
            Array.prototype.forEach.call(container.querySelectorAll('script'), function (script) {
                var replacement = document.createElement('script');

                Array.prototype.forEach.call(script.attributes, function (attribute) {
                    replacement.setAttribute(attribute.name, attribute.value);
                });

                if (!replacement.src) {
                    replacement.textContent = script.textContent || '';
                }

                script.parentNode.replaceChild(replacement, script);
            });
        }

        function reloadFragment() {
            var fragmentUrl = fragment.getAttribute('data-profile-fragment-url') || '';
            if (fragmentUrl === '') {
                return;
            }

            fetch(fragmentUrl, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('load');
                    }

                    return response.text();
                })
                .then(function (html) {
                    var wrapper = document.createElement('div');
                    wrapper.innerHTML = html;
                    var replacement = wrapper.firstElementChild;

                    if (!replacement || !fragment.parentNode) {
                        throw new Error('fragment');
                    }

                    fragment.parentNode.replaceChild(replacement, fragment);
                    executeEmbeddedScripts(replacement);
                })
                .catch(function () {
                    setFeedback(reloadErrorMessage, 'error');
                });
        }

        function setFieldValue(fieldName, value) {
            var field = editorForm ? editorForm.querySelector('[name="' + fieldName + '"]') : null;
            if (field) {
                field.value = value;
            }
        }

        function setCheckboxValue(fieldName, checked) {
            var field = editorForm ? editorForm.querySelector('[name="' + fieldName + '"]') : null;
            if (field) {
                field.checked = !!checked;
            }
        }

        function openEditor(payload) {
            var isEdit = !!(payload && payload.id);

            if (!editorForm) {
                return;
            }

            setFieldValue('id', isEdit ? String(payload.id) : '');
            setFieldValue('scope', payload && payload.scope ? String(payload.scope) : (createButton ? (createButton.getAttribute('data-profile-competence-default-scope') || 'general') : 'general'));
            setFieldValue('name', payload && payload.name ? String(payload.name) : '');
            setFieldValue('description', payload && payload.description ? String(payload.description) : '');
            setFieldValue('category', payload && payload.category ? String(payload.category) : 'technical');
            setFieldValue('level', payload && payload.level ? String(payload.level) : '');
            setCheckboxValue('limit_to_organization', !!(payload && payload.limitToOrganization));

            if (editorTitle) {
                editorTitle.textContent = isEdit ? editTitle : createTitle;
            }
            if (submitButton) {
                submitButton.textContent = isEdit ? saveLabel : addLabel;
            }
            if (deleteButton) {
                deleteButton.hidden = !isEdit;
            }

            editorForm.hidden = false;
            editorForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function closeEditor() {
            if (!editorForm) {
                return;
            }

            editorForm.reset();
            setFieldValue('id', '');
            setFieldValue('scope', createButton ? (createButton.getAttribute('data-profile-competence-default-scope') || 'general') : 'general');
            setCheckboxValue('limit_to_organization', createButton && createButton.getAttribute('data-profile-competence-default-scope') === 'organization');
            if (editorTitle) {
                editorTitle.textContent = createTitle;
            }
            if (submitButton) {
                submitButton.textContent = addLabel;
            }
            if (deleteButton) {
                deleteButton.hidden = true;
            }
            editorForm.hidden = true;
        }

        if (createButton) {
            createButton.addEventListener('click', function () {
                openEditor({
                    scope: createButton.getAttribute('data-profile-competence-default-scope') || 'general',
                    limitToOrganization: (createButton.getAttribute('data-profile-competence-default-scope') || 'general') === 'organization'
                });
            });
        }

        Array.prototype.forEach.call(fragment.querySelectorAll('[data-profile-competence-edit="1"]'), function (button) {
            button.addEventListener('click', function () {
                var payloadText = button.getAttribute('data-profile-competence-payload') || '';
                var payload = null;

                try {
                    payload = JSON.parse(payloadText);
                } catch (error) {
                    payload = null;
                }

                openEditor(payload || {});
            });
        });

        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                closeEditor();
            });
        }

        if (editorForm) {
            editorForm.addEventListener('submit', function (event) {
                event.preventDefault();

                if (typeof window.omoBeginPendingAction === 'function' && !window.omoBeginPendingAction(editorForm)) {
                    return;
                }

                setFeedback('', '');

                var formData = new FormData(editorForm);

                fetch('/ajax/user_competence_save.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(parseResponse)
                    .then(function (result) {
                        if (!result || !result.status) {
                            setFeedback(result && result.message ? result.message : saveErrorMessage, 'error');
                            return;
                        }

                        setFeedback(result.message || saveSuccessMessage, 'success');
                        reloadFragment();
                    })
                    .catch(function () {
                        setFeedback(saveErrorMessage, 'error');
                    })
                    .finally(function () {
                        if (typeof window.omoEndPendingAction === 'function') {
                            window.omoEndPendingAction(editorForm);
                        }
                    });
            });
        }

        if (deleteButton) {
            deleteButton.addEventListener('click', function () {
                var identifier = editorForm ? editorForm.querySelector('input[name="id"]') : null;
                if (!identifier || !identifier.value) {
                    return;
                }

                if (!confirm(deleteConfirmMessage)) {
                    return;
                }

                if (typeof window.omoBeginPendingAction === 'function' && !window.omoBeginPendingAction(editorForm)) {
                    return;
                }

                setFeedback('', '');

                var formData = new FormData();
                formData.append('id', identifier.value);

                fetch('/ajax/user_competence_delete.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(parseResponse)
                    .then(function (result) {
                        if (!result || !result.status) {
                            setFeedback(result && result.message ? result.message : deleteErrorMessage, 'error');
                            return;
                        }

                        setFeedback(result.message || deleteSuccessMessage, 'success');
                        reloadFragment();
                    })
                    .catch(function () {
                        setFeedback(deleteErrorMessage, 'error');
                    })
                    .finally(function () {
                        if (typeof window.omoEndPendingAction === 'function') {
                            window.omoEndPendingAction(editorForm);
                        }
                    });
            });
        }

        if (editorForm) {
            closeEditor();
        }
    })();
    </script>
</div>
    <?php

    return ob_get_clean();
}

function profilRenderCurrentSummaryFragment(
    \dbObject\User $user,
    $currentOrganizationId,
    $hasOrganizationScope,
    $organization
) {
    $activeEmail = $user->getScopedEmail($currentOrganizationId);
    $activeUsername = $user->getScopedUsername($currentOrganizationId);
    $activePhotoUrl = $user->getScopedProfilePhotoUrl($currentOrganizationId);
    $activePresentation = $user->getScopedPresentation($currentOrganizationId);
    $activeFullName = trim((string)$user->get('firstname') . ' ' . (string)$user->get('lastname'));
    $birthdate = $user->get('birthdate');
    $birthdaySummary = commonUserProfileBuildBirthdaySummary($birthdate);
    $birthdateLabel = commonUserProfileFormatBirthDate($birthdate);

    ob_start();
    ?>
<div class="profile-panel__scope-fragment" data-profile-fragment-kind="current">
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
    echo profilRenderProfileFragment($scope, $user, $organizationMembership, $userHasPassword, $leafletMapsEnabled);
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

if ($requestedSection === 'current') {
    echo profilRenderCurrentSummaryFragment($user, $currentOrganizationId, $hasOrganizationScope, isset($organization) ? $organization : null);
    return;
}

if ($requestedSection === 'patreon') {
    echo profilRenderPatreonFragment($user);
    return;
}

echo profilRenderProfileFragment($scope, $user, $organizationMembership, $userHasPassword, $leafletMapsEnabled);
echo profilRenderCompetenceFragment(array($scope), $user, $currentOrganizationId, $currentUserId, $canLimitCompetenceToOrganization);
