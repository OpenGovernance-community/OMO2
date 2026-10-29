<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/choice/holon-removal-fields.php';
require_once __DIR__ . '/archive-preview.php';

use dbObject\{ArrayHolon, Holon, Organization};

$sourceLang = [
    'title' => ['text' => 'Archives des cercles et rôles', 'context' => 'Structure archive drawer title.'],
    'archive' => ['text' => 'Archiver', 'context' => 'Archive a circle or role with its descendants.'],
    'restore' => ['text' => 'Restaurer', 'context' => 'Restore an archived circle or role.'],
    'back' => ['text' => 'Retour aux archives', 'context' => 'Return to the archived structure list.'],
    'empty' => ['text' => 'Aucun cercle ou rôle archivé accessible.', 'context' => 'Empty structure archives.'],
    'hint' => ['text' => 'Cet espace et ses descendants disparaîtront des vues courantes. Leurs données et affectations seront conservées pour une restauration ultérieure.', 'context' => 'Archive confirmation explanation.'],
    'restore_hint' => ['text' => 'La restauration réactive cet espace et les descendants archivés avec lui. Les éléments archivés auparavant restent archivés.', 'context' => 'Restore confirmation explanation.'],
    'date' => ['text' => 'Archive le {date}', 'context' => 'Structure archive date.'],
    'contents' => ['text' => 'Contenu conservé', 'context' => 'Read-only archived subtree preview.'],
    'preview' => ['text' => 'Contenu actuel des espaces', 'context' => 'Subtree content before archiving and handling its authorities.'],
    'authority_unavailable' => ['text' => 'Autorité #{id} indisponible', 'context' => 'Unresolved or inaccessible authority reference in an archive.'],
    'authority_parent' => ['text' => 'Autorité parente : {name} — {holon}', 'context' => 'Parent authority and owning holon in archive preview.'],
    'authority_local' => ['text' => 'Autorité locale, sans autorité parente', 'context' => 'Local authority in archive preview.'],
    'authority_parent_unavailable' => ['text' => 'Autorité parente non accessible', 'context' => 'Inaccessible parent authority in archive preview.'],
    'authority_shell' => ['text' => 'Délégation complete', 'context' => 'Authority shell in archive preview.'],
    'older' => ['text' => 'Archive anterieure', 'context' => 'Child archived independently before its parent.'],
    'saved_archive' => ['text' => 'Espace et descendants archivés.', 'context' => 'Archive success notification.'],
    'saved_restore' => ['text' => 'Espace restauré.', 'context' => 'Restore success notification.'],
    'forbidden' => ['text' => 'Cet espace est inaccessible ou vous ne disposez pas du droit de suppression requis.', 'context' => 'Denied structure archive or restore.'],
    'unavailable' => ['text' => 'Organisation indisponible.', 'context' => 'Missing structure organization.'],
    'template_in_use' => ['text' => 'Un modèle de cet espace est encore utilisé en dehors de ses descendants. Déplacez ce modèle avant d’archiver.', 'context' => 'Archive blocked by an external template dependency.'],
    'template_conflict' => ['text' => 'La restauration créerait un doublon de modèle unique ou un conflit de structure. Vérifiez les espaces actifs avant de restaurer.', 'context' => 'Restore blocked by template constraints.'],
    'failed' => ['text' => 'Impossible de modifier les archives pour le moment.', 'context' => 'Structure archive operation error.'],
];
$bundle = omoLoadTranslationBundle('omo_holon_archives', $sourceLang);
$tr = static fn(string $key, array $values = []): string => t($key, $values, $bundle, $sourceLang);
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$organization = new Organization();
$root = $organization->load($organizationId) ? $organization->getEnabledStructuralRootHolon() : null;
$holonId = (int)($_POST['hid'] ?? $_GET['hid'] ?? 0);
$holon = new Holon();
$found = $root && $holonId > 0 && $holon->load($holonId)
    && $holon->resolveOrganizationId() === $organizationId && $holon->isDescendantOf($root, false);
