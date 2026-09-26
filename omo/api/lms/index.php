<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include 'inc/org.php';
require_once __DIR__ . '/inc/access.php';

$sourceLang = [
    'lms.index.title.catalog_brand' => ['text' => 'Tutoriels OMO', 'context' => 'Page title used in the basic LMS catalog mode.'],
    'lms.index.title.catalog' => ['text' => 'Tutoriels de prise en main', 'context' => 'Main page title used in the basic LMS catalog mode.'],
    'lms.index.title.training' => ['text' => 'Parcours de formation', 'context' => 'Main page title used in the standard LMS organization mode.'],
    'lms.index.title.embed_catalog' => ['text' => 'Parcours de prise en main', 'context' => 'Subtitle shown in embedded LMS catalog mode.'],
    'lms.index.card.action.delete' => ['text' => 'Supprimer', 'context' => 'Menu action used to delete an owned parcours or pack.'],
    'lms.index.card.action.detach' => ['text' => 'Détacher', 'context' => 'Menu action used to detach a shared parcours or pack from the current organization.'],
    'lms.index.card.action.edit' => ['text' => 'Éditer', 'context' => 'Menu action used to open the editor of a parcours or pack.'],
    'lms.index.card.visibility_hidden' => ['text' => 'Actuellement masqué pour les membres standard.', 'context' => 'Note shown on hidden parcours cards.'],
    'lms.index.card.open' => ['text' => 'Ouvrir', 'context' => 'Button label used to open a parcours.'],
    'lms.index.card.hidden' => ['text' => 'Masqué', 'context' => 'Button label used on hidden parcours cards.'],
    'lms.index.create.kicker' => ['text' => 'Parcours', 'context' => 'Small overline shown on the create parcours card.'],
    'lms.index.create.title' => ['text' => 'Ajouter un parcours', 'context' => 'Title shown on the create parcours card.'],
    'lms.index.create.description' => ['text' => 'Créer un nouveau parcours ou importer un parcours déjà partagé comme public ou basic.', 'context' => 'Description shown on the create parcours card.'],
    'lms.index.create.helper' => ['text' => 'Les deux actions s’ouvrent directement dans le drawer.', 'context' => 'Helper text shown on the create parcours card.'],
    'lms.index.create.import' => ['text' => 'Importer', 'context' => 'Button label used to import an existing parcours.'],
    'lms.index.create.new' => ['text' => 'Nouveau', 'context' => 'Button label used to create a new parcours.'],
    'lms.index.section.completed' => ['text' => 'Parcours terminés', 'context' => 'Section title listing completed parcours.'],
    'lms.index.section.packs' => ['text' => 'Packs de parcours', 'context' => 'Section title listing parcours packs.'],
    'lms.index.pack.kicker' => ['text' => 'Pack', 'context' => 'Small overline shown on pack cards.'],
    'lms.index.section.completed_intro' => ['text' => 'Retrouvez ici les parcours deja completes a 100%.', 'context' => 'Intro text shown above the completed parcours section.'],
    'lms.index.section.packs_intro' => ['text' => 'Ces packs regroupent plusieurs parcours. Les parcours liés à des applications désactivées y restent automatiquement masqués.', 'context' => 'Intro text shown above the parcours packs section.'],
    'lms.index.alert.required_title' => ['text' => 'Le titre est obligatoire.', 'context' => 'Alert shown when a title is required before submitting a form.'],
    'lms.index.alert.required_resume' => ['text' => 'Le résumé est obligatoire.', 'context' => 'Alert shown when a summary is required before submitting a mission form.'],
    'lms.index.alert.load_form' => ['text' => 'Impossible de charger le formulaire de parcours.', 'context' => 'Alert shown when the parcours create form cannot be loaded.'],
    'lms.index.alert.load_catalog' => ['text' => 'Impossible de charger le catalogue de parcours.', 'context' => 'Alert shown when the import catalog cannot be loaded.'],
    'lms.index.alert.load_parcours' => ['text' => 'Impossible de charger ce parcours.', 'context' => 'Alert shown when a parcours editor cannot be loaded.'],
    'lms.index.alert.load_mission' => ['text' => 'Impossible de charger cette mission.', 'context' => 'Alert shown when a mission editor cannot be loaded.'],
    'lms.index.alert.create_mission' => ['text' => 'Impossible de créer cette mission.', 'context' => 'Alert shown when mission creation fails.'],
    'lms.index.alert.save_mission' => ['text' => 'Impossible d’enregistrer cette mission.', 'context' => 'Alert shown when mission editing fails.'],
    'lms.index.alert.save_mission_refresh' => ['text' => 'Mission enregistrée, mais impossible de recharger son éditeur.', 'context' => 'Alert shown when a mission saves but its editor cannot be reloaded.'],
    'lms.index.alert.add_prerequisite' => ['text' => 'Impossible d’ajouter ce prérequis.', 'context' => 'Alert shown when adding a mission prerequisite fails.'],
    'lms.index.alert.remove_prerequisite' => ['text' => 'Impossible de retirer ce prérequis.', 'context' => 'Alert shown when removing a mission prerequisite fails.'],
    'lms.index.confirm.remove_prerequisite' => ['text' => 'Retirer ce prérequis de mission ?', 'context' => 'Confirmation shown before removing a mission prerequisite.'],
    'lms.index.alert.create_homework' => ['text' => 'Impossible de créer ce devoir.', 'context' => 'Alert shown when homework creation fails.'],
    'lms.index.alert.create_question' => ['text' => 'Impossible de créer cette question.', 'context' => 'Alert shown when question creation fails.'],
    'lms.index.alert.reorder_homeworks' => ['text' => 'Impossible de réordonner les devoirs.', 'context' => 'Alert shown when homework reordering fails.'],
    'lms.index.alert.reorder_questions' => ['text' => 'Impossible de réordonner les questions.', 'context' => 'Alert shown when question reordering fails.'],
    'lms.index.alert.question_required' => ['text' => 'La question est obligatoire.', 'context' => 'Alert shown when the question field is empty.'],
    'lms.index.alert.answer_required' => ['text' => 'La réponse est obligatoire.', 'context' => 'Alert shown when the answer field is empty.'],
    'lms.index.alert.minimum_choices' => ['text' => 'Ajoutez au moins deux choix de réponse.', 'context' => 'Alert shown when fewer than two answer choices are provided.'],
    'lms.index.alert.keep_two_choices' => ['text' => 'Gardez au moins deux choix de réponse.', 'context' => 'Alert shown when trying to remove too many answer choices.'],
    'lms.index.alert.need_correct_choice' => ['text' => 'Indiquez au moins une bonne réponse.', 'context' => 'Alert shown when no correct answer choice is selected.'],
    'lms.index.form.choice' => ['text' => 'Choix', 'context' => 'Field label used for a question choice row.'],
    'lms.index.form.correct_choice' => ['text' => 'Bonne réponse', 'context' => 'Field label used for the correct choice checkbox.'],
    'lms.index.form.remove' => ['text' => 'Supprimer', 'context' => 'Button label used to remove an answer choice row.'],
    'lms.index.form.create_homework' => ['text' => 'Créer le devoir', 'context' => 'Submit button label used when creating a homework.'],
    'lms.index.form.update_question' => ['text' => 'Mettre à jour la question', 'context' => 'Submit button label used when editing a mission question.'],
    'lms.index.form.create_question' => ['text' => 'Créer la question', 'context' => 'Submit button label used when creating a mission question.'],
    'lms.index.delete.preview_failed' => ['text' => 'Impossible de préparer la suppression de ce parcours.', 'context' => 'Alert shown when the parcours deletion preview cannot be loaded.'],
    'lms.index.delete.confirm_named' => ['text' => 'Supprimer le parcours "{title}" ?', 'context' => 'Fallback confirmation message used before deleting a named parcours.'],
    'lms.index.delete.confirm_generic' => ['text' => 'Supprimer ce parcours ?', 'context' => 'Fallback confirmation message used before deleting a parcours without title.'],
    'lms.index.delete.failed' => ['text' => 'Impossible de supprimer ce parcours.', 'context' => 'Alert shown when parcours deletion fails.'],
    'lms.index.delete.success' => ['text' => 'Parcours supprimé.', 'context' => 'Success alert shown after deleting a parcours.'],
];

