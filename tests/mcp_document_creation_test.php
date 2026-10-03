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
    $items['no_documents_org'] = mcpFixture(McpTestOrganization::class, ['name' => 'MCP without Documents']);
    $items['no_documents_membership'] = mcpFixture(\dbObject\UserOrganization::class,
        ['IDuser' => $uid, 'IDorganization' => $items['no_documents_org']->getId(), 'active' => 1]);
    mcpCreationDenied(fn () => McpDocumentCreation::spaces(array_replace($readGrant,
        ['IDorganization' => (int)$items['no_documents_org']->getId()]), []), 'Documents must be enabled even when membership is active');
    foreach (['html', 'markdown'] as $format) {
        $emptyArgs = ['title' => 'MCP cleaned content', 'request_key' => 'empty-content-' . $format, 'holon_id' => $hid,
            'content_format' => $format, 'content' => '<script>bad()</script><iframe src="https://example.invalid/"></iframe>'];
        mcpCreationDenied(fn () => McpDocumentCreation::create($writeGrant, $emptyArgs), 'Fully removed content must not create an empty Memo');
        mcpCheck(!\dbObject\DbObject::getPdo()->inTransaction(), 'Rejected content leaves no open transaction');
        $documents = new \dbObject\ArrayDocument();
        $documents->load(['where' => [['field' => 'IDorganization', 'value' => $oid]]]);
        mcpCheck(count($documents) === ($format === 'html' ? 0 : 1), 'Rejected content leaves no document behind');
        $corrected = McpDocumentCreation::create($writeGrant, array_replace($emptyArgs, ['content' => 'Recovered content']));
        $items['corrected_' . $format] = new Document(); $items['corrected_' . $format]->load($corrected['record']['record_id']);
        mcpCheck($corrected['created'] && !$corrected['replayed'], 'Rejected content does not consume the request key');
    }
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
    mcpCheck($inFolder['document_type_label'] === 'Memo' && $items['folder_child']->getDocumentTypeLabel() === 'Memo', 'Memo label applies to MCP and existing HTML documents');
    mcpCheck($spaces['content_formats'] === ['text', 'html', 'markdown', 'md'] && $spaces['external_links_available'], 'Discovery includes formatted content and links without file storage');
    $markdownArgs = ['title' => 'MCP Markdown Memo', 'request_key' => 'create-markdown-memo', 'holon_id' => $hid,
        'content_format' => 'md', 'content' => "# Heading\n\n**Bold** and *italic*.\n\n- First\n- Second\n\n<script>bad()</script>\n\n<p onclick=\"bad()\">Body</p>"];
    $markdownCreated = McpDocumentCreation::create($writeGrant, $markdownArgs);
    $items['markdown_memo'] = new Document(); $items['markdown_memo']->load($markdownCreated['record']['record_id']);
    $markdownContent = $items['markdown_memo']->get('content');
    mcpCheck($markdownCreated['document_type'] === Document::TYPE_HTML && $markdownCreated['document_type_label'] === 'Memo'
        && $markdownCreated['visibility_type'] === 'self', 'Markdown creates a native private Memo');
    mcpCheck(str_contains($markdownContent, '<h1>Heading</h1>') && str_contains($markdownContent, '<strong>Bold</strong>')
        && str_contains($markdownContent, '<li>First</li>'), 'Converted Markdown formatting persists');
    mcpCheck(!str_contains($markdownContent, '<script') && !str_contains($markdownContent, 'bad()') && !str_contains($markdownContent, 'onclick'), 'Markdown is sanitized before persistence');
    mcpCheck(McpDocumentCreation::create($writeGrant, $markdownArgs)['replayed'], 'Markdown retry returns original Memo');
    mcpCreationDenied(fn () => McpDocumentCreation::create($writeGrant, array_replace($markdownArgs, ['content_format' => 'html'])), 'Changing format on retry rejected');
    $linkArgs = ['title' => 'MCP external link', 'request_key' => 'create-external-link',
        'parent_document_id' => (int)$items['folder']->getId(), 'external_url' => 'https://example.invalid/project?q=one#section'];
    $linkCreated = McpDocumentCreation::create($writeGrant, $linkArgs);
    $items['external_link'] = new Document(); $items['external_link']->load($linkCreated['record']['record_id']);
    mcpCheck($items['external_link']->isExternalLink() && $items['external_link']->getExternalUrl() === $linkArgs['external_url']
        && $linkCreated['external_url'] === $linkArgs['external_url'], 'URL becomes a native external link without fetching the target');
    mcpCheck($items['external_link']->shouldOpenExternalLinkInNewWindow() && $linkCreated['visibility_type'] === 'self'
        && (int)$items['external_link']->get('IDholon') === $hid, 'Link inherits the folder and owner-only visibility');
    $linkRetry = McpDocumentCreation::create($writeGrant, $linkArgs);
    mcpCheck($linkRetry['replayed'] && $linkRetry['record']['record_id'] === $linkCreated['record']['record_id'], 'URL retries are deduplicated');
    mcpCreationDenied(fn () => McpDocumentCreation::create($writeGrant, array_replace($linkArgs, ['external_url' => 'https://example.invalid/changed'])), 'Different URL with same key rejected');
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
