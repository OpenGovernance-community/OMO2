<?php
require_once dirname(__DIR__) . '/bootstrap.php';

function lmsParsePositiveIds($rawValue)
{
	$values = [];

	if (is_array($rawValue)) {
		$values = $rawValue;
	} else {
		$rawValue = trim((string)$rawValue);
		if ($rawValue === '') {
			return [];
		}

		$decoded = json_decode($rawValue, true);
		if (is_array($decoded)) {
			$values = $decoded;
		} else {
			$values = preg_split('/[^0-9]+/', $rawValue);
		}
	}

	$missionIds = [];
	foreach ($values as $value) {
		$missionId = (int)$value;
		if ($missionId > 0) {
			$missionIds[$missionId] = $missionId;
		}
	}

	return array_values($missionIds);
}

function lmsParseDoneMissionIds($rawValue)
{
	return lmsParsePositiveIds($rawValue);
}

function lmsGetAnonymousDoneMissionIds()
{
	return lmsParseDoneMissionIds($_POST['done_ids'] ?? ($_GET['done_ids'] ?? ''));
}

function lmsParseDoneParcoursIds($rawValue)
{
	return lmsParsePositiveIds($rawValue);
}

function lmsGetAnonymousCompletedParcoursIds()
{
	return lmsParseDoneParcoursIds($_POST['done_parcours_ids'] ?? ($_GET['done_parcours_ids'] ?? ''));
}

function lmsParseDoneHomeworkIds($rawValue)
{
	return lmsParsePositiveIds($rawValue);
}

function lmsGetParcoursAccessContext($organizationId, $parcoursId, $userId = null)
{
	if ($userId === null) {
		$userId = (int)commonGetCurrentUserId();
	}

	$parcoursId = (int)$parcoursId;
	$userId = (int)$userId;

	if (function_exists('lmsIsBasicCatalogMode') && lmsIsBasicCatalogMode()) {
		$accessContext = \dbObject\Parcours::resolveBasicCatalogAccessContext(
			$parcoursId,
			$userId
		);
	} else {
		$accessContext = \dbObject\OrganizationParcours::resolveAccessContext(
			(int)$organizationId,
			$parcoursId,
			$userId
		);
	}

	if (!empty($accessContext['canView'])) {
		$prerequisiteIds = \dbObject\Parcours::fetchPrerequisiteIdsForParcours($parcoursId);
		if (count($prerequisiteIds) > 0) {
			$prerequisitesSatisfied = $userId > 0
				? \dbObject\Parcours::arePrerequisitesSatisfiedForUser($parcoursId, $userId)
				: count(array_diff($prerequisiteIds, lmsGetAnonymousCompletedParcoursIds())) === 0;

			if (!$prerequisitesSatisfied) {
				$accessContext['canView'] = false;
				$accessContext['blockedByPrerequisites'] = true;
			}
		}
	}

	return $accessContext;
}

function lmsIsAnonymousViewer(array $accessContext)
{
	return empty($accessContext['isLoggedIn']) && !empty($accessContext['canTrackProgressLocally']);
}

function lmsCanTrackProgress(array $accessContext)
{
	return !empty($accessContext['canTrackProgress']);
}

function lmsResolveCurrentUserId($userId = null)
{
	if ($userId !== null) {
		return (int)$userId;
	}

	return (int)commonGetCurrentUserId();
}

function lmsGetRequestedContextHolonId()
{
	return max(0, (int)($_REQUEST['cid'] ?? 0));
}

function lmsResolvePermissionHolonContext($organizationId, $userId = null)
{
	static $cache = array();

	$organizationId = (int)$organizationId;
	$userId = lmsResolveCurrentUserId($userId);
	$requestedHolonId = lmsGetRequestedContextHolonId();
	$cacheKey = $organizationId . ':' . $userId . ':' . $requestedHolonId;

	if (array_key_exists($cacheKey, $cache)) {
		return $cache[$cacheKey];
	}

	$context = array(
		'organization' => null,
		'rootHolon' => null,
		'permissionHolon' => null,
		'requestedHolonId' => $requestedHolonId,
	);

	if ($organizationId <= 0) {
		$cache[$cacheKey] = $context;
		return $context;
	}

	$organization = new \dbObject\Organization();
	if (!$organization->load($organizationId)) {
		$cache[$cacheKey] = $context;
		return $context;
	}

	$rootHolon = $organization->getEnabledStructuralRootHolon($userId > 0 ? $userId : null);
	$permissionHolon = $rootHolon instanceof \dbObject\Holon ? $rootHolon : null;

	if (
		$requestedHolonId > 0
		&& $rootHolon instanceof \dbObject\Holon
		&& (int)$rootHolon->getId() !== $requestedHolonId
	) {
		$candidateHolon = new \dbObject\Holon();
		if (
			$candidateHolon->load($requestedHolonId)
			&& $organization->containsHolon($candidateHolon)
			&& $candidateHolon->canViewDetail()
		) {
			$permissionHolon = $candidateHolon;
		}
	}

	$context = array(
		'organization' => $organization,
		'rootHolon' => $rootHolon instanceof \dbObject\Holon ? $rootHolon : null,
		'permissionHolon' => $permissionHolon,
		'requestedHolonId' => $requestedHolonId,
	);

	$cache[$cacheKey] = $context;
	return $context;
}

