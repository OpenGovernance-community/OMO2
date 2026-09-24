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
$organization = $context['organization'];
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
$applicationViewPreferences = omoApplicationViewPreferencesGetContext('policy', $organization, $currentHolon, $currentUserId);
$availableScopes = omoApiGetAvailableContextScopes($currentHolon instanceof \dbObject\Holon, $currentHolon, $rootHolon);
$policyScope = omoApiNormalizeContextScope(
    omoApplicationViewPreferencesGetInitialValue($applicationViewPreferences, 'policy_scope', 'scope', 'contextual'),
    $availableScopes
);
$scopeHolonIds = $policyScope === 'children'
    ? omoApiGetDirectChildScopeHolonIds($currentHolon)
    : ($policyScope === 'descendants' ? omoApiGetDescendantHolonIds($currentHolon) : ($currentHolon instanceof Holon ? [(int)$currentHolon->getId()] : []));
$rules = new ArrayRule();
$includeOrganizationRules = !($currentHolon instanceof Holon)
    || ($rootHolon instanceof Holon && (int)$currentHolon->getId() === (int)$rootHolon->getId());
$rules->loadForPolicyContexts($organizationId, $scopeHolonIds, $includeOrganizationRules);
$policySort = omoPolicyNormalizeSort(
    omoApplicationViewPreferencesGetInitialValue($applicationViewPreferences, 'policy_sort', 'sort', 'alpha')
);
$policyGroup = omoPolicyNormalizeGroup(
    omoApplicationViewPreferencesGetInitialValue($applicationViewPreferences, 'policy_group', 'group', 'holon')
);
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
            $holonLabel = $ruleHolon instanceof Holon ? $ruleHolon->getFullDisplayName() : (string)$organization->get('name');
            $nodeKey = $policyRegisterNode('local:' . ($ruleHolon instanceof Holon ? (int)$ruleHolon->getId() : 'organization'), omoPolicyT('policy.group.local_rules', ['holon' => $holonLabel]));
        } else {
            $nodeKey = $ruleHolon instanceof Holon
                ? $policyRegisterHolon($ruleHolon)
                : $policyRegisterNode('organization', (string)$organization->get('name'));
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
$currentContextHolonId = $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0;
$createUrl = '/omo/api/policy/edit.php?oid=' . rawurlencode((string)$organizationId) . '&cid=' . $currentContextHolonId;
$indexUrl = '/omo/api/policy/index.php?oid=' . rawurlencode((string)$organizationId) . '&cid=' . $currentContextHolonId;
?>
<link rel="stylesheet" href="/common/view-filter/view-filter.css?v=20260902-save-menu">
<div class="omo-policy omo-panel-view" id="omo-policy-root" data-policy-oid="<?= (int)$organizationId ?>" data-policy-cid="<?= $currentContextHolonId ?>" data-omo-app-view-preferences="<?= omoApiEscape(json_encode($applicationViewPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>" data-policy-index-url="<?= omoApiEscape($indexUrl) ?>" data-policy-scope="<?= omoApiEscape($policyScope) ?>" data-policy-sort="<?= omoApiEscape($policySort) ?>" data-policy-group="<?= omoApiEscape($policyGroup) ?>" data-policy-create-url="<?= omoApiEscape($createUrl) ?>" data-policy-load-error="<?= omoApiEscape(omoPolicyT('policy.error.load')) ?>" data-policy-save-error="<?= omoApiEscape(omoPolicyT('policy.error.save')) ?>" data-policy-delete-confirm="<?= omoApiEscape(omoPolicyT('policy.delete.confirm')) ?>" data-policy-delete-error="<?= omoApiEscape(omoPolicyT('policy.error.delete')) ?>">
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
                        <button type="button" class="generic-action-button generic-action-button--main" data-policy-filter-apply><?= omoApiEscape(omoPolicyT('policy.filters.apply')) ?></button>
                        <?php if (!empty($applicationViewPreferences['canSavePersonal']) || !empty($applicationViewPreferences['canSaveTemporary'])): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary"<?= !empty($applicationViewPreferences['canSavePersonal']) ? ' data-policy-filter-save' : '' ?> data-omo-app-view-save-scope="<?= omoApiEscape($applicationViewPreferences['primarySaveScope']) ?>"><?= omoApiEscape(omoPolicyT('policy.filters.save_view')) ?></button>
                        <?php elseif (($applicationViewPreferences['primarySaveScope'] ?? '') !== ''): ?>
                            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-app-view-save-scope="<?= omoApiEscape($applicationViewPreferences['primarySaveScope']) ?>"><?= omoApiEscape($applicationViewPreferences['primarySaveLabel'] ?? '') ?></button>
                        <?php endif; ?>
                        <?= omoApplicationViewPreferencesRenderMenu($applicationViewPreferences) ?>
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
            $policyRenderRule = static function (array $entry) use ($organizationId, $organization) {
                $rule = $entry['rule'];
                $createdBy = $rule->getCreatedByUser();
                $updatedBy = $rule->getUpdatedByUser();
                $ruleHolon = $entry['holon'];
                $ruleAuthority = $entry['authority'];
                $createdDate = $rule->get('created_at') instanceof DateTimeInterface ? $rule->get('created_at')->format('d.m.Y H:i') : '';
                $updatedDate = $rule->get('updated_at') instanceof DateTimeInterface ? $rule->get('updated_at')->format('d.m.Y H:i') : '';
                $createdByLabel = $createdBy ? $createdBy->getScopedDisplayName($organizationId) : '-';
                $updatedByLabel = $updatedBy ? $updatedBy->getScopedDisplayName($organizationId) : '-';
                $holonLabel = $ruleHolon ? $ruleHolon->getFullDisplayName() : trim((string)$organization->get('name'));
                $authorityLabel = $ruleAuthority ? trim((string)$ruleAuthority->get('label')) : '';
                $canEditRule = $rule->canEdit();
                $canDeleteRule = $rule->canDelete();
                $ruleEditUrl = $canEditRule
                    ? '/omo/api/policy/edit.php?oid=' . rawurlencode((string)$organizationId) . '&cid=' . ($ruleHolon instanceof Holon ? (int)$ruleHolon->getId() : 0) . '&rule_id=' . rawurlencode((string)$rule->getId())
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
                        <?php if ($canEditRule || $canDeleteRule): ?>
                            <div class="generic-menu omo-policy__rule-menu" data-policy-rule-menu>
                                <button type="button" class="generic-menu-toggle omo-policy__rule-menu-toggle" data-policy-rule-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="<?= omoApiEscape(omoPolicyT('policy.edit')) ?>">...</button>
                                <div class="generic-menu-panel omo-policy__rule-menu-panel" data-policy-rule-menu-panel role="menu" hidden>
                                    <?php if ($canEditRule): ?><button type="button" class="generic-menu-item" data-policy-rule-edit data-policy-edit-url="<?= omoApiEscape($ruleEditUrl) ?>" role="menuitem"><?= omoApiEscape(omoPolicyT('policy.edit')) ?></button><?php endif; ?>
                                    <?php if ($canDeleteRule): ?><button type="button" class="generic-menu-item generic-menu-item--danger" data-policy-rule-delete data-policy-rule-id="<?= (int)$rule->getId() ?>" role="menuitem"><?= omoApiEscape(omoPolicyT('policy.delete')) ?></button><?php endif; ?>
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
        <div class="omo-overlay-drawer__panel"><div class="omo-overlay-drawer__header generic-drawer-header generic-drawer-header--sticky"><div class="generic-drawer-header__copy"><h3 class="omo-overlay-drawer__title"><?= omoApiEscape(omoPolicyT('policy.drawer.title')) ?></h3><p class="omo-overlay-drawer__description"><?= omoApiEscape(omoPolicyT($currentHolon instanceof Holon ? 'policy.drawer.description_local' : 'policy.drawer.description_organization')) ?></p></div><div class="generic-drawer-header__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-policy-close><?= omoApiEscape(omoPolicyT('policy.close')) ?></button></div></div><div class="omo-overlay-drawer__body" data-policy-drawer-body></div></div>
    </div>
</div>
<script src="/omo/assets/js/application-view-preferences.js?v=20260917-filter-hierarchy"></script>
<script src="<?= commonAssetUrl('/omo/api/policy/index.js') ?>"></script>
