<?php
/** Response contracts shared by OpenAPI and the public reference. No organization data is used here. */
function omoApiResponseSchemas(): array
{
    static $schemas;
    if ($schemas !== null) return $schemas;
    $object = static fn (array $properties, ?array $required = null): array => [
        'type' => 'object', 'properties' => $properties, 'required' => $required ?? array_keys($properties), 'additionalProperties' => false];
    $ref = static fn (string $name): array => ['$ref' => '#/components/schemas/' . $name];
    $list = static fn (array $items): array => ['type' => 'array', 'items' => $items];
    $s = ['type' => 'string']; $i = ['type' => 'integer']; $b = ['type' => 'boolean'];
    $textNull = ['type' => ['string', 'null']];
    $idNull = ['type' => ['integer', 'null']];
    $url = $s + ['format' => 'uri', 'examples' => ['https://example.org/omo/o/1']];
    $meetingUrl = ['type' => ['string', 'null'], 'format' => 'uri', 'examples' => ['https://example.org/meeting/alice'],
        'description' => 'Public personal appointment booking URL. Share this exact URL in a user-requested message so recipients choose a time with this person. Sharing does not create a booking or guarantee a free slot. Null when no enabled booking profile with a valid destination calendar is configured. No private calendar settings are returned.'];
    $date = $s + ['format' => 'date', 'examples' => ['2026-10-01']];
    $instant = $s + ['format' => 'date-time', 'examples' => ['2026-10-01T10:00:00+02:00']];
    $instantNull = ['type' => ['string', 'null'], 'format' => 'date-time', 'examples' => [null]];
    $strings = $list($s);
    $moduleSchema = $s + ['enum' => OMO_MCP_MODULES];
    $nextId = $idNull + ['description' => 'Cursor for the next page. Keep the same filters and continue until null, including after an empty page.', 'examples' => [null]];
    $nextOffset = $idNull + ['description' => 'Offset for the next page, or null when this selection/text is exhausted.', 'examples' => [null]];
    $instructions = $s + ['description' => 'Usage guidance for this result.'];
    $complete = $b + ['description' => 'True when this enumeration is exhausted. A partial or empty page alone does not prove completeness.', 'examples' => [true]];
    // Legacy serializers keep [] for an empty PHP map, including filters and holonMemberCounts.
    $emptyMap = ['type' => 'array', 'maxItems' => 0];
    $map = ['type' => 'object', 'additionalProperties' => true];
    $schemas = [];
    $schemas['Error'] = $object([
        'error' => $s + ['description' => 'Error code; origin rejection currently uses a readable message.', 'examples' => ['invalid_request']],
        'error_description' => $s + ['description' => 'Readable explanation, when supplied.'],
        'required_scope' => $s + ['description' => 'Additional consent needed for an insufficient_scope response.'],
    ], ['error']);
    $schemas['Navigation'] = $object(['tool' => $s, 'arguments' => ['oneOf' => [$map, $emptyMap]]]);
    $schemas['Holon'] = $object([
        'id' => $i, 'name' => $s, 'full_name' => $s, 'type_id' => $i, 'type' => $s,
        'parent_id' => $idNull + ['description' => 'Null when there is no readable parent.'],
        'organization_id' => $i, 'updated_at' => ['type' => ['string', 'null'], 'format' => 'date-time'], 'url' => $url,
    ]);
    $schemas['Invitee'] = $object([
        'member_id' => $s + ['description' => 'Stable user:<id> or guest:<hash> identity.'],
        'user_id' => $idNull + ['description' => 'Null for an external guest.'], 'name' => $s, 'status' => $s,
        'mail_eligible' => $b + ['description' => 'Email eligibility, not attendance or confirmed presence.', 'examples' => [true]],
    ]);
    $schemas['EffectiveInvitees'] = $object([
        'items' => $list($ref('Invitee')), 'total' => $i, 'complete' => $complete, 'next_offset' => $nextOffset,
        'next_page' => ['anyOf' => [$ref('Navigation'), ['type' => 'null']], 'description' => 'Navigation to the remaining invitations, or null.', 'examples' => [null]],
        'semantics' => $s,
    ]);
    $record = [
        'module' => $moduleSchema, 'record_id' => $i, 'title' => $s,
        'context_holon_id' => $idNull + ['description' => 'Pass this context when reading the record.'], 'url' => $url,
        'date_field' => $s + ['description' => 'Name of the stored date field used by this module.'],
        'date' => ['type' => ['string', 'integer', 'null'], 'description' => 'Stored module date, normally YYYY-MM-DD HH:mm:ss; not an RFC 3339 timestamp.'],
        'user_id' => $i + ['description' => 'team only: member identifier.'], 'member_details' => $ref('Navigation') + ['description' => 'team only: member navigation.'],
        'meeting_booking_url' => array_replace($meetingUrl, ['description' => 'team only: ' . $meetingUrl['description']]),
        'holon_id' => $idNull + ['description' => 'Present for modules attached to a holon.'],
        'parent_id' => $idNull + ['description' => 'structure, documents, pv and projects only.'],
        'status' => $s + ['description' => 'calendar, pv, decision, projects and processus only.'],
        'effective_invitees' => $ref('EffectiveInvitees') + ['description' => 'calendar only: effective people invited directly or through holons.'],
    ];
    foreach (['IDtypeholon' => 'structure', 'IDevent' => 'documents, pv', 'IDproject' => 'calendar'] as $field => $module) {
        $record[$field] = ['type' => ['integer', 'string', 'null'], 'description' => $module . ' only: native field; legacy IDs may be strings.'];
    }
    foreach (['documenttype' => 'documents, pv', 'start_at' => 'calendar', 'end_at' => 'calendar', 'timezone' => 'calendar',
        'planned_start_date' => 'projects', 'planned_end_date' => 'projects', 'project_size' => 'projects',
        'review_date' => 'rules', 'expiration_date' => 'rules', 'scope' => 'rules', 'measurement_frequency' => 'stats',
        'frequency' => 'activities', 'schedule' => 'activities'] as $field => $module) {
        $record[$field] = $textNull + ['description' => $module . ' only: native field. Dates here normally use stored YYYY-MM-DD or YYYY-MM-DD HH:mm:ss values.'];
    }
    $schemas['RecordSummary'] = $object($record, ['module', 'record_id', 'title', 'context_holon_id', 'url', 'date_field', 'date']);
    $schemas['Connection'] = $object([
        'connected' => $b + ['const' => true], 'read_only' => $b + ['examples' => [true]], 'scope' => $s + ['examples' => ['organization:read']],
        'decision_creation_authorized' => $b, 'event_creation_authorized' => $b, 'document_creation_authorized' => $b, 'mail_sending_authorized' => $b,
        'user' => $object(['id' => $i, 'name' => $s, 'meeting_booking_url' => $meetingUrl]),
        'organization' => $object(['id' => $i, 'name' => $s, 'root_holon_id' => $idNull, 'url' => $url]),
        'modules' => $list($moduleSchema), 'coverage' => $s,
    ]);
    $schemas['Catalog'] = $object([
        'organization_id' => $i,
        'modules' => $list($object(['module' => $moduleSchema, 'description' => $s, 'filters' => $strings,
            'user_relations' => $strings, 'statuses' => $list(['type' => ['string', 'integer']]), 'date_field' => $s])),
        'availability' => $object(['tool' => $s, 'member_lookup_module' => $s, 'max_members' => $i, 'max_days' => $i,
            'includes_imported_calendars' => $b, 'timezone' => $s, 'private_event_details' => $b]),
        'decision_creation' => $object(['authorized' => $b, 'scope' => $s, 'discover_tool' => $s, 'create_tool' => $s,
            'requires_omo_permission' => $s, 'methods' => $strings, 'dated_proposals_reserve_calendar' => $b]),
        'event_creation' => $object(['authorized' => $b, 'scope' => $s, 'discover_tool' => $s, 'create_tool' => $s,
            'requires_omo_permission' => $s, 'invitation_modes' => $strings]),
        'member_exploration' => $object(['lookup_tool' => $s, 'lookup_module' => $s, 'details_tool' => $s, 'description' => $s]),
        'object_members' => $object(['tool' => $s, 'object_types' => $strings, 'pagination' => $s, 'description' => $s]),
        'object_mail' => $object(['authorized' => $b, 'scope' => $s, 'send_tool' => $s, 'status_tool' => $s,
            'requires_audience_preview' => $b, 'max_recipients' => $i, 'direct_recipient_limit' => $i, 'arbitrary_addresses' => $b]),
        'document_creation' => $object(['authorized' => $b, 'scope' => $s, 'discover_tool' => $s, 'create_tool' => $s,
            'default_visibility' => $s, 'requires_omo_permission' => $s]), 'instructions' => $instructions,
    ]);
    $schemas['StructurePage'] = $object(['organization_id' => $i, 'items' => $list($ref('Holon')), 'next_after_id' => $nextId]);
    $schemas['AssignmentPage'] = $object([
        'organization_id' => $i, 'items' => $list($object([
            'assignment_id' => $i, 'user_id' => $i, 'user_name' => $s, 'holon_id' => $i, 'holon_name' => $s,
            'holon_type_id' => $i, 'holon_type' => $s, 'membership' => $b, 'focus' => $s, 'url' => $url,
        ])), 'next_after_id' => $nextId, 'complete' => $complete, 'coverage' => $s,
    ]);
    $schemas['Member'] = $object([
        'organization_id' => $i,
        'member' => $object(array_replace($record, ['module' => $s + ['const' => 'team']]) + ['username' => $s, 'email' => $s, 'phone' => $s],
            ['module', 'record_id', 'title', 'context_holon_id', 'url', 'date_field', 'date', 'user_id', 'member_details', 'meeting_booking_url', 'username', 'email', 'phone']),
        'profile' => $ref('Navigation'), 'assignments' => ['anyOf' => [$ref('AssignmentPage'), ['type' => 'null']],
            'description' => 'First direct assignment page, or null if Structure is unavailable.'],
        'related_records' => $list($object(['module' => $s, 'tool' => $s, 'arguments' => $map, 'user_relations' => $strings, 'coverage' => $s])),
        'instructions' => $instructions,
    ]);
    $schemas['RecordPage'] = $object([
        'organization_id' => $i, 'module' => $s,
        'filters' => ['oneOf' => [$map, $emptyMap], 'description' => 'Echo of the active filters; [] when none are supplied.'],
        'items' => $list($ref('RecordSummary')), 'next_after_id' => $nextId, 'complete' => $complete, 'coverage' => $s,
    ]);
    $schemas['RecordText'] = $object([
        'organization_id' => $i, 'module' => $s, 'record_id' => $i, 'context_holon_id' => $idNull, 'mission_id' => $idNull,
        'title' => $s, 'record' => $ref('RecordSummary'), 'url' => $url,
        'text' => $s + ['description' => 'Plain text chunk; external attachment contents and full histories are not included.'],
        'offset' => $i + ['description' => 'Offset in UTF-8 characters, not bytes.'], 'next_offset' => $nextOffset, 'coverage' => $s,
    ]);
    $schemas['SearchPage'] = $object([
        'organization_id' => $i, 'query' => $s, 'modules' => $strings,
        'items' => $list($object(['module' => $s, 'record_id' => $i, 'context_holon_id' => $idNull, 'mission_id' => $idNull,
            'title' => $s, 'subtitle' => $s, 'excerpt' => $s, 'url' => $url, 'effective_invitees' => $ref('EffectiveInvitees')],
            ['module', 'record_id', 'context_holon_id', 'mission_id', 'title', 'subtitle', 'excerpt', 'url'])),
        'next_offset' => $nextOffset, 'selection_count' => $i, 'selection_limit_per_module' => $i,
        'exhaustive' => $b + ['const' => false, 'description' => 'Search is bounded, even when next_offset is null.'],
    ]);
    $schemas['AudienceMember'] = $object([
        'member_id' => $s, 'user_id' => $idNull, 'name' => $s,
        'firstname' => $s + ['description' => 'OMO users only; omitted for external guests.'],
        'lastname' => $s + ['description' => 'OMO users only; omitted for external guests.'],
        'email' => $textNull + ['description' => 'Organization-scoped address, or null if unavailable/invalid.'], 'phone' => $textNull,
        'relations' => $strings, 'status' => $s, 'mail_eligible' => $b + ['examples' => [true]],
    ], ['member_id', 'user_id', 'name', 'email', 'phone', 'relations', 'status', 'mail_eligible']);
    $schemas['AudiencePage'] = $object([
        'organization_id' => $i, 'object_type' => $s, 'object_id' => $i, 'url' => $url, 'title' => $s,
        'items' => $list($ref('AudienceMember')), 'total' => $i,
        'recipient_count' => $i + ['description' => 'Eligible unique email recipients for the full audience, not this page.'],
        'can_send' => $b + ['description' => 'Current OMO permission; mail_authorized must also be true to send via the API.', 'examples' => [true]],
        'audience_token' => $s + ['pattern' => '^[a-f0-9]{64}$', 'examples' => [str_repeat('0', 64)],
            'description' => 'Opaque audience fingerprint, not an authentication token. Copy unchanged into the send request.'],
        'complete' => $complete, 'next_offset' => $nextOffset, 'semantics' => $s, 'mail_authorized' => $b + ['examples' => [true]],
    ]);
    $schemas['DocumentSpaces'] = $object([
        'organization_id' => $i, 'kind' => $s,
        'items' => $list($object(['kind' => $s, 'id' => $i, 'name' => $s, 'holon_id' => $i,
            'parent_document_id' => $i, 'visibility_types' => $strings, 'url' => $url])),
        'next_after_id' => $nextId, 'complete' => $complete, 'write_authorized' => $b, 'file_storage_available' => $b,
        'max_file_bytes' => $i, 'default_visibility' => $s, 'content_formats' => $strings, 'external_links_available' => $b, 'instructions' => $instructions,
    ]);
    $schemas['DocumentCreated'] = $object([
        'created' => $b + ['const' => true], 'replayed' => $b + ['description' => 'True if the existing request_key was replayed; no new document was created.'],
        'record' => $ref('RecordSummary'), 'document_type' => $s, 'document_type_label' => $s,
        'external_url' => $textNull, 'visibility_type' => $textNull, 'file_name' => $textNull,
        'file_size' => $i + ['description' => 'Stored file size in bytes; zero when there is no imported file.'],
    ]);
    $delivery = $object(array_fill_keys(['queued', 'sending', 'sent', 'failed', 'skipped', 'unknown'], $i));
    $mail = ['mail_id' => $i, 'object_type' => $s, 'object_id' => $i, 'recipient_count' => $i, 'delivery' => $delivery,
        'complete' => $b + ['description' => 'No queued/sending deliveries remain. This does not imply every recipient was sent.'], 'instructions' => $instructions];
    $schemas['MailStatus'] = $object($mail);
    $schemas['MailSent'] = $object($mail + [
        'replayed' => $b,
        'delivery_mode' => $s + ['enum' => ['direct', 'queued'], 'description' => 'Omitted on an idempotent replay. sent means SMTP acceptance, not final delivery.'],
    ], [...array_keys($mail), 'replayed']);
    $endInstant = array_replace($instant, ['examples' => ['2026-10-01T11:00:00+02:00']]);
    $schemas['Interval'] = $object(['start_at' => $instant, 'end_at' => $endInstant,
        'duration_minutes' => $i + ['description' => 'Interval length in minutes.', 'examples' => [60]]]);
    $schemas['Availability'] = $object([
        'organization_id' => $i, 'timezone' => $s, 'date_from' => $date, 'date_to' => $date, 'slot_minutes' => $i, 'duration_minutes' => $i,
        'members' => $list($object(['user_id' => $i, 'name' => $s, 'incomplete' => $b,
            'free_intervals' => $list($ref('Interval')), 'busy_intervals' => $list($ref('Interval')), 'url' => $url])),
        'common_free_intervals' => $list($ref('Interval')),
        'incomplete' => $b + ['description' => 'External data is stale, unavailable or outside its coverage; free times are not fully verified.'],
        'advisory' => $b + ['const' => true], 'instructions' => $instructions,
    ]);
    $schemas['EventSpaces'] = $object([
        'organization_id' => $i, 'items' => $list($object(['holon_id' => $i, 'name' => $s, 'holon_type_id' => $i,
            'default_invitation_holon_id' => $i, 'url' => $url])),
        'next_after_id' => $nextId, 'complete' => $complete, 'write_authorized' => $b, 'required_scope' => $s,
    ]);
    $schemas['EventCreated'] = $object([
        'created' => $b + ['const' => true], 'replayed' => $b, 'event_id' => $i, 'organization_id' => $i, 'holon_id' => $i,
        'title' => $s, 'status' => $s, 'start_at' => $instant, 'end_at' => $endInstant, 'timezone' => $s + ['examples' => ['Europe/Zurich']], 'is_all_day' => $b,
        'invitation_mode' => $s + ['enum' => ['explicit', 'default']],
        'invitation_counts' => $object(['total' => $i,
            'holonMemberCounts' => ['oneOf' => [['type' => 'object', 'additionalProperties' => $i], $emptyMap],
                'description' => 'Map of invited holon ID to effective member count; [] when empty.'],
            'members' => $i, 'individualMembers' => $i, 'invitedEmails' => $i, 'confirmedRegistrations' => $i]),
        'emails_sent' => $b + ['const' => false, 'description' => 'Saving invitations does not send email.'], 'url' => $url,
    ]);
    $schemas['DecisionSpaces'] = $object([
        'organization_id' => $i, 'items' => $list($object(['holon_id' => $i, 'name' => $s, 'visibility_types' => $strings])),
        'next_after_id' => $nextId, 'complete' => $complete, 'write_authorized' => $b, 'required_scope' => $s,
    ]);
    $schemas['DecisionCreated'] = $object([
        'created' => $b + ['const' => true], 'replayed' => $b, 'decision_id' => $i, 'group_id' => $i, 'organization_id' => $i,
        'holon_id' => $i, 'title' => $s, 'question' => $s, 'method' => $s + ['enum' => ['simple_vote', 'majority_judgment', 'consent']],
        'status' => $s, 'visibility_type' => $s, 'participant_count' => $i,
        'consultation_start_at' => $instantNull, 'consultation_end_at' => $instantNull,
        'evaluation_start_at' => $instantNull, 'evaluation_end_at' => $instantNull,
        'proposals' => $list($object(['proposal_id' => $i, 'title' => $s, 'description' => $s + ['description' => 'Sanitized HTML.'],
            'start_at' => $instantNull, 'end_at' => $instantNull, 'timezone' => $textNull,
            'event_id' => $idNull, 'calendar_status' => $textNull])),
        'emails_sent' => $b + ['const' => false], 'url' => $url,
        'public_url' => $url + ['description' => 'Generic participation page. Native identity and invitation checks still apply; no anonymous access token.'],
    ]);
    $schemas['EventConflict'] = $object([
        'created' => $b + ['const' => false], 'requires_confirmation' => $b + ['const' => true],
        'availability' => $object([
            'conflicts' => $list($object(['name' => $s,
                'start' => $s + ['description' => 'Local YYYY-MM-DD HH:mm conflict start.', 'examples' => ['2026-10-01 10:00']],
                'end' => $s + ['description' => 'Local YYYY-MM-DD HH:mm conflict end.', 'examples' => ['2026-10-01 11:00']],
                'source' => $s + ['enum' => ['omo', 'external', 'availability']]])),
            'unverified' => $list($object(['name' => $s, 'reason' => $s + ['enum' => ['email', 'cache', 'storage']]])),
            'externalCache' => $b,
        ]), 'instructions' => $instructions,
    ]);
    return $schemas;
}

