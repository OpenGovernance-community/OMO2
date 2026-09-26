window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/lms/mission-detail.js"] = function (pageConfig, pageScript) {
(() => {
	if (typeof initVideoPlayer === 'function') {
		initVideoPlayer();
	}

	let currentQuestions = [];
	let currentIndex = 0;
	let currentMission = null;
	let quizMode = false;
	const lmsMissionId = pageConfig.lmsMissionId;
	const lmsMissionViewerCanTrack = pageConfig.lmsMissionViewerCanTrack;
	const lmsMissionViewerIsAnonymous = pageConfig.lmsMissionViewerIsAnonymous;
	const lmsMissionHomeworks = pageConfig.lmsMissionHomeworks;
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
			const detailHtml = String(homework.detail || '');

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
						window.lmsOpenLoginDrawer(pageConfig.parcoursId);
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
	initMissionUI();
	window.initMissionUI = initMissionUI;
})();
};