function lmsCurrentUserCanCreateParcours($organizationId, $userId = null, $useSessionCache = true)
{
	$organizationId = (int)$organizationId;
	$userId = lmsResolveCurrentUserId($userId);
	if ($organizationId <= 0 || $userId <= 0 || !commonUserHasOrganizationAccess($userId, $organizationId)) {
		return false;
	}

	if (function_exists('commonCurrentUserIsAdminModeEnabled') && commonCurrentUserIsAdminModeEnabled($organizationId)) {
		return true;
	}

	$permissionContext = lmsResolvePermissionHolonContext($organizationId, $userId);
	$permissionHolon = $permissionContext['permissionHolon'] ?? null;
	if (!($permissionHolon instanceof \dbObject\Holon)) {
		return false;
	}

	return $permissionHolon->isAllowed('CAN_CREATE_PARCOURS', (bool)$useSessionCache, $userId);
}

function lmsCurrentUserCanEditParcours($organizationId, $userId = null, $useSessionCache = true)
{
	$organizationId = (int)$organizationId;
	$userId = lmsResolveCurrentUserId($userId);
	if ($organizationId <= 0 || $userId <= 0 || !commonUserHasOrganizationAccess($userId, $organizationId)) {
		return false;
	}

	if (function_exists('commonCurrentUserIsAdminModeEnabled') && commonCurrentUserIsAdminModeEnabled($organizationId)) {
		return true;
	}

	$permissionContext = lmsResolvePermissionHolonContext($organizationId, $userId);
	$permissionHolon = $permissionContext['permissionHolon'] ?? null;
	if (!($permissionHolon instanceof \dbObject\Holon)) {
		return false;
	}

	return $permissionHolon->isAllowed('CAN_EDIT_PARCOURS', (bool)$useSessionCache, $userId);
}

function lmsResolveParcoursManagementContext($organizationId, $parcoursId = 0, $userId = null, $useSessionCache = true)
{
	$organizationId = (int)$organizationId;
	$parcoursId = (int)$parcoursId;
	$userId = lmsResolveCurrentUserId($userId);
	$hasOrganizationAccess = $organizationId > 0 && $userId > 0
		? commonUserHasOrganizationAccess($userId, $organizationId)
		: false;
	$canCreate = $hasOrganizationAccess && lmsCurrentUserCanCreateParcours($organizationId, $userId, $useSessionCache);
	$canEdit = $hasOrganizationAccess && lmsCurrentUserCanEditParcours($organizationId, $userId, $useSessionCache);
	$link = null;
	$parcours = null;
	$isOwned = false;
	$isExposedViaPack = false;

	if ($organizationId > 0 && $parcoursId > 0) {
		$link = \dbObject\OrganizationParcours::loadForOrganizationParcours($organizationId, $parcoursId);
		$parcours = new \dbObject\Parcours();
		if (!$parcours->load($parcoursId)) {
			$parcours = null;
		} else {
			$isOwned = $parcours->isOwnedByOrganization($organizationId);
			$isExposedViaPack = \dbObject\Parcours::hasAttachedPackParentInOrganization($organizationId, $parcoursId);
		}
	}

	return array(
		'userId' => $userId,
		'organizationId' => $organizationId,
		'hasOrganizationAccess' => $hasOrganizationAccess,
		'canCreate' => $canCreate,
		'canEdit' => $canEdit,
		'link' => $link,
		'parcours' => $parcours,
		'isOwned' => $isOwned,
		'isExposedViaPack' => $isExposedViaPack,
		'canEditContent' => $canEdit && $parcours instanceof \dbObject\Parcours && $isOwned && ($link !== null || $isExposedViaPack),
	);
}
