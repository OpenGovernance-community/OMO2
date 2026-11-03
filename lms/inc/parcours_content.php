<?php
require_once dirname(__DIR__, 2) . '/common/lms/ui-translations.php';
require_once dirname(__DIR__, 2) . '/common/lms/content-indicators.php';
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
$contentIndicators = \dbObject\Parcours::fetchContentIndicators(array_column($packChildren, 'id'));
?>
<div class="content lms-parcours-content<?php echo $isEmbedded ? ' lms-parcours-content--embed' : ''; ?>">
<?php if ($isEmbedded): ?>
    <div class="lms-parcours-embed-header">
        <?php commonLmsRenderCatalogBackLink($catalogUrl ?? ''); ?>
        <h1><?php echo htmlspecialchars($parcours['title']); ?></h1>
        <?php if ($parcours['description'] !== ''): ?>
            <p><?php echo htmlspecialchars($parcours['description']); ?></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($isPackParcours): ?>
<div class="lms-pack-children">
    <div class="lms-pack-children__intro">
        <h2><?= htmlspecialchars(commonLmsUiT('pack_title'), ENT_QUOTES, 'UTF-8') ?></h2>
        <p>
            <?php if ($showAdminPreviewCards): ?>
                <?= htmlspecialchars(commonLmsUiT('pack_preview_hint'), ENT_QUOTES, 'UTF-8') ?>
            <?php else: ?>
                <?= htmlspecialchars(commonLmsUiT('pack_visible_hint'), ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
        </p>
    </div>

    <?php if (count($packChildren) === 0): ?>
        <div class="lms-pack-children__empty"><?= htmlspecialchars(commonLmsUiT('pack_empty'), ENT_QUOTES, 'UTF-8') ?></div>
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
                    <?php commonLmsRenderParcoursCover($childParcours, $contentIndicators[(int)$childParcours['id']] ?? []); ?>
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars((string)($childParcours['title'] ?? '')); ?></h3>
                        <?php if (trim((string)($childParcours['description'] ?? '')) !== ''): ?>
                            <p><?php echo htmlspecialchars((string)$childParcours['description']); ?></p>
                        <?php endif; ?>
                        <div class="card-footer">
                            <span class="card-meta"><?php echo $isPreviewOnly ? htmlspecialchars(lmsParcoursPreviewLabel($childParcours)) : htmlspecialchars(commonLmsUiT('percent_completed', ['percent' => $percent]), ENT_QUOTES, 'UTF-8'); ?></span>
                            <button type="button" class="open-btn" onclick="<?php echo $isPreviewOnly ? 'return false;' : 'event.stopPropagation(); goToPackChildParcours(' . (int)($childParcours['id'] ?? 0) . ')'; ?>" <?php echo $isPreviewOnly ? 'disabled' : ''; ?>>
                                <?php echo $isPreviewOnly ? htmlspecialchars(commonLmsUiT('hidden'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(commonLmsUiT('open'), ENT_QUOTES, 'UTF-8'); ?>
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
    <button onclick="setView('todo')" id="btnTodo" class="active"><?= htmlspecialchars(commonLmsUiT('missions_todo'), ENT_QUOTES, 'UTF-8') ?></button>
    <button onclick="setView('done')" id="btnDone"><?= htmlspecialchars(commonLmsUiT('missions_done'), ENT_QUOTES, 'UTF-8') ?></button>
    <button onclick="setView('next')" id="btnNext"><?= htmlspecialchars(commonLmsUiT('missions_next'), ENT_QUOTES, 'UTF-8') ?></button>
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
