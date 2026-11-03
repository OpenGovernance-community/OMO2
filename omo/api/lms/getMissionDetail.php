<?php
require_once __DIR__ . '/ui_translations.php';
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

$sourceLang = [
    'lms.mission_detail.error.access_denied' => ['text' => 'Accès refusé', 'context' => 'Error shown when the viewer cannot access the mission detail.'],
    'lms.mission_detail.error.not_found' => ['text' => 'Mission introuvable', 'context' => 'Error shown when the mission cannot be found.'],
    'lms.mission_detail.homeworks.title' => ['text' => 'Devoirs', 'context' => 'Section title listing mission homeworks.'],
    'lms.mission_detail.homeworks.status_done' => ['text' => 'Validé', 'context' => 'Status label shown when a homework is completed.'],
    'lms.mission_detail.homeworks.status_todo' => ['text' => 'À faire', 'context' => 'Status label shown when a homework remains to do.'],
    'lms.mission_detail.homeworks.expand' => ['text' => 'Détail de la tâche', 'context' => 'Button label used to open a homework detail.'],
    'lms.mission_detail.homeworks.collapse' => ['text' => 'Masquer le détail', 'context' => 'Button label used to collapse a homework detail.'],
    'lms.mission_detail.homeworks.empty_detail' => ['text' => 'Aucun détail supplémentaire.', 'context' => 'Fallback text shown when a homework has no extra detail.'],
    'lms.mission_detail.homeworks.help' => ['text' => 'Terminez tous les devoirs avant de poursuivre cette mission.', 'context' => 'Help text shown below the homework list.'],
    'lms.mission_detail.homeworks.mark_done' => ['text' => 'Valider la tâche', 'context' => 'Accessible label used to mark a homework as done.'],
    'lms.mission_detail.homeworks.mark_undone' => ['text' => 'Retirer la validation', 'context' => 'Accessible label used to unmark a completed homework.'],
    'lms.mission_detail.validation.unavailable' => ['text' => 'La validation de cette mission n’est pas disponible dans ce contexte.', 'context' => 'Message shown when mission validation cannot be used.'],
    'lms.mission_detail.validation.start_quiz' => ['text' => 'Commencer le quiz', 'context' => 'Button label used to start the quiz.'],
    'lms.mission_detail.validation.mark_read' => ['text' => 'Marquer comme lu', 'context' => 'Button label used to complete a mission without quiz.'],
    'lms.mission_detail.validation.remaining' => ['text' => 'Terminez encore {count} devoir{suffix} pour continuer.', 'context' => 'Info message shown when some homeworks remain before validation.'],
    'lms.mission_detail.validation.quiz_info' => ['text' => 'Cette mission sera validée par {count} question{suffix}', 'context' => 'Info message shown before starting the quiz.'],
    'lms.mission_detail.quiz.counter' => ['text' => 'Question {current}/{total}', 'context' => 'Counter shown above the current quiz question.'],
    'lms.mission_detail.quiz.multiple' => ['text' => 'Plusieurs réponses possibles', 'context' => 'Hint shown when a quiz question allows multiple answers.'],
    'lms.mission_detail.quiz.finish' => ['text' => 'Terminer', 'context' => 'Button label used on the last quiz question.'],
    'lms.mission_detail.quiz.submit' => ['text' => 'Valider la réponse', 'context' => 'Button label used to submit a quiz answer.'],
    'lms.mission_detail.quiz.select_answer' => ['text' => 'Veuillez sélectionner une réponse.', 'context' => 'Alert shown when no answer is selected.'],
    'lms.mission_detail.quiz.wrong_answer' => ['text' => 'Mauvaise réponse.', 'context' => 'Alert shown when the submitted answer is wrong.'],
    'lms.mission_detail.alert.load_quiz' => ['text' => 'Impossible de charger le quiz.', 'context' => 'Alert shown when the quiz cannot be loaded.'],
    'lms.mission_detail.alert.check_answer' => ['text' => 'Impossible de verifier cette reponse. Reessayez.', 'context' => 'Notification shown when quiz answer verification fails.'],
    'lms.mission_detail.alert.save_homework' => ['text' => 'Impossible d’enregistrer ce devoir pour le moment.', 'context' => 'Alert shown when homework completion cannot be saved.'],
    'lms.mission_detail.alert.validate_mission' => ['text' => 'Impossible de valider cette mission pour le moment.', 'context' => 'Alert shown when the mission cannot be validated.'],
];

