<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayProject;
use dbObject\Holon;
use dbObject\Project;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$projectId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$context = omoProjectsResolveContext($organizationId, isset($_GET['cid']) ? (int)$_GET['cid'] : 0);

if (empty($context['status'])) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

if ($projectId <= 0) {
    $availableScopes = omoApiGetAvailableContextScopes(
        $context['currentHolon'] instanceof Holon,
        $context['currentHolon'],
        $context['rootHolon']
    );
    $projectScope = omoApiNormalizeContextScope($_GET['project_scope'] ?? 'contextual', $availableScopes);
    $projectAssignment = strtolower(trim((string)($_GET['project_assignment'] ?? 'all')));
    $projectAssignment = $projectAssignment === 'mine' ? 'mine' : 'all';
    $projectQuickSearch = trim((string)($_GET['project_query'] ?? ''));
    $currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
    $currentHolon = $context['currentHolon'];
    $descendantHolonIds = $projectScope === 'descendants' && $currentHolon instanceof Holon
        ? omoApiGetDescendantHolonIds($currentHolon)
        : [];
    $scopeHolonIds = $projectScope === 'children'
        ? omoApiGetDirectChildScopeHolonIds($currentHolon)
        : $descendantHolonIds;
    $normalizedSearch = omoProjectsNormalizeSearchText($projectQuickSearch);
    $today = new DateTimeImmutable('today');
    $groups = sharedGetRelativeDateGroups($today, [
        'today' => omoProjectsT('projects.archives.group.today'),
        'yesterday' => omoProjectsT('projects.archives.group.yesterday'),
        'this_week' => omoProjectsT('projects.archives.group.this_week'),
        'last_week' => omoProjectsT('projects.archives.group.last_week'),
        'this_month' => omoProjectsT('projects.archives.group.this_month'),
        'last_month' => omoProjectsT('projects.archives.group.last_month'),
        'this_year' => omoProjectsT('projects.archives.group.this_year'),
        'last_year' => omoProjectsT('projects.archives.group.last_year'),
        'earlier' => omoProjectsT('projects.archives.group.earlier'),
        'too_far' => omoProjectsT('projects.archives.group.too_far'),
    ]);
    $groupedProjects = [];
    foreach ($groups as $group) {
        $groupedProjects[(string)$group['key']] = [
            'label' => (string)$group['label'],
            'items' => [],
        ];
    }

    $archivedProjects = new ArrayProject();
    $archivedProjects->loadArchivedForOrganization($organizationId);
    foreach ($archivedProjects as $archivedProject) {
        if (!($archivedProject instanceof Project)) {
            continue;
        }
        if (!omoProjectsScopeContainsProject($archivedProject, $projectScope, $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0, $scopeHolonIds)) {
            continue;
        }
        if ($projectAssignment === 'mine' && (int)$archivedProject->get('IDuser') !== $currentUserId) {
            continue;
        }

        $projectHolon = $archivedProject->getHolon();
        $contextLabel = $projectHolon instanceof Holon
            ? trim((string)$projectHolon->getDisplayName())
            : trim((string)$context['organization']->get('name'));
        $responsibleLabel = omoProjectsGetUserLabel($archivedProject->getResponsible());
        $statusLabel = omoProjectsStatusLabel($archivedProject->get('status'));
        $searchText = omoProjectsNormalizeSearchText(
            trim((string)$archivedProject->get('title')) . ' '
            . $contextLabel . ' '
            . $responsibleLabel . ' '
            . $statusLabel
        );
        if ($normalizedSearch !== '' && strpos($searchText, $normalizedSearch) === false) {
            continue;
        }

        $archiveMetadata = omoProjectsGetArchiveDate($archivedProject);
        $archiveDate = $archiveMetadata['date'] ?? null;
        $groupIndex = sharedGetRelativeDateGroupIndexForDate($archiveDate, $groups, $today);
        $group = $groups[$groupIndex] ?? $groups[count($groups) - 1];
        $groupKey = (string)$group['key'];
        if (!isset($groupedProjects[$groupKey])) {
            $groupedProjects[$groupKey] = [
                'label' => (string)$group['label'],
                'items' => [],
            ];
        }
        $dateLabel = omoProjectsFormatDate($archiveDate);
        $dateType = (string)($archiveMetadata['type'] ?? 'archived');
        $dateText = $dateLabel !== ''
            ? omoProjectsT('projects.archives.date.' . ($dateType === 'closed' ? 'closed' : 'archived'), ['date' => $dateLabel])
            : omoProjectsT('projects.archives.group.too_far');
        $groupedProjects[$groupKey]['items'][] = [
            'project' => $archivedProject,
            'contextLabel' => $contextLabel,
            'responsibleLabel' => $responsibleLabel,
            'statusLabel' => $statusLabel,
            'dateText' => $dateText,
            'timestamp' => $archiveDate instanceof DateTimeInterface ? (int)$archiveDate->format('U') : 0,
        ];
    }

    foreach ($groupedProjects as &$group) {
        usort($group['items'], static function (array $left, array $right): int {
            $timestampComparison = (int)$right['timestamp'] <=> (int)$left['timestamp'];
            if ($timestampComparison !== 0) {
                return $timestampComparison;
            }
            return strcasecmp(
                (string)$left['project']->get('title'),
                (string)$right['project']->get('title')
            );
        });
    }
    unset($group);

    $hasArchivedProject = false;
    ?>
    <div class="omo-project-archives__content" data-topbar-modal-max-width="760px">
        <?php foreach ($groupedProjects as $group): ?>
            <?php if (count($group['items']) === 0): continue; endif; ?>
            <?php $hasArchivedProject = true; ?>
            <section class="omo-project-archives__group">
                <h3 class="omo-project-archives__group-title generic-card-title generic-card-title--small"><?= omoApiEscape($group['label']) ?></h3>
                <div class="omo-project-archives__group-items">
                    <?php foreach ($group['items'] as $archiveItem): ?>
                        <?php $archivedProject = $archiveItem['project']; ?>
                        <article class="generic-soft-panel omo-project-archives__item" data-project-id="<?= (int)$archivedProject->getId() ?>">
                            <div class="omo-project-archives__item-main">
                                <a href="#projects-d<?= (int)$archivedProject->getId() ?>" class="omo-project-archives__item-title" data-omo-project-archive-link data-project-id="<?= (int)$archivedProject->getId() ?>"><?= omoApiEscape((string)$archivedProject->get('title')) ?></a>
                                <span class="omo-project-archives__item-date"><?= omoApiEscape($archiveItem['dateText']) ?></span>
                            </div>
                            <div class="omo-project-archives__item-meta">
                                <span><?= omoApiEscape($archiveItem['statusLabel']) ?></span>
                                <span><?= omoApiEscape($archiveItem['contextLabel']) ?></span>
                                <span><?= omoApiEscape($archiveItem['responsibleLabel']) ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
        <?php if (!$hasArchivedProject): ?>
            <p class="generic-description generic-description--small"><?= omoApiEscape(omoProjectsT('projects.archives.empty')) ?></p>
        <?php endif; ?>
    </div>
    <?php
    exit;
}

