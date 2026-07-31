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

function omoDecisionInvitationsPopupSourceLang()
{
    return [
        'decisions.invitations_popup.denied' => [
            'text' => 'Vous ne pouvez pas gerer les invitations de ce scrutin.',
            'context' => 'Message shown when the current user cannot manage invitations for the decision.',
        ],
        'decisions.invitations_popup.db_error' => [
            'text' => 'Connexion a la base impossible.',
            'context' => 'Error returned when the invitation popup cannot access the database.',
        ],
        'decisions.invitations_popup.save_error' => [
            'text' => 'Impossible d enregistrer les invitations pour le moment.',
            'context' => 'Generic error returned when invitation changes cannot be saved.',
        ],
        'decisions.invitations_popup.updated' => [
            'text' => 'Invitations mises a jour.',
            'context' => 'Success message returned after saving invitation changes.',
        ],
        'decisions.invitations_popup.drawer_title' => [
            'text' => 'Prises de decision',
            'context' => 'Drawer title used when reopening the decision editor after saving invitation changes.',
        ],
        'decisions.invitations_popup.current' => [
            'text' => '(courant)',
            'context' => 'Suffix shown next to the current holon in the invitation tree.',
        ],
        'decisions.invitations_popup.context.current' => [
            'text' => 'du contexte courant',
            'context' => 'Suffix used in the intro when a current holon context exists.',
        ],
        'decisions.invitations_popup.context.organization' => [
            'text' => 'de l organisation',
            'context' => 'Suffix used in the intro when the organization is the active context.',
        ],
        'decisions.invitations_popup.intro' => [
            'text' => 'Definissez ici les participants explicites du scrutin. Si vous laissez tout vide, seuls les membres {context_label} restent autorises.',
            'context' => 'Introductory text displayed at the top of the invitation popup.',
        ],
        'decisions.invitations_popup.no_structure' => [
            'text' => 'Cette organisation n a pas encore de structure. Vous pouvez inviter directement des membres de l organisation ou des adresses e-mail externes.',
            'context' => 'Hint shown when the organization has no holon structure.',
        ],
        'decisions.invitations_popup.tabs_aria' => [
            'text' => 'Categories d invitations',
            'context' => 'Accessible label for the invitation popup tabs.',
        ],
        'decisions.invitations_popup.tab.holons' => [
            'text' => 'Holons',
            'context' => 'Tab label for invited holons.',
        ],
        'decisions.invitations_popup.tab.members' => [
            'text' => 'Membres',
            'context' => 'Tab label for invited organization members.',
        ],
        'decisions.invitations_popup.tab.guests' => [
            'text' => 'Invites',
            'context' => 'Tab label for invited guest emails.',
        ],
        'decisions.invitations_popup.tab.public' => [
            'text' => 'Public',
            'context' => 'Tab label for public participation settings and public-link additions.',
        ],
        'decisions.invitations_popup.holons_title' => [
            'text' => 'Holons invites',
            'context' => 'Section title for invited holons in the invitation popup.',
        ],
        'decisions.invitations_popup.holons_hint' => [
            'text' => 'Le holon courant apparait ici comme n importe quel autre. S il n est pas coche, ses membres ne seront pas inclus des qu une invitation explicite existe.',
            'context' => 'Hint shown under the invited holons section.',
        ],
        'decisions.invitations_popup.members_title' => [
            'text' => 'Membres supplementaires de l organisation',
            'context' => 'Section title for invited organization members.',
        ],
        'decisions.invitations_popup.members_hint_structure' => [
            'text' => 'Cochez les membres a inviter individuellement, en plus des holons selectionnes.',
            'context' => 'Hint shown below invited members when a holon structure exists.',
        ],
        'decisions.invitations_popup.members_hint_flat' => [
            'text' => 'Cochez les membres a inviter individuellement. Sans structure, ils representent le contexte organisationnel.',
            'context' => 'Hint shown below invited members when there is no holon structure.',
        ],
        'decisions.invitations_popup.guests_title' => [
            'text' => 'Adresses e-mail externes',
            'context' => 'Section title for invited external email addresses.',
        ],
        'decisions.invitations_popup.guests_placeholder' => [
            'text' => 'prenom.nom@exemple.ch',
            'context' => 'Placeholder used in the external guest emails textarea.',
        ],
        'decisions.invitations_popup.guests_hint' => [
            'text' => 'Une adresse par ligne. Les invitations seront envoyees plus tard.',
            'context' => 'Hint shown below the external guest emails textarea.',
        ],
        'decisions.invitations_popup.public_title' => [
            'text' => 'Participation sans invitation',
            'context' => 'Title of the checkbox enabling public self-registration.',
        ],
        'decisions.invitations_popup.public_hint' => [
            'text' => 'Toute personne disposant du lien public peut demander un code par e-mail. Si son adresse n est pas encore associee a ce scrutin, un participant est cree automatiquement.',
            'context' => 'Hint shown under the public self-registration checkbox.',
        ],
        'decisions.invitations_popup.public_people_title' => [
            'text' => 'Personnes deja ajoutees via le lien public',
            'context' => 'Title of the list showing people already added from the public link.',
        ],
        'decisions.invitations_popup.public_people_empty' => [
            'text' => 'Personne ne s est encore ajoute via le lien public.',
            'context' => 'Empty-state text for the public-link participant list.',
        ],
        'decisions.invitations_popup.public_people_hint' => [
            'text' => 'Ces personnes restent distinctes des invitations explicites, mais elles ont deja demande un acces.',
            'context' => 'Hint shown below the public-link participant list.',
        ],
        'decisions.invitations_popup.public_member_badge' => [
            'text' => 'Ajoute via lien public',
            'context' => 'Tooltip shown on a disabled checked member checkbox when the person joined via the public link.',
        ],
        'decisions.invitations_popup.public_member_type' => [
            'text' => 'Membre de l organisation',
            'context' => 'Secondary label shown for a public-link participant tied to an existing organization member.',
        ],
        'decisions.invitations_popup.public_guest_type' => [
            'text' => 'Adresse externe',
            'context' => 'Secondary label shown for a public-link participant tied only to an external email.',
        ],
        'decisions.invitations_popup.submit' => [
            'text' => 'Enregistrer les invitations',
            'context' => 'Submit button label in the invitation popup.',
        ],
        'decisions.invitations_popup.js_error' => [
            'text' => 'Une erreur est survenue.',
            'context' => 'Fallback error message shown by the popup JavaScript when the server does not return a message.',
        ],
        'decisions.invitations_popup.js_request_error' => [
            'text' => 'Impossible d enregistrer ces invitations pour le moment.',
            'context' => 'Network error message shown by the popup JavaScript when the request fails.',
        ],
    ];
}

