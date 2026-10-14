<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';
use dbObject\ScrumSprint;
use dbObject\ScrumSample;
use dbObject\Project;
use dbObject\ArrayProject;
use dbObject\User;

try {
    $context = omoScrumContext();
    $organizationId = (int)$context['organization']->getId();
    ScrumSprint::maintenance($organizationId);
    $sprintId = (int)($_GET['id'] ?? 0);
    $scrumSprint = $sprintId ? omoScrumLoad($context, $sprintId) : null;
    $visibleNodes = $scrumSprint ? $scrumSprint->nodes(true, static fn (Project $project) => omoProjectsCanViewProject($project, $context)) : [];
    $canManage = omoProjectsCanManageContext($context);
    $editing = isset($_GET['edit']); $estimating = isset($_GET['estimates']); $importing = isset($_GET['import']) || $estimating; $archived = !empty($_GET['archives']);
    if (($editing || $importing) && (!$canManage || ($scrumSprint && !$scrumSprint->editable()))) { throw new RuntimeException('access'); }
    if ($importing && !$scrumSprint) { throw new RuntimeException('missing'); }
    $texts = [];
    foreach (array_keys(omoScrumSourceLang()) as $key) { $texts[$key] = omoScrumT($key); }
    foreach (['assignmentAria' => 'projects.assignment.aria', 'assignmentMine' => 'projects.assignment.mine', 'assignmentFollowed' => 'projects.assignment.followed', 'assignmentEveryone' => 'projects.assignment.everyone', 'quickSearchPlaceholder' => 'projects.search.placeholder'] as $key => $sourceKey) { $texts[$key] = omoProjectsT($sourceKey); }
    $texts['scopeLabels'] = ['local' => omoProjectsT('projects.scope.contextual'), 'children' => omoProjectsT('projects.scope.children'), 'descendants' => omoProjectsT('projects.scope.descendants')];
    $csrf = commonCsrfToken();
} catch (Throwable $error) {
    http_response_code(403); echo omoApiEscape(omoScrumT(isset(omoScrumSourceLang()[$error->getMessage()]) ? $error->getMessage() : 'save')); return;
}
$rootId = $importing ? 'omo-scrum-picker' : (($sprintId || $editing) ? 'omo-scrum-detail' : 'omo-scrum-root');
$e = static fn ($value) => omoApiEscape($value);
?>
<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/scrum/scrum.css') ?>">
<link rel="stylesheet" href="<?= commonAssetUrl('/common/project-picker/project-picker.css') ?>">
<div id="<?= $rootId ?>" data-scrum-root class="<?= $importing ? 'generic-form-stack' : 'omo-panel-view' ?>" data-estimates-only="<?= $estimating ? '1' : '0' ?>" data-id="<?= $sprintId ?>" data-csrf="<?= $e($csrf) ?>"
    data-action-url="<?= $e(omoScrumUrl($context, [], 'action.php')) ?>" data-url="<?= $e(omoScrumUrl($context, $sprintId ? ['id' => $sprintId] : ['archives' => (int)$archived])) ?>"
    data-texts="<?= $e(json_encode($texts, JSON_UNESCAPED_UNICODE)) ?>"<?= $importing ? ' data-topbar-modal-max-width="1100px"' : '' ?>>
    <?php if (!$importing): ?>
    <header class="omo-panel-view__header<?= $rootId === 'omo-scrum-detail' ? ' generic-drawer-header' : '' ?>">
        <div class="omo-panel-view__header-main">
            <h2 class="omo-panel-view__title"><?= $e($scrumSprint ? $scrumSprint->get('title') : omoScrumT($editing ? 'new' : ($archived ? 'archives' : 'title'))) ?></h2>
            <div class="generic-panel-actions-host" data-omo-header-actions>
                <?php if (!$sprintId && !$editing): ?>
                    <?php if ($canManage): ?><a class="generic-action-button generic-action-button--main" data-scrum-open href="<?= $e(omoScrumUrl($context, ['edit' => 1])) ?>"><?= $e(omoScrumT('new')) ?></a><?php endif; ?>
                <?php elseif ($scrumSprint && !$editing && $canManage && $scrumSprint->editable()): ?>
                    <button type="button" class="generic-action-button generic-action-button--main" data-scrum-popup="<?= $e(omoScrumUrl($context, ['id' => $sprintId, 'import' => 1])) ?>"><?= $e(omoScrumT('add')) ?></button>
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-scrum-action="resume" data-restart="<?= $scrumSprint->get('started_at') ? '1' : '0' ?>"><?= $e(omoScrumT('resume')) ?></button>
                <?php endif; ?>
                <div class="generic-menu generic-panel-actions">
                    <button type="button" class="generic-menu-toggle" data-common-panel-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="<?= $e(omoScrumT('more')) ?>">&#8942;</button>
                    <div class="generic-menu-panel generic-menu-panel--wide generic-menu-panel--anchored" data-common-panel-actions-panel role="menu" hidden>
                        <?php if (!$sprintId && !$editing): ?>
                            <a class="generic-menu-item" role="menuitem" data-scrum-link href="<?= $e(omoScrumUrl($context, $archived ? [] : ['archives' => 1])) ?>"><?= $e(omoScrumT($archived ? 'title' : 'archives')) ?></a>
                        <?php elseif ($scrumSprint && !$editing && $canManage): ?>
                            <?php if ($scrumSprint->editable()): ?><a class="generic-menu-item" role="menuitem" data-scrum-link href="<?= $e(omoScrumUrl($context, ['id' => $sprintId, 'edit' => 1])) ?>"><?= $e(omoScrumT('edit')) ?></a><?php endif; ?>
                            <?php if ($scrumSprint->get('state') === 'running'): ?><button type="button" class="generic-menu-item" role="menuitem" data-scrum-action="stop"><?= $e(omoScrumT('stop')) ?></button><?php endif; ?>
                            <?php if ($scrumSprint->get('state') === 'finished'): ?><button type="button" class="generic-menu-item" role="menuitem" data-scrum-action="<?= $scrumSprint->get('archived') ? 'restore' : 'archive' ?>"><?= $e(omoScrumT($scrumSprint->get('archived') ? 'restore' : 'archive')) ?></button><?php endif; ?>
                            <?php if ($scrumSprint->get('state') !== 'running'): ?><button type="button" class="generic-menu-item generic-menu-item--danger" role="menuitem" data-scrum-action="delete"><?= $e(omoScrumT('delete')) ?></button><?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($rootId === 'omo-scrum-detail'): ?><button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--icon-only generic-action-button--close" data-scrum-close data-common-drawer-dismiss aria-label="<?= $e(omoScrumT('close')) ?>" title="<?= $e(omoScrumT('close')) ?>"></button><?php endif; ?>
            </div>
        </div>
    </header>
    <?php endif; ?>
    <div class="omo-panel-view__body"><div class="omo-panel-view__body_content generic-form-stack">
    <?php if ($editing): ?>
        <?php
        $editor = $scrumSprint ?: new ScrumSprint();
        if (!$sprintId) { $editor->set('start_date', ScrumSprint::now()); $editor->set('end_date', ScrumSprint::now()->modify('+13 days')); }
        $editor->display('adminEdit.php', ['buttons' => false, 'fields' => ['title', 'end_date', 'objective'], 'action' => omoScrumUrl($context, [], 'action.php')]);
        ?>
        <div class="generic-form-actions"><button type="submit" form="formulaire-edit" class="generic-action-button generic-action-button--main"><?= $e(omoScrumT('save_action')) ?></button></div>
    <?php elseif ($importing): ?>
        <?php
        $projects = new ArrayProject(); $projects->loadForOrganization($organizationId, false, Project::KIND_STANDARD, true);
        $pickerNodes = [];
        $enrolledNodes = $estimating ? $scrumSprint->nodes() : [];
        foreach ($projects as $project) {
            if (($estimating && !isset($visibleNodes[(int)$project->getId()])) || !omoProjectsCanViewProject($project, $context) || $project->isPrivateProposal()) { continue; }
            $node = ScrumSprint::projectData($project); $node['active'] = (int)$project->get('active');
            $node['editable'] = omoProjectsCanManageProject($project, $context);
            $node['removable'] = $estimating && !isset($enrolledNodes[$enrolledNodes[$node['id']]['parent']]);
            $node['holonId'] = $node['holon'] ?: (int)$context['rootHolon']->getId();
            $node['isMine'] = $node['user'] > 0 && $node['user'] === (int)commonGetCurrentUserId();
            $pickerNodes[] = $node;
        }
        $followedIds = $estimating ? [] : \dbObject\ProjectFollower::getActiveProjectIds(array_column($pickerNodes, 'id'));
        foreach ($pickerNodes as &$node) { $node['isFollowed'] = isset($followedIds[$node['id']]); }
        unset($node);
        ?>
        <section class="generic-form-stack generic-drawer-content" data-scrum-step="selection"<?= $estimating ? ' hidden' : '' ?> data-scrum-picker data-nodes="<?= $e(json_encode($pickerNodes, JSON_UNESCAPED_UNICODE)) ?>" data-existing="<?= $e(json_encode(array_keys($visibleNodes))) ?>" data-oid="<?= $organizationId ?>" data-cid="<?= (int)$context['currentHolon']->getId() ?>">
            <p class="generic-description"><?= $e(omoScrumT('import_hint')) ?></p>
            <div data-scrum-selection-surface></div>
            <div class="generic-form-actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-scrum-cancel><?= $e(omoScrumT('cancel')) ?></button><button type="button" class="generic-action-button generic-action-button--main" data-scrum-next><?= $e(omoScrumT('next')) ?></button></div>
        </section>
        <section class="generic-section generic-section--stack generic-section--plain" data-scrum-step="sizes"<?= $estimating ? '' : ' hidden' ?>>
            <h3 class="generic-card-title"><?= $e(omoScrumT('selection')) ?></h3>
            <p class="generic-description"><?= $e(omoScrumT($estimating ? 'adjust_hint' : 'estimate_hint')) ?></p>
            <div data-scrum-preview></div>
            <strong data-scrum-total aria-live="polite"></strong>
            <div class="generic-form-actions"><button type="button" class="generic-action-button generic-action-button--secondary" <?= $estimating ? 'data-scrum-cancel' : 'data-scrum-previous' ?>><?= $e(omoScrumT($estimating ? 'cancel' : 'previous')) ?></button><button type="button" class="generic-action-button generic-action-button--main" data-scrum-import><?= $e(omoScrumT($estimating ? 'save_action' : 'import')) ?></button></div>
        </section>
    <?php elseif (!$scrumSprint): ?>
        <?php $sprints = ScrumSprint::forContext($organizationId, (int)$context['currentHolon']->getId(), $archived); ?>
        <?php if (!$sprints): ?><p class="omo-empty-state"><?= $e(omoScrumT('empty')) ?></p><?php endif; ?>
        <?php foreach ($sprints as $item): ?>
            <?php $itemSamples = ScrumSample::forSprint((int)$item->getId()); $itemRemaining = $itemSamples && !$item->editable() ? (int)$itemSamples[count($itemSamples) - 1]['remaining'] : ScrumSprint::calculate($item->nodes())['remaining']; ?>
            <article class="generic-section scrum-summary" data-scrum-card="<?= $e(omoScrumUrl($context, ['id' => (int)$item->getId()])) ?>">
                <div class="generic-form-stack">
                    <h3 class="generic-card-title"><a data-scrum-open href="<?= $e(omoScrumUrl($context, ['id' => (int)$item->getId()])) ?>"><?= $e($item->get('title')) ?></a></h3>
                    <p class="generic-meta"><?= $e(omoScrumDates($item)) ?> · <?= $e(omoScrumT($item->get('state'))) ?></p>
                    <p class="generic-description scrum-objective"><?= $e($item->get('objective')) ?></p>
                    <strong><?= $e(omoScrumT('remaining')) ?> : <?= $e(omoScrumPercent($itemRemaining, (int)$item->get('baseline'))) ?></strong>
                </div>
                <?= omoScrumChart($item, $itemSamples) ?>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <?php
        $allNodes = $scrumSprint->nodes(); $samples = ScrumSample::forSprint($sprintId);
        $remaining = $samples && !$scrumSprint->editable() ? (int)$samples[count($samples) - 1]['remaining'] : ScrumSprint::calculate($allNodes)['remaining'];
        $nodes = $visibleNodes;
        $team = [];
        foreach ($nodes as $node) { if ($node['user'] > 0) { $user = new User(); if ($user->load($node['user'])) { $team[$node['user']] = omoProjectsGetUserLabel($user); } } }
        ?>
        <section class="generic-soft-panel scrum-summary" data-scrum-summary>
            <div class="generic-form-stack">
                <p class="generic-meta"><?= $e(omoScrumDates($scrumSprint)) ?> · <?= $e(omoScrumT($scrumSprint->get('state'))) ?></p>
                <p class="generic-description scrum-objective"><?= $e($scrumSprint->get('objective')) ?></p>
                <strong><?= $e(omoScrumT('remaining')) ?> : <?= $e(omoScrumPercent($remaining, (int)$scrumSprint->get('baseline'))) ?></strong>
                <p class="generic-meta"><?= $e(omoScrumT('team')) ?> : <?= $e($team ? implode(', ', $team) : omoScrumT('no_team')) ?></p>
                <?php if ($scrumSprint->get('state') === 'stopped'): ?><p class="generic-description"><?= $e(omoScrumT('restart_hint')) ?></p><?php endif; ?>
                <?php if ($scrumSprint->get('state') === 'running'): ?><p class="generic-description"><?= $e(omoScrumT('snapshot_help')) ?></p><?php endif; ?>
                <?php if ($scrumSprint->get('state') === 'finished'): ?><p class="generic-description"><?= $e(omoScrumT('frozen')) ?></p><?php endif; ?>
            </div>
            <?= omoScrumChart($scrumSprint, $samples) ?>
        </section>
        <?php if ($scrumSprint->editable() && $canManage && $nodes): ?>
            <div class="generic-form-actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-scrum-popup="<?= $e(omoScrumUrl($context, ['id' => $sprintId, 'estimates' => 1])) ?>" data-scrum-popup-title="selection"><?= $e(omoScrumT('selection')) ?></button></div>
        <?php endif; ?>
        <?php if ($scrumSprint->get('state') !== 'finished'): ?>
            <?php require dirname(__DIR__) . '/projects/index.php'; ?>
        <?php else: ?>
            <link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/projects/projects.css') ?>">
            <div class="omo-projects omo-projects--embedded-board omo-panel-view--content-height"><div class="omo-projects__board" style="--param-kanban-columns: 5">
                <?php foreach (array_diff(Project::statuses(), [Project::STATUS_SOMEDAY]) as $status): ?>
                <section class="omo-projects__column is-mobile-active"><header class="omo-projects__column-header"><h3 class="generic-card-title"><?= $e(omoProjectsStatusLabel($status)) ?></h3></header>
                    <div class="omo-projects__column-cards">
                    <?php foreach ($nodes as $node): if ($node['status'] !== $status) { continue; } ?>
                    <article class="omo-project-card omo-project-card--<?= $e($status) ?> generic-section generic-section--stack">
                        <h4 class="omo-project-card__title generic-title generic-title--item"><?= $e($node['title']) ?></h4>
                        <span class="generic-meta"><?= $e($node['size']) ?> · <?= $e(omoScrumT('own')) ?> : <?= (int)$node['own'] ?></span>
                    </article>
                    <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>
            </div></div>
        <?php endif; ?>
    <?php endif; ?>
    </div></div>
    <?php if ($rootId === 'omo-scrum-root'): ?>
    <div class="omo-overlay-drawer" data-scrum-drawer hidden>
        <div class="omo-overlay-drawer__backdrop" data-scrum-close></div>
        <div class="omo-overlay-drawer__panel" role="dialog" aria-modal="true" aria-label="<?= $e(omoScrumT('title')) ?>">
            <div class="omo-overlay-drawer__body" data-scrum-drawer-body></div>
        </div>
    </div>
    <?php endif; ?>
</div>
<script src="<?= commonAssetUrl('/common/holon_scope_picker.js') ?>"></script>
<script src="<?= commonAssetUrl('/common/project-picker/project-picker.js') ?>"></script>
<script src="<?= commonAssetUrl('/common/drawer/subdrawer.js') ?>"></script>
<script src="<?= commonAssetUrl('/omo/api/scrum/scrum.js') ?>"></script>
