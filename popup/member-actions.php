<?php
require_once dirname(__DIR__) . '/omo/api/bootstrap.php';
require_once dirname(__DIR__) . '/common/team/translations.php';

use dbObject\Holon;
use dbObject\Organization;
use dbObject\User;

function omoMemberActionsHolonTypeLabel(Holon $holon, ?array $lang = null, ?array $sourceLang = null)
{
    return omoTeamHolonTypeLabelByTypeId((int)$holon->get('IDtypeholon'), $lang, $sourceLang);
}

$organizationId = (int)($_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$userId = (int)($_GET['id'] ?? 0);
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$sourceLang = omoTeamSourceLang();
$lang = omoTeamLoadTranslationBundle();

if ($organizationId <= 0 || $userId <= 0) {
    http_response_code(400);
    ?>
    <div class="omo-member-actions omo-member-actions--error generic-drawer-content"><?= htmlspecialchars(omoTeamT('team.popup.invalid_member_context', [], $lang, $sourceLang)) ?></div>
    <?php
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-member-actions omo-member-actions--error generic-drawer-content"><?= htmlspecialchars(omoTeamT('team.popup.organization_not_found', [], $lang, $sourceLang)) ?></div>
    <?php
    exit;
}

if (!$organization->canViewDetail()) {
    http_response_code(403);
    ?>
    <div class="omo-member-actions omo-member-actions--error generic-drawer-content"><?= htmlspecialchars(omoTeamT('team.popup.organization_forbidden', [], $lang, $sourceLang)) ?></div>
    <?php
    exit;
}

$rootHolon = $organization->getEnabledStructuralRootHolon();
if ($rootHolon === null) {
    http_response_code(404);
    ?>
    <div class="omo-member-actions omo-member-actions--error generic-drawer-content"><?= htmlspecialchars(omoTeamT('team.popup.organization_context_missing', [], $lang, $sourceLang)) ?></div>
    <?php
    exit;
}

$currentHolon = $rootHolon;
if ($currentHolonId > 0 && (int)$rootHolon->getId() !== $currentHolonId) {
    $candidate = new Holon();
    if (!$candidate->load($currentHolonId) || !$candidate->isDescendantOf($rootHolon->getId())) {
        http_response_code(404);
        ?>
        <div class="omo-member-actions omo-member-actions--error generic-drawer-content"><?= htmlspecialchars(omoTeamT('team.popup.context_not_found', [], $lang, $sourceLang)) ?></div>
        <?php
        exit;
    }

    if (!$candidate->canViewDetail()) {
        http_response_code(403);
        ?>
        <div class="omo-member-actions omo-member-actions--error generic-drawer-content"><?= htmlspecialchars(omoTeamT('team.popup.context_forbidden', [], $lang, $sourceLang)) ?></div>
        <?php
        exit;
    }

    $currentHolon = $candidate;
}

$user = new User();
if (!$user->load($userId)) {
    http_response_code(404);
    ?>
    <div class="omo-member-actions omo-member-actions--error generic-drawer-content"><?= htmlspecialchars(omoTeamT('team.popup.user_not_found', [], $lang, $sourceLang)) ?></div>
    <?php
    exit;
}

if (!$user->canViewDetail()) {
    http_response_code(403);
    ?>
    <div class="omo-member-actions omo-member-actions--error generic-drawer-content"><?= htmlspecialchars(omoTeamT('team.popup.user_forbidden', [], $lang, $sourceLang)) ?></div>
    <?php
    exit;
}

$membership = $user->getOrganizationMembership($organizationId);
$displayName = trim((string)$user->getScopedDisplayName($organizationId));
$secondaryLabel = trim((string)$user->getScopedEmail($organizationId));
if ($secondaryLabel === '') {
    $username = trim((string)$user->getScopedUsername($organizationId));
    $secondaryLabel = $username !== '' ? '@' . $username : '';
}

$isPending = $membership ? !(bool)$membership->get('active') : false;
$contextAdminUserIds = array_fill_keys($currentHolon->getDirectContextAdminUserIds($organizationId), true);
$isContextAdmin = isset($contextAdminUserIds[$userId]);
$currentHolonTemplateLabel = trim((string)$currentHolon->getTemplateLabel(true));
if ($currentHolonTemplateLabel === '') {
    $currentHolonTemplateLabel = omoMemberActionsHolonTypeLabel($currentHolon, $lang, $sourceLang);
}

$currentHolonName = trim((string)$currentHolon->getDisplayName());
$canManageCurrentHolonMembers = $currentHolon->canEdit();
?>
<div
    class="omo-member-actions generic-drawer-content"
    id="omoMemberActionsPopup"
    data-user-id="<?= (int)$userId ?>"
    data-oid="<?= (int)$organizationId ?>"
    data-hid="<?= (int)$currentHolon->getId() ?>"
    data-root-hid="<?= (int)$rootHolon->getId() ?>"
    data-context-label="<?= htmlspecialchars((string)$currentHolonTemplateLabel, ENT_QUOTES, 'UTF-8') ?>"
    data-display-name="<?= htmlspecialchars((string)($displayName !== '' ? $displayName : omoTeamT('team.member.user_fallback', ['userId' => (string)$userId], $lang, $sourceLang)), ENT_QUOTES, 'UTF-8') ?>"
>
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/team/member-actions.css') ?>">

    <div class="omo-member-actions__hero generic-hero-panel">
        <div class="omo-member-actions__eyebrow generic-card-title generic-card-title--eyebrow"><?= htmlspecialchars(omoTeamT('team.popup.contextual_actions', [], $lang, $sourceLang)) ?></div>
        <h2 class="generic-card-title generic-card-title--large"><?= htmlspecialchars((string)($displayName !== '' ? $displayName : omoTeamT('team.member.user_fallback', ['userId' => (string)$userId], $lang, $sourceLang))) ?></h2>
        <?php if ($secondaryLabel !== ''): ?>
            <div class="omo-member-actions__secondary"><?= htmlspecialchars($secondaryLabel) ?></div>
        <?php endif; ?>
        <div class="omo-member-actions__secondary">
            <?= htmlspecialchars(omoTeamT('team.popup.context_prefix', [], $lang, $sourceLang)) ?>: <?= htmlspecialchars($currentHolonTemplateLabel) ?>
            <?php if ($currentHolonName !== ''): ?>
                · <?= htmlspecialchars($currentHolonName) ?>
            <?php endif; ?>
        </div>
        <div class="omo-member-actions__badge-row">
            <?php if ($isPending): ?>
                <span class="omo-member-actions__badge omo-member-actions__badge--pending"><?= htmlspecialchars(omoTeamT('team.member.pending', [], $lang, $sourceLang)) ?></span>
            <?php endif; ?>
            <?php if ($isContextAdmin): ?>
                <span class="omo-member-actions__badge"><?= htmlspecialchars(omoTeamT('team.member.admin_context', [], $lang, $sourceLang)) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="omo-member-actions__section generic-section generic-section--stack">
        <h3 class="generic-card-title generic-card-title--medium"><?= htmlspecialchars(omoTeamT('team.popup.member_management', [], $lang, $sourceLang)) ?></h3>
        <?php if (!$canManageCurrentHolonMembers): ?>
            <p><?= htmlspecialchars(omoTeamT('team.popup.no_manage_rights', ['context' => (string)$currentHolonTemplateLabel], $lang, $sourceLang)) ?></p>
        <?php else: ?>
            <p><?= htmlspecialchars(omoTeamT('team.popup.choose_action', ['context' => (string)$currentHolonTemplateLabel], $lang, $sourceLang)) ?></p>
            <div class="omo-member-actions__actions">
                <button
                    type="button"
                    class="omo-member-actions__button generic-action-button generic-action-button--danger"
                    data-member-popup-action="remove"
                ><?= htmlspecialchars(omoTeamT('team.action.remove_from_context', ['context' => (string)$currentHolonTemplateLabel], $lang, $sourceLang)) ?></button>
                <?php if (!$isPending): ?>
                    <button
                        type="button"
                        class="omo-member-actions__button generic-action-button generic-action-button--secondary"
                        data-member-popup-action="<?= $isContextAdmin ? 'revoke_admin' : 'grant_admin' ?>"
                    ><?= htmlspecialchars($isContextAdmin
                        ? omoTeamT('team.action.revoke_context_admin', ['context' => (string)$currentHolonTemplateLabel], $lang, $sourceLang)
                        : omoTeamT('team.action.grant_context_admin', ['context' => (string)$currentHolonTemplateLabel], $lang, $sourceLang)) ?></button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="omo-member-actions__feedback" id="omoMemberActionsFeedback"></div>
</div>

<?= commonPageScriptTags('/common/team/member-actions.js', [
    'memberActionsText' => [
        'defaultContextLabel' => omoTeamT('team.holon_type.context', [], $lang, $sourceLang),
        'defaultMemberLabel' => omoTeamT('team.member.this_member', [], $lang, $sourceLang),
        'confirmRemove' => omoTeamT('team.confirm.remove', ['name' => '{name}', 'context' => '{context}'], $lang, $sourceLang),
        'confirmGrantAdmin' => omoTeamT('team.confirm.grant_context_admin', ['name' => '{name}', 'context' => '{context}'], $lang, $sourceLang),
        'confirmRevokeAdmin' => omoTeamT('team.confirm.revoke_context_admin', ['name' => '{name}', 'context' => '{context}'], $lang, $sourceLang),
        'updateFailed' => omoTeamT('team.message.update_failed', [], $lang, $sourceLang),
        'updateSuccess' => omoTeamT('team.message.update_success', [], $lang, $sourceLang),
        'updateFailedLater' => omoTeamT('team.message.update_failed_later', [], $lang, $sourceLang),
    ],
]) ?>
