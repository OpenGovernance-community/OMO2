<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/modules/context.php';
require_once __DIR__ . '/modules/common.php';

use dbObject\ArrayUserOrganization;
use dbObject\DbObject;
use dbObject\DecisionInvitation;
use dbObject\DecisionProcess;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\UserOrganization;

function omoDecisionInvitationsPopupParseEmails($value)
{
    $rawItems = is_array($value)
        ? $value
        : preg_split('/[\r\n,;]+/', (string)$value);
    $rawItems = is_array($rawItems) ? $rawItems : [];

    $emails = [];
    foreach ($rawItems as $item) {
        $email = trim(mb_strtolower((string)$item, 'UTF-8'));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }
        if (!in_array($email, $emails, true)) {
            $emails[] = $email;
        }
    }

    return $emails;
}

function omoDecisionInvitationsBuildHolonTreeData(Holon $holon, Organization $organization, array $selectedHolonIds, $currentHolonId)
{
    if (!$organization->containsHolon($holon) || !$holon->canViewDetail()) {
        return null;
    }

    $holonId = (int)$holon->getId();
    $children = [];
    $hasSelectedDescendant = in_array($holonId, $selectedHolonIds, true);
    $hasCurrentDescendant = $holonId === (int)$currentHolonId;

    foreach ($holon->getChildren() as $child) {
        if (!$child instanceof Holon) {
            continue;
        }

        $childNode = omoDecisionInvitationsBuildHolonTreeData($child, $organization, $selectedHolonIds, $currentHolonId);
        if (!is_array($childNode)) {
            continue;
        }

        $children[] = $childNode;
        if (!empty($childNode['hasSelectedDescendant'])) {
            $hasSelectedDescendant = true;
        }
        if (!empty($childNode['hasCurrentDescendant'])) {
            $hasCurrentDescendant = true;
        }
    }

    return [
        'id' => $holonId,
        'label' => trim((string)$holon->getDisplayName()),
        'typeLabel' => trim((string)$holon->getTemplateLabel(true)),
        'isCurrent' => $holonId === (int)$currentHolonId,
        'isSelected' => in_array($holonId, $selectedHolonIds, true),
        'children' => $children,
        'hasChildren' => count($children) > 0,
        'hasSelectedDescendant' => $hasSelectedDescendant,
        'hasCurrentDescendant' => $hasCurrentDescendant,
        'isExpanded' => $holonId === (int)$currentHolonId || $hasSelectedDescendant || $hasCurrentDescendant,
    ];
}

