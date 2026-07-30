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

$isEdit = $projectId > 0;
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
    if (!omoProjectsCanCreateContext($context)) {
        http_response_code(403);
        echo '<div class="omo-empty-state">' . omoApiEscape(omoProjectsT('projects.error.forbidden')) . '</div>';
        exit;
    }

    $project->set('IDorganization', $organizationId);
    $project->set('IDholon', $context['currentHolon'] instanceof Holon ? (int)$context['currentHolon']->getId() : null);
    $project->set('IDuser', null);
    $requestedStatus = isset($_GET['status']) ? (string)$_GET['status'] : Project::STATUS_SOMEDAY;
    $project->set('status', Project::normalizeStatus($requestedStatus));
    $project->set('capture_mode', Project::CAPTURE_MULTIPLE_DOCUMENTS);
    $project->set('project_size', Project::SIZE_M);

    $requestedParentId = isset($_GET['parent_id']) && is_numeric($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;
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
    if (!($candidate instanceof Project) || !$project->canUseAsParent($candidate)) {
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

$statuses = Project::getStatusCatalog();
$selectedStatus = Project::normalizeStatus($project->get('status'));
$selectedPriority = Project::normalizeLevel($project->get('priority')) ?? 0;
$selectedImportance = Project::normalizeLevel($project->get('importance')) ?? 0;
$selectedSize = Project::normalizeSize($project->get('project_size'));
$selectedCaptureMode = Project::normalizeCaptureMode($project->get('capture_mode'));
$parentPickerInitialHolonId = (int)$project->get('IDholon');
if ($parentPickerInitialHolonId <= 0 && $context['currentHolon'] instanceof Holon) {
    $parentPickerInitialHolonId = (int)$context['currentHolon']->getId();
}
$assignedHolon = $project->getHolon();
$assignedHolonId = $assignedHolon instanceof Holon ? (int)$assignedHolon->getId() : $parentPickerInitialHolonId;
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
        data-omo-subdrawer-description="<?= omoApiEscape(omoProjectsT($isEdit ? 'projects.form.edit_description' : 'projects.form.description')) ?>"
    >
        <button type="submit" form="<?= $formId ?>" class="generic-action-button generic-action-button--main" data-omo-subdrawer-action data-omo-project-form-submit><?= omoApiEscape(omoProjectsT($isEdit ? 'projects.form.edit_submit' : 'projects.form.submit')) ?></button>
        <button type="button" form="<?= $formId ?>" class="generic-action-button generic-action-button--secondary" data-omo-subdrawer-action data-omo-projects-cancel-create><?= omoApiEscape(omoProjectsT('projects.action.cancel')) ?></button>
    </div>

    <form id="<?= $formId ?>" class="omo-project-form__fields generic-form-stack" action="/omo/api/projects/action.php" method="post" novalidate>
        <input type="hidden" name="project_action" value="save_project">
        <input type="hidden" name="oid" value="<?= (int)$organizationId ?>">
        <input type="hidden" name="cid" value="<?= (int)$currentHolonId ?>">
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$projectId ?>"><?php endif; ?>

        <section class="generic-section generic-section--stack generic-form-section omo-project-form__section">
            <div class="omo-project-form__field generic-form-field">
                <label class="generic-form-label" for="omo-project-title"><?= omoApiEscape(omoProjectsT('projects.field.title')) ?></label>
                <input id="omo-project-title" class="generic-form-control" type="text" name="title" maxlength="255" required autofocus value="<?= omoApiEscape((string)$project->get('title')) ?>" autocomplete="off">
            </div>
            <div class="omo-project-form__field generic-form-field">
                <label class="generic-form-label" for="omo-project-description"><?= omoApiEscape(omoProjectsT('projects.field.description')) ?></label>
                <textarea id="omo-project-description" class="generic-form-control" name="description" rows="5" placeholder="<?= omoApiEscape(omoProjectsT('projects.field.description_placeholder')) ?>"><?= omoApiEscape((string)$project->get('description')) ?></textarea>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-form-section omo-project-form__section">
            <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoProjectsT('projects.form.assignment')) ?></h3>
            <div class="omo-project-form__grid omo-project-form__grid--assignment generic-form-grid">
                <div class="omo-project-form__field generic-form-field">
                    <label class="generic-form-label" for="omo-project-holon-label"><?= omoApiEscape(omoProjectsT('projects.field.holon')) ?></label>
                    <div class="omo-project-form__parent-control">
                        <input id="omo-project-holon-label" class="generic-form-control" type="text" value="<?= omoApiEscape($assignedHolonLabel) ?>" readonly>
                        <input type="hidden" name="IDholon" value="<?= $assignedHolonId > 0 ? $assignedHolonId : '' ?>" data-omo-project-holon-id>
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-project-holon-picker><?= omoApiEscape(omoProjectsT('projects.holon.choose')) ?></button>
                    </div>
                </div>
                <div class="omo-project-form__field generic-form-field">
                    <label class="generic-form-label" for="omo-project-responsible"><?= omoApiEscape(omoProjectsT('projects.field.responsible')) ?></label>
                    <select id="omo-project-responsible" class="generic-form-control" name="IDuser">
                        <option value=""><?= omoApiEscape(omoProjectsT('projects.responsible.none')) ?></option>
                        <?php foreach ($responsibleOptions as $responsible): ?>
                            <option value="<?= (int)$responsible['id'] ?>"<?= (int)$project->get('IDuser') === (int)$responsible['id'] ? ' selected' : '' ?>><?= omoApiEscape($responsible['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="generic-help-text"><?= omoApiEscape(omoProjectsT('projects.responsible.help')) ?></small>
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

        <section class="generic-section generic-section--stack generic-form-section omo-project-form__section">
            <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoProjectsT('projects.form.planning')) ?></h3>
            <div class="omo-project-form__grid generic-form-grid">
                <div class="omo-project-form__field generic-form-field">
                    <label class="generic-form-label" for="omo-project-status"><?= omoApiEscape(omoProjectsT('projects.field.status')) ?></label>
                    <select id="omo-project-status" class="generic-form-control" name="status">
                        <?php foreach ($statuses as $status => $catalog): ?>
                            <option value="<?= omoApiEscape($status) ?>"<?= $status === $selectedStatus ? ' selected' : '' ?>><?= omoApiEscape(omoProjectsStatusLabel($status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="omo-project-form__field generic-form-field">
                    <label class="generic-form-label" for="omo-project-size"><?= omoApiEscape(omoProjectsT('projects.field.size')) ?></label>
                    <select id="omo-project-size" class="generic-form-control" name="project_size">
                        <?php foreach (Project::sizes() as $size): ?>
                            <option value="<?= omoApiEscape($size) ?>"<?= $size === $selectedSize ? ' selected' : '' ?>><?= omoApiEscape($size) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
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

        <section class="generic-section generic-section--stack generic-form-section omo-project-form__section">
            <h3 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoProjectsT('projects.form.attention')) ?></h3>
            <div class="omo-project-form__grid generic-form-grid">
                <?php foreach (['priority', 'importance'] as $levelField): ?>
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
<script>
(function (window, document) {
    'use strict';
    var root = document.querySelector('[data-omo-project-form-root]');
    if (!root || root.dataset.omoProjectFormReady === '1') return;
    root.dataset.omoProjectFormReady = '1';
    var form = root.querySelector('#<?= $formId ?>');
    if (!(form instanceof HTMLFormElement)) return;
    var parentCandidates = <?= json_encode($parentProjects, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var texts = <?= json_encode($formTexts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var parentId = form.querySelector('[data-omo-project-parent-id]');
    var parentLabel = document.getElementById('omo-project-parent-label');
    var holonId = form.querySelector('[data-omo-project-holon-id]');
    var holonLabel = document.getElementById('omo-project-holon-label');
    var feedback = form.querySelector('[data-omo-project-form-feedback]');

    function escapeHtml(value) {
        return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function setFeedback(message) {
        if (!feedback) return;
        feedback.textContent = String(message || '');
        feedback.hidden = feedback.textContent === '';
    }

    root.querySelectorAll('[data-omo-project-level-input]').forEach(function (input) {
        var field = input.closest('.omo-project-form__level-field');
        var output = field.querySelector('[data-omo-project-level-output]');
        var kind = input.getAttribute('data-omo-project-level-kind');
        var priorityValue = field.querySelector('[data-omo-project-priority-value]');
        var updateLevel = function () {
            var value = Number(input.value || 0);
            var actualValue = kind === 'priority' && value > 0 ? 6 - value : value;
            if (priorityValue) priorityValue.value = String(actualValue);
            if (output) output.textContent = actualValue > 0
                ? (kind === 'priority' ? 'P' + String(actualValue) : String(actualValue) + '/5')
                : '<?= omoApiEscape(omoProjectsT('projects.level.none')) ?>';
            input.setAttribute('aria-valuetext', actualValue > 0
                ? (kind === 'priority' ? 'P' + String(actualValue) : String(actualValue) + '/5')
                : '<?= omoApiEscape(omoProjectsT('projects.level.none')) ?>');
        };
        input.addEventListener('input', updateLevel);
        updateLevel();
    });

    root.querySelector('[data-omo-project-parent-picker]').addEventListener('click', function () {
        if (typeof window.commonTopbarOpenModal !== 'function') return;
        var html = '<div class="omo-project-parent-picker omo-resource-picker">'
            + '<aside class="omo-resource-picker__navigation" data-omo-project-parent-scope></aside>'
            + '<div class="omo-resource-picker__content">'
            + '<label class="omo-resource-picker__quick-search"><input class="generic-form-control" type="search" data-omo-project-parent-search aria-label="' + escapeHtml(texts.parentPickerSearch) + '" placeholder="' + escapeHtml(texts.parentPickerSearch) + '"></label>'
            + '<select class="generic-form-control omo-project-parent-picker__select" size="10" data-omo-project-parent-select></select>'
            + '<p class="omo-project-parent-picker__empty generic-description generic-description--small" data-omo-project-parent-empty hidden></p>'
            + '<div class="omo-project-parent-picker__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-project-parent-cancel>' + escapeHtml(texts.cancel) + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-project-parent-confirm>' + escapeHtml(texts.parentPickerChoose) + '</button></div></div></div>';
        window.commonTopbarOpenModal(texts.parentPickerTitle, html, 'html');
        var modal = document.getElementById('commonTopbarModalBody');
        if (!(modal instanceof Element)) return;
        var search = modal.querySelector('[data-omo-project-parent-search]');
        var select = modal.querySelector('[data-omo-project-parent-select]');
        var empty = modal.querySelector('[data-omo-project-parent-empty]');
        var currentId = Number(parentId && parentId.value || 0);
        var scopePicker = null;
        var render = function () {
            var query = String(search && search.value || '').trim().toLowerCase();
            var selectedHolonId = scopePicker && typeof scopePicker.getSelectedHolonId === 'function'
                ? Number(scopePicker.getSelectedHolonId() || 0)
                : 0;
            var matches = parentCandidates.filter(function (candidate) {
                var matchesScope = !scopePicker
                    || scopePicker.matches(Number(candidate.holonId || 0))
                    || (selectedHolonId > 0 && Number(candidate.holonId || 0) === selectedHolonId)
                    || Number(candidate.id || 0) === currentId;
                return matchesScope && (query === '' || [candidate.title, candidate.context].join(' ').toLowerCase().indexOf(query) !== -1);
            });
            select.innerHTML = '<option value="0">' + escapeHtml(texts.parentPickerNone) + '</option>';
            matches.forEach(function (candidate) {
                var option = document.createElement('option');
                option.value = String(candidate.id);
                option.textContent = String(candidate.title || '') + (candidate.context ? ' - ' + String(candidate.context) : '');
                select.appendChild(option);
            });
            select.value = String(currentId);
            if (select.value !== String(currentId)) select.value = '0';
            empty.textContent = texts.parentPickerEmpty;
            empty.hidden = matches.length > 0;
        };
        modal.addEventListener('click', function (event) {
            if (event.target.closest('[data-omo-project-parent-cancel]')) {
                window.commonTopbarCloseModal();
                return;
            }
            if (!event.target.closest('[data-omo-project-parent-confirm]')) return;
            var selectedId = Number(select.value || 0);
            var selected = parentCandidates.find(function (candidate) { return Number(candidate.id) === selectedId; });
            if (parentId) parentId.value = selected ? String(selected.id) : '';
            if (parentLabel) parentLabel.value = selected ? String(selected.title || '') : '';
            window.commonTopbarCloseModal();
        });
        if (search) {
            search.addEventListener('input', render);
            search.focus();
        }
        if (typeof window.omoMountHolonScopePicker === 'function') {
            scopePicker = window.omoMountHolonScopePicker({
                host: modal.querySelector('[data-omo-project-parent-scope]'),
                organizationId: <?= (int)$organizationId ?>,
                initialHolonId: <?= $parentPickerInitialHolonId ?>,
                labels: texts.parentPickerScope || {},
                onChange: render
            });
        }
        render();
    });

    root.querySelector('[data-omo-project-holon-picker]').addEventListener('click', function () {
        if (typeof window.commonTopbarOpenModal !== 'function' || typeof window.omoMountHolonScopePicker !== 'function') return;
        var html = '<div class="omo-project-holon-picker">'
            + '<p class="omo-project-move-dialog__hint generic-help-text">' + escapeHtml(texts.holonPickerHint) + '</p>'
            + '<div data-omo-project-holon-scope></div>'
            + '<div class="omo-project-parent-picker__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-omo-project-holon-cancel>' + escapeHtml(texts.cancel) + '</button><button type="button" class="generic-action-button generic-action-button--main" data-omo-project-holon-confirm>' + escapeHtml(texts.holonPickerConfirm) + '</button></div></div>';
        window.commonTopbarOpenModal(texts.holonPickerTitle, html, 'html');
        var modal = document.getElementById('commonTopbarModalBody');
        var dialog = modal ? modal.querySelector('.omo-project-holon-picker') : null;
        if (!(dialog instanceof Element)) return;
        var selectedHolonId = Number(holonId && holonId.value || <?= $assignedHolonId ?>);
        var selectedHolonLabel = holonLabel ? String(holonLabel.value || '') : '';
        var picker = window.omoMountHolonScopePicker({
            host: dialog.querySelector('[data-omo-project-holon-scope]'),
            organizationId: <?= (int)$organizationId ?>,
            initialHolonId: selectedHolonId,
            showModes: false,
            onChange: function (nextHolonId) {
                selectedHolonId = Number(nextHolonId || 0);
                if (picker && typeof picker.getSelectedHolonLabel === 'function') {
                    selectedHolonLabel = picker.getSelectedHolonLabel() || selectedHolonLabel;
                }
            }
        });
        dialog.addEventListener('click', function (event) {
            if (event.target.closest('[data-omo-project-holon-cancel]')) {
                window.commonTopbarCloseModal();
                return;
            }
            if (!event.target.closest('[data-omo-project-holon-confirm]')) return;
            if (holonId) holonId.value = selectedHolonId > 0 ? String(selectedHolonId) : '';
            if (holonLabel && selectedHolonLabel !== '') holonLabel.value = selectedHolonLabel;
            window.commonTopbarCloseModal();
        });
    });

    var startDate = form.querySelector('[data-omo-project-start-date]');
    var endDate = form.querySelector('[data-omo-project-end-date]');
    var syncDateConstraint = function () {
        if (!(startDate instanceof HTMLInputElement) || !(endDate instanceof HTMLInputElement)) return;
        endDate.min = String(startDate.value || '');
        endDate.setCustomValidity(startDate.value && endDate.value && endDate.value < startDate.value ? texts.dateError : '');
    };
    if (startDate) startDate.addEventListener('change', syncDateConstraint);
    if (endDate) endDate.addEventListener('change', syncDateConstraint);
    syncDateConstraint();

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        setFeedback('');
        if (!form.reportValidity()) return;
        var title = form.elements.namedItem('title');
        if (title && String(title.value || '').trim() === '') {
            title.setCustomValidity('<?= omoApiEscape(omoProjectsT('projects.error.title')) ?>');
            form.reportValidity();
            title.setCustomValidity('');
            return;
        }
        var submitters = document.querySelectorAll('[data-omo-project-form-submit], button[type="submit"][form="' + form.id + '"]');
        submitters.forEach(function (button) { button.disabled = true; });
        fetch(form.action, {method: 'POST', credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}, body: new FormData(form)})
            .then(function (response) { return response.json().then(function (payload) { return {ok: response.ok, payload: payload}; }); })
            .then(function (result) {
                if (!result.ok || !result.payload || !result.payload.success) throw new Error(result.payload && result.payload.message ? result.payload.message : texts.saveError);
                return typeof window.omoProjectsAfterSave === 'function' ? window.omoProjectsAfterSave() : null;
            })
            .catch(function (error) { setFeedback(error && error.message ? error.message : texts.saveError); submitters.forEach(function (button) { button.disabled = false; }); });
    });
})(window, document);
</script>
