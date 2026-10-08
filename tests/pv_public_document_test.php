<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/pv_participation.php';
use dbObject\{Document, DocumentPvPoint, DocumentShareLink};

function pvDocumentRequest(array $query, string $path = '/omo/pv_document.php'): array
{
    $curl = curl_init('https://localhost' . $path . '?' . http_build_query($query));
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => ['Host: localtest.me']]);
    $body = curl_exec($curl);
    mcpCheck(is_string($body), 'Reader HTTP request failed.');
    return ['status' => (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'body' => $body];
}
$before = $_SESSION;
$items = mcpFixtures();
try {
    $oid = (int)$items['org']->getId();
    $uid = (int)$items['user']->getId();
    $docFields = ['documenttype' => Document::TYPE_HTML, 'IDorganization' => $oid, 'IDuser' => $uid, 'active' => 1];
    $items['target'] = mcpFixture(Document::class, $docFields + ['title' => 'Private attachment fixture', 'content' => '<p>VISIBLE_ATTACHMENT_MARKER</p>']);
    $items['hidden_doc'] = mcpFixture(Document::class, $docFields + ['title' => 'Hidden attachment', 'content' => '<p>HIDDEN_ATTACHMENT_MARKER</p>']);
    $items['foreign_doc'] = mcpFixture(Document::class, array_replace($docFields, ['IDorganization' => $items['other_org']->getId(), 'title' => 'Other org attachment']));
    $items['pv'] = mcpFixture(Document::class, array_replace($docFields, ['documenttype' => Document::TYPE_PV, 'title' => 'Public PV fixture', 'pvstage' => Document::PV_STAGE_PREPARATION]));
    $embed = static fn($doc): string => '<span class="omo-document-embed" data-omo-embed-type="document" data-omo-document-id="' . $doc->getId() . '" data-omo-document-title="Attachment"><a href="#documents-d' . $doc->getId() . '">Attachment</a></span>';
    $pointFields = ['IDdocument' => $items['pv']->getId(), 'title' => 'Fixture point', 'pointtype' => 'information', 'active' => 1];
    $items['point'] = mcpFixture(DocumentPvPoint::class, $pointFields + ['content' => $embed($items['target']) . $embed($items['foreign_doc'])]);
    $items['confidential'] = mcpFixture(DocumentPvPoint::class, $pointFields + ['content' => $embed($items['hidden_doc']), 'is_confidential' => 1]);
    $items['link'] = mcpFixture(DocumentShareLink::class, ['IDorganization' => $oid, 'IDdocument' => $items['pv']->getId(),
        'IDuser' => $uid, 'token' => DocumentShareLink::generateUniqueToken(), 'recipient_email' => 'guest@example.invalid', 'allow_pv_contribution' => 1, 'active' => 1]);
    $link = $items['link'];
    $_SESSION['currentUser'] = 0;
    mcpCheck($link->getReadablePvEmbeddedDocument((int)$items['target']->getId()) instanceof Document, 'Guest can read a referenced private document.');
    mcpCheck(!$link->getReadablePvEmbeddedDocument((int)$items['hidden_doc']->getId()), 'Confidential points do not grant guest access.');
    mcpCheck(!$link->getReadablePvEmbeddedDocument((int)$items['foreign_doc']->getId()), 'Cross-organization references do not grant access.');
    mcpCheck(!$link->canUsePvDocumentReferences($embed($items['hidden_doc'])), 'Guest cannot forge a reference to gain access.');
    mcpCheck($link->canUsePvDocumentReferences($embed($items['foreign_doc']), $embed($items['foreign_doc'])), 'Existing unavailable references do not block text edits.');
    mcpCheck($link->canUsePvDocumentReferences($embed($items['target'])), 'Guest can preserve references that already grant access.');
    $items['target']->set('content', '<p>VISIBLE_ATTACHMENT_MARKER</p>' . $embed($items['hidden_doc']));
    $items['target']->save();
    $_SESSION['currentUser'] = $uid;
    $rendered = $items['target']->renderResolvedHtmlForViewer((string)$items['target']->get('content'), $oid, ['compactEmbeds' => true, 'pvParticipationLink' => $link]);
    mcpCheck(!str_contains($rendered, 'Hidden attachment'), 'Logged-in privileges do not broaden the token reader to nested documents.');
    $_SESSION['currentUser'] = 0;
    $query = ['id' => $items['target']->getId(), 'pv_document_id' => $items['pv']->getId(), 'pv_token' => $link->get('token')];
    $response = pvDocumentRequest($query);
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'VISIBLE_ATTACHMENT_MARKER'), 'Anonymous HTTP reader opens saved HTML.');
    mcpCheck(!str_contains($response['body'], 'HIDDEN_ATTACHMENT_MARKER'), 'Nested documents do not inherit meeting access.');
    mcpCheck(!str_contains($response['body'], 'contenteditable') && !str_contains($response['body'], 'collabora'), 'Reader exposes no editing controls.');
    $response = pvDocumentRequest(array_replace($query, ['id' => $items['hidden_doc']->getId()]));
    mcpCheck($response['status'] === 404 && str_contains($response['body'], 'acces est indisponible'), 'Denied reader shows an explanation.');
    mcpCheck(pvDocumentRequest(array_replace($query, ['pv_document_id' => $items['target']->getId()]))['status'] === 404, 'Wrong source meeting is rejected.');
    mcpCheck(pvDocumentRequest(array_replace($query, ['pv_token' => 'invalid']), '/omo/api/documents/upload/download.php')['status'] === 404, 'File download also checks the meeting token.');
    $items['file'] = mcpFixture(Document::class, array_replace($docFields, ['documenttype' => Document::TYPE_UPLOADED_FILE,
        'title' => 'File attachment', 'storedfilepath' => '/fixture-only.pdf', 'storedfilename' => 'fixture.pdf', 'storedfilemime' => 'application/pdf']));
    $items['point']->set('content', $embed($items['target']) . $embed($items['file'])); $items['point']->save();
    $fileQuery = array_replace($query, ['id' => $items['file']->getId()]);
    $response = pvDocumentRequest($fileQuery);
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'inline=1') && str_contains($response['body'], 'pv_token='), 'PDF actions carry the scoped meeting context.');
    mcpCheck(pvDocumentRequest($fileQuery, '/omo/api/documents/upload/download.php')['status'] === 503, 'Authorized download reaches storage without requiring an account.');
    $link->set('password_hash', password_hash('fixture-password', PASSWORD_DEFAULT)); $link->save();
    mcpCheck(pvDocumentRequest($query)['status'] === 404, 'Password protected participation cannot be bypassed.');
    $link->set('password_hash', null); $link->save();
    $items['point']->set('content', ''); $items['point']->save();
    mcpCheck(pvDocumentRequest($query)['status'] === 404, 'Removing the reference revokes document access.');
    $items['point']->set('content', $embed($items['target'])); $items['point']->save();
    $items['target']->set('active', 0); $items['target']->save();
    mcpCheck(!$link->getReadablePvEmbeddedDocument((int)$items['target']->getId()), 'Inactive documents cannot be read.');
    $items['target']->set('active', 1); $items['target']->save();
    $link->set('dateexpiration', time() - 60); $link->save();
    mcpCheck(pvDocumentRequest($query)['status'] === 404, 'Expired link revokes reader access.');
    $link->set('dateexpiration', null); $link->set('active', 0); $link->save();
    mcpCheck(pvDocumentRequest($query)['status'] === 404, 'Revoked link revokes reader access.');
    $link->set('active', 1); $link->save();
    $items['pv']->set('pvstage', Document::PV_STAGE_VALIDATED); $items['pv']->save();
    mcpCheck(pvDocumentRequest($query)['status'] === 404, 'Validated PV ends participation access.');
    echo "pv_public_document_test: OK\n";
} finally {
    mcpCleanup($items);
    $_SESSION = $before;
}
