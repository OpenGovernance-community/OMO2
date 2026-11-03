<?php
require_once __DIR__ . '/ui_translations.php';
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';
require_once dirname(__DIR__, 3) . '/common/lms/quiz.php';

require_once dirname(__DIR__, 3) . '/common/lms/mission-translations.php';

$mission_id = (int)($_GET['mission_id'] ?? 0);
$parcours_id = (int)($_GET['parcours_id'] ?? 0);
$accessContext = lmsGetParcoursAccessContext((int)$org['id'], $parcours_id);
$canTrackProgress = lmsCanTrackProgress($accessContext);
$isAnonymousViewer = lmsIsAnonymousViewer($accessContext);
$isOrganizationAdmin = !empty($accessContext['isOrganizationAdmin']);

if (empty($accessContext['exists']) || empty($accessContext['canView'])) {
	http_response_code(empty($accessContext['isLoggedIn']) ? 401 : 403);
	echo '<div class="generic-section generic-section--plain">' . commonLmsMissionT('lms.mission_detail.error.access_denied') . '</div>';
	exit;
}

$parcoursMission = new \dbObject\ParcoursMission();
if (!$parcoursMission->load([
	['IDparcours', $parcours_id],
	['IDmission', $mission_id],
])) {
	http_response_code(404);
	echo '<div class="generic-section generic-section--plain">' . commonLmsMissionT('lms.mission_detail.error.not_found') . '</div>';
	exit;
}

$mission = new \dbObject\Mission();
$m = false;
$quizCount = 0;
$homeworks = [];
$parcoursRef = new \dbObject\Parcours();
$parcoursRef->load($parcours_id);
$videoPresentation = $parcoursRef->getVideoPresentationForOrganization((int)$org['id']);
require_once dirname(__DIR__, 3) . '/common/lms/video.php';

if ($mission->load($mission_id)) {
	$m = [
		'title' => (string)$mission->get('title'),
		'resume' => (string)$mission->get('resume'),
		'html' => (string)$mission->get('html'),
		'video' => (string)$mission->get('video'),
	];
	$quizCount = $mission->getQuizCount();
	$homeworks = \dbObject\Mission::fetchHomeworksForMission(
		$mission_id,
		!empty($accessContext['isLoggedIn']) ? (int)$accessContext['userId'] : 0,
		$parcours_id,
		$isOrganizationAdmin
	);
}

$homeworksJson = json_encode($homeworks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($homeworksJson === false) {
	$homeworksJson = '[]';
}

echo "<div class=\"lms-mission-detail generic-section generic-section--plain\">";

if ($m) {

	echo "<h2>" . htmlspecialchars($m['title']) . "</h2>";
	echo "<p><em>" . htmlspecialchars($m['resume']) . "</em></p>";
	echo "<div>";
?>
	<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/lms/css/mission-detail.css') ?>">
<?php
	commonLmsRenderMissionVideo($m['video'], $videoPresentation, $m['title']);
	echo $m['html'];
	echo "<div id='homework-section'></div>";
	echo "</div>";
	echo "<div data-quiz-count='$quizCount' data-homework-count='" . count($homeworks) . "' id='quiz-info'></div>";
} else {
	echo commonLmsMissionT('lms.mission_detail.error.not_found');
}
?>
<script src="<?= commonAssetUrl('/common/lms/quiz.js') ?>"></script>
<script src="<?= commonAssetUrl('/common/lms/read-progress.js') ?>"></script>
<?= commonPageScriptTags('/omo/api/lms/getMissionDetail.js', [
    'quizFeedbackText' => commonLmsQuizFeedbackTexts(),
    'lmsMissionText' => commonLmsMissionTexts(),
    'lmsMissionId' => (int)$mission_id,
    'lmsMissionViewerCanTrack' => ($canTrackProgress),
    'lmsMissionViewerIsAnonymous' => ($isAnonymousViewer),
    'lmsMissionHomeworks' => json_decode($homeworksJson, true, 512, JSON_THROW_ON_ERROR),
    'text' => lmsBuildLocalPath('/homework_action.php'),
    'text2' => lmsBuildLocalPath('/getMissionQuestions.php'),
    'text3' => lmsBuildLocalPath('/action.php'),
    'checkAnswerUrl' => lmsBuildLocalPath('/checkAnswer.php'),
]) ?>
</div>
