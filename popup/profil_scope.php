<?php
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");
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
$requestedScope = isset($_GET['scope']) && $_GET['scope'] === 'organization' ? 'organization' : 'general';

$user = new \dbObject\User();
$user->load($currentUserId);
if (!($user->get("id") > 0)) {
    die(profilPopupT('profile.popup.error.unknown_user'));
}

$scope = 'general';
$organizationMembership = null;

if ($requestedScope === 'organization' && $currentOrganizationId > 0) {
    $organization = new \dbObject\Organization();
    if ($organization->load($currentOrganizationId)) {
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

$competenceRows = $user->getCompetenceRowsForScope($scope, $currentOrganizationId, $currentUserId);
$competenceSectionTitle = $scope === 'organization'
    ? profilPopupT('profile.popup.competence.section.organization_title')
    : profilPopupT('profile.popup.competence.section.general_title');
$competenceSectionHelp = $scope === 'organization'
    ? profilPopupT('profile.popup.competence.section.organization_help')
    : profilPopupT('profile.popup.competence.section.general_help');
$canLimitCompetenceToOrganization = $currentOrganizationId > 0;
$leafletMapsEnabled = function_exists('commonLeafletMapsEnabled') && commonLeafletMapsEnabled();
?>
<div class="profile-panel__scope-fragment" data-profile-loaded-scope="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>">
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
        <button type="button" id="updateprofil-organization" class="generic-action-button generic-action-button--main"><?= htmlspecialchars(profilPopupT('profile.popup.scope.organization_submit')) ?></button>
    </div>
    <?php else: ?>
    <?php
    $params = array(
        "buttons" => false,
        "action" => "/ajax/saveaccount.php?origin=profil&scope=general",
        "success" => "profileHandleGeneralSaved()",
        "allowProtectedFields" => true,
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
        <button type="button" id="updateprofil-general" class="generic-action-button generic-action-button--main"><?= htmlspecialchars(profilPopupT('profile.popup.scope.general_submit')) ?></button>
    </div>
    <?php endif; ?>

    <section class="profile-panel__competence-section generic-section generic-section--stack">
        <div class="profile-panel__competence-head">
            <h4 class="generic-card-title generic-card-title--section"><?= htmlspecialchars($competenceSectionTitle, ENT_QUOTES, 'UTF-8') ?></h4>
            <p class="profile-panel__scope-help"><?= htmlspecialchars($competenceSectionHelp, ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="profile-panel__competence-list">
            <?php if (count($competenceRows) === 0): ?>
                <div class="profile-panel__competence-empty"><?= htmlspecialchars(profilPopupT('profile.popup.competence.empty')) ?></div>
            <?php else: ?>
                <?php foreach ($competenceRows as $competenceRow): ?>
                    <form class="profile-panel__competence-card generic-soft-panel" data-profile-competence-form="1">
                        <input type="hidden" name="scope" value="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= (int)$competenceRow['id'] ?>">

                        <div class="profile-panel__competence-grid">
                            <label class="profile-panel__competence-field">
                                <span><?= htmlspecialchars(profilPopupT('profile.popup.competence.field.name')) ?></span>
                                <input type="text" class="generic-form-control" name="name" value="<?= htmlspecialchars((string)$competenceRow['name'], ENT_QUOTES, 'UTF-8') ?>" maxlength="190" required>
                            </label>

                            <label class="profile-panel__competence-field">
                                <span><?= htmlspecialchars(profilPopupT('profile.popup.competence.field.description')) ?></span>
                                <input type="text" class="generic-form-control" name="description" value="<?= htmlspecialchars((string)($competenceRow['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="500" placeholder="<?= htmlspecialchars(profilPopupT('profile.popup.competence.field.description_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                            </label>

                            <label class="profile-panel__competence-field">
                                <span><?= htmlspecialchars(profilPopupT('profile.popup.competence.field.category')) ?></span>
                                <select name="category" class="generic-form-control">
                                    <?php omoRenderCompetenceTypeOptions((string)$competenceRow['category']); ?>
                                </select>
                            </label>

                            <label class="profile-panel__competence-field">
                                <span><?= htmlspecialchars(profilPopupT('profile.popup.competence.field.level')) ?></span>
                                <select name="level" class="generic-form-control" required>
                                    <?php omoRenderCompetenceLevelOptions((int)$competenceRow['level']); ?>
                                </select>
                            </label>
                        </div>

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

                        <?php if ($canLimitCompetenceToOrganization): ?>
                            <div class="profile-panel__competence-scope-row">
                                <?php omoRenderCompetenceScopeToggle((string)($competenceRow['scope'] ?? 'general') === 'organization'); ?>
                            </div>
                        <?php endif; ?>

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

                        <div class="profile-panel__competence-actions">
                            <button type="submit" class="generic-action-button generic-action-button--main"><?= htmlspecialchars(profilPopupT('profile.popup.competence.save')) ?></button>
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-profile-competence-delete="1"><?= htmlspecialchars(profilPopupT('profile.popup.competence.delete')) ?></button>
                        </div>
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form class="profile-panel__competence-card profile-panel__competence-card--new generic-soft-panel" data-profile-competence-form="1">
            <input type="hidden" name="scope" value="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" value="">

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
                    <?php omoRenderCompetenceScopeToggle($scope === 'organization'); ?>
                </div>
            <?php endif; ?>

            <div class="profile-panel__competence-actions">
                <button type="submit" class="generic-action-button generic-action-button--main"><?= htmlspecialchars(profilPopupT('profile.popup.competence.add')) ?></button>
            </div>
        </form>

        <div class="profile-panel__competence-feedback" data-profile-competence-feedback="1"></div>
    </section>

    <script>
    (function () {
        var fragment = document.querySelector('.profile-panel__scope-fragment[data-profile-loaded-scope="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>"]');
        if (!fragment) {
            return;
        }

        var feedback = fragment.querySelector('[data-profile-competence-feedback="1"]');
        var reloadErrorMessage = <?= json_encode(profilPopupT('profile.popup.competence.js.reload_error'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var invalidResponseMessage = <?= json_encode(profilPopupT('profile.popup.js.invalid_response'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var saveErrorMessage = <?= json_encode(profilPopupT('profile.popup.competence.js.save_error'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var saveSuccessMessage = <?= json_encode(profilPopupT('profile.popup.competence.js.save_success'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var deleteConfirmMessage = <?= json_encode(profilPopupT('profile.popup.competence.js.delete_confirm'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var deleteErrorMessage = <?= json_encode(profilPopupT('profile.popup.competence.js.delete_error'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var deleteSuccessMessage = <?= json_encode(profilPopupT('profile.popup.competence.js.delete_success'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var jqueryRequiredMessage = <?= json_encode(profilPopupT('profile.popup.scope.jquery_required'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

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

        function reloadScope() {
            var scopeName = fragment.getAttribute('data-profile-loaded-scope') || 'general';
            var container = document.getElementById('profileScopeContent');
            if (!container) {
                return;
            }

            fetch('/popup/profil_scope.php?scope=' + encodeURIComponent(scopeName), {
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
                    container.innerHTML = html;
                    executeEmbeddedScripts(container);
                })
                .catch(function () {
                    setFeedback(reloadErrorMessage, 'error');
                });
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

        decorateAdminEditForm();

        ['updateprofil-general', 'updateprofil-organization'].forEach(function (buttonId) {
            var button = document.getElementById(buttonId);
            if (!button) {
                return;
            }

            button.addEventListener('click', function () {
                if (window.jQuery && window.jQuery('#formulaire-edit').length) {
                    window.jQuery('#formulaire-edit').trigger('submit');
                    return;
                }

                alert(jqueryRequiredMessage);
            });
        });

        fragment.querySelectorAll('[data-profile-competence-form="1"]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                if (typeof window.omoBeginPendingAction === 'function' && !window.omoBeginPendingAction(form)) {
                    return;
                }

                setFeedback('', '');

                var formData = new FormData(form);

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
                        reloadScope();
                    })
                    .catch(function () {
                        setFeedback(saveErrorMessage, 'error');
                    })
                    .finally(function () {
                        if (typeof window.omoEndPendingAction === 'function') {
                            window.omoEndPendingAction(form);
                        }
                    });
            });

            var deleteButton = form.querySelector('[data-profile-competence-delete="1"]');
            if (!deleteButton) {
                return;
            }

            deleteButton.addEventListener('click', function () {
                var identifier = form.querySelector('input[name="id"]');
                if (!identifier || !identifier.value) {
                    return;
                }

                if (!confirm(deleteConfirmMessage)) {
                    return;
                }

                setFeedback('', '');

                if (typeof window.omoBeginPendingAction === 'function' && !window.omoBeginPendingAction(form)) {
                    return;
                }

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
                        reloadScope();
                    })
                    .catch(function () {
                        setFeedback(deleteErrorMessage, 'error');
                    })
                    .finally(function () {
                        if (typeof window.omoEndPendingAction === 'function') {
                            window.omoEndPendingAction(form);
                        }
                    });
            });
        });
    })();
    </script>
</div>
