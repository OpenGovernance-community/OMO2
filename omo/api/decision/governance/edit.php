<?php

use dbObject\Authority;
use dbObject\ArrayAuthority;
use dbObject\DecisionProcess;
use dbObject\DeferredProposal;
use dbObject\Rule;

require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__, 4) . '/common/choice/deferred-editor-fields.php';
require_once dirname(__DIR__, 4) . '/common/patreon.php';
require_once dirname(__DIR__, 4) . '/common/openai_text.php';
require_once dirname(__DIR__) . '/params/shared.php';

$decision = ($context['decision'] ?? null) instanceof DecisionProcess ? $context['decision'] : null;
$currentUserId = (int)($context['currentUserId'] ?? 0);
$targetHolonId = (int)($context['targetHolonId'] ?? 0);
$targetHolon = ($context['effectiveHolon'] ?? null) instanceof \dbObject\Holon ? $context['effectiveHolon'] : null;
$isEditing = $decision instanceof DecisionProcess;
$isOwner = !$isEditing || (int)$decision->get('IDuser') === $currentUserId;
$isLocked = $isEditing && $decision->hasConsultationEnded();
$canEdit = $isOwner && !$isLocked;
$canUseAi = $canEdit && commonOpenAiGetApiKey() !== '' && patreonUserCanUseAi($currentUserId);
$processDescription = $isEditing ? (string)$decision->get('description') : '';
$decisionSettings = omoDecisionParamsGetConfig($context['organization'] ?? null);
$governanceSettings = $decisionSettings['governance'];
$existingGovernanceGroup = $isEditing ? $decision->getPrimaryGroup(false) : null;
$governanceMethod = $existingGovernanceGroup instanceof \dbObject\DecisionGroup
    ? DecisionProcess::normalizeEvaluationMethod($existingGovernanceGroup->get('evaluation_method'))
    : (string)($governanceSettings['evaluation_method'] ?? DecisionProcess::METHOD_CONSENT);
if (!in_array($governanceMethod, [DecisionProcess::METHOD_SIMPLE_VOTE, DecisionProcess::METHOD_CONSENT], true)) {
    $governanceMethod = DecisionProcess::METHOD_CONSENT;
}
$questionLabelKey = $governanceMethod === DecisionProcess::METHOD_SIMPLE_VOTE ? 'governance.question.vote_label' : 'governance.question.label';
$questionHelpKey = $governanceMethod === DecisionProcess::METHOD_SIMPLE_VOTE ? 'governance.question.vote_help' : 'governance.question.help';

if ($targetHolonId <= 0 || !$targetHolon instanceof \dbObject\Holon || !in_array((int)$targetHolon->get('IDtypeholon'), [1, 2], true)) {
    http_response_code(422);
    ?><div class="omo-empty-state"><?= omoApiEscape(omoDecisionGovernanceT('governance.error.holon')) ?></div><?php
    return;
}
if (!$isOwner) {
    http_response_code(403);
    ?><div class="omo-empty-state"><?= omoApiEscape(omoDecisionGovernanceT('governance.error.owner')) ?></div><?php
    return;
}
if (!$isEditing && empty($governanceSettings['enabled'])) {
    http_response_code(403);
    ?><div class="omo-empty-state"><?= omoApiEscape(omoDecisionGovernanceT('governance.error.disabled')) ?></div><?php
    return;
}

$now = new DateTimeImmutable('now');
$consultationEnd = $isEditing
    ? DecisionProcess::normalizeDateTimeValue($decision->get('consultation_end_at'))
    : $now->modify('+' . (int)$governanceSettings['consultation_days'] . ' days');
$evaluationEnd = $isEditing
    ? DecisionProcess::normalizeDateTimeValue($decision->get('evaluation_end_at'))
    : $consultationEnd->modify('+' . (int)$governanceSettings['vote_days'] . ' days');
