<?php
namespace dbObject;

/** Narrow read model for MCP: no generic object serialization or hidden fields. */
final class McpStructure
{
    public static function organization(array $grant): Organization
    {
        $organization = new Organization();
        if (!UserOrganization::hasActiveMembership((int)$grant['IDuser'], (int)$grant['IDorganization'])
            || !$organization->load((int)$grant['IDorganization']) || !$organization->canViewDetail()) {
            throw new \DomainException('Organization unavailable.');
        }
        return $organization;
    }
    public static function connectionInfo(array $grant): array
    {
        $organization = self::organization($grant);
        $user = new User();
        if (!$user->load((int)$grant['IDuser'])) throw new \DomainException('User unavailable.');
        $root = $organization->getEnabledStructuralRootHolon((int)$grant['IDuser']);
        return ['connected' => true, 'read_only' => !\omoMcpCanCreateDocuments($grant) && !\omoMcpCanSendMail($grant) && !\omoMcpCanCreateEvents($grant) && !\omoMcpCanCreateDecisions($grant) && !\omoMcpCanWriteProjects($grant), 'scope' => $grant['scope'],
            'project_writing_authorized' => \omoMcpCanWriteProjects($grant),
            'decision_creation_authorized' => \omoMcpCanCreateDecisions($grant),
            'event_creation_authorized' => \omoMcpCanCreateEvents($grant),
            'document_creation_authorized' => \omoMcpCanCreateDocuments($grant),
            'mail_sending_authorized' => \omoMcpCanSendMail($grant),
            'user' => ['id' => (int)$user->getId(), 'name' => \commonGetCurrentUserDisplayName(),
                'meeting_booking_url' => McpBrowse::meetingBookingUrl((int)$user->getId())],
            'organization' => ['id' => (int)$organization->getId(), 'name' => (string)$organization->get('name'),
                'root_holon_id' => $root ? (int)$root->getId() : null,
                'url' => \omoMcpIssuer() . '/omo/o/' . (int)$organization->getId()],
            'modules' => McpContent::enabledModules($organization, (int)$grant['IDuser']),
            'coverage' => 'Complete paginated lists, user filters, role assignments, search and record text using your current OMO permissions. Resolve names with omo_list_records module team, then use omo_get_member for roles and related-object navigation. Filter calendar by invited and date_from for upcoming meetings; structure/effective_member lists effective holons. Use omo_list_object_members for effective holon memberships and meeting/event invitations, with scoped contact details. Use omo_get_availability for title-free member and common free times, including OMO and imported calendars. Optional events:create consent permits user-requested new events in holons discovered with omo_list_event_spaces, with default or explicit invitations. Optional mail:send consent permits user-requested email to object audiences through omo_send_object_email, with direct or automatic background delivery and tracking. Optional documents:create consent permits new text documents and original file imports through omo_create_document, only in spaces allowed by OMO. Discover destinations with omo_list_document_spaces. Optional decisions:create consent permits ballots with text/date proposals through omo_create_decision; discover omo_list_decision_spaces. Dated proposals reserve tentative native calendar events. Preview and send decision audience mail separately. Optional projects:write consent permits creation and partial updates through omo_create_project and omo_update_project; discover omo_list_project_spaces and read omo_get_project before modifications. Ask for missing planning choices and blocking details. Search is bounded; use lists for full enumeration.'];
    }
    public static function root(Organization $organization, int $userId): Holon
    {
        $root = $organization->getEnabledStructuralRootHolon($userId);
        if (!$root) throw new \DomainException('Structure module unavailable.');
        return $root;
    }
    public static function requireHolon(Organization $organization, Holon $root, int $id): Holon
    {
        $holon = new Holon();
        if (!$holon->load($id) || !$holon->get('active') || !$holon->get('visible')
            || ((int)$holon->getId() !== (int)$root->getId() && (int)$holon->get('IDholon_org') !== (int)$root->getId())
            || $holon->resolveOrganizationId() !== (int)$organization->getId() || !$holon->canViewDetail()) {
            throw new \DomainException('Structure element unavailable.');
        }
        return $holon;
    }
    private static function serialize(Holon $holon, Organization $organization, Holon $root): array
    {
        $parentId = (int)$holon->get('IDholon_parent');
        $parent = null;
        if ($parentId > 0) {
            try { $parent = self::requireHolon($organization, $root, $parentId); }
            catch (\DomainException $error) { /* Do not disclose inaccessible parent identifiers. */ }
        }
        $modified = $holon->get('datemodification');
        return ['id' => (int)$holon->getId(), 'name' => (string)$holon->getDisplayName(),
            'full_name' => (string)$holon->getFullDisplayName(), 'type_id' => (int)$holon->get('IDtypeholon'),
            'type' => (string)$holon->getTypeLabel(), 'parent_id' => $parent ? (int)$parent->getId() : null,
            'organization_id' => (int)$organization->getId(),
            'updated_at' => $modified instanceof \DateTimeInterface ? $modified->format(DATE_ATOM) : null,
            'url' => \omoMcpIssuer() . '/omo/o/' . (int)$organization->getId() . '/c/' . (int)$holon->getId()];
    }
    public static function read(array $grant, int $id): array
    {
        $organization = self::organization($grant);
        $root = self::root($organization, (int)$grant['IDuser']);
        return self::serialize(self::requireHolon($organization, $root, $id), $organization, $root);
    }
    public static function list(array $grant, int $afterId, int $limit, ?int $parentId): array
    {
        $organization = self::organization($grant);
        $root = self::root($organization, (int)$grant['IDuser']);
        if ($parentId !== null) self::requireHolon($organization, $root, $parentId);
        $where = [['field' => 'id', 'op' => '>', 'value' => $afterId],
            ['field' => 'active', 'value' => 1], ['field' => 'visible', 'value' => 1]];
        if ($parentId !== null) $where[] = ['field' => 'IDholon_parent', 'value' => $parentId];
        $items = [];
        // Fill the visible page even if inconsistent legacy rows belong to a different organization.
        do {
            $holons = new ArrayHolon();
            $holons->load(['where' => $where,
                'whereAny' => [['field' => 'id', 'value' => (int)$root->getId()], ['field' => 'IDholon_org', 'value' => (int)$root->getId()]],
                'orderBy' => [['field' => 'id', 'dir' => 'ASC']], 'limit' => $limit + 1]);
            foreach ($holons as $holon) {
                $where[0]['value'] = (int)$holon->getId();
                if ($holon->resolveOrganizationId() === (int)$organization->getId() && $holon->canViewDetail()) {
                    $items[] = self::serialize($holon, $organization, $root);
                    if (count($items) > $limit) break;
                }
            }
        } while (count($holons) === $limit + 1 && count($items) <= $limit);
        $more = count($items) > $limit;
        $items = array_slice($items, 0, $limit);
        return ['organization_id' => (int)$organization->getId(), 'items' => $items,
            'next_after_id' => $more ? $items[count($items) - 1]['id'] : null];
    }
}
