<?php
require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/decisionprocess.class.php';

use dbObject\DecisionProcess;

function assertDecisionConsultationLifecycle($condition, $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$decision = new DecisionProcess();
$decision->set('status', DecisionProcess::STATUS_CONSULTATION);
$decision->set('consultation_end_at', (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'));
assertDecisionConsultationLifecycle(
    !$decision->hasConsultationEnded(),
    'An open consultation must stay open.'
);
assertDecisionConsultationLifecycle(
    $decision->isConsultationOpen(),
    'An open consultation must expose the participation interface.'
);
assertDecisionConsultationLifecycle(
    $decision->isParticipationInterfaceOpen(),
    'An open consultation must allow participant access.'
);

$decision->set('consultation_end_at', (new DateTimeImmutable('-1 hour'))->format('Y-m-d H:i:s'));
assertDecisionConsultationLifecycle(
    $decision->hasConsultationEnded(),
    'A completed consultation must be closed.'
);
assertDecisionConsultationLifecycle(
    !$decision->isConsultationOpen(),
    'A completed consultation must not expose the participation interface.'
);
assertDecisionConsultationLifecycle(
    $decision->resolveAutomaticStatus() === DecisionProcess::STATUS_DRAFT,
    'A completed elaboration phase without a planned vote must return to preparation.'
);
$decision->set('evaluation_start_at', (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'));
assertDecisionConsultationLifecycle(
    $decision->resolveAutomaticStatus() === DecisionProcess::STATUS_SCHEDULED,
    'A future vote after elaboration must keep the decision scheduled.'
);
$decision->set('evaluation_start_at', null);

$decision->set('status', DecisionProcess::STATUS_EVALUATION);
$decision->set('consultation_end_at', (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'));
assertDecisionConsultationLifecycle(
    $decision->hasConsultationEnded(),
    'The evaluation phase must close the consultation.'
);
assertDecisionConsultationLifecycle(
    !$decision->isConsultationOpen() && $decision->isParticipationInterfaceOpen(),
    'The evaluation phase must expose voting without reopening consultation.'
);

echo "Decision consultation lifecycle tests passed.\n";