function omoDecisionInvitationsRenderHolonTreeNode(array $node)
{
    $hasChildren = !empty($node['hasChildren']);
    $isExpanded = !empty($node['isExpanded']);
    ?>
    <div class="omo-decision-invitations-popup__tree-node<?= $hasChildren ? ' has-children' : '' ?>" data-omo-decision-holon-node>
        <div class="omo-decision-invitations-popup__tree-row">
            <?php if ($hasChildren): ?>
            <button
                type="button"
                class="omo-decision-invitations-popup__tree-toggle"
                data-omo-decision-holon-toggle
                aria-expanded="<?= $isExpanded ? 'true' : 'false' ?>"
            >
                <span aria-hidden="true">&#9662;</span>
            </button>
            <?php else: ?>
            <span class="omo-decision-invitations-popup__tree-spacer" aria-hidden="true"></span>
            <?php endif; ?>

            <label class="omo-decision-invitations-popup__check">
                <input type="checkbox" name="holon_ids[]" value="<?= (int)$node['id'] ?>"<?= !empty($node['isSelected']) ? ' checked' : '' ?>>
                <span class="omo-decision-invitations-popup__check-meta">
                    <strong><?= omoApiEscape((string)$node['label']) ?><?= !empty($node['isCurrent']) ? ' (courant)' : '' ?></strong>
                    <span class="omo-decision-invitations-popup__check-type"><?= omoApiEscape((string)$node['typeLabel']) ?></span>
                </span>
            </label>
        </div>

        <?php if ($hasChildren): ?>
        <div class="omo-decision-invitations-popup__tree-children" data-omo-decision-holon-children<?= $isExpanded ? '' : ' hidden' ?>>
            <?php foreach ($node['children'] as $childNode): ?>
                <?php omoDecisionInvitationsRenderHolonTreeNode($childNode); ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$context = omoDecisionResolveEditorContext($input);

if (empty($context['status']) || empty($context['decision']) || !($context['decision'] instanceof DecisionProcess) || empty($context['canManage'])) {
    $statusCode = (int)($context['code'] ?? 403);
    http_response_code($statusCode);
    ?>
    <div class="omo-decision-invitations-popup__empty">Vous ne pouvez pas gerer les invitations de ce scrutin.</div>
    <?php
    exit;
}

$decision = $context['decision'];
$organization = $context['organization'];
$effectiveHolon = $context['effectiveHolon'];
$organizationId = (int)$context['organizationId'];
$targetHolonId = (int)$context['targetHolonId'];
$method = DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');

    $selectedHolonIds = array_values(array_unique(array_filter(array_map('intval', $_POST['holon_ids'] ?? []), static function ($holonId) {
        return $holonId > 0;
    })));
    $selectedUserIds = array_values(array_unique(array_filter(array_map('intval', $_POST['user_ids'] ?? []), static function ($userId) {
        return $userId > 0;
    })));
    $selectedEmails = omoDecisionInvitationsPopupParseEmails($_POST['emails'] ?? '');

    $validHolonLabels = [];
    foreach ($selectedHolonIds as $holonId) {
        $holon = new Holon();
        if (!$holon->load($holonId) || !$organization->containsHolon($holon) || !$holon->canViewDetail()) {
            omoDecisionModuleJsonResponse(422, [
                'status' => false,
                'message' => 'Un holon selectionne est invalide.',
            ]);
        }

        $validHolonLabels[$holonId] = trim((string)$holon->getDisplayName());
    }

    $validUserLabels = [];
    foreach ($selectedUserIds as $userId) {
        $membership = new UserOrganization();
        if (!$membership->load([
            ['IDorganization', $organizationId],
            ['IDuser', $userId],
        ]) || !(bool)$membership->get('active')) {
            omoDecisionModuleJsonResponse(422, [
                'status' => false,
                'message' => 'Un membre selectionne est invalide.',
            ]);
        }

        $validUserLabels[$userId] = trim((string)$membership->getUserDisplayName());
    }

    $existingInvitations = [];
    foreach ($decision->getInvitations(false) as $invitation) {
        if ($invitation instanceof DecisionInvitation) {
            $existingInvitations[$invitation->getIdentityKey()] = $invitation;
        }
    }

    $desiredInvitations = [];
    foreach ($selectedHolonIds as $holonId) {
        $desiredInvitations['holon:' . $holonId] = [
            'invitation_type' => DecisionInvitation::TYPE_HOLON,
            'IDholon' => $holonId,
            'display_name' => $validHolonLabels[$holonId] ?? '',
        ];
    }
    foreach ($selectedUserIds as $userId) {
        $desiredInvitations['user:' . $userId] = [
            'invitation_type' => DecisionInvitation::TYPE_USER,
            'IDuser' => $userId,
            'display_name' => $validUserLabels[$userId] ?? '',
        ];
    }
    foreach ($selectedEmails as $email) {
        $desiredInvitations['email:' . $email] = [
            'invitation_type' => DecisionInvitation::TYPE_EMAIL,
            'email' => $email,
            'display_name' => $email,
        ];
    }

    $pdo = DbObject::getPdo();
    if (!$pdo) {
        omoDecisionModuleJsonResponse(500, [
            'status' => false,
            'message' => 'Connexion a la base impossible.',
        ]);
    }

    try {
        $pdo->beginTransaction();

        foreach ($desiredInvitations as $identityKey => $invitationData) {
            $invitation = $existingInvitations[$identityKey] ?? new DecisionInvitation();
            $invitation->set('IDdecision_process', (int)$decision->getId());
            $invitation->set('invitation_type', $invitationData['invitation_type']);
            $invitation->set('IDholon', $invitationData['IDholon'] ?? null);
            $invitation->set('IDuser', $invitationData['IDuser'] ?? null);
            $invitation->set('email', $invitationData['email'] ?? null);
            $invitation->set('display_name', $invitationData['display_name'] ?? null);
            $invitation->set('status', DecisionInvitation::STATUS_INVITED);
            $invitation->set('active', 1);
            $invitation->set('parameters', [
                'updated_from_popup' => 1,
            ]);

            $saveResult = $invitation->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                throw new RuntimeException('invitation_save_failed');
            }
        }

        foreach ($existingInvitations as $identityKey => $invitation) {
            if (isset($desiredInvitations[$identityKey])) {
                continue;
            }

            $invitation->set('active', 0);
            $invitation->set('status', DecisionInvitation::STATUS_REVOKED);
            $saveResult = $invitation->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                throw new RuntimeException('invitation_revoke_failed');
            }
        }

        $syncResult = $decision->syncParticipantsFromInvitations();
        if (!is_array($syncResult) || empty($syncResult['status'])) {
            throw new RuntimeException('participant_sync_failed');
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        omoDecisionModuleJsonResponse(500, [
            'status' => false,
            'message' => 'Impossible d enregistrer les invitations pour le moment.',
        ]);
    }

    omoDecisionModuleJsonResponse(200, [
        'status' => true,
        'message' => 'Invitations mises a jour.',
        'redirectUrl' => omoDecisionBuildEditorUrl($organizationId, $targetHolonId, (int)$decision->getId(), $method, 'manage'),
        'drawerTitle' => 'Prises de decision',
    ]);
}