$lang = omoLoadTranslationBundle('omo_lms_index', $sourceLang);

function lmsIndexT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$isEmbedded = !empty($_GET['embed']);
$isBasicCatalogMode = lmsIsBasicCatalogMode();
$user_id = (int)($_SESSION['currentUser'] ?? 0);
$anonymousCompletedParcoursIds = $user_id > 0 ? [] : lmsGetAnonymousCompletedParcoursIds();
$hasOrganizationAccess = commonUserHasOrganizationAccess($user_id, (int)$org['id']);
$canCreateParcours = !$isBasicCatalogMode && lmsCurrentUserCanCreateParcours((int)$org['id'], $user_id);
$canEditParcours = !$isBasicCatalogMode && lmsCurrentUserCanEditParcours((int)$org['id'], $user_id);
$organizationColor = commonGetOrganizationExplicitColor($org);
$parcours = $isBasicCatalogMode
    ? \dbObject\Parcours::fetchBasicCatalogWithProgress($user_id, $anonymousCompletedParcoursIds)
    : \dbObject\Parcours::fetchForOrganizationWithProgress($org['id'], $user_id, $hasOrganizationAccess, $canEditParcours);
$parcours = is_array($parcours) ? $parcours : [];
$pendingParcours = [];
$completedParcours = [];
$packParcours = [];

