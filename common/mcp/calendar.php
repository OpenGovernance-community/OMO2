<?php
// MCP calendar descriptors and strict input validation. Persistence stays in dbObject.
function omoMcpCalendarTools(): array
{
    $ids = ['type' => 'array', 'items' => ['type' => 'integer', 'minimum' => 1], 'uniqueItems' => true, 'maxItems' => 100];
    return [
        ['name' => 'omo_get_availability', 'title' => 'Find member availability and common free times',
            'description' => 'Read title-free availability for readable active organization members, using the same calculation as the user profile: OMO events across organizations, imported calendars, imported opening calendars and personal working hours/pauses. Resolve names with omo_list_records module team. Supply 1 to 20 user_ids and inclusive date_from/date_to, at most 31 days. Times use Europe/Zurich, as the profile. Returns each member free/busy intervals and common_free_intervals long enough for duration_minutes. Half-hour resolution. incomplete=true means some external calendars could not be verified; never promise availability in that case. No event titles, calendar names, credentials or private event links are returned. This is advisory and does not reserve a slot.',
            'inputSchema' => ['type' => 'object', 'properties' => [
                'user_ids' => array_replace($ids, ['minItems' => 1, 'maxItems' => 20]),
                'date_from' => ['type' => 'string', 'format' => 'date'], 'date_to' => ['type' => 'string', 'format' => 'date'],
                'duration_minutes' => ['type' => 'integer', 'minimum' => 30, 'maximum' => 1440, 'multipleOf' => 30, 'default' => 30]],
                'required' => ['user_ids', 'date_from', 'date_to'], 'additionalProperties' => false]],
        ['name' => 'omo_list_event_spaces', 'title' => 'Find holons where events can be created',
            'description' => 'List active visible roles and circles where your account currently has CAN_CREATE_EVENT. Follow next_after_id until null, including empty pages. Copy holon_id into omo_create_event. Default invitees are the native effective members of the holon; use omo_list_object_members to inspect them. events:create OAuth consent is also required to create an event.',
            'inputSchema' => ['type' => 'object', 'properties' => [
                'after_id' => ['type' => 'integer', 'minimum' => 0, 'default' => 0],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20]], 'additionalProperties' => false]],
        ['name' => 'omo_create_event', 'title' => 'Create an event in an authorized holon',
            'description' => 'Create a user-requested new OMO event in a holon returned by omo_list_event_spaces. Requires events:create OAuth consent and current CAN_CREATE_EVENT permission. start_at/end_at are RFC3339 timestamps with an explicit offset; timezone defaults to Europe/Zurich. Default status confirmed. With no invitation arrays, native holon invitees apply. Supplying any invitation array replaces that default with exactly the selected members, holons and/or external email guests, deduplicated by OMO; the explicit selection must not be empty. Resolve IDs in the authorized organization first. Check availability with omo_get_availability. Creation also checks conflicts: created=false and requires_confirmation=true means nothing was saved. Show the returned warnings and ask whether to proceed; only after user approval retry with allow_conflicts=true. External email guest availability cannot be verified. A successful result has created=true, event_id and url. Reuse the same request_key and payload after errors/timeouts to avoid duplicates. This only creates events/invitation records, without sending invitation emails or creating linked documents.',
            'inputSchema' => ['type' => 'object', 'properties' => [
                'holon_id' => ['type' => 'integer', 'minimum' => 1], 'title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 190],
                'description' => ['type' => 'string', 'maxLength' => 20000],
                'start_at' => ['type' => 'string', 'format' => 'date-time'], 'end_at' => ['type' => 'string', 'format' => 'date-time'],
                'timezone' => ['type' => 'string', 'default' => 'Europe/Zurich'],
                'status' => ['type' => 'string', 'enum' => ['draft', 'option', 'confirmed'], 'default' => 'confirmed'],
                'is_all_day' => ['type' => 'boolean', 'default' => false],
                'locationmode' => ['type' => 'string', 'enum' => ['in_person', 'virtual', 'hybrid']],
                'locationaddress' => ['type' => 'string', 'maxLength' => 1000], 'videomeetingurl' => ['type' => 'string', 'maxLength' => 2000],
                'invitation_user_ids' => $ids, 'invitation_holon_ids' => $ids,
                'invitation_emails' => ['type' => 'array', 'maxItems' => 100, 'uniqueItems' => true, 'items' => ['type' => 'string', 'format' => 'email']],
                'allow_conflicts' => ['type' => 'boolean', 'default' => false],
                'request_key' => ['type' => 'string', 'pattern' => '^[A-Za-z0-9_-]{8,100}$']],
                'required' => ['holon_id', 'title', 'start_at', 'end_at', 'request_key'], 'additionalProperties' => false]],
    ];
}

