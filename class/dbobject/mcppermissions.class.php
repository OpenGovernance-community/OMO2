<?php
namespace dbObject;

/** Read-only permission explanations shared by REST and MCP. */
final class McpPermissions
{
    private const MEMBER_BATCH = 20;

    public const INSTRUCTIONS = 'Use omo_permission_catalog to resolve permission keys, omo_list_records module team to resolve people, and module structure to resolve spaces. Call omo_list_permissions with user_id, holon_id, permission_key, or a combination. holon_id=0 means the organization space; the structural root has its own ID. Default mode normal excludes extended authorities and temporary administrator overrides, even when active. Use extended or all ONLY when the user explicitly asks about exceptional rights. Extended grants describe available authorities requiring activation, not currently active access. Group rows by person, permission, assigned_holon and space; deduplicate people/spaces when counting. source_holon identifies the configuration or inherited template; assigned_holon identifies the effective membership that carries it (which can be a circle or group, not necessarily a direct role). default_membership means an unconfigured right open to organization members; fallback_permission_key explains a fallback to another right. Collective rights are not individual grants. No administrator bypass is included. A general edit/delete right does not guarantee access to each object: visibility, ownership, application availability and object-specific rules still apply. Follow next_cursor until null with identical filters, even after an empty page. complete means the visible enumeration is exhausted, not that hidden members or spaces do not exist. Qualify exclusive claims as limited to visible information. Data is live, not a historical snapshot. This operation never changes rights or activates a mode.';

    private static function definitions(Organization $organization, ?string $permissionKey = null): array
    {
        $entries = [];
        foreach (Permission::getEditorCatalog($organization->getLexicon()) as $entry) {
            $entries[$entry['key']] = ['permission_key' => $entry['key'], 'title' => $entry['title'],
                'description' => $entry['description'], 'group' => $entry['group'], 'is_contextual' => (bool)$entry['isContextual']];
        }
        ksort($entries, SORT_STRING);
        if ($permissionKey !== null) {
            if (!isset($entries[$permissionKey])) throw new \InvalidArgumentException('Unknown permission_key. Use omo_permission_catalog.');
            return [$permissionKey => $entries[$permissionKey]];
        }
        return $entries;
    }

    public static function catalog(array $grant, array $args): array
    {
        $organization = McpStructure::organization($grant);
        $entries = array_values(self::definitions($organization));
        if (isset($args['query'])) {
            $query = mb_strtolower($args['query'], 'UTF-8');
            $entries = array_values(array_filter($entries, static fn(array $entry): bool =>
                str_contains(mb_strtolower($entry['permission_key'] . ' ' . $entry['title'] . ' ' . $entry['description'], 'UTF-8'), $query)));
        }
        $offset = $args['offset'] ?? 0;
        $items = array_slice($entries, $offset, $args['limit'] ?? 50);
        $next = $offset + count($items);
        return ['organization_id' => (int)$organization->getId(), 'items' => $items,
            'next_offset' => $next < count($entries) ? $next : null, 'complete' => $next >= count($entries),
            'instructions' => \omoApiAgentPermissionInstructions() . ' ' . self::INSTRUCTIONS];
    }

    public static function permissions(array $grant, array $args): array
    {
        $memoized = DbObject::isReadOnlyMemoizationEnabled();
        if (!$memoized) DbObject::enableReadOnlyMemoization();
        try {
            return self::page($grant, $args);
        } finally {
            if (!$memoized) DbObject::enableReadOnlyMemoization(false);
        }
    }

