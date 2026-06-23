<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/parcours_editor.php';

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$hasOrganizationAccess = commonUserHasOrganizationAccess($currentUserId, $organizationId);
$canManagePublicParcours = function_exists('commonCurrentUserIsAdminModeEnabled')
    ? commonCurrentUserIsAdminModeEnabled($organizationId)
    : false;
$canManageBasicParcours = function_exists('commonCurrentUserIsSiteAdminModeEnabled')
    ? commonCurrentUserIsSiteAdminModeEnabled()
    : false;
$parcoursId = (int)($_GET['pid'] ?? 0);

if ($currentUserId <= 0 || !$hasOrganizationAccess || $organizationId <= 0) {
    http_response_code(403);
    echo '<div class="lms-create-parcours-view"><p>Acces refuse.</p></div>';
    exit;
}

$parcours = new \dbObject\Parcours();
$isEditMode = false;

if ($parcoursId > 0) {
    $link = \dbObject\OrganizationParcours::loadForOrganizationParcours($organizationId, $parcoursId);
    if ($link === null || !$parcours->load($parcoursId)) {
        http_response_code(404);
        echo '<div class="lms-create-parcours-view"><p>Parcours introuvable.</p></div>';
        exit;
    }

    if ((int)$parcours->get('IDorganization') !== $organizationId) {
        http_response_code(403);
        echo '<div class="lms-create-parcours-view"><p>Seuls les parcours proprietaires de cette organisation peuvent etre modifies.</p></div>';
        exit;
    }

    $isEditMode = true;
} else {
    $parcours->set('IDorganization', $organizationId);
}

$drawerTitle = $isEditMode ? 'Editer le parcours' : 'Creer un parcours';
$drawerIntro = $isEditMode
    ? 'Mettez a jour le titre, la description et l image de ce parcours.'
    : 'Renseignez le titre, la description et l image du parcours. Il sera ensuite ajoute a l organisation courante.';
$submitLabel = $isEditMode ? 'Enregistrer' : 'Creer le parcours';
$editorFields = array(
    '{title:Informations principales}',
    'title',
    'description',
);

if ($canManagePublicParcours || $canManageBasicParcours) {
    $editorFields[] = '{title:Diffusion}';

    if ($canManagePublicParcours) {
        $editorFields[] = 'ispublic';
    }

    if ($canManageBasicParcours) {
        $editorFields[] = 'isbasic';
    }
}

$editorFields[] = '{title:Visuel}';
$editorFields[] = 'image';

$params = array(
    'buttons' => false,
    'action' => omoLmsBuildPath('/save_parcours.php', array(
        'oid' => $organizationId,
        'pid' => $parcoursId > 0 ? $parcoursId : null,
    )),
    'fields' => $editorFields,
);
?>
<style>
.lms-create-parcours-view {
    display: grid;
    gap: 18px;
}

.lms-create-parcours-hero {
    padding: 20px 22px;
    border: 1px solid color-mix(in srgb, var(--primary) 18%, var(--border-color));
    border-radius: 18px;
    background:
        radial-gradient(circle at top right, color-mix(in srgb, var(--primary) 20%, transparent), transparent 42%),
        linear-gradient(135deg, color-mix(in srgb, var(--primary) 8%, var(--bg-card)), var(--bg-card));
}

.lms-create-parcours-hero h2 {
    margin: 0 0 8px;
}

.lms-create-parcours-hero p {
    margin: 0;
    color: var(--text-light);
    line-height: 1.5;
}

.lms-create-parcours-card {
    padding: 20px 22px;
    border: 1px solid var(--border-color);
    border-radius: 18px;
    background: var(--bg-card);
    box-shadow: var(--shadow);
}

.lms-create-parcours-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 18px;
}

.lms-create-parcours-cancel,
.lms-create-parcours-save {
    min-width: 150px;
}

.lms-create-parcours-cancel {
    background: var(--border-color);
    color: var(--text-main);
}

.lms-create-parcours-note,
.lms-parcours-missions {
    padding: 20px 22px;
    border: 1px solid var(--border-color);
    border-radius: 18px;
    background: var(--bg-card);
    box-shadow: var(--shadow);
}

.lms-create-parcours-note {
    color: var(--text-light);
    line-height: 1.5;
}

.lms-parcours-missions {
    display: grid;
    gap: 16px;
}

.lms-parcours-missions__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
}

.lms-parcours-missions__header h3 {
    margin: 0 0 6px;
}

.lms-parcours-missions__header p {
    margin: 0;
    color: var(--text-light);
    line-height: 1.5;
}

.lms-parcours-missions__add-button {
    white-space: nowrap;
}

.lms-parcours-missions__empty {
    padding: 16px 18px;
    border-radius: 14px;
    background: color-mix(in srgb, var(--primary) 7%, var(--bg-main));
    color: var(--text-light);
}