foreach ($parcours as $parcoursItem) {
    if (!empty($parcoursItem['ispack'])) {
        $isOwnerPack = (int)($parcoursItem['owner_organization_id'] ?? 0) === (int)$org['id'];
        if (!$canEditParcours && !$isOwnerPack) {
            continue;
        }
        $packParcours[] = $parcoursItem;
        continue;
    }

    $totalMissions = (int)($parcoursItem['total_missions'] ?? 0);
    $doneMissions = (int)($parcoursItem['done_missions'] ?? 0);
    $percent = $totalMissions > 0 ? (int)round(($doneMissions / $totalMissions) * 100) : 0;

    if ($totalMissions > 0 && $percent >= 100) {
        $completedParcours[] = $parcoursItem;
        continue;
    }

    $pendingParcours[] = $parcoursItem;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($isBasicCatalogMode ? lmsIndexT('lms.index.title.catalog_brand') : $org['name']); ?></title>
    <link rel="stylesheet" href="/common/assets/theme.css">
    <?= commonStylesheetTags('/shared_css.css') ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(omoLmsBuildPath('/css/std.css')); ?>">
    <?php if ($isEmbedded && $canCreateParcours): ?>
    <script src="/common/assets/components.js"></script>
    <?php endif; ?>
    <script src="/shared_functions.js"></script>
    <script>
    sharedApplyDocumentTheme({
        preference: <?php echo $user_id > 0 ? 'undefined' : "'system'"; ?>
    });
    </script>
    <style>
        :root {
            <?php if ($organizationColor !== ''): ?>
            --primary: <?php echo htmlspecialchars($organizationColor); ?>;
            <?php endif; ?>
        }
    </style>
    
    <link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/lms/css/catalog.css') ?>">
</head>
<body class="<?php echo $isEmbedded ? 'lms-embed-mode' : ''; ?>">
<?php
if (!$isEmbedded) {
    include 'inc/menu.php';
}
?>
<div class="content<?php echo $isEmbedded ? ' lms-index-content--embed' : ''; ?>">
<?php if ($isEmbedded): ?>
<div class="lms-index-embed-header">
    <h1><?php echo htmlspecialchars($isBasicCatalogMode ? lmsIndexT('lms.index.title.catalog_brand') : $org['name']); ?></h1>
    <p><?php echo htmlspecialchars($isBasicCatalogMode ? lmsIndexT('lms.index.title.embed_catalog') : lmsIndexT('lms.index.title.training')); ?></p>
</div>
<?php endif; ?>
<?php if (!$isEmbedded && !$isBasicCatalogMode): ?>
<div class="org-banner" style="background-color: <?php echo htmlspecialchars($org['color']); ?>">

    <?php if (!empty($org['banner'])): ?>
        <div class="banner-bg" style="background-image: url('<?php echo htmlspecialchars($org['banner']); ?>')"></div>
    <?php endif; ?>

    <div class="banner-content">
        <?php if (!empty($org['logo'])): ?>
            <div class="logo-wrapper">
                <img src="<?php echo htmlspecialchars($org['logo']); ?>" alt="logo">
            </div>
        <?php endif; ?>

        <h1><?php echo htmlspecialchars($org['name']); ?></h1>
    </div>
</div>
<?php endif; ?>

<h1><?php echo htmlspecialchars($isBasicCatalogMode ? lmsIndexT('lms.index.title.catalog') : lmsIndexT('lms.index.title.training')); ?></h1>

<div class="lms-parcours-sections">
<section class="lms-parcours-section" id="lms-parcours-section-pending">
<div class="container" id="lms-parcours-pending-grid">
<?php foreach ($pendingParcours as $p):
    $total = (int)$p['total_missions'];
    $done = (int)$p['done_missions'];
    $percent = $total > 0 ? round(($done / $total) * 100) : 0;
    $isOwnerParcours = (int)($p['owner_organization_id'] ?? 0) === (int)$org['id'];
    $isVisibleParcours = !array_key_exists('isvisible', $p) || !empty($p['isvisible']);
    $canManageThisParcours = $canCreateParcours && !\dbObject\Parcours::hasAttachedPackParentInOrganization((int)$org['id'], (int)($p['id'] ?? 0));
    $canEditThisParcours = $canEditParcours && $isOwnerParcours;
    $showMenuThisParcours = $canManageThisParcours || $canEditThisParcours;
    $detachActionLabel = $isOwnerParcours ? lmsIndexT('lms.index.card.action.delete') : lmsIndexT('lms.index.card.action.detach');
?>
<div
    class="card<?php echo !$isVisibleParcours ? ' card--visibility-hidden' : ''; ?>"
    data-parcours-card="1"
    data-is-pack="0"
    data-parcours-id="<?php echo (int)$p['id']; ?>"
    data-parcours-title="<?php echo htmlspecialchars((string)$p['title'], ENT_QUOTES, 'UTF-8'); ?>"
    data-is-owner="<?php echo $isOwnerParcours ? '1' : '0'; ?>"
    data-can-edit="<?php echo $canEditThisParcours ? '1' : '0'; ?>"
    data-can-manage="<?php echo $canManageThisParcours ? '1' : '0'; ?>"
    data-total-missions="<?php echo $total; ?>"
    data-local-progress="<?php echo $user_id > 0 ? '0' : '1'; ?>"
    onclick="<?php echo $isVisibleParcours ? 'goToParcours(' . (int)$p['id'] . ')' : ''; ?>"
>
    <?php if ($showMenuThisParcours): ?>
        <div class="card-menu-wrap" onclick="event.stopPropagation()">
            <button
                type="button"
                class="card-menu-trigger"
                aria-label="Actions"
                onclick="toggleParcoursCardMenu(event, <?php echo (int)$p['id']; ?>)"
            >...</button>
            <div class="card-menu" id="parcours-card-menu-<?php echo (int)$p['id']; ?>">
                <?php if ($canEditThisParcours): ?>
                    <button type="button" class="card-menu-item" onclick="openEditParcoursDrawer(event, <?php echo (int)$p['id']; ?>)"><?php echo htmlspecialchars(lmsIndexT('lms.index.card.action.edit')); ?></button>
                <?php endif; ?>
                <?php if ($canManageThisParcours): ?>
                <button type="button" class="card-menu-item card-menu-item--danger" onclick="deleteParcoursFromCard(event, <?php echo (int)$p['id']; ?>)"><?php echo $detachActionLabel; ?></button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($p['image'])): ?>
        <div class="card-image">
            <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
        </div>
    <?php endif; ?>

    <div class="card-content">
        <h3><?php echo htmlspecialchars($p['title']); ?></h3>
        <div><?php echo htmlspecialchars($p['description']); ?></div>
        <?php if (!$isVisibleParcours): ?>
            <div class="card-visibility-note"><?php echo htmlspecialchars(lmsIndexT('lms.index.card.visibility_hidden')); ?></div>
        <?php endif; ?>

        <div class="card-footer">
            <div class="progress-circle" data-percent="<?php echo (int)$percent; ?>"></div>
            <button class="open-btn"<?php echo $isVisibleParcours ? '' : ' type="button" disabled'; ?>><?php echo htmlspecialchars($isVisibleParcours ? lmsIndexT('lms.index.card.open') : lmsIndexT('lms.index.card.hidden')); ?></button>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php if ($canCreateParcours): ?>