$existingQuestion = $isEditing && $decision instanceof DecisionProcess
    ? trim((string)($existingGovernanceGroup ? $existingGovernanceGroup->get('title') : ''))
    : '';
$configuredQuestion = trim((string)($governanceSettings['question'] ?? ''));
$question = $existingQuestion !== '' ? $existingQuestion : $configuredQuestion;
$questionValue = $question !== '' ? $question : omoDecisionGovernanceT('governance.question.default');
$questionIsEditable = $canEdit;
$defaultRuleState = [
    'IDauthority' => null,
    'IDholon' => $targetHolonId,
    'title' => '',
    'intention' => '',
    'description' => '',
    'scope' => Rule::SCOPE_LOCAL,
    'review_date' => $now->modify('+6 months')->format('Y-m-d'),
    'expiration_date' => $now->modify('+1 year')->format('Y-m-d'),
];
$rules = Rule::findDefinedInHolon($targetHolonId);
$ruleData = array_map('omoDecisionGovernanceBuildRuleClientData', $rules);
$organization = $context['organization'] ?? null;
$roleData = [];
foreach (\dbObject\DecisionGovernanceAction::findRolesInGovernanceContext($targetHolon) as $role) {
    $roleData[] = omoDecisionGovernanceBuildRoleClientData(
        $role,
        $organization instanceof \dbObject\Organization ? $organization : null,
        $targetHolonId
    );
}
$roleTemplates = [];
if ($organization instanceof \dbObject\Organization) {
    $editorData = $organization->getHolonCreationEditorData($targetHolonId, 0, true);
    foreach ((array)($editorData['templateCatalog'] ?? []) as $template) {
        if ((int)($template['typeId'] ?? 0) === 1) {
            $roleTemplates[] = ['id' => (int)$template['id'], 'label' => trim((string)$template['name'])];
        }
    }
}
$authorities = [];
$authorityItems = new ArrayAuthority();
$authorityItems->loadForHolon($targetHolonId);
foreach ($authorityItems as $authority) {
    if ($authority instanceof Authority) {
        $authorities[(int)$authority->getId()] = [
            'id' => (int)$authority->getId(),
            'label' => trim((string)$authority->get('label')),
        ];
    }
}
$responsibleLabels = [];
$organizationMembers = new \dbObject\ArrayUserOrganization();
$organizationMembers->loadActiveForOrganization((int)$context['organizationId']);
foreach ($organizationMembers as $membership) {
    $memberUserId = (int)$membership->get('IDuser');
    if ($memberUserId > 0) {
        $responsibleLabels[$memberUserId] = \dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($memberUserId, (int)$context['organizationId']);
    }
}
$ruleCatalog = DeferredProposal::getRuleTargetHolonCatalog((int)$context['organizationId'], $targetHolonId);
$holonCatalog = DeferredProposal::getHolonTargetHolonCatalog((int)$context['organizationId'], $targetHolonId);
$projectCatalog = DeferredProposal::getProjectTargetHolonCatalog((int)$context['organizationId'], $targetHolonId);
$recurringTaskCatalog = DeferredProposal::getObjectTargetHolonCatalog((int)$context['organizationId'], $targetHolonId, DeferredProposal::TARGET_RECURRING_TASK);
$indicatorCatalog = DeferredProposal::getObjectTargetHolonCatalog((int)$context['organizationId'], $targetHolonId, DeferredProposal::TARGET_INDICATOR);
$contextLabels = [];
$contextPermissions = [
    DeferredProposal::TARGET_RULE => ['create' => [], 'update' => [], 'delete' => []],
    DeferredProposal::TARGET_HOLON => ['create' => [], 'update' => [], 'delete' => []],
    DeferredProposal::TARGET_PROJECT => ['create' => [], 'update' => [], 'delete' => []],
    DeferredProposal::TARGET_RECURRING_TASK => ['create' => [], 'update' => [], 'delete' => []],
    DeferredProposal::TARGET_INDICATOR => ['create' => [], 'update' => [], 'delete' => []],
];
$projectCreationModes = [];
foreach ([$ruleCatalog, $holonCatalog, $projectCatalog, $recurringTaskCatalog, $indicatorCatalog] as $catalog) {
    foreach ($catalog as $catalogHolonId => $entry) $contextLabels[(int)$catalogHolonId] = (string)($entry['label'] ?? '');
}
foreach ($ruleCatalog as $catalogHolonId => $entry) {
    foreach (array_keys($contextPermissions[DeferredProposal::TARGET_RULE]) as $catalogOperation) {
        if (!empty($entry['permissions'][$catalogOperation])) $contextPermissions[DeferredProposal::TARGET_RULE][$catalogOperation][] = (int)$catalogHolonId;
    }
}
foreach ($holonCatalog as $catalogHolonId => $entry) {
    if (!empty($entry['permissions'][DeferredProposal::OPERATION_CREATE])) $contextPermissions[DeferredProposal::TARGET_HOLON]['create'][] = (int)$catalogHolonId;
    foreach ([DeferredProposal::OPERATION_UPDATE, DeferredProposal::OPERATION_DELETE] as $catalogOperation) {
        if (empty($entry['permissions'][$catalogOperation])) continue;
        $catalogHolon = new \dbObject\Holon();
        $parentHolon = $catalogHolon->load((int)$catalogHolonId) ? $catalogHolon->getParentHolon() : null;
        if ($parentHolon instanceof \dbObject\Holon) $contextPermissions[DeferredProposal::TARGET_HOLON][$catalogOperation][] = (int)$parentHolon->getId();
    }
}
foreach ($projectCatalog as $catalogHolonId => $entry) {
    $projectCreationModes[(int)$catalogHolonId] = (string)($entry['project_creation_mode'] ?? '');
    foreach (array_keys($contextPermissions[DeferredProposal::TARGET_PROJECT]) as $catalogOperation) {
        if (!empty($entry['permissions'][$catalogOperation])) $contextPermissions[DeferredProposal::TARGET_PROJECT][$catalogOperation][] = (int)$catalogHolonId;
    }
}
foreach ([DeferredProposal::TARGET_RECURRING_TASK => $recurringTaskCatalog, DeferredProposal::TARGET_INDICATOR => $indicatorCatalog] as $catalogType => $catalog) {
    foreach ($catalog as $catalogHolonId => $entry) foreach (array_keys($contextPermissions[$catalogType]) as $catalogOperation) {
        if (!empty($entry['permissions'][$catalogOperation])) $contextPermissions[$catalogType][$catalogOperation][] = (int)$catalogHolonId;
    }
}
foreach ($contextPermissions as &$operationPermissions) {
    foreach ($operationPermissions as &$ids) $ids = array_values(array_unique(array_map('intval', $ids)));
    unset($ids);
}
unset($operationPermissions);
$payload = [
    'blueprint' => omoDecisionGovernanceBuildBlueprint($decision),
    'contextHolonId' => $targetHolonId,
    'rules' => array_values($ruleData),
    'roles' => array_values($roleData),
    'roleTemplates' => $roleTemplates,
    'authorities' => array_values($authorities),
    'responsibleLabels' => $responsibleLabels,
    'organizationId' => (int)$context['organizationId'],
    'decisionId' => $decision instanceof DecisionProcess ? (int)$decision->getId() : 0,
    'contextLabels' => $contextLabels,
    'contextPermissions' => $contextPermissions,
    'projectCreationModes' => $projectCreationModes,
    'defaultRuleState' => $defaultRuleState,
    'editable' => $canEdit,
    'aiEnabled' => $canUseAi,
    'texts' => [
        'proposalDefault' => omoDecisionGovernanceT('governance.proposal.default', ['index' => '__INDEX__']),
        'proposalRemove' => omoDecisionGovernanceT('governance.proposal.remove'),
        'proposalTitle' => omoDecisionGovernanceT('governance.proposal.title'),
        'proposalDescription' => omoDecisionGovernanceT('governance.proposal.description'),
        'summaryGenerate' => omoDecisionGovernanceT('governance.proposal.summary.generate'),
        'summaryLoading' => omoDecisionGovernanceT('governance.proposal.summary.loading'),
        'summaryEmpty' => omoDecisionGovernanceT('governance.proposal.summary.empty'),
        'summaryFailed' => omoDecisionGovernanceT('governance.proposal.summary.failed'),
        'summaryReady' => omoDecisionGovernanceT('governance.proposal.summary.ready'),
        'actionAdd' => omoDecisionGovernanceT('governance.action.add'),
        'actionMore' => omoDecisionGovernanceT('governance.action.more'),
        'actionEdit' => omoDecisionGovernanceT('governance.action.edit'),
        'actionRemove' => omoDecisionGovernanceT('governance.action.remove'),
        'ruleUpdate' => omoDecisionGovernanceT('governance.action.rule_update'),
        'ruleCreate' => omoDecisionGovernanceT('governance.action.rule_create'),
        'ruleDelete' => omoDecisionGovernanceT('governance.action.rule_delete'),
        'roleUpdate' => omoDecisionGovernanceT('governance.action.role_update'),
        'roleCreate' => omoDecisionGovernanceT('governance.action.role_create'),
        'roleDelete' => omoDecisionGovernanceT('governance.action.role_delete'),
        'projectUpdate' => omoDecisionGovernanceT('governance.action.project_update'),
        'projectCreate' => omoDecisionGovernanceT('governance.action.project_create'),
        'projectPropose' => omoDecisionGovernanceT('governance.action.project_propose'),
        'projectDelete' => omoDecisionGovernanceT('governance.action.project_delete'),
        'recurringTaskUpdate' => omoDecisionGovernanceT('governance.action.recurring_task_update'),
        'recurringTaskCreate' => omoDecisionGovernanceT('governance.action.recurring_task_create'),
        'recurringTaskDelete' => omoDecisionGovernanceT('governance.action.recurring_task_delete'),
        'indicatorUpdate' => omoDecisionGovernanceT('governance.action.indicator_update'),
        'indicatorCreate' => omoDecisionGovernanceT('governance.action.indicator_create'),
        'indicatorDelete' => omoDecisionGovernanceT('governance.action.indicator_delete'),
        'objectType' => omoDecisionGovernanceT('governance.action.object_type'),
        'context' => omoDecisionGovernanceT('governance.action.context'),
        'object' => omoDecisionGovernanceT('governance.action.object'),
        'openContext' => omoDecisionGovernanceT('governance.action.open_context'),
        'openEditor' => omoDecisionGovernanceT('governance.action.open_editor'),
        'emptyObjects' => omoDecisionGovernanceT('governance.action.empty'),
        'loading' => omoDecisionGovernanceT('governance.action.loading'),
        'pending' => omoDecisionGovernanceT('governance.status.pending'),
        'applied' => omoDecisionGovernanceT('governance.status.applied'),
        'rejected' => omoDecisionGovernanceT('governance.status.rejected'),
        'conflict' => omoDecisionGovernanceT('governance.status.conflict'),
        'failed' => omoDecisionGovernanceT('governance.status.failed'),
        'emptyRules' => omoDecisionGovernanceT('governance.empty.rules'),
        'genericError' => omoDecisionGovernanceT('governance.error.generic'),
        'saving' => omoDecisionGovernanceT('governance.saving'),
        'updateAction' => omoDecisionGovernanceT('governance.action.update'),
        'addAction' => omoDecisionGovernanceT('governance.action.apply'),
    ],
];
?>
<link rel="stylesheet" href="/common/choice/governance-actions.css?v=20260923-compact-editor">
<link rel="stylesheet" href="/common/choice/deferred-proposal-picker.css?v=20260923-shared">
<link rel="stylesheet" href="/common/choice/change-details.css?v=20260923-lifecycle-details">
<section class="omo-decision-governance generic-form-stack generic-form-stack--compact" data-governance-editor>
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape(omoDecisionGovernanceT($isEditing ? 'governance.title.edit' : 'governance.title.create')) ?>"
        data-omo-subdrawer-help="<?= omoApiEscape(omoDecisionGovernanceT('governance.intro')) ?>"
    >
        <?php if ($canEdit): ?>
        <button type="submit" form="omo-governance-editor-form" class="generic-action-button generic-action-button--main" data-governance-submit data-omo-subdrawer-action><?= omoApiEscape(omoDecisionGovernanceT($isEditing ? 'governance.update_short' : 'governance.save_short')) ?></button>
        <?php endif; ?>
    </div>

    <?php if ($isLocked): ?>
        <div class="generic-soft-panel"><?= omoApiEscape(omoDecisionGovernanceT('governance.error.locked')) ?></div>
    <?php endif; ?>

    <form id="omo-governance-editor-form" class="generic-form-stack generic-form-stack--compact" action="/omo/api/decision/governance/save.php" method="post" data-governance-form>
        <input type="hidden" name="oid" value="<?= (int)$context['organizationId'] ?>">
        <input type="hidden" name="cid" value="<?= $targetHolonId ?>">
        <input type="hidden" name="id" value="<?= $isEditing ? (int)$decision->getId() : 0 ?>">
        <input type="hidden" name="method" value="<?= omoApiEscape($governanceMethod) ?>">
        <input type="hidden" name="intent" value="manage">
        <input type="hidden" name="workflow" value="<?= omoApiEscape(DecisionProcess::WORKFLOW_GOVERNANCE) ?>">
        <textarea name="governance_blueprint" data-governance-blueprint hidden></textarea>

        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
            <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoDecisionGovernanceT('governance.section.identity')) ?></h3>
            <label class="generic-form-field">
                <span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.field.title')) ?></span>
                <input class="generic-form-control generic-form-control--compact" name="process_title" maxlength="190" required value="<?= omoApiEscape($isEditing ? (string)$decision->get('title') : '') ?>"<?= $canEdit ? '' : ' readonly' ?>>
            </label>
            <?php if ($canEdit && trim($processDescription) === ''): ?>
            <button type="button" class="omo-decision-governance__intention-link" data-governance-intention-open aria-expanded="false" aria-controls="omo-governance-intention"><?= omoApiEscape(omoDecisionGovernanceT('governance.field.intention.add')) ?></button>
            <?php endif; ?>
            <label id="omo-governance-intention" class="generic-form-field" data-governance-intention-field<?= trim($processDescription) === '' ? ' hidden' : '' ?>>
                <span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.field.intention')) ?></span>
                <textarea class="generic-form-control generic-form-control--compact" name="process_description" rows="3"<?= $canEdit ? '' : ' readonly' ?>><?= omoApiEscape($processDescription) ?></textarea>
            </label>
        </section>

        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
            <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoDecisionGovernanceT('governance.section.schedule')) ?></h3>
            <div class="generic-form-grid generic-form-grid--pair">
                <label class="generic-form-field">
                    <span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.field.consultation_end')) ?></span>
                    <input class="generic-form-control generic-form-control--compact" type="datetime-local" name="consultation_end_at" required value="<?= omoApiEscape($consultationEnd instanceof DateTimeInterface ? $consultationEnd->format('Y-m-d\TH:i') : '') ?>"<?= $canEdit ? '' : ' readonly' ?>>
                </label>
                <label class="generic-form-field">
                    <span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.field.vote_end')) ?></span>
                    <input class="generic-form-control generic-form-control--compact" type="datetime-local" name="evaluation_end_at" required value="<?= omoApiEscape($evaluationEnd instanceof DateTimeInterface ? $evaluationEnd->format('Y-m-d\TH:i') : '') ?>"<?= $canEdit ? '' : ' readonly' ?>>
                </label>
            </div>
            <?php if ($questionIsEditable): ?>
            <div class="generic-form-field">
                <div class="generic-heading-with-help">
                    <label class="generic-form-label" for="omo-governance-question"><?= omoApiEscape(omoDecisionGovernanceT($questionLabelKey)) ?></label>
                    <details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>
                        <summary aria-label="<?= omoApiEscape(omoDecisionGovernanceT($questionHelpKey)) ?>">?</summary>
                        <div class="generic-context-help__content"><?= omoApiEscape(omoDecisionGovernanceT($questionHelpKey)) ?></div>
                    </details>
                </div>
                <textarea class="generic-form-control generic-form-control--compact" id="omo-governance-question" name="consent_question" rows="2" maxlength="1000" required><?= omoApiEscape($questionValue) ?></textarea>
            </div>
            <?php else: ?>
            <div class="generic-soft-panel generic-soft-panel--stack">
                <strong class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT($questionLabelKey)) ?></strong>
                <span class="generic-help-text generic-help-text--regular"><?= omoApiEscape($questionValue) ?></span>
                <input type="hidden" name="consent_question" value="<?= omoApiEscape($questionValue) ?>">
            </div>
            <?php endif; ?>
        </section>

        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
            <div class="generic-heading-with-help">
                <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoDecisionGovernanceT('governance.proposals.title')) ?></h3>
                <details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>
                    <summary aria-label="<?= omoApiEscape(omoDecisionGovernanceT('governance.proposals.help')) ?>">?</summary>
                    <div class="generic-context-help__content"><?= omoApiEscape(omoDecisionGovernanceT('governance.proposals.help')) ?></div>
                </details>
            </div>
            <div class="omo-governance-proposals" data-governance-proposals></div>
            <?php if ($canEdit): ?><div><button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--compact" data-governance-proposal-add><span aria-hidden="true">+</span><?= omoApiEscape(omoDecisionGovernanceT('governance.proposal.add')) ?></button></div><?php endif; ?>
        </section>

        <p class="generic-feedback" data-governance-feedback hidden aria-live="polite"></p>
        <?php if ($canEdit): ?>
        <div class="generic-form-actions">
            <button type="submit" class="generic-action-button generic-action-button--main" data-governance-submit><?= omoApiEscape(omoDecisionGovernanceT($isEditing ? 'governance.update' : 'governance.save')) ?></button>
        </div>
        <?php endif; ?>
    </form>

    <?php foreach (['rule', 'project'] as $editorTargetType): ?>
    <template data-governance-fields="<?= $editorTargetType ?>">
        <section class="generic-section generic-section--stack generic-section--roomy">
            <form class="generic-form-stack" data-editor>
                <?php omoDeferredEditorRenderFields($editorTargetType, []); ?>
                <div class="generic-action-row">
                    <button class="generic-action-button generic-action-button--main" type="submit"><?= omoApiEscape(omoDeferredEditorT('save')) ?></button>
                    <button class="generic-action-button generic-action-button--secondary" type="button" data-cancel><?= omoApiEscape(omoDeferredEditorT('cancel')) ?></button>
                </div>
            </form>
        </section>
    </template>
    <?php endforeach; ?>
    <script type="application/json" data-governance-data><?= omoDecisionGovernanceEncodeJson($payload, '{}') ?></script>
</section>
<script src="/common/choice/word-diff.js?v=20260815"></script>
<script src="/common/choice/change-details.js?v=20260924-readable-diffs"></script>
<script src="/common/choice/governance-actions.js?v=20260924-shared-object-forms"></script>
<script>if(window.omoGovernanceEditorInit){window.omoGovernanceEditorInit(document);}</script>
