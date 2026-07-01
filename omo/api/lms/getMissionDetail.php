<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

$sourceLang = [
    'lms.mission_detail.error.access_denied' => ['text' => 'Acces refuse', 'context' => 'Error shown when the viewer cannot access the mission detail.'],
    'lms.mission_detail.error.not_found' => ['text' => 'Mission introuvable', 'context' => 'Error shown when the mission cannot be found.'],
    'lms.mission_detail.video.play' => ['text' => 'Lire', 'context' => 'Custom control label used to play the mission video.'],
    'lms.mission_detail.video.sound' => ['text' => 'Son', 'context' => 'Custom control label used to control mission video sound.'],
    'lms.mission_detail.video.unavailable' => ['text' => 'La video de cette mission n est pas disponible pour le moment.', 'context' => 'Fallback message shown when the mission video cannot be embedded.'],
    'lms.mission_detail.homeworks.title' => ['text' => 'Devoirs', 'context' => 'Section title listing mission homeworks.'],
    'lms.mission_detail.homeworks.status_done' => ['text' => 'Valide', 'context' => 'Status label shown when a homework is completed.'],
    'lms.mission_detail.homeworks.status_todo' => ['text' => 'A faire', 'context' => 'Status label shown when a homework remains to do.'],
    'lms.mission_detail.homeworks.expand' => ['text' => 'Detail de la tache', 'context' => 'Button label used to open a homework detail.'],
    'lms.mission_detail.homeworks.collapse' => ['text' => 'Masquer le detail', 'context' => 'Button label used to collapse a homework detail.'],
    'lms.mission_detail.homeworks.empty_detail' => ['text' => 'Aucun detail supplementaire.', 'context' => 'Fallback text shown when a homework has no extra detail.'],
    'lms.mission_detail.homeworks.help' => ['text' => 'Terminez tous les homeworks avant de poursuivre cette mission.', 'context' => 'Help text shown below the homework list.'],
    'lms.mission_detail.homeworks.mark_done' => ['text' => 'Valider la tâche', 'context' => 'Accessible label used to mark a homework as done.'],
    'lms.mission_detail.homeworks.mark_undone' => ['text' => 'Retirer la validation', 'context' => 'Accessible label used to unmark a completed homework.'],
    'lms.mission_detail.validation.unavailable' => ['text' => 'La validation de cette mission n est pas disponible dans ce contexte.', 'context' => 'Message shown when mission validation cannot be used.'],
    'lms.mission_detail.validation.start_quiz' => ['text' => 'Commencer le quiz', 'context' => 'Button label used to start the quiz.'],
    'lms.mission_detail.validation.mark_read' => ['text' => 'Marquer comme lu', 'context' => 'Button label used to complete a mission without quiz.'],
    'lms.mission_detail.validation.remaining' => ['text' => 'Terminez encore {count} homework{suffix} pour continuer.', 'context' => 'Info message shown when some homeworks remain before validation.'],
    'lms.mission_detail.validation.quiz_info' => ['text' => 'Cette mission sera validee par {count} question{suffix}', 'context' => 'Info message shown before starting the quiz.'],
    'lms.mission_detail.quiz.counter' => ['text' => 'Question {current}/{total}', 'context' => 'Counter shown above the current quiz question.'],
    'lms.mission_detail.quiz.multiple' => ['text' => 'Plusieurs reponses possibles', 'context' => 'Hint shown when a quiz question allows multiple answers.'],
    'lms.mission_detail.quiz.finish' => ['text' => 'Terminer', 'context' => 'Button label used on the last quiz question.'],
    'lms.mission_detail.quiz.submit' => ['text' => 'Valider la reponse', 'context' => 'Button label used to submit a quiz answer.'],
    'lms.mission_detail.quiz.select_answer' => ['text' => 'Veuillez selectionner une reponse', 'context' => 'Alert shown when no answer is selected.'],
    'lms.mission_detail.quiz.wrong_answer' => ['text' => 'Mauvaise reponse', 'context' => 'Alert shown when the submitted answer is wrong.'],
    'lms.mission_detail.alert.load_quiz' => ['text' => 'Impossible de charger le quiz.', 'context' => 'Alert shown when the quiz cannot be loaded.'],
    'lms.mission_detail.alert.save_homework' => ['text' => 'Impossible d enregistrer ce homework pour le moment.', 'context' => 'Alert shown when homework completion cannot be saved.'],
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

if (empty($accessContext['exists']) || empty($accessContext['canView'])) {
	http_response_code(empty($accessContext['isLoggedIn']) ? 401 : 403);
	echo lmsMissionDetailT('lms.mission_detail.error.access_denied');
	exit;
}

$parcoursMission = new \dbObject\ParcoursMission();
if (!$parcoursMission->load([
	['IDparcours', $parcours_id],
	['IDmission', $mission_id],
])) {
	http_response_code(404);
	echo lmsMissionDetailT('lms.mission_detail.error.not_found');
	exit;
}

$mission = new \dbObject\Mission();
$m = false;
$quizCount = 0;
$homeworks = [];

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
		$parcours_id
	);
}

