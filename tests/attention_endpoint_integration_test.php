<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, Organization, User, UserOrganization, ArrayApplication, OrganizationApplication,
    Holon, ControlActivity, DecisionProcess, DecisionParticipant};

function attentionEndpointCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function attentionEndpointFixture(string $class, array $values): DbObject
{
    $object = new $class();
    foreach ($values as $field => $value) $object->set($field, $value);
    $reply = $object->save();
    attentionEndpointCheck(!empty($reply['status']), $class . ': ' . json_encode($reply));
    return $object;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(6));
    $org = attentionEndpointFixture(Organization::class, ['name' => 'Attention fixture', 'shortname' => 'attention-' . $nonce,
        'interface_level' => Organization::INTERFACE_LEVEL_AUTONOMOUS]);
    $user = attentionEndpointFixture(User::class, ['email' => 'attention-' . $nonce . '@example.invalid', 'active' => 1]);
    $otherUser = attentionEndpointFixture(User::class, ['email' => 'attention-other-' . $nonce . '@example.invalid', 'active' => 1]);
    foreach ([$user, $otherUser] as $member) attentionEndpointFixture(UserOrganization::class,
        ['IDorganization' => $org->getId(), 'IDuser' => $member->getId(), 'active' => 1]);
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'op' => 'in', 'value' => ['structure', 'decision', 'activities']]]]);
    attentionEndpointCheck(count($apps) === 3, 'Expected applications must exist.');
    foreach ($apps as $app) attentionEndpointFixture(OrganizationApplication::class,
        ['IDorganization' => $org->getId(), 'IDapplication' => $app->getId(), 'active' => 1]);
    $root = attentionEndpointFixture(Holon::class,
        ['IDorganization' => $org->getId(), 'name' => 'Root', 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $root->set('IDholon_org', $root->getId());
    attentionEndpointCheck(!empty($root->save()['status']), 'Root must save.');
    $spaces = [];
    foreach ([1, 2, 3] as $type) $spaces[] = attentionEndpointFixture(Holon::class, [
        'IDorganization' => $org->getId(), 'IDholon_org' => $root->getId(), 'IDholon_parent' => $root->getId(),
        'name' => 'Space ' . $type, 'IDtypeholon' => $type, 'active' => 1, 'visible' => 1,
    ]);
    $tasks = [];
    foreach ([$spaces[0], $spaces[1], $spaces[1], $spaces[2]] as $index => $space) {
        $tasks[] = attentionEndpointFixture(ControlActivity::class, [
            'IDorganization' => $org->getId(), 'IDholon' => $space->getId(), 'IDuser_responsible' => $user->getId(),
            'title' => 'Task ' . $index, 'frequency' => 'monthly', 'schedule' => '1',
            'created_at' => new DateTimeImmutable('first day of -6 months midnight'),
            'execution_duration_value' => $index + 1, 'execution_duration_unit' => 'hour', 'active' => 1,
        ]);
    }
    foreach ([null, $otherUser->getId()] as $responsible) attentionEndpointFixture(ControlActivity::class, [
        'IDorganization' => $org->getId(), 'IDholon' => $spaces[0]->getId(), 'IDuser_responsible' => $responsible,
        'title' => 'Not my task', 'frequency' => 'monthly', 'schedule' => '1',
        'created_at' => new DateTimeImmutable('first day of -6 months midnight'),
        'execution_duration_value' => 1, 'execution_duration_unit' => 'hour', 'active' => 1,
    ]);
    $decisions = [];
    for ($index = 0; $index < 2; $index++) {
        $decision = attentionEndpointFixture(DecisionProcess::class, [
            'IDorganization' => $org->getId(), 'IDholon' => $spaces[1]->getId(), 'IDuser' => $otherUser->getId(),
            'title' => 'Decision ' . $index, 'decision_type' => 'decision', 'status' => 'evaluation',
            'evaluation_method' => 'simple_vote', 'visibility_type' => DecisionProcess::getDefaultVisibilityType(),
            'evaluation_start_at' => new DateTimeImmutable('-1 hour'),
        ]);
        attentionEndpointFixture(DecisionParticipant::class, ['IDdecision_process' => $decision->getId(),
            'IDuser' => $user->getId(), 'role' => 'participant', 'status' => 'invited', 'active' => 1]);
        $decisions[] = $decision;
    }
    $user->load((int)$user->getId(), true);
    $_SESSION['currentUser'] = (int)$user->getId();
    $_SESSION['auth_security_version'] = (int)$user->get('security_version');
    $_SESSION['currentOrganization'] = (int)$org->getId();
    $_GET = ['oid' => $org->getId(), 'lang' => 'fr'];
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_URI'] = '/omo/api/attention.php?oid=' . $org->getId();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $readEndpoint = static function (): array {
        ob_start();
        try {
            include dirname(__DIR__) . '/omo/api/attention.php';
            return json_decode(ob_get_contents(), true, 512, JSON_THROW_ON_ERROR);
        } finally { ob_end_clean(); }
    };
    $payload = $readEndpoint();
    $signals = array_column($payload['signals'], null, 'type');
    attentionEndpointCheck(count($signals) === 2, 'Each application must have an independent signal.');
    $activity = $signals['activities'];
    attentionEndpointCheck($activity['count'] === 4 && $activity['spaceCount'] === 3, 'Only explicitly assigned tasks count, with distinct spaces.');
    attentionEndpointCheck($activity['id'] === (int)$tasks[0]->getId() && $activity['cid'] === (int)$spaces[0]->getId(), 'The oldest deadline must link to its own space and task.');
    attentionEndpointCheck($activity['routeToken'] === 'activities-d' . $tasks[0]->getId(), 'The task badge must open the detail route.');
    attentionEndpointCheck(str_contains($activity['message'], 'Task 0') && str_contains($activity['message'], 'Et 3 autres dans 2 espaces.'), 'The summary must name the first task and count remaining tasks and their spaces.');
    attentionEndpointCheck($signals['decision']['count'] === 2 && str_contains($signals['decision']['message'], 'Et 1 autre ailleurs.'), 'Decision summaries must preserve their own count and pluralization.');
    foreach ($tasks as $task) { $task->set('active', 0); attentionEndpointCheck(!empty($task->save()['status']), 'Task must save.'); }
    $after = array_column($readEndpoint()['signals'], null, 'type');
    attentionEndpointCheck(!isset($after['activities']) && isset($after['decision']), 'Clearing tasks must leave the decision signal intact.');
    echo "attention_endpoint_integration_test: OK\n";
} finally {
    $pdo->rollBack();
}
