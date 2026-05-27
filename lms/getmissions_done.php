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
	echo json_encode(['html' => '']);
	exit;
}

if (empty($accessContext['canView'])) {
	http_response_code(!empty($accessContext['isLoggedIn']) ? 403 : 401);
	echo json_encode(['html' => '']);
	exit;
}

$isAnonymousViewer = lmsIsAnonymousViewer($accessContext);
$missions = $isAnonymousViewer
	? \dbObject\Mission::fetchDoneForMissionIds($parcours_id, lmsGetAnonymousDoneMissionIds())
	: \dbObject\Mission::fetchDoneForUserParcours((int)$accessContext['userId'], $parcours_id);
$missions = is_array($missions) ? $missions : [];

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
		echo "<div class='branch-header' onclick=\"toggleBranch('$branchId')\">";
		echo "<h2>" . htmlspecialchars($branch) . "</h2>";
		echo "</div>";
		echo "<div class='missions'>";

		$currentBranch = $branch;
	}

	$meta = '';
	if ($isAnonymousViewer) {
		$meta = 'Enregistre localement';
	} elseif (!empty($m['done'])) {
		$meta = 'Fait le ' . htmlspecialchars((string)$m['done']);
	}
	echo "<div class='card' onclick='viewMission(" . (int)$m['id'] . ")'>";
	echo "<div class='card-content'>";
	echo "<h3>" . htmlspecialchars($m['title']) . "</h3>";
	echo "<p>" . htmlspecialchars($m['resume']) . "</p>";
	echo "<div class='card-footer'>";
	echo "<span class='card-meta'>" . $meta . "</span>";
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
	'html' => $html
]);
