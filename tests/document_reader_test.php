<?php
declare(strict_types=1);
// Local Docker only; creates and removes its own fixtures. Run as www-data.
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{Document, DocumentPvPoint, User, UserOrganization, Application, OrganizationApplication};

function readerHttp(string $path, bool $authenticated = false): array
{
    $cookie = session_name() . '=' . session_id();
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    $curl = curl_init('https://localhost' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => array_filter(['Host: localtest.me', $authenticated ? 'Cookie: ' . $cookie : null])]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    session_start();
    mcpCheck(is_string($body), 'Reader HTTP request failed.');
    return ['status' => $status, 'body' => $body];
}

$before = $_SESSION;
$items = [];
try {
    $nonce = bin2hex(random_bytes(6));
    $items['org'] = mcpFixture(McpTestOrganization::class, ['name' => 'Reader test', 'shortname' => 'reader-' . $nonce]);
    $oid = (int)$items['org']->getId();
    foreach (['structure', 'documents'] as $hash) {
        $app = new Application(); mcpCheck((bool)$app->load(['hash', $hash]), 'Application exists');
        $items['app_' . $hash] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    }
    foreach (['owner', 'member', 'outsider'] as $name) {
        $items[$name] = mcpFixture(User::class, ['email' => "reader-$name-$nonce@example.invalid", 'active' => 1]);
        if ($name !== 'outsider') $items['membership_' . $name] = mcpFixture(UserOrganization::class,
            ['IDorganization' => $oid, 'IDuser' => $items[$name]->getId(), 'active' => 1]);
    }
    $uid = (int)$items['owner']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $fields = ['IDorganization' => $oid, 'IDuser' => $uid, 'documenttype' => Document::TYPE_HTML, 'active' => 1];
    $items['doc'] = mcpFixture(Document::class, $fields + ['title' => 'Document public été', 'description' => 'Description du document',
        'content' => '<p>READER_SAVED_CONTENT</p>', 'contentedition' => '<p>READER_UNSAVED_SECRET</p>']);
    $doc = $items['doc'];
    mcpCheck(!empty($doc->saveVisibilityRule('everyone')['status']), 'Public rule saved');
    $url = $doc->buildReaderUrl();
    mcpCheck($doc->canReadFromLink(0), 'Everyone is readable without membership');
    $response = readerHttp($url);
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'READER_SAVED_CONTENT'), 'Anonymous public reader opens saved content');
    mcpCheck(!str_contains($response['body'], 'READER_UNSAVED_SECRET'), 'Reader never publishes unsaved drafts');
    mcpCheck(str_contains($response['body'], 'Voir dans OMO') && str_contains($response['body'], $doc->buildOmoDocumentUrl()), 'Reader links to the full OMO document route');
    mcpCheck(!str_contains($response['body'], 'contenteditable') && !str_contains($response['body'], 'collabora/open'), 'Reader has no editor');
    file_put_contents(dirname(__DIR__) . '/tmp/document-reader-public.html', $response['body']);
    $popup = readerHttp('/omo/api/documents/share_popup.php?id=' . $doc->getId(), true);
    mcpCheck($popup['status'] === 200 && str_contains($popup['body'], 'omoDocumentReaderLink'), 'Share popup contains a permanent reader link');
    file_put_contents(dirname(__DIR__) . '/tmp/document-reader-popup.html', $popup['body']);

    $doc->saveVisibilityRule('organization');
    $response = readerHttp($url);
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'commonLoginConfig'), 'Private reader asks anonymous visitor to log in');
    mcpCheck(!str_contains($response['body'], 'READER_SAVED_CONTENT') && !str_contains($response['body'], 'Document public été'), 'Login page does not expose private content or title');
    mcpCheck(str_contains($response['body'], $url), 'Login returns to the same reader');
    file_put_contents(dirname(__DIR__) . '/tmp/document-reader-login.html', $response['body']);
    $_SESSION['currentUser'] = (int)$items['member']->getId();
    mcpCheck(readerHttp($url, true)['status'] === 200, 'Organization member can read');
    $_SESSION['currentUser'] = (int)$items['outsider']->getId();
    $response = readerHttp($url, true);
    mcpCheck($response['status'] === 404 && !str_contains($response['body'], 'READER_SAVED_CONTENT'), 'Login alone grants no organization access');
    file_put_contents(dirname(__DIR__) . '/tmp/document-reader-denied.html', $response['body']);
    $_SESSION['currentUser'] = (int)$items['member']->getId();
    $doc->saveVisibilityRule('self');
    mcpCheck(readerHttp($url, true)['status'] === 404, 'Self visibility excludes other members');
    $_SESSION['currentUser'] = $uid;
    mcpCheck(readerHttp($url, true)['status'] === 200, 'Owner can read a private document');
    $doc->saveVisibilityRule('everyone');

    $items['folder'] = mcpFixture(Document::class, array_replace($fields, ['documenttype' => Document::TYPE_FOLDER, 'estDossier' => 1, 'title' => 'Private folder']));
    $items['folder']->saveVisibilityRule('self');
    $doc->set('IDdocument_parent', $items['folder']->getId()); $doc->save();
    mcpCheck(!$doc->canReadFromLink(0), 'Private parent prevents anonymous access');
    $items['folder']->saveVisibilityRule('everyone');
    mcpCheck($doc->canReadFromLink(0), 'Public parent allows anonymous access');
    $items['folder']->set('active', 0); $items['folder']->save();
    mcpCheck(!$doc->canReadFromLink(0), 'Archived parent prevents access');
    $doc->set('IDdocument_parent', null); $doc->save();
    $items['hidden'] = mcpFixture(Document::class, $fields + ['title' => 'Hidden', 'content' => 'READER_HIDDEN_CONTENT']);
    $items['hidden']->saveVisibilityRule('self');
    $embed = static fn($target): string => '<span data-omo-embed-type="document" data-omo-document-id="' . $target->getId() . '" data-omo-document-title="Attachment"></span>';
    $doc->set('content', '<p>READER_SAVED_CONTENT</p>' . $embed($items['hidden'])); $doc->save();
    mcpCheck(!str_contains(readerHttp($url)['body'], 'READER_HIDDEN_CONTENT'), 'Public page cannot disclose private embeds');
    $items['hidden']->saveVisibilityRule('everyone');
    mcpCheck(str_contains(readerHttp($url)['body'], '#documents-d' . $items['hidden']->getId()), 'Public embeds can be opened in the reader');

    $items['pv'] = mcpFixture(Document::class, array_replace($fields, ['documenttype' => Document::TYPE_PV, 'title' => 'Public PV', 'pvstage' => Document::PV_STAGE_PREPARATION]));
    $pv = $items['pv']; $pv->saveVisibilityRule('everyone');
    mcpCheck(!$pv->canReadFromLink(0), 'Public visibility does not expose meeting preparation');
    $pv->set('pvstage', Document::PV_STAGE_VALIDATED); $pv->save();
    $items['point'] = mcpFixture(DocumentPvPoint::class, ['IDdocument' => $pv->getId(), 'title' => 'Visible point', 'content' => 'READER_PUBLIC_POINT', 'is_handled' => 1, 'active' => 1]);
    $items['secret_point'] = mcpFixture(DocumentPvPoint::class, ['IDdocument' => $pv->getId(), 'title' => 'Confidential', 'content' => 'READER_CONFIDENTIAL_POINT', 'is_confidential' => 1, 'is_handled' => 1, 'active' => 1]);
    $response = readerHttp($pv->buildReaderUrl());
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'READER_PUBLIC_POINT') && !str_contains($response['body'], 'READER_CONFIDENTIAL_POINT'), 'Public PV shows visible points and protects confidential points');

    $items['file'] = mcpFixture(Document::class, array_replace($fields, ['documenttype' => Document::TYPE_UPLOADED_FILE, 'title' => 'Public PDF',
        'storedfilepath' => '/fixture-only.pdf', 'storedfilename' => 'fixture.pdf', 'storedfilemime' => 'application/pdf']));
    $file = $items['file']; $file->saveVisibilityRule('everyone');
    $response = readerHttp($file->buildReaderUrl());
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'reader=1') && str_contains($response['body'], 'inline=1'), 'Public PDF preview uses an authorized download');
    file_put_contents(dirname(__DIR__) . '/tmp/document-reader-pdf.html', $response['body']);
    $popup = readerHttp('/omo/api/documents/share_popup.php?id=' . $file->getId(), true);
    mcpCheck($popup['status'] === 200 && str_contains($popup['body'], 'omoDocumentReaderLink'), 'Uploaded files also have a permanent share link');
    $download = $file->buildStoredFileDownloadUrl() . '&reader=1';
    mcpCheck(readerHttp($download)['status'] === 503, 'Public download reaches the storage check');
    mcpCheck(readerHttp($download . '&version_id=1')['status'] === 404, 'Public reader does not expose version history');
    $file->saveVisibilityRule('self');
    mcpCheck(readerHttp($download)['status'] === 404, 'Changing visibility immediately revokes anonymous download');
    mcpCheck(readerHttp($download, true)['status'] === 503, 'Owner download remains authorized');
    $doc->set('active', 0); $doc->save();
    mcpCheck(readerHttp($url)['status'] === 404 && readerHttp($url, true)['status'] === 404, 'Archived documents cannot be read');
    mcpCheck(readerHttp('/omo/document.php?id=0')['status'] === 404, 'Invalid reader id is unavailable');
    echo "document_reader_test: OK\n";
} finally {
    mcpCleanup($items);
    $_SESSION = $before;
}
