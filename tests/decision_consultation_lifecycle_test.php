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

$decision->set('consultation_end_at', (new DateTimeImmutable('-1 hour'))->format('Y-m-d H:i:s'));
assertDecisionConsultationLifecycle(
    $decision->hasConsultationEnded(),
    'A completed consultation must be closed.'
);

$decision->set('status', DecisionProcess::STATUS_EVALUATION);
$decision->set('consultation_end_at', (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'));
assertDecisionConsultationLifecycle(
    $decision->hasConsultationEnded(),
    'The evaluation phase must close the consultation.'
);

echo "Decision consultation lifecycle tests passed.\n";
