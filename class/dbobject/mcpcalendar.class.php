<?php
namespace dbObject;

/** Calendar availability and atomic event creation using native OMO rules. */
class McpCalendar extends DbObject
{
    public static function tableName() { return 'mcp_event_creation'; }
    public static function rules()
    {
        return [[['id', 'created_at'], 'integer'], [['completed'], 'boolean'], [['IDuser', 'IDorganization', 'IDevent'], 'fk'],
            [['key_hash', 'payload_hash'], 'string'], [['id'], 'safe']];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'IDuser' => 'Personne', 'IDorganization' => 'Organisation', 'IDevent' => 'Evenement',
            'key_hash' => 'Empreinte demande', 'payload_hash' => 'Empreinte contenu', 'created_at' => 'Creation', 'completed' => 'Termine'];
    }
    public static function attributeLength() { return ['key_hash' => 64, 'payload_hash' => 64]; }
    public function canView() { return false; }
    public function canViewDetail() { return false; }
    public function canEdit() { return false; }
    public function canDelete() { return false; }

    private static function organization(array $grant, string $module): Organization
    {
        $org = McpStructure::organization($grant);
        $user = new User();
        if (!$user->load((int)$grant['IDuser']) || !$user->get('active') || !$org->isApplicationEnabled($module, (int)$grant['IDuser'])) {
            throw new \DomainException('Calendar or member module unavailable.');
        }
        return $org;
    }

    /** Merge touching slots while keeping boundaries and offsets explicit. */
    private static function intervals(array $ranges, \DateTimeZone $zone, int $minimumMinutes = 0): array
    {
        usort($ranges, static fn($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($ranges as [$start, $end]) {
            $last = count($merged) - 1;
            if ($last >= 0 && $start <= $merged[$last][1]) {
                if ($end > $merged[$last][1]) $merged[$last][1] = $end;
            } else { $merged[] = [$start, $end]; }
        }
        $result = [];
        foreach ($merged as [$start, $end]) {
            $minutes = (int)(($end->getTimestamp() - $start->getTimestamp()) / 60);
            if ($minutes < $minimumMinutes) continue;
            $result[] = ['start_at' => \DateTimeImmutable::createFromInterface($start)->setTimezone($zone)->format(\DateTimeInterface::ATOM),
                'end_at' => \DateTimeImmutable::createFromInterface($end)->setTimezone($zone)->format(\DateTimeInterface::ATOM), 'duration_minutes' => $minutes];
        }
        return $result;
    }

    public static function availability(array $grant, array $args): array
    {
        $args = \omoMcpCalendarValidate('omo_get_availability', $args);
        $org = self::organization($grant, 'team'); $oid = (int)$org->getId();
        $context = McpContent::context($grant, $org, null); $users = [];
        // Validate the complete selection before any external refresh or private agenda lookup.
        foreach ($args['user_ids'] as $uid) $users[$uid] = McpBrowse::requireUser($org, $context, $uid);
        require_once dirname(__DIR__, 2) . '/common/user_availability.php';
        require_once dirname(__DIR__, 2) . '/common/external_calendar.php';
        $zone = new \DateTimeZone('Europe/Zurich');
        $start = \omoMcpCalendarDate($args['date_from']); $end = \omoMcpCalendarDate($args['date_to'])->modify('+1 day');
        $deadline = microtime(true) + 12; $participants = []; $items = []; $incomplete = false;
        foreach ($users as $uid => $user) {
            $refreshFailed = false;
            try { \commonExternalCalendarRefreshForAvailability($uid, $deadline); }
            catch (\Throwable $error) { $refreshFailed = true; error_log('OMO MCP availability refresh failed: ' . get_class($error)); }
            $busy = \commonUserAvailabilityLoadBusyIntervals($uid, $start, $end, $unknown);
            $unknown = $unknown || $refreshFailed; $incomplete = $incomplete || $unknown;
            $hours = MeetingProfile::isStorageAvailable() ? MeetingProfile::forUser($uid)->availabilityHours() : MeetingProfile::defaultHours();
            $free = []; $clippedBusy = [];
            foreach ($busy as [$busyStart, $busyEnd]) {
                if ($busyStart < $end && $busyEnd > $start) $clippedBusy[] = [max($start, $busyStart), min($end, $busyEnd)];
            }
            for ($day = $start; $day < $end; $day = $day->modify('+1 day')) {
                foreach (\commonUserAvailabilityBuildDay($day, $hours, $busy)['slots'] as $slot) {
                    if (!$slot['busy'] && !$slot['pause']) $free[] = [$slot['start'], $slot['end']];
                }
            }
            $participants[] = ['hours' => $hours, 'busy' => $busy];
            $items[] = ['user_id' => $uid, 'name' => trim(strip_tags($user->getScopedDisplayName($oid))), 'incomplete' => $unknown,
                'free_intervals' => self::intervals($free, $zone), 'busy_intervals' => self::intervals($clippedBusy, $zone),
                'url' => \omoMcpIssuer() . '/popup/user.php?' . http_build_query(['section' => 'availability', 'id' => $uid, 'oid' => $oid])];
        }
        $common = [];
        for ($day = $start; $day < $end; $day = $day->modify('+1 day')) {
            foreach (\commonUserAvailabilityBuildCombinedDay($day, $participants)['slots'] as $slot) {
                if (!$slot['busy'] && !$slot['pause']) $common[] = [$slot['start'], $slot['end']];
            }
        }
        return ['organization_id' => $oid, 'timezone' => $zone->getName(), 'date_from' => $args['date_from'], 'date_to' => $args['date_to'],
            'slot_minutes' => 30, 'duration_minutes' => $args['duration_minutes'] ?? 30, 'members' => $items,
            'common_free_intervals' => self::intervals($common, $zone, $args['duration_minutes'] ?? 30), 'incomplete' => $incomplete,
            'advisory' => true, 'instructions' => 'Free intervals respect profile working hours and pauses, OMO busy events and imported busy/opening calendars. Times use Europe/Zurich, matching the profile. incomplete means external data is stale, failed or outside its coverage: do not claim a slot is certainly free. Availability is advisory; no reservation was made.'];
    }

    private static function destination(Organization $org, array $grant, int $hid): Holon
    {
        $holon = new Holon();
        if ($hid <= 0 || !$org->isStructureApplicationEnabled() || !$holon->load($hid) || !$holon->get('active') || !$holon->get('visible')
            || !in_array((int)$holon->get('IDtypeholon'), [1, 2], true) || !$org->containsHolon($holon) || !$holon->canViewDetail()
            || !$holon->isAllowed('CAN_CREATE_EVENT', false, (int)$grant['IDuser'])) {
            throw new \DomainException('You do not have permission to create an event in this holon.');
        }
        return $holon;
    }

    public static function spaces(array $grant, array $args): array
    {
        $args = \omoMcpCalendarValidate('omo_list_event_spaces', $args);
        $org = self::organization($grant, 'calendar'); $cursor = $args['after_id'] ?? 0; $limit = $args['limit'] ?? 20;
        $items = []; $complete = false; $scanned = 0;
        while ($scanned < 500 && count($items) < $limit) {
            $rows = self::fetchAll('SELECT h.id FROM holon h LEFT JOIN holon root ON root.id = h.IDholon_org
                WHERE COALESCE(NULLIF(h.IDorganization, 0), root.IDorganization) = :oid
                AND h.active = 1 AND h.visible = 1 AND h.IDtypeholon IN (1, 2) AND h.id > :cursor ORDER BY h.id ASC LIMIT 100',
                ['oid' => (int)$org->getId(), 'cursor' => $cursor]);
            if ($rows === false) throw new \RuntimeException('MCP event spaces query failed.');
            if (!$rows) { $complete = true; break; }
            foreach ($rows as $row) {
                $cursor = (int)$row['id']; $scanned++;
                try { $holon = self::destination($org, $grant, $cursor); } catch (\DomainException $error) { continue; }
                $items[] = ['holon_id' => $cursor, 'name' => trim(strip_tags($holon->getDisplayName())),
                    'holon_type_id' => (int)$holon->get('IDtypeholon'), 'default_invitation_holon_id' => $cursor,
                    'url' => \omoMcpIssuer() . '/omo/o/' . (int)$org->getId() . '/h/' . $cursor];
                if (count($items) === $limit) break;
            }
            if (count($items) < $limit && count($rows) < 100) { $complete = true; break; }
        }
        return ['organization_id' => (int)$org->getId(), 'items' => $items, 'next_after_id' => $complete ? null : $cursor,
            'complete' => $complete, 'write_authorized' => \omoMcpCanCreateEvents($grant), 'required_scope' => \OMO_MCP_EVENT_SCOPE];
    }

    private static function response(Organization $org, array $grant, int $eventId, bool $replayed): array
    {
        $event = new Event();
        if (!$event->load($eventId, true) || !$event->get('active') || (int)$event->get('IDorganization') !== (int)$org->getId()
            || (int)$event->get('IDuser') !== (int)$grant['IDuser']) {
            throw new \DomainException('Previously created event unavailable. Do not retry with a new key.');
        }
        $zone = new \DateTimeZone($event->get('timezone'));
        return ['created' => true, 'replayed' => $replayed, 'event_id' => $eventId, 'organization_id' => (int)$org->getId(),
            'holon_id' => (int)$event->get('IDholon'), 'title' => $event->get('title'), 'status' => $event->get('status'),
            'start_at' => \DateTimeImmutable::createFromInterface($event->get('start_at'))->setTimezone($zone)->format(\DateTimeInterface::ATOM),
            'end_at' => \DateTimeImmutable::createFromInterface($event->get('end_at'))->setTimezone($zone)->format(\DateTimeInterface::ATOM),
            'timezone' => $zone->getName(), 'is_all_day' => (bool)$event->get('is_all_day'),
            'invitation_mode' => $event->hasExplicitInvitations() ? 'explicit' : 'default', 'invitation_counts' => $event->getInvitationCounts(),
            'emails_sent' => false, 'url' => McpContent::sourceUrl((int)$org->getId(), 'calendar', $eventId, (int)$event->get('IDholon'))];
    }

    public static function create(array $grant, array $args): array
    {
        $args = \omoMcpCalendarValidate('omo_create_event', $args);
        if (!\omoMcpCanCreateEvents($grant) || !McpOauthGrant::hasActiveScopeAuthorization($grant, \OMO_MCP_EVENT_SCOPE)) {
            throw new \DomainException('Reconnect and authorize events:create before creating events.');
        }
        $org = self::organization($grant, 'calendar'); $oid = (int)$org->getId(); $uid = (int)$grant['IDuser'];
        self::destination($org, $grant, $args['holon_id']);
        require_once dirname(__DIR__, 2) . '/omo/api/calendar/invitations_shared.php';
        require_once dirname(__DIR__, 2) . '/common/external_calendar.php';
        $holons = $args['invitation_holon_ids'] ?? []; $users = $args['invitation_user_ids'] ?? []; $emails = $args['invitation_emails'] ?? [];
        $selection = \omoCalendarPrepareInvitationSelections($org, $oid, $holons, $users, $emails);
        if (!$selection['status']) throw new \DomainException('Invalid or inaccessible event invitees.');
        foreach ($holons as $hid) {
            $holon = new Holon();
            if (!$holon->load($hid) || !$holon->get('active') || !$holon->get('visible')) throw new \DomainException('Invitation holon unavailable.');
        }
        foreach ($users as $userId) {
            $user = new User();
            if (!$user->load($userId) || !$user->get('active') || !$user->canViewDetail()) throw new \DomainException('Invitation member unavailable.');
        }
        $zone = new \DateTimeZone($args['timezone'] ?? 'Europe/Zurich'); $storageZone = new \DateTimeZone(date_default_timezone_get());
        $start = \omoMcpCalendarDate($args['start_at'], true)->setTimezone($zone); $end = \omoMcpCalendarDate($args['end_at'], true)->setTimezone($zone);
        if ($args['is_all_day'] ?? false) { $start = $start->setTime(0, 0); $end = $end->setTime(23, 59, 59); }
        $fields = ['IDorganization' => $oid, 'IDholon' => $args['holon_id'], 'IDuser' => $uid, 'active' => 1,
            'title' => $args['title'], 'description' => PropertyFormat::formattedTextToHtml($args['description'] ?? '', 'text'),
            'status' => $args['status'] ?? Event::STATUS_CONFIRMED, 'timezone' => $zone->getName(),
            'start_at' => $start->setTimezone($storageZone), 'end_at' => $end->setTimezone($storageZone), 'is_all_day' => $args['is_all_day'] ?? false,
            'locationmode' => $args['locationmode'] ?? null, 'locationaddress' => $args['locationaddress'] ?? null, 'videomeetingurl' => $args['videomeetingurl'] ?? null];
        $fingerprint = $fields;
        $fingerprint['start_at'] = $start->format(\DateTimeInterface::ATOM); $fingerprint['end_at'] = $end->format(\DateTimeInterface::ATOM);
        $fingerprint['invitations'] = [$holons, $users, $emails];
        // Conflict acknowledgement may change on retry; event content and invitees may not.
        $hash = hash('sha256', json_encode($fingerprint, JSON_THROW_ON_ERROR));
        $bindings = ['uid' => $uid, 'oid' => $oid, 'key_hash' => hash('sha256', $args['request_key'])];
        $lookup = 'SELECT * FROM mcp_event_creation WHERE IDuser = :uid AND IDorganization = :oid AND key_hash = :key_hash';
        $row = self::fetchRow($lookup, $bindings);
        if ($row) {
            if (!hash_equals($row['payload_hash'], $hash)) throw new \DomainException('request_key already used for a different event.');
            if ($row['completed']) return self::response($org, $grant, (int)$row['IDevent'], true);
        }
        $event = new Event(); foreach ($fields as $field => $value) $event->set($field, $value);
        $proposed = [];
        foreach ($selection['invitations'] as $values) {
            $invitation = new EventInvitation(); foreach ($values as $field => $value) $invitation->set($field, $value);
            $invitation->set('active', 1); $invitation->set('status', EventInvitation::STATUS_INVITED); $proposed[] = $invitation;
        }
        if (!($args['allow_conflicts'] ?? false)) {
            $targets = $event->getEffectiveInvitationTargets($oid, $proposed);
            if (count($targets['userIds']) > 200) throw new \DomainException('Too many participants for an availability check. Select fewer invitees.');
            $deadline = microtime(true) + 12;
            $report = $event->checkInvitationAvailability($proposed, static fn(int $userId) => \commonExternalCalendarRefreshForAvailability($userId, $deadline));
            if ($report['conflicts'] || $report['unverified']) {
                $report['conflicts'] = array_map(static fn($conflict) => array_intersect_key($conflict, array_flip(['name', 'start', 'end', 'source'])), $report['conflicts']);
                return ['created' => false, 'requires_confirmation' => true, 'availability' => $report,
                    'instructions' => 'No event was saved. Show these warnings, ask the user whether to proceed, then retry the same request_key and payload with allow_conflicts=true only after approval.'];
            }
        }
        $org = self::organization($grant, 'calendar'); self::destination($org, $grant, $args['holon_id']);
        if (!McpOauthGrant::hasActiveScopeAuthorization($grant, \OMO_MCP_EVENT_SCOPE)) throw new \DomainException('Event creation authorization expired or revoked.');
        $pdo = self::getPdo();
        try {
            $pdo->beginTransaction();
            if (!self::execute('INSERT INTO mcp_event_creation (IDuser, IDorganization, key_hash, payload_hash, created_at)
                VALUES (:uid, :oid, :key_hash, :payload_hash, :now) ON DUPLICATE KEY UPDATE id = id', $bindings + ['payload_hash' => $hash, 'now' => time()])) {
                throw new \RuntimeException('MCP event request storage failed.');
            }
            $row = self::fetchRow($lookup . ' FOR UPDATE', $bindings);
            if (!$row || !hash_equals($row['payload_hash'], $hash)) throw new \DomainException('request_key already used for a different event.');
            if ($row['completed']) { $result = self::response($org, $grant, (int)$row['IDevent'], true); $pdo->commit(); return $result; }
            $saved = $event->save();
            if (empty($saved['status'])) throw new \DomainException($saved['text'] ?? 'Event creation failed.');
            $applied = \omoCalendarApplyInvitationSelections($event, $org, $oid, $holons, $users, $emails);
            if (empty($applied['status'])) throw new \DomainException('Event invitations could not be saved.');
            if (!self::execute('UPDATE mcp_event_creation SET IDevent = :event, completed = 1 WHERE id = :id',
                ['event' => (int)$event->getId(), 'id' => (int)$row['id']])) throw new \RuntimeException('MCP event completion failed.');
            $result = self::response($org, $grant, (int)$event->getId(), false); $pdo->commit(); return $result;
        } catch (\Throwable $error) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $error; }
    }
}
