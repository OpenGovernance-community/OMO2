<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';

$parcours_id = (int)$_GET['parcours_id'];
$user_id = (int)($_SESSION['currentUser'] ?? 0);

if ($user_id <= 0) {
    http_response_code(401);
    echo json_encode([
        'html' => '',
        'progress' => 0,
        'done' => 0,
        'total' => 0,
    ]);
    exit;
}

$total = \dbObject\ParcoursMission::countForParcours($parcours_id);
$done = \dbObject\UserMission::countDoneForUserAndParcours($user_id, $parcours_id);
$progress = $total > 0 ? round(($done / $total) * 100) : 0;
$missions = \dbObject\Mission::fetchAvailableForUserParcours($user_id, $parcours_id);

ob_start();

$currentBranch = null;

foreach ($missions as $m) {
    $branch = $m['branch'] ?: 'Tronc commun';

    if ($branch !== $currentBranch) {
        if ($currentBranch !== null) echo "</div></div>";

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

    echo "<div class='card'>";
    echo "<h3>" . htmlspecialchars($m['title']) . "</h3>";
    echo "<p>" . htmlspecialchars($m['resume']) . "</p>";
    echo "<button onclick='viewMission(" . $m['id'] . ")'>View</button>";
    echo "</div>";
}

if ($currentBranch !== null) echo "</div></div>";

$html = ob_get_clean();

echo json_encode([
    'html' => $html,
    'progress' => $progress,
    'done' => $done,
    'total' => $total
]);
