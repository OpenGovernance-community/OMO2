<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

if (!isset($argv[1])) {
    foreach (['owner', 'member', 'editor', 'memory', 'owner_delete', 'memory_delete',
        'owner_save', 'member_save', 'editor_save', 'memory_save'] as $scenario) {
        $process = proc_open([PHP_BINARY, '-d', 'display_errors=1', '-d', 'log_errors=0', __FILE__, $scenario],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) throw new RuntimeException('Unable to start scenario');
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
        if (proc_close($process) !== 0 || $errors !== '' || !str_contains($output, $scenario . ': OK')) {
            throw new RuntimeException($scenario . ': ' . $output . $errors);
        }
        echo $output;
    }
    exit;
}

require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/context.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/common.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/registry.php';

use dbObject\{DbObject, Organization, Holon, User, UserOrganization, UserHolon, Application, OrganizationApplication,
    HolonPermission, Permission, DecisionProcess, DecisionProposal, DecisionParticipant};

function decisionPermissionExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function decisionPermissionFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $field => $value) $object->set($field, $value);
    $result = $object->save();
    decisionPermissionExpect(!empty($result['status']), $class . ': ' . json_encode($result));
    return $object;
}
function decisionPermissionList(array $input): array
{
    $_GET = $_REQUEST = $input;
    ob_start();
    require dirname(__DIR__) . '/omo/api/decision/index.php';
    ob_end_clean();
    return $decisionEntries;
}
function decisionPermissionGovernanceForm(array $context): string
{
    ob_start();
    require dirname(__DIR__) . '/omo/api/decision/governance/edit.php';
    return (string)ob_get_clean();
}

