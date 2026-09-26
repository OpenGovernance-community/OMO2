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
<?= commonPageScriptTags('/lms/inc/parcours-content.js', [
    'parcoursId' => (int)$parcours_id,
    'userId' => (int)$user_id,
    'organizationId' => (int)$org['id'],
    'isAnonymousViewer' => ($isAnonymousViewer),
    'canTrackProgress' => ($canTrackProgress),
    'url' => lmsBuildLocalPath('/lms/getmissions.php'),
    'text' => lmsBuildLocalPath('/lms/getmissions_done.php'),
    'text2' => lmsBuildLocalPath('/lms/getmissions_next.php'),
    'text3' => lmsBuildLocalPath('/lms/action.php'),
], 'publicLmsParcoursConfig') ?>

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