function omoMcpCalendarDate(string $value, bool $withTime = false): DateTimeImmutable
{
    $pattern = $withTime ? '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})$/D' : '/^\d{4}-\d{2}-\d{2}$/D';
    if (!preg_match($pattern, $value) || !checkdate((int)substr($value, 5, 2), (int)substr($value, 8, 2), (int)substr($value, 0, 4))) {
        throw new InvalidArgumentException('Invalid calendar date. Use ISO dates and RFC3339 timestamps with an explicit offset.');
    }
    if ($withTime && ((int)substr($value, 11, 2) > 23 || (int)substr($value, 14, 2) > 59 || (int)substr($value, 17, 2) > 59
        || (strlen($value) > 20 && ((int)substr($value, 20, 2) > 23 || (int)substr($value, 23, 2) > 59)))) {
        throw new InvalidArgumentException('Invalid calendar time.');
    }
    return new DateTimeImmutable($value, new DateTimeZone('Europe/Zurich'));
}

function omoMcpCalendarValidate(string $name, array $args): array
{
    $descriptor = array_column(omoMcpCalendarTools(), null, 'name')[$name];
    $schema = $descriptor['inputSchema'];
    if (array_diff(array_keys($args), array_keys($schema['properties'])) || array_diff($schema['required'] ?? [], array_keys($args))) {
        throw new InvalidArgumentException('Unknown or missing calendar argument.');
    }
    foreach ($args as $key => $value) {
        $rule = $schema['properties'][$key];
        $valid = match ($rule['type']) {
            'integer' => is_int($value) && $value >= ($rule['minimum'] ?? 0) && $value <= ($rule['maximum'] ?? PHP_INT_MAX)
                && (!isset($rule['multipleOf']) || $value % $rule['multipleOf'] === 0),
            'boolean' => is_bool($value),
            'string' => is_string($value) && !str_contains($value, "\0") && mb_strlen($value, 'UTF-8') <= ($rule['maxLength'] ?? 100)
                && (!isset($rule['minLength']) || mb_strlen(trim($value), 'UTF-8') >= $rule['minLength'])
                && (!isset($rule['enum']) || in_array($value, $rule['enum'], true)) && (!isset($rule['pattern']) || preg_match('/' . $rule['pattern'] . '/D', $value)),
            'array' => is_array($value) && array_is_list($value) && count($value) >= ($rule['minItems'] ?? 0) && count($value) <= $rule['maxItems'],
        };
        if (!$valid) throw new InvalidArgumentException('Invalid calendar argument: ' . $key . '.');
        if ($rule['type'] === 'array') {
            foreach ($value as $item) {
                if ($key === 'invitation_emails' ? (!is_string($item) || strlen($item) > 254 || !filter_var($item, FILTER_VALIDATE_EMAIL)) : (!is_int($item) || $item <= 0)) {
                    throw new InvalidArgumentException('Invalid calendar selection: ' . $key . '.');
                }
            }
            $args[$key] = array_values(array_unique($value)); sort($args[$key]);
        }
    }
    if ($name === 'omo_get_availability') {
        $start = omoMcpCalendarDate($args['date_from']); $end = omoMcpCalendarDate($args['date_to']);
        if ($end < $start || $start->diff($end)->days >= 31) throw new InvalidArgumentException('Availability range must contain 1 to 31 days.');
    }
    if ($name === 'omo_create_event') {
        $start = omoMcpCalendarDate($args['start_at'], true); $end = omoMcpCalendarDate($args['end_at'], true);
        if ($end <= $start || $end->getTimestamp() - $start->getTimestamp() > 31 * 86400) throw new InvalidArgumentException('Event end must follow start, within 31 days.');
        if (!in_array($args['timezone'] ?? 'Europe/Zurich', DateTimeZone::listIdentifiers(), true)) throw new InvalidArgumentException('Invalid timezone.');
        if (isset($args['videomeetingurl']) && $args['videomeetingurl'] !== '') {
            $url = $args['videomeetingurl'];
            if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
                || preg_match('/[\x00-\x20\x7f]/', $url) || parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null) {
                throw new InvalidArgumentException('Invalid meeting URL.');
            }
        }
        $explicit = array_intersect(['invitation_user_ids', 'invitation_holon_ids', 'invitation_emails'], array_keys($args));
        if ($explicit && !array_merge($args['invitation_user_ids'] ?? [], $args['invitation_holon_ids'] ?? [], $args['invitation_emails'] ?? [])) {
            throw new InvalidArgumentException('Explicit invitations must not be empty. Omit all invitation arrays for holon defaults.');
        }
        $args['title'] = trim($args['title']);
    }
    return $args;
}
