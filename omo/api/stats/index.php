<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayStatIndicator;
use dbObject\Holon;
use dbObject\StatIndicator;
use dbObject\StatIndicatorReferencePoint;
use dbObject\StatIndicatorValue;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$openIndicatorId = isset($_GET['open_indicator_id']) && is_numeric($_GET['open_indicator_id']) ? (int)$_GET['open_indicator_id'] : 0;
$context = omoStatsResolveContext($organizationId, $currentHolonId);

if (empty($context['status'])) {
    http_response_code(403);
    ?>
    <div class="omo-stats omo-panel-view">
        <div class="omo-panel-view__body"><div class="omo-panel-view__body_content"><div class="omo-empty-state"><?= omoApiEscape((string)($context['message'] ?? omoStatsT('stats.error.context'))) ?></div></div></div>
    </div>
    <?php
    exit;
}

$organization = $context['organization'];
$rootHolon = $context['rootHolon'];
$currentHolon = $context['currentHolon'];
$canToggleScope = $currentHolon instanceof Holon;
$availableScopes = omoApiGetAvailableContextScopes($canToggleScope, $currentHolon, $rootHolon);
$statsScope = omoApiNormalizeContextScope($_GET['stats_scope'] ?? 'contextual', $availableScopes);
$scopeActiveIndex = omoApiResolveContextScopeIndex($statsScope, $availableScopes);
$scopeLabels = [
    'contextual' => omoStatsT('stats.scope.contextual'),
    'descendants' => omoStatsT('stats.scope.descendants'),
    'global' => omoStatsT('stats.scope.global'),
];
$descendantHolonIds = $statsScope === 'descendants' && $currentHolon instanceof Holon
    ? omoApiGetDescendantHolonIds($currentHolon)
    : [];
$indicators = new ArrayStatIndicator();
$indicators->loadForContext(
    $organizationId,
    $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0,
    $statsScope,
    $descendantHolonIds
);
$indicatorItems = omoStatsCollectionItems($indicators, StatIndicator::class);
$canCreate = omoStatsCanManageContext($context);
$emptyKey = $statsScope === 'global'
    ? 'stats.empty.global'
    : ($statsScope === 'descendants' ? 'stats.empty.descendants' : 'stats.empty.contextual');

$currentUrl = '/omo/api/stats/index.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $currentUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
if ($statsScope !== 'contextual') {
    $currentUrl .= '&stats_scope=' . rawurlencode($statsScope);
}
$createUrl = '/omo/api/stats/edit.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $createUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}
$detailBaseUrl = '/omo/api/stats/detail.php?oid=' . rawurlencode((string)$organizationId);
if ($currentHolonId > 0) {
    $detailBaseUrl .= '&cid=' . rawurlencode((string)$currentHolonId);
}

$indicatorViewData = [];
foreach ($indicatorItems as $indicator) {
    $values = omoStatsCollectionItems($indicator->getMeasurements(), StatIndicatorValue::class);
    $referencePoints = omoStatsCollectionItems($indicator->getReferencePoints(), StatIndicatorReferencePoint::class);
    $latestValue = count($values) > 0 ? $values[count($values) - 1] : null;
    $indicatorViewData[] = [
        'indicator' => $indicator,
        'values' => $values,
        'referencePoints' => $referencePoints,
        'latestValue' => $latestValue,
        'contextLabel' => omoStatsContextLabel($indicator),
    ];
}
?>
<link rel="stylesheet" href="/omo/api/stats/stats.css?v=20260715-2">
<div
    class="omo-stats omo-panel-view"
    id="omo-stats-root"
    data-omo-stats-oid="<?= (int)$organizationId ?>"
    data-omo-stats-cid="<?= $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0 ?>"
    data-omo-stats-route-cid="<?= (int)$currentHolonId ?>"
    data-omo-stats-root-hid="<?= $rootHolon instanceof Holon ? (int)$rootHolon->getId() : 0 ?>"
    data-omo-stats-scope="<?= omoApiEscape($statsScope) ?>"
    data-omo-stats-current-url="<?= omoApiEscape($currentUrl) ?>"
    data-omo-stats-create-url="<?= omoApiEscape($createUrl) ?>"
    data-omo-stats-detail-url="<?= omoApiEscape($detailBaseUrl) ?>"
    data-omo-stats-open-indicator-id="<?= (int)$openIndicatorId ?>"
