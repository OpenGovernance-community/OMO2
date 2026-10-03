<?php
namespace dbObject;

/** Complete keyset enumeration. Candidate queries never replace the existing object access gates. */
final class McpBrowse
{
    private const BATCH = 100;
    private const SCAN_LIMIT = 500;

    /** All SQL expressions are fixed here; caller values are always bound parameters. */
    private static function definitions(): array
    {
        $org = 't.IDorganization = :organization_id';
        return [
            'structure' => ['from' => 'holon t LEFT JOIN holon root ON root.id = t.IDholon_org',
                'where' => 'COALESCE(NULLIF(t.IDorganization, 0), root.IDorganization) = :organization_id AND t.active = 1 AND t.visible = 1',
                'title' => 't.name', 'date' => 't.datecreation', 'holon' => 't.id', 'parent' => 't.IDholon_parent',
                'users' => ['member' => 'EXISTS (SELECT 1 FROM user_holon uh WHERE uh.IDholon = t.id AND uh.active = 1 AND uh.is_membership = 1 AND uh.IDuser = %s)'],
                'description' => 'Active visible roles, circles and groups, including the organization root. User filter: active assignment.'],
            'team' => ['from' => 'user t INNER JOIN user_organization membership ON membership.IDuser = t.id',
                'where' => 'membership.IDorganization = :organization_id AND membership.active = 1 AND t.active = 1',
                'title' => "CONCAT_WS(' ', t.firstname, t.lastname, COALESCE(NULLIF(membership.username, ''), t.username))",
                'date' => 'membership.datecreation', 'users' => ['member' => 't.id = %s'],
                'description' => 'Active readable organization members. record_id is user_id; query matches names and usernames.'],
            'calendar' => ['from' => 'event t', 'where' => $org . " AND t.active = 1 AND t.status <> 'cancelled'",
                'title' => 't.title', 'date' => 't.start_at', 'holon' => 't.IDholon', 'status' => 't.status',
                'statuses' => array_values(array_diff(array_keys(Event::getStatusCatalog()), [Event::STATUS_CANCELLED])),
                'users' => ['author' => 't.IDuser = %s'],
                'description' => 'Active non-cancelled events. Dates filter event start, not creation. User filter: event creator, not invitees.'],
            'documents' => ['from' => 'document t', 'where' => $org . " AND t.active = 1 AND COALESCE(t.documenttype, '') <> 'pv'",
                'title' => 't.title', 'date' => 't.datecreation', 'holon' => 't.IDholon', 'parent' => 't.IDdocument_parent',
                'users' => ['owner' => 't.IDuser = %s', 'author' => 't.IDusercreation = %s'],
                'description' => 'Active readable documents and folders, excluding meeting minutes. User filter: owner or creator.'],
            'pv' => ['from' => 'document t', 'where' => $org . " AND t.active = 1 AND t.documenttype = 'pv'",
                'title' => 't.title', 'date' => 't.datecreation', 'holon' => 't.IDholon', 'parent' => 't.IDdocument_parent',
                'status' => 't.pvstage', 'statuses' => array_keys(Document::getPvStageOptions()),
                'users' => ['owner' => 't.IDuser = %s', 'author' => 't.IDusercreation = %s',
                    'editor' => '%s IN (t.IDuser_pv_editor, t.IDuser_pv_official_editor)'],
                'description' => 'Active readable meeting minutes. User filter: owner, creator or editor; not point authors or invitees. Dates are document creation dates.'],
            'rules' => ['from' => 'rule t LEFT JOIN authority a ON a.id = t.IDauthority LEFT JOIN holon h ON h.id = COALESCE(NULLIF(t.IDholon, 0), a.IDholon) LEFT JOIN holon root ON root.id = h.IDholon_org',
                'where' => 'COALESCE(NULLIF(t.IDorganization, 0), NULLIF(h.IDorganization, 0), root.IDorganization) = :organization_id',
                'title' => 't.title', 'date' => 't.created_at', 'holon' => 'h.id',
                'users' => ['author' => 't.IDuser_creation = %s'],
                'description' => 'Readable rules, including expired rules. With context_holon_id, list rules applicable to that context. User filter: creator.'],
            'decision' => ['from' => 'decision_process t', 'where' => $org, 'title' => 't.title',
                'date' => 't.created_at', 'holon' => 't.IDholon', 'status' => 't.status',
                'statuses' => array_keys(DecisionProcess::getStatusCatalog()), 'users' => ['owner' => 't.IDuser = %s'],
                'description' => 'Readable decisions, including archived decisions. User filter: owner only; does not reveal voter identities.'],
            'projects' => ['from' => 'project t', 'where' => $org . " AND t.active = 1 AND t.project_kind = 'standard'",
                'title' => 't.title', 'date' => 't.created_at', 'holon' => 't.IDholon', 'parent' => 't.IDproject_parent',
                'status' => 't.status', 'statuses' => array_keys(Project::getStatusCatalog()),
                'users' => ['responsible' => 't.IDuser = %s',
                    'assignee' => 'EXISTS (SELECT 1 FROM project_user pu WHERE pu.IDproject = t.id AND pu.active = 1 AND pu.IDuser = %s)'],
                'description' => 'Active standard projects and their tasks/subprojects at every depth. parent_id lists direct children; 0 lists roots. User filter: responsible or explicitly assigned person.'],
            'stats' => ['from' => 'stat_indicator t', 'where' => $org . ' AND t.active = 1',
                'title' => 't.name', 'date' => 't.created_at', 'holon' => 't.IDholon',
                'users' => ['author' => 't.IDuser = %s', 'responsible' => 't.IDuser_responsible = %s'],
                'description' => 'Active readable indicators. User filter: creator or responsible person. Read records for measurements.'],
            'processus' => ['from' => 'process t INNER JOIN project template ON template.id = t.IDproject_template_root AND template.IDorganization = t.IDorganization',
                'where' => $org . ' AND t.active = 1 AND template.active = 1', 'title' => 'template.title',
                'date' => 't.created_at', 'holon' => 'template.IDholon', 'status' => 't.status',
                'statuses' => [Checklist::STATUS_DRAFT, Checklist::STATUS_PUBLISHED, Checklist::STATUS_RETIRED],
                'users' => ['responsible' => 't.IDuser_responsible = %s'],
                'description' => 'Active readable process definitions. User filter: responsible person.'],
            'activities' => ['from' => 'recurring_task t', 'where' => $org . ' AND t.active = 1',
                'title' => 't.title', 'date' => 't.created_at', 'holon' => 't.IDholon',
                'users' => ['responsible' => 't.IDuser_responsible = %s'],
                'description' => 'Active recurring activity definitions. User filter: responsible person; individual executions are not separate records.'],
            'faq' => ['from' => 'faq t LEFT JOIN holon h ON h.id = t.IDholon LEFT JOIN holon root ON root.id = h.IDholon_org',
                'where' => 't.isactive = 1 AND (COALESCE(NULLIF(t.IDorganization, 0), NULLIF(h.IDorganization, 0), root.IDorganization) = :organization_id OR t.IDparcours > 0 OR (COALESCE(t.IDorganization, 0) = 0 AND COALESCE(t.IDholon, 0) = 0))',
                'title' => 't.question', 'date' => 't.created', 'holon' => 't.IDholon',
                'users' => ['requester' => 't.request_user_id = %s'],
                'description' => 'Active readable organization, contextual, generic and available tutorial FAQs. User filter: original requester.'],
            'tutorials' => ['from' => 'parcours t', 'where' => 't.isarchived = 0', 'title' => 't.title', 'date' => 't.datecreation',
                'users' => [], 'description' => 'All tutorial courses available to the authenticated viewer. User filtering is unsupported; this does not list another person\'s progress.'],
        ];
    }

