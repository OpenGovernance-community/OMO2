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
		  background: #ccc;
		  cursor: not-allowed;
		  opacity: 0.7;
	  }

	  .lms-login-invite {
		  display: flex;
		  align-items: center;
		  justify-content: space-between;
		  gap: 12px;
		  padding: 14px 16px;
		  border-top: 1px solid #ddd;
		  background: #faf6e9;
	  }

	  .lms-login-invite p {
		  margin: 0;
		  color: #5f4a11;
	  }

	  .lms-login-invite button {
		  border: 0;
		  border-radius: 10px;
		  padding: 10px 14px;
		  background: var(--color-primary, #004663);
		  color: #fff;
		  cursor: pointer;
	  }

	  .lms-homework-section {
		  margin-top: 24px;
		  padding: 18px;
		  border: 1px solid #d8e0e8;
		  border-radius: 18px;
		  background: #f8fbfd;
	  }

	  .lms-homework-section h3 {
		  margin: 0 0 14px;
		  font-size: 1.05rem;
	  }

	  .lms-homework-list {
		  display: flex;
		  flex-direction: column;
		  gap: 12px;
	  }

	  .lms-homework-item {
		  border: 1px solid #d9e3ea;
		  border-radius: 14px;
		  background: #fff;
		  overflow: hidden;
	  }

	  .lms-homework-item.is-done {
		  border-color: #9bc7b5;
		  background: #f5fbf8;
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
		  color: #22313f;
	  }

	  .lms-homework-meta {
		  margin-top: 4px;
		  font-size: 0.9rem;
		  color: #5f6f7f;
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
		  border: 2px solid #90a4b4;
		  border-radius: 8px;
		  background: #fff;
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
		  border-color: #5f7d92;
		  box-shadow: 0 0 0 3px rgba(0, 70, 99, 0.08);
	  }

	  .lms-homework-check.is-done {
		  border-color: #4d9a76;
		  background: #4d9a76;
		  color: #fff;
	  }

	  .lms-homework-check.is-done::after {
		  border-right-color: #fff;
		  border-bottom-color: #fff;
	  }

	  .lms-homework-expand {
		  border: 1px solid #c9d5df;
		  border-radius: 10px;
		  padding: 8px 12px;
		  background: #fff;
		  color: #22313f;
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
		  color: #3b4d5d;
		  line-height: 1.5;
		  border-top: 1px solid #eef3f6;
	  }

	  .lms-homework-detail[hidden] {
		  display: none;
	  }

	  .lms-homework-help {
		  margin: 14px 0 0;
		  color: #5f6f7f;
		  font-size: 0.92rem;
	  }

	  .quiz-info {
		  padding: 14px 16px;
		  border-radius: 14px;
		  background: #eff5f8;
		  color: #264052;
		  margin-top: 16px;
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
			position: relative;
			max-width: 800px;
			font-family: sans-serif;
			overflow: hidden;
			margin: auto;
			max-height: 50dvh;
			aspect-ratio: 1.8;
		}

		.video-inner {
			padding-top: 56.25%;
			position: relative;
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
			background: url("/lms/branding-client.png") center/cover no-repeat;
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
<script>
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
				<h3>Homeworks</h3>
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
							${lmsMissionViewerCanTrack ? `<button type="button" class="lms-homework-check${isDone ? ' is-done' : ''}" data-homework-check="${homeworkId}" aria-label="${isDone ? 'Retirer la validation' : 'Valider la tache'}" title="${isDone ? 'Retirer la validation' : 'Valider la tache'}" ${quizMode ? 'disabled' : ''}></button>` : ''}
							<div class="lms-homework-text">
								<div class="lms-homework-title">${escapeHtml(homework.title || '')}</div>
								<div class="lms-homework-meta">${isDone ? 'Valide' : 'A faire'}</div>
							</div>
						</div>
						<div class="lms-homework-actions">
							<button type="button" class="lms-homework-expand" data-homework-expand="${homeworkId}" aria-expanded="${detailOpen ? 'true' : 'false'}">${detailOpen ? 'Masquer le detail' : 'Detail de la tache'}</button>
						</div>
					</div>
					<div class="lms-homework-detail" ${detailOpen ? '' : 'hidden'}>
						${detailHtml !== '' ? detailHtml : 'Aucun detail supplementaire.'}
					</div>
				</div>
			`;
		});

		html += `
				</div>
				<p class="lms-homework-help">Terminez tous les homeworks avant de poursuivre cette mission.</p>
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
					<p>Connectez-vous pour valider cette mission et enregistrer votre avancement.</p>
					<button type="button" id="missionLoginInviteBtn">Login</button>
				</div>
			`;
			doneBtn.style.display = 'none';

			const inviteBtn = document.getElementById('missionLoginInviteBtn');
			if (inviteBtn) {
				inviteBtn.onclick = () => {
					if (typeof closeDrawer === 'function') {
						closeDrawer();
					}
					if (typeof window.lmsOpenLoginDrawer === 'function') {
						window.lmsOpenLoginDrawer('/lms/parcours.php?idp=' + Number(parcoursId || 0));
					}
				};
			}
			return;
		}

		doneBtn.style.display = '';

		if (quizMode) {
			return;
		}

		const allHomeworksDone = areAllHomeworksDone();
		const remainingHomeworks = Math.max(0, lmsMissionHomeworks.length - getDoneHomeworkCount());

		doneBtn.disabled = !allHomeworksDone;
		doneBtn.textContent = quizCount > 0 ? "Commencer le quiz" : "Marquer comme lu";

		if (!allHomeworksDone) {
			quizZone.innerHTML = `
				<div class="quiz-info">
					Terminez encore ${remainingHomeworks} homework${remainingHomeworks > 1 ? 's' : ''} pour continuer.
				</div>
			`;
			return;
		}

		if (quizCount > 0) {
			quizZone.innerHTML = `
				<div class="quiz-info">
					Cette mission sera validee par ${quizCount} question${quizCount > 1 ? 's' : ''}
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

		fetch('homework_action.php', {
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
			alert("Impossible d'enregistrer ce homework pour le moment.");
		});
	}

	function startValidation(missionId) {
		currentMission = missionId;
		quizMode = true;
		renderHomeworkList();

		fetch(`getMissionQuestions.php?mission_id=${missionId}`)
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
				alert("Impossible de charger le quiz.");
			});
	}

	function showQuestion() {
		let q = currentQuestions[currentIndex];

		let html = `
			<div class="quiz">
				<strong>Question ${currentIndex + 1}/${currentQuestions.length}</strong>
				<p>${q.question}</p>
		`;

		if (q.multiple) {
			html += `<small>Plusieurs reponses possibles</small>`;
		}

		q.choices.forEach(c => {
			html += `
				<label>
					<input type="${q.multiple ? 'checkbox' : 'radio'}" name="qcm" value="${c.id}">
					${c.label}
				</label><br>
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
			doneBtn.textContent = "Terminer";
		} else {
			doneBtn.textContent = "Valider la reponse";
		}
	}

	function submitAnswer() {
		let inputs = document.querySelectorAll('input[name="qcm"]:checked');
		let selected = Array.from(inputs).map(i => i.value);

		if (selected.length === 0) {
			alert("Veuillez selectionner une reponse");
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
				alert("Mauvaise reponse");
			}
		});
	}

	function completeMission() {
		const missionId = currentMission || lmsMissionId || currentMissionId;

		if (!missionId) {
			alert("Mission introuvable");
			return;
		}

		const doneHomeworkIds = getHomeworkDoneIds();

		fetch('action.php', {
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
			alert("Impossible de valider cette mission pour le moment.");
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
</script>
</div>
