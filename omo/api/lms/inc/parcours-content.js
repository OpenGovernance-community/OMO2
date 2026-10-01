
function initPackProgressCircles() {
    document.querySelectorAll('.lms-pack-children__grid .progress-circle').forEach((el) => {
        const percent = Math.max(0, Math.min(100, Number(el.getAttribute('data-percent') || 0)));
        const radius = 24;
        const circumference = 2 * Math.PI * radius;

        el.innerHTML = `
            <svg width="60" height="60" viewBox="0 0 60 60" aria-hidden="true">
                <circle class="bg" cx="30" cy="30" r="${radius}"></circle>
                <circle class="progress" cx="30" cy="30" r="${radius}"></circle>
            </svg>
            <div class="label">${percent}%</div>
        `;

        const progressCircle = el.querySelector('.progress');
        if (!progressCircle) {
            return;
        }

        progressCircle.style.strokeDasharray = String(circumference);
        progressCircle.style.strokeDashoffset = String(circumference * (1 - percent / 100));
    });
}

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

function goToPackChildParcours(parcoursId) {
    const targetUrl = buildLmsUrlWithParams(
        window.lmsParcoursPageConfig.text,
        {
            idp: parcoursId,
            embed: window.lmsParcoursPageConfig.embed,
            done_parcours_ids: (function () {
                if (typeof getAnonymousCompletedParcoursIds !== 'function') {
                    return '';
                }

                const completedParcoursIds = getAnonymousCompletedParcoursIds();
                return completedParcoursIds.length > 0 ? completedParcoursIds.join(',') : '';
            })()
        }
    );

    window.location.href = targetUrl;
}

if (window.lmsParcoursPageConfig.isPackParcours) {
initPackProgressCircles();
}


let currentView = 'todo';
const parcoursId = window.lmsParcoursPageConfig.parcoursId;
const lmsViewer = {
    userId: window.lmsParcoursPageConfig.userId,
    organizationId: window.lmsParcoursPageConfig.organizationId,
    isAnonymousViewer: window.lmsParcoursPageConfig.isAnonymousViewer,
    canTrackProgress: window.lmsParcoursPageConfig.canTrackProgress
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

function setAnonymousHomeworkDone(missionId, homeworkId, isDone) {
    if (!lmsViewer.isAnonymousViewer) {
        return;
    }

    const progress = readAnonymousProgress();
    const missionKey = String(missionId);
    const homeworkKey = String(homeworkId);

    if (!progress.homeworks || typeof progress.homeworks !== 'object') {
        progress.homeworks = {};
    }

    if (!progress.homeworks[missionKey] || typeof progress.homeworks[missionKey] !== 'object') {
        progress.homeworks[missionKey] = {};
    }

    if (isDone) {
        progress.homeworks[missionKey][homeworkKey] = new Date().toISOString();
    } else {
        delete progress.homeworks[missionKey][homeworkKey];
        if (Object.keys(progress.homeworks[missionKey]).length === 0) {
            delete progress.homeworks[missionKey];
        }
    }

    writeAnonymousProgress(progress);
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
    let url = window.lmsParcoursPageConfig.url;

    if (currentView === 'done') url = window.lmsParcoursPageConfig.text2;
    if (currentView === 'next') url = window.lmsParcoursPageConfig.text3;

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

    fetch(window.lmsParcoursPageConfig.text4, {
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

if (!window.lmsParcoursPageConfig.isPackParcours) loadMissions();

