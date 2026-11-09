<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/common.php';

use dbObject\{Application, ChatMessage, DecisionParticipant, DecisionProcess, DecisionProposal, DecisionResponse,
    OrganizationApplication, User, UserOrganization};

if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session'];
    $_POST = $_REQUEST = $request['post'] ?? [];
    $_GET = $request['get'] ?? [];
    $_SERVER['REQUEST_METHOD'] = $request['method'] ?? 'POST';
    $_SERVER['REQUEST_URI'] = $request['endpoint'];
    $GLOBALS['omoDecisionDeferProposalNotifications'] = true;
    require dirname(__DIR__) . $request['endpoint'];
    exit;
}

function anonymityRequest(array $session, string $endpoint, array $post = [], array $get = [], string $method = 'POST'): array
{
    $payload = base64_encode(json_encode(compact('session', 'endpoint', 'post', 'get', 'method'), JSON_THROW_ON_ERROR));
    $process = proc_open([PHP_BINARY, '-d', 'display_errors=1', __FILE__, '--request', $payload],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Start request');
    fclose($pipes[0]); $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0 && $errors === '', 'Request failed: ' . $errors . $output);
    return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
}

function anonymityProposal(int $id): DecisionProposal
{
    $proposal = new DecisionProposal();
    mcpCheck($proposal->load($id, true), 'Reload proposal');
    return $proposal;
}

