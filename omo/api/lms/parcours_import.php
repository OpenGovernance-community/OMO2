<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$managementContext = lmsResolveParcoursManagementContext($organizationId, 0, $currentUserId);
$hasOrganizationAccess = !empty($managementContext['hasOrganizationAccess']);
$canCreateParcours = !empty($managementContext['canCreate']);

if ($currentUserId <= 0 || !$hasOrganizationAccess || !$canCreateParcours || $organizationId <= 0) {
    http_response_code(403);
    echo '<div class="lms-import-parcours-view"><p>Acces refuse.</p></div>';
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
        <h2>Importer un parcours</h2>
        <p>Ajoutez a cette organisation un parcours deja partage comme public ou marque basic.</p>
    </section>

    <section class="lms-import-parcours-card">
        <div>
            <h3>Catalogue disponible</h3>
            <p>Selectionnez un parcours existant pour le lier a l organisation courante.</p>
        </div>

        <?php if (count($importableParcours) > 0): ?>
        <label class="lms-import-parcours-search">
            <span>Rechercher</span>
            <input type="search" data-lms-import-parcours-search="1" placeholder="Titre, description ou organisation">
        </label>
        <?php endif; ?>

        <div class="lms-import-parcours-list">
            <?php if (count($importableParcours) === 0): ?>
                <div class="lms-import-parcours-empty">Aucun parcours public ou basic n est disponible a l import pour le moment.</div>
            <?php else: ?>
                <?php foreach ($importableParcours as $item): ?>
                    <?php
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
                        class="lms-import-parcours-item"
                        data-lms-import-parcours-item="1"
                        data-search-text="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                        <div class="lms-import-parcours-item__copy">
                            <div>
                                <strong><?php echo htmlspecialchars((string)($item['title'] ?? '')); ?></strong>
                                <?php if (trim((string)($item['description'] ?? '')) !== ''): ?>
                                    <p><?php echo htmlspecialchars((string)$item['description']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="lms-import-parcours-item__meta">
                                <?php if (!empty($item['isbasic'])): ?>
                                    <span>Basic</span>
                                <?php endif; ?>
                                <?php if (!empty($item['ispublic'])): ?>
                                    <span>Public</span>
                                <?php endif; ?>
                                <?php if (trim((string)($item['owner_name'] ?? '')) !== ''): ?>
                                    <span>Orga: <?php echo htmlspecialchars((string)$item['owner_name']); ?></span>
                                <?php endif; ?>
                                <span><?php echo (int)($item['total_missions'] ?? 0); ?> missions</span>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="lms-import-parcours-item__action"
                            data-lms-import-parcours-id="<?php echo (int)($item['id'] ?? 0); ?>"
                        >Importer</button>
                    </article>
                <?php endforeach; ?>
                <div class="lms-import-parcours-empty" data-lms-import-parcours-empty-search="1" hidden>Aucun parcours ne correspond a cette recherche.</div>
            <?php endif; ?>
        </div>

        <div class="lms-import-parcours-actions">
            <button type="button" class="lms-import-parcours-cancel" onclick="closeDrawer()">Fermer</button>
        </div>
    </section>
</div>
