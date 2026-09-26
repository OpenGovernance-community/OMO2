<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

$sourceLang = [
    'lms.parcours_import.error.access_denied' => ['text' => 'Accès refusé.', 'context' => 'Error shown when the user cannot access the parcours import drawer.'],
    'lms.parcours_import.hero.title' => ['text' => 'Importer un parcours', 'context' => 'Title of the parcours import drawer.'],
    'lms.parcours_import.hero.intro' => ['text' => 'Ajoutez à cette organisation un parcours déjà partagé comme public ou marqué « Basic ».', 'context' => 'Intro text shown in the parcours import drawer.'],
    'lms.parcours_import.catalog.title' => ['text' => 'Catalogue disponible', 'context' => 'Title shown above the list of importable parcours.'],
    'lms.parcours_import.catalog.intro' => ['text' => 'Sélectionnez un parcours existant pour le lier à l’organisation courante.', 'context' => 'Intro text shown above the list of importable parcours.'],
    'lms.parcours_import.search.label' => ['text' => 'Rechercher', 'context' => 'Label shown above the import search field.'],
    'lms.parcours_import.search.placeholder' => ['text' => 'Titre, description ou organisation', 'context' => 'Placeholder shown in the import search field.'],
    'lms.parcours_import.empty' => ['text' => 'Aucun parcours public ou « Basic » n’est disponible à l’import pour le moment.', 'context' => 'Empty state shown when no parcours can be imported.'],
    'lms.parcours_import.type.pack' => ['text' => 'Pack', 'context' => 'Badge shown on importable pack parcours.'],
    'lms.parcours_import.type.parcours' => ['text' => 'Parcours', 'context' => 'Badge shown on importable simple parcours.'],
    'lms.parcours_import.badge.basic' => ['text' => 'Basic', 'context' => 'Badge shown when an importable parcours is marked basic.'],
    'lms.parcours_import.badge.public' => ['text' => 'Public', 'context' => 'Badge shown when an importable parcours is public.'],
    'lms.parcours_import.owner' => ['text' => 'Orga : {name}', 'context' => 'Badge showing the owner organization of an importable parcours.'],
    'lms.parcours_import.count.parcours' => ['text' => '{count} parcours', 'context' => 'Badge showing how many child parcours are inside an importable pack.'],
    'lms.parcours_import.count.missions' => ['text' => '{count} missions', 'context' => 'Badge showing how many missions are inside an importable parcours.'],
    'lms.parcours_import.action.import' => ['text' => 'Importer', 'context' => 'Button used to import a parcours.'],
    'lms.parcours_import.empty_search' => ['text' => 'Aucun parcours ne correspond à cette recherche.', 'context' => 'Empty state shown when no import search result matches.'],
    'lms.parcours_import.action.close' => ['text' => 'Fermer', 'context' => 'Button used to close the parcours import drawer.'],
];

$lang = omoLoadTranslationBundle('omo_lms_parcours_import', $sourceLang);

function lmsParcoursImportT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$managementContext = lmsResolveParcoursManagementContext($organizationId, 0, $currentUserId);
$hasOrganizationAccess = !empty($managementContext['hasOrganizationAccess']);
$canCreateParcours = !empty($managementContext['canCreate']);

if ($currentUserId <= 0 || !$hasOrganizationAccess || !$canCreateParcours || $organizationId <= 0) {
    http_response_code(403);
    echo '<div class="lms-import-parcours-view"><p>' . htmlspecialchars(lmsParcoursImportT('lms.parcours_import.error.access_denied')) . '</p></div>';
    exit;
}

$importableParcours = \dbObject\Parcours::fetchImportableForOrganization($organizationId);
?>
<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/lms/css/parcours-import.css') ?>">

<div class="lms-import-parcours-view" data-lms-parcours-importer="1">
    <section class="lms-import-parcours-hero">
        <h2 class="generic-title generic-title--large"><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.hero.title')); ?></h2>
        <p class="generic-description"><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.hero.intro')); ?></p>
    </section>

    <section class="lms-import-parcours-card">
        <div>
            <h3 class="generic-title generic-title--big"><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.catalog.title')); ?></h3>
            <p class="generic-description"><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.catalog.intro')); ?></p>
        </div>

        <?php if (count($importableParcours) > 0): ?>
        <label class="lms-import-parcours-search">
            <span><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.search.label')); ?></span>
            <input type="search" data-lms-import-parcours-search="1" placeholder="<?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.search.placeholder')); ?>">
        </label>
        <?php endif; ?>

        <div class="lms-import-parcours-list">
            <?php if (count($importableParcours) === 0): ?>
                <div class="lms-import-parcours-empty generic-description"><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.empty')); ?></div>
            <?php else: ?>
                <?php foreach ($importableParcours as $item): ?>
                    <?php
                    $isPack = !empty($item['ispack']);
                    $searchText = trim(
                        (string)($item['title'] ?? '') . ' ' .
                        (string)($item['description'] ?? '') . ' ' .
                        (string)($item['owner_name'] ?? '')
                    );
                    $searchText = function_exists('mb_strtolower')
                        ? mb_strtolower($searchText, 'UTF-8')
                        : strtolower($searchText);
                    ?>
                    <article
                        class="lms-import-parcours-item<?php echo $isPack ? ' lms-import-parcours-item--pack' : ''; ?>"
                        data-lms-import-parcours-item="1"
                        data-search-text="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                        <div class="lms-import-parcours-item__copy">
                            <div>
                                <span class="lms-import-parcours-item__type"><?php echo htmlspecialchars($isPack ? lmsParcoursImportT('lms.parcours_import.type.pack') : lmsParcoursImportT('lms.parcours_import.type.parcours')); ?></span>
                                <strong class="generic-title generic-title--compact"><?php echo htmlspecialchars((string)($item['title'] ?? '')); ?></strong>
                                <?php if (trim((string)($item['description'] ?? '')) !== ''): ?>
                                    <p class="generic-description generic-description--compact"><?php echo htmlspecialchars((string)$item['description']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="lms-import-parcours-item__meta generic-meta generic-meta--compact">
                                <?php if (!empty($item['isbasic'])): ?>
                                    <span><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.badge.basic')); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item['ispublic'])): ?>
                                    <span><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.badge.public')); ?></span>
                                <?php endif; ?>
                                <?php if (trim((string)($item['owner_name'] ?? '')) !== ''): ?>
                                    <span><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.owner', ['name' => (string)$item['owner_name']])); ?></span>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($isPack ? lmsParcoursImportT('lms.parcours_import.count.parcours', ['count' => (string)((int)($item['total_parcours'] ?? 0))]) : lmsParcoursImportT('lms.parcours_import.count.missions', ['count' => (string)((int)($item['total_missions'] ?? 0))])); ?></span>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="lms-import-parcours-item__action"
                            data-lms-import-parcours-id="<?php echo (int)($item['id'] ?? 0); ?>"
                        ><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.action.import')); ?></button>
                    </article>
                <?php endforeach; ?>
                <div class="lms-import-parcours-empty generic-description" data-lms-import-parcours-empty-search="1" hidden><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.empty_search')); ?></div>
            <?php endif; ?>
        </div>

        <div class="lms-import-parcours-actions">
            <button type="button" class="lms-import-parcours-cancel" onclick="closeDrawer()"><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.action.close')); ?></button>
        </div>
    </section>
</div>