<div
    class="card card--create"
    data-parcours-create-card="1"
>
    <div class="card-content">
        <div class="card-create-visual">
            <img src="<?php echo htmlspecialchars(omoLmsBuildPath('/img/create-parcours-card.png')); ?>" alt="">
            <div class="card-create-plus" aria-hidden="true">+</div>
        </div>
        <div class="card-create-body">
            <div class="card-create-kicker"><?php echo htmlspecialchars(lmsIndexT('lms.index.create.kicker')); ?></div>
            <h3><?php echo htmlspecialchars(lmsIndexT('lms.index.create.title')); ?></h3>
            <div class="card-create-copy"><?php echo htmlspecialchars(lmsIndexT('lms.index.create.description')); ?></div>

            <div class="card-create-footer">
                <span class="card-create-copy"><?php echo htmlspecialchars(lmsIndexT('lms.index.create.helper')); ?></span>
                <div class="card-create-actions">
                    <button class="card-create-import" type="button" onclick="openImportParcoursDrawer(event)"><?php echo htmlspecialchars(lmsIndexT('lms.index.create.import')); ?></button>
                    <button class="open-btn" type="button" onclick="openCreateParcoursDrawer(event)"><?php echo htmlspecialchars(lmsIndexT('lms.index.create.new')); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
