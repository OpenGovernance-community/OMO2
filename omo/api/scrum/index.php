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
    $canManage = omoProjectsCanManageContext($context);
    $editing = isset($_GET['edit']); $importing = isset($_GET['import']); $archived = !empty($_GET['archives']);
    if (($editing || $importing) && (!$canManage || ($scrumSprint && !$scrumSprint->editable()))) { throw new RuntimeException('access'); }
    if ($importing && !$scrumSprint) { throw new RuntimeException('missing'); }
    $texts = [];
    foreach (array_keys(omoScrumSourceLang()) as $key) { $texts[$key] = omoScrumT($key); }
    $csrf = commonCsrfToken();
} catch (Throwable $error) {
    http_response_code(403); echo omoApiEscape(omoScrumT(isset(omoScrumSourceLang()[$error->getMessage()]) ? $error->getMessage() : 'save')); return;
}
$e = static fn ($value) => omoApiEscape($value);
$button = static function (string $action, string $label, int $projectId = 0) use ($e) {
    echo '<button type="button" class="generic-action-button generic-action-button--secondary" data-scrum-action="' . $e($action) . '" data-project-id="' . $projectId . '">' . $e(omoScrumT($label)) . '</button>';
};
?>
<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/scrum/scrum.css') ?>">
<div id="omo-scrum-root" class="omo-panel-view" data-id="<?= $sprintId ?>" data-csrf="<?= $e($csrf) ?>"
    data-action-url="<?= $e(omoScrumUrl($context, [], 'action.php')) ?>" data-url="<?= $e(omoScrumUrl($context, $sprintId ? ['id' => $sprintId] : ['archives' => (int)$archived])) ?>"
    data-texts="<?= $e(json_encode($texts, JSON_UNESCAPED_UNICODE)) ?>">
    <header class="omo-panel-view__header omo-panel-view__header--stacked">
        <div class="omo-panel-view__header-main">
            <h2 class="omo-panel-view__title"><?= $e($scrumSprint ? $scrumSprint->get('title') : omoScrumT('title')) ?></h2>
            <div class="generic-form-actions">
                <?php if ($sprintId || $editing || $archived): ?><a class="generic-action-button generic-action-button--secondary" data-scrum-link href="<?= $e(omoScrumUrl($context)) ?>"><?= $e(omoScrumT('back')) ?></a><?php endif; ?>
                <?php if (!$sprintId && !$editing): ?>
                    <?php if ($canManage): ?><a class="generic-action-button generic-action-button--main" data-scrum-link href="<?= $e(omoScrumUrl($context, ['edit' => 1])) ?>"><?= $e(omoScrumT('new')) ?></a><?php endif; ?>
                    <?php if (!$archived): ?><a class="generic-action-button generic-action-button--secondary" data-scrum-link href="<?= $e(omoScrumUrl($context, ['archives' => 1])) ?>"><?= $e(omoScrumT('archives')) ?></a><?php endif; ?>
                <?php elseif ($scrumSprint && !$editing && !$importing && $canManage): ?>
                    <?php if ($scrumSprint->editable()): ?>
                        <a class="generic-action-button generic-action-button--main" data-scrum-link href="<?= $e(omoScrumUrl($context, ['id' => $sprintId, 'import' => 1])) ?>"><?= $e(omoScrumT('add')) ?></a>
                        <a class="generic-action-button generic-action-button--secondary" data-scrum-link href="<?= $e(omoScrumUrl($context, ['id' => $sprintId, 'edit' => 1])) ?>"><?= $e(omoScrumT('edit')) ?></a>
                        <?php if ($scrumSprint->get('state') === 'stopped'): $button('resume', 'resume'); endif; ?>
                    <?php endif; ?>
                    <?php if (in_array($scrumSprint->get('state'), ['running', 'scheduled'], true)): $button('stop', 'stop'); endif; ?>
                    <?php if ($scrumSprint->get('state') === 'finished'): $button($scrumSprint->get('archived') ? 'restore' : 'archive', $scrumSprint->get('archived') ? 'restore' : 'archive'); endif; ?>
                    <?php if ($scrumSprint->get('state') !== 'running'): $button('delete', 'delete'); endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <div class="omo-panel-view__body"><div class="omo-panel-view__body_content generic-form-stack">
    <?php if ($editing): ?>
        <?php
        $editor = $scrumSprint ?: new ScrumSprint();
        if (!$sprintId) { $editor->set('start_date', ScrumSprint::now()); $editor->set('end_date', ScrumSprint::now()->modify('+13 days')); }
        $editor->display('adminEdit.php', ['buttons' => false, 'fields' => ['title', ['start_date', 'end_date'], 'objective'], 'action' => omoScrumUrl($context, [], 'action.php')]);
        ?>
        <div class="generic-form-actions"><button type="submit" form="formulaire-edit" class="generic-action-button generic-action-button--main"><?= $e(omoScrumT('save_action')) ?></button></div>
    <?php elseif ($importing): ?>
        <?php
        $projects = new ArrayProject(); $projects->loadForOrganization($organizationId, false, Project::KIND_STANDARD, true);
        $pickerNodes = [];
        foreach ($projects as $project) {
            if (!omoProjectsCanViewProject($project, $context) || $project->isPrivateProposal()) { continue; }
            $node = ScrumSprint::projectData($project); $node['active'] = (int)$project->get('active');
            $node['editable'] = omoProjectsCanManageProject($project, $context);
            $node['holonId'] = $node['holon'] ?: (int)$context['rootHolon']->getId();
            $pickerNodes[] = $node;
        }
        ?>
        <section class="generic-section generic-section--stack" data-scrum-picker data-nodes="<?= $e(json_encode($pickerNodes, JSON_UNESCAPED_UNICODE)) ?>" data-existing="<?= $e(json_encode(array_keys($scrumSprint->nodes()))) ?>" data-oid="<?= $organizationId ?>" data-cid="<?= (int)$context['currentHolon']->getId() ?>">
            <p class="generic-description"><?= $e(omoScrumT('import_hint')) ?></p>
            <div data-scrum-scope></div>
            <label class="generic-form-field"><span class="generic-form-label"><?= $e(omoScrumT('search')) ?></span><input class="generic-form-control" type="search" data-scrum-search></label>
            <select class="generic-form-control" size="8" multiple data-scrum-select aria-label="<?= $e(omoScrumT('select')) ?>"></select>
        </section>
        <section class="generic-section generic-section--stack">
            <h3 class="generic-card-title"><?= $e(omoScrumT('selection')) ?></h3>
            <p class="generic-description"><?= $e(omoScrumT('estimate_hint')) ?></p>
            <div data-scrum-preview></div>
            <strong data-scrum-total aria-live="polite"></strong>
            <button type="button" class="generic-action-button generic-action-button--main" data-scrum-import><?= $e(omoScrumT('import')) ?></button>
        </section>
    <?php elseif (!$scrumSprint): ?>
        <?php $sprints = ScrumSprint::forContext($organizationId, (int)$context['currentHolon']->getId(), $archived); ?>
        <?php if (!$sprints): ?><p class="omo-empty-state"><?= $e(omoScrumT('empty')) ?></p><?php endif; ?>
        <?php foreach ($sprints as $item): ?>
            <?php $itemSamples = ScrumSample::forSprint((int)$item->getId()); $itemRemaining = $itemSamples && !$item->editable() ? (int)$itemSamples[count($itemSamples) - 1]['remaining'] : ScrumSprint::calculate($item->nodes())['remaining']; ?>
            <article class="generic-section scrum-summary" data-scrum-card="<?= $e(omoScrumUrl($context, ['id' => (int)$item->getId()])) ?>">
                <div class="generic-form-stack">
                    <h3 class="generic-card-title"><a data-scrum-link href="<?= $e(omoScrumUrl($context, ['id' => (int)$item->getId()])) ?>"><?= $e($item->get('title')) ?></a></h3>
                    <p class="generic-meta"><?= $e($item->get('start_date')->format('d.m.Y')) ?> - <?= $e($item->get('end_date')->format('d.m.Y')) ?> · <?= $e(omoScrumT($item->get('state'))) ?></p>
                    <p class="generic-description scrum-objective"><?= $e($item->get('objective')) ?></p>
                    <strong><?= $e(omoScrumT('remaining')) ?> : <?= $itemRemaining ?> / <?= (int)$item->get('baseline') ?> <?= $e(omoScrumT('points')) ?></strong>
                </div>
                <?= omoScrumChart($item, $itemSamples) ?>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <?php
        $nodes = $scrumSprint->nodes(); $samples = ScrumSample::forSprint($sprintId);
        $remaining = $samples && !$scrumSprint->editable() ? (int)$samples[count($samples) - 1]['remaining'] : ScrumSprint::calculate($nodes)['remaining'];
        $team = [];
        foreach ($nodes as $node) { if ($node['user'] > 0) { $user = new User(); if ($user->load($node['user'])) { $team[$node['user']] = omoProjectsGetUserLabel($user); } } }
        ?>
        <section class="generic-soft-panel scrum-summary">
            <div class="generic-form-stack">
                <p class="generic-meta"><?= $e($scrumSprint->get('start_date')->format('d.m.Y')) ?> - <?= $e($scrumSprint->get('end_date')->format('d.m.Y')) ?> · <?= $e(omoScrumT($scrumSprint->get('state'))) ?></p>
                <p class="generic-description scrum-objective"><?= $e($scrumSprint->get('objective')) ?></p>
                <strong><?= $e(omoScrumT('remaining')) ?> : <?= $remaining ?> / <?= (int)$scrumSprint->get('baseline') ?> <?= $e(omoScrumT('points')) ?></strong>
                <p class="generic-meta"><?= $e(omoScrumT('team')) ?> : <?= $e($team ? implode(', ', $team) : omoScrumT('no_team')) ?></p>
                <?php if ($scrumSprint->get('state') === 'stopped'): ?><p class="generic-description"><?= $e(omoScrumT('restart_hint')) ?></p><?php endif; ?>
                <?php if ($scrumSprint->get('state') === 'running'): ?><p class="generic-description"><?= $e(omoScrumT('snapshot_help')) ?></p><?php endif; ?>
                <?php if ($scrumSprint->get('state') === 'finished'): ?><p class="generic-description"><?= $e(omoScrumT('frozen')) ?></p><?php endif; ?>
            </div>
            <?= omoScrumChart($scrumSprint, $samples) ?>
        </section>
        <?php if ($samples): ?>
            <details class="generic-accordion"><summary><?= $e(omoScrumT('history')) ?></summary>
                <p class="generic-help-text"><?= $e(omoScrumT('measured_at')) ?></p>
                <ol><?php foreach ($samples as $sample): ?><li><time><?= $e($sample['sampled_at']) ?></time> : <?= (int)$sample['remaining'] ?> <?= $e(omoScrumT('points')) ?></li><?php endforeach; ?></ol>
            </details>
        <?php endif; ?>
        <?php if ($scrumSprint->editable() && $canManage && $nodes): ?>
            <details class="generic-accordion"><summary><?= $e(omoScrumT('selection')) ?></summary><div class="generic-form-stack">
            <?php foreach ($nodes as $id => $node): if (isset($nodes[$node['parent']])) { continue; } ?>
                <div class="generic-form-actions"><span><?= $e($node['title']) ?></span><?php $button('remove', 'remove', $id); ?></div>
            <?php endforeach; ?>
            </div></details>
        <?php endif; ?>
        <?php if ($scrumSprint->get('state') !== 'finished'): ?>
            <?php require dirname(__DIR__) . '/projects/index.php'; ?>
        <?php else: ?>
            <link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/projects/projects.css') ?>">
            <div class="omo-projects"><div class="omo-projects__board" style="--param-kanban-columns: 5">
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
</div>
<script src="<?= commonAssetUrl('/common/holon_scope_picker.js') ?>"></script>
<script src="<?= commonAssetUrl('/common/project-picker/project-picker.js') ?>"></script>
<script src="<?= commonAssetUrl('/omo/api/scrum/scrum.js') ?>"></script>