function omoApiResponseName(string $operation, int $status = 200): string
{
    if ($operation === 'omo_create_event' && $status === 409) return 'EventConflict';
    if ($status >= 400) return 'Error';
    return match ($operation) {
        'omo_connection_info' => 'Connection', 'omo_catalog' => 'Catalog',
        'omo_list_structure' => 'StructurePage', 'omo_get_holon' => 'Holon', 'omo_get_member' => 'Member',
        'omo_list_records' => 'RecordPage', 'omo_read_record' => 'RecordText', 'omo_list_assignments' => 'AssignmentPage',
        'omo_search' => 'SearchPage', 'omo_list_object_members' => 'AudiencePage',
        'omo_list_document_spaces' => 'DocumentSpaces', 'omo_create_document' => 'DocumentCreated',
        'omo_send_object_email' => 'MailSent', 'omo_object_email_status' => 'MailStatus',
        'omo_get_availability' => 'Availability', 'omo_list_event_spaces' => 'EventSpaces', 'omo_create_event' => 'EventCreated',
        'omo_list_decision_spaces' => 'DecisionSpaces', 'omo_create_decision' => 'DecisionCreated',
    };
}

/** Synthetic illustrations derived from the contracts; never samples from a real account. */
function omoApiResponseExample(array $schema, array $schemas): mixed
{
    if (isset($schema['examples'])) return $schema['examples'][0];
    if (array_key_exists('const', $schema)) return $schema['const'];
    if (isset($schema['enum'])) return $schema['enum'][0];
    if (isset($schema['$ref'])) return omoApiResponseExample($schemas[basename($schema['$ref'])], $schemas);
    foreach (['oneOf', 'anyOf'] as $key) if (isset($schema[$key])) return omoApiResponseExample($schema[$key][0], $schemas);
    $type = $schema['type'] ?? 'object';
    if (is_array($type)) $type = in_array('null', $type, true) ? 'null' : $type[0];
    if ($type === 'object') {
        $result = new stdClass();
        foreach ($schema['required'] ?? [] as $key) $result->$key = omoApiResponseExample($schema['properties'][$key], $schemas);
        return $result;
    }
    return match ($type) {
        'array' => ($schema['maxItems'] ?? null) === 0 ? [] : [omoApiResponseExample($schema['items'], $schemas)],
        'integer', 'number' => 1, 'boolean' => false, 'null' => null, default => 'Example',
    };
}
