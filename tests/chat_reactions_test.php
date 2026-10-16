<?php
declare(strict_types=1);
// Run with: docker compose exec -T --user www-data app php tests/chat_reactions_test.php
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{ChatMessage, ChatThread, ChatMessageReaction, Project, DecisionProcess, DecisionGroup,
    DecisionProposal, DecisionParticipant, Document, DocumentPvPoint, DocumentShareLink, Application, OrganizationApplication};

if (($argv[1] ?? '') === '--worker') {
    $thread = new ChatThread(); $thread->load((int)$argv[2]);
    for ($i = 0; $i < 12; $i++) ChatMessageReaction::setForMessage($thread, (int)$argv[3], $argv[4], $i % 2 ? 'clap' : 'down');
    exit;
}

function reactionHttp(string $endpoint, array $context, ?array $post = null, bool $csrf = true): array
{
    if ($post !== null && $csrf) $post['_csrf'] = commonCsrfToken();
    $cookie = session_name() . '=' . session_id();
    session_write_close();
    $curl = curl_init('https://localhost' . $endpoint . '?' . http_build_query($context));
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Cookie: ' . $cookie]]);
    if ($post !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post + $context)]);
    $body = curl_exec($curl); $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    session_start();
    return ['status' => $status, 'body' => $body, 'json' => json_decode((string)$body, true)];
}