    public static function catalog(array $grant): array
    {
        $organization = McpStructure::organization($grant);
        $modules = [];
        foreach (McpContent::enabledModules($organization, (int)$grant['IDuser']) as $module) {
            $definition = self::definitions()[$module];
            $filters = ['query', 'date_from', 'date_to'];
            if ($definition['users']) array_push($filters, 'user_id', 'user_relation');
            if (isset($definition['holon']) || $module === 'team') $filters[] = 'context_holon_id';
            if (isset($definition['parent'])) $filters[] = 'parent_id';
            if (isset($definition['status'])) $filters[] = 'status';
            $modules[] = ['module' => $module, 'description' => $definition['description'], 'filters' => $filters,
                'user_relations' => array_keys($definition['users']), 'statuses' => $definition['statuses'] ?? [],
                'date_field' => substr($definition['date'], strpos($definition['date'], '.') + 1)];
        }
        return ['organization_id' => (int)$organization->getId(), 'modules' => $modules,
            'document_creation' => ['authorized' => \omoMcpCanCreateDocuments($grant), 'scope' => \OMO_MCP_CREATE_SCOPE,
                'discover_tool' => 'omo_list_document_spaces', 'create_tool' => 'omo_create_document',
                'default_visibility' => 'self', 'requires_omo_permission' => 'CAN_CREATE_DOCUMENT'],
            'instructions' => 'Use omo_list_records with module team to resolve a name to user_id. Use user_id and optional user_relation on lists; filtering never changes viewer permissions. query is a literal title/name substring, not full-text search. Dates are inclusive calendar days in stored field values. List projects without parent_id for every level, or parent_id=0 for roots. Follow next_after_id even after an empty page until null. Keep filters identical between pages. Lists are live, not a frozen snapshot; only claim completeness when complete=true. Use omo_list_assignments for roles held by a person. Read details with omo_read_record using the returned context_holon_id.'];
    }