</div>
</section>

<section class="lms-parcours-section" id="lms-parcours-section-completed" <?php echo count($completedParcours) === 0 ? 'hidden' : ''; ?>>
    <div class="lms-parcours-separator">
        <div class="lms-parcours-section__intro">
            <h2 class="generic-title generic-title--section"><?php echo htmlspecialchars(lmsIndexT('lms.index.section.completed')); ?></h2>
            <p class="generic-description"><?php echo htmlspecialchars(lmsIndexT('lms.index.section.completed_intro')); ?></p>
        </div>
    </div>

    <div class="container" id="lms-parcours-completed-grid">
    <?php foreach ($completedParcours as $p):
        $total = (int)$p['total_missions'];
        $done = (int)$p['done_missions'];
        $percent = $total > 0 ? round(($done / $total) * 100) : 0;
        $isOwnerParcours = (int)($p['owner_organization_id'] ?? 0) === (int)$org['id'];
        $isVisibleParcours = !array_key_exists('isvisible', $p) || !empty($p['isvisible']);
        $canManageThisParcours = $canCreateParcours && !\dbObject\Parcours::hasAttachedPackParentInOrganization((int)$org['id'], (int)($p['id'] ?? 0));
        $canEditThisParcours = $canEditParcours && $isOwnerParcours;
        $showMenuThisParcours = $canManageThisParcours || $canEditThisParcours;
        $detachActionLabel = $isOwnerParcours ? lmsIndexT('lms.index.card.action.delete') : lmsIndexT('lms.index.card.action.detach');
    ?>
    <div
        class="card<?php echo !$isVisibleParcours ? ' card--visibility-hidden' : ''; ?>"
        data-parcours-card="1"
        data-is-pack="0"
        data-parcours-id="<?php echo (int)$p['id']; ?>"
        data-parcours-title="<?php echo htmlspecialchars((string)$p['title'], ENT_QUOTES, 'UTF-8'); ?>"
        data-is-owner="<?php echo $isOwnerParcours ? '1' : '0'; ?>"
        data-can-edit="<?php echo $canEditThisParcours ? '1' : '0'; ?>"
        data-can-manage="<?php echo $canManageThisParcours ? '1' : '0'; ?>"
        data-total-missions="<?php echo $total; ?>"
        data-local-progress="<?php echo $user_id > 0 ? '0' : '1'; ?>"
        onclick="<?php echo $isVisibleParcours ? 'goToParcours(' . (int)$p['id'] . ')' : ''; ?>"
    >
        <?php if ($showMenuThisParcours): ?>
            <div class="card-menu-wrap" onclick="event.stopPropagation()">
                <button
                    type="button"
                    class="card-menu-trigger"
                    aria-label="Actions"
                    onclick="toggleParcoursCardMenu(event, <?php echo (int)$p['id']; ?>)"
                >...</button>
                <div class="card-menu" id="parcours-card-menu-<?php echo (int)$p['id']; ?>">
                    <?php if ($canEditThisParcours): ?>
                        <button type="button" class="card-menu-item" onclick="openEditParcoursDrawer(event, <?php echo (int)$p['id']; ?>)"><?php echo htmlspecialchars(lmsIndexT('lms.index.card.action.edit')); ?></button>
                    <?php endif; ?>
                    <?php if ($canManageThisParcours): ?>
                    <button type="button" class="card-menu-item card-menu-item--danger" onclick="deleteParcoursFromCard(event, <?php echo (int)$p['id']; ?>)"><?php echo $detachActionLabel; ?></button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($p['image'])): ?>
            <div class="card-image">
                <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
            </div>
        <?php endif; ?>

        <div class="card-content">
            <h3><?php echo htmlspecialchars($p['title']); ?></h3>
            <div><?php echo htmlspecialchars($p['description']); ?></div>
            <?php if (!$isVisibleParcours): ?>
                <div class="card-visibility-note"><?php echo htmlspecialchars(lmsIndexT('lms.index.card.visibility_hidden')); ?></div>
            <?php endif; ?>

            <div class="card-footer">
                <div class="progress-circle" data-percent="<?php echo (int)$percent; ?>"></div>
                <button class="open-btn"<?php echo $isVisibleParcours ? '' : ' type="button" disabled'; ?>><?php echo htmlspecialchars($isVisibleParcours ? lmsIndexT('lms.index.card.open') : lmsIndexT('lms.index.card.hidden')); ?></button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</section>