$selectedHolonIds = [];
$selectedUserIds = [];
$selectedEmails = [];
foreach ($decision->getInvitations(true) as $invitation) {
    if (!$invitation instanceof DecisionInvitation || DecisionInvitation::normalizeStatus($invitation->get('status')) === DecisionInvitation::STATUS_REVOKED) {
        continue;
    }

    $type = DecisionInvitation::normalizeType($invitation->get('invitation_type'));
    if ($type === DecisionInvitation::TYPE_HOLON) {
        $selectedHolonIds[] = (int)$invitation->get('IDholon');
    } elseif ($type === DecisionInvitation::TYPE_USER) {
        $selectedUserIds[] = (int)$invitation->get('IDuser');
    } else {
        $email = trim((string)$invitation->get('email'));
        if ($email !== '') {
            $selectedEmails[] = $email;
        }
    }
}

$selectedHolonIds = array_values(array_unique(array_filter($selectedHolonIds)));
$selectedUserIds = array_values(array_unique(array_filter($selectedUserIds)));
$selectedEmails = array_values(array_unique(array_filter($selectedEmails)));

$rootHolon = $organization instanceof Organization ? $organization->getStructuralRootHolon() : null;
$holonTree = $rootHolon instanceof Holon
    ? omoDecisionInvitationsBuildHolonTreeData($rootHolon, $organization, $selectedHolonIds, $targetHolonId)
    : null;

$memberships = new ArrayUserOrganization();
$memberships->loadActiveForOrganization($organizationId);
?>
<style>
.omo-decision-invitations-popup {
    display: grid;
    gap: 16px;
    padding: 8px 4px 4px;
    color: var(--color-text, #1f2937);
}

.omo-decision-invitations-popup__intro,
.omo-decision-invitations-popup__hint {
    margin: 0;
    color: var(--topbar-panel-muted, #64748b);
    line-height: 1.5;
}

.omo-decision-invitations-popup__group {
    display: grid;
    gap: 10px;
}

.omo-decision-invitations-popup__tabs {
    --generic-tabs-panel-padding-block: 14px;
    --generic-tabs-panel-padding-inline: 14px;
}

.omo-decision-invitations-popup__tab-panel {
    display: grid;
    gap: 10px;
}

.omo-decision-invitations-popup__checklist {
    display: grid;
    gap: 8px;
    max-height: 360px;
    overflow: auto;
    padding-right: 4px;
}

.omo-decision-invitations-popup__tree-node {
    display: grid;
    gap: 6px;
}

.omo-decision-invitations-popup__tree-row {
    display: flex;
    gap: 8px;
    align-items: flex-start;
}

.omo-decision-invitations-popup__tree-toggle,
.omo-decision-invitations-popup__tree-spacer {
    width: 28px;
    min-width: 28px;
    height: 28px;
    margin-top: 2px;
}

.omo-decision-invitations-popup__tree-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.12);
    color: inherit;
    cursor: pointer;
}

