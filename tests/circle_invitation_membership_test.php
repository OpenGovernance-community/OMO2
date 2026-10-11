<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/context.php';

use dbObject\{DbObject, Organization, Holon, User, UserOrganization, UserHolon, Application, OrganizationApplication,
    DecisionProcess, DecisionInvitation, DecisionParticipant, DecisionResponse, Event, EventInvitation, Document, DocumentInvitation};

function circleInviteExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function circleInviteFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $field => $value) $object->set($field, $value);
    $result = $object->save();
    circleInviteExpect(!empty($result['status']), $class . ': ' . json_encode($result));
    return $object;
}
function circleInviteIds(array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    sort($ids, SORT_NUMERIC);
    return $ids;
}

// All fixture changes are rolled back, including participants and ballots.
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
register_shutdown_function(static function () use ($pdo): void { if ($pdo->inTransaction()) $pdo->rollBack(); });
try {
    $nonce = bin2hex(random_bytes(5));
    $org = circleInviteFixture(Organization::class, ['name' => 'Circle invitation test', 'shortname' => 'circle-' . $nonce,
        'interface_level' => Organization::INTERFACE_LEVEL_EXPERT]);
    $oid = (int)$org->getId();
    foreach (['structure', 'decision', 'calendar', 'documents'] as $hash) {
        $app = new Application();
        circleInviteExpect($app->load(['hash', $hash]), 'Application exists: ' . $hash);
        circleInviteFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    }
    $root = circleInviteFixture(Holon::class, ['name' => 'Root', 'IDorganization' => $oid, 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $root->set('IDholon_org', $root->getId());
    circleInviteExpect(!empty($root->save()['status']), 'Root saved');
    $makeHolon = static fn(array $fields): Holon => circleInviteFixture(Holon::class,
        $fields + ['name' => 'Role', 'IDtypeholon' => 1, 'IDorganization' => $oid, 'IDholon_org' => $root->getId(),
            'IDholon_parent' => $root->getId(), 'active' => 1, 'visible' => 1]);
    $circle = $makeHolon(['name' => 'Pedagogy', 'IDtypeholon' => 2]);
    $role = $makeHolon(['IDholon_parent' => $circle->getId()]);
    $group = $makeHolon(['IDholon_parent' => $circle->getId(), 'IDtypeholon' => 3]);
    $groupRole = $makeHolon(['IDholon_parent' => $group->getId()]);
    $childCircle = $makeHolon(['name' => 'Child circle', 'IDholon_parent' => $circle->getId(), 'IDtypeholon' => 2]);
    $childRole = $makeHolon(['IDholon_parent' => $childCircle->getId()]);
    $linkRole = $makeHolon(['name' => 'Link role', 'IDholon_parent' => $childCircle->getId(), 'link' => 1]);
    $people = [];
    $memberships = [];
    foreach (['owner', 'direct', 'role', 'group', 'link_admin', 'link_member', 'child', 'inactive_link', 'technical', 'inactive_org', 'outsider'] as $name) {
        $people[$name] = circleInviteFixture(User::class, ['email' => "circle-$nonce-$name@example.invalid", 'firstname' => $name === 'role' ? '' : $name, 'active' => 1]);
        $memberships[$name] = circleInviteFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $people[$name]->getId(),
            'username' => 'Scoped ' . $name, 'active' => $name === 'inactive_org' ? 0 : 1]);
    }
    $links = [];
    foreach (['direct' => $circle, 'role' => $role, 'group' => $groupRole, 'link_admin' => $linkRole, 'link_member' => $linkRole,
        'child' => $childRole, 'inactive_link' => $role, 'technical' => $role, 'inactive_org' => $role] as $name => $holon) {
        $links[$name] = circleInviteFixture(UserHolon::class, ['IDholon' => $holon->getId(), 'IDuser' => $people[$name]->getId(),
            'active' => in_array($name, ['inactive_link', 'technical'], true) ? 0 : 1, 'is_membership' => $name === 'technical' ? 0 : 1,
            'parameters' => $name === 'link_admin' ? ['isAdmin' => true] : []]);
    }
    // A role member may also have a direct assignment; invitations must deduplicate them.
    circleInviteFixture(UserHolon::class, ['IDholon' => $circle->getId(), 'IDuser' => $people['group']->getId(), 'active' => 1, 'is_membership' => 1]);
    $expected = circleInviteIds(array_map(static fn($name) => $people[$name]->getId(), ['direct', 'role', 'group', 'link_admin']));
    $actual = circleInviteIds($circle->getAssociatedMemberUserIds(['organizationId' => $oid, 'skipPermissionFilter' => true, 'activeOnly' => true]));
    circleInviteExpect($actual === $expected,
        'Fixture effective members: ' . json_encode(['expected' => $expected, 'actual' => $actual, 'people' => array_map(static fn($user) => $user->getId(), $people)]));
    $decisionFields = ['IDorganization' => $oid, 'IDholon' => $circle->getId(), 'IDuser' => $people['owner']->getId(),
        'title' => 'Circle invitation', 'status' => 'evaluation', 'evaluation_start_at' => '2020-01-01 00:00:00', 'evaluation_method' => 'simple_vote'];
    $decision = circleInviteFixture(DecisionProcess::class, $decisionFields);
    $invitation = circleInviteFixture(DecisionInvitation::class, ['IDdecision_process' => $decision->getId(), 'invitation_type' => 'holon',
        'IDholon' => $circle->getId(), 'status' => 'invited', 'active' => 1]);
    circleInviteExpect($decision->getInvitationRecipientCount() === count($expected), 'Counter includes role and calculated members exactly once');
    circleInviteExpect(!empty($decision->syncParticipantsFromInvitations()['status']), 'Participants synchronized');
    $withOwner = circleInviteIds([...$expected, $people['owner']->getId()]);
    circleInviteExpect(circleInviteIds(DecisionParticipant::getActiveUserIdsForDecision($decision->getId())) === $withOwner, 'Effective members become active participants');
    $participant = DecisionParticipant::findByDecisionAndUser($decision->getId(), $people['role']->getId());
    circleInviteExpect($participant instanceof DecisionParticipant && $participant->get('display_name') === 'Scoped role', 'Organization display name retained');
    $rows = DecisionProcess::fetchListRowsForOrganization($oid, $people['role']->getId());
    circleInviteExpect(count($rows) === 1 && (bool)$rows[0]['has_user_participation'] && $decision->isParticipationInterfaceOpen(), 'Role member has the list participation button flags');
    $_SESSION['currentUser'] = (int)$people['role']->getId();
    $_SESSION['currentOrganization'] = $oid;
    $context = omoDecisionResolveEditorContext(['oid' => $oid, 'cid' => $circle->getId(), 'id' => $decision->getId(), 'intent' => 'participate']);
    circleInviteExpect(!empty($context['status']) && !empty($context['canParticipate']), 'Role member can open the participation interface: ' . json_encode($context));
    $response = circleInviteFixture(DecisionResponse::class, ['IDdecision_process' => $decision->getId(), 'IDdecision_group' => $decision->getPrimaryGroup(true)->getId(),
        'IDdecision_participant' => $participant->getId(), 'status' => 'submitted', 'submitted_at' => '2020-01-02 00:00:00']);
    circleInviteExpect(!empty($decision->syncParticipantsFromInvitations()['status']), 'Repeated synchronization succeeds');
    circleInviteExpect(DecisionParticipant::findByDecisionAndUser($decision->getId(), $people['role']->getId())->getId() === $participant->getId()
        && $response->load($response->getId(), true) && $response->get('status') === 'submitted', 'Existing participant identity and ballot preserved');
    $implicit = circleInviteFixture(DecisionProcess::class, $decisionFields);
    circleInviteExpect($implicit->getInvitationRecipientCount() === count($expected) && !empty($implicit->syncParticipantsFromInvitations()['status'])
        && circleInviteIds(DecisionParticipant::getActiveUserIdsForDecision($implicit->getId())) === $withOwner, 'Implicit circle invitations have the same effective membership');
    $rootDecision = circleInviteFixture(DecisionProcess::class, array_replace($decisionFields, ['IDholon' => $root->getId()]));
    circleInviteExpect(!empty($rootDecision->syncParticipantsFromInvitations()['status']) && $rootDecision->getInvitationRecipientCount() === count($people) - 2,
        'Root invitations still include active organization members without a role');

    // Audit the other two resource invitation types with the same role-only member.
    $event = circleInviteFixture(Event::class, ['IDorganization' => $oid, 'IDholon' => $circle->getId(), 'IDuser' => $people['owner']->getId(),
        'title' => 'Circle event', 'status' => 'confirmed', 'active' => 1, 'start_at' => '2030-01-01 09:00:00', 'end_at' => '2030-01-01 10:00:00']);
    circleInviteFixture(EventInvitation::class, ['IDevent' => $event->getId(), 'invitation_type' => 'holon', 'IDholon' => $circle->getId(), 'status' => 'invited', 'active' => 1]);
    circleInviteExpect(circleInviteIds($event->getEffectiveInvitationTargets($oid, fresh: true, activeOnly: true)['userIds']) === $expected,
        'Calendar invitations include the same active effective circle members');
    circleInviteExpect($event->canEditTimeBuffers((int)$people['role']->getId()), 'Role-only member has personal event participation access');
    $document = circleInviteFixture(Document::class, ['IDorganization' => $oid, 'IDholon' => $circle->getId(), 'IDuser' => $people['owner']->getId(),
        'title' => 'Circle document', 'documenttype' => Document::TYPE_PV, 'active' => 1]);
    circleInviteFixture(DocumentInvitation::class, ['IDdocument' => $document->getId(), 'invitation_type' => 'holon', 'IDholon' => $circle->getId(), 'status' => 'invited', 'active' => 1]);
    circleInviteExpect($document->isUserInvited((int)$people['role']->getId(), $oid), 'Document invitation includes a role-only member');
    circleInviteExpect(in_array((int)$people['role']->getId(), array_column($document->getInvitationEmailRecipients($oid), 'user_id'), true),
        'Document email recipients include a role-only member');

    // An explicit invitation to one role must not invite the containing circle.
    $invitation->set('IDholon', $role->getId());
    circleInviteExpect(!empty($invitation->save()['status']) && !empty($decision->syncParticipantsFromInvitations()['status']), 'Switch to role-only invitation');
    circleInviteExpect($decision->getInvitationRecipientCount() === 1
        && circleInviteIds(DecisionParticipant::getActiveUserIdsForDecision($decision->getId())) === circleInviteIds([$people['owner']->getId(), $people['role']->getId()]),
        'Role invitation excludes unrelated circle members');
    circleInviteFixture(DecisionInvitation::class, ['IDdecision_process' => $decision->getId(), 'invitation_type' => 'email',
        'email' => "circle-$nonce-external@example.invalid", 'status' => 'invited', 'active' => 1]);
    $invitation->set('status', 'revoked');
    circleInviteExpect(!empty($invitation->save()['status']) && !empty($decision->syncParticipantsFromInvitations()['status'])
        && $decision->getInvitationRecipientCount() === 1
        && circleInviteIds(DecisionParticipant::getActiveUserIdsForDecision($decision->getId())) === [(int)$people['owner']->getId()],
        'Revoked circle invitation grants no membership while external invite stays active');
    echo "Circle invitation membership: decisions, calendar and documents passed.\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
