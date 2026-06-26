<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/leaflet_helper.php';

use dbObject\ArrayUserOrganization;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\User;
use dbObject\UserOrganization;

function omoTeamHolonTypeLabel(Holon $holon)
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
            return 'holon';
    }
}

function omoTeamNormalizeLatLong($value)
{
    if (!is_object($value)) {
        return null;
    }

    $latitude = $value->lat ?? null;
    $longitude = $value->long ?? null;
    if (!is_numeric($latitude) || !is_numeric($longitude)) {
        return null;
    }

    return array(
        'lat' => (float)$latitude,
        'long' => (float)$longitude,
    );
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;

if ($organizationId <= 0) {
    http_response_code(400);
    ?>
    <div class="omo-team omo-panel-view">
        <div class="omo-panel-view__body">
            <div class="omo-panel-view__body_content">
                <div class="omo-team__empty omo-empty-state">Organisation invalide.</div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-team omo-panel-view">
        <div class="omo-panel-view__body">
            <div class="omo-panel-view__body_content">
                <div class="omo-team__empty omo-empty-state">Organisation introuvable.</div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

if (!$organization->canViewDetail()) {
    http_response_code(403);
    ?>
    <div class="omo-team omo-panel-view">
        <div class="omo-panel-view__body">
            <div class="omo-panel-view__body_content">
                <div class="omo-team__empty omo-empty-state">Acces refuse a cette organisation.</div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

if (
    function_exists('commonGetCurrentShareToken')
    && commonGetCurrentShareToken() !== ''
    && !commonCurrentShareAllowsPeople()
) {
    http_response_code(403);
    ?>
    <div class="omo-team omo-panel-view">
        <div class="omo-panel-view__body">
            <div class="omo-panel-view__body_content">
                <div class="omo-team__empty omo-empty-state">Acces refuse a la liste des personnes.</div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

$rootHolon = $organization->getEnabledStructuralRootHolon();
$hasStructureContext = $rootHolon instanceof Holon;
$currentHolon = $rootHolon;

if ($hasStructureContext && $currentHolonId > 0 && (int)$currentHolon->getId() !== $currentHolonId) {
    $candidate = new Holon();
    if (!$candidate->load($currentHolonId) || !$candidate->isDescendantOf($currentHolon->getId())) {
        http_response_code(404);
        ?>
        <div class="omo-team omo-panel-view">
            <div class="omo-panel-view__body">
                <div class="omo-panel-view__body_content">
                    <div class="omo-team__empty omo-empty-state">Holon introuvable pour cette organisation.</div>
                </div>
            </div>
        </div>
        <?php
        exit;
    }

    $canViewCandidate = $candidate->canViewDetail()
        || (function_exists('commonCurrentShareContainsHolon') && commonCurrentShareContainsHolon($candidate));
    if (!$canViewCandidate) {
        http_response_code(403);
        ?>
        <div class="omo-team omo-panel-view">
            <div class="omo-panel-view__body">
                <div class="omo-panel-view__body_content">
                    <div class="omo-team__empty omo-empty-state">Acces refuse a ce holon.</div>
                </div>
            </div>
        </div>
        <?php
        exit;
    }

    $currentHolon = $candidate;
}

$canToggleTeamScope = $hasStructureContext && $currentHolon instanceof Holon;
$availableTeamScopes = omoApiGetAvailableContextScopes($canToggleTeamScope, $currentHolon, $rootHolon);
$teamScope = omoApiNormalizeContextScope($_GET['team_scope'] ?? 'contextual', $availableTeamScopes);
$teamScopeActiveIndex = omoApiResolveContextScopeIndex($teamScope, $availableTeamScopes);
$teamScopeLabels = array(
    'contextual' => 'Contextuel',
    'descendants' => 'Descendants',
    'global' => 'Global',
);

$rawMemberCards = array();
$contextAdminUserIds = array();
$directContextMemberUserIds = array();

if ($hasStructureContext) {
    $directContextMemberUserIds = array_fill_keys($currentHolon->getDirectMemberUserIds($organizationId), true);

    if ($teamScope === 'global' && $rootHolon instanceof Holon) {
        $rawMemberCards = $rootHolon->getAssociatedMemberCards(array(
            'organizationId' => $organizationId,
            'includeDescendants' => true,
        ));
    } elseif ($teamScope === 'contextual') {
        $rawMemberCards = $currentHolon->getAssociatedMemberCards(array(
            'organizationId' => $organizationId,
        ));
    } else {
        $rawMemberCards = $currentHolon->getAssociatedMemberCards(array(
            'organizationId' => $organizationId,
            'includeDescendants' => true,
        ));
    }

    $contextAdminUserIds = array_fill_keys($currentHolon->getDirectContextAdminUserIds($organizationId), true);
} else {
    $memberships = new ArrayUserOrganization();
    $memberships->loadVisibleForOrganization($organizationId);

    foreach ($memberships as $membership) {
        if (!$membership instanceof UserOrganization) {
            continue;
        }

        $userId = (int)$membership->get('IDuser');
        if ($userId <= 0) {
            continue;
        }

        if ($membership->isOrganizationAdmin() && (bool)$membership->get('active')) {
            $contextAdminUserIds[$userId] = true;
        }

        $directContextMemberUserIds[$userId] = true;

        $rawMemberCards[] = array(
            'userId' => $userId,
            'isPending' => !(bool)$membership->get('active'),
        );
    }
}

$memberCards = [];

$formatter = class_exists('IntlDateFormatter')
    ? new IntlDateFormatter('fr_CH', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE)
    : null;

$formatDate = static function ($value) use ($formatter): string {
    if (!$value instanceof DateTimeInterface) {
        return '';
    }

    if ($formatter instanceof IntlDateFormatter) {
        $formatted = $formatter->format($value);
        if (is_string($formatted) && $formatted !== '') {
            return $formatted;
        }
    }

    return $value->format('d.m.Y');
};

$formatLastSeenLabel = static function ($organizationDate, $globalDate) use ($formatDate): string {
    $organizationLabel = $organizationDate instanceof DateTimeInterface ? $formatDate($organizationDate) : '';
    $globalLabel = $globalDate instanceof DateTimeInterface ? $formatDate($globalDate) : '';

    if ($organizationLabel !== '') {
        if ($globalLabel !== '') {
            return $organizationLabel . ' (générale : ' . $globalLabel . ')';
        }

        return $organizationLabel;
    }

    return $globalLabel;
};

foreach ($rawMemberCards as $rawCard) {
    $userId = (int)($rawCard['userId'] ?? 0);
    if ($userId <= 0) {
        continue;
    }

    $membership = new UserOrganization();
    $hasMembership = $membership->load(array(
        array('IDuser', $userId),
        array('IDorganization', $organizationId),
    ));

    $user = new User();
    $hasUser = $user->load($userId);
    if ($hasUser && !$user->canView()) {
        continue;
    }

    $canViewUserDetail = $hasUser ? $user->canViewDetail() : false;

    $isPending = !empty($rawCard['isPending']) || ($hasMembership && !(bool)$membership->get('active'));
    $isOrganizationAdmin = $hasMembership ? $membership->isOrganizationAdmin() : false;
    $isContextAdmin = $hasStructureContext
        ? isset($contextAdminUserIds[$userId])
        : $isOrganizationAdmin;
    $organizationLastSeen = $hasMembership ? $membership->get('dateconnexion') : null;
    $organizationJoinedAt = $hasMembership ? $membership->get('datecreation') : null;
    $globalLastSeen = $hasMembership
        ? $membership->getGlobalLastConnectionAt()
        : ($hasUser && $user->get('dateconnexion') instanceof DateTimeInterface ? $user->get('dateconnexion') : null);
    $globalJoinedAt = $hasMembership
        ? $membership->getGlobalCreatedAt()
        : ($hasUser && $user->get('datecreation') instanceof DateTimeInterface ? $user->get('datecreation') : null);
    $effectiveLastSeen = $organizationLastSeen instanceof DateTimeInterface ? $organizationLastSeen : $globalLastSeen;

    $effectiveJoinedAt = $organizationJoinedAt instanceof DateTimeInterface ? $organizationJoinedAt : $globalJoinedAt;
    $displayName = trim((string)($rawCard['displayName'] ?? ''));
    if ($displayName === '' && $hasMembership) {
        $displayName = $membership->getUserDisplayName();
    }
    if ($displayName === '' && $hasUser) {
        $displayName = $user->getScopedDisplayName($organizationId);
    }

    $email = $hasMembership ? $membership->getScopedEmail() : ($hasUser ? $user->getScopedEmail($organizationId) : '');
    $username = $hasMembership ? $membership->getScopedUsername() : ($hasUser ? $user->getScopedUsername($organizationId) : '');
    $secondary = $email !== ''
        ? $email
        : ($hasMembership
            ? $membership->getUserSecondaryLabel()
            : ($username !== '' ? '@' . $username : ''));

    $photoUrl = trim((string)($rawCard['photoUrl'] ?? ''));
    if ($photoUrl === '' && $hasMembership) {
        $photoUrl = $membership->getProfilePhotoUrl();
    }
    if ($photoUrl === '' && $hasUser) {
        $photoUrl = $user->getScopedProfilePhotoUrl($organizationId);
    }

    $firstName = $hasUser ? trim((string)$user->get('firstname')) : '';
    $lastName = $hasUser ? trim((string)$user->get('lastname')) : '';
    $phone = '';

    $initials = trim((string)($rawCard['initials'] ?? ''));
    if ($initials === '' && $hasMembership) {
        $initials = $membership->getUserInitials();
    }
    if ($initials === '' && $displayName !== '') {
        $words = preg_split('/\s+/u', $displayName) ?: array();
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
    }

    $latlong = $hasUser ? omoTeamNormalizeLatLong($user->get('latlong')) : null;

    $memberCards[] = array(
        'userId' => $userId,
        'displayName' => $displayName !== '' ? $displayName : ('Utilisateur ' . $userId),
        'firstName' => $firstName,
        'lastName' => $lastName,
        'phone' => $phone,
        'email' => $email,
        'username' => $username,
        'secondary' => $secondary,
        'photoUrl' => $photoUrl,
        'initials' => $initials !== '' ? mb_strtoupper($initials, 'UTF-8') : 'P',
        'isOrganizationAdmin' => $isOrganizationAdmin,
        'isContextAdmin' => $isContextAdmin,
        'isDirectContextMember' => isset($directContextMemberUserIds[$userId]),
        'isPending' => $isPending,
        'joinedAtLabel' => $effectiveJoinedAt instanceof DateTimeInterface ? $formatDate($effectiveJoinedAt) : '',
        'lastSeenLabel' => $formatLastSeenLabel($organizationLastSeen, $globalLastSeen),
        'canViewDetail' => $canViewUserDetail,
        'latlong' => $latlong,
    );
}

usort($memberCards, static function (array $left, array $right): int {
    if ($left['isContextAdmin'] !== $right['isContextAdmin']) {
        return $left['isContextAdmin'] ? -1 : 1;
    }

    return strcmp(
        omoApiSortKey($left['displayName']),
        omoApiSortKey($right['displayName'])
    );
});

$currentHolonTypeLabel = $hasStructureContext ? omoTeamHolonTypeLabel($currentHolon) : 'organisation';
$currentHolonTemplateLabel = $hasStructureContext
    ? trim((string)$currentHolon->getTemplateLabel(true))
    : 'organisation';
if ($currentHolonTemplateLabel === '') {
    $currentHolonTemplateLabel = $currentHolonTypeLabel;
}
$teamEmptyMessage = "Aucune personne n'est encore liee a ce " . $currentHolonTypeLabel . '.';
$teamMapEmptyMessage = "Aucun membre n'a encore de position geographique dans ce contexte.";

if ($teamScope === 'global') {
    $teamEmptyMessage = "Aucune personne n'est encore liee a cette organisation.";
    $teamMapEmptyMessage = "Aucun membre n'a encore de position geographique dans cette organisation.";
} elseif ($teamScope === 'descendants') {
    $teamEmptyMessage = "Aucune personne n'est encore liee a ce contexte et a ses descendants.";
    $teamMapEmptyMessage = "Aucun membre n'a encore de position geographique dans ce contexte et ses descendants.";
}

$canAddCurrentHolonMembers = $hasStructureContext ? $currentHolon->isAllowed('CAN_ADD_MEMBER') : false;
$canRemoveCurrentHolonMembers = $hasStructureContext ? $currentHolon->canEdit() : false;
$canGrantCurrentHolonAdmin = $hasStructureContext ? $currentHolon->isAllowed('CAN_ADD_ADMIN') : false;
$canManageCurrentHolonMembers = $canRemoveCurrentHolonMembers || $canGrantCurrentHolonAdmin;
$leafletMapsEnabled = function_exists('commonLeafletMapsEnabled') && commonLeafletMapsEnabled();
$mapMembers = array_values(array_filter($memberCards, static function (array $card): bool {
    return is_array($card['latlong'] ?? null);
}));
$mapMemberPayload = array_map(static function (array $card): array {
    return array(
        'userId' => (int)$card['userId'],
        'displayName' => (string)$card['displayName'],
        'secondary' => (string)($card['secondary'] ?? ''),
        'email' => (string)($card['email'] ?? ''),
        'joinedAtLabel' => (string)($card['joinedAtLabel'] ?? ''),
        'lastSeenLabel' => (string)($card['lastSeenLabel'] ?? ''),
        'photoUrl' => (string)($card['photoUrl'] ?? ''),
        'initials' => (string)($card['initials'] ?? 'P'),
        'isContextAdmin' => !empty($card['isContextAdmin']),
        'isOrganizationAdmin' => !empty($card['isOrganizationAdmin']),
        'isPending' => !empty($card['isPending']),
        'canViewDetail' => !empty($card['canViewDetail']),
        'lat' => (float)$card['latlong']['lat'],
        'long' => (float)$card['latlong']['long'],
    );
}, $mapMembers);
$leafletAssetsHtml = '';
if ($leafletMapsEnabled) {
    ob_start();
    commonRenderLeafletAssets();
    $leafletAssetsHtml = ob_get_clean();
}
?>
<?= $leafletAssetsHtml ?>
<div
    class="omo-team omo-panel-view"
    id="omo-team-root"
    data-team-oid="<?= (int)$organizationId ?>"
    data-team-cid="<?= $hasStructureContext ? (int)$currentHolon->getId() : 0 ?>"
    data-team-root-hid="<?= $hasStructureContext ? (int)$rootHolon->getId() : 0 ?>"
    data-team-scope="<?= omoApiEscape($teamScope) ?>"
>
    <div class="omo-team__hero omo-panel-view__header omo-panel-view__header--stacked">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <span class="omo-panel-view__app-icon omo-team__app-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M7.5 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"></path>
                        <path d="M16.5 10a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"></path>
                        <path d="M3.5 18.5a4.5 4.5 0 0 1 8 0"></path>
                        <path d="M13 18.5a3.8 3.8 0 0 1 7 0"></path>
                    </svg>
                </span>
                <div class="omo-panel-view__header-copy">
                    <div class="omo-team__title-row">
                        <h2 class="omo-panel-view__title">Team</h2>
                        <span class="omo-panel-view__count"><?= omoApiEscape(count($memberCards)) ?></span>
                    </div>
                </div>
            </div>
            <?php if ($canAddCurrentHolonMembers): ?>
                <div class="omo-team__header-action">
                    <button
                        type="button"
                        class="generic-action-button generic-action-button--main omo-team__add-member-button"
                        data-team-open-member-popup="1"
                        data-hid="<?= (int)$currentHolon->getId() ?>"
                    >Ajouter un membre</button>
                </div>
            <?php endif; ?>
        </div>
        <div class="omo-panel-view__header-secondary omo-team__header-secondary">
            <div class="omo-team__header-controls">
                <?php if ($canToggleTeamScope): ?>
                    <div
                        class="omo-scope-toggle omo-team__scope-toggle"
                        role="tablist"
                        aria-label="Portee des membres"
                        data-omo-scope-switch="<?= omoApiEscape($teamScope) ?>"
                        style="--omo-scope-option-count: <?= (int)count($availableTeamScopes) ?>; --omo-scope-active-index: <?= (int)$teamScopeActiveIndex ?>;"
                    >
                        <?php foreach ($availableTeamScopes as $scopeIndex => $scopeKey): ?>
                            <?php $scopeLabel = (string)($teamScopeLabels[$scopeKey] ?? $scopeKey); ?>
                            <button
                                type="button"
                                class="omo-scope-toggle__button<?= $teamScope === $scopeKey ? ' is-active' : '' ?>"
                                aria-label="<?= omoApiEscape($scopeLabel) ?>"
                                data-team-scope-toggle="<?= omoApiEscape($scopeKey) ?>"
                                data-omo-scope-option="<?= omoApiEscape($scopeKey) ?>"
                                data-omo-scope-index="<?= (int)$scopeIndex ?>"
                                aria-pressed="<?= $teamScope === $scopeKey ? 'true' : 'false' ?>"
                                onclick="return window.omoToggleTeamScope ? window.omoToggleTeamScope(this, event) : false;"
                            ><span class="omo-scope-toggle__text"><?= omoApiEscape($scopeLabel) ?></span></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="omo-segmented omo-team__view-switch" role="tablist" aria-label="Choix de la vue">
                    <button type="button" class="omo-team__view-button omo-segmented__button is-active" data-team-view-button="cards" aria-pressed="true">Cartes</button>
                    <button type="button" class="omo-team__view-button omo-segmented__button" data-team-view-button="compact" aria-pressed="false">Compact</button>
                    <?php if ($leafletMapsEnabled): ?>
                    <button type="button" class="omo-team__view-button omo-segmented__button" data-team-view-button="map" aria-pressed="false">Carte geo</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content">
        <section class="omo-team__view-panel" data-team-view-panel="cards">
        <?php if (count($memberCards) === 0): ?>
            <div class="omo-team__empty omo-empty-state">
                <?= omoApiEscape($teamEmptyMessage) ?>
            </div>
        <?php else: ?>
            <div class="omo-team__grid omo-card-grid omo-card-grid--fixed">
                <?php foreach ($memberCards as $card): ?>
                    <article
                        class="omo-team-card omo-card<?= $card['canViewDetail'] ? ' omo-card--interactive' : '' ?><?= $card['isPending'] ? ' omo-team-card--pending' : '' ?>"
                        <?php if ($card['canViewDetail']): ?>
                        data-open-user-context="1"
                        <?php endif; ?>
                        data-user-id="<?= (int)$card['userId'] ?>"
                        data-context-admin="<?= $card['isContextAdmin'] ? '1' : '0' ?>"
                        data-member-pending="<?= $card['isPending'] ? '1' : '0' ?>"
                        <?php if ($card['canViewDetail']): ?>
                        tabindex="0"
                        role="button"
                        aria-label="Ouvrir le profil contextuel de <?= omoApiEscape($card['displayName']) ?>"
                        <?php endif; ?>
                    >
                        <div class="omo-team-card__banner">
                            <?php if ($canManageCurrentHolonMembers && !empty($card['isDirectContextMember'])): ?>
                                <div class="omo-team-card__menu" data-team-member-menu="1">
                                    <button
                                        type="button"
                                        class="omo-team-card__menu-toggle"
                                        data-team-member-menu-toggle="1"
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                        aria-label="Actions pour <?= omoApiEscape($card['displayName']) ?>"
                                    >...</button>
                                    <div class="omo-team-card__menu-panel" data-team-member-menu-panel="1" hidden>
                                        <?php if ($canRemoveCurrentHolonMembers): ?>
                                            <button
                                                type="button"
                                                class="omo-team-card__menu-item omo-team-card__menu-item--danger"
                                                data-member-action="remove"
                                                data-user-id="<?= (int)$card['userId'] ?>"
                                            >Retirer du contexte <?= omoApiEscape($currentHolonTemplateLabel) ?></button>
                                        <?php endif; ?>
                                        <?php if ($canGrantCurrentHolonAdmin && !$card['isPending']): ?>
                                            <button
                                                type="button"
                                                class="omo-team-card__menu-item"
                                                data-member-action="<?= $card['isContextAdmin'] ? 'revoke_admin' : 'grant_admin' ?>"
                                                data-user-id="<?= (int)$card['userId'] ?>"
                                            ><?= $card['isContextAdmin'] ? 'Retirer le statut admin du contexte ' : 'Définir comme admin du contexte ' ?><?= omoApiEscape($currentHolonTemplateLabel) ?></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="omo-team-card__media">
                            <?php if ($card['photoUrl'] !== ''): ?>
                                <img
                                    src="<?= omoApiEscape($card['photoUrl']) ?>"
                                    alt="<?= omoApiEscape($card['displayName']) ?>"
                                    class="omo-team-card__photo"
                                >
                            <?php else: ?>
                                <div class="omo-team-card__photo-placeholder">
                                    <span class="omo-team-card__initials"><?= omoApiEscape($card['initials']) ?></span>
                                    <span class="omo-team-card__photo-label">Photo à venir</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="omo-team-card__body">
                            <div class="omo-team-card__head">
                                <div class="omo-team-card__identity">
                                    <h3><?= omoApiEscape($card['displayName']) ?></h3>
                                    <?php if ($card['secondary'] !== ''): ?>
                                        <p><?= omoApiEscape($card['secondary']) ?></p>
                                    <?php endif; ?>
                                </div>

                                <?php if ($card['isPending']): ?>
                                    <span class="omo-team-card__badge omo-team-card__badge--pending">En attente</span>
                                <?php elseif ($card['isContextAdmin']): ?>
                                    <span class="omo-team-card__badge">Admin</span>
                                <?php endif; ?>
                            </div>

                            <div class="omo-team-card__meta">
                                <div class="omo-team-card__meta-row">
                                    <span class="omo-team-card__meta-label">E-mail</span>
                                    <span class="omo-team-card__meta-value<?= $card['email'] === '' ? ' omo-team-card__meta-value--muted' : '' ?>">
                                        <?= omoApiEscape($card['email'] !== '' ? $card['email'] : 'Non renseigné') ?>
                                    </span>
                                </div>
                            </div>

                            <div class="omo-team-card__dates">
                                <div class="omo-team-card__date">
                                    <span class="omo-team-card__date-label">Ajout</span>
                                    <span class="omo-team-card__date-value<?= $card['joinedAtLabel'] === '' ? ' omo-team-card__date-value--muted' : '' ?>">
                                        <?= omoApiEscape($card['joinedAtLabel'] !== '' ? $card['joinedAtLabel'] : 'N/A') ?>
                                    </span>
                                </div>

                                <div class="omo-team-card__date">
                                    <span class="omo-team-card__date-label">Connexion</span>
                                    <span class="omo-team-card__date-value<?= $card['lastSeenLabel'] === '' ? ' omo-team-card__date-value--muted' : '' ?>">
                                        <?= omoApiEscape($card['lastSeenLabel'] !== '' ? $card['lastSeenLabel'] : 'Jamais') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </section>
        <section class="omo-team__view-panel omo-team__view-panel--compact generic-file-list generic-file-list--structured" data-team-view-panel="compact" hidden>
        <?php if (count($memberCards) === 0): ?>
            <div class="omo-team__empty omo-empty-state">
                <?= omoApiEscape($teamEmptyMessage) ?>
            </div>
        <?php else: ?>
            <div class="omo-team__compact-list-shell">
                <div class="omo-team__compact-list generic-file-list__table">
                    <div class="omo-team__compact-list-header generic-file-list__header">
                        <div class="omo-team__compact-list-header-cell generic-file-list__header-cell omo-team__compact-list-header-cell--name">Nom</div>
                        <div class="omo-team__compact-list-header-cell generic-file-list__header-cell omo-team__compact-list-header-cell--firstname">Prenom</div>
                        <div class="omo-team__compact-list-header-cell generic-file-list__header-cell omo-team__compact-list-header-cell--phone">Telephone</div>
                        <div class="omo-team__compact-list-header-cell generic-file-list__header-cell omo-team__compact-list-header-cell--email">E-mail</div>
                    </div>
                    <?php foreach ($memberCards as $card): ?>
                        <?php
                        $compactLastName = trim((string)($card['lastName'] ?? ''));
                        $compactFirstName = trim((string)($card['firstName'] ?? ''));
                        $compactPhone = trim((string)($card['phone'] ?? ''));
                        $compactUsername = trim((string)($card['username'] ?? ''));
                        $compactDisplayName = trim((string)($card['displayName'] ?? ''));
                        $compactEmailLocalPart = '';
                        if (trim((string)($card['email'] ?? '')) !== '') {
                            $compactEmailParts = explode('@', trim((string)$card['email']), 2);
                            $compactEmailLocalPart = trim((string)($compactEmailParts[0] ?? ''));
                        }
                        $hasStructuredName = ($compactLastName !== '' || $compactFirstName !== '');
                        $compactNameLabel = $compactLastName;
                        $compactFirstNameLabel = $compactFirstName;
                        $compactMetaUsername = $hasStructuredName && $compactUsername !== ''
                            ? '@' . $compactUsername
                            : '';
                        $compactPrivilegeLabels = array();

                        if (!$hasStructuredName) {
                            if ($compactUsername !== '') {
                                $compactNameLabel = $compactUsername;
                            } elseif ($compactEmailLocalPart !== '') {
                                $compactNameLabel = $compactEmailLocalPart;
                            } elseif ($compactDisplayName !== '') {
                                $compactNameLabel = $compactDisplayName;
                            }

                            $compactFirstNameLabel = '';
                        }

                        if ($card['isPending']) {
                            $compactPrivilegeLabels[] = array(
                                'label' => 'En attente',
                                'className' => 'omo-team__compact-badge omo-team__compact-badge--pending',
                            );
                        } else {
                            if ($card['isContextAdmin']) {
                                $compactPrivilegeLabels[] = array(
                                    'label' => 'Admin contexte',
                                    'className' => 'omo-team__compact-badge',
                                );
                            }

                            if ($card['isOrganizationAdmin']) {
                                $compactPrivilegeLabels[] = array(
                                    'label' => 'Admin organisation',
                                    'className' => 'omo-team__compact-badge omo-team__compact-badge--organization',
                                );
                            }
                        }
                        ?>
                        <article class="omo-team__compact-item-shell generic-file-list__item-shell">
                            <div
                                class="omo-team__compact-row generic-file-list__row<?= $card['canViewDetail'] ? ' omo-team__compact-row--interactive' : '' ?><?= $card['isPending'] ? ' omo-team__compact-row--pending' : '' ?>"
                                <?php if ($card['canViewDetail']): ?>
                                data-open-user-context="1"
                                tabindex="0"
                                role="button"
                                aria-label="Ouvrir le profil contextuel de <?= omoApiEscape($card['displayName']) ?>"
                                <?php endif; ?>
                                data-user-id="<?= (int)$card['userId'] ?>"
                            >
                                <div class="omo-team__compact-cell omo-team__compact-cell--identity generic-file-list__cell generic-file-list__cell--name" data-label="Identite">
                                    <div class="omo-team__compact-name-main generic-file-list__name-main">
                                        <?php if ($card['photoUrl'] !== ''): ?>
                                            <img
                                                src="<?= omoApiEscape($card['photoUrl']) ?>"
                                                alt="<?= omoApiEscape($card['displayName']) ?>"
                                                class="omo-team__compact-photo"
                                            >
                                        <?php else: ?>
                                            <div class="omo-team__compact-photo-placeholder">
                                                <?= omoApiEscape($card['initials']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="omo-team__compact-title-block generic-file-list__title-block">
                                            <div class="omo-team__compact-identity-grid">
                                                <div class="omo-team__compact-title-row generic-file-list__title-row">
                                                    <strong class="omo-team__compact-title generic-file-list__title"><?= omoApiEscape($compactNameLabel !== '' ? $compactNameLabel : '-') ?></strong>
                                                </div>
                                                <div class="omo-team__compact-firstname"><?= omoApiEscape($compactFirstNameLabel) ?></div>
                                            </div>
                                            <?php if ($compactMetaUsername !== '' || count($compactPrivilegeLabels) > 0): ?>
                                                <div class="omo-team__compact-meta-row generic-file-list__meta-line">
                                                    <?php if ($compactMetaUsername !== ''): ?>
                                                        <span class="omo-team__compact-username"><?= omoApiEscape($compactMetaUsername) ?></span>
                                                    <?php endif; ?>
                                                    <?php foreach ($compactPrivilegeLabels as $privilege): ?>
                                                        <span class="<?= omoApiEscape($privilege['className']) ?>"><?= omoApiEscape($privilege['label']) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="omo-team__compact-cell generic-file-list__cell" data-label="Telephone">
                                    <span class="<?= $compactPhone === '' ? 'omo-team__compact-placeholder' : '' ?>"><?= omoApiEscape($compactPhone !== '' ? $compactPhone : '-') ?></span>
                                </div>
                                <div class="omo-team__compact-cell generic-file-list__cell" data-label="E-mail">
                                    <span class="<?= $card['email'] === '' ? 'omo-team__compact-placeholder' : '' ?>"><?= omoApiEscape($card['email'] !== '' ? $card['email'] : '-') ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        </section>
        <?php if ($leafletMapsEnabled): ?>
        <section class="omo-team__view-panel" data-team-view-panel="map" hidden>
            <?php if (count($mapMembers) === 0): ?>
                <div class="omo-team__empty omo-empty-state">
                    <?= omoApiEscape($teamMapEmptyMessage) ?>
                </div>
            <?php else: ?>
                <div class="omo-team__map-shell">
                    <div class="omo-team__map-summary">
                        <?= omoApiEscape(count($mapMembers)) ?> membre<?= count($mapMembers) > 1 ? 's' : '' ?> geolocalise<?= count($mapMembers) > 1 ? 's' : '' ?>.
                    </div>
                    <div id="omo-team-map" class="omo-team__map" data-team-map="1"></div>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        </div>
    </div>
</div>

<style>
.omo-team__app-icon {
    --omo-panel-view-app-icon-accent: #0f766e;
}

.omo-team__title-row {
    display: flex;
    align-items: baseline;
    gap: 10px;
    flex-wrap: wrap;
}

.omo-team__header-secondary {
    align-items: end;
}

.omo-team__header-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    width: 100%;
}

.omo-team__header-action {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    min-width: 0;
    justify-self: end;
}

.omo-team__add-member-button {
    white-space: nowrap;
}

.omo-team__view-switch {
    justify-self: end;
}

.omo-team__scope-toggle {
    flex: 0 0 auto;
}

.omo-team__view-button {
    min-width: 0;
}

.omo-team__view-panel[hidden] {
    display: none !important;
}

.omo-team__map-shell {
    display: grid;
    gap: 12px;
    margin:10px;
}

.omo-team__compact-list-shell {
    --generic-file-list-columns: minmax(0, 1.5fr) minmax(0, 1.2fr) minmax(120px, 0.9fr) minmax(0, 1.7fr);
    margin: 10px;
}

.omo-team__compact-list {
    --generic-file-list-table-margin-inline: 0px;
}

.omo-team__compact-row {
    min-width: 0;
    background: transparent;
    transition: background 180ms ease;
}

.omo-team__compact-row--interactive {
    cursor: pointer;
}

.omo-team__compact-row--interactive:hover,
.omo-team__compact-row--interactive:focus-visible {
    background: color-mix(in srgb, var(--color-primary) 6%, var(--color-surface, #ffffff));
    outline: none;
}

.omo-team__compact-row--pending {
    opacity: 0.7;
}

.omo-team__compact-cell {
    min-width: 0;
    word-break: break-word;
}

.omo-team__compact-cell--identity {
    grid-column: 1 / span 2;
}

.omo-team__compact-name-main {
    min-width: 0;
}

.omo-team__compact-photo,
.omo-team__compact-photo-placeholder {
    width: 34px;
    height: 34px;
    border-radius: 999px;
    flex: 0 0 auto;
}

.omo-team__compact-photo {
    object-fit: cover;
}

.omo-team__compact-photo-placeholder {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #14b8a6, #0f766e);
    color: #ffffff;
    font-size: 0.76rem;
    font-weight: 700;
}

.omo-team__compact-title-block {
    min-width: 0;
}

.omo-team__compact-identity-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(0, 1.2fr);
    gap: 16px;
    align-items: start;
    min-width: 0;
}

.omo-team__compact-title-row {
    min-width: 0;
}

.omo-team__compact-title {
    min-width: 0;
}

.omo-team__compact-firstname {
    min-width: 0;
    font-size: 0.95rem;
    line-height: 1.35;
    color: var(--color-text, #1f2937);
    word-break: break-word;
}

.omo-team__compact-meta-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    word-break: break-word;
}

.omo-team__compact-username {
    color: var(--color-text-light);
}

.omo-team__compact-badge {
    display: inline-flex;
    align-items: center;
    min-height: 22px;
    padding: 0 8px;
    border-radius: 999px;
    background: rgba(245, 158, 11, 0.14);
    color: #b45309;
    font-size: 0.72rem;
    font-weight: 700;
}

.omo-team__compact-badge--pending {
    background: rgba(100, 116, 139, 0.14);
    color: #475569;
}

.omo-team__compact-badge--organization {
    background: rgba(20, 184, 166, 0.12);
    color: #0f766e;
}

.omo-team__compact-placeholder {
    color: var(--color-text-light);
}

.omo-team__map-summary {
    color: var(--color-text-light);
    font-size: 0.9rem;
}

.omo-team__map {
    width: 100%;
    min-height: 460px;
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid var(--color-border);
    background:
        radial-gradient(circle at top left, color-mix(in srgb, var(--color-primary) 14%, transparent), transparent 42%),
        linear-gradient(180deg, var(--color-surface-alt), var(--color-surface));
}

.omo-team__map-popup {
    display: grid;
    gap: 10px;
    min-width: 220px;
    max-width: 280px;
}

.omo-team__map-popup-head {
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    gap: 10px;
}

.omo-team__map-popup-photo,
.omo-team__map-popup-photo-placeholder {
    width: 44px;
    height: 44px;
    border-radius: 999px;
    border: 2px solid rgba(255, 255, 255, 0.9);
    box-shadow: 0 8px 16px rgba(15, 23, 42, 0.14);
}

.omo-team__map-popup-photo {
    object-fit: cover;
}

.omo-team__map-popup-photo-placeholder {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #14b8a6, #0f766e);
    color: #ffffff;
    font-size: 0.78rem;
    font-weight: 700;
}

.omo-team__map-popup-identity {
    min-width: 0;
}

.omo-team__map-popup-name {
    font-weight: 700;
    line-height: 1.2;
}

.omo-team__map-popup-secondary {
    margin-top: 2px;
    color: #475569;
    font-size: 0.82rem;
    line-height: 1.25;
    word-break: break-word;
}

.omo-team__map-popup-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.omo-team__map-popup-badge {
    display: inline-flex;
    align-items: center;
    min-height: 22px;
    padding: 0 8px;
    border-radius: 999px;
    background: rgba(20, 184, 166, 0.12);
    color: #0f766e;
    font-size: 0.72rem;
    font-weight: 700;
}

.omo-team__map-popup-badge--admin {
    background: rgba(245, 158, 11, 0.16);
    color: #b45309;
}

.omo-team__map-popup-badge--pending {
    background: rgba(100, 116, 139, 0.14);
    color: #475569;
}

.omo-team__map-popup-meta {
    display: grid;
    gap: 6px;
}

.omo-team__map-popup-meta-row {
    display: grid;
    gap: 1px;
}

.omo-team__map-popup-meta-label {
    font-size: 0.64rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #64748b;
}

.omo-team__map-popup-meta-value {
    color: #0f172a;
    font-size: 0.82rem;
    line-height: 1.3;
    word-break: break-word;
}

.omo-team__map-popup-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    padding: 8px 12px;
    border-radius: 10px;
    background: #0f766e;
    color: #ffffff;
    font-size: 0.8rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
}

.omo-team__map-popup-action:hover {
    background: #115e59;
}

.leaflet-popup-content-wrapper {
    border-radius: 16px;
}

.omo-team__grid {
    --omo-card-min: 220px;
    --omo-card-max: 240px;
    gap: 10px;
    margin:10px;
}

.omo-team-card {
    position: relative;
    flex-direction: column;
    min-width: 0;
    overflow: visible;
    padding: 0;
}

.omo-team-card--pending {
    opacity: 0.7;
}

.omo-team-card__banner {
    position: relative;
    display: flex;
    align-items: flex-start;
    justify-content: flex-end;
    height: 34px;
    padding: 6px 8px 0;
    background:
        radial-gradient(circle at top right, color-mix(in srgb, var(--color-primary) 30%, transparent), transparent 52%),
        linear-gradient(135deg, color-mix(in srgb, var(--color-primary) 22%, var(--color-surface-alt)), var(--color-surface-alt));
    border-bottom: 1px solid var(--color-border);
    border-top-left-radius: inherit;
    border-top-right-radius: inherit;
}

.omo-team-card__media {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    min-height: 0;
    padding: 0 14px;
    margin-top: -24px;
    margin-bottom: -2px;
    position: relative;
    z-index: 1;
}

.omo-team-card__photo,
.omo-team-card__photo-placeholder {
    width: 52px;
    height: 52px;
    border-radius: 999px;
    border: 2px solid var(--color-surface);
    background: var(--color-surface);
    box-shadow: var(--shadow-sm);
}

.omo-team-card__photo {
    object-fit: cover;
}

.omo-team-card--pending .omo-team-card__photo {
    filter: grayscale(1);
}

.omo-team-card__photo-placeholder {
    display: grid;
    place-items: center;
    gap: 2px;
    padding: 6px;
    text-align: center;
}

.omo-team-card__initials {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 999px;
    background: var(--color-primary);
    color: var(--color-text-inverse);
    font-size: 0.72rem;
    font-weight: 700;
}

.omo-team-card__photo-label {
    font-size: 0.52rem;
    line-height: 1.1;
    color: var(--color-text-light);
}

.omo-team-card__body {
    display: grid;
    gap: 10px;
    padding: 8px 14px 12px;
}

.omo-team-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
}

.omo-team-card__identity {
    min-width: 0;
}

.omo-team-card__identity h3 {
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.2;
}

.omo-team-card__identity p {
    margin: 2px 0 0;
    color: var(--color-text-light);
    font-size: 0.76rem;
    line-height: 1.25;
    word-break: break-word;
}

.omo-team-card__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 22px;
    padding: 0 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-primary) 12%, var(--color-surface));
    color: var(--color-primary);
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
}

