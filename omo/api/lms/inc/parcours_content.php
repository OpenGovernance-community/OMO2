<?php
$sourceLang = [
    'lms.parcours_content.pack.title' => ['text' => 'Parcours du pack', 'context' => 'Section title shown when viewing a pack parcours.'],
    'lms.parcours_content.pack.intro' => ['text' => 'Seuls les parcours compatibles avec les applications actives dans cette organisation sont affiches.', 'context' => 'Intro text shown above the list of child parcours in a pack.'],
    'lms.parcours_content.pack.empty' => ['text' => 'Aucun parcours visible n est actuellement disponible dans ce pack.', 'context' => 'Empty state shown when a pack exposes no visible child parcours.'],
    'lms.parcours_content.pack.hidden_note' => ['text' => 'Actuellement masque pour les membres standard.', 'context' => 'Note shown on hidden child parcours cards.'],
    'lms.parcours_content.pack.open' => ['text' => 'Ouvrir', 'context' => 'Button used to open a visible child parcours.'],
    'lms.parcours_content.pack.hidden' => ['text' => 'Masque', 'context' => 'Disabled button label shown for hidden child parcours.'],
    'lms.parcours_content.views.todo' => ['text' => 'Mes missions', 'context' => 'Button used to show pending missions in a parcours.'],
    'lms.parcours_content.views.done' => ['text' => 'Terminees', 'context' => 'Button used to show completed missions in a parcours.'],
    'lms.parcours_content.views.next' => ['text' => 'A venir', 'context' => 'Button used to show upcoming missions in a parcours.'],
];

$lang = omoLoadTranslationBundle('omo_lms_parcours_content', $sourceLang);

function lmsParcoursContentT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$isPackParcours = $parcoursRef instanceof \dbObject\Parcours && $parcoursRef->isPack();
$canEditPackParcours = lmsCurrentUserCanEditParcours((int)$org['id'], (int)$user_id);
$packChildren = $isPackParcours
    ? \dbObject\Parcours::fetchPackChildrenForOrganizationWithProgress(
        (int)$org['id'],
        (int)$parcours_id,
        (int)$user_id,
        (bool)commonUserHasOrganizationAccess((int)$user_id, (int)$org['id']),
        $canEditPackParcours
    )
    : [];
?>
<?php if ($isPackParcours): ?>
<style>
.lms-pack-children__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 350px));
    justify-content: center;
    gap: 20px;
    align-items: stretch;
}

.lms-pack-children__grid .card {
    width: 100%;
    max-width: none;
    height: 100%;
    flex: none;
}

.lms-pack-children__grid .card-image {
    aspect-ratio: 5 / 2 !important;
    overflow: hidden;
}

.lms-pack-children__grid .card-image img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.lms-pack-children__grid .progress-circle {
    position: relative;
    width: 60px;
    height: 60px;
    display: inline-block;
    margin: 5px;
}

.lms-pack-children__grid .progress-circle svg {
    transform: rotate(-90deg);
}

.lms-pack-children__grid .progress-circle circle {
    fill: none;
    stroke-width: 5;
}

.lms-pack-children__grid .progress-circle .bg {
    stroke: var(--progress-bg);
}

.lms-pack-children__grid .progress-circle .progress {
    stroke: var(--primary);
    stroke-linecap: round;
    transition: stroke-dashoffset 0.6s ease;
}

.lms-pack-children__grid .progress-circle .label {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 13px;
    font-weight: 600;
    color: var(--text-main);
    white-space: nowrap;
}
</style>
<?php endif; ?>
<div class="content lms-parcours-content<?php echo $isEmbedded ? ' lms-parcours-content--embed' : ''; ?>">
<?php if ($isEmbedded): ?>
    <div class="lms-parcours-embed-header">
        <h1><?php echo htmlspecialchars($parcours['title']); ?></h1>
        <?php if ($parcours['description'] !== ''): ?>
            <p><?php echo htmlspecialchars($parcours['description']); ?></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($isPackParcours): ?>