<?php if (count($packParcours) > 0): ?>
<section class="lms-parcours-section" id="lms-parcours-section-packs">
    <div class="lms-parcours-separator">
        <div class="lms-parcours-section__intro">
            <h2 class="generic-title generic-title--section"><?php echo htmlspecialchars(lmsIndexT('lms.index.section.packs')); ?></h2>
            <p class="generic-description"><?php echo htmlspecialchars(lmsIndexT('lms.index.section.packs_intro')); ?></p>
        </div>
    </div>

    <div class="container" id="lms-parcours-pack-grid">
    <?php foreach ($packParcours as $p):
        $total = (int)$p['total_missions'];
        $done = (int)$p['done_missions'];
        $percent = $total > 0 ? round(($done / $total) * 100) : 0;
        $isOwnerParcours = (int)($p['owner_organization_id'] ?? 0) === (int)$org['id'];
        $isVisibleParcours = !array_key_exists('isvisible', $p) || !empty($p['isvisible']);
        $canManageThisParcours = $canCreateParcours;
        $canEditThisParcours = $canEditParcours && $isOwnerParcours;
        $showMenuThisParcours = $canManageThisParcours || $canEditThisParcours;
        $detachActionLabel = $isOwnerParcours ? lmsIndexT('lms.index.card.action.delete') : lmsIndexT('lms.index.card.action.detach');
    ?>
    <div
        class="card<?php echo !$isVisibleParcours ? ' card--visibility-hidden' : ''; ?>"
        data-parcours-card="1"
        data-is-pack="1"
        data-parcours-id="<?php echo (int)$p['id']; ?>"
        data-parcours-title="<?php echo htmlspecialchars((string)$p['title'], ENT_QUOTES, 'UTF-8'); ?>"
        data-is-owner="<?php echo $isOwnerParcours ? '1' : '0'; ?>"
        data-can-edit="<?php echo $canEditThisParcours ? '1' : '0'; ?>"
        data-can-manage="<?php echo $canManageThisParcours ? '1' : '0'; ?>"
        data-total-missions="<?php echo $total; ?>"
        data-local-progress="<?php echo $user_id > 0 ? '0' : '1'; ?>"
        onclick="<?php echo $isVisibleParcours ? 'goToParcours(' . (int)$p['id'] . ')' : ''; ?>"
    >
        <?php if ($showMenuThisParcours): ?>
            <div class="card-menu-wrap" onclick="event.stopPropagation()">
                <button
                    type="button"
                    class="card-menu-trigger"
                    aria-label="Actions"
                    onclick="toggleParcoursCardMenu(event, <?php echo (int)$p['id']; ?>)"
                >...</button>
                <div class="card-menu" id="parcours-card-menu-<?php echo (int)$p['id']; ?>">
                    <?php if ($canEditThisParcours): ?>
                        <button type="button" class="card-menu-item" onclick="openEditParcoursDrawer(event, <?php echo (int)$p['id']; ?>)"><?php echo htmlspecialchars(lmsIndexT('lms.index.card.action.edit')); ?></button>
                    <?php endif; ?>
                    <?php if ($canManageThisParcours): ?>
                    <button type="button" class="card-menu-item card-menu-item--danger" onclick="deleteParcoursFromCard(event, <?php echo (int)$p['id']; ?>)"><?php echo $detachActionLabel; ?></button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($p['image'])): ?>
            <div class="card-image">
                <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="">
            </div>
        <?php endif; ?>

        <div class="card-content">
            <div class="card-create-kicker"><?php echo htmlspecialchars(lmsIndexT('lms.index.pack.kicker')); ?></div>
            <h3><?php echo htmlspecialchars($p['title']); ?></h3>
            <div><?php echo htmlspecialchars($p['description']); ?></div>
            <?php if (!$isVisibleParcours): ?>
                <div class="card-visibility-note"><?php echo htmlspecialchars(lmsIndexT('lms.index.card.visibility_hidden')); ?></div>
            <?php endif; ?>

            <div class="card-footer">
                <div class="progress-circle" data-percent="<?php echo (int)$percent; ?>"></div>
                <button class="open-btn"<?php echo $isVisibleParcours ? '' : ' type="button" disabled'; ?>><?php echo htmlspecialchars($isVisibleParcours ? lmsIndexT('lms.index.card.open') : lmsIndexT('lms.index.card.hidden')); ?></button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
