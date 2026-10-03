<?php
namespace dbObject;

/** Document creation with existing OMO permissions, storage and visibility rules. */
class McpDocumentCreation extends DbObject
{
    public static function tableName() { return 'mcp_document_creation'; }
    public static function rules()
    {
        return [[['id', 'created_at'], 'integer'], [['completed'], 'boolean'], [['IDuser', 'IDorganization', 'IDdocument'], 'fk'],
            [['key_hash', 'payload_hash'], 'string'], [['id'], 'safe']];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'IDuser' => 'Personne', 'IDorganization' => 'Organisation', 'IDdocument' => 'Document',
            'key_hash' => 'Empreinte demande', 'payload_hash' => 'Empreinte contenu', 'created_at' => 'Creation', 'completed' => 'Termine'];
    }
    public static function attributeLength() { return ['key_hash' => 64, 'payload_hash' => 64]; }
    public function canView() { return false; }
    public function canViewDetail() { return false; }
    public function canEdit() { return false; }
    public function canDelete() { return false; }

    private static function organization(array $grant): Organization
    {
        $org = McpStructure::organization($grant);
        $user = new User();
        if (!$user->load((int)$grant['IDuser']) || !$user->get('active')) throw new \DomainException('User unavailable.');
        if (!$org->isApplicationEnabled('documents', (int)$grant['IDuser'])) {
            throw new \DomainException('Documents module unavailable.');
        }
        return $org;
    }
    private static function destination(Organization $org, array $grant, int $holonId, int $parentId): array
    {
        $oid = (int)$org->getId(); $uid = (int)$grant['IDuser'];
        if ($parentId > 0) {
            $parent = McpContent::accessibleObject($org, McpContent::context($grant, $org, null), 'documents', $parentId);
            if (!$parent instanceof Document || !$parent->isFolder() || $parent->getDocumentType() !== Document::TYPE_FOLDER) {
                throw new \DomainException('Readable native document folder required.');
            }
            $parentHolon = (int)$parent->get('IDholon');
            if ($holonId > 0 && $holonId !== $parentHolon) throw new \DomainException('Folder and holon destinations do not match.');
            $holonId = $parentHolon;
        }
        if ($holonId > 0) {
            $holon = new Holon();
            if (!$holon->load($holonId) || !$holon->get('active') || !$holon->get('visible')
                || !$org->containsHolon($holon) || !$holon->canViewDetail()) throw new \DomainException('Destination unavailable.');
        }
        if (!Document::canCreateInOrganizationContext($oid, $holonId ?: null, $uid, $parentId, false)) {
            throw new \DomainException('You do not have permission to create documents in this space.');
        }
        return ['holon_id' => $holonId, 'parent_document_id' => $parentId];
    }
    private static function visibilityTypes(int $oid, int $holonId): array
    {
        return array_values(array_filter(['self', 'organization', 'circle', 'role'], static fn ($type) =>
            Document::resolveCompatibleScopeTypeForHolonId($type, $oid, $holonId ?: null, 'organization') === $type));
    }

    public static function spaces(array $grant, array $args): array
    {
        $org = self::organization($grant); $oid = (int)$org->getId();
        $kind = $args['kind'] ?? 'organization'; $cursor = $args['after_id'] ?? 0; $limit = $args['limit'] ?? 20;
        $items = []; $complete = false; $scanned = 0;
        $append = static function (int $hid, int $parentId, string $name, int $id) use ($org, $oid, $grant, $kind, &$items): void {
            try { $destination = self::destination($org, $grant, $hid, $parentId); }
            catch (\DomainException $error) { return; }
            $items[] = ['kind' => $kind, 'id' => $id, 'name' => trim(html_entity_decode(strip_tags($name), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                ...$destination, 'visibility_types' => self::visibilityTypes($oid, $destination['holon_id']),
                'url' => $parentId > 0 ? McpContent::sourceUrl($oid, 'documents', $parentId, $hid)
                    : \omoMcpIssuer() . '/omo/o/' . $oid . ($hid > 0 ? '/h/' . $hid : '')];
        };
        if ($kind === 'organization') {
            $append(0, 0, (string)$org->get('name'), $oid); $complete = true;
        } else {
            $from = $kind === 'holons' ? 'holon t LEFT JOIN holon root ON root.id = t.IDholon_org' : 'document t';
            $where = $kind === 'holons'
                ? 'COALESCE(NULLIF(t.IDorganization, 0), root.IDorganization) = :oid AND t.active = 1 AND t.visible = 1'
                : "t.IDorganization = :oid AND t.active = 1 AND t.estDossier = 1 AND t.documenttype = 'folder'";
            while ($scanned < 500 && count($items) < $limit) {
                $rows = self::fetchAll('SELECT t.id FROM ' . $from . ' WHERE ' . $where . ' AND t.id > :after_id ORDER BY t.id ASC LIMIT 100',
                    ['oid' => $oid, 'after_id' => $cursor]);
                if ($rows === false) throw new \RuntimeException('MCP document spaces query failed.');
                if (!$rows) { $complete = true; break; }
                foreach ($rows as $row) {
                    $cursor = (int)$row['id']; $scanned++;
                    $object = $kind === 'holons' ? new Holon() : new Document();
                    if ($object->load($cursor)) $append($kind === 'holons' ? $cursor : (int)$object->get('IDholon'),
                        $kind === 'folders' ? $cursor : 0, (string)$object->get($kind === 'holons' ? 'name' : 'title'), $cursor);
                    if (count($items) === $limit) break;
                }
                if (count($items) < $limit && count($rows) < 100) { $complete = true; break; }
            }
        }
        return ['organization_id' => $oid, 'kind' => $kind, 'items' => $items, 'next_after_id' => $complete ? null : $cursor,
            'complete' => $complete, 'write_authorized' => \omoMcpCanCreateDocuments($grant),
            'file_storage_available' => $org->hasDocumentStorage(), 'max_file_bytes' => 20971520, 'default_visibility' => 'self',
            'content_formats' => ['text', 'html', 'markdown', 'md'], 'external_links_available' => true,
            'instructions' => 'These spaces permit document creation with your OMO permissions; documents:create OAuth consent is also required. Enumerate organization, holons and folders separately, following each cursor until null, even after an empty page. Copy holon_id and parent_document_id from the chosen destination. Native folders only; remote folders and project associations are not supported. Default visibility is owner only. File uploads require organization document storage.'];
    }

    private static function response(Organization $org, array $grant, int $documentId, bool $replayed): array
    {
        $context = McpContent::context($grant, $org, null);
        $document = McpContent::accessibleObject($org, $context, 'documents', $documentId);
        if (!$document) throw new \DomainException('The previously created document is no longer available. Do not retry with a new key.');
        return ['created' => true, 'replayed' => $replayed,
            'record' => McpBrowse::summary($org, $context, 'documents', $document),
            'document_type' => $document->getDocumentType(), 'document_type_label' => $document->getDocumentTypeLabel(),
            'external_url' => $document->isExternalLink() ? $document->getExternalUrl() : null,
            'visibility_type' => $document->getPrimaryVisibilityRuleRow()['visibility_type'] ?? null,
            'file_name' => $document->get('storedfilename'), 'file_size' => (int)$document->get('storedfilesize')];
    }

    public static function create(array $grant, array $args): array
    {
        if (!\omoMcpCanCreateDocuments($grant)) throw new \DomainException('Reconnect and authorize documents:create before creating documents.');
        if (!McpOauthGrant::hasActiveCreationAuthorization($grant)) throw new \DomainException('Creation authorization expired or revoked. Reconnect your account.');
        $org = self::organization($grant); $oid = (int)$org->getId(); $uid = (int)$grant['IDuser'];
        $destination = self::destination($org, $grant, $args['holon_id'] ?? 0, $args['parent_document_id'] ?? 0);
        $visibility = $args['visibility_type'] ?? 'self';
        if (!in_array($visibility, self::visibilityTypes($oid, $destination['holon_id']), true)) {
            throw new \DomainException('Visibility unavailable for this destination. Use omo_list_document_spaces.');
        }
        $fingerprint = ['title' => $args['title'], 'description' => $args['description'] ?? '', 'keywords' => $args['keywords'] ?? '',
            'content' => $args['content'] ?? null, 'content_format' => $args['content_format'] ?? 'text',
            'file_id' => $args['file']['file_id'] ?? null, 'file_name' => $args['file']['file_name'] ?? null,
            'destination' => $destination, 'visibility_type' => $visibility];
        // Keep fingerprints of previously supported payloads unchanged for existing retry keys.
        if (isset($args['external_url'])) $fingerprint['external_url'] = $args['external_url'];
        $bindings = ['uid' => $uid, 'oid' => $oid, 'key_hash' => hash('sha256', $args['request_key'])];
        $payloadHash = hash('sha256', json_encode($fingerprint, JSON_THROW_ON_ERROR));
        $lookup = 'SELECT * FROM mcp_document_creation WHERE IDuser = :uid AND IDorganization = :oid AND key_hash = :key_hash';
        $existing = self::fetchRow($lookup, $bindings);
        if ($existing) {
            if (!hash_equals($existing['payload_hash'], $payloadHash)) throw new \DomainException('request_key already used for different content or destination.');
            return self::response($org, $grant, (int)$existing['IDdocument'], true);
        }
        // Parse before taking the idempotency lock; successful retries skip this work entirely.
        $content = isset($args['content']) ? PropertyFormat::formattedTextToHtml($args['content'], $args['content_format'] ?? 'text') : '';
        if (isset($args['content']) && $content === '') {
            throw new \DomainException('No Memo content remains after cleaning. Supply text or supported formatting and retry with the same request_key.');
        }
        $uploadedFile = null; $document = new Document(); $pdo = self::getPdo();
        try {
            if (isset($args['file'])) {
                if (!$org->hasDocumentStorage()) throw new \DomainException('Configure organization document storage before importing files. Text documents remain available.');
                require_once dirname(__DIR__, 2) . '/common/mcp/files.php';
                $uploadedFile = \omoMcpDownloadFile($args['file'], $args['title']);
            }
            // Recheck rights after conversion or download, before opening the transaction.
            $org = self::organization($grant);
            if (!McpOauthGrant::hasActiveCreationAuthorization($grant)) throw new \DomainException('Creation authorization expired or revoked. Reconnect your account.');
            self::destination($org, $grant, $destination['holon_id'], $destination['parent_document_id']);
            $pdo->beginTransaction();
            if (!self::execute('INSERT INTO mcp_document_creation (IDuser, IDorganization, key_hash, payload_hash, created_at)
                VALUES (:uid, :oid, :key_hash, :payload_hash, :now) ON DUPLICATE KEY UPDATE id = id',
                $bindings + ['payload_hash' => $payloadHash, 'now' => time()])) throw new \RuntimeException('MCP request storage failed.');
            $row = self::fetchRow($lookup . ' FOR UPDATE', $bindings);
            if (!$row || !hash_equals($row['payload_hash'], $payloadHash)) throw new \DomainException('request_key already used for different content or destination.');
            if ($row['completed']) {
                $result = self::response($org, $grant, (int)$row['IDdocument'], true); $pdo->commit(); return $result;
            }
            $saved = $document->createInOrganizationContext($oid, $destination['holon_id'] ?: null, $uid, [
                'title' => $args['title'], 'description' => $args['description'] ?? '', 'keywords' => $args['keywords'] ?? '',
                'content' => $content, 'document_type' => $uploadedFile ? Document::TYPE_UPLOADED_FILE
                    : (isset($args['external_url']) ? Document::TYPE_EXTERNAL_LINK : Document::TYPE_HTML),
                'external_url' => $args['external_url'] ?? '', 'open_in_new_window' => isset($args['external_url']),
                'uploaded_file' => $uploadedFile, 'parent_document_id' => $destination['parent_document_id'],
                'visibility_type' => $visibility, 'edit_visibility_type' => 'self']);
            if (empty($saved['status'])) throw new \DomainException((string)($saved['text'] ?? 'Document creation failed.'));
            if (!self::execute('UPDATE mcp_document_creation SET IDdocument = :document, completed = 1 WHERE id = :id',
                ['document' => (int)$document->getId(), 'id' => (int)$row['id']])) throw new \RuntimeException('MCP request completion failed.');
            $result = self::response($org, $grant, (int)$document->getId(), false);
            $pdo->commit(); return $result;
        } catch (\Throwable $error) {
            // An uploaded object is external to the DB transaction; remove it if later persistence fails.
            $storedPath = trim((string)$document->get('storedfilepath'));
            if ($storedPath !== '') {
                try { $org->deleteDocumentFileFromStorage($storedPath); }
                catch (\Throwable $cleanupError) { error_log('OMO MCP failed to clean up an uncommitted document file.'); }
            }
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        } finally {
            if ($uploadedFile && is_file($uploadedFile['tmp_name'])) unlink($uploadedFile['tmp_name']);
        }
    }
}
