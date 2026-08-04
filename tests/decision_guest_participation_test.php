<?php
require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/decisionproposal.class.php';
require_once dirname(__DIR__) . '/class/dbobject/chatmessage.class.php';
require_once dirname(__DIR__) . '/class/dbobject/user.class.php';

use dbObject\ChatMessage;
use dbObject\DecisionProposal;

function assertDecisionGuestParticipation($condition, $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$proposal = new DecisionProposal();
$proposal->set('parameters', ['added_by_participant_id' => 42]);
assertDecisionGuestParticipation(
    $proposal->canBeEditedByParticipant(42),
    'The proposal author participant must be allowed to edit.'
);
assertDecisionGuestParticipation(
    !$proposal->canBeEditedByParticipant(43),
    'Another participant must not be allowed to edit.'
);

$message = new ChatMessage();
$message->set('IDorganization', 1);
$message->set('IDdecision_participant', 42);
$message->set('message_type', ChatMessage::TYPE_USER);
$message->set('content', 'Guest message');
$message->set('author_name', 'Guest');
$messagePayload = $message->toClientArray(0, 42);
assertDecisionGuestParticipation(
    !empty($messagePayload['isOwn'])
    && (int)($messagePayload['authorParticipantId'] ?? 0) === 42,
    'The participant identity must be retained for their own chat message.'
);

echo "Decision guest participation tests passed.\n";