>
    <header class="omo-stats__header omo-panel-view__header omo-panel-view__header--stacked">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <span class="omo-panel-view__app-icon omo-stats__app-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M4 19V5M4 19H21M7 15l4-4 3 2 5-7"/><circle cx="7" cy="15" r="1"/><circle cx="11" cy="11" r="1"/><circle cx="14" cy="13" r="1"/><circle cx="19" cy="6" r="1"/></svg>
                </span>
                <div class="omo-panel-view__header-copy">
                    <div class="omo-stats__title-row">
                        <h2 class="omo-panel-view__title"><?= omoApiEscape(omoStatsT('stats.title')) ?></h2>
                        <span class="omo-panel-view__count"><?= count($indicatorItems) ?></span>
                    </div>
                    <p class="omo-panel-view__description"><?= omoApiEscape(omoStatsT('stats.description')) ?></p>
                </div>
            </div>
            <?php if ($canCreate): ?>
                <button type="button" class="generic-action-button generic-action-button--main omo-mobile-corner-action" data-omo-stats-open-create><?= omoApiEscape(omoStatsT('stats.action.new')) ?></button>
            <?php endif; ?>
        </div>
        <div class="omo-panel-view__header-secondary">
            <div class="omo-scope-toolbar__main">
                <?php if (count($availableScopes) > 1): ?>
                    <div
                        class="omo-scope-toggle"
                        data-omo-scope-switch="<?= omoApiEscape($statsScope) ?>"
                        style="--omo-scope-option-count: <?= count($availableScopes) ?>; --omo-scope-active-index: <?= (int)$scopeActiveIndex ?>;"
                    >
                        <?php foreach ($availableScopes as $scopeIndex => $scopeKey): ?>
                            <button
                                type="button"
                                class="omo-scope-toggle__button<?= $statsScope === $scopeKey ? ' is-active' : '' ?>"
                                data-omo-stats-scope="<?= omoApiEscape($scopeKey) ?>"
                                data-omo-scope-option="<?= omoApiEscape($scopeKey) ?>"
                                data-omo-scope-index="<?= (int)$scopeIndex ?>"
                                aria-pressed="<?= $statsScope === $scopeKey ? 'true' : 'false' ?>"
                            ><span class="omo-scope-toggle__text"><?= omoApiEscape($scopeLabels[$scopeKey] ?? $scopeKey) ?></span></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="omo-segmented" aria-label="<?= omoApiEscape(omoStatsT('stats.title')) ?>">
                <button type="button" class="omo-segmented__button is-active" data-omo-stats-view="cards" aria-pressed="true"><?= omoApiEscape(omoStatsT('stats.view.cards')) ?></button>
                <button type="button" class="omo-segmented__button" data-omo-stats-view="compact" aria-pressed="false"><?= omoApiEscape(omoStatsT('stats.view.compact')) ?></button>
            </div>
        </div>
    </header>

    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content omo-stats__body">
            <section data-omo-stats-view-panel="cards">
                <?php if (count($indicatorViewData) === 0): ?>
                    <div class="omo-empty-state"><?= omoApiEscape(omoStatsT($emptyKey)) ?></div>
                <?php else: ?>
                    <div class="omo-stats-grid">
                        <?php foreach ($indicatorViewData as $item): ?>
                            <?php
                            $indicator = $item['indicator'];
                            $latestValue = $item['latestValue'];
                            $indicatorName = trim((string)$indicator->get('name'));
                            ?>
                            <article
                                class="generic-section omo-stats-card"
                                data-omo-stats-indicator-id="<?= (int)$indicator->getId() ?>"
                                tabindex="0"
                                role="button"
                                aria-label="<?= omoApiEscape(omoStatsT('stats.card.open', ['name' => $indicatorName])) ?>"
                            >
                                <div class="omo-stats-card__header">
                                    <div>
                                        <span class="generic-card-title generic-card-title--eyebrow"><?= omoApiEscape((string)$item['contextLabel']) ?></span>
                                        <h3 class="generic-card-title generic-card-title--big"><?= omoApiEscape($indicatorName) ?></h3>
                                    </div>
                                    <span class="omo-stats-card__value-count"><?= omoApiEscape(omoStatsT('stats.card.value_count', ['count' => count($item['values'])])) ?></span>
                                </div>
                                <div class="omo-stats-card__chart">
                                    <?= omoStatsRenderChart($indicator, $item['values'], $item['referencePoints'], 'card') ?>
                                </div>
                                <div class="omo-stats-card__footer">
                                    <span><?= omoApiEscape(omoStatsT('stats.card.latest')) ?></span>
                                    <?php if ($latestValue instanceof StatIndicatorValue): ?>
                                        <strong><?= omoApiEscape(omoStatsFormatNumber($latestValue->get('value'))) ?></strong>
                                        <time><?= omoApiEscape(omoStatsFormatDateTime($latestValue->get('measured_at'), false)) ?></time>
                                    <?php else: ?>
                                        <strong class="omo-stats-card__empty-value"><?= omoApiEscape(omoStatsT('stats.card.no_value')) ?></strong>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="generic-file-list generic-file-list--structured omo-stats-compact" data-omo-stats-view-panel="compact" hidden>
                <?php if (count($indicatorViewData) === 0): ?>
                    <div class="omo-empty-state"><?= omoApiEscape(omoStatsT($emptyKey)) ?></div>
                <?php else: ?>
                    <div class="generic-file-list__table">
                        <div class="generic-file-list__header">
                            <div class="generic-file-list__header-cell"><?= omoApiEscape(omoStatsT('stats.column.indicator')) ?></div>
                            <div class="generic-file-list__header-cell"><?= omoApiEscape(omoStatsT('stats.column.context')) ?></div>
                            <div class="generic-file-list__header-cell"><?= omoApiEscape(omoStatsT('stats.column.latest')) ?></div>
                            <div class="generic-file-list__header-cell"><?= omoApiEscape(omoStatsT('stats.column.history')) ?></div>
                        </div>
                        <?php foreach ($indicatorViewData as $item): ?>
                            <?php
                            $indicator = $item['indicator'];
                            $latestValue = $item['latestValue'];
                            $indicatorName = trim((string)$indicator->get('name'));
                            ?>
                            <article class="generic-file-list__item-shell">
                                <div
                                    class="generic-file-list__row omo-stats-compact__row"
                                    data-omo-stats-indicator-id="<?= (int)$indicator->getId() ?>"
                                    tabindex="0"
                                    role="button"
                                    aria-label="<?= omoApiEscape(omoStatsT('stats.card.open', ['name' => $indicatorName])) ?>"
                                >
                                    <div class="generic-file-list__cell generic-file-list__cell--name" data-label="<?= omoApiEscape(omoStatsT('stats.column.indicator')) ?>">
                                        <div class="generic-file-list__name-main">
                                            <span class="omo-stats-compact__dot" aria-hidden="true"></span>
                                            <div class="generic-file-list__title-block">
                                                <strong class="generic-file-list__title"><?= omoApiEscape($indicatorName) ?></strong>
                                                <span class="generic-file-list__meta-line"><?= omoApiEscape(omoStatsT('stats.card.value_count', ['count' => count($item['values'])])) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="generic-file-list__cell" data-label="<?= omoApiEscape(omoStatsT('stats.column.context')) ?>"><?= omoApiEscape((string)$item['contextLabel']) ?></div>
                                    <div class="generic-file-list__cell omo-stats-compact__latest" data-label="<?= omoApiEscape(omoStatsT('stats.column.latest')) ?>">
                                        <?php if ($latestValue instanceof StatIndicatorValue): ?>
                                            <strong><?= omoApiEscape(omoStatsFormatNumber($latestValue->get('value'))) ?></strong>
                                            <time><?= omoApiEscape(omoStatsFormatDateTime($latestValue->get('measured_at'), false)) ?></time>
                                        <?php else: ?>
                                            <span>—</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="generic-file-list__cell omo-stats-compact__chart" data-label="<?= omoApiEscape(omoStatsT('stats.column.history')) ?>">
                                        <?= omoStatsRenderChart($indicator, $item['values'], $item['referencePoints'], 'compact') ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <div class="omo-overlay-drawer omo-stats__detail-drawer" data-omo-stats-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-omo-stats-drawer-close></div>
        <div class="omo-overlay-drawer__panel">
            <div class="omo-overlay-drawer__header generic-drawer-header generic-drawer-header--sticky">
                <div class="omo-overlay-drawer__header-copy generic-drawer-header__copy">
                    <h3 class="omo-overlay-drawer__title"><?= omoApiEscape(omoStatsT('stats.drawer.title')) ?></h3>
                    <p class="omo-overlay-drawer__description"><?= omoApiEscape(omoStatsT('stats.drawer.description')) ?></p>
                </div>
                <div class="generic-drawer-header__actions">
                    <button type="button" class="omo-overlay-drawer__close" data-omo-stats-drawer-close><?= omoApiEscape(omoStatsT('stats.action.close')) ?></button>
                </div>
            </div>
            <div class="omo-overlay-drawer__body" data-omo-stats-drawer-body></div>
        </div>
    </div>
