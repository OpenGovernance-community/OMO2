<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/mcp/files.php';
use dbObject\{McpDocumentCreation, McpContent, Document, ObjectVisibility, McpOauthGrant};
$before = $_SESSION; $items = mcpFixtures();
function mcpCreationDenied(callable $action, string $message): void
{
    $denied = false;
    try { $action(); } catch (DomainException $error) { $denied = true; }
    mcpCheck($denied, $message);
}
try {
    mcpEnableDocumentCreation($items);
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['role']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $readGrant = ['IDuser' => $uid, 'IDorganization' => $oid, 'scope' => OMO_MCP_SCOPE];
    $authorization = mcpAuthorizationRequest($items['client']); $authorization['scope'] = OMO_MCP_SCOPE . ' ' . OMO_MCP_CREATE_SCOPE;
    $code = McpOauthGrant::issueCode($items['client'], $uid, $oid, $authorization);
    $tokens = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));
    $writeGrant = McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl());
    $args = ['title' => 'MCP created text', 'request_key' => 'create-text-once', 'holon_id' => $hid,
        'content' => "<script>literal text</script>\nSecond line"];
    mcpCreationDenied(fn () => McpDocumentCreation::create($readGrant, $args), 'Read consent never permits writing');
    mcpCreationDenied(fn () => McpDocumentCreation::create($writeGrant, array_replace($args, ['holon_id' => 0])), 'Organization space without permission rejected');
    foreach (['other_root', 'hidden', 'inactive'] as $fixture) {
        mcpCreationDenied(fn () => McpDocumentCreation::create($writeGrant, array_replace($args, ['holon_id' => (int)$items[$fixture]->getId()])), 'Unavailable destination rejected');
    }
    $spaces = McpDocumentCreation::spaces($readGrant, ['kind' => 'holons']);
    mcpCheck(array_column($spaces['items'], 'holon_id') === [$hid], 'Only permitted holon is discoverable');
    mcpCheck(!$spaces['write_authorized'] && !$spaces['file_storage_available'], 'Consent and file storage availability are explicit');
    mcpCheck(McpDocumentCreation::spaces($writeGrant, [])['items'] === [], 'Organization creation is not inferred from role permission');
    $created = McpDocumentCreation::create($writeGrant, $args);
    $items['created'] = new Document(); $items['created']->load($created['record']['record_id']);
    mcpCheck($created['created'] && !$created['replayed'] && $created['visibility_type'] === 'self', 'Created privately by default');
    mcpCheck((int)$items['created']->get('IDuser') === $uid && (int)$items['created']->get('IDholon') === $hid, 'Owner and destination come from authorized identity');
    mcpCheck(str_contains($items['created']->get('content'), '&lt;script&gt;'), 'Plain text is escaped, not interpreted as HTML');
    mcpCheck(!str_contains(json_encode($created), (string)$items['created']->get('codeview')), 'Share keys excluded from write response');
    $retry = McpDocumentCreation::create($writeGrant, $args);
    mcpCheck($retry['replayed'] && $retry['record']['record_id'] === $created['record']['record_id'], 'Retry returns same document');
    mcpCreationDenied(fn () => McpDocumentCreation::create($writeGrant, array_replace($args, ['content' => 'Changed'])), 'Same key cannot create different content');
    $items['folder'] = mcpFixture(Document::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'title' => 'MCP writable folder', 'estDossier' => 1, 'documenttype' => Document::TYPE_FOLDER, 'active' => 1]);
    $items['folder_visibility'] = mcpFixture(ObjectVisibility::class, ['object_type' => 'document',
        'object_id' => $items['folder']->getId(), 'IDorganization' => $oid, 'visibility_type' => 'organization', 'active' => 1]);
    $folders = McpDocumentCreation::spaces($writeGrant, ['kind' => 'folders', 'limit' => 1]);
    mcpCheck($folders['items'][0]['parent_document_id'] === (int)$items['folder']->getId(), 'Writable folder discovery');
    $folderArgs = ['title' => 'MCP folder HTML', 'request_key' => 'create-in-folder', 'parent_document_id' => (int)$items['folder']->getId(),
        'content' => '<h2>Heading</h2><script>window.bad=true</script><p onclick="bad()">Body</p>',
        'content_format' => 'html', 'visibility_type' => 'role'];
    $inFolder = McpDocumentCreation::create($writeGrant, $folderArgs);
    $items['folder_child'] = new Document(); $items['folder_child']->load($inFolder['record']['record_id']);
    mcpCheck($inFolder['record']['parent_id'] === (int)$items['folder']->getId() && $inFolder['record']['holon_id'] === $hid, 'Folder context inherited');
    mcpCheck(!str_contains($items['folder_child']->get('content'), '<script') && !str_contains($items['folder_child']->get('content'), 'onclick'), 'HTML uses existing sanitizer');
    mcpCreationDenied(fn () => McpDocumentCreation::create($writeGrant, array_replace($folderArgs, ['holon_id' => (int)$items['root']->getId()])), 'Conflicting folder and holon rejected');
    mcpCreationDenied(fn () => McpDocumentCreation::create($writeGrant, array_replace($folderArgs, ['parent_document_id' => (int)$items['created']->getId()])), 'Non-folder parent rejected');
    mcpCreationDenied(fn () => McpDocumentCreation::create($writeGrant, array_replace($args, ['request_key' => 'no-file-storage', 'content' => null,
        'file' => ['file_id' => 'fixture-file', 'download_url' => 'https://example.invalid/file.pdf']])), 'Unconfigured file storage fails before creating a record');
    $items['document_assignment']->set('active', 0); $items['document_assignment']->save();
    mcpCreationDenied(fn () => McpDocumentCreation::create($writeGrant, array_replace($args, ['request_key' => 'rights-removed'])), 'Current permission loss prevents creation');
    $items['document_assignment']->set('active', 1); $items['document_assignment']->save();
    $items['created']->delete();
    mcpCreationDenied(fn () => McpDocumentCreation::create($writeGrant, $args), 'Deleted document is not recreated on retry');
    $items['membership']->set('active', 0); $items['membership']->save();
    mcpCreationDenied(fn () => McpDocumentCreation::create($writeGrant, array_replace($args, ['request_key' => 'membership-lost'])), 'Membership checked for each creation');
    $items['membership']->set('active', 1); $items['membership']->save();
    McpOauthGrant::revokeOwned((int)$writeGrant['id'], $uid);
    mcpCreationDenied(fn () => McpDocumentCreation::create($writeGrant, array_replace($args, ['request_key' => 'grant-revoked'])), 'Grant revocation is checked again by creation service');
    foreach (['http://example.com/a', 'https://127.0.0.1/a', 'https://10.0.0.1/a', 'https://169.254.169.254/a',
        'https://100.64.0.1/a', 'https://224.0.0.1/a', 'https://[::1]/a', 'https://user:pass@example.com/a', 'https://example.com:444/a', 'file:///etc/passwd'] as $url) {
        mcpCreationDenied(fn () => omoMcpFileTarget($url), 'Unsafe file source rejected');
    }
} finally { mcpCleanup($items); $_SESSION = $before; }
echo "mcp_document_creation_test: OK\n";
