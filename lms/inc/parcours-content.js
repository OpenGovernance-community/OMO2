
let currentView = 'todo';
const parcoursId = window.publicLmsParcoursConfig.parcoursId;
const lmsViewer = {
    userId: window.publicLmsParcoursConfig.userId,
    organizationId: window.publicLmsParcoursConfig.organizationId,
    isAnonymousViewer: window.publicLmsParcoursConfig.isAnonymousViewer,
    canTrackProgress: window.publicLmsParcoursConfig.canTrackProgress
};

let branchState = {};

function getAnonymousProgressKey() {
    return `lms_progress_${lmsViewer.organizationId}_${parcoursId}`;
}

function readAnonymousProgress() {
    if (!lmsViewer.isAnonymousViewer) {
        return { missions: {}, homeworks: {} };
    }

    try {
        const rawValue = localStorage.getItem(getAnonymousProgressKey());
        if (!rawValue) {
            return { missions: {}, homeworks: {} };
        }

        const parsed = JSON.parse(rawValue);
        if (!parsed || typeof parsed !== 'object') {
            return { missions: {}, homeworks: {} };
        }

        if (!parsed.missions || typeof parsed.missions !== 'object') {
            parsed.missions = {};
        }

        if (!parsed.homeworks || typeof parsed.homeworks !== 'object') {
            parsed.homeworks = {};
        }

        return parsed;
    } catch (error) {
        return { missions: {}, homeworks: {} };
    }
}

function writeAnonymousProgress(progress) {
    if (!lmsViewer.isAnonymousViewer) {
        return;
    }

    const payload = progress && typeof progress === 'object' ? progress : { missions: {}, homeworks: {} };
    if (!payload.missions || typeof payload.missions !== 'object') {
        payload.missions = {};
    }
    if (!payload.homeworks || typeof payload.homeworks !== 'object') {
        payload.homeworks = {};
    }

    payload.updatedAt = new Date().toISOString();
    localStorage.setItem(getAnonymousProgressKey(), JSON.stringify(payload));
}

function getAnonymousDoneMissionIds() {
    const progress = readAnonymousProgress();
    return Object.keys(progress.missions || {})
        .map(value => Number(value))
        .filter(value => Number.isInteger(value) && value > 0);
}

function rememberAnonymousMission(missionId) {
    if (!lmsViewer.isAnonymousViewer) {
        return;
    }

    const progress = readAnonymousProgress();
    progress.missions[String(missionId)] = new Date().toISOString();
    writeAnonymousProgress(progress);
}

function getAnonymousDoneHomeworkIds(missionId) {
    const progress = readAnonymousProgress();
    const missionKey = String(missionId);
    const homeworks = progress.homeworks && typeof progress.homeworks === 'object'
        ? progress.homeworks[missionKey]
        : null;

    if (!homeworks || typeof homeworks !== 'object') {
        return [];
    }

    return Object.keys(homeworks)
        .map(value => Number(value))
        .filter(value => Number.isInteger(value) && value > 0);
}

function buildDoneIdsParam() {
    if (!lmsViewer.isAnonymousViewer) {
        return '';
    }

    const doneIds = getAnonymousDoneMissionIds();
    return doneIds.length > 0 ? `&done_ids=${encodeURIComponent(doneIds.join(','))}` : '';
}

function setView(view) {
    currentView = view;

    document.getElementById('btnTodo').classList.remove('active');
    document.getElementById('btnDone').classList.remove('active');
    document.getElementById('btnNext').classList.remove('active');

    if (view === 'todo') {
        document.getElementById('btnTodo').classList.add('active');
    } else if (view === 'done') {
        document.getElementById('btnDone').classList.add('active');
    } else if (view === 'next') {
        document.getElementById('btnNext').classList.add('active');
    }

    loadMissions();
}

function loadMissions() {
    let url = window.publicLmsParcoursConfig.url;

    if (currentView === 'done') url = window.publicLmsParcoursConfig.text;
    if (currentView === 'next') url = window.publicLmsParcoursConfig.text2;

    const requestUrl = buildLmsUrlWithParams(url, {
        parcours_id: parcoursId
    }) + buildDoneIdsParam().replace(/^&/, '&');

    fetch(requestUrl)
        .then(res => res.json())
        .then(data => {
            document.getElementById('missions').innerHTML = data.html || '';

            if (data.progress !== undefined) {
                document.getElementById('progressBar').style.width = data.progress + '%';
            }

            restoreBranches();
        });
}

function markDone(missionId) {
    const doneHomeworkIds = typeof getAnonymousDoneHomeworkIds === 'function'
        ? getAnonymousDoneHomeworkIds(missionId)
        : [];

    fetch(window.publicLmsParcoursConfig.text3, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `mission_id=${missionId}&parcours_id=${parcoursId}&done_homework_ids=${encodeURIComponent(doneHomeworkIds.join(','))}`
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('save_failed');
        }

        rememberAnonymousMission(missionId);
        loadMissions();
    });
}

function toggleBranch(branchId) {
    const el = document.querySelector(`[data-branch-id="${branchId}"]`);

    if (!el) return;

    const isClosed = el.classList.toggle('closed');
    branchState[branchId] = isClosed;
}

function restoreBranches() {
    document.querySelectorAll('.branch').forEach(el => {
        const id = el.dataset.branchId;

        if (branchState[id]) {
            el.classList.add('closed');
        }
    });
}

loadMissions();