$scenario = $argv[1];
$actorType = preg_replace('/_(delete|save)$/', '', $scenario);
$canManage = in_array($actorType, ['owner', 'editor'], true);
$canDelete = in_array($actorType, ['owner', 'memory'], true);
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
$verifyAction = null;
register_shutdown_function(static function () use ($pdo, &$verifyAction): void {
    try {
        if ($verifyAction) $verifyAction();
    } finally {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
});
try {
    $nonce = bin2hex(random_bytes(5));
    $org = decisionPermissionFixture(Organization::class, ['name' => 'Decision permissions', 'shortname' => 'permissions-' . $nonce,
        'interface_level' => Organization::INTERFACE_LEVEL_EXPERT]);
    $oid = (int)$org->getId();
    foreach (['structure', 'decision'] as $hash) {
        $app = new Application();
        decisionPermissionExpect($app->load(['hash', $hash]), 'Application exists');
        decisionPermissionFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1,
            'parameters' => $hash === 'decision' ? ['decisionSettings' => ['governance' => ['enabled' => true]]] : []]);
    }
    $root = decisionPermissionFixture(Holon::class, ['name' => 'Root', 'IDorganization' => $oid, 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $root->set('IDholon_org', $root->getId());
    decisionPermissionExpect(!empty($root->save()['status']), 'Root saved');
    $circle = decisionPermissionFixture(Holon::class, ['name' => 'Pedagogy', 'IDorganization' => $oid, 'IDholon_org' => $root->getId(),
        'IDholon_parent' => $root->getId(), 'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
    $owner = decisionPermissionFixture(User::class, ['email' => "owner-$nonce@example.invalid", 'active' => 1]);
    $other = decisionPermissionFixture(User::class, ['email' => "other-$nonce@example.invalid", 'active' => 1]);
    foreach ([$owner, $other] as $user) {
        decisionPermissionFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $user->getId(), 'active' => 1]);
        decisionPermissionFixture(UserHolon::class, ['IDholon' => $circle->getId(), 'IDuser' => $user->getId(), 'active' => 1, 'is_membership' => 1]);
    }
    $grantKeys = ['CAN_CREATE_DECISION'];
    if ($actorType === 'editor') $grantKeys[] = 'CAN_EDIT_DECISION';
    if ($actorType === 'memory') $grantKeys[] = 'CAN_DELETE_DECISION';
    foreach ($grantKeys as $key) decisionPermissionFixture(HolonPermission::class, ['IDholon' => $circle->getId(),
        'IDpermission' => Permission::findByKey($key)->getId(), 'member_type' => 'member', 'range' => 'self']);
    $fields = ['IDorganization' => $oid, 'IDholon' => $circle->getId(), 'IDuser' => $owner->getId(), 'title' => 'Permission scrutiny',
        'status' => 'consultation', 'evaluation_method' => 'consent', 'consultation_start_at' => '2020-01-01 00:00:00',
        'consultation_end_at' => '2090-01-01 00:00:00', 'evaluation_start_at' => '2090-01-02 00:00:00',
        'parameters' => ['workflow_type' => DecisionProcess::WORKFLOW_GOVERNANCE]];
    $decision = decisionPermissionFixture(DecisionProcess::class, $fields);
    decisionPermissionExpect(!empty($decision->syncParticipantsFromInvitations()['status']), 'Participants synchronized');
    $archivable = decisionPermissionFixture(DecisionProcess::class, array_replace($fields, ['title' => 'Finished scrutiny', 'status' => 'results',
        'consultation_end_at' => '2020-01-02 00:00:00', 'evaluation_start_at' => '2020-01-03 00:00:00', 'evaluation_end_at' => '2020-01-04 00:00:00']));
    $actor = $actorType === 'owner' ? $owner : $other;
    $_SESSION['currentUser'] = (int)$actor->getId();
    $_SESSION['currentOrganization'] = $oid;
    $_SESSION['auth_security_version'] = (int)$actor->get('security_version');
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/omo/api/decision/index.php';
    $_GET = $_REQUEST = ['oid' => $oid, 'cid' => $circle->getId()];
    require_once dirname(__DIR__) . '/omo/api/bootstrap.php';
    decisionPermissionExpect($circle->isAllowed('CAN_CREATE_DECISION', false, (int)$actor->getId()), 'All scenarios can create');
    decisionPermissionExpect($decision->canUseManagementPermission('CAN_EDIT_DECISION', (int)$actor->getId()) === $canManage, 'Author or explicit edit permission');
    decisionPermissionExpect($decision->canUseManagementPermission('CAN_DELETE_DECISION', (int)$actor->getId()) === $canDelete, 'Author or explicit delete permission');
    $input = ['oid' => $oid, 'cid' => $circle->getId(), 'id' => $decision->getId()];
    $entries = decisionPermissionList($input);
    $byId = array_column($entries, null, 'id');
    decisionPermissionExpect(isset($byId[$decision->getId()]) && $byId[$decision->getId()]['canManage'] === $canManage, 'List management permission matches');
    $modes = array_column($byId[$decision->getId()]['actions'], 'mode');
    decisionPermissionExpect(in_array('manage', $modes, true) === $canManage, 'Manage button follows edit permission');
    $archiveActions = array_filter($byId[$archivable->getId()]['menuActions'], static fn($action) => ($action['requestPayload']['decision_action'] ?? '') === 'archive');
    decisionPermissionExpect((count($archiveActions) > 0) === $canDelete, 'Archive button follows delete permission');
    $context = omoDecisionResolveEditorContext($input + ['intent' => 'manage']);
    decisionPermissionExpect(!empty($context['status']) === $canManage, 'Editor permission matches button');
    if ($canManage) {
        $html = decisionPermissionGovernanceForm($context);
        decisionPermissionExpect(str_contains($html, 'data-governance-form'), 'Authorized non-author and author can render governance form');
    } else {
        decisionPermissionExpect(($context['code'] ?? 0) === 403, 'Unauthorized editor rejected');
    }
    foreach (['view', 'participate', 'manage', ''] as $intent) {
        $write = omoDecisionResolveManagementContext($input + ['intent' => $intent]);
        decisionPermissionExpect(!empty($write['status']) === $canManage, 'Client intent cannot bypass management permission');
    }
    $creationContext = omoDecisionResolveManagementContext(['oid' => $oid, 'cid' => $circle->getId()]);
    decisionPermissionExpect(!empty($creationContext['status']) && !empty($creationContext['canCreate']), 'Create permission remains independent');
    decisionPermissionExpect(str_contains(decisionPermissionGovernanceForm($creationContext), 'data-governance-form'), 'Member with create permission can load a new form');
    if (!$canManage) {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        commonEnableReadOnlyCaches();
        foreach (['vote/save.php', 'majority_judgment/save.php', 'consent/save.php', 'consultation_only/convert.php'] as $endpoint) {
            $_POST = $_REQUEST = $input + ['intent' => 'view'];
            $GLOBALS['omoDecisionCaptureModuleResponse'] = true;
            try {
                require dirname(__DIR__) . '/omo/api/decision/modules/' . $endpoint;
                throw new RuntimeException('Endpoint must reject a read-only participant');
            } catch (OmoDecisionModuleCapturedResponse $response) {
                decisionPermissionExpect($response->statusCode === 403, 'Read-only save rejected: ' . $endpoint);
            } finally {
                unset($GLOBALS['omoDecisionCaptureModuleResponse']);
            }
        }
    }
    $_SERVER['REQUEST_METHOD'] = 'POST';
    commonEnableReadOnlyCaches();
    if ($scenario === 'editor') {
        $vote = decisionPermissionFixture(DecisionProcess::class, array_replace($fields,
            ['evaluation_method' => DecisionProcess::METHOD_SIMPLE_VOTE, 'parameters' => []]));
        $_POST = $_REQUEST = ['oid' => $oid, 'cid' => $circle->getId(), 'id' => $vote->getId(), 'intent' => 'view',
            'process_title' => 'Edited by manager', 'title' => 'Question', 'proposals' => ['Yes', 'No'], 'proposal_content_title' => 1];
        $GLOBALS['omoDecisionCaptureModuleResponse'] = true;
        $GLOBALS['omoDecisionDeferProposalNotifications'] = true;
        try {
            require dirname(__DIR__) . '/omo/api/decision/modules/vote/save.php';
            throw new RuntimeException('Vote save must return a response');
        } catch (OmoDecisionModuleCapturedResponse $response) {
            decisionPermissionExpect($response->statusCode === 200 && !empty($response->payload['status']), 'Explicit editor can save another author scrutiny: ' . json_encode($response->payload));
        } finally {
            unset($GLOBALS['omoDecisionCaptureModuleResponse'], $GLOBALS['omoDecisionDeferProposalNotifications']);
        }
        decisionPermissionExpect($vote->load($vote->getId(), true) && (int)$vote->get('IDuser') === (int)$owner->getId()
            && $vote->get('title') === 'Edited by manager', 'Saved scrutiny retains its author');
        $ownerParticipant = DecisionParticipant::findByDecisionAndUser($vote->getId(), $owner->getId());
        $editorParticipant = DecisionParticipant::findByDecisionAndUser($vote->getId(), $other->getId());
        decisionPermissionExpect($ownerParticipant && $ownerParticipant->get('role') === DecisionParticipant::ROLE_OWNER, 'Author retains owner participant role');
        decisionPermissionExpect(!$editorParticipant || $editorParticipant->get('role') !== DecisionParticipant::ROLE_OWNER, 'Editor does not become an owner participant');
    }
    if (str_ends_with($scenario, '_save')) {
        $proposal = decisionPermissionFixture(DecisionProposal::class, ['IDdecision_process' => $decision->getId(),
            'IDuser_author' => $owner->getId(), 'title' => 'Author proposal', 'active' => 1]);
        $_POST = $_REQUEST = $input + ['intent' => 'view', 'process_title' => 'Updated scrutiny', 'consent_question' => 'Agree?',
            'consultation_end_at' => '2090-01-01 00:00:00', 'evaluation_end_at' => '2090-01-02 00:00:00',
            'governance_blueprint' => json_encode([['id' => $proposal->getId(), 'actions' => []]])];
        $verifyAction = static function () use ($scenario, $canManage): void {
            $payload = json_decode((string)ob_get_clean(), true);
            decisionPermissionExpect(is_array($payload) && empty($payload['status']), 'Invalid governance payload rejected');
            decisionPermissionExpect(http_response_code() === ($canManage ? 422 : 403), 'Author and explicit editor reach proposal validation');
            if ($canManage) decisionPermissionExpect(str_contains($payload['message'] ?? '', 'au moins une modification'), 'Validation reached after existing proposal permission checks');
            echo $scenario . ": OK\n";
        };
        ob_start();
        require dirname(__DIR__) . '/omo/api/decision/governance/save.php';
        throw new RuntimeException('Governance save must return a response');
    }
    $deleteAction = str_ends_with($scenario, '_delete');
    $_POST = $_REQUEST = ['oid' => $oid, 'cid' => $circle->getId(), 'id' => $deleteAction ? $decision->getId() : $archivable->getId(),
        'decision_action' => $deleteAction ? 'delete' : 'archive'];
    $verifyAction = static function () use ($scenario, $canDelete, $archivable, $decision, $deleteAction): void {
        $payload = json_decode((string)ob_get_clean(), true);
        decisionPermissionExpect(is_array($payload) && !empty($payload['status']) === $canDelete, 'Lifecycle action follows delete permission');
        decisionPermissionExpect(http_response_code() === ($canDelete ? 200 : 403), 'Lifecycle response code');
        if ($canDelete) {
            if ($deleteAction) decisionPermissionExpect(!$decision->load($decision->getId(), true), 'Delete action removes the scrutiny');
            else decisionPermissionExpect($archivable->load($archivable->getId(), true) && $archivable->get('status') === 'archived', 'Memory role and author can archive');
        }
        echo $scenario . ": OK\n";
    };
    ob_start();
    require dirname(__DIR__) . '/omo/api/decision/action.php';
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