<div class="lms-pack-children">
    <div class="lms-pack-children__intro">
        <h2 class="generic-title generic-title--section"><?php echo htmlspecialchars(lmsParcoursContentT('lms.parcours_content.pack.title')); ?></h2>
        <p class="generic-description"><?php echo htmlspecialchars(lmsParcoursContentT('lms.parcours_content.pack.intro')); ?></p>
    </div>

    <?php if (count($packChildren) === 0): ?>
        <div class="lms-pack-children__empty generic-description"><?php echo htmlspecialchars(lmsParcoursContentT('lms.parcours_content.pack.empty')); ?></div>
    <?php else: ?>
        <div class="missions lms-pack-children__grid">
            <?php foreach ($packChildren as $childParcours): ?>
                <?php
                $total = (int)($childParcours['total_missions'] ?? 0);
                $done = (int)($childParcours['done_missions'] ?? 0);
                $percent = $total > 0 ? (int)round(($done / $total) * 100) : 0;
                $isVisibleParcours = !empty($childParcours['isvisible']);
                ?>
                <div class="card<?php echo !$isVisibleParcours ? ' card--visibility-hidden' : ''; ?>" onclick="<?php echo $isVisibleParcours ? 'goToPackChildParcours(' . (int)($childParcours['id'] ?? 0) . ')' : ''; ?>">
                    <?php if (!empty($childParcours['image'])): ?>
                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars((string)$childParcours['image']); ?>" alt="">
                        </div>
                    <?php endif; ?>

                    <div class="card-content">
                        <h3><?php echo htmlspecialchars((string)($childParcours['title'] ?? '')); ?></h3>
                        <div><?php echo htmlspecialchars((string)($childParcours['description'] ?? '')); ?></div>
                        <?php if (!$isVisibleParcours): ?>
                            <div class="card-visibility-note"><?php echo htmlspecialchars(lmsParcoursContentT('lms.parcours_content.pack.hidden_note')); ?></div>
                        <?php endif; ?>
                        <div class="card-footer">
                            <div class="progress-circle" data-percent="<?php echo (int)$percent; ?>"></div>
                            <button type="button" class="open-btn" <?php echo $isVisibleParcours ? 'onclick="event.stopPropagation(); goToPackChildParcours(' . (int)($childParcours['id'] ?? 0) . ')"' : 'disabled'; ?>><?php echo htmlspecialchars($isVisibleParcours ? lmsParcoursContentT('lms.parcours_content.pack.open') : lmsParcoursContentT('lms.parcours_content.pack.hidden')); ?></button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="view-switch">
    <button onclick="setView('todo')" id="btnTodo" class="active"><?php echo htmlspecialchars(lmsParcoursContentT('lms.parcours_content.views.todo')); ?></button>
    <button onclick="setView('done')" id="btnDone"><?php echo htmlspecialchars(lmsParcoursContentT('lms.parcours_content.views.done')); ?></button>
    <button onclick="setView('next')" id="btnNext"><?php echo htmlspecialchars(lmsParcoursContentT('lms.parcours_content.views.next')); ?></button>
</div>
<div class="progress-container">
    <div class="progress-bar" id="progressBar"></div>
</div>
<div id="missions" class="missions"></div>
<?php endif; ?>

</div>

<?php
include __DIR__ . '/video.php';
include __DIR__ . '/drawer.php';
?>

<script>
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

function goToPackChildParcours(parcoursId) {
    const targetUrl = buildLmsUrlWithParams(
        <?php echo json_encode(lmsBuildLocalPath('/parcours.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
        {
            idp: parcoursId,
            embed: <?php echo $isEmbedded ? "'1'" : "''"; ?>,
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

<?php if ($isPackParcours): ?>
initPackProgressCircles();
<?php endif; ?>

<?php if (!$isPackParcours): ?>
let currentView = 'todo';
const parcoursId = <?php echo (int)$parcours_id; ?>;
const lmsViewer = {
    userId: <?php echo (int)$user_id; ?>,
    organizationId: <?php echo (int)$org['id']; ?>,
    isAnonymousViewer: <?php echo $isAnonymousViewer ? 'true' : 'false'; ?>,
    canTrackProgress: <?php echo $canTrackProgress ? 'true' : 'false'; ?>
};

let branchState = {};

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
    let url = <?php echo json_encode(lmsBuildLocalPath('/getmissions.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

    if (currentView === 'done') url = <?php echo json_encode(lmsBuildLocalPath('/getmissions_done.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    if (currentView === 'next') url = <?php echo json_encode(lmsBuildLocalPath('/getmissions_next.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

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

    fetch(<?php echo json_encode(lmsBuildLocalPath('/action.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>, {
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
<?php endif; ?>
</script>

<?php if (!$isPackParcours): ?>
<script>
let currentMissionId = null;
const initialMissionId = <?php echo (int)($initialMissionId ?? 0); ?>;

function viewMission(missionId) {
    currentMissionId = missionId;

    fetch(buildLmsUrlWithParams(
        <?php echo json_encode(lmsBuildLocalPath('/getMissionDetail.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
        {
            mission_id: missionId,
            parcours_id: parcoursId
        }
    ))
        .then(res => res.text())
        .then(html => {
            openDrawer(html);
        });
}

if (initialMissionId > 0) {
    window.setTimeout(function () {
        viewMission(initialMissionId);
    }, 80);
}
</script>
<?php endif; ?>
