<?php
require_once dirname(__DIR__) . '/omo/api/bootstrap.php';
require_once dirname(__DIR__) . '/common/user_competence_ui.php';
require_once dirname(__DIR__) . '/common/user_profile_ui.php';
require_once dirname(__DIR__) . '/common/user_permission_ui.php';

use dbObject\Holon;
use dbObject\Invitation;
use dbObject\Organization;
use dbObject\User;

function omoUserContextFormatDate($value)
{
    if (!$value instanceof DateTimeInterface) {
        return '';
    }

    if (class_exists('IntlDateFormatter')) {
        $formatter = new IntlDateFormatter('fr_CH', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);
        $formatted = $formatter->format($value);
        if (is_string($formatted) && $formatted !== '') {
            return $formatted;
        }
    }

    return $value->format('d.m.Y');
}

function omoUserContextFormatAssignmentBudgetAmount($value, $isMoney = false)
{
    if (!is_numeric($value) || (float)$value < 0) {
        return '';
    }

    $amount = (float)$value;
    $formatted = rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
    if ($formatted === '') {
        $formatted = '0';
    }

    return $isMoney ? $formatted . '.-' : $formatted . 'h';
}

function omoUserContextBuildAssignmentBudgetLabels(array $assignment)
{
    $recurrenceLabels = array(
        'day' => 'par jour',
        'week' => 'par semaine',
        'month' => 'par mois',
        'year' => 'par an',
    );
    $labels = array();
    $timeRecurrence = trim((string)($assignment['timeBudgetRecurrence'] ?? ''));
    $timeAmount = omoUserContextFormatAssignmentBudgetAmount($assignment['timeBudgetHours'] ?? null);
    if ($timeAmount !== '' && isset($recurrenceLabels[$timeRecurrence])) {
        $labels[] = $timeAmount . ' ' . $recurrenceLabels[$timeRecurrence];
    }

    $moneyRecurrence = trim((string)($assignment['moneyBudgetRecurrence'] ?? ''));
    $moneyAmount = omoUserContextFormatAssignmentBudgetAmount($assignment['moneyBudget'] ?? null, true);
    if ($moneyAmount !== '' && isset($recurrenceLabels[$moneyRecurrence])) {
        $labels[] = $moneyAmount . ' ' . $recurrenceLabels[$moneyRecurrence];
    }

    return $labels;
}

