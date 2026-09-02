<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayControlActivity;
use dbObject\ControlActivity;
use dbObject\ControlTaskCheck;
use dbObject\Holon;
use dbObject\RecurrenceSchedule;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = (int)($_GET['cid'] ?? 0);
$context = omoActivityResolveContext($organizationId, $currentHolonId);
if (empty($context['status'])) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape($context['message']) . '</div>';
    exit;
}

$currentHolon = $context['currentHolon'];
$rootHolon = $context['rootHolon'];
$organization = $context['organization'];
$currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
$applicationViewPreferences = omoApplicationViewPreferencesGetContext('activities', $organization, $currentHolon, $currentUserId);
$scopes = omoApiGetAvailableContextScopes(true, $currentHolon, $rootHolon);
$scope = omoApiNormalizeContextScope(
    omoApplicationViewPreferencesGetInitialValue($applicationViewPreferences, 'activity_scope', 'scope', 'contextual'),
    $scopes
);
$holonIds = $scope === 'children'
    ? omoApiGetDirectChildScopeHolonIds($currentHolon)
    : ($scope === 'descendants' ? omoApiGetDescendantHolonIds($currentHolon) : [(int)$currentHolon->getId()]);

$activities = new ArrayControlActivity();
$activities->loadForContext($organizationId, $holonIds);
$now = new DateTimeImmutable('now');
$frequencyOrder = RecurrenceSchedule::getFrequencyCatalog();
$groups = array_fill_keys($frequencyOrder, []);
$activityCount = 0;

foreach ($activities as $activity) {
    if (!($activity instanceof ControlActivity) || !omoActivityCanView($activity)) {
        continue;
    }
    $frequency = RecurrenceSchedule::normalizeFrequency($activity->get('frequency'));
    if (!$frequency || !array_key_exists($frequency, $groups)) {
        continue;
    }
    $state = $activity->getOccurrenceState($now);
    $holon = $activity->getHolon();
    $check = $state['check'] ?? null;
    $checkedAt = $check instanceof ControlTaskCheck && $check->get('checked_at') instanceof DateTimeInterface
        ? DateTimeImmutable::createFromInterface($check->get('checked_at'))
        : null;
    $deadlineAt = $state['deadlineAt'] ?? null;
    $occurrenceAt = $state['occurrenceAt'] ?? null;
    $stateKey = (string)($state['state'] ?? 'upcoming');
    $detailUrl = '/omo/api/activities/detail.php?oid=' . $organizationId . '&id=' . (int)$activity->getId()
        . ($currentHolonId > 0 ? '&cid=' . $currentHolonId : '');

    $groups[$frequency][] = [
        'activity' => $activity,
        'state' => $state,
        'stateKey' => $stateKey,
        'holonName' => $holon instanceof Holon ? $holon->getDisplayName() : '',
        'checkedAt' => $checkedAt,
        'deadlineAt' => $deadlineAt instanceof DateTimeInterface ? $deadlineAt : null,
        'occurrenceAt' => $occurrenceAt instanceof DateTimeInterface ? $occurrenceAt : null,
        'overdueLabel' => omoActivityOverdueLabel($state, $now),
        'detailUrl' => $detailUrl,
    ];
    $activityCount++;
}

$statePriority = ['missed' => 0, 'due' => 1, 'late' => 2, 'checked' => 3, 'upcoming' => 4];
foreach ($groups as &$groupRows) {
    usort($groupRows, static function (array $left, array $right) use ($statePriority) {
        $leftPriority = $statePriority[$left['stateKey']] ?? 9;
        $rightPriority = $statePriority[$right['stateKey']] ?? 9;
        if ($leftPriority !== $rightPriority) {
            return $leftPriority <=> $rightPriority;
        }
        $leftTimestamp = $left['occurrenceAt'] instanceof DateTimeInterface ? $left['occurrenceAt']->getTimestamp() : PHP_INT_MAX;
        $rightTimestamp = $right['occurrenceAt'] instanceof DateTimeInterface ? $right['occurrenceAt']->getTimestamp() : PHP_INT_MAX;
        if ($leftTimestamp !== $rightTimestamp) {
            return $leftTimestamp <=> $rightTimestamp;
        }
        return strnatcasecmp((string)$left['activity']->get('title'), (string)$right['activity']->get('title'));
    });
}
unset($groupRows);

$baseUrl = '/omo/api/activities/index.php?oid=' . $organizationId
    . ($currentHolonId > 0 ? '&cid=' . $currentHolonId : '');