    private static function requireUser(Organization $organization, array $context, int $userId): User
    {
        $user = new User();
        if (!$user->load($userId) || !$user->getOrganizationMembership((int)$organization->getId())
            || !$user->canViewDetail()) throw new \DomainException('Member unavailable.');
        if ($userId !== $context['userId'] && !McpContent::accessibleObject($organization, $context, 'team', $userId)) {
            throw new \DomainException('Member unavailable.');
        }
        return $user;
    }

    public static function records(array $grant, array $args): array
    {
        $organization = McpStructure::organization($grant);
        $module = $args['module'];
        if (!in_array($module, McpContent::enabledModules($organization, (int)$grant['IDuser']), true)) {
            throw new \DomainException('Module unavailable.');
        }
        $definition = self::definitions()[$module];
        $context = McpContent::context($grant, $organization, $args['context_holon_id'] ?? null);
        $params = ['organization_id' => (int)$organization->getId()];
        $where = [$definition['where']];
        if ($module === 'tutorials') {
            unset($params['organization_id']);
            $rows = Parcours::fetchForOrganizationWithProgress((int)$organization->getId(), (int)$grant['IDuser'], true, false);
            $ids = array_map('intval', array_column($rows, 'id'));
            $where[] = 't.id IN (' . ($ids ? implode(',', $ids) : '0') . ')';
        }
        if (isset($args['user_id'])) {
            self::requireUser($organization, $context, $args['user_id']);
            $relations = isset($args['user_relation']) ? [$args['user_relation']] : array_keys($definition['users']);
            if (!$relations) throw new \DomainException('User filter unavailable for this module. Use omo_catalog.');
            $matches = [];
            foreach ($relations as $index => $relation) {
                if (!isset($definition['users'][$relation])) throw new \DomainException('User relation unavailable for this module. Use omo_catalog.');
                $key = 'user_' . $index;
                $params[$key] = $args['user_id'];
                $matches[] = sprintf($definition['users'][$relation], ':' . $key);
            }
            $where[] = '(' . implode(' OR ', $matches) . ')';
        }
        if (isset($args['query'])) {
            $where[] = 'LOCATE(:query, LOWER(COALESCE(' . $definition['title'] . ", ''))) > 0";
            $params['query'] = mb_strtolower($args['query'], 'UTF-8');
        }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $filter => $operator) {
            if (!isset($args[$filter])) continue;
            $where[] = 'DATE(' . $definition['date'] . ') ' . $operator . ' :' . $filter;
            $params[$filter] = $args[$filter];
        }
        if (isset($args['status'])) {
            if (!in_array($args['status'], $definition['statuses'] ?? [], true)) throw new \DomainException('Status unavailable for this module. Use omo_catalog.');
            $where[] = $definition['status'] . ' = :status'; $params['status'] = $args['status'];
        }
        if (isset($args['parent_id'])) {
            if (!isset($definition['parent'])) throw new \DomainException('Parent filter unavailable for this module.');
            $where[] = 'COALESCE(' . $definition['parent'] . ', 0) = :parent_id'; $params['parent_id'] = $args['parent_id'];
        }
        if (isset($args['context_holon_id'])) {
            if ($module === 'team') {
                $where[] = 'EXISTS (SELECT 1 FROM user_holon uh WHERE uh.IDuser = t.id AND uh.active = 1 AND uh.is_membership = 1 AND uh.IDholon = :holon_id)';
                $params['holon_id'] = $args['context_holon_id'];
            } elseif (in_array($module, ['rules', 'faq'], true)) {
                // Their existing access gate applies contextual/inherited rules, not a raw FK filter.
            } elseif (isset($definition['holon'])) {
                $where[] = $definition['holon'] . ' = :holon_id'; $params['holon_id'] = $args['context_holon_id'];
            } else { throw new \DomainException('Context filter unavailable for this module.'); }
        }
        $cursor = $args['after_id'] ?? 0; $limit = $args['limit'] ?? 20;
        $items = []; $complete = false; $scanned = 0;
        while ($scanned < self::SCAN_LIMIT && count($items) < $limit) {
            $params['after_id'] = $cursor;
            $contextSelect = $definition['holon'] ?? 'NULL';
            $rows = DbObject::fetchAll('SELECT DISTINCT t.id, ' . $contextSelect . ' AS context_id FROM ' . $definition['from']
                . ' WHERE ' . implode(' AND ', $where) . ' AND t.id > :after_id ORDER BY t.id ASC LIMIT ' . self::BATCH, $params);
            if ($rows === false) throw new \RuntimeException('MCP list query failed.');
            if (!$rows) { $complete = true; break; }
            foreach ($rows as $row) {
                $cursor = (int)$row['id']; $scanned++;
                $recordContext = $context;
                if (!isset($args['context_holon_id']) && in_array($module, ['rules', 'faq'], true) && (int)$row['context_id'] > 0) {
                    try { $recordContext = McpContent::context($grant, $organization, (int)$row['context_id']); }
                    catch (\DomainException $error) { continue; }
                }
                $object = McpContent::accessibleObject($organization, $recordContext, $module, $cursor);
                if ($object) $items[] = self::summary($organization, $recordContext, $module, $object);
                if (count($items) === $limit) break;
            }
            if (count($items) < $limit && count($rows) < self::BATCH) { $complete = true; break; }
        }
        return ['organization_id' => (int)$organization->getId(), 'module' => $module,
            'filters' => array_diff_key($args, array_flip(['module', 'after_id', 'limit'])),
            'items' => $items, 'next_after_id' => $complete ? null : $cursor, 'complete' => $complete,
            'coverage' => $definition['description'] . ' No search-result cap; follow next_after_id until null. Permissions are those of the authenticated viewer.'];
    }

    public static function summary(Organization $organization, array $context, string $module, DbObject $object): array
    {
        $title = match ($module) {
            'structure' => $object->getDisplayName(), 'team' => $object->getScopedDisplayName((int)$organization->getId()),
            'stats' => $object->get('name'), 'faq' => $object->get('question'),
            'processus' => $object->getTemplateRoot()?->get('title'), default => $object->get('title'),
        };
        $result = ['module' => $module, 'record_id' => (int)$object->getId(), 'title' => self::text($title),
            'context_holon_id' => $context['currentHolonId'] ?: null,
            'url' => McpContent::sourceUrl((int)$organization->getId(), $module, (int)$object->getId(), $context['currentHolonId'])];
        $definition = self::definitions()[$module];
        if (isset($definition['holon'])) {
            $result['holon_id'] = match ($module) {
                'structure' => (int)$object->getId(),
                'rules', 'processus' => $object->getHolon() ? (int)$object->getHolon()->getId() : null,
                default => (int)$object->get('IDholon') ?: null,
            };
        }
        $dateField = substr($definition['date'], strpos($definition['date'], '.') + 1);
        $date = $module === 'team' ? $object->getOrganizationMembership((int)$organization->getId())?->get($dateField) : $object->get($dateField);
        $result['date_field'] = $dateField;
        $result['date'] = $date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : $date;
        foreach (['parent' => 'parent_id', 'status' => 'status'] as $key => $output) {
            if (!isset($definition[$key])) continue;
            $field = substr($definition[$key], 2);
            $result[$output] = $key === 'parent' ? ((int)$object->get($field) ?: null) : (string)$object->get($field);
        }
        $fields = match ($module) {
            'structure' => ['IDtypeholon'], 'documents', 'pv' => ['documenttype', 'IDevent'],
            'calendar' => ['start_at', 'end_at', 'timezone', 'IDproject'],
            'projects' => ['planned_start_date', 'planned_end_date', 'project_size'],
            'rules' => ['review_date', 'expiration_date', 'scope'],
            'stats' => ['measurement_frequency'], 'activities' => ['frequency', 'schedule'], default => [],
        };
        foreach ($fields as $field) {
            $value = $object->get($field);
            $result[$field] = $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value;
        }
        return $result;
    }
    private static function text($value): string
    {
        return trim(html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public static function assignments(array $grant, array $args): array
    {
        $organization = McpStructure::organization($grant);
        $context = McpContent::context($grant, $organization, $args['holon_id'] ?? null);
        if (!in_array('structure', McpContent::enabledModules($organization, (int)$grant['IDuser']), true)) {
            throw new \DomainException('Structure unavailable.');
        }
        if (isset($args['user_id'])) self::requireUser($organization, $context, $args['user_id']);
        $where = ['uh.active = 1', 'uh.is_membership = 1', 'h.active = 1', 'h.visible = 1', 'COALESCE(NULLIF(h.IDorganization, 0), root.IDorganization) = :organization_id'];
        $params = ['organization_id' => (int)$organization->getId()];
        foreach (['user_id' => 'uh.IDuser', 'holon_id' => 'uh.IDholon'] as $key => $field) {
            if (isset($args[$key])) { $where[] = $field . ' = :' . $key; $params[$key] = $args[$key]; }
        }
        $cursor = $args['after_id'] ?? 0; $limit = $args['limit'] ?? 20; $scanned = 0; $items = []; $complete = false;
        while ($scanned < self::SCAN_LIMIT && count($items) < $limit) {
            $params['after_id'] = $cursor;
            $rows = UserHolon::fetchAll('SELECT uh.id, uh.IDuser, uh.IDholon FROM user_holon uh INNER JOIN holon h ON h.id = uh.IDholon
                LEFT JOIN holon root ON root.id = h.IDholon_org WHERE ' . implode(' AND ', $where)
                . ' AND uh.id > :after_id ORDER BY uh.id ASC LIMIT ' . self::BATCH, $params);
            if ($rows === false) throw new \RuntimeException('MCP assignment query failed.');
            if (!$rows) { $complete = true; break; }
            foreach ($rows as $row) {
                $cursor = (int)$row['id']; $scanned++;
                $holon = McpContent::accessibleObject($organization, $context, 'structure', (int)$row['IDholon']);
                if (!$holon) continue;
                try { $user = self::requireUser($organization, $context, (int)$row['IDuser']); }
                catch (\DomainException $error) { continue; }
                $link = new UserHolon();
                if (!$link->load($cursor)) continue;
                $items[] = ['assignment_id' => $cursor, 'user_id' => (int)$user->getId(),
                    'user_name' => self::text($user->getScopedDisplayName((int)$organization->getId())),
                    'holon_id' => (int)$holon->getId(), 'holon_name' => self::text($holon->getDisplayName()),
                    'membership' => (bool)$link->get('is_membership'), 'focus' => self::text($link->get('focus')),
                    'url' => McpContent::sourceUrl((int)$organization->getId(), 'structure', (int)$holon->getId(), $context['currentHolonId'])];
                if (count($items) === $limit) break;
            }
            if (count($items) < $limit && count($rows) < self::BATCH) { $complete = true; break; }
        }
        return ['organization_id' => (int)$organization->getId(), 'items' => $items,
            'next_after_id' => $complete ? null : $cursor, 'complete' => $complete,
            'coverage' => 'Active direct assignments to readable holons for readable organization members; inherited memberships are not expanded.'];
    }
}
