<?php
declare(strict_types=1);
// Run with the web server user so both processes can read the fixture session.
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{Document, DocumentPvPoint, DocumentShareLink, DocumentInvitation, User, UserOrganization};

function participationEditorRequest(string $path, array $query, ?array $post = null): array
{
    if ($post !== null) $post['_csrf'] = commonCsrfToken();
    $cookie = session_name() . '=' . session_id();
    session_write_close();
    $curl = curl_init('https://localhost' . $path . '?' . http_build_query($query));
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Cookie: ' . $cookie]]);
    if ($post !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    session_start();
    mcpCheck(is_string($body), 'Participation HTTP request failed.');
    return ['status' => $status, 'body' => $body];
}
function participationEditorRoot(string $body): array
{
    $html = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $html->loadHTML($body);
    libxml_clear_errors(); libxml_use_internal_errors($previous);
    $xpath = new DOMXPath($html);
    $root = $xpath->query('//*[@data-omo-pv-editor-root]')->item(0);
    mcpCheck($root instanceof DOMElement, 'Participation page must render the existing PV editor.');
    $group = $xpath->query('//*[@data-omo-pv-editor-add-group]')->item(0);
    $sort = $xpath->query('//*[@data-omo-pv-sort-menu]')->item(0);
    $handover = $xpath->query('//*[@data-omo-pv-claim-secretary]')->item(0);
    return ['token' => $root->getAttribute('data-omo-pv-editor-token'), 'user' => $root->getAttribute('data-omo-pv-editor-user-id'),
        'groups' => $group instanceof DOMElement && !$group->hasAttribute('hidden'),
        'sort' => $sort instanceof DOMElement && !$sort->hasAttribute('hidden'),
        'handover' => $handover instanceof DOMElement && !$handover->hasAttribute('hidden') ? $handover->getAttribute('data-omo-pv-secretary-action') : ''];
}
$before = $_SESSION;
$items = mcpFixtures();
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId();
    $items['pv'] = mcpFixture(Document::class, ['title' => 'Participation editor fixture', 'documenttype' => Document::TYPE_PV,
        'pvstage' => Document::PV_STAGE_PREPARATION, 'IDorganization' => $oid, 'IDuser' => $uid, 'IDusercreation' => $uid,
        'IDuser_pv_editor' => $uid, 'IDuser_pv_official_editor' => $uid, 'active' => 1]);
    $id = (int)$items['pv']->getId();
    $items['link'] = mcpFixture(DocumentShareLink::class, ['IDorganization' => $oid, 'IDdocument' => $id, 'IDuser' => $uid,
        'recipient_user_id' => $uid, 'recipient_email' => $items['user']->get('email'), 'allow_pv_contribution' => 1,
        'token' => DocumentShareLink::generateUniqueToken(), 'active' => 1]);
    $token = $items['link']->get('token');
    $_SESSION = ['currentOrganization' => $oid];
    $response = participationEditorRequest('/omo/pv_participation.php', ['token' => $token]);
    $browserFixture = ['html' => $response['body'], 'responses' => []];
    mcpCheck($response['status'] === 200, 'Invitation link opens without an account.');
    $guest = participationEditorRoot($response['body']);
    mcpCheck($guest['sort'] && $guest['groups'] && $guest['handover'] === 'pass_pv_editor', 'The editor link exposes agenda management and handover without login.');
    $post = ['document_id' => $id, 'oid' => $oid, 'editor_token' => $guest['token'], 'action' => 'add_group'];
    $api = '/omo/api/documents/pv/action.php';
    $response = participationEditorRequest($api, ['pv_token' => $token], $post);
    $result = json_decode($response['body'], true);
    $browserFixture['responses']['add_group'] = $result;
    mcpCheck($response['status'] === 200 && !empty($result['status']) && !empty($result['point']['canEditGroup']), 'Editor token creates an editable group.');
    $items['public_group'] = new DocumentPvPoint();
    mcpCheck($items['public_group']->load((int)$result['point']['id']), 'Public group saved.');
    $groupId = (int)$items['public_group']->getId();
    $rename = $post + ['point_id' => $groupId, 'title' => 'Renamed group']; $rename['action'] = 'update_group';
    $response = participationEditorRequest($api, ['pv_token' => $token], $rename);
    $browserFixture['responses']['update_group'] = json_decode($response['body'], true);
    mcpCheck($response['status'] === 200 && !empty(json_decode($response['body'], true)['status']), 'Editor token renames a group.');
    $items['public_group']->load($groupId, true);
    mcpCheck($items['public_group']->get('title') === 'Renamed group' && (int)$items['public_group']->get('IDuser_modification') === $uid, 'Group rename is attributed to the link recipient.');
    $items['point'] = mcpFixture(DocumentPvPoint::class, ['IDdocument' => $id, 'title' => 'Point fixture', 'pointtype' => 'information', 'is_confidential' => 1, 'active' => 1]);
    $reorder = $post; $reorder['action'] = 'reorder_points';
    $reorder['layout'] = json_encode([['id' => $groupId, 'parentId' => 0], ['id' => $items['point']->getId(), 'parentId' => $groupId]]);
    $response = participationEditorRequest($api, ['pv_token' => $token], $reorder);
    $result = json_decode($response['body'], true);
    mcpCheck($response['status'] === 200 && !empty($result['status']) && count($result['points']) === 2, 'Editor token reorders a complete agenda, including visible confidential points.');
    $items['point']->load((int)$items['point']->getId(), true);
    mcpCheck((int)$items['point']->get('IDparent') === $groupId, 'Point moves into the selected group.');
    $sortPost = $post; $sortPost['action'] = 'sort_points'; $sortPost['sort_mode'] = 'priority';
    $response = participationEditorRequest($api, ['pv_token' => $token], $sortPost);
    $browserFixture['responses']['sort_points'] = json_decode($response['body'], true);
    mcpCheck($response['status'] === 200 && !empty(json_decode($response['body'], true)['status']), 'Editor token sorts the agenda.');
    $poll = $post; $poll['action'] = 'poll_updates';
    $result = json_decode(participationEditorRequest($api, ['pv_token' => $token], $poll)['body'], true);
    $browserFixture['responses']['poll_updates'] = $result;
    mcpCheck(!empty($result['document']['canSortPvAgenda']) && !empty($result['document']['canCreatePvGroups']) && !empty($result['document']['canPassPvEditor']), 'Polling preserves token agenda and handover controls.');
    mcpCheck(empty($result['document']['canManagePvStructure']), 'Agenda access does not expose unrelated account management controls.');
    $bad = $post; $bad['editor_token'] = 'invalid';
    mcpCheck(participationEditorRequest($api, ['pv_token' => $token], $bad)['status'] === 403, 'Agenda writes require the editor session token.');
    $bad = $post; $bad['action'] = 'update_document_metadata';
    mcpCheck(participationEditorRequest($api, ['pv_token' => $token], $bad)['status'] === 403, 'Token access stays limited to the authorized meeting operations.');
    $delete = $post; $delete['action'] = 'delete_point'; $delete['point_id'] = $groupId;
    mcpCheck(participationEditorRequest($api, ['pv_token' => $token], $delete)['status'] === 200, 'Editor token deletes a group.');
    $pass = $post; $pass['action'] = 'pass_pv_editor';
    $result = json_decode(participationEditorRequest($api, ['pv_token' => $token], $pass)['body'], true);
    $browserFixture['responses']['pass_pv_editor'] = $result;
    mcpCheck(!empty($result['status']) && !empty($result['document']['pvEditorHandoverOpen']), 'Editor token can pass the hand.');
    $claim = $post; $claim['action'] = 'claim_pv_editor';
    $result = json_decode(participationEditorRequest($api, ['pv_token' => $token], $claim)['body'], true);
    $browserFixture['responses']['claim_pv_editor'] = $result;
    mcpCheck(!empty($result['status']) && empty($result['document']['pvEditorHandoverOpen']), 'Official editor token can reclaim the hand.');
    $items['user']->load($uid);
    $_SESSION['currentUser'] = $uid;
    $_SESSION['auth_security_version'] = (int)$items['user']->get('security_version');
    $response = participationEditorRequest('/omo/pv_participation.php', ['token' => $token]);
    mcpCheck($response['status'] === 200, 'Authenticated editor opens the same public page.');
    $editor = participationEditorRoot($response['body']);
    mcpCheck($editor['sort'] && $editor['groups'], 'Existing sort and folder controls return for the authenticated editor.');
    mcpCheck(isset($_SESSION['omo_pv_editor_tokens'][$oid . ':' . $id . ':user:' . $uid]), 'Editor uses the existing account lock/session token.');
    $post['editor_token'] = $editor['token'];
    $response = participationEditorRequest('/omo/api/documents/pv/action.php', ['pv_token' => $token], $post);
    $result = json_decode($response['body'], true);
    mcpCheck($response['status'] === 200 && !empty($result['status']), 'Existing add_group action works through the participation page.');
    $items['group'] = new DocumentPvPoint();
    mcpCheck($items['group']->load((int)$result['point']['id']) && $items['group']->isGroup(), 'Created folder is stored by the normal dbObject flow.');
    $post['action'] = 'sort_points'; $post['sort_mode'] = 'priority';
    $response = participationEditorRequest('/omo/api/documents/pv/action.php', ['pv_token' => $token], $post);
    mcpCheck($response['status'] === 200 && !empty(json_decode($response['body'], true)['status']), 'Existing sort action works through the participation page.');
    $items['other_user'] = mcpFixture(User::class, ['firstname' => 'Unrelated user', 'email' => 'pv-other-' . bin2hex(random_bytes(5)) . '@example.invalid', 'active' => 1]);
    $items['other_user']->load((int)$items['other_user']->getId());
    $items['guest_link'] = mcpFixture(DocumentShareLink::class, ['IDorganization' => $oid, 'IDdocument' => $id, 'IDuser' => $uid,
        'recipient_email' => 'guest@example.invalid', 'allow_pv_contribution' => 1, 'token' => DocumentShareLink::generateUniqueToken(), 'active' => 1]);
    $guestToken = $items['guest_link']->get('token');
    $_SESSION['currentUser'] = $items['other_user']->getId();
    $_SESSION['auth_security_version'] = (int)$items['other_user']->get('security_version');
    $other = participationEditorRoot(participationEditorRequest('/omo/pv_participation.php', ['token' => $guestToken])['body']);
    mcpCheck(!$other['sort'] && !$other['groups'] && $other['handover'] === '', 'An ordinary guest invitation does not grant editor tools.');
    $post['editor_token'] = $other['token'];
    mcpCheck(participationEditorRequest('/omo/api/documents/pv/action.php', ['pv_token' => $guestToken], $post)['status'] === 403, 'An ordinary guest invitation cannot sort.');
    $_SESSION = ['currentOrganization' => $oid];
    $items['other_membership'] = mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $items['other_user']->getId(), 'active' => 1]);
    $items['other_invitation'] = mcpFixture(DocumentInvitation::class, ['resource_type' => 'document', 'resource_id' => $id,
        'invitation_type' => 'user', 'IDuser' => $items['other_user']->getId(), 'status' => 'invited', 'active' => 1]);
    $items['other_link'] = mcpFixture(DocumentShareLink::class, ['IDorganization' => $oid, 'IDdocument' => $id, 'IDuser' => $uid,
        'recipient_user_id' => $items['other_user']->getId(), 'recipient_email' => $items['other_user']->get('email'), 'allow_pv_contribution' => 1,
        'token' => DocumentShareLink::generateUniqueToken(), 'active' => 1]);
    $otherToken = $items['other_link']->get('token');
    participationEditorRequest($api, ['pv_token' => $token], $pass);
    $replacement = participationEditorRoot(participationEditorRequest('/omo/pv_participation.php', ['token' => $otherToken])['body']);
    mcpCheck(in_array($replacement['handover'], ['claim_pv_editor', 'replace_pv_editor'], true), 'An invited member sees the existing takeover command allowed by their rights.');
    $replace = $post; $replace['action'] = $replacement['handover']; $replace['editor_token'] = $replacement['token'];
    $result = json_decode(participationEditorRequest($api, ['pv_token' => $otherToken], $replace)['body'], true);
    mcpCheck(!empty($result['status']) && (int)$result['document']['pvEditorUserId'] === (int)$items['other_user']->getId(), 'Invited member token can take the offered hand.');
    $official = participationEditorRoot(participationEditorRequest('/omo/pv_participation.php', ['token' => $token])['body']);
    mcpCheck($official['handover'] === 'claim_pv_editor', 'Official editor can reclaim the hand through their token.');
    $browserFixture['responses']['poll_after_replacement'] = json_decode(participationEditorRequest($api, ['pv_token' => $token], $poll)['body'], true);
    $claim['editor_token'] = $official['token'];
    $result = json_decode(participationEditorRequest($api, ['pv_token' => $token], $claim)['body'], true);
    mcpCheck(!empty($result['status']) && (int)$result['document']['pvEditorUserId'] === $uid, 'Official editor token restores the official editor.');
    $_SESSION['currentUser'] = $uid; $_SESSION['auth_security_version'] = (int)$items['user']->get('security_version');
    $items['membership']->set('active', 0); $items['membership']->save();
    $inactive = participationEditorRoot(participationEditorRequest('/omo/pv_participation.php', ['token' => $token])['body']);
    mcpCheck(!$inactive['sort'] && !$inactive['groups'], 'An inactive organization membership cannot restore management tools.');
    $_SESSION = $before;
    mcpCleanup($items);
    $items = [];
    echo in_array('--render', $argv, true) ? json_encode($browserFixture, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : "pv_participation_editor_permissions_test: OK\n";
} finally {
    mcpCleanup($items);
    $_SESSION = $before;
}
