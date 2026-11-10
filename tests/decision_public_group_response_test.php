<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/omo/api/decision/modules/majority_judgment/respond.php';
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/context.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/common.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/omo/translations.php';

use dbObject\{DbObject, Organization, DecisionProcess, DecisionProposal, DecisionParticipant, DecisionResponse};

function publicGroupCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function publicGroupFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $field => $value) $object->set($field, $value);
    $result = $object->save();
    publicGroupCheck(!empty($result['status']), 'Save ' . $class . ': ' . json_encode($result));
    return $object;
}

function publicGroupRespond(array $input): OmoDecisionModuleCapturedResponse
{
    $_POST = $_REQUEST = $input;
    $GLOBALS['omoDecisionCaptureModuleResponse'] = true;
    try {
        require dirname(__DIR__) . '/omo/api/decision/modules/majority_judgment/respond.php';
    } catch (OmoDecisionModuleCapturedResponse $response) {
        return $response;
    } finally {
        unset($GLOBALS['omoDecisionCaptureModuleResponse']);
    }
    throw new RuntimeException('Expected a captured response');
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
register_shutdown_function(static function () use ($pdo): void { if ($pdo->inTransaction()) $pdo->rollBack(); });
try {
    $_SESSION = [];
    $org = publicGroupFixture(Organization::class, ['name' => 'Public group response test', 'shortname' => 'public-group-' . bin2hex(random_bytes(5))]);
    $decision = publicGroupFixture(DecisionProcess::class, ['IDorganization' => $org->getId(),
        'title' => 'Majority judgment questionnaire', 'evaluation_method' => 'majority_judgment',
        'status' => 'evaluation', 'evaluation_start_at' => '2020-01-01 00:00:00']);
    $groups = [$decision->getPrimaryGroup(true), $decision->addDecisionGroup('majority_judgment')];
    $scores = [];
    foreach ($groups as $index => $group) {
        foreach (['A', 'B'] as $position => $title) {
            $proposal = publicGroupFixture(DecisionProposal::class, ['IDdecision_process' => $decision->getId(),
                'IDdecision_group' => $group->getId(), 'title' => $title, 'position' => $position + 1, 'active' => 1]);
            $scores[$index][(int)$proposal->getId()] = $position === 0 ? '0' : '6';
        }
    }
    $participant = publicGroupFixture(DecisionParticipant::class, ['IDdecision_process' => $decision->getId(),
        'email' => 'public-group@example.invalid', 'role' => 'participant', 'status' => 'active', 'active' => 1]);
    $input = ['token' => $participant->ensureAccessToken(), 'intent' => 'participate', 'id' => $decision->getId()];

    // A complete second block must not be checked against the first block's proposals.
    $result = publicGroupRespond($input + ['gid' => $groups[1]->getId(), 'scores' => $scores[1]]);
    publicGroupCheck($result->statusCode === 200 && !empty($result->payload['status']), 'Second block submission: ' . $result->getMessage());
    $saved = DecisionResponse::findByDecisionAndParticipant($decision->getId(), $participant->getId(), $groups[1]->getId());
    publicGroupCheck($saved instanceof DecisionResponse && $saved->get('status') === 'submitted', 'Second block response saved');
    publicGroupCheck(omoDecisionMajorityJudgmentExtractScores($saved) === array_map('intval', $scores[1]), 'All mentions, including zero, saved in the requested block');
    publicGroupCheck(DecisionResponse::findByDecisionAndParticipant($decision->getId(), $participant->getId(), $groups[0]->getId()) === null, 'First block remains unanswered');

    $incomplete = $scores[1]; array_pop($incomplete);
    $result = publicGroupRespond($input + ['gid' => $groups[1]->getId(), 'scores' => $incomplete]);
    publicGroupCheck($result->statusCode === 400, 'Incomplete ballots remain rejected');

    $foreignDecision = publicGroupFixture(DecisionProcess::class, ['IDorganization' => $org->getId(),
        'title' => 'Other decision', 'evaluation_method' => 'majority_judgment', 'status' => 'draft']);
    foreach ([$foreignDecision->getPrimaryGroup(true)->getId(), 2147483647] as $invalidGroupId) {
        $context = omoDecisionResolveEditorContext($input + ['gid' => $invalidGroupId]);
        publicGroupCheck(empty($context['status']) && $context['code'] === 404, 'Reject foreign or missing blocks');
    }

    $result = publicGroupRespond($input + ['scores' => $scores[0]]);
    publicGroupCheck($result->statusCode === 200, 'Legacy requests without gid still use the primary block');
    echo "decision_public_group_response_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