$currentUrl = $baseUrl . ($scope !== 'contextual' ? '&activity_scope=' . rawurlencode($scope) : '');
$createUrl = '/omo/api/activities/edit.php?oid=' . $organizationId
    . ($currentHolonId > 0 ? '&cid=' . $currentHolonId : '');
$canCreate = omoActivityCanUsePermission($currentHolon, 'CAN_CREATE_CONTROL_ACTIVITY');
$stateFilters = ['all', 'attention', 'missed', 'checked', 'upcoming'];
$texts = [
    'loading' => omoActivityT('activity.loading'),
    'loadingError' => omoActivityT('activity.error.load'),
    'actionError' => omoActivityT('activity.error.action'),
];
?>
<link rel="stylesheet" href="/common/view-filter/view-filter.css?v=20260902-save-menu">
<link rel="stylesheet" href="/omo/api/activities/activities.css?v=20260901-timeline-fluid-4">
<div
    class="omo-activities omo-panel-view"
    id="omo-activities-root"
    data-activity-oid="<?= (int)$organizationId ?>"
    data-activity-cid="<?= (int)$currentHolonId ?>"
    data-activity-scope="<?= omoApiEscape($scope) ?>"
    data-activity-current-url="<?= omoApiEscape($currentUrl) ?>"
    data-activity-base-url="<?= omoApiEscape($baseUrl) ?>"
    data-activity-texts="<?= omoApiEscape(json_encode($texts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    data-omo-app-view-preferences="<?= omoApiEscape(json_encode($applicationViewPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    data-omo-view-filter-pending="1"
    aria-busy="true"
>
    <header class="omo-activities__header omo-panel-view__header omo-panel-view__header--stacked">
        <div class="omo-panel-view__header-main">
            <div class="omo-panel-view__title-cluster">
                <span class="omo-panel-view__app-icon" aria-hidden="true"><img src="/omo/images/tools/control-list.png" alt=""></span>
                <div class="omo-panel-view__header-copy">
                    <div class="generic-title-row generic-title-row--center">
                        <h2 class="omo-panel-view__title"><?= omoApiEscape(omoActivityT('activity.title')) ?></h2>
                        <span class="omo-panel-view__count" data-activity-header-count data-activity-total-count="<?= $activityCount ?>"><?= $activityCount ?></span>
                    </div>
                </div>
            </div>
            <?php if ($canCreate): ?>
                <div class="omo-panel-view__header-actions" data-omo-header-actions>
                    <button type="button" class="generic-action-button generic-action-button--main omo-mobile-corner-action" data-activity-open-url="<?= omoApiEscape($createUrl) ?>"><?= omoApiEscape(omoActivityT('activity.new')) ?></button>
                </div>
            <?php endif; ?>
        </div>
        <div class="omo-panel-view__header-secondary">
            <div class="omo-view-filter" data-activity-filter-control role="group" aria-label="<?= omoApiEscape(omoActivityT('activity.filters.aria')) ?>">
                <div class="omo-view-filter__input">
                    <div class="omo-view-filter__chips">
                        <button type="button" class="omo-view-filter__chip" data-activity-filter-toggle data-activity-scope-chip aria-expanded="false" aria-controls="omo-activity-filter-panel"><?= omoApiEscape(omoActivityT('activity.scope.' . $scope)) ?></button>
                        <button type="button" class="omo-view-filter__chip" data-activity-filter-toggle data-activity-state-chip aria-expanded="false" aria-controls="omo-activity-filter-panel"><?= omoApiEscape(omoActivityT('activity.filter.all')) ?></button>
                    </div>
                    <label class="omo-view-filter__search">
                        <input type="search" class="generic-form-control" data-activity-quick-search placeholder="<?= omoApiEscape(omoActivityT('activity.search.placeholder')) ?>" aria-label="<?= omoApiEscape(omoActivityT('activity.search.aria')) ?>" autocomplete="off">
                    </label>
                </div>
                <section id="omo-activity-filter-panel" class="omo-view-filter__panel generic-soft-panel generic-soft-panel--stack" data-activity-filter-panel hidden>
                    <div class="omo-view-filter__panel-grid omo-activities__filter-grid">
                        <div class="omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoActivityT('activity.filters.scope')) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoActivityT('activity.filters.scope')) ?>">
                                <?php foreach ($scopes as $option): ?>
                                    <button type="button" class="omo-segmented__button<?= $scope === $option ? ' is-active' : '' ?>" data-activity-scope-option="<?= omoApiEscape($option) ?>" aria-pressed="<?= $scope === $option ? 'true' : 'false' ?>"><?= omoApiEscape(omoActivityT('activity.scope.' . $option)) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="omo-view-filter__group">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoActivityT('activity.filters.state')) ?></span>
                            <div class="omo-segmented" role="group" aria-label="<?= omoApiEscape(omoActivityT('activity.filters.state')) ?>">
                                <?php foreach ($stateFilters as $stateFilter): ?>
                                    <button type="button" class="omo-segmented__button<?= $stateFilter === 'all' ? ' is-active' : '' ?>" data-activity-state-option="<?= omoApiEscape($stateFilter) ?>" aria-pressed="<?= $stateFilter === 'all' ? 'true' : 'false' ?>"><?= omoApiEscape(omoActivityT('activity.filter.' . $stateFilter)) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="omo-view-filter__actions">
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-activity-filter-apply><?= omoApiEscape(omoActivityT('activity.filters.apply')) ?></button>
                        <?php if (!empty($applicationViewPreferences['canSavePersonal'])): ?>
                            <button type="button" class="generic-action-button generic-action-button--main" data-activity-filter-save data-omo-app-view-save-scope="personal"><?= omoApiEscape(omoActivityT('activity.filters.save_view')) ?></button>
                        <?php elseif (($applicationViewPreferences['primarySaveScope'] ?? '') !== ''): ?>
                            <button type="button" class="generic-action-button generic-action-button--main" data-omo-app-view-save-scope="<?= omoApiEscape($applicationViewPreferences['primarySaveScope']) ?>"><?= omoApiEscape(omoApplicationViewPreferencesT('app_view.save_organization_template', array('templateName' => $applicationViewPreferences['templateLabel'] ?? ''))) ?></button>
                        <?php endif; ?>
                        <?= omoApplicationViewPreferencesRenderMenu($applicationViewPreferences) ?>
                    </div>
                </section>
            </div>
        </div>
    </header>

    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content omo-activities__body">
            <?php if ($activityCount === 0): ?>
                <div class="omo-empty-state" data-activity-default-empty><?= omoApiEscape(omoActivityT('activity.empty')) ?></div>
            <?php else: ?>
                <div class="generic-file-list generic-file-list--structured generic-file-list--stacked-sticky omo-activities__groups">
                    <?php foreach ($frequencyOrder as $frequency): ?>
                        <?php if (count($groups[$frequency]) === 0) { continue; } ?>
                        <section class="omo-panel-group generic-file-list__group omo-activities__group" data-activity-group>
                            <h3 class="omo-panel-group__title generic-file-list__group-title">
                                <span><?= omoApiEscape(omoActivityFrequencyLabel($frequency)) ?></span>
                                <span class="omo-activities__group-count" data-activity-group-count><?= count($groups[$frequency]) ?></span>
                            </h3>
                            <div class="generic-file-list__table omo-activities__table">
                                <div class="generic-file-list__header" aria-hidden="true">
                                    <span class="generic-file-list__header-cell"><?= omoApiEscape(omoActivityT('activity.column.activity')) ?></span>
                                    <span class="generic-file-list__header-cell"><?= omoApiEscape(omoActivityT('activity.column.context')) ?></span>
                                    <span class="generic-file-list__header-cell"><?= omoApiEscape(omoActivityT('activity.column.next')) ?></span>
                                    <span class="generic-file-list__header-cell"><?= omoApiEscape(omoActivityT('activity.column.status')) ?></span>
                                </div>
                                <?php foreach ($groups[$frequency] as $row): ?>
                                    <?php
                                    $activity = $row['activity'];
                                    $stateKey = $row['stateKey'];
                                    $dateLabel = '';
                                    if ($stateKey === 'checked' && $row['checkedAt'] instanceof DateTimeInterface) {
                                        $dateLabel = omoActivityT('activity.checked.on', ['date' => $row['checkedAt']->format('d.m.Y à H:i')]);
                                    } elseif ($stateKey === 'late' && $row['checkedAt'] instanceof DateTimeInterface) {
                                        $dateLabel = omoActivityT('activity.checked.late_on', ['date' => $row['checkedAt']->format('d.m.Y à H:i')]);
                                    } elseif ($stateKey === 'missed') {
                                        $dateLabel = $row['overdueLabel'];
                                    } elseif (in_array($stateKey, ['due', 'upcoming'], true) && $row['occurrenceAt'] instanceof DateTimeInterface) {
                                        $dateLabel = $row['occurrenceAt']->format('d.m.Y à H:i');
                                    } elseif ($row['occurrenceAt'] instanceof DateTimeInterface) {
                                        $dateLabel = $row['occurrenceAt']->format('d.m.Y à H:i');
                                    }
                                    ?>
                                    <div class="generic-file-list__item-shell omo-activity-row-shell omo-activity-row-shell--<?= omoApiEscape($stateKey) ?>" data-activity-search-item data-activity-state="<?= omoApiEscape($stateKey) ?>">
                                        <article class="generic-file-list__row omo-activity-row" data-activity-open-url="<?= omoApiEscape($row['detailUrl']) ?>" tabindex="0" role="button" aria-label="<?= omoApiEscape((string)$activity->get('title')) ?>">
                                            <div class="generic-file-list__cell generic-file-list__cell--name" data-label="<?= omoApiEscape(omoActivityT('activity.column.activity')) ?>">
                                                <div class="generic-file-list__name-main">
                                                    <span class="generic-file-list__icon-box omo-activity-row__icon" aria-hidden="true"><img src="/omo/images/tools/control-list.png" alt=""></span>
                                                    <span class="generic-file-list__title-block">
                                                        <span class="generic-file-list__title-row"><strong class="generic-file-list__title"><?= omoApiEscape((string)$activity->get('title')) ?></strong></span>
                                                        <span class="generic-file-list__meta-line"><?= omoApiEscape(omoActivityScheduleLabel($frequency, $activity->get('schedule'))) ?><?php if (trim((string)$activity->get('description')) !== ''): ?> · <?= omoApiEscape(mb_strimwidth(trim((string)$activity->get('description')), 0, 85, '…', 'UTF-8')) ?><?php endif; ?></span>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="generic-file-list__cell" data-label="<?= omoApiEscape(omoActivityT('activity.column.context')) ?>"><span class="omo-activity-row__context"><?= omoApiEscape($row['holonName']) ?></span></div>
                                            <div class="generic-file-list__cell" data-label="<?= omoApiEscape(omoActivityT('activity.column.next')) ?>"><time class="omo-activity-row__date<?= $stateKey === 'missed' ? ' is-missed' : '' ?>"><?= omoApiEscape($dateLabel) ?></time></div>
                                            <div class="generic-file-list__cell omo-activity-row__status-cell" data-label="<?= omoApiEscape(omoActivityT('activity.column.status')) ?>">
                                                <span class="omo-activity-badge omo-activity-badge--<?= omoApiEscape($stateKey) ?>"><?= omoApiEscape(omoActivityStateLabel($row['state'], $now)) ?></span>
                                                <?php if (in_array($stateKey, ['due', 'missed'], true)): ?>
                                                    <button type="button" class="generic-action-button generic-action-button--main omo-activity-row__check" data-activity-post-action="check_activity" data-activity-id="<?= (int)$activity->getId() ?>" data-activity-list-check><?= omoApiEscape(omoActivityT('activity.done')) ?></button>
                                                <?php endif; ?>
                                            </div>
                                        </article>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="omo-empty-state" data-activity-search-empty hidden><?= omoApiEscape(omoActivityT('activity.search.empty')) ?></div>
        </div>
    </div>

    <div class="omo-overlay-drawer omo-activities__drawer" data-activity-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-activity-close></div>
        <div class="omo-overlay-drawer__panel">
            <div class="omo-overlay-drawer__header generic-drawer-header generic-drawer-header--sticky">
                <div class="omo-overlay-drawer__header-copy generic-drawer-header__copy">
                    <h3 data-omo-subdrawer-title><?= omoApiEscape(omoActivityT('activity.title')) ?></h3>
                    <p data-omo-subdrawer-description><?= omoApiEscape(omoActivityT('activity.description')) ?></p>
                </div>
                <div class="generic-drawer-header__actions">
                    <div data-omo-subdrawer-actions></div>
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-activity-close><?= omoApiEscape(omoActivityT('activity.close')) ?></button>
                </div>
            </div>
            <div class="omo-overlay-drawer__body" data-activity-drawer-body></div>
        </div>
    </div>
</div>
<script src="/common/drawer/subdrawer.js?v=20260816-header-help"></script>
<script src="/omo/assets/js/application-view-preferences.js?v=20260902-view-cleanup"></script>
<script src="/omo/api/activities/activities.js?v=20260902-restore-default"></script>
