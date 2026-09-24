<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/leaflet_helper.php';
require_once dirname(__DIR__, 3) . '/common/team/translations.php';

use dbObject\ArrayUserOrganization;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\User;
use dbObject\UserHolon;
use dbObject\UserOrganization;

function omoTeamHolonTypeLabel(Holon $holon, ?array $lang = null, ?array $sourceLang = null)
{
    return $holon->getTypeLabel();
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

function omoTeamPhoneHref($phone)
{
    $phone = trim((string)$phone);
    $normalizedPhone = preg_replace('/[^0-9+*#]/', '', $phone);
    if ($normalizedPhone === null || !preg_match('/\d/', $normalizedPhone)) {
        return '';
    }

    return 'tel:' . $normalizedPhone;
}

function omoTeamEmailHref($email)
{
    $email = trim((string)$email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return '';
    }

    return 'mailto:' . $email;
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
$contextAdminLabel = trim((string)($organizationLexicon['admin']['label'] ?? '')) ?: 'Admin';
$contextAdminLabelLower = function_exists('mb_strtolower')
	? mb_strtolower($contextAdminLabel, 'UTF-8')
	: strtolower($contextAdminLabel);
$organizationAdminLabel = 'Admin';

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
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
$hasBudgetApplication = $organization->isApplicationEnabled('budget', $currentUserId);
$applicationViewPreferences = omoApplicationViewPreferencesGetContext('team', $organization, $currentHolon, $currentUserId);
commonReleaseReadOnlySession();
$availableTeamScopes = omoApiGetAvailableContextScopes($canToggleTeamScope, $currentHolon, $rootHolon);
$teamScope = omoApiNormalizeContextScope(
    omoApplicationViewPreferencesGetInitialValue($applicationViewPreferences, 'team_scope', 'scope', 'contextual'),
    $availableTeamScopes
);
$teamQuickSearch = trim((string)($_GET['team_query'] ?? ''));
$teamScopeActiveIndex = omoApiResolveContextScopeIndex($teamScope, $availableTeamScopes);
$teamScopeLabels = array(
    'contextual' => omoTeamT('team.scope.contextual', [], $lang, $sourceLang),
    'children' => omoTeamT('team.scope.children', [], $lang, $sourceLang),
    'descendants' => omoTeamT('team.scope.descendants', [], $lang, $sourceLang),
);

$rawMemberCards = array();
$membershipsByUserId = array();
$contextAdminUserIds = array();
$removableContextMemberUserIds = array();
$directContextMemberUserIds = array();

if ($hasStructureContext) {
    $removableContextMemberUserIds = array_fill_keys($currentHolon->getAssociatedMemberUserIds(array(
        'organizationId' => $organizationId,
    )), true);
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

        $removableContextMemberUserIds[$userId] = true;
        $directContextMemberUserIds[$userId] = true;

        $rawMemberCards[] = array(
            'userId' => $userId,
            'isPending' => !(bool)$membership->get('active'),
        );
        $membershipsByUserId[$userId] = $membership;
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
$budgetFormatter = class_exists('NumberFormatter') ? new NumberFormatter($intlLocale, NumberFormatter::DECIMAL) : null;
if ($budgetFormatter instanceof NumberFormatter) {
    $budgetFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 0);
    $budgetFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
}

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

$formatBudgetAmount = static function ($value) use ($budgetFormatter): string {
    if (!is_numeric($value) || (float)$value < 0) {
        return '';
    }

    $amount = (float)$value;
    if ($budgetFormatter instanceof NumberFormatter) {
        $formatted = $budgetFormatter->format($amount);
        if (is_string($formatted) && $formatted !== '') {
            return $formatted;
        }
    }

    return rtrim(rtrim(number_format($amount, 2, '.', ' '), '0'), '.');
};

$budgetRecurrenceLabels = array(
    UserHolon::BUDGET_RECURRENCE_DAY => omoTeamT('team.assignment_popup.recurrence.day', [], $lang, $sourceLang),
    UserHolon::BUDGET_RECURRENCE_WEEK => omoTeamT('team.assignment_popup.recurrence.week', [], $lang, $sourceLang),
    UserHolon::BUDGET_RECURRENCE_MONTH => omoTeamT('team.assignment_popup.recurrence.month', [], $lang, $sourceLang),
    UserHolon::BUDGET_RECURRENCE_YEAR => omoTeamT('team.assignment_popup.recurrence.year', [], $lang, $sourceLang),
);

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

$parseAssignmentDate = static function ($value): ?DateTimeImmutable {
    if ($value instanceof DateTimeImmutable) {
        return $value;
    }
    if ($value instanceof DateTimeInterface) {
        return DateTimeImmutable::createFromInterface($value);
    }
    if (!is_scalar($value) || trim((string)$value) === '') {
        return null;
    }

    try {
        return new DateTimeImmutable((string)$value);
    } catch (Exception $exception) {
        return null;
    }
};

$isOrganizationTeamContext = !$hasStructureContext || $currentHolon->isOrganizationHolon();
$currentHolonTypeId = $hasStructureContext ? (int)$currentHolon->get('IDtypeholon') : 4;
$currentHolonIdForAssignments = $hasStructureContext ? (int)$currentHolon->getId() : 0;
$isRoleTeamContext = $currentHolonTypeId === 1;
$assignmentReviewReferenceDate = new DateTimeImmutable('today');
$memberContext = UserOrganization::loadTeamMemberContext(
    $organizationId,
    array_column($rawMemberCards, 'userId'),
    $membershipsByUserId
);

foreach ($rawMemberCards as $rawCard) {
    $userId = (int)($rawCard['userId'] ?? 0);
    if ($userId <= 0) {
        continue;
    }

    $membership = $memberContext['memberships'][$userId] ?? null;
    $hasMembership = $membership instanceof UserOrganization;
    $user = $memberContext['users'][$userId] ?? null;
    $hasUser = $user instanceof User;
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
    $assignmentLinks = is_array($rawCard['assignmentLinks'] ?? null)
        ? $rawCard['assignmentLinks']
        : array();
    $directAssignment = null;
    $contextFocus = '';
    $contextTimeBudget = null;
    $contextTimeBudgetRecurrence = '';
    $contextMoneyBudget = null;
    $contextMoneyBudgetRecurrence = '';
    $contextAssignmentReviewDate = null;
    $roleAssignments = array();
    $fallbackAssignments = array();
    foreach ($assignmentLinks as $assignmentLink) {
        if (!is_array($assignmentLink)) {
            continue;
        }

        $assignmentDate = $parseAssignmentDate($assignmentLink['assignedAt'] ?? null);
        if ($assignmentDate instanceof DateTimeImmutable) {
            $fallbackAssignments[] = $assignmentDate;
        }
        if ((int)($assignmentLink['holonId'] ?? 0) === $currentHolonIdForAssignments) {
            $directAssignment = $assignmentDate;
            $contextFocus = trim((string)($assignmentLink['focus'] ?? ''));
            $contextTimeBudget = $assignmentLink['timeBudgetHours'] ?? null;
            $contextTimeBudgetRecurrence = trim((string)($assignmentLink['timeBudgetRecurrence'] ?? ''));
            $contextMoneyBudget = $assignmentLink['moneyBudget'] ?? null;
            $contextMoneyBudgetRecurrence = trim((string)($assignmentLink['moneyBudgetRecurrence'] ?? ''));
            $contextAssignmentReviewDate = $parseAssignmentDate($assignmentLink['assignmentReviewDate'] ?? null);
        }
        if ((int)($assignmentLink['holonTypeId'] ?? 0) === 1 && $assignmentDate instanceof DateTimeImmutable) {
            $roleAssignments[] = $assignmentDate;
        }
    }

    $contextAssignmentAt = $directAssignment;
    $contextAssignmentRoleCount = 0;
    if (!$contextAssignmentAt instanceof DateTimeImmutable && $currentHolonTypeId === 2 && count($roleAssignments) > 0) {
        usort($roleAssignments, static fn (DateTimeImmutable $left, DateTimeImmutable $right): int => $left <=> $right);
        $contextAssignmentAt = $roleAssignments[0];
        $contextAssignmentRoleCount = count($roleAssignments);
    }
    if (!$contextAssignmentAt instanceof DateTimeImmutable && count($fallbackAssignments) > 0) {
        usort($fallbackAssignments, static fn (DateTimeImmutable $left, DateTimeImmutable $right): int => $left <=> $right);
        $contextAssignmentAt = $fallbackAssignments[0];
    }
    $contextTimeBudgetAmount = $formatBudgetAmount($contextTimeBudget);
    $contextMoneyBudgetAmount = $formatBudgetAmount($contextMoneyBudget);
    $contextTimeBudgetRecurrenceLabel = $budgetRecurrenceLabels[$contextTimeBudgetRecurrence] ?? '';
    $contextMoneyBudgetRecurrenceLabel = $budgetRecurrenceLabels[$contextMoneyBudgetRecurrence] ?? '';
    $contextTimeBudgetLabel = $hasBudgetApplication && $contextTimeBudgetAmount !== '' && $contextTimeBudgetRecurrenceLabel !== ''
        ? omoTeamT('team.member.time_budget_value', array('amount' => $contextTimeBudgetAmount, 'recurrence' => $contextTimeBudgetRecurrenceLabel), $lang, $sourceLang)
        : '';
    $contextMoneyBudgetLabel = $hasBudgetApplication && $contextMoneyBudgetAmount !== '' && $contextMoneyBudgetRecurrenceLabel !== ''
        ? omoTeamT('team.member.money_budget_value', array('amount' => $contextMoneyBudgetAmount, 'recurrence' => $contextMoneyBudgetRecurrenceLabel), $lang, $sourceLang)
        : '';
    $contextAssignmentReviewDateLabel = $contextAssignmentReviewDate instanceof DateTimeImmutable
        ? $formatDate($contextAssignmentReviewDate)
        : '';
    $isAssignmentReviewOverdue = $contextAssignmentReviewDate instanceof DateTimeImmutable
        && $contextAssignmentReviewDate < $assignmentReviewReferenceDate;
    $displayName = trim((string)($rawCard['displayName'] ?? ''));
    if ($displayName === '' && $hasMembership) {
        $displayName = $membership->getUserDisplayName();
    }
    if ($displayName === '' && $hasUser) {
        $displayName = $user->getScopedDisplayName($organizationId);
    }

    $email = $hasMembership ? $membership->getScopedEmail() : ($hasUser ? $user->getScopedEmail($organizationId) : '');
	$phone = $hasMembership ? $membership->getScopedPhone() : ($hasUser ? $user->getScopedPhone($organizationId) : '');
	$phoneHref = omoTeamPhoneHref($phone);
	$emailHref = omoTeamEmailHref($email);
    $username = $hasMembership ? $membership->getScopedUsername() : ($hasUser ? $user->getScopedUsername($organizationId) : '');
    $photoUrl = trim((string)($rawCard['photoUrl'] ?? ''));
    if ($photoUrl === '' && $hasMembership) {
        $photoUrl = $membership->getProfilePhotoUrl();
    }
    if ($photoUrl === '' && $hasUser) {
        $photoUrl = $user->getScopedProfilePhotoUrl($organizationId);
    }

    $firstName = $hasUser ? trim((string)$user->get('firstname')) : '';
    $lastName = $hasUser ? trim((string)$user->get('lastname')) : '';
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
    $structuredIdentityName = trim($firstName . ' ' . $lastName);
    $identityTitle = $structuredIdentityName !== ''
        ? $structuredIdentityName
        : ($username !== '' ? $username : ($email !== '' ? $email : $resolvedDisplayName));
    $identitySecondary = $structuredIdentityName !== '' && $username !== '' && $username !== $identityTitle
        ? $username
        : '';
    $memberSearchText = trim(implode(' ', array_filter(array(
        $resolvedDisplayName,
        $firstName,
        $lastName,
        $phone,
        $email,
        $username,
        $identitySecondary,
        $currentHolonTypeId === 1 ? $contextFocus : '',
        $contextTimeBudgetLabel,
        $contextMoneyBudgetLabel,
        $hasPendingInvitation
            ? omoTeamT('team.member.invitation_pending', [], $lang, $sourceLang)
            : ($isPending ? omoTeamT('team.member.to_invite', [], $lang, $sourceLang) : ''),
        $isContextAdmin ? omoTeamT('team.member.admin_context', ['adminLabel' => $contextAdminLabel], $lang, $sourceLang) : '',
        $isOrganizationAdmin ? omoTeamT('team.member.admin_organization', ['adminLabel' => $organizationAdminLabel], $lang, $sourceLang) : '',
    ), static fn ($value): bool => trim((string)$value) !== '')));

    $memberCards[] = array(
        'userId' => $userId,
        'displayName' => $resolvedDisplayName,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'phone' => $phone,
		'phoneHref' => $phoneHref,
        'email' => $email,
		'emailHref' => $emailHref,
        'username' => $username,
        'secondary' => $identitySecondary,
        'identityTitle' => $identityTitle,
        'identitySecondary' => $identitySecondary,
        'photoUrl' => $photoUrl,
        'initials' => $initials !== '' ? mb_strtoupper($initials, 'UTF-8') : 'P',
        'isOrganizationAdmin' => $isOrganizationAdmin,
        'isContextAdmin' => $isContextAdmin,
        'isRemovableInContext' => isset($removableContextMemberUserIds[$userId]),
        'isDirectContextMember' => isset($directContextMemberUserIds[$userId]),
        'isPending' => $isPending,
        'hasPendingInvitation' => $hasPendingInvitation,
        'joinedAtLabel' => $effectiveJoinedAt instanceof DateTimeInterface ? $formatDate($effectiveJoinedAt) : '',
        'lastSeenLabel' => $formatLastSeenLabel($organizationLastSeen, $globalLastSeen),
        'organizationLastSeenLabel' => $organizationLastSeen instanceof DateTimeInterface ? $formatDate($organizationLastSeen) : '',
        'siteLastSeenLabel' => $globalLastSeen instanceof DateTimeInterface ? $formatDate($globalLastSeen) : '',
        'createdAtLabel' => $globalJoinedAt instanceof DateTimeInterface ? $formatDate($globalJoinedAt) : '',
        'contextAssignmentLabel' => $contextAssignmentAt instanceof DateTimeImmutable ? $formatDate($contextAssignmentAt) : '',
        'contextAssignmentRoleCount' => $contextAssignmentRoleCount,
        'contextFocus' => $currentHolonTypeId === 1 ? $contextFocus : '',
        'contextTimeBudgetLabel' => $contextTimeBudgetLabel,
        'contextMoneyBudgetLabel' => $contextMoneyBudgetLabel,
        'contextAssignmentReviewDateLabel' => $contextAssignmentReviewDateLabel,
        'isAssignmentReviewOverdue' => $isAssignmentReviewOverdue,
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
$canRemoveCurrentHolonMembers = $hasStructureContext ? $currentHolon->isAllowed('CAN_DELETE_MEMBER') : false;
$canGrantCurrentHolonAdmin = $hasStructureContext ? $currentHolon->isAllowed('CAN_ADD_ADMIN') : false;
$canManageCurrentHolonMembers = $canRemoveCurrentHolonMembers || $canGrantCurrentHolonAdmin;
$canEditCurrentMemberAssignments = $hasStructureContext
    && !$currentHolon->isOrganizationHolon()
    && ($currentHolon->isAllowed('CAN_EDIT_MEMBER_ASSIGNMENT') || $currentHolon->isAllowed('CAN_EDIT_AFFECTATION_BUDGET'));
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
		'emailHref' => (string)($card['emailHref'] ?? ''),
		'phone' => (string)($card['phone'] ?? ''),
		'phoneHref' => (string)($card['phoneHref'] ?? ''),
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
<link rel="stylesheet" href="/common/view-filter/view-filter.css?v=20260902-save-menu">
<div
    class="omo-team omo-panel-view"
    id="omo-team-root"
    data-team-oid="<?= (int)$organizationId ?>"
    data-team-cid="<?= $hasStructureContext ? (int)$currentHolon->getId() : 0 ?>"
    data-omo-app-view-preferences="<?= omoApiEscape(json_encode($applicationViewPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
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
                        <?php if (!empty($applicationViewPreferences['canSavePersonal']) || !empty($applicationViewPreferences['canSaveTemporary'])): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary"<?= !empty($applicationViewPreferences['canSavePersonal']) ? ' data-team-filter-save' : '' ?> data-omo-app-view-save-scope="<?= omoApiEscape($applicationViewPreferences['primarySaveScope']) ?>"><?= omoApiEscape(omoTeamT('team.filters.save_view', [], $lang, $sourceLang)) ?></button>
                        <?php elseif (($applicationViewPreferences['primarySaveScope'] ?? '') !== ''): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-app-view-save-scope="<?= omoApiEscape($applicationViewPreferences['primarySaveScope']) ?>"><?= omoApiEscape($applicationViewPreferences['primarySaveLabel'] ?? '') ?></button>
                        <?php endif; ?>
                        <?= omoApplicationViewPreferencesRenderMenu($applicationViewPreferences) ?>
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
                        class="omo-team-card omo-card<?= $card['canViewDetail'] ? ' omo-card--interactive' : '' ?><?= $card['isPending'] ? ' omo-team-card--pending' : '' ?><?= $card['isAssignmentReviewOverdue'] ? ' omo-team-card--assignment-overdue' : '' ?>"
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
                            <?php if ($canManageCurrentHolonMembers && !empty($card['isRemovableInContext'])): ?>
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
                                        <?php if ($canEditCurrentMemberAssignments && !empty($card['isDirectContextMember'])): ?>
                                            <button
                                                type="button"
                                                class="omo-team-card__menu-item"
                                                data-team-edit-assignment="1"
                                                data-user-id="<?= (int)$card['userId'] ?>"
                                            ><?= omoApiEscape(omoTeamT('team.action.edit_assignment', [], $lang, $sourceLang)) ?></button>
                                        <?php endif; ?>
                                        <?php if ($canRemoveCurrentHolonMembers): ?>
                                            <button
                                                type="button"
                                                class="omo-team-card__menu-item omo-team-card__menu-item--danger"
                                                data-member-action="remove"
                                                data-user-id="<?= (int)$card['userId'] ?>"
                                            ><?= omoApiEscape(omoTeamT('team.action.remove_from_context', ['context' => (string)$currentHolonTemplateLabel], $lang, $sourceLang)) ?></button>
                                        <?php endif; ?>
                                        <?php if ($canRemoveCurrentHolonMembers && $card['hasPendingInvitation']): ?>
                                            <button
                                                type="button"
                                                class="omo-team-card__menu-item omo-team-card__menu-item--danger"
                                                data-member-action="cancel_invitation"
                                                data-user-id="<?= (int)$card['userId'] ?>"
                                            ><?= omoApiEscape(omoTeamT('team.action.cancel_invitation', [], $lang, $sourceLang)) ?></button>
                                        <?php endif; ?>
                                        <?php if ($canGrantCurrentHolonAdmin && !$card['isPending']): ?>
                                            <button
                                                type="button"
                                                class="omo-team-card__menu-item"
                                                data-member-action="<?= $card['isContextAdmin'] ? 'revoke_admin' : 'grant_admin' ?>"
                                                data-user-id="<?= (int)$card['userId'] ?>"
                                            ><?= omoApiEscape($card['isContextAdmin']
                                                ? omoTeamT('team.action.revoke_context_admin', ['context' => (string)$currentHolonTemplateLabel, 'adminLabel' => $contextAdminLabelLower], $lang, $sourceLang)
                                                : omoTeamT('team.action.grant_context_admin', ['context' => (string)$currentHolonTemplateLabel, 'adminLabel' => $contextAdminLabelLower], $lang, $sourceLang)) ?></button>
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
                                    <h3 title="<?= omoApiEscape($card['identityTitle']) ?>"><?= omoApiEscape($card['identityTitle']) ?></h3>
                                    <?php if ($card['identitySecondary'] !== ''): ?>
                                        <p title="<?= omoApiEscape($card['identitySecondary']) ?>"><?= omoApiEscape($card['identitySecondary']) ?></p>
                                    <?php endif; ?>
                                </div>

                                <?php if ($card['hasPendingInvitation']): ?>
                                    <span class="omo-team-card__badge omo-team-card__badge--pending"><?= omoApiEscape(omoTeamT('team.member.invitation_pending', [], $lang, $sourceLang)) ?></span>
                                <?php elseif ($card['isPending']): ?>
                                    <span class="omo-team-card__badge omo-team-card__badge--pending"><?= omoApiEscape(omoTeamT('team.member.to_invite', [], $lang, $sourceLang)) ?></span>
                                <?php elseif ($card['isContextAdmin']): ?>
                                    <span class="omo-team-card__badge"><?= omoApiEscape(omoTeamT('team.member.admin_short', ['adminLabel' => $contextAdminLabel], $lang, $sourceLang)) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="omo-team-card__meta">
								<div class="omo-team-card__meta-row<?= !$isRoleTeamContext && ($card['phone'] !== '' || $card['email'] !== '') ? ' omo-team-card__meta-row--contact' : '' ?>">
									<span class="omo-team-card__meta-label generic-meta-label generic-meta-label--compact"><?= omoApiEscape(omoTeamT($isRoleTeamContext ? 'team.member.focus' : 'team.member.contact', [], $lang, $sourceLang)) ?></span>
									<?php if (!$isRoleTeamContext): ?>
										<span class="omo-team-card__meta-value omo-team-card__contact-value generic-meta-value generic-meta-value--compact">
											<span class="omo-team-card__contact-line omo-team-card__contact-line--phone<?= $card['phone'] === '' ? ' omo-team-card__contact-line--muted' : '' ?>">
												<svg class="omo-team-card__contact-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6.6 10.8c1.4 2.8 3.7 5.1 6.5 6.5l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.5.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.5 21 3 13.5 3 4.2c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.2.2 2.4.6 3.5.1.3.1.7-.2 1L6.6 10.8z" fill="currentColor"></path></svg>
												<?php if ($card['phoneHref'] !== ''): ?>
												<a class="omo-team-card__phone-link" href="<?= omoApiEscape($card['phoneHref']) ?>" data-team-phone-link><?= omoApiEscape($card['phone']) ?></a>
												<?php else: ?>
												<span><?= omoApiEscape($card['phone'] !== '' ? $card['phone'] : omoTeamT('team.member.not_provided', [], $lang, $sourceLang)) ?></span>
												<?php endif; ?>
											</span>
											<span class="omo-team-card__contact-line omo-team-card__contact-line--email<?= $card['email'] === '' ? ' omo-team-card__contact-line--muted' : '' ?>">
												<svg class="omo-team-card__contact-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h13A2.5 2.5 0 0 1 21 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5v-11zm2 .2v.3l7 4.7 7-4.7v-.3c0-.4-.3-.7-.7-.7H5.7c-.4 0-.7.3-.7.7zm14 2.7-6.4 4.3a1 1 0 0 1-1.1 0L5 9.4v8.1c0 .4.3.7.7.7h12.6c.4 0 .7-.3.7-.7V9.4z" fill="currentColor"></path></svg>
												<?php if ($card['emailHref'] !== ''): ?>
												<a class="omo-team-card__email-text omo-team-card__email-link" href="<?= omoApiEscape($card['emailHref']) ?>" data-team-email-link title="<?= omoApiEscape($card['email']) ?>"><?= omoApiEscape($card['email']) ?></a>
												<?php else: ?>
												<span class="omo-team-card__email-text" title="<?= omoApiEscape($card['email']) ?>"><?= omoApiEscape($card['email'] !== '' ? $card['email'] : omoTeamT('team.member.not_provided', [], $lang, $sourceLang)) ?></span>
												<?php endif; ?>
												<?php if ($card['email'] !== ''): ?>
												<button
													type="button"
													class="omo-team-card__copy-email"
													data-team-copy-email="<?= omoApiEscape($card['email']) ?>"
													aria-label="<?= omoApiEscape(omoTeamT('team.action.copy_email', [], $lang, $sourceLang)) ?>"
													title="<?= omoApiEscape(omoTeamT('team.action.copy_email', [], $lang, $sourceLang)) ?>"
												><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 8h10v12H8zM5 4h10v2H7v10H5z" fill="currentColor"></path></svg></button>
												<?php endif; ?>
											</span>
										</span>
									<?php else: ?>
										<span class="omo-team-card__meta-value omo-team-card__focus-value generic-meta-value generic-meta-value--compact<?= $card['contextFocus'] === '' ? ' omo-team-card__meta-value--muted' : '' ?>"><?= omoApiEscape($card['contextFocus'] !== '' ? $card['contextFocus'] : omoTeamT('team.member.not_provided', [], $lang, $sourceLang)) ?></span>
									<?php endif; ?>
								</div>
                            </div>

                            <?php if ($card['contextTimeBudgetLabel'] !== '' || $card['contextMoneyBudgetLabel'] !== ''): ?>
                                <div class="omo-team-card__budgets">
                                    <?php if ($card['contextTimeBudgetLabel'] !== ''): ?>
                                        <div class="omo-team-card__budget">
                                            <span class="omo-team-card__meta-label generic-meta-label generic-meta-label--compact"><?= omoApiEscape(omoTeamT('team.assignment_popup.time_budget', [], $lang, $sourceLang)) ?></span>
                                            <span class="omo-team-card__meta-value generic-meta-value generic-meta-value--compact"><?= omoApiEscape($card['contextTimeBudgetLabel']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($card['contextMoneyBudgetLabel'] !== ''): ?>
                                        <div class="omo-team-card__budget">
                                            <span class="omo-team-card__meta-label generic-meta-label generic-meta-label--compact"><?= omoApiEscape(omoTeamT('team.assignment_popup.money_budget', [], $lang, $sourceLang)) ?></span>
                                            <span class="omo-team-card__meta-value generic-meta-value generic-meta-value--compact"><?= omoApiEscape($card['contextMoneyBudgetLabel']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="omo-team-card__dates">
                                <?php if ($isOrganizationTeamContext): ?>
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
                                <?php else: ?>
                                    <div class="omo-team-card__date">
                                        <span class="omo-team-card__date-label"><?= omoApiEscape(omoTeamT('team.member.assignment', [], $lang, $sourceLang)) ?></span>
                                        <span class="omo-team-card__date-value<?= $card['contextAssignmentLabel'] === '' ? ' omo-team-card__date-value--muted' : '' ?>">
                                            <?= omoApiEscape($card['contextAssignmentLabel'] !== '' ? $card['contextAssignmentLabel'] : 'N/A') ?>
                                        </span>
                                    </div>

                                    <?php if ($card['contextAssignmentReviewDateLabel'] !== ''): ?>
                                        <div class="omo-team-card__date">
                                            <span class="omo-team-card__date-label"><?= omoApiEscape(omoTeamT('team.member.assignment_review_date', [], $lang, $sourceLang)) ?></span>
                                            <span class="omo-team-card__date-value"><?= omoApiEscape($card['contextAssignmentReviewDateLabel']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($card['contextAssignmentRoleCount'] > 0): ?>
                                        <div class="omo-team-card__date">
                                            <span class="omo-team-card__date-label"><?= omoApiEscape(omoTeamT('team.member.assignment_via', [], $lang, $sourceLang)) ?></span>
                                            <span class="omo-team-card__date-value">
                                                <?= omoApiEscape(omoTeamT('team.member.assignment_roles', ['count' => (int)$card['contextAssignmentRoleCount']], $lang, $sourceLang)) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
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
                        <div class="omo-team__compact-list-header-cell generic-file-list__header-cell omo-team__compact-list-header-cell--phone"><?= omoApiEscape(omoTeamT($isRoleTeamContext ? 'team.member.focus' : 'team.column.phone', [], $lang, $sourceLang)) ?></div>
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
                                    'label' => omoTeamT('team.member.admin_context', ['adminLabel' => $contextAdminLabel], $lang, $sourceLang),
                                    'className' => 'omo-team__compact-badge',
                                );
                            }

                            if ($card['isOrganizationAdmin']) {
                                $compactPrivilegeLabels[] = array(
                                    'label' => omoTeamT('team.member.admin_organization', ['adminLabel' => $organizationAdminLabel], $lang, $sourceLang),
                                    'className' => 'omo-team__compact-badge omo-team__compact-badge--organization',
                                );
                            }
                        }
                        ?>
                        <article class="omo-team__compact-item-shell generic-file-list__item-shell" data-team-member-item data-team-member-search="<?= omoApiEscape((string)$card['searchText']) ?>">
                            <div
                                class="omo-team__compact-row generic-file-list__row<?= $card['canViewDetail'] ? ' omo-team__compact-row--interactive' : '' ?><?= $card['isPending'] ? ' omo-team__compact-row--pending' : '' ?><?= $card['isAssignmentReviewOverdue'] ? ' omo-team__compact-row--assignment-overdue' : '' ?>"
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
                                <div class="omo-team__compact-cell generic-file-list__cell" data-label="<?= omoApiEscape(omoTeamT($isRoleTeamContext ? 'team.member.focus' : 'team.column.phone', [], $lang, $sourceLang)) ?>">
                                    <?php $compactFocus = trim((string)($card['contextFocus'] ?? '')); ?>
									<?php $compactPhoneHref = trim((string)($card['phoneHref'] ?? '')); ?>
									<?php if (!$isRoleTeamContext && $compactPhone !== '' && $compactPhoneHref !== ''): ?>
										<a class="omo-team__compact-phone-link" href="<?= omoApiEscape($compactPhoneHref) ?>" data-team-phone-link><?= omoApiEscape($compactPhone) ?></a>
									<?php else: ?>
										<span class="<?= ($isRoleTeamContext ? $compactFocus : $compactPhone) === '' ? 'omo-team__compact-placeholder' : '' ?>"><?= omoApiEscape($isRoleTeamContext ? ($compactFocus !== '' ? $compactFocus : '-') : ($compactPhone !== '' ? $compactPhone : '-')) ?></span>
									<?php endif; ?>
                                </div>
                                <div class="omo-team__compact-cell generic-file-list__cell" data-label="<?= omoApiEscape(omoTeamT('team.member.email', [], $lang, $sourceLang)) ?>">
									<?php $compactEmailHref = trim((string)($card['emailHref'] ?? '')); ?>
									<?php if ($card['email'] !== '' && $compactEmailHref !== ''): ?>
										<span class="omo-team__compact-email-value">
											<a class="omo-team__compact-email-link" href="<?= omoApiEscape($compactEmailHref) ?>" data-team-email-link><?= omoApiEscape($card['email']) ?></a>
											<button
												type="button"
												class="omo-team__compact-copy-email"
												data-team-copy-email="<?= omoApiEscape($card['email']) ?>"
												aria-label="<?= omoApiEscape(omoTeamT('team.action.copy_email', [], $lang, $sourceLang)) ?>"
												title="<?= omoApiEscape(omoTeamT('team.action.copy_email', [], $lang, $sourceLang)) ?>"
											><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 8h10v12H8zM5 4h10v2H7v10H5z" fill="currentColor"></path></svg></button>
										</span>
									<?php else: ?>
										<span class="<?= $card['email'] === '' ? 'omo-team__compact-placeholder' : '' ?>"><?= omoApiEscape($card['email'] !== '' ? $card['email'] : '-') ?></span>
									<?php endif; ?>
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

<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/team/index.css') ?>">

<?php
$teamJsTranslations = [
    'userFallback' => omoTeamT('team.member.user_fallback', ['userId' => '{userId}'], $lang, $sourceLang),
    'pending' => omoTeamT('team.member.pending', [], $lang, $sourceLang),
    'adminContext' => omoTeamT('team.member.admin_context', ['adminLabel' => $contextAdminLabel], $lang, $sourceLang),
    'adminOrganization' => omoTeamT('team.member.admin_organization', ['adminLabel' => $organizationAdminLabel], $lang, $sourceLang),
    'email' => omoTeamT('team.member.email', [], $lang, $sourceLang),
	'phone' => omoTeamT('team.column.phone', [], $lang, $sourceLang),
    'contact' => omoTeamT('team.member.contact', [], $lang, $sourceLang),
	'emailCopied' => omoTeamT('team.action.email_copied', [], $lang, $sourceLang),
    'notProvided' => omoTeamT('team.member.not_provided', [], $lang, $sourceLang),
    'added' => omoTeamT('team.member.added', [], $lang, $sourceLang),
    'lastConnection' => omoTeamT('team.member.last_connection', [], $lang, $sourceLang),
    'never' => omoTeamT('team.member.never', [], $lang, $sourceLang),
    'openProfile' => omoTeamT('team.map.open_profile', [], $lang, $sourceLang),
    'addMemberTitle' => omoTeamT('team.action.add_member', [], $lang, $sourceLang),
    'thisMember' => omoTeamT('team.member.this_member', [], $lang, $sourceLang),
    'confirmCancelInvitation' => omoTeamT('team.confirm.cancel_invitation', ['name' => '{name}'], $lang, $sourceLang),
    'confirmRemove' => omoTeamT('team.confirm.remove', ['name' => '{name}', 'context' => '{context}'], $lang, $sourceLang),
    'confirmRemoveWithOneRole' => omoTeamT('team.confirm.remove_with_one_role', ['name' => '{name}', 'context' => '{context}'], $lang, $sourceLang),
    'confirmRemoveWithRoles' => omoTeamT('team.confirm.remove_with_roles', ['name' => '{name}', 'context' => '{context}', 'roleCount' => '{roleCount}'], $lang, $sourceLang),
    'confirmGrantAdmin' => omoTeamT('team.confirm.grant_context_admin', ['name' => '{name}', 'context' => '{context}', 'adminLabel' => $contextAdminLabelLower], $lang, $sourceLang),
    'confirmRevokeAdmin' => omoTeamT('team.confirm.revoke_context_admin', ['name' => '{name}', 'context' => '{context}', 'adminLabel' => $contextAdminLabelLower], $lang, $sourceLang),
    'updateFailed' => omoTeamT('team.message.update_failed', [], $lang, $sourceLang),
    'updateFailedLater' => omoTeamT('team.message.update_failed_later', [], $lang, $sourceLang),
    'mapSummaryOne' => omoTeamT('team.map.summary_one', [], $lang, $sourceLang),
    'mapSummaryOther' => omoTeamT('team.map.summary_other', ['count' => '{count}'], $lang, $sourceLang),
];
?>
<script src="/omo/assets/js/application-view-preferences.js?v=20260917-filter-hierarchy"></script>
<?= commonPageScriptTags('/omo/api/team/index.js', [
    'omoTeamMapEnabled' => ($leafletMapsEnabled),
    'omoTeamMapMembers' => $mapMemberPayload,
    'omoTeamInitialScope' => $teamScope,
    'omoTeamText' => $teamJsTranslations,
    'teamAssignmentPopupTitle' => omoTeamT('team.assignment_popup.title', [], $lang, $sourceLang),
    'organizationId' => (int)$organizationId,
    'currentHolonId' => $hasStructureContext ? (int)$currentHolon->getId() : 0,
    'rootHolonId' => $hasStructureContext ? (int)$rootHolon->getId() : 0,
    'contextLabel' => $currentHolonTemplateLabel,
], 'omoTeamPageConfig') ?>
