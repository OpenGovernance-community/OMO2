<?php
require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/decisionprocess.class.php';
require_once dirname(__DIR__) . '/omo/api/decision/params/shared.php';

use dbObject\DecisionProcess;

function assertDecisionParams($condition, $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$defaults = omoDecisionParamsGetDefaultConfig();
assertDecisionParams(empty($defaults['governance']['enabled']), 'Governance workflow must require explicit activation.');
assertDecisionParams(!empty($defaults['methods'][DecisionProcess::METHOD_CONSENT]), 'Consent must remain enabled by default.');

$normalized = omoDecisionParamsNormalizeConfig([
    'methods' => [DecisionProcess::METHOD_SIMPLE_VOTE => false],
    'governance' => [
        'enabled' => true,
        'evaluation_method' => DecisionProcess::METHOD_SIMPLE_VOTE,
        'question' => ' Question test ',
        'consultation_days' => -2,
        'vote_days' => 500,
        'show_live_votes' => true,
        'live_votes_anonymous' => true,
    ],
]);
assertDecisionParams(empty($normalized['methods'][DecisionProcess::METHOD_SIMPLE_VOTE]), 'A disabled method must remain disabled.');
assertDecisionParams(empty($normalized['methods'][DecisionProcess::METHOD_CONSENT]), 'Unchecked methods must remain disabled after form submission.');
assertDecisionParams($normalized['governance']['evaluation_method'] === DecisionProcess::METHOD_SIMPLE_VOTE, 'Governance workflows must retain their configured evaluation method.');
assertDecisionParams($normalized['governance']['question'] === 'Question test', 'The governance question must be trimmed.');
assertDecisionParams($normalized['governance']['consultation_days'] === 0, 'Consultation duration must accept zero but not negative days.');
assertDecisionParams($normalized['governance']['vote_days'] === 365, 'Vote duration must be limited to one year.');
assertDecisionParams(!empty($normalized['governance']['live_votes_anonymous']), 'Anonymous live votes require live result display.');

$withoutLiveVotes = omoDecisionParamsNormalizeConfig([
    'governance' => ['live_votes_anonymous' => true],
]);
assertDecisionParams(empty($withoutLiveVotes['governance']['live_votes_anonymous']), 'Anonymous live votes must be disabled when live results are off.');
assertDecisionParams(!empty($withoutLiveVotes['methods'][DecisionProcess::METHOD_SIMPLE_VOTE]), 'Methods must retain their defaults before the settings form is saved.');

echo "Decision parameter tests passed.\n";
