<?php
declare(strict_types=1);
// Run in Docker as www-data so Apache can read the temporary session.
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/etherpad.php';

use dbObject\{Document, User, UserOrganization, Event, EventInvitation, Permission, HolonPermission, UserHolon, ArrayDocument};

function padAccessHttp(string $path, ?array $post = null): array
{
    if ($post !== null) $post['_csrf'] = commonCsrfToken();
    $cookie = session_name() . '=' . session_id();
    session_write_close();
    $headers = [];
    $curl = curl_init('https://localhost' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Cookie: ' . $cookie],
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$headers): int {
            $parts = explode(':', trim($line), 2);
            if (count($parts) === 2) $headers[strtolower($parts[0])][] = trim($parts[1]);
            return strlen($line);
        }]);
    if ($post !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    session_start();
    mcpCheck(is_string($body), 'PAD HTTP request failed');
    return ['status' => $status, 'body' => $body, 'headers' => $headers];
}

$before = $_SESSION;
$items = mcpFixtures();
$padId = '';
try {
    $oid = (int)$items['org']->getId();
    $uid = (int)$items['user']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid,
        'auth_security_version' => (int)$items['user']->get('security_version')];
    $items['owner'] = mcpFixture(User::class, ['email' => 'pad-owner-' . bin2hex(random_bytes(6)) . '@example.invalid', 'active' => 1]);
    $items['owner_membership'] = mcpFixture(UserOrganization::class,
        ['IDuser' => $items['owner']->getId(), 'IDorganization' => $oid, 'active' => 1]);
    $marker = 'Private PAD content ' . bin2hex(random_bytes(6));
    $pad = omoEtherpadCreateDocumentPad($items['org'], $oid, (int)$items['owner']->getId(), 'PAD test owner', $marker);
    mcpCheck(!empty($pad['status']), 'Create private Etherpad fixture: ' . ($pad['text'] ?? 'unknown error'));
    $padId = $pad['padId'];
    $items['event'] = mcpFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $items['owner']->getId(),
        'title' => 'PAD visibility HTTP event', 'active' => 1, 'status' => Event::STATUS_CONFIRMED,
        'start_at' => new DateTimeImmutable('+1 day'), 'end_at' => new DateTimeImmutable('+1 day +1 hour')]);
    $items['invitation'] = mcpFixture(EventInvitation::class, ['IDevent' => $items['event']->getId(),
        'IDuser' => $items['owner']->getId(), 'invitation_type' => EventInvitation::TYPE_USER,
        'status' => EventInvitation::STATUS_INVITED, 'active' => 1]);
    $items['document'] = $document = mcpFixture(Document::class, ['IDorganization' => $oid,
        'IDuser' => $items['owner']->getId(), 'title' => 'Private PAD HTTP regression',
        'IDevent' => $items['event']->getId(),
        'documenttype' => Document::TYPE_ETHERPAD, 'etherpadpadid' => $padId, 'active' => 1]);
    mcpCheck(!empty($document->saveVisibilityRule('organization')['status']), 'Save reading rule');
    mcpCheck(!empty($document->saveEditVisibilityRule('self')['status']), 'Save editing rule');
    $path = '/omo/api/documents/etherpad/open.php?id=' . $document->getId();
    $eventPath = '/omo/api/calendar/detail.php?oid=' . $oid . '&id=' . $items['event']->getId();
    $response = padAccessHttp($eventPath);
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'Private PAD HTTP regression'), 'Accessible PAD appears on the event sheet');
    $response = padAccessHttp($path);
    mcpCheck($response['status'] === 200 && str_contains($response['body'], $marker), 'Reader opens the private PAD');
    mcpCheck(!isset($response['headers']['location']) && !str_contains($response['body'], $padId), 'Reader receives no Etherpad credentials or internal ID');
    foreach ($response['headers']['set-cookie'] ?? [] as $cookie) {
        mcpCheck(!str_starts_with($cookie, 'sessionID='), 'Reading must never issue an editing session');
    }
    $sessions = omoEtherpadApiRequest($items['org'], 'listSessionsOfGroup', ['groupID' => $pad['groupId']]);
    mcpCheck(!empty($sessions['status']) && empty($sessions['data']), 'Reading creates no group session');
    $response = padAccessHttp($path . '&content=1');
    $json = json_decode($response['body'], true);
    mcpCheck($response['status'] === 200 && !empty($json['status']) && str_contains($json['html'], $marker), 'Automatic read-only refresh');
    omoEtherpadApiRequest($items['org'], 'setText', ['padID' => $padId, 'text' => $marker . ' updated']);
    $response = padAccessHttp($path . '&content=1');
    mcpCheck(str_contains($response['body'], $marker . ' updated'), 'Refresh reads current PAD contents');
    // Renaming through the real form endpoint must keep the same PAD and scopes,
    // including composed accents and combining marks received from keyboards.
    $items['owner_edit_permission'] = mcpFixture(HolonPermission::class, [
        'IDholon' => $items['role']->getId(), 'IDpermission' => Permission::findByKey('CAN_EDIT_DOCUMENT')->getId(),
        'range' => 'organization', 'member_type' => 'member']);
    $items['owner_assignment'] = mcpFixture(UserHolon::class, ['IDholon' => $items['role']->getId(),
        'IDuser' => $items['owner']->getId(), 'active' => 1, 'is_membership' => 1]);
    foreach (['Reunion ete', "R\u{00e9}union \u{00e9}t\u{00e9} - d\u{00e9}cisions", "Re\u{0301}union e\u{0301}te\u{0301}"] as $title) {
        $_SESSION['currentUser'] = (int)$items['owner']->getId();
        $_SESSION['auth_security_version'] = (int)$items['owner']->get('security_version');
        $response = padAccessHttp('/omo/api/documents/save.php', ['oid' => $oid, 'id' => $document->getId(), 'title' => $title]);
        $saved = json_decode($response['body'], true);
        mcpCheck($response['status'] === 200 && !empty($saved['status']), 'Rename PAD through the form endpoint');
        $document->load($document->getId(), true);
        mcpCheck($document->get('title') === $title && $document->getEtherpadPadId() === $padId, 'UTF-8 title round trip keeps the PAD identifier');
        mcpCheck($document->getPrimaryVisibilityRuleRow()['visibility_type'] === 'organization'
            && $document->getPrimaryEditVisibilityRuleRow()['visibility_type'] === 'self', 'Renaming preserves read and edit scopes');
        mcpCheck(padAccessHttp($path)['status'] === 302, 'Creator opens the renamed PAD in editing mode');
        $_SESSION['currentUser'] = $uid;
        $_SESSION['auth_security_version'] = (int)$items['user']->get('security_version');
        $response = padAccessHttp($path);
        mcpCheck($response['status'] === 200 && str_contains($response['body'], $marker), 'Member opens the renamed PAD in read-only mode');
        $response = padAccessHttp($eventPath);
        mcpCheck($response['status'] === 200 && str_contains($response['body'], htmlspecialchars($title, ENT_QUOTES, 'UTF-8')), 'Event sheet displays the UTF-8 title');
        $list = new ArrayDocument();
        $list->loadVisibleForOrganization($oid);
        mcpCheck(in_array((int)$document->getId(), array_map(static fn($doc) => (int)$doc->getId(), $list->getArrayCopy()), true), 'Member sees the renamed PAD in the document list');
    }
    // Restore the baseline title for the following visibility assertions.
    $document->set('title', 'Private PAD HTTP regression');
    mcpCheck(!empty($document->save()['status']), 'Restore baseline title');
    // An editor still receives the real Etherpad session and collaborative URL.
    $_SESSION['currentUser'] = (int)$items['owner']->getId();
    $_SESSION['auth_security_version'] = (int)$items['owner']->get('security_version');
    $response = padAccessHttp($path);
    mcpCheck($response['status'] === 302 && ($response['headers']['location'][0] ?? '') === omoEtherpadBuildPadUrl($items['org'], $padId), 'Owner opens the editable PAD');
    $editingCookie = '';
    foreach ($response['headers']['set-cookie'] ?? [] as $cookie) {
        if (preg_match('/^sessionID=([^;]+)/', $cookie, $matches)) $editingCookie = urldecode($matches[1]);
    }
    mcpCheck($editingCookie !== '', 'Editor receives an Etherpad session cookie');
    $info = omoEtherpadApiRequest($items['org'], 'getSessionInfo', ['sessionID' => $editingCookie]);
    mcpCheck(!empty($info['status']) && $info['data']['groupID'] === $pad['groupId'], 'Editing session belongs to this PAD group');
    $_SESSION['currentUser'] = $uid;
    $_SESSION['auth_security_version'] = (int)$items['user']->get('security_version');
    $document->saveEditVisibilityRule('organization');
    $response = padAccessHttp($path);
    mcpCheck($response['status'] === 302, 'Organization editing scope opens the collaborative PAD');
    $document->saveVisibilityRule('self');
    $response = padAccessHttp($eventPath);
    mcpCheck($response['status'] === 200 && !str_contains($response['body'], 'Private PAD HTTP regression'), 'Inaccessible PAD disappears from the event sheet');
    foreach ([$path, $path . '&content=1'] as $deniedPath) {
        $response = padAccessHttp($deniedPath);
        mcpCheck($response['status'] === 403 && !str_contains($response['body'], $marker), 'Revoked reading blocks initial opening and refresh');
    }
    $response = padAccessHttp('/omo/api/documents/detail.php?oid=' . $oid . '&id=' . $document->getId());
    mcpCheck($response['status'] === 404, 'Document detail uses the same private read scope');
    $document->saveVisibilityRule('organization');
    $document->set('active', 0); $document->save();
    mcpCheck(padAccessHttp($path)['status'] === 403, 'Archived PAD cannot be opened');
    echo "etherpad_access_http_test: OK\n";
} finally {
    // The document deletes its own temporary Etherpad group through the normal API.
    if (!isset($items['document']) && $padId !== '') omoEtherpadDeleteDocumentPad($items['org'], $padId);
    mcpCleanup($items);
    $_SESSION = $before;
}
