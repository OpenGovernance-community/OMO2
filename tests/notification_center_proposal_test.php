<?php
require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/propertyformat.class.php';
require_once dirname(__DIR__) . '/class/dbobject/decisionproposal.class.php';
require_once dirname(__DIR__) . '/common/notification_center.php';

function assertNotificationCenterProposal($condition, $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$proposal = new \dbObject\DecisionProposal();
$proposal->set('title', 'Proposition titree');
$proposal->set('description', '<p>Description ignoree</p>');
assertNotificationCenterProposal(
    notificationCenterBuildDecisionProposalLabel($proposal) === 'Proposition titree',
    'The proposal title must remain the notification label when it exists.'
);

$proposal->set('title', null);
$proposal->set('description', '<p>Premiers caracteres de la proposition sans titre avec une suite.</p>');
assertNotificationCenterProposal(
    notificationCenterBuildDecisionProposalLabel($proposal) === 'Premiers caracteres de la prop...',
    'The notification label must use a shortened plain-text description when the title is empty.'
);

$proposal->set('description', '<p><strong></strong></p>');
assertNotificationCenterProposal(
    notificationCenterBuildDecisionProposalLabel($proposal) === '',
    'An empty proposal title and description must return no label for the generic fallback.'
);

echo "Notification center proposal tests passed.\n";
