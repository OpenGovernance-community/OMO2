<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/arraydbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/arraydeferredproposal.class.php';
require_once dirname(__DIR__) . '/class/dbobject/recurrenceschedule.class.php';
require_once dirname(__DIR__) . '/class/dbobject/controltask.class.php';
require_once dirname(__DIR__) . '/class/dbobject/controlactivity.class.php';
require_once dirname(__DIR__) . '/class/dbobject/statindicator.class.php';
require_once dirname(__DIR__) . '/class/dbobject/propertyformat.class.php';
require_once dirname(__DIR__) . '/class/dbobject/property.class.php';
require_once dirname(__DIR__) . '/class/dbobject/organization.class.php';
require_once dirname(__DIR__) . '/class/dbobject/deferredproposal.class.php';
require_once dirname(__DIR__) . '/omo/api/documents/pv/helpers.php';

use dbObject\DeferredProposal;

function assertDeferredProposal(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$catalog = DeferredProposal::getTargetCatalog();
assertDeferredProposal(isset($catalog[DeferredProposal::TARGET_RULE]), 'Rules must be available to deferred proposals.');
assertDeferredProposal(isset($catalog[DeferredProposal::TARGET_PROJECT]), 'Projects must have a reserved deferred-proposal target.');
assertDeferredProposal(
    !empty($catalog[DeferredProposal::TARGET_RECURRING_TASK]['implemented'])
        && !empty($catalog[DeferredProposal::TARGET_INDICATOR]['implemented']),
    'Recurring tasks and indicators must be available to deferred proposals.'
);
assertDeferredProposal(
    DeferredProposal::getRecurringTaskOperationPermissionKey(DeferredProposal::OPERATION_CREATE) === 'CAN_CREATE_RECURRING_TASK'
        && DeferredProposal::getIndicatorOperationPermissionKey(DeferredProposal::OPERATION_DELETE) === 'CAN_DELETE_INDICATOR',
    'New deferred targets must use their dedicated collective CRUD permissions.'
);
$recurringState = DeferredProposal::normalizeRecurringTaskState([
    'title' => 'Revue hebdomadaire', 'frequency' => 'weekly', 'schedule' => '2',
    'display_lead_value' => 1, 'display_lead_unit' => 'day',
    'execution_duration_value' => 2, 'execution_duration_unit' => 'day',
]);
assertDeferredProposal(
    $recurringState['title'] === 'Revue hebdomadaire' && $recurringState['frequency'] === 'weekly' && $recurringState['schedule'] === '2',
    'Recurring task proposal states must be normalized.'
);
$indicatorState = DeferredProposal::normalizeIndicatorState([
    'name' => 'Trésorerie', 'source_type' => 'manual', 'reference_type' => 'ceiling', 'ceiling_value' => '100',
]);
assertDeferredProposal(
    $indicatorState['name'] === 'Trésorerie'
        && $indicatorState['reference_type'] === 'ceiling'
        && count($indicatorState['reference_points']) === 2,
    'Indicator proposal states must retain their measurement and reference configuration.'
);
$indicatorEditorState = DeferredProposal::normalizeIndicatorEditorState([
    'name' => 'Progression', 'source_type' => 'manual', 'reference_type' => 'objective',
    'reference_points' => [
        ['position_percent' => '0', 'value' => '10', 'point_at' => '2026-01-01T10:00'],
        ['position_percent' => '50', 'value' => '20', 'point_at' => ''],
        ['position_percent' => '100', 'value' => '30', 'point_at' => '2026-01-03T10:00'],
    ],
], 1);
assertDeferredProposal(
    count($indicatorEditorState['reference_points']) === 3
        && $indicatorEditorState['reference_points'][1]['point_at'] === '2026-01-02 10:00:00',
    'The shared indicator editor must retain and date its objective points.'
);
assertDeferredProposal(
    DeferredProposal::normalizeState('{"title":"Proposition"}') === ['title' => 'Proposition'],
    'JSON proposal states must be decoded.'
);
assertDeferredProposal(
    DeferredProposal::normalizeState('invalid') === [],
    'Invalid proposal states must fail closed.'
);
$displayOrganization = new \dbObject\Organization();
$authorityDisplay = $displayOrganization->getHolonEditorListDisplayItems(
    '[{"id":17,"label":"Comptabilité"},{"id":18,"delete":true}]', 2, 'authority'
);
assertDeferredProposal(
    $authorityDisplay === [['id' => 17, 'label' => 'Comptabilité']],
    'Holon authority lists must expose readable labels and omit deleted draft entries.'
);
$decoratedHolon = DeferredProposal::decorateHolonListDisplayState([
    'editor_payload' => ['properties' => [
        ['name' => 'Mandats', 'formatId' => 2, 'listItemType' => 'text', 'value' => '["Accueil","Comptes"]'],
    ]],
], $displayOrganization);
assertDeferredProposal(
    $decoratedHolon['editor_payload']['properties'][0]['displayItems'] === ['Accueil', 'Comptes'],
    'Holon list display data must be derived without changing the stored value.'
);
assertDeferredProposal(
    $displayOrganization->getHolonEditorListDisplayItems('{"before":"Intro","items":["Un","Deux"],"after":"Fin"}', 7, 'text') === ['Un', 'Deux'],
    'Composite HTML lists must compare their items separately from the surrounding text.'
);
$presentationProposal = new DeferredProposal();
$presentationProposal->set('target_type', DeferredProposal::TARGET_RULE);
$presentationProposal->set('operation', DeferredProposal::OPERATION_UPDATE);
$presentationProposal->set('status', DeferredProposal::STATUS_PENDING);
$presentationProposal->set('before_state', ['title' => 'Ancienne règle', 'description' => 'Ancien contenu']);
$presentationProposal->set('after_state', ['title' => 'Nouvelle règle', 'description' => 'Nouveau contenu']);
$presentation = $presentationProposal->buildPresentationData();
assertDeferredProposal(
    ($presentation['title'] ?? '') === 'Nouvelle règle'
        && ($presentation['excerpt'] ?? '') === 'Nouveau contenu'
        && ($presentation['changedFieldCount'] ?? 0) === 2,
    'Deferred proposals must expose a readable, target-neutral presentation summary.'
);
$presentation['holonLabel'] = 'Cercle Produit';
$presentationHtml = omoDocumentsPvEditorRenderDeferredProposals(
    [
        'id' => 42,
        'organizationId' => 7,
        'canAddDeferredProposal' => true,
        'canManageDeferredProposals' => true,
        'deferredProposals' => [$presentation],
    ],
    [
        'proposalsTitle' => 'Modifications',
        'proposalOperationUpdate' => 'Modification',
        'proposalStatusPending' => 'En attente',
        'proposalChangedFields' => 'Champs modifiés : {count}',
    ]
);
assertDeferredProposal(
    strpos($presentationHtml, 'Modification : Règle « Nouvelle règle »') !== false
        && strpos($presentationHtml, 'Cercle Produit') !== false
        && strpos($presentationHtml, 'Nouveau contenu') !== false
        && strpos($presentationHtml, 'En attente') !== false
        && strpos($presentationHtml, 'data-omo-deferred-proposal-toggle') !== false
        && strpos($presentationHtml, 'data-omo-deferred-proposal-menu-toggle') !== false
        && strpos($presentationHtml, 'data-omo-deferred-proposal-edit') !== false
        && strpos($presentationHtml, 'pv_rule_editor.php?workflow=1&amp;direct=1') !== false
        && strpos($presentationHtml, 'data-omo-deferred-proposal-delete') !== false
        && strpos($presentationHtml, 'data-omo-pv-point-add-proposal') > strpos($presentationHtml, 'data-deferred-proposal-id'),
    'PV proposal summaries must include their action, target, content excerpt, and status.'
);
$holonPresentation = $presentation;
$holonPresentation['targetType'] = DeferredProposal::TARGET_HOLON;
$holonPresentation['targetLabel'] = 'Rôle ou espace';
$holonPresentation['objectTypeLabel'] = 'Rôle';
$holonPresentation['operation'] = DeferredProposal::OPERATION_CREATE;
$holonPresentation['title'] = 'Coordination';
$holonPresentationHtml = omoDocumentsPvEditorRenderDeferredProposals(
    [
        'id' => 43,
        'organizationId' => 7,
        'canAddDeferredProposal' => false,
        'canManageDeferredProposals' => true,
        'deferredProposals' => [$holonPresentation],
    ],
    [
        'proposalsTitle' => 'Modifications',
        'proposalOperationCreate' => 'Création',
        'proposalStatusPending' => 'En attente',
    ]
);
assertDeferredProposal(
    strpos($holonPresentationHtml, 'Création d’un élément de type rôle « Coordination »') !== false
        && strpos($holonPresentationHtml, 'pv_holon_editor.php?stage=capture&amp;direct=1') !== false,
    'Holon proposal summaries must identify the concrete structural type.'
);

$pvAction = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/action.php');
$pvHelpers = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/helpers.php');
$pvEditor = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/editor.php');
$pvRuleEditor = (string)file_get_contents(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_rule_editor.php');
$pvRuleContext = (string)file_get_contents(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_rule_context.php');
$pvRuleSave = (string)file_get_contents(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_rule_save.php');
$pvProposalPicker = (string)file_get_contents(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_proposal_picker.php');
$pvProposalContext = (string)file_get_contents(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_proposal_context.php');
$pvHolonEditor = (string)file_get_contents(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_holon_editor.php');
$pvHolonSave = (string)file_get_contents(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_holon_save.php');
$pvProjectEditor = (string)file_get_contents(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_project_editor.php');
$pvProjectSave = (string)file_get_contents(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_project_save.php');
$deferredProposalSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/deferredproposal.class.php');
$holonScopePicker = (string)file_get_contents(dirname(__DIR__) . '/common/holon_scope_picker.js');
$holonCreate = (string)file_get_contents(dirname(__DIR__) . '/omo/api/holons/create.php');
$pvProposalPicker .= (string)file_get_contents(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_proposal_picker.js');
$pvHolonEditor .= (string)file_get_contents(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_holon_editor.js');
$pvRuleEditor .= (string)file_get_contents(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_rule_editor.js');
$pvEditor .= (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/editor.js');
$holonCreate .= (string)file_get_contents(dirname(__DIR__) . '/omo/api/holons/editor.js');
$holonEditorCss = (string)file_get_contents(dirname(__DIR__) . '/omo/api/holons/editor.css');
$toggleStart = strpos($pvAction, "if (\$action === 'toggle_handled')");
$toggleEnd = strpos($pvAction, "if (\$action === 'reorder_points')", $toggleStart);
$toggleSource = $toggleStart !== false && $toggleEnd !== false ? substr($pvAction, $toggleStart, $toggleEnd - $toggleStart) : '';
assertDeferredProposal(
    strpos($toggleSource, 'DeferredProposal::applyForPvPoint') !== false
        && strpos($toggleSource, 'beginTransaction()') !== false
        && strpos($toggleSource, 'commit()') !== false,
    'Handling a PV point must apply its pending deferred proposals in the same transaction.'
);
assertDeferredProposal(
    strpos($pvProposalPicker, 'pv_holon_editor.php') !== false
        && strpos($pvProposalPicker, 'pv_rule_editor.php?workflow=1') !== false
        && strpos($pvProposalPicker, 'pv_project_editor.php?stage=capture') !== false
        && strpos($pvProposalPicker, 'pv_proposal_context.php') !== false
        && strpos($pvProposalPicker, 'commonTopbarRefreshModalContent') !== false
        && strpos($pvProposalPicker, 'commonTopbarOpenModal') === false
        && strpos($pvProposalPicker, 'data-deferred-target-type') !== false
        && strpos($pvProposalPicker, 'data-deferred-editor-host') === false
        && strpos($pvHolonEditor, 'omo-holon-governance-capture') !== false
        && strpos($pvHolonEditor, '/omo/images/tools/connection.png') !== false
        && strpos($pvHolonEditor, 'omoMountHolonScopePicker') !== false
        && strpos($pvHolonSave, 'loadAllowedHolonTargetHolon') !== false
        && strpos($pvHolonSave, 'getPvContextHolonId') !== false,
    'PV points must offer deferred rule, structural, and project proposals through their collective workflow editors.'
);
assertDeferredProposal(
    strpos($pvProposalContext, 'loadAllowedRuleTargetHolon') !== false
        && strpos($pvProposalContext, 'getHolonTargetHolonCatalog') !== false
        && strpos($pvProposalContext, 'loadAllowedProjectTargetHolon') !== false
        && strpos($pvHelpers, 'pv_proposal_picker.php?oid=') !== false,
    'The unified proposal workflow must load rule, holon, and project choices before replacing the popup content with the editor.'
);
assertDeferredProposal(
    strpos($deferredProposalSource, 'getProjectOperationPermissionKey') !== false
        && strpos($deferredProposalSource, 'CAN_CREATE_PROJECT') !== false
        && strpos($deferredProposalSource, 'CAN_PROPOSE_PROJECT') !== false
        && strpos($deferredProposalSource, "'project_creation_mode'") !== false
        && strpos($deferredProposalSource, 'captureProjectState') !== false
        && strpos($deferredProposalSource, 'applyProjectProposal') !== false
        && strpos($pvProjectSave, 'loadAllowedProjectTargetHolon') !== false
        && strpos($pvProjectEditor, 'data-deferred-project-editor') !== false,
    'Deferred project proposals must use collective create or propose permissions, retain a snapshot, and apply through the generic proposal executor.'
);
assertDeferredProposal(
    strpos($deferredProposalSource, 'getHolonOperationPermissionKey') !== false
        && strpos($deferredProposalSource, 'CAN_ADD_HOLON') !== false
        && strpos($deferredProposalSource, 'getHolonTargetHolonCatalog') !== false,
    'Deferred holon proposals must map each operation to its collective structural permission.'
);
assertDeferredProposal(
    strpos($pvHolonSave, 'captureHolonEditorState') !== false
        && strpos($deferredProposalSource, "['editor_payload']") !== false,
    'Deferred holon updates must retain their editor snapshot for property-level change details.'
);
assertDeferredProposal(
    strpos($holonCreate, 'omo-holon-create--governance-capture') !== false
        && strpos($holonEditorCss, '.omo-holon-create--governance-capture') !== false
        && strpos($holonEditorCss, 'background: var(--color-surface, #ffffff)') !== false,
    'The embedded holon editor must use the modal surface instead of the full-screen application background.'
);
assertDeferredProposal(
    strpos($holonCreate, 'Object.keys(governanceInitialPayloadCandidate).length > 0') !== false,
    'An empty deferred snapshot must not replace the existing holon data when starting an update proposal.'
);
assertDeferredProposal(
    strpos($pvRuleEditor, 'omo-deferred-proposal-saved') !== false
        && strpos($pvRuleEditor, 'window.location.reload()') === false
        && strpos($pvEditor, "postPointAction('refresh_point', pointId)") !== false,
    'Saving a proposal must refresh only its PV point without reloading the page.'
);
assertDeferredProposal(
    strpos($pvHelpers, 'data-omo-pv-point-add-proposal') !== false
        && strpos($pvEditor, 'data-omo-pv-point-add-proposal') !== false
        && strpos($pvHelpers, 'omoDocumentsPvEditorRenderDeferredProposals') !== false
        && strpos($pvHelpers, 'data-omo-pv-point-proposals') !== false
        && strpos($pvEditor, "postPointAction('remove_deferred_proposal'") !== false
        && strpos($pvAction, "if (\$action === 'remove_deferred_proposal')") !== false,
    'PV points must expose the deferred-proposal action and their attached proposal summaries.'
);
assertDeferredProposal(
    strpos($pvRuleEditor, "\$_GET['proposal_id']") !== false
        && strpos($pvRuleEditor, 'Enregistrer la modification') !== false
        && strpos($pvRuleSave, "\$_POST['proposal_id']") !== false,
    'Pending PV proposals must reopen in their editor and save back into the same proposal.'
);
assertDeferredProposal(
    strpos($pvRuleEditor, 'DecisionGovernanceAction::captureRuleState') !== false
        && strpos($pvRuleEditor, 'populateFromSelectedRule') !== false
        && strpos($pvRuleEditor, "['title','intention','description','review_date','expiration_date','scope','IDauthority']") !== false,
    'Selecting a rule for an update proposal must initialise all editable rule fields.'
);
assertDeferredProposal(
    strpos($pvRuleEditor, 'data-deferred-holon-label') !== false
        && strpos($pvRuleEditor, 'readonly data-deferred-holon-label') !== false
        && strpos($pvRuleEditor, '/omo/images/tools/connection.png') !== false
        && strpos($pvRuleEditor, 'omoMountHolonScopePicker') !== false
        && strpos($pvRuleEditor, 'selectableHolonIds:allowedIds()') !== false
        && strpos($pvRuleEditor, 'pv_rule_context.php') !== false,
    'Rule proposals must offer the shared circular holon picker and reload target rules.'
);
assertDeferredProposal(
    strpos($deferredProposalSource, 'buildHolonCollectivePermissionSetForOrganization') !== false
        && strpos($deferredProposalSource, 'holonHasCollectivePermissionForHolonContext') !== false
        && strpos($pvRuleEditor, 'getPvContextHolonId') !== false
        && strpos($pvRuleSave, 'getPvContextHolonId') !== false
        && strpos($pvRuleContext, 'getPvContextHolonId') !== false
        && strpos($pvRuleSave, 'loadAllowedRuleTargetHolon') !== false
        && strpos($pvRuleContext, 'loadAllowedRuleTargetHolon') !== false,
    'Holon targets must be filtered and validated with the PV collective permissions on both server endpoints.'
);
assertDeferredProposal(
    strpos($deferredProposalSource, '$proposalHolonId > 0 ? $proposalHolonId : $contextHolonId') !== false,
    'Applying a deferred proposal must prefer its own holon over the collective-space context.'
);
assertDeferredProposal(
    strpos($holonScopePicker, "node.isSelectable !== false && typeof onSelect") !== false,
    'Disabled holons in the shared structure picker must not be selectable.'
);
assertDeferredProposal(
    is_file(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_rule_editor.php')
        && is_file(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_rule_context.php')
        && is_file(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_rule_save.php')
        && is_file(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_holon_editor.php')
        && is_file(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_holon_save.php')
        && is_file(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_project_editor.php')
        && is_file(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_project_save.php')
        && is_file(dirname(__DIR__) . '/omo/api/deferred_proposals/pv_proposal_context.php'),
    'The PV must provide deferred editors and save endpoints for rules and holons.'
);

$governanceEdit = (string)file_get_contents(dirname(__DIR__) . '/omo/api/decision/governance/edit.php');
$governanceSave = (string)file_get_contents(dirname(__DIR__) . '/omo/api/decision/governance/save.php');
$governanceContext = (string)file_get_contents(dirname(__DIR__) . '/omo/api/decision/governance/proposal_context.php');
$governanceJs = (string)file_get_contents(dirname(__DIR__) . '/common/choice/governance-actions.js');
$decisionProcessSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/decisionprocess.class.php');
assertDeferredProposal(
    strpos($governanceEdit, 'contextPermissions') !== false
        && strpos($governanceJs, 'proposal_context.php') !== false
        && strpos($governanceJs, 'omoMountHolonScopePicker') !== false
        && strpos($governanceJs, 'data-type="project"') !== false
        && strpos($governanceJs, 'data-type="recurring_task"') !== false
        && strpos($governanceJs, 'data-type="indicator"') !== false
        && strpos($governanceJs, '<details class="generic-accordion omo-governance-action__accordion"') !== false,
    'Governance alternatives must use the unified deferred-proposal picker, collective holon navigation, projects, and accordion rows.'
);
assertDeferredProposal(
    strpos($governanceContext, 'loadAllowedRuleTargetHolon') !== false
        && strpos($governanceContext, 'getHolonTargetHolonCatalog') !== false
        && strpos($governanceContext, 'loadAllowedProjectTargetHolon') !== false
        && strpos($governanceContext, 'loadAllowedObjectTargetHolon') !== false
        && strpos($governanceSave, "set('IDdecision_proposal', \$proposalId)") !== false
        && strpos($governanceSave, "set('IDdocument_pv_point', null)") !== false,
    'Governance deferred proposals must be validated with the decision holon collective rights and attached to the selected alternative.'
);
assertDeferredProposal(
    strpos($decisionProcessSource, 'DeferredProposal::applyAcceptedForDecision($this)') !== false,
    'Accepted governance alternatives must apply their deferred proposals when results are finalized.'
);

require_once dirname(__DIR__) . '/omo/api/decision/governance/shared.php';
$governanceLabels = omoDecisionGovernanceGetSourceLang();
assertDeferredProposal(
    $governanceLabels['governance.proposal.add']['text'] === 'Ajouter une proposition'
        && $governanceLabels['governance.proposal.remove']['text'] === 'Retirer la proposition'
        && $governanceLabels['governance.proposal.title']['text'] === 'Titre de la proposition'
        && $governanceLabels['governance.proposal.description']['text'] === 'Description de la proposition'
        && $governanceLabels['governance.action.add']['text'] === 'Ajouter une modification'
        && $governanceLabels['governance.action.more']['text'] === 'Actions de la modification',
    'Ballot proposals must retain their name while individual changes are called modifications.'
);
$pvLabels = omoDocumentsPvEditorSourceLang();
assertDeferredProposal(
    $pvLabels['documents.pv_editor.action.add_proposal']['text'] === 'Ajouter une modification'
        && $pvLabels['documents.pv_editor.proposals.title']['text'] === 'Modifications'
        && $pvLabels['documents.pv_editor.proposals.edit_title']['text'] === 'Éditer une modification',
    'PV changes must use the same terminology as changes inside ballot proposals.'
);

echo "deferred_proposal_test: OK\n";
