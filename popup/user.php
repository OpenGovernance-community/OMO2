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
            <div class="omo-user-context__section-copy">Chaque droit est affiche avec sa portee calculee et le holon source quand il peut etre retrouve.</div>
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
    $displayTitle = $displayName !== '' ? $displayName : 'Invitation en attente';
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
    >
        <style>
        .omo-user-pending-invitation {
            display: grid;
            gap: 16px;
            padding: 18px;
            box-sizing: border-box;
            color: var(--color-text, #1f2937);
        }

        .omo-user-pending-invitation__hero {
            --generic-hero-gap: 10px;
            --generic-hero-padding: 20px;
            --generic-hero-radius: 22px;
        }

        .omo-user-pending-invitation__hero h2 {
            margin: 0;
        }

        .omo-user-pending-invitation__secondary,
        .omo-user-pending-invitation__copy,
        .omo-user-pending-invitation__detail,
        .omo-user-pending-invitation__meta-value,
        .omo-user-pending-invitation__empty,
        .omo-user-pending-invitation__feedback {
            color: var(--color-text-light, #6b7280);
        }

        .omo-user-pending-invitation__secondary,
        .omo-user-pending-invitation__copy,
        .omo-user-pending-invitation__detail,
        .omo-user-pending-invitation__feedback {
            line-height: 1.5;
        }

        .omo-user-pending-invitation__badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .omo-user-pending-invitation__badge {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            border-radius: 999px;
            background: rgba(100, 116, 139, 0.14);
            color: var(--color-text-light, #6b7280);
            border: 1px solid var(--color-border, #e5e7eb);
            font-size: 0.76rem;
            font-weight: 700;
        }

        .omo-user-pending-invitation__section {
            --generic-section-gap: 12px;
            --generic-section-padding-block: 18px;
            --generic-section-padding-inline: 18px;
            --generic-section-radius: 20px;
        }

        .omo-user-pending-invitation__section h3 {
            margin: 0;
            font-size: 1rem;
        }

        .omo-user-pending-invitation__meta {
            display: grid;
            gap: 10px;
        }

        .omo-user-pending-invitation__meta-row {
            display: grid;
            gap: 4px;
        }

        .omo-user-pending-invitation__meta-label {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--color-text, #1f2937);
        }

        .omo-user-pending-invitation__holon-list {
            margin: 0;
            padding-left: 18px;
            display: grid;
            gap: 8px;
        }

        .omo-user-pending-invitation__holon-list li {
            line-height: 1.45;
        }

        .omo-user-pending-invitation__holon-type {
            color: var(--color-text-light, #6b7280);
        }

        .omo-user-pending-invitation__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .omo-user-pending-invitation__action {
            text-decoration: none;
        }

        .omo-user-pending-invitation__feedback {
            min-height: 24px;
            font-size: 0.92rem;
        }

        .omo-user-pending-invitation__feedback.is-success {
            color: #166534;
        }

        .omo-user-pending-invitation__feedback.is-error {
            color: #b91c1c;
        }
        </style>

        <div class="omo-user-pending-invitation__hero generic-hero-panel">
            <div class="generic-card-title generic-card-title--eyebrow">Invitation en attente</div>
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
                <span class="omo-user-pending-invitation__badge">En attente</span>
            </div>
        </div>

        <section class="omo-user-pending-invitation__section generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium">Etat du profil</h3>
            <p class="omo-user-pending-invitation__detail"><?= omoApiEscape($detailCopy !== '' ? $detailCopy : 'Le profil detaille n est pas encore accessible.') ?></p>
        </section>

        <section class="omo-user-pending-invitation__section generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium">Invitation en cours</h3>
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
                <div class="omo-user-pending-invitation__empty">Cette invitation attend encore une reponse, mais aucun holon detaille n a pu etre liste.</div>
            <?php else: ?>
                <div class="omo-user-pending-invitation__empty">Aucune invitation active n a ete retrouvee pour ce membre. La vue affichera le profil normal des que la situation sera regularisee.</div>
            <?php endif; ?>
        </section>

        <section class="omo-user-pending-invitation__section generic-section generic-section--stack">
            <h3 class="generic-card-title generic-card-title--medium">Actions</h3>
            <?php if (!$canManageInvitation): ?>
                <p class="omo-user-pending-invitation__detail"><?= omoApiEscape($manageRestrictedCopy !== '' ? $manageRestrictedCopy : 'Vous pouvez voir que cette invitation est en attente, mais seuls les responsables du contexte peuvent la gerer.') ?></p>
            <?php elseif (!$hasPendingInvitation): ?>
                <p class="omo-user-pending-invitation__detail">Il n y a plus d invitation active a ouvrir ou a renvoyer pour ce membre.</p>
            <?php else: ?>
                <p class="omo-user-pending-invitation__detail"><?= omoApiEscape($manageCopy !== '' ? $manageCopy : 'Vous pouvez renvoyer le message d invitation a cette adresse e-mail.') ?></p>
                <div class="omo-user-pending-invitation__actions">
                    <?php if ($canResendInvitation): ?>
                        <button
                            type="button"
                            class="omo-user-pending-invitation__action generic-action-button generic-action-button--main"
                            data-pending-invitation-resend="1"
                        >Renvoyer l e-mail</button>
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
            formData.append('action', 'resend_invitation');

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
if ($requestedSection === 'rights') {
    echo omoUserContextRenderRightsFragment($userId, $organizationId);
    exit;
}

$membership = $user->getOrganizationMembership($organizationId);
$currentViewerUserId = (int)commonGetCurrentUserId();
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
    ])
    : [];
$organizationAssignments = $hasStructureContext
    ? $rootHolon->getVisibleRoleAssignmentsForUser($userId, [
        'organizationId' => $organizationId,
    ])
    : [];
$competenceRows = $user->getVisibleCompetenceRows($organizationId, $currentViewerUserId);
$canValidateCompetences = $currentViewerUserId > 0
    && $currentViewerUserId !== $userId
    && commonCurrentUserHasOrganizationAccess($organizationId)
    && (!function_exists('commonGetCurrentShareToken') || commonGetCurrentShareToken() === '');
$popupReloadUrl = '/popup/user.php?id=' . (int)$userId . '&oid=' . (int)$organizationId . ($currentHolonId > 0 ? '&cid=' . (int)$currentHolonId : '');
$rightsFragmentUrl = '/popup/user.php?section=rights&id=' . (int)$userId . '&oid=' . (int)$organizationId;
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
<div class="omo-user-context" data-user-competence-popup-url="<?= omoApiEscape($popupReloadUrl) ?>">
    <style>
    .omo-user-context {
        display: grid;
        gap: 0;
        color: var(--color-text, #1f2937);
    }

    .omo-user-context__header {
        position: sticky;
        top: 0;
        z-index: 3;
    }

    .omo-user-context__shell {
        display: grid;
        gap: 18px;
        padding: 16px 18px 18px;
    }

    .omo-user-context--error {
        padding: 18px;
        border-radius: 16px;
        background: var(--color-surface-alt, #f0f2f5);
        color: var(--color-text-light, #6b7280);
        border: 1px solid var(--color-border, #e5e7eb);
    }

    .omo-user-context__main {
        min-width: 0;
        display: grid;
        gap: 18px;
    }

    .omo-user-context__profile {
        --generic-hero-gap: 18px;
        --generic-hero-padding: 22px;
        --generic-hero-radius: 24px;
        --generic-hero-shadow: var(--shadow-md, 0 12px 24px rgba(0,0,0,0.12));
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: 18px;
        width: 100%;
    }

    .omo-user-context__photo-shell {
        width: 104px;
        height: 104px;
        padding: 4px;
        border-radius: 999px;
        background:
            linear-gradient(
                135deg,
                color-mix(in srgb, var(--color-primary, #2563eb) 58%, var(--color-surface, #ffffff)),
                color-mix(in srgb, var(--color-surface-alt, #f0f2f5) 84%, var(--color-primary, #2563eb) 20%)
            );
        box-shadow: 0 16px 34px color-mix(in srgb, var(--color-primary, #2563eb) 18%, transparent);
    }

    .omo-user-context__photo,
    .omo-user-context__photo-placeholder {
        width: 100%;
        height: 100%;
        border-radius: inherit;
        overflow: hidden;
        background: var(--color-surface, #ffffff);
        border: 2px solid color-mix(in srgb, var(--color-surface, #ffffff) 88%, transparent);
    }

    .omo-user-context__photo {
        object-fit: cover;
        display: block;
    }

    .omo-user-context__photo-placeholder {
        display: grid;
        place-items: center;
        font-size: 32px;
        font-weight: 700;
        color: var(--color-primary, #2563eb);
    }

    .omo-user-context__identity {
        display: grid;
        gap: 6px;
        min-width: 0;
    }

    .omo-user-context__identity h2 {
        margin: 0;
        font-size: 22px;
        line-height: 1.05;
    }

    .omo-user-context__secondary,
    .omo-user-context__supporting,
    .omo-user-context__presentation,
    .omo-user-context__birthday-detail,
    .omo-user-context__meta-value--muted,
    .omo-user-context__empty,
    .omo-user-context__role-path,
    .omo-user-context__summary-copy p,
    .omo-user-context__section-copy,
    .omo-user-context__competence-description,
    .omo-user-context__competence-more {
        color: var(--color-text-light, #6b7280);
    }

    .omo-user-context__secondary,
    .omo-user-context__meta-value {
        word-break: break-word;
    }

    .omo-user-context__presentation {
        margin: 0;
        line-height: 1.55;
        white-space: pre-line;
    }

    .omo-user-context__birthday-card {
        --generic-soft-panel-radius: 16px;
        --generic-soft-panel-padding-block: 12px;
        --generic-soft-panel-padding-inline: 14px;
        display: grid;
        gap: 3px;
        width: 100%;
        background:
            linear-gradient(
                135deg,
                color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface, #ffffff)),
                color-mix(in srgb, var(--color-surface-alt, #f0f2f5) 82%, var(--color-surface, #ffffff))
            );
    }

    .omo-user-context__birthday-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--color-text, #1f2937);
    }

    .omo-user-context__birthday-detail {
        font-size: 12px;
    }

    .omo-user-context__badges,
    .omo-user-context__competence-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .omo-user-context__badge,
    .omo-user-context__competence-badge {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        border: 1px solid var(--color-border, #e5e7eb);
        background: var(--color-surface, #ffffff);
        color: var(--color-text, #1f2937);
        font-size: 12px;
        font-weight: 700;
    }

    .omo-user-context__badge--admin,
    .omo-user-context__competence-badge:not(.omo-user-context__competence-badge--muted) {
        background: color-mix(in srgb, var(--color-primary, #2563eb) 12%, var(--color-surface, #ffffff));
        color: var(--color-primary, #2563eb);
        border-color: color-mix(in srgb, var(--color-primary, #2563eb) 26%, var(--color-border, #e5e7eb));
    }

    .omo-user-context__badge--pending,
    .omo-user-context__competence-badge--muted {
        background: var(--color-surface-alt, #f0f2f5);
        color: var(--color-text-light, #6b7280);
    }

    .omo-user-context__stats {
        --generic-section-gap: 14px;
        --generic-section-radius: 22px;
    }

    .omo-user-context__stats-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .omo-user-context__stat {
        --generic-soft-panel-radius: 16px;
        --generic-soft-panel-padding-block: 14px;
        --generic-soft-panel-padding-inline: 14px;
        display: grid;
        gap: 6px;
        min-width: 0;
    }

    .omo-user-context__stat-value {
        font-size: 28px;
        font-weight: 700;
        line-height: 1;
        color: var(--color-text, #1f2937);
    }

    .omo-user-context__stat-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--color-text-light, #6b7280);
    }

    .omo-user-context__meta-card,
    .omo-user-context__section {
        --generic-section-gap: 14px;
    }

    .omo-user-context__info-grid {
        display: grid;
        gap: 18px;
    }

    .omo-user-context__meta-list {
        display: grid;
        gap: 14px;
    }

    .omo-user-context__meta-item {
        display: grid;
        gap: 4px;
    }

    .omo-user-context__meta-label,
    .omo-user-context__section-kicker {
        margin: 0;
    }

    .omo-user-context__meta-value {
        line-height: 1.4;
        color: var(--color-text, #1f2937);
    }

    .omo-user-context__tabs {
        --generic-tabs-list-padding-inline: 14px;
        --generic-tabs-panel-padding-block: 18px;
        --generic-tabs-panel-padding-inline: 18px;
        --generic-tabs-panel-radius: 22px;
        --generic-tabs-panel-shadow: var(--shadow-sm, none);
    }

    .omo-user-context__pane-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
    }

    .omo-user-context__pane-copy {
        display: grid;
        gap: 6px;
    }

    .omo-user-context__summary {
        --generic-soft-panel-radius: 18px;
        --generic-soft-panel-padding-block: 16px;
        --generic-soft-panel-padding-inline: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
        background:
            linear-gradient(
                135deg,
                color-mix(in srgb, var(--color-primary, #2563eb) 7%, var(--color-surface-alt, #f0f2f5)),
                color-mix(in srgb, var(--color-surface, #ffffff) 92%, var(--color-primary, #2563eb) 4%)
            );
    }

    .omo-user-context__summary-copy {
        display: grid;
        gap: 4px;
    }

    .omo-user-context__summary-copy p {
        margin: 0;
        line-height: 1.45;
    }

    .omo-user-context__roles {
        display: grid;
        gap: 10px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .omo-user-context__role {
        --generic-soft-panel-border: var(--color-border, #e5e7eb);
        --generic-soft-panel-background: var(--color-surface-alt, #f0f2f5);
        --generic-soft-panel-radius: 16px;
        --generic-soft-panel-padding-block: 14px;
        --generic-soft-panel-padding-inline: 16px;
    }

    .omo-user-context__role-link {
        width: 100%;
        border: 0;
        text-align: left;
        cursor: pointer;
        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease,
            border-color 0.15s ease,
            background 0.15s ease;
    }

    .omo-user-context__role-link:hover {
        transform: translateY(-1px);
        border-color: color-mix(in srgb, var(--color-primary, #2563eb) 26%, var(--color-border, #e5e7eb));
        box-shadow: 0 12px 28px color-mix(in srgb, var(--color-primary, #2563eb) 10%, transparent);
    }

    .omo-user-context__role-link:focus-visible {
        outline: 2px solid color-mix(in srgb, var(--color-primary, #2563eb) 54%, #ffffff);
        outline-offset: 2px;
    }

    .omo-user-context__role-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    .omo-user-context__role-name {
        font-weight: 700;
        line-height: 1.35;
        color: var(--color-text, #1f2937);
    }

    .omo-user-context__role-path {
        margin-top: 5px;
        font-size: 13px;
        line-height: 1.45;
    }

    .omo-user-context__role-status {
        flex: 0 0 auto;
        padding: 5px 9px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface, #ffffff));
        color: var(--color-primary, #2563eb);
        font-size: 11px;
        font-weight: 700;
    }

    .omo-user-context__competence-labels {
        display: none;
        gap: 12px;
        padding: 0 14px;
        grid-template-columns: minmax(0, 1.9fr) minmax(220px, 1fr);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--color-text-light, #6b7280);
    }

    .omo-user-context__competences {
        display: grid;
        gap: 10px;
    }

    .omo-user-context__competence {
        --generic-soft-panel-radius: 18px;
        --generic-soft-panel-padding-block: 14px;
        --generic-soft-panel-padding-inline: 14px;
        position: relative;
        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease,
            border-color 0.15s ease;
    }

    .omo-user-context__competence:hover {
        transform: translateY(-1px);
        border-color: color-mix(in srgb, var(--color-primary, #2563eb) 26%, var(--color-border, #e5e7eb));
        box-shadow: 0 12px 28px color-mix(in srgb, var(--color-primary, #2563eb) 10%, transparent);
    }

    .omo-user-context__competence-grid {
        display: grid;
        gap: 14px;
        align-items: start;
    }

    .omo-user-context__competence-main {
        display: grid;
        gap: 6px;
        align-items: start;
        min-width: 0;
        padding-right: 40px;
    }

    .omo-user-context__competence-copy {
        display: grid;
        gap: 5px;
        min-width: 0;
    }

    .omo-user-context__competence-name {
        font-size: 15px;
        font-weight: 700;
        line-height: 1.25;
        color: var(--color-text, #1f2937);
    }

    .omo-user-context__competence-description {
        font-size: 13px;
        line-height: 1.45;
    }

    .omo-user-context__competence-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .omo-user-context__competence-side {
        display: grid;
        gap: 12px;
        min-width: 0;
        padding-right: 40px;
    }

    .omo-user-context__competence-level {
        display: grid;
        gap: 7px;
        min-width: 0;
    }

    .omo-user-context__competence-level-track {
        display: grid;
        gap: 6px;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        min-width: 120px;
    }

    .omo-user-context__competence-level-step {
        height: 10px;
        border-radius: 999px;
        background: var(--color-surface, #ffffff);
        border: 1px solid var(--color-border, #e5e7eb);
    }

    .omo-user-context__competence-level-step.is-active {
        background: linear-gradient(
            135deg,
            color-mix(in srgb, var(--color-primary, #2563eb) 78%, #ffffff),
            color-mix(in srgb, var(--color-primary, #2563eb) 44%, var(--color-surface-alt, #f0f2f5))
        );
        border-color: color-mix(in srgb, var(--color-primary, #2563eb) 38%, var(--color-border, #e5e7eb));
    }

    .omo-user-context__competence-level-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--color-text-light, #6b7280);
    }

    .omo-user-context__competence-validators {
        display: grid;
        gap: 7px;
        min-width: 0;
    }

    .omo-user-context__competence-validators-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--color-text, #1f2937);
    }

    .omo-user-context__competence-avatar-stack {
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        padding-left: 8px;
    }

    .omo-user-context__competence-avatar-stack > span:first-child {
        position: relative;
        z-index: 2;
        margin-right: 8px;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        color: var(--color-text, #1f2937);
        white-space: nowrap;
    }

    .omo-user-context__competence-avatar-stack > span:first-child + .omo-user-context__competence-avatar,
    .omo-user-context__competence-avatar-stack > span:first-child + .omo-user-context__competence-avatar--placeholder {
        margin-left: 0;
    }

    .omo-user-context__competence-avatar,
    .omo-user-context__competence-avatar--placeholder {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        border: 2px solid var(--color-surface, #ffffff);
        background: var(--color-surface-alt, #f0f2f5);
        margin-left: -8px;
        box-shadow: 0 3px 8px rgba(15, 23, 42, 0.08);
    }

    .omo-user-context__competence-avatar {
        object-fit: cover;
        display: block;
    }

    .omo-user-context__competence-avatar--placeholder {
        display: inline-grid;
        place-items: center;
        font-size: 10px;
        font-weight: 700;
        color: var(--color-primary, #2563eb);
    }

    .omo-user-context__competence-more {
        margin-left: 8px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .omo-user-context__competence-action {
        position: absolute;
        top: 12px;
        right: 12px;
        display: flex;
        justify-content: flex-end;
        z-index: 2;
    }

    .omo-user-context__competence-menu-toggle {
        width: 30px;
        min-width: 30px;
        min-height: 30px;
        height: 30px;
        padding: 0;
        border: 1px solid transparent;
        border-radius: 999px;
        background: transparent;
        color: var(--color-text-light, #6b7280);
        font-size: 18px;
        line-height: 1;
        box-shadow: none;
    }

    .omo-user-context__competence-menu-toggle:hover,
    .omo-user-context__competence-menu-toggle:focus-visible {
        background: var(--color-surface-alt, #f0f2f5);
        border-color: var(--color-border, #e5e7eb);
        color: var(--color-text, #1f2937);
    }

    .omo-user-context__competence-menu {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        width: min(260px, calc(100vw - 48px));
        padding: 12px;
        border: 1px solid var(--color-border, #e5e7eb);
        border-radius: 14px;
        background: var(--color-surface, #ffffff);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.14);
    }

    .omo-user-context__competence-menu[hidden] {
        display: none;
    }

    .omo-user-context__competence-menu-title {
        margin: 0 0 4px;
        font-size: 13px;
        font-weight: 700;
        color: var(--color-text, #1f2937);
    }

    .omo-user-context__competence-menu-copy {
        margin: 0 0 10px;
        font-size: 12px;
        line-height: 1.4;
        color: var(--color-text-light, #6b7280);
    }

    .omo-user-context__competence-form {
        display: grid;
        gap: 8px;
    }

    .omo-user-context__competence-form select {
        min-width: 0;
        min-height: 36px;
        padding: 6px 10px;
        font-size: 12px;
    }

    .omo-user-context__competence-form .generic-action-button {
        min-height: 36px;
        padding: 8px 12px;
        border-radius: 10px;
        font-size: 12px;
        line-height: 1.1;
    }

    .omo-user-context__competence-feedback {
        color: var(--color-text-light, #6b7280);
        font-size: 13px;
        line-height: 1.45;
    }

    .omo-user-context__competence-feedback.is-success {
        color: #15803d;
    }

    .omo-user-context__competence-feedback.is-error {
        color: #b91c1c;
    }

    .omo-user-context__rights-list {
        display: grid;
        gap: 12px;
    }

    .omo-user-context__rights-card {
        display: grid;
        gap: 12px;
    }

    .omo-user-context__rights-scope-list {
        display: grid;
        gap: 10px;
    }

    .omo-user-context__rights-scope-item {
        display: grid;
        gap: 6px;
        padding-top: 2px;
    }

    .omo-user-context__rights-pill {
        display: inline-flex;
        flex-direction: column;
        gap: 4px;
        min-width: min(100%, 360px);
        padding: 10px 12px;
        border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 16%, var(--color-border, #dbe4ee));
        border-radius: 14px;
        background: color-mix(in srgb, var(--color-primary, #2563eb) 6%, var(--color-surface, #ffffff));
    }

    .omo-user-context__rights-pill-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--color-text, #0f172a);
    }

    .omo-user-context__rights-pill-scope {
        font-size: 11px;
        line-height: 1.35;
        color: var(--color-text-light, #64748b);
    }

    .omo-user-context__rights-sources {
        display: grid;
        gap: 8px;
    }

    .omo-user-context__rights-sources-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--color-text-light, #64748b);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .omo-user-context__rights-source-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .omo-user-context__rights-source-chip {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        background: var(--color-surface-alt, #e2e8f0);
        color: var(--color-text, #334155);
        font-size: 12px;
        line-height: 1.35;
    }

    .omo-user-context__fragment-host {
        min-height: 72px;
        display: grid;
        gap: 14px;
    }

    .omo-user-context__fragment-feedback {
        color: var(--color-text-light, #64748b);
        line-height: 1.45;
    }

    .omo-user-context__fragment-feedback.is-error {
        color: #b91c1c;
    }

    @media (min-width: 920px) {
        .omo-user-context__info-grid {
            grid-template-columns: minmax(220px, 280px) minmax(0, 1fr);
        }

        .omo-user-context__competence-labels {
            display: grid;
        }

        .omo-user-context__competence-grid {
            grid-template-columns: minmax(0, 1.9fr) minmax(220px, 1fr);
        }
    }

    @media (max-width: 919px) {
        .omo-user-context__profile {
            grid-template-columns: minmax(0, 1fr);
            justify-items: center;
            text-align: center;
        }

        .omo-user-context__badges {
            justify-content: center;
        }

        .omo-user-context__competence-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .omo-user-context__competence-side {
            gap: 10px;
        }

        .omo-user-context__competence-validators,
        .omo-user-context__competence-action {
            justify-items: start;
        }
    }

    @media (max-width: 640px) {
        .omo-user-context__stats-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .omo-user-context__tabs {
            --generic-tabs-list-padding-inline: 0;
            --generic-tabs-panel-padding-block: 14px;
            --generic-tabs-panel-padding-inline: 14px;
        }

        .omo-user-context__summary {
            padding: 14px;
        }

        .omo-user-context__competence {
            --generic-soft-panel-padding-block: 12px;
            --generic-soft-panel-padding-inline: 12px;
        }

        .omo-user-context__competence-main {
            padding-right: 0;
        }

        .omo-user-context__competence-side {
            padding-right: 0;
        }

        .omo-user-context__competence-action {
            position: static;
            justify-content: flex-start;
        }

        .omo-user-context__competence-level-track {
            min-width: 0;
        }

        .omo-user-context__competence-menu {
            position: static;
            width: 100%;
            margin-top: 8px;
        }
    }
    </style>

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
                    <button
                        type="button"
                        class="generic-tabs__tab"
                        data-generic-tab
                        data-generic-tab-target="omo-user-context-panel-rights"
                        data-user-fragment-panel="omo-user-context-panel-rights"
                    >Droits</button>
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
                                            <li>
                                                <button
                                                    type="button"
                                                    class="omo-user-context__role omo-user-context__role-link generic-soft-panel"
                                                    data-user-role-cid="<?= (int)$assignment['holonId'] ?>"
                                                    aria-label="<?= omoApiEscape('Ouvrir le role ' . ($assignment['name'] ?: ('Role ' . (int)$assignment['holonId']))) ?>"
                                                >
                                                    <div class="omo-user-context__role-head">
                                                        <div>
                                                            <div class="omo-user-context__role-name"><?= omoApiEscape($assignment['name'] ?: ('Role ' . (int)$assignment['holonId'])) ?></div>
                                                            <?php if ((string)$assignment['pathLabel'] !== ''): ?>
                                                                <div class="omo-user-context__role-path"><?= omoApiEscape($assignment['pathLabel']) ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($assignment['isPending'])): ?>
                                                            <span class="omo-user-context__role-status">En attente</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </button>
                                            </li>
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
                                            <li>
                                                <button
                                                    type="button"
                                                    class="omo-user-context__role omo-user-context__role-link generic-soft-panel"
                                                    data-user-role-cid="<?= (int)$assignment['holonId'] ?>"
                                                    aria-label="<?= omoApiEscape('Ouvrir le role ' . ($assignment['name'] ?: ('Role ' . (int)$assignment['holonId']))) ?>"
                                                >
                                                    <div class="omo-user-context__role-head">
                                                        <div>
                                                            <div class="omo-user-context__role-name"><?= omoApiEscape($assignment['name'] ?: ('Role ' . (int)$assignment['holonId'])) ?></div>
                                                            <?php if ((string)$assignment['pathLabel'] !== ''): ?>
                                                                <div class="omo-user-context__role-path"><?= omoApiEscape($assignment['pathLabel']) ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($assignment['isPending'])): ?>
                                                            <span class="omo-user-context__role-status">En attente</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </button>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </section>
                        </div>
                    <?php endif; ?>

                    <div id="omo-user-context-panel-rights" class="generic-tabs__panel" data-generic-tab-panel hidden>
                        <div
                            class="omo-user-context__fragment-host"
                            data-user-fragment-host="1"
                            data-user-fragment-url="<?= omoApiEscape($rightsFragmentUrl) ?>"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var root = document.querySelector('.omo-user-context');
    var modalBody = document.getElementById('commonTopbarModalBody');
    var fragmentLoadingMessage = 'Chargement...';
    var fragmentErrorMessage = 'Impossible de charger cet onglet pour le moment.';

    if (!root) {
        return;
    }

    if (modalBody) {
        modalBody.setAttribute('data-omo-popup-live-sync', '1');
    }

    function loadFragmentHost(host) {
        var fragmentUrl;

        if (!host) {
            return;
        }

        fragmentUrl = String(host.getAttribute('data-user-fragment-url') || '').trim();
        if (fragmentUrl === '') {
            return;
        }

        if (host.getAttribute('data-user-fragment-loaded') === '1') {
            return;
        }

        host.innerHTML = '<div class="omo-user-context__fragment-feedback">' + fragmentLoadingMessage + '</div>';
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
                host.innerHTML = html;
                host.setAttribute('data-user-fragment-loaded', '1');
            })
            .catch(function () {
                host.innerHTML = '<div class="omo-user-context__fragment-feedback is-error">' + fragmentErrorMessage + '</div>';
            });
    }

    root.querySelectorAll('[data-user-fragment-panel]').forEach(function (button) {
        button.addEventListener('click', function () {
            var panelId = String(button.getAttribute('data-user-fragment-panel') || '').trim();
            var panel = panelId !== '' ? document.getElementById(panelId) : null;
            var host = panel ? panel.querySelector('[data-user-fragment-host="1"]') : null;
            loadFragmentHost(host);
        });
    });

    root.querySelectorAll('[data-user-role-cid]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var cid = Number(button.getAttribute('data-user-role-cid') || '0');
            if (!Number.isInteger(cid) || cid <= 0) {
                return;
            }

            if (typeof navigate !== 'function' || typeof parseUrl !== 'function') {
                return;
            }

            var route = parseUrl();
            navigate(route.oid, cid, route.hash || null);

            if (typeof omoFocusStructureNode === 'function') {
                window.setTimeout(function () {
                    omoFocusStructureNode(cid, { quickZoom: true });
                }, 140);
            }
        });
    });
})();
</script>
<?php if ($canValidateCompetences && count($competenceRows) > 0): ?>
<script>
(function () {
    var root = document.querySelector('.omo-user-context[data-user-competence-popup-url]');
    if (!root) {
        return;
    }

    var feedback = root.querySelector('[data-user-competence-feedback="1"]');
    var openMenu = null;

    function setFeedback(message, type) {
        if (!feedback) {
            return;
        }

        feedback.textContent = message || '';
        feedback.className = 'omo-user-context__competence-feedback';
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
                    message: 'Reponse serveur invalide.'
                };
            }
        });
    }

    function reloadPopup() {
        var popupUrl = root.getAttribute('data-user-competence-popup-url') || '';
        if (popupUrl === '') {
            return;
        }

        if (window.jQuery && document.getElementById('popup_content')) {
            window.jQuery('#popup_content').load(popupUrl);
            return;
        }

        if (window.commonTopbarRefreshModalContent) {
            window.commonTopbarRefreshModalContent(popupUrl);
        }
    }

    function closeMenu(menu) {
        if (!menu) {
            return;
        }

        menu.hidden = true;
        var toggle = menu.parentNode ? menu.parentNode.querySelector('[data-user-competence-menu-toggle="1"]') : null;
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
        if (openMenu === menu) {
            openMenu = null;
        }
    }

    function openMenuFor(toggle) {
        var action = toggle.parentNode;
        var menu = action ? action.querySelector('[data-user-competence-menu="1"]') : null;
        if (!menu) {
            return;
        }

        if (openMenu && openMenu !== menu) {
            closeMenu(openMenu);
        }

        menu.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        openMenu = menu;
    }

    root.querySelectorAll('[data-user-competence-menu-toggle="1"]').forEach(function (toggle) {
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            var action = toggle.parentNode;
            var menu = action ? action.querySelector('[data-user-competence-menu="1"]') : null;
            if (!menu) {
                return;
            }

            if (!menu.hidden) {
                closeMenu(menu);
                return;
            }

            openMenuFor(toggle);
        });
    });

    document.addEventListener('click', function (event) {
        if (!openMenu) {
            return;
        }

        if (openMenu.contains(event.target)) {
            return;
        }

        var toggle = openMenu.parentNode ? openMenu.parentNode.querySelector('[data-user-competence-menu-toggle="1"]') : null;
        if (toggle && toggle.contains(event.target)) {
            return;
        }

        closeMenu(openMenu);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && openMenu) {
            closeMenu(openMenu);
        }
    });

    root.querySelectorAll('[data-user-competence-validate-form="1"]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (typeof window.omoBeginPendingAction === 'function' && !window.omoBeginPendingAction(form)) {
                return;
            }

            setFeedback('', '');

            fetch('/omo/api/user_competence_validate.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(parseResponse)
                .then(function (result) {
                    if (!result || !result.status) {
                        setFeedback(result && result.message ? result.message : "Impossible d'enregistrer cette validation.", 'error');
                        return;
                    }

                    setFeedback(result.message || 'Validation enregistree.', 'success');
                    closeMenu(form.closest('[data-user-competence-menu="1"]'));
                    reloadPopup();
                })
                .catch(function () {
                    setFeedback("Impossible d'enregistrer cette validation.", 'error');
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
<?php endif; ?>
