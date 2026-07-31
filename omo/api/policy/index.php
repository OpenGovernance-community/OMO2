<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayRule;
use dbObject\Authority;
use dbObject\Holon;
use dbObject\Rule;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$context = omoPolicyResolveContext($organizationId, $currentHolonId);
if (empty($context['status'])) {
    http_response_code(403);
    ?>
    <div class="omo-panel-view"><div class="omo-panel-view__body"><div class="omo-panel-view__body_content"><div class="omo-empty-state"><?= omoApiEscape((string)$context['message']) ?></div></div></div></div>
    <?php
    exit;
}

$currentHolon = $context['currentHolon'];
$rootHolon = $context['rootHolon'] ?? null;
$availableScopes = omoApiGetAvailableContextScopes($currentHolon instanceof \dbObject\Holon, $currentHolon, $rootHolon);
$policyScope = omoApiNormalizeContextScope($_GET['policy_scope'] ?? 'contextual', $availableScopes);
$scopeHolonIds = $policyScope === 'children'
    ? omoApiGetDirectChildScopeHolonIds($currentHolon)
    : ($policyScope === 'descendants' ? omoApiGetDescendantHolonIds($currentHolon) : [(int)$currentHolon->getId()]);
$rules = new ArrayRule();
$rules->loadForPolicyContexts($organizationId, $scopeHolonIds);
$policySort = omoPolicyNormalizeSort($_GET['policy_sort'] ?? 'alpha');
$policyGroup = omoPolicyNormalizeGroup($_GET['policy_group'] ?? 'holon');
$policyRuleEntries = [];
foreach ($rules as $rule) {
    if (!($rule instanceof Rule)) {
        continue;
    }

    $policyRuleEntries[] = [
        'rule' => $rule,
        'holon' => $rule->getHolon(),
        'authority' => $rule->getAuthority(),
    ];
}
usort($policyRuleEntries, static function (array $left, array $right) use ($policySort) {
    $leftRule = $left['rule'];
    $rightRule = $right['rule'];
    $dateField = $policySort === 'created' ? 'created_at' : ($policySort === 'updated' ? 'updated_at' : null);
    if ($dateField !== null) {
        $leftDate = $leftRule->get($dateField);
        $rightDate = $rightRule->get($dateField);
        $leftTimestamp = $leftDate instanceof DateTimeInterface ? $leftDate->getTimestamp() : 0;
        $rightTimestamp = $rightDate instanceof DateTimeInterface ? $rightDate->getTimestamp() : 0;
        if ($leftTimestamp !== $rightTimestamp) {
            return $rightTimestamp <=> $leftTimestamp;
        }
    }

    return strnatcasecmp((string)$leftRule->get('title'), (string)$rightRule->get('title'));
});

