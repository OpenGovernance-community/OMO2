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
<style>
.lms-mission-editor-view {
    display: grid;
    gap: 18px;
}

.lms-mission-editor-card,
.lms-mission-related {
    padding: 20px 22px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    background: var(--bg-card);
    box-shadow: var(--shadow);
.lms-mission-editor-header {
    top: 0;
    margin: 0;
    z-index: 8;
}

.lms-mission-editor-header.generic-drawer-header {
    --generic-drawer-header-z: 8;
    --generic-drawer-header-padding: 20px 22px;
    --generic-drawer-header-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
}

.lms-mission-editor-header h2 {
    margin: 0 0 8px;
}

.lms-mission-editor-header p {
    margin: 0;
    color: var(--text-light);
    line-height: 1.5;
}

.lms-mission-editor-branch {
    margin-top: 18px;
    display: grid;
    gap: 6px;
}

.lms-mission-editor-branch span {
    font-weight: 600;
}

.lms-mission-related {
    display: grid;
    gap: 16px;
}

.lms-parcours-mission-item {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr);
    gap: 14px;
    align-items: start;
    padding: 14px 16px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    background: var(--bg-main);
    position: relative;
}

.lms-parcours-mission-item.is-menu-open {
    z-index: 20;
}

.lms-parcours-mission-item__handle {
    user-select: none;
}

.lms-parcours-mission-item__menu-wrap {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 25;
}

.lms-parcours-mission-item__menu-trigger {
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: var(--radius-md);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.94);
    color: var(--text-main);
    cursor: pointer;
    box-shadow: 0 8px 18px rgba(15,23,42,0.1);
}

.lms-parcours-mission-item__menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 180px;
    padding: 8px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    background: var(--bg-card);
    box-shadow: 0 16px 40px rgba(15,23,42,0.16);
    display: none;
    z-index: 30;
}

.lms-parcours-mission-item__menu.is-open {
    display: block;
}

.lms-parcours-mission-item__menu-item {
    width: 100%;
    border: 0;
    background: transparent;
    color: var(--text-main);
    text-align: left;
    padding: 10px 12px;
    border-radius: var(--radius-md);
    cursor: pointer;
}

.lms-parcours-mission-item__menu-item:hover {
    background: color-mix(in srgb, var(--primary) 10%, var(--bg-card));
}

.lms-parcours-mission-item__body strong {
    display: block;
    margin-bottom: 6px;
    padding-right: 42px;
}

.lms-parcours-mission-item__body p {
    margin: 0 0 8px;
    color: var(--text-light);
    line-height: 1.5;
}

.lms-parcours-mission-item__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    color: var(--text-light);
    font-size: 0.92rem;
}

.lms-parcours-mission-item__meta span {
    padding: 4px 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--primary) 8%, var(--bg-card));
}

.lms-mission-related__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
}

.lms-mission-related__header h3 {
    margin: 0 0 6px;
}

.lms-mission-related__header p {
    margin: 0;
    color: var(--text-light);
    line-height: 1.5;
}

.lms-mission-related__list {
    display: grid;
    gap: 12px;
}

.lms-mission-related__item {
    padding: 14px 16px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    background: var(--bg-main);
    position: relative;
}

.lms-mission-related__item.is-dragging {
    opacity: 0.7;
}

.lms-mission-related__item.is-drop-target {
    border-color: color-mix(in srgb, var(--primary) 45%, var(--border-color));
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--primary) 12%, transparent);
}

.lms-mission-related__item-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}

.lms-mission-related__item-head--structured {
    align-items: stretch;
    margin-bottom: 0;
}

.lms-mission-related__item-main {
    min-width: 0;
    flex: 1;
    display: grid;
    gap: 6px;
}

.lms-mission-related__item-topbar {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.lms-mission-related__item-topbar > * {
    margin-top: 0;
}

.lms-mission-related__item strong {
    display: block;
    margin-bottom: 6px;
}

.lms-mission-related__item-topbar strong {
    margin-bottom: 0;
    min-width: 0;
}

.lms-mission-related__item p,
.lms-mission-related__empty {
    margin: 0;
    color: var(--text-light);
    line-height: 1.5;
}

.lms-mission-related__item-html {
    color: var(--text-light);
    line-height: 1.55;
}

.lms-mission-related__item-html > :first-child {
    margin-top: 0;
}

.lms-mission-related__item-html > :last-child {
    margin-bottom: 0;
}

.lms-mission-related__item-html ul,
.lms-mission-related__item-html ol {
    margin: 10px 0 10px 20px;
}

.lms-mission-related__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
    color: var(--text-light);
    font-size: 0.92rem;
}

.lms-mission-related__meta span {
    padding: 4px 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--primary) 8%, var(--bg-card));
}

.lms-mission-related__item-actions button {
    min-width: 0;
    padding: 8px 12px;
    margin: 0;
    align-self: flex-start;
}

.lms-mission-related__drag-handle {
    margin: 0;
}

.lms-mission-related__item--structured .lms-mission-related__drag-handle {
    align-self: stretch;
}

.lms-mission-creator-form[hidden] {
    display: none !important;
}

.lms-mission-creator-form {
    padding-top: 4px;
    border-top: 1px solid var(--border-color);
}

