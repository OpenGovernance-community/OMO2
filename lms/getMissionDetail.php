<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';
require_once BASE_PATH . '/common/auth.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

$mission_id = (int)($_GET['mission_id'] ?? 0);
$parcours_id = (int)($_GET['parcours_id'] ?? 0);
$accessContext = lmsGetParcoursAccessContext((int)$org['id'], $parcours_id);
$canTrackProgress = lmsCanTrackProgress($accessContext);
$isAnonymousViewer = lmsIsAnonymousViewer($accessContext);
$isOrganizationAdmin = !empty($accessContext['isLoggedIn'])
	? lmsCurrentUserIsOrganizationAdmin((int)$org['id'], (int)($accessContext['userId'] ?? 0))
	: false;

if (empty($accessContext['exists']) || empty($accessContext['canView'])) {
	http_response_code(empty($accessContext['isLoggedIn']) ? 401 : 403);
	echo "Acces refuse";
	exit;
}

$parcoursMission = new \dbObject\ParcoursMission();
if (!$parcoursMission->load([
	['IDparcours', $parcours_id],
	['IDmission', $mission_id],
])) {
	http_response_code(404);
	echo "Mission introuvable";
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
		$parcours_id,
		$isOrganizationAdmin
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

	  .lms-homework-detail > :first-child {
		  margin-top: 12px;
	  }

	  .lms-homework-detail > :last-child {
		  margin-bottom: 0;
	  }

	  .lms-homework-detail ul,
	  .lms-homework-detail ol {
		  margin: 10px 0 10px 20px;
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
			background: url("/lms/branding-client.png") center/contain no-repeat;
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
			<button id="playBtn">Lire</button>
			<div class="progressvideo">
				<div class="progressvideo-bar"></div>
			</div>
			<span id="time">0:00</span>
			<div class="volume-controls">
				<button id="volumeBtn" type="button">Son</button>
				<input id="volumeSlider" type="range" min="0" max="100" step="1" value="100" aria-label="Volume">
			</div>
		</div>
	</div>

<?php
	} elseif ($m['video']) {
?>
	<p>La video de cette mission n'est pas disponible pour le moment.</p>
<?php
	}
	echo $m['html'];
	echo "<div id='homework-section'></div>";
	echo "</div>";
	echo "<div data-quiz-count='$quizCount' data-homework-count='" . count($homeworks) . "' id='quiz-info'></div>";
} else {
	echo "Mission introuvable";
}
?>
<?= commonPageScriptTags('/lms/mission-detail.js', [
    'lmsMissionId' => (int)$mission_id,
    'lmsMissionViewerCanTrack' => ($canTrackProgress),
    'lmsMissionViewerIsAnonymous' => ($isAnonymousViewer),
    'lmsMissionHomeworks' => json_decode($homeworksJson, true, 512, JSON_THROW_ON_ERROR),
    'parcoursId' => lmsBuildLocalPath('/lms/parcours.php', ['idp' => $parcours_id]),
]) ?>
</div>