    public static function holonPermissions(array $grant, array $args): array
    {
        $memoized = DbObject::isReadOnlyMemoizationEnabled();
        if (!$memoized) DbObject::enableReadOnlyMemoization();
        try {
            $organization = McpStructure::organization($grant);
            $oid = (int)$organization->getId();
            $root = McpStructure::root($organization, (int)$grant['IDuser']);
            $targetId = $args['holon_id'];
            if ($targetId > 0) McpStructure::requireHolon($organization, $root, $targetId);
            $cache = [];
            $space = $targetId > 0 ? self::holon($organization, $root, $targetId, $cache)
                : ['holon_id' => 0, 'name' => self::text($organization->get('name')), 'type' => 'organization',
                    'url' => \omoMcpIssuer() . '/omo/o/' . $oid];
            $definitions = self::definitions($organization, $args['permission_key'] ?? null);
            $mode = $args['mode'] ?? 'normal';
            $rows = HolonPermission::inspectPermissionsForHolonContext($oid, $targetId, array_keys($definitions), $mode);
            $items = []; $offset = $args['offset'] ?? 0; $position = 0; $next = null;
            foreach ($rows as $row) {
                $assigned = self::holon($organization, $root, (int)$row['assignedHolonId'], $cache);
                $source = self::holon($organization, $root, (int)$row['sourceHolonId'], $cache,
                    $row['sourceHolonId'] !== $row['assignedHolonId']);
                if (($row['assignedHolonId'] && $assigned === null) || ($row['sourceHolonId'] && $source === null)) continue;
                if ($position++ < $offset) continue;
                if (count($items) === ($args['limit'] ?? 50)) { $next = $position - 1; break; }
                $kind = $row['isExtended'] ? 'extended' : 'normal';
                $items[] = ['permission' => $definitions[$row['permissionKey']], 'kind' => $kind,
                    'requires_activation' => (bool)$row['isExtended'], 'origin' => $row['origin'], 'member_type' => $row['memberType'],
                    'assigned_holon' => $assigned, 'source_holon' => $source, 'range' => $row['range'],
                    'fallback_permission_key' => $row['fallbackPermissionKey'],
                    'holders' => ['tool' => 'omo_list_permissions', 'arguments' => ['holon_id' => $targetId,
                        'permission_key' => $row['permissionKey'], 'assigned_holon_id' => (int)$row['assignedHolonId'],
                        'member_type' => $row['memberType'], 'mode' => $kind]]];
            }
            return ['organization_id' => $oid, 'space' => $space, 'mode' => $mode, 'items' => $items,
                'next_offset' => $next, 'complete' => $next === null, 'coverage' => 'visible_role_configurations',
                'holders' => ['tool' => 'omo_list_permissions', 'arguments' => ['holon_id' => $targetId, 'mode' => $mode]
                    + (isset($args['permission_key']) ? ['permission_key' => $args['permission_key']] : [])],
                'instructions' => \omoApiAgentPermissionInstructions() . ' This list starts from configured roles/circles/groups, independently of occupancy. Each range covers the requested space, including inherited templates and roles elsewhere in the organization. Follow next_offset with identical filters. Use holders navigation to resolve actual people with the matching membership profile; never assume every member is an administrator or that a configured role is occupied. Empty visible holder results do not prove vacancy when access is restricted. Collective rights and admin bypasses are excluded. Defaults/fallbacks are explicit. No write consent is needed.'];
        } finally {
            if (!$memoized) DbObject::enableReadOnlyMemoization(false);
        }
    }

