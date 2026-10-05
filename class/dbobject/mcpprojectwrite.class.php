<?php
namespace dbObject;

/** REST/MCP project access, permission checks and atomic idempotent native writes. */
class McpProjectWrite extends DbObject
{
    private const FIELDS = ['title', 'description', 'status', 'priority', 'importance', 'planned_start_date', 'planned_end_date',
        'project_size', 'blocked_reason', 'blocked_until', 'blocked_auto_reactivate', 'blocked_reactivate_status'];
    public static function tableName() { return 'mcp_project_write'; }
    public static function rules()
    {
        return [[['id', 'created_at'], 'integer'], [['completed'], 'boolean'], [['IDuser', 'IDorganization', 'IDproject'], 'fk'],
            [['operation', 'key_hash', 'payload_hash'], 'string'], [['id'], 'safe']];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'created_at' => 'Creation', 'completed' => 'Termine', 'IDuser' => 'Personne',
            'IDorganization' => 'Organisation', 'IDproject' => 'Projet', 'operation' => 'Operation', 'key_hash' => 'Demande', 'payload_hash' => 'Contenu'];
    }
    public static function attributeLength() { return ['operation' => 10, 'key_hash' => 64, 'payload_hash' => 64]; }
    public function canView() { return false; }
    public function canViewDetail() { return false; }
    public function canEdit() { return false; }
    public function canDelete() { return false; }

    private static function organization(array $grant): Organization
    {
        $org = McpStructure::organization($grant);
        $user = new User();
        if (!$user->load((int)$grant['IDuser']) || !$user->get('active') || !$org->isApplicationEnabled('projects', (int)$grant['IDuser'])) {
            throw new \DomainException('Projects application unavailable.');
        }
        require_once dirname(__DIR__, 2) . '/omo/api/projects/shared.php';
        return $org;
    }

    private static function context(Organization $org, int $holonId): array
    {
        if ($holonId) {
            $root = $org->getEnabledStructuralRootHolon();
            if (!$root) throw new \DomainException('Project context unavailable.');
            McpStructure::requireHolon($org, $root, $holonId);
        }
        $context = \omoProjectsResolveContext((int)$org->getId(), $holonId, false);
        if (empty($context['status'])) throw new \DomainException('Project context unavailable.');
        $context['freshPermissions'] = true;
        return $context;
    }

    private static function destination(Organization $org, int $holonId): array
    {
        $context = self::context($org, $holonId);
        if (!$holonId && $context['rootHolon']) throw new \DomainException('Choose the structural root ID for organization projects.');
        if (!\omoProjectsCanCreateContext($context)) throw new \DomainException('Project creation not permitted in this destination.');
        $context['freshPermissions'] = true;
        return $context;
    }

    private static function project(Organization $org, array $grant, int $id): Project
    {
        $project = McpContent::accessibleObject($org, McpContent::context($grant, $org, null), 'projects', $id);
        if (!$project instanceof Project || (int)$project->get('IDorganization') !== (int)$org->getId()) throw new \DomainException('Project unavailable.');
        $project->load($id, true);
        self::context($org, (int)$project->get('IDholon'));
        return $project;
    }

    private static function editable(Project $project, Organization $org): bool
    {
        return (bool)$project->get('active') && $project->get('project_kind') === Project::KIND_STANDARD
            && !in_array($project->get('proposal_status'), [Project::PROPOSAL_PENDING, Project::PROPOSAL_REFUSED], true)
            && \omoProjectsCanManageProject($project, self::context($org, (int)$project->get('IDholon')));
    }

    public static function spaces(array $grant, array $args): array
    {
        $org = self::organization($grant); $cursor = $args['after_id'] ?? 0; $complete = false; $items = [];
        if (($args['kind'] ?? 'organization') === 'organization') {
            $id = (int)($org->getEnabledStructuralRootHolon()?->getId() ?? 0);
            try { self::destination($org, $id); $items[] = ['holon_id' => $id, 'name' => (string)$org->get('name')]; } catch (\DomainException $error) { }
            $complete = true;
        } else {
            $limit = $args['limit'] ?? 20; $scanned = 0;
            while (count($items) < $limit && $scanned < 1000) {
                $rows = self::fetchAll('SELECT h.id, h.name FROM holon h LEFT JOIN holon root ON root.id = h.IDholon_org
                    WHERE COALESCE(NULLIF(h.IDorganization, 0), root.IDorganization) = :oid
                    AND h.active = 1 AND h.visible = 1 AND h.id > :cursor ORDER BY h.id ASC LIMIT 100', ['oid' => (int)$org->getId(), 'cursor' => $cursor]);
                if ($rows === false) throw new \RuntimeException('Project spaces query failed.');
                if (!$rows) { $complete = true; break; }
                foreach ($rows as $row) {
                    $cursor = (int)$row['id']; $scanned++;
                    try { self::destination($org, $cursor); } catch (\DomainException $error) { continue; }
                    $items[] = ['holon_id' => $cursor, 'name' => trim(strip_tags((string)$row['name']))];
                    if (count($items) === $limit) break;
                }
                if (count($items) < $limit && count($rows) < 100) { $complete = true; break; }
            }
        }
        $config = \omoProjectsGetDisplayConfig((int)$org->getId()); $statuses = [];
        foreach (Project::getStatusCatalog() as $status => $value) $statuses[] = ['value' => $status,
            'label' => Project::getOrganizationStatusLabel((int)$org->getId(), $status), 'displayed' => in_array($status, $config['enabledStatuses'], true)];
        return ['organization_id' => (int)$org->getId(), 'items' => $items, 'next_after_id' => $complete ? null : $cursor,
            'complete' => $complete, 'write_authorized' => \omoMcpCanWriteProjects($grant), 'required_scope' => \OMO_MCP_PROJECT_SCOPE,
            'statuses' => $statuses];
    }

    public static function read(array $grant, int $id): array
    {
        $org = self::organization($grant);
        return self::response($org, self::project($org, $grant, $id));
    }

    private static function response(Organization $org, Project $project): array
    {
        $data = ['project_id' => (int)$project->getId(), 'organization_id' => (int)$org->getId(), 'holon_id' => (int)$project->get('IDholon'),
            'parent_id' => (int)$project->get('IDproject_parent'), 'responsible_user_id' => $project->get('IDuser') ? (int)$project->get('IDuser') : null];
        foreach (self::FIELDS as $field) {
            $value = $project->get($field);
            $data[$field] = match ($field) {
                'planned_start_date', 'planned_end_date', 'blocked_until' => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : null,
                'priority', 'importance' => $value ? (int)$value : null,
                'blocked_auto_reactivate' => (bool)$value,
                'blocked_reason' => $value === null ? null : (string)$value,
                default => (string)$value,
            };
        }
        // Include native lifecycle fields in the revision so UI edits within the same second are detected.
        $revision = $data;
        foreach (['active', 'project_kind', 'proposal_status', 'capture_mode', 'closed_at', 'archived_at', 'updated_at'] as $field) {
            $value = $project->get($field); $revision[$field] = $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value;
        }
        $data['version'] = hash('sha256', json_encode($revision, JSON_THROW_ON_ERROR));
        $data['can_edit'] = self::editable($project, $org);
        $data['status_label'] = Project::getOrganizationStatusLabel((int)$org->getId(), $data['status']);
        $data['calculated_importance'] = (float)$project->get('calculated_importance');
        $data['url'] = McpContent::sourceUrl((int)$org->getId(), 'projects', (int)$project->getId(), (int)$project->get('IDholon'));
        return ['project' => $data];
    }

    public static function write(array $grant, array $args, bool $create): array
    {
        $args = \omoApiProjectValidate($create ? 'omo_create_project' : 'omo_update_project', $args);
        if (!\omoMcpCanWriteProjects($grant) || !McpOauthGrant::hasActiveScopeAuthorization($grant, \OMO_MCP_PROJECT_SCOPE)) {
            throw new \DomainException('Reconnect and authorize projects:write before modifying projects.');
        }
        $org = self::organization($grant); $oid = (int)$org->getId(); $uid = (int)$grant['IDuser'];
        $payload = $args; unset($payload['request_key']); ksort($payload);
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)); $operation = $create ? 'create' : 'update';
        $bindings = ['uid' => $uid, 'oid' => $oid, 'key_hash' => hash('sha256', $args['request_key'])];
        $pdo = self::getPdo(); $notify = false; $previousStatus = '';
        try {
            $pdo->beginTransaction();
            // Lock the organization to serialize parent graph edits and the native importance calculation.
            if (!self::fetchRow('SELECT id FROM organization WHERE id = :oid FOR UPDATE', ['oid' => $oid])) throw new \DomainException('Organization unavailable.');
            if (!self::execute('INSERT INTO mcp_project_write (IDuser, IDorganization, operation, key_hash, payload_hash, created_at)
                VALUES (:uid, :oid, :operation, :key_hash, :payload_hash, :now) ON DUPLICATE KEY UPDATE id = id',
                $bindings + ['operation' => $operation, 'payload_hash' => $hash, 'now' => time()])) throw new \RuntimeException('Project request storage failed.');
            $row = self::fetchRow('SELECT * FROM mcp_project_write WHERE IDuser = :uid AND IDorganization = :oid AND key_hash = :key_hash FOR UPDATE', $bindings);
            if (!$row || $row['operation'] !== $operation || !hash_equals($row['payload_hash'], $hash)) throw new \DomainException('request_key already used for a different project write.');
            if ($row['completed']) {
                $project = self::project($org, $grant, (int)$row['IDproject']);
                if ($create) self::destination($org, (int)$project->get('IDholon'));
                elseif (!self::editable($project, $org)) throw new \DomainException('Previously saved project no longer editable. Do not retry with a new key.');
                $result = self::response($org, $project) + [$create ? 'created' : 'updated' => true, 'replayed' => true];
                $pdo->commit(); return $result;
            }
            if ($create) {
                self::destination($org, $args['holon_id']);
                $project = new Project();
                foreach (['IDorganization' => $oid, 'IDholon' => $args['holon_id'] ?: null, 'active' => 1,
                    'project_kind' => Project::KIND_STANDARD, 'proposal_status' => Project::PROPOSAL_NONE] as $field => $value) $project->set($field, $value);
            } else {
                if (!self::fetchRow('SELECT id FROM project WHERE id = :id AND IDorganization = :oid FOR UPDATE', ['id' => $args['project_id'], 'oid' => $oid])) throw new \DomainException('Project unavailable.');
                $project = self::project($org, $grant, $args['project_id']);
                if (!self::editable($project, $org)) throw new \DomainException('Project modification not permitted.');
                $version = self::response($org, $project)['project']['version'];
                if (!hash_equals($version, $args['expected_version'])) throw new \OmoApiProjectConflictException($version);
                $previousStatus = (string)$project->get('status');
            }
            if (array_key_exists('parent_id', $args)) {
                if ($args['parent_id']) {
                    $parent = self::project($org, $grant, $args['parent_id']);
                    if (!$parent->get('active') || $parent->get('project_kind') !== Project::KIND_STANDARD || $parent->isPrivateProposal() || !$project->canUseAsParent($parent)) throw new \DomainException('Invalid project parent.');
                }
                $project->set('IDproject_parent', $args['parent_id'] ?: null);
            }
            if (array_key_exists('responsible_user_id', $args)) {
                if ($args['responsible_user_id']) McpBrowse::requireUser($org, McpContent::context($grant, $org, null), $args['responsible_user_id']);
                $project->set('IDuser', $args['responsible_user_id']);
            }
            foreach (self::FIELDS as $field) if (array_key_exists($field, $args)) $project->set($field,
                $field === 'description' ? PropertyFormat::formattedTextToHtml($args[$field], 'text') : $args[$field]);
            if ($project->get('status') !== Project::STATUS_BLOCKED) {
                foreach (['blocked_reason', 'blocked_until', 'blocked_auto_reactivate', 'blocked_reactivate_status'] as $field) {
                    if (array_key_exists($field, $args)) throw new \DomainException('Blocking fields require blocked status.');
                }
            }
            if ($project->get('status') === Project::STATUS_SOMEDAY && (!empty($args['planned_start_date']) || !empty($args['planned_end_date']))) {
                throw new \DomainException('Someday projects cannot have planning dates. Choose a scheduled status.');
            }
            $saved = $project->save();
            if (empty($saved['status'])) throw new \DomainException('Native project save rejected: ' . ($saved['errorCode'] ?? $saved['text'] ?? 'invalid project'));
            $project->load($project->getId(), true);
            $start = $project->get('planned_start_date'); $end = $project->get('planned_end_date');
            if ($start && $end && $end < $start) throw new \DomainException('Project end must not precede start, including the native parent/status dates.');
            if (!self::execute('UPDATE mcp_project_write SET IDproject = :project, completed = 1 WHERE id = :id', ['project' => (int)$project->getId(), 'id' => (int)$row['id']])) throw new \RuntimeException('Project request completion failed.');
            $result = self::response($org, $project) + [$create ? 'created' : 'updated' => true, 'replayed' => false];
            $notify = !$create && $previousStatus !== $project->get('status');
            $pdo->commit();
        } catch (\Throwable $error) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $error; }
        if ($notify) \omoProjectsDispatchStatusChangeNotification($project, $previousStatus, $uid);
        return $result;
    }
}
