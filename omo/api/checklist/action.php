<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/shared.php';

use dbObject\Checklist;
use dbObject\ChecklistItem;
use dbObject\ChecklistItemDependency;
use dbObject\ChecklistItemRecurrence;
use dbObject\ArrayChecklistItemDependency;
use dbObject\ChecklistRun;
use dbObject\ChecklistRunItem;
use dbObject\ChecklistTrigger;
use dbObject\DbObject;
use dbObject\Holon;
use dbObject\Project;
use dbObject\PropertyFormat;
use dbObject\RecurrenceSchedule;

header('Content-Type: application/json; charset=UTF-8');

function omoChecklistActionRespond($success, $message = '', array $extra = [], $statusCode = 200)
{
    http_response_code((int)$statusCode);
    echo json_encode(array_merge([
        'success' => (bool)$success,
        'status' => (bool)$success,
        'message' => (string)$message,
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function omoChecklistActionSaveObject($object)
{
    $result = $object->save();
    if (!is_array($result) || empty($result['status']) || (int)$object->getId() <= 0) {
        throw new RuntimeException(omoChecklistT('checklist.error.save'));
    }
}

function omoChecklistActionHasCycle(array $relations)
{
    $states = [];
    $visit = static function ($key) use (&$visit, &$states, $relations) {
        if (($states[$key] ?? 0) === 1) {
            return true;
        }
        if (($states[$key] ?? 0) === 2) {
            return false;
        }
        $states[$key] = 1;
        $target = (string)($relations[$key] ?? '');
        if ($target !== '' && isset($relations[$target]) && $visit($target)) {
            return true;
        }
        $states[$key] = 2;
        return false;
    };
    foreach (array_keys($relations) as $key) {
        if ($visit($key)) {
            return true;
        }
    }
    return false;
}

function omoChecklistActionDetailUrl($organizationId, $checklistId, $currentHolonId = 0)
{
    $url = '/omo/api/checklist/detail.php?oid=' . rawurlencode((string)$organizationId)
        . '&id=' . rawurlencode((string)$checklistId);
    if ((int)$currentHolonId > 0) {
        $url .= '&cid=' . rawurlencode((string)$currentHolonId);
    }
    return $url;
}

function omoChecklistActionCloneProject(Project $template, $parentProjectId, DateTimeInterface $plannedStart, $titleOverride = null, $plannedEnd = null)
{
    $project = Project::createFromChecklistTemplate($template, $parentProjectId, $plannedStart, $titleOverride, $plannedEnd);
    if (!($project instanceof Project)) {
        throw new RuntimeException(omoChecklistT('checklist.error.save'));
    }
    return $project;
}

function omoChecklistActionRemoveItemDependencies(ChecklistItem $item)
{
    $dependencies = new ArrayChecklistItemDependency();
    $dependencies->loadForItem((int)$item->getId());
    foreach ($dependencies as $dependency) {
        if ($dependency instanceof ChecklistItemDependency && !$dependency->delete()) {
            throw new RuntimeException(omoChecklistT('checklist.error.save'));
        }
    }
    $dependencies->loadForRequiredItem((int)$item->getId());
    foreach ($dependencies as $dependency) {
        if ($dependency instanceof ChecklistItemDependency && !$dependency->delete()) {
            throw new RuntimeException(omoChecklistT('checklist.error.save'));
        }
    }
}

function omoChecklistActionNextItemPosition(Checklist $checklist)
{
    $position = 0;
    foreach ($checklist->getItems(true) as $item) {
        if ($item instanceof ChecklistItem) {
            $position = max($position, (int)$item->get('position') + 1);
        }
    }
    return $position;
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    omoChecklistActionRespond(false, omoChecklistT('checklist.error.method'), [], 405);
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_POST['oid'] ?? 0));
$currentHolonId = isset($_POST['cid']) && is_numeric($_POST['cid']) ? (int)$_POST['cid'] : 0;
$context = omoChecklistResolveContext($organizationId, $currentHolonId);
if (empty($context['status'])) {
    omoChecklistActionRespond(false, (string)($context['message'] ?? omoChecklistT('checklist.error.context')), [], 403);
}
$action = trim((string)($_POST['checklist_action'] ?? $_POST['action'] ?? ''));

if ($action === 'save_checklist') {
    $checklistId = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : 0;
    $checklist = $checklistId > 0 ? omoChecklistLoad($checklistId, $organizationId) : new Checklist();
    if ($checklistId > 0 && !($checklist instanceof Checklist)) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.not_found'), [], 404);
    }
    if ($checklistId > 0 ? !omoChecklistCanManage($checklist) : !omoChecklistCanCreateContext($context)) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.forbidden'), [], 403);
    }

    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.title'), [], 422);
    }
    $status = Checklist::normalizeStatus($_POST['status'] ?? Checklist::STATUS_DRAFT);
    if ($status === Checklist::STATUS_RETIRED) {
        $status = Checklist::STATUS_DRAFT;
    }

    $triggerType = ChecklistTrigger::normalizeTriggerType($_POST['trigger_type'] ?? ChecklistTrigger::TYPE_MANUAL);
    $frequency = null;
    $schedule = null;
    if ($triggerType === ChecklistTrigger::TYPE_SCHEDULED) {
        $frequency = RecurrenceSchedule::normalizeFrequency($_POST['frequency'] ?? '');
        $schedule = RecurrenceSchedule::normalizeSchedule($frequency, $_POST['schedule'] ?? '');
        if ($frequency === null || $schedule === null) {
            omoChecklistActionRespond(false, omoChecklistT('checklist.error.schedule'), [], 422);
        }
    }
    $overlapPolicy = ChecklistTrigger::normalizeOverlapPolicy($_POST['overlap_policy'] ?? ChecklistTrigger::OVERLAP_REUSE_OPEN);

    $pdo = DbObject::getPdo();
    $startedTransaction = false;
    try {
        if ($pdo && !$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        $templateRoot = $checklistId > 0 ? $checklist->getTemplateRoot() : new Project();
        if (!($templateRoot instanceof Project)) {
            throw new RuntimeException(omoChecklistT('checklist.error.save'));
        }
        if ($checklistId <= 0) {
            $currentHolon = $context['currentHolon'] ?? null;
            if (!($currentHolon instanceof Holon)) {
                throw new RuntimeException(omoChecklistT('checklist.error.context'));
            }
            $templateRoot->set('IDorganization', $organizationId);
            $templateRoot->set('IDholon', (int)$currentHolon->getId());
            $templateRoot->set('IDproject_parent', null);
        }
        $templateRoot->set('IDuser', null);
        $templateRoot->set('title', mb_substr($title, 0, 255, 'UTF-8'));
        $templateRoot->set('description', PropertyFormat::sanitizeHtml((string)($_POST['description'] ?? '')));
        $templateRoot->set('status', Project::STATUS_SOMEDAY);
        $templateRoot->set('capture_mode', Project::CAPTURE_MULTIPLE_DOCUMENTS);
        $templateRoot->set('project_size', Project::SIZE_M);
        $templateRoot->set('project_kind', Project::KIND_CHECKLIST_TEMPLATE);
        $templateRoot->set('IDproject_template', null);
        $templateRoot->set('active', 1);
        omoChecklistActionSaveObject($templateRoot);

        if ($checklistId <= 0) {
            $checklist->set('IDorganization', $organizationId);
            $checklist->set('IDproject_template_root', (int)$templateRoot->getId());
            $checklist->set('IDchecklist_previous', null);
            $checklist->set('IDdocument', null);
        }
        $checklist->set('status', $status);
        $checklist->set('revision_note', trim((string)($_POST['revision_note'] ?? '')) ?: null);
        $checklist->set('active', 1);
        omoChecklistActionSaveObject($checklist);

        $primaryTrigger = null;
        foreach ($checklist->getTriggers(false) as $candidateTrigger) {
            if (!($candidateTrigger instanceof ChecklistTrigger)) {
                continue;
            }
            if (!($primaryTrigger instanceof ChecklistTrigger)) {
                $primaryTrigger = $candidateTrigger;
            } else {
                $candidateTrigger->set('enabled', 0);
                omoChecklistActionSaveObject($candidateTrigger);
            }
        }
        if (!($primaryTrigger instanceof ChecklistTrigger)) {
            $primaryTrigger = new ChecklistTrigger();
            $primaryTrigger->set('IDchecklist', (int)$checklist->getId());
            $primaryTrigger->set('stable_key', 'primary');
        }
        $primaryTrigger->set('trigger_type', $triggerType);
        $primaryTrigger->set('frequency', $frequency);
        $primaryTrigger->set('schedule', $schedule);
        $primaryTrigger->set('overlap_policy', $overlapPolicy);
        $primaryTrigger->set('enabled', $triggerType === ChecklistTrigger::TYPE_CONTAINER ? 0 : 1);
        $primaryTrigger->set('next_trigger_at', $triggerType === ChecklistTrigger::TYPE_SCHEDULED
            ? RecurrenceSchedule::getNextOccurrence($frequency, $schedule, new DateTimeImmutable())
            : null);
        omoChecklistActionSaveObject($primaryTrigger);

        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (Throwable $exception) {
        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.save'), [], 500);
    }

    omoChecklistActionRespond(true, omoChecklistT('checklist.success.save'), [
        'id' => (int)$checklist->getId(),
        'detailUrl' => omoChecklistActionDetailUrl($organizationId, (int)$checklist->getId(), $currentHolonId),
    ]);
}

if ($action === 'activate_checklist') {
    $checklistId = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : 0;
    $checklist = omoChecklistLoad($checklistId, $organizationId);
    $trigger = $checklist instanceof Checklist ? omoChecklistGetPrimaryTrigger($checklist) : null;
    if (!($checklist instanceof Checklist)) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.not_found'), [], 404);
    }
    if (!($trigger instanceof ChecklistTrigger) || !omoChecklistCanActivate($checklist, $trigger)) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.activation_unavailable'), [], 403);
    }

    $referenceValue = trim((string)($_POST['reference_date'] ?? ''));
    $referenceAt = DateTimeImmutable::createFromFormat('!Y-m-d', $referenceValue);
    $referenceErrors = DateTimeImmutable::getLastErrors();
    if (
        !($referenceAt instanceof DateTimeImmutable)
        || $referenceAt->format('Y-m-d') !== $referenceValue
        || (is_array($referenceErrors) && ((int)$referenceErrors['warning_count'] > 0 || (int)$referenceErrors['error_count'] > 0))
    ) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.reference_date'), [], 422);
    }

    $openRuns = [];
    foreach ($checklist->getOpenRuns() as $openRun) {
        if ($openRun instanceof ChecklistRun) {
            $openRuns[] = $openRun;
        }
    }
    $overlapPolicy = ChecklistTrigger::normalizeOverlapPolicy($trigger->get('overlap_policy'));
    if (count($openRuns) > 0) {
        if ($overlapPolicy === ChecklistTrigger::OVERLAP_REUSE_OPEN) {
            omoChecklistActionRespond(true, omoChecklistT('checklist.success.reused'), [
                'id' => $checklistId,
                'runId' => (int)$openRuns[0]->getId(),
                'reused' => true,
                'detailUrl' => omoChecklistActionDetailUrl($organizationId, $checklistId, $currentHolonId),
            ]);
        }
        if ($overlapPolicy === ChecklistTrigger::OVERLAP_SKIP) {
            omoChecklistActionRespond(false, omoChecklistT('checklist.error.open_instance'), [], 409);
        }
        if (
            $overlapPolicy === ChecklistTrigger::OVERLAP_ASK
            && trim((string)($_POST['overlap_decision'] ?? '')) !== 'create_new'
        ) {
            omoChecklistActionRespond(false, omoChecklistT('checklist.error.open_instance'), [], 409);
        }
    }

    $templateRoot = $checklist->getTemplateRoot();
    if (!($templateRoot instanceof Project)) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.not_found'), [], 404);
    }
    $instanceTitle = trim((string)($_POST['instance_title'] ?? ''));
    $projectAttributeLength = Project::attributeLength();
    $instanceTitleMaxLength = (int)($projectAttributeLength['title'] ?? 255);
    if ($instanceTitle === '' || mb_strlen($instanceTitle, 'UTF-8') > $instanceTitleMaxLength) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.instance_title'), [], 422);
    }
    $now = new DateTimeImmutable();
    $pdo = DbObject::getPdo();
    $startedTransaction = false;
    try {
        if ($pdo && !$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        $rootProject = omoChecklistActionCloneProject($templateRoot, 0, $referenceAt, $instanceTitle);
        $currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
        $run = new ChecklistRun();
        $run->set('IDchecklist', $checklistId);
        $run->set('IDchecklisttrigger', (int)$trigger->getId());
        $run->set('IDorganization', $organizationId);
        $run->set('IDholon', (int)$templateRoot->get('IDholon') ?: null);
        $run->set('IDproject_root', (int)$rootProject->getId());
        $run->set('IDuser_created', $currentUserId > 0 ? $currentUserId : null);
        $run->set('scheduled_for', $referenceAt);
        $run->set('status', ChecklistRun::STATUS_RUNNING);
        omoChecklistActionSaveObject($run);

        $items = [];
        foreach ($checklist->getItems(true) as $item) {
            if (!($item instanceof ChecklistItem)) {
                continue;
            }
            $itemId = (int)$item->getId();
            $items[$itemId] = $item;
        }

        $plans = [];
        foreach ($items as $itemId => $item) {
            $templateProject = $item->getProjectTemplate();
            if (!($templateProject instanceof Project)) {
                throw new RuntimeException(omoChecklistT('checklist.error.save'));
            }
            $activationType = ChecklistItem::normalizeActivationType($item->get('activation_type'));
            $activationAt = $item->calculateActivationAt($referenceAt, $now);
            $plannedStartAt = $item->calculatePlannedStartAt($referenceAt);
            $plans[$itemId] = [
                'item' => $item,
                'template' => $templateProject,
                'activationType' => $activationType,
                'activationAt' => $activationAt,
                'plannedStartAt' => $plannedStartAt,
                'parentTemplateId' => (int)$templateProject->get('IDproject_parent'),
                'project' => null,
            ];
        }

        $generatedProjectIds = [(int)$templateRoot->getId() => (int)$rootProject->getId()];
        $madeProgress = true;
        while ($madeProgress) {
            $madeProgress = false;
            foreach ($plans as $itemId => &$plan) {
                if ($plan['project'] instanceof Project || !($plan['activationAt'] instanceof DateTimeImmutable) || $plan['activationAt'] > $now) {
                    continue;
                }
                $parentTemplateId = (int)$plan['parentTemplateId'];
                if ($parentTemplateId <= 0) {
                    $parentTemplateId = (int)$templateRoot->getId();
                }
                if (!isset($generatedProjectIds[$parentTemplateId])) {
                    continue;
                }
                $plan['project'] = omoChecklistActionCloneProject(
                    $plan['template'],
                    (int)$generatedProjectIds[$parentTemplateId],
                    $plan['plannedStartAt'] instanceof DateTimeImmutable ? $plan['plannedStartAt'] : $plan['activationAt'],
                    null,
                    $plan['plannedStartAt'] instanceof DateTimeImmutable
                        ? $plan['item']->getDeadlineAt($plan['plannedStartAt'])
                        : null
                );
                $generatedProjectIds[(int)$plan['template']->getId()] = (int)$plan['project']->getId();
                $madeProgress = true;
            }
            unset($plan);
        }

        foreach ($plans as $plan) {
            $runItem = new ChecklistRunItem();
            $runItem->set('IDchecklistrun', (int)$run->getId());
            $runItem->set('IDchecklistitem', (int)$plan['item']->getId());
            $runItem->set('IDproject', $plan['project'] instanceof Project ? (int)$plan['project']->getId() : null);
            $runItem->set('activation_at', $plan['activationAt']);
            if ($plan['project'] instanceof Project) {
                $runItem->set('state', ChecklistRunItem::STATE_CREATED);
            } elseif ($plan['activationType'] === ChecklistItem::ACTIVATION_AFTER_COMPLETION) {
                $runItem->set('state', ChecklistRunItem::STATE_BLOCKED);
            } elseif ($plan['activationAt'] instanceof DateTimeImmutable && $plan['activationAt'] > $now) {
                $runItem->set('state', ChecklistRunItem::STATE_WAITING);
            } else {
                $runItem->set('state', ChecklistRunItem::STATE_WAITING);
            }
            omoChecklistActionSaveObject($runItem);
        }

        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (Throwable $exception) {
        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.save'), [], 500);
    }

    omoChecklistActionRespond(true, omoChecklistT('checklist.success.activated'), [
        'id' => $checklistId,
        'runId' => (int)$run->getId(),
        'detailUrl' => omoChecklistActionDetailUrl($organizationId, $checklistId, $currentHolonId),
    ]);
}

if (in_array($action, ['delete_item', 'move_item', 'extract_item'], true)) {
    $checklistId = isset($_POST['checklist_id']) && is_numeric($_POST['checklist_id']) ? (int)$_POST['checklist_id'] : 0;
    $itemId = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : 0;
    $checklist = omoChecklistLoad($checklistId, $organizationId);
    $item = new ChecklistItem();
    if (!($checklist instanceof Checklist)) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.not_found'), [], 404);
    }
    if (!omoChecklistCanManage($checklist)) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.forbidden'), [], 403);
    }
    if ($itemId <= 0 || !$item->load($itemId) || (int)$item->get('IDchecklist') !== $checklistId || (int)$item->get('active') !== 1) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.item_not_found'), [], 404);
    }

    $project = $item->getProjectTemplate();
    if (!($project instanceof Project)) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.item_not_found'), [], 404);
    }

    $pdo = DbObject::getPdo();
    $startedTransaction = false;
    try {
        if ($pdo && !$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        if ($action === 'delete_item') {
            omoChecklistActionRemoveItemDependencies($item);
            $recurrence = $item->getRecurrence();
            if ($recurrence instanceof ChecklistItemRecurrence && !$recurrence->delete()) {
                throw new RuntimeException(omoChecklistT('checklist.error.save'));
            }
            $item->set('active', 0);
            omoChecklistActionSaveObject($item);
            $project->set('active', 0);
            omoChecklistActionSaveObject($project);
            omoChecklistActionSaveObject($checklist);
        }

        if ($action === 'move_item') {
            $targetChecklistId = isset($_POST['target_checklist_id']) && is_numeric($_POST['target_checklist_id'])
                ? (int)$_POST['target_checklist_id']
                : 0;
            $targetChecklist = omoChecklistLoad($targetChecklistId, $organizationId);
            if (!($targetChecklist instanceof Checklist)
                || $targetChecklistId === $checklistId
                || !omoChecklistCanManage($targetChecklist)) {
                throw new InvalidArgumentException(omoChecklistT('checklist.error.item_target'));
            }
            $targetRoot = $targetChecklist->getTemplateRoot();
            if (!($targetRoot instanceof Project)) {
                throw new InvalidArgumentException(omoChecklistT('checklist.error.item_target'));
            }
            $targetTrigger = omoChecklistGetPrimaryTrigger($targetChecklist);
            $targetIsContainer = $targetTrigger instanceof ChecklistTrigger
                && ChecklistTrigger::normalizeTriggerType($targetTrigger->get('trigger_type')) === ChecklistTrigger::TYPE_CONTAINER;

            omoChecklistActionRemoveItemDependencies($item);
            $recurrence = $item->getRecurrence();
            if (!$targetIsContainer && $recurrence instanceof ChecklistItemRecurrence && !$recurrence->delete()) {
                throw new RuntimeException(omoChecklistT('checklist.error.save'));
            }
            $project->set('IDproject_parent', (int)$targetRoot->getId());
            omoChecklistActionSaveObject($project);
            $item->set('IDchecklist', $targetChecklistId);
            $item->set('position', omoChecklistActionNextItemPosition($targetChecklist));
            if ($targetIsContainer) {
                $item->set('activation_type', ChecklistItem::ACTIVATION_IMMEDIATE);
                $item->set('delay_value', 0);
                $item->set('delay_unit', null);
            }
            omoChecklistActionSaveObject($item);
            omoChecklistActionSaveObject($checklist);
            omoChecklistActionSaveObject($targetChecklist);
        }

        if ($action === 'extract_item') {
            $sourceTrigger = omoChecklistGetPrimaryTrigger($checklist);
            $isContainerChecklist = $sourceTrigger instanceof ChecklistTrigger
                && ChecklistTrigger::normalizeTriggerType($sourceTrigger->get('trigger_type')) === ChecklistTrigger::TYPE_CONTAINER;
            $recurrence = $item->getRecurrence();
            $frequency = $recurrence instanceof ChecklistItemRecurrence
                ? RecurrenceSchedule::normalizeFrequency($recurrence->get('frequency'))
                : null;
            $schedule = $recurrence instanceof ChecklistItemRecurrence
                ? RecurrenceSchedule::normalizeSchedule($frequency, $recurrence->get('schedule'))
                : null;
            if (!$isContainerChecklist || $frequency === null || $schedule === null || (int)$recurrence->get('enabled') !== 1) {
                throw new InvalidArgumentException(omoChecklistT('checklist.error.item_extract_recurrence'));
            }

            $project->set('IDproject_parent', null);
            $project->set('project_kind', Project::KIND_CHECKLIST_TEMPLATE);
            omoChecklistActionSaveObject($project);

            $extractedChecklist = new Checklist();
            $extractedChecklist->set('IDorganization', $organizationId);
            $extractedChecklist->set('IDchecklist_previous', null);
            $extractedChecklist->set('IDproject_template_root', (int)$project->getId());
            $extractedChecklist->set('IDdocument', null);
            $extractedChecklist->set('status', Checklist::normalizeStatus($checklist->get('status')));
            $extractedChecklist->set('revision_note', null);
            $extractedChecklist->set('active', 1);
            omoChecklistActionSaveObject($extractedChecklist);

            $trigger = new ChecklistTrigger();
            $trigger->set('IDchecklist', (int)$extractedChecklist->getId());
            $trigger->set('stable_key', 'primary');
            $trigger->set('trigger_type', ChecklistTrigger::TYPE_SCHEDULED);
            $trigger->set('frequency', $frequency);
            $trigger->set('schedule', $schedule);
            $trigger->set('overlap_policy', ChecklistTrigger::OVERLAP_REUSE_OPEN);
            $trigger->set('enabled', 1);
            $trigger->set('next_trigger_at', RecurrenceSchedule::getNextOccurrence($frequency, $schedule, new DateTimeImmutable()));
            omoChecklistActionSaveObject($trigger);

            omoChecklistActionRemoveItemDependencies($item);
            if (!$recurrence->delete()) {
                throw new RuntimeException(omoChecklistT('checklist.error.save'));
            }
            $item->set('active', 0);
            omoChecklistActionSaveObject($item);
            omoChecklistActionSaveObject($checklist);
        }

        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (InvalidArgumentException $exception) {
        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        omoChecklistActionRespond(false, $exception->getMessage(), [], 422);
    } catch (Throwable $exception) {
        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.save'), [], 500);
    }

    $responseChecklistId = $action === 'extract_item' ? (int)$extractedChecklist->getId() : $checklistId;
    $messages = [
        'delete_item' => omoChecklistT('checklist.success.item_deleted'),
        'move_item' => omoChecklistT('checklist.success.item_moved'),
        'extract_item' => omoChecklistT('checklist.success.item_extracted'),
    ];
    omoChecklistActionRespond(true, $messages[$action], [
        'id' => $responseChecklistId,
        'detailUrl' => omoChecklistActionDetailUrl($organizationId, $responseChecklistId, $currentHolonId),
    ]);
}

if ($action === 'save_item') {
    $checklistId = isset($_POST['checklist_id']) && is_numeric($_POST['checklist_id']) ? (int)$_POST['checklist_id'] : 0;
    $itemId = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : 0;
    $checklist = omoChecklistLoad($checklistId, $organizationId);
    if (!($checklist instanceof Checklist)) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.not_found'), [], 404);
    }
    if (!omoChecklistCanManage($checklist)) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.forbidden'), [], 403);
    }

    $item = new ChecklistItem();
    if ($itemId > 0 && (!$item->load($itemId) || (int)$item->get('IDchecklist') !== $checklistId || (int)$item->get('active') !== 1)) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.item_not_found'), [], 404);
    }
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.item_title'), [], 422);
    }

    $holonId = isset($_POST['IDholon']) && is_numeric($_POST['IDholon']) ? (int)$_POST['IDholon'] : 0;
    $allowedHolonMap = [];
    foreach (omoChecklistBuildHolonOptions($context) as $option) {
        $allowedHolonMap[(int)$option['id']] = true;
    }
    if (!isset($allowedHolonMap[$holonId])) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.item_holon'), [], 422);
    }

    $activationType = ChecklistItem::normalizeActivationType($_POST['activation_type'] ?? ChecklistItem::ACTIVATION_IMMEDIATE);
    if (!in_array($activationType, [ChecklistItem::ACTIVATION_IMMEDIATE, ChecklistItem::ACTIVATION_AFTER_START, ChecklistItem::ACTIVATION_AFTER_COMPLETION], true)) {
        $activationType = ChecklistItem::ACTIVATION_IMMEDIATE;
    }
    $delayValue = max(-3650, min(3650, (int)($_POST['delay_value'] ?? 0)));
    $delayUnit = ChecklistItem::normalizeDelayUnit($_POST['delay_unit'] ?? ChecklistItem::DELAY_DAY);
    if ($delayValue !== 0 && $delayUnit === null) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.item_relation'), [], 422);
    }
    $delayUnit = $delayUnit ?: ChecklistItem::DELAY_DAY;
    $displayLeadValue = max(0, min(3650, (int)($_POST['display_lead_value'] ?? 0)));
    $displayLeadUnit = ChecklistItem::normalizeDelayUnit($_POST['display_lead_unit'] ?? ChecklistItem::DELAY_DAY) ?: ChecklistItem::DELAY_DAY;
    $executionDurationValue = max(0, min(3650, (int)($_POST['execution_duration_value'] ?? 0)));
    $executionDurationUnit = ChecklistItem::normalizeDelayUnit($_POST['execution_duration_unit'] ?? ChecklistItem::DELAY_DAY) ?: ChecklistItem::DELAY_DAY;

    $primaryTrigger = omoChecklistGetPrimaryTrigger($checklist);
    $isContainerChecklist = $primaryTrigger instanceof ChecklistTrigger
        && ChecklistTrigger::normalizeTriggerType($primaryTrigger->get('trigger_type')) === ChecklistTrigger::TYPE_CONTAINER;
    $recurrenceFrequency = null;
    $recurrenceSchedule = null;
    if ($isContainerChecklist) {
        $recurrenceFrequency = RecurrenceSchedule::normalizeFrequency($_POST['recurrence_frequency'] ?? '');
        if ($recurrenceFrequency !== null) {
            $recurrenceSchedule = RecurrenceSchedule::normalizeSchedule($recurrenceFrequency, $_POST['recurrence_schedule'] ?? '');
            if ($recurrenceSchedule === null) {
                omoChecklistActionRespond(false, omoChecklistT('checklist.error.schedule'), [], 422);
            }
        }
    }

    $parentItemId = isset($_POST['parent_item_id']) && is_numeric($_POST['parent_item_id']) ? (int)$_POST['parent_item_id'] : 0;
    $dependencyItemId = isset($_POST['dependency_item_id']) && is_numeric($_POST['dependency_item_id']) ? (int)$_POST['dependency_item_id'] : 0;
    if ($activationType !== ChecklistItem::ACTIVATION_AFTER_COMPLETION) {
        $dependencyItemId = 0;
    } else {
        $delayValue = max(0, $delayValue);
    }
    if ($isContainerChecklist) {
        $parentItemId = 0;
        $dependencyItemId = 0;
        $activationType = ChecklistItem::ACTIVATION_IMMEDIATE;
        $delayValue = 0;
        $delayUnit = ChecklistItem::DELAY_DAY;
    }

    $activeItems = [];
    $itemByProjectId = [];
    $maxPosition = -1;
    foreach ($checklist->getItems(true) as $candidateItem) {
        if (!($candidateItem instanceof ChecklistItem)) {
            continue;
        }
        $candidateId = (int)$candidateItem->getId();
        $activeItems[$candidateId] = $candidateItem;
        $itemByProjectId[(int)$candidateItem->get('IDproject_template')] = $candidateId;
        $maxPosition = max($maxPosition, (int)$candidateItem->get('position'));
    }
    foreach ([$parentItemId, $dependencyItemId] as $relatedItemId) {
        if ($relatedItemId > 0 && (!isset($activeItems[$relatedItemId]) || $relatedItemId === $itemId)) {
            omoChecklistActionRespond(false, omoChecklistT('checklist.error.item_relation'), [], 422);
        }
    }
    if ($activationType === ChecklistItem::ACTIVATION_AFTER_COMPLETION && $dependencyItemId <= 0) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.item_relation'), [], 422);
    }

    $currentNode = $itemId > 0 ? 'item_' . $itemId : 'new_item';
    $parentRelations = [];
    $dependencyRelations = [];
    foreach ($activeItems as $candidateId => $candidateItem) {
        $node = $candidateId === $itemId ? $currentNode : 'item_' . $candidateId;
        $candidateProject = $candidateItem->getProjectTemplate();
        $candidateParentItemId = $candidateProject instanceof Project
            ? (int)($itemByProjectId[(int)$candidateProject->get('IDproject_parent')] ?? 0)
            : 0;
        $parentRelations[$node] = $candidateParentItemId > 0 ? 'item_' . $candidateParentItemId : '';

        $candidateDependencyId = 0;
        foreach ($candidateItem->getDependencies() as $candidateDependency) {
            if ($candidateDependency instanceof ChecklistItemDependency) {
                $candidateDependencyId = (int)$candidateDependency->get('IDchecklistitem_required');
                break;
            }
        }
        $dependencyRelations[$node] = $candidateDependencyId > 0 ? 'item_' . $candidateDependencyId : '';
    }
    if (!isset($parentRelations[$currentNode])) {
        $parentRelations[$currentNode] = '';
        $dependencyRelations[$currentNode] = '';
    }
    $parentRelations[$currentNode] = $parentItemId > 0 ? 'item_' . $parentItemId : '';
    $dependencyRelations[$currentNode] = $dependencyItemId > 0 ? 'item_' . $dependencyItemId : '';
    if (omoChecklistActionHasCycle($parentRelations) || omoChecklistActionHasCycle($dependencyRelations)) {
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.item_relation'), [], 422);
    }

    $pdo = DbObject::getPdo();
    $startedTransaction = false;
    try {
        if ($pdo && !$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }
        $templateRoot = $checklist->getTemplateRoot();
        $project = $itemId > 0 ? $item->getProjectTemplate() : new Project();
        if (!($templateRoot instanceof Project) || !($project instanceof Project)) {
            throw new RuntimeException(omoChecklistT('checklist.error.save'));
        }
        $parentProject = $parentItemId > 0 ? $activeItems[$parentItemId]->getProjectTemplate() : $templateRoot;
        if (!($parentProject instanceof Project)) {
            throw new RuntimeException(omoChecklistT('checklist.error.save'));
        }

        if ($itemId <= 0) {
            $project->set('IDorganization', $organizationId);
        }
        $project->set('IDholon', $holonId);
        $project->set('IDuser', null);
        $project->set('IDproject_parent', (int)$parentProject->getId());
        $project->set('title', mb_substr($title, 0, 255, 'UTF-8'));
        $project->set('description', PropertyFormat::sanitizeHtml((string)($_POST['description'] ?? '')));
        $project->set('status', Project::STATUS_SOMEDAY);
        $project->set('capture_mode', Project::CAPTURE_MULTIPLE_DOCUMENTS);
        $project->set('project_size', Project::normalizeSize($_POST['project_size'] ?? Project::SIZE_M));
        $project->set('priority', Project::normalizeLevel($_POST['priority'] ?? null));
        $project->set('importance', Project::normalizeLevel($_POST['importance'] ?? null));
        $project->set('project_kind', Project::KIND_CHECKLIST_TEMPLATE);
        $project->set('IDproject_template', null);
        $project->set('active', 1);
        omoChecklistActionSaveObject($project);

        if ($itemId <= 0) {
            $item->set('IDchecklist', $checklistId);
            $item->set('IDproject_template', (int)$project->getId());
            $item->set('stable_key', 'item_' . bin2hex(random_bytes(8)));
            $item->set('position', $maxPosition + 1);
        }
        $item->set('activation_type', $activationType);
        $item->set('delay_value', $activationType === ChecklistItem::ACTIVATION_AFTER_START ? $delayValue : 0);
        $item->set('delay_unit', $activationType === ChecklistItem::ACTIVATION_AFTER_START && $delayValue !== 0 ? $delayUnit : null);
        $item->set('display_lead_value', $displayLeadValue);
        $item->set('display_lead_unit', $displayLeadValue > 0 ? $displayLeadUnit : null);
        $item->set('execution_duration_value', $executionDurationValue);
        $item->set('execution_duration_unit', $executionDurationValue > 0 ? $executionDurationUnit : null);
        $item->set('active', 1);
        omoChecklistActionSaveObject($item);

        foreach ($item->getDependencies() as $existingDependency) {
            if ($existingDependency instanceof ChecklistItemDependency && !$existingDependency->delete()) {
                throw new RuntimeException(omoChecklistT('checklist.error.save'));
            }
        }
        if ($dependencyItemId > 0) {
            $dependency = new ChecklistItemDependency();
            $dependency->set('IDchecklistitem', (int)$item->getId());
            $dependency->set('IDchecklistitem_required', $dependencyItemId);
            $dependency->set('delay_value', $delayValue);
            $dependency->set('delay_unit', $delayValue > 0 ? $delayUnit : null);
            omoChecklistActionSaveObject($dependency);
        }

        $recurrence = $item->getRecurrence();
        if ($isContainerChecklist && $recurrenceFrequency !== null) {
            if (!($recurrence instanceof ChecklistItemRecurrence)) {
                $recurrence = new ChecklistItemRecurrence();
                $recurrence->set('IDchecklistitem', (int)$item->getId());
            }
            $existingLeadValue = max(0, (int)$recurrence->get('display_lead_value'));
            $existingLeadUnit = $existingLeadValue > 0
                ? (ChecklistItem::normalizeDelayUnit($recurrence->get('display_lead_unit')) ?: ChecklistItem::DELAY_DAY)
                : ChecklistItem::DELAY_DAY;
            $scheduleChanged = RecurrenceSchedule::normalizeFrequency($recurrence->get('frequency')) !== $recurrenceFrequency
                || RecurrenceSchedule::normalizeSchedule($recurrence->get('frequency'), $recurrence->get('schedule')) !== $recurrenceSchedule
                || $existingLeadValue !== $displayLeadValue
                || $existingLeadUnit !== $displayLeadUnit;
            $recurrence->set('frequency', $recurrenceFrequency);
            $recurrence->set('schedule', $recurrenceSchedule);
            $recurrence->set('display_lead_value', $displayLeadValue);
            $recurrence->set('display_lead_unit', $displayLeadValue > 0 ? $displayLeadUnit : null);
            $recurrence->set('execution_duration_value', $executionDurationValue);
            $recurrence->set('execution_duration_unit', $executionDurationValue > 0 ? $executionDurationUnit : null);
            $recurrence->set('enabled', 1);
            if ($scheduleChanged || !($recurrence->get('next_trigger_at') instanceof DateTimeInterface)) {
                $nextOccurrenceAt = RecurrenceSchedule::getNextOccurrence($recurrenceFrequency, $recurrenceSchedule, new DateTimeImmutable());
                $recurrence->set(
                    'next_trigger_at',
                    $nextOccurrenceAt instanceof DateTimeImmutable ? $recurrence->getDisplayTriggerAt($nextOccurrenceAt) : null
                );
            }
            omoChecklistActionSaveObject($recurrence);
        } elseif ($isContainerChecklist && $recurrence instanceof ChecklistItemRecurrence) {
            if (!$recurrence->delete()) {
                throw new RuntimeException(omoChecklistT('checklist.error.save'));
            }
        }

        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (Throwable $exception) {
        if ($startedTransaction && $pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        omoChecklistActionRespond(false, omoChecklistT('checklist.error.save'), [], 500);
    }

    omoChecklistActionRespond(true, omoChecklistT('checklist.success.save'), [
        'id' => (int)$checklist->getId(),
        'itemId' => (int)$item->getId(),
        'detailUrl' => omoChecklistActionDetailUrl($organizationId, (int)$checklist->getId(), $currentHolonId),
    ]);
}

omoChecklistActionRespond(false, omoChecklistT('checklist.error.action'), [], 422);
