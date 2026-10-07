<?php
function omoObjectMailValidateSelection(array $args): void
{
    if (!array_key_exists('user_ids', $args)) return;
    $ids = $args['user_ids'];
    if (($args['object_type'] ?? null) !== 'organization' || !is_array($ids) || !array_is_list($ids)
        || count($ids) < 1 || count($ids) > 500) throw new InvalidArgumentException('Selection de membres invalide.');
    foreach ($ids as $id) {
        if (!is_int($id) || $id <= 0) throw new InvalidArgumentException('Identifiant de membre invalide.');
    }
    if (count(array_unique($ids)) !== count($ids)) throw new InvalidArgumentException('Membres en double.');
}

function omoObjectMailValidate(array $args): void
{
    omoObjectMailValidateSelection($args);
    if (array_diff(array_keys($args), ['object_type', 'object_id', 'user_ids', 'subject', 'message', 'message_format', 'request_key', 'audience_token'])
        || !in_array($args['object_type'] ?? null, ['organization', 'holon', 'event', 'project', 'decision'], true)
        || !is_int($args['object_id'] ?? null) || $args['object_id'] <= 0) throw new InvalidArgumentException('Objet invalide.');
    if (!in_array($args['message_format'] ?? 'plain', ['plain', 'html'], true)) throw new InvalidArgumentException('Format de message invalide.');
    foreach (['subject' => 250, 'message' => 20000, 'request_key' => 100, 'audience_token' => 64] as $field => $maximum) {
        $value = $args[$field] ?? null;
        if (!is_string($value) || trim($value) === '' || !mb_check_encoding($value, 'UTF-8')
            || mb_strlen($value, 'UTF-8') > $maximum || str_contains($value, "\0")) throw new InvalidArgumentException('Champ invalide : ' . $field);
    }
    if (preg_match('/[\r\n\x00-\x1f\x7f]/', $args['subject'])
        || !preg_match('/^[a-f0-9]{64}$/D', $args['audience_token'])
        || !preg_match('/^[A-Za-z0-9_-]{8,100}$/D', $args['request_key'])) throw new InvalidArgumentException('Identifiant, audience ou sujet invalide.');
}