$items = mcpFixtures(false);
$decisions = [];
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['role']->getId();
    $app = new Application(); mcpCheck($app->load(['hash', 'decision']), 'Decision app exists');
    $items['decision_app'] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $items['author'] = mcpFixture(User::class, ['email' => 'anonymous-author-' . bin2hex(random_bytes(5)) . '@example.invalid',
        'firstname' => 'ConfidentialAuthor', 'active' => 1]);
    $authorId = (int)$items['author']->getId();
    $items['author_membership'] = mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $authorId, 'active' => 1]);
    $session = ['currentUser' => $uid, 'currentOrganization' => $oid,
        'auth_security_version' => $items['user']->get('security_version')];

    foreach (['majority_judgment', 'simple_vote', 'consent'] as $method) {
        $decision = $decisions[] = mcpFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $hid,
            'IDuser' => $uid, 'title' => 'Anonymity ' . $method, 'evaluation_method' => $method, 'status' => 'draft',
            'parameters' => [$method => ['is_anonymous' => 1, 'allow_proposal_discussions' => 1]]]);
        $group = $decision->ensurePrimaryGroup();
        $proposals = [];
        foreach (['Old A', 'Old B'] as $position => $title) {
            $proposals[] = mcpFixture(DecisionProposal::class, ['IDdecision_process' => $decision->getId(),
                'IDdecision_group' => $group->getId(), 'IDuser_author' => $authorId,
                'title' => $title, 'position' => $position + 1, 'active' => 1]);
        }
        // Simulate a legacy proposal predating the per-proposal anonymity snapshot.
        $legacy = $proposals[0];
        $legacy->set('parameters', []);
        // The base save simulates old persisted content without bypassing dbObject data access.
        $legacySave = new ReflectionMethod(\dbObject\DbObject::class, 'save');
        mcpCheck(!empty($legacySave->invoke($legacy)['status']), 'Legacy proposal fixture saved');
        $staleProposal = anonymityProposal((int)$legacy->getId());
        $thread = $legacy->getChatThread(true, $uid);
        $oldSystemMessage = ChatMessage::createSystemMessage($thread, 'La proposition a été modifiée.', $authorId,
            ['action' => 'decision_proposal_updated']);
        $oldMessage = ChatMessage::createUserMessage($thread, $authorId, 'Anonymous before transition', true);
        mcpCheck($oldMessage instanceof ChatMessage, 'Anonymous discussion message created');
        mcpCheck($decision->canEnableNamedVote(), 'Collaborative proposals and discussions allow a future named vote.');

        $pdo = \dbObject\DbObject::getPdo();
        $pdo->beginTransaction();
        $group->set('parameters', [$method => ['is_anonymous' => 0, 'allow_proposal_discussions' => 1]]);
        mcpCheck(!empty($group->save()['status']), 'Transition and anonymity snapshot share a transaction');
        $pdo->rollBack();
        $group->load((int)$group->getId(), true);
        $legacyParameters = omoDecisionModuleDecodeParameters(anonymityProposal((int)$legacy->getId())->get('parameters'));
        mcpCheck(empty($legacyParameters['anonymous_author']) && anonymityProposal((int)$legacy->getId())->isDiscussionAnonymous(),
            'Rollback restores both the question setting and the anonymity snapshot');
        $sibling = $decision->addDecisionGroup($method, 'decision', 'Already named sibling');
        $sibling->set('parameters', [$method => ['is_anonymous' => 0]]);
        mcpCheck(!empty($sibling->save()['status']), 'Named sibling configured');
        $siblingProposal = mcpFixture(DecisionProposal::class, ['IDdecision_process' => $decision->getId(),
            'IDdecision_group' => $sibling->getId(), 'IDuser_author' => $authorId, 'title' => 'Other block', 'position' => 1, 'active' => 1]);

        $payload = ['oid' => $oid, 'cid' => $hid, 'id' => $decision->getId(), 'gid' => $group->getId(),
            'method' => $method, 'process_title' => $decision->get('title'), 'title' => 'Named question', 'status' => 'draft',
            'proposals' => ['Old A', 'Old B'], 'proposal_ids' => array_map(fn($p) => $p->getId(), $proposals),
            'proposal_content_title' => 1, 'allow_proposal_discussions' => 1];
        $saveModule = $method === 'simple_vote' ? 'vote' : $method;
        $saved = anonymityRequest($session, '/omo/api/decision/modules/' . $saveModule . '/save.php', $payload);
        mcpCheck(!empty($saved['status']), 'Switch before evaluation succeeds for ' . $method . ': ' . json_encode($saved));
        $group->load((int)$group->getId(), true);
        $config = omoDecisionModuleGetMethodParameters($group->get('parameters'), $method);
        mcpCheck(empty($config['is_anonymous']), 'Named setting saved');
        mcpCheck(!anonymityProposal((int)$siblingProposal->getId())->isAnonymous(), 'Changing one block preserves named authors in other blocks');
        foreach ($proposals as $proposal) {
            $reloaded = anonymityProposal((int)$proposal->getId());
            mcpCheck($reloaded->isAnonymous() && !$reloaded->isDiscussionAnonymous(), 'Old author remains hidden while new discussion is named');
        }
        $staleProposal->set('description', 'Updated after transition');
        mcpCheck(!empty($staleProposal->save()['status']), 'Stale proposal object can still save');
        mcpCheck(anonymityProposal((int)$legacy->getId())->isAnonymous(), 'A stale save cannot erase preserved anonymity');
        $newProposal = mcpFixture(DecisionProposal::class, ['IDdecision_process' => $decision->getId(),
            'IDdecision_group' => $group->getId(), 'IDuser_author' => $authorId, 'title' => 'New named proposal', 'position' => 3, 'active' => 1]);
        mcpCheck(!$newProposal->isAnonymous(), 'New proposal is named');

        $discussionContext = ['oid' => $oid, 'cid' => $hid, 'id' => $decision->getId(), 'proposal_id' => $legacy->getId()];
        $discussion = anonymityRequest($session, '/omo/api/decision/modules/proposals/discussion.php', [], $discussionContext, 'GET');
        mcpCheck(!empty($discussion['status']) && !$discussion['discussionIsAnonymous'], 'Existing proposal accepts named discussion');
        $old = array_column($discussion['messages'], null, 'id')[(int)$oldMessage->getId()];
        mcpCheck($old['authorUserId'] === 0 && $old['authorParticipantId'] === 0 && $old['photoUrl'] === ''
            && !str_contains($old['authorName'], 'ConfidentialAuthor'), 'Old message hides author name, IDs and photo');
        $oldSystem = array_column($discussion['messages'], null, 'id')[(int)$oldSystemMessage->getId()];
        mcpCheck($oldSystem['authorUserId'] === 0 && !str_contains($oldSystem['authorName'], 'ConfidentialAuthor'),
            'Old system events also preserve anonymous editors');
        $metadata = omoDecisionRenderProposalMetadata(anonymityProposal((int)$legacy->getId()),
            ['decision' => $decision, 'currentUserId' => $uid], fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'));
        mcpCheck(!str_contains($metadata, 'ConfidentialAuthor'), 'Discussion summary preserves old anonymous authors');

        $authorSession = ['currentUser' => $authorId, 'currentOrganization' => $oid,
            'auth_security_version' => $items['author']->get('security_version')];
        $decision->load((int)$decision->getId(), true);
        $decision->set('status', 'consultation');
        $decision->set('consultation_start_at', date('Y-m-d H:i:s', time() - 60));
        mcpCheck(!empty($decision->save()['status']), 'Consultation is open for discussion');
        mcpFixture(DecisionParticipant::class, ['IDdecision_process' => $decision->getId(), 'IDuser' => $authorId,
            'role' => 'participant', 'status' => 'active', 'active' => 1]);
        $decision->saveVisibilityRulesFromInput(['visibility_type' => 'organization']);
        $discussion = anonymityRequest($authorSession, '/omo/api/decision/modules/proposals/discussion.php',
            $discussionContext + ['content' => 'Named after transition']);
        mcpCheck(!empty($discussion['status']), 'Former anonymous author posts a new named message: ' . json_encode($discussion));
        $new = $discussion['messages'][count($discussion['messages']) - 1];
        mcpCheck($new['authorUserId'] === $authorId && str_contains($new['authorName'], 'ConfidentialAuthor'), 'New message is named');
        $old = array_column($discussion['messages'], null, 'id')[(int)$oldMessage->getId()];
        mcpCheck($old['authorUserId'] === 0 && !str_contains($old['authorName'], 'ConfidentialAuthor'), 'New named activity leaves the old message anonymous');
        mcpCheck(!in_array('proposal-author', array_column($new['authorRoles'], 'key'), true),
            'New named messages cannot reveal authorship of an old anonymous proposal through a badge');

        // An evaluation draft and an evaluation that has started both prevent enabling named voting.
        $response = mcpFixture(DecisionResponse::class, ['IDdecision_process' => $decision->getId(),
            'IDdecision_group' => $group->getId(), 'IDdecision_participant' => DecisionParticipant::findByDecisionAndUser((int)$decision->getId(), $uid)->getId(), 'status' => 'draft']);
        mcpCheck(!$decision->canEnableNamedVote(), 'An existing partial evaluation prevents an anonymity change');
        $response->delete();
        $decision->set('status', 'evaluation');
        $decision->set('evaluation_start_at', date('Y-m-d H:i:s', time() - 60));
        mcpCheck(!empty($decision->save()['status']), 'Evaluation started');
        mcpCheck(!$decision->canEnableNamedVote(), 'Started evaluation keeps named voting locked');
        $payload['is_anonymous'] = 1;
        $payload['status'] = 'evaluation';
        $rejected = anonymityRequest($session, '/omo/api/decision/modules/' . $saveModule . '/save.php', $payload);
        mcpCheck(empty($rejected['status']), 'Changing anonymity after evaluation starts is rejected by the endpoint');
        $group->load((int)$group->getId(), true);
        $config = omoDecisionModuleGetMethodParameters($group->get('parameters'), $method);
        mcpCheck(empty($config['is_anonymous']), 'Rejected save leaves evaluation anonymity unchanged');
    }
    echo "decision_anonymity_transition_test: OK\n";
} finally {
    foreach (array_reverse($decisions) as $decision) $decision->delete();
    mcpCleanup($items);
}
