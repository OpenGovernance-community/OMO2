<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

$sourceLang = [
    'lms.parcours_import.error.access_denied' => ['text' => 'Acces refuse.', 'context' => 'Error shown when the user cannot access the parcours import drawer.'],
    'lms.parcours_import.hero.title' => ['text' => 'Importer un parcours', 'context' => 'Title of the parcours import drawer.'],
    'lms.parcours_import.hero.intro' => ['text' => 'Ajoutez a cette organisation un parcours deja partage comme public ou marque basic.', 'context' => 'Intro text shown in the parcours import drawer.'],
    'lms.parcours_import.catalog.title' => ['text' => 'Catalogue disponible', 'context' => 'Title shown above the list of importable parcours.'],
    'lms.parcours_import.catalog.intro' => ['text' => 'Selectionnez un parcours existant pour le lier a l organisation courante.', 'context' => 'Intro text shown above the list of importable parcours.'],
    'lms.parcours_import.search.label' => ['text' => 'Rechercher', 'context' => 'Label shown above the import search field.'],
    'lms.parcours_import.search.placeholder' => ['text' => 'Titre, description ou organisation', 'context' => 'Placeholder shown in the import search field.'],
    'lms.parcours_import.empty' => ['text' => 'Aucun parcours public ou basic n est disponible a l import pour le moment.', 'context' => 'Empty state shown when no parcours can be imported.'],
    'lms.parcours_import.type.pack' => ['text' => 'Pack', 'context' => 'Badge shown on importable pack parcours.'],
    'lms.parcours_import.type.parcours' => ['text' => 'Parcours', 'context' => 'Badge shown on importable simple parcours.'],
    'lms.parcours_import.badge.basic' => ['text' => 'Basic', 'context' => 'Badge shown when an importable parcours is marked basic.'],
    'lms.parcours_import.badge.public' => ['text' => 'Public', 'context' => 'Badge shown when an importable parcours is public.'],
    'lms.parcours_import.owner' => ['text' => 'Orga : {name}', 'context' => 'Badge showing the owner organization of an importable parcours.'],
    'lms.parcours_import.count.parcours' => ['text' => '{count} parcours', 'context' => 'Badge showing how many child parcours are inside an importable pack.'],
    'lms.parcours_import.count.missions' => ['text' => '{count} missions', 'context' => 'Badge showing how many missions are inside an importable parcours.'],
    'lms.parcours_import.action.import' => ['text' => 'Importer', 'context' => 'Button used to import a parcours.'],
    'lms.parcours_import.empty_search' => ['text' => 'Aucun parcours ne correspond a cette recherche.', 'context' => 'Empty state shown when no import search result matches.'],
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
<style>
.lms-import-parcours-view {
    display: grid;
    gap: 18px;
}

.lms-import-parcours-hero,
.lms-import-parcours-card {
    padding: 20px 22px;
    border-radius: 18px;
    background: var(--bg-card);
    box-shadow: var(--shadow);
}

.lms-import-parcours-hero {
    border: 1px solid color-mix(in srgb, var(--primary) 18%, var(--border-color));
    background:
        radial-gradient(circle at top right, color-mix(in srgb, var(--primary) 20%, transparent), transparent 42%),
        linear-gradient(135deg, color-mix(in srgb, var(--primary) 8%, var(--bg-card)), var(--bg-card));
}

.lms-import-parcours-hero h2,
.lms-import-parcours-card h3 {
    margin: 0 0 8px;
}

.lms-import-parcours-hero p,
.lms-import-parcours-card p,
.lms-import-parcours-item__copy p,
.lms-import-parcours-empty {
    margin: 0;
    color: var(--text-light);
    line-height: 1.5;
}

.lms-import-parcours-card {
    border: 1px solid var(--border-color);
    display: grid;
    gap: 16px;
}

.lms-import-parcours-search {
    display: grid;
    gap: 6px;
}

.lms-import-parcours-search input {
    width: 100%;
}

.lms-import-parcours-list {
    display: grid;
    gap: 12px;
    max-height: min(58vh, 560px);
    overflow: auto;
    padding-right: 4px;
}

.lms-import-parcours-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 14px;
    align-items: start;
    padding: 14px 16px;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    background: var(--bg-main);
}

.lms-import-parcours-item--pack {
    border-color: color-mix(in srgb, var(--primary) 26%, var(--border-color));
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--primary) 7%, var(--bg-main)), var(--bg-main));
}

.lms-import-parcours-item[hidden],
.lms-import-parcours-empty[hidden] {
    display: none !important;
}

.lms-import-parcours-item__copy {
    display: grid;
    gap: 8px;
}

.lms-import-parcours-item__copy strong {
    display: block;
}

.lms-import-parcours-item__type {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    width: fit-content;
    padding: 4px 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--primary) 8%, var(--bg-card));
    color: var(--text-light);
    font-size: 0.82rem;
    letter-spacing: 0.02em;
}

.lms-import-parcours-item--pack .lms-import-parcours-item__type {
    background: color-mix(in srgb, var(--primary) 15%, var(--bg-card));
    color: var(--primary);
}

.lms-import-parcours-item__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.lms-import-parcours-item__meta span {
    padding: 4px 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--primary) 8%, var(--bg-card));
    color: var(--text-light);
    font-size: 0.92rem;
}

.lms-import-parcours-item__action {
    margin-top: 0;
    white-space: nowrap;
}

.lms-import-parcours-empty {
    padding: 16px 18px;
    border-radius: 14px;
    background: color-mix(in srgb, var(--primary) 7%, var(--bg-main));
}

.lms-import-parcours-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.lms-import-parcours-cancel {
    background: var(--border-color);
    color: var(--text-main);
}

@media (max-width: 720px) {
    .lms-import-parcours-item,
    .lms-import-parcours-actions {
        grid-template-columns: 1fr;
        flex-direction: column-reverse;
    }

    .lms-import-parcours-item__action,
    .lms-import-parcours-cancel {
        width: 100%;
    }
}
</style>

<div class="lms-import-parcours-view" data-lms-parcours-importer="1">
    <section class="lms-import-parcours-hero">
        <h2><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.hero.title')); ?></h2>
        <p><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.hero.intro')); ?></p>
    </section>

    <section class="lms-import-parcours-card">
        <div>
            <h3><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.catalog.title')); ?></h3>
            <p><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.catalog.intro')); ?></p>
        </div>

        <?php if (count($importableParcours) > 0): ?>
        <label class="lms-import-parcours-search">
            <span><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.search.label')); ?></span>
            <input type="search" data-lms-import-parcours-search="1" placeholder="<?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.search.placeholder')); ?>">
        </label>
        <?php endif; ?>

        <div class="lms-import-parcours-list">
            <?php if (count($importableParcours) === 0): ?>
                <div class="lms-import-parcours-empty"><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.empty')); ?></div>
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
                                <strong><?php echo htmlspecialchars((string)($item['title'] ?? '')); ?></strong>
                                <?php if (trim((string)($item['description'] ?? '')) !== ''): ?>
                                    <p><?php echo htmlspecialchars((string)$item['description']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="lms-import-parcours-item__meta">
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
                <div class="lms-import-parcours-empty" data-lms-import-parcours-empty-search="1" hidden><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.empty_search')); ?></div>
            <?php endif; ?>
        </div>

        <div class="lms-import-parcours-actions">
            <button type="button" class="lms-import-parcours-cancel" onclick="closeDrawer()"><?php echo htmlspecialchars(lmsParcoursImportT('lms.parcours_import.action.close')); ?></button>
        </div>
    </section>
</div>