.omo-team-card__badge--pending {
    background: rgba(100, 116, 139, 0.12);
    color: #475569;
}

.omo-team-card__menu-toggle {
    min-width: 30px;
    min-height: 30px;
    padding: 3px 7px;
    border: 1px solid rgba(255, 255, 255, 0.45);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.18);
    color: var(--color-text);
    backdrop-filter: blur(8px);
    font-weight: 700;
    letter-spacing: 0.08em;
    cursor: pointer;
}

.omo-team-card__menu-toggle:hover {
    background: rgba(255, 255, 255, 0.28);
}

.omo-team-card__menu {
    position: relative;
    z-index: 4;
}

.omo-team-card__menu.is-open {
    z-index: 30;
}

.omo-team-card__menu-panel {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    z-index: 40;
    min-width: 220px;
    padding: 6px;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: var(--color-surface, #fff);
    box-shadow: var(--shadow-md, 0 12px 24px rgba(15, 23, 42, 0.14));
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.omo-team-card__menu-panel[hidden] {
    display: none;
}

.omo-team-card__menu-item {
    width: 100%;
    padding: 9px 11px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: var(--color-text);
    text-align: left;
    cursor: pointer;
    font-size: 13px;
    line-height: 1.35;
}

.omo-team-card__menu-item:hover {
    background: var(--color-surface-alt, #f0f2f5);
}

.omo-team-card__menu-item--danger {
    color: #b91c1c;
}

.omo-team-card__menu-item--danger:hover {
    background: rgba(220, 38, 38, 0.08);
}

.omo-team-card__meta {
    display: block;
}

.omo-team-card__meta-row {
    display: grid;
    gap: 1px;
}

.omo-team-card__meta-label {
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--color-text-light);
}

.omo-team-card__meta-value {
    word-break: break-word;
    font-size: 0.8rem;
}

.omo-team-card__meta-value--muted {
    color: var(--color-text-light);
}

.omo-team-card__dates {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    padding-top: 8px;
    border-top: 1px solid var(--color-border);
}

.omo-team-card__date {
    display: grid;
    gap: 1px;
    min-width: 0;
}

.omo-team-card__date-label {
    font-size: 0.58rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--color-text-light);
}

.omo-team-card__date-value {
    font-size: 0.68rem;
    line-height: 1.2;
    word-break: break-word;
}

.omo-team-card__date-value--muted {
    color: var(--color-text-light);
}

@media (max-width: 820px) {
    .omo-team__header-action {
        width: 100%;
        justify-content: flex-start;
    }

    .omo-team__header-controls {
        width: 100%;
        justify-content: flex-start;
    }
}

@media (max-width: 560px) {
    .omo-team__header-action {
        justify-content: stretch;
    }

    .omo-team__add-member-button {
        width: 100%;
    }

    .omo-team__view-switch {
        display: flex;
        width: 100%;
        justify-self: stretch;
    }

    .omo-team__scope-toggle {
        width: 100%;
        justify-content: stretch;
    }

    .omo-team__view-button {
        flex: 1 1 calc(50% - 4px);
        text-align: center;
    }

    .omo-team__grid {
        grid-template-columns: 1fr;
    }

    .omo-team__map {
        min-height: 320px;
    }

    .omo-team__compact-list-shell {
        margin: 0;
    }

    .omo-team__compact-cell--identity {
        grid-column: auto;
    }

    .omo-team__compact-identity-grid {
        grid-template-columns: minmax(0, 1fr);
        gap: 6px;
    }
}
</style>

<script>
var omoTeamViewStorageKey = <?= json_encode('omo-team-view:' . (int)$organizationId . ':' . ($hasStructureContext ? (int)$currentHolon->getId() : 0), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var omoTeamMapEnabled = <?= $leafletMapsEnabled ? 'true' : 'false' ?>;
var omoTeamMapMembers = <?= json_encode($mapMemberPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var omoTeamInitialScope = <?= json_encode($teamScope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var omoTeamLeafletMap = null;
var omoTeamLeafletLayer = null;
var omoTeamLeafletTileState = {layer: null, theme: null};

function omoTeamEscapeHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function omoTeamNormalizeScope(scopeValue) {
    const normalizedScope = String(scopeValue || '').trim().toLowerCase();
    return normalizedScope === 'global' || normalizedScope === 'descendants'
        ? normalizedScope
        : 'contextual';
}

function omoTeamBuildScopeUrl(scopeValue) {
    const root = document.getElementById('omo-team-root');
    const organizationId = Number(root ? (root.getAttribute('data-team-oid') || 0) : 0);
    const holonId = Number(root ? (root.getAttribute('data-team-cid') || 0) : 0);
    const rootHolonId = Number(root ? (root.getAttribute('data-team-root-hid') || 0) : 0);
    const resolvedScope = omoTeamNormalizeScope(scopeValue);
    const query = [];

    if (organizationId > 0) {
        query.push('oid=' + encodeURIComponent(String(organizationId)));
    }

    if (holonId > 0 && holonId !== rootHolonId) {
        query.push('cid=' + encodeURIComponent(String(holonId)));
    }

    if (resolvedScope !== 'contextual') {
        query.push('team_scope=' + encodeURIComponent(resolvedScope));
    }

    return '/omo/api/team/index.php' + (query.length > 0 ? '?' + query.join('&') : '');
}

function omoTeamSetScopeLoadingState(targetScope, isLoading) {
    const root = document.getElementById('omo-team-root');
    if (!root) {
        return;
    }

    const resolvedScope = omoTeamNormalizeScope(targetScope);
    let activeScopeIndex = 0;

    root.classList.toggle('is-loading', Boolean(isLoading));
    root.setAttribute('data-team-scope', resolvedScope);

    root.querySelectorAll('[data-team-scope-toggle]').forEach(function (scopeButton) {
        const buttonScope = omoTeamNormalizeScope(scopeButton.getAttribute('data-team-scope-toggle') || '');
        const isActive = buttonScope === resolvedScope;

        scopeButton.disabled = Boolean(isLoading);
        scopeButton.classList.toggle('is-active', isActive);
        scopeButton.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        if (isActive) {
            activeScopeIndex = parseInt(scopeButton.getAttribute('data-omo-scope-index') || '0', 10) || 0;
        }
    });

    const scopeSwitch = root.querySelector('[data-omo-scope-switch]');
    if (scopeSwitch) {
        scopeSwitch.setAttribute('data-omo-scope-switch', resolvedScope);
        scopeSwitch.style.setProperty('--omo-scope-active-index', String(activeScopeIndex));
    }
}

window.omoToggleTeamScope = function (button, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    const root = document.getElementById('omo-team-root');
    const currentScope = omoTeamNormalizeScope(root ? root.getAttribute('data-team-scope') : omoTeamInitialScope);
    const targetScope = omoTeamNormalizeScope(button ? button.getAttribute('data-team-scope-toggle') : '');

    if (targetScope === currentScope) {
        return false;
    }

    const nextUrl = omoTeamBuildScopeUrl(targetScope);
    omoTeamSetScopeLoadingState(targetScope, true);

    if (root && typeof window.omoReplaceFetchedPanelRoot === 'function') {
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(function () {
                window.omoReplaceFetchedPanelRoot({
                    rootSelector: '#omo-team-root',
                    currentRoot: root,
                    url: nextUrl,
                    setLoadingState: function (isLoading) {
                        omoTeamSetScopeLoadingState(targetScope, isLoading);
                    }
                }).catch(function () {
                    window.location.href = nextUrl;
                });
            });
        } else {
            window.omoReplaceFetchedPanelRoot({
                rootSelector: '#omo-team-root',
                currentRoot: root,
                url: nextUrl,
                setLoadingState: function (isLoading) {
                    omoTeamSetScopeLoadingState(targetScope, isLoading);
                }
            }).catch(function () {
                window.location.href = nextUrl;
            });
        }
        return false;
    }

    window.location.href = nextUrl;
    return false;
};

function omoTeamApplyView(viewName) {
    const normalizedView = viewName === 'map' && omoTeamMapEnabled
        ? 'map'
        : (viewName === 'compact' ? 'compact' : 'cards');
    $('[data-team-view-button]').each(function () {
        const isActive = $(this).data('team-view-button') === normalizedView;
        $(this).toggleClass('is-active', isActive).attr('aria-pressed', isActive ? 'true' : 'false');
    });

    $('[data-team-view-panel]').each(function () {
        const shouldShow = $(this).data('team-view-panel') === normalizedView;
        $(this).prop('hidden', !shouldShow);
    });

    try {
        window.sessionStorage.setItem(omoTeamViewStorageKey, normalizedView);
    } catch (error) {
    }

    if (normalizedView === 'map' && omoTeamMapEnabled) {
        if (typeof window.commonWhenLeafletReady === 'function') {
            window.commonWhenLeafletReady(function () {
                omoTeamEnsureMapReady();
            });
        } else {
            omoTeamEnsureMapReady();
        }
    }
}

function omoTeamEnsureMapReady() {
    if (typeof L === 'undefined' || !Array.isArray(omoTeamMapMembers) || omoTeamMapMembers.length === 0) {
        return;
    }

    const mapElement = document.getElementById('omo-team-map');
    if (!mapElement) {
        return;
    }

    if (!omoTeamLeafletMap) {
        omoTeamLeafletMap = L.map(mapElement, {
            zoomControl: true,
            scrollWheelZoom: true
        });

        if (typeof window.commonBindLeafletTheme === 'function') {
            window.commonBindLeafletTheme(omoTeamLeafletMap, omoTeamLeafletTileState);
        }

        omoTeamLeafletLayer = L.layerGroup().addTo(omoTeamLeafletMap);
        const bounds = [];

        omoTeamMapMembers.forEach(function (member) {
            const lat = Number(member.lat);
            const lng = Number(member.long);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const popupBits = ['<div class="omo-team__map-popup">'];

            popupBits.push('<div class="omo-team__map-popup-head">');
            if (member.photoUrl) {
                popupBits.push('<img class="omo-team__map-popup-photo" src="' + omoTeamEscapeHtml(member.photoUrl) + '" alt="' + omoTeamEscapeHtml(member.displayName || ('Utilisateur ' + member.userId)) + '">');
            } else {
                popupBits.push('<div class="omo-team__map-popup-photo-placeholder">' + omoTeamEscapeHtml(member.initials || 'P') + '</div>');
            }

            popupBits.push('<div class="omo-team__map-popup-identity">');
            popupBits.push('<div class="omo-team__map-popup-name">' + omoTeamEscapeHtml(member.displayName || ('Utilisateur ' + member.userId)) + '</div>');
            if (member.secondary) {
                popupBits.push('<div class="omo-team__map-popup-secondary">' + omoTeamEscapeHtml(member.secondary) + '</div>');
            } else if (member.email) {
                popupBits.push('<div class="omo-team__map-popup-secondary">' + omoTeamEscapeHtml(member.email) + '</div>');
            }
            popupBits.push('</div>');
            popupBits.push('</div>');

            popupBits.push('<div class="omo-team__map-popup-badges">');
            if (member.isPending) {
                popupBits.push('<span class="omo-team__map-popup-badge omo-team__map-popup-badge--pending">En attente</span>');
            }
            if (member.isContextAdmin) {
                popupBits.push('<span class="omo-team__map-popup-badge omo-team__map-popup-badge--admin">Admin du contexte</span>');
            }
            if (member.isOrganizationAdmin) {
                popupBits.push('<span class="omo-team__map-popup-badge">Admin organisation</span>');
            }
            popupBits.push('</div>');

            popupBits.push('<div class="omo-team__map-popup-meta">');
            popupBits.push('<div class="omo-team__map-popup-meta-row"><div class="omo-team__map-popup-meta-label">E-mail</div><div class="omo-team__map-popup-meta-value">' + omoTeamEscapeHtml(member.email || 'Non renseigne') + '</div></div>');
            popupBits.push('<div class="omo-team__map-popup-meta-row"><div class="omo-team__map-popup-meta-label">Ajout</div><div class="omo-team__map-popup-meta-value">' + omoTeamEscapeHtml(member.joinedAtLabel || 'N/A') + '</div></div>');
            popupBits.push('<div class="omo-team__map-popup-meta-row"><div class="omo-team__map-popup-meta-label">Connexion</div><div class="omo-team__map-popup-meta-value">' + omoTeamEscapeHtml(member.lastSeenLabel || 'Jamais') + '</div></div>');
            popupBits.push('</div>');

            if (member.canViewDetail) {
                popupBits.push('<button type="button" class="omo-team__map-popup-action" data-map-popup-open-user="' + Number(member.userId) + '">Ouvrir la fiche</button>');
            }

            popupBits.push('</div>');

            const marker = L.circleMarker([lat, lng], {
                radius: member.isContextAdmin ? 9 : 7,
                color: member.isContextAdmin ? '#b45309' : '#0f766e',
                weight: 2,
                fillColor: member.isContextAdmin ? '#f59e0b' : '#14b8a6',
                fillOpacity: 0.88
            });

            marker.bindPopup(popupBits.join(''));
            if (member.canViewDetail) {
                marker.on('dblclick', function () {
                    if (typeof window.omoOpenUserContextPopup === 'function') {
                        window.omoOpenUserContextPopup(Number(member.userId));
                    }
                });
            }

            marker.addTo(omoTeamLeafletLayer);
            bounds.push([lat, lng]);
        });

        if (bounds.length === 1) {
            omoTeamLeafletMap.setView(bounds[0], 13);
        } else if (bounds.length > 1) {
            omoTeamLeafletMap.fitBounds(bounds, {padding: [28, 28]});
        } else {
            omoTeamLeafletMap.setView([46.8182, 8.2275], 7);
        }
    }

    window.setTimeout(function () {
        if (omoTeamLeafletMap) {
            omoTeamLeafletMap.invalidateSize();
        }
    }, 0);
    window.setTimeout(function () {
        if (omoTeamLeafletMap) {
            omoTeamLeafletMap.invalidateSize();
        }
    }, 250);
}

$(document)
  .off('click.omoTeamViewToggle', '[data-team-view-button]')
  .on('click.omoTeamViewToggle', '[data-team-view-button]', function () {
    omoTeamApplyView(String($(this).data('team-view-button') || 'cards'));
  });

$(function () {
    let initialView = 'cards';
    try {
        initialView = window.sessionStorage.getItem(omoTeamViewStorageKey) || 'cards';
    } catch (error) {
    }

    omoTeamApplyView(initialView);
    if (typeof window.commonWhenLeafletReady === 'function') {
        window.commonWhenLeafletReady(function () {
            if (($('[data-team-view-button].is-active').data('team-view-button') || 'cards') === 'map') {
                omoTeamEnsureMapReady();
            }
        });
    }
});

function omoCloseTeamMemberMenus() {
    $('[data-team-member-menu="1"]').each(function () {
        $(this).removeClass('is-open');
        $(this).find('[data-team-member-menu-panel="1"]').prop('hidden', true);
        $(this).find('[data-team-member-menu-toggle="1"]').attr('aria-expanded', 'false');
    });
}

$(document)
  .off('click.omoTeamMapPopupAction', '[data-map-popup-open-user]')
  .on('click.omoTeamMapPopupAction', '[data-map-popup-open-user]', function (event) {
    event.preventDefault();
    const userId = Number($(this).data('map-popup-open-user') || 0);
    if (!userId || typeof window.omoOpenUserContextPopup !== 'function') {
        return;
    }

    window.omoOpenUserContextPopup(userId);
  });

$(document)
  .off('click.omoTeamUserContext', '[data-open-user-context="1"]')
  .on('click.omoTeamUserContext', '[data-open-user-context="1"]', function (event) {
    if ($(event.target).closest('[data-team-member-menu="1"]').length) {
        return;
    }

    const userId = Number($(this).data('user-id'));

    if (typeof window.omoOpenUserContextPopup !== 'function') {
        return;
    }

    window.omoOpenUserContextPopup(userId);
  });

$(document)
  .off('keydown.omoTeamUserContext', '[data-open-user-context="1"]')
  .on('keydown.omoTeamUserContext', '[data-open-user-context="1"]', function (event) {
    if ($(event.target).closest('[data-team-member-menu="1"]').length) {
        return;
    }

    if (event.key !== 'Enter' && event.key !== ' ') {
        return;
    }

    event.preventDefault();
    $(this).trigger('click');
  });

$(document)
  .off('click.omoTeamMenuSurface', '.omo-team-card__menu')
  .on('click.omoTeamMenuSurface', '.omo-team-card__menu', function (event) {
    event.stopPropagation();
  });

$(document)
  .off('click.omoTeamMenuToggle', '[data-team-member-menu-toggle="1"]')
  .on('click.omoTeamMenuToggle', '[data-team-member-menu-toggle="1"]', function (event) {
    event.preventDefault();
    event.stopPropagation();

    const menu = $(this).closest('[data-team-member-menu="1"]');
    const willOpen = !menu.hasClass('is-open');
    omoCloseTeamMemberMenus();

    if (!willOpen) {
        return;
    }

    menu.addClass('is-open');
    menu.find('[data-team-member-menu-panel="1"]').prop('hidden', false);
    menu.find('[data-team-member-menu-toggle="1"]').attr('aria-expanded', 'true');
  });

$(document)
  .off('click.omoTeamMenuOutside')
  .on('click.omoTeamMenuOutside', function (event) {
    if ($(event.target).closest('[data-team-member-menu="1"]').length) {
        return;
    }

    omoCloseTeamMemberMenus();
  });

$(document)
  .off('click.omoTeamOpenMemberPopup', '[data-team-open-member-popup="1"]')
  .on('click.omoTeamOpenMemberPopup', '[data-team-open-member-popup="1"]', function (event) {
    event.preventDefault();

    const holonId = Number($(this).data('hid') || 0);

    if (!holonId || typeof window.commonTopbarOpenModal !== 'function') {
        return;
    }

    window.commonTopbarOpenModal(
        'Ajouter un membre',
        'api/holons/member_popup.php?hid=' + holonId,
        'fetch'
    );
  });

$(document)
  .off('click.omoTeamMemberAction', '[data-member-action]')
  .on('click.omoTeamMemberAction', '[data-member-action]', function (event) {
    event.preventDefault();
    event.stopPropagation();

    const button = $(this);
    const card = button.closest('.omo-team-card');
    const action = String(button.data('member-action') || '');
    const userId = Number(button.data('user-id') || card.data('user-id') || 0);
    const organizationId = <?= (int)$organizationId ?>;
    const currentHolonId = <?= $hasStructureContext ? (int)$currentHolon->getId() : 0 ?>;
    const rootHolonId = <?= $hasStructureContext ? (int)$rootHolon->getId() : 0 ?>;
    const teamRoot = document.getElementById('omo-team-root');
    const currentTeamScope = omoTeamNormalizeScope(teamRoot ? teamRoot.getAttribute('data-team-scope') : omoTeamInitialScope);
    const contextLabel = <?= json_encode($currentHolonTemplateLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const displayName = $.trim(card.find('.omo-team-card__identity h3').first().text()) || 'ce membre';
    let confirmationMessage = '';

    if (!action || !userId) {
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

    button.prop('disabled', true);

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
        button.prop('disabled', false);

        if (!result.ok || !result.data || !result.data.status) {
            window.alert(result.data && result.data.message ? result.data.message : 'Impossible de mettre à jour ce membre.');
            return;
        }

        omoCloseTeamMemberMenus();

        if (typeof refreshDrawer === 'function') {
            let drawerUrl = '/omo/api/team/index.php?oid=' + organizationId;
            if (currentHolonId > 0 && currentHolonId !== rootHolonId) {
                drawerUrl += '&cid=' + currentHolonId;
            }
            if (currentTeamScope !== 'contextual') {
                drawerUrl += '&team_scope=' + encodeURIComponent(currentTeamScope);
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

        if (rootHolonId > 0 && typeof window.omoReloadStructureAndFocus === 'function') {
            window.omoReloadStructureAndFocus(currentHolonId > 0 && currentHolonId !== rootHolonId ? currentHolonId : null, {
                quickZoom: true
            });
        }
      })
      .catch(function () {
        button.prop('disabled', false);
        window.alert('Impossible de mettre à jour ce membre pour le moment.');
      });
  });
</script>
