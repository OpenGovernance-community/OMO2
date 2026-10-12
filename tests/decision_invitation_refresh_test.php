<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/context.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/common.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/omo/translations.php';

use dbObject\{DbObject, Organization, Application, OrganizationApplication, Holon, User, UserOrganization, UserHolon,
    DecisionProcess, DecisionInvitation, DecisionParticipant, DecisionResponse};

function refreshExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function refreshFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $field => $value) $object->set($field, $value);
    refreshSave($object);
    return $object;
}
function refreshSave(DbObject $object): void
{
    $result = $object->save();
    refreshExpect(!empty($result['status']), get_class($object) . ': ' . json_encode($result));
}
function refreshHas(DecisionProcess $decision, User $user): bool
{
    return in_array((int)$user->getId(), DecisionParticipant::getActiveUserIdsForDecision($decision->getId()), true);
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
register_shutdown_function(static function () use ($pdo): void { if ($pdo->inTransaction()) $pdo->rollBack(); });
try {
    $nonce = bin2hex(random_bytes(5));
    $org = refreshFixture(Organization::class, ['name' => 'Dynamic invitation test', 'shortname' => 'refresh-' . $nonce,
        'interface_level' => Organization::INTERFACE_LEVEL_EXPERT]);
    $oid = (int)$org->getId();
    foreach (['structure', 'decision'] as $hash) {
        $app = new Application();
        refreshExpect($app->load(['hash', $hash]), 'Application exists');
        refreshFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    }
    $root = refreshFixture(Holon::class, ['name' => 'Root', 'IDorganization' => $oid, 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $root->set('IDholon_org', $root->getId());
    refreshSave($root);
    $makeHolon = static fn(array $fields): Holon => refreshFixture(Holon::class, $fields + ['name' => 'Role',
        'IDorganization' => $oid, 'IDholon_org' => $root->getId(), 'IDholon_parent' => $root->getId(),
        'IDtypeholon' => 1, 'active' => 1, 'visible' => 1]);
    $circle = $makeHolon(['name' => 'Pedagogy', 'IDtypeholon' => 2]);
    $role = $makeHolon(['IDholon_parent' => $circle->getId()]);
    $child = $makeHolon(['IDholon_parent' => $circle->getId(), 'IDtypeholon' => 2]);
    $linkRole = $makeHolon(['IDholon_parent' => $child->getId(), 'link' => 1]);
    $people = $memberships = [];
    foreach (['owner', 'voter', 'late', 'link', 'direct', 'declined', 'public', 'manual'] as $name) {
        $people[$name] = refreshFixture(User::class, ['email' => "refresh-$nonce-$name@example.invalid", 'active' => 1]);
        $memberships[$name] = refreshFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $people[$name]->getId(), 'active' => 1]);
    }
    $links = [];
    foreach (['voter' => $role, 'direct' => $circle, 'declined' => $role] as $name => $holon) {
        $links[$name] = refreshFixture(UserHolon::class, ['IDholon' => $holon->getId(), 'IDuser' => $people[$name]->getId(), 'active' => 1, 'is_membership' => 1]);
    }
    $fields = ['IDorganization' => $oid, 'IDholon' => $circle->getId(), 'IDuser' => $people['owner']->getId(),
        'title' => 'Dynamic invitation', 'status' => 'evaluation', 'evaluation_start_at' => '2020-01-01 00:00:00', 'evaluation_method' => 'simple_vote'];
    $decisions = [];
    foreach (['explicit', 'implicit', 'root', 'results', 'archived', 'expired'] as $kind) {
        $values = $fields;
        if ($kind === 'root') $values['IDholon'] = $root->getId();
        if (in_array($kind, ['results', 'archived'], true)) $values['status'] = $kind;
        $decisions[$kind] = refreshFixture(DecisionProcess::class, $values);
        if ($kind !== 'implicit' && $kind !== 'root') refreshFixture(DecisionInvitation::class, ['IDdecision_process' => $decisions[$kind]->getId(),
            'invitation_type' => 'holon', 'IDholon' => $circle->getId(), 'active' => 1, 'status' => 'invited']);
        refreshExpect(!empty($decisions[$kind]->syncParticipantsFromInvitations()['status']), 'Initial snapshot');
    }
    // Simulate a decision finished by date but not processed by cron yet.
    $decisions['expired']->set('evaluation_end_at', '2020-01-02 00:00:00');
    refreshSave($decisions['expired']);
    $decision = $decisions['explicit'];
    $voter = DecisionParticipant::findByDecisionAndUser($decision->getId(), $people['voter']->getId());
    $voterId = (int)$voter->getId();
    $token = $voter->ensureAccessToken();
    $ballot = refreshFixture(DecisionResponse::class, ['IDdecision_process' => $decision->getId(),
        'IDdecision_group' => $decision->getPrimaryGroup(true)->getId(), 'IDdecision_participant' => $voterId,
        'status' => 'submitted', 'submitted_at' => '2020-01-01 00:00:00', 'parameters' => ['preserved' => true]]);
    refreshExpect($ballot->load($ballot->getId(), true), 'Ballot persisted');
    $ballotParameters = $ballot->get('parameters');
    $declined = DecisionParticipant::findByDecisionAndUser($decision->getId(), $people['declined']->getId());
    $declined->set('status', DecisionParticipant::STATUS_DECLINED);
    refreshSave($declined);
    foreach (['public', 'manual'] as $name) {
        refreshFixture(DecisionParticipant::class, ['IDdecision_process' => $decision->getId(), 'IDuser' => $people[$name]->getId(),
            'role' => $name === 'manual' ? 'observer' : 'participant', 'status' => 'active', 'active' => 1, 'parameters' => $name === 'public' ? ['sync_source' => 'public_opt_in'] : []]);
    }
    refreshFixture(UserHolon::class, ['IDholon' => $circle->getId(), 'IDuser' => $people['manual']->getId(), 'active' => 1, 'is_membership' => 1]);
    refreshFixture(DecisionInvitation::class, ['IDdecision_process' => $decision->getId(), 'invitation_type' => 'user',
        'IDuser' => $people['direct']->getId(), 'status' => 'invited', 'active' => 1]);
    // Existing decisions automatically admit a new role member, without saving their invitations.
    $lateLink = refreshFixture(UserHolon::class, ['IDholon' => $role->getId(), 'IDuser' => $people['late']->getId(), 'active' => 1, 'is_membership' => 1]);
    $_SESSION['currentUser'] = (int)$people['late']->getId();
    $_SESSION['currentOrganization'] = $oid;
    $context = omoDecisionResolveEditorContext(['oid' => $oid, 'cid' => $circle->getId(), 'id' => $decision->getId(), 'intent' => 'participate']);
    refreshExpect(!empty($context['status']) && !empty($context['canParticipate']), 'New role member can enter before opening the list');
    refreshExpect(refreshHas($decision, $people['late']), 'Participant created retroactively');
    DecisionProcess::fetchListRowsForOrganization($oid, $people['late']->getId());
    foreach (['explicit', 'implicit', 'root'] as $kind) refreshExpect(refreshHas($decisions[$kind], $people['late']), 'Open invitations refreshed: ' . $kind);
    foreach (['results', 'archived', 'expired'] as $kind) {
        refreshExpect(!refreshHas($decisions[$kind], $people['late']), 'Historical membership frozen: ' . $kind);
        refreshExpect($decisions[$kind]->getInvitationRecipientCount() === 3, 'Historical recipient count frozen: ' . $kind);
        $summary = omoDecisionBuildInvitationSummaryData($decisions[$kind], ['organizationId' => $oid, 'targetHolonId' => $circle->getId(), 'effectiveHolon' => $circle],
            null, omoDecisionInvitationGetSourceLang());
        refreshExpect($summary['recipientCount'] === 3 && !str_contains($summary['recipientTooltip'], (string)$people['late']->get('email')), 'Historical invitation summary uses its snapshot: ' . $kind);
        refreshExpect($decisions[$kind]->findAccessibleParticipantByEmail($people['late']->get('email')) === null, 'Email lookup preserves closed snapshot: ' . $kind);
    }
    refreshExpect(DecisionParticipant::findByDecisionAndUser($decision->getId(), $people['declined']->getId())->get('status') === 'declined', 'Personal refusal preserved');
    refreshExpect(refreshHas($decision, $people['public']) && refreshHas($decision, $people['manual']), 'Voluntary and manual participants preserved');
    refreshExpect(DecisionParticipant::findByDecisionAndUser($decision->getId(), $people['manual']->getId())->get('role') === 'observer', 'Automatic membership refresh preserves observer access');
    // Link-role admin changes affect the enclosing circle without a direct circle assignment.
    $link = refreshFixture(UserHolon::class, ['IDholon' => $linkRole->getId(), 'IDuser' => $people['link']->getId(), 'active' => 1, 'is_membership' => 1]);
    DecisionProcess::fetchListRowsForOrganization($oid);
    refreshExpect(!refreshHas($decision, $people['link']), 'Ordinary child role member excluded');
    $link->set('parameters', ['isAdmin' => true]);
    refreshSave($link);
    DecisionProcess::fetchListRowsForOrganization($oid);
    refreshExpect(refreshHas($decision, $people['link']), 'Calculated link-role admin automatically invited');
    $linkRole->set('link', 0);
    refreshSave($linkRole);
    DecisionProcess::fetchListRowsForOrganization($oid);
    refreshExpect(!refreshHas($decision, $people['link']), 'Removing link-role property revokes calculated membership');
    $template = $makeHolon(['name' => 'Link template', 'templatename' => 'Link-' . $nonce, 'link' => 1, 'visible' => 0]);
    $linkRole->set('IDholon_template', $template->getId());
    refreshSave($linkRole);
    DecisionProcess::fetchListRowsForOrganization($oid);
    refreshExpect(refreshHas($decision, $people['link']), 'Inherited template property establishes calculated membership');
    $template->set('link', 0);
    refreshSave($template);
    DecisionProcess::fetchListRowsForOrganization($oid);
    refreshExpect(!refreshHas($decision, $people['link']), 'Changing role template refreshes calculated membership');
    // A role move removes members of that role while preserving explicit invitations.
    $role->set('IDholon_parent', $child->getId());
    refreshSave($role);
    DecisionProcess::fetchListRowsForOrganization($oid);
    refreshExpect(!refreshHas($decision, $people['late']), 'Moving a role out of the circle revokes its members');
    foreach (['results', 'archived', 'expired'] as $kind) refreshExpect(refreshHas($decisions[$kind], $people['voter']), 'Former circle member remains in historical snapshot: ' . $kind);
    $links['direct']->set('active', 0);
    refreshSave($links['direct']);
    DecisionProcess::fetchListRowsForOrganization($oid);
    refreshExpect(refreshHas($decision, $people['direct']), 'Explicit individual invitation survives departure from circle');
    // A personal participation link must refresh membership before checking its token.
    refreshExpect(omoDecisionResolvePublicParticipantByToken($token) === null, 'Departed voter token cannot participate');
    refreshExpect($ballot->load($ballot->getId(), true) && $ballot->get('status') === 'submitted', 'Departure keeps existing ballot');
    $role->set('IDholon_parent', $circle->getId());
    refreshSave($role);
    $restored = omoDecisionResolvePublicParticipantByToken($token);
    refreshExpect($restored && (int)$restored->getId() === $voterId && $restored->get('access_token') === $token, 'Returning member retains identity and token');
    refreshExpect($ballot->load($ballot->getId(), true) && $ballot->get('parameters') === $ballotParameters, 'Returning member retains ballot content');
    $memberships['late']->set('active', 0);
    refreshSave($memberships['late']);
    DecisionProcess::fetchListRowsForOrganization($oid);
    refreshExpect(!refreshHas($decision, $people['late']) && !refreshHas($decisions['root'], $people['late']), 'Organization deactivation removes eligibility');
    $memberships['late']->set('active', 1);
    refreshSave($memberships['late']);
    $choiceRows = DecisionProcess::fetchRelevantRowsForUser((int)$people['late']->getId());
    refreshExpect(in_array((int)$decision->getId(), array_column($choiceRows, 'id'), true), 'Choice list refreshes before filtering by participant');
    // Stable reads leave participant timestamps unchanged, including with read memoization enabled.
    $timestamps = static fn(): array => array_map(static fn($participant) => [(int)$participant->getId(),
        $participant->get('updated_at')?->format('Y-m-d H:i:s')], $decision->getParticipants()->getArrayCopy());
    $before = $timestamps();
    DbObject::enableReadOnlyMemoization(true);
    $decision->syncLifecycleStatus();
    refreshExpect(DbObject::isReadOnlyMemoizationEnabled(), 'Unchanged refresh does not issue writes');
    $after = $timestamps();
    refreshExpect($before === $after, 'Unchanged refresh preserves participant timestamps');
    echo "Decision invitation refresh: roles, links, tokens, ballots and historical decisions passed.\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