</div>
<script>
(function () {
    var root = document.getElementById('omo-stats-root');
    if (!root || root.dataset.omoStatsReady === '1') {
        return;
    }
    root.dataset.omoStatsReady = '1';

    var drawer = root.querySelector('[data-omo-stats-drawer]');
    var drawerBody = root.querySelector('[data-omo-stats-drawer-body]');
    var currentUrl = root.getAttribute('data-omo-stats-current-url') || '';
    var createUrl = root.getAttribute('data-omo-stats-create-url') || '';
    var detailBaseUrl = root.getAttribute('data-omo-stats-detail-url') || '';
    var currentScope = root.getAttribute('data-omo-stats-scope') || 'contextual';
    var routeCid = Number(root.getAttribute('data-omo-stats-route-cid') || 0);
    var initialIndicatorId = Number(root.getAttribute('data-omo-stats-open-indicator-id') || 0);
    var requestToken = 0;
    var listNeedsRefresh = false;
    var storageKey = 'omoStatsDisplayMode';
    var texts = <?= json_encode([
        'loading' => omoStatsT('stats.loading'),
        'loadError' => omoStatsT('stats.error.load'),
        'confirmDelete' => omoStatsT('stats.detail.confirm_delete'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function resolveUrl(url) {
        return typeof window.omoResolveAppUrl === 'function' ? window.omoResolveAppUrl(url) : url;
    }

    function normalizeScope(scope) {
        return scope === 'global' || scope === 'descendants' ? scope : 'contextual';
    }

    function buildScopeUrl(scope) {
        var organizationId = Number(root.getAttribute('data-omo-stats-oid') || 0);
        var query = ['oid=' + encodeURIComponent(String(organizationId))];
        var nextScope = normalizeScope(scope);
        if (routeCid > 0) {
            query.push('cid=' + encodeURIComponent(String(routeCid)));
        }
        if (nextScope !== 'contextual') {
            query.push('stats_scope=' + encodeURIComponent(nextScope));
        }
        return '/omo/api/stats/index.php?' + query.join('&');
    }

    function setLoading(isLoading) {
        root.classList.toggle('is-loading', Boolean(isLoading));
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-scope]'), function (button) {
            button.disabled = Boolean(isLoading);
        });
    }

    function refreshRoot(url) {
        var targetUrl = url || currentUrl;
        if (!targetUrl) {
            return Promise.resolve(null);
        }
        if (typeof window.omoReplaceFetchedPanelRoot !== 'function') {
            window.location.href = resolveUrl(targetUrl);
            return Promise.resolve(null);
        }
        return window.omoReplaceFetchedPanelRoot({
            rootSelector: '#omo-stats-root',
            currentRoot: root,
            url: resolveUrl(targetUrl),
            setLoadingState: setLoading
        });
    }

    function applyView(viewName) {
        var view = viewName === 'compact' ? 'compact' : 'cards';
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-view]'), function (button) {
            var active = button.getAttribute('data-omo-stats-view') === view;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-view-panel]'), function (panel) {
            panel.hidden = panel.getAttribute('data-omo-stats-view-panel') !== view;
        });
        try {
            window.localStorage.setItem(storageKey, view);
        } catch (error) {
        }
    }

    function executeFetchedScripts(container) {
        var scripts = Array.prototype.slice.call(container ? container.querySelectorAll('script') : []);
        var chain = Promise.resolve();
        scripts.forEach(function (script) {
            chain = chain.then(function () {
                var source = String(script.getAttribute('src') || '').trim();
                if (source) {
                    var existing = Array.prototype.some.call(document.querySelectorAll('script[src]'), function (candidate) {
                        return String(candidate.getAttribute('src') || '') === source;
                    });
                    if (existing) {
                        return null;
                    }
                    return new Promise(function (resolve) {
                        var external = document.createElement('script');
                        Array.prototype.forEach.call(script.attributes, function (attribute) {
                            external.setAttribute(attribute.name, attribute.value);
                        });
                        external.onload = resolve;
                        external.onerror = resolve;
                        document.body.appendChild(external);
                    });
                }
                var executable = document.createElement('script');
                executable.text = script.textContent || '';
                document.body.appendChild(executable);
                document.body.removeChild(executable);
                return null;
            });
        });
        return chain;
    }

    function buildDetailUrl(indicatorId) {
        return detailBaseUrl + (detailBaseUrl.indexOf('?') === -1 ? '?' : '&') + 'id=' + encodeURIComponent(String(indicatorId));
    }

    function setDrawerMessage(message, isError) {
        if (!drawerBody) {
            return;
        }
        drawerBody.innerHTML = '<div class="generic-section' + (isError ? ' omo-stats-feedback is-error' : '') + '"></div>';
        drawerBody.firstElementChild.textContent = message;
    }

    function openDrawerWithUrl(url) {
        if (!drawer || !drawerBody || !url) {
            return Promise.resolve(false);
        }
        setDrawerMessage(texts.loading, false);
        drawer.hidden = false;
        window.requestAnimationFrame(function () {
            drawer.classList.add('is-open');
        });
        var localToken = ++requestToken;
        return fetch(resolveUrl(url), {
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            cache: 'no-store'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('load_failed');
            }
            return response.text();
        }).then(function (html) {
            if (localToken !== requestToken || !drawerBody) {
                return false;
            }
            drawerBody.innerHTML = html;
            if (typeof window.initGenericComponents === 'function') {
                window.initGenericComponents(drawerBody);
            }
            return executeFetchedScripts(drawerBody).then(function () {
                if (typeof window.initGenericComponents === 'function') {
                    window.initGenericComponents(drawerBody);
                }
                return true;
            });
        }).catch(function () {
            if (localToken === requestToken) {
                setDrawerMessage(texts.loadError, true);
            }
            return false;
        });
    }

    function getCurrentRouteToken() {
        if (typeof window.omoParsePopupHashState !== 'function') {
            return '';
        }
        var state = window.omoParsePopupHashState();
        return state && state.routeToken ? String(state.routeToken) : '';
    }

    function closeDrawer(options) {
        var settings = options && typeof options === 'object' ? options : {};
        if (
            settings.force !== true
            && /^stats-(?:i|indicator-)(\d+)$/i.test(getCurrentRouteToken())
            && typeof window.omoOpenDrawerHashState === 'function'
        ) {
            window.omoOpenDrawerHashState('stats');
            return;
        }
        if (!drawer) {
            return;
        }
        drawer.classList.remove('is-open');
        window.setTimeout(function () {
            if (!drawer.classList.contains('is-open')) {
                drawer.hidden = true;
                if (drawerBody) {
                    drawerBody.innerHTML = '';
                }
                if (listNeedsRefresh) {
                    listNeedsRefresh = false;
                    refreshRoot(currentUrl);
                }
            }
        }, 180);
    }

    function openIndicator(indicatorId) {
        var resolvedId = Number(indicatorId || 0);
        if (!Number.isInteger(resolvedId) || resolvedId <= 0) {
            return;
        }
        var routeToken = typeof window.omoBuildStatsIndicatorRouteToken === 'function'
            ? window.omoBuildStatsIndicatorRouteToken(resolvedId)
            : 'stats-i' + String(resolvedId);
        if (typeof window.omoOpenDrawerHashState === 'function' && routeToken !== getCurrentRouteToken()) {
            window.omoOpenDrawerHashState(routeToken);
            return;
        }
        openDrawerWithUrl(buildDetailUrl(resolvedId));
    }

    function postFormData(formData) {
        return fetch(resolveUrl('/omo/api/stats/action.php'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: formData
        }).then(function (response) {
            return response.json().catch(function () {
                return null;
            }).then(function (payload) {
                if (!response.ok || !payload || payload.success !== true) {
                    throw new Error(payload && payload.message ? payload.message : texts.loadError);
                }
                return payload;
            });
        });
    }

    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-scope]'), function (button) {
        button.addEventListener('click', function () {
            var nextScope = normalizeScope(button.getAttribute('data-omo-stats-scope') || '');
            if (nextScope !== currentScope) {
                currentScope = nextScope;
                refreshRoot(buildScopeUrl(nextScope));
            }
        });
    });

    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-view]'), function (button) {
        button.addEventListener('click', function () {
            applyView(button.getAttribute('data-omo-stats-view') || 'cards');
        });
    });

    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-indicator-id]'), function (item) {
        function activate(event) {
            if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            event.preventDefault();
            openIndicator(item.getAttribute('data-omo-stats-indicator-id'));
        }
        item.addEventListener('click', activate);
        item.addEventListener('keydown', activate);
    });

    var createButton = root.querySelector('[data-omo-stats-open-create]');
    if (createButton) {
        createButton.addEventListener('click', function () {
            openDrawerWithUrl(createUrl);
        });
    }

    Array.prototype.forEach.call(root.querySelectorAll('[data-omo-stats-drawer-close]'), function (button) {
        button.addEventListener('click', closeDrawer);
    });

    if (drawerBody) {
        drawerBody.addEventListener('click', function (event) {
            var editButton = event.target.closest('[data-omo-stats-open-editor-url]');
            if (editButton) {
                event.preventDefault();
                openDrawerWithUrl(editButton.getAttribute('data-omo-stats-open-editor-url') || '');
                return;
            }

            var cancelButton = event.target.closest('[data-omo-stats-cancel-editor]');
            if (cancelButton) {
                event.preventDefault();
                var indicatorId = Number(cancelButton.getAttribute('data-indicator-id') || 0);
                if (indicatorId > 0) {
                    openDrawerWithUrl(buildDetailUrl(indicatorId));
                } else {
                    closeDrawer({force: true});
                }
                return;
            }

            var deleteButton = event.target.closest('[data-omo-stats-delete-value]');
            if (!deleteButton) {
                return;
            }
            event.preventDefault();
            if (!window.confirm(texts.confirmDelete)) {
                return;
            }
            var detail = deleteButton.closest('[data-omo-stats-detail]');
            var formData = new FormData();
            formData.append('stats_action', 'delete_value');
            formData.append('value_id', deleteButton.getAttribute('data-omo-stats-delete-value') || '');
            formData.append('oid', root.getAttribute('data-omo-stats-oid') || '');
            deleteButton.disabled = true;
            postFormData(formData).then(function () {
                listNeedsRefresh = true;
                return openDrawerWithUrl(detail ? detail.getAttribute('data-detail-url') : '');
            }).catch(function (error) {
                deleteButton.disabled = false;
                window.alert(error.message || texts.loadError);
            });
        });

        drawerBody.addEventListener('submit', function (event) {
            var form = event.target.closest('[data-omo-stats-add-value-form]');
            if (!form) {
                return;
            }
            event.preventDefault();
            var submitButton = form.querySelector('button[type="submit"]');
            var feedback = form.querySelector('[data-omo-stats-value-feedback]');
            if (submitButton) {
                submitButton.disabled = true;
            }
            if (feedback) {
                feedback.textContent = '';
                feedback.className = 'omo-stats-feedback';
            }
            postFormData(new FormData(form)).then(function () {
                var detail = form.closest('[data-omo-stats-detail]');
                listNeedsRefresh = true;
                return openDrawerWithUrl(detail ? detail.getAttribute('data-detail-url') : '');
            }).catch(function (error) {
                if (feedback) {
                    feedback.textContent = error.message || texts.loadError;
                    feedback.className = 'omo-stats-feedback is-error';
                }
            }).finally(function () {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            });
        });
    }

    window.omoStatsAfterIndicatorSave = function () {
        var editor = drawerBody ? drawerBody.querySelector('[data-omo-stats-editor]') : null;
        var indicatorId = Number(editor ? (editor.getAttribute('data-indicator-id') || 0) : 0);
        if (indicatorId > 0) {
            listNeedsRefresh = true;
            openDrawerWithUrl(buildDetailUrl(indicatorId));
            return;
        }
        listNeedsRefresh = false;
        closeDrawer({force: true});
        refreshRoot(currentUrl);
    };
    window.omoStatsRefreshCurrentView = function () {
        return refreshRoot(currentUrl);
    };

    if (!root.__omoStatsRouteHandler) {
        root.__omoStatsRouteHandler = function (routeEvent) {
            if (!document.body.contains(root)) {
                return;
            }
            var detail = routeEvent && routeEvent.detail ? routeEvent.detail : {};
            var indicatorId = Number(detail.indicatorId || 0);
            if (indicatorId > 0) {
                openDrawerWithUrl(buildDetailUrl(indicatorId));
            } else {
                closeDrawer({force: true});
            }
        };
        window.addEventListener('omo-stats-route-change', root.__omoStatsRouteHandler);
    }

    var preferredView = 'cards';
    try {
        preferredView = window.localStorage.getItem(storageKey) || 'cards';
    } catch (error) {
    }
    applyView(preferredView);

    if (Number.isInteger(initialIndicatorId) && initialIndicatorId > 0) {
        window.setTimeout(function () {
            openDrawerWithUrl(buildDetailUrl(initialIndicatorId));
        }, 40);
    }
})();
</script>