</div>
</div>

<?= commonPageScriptTags('/omo/api/lms/catalog.js', [
    'userId' => (int)$user_id,
    'organizationId' => (int)$org['id'],
    'isEmbedded' => ($isEmbedded),
    'canCreateParcours' => ($canCreateParcours),
    'canEditParcours' => ($canEditParcours),
    'lmsIndexText' => [
    'choiceLabel' => lmsIndexT('lms.index.form.choice'),
    'correctChoiceLabel' => lmsIndexT('lms.index.form.correct_choice'),
    'removeLabel' => lmsIndexT('lms.index.form.remove'),
    'createHomework' => lmsIndexT('lms.index.form.create_homework'),
    'updateQuestion' => lmsIndexT('lms.index.form.update_question'),
    'createQuestion' => lmsIndexT('lms.index.form.create_question'),
    'requiredTitle' => lmsIndexT('lms.index.alert.required_title'),
    'requiredResume' => lmsIndexT('lms.index.alert.required_resume'),
    'keepTwoChoices' => lmsIndexT('lms.index.alert.keep_two_choices'),
    'reorderHomeworks' => lmsIndexT('lms.index.alert.reorder_homeworks'),
    'reorderQuestions' => lmsIndexT('lms.index.alert.reorder_questions'),
    'createHomeworkError' => lmsIndexT('lms.index.alert.create_homework'),
    'questionRequired' => lmsIndexT('lms.index.alert.question_required'),
    'answerRequired' => lmsIndexT('lms.index.alert.answer_required'),
    'minimumChoices' => lmsIndexT('lms.index.alert.minimum_choices'),
    'needCorrectChoice' => lmsIndexT('lms.index.alert.need_correct_choice'),
    'createQuestionError' => lmsIndexT('lms.index.alert.create_question'),
    'loadFormError' => lmsIndexT('lms.index.alert.load_form'),
    'loadCatalogError' => lmsIndexT('lms.index.alert.load_catalog'),
    'loadParcoursError' => lmsIndexT('lms.index.alert.load_parcours'),
    'loadMissionError' => lmsIndexT('lms.index.alert.load_mission'),
    'createMissionError' => lmsIndexT('lms.index.alert.create_mission'),
    'saveMissionError' => lmsIndexT('lms.index.alert.save_mission'),
    'saveMissionRefreshError' => lmsIndexT('lms.index.alert.save_mission_refresh'),
    'addPrerequisiteError' => lmsIndexT('lms.index.alert.add_prerequisite'),
    'removePrerequisiteError' => lmsIndexT('lms.index.alert.remove_prerequisite'),
    'removePrerequisiteConfirm' => lmsIndexT('lms.index.confirm.remove_prerequisite'),
    'deletePreviewError' => lmsIndexT('lms.index.delete.preview_failed'),
    'deleteConfirmNamed' => lmsIndexT('lms.index.delete.confirm_named'),
    'deleteConfirmGeneric' => lmsIndexT('lms.index.delete.confirm_generic'),
    'deleteFailed' => lmsIndexT('lms.index.delete.failed'),
    'deleteSuccess' => lmsIndexT('lms.index.delete.success'),
],
    'lmsParcoursBasePath' => lmsBuildLocalPath('/parcours.php'),
    'lmsParcoursCreatePath' => lmsBuildLocalPath('/parcours_create.php'),
    'lmsParcoursImportPath' => lmsBuildLocalPath('/parcours_import.php'),
    'lmsParcoursImportSavePath' => lmsBuildLocalPath('/import_parcours.php'),
    'lmsParcoursDeletePreviewPath' => lmsBuildLocalPath('/delete_parcours_preview.php'),
    'lmsParcoursDeletePath' => lmsBuildLocalPath('/delete_parcours.php'),
    'lmsMissionEditBasePath' => lmsBuildLocalPath('/mission_edit.php'),
    'lmsParcoursMissionPanelBasePath' => lmsBuildLocalPath('/parcours_missions_panel.php'),
    'lmsParcoursMissionAddPath' => lmsBuildLocalPath('/parcours_mission_add.php'),
    'lmsParcoursMissionRemovePath' => lmsBuildLocalPath('/parcours_mission_remove.php'),
    'lmsParcoursMissionCreatePath' => lmsBuildLocalPath('/parcours_mission_create.php'),
    'lmsParcoursMissionReorderPath' => lmsBuildLocalPath('/parcours_mission_reorder.php'),
    'lmsParcoursPackAddPath' => lmsBuildLocalPath('/parcours_pack_add.php'),
    'lmsParcoursPackRemovePath' => lmsBuildLocalPath('/parcours_pack_remove.php'),
    'lmsParcoursPackReorderPath' => lmsBuildLocalPath('/parcours_pack_reorder.php'),
    'lmsParcoursPrerequisiteAddPath' => lmsBuildLocalPath('/parcours_prerequisite_add.php'),
    'lmsParcoursPrerequisiteRemovePath' => lmsBuildLocalPath('/parcours_prerequisite_remove.php'),
    'lmsMissionDependencyAddPath' => lmsBuildLocalPath('/mission_dependency_add.php'),
    'lmsMissionDependencyRemovePath' => lmsBuildLocalPath('/mission_dependency_remove.php'),
    'lmsMissionHomeworkReorderPath' => lmsBuildLocalPath('/mission_homework_reorder.php'),
    'lmsMissionQuestionReorderPath' => lmsBuildLocalPath('/mission_question_reorder.php'),
], 'omoLmsCatalogConfig') ?>
<?php include 'inc/drawer.php'; ?>
</body>
</html>
