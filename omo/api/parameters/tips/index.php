<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/tips/translations.php';
if (!commonCurrentUserIsSiteAdminModeEnabled() || commonGetCurrentShareLink()) {
    http_response_code(403);
    echo omoApiEscape(commonTipsT('forbidden'));
    return;
}
if (!\dbObject\Tip::isAvailable()) {
    echo '<p class="generic-soft-panel">' . omoApiEscape(commonTipsT('unavailable')) . '</p>';
    return;
}
$tip = new \dbObject\Tip();
$id = (int)($_GET['id'] ?? 0);
if ($id > 0 && !$tip->load($id)) { http_response_code(404); return; }
if ($id === 0) $tip->set('active', 1);
$tips = new \dbObject\ArrayTip();
$tips->load();
$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
?>
<div data-tips-admin data-oid="<?= $organizationId ?>">
    <div class="generic-drawer-content generic-stack">
        <h2 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(commonTipsT('manage')) ?></h2>
        <p class="generic-description"><?= omoApiEscape(commonTipsT('description')) ?></p>
        <div class="generic-action-row">
            <button type="button" class="generic-action-button generic-action-button--secondary" data-tip-edit="0"><?= omoApiEscape(commonTipsT('new')) ?></button>
        </div>
        <?php $tip->display('adminEdit.php', [
            'buttons' => false, 'bindFormScripts' => false, 'includeComponentAssets' => false,
            'action' => '/omo/api/parameters/tips/save.php?oid=' . $organizationId,
            'fields' => ['text', 'link', 'image', 'active'],
        ]); ?>
        <div class="generic-action-row">
            <button type="button" class="generic-action-button generic-action-button--secondary" data-tip-remove-image><?= omoApiEscape(commonTipsT('removeImage')) ?></button>
            <button type="submit" form="formulaire-edit" class="generic-action-button generic-action-button--main" data-tip-save><?= omoApiEscape(commonTipsT('save')) ?></button>
        </div>
        <?php if (!count($tips)): ?><p class="generic-help-text"><?= omoApiEscape(commonTipsT('empty')) ?></p><?php endif; ?>
        <?php foreach ($tips as $entry): ?>
            <section class="generic-soft-panel generic-stack generic-stack--compact">
                <p class="generic-description"><?= omoApiEscape($entry->get('text')) ?></p>
                <div class="generic-action-row">
                    <span class="generic-help-text"><?= omoApiEscape(commonTipsT($entry->get('active') ? 'published' : 'draft')) ?></span>
                    <button type="button" class="generic-action-button generic-action-button--compact generic-action-button--secondary" data-tip-edit="<?= (int)$entry->getId() ?>"><?= omoApiEscape(commonTipsT('edit')) ?></button>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</div>
<?= commonPageScriptTags('/omo/api/parameters/tips/index.js', ['error' => commonTipsT('error')]) ?>