    private static function page(array $grant, array $args): array
    {
        $organization = McpStructure::organization($grant);
        $oid = (int)$organization->getId();
        $enabled = McpContent::enabledModules($organization, (int)$grant['IDuser']);
        if (!in_array('team', $enabled, true)) throw new \DomainException('Team module unavailable.');
        $root = McpStructure::root($organization, (int)$grant['IDuser']);
        $context = McpContent::context($grant, $organization, null);
        $definitions = self::definitions($organization, $args['permission_key'] ?? null);
        $mode = $args['mode'] ?? 'normal';
        $holonCache = [];
        // Never use the subject's session or access gates to authorize the viewer.
        if (isset($args['holon_id']) && $args['holon_id'] > 0) {
            McpStructure::requireHolon($organization, $root, $args['holon_id']);
        }
        if (isset($args['user_id'])) McpBrowse::requireUser($organization, $context, $args['user_id']);
        if (!empty($args['assigned_holon_id'])) McpStructure::requireHolon($organization, $root, $args['assigned_holon_id']);
        [$firstUser, $startOffset] = array_map('intval', explode(':', $args['cursor'] ?? '0:0'));
        $params = ['organization_id' => $oid, 'first_user' => $firstUser];
        $selection = '';
        if (isset($args['user_id'])) {
            $selection = ' AND u.id = :user_id';
            $params['user_id'] = $args['user_id'];
        }
        $candidates = DbObject::fetchAll('SELECT DISTINCT u.id FROM user u
            INNER JOIN user_organization membership ON membership.IDuser = u.id
            WHERE membership.IDorganization = :organization_id AND membership.active = 1 AND u.active = 1
            AND u.id >= :first_user' . $selection . ' ORDER BY u.id ASC LIMIT ' . (self::MEMBER_BATCH + 1), $params);
        if ($candidates === false) throw new \RuntimeException('Permission member query failed.');
        $items = [];
        $next = null;
        $limit = $args['limit'] ?? 50;
        foreach ($candidates as $index => $candidate) {
            $uid = (int)$candidate['id'];
            if ($index === self::MEMBER_BATCH) { $next = $uid . ':0'; break; }
            try { $user = McpBrowse::requireUser($organization, $context, $uid); }
            catch (\DomainException $error) { continue; }
            $member = ['user_id' => $uid, 'name' => self::text($user->getScopedDisplayName($oid)),
                'url' => McpContent::sourceUrl($oid, 'team', $uid, 0, 0)];
            $offset = 0;
            foreach (self::memberRows($organization, $root, $member, $definitions, $mode, $args['holon_id'] ?? null, $holonCache) as $row) {
                if (isset($args['assigned_holon_id']) && ($row['assigned_holon']['holon_id'] ?? 0) !== $args['assigned_holon_id']) continue;
                if (isset($args['member_type']) && $row['member_type'] !== $args['member_type']) continue;
                if ($uid === $firstUser && $offset < $startOffset) { $offset++; continue; }
                if (count($items) === $limit) { $next = $uid . ':' . $offset; break 2; }
                $items[] = $row;
                $offset++;
            }
        }
        return ['organization_id' => $oid, 'mode' => $mode,
            'filters' => (object)array_intersect_key($args, array_flip(['user_id', 'holon_id', 'permission_key', 'assigned_holon_id', 'member_type'])),
            'items' => $items, 'next_cursor' => $next, 'complete' => $next === null,
            'coverage' => 'visible_members_and_spaces', 'instructions' => \omoApiAgentPermissionInstructions() . ' ' . self::INSTRUCTIONS];
    }

    private static function memberRows(Organization $organization, Holon $root, array $member, array $definitions, string $mode, ?int $contextId, array &$holonCache): \Generator
    {
        $oid = (int)$organization->getId();
        $rows = HolonPermission::inspectUserPermissionsForOrganization($member['user_id'], $oid, array_keys($definitions), $mode);
        $seen = [];
        foreach ($rows as $row) {
            $assigned = self::holon($organization, $root, (int)$row['assignedHolonId'], $holonCache);
            $source = self::holon($organization, $root, (int)$row['sourceHolonId'], $holonCache,
                $row['sourceHolonId'] !== $row['assignedHolonId']);
            // Memberships must be visible; inherited templates may be absent from the diagram.
            // Both still require active, same-organization, viewer-readable holons.
            if (($row['assignedHolonId'] && $assigned === null) || ($row['sourceHolonId'] && $source === null)) continue;
            $targetIds = HolonPermission::permissionInspectionContextIds($oid, $row, $contextId);
            // A noncontextual permission is global, not one separate grant per space.
            if (!$row['isContextual']) $targetIds = $targetIds ? [0] : [];
            foreach ($targetIds as $id) {
                $space = $id === 0
                    ? ['holon_id' => 0, 'name' => self::text($organization->get('name')), 'type' => 'organization',
                        'url' => \omoMcpIssuer() . '/omo/o/' . $oid]
                    : self::holon($organization, $root, $id, $holonCache);
                if ($space === null) continue;
                $key = implode('|', [$row['permissionKey'], $row['assignedHolonId'], $row['sourceHolonId'],
                    $row['memberType'], (int)$row['isExtended'], $row['range'], $id]);
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                yield ['member' => $member, 'permission' => $definitions[$row['permissionKey']],
                    'kind' => $row['isExtended'] ? 'extended' : 'normal',
                    'requires_activation' => (bool)$row['isExtended'], 'origin' => $row['origin'],
                    'member_type' => $row['memberType'], 'assigned_holon' => $assigned, 'source_holon' => $source,
                    'range' => $row['range'], 'space' => $space, 'fallback_permission_key' => $row['fallbackPermissionKey']];
            }
        }
    }

    private static function holon(Organization $organization, Holon $root, int $id, array &$cache, bool $templateSource = false): ?array
    {
        if ($id <= 0) return null;
        $cacheKey = ($templateSource ? 'source:' : 'visible:') . $id;
        if (array_key_exists($cacheKey, $cache)) return $cache[$cacheKey];
        try { $holon = McpStructure::requireHolon($organization, $root, $id, $templateSource); }
        catch (\DomainException $error) { return $cache[$cacheKey] = null; }
        return $cache[$cacheKey] = ['holon_id' => $id, 'name' => self::text($holon->getFullDisplayName()),
            'type' => self::text($holon->getTypeLabel()),
            'url' => \omoMcpIssuer() . '/omo/o/' . (int)$organization->getId() . '/c/' . $id];
    }

    private static function text($value): string
    {
        return trim(html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
