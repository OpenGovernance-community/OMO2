<?php
require_once dirname(__DIR__) . '/omo/api/bootstrap.php';

use dbObject\Holon;
use dbObject\Organization;
use dbObject\User;

function omoMemberActionsHolonTypeLabel(Holon $holon)
{
    switch ((int)$holon->get('IDtypeholon')) {
        case 4:
            return 'organisation';
        case 3:
            return 'groupe';
        case 2:
            return 'cercle';
        case 1:
            return 'rôle';
        default:
            return 'contexte';
    }
}

$organizationId = (int)($_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$userId = (int)($_GET['id'] ?? 0);
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;

if ($organizationId <= 0 || $userId <= 0) {
    http_response_code(400);
    ?>
    <div class="omo-member-actions omo-member-actions--error">Contexte membre invalide.</div>
    <?php
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-member-actions omo-member-actions--error">Organisation introuvable.</div>
    <?php
    exit;
}

if (!$organization->canViewDetail()) {
    http_response_code(403);
    ?>
    <div class="omo-member-actions omo-member-actions--error">Acces refuse a cette organisation.</div>
    <?php
    exit;
}

$rootHolon = $organization->getStructuralRootHolon();
if ($rootHolon === null) {
    http_response_code(404);
    ?>
    <div class="omo-member-actions omo-member-actions--error">Aucun contexte organisationnel n'est disponible.</div>
    <?php
    exit;
}

$currentHolon = $rootHolon;
if ($currentHolonId > 0 && (int)$rootHolon->getId() !== $currentHolonId) {
    $candidate = new Holon();
    if (!$candidate->load($currentHolonId) || !$candidate->isDescendantOf($rootHolon->getId())) {
        http_response_code(404);
        ?>
        <div class="omo-member-actions omo-member-actions--error">Contexte introuvable pour cette organisation.</div>
        <?php
        exit;
    }

    if (!$candidate->canViewDetail()) {
        http_response_code(403);
        ?>
        <div class="omo-member-actions omo-member-actions--error">Acces refuse a ce contexte.</div>
        <?php
        exit;
    }

    $currentHolon = $candidate;
}

$user = new User();
if (!$user->load($userId)) {
    http_response_code(404);
    ?>
    <div class="omo-member-actions omo-member-actions--error">Utilisateur introuvable.</div>
    <?php
    exit;
}

if (!$user->canViewDetail()) {
    http_response_code(403);
    ?>
    <div class="omo-member-actions omo-member-actions--error">Acces refuse a cet utilisateur.</div>
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
    $currentHolonTemplateLabel = omoMemberActionsHolonTypeLabel($currentHolon);
}

$currentHolonName = trim((string)$currentHolon->getDisplayName());
$canManageCurrentHolonMembers = $currentHolon->canEdit();
?>
<div
    class="omo-member-actions"
    id="omoMemberActionsPopup"
    data-user-id="<?= (int)$userId ?>"
    data-oid="<?= (int)$organizationId ?>"
    data-hid="<?= (int)$currentHolon->getId() ?>"
    data-root-hid="<?= (int)$rootHolon->getId() ?>"
    data-context-label="<?= htmlspecialchars((string)$currentHolonTemplateLabel, ENT_QUOTES, 'UTF-8') ?>"
    data-display-name="<?= htmlspecialchars((string)($displayName !== '' ? $displayName : ('Utilisateur ' . $userId)), ENT_QUOTES, 'UTF-8') ?>"
>
    <style>
    .omo-member-actions {
        display: grid;
        gap: 16px;
        color: var(--color-text, #1f2937);
    }

    .omo-member-actions--error {
        padding: 18px;
        border-radius: 16px;
        background: var(--color-surface-alt, #f0f2f5);
        color: var(--color-text-light, #6b7280);
        border: 1px solid var(--color-border, #e5e7eb);
    }

    .omo-member-actions__hero {
        --generic-hero-gap: 8px;
        --generic-hero-padding: 18px;
        --generic-hero-radius: 20px;
        --generic-hero-shadow: var(--shadow-md, 0 12px 24px rgba(0,0,0,0.12));
    }

    .omo-member-actions__hero h2 {
        margin: 0;
    }

    .omo-member-actions__secondary {
        color: var(--color-text-light, #6b7280);
        word-break: break-word;
    }

    .omo-member-actions__badge-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .omo-member-actions__badge {
        display: inline-flex;
        align-items: center;
        min-height: 26px;
        padding: 0 10px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-primary, #2563eb) 14%, var(--color-surface, #ffffff));
        color: var(--color-primary, #2563eb);
        font-size: 0.76rem;
        font-weight: 700;
        border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 30%, var(--color-border, #e5e7eb));
    }

    .omo-member-actions__badge--pending {
        background: rgba(100, 116, 139, 0.14);
        color: var(--color-text-light, #6b7280);
        border-color: var(--color-border, #e5e7eb);
    }

    .omo-member-actions__section {
        --generic-section-gap: 10px;
        --generic-section-padding-block: 18px;
        --generic-section-padding-inline: 18px;
        --generic-section-radius: 20px;
    }

    .omo-member-actions__section h3 {
        margin: 0;
        font-size: 1rem;
    }

    .omo-member-actions__section p {
        margin: 0;
        color: var(--color-text-light, #6b7280);
        line-height: 1.45;
    }

    .omo-member-actions__actions {
        display: grid;
        gap: 10px;
    }

    .omo-member-actions__button {
        width: 100%;
        justify-content: flex-start;
        text-align: left;
        font-size: 0.95rem;
        white-space: normal;
    }

    .omo-member-actions__feedback {
        min-height: 22px;
        color: var(--color-text-light, #6b7280);
        font-size: 0.92rem;
        line-height: 1.4;
    }

    .omo-member-actions__feedback.is-error {
        color: #b91c1c;
    }

    .omo-member-actions__feedback.is-success {
        color: #166534;
    }
    </style>

    <div class="omo-member-actions__hero generic-hero-panel">
        <div class="omo-member-actions__eyebrow generic-card-title generic-card-title--eyebrow">Actions contextuelles</div>
        <h2 class="generic-card-title generic-card-title--large"><?= htmlspecialchars((string)($displayName !== '' ? $displayName : ('Utilisateur ' . $userId))) ?></h2>
        <?php if ($secondaryLabel !== ''): ?>
            <div class="omo-member-actions__secondary"><?= htmlspecialchars($secondaryLabel) ?></div>
        <?php endif; ?>
        <div class="omo-member-actions__secondary">
            Contexte: <?= htmlspecialchars($currentHolonTemplateLabel) ?>
            <?php if ($currentHolonName !== ''): ?>
                · <?= htmlspecialchars($currentHolonName) ?>
            <?php endif; ?>
        </div>
        <div class="omo-member-actions__badge-row">
            <?php if ($isPending): ?>
                <span class="omo-member-actions__badge omo-member-actions__badge--pending">En attente</span>
            <?php endif; ?>
            <?php if ($isContextAdmin): ?>
                <span class="omo-member-actions__badge">Admin du contexte</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="omo-member-actions__section generic-section generic-section--stack">
        <h3 class="generic-card-title generic-card-title--medium">Gestion du membre</h3>
        <?php if (!$canManageCurrentHolonMembers): ?>
            <p>Vous n’avez pas les droits pour modifier ce <?= htmlspecialchars($currentHolonTemplateLabel) ?>.</p>
        <?php else: ?>
            <p>Choisissez l’action à appliquer dans ce <?= htmlspecialchars($currentHolonTemplateLabel) ?>.</p>
            <div class="omo-member-actions__actions">
                <button
                    type="button"
                    class="omo-member-actions__button generic-action-button generic-action-button--danger"
                    data-member-popup-action="remove"
                >Retirer du contexte <?= htmlspecialchars($currentHolonTemplateLabel) ?></button>
                <?php if (!$isPending): ?>
                    <button
                        type="button"
                        class="omo-member-actions__button generic-action-button generic-action-button--secondary"
                        data-member-popup-action="<?= $isContextAdmin ? 'revoke_admin' : 'grant_admin' ?>"
                    ><?= htmlspecialchars($isContextAdmin ? 'Retirer le statut admin du contexte ' : 'Définir comme admin du contexte ') ?><?= htmlspecialchars($currentHolonTemplateLabel) ?></button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="omo-member-actions__feedback" id="omoMemberActionsFeedback"></div>
</div>

<script>
(function () {
    const root = document.getElementById('omoMemberActionsPopup');
    const feedback = document.getElementById('omoMemberActionsFeedback');

    if (!root || !feedback) {
        return;
    }

    const userId = Number(root.getAttribute('data-user-id') || 0);
    const organizationId = Number(root.getAttribute('data-oid') || 0);
    const currentHolonId = Number(root.getAttribute('data-hid') || 0);
    const rootHolonId = Number(root.getAttribute('data-root-hid') || 0);
    const contextLabel = String(root.getAttribute('data-context-label') || '').trim() || 'contexte';
    const displayName = String(root.getAttribute('data-display-name') || '').trim() || 'ce membre';

    function setFeedback(message, isError) {
        feedback.textContent = message || '';
        feedback.classList.toggle('is-error', !!isError);
        feedback.classList.toggle('is-success', !isError && !!message);
    }

    function refreshContext() {
        if (typeof refreshDrawer === 'function') {
            let drawerUrl = '/omo/api/team/index.php?oid=' + organizationId;
            if (currentHolonId > 0 && currentHolonId !== rootHolonId) {
                drawerUrl += '&cid=' + currentHolonId;
            }
            refreshDrawer('drawer_team', drawerUrl);
        }

        if (typeof loadContent === 'function') {
            let leftUrl = 'api/getOrg.php?oid=' + organizationId;
            if (currentHolonId > 0 && currentHolonId !== rootHolonId) {
                leftUrl += '&cid=' + currentHolonId;
            }
            loadContent('#panel-left', leftUrl);
        }

        if (typeof window.omoReloadStructureAndFocus === 'function') {
            window.omoReloadStructureAndFocus(currentHolonId > 0 && currentHolonId !== rootHolonId ? currentHolonId : null, {
                quickZoom: true
            });
        }
    }

    root.querySelectorAll('[data-member-popup-action]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();

            const action = String(button.getAttribute('data-member-popup-action') || '').trim();
            let confirmationMessage = '';

            if (!action || !userId || !organizationId || !currentHolonId) {
                return;
            }

            if (action === 'remove') {
                confirmationMessage = 'Retirer ' + displayName + ' du contexte ' + contextLabel + ' ?';
            } else if (action === 'grant_admin') {
                confirmationMessage = 'Définir ' + displayName + ' comme admin du contexte ' + contextLabel + ' ?';
            } else if (action === 'revoke_admin') {
                confirmationMessage = 'Retirer le statut admin de ' + displayName + ' pour le contexte ' + contextLabel + ' ?';
            } else {
                return;
            }

            if (!window.confirm(confirmationMessage)) {
                return;
            }

            setFeedback('', false);
            button.disabled = true;

            const formData = new FormData();
            formData.append('hid', String(currentHolonId));
            formData.append('oid', String(organizationId));
            formData.append('user_id', String(userId));
            formData.append('action', action);

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
                    button.disabled = false;

                    if (!result.ok || !result.data || !result.data.status) {
                        setFeedback(result.data && result.data.message ? result.data.message : 'Impossible de mettre à jour ce membre.', true);
                        return;
                    }

                    setFeedback(result.data.message || 'Mise à jour effectuée.', false);
                    refreshContext();

                    window.setTimeout(function () {
                        if (typeof window.omoSetPopupHashState === 'function') {
                            window.omoSetPopupHashState({ open: false });
                        } else if (typeof window.commonTopbarCloseModal === 'function') {
                            window.commonTopbarCloseModal();
                        }
                    }, 220);
                })
                .catch(function () {
                    button.disabled = false;
                    setFeedback('Impossible de mettre à jour ce membre pour le moment.', true);
                });
        });
    });
})();
</script>
