<?php
// Shared REST/MCP contract. Native Decision objects own persistence and calendar effects.
function omoApiDecisionTools(): array
{
    $ids = ['type' => 'array', 'items' => ['type' => 'integer', 'minimum' => 1], 'uniqueItems' => true, 'maxItems' => 100];
    $date = ['type' => 'string', 'format' => 'date-time'];
    return [
        ['name' => 'omo_list_decision_spaces', 'title' => 'Find spaces for decision creation',
            'description' => 'Find organization space (kind organization) or readable active holons (kind holons) where you have CAN_CREATE_DECISION. Follow next_after_id until null. Copy holon_id into omo_create_decision; 0 means organization space. decisions:create OAuth consent is required to create ballots, including tentative calendar reservations for dated proposals.',
            'inputSchema' => ['type' => 'object', 'properties' => [
                'kind' => ['type' => 'string', 'enum' => ['organization', 'holons'], 'default' => 'organization'],
                'after_id' => ['type' => 'integer', 'minimum' => 0, 'default' => 0],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20]], 'additionalProperties' => false]],
        ['name' => 'omo_create_decision', 'title' => 'Create a ballot with text or date proposals',
            'description' => 'Create a user-requested Decision ballot with one question and 2 to 20 proposals, using simple_vote, majority_judgment or consent. First discover omo_list_decision_spaces. title names the process; question is the ballot question. Descriptions are plain text. Proposals can contain title, description, dates or a mixture; date-only proposals are supported. Dates are RFC3339 with an explicit offset, timezone defaults to Europe/Zurich. consultation_start_at/end_at define elaboration; evaluation_start_at/end_at define voting. Supply both boundaries of each requested phase, in chronological order. No dates leaves a draft; scheduled phases use the native lifecycle. With no invitation arrays, native holon members (or organization members for organization space) and the owner participate. Explicit arrays replace this default; the owner remains a participant. No arbitrary email addresses are accepted. Dated proposals create native tentative calendar events for participants; no separate omo_create_event is needed. Find common times first with omo_get_availability; reservations do not guarantee availability. At results, native decision rules confirm the winner and cancel other slots; ties require manager resolution. visibility_type defaults to organization; everyone explicitly publishes content. public_url is the generic public participation entry, with native identity and invitation checks, not an anonymous voting grant. Creation sends no email. Preview omo_list_object_members with object_type decision and returned decision_id, then use omo_send_object_email with its audience_token and internal or public URL only when the user requests sending. Requires decisions:create consent and current OMO permission. Reuse request_key and identical payload on retries. Confirm creation only with created=true and provide url.',
            'inputSchema' => ['type' => 'object', 'properties' => [
                'holon_id' => ['type' => 'integer', 'minimum' => 0, 'default' => 0,
                    'description' => 'Destination explicitly requested by the user, resolved through omo_list_decision_spaces. Ask if missing or ambiguous. Use 0 only when organization space is requested; do not infer a role from the current page or discovery order.'],
                'title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 190],
                'question' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 190],
                'description' => ['type' => 'string', 'maxLength' => 20000],
                'question_description' => ['type' => 'string', 'maxLength' => 20000],
                'method' => ['type' => 'string', 'enum' => ['simple_vote', 'majority_judgment', 'consent'],
                    'description' => 'Method requested by the user. Ask them to choose simple vote, majority judgment or consent if unspecified; a request for a poll does not select a method.'],
                'visibility_type' => ['type' => 'string', 'enum' => ['organization', 'circle', 'role', 'self', 'everyone'], 'default' => 'organization'],
                'consultation_start_at' => $date, 'consultation_end_at' => $date,
                'evaluation_start_at' => $date, 'evaluation_end_at' => $date,
                'timezone' => ['type' => 'string', 'default' => 'Europe/Zurich'],
                'invitation_user_ids' => $ids, 'invitation_holon_ids' => $ids,
                'proposals' => ['type' => 'array', 'minItems' => 2, 'maxItems' => 20, 'items' => [
                    'type' => 'object', 'properties' => [
                        'title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 190],
                        'description' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 20000],
                        'start_at' => $date, 'end_at' => $date], 'additionalProperties' => false]],
                'request_key' => ['type' => 'string', 'pattern' => '^[A-Za-z0-9_-]{8,100}$']],
                'required' => ['title', 'question', 'method', 'proposals', 'request_key'], 'additionalProperties' => false]],
    ];
}

