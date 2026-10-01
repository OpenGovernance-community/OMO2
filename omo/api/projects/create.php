<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\ArrayProject;
use dbObject\ArrayUserOrganization;
use dbObject\DocumentPvPoint;
use dbObject\Holon;
use dbObject\Project;

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;
$projectId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$context = omoProjectsResolveContext($organizationId, $currentHolonId);

if (empty($context['status'])) {
    http_response_code(403);
    echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.forbidden')) . '</div>';
    exit;
}

$projectDisplayConfig = omoProjectsGetDisplayConfig($organizationId);
$enabledStatuses = $projectDisplayConfig['enabledStatuses'];
$usesPriority = !empty($projectDisplayConfig['usePriority']);
$usesImportance = !empty($projectDisplayConfig['useImportance']);
$usesSize = !empty($projectDisplayConfig['useSize']);

$isEdit = $projectId > 0;
$canCreateProject = omoProjectsCanCreateContext($context);
$canProposeProject = omoProjectsCanProposeContext($context);
$requestedParentId = isset($_GET['parent_id']) && is_numeric($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;
$canCreateSubprojectFromParent = false;
$project = new Project();
if ($isEdit) {
    if (
        !$project->load($projectId)
        || (int)$project->get('IDorganization') !== $organizationId
        || (int)$project->get('active') !== 1
        || !omoProjectsCanManageProject($project, $context)
    ) {
        http_response_code(403);
        echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.forbidden')) . '</div>';
        exit;
    }
} else {
    if ($requestedParentId > 0) {
        $requestedParent = new Project();
        $canCreateSubprojectFromParent = $requestedParent->load($requestedParentId)
            && (int)$requestedParent->get('IDorganization') === $organizationId
            && (int)$requestedParent->get('active') === 1
            && Project::normalizeKind($requestedParent->get('project_kind')) === Project::KIND_STANDARD
            && omoProjectsCanManageProject($requestedParent, $context);
    }

    if (!$canCreateProject && !$canProposeProject && !$canCreateSubprojectFromParent) {
        http_response_code(403);
        echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.forbidden')) . '</div>';
        exit;
    }

    $project->set('IDorganization', $organizationId);
    $project->set('IDholon', $context['currentHolon'] instanceof Holon ? (int)$context['currentHolon']->getId() : null);
    $project->set('IDuser', null);
    $requestedStatus = isset($_GET['status']) ? (string)$_GET['status'] : Project::STATUS_IN_PROGRESS;
    $requestedStatus = Project::normalizeStatus($requestedStatus);
    $project->set('status', in_array($requestedStatus, $enabledStatuses, true) ? $requestedStatus : $enabledStatuses[0]);
    if (Project::normalizeStatus($project->get('status')) === Project::STATUS_IN_PROGRESS) {
        $project->set('planned_start_date', (new \DateTimeImmutable('today'))->format('Y-m-d'));
    }
    $project->set('capture_mode', Project::CAPTURE_MULTIPLE_DOCUMENTS);
    $project->set('project_size', Project::SIZE_M);

    if ($requestedParentId > 0) {
        $requestedParent = new Project();
        if (
            $requestedParent->load($requestedParentId)
            && (int)$requestedParent->get('IDorganization') === $organizationId
            && (int)$requestedParent->get('active') === 1
            && Project::normalizeKind($requestedParent->get('project_kind')) === Project::KIND_STANDARD
            && $project->canUseAsParent($requestedParent)
        ) {
            $project->set('IDproject_parent', $requestedParentId);
        }
    }
}

$isProposalForm = !$isEdit && !$canCreateProject && $canProposeProject;

$formatDateValue = static function ($value): string {
    if ($value instanceof \DateTimeInterface) {
        return $value->format('Y-m-d');
    }

    $value = trim((string)$value);
    return preg_match('/^\d{4}-\d{2}-\d{2}/', $value) === 1 ? substr($value, 0, 10) : '';
};

$organizationMembers = new ArrayUserOrganization();
$organizationMembers->loadActiveForOrganization($organizationId);
$responsibleOptions = [];
foreach ($organizationMembers as $membership) {
    $userId = (int)$membership->get('IDuser');
    if ($userId <= 0) {
        continue;
    }

    $responsibleOptions[] = [
        'id' => $userId,
        'label' => DocumentPvPoint::getUserDisplayNameForOrganization($userId, $organizationId),
    ];
}

$allProjects = new ArrayProject();
$allProjects->loadForOrganization($organizationId);
$parentProjects = [];
foreach ($allProjects as $candidate) {
    if (!($candidate instanceof Project) || !omoProjectsCanViewProject($candidate, $context) || $candidate->isPendingProposal() || !$project->canUseAsParent($candidate)) {
        continue;
    }

    $candidateHolon = $candidate->getHolon();
    $parentProjects[] = [
        'id' => (int)$candidate->getId(),
        'title' => trim((string)$candidate->get('title')),
        'holonId' => $candidateHolon instanceof Holon ? (int)$candidateHolon->getId() : 0,
        'context' => $candidateHolon instanceof Holon
            ? trim((string)$candidateHolon->getDisplayName())
            : trim((string)$context['organization']->get('name')),
    ];
}
usort($parentProjects, static function (array $left, array $right): int {
    return strcasecmp((string)$left['title'], (string)$right['title']);
});

$parentId = (int)$project->get('IDproject_parent');
$parentTitle = '';
foreach ($parentProjects as $parentProject) {
    if ((int)$parentProject['id'] === $parentId) {
        $parentTitle = (string)$parentProject['title'];
        break;
    }
}

$statuses = [];
foreach (omoProjectsStatusDisplayOrder() as $status) {
    if (in_array($status, $enabledStatuses, true)) {
        $statuses[$status] = ['label' => omoProjectsStatusLabel($status, $organizationId)];
    }
}
$selectedStatus = Project::normalizeStatus($project->get('status'));
$statusIsConfigurable = in_array($selectedStatus, $enabledStatuses, true);
$selectedBlockedUntil = $formatDateValue($project->get('blocked_until'));
$selectedBlockedAutoReactivate = (int)$project->get('blocked_auto_reactivate') === 1;
$selectedBlockedReactivateStatus = Project::normalizeBlockedReactivateStatus($project->get('blocked_reactivate_status'));
$selectedPriority = Project::normalizeLevel($project->get('priority')) ?? 0;
$selectedImportance = Project::normalizeLevel($project->get('importance')) ?? 0;
$selectedSize = Project::normalizeSize($project->get('project_size'));
$selectedCaptureMode = Project::normalizeCaptureMode($project->get('capture_mode'));
$parentPickerInitialHolonId = (int)$project->get('IDholon');
if ($parentPickerInitialHolonId <= 0 && $context['currentHolon'] instanceof Holon) {
    $parentPickerInitialHolonId = (int)$context['currentHolon']->getId();
}
$assignedHolon = $project->getHolon();
$assignedHolonId = $assignedHolon instanceof Holon
    ? (int)$assignedHolon->getId()
    : ($isEdit ? 0 : $parentPickerInitialHolonId);
$assignedHolonLabel = $assignedHolon instanceof Holon
    ? trim((string)$assignedHolon->getDisplayName())
    : trim((string)$context['organization']->get('name'));
$formId = 'omo-project-form';
$formTexts = [
    'parentPickerTitle' => omoProjectsT('projects.parent_picker.title'),
    'parentPickerSearch' => omoProjectsT('projects.parent_picker.search'),
    'parentPickerEmpty' => omoProjectsT('projects.parent_picker.empty'),
    'parentPickerNone' => omoProjectsT('projects.parent_picker.none'),
    'parentPickerChoose' => omoProjectsT('projects.parent_picker.choose'),
    'holonPickerTitle' => omoProjectsT('projects.holon_picker.title'),
    'holonPickerHint' => omoProjectsT('projects.holon_picker.hint'),
    'holonPickerConfirm' => omoProjectsT('projects.holon_picker.confirm'),
    'parentPickerScope' => [
        'local' => omoProjectsT('projects.parent_picker.scope_local'),
        'children' => omoProjectsT('projects.parent_picker.scope_children'),
        'descendants' => omoProjectsT('projects.parent_picker.scope_descendants'),
    ],
    'cancel' => omoProjectsT('projects.action.cancel'),
    'dateError' => omoProjectsT('projects.error.dates'),
    'saveError' => omoProjectsT('projects.error.save'),
];
?>
<div class="omo-project-form generic-drawer-content" data-omo-project-form-root>
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape(omoProjectsT($isEdit ? 'projects.form.edit_title' : 'projects.form.title')) ?>"
        data-omo-subdrawer-description=""
    >
        <button type="submit" form="<?= $formId ?>" class="generic-action-button generic-action-button--main" data-omo-subdrawer-action data-omo-project-form-submit><?= omoApiEscape(omoProjectsT($isEdit ? 'projects.form.edit_submit' : ($isProposalForm ? 'projects.action.propose' : 'projects.form.submit'))) ?></button>
        <button type="button" form="<?= $formId ?>" class="generic-action-button generic-action-button--secondary" data-omo-subdrawer-action data-omo-projects-cancel-create><?= omoApiEscape(omoProjectsT('projects.action.cancel')) ?></button>
    </div>

    <form id="<?= $formId ?>" class="omo-project-form__fields generic-form-stack generic-form-stack--compact" action="/omo/api/projects/action.php" method="post" novalidate>
        <input type="hidden" name="project_action" value="save_project">
        <?php if (omoProjectsPvMeetingQuery($organizationId) !== ''): ?>
            <input type="hidden" name="pv_meeting_document_id" value="<?= (int)($_GET['pv_meeting_document_id'] ?? 0) ?>">
            <input type="hidden" name="pv_meeting_editor_token" value="<?= omoApiEscape((string)($_GET['pv_meeting_editor_token'] ?? '')) ?>">
        <?php endif; ?>
        <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
        <input type="hidden" name="cid" value="<?= (int)$currentHolonId ?>">
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$projectId ?>"><?php endif; ?>

        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact omo-project-form__section">
            <div class="omo-project-form__field generic-form-field">
                <label class="generic-form-label" for="omo-project-title"><?= omoApiEscape(omoProjectsT('projects.field.title')) ?></label>
                <input id="omo-project-title" class="generic-form-control generic-form-control--compact" type="text" name="title" maxlength="255" required autofocus value="<?= omoApiEscape((string)$project->get('title')) ?>" autocomplete="off">
            </div>
            <div class="omo-project-form__field generic-form-field">
                <label class="generic-form-label" for="omo-project-description"><?= omoApiEscape(omoProjectsT('projects.field.description')) ?></label>
                <textarea id="omo-project-description" class="generic-form-control generic-form-control--compact" name="description" rows="2" placeholder="<?= omoApiEscape(omoProjectsT('projects.field.description_placeholder')) ?>"><?= omoApiEscape((string)$project->get('description')) ?></textarea>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact omo-project-form__section">
            <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoProjectsT('projects.form.assignment')) ?></h3>
            <div class="omo-project-form__grid omo-project-form__grid--assignment generic-form-grid">
                <?php if (($context['rootHolon'] ?? null) instanceof Holon): ?>
                <div class="omo-project-form__field generic-form-field">
                    <label class="generic-form-label" for="omo-project-holon-label"><?= omoApiEscape(omoProjectsT('projects.field.holon')) ?></label>
                    <div class="omo-project-form__parent-control">
                        <input id="omo-project-holon-label" class="generic-form-control" type="text" value="<?= omoApiEscape($assignedHolonLabel) ?>" readonly>
                        <input type="hidden" name="IDholon" value="<?= $assignedHolonId > 0 ? $assignedHolonId : '' ?>" data-omo-project-holon-id>
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-project-holon-picker><?= omoApiEscape(omoProjectsT('projects.holon.choose')) ?></button>
                    </div>
                </div>
                <?php endif; ?>
                <div class="omo-project-form__field generic-form-field">
                    <span class="generic-inline-help"><label class="generic-form-label" for="omo-project-responsible"><?= omoApiEscape(omoProjectsT('projects.field.responsible')) ?></label><details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover><summary aria-label="<?= omoApiEscape(omoProjectsT('projects.responsible.help')) ?>">?</summary><div class="generic-context-help__content"><?= omoApiEscape(omoProjectsT('projects.responsible.help')) ?></div></details></span>
                    <select id="omo-project-responsible" class="generic-form-control" name="IDuser">
                        <option value=""><?= omoApiEscape(omoProjectsT('projects.responsible.none')) ?></option>
                        <?php foreach ($responsibleOptions as $responsible): ?>
                            <option value="<?= (int)$responsible['id'] ?>"<?= (int)$project->get('IDuser') === (int)$responsible['id'] ? ' selected' : '' ?>><?= omoApiEscape($responsible['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="omo-project-form__field generic-form-field">
                    <label class="generic-form-label" for="omo-project-parent-label"><?= omoApiEscape(omoProjectsT('projects.field.parent')) ?></label>
                    <div class="omo-project-form__parent-control">
                        <input id="omo-project-parent-label" class="generic-form-control" type="text" value="<?= omoApiEscape($parentTitle) ?>" placeholder="<?= omoApiEscape(omoProjectsT('projects.parent.none')) ?>" readonly>
                        <input type="hidden" name="IDproject_parent" value="<?= $parentId > 0 ? $parentId : '' ?>" data-omo-project-parent-id>
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-project-parent-picker><?= omoApiEscape(omoProjectsT('projects.parent.choose')) ?></button>
                    </div>
                </div>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact omo-project-form__section">
            <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoProjectsT('projects.form.planning')) ?></h3>
            <div class="omo-project-form__grid generic-form-grid">
                <?php if ($statusIsConfigurable): ?>
                    <div class="omo-project-form__field generic-form-field">
                        <label class="generic-form-label" for="omo-project-status"><?= omoApiEscape(omoProjectsT('projects.field.status')) ?></label>
                        <select id="omo-project-status" class="generic-form-control" name="status">
                            <?php foreach ($statuses as $status => $catalog): ?>
                                <option value="<?= omoApiEscape($status) ?>"<?= $status === $selectedStatus ? ' selected' : '' ?>><?= omoApiEscape(omoProjectsStatusLabel($status)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="status" value="<?= omoApiEscape($selectedStatus) ?>">
                <?php endif; ?>
                <?php if ($usesSize): ?>
                    <div class="omo-project-form__field generic-form-field">
                        <label class="generic-form-label" for="omo-project-size"><?= omoApiEscape(omoProjectsT('projects.field.size')) ?></label>
                        <select id="omo-project-size" class="generic-form-control" name="project_size">
                            <?php foreach (Project::sizes() as $size): ?>
                                <option value="<?= omoApiEscape($size) ?>"<?= $size === $selectedSize ? ' selected' : '' ?>><?= omoApiEscape($size) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="project_size" value="<?= omoApiEscape($selectedSize) ?>">
                <?php endif; ?>
                <div class="omo-project-form__field generic-form-field">
                    <label class="generic-form-label" for="omo-project-start"><?= omoApiEscape(omoProjectsT('projects.field.start_date')) ?></label>
                    <input id="omo-project-start" class="generic-form-control" type="date" name="planned_start_date" value="<?= omoApiEscape($formatDateValue($project->get('planned_start_date'))) ?>" data-omo-project-start-date>
                </div>
                <div class="omo-project-form__field generic-form-field">
                    <label class="generic-form-label" for="omo-project-end"><?= omoApiEscape(omoProjectsT('projects.field.end_date')) ?></label>
                    <input id="omo-project-end" class="generic-form-control" type="date" name="planned_end_date" value="<?= omoApiEscape($formatDateValue($project->get('planned_end_date'))) ?>" data-omo-project-end-date>
                </div>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact omo-project-form__section omo-project-form__blocked-section" data-omo-project-blocked-section<?= $selectedStatus === Project::STATUS_BLOCKED ? '' : ' hidden' ?>>
            <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoProjectsT('projects.form.blocked')) ?></h3>
            <div class="omo-project-form__grid generic-form-grid">
                <div class="omo-project-form__field generic-form-field">
                    <label class="generic-form-label" for="omo-project-blocked-reason"><?= omoApiEscape(omoProjectsT('projects.blocked.reason')) ?></label>
                    <textarea id="omo-project-blocked-reason" class="generic-form-control" name="blocked_reason" rows="3" maxlength="4000" data-omo-project-blocked-reason><?= omoApiEscape((string)$project->get('blocked_reason')) ?></textarea>
                </div>
                <div class="omo-project-form__field generic-form-field">
                    <label class="generic-form-label" for="omo-project-blocked-until"><?= omoApiEscape(omoProjectsT('projects.blocked.until')) ?></label>
                    <input id="omo-project-blocked-until" class="generic-form-control" type="date" name="blocked_until" value="<?= omoApiEscape($selectedBlockedUntil) ?>" data-omo-project-blocked-until>
                </div>
                <div class="omo-project-form__field generic-form-field omo-project-form__blocked-auto-field">
                    <label class="generic-form-checkbox" for="omo-project-blocked-auto">
                        <input id="omo-project-blocked-auto" type="checkbox" name="blocked_auto_reactivate" value="1"<?= $selectedBlockedAutoReactivate ? ' checked' : '' ?> data-omo-project-blocked-auto>
                        <span><?= omoApiEscape(omoProjectsT('projects.blocked.auto_reactivate')) ?></span>
                    </label>
                </div>
                <div class="omo-project-form__field generic-form-field" data-omo-project-blocked-target-field>
                    <label class="generic-form-label" for="omo-project-blocked-target"><?= omoApiEscape(omoProjectsT('projects.blocked.reactivate_status')) ?></label>
                    <select id="omo-project-blocked-target" class="generic-form-control" name="blocked_reactivate_status" data-omo-project-blocked-target>
                        <option value="ready"<?= $selectedBlockedReactivateStatus === Project::STATUS_READY ? ' selected' : '' ?>><?= omoApiEscape(omoProjectsT('projects.blocked.reactivate_ready')) ?></option>
                        <option value="in_progress"<?= $selectedBlockedReactivateStatus === Project::STATUS_IN_PROGRESS ? ' selected' : '' ?>><?= omoApiEscape(omoProjectsT('projects.blocked.reactivate_in_progress')) ?></option>
                    </select>
                </div>
            </div>
            <p class="generic-help-text"><?= omoApiEscape(omoProjectsT('projects.blocked.dialog.hint')) ?></p>
        </section>

        <?php if ($usesPriority || $usesImportance): ?>
        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact omo-project-form__section">
            <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoProjectsT('projects.form.attention')) ?></h3>
            <div class="omo-project-form__grid generic-form-grid">
                <?php foreach (array_filter(['priority', 'importance'], static fn (string $field): bool => $field === 'priority' ? $usesPriority : $usesImportance) as $levelField): ?>
                    <?php
                    $isPriority = $levelField === 'priority';
                    $levelValue = $isPriority ? $selectedPriority : $selectedImportance;
                    $sliderValue = $isPriority && $levelValue > 0 ? 6 - $levelValue : $levelValue;
                    ?>
                    <div class="omo-project-form__field generic-form-field omo-project-form__level-field">
                        <div class="omo-project-form__label-row">
                            <label class="generic-form-label" for="omo-project-<?= $levelField ?>"><?= omoApiEscape(omoProjectsT('projects.field.' . $levelField)) ?></label>
                            <output for="omo-project-<?= $levelField ?>" data-omo-project-level-output><?= $levelValue > 0 ? ($isPriority ? 'P' . $levelValue : $levelValue . '/5') : omoApiEscape(omoProjectsT('projects.level.none')) ?></output>
                        </div>
                        <?php if ($isPriority): ?><input type="hidden" name="priority" value="<?= $levelValue ?>" data-omo-project-priority-value><?php endif; ?>
                        <input id="omo-project-<?= $levelField ?>" class="omo-project-form__level-input" type="range" name="<?= $isPriority ? 'priority_slider' : $levelField ?>" min="0" max="5" step="1" value="<?= $sliderValue ?>" data-omo-project-level-input data-omo-project-level-kind="<?= $isPriority ? 'priority' : 'importance' ?>">
                        <div class="omo-project-form__level-scale" aria-hidden="true"><?php if ($isPriority): ?><span>0</span><span>P5</span><span>P4</span><span>P3</span><span>P2</span><span>P1</span><?php else: ?><span>0</span><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><?php endif; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        <?php if (!$usesPriority): ?><input type="hidden" name="priority" value="<?= (int)$selectedPriority ?>"><?php endif; ?>
        <?php if (!$usesImportance): ?><input type="hidden" name="importance" value="<?= (int)$selectedImportance ?>"><?php endif; ?>

        <section class="generic-accordion generic-accordion--card generic-accordion--collapsible generic-form-section is-collapsed omo-project-form__section" data-generic-accordion>
            <div class="generic-accordion__header">
                <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoProjectsT('projects.form.more_options')) ?></h3>
                <button type="button" class="generic-accordion__toggle" data-generic-accordion-toggle aria-expanded="false" aria-label="<?= omoApiEscape(omoProjectsT('projects.form.more_options_toggle')) ?>">&#9662;</button>
            </div>
            <div class="generic-accordion__content">
                <div class="omo-project-form__grid generic-form-grid">
                    <div class="omo-project-form__field generic-form-field">
                        <label class="generic-form-label" for="omo-project-capture-mode"><?= omoApiEscape(omoProjectsT('projects.field.capture_mode')) ?></label>
                        <select id="omo-project-capture-mode" class="generic-form-control" name="capture_mode">
                            <?php foreach (Project::getCaptureModeCatalog() as $captureMode => $catalog): ?>
                                <option value="<?= omoApiEscape($captureMode) ?>"<?= $captureMode === $selectedCaptureMode ? ' selected' : '' ?>><?= omoApiEscape(omoProjectsCaptureModeLabel($captureMode)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </section>

        <p class="omo-project-form__feedback generic-feedback" data-omo-project-form-feedback hidden aria-live="polite"></p>
    </form>
</div>
<?= commonPageScriptTags('/omo/api/projects/create.js', [
    'formId' => $formId,
    'parentCandidates' => $parentProjects,
    'texts' => $formTexts,
    'projectsLevelNone' => omoApiEscape(omoProjectsT('projects.level.none')),
    'rootHolon' => ($context['rootHolon'] ?? null) instanceof Holon ? (int)$context['rootHolon']->getId() : 0,
    'rootHolon2' => (($context['rootHolon'] ?? null) instanceof Holon),
    'organizationId' => (int)$organizationId,
    'initialHolonId' => $parentPickerInitialHolonId,
    'assignedHolonId' => $assignedHolonId,
    'projectsErrorTitle' => omoApiEscape(omoProjectsT('projects.error.title')),
]) ?>
