<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/leaflet_helper.php';
require_once dirname(__DIR__, 3) . '/common/team/translations.php';

use dbObject\ArrayUserOrganization;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\User;
use dbObject\UserOrganization;

function omoTeamHolonTypeLabel(Holon $holon, ?array $lang = null, ?array $sourceLang = null)
{
    return omoTeamHolonTypeLabelByTypeId((int)$holon->get('IDtypeholon'), $lang, $sourceLang);
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
$sourceLang = omoTeamSourceLang();
$lang = omoTeamLoadTranslationBundle();
$teamLocale = omoGetTranslationLocale();

if ($organizationId <= 0) {
    http_response_code(400);
    ?>
    <div class="omo-team omo-panel-view">
        <div class="omo-panel-view__body">
            <div class="omo-panel-view__body_content">
                <div class="omo-team__empty omo-empty-state"><?= omoApiEscape(omoTeamT('team.error.invalid_organization', [], $lang, $sourceLang)) ?></div>
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
                <div class="omo-team__empty omo-empty-state"><?= omoApiEscape(omoTeamT('team.popup.organization_not_found', [], $lang, $sourceLang)) ?></div>
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
                <div class="omo-team__empty omo-empty-state"><?= omoApiEscape(omoTeamT('team.error.people_forbidden', [], $lang, $sourceLang)) ?></div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

$organizationLexicon = $organization->getLexicon();
$adminLabel = trim((string)($organizationLexicon['admin']['label'] ?? '')) ?: 'Admin';
$adminLabelLower = function_exists('mb_strtolower')
	? mb_strtolower($adminLabel, 'UTF-8')
	: strtolower($adminLabel);

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
                <div class="omo-team__empty omo-empty-state"><?= omoApiEscape(omoTeamT('team.popup.organization_forbidden', [], $lang, $sourceLang)) ?></div>
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
                    <div class="omo-team__empty omo-empty-state"><?= omoApiEscape(omoTeamT('team.popup.context_not_found', [], $lang, $sourceLang)) ?></div>
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
                    <div class="omo-team__empty omo-empty-state"><?= omoApiEscape(omoTeamT('team.popup.context_forbidden', [], $lang, $sourceLang)) ?></div>
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
$teamQuickSearch = trim((string)($_GET['team_query'] ?? ''));
$teamScopeActiveIndex = omoApiResolveContextScopeIndex($teamScope, $availableTeamScopes);
$teamScopeLabels = array(
    'contextual' => omoTeamT('team.scope.contextual', [], $lang, $sourceLang),
    'children' => omoTeamT('team.scope.children', [], $lang, $sourceLang),
    'descendants' => omoTeamT('team.scope.descendants', [], $lang, $sourceLang),
);

$rawMemberCards = array();
$contextAdminUserIds = array();
$directContextMemberUserIds = array();

if ($hasStructureContext) {
    $directContextMemberUserIds = array_fill_keys($currentHolon->getDirectMemberUserIds($organizationId), true);

    if ($teamScope === 'contextual') {
        $rawMemberCards = $currentHolon->getAssociatedMemberCards(array(
            'organizationId' => $organizationId,
        ));
    } elseif ($teamScope === 'children') {
        $memberCardsByUserId = array();
        foreach ($currentHolon->getAssociatedMemberCards(array(
            'organizationId' => $organizationId,
            'includeDescendants' => false,
        )) as $currentHolonMemberCard) {
            $memberUserId = (int)($currentHolonMemberCard['userId'] ?? 0);
            if ($memberUserId > 0) {
                $memberCardsByUserId[$memberUserId] = $currentHolonMemberCard;
            }
        }
        foreach ($currentHolon->getChildren() as $directChildHolon) {
            if (!omoApiIsStructuralScopeHolon($directChildHolon, $currentHolon)) {
                continue;
            }
            foreach ($directChildHolon->getAssociatedMemberCards(array(
                'organizationId' => $organizationId,
                'includeDescendants' => false,
            )) as $directChildMemberCard) {
                $memberUserId = (int)($directChildMemberCard['userId'] ?? 0);
                if ($memberUserId > 0) {
                    $memberCardsByUserId[$memberUserId] = $directChildMemberCard;
                }
            }
        }
        $rawMemberCards = array_values($memberCardsByUserId);
    } else {
        $rawMemberCards = $currentHolon->getAssociatedMemberCards(array(
            'organizationId' => $organizationId,
            'includeDescendants' => true,
        ));
    }

    $contextAdminUserIds = array_fill_keys($currentHolon->getDirectContextAdminUserIds($organizationId), true);
} else {
    $memberships = new ArrayUserOrganization();
    $memberships->loadVisibleForOrganization($organizationId, true);

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

$intlLocale = str_replace('-', '_', trim((string)$teamLocale));
if ($intlLocale === '') {
    $intlLocale = 'fr';
}
$formatter = class_exists('IntlDateFormatter')
    ? new IntlDateFormatter($intlLocale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'dd MMMM yyyy')
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

$formatLastSeenLabel = static function ($organizationDate, $globalDate) use ($formatDate, $lang, $sourceLang): string {
    $organizationLabel = $organizationDate instanceof DateTimeInterface ? $formatDate($organizationDate) : '';
    $globalLabel = $globalDate instanceof DateTimeInterface ? $formatDate($globalDate) : '';

    if ($organizationLabel !== '') {
        if ($globalLabel !== '') {
            return omoTeamT(
                'team.member.last_seen_global',
                ['organization' => $organizationLabel, 'global' => $globalLabel],
                $lang,
                $sourceLang
            );
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
    $hasPendingInvitation = !empty($rawCard['hasPendingInvitation']);
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
    $resolvedDisplayName = $displayName !== ''
        ? $displayName
        : omoTeamT('team.member.user_fallback', ['userId' => (string)$userId], $lang, $sourceLang);
    $memberSearchText = trim(implode(' ', array_filter(array(
        $resolvedDisplayName,
        $firstName,
        $lastName,
        $phone,
        $email,
        $username,
        $secondary,
        $hasPendingInvitation
            ? omoTeamT('team.member.invitation_pending', [], $lang, $sourceLang)
            : ($isPending ? omoTeamT('team.member.to_invite', [], $lang, $sourceLang) : ''),
        $isContextAdmin ? omoTeamT('team.member.admin_context', ['adminLabel' => $adminLabel], $lang, $sourceLang) : '',
        $isOrganizationAdmin ? omoTeamT('team.member.admin_organization', ['adminLabel' => $adminLabel], $lang, $sourceLang) : '',
    ), static fn ($value): bool => trim((string)$value) !== '')));

    $memberCards[] = array(
        'userId' => $userId,
        'displayName' => $resolvedDisplayName,
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
        'hasPendingInvitation' => $hasPendingInvitation,
        'joinedAtLabel' => $effectiveJoinedAt instanceof DateTimeInterface ? $formatDate($effectiveJoinedAt) : '',
        'lastSeenLabel' => $formatLastSeenLabel($organizationLastSeen, $globalLastSeen),
        'organizationLastSeenLabel' => $organizationLastSeen instanceof DateTimeInterface ? $formatDate($organizationLastSeen) : '',
        'siteLastSeenLabel' => $globalLastSeen instanceof DateTimeInterface ? $formatDate($globalLastSeen) : '',
        'createdAtLabel' => $globalJoinedAt instanceof DateTimeInterface ? $formatDate($globalJoinedAt) : '',
        'canViewDetail' => $canViewUserDetail,
        'latlong' => $latlong,
        'searchText' => $memberSearchText,
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

$currentHolonTypeLabel = $hasStructureContext
    ? omoTeamHolonTypeLabel($currentHolon, $lang, $sourceLang)
    : omoTeamT('team.holon_type.organization', [], $lang, $sourceLang);
$currentHolonTemplateLabel = $hasStructureContext
    ? trim((string)$currentHolon->getTemplateLabel(true))
    : omoTeamT('team.holon_type.organization', [], $lang, $sourceLang);
if ($currentHolonTemplateLabel === '') {
    $currentHolonTemplateLabel = $currentHolonTypeLabel;
}
$teamEmptyMessage = omoTeamT('team.empty.contextual', ['context_type' => $currentHolonTypeLabel], $lang, $sourceLang);
$teamMapEmptyMessage = omoTeamT('team.map.empty.contextual', [], $lang, $sourceLang);

if ($teamScope === 'children') {
    $teamEmptyMessage = omoTeamT('team.empty.children', [], $lang, $sourceLang);
    $teamMapEmptyMessage = omoTeamT('team.map.empty.children', [], $lang, $sourceLang);
} elseif ($teamScope === 'descendants') {
    $teamEmptyMessage = omoTeamT('team.empty.descendants', [], $lang, $sourceLang);
    $teamMapEmptyMessage = omoTeamT('team.map.empty.descendants', [], $lang, $sourceLang);
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
        'hasPendingInvitation' => !empty($card['hasPendingInvitation']),
        'canViewDetail' => !empty($card['canViewDetail']),
        'searchText' => (string)($card['searchText'] ?? ''),
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
<link rel="stylesheet" href="/common/view-filter/view-filter.css?v=20260801-view-preferences-actions-height">
<div
    class="omo-team omo-panel-view"
    id="omo-team-root"
    data-team-oid="<?= (int)$organizationId ?>"
    data-team-cid="<?= $hasStructureContext ? (int)$currentHolon->getId() : 0 ?>"
    data-team-root-hid="<?= $hasStructureContext ? (int)$rootHolon->getId() : 0 ?>"
    data-team-scope="<?= omoApiEscape($teamScope) ?>"
    data-team-view="cards"
    data-team-query="<?= omoApiEscape($teamQuickSearch) ?>"
    data-team-preferences-pending="1"
    aria-busy="true"
>
    <div class="omo-team__hero omo-panel-view__header omo-panel-view__header--stacked">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <span class="omo-panel-view__app-icon omo-team__app-icon" aria-hidden="true">
                    <img src="images/tools/team.png" alt="">
                </span>
                <div class="omo-panel-view__header-copy">
                    <div class="omo-team__title-row generic-title-row">
                        <h2 class="omo-panel-view__title"><?= omoApiEscape(omoTeamT('team.title', [], $lang, $sourceLang)) ?></h2>
                        <span class="omo-panel-view__count"><?= omoApiEscape(count($memberCards)) ?></span>
                    </div>
                </div>
            </div>
            <?php if ($canAddCurrentHolonMembers): ?>
                <div class="omo-team__header-action" data-omo-header-actions>
                    <button
                        type="button"
                        class="generic-action-button generic-action-button--main omo-team__add-member-button"
                        data-team-open-member-popup="1"
                        data-hid="<?= (int)$currentHolon->getId() ?>"
                    ><?= omoApiEscape(omoTeamT('team.action.add_member', [], $lang, $sourceLang)) ?></button>
                </div>
            <?php endif; ?>
        </div>
        <div class="omo-panel-view__header-secondary omo-team__header-secondary">
            <div class="omo-team__filter-toolbar omo-view-filter" data-team-filter-control role="group" aria-label="<?= omoApiEscape(omoTeamT('team.filters.aria', [], $lang, $sourceLang)) ?>">
                <div class="omo-team__filter-input omo-view-filter__input">
                    <div class="omo-team__filter-chips omo-view-filter__chips">
                        <button type="button" class="omo-team__filter-chip omo-view-filter__chip" data-team-filter-toggle data-team-filter-scope-chip aria-expanded="false" aria-controls="omo-team-filter-panel"><?= omoApiEscape((string)($teamScopeLabels[$teamScope] ?? $teamScope)) ?></button>
                        <button type="button" class="omo-team__filter-chip omo-view-filter__chip" data-team-filter-toggle data-team-filter-view-chip aria-expanded="false" aria-controls="omo-team-filter-panel"><?= omoApiEscape(omoTeamT('team.view.cards', [], $lang, $sourceLang)) ?></button>
                    </div>
                    <label class="omo-team__filter-search omo-view-filter__search">
                        <input type="search" class="generic-form-control" data-team-quick-search value="<?= omoApiEscape($teamQuickSearch) ?>" placeholder="<?= omoApiEscape(omoTeamT('team.search.placeholder', [], $lang, $sourceLang)) ?>" aria-label="<?= omoApiEscape(omoTeamT('team.search.aria', [], $lang, $sourceLang)) ?>" autocomplete="off">
                    </label>
                </div>
                <section id="omo-team-filter-panel" class="omo-team__filter-panel omo-view-filter__panel generic-soft-panel generic-soft-panel--stack" data-team-filter-panel hidden>
                    <div class="omo-team__filter-panel-grid omo-view-filter__panel-grid">
                        <div class="omo-team__filter-group omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoTeamT('team.filters.scope', [], $lang, $sourceLang)) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoTeamT('team.scope.members_aria', [], $lang, $sourceLang)) ?>">
                                <?php foreach ($availableTeamScopes as $scopeKey): ?>
                                    <button type="button" class="omo-segmented__button<?= $teamScope === $scopeKey ? ' is-active' : '' ?>" data-team-filter-scope="<?= omoApiEscape($scopeKey) ?>" aria-pressed="<?= $teamScope === $scopeKey ? 'true' : 'false' ?>"><?= omoApiEscape((string)($teamScopeLabels[$scopeKey] ?? $scopeKey)) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="omo-team__filter-group omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoTeamT('team.filters.view', [], $lang, $sourceLang)) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoTeamT('team.view.choice_aria', [], $lang, $sourceLang)) ?>">
                                <button type="button" class="omo-segmented__button is-active" data-team-filter-view="cards" aria-pressed="true"><?= omoApiEscape(omoTeamT('team.view.cards', [], $lang, $sourceLang)) ?></button>
                                <button type="button" class="omo-segmented__button" data-team-filter-view="compact" aria-pressed="false"><?= omoApiEscape(omoTeamT('team.view.compact', [], $lang, $sourceLang)) ?></button>
                                <?php if ($leafletMapsEnabled): ?>
                                <button type="button" class="omo-segmented__button" data-team-filter-view="map" aria-pressed="false"><?= omoApiEscape(omoTeamT('team.view.map', [], $lang, $sourceLang)) ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="omo-team__filter-actions omo-view-filter__actions">
                        <button type="button" class="generic-action-button generic-action-button--main" data-team-filter-apply><?= omoApiEscape(omoTeamT('team.filters.apply', [], $lang, $sourceLang)) ?></button>
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-team-filter-save><?= omoApiEscape(omoTeamT('team.filters.save_view', [], $lang, $sourceLang)) ?></button>
                        <div class="generic-menu omo-view-filter__actions-more" data-team-filter-more-menu>
                            <button type="button" class="generic-menu-toggle" data-team-filter-more-toggle aria-expanded="false" aria-label="<?= omoApiEscape(omoTeamT('team.filters.more_actions', [], $lang, $sourceLang)) ?>">&#8942;</button>
                            <div class="generic-menu-panel" data-team-filter-more-panel role="menu" hidden>
                                <button type="button" class="generic-menu-item" data-team-filter-more-action="apply-everywhere" role="menuitem"><?= omoApiEscape(omoTeamT('team.filters.apply_everywhere', [], $lang, $sourceLang)) ?></button>
                                <button type="button" class="generic-menu-item" data-team-filter-more-action="set-default" role="menuitem"><?= omoApiEscape(omoTeamT('team.filters.set_default', [], $lang, $sourceLang)) ?></button>
                                <button type="button" class="generic-menu-item" data-team-filter-more-action="restore-default" role="menuitem"><?= omoApiEscape(omoTeamT('team.filters.restore_default', [], $lang, $sourceLang)) ?></button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content">
        <section class="omo-team__view-panel" data-team-view-panel="cards">
        <?php if (count($memberCards) === 0): ?>
            <div class="omo-team__empty omo-empty-state" data-team-default-empty>
                <?= omoApiEscape($teamEmptyMessage) ?>
            </div>
        <?php else: ?>
            <div class="omo-team__grid omo-card-grid omo-card-grid--fixed" data-team-items-container="cards">
                <?php foreach ($memberCards as $card): ?>
                    <article
                        class="omo-team-card omo-card<?= $card['canViewDetail'] ? ' omo-card--interactive' : '' ?><?= $card['isPending'] ? ' omo-team-card--pending' : '' ?>"
                        <?php if ($card['canViewDetail']): ?>
                        data-open-user-context="1"
                        <?php endif; ?>
                        data-user-id="<?= (int)$card['userId'] ?>"
                        data-team-member-item
                        data-team-member-search="<?= omoApiEscape((string)$card['searchText']) ?>"
                        data-context-admin="<?= $card['isContextAdmin'] ? '1' : '0' ?>"
                        data-member-pending="<?= $card['isPending'] ? '1' : '0' ?>"
                        <?php if ($card['canViewDetail']): ?>
                        tabindex="0"
                        role="button"
                        aria-label="<?= omoApiEscape(omoTeamT('team.member.open_contextual_profile', ['name' => (string)$card['displayName']], $lang, $sourceLang)) ?>"
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
                                        aria-label="<?= omoApiEscape(omoTeamT('team.member.actions_for', ['name' => (string)$card['displayName']], $lang, $sourceLang)) ?>"
                                    >...</button>
                                    <div class="omo-team-card__menu-panel" data-team-member-menu-panel="1" hidden>
                                        <?php if ($canRemoveCurrentHolonMembers && $card['hasPendingInvitation']): ?>
                                            <button
                                                type="button"
                                                class="omo-team-card__menu-item omo-team-card__menu-item--danger"
                                                data-member-action="cancel_invitation"
                                                data-user-id="<?= (int)$card['userId'] ?>"
                                            ><?= omoApiEscape(omoTeamT('team.action.cancel_invitation', [], $lang, $sourceLang)) ?></button>
                                        <?php endif; ?>
                                        <?php if ($canRemoveCurrentHolonMembers && !$card['hasPendingInvitation']): ?>
                                            <button
                                                type="button"
                                                class="omo-team-card__menu-item omo-team-card__menu-item--danger"
                                                data-member-action="remove"
                                                data-user-id="<?= (int)$card['userId'] ?>"
                                            ><?= omoApiEscape(omoTeamT('team.action.remove_from_context', ['context' => (string)$currentHolonTemplateLabel], $lang, $sourceLang)) ?></button>
                                        <?php endif; ?>
                                        <?php if ($canGrantCurrentHolonAdmin && !$card['isPending']): ?>
                                            <button
                                                type="button"
                                                class="omo-team-card__menu-item"
                                                data-member-action="<?= $card['isContextAdmin'] ? 'revoke_admin' : 'grant_admin' ?>"
                                                data-user-id="<?= (int)$card['userId'] ?>"
                                            ><?= omoApiEscape($card['isContextAdmin']
                                                ? omoTeamT('team.action.revoke_context_admin', ['context' => (string)$currentHolonTemplateLabel, 'adminLabel' => $adminLabelLower], $lang, $sourceLang)
                                                : omoTeamT('team.action.grant_context_admin', ['context' => (string)$currentHolonTemplateLabel, 'adminLabel' => $adminLabelLower], $lang, $sourceLang)) ?></button>
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

                                <?php if ($card['hasPendingInvitation']): ?>
                                    <span class="omo-team-card__badge omo-team-card__badge--pending"><?= omoApiEscape(omoTeamT('team.member.invitation_pending', [], $lang, $sourceLang)) ?></span>
                                <?php elseif ($card['isPending']): ?>
                                    <span class="omo-team-card__badge omo-team-card__badge--pending"><?= omoApiEscape(omoTeamT('team.member.to_invite', [], $lang, $sourceLang)) ?></span>
                                <?php elseif ($card['isContextAdmin']): ?>
                                    <span class="omo-team-card__badge"><?= omoApiEscape(omoTeamT('team.member.admin_short', ['adminLabel' => $adminLabel], $lang, $sourceLang)) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="omo-team-card__meta">
                                <div class="omo-team-card__meta-row">
                                    <span class="omo-team-card__meta-label generic-meta-label generic-meta-label--compact"><?= omoApiEscape(omoTeamT('team.member.email', [], $lang, $sourceLang)) ?></span>
                                    <span class="omo-team-card__meta-value generic-meta-value generic-meta-value--compact<?= $card['email'] === '' ? ' omo-team-card__meta-value--muted' : '' ?>">
                                        <?= omoApiEscape($card['email'] !== '' ? $card['email'] : omoTeamT('team.member.not_provided', [], $lang, $sourceLang)) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="omo-team-card__dates">
                                <div class="omo-team-card__date">
                                    <span class="omo-team-card__date-label"><?= omoApiEscape(omoTeamT('team.member.organization_connection', [], $lang, $sourceLang)) ?></span>
                                    <span class="omo-team-card__date-value<?= $card['organizationLastSeenLabel'] === '' ? ' omo-team-card__date-value--muted' : '' ?>">
                                        <?= omoApiEscape($card['organizationLastSeenLabel'] !== '' ? $card['organizationLastSeenLabel'] : omoTeamT('team.member.never', [], $lang, $sourceLang)) ?>
                                    </span>
                                </div>

                                <div class="omo-team-card__date">
                                    <span class="omo-team-card__date-label"><?= omoApiEscape(omoTeamT('team.member.site_connection', [], $lang, $sourceLang)) ?></span>
                                    <span class="omo-team-card__date-value<?= $card['siteLastSeenLabel'] === '' ? ' omo-team-card__date-value--muted' : '' ?>">
                                        <?= omoApiEscape($card['siteLastSeenLabel'] !== '' ? $card['siteLastSeenLabel'] : omoTeamT('team.member.never', [], $lang, $sourceLang)) ?>
                                    </span>
                                </div>

                                <div class="omo-team-card__date">
                                    <span class="omo-team-card__date-label"><?= omoApiEscape(omoTeamT('team.member.created', [], $lang, $sourceLang)) ?></span>
                                    <span class="omo-team-card__date-value<?= $card['createdAtLabel'] === '' ? ' omo-team-card__date-value--muted' : '' ?>">
                                        <?= omoApiEscape($card['createdAtLabel'] !== '' ? $card['createdAtLabel'] : 'N/A') ?>
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
            <div class="omo-team__empty omo-empty-state" data-team-default-empty>
                <?= omoApiEscape($teamEmptyMessage) ?>
            </div>
        <?php else: ?>
            <div class="omo-team__compact-list-shell" data-team-items-container="compact">
                <div class="omo-team__compact-list generic-file-list__table">
                    <div class="omo-team__compact-list-header generic-file-list__header">
                        <div class="omo-team__compact-list-header-cell generic-file-list__header-cell omo-team__compact-list-header-cell--name"><?= omoApiEscape(omoTeamT('team.column.name', [], $lang, $sourceLang)) ?></div>
                        <div class="omo-team__compact-list-header-cell generic-file-list__header-cell omo-team__compact-list-header-cell--firstname"><?= omoApiEscape(omoTeamT('team.column.first_name', [], $lang, $sourceLang)) ?></div>
                        <div class="omo-team__compact-list-header-cell generic-file-list__header-cell omo-team__compact-list-header-cell--phone"><?= omoApiEscape(omoTeamT('team.column.phone', [], $lang, $sourceLang)) ?></div>
                        <div class="omo-team__compact-list-header-cell generic-file-list__header-cell omo-team__compact-list-header-cell--email"><?= omoApiEscape(omoTeamT('team.member.email', [], $lang, $sourceLang)) ?></div>
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

                        if ($card['hasPendingInvitation']) {
                            $compactPrivilegeLabels[] = array(
                                    'label' => omoTeamT('team.member.invitation_pending', [], $lang, $sourceLang),
                                'className' => 'omo-team__compact-badge omo-team__compact-badge--pending',
                            );
                        } elseif ($card['isPending']) {
                            $compactPrivilegeLabels[] = array(
                                'label' => omoTeamT('team.member.to_invite', [], $lang, $sourceLang),
                                'className' => 'omo-team__compact-badge omo-team__compact-badge--pending',
                            );
                        } else {
                            if ($card['isContextAdmin']) {
                                $compactPrivilegeLabels[] = array(
                                    'label' => omoTeamT('team.member.admin_context', ['adminLabel' => $adminLabel], $lang, $sourceLang),
                                    'className' => 'omo-team__compact-badge',
                                );
                            }

                            if ($card['isOrganizationAdmin']) {
                                $compactPrivilegeLabels[] = array(
                                    'label' => omoTeamT('team.member.admin_organization', ['adminLabel' => $adminLabel], $lang, $sourceLang),
                                    'className' => 'omo-team__compact-badge omo-team__compact-badge--organization',
                                );
                            }
                        }
                        ?>
                        <article class="omo-team__compact-item-shell generic-file-list__item-shell" data-team-member-item data-team-member-search="<?= omoApiEscape((string)$card['searchText']) ?>">
                            <div
                                class="omo-team__compact-row generic-file-list__row<?= $card['canViewDetail'] ? ' omo-team__compact-row--interactive' : '' ?><?= $card['isPending'] ? ' omo-team__compact-row--pending' : '' ?>"
                                <?php if ($card['canViewDetail']): ?>
                                data-open-user-context="1"
                                tabindex="0"
                                role="button"
                                aria-label="<?= omoApiEscape(omoTeamT('team.member.open_contextual_profile', ['name' => (string)$card['displayName']], $lang, $sourceLang)) ?>"
                                <?php endif; ?>
                                data-user-id="<?= (int)$card['userId'] ?>"
                            >
                                <div class="omo-team__compact-cell omo-team__compact-cell--identity generic-file-list__cell generic-file-list__cell--name" data-label="<?= omoApiEscape(omoTeamT('team.column.identity', [], $lang, $sourceLang)) ?>">
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
                                <div class="omo-team__compact-cell generic-file-list__cell" data-label="<?= omoApiEscape(omoTeamT('team.column.phone', [], $lang, $sourceLang)) ?>">
                                    <span class="<?= $compactPhone === '' ? 'omo-team__compact-placeholder' : '' ?>"><?= omoApiEscape($compactPhone !== '' ? $compactPhone : '-') ?></span>
                                </div>
                                <div class="omo-team__compact-cell generic-file-list__cell" data-label="<?= omoApiEscape(omoTeamT('team.member.email', [], $lang, $sourceLang)) ?>">
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
                <div class="omo-team__empty omo-empty-state" data-team-default-empty>
                    <?= omoApiEscape($teamMapEmptyMessage) ?>
                </div>
            <?php else: ?>
                <div class="omo-team__map-shell">
                    <div class="omo-team__map-summary" data-team-map-summary>
                        <?= omoApiEscape(omoTeamT('team.map.summary', ['count' => (string)count($mapMembers)], $lang, $sourceLang)) ?>
                    </div>
                    <div id="omo-team-map" class="omo-team__map" data-team-map="1"></div>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
        <div class="omo-team__search-empty omo-empty-state" data-team-search-empty hidden><?= omoApiEscape(omoTeamT('team.search.empty', [], $lang, $sourceLang)) ?></div>
        </div>
    </div>
</div>

<style>
.omo-team__app-icon {
    --omo-panel-view-app-icon-accent: #0f766e;
}

.omo-team__header-secondary {
    align-items: end;
}

.omo-team-card[hidden],
.omo-team__compact-item-shell[hidden],
.omo-team__grid[hidden],
.omo-team__compact-list-shell[hidden],
.omo-team__map-shell[hidden],
.omo-team__empty[hidden],
.omo-team__search-empty[hidden] {
    display: none !important;
}

.omo-team__search-empty {
    margin: 16px 0;
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
    border-radius: var(--radius-md);
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
    border-radius: var(--radius-md);
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
    border-radius: var(--radius-md);
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
    text-align: center;
}

.omo-team-card__initials {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    border-radius: 999px;
    background: var(--color-primary);
    color: var(--color-text-inverse);
    font-size: 1rem;
    font-weight: 700;
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
    border-radius: var(--radius-md);
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
    border-radius: var(--radius-md);
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

.omo-team-card__meta-value {
    word-break: break-word;
}

.omo-team-card__meta-value--muted {
    color: var(--color-text-light);
}

.omo-team-card__dates {
    display: grid;
    gap: 7px;
    padding-top: 8px;
    border-top: 1px solid var(--color-border);
}

.omo-team-card__date {
    display: grid;
    grid-template-columns: minmax(0, 0.9fr) minmax(0, 0.7fr);
    align-items: baseline;
    gap: 8px;
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

}

@media (max-width: 560px) {
    .omo-team__header-action {
        justify-content: stretch;
    }

    .omo-team__add-member-button {
        width: 100%;
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

<?php
$teamJsTranslations = [
    'userFallback' => omoTeamT('team.member.user_fallback', ['userId' => '{userId}'], $lang, $sourceLang),
    'pending' => omoTeamT('team.member.pending', [], $lang, $sourceLang),
    'adminContext' => omoTeamT('team.member.admin_context', ['adminLabel' => $adminLabel], $lang, $sourceLang),
    'adminOrganization' => omoTeamT('team.member.admin_organization', ['adminLabel' => $adminLabel], $lang, $sourceLang),
    'email' => omoTeamT('team.member.email', [], $lang, $sourceLang),
    'notProvided' => omoTeamT('team.member.not_provided', [], $lang, $sourceLang),
    'added' => omoTeamT('team.member.added', [], $lang, $sourceLang),
    'lastConnection' => omoTeamT('team.member.last_connection', [], $lang, $sourceLang),
    'never' => omoTeamT('team.member.never', [], $lang, $sourceLang),
    'openProfile' => omoTeamT('team.map.open_profile', [], $lang, $sourceLang),
    'addMemberTitle' => omoTeamT('team.action.add_member', [], $lang, $sourceLang),
    'thisMember' => omoTeamT('team.member.this_member', [], $lang, $sourceLang),
    'confirmCancelInvitation' => omoTeamT('team.confirm.cancel_invitation', ['name' => '{name}'], $lang, $sourceLang),
    'confirmRemove' => omoTeamT('team.confirm.remove', ['name' => '{name}', 'context' => '{context}'], $lang, $sourceLang),
    'confirmGrantAdmin' => omoTeamT('team.confirm.grant_context_admin', ['name' => '{name}', 'context' => '{context}', 'adminLabel' => $adminLabelLower], $lang, $sourceLang),
    'confirmRevokeAdmin' => omoTeamT('team.confirm.revoke_context_admin', ['name' => '{name}', 'context' => '{context}', 'adminLabel' => $adminLabelLower], $lang, $sourceLang),
    'updateFailed' => omoTeamT('team.message.update_failed', [], $lang, $sourceLang),
    'updateFailedLater' => omoTeamT('team.message.update_failed_later', [], $lang, $sourceLang),
    'mapSummaryOne' => omoTeamT('team.map.summary_one', [], $lang, $sourceLang),
    'mapSummaryOther' => omoTeamT('team.map.summary_other', ['count' => '{count}'], $lang, $sourceLang),
];
?>
<script>
var omoTeamSavedViewsStorageKey = 'omo.team.saved-views.v2';
var omoTeamLegacySavedViewsStorageKey = 'omo.team.saved-views.v1';
var omoTeamSessionViewsStorageKey = 'omo.team.session-views.v1';
var omoTeamMapEnabled = <?= $leafletMapsEnabled ? 'true' : 'false' ?>;
var omoTeamMapMembers = <?= json_encode($mapMemberPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var omoTeamInitialScope = <?= json_encode($teamScope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var omoTeamText = <?= json_encode($teamJsTranslations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var omoTeamLeafletMap = null;
var omoTeamLeafletLayer = null;
var omoTeamLeafletTileState = {layer: null, theme: null};
var omoTeamCurrentView = 'cards';
var omoTeamCurrentSearch = '';
var omoTeamPendingFilters = null;
var omoTeamFilterPanelOpen = false;

function omoTeamEscapeHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function omoTeamFormatText(template, variables) {
    let text = String(template == null ? '' : template);
    if (!variables || typeof variables !== 'object') {
        return text;
    }

    Object.keys(variables).forEach(function (key) {
        const value = variables[key] == null ? '' : String(variables[key]);
        text = text.split('{' + key + '}').join(value);
    });

    return text;
}

function omoTeamNormalizeScope(scopeValue) {
    const normalizedScope = String(scopeValue || '').trim().toLowerCase();
    if (normalizedScope === 'global') {
        return 'descendants';
    }
    return normalizedScope === 'children' || normalizedScope === 'descendants' ? normalizedScope : 'contextual';
}

function omoTeamNormalizeView(viewValue) {
    const normalizedView = String(viewValue || '').trim().toLowerCase();
    if (normalizedView === 'map' && omoTeamMapEnabled) {
        return 'map';
    }
    return normalizedView === 'compact' ? 'compact' : 'cards';
}

function omoTeamGetContextKey() {
    const root = document.getElementById('omo-team-root');
    return String(root ? (root.getAttribute('data-team-oid') || '0') : '0')
        + ':' + String(root ? (root.getAttribute('data-team-cid') || '0') : '0');
}

function omoTeamCreateViewPreferences(filters) {
    return {
        scope: omoTeamNormalizeScope(filters && filters.scope),
        view: omoTeamNormalizeView(filters && filters.view)
    };
}

function omoTeamGetSavedViewsStore() {
    try {
        const storedValue = window.localStorage.getItem(omoTeamSavedViewsStorageKey);
        const savedViews = storedValue ? JSON.parse(storedValue) : null;
        if (savedViews && typeof savedViews === 'object' && savedViews.contexts && typeof savedViews.contexts === 'object') {
            return {
                defaultView: savedViews.defaultView && typeof savedViews.defaultView === 'object'
                    ? savedViews.defaultView
                    : null,
                contexts: savedViews.contexts
            };
        }

        const legacyValue = window.localStorage.getItem(omoTeamLegacySavedViewsStorageKey);
        const legacyViews = legacyValue ? JSON.parse(legacyValue) : null;
        return {
            defaultView: null,
            contexts: legacyViews && typeof legacyViews === 'object' ? legacyViews : {}
        };
    } catch (error) {
        return {defaultView: null, contexts: {}};
    }
}

function omoTeamSaveViewsStore(store) {
    try {
        window.localStorage.setItem(omoTeamSavedViewsStorageKey, JSON.stringify({
            defaultView: store.defaultView && typeof store.defaultView === 'object'
                ? store.defaultView
                : null,
            contexts: store.contexts && typeof store.contexts === 'object' ? store.contexts : {}
        }));
    } catch (error) {
    }
}

function omoTeamGetStoredViewPreferences() {
    const preferences = omoTeamGetSavedViewsStore().contexts[omoTeamGetContextKey()];
    return preferences && typeof preferences === 'object' ? preferences : null;
}

function omoTeamGetDefaultViewPreferences() {
    return omoTeamGetSavedViewsStore().defaultView;
}

function omoTeamStoreViewPreferences(filters) {
    const store = omoTeamGetSavedViewsStore();
    store.contexts[omoTeamGetContextKey()] = omoTeamCreateViewPreferences(filters);
    omoTeamSaveViewsStore(store);
}

function omoTeamStoreDefaultViewPreferences(filters) {
    const store = omoTeamGetSavedViewsStore();
    store.defaultView = omoTeamCreateViewPreferences(filters);
    omoTeamSaveViewsStore(store);
}

function omoTeamClearStoredViewPreferences() {
    const store = omoTeamGetSavedViewsStore();
    delete store.contexts[omoTeamGetContextKey()];
    omoTeamSaveViewsStore(store);
}

function omoTeamReadViewPreferences(storage, storageKey) {
    try {
        const storedValue = storage.getItem(storageKey);
        const views = storedValue ? JSON.parse(storedValue) : null;
        if (!views || typeof views !== 'object') {
            return null;
        }
        const preferences = views[omoTeamGetContextKey()];
        return preferences && typeof preferences === 'object' ? preferences : null;
    } catch (error) {
        return null;
    }
}

function omoTeamWriteViewPreferences(storage, storageKey, filters) {
    try {
        const storedValue = storage.getItem(storageKey);
        let views = storedValue ? JSON.parse(storedValue) : {};
        if (!views || typeof views !== 'object') {
            views = {};
        }
        views[omoTeamGetContextKey()] = omoTeamCreateViewPreferences(filters);
        storage.setItem(storageKey, JSON.stringify(views));
    } catch (error) {
    }
}

function omoTeamClearSessionViewPreferences() {
    try {
        const storedValue = window.sessionStorage.getItem(omoTeamSessionViewsStorageKey);
        const views = storedValue ? JSON.parse(storedValue) : {};
        if (!views || typeof views !== 'object') {
            return;
        }
        delete views[omoTeamGetContextKey()];
        window.sessionStorage.setItem(omoTeamSessionViewsStorageKey, JSON.stringify(views));
    } catch (error) {
    }
}

function omoTeamClearAllSessionViewPreferences() {
    try {
        window.sessionStorage.removeItem(omoTeamSessionViewsStorageKey);
    } catch (error) {
    }
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
    const quickSearch = root ? String(root.getAttribute('data-team-query') || '').trim() : '';
    if (quickSearch !== '') {
        query.push('team_query=' + encodeURIComponent(quickSearch));
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

function omoTeamChangeScope(scopeValue) {
    const root = document.getElementById('omo-team-root');
    const currentScope = omoTeamNormalizeScope(root ? root.getAttribute('data-team-scope') : omoTeamInitialScope);
    const targetScope = omoTeamNormalizeScope(scopeValue);

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
}

window.omoToggleTeamScope = function (button, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    return omoTeamChangeScope(button ? button.getAttribute('data-team-scope-toggle') : '');
};

function omoTeamApplyView(viewName) {
    const normalizedView = omoTeamNormalizeView(viewName);
    const root = document.getElementById('omo-team-root');
    omoTeamCurrentView = normalizedView;
    if (root) {
        root.setAttribute('data-team-view', normalizedView);
    }

    $('[data-team-view-panel]').each(function () {
        const shouldShow = $(this).data('team-view-panel') === normalizedView;
        $(this).prop('hidden', !shouldShow);
    });

    const viewButton = root ? root.querySelector('[data-team-filter-view="' + normalizedView + '"]') : null;
    const viewChip = root ? root.querySelector('[data-team-filter-view-chip]') : null;
    if (viewButton && viewChip) {
        viewChip.textContent = viewButton.textContent.trim();
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
    omoTeamApplyQuickSearch();
}

function omoTeamNormalizeSearch(value) {
    return String(value || '')
        .toLocaleLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

function omoTeamMemberMatchesSearch(member, query) {
    if (query === '') {
        return true;
    }
    return omoTeamNormalizeSearch(member && member.searchText ? member.searchText : '').indexOf(query) !== -1;
}

function omoTeamUpdateMapSummary(count) {
    const root = document.getElementById('omo-team-root');
    const summary = root ? root.querySelector('[data-team-map-summary]') : null;
    if (!summary) {
        return;
    }
    const template = count === 1 ? omoTeamText.mapSummaryOne : omoTeamText.mapSummaryOther;
    summary.textContent = omoTeamFormatText(template, {count: String(count)});
}

function omoTeamApplyQuickSearch() {
    const root = document.getElementById('omo-team-root');
    if (!root) {
        return;
    }
    const query = omoTeamNormalizeSearch(omoTeamCurrentSearch);
    root.querySelectorAll('[data-team-member-item]').forEach(function (memberNode) {
        const searchableText = memberNode.getAttribute('data-team-member-search') || memberNode.textContent || '';
        memberNode.hidden = query !== '' && omoTeamNormalizeSearch(searchableText).indexOf(query) === -1;
    });
    root.querySelectorAll('[data-team-default-empty]').forEach(function (emptyNode) {
        emptyNode.hidden = query !== '';
    });

    let visibleCount = 0;
    if (omoTeamCurrentView === 'map') {
        visibleCount = omoTeamMapMembers.filter(function (member) {
            return omoTeamMemberMatchesSearch(member, query);
        }).length;
        const mapShell = root.querySelector('.omo-team__map-shell');
        if (mapShell) {
            mapShell.hidden = query !== '' && visibleCount === 0;
        }
        omoTeamUpdateMapSummary(visibleCount);
        if (omoTeamLeafletMap) {
            omoTeamEnsureMapReady();
        }
    } else {
        const activePanel = root.querySelector('[data-team-view-panel="' + omoTeamCurrentView + '"]');
        visibleCount = activePanel
            ? activePanel.querySelectorAll('[data-team-member-item]:not([hidden])').length
            : 0;
        const itemsContainer = root.querySelector('[data-team-items-container="' + omoTeamCurrentView + '"]');
        if (itemsContainer) {
            itemsContainer.hidden = query !== '' && visibleCount === 0;
        }
    }

    const empty = root.querySelector('[data-team-search-empty]');
    if (empty) {
        empty.hidden = query === '' || visibleCount > 0;
    }
}

function omoTeamGetActiveFilters() {
    const root = document.getElementById('omo-team-root');
    return {
        scope: omoTeamNormalizeScope(root ? root.getAttribute('data-team-scope') : omoTeamInitialScope),
        view: omoTeamNormalizeView(omoTeamCurrentView)
    };
}

function omoTeamNormalizeFilters(filters) {
    const root = document.getElementById('omo-team-root');
    const active = omoTeamGetActiveFilters();
    let scope = omoTeamNormalizeScope(filters && filters.scope);
    if (!root || !root.querySelector('[data-team-filter-scope="' + scope + '"]')) {
        scope = active.scope;
    }
    return {
        scope: scope,
        view: omoTeamNormalizeView(filters && filters.view)
    };
}

function omoTeamSyncFilterChoices() {
    const root = document.getElementById('omo-team-root');
    const panel = root ? root.querySelector('[data-team-filter-panel]') : null;
    if (!panel || !omoTeamPendingFilters) {
        return;
    }
    omoTeamPendingFilters = omoTeamNormalizeFilters(omoTeamPendingFilters);
    panel.querySelectorAll('[data-team-filter-scope]').forEach(function (button) {
        const active = button.getAttribute('data-team-filter-scope') === omoTeamPendingFilters.scope;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    panel.querySelectorAll('[data-team-filter-view]').forEach(function (button) {
        const active = button.getAttribute('data-team-filter-view') === omoTeamPendingFilters.view;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
}

function omoTeamHandleFilterOutsidePointerDown(event) {
    const root = document.getElementById('omo-team-root');
    const control = root ? root.querySelector('[data-team-filter-control]') : null;
    if (control && control.contains(event.target)) {
        return;
    }
    omoTeamCloseFilterPanel(true, false);
}

function omoTeamApplyFilters(filters, active) {
    const next = omoTeamNormalizeFilters(filters);
    const previous = active || omoTeamGetActiveFilters();
    if (next.scope !== previous.scope) {
        omoTeamChangeScope(next.scope);
        return;
    }
    omoTeamApplyView(next.view);
}

function omoTeamCloseFilterMoreMenu() {
    const root = document.getElementById('omo-team-root');
    if (!root) {
        return;
    }
    root.querySelectorAll('[data-team-filter-more-menu]').forEach(function (menu) {
        const panel = menu.querySelector('[data-team-filter-more-panel]');
        const toggle = menu.querySelector('[data-team-filter-more-toggle]');
        if (panel) {
            panel.hidden = true;
        }
        menu.classList.remove('is-open');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
}

function omoTeamOpenFilterPanel() {
    const root = document.getElementById('omo-team-root');
    const panel = root ? root.querySelector('[data-team-filter-panel]') : null;
    if (!root || !panel || omoTeamFilterPanelOpen) {
        return;
    }
    omoTeamPendingFilters = omoTeamGetActiveFilters();
    omoTeamCloseFilterMoreMenu();
    omoTeamSyncFilterChoices();
    panel.hidden = false;
    omoTeamFilterPanelOpen = true;
    root.querySelectorAll('[data-team-filter-toggle]').forEach(function (button) {
        button.setAttribute('aria-expanded', 'true');
    });
    document.addEventListener('pointerdown', omoTeamHandleFilterOutsidePointerDown, true);
}

function omoTeamCloseFilterPanel(applyChanges, saveView) {
    const root = document.getElementById('omo-team-root');
    const panel = root ? root.querySelector('[data-team-filter-panel]') : null;
    if (!root || !omoTeamFilterPanelOpen) {
        return;
    }
    omoTeamFilterPanelOpen = false;
    if (panel) {
        panel.hidden = true;
    }
    root.querySelectorAll('[data-team-filter-toggle]').forEach(function (button) {
        button.setAttribute('aria-expanded', 'false');
    });
    document.removeEventListener('pointerdown', omoTeamHandleFilterOutsidePointerDown, true);
    omoTeamCloseFilterMoreMenu();

    if (!applyChanges || !omoTeamPendingFilters) {
        omoTeamPendingFilters = null;
        return;
    }

    const active = omoTeamGetActiveFilters();
    const next = omoTeamNormalizeFilters(omoTeamPendingFilters);
    omoTeamPendingFilters = null;
    if (saveView) {
        omoTeamStoreViewPreferences(next);
        omoTeamClearSessionViewPreferences();
    } else {
        omoTeamWriteViewPreferences(window.sessionStorage, omoTeamSessionViewsStorageKey, next);
    }

    omoTeamApplyFilters(next, active);
}

function omoTeamApplyFilterMoreAction(action) {
    if (!omoTeamFilterPanelOpen || !omoTeamPendingFilters) {
        return;
    }

    const active = omoTeamGetActiveFilters();
    const next = omoTeamNormalizeFilters(omoTeamPendingFilters);
    omoTeamCloseFilterPanel(false, false);

    if (action === 'set-default') {
        omoTeamClearStoredViewPreferences();
        omoTeamClearSessionViewPreferences();
        omoTeamStoreDefaultViewPreferences(next);
        omoTeamApplyFilters(next, active);
        return;
    }

    if (action === 'apply-everywhere') {
        const store = omoTeamGetSavedViewsStore();
        store.defaultView = omoTeamCreateViewPreferences(next);
        store.contexts = {};
        omoTeamSaveViewsStore(store);
        omoTeamClearAllSessionViewPreferences();
        omoTeamApplyFilters(next, active);
        return;
    }

    if (action === 'restore-default') {
        omoTeamClearStoredViewPreferences();
        omoTeamClearSessionViewPreferences();
        omoTeamApplyFilters(omoTeamGetDefaultViewPreferences() || {
            scope: 'contextual',
            view: 'cards'
        }, active);
    }
}

function omoTeamRevealRoot() {
    const root = document.getElementById('omo-team-root');
    if (!root) {
        return;
    }
    root.removeAttribute('data-team-preferences-pending');
    root.removeAttribute('aria-busy');
}

function omoTeamInitializeFilters() {
    const root = document.getElementById('omo-team-root');
    if (!root) {
        return;
    }
    const temporary = omoTeamReadViewPreferences(window.sessionStorage, omoTeamSessionViewsStorageKey);
    const saved = omoTeamGetStoredViewPreferences();
    const defaultView = omoTeamGetDefaultViewPreferences();
    const preferences = omoTeamNormalizeFilters(temporary || saved || defaultView || {
        scope: root.getAttribute('data-team-scope') || omoTeamInitialScope,
        view: 'cards'
    });
    const currentScope = omoTeamNormalizeScope(root.getAttribute('data-team-scope') || omoTeamInitialScope);
    omoTeamCurrentSearch = root.getAttribute('data-team-query') || '';
    if (preferences.scope !== currentScope) {
        omoTeamChangeScope(preferences.scope);
        return;
    }

    omoTeamApplyView(preferences.view);
    const scopeButton = root.querySelector('[data-team-filter-scope="' + currentScope + '"]');
    const scopeChip = root.querySelector('[data-team-filter-scope-chip]');
    if (scopeButton && scopeChip) {
        scopeChip.textContent = scopeButton.textContent.trim();
    }
    omoTeamRevealRoot();
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
    }

    if (!omoTeamLeafletLayer) {
        omoTeamLeafletLayer = L.layerGroup().addTo(omoTeamLeafletMap);
    }
    omoTeamLeafletLayer.clearLayers();
    const query = omoTeamNormalizeSearch(omoTeamCurrentSearch);
    const visibleMapMembers = omoTeamMapMembers.filter(function (member) {
        return omoTeamMemberMatchesSearch(member, query);
    });
    const bounds = [];

    visibleMapMembers.forEach(function (member) {
            const lat = Number(member.lat);
            const lng = Number(member.long);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const popupBits = ['<div class="omo-team__map-popup">'];

            popupBits.push('<div class="omo-team__map-popup-head">');
            if (member.photoUrl) {
                popupBits.push('<img class="omo-team__map-popup-photo" src="' + omoTeamEscapeHtml(member.photoUrl) + '" alt="' + omoTeamEscapeHtml(member.displayName || omoTeamFormatText(omoTeamText.userFallback, {userId: member.userId})) + '">');
            } else {
                popupBits.push('<div class="omo-team__map-popup-photo-placeholder">' + omoTeamEscapeHtml(member.initials || 'P') + '</div>');
            }

            popupBits.push('<div class="omo-team__map-popup-identity">');
            popupBits.push('<div class="omo-team__map-popup-name">' + omoTeamEscapeHtml(member.displayName || omoTeamFormatText(omoTeamText.userFallback, {userId: member.userId})) + '</div>');
            if (member.secondary) {
                popupBits.push('<div class="omo-team__map-popup-secondary">' + omoTeamEscapeHtml(member.secondary) + '</div>');
            } else if (member.email) {
                popupBits.push('<div class="omo-team__map-popup-secondary">' + omoTeamEscapeHtml(member.email) + '</div>');
            }
            popupBits.push('</div>');
            popupBits.push('</div>');

            popupBits.push('<div class="omo-team__map-popup-badges">');
            if (member.isPending) {
                popupBits.push('<span class="omo-team__map-popup-badge omo-team__map-popup-badge--pending">' + omoTeamEscapeHtml(omoTeamText.pending) + '</span>');
            }
            if (member.isContextAdmin) {
                popupBits.push('<span class="omo-team__map-popup-badge omo-team__map-popup-badge--admin">' + omoTeamEscapeHtml(omoTeamText.adminContext) + '</span>');
            }
            if (member.isOrganizationAdmin) {
                popupBits.push('<span class="omo-team__map-popup-badge">' + omoTeamEscapeHtml(omoTeamText.adminOrganization) + '</span>');
            }
            popupBits.push('</div>');

            popupBits.push('<div class="omo-team__map-popup-meta">');
            popupBits.push('<div class="omo-team__map-popup-meta-row"><div class="omo-team__map-popup-meta-label">' + omoTeamEscapeHtml(omoTeamText.email) + '</div><div class="omo-team__map-popup-meta-value">' + omoTeamEscapeHtml(member.email || omoTeamText.notProvided) + '</div></div>');
            popupBits.push('<div class="omo-team__map-popup-meta-row"><div class="omo-team__map-popup-meta-label">' + omoTeamEscapeHtml(omoTeamText.added) + '</div><div class="omo-team__map-popup-meta-value">' + omoTeamEscapeHtml(member.joinedAtLabel || 'N/A') + '</div></div>');
            popupBits.push('<div class="omo-team__map-popup-meta-row"><div class="omo-team__map-popup-meta-label">' + omoTeamEscapeHtml(omoTeamText.lastConnection) + '</div><div class="omo-team__map-popup-meta-value">' + omoTeamEscapeHtml(member.lastSeenLabel || omoTeamText.never) + '</div></div>');
            popupBits.push('</div>');

            if (member.canViewDetail) {
                popupBits.push('<button type="button" class="omo-team__map-popup-action" data-map-popup-open-user="' + Number(member.userId) + '">' + omoTeamEscapeHtml(omoTeamText.openProfile) + '</button>');
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

$(function () {
    const root = document.getElementById('omo-team-root');
    if (!root) {
        return;
    }

    root.querySelectorAll('[data-team-filter-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (omoTeamFilterPanelOpen) {
                omoTeamCloseFilterPanel(true, false);
            } else {
                omoTeamOpenFilterPanel();
            }
        });
    });

    const panel = root.querySelector('[data-team-filter-panel]');
    if (panel) {
        panel.addEventListener('click', function (event) {
            const moreToggle = event.target.closest('[data-team-filter-more-toggle]');
            if (moreToggle) {
                event.preventDefault();
                event.stopPropagation();
                const moreMenu = moreToggle.closest('[data-team-filter-more-menu]');
                const morePanel = moreMenu ? moreMenu.querySelector('[data-team-filter-more-panel]') : null;
                const isMoreMenuOpen = !!morePanel && !morePanel.hidden;
                omoTeamCloseFilterMoreMenu();
                if (!isMoreMenuOpen && morePanel) {
                    morePanel.hidden = false;
                    moreMenu.classList.add('is-open');
                    moreToggle.setAttribute('aria-expanded', 'true');
                }
                return;
            }
            const moreAction = event.target.closest('[data-team-filter-more-action]');
            if (moreAction) {
                event.preventDefault();
                event.stopPropagation();
                omoTeamApplyFilterMoreAction(
                    moreAction.getAttribute('data-team-filter-more-action') || ''
                );
                return;
            }
            const applyButton = event.target.closest('[data-team-filter-apply]');
            if (applyButton) {
                event.preventDefault();
                omoTeamCloseFilterPanel(true, false);
                return;
            }
            const saveButton = event.target.closest('[data-team-filter-save]');
            if (saveButton) {
                event.preventDefault();
                omoTeamCloseFilterPanel(true, true);
                return;
            }
            const scopeButton = event.target.closest('[data-team-filter-scope]');
            if (scopeButton && omoTeamPendingFilters) {
                omoTeamPendingFilters.scope = omoTeamNormalizeScope(scopeButton.getAttribute('data-team-filter-scope'));
                omoTeamSyncFilterChoices();
                return;
            }
            const viewButton = event.target.closest('[data-team-filter-view]');
            if (viewButton && omoTeamPendingFilters) {
                omoTeamPendingFilters.view = omoTeamNormalizeView(viewButton.getAttribute('data-team-filter-view'));
                omoTeamSyncFilterChoices();
            }
        });
    }

    const quickSearch = root.querySelector('[data-team-quick-search]');
    if (quickSearch) {
        quickSearch.addEventListener('input', function () {
            omoTeamCurrentSearch = quickSearch.value || '';
            root.setAttribute('data-team-query', omoTeamCurrentSearch);
            omoTeamApplyQuickSearch();
        });
        quickSearch.addEventListener('search', function () {
            omoTeamCurrentSearch = quickSearch.value || '';
            root.setAttribute('data-team-query', omoTeamCurrentSearch);
            omoTeamApplyQuickSearch();
        });
    }

    root.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && omoTeamFilterPanelOpen) {
            omoTeamCloseFilterPanel(false, false);
        }
    });

    omoTeamInitializeFilters();
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
        omoTeamText.addMemberTitle,
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
    const displayName = $.trim(card.find('.omo-team-card__identity h3').first().text()) || omoTeamText.thisMember;
    let confirmationMessage = '';

    if (!action || !userId) {
        return;
    }

    if (action === 'cancel_invitation') {
        confirmationMessage = omoTeamFormatText(omoTeamText.confirmCancelInvitation, {name: displayName});
    } else if (action === 'remove') {
        confirmationMessage = omoTeamFormatText(omoTeamText.confirmRemove, {name: displayName, context: contextLabel});
    } else if (action === 'grant_admin') {
        confirmationMessage = omoTeamFormatText(omoTeamText.confirmGrantAdmin, {name: displayName, context: contextLabel});
    } else if (action === 'revoke_admin') {
        confirmationMessage = omoTeamFormatText(omoTeamText.confirmRevokeAdmin, {name: displayName, context: contextLabel});
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

    const memberActionUrl = typeof window.omoResolveAppUrl === 'function'
        ? window.omoResolveAppUrl('/omo/api/team/member_action.php')
        : '/omo/api/team/member_action.php';

    fetch(memberActionUrl, {
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
            window.omoNotify(result.data && result.data.message ? result.data.message : omoTeamText.updateFailed, 'error');
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
            const currentTeamQuery = teamRoot ? String(teamRoot.getAttribute('data-team-query') || '').trim() : '';
            if (currentTeamQuery !== '') {
                drawerUrl += '&team_query=' + encodeURIComponent(currentTeamQuery);
            }
            refreshDrawer('drawer_team', drawerUrl);
        }

        if (typeof loadContent === 'function') {
            let leftUrl = 'api/getOrg.php?oid=' + organizationId;
            if (currentHolonId > 0 && currentHolonId !== rootHolonId) {
                leftUrl += '&cid=' + currentHolonId;
            }
            loadContent(typeof omoGetLeftPanelContentSelector === 'function' ? omoGetLeftPanelContentSelector() : '#panel-left', leftUrl);
        }

        if (rootHolonId > 0 && typeof window.omoReloadStructureAndFocus === 'function') {
            window.omoReloadStructureAndFocus(currentHolonId > 0 && currentHolonId !== rootHolonId ? currentHolonId : null, {
                quickZoom: true
            });
        }
      })
      .catch(function () {
        button.prop('disabled', false);
        window.omoNotify(omoTeamText.updateFailedLater, 'error');
      });
  });
</script>