$policyGroupNodes = [];
$policyRegisterNode = static function ($key, $label, $parentKey = null) use (&$policyGroupNodes) {
    if (!isset($policyGroupNodes[$key])) {
        $policyGroupNodes[$key] = [
            'key' => $key,
            'label' => $label,
            'parent' => $parentKey,
            'rules' => [],
            'children' => [],
        ];
    } elseif ($policyGroupNodes[$key]['parent'] === null && $parentKey !== null) {
        $policyGroupNodes[$key]['parent'] = $parentKey;
    }

    return $key;
};
$policyRegisterHolon = null;
$policyRegisterHolon = static function ($holon, array $seen = []) use (&$policyRegisterHolon, $policyRegisterNode) {
    if (!($holon instanceof Holon)) {
        return null;
    }

    $holonId = (int)$holon->getId();
    if ($holonId <= 0 || isset($seen[$holonId])) {
        return null;
    }
    $seen[$holonId] = true;
    $parent = $holon->getParentHolon();
    $parentKey = $parent instanceof Holon ? $policyRegisterHolon($parent, $seen) : null;
    return $policyRegisterNode('holon:' . $holonId, $holon->getFullDisplayName(), $parentKey);
};
$policyRegisterAuthority = null;
$policyRegisterAuthority = static function ($authority, array $seen = []) use (&$policyRegisterAuthority, $policyRegisterNode) {
    if (!($authority instanceof Authority)) {
        return null;
    }

    $authorityId = (int)$authority->getId();
    if ($authorityId <= 0 || isset($seen[$authorityId])) {
        return null;
    }
    $seen[$authorityId] = true;
    $parent = $authority->getParent();
    $parentKey = $parent instanceof Authority ? $policyRegisterAuthority($parent, $seen) : null;
    if ((int)$authority->get('is_shell') === 1) {
        return $parentKey;
    }
    $label = trim((string)$authority->get('label'));
    return $policyRegisterNode('authority:' . $authorityId, $label !== '' ? $label : omoPolicyT('policy.group.unnamed_authority'), $parentKey);
};
if ($policyGroup === 'none') {
    $policyGroupNodes['flat'] = [
        'key' => 'flat',
        'label' => '',
        'parent' => null,
        'rules' => $policyRuleEntries,
        'children' => [],
    ];
} else {
    foreach ($policyRuleEntries as $entry) {
        $ruleHolon = $entry['holon'];
        $ruleAuthority = $entry['authority'];
        if ($policyGroup === 'authority' && $ruleAuthority instanceof Authority) {
            $nodeKey = $policyRegisterAuthority($ruleAuthority);
        } elseif ($policyGroup === 'authority') {
            $holonLabel = $ruleHolon instanceof Holon ? $ruleHolon->getFullDisplayName() : '-';
            $nodeKey = $policyRegisterNode('local:' . ($ruleHolon instanceof Holon ? (int)$ruleHolon->getId() : 'unknown'), omoPolicyT('policy.group.local_rules', ['holon' => $holonLabel]));
        } else {
            $nodeKey = $policyRegisterHolon($ruleHolon);
        }

        if ($nodeKey === null) {
            $nodeKey = $policyRegisterNode('unknown', omoPolicyT('policy.group.unknown'));
        }
        $policyGroupNodes[$nodeKey]['rules'][] = $entry;
    }
}
foreach ($policyGroupNodes as $nodeKey => $node) {
    $parentKey = $node['parent'];
    if ($parentKey !== null && isset($policyGroupNodes[$parentKey])) {
        $policyGroupNodes[$parentKey]['children'][] = $nodeKey;
    }
}
$policyRootGroupKeys = [];
foreach ($policyGroupNodes as $nodeKey => $node) {
    if ($node['parent'] === null || !isset($policyGroupNodes[$node['parent']])) {
        $policyRootGroupKeys[] = $nodeKey;
    }
}
$policySortGroupKeys = static function (array $keys) use (&$policyGroupNodes) {
    usort($keys, static function ($left, $right) use (&$policyGroupNodes) {
        return strnatcasecmp($policyGroupNodes[$left]['label'], $policyGroupNodes[$right]['label']);
    });
    return $keys;
};
$policyRootGroupKeys = $policySortGroupKeys($policyRootGroupKeys);
$canCreate = omoPolicyCanCreateLocalRule($context);
$createUrl = '/omo/api/policy/edit.php?oid=' . rawurlencode((string)$organizationId) . '&cid=' . rawurlencode((string)$currentHolon->getId());
$indexUrl = '/omo/api/policy/index.php?oid=' . rawurlencode((string)$organizationId) . '&cid=' . rawurlencode((string)$currentHolon->getId());
?>
<link rel="stylesheet" href="/common/view-filter/view-filter.css?v=20260729-compact-2">
<div class="omo-policy omo-panel-view" id="omo-policy-root" data-policy-oid="<?= (int)$organizationId ?>" data-policy-cid="<?= (int)$currentHolon->getId() ?>" data-policy-index-url="<?= omoApiEscape($indexUrl) ?>" data-policy-scope="<?= omoApiEscape($policyScope) ?>" data-policy-sort="<?= omoApiEscape($policySort) ?>" data-policy-group="<?= omoApiEscape($policyGroup) ?>" data-policy-create-url="<?= omoApiEscape($createUrl) ?>" data-policy-load-error="<?= omoApiEscape(omoPolicyT('policy.error.load')) ?>" data-policy-save-error="<?= omoApiEscape(omoPolicyT('policy.error.save')) ?>" data-policy-delete-confirm="<?= omoApiEscape(omoPolicyT('policy.delete.confirm')) ?>" data-policy-delete-error="<?= omoApiEscape(omoPolicyT('policy.error.delete')) ?>">
    <header class="omo-panel-view__header omo-panel-view__header--stacked">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <span class="omo-panel-view__app-icon omo-policy__app-icon" aria-hidden="true"><img src="images/tools/policy.png" alt=""></span>
                <div class="omo-panel-view__header-copy">
                    <div class="omo-panel-view__title-row generic-title-row generic-title-row--center"><h2 class="omo-panel-view__title"><?= omoApiEscape(omoPolicyT('policy.title')) ?></h2><span class="omo-panel-view__count"><?= count($rules) ?></span></div>
                </div>
            </div>
            <?php if ($canCreate): ?><div class="omo-panel-view__header-actions" data-omo-header-actions><button type="button" class="generic-action-button generic-action-button--main omo-mobile-corner-action" data-policy-new><?= omoApiEscape(omoPolicyT('policy.new')) ?></button></div><?php endif; ?>
        </div>
        <div class="omo-panel-view__header-secondary">
            <div class="omo-context-filter omo-view-filter" data-policy-filter-control role="group" aria-label="<?= omoApiEscape(omoPolicyT('policy.filters.aria')) ?>">
                <div class="omo-context-filter__input omo-view-filter__input">
                    <div class="omo-context-filter__chips omo-view-filter__chips">
                        <button type="button" class="omo-context-filter__chip omo-view-filter__chip" data-policy-filter-toggle aria-expanded="false" aria-controls="omo-policy-filter-panel"><?= omoApiEscape(omoPolicyT('policy.scope.' . $policyScope)) ?></button>
                        <button type="button" class="omo-context-filter__chip omo-view-filter__chip" data-policy-filter-toggle aria-expanded="false" aria-controls="omo-policy-filter-panel"><?= omoApiEscape(omoPolicyT('policy.sort.' . $policySort)) ?></button>
                        <button type="button" class="omo-context-filter__chip omo-view-filter__chip" data-policy-filter-toggle aria-expanded="false" aria-controls="omo-policy-filter-panel"><?= omoApiEscape(omoPolicyT('policy.group.' . $policyGroup)) ?></button>
                    </div>
                    <label class="omo-context-filter__search omo-view-filter__search">
                        <input type="search" class="generic-form-control" data-policy-quick-search placeholder="<?= omoApiEscape(omoPolicyT('policy.search.placeholder')) ?>" aria-label="<?= omoApiEscape(omoPolicyT('policy.search.aria')) ?>" autocomplete="off">
                    </label>
                </div>
                <section id="omo-policy-filter-panel" class="omo-context-filter__panel omo-view-filter__panel generic-soft-panel generic-soft-panel--stack is-filter-hidden" data-policy-filter-panel>
                    <div class="omo-context-filter__group omo-view-filter__group">
                        <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoPolicyT('policy.scope')) ?></span>
                        <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoPolicyT('policy.scope')) ?>">
                            <?php foreach ($availableScopes as $scopeKey): ?>
                                <button type="button" class="omo-segmented__button<?= $policyScope === $scopeKey ? ' is-active' : '' ?>" data-policy-scope-choice="<?= omoApiEscape($scopeKey) ?>" aria-pressed="<?= $policyScope === $scopeKey ? 'true' : 'false' ?>"><?= omoApiEscape(omoPolicyT('policy.scope.' . $scopeKey)) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="omo-context-filter__group omo-view-filter__group">
                        <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoPolicyT('policy.sort')) ?></span>
                        <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoPolicyT('policy.sort')) ?>">
                            <?php foreach (['alpha', 'created', 'updated'] as $sortKey): ?>
                                <button type="button" class="omo-segmented__button<?= $policySort === $sortKey ? ' is-active' : '' ?>" data-policy-sort-choice="<?= omoApiEscape($sortKey) ?>" aria-pressed="<?= $policySort === $sortKey ? 'true' : 'false' ?>"><?= omoApiEscape(omoPolicyT('policy.sort.' . $sortKey)) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="omo-context-filter__group omo-view-filter__group">
                        <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoPolicyT('policy.group')) ?></span>
                        <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoPolicyT('policy.group')) ?>">
                            <?php foreach (['holon', 'authority', 'none'] as $groupKey): ?>
                                <button type="button" class="omo-segmented__button<?= $policyGroup === $groupKey ? ' is-active' : '' ?>" data-policy-group-choice="<?= omoApiEscape($groupKey) ?>" aria-pressed="<?= $policyGroup === $groupKey ? 'true' : 'false' ?>"><?= omoApiEscape(omoPolicyT('policy.group.' . $groupKey)) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="omo-context-filter__actions omo-view-filter__actions">
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-policy-filter-apply><?= omoApiEscape(omoPolicyT('policy.filters.apply')) ?></button>
                        <button type="button" class="generic-action-button generic-action-button--main" data-policy-filter-save><?= omoApiEscape(omoPolicyT('policy.filters.save_view')) ?></button>
                    </div>
                </section>
            </div>
        </div>
    </header>
    <div class="omo-panel-view__body"><div class="omo-panel-view__body_content omo-policy__body">
        <?php if (count($rules) === 0): ?>
            <section class="generic-hero-panel accent generic-empty-hero">
                <h3 class="generic-empty-hero__title"><?= omoApiEscape(omoPolicyT('policy.empty.title')) ?></h3>
                <p class="generic-empty-hero__text"><?= omoApiEscape(omoPolicyT('policy.empty.' . $policyScope)) ?></p>
            </section>
        <?php else: ?>
            <?php
            $policyRenderRule = static function (array $entry) use ($organizationId) {
                $rule = $entry['rule'];
                $createdBy = $rule->getCreatedByUser();
                $updatedBy = $rule->getUpdatedByUser();
                $ruleHolon = $entry['holon'];
                $ruleAuthority = $entry['authority'];
                $createdDate = $rule->get('created_at') instanceof DateTimeInterface ? $rule->get('created_at')->format('d.m.Y H:i') : '';
                $updatedDate = $rule->get('updated_at') instanceof DateTimeInterface ? $rule->get('updated_at')->format('d.m.Y H:i') : '';
                $createdByLabel = $createdBy ? $createdBy->getScopedDisplayName($organizationId) : '-';
                $updatedByLabel = $updatedBy ? $updatedBy->getScopedDisplayName($organizationId) : '-';
                $holonLabel = $ruleHolon ? $ruleHolon->getFullDisplayName() : '-';
                $authorityLabel = $ruleAuthority ? trim((string)$ruleAuthority->get('label')) : '';
                $canEditRule = $rule->canEdit();
                $ruleEditUrl = $canEditRule && $ruleHolon instanceof Holon
                    ? '/omo/api/policy/edit.php?oid=' . rawurlencode((string)$organizationId) . '&cid=' . rawurlencode((string)$ruleHolon->getId()) . '&rule_id=' . rawurlencode((string)$rule->getId())
                    : '';
                $isExpired = !$rule->isValidAt();
                $needsReview = !$isExpired && $rule->isReviewDue();
                $statusClass = $isExpired
                    ? ' omo-policy__rule-card--expired'
                    : ($needsReview ? ' omo-policy__rule-card--review' : '');
                ?>
                <article class="omo-policy__rule-card omo-card generic-section--stack<?= $statusClass ?>" data-policy-rule-card data-policy-rule-search="<?= omoApiEscape(trim(implode(' ', [(string)$rule->get('title'), strip_tags((string)$rule->get('description')), strip_tags((string)$rule->get('intention')), $holonLabel, $authorityLabel]))) ?>">
                    <div class="omo-policy__rule-head">
                        <h3 class="generic-card-title generic-card-title--big omo-policy__rule-title">
                            <?= omoApiEscape((string)$rule->get('title')) ?>
                            <?php if ($isExpired): ?><span class="omo-policy__rule-status omo-policy__rule-status--expired"><?= omoApiEscape(omoPolicyT('policy.status.expired')) ?></span><?php elseif ($needsReview): ?><span class="omo-policy__rule-status omo-policy__rule-status--review"><?= omoApiEscape(omoPolicyT('policy.status.review')) ?></span><?php endif; ?>
                        </h3>
                        <?php if ($canEditRule): ?>
                            <div class="generic-menu omo-policy__rule-menu" data-policy-rule-menu>
                                <button type="button" class="generic-menu-toggle omo-policy__rule-menu-toggle" data-policy-rule-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="<?= omoApiEscape(omoPolicyT('policy.edit')) ?>">...</button>
                                <div class="generic-menu-panel omo-policy__rule-menu-panel" data-policy-rule-menu-panel role="menu" hidden>
                                    <button type="button" class="generic-menu-item" data-policy-rule-edit data-policy-edit-url="<?= omoApiEscape($ruleEditUrl) ?>" role="menuitem"><?= omoApiEscape(omoPolicyT('policy.edit')) ?></button>
                                    <button type="button" class="generic-menu-item generic-menu-item--danger" data-policy-rule-delete data-policy-rule-id="<?= (int)$rule->getId() ?>" role="menuitem"><?= omoApiEscape(omoPolicyT('policy.delete')) ?></button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="omo-policy__rule-statement"><?= (string)$rule->get('description') ?></div>
                    <details class="omo-policy__rule-details generic-section generic-accordion--card">
                        <summary><small><?= omoApiEscape(omoPolicyT('policy.documentation')) ?></small></summary>
                        <div class="omo-policy__rule-details-content">
                            <?php if (trim(strip_tags((string)$rule->get('intention'))) !== ''): ?>
                                <section class="omo-policy__rule-intention">
                                    <h4 class="generic-card-title"><?= omoApiEscape(omoPolicyT('policy.intention')) ?></h4>
                                    <div><?= (string)$rule->get('intention') ?></div>
                                </section>
                            <?php endif; ?>
                            <div class="omo-policy__rule-meta">
                                <small><?= omoApiEscape(omoPolicyT('policy.review', ['date' => $rule->get('review_date') instanceof DateTimeInterface ? $rule->get('review_date')->format('d.m.Y') : ''])) ?></small>
                                <small><?= omoApiEscape(omoPolicyT('policy.expiration', ['date' => $rule->get('expiration_date') instanceof DateTimeInterface ? $rule->get('expiration_date')->format('d.m.Y') : ''])) ?></small>
                                <small><?= omoApiEscape(omoPolicyT('policy.created', ['date' => $createdDate, 'user' => $createdByLabel])) ?></small>
                                <small><?= omoApiEscape(omoPolicyT('policy.updated', ['date' => $updatedDate, 'user' => $updatedByLabel])) ?></small>
                                <small><?= omoApiEscape(omoPolicyT('policy.holon', ['holon' => $holonLabel])) ?></small>
                                <?php if ($authorityLabel !== ''): ?><small><?= omoApiEscape(omoPolicyT('policy.authority', ['authority' => $authorityLabel])) ?></small><?php endif; ?>
                            </div>
                        </div>
                    </details>
                </article>
                <?php
            };
            $policyRenderGroups = null;
            $policyRenderGroups = static function (array $keys, $prefix = '', $showRootTitles = true) use (&$policyRenderGroups, &$policyGroupNodes, $policySortGroupKeys, $policyRenderRule) {
                foreach ($policySortGroupKeys($keys) as $index => $nodeKey) {
                    $node = $policyGroupNodes[$nodeKey];
                    $showTitle = $showRootTitles || $prefix !== '';
                    $number = $prefix === '' ? (string)($index + 1) : $prefix . '.' . ($index + 1);
                    $nextPrefix = $showTitle ? $number : '';
                    ?>
                    <section class="omo-policy__rule-group generic-file-list__group" data-policy-rule-group>
                        <?php if ($showTitle): ?><h3 class="generic-file-list__group-title omo-policy__rule-group-title"><?= omoApiEscape($number . '. ' . $node['label']) ?></h3><?php endif; ?>
                        <div class="omo-policy__rule-group-content">
                            <?php foreach ($node['rules'] as $entry): $policyRenderRule($entry); endforeach; ?>
                            <?php if (!empty($node['children'])): $policyRenderGroups($node['children'], $nextPrefix, true); endif; ?>
                        </div>
                    </section>
                    <?php
                }
            };
            ?>
            <div class="omo-policy__groups generic-file-list generic-file-list--structured">
                <?php $policyRenderGroups($policyRootGroupKeys, '', count($policyRootGroupKeys) > 1); ?>
            </div>
            <div class="omo-empty-state is-filter-hidden" data-policy-search-empty><?= omoApiEscape(omoPolicyT('policy.search.empty')) ?></div>
        <?php endif; ?>
    </div></div>
    <div class="omo-overlay-drawer" data-policy-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-policy-close></div>
        <div class="omo-overlay-drawer__panel"><div class="omo-overlay-drawer__header generic-drawer-header generic-drawer-header--sticky"><div class="generic-drawer-header__copy"><h3 class="omo-overlay-drawer__title"><?= omoApiEscape(omoPolicyT('policy.drawer.title')) ?></h3><p class="omo-overlay-drawer__description"><?= omoApiEscape(omoPolicyT('policy.drawer.description')) ?></p></div><div class="generic-drawer-header__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-policy-close><?= omoApiEscape(omoPolicyT('policy.close')) ?></button></div></div><div class="omo-overlay-drawer__body" data-policy-drawer-body></div></div>
    </div>
