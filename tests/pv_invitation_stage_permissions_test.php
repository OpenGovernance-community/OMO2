<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\Document;

function invitationStageExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

// Unsaved objects exercise the real permission policy without writes or mail delivery.
$pv = new Document();
$pv->set('documenttype', Document::TYPE_PV);
$pv->set('IDorganization', 1);
$pv->set('IDuser_pv_editor', 7);
$pv->set('IDuser_pv_official_editor', 7);
foreach ([Document::PV_STAGE_PREPARATION, Document::PV_STAGE_MEETING, Document::PV_STAGE_REVIEW, Document::PV_STAGE_VALIDATED] as $stage) {
    $pv->set('pvstage', $stage);
    invitationStageExpect($pv->canUserSendPvInvitations(1, 7), 'The official editor can send at stage ' . $stage);
    invitationStageExpect(!$pv->canUserSendPvInvitations(1, 0), 'Anonymous users cannot send at stage ' . $stage);
    $editable = $stage !== Document::PV_STAGE_VALIDATED;
    invitationStageExpect($pv->canUserManagePvDocument(7) === $editable, 'Content remains locked after validation.');
    invitationStageExpect($pv->canUserManagePvStructure(1, 7) === $editable, 'Structure remains locked after validation.');
}
foreach ([Document::PV_STAGE_REVIEW, Document::PV_STAGE_VALIDATED] as $stage) {
    $pv->set('pvstage', $stage);
    $pv->set('IDuser_pv_official_editor', 8);
    invitationStageExpect(!$pv->canUserSendPvInvitations(1, 7), 'Replacement editors do not gain official management rights.');
}
$pv->set('documenttype', 'html');
invitationStageExpect(!$pv->canUserSendPvInvitations(1, 7), 'Other document types cannot send PV invitations.');
echo "pv_invitation_stage_permissions_test: OK\n";
