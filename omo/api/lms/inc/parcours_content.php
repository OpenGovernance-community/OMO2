<?php
$sourceLang = [
    'lms.parcours_content.pack.title' => ['text' => 'Parcours du pack', 'context' => 'Section title shown when viewing a pack parcours.'],
    'lms.parcours_content.pack.intro' => ['text' => 'Seuls les parcours compatibles avec les applications actives dans cette organisation sont affichés.', 'context' => 'Intro text shown above the list of child parcours in a pack.'],
    'lms.parcours_content.pack.empty' => ['text' => 'Aucun parcours visible n’est actuellement disponible dans ce pack.', 'context' => 'Empty state shown when a pack exposes no visible child parcours.'],
    'lms.parcours_content.pack.hidden_note' => ['text' => 'Actuellement masqué pour les membres standard.', 'context' => 'Note shown on hidden child parcours cards.'],
    'lms.parcours_content.pack.open' => ['text' => 'Ouvrir', 'context' => 'Button used to open a visible child parcours.'],
    'lms.parcours_content.pack.hidden' => ['text' => 'Masqué', 'context' => 'Disabled button label shown for hidden child parcours.'],
    'lms.parcours_content.views.todo' => ['text' => 'Mes missions', 'context' => 'Button used to show pending missions in a parcours.'],
    'lms.parcours_content.views.done' => ['text' => 'Terminées', 'context' => 'Button used to show completed missions in a parcours.'],
    'lms.parcours_content.views.next' => ['text' => 'À venir', 'context' => 'Button used to show upcoming missions in a parcours.'],
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
<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/lms/css/parcours-content.css') ?>">
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

<?= commonPageScriptTags('/omo/api/lms/inc/parcours-content.js', [
    'text' => lmsBuildLocalPath('/parcours.php'),
    'embed' => $isEmbedded ? '1' : '',
    'isPackParcours' => $isPackParcours,
    'parcoursId' => (int)$parcours_id,
    'userId' => (int)$user_id,
    'organizationId' => (int)$org['id'],
    'isAnonymousViewer' => ($isAnonymousViewer),
    'canTrackProgress' => ($canTrackProgress),
    'url' => lmsBuildLocalPath('/getmissions.php'),
    'text2' => lmsBuildLocalPath('/getmissions_done.php'),
    'text3' => lmsBuildLocalPath('/getmissions_next.php'),
    'text4' => lmsBuildLocalPath('/action.php'),
], 'lmsParcoursPageConfig') ?>

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
