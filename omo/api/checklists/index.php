<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayControlList;
use dbObject\ControlList;
use dbObject\Holon;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$context = omoControlListResolveContext($organizationId, $currentHolonId);
if (empty($context['status'])) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape((string)($context['message'] ?? omoControlListT('control.error.context'))) . '</div>';
    exit;
}
$rootHolon = $context['rootHolon'];
$currentHolon = $context['currentHolon'];
$scopes = omoApiGetAvailableContextScopes($currentHolon instanceof Holon, $currentHolon, $rootHolon);
$scope = omoApiNormalizeContextScope($_GET['control_scope'] ?? 'contextual', $scopes);
$scopeHolonIds = $scope === 'children'
    ? omoApiGetDirectChildScopeHolonIds($currentHolon)
    : ($scope === 'descendants' ? omoApiGetDescendantHolonIds($currentHolon) : [(int)$currentHolon->getId()]);
$lists = new ArrayControlList();
$lists->loadForContext($organizationId, (int)$currentHolon->getId(), $scope, $scopeHolonIds);
$rows = [];
foreach ($lists as $list) {
    if (!($list instanceof ControlList) || !omoControlListCanView($list)) { continue; }
    $holon = $list->getHolon();
    $rows[] = [
        'list' => $list,
        'holonLabel' => $holon instanceof Holon ? $holon->getDisplayName() : '',
        'taskCount' => count($list->getTasks()),
    ];
}
$currentUrl = '/omo/api/checklists/index.php?oid=' . rawurlencode((string)$organizationId) . ($currentHolonId > 0 ? '&cid=' . rawurlencode((string)$currentHolonId) : '') . ($scope !== 'contextual' ? '&control_scope=' . rawurlencode($scope) : '');
$baseUrl = '/omo/api/checklists/index.php?oid=' . rawurlencode((string)$organizationId) . ($currentHolonId > 0 ? '&cid=' . rawurlencode((string)$currentHolonId) : '');
$createUrl = '/omo/api/checklists/edit.php?oid=' . rawurlencode((string)$organizationId) . ($currentHolonId > 0 ? '&cid=' . rawurlencode((string)$currentHolonId) : '');
$canCreate = omoControlListCanUsePermission($currentHolon, 'CAN_CREATE_CONTROL_LIST');
?>
<link rel="stylesheet" href="/omo/api/checklists/checklists.css?v=20260831-control-lists">
<div class="omo-control-lists omo-panel-view" id="omo-control-lists-root" data-control-oid="<?= (int)$organizationId ?>" data-control-cid="<?= (int)$currentHolonId ?>" data-control-current-url="<?= omoApiEscape($currentUrl) ?>" data-control-create-url="<?= omoApiEscape($createUrl) ?>">
    <header class="omo-panel-view__header omo-panel-view__header--stacked">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <span class="omo-panel-view__app-icon" aria-hidden="true"><img src="/omo/images/tools/control-list.png" alt=""></span>
                <div class="omo-panel-view__header-copy"><div class="generic-title-row generic-title-row--center"><h2 class="omo-panel-view__title"><?= omoApiEscape(omoControlListT('control.title')) ?></h2><span class="omo-panel-view__count"><?= count($rows) ?></span></div></div>
            </div>
            <?php if ($canCreate): ?><div class="omo-panel-view__header-actions"><button type="button" class="generic-action-button generic-action-button--main" data-control-open-url="<?= omoApiEscape($createUrl) ?>"><?= omoApiEscape(omoControlListT('control.action.new')) ?></button></div><?php endif; ?>
        </div>
        <?php if (count($scopes) > 1): ?>
            <div class="omo-panel-view__header-secondary"><div class="omo-control-lists__scopes" role="group" aria-label="Portée">
                <?php foreach ($scopes as $scopeOption): ?><a class="generic-action-button generic-action-button--secondary<?= $scope === $scopeOption ? ' is-active' : '' ?>" href="<?= omoApiEscape($baseUrl . ($scopeOption !== 'contextual' ? '&control_scope=' . rawurlencode($scopeOption) : '')) ?>"><?= omoApiEscape(omoControlListT('control.scope.' . $scopeOption)) ?></a><?php endforeach; ?>
            </div></div>
        <?php endif; ?>
    </header>
    <div class="omo-panel-view__body"><div class="omo-panel-view__body_content omo-control-lists__body">
        <?php if (count($rows) === 0): ?><div class="omo-empty-state"><?= omoApiEscape(omoControlListT('control.empty')) ?></div>
        <?php else: ?><div class="omo-control-lists__list">
            <?php foreach ($rows as $row): $list = $row['list']; $detailUrl = '/omo/api/checklists/detail.php?oid=' . rawurlencode((string)$organizationId) . '&id=' . (int)$list->getId() . ($currentHolonId > 0 ? '&cid=' . rawurlencode((string)$currentHolonId) : ''); ?>
                <article class="generic-soft-panel omo-control-list-card" data-control-open-url="<?= omoApiEscape($detailUrl) ?>" tabindex="0" role="button">
                    <div><h3 class="generic-card-title"><?= omoApiEscape($list->get('title')) ?></h3><?php if (trim((string)$list->get('description')) !== ''): ?><p><?= omoApiEscape(mb_strimwidth(trim((string)$list->get('description')), 0, 160, '…', 'UTF-8')) ?></p><?php endif; ?></div>
                    <div class="omo-control-list-card__meta"><span><?= omoApiEscape((string)$row['holonLabel']) ?></span><span><?= (int)$row['taskCount'] ?> activité(s)</span></div>
                </article>
            <?php endforeach; ?>
        </div><?php endif; ?>
    </div></div>
    <div class="omo-overlay-drawer omo-control-lists__drawer" data-control-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-control-close></div>
        <div class="omo-overlay-drawer__panel"><div class="omo-overlay-drawer__header generic-drawer-header generic-drawer-header--sticky"><div class="generic-drawer-header__copy"><h3 data-control-drawer-title><?= omoApiEscape(omoControlListT('control.title')) ?></h3><p data-control-drawer-description><?= omoApiEscape(omoControlListT('control.drawer.description')) ?></p></div><div class="generic-drawer-header__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-control-close><?= omoApiEscape(omoControlListT('control.action.close')) ?></button></div></div><div class="omo-overlay-drawer__body" data-control-drawer-body></div></div>
    </div>
</div>
<script src="/omo/api/checklists/checklists.js?v=20260831-control-lists"></script>
