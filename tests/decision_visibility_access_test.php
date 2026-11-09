<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/omo/translations.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/context.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/common.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/registry.php';
require_once dirname(__DIR__) . '/common/choice/decision-visibility.php';

use dbObject\{DbObject, Organization, OrganizationApplication, Application, Holon, User, UserOrganization, UserHolon,
    DecisionProcess, DecisionParticipant, DecisionInvitation, ObjectVisibility};

function decisionVisibilityCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function decisionVisibilityFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $field => $value) $object->set($field, $value);
    decisionVisibilityCheck(!empty($object->save()['status']), 'Save fixture ' . $class);
    return $object;
}
if (!function_exists('omoApiEscape')) {
    function omoApiEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}

function decisionVisibilitySave(array $post, string $module): OmoDecisionModuleCapturedResponse
{
    $_POST = $_REQUEST = $post;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/omo/api/decision/modules/' . $module . '/save.php';
    $GLOBALS['omoDecisionCaptureModuleResponse'] = true;
    $GLOBALS['omoDecisionDeferProposalNotifications'] = true;
    try {
        require dirname(__DIR__) . '/omo/api/decision/modules/' . $module . '/save.php';
        throw new RuntimeException('Save must return a response');
    } catch (OmoDecisionModuleCapturedResponse $response) {
        return $response;
    } finally {
        unset($GLOBALS['omoDecisionCaptureModuleResponse'], $GLOBALS['omoDecisionDeferProposalNotifications']);
    }
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $nonce = bin2hex(random_bytes(5));
    $org = decisionVisibilityFixture(Organization::class, ['name' => 'Decision visibility', 'shortname' => $nonce]);
    $oid = (int)$org->getId();
    foreach (['structure', 'decision'] as $hash) {
        $app = new Application();
        decisionVisibilityCheck($app->load(['hash', $hash]), 'Application exists');
        decisionVisibilityFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    }
    $root = decisionVisibilityFixture(Holon::class, ['name' => 'Root', 'IDorganization' => $oid, 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $root->set('IDholon_org', $root->getId()); $root->save();
    $circle = decisionVisibilityFixture(Holon::class, ['name' => 'Circle', 'IDorganization' => $oid, 'IDholon_org' => $root->getId(),
        'IDholon_parent' => $root->getId(), 'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
    $role = decisionVisibilityFixture(Holon::class, ['name' => 'Role', 'IDorganization' => $oid, 'IDholon_org' => $root->getId(),
        'IDholon_parent' => $circle->getId(), 'IDtypeholon' => 1, 'active' => 1, 'visible' => 1]);
    $users = [];
    foreach (['owner', 'role_peer', 'circle_peer', 'org_peer', 'invited'] as $key) {
        $users[$key] = decisionVisibilityFixture(User::class, ['email' => "$key-$nonce@example.invalid", 'active' => 1]);
        decisionVisibilityFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $users[$key]->getId(), 'active' => 1]);
    }
    foreach (['role_peer' => $role, 'circle_peer' => $circle] as $key => $holon) {
        decisionVisibilityFixture(UserHolon::class, ['IDholon' => $holon->getId(), 'IDuser' => $users[$key]->getId(), 'active' => 1, 'is_membership' => 1]);
    }
    $decision = decisionVisibilityFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $role->getId(), 'IDuser' => $users['owner']->getId(),
        'title' => 'Visibility test', 'status' => 'consultation', 'evaluation_method' => 'simple_vote',
        'consultation_start_at' => '2020-01-01 00:00:00', 'consultation_end_at' => '2090-01-01 00:00:00', 'evaluation_start_at' => '2090-01-02 00:00:00']);
    decisionVisibilityFixture(DecisionInvitation::class, ['IDdecision_process' => $decision->getId(), 'invitation_type' => 'user',
        'IDuser' => $users['invited']->getId(), 'active' => 1, 'status' => 'invited']);
    decisionVisibilityCheck(!empty($decision->syncParticipantsFromInvitations()['status']), 'Invitation synchronized');
    decisionVisibilityCheck($decision->get('visibility_type') === 'organization', 'Default organization reading');
    decisionVisibilityCheck($decision->getPrimaryEditVisibilityRuleRow()['visibility_type'] === 'self', 'Default author editing');
    $input = ['oid' => $oid, 'cid' => $role->getId(), 'id' => $decision->getId()];
    $scopeUsers = ['everyone' => ['role_peer', 'circle_peer', 'org_peer'], 'organization' => ['role_peer', 'circle_peer', 'org_peer'],
        'circle' => ['role_peer', 'circle_peer'], 'role' => ['role_peer'], 'self' => []];
    foreach ($scopeUsers as $scope => $allowed) {
        decisionVisibilityCheck(!empty($decision->saveVisibilityRulesFromInput(['visibility_type' => $scope, 'edit_visibility_type' => 'self'])['status']), 'Save read scope ' . $scope);
        foreach ($users as $key => $user) {
            $_SESSION = ['currentUser' => $user->getId(), 'currentOrganization' => $oid];
            $context = omoDecisionResolveEditorContext($input + ['intent' => 'view']);
            $expected = in_array($key, ['owner', 'invited'], true) || in_array($key, $allowed, true);
            decisionVisibilityCheck(!empty($context['status']) === $expected, "$scope: read $key");
            if ($expected && !in_array($key, ['owner', 'invited'], true)) {
                decisionVisibilityCheck(empty($context['canParticipate']) && empty($context['hasParticipation']), 'Readers never become participants');
            }
        }
    }
    $decision->saveVisibilityRulesFromInput(['visibility_type' => 'organization']);
    foreach ($scopeUsers as $scope => $allowed) {
        decisionVisibilityCheck(!empty($decision->saveVisibilityRulesFromInput(['edit_visibility_type' => $scope])['status']), 'Save edit scope ' . $scope);
        foreach ($users as $key => $user) {
            $_SESSION = ['currentUser' => $user->getId(), 'currentOrganization' => $oid];
            $context = omoDecisionResolveManagementContext($input);
            decisionVisibilityCheck(!empty($context['status']) === ($key === 'owner' || in_array($key, $allowed, true)
                || ($key === 'invited' && in_array($scope, ['everyone', 'organization'], true))), "$scope: edit $key");
            if (!empty($context['status']) && in_array($key, ['role_peer', 'circle_peer', 'org_peer'], true)) {
                decisionVisibilityCheck(empty($context['canParticipate']) && empty($context['hasParticipation']), 'Editors never become participants');
            }
        }
    }
    decisionVisibilityCheck(empty($decision->saveVisibilityRulesFromInput(['edit_visibility_type' => 'invalid'])['status']), 'Unknown edit scope rejected');
    $decision->saveVisibilityRulesFromInput(['visibility_type' => 'self', 'edit_visibility_type' => 'organization']);
    decisionVisibilityCheck(!$decision->canUseManagementPermission('CAN_EDIT_DECISION', (int)$users['org_peer']->getId()), 'Editing also requires reading, as for documents');
    decisionVisibilityCheck($decision->canUseManagementPermission('CAN_EDIT_DECISION', (int)$users['invited']->getId()), 'Invitation supplies reading for authorized editors');
    $decision->saveVisibilityRulesFromInput(['visibility_type' => 'organization']);
    $decision->saveVisibilityRulesFromInput(['edit_visibility_type' => 'role']);
    $_SESSION = ['currentUser' => $users['owner']->getId(), 'currentOrganization' => $oid];
    $html = commonDecisionRenderVisibilitySelectors($decision, ['organizationId' => $oid], true, 'fixture');
    decisionVisibilityCheck(substr_count($html, 'type="radio"') === 10 && substr_count($html, 'data-omo-visibility-choice') >= 2, 'Shared document selectors rendered');
    $decision->set('status', 'evaluation'); $decision->set('evaluation_start_at', '2020-01-02 00:00:00'); $decision->save();
    $lockedHtml = commonDecisionRenderVisibilitySelectors($decision, ['organizationId' => $oid], false, 'locked');
    decisionVisibilityCheck(substr_count($lockedHtml, ' disabled') === 10, 'Selectors locked after voting starts');
    decisionVisibilityCheck(!empty($decision->saveVisibilityRulesFromInput(['visibility_type' => 'self', 'edit_visibility_type' => 'organization'], true)['status'])
        && $decision->getPrimaryEditVisibilityRuleRow()['visibility_type'] === 'role'
        && $decision->get('visibility_type') === 'organization', 'Locked access settings preserved');
    // Direct model updates must not leave an older read rule in effect.
    $decision->set('visibility_type', 'everyone'); $decision->save();
    decisionVisibilityCheck($decision->getPrimaryVisibilityRuleRow()['visibility_type'] === 'everyone', 'Read rule synchronized');
    $public = omoDecisionResolveGenericPublicContext($input + ['intent' => 'view']);
    decisionVisibilityCheck($public['accessMode'] === 'public_observer' && !$public['canParticipate'], 'Public observer cannot participate');
    $publicParticipation = omoDecisionResolveGenericPublicContext($input + ['intent' => 'participate']);
    decisionVisibilityCheck($publicParticipation['accessMode'] === 'public_request' && !$publicParticipation['canParticipate'], 'Public participation still requires invitation or registration');
    decisionVisibilityCheck(!empty($decision->deleteWithRelations()['status']), 'Decision deleted');
    decisionVisibilityCheck(!ObjectVisibility::loadActiveRuleRow(DecisionProcess::getEditVisibilityObjectType(), (int)$decision->getId(), $oid), 'Edit rule deleted with decision');
    // Exercise the real save endpoints as a non-author, then attempt forged context changes after start.
    $_SESSION = ['currentUser' => $users['role_peer']->getId(), 'currentOrganization' => $oid,
        'auth_security_version' => $users['role_peer']->get('security_version')];
    foreach (['vote' => 'simple_vote', 'majority_judgment' => 'majority_judgment', 'consent' => 'consent'] as $module => $method) {
        $item = decisionVisibilityFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $role->getId(),
            'IDuser' => $users['owner']->getId(), 'title' => 'Original', 'status' => 'draft', 'evaluation_method' => $method]);
        decisionVisibilityFixture(DecisionInvitation::class, ['IDdecision_process' => $item->getId(), 'invitation_type' => 'user',
            'IDuser' => $users['invited']->getId(), 'active' => 1, 'status' => 'invited']);
        $item->saveVisibilityRulesFromInput(['edit_visibility_type' => 'circle']);
        $post = ['oid' => $oid, 'cid' => $role->getId(), 'id' => $item->getId(), 'process_title' => 'Edited by peer',
            'title' => 'Question', 'proposals' => ['Yes', 'No'], 'proposal_content_title' => 1, 'status' => 'draft',
            'visibility_type' => 'role', 'edit_visibility_type' => 'circle'];
        $response = decisionVisibilitySave($post, $module);
        decisionVisibilityCheck($response->statusCode === 200 && !empty($response->payload['status']), "$module: peer saves " . json_encode($response->payload));
        $item->load((int)$item->getId(), true);
        decisionVisibilityCheck($item->get('title') === 'Edited by peer' && (int)$item->get('IDuser') === (int)$users['owner']->getId(), "$module: author preserved");
        decisionVisibilityCheck($item->getPrimaryVisibilityRuleRow()['visibility_type'] === 'role'
            && $item->getPrimaryEditVisibilityRuleRow()['visibility_type'] === 'circle', "$module: access persisted");
        decisionVisibilityCheck(!DecisionParticipant::findByDecisionAndUser((int)$item->getId(), (int)$users['role_peer']->getId()), "$module: peer not enrolled");
        $item->set('status', 'evaluation'); $item->set('evaluation_start_at', '2020-01-02 00:00:00'); $item->save();
        $post = array_replace($post, ['status' => 'evaluation', 'evaluation_start_at' => '2020-01-02 00:00:00',
            'process_title' => 'Forbidden context change', 'visibility_type' => 'everyone', 'edit_visibility_type' => 'organization']);
        $response = decisionVisibilitySave($post, $module);
        decisionVisibilityCheck($response->statusCode === 200 && !empty($response->payload['status']), "$module: locked save " . json_encode($response->payload));
        $item->load((int)$item->getId(), true);
        decisionVisibilityCheck($item->get('title') === 'Edited by peer' && $item->get('visibility_type') === 'role'
            && $item->getPrimaryEditVisibilityRuleRow()['visibility_type'] === 'circle', "$module: forged context changes ignored");
    }
    echo "decision_visibility_access_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    DbObject::$preload = [];
}