function omoUserContextRenderRoleAssignment(array $assignment, $userId, $returnPopupUrl)
{
    $roleId = (int)($assignment['holonId'] ?? 0);
    $roleName = (string)($assignment['displayName'] ?? ($assignment['name'] ?? ''));
    $roleLabel = $roleName !== '' ? $roleName : ('Role ' . $roleId);
    $canEditAssignment = !empty($assignment['canEditAssignment']);
    $assignmentEditorUrl = '/omo/api/team/member_assignment_popup.php?hid=' . $roleId
        . '&user_id=' . (int)$userId
        . '&team_scope=contextual&team_query='
        . '&return_popup_url=' . rawurlencode((string)$returnPopupUrl);
    ?>
    <li class="omo-user-context__role generic-soft-panel">
        <button
            type="button"
            class="omo-user-context__role-link"
            data-user-role-cid="<?= $roleId ?>"
            aria-label="<?= omoApiEscape('Ouvrir le role ' . $roleLabel) ?>"
        >
            <div class="omo-user-context__role-head">
                <div>
                    <div class="omo-user-context__role-name-line">
                        <div class="omo-user-context__role-name"><?= omoApiEscape($roleLabel) ?></div>
                        <?php foreach ((array)($assignment['budgetLabels'] ?? []) as $budgetLabel): ?>
                            <span class="omo-user-context__role-budget">| <?= omoApiEscape($budgetLabel) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ((string)($assignment['pathLabel'] ?? '') !== ''): ?>
                        <div class="omo-user-context__role-path"><?= omoApiEscape((string)$assignment['pathLabel']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </button>
        <div class="omo-user-context__role-actions">
            <?php if (!empty($assignment['isPending'])): ?>
                <span class="omo-user-context__role-status">En attente</span>
            <?php endif; ?>
            <?php if ($canEditAssignment): ?>
                <div class="omo-user-context__role-menu generic-menu" data-user-role-menu="1">
                    <button
                        type="button"
                        class="generic-menu-toggle"
                        data-user-role-menu-toggle="1"
                        aria-haspopup="menu"
                        aria-expanded="false"
                        aria-label="<?= omoApiEscape('Actions pour le role ' . $roleLabel) ?>"
                    >...</button>
                    <div class="generic-menu-panel generic-menu-panel--wide" data-user-role-menu-panel="1" role="menu" hidden>
                        <button
                            type="button"
                            class="generic-menu-item"
                            data-user-role-edit-url="<?= omoApiEscape($assignmentEditorUrl) ?>"
                            role="menuitem"
                        >Editer l affectation</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </li>
    <?php
}

function omoUserContextRenderRightsFragment($targetUserId, $organizationId)
{
    $details = \dbObject\HolonPermission::buildEffectivePermissionDetailsForOrganization((int)$targetUserId, (int)$organizationId);
    $permissionCatalog = commonUserPermissionBuildCatalogMap();
    $holonIds = [];

    foreach ((array)($details['rows'] ?? []) as $row) {
        $holonIds[] = (int)($row['scopeHolonId'] ?? 0);
        $holonIds[] = (int)($row['assignedHolonId'] ?? 0);
        $holonIds[] = (int)($row['sourceHolonId'] ?? 0);
    }

    $holonMeta = commonUserPermissionBuildHolonMetaMap($holonIds);
    $labelsById = (array)($holonMeta['labelsById'] ?? []);
    $typeLabelsById = (array)($holonMeta['typeLabelsById'] ?? []);
    $formatHolonLabel = static function ($holonId) use ($labelsById, $typeLabelsById) {
        return commonUserPermissionFormatHolonLabel((int)$holonId, $labelsById, $typeLabelsById);
    };

    $groupedRights = [];
    foreach ((array)($details['rows'] ?? []) as $row) {
        $permissionKey = trim((string)($row['permissionKey'] ?? ''));
        $scopeType = trim((string)($row['scopeType'] ?? ''));
        $scopeHolonId = (int)($row['scopeHolonId'] ?? 0);
        if ($permissionKey === '' || $scopeType === '') {
            continue;
        }

        if (!isset($groupedRights[$permissionKey])) {
            $groupedRights[$permissionKey] = [
                'permissionKey' => $permissionKey,
                'scopes' => [],
            ];
        }

        $scopeKey = implode('|', [$scopeType, (string)$scopeHolonId]);
        if (!isset($groupedRights[$permissionKey]['scopes'][$scopeKey])) {
            $groupedRights[$permissionKey]['scopes'][$scopeKey] = [
                'scopeType' => $scopeType,
                'scopeHolonId' => $scopeHolonId,
                'sources' => [],
            ];
        }

        $sourceKey = implode('|', [
            (string)((int)($row['assignedHolonId'] ?? 0)),
            (string)((int)($row['sourceHolonId'] ?? 0)),
            (string)($row['range'] ?? ''),
        ]);
        $groupedRights[$permissionKey]['scopes'][$scopeKey]['sources'][$sourceKey] = [
            'assignedHolonId' => (int)($row['assignedHolonId'] ?? 0),
            'sourceHolonId' => (int)($row['sourceHolonId'] ?? 0),
            'range' => (string)($row['range'] ?? ''),
        ];
    }

    uasort($groupedRights, static function ($left, $right) use ($permissionCatalog) {
        $leftKey = (string)($left['permissionKey'] ?? '');
        $rightKey = (string)($right['permissionKey'] ?? '');
        $leftTitle = trim((string)($permissionCatalog[$leftKey]['title'] ?? $leftKey));
        $rightTitle = trim((string)($permissionCatalog[$rightKey]['title'] ?? $rightKey));
        return strnatcasecmp($leftTitle, $rightTitle);
    });

    foreach ($groupedRights as &$permissionGroup) {
        uasort($permissionGroup['scopes'], static function ($left, $right) use ($formatHolonLabel) {
            $leftLabel = commonUserPermissionDescribeScope(
                (string)($left['scopeType'] ?? ''),
                (int)($left['scopeHolonId'] ?? 0),
                $formatHolonLabel
            );
            $rightLabel = commonUserPermissionDescribeScope(
                (string)($right['scopeType'] ?? ''),
                (int)($right['scopeHolonId'] ?? 0),
                $formatHolonLabel
            );
            return strnatcasecmp($leftLabel, $rightLabel);
        });
    }
    unset($permissionGroup);

    ob_start();
    ?>
    <section class="omo-user-context__section generic-section generic-section--stack">
        <div class="omo-user-context__pane-copy">
            <div class="omo-user-context__section-kicker generic-card-title generic-card-title--eyebrow">Droits</div>
            <div class="generic-card-title generic-card-title--medium">Droits effectifs dans l'organisation</div>
            <div class="omo-user-context__section-copy">Chaque droit est affiché avec sa portée calculée et l’espace source quand il peut être retrouvé.</div>
        </div>

        <?php if (count($groupedRights) === 0): ?>
            <div class="omo-user-context__empty">Aucun droit effectif visible pour ce membre.</div>
        <?php else: ?>
            <div class="omo-user-context__rights-list">
                <?php foreach ($groupedRights as $group): ?>
                    <?php
                    $permissionKey = (string)$group['permissionKey'];
                    $permissionTitle = trim((string)($permissionCatalog[$permissionKey]['title'] ?? ''));
                    if ($permissionTitle === '') {
                        $permissionTitle = $permissionKey;
                    }
                    ?>
                    <article class="omo-user-context__rights-card generic-soft-panel">
                        <div class="omo-user-context__rights-pill">
                            <div class="omo-user-context__rights-pill-title"><?= omoApiEscape($permissionTitle) ?></div>
                        </div>
                        <div class="omo-user-context__rights-scope-list">
                            <?php foreach ((array)$group['scopes'] as $scope): ?>
                                <?php
                                $scopeLabel = commonUserPermissionDescribeScope(
                                    (string)$scope['scopeType'],
                                    (int)$scope['scopeHolonId'],
                                    $formatHolonLabel
                                );
                                ?>
                                <div class="omo-user-context__rights-scope-item">
                                    <div class="omo-user-context__rights-pill-scope"><?= omoApiEscape($scopeLabel) ?></div>
                                    <div class="omo-user-context__rights-source-list">
                                        <?php foreach ((array)$scope['sources'] as $source): ?>
                                            <?php
                                            $sourceHolonId = (int)($source['sourceHolonId'] ?? 0);
                                            $assignedHolonId = (int)($source['assignedHolonId'] ?? 0);
                                            $sourceLabel = $sourceHolonId > 0 ? $formatHolonLabel($sourceHolonId) : 'Source inconnue';
                                            if ($assignedHolonId > 0 && $assignedHolonId !== $sourceHolonId) {
                                                $sourceLabel .= ' - via ' . $formatHolonLabel($assignedHolonId);
                                            }
                                            ?>
                                            <span class="omo-user-context__rights-source-chip"><?= omoApiEscape($sourceLabel) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php

    return ob_get_clean();
}

function omoUserContextRenderPendingInvitationFragment(array $context)
{
    $organizationId = (int)($context['organizationId'] ?? 0);
    $userId = (int)($context['userId'] ?? 0);
    $currentHolonId = (int)($context['currentHolonId'] ?? 0);
    $displayName = trim((string)($context['displayName'] ?? ''));
    $secondaryLabel = trim((string)($context['secondaryLabel'] ?? ''));
    $contextLabel = trim((string)($context['contextLabel'] ?? ''));
    $contextName = trim((string)($context['contextName'] ?? ''));
    $organizationName = trim((string)($context['organizationName'] ?? ''));
    $statusCopy = trim((string)($context['statusCopy'] ?? ''));
    $detailCopy = trim((string)($context['detailCopy'] ?? ''));
    $manageCopy = trim((string)($context['manageCopy'] ?? ''));
    $manageRestrictedCopy = trim((string)($context['manageRestrictedCopy'] ?? ''));
    $invitationTypeLabel = trim((string)($context['invitationTypeLabel'] ?? ''));
    $pendingHolons = is_array($context['pendingHolons'] ?? null) ? $context['pendingHolons'] : [];
    $hasPendingInvitation = !empty($context['hasPendingInvitation']);
    $canManageInvitation = !empty($context['canManageInvitation']);
    $canResendInvitation = !empty($context['canResendInvitation']);
    $stateTitle = trim((string)($context['stateTitle'] ?? ''));
    $stateBadge = trim((string)($context['stateBadge'] ?? ''));
    $invitationSectionTitle = trim((string)($context['invitationSectionTitle'] ?? ''));
    $missingInvitationCopy = trim((string)($context['missingInvitationCopy'] ?? ''));
    $invitationAction = trim((string)($context['invitationAction'] ?? 'resend_invitation'));
    $invitationActionLabel = trim((string)($context['invitationActionLabel'] ?? ''));
    if (!in_array($invitationAction, array('resend_invitation', 'send_invitation'), true)) {
        $invitationAction = 'resend_invitation';
    }
    $displayTitle = $displayName !== '' ? $displayName : ($stateTitle !== '' ? $stateTitle : 'Invitation en attente');
    if ($stateTitle === '') {
        $stateTitle = 'Invitation en attente';
    }
    if ($stateBadge === '') {
        $stateBadge = 'En attente';
    }
    if ($invitationSectionTitle === '') {
        $invitationSectionTitle = 'Invitation en cours';
    }
    $contextSummary = $contextLabel !== '' ? $contextLabel : 'contexte';
    if ($contextName !== '') {
        $contextSummary .= ' - ' . $contextName;
    }
    if ($organizationName !== '') {
        $contextSummary .= ' - ' . $organizationName;
    }

    ob_start();
    ?>
    <div
        class="omo-user-pending-invitation"
        id="omoUserPendingInvitation"
        data-oid="<?= (int)$organizationId ?>"
        data-hid="<?= (int)$currentHolonId ?>"
        data-user-id="<?= (int)$userId ?>"
        data-invitation-action="<?= omoApiEscape($invitationAction) ?>"
    >
        <link rel="stylesheet" href="<?= commonAssetUrl('/common/team/pending-invitation.css') ?>">

        <div class="omo-user-pending-invitation__hero generic-hero-panel">
            <div class="generic-card-title generic-card-title--eyebrow"><?= omoApiEscape($stateTitle) ?></div>
            <h2 class="generic-card-title generic-card-title--large"><?= omoApiEscape($displayTitle) ?></h2>
            <?php if ($secondaryLabel !== ''): ?>
                <div class="omo-user-pending-invitation__secondary"><?= omoApiEscape($secondaryLabel) ?></div>
            <?php endif; ?>
            <?php if ($contextSummary !== ''): ?>
                <div class="omo-user-pending-invitation__secondary">Contexte: <?= omoApiEscape($contextSummary) ?></div>
            <?php endif; ?>
            <?php if ($statusCopy !== ''): ?>
                <p class="omo-user-pending-invitation__copy"><?= omoApiEscape($statusCopy) ?></p>
            <?php endif; ?>
            <div class="omo-user-pending-invitation__badge-row">
                <span class="omo-user-pending-invitation__badge"><?= omoApiEscape($stateBadge) ?></span>
            </div>
        </div>

        <section class="omo-user-pending-invitation__section generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium">Etat du profil</h3>
            <p class="omo-user-pending-invitation__detail"><?= omoApiEscape($detailCopy !== '' ? $detailCopy : 'Le profil detaille n est pas encore accessible.') ?></p>
        </section>

        <section class="omo-user-pending-invitation__section generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape($invitationSectionTitle) ?></h3>
            <div class="omo-user-pending-invitation__meta">
                <?php if ($secondaryLabel !== ''): ?>
                    <div class="omo-user-pending-invitation__meta-row">
                        <div class="omo-user-pending-invitation__meta-label">E-mail</div>
                        <div class="omo-user-pending-invitation__meta-value"><?= omoApiEscape($secondaryLabel) ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($invitationTypeLabel !== ''): ?>
                    <div class="omo-user-pending-invitation__meta-row">
                        <div class="omo-user-pending-invitation__meta-label">Type</div>
                        <div class="omo-user-pending-invitation__meta-value"><?= omoApiEscape($invitationTypeLabel) ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (count($pendingHolons) > 0): ?>
                <ul class="omo-user-pending-invitation__holon-list">
                    <?php foreach ($pendingHolons as $pendingHolon): ?>
                        <?php
                        $pendingHolonName = trim((string)($pendingHolon['name'] ?? ''));
                        $pendingHolonType = trim((string)($pendingHolon['typeLabel'] ?? ''));
                        ?>
                        <li>
                            <strong><?= omoApiEscape($pendingHolonName !== '' ? $pendingHolonName : 'Acces en attente') ?></strong>
                            <?php if ($pendingHolonType !== ''): ?>
                                <span class="omo-user-pending-invitation__holon-type">(<?= omoApiEscape($pendingHolonType) ?>)</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php elseif ($hasPendingInvitation): ?>
                <div class="omo-user-pending-invitation__empty">Cette invitation attend encore une réponse, mais aucun espace détaillé n’a pu être listé.</div>
            <?php else: ?>
                <div class="omo-user-pending-invitation__empty"><?= omoApiEscape($missingInvitationCopy !== '' ? $missingInvitationCopy : 'Aucune invitation active n a ete retrouvee pour ce membre. La vue affichera le profil normal des que la situation sera regularisee.') ?></div>
            <?php endif; ?>
        </section>

        <section class="omo-user-pending-invitation__section generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium">Actions</h3>
            <?php if (!$canManageInvitation): ?>
                <p class="omo-user-pending-invitation__detail"><?= omoApiEscape($manageRestrictedCopy !== '' ? $manageRestrictedCopy : 'Vous pouvez voir que cette invitation est en attente, mais seuls les responsables du contexte peuvent la gerer.') ?></p>
            <?php elseif (!$hasPendingInvitation && !$canResendInvitation): ?>
                <p class="omo-user-pending-invitation__detail">Il n y a plus d invitation active a ouvrir ou a renvoyer pour ce membre.</p>
            <?php else: ?>
                <p class="omo-user-pending-invitation__detail"><?= omoApiEscape($manageCopy !== '' ? $manageCopy : 'Vous pouvez renvoyer le message d invitation a cette adresse e-mail.') ?></p>
                <div class="omo-user-pending-invitation__actions">
                    <?php if ($canResendInvitation): ?>
                        <button
                            type="button"
                            class="omo-user-pending-invitation__action generic-action-button generic-action-button--main"
                            data-pending-invitation-resend="1"
                        ><?= omoApiEscape($invitationActionLabel !== '' ? $invitationActionLabel : 'Renvoyer l e-mail') ?></button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="omo-user-pending-invitation__feedback" id="omoUserPendingInvitationFeedback"></div>
        </section>
    </div>

    <script>
    (function () {
        var root = document.getElementById('omoUserPendingInvitation');
        var feedback = document.getElementById('omoUserPendingInvitationFeedback');
        var resendButton = root ? root.querySelector('[data-pending-invitation-resend="1"]') : null;
        var invitationAction = root ? String(root.getAttribute('data-invitation-action') || 'resend_invitation') : 'resend_invitation';

        if (!root || !feedback || !resendButton) {
            return;
        }

        var organizationId = Number(root.getAttribute('data-oid') || 0);
        var currentHolonId = Number(root.getAttribute('data-hid') || 0);
        var userId = Number(root.getAttribute('data-user-id') || 0);

        function setFeedback(message, isError) {
            feedback.textContent = message || '';
            feedback.classList.toggle('is-error', !!isError && !!message);
            feedback.classList.toggle('is-success', !isError && !!message);
        }

        resendButton.addEventListener('click', function () {
            if (!organizationId || !currentHolonId || !userId) {
                return;
            }

            resendButton.disabled = true;
            setFeedback('', false);

            var formData = new FormData();
            formData.append('hid', String(currentHolonId));
            formData.append('oid', String(organizationId));
            formData.append('user_id', String(userId));
            formData.append('action', invitationAction);

            fetch('/omo/api/team/member_action.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
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
                    resendButton.disabled = false;

                    if (!result.ok || !result.data || !result.data.status) {
                        setFeedback(result.data && result.data.message ? result.data.message : 'Impossible de renvoyer cette invitation pour le moment.', true);
                        return;
                    }

                    setFeedback(result.data.message || 'Invitation renvoyee.', false);
                })
                .catch(function () {
                    resendButton.disabled = false;
                    setFeedback('Impossible de renvoyer cette invitation pour le moment.', true);
                });
        });
    })();
    </script>
    <?php

    return ob_get_clean();
}

