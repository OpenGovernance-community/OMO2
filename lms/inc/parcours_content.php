<div class="content lms-parcours-content<?php echo $isEmbedded ? ' lms-parcours-content--embed' : ''; ?>">
<?php if ($isEmbedded): ?>
    <div class="lms-parcours-embed-header">
        <h1><?php echo htmlspecialchars($parcours['title']); ?></h1>
        <?php if ($parcours['description'] !== ''): ?>
            <p><?php echo htmlspecialchars($parcours['description']); ?></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="view-switch">
    <button onclick="setView('todo')" id="btnTodo" class="active">Mes missions</button>
    <button onclick="setView('done')" id="btnDone">Terminees</button>
    <button onclick="setView('next')" id="btnNext">A venir</button>
</div>
<div class="progress-container">
    <div class="progress-bar" id="progressBar"></div>
</div>
<div id="missions" class="missions"></div>

</div>

<?php
include __DIR__ . '/video.php';
include __DIR__ . '/drawer.php';
?>

<script>
let currentView = 'todo';
const parcoursId = <?php echo (int)$parcours_id; ?>;
const lmsViewer = {
    userId: <?php echo (int)$user_id; ?>,
    organizationId: <?php echo (int)$org['id']; ?>,
    isAnonymousViewer: <?php echo $isAnonymousViewer ? 'true' : 'false'; ?>,
    canTrackProgress: <?php echo $canTrackProgress ? 'true' : 'false'; ?>
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
    let url = '/lms/getmissions.php';

    if (currentView === 'done') url = '/lms/getmissions_done.php';
    if (currentView === 'next') url = '/lms/getmissions_next.php';

    fetch(`${url}?parcours_id=${parcoursId}${buildDoneIdsParam()}`)
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

    fetch('action.php', {
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
</script>

<script>
let currentMissionId = null;

function viewMission(missionId) {
    currentMissionId = missionId;

    fetch(`/lms/getMissionDetail.php?mission_id=${missionId}&parcours_id=${parcoursId}`)
        .then(res => res.text())
        .then(html => {
            openDrawer(html);
            initMissionUI();
        });
}
</script>
