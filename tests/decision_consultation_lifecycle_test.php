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
$decision->set('consultation_start_at', (new DateTimeImmutable('-1 minute'))->format('Y-m-d H:i:s'));
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
assertDecisionConsultationLifecycle(
    !$decision->isParticipationOpen(),
    'An open consultation must not accept votes before the evaluation phase.'
);

$futureEvaluationStart = new DateTimeImmutable('+5 days');
$decision->set('status', DecisionProcess::STATUS_EVALUATION);
$decision->set('evaluation_start_at', $futureEvaluationStart->format('Y-m-d H:i:s'));
assertDecisionConsultationLifecycle(
    !$decision->hasEvaluationStarted() && !$decision->isParticipationOpen(),
    'A manually selected evaluation status must not open voting before its scheduled start.'
);
assertDecisionConsultationLifecycle(
    DecisionProcess::getManualEvaluationStartConflict(DecisionProcess::STATUS_EVALUATION, $futureEvaluationStart) instanceof DateTimeInterface,
    'A future evaluation start must require lifecycle confirmation.'
);
assertDecisionConsultationLifecycle(
    DecisionProcess::getManualEvaluationStartConflict(DecisionProcess::STATUS_EVALUATION, new DateTimeImmutable('-1 minute')) === null,
    'A past evaluation start must be compatible with the evaluation status.'
);
$decision->set('status', DecisionProcess::STATUS_CONSULTATION);
$decision->set('evaluation_start_at', null);
$futureConsultationStart = new DateTimeImmutable('+5 days');
$decision->set('consultation_start_at', $futureConsultationStart->format('Y-m-d H:i:s'));
assertDecisionConsultationLifecycle(
    !$decision->hasConsultationStarted() && !$decision->isConsultationOpen(),
    'A manually selected consultation status must not open elaboration before its scheduled start.'
);
assertDecisionConsultationLifecycle(
    DecisionProcess::getManualConsultationStartConflict(DecisionProcess::STATUS_CONSULTATION, $futureConsultationStart) instanceof DateTimeInterface,
    'A future consultation start must require lifecycle confirmation.'
);
$decision->set('consultation_start_at', null);

foreach ([
    'omo/api/decision/modules/vote/module.php' => 'omo-decision-vote__form',
    'omo/api/decision/modules/consent/module.php' => 'omo-decision-consent__form',
] as $modulePath => $consultationContainer) {
    $moduleSource = file_get_contents(dirname(__DIR__) . '/' . $modulePath);
    assertDecisionConsultationLifecycle(
        is_string($moduleSource)
            && str_contains($moduleSource, '$isConsultationPhase')
            && str_contains($moduleSource, '<?php if (!$isConsultationPhase): ?>')
            && str_contains($moduleSource, '<div class="' . $consultationContainer . ' generic-form-stack">'),
        'The vote UI must be hidden while the decision is in consultation: ' . $modulePath
    );
}

foreach ([
    'omo/api/decision/modules/vote/save.php',
    'omo/api/decision/modules/majority_judgment/save.php',
    'omo/api/decision/modules/consent/save.php',
] as $savePath) {
    $saveSource = file_get_contents(dirname(__DIR__) . '/' . $savePath);
    assertDecisionConsultationLifecycle(
        is_string($saveSource)
            && str_contains($saveSource, 'getManualEvaluationStartConflict')
            && str_contains($saveSource, 'getManualConsultationStartConflict'),
        'Saving must reject statuses that start in the future: ' . $savePath
    );
}

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
$decision->set('evaluation_start_at', (new DateTimeImmutable('-1 minute'))->format('Y-m-d H:i:s'));
assertDecisionConsultationLifecycle(
    $decision->hasConsultationEnded(),
    'The evaluation phase must close the consultation.'
);
assertDecisionConsultationLifecycle(
    !$decision->isConsultationOpen() && $decision->isParticipationInterfaceOpen(),
    'The evaluation phase must expose voting without reopening consultation.'
);

echo "Decision consultation lifecycle tests passed.\n";
