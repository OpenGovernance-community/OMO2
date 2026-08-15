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
$questionIsEditable = !$isEditing && $configuredQuestion === '';
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
    'rules' => array_values($ruleData),
    'authorities' => array_values($authorities),
    'defaultRuleState' => $defaultRuleState,
    'editable' => $canEdit,
    'texts' => [
        'proposalDefault' => omoDecisionGovernanceT('governance.proposal.default', ['index' => '__INDEX__']),
        'proposalRemove' => omoDecisionGovernanceT('governance.proposal.remove'),
        'proposalTitle' => omoDecisionGovernanceT('governance.proposal.title'),
        'actionAdd' => omoDecisionGovernanceT('governance.action.add'),
        'actionEdit' => omoDecisionGovernanceT('governance.action.edit'),
        'actionRemove' => omoDecisionGovernanceT('governance.action.remove'),
        'ruleUpdate' => omoDecisionGovernanceT('governance.action.rule_update'),
        'ruleCreate' => omoDecisionGovernanceT('governance.action.rule_create'),
        'ruleDelete' => omoDecisionGovernanceT('governance.action.rule_delete'),
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
<section class="omo-decision-governance generic-section generic-section--stack" data-governance-editor>
    <div class="generic-hero-panel accent">
        <div class="generic-heading-with-help">
            <h3 class="generic-card-title"><?= omoApiEscape(omoDecisionGovernanceT($isEditing ? 'governance.title.edit' : 'governance.title.create')) ?></h3>
            <details class="generic-context-help">
                <summary aria-label="<?= omoApiEscape(omoDecisionGovernanceT('governance.intro')) ?>">?</summary>
                <div class="generic-context-help__content"><?= omoApiEscape(omoDecisionGovernanceT('governance.intro')) ?></div>
            </details>
        </div>
    </div>

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
                <textarea class="generic-form-control" id="omo-governance-question" name="consent_question" rows="3" maxlength="1000" required<?= $canEdit ? '' : ' readonly' ?>></textarea>
            </div>
            <?php else: ?>
            <div class="omo-decision-governance__question">
                <strong><?= omoApiEscape(omoDecisionGovernanceT($questionLabelKey)) ?></strong>
                <span><?= omoApiEscape($question !== '' ? $question : omoDecisionGovernanceT('governance.question.default')) ?></span>
                <input type="hidden" name="consent_question" value="<?= omoApiEscape($question !== '' ? $question : omoDecisionGovernanceT('governance.question.default')) ?>">
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

    <div class="omo-governance-modal" data-governance-modal hidden>
        <button type="button" class="omo-governance-modal__backdrop" data-governance-modal-close aria-label="<?= omoApiEscape(omoDecisionGovernanceT('governance.action.cancel')) ?>"></button>
        <div class="omo-governance-modal__panel generic-section generic-section--stack" role="dialog" aria-modal="true">
            <div class="generic-drawer-header">
                <div class="generic-drawer-header__copy"><h4 class="generic-card-title" data-governance-modal-title><?= omoApiEscape(omoDecisionGovernanceT('governance.action.choose')) ?></h4></div>
                <div class="generic-drawer-header__actions"><button type="button" class="generic-action-button generic-action-button--secondary" data-governance-modal-close><?= omoApiEscape(omoDecisionGovernanceT('governance.action.cancel')) ?></button></div>
            </div>
            <div data-governance-action-chooser>
                <div class="generic-action-row">
                    <button type="button" class="generic-action-button generic-action-button--main" data-governance-choose-rule-create><?= omoApiEscape(omoDecisionGovernanceT('governance.action.rule_create')) ?></button>
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-governance-choose-rule-update<?= count($rules) > 0 ? '' : ' disabled' ?>><?= omoApiEscape(omoDecisionGovernanceT('governance.action.rule_update')) ?></button>
                    <button type="button" class="generic-action-button generic-action-button--danger" data-governance-choose-rule-delete<?= count($rules) > 0 ? '' : ' disabled' ?>><?= omoApiEscape(omoDecisionGovernanceT('governance.action.rule_delete')) ?></button>
                </div>
            </div>
            <div class="generic-form-stack" data-governance-rule-editor hidden>
                <label class="generic-form-field" data-governance-rule-select-field><span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.action.rule')) ?></span><select class="generic-form-control" data-governance-rule-select></select></label>
                <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.action.authority')) ?></span><select class="generic-form-control" data-governance-authority-select><option value="0"><?= omoApiEscape(omoDecisionGovernanceT('governance.action.local_rule')) ?></option><?php foreach ($authorities as $authority): ?><option value="<?= (int)$authority['id'] ?>"><?= omoApiEscape($authority['label']) ?></option><?php endforeach; ?></select></label>
                <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.field.title')) ?></span><input class="generic-form-control" maxlength="255" data-governance-rule-title required></label>
                <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.action.intention')) ?></span><div data-governance-html-field="intention"></div></label>
                <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.action.content')) ?></span><div data-governance-html-field="description"></div></label>
                <div class="generic-form-grid">
                    <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.action.review_date')) ?></span><input class="generic-form-control" type="date" data-governance-rule-review required></label>
                    <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.action.expiration_date')) ?></span><input class="generic-form-control" type="date" data-governance-rule-expiration required></label>
                </div>
                <div class="generic-form-actions"><button type="button" class="generic-action-button generic-action-button--main" data-governance-action-apply><?= omoApiEscape(omoDecisionGovernanceT('governance.action.apply')) ?></button></div>
            </div>
            <div class="generic-form-stack" data-governance-rule-delete-editor hidden>
                <label class="generic-form-field"><span class="generic-form-label"><?= omoApiEscape(omoDecisionGovernanceT('governance.action.rule')) ?></span><select class="generic-form-control" data-governance-delete-rule-select></select></label>
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <p><?= omoApiEscape(omoDecisionGovernanceT('governance.action.delete_help')) ?></p>
                    <div data-governance-delete-preview></div>
                </div>
                <div class="generic-form-actions"><button type="button" class="generic-action-button generic-action-button--danger" data-governance-delete-apply><?= omoApiEscape(omoDecisionGovernanceT('governance.action.confirm_delete')) ?></button></div>
            </div>
        </div>
    </div>
    <script type="application/json" data-governance-data><?= omoDecisionGovernanceEncodeJson($payload, '{}') ?></script>
</section>
<script src="/common/choice/governance-actions.js?v=20260813"></script>
<script>if(window.omoGovernanceEditorInit){window.omoGovernanceEditorInit(document);}</script>