function vimeoEmbedUrl($url) {
	$url = trim((string)$url);

	if ($url === '') {
		return null;
	}

	if (preg_match('#videos/(\d+)/([a-zA-Z0-9]+)#', $url, $matches)) {
		$videoId = $matches[1];
		$hash = $matches[2];

		return "https://player.vimeo.com/video/$videoId?h=$hash";
	}

	if (preg_match('#vimeo\.com/(?:video/)?(\d+)(?:$|[?/])#', $url, $matches)) {
		$videoId = $matches[1];

		return "https://player.vimeo.com/video/$videoId";
	}

	return null;
}

$homeworksJson = json_encode($homeworks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($homeworksJson === false) {
	$homeworksJson = '[]';
}

echo "<div>";

if ($m) {
	$embedVideoUrl = vimeoEmbedUrl($m['video']);

	echo "<h2>" . htmlspecialchars($m['title']) . "</h2>";
	echo "<p><em>" . htmlspecialchars($m['resume']) . "</em></p>";
	echo "<div>";
?>
	<style>
	  button:disabled {
		  background: var(--disabled, color-mix(in srgb, var(--text-main, #1f2937) 18%, var(--bg-card, #ffffff)));
		  cursor: not-allowed;
		  opacity: 0.7;
	  }

	  .lms-login-invite {
		  display: flex;
		  align-items: center;
		  justify-content: space-between;
		  gap: 12px;
		  padding: 14px 16px;
		  border-top: 1px solid var(--border-color, #ddd);
		  background: color-mix(in srgb, var(--primary, #004663) 10%, var(--bg-card, #faf6e9));
	  }

	  .lms-login-invite p {
		  margin: 0;
		  color: var(--text-main, #5f4a11);
	  }

	  .lms-login-invite button {
		  border: 0;
		  border-radius: 10px;
		  padding: 10px 14px;
		  background: var(--color-primary, #004663);
		  color: var(--color-text-inverse, #fff);
		  cursor: pointer;
	  }

	  .lms-homework-section {
		  margin-top: 24px;
		  padding: 18px;
		  border: 1px solid var(--border-color, #d8e0e8);
		  border-radius: 18px;
		  background: var(--bg-header, #f8fbfd);
	  }

	  .lms-homework-section h3 {
		  margin: 0 0 14px;
		  font-size: 1.05rem;
		  color: var(--text-main, #22313f);
	  }

	  .lms-homework-list {
		  display: flex;
		  flex-direction: column;
		  gap: 12px;
	  }

	  .lms-homework-item {
		  border: 1px solid var(--border-color, #d9e3ea);
		  border-radius: 14px;
		  background: var(--bg-card, #fff);
		  overflow: hidden;
	  }

	  .lms-homework-item.is-done {
		  border-color: color-mix(in srgb, var(--primary, #004663) 45%, var(--border-color, #d9e3ea));
		  background: color-mix(in srgb, var(--primary, #004663) 10%, var(--bg-card, #ffffff));
	  }

	  .lms-homework-row {
		  display: flex;
		  align-items: center;
		  justify-content: space-between;
		  gap: 12px;
		  padding: 14px 16px;
		  cursor: pointer;
	  }

	  .lms-homework-summary {
		  display: flex;
		  align-items: center;
		  gap: 12px;
		  min-width: 0;
		  flex: 1;
	  }

	  .lms-homework-text {
		  min-width: 0;
	  }

	  .lms-homework-title {
		  font-weight: 600;
		  color: var(--text-main, #22313f);
	  }

	  .lms-homework-meta {
		  margin-top: 4px;
		  font-size: 0.9rem;
		  color: var(--text-light, #5f6f7f);
	  }

	  .lms-homework-actions {
		  display: flex;
		  align-items: center;
		  gap: 8px;
		  flex: 0 0 auto;
	  }

	  .lms-homework-check {
		  position: relative;
		  width: 28px;
		  height: 28px;
		  border: 2px solid color-mix(in srgb, var(--border-color, #90a4b4) 85%, var(--text-light, #5f6f7f));
		  border-radius: 8px;
		  background: var(--bg-card, #fff);
		  cursor: pointer;
		  margin: 0;
		  flex: 0 0 auto;
		  transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
	  }

	  .lms-homework-check::after {
		  content: "";
		  position: absolute;
		  left: 8px;
		  top: 3px;
		  width: 8px;
		  height: 14px;
		  border-right: 3px solid transparent;
		  border-bottom: 3px solid transparent;
		  transform: rotate(45deg);
	  }

	  .lms-homework-check:hover {
		  border-color: color-mix(in srgb, var(--primary, #004663) 55%, var(--border-color, #5f7d92));
		  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary, #004663) 10%, transparent);
	  }

	  .lms-homework-check.is-done {
		  border-color: var(--primary, #4d9a76);
		  background: var(--primary, #4d9a76);
		  color: var(--color-text-inverse, #fff);
	  }

	  .lms-homework-check.is-done::after {
		  border-right-color: var(--color-text-inverse, #fff);
		  border-bottom-color: var(--color-text-inverse, #fff);
	  }

	  .lms-homework-expand {
		  border: 1px solid var(--border-color, #c9d5df);
		  border-radius: 10px;
		  padding: 8px 12px;
		  background: var(--bg-card, #fff);
		  color: var(--text-main, #22313f);
		  cursor: pointer;
		  margin: 0;
	  }

	  .lms-homework-expand {
		  min-width: 140px;
		  font-size: 0.95rem;
		  line-height: 1.2;
	  }

	  .lms-homework-detail {
		  padding: 0 16px 16px 39px;
		  color: var(--text-main, #3b4d5d);
		  line-height: 1.5;
		  border-top: 1px solid color-mix(in srgb, var(--border-color, #d8e0e8) 60%, transparent);
	  }

	  .lms-homework-detail[hidden] {
		  display: none;
	  }

	  .lms-homework-help {
		  margin: 14px 0 0;
		  color: var(--text-light, #5f6f7f);
		  font-size: 0.92rem;
	  }

	  .quiz-info {
		  padding: 14px 16px;
		  border-radius: 14px;
		  border: 1px solid var(--border-color, #d8e0e8);
		  background: var(--bg-header, #eff5f8);
		  color: var(--text-main, #264052);
		  margin-top: 16px;
	  }

	  #quiz-zone {
		  margin-top: 16px;
		  background: transparent;
	  }

	  #quiz-zone:empty {
		  display: none;
	  }

	  .quiz {
		  padding: 18px;
		  border: 1px solid var(--border-color, #d8e0e8);
		  border-radius: 18px;
		  background: var(--bg-card, #ffffff);
		  color: var(--text-main, #22313f);
	  }

	  .quiz strong {
		  display: block;
		  margin-bottom: 10px;
		  color: var(--text-main, #22313f);
	  }

	  .quiz p {
		  margin: 0 0 12px;
		  color: var(--text-main, #22313f);
	  }

	  .quiz small {
		  display: block;
		  margin: 0 0 14px;
		  color: var(--text-light, #5f6f7f);
	  }

	  .quiz label {
		  display: flex;
		  align-items: flex-start;
		  gap: 10px;
		  margin: 10px 0 0;
		  padding: 12px 14px;
		  border: 1px solid var(--border-color, #d8e0e8);
		  border-radius: 12px;
		  background: color-mix(in srgb, var(--bg-card, #ffffff) 82%, var(--bg-header, #f8fbfd));
		  color: var(--text-main, #22313f);
		  cursor: pointer;
		  transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
	  }

	  .quiz label:hover {
		  border-color: color-mix(in srgb, var(--primary, #004663) 45%, var(--border-color, #d8e0e8));
		  box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary, #004663) 10%, transparent);
	  }

	  .quiz input[name="qcm"] {
		  margin: 2px 0 0;
		  accent-color: var(--primary, #004663);
		  flex: 0 0 auto;
	  }

	  #doneBtn {
		  background: var(--primary, #004663);
		  color: var(--color-text-inverse, #ffffff);
		  border: 1px solid color-mix(in srgb, var(--primary, #004663) 72%, transparent);
	  }
	</style>
<?php
	if ($embedVideoUrl) {
?>
	<style>
		body {
			font-family: Arial, sans-serif;
			margin: 0;
			padding: 20px;
		}

		* {
			box-sizing: border-box;
		}

		.video-portal {
			--video-aspect-ratio: 16 / 9;
			--video-ratio-number: 1.7777778;
			position: relative;
			width: min(100%, 960px, calc(50dvh * var(--video-ratio-number)));
			font-family: sans-serif;
			overflow: hidden;
			margin: auto;
		}

		.video-inner {
			position: relative;
			aspect-ratio: var(--video-aspect-ratio);
		}

		.video-inner iframe {
			position: absolute;
			width: 100%;
			height: 100%;
			top: 0;
			left: 0;
		}

		.branding-overlay {
			position: absolute;
			inset: 0;
			pointer-events: none;
			background: url("<?= htmlspecialchars(omoLmsBuildPath('/branding-client.png'), ENT_QUOTES, 'UTF-8') ?>") center/contain no-repeat;
		}

		.video-portal {
			position: relative;
			max-width: 960px;
			font-family: sans-serif;
			overflow: hidden;
		}

		.custom-controls {
			position: absolute;
			left: 0;
			bottom: 0;
			width: 100%;
			padding: 12px;
			background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
			display: flex;
			align-items: center;
			gap: 10px;
			opacity: 0;
			transform: translateY(10px);
			transition: opacity 0.25s ease, transform 0.25s ease;
		}

		.video-portal:hover .custom-controls {
			opacity: 1;
			transform: translateY(0);
		}

		.custom-controls button {
			border: none;
			padding: 5px 10px;
			cursor: pointer;
		}

		.volume-controls {
			display: flex;
			align-items: center;
			gap: 8px;
			margin-left: 12px;
		}

		.volume-controls input[type="range"] {
			width: 96px;
			cursor: pointer;
		}

		.progressvideo {
			flex: 1;
			height: 6px;
			background: #444;
			cursor: pointer;
			position: relative;
		}

		.progressvideo-bar {
			height: 100%;
			width: 0%;
			background: #fff;
		}

		@media (max-width: 640px) {
			.video-portal {
				width: min(100%, calc(42dvh * var(--video-ratio-number)));
			}

			.video-portal .custom-controls {
				position: static;
				width: auto;
				opacity: 1;
				transform: none;
				padding: 8px 10px;
				gap: 8px;
				flex-wrap: wrap;
				align-items: center;
				background: color-mix(in srgb, var(--bg-card, #111827) 88%, #000);
				border-top: 1px solid color-mix(in srgb, var(--border-color, #374151) 55%, transparent);
			}

			.video-portal .custom-controls button {
				padding: 6px 10px;
				min-width: 0;
				min-height: 34px;
				border-radius: 999px;
				font-size: 0.9rem;
			}

			.video-portal #playBtn,
			.video-portal #volumeBtn {
				flex: 0 0 auto;
			}

			.video-portal .progressvideo {
				order: 10;
				flex: 1 0 100%;
				height: 6px;
			}

			.video-portal #time {
				font-size: 0.82rem;
				opacity: 0.9;
			}

			.video-portal .volume-controls {
				margin-left: auto;
				gap: 6px;
				flex: 0 1 auto;
			}

			.video-portal .volume-controls input[type="range"] {
				width: 78px;
			}
		}

		@media (max-width: 420px) {
			.video-portal .custom-controls {
				padding: 7px 8px;
				gap: 6px;
			}

			.video-portal .custom-controls button {
				padding: 5px 8px;
				min-height: 32px;
				font-size: 0.85rem;
			}

			.video-portal .volume-controls input[type="range"] {
				width: 64px;
			}
		}
	</style>

	<div class="video-portal">
		<div class="video-inner">
			<iframe
				id="vimeoPlayer"
				src="<?php echo htmlspecialchars($embedVideoUrl . (strpos($embedVideoUrl, '?') === false ? '?' : '&') . 'controls=0', ENT_QUOTES, 'UTF-8'); ?>"
				frameborder="0"
				allow="autoplay; fullscreen; picture-in-picture"
				allowfullscreen>
			</iframe>
		</div>

		<div class="branding-overlay"></div>

		<div class="custom-controls">
			<button id="playBtn"><?= htmlspecialchars(lmsMissionDetailT('lms.mission_detail.video.play')); ?></button>
			<div class="progressvideo">
				<div class="progressvideo-bar"></div>
			</div>
			<span id="time">0:00</span>
			<div class="volume-controls">
				<button id="volumeBtn" type="button"><?= htmlspecialchars(lmsMissionDetailT('lms.mission_detail.video.sound')); ?></button>
				<input id="volumeSlider" type="range" min="0" max="100" step="1" value="100" aria-label="Volume">
			</div>
		</div>
	</div>

<?php
	} elseif ($m['video']) {
?>
	<p><?= htmlspecialchars(lmsMissionDetailT('lms.mission_detail.video.unavailable')); ?></p>
<?php
	}
	echo $m['html'];
	echo "<div id='homework-section'></div>";
	echo "</div>";
	echo "<div data-quiz-count='$quizCount' data-homework-count='" . count($homeworks) . "' id='quiz-info'></div>";
} else {
	echo lmsMissionDetailT('lms.mission_detail.error.not_found');
}
?>
<script>
(() => {
	const lmsMissionText = <?php echo json_encode([
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
		'alertSaveHomework' => lmsMissionDetailT('lms.mission_detail.alert.save_homework'),
		'alertValidateMission' => lmsMissionDetailT('lms.mission_detail.alert.validate_mission'),
		'notFound' => lmsMissionDetailT('lms.mission_detail.error.not_found'),
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

	function formatMissionText(template, replace) {
		let output = String(template || '');
		Object.keys(replace || {}).forEach((key) => {
			output = output.replace(new RegExp('\\{' + key + '\\}', 'g'), String(replace[key]));
		});
		return output;
	}

	if (typeof initVideoPlayer === 'function') {
		initVideoPlayer();
	}

	let currentQuestions = [];
	let currentIndex = 0;
	let currentMission = null;
	let quizMode = false;
	const lmsMissionId = <?php echo (int)$mission_id; ?>;
	const lmsMissionViewerCanTrack = <?php echo $canTrackProgress ? 'true' : 'false'; ?>;
	const lmsMissionViewerIsAnonymous = <?php echo $isAnonymousViewer ? 'true' : 'false'; ?>;
	const lmsMissionHomeworks = <?php echo $homeworksJson; ?>;
	const homeworkExpandedState = {};

	function buildLmsUrlWithParams(baseUrl, params) {
		const targetUrl = new URL(String(baseUrl || ''), window.location.origin);

		Object.keys(params || {}).forEach(function (key) {
			const value = params[key];
			if (value === null || value === undefined || value === '') {
				return;
			}

			targetUrl.searchParams.set(key, String(value));
		});

		return targetUrl.pathname + targetUrl.search + targetUrl.hash;
	}

	function escapeHtml(value) {
		return String(value ?? '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function getHomeworkDoneIds() {
		if (lmsMissionViewerIsAnonymous && typeof getAnonymousDoneHomeworkIds === 'function') {
			return getAnonymousDoneHomeworkIds(lmsMissionId);
		}

		return lmsMissionHomeworks
			.filter(homework => !!homework.is_done)
			.map(homework => Number(homework.id))
			.filter(homeworkId => Number.isInteger(homeworkId) && homeworkId > 0);
	}

	function isHomeworkDone(homework) {
		const doneLookup = new Set(getHomeworkDoneIds());
		return doneLookup.has(Number(homework.id));
	}

	function getDoneHomeworkCount() {
		return lmsMissionHomeworks.filter(homework => isHomeworkDone(homework)).length;
	}

	function areAllHomeworksDone() {
		return lmsMissionHomeworks.length === 0 || getDoneHomeworkCount() >= lmsMissionHomeworks.length;
	}

	function renderHomeworkList() {
		const section = document.getElementById('homework-section');
		if (!section) {
			return;
		}

		if (!Array.isArray(lmsMissionHomeworks) || lmsMissionHomeworks.length === 0) {
			section.innerHTML = '';
			section.style.display = 'none';
			return;
		}

		section.style.display = '';

		let html = `
			<section class="lms-homework-section">
				<h3>${escapeHtml(lmsMissionText.homeworksTitle || '')}</h3>
				<div class="lms-homework-list">
		`;

		lmsMissionHomeworks.forEach(homework => {
			const homeworkId = Number(homework.id || 0);
			const detailOpen = !!homeworkExpandedState[homeworkId];
			const isDone = isHomeworkDone(homework);
			const detailHtml = escapeHtml(homework.detail || '').replace(/\n/g, '<br>');

			html += `
				<div class="lms-homework-item${isDone ? ' is-done' : ''}" data-homework-id="${homeworkId}">
					<div class="lms-homework-row">
						<div class="lms-homework-summary">
							${lmsMissionViewerCanTrack ? `<button type="button" class="lms-homework-check${isDone ? ' is-done' : ''}" data-homework-check="${homeworkId}" aria-label="${escapeHtml(isDone ? (lmsMissionText.homeworkMarkUndone || '') : (lmsMissionText.homeworkMarkDone || ''))}" title="${escapeHtml(isDone ? (lmsMissionText.homeworkMarkUndone || '') : (lmsMissionText.homeworkMarkDone || ''))}" ${quizMode ? 'disabled' : ''}></button>` : ''}
							<div class="lms-homework-text">
								<div class="lms-homework-title">${escapeHtml(homework.title || '')}</div>
								<div class="lms-homework-meta">${isDone ? escapeHtml(lmsMissionText.homeworkDone || '') : escapeHtml(lmsMissionText.homeworkTodo || '')}</div>
							</div>
						</div>
						<div class="lms-homework-actions">
							<button type="button" class="lms-homework-expand" data-homework-expand="${homeworkId}" aria-expanded="${detailOpen ? 'true' : 'false'}">${detailOpen ? escapeHtml(lmsMissionText.homeworkCollapse || '') : escapeHtml(lmsMissionText.homeworkExpand || '')}</button>
						</div>
					</div>
					<div class="lms-homework-detail" ${detailOpen ? '' : 'hidden'}>
						${detailHtml !== '' ? detailHtml : escapeHtml(lmsMissionText.homeworkEmptyDetail || '')}
					</div>
				</div>
			`;
		});

		html += `
				</div>
				<p class="lms-homework-help">${escapeHtml(lmsMissionText.homeworkHelp || '')}</p>
			</section>
		`;

		section.innerHTML = html;

		document.querySelectorAll('[data-homework-expand]').forEach(button => {
			button.onclick = () => {
				const homeworkId = Number(button.getAttribute('data-homework-expand') || 0);
				homeworkExpandedState[homeworkId] = !homeworkExpandedState[homeworkId];
				renderHomeworkList();
			};
		});

		document.querySelectorAll('.lms-homework-row').forEach(row => {
			row.onclick = event => {
				if (event.target.closest('[data-homework-check]')) {
					return;
				}
				if (event.target.closest('[data-homework-expand]')) {
					return;
				}

				const container = row.closest('[data-homework-id]');
				if (!container) {
					return;
				}

				const homeworkId = Number(container.getAttribute('data-homework-id') || 0);
				homeworkExpandedState[homeworkId] = !homeworkExpandedState[homeworkId];
				renderHomeworkList();
			};
		});

		document.querySelectorAll('[data-homework-check]').forEach(button => {
			button.onclick = event => {
				event.stopPropagation();
				const homeworkId = Number(button.getAttribute('data-homework-check') || 0);
				const homework = lmsMissionHomeworks.find(item => Number(item.id) === homeworkId);
				if (!homework) {
					return;
				}

				setHomeworkDone(homework, !isHomeworkDone(homework));
			};
		});
	}

	function updateMissionValidationState() {
		const quizZone = document.getElementById('quiz-zone');
		const doneBtn = document.getElementById('doneBtn');
		const quizCount = parseInt(document.getElementById('quiz-info').dataset.quizCount || 0, 10);

		renderHomeworkList();

		if (!lmsMissionViewerCanTrack) {
			quizZone.innerHTML = `
				<div class="lms-login-invite">
					<p>${escapeHtml(lmsMissionText.validationUnavailable || '')}</p>
				</div>
			`;
			doneBtn.style.display = 'none';
			return;
		}

		doneBtn.style.display = '';

		if (quizMode) {
			return;
		}

		const allHomeworksDone = areAllHomeworksDone();
		const remainingHomeworks = Math.max(0, lmsMissionHomeworks.length - getDoneHomeworkCount());

		doneBtn.disabled = !allHomeworksDone;
		doneBtn.textContent = quizCount > 0 ? String(lmsMissionText.startQuiz || '') : String(lmsMissionText.markRead || '');

		if (!allHomeworksDone) {
			quizZone.innerHTML = `
				<div class="quiz-info">
					${escapeHtml(formatMissionText(lmsMissionText.remaining || '', { count: remainingHomeworks, suffix: remainingHomeworks > 1 ? 's' : '' }))}
				</div>
			`;
			return;
		}

		if (quizCount > 0) {
			quizZone.innerHTML = `
				<div class="quiz-info">
					${escapeHtml(formatMissionText(lmsMissionText.quizInfo || '', { count: quizCount, suffix: quizCount > 1 ? 's' : '' }))}
				</div>
			`;
			return;
		}

		quizZone.innerHTML = '';
	}

	function initMissionUI() {
		quizMode = false;
		updateMissionValidationState();
	}

	function setHomeworkDone(homework, done) {
		if (!lmsMissionViewerCanTrack || quizMode) {
			return;
		}

		if (lmsMissionViewerIsAnonymous) {
			if (typeof setAnonymousHomeworkDone === 'function') {
				setAnonymousHomeworkDone(lmsMissionId, homework.id, done);
			}
			homework.is_done = done;
			updateMissionValidationState();
			return;
		}

		fetch(<?php echo json_encode(lmsBuildLocalPath('/homework_action.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: `mission_id=${encodeURIComponent(lmsMissionId)}&parcours_id=${encodeURIComponent(parcoursId)}&homework_id=${encodeURIComponent(homework.id)}&done=${done ? '1' : '0'}`
		})
		.then(res => {
			if (!res.ok) {
				throw new Error('homework_save_failed');
			}

			homework.is_done = done;
			updateMissionValidationState();
		})
		.catch(() => {
			alert(String(lmsMissionText.alertSaveHomework || ''));
		});
	}

	function startValidation(missionId) {
		currentMission = missionId;
		quizMode = true;
		renderHomeworkList();

		fetch(buildLmsUrlWithParams(
			<?php echo json_encode(lmsBuildLocalPath('/getMissionQuestions.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
			{
				mission_id: missionId,
				parcours_id: parcoursId
			}
		))
			.then(res => res.json())
			.then(data => {
				if (!data || data.length === 0) {
					completeMission();
					return;
				}

				currentQuestions = data;
				currentIndex = 0;

				showQuestion();
			})
			.catch(() => {
				quizMode = false;
				updateMissionValidationState();
				alert(String(lmsMissionText.alertLoadQuiz || ''));
			});
	}

	function showQuestion() {
		let q = currentQuestions[currentIndex];

		let html = `
			<div class="quiz">
				<strong>${escapeHtml(formatMissionText(lmsMissionText.quizCounter || '', { current: currentIndex + 1, total: currentQuestions.length }))}</strong>
				<p>${q.question}</p>
		`;

		if (q.multiple) {
			html += `<small>${escapeHtml(lmsMissionText.quizMultiple || '')}</small>`;
		}

		q.choices.forEach(c => {
			html += `
				<label>
					<input type="${q.multiple ? 'checkbox' : 'radio'}" name="qcm" value="${c.id}">
					${c.label}
				</label>
			`;
		});

		html += `</div>`;

		document.getElementById('quiz-zone').innerHTML = html;

		const doneBtn = document.getElementById('doneBtn');
		doneBtn.disabled = true;

		setTimeout(() => {
			document.querySelectorAll('input[name="qcm"]').forEach(i => {
				i.addEventListener('change', () => {
					document.getElementById('doneBtn').disabled = false;
				});
			});
		}, 0);

		if (currentIndex === currentQuestions.length - 1) {
			doneBtn.textContent = String(lmsMissionText.quizFinish || '');
		} else {
			doneBtn.textContent = String(lmsMissionText.quizSubmit || '');
		}
	}

	function submitAnswer() {
		let inputs = document.querySelectorAll('input[name="qcm"]:checked');
		let selected = Array.from(inputs).map(i => i.value);

		if (selected.length === 0) {
			alert(String(lmsMissionText.quizSelectAnswer || ''));
			return;
		}

		fetch('checkAnswer.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ choices: selected })
		})
		.then(res => res.json())
		.then(data => {
			if (data.correct) {
				currentIndex++;

				if (currentIndex >= currentQuestions.length) {
					completeMission();
				} else {
					showQuestion();
				}
			} else {
				alert(String(lmsMissionText.quizWrongAnswer || ''));
			}
		});
	}

	function completeMission() {
		const missionId = currentMission || lmsMissionId || currentMissionId;

		if (!missionId) {
			alert(String(lmsMissionText.notFound || ''));
			return;
		}

		const doneHomeworkIds = getHomeworkDoneIds();

		fetch(<?php echo json_encode(lmsBuildLocalPath('/action.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: `mission_id=${encodeURIComponent(missionId)}&parcours_id=${encodeURIComponent(parcoursId)}&done_homework_ids=${encodeURIComponent(doneHomeworkIds.join(','))}`
		})
		.then(res => {
			if (!res.ok) {
				throw new Error('save_failed');
			}

			if (typeof rememberAnonymousMission === 'function') {
				rememberAnonymousMission(missionId);
			}

			document.getElementById('quiz-zone').innerHTML = '';
			closeDrawer();
			loadMissions();
		})
		.catch(() => {
			quizMode = false;
			updateMissionValidationState();
			alert(String(lmsMissionText.alertValidateMission || ''));
		});
	}

	document.getElementById('doneBtn').onclick = () => {
		if (!lmsMissionViewerCanTrack) {
			return;
		}

		if (!currentMissionId && !lmsMissionId) {
			return;
		}

		if (!areAllHomeworksDone()) {
			return;
		}

		const quizCount = parseInt(document.getElementById('quiz-info').dataset.quizCount || 0, 10);

		if (quizCount === 0) {
			completeMission();
			return;
		}

		if (!quizMode) {
			startValidation(lmsMissionId || currentMissionId);
			return;
		}

		submitAnswer();
	};
	initMissionUI();
	window.initMissionUI = initMissionUI;
})();
</script>
</div>