$restoring = $found && $holon->get('archived_at') instanceof DateTimeInterface;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    $action = (string)($_POST['action'] ?? '');
    $choice = $_POST['authority_choice'] ?? '';
    if ($action === 'archive' && !\dbObject\Authority::validateHolonRemovalChoice($choice)) {
        http_response_code(422);
        echo json_encode(['status' => false, 'message' => omoHolonRemovalT('required')]);
        exit;
    }
    $result = $found && in_array($action, ['archive', 'restore'], true)
        ? $holon->changeArchiveState($organizationId, $action === 'restore', is_string($choice) ? $choice : '')
        : ['status' => false, 'reason' => 'forbidden'];
    if (empty($result['status'])) http_response_code(422);
    $key = !empty($result['status']) ? 'saved_' . $action : ($result['reason'] ?? 'failed');
    $result['message'] = $tr(isset($sourceLang[$key]) ? $key : 'failed');
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$root || !$root->canViewDetail() || ($holonId > 0 && (!$found || !($restoring ? $holon->canViewArchive() : $holon->canManageArchive())))) {
    http_response_code(403);
    echo '<p class="generic-description">' . omoApiEscape($tr('forbidden')) . '</p>';
    exit;
}
$url = '/omo/api/holons/archive.php';
$previewNodes = $holonId > 0 ? array_values(array_filter($holon->getArchiveSubtree(), static fn(Holon $node): bool => (bool)$node->get('visible'))) : [];
$visibleHolonIds = array_fill_keys(array_map(static fn(Holon $node): int => (int)$node->getId(), $previewNodes), true);
?>
<div data-holon-archives data-topbar-modal-max-width="760px" class="generic-stack">
    <div class="generic-drawer-header">
        <div class="generic-drawer-header__copy">
            <h3 class="generic-card-title"><?= omoApiEscape($tr('title')) ?></h3>
        </div>
    </div>
    <div class="generic-drawer-content generic-stack">
    <?php if ($holonId > 0): ?>
        <h4 class="generic-card-title"><?= omoApiEscape($holon->getFullDisplayName()) ?></h4>
        <p class="generic-description"><?= omoApiEscape($tr($restoring ? 'restore_hint' : 'hint')) ?></p>
        <?php if ($restoring): ?>
            <p class="generic-description"><?= omoApiEscape($tr('date', ['date' => $holon->get('archived_at')->format('d.m.Y H:i')])) ?></p>
        <?php endif; ?>
        <section class="generic-section">
            <h4 class="generic-card-title"><?= omoApiEscape($tr($restoring ? 'contents' : 'preview')) ?></h4>
            <div class="generic-stack">
            <?php foreach ($previewNodes as $node): ?>
                <article class="generic-soft-panel generic-soft-panel--stack">
                    <h4 class="generic-card-title"><?= omoApiEscape($node->getFullDisplayName()) ?></h4>
                    <p class="generic-description"><?= omoApiEscape($node->getTemplateLabel()) ?>
                        <?php if (($node->getParametersArray()['holon_archive']['root_id'] ?? $holonId) != $holonId): ?>
                            &middot; <?= omoApiEscape($tr('older')) ?>
                        <?php endif; ?>
                    </p>
                    <?php foreach ($node->getPropertyEntries() as $entry): ?>
                        <?php $propertyHtml = omoHolonArchivePropertyHtml($entry, $organization, $tr, $visibleHolonIds); if ($propertyHtml === '') continue; ?>
                        <section class="generic-section generic-section--stack">
                            <h5 class="generic-card-title"><?= omoApiEscape($entry['name'] ?? '') ?></h5>
                            <?= $propertyHtml ?>
                        </section>
                    <?php endforeach; ?>
                </article>
            <?php endforeach; ?>
            </div>
        </section>
        <form action="<?= $url ?>" method="post" class="generic-stack">
            <input type="hidden" name="_csrf" value="<?= omoApiEscape(commonCsrfToken()) ?>">
            <input type="hidden" name="hid" value="<?= $holonId ?>">
            <input type="hidden" name="action" value="<?= $restoring ? 'restore' : 'archive' ?>">
            <?php if (!$restoring) omoRenderHolonRemovalFields('', true); ?>
            <div class="generic-action-row">
            <?php if ($restoring): ?><button type="button" class="generic-action-button generic-action-button--secondary" data-archive-open="<?= $url ?>"><?= omoApiEscape($tr('back')) ?></button><?php endif; ?>
            <?php if (!$restoring || $holon->canManageArchive(true)): ?>
                <button type="submit" class="generic-action-button"><?= omoApiEscape($tr($restoring ? 'restore' : 'archive')) ?></button>
            <?php endif; ?>
            </div>
        </form>
    <?php else: ?>
        <?php
        $archives = new ArrayHolon();
        $archives->loadArchivesForOrganization((int)$root->getId());
        $hasArchive = false;
        foreach ($archives as $archive):
            if (!$archive->canViewArchive()) continue;
            $hasArchive = true;
        ?>
            <article class="generic-soft-panel">
                <button type="button" class="generic-action-button generic-action-button--secondary" data-archive-open="<?= $url ?>?hid=<?= (int)$archive->getId() ?>"><?= omoApiEscape($archive->getFullDisplayName()) ?></button>
                <p class="generic-description"><?= omoApiEscape($tr('date', ['date' => $archive->get('archived_at')->format('d.m.Y H:i')])) ?> &middot; <?= omoApiEscape($archive->getParentHolon()->getFullDisplayName()) ?></p>
            </article>
        <?php endforeach; ?>
        <?php if (!$hasArchive): ?><p class="generic-description"><?= omoApiEscape($tr('empty')) ?></p><?php endif; ?>
    <?php endif; ?>
    </div>
</div>
<?= commonPageScriptTags('/omo/api/holons/archive.js', ['title' => $tr('title'), 'error' => $tr('failed'), 'organizationId' => $organizationId, 'rootId' => (int)$root->getId()]) ?>