$project = new Project();
if (
    !$project->load($projectId)
    || (int)$project->get('IDorganization') !== $organizationId
    || (int)$project->get('active') !== 1
) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

$projectHolon = $project->getHolon();
$rootHolon = $context['rootHolon'];
if (
    $projectHolon instanceof Holon
    && (
        !($rootHolon instanceof Holon)
        || !$projectHolon->isDescendantOf((int)$rootHolon->getId(), true)
        || !$projectHolon->canViewDetail()
    )
) {
    http_response_code(404);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.not_found')) . '</div>';
    exit;
}

$archivedProjects = new ArrayProject();
$archivedProjects->loadForParent($projectId, false);
$hasArchivedProject = false;
?>
<div class="omo-project-archives__list">
    <?php foreach ($archivedProjects as $archivedProject): ?>
        <?php if (!($archivedProject instanceof Project) || (int)$archivedProject->get('active') === 1): continue; endif; ?>
        <?php $hasArchivedProject = true; ?>
        <div class="generic-soft-panel omo-project-archives__item">
            <a href="#projects-d<?= (int)$archivedProject->getId() ?>" class="omo-project-archives__item-title" data-omo-project-archive-link data-project-id="<?= (int)$archivedProject->getId() ?>"><?= omoApiEscape((string)$archivedProject->get('title')) ?></a>
            <span><?= omoApiEscape(omoProjectsStatusLabel($archivedProject->get('status'))) ?><?php if ($archivedProject->get('planned_start_date') instanceof DateTimeInterface): ?> · <?= omoApiEscape($archivedProject->get('planned_start_date')->format('d.m.Y')) ?><?php endif; ?></span>
        </div>
    <?php endforeach; ?>
</div>
<?php if (!$hasArchivedProject): ?>
    <p class="omo-project-detail__muted generic-description generic-description--small"><?= omoApiEscape(omoProjectsT('projects.detail.archives.empty')) ?></p>
<?php endif; ?>