function omoDecisionInvitationsPopupT($key, array $variables = [])
{
    global $lang, $sourceLang;

    return t($key, $variables, $lang, $sourceLang);
}

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

function omoDecisionInvitationsRenderHolonTreeNode(array $node, $currentLabel)
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
                    <strong><?= omoApiEscape((string)$node['label']) ?><?= !empty($node['isCurrent']) ? ' ' . omoApiEscape((string)$currentLabel) : '' ?></strong>
                    <span class="omo-decision-invitations-popup__check-type"><?= omoApiEscape((string)$node['typeLabel']) ?></span>
                </span>
            </label>
        </div>

        <?php if ($hasChildren): ?>
        <div class="omo-decision-invitations-popup__tree-children" data-omo-decision-holon-children<?= $isExpanded ? '' : ' hidden' ?>>
            <?php foreach ($node['children'] as $childNode): ?>
                <?php omoDecisionInvitationsRenderHolonTreeNode($childNode, $currentLabel); ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

$input = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? array_merge($_GET, $_POST)
    : $_GET;
$context = omoDecisionResolveEditorContext($input);
$sourceLang = omoDecisionInvitationsPopupSourceLang();
$lang = omoLoadTranslationBundle('omo_decision_invitations_popup', $sourceLang);

if (empty($context['status']) || empty($context['decision']) || !($context['decision'] instanceof DecisionProcess) || empty($context['canManage'])) {
    $statusCode = (int)($context['code'] ?? 403);
    http_response_code($statusCode);
    ?>
    <div class="omo-decision-invitations-popup__empty"><?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.denied')) ?></div>
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
    $selectedEmails = omoDecisionParseInvitationEmails($_POST['emails'] ?? '');
    $allowPublicSelfRegistration = !empty($_POST['allow_public_self_registration']);

    $pdo = DbObject::getPdo();
    if (!$pdo) {
        omoDecisionModuleJsonResponse(500, [
            'status' => false,
            'message' => omoDecisionInvitationsPopupT('decisions.invitations_popup.db_error'),
        ]);
    }

    try {
        $pdo->beginTransaction();

        $applyResult = omoDecisionApplyInvitationSelections(
            $decision,
            $organization,
            $organizationId,
            $selectedHolonIds,
            $selectedUserIds,
            $selectedEmails,
            $allowPublicSelfRegistration
        );
        if (empty($applyResult['status'])) {
            throw new InvalidArgumentException(
                trim((string)($applyResult['message'] ?? omoDecisionInvitationsPopupT('decisions.invitations_popup.save_error')))
            );
        }

        $syncResult = $decision->syncParticipantsFromInvitations();
        if (!is_array($syncResult) || empty($syncResult['status'])) {
            throw new RuntimeException('participant_sync_failed');
        }

        $pdo->commit();
    } catch (InvalidArgumentException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        omoDecisionModuleJsonResponse(422, [
            'status' => false,
            'message' => trim((string)$exception->getMessage()) !== ''
                ? trim((string)$exception->getMessage())
                : omoDecisionInvitationsPopupT('decisions.invitations_popup.save_error'),
        ]);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        omoDecisionModuleJsonResponse(500, [
            'status' => false,
            'message' => omoDecisionInvitationsPopupT('decisions.invitations_popup.save_error'),
        ]);
    }

    omoDecisionModuleJsonResponse(200, [
        'status' => true,
        'message' => omoDecisionInvitationsPopupT('decisions.invitations_popup.updated'),
        'redirectUrl' => omoDecisionBuildEditorUrl($organizationId, $targetHolonId, (int)$decision->getId(), $method, 'manage'),
        'drawerTitle' => omoDecisionInvitationsPopupT('decisions.invitations_popup.drawer_title'),
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
$allowPublicSelfRegistration = $decision->isPublicSelfRegistrationEnabled();
$publicOptInState = function_exists('omoDecisionExtractPublicOptInSelections')
    ? omoDecisionExtractPublicOptInSelections($decision)
    : ['entries' => [], 'user_ids' => [], 'emails' => [], 'count' => 0];
$publicOptInEntries = (array)($publicOptInState['entries'] ?? []);
$publicOptInUserIds = array_values(array_unique(array_map('intval', (array)($publicOptInState['user_ids'] ?? []))));

$rootHolon = $organization instanceof Organization ? $organization->getEnabledStructuralRootHolon() : null;
$holonTree = $rootHolon instanceof Holon
    ? omoDecisionInvitationsBuildHolonTreeData($rootHolon, $organization, $selectedHolonIds, $targetHolonId)
    : null;
$hasHolonStructure = is_array($holonTree);
$currentContextLabel = $effectiveHolon instanceof Holon
    ? omoDecisionInvitationsPopupT('decisions.invitations_popup.context.current')
    : omoDecisionInvitationsPopupT('decisions.invitations_popup.context.organization');

$memberships = new ArrayUserOrganization();
$memberships->loadActiveForOrganization($organizationId);
?>
<style>
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

.omo-decision-invitations-popup__textarea {
    min-height: 120px;
}

.omo-decision-invitations-popup__public-empty {
    margin: 0;
    color: var(--topbar-panel-muted, #64748b);
    line-height: 1.5;
}
</style>

<form
    id="omoDecisionInvitationsPopupForm"
    class="omo-decision-invitations-popup generic-stack generic-stack--flush"
    action="/omo/api/decision/invitations_popup.php?oid=<?= (int)$organizationId ?>&cid=<?= (int)$targetHolonId ?>&id=<?= (int)$decision->getId() ?>&method=<?= urlencode($method) ?>"
    method="post"
>
    <div class="omo-decision-invitations-popup__header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy omo-decision-invitations-popup__header-copy">
            <div class="generic-card-title generic-card-title--eyebrow">Prises de decision</div>
            <h3 class="generic-card-title generic-card-title--medium">Invitations</h3>
        </div>
    </div>
    <div class="omo-decision-invitations-popup__shell generic-drawer-content">
    <p class="omo-decision-invitations-popup__intro generic-description">
        <?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.intro', ['context_label' => $currentContextLabel])) ?>
    </p>

    <?php if (!$hasHolonStructure): ?>
    <p class="omo-decision-invitations-popup__hint generic-description">
        <?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.no_structure')) ?>
    </p>
    <?php endif; ?>

    <div class="generic-tabs omo-decision-invitations-popup__tabs" data-generic-tabs>
        <div class="generic-tabs__list" aria-label="<?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.tabs_aria')) ?>">
            <?php if ($hasHolonStructure): ?>
            <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="omoDecisionInvitationsTabHolons"><?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.tab.holons')) ?></button>
            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omoDecisionInvitationsTabMembers"><?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.tab.members')) ?></button>
            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omoDecisionInvitationsTabGuests"><?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.tab.guests')) ?></button>
            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omoDecisionInvitationsTabPublic"><?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.tab.public')) ?></button>
            <?php else: ?>
            <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="omoDecisionInvitationsTabMembers"><?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.tab.members')) ?></button>
            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omoDecisionInvitationsTabGuests"><?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.tab.guests')) ?></button>
            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omoDecisionInvitationsTabPublic"><?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.tab.public')) ?></button>
            <?php endif; ?>
        </div>
        <div class="generic-tabs__panels">
            <?php if ($hasHolonStructure): ?>
            <div id="omoDecisionInvitationsTabHolons" class="generic-tabs__panel omo-decision-invitations-popup__tab-panel" data-generic-tab-panel>
                <strong><?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.holons_title')) ?></strong>
                <p class="omo-decision-invitations-popup__hint generic-description">
                    <?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.holons_hint')) ?>
                </p>
                <div class="omo-decision-invitations-popup__checklist">
                    <?php if (is_array($holonTree)): ?>
                        <?php omoDecisionInvitationsRenderHolonTreeNode($holonTree, omoDecisionInvitationsPopupT('decisions.invitations_popup.current')); ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div id="omoDecisionInvitationsTabMembers" class="generic-tabs__panel omo-decision-invitations-popup__tab-panel" data-generic-tab-panel<?= $hasHolonStructure ? ' hidden' : '' ?>>
                <strong><?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.members_title')) ?></strong>
                <div class="omo-decision-invitations-popup__member-list">
                    <?php foreach ($memberships as $membership): ?>
                        <?php
                        $userId = (int)$membership->get('IDuser');
                        if ($userId <= 0) {
                            continue;
                        }
                        $displayName = $membership->getUserDisplayName();
                        $secondary = $membership->getScopedEmail() !== '' ? $membership->getScopedEmail() : $membership->getUserSecondaryLabel();
                        $isExplicitUser = in_array($userId, $selectedUserIds, true);
                        $isPublicOnlyUser = in_array($userId, $publicOptInUserIds, true) && !$isExplicitUser;
                        ?>
                        <label class="omo-decision-invitations-popup__check">
                            <input
                                type="checkbox"
                                name="user_ids[]"
                                value="<?= $userId ?>"
                                <?= ($isExplicitUser || $isPublicOnlyUser) ? ' checked' : '' ?>
                                <?= $isPublicOnlyUser ? ' disabled title="' . omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.public_member_badge')) . '"' : '' ?>
                            >
                            <span class="omo-decision-invitations-popup__check-meta">
                                <strong><?= omoApiEscape($displayName) ?></strong>
                                <?php if ($secondary !== ''): ?>
                                <span class="omo-decision-invitations-popup__member-email"><?= omoApiEscape($secondary) ?></span>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="omo-decision-invitations-popup__hint generic-description">
                    <?= omoApiEscape($hasHolonStructure
                        ? omoDecisionInvitationsPopupT('decisions.invitations_popup.members_hint_structure')
                        : omoDecisionInvitationsPopupT('decisions.invitations_popup.members_hint_flat')) ?>
                </p>
            </div>

            <div id="omoDecisionInvitationsTabGuests" class="generic-tabs__panel omo-decision-invitations-popup__tab-panel" data-generic-tab-panel hidden>
                <label for="omoDecisionInvitationsEmails"><strong><?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.guests_title')) ?></strong></label>
                <textarea
                    id="omoDecisionInvitationsEmails"
                    name="emails"
                    class="omo-decision-invitations-popup__textarea generic-form-control"
                    placeholder="<?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.guests_placeholder')) ?>"
                ><?= omoApiEscape(implode("\n", $selectedEmails)) ?></textarea>
                <p class="omo-decision-invitations-popup__hint generic-description">
                    <?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.guests_hint')) ?>
                </p>
            </div>

            <div id="omoDecisionInvitationsTabPublic" class="generic-tabs__panel omo-decision-invitations-popup__tab-panel" data-generic-tab-panel hidden>
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <label class="omo-decision-invitations-popup__check">
                        <input type="checkbox" name="allow_public_self_registration" value="1"<?= $allowPublicSelfRegistration ? ' checked' : '' ?>>
                        <span class="omo-decision-invitations-popup__check-meta">
                            <strong><?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.public_title')) ?></strong>
                            <span class="omo-decision-invitations-popup__member-email">
                                <?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.public_hint')) ?>
                            </span>
                        </span>
                    </label>
                </div>

                <strong><?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.public_people_title')) ?></strong>
                <?php if (count($publicOptInEntries) > 0): ?>
                <div class="omo-decision-invitations-popup__checklist">
                    <?php foreach ($publicOptInEntries as $publicOptInEntry): ?>
                    <div class="generic-soft-panel generic-soft-panel--stack">
                        <strong><?= omoApiEscape((string)$publicOptInEntry['label']) ?></strong>
                        <?php if (trim((string)$publicOptInEntry['email']) !== '' && trim((string)$publicOptInEntry['email']) !== trim((string)$publicOptInEntry['label'])): ?>
                        <span class="omo-decision-invitations-popup__member-email"><?= omoApiEscape((string)$publicOptInEntry['email']) ?></span>
                        <?php endif; ?>
                        <span class="omo-decision-invitations-popup__member-email">
                            <?= omoApiEscape((int)($publicOptInEntry['userId'] ?? 0) > 0
                                ? omoDecisionInvitationsPopupT('decisions.invitations_popup.public_member_type')
                                : omoDecisionInvitationsPopupT('decisions.invitations_popup.public_guest_type')) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="omo-decision-invitations-popup__public-empty">
                    <?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.public_people_empty')) ?>
                </p>
                <?php endif; ?>

                <p class="omo-decision-invitations-popup__hint generic-description">
                    <?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.public_people_hint')) ?>
                </p>
            </div>
        </div>
    </div>

    <div id="omoDecisionInvitationsPopupFeedback" class="omo-decision-invitations-popup__feedback generic-feedback"></div>

    <div class="omo-decision-invitations-popup__actions generic-action-row">
        <button type="submit" id="omoDecisionInvitationsPopupSubmit" class="generic-action-button generic-action-button--main">
            <?= omoApiEscape(omoDecisionInvitationsPopupT('decisions.invitations_popup.submit')) ?>
        </button>
    </div>
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
                    feedback.textContent = result.data && result.data.message ? result.data.message : <?= json_encode(omoDecisionInvitationsPopupT('decisions.invitations_popup.js_error')) ?>;
                    submitButton.disabled = false;
                    return;
                }

                feedback.textContent = result.data.message || <?= json_encode(omoDecisionInvitationsPopupT('decisions.invitations_popup.updated')) ?>;
                feedback.classList.add('is-success');

                if (typeof window.commonTopbarCloseModal === 'function') {
                    window.commonTopbarCloseModal();
                }

                if (result.data.redirectUrl && typeof window.omoDecisionOpenNestedDrawer === 'function') {
                    window.omoDecisionOpenNestedDrawer(result.data.drawerTitle || <?= json_encode(omoDecisionInvitationsPopupT('decisions.invitations_popup.drawer_title')) ?>, result.data.redirectUrl, '');
                    return;
                }

                if (result.data.redirectUrl && typeof window.commonTopbarOpenDrawer === 'function') {
                    window.commonTopbarOpenDrawer(result.data.drawerTitle || <?= json_encode(omoDecisionInvitationsPopupT('decisions.invitations_popup.drawer_title')) ?>, result.data.redirectUrl, 'fetch');
                }
            })
            .catch(function () {
                feedback.textContent = <?= json_encode(omoDecisionInvitationsPopupT('decisions.invitations_popup.js_request_error')) ?>;
                submitButton.disabled = false;
            });
    });
})();
</script>
