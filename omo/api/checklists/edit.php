<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ControlList;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$context = omoControlListResolveContext($organizationId, $currentHolonId);
$listId = (int)($_GET['id'] ?? 0);
$list = $listId > 0 ? omoControlListLoad($listId, $organizationId) : new ControlList();
if (empty($context['status']) || !($list instanceof ControlList) || ($listId > 0 && !omoControlListCanManage($list)) || ($listId === 0 && !omoControlListCanUsePermission($context['currentHolon'], 'CAN_CREATE_CONTROL_LIST'))) {
    http_response_code(403); echo '<div class="omo-empty-state">' . omoApiEscape(omoControlListT('control.error.forbidden')) . '</div>'; exit;
}
$returnUrl = $listId > 0
    ? '/omo/api/checklists/detail.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . $listId . ($currentHolonId > 0 ? '&cid=' . rawurlencode((string)$currentHolonId) : '')
    : '/omo/api/checklists/index.php?oid=' . rawurlencode((string)$organizationId) . ($currentHolonId > 0 ? '&cid=' . rawurlencode((string)$currentHolonId) : '');
?>
<div class="omo-control-list-detail generic-drawer-content" data-control-drawer-title="<?= $listId > 0 ? 'Modifier la liste' : 'Nouvelle liste de contrôle' ?>" data-control-drawer-description="<?= omoApiEscape($listId > 0 ? (string)$list->get('title') : '') ?>">
    <form class="generic-form-stack" action="/omo/api/checklists/action.php" method="post" data-control-form>
        <input type="hidden" name="control_action" value="save_list"><input type="hidden" name="oid" value="<?= $organizationId ?>"><input type="hidden" name="cid" value="<?= $currentHolonId ?>"><?php if ($listId > 0): ?><input type="hidden" name="id" value="<?= $listId ?>"><?php endif; ?>
        <section class="generic-section generic-section--stack"><label class="omo-control-field"><span><?= omoApiEscape(omoControlListT('control.field.title')) ?></span><input class="generic-form-control" type="text" name="title" maxlength="255" required autofocus value="<?= omoApiEscape($list->get('title')) ?>"></label><label class="omo-control-field"><span><?= omoApiEscape(omoControlListT('control.field.description')) ?></span><textarea class="generic-form-control" name="description" rows="5"><?= omoApiEscape($list->get('description')) ?></textarea></label></section>
        <div class="omo-control-list-detail__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-control-open-url="<?= omoApiEscape($returnUrl) ?>">Annuler</button><button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape(omoControlListT('control.action.save')) ?></button></div><div class="omo-control-feedback" data-control-feedback aria-live="polite"></div>
    </form>
</div>
