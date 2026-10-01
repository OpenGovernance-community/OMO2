<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/etherpad.php';

$organization = new \dbObject\Organization();
if (!$organization->load((int)($argv[1] ?? 0)) || !omoEtherpadHasConfig($organization)) {
    throw new RuntimeException('Usage: php tests/etherpad_sessions_test.php ORGANIZATION_ID (with Etherpad configured)');
}
function sessionExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function sessionApi(string $method, array $parameters = [])
{
    global $organization;
    $result = omoEtherpadApiRequest($organization, $method, $parameters);
    sessionExpect(!empty($result['status']), 'Etherpad API failed: ' . $method);
    return $result['data'] ?? null;
}

$groups = [];
try {
    $groups[] = $groupId = sessionApi('createGroup')['groupID'];
    $groups[] = $otherGroupId = sessionApi('createGroup')['groupID'];
    $memberName = 'OMO session test member';
    $guestName = 'OMO session test guest (guest@example.invalid)';
    $memberId = sessionApi('createAuthor', ['name' => $memberName])['authorID'];
    $guestId = sessionApi('createAuthor', ['name' => $guestName])['authorID'];
    $otherSession = omoEtherpadGetOrCreateSession($organization, $otherGroupId, $memberId);
    sessionExpect(!empty($otherSession['status']), 'Other pad session could not be created');
    $cookie = $otherSession['sessionId'];
    $createdSessions = [];
    foreach ([$guestId, $memberId, $guestId, $memberId] as $authorId) {
        $session = omoEtherpadGetOrCreateSession($organization, $groupId, $authorId);
        sessionExpect(!empty($session['status']), 'Current identity session could not be created');
        if (isset($createdSessions[$authorId])) {
            sessionExpect($session['sessionId'] === $createdSessions[$authorId], 'Existing identity session should be reused');
        }
        $createdSessions[$authorId] = $session['sessionId'];
        $cookie = omoEtherpadBuildSessionCookieValue($session['sessionId'], $cookie, $session['groupSessionIds']);
        $matchingAuthors = [];
        foreach (explode(',', $cookie) as $cookieSession) {
            $info = sessionApi('getSessionInfo', ['sessionID' => $cookieSession]);
            if ($info['groupID'] === $groupId) { $matchingAuthors[] = $info['authorID']; }
        }
        sessionExpect($matchingAuthors === [$authorId], 'A pad must receive only the identity selected for this opening');
        sessionExpect(in_array($otherSession['sessionId'], explode(',', $cookie), true), 'Other pads must keep their sessions');
    }
    foreach ($createdSessions as $authorId => $sessionId) {
        sessionExpect(sessionApi('getSessionInfo', ['sessionID' => $sessionId])['authorID'] === $authorId, 'Other browsers must not have their sessions revoked');
    }
    sessionExpect(sessionApi('getAuthorName', ['authorID' => $memberId]) === $memberName, 'Member must keep name alone');
    sessionExpect(sessionApi('getAuthorName', ['authorID' => $guestId]) === $guestName, 'Guest must keep confirmed email');
    sessionExpect(omoEtherpadBuildSessionCookieValue('s.current', 's.old', null) === 's.current', 'Unknown competing sessions must be discarded');
    sessionExpect(omoEtherpadBuildSessionCookieValue('s.current', 'invalid,s.other,s.old,s.other', ['s.old']) === 's.other,s.current', 'Cookie must be filtered and deduplicated');
    sessionExpect(omoEtherpadBuildSessionCookieValue('invalid', $cookie, []) === '', 'Invalid current session must fail closed');
    $many = implode(',', array_map(static fn($i) => 's.previous' . $i, range(1, 25)));
    $limited = explode(',', omoEtherpadBuildSessionCookieValue('s.current', $many, []));
    sessionExpect(count($limited) === 20 && end($limited) === 's.current', 'Cookie limit must retain the selected identity');
    echo "Etherpad sessions: guest/member switching, session reuse, other pads and identity labels OK.\n";
} finally {
    foreach ($groups as $groupId) { sessionApi('deleteGroup', ['groupID' => $groupId]); }
}
