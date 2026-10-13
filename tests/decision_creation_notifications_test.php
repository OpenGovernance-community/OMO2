<?php
declare(strict_types=1);

// Run as www-data in local Docker; exercise committed saves through the real endpoints.
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/notification_center.php';

use dbObject\{ArrayApplication, DbObject, DecisionProcess, HolonPermission, Notification, NotificationPreference,
    OrganizationApplication, Permission, User, UserHolon, UserOrganization};

function decisionNotificationHttp(string $endpoint, array $post): array
{
    $post['_csrf'] = commonCsrfToken();
    $cookie = session_name() . '=' . session_id();
    session_write_close();
    $curl = curl_init('https://localhost/omo/api/decision/' . $endpoint);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post),
        CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Cookie: ' . $cookie]]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    unset($curl);
    session_start();
    mcpCheck(is_string($body), 'HTTP response received');
    return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
}

function decisionNotificationSave(string $endpoint, array $post, array &$decisions): DecisionProcess
{
    $response = decisionNotificationHttp($endpoint, $post);
    mcpCheck($response['status'] === 200 && !empty($response['json']['status']), 'Save ballot: ' . $response['body']);
    $id = (int)($response['json']['decisionId'] ?? $response['json']['results'][0]['decisionId'] ?? 0);
    $decision = new DecisionProcess();
    mcpCheck($id > 0 && $decision->load($id, true), 'Saved ballot exists');
    $decisions[$id] = $decision;
    return $decision;
}

function decisionNotificationCount(int $userId, int $organizationId, string $event, int $decisionId = 0): int
{
    return count(array_filter(Notification::getInboxForUser($userId, $organizationId, 100),
        static fn($item) => $item->get('event_key') === $event
            && (!$decisionId || $item->get('source_key') === 'decision-phase-consultation-' . $decisionId)));
}