function omoApiDecisionValidate(string $name, array $args): array
{
    $schema = array_column(omoApiDecisionTools(), 'inputSchema', 'name')[$name];
    $validate = static function ($value, array $rule) use (&$validate): void {
        $type = $rule['type'];
        if ($type === 'object') {
            if ($value instanceof stdClass) $value = get_object_vars($value);
            if (!is_array($value) || ($value !== [] && array_is_list($value))
                || array_diff(array_keys($value), array_keys($rule['properties']))
                || array_diff($rule['required'] ?? [], array_keys($value))) throw new InvalidArgumentException('Unknown or missing decision field.');
            foreach ($value as $key => $item) $validate($item, $rule['properties'][$key]);
        } elseif ($type === 'array') {
            if (!is_array($value) || !array_is_list($value) || count($value) < ($rule['minItems'] ?? 0)
                || count($value) > $rule['maxItems']) throw new InvalidArgumentException('Invalid decision list.');
            foreach ($value as $item) $validate($item, $rule['items']);
            if (!empty($rule['uniqueItems']) && count(array_unique($value)) !== count($value)) throw new InvalidArgumentException('Duplicate invitation ID.');
        } elseif ($type === 'integer') {
            if (!is_int($value) || $value < $rule['minimum'] || $value > ($rule['maximum'] ?? PHP_INT_MAX)) throw new InvalidArgumentException('Invalid decision integer.');
        } else {
            if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")
                || mb_strlen(trim($value), 'UTF-8') < ($rule['minLength'] ?? 0) || mb_strlen($value, 'UTF-8') > ($rule['maxLength'] ?? 100)
                || (isset($rule['enum']) && !in_array($value, $rule['enum'], true))
                || (isset($rule['pattern']) && !preg_match('/' . $rule['pattern'] . '/D', $value))) throw new InvalidArgumentException('Invalid decision text.');
            if (($rule['format'] ?? '') === 'date-time') omoMcpCalendarDate($value, true);
        }
    };
    $validate($args, $schema);
    if ($name !== 'omo_create_decision') return $args;
    $args += ['holon_id' => 0, 'visibility_type' => 'organization', 'timezone' => 'Europe/Zurich'];
    if (!in_array($args['timezone'], DateTimeZone::listIdentifiers(), true)) throw new InvalidArgumentException('Invalid decision timezone.');
    foreach (['title', 'question'] as $key) $args[$key] = trim($args[$key]);
    foreach (['consultation', 'evaluation'] as $phase) {
        $start = $args[$phase . '_start_at'] ?? null; $end = $args[$phase . '_end_at'] ?? null;
        if (($start === null) !== ($end === null) || ($start !== null && omoMcpCalendarDate($end, true) <= omoMcpCalendarDate($start, true))) {
            throw new InvalidArgumentException('Each decision phase needs a start and a later end.');
        }
    }
    if (isset($args['consultation_end_at'], $args['evaluation_start_at'])
        && omoMcpCalendarDate($args['consultation_end_at'], true) > omoMcpCalendarDate($args['evaluation_start_at'], true)) {
        throw new InvalidArgumentException('Evaluation must follow consultation.');
    }
    foreach ($args['proposals'] as &$proposal) {
        $proposal = (array)$proposal;
        if (isset($proposal['title'])) $proposal['title'] = trim($proposal['title']);
        if (!isset($proposal['title']) && !isset($proposal['description']) && !isset($proposal['start_at'])) throw new InvalidArgumentException('Empty proposal.');
        if (isset($proposal['start_at']) !== isset($proposal['end_at'])) throw new InvalidArgumentException('Proposal dates need both boundaries.');
        if (isset($proposal['start_at'])) {
            $start = omoMcpCalendarDate($proposal['start_at'], true); $end = omoMcpCalendarDate($proposal['end_at'], true);
            if ($end <= $start || $end->getTimestamp() - $start->getTimestamp() > 31 * 86400) throw new InvalidArgumentException('Invalid proposal date range.');
            if (isset($args['evaluation_end_at']) && $start < omoMcpCalendarDate($args['evaluation_end_at'], true)) throw new InvalidArgumentException('A proposed slot must start after voting ends.');
        }
    }
    unset($proposal);
    $explicit = false; $count = 0;
    foreach (['invitation_user_ids', 'invitation_holon_ids'] as $key) if (isset($args[$key])) { $explicit = true; sort($args[$key]); $count += count($args[$key]); }
    if ($explicit && !$count) throw new InvalidArgumentException('Explicit invitations must not be empty.');
    return $args;
}
