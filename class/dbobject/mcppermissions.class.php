<?php
namespace dbObject;

/** Read-only permission explanations shared by REST and MCP. */
final class McpPermissions
{
    private const MEMBER_BATCH = 20;

    public const INSTRUCTIONS = 'Use omo_permission_catalog to resolve permission keys, omo_list_records module team to resolve people, and module structure to resolve spaces. Call omo_list_permissions with user_id, holon_id, permission_key, or a combination. holon_id=0 means the organization space; the structural root has its own ID. Default mode normal excludes extended authorities and temporary administrator overrides, even when active. Use extended or all ONLY when the user explicitly asks about exceptional rights. Extended grants describe available authorities requiring activation, not currently active access. Group rows by person, permission, assigned_holon and space; deduplicate people/spaces when counting. source_holon identifies the configuration or inherited template; assigned_holon identifies the effective membership that carries it (which can be a circle or group, not necessarily a direct role). default_membership means an unconfigured right open to organization members; fallback_permission_key explains a fallback to another right. Collective rights are not individual grants. No administrator bypass is included. A general edit/delete right does not guarantee access to each object: visibility, ownership, application availability and object-specific rules still apply. Follow next_cursor until null with identical filters, even after an empty page. complete means the visible enumeration is exhausted, not that hidden members or spaces do not exist. Qualify exclusive claims as limited to visible information. Data is live, not a historical snapshot. This operation never changes rights or activates a mode.';

    private static function definitions(Organization $organization): array
    {
        $entries = [];
        foreach (Permission::getEditorCatalog($organization->getLexicon()) as $entry) {
            $entries[$entry['key']] = ['permission_key' => $entry['key'], 'title' => $entry['title'],
                'description' => $entry['description'], 'group' => $entry['group'], 'is_contextual' => (bool)$entry['isContextual']];
        }
        ksort($entries, SORT_STRING);
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
            'instructions' => self::INSTRUCTIONS];
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

    private static function page(array $grant, array $args): array
    {
        $organization = McpStructure::organization($grant);
        $oid = (int)$organization->getId();
        $enabled = McpContent::enabledModules($organization, (int)$grant['IDuser']);
        if (!in_array('team', $enabled, true)) throw new \DomainException('Team module unavailable.');
        $root = McpStructure::root($organization, (int)$grant['IDuser']);
        $context = McpContent::context($grant, $organization, null);
        $definitions = self::definitions($organization);
        if (isset($args['permission_key'])) {
            if (!isset($definitions[$args['permission_key']])) throw new \InvalidArgumentException('Unknown permission_key. Use omo_permission_catalog.');
            $definitions = [$args['permission_key'] => $definitions[$args['permission_key']]];
        }
        $mode = $args['mode'] ?? 'normal';
        $holonCache = [];
        // Never use the subject's session or access gates to authorize the viewer.
        if (isset($args['holon_id']) && $args['holon_id'] > 0) {
            McpStructure::requireHolon($organization, $root, $args['holon_id']);
        }
        if (isset($args['user_id'])) McpBrowse::requireUser($organization, $context, $args['user_id']);
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
                if ($uid === $firstUser && $offset < $startOffset) { $offset++; continue; }
                if (count($items) === $limit) { $next = $uid . ':' . $offset; break 2; }
                $items[] = $row;
                $offset++;
            }
        }
        return ['organization_id' => $oid, 'mode' => $mode,
            'filters' => (object)array_intersect_key($args, array_flip(['user_id', 'holon_id', 'permission_key'])),
            'items' => $items, 'next_cursor' => $next, 'complete' => $next === null,
            'coverage' => 'visible_members_and_spaces', 'instructions' => self::INSTRUCTIONS];
    }

    private static function memberRows(Organization $organization, Holon $root, array $member, array $definitions, string $mode, ?int $contextId, array &$holonCache): \Generator
    {
        $oid = (int)$organization->getId();
        $rows = HolonPermission::inspectUserPermissionsForOrganization($member['user_id'], $oid, array_keys($definitions), $mode);
        $seen = [];
        foreach ($rows as $row) {
            $assigned = self::holon($organization, $root, (int)$row['assignedHolonId'], $holonCache);
            $source = self::holon($organization, $root, (int)$row['sourceHolonId'], $holonCache);
            // Do not reveal grants configured on a hidden membership or hidden template.
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

    private static function holon(Organization $organization, Holon $root, int $id, array &$cache): ?array
    {
        if ($id <= 0) return null;
        if (array_key_exists($id, $cache)) return $cache[$id];
        try { $holon = McpStructure::requireHolon($organization, $root, $id); }
        catch (\DomainException $error) { return $cache[$id] = null; }
        return $cache[$id] = ['holon_id' => $id, 'name' => self::text($holon->getFullDisplayName()),
            'type' => self::text($holon->getTypeLabel()),
            'url' => \omoMcpIssuer() . '/omo/o/' . (int)$organization->getId() . '/c/' . $id];
    }

    private static function text($value): string
    {
        return trim(html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