$before = $_SESSION;
$items = mcpFixtures();
$decisions = [];
try {
    $oid = (int)$items['org']->getId();
    $uid = (int)$items['user']->getId();
    $hid = (int)$items['root']->getId();
    $items['root']->set('IDholon_org', $hid);
    mcpCheck(!empty($items['root']->save()['status']), 'Root context saved');
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid,
        'auth_security_version' => (int)$items['user']->get('security_version')];
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'value' => 'decision']], 'limit' => 1]);
    $items['decision_app'] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $oid, 'IDapplication' => $apps[0]->getId(), 'active' => 1]);
    $items['creator_assignment'] = mcpFixture(UserHolon::class,
        ['IDholon' => $hid, 'IDuser' => $uid, 'active' => 1, 'is_membership' => 1]);
    $items['create_permission'] = mcpFixture(HolonPermission::class, ['IDholon' => $hid,
        'IDpermission' => Permission::findByKey('CAN_CREATE_DECISION')->getId(), 'range' => 'self', 'member_type' => 'member']);
    foreach (['direct', 'role_member', 'outsider'] as $name) {
        $items[$name] = mcpFixture(User::class, ['firstname' => $name,
            'email' => bin2hex(random_bytes(8)) . '@example.invalid', 'active' => 1]);
        $items[$name . '_org'] = mcpFixture(UserOrganization::class,
            ['IDuser' => $items[$name]->getId(), 'IDorganization' => $oid, 'active' => 1]);
    }
    $directId = (int)$items['direct']->getId();
    $roleMemberId = (int)$items['role_member']->getId();
    foreach ([$directId, $roleMemberId] as $person) {
        $items['role_assignment_' . $person] = mcpFixture(UserHolon::class,
            ['IDholon' => $items['role']->getId(), 'IDuser' => $person, 'active' => 1, 'is_membership' => 1]);
    }
    foreach ([$uid, $directId, $roleMemberId, (int)$items['outsider']->getId()] as $person) {
        foreach (['decision_consultation_started', 'decision_evaluation_started', 'decision_proposal_owner', 'decision_proposal_participant'] as $key) {
            mcpCheck(NotificationPreference::saveChannels($person, $oid, $key, ['in_app' => 1]), 'Local in-app preference saved');
        }
    }
    $now = new DateTimeImmutable();
    $base = ['oid' => $oid, 'cid' => $hid, 'process_title' => 'Notification fixture', 'title' => 'Question',
        'status' => DecisionProcess::STATUS_CONSULTATION, 'consultation_start_at' => $now->modify('-1 minute')->format('Y-m-d H:i:s'),
        'consultation_end_at' => $now->modify('+1 day')->format('Y-m-d H:i:s'), 'allow_consultation_proposals' => 1,
        'proposal_content_title' => 1, 'proposals' => ['A', 'B', 'C', 'D', 'E', 'F', 'G'],
        'invitation_inline_enabled' => 1, 'invitation_user_ids' => [$directId],
        'invitation_holon_ids' => [$items['role']->getId()]];

    foreach (['vote' => 'simple_vote', 'majority_judgment' => 'majority_judgment', 'consent' => 'consent',
        'consultation_only' => 'consultation_only'] as $module => $method) {
        $post = $base + ['evaluation_method' => $method];
        $decision = decisionNotificationSave('modules/' . $module . '/save.php', $post, $decisions);
        mcpCheck(count($decision->getProposals(false)) === 7, 'Seven initial proposals saved for ' . $module);
        foreach ([$directId, $roleMemberId] as $person) {
            mcpCheck(decisionNotificationCount($person, $oid, 'decision_consultation_started', $decision->getId()) === 1,
                'One opening notification per direct/role invitee, including overlapping invitations: ' . $module);
            mcpCheck(decisionNotificationCount($person, $oid, 'decision_proposal_participant') === 0, 'Initial proposals stay silent');
        }
        // An unchanged save must not produce another opening notification.
        $post['id'] = $decision->getId();
        $post['proposal_ids'] = array_map(static fn($proposal) => $proposal->getId(), iterator_to_array($decision->getProposals(false)));
        decisionNotificationSave('modules/' . $module . '/save.php', $post, $decisions);
        mcpCheck(decisionNotificationCount($directId, $oid, 'decision_consultation_started', $decision->getId()) === 1, 'No duplicate on edit');
    }
    mcpCheck(decisionNotificationCount($uid, $oid, 'decision_proposal_owner') === 0, 'Initial proposals do not notify the owner');
    mcpCheck(Notification::getInboxForUser((int)$items['outsider']->getId(), $oid) === [], 'Non-invited members receive nothing');

    $first = $base + ['evaluation_method' => 'simple_vote', 'method' => 'simple_vote'];
    $second = array_replace($base, ['evaluation_method' => 'consent', 'method' => 'consent', 'title' => 'Second question']);
    $multi = decisionNotificationSave('save_multi.php', ['groups' => [http_build_query($first), http_build_query($second)]], $decisions);
    $groups = $multi->getDecisionGroups(false);
    $proposalCount = 0;
    foreach ($groups as $group) $proposalCount += count($group->getProposals(false));
    mcpCheck(count($groups) === 2 && $proposalCount === 14, 'Both questions save all initial proposals');
    mcpCheck(decisionNotificationCount($directId, $oid, 'decision_consultation_started', $multi->getId()) === 1, 'One opening for the whole ballot');
    mcpCheck(decisionNotificationCount($directId, $oid, 'decision_proposal_participant') === 0, 'Every initial question stays silent');
    $finalInvitations = array_replace($second, ['invitation_holon_ids' => [], 'invitation_user_ids' => [$roleMemberId]]);
    $finalAudience = decisionNotificationSave('save_multi.php', ['groups' => [http_build_query($first), http_build_query($finalInvitations)]], $decisions);
    mcpCheck(decisionNotificationCount($directId, $oid, 'decision_consultation_started', $finalAudience->getId()) === 0,
        'Intermediate invitations must not receive an opening before the complete save commits');
    mcpCheck(decisionNotificationCount($roleMemberId, $oid, 'decision_consultation_started', $finalAudience->getId()) === 1,
        'Opening uses the final invitation list');
    // A later added question still notifies for its new proposals.
    $later = array_replace($second, ['id' => $multi->getId(), 'group_action' => 'create', 'title' => 'Later question', 'proposals' => ['Later A', 'Later B']]);
    decisionNotificationSave('save_multi.php', ['groups' => [http_build_query($later)]], $decisions);
    mcpCheck(decisionNotificationCount($directId, $oid, 'decision_proposal_participant') === 2, 'Later question proposals remain notified');
    mcpCheck(decisionNotificationCount($directId, $oid, 'decision_consultation_started', $multi->getId()) === 1, 'Later question does not reopen notifications');

    // Creation in preparation stays silent; starting elaboration by saving notifies immediately.
    $draftPost = array_replace($first, ['status' => 'draft', 'consultation_start_at' => '', 'consultation_end_at' => '']);
    $draft = decisionNotificationSave('modules/vote/save.php', $draftPost, $decisions);
    mcpCheck(decisionNotificationCount($directId, $oid, 'decision_consultation_started', $draft->getId()) === 0, 'Draft has no opening notification');
    $manual = array_replace($first, ['id' => $draft->getId(),
        'proposal_ids' => array_map(static fn($proposal) => $proposal->getId(), iterator_to_array($draft->getProposals(false)))]);
    decisionNotificationSave('modules/vote/save.php', $manual, $decisions);
    mcpCheck(decisionNotificationCount($directId, $oid, 'decision_consultation_started', $draft->getId()) === 1, 'Manual start notifies at save');

    $automatic = decisionNotificationSave('modules/vote/save.php', array_replace($first, ['status' => 'draft']), $decisions);
    mcpCheck($automatic->get('status') === 'consultation'
        && decisionNotificationCount($directId, $oid, 'decision_consultation_started', $automatic->getId()) === 1,
        'Past start date opens and notifies during the first save');

    $scheduled = decisionNotificationSave('modules/vote/save.php', array_replace($first, ['status' => 'scheduled',
        'consultation_start_at' => $now->modify('+1 hour')->format('Y-m-d H:i:s')]), $decisions);
    mcpCheck(decisionNotificationCount($directId, $oid, 'decision_consultation_started', $scheduled->getId()) === 0, 'Future elaboration stays silent');
    $pdo = DbObject::getPdo();
    $pdo->beginTransaction();
    $scheduled->set('consultation_start_at', $now->modify('-1 minute'));
    mcpCheck(!empty($scheduled->save()['status']) && $scheduled->syncLifecycleStatus(), 'Lifecycle may advance within a batch transaction');
    mcpCheck(decisionNotificationCount($directId, $oid, 'decision_consultation_started', $scheduled->getId()) === 0,
        'No opening is dispatched inside an uncommitted transaction');
    $pdo->rollBack();
    $scheduled->load($scheduled->getId(), true);
    mcpCheck($scheduled->syncLifecycleStatus($now->modify('+2 hours')), 'Scheduled phase starts automatically');
    $scheduled->syncLifecycleStatus($now->modify('+2 hours'));
    mcpCheck(decisionNotificationCount($directId, $oid, 'decision_consultation_started', $scheduled->getId()) === 1, 'Scheduled start notifies once');

    // A failed batch must not notify for the successfully processed first question.
    $beforeCount = count(Notification::getInboxForUser($directId, $oid, 100));
    $invalid = array_replace($second, ['title' => '']);
    $failed = decisionNotificationHttp('save_multi.php', ['groups' => [http_build_query($first), http_build_query($invalid)]]);
    mcpCheck($failed['status'] >= 400 && empty($failed['json']['status']), 'Invalid final question rolls back the batch');
    mcpCheck(count(Notification::getInboxForUser($directId, $oid, 100)) === $beforeCount, 'Rollback sends no notifications');
    mcpCheck(decisionNotificationCount($uid, $oid, 'decision_proposal_owner') === 0, 'Owner still receives no notifications for their own proposals');

    $items['org']->set('interface_level', \dbObject\Organization::INTERFACE_LEVEL_EXPERT);
    mcpCheck(!empty($items['org']->save()['status']), 'Expert governance enabled');
    $items['decision_app']->set('parameters', ['decisionSettings' => ['governance' => ['enabled' => true]]]);
    mcpCheck(!empty($items['decision_app']->save()['status']), 'Governance creation enabled');
    $items['rule_permission'] = mcpFixture(HolonPermission::class, ['IDholon' => $hid,
        'IDpermission' => Permission::findByKey('CAN_CREATE_RULE')->getId(), 'range' => 'self', 'member_type' => 'collective']);
    $governance = decisionNotificationSave('governance/save.php', ['oid' => $oid, 'cid' => $hid,
        'process_title' => 'Governance notification', 'consent_question' => 'Adopt these rules?',
        'consultation_end_at' => $now->modify('+1 day')->format('Y-m-d H:i:s'),
        'evaluation_end_at' => $now->modify('+2 days')->format('Y-m-d H:i:s'),
        'governance_blueprint' => json_encode(array_map(static fn($i) => ['title' => 'Rule ' . $i, 'actions' => [
            ['type' => 'rule.create', 'holonId' => $hid, 'after' => ['title' => 'Rule ' . $i, 'description' => '<p>Rule content</p>',
                'review_date' => '2027-01-01', 'expiration_date' => '2027-12-31']]]], range(1, 7)))], $decisions);
    mcpCheck(count($governance->getProposals(false)) === 7, 'Seven governance proposals saved');
    mcpCheck(decisionNotificationCount($roleMemberId, $oid, 'decision_consultation_started', $governance->getId()) === 1,
        'Governance creation immediately notifies its native invitees');
    mcpCheck(decisionNotificationCount($roleMemberId, $oid, 'decision_proposal_participant') === 2,
        'Initial governance proposals also stay silent');

    // A proposal subsequently submitted by an invitee still reaches its owner and other invitees.
    $actorSession = $_SESSION;
    $_SESSION = ['currentUser' => $directId, 'currentOrganization' => $oid,
        'auth_security_version' => (int)$items['direct']->get('security_version')];
    $added = decisionNotificationHttp('modules/proposals/consultation_add.php', ['oid' => $oid, 'cid' => $hid,
        'id' => $automatic->getId(), 'consultation_proposal_title' => 'Later participant proposal', 'ajax' => 1]);
    $_SESSION = $actorSession;
    mcpCheck($added['status'] === 200 && !empty($added['json']['status']), 'Participant adds a later proposal: ' . $added['body']);
    mcpCheck(decisionNotificationCount($uid, $oid, 'decision_proposal_owner') === 1, 'Later participant proposal notifies owner');
    mcpCheck(decisionNotificationCount($roleMemberId, $oid, 'decision_proposal_participant') === 3, 'Later participant proposal notifies other invitees');
    mcpCheck(decisionNotificationCount($directId, $oid, 'decision_proposal_participant') === 2, 'Proposal author is excluded');
    echo "decision_creation_notifications_test: OK\n";
} finally {
    if (DbObject::getPdo()->inTransaction()) DbObject::getPdo()->rollBack();
    foreach (array_reverse($decisions) as $decision) $decision->deleteWithRelations();
    mcpCleanup($items);
    $_SESSION = $before;
}