$organizationId = (int)($_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$userId = (int)($_GET['id'] ?? 0);
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;

if ($organizationId <= 0 || $userId <= 0) {
    http_response_code(400);
    ?>
    <div class="omo-user-context omo-user-context--error">Contexte utilisateur invalide.</div>
    <?php
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-user-context omo-user-context--error">Organisation introuvable.</div>
    <?php
    exit;
}

if (!$organization->canViewDetail()) {
    http_response_code(403);
    ?>
    <div class="omo-user-context omo-user-context--error">Acces refuse a cette organisation.</div>
    <?php
    exit;
}

$rootHolon = $organization->getEnabledStructuralRootHolon();
$hasStructureContext = $rootHolon instanceof Holon;
$currentHolon = $hasStructureContext ? $rootHolon : null;
if ($hasStructureContext && $currentHolonId > 0 && (int)$rootHolon->getId() !== $currentHolonId) {
    $candidate = new Holon();
    if (!$candidate->load($currentHolonId) || !$candidate->isDescendantOf($rootHolon->getId())) {
        http_response_code(404);
        ?>
        <div class="omo-user-context omo-user-context--error">Contexte introuvable pour cette organisation.</div>
        <?php
        exit;
    }

    $canViewCandidate = $candidate->canViewDetail()
        || (function_exists('commonCurrentShareContainsHolon') && commonCurrentShareContainsHolon($candidate));
    if (!$canViewCandidate) {
        http_response_code(403);
        ?>
        <div class="omo-user-context omo-user-context--error">Acces refuse a ce contexte.</div>
        <?php
        exit;
    }

    $currentHolon = $candidate;
}

$pendingInvitation = Invitation::findPendingForOrganizationUser($organizationId, $userId);
$user = new User();
$userLoaded = $user->load($userId);
$pendingOrganizationMembership = $userLoaded ? $user->getOrganizationMembership($organizationId) : null;
$hasUninvitedPendingMembership = $pendingOrganizationMembership
    && !(bool)$pendingOrganizationMembership->get('active')
    && !($pendingInvitation instanceof Invitation);

if ($hasUninvitedPendingMembership) {
    $pendingMemberEmail = trim((string)$user->getScopedEmail($organizationId));
    $pendingMemberDisplayName = trim((string)$user->getScopedDisplayName($organizationId));
    if ($pendingMemberDisplayName === '') {
        $pendingMemberDisplayName = $pendingMemberEmail !== '' ? $pendingMemberEmail : ('Utilisateur ' . $userId);
    }

    $canSendPendingMemberInvitation = $currentHolon->isAllowed('CAN_ADD_MEMBER');
    echo omoUserContextRenderPendingInvitationFragment([
        'organizationId' => $organizationId,
        'userId' => $userId,
        'currentHolonId' => (int)$currentHolon->getId(),
        'organizationName' => trim((string)$organization->get('name')),
        'displayName' => $pendingMemberDisplayName,
        'secondaryLabel' => $pendingMemberEmail,
        'contextLabel' => trim((string)$currentHolon->getTemplateLabel(true)),
        'contextName' => trim((string)$currentHolon->getDisplayName()),
        'stateTitle' => 'A inviter',
        'stateBadge' => 'A inviter',
        'invitationSectionTitle' => 'Invitation non envoyee',
        'statusCopy' => 'Cette personne a ete importee sans recevoir d e-mail. Son profil reste masque tant qu une invitation n est pas envoyee et acceptee.',
        'detailCopy' => 'Le compte est prepare dans cette organisation, sans que la personne ait encore ete informee de son existence.',
        'manageCopy' => 'Vous pouvez creer puis envoyer une invitation a cette adresse e-mail.',
        'manageRestrictedCopy' => 'Le compte reste en attente. Seules les personnes ayant le droit CAN_ADD_MEMBER sur ce holon peuvent envoyer une invitation.',
        'missingInvitationCopy' => 'Aucune invitation n a encore ete creee pour cette personne.',
        'pendingHolons' => array(),
        'hasPendingInvitation' => false,
        'canManageInvitation' => $canSendPendingMemberInvitation,
        'canResendInvitation' => $canSendPendingMemberInvitation,
        'invitationAction' => 'send_invitation',
        'invitationActionLabel' => 'Envoyer l e-mail d invitation',
    ]);
    exit;
}

if ($pendingInvitation instanceof Invitation && $pendingInvitation->isAdminInitiatedInvitation()) {
    $pendingInvitationEmail = trim((string)$pendingInvitation->get('email'));
    if ($pendingInvitationEmail === '' && $userLoaded) {
        $pendingInvitationEmail = trim((string)$user->getScopedEmail($organizationId));
    }

    $pendingInvitationDisplayName = $userLoaded
        ? trim((string)$user->getScopedDisplayName($organizationId))
        : '';
    if ($pendingInvitationDisplayName === '') {
        $pendingInvitationDisplayName = $pendingInvitationEmail !== ''
            ? $pendingInvitationEmail
            : ('Utilisateur ' . $userId);
    }

    $pendingInvitationHolons = $pendingInvitation->getPendingHolons();
    $canManagePendingInvitation = $currentHolon->isAllowed('CAN_ADD_MEMBER');
    echo omoUserContextRenderPendingInvitationFragment([
        'organizationId' => $organizationId,
        'userId' => $userId,
        'currentHolonId' => (int)$currentHolon->getId(),
        'organizationName' => trim((string)$organization->get('name')),
        'displayName' => $pendingInvitationDisplayName,
        'secondaryLabel' => $pendingInvitationEmail,
        'contextLabel' => trim((string)$currentHolon->getTemplateLabel(true)),
        'contextName' => trim((string)$currentHolon->getDisplayName()),
        'statusCopy' => 'Ce profil reste masque tant que la personne n a pas accepte son invitation.',
        'detailCopy' => 'Meme en mode admin d organisation, les informations du profil ne sont pas affichees avant acceptation afin d eviter tout acces sans consentement.',
        'manageCopy' => 'Vous pouvez uniquement renvoyer le message d invitation a cette adresse e-mail.',
        'manageRestrictedCopy' => 'Le profil reste masque jusqu a l acceptation de l invitation. Seules les personnes qui ont le droit CAN_ADD_MEMBER sur ce holon peuvent renvoyer le message.',
        'invitationTypeLabel' => 'Invitation admin',
        'pendingHolons' => $pendingInvitationHolons,
        'hasPendingInvitation' => true,
        'canManageInvitation' => $canManagePendingInvitation,
        'canResendInvitation' => $canManagePendingInvitation,
    ]);
    exit;
}

if (!$userLoaded) {
    if ($pendingInvitation instanceof Invitation) {
        $pendingInvitationEmail = trim((string)$pendingInvitation->get('email'));
        $pendingInvitationHolons = $pendingInvitation->getPendingHolons();
        $canManagePendingInvitation = $currentHolon->isAllowed('CAN_ADD_MEMBER');
        echo omoUserContextRenderPendingInvitationFragment([
            'organizationId' => $organizationId,
            'userId' => $userId,
            'currentHolonId' => (int)$currentHolon->getId(),
            'organizationName' => trim((string)$organization->get('name')),
            'displayName' => $pendingInvitationEmail !== '' ? $pendingInvitationEmail : ('Utilisateur ' . $userId),
            'secondaryLabel' => $pendingInvitationEmail,
            'contextLabel' => trim((string)$currentHolon->getTemplateLabel(true)),
            'contextName' => trim((string)$currentHolon->getDisplayName()),
            'statusCopy' => 'Ce membre a bien une invitation en attente, mais son profil complet n est pas encore disponible.',
            'detailCopy' => 'Pour le moment, seuls les elements lies a l invitation peuvent etre affiches. Les informations detaillees du profil seront visibles apres acceptation.',
            'manageCopy' => 'Vous pouvez renvoyer le message d invitation a cette adresse e-mail.',
            'manageRestrictedCopy' => 'Vous pouvez voir que cette invitation existe, mais seul un role avec le droit CAN_ADD_MEMBER sur ce holon peut renvoyer le message.',
            'invitationTypeLabel' => $pendingInvitation->isAdminInitiatedInvitation() ? 'Invitation admin' : 'Demande d acces',
            'pendingHolons' => $pendingInvitationHolons,
            'hasPendingInvitation' => true,
            'canManageInvitation' => $canManagePendingInvitation,
            'canResendInvitation' => $canManagePendingInvitation && $pendingInvitation->isAdminInitiatedInvitation(),
        ]);
        exit;
    }

    http_response_code(404);
    ?>
    <div class="omo-user-context omo-user-context--error">Utilisateur introuvable.</div>
    <?php
    exit;
}

if (!$user->canViewDetail()) {
    if ($pendingInvitation instanceof Invitation) {
        $pendingInvitationEmail = trim((string)$pendingInvitation->get('email'));
        if ($pendingInvitationEmail === '') {
            $pendingInvitationEmail = trim((string)$user->getScopedEmail($organizationId));
        }
        $pendingInvitationDisplayName = trim((string)$user->getScopedDisplayName($organizationId));
        if ($pendingInvitationDisplayName === '') {
            $pendingInvitationDisplayName = $pendingInvitationEmail !== '' ? $pendingInvitationEmail : ('Utilisateur ' . $userId);
        }
        $pendingInvitationHolons = $pendingInvitation->getPendingHolons();
        $canManagePendingInvitation = $currentHolon->isAllowed('CAN_ADD_MEMBER');
        echo omoUserContextRenderPendingInvitationFragment([
            'organizationId' => $organizationId,
            'userId' => $userId,
            'currentHolonId' => (int)$currentHolon->getId(),
            'organizationName' => trim((string)$organization->get('name')),
            'displayName' => $pendingInvitationDisplayName,
            'secondaryLabel' => $pendingInvitationEmail,
            'contextLabel' => trim((string)$currentHolon->getTemplateLabel(true)),
            'contextName' => trim((string)$currentHolon->getDisplayName()),
            'statusCopy' => 'Ce profil n est pas encore accessible car la personne n a pas encore accepte son invitation.',
            'detailCopy' => 'Dans cet etat, le logiciel ne dispose pas encore d un profil consultable. Seules les informations minimales de l invitation peuvent etre affichees.',
            'manageCopy' => 'Vous pouvez renvoyer le message sans ouvrir le profil complet.',
            'manageRestrictedCopy' => 'Le profil detaille reste masque tant que l invitation n a pas ete acceptee. Le renvoi est reserve aux roles qui ont CAN_ADD_MEMBER sur ce holon.',
            'invitationTypeLabel' => $pendingInvitation->isAdminInitiatedInvitation() ? 'Invitation admin' : 'Demande d acces',
            'pendingHolons' => $pendingInvitationHolons,
            'hasPendingInvitation' => true,
            'canManageInvitation' => $canManagePendingInvitation,
            'canResendInvitation' => $canManagePendingInvitation && $pendingInvitation->isAdminInitiatedInvitation(),
        ]);
        exit;
    }

    http_response_code(403);
    ?>
    <div class="omo-user-context omo-user-context--error">Acces refuse a cet utilisateur.</div>
    <?php
    exit;
}

$requestedSection = trim((string)($_GET['section'] ?? ''));
$canViewRights = $organization->canManagePermissionAssignments();
if ($requestedSection === 'rights' && $canViewRights) {
    echo omoUserContextRenderRightsFragment($userId, $organizationId);
    exit;
}
if ($requestedSection === 'rights') {
    http_response_code(404);
    exit;
}

$membership = $user->getOrganizationMembership($organizationId);
$currentViewerUserId = (int)commonGetCurrentUserId();
$hasBudgetApplication = $organization->isApplicationEnabled('budget', $currentViewerUserId);
$displayName = trim((string)$user->getScopedDisplayName($organizationId));
$email = trim((string)$user->getScopedEmail($organizationId));
$username = trim((string)$user->getScopedUsername($organizationId));
$photoUrl = trim((string)$user->getScopedProfilePhotoUrl($organizationId));
$presentation = trim((string)$user->getScopedPresentation($organizationId));
$birthdate = $user->get('birthdate');
$birthdateLabel = commonUserProfileFormatBirthDate($birthdate);
$birthdaySummary = commonUserProfileBuildBirthdaySummary($birthdate);
$joinedAt = $membership ? ($membership->get('datecreation') instanceof DateTimeInterface ? $membership->get('datecreation') : $membership->getGlobalCreatedAt()) : $user->get('datecreation');
$lastSeenAt = $membership ? ($membership->get('dateconnexion') instanceof DateTimeInterface ? $membership->get('dateconnexion') : $membership->getGlobalLastConnectionAt()) : $user->get('dateconnexion');
$joinedAtLabel = omoUserContextFormatDate($joinedAt);
$lastSeenLabel = omoUserContextFormatDate($lastSeenAt);
$isPending = $membership ? !(bool)$membership->get('active') : false;
$isAdmin = $membership ? $membership->isOrganizationAdmin() : false;
$currentAssignments = $hasStructureContext
    ? $currentHolon->getVisibleRoleAssignmentsForUser($userId, [
        'organizationId' => $organizationId,
        'includeDescendants' => false,
    ])
    : [];
$organizationAssignments = $hasStructureContext
    ? $rootHolon->getVisibleRoleAssignmentsForUser($userId, [
        'organizationId' => $organizationId,
    ])
    : [];
if ($hasBudgetApplication) {
    foreach ($currentAssignments as &$assignment) {
        $assignment['budgetLabels'] = omoUserContextBuildAssignmentBudgetLabels($assignment);
    }
    unset($assignment);

    foreach ($organizationAssignments as &$assignment) {
        $assignment['budgetLabels'] = omoUserContextBuildAssignmentBudgetLabels($assignment);
    }
    unset($assignment);
}
$competenceRows = $user->getVisibleCompetenceRows($organizationId, $currentViewerUserId);
$canValidateCompetences = $currentViewerUserId > 0
    && $currentViewerUserId !== $userId
    && commonCurrentUserHasOrganizationAccess($organizationId)
    && (!function_exists('commonGetCurrentShareToken') || commonGetCurrentShareToken() === '');
$popupReloadUrl = '/popup/user.php?id=' . (int)$userId . '&oid=' . (int)$organizationId . ($currentHolonId > 0 ? '&cid=' . (int)$currentHolonId : '');
$rightsFragmentUrl = '/popup/user.php?section=rights&id=' . (int)$userId . '&oid=' . (int)$organizationId;
$initialTab = trim((string)($_GET['tab'] ?? ''));
$initialTab = in_array($initialTab, array('current-roles', 'organization-roles'), true) ? $initialTab : '';
$showCurrentScope = $hasStructureContext && (int)$currentHolon->getId() !== (int)$rootHolon->getId();
$currentScopeName = $showCurrentScope ? trim((string)$currentHolon->getDisplayName()) : '';
$secondaryLabel = $email !== '' ? $email : ($username !== '' ? '@' . $username : '');
$initials = 'P';

if ($displayName !== '') {
    $words = preg_split('/\s+/u', $displayName) ?: [];
    $initials = '';
    foreach ($words as $word) {
        $word = trim((string)$word);
        if ($word === '') {
            continue;
        }

        $initials .= mb_substr($word, 0, 1, 'UTF-8');
        if (mb_strlen($initials, 'UTF-8') >= 2) {
            break;
        }
    }

    if ($initials === '') {
        $initials = mb_substr($displayName, 0, 1, 'UTF-8');
    }
}

$initials = mb_strtoupper($initials !== '' ? $initials : 'P', 'UTF-8');
$competenceCount = count($competenceRows);
$currentRoleCount = count($currentAssignments);
$organizationRoleCount = count($organizationAssignments);
$organizationCompetenceCount = 0;
$generalCompetenceCount = 0;
$totalValidationCount = 0;

foreach ($competenceRows as $competenceRow) {
    $totalValidationCount += (int)($competenceRow['validationCount'] ?? 0);
    if ((string)($competenceRow['scope'] ?? '') === 'organization') {
        $organizationCompetenceCount += 1;
    } else {
        $generalCompetenceCount += 1;
    }
}
?>
<div class="omo-user-context" data-user-competence-popup-url="<?= omoApiEscape($popupReloadUrl) ?>" data-user-initial-tab="<?= omoApiEscape($initialTab) ?>">
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/team/user-popup.css') ?>">

    <div class="omo-user-context__header generic-drawer-header generic-drawer-header--sticky">
        <section class="omo-user-context__profile">
            <div class="omo-user-context__photo-shell">
                <?php if ($photoUrl !== ''): ?>
                    <img
                        src="<?= omoApiEscape($photoUrl) ?>"
                        alt="<?= omoApiEscape($displayName !== '' ? $displayName : ('Utilisateur ' . $userId)) ?>"
                        class="omo-user-context__photo"
                    >
                <?php else: ?>
                    <div class="omo-user-context__photo-placeholder" aria-hidden="true"><?= omoApiEscape($initials) ?></div>
                <?php endif; ?>
            </div>

            <div class="omo-user-context__identity">
                <div class="generic-card-title generic-card-title--eyebrow">Profil membre</div>
                <h2 class="generic-card-title generic-card-title--large"><?= omoApiEscape($displayName !== '' ? $displayName : ('Utilisateur ' . $userId)) ?></h2>
                <div class="omo-user-context__secondary<?= $email === '' ? ' omo-user-context__meta-value--muted' : '' ?>">
                    <?= omoApiEscape($email !== '' ? $email : 'Non renseigne') ?>
                </div>
                <div class="omo-user-context__badges">
                    <?php if ($isAdmin): ?>
                        <span class="omo-user-context__badge omo-user-context__badge--admin">Admin de l'organisation</span>
                    <?php endif; ?>
                    <?php if ($isPending): ?>
                        <span class="omo-user-context__badge omo-user-context__badge--pending">Invitation en attente</span>
                    <?php endif; ?>
                    <span class="omo-user-context__badge"><?= $competenceCount ?> competence<?= $competenceCount > 1 ? 's' : '' ?></span>
                </div>
            </div>
        </section>
    </div>

    <div class="omo-user-context__shell">
        <div class="omo-user-context__main">
            <div class="generic-tabs omo-user-context__tabs" data-generic-tabs>
                <div class="generic-tabs__list">
                    <button
                        type="button"
                        class="generic-tabs__tab is-active"
                        data-generic-tab
                        data-generic-tab-target="omo-user-context-panel-infos"
                    >Infos</button>
                    <button
                        type="button"
                        class="generic-tabs__tab"
                        data-generic-tab
                        data-generic-tab-target="omo-user-context-panel-competences"
                    >Competences</button>
                    <?php if ($hasStructureContext && $showCurrentScope): ?>
                        <button
                            type="button"
                            class="generic-tabs__tab"
                            data-generic-tab
                            data-generic-tab-target="omo-user-context-panel-current-roles"
                        >Roles (contexte)</button>
                    <?php endif; ?>
                    <?php if ($hasStructureContext): ?>
                        <button
                            type="button"
                            class="generic-tabs__tab"
                            data-generic-tab
                            data-generic-tab-target="omo-user-context-panel-organization-roles"
                        >Tous les roles</button>
                    <?php endif; ?>
                    <?php if ($canViewRights): ?>
                    <button
                        type="button"
                        class="generic-tabs__tab"
                        data-generic-tab
                        data-generic-tab-target="omo-user-context-panel-rights"
                        data-user-fragment-panel="omo-user-context-panel-rights"
                    >Droits</button>
                    <?php endif; ?>
                </div>
                <div class="generic-tabs__panels">
                    <div id="omo-user-context-panel-infos" class="generic-tabs__panel" data-generic-tab-panel>
                        <section class="omo-user-context__section generic-section generic-section--stack">
                            <div class="omo-user-context__pane-copy">
                                <div class="omo-user-context__section-kicker generic-card-title generic-card-title--eyebrow">Informations</div>
                                <div class="generic-card-title generic-card-title--medium">Vue d'ensemble du membre</div>
                                <div class="omo-user-context__section-copy">Coordonnees, dates utiles et quelques indicateurs generaux pour ce profil.</div>
                            </div>

                            <?php if ($presentation !== ''): ?>
                                <div class="omo-user-context__summary generic-soft-panel">
                                    <div class="omo-user-context__summary-copy">
                                        <strong class="generic-card-title generic-card-title--small">Presentation</strong>
                                        <p class="omo-user-context__presentation"><?= nl2br(omoApiEscape($presentation)) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($birthdaySummary): ?>
                                <div class="omo-user-context__birthday-card generic-soft-panel">
                                    <div class="omo-user-context__birthday-title"><?= omoApiEscape((string)$birthdaySummary['headline']) ?></div>
                                    <?php if ((string)$birthdaySummary['detail'] !== ''): ?>
                                        <div class="omo-user-context__birthday-detail"><?= omoApiEscape((string)$birthdaySummary['detail']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="omo-user-context__info-grid">
                                <section class="omo-user-context__stats generic-section generic-section--stack">
                                    <div class="generic-card-title generic-card-title--eyebrow">Apercu</div>
                                    <div class="omo-user-context__stats-grid">
                                        <div class="omo-user-context__stat generic-soft-panel">
                                            <div class="omo-user-context__stat-value"><?= $competenceCount ?></div>
                                            <div class="omo-user-context__stat-label">Competences</div>
                                        </div>
                                        <div class="omo-user-context__stat generic-soft-panel">
                                            <div class="omo-user-context__stat-value"><?= $totalValidationCount ?></div>
                                            <div class="omo-user-context__stat-label">Avis</div>
                                        </div>
                                        <?php if ($hasStructureContext): ?>
                                            <div class="omo-user-context__stat generic-soft-panel">
                                                <div class="omo-user-context__stat-value"><?= $currentRoleCount ?></div>
                                                <div class="omo-user-context__stat-label">Roles ici</div>
                                            </div>
                                            <div class="omo-user-context__stat generic-soft-panel">
                                                <div class="omo-user-context__stat-value"><?= $organizationRoleCount ?></div>
                                                <div class="omo-user-context__stat-label">Roles orga</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </section>

                                <section class="omo-user-context__meta-card generic-section generic-section--stack">
                                    <div class="generic-card-title generic-card-title--eyebrow">Details</div>
                                    <div class="omo-user-context__meta-list">
                                        <div class="omo-user-context__meta-item">
                                            <div class="omo-user-context__meta-label generic-card-title generic-card-title--small">E-mail</div>
                                            <div class="omo-user-context__meta-value<?= $email === '' ? ' omo-user-context__meta-value--muted' : '' ?>">
                                                <?= omoApiEscape($email !== '' ? $email : 'Non renseigne') ?>
                                            </div>
                                        </div>
                                        <div class="omo-user-context__meta-item">
                                            <div class="omo-user-context__meta-label generic-card-title generic-card-title--small">Nom d'utilisateur</div>
                                            <div class="omo-user-context__meta-value<?= $username === '' ? ' omo-user-context__meta-value--muted' : '' ?>">
                                                <?= omoApiEscape($username !== '' ? $username : 'Non renseigne') ?>
                                            </div>
                                        </div>
                                        <div class="omo-user-context__meta-item">
                                            <div class="omo-user-context__meta-label generic-card-title generic-card-title--small">Ajout a l'organisation</div>
                                            <div class="omo-user-context__meta-value<?= $joinedAtLabel === '' ? ' omo-user-context__meta-value--muted' : '' ?>">
                                                <?= omoApiEscape($joinedAtLabel !== '' ? $joinedAtLabel : 'Inconnu') ?>
                                            </div>
                                        </div>
                                        <div class="omo-user-context__meta-item">
                                            <div class="omo-user-context__meta-label generic-card-title generic-card-title--small">Derniere connexion</div>
                                            <div class="omo-user-context__meta-value<?= $lastSeenLabel === '' ? ' omo-user-context__meta-value--muted' : '' ?>">
                                                <?= omoApiEscape($lastSeenLabel !== '' ? $lastSeenLabel : 'Jamais') ?>
                                            </div>
                                        </div>
                                        <div class="omo-user-context__meta-item">
                                            <div class="omo-user-context__meta-label generic-card-title generic-card-title--small">Date de naissance</div>
                                            <div class="omo-user-context__meta-value<?= $birthdateLabel === '' ? ' omo-user-context__meta-value--muted' : '' ?>">
                                                <?= omoApiEscape($birthdateLabel !== '' ? $birthdateLabel : 'Non renseignee') ?>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </section>
                    </div>

                    <div id="omo-user-context-panel-competences" class="generic-tabs__panel" data-generic-tab-panel hidden>
                        <section class="omo-user-context__section generic-section generic-section--stack">
                            <div class="omo-user-context__pane-head">
                                <div class="omo-user-context__pane-copy">
                                    <div class="omo-user-context__section-kicker generic-card-title generic-card-title--eyebrow">Competences visibles</div>
                                    <div class="generic-card-title generic-card-title--medium">Vue detaillee des competences declarees</div>
                                    <div class="omo-user-context__section-copy">
                                        Niveau, validations et contexte d'application sont regroupes dans une meme vue.
                                    </div>
                                </div>
                            </div>

               

                            <?php if ($competenceCount === 0): ?>
                                <div class="omo-user-context__empty">Aucune competence visible pour le moment.</div>
                            <?php else: ?>
                                <div class="omo-user-context__competence-labels" aria-hidden="true">
                                    <div>Competence</div>
                                    <div>Niveau et recommandations</div>
                                </div>

                                <div class="omo-user-context__competences">
                                    <?php foreach ($competenceRows as $competenceRow): ?>
                                        <?php
                                        $competenceName = trim((string)($competenceRow['name'] ?? ''));
                                        $competenceDescription = trim((string)($competenceRow['description'] ?? ''));
                                        $competenceLevel = (int)($competenceRow['level'] ?? 0);
                                        $competenceValidators = is_array($competenceRow['validators'] ?? null) ? $competenceRow['validators'] : [];
                                        $competenceValidatorPreview = array_slice($competenceValidators, 0, 4);
                                        $competenceValidatorOverflow = count($competenceValidators) - count($competenceValidatorPreview);
                                        $competenceScopeLabel = (string)($competenceRow['scope'] ?? '') === 'organization'
                                            ? (string)($competenceRow['scopeLabel'] ?? '')
                                            : '';
                                        $competenceMetaFallback = trim(implode(' - ', array_filter([
                                            (string)($competenceRow['categoryLabel'] ?? ''),
                                            $competenceScopeLabel,
                                        ])));
                                        ?>
                                        <article class="omo-user-context__competence generic-soft-panel">
                                            <div class="omo-user-context__competence-grid">
                                                <div class="omo-user-context__competence-main">
                                                    <div class="omo-user-context__competence-copy">
                                                        <div class="omo-user-context__competence-name" title="<?= omoApiEscape($competenceName) ?>">
                                                            <?= omoApiEscape($competenceName) ?>
                                                        </div>
                                                        <?php if ($competenceDescription !== ''): ?>
                                                            <div class="omo-user-context__competence-description" title="<?= omoApiEscape($competenceDescription) ?>">
                                                                <?= omoApiEscape($competenceDescription) ?>
                                                            </div>
                                                        <?php elseif ($competenceMetaFallback !== ''): ?>
                                                            <div class="omo-user-context__competence-description" title="<?= omoApiEscape($competenceMetaFallback) ?>">
                                                                <?= omoApiEscape($competenceMetaFallback) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="omo-user-context__competence-meta">
                                                            <span class="omo-user-context__competence-badge"><?= omoApiEscape((string)$competenceRow['categoryLabel']) ?></span>
                                                            <?php if ((string)($competenceRow['scope'] ?? '') === 'organization'): ?>
                                                                <span class="omo-user-context__competence-badge omo-user-context__competence-badge--muted"><?= omoApiEscape((string)$competenceRow['scopeLabel']) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="omo-user-context__competence-side">
                                                    <div class="omo-user-context__competence-level" aria-label="<?= omoApiEscape((string)$competenceRow['levelLabel']) ?>">
                                                        <div class="omo-user-context__competence-level-track">
                                                            <?php for ($levelStep = 1; $levelStep <= 5; $levelStep++): ?>
                                                                <span class="omo-user-context__competence-level-step<?= $competenceLevel >= $levelStep ? ' is-active' : '' ?>"></span>
                                                            <?php endfor; ?>
                                                        </div>
                                                        <div class="omo-user-context__competence-level-label"><?= omoApiEscape((string)$competenceRow['levelLabel']) ?></div>
                                                    </div>

                                                    <div class="omo-user-context__competence-validators">

                                                        <?php if (!empty($competenceValidatorPreview)): ?>
                                                            <div class="omo-user-context__competence-avatar-stack">
                                                                <?= (int)$competenceRow['validationCount'] > 0 ? "<span>".(int)$competenceRow['validationCount']  . ((int)$competenceRow['validationCount'] > 1 ? 's' : '')."</span>" : 'Pas encore de recommandation' ?>
                                                                <?php foreach ($competenceValidatorPreview as $validator): ?>
                                                                    <?php omoRenderCompetenceAvatar([
                                                                        'photoUrl' => (string)($validator['photoUrl'] ?? ''),
                                                                        'displayName' => (string)($validator['displayName'] ?? ''),
                                                                        'initials' => (string)($validator['initials'] ?? 'P'),
                                                                        'levelLabel' => (string)($validator['levelLabel'] ?? ''),
                                                                    ], 'omo-user-context__competence-avatar'); ?>
                                                                <?php endforeach; ?>
                                                                <?php if ($competenceValidatorOverflow > 0): ?>
                                                                    <span class="omo-user-context__competence-more">+<?= $competenceValidatorOverflow ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="omo-user-context__competence-action">
                                                    <?php if ($canValidateCompetences): ?>
                                                        <button
                                                            type="button"
                                                            class="omo-user-context__competence-menu-toggle generic-action-button generic-action-button--secondary"
                                                            data-user-competence-menu-toggle="1"
                                                            aria-expanded="false"
                                                            aria-label="Editer la recommandation pour <?= omoApiEscape($competenceName) ?>"
                                                        >...</button>
                                                        <div class="omo-user-context__competence-menu" data-user-competence-menu="1" hidden>
                                                            <div class="omo-user-context__competence-menu-title"><?= omoApiEscape($competenceName) ?></div>
                                                            <p class="omo-user-context__competence-menu-copy">
                                                                <?= (int)$competenceRow['currentViewerValidationLevel'] > 0 ? 'Modifier votre recommandation.' : 'Ajouter une recommandation discrete.' ?>
                                                            </p>
                                                            <form class="omo-user-context__competence-form" data-user-competence-validate-form="1">
                                                                <input type="hidden" name="id" value="<?= (int)$competenceRow['id'] ?>">
                                                                <select name="level" class="generic-form-control" title="Votre reconnaissance pour cette competence">
                                                                    <?php omoRenderCompetenceLevelOptions((int)$competenceRow['currentViewerValidationLevel'], true); ?>
                                                                </select>
                                                                <button type="submit" class="generic-action-button generic-action-button--main">
                                                                    <?= (int)$competenceRow['currentViewerValidationLevel'] > 0 ? 'Enregistrer' : 'Recommander' ?>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="omo-user-context__supporting"></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($canValidateCompetences): ?>
                                    <div class="omo-user-context__competence-feedback" data-user-competence-feedback="1"></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </section>
                    </div>

                    <?php if ($hasStructureContext && $showCurrentScope): ?>
                        <div id="omo-user-context-panel-current-roles" class="generic-tabs__panel" data-generic-tab-panel hidden>
                            <section class="omo-user-context__section generic-section generic-section--stack">
                                <div class="omo-user-context__pane-copy">
                                    <div class="omo-user-context__section-kicker generic-card-title generic-card-title--eyebrow">Contexte courant</div>
                                    <div class="generic-card-title generic-card-title--medium"><?= omoApiEscape($currentScopeName !== '' ? $currentScopeName : 'Contexte actif') ?></div>
                                    <div class="omo-user-context__section-copy">Roles visibles uniquement dans le contexte actuellement consulte.</div>
                                </div>

                                <?php if ($currentRoleCount === 0): ?>
                                    <div class="omo-user-context__empty">Aucun role visible dans ce contexte.</div>
                                <?php else: ?>
                                    <ul class="omo-user-context__roles">
                                        <?php foreach ($currentAssignments as $assignment): ?>
                                            <?php omoUserContextRenderRoleAssignment($assignment, $userId, $popupReloadUrl . '&tab=current-roles'); ?>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </section>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasStructureContext): ?>
                        <div id="omo-user-context-panel-organization-roles" class="generic-tabs__panel" data-generic-tab-panel hidden>
                            <section class="omo-user-context__section generic-section generic-section--stack">
                                <div class="omo-user-context__pane-copy">
                                    <div class="omo-user-context__section-kicker generic-card-title generic-card-title--eyebrow">Organisation</div>
                                    <div class="generic-card-title generic-card-title--medium">Ensemble des roles visibles dans l'organisation</div>
                                    <div class="omo-user-context__section-copy">Cette vue rassemble les affectations generales et les roles attaches a d'autres branches visibles.</div>
                                </div>

                                <?php if ($organizationRoleCount === 0): ?>
                                    <div class="omo-user-context__empty">Aucun role visible dans l'organisation.</div>
                                <?php else: ?>
                                    <ul class="omo-user-context__roles">
                                        <?php foreach ($organizationAssignments as $assignment): ?>
                                            <?php omoUserContextRenderRoleAssignment($assignment, $userId, $popupReloadUrl . '&tab=organization-roles'); ?>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </section>
                        </div>
                    <?php endif; ?>

                    <?php if ($canViewRights): ?>
                    <div id="omo-user-context-panel-rights" class="generic-tabs__panel" data-generic-tab-panel hidden>
                        <div
                            class="omo-user-context__fragment-host"
                            data-user-fragment-host="1"
                            data-user-fragment-url="<?= omoApiEscape($rightsFragmentUrl) ?>"
                        ></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?= commonAssetUrl('/common/team/user-popup.js') ?>"></script>
<?php if ($canValidateCompetences && count($competenceRows) > 0): ?>
<script src="<?= commonAssetUrl('/common/team/user-actions.js') ?>"></script>
<?php endif; ?>
