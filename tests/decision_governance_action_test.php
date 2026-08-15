<?php
require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/propertyformat.class.php';
require_once dirname(__DIR__) . '/class/dbobject/rule.class.php';
require_once dirname(__DIR__) . '/class/dbobject/decisionresponse.class.php';
require_once dirname(__DIR__) . '/class/dbobject/decisionproposal.class.php';
require_once dirname(__DIR__) . '/class/dbobject/decisionprocess.class.php';
require_once dirname(__DIR__) . '/class/dbobject/decisiongovernanceaction.class.php';

use dbObject\DecisionGovernanceAction;
use dbObject\DecisionProposal;
use dbObject\DecisionResponse;

function assertDecisionGovernanceAction($condition, $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$normalized = DecisionGovernanceAction::normalizeRuleState([
    'IDholon' => 12,
    'title' => ' Regle test ',
    'intention' => '<p>Intention</p>',
    'description' => '<p>Contenu</p>',
    'scope' => 'descendants',
    'review_date' => '2027-01-10 12:00:00',
    'expiration_date' => '2027-12-31 12:00:00',
]);
assertDecisionGovernanceAction($normalized['title'] === 'Regle test', 'The rule title must be trimmed.');
assertDecisionGovernanceAction($normalized['scope'] === 'local', 'A holon rule must remain local.');
assertDecisionGovernanceAction($normalized['review_date'] === '2027-01-10', 'The review date must be normalized.');

$description = DecisionGovernanceAction::buildRuleUpdateDescription(
    array_merge($normalized, ['title' => 'Ancien titre']),
    array_merge($normalized, ['title' => 'Nouveau titre'])
);
assertDecisionGovernanceAction(
    str_contains($description, 'Ancien titre') && str_contains($description, 'Nouveau titre'),
    'The action description must expose the before and after values.'
);

$proposal = new DecisionProposal();
$proposal->setId(24);
$response = new DecisionResponse();
$response->set('parameters', [
    'consent' => [
        'choices' => [24 => 'objection'],
    ],
]);
assertDecisionGovernanceAction(
    DecisionGovernanceAction::proposalHasObjection($proposal, [$response]),
    'One objection must reject the whole governance proposal.'
);

$response->set('parameters', [
    'consent' => [
        'choices' => [24 => 'no_objection'],
    ],
]);
assertDecisionGovernanceAction(
    !DecisionGovernanceAction::proposalHasObjection($proposal, [$response]),
    'A response without objection must keep the proposal accepted.'
);

assertDecisionGovernanceAction(
    DecisionGovernanceAction::isImplementedType(DecisionGovernanceAction::TYPE_RULE_UPDATE),
    'Rule updates must be registered as implemented.'
);
assertDecisionGovernanceAction(
    DecisionGovernanceAction::isImplementedType(DecisionGovernanceAction::TYPE_RULE_CREATE),
    'Rule creations must be registered as implemented.'
);
assertDecisionGovernanceAction(
    DecisionGovernanceAction::isImplementedType(DecisionGovernanceAction::TYPE_RULE_DELETE),
    'Rule deletions must be registered as implemented.'
);

$createValidation = DecisionGovernanceAction::validateRuleCreate($normalized, 12);
assertDecisionGovernanceAction(
    !empty($createValidation['status']) && (int)$createValidation['state']['IDholon'] === 12,
    'A local rule creation must be normalized in the decision holon.'
);
$invalidCreateValidation = DecisionGovernanceAction::validateRuleCreate(
    array_merge($normalized, ['review_date' => '2028-01-01', 'expiration_date' => '2027-01-01']),
    12
);
assertDecisionGovernanceAction(
    empty($invalidCreateValidation['status']),
    'A rule creation must reject an expiration date before its review date.'
);

$stateDescription = DecisionGovernanceAction::buildRuleStateDescription($normalized);
assertDecisionGovernanceAction(
    str_contains($stateDescription, 'Regle test') && str_contains($stateDescription, 'Contenu'),
    'Creation and deletion descriptions must expose the complete rule snapshot.'
);
assertDecisionGovernanceAction(
    !DecisionGovernanceAction::isImplementedType(DecisionGovernanceAction::TYPE_HOLON_UPDATE),
    'Future holon actions must stay registered without being executable yet.'
);

echo "Decision governance action tests passed.\n";
