<?php

use dbObject\Authority;
use dbObject\ArrayAuthority;
use dbObject\DecisionProcess;
use dbObject\Rule;

require_once __DIR__ . '/shared.php';
require_once dirname(__DIR__) . '/params/shared.php';

$decision = ($context['decision'] ?? null) instanceof DecisionProcess ? $context['decision'] : null;
$currentUserId = (int)($context['currentUserId'] ?? 0);
$targetHolonId = (int)($context['targetHolonId'] ?? 0);
$targetHolon = ($context['effectiveHolon'] ?? null) instanceof \dbObject\Holon ? $context['effectiveHolon'] : null;
$isEditing = $decision instanceof DecisionProcess;
$isOwner = !$isEditing || (int)$decision->get('IDuser') === $currentUserId;
$isLocked = $isEditing && $decision->hasEvaluationStarted();
$canEdit = $isOwner && !$isLocked;
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
$payload = [
    'blueprint' => omoDecisionGovernanceBuildBlueprint($decision),
    'contextHolonId' => $targetHolonId,
    'rules' => array_values($ruleData),
    'roles' => array_values($roleData),
    'roleTemplates' => $roleTemplates,
    'authorities' => array_values($authorities),
    'defaultRuleState' => $defaultRuleState,
    'editable' => $canEdit,
    'texts' => [
        'proposalDefault' => omoDecisionGovernanceT('governance.proposal.default', ['index' => '__INDEX__']),
        'proposalRemove' => omoDecisionGovernanceT('governance.proposal.remove'),
        'proposalTitle' => omoDecisionGovernanceT('governance.proposal.title'),
        'proposalDescription' => omoDecisionGovernanceT('governance.proposal.description'),
        'actionAdd' => omoDecisionGovernanceT('governance.action.add'),
        'actionEdit' => omoDecisionGovernanceT('governance.action.edit'),
        'actionRemove' => omoDecisionGovernanceT('governance.action.remove'),
        'ruleUpdate' => omoDecisionGovernanceT('governance.action.rule_update'),
        'ruleCreate' => omoDecisionGovernanceT('governance.action.rule_create'),
        'ruleDelete' => omoDecisionGovernanceT('governance.action.rule_delete'),
        'roleUpdate' => omoDecisionGovernanceT('governance.action.role_update'),
        'roleCreate' => omoDecisionGovernanceT('governance.action.role_create'),
        'roleDelete' => omoDecisionGovernanceT('governance.action.role_delete'),
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
<link rel="stylesheet" href="/common/choice/governance-actions.css?v=20260813-ergonomics">
<link rel="stylesheet" href="/common/choice/change-details.css?v=20260816-2">
<section class="omo-decision-governance generic-section generic-section--stack" data-governance-editor>
    <div
        hidden
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape(omoDecisionGovernanceT($isEditing ? 'governance.title.edit' : 'governance.title.create')) ?>"
        data-omo-subdrawer-help="<?= omoApiEscape(omoDecisionGovernanceT('governance.intro')) ?>"
    ></div>

    <?php if ($isLocked): ?>
        <div class="generic-soft-panel"><?= omoApiEscape(omoDecisionGovernanceT('governance.error.locked')) ?></div>
    <?php endif; ?>

    <form class="generic-form-stack" action="/omo/api/decision/governance/save.php" method="post" data-governance-form>
        <input type="hidden" name="oid" value="<?= (int)$context['organizationId'] ?>">
        <input type="hidden" name="cid" value="<?= $targetHolonId ?>">
        <input type="hidden" name="id" value="<?= $isEditing ? (int)$decision->getId() : 0 ?>">
        <input type="hidden" name="method" value="<?= omoApiEscape($governanceMethod) ?>">
        <input type="hidden" name="intent" value="manage">
        <input type="hidden" name="workflow" value="<?= omoApiEscape(DecisionProcess::WORKFLOW_GOVERNANCE) ?>">
        <textarea name="governance_blueprint" data-governance-blueprint hidden></textarea>

        <section class="generic-section generic-section--stack generic-form-section">
            <label class="generic-form-field">
                <span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.field.title')) ?></span>
                <input class="generic-form-control" name="process_title" maxlength="190" required value="<?= omoApiEscape($isEditing ? (string)$decision->get('title') : '') ?>"<?= $canEdit ? '' : ' readonly' ?>>
            </label>
            <label class="generic-form-field">
                <span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.field.intention')) ?></span>
                <textarea class="generic-form-control" name="process_description" rows="5" required<?= $canEdit ? '' : ' readonly' ?>><?= omoApiEscape($isEditing ? (string)$decision->get('description') : '') ?></textarea>
            </label>
            <div class="generic-form-grid">
                <label class="generic-form-field">
                    <span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.field.consultation_end')) ?></span>
                    <input class="generic-form-control" type="datetime-local" name="consultation_end_at" required value="<?= omoApiEscape($consultationEnd instanceof DateTimeInterface ? $consultationEnd->format('Y-m-d\TH:i') : '') ?>"<?= $canEdit ? '' : ' readonly' ?>>
                </label>
                <label class="generic-form-field">
                    <span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.field.vote_end')) ?></span>
                    <input class="generic-form-control" type="datetime-local" name="evaluation_end_at" required value="<?= omoApiEscape($evaluationEnd instanceof DateTimeInterface ? $evaluationEnd->format('Y-m-d\TH:i') : '') ?>"<?= $canEdit ? '' : ' readonly' ?>>
                </label>
            </div>
            <?php if ($questionIsEditable): ?>
            <div class="generic-form-field">
                <div class="generic-heading-with-help">
                    <label class="generic-form-label" for="omo-governance-question"><?= omoApiEscape(omoDecisionGovernanceT($questionLabelKey)) ?></label>
                    <details class="generic-context-help">
                        <summary aria-label="<?= omoApiEscape(omoDecisionGovernanceT($questionHelpKey)) ?>">?</summary>
                        <div class="generic-context-help__content"><?= omoApiEscape(omoDecisionGovernanceT($questionHelpKey)) ?></div>
                    </details>
                </div>
                <textarea class="generic-form-control" id="omo-governance-question" name="consent_question" rows="3" maxlength="1000" required><?= omoApiEscape($questionValue) ?></textarea>
            </div>
            <?php else: ?>
            <div class="omo-decision-governance__question">
                <strong><?= omoApiEscape(omoDecisionGovernanceT($questionLabelKey)) ?></strong>
                <span><?= omoApiEscape($questionValue) ?></span>
                <input type="hidden" name="consent_question" value="<?= omoApiEscape($questionValue) ?>">
            </div>
            <?php endif; ?>
        </section>

        <section class="generic-section generic-section--stack generic-form-section">
            <div class="generic-heading-with-help">
                <h4 class="generic-card-title"><?= omoApiEscape(omoDecisionGovernanceT('governance.proposals.title')) ?></h4>
                <details class="generic-context-help">
                    <summary aria-label="<?= omoApiEscape(omoDecisionGovernanceT('governance.proposals.help')) ?>">?</summary>
                    <div class="generic-context-help__content"><?= omoApiEscape(omoDecisionGovernanceT('governance.proposals.help')) ?></div>
                </details>
            </div>
            <div class="omo-governance-proposals" data-governance-proposals></div>
            <?php if ($canEdit): ?><button type="button" class="generic-action-button generic-action-button--secondary" data-governance-proposal-add><?= omoApiEscape(omoDecisionGovernanceT('governance.proposal.add')) ?></button><?php endif; ?>
        </section>

        <p class="generic-feedback" data-governance-feedback hidden aria-live="polite"></p>
        <?php if ($canEdit): ?>
        <div class="generic-form-actions">
            <button type="submit" class="generic-action-button generic-action-button--main" data-governance-submit><?= omoApiEscape(omoDecisionGovernanceT($isEditing ? 'governance.update' : 'governance.save')) ?></button>
        </div>
        <?php endif; ?>
    </form>

    <script type="application/json" data-governance-data><?= omoDecisionGovernanceEncodeJson($payload, '{}') ?></script>
</section>
<script src="/common/choice/word-diff.js?v=20260815"></script>
<script src="/common/choice/change-details.js?v=20260816-governance-details"></script>
<script src="/common/choice/governance-actions.js?v=20260816-property-match"></script>
<script>if(window.omoGovernanceEditorInit){window.omoGovernanceEditorInit(document);}</script>
