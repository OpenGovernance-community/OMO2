<?php
require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__, 2) . '/common/auth.php';

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

function lmsParseDoneHomeworkIds($rawValue)
{
	return lmsParsePositiveIds($rawValue);
}

function lmsGetParcoursAccessContext($organizationId, $parcoursId, $userId = null)
{
	if ($userId === null) {
		$userId = (int)commonGetCurrentUserId();
	}

	return \dbObject\OrganizationParcours::resolveAccessContext(
		(int)$organizationId,
		(int)$parcoursId,
		(int)$userId
	);
}

function lmsIsAnonymousViewer(array $accessContext)
{
	return empty($accessContext['isLoggedIn']) && !empty($accessContext['canTrackProgressLocally']);
}

function lmsCanTrackProgress(array $accessContext)
{
	return !empty($accessContext['canTrackProgress']);
}
