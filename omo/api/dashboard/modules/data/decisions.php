<?php
use dbObject\DecisionParticipant;
use dbObject\DecisionProcess;
use dbObject\Organization;
use dbObject\User;

$dashboardDecisionItems = array();
$dashboardDecisionCounts = array('active' => 0, 'elaboration' => 0, 'pending' => 0);
if (!empty($enabledAppHashes['decision'])) {
    // Reuse the same organization list and lifecycle updates for repeated modules.
    if (!isset($dashboardDecisionRows)) {
        $dashboardDecisionUserEmail = '';
        $dashboardDecisionUser = new User();
        if ($currentUserId > 0 && $dashboardDecisionUser->load($currentUserId)) {
            $dashboardDecisionUserEmail = trim(mb_strtolower((string)$dashboardDecisionUser->getScopedEmail($currentOrganizationId), 'UTF-8'));
        }
        $dashboardDecisionRows = DecisionProcess::fetchListRowsForOrganization($currentOrganizationId, $currentUserId, $dashboardDecisionUserEmail);
        $dashboardDecisionPendingGroups = array();
    }
    foreach ($dashboardDecisionRows as $row) {
        $decisionHolonId = (int)($row['IDholon'] ?? 0);
        if ($decisionHolonId > 0 ? !isset($dashboardModuleScopeHolonIdMap[$decisionHolonId]) : !$dashboardIsOrganizationHolon) {
            continue;
        }
        $decision = new DecisionProcess();
        $decision->hydrateFromDatabaseRow($row, true);
        $status = DecisionProcess::normalizeStatus($decision->get('status'));
        if (
            in_array($status, [DecisionProcess::STATUS_RESULTS, DecisionProcess::STATUS_ARCHIVED], true)
            || ($decision->isGovernanceWorkflow() && $organization->getInterfaceLevel() < Organization::INTERFACE_LEVEL_AUTONOMOUS)
        ) {
            continue;
        }
        $isOwner = $currentUserId > 0 && (int)$decision->get('IDuser') === $currentUserId;
        $hasParticipation = !empty($row['has_user_participation']) || !empty($row['has_email_participation']);
        $canView = $isOwner || $hasParticipation
            || $decision->canUseManagementPermission('CAN_EDIT_DECISION', $currentUserId)
            || $decision->canUseManagementPermission('CAN_DELETE_DECISION', $currentUserId)
            || ($status !== DecisionProcess::STATUS_DRAFT && $decision->currentViewerCanAccessVisibility($currentOrganizationId));
        if (!$canView) {
            continue;
        }
        $decisionId = (int)$decision->getId();
        $filters = array('active');
        $dashboardDecisionCounts['active']++;
        if ($status === DecisionProcess::STATUS_CONSULTATION) {
            $filters[] = 'elaboration';
            $dashboardDecisionCounts['elaboration']++;
        }
        if ($status === DecisionProcess::STATUS_EVALUATION) {
            if (!array_key_exists($decisionId, $dashboardDecisionPendingGroups)) {
                $participant = $currentUserId > 0 ? DecisionParticipant::findByDecisionAndUser($decisionId, $currentUserId) : null;
                if ((!$participant || (int)$participant->get('active') !== 1) && $dashboardDecisionUserEmail !== '') {
                    $participant = DecisionParticipant::findByDecisionAndEmail($decisionId, $dashboardDecisionUserEmail);
                }
                // Owners can vote even before their participant record is created by the voting form.
                if (!$participant && $isOwner) {
                    $participant = new DecisionParticipant();
                    $participant->set('IDdecision_process', $decisionId);
                    $participant->set('active', 1);
                    $participant->set('status', DecisionParticipant::STATUS_ACTIVE);
                }
                $dashboardDecisionPendingGroups[$decisionId] = $participant instanceof DecisionParticipant
                    ? $decision->getPendingEvaluationGroupIdForParticipant($participant)
                    : null;
            }
            if ($dashboardDecisionPendingGroups[$decisionId] !== null) {
                $filters[] = 'pending';
                $dashboardDecisionCounts['pending']++;
            }
        }
        $dashboardDecisionItems[] = array(
            'id' => $decisionId,
            'title' => (string)$decision->get('title'),
            'filters' => $filters,
        );
    }
    usort($dashboardDecisionItems, static function (array $left, array $right): int {
        return (in_array('pending', $right['filters'], true) <=> in_array('pending', $left['filters'], true))
            ?: (in_array('elaboration', $right['filters'], true) <=> in_array('elaboration', $left['filters'], true));
    });
}
