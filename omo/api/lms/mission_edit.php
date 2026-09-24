<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';
require_once __DIR__ . '/inc/mission_editor.php';

$sourceLang = [
    'lms.mission_edit.error.access_denied' => [
        'text' => 'Acces refuse.',
        'context' => 'Error shown when the user cannot access the mission editor.',
    ],
    'lms.mission_edit.error.cannot_edit_parcours' => [
        'text' => 'Vous n avez pas le droit de modifier ce parcours.',
        'context' => 'Error shown when the user cannot edit the parcours of the mission.',
    ],
    'lms.mission_edit.error.not_found' => [
        'text' => 'Mission introuvable.',
        'context' => 'Error shown when the requested mission cannot be found.',
    ],
    'lms.mission_edit.branch.label' => [
        'text' => 'Nom du groupe',
        'context' => 'Label of the editable select used for the mission branch name.',
    ],
    'lms.mission_edit.branch.placeholder' => [
        'text' => 'Exemple : Introduction',
        'context' => 'Placeholder shown in the mission branch input.',
    ],
    'lms.mission_edit.branch.toggle_aria' => [
        'text' => 'Afficher les groupes existants',
        'context' => 'Accessible label for the branch suggestions toggle.',
    ],
    'lms.mission_edit.branch.empty' => [
        'text' => 'Aucun autre groupe n existe encore dans ce parcours.',
        'context' => 'Empty state shown when no other branch exists in the parcours.',
    ],
    'lms.mission_edit.hero.intro' => [
        'text' => 'Modifiez les informations de la mission, puis enrichissez-la avec ses devoirs et ses questions de validation.',
        'context' => 'Intro text shown at the top of the mission editor.',
    ],
    'lms.mission_edit.action.back' => [
        'text' => 'Retour au parcours',
        'context' => 'Action used to go back to the parcours editor.',
    ],
    'lms.mission_edit.action.save' => [
        'text' => 'Enregistrer la mission',
        'context' => 'Primary action used to save the mission.',
    ],
];

$lang = omoLoadTranslationBundle('omo_lms_mission_edit', $sourceLang);

function lmsMissionEditT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$parcoursId = (int)($_GET['pid'] ?? 0);
$missionId = (int)($_GET['mid'] ?? 0);
$managementContext = lmsResolveParcoursManagementContext($organizationId, $parcoursId, $currentUserId, false);
$hasOrganizationAccess = !empty($managementContext['hasOrganizationAccess']);

if ($currentUserId <= 0 || !$hasOrganizationAccess || $organizationId <= 0) {
    http_response_code(403);
    echo '<div class="lms-mission-editor-view"><p>' . htmlspecialchars(lmsMissionEditT('lms.mission_edit.error.access_denied')) . '</p></div>';
    exit;
}

if (empty($managementContext['canEditContent'])) {
    http_response_code(403);
    echo '<div class="lms-mission-editor-view"><p>' . htmlspecialchars(lmsMissionEditT('lms.mission_edit.error.cannot_edit_parcours')) . '</p></div>';
    exit;
}

$parcoursMission = new \dbObject\ParcoursMission();
$mission = new \dbObject\Mission();

if (($managementContext['link'] ?? null) === null || !$parcoursMission->load([
    ['IDparcours', $parcoursId],
    ['IDmission', $missionId],
]) || !$mission->load($missionId)) {
    http_response_code(404);
    echo '<div class="lms-mission-editor-view"><p>' . htmlspecialchars(lmsMissionEditT('lms.mission_edit.error.not_found')) . '</p></div>';
    exit;
}

$params = array(
    'buttons' => false,
    'action' => omoLmsBuildPath('/save_mission.php', array(
        'oid' => $organizationId,
        'pid' => $parcoursId,
        'mid' => $missionId,
    )),
    'fields' => array(
        '{title:Informations principales}',
        'title',
        'resume',
        'video',
        'html',
    ),
);
$availableBranches = \dbObject\ParcoursMission::fetchDistinctBranchesForParcours($parcoursId);
$currentBranch = (string)$parcoursMission->get('branch');
ob_start();
?>
<label class="lms-mission-editor-branch">
    <span><?php echo htmlspecialchars(lmsMissionEditT('lms.mission_edit.branch.label')); ?></span>
    <div class="generic-editable-select" data-generic-editable-select>
        <div class="generic-editable-select__control">
            <input
                type="text"
                name="branch"
                maxlength="50"
                class="generic-form-control generic-editable-select__input"
                value="<?php echo htmlspecialchars($currentBranch); ?>"
                placeholder="<?php echo htmlspecialchars(lmsMissionEditT('lms.mission_edit.branch.placeholder')); ?>"
                data-generic-editable-select-input
            >
            <button type="button" class="generic-editable-select__toggle" data-generic-editable-select-toggle aria-label="<?php echo htmlspecialchars(lmsMissionEditT('lms.mission_edit.branch.toggle_aria')); ?>"></button>
        </div>
        <div class="generic-editable-select__panel" data-generic-editable-select-panel hidden>
            <?php foreach ($availableBranches as $branchOption): ?>
                <button
                    type="button"
                    class="generic-editable-select__option"
                    data-generic-editable-select-option="<?php echo htmlspecialchars($branchOption); ?>"
                ><?php echo htmlspecialchars($branchOption); ?></button>
            <?php endforeach; ?>
            <div class="generic-editable-select__empty" data-generic-editable-select-empty hidden><?php echo htmlspecialchars(lmsMissionEditT('lms.mission_edit.branch.empty')); ?></div>
        </div>
    </div>
</label>
<?php
$params['afterTableHtml'] = ob_get_clean();
?>
<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/lms/css/mission-components.css') ?>">
<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/lms/css/mission-editor.css') ?>">

<div class="lms-mission-editor-view" data-lms-mission-editor="1" data-mission-id="<?php echo $missionId; ?>" data-parcours-id="<?php echo $parcoursId; ?>">
    <section class="lms-mission-editor-header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy">
            <h2 class="generic-title generic-title--large"><?php echo htmlspecialchars((string)$mission->get('title')); ?></h2>
            <p class="generic-description"><?php echo htmlspecialchars(lmsMissionEditT('lms.mission_edit.hero.intro')); ?></p>
        </div>
        <div class="generic-drawer-header__actions">
            <button type="button" class="generic-action-button generic-action-button--secondary" data-lms-back-to-parcours-editor="1"><?php echo htmlspecialchars(lmsMissionEditT('lms.mission_edit.action.back')); ?></button>
            <button type="button" class="generic-action-button generic-action-button--main" id="lms-save-mission-submit"><?php echo htmlspecialchars(lmsMissionEditT('lms.mission_edit.action.save')); ?></button>
        </div>
    </section>

    <div class="lms-mission-editor-content generic-drawer-content">
        <section class="lms-mission-editor-card">
            <?php $mission->display('adminEdit.php', $params); ?>
        </section>

        <?php echo lmsRenderMissionDependencyManager($parcoursId, $missionId); ?>
        <?php echo lmsRenderMissionHomeworkManager($parcoursId, $missionId); ?>
        <?php echo lmsRenderMissionQuestionManager($parcoursId, $missionId); ?>
    </div>
</div>
<script>
(function () {
    var root = document.querySelector('[data-lms-mission-editor="1"]');
    if (root && typeof window.initGenericComponents === 'function') {
        window.initGenericComponents(root);
    }
})();
</script>
