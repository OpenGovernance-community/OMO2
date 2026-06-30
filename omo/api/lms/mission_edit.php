<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';
require_once __DIR__ . '/inc/mission_editor.php';

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$parcoursId = (int)($_GET['pid'] ?? 0);
$missionId = (int)($_GET['mid'] ?? 0);
$managementContext = lmsResolveParcoursManagementContext($organizationId, $parcoursId, $currentUserId, false);
$hasOrganizationAccess = !empty($managementContext['hasOrganizationAccess']);

if ($currentUserId <= 0 || !$hasOrganizationAccess || $organizationId <= 0) {
    http_response_code(403);
    echo '<div class="lms-mission-editor-view"><p>Acces refuse.</p></div>';
    exit;
}

if (empty($managementContext['canEditContent'])) {
    http_response_code(403);
    echo '<div class="lms-mission-editor-view"><p>Vous n avez pas le droit de modifier ce parcours.</p></div>';
    exit;
}

$parcoursMission = new \dbObject\ParcoursMission();
$mission = new \dbObject\Mission();

if (($managementContext['link'] ?? null) === null || !$parcoursMission->load([
    ['IDparcours', $parcoursId],
    ['IDmission', $missionId],
]) || !$mission->load($missionId)) {
    http_response_code(404);
    echo '<div class="lms-mission-editor-view"><p>Mission introuvable.</p></div>';
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
?>
<style>
.lms-mission-editor-view {
    display: grid;
    gap: 18px;
}

.lms-mission-editor-hero,
.lms-mission-editor-card,
.lms-mission-related {
    padding: 20px 22px;
    border: 1px solid var(--border-color);
    border-radius: 18px;
    background: var(--bg-card);
    box-shadow: var(--shadow);
}

.lms-mission-editor-hero {
    border-color: color-mix(in srgb, var(--primary) 18%, var(--border-color));
    background:
        radial-gradient(circle at top right, color-mix(in srgb, var(--primary) 20%, transparent), transparent 42%),
        linear-gradient(135deg, color-mix(in srgb, var(--primary) 8%, var(--bg-card)), var(--bg-card));
}

.lms-mission-editor-hero h2 {
    margin: 0 0 8px;
}

.lms-mission-editor-hero p {
    margin: 0;
    color: var(--text-light);
    line-height: 1.5;
}

.lms-mission-editor-actions {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-top: 18px;
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
    border-radius: 14px;
    background: var(--bg-main);
    position: relative;
}

.lms-parcours-mission-item.is-menu-open {
    z-index: 20;
}

.lms-parcours-mission-item__handle {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: color-mix(in srgb, var(--primary) 10%, var(--bg-card));
    color: var(--primary);
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
    border-radius: 10px;
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
    border-radius: 14px;
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
    border-radius: 10px;
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
    border-radius: 14px;
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

.lms-mission-related__item strong {
    display: block;
    margin-bottom: 6px;
}

.lms-mission-related__item p,
.lms-mission-related__empty {
    margin: 0;
    color: var(--text-light);
    line-height: 1.5;
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
}

.lms-mission-related__drag-handle {
    min-width: 0;
    padding: 8px 10px;
    border: 1px dashed color-mix(in srgb, var(--primary) 25%, var(--border-color));
    background: color-mix(in srgb, var(--primary) 8%, var(--bg-card));
    color: var(--text-light);
    cursor: grab;
    letter-spacing: 1px;
}

.lms-mission-related__drag-handle:active {
    cursor: grabbing;
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
    border-radius: 12px;
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
    border-radius: 20px;
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
    border-radius: 14px;
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
    border-radius: 14px;
    background: color-mix(in srgb, var(--primary) 7%, var(--bg-main));
}

@media (max-width: 720px) {
    .lms-mission-editor-actions,
    .lms-mission-related__header,
    .lms-mission-creator-form__actions,
    .lms-question-choice-row {
        flex-direction: column;
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
    <section class="lms-mission-editor-hero">
        <h2><?php echo htmlspecialchars((string)$mission->get('title')); ?></h2>
        <p>Modifiez les informations de la mission, puis enrichissez-la avec ses devoirs et ses questions de validation.</p>
    </section>

    <section class="lms-mission-editor-card">
        <?php $mission->display('adminEdit.php', $params); ?>

        <div class="lms-mission-editor-actions">
            <button type="button" data-lms-back-to-parcours-editor="1">Retour au parcours</button>
            <button type="button" id="lms-save-mission-submit">Enregistrer la mission</button>
        </div>
    </section>

    <?php echo lmsRenderMissionDependencyManager($parcoursId, $missionId); ?>
    <?php echo lmsRenderMissionHomeworkManager($parcoursId, $missionId); ?>
    <?php echo lmsRenderMissionQuestionManager($parcoursId, $missionId); ?>
</div>