$before = $_SESSION;
$items = mcpFixtures(false);
try {
    mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'Local fixtures only');
    $uid = (int)$items['user']->getId(); $oid = (int)$items['org']->getId(); $cid = (int)$items['root']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid, 'auth_security_version' => (int)$items['user']->get('security_version')];
    $items['membership']->set('parameters', ['isAdmin' => true]); $items['membership']->save();
    commonSetCurrentUserAdminMode(true, $oid);
    $items['author'] = mcpFixture(\dbObject\User::class, ['firstname' => 'Autre auteur',
        'email' => 'chat-author-' . bin2hex(random_bytes(6)) . '@example.invalid', 'active' => 1]);
    $authorId = (int)$items['author']->getId();
    $items['author_membership'] = mcpFixture(\dbObject\UserOrganization::class,
        ['IDuser' => $authorId, 'IDorganization' => $oid, 'active' => 1]);
    foreach (['projects', 'documents', 'decision'] as $hash) {
        $app = new Application();
        if ($app->load(['hash', $hash])) $items['app_' . $hash] = mcpFixture(OrganizationApplication::class,
            ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    }
    $items['project'] = mcpFixture(Project::class, ['IDorganization' => $oid, 'IDuser' => $uid,
        'title' => 'Reaction project', 'visibility_type' => 'organization', 'active' => 1]);
    $items['pv'] = mcpFixture(Document::class, ['title' => 'Reaction PV', 'documenttype' => Document::TYPE_PV,
        'pvstage' => Document::PV_STAGE_REVIEW, 'IDorganization' => $oid, 'IDholon' => $cid, 'IDuser' => $uid,
        'IDusercreation' => $uid, 'IDuser_pv_editor' => $uid, 'IDuser_pv_official_editor' => $uid, 'active' => 1]);
    $items['point'] = mcpFixture(DocumentPvPoint::class, ['IDdocument' => $items['pv']->getId(), 'title' => 'Reaction point',
        'pointtype' => 'information', 'IDuser_author' => $uid, 'active' => 1]);
    foreach (['guest_link', 'second_guest_link'] as $key) $items[$key] = mcpFixture(DocumentShareLink::class,
        ['IDorganization' => $oid, 'IDdocument' => $items['pv']->getId(), 'IDuser' => $uid,
            'token' => DocumentShareLink::generateUniqueToken(), 'recipient_email' => 'guest@example.invalid',
            'allow_pv_contribution' => 1, 'active' => 1]);
    $items['decision'] = mcpFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDuser' => $uid,
        'title' => 'Reaction decision', 'decision_type' => 'decision', 'evaluation_method' => 'simple_vote',
        'status' => 'consultation', 'consultation_end_at' => new DateTimeImmutable('+1 day'),
        'evaluation_end_at' => new DateTimeImmutable('+2 days'), 'visibility_type' => 'organization']);
    $items['group'] = mcpFixture(DecisionGroup::class, ['IDdecision_process' => $items['decision']->getId(), 'title' => 'Question']);
    $items['proposal'] = mcpFixture(DecisionProposal::class, ['IDdecision_process' => $items['decision']->getId(),
        'IDdecision_group' => $items['group']->getId(), 'title' => 'Reaction proposal', 'IDuser_author' => $uid, 'active' => 1]);
    $items['participant'] = DecisionParticipant::findByDecisionAndUser((int)$items['decision']->getId(), $uid);
    mcpCheck($items['participant'] instanceof DecisionParticipant, 'Decision owner participant created');
    $items['guest_participant'] = mcpFixture(DecisionParticipant::class, ['IDdecision_process' => $items['decision']->getId(),
        'email' => 'guest@example.invalid', 'role' => 'participant', 'status' => 'active', 'active' => 1]);
    $contexts = [
        'project' => ['endpoint' => '/omo/api/projects/discussion.php', 'context' => ['oid' => $oid, 'project_id' => $items['project']->getId()]],
        'pv' => ['endpoint' => '/omo/api/documents/pv/discussion.php', 'context' => ['oid' => $oid, 'document_id' => $items['pv']->getId(), 'point_id' => $items['point']->getId()]],
        'decision' => ['endpoint' => '/omo/api/decision/modules/proposals/discussion.php', 'context' => ['oid' => $oid, 'id' => $items['decision']->getId(), 'proposal_id' => $items['proposal']->getId()]],
    ];
    foreach (['project' => [ChatThread::SUBJECT_PROJECT, $items['project']->getId()],
        'pv' => [ChatThread::SUBJECT_DOCUMENT_PV_POINT, $items['point']->getId()],
        'decision' => [ChatThread::SUBJECT_DECISION_PROPOSAL, $items['proposal']->getId()]] as $name => [$type, $id]) {
        $thread = ChatThread::getOrCreateForSubject($oid, $type, $id, $uid);
        $items[$name . '_thread'] = $thread;
        $items[$name . '_message'] = ChatMessage::createUserMessage($thread, $authorId, 'Une proposition a soutenir.');
        $contexts[$name]['messageId'] = (int)$items[$name . '_message']->getId();
        $items[$name . '_own_message'] = ChatMessage::createUserMessage($thread, $uid, 'Mon propre message.', $name === 'decision', $name === 'decision');
        $contexts[$name]['ownMessageId'] = (int)$items[$name . '_own_message']->getId();
    }
    if (in_array('--browser', $argv, true)) {
        echo json_encode(['origin' => omoMcpIssuer(), 'cookie' => ['name' => session_name(), 'value' => session_id()],
            'contexts' => $contexts], JSON_THROW_ON_ERROR) . "\n";
        fflush(STDOUT); session_write_close(); fgets(STDIN); session_start();
    } else {
        $thread = $items['project_thread']; $mid = (int)$items['project_message']->getId();
        $actor = ChatMessageReaction::actorKey($uid);
        mcpCheck($actor === ChatMessageReaction::actorKey(0, (int)$items['participant']->getId()), 'Invitation and account share one identity');
        mcpCheck(ChatMessageReaction::actorKey(0, 0, (int)$items['guest_link']->getId()) ===
            ChatMessageReaction::actorKey(0, 0, (int)$items['second_guest_link']->getId()), 'Reissued PV invitations share one identity');
        $first = ChatMessageReaction::setForMessage($thread, $mid, $actor, 'up');
        mcpCheck($first['counts'] === ['up' => 1, 'down' => 0, 'clap' => 0], 'First vote counted');
        $staleMessage = new ChatMessage(); $staleMessage->load($mid, true);
        mcpCheck(ChatMessageReaction::setForMessage($thread, $mid, $actor, 'up') === $first, 'Retry is idempotent');
        $changed = ChatMessageReaction::setForMessage($thread, $mid, $actor, 'down');
        mcpCheck($changed['counts'] === ['up' => 0, 'down' => 1, 'clap' => 0], 'Changing vote replaces it');
        $staleMessage->set('content', 'Une proposition a soutenir.'); $staleMessage->save();
        mcpCheck(ChatMessageReaction::forMessages($thread, [$mid], $actor)[0] === $changed, 'Saving a stale message preserves newer totals');
        $cleared = ChatMessageReaction::setForMessage($thread, $mid, $actor, '');
        mcpCheck(array_sum($cleared['counts']) === 0, 'Vote can be removed');
        mcpCheck(ChatMessageReaction::setForMessage($thread, $mid, $actor, '') === $cleared, 'Removal retry is idempotent');
        foreach ([[$thread, $mid, 'invalid'], [$items['pv_thread'], $mid, 'up']] as [$target, $id, $kind]) {
            $rejected = false;
            try { ChatMessageReaction::setForMessage($target, $id, $actor, $kind); } catch (InvalidArgumentException $error) { $rejected = true; }
            mcpCheck($rejected, 'Invalid kind or cross-thread message rejected');
        }
        $items['system'] = ChatMessage::createSystemMessage($thread, 'System event', $uid);
        $rejected = false;
        try { ChatMessageReaction::setForMessage($thread, (int)$items['system']->getId(), $actor, 'up'); } catch (InvalidArgumentException $error) { $rejected = true; }
        mcpCheck($rejected, 'System messages cannot receive reactions');
        mcpCheck(ChatMessageReaction::forMessages($items['pv_thread'], [$mid], $actor) === [], 'Snapshot cannot read another thread');
        $workers = [];
        // Two simultaneous requests for the same person plus distinct people on the same message.
        foreach ([$actor, $actor, 'participant:fixture-a', 'participant:fixture-b'] as $key) {
            $workers[] = proc_open([PHP_BINARY, __FILE__, '--worker', (string)$thread->getId(), (string)$mid, $key],
                [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => STDERR], $pipes);
        }
        foreach ($workers as $worker) mcpCheck(proc_close($worker) === 0, 'Concurrent worker succeeds');
        $state = ChatMessageReaction::forMessages($thread, [$mid], $actor)[0];
        mcpCheck($state['counts'] === ['up' => 0, 'down' => 0, 'clap' => 3] && $state['own'] === 'clap', 'Concurrent counts retain exactly one vote per identity');
        $details = new class extends \dbObject\ArrayDbObject {
            public static function objectName() { return ChatMessageReaction::class; }
        };
        $details->load(['where' => [['field' => 'IDchat_message', 'value' => $mid]]]);
        mcpCheck(count($details) === 3, 'Detail has exactly three unique votes after concurrent requests');
        foreach ($contexts as $name => $config) {
            $read = reactionHttp($config['endpoint'], $config['context']);
            mcpCheck($read['status'] === 200 && !empty($read['json']['canReact']), $name . ' access: ' . $read['body']);
            $states = array_column($read['json']['reactions'], null, 'id');
            mcpCheck($states[$config['ownMessageId']]['canReact'] === false && $states[$config['messageId']]['canReact'] === true,
                $name . ' distinguishes own messages without exposing author identity');
            foreach (['up', 'down', 'clap', ''] as $kind) {
                $own = reactionHttp($config['endpoint'], $config['context'], ['action' => 'reaction',
                    'message_id' => $config['ownMessageId'], 'reaction' => $kind, 'isOwn' => '0', 'IDuser' => $authorId]);
                mcpCheck($own['status'] === 403 && !empty($own['json']['message']), $name . ' rejects self-reactions including anonymous messages');
            }
            $post = ['action' => 'reaction', 'message_id' => $config['messageId'], 'reaction' => 'up'];
            $saved = reactionHttp($config['endpoint'], $config['context'], $post);
            mcpCheck($saved['status'] === 200 && $saved['json']['reactions'][0]['own'] === 'up', $name . ' reaction saved: ' . $saved['body']);
            $poll = reactionHttp($config['endpoint'], $config['context'] + ['after_id' => $config['messageId'], 'reaction_ids' => (string)$config['messageId']]);
            mcpCheck($poll['status'] === 200 && $poll['json']['reactions'][0]['own'] === 'up', $name . ' polling updates existing message');
            $denied = reactionHttp($config['endpoint'], $config['context'], $post, false);
            mcpCheck($denied['status'] === 403, $name . ' rejects missing CSRF');
            $forged = reactionHttp($config['endpoint'], $config['context'], array_replace($post, ['message_id' => $contexts[$name === 'pv' ? 'project' : 'pv']['messageId']]));
            mcpCheck($forged['status'] === 422, $name . ' rejects another thread');
        }
        $items['linked_participant_message'] = ChatMessage::createUserMessage($items['decision_thread'], 0,
            'Message via participant', true, false, (int)$items['participant']->getId());
        $items['guest_participant_message'] = ChatMessage::createUserMessage($items['decision_thread'], 0,
            'Message invite anonyme', true, false, (int)$items['guest_participant']->getId());
        $items['guest_message'] = ChatMessage::createUserMessage($items['pv_thread'], 0,
            'Message via lien PV', false, false, 0, (int)$items['guest_link']->getId());
        foreach ([
            [$items['decision_thread'], $items['linked_participant_message'], $actor],
            [$items['decision_thread'], $items['guest_participant_message'], ChatMessageReaction::actorKey(0, (int)$items['guest_participant']->getId())],
            [$items['pv_thread'], $items['guest_message'], ChatMessageReaction::actorKey(0, 0, (int)$items['second_guest_link']->getId())],
        ] as [$ownThread, $ownMessage, $ownActor]) {
            $rejected = false;
            try { ChatMessageReaction::setForMessage($ownThread, (int)$ownMessage->getId(), $ownActor, 'up'); }
            catch (DomainException $error) { $rejected = true; }
            mcpCheck($rejected, 'Model rejects self-reactions through participant/account/invitation aliases');
            mcpCheck(ChatMessageReaction::forMessages($ownThread, [(int)$ownMessage->getId()], $ownActor)[0]['canReact'] === false,
                'Snapshot agrees with server ownership checks');
        }
        $accountSession = $_SESSION;
        $_SESSION = [];
        $guestConfig = $contexts['pv'];
        $guestContext = $guestConfig['context'] + ['pv_token' => $items['guest_link']->get('token')];
        $guestPost = ['action' => 'reaction', 'message_id' => $guestConfig['messageId'], 'reaction' => 'clap', 'IDuser' => $uid];
        $guest = reactionHttp($guestConfig['endpoint'], $guestContext, $guestPost);
        mcpCheck($guest['status'] === 200 && $guest['json']['reactions'][0]['counts'] === ['up' => 1, 'down' => 0, 'clap' => 1],
            'Guest PV invitation has its own server-resolved identity: ' . $guest['body']);
        $guestContext['pv_token'] = $items['second_guest_link']->get('token');
        $repeat = reactionHttp($guestConfig['endpoint'], $guestContext, $guestPost);
        mcpCheck($repeat['status'] === 200 && $repeat['json'] === $guest['json'], 'Another invitation to the same email does not add a vote');
        $guestNoCsrf = reactionHttp($guestConfig['endpoint'], $guestContext, $guestPost, false);
        mcpCheck($guestNoCsrf['status'] === 403, 'Guest reaction also requires CSRF');
        $guestOwn = reactionHttp($guestConfig['endpoint'], $guestContext,
            array_replace($guestPost, ['message_id' => (int)$items['guest_message']->getId()]));
        mcpCheck($guestOwn['status'] === 403, 'Reissued PV invitation cannot react to its own message');
        $_SESSION = $accountSession;
        $items['project']->set('active', 0); $items['project']->save();
        $denied = reactionHttp($contexts['project']['endpoint'], $contexts['project']['context'], ['action' => 'reaction', 'message_id' => $mid, 'reaction' => 'up']);
        mcpCheck($denied['status'] === 403, 'Archived project reactions are read-only');
        echo "chat_reactions_test: OK (identities, retries, changes, removal, concurrency, three HTTP endpoints, CSRF, thread isolation, read-only)\n";
    }
} finally {
    mcpCleanup($items);
    $_SESSION = $before;
}