.lms-mission-creator-form__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.lms-mission-creator-form__field {
    display: grid;
    gap: 6px;
}

.lms-mission-creator-form__field span {
    font-weight: 600;
}

.lms-mission-creator-form__check {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    color: var(--text-main);
}

.lms-mission-creator-form__check input[type='checkbox'] {
    margin-top: 2px;
}

.lms-mission-creator-form__field .note-editor {
    border-color: var(--color-border, #d1d5db);
    border-radius: var(--radius-md);
    overflow: hidden;
    background: var(--color-surface, #ffffff);
}

.lms-mission-creator-form__field .note-toolbar {
    background: var(--color-surface-alt, #f8fafc);
    border-bottom-color: var(--color-border, #d1d5db);
}

.lms-mission-creator-form__field .note-editing-area .note-editable {
    background: var(--color-surface, #ffffff);
    color: var(--color-text, #1f2937);
}

.lms-mission-creator-form__field--full {
    grid-column: 1 / -1;
}

.lms-mission-creator-form__actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 16px;
}

.lms-question-choice-list {
    display: grid;
    gap: 10px;
}

.lms-question-choice-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    gap: 10px;
    align-items: end;
    padding: 12px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    background: var(--bg-main);
}

.lms-question-choice-row__label,
.lms-question-choice-row__correct {
    display: grid;
    gap: 6px;
}

.lms-question-choice-row__correct {
    grid-auto-flow: column;
    align-items: center;
}

.lms-question-choice-list__add {
    margin-top: 10px;
}

.lms-parcours-mission-picker[hidden] {
    display: none !important;
}

.lms-parcours-mission-picker {
    position: fixed;
    inset: 0;
    z-index: 1100;
}

.lms-parcours-mission-picker__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.36);
}

.lms-parcours-mission-picker__panel {
    position: relative;
    z-index: 1;
    width: min(760px, calc(100vw - 32px));
    max-height: min(78vh, 760px);
    margin: 7vh auto 0;
    padding: 18px;
    border-radius: var(--radius-md);
    background: var(--bg-card);
    box-shadow: 0 28px 80px rgba(15,23,42,0.28);
    display: grid;
    grid-template-rows: auto auto minmax(0, 1fr);
    gap: 14px;
    overflow: hidden;
}

.lms-parcours-mission-picker__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.lms-parcours-mission-picker__header h4 {
    margin: 0 0 6px;
}

.lms-parcours-mission-picker__header p {
    margin: 0;
    color: var(--text-light);
    line-height: 1.5;
}

.lms-parcours-mission-picker__header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.lms-parcours-mission-picker__search {
    display: grid;
    gap: 6px;
}

.lms-parcours-mission-picker__list {
    display: grid;
    gap: 12px;
    overflow: auto;
    padding-right: 4px;
}

.lms-parcours-mission-picker__item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 14px;
    align-items: start;
    padding: 14px 16px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    background: var(--bg-main);
}

.lms-parcours-mission-picker__copy {
    display: grid;
    gap: 8px;
}

.lms-parcours-mission-picker__copy strong {
    display: block;
}

.lms-parcours-mission-picker__copy p,
.lms-parcours-mission-picker__empty {
    margin: 0;
    color: var(--text-light);
    line-height: 1.5;
}

.lms-parcours-mission-picker__empty {
    padding: 16px 18px;
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--primary) 7%, var(--bg-main));
}

@media (max-width: 720px) {
    .lms-mission-editor-header .generic-drawer-header__actions,
    .lms-mission-related__header,
    .lms-mission-creator-form__actions,
    .lms-question-choice-row {
        width: 100%;
        flex-direction: column-reverse;
        align-items: stretch;
    }

    .lms-mission-related__item-head {
        align-items: stretch;
    }

    .lms-mission-creator-form__grid,
    .lms-question-choice-row,
    .lms-parcours-mission-picker__item {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="lms-mission-editor-view" data-lms-mission-editor="1" data-mission-id="<?php echo $missionId; ?>" data-parcours-id="<?php echo $parcoursId; ?>">
    <section class="lms-mission-editor-header generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy">
            <h2><?php echo htmlspecialchars((string)$mission->get('title')); ?></h2>
            <p><?php echo htmlspecialchars(lmsMissionEditT('lms.mission_edit.hero.intro')); ?></p>
        </div>
        <div class="generic-drawer-header__actions">
            <button type="button" data-lms-back-to-parcours-editor="1"><?php echo htmlspecialchars(lmsMissionEditT('lms.mission_edit.action.back')); ?></button>
            <button type="button" id="lms-save-mission-submit"><?php echo htmlspecialchars(lmsMissionEditT('lms.mission_edit.action.save')); ?></button>
        </div>
    </section>

    <section class="lms-mission-editor-card">
        <?php $mission->display('adminEdit.php', $params); ?>
    </section>

    <?php echo lmsRenderMissionDependencyManager($parcoursId, $missionId); ?>
    <?php echo lmsRenderMissionHomeworkManager($parcoursId, $missionId); ?>
    <?php echo lmsRenderMissionQuestionManager($parcoursId, $missionId); ?>
</div>
<script>
(function () {
    var root = document.querySelector('[data-lms-mission-editor="1"]');
    if (root && typeof window.initGenericComponents === 'function') {
        window.initGenericComponents(root);
    }
})();
</script>