$lang = omoLoadTranslationBundle('omo_lms_mission_detail', $sourceLang);

function lmsMissionDetailT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$mission_id = (int)($_GET['mission_id'] ?? 0);
$parcours_id = (int)($_GET['parcours_id'] ?? 0);
$accessContext = lmsGetParcoursAccessContext((int)$org['id'], $parcours_id);
$canTrackProgress = lmsCanTrackProgress($accessContext);
$isAnonymousViewer = lmsIsAnonymousViewer($accessContext);
$isOrganizationAdmin = !empty($accessContext['isOrganizationAdmin']);

if (empty($accessContext['exists']) || empty($accessContext['canView'])) {
	http_response_code(empty($accessContext['isLoggedIn']) ? 401 : 403);
	echo '<div class="generic-section generic-section--plain">' . lmsMissionDetailT('lms.mission_detail.error.access_denied') . '</div>';
	exit;
}

$parcoursMission = new \dbObject\ParcoursMission();
if (!$parcoursMission->load([
	['IDparcours', $parcours_id],
	['IDmission', $mission_id],
])) {
	http_response_code(404);
	echo '<div class="generic-section generic-section--plain">' . lmsMissionDetailT('lms.mission_detail.error.not_found') . '</div>';
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
	echo lmsMissionDetailT('lms.mission_detail.error.not_found');
}
?>
<?= commonPageScriptTags('/omo/api/lms/getMissionDetail.js', [
    'lmsMissionText' => [
		'homeworksTitle' => lmsMissionDetailT('lms.mission_detail.homeworks.title'),
		'homeworkDone' => lmsMissionDetailT('lms.mission_detail.homeworks.status_done'),
		'homeworkTodo' => lmsMissionDetailT('lms.mission_detail.homeworks.status_todo'),
		'homeworkExpand' => lmsMissionDetailT('lms.mission_detail.homeworks.expand'),
		'homeworkCollapse' => lmsMissionDetailT('lms.mission_detail.homeworks.collapse'),
		'homeworkEmptyDetail' => lmsMissionDetailT('lms.mission_detail.homeworks.empty_detail'),
		'homeworkHelp' => lmsMissionDetailT('lms.mission_detail.homeworks.help'),
		'homeworkMarkDone' => lmsMissionDetailT('lms.mission_detail.homeworks.mark_done'),
		'homeworkMarkUndone' => lmsMissionDetailT('lms.mission_detail.homeworks.mark_undone'),
		'validationUnavailable' => lmsMissionDetailT('lms.mission_detail.validation.unavailable'),
		'startQuiz' => lmsMissionDetailT('lms.mission_detail.validation.start_quiz'),
		'markRead' => lmsMissionDetailT('lms.mission_detail.validation.mark_read'),
		'remaining' => lmsMissionDetailT('lms.mission_detail.validation.remaining'),
		'quizInfo' => lmsMissionDetailT('lms.mission_detail.validation.quiz_info'),
		'quizCounter' => lmsMissionDetailT('lms.mission_detail.quiz.counter'),
		'quizMultiple' => lmsMissionDetailT('lms.mission_detail.quiz.multiple'),
		'quizFinish' => lmsMissionDetailT('lms.mission_detail.quiz.finish'),
		'quizSubmit' => lmsMissionDetailT('lms.mission_detail.quiz.submit'),
		'quizSelectAnswer' => lmsMissionDetailT('lms.mission_detail.quiz.select_answer'),
		'quizWrongAnswer' => lmsMissionDetailT('lms.mission_detail.quiz.wrong_answer'),
		'alertLoadQuiz' => lmsMissionDetailT('lms.mission_detail.alert.load_quiz'),
		'alertCheckAnswer' => lmsMissionDetailT('lms.mission_detail.alert.check_answer'),
		'alertSaveHomework' => lmsMissionDetailT('lms.mission_detail.alert.save_homework'),
		'alertValidateMission' => lmsMissionDetailT('lms.mission_detail.alert.validate_mission'),
		'notFound' => lmsMissionDetailT('lms.mission_detail.error.not_found'),
	],
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