.omo-decision-invitations-popup__tree-toggle span {
    display: inline-block;
    transition: transform 0.18s ease;
}

.omo-decision-invitations-popup__tree-toggle[aria-expanded="false"] span {
    transform: rotate(-90deg);
}

.omo-decision-invitations-popup__tree-spacer {
    display: inline-block;
}

.omo-decision-invitations-popup__tree-children {
    display: grid;
    gap: 8px;
    margin-left: 18px;
    padding-left: 14px;
    border-left: 1px solid var(--topbar-panel-border, #dbe3ef);
}

.omo-decision-invitations-popup__tree-children[hidden] {
    display: none !important;
}

.omo-decision-invitations-popup__check {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    flex: 1 1 auto;
}

.omo-decision-invitations-popup__check-meta {
    display: grid;
    gap: 2px;
}

.omo-decision-invitations-popup__check-type {
    color: var(--topbar-panel-muted, #64748b);
    font-size: 0.9rem;
}

.omo-decision-invitations-popup__member-list {
    display: grid;
    gap: 8px;
    max-height: 260px;
    overflow: auto;
    padding-right: 4px;
}

.omo-decision-invitations-popup__member-email {
    color: var(--topbar-panel-muted, #64748b);
    font-size: 0.9rem;
}

.omo-decision-invitations-popup__select,
.omo-decision-invitations-popup__textarea {
    --generic-form-control-border: var(--topbar-panel-border, #dbe3ef);
    --generic-form-control-background: var(--topbar-panel-bg, #ffffff);
    --generic-form-control-background-focus: var(--topbar-panel-bg, #ffffff);
    --generic-form-control-color: inherit;
}

.omo-decision-invitations-popup__textarea {
    min-height: 120px;
}

.omo-decision-invitations-popup__actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.omo-decision-invitations-popup__feedback {
    min-height: 22px;
    color: #b91c1c;
    font-weight: 600;
}

.omo-decision-invitations-popup__feedback.is-success {
    color: #15803d;
}
</style>

<form
    id="omoDecisionInvitationsPopupForm"
    class="omo-decision-invitations-popup"
    action="/omo/api/decision/invitations_popup.php?oid=<?= (int)$organizationId ?>&cid=<?= (int)$targetHolonId ?>&id=<?= (int)$decision->getId() ?>&method=<?= urlencode($method) ?>"
    method="post"
>
    <p class="omo-decision-invitations-popup__intro">
        Definissez ici les participants explicites du scrutin. Si vous laissez tout vide, seuls les membres du contexte courant restent autorises.
    </p>

    <div class="generic-tabs omo-decision-invitations-popup__tabs" data-generic-tabs>
        <div class="generic-tabs__list" aria-label="Categories d invitations">
            <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="omoDecisionInvitationsTabHolons">Holons</button>
            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omoDecisionInvitationsTabMembers">Membres</button>
            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omoDecisionInvitationsTabGuests">Invites</button>
        </div>
        <div class="generic-tabs__panels">
            <div id="omoDecisionInvitationsTabHolons" class="generic-tabs__panel omo-decision-invitations-popup__tab-panel" data-generic-tab-panel>
                <strong>Holons invites</strong>
                <p class="omo-decision-invitations-popup__hint">
                    Le holon courant apparait ici comme n importe quel autre. S il n est pas coche, ses membres ne seront pas inclus des qu une invitation explicite existe.
                </p>
                <div class="omo-decision-invitations-popup__checklist">
                    <?php if (is_array($holonTree)): ?>
                        <?php omoDecisionInvitationsRenderHolonTreeNode($holonTree); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div id="omoDecisionInvitationsTabMembers" class="generic-tabs__panel omo-decision-invitations-popup__tab-panel" data-generic-tab-panel hidden>
                <strong>Membres supplementaires de l organisation</strong>
                <div class="omo-decision-invitations-popup__member-list">
                    <?php foreach ($memberships as $membership): ?>
                        <?php
                        $userId = (int)$membership->get('IDuser');
                        if ($userId <= 0) {
                            continue;
                        }
                        $displayName = $membership->getUserDisplayName();
                        $secondary = $membership->getScopedEmail() !== '' ? $membership->getScopedEmail() : $membership->getUserSecondaryLabel();
                        ?>
                        <label class="omo-decision-invitations-popup__check">
                            <input type="checkbox" name="user_ids[]" value="<?= $userId ?>"<?= in_array($userId, $selectedUserIds, true) ? ' checked' : '' ?>>
                            <span class="omo-decision-invitations-popup__check-meta">
                                <strong><?= omoApiEscape($displayName) ?></strong>
                                <?php if ($secondary !== ''): ?>
                                <span class="omo-decision-invitations-popup__member-email"><?= omoApiEscape($secondary) ?></span>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="omo-decision-invitations-popup__hint">
                    Cochez les membres a inviter individuellement, en plus des holons selectionnes.
                </p>
            </div>

            <div id="omoDecisionInvitationsTabGuests" class="generic-tabs__panel omo-decision-invitations-popup__tab-panel" data-generic-tab-panel hidden>
                <label for="omoDecisionInvitationsEmails"><strong>Adresses e-mail externes</strong></label>
                <textarea
                    id="omoDecisionInvitationsEmails"
                    name="emails"
                    class="omo-decision-invitations-popup__textarea generic-form-control"
                    placeholder="prenom.nom@exemple.ch"
                ><?= omoApiEscape(implode("\n", $selectedEmails)) ?></textarea>
                <p class="omo-decision-invitations-popup__hint">
                    Une adresse par ligne. Les invitations seront envoyees plus tard.
                </p>
            </div>
        </div>
    </div>

    <div id="omoDecisionInvitationsPopupFeedback" class="omo-decision-invitations-popup__feedback"></div>

    <div class="omo-decision-invitations-popup__actions">
        <button type="submit" id="omoDecisionInvitationsPopupSubmit" class="generic-action-button generic-action-button--main">
            Enregistrer les invitations
        </button>
    </div>
</form>

<script>
(function () {
    var form = document.getElementById('omoDecisionInvitationsPopupForm');
    var feedback = document.getElementById('omoDecisionInvitationsPopupFeedback');
    var submitButton = document.getElementById('omoDecisionInvitationsPopupSubmit');

    if (!form || !feedback || !submitButton) {
        return;
    }

    if (typeof window.initGenericComponents === 'function') {
        window.initGenericComponents(form);
    }

    Array.prototype.forEach.call(form.querySelectorAll('[data-omo-decision-holon-toggle]'), function (toggle) {
        toggle.addEventListener('click', function (event) {
            var node;
            var children;
            var isExpanded;

            event.preventDefault();
            event.stopPropagation();

            node = toggle.closest('[data-omo-decision-holon-node]');
            children = node ? node.querySelector('[data-omo-decision-holon-children]') : null;
            if (!children) {
                return;
            }

            isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            children.hidden = isExpanded;
        });
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        feedback.textContent = '';
        feedback.classList.remove('is-success');
        submitButton.disabled = true;

        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return {
                        ok: response.ok,
                        data: data
                    };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.status) {
                    feedback.textContent = result.data && result.data.message ? result.data.message : 'Une erreur est survenue.';
                    submitButton.disabled = false;
                    return;
                }

                feedback.textContent = result.data.message || 'Invitations mises a jour.';
                feedback.classList.add('is-success');

                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }

                if (result.data.redirectUrl && typeof window.omoDecisionOpenNestedDrawer === 'function') {
                    window.omoDecisionOpenNestedDrawer(result.data.drawerTitle || 'Prises de decision', result.data.redirectUrl, '');
                    return;
                }

                if (result.data.redirectUrl && typeof window.commonTopbarOpenDrawer === 'function') {
                    window.commonTopbarOpenDrawer(result.data.drawerTitle || 'Prises de decision', result.data.redirectUrl, 'fetch');
                }
            })
            .catch(function () {
                feedback.textContent = 'Impossible d enregistrer ces invitations pour le moment.';
                submitButton.disabled = false;
            });
    });
})();
</script>
