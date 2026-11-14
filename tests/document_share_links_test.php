<?php
declare(strict_types=1);
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/document/share.php';
require_once dirname(__DIR__) . '/common/etherpad.php';
require_once dirname(__DIR__) . '/common/ethercalc.php';
require_once dirname(__DIR__) . '/common/spacedeck.php';
use dbObject\{Document, DocumentShareLink, DocumentPvPoint, User, UserOrganization};

function shareHttp(string $url, bool $owner = false, ?array $post = null): array
{
    global $guestJar;
    if ($post !== null && $owner) $post['_csrf'] = commonCsrfToken();
    $cookie = session_name() . '=' . session_id();
    session_write_close();
    $headers = [];
    $curl = curl_init('https://localhost' . $url);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => array_filter(['Host: localtest.me', $owner ? 'Cookie: ' . $cookie : null]),
        CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$headers): int {
            $parts = explode(':', trim($line), 2);
            if (count($parts) === 2) $headers[strtolower($parts[0])][] = trim($parts[1]);
            return strlen($line);
        }]);
    if (!$owner) curl_setopt_array($curl, [CURLOPT_COOKIEFILE => $guestJar, CURLOPT_COOKIEJAR => $guestJar]);
    if ($post !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    unset($curl);
    session_start();
    mcpCheck(is_string($body), 'Shared document HTTP response');
    return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true), 'headers' => $headers];
}

