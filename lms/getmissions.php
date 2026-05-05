<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once BASE_PATH . '/common/auth.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

$parcours_id = (int)($_GET['parcours_id'] ?? 0);
$accessContext = lmsGetParcoursAccessContext((int)$org['id'], $parcours_id);

if (empty($accessContext['exists'])) {
	http_response_code(404);
	echo json_encode([
		'html' => '',
		'progress' => 0,
		'done' => 0,
		'total' => 0,
	]);
	exit;
}

if (empty($accessContext['canView'])) {
	http_response_code(!empty($accessContext['isLoggedIn']) ? 403 : 401);
	echo json_encode([
		'html' => '',
		'progress' => 0,
		'done' => 0,
		'total' => 0,
	]);
	exit;
}

$total = \dbObject\ParcoursMission::countForParcours($parcours_id);

if (lmsIsAnonymousViewer($accessContext)) {
	$doneMissionIds = lmsGetAnonymousDoneMissionIds();
	$doneMissions = \dbObject\Mission::fetchDoneForMissionIds($parcours_id, $doneMissionIds);
	$doneMissions = is_array($doneMissions) ? $doneMissions : [];
	$done = count($doneMissions);
	$missions = \dbObject\Mission::fetchAvailableForMissionIds($parcours_id, $doneMissionIds);
} else {
	$user_id = (int)$accessContext['userId'];
	$done = \dbObject\UserMission::countDoneForUserAndParcours($user_id, $parcours_id);
	$missions = \dbObject\Mission::fetchAvailableForUserParcours($user_id, $parcours_id);
}

$missions = is_array($missions) ? $missions : [];

$progress = $total > 0 ? round(($done / $total) * 100) : 0;

ob_start();

$currentBranch = null;

foreach ($missions as $m) {
	$branch = $m['branch'] ?: 'Tronc commun';

	if ($branch !== $currentBranch) {
		if ($currentBranch !== null) {
			echo "</div></div>";
		}

		$branchId = 'branch_' . md5($branch);

		echo "<div class='branch' data-branch-id='$branchId'>";
		if ($branch !== 'Tronc commun') {
			echo "<div class='branch-header' onclick=\"toggleBranch('$branchId')\">";
			echo "<h2>" . htmlspecialchars($branch) . "</h2>";
			echo "</div>";
		}
		echo "<div class='missions'>";

		$currentBranch = $branch;
	}

	echo "<div class='card' onclick='viewMission(" . (int)$m['id'] . ")'>";
	echo "<div class='card-content'>";
	echo "<h3>" . htmlspecialchars($m['title']) . "</h3>";
	echo "<p>" . htmlspecialchars($m['resume']) . "</p>";
	echo "<div class='card-footer'>";
	echo "<span class='card-meta'></span>";
	echo "<button type='button' class='open-btn' onclick='event.stopPropagation(); viewMission(" . (int)$m['id'] . ")'>Ouvrir</button>";
	echo "</div>";
	echo "</div>";
	echo "</div>";
}

if ($currentBranch !== null) {
	echo "</div></div>";
}

$html = ob_get_clean();

echo json_encode([
	'html' => $html,
	'progress' => $progress,
	'done' => $done,
	'total' => $total,
]);