</div>
<script>
(function () {
    var root = document.getElementById('omo-policy-root');
    if (!root || root.dataset.ready === '1') return;
    root.dataset.ready = '1';
    var drawer = root.querySelector('[data-policy-drawer]');
    var body = root.querySelector('[data-policy-drawer-body]');
    var drawerTitle = root.querySelector('.omo-overlay-drawer__title');
    var drawerDescription = root.querySelector('.omo-overlay-drawer__description');
    var filterControl = root.querySelector('[data-policy-filter-control]');
    var filterPanel = root.querySelector('[data-policy-filter-panel]');
    var quickSearchInput = root.querySelector('[data-policy-quick-search]');
    var quickSearchEmpty = root.querySelector('[data-policy-search-empty]');
    var pendingView = null;
    var filterPanelIsOpen = false;
    var savedViewsStorageKey = 'omo.policy.saved-views.v1';
    var temporaryViewsStorageKey = 'omo.policy.session-views.v1';
    var currentQuickSearch = '';
    var refreshRoot = function (url) {
        if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
            window.location.href = url;
            return;
        }
        window.omoReplaceFetchedPanelRoot({
            rootSelector: '#omo-policy-root',
            currentRoot: root,
            url: url
        });
    };
    var policyViewUrl = function (view) {
        var url = root.dataset.policyIndexUrl;
        if (view.scope !== 'contextual') url += '&policy_scope=' + encodeURIComponent(view.scope);
        if (view.sort !== 'alpha') url += '&policy_sort=' + encodeURIComponent(view.sort);
        if (view.group !== 'holon') url += '&policy_group=' + encodeURIComponent(view.group);
        return url;
    };
    var policyPreferenceKey = function () {
        return String(root.dataset.policyOid || '0') + ':' + String(root.dataset.policyCid || '0');
    };
    var normalizeView = function (view) {
        view = view && typeof view === 'object' ? view : {};
        var scope = view.scope === 'children' || view.scope === 'descendants' ? view.scope : 'contextual';
        if (!filterPanel || !filterPanel.querySelector('[data-policy-scope-choice="' + scope + '"]')) scope = root.dataset.policyScope || 'contextual';
        var sort = view.sort === 'created' || view.sort === 'updated' ? view.sort : 'alpha';
        var group = view.group === 'authority' ? 'authority' : (view.group === 'none' ? 'none' : 'holon');
        return {scope: scope, sort: sort, group: group};
    };
    var currentView = function () {
        return normalizeView({
            scope: root.dataset.policyScope || 'contextual',
            sort: root.dataset.policySort || 'alpha',
            group: root.dataset.policyGroup || 'holon'
        });
    };
    var viewsMatch = function (left, right) {
        return left.scope === right.scope && left.sort === right.sort && left.group === right.group;
    };
    var readPreference = function (storage) {
        try {
            var value = storage.getItem(storage === window.localStorage ? savedViewsStorageKey : temporaryViewsStorageKey);
            var preferences = value ? JSON.parse(value) : null;
            return preferences && typeof preferences === 'object' ? preferences[policyPreferenceKey()] : null;
        } catch (error) {
            return null;
        }
    };
    var writePreference = function (storage, view) {
        try {
            var key = storage === window.localStorage ? savedViewsStorageKey : temporaryViewsStorageKey;
            var value = storage.getItem(key);
            var preferences = value ? JSON.parse(value) : {};
            if (!preferences || typeof preferences !== 'object') preferences = {};
            preferences[policyPreferenceKey()] = normalizeView(view);
            storage.setItem(key, JSON.stringify(preferences));
        } catch (error) {
            // Storage can be unavailable in private or restricted browsing contexts.
        }
    };
    var clearTemporaryPreference = function () {
        try {
            var value = window.sessionStorage.getItem(temporaryViewsStorageKey);
            var preferences = value ? JSON.parse(value) : null;
            if (!preferences || typeof preferences !== 'object') return;
            delete preferences[policyPreferenceKey()];
            window.sessionStorage.setItem(temporaryViewsStorageKey, JSON.stringify(preferences));
        } catch (error) {
            // Storage can be unavailable in private or restricted browsing contexts.
        }
    };
    var syncPolicyGroupStickyOffsets = function () {
        var titles = root.querySelectorAll('.omo-policy__rule-group-title');
        titles.forEach(function (title) {
            var offset = 0;
            var depth = 0;
            var group = title.closest('[data-policy-rule-group]');
            var parent = group ? group.parentElement : null;
            while (parent) {
                var ancestorGroup = parent.closest('[data-policy-rule-group]');
                if (!ancestorGroup) break;
                var ancestorTitle = ancestorGroup.querySelector(':scope > .omo-policy__rule-group-title');
                if (ancestorTitle && !ancestorGroup.classList.contains('is-filter-hidden')) {
                    offset += Math.ceil(ancestorTitle.getBoundingClientRect().height);
                    depth++;
                }
                parent = ancestorGroup.parentElement;
            }
            title.style.setProperty('--omo-policy-group-top', offset + 'px');
            title.style.setProperty('--omo-policy-group-z', String(40 - depth));
        });
    };
    var syncViewChoices = function () {
        if (!filterPanel || pendingView === null) return;
        pendingView = normalizeView(pendingView);
        filterPanel.querySelectorAll('[data-policy-scope-choice]').forEach(function (button) {
            var active = button.getAttribute('data-policy-scope-choice') === pendingView.scope;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        filterPanel.querySelectorAll('[data-policy-sort-choice]').forEach(function (button) {
            var active = button.getAttribute('data-policy-sort-choice') === pendingView.sort;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        filterPanel.querySelectorAll('[data-policy-group-choice]').forEach(function (button) {
            var active = button.getAttribute('data-policy-group-choice') === pendingView.group;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };
    var removeFilterOutsideHandler = function () {
        document.removeEventListener('pointerdown', handleFilterOutsidePointerDown, true);
    };
    var closeFilterPanel = function (applyChanges, saveView) {
        if (!filterPanelIsOpen) return;
        filterPanelIsOpen = false;
        filterPanel.classList.add('is-filter-hidden');
        root.querySelectorAll('[data-policy-filter-toggle]').forEach(function (button) { button.setAttribute('aria-expanded', 'false'); });
        removeFilterOutsideHandler();
        if (!applyChanges || pendingView === null) {
            pendingView = null;
            return;
        }
        var nextView = normalizeView(pendingView);
        pendingView = null;
        if (saveView) {
            writePreference(window.localStorage, nextView);
            clearTemporaryPreference();
        } else {
            writePreference(window.sessionStorage, nextView);
        }
        if (!viewsMatch(nextView, currentView())) refreshRoot(policyViewUrl(nextView));
    };
    var handleFilterOutsidePointerDown = function (event) {
        if (!filterControl || filterControl.contains(event.target)) return;
        closeFilterPanel(true, false);
    };
    var openFilterPanel = function () {
        if (!filterControl || !filterPanel || filterPanelIsOpen) return;
        pendingView = currentView();
        syncViewChoices();
        filterPanel.classList.remove('is-filter-hidden');
        filterPanelIsOpen = true;
        root.querySelectorAll('[data-policy-filter-toggle]').forEach(function (button) { button.setAttribute('aria-expanded', 'true'); });
        document.addEventListener('pointerdown', handleFilterOutsidePointerDown, true);
    };
    var normalizeQuickSearch = function (value) {
        return String(value || '').toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    };
    var applyQuickSearch = function () {
        var query = normalizeQuickSearch(currentQuickSearch);
        var visibleCount = 0;
        root.querySelectorAll('[data-policy-rule-card]').forEach(function (ruleCard) {
            var searchableText = ruleCard.getAttribute('data-policy-rule-search') || ruleCard.textContent || '';
            var matches = query === '' || normalizeQuickSearch(searchableText).indexOf(query) !== -1;
            ruleCard.classList.toggle('is-filter-hidden', !matches);
            if (matches) visibleCount++;
        });
        root.querySelectorAll('[data-policy-rule-group]').forEach(function (group) {
            group.classList.toggle('is-filter-hidden', !group.querySelector('[data-policy-rule-card]:not(.is-filter-hidden)'));
        });
        syncPolicyGroupStickyOffsets();
        if (quickSearchEmpty) quickSearchEmpty.classList.toggle('is-filter-hidden', query === '' || visibleCount > 0);
    };
    root.querySelectorAll('[data-policy-filter-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (filterPanelIsOpen) closeFilterPanel(true, false); else openFilterPanel();
        });
    });
    if (filterPanel) {
        filterPanel.addEventListener('click', function (event) {
            if (event.target.closest('[data-policy-filter-apply]')) {
                closeFilterPanel(true, false);
                return;
            }
            if (event.target.closest('[data-policy-filter-save]')) {
                closeFilterPanel(true, true);
                return;
            }
            var scopeButton = event.target.closest('[data-policy-scope-choice]');
            if (scopeButton) {
                pendingView.scope = scopeButton.getAttribute('data-policy-scope-choice') || 'contextual';
                syncViewChoices();
                return;
            }
            var sortButton = event.target.closest('[data-policy-sort-choice]');
            if (sortButton) {
                pendingView.sort = sortButton.getAttribute('data-policy-sort-choice') || 'alpha';
                syncViewChoices();
                return;
            }
            var groupButton = event.target.closest('[data-policy-group-choice]');
            if (groupButton) {
                pendingView.group = groupButton.getAttribute('data-policy-group-choice') || 'holon';
                syncViewChoices();
            }
        });
        var preferredView = readPreference(window.sessionStorage) || readPreference(window.localStorage);
        if (preferredView && !viewsMatch(normalizeView(preferredView), currentView())) {
            refreshRoot(policyViewUrl(normalizeView(preferredView)));
        }
    }
    if (quickSearchInput) {
        quickSearchInput.addEventListener('input', function () {
            currentQuickSearch = quickSearchInput.value || '';
            applyQuickSearch();
        });
        quickSearchInput.addEventListener('search', function () {
            currentQuickSearch = quickSearchInput.value || '';
            applyQuickSearch();
        });
    }
    window.requestAnimationFrame(syncPolicyGroupStickyOffsets);
    window.addEventListener('resize', syncPolicyGroupStickyOffsets);
    var closeRuleMenus = function (exceptMenu) {
        root.querySelectorAll('[data-policy-rule-menu]').forEach(function (menu) {
            if (menu === exceptMenu) return;
            var panel = menu.querySelector('[data-policy-rule-menu-panel]');
            var toggle = menu.querySelector('[data-policy-rule-menu-toggle]');
            if (panel) panel.hidden = true;
            menu.classList.remove('is-open');
            var card = menu.closest('[data-policy-rule-card]');
            if (card) card.classList.remove('is-menu-open');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
    };
    root.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-policy-rule-menu-toggle]');
        if (toggle && root.contains(toggle)) {
            var menu = toggle.closest('[data-policy-rule-menu]');
            var panel = menu ? menu.querySelector('[data-policy-rule-menu-panel]') : null;
            var isOpen = !!panel && !panel.hidden;
            event.preventDefault();
            event.stopPropagation();
            closeRuleMenus(menu);
            if (panel) panel.hidden = isOpen;
            if (menu) menu.classList.toggle('is-open', !isOpen);
            var card = menu ? menu.closest('[data-policy-rule-card]') : null;
            if (card) card.classList.toggle('is-menu-open', !isOpen);
            toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            return;
        }

        var editButton = event.target.closest('[data-policy-rule-edit]');
        if (editButton && root.contains(editButton)) {
            event.preventDefault();
            event.stopPropagation();
            closeRuleMenus();
            openPolicyDrawer(editButton.getAttribute('data-policy-edit-url') || '');
            return;
        }

        var deleteButton = event.target.closest('[data-policy-rule-delete]');
        if (deleteButton && root.contains(deleteButton)) {
            event.preventDefault();
            event.stopPropagation();
            closeRuleMenus();

            var ruleId = deleteButton.getAttribute('data-policy-rule-id') || '';
            var confirmation = root.dataset.policyDeleteConfirm || '';
            if (!ruleId || (confirmation !== '' && !window.confirm(confirmation))) return;

            deleteButton.disabled = true;
            var payload = new FormData();
            payload.append('oid', root.dataset.policyOid || '0');
            payload.append('cid', root.dataset.policyCid || '0');
            payload.append('rule_id', ruleId);
            payload.append('action', 'delete');

            fetch('/omo/api/policy/action.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: payload
            }).then(function (response) {
                return response.json().then(function (data) {
                    return {ok: response.ok, data: data};
                });
            }).then(function (result) {
                if (!result.ok || !result.data || !result.data.success) {
                    throw new Error(result.data && result.data.message ? result.data.message : (root.dataset.policyDeleteError || ''));
                }
                window.omoPolicyAfterSave();
            }).catch(function (error) {
                deleteButton.disabled = false;
                window.alert(error && error.message ? error.message : (root.dataset.policyDeleteError || ''));
            });
            return;
        }

        if (!event.target.closest('[data-policy-rule-menu]')) closeRuleMenus();
    });
    var close = function () { drawer.classList.remove('is-open'); window.setTimeout(function () { if (!drawer.classList.contains('is-open')) { drawer.hidden = true; body.innerHTML = ''; } }, 220); };
    var mountPolicyHtmlFields = function (scope) {
        if (!scope || !window.omoSimpleHtmlField || typeof window.omoSimpleHtmlField.mount !== 'function') return;
        scope.querySelectorAll('[data-policy-html-field]').forEach(function (host) {
            var input = host.parentElement ? host.parentElement.querySelector('[data-policy-html-input]') : null;
            if (!input) return;
            window.omoSimpleHtmlField.mount(host, {
                value: input.value || '',
                placeholder: 'Saisissez le contenu de la regle...',
                simpleOnly: true,
                onChange: function (value) { input.value = String(value || ''); }
            });
        });
    };
    var openPolicyDrawer = function (url) {
        if (!url) return;
        closeRuleMenus();
        drawer.hidden = false;
        window.requestAnimationFrame(function () { drawer.classList.add('is-open'); });
        body.textContent = '...';
        fetch(url, {credentials: 'same-origin'}).then(function (response) {
            if (!response.ok) throw new Error('load_failed');
            return response.text();
        }).then(function (html) {
            body.innerHTML = html;
            var form = body.querySelector('[data-policy-form]');
            if (form) {
                if (drawerTitle) drawerTitle.textContent = form.getAttribute('data-policy-form-title') || omoPolicyDefaultDrawerTitle;
                if (drawerDescription) drawerDescription.textContent = form.getAttribute('data-policy-form-description') || omoPolicyDefaultDrawerDescription;
                mountPolicyHtmlFields(form);
            }
            if (typeof window.initGenericComponents === 'function') window.initGenericComponents(body);
        }).catch(function () { body.textContent = root.dataset.policyLoadError; });
    };
    var omoPolicyDefaultDrawerTitle = drawerTitle ? drawerTitle.textContent : '';
    var omoPolicyDefaultDrawerDescription = drawerDescription ? drawerDescription.textContent : '';
    root.querySelectorAll('[data-policy-close]').forEach(function (button) { button.addEventListener('click', close); });
    var create = root.querySelector('[data-policy-new]');
    if (create) create.addEventListener('click', function () { openPolicyDrawer(root.dataset.policyCreateUrl || ''); });
    body.addEventListener('submit', function (event) { var form = event.target.closest('[data-policy-form]'); if (!form) return; event.preventDefault(); if (!form.reportValidity()) return; var feedback = form.querySelector('[data-policy-feedback]'); fetch(form.action, {method: 'POST', credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}, body: new FormData(form)}).then(function (response) { return response.json().then(function (payload) { return {ok: response.ok, payload: payload}; }); }).then(function (result) { if (!result.ok || !result.payload.success) throw new Error(result.payload.message || root.dataset.policySaveError); feedback.hidden = false; feedback.textContent = result.payload.message; window.omoPolicyAfterSave(); }).catch(function (error) { feedback.hidden = false; feedback.textContent = error.message || root.dataset.policySaveError; }); });
    window.omoPolicyAfterSave = function () { window.location.reload(); };
})();
</script>
