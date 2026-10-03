<?php
function omoObjectMailValidate(array $args): void
{
    if (array_diff(array_keys($args), ['object_type', 'object_id', 'subject', 'message', 'request_key', 'audience_token'])
        || !in_array($args['object_type'] ?? null, ['holon', 'event', 'project', 'decision'], true)
        || !is_int($args['object_id'] ?? null) || $args['object_id'] <= 0) throw new InvalidArgumentException('Objet invalide.');
    foreach (['subject' => 250, 'message' => 20000, 'request_key' => 100, 'audience_token' => 64] as $field => $maximum) {
        $value = $args[$field] ?? null;
        if (!is_string($value) || trim($value) === '' || !mb_check_encoding($value, 'UTF-8')
            || mb_strlen($value, 'UTF-8') > $maximum || str_contains($value, "\0")) throw new InvalidArgumentException('Champ invalide : ' . $field);
    }
    if (preg_match('/[\r\n\x00-\x1f\x7f]/', $args['subject'])
        || !preg_match('/^[a-f0-9]{64}$/D', $args['audience_token'])
        || !preg_match('/^[A-Za-z0-9_-]{8,100}$/D', $args['request_key'])) throw new InvalidArgumentException('Identifiant, audience ou sujet invalide.');
}