.lms-parcours-missions__list {
    display: grid;
    gap: 12px;
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

.lms-parcours-mission-item.is-drop-target {
    border-color: color-mix(in srgb, var(--primary) 55%, var(--border-color));
}

.lms-parcours-mission-item.is-dragging {
    opacity: 0.45;
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
    cursor: grab;
    user-select: none;
}

.lms-parcours-mission-item__menu-wrap {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 2;
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
    min-width: 140px;
    padding: 8px;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    background: var(--bg-card);
    box-shadow: 0 16px 40px rgba(15,23,42,0.16);
    display: none;
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
    grid-template-rows: auto minmax(0, 1fr);
    gap: 14px;
    overflow: hidden;
}

.lms-parcours-mission-picker__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.lms-parcours-mission-picker__header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.lms-parcours-mission-picker__header h4 {
    margin: 0 0 6px;
}

.lms-parcours-mission-picker__header p {
    margin: 0;
    color: var(--text-light);
}

.lms-parcours-mission-picker__search {
    display: grid;
    gap: 6px;
}

.lms-parcours-mission-picker [data-lms-mission-picker-library="1"] {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
    gap: 14px;
    min-height: 0;
    overflow: hidden;
}

.lms-parcours-mission-picker__list {
    display: grid;
    gap: 10px;
    overflow: auto;
    min-height: 0;
    padding-right: 4px;
}

.lms-parcours-mission-picker__item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 14px 16px;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    background: var(--bg-main);
}

.lms-parcours-mission-picker__copy strong {
    display: block;
    margin-bottom: 6px;
}

.lms-parcours-mission-picker__copy p {
    margin: 0;
    color: var(--text-light);
}

.lms-parcours-mission-picker__empty {
    padding: 16px 18px;
    border-radius: 14px;
    background: color-mix(in srgb, var(--primary) 7%, var(--bg-main));
    color: var(--text-light);
}

.lms-parcours-mission-picker__item[hidden],
.lms-parcours-mission-picker__empty[hidden] {
    display: none !important;
}

.lms-parcours-mission-picker__new-button,
.lms-parcours-mission-creator__submit {
    white-space: nowrap;
}

.lms-parcours-mission-creator[hidden] {
    display: none !important;
}

.lms-parcours-mission-creator {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
    gap: 16px;
    min-height: 0;
    overflow: auto;
    padding-right: 4px;
}

.lms-parcours-mission-creator__header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.lms-parcours-mission-creator__header h4 {
    margin: 0 0 6px;
}

.lms-parcours-mission-creator__header p {
    margin: 0;
    color: var(--text-light);
}

.lms-parcours-mission-creator__back,
.lms-parcours-mission-creator__cancel {
    background: var(--border-color);
    color: var(--text-main);
}

.lms-parcours-mission-creator__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.lms-parcours-mission-creator__field {
    display: grid;
    gap: 6px;
}

.lms-parcours-mission-creator__field span {
    font-weight: 600;
}

.lms-parcours-mission-creator__field input,
.lms-parcours-mission-creator__field textarea {
    width: 100%;
}

.lms-parcours-mission-creator__field--full {
    grid-column: 1 / -1;
}

.lms-parcours-mission-creator__actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

@media (max-width: 720px) {
    .lms-create-parcours-actions {
        flex-direction: column-reverse;
    }

    .lms-create-parcours-cancel,
    .lms-create-parcours-save {
        width: 100%;
    }

    .lms-parcours-missions__header,
    .lms-parcours-mission-picker__header-actions,
    .lms-parcours-mission-picker__header,
    .lms-parcours-mission-picker__item,
    .lms-parcours-mission-creator__header,
    .lms-parcours-mission-creator__actions {
        flex-direction: column;
    }

    .lms-parcours-mission-picker__panel {
        width: calc(100vw - 20px);
        margin-top: 4vh;
        max-height: 88vh;
    }

    .lms-parcours-mission-creator__grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="lms-create-parcours-view">
    <section class="lms-create-parcours-hero">
        <h2><?php echo htmlspecialchars($drawerTitle); ?></h2>
        <p><?php echo htmlspecialchars($drawerIntro); ?></p>
    </section>

    <section class="lms-create-parcours-card">
        <?php $parcours->display('adminEdit.php', $params); ?>

        <div class="lms-create-parcours-actions">
            <button type="button" class="lms-create-parcours-cancel" onclick="closeDrawer()">Annuler</button>
            <button type="button" class="lms-create-parcours-save" id="lms-create-parcours-submit"><?php echo htmlspecialchars($submitLabel); ?></button>
        </div>
    </section>

    <?php if ($isEditMode): ?>
        <?php echo lmsRenderParcoursMissionManager($organizationId, $parcoursId); ?>
    <?php else: ?>
        <section class="lms-create-parcours-note">
            Vous pourrez ajouter et reordonner les missions de ce parcours juste apres sa creation.
        </section>
    <?php endif; ?>
</div>