$before = $_SESSION;
$items = mcpFixtures();
$guestJar = tempnam(sys_get_temp_dir(), 'share-cookies-');
$padId = ''; $roomId = ''; $spaceId = '';
try {
    $uid = (int)$items['user']->getId(); $oid = (int)$items['org']->getId();
    mcpEnableDocumentCreation($items);
    $editPermission = new \dbObject\Permission(); $editPermission->load(['permission_key', 'CAN_EDIT_DOCUMENT']);
    $items['edit_permission'] = mcpFixture(\dbObject\HolonPermission::class, ['IDholon' => $items['role']->getId(),
        'IDpermission' => $editPermission->getId(), 'range' => 'organization', 'member_type' => 'member']);
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $fields = ['IDorganization' => $oid, 'IDuser' => $uid, 'active' => 1];
    foreach ([Document::TYPE_HTML, Document::TYPE_UPLOADED_FILE, Document::TYPE_PV,
        Document::TYPE_ETHERPAD, Document::TYPE_ETHERCALC, Document::TYPE_WHITEBOARD] as $type) {
        $doc = $items['doc_' . $type] = mcpFixture(Document::class, $fields + ['documenttype' => $type,
            'title' => 'Shared ' . $type, 'content' => '<p>SHARED_SAVED_CONTENT</p>',
            'storedfilepath' => '/fixture.zip', 'storedfilename' => 'fixture.zip', 'storedfilemime' => 'application/zip']);
        $doc->saveVisibilityRule('self');
        $result = shareHttp('/omo/api/documents/share_create.php', true, ['id' => $doc->getId(), 'label' => 'External test', 'allow_edit' => 1]);
        mcpCheck($result['status'] === 200 && !empty($result['json']['status']), 'Create external link for ' . $type . ': ' . $result['body']);
        parse_str((string)parse_url($result['json']['shareUrl'], PHP_URL_QUERY), $query);
        $link = $items['link_' . $type] = DocumentShareLink::findByToken($query['token']);
        mcpCheck(strlen($link->get('token')) === 64, 'Long cryptographic share token');
        mcpCheck((bool)$link->get('allow_edit') === $doc->supportsSharedEditing(), 'Editing capability enforced for ' . $type);
        $page = shareHttp($link->buildShareUrl());
        mcpCheck($page['status'] === 200 && str_contains($page['body'], 'common-topbar'), 'Shared reader for ' . $type);
        $popup = shareHttp('/omo/api/documents/share_popup.php?id=' . $doc->getId(), true);
        mcpCheck(str_contains($popup['body'], 'omoDocumentSharePopupForm'), 'Share form available for ' . $type);
        mcpCheck(str_contains($popup['body'], 'id="omoDocumentSharePopupAllowEdit"') === $doc->supportsSharedEditing(), 'Editing option shown only when supported');
        if ($type === Document::TYPE_ETHERPAD) file_put_contents(dirname(__DIR__) . '/tmp/document-share-popup.html', $popup['body']);
    }
    $doc = $items['doc_' . Document::TYPE_HTML]; $link = $items['link_' . Document::TYPE_HTML];
    mcpCheck(!str_contains(shareHttp($doc->buildReaderUrl())['body'], 'SHARED_SAVED_CONTENT'), 'Short link retains OMO rights');
    $result = shareHttp('/omo/api/documents/share_create.php', true, ['id' => $doc->getId(), 'label' => 'Second', 'password' => 'secret', 'dateexpiration' => (new DateTimeImmutable('+1 day'))->format('Y-m-d\TH:i')]);
    parse_str((string)parse_url($result['json']['shareUrl'], PHP_URL_QUERY), $query);
    $protected = $items['protected'] = DocumentShareLink::findByToken($query['token']);
    mcpCheck($protected->getId() !== $link->getId(), 'Independent links for one document');
    mcpCheck(!str_contains(shareHttp($protected->buildShareUrl())['body'], 'SHARED_SAVED_CONTENT'), 'Password protects content');
    $live = '/omo/api/documents/share_live.php?token=' . rawurlencode($protected->get('token'));
    mcpCheck(shareHttp($live)['status'] === 403, 'Password protects live endpoint');
    mcpCheck(shareHttp($protected->buildShareUrl(), false, ['share_password' => 'wrong'])['status'] === 200, 'Wrong password stays on gate');
    mcpCheck(shareHttp($protected->buildShareUrl(), false, ['share_password' => 'secret'])['status'] === 302, 'Password unlocks link');
    mcpCheck(str_contains(shareHttp($protected->buildShareUrl())['body'], 'SHARED_SAVED_CONTENT'), 'Approved guest reads private document');
    shareHttp('/omo/api/documents/share_create.php', true, ['id' => $doc->getId(), 'share_id' => $protected->getId(), 'password' => 'new-secret']);
    mcpCheck(shareHttp($live)['status'] === 403, 'Password change invalidates previous approval');
    $protected->load($protected->getId(), true);
    $protected->set('dateexpiration', new DateTimeImmutable('-1 second')); $protected->save();
    mcpCheck(shareHttp($protected->buildShareUrl())['status'] === 404, 'Expiration enforced');
    mcpCheck(str_contains(shareHttp($link->buildShareUrl())['body'], 'SHARED_SAVED_CONTENT'), 'Expiring one link preserves another');
    $doc->set('contentedition', '<p>SHARED_DRAFT_CONTENT</p>'); $doc->set('IDuseredition', $uid);
    $doc->set('datecontentedition', new DateTimeImmutable()); $doc->save();
    mcpCheck(!str_contains(shareHttp($link->buildShareUrl())['body'], 'SHARED_DRAFT_CONTENT'), 'Draft hidden by default');
    shareHttp('/omo/api/documents/share_create.php', true, ['id' => $doc->getId(), 'share_id' => $link->getId(), 'allow_live_follow' => 1]);
    mcpCheck(str_contains(shareHttp($link->buildShareUrl())['body'], 'SHARED_DRAFT_CONTENT'), 'Explicit live follow retained');

    $pv = $items['doc_' . Document::TYPE_PV]; $pvLink = $items['link_' . Document::TYPE_PV];
    $items['secret'] = mcpFixture(DocumentPvPoint::class, ['IDdocument' => $pv->getId(), 'title' => 'Secret', 'content' => 'SHARED_CONFIDENTIAL', 'is_confidential' => 1, 'active' => 1]);
    $pvLink->set('allow_edit', 1); $pvLink->save();
    mcpCheck(!$pvLink->allowsDocumentEditing($pv), 'PV never editable even with forged editing flag');
    mcpCheck(!str_contains(shareHttp($pvLink->buildShareUrl(), true)['body'], 'SHARED_CONFIDENTIAL'), 'Share does not inherit owner confidential PV access');

    $file = $items['doc_' . Document::TYPE_UPLOADED_FILE]; $fileLink = $items['link_' . Document::TYPE_UPLOADED_FILE];
    $download = '/omo/api/documents/upload/download.php?share_token=' . rawurlencode($fileLink->get('token')) . '&id=' . $file->getId();
    mcpCheck(shareHttp($download)['status'] === 503, 'Guest file download reaches storage check');
    mcpCheck(shareHttp($download . '&version_id=1')['status'] === 404, 'No version history via external links');
    mcpCheck(shareHttp(str_replace('&id=' . $file->getId(), '&id=' . $doc->getId(), $download))['status'] === 404, 'File grant bound to one document');
    shareHttp('/omo/api/documents/share_delete.php', true, ['id' => $file->getId(), 'share_id' => $fileLink->getId()]);
    mcpCheck(shareHttp($download)['status'] === 404, 'Revocation blocks downloads');

    $pad = omoEtherpadCreateDocumentPad($items['org'], $oid, $uid, 'Share test', 'SHARED_PAD_CONTENT');
    mcpCheck(!empty($pad['status']), 'Create pad'); $padId = $pad['padId'];
    $padDoc = $items['doc_' . Document::TYPE_ETHERPAD]; $padLink = $items['link_' . Document::TYPE_ETHERPAD];
    $padDoc->set('etherpadpadid', $padId); $padDoc->save();
    $padOpen = '/omo/api/documents/etherpad/open.php?id=' . $padDoc->getId() . '&share_token=' . rawurlencode($padLink->get('token'));
    mcpCheck(shareHttp($padOpen)['status'] === 302, 'Guest editable Etherpad opens');
    $padLink->load($padLink->getId(), true);
    $author = omoEtherpadApiRequest($items['org'], 'createAuthorIfNotExistsFor', ['authorMapper' => 'omo-share-' . $padLink->getId() . '-' . $padLink->getAccessFingerprint()]);
    $authorId = $author['data']['authorID'];
    $sessions = omoEtherpadApiRequest($items['org'], 'listSessionsOfAuthor', ['authorID' => $authorId]);
    mcpCheck(count($sessions['data'] ?? []) > 0, 'Share creates a native Etherpad session');
    shareHttp('/omo/api/documents/share_create.php', true, ['id' => $padDoc->getId(), 'share_id' => $padLink->getId()]);
    $sessions = omoEtherpadApiRequest($items['org'], 'listSessionsOfAuthor', ['authorID' => $authorId]);
    mcpCheck(empty($sessions['data']), 'Disabling editing deletes existing native Etherpad sessions');
    $readonly = shareHttp($padOpen);
    mcpCheck($readonly['status'] === 200 && str_contains($readonly['body'], 'SHARED_PAD_CONTENT'), 'Read-only pad served without native editing credentials');

    $sheet = omoEthercalcCreateDocumentSheet($oid); mcpCheck(!empty($sheet['status']), 'Create sheet'); $roomId = $sheet['roomId'];
    $sheetDoc = $items['doc_' . Document::TYPE_ETHERCALC]; $sheetLink = $items['link_' . Document::TYPE_ETHERCALC];
    $sheetDoc->set('ethercalcroomid', $roomId); $sheetDoc->save();
    $sheetOpen = '/omo/api/documents/ethercalc/open.php?id=' . $sheetDoc->getId() . '&share_token=' . rawurlencode($sheetLink->get('token'));
    $response = shareHttp($sheetOpen);
    mcpCheck($response['status'] === 302, 'Guest editable EtherCalc opens');
    parse_str((string)parse_url($response['headers']['location'][0], PHP_URL_QUERY), $query);
    mcpCheck(str_starts_with($query['auth'], 'omo.') && $query['auth'] !== omoEthercalcBuildEditToken($roomId), 'Share never exposes permanent room credential');
    mcpCheck(omoEthercalcResolveShareToken($query['auth'], $roomId) !== null, 'Scoped EtherCalc grant valid');
    $service = '/omo/api/documents/ethercalc/access.php?' . http_build_query(['room' => $roomId, 'auth' => $query['auth']]);
    mcpCheck(shareHttp($service)['json']['canEdit'] === true, 'Real callback accepts sheet grant');
    $items['sheet_read_link'] = $sheetReadLink = DocumentShareLink::createForDocument($sheetDoc, $uid);
    $sheetReadonly = shareHttp('/omo/api/documents/ethercalc/open.php?' . http_build_query(['id' => $sheetDoc->getId(), 'share_token' => $sheetReadLink->get('token')]));
    mcpCheck($sheetReadonly['status'] === 200 && str_contains($sheetReadonly['body'], 'data-pad-reader'), 'Read-only sheet rendered through the authenticated OMO snapshot');
    mcpCheck(!str_contains($sheetReadonly['body'], omoEthercalcBuildEditToken($roomId)), 'Read-only sheet never exposes native editing credentials');
    $board = $items['doc_' . Document::TYPE_WHITEBOARD]; $boardLink = $items['link_' . Document::TYPE_WHITEBOARD];
    $space = omoSpacedeckProvisionSpace('Document share regression');
    mcpCheck(!empty($space['status']), 'Create isolated board'); $spaceId = (string)$space['data']['id'];
    $board->set('spacedeckspaceid', $spaceId); $board->save();
    $boardUrl = omoSpacedeckBuildDocumentOpenUrl($board, 0, $boardLink);
    parse_str((string)parse_url($boardUrl, PHP_URL_QUERY), $boardQuery);
    $boardToken = $boardQuery['externalToken'] ?? '';
    mcpCheck((omoSpacedeckVerifyExternalAccessToken($boardToken)['accessLevel'] ?? 0) === 2, 'Scoped guest board editing grant');
    $boardCurl = curl_init('http://spacedeck:9666/spaces/' . rawurlencode($spaceId) . '?externalToken=' . rawurlencode($boardToken));
    curl_setopt_array($boardCurl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    curl_exec($boardCurl);
    mcpCheck((int)curl_getinfo($boardCurl, CURLINFO_RESPONSE_CODE) === 200, 'Real SpaceDeck accepts the anonymous shared editing identity');
    unset($boardCurl);
    $boardLink->set('allow_edit', 0); $boardLink->save();
    mcpCheck(omoSpacedeckVerifyExternalAccessToken($boardToken) === false, 'Board permission change revokes existing service credentials');
    if (getenv('DOCUMENT_SHARE_BROWSER_FIXTURE')) {
        $fixture = dirname(__DIR__) . '/tmp/document-share-live.json';
        file_put_contents($fixture, json_encode(['url' => 'https://localtest.me' . $sheetLink->buildShareUrl(),
            'sheetUrl' => $response['headers']['location'][0], 'room' => $roomId, 'auth' => $query['auth'],
            'ownerAuth' => omoEthercalcBuildEditToken($roomId),
            'revokeUrl' => 'https://localtest.me/omo/api/documents/share_delete.php',
            'documentId' => $sheetDoc->getId(), 'shareId' => $sheetLink->getId(), 'csrf' => commonCsrfToken(),
            'ownerCookie' => ['name' => session_name(), 'value' => session_id(), 'domain' => 'localtest.me', 'path' => '/', 'secure' => true]]));
        session_write_close();
        for ($attempt = 0; $attempt < 900 && is_file($fixture); $attempt++) { clearstatcache(true, $fixture); usleep(100000); }
        if (is_file($fixture)) unlink($fixture);
        session_start();
        $snapshot = omoEthercalcRequest('GET', '/_/' . $roomId);
        mcpCheck(str_contains((string)($snapshot['body'] ?? ''), 'SHARE_BROWSER_EDIT'), 'Real browser sheet edit persisted');
    }
    shareHttp('/omo/api/documents/share_delete.php', true, ['id' => $sheetDoc->getId(), 'share_id' => $sheetLink->getId()]);
    mcpCheck(shareHttp($service)['json']['canEdit'] === false, 'Revoked sheet credentials fail callback');

    $items['reader'] = mcpFixture(User::class, ['email' => 'share-reader-' . bin2hex(random_bytes(6)) . '@example.invalid', 'active' => 1]);
    $items['reader_member'] = mcpFixture(UserOrganization::class, ['IDuser' => $items['reader']->getId(), 'IDorganization' => $oid, 'active' => 1]);
    $doc->saveVisibilityRule('organization');
    $_SESSION['currentUser'] = (int)$items['reader']->getId();
    $denied = shareHttp('/omo/api/documents/share_create.php', true, ['id' => $doc->getId()]);
    mcpCheck($denied['status'] === 403, 'Reading does not grant external share management: ' . $denied['status'] . ' ' . $denied['body']);
    $popup = shareHttp('/omo/api/documents/share_popup.php?id=' . $doc->getId(), true);
    mcpCheck(str_contains($popup['body'], 'omoDocumentReaderLink') && !str_contains($popup['body'], 'id="omoDocumentSharePopupForm"'), 'Reader can copy OMO link without seeing external credentials');
    echo "document_share_links_test: OK\n";
} finally {
    if ($padId !== '') omoEtherpadDeleteDocumentPad($items['org'], $padId);
    if ($roomId !== '') omoEthercalcDeleteDocumentSheet($roomId);
    if ($spaceId !== '') omoSpacedeckDeleteSpace($spaceId);
    mcpCleanup($items);
    $_SESSION = $before;
    unlink($guestJar);
}
