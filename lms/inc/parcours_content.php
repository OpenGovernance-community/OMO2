<?php
$isPackParcours = $parcoursRef instanceof \dbObject\Parcours && $parcoursRef->isPack();
$showAdminPreviewCards = lmsCurrentUserHasExplicitOrganizationAdminMode((int)$org['id']);
$packChildren = $isPackParcours
    ? \dbObject\Parcours::fetchPackChildrenForOrganizationWithProgress(
        (int)$org['id'],
        (int)$parcours_id,
        (int)$user_id,
        (bool)commonUserHasOrganizationAccess((int)$user_id, (int)$org['id']),
        $showAdminPreviewCards
    )
    : [];
?>
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
        <h2>Parcours du pack</h2>
        <p>
            <?php if ($showAdminPreviewCards): ?>
                Les cartes grisees correspondent aux parcours encore masques dans la vue normale des membres.
            <?php else: ?>
                Seuls les parcours compatibles avec les applications actives dans cette organisation sont affiches.
            <?php endif; ?>
        </p>
    </div>

    <?php if (count($packChildren) === 0): ?>
        <div class="lms-pack-children__empty">Aucun parcours visible n est actuellement disponible dans ce pack.</div>
    <?php else: ?>
        <div class="missions lms-pack-children__grid">
            <?php foreach ($packChildren as $childParcours): ?>
                <?php
                $total = (int)($childParcours['total_missions'] ?? 0);
                $done = (int)($childParcours['done_missions'] ?? 0);
                $percent = $total > 0 ? (int)round(($done / $total) * 100) : 0;
                $isPreviewOnly = $showAdminPreviewCards && lmsParcoursIsPreviewOnly($childParcours);
                ?>
                <div class="card<?php echo $isPreviewOnly ? ' is-preview-only' : ''; ?>" onclick="<?php echo $isPreviewOnly ? 'return false;' : 'goToPackChildParcours(' . (int)($childParcours['id'] ?? 0) . ')'; ?>">
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars((string)($childParcours['title'] ?? '')); ?></h3>
                        <?php if (trim((string)($childParcours['description'] ?? '')) !== ''): ?>
                            <p><?php echo htmlspecialchars((string)$childParcours['description']); ?></p>
                        <?php endif; ?>
                        <div class="card-footer">
                            <span class="card-meta"><?php echo $isPreviewOnly ? htmlspecialchars(lmsParcoursPreviewLabel($childParcours)) : $percent . '% termine'; ?></span>
                            <button type="button" class="open-btn" onclick="<?php echo $isPreviewOnly ? 'return false;' : 'event.stopPropagation(); goToPackChildParcours(' . (int)($childParcours['id'] ?? 0) . ')'; ?>" <?php echo $isPreviewOnly ? 'disabled' : ''; ?>>
                                <?php echo $isPreviewOnly ? 'Masque' : 'Ouvrir'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="view-switch">
    <button onclick="setView('todo')" id="btnTodo" class="active">Mes missions</button>
    <button onclick="setView('done')" id="btnDone">Terminees</button>
    <button onclick="setView('next')" id="btnNext">A venir</button>
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
        <?php echo json_encode(lmsBuildLocalPath('/lms/parcours.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
        {
            idp: parcoursId,
            embed: <?php echo $isEmbedded ? "'1'" : "''"; ?>
        }
    );

    window.location.href = targetUrl;
}
</script>

<?php if (!$isPackParcours): ?>
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
    let url = <?php echo json_encode(lmsBuildLocalPath('/lms/getmissions.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

    if (currentView === 'done') url = <?php echo json_encode(lmsBuildLocalPath('/lms/getmissions_done.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    if (currentView === 'next') url = <?php echo json_encode(lmsBuildLocalPath('/lms/getmissions_next.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

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

    fetch(<?php echo json_encode(lmsBuildLocalPath('/lms/action.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>, {
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

    fetch(buildLmsUrlWithParams(
        <?php echo json_encode(lmsBuildLocalPath('/lms/getMissionDetail.php'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
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
</script>
<?php endif; ?>
