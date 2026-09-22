<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/arraydbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/arraydeferredproposal.class.php';
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
    DeferredProposal::normalizeState('{"title":"Proposition"}') === ['title' => 'Proposition'],
    'JSON proposal states must be decoded.'
);
assertDeferredProposal(
    DeferredProposal::normalizeState('invalid') === [],
    'Invalid proposal states must fail closed.'
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
        'proposalsTitle' => 'Propositions',
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
        'proposalsTitle' => 'Propositions',
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
        && strpos($pvRuleEditor, 'Enregistrer la proposition') !== false
        && strpos($pvRuleSave, "\$_POST['proposal_id']") !== false,
    'Pending PV proposals must reopen in their editor and save back into the same proposal.'
);
assertDeferredProposal(
    strpos($pvRuleEditor, 'DecisionGovernanceAction::captureRuleState') !== false
        && strpos($pvRuleEditor, 'populateFromSelectedRule') !== false
        && strpos($pvRuleEditor, "['title','intention','description','review_date','expiration_date']") !== false,
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

echo "deferred_proposal_test: OK\n";
